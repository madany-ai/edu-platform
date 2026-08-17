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
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use App\Filament\Exports\StudentExporter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                    ->unique('users', 'email')
                    ->extraInputAttributes(['autocomplete' => 'new-email']),
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
                    ->maxLength(20)
                    ->unique(
                        table: 'users',
                        column: 'phone',
                        ignorable: fn (?Student $record) => $record?->user
                    ),
                TextInput::make('father_phone')
                    ->label('رقم هاتف ولي الأمر')
                    ->required()
                    ->tel()
                    ->maxLength(20),
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
                    ->options(\App\Models\Governorate::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                TextInput::make('school_name')
                    ->label('المدرسة')
                    ->maxLength(255),
                Select::make('academic_year')
                    ->label('الصف الدراسي')
                    ->options([
                        'prep_1' => 'الصف الأول الإعدادي',
                        'prep_2' => 'الصف الثاني الإعدادي',
                        'prep_3' => 'الصف الثالث الإعدادي',
                        'sec_1' => 'الصف الأول الثانوي',
                        'sec_2' => 'الصف الثاني الثانوي',
                        'sec_3' => 'الصف الثالث الثانوي',
                    ])
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (!$state) {
                            $set('academic_track_id', null);
                            return;
                        }
                        if (!in_array($state, ['sec_2', 'sec_3'])) {
                            $set('academic_track_id', null);
                        }
                    }),
                Select::make('academic_track')
                    ->label('الشعبة')
                    ->options([
                        'math' => 'علمي رياضة',
                        'science' => 'علمي علوم',
                        'literary' => 'أدبي',
                        'general' => 'عام',
                    ])
                    ->searchable()
                    ->preload()
                    ->visible(function (callable $get) {
                        $academicYear = $get('academic_year');
                        if (!$academicYear) {
                            return false;
                        }
                        return in_array($academicYear, ['sec_2', 'sec_3']);
                    }),
                Select::make('group_id')
                    ->label('مجموعة السنتر')
                    ->relationship('group', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('password')
                    ->label('كلمة المرور')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->dehydrated(fn ($state) => filled($state))
                    ->helperText(fn (?Student $record) => $record !== null ? 'اتركه فارغاً إذا كنت لا تريد تغيير كلمة المرور.' : 'إذا تركته فارغاً، ستكون كلمة المرور هي كود الطالب.')
                    ->extraInputAttributes(['autocomplete' => 'new-password']),

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
                    ->sortable(['first_name'])
                    ->state(fn (Student $record): string => trim(
                        "{$record->first_name} {$record->second_name} {$record->third_name} {$record->last_name}"
                    )),
                TextColumn::make('user.email')
                    ->label('البريد الإلكتروني'),
                TextColumn::make('phone')
                    ->label('رقم الهاتف'),
                TextColumn::make('user.status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof \App\Enums\UserStatus ? $state->value : $state) {
                        'pending' => 'warning',
                        'active' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => match ($state instanceof \App\Enums\UserStatus ? $state->value : $state) {
                        'pending' => 'قيد المراجعة',
                        'active' => 'نشط',
                        'rejected' => 'مرفوض',
                        default => (string) $state,
                    }),
                TextColumn::make('group.name')
                    ->label('مجموعة السنتر')
                    ->sortable()
                    ->default('بدون مجموعة'),
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
                SelectFilter::make('group_id')
                    ->label('مجموعة السنتر')
                    ->relationship('group', 'name'),
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
                    })
                    ->after(function (Student $record, array $data): void {
                        if (!empty($data['password'])) {
                            $record->user->update([
                                'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
                            ]);
                        }
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
                    ->visible(fn (Student $record): bool => $record->user->status?->value === 'pending'),
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
                    ->visible(fn (Student $record): bool => $record->user->status?->value === 'pending'),

                Action::make('grantAccess')
                    ->label('منح صلاحية')
                    ->icon('heroicon-o-key')
                    ->color('success')
                    ->form([
                        Select::make('course_id')
                            ->label('الدورة التدريبية')
                            ->options(function () {
                                $user = auth()->user();
                                $query = \App\Models\Course::query();
                                if ($user->hasRole('instructor')) {
                                    $query->where('instructor_id', $user->id);
                                } elseif ($user->hasRole('assistant')) {
                                    $query->whereHas('assistants', fn ($q) => $q->where('user_id', $user->id));
                                }
                                return $query->pluck('title', 'id')->toArray();
                            })
                            ->live()
                            ->required(),

                        Select::make('lecture_id')
                            ->label('المحاضرة')
                            ->options(function (callable $get) {
                                $courseId = $get('course_id');
                                if (!$courseId) {
                                    return [];
                                }
                                return \App\Models\Lecture::whereHas('section', fn ($q) => $q->where('course_id', $courseId))
                                    ->pluck('title', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable(),

                        DatePicker::make('expires_at')
                            ->label('تاريخ الانتهاء (اختياري)')
                            ->helperText('اتركه فارغاً للحصول على صلاحية دائمة.'),
                    ])
                    ->action(function (Student $record, array $data): void {
                        \App\Models\Entitlement::updateOrCreate(
                            [
                                'student_id' => $record->id,
                                'lecture_id' => $data['lecture_id'],
                            ],
                            [
                                'expires_at' => $data['expires_at'] ?? null,
                            ]
                        );

                        Notification::make()
                            ->title('تم منح الصلاحية المحددة بنجاح')
                            ->success()
                            ->send();
                    }),

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

                Action::make('transferGroup')
                    ->label('نقل المجموعة')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->color('warning')
                    ->modalHeading('نقل الطالب إلى مجموعة دراسية أخرى')
                    ->form([
                        Select::make('to_group_id')
                            ->label('المجموعة الجديدة')
                            ->relationship('group', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        TextInput::make('reason')
                            ->label('سبب النقل')
                            ->placeholder('مثال: تغيير الموعد بناءً على طلب الطالب')
                            ->maxLength(255),
                    ])
                    ->action(function (Student $record, array $data): void {
                        $fromGroupId = $record->group_id;
                        $toGroupId = $data['to_group_id'];

                        if ($fromGroupId === $toGroupId) {
                            Notification::make()
                                ->title('الطالب موجود بالفعل في هذه المجموعة!')
                                ->warning()
                                ->send();
                            return;
                        }

                        \App\Models\StudentTransfer::create([
                            'student_id' => $record->id,
                            'from_group_id' => $fromGroupId,
                            'to_group_id' => $toGroupId,
                            'reason' => $data['reason'] ?? null,
                            'transferred_at' => now(),
                        ]);

                        $record->update(['group_id' => $toGroupId]);

                        Notification::make()
                            ->title('تم نقل الطالب إلى المجموعة الجديدة بنجاح مع حفظ سجل النقل')
                            ->success()
                            ->send();
                    }),

                Action::make('addCommunicationLog')
                    ->label('تواصل ولي الأمر')
                    ->icon(Heroicon::OutlinedPhone)
                    ->color('info')
                    ->modalHeading('تسجيل تواصل مع ولي الأمر')
                    ->form([
                        DatePicker::make('date')
                            ->label('تاريخ التواصل')
                            ->default(now())
                            ->required(),

                        Select::make('contact_method')
                            ->label('وسيلة التواصل')
                            ->options([
                                'اتصال هاتف' => 'اتصال هاتف',
                                'واتساب' => 'واتساب',
                                'مقابلة في السنتر' => 'مقابلة في السنتر',
                            ])
                            ->default('اتصال هاتف')
                            ->required(),

                        TextInput::make('reason')
                            ->label('سبب التواصل')
                            ->placeholder('مثال: متابعة الغياب / تراجع المستوى')
                            ->required(),

                        Textarea::make('notes')
                            ->label('تفاصيل الملاحظات'),
                    ])
                    ->action(function (Student $record, array $data): void {
                        \App\Models\CommunicationLog::create([
                            'student_id' => $record->id,
                            'date' => $data['date'],
                            'contact_method' => $data['contact_method'],
                            'reason' => $data['reason'],
                            'notes' => $data['notes'] ?? null,
                            'created_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('تم تسجيل تواصل ولي الأمر بنجاح!')
                            ->success()
                            ->send();
                    }),

                Action::make('printQrCode')
                    ->label('طباعة كارنيه / QR')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->color('primary')
                    ->modalHeading('كارنيه الطالب - QR Code')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(fn (Student $record) => view('filament.modals.student-qr-card', [
                        'student' => $record,
                    ])),

            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(StudentExporter::class)
                    ->label('تصدير بيانات الطلاب (Excel)'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('printBulkQrCodes')
                        ->label('طباعة الكارنيهات (QR)')
                        ->icon('heroicon-o-qr-code')
                        ->color('primary')
                        ->modalHeading('كارنيهات الطلاب المحددين - QR Codes')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('إغلاق')
                        ->modalContent(fn (\Illuminate\Database\Eloquent\Collection $records) => view('filament.modals.bulk-student-qr-cards', [
                            'students' => $records,
                        ])),
                    ExportBulkAction::make()
                        ->exporter(StudentExporter::class)
                        ->label('تصدير الطلاب المحددين (Excel)'),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStudents::route('/'),
        ];
    }
}
