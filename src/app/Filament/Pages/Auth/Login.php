<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;
use SensitiveParameter;

class Login extends BaseLogin
{
    public function getTitle(): string|Htmlable
    {
        return 'تسجيل الدخول';
    }

    public function getHeading(): string|Htmlable
    {
        return 'تسجيل الدخول';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('البريد الإلكتروني')
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('كلمة المرور')
            ->password()
            ->revealable()
            ->required();
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label('تذكرني');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->label('تسجيل الدخول');
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $login = $data['email'];

        // Find user by email, phone, or assistant_code
        $user = User::where('email', $login)
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
