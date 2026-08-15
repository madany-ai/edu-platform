<?php

namespace App\Filament\Exports;

use App\Models\Student;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class StudentExporter extends Exporter
{
    protected static ?string $model = Student::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('student_code')->label('كود الطالب'),
            ExportColumn::make('first_name')->label('الاسم الأول'),
            ExportColumn::make('second_name')->label('الاسم الثاني'),
            ExportColumn::make('third_name')->label('الاسم الثالث'),
            ExportColumn::make('last_name')->label('الاسم الأخير'),
            ExportColumn::make('user.email')->label('البريد الإلكتروني'),
            ExportColumn::make('phone')->label('رقم الهاتف'),
            ExportColumn::make('father_phone')->label('رقم هاتف ولي الأمر'),
            ExportColumn::make('gender')->label('الجنس'),
            ExportColumn::make('birth_date')->label('تاريخ الميلاد'),
            ExportColumn::make('school_name')->label('المدرسة'),
            ExportColumn::make('academic_year')->label('الصف الدراسي'),
            ExportColumn::make('group.name')->label('مجموعة السنتر'),
            ExportColumn::make('is_verified')->label('معتمد للشراء'),
            ExportColumn::make('created_at')->label('تاريخ التسجيل'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'تم الانتهاء من تصدير بيانات الطلاب بنجاح وعددهم ' . number_format($export->successful_rows) . ' طالب.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' مع فشل تصدير ' . number_format($failedRowsCount) . ' صف.';
        }

        return $body;
    }
}
