<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lecture;
use App\Models\Enrollment;
use App\Models\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\Product;
use App\Models\Bundle;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'instructor']);
        Role::firstOrCreate(['name' => 'student']);
        Role::firstOrCreate(['name' => 'assistant']);

        $instructor = User::create([
            'name' => 'أحمد محمد',
            'email' => 'instructor@lms.local',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $instructor->assignRole('instructor');

        $studentNames = [
            ['first' => 'محمد', 'second' => 'علي', 'third' => 'حسين', 'last' => 'عبدالله', 'gender' => 'male'],
            ['first' => 'فاطمة', 'second' => 'أحمد', 'third' => 'محمد', 'last' => 'الزهراء', 'gender' => 'female'],
            ['first' => 'عمر', 'second' => 'خالد', 'third' => 'ياسر', 'last' => 'العتيبي', 'gender' => 'male'],
            ['first' => 'نورة', 'second' => 'سعود', 'third' => 'خالد', 'last' => 'الشمري', 'gender' => 'female'],
            ['first' => 'يوسف', 'second' => 'إبراهيم', 'third' => 'محمد', 'last' => 'القحطاني', 'gender' => 'male'],
            ['first' => 'ريم', 'second' => 'عبدالرحمن', 'third' => 'سليمان', 'last' => 'الدوسري', 'gender' => 'female'],
            ['first' => 'خالد', 'second' => 'سعد', 'third' => 'فهد', 'last' => 'المطيري', 'gender' => 'male'],
            ['first' => 'سارة', 'second' => 'ياسر', 'third' => 'عبدالله', 'last' => 'الحربي', 'gender' => 'female'],
            ['first' => 'عبدالله', 'second' => 'منصور', 'third' => 'علي', 'last' => 'النعيمي', 'gender' => 'male'],
            ['first' => 'هند', 'second' => 'تركي', 'third' => 'سلطان', 'last' => 'الزهراني', 'gender' => 'female'],
        ];

        $phones = [
            '0551234567', '0562345678', '0573456789', '0584567890', '0595678901',
            '0506789012', '0517890123', '0528901234', '0539012345', '0540123456',
        ];

        $governorates = ['الرياض', 'جدة', 'مكة', 'المدينة', 'الدمام'];
        $schools = ['مدرسة النور', 'مدرسة التميز', 'مدرسة الأمل', 'مدرسة المعرفة', 'مدرسة الإبداع'];
        $grades = ['الصف الأول الثانوي', 'الصف الثاني الثانوي', 'الصف الثالث الثانوي'];

        $students = [];
        foreach ($studentNames as $i => $name) {
            $user = User::create([
                'name' => "{$name['first']} {$name['last']}",
                'email' => "student" . ($i + 1) . "@lms.local",
                'password' => Hash::make('password'),
                'status' => $i < 8 ? 'active' : 'pending',
            ]);
            $user->assignRole('student');

            $students[] = Student::create([
                'user_id' => $user->id,
                'first_name' => $name['first'],
                'second_name' => $name['second'],
                'third_name' => $name['third'],
                'last_name' => $name['last'],
                'phone' => $phones[$i],
                'father_phone' => '05' . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'mother_phone' => '05' . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'guardian_job' => ['مهندس', 'طبيب', 'معلم', 'موظف', 'حرفي', 'متاجر', 'محاسب', 'مبرمج', 'مستشار', 'فن'][array_rand(['工程师', 'doctor', 'teacher', 'employee', 'trader', 'merchant', 'accountant', 'developer', 'advisor', 'artist'])],
                'gender' => $name['gender'],
                'birth_date' => '200' . mt_rand(2, 8) . '-' . str_pad(mt_rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(mt_rand(1, 28), 2, '0', STR_PAD_LEFT),
                'is_verified' => $i < 8,
            ]);
        }

        $coursesData = [
            [
                'title' => 'دورة تعلم البرمجة من الصفر',
                'description' => 'دورة شاملة لتعلم أساسيات البرمجة. نبدأ من الصفر ونتعلم المتغيرات والدوال والكائنات. مناسب للمبتدئين تماماً.',
                'price' => 0,
                'status' => 'published',
                'sections' => [
                    ['title' => 'مقدمة في البرمجة', 'lectures' => [
                        ['title' => 'ما هي البرمجة؟', 'desc' => 'تعريف البرمجة وأهميتها في عصرنا', 'dur' => 15],
                        ['title' => 'أنواع لغات البرمجة', 'desc' => 'مقارنة بين لغات البرمجة المختلفة', 'dur' => 20],
                        ['title' => 'كيف تختار لغتك الأولى', 'desc' => 'نصائح لاختيار لغة البرمجة المناسبة', 'dur' => 12],
                    ]],
                    ['title' => 'أساسيات PHP', 'lectures' => [
                        ['title' => 'متغيرات PHP', 'desc' => 'شرح المتغيرات وأنواع البيانات', 'dur' => 25],
                        ['title' => 'الشروط والحلقات', 'desc' => 'if/else و for/while', 'dur' => 30],
                        ['title' => 'الدوال في PHP', 'desc' => 'كيفية تعريف واستدعاء الدوال', 'dur' => 22],
                        ['title' => 'المصفوفات', 'desc' => 'المصفوفات البسيطة وال multidimensional', 'dur' => 28],
                    ]],
                    ['title' => 'مقدمة في قواعد البيانات', 'lectures' => [
                        ['title' => 'ما هي قواعد البيانات؟', 'desc' => 'مقدمة في SQL وقواعد البيانات', 'dur' => 18],
                        ['title' => 'أساسيات SQL', 'desc' => 'SELECT, INSERT, UPDATE, DELETE', 'dur' => 35],
                    ]],
                ],
            ],
            [
                'title' => 'تطوير الويب بـ Laravel',
                'description' => 'دورة متقدمة في تطوير تطبيقات الويب باستخدام إطار العمل Laravel. تغطي المعمارية والـ API والـ authentication.',
                'price' => 299.99,
                'status' => 'published',
                'sections' => [
                    ['title' => 'أساسيات Laravel', 'lectures' => [
                        ['title' => 'مقدمة في Laravel', 'desc' => 'تعريف بإطار العمل وميزاته', 'dur' => 20],
                        ['title' => 'تثبيت وإعداد المشروع', 'desc' => 'كيفية إنشاء مشروع Laravel جديد', 'dur' => 15],
                        ['title' => 'هيكل المشروع', 'desc' => 'شرح مجلدات và ملفات المشروع', 'dur' => 25],
                    ]],
                    ['title' => 'Eloquent ORM', 'lectures' => [
                        ['title' => 'العلاقات', 'desc' => 'hasOne, hasMany, belongsTo, belongsToMany', 'dur' => 30],
                        ['title' => 'Query Builder', 'desc' => 'بناء الاستعلامات المعقدة', 'dur' => 28],
                        ['title' => 'Mutators و Accessors', 'desc' => 'تشفير البيانات والوصول إليها', 'dur' => 22],
                    ]],
                    ['title' => 'بناء API', 'lectures' => [
                        ['title' => 'RESTful API', 'desc' => 'مبادئ تصميم API', 'dur' => 25],
                        ['title' => 'API Resources', 'desc' => 'تنسيق استجابة API', 'dur' => 20],
                        ['title' => 'Authentication', 'desc' => 'Sanctum و Token-based auth', 'dur' => 35],
                    ]],
                ],
            ],
            [
                'title' => 'React للمبتدئين',
                'description' => 'تعلم بناء واجهات المستخدم التفاعلية باستخدام React. من المفاهيم الأساسية إلى Hooks المتقدمة.',
                'price' => 199.99,
                'status' => 'published',
                'sections' => [
                    ['title' => 'أساسيات React', 'lectures' => [
                        ['title' => 'ما هو React؟', 'desc' => 'مقدمة في مكتبة React', 'dur' => 18],
                        ['title' => 'JSX و Components', 'desc' => 'بناء المكونات الأولى', 'dur' => 22],
                        ['title' => 'الـ Props و State', 'desc' => 'إدارة البيانات في المكونات', 'dur' => 30],
                    ]],
                    ['title' => 'Hooks المتقدمة', 'lectures' => [
                        ['title' => 'useEffect', 'desc' => 'إدارة التأثيرات الجانبية', 'dur' => 25],
                        ['title' => 'useContext', 'desc' => 'إدارة الحالة العامة', 'dur' => 20],
                        ['title' => 'Custom Hooks', 'desc' => 'إنشاء hooks مخصصة', 'dur' => 28],
                    ]],
                ],
            ],
            [
                'title' => 'أساسيات التصميم UI/UX',
                'description' => 'تعلم مبادئ تصميم واجهات المستخدم وتجربة المستخدم. من نظرية الألوان إلى تصميم التطبيقات.',
                'price' => 149.99,
                'status' => 'draft',
                'sections' => [
                    ['title' => 'مبادئ التصميم', 'lectures' => [
                        ['title' => 'نظرية الألوان', 'desc' => 'كيف تختار الألوان المناسبة', 'dur' => 20],
                        ['title' => 'الطباعة والخطوط', 'desc' => 'اختيار واستخدام الخطوط', 'dur' => 15],
                    ]],
                ],
            ],
            [
                'title' => 'دورة DevOps的基础',
                'description' => 'مقدمة في مفاهيم DevOps و Docker و CI/CD. تعلم كيفية نشر تطبيقاتك.',
                'price' => 399.99,
                'status' => 'published',
                'sections' => [
                    ['title' => 'مقدمة في Docker', 'lectures' => [
                        ['title' => 'ما هو Docker؟', 'desc' => 'تعريف Docker والأوعية', 'dur' => 20],
                        ['title' => 'Dockerfile', 'desc' => 'كتابة ملفات Docker', 'dur' => 25],
                        ['title' => 'Docker Compose', 'desc' => 'تنسيق عدة أوعية', 'dur' => 30],
                    ]],
                ],
            ],
        ];

        $courseModels = [];
        foreach ($coursesData as $courseData) {
            $course = Course::create([
                'title' => $courseData['title'],
                'description' => $courseData['description'],
                'price' => $courseData['price'],
                'status' => $courseData['status'],
                'instructor_id' => $instructor->id,
            ]);
            $courseModels[] = $course;

            foreach ($courseData['sections'] as $sIdx => $sectionData) {
                $section = CourseSection::create([
                    'course_id' => $course->id,
                    'title' => $sectionData['title'],
                    'sort_order' => $sIdx + 1,
                ]);

                foreach ($sectionData['lectures'] as $lIdx => $lectureData) {
                    Lecture::create([
                        'section_id' => $section->id,
                        'title' => $lectureData['title'],
                        'description' => $lectureData['desc'],
                        'duration' => $lectureData['dur'],
                        'sort_order' => $lIdx + 1,
                    ]);
                }
            }
        }

        $enrollmentsData = [
            [0, 0, 'active', 'manual'],
            [0, 1, 'active', 'purchase'],
            [0, 2, 'active', 'purchase'],
            [1, 0, 'active', 'manual'],
            [1, 1, 'active', 'purchase'],
            [2, 0, 'active', 'manual'],
            [3, 0, 'active', 'manual'],
            [3, 1, 'active', 'purchase'],
            [4, 0, 'active', 'manual'],
            [4, 2, 'active', 'purchase'],
            [5, 1, 'active', 'purchase'],
            [6, 0, 'active', 'manual'],
            [7, 3, 'active', 'manual'],
            [8, 1, 'active', 'purchase'],
            [9, 0, 'active', 'manual'],
        ];

        foreach ($enrollmentsData as [$sIdx, $cIdx, $status, $source]) {
            if (isset($students[$sIdx]) && isset($courseModels[$cIdx])) {
                Enrollment::create([
                    'student_id' => $students[$sIdx]->id,
                    'course_id' => $courseModels[$cIdx]->id,
                    'status' => $status,
                    'source' => $source,
                    'started_at' => now()->subDays(mt_rand(0, 30)),
                ]);
            }
        }

        $notifications = [
            'تسجيل طالب جديد: محمد عبدالله',
            'طالب جديد: فاطمة الزهراء اشترت دورة Laravel',
            'تم اعتماد حساب الطالب عمر العتيبي',
            'سؤال جديد في محاضرة "متغيرات PHP"',
            'طالب ينتظر الاعتماد: هند الزهراني',
        ];

        foreach ($notifications as $i => $text) {
            Notification::create([
                'user_id' => $instructor->id,
                'title' => 'إشعار جديد',
                'body' => $text,
                'read_at' => $i < 2 ? now()->subHours($i) : null,
            ]);
        }

        // Seed Products for Courses (Paid Courses)
        foreach ($courseModels as $course) {
            if ($course->price > 0) {
                Product::create([
                    'instructor_id' => $instructor->id,
                    'name' => "شراء كورس: {$course->title}",
                    'sellable_id' => $course->id,
                    'sellable_type' => Course::class,
                    'price' => $course->price,
                    'access_duration_days' => null, // Lifetime
                    'is_active' => true,
                ]);
            }
        }

        // Seed Lecture Product (Single Lecture)
        $lecturePhp = Lecture::where('title', 'متغيرات PHP')->first();
        if ($lecturePhp) {
            Product::create([
                'instructor_id' => $instructor->id,
                'name' => 'شراء محاضرة منفردة: متغيرات PHP 🧪',
                'sellable_id' => $lecturePhp->id,
                'sellable_type' => Lecture::class,
                'price' => 15.00,
                'access_duration_days' => 14, // 14 days access
                'is_active' => true,
            ]);
        }

        // Seed Section Product (Month / Section)
        $sectionPhp = CourseSection::where('title', 'أساسيات PHP')->first();
        if ($sectionPhp) {
            Product::create([
                'instructor_id' => $instructor->id,
                'name' => 'اشتراك شهر: أساسيات لغة PHP 📅',
                'sellable_id' => $sectionPhp->id,
                'sellable_type' => CourseSection::class,
                'price' => 45.00,
                'access_duration_days' => 30, // 30 days access
                'is_active' => true,
            ]);
        }

        // Seed Bundle (Full Package Subscription)
        $bundle = Bundle::create([
            'instructor_id' => $instructor->id,
            'name' => 'اشتراك كامل الباقة: التطوير الشامل بالـ PHP و الويب 💎',
            'price' => 399.99,
        ]);

        // Attach all products to this bundle
        $products = Product::all();
        foreach ($products as $p) {
            $bundle->products()->attach($p->id);
        }
    }
}
