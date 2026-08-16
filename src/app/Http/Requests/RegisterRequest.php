<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'first_name' => 'required|string|max:255',
            'second_name' => 'required|string|max:255',
            'third_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone',
            'father_phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
            'birth_date' => 'required|date',
            'governorate_id' => 'required|uuid|exists:governorates,id',
            'academic_year' => 'required|string|in:prep_1,prep_2,prep_3,sec_1,sec_2,sec_3',
            'cf-turnstile-response' => ['required', 'string', new \App\Rules\TurnstileRule],
        ];
    }

    public function messages(): array
    {
        return [
            'cf-turnstile-response.required' => 'يرجى التحقق من أنك لست روبوت لإكمال التسجيل.',
        ];
    }
}
