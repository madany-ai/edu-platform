<?php

namespace App\Filament\Resources\Lectures;

use App\Filament\Resources\Lectures\Pages\CreateLecture;
use App\Filament\Resources\Lectures\Pages\EditLecture;
use App\Filament\Resources\Lectures\Pages\ListLectures;
use App\Models\Lecture;
use App\Models\CourseSection;
use BackedEnum;
use Filament\Actions\Action;
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
                FormSection::make('البيانات الأساسية وموضع المحاضرة')
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان المحاضرة')
                            ->required()
                            ->maxLength(255),

                        Select::make('section_id')
                            ->label('القسم / الشهر (داخل كورس)')
                            ->options(function () {
                                return CourseSection::with('course')
                                    ->get()
                                    ->mapWithKeys(fn ($s) => [$s->id => "{$s->course?->title} - {$s->title}"]);
                            })
                            ->searchable()
                            ->nullable()
                            ->placeholder('محاضرة منفردة (بدون كورس)')
                            ->helperText('اترك الحقل فارغاً إذا كانت المحاضرة منفردة خارج أي كورس.'),

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

                FormSection::make('إعدادات البيع والباقات (Monetization & Bundles)')
                    ->description('تحديد خيارات بيع المحاضرة بشكل منفرد أو ربطها بباقات مدفوعة')
                    ->schema([
                        Toggle::make('is_standalone')
                            ->label('إتاحة كـ محاضرة منفردة للبيع المستقل (Product)')
                            ->default(true)
                            ->live()
                            ->helperText('عند التفعيل، يتم إنشاء منتج مالي خاص بالمحاضرة لبيعها بشكل منفرد للطلاب.'),

                        TextInput::make('price')
                            ->label('سعر المحاضرة المنفردة (جنيه مصري)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(fn ($get): bool => (bool) $get('is_standalone'))
                            ->visible(fn ($get): bool => (bool) $get('is_standalone')),

                        TextInput::make('access_duration_days')
                            ->label('مدة صلاحية الوصول (بالأيام)')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('اترك فارغاً للوصول الدائم دون انتهاء')
                            ->visible(fn ($get): bool => (bool) $get('is_standalone')),

                        Select::make('bundles')
                            ->label('إضافة إلى الباقات (Bundles)')
                            ->options(fn () => \App\Models\Bundle::pluck('name', 'id'))
                            ->multiple()
                            ->preload()
                            ->helperText('اختر الباقات التي ترغب في إدراج هذه المحاضرة ضمنها تلقائياً.'),
                    ])->columns(2),

                FormSection::make('الملفات والفيديو')
                    ->schema([
                        TextInput::make('youtube_url')
                            ->label('رابط يوتيوب (بديل لرفع الفيديو)')
                            ->url()
                            ->columnSpanFull()
                            ->helperText('إذا قمت بوضع رابط يوتيوب هنا، سيتم تجاهل ملف الفيديو المرفوع بالأسفل.'),

                        FileUpload::make('video_path')
                            ->label('فيديو المحاضرة')
                            ->disk('public')
                            ->directory('lectures')
                            ->visibility('private')
                            ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg'])
                            ->maxSize(1024000), // 1 GB

                        Repeater::make('files')
                            ->relationship('files')
                            ->label('الملفات المرفقة (PDF)')
                            ->schema([
                                TextInput::make('type')
                                    ->label('نوع الملف')
                                    ->default('PDF')
                                    ->required(),
                                FileUpload::make('file_path')
                                    ->label('الملف')
                                    ->disk('public')
                                    ->directory('lecture-files')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(20480) // 20 MB
                                    ->required(),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->columnSpanFull(),
                    ])->columns(2),

                FormSection::make('الامتحانات')
                    ->description('أضف امتحانات لهذه المحاضرة وحدد شروط النجاح')
                    ->schema([
                        Repeater::make('exams')
                            ->relationship('exams')
                            ->label('الامتحانات')
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => array_merge($data, ['is_assignment' => false]))
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => array_merge($data, ['is_assignment' => false]))
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
                                                'essay' => 'مقال',
                                            ])
                                            ->live()
                                            ->default('multiple_choice'),

                                        FileUpload::make('image_path')
                                            ->label('صورة للسؤال (اختياري)')
                                            ->disk('public')
                                            ->directory('questions')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                                            ->maxSize(2048) // 2 MB
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
                                    ->default(false),

                                Repeater::make('questions')
                                    ->relationship('questions')
                                    ->label('الأسئلة')
                                    ->schema([
                                        Select::make('type')
                                            ->label('نوع السؤال')
                                            ->options([
                                                'multiple_choice' => 'اختيار متعدد',
                                                'essay' => 'مقال',
                                            ])
                                            ->live()
                                            ->default('multiple_choice'),

                                        FileUpload::make('image_path')
                                            ->label('صورة للسؤال (اختياري)')
                                            ->disk('public')
                                            ->directory('questions')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                                            ->maxSize(2048) // 2 MB
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
                    ->label('الكورس')
                    ->placeholder('محاضرة منفردة')
                    ->searchable(),

                TextColumn::make('section.title')
                    ->label('القسم')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('placement_type')
                    ->label('الموضع والبيع')
                    ->state(function (Lecture $record): string {
                        $hasSection = (bool) $record->section_id;
                        $hasProduct = $record->products()->where('is_active', true)->exists();
                        if ($hasSection && $hasProduct) return 'كورس + منفردة';
                        if ($hasSection) return 'داخل كورس';
                        if ($hasProduct) return 'منفردة (مدفوعة)';
                        return 'منفردة (مجانية)';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'كورس + منفردة' => 'success',
                        'داخل كورس' => 'info',
                        'منفردة (مدفوعة)' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('exams.title')
                    ->label('الامتحانات')
                    ->listWithLineBreaks()
                    ->bulleted(),

                TextColumn::make('assignments.title')
                    ->label('الواجبات')
                    ->listWithLineBreaks()
                    ->bulleted(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('course')
                    ->label('الفلترة حسب الدورة')
                    ->relationship('section.course', 'title'),
                \Filament\Tables\Filters\SelectFilter::make('placement')
                    ->label('نوع الموضع')
                    ->options([
                        'standalone' => 'محاضرات منفردة فقط',
                        'course' => 'داخل كورسات فقط',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'standalone') {
                            $query->whereNull('section_id');
                        } elseif ($data['value'] === 'course') {
                            $query->whereNotNull('section_id');
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('manageBundles')
                    ->label('ربط بباقة')
                    ->icon(Heroicon::OutlinedGift)
                    ->color('warning')
                    ->form([
                        Select::make('bundles')
                            ->label('اختر الباقات المشمولة')
                            ->options(fn () => \App\Models\Bundle::pluck('name', 'id'))
                            ->multiple()
                            ->preload()
                            ->default(function (Lecture $record): array {
                                $product = \App\Models\Product::where('sellable_id', $record->id)
                                    ->where('sellable_type', Lecture::class)
                                    ->first();
                                return $product ? $product->bundles()->pluck('bundles.id')->toArray() : [];
                            }),
                    ])
                    ->action(function (Lecture $record, array $data): void {
                        $instructorId = $record->resolveInstructorId() ?? auth()->id();
                        $product = \App\Models\Product::updateOrCreate(
                            [
                                'sellable_id' => $record->id,
                                'sellable_type' => Lecture::class,
                            ],
                            [
                                'instructor_id' => $instructorId,
                                'name' => 'محاضرة: ' . $record->title,
                                'price' => $record->price ?? 0,
                                'is_active' => true,
                            ]
                        );

                        if (isset($data['bundles']) && is_array($data['bundles'])) {
                            $product->bundles()->sync($data['bundles']);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('تم ربط المحاضرة بالباقات بنجاح')
                            ->success()
                            ->send();
                    }),
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
                    $query->where(function ($q) use ($user) {
                        $q->whereHas('section.course', fn ($c) => $c->where('instructor_id', $user->id))
                          ->orWhere('instructor_id', $user->id);
                    });
                } elseif ($user->hasRole('assistant')) {
                    $query->whereHas('section.course.assistants', fn ($q) => $q->where('user_id', $user->id));
                }
            });
    }
}
