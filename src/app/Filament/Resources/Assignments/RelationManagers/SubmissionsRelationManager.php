<?php

namespace App\Filament\Resources\Assignments\RelationManagers;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    protected static ?string $title = 'تسليمات الطلاب';

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

                TextColumn::make('student.user.name')
                    ->label('الطالب')
                    ->searchable(),

                TextColumn::make('file')
                    ->label('الملف')
                    ->copyable()
                    ->limit(30),

                TextColumn::make('content')
                    ->label('المحتوى')
                    ->limit(50),

                TextColumn::make('score')
                    ->label('الدرجة')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ التسليم')
                    ->dateTime('Y-m-d'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('grade')
                    ->label('تصحيح')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->form([
                        TextInput::make('score')
                            ->label('الدرجة')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        Textarea::make('feedback')
                            ->label('ملاحظات')
                            ->rows(3),
                    ])
                    ->action(function (array $data, $record): void {
                        $record->update(['score' => $data['score']]);

                        Notification::make()
                            ->title('تم تصحيح الواجب')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
