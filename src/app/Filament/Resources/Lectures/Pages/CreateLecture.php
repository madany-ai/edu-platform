<?php

namespace App\Filament\Resources\Lectures\Pages;

use App\Filament\Resources\Lectures\LectureResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLecture extends CreateRecord
{
    protected static string $resource = LectureResource::class;
}
