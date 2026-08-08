<?php

namespace App\Filament\Resources\AcademicSessions;

use App\Filament\Resources\AcademicSessions\Pages\ManageAcademicSessions;
use App\Models\AcademicSession;
use App\Models\Attendance;
use App\Models\Student;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
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

class AcademicSessionResource extends Resource
{
    protected static ?string $model = AcademicSession::class;

    protected static ?int $navigationSort = 12;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'الحصص والغياب';

    protected static ?string $pluralLabel = 'الحصص والغياب';

    protected static ?string $modelLabel = 'حصة دراسية';

    protected static \UnitEnum|string|null $navigationGroup = 'إدارة السنتر الأوفلاين';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('group_id')
                    ->label('المجموعة الدراسية')
                    ->relationship('group', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                DatePicker::make('date')
                    ->label('تاريخ الحصة')
                    ->default(now())
                    ->required(),

                TextInput::make('topic')
                    ->label('موضوع الحصة / الدرس')
                    ->placeholder('مثال: التفاعلات الكيميائية - الدرس الأول')
                    ->required()
                    ->columnSpan(2),

                Textarea::make('notes')
                    ->label('ملاحظات الحصة')
                    ->columnSpan(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group.name')
                    ->label('المجموعة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('group.gradeLevel.name')
                    ->label('الصف الدراسي')
                    ->sortable(),

                TextColumn::make('date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),

                TextColumn::make('topic')
                    ->label('موضوع الحصة')
                    ->searchable(),

                TextColumn::make('attendances_count')
                    ->label('عدد الحاضرين')
                    ->counts('attendances')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('group_id')
                    ->label('تصفية حسب المجموعة')
                    ->relationship('group', 'name'),
            ])
            ->actions([
                Action::make('markAttendance')
                    ->label('تسجيل الحضور يدوي')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->modalHeading('تسجيل كشف حضور الطلاب للحصة')
                    ->modalSubmitActionLabel('حفظ الحضور')
                    ->form(function (AcademicSession $record): array {
                        $students = Student::where('group_id', $record->group_id)->get();
                        $existing = Attendance::where('session_id', $record->id)->pluck('status', 'student_id')->toArray();

                        $components = [];
                        foreach ($students as $student) {
                            $components[] = Select::make("student_{$student->id}")
                                ->label($student->full_name . ' (' . ($student->student_code ?? 'بدون كود') . ')')
                                ->options([
                                    'present' => 'حاضر ✅',
                                    'absent' => 'غائب ❌',
                                    'late' => 'متأخر ⏳',
                                    'guest' => 'حاضر كضيف 👤',
                                ])
                                ->default($existing[$student->id] ?? 'present')
                                ->required();
                        }

                        return $components;
                    })
                    ->action(function (AcademicSession $record, array $data): void {
                        foreach ($data as $key => $status) {
                            if (str_starts_with($key, 'student_')) {
                                $studentId = str_replace('student_', '', $key);
                                Attendance::updateOrCreate(
                                    [
                                        'session_id' => $record->id,
                                        'student_id' => $studentId,
                                    ],
                                    [
                                        'status' => $status,
                                        'is_guest' => false,
                                    ]
                                );
                                $student = Student::find($studentId);
                                if ($student) {
                                    app(\App\Services\NotificationService::class)->notifyAttendance(
                                        $student,
                                        $status,
                                        $record->topic,
                                        $record->date instanceof \DateTimeInterface ? $record->date->format('Y-m-d') : (string) ($record->date ?? now()->format('Y-m-d'))
                                    );
                                }
                            }
                        }

                        Notification::make()
                            ->title('تم حفظ كشف الحضور وإرسال الإشعارات بنجاح!')
                            ->success()
                            ->send();
                    }),

                Action::make('scanQr')
                    ->label('مسح QR Code')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->color('primary')
                    ->modalHeading('إدخال/مسح كود الطالب بالـ QR')
                    ->form([
                        TextInput::make('student_code')
                            ->label('كود الطالب / امسح الـ QR')
                            ->placeholder('أدخل أو امسح كود الطالب هنا...')
                            ->autofocus()
                            ->required(),

                        Select::make('status')
                            ->label('حالة الحضور')
                            ->options([
                                'present' => 'حاضر',
                                'late' => 'متأخر',
                            ])
                            ->default('present')
                            ->required(),
                    ])
                    ->action(function (AcademicSession $record, array $data): void {
                        $code = trim($data['student_code']);
                        $student = Student::where('student_code', $code)
                            ->orWhere('id', $code)
                            ->first();

                        if (!$student) {
                            Notification::make()
                                ->title('عفواً، كود الطالب غير موجود بالمدرس!')
                                ->danger()
                                ->send();
                            return;
                        }

                        $isGuest = ($student->group_id != $record->group_id);
                        $status = $isGuest ? 'guest' : $data['status'];

                        Attendance::updateOrCreate(
                            [
                                'session_id' => $record->id,
                                'student_id' => $student->id,
                            ],
                            [
                                'status' => $status,
                                'is_guest' => $isGuest,
                                'original_group_id' => $isGuest ? $student->group_id : null,
                            ]
                        );

                        app(\App\Services\NotificationService::class)->notifyAttendance(
                            $student,
                            $status,
                            $record->topic,
                            $record->date instanceof \DateTimeInterface ? $record->date->format('Y-m-d') : (string) ($record->date ?? now()->format('Y-m-d'))
                        );

                        $msg = "تم تسجيل حضور الطالب: {$student->first_name} {$student->last_name}";
                        if ($isGuest) {
                            $msg .= " (حاضر كضيف من مجموعة أخرى)";
                        }

                        Notification::make()
                            ->title($msg)
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
            'index' => ManageAcademicSessions::route('/'),
        ];
    }
}
