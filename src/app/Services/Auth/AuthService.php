<?php

namespace App\Services\Auth;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthService
{
    /** @param array{name: string, email: string, password: string} $data
     * @return array{user: User, token: string} */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole(Role::firstOrCreate(['name' => 'student']));

        Student::create(['user_id' => $user->id]);

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    /** @return array{user: User, token: string}|null */
    public function login(string $email, string $password): ?array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

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
