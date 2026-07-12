<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use App\Models\Product;
use App\Models\Bundle;
use App\Models\Order;
use App\Services\GrantEntitlementService;
use Filament\Notifications\Notification;

class ManageEnrollments extends ManageRecords
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('grantAccess')
                ->label('إضافة صلاحية (محاضرة/باقة)')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->modalHeading('إضافة صلاحية وصول للطالب')
                ->modalDescription('سيتم إنشاء طلب يدوي ومنح الطالب صلاحية الوصول للمحتوى (محاضرة أو باقة).')
                ->form([
                    Select::make('student_id')
                        ->label('الطالب')
                        ->options(function () {
                            return \App\Models\Student::with('user')
                                ->get()
                                ->mapWithKeys(fn ($s) => [$s->id => $s->student_code . ' - ' . ($s->user?->name ?? 'غير معروف')]);
                        })
                        ->searchable()
                        ->required(),

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
                ->action(function (array $data): void {
                    $order = Order::create([
                        'student_id' => $data['student_id'],
                        'purchasable_type' => $data['purchasable_type'],
                        'purchasable_id' => $data['purchasable_id'],
                        'amount_cents' => 0,
                        'status' => 'completed',
                        'paid_at' => now(),
                    ]);

                    app(GrantEntitlementService::class)->handle($order);

                    Notification::make()
                        ->title('تم منح الصلاحية للطالب بنجاح')
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }
}
