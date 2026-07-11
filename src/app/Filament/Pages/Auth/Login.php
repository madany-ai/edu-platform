<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;
use Filament\Forms\Components\TextInput;
use SensitiveParameter;

class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('البريد الإلكتروني')
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $login = $data['email'];

        // Find user by email, phone, or assistant_code
        $user = \App\Models\User::where('email', $login)
            ->orWhere('phone', $login)
            ->orWhere('assistant_code', $login)
            ->first();

        // If user is found, authenticate using their email
        $email = $user ? $user->email : $login;

        return [
            'email' => $email,
            'password' => $data['password'],
        ];
    }
}
