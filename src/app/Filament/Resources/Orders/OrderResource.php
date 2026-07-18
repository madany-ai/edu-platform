<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Models\Bundle;
use App\Models\Order;
use App\Models\Product;
use App\Services\GrantEntitlementService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?int $navigationSort = 8;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $navigationLabel = 'الطلبات';

    protected static ?string $pluralLabel = 'الطلبات';

    protected static ?string $modelLabel = 'طلب';

    public static function canViewAny(): bool
    {
        return ! auth()->user()->hasRole('assistant');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([]);
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

                TextColumn::make('purchasable_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Product::class => 'منتج',
                        Bundle::class => 'باقة',
                        default => $state,
                    })
                    ->badge(),

                TextColumn::make('purchasable_name')
                    ->label('المشتريات')
                    ->formatStateUsing(function (Order $record): string {
                        if ($record->purchasable) {
                            return $record->purchasable->name;
                        }
                        return '-';
                    }),

                TextColumn::make('amount_cents')
                    ->label('المبلغ')
                    ->formatStateUsing(fn ($state): string => number_format((int) $state / 100, 2) . ' ج.م'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn ($state): ?string => $state ? (\App\Enums\OrderStatus::tryFrom($state)?->color() ?? 'gray') : 'gray')
                    ->formatStateUsing(fn ($state): string => $state ? (\App\Enums\OrderStatus::tryFrom($state)?->label() ?? (string) $state) : ''),

                TextColumn::make('payment_method')
                    ->label('طريقة الدفع'),

                TextColumn::make('created_at')
                    ->label('تاريخ الشراء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('confirm')
                    ->label('تأكيد الدفع')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد استلام الدفع')
                    ->modalDescription('هل تأكدت من استلام المبلغ من الطالب؟ سيتم تفعيل المحتوى فوراً.')
                    ->modalSubmitActionLabel('نعم، تأكيد')
                    ->visible(fn (Order $record): bool => ($record->status instanceof \App\Enums\OrderStatus ? $record->status->value : $record->status) === 'pending')
                    ->action(function (Order $record): void {
                        $record->update([
                            'status' => 'completed',
                            'paid_at' => now(),
                            'payment_method' => 'manual',
                        ]);

                        app(GrantEntitlementService::class)->handle($record);

                        Notification::make()
                            ->title('تم تأكيد الدفع')
                            ->body("تم تفعيل طلب بنجاح.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOrders::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = request()->user();

        return parent::getEloquentQuery()
            ->with(['student.user', 'purchasable'])
            ->when($user && ! $user->hasRole('super_admin'), function (Builder $query) use ($user) {
                if ($user->hasRole('instructor')) {
                    $query->where(function (Builder $q) use ($user) {
                        $q->whereHasMorph('purchasable', [Product::class], fn (Builder $pq) => $pq->where('instructor_id', $user->id))
                          ->orWhereHasMorph('purchasable', [Bundle::class], fn (Builder $bq) => $bq->where('instructor_id', $user->id));
                    });
                } elseif ($user->hasRole('assistant')) {
                    $query->where(function (Builder $q) use ($user) {
                        $q->whereHasMorph('purchasable', [Product::class], fn (Builder $pq) => $pq->where('instructor_id', $user->id))
                          ->orWhereHasMorph('purchasable', [Bundle::class], fn (Builder $bq) => $bq->where('instructor_id', $user->id));
                    });
                }
            });
    }
}
