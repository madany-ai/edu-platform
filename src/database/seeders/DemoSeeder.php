<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lecture;
use App\Models\Enrollment;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Product;
use App\Models\Bundle;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Choice;
use App\Models\Governorate;
use App\Models\GradeLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('⛔ DemoSeeder cannot be executed in production environment!');
            return;
        }
        // ═══════════════════════════════════════════════════════════════
        // 1. ROLES
        // ═══════════════════════════════════════════════════════════════
        Role::firstOrCreate(['name' => 'instructor']);
        Role::firstOrCreate(['name' => 'student']);
        Role::firstOrCreate(['name' => 'assistant']);

        // ═══════════════════════════════════════════════════════════════
        // 2. INSTRUCTOR (Science Teacher)
        // ═══════════════════════════════════════════════════════════════
        $instructor = User::create([
            'name' => 'أ. محمد عبدالرحمن',
            'email' => 'teacher@demo.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $instructor->assignRole('instructor');

        // ═══════════════════════════════════════════════════════════════
        // 3. ASSISTANTS (2 Teaching Assistants)
        // ═══════════════════════════════════════════════════════════════
        $assistant1User = User::create([
            'name' => 'م. سارة أحمد',
            'email' => 'assistant1@demo.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'assistant_code' => 'TA-001',
        ]);
        $assistant1User->assignRole('assistant');

        $assistant2User = User::create([
            'name' => 'م. علي حسن',
            'email' => 'assistant2@demo.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'assistant_code' => 'TA-002',
        ]);
        $assistant2User->assignRole('assistant');

        // ═══════════════════════════════════════════════════════════════
        // 4. GEOGRAPHY DATA
        // ═══════════════════════════════════════════════════════════════
        $govNames = [
            'القاهرة', 'الجيزة', 'الإسكندرية', 'القليوبية', 'الشرقية',
            'المنوفية', 'الغربية', 'الدقهلية', 'البحيرة', 'كفر الشيخ',
            'دمياط', 'بورسعيد', 'الإسماعيلية', 'السويس', 'الفيوم',
            'بني سويف', 'المنيا', 'أسيوط', 'سوهاج', 'قنا', 'الأقصر', 'أسوان',
        ];
        $dbGovs = [];
        foreach ($govNames as $govName) {
            $dbGovs[] = Governorate::firstOrCreate(['name' => $govName]);
        }

        $gradeNames = [
            ['name' => 'الصف الأول الإعدادي', 'sort_order' => 1],
            ['name' => 'الصف الثاني الإعدادي', 'sort_order' => 2],
            ['name' => 'الصف الثالث الإعدادي', 'sort_order' => 3],
        ];
        foreach ($gradeNames as $gradeInfo) {
            GradeLevel::firstOrCreate(
                ['name' => $gradeInfo['name']],
                ['sort_order' => $gradeInfo['sort_order']]
            );
        }

        $prep3Grade = GradeLevel::where('name', 'الصف الثالث الإعدادي')->first();

        // ═══════════════════════════════════════════════════════════════
        // 5. STUDENTS (15 students — all active, 3rd year prep)
        // ═══════════════════════════════════════════════════════════════
        $studentData = [
            ['first' => 'محمد', 'second' => 'علي', 'third' => 'حسين', 'last' => 'عبدالله', 'gender' => 'male',   'job' => 'مهندس'],
            ['first' => 'فاطمة', 'second' => 'أحمد', 'third' => 'محمد', 'last' => 'الزهراء', 'gender' => 'female', 'job' => 'طبيب'],
            ['first' => 'عمر', 'second' => 'خالد', 'third' => 'ياسر', 'last' => 'العتيبي', 'gender' => 'male',   'job' => 'معلم'],
            ['first' => 'نورة', 'second' => 'سعود', 'third' => 'خالد', 'last' => 'الشمري', 'gender' => 'female', 'job' => 'محاسب'],
            ['first' => 'يوسف', 'second' => 'إبراهيم', 'third' => 'محمد', 'last' => 'القحطاني', 'gender' => 'male',   'job' => 'موظف'],
            ['first' => 'ريم', 'second' => 'عبدالرحمن', 'third' => 'سليمان', 'last' => 'الدوسري', 'gender' => 'female', 'job' => 'مبرمج'],
            ['first' => 'خالد', 'second' => 'سعد', 'third' => 'فهد', 'last' => 'المطيري', 'gender' => 'male',   'job' => 'حرفي'],
            ['first' => 'سارة', 'second' => 'ياسر', 'third' => 'عبدالله', 'last' => 'الحربي', 'gender' => 'female', 'job' => 'مستشار'],
            ['first' => 'عبدالله', 'second' => 'منصور', 'third' => 'علي', 'last' => 'النعيمي', 'gender' => 'male',   'job' => 'متاجر'],
            ['first' => 'هند', 'second' => 'تركي', 'third' => 'سلطان', 'last' => 'الزهراني', 'gender' => 'female', 'job' => 'فنان'],
            ['first' => 'أحمد', 'second' => 'حسام', 'third' => 'محمود', 'last' => 'الصعيدي', 'gender' => 'male',   'job' => 'مهندس'],
            ['first' => 'ياسمين', 'second' => 'عادل', 'third' => 'فتحي', 'last' => 'المنصوري', 'gender' => 'female', 'job' => 'طبيب'],
            ['first' => 'كريم', 'second' => 'وليد', 'third' => 'سامي', 'last' => 'الشرقاوي', 'gender' => 'male',   'job' => 'معلم'],
            ['first' => 'مريم', 'second' => 'طارق', 'third' => 'حسن', 'last' => 'المقدري', 'gender' => 'female', 'job' => 'محاسب'],
            ['first' => 'حسن', 'second' => 'إبراهيم', 'third' => 'مصطفى', 'last' => 'البلبيسي', 'gender' => 'male',   'job' => 'موظف'],
        ];

        $students = [];
        foreach ($studentData as $i => $s) {
            $user = User::create([
                'name' => "{$s['first']} {$s['last']}",
                'email' => 'student' . ($i + 1) . '@demo.com',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]);
            $user->assignRole('student');

            $students[] = Student::create([
                'user_id' => $user->id,
                'first_name' => $s['first'],
                'second_name' => $s['second'],
                'third_name' => $s['third'],
                'last_name' => $s['last'],
                'phone' => '010' . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'father_phone' => '011' . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'mother_phone' => '012' . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'guardian_job' => $s['job'],
                'gender' => $s['gender'],
                'birth_date' => '2008-' . str_pad(mt_rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(mt_rand(1, 28), 2, '0', STR_PAD_LEFT),
                'is_verified' => true,
                'governorate_id' => $dbGovs[array_rand($dbGovs)]->id,
                'academic_year' => 'prep_3',
            ]);
        }

        // ═══════════════════════════════════════════════════════════════
        // 6. COURSES — Science for 3rd Year Preparatory (تالت إعدادي)
        // ═══════════════════════════════════════════════════════════════

        // ─────────────────────────────────────────────
        // COURSE 1: العلوم — الفصل الأول (مجاني)
        // ─────────────────────────────────────────────
        $course1 = Course::create([
            'title' => 'علوم تالت إعدادي — الفصل الأول',
            'description' => 'دورة شاملة لمنهج العلوم للصف الثالث الإعدادي الفصل الأول. تغطي حركة الأرض والقمر، الطاقة وتحولاتها، وخصائص المادة وتركيبها. مع شرح مبسط وأمثلة تطبيقية.',
            'price' => 0,
            'status' => 'published',
            'instructor_id' => $instructor->id,
        ]);

        // Section 1: حركة الأرض والقمر
        $s1_1 = CourseSection::create([
            'course_id' => $course1->id,
            'title' => 'الوحدة الأولى: حركة الأرض والقمر',
            'sort_order' => 1,
        ]);

        $lectures_s1_1 = [
            ['title' => 'حركة الأرض حول الشمس', 'desc' => 'شرح حركة الأرض حول محورها وحول الشمس، والفصول الأربعة', 'dur' => 45],
            ['title' => 'القمر ودوراته', 'desc' => 'دورات القمر حول الأرض والمراحل القمرية', 'dur' => 35],
            ['title' => 'الخسوف والكسوف', 'desc' => 'تفسير ظاهرة الخسوف والكسوف وشروط حدوثها', 'dur' => 30],
            ['title' => 'المد والجزر', 'desc' => 'تأثير جاذبية القمر والشمس على المد والجزر', 'dur' => 25],
        ];

        foreach ($lectures_s1_1 as $i => $lec) {
            Lecture::create([
                'section_id' => $s1_1->id,
                'title' => $lec['title'],
                'description' => $lec['desc'],
                'duration' => $lec['dur'],
                'sort_order' => $i + 1,
            ]);
        }

        // Section 2: الطاقة وتحولاتها
        $s1_2 = CourseSection::create([
            'course_id' => $course1->id,
            'title' => 'الوحدة الثانية: الطاقة وتحولاتها',
            'sort_order' => 2,
        ]);

        $lectures_s1_2 = [
            ['title' => 'مقدمة في الطاقة', 'desc' => 'تعريف الطاقة وأنواعها: حركية، كهربائية، كيميائية، حرارية', 'dur' => 40],
            ['title' => 'تحولات الطاقة', 'desc' => 'كيف تتحول الطاقة من نوع لآخر مع أمثلة يومية', 'dur' => 35],
            ['title' => 'الطاقة المتجددة', 'desc' => 'مصادر الطاقة المتجددة: شمسية، رياح، مائية، حرارية أرضية', 'dur' => 30],
            ['title' => 'مبدأ حفظ الطاقة', 'desc' => 'مبدأ بقاء الطاقة وتطبيقاته العملية', 'dur' => 25],
        ];

        foreach ($lectures_s1_2 as $i => $lec) {
            Lecture::create([
                'section_id' => $s1_2->id,
                'title' => $lec['title'],
                'description' => $lec['desc'],
                'duration' => $lec['dur'],
                'sort_order' => $i + 1,
            ]);
        }

        // Section 3: خصائص المادة وتركيبها
        $s1_3 = CourseSection::create([
            'course_id' => $course1->id,
            'title' => 'الوحدة الثالثة: خصائص المادة وتركيبها',
            'sort_order' => 3,
        ]);

        $lectures_s1_3 = [
            ['title' => 'المادة وأنواعها', 'desc' => 'المادة الصلبة والسائلة والغازية وخصائص كل منها', 'dur' => 35],
            ['title' => 'الذرة وتركيبها', 'desc' => 'النواة والمحلل والنيوترون والإلكترونات', 'dur' => 40],
            ['title' => 'الجدول الدوري', 'desc' => 'تنظيم العناصر في الجدول الدوري وخصائص الدورات', 'dur' => 30],
            ['title' => 'الروابط الكيميائية', 'desc' => 'الروابط الأيونية والتساهمية والمعدنية', 'dur' => 35],
        ];

        foreach ($lectures_s1_3 as $i => $lec) {
            Lecture::create([
                'section_id' => $s1_3->id,
                'title' => $lec['title'],
                'description' => $lec['desc'],
                'duration' => $lec['dur'],
                'sort_order' => $i + 1,
            ]);
        }

        // ─────────────────────────────────────────────
        // COURSE 2: العلوم — الفصل الثاني (مدفوع)
        // ─────────────────────────────────────────────
        $course2 = Course::create([
            'title' => 'علوم تالت إعدادي — الفصل الثاني',
            'description' => 'دورة شاملة لمنهج العلوم للصف الثالث الإعدادي الفصل الثاني. تغطي التفاعلات الكيميائية، النظام البيئي، وجسم الإنسان. مع شرح مبسط ورسوم توضيحية.',
            'price' => 199.99,
            'status' => 'published',
            'instructor_id' => $instructor->id,
        ]);

        // Section 4: التفاعلات الكيميائية
        $s2_1 = CourseSection::create([
            'course_id' => $course2->id,
            'title' => 'الوحدة الرابعة: التفاعلات الكيميائية',
            'sort_order' => 1,
        ]);

        $lectures_s2_1 = [
            ['title' => 'مقدمة في التفاعلات الكيميائية', 'desc' => 'تعريف التفاعل الكيميائي وعوامله المؤثرة', 'dur' => 40],
            ['title' => 'أنواع التفاعلات الكيميائية', 'desc' => 'تفاعل الاتحاد، التحلل، الأكسدة والاختزال', 'dur' => 35],
            ['title' => 'المعادلة الكيميائية', 'desc' => 'كتابة المعادلات الكيميائية وموازنتها', 'dur' => 30],
            ['title' => 'التفاعلات في حياتنا اليومية', 'desc' => 'تطبيقات التفاعلات الكيميائية في الحياة اليومية', 'dur' => 25],
        ];

        foreach ($lectures_s2_1 as $i => $lec) {
            Lecture::create([
                'section_id' => $s2_1->id,
                'title' => $lec['title'],
                'description' => $lec['desc'],
                'duration' => $lec['dur'],
                'sort_order' => $i + 1,
            ]);
        }

        // Section 5: النظام البيئي والبيئة
        $s2_2 = CourseSection::create([
            'course_id' => $course2->id,
            'title' => 'الوحدة الخامسة: النظام البيئي والبيئة',
            'sort_order' => 2,
        ]);

        $lectures_s2_2 = [
            ['title' => 'النظام البيئي البري والمائي', 'desc' => 'النظام البيئي البري والبحري وعناصره', 'dur' => 35],
            ['title' => 'السلسلة الغذائية', 'desc' => 'السلسلة الغذائية وشبكة الغذاء', 'dur' => 30],
            ['title' => 'التلوث البيئي', 'desc' => 'أنواع التلوث وتأثيرها على الكائنات الحية', 'dur' => 25],
            ['title' => 'حماية البيئة', 'desc' => 'طرق الحفاظ على البيئة وتطوع بيئي', 'dur' => 20],
        ];

        foreach ($lectures_s2_2 as $i => $lec) {
            Lecture::create([
                'section_id' => $s2_2->id,
                'title' => $lec['title'],
                'description' => $lec['desc'],
                'duration' => $lec['dur'],
                'sort_order' => $i + 1,
            ]);
        }

        // Section 6: جسم الإنسان
        $s2_3 = CourseSection::create([
            'course_id' => $course2->id,
            'title' => 'الوحدة السادسة: جسم الإنسان',
            'sort_order' => 3,
        ]);

        $lectures_s2_3 = [
            ['title' => 'الجهاز الهضمي', 'desc' => 'المريء والمعدة والأمعاء وعملية الهضم', 'dur' => 40],
            ['title' => 'الجهاز الدوري', 'desc' => 'القلب والأوعية الدموية ودور الدم في الجسم', 'dur' => 35],
            ['title' => 'الجهاز التنفسي', 'desc' => 'الرئتان وعمليات التنفس والاستنشاق', 'dur' => 30],
            ['title' => 'الجهاز العصبي', 'desc' => 'الدماغ والأعصاب ونقل الإشارات العصبية', 'dur' => 35],
        ];

        foreach ($lectures_s2_3 as $i => $lec) {
            Lecture::create([
                'section_id' => $s2_3->id,
                'title' => $lec['title'],
                'description' => $lec['desc'],
                'duration' => $lec['dur'],
                'sort_order' => $i + 1,
            ]);
        }

        // ─────────────────────────────────────────────
        // COURSE 3: مراجعة شاملة لامتحانات تالت إعدادي (مدفوع)
        // ─────────────────────────────────────────────
        $course3 = Course::create([
            'title' => 'مراجعة شاملة — علوم تالت إعدادي',
            'description' => 'دورة مراجعة شاملة لكل منهج علوم تالت إعدادي. تشمل ملخصات ونماذج امتحانات محلولة وأسئلة تدريبية مع التصحيح. مثالية للتحضير للامتحانات.',
            'price' => 149.99,
            'status' => 'published',
            'instructor_id' => $instructor->id,
        ]);

        // Section 7: مراجعة الفصل الأول
        $s3_1 = CourseSection::create([
            'course_id' => $course3->id,
            'title' => 'مراجعة الفصل الأول',
            'sort_order' => 1,
        ]);

        $lectures_s3_1 = [
            ['title' => 'ملخص: حركة الأرض والقمر', 'desc' => 'ملخص شامل للوحدة الأولى مع أهم الأسئلة', 'dur' => 50],
            ['title' => 'ملخص: الطاقة وتحولاتها', 'desc' => 'ملخص شامل للوحدة الثانية مع أمثلة محلولة', 'dur' => 45],
            ['title' => 'ملخص: خصائص المادة', 'desc' => 'ملخص شامل للوحدة الثالثة مع جدول الدوري', 'dur' => 40],
            ['title' => 'نموذج امتحان الفصل الأول', 'desc' => 'نموذج امتحان شامل مع التصحيح التفصيلي', 'dur' => 60],
        ];

        foreach ($lectures_s3_1 as $i => $lec) {
            Lecture::create([
                'section_id' => $s3_1->id,
                'title' => $lec['title'],
                'description' => $lec['desc'],
                'duration' => $lec['dur'],
                'sort_order' => $i + 1,
            ]);
        }

        // Section 8: مراجعة الفصل الثاني
        $s3_2 = CourseSection::create([
            'course_id' => $course3->id,
            'title' => 'مراجعة الفصل الثاني',
            'sort_order' => 2,
        ]);

        $lectures_s3_2 = [
            ['title' => 'ملخص: التفاعلات الكيميائية', 'desc' => 'ملخص شامل للوحدة الرابعة مع معادلات محلولة', 'dur' => 45],
            ['title' => 'ملخص: النظام البيئي', 'desc' => 'ملخص شامل للوحدة الخامسة مع رسوم توضيحية', 'dur' => 35],
            ['title' => 'ملخص: جسم الإنسان', 'desc' => 'ملخص شامل للوحدة السادسة مع أجهزة الجسم', 'dur' => 40],
            ['title' => 'نموذج امتحان الفصل الثاني', 'desc' => 'نموذج امتحان شامل مع التصحيح التفصيلي', 'dur' => 60],
        ];

        foreach ($lectures_s3_2 as $i => $lec) {
            Lecture::create([
                'section_id' => $s3_2->id,
                'title' => $lec['title'],
                'description' => $lec['desc'],
                'duration' => $lec['dur'],
                'sort_order' => $i + 1,
            ]);
        }

        // Section 9: أسئلة تدريبية نهائية
        $s3_3 = CourseSection::create([
            'course_id' => $course3->id,
            'title' => 'أسئلة تدريبية نهائية',
            'sort_order' => 3,
        ]);

        $lectures_s3_3 = [
            ['title' => '100 سؤال تدريبي مع التصحيح', 'desc' => 'مجموعة شاملة من الأسئلة التدريبية على كل الوحدات', 'dur' => 90],
            ['title' => 'نصائح للمراجعة النهائية', 'desc' => 'استراتيجيات المراجعة الفعالة للامتحانات', 'dur' => 20],
        ];

        foreach ($lectures_s3_3 as $i => $lec) {
            Lecture::create([
                'section_id' => $s3_3->id,
                'title' => $lec['title'],
                'description' => $lec['desc'],
                'duration' => $lec['dur'],
                'sort_order' => $i + 1,
            ]);
        }

        // ═══════════════════════════════════════════════════════════════
        // 7. EXAMS & QUESTIONS (Sample exams for some lectures)
        // ═══════════════════════════════════════════════════════════════

        // Exam 1: امتحان الوحدة الأولى — حركة الأرض والقمر
        $exam1 = Exam::create([
            'lecture_id' => $s1_1->lectures()->first()->id,
            'title' => 'اختبار الوحدة الأولى: حركة الأرض والقمر',
            'duration' => 15,
        ]);

        $exam1Questions = [
            [
                'question' => 'ما هي الفترة الزمنية لدوران الأرض حول محورها؟',
                'type' => 'multiple_choice',
                'degree' => 2,
                'choices' => [
                    ['answer' => '24 ساعة', 'is_correct' => true],
                    ['answer' => '365 يوم', 'is_correct' => false],
                    ['answer' => '27.3 يوم', 'is_correct' => false],
                    ['answer' => '12 شهر', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'السبب الرئيسي لحدث الفصول الأربعة على الأرض هو:',
                'type' => 'multiple_choice',
                'degree' => 2,
                'choices' => [
                    ['answer' => 'ميلان محور الأرض', 'is_correct' => true],
                    ['answer' => 'بعد الأرض عن الشمس', 'is_correct' => false],
                    ['answer' => 'سرعة دوران الأرض', 'is_correct' => false],
                    ['answer' => 'حجم الأرض', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'الكسوف يحدث عندما يمر القمر بين الأرض والشمس.',
                'type' => 'true_false',
                'degree' => 1,
                'choices' => [
                    ['answer' => 'صحيح', 'is_correct' => true],
                    ['answer' => 'خطأ', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'اشرح كيف تحدث ظاهرة المد والجزر مع التوضيح.',
                'type' => 'essay',
                'degree' => 3,
                'choices' => [],
            ],
        ];

        foreach ($exam1Questions as $qData) {
            $q = Question::create([
                'exam_id' => $exam1->id,
                'type' => $qData['type'],
                'question' => $qData['question'],
                'degree' => $qData['degree'],
            ]);

            foreach ($qData['choices'] as $cData) {
                Choice::create([
                    'question_id' => $q->id,
                    'answer' => $cData['answer'],
                    'is_correct' => $cData['is_correct'],
                ]);
            }
        }

        // Exam 2: امتحان الوحدة الرابعة — التفاعلات الكيميائية
        $exam2 = Exam::create([
            'lecture_id' => $s2_1->lectures()->first()->id,
            'title' => 'اختبار الوحدة الرابعة: التفاعلات الكيميائية',
            'duration' => 20,
        ]);

        $exam2Questions = [
            [
                'question' => 'تفاعل الاتحاد هو تفاعل يتحد فيه عنصران أو أكثر لتكوين مركب واحد.',
                'type' => 'true_false',
                'degree' => 1,
                'choices' => [
                    ['answer' => 'صحيح', 'is_correct' => true],
                    ['answer' => 'خطأ', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'ما هو الناتج من تفاعل حمض الهيدروكلوريك مع هيدروكسيد الصوديوم؟',
                'type' => 'multiple_choice',
                'degree' => 2,
                'choices' => [
                    ['answer' => 'ماء + كلوريد الصوديوم', 'is_correct' => true],
                    ['answer' => 'غاز الهيدروجين', 'is_correct' => false],
                    ['answer' => 'أكسيد النحاس', 'is_correct' => false],
                    ['answer' => 'حمض كبريتيك', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'اذكر ثلاثة عوامل مؤثرة في سرعة التفاعل الكيميائي.',
                'type' => 'essay',
                'degree' => 3,
                'choices' => [],
            ],
        ];

        foreach ($exam2Questions as $qData) {
            $q = Question::create([
                'exam_id' => $exam2->id,
                'type' => $qData['type'],
                'question' => $qData['question'],
                'degree' => $qData['degree'],
            ]);

            foreach ($qData['choices'] as $cData) {
                Choice::create([
                    'question_id' => $q->id,
                    'answer' => $cData['answer'],
                    'is_correct' => $cData['is_correct'],
                ]);
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // 8. ENROLLMENTS (Students enrolled in courses)
        // ═══════════════════════════════════════════════════════════════
        $courses = [$course1, $course2, $course3];

        $enrollmentsData = [
            // Students 0-4: Enrolled in Course 1 (free)
            [0, 0, 'active', 'manual'],
            [1, 0, 'active', 'manual'],
            [2, 0, 'active', 'manual'],
            [3, 0, 'active', 'manual'],
            [4, 0, 'active', 'manual'],
            // Students 5-9: Enrolled in Course 2 (paid)
            [5, 1, 'active', 'purchase'],
            [6, 1, 'active', 'purchase'],
            [7, 1, 'active', 'purchase'],
            [8, 1, 'active', 'purchase'],
            [9, 1, 'active', 'purchase'],
            // Students 10-14: Enrolled in Course 3 (paid)
            [10, 2, 'active', 'purchase'],
            [11, 2, 'active', 'purchase'],
            [12, 2, 'active', 'purchase'],
            [13, 2, 'active', 'purchase'],
            [14, 2, 'active', 'purchase'],
            // Some students enrolled in multiple courses
            [0, 1, 'active', 'purchase'],
            [0, 2, 'active', 'purchase'],
            [1, 2, 'active', 'purchase'],
            [5, 0, 'active', 'manual'],
            [10, 0, 'active', 'manual'],
        ];

        foreach ($enrollmentsData as [$sIdx, $cIdx, $status, $source]) {
            Enrollment::create([
                'student_id' => $students[$sIdx]->id,
                'course_id' => $courses[$cIdx]->id,
                'status' => $status,
                'source' => $source,
                'started_at' => now()->subDays(mt_rand(1, 30)),
            ]);
        }

        // ═══════════════════════════════════════════════════════════════
        // 9. NOTIFICATIONS
        // ═══════════════════════════════════════════════════════════════
        $notifications = [
            'تم تسجيل 15 طالب جديد في منصة العلوم',
            'الطالب محمد عبدالله أكمل محاضرة "حركة الأرض حول الشمس"',
            'سؤال جديد في محاضرة "الذرة وتركيبها" من الطالبة فاطمة الزهراء',
            'تم تأكيد دفع 5 طلبات شراء للفصل الثاني',
            'الطالب عمر العتيبي سأل سؤالاً في محاضرة "السلسلة الغذائية"',
            'تذكير: موعد الامتحان النهائي خلال أسبوعين',
            'تم رفع محتوى جديد: ملخص الفصل الأول شامل',
        ];

        foreach ($notifications as $i => $text) {
            Notification::create([
                'user_id' => $instructor->id,
                'title' => 'إشعار منصة العلوم',
                'body' => $text,
                'read_at' => $i < 3 ? now()->subHours($i * 2) : null,
            ]);
        }

        // ═══════════════════════════════════════════════════════════════
        // 10. PRODUCTS (For paid courses)
        // ═══════════════════════════════════════════════════════════════
        Product::create([
            'instructor_id' => $instructor->id,
            'name' => 'اشتراك كورس: علوم الفصل الثاني — تالت إعدادي',
            'sellable_id' => $course2->id,
            'sellable_type' => Course::class,
            'price' => 199.99,
            'access_duration_days' => null,
            'is_active' => true,
        ]);

        Product::create([
            'instructor_id' => $instructor->id,
            'name' => 'اشتراك كورس: مراجعة شاملة — علوم تالت إعدادي',
            'sellable_id' => $course3->id,
            'sellable_type' => Course::class,
            'price' => 149.99,
            'access_duration_days' => null,
            'is_active' => true,
        ]);

        // ═══════════════════════════════════════════════════════════════
        // 10.1 STANDALONE LECTURES
        // ═══════════════════════════════════════════════════════════════
        $standaloneLecture1 = Lecture::create([
            'instructor_id' => $instructor->id,
            'title' => 'محاضرة منفردة: ملخص التفاعلات الكيميائية بالتفصيل 🧪',
            'description' => 'شرح مكثف وشامل لجميع معادلات الكيمياء للترم الأول مع أمثلة مجابة.',
            'duration' => 45,
            'sort_order' => 1,
            'status' => 'published',
            'price' => 35.00,
        ]);

        $standaloneLecture1->video()->create([
            'video_path' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => 'completed',
            'bunny_video_id' => 'youtube',
            'duration' => 45,
        ]);

        $standaloneProduct1 = Product::create([
            'instructor_id' => $instructor->id,
            'name' => 'محاضرة: ملخص التفاعلات الكيميائية',
            'sellable_id' => $standaloneLecture1->id,
            'sellable_type' => Lecture::class,
            'price' => 35.00,
            'access_duration_days' => 30,
            'is_active' => true,
        ]);

        $standaloneLecture2 = Lecture::create([
            'instructor_id' => $instructor->id,
            'title' => 'محاضرة منفردة: مراجعة قوانين السرعة والعجلة ⚡',
            'description' => 'حل 50 مسألة على قوانين الحركة والسرعة النسبية والعجلة المنتظمة.',
            'duration' => 60,
            'sort_order' => 2,
            'status' => 'published',
            'price' => 25.00,
        ]);

        $standaloneLecture2->video()->create([
            'video_path' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => 'completed',
            'bunny_video_id' => 'youtube',
            'duration' => 60,
        ]);

        $standaloneProduct2 = Product::create([
            'instructor_id' => $instructor->id,
            'name' => 'محاضرة: مراجعة قوانين السرعة والعجلة',
            'sellable_id' => $standaloneLecture2->id,
            'sellable_type' => Lecture::class,
            'price' => 25.00,
            'access_duration_days' => null,
            'is_active' => true,
        ]);

        // ═══════════════════════════════════════════════════════════════
        // 11. BUNDLE
        // ═══════════════════════════════════════════════════════════════
        $bundle = Bundle::create([
            'instructor_id' => $instructor->id,
            'name' => 'باقة التفوق الشاملة: الكورسات + المحاضرات المنفردة 💎',
            'price' => 299.99,
        ]);

        $products = Product::where('is_active', true)->get();
        foreach ($products as $p) {
            $bundle->products()->attach($p->id);
        }

        // ═══════════════════════════════════════════════════════════════
        // SUMMARY
        // ═══════════════════════════════════════════════════════════════
        $this->command->info('===========================================');
        $this->command->info('  Demo Data Seeded Successfully!');
        $this->command->info('===========================================');
        $this->command->info('  Instructor:   teacher@demo.com');
        $this->command->info('  Assistants:   assistant1@demo.com, assistant2@demo.com');
        $this->command->info('  Students:     student1@demo.com — student15@demo.com');
        $this->command->info('  Password:     password');
        $this->command->info('  Courses:      3 courses (1 free, 2 paid)');
        $this->command->info('  Sections:     9 sections total');
        $this->command->info('  Lectures:     34 lectures total');
        $this->command->info('  Exams:        2 exams with questions');
        $this->command->info('  Enrollments:  20 enrollments');
        $this->command->info('  Products:     3 products + 1 bundle');
        $this->command->info('  Notifications: 7 notifications for instructor');
        $this->command->info('===========================================');
    }
}
