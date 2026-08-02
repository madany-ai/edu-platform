<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorStudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_code' => $this->student_code,
            'name' => $this->user?->name ?? trim("{$this->first_name} {$this->last_name}"),
            'is_verified' => $this->is_verified,
            'created_at' => $this->created_at,
        ];
    }
}
