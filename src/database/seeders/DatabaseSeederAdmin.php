<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;

class DatabaseSeederAdmin extends Seeder
{
    public function run(): void
    {
        // 1. إنشاء الأدوار إن لم تكن موجودة
        Role::firstOrCreate(['name' => 'instructor']);
        Role::firstOrCreate(['name' => 'assistant']);
        Role::firstOrCreate(['name' => 'student']); 

        // 2. إنشاء حساب الأدمن (Instructor)
        $admin = User::firstOrCreate(
            ['email' => 'admin@lms.local'],
            [
                'name' => 'المدير العام (الأدمن)',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );
        $admin->assignRole('instructor');

        // 3. إنشاء حساب المساعد (Assistant)
        $assistant = User::firstOrCreate(
            ['email' => 'assistant@lms.local'],
            [
                'name' => 'مساعد النظام',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );
        $assistant->assignRole('assistant');

        // 4. إضافة محافظات مصر
        $govNames = ['القاهرة', 'الجيزة', 'الإسكندرية', 'القليوبية', 'الشرقية', 'المنوفية', 'الغربية', 'الدقهلية', 'البحيرة', 'كفر الشيخ', 'دمياط', 'بورسعيد', 'الإسماعيلية', 'السويس', 'شمال سيناء', 'جنوب سيناء', 'البحر الأحمر', 'الوادي الجديد', 'مطروح', 'الفيوم', 'بني سويف', 'المنيا', 'أسيوط', 'سوهاج', 'قنا', 'الأقصر', 'أسوان'];
        foreach ($govNames as $govName) {
            \App\Models\Governorate::firstOrCreate(['name' => $govName]);
        }

        // 5. إضافة الصفوف الدراسية
        $gradeLevels = [
            ['name' => 'الصف الأول الإعدادي', 'sort_order' => 1],
            ['name' => 'الصف الثاني الإعدادي', 'sort_order' => 2],
            ['name' => 'الصف الثالث الإعدادي', 'sort_order' => 3],
            ['name' => 'الصف الأول الثانوي', 'sort_order' => 4],
            ['name' => 'الصف الثاني الثانوي', 'sort_order' => 5],
            ['name' => 'الصف الثالث الثانوي', 'sort_order' => 6],
        ];
        foreach ($gradeLevels as $grade) {
            \App\Models\GradeLevel::firstOrCreate(
                ['name' => $grade['name']],
                ['sort_order' => $grade['sort_order']]
            );
        }

        // 6. إضافة الشعب الدراسية
        $tracks = ['عام', 'علمي علوم', 'علمي رياضة', 'أدبي'];
        foreach ($tracks as $track) {
            \App\Models\AcademicTrack::firstOrCreate(['name' => $track]);
        }
    }
}
