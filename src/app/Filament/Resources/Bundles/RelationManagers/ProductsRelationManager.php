<?php

namespace App\Filament\Resources\Bundles\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'المنتجات المضمنة';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('اسم المنتج')
                    ->searchable(),

                TextColumn::make('price_cents')
                    ->label('السعر')
                    ->formatStateUsing(fn ($state): string => number_format((int) $state / 100, 2) . ' ج.م'),

                TextColumn::make('access_duration_days')
                    ->label('مدة الوصول')
                    ->formatStateUsing(fn ($state): string => $state ? "{$state} يوم" : 'دائم'),
            ])
            ->headerActions([
                \Filament\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->multiple(),
            ])
            ->recordActions([
                \Filament\Actions\DetachAction::make(),
            ]);
    }
}
