<?php

namespace App\Filament\Resources\Assistants;

use App\Filament\Resources\Assistants\Pages\CreateAssistant;
use App\Filament\Resources\Assistants\Pages\EditAssistant;
use App\Filament\Resources\Assistants\Pages\ListAssistants;
use App\Models\Course;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class AssistantResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $navigationLabel = 'المساعدون';

    protected static ?string $pluralLabel = 'المساعدون';

    protected static ?string $modelLabel = 'مساعد';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('الاسم')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->required()
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->extraInputAttributes(['autocomplete' => 'new-email']),

                TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->required()
                    ->tel()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),

                TextInput::make('password')
                    ->label('كلمة المرور')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->extraInputAttributes(['autocomplete' => 'new-password']),

                Select::make('assigned_courses')
                    ->label('الدورات المساعد بها')
                    ->multiple()
                    ->options(fn (): array => Course::query()
                        ->when(
                            auth()->user()?->hasRole('instructor') && ! auth()->user()->hasRole('super_admin'),
                            fn (Builder $q) => $q->where('instructor_id', auth()->id())
                        )
                        ->pluck('title', 'id')
                        ->toArray())
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('assistant_code')
                    ->label('#')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable(),



                TextColumn::make('assisted_courses_count')
                    ->label('عدد الدورات')
                    ->counts('assistedCourses'),

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
            'index' => ListAssistants::route('/'),
            'create' => CreateAssistant::route('/create'),
            'edit' => EditAssistant::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = request()->user();

        return parent::getEloquentQuery()
            ->role('assistant')
            ->withCount('assistedCourses')
            ->when($user && ! $user->hasRole('super_admin'), function (Builder $query) use ($user) {
                if ($user->hasRole('instructor')) {
                    $courseIds = Course::where('instructor_id', $user->id)->pluck('id');
                    $query->where(function (Builder $q) use ($courseIds) {
                        $q->whereHas('assistedCourses', fn (Builder $sub) => $sub->whereIn('course_assistants.course_id', $courseIds))
                            ->orDoesntHave('assistedCourses');
                    });
                }
            });
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
}
