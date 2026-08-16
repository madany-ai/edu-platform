<?php

namespace App\Services;

use App\Services\NotificationService;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;

class AuthService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /** @param array<string, mixed> $data
     * @return array{user: User, token?: string, message?: string} */
    public function register(array $data): array
    {
        $user = \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'status' => 'pending',
            ]);

            $user->assignRole(Role::firstOrCreate(['name' => 'student']));

            Student::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'second_name' => $data['second_name'],
                'third_name' => $data['third_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'father_phone' => $data['father_phone'],
                'gender' => $data['gender'],
                'birth_date' => $data['birth_date'],
                'governorate_id' => $data['governorate_id'],
                'academic_year' => $data['academic_year'],
            ]);

            return $user;
        });

        try {
            $instructors = User::role('instructor')->get();
            foreach ($instructors as $instructor) {
                $this->notificationService->send(
                    $instructor,
                    'تسجيل طالب جديد',
                    "قام {$data['first_name']} {$data['last_name']} بالتسجيل في المنصة. يرجى مراجعة واعتماد الحساب.",
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send registration notifications: ' . $e->getMessage());
        }

        return [
            'user' => $user,
            'message' => 'تم إنشاء الحساب بنجاح. يرجى انتظار الموافقة من قبل الإدارة.',
        ];
    }

    /** @return array{user: User, token: string}|string|null */
    public function login(string $emailOrCode, string $password): array|string|null
    {
        $user = User::where('email', $emailOrCode)
            ->orWhere('phone', $emailOrCode)
            ->first();

        // Try login by student_code or student phone
        if (! $user) {
            $student = Student::where('student_code', $emailOrCode)
                ->orWhere('phone', $emailOrCode)
                ->first();
            if ($student) {
                $user = $student->user;
            }
        }

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        if ($user->status === \App\Enums\UserStatus::Pending) {
            return 'pending';
        }

        if ($user->status === \App\Enums\UserStatus::Rejected) {
            return 'rejected';
        }

        // Check if password matches student code and force password change
        $student = $user->student;
        if ($student && \Illuminate\Support\Facades\Hash::check($student->student_code, $user->password)) {
            $user->must_change_password = true;
            $user->save();
        }

        $user->update(['last_login_at' => now()]);

        \Illuminate\Support\Facades\Auth::guard('web')->login($user);
        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    public function logout(User $user): void
    {
        \Illuminate\Support\Facades\Auth::guard('web')->logout();
        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
        
        $token = $user->currentAccessToken();
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }
    }

    public function updateProfile(User $user, array $data): User
    {
        if (isset($data['name'])) {
            $user->update(['name' => $data['name']]);
        }

        $student = $user->student;
        if (!$student && $user->hasRole(['student', 'super_admin', 'admin', 'instructor'])) {
            $student = Student::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'student_code' => 'ST' . rand(100000, 999999),
                    'first_name' => $data['first_name'] ?? explode(' ', $user->name)[0] ?? 'Student',
                    'second_name' => 'A',
                    'third_name' => 'B',
                    'last_name' => 'C',
                    'phone' => $data['phone'] ?? '01000000000',
                    'father_phone' => $data['father_phone'] ?? '01000000000',
                    'mother_phone' => $data['mother_phone'] ?? '01000000000',
                    'guardian_job' => 'Guardian',
                    'gender' => 'male',
                    'is_verified' => true,
                ]
            );
        }

        if ($student) {
            $studentData = array_filter([
                'phone' => $data['phone'] ?? null,
                'father_phone' => $data['father_phone'] ?? null,
                'mother_phone' => $data['mother_phone'] ?? null,
                'school_name' => $data['school_name'] ?? null,
            ], fn($val) => $val !== null);

            if (!empty($studentData)) {
                $student->update($studentData);
            }
        }

        return $user->fresh(['student', 'roles']);
    }
}
