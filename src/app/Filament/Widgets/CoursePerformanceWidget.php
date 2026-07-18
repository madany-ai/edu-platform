<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class CoursePerformanceWidget extends TableWidget
{
    protected static ?string $heading = 'أداء الدورات';

    protected static ?int $sort = 3;

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
                Course::withCount(['enrollments', 'lectures', 'sections'])
                    ->whereIn('id', $courseIds)
                    ->where('status', 'published')
                    ->orderByDesc('enrollments_count')
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('الدورة')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof \App\Enums\CourseStatus ? $state->value : $state) {
                        'draft' => 'gray',
                        'published' => 'success',
                        'archived' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => match ($state instanceof \App\Enums\CourseStatus ? $state->value : $state) {
                        'draft' => 'مسودة',
                        'published' => 'منشور',
                        'archived' => 'مؤرشف',
                        default => (string) $state,
                    }),

                Tables\Columns\TextColumn::make('enrollments_count')
                    ->label('المسجلين')
                    ->counts('enrollments')
                    ->sortable(),

                Tables\Columns\TextColumn::make('lectures_count')
                    ->label('المحاضرات')
                    ->counts('lectures')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2) . ' ج.م'),
            ])
            ->defaultSort('enrollments_count', 'desc');
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
