<?php

namespace App\Filament\Resources\Entitlements;

use App\Filament\Resources\Entitlements\Pages\ManageEntitlements;
use App\Models\Entitlement;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EntitlementResource extends Resource
{
    protected static ?string $model = Entitlement::class;

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'صلاحيات الوصول';

    protected static ?string $pluralLabel = 'صلاحيات الوصول';

    protected static ?string $modelLabel = 'صلاحية وصول';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([]);
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

                TextColumn::make('lecture.title')
                    ->label('المحاضرة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lecture.section.course.title')
                    ->label('الدورة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('order.amount_cents')
                    ->label('المبلغ')
                    ->formatStateUsing(fn ($state): string => $state > 0 ? number_format($state / 100, 2) . ' ج.م' : 'مجاني')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('تاريخ الانتهاء')
                    ->dateTime('Y-m-d')
                    ->placeholder('دائم')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ المنح')
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
            'index' => ManageEntitlements::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = request()->user();

        return parent::getEloquentQuery()
            ->with(['student.user', 'lecture.section.course', 'order'])
            ->when($user && ! $user->hasRole('super_admin'), function (Builder $query) use ($user) {
                if ($user->hasRole('instructor')) {
                    $query->whereHas('lecture.section.course', fn (Builder $q) => $q->where('instructor_id', $user->id));
                } elseif ($user->hasRole('assistant')) {
                    $query->whereHas('lecture.section.course', fn (Builder $q) => $q->whereHas('assistants', fn (Builder $a) => $a->where('user_id', $user->id)));
                }
            });
    }
}
