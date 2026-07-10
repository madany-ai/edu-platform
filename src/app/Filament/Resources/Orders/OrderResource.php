<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Models\Bundle;
use App\Models\Order;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $navigationLabel = 'الطلبات';

    protected static ?string $pluralLabel = 'الطلبات';

    protected static ?string $modelLabel = 'طلب';

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
                TextColumn::make('id')
                    ->label('#')
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
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'مكتمل',
                        'pending' => 'قيد الانتظار',
                        'failed' => 'فشل',
                        'refunded' => 'مسترجع',
                        default => $state,
                    }),

                TextColumn::make('payment_method')
                    ->label('طريقة الدفع'),

                TextColumn::make('created_at')
                    ->label('تاريخ الشراء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
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
