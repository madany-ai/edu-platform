<?php

namespace Database\Seeders;

use App\Domain\Course\Models\Category;
use App\Domain\Course\Models\Course;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $studentRole = Role::firstOrCreate(['name' => 'student']);
        $instructorRole = Role::firstOrCreate(['name' => 'instructor']);
        $assistantRole = Role::firstOrCreate(['name' => 'assistant']);

        $permissions = [
            'approve-students',
            'answer-questions',
            'grade-assignments',
            'manage-courses',
            'manage-assistants',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $instructorRole->syncPermissions($permissions);
        $assistantRole->syncPermissions(['answer-questions', 'grade-assignments']);

        $instructor = User::firstOrCreate(
            ['email' => 'instructor@test.com'],
            [
                'name' => 'أحمد محمد',
                'password' => bcrypt('password'),
            ]
        );
        $instructor->assignRole('instructor');

        $categoriesData = [
            ['name' => 'برمجة', 'slug' => 'programming', 'icon' => 'code'],
            ['name' => 'تصميم', 'slug' => 'design', 'icon' => 'palette'],
            ['name' => 'تسويق', 'slug' => 'marketing', 'icon' => 'megaphone'],
            ['name' => 'هندسة', 'slug' => 'engineering', 'icon' => 'wrench'],
            ['name' => 'لغات', 'slug' => 'languages', 'icon' => 'globe'],
        ];

        $categories = [];
        foreach ($categoriesData as $catData) {
            $categories[] = Category::firstOrCreate(
                ['slug' => $catData['slug']],
                $catData
            );
        }

        $coursesData = [
            [
                'title' => 'دورة كاملة في تطوير الويب باستخدام React',
                'description' => 'دورة شاملة في تطوير الويب باستخدام React.js. ستتعلم من الأساسيات إلى المفاهيم المتقدمة مثل Hooks وContext API وRedux.',
                'price' => 299,
                'status' => 'published',
                'category_id' => 1,
            ],
            [
                'title' => 'تصميم UX/UI من الصفر إلى الاحتراف',
                'description' => 'تعلم أساسيات تصميم تجربة المستخدم وواجهات المستخدم باستخدام أحدث الأدوات.',
                'price' => 249,
                'status' => 'published',
                'category_id' => 2,
            ],
            [
                'title' => 'التسويق الرقمي المتكامل',
                'description' => 'دورة متكاملة في التسويق الرقمي تشمل SEO وSEM وإعلانات التواصل الاجتماعي.',
                'price' => 199,
                'status' => 'published',
                'category_id' => 3,
            ],
            [
                'title' => 'تعلم لغة Python للمبتدئين',
                'description' => 'دورة مجانية في أساسيات لغة Python من الصفر.',
                'price' => 0,
                'status' => 'published',
                'category_id' => 1,
            ],
            [
                'title' => 'أساسيات الشبكات وأمن المعلومات',
                'description' => 'تعلم مفاهيم الشبكات وأمن المعلومات من الأساسيات إلى المستوى المتقدم.',
                'price' => 349,
                'status' => 'published',
                'category_id' => 4,
            ],
            [
                'title' => 'دورة الإنجليزية للأعمال',
                'description' => 'حسن لغتك الإنجليزية لمكان العمل مع هذه الدورة الشاملة.',
                'price' => 149,
                'status' => 'published',
                'category_id' => 5,
            ],
            [
                'title' => 'تطوير تطبيقات الموبايل باستخدام Flutter',
                'description' => 'بناء تطبيقات موبايل لأنظمة iOS و Android باستخدام Flutter و Dart.',
                'price' => 399,
                'status' => 'published',
                'category_id' => 1,
            ],
            [
                'title' => 'أساسيات الجرافيك ديزاين وأدوبي فوتوشوب',
                'description' => 'تعلم أساسيات التصميم الجرافيكي واستخدام أدوبي فوتوشوب.',
                'price' => 0,
                'status' => 'published',
                'category_id' => 2,
            ],
        ];

        foreach ($coursesData as $courseData) {
            Course::firstOrCreate(
                ['title' => $courseData['title'], 'instructor_id' => $instructor->id],
                $courseData
            );
        }
    }
}
