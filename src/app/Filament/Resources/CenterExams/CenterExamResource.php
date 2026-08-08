<?php

namespace App\Filament\Resources\CenterExams;

use App\Filament\Resources\CenterExams\Pages\ManageCenterExams;
use App\Models\CenterExam;
use App\Models\CenterGrade;
use App\Models\Student;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CenterExamResource extends Resource
{
    protected static ?string $model = CenterExam::class;

    protected static ?int $navigationSort = 13;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?string $navigationLabel = 'الامتحانات الورقية والدرجات';

    protected static ?string $pluralLabel = 'الامتحانات الورقية والدرجات';

    protected static ?string $modelLabel = 'امتحان سنتر ورقي';

    protected static \UnitEnum|string|null $navigationGroup = 'إدارة السنتر الأوفلاين';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('اسم الامتحان')
                    ->placeholder('مثال: اختبار شهر أكتوبر في الجبر')
                    ->required()
                    ->maxLength(255),

                TextInput::make('total_marks')
                    ->label('الدرجة النهائية')
                    ->numeric()
                    ->default(20)
                    ->required(),

                Select::make('group_id')
                    ->label('المجموعة المستهدفة')
                    ->relationship('group', 'name')
                    ->searchable()
                    ->preload(),

                Select::make('academic_year_id')
                    ->label('السنة الدراسية')
                    ->relationship('academicYear', 'name')
                    ->searchable()
                    ->preload(),

                DatePicker::make('date')
                    ->label('تاريخ الامتحان')
                    ->default(now())
                    ->required(),

                Textarea::make('description')
                    ->label('وصف / محتوى الامتحان')
                    ->columnSpan(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الامتحان')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('group.name')
                    ->label('المجموعة')
                    ->sortable()
                    ->default('جميع المجموعات'),

                TextColumn::make('total_marks')
                    ->label('الدرجة العظمى')
                    ->sortable(),

                TextColumn::make('date')
                    ->label('تاريخ الامتحان')
                    ->date()
                    ->sortable(),

                TextColumn::make('grades_count')
                    ->label('عدد الدرجات المدخلة')
                    ->counts('grades')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('group_id')
                    ->label('تصفية حسب المجموعة')
                    ->relationship('group', 'name'),
            ])
            ->actions([
                Action::make('enterGrades')
                    ->label('إدخال درجات الطلاب')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('success')
                    ->modalHeading('إدخال درجات الطلاب للامتحان')
                    ->modalSubmitActionLabel('حفظ الدرجات')
                    ->form(function (CenterExam $record): array {
                        $students = $record->group_id
                            ? Student::where('group_id', $record->group_id)->get()
                            : Student::all();

                        $existingGrades = CenterGrade::where('center_exam_id', $record->id)
                            ->pluck('score', 'student_id')
                            ->toArray();

                        $components = [];
                        foreach ($students as $student) {
                            $components[] = TextInput::make("student_{$student->id}")
                                ->label($student->full_name . ' (' . ($student->student_code ?? 'بدون كود') . ')')
                                ->numeric()
                                ->placeholder("الدرجة من {$record->total_marks}")
                                ->default($existingGrades[$student->id] ?? null);
                        }

                        return $components;
                    })
                    ->action(function (CenterExam $record, array $data): void {
                        foreach ($data as $key => $score) {
                            if (str_starts_with($key, 'student_') && $score !== null && $score !== '') {
                                $studentId = str_replace('student_', '', $key);
                                CenterGrade::updateOrCreate(
                                    [
                                        'center_exam_id' => $record->id,
                                        'student_id' => $studentId,
                                    ],
                                    [
                                        'score' => (float) $score,
                                    ]
                                );

                                $student = Student::find($studentId);
                                if ($student) {
                                    app(\App\Services\NotificationService::class)->notifyCenterGrade(
                                        $student,
                                        $record->name,
                                        (float) $score,
                                        (float) $record->total_marks
                                    );
                                }
                            }
                        }

                        Notification::make()
                            ->title('تم حفظ درجات الامتحان وإشعار الطلاب بنجاح!')
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
            'index' => ManageCenterExams::route('/'),
        ];
    }
}
