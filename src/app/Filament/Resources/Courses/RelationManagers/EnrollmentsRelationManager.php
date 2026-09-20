<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'الطلاب المسجلين';

    protected static ?string $modelLabel = 'تسجيل';

    protected static ?string $pluralModelLabel = 'التسجيلات';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('student_id')
                    ->label('الطالب')
                    ->relationship('student', 'first_name', fn ($query) => $query->where('academic_year', $this->getOwnerRecord()->academic_year))
                    ->getOptionLabelFromRecordUsing(fn (Student $record) => "{$record->first_name} {$record->second_name} {$record->third_name} ({$record->student_code})")
                    ->searchable()
                    ->required(),
                Select::make('status')
                    ->label('الحالة')
                    ->options(EnrollmentStatus::class)
                    ->default(EnrollmentStatus::Active)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('student.student_code')
                    ->label('كود الطالب')
                    ->searchable(),
                Tables\Columns\TextColumn::make('student.first_name')
                    ->label('اسم الطالب')
                    ->state(fn (Enrollment $record) => $record->student ? "{$record->student->first_name} {$record->student->second_name} {$record->student->third_name}" : 'غير متوفر')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn ($state): string => $state instanceof EnrollmentStatus ? $state->color() : 'gray')
                    ->formatStateUsing(fn ($state): string => $state instanceof EnrollmentStatus ? $state->label() : (string) $state),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('bulk_add_students')
                    ->label('إضافة طلاب (مجمع)')
                    ->icon('heroicon-o-users')
                    ->color('primary')
                    ->form([
                        Select::make('student_ids')
                            ->label('اختر الطلاب')
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search, RelationManager $livewire) {
                                $courseAcademicYear = $livewire->getOwnerRecord()->academic_year;

                                return Student::query()
                                    ->where('academic_year', $courseAcademicYear)
                                    ->where(function ($query) use ($search) {
                                        $query->where('first_name', 'like', "%{$search}%")
                                            ->orWhere('student_code', 'like', "%{$search}%")
                                            ->orWhere('phone', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($student) => [$student->id => "{$student->first_name} {$student->second_name} {$student->third_name} ({$student->student_code})"])
                                    ->toArray();
                            })
                            ->getOptionLabelsUsing(function (array $values) {
                                return Student::whereIn('id', $values)
                                    ->get()
                                    ->mapWithKeys(fn ($student) => [$student->id => "{$student->first_name} {$student->second_name} {$student->third_name} ({$student->student_code})"])
                                    ->toArray();
                            })
                            ->required(),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $courseId = $livewire->ownerRecord->id;
                        $count = 0;

                        foreach ($data['student_ids'] as $studentId) {
                            $enrollment = Enrollment::firstOrCreate([
                                'student_id' => $studentId,
                                'course_id' => $courseId,
                            ], [
                                'status' => EnrollmentStatus::Active,
                                'source' => EnrollmentSource::Manual,
                                'started_at' => now(),
                            ]);

                            if ($enrollment->wasRecentlyCreated) {
                                $count++;
                            }
                        }

                        Notification::make()
                            ->title("تم تسجيل {$count} طالب بنجاح في هذه الدورة.")
                            ->success()
                            ->send();
                    }),
                CreateAction::make()->label('تسجيل طالب جديد'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
