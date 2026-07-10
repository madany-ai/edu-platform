<?php

namespace App\Filament\Resources\Courses;

use App\Enums\CourseStatus;
use App\Filament\Resources\Courses\Pages\ManageCourses;
use App\Models\Course;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

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

                Toggle::make('is_published')
                    ->label('منشور')
                    ->default(false),

                FormSection::make('الأقسام والمحاضرات')
                    ->schema([
                        Repeater::make('sections')
                            ->relationship()
                            ->schema([
                                TextInput::make('title')
                                    ->label('عنوان القسم')
                                    ->required()
                                    ->maxLength(255),

                                Repeater::make('lectures')
                                    ->relationship()
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('عنوان المحاضرة')
                                            ->required()
                                            ->maxLength(255),

                                        Textarea::make('description')
                                            ->label('وصف المحاضرة')
                                            ->rows(2),

                                        TextInput::make('duration')
                                            ->label('المدة (دقائق)')
                                            ->numeric()
                                            ->minValue(0),

                                        TextInput::make('sort_order')
                                            ->label('الترتيب')
                                            ->numeric()
                                            ->default(0),

                                        TextInput::make('bunny_video_id')
                                            ->label('معرّف الفيديو (Bunny Stream)')
                                            ->placeholder('اتركه فارغاً إذا لا يوجد فيديو')
                                            ->maxLength(255),

                                        TextInput::make('pdf_url')
                                            ->label('رابط ملف PDF')
                                            ->url()
                                            ->placeholder('https://...')
                                            ->maxLength(500),
                                    ])
                                    ->columns(2)
                                    ->label('المحاضرات'),
                            ])
                            ->columns(1)
                            ->label('الأقسام'),
                    ])
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

                TextColumn::make('price')
                    ->label('السعر')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2) . ' د.م'),

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
            'index' => ManageCourses::route('/'),
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
            ->when(
                $user && $user->hasRole('instructor') && ! $user->hasRole('super_admin'),
                fn (Builder $query) => $query->where('instructor_id', $user->id)
            );
    }
}
