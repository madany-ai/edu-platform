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
        if ($student->academic_year) {
            $map = [
                'prep_1' => '1', 'prep_2' => '2', 'prep_3' => '3',
                'sec_1' => '4', 'sec_2' => '5', 'sec_3' => '6',
            ];
            $gradeNumber = $map[$student->academic_year] ?? 'X';
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
            $code = $prefix . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            if (! $exists($code)) {
                return $code;
            }
        }

        // Fallback: use unique alphanumeric string
        return $prefix . strtoupper(Str::random(6));
    }
}
