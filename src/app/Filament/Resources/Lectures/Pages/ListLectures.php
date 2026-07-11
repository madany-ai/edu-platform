<?php

namespace App\Filament\Resources\Lectures\Pages;

use App\Filament\Resources\Lectures\LectureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLectures extends ListRecords
{
    protected static string $resource = LectureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
