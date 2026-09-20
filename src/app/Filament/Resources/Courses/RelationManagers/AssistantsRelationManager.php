<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->options(fn () => User::whereHas('roles', fn ($q) => $q->where('name', 'assistant'))->pluck('name', 'id'))
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
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->role('assistant')),
            ])
            ->recordActions([
                DetachAction::make(),
            ]);
    }
}
