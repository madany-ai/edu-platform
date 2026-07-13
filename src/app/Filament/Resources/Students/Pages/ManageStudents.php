<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStudents extends ManageRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    if (empty($data['student_code'])) {
                        $tempStudent = new \App\Models\Student(['grade_level_id' => $data['grade_level_id'] ?? null]);
                        $data['student_code'] = app(\App\Services\CodeGeneratorService::class)->generateStudentCode($tempStudent);
                    }

                    if (empty($data['user_id'])) {
                        $email = $data['email'] ?? null;
                        if (!$email) {
                            throw new \Exception('البريد الإلكتروني مطلوب لإنشاء مستخدم جديد.');
                        }

                        $fullName = trim(($data['first_name'] ?? '') . ' ' . ($data['second_name'] ?? ''));

                        $user = \App\Models\User::create([
                            'name' => $fullName,
                            'email' => $email,
                            'phone' => $data['phone'] ?? null,
                            'password' => \Illuminate\Support\Facades\Hash::make(!empty($data['password']) ? $data['password'] : $data['student_code']),
                            'status' => 'active',
                        ]);
                        $user->assignRole('student');

                        $data['user_id'] = $user->id;
                    }

                    unset($data['email']);
                    unset($data['password']);
                    return $data;
                }),
        ];
    }
}
