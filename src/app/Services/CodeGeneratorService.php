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
        $prefix = '';
        $track = $student->academic_track ?? 'general';

        switch ($student->academic_year) {
            case 'prep_1': $prefix = '71'; break;
            case 'prep_2': $prefix = '81'; break;
            case 'prep_3': $prefix = '91'; break;
            case 'sec_1':  $prefix = '12'; break;
            case 'sec_2':  $prefix = '22'; break;
            case 'sec_3':
                if ($track === 'math') {
                    $prefix = '31';
                } elseif ($track === 'literary') {
                    $prefix = '32';
                } else {
                    $prefix = '31'; // default to math for sec_3 if unknown
                }
                break;
            default: $prefix = '99'; break;
        }

        $maxAttempts = 100;
        // الاعتماد على الوقت (الدقائق والثواني) كما طلبت
        $timeDigits = date('is');
        
        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = $prefix . str_pad($timeDigits, 4, '0', STR_PAD_LEFT);
            if (! Student::where('student_code', $code)->exists()) {
                return $code;
            }
            // في حالة نادرة جداً أن الوقت تكرر بنفس الدقيقة والثانية، نولد رقم عشوائي
            $timeDigits = (string) random_int(1000, 9999);
        }

        // Fallback: use time based to guarantee uniqueness if 4 digits are exhausted
        return $prefix . substr(time(), -4);
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
