<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الإعدادات والنظام';
    }

    public static function getModelLabel(): string
    {
        return 'سجل النشاط';
    }

    public static function getPluralModelLabel(): string
    {
        return 'سجلات الأنشطة';
    }

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('log_name')->label('اسم السجل'),
                Forms\Components\TextInput::make('description')->label('الوصف'),
                Forms\Components\TextInput::make('subject_type')->label('نوع العنصر'),
                Forms\Components\TextInput::make('causer_type')->label('الفاعل'),
                Forms\Components\KeyValue::make('properties')->label('الخصائص'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('log_name')->label('النوع')->badge(),
                Tables\Columns\TextColumn::make('description')->label('الوصف')->searchable(),
                Tables\Columns\TextColumn::make('subject_type')->label('الكيان المتاثر')->searchable(),
                Tables\Columns\TextColumn::make('causer.name')->label('بواسطة')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('التاريخ')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canEdit(Model|Activity $record): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return ! auth()->user()?->hasRole('assistant');
    }
}
