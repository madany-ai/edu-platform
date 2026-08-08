<?php

namespace App\Filament\Resources\CenterExams\Pages;

use App\Filament\Resources\CenterExams\CenterExamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCenterExams extends ManageRecords
{
    protected static string $resource = CenterExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
