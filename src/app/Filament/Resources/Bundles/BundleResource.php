<?php

namespace App\Filament\Resources\Bundles;

use App\Filament\Resources\Bundles\Pages\ManageBundles;
use App\Models\Bundle;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BundleResource extends Resource
{
    protected static ?string $model = Bundle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static ?string $navigationLabel = 'الباقات';

    protected static ?string $pluralLabel = 'الباقات';

    protected static ?string $modelLabel = 'باقة';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('اسم الباقة')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('price_cents')
                    ->label('السعر (بالقرش)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->default(0)
                    ->helperText('مثال: 50000 قرش = 500 جنيه'),

                \Filament\Forms\Components\Select::make('products')
                    ->label('المنتجات المشمولة في الباقة')
                    ->relationship('products', 'name')
                    ->multiple()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('اسم الباقة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price_cents')
                    ->label('السعر')
                    ->formatStateUsing(fn ($state): string => number_format((int) $state / 100, 2) . ' ج.م'),

                TextColumn::make('products_count')
                    ->label('عدد المنتجات')
                    ->counts('products'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBundles::route('/'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Bundles\RelationManagers\ProductsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = request()->user();

        return parent::getEloquentQuery()
            ->withCount('products')
            ->when($user && ! $user->hasRole('super_admin'), function (Builder $query) use ($user) {
                if ($user->hasRole('instructor')) {
                    $query->where('instructor_id', $user->id);
                } elseif ($user->hasRole('assistant')) {
                    $query->whereHas('products', fn (Builder $q) => $q->where('instructor_id', $user->id));
                }
            });
    }
}
