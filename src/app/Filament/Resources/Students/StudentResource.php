<?php

namespace App\Filament\Resources\Students;

use App\Models\Bundle;
use App\Models\Order;
use App\Models\Product;
use App\Services\GrantEntitlementService;
use App\Services\NotificationService;
use App\Models\Student;
use App\Filament\Resources\Students\Pages\ManageStudents;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'الطلاب';

    protected static ?string $pluralLabel = 'الطلاب';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('user_id')
                    ->label('المستخدم')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->reactive(),
                TextInput::make('email')
                    ->label('البريد الإلكتروني للجديد')
                    ->email()
                    ->required(fn (callable $get) => !$get('user_id'))
                    ->visible(fn (callable $get) => !$get('user_id'))
                    ->unique('users', 'email'),
                TextInput::make('student_code')
                    ->label('كود الطالب')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (?Student $record) => $record !== null),
                TextInput::make('first_name')
                    ->label('الاسم الأول')
                    ->required()
                    ->maxLength(255),
                TextInput::make('second_name')
                    ->label('الاسم الثاني')
                    ->required()
                    ->maxLength(255),
                TextInput::make('third_name')
                    ->label('الاسم الثالث')
                    ->required()
                    ->maxLength(255),
                TextInput::make('last_name')
                    ->label('الاسم الأخير')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (?Student $record) => $record !== null)
                    ->afterStateHydrated(function ($component, ?Student $record) {
                        $component->state($record?->user?->email);
                    }),
                TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->required()
                    ->tel()
                    ->maxLength(20),
                TextInput::make('father_phone')
                    ->label('هاتف الأب')
                    ->required()
                    ->tel()
                    ->maxLength(20),
                TextInput::make('mother_phone')
                    ->label('هاتف الأم')
                    ->required()
                    ->tel()
                    ->maxLength(20),
                TextInput::make('guardian_job')
                    ->label('وظيفة ولي الأمر')
                    ->required()
                    ->maxLength(255),
                Select::make('gender')
                    ->label('الجنس')
                    ->options([
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                    ])
                    ->required(),
                DatePicker::make('birth_date')
                    ->label('تاريخ الميلاد')
                    ->required(),
                Select::make('governorate_id')
                    ->label('المحافظة')
                    ->relationship('governorate', 'name')
                    ->searchable(),
                Select::make('city_id')
                    ->label('المدينة')
                    ->relationship('city', 'name')
                    ->searchable(),
                Select::make('school_id')
                    ->label('المدرسة')
                    ->relationship('school', 'name')
                    ->searchable(),
                Select::make('grade_level_id')
                    ->label('الصف الدراسي')
                    ->relationship('gradeLevel', 'name')
                    ->searchable(),
                Select::make('academic_track_id')
                    ->label('الشعبة')
                    ->relationship('academicTrack', 'name')
                    ->searchable(),
                \Filament\Forms\Components\Toggle::make('is_verified')
                    ->label('مُفعل / معتمد (صلاحية الشراء)')
                    ->helperText('تفعيل هذا الخيار يسمح للطالب بشراء وتفعيل الكورسات من المتجر مباشرة.')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_code')
                    ->label('#')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label('الاسم')
                    ->searchable(['first_name', 'second_name', 'third_name', 'last_name'])
                    ->sortable(['first_name'])
                    ->state(fn (Student $record): string => trim(
                        "{$record->first_name} {$record->second_name} {$record->third_name} {$record->last_name}"
                    )),
                TextColumn::make('user.email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('رقم الهاتف'),
                TextColumn::make('user.status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'active' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'قيد المراجعة',
                        'active' => 'نشط',
                        'rejected' => 'مرفوض',
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('Y-m-d')
                    ->sortable(),
                \Filament\Tables\Columns\IconColumn::make('is_verified')
                    ->label('معتمد للشراء')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد المراجعة',
                        'active' => 'نشط',
                        'rejected' => 'مرفوض',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $q, $value) => $q->whereHas(
                                'user',
                                fn (Builder $q) => $q->where('status', $value)
                            )
                        );
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        unset($data['email']);
                        return $data;
                    }),
                Action::make('approve')
                    ->label('اعتماد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('اعتماد الطالب')
                    ->modalDescription('هل أنت متأكد من اعتماد هذا الطالب؟')
                    ->modalSubmitActionLabel('نعم، اعتماد')
                    ->action(function (Student $record): void {
                        $user = $record->user;
                        $user->update(['status' => 'active']);

                        $record->update(['is_verified' => true]);

                        app(NotificationService::class)->send(
                            $user,
                            'تم اعتماد حسابك',
                            'تم اعتماد حسابك في المنصة. يمكنك الآن تسجيل الدخول والبدء في التعلم.',
                        );

                        Notification::make()
                            ->title('تم اعتماد الطالب بنجاح')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Student $record): bool => $record->user->status === 'pending'),
                Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('رفض الطالب')
                    ->modalDescription('هل أنت متأكد من رفض هذا الطالب؟')
                    ->modalSubmitActionLabel('نعم، رفض')
                    ->action(function (Student $record): void {
                        $user = $record->user;
                        $user->update(['status' => 'rejected']);

                        app(NotificationService::class)->send(
                            $user,
                            'لم يتم اعتماد حسابك',
                            'نأسف، لم يتم اعتماد حسابك في المنصة. يرجى التواصل مع الإدارة لمزيد من التفاصيل.',
                        );

                        Notification::make()
                            ->title('تم رفض الطالب')
                            ->danger()
                            ->send();
                    })
                    ->visible(fn (Student $record): bool => $record->user->status === 'pending'),


                Action::make('revokeAccess')
                    ->label('إلغاء الصلاحيات')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->modalHeading('إلغاء صلاحيات الطالب')
                    ->modalDescription('اختر المحاضرات التي ترغب في إلغاء صلاحية الوصول إليها لهذا الطالب.')
                    ->form(fn (Student $record) => [
                        \Filament\Forms\Components\CheckboxList::make('entitlements')
                            ->label('الصلاحيات النشطة')
                            ->options(function () use ($record) {
                                return $record->entitlements()
                                    ->with('lecture.section.course')
                                    ->get()
                                    ->mapWithKeys(function ($e) {
                                        $course = $e->lecture?->section?->course?->title ?? '-';
                                        $section = $e->lecture?->section?->title ?? '-';
                                        $lecture = $e->lecture?->title ?? '-';
                                        $expires = $e->expires_at ? ' (ينتهي: ' . $e->expires_at->format('Y-m-d') . ')' : ' (دائم)';
                                        return [$e->id => "{$course} / {$section} / {$lecture}{$expires}"];
                                    });
                            })
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        \App\Models\Entitlement::whereIn('id', $data['entitlements'])->delete();

                        Notification::make()
                            ->title('تم إلغاء الصلاحيات المحددة بنجاح')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Student $record): bool => $record->entitlements()->exists()),

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
            'index' => ManageStudents::route('/'),
        ];
    }
}
