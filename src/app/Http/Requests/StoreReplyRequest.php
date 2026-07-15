<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'body' => 'required|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'يرجى كتابة نص الرد.',
            'body.string' => 'نص الرد يجب أن يكون نصاً.',
            'body.max' => 'نص الرد طويل جداً. الحد الأقصى 5000 حرف.',
        ];
    }
}
