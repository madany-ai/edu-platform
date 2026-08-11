<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roles = $this->roles->pluck('name');
        
        $studentData = null;
        if ($this->relationLoaded('student') && $this->student) {
            $studentData = [
                'id' => $this->student->id,
                'first_name' => $this->student->first_name,
                'last_name' => $this->student->last_name,
                'student_code' => $this->student->student_code,
                'phone' => $this->student->phone,
                'father_phone' => $this->student->father_phone,
                'mother_phone' => $this->student->mother_phone,
                'school_name' => $this->student->school_name,
                'is_verified' => (bool) $this->student->is_verified,
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'roles' => $roles,
            'must_change_password' => (bool) $this->must_change_password,
            'student' => $studentData,
        ];
    }
}
