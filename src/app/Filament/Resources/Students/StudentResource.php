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
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->required(),
                TextInput::make('student_code')
                    ->label('كود الطالب'),
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

                Action::make('grantAccess')
                    ->label('إضافة صلاحية')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->modalHeading('إضافة صلاحية وصول للطالب')
                    ->modalDescription('سيتم إنشاء طلب يدوي ومنح الطالب صلاحية الوصول للمحتوى.')
                    ->form([
                        Select::make('purchasable_type')
                            ->label('النوع')
                            ->options([
                                Product::class => 'منتج',
                                Bundle::class => 'باقة',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('purchasable_id', null)),

                        Select::make('purchasable_id')
                            ->label('اختر المنتج/الباقة')
                            ->options(function (callable $get) {
                                $type = $get('purchasable_type');
                                if (! $type || ! class_exists($type)) {
                                    return [];
                                }
                                $user = request()->user();
                                $query = $type::query();
                                if ($user && ! $user->hasRole('super_admin') && $user->hasRole('instructor')) {
                                    $query->where('instructor_id', $user->id);
                                }
                                return $query->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data, Student $record): void {
                        $order = Order::create([
                            'student_id' => $record->id,
                            'purchasable_type' => $data['purchasable_type'],
                            'purchasable_id' => $data['purchasable_id'],
                            'amount_cents' => 0,
                            'status' => 'completed',
                            'paid_at' => now(),
                        ]);

                        app(GrantEntitlementService::class)->handle($order);

                        Notification::make()
                            ->title('تم منح الصلاحية للطالب')
                            ->success()
                            ->send();
                    }),

                Action::make('revokeAccess')
                    ->label('إلغاء الصلاحيات')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->modalHeading('صلاحيات الطالب الحالية')
                    ->modalContent(function (Student $record): string {
                        $entitlements = $record->entitlements()
                            ->with('lecture.section.course')
                            ->latest()
                            ->get();

                        if ($entitlements->isEmpty()) {
                            return '<p class="text-gray-500 text-center py-4">لا توجد صلاحيات وصول لهذا الطالب.</p>';
                        }

                        $html = '<div class="space-y-2 max-h-96 overflow-y-auto">';
                        foreach ($entitlements as $e) {
                            $course = $e->lecture?->section?->course?->title ?? '-';
                            $section = $e->lecture?->section?->title ?? '-';
                            $lecture = $e->lecture?->title ?? '-';
                            $expires = $e->expires_at ? $e->expires_at->format('Y-m-d') : 'دائم';
                            $html .= "<div class=\"p-3 border rounded-lg flex justify-between items-center\">
                                <div>
                                    <strong>{$lecture}</strong>
                                    <div class=\"text-sm text-gray-500\">{$course} / {$section}</div>
                                    <div class=\"text-xs text-gray-400\">ينتهي: {$expires}</div>
                                </div>
                            </div>";
                        }
                        $html .= '</div>';
                        return $html;
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق'),

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
