<?php

namespace App\Filament\Resources\Exams;

use App\Filament\Resources\Exams\Pages\ManageExams;
use App\Models\Exam;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'الامتحانات';

    protected static ?string $pluralLabel = 'الامتحانات';

    protected static ?string $modelLabel = 'الامتحان';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->label('عنوان الامتحان')
                    ->required()
                    ->maxLength(255),

                TextInput::make('duration')
                    ->label('المدة (دقيقة)')
                    ->numeric()
                    ->default(30)
                    ->minValue(1),

                Select::make('lecture_id')
                    ->label('المحاضرة (اختياري)')
                    ->relationship('lecture', 'title')
                    ->searchable(),

                Repeater::make('questions')
                    ->relationship()
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
                            ->relationship()
                            ->schema([
                                TextInput::make('answer')
                                    ->label('الإجابة')
                                    ->required(),
                                Toggle::make('is_correct')
                                    ->label('إجابة صحيحة'),
                            ])
                            ->columns(2)
                            ->hidden(fn ($get): bool => $get('type') === 'essay')
                            ->label('الخيارات'),
                    ])
                    ->label('الأسئلة')
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
                    ->searchable(),

                TextColumn::make('lecture.title')
                    ->label('المحاضرة')
                    ->searchable(),

                TextColumn::make('duration')
                    ->label('المدة')
                    ->formatStateUsing(fn ($state): string => "{$state} دقيقة"),

                TextColumn::make('questions_count')
                    ->label('الأسئلة')
                    ->counts('questions'),

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

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageExams::route('/'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = request()->user();

        return parent::getEloquentQuery()
            ->where('is_assignment', false)
            ->when($user && ! $user->hasRole('super_admin'), function (\Illuminate\Database\Eloquent\Builder $query) use ($user) {
                if ($user->hasRole('instructor')) {
                    $query->where(function ($q) use ($user) {
                        $q->whereHas('lecture.section.course', fn ($c) => $c->where('instructor_id', $user->id))
                          ->orWhereNull('lecture_id');
                    });
                } elseif ($user->hasRole('assistant')) {
                    $query->where(function ($q) use ($user) {
                        $q->whereHas('lecture.section.course.assistants', fn ($c) => $c->where('user_id', $user->id))
                          ->orWhereNull('lecture_id');
                    });
                }
            });
    }
}
