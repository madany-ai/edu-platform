<?php

namespace App\Filament\Resources\Pricing\Pages;

use App\Filament\Resources\Pricing\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageProducts extends ManageRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Model {
                    $data['instructor_id'] = auth()->id();

                    return static::getModel()::create($data);
                }),
        ];
    }
}
