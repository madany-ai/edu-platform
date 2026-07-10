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

    public function table(Table $table): Table
    {
        $user = request()->user();

        return $table
            ->query(
                Course::withCount(['enrollments', 'lectures', 'sections'])
                    ->where('instructor_id', $user->id)
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
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2) . ' د.م'),
            ])
            ->defaultSort('enrollments_count', 'desc');
    }
}
