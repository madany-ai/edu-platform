<?php

namespace App\Models;

use App\Models\AcademicTrack;
use App\Models\AssignmentSubmission;
use App\Models\City;
use App\Models\Enrollment;
use App\Models\ExamAttempt;
use App\Models\GradeLevel;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\QuestionsPost;
use App\Models\School;
use App\Models\StudentActivity;
use App\Models\StudentDocument;
use App\Models\StudentStatistic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    protected static function newFactory(): StudentFactory
    {
        return StudentFactory::new();
    }

    protected $fillable = [
        'user_id', 'student_code', 'first_name', 'second_name', 'third_name', 'last_name',
        'phone', 'father_phone', 'mother_phone', 'guardian_job',
        'governorate_id', 'city_id', 'school_id', 'grade_level_id', 'academic_track_id',
        'gender', 'birth_date', 'profile_image', 'is_verified',
    ];

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

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class, 'student_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'student_id');
    }

    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'student_id');
    }

    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'student_id');
    }

    public function questionsPosts(): HasMany
    {
        return $this->hasMany(QuestionsPost::class, 'student_id');
    }
}
