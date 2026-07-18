<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveLectureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'integer',
            'is_free' => 'boolean',
            'youtube_url' => 'nullable|url',
            'duration' => 'nullable|integer',
        ];
    }
}
