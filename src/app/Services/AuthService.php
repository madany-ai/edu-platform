<?php

namespace App\Services;

use App\Services\NotificationService;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /** @param array<string, mixed> $data
     * @return array{user: User, token?: string, message?: string} */
    public function register(array $data): array
    {
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
        ]);

        $instructors = User::role('instructor')->get();
        foreach ($instructors as $instructor) {
            $this->notificationService->send(
                $instructor,
                'تسجيل طالب جديد',
                "قام {$data['first_name']} {$data['last_name']} بالتسجيل في المنصة. يرجى مراجعة واعتماد الحساب.",
            );
        }

        return [
            'user' => $user,
            'message' => 'تم إنشاء الحساب بنجاح. يرجى انتظار الموافقة من قبل الإدارة.',
        ];
    }

    /** @return array{user: User, token: string}|string|null */
    public function login(string $email, string $password): array|string|null
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        if ($user->status === 'pending') {
            return 'pending';
        }

        if ($user->status === 'rejected') {
            return 'rejected';
        }

        $user->update(['last_login_at' => now()]);

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
