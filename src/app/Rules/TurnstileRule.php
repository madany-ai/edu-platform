<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class TurnstileRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secretKey = env('TURNSTILE_SECRET_KEY');

        if (empty($secretKey)) {
            if (app()->environment('local')) {
                return; // Skip validation in local development if no key is set
            }
            $fail('إعدادات الحماية غير مكتملة بالخادم.');
            return;
        }

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => $secretKey,
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        if (!$response->successful() || !$response->json('success')) {
            $fail('فشل التحقق האمني. يرجى التأكد من أنك لست برنامج روبوت.');
        }
    }
}
