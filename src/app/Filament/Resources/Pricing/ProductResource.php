<?php

namespace App\Filament\Resources\Pricing;

use App\Filament\Resources\Pricing\Pages\ManageProducts;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lecture;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'المنتجات';

    protected static ?string $pluralLabel = 'المنتجات';

    protected static ?string $modelLabel = 'منتج';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('اسم المنتج')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('price')
                    ->label('السعر (بالجنيه المصري)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->default(0),

                TextInput::make('access_duration_days')
                    ->label('مدة الوصول (أيام)')
                    ->numeric()
                    ->minValue(1)
                    ->placeholder('اترك فارغاً للوصول الدائم'),

                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),

                Select::make('sellable_type')
                    ->label('نوع المحتوى')
                    ->options([
                        Course::class => 'دورة كاملة',
                        CourseSection::class => 'شهر / قسم',
                        Lecture::class => 'محاضرة',
                    ])
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('sellable_id', null)),

                Select::make('sellable_id')
                    ->label('اختر المحتوى')
                    ->options(function (callable $get) {
                        $type = $get('sellable_type');
                        if (! $type || ! class_exists($type)) {
                            return [];
                        }

                        return match ($type) {
                            Course::class => Course::pluck('title', 'id'),
                            CourseSection::class => CourseSection::with('course')
                                ->get()
                                ->mapWithKeys(fn ($s) => [$s->id => "{$s->course?->title} / {$s->title}"]),
                            Lecture::class => Lecture::with('section.course')
                                ->get()
                                ->mapWithKeys(fn ($l) => [$l->id => "{$l->section?->course?->title} / {$l->section?->title} / {$l->title}"]),
                            default => [],
                        };
                    })
                    ->searchable()
                    ->required(),
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
                    ->label('اسم المنتج')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sellable_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Course::class => 'دورة',
                        CourseSection::class => 'شهر',
                        Lecture::class => 'محاضرة',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Course::class => 'success',
                        CourseSection::class => 'warning',
                        Lecture::class => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('sellable.sellable_name')
                    ->label('المرتبط بـ')
                    ->formatStateUsing(function (Product $record): string {
                        if ($record->sellable instanceof Course) return $record->sellable->title;
                        if ($record->sellable instanceof CourseSection) return "{$record->sellable->course?->title} / {$record->sellable->title}";
                        if ($record->sellable instanceof Lecture) return "{$record->sellable->section?->course?->title} / {$record->sellable->section?->title} / {$record->sellable->title}";
                        return '-';
                    }),

                TextColumn::make('price')
                    ->label('السعر')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2) . ' ج.م'),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),

                TextColumn::make('access_duration_days')
                    ->label('مدة الوصول')
                    ->formatStateUsing(fn ($state): string => $state ? "{$state} يوم" : 'دائم'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('sellable_type')
                    ->label('نوع المحتوى')
                    ->options([
                        Course::class => 'دورة كاملة',
                        CourseSection::class => 'شهر / قسم',
                        Lecture::class => 'محاضرة',
                    ]),
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

    public static function canViewAny(): bool
    {
        return ! auth()->user()->hasRole('assistant');
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
            'index' => ManageProducts::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = request()->user();

        return parent::getEloquentQuery()
            ->with('sellable')
            ->when($user && ! $user->hasRole('super_admin'), function (Builder $query) use ($user) {
                if ($user->hasRole('instructor')) {
                    $query->where('instructor_id', $user->id);
                } elseif ($user->hasRole('assistant')) {
                    $query->whereHas('sellable', fn (Builder $q) => $q->where('instructor_id', $user->id));
                }
            });
    }
}
