<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Str;

class CodeGeneratorService
{
    public function generateStudentCode(Student $student): string
    {
        $gradeNumber = 'X';
        if ($student->grade_level_id) {
            $gradeLevel = $student->gradeLevel ?? \App\Models\GradeLevel::find($student->grade_level_id);
            if ($gradeLevel) {
                $gradeNumber = $gradeLevel->sort_order ?? 'X';
            }
        }
        $prefix = "ST{$gradeNumber}";
        
        return $this->generateUnique($prefix, fn ($code) => Student::where('student_code', $code)->exists());
    }

    public function generateAssistantCode(): string
    {
        $prefix = 'TA';
        
        return $this->generateUnique($prefix, fn ($code) => User::where('assistant_code', $code)->exists());
    }

    public function generateCourseCode(): string
    {
        $prefix = 'CR';
        
        return $this->generateUnique($prefix, fn ($code) => Course::where('course_code', $code)->exists());
    }

    private function generateUnique(string $prefix, callable $exists): string
    {
        $maxAttempts = 100;
        
        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = $prefix . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            if (! $exists($code)) {
                return $code;
            }
        }

        // Fallback: use timestamp-based code
        return $prefix . now()->format('His');
    }
}
