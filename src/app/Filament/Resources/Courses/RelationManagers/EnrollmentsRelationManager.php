<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Models\Enrollment;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

use Filament\Schemas\Schema;

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
                    ->relationship('student', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn (Student $record) => "{$record->first_name} {$record->second_name} {$record->third_name} ({$record->student_code})")
                    ->searchable()
                    ->required(),
                Select::make('status')
                    ->label('الحالة')
                    ->options(\App\Enums\EnrollmentStatus::class)
                    ->default(\App\Enums\EnrollmentStatus::Active)
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
                    ->color(fn ($state): string => $state instanceof \App\Enums\EnrollmentStatus ? $state->color() : 'gray')
                    ->formatStateUsing(fn ($state): string => $state instanceof \App\Enums\EnrollmentStatus ? $state->label() : (string) $state),
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
                            ->getSearchResultsUsing(function (string $search) {
                                return Student::query()
                                    ->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('student_code', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%")
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
                                'status' => \App\Enums\EnrollmentStatus::Active,
                                'source' => \App\Enums\EnrollmentSource::Manual,
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
                \Filament\Actions\CreateAction::make()->label('تسجيل طالب جديد'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
