<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
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
            'body.required' => 'يرجى كتابة نص السؤال.',
            'body.string' => 'نص السؤال يجب أن يكون نصاً.',
            'body.max' => 'نص السؤال طويل جداً. الحد الأقصى 5000 حرف.',
        ];
    }
}
