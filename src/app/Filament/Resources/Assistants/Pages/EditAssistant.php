<?php

namespace App\Filament\Resources\Assistants\Pages;

use App\Filament\Resources\Assistants\AssistantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditAssistant extends EditRecord
{
    protected static string $resource = AssistantResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['assigned_courses']);
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        return $data;
    }

    protected function afterSave(): void
    {
        $courses = $this->form->getState()['assigned_courses'] ?? [];
        $this->record->assistedCourses()->sync($courses);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
