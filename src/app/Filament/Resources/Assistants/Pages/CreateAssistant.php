<?php

namespace App\Filament\Resources\Assistants\Pages;

use App\Filament\Resources\Assistants\AssistantResource;
use App\Models\User;
use App\Services\CodeGeneratorService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateAssistant extends CreateRecord
{
    protected static string $resource = AssistantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['assigned_courses']);
        $data['password'] = Hash::make($data['password']);
        $data['status'] = 'active';
        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        $user->assignRole('assistant');

        if (! $user->assistant_code) {
            $user->assistant_code = app(CodeGeneratorService::class)->generateAssistantCode();
            $user->save();
        }

        $courses = $this->form->getState()['assigned_courses'] ?? [];
        if (! empty($courses)) {
            $user->assistedCourses()->sync($courses);
        }
    }
}
