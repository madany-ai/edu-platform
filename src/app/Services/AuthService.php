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
                'mother_phone' => $data['mother_phone'],
                'guardian_job' => $data['guardian_job'],
                'gender' => $data['gender'],
                'birth_date' => $data['birth_date'],
                'governorate_id' => $data['governorate_id'],
                'grade_level_id' => $data['grade_level_id'],
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

        $user->update(['last_login_at' => now()]);

        \Illuminate\Support\Facades\Auth::guard('web')->login($user);
        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return [
            'user' => $user,
            // 'token' => $user->createToken('api')->plainTextToken, // SPA uses stateful session cookie
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
}
