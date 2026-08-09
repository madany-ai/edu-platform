<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => 'required|string',
            'password' => 'required|string',
            'cf-turnstile-response' => app()->environment('local', 'testing') ? ['nullable'] : ['required', 'string', new \App\Rules\TurnstileRule],
        ];
    }

    public function messages(): array
    {
        return [
            'cf-turnstile-response.required' => 'يرجى التحقق من أنك لست روبوت لإكمال تسجيل الدخول.',
        ];
    }
}
