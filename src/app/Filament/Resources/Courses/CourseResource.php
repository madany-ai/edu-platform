<?php

namespace App\Filament\Resources\Courses;

use App\Enums\CourseStatus;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Models\Course;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'الدورات';

    protected static ?string $pluralLabel = 'الدورات';

    protected static ?string $modelLabel = 'الدورة';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->label('عنوان الدورة')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('وصف الدورة')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),

                FileUpload::make('thumbnail')
                    ->label('صورة مصغرة للكورس (اختياري)')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                    ->maxSize(5120) // 5 MB
                    ->directory('courses/thumbnails')
                    ->columnSpanFull(),

                TextInput::make('price')
                    ->label('السعر')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0),

                Select::make('status')
                    ->label('الحالة')
                    ->options(CourseStatus::class)
                    ->default(CourseStatus::Draft)
                    ->required(),

                FormSection::make('أقسام الدورة (الشهور)')
                    ->schema([
                        Repeater::make('sections')
                            ->relationship()
                            ->schema([
                                TextInput::make('title')
                                    ->label('عنوان القسم / الشهر')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('sort_order')
                                    ->label('ترتيب القسم')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->columns(2)
                            ->label('الأقسام'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course_code')
                    ->label('#')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('السعر')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2) . ' ج.م'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                        'archived' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'مسودة',
                        'published' => 'منشور',
                        'archived' => 'مؤرشف',
                        default => $state,
                    }),

                TextColumn::make('sections_count')
                    ->label('الأقسام')
                    ->counts('sections'),

                TextColumn::make('enrollments_count')
                    ->label('المسجلين')
                    ->counts('enrollments'),

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
            'index' => ListCourses::route('/'),
            'create' => CreateCourse::route('/create'),
            'edit' => EditCourse::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Courses\RelationManagers\AssistantsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = request()->user();

        return parent::getEloquentQuery()
            ->withCount(['sections', 'enrollments'])
            ->when($user && ! $user->hasRole('super_admin'), function (Builder $query) use ($user) {
                if ($user->hasRole('instructor')) {
                    $query->where('instructor_id', $user->id);
                } elseif ($user->hasRole('assistant')) {
                    $query->whereHas('assistants', fn (Builder $q) => $q->where('user_id', $user->id));
                }
            });
    }

    public static function canCreate(): bool
    {
        return ! auth()->user()->hasRole('assistant');
    }

    public static function canEdit(Model $record): bool
    {
        return ! auth()->user()->hasRole('assistant');
    }

    public static function canDelete(Model $record): bool
    {
        return ! auth()->user()->hasRole('assistant');
    }
}
