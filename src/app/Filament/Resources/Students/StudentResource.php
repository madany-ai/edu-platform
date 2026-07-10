<?php

namespace App\Filament\Resources\Students;

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
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

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
                    ->required(),
                TextInput::make('student_code')
                    ->label('كود الطالب'),
                TextInput::make('first_name')
                    ->label('الاسم الأول')
                    ->required()
                    ->maxLength(255),
                TextInput::make('second_name')
                    ->label('الاسم الثاني')
                    ->maxLength(255),
                TextInput::make('third_name')
                    ->label('الاسم الثالث')
                    ->maxLength(255),
                TextInput::make('last_name')
                    ->label('الاسم الأخير')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('father_phone')
                    ->label('هاتف الأب')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('mother_phone')
                    ->label('هاتف الأم')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('guardian_job')
                    ->label('وظيفة ولي الأمر')
                    ->maxLength(255),
                Select::make('gender')
                    ->label('الجنس')
                    ->options([
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                    ]),
                DatePicker::make('birth_date')
                    ->label('تاريخ الميلاد'),
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
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
                EditAction::make(),
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
            'index' => ManageStudents::route('/'),
        ];
    }
}
