<?php

namespace App\Filament\Resources\Enrollments;

use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use App\Filament\Resources\Enrollments\Pages\ManageEnrollments;
use App\Models\Course;
use App\Models\Enrollment;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;

    protected static ?int $navigationSort = 9;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'التسجيلات';

    protected static ?string $pluralLabel = 'التسجيلات';

    protected static ?string $modelLabel = 'التسجيل';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('student_id')
                    ->label('الطالب')
                    ->options(function () {
                        return \App\Models\Student::with('user')
                            ->get()
                            ->mapWithKeys(fn ($s) => [$s->id => $s->user?->name ?? 'غير معروف']);
                    })
                    ->searchable()
                    ->required(),

                Select::make('course_id')
                    ->label('الدورة')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->required(),

                Select::make('status')
                    ->label('الحالة')
                    ->options(EnrollmentStatus::class)
                    ->default(EnrollmentStatus::Active)
                    ->required(),

                Select::make('source')
                    ->label('المصدر')
                    ->options(EnrollmentSource::class)
                    ->default(EnrollmentSource::Manual)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.student_code')
                    ->label('كود الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('student.user.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('course.title')
                    ->label('الدورة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'suspended' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشط',
                        'expired' => 'منتهي',
                        'suspended' => 'معلق',
                        default => $state,
                    }),

                TextColumn::make('source')
                    ->label('المصدر')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'manual' => 'يدوي',
                        'purchase' => 'شراء',
                        default => $state,
                    }),

                TextColumn::make('started_at')
                    ->label('تاريخ البدء')
                    ->dateTime('Y-m-d')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
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
        return ! auth()->user()->hasRole('assistant');
    }

    public static function canEdit(Model $record): bool
    {
        return ! auth()->user()->hasRole('assistant');
    }

    public static function canDelete(Model $record): bool
    {
        return ! auth()->user()->hasRole('assistant');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEnrollments::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = request()->user();

        return parent::getEloquentQuery()
            ->with(['student.user', 'course'])
            ->when($user && ! $user->hasRole('super_admin'), function (Builder $query) use ($user) {
                if ($user->hasRole('instructor')) {
                    $query->whereHas('course', fn (Builder $q) => $q->where('instructor_id', $user->id));
                } elseif ($user->hasRole('assistant')) {
                    $query->whereHas('course', fn (Builder $q) => $q->whereHas('assistants', fn (Builder $a) => $a->where('user_id', $user->id)));
                }
            });
    }
}
