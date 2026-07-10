<?php

namespace App\Filament\Resources\Assignments;

use App\Filament\Resources\Assignments\Pages\ManageAssignments;
use App\Models\Assignment;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'الواجبات';

    protected static ?string $pluralLabel = 'الواجبات';

    protected static ?string $modelLabel = 'الواجب';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->label('عنوان الواجب')
                    ->required()
                    ->maxLength(255),

                Select::make('lecture_id')
                    ->label('المحاضرة')
                    ->relationship('lecture', 'title')
                    ->searchable()
                    ->required(),

                TextInput::make('degree')
                    ->label('الدرجة')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),

                Textarea::make('description')
                    ->label('وصف الواجب')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lecture.title')
                    ->label('المحاضرة')
                    ->searchable(),

                TextColumn::make('degree')
                    ->label('الدرجة'),

                TextColumn::make('submissions_count')
                    ->label('التسليمات')
                    ->counts('submissions'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAssignments::route('/'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Assignments\RelationManagers\SubmissionsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = request()->user();

        return parent::getEloquentQuery()
            ->withCount('submissions')
            ->when(
                $user && $user->hasRole('instructor') && ! $user->hasRole('super_admin'),
                fn ($query) => $query->whereHas('lecture.section.course', fn ($q) => $q->where('instructor_id', $user->id))
            );
    }
}
