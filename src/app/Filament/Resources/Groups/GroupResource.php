<?php

namespace App\Filament\Resources\Groups;

use App\Filament\Resources\Groups\Pages\ManageGroups;
use App\Models\Group;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?int $navigationSort = 11;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'المجموعات الدراسية';

    protected static ?string $pluralLabel = 'المجموعات الدراسية';

    protected static ?string $modelLabel = 'مجموعة دراسية';

    protected static \UnitEnum|string|null $navigationGroup = 'إدارة السنتر الأوفلاين';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('اسم المجموعة')
                    ->placeholder('مثال: مجموعة أ - السبت والثلاثاء 4 عصراً')
                    ->required()
                    ->maxLength(255),

                Select::make('grade_level_id')
                    ->label('الصف الدراسي (إعدادي / ثانوي)')
                    ->relationship('gradeLevel', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('academic_year_id')
                    ->label('السنة الدراسية')
                    ->relationship('academicYear', 'name')
                    ->searchable()
                    ->preload(),

                TextInput::make('capacity')
                    ->label('سعة المجموعة (أقصى عدد طلاب)')
                    ->numeric()
                    ->default(50)
                    ->required(),

                Toggle::make('is_active')
                    ->label('مجموعة نشطة')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم المجموعة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('gradeLevel.name')
                    ->label('الصف الدراسي')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('academicYear.name')
                    ->label('السنة الدراسية')
                    ->sortable(),

                TextColumn::make('students_count')
                    ->label('عدد الطلاب المسجلين')
                    ->counts('students')
                    ->sortable(),

                TextColumn::make('capacity')
                    ->label('السعة القصوى')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('grade_level_id')
                    ->label('تصفية حسب الصف الدراسي')
                    ->relationship('gradeLevel', 'name'),

                SelectFilter::make('academic_year_id')
                    ->label('تصفية حسب السنة الدراسية')
                    ->relationship('academicYear', 'name'),
            ])
            ->actions([
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
            'index' => ManageGroups::route('/'),
        ];
    }
}
