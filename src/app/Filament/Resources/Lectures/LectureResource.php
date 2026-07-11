<?php

namespace App\Filament\Resources\Lectures;

use App\Filament\Resources\Lectures\Pages\CreateLecture;
use App\Filament\Resources\Lectures\Pages\EditLecture;
use App\Filament\Resources\Lectures\Pages\ListLectures;
use App\Models\Lecture;
use App\Models\CourseSection;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LectureResource extends Resource
{
    protected static ?string $model = Lecture::class;

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static ?string $navigationLabel = 'المحاضرات';

    protected static ?string $pluralLabel = 'المحاضرات';

    protected static ?string $modelLabel = 'محاضرة';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                FormSection::make('البيانات الأساسية')
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان المحاضرة')
                            ->required()
                            ->maxLength(255),

                        Select::make('section_id')
                            ->label('القسم / الشهر')
                            ->options(function () {
                                return CourseSection::with('course')
                                    ->get()
                                    ->mapWithKeys(fn ($s) => [$s->id => "{$s->course?->title} - {$s->title}"]);
                            })
                            ->searchable()
                            ->required(),

                        Textarea::make('description')
                            ->label('وصف المحاضرة')
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('duration')
                            ->label('المدة (دقائق)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),

                        TextInput::make('sort_order')
                            ->label('ترتيب المحاضرة')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),

                FormSection::make('الملفات والفيديو')
                    ->schema([
                        FileUpload::make('video_path')
                            ->label('فيديو المحاضرة')
                            ->disk('minio')
                            ->directory('lectures')
                            ->visibility('private')
                            ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg'])
                            ->maxSize(1024000), // 1 GB

                        FileUpload::make('pdf_url')
                            ->label('ملف الـ PDF')
                            ->disk('minio')
                            ->directory('pdfs')
                            ->acceptedFileTypes(['application/pdf']),
                    ])->columns(2),

                FormSection::make('الامتحانات')
                    ->description('أضف امتحانات لهذه المحاضرة وحدد شروط النجاح')
                    ->schema([
                        Repeater::make('exams')
                            ->relationship('exams')
                            ->label('الامتحانات')
                            ->schema([
                                TextInput::make('title')
                                    ->label('عنوان الامتحان')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('duration')
                                    ->label('المدة (دقائق)')
                                    ->numeric()
                                    ->default(30),

                                TextInput::make('pass_percentage')
                                    ->label('نسبة النجاح (%)')
                                    ->numeric()
                                    ->default(50)
                                    ->minValue(1)
                                    ->maxValue(100),

                                Toggle::make('is_blocking')
                                    ->label('حجب باقي المحتوى حتى النجاح')
                                    ->default(true),

                                Repeater::make('questions')
                                    ->relationship('questions')
                                    ->label('الأسئلة')
                                    ->schema([
                                        Select::make('type')
                                            ->label('نوع السؤال')
                                            ->options([
                                                'multiple_choice' => 'اختيار متعدد',
                                                'true_false' => 'صح / خطأ',
                                                'essay' => 'مقال',
                                            ])
                                            ->live()
                                            ->default('multiple_choice'),

                                        FileUpload::make('image_path')
                                            ->label('صورة للسؤال (اختياري)')
                                            ->disk('minio')
                                            ->directory('questions')
                                            ->image()
                                            ->columnSpanFull(),

                                        Textarea::make('question')
                                            ->label('نص السؤال')
                                            ->required()
                                            ->rows(2)
                                            ->columnSpanFull(),

                                        TextInput::make('degree')
                                            ->label('النقاط')
                                            ->numeric()
                                            ->required()
                                            ->default(1),

                                        Repeater::make('choices')
                                            ->relationship('choices')
                                            ->label('الخيارات')
                                            ->schema([
                                                TextInput::make('answer')
                                                    ->label('الإجابة')
                                                    ->required(),
                                                Toggle::make('is_correct')
                                                    ->label('إجابة صحيحة'),
                                            ])
                                            ->hidden(fn ($get): bool => $get('type') === 'essay')
                                            ->columns(2)
                                    ])
                                    ->collapsible()
                                    ->columnSpanFull()
                            ])
                            ->orderColumn('sort_order')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->columnSpanFull(),
                    ])->columnSpanFull(),

                FormSection::make('الواجب')
                    ->description('أضف واجبات لهذه المحاضرة وحدد شروط النجاح')
                    ->schema([
                        Repeater::make('assignments')
                            ->relationship('assignments')
                            ->label('الواجبات')
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => array_merge($data, ['is_assignment' => true]))
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => array_merge($data, ['is_assignment' => true]))
                            ->schema([
                                TextInput::make('title')
                                    ->label('عنوان الواجب')
                                    ->required()
                                    ->maxLength(255),

                                Repeater::make('questions')
                                    ->relationship('questions')
                                    ->label('الأسئلة')
                                    ->schema([
                                        Select::make('type')
                                            ->label('نوع السؤال')
                                            ->options([
                                                'multiple_choice' => 'اختيار متعدد',
                                                'true_false' => 'صح / خطأ',
                                                'essay' => 'مقال',
                                            ])
                                            ->live()
                                            ->default('multiple_choice'),

                                        FileUpload::make('image_path')
                                            ->label('صورة للسؤال (اختياري)')
                                            ->disk('minio')
                                            ->directory('questions')
                                            ->image()
                                            ->columnSpanFull(),

                                        Textarea::make('question')
                                            ->label('نص السؤال')
                                            ->required()
                                            ->rows(2)
                                            ->columnSpanFull(),

                                        TextInput::make('degree')
                                            ->label('النقاط')
                                            ->numeric()
                                            ->required()
                                            ->default(1),

                                        Repeater::make('choices')
                                            ->relationship('choices')
                                            ->label('الخيارات')
                                            ->schema([
                                                TextInput::make('answer')
                                                    ->label('الإجابة')
                                                    ->required(),
                                                Toggle::make('is_correct')
                                                    ->label('إجابة صحيحة'),
                                            ])
                                            ->hidden(fn ($get): bool => $get('type') === 'essay')
                                            ->columns(2)
                                    ])
                                    ->collapsible()
                                    ->columnSpanFull()
                            ])
                            ->orderColumn('sort_order')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
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
                    ->label('عنوان المحاضرة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('section.course.title')
                    ->label('الدورة')
                    ->searchable(),

                TextColumn::make('section.title')
                    ->label('القسم')
                    ->searchable(),

                TextColumn::make('exam_count')
                    ->label('عدد الامتحانات')
                    ->counts('exams'),

                TextColumn::make('assignment_count')
                    ->label('عدد الواجبات')
                    ->counts('assignments'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('course')
                    ->label('الفلترة حسب الدورة')
                    ->relationship('section.course', 'title'),
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
            'index' => ListLectures::route('/'),
            'create' => CreateLecture::route('/create'),
            'edit' => EditLecture::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = request()->user();

        return parent::getEloquentQuery()
            ->when($user && ! $user->hasRole('super_admin'), function (Builder $query) use ($user) {
                if ($user->hasRole('instructor')) {
                    $query->whereHas('section.course', fn ($q) => $q->where('instructor_id', $user->id));
                } elseif ($user->hasRole('assistant')) {
                    $query->whereHas('section.course.assistants', fn ($q) => $q->where('user_id', $user->id));
                }
            });
    }
}
