<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\Enrollment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentEnrollmentsWidget extends TableWidget
{
    protected static ?string $heading = 'آخر التسجيلات';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return ! auth()->user()->hasRole('assistant');
    }

    public function table(Table $table): Table
    {
        $user = request()->user();
        $courseIds = $this->getUserCourseIds($user);

        return $table
            ->query(
                Enrollment::with(['student.user', 'course'])
                    ->whereIn('course_id', $courseIds)
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.user.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('course.title')
                    ->label('الدورة')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('source')
                    ->label('المصدر')
                    ->formatStateUsing(fn ($state): string => match ($state instanceof \App\Enums\EnrollmentSource ? $state->value : $state) {
                        'manual' => 'يدوي',
                        'purchase' => 'شراء',
                        default => (string) $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    private function getUserCourseIds($user): array
    {
        if ($user->hasRole('super_admin')) {
            return Course::pluck('id')->toArray();
        }

        if ($user->hasRole('instructor')) {
            return Course::where('instructor_id', $user->id)->pluck('id')->toArray();
        }

        if ($user->hasRole('assistant')) {
            return Course::whereHas('assistants', fn ($q) => $q->where('user_id', $user->id))
                ->pluck('id')->toArray();
        }

        return [];
    }
}
