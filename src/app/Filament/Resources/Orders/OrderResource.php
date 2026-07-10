<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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

                TextColumn::make('course.title')
                    ->label('الدورة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2) . ' د.م'),

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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = request()->user();

        return parent::getEloquentQuery()
            ->with(['student.user', 'course'])
            ->when(
                $user && $user->hasRole('instructor') && ! $user->hasRole('super_admin'),
                fn ($query) => $query->whereHas('course', fn ($q) => $q->where('instructor_id', $user->id))
            );
    }
}
