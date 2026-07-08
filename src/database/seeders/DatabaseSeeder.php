<?php

namespace Database\Seeders;

use App\Domain\Course\Models\Category;
use App\Domain\Course\Models\Course;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'student']);
        Role::firstOrCreate(['name' => 'instructor']);

        $instructor = User::factory()->create([
            'name' => 'أحمد محمد',
            'email' => 'instructor@test.com',
            'password' => bcrypt('password'),
        ]);
        $instructor->assignRole('instructor');

        $categories = [
            ['name' => 'برمجة', 'slug' => 'programming', 'icon' => 'code'],
            ['name' => 'تصميم', 'slug' => 'design', 'icon' => 'palette'],
            ['name' => 'تسويق', 'slug' => 'marketing', 'icon' => 'megaphone'],
            ['name' => 'هندسة', 'slug' => 'engineering', 'icon' => 'wrench'],
            ['name' => 'لغات', 'slug' => 'languages', 'icon' => 'globe'],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }

        $courses = [
            [
                'title' => 'دورة كاملة في تطوير الويب باستخدام React',
                'slug' => 'react-web-development',
                'description' => 'دورة شاملة في تطوير الويب باستخدام React.js. ستتعلم من الأساسيات إلى المفاهيم المتقدمة مثل Hooks وContext API وRedux.',
                'short_description' => 'تعلم React من الصفر إلى الاحتراف',
                'price' => 299,
                'level' => 'intermediate',
                'duration_minutes' => 960,
                'category_id' => 1,
            ],
            [
                'title' => 'تصميم UX/UI من الصفر إلى الاحتراف',
                'slug' => 'ux-ui-design',
                'description' => 'تعلم أساسيات تصميم تجربة المستخدم وواجهات المستخدم باستخدام أحدث الأدوات.',
                'short_description' => 'أتقن فن تصميم الواجهات',
                'price' => 249,
                'level' => 'beginner',
                'duration_minutes' => 720,
                'category_id' => 2,
            ],
            [
                'title' => 'التسويق الرقمي المتكامل',
                'slug' => 'digital-marketing',
                'description' => 'دورة متكاملة في التسويق الرقمي تشمل SEO وSEM وإعلانات التواصل الاجتماعي.',
                'short_description' => 'احترف التسويق الرقمي',
                'price' => 199,
                'level' => 'beginner',
                'duration_minutes' => 840,
                'category_id' => 3,
            ],
            [
                'title' => 'تعلم لغة Python للمبتدئين',
                'slug' => 'python-beginners',
                'description' => 'دورة مجانية في أساسيات لغة Python من الصفر.',
                'short_description' => 'أساسيات Python مجاناً',
                'price' => 0,
                'level' => 'beginner',
                'duration_minutes' => 600,
                'category_id' => 1,
            ],
            [
                'title' => 'أساسيات الشبكات وأمن المعلومات',
                'slug' => 'networking-security',
                'description' => 'تعلم مفاهيم الشبكات وأمن المعلومات من الأساسيات إلى المستوى المتقدم.',
                'short_description' => 'شبكات وأمن للمبتدئين',
                'price' => 349,
                'level' => 'intermediate',
                'duration_minutes' => 900,
                'category_id' => 4,
            ],
            [
                'title' => 'دورة الإنجليزية للأعمال',
                'slug' => 'business-english',
                'description' => 'حسن لغتك الإنجليزية لمكان العمل مع هذه الدورة الشاملة.',
                'short_description' => 'إنجليزية احترافية للأعمال',
                'price' => 149,
                'level' => 'beginner',
                'duration_minutes' => 1200,
                'category_id' => 5,
            ],
            [
                'title' => 'تطوير تطبيقات الموبايل باستخدام Flutter',
                'slug' => 'flutter-mobile-apps',
                'description' => 'بناء تطبيقات موبايل لأنظمة iOS و Android باستخدام Flutter و Dart.',
                'short_description' => 'ابنِ تطبيقات موبايل باحتراف',
                'price' => 399,
                'level' => 'intermediate',
                'duration_minutes' => 840,
                'category_id' => 1,
            ],
            [
                'title' => 'أساسيات الجرافيك ديزاين وأدوبي فوتوشوب',
                'slug' => 'graphic-design-photoshop',
                'description' => 'تعلم أساسيات التصميم الجرافيكي واستخدام أدوبي فوتوشوب.',
                'short_description' => 'مجاناً: أساسيات التصميم',
                'price' => 0,
                'level' => 'beginner',
                'duration_minutes' => 480,
                'category_id' => 2,
            ],
        ];

        foreach ($courses as $courseData) {
            Course::create([
                ...$courseData,
                'language' => 'العربية',
                'is_published' => true,
                'instructor_id' => $instructor->id,
            ]);
        }
    }
}
