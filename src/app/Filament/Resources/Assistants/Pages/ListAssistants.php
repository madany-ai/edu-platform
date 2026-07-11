<?php

namespace App\Filament\Resources\Assistants\Pages;

use App\Filament\Resources\Assistants\AssistantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssistants extends ListRecords
{
    protected static string $resource = AssistantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
