<?php

namespace App\Models;

use App\Models\AcademicTrack;
use App\Models\City;
use App\Models\Enrollment;
use App\Models\ExamAttempt;
use App\Models\GradeLevel;
use App\Models\Governorate;
use App\Models\QuestionsPost;
use App\Models\School;
use App\Models\Entitlement;
use App\Models\StudentActivity;
use App\Models\StudentStatistic;
use App\Models\User;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Database\Factories\StudentFactory;

class Student extends Model
{
    use HasUuids;
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory;

    protected static function newFactory(): \Database\Factories\StudentFactory
    {
        return \Database\Factories\StudentFactory::new();
    }

    protected $fillable = [
        'user_id', 'student_code', 'first_name', 'second_name', 'third_name', 'last_name',
        'phone', 'father_phone', 'mother_phone', 'guardian_job',
        'governorate_id', 'city_id', 'school_id', 'school_name', 'grade_level_id', 'academic_track_id',
        'gender', 'birth_date', 'profile_image', 'is_verified',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Student $student) {
            if (! $student->student_code) {
                $student->student_code = app(CodeGeneratorService::class)->generateStudentCode($student);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_verified' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function academicTrack(): BelongsTo
    {
        return $this->belongsTo(AcademicTrack::class);
    }

    public function statistics(): HasOne
    {
        return $this->hasOne(StudentStatistic::class, 'student_id');
    }

    public function activity(): HasMany
    {
        return $this->hasMany(StudentActivity::class, 'student_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'student_id');
    }

    public function questionsPosts(): HasMany
    {
        return $this->hasMany(QuestionsPost::class, 'student_id');
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(Entitlement::class, 'student_id');
    }
}
