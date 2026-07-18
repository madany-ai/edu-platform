<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssistantsRelationManager extends RelationManager
{
    protected static string $relationship = 'assistants';

    protected static ?string $inverseRelationship = 'assistedCourses';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'المساعدون التعليميون';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('id')
                    ->label('المستخدم')
                    ->options(fn () => \App\Models\User::whereHas('roles', fn ($q) => $q->where('name', 'assistant'))->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
            ])
            ->headerActions([
                \Filament\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->role('assistant')),
            ])
            ->recordActions([
                \Filament\Actions\DetachAction::make(),
            ]);
    }
}
