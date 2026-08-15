# التقرير الشامل لمعمارية وتفاصيل الكود وقابلية التوسع والأمان للمشروع (Production Audit & Architecture Overview)

---

## 📌 1. نظرة عامة عن المشروع (Project Overview & Domain)

هذا المشروع عبارة عن **منصة تعليمية متكاملة (LMS - Learning Management System)** مصممة للعمل في بيئة إنتاجية حقيقية (Production) وتدعم **النظام الهجين (Hybrid Educational System)** الذي يدمج بين التعليم عبر الإنترنت (Online E-Learning) والتعليم الفعلي في المراكز التعليمية (Offline Centers).

### 👥 أدواره وصلاحيات المستخدمين (User Roles & Capabilities):
1. **الطالب (Student):**
   - تصفح وشراء المقررات التعليمية (Courses)، الحزم (Bundles)، والمحاضرات المنفصلة (Standalone Lectures).
   - مشاهدة مقاطع الفيديو التعليمية عبر مشغل حماية متطور مع علامة مائية ديناميكية (Dynamic Watermark).
   - خوض الامتحانات الإلكترونية، متابعة محاولات الامتحان، واستعراض نتائج الإجابات.
   - طرح الأسئلة والتفاعل في قسم الأسئلة والأجوبة (Q&A).
   - متابعة الحضور والغياب والدرجات في السنتر التعليمي من خلال QR Code / البارلود.
2. **ولي الأمر (Parent):**
   - استلام إشعارات دورية بحضور/غياب الطالب ونتائج امتحاناته عبر SMS / WhatsApp / In-App Notifications.
3. **المدرس (Instructor):**
   - لوحة تحكم سريعة لإدارة المحتوى التعليمي والأقسام والمحاضرات والملفات والامتحانات.
   - متابعة الإحصائيات المالية والأكاديمية، ونسب إكمال الطلاب للمقررات.
4. **المساعد (Assistant):**
   - الإجابة على استفسارات الطلاب وأسئلتهم الأكاديمية.
   - تصحيح الأسئلة المقالية ومتابعة الامتحانات.
5. **موظف السنتر (Center Staff):**
   - المسح الضوئي لرمز QR لتسجيل حضور الطلاب بالسنتر.
   - رصد درجات الامتحانات الورقية وتوزيع الطلاب على المجموعات والأوقات.
6. **مدير النظام (Super Admin / Admin):**
   - لوحة تحكم مركزية مجانية وشاملة مبنية على **Filament PHP** لإدارة كل مفاصل النظام، الصلاحيات، المدفوعات، والشؤون المالية.

---

## 🏗️ 2. معمارية وهندسة النظام (System Architecture)

يعتمد المشروع على نمط **Decoupled Architecture (Headless CMS / API-Driven Architecture)** الذي يفصل الواجهة الخلفية عن الأمامية تماماً:

```
                  ┌─────────────────────────────────────────┐
                  │          Cloudflare DNS & WAF           │
                  └────────────────────┬────────────────────┘
                                       │
                                       ▼
                  ┌─────────────────────────────────────────┐
                  │       Nginx Proxy Manager / Reverse     │
                  └────────────┬───────────────┬────────────┘
                               │               │
            /api/* , /admin    │               │  React / App Router
                               ▼               ▼
                   ┌───────────────┐       ┌────────────────┐
                   │  Laravel API  │       │ Next.js 15 App │
                   │  (PHP 8.3/4)  │       │ (Frontend Web) │
                   └───────┬───────┘       └────────────────┘
                           │
       ┌───────────────────┼───────────────────┬───────────────────┐
       ▼                   ▼                   ▼                   ▼
┌──────────────┐   ┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│ PostgreSQL18 │   │   Redis 7    │    │ Bunny Stream │    │ Paymob/Fawry │
│ (DB Engine)  │   │Cache & Queue │    │ (HLS Video)  │    │(Webhooks API)│
└──────────────┘   └──────────────┘    └──────────────┘    └──────────────┘
```

### 🧩 مكونات البنية التحتية:
1. **الواجهة الخلفية (Backend API):**
   - إطار العمل: **Laravel 11/12** على **PHP 8.3/8.4**.
   - إدارة اللوحة: **Filament PHP v3** لإدارة البيانات المعقدة بسهولة وأمان.
   - المصادقة والتوثيق: **Laravel Sanctum** لإنشاء وإدارة رموز الوصول API Tokens.
2. **الواجهة الأمامية (Frontend Web App):**
   - إطار العمل: **Next.js 15 (App Router)** مع **TypeScript**.
   - التنسيق والتصميم: **Tailwind CSS** + **Radix UI / Shadcn UI** + **Lucide Icons**.
   - إدارة الحالة والاستعلامات: **React Query (TanStack Query)** + **Zustand**.
3. **منظومة التخزين والفيديو (Media & Video Infrastructure):**
   - **Bunny.net Stream API**: رفع الفيديو وتحويله لتنسيق HLS المتكيف، مع توقيع الروابط (Signed URLs).
   - **MinIO / AWS S3**: تخزين المرفقات والملفات الدراسية والملفات الخاصة بحسابات الطلاب.
4. **قاعدة البيانات والكاش والطوابير (Database, Cache & Queues):**
   - **PostgreSQL 18**: حاوية قاعدة البيانات الرئيسية مع إندكسات مفهرسة لتسريع البحث.
   - **Redis 7**: تخزين الجلسات (Sessions)، الكاش (Cache)، وطوابير المهام الخلفية (Queue Workers) عبر `php artisan queue:work`.
5. **البيئة والحاويات (Docker Infrastructure):**
   - بيئة التطوير والإنتاج تدار عبر **Docker Compose** وتضم حاويات: `app`, `frontend`, `nginx`, `postgres`, `redis`, `queue`, `scheduler`, `proxy`.

---

## 📈 3. تفصيل الكود وقابلية التوسع (Code Deep-Dive & Scalability Evaluation)

### ✅ نقاط القوة والأنماط البرمجية الجيدة (Architectural Strengths):
1. **نمط مزود الخدمات والـ Gateways للمدفوعات (`Service & Gateway Pattern`):**
   - تم بناء نظام المدفوعات في [PaymentService.php](file:///home/madany/Projects/edu-platform/src/app/Services/Payment/PaymentService.php) باستخدام `Drivers` منفصلة ([FawryGateway.php](file:///home/madany/Projects/edu-platform/src/app/Services/Payment/Drivers/FawryGateway.php) و [PaymobGateway.php](file:///home/madany/Projects/edu-platform/src/app/Services/Payment/Drivers/PaymobGateway.php)). هذا يسمح بإضافة أي بوابات دفع جديدة (مثل Tap, Moyasar, Stripe) دون تعديل كود الأعمال الرئيسي.
2. **الفصل بين العمليات الثقيلة والأنشطة التزامنية (`Asynchronous Job Queuing`):**
   - إرسال التنبيهات لأولياء الأمور ومعالجة الفيديوهات تتم عبر [NotifyParentJob.php](file:///home/madany/Projects/edu-platform/src/app/Jobs/NotifyParentJob.php) و [ProcessVideoHLS.php](file:///home/madany/Projects/edu-platform/src/app/Jobs/ProcessVideoHLS.php) لمنع تعطيل استجابة الطلب (HTTP Request Latency).
3. **حماية أجزاء الفيديو HLS المخصصة:**
   - استخدام [VideoTokenService.php](file:///home/madany/Projects/edu-platform/src/app/Services/VideoTokenService.php) لتوليد HMAC short-lived tokens مخصصة لكل طالب وربطها برقم الـ IP لمعالجة تشغيل مقاطع الفيديو بشكل آمن ومحمي من التحميل المباشر.

---

### ⚠️ نقاط الضعف وعقبات التوسع (Scalability Bottlenecks & Code Review):

#### 1. مشكلة التخزين المؤقت المحلي فقط داخل الـ Memory (In-Memory Request Scope Caching):
- في الكلاس [VideoAccessService.php](file:///home/madany/Projects/edu-platform/src/app/Services/VideoAccessService.php):
  ```php
  protected array $studentCache = [];
  protected array $enrollmentsCache = [];
  ```
  **المشكلة:** التخزين المؤقت هنا يعيش فقط لأجل الطلب الواحد (Single HTTP Request). عند وجود ألف طالب يطلبون المحاضرات في نفس الوقت، سيتم تنفيذ استعلامات قواعد البيانات مراراً وتكراراً في كل طلب منفصل لأن `$this->studentCache` يتصفّر مع انتهاء كل Request.
  
  **الحل التوسعي:** استخدام Redis Cache مع Tagging مثل `Cache::remember("student_{$user->id}", 60, ...)` للاستفادة من الكاش عبر كافة الطلبات.

#### 2. مشكلة استعلامات N+1 في لوحات الإحصائيات (N+1 Query Issue):
- في [DashboardService.php](file:///home/madany/Projects/edu-platform/src/app/Services/DashboardService.php) و [ExamService.php](file:///home/madany/Projects/edu-platform/src/app/Services/ExamService.php)، عند جلب الامتحانات أو محاولات الطلاب، يتم تنفيذ استعلامات فرعية لـ `count()` و `scores` داخل حلقات تكرار بدون استدعاء `withCount()` أو `with()` مسبقاً.
- **تأثيرها:** مع زيادة عدد الطلاب إلى 10,000 طالب، سيهبط أداء لوحة التحكم ويتسبب في استهلاك الموارد وااختناق قاعدة البيانات PostgreSQL.

#### 3. عدم تنظيف الـ Tokens والـ Logs القديمة (Database Bloat):
- جدولي `personal_access_tokens` و `communication_logs` ينموان بسرعة فائقة مع كثرة عمليات تسجيل الدخول والإشعارات.
- **عدم وجود Pruning Task:** لا توجد مهمة مجدولة تنفذ `php artisan sanctum:prune-expired` بانتظام، مما تؤدي لبطء الاستعلامات على جدول المفاتيح الشخصية.

---

## 🔒 4. مراجعة الثغرات والمشاكل الأمنية (Production Security Audit)

بما أن المشروع يدار في بيئة **Production حقيقية**، فهناك عدة ثغرات ومخاطر عالية ومتوسطة الخطورة يجب إغلاقها فوراً:

### 🚨 ثغرات عالية الخطورة (High Severity):

#### 1. ربط المجلد المباشر في بيئة الإنتاج (`Production Docker Host Volume Mount`):
- في ملف [docker-compose.prod.yml](file:///home/madany/Projects/edu-platform/docker-compose.prod.yml#L26-L28):
  ```yaml
  app:
    volumes:
      - ./src:/var/www/html
  ```
- **الخطورة:** في بيئات Production، يجب ألا يتم عمل Mount للكود المصدري من المستضيف (Host Volume) إلى داخل الحاوية. هذا يكسر مبدأ الحاويات المغلقة (Immutable Containers) ويزيد من خطر تعديل الكود أو الخرق الأمنية عند الوصول للسيرفر، بالإضافة لضرب أداء الـ I/O.
- **الحل:** بناء الكود داخل الصورة أثناء `docker build` بدون ربط مجلد `./src` في الـ Production Compose.

#### 2. تشغيل Redis بدون كلمة مرور وفي شبكة المضيف (`Unauthenticated Redis`):
- في [docker-compose.prod.yml](file:///home/madany/Projects/edu-platform/docker-compose.prod.yml#L73-L80):
  ```yaml
  redis:
    image: redis:7-alpine
    container_name: lms_redis
  ```
- **الخطورة:** عدم تعيين `requirepass` لحاوية Redis يتيح لأي حاوية أو عملية أخرى داخل الشبكة الوصول إلى الكاش والـ Sessions وبيانات الطوابير وقراءتها أو تعديلها.

#### 3. كشف منافذ إدارة Nginx Proxy Manager عالي الخطورة (`Exposed Admin Ports`):
- منفذ الإدارة `81:81` في `proxy` متاح للخارج على `0.0.0.0:81`.
- **الخطورة:** أي شخص يعرف IP السيرفر يمكنه الوصول للوحة التحكم الخاصة بالـ SSL والـ Reverse Proxy ومحاولة تخمين كلمة المرور.
- **الحل:** ربطه بـ `127.0.0.1:81:81` أو إغلاقه بالجدار الناري WAF.

---

### ⚠️ ثغرات متوسطة ومنخفضة الخطورة (Medium / Low Severity):

#### 1. التحقق من امتدادات الملفات المرفوعة (File Upload MIME-Type Validation):
- عند رفع ملفات المحاضرات أو ملفات الواجبات، ينبغي التأكد من استخدام `mimetypes` في الـ Request Validation وليس مجرد الامتداد الظاهري `mimes:pdf,docx` لمنع رفع ملفات PHP/HTML خبيثة.

#### 2. مشاركة الحسابات ومنع الدخول المتعدد (Account Sharing / Single Device Session):
- حالياً يمكن للمستخدم تسجيل الدخول من أكثر من جهاز في نفس الوقت ومشاهدة الفيديو، مما يسمح للطلاب بمشاركة حساب واحد بين عدة أشخاص.
- **توصية:** تفعيل `broadcast_driver` أو ميزة `revoke_other_tokens` عند تسجيل الدخول من جهاز جديد لمنع استخدام الحساب من أكثر من طالب بنفس الوقت.

---

## 📂 5. شرح وتفصيل الكود والمجلدات خطوة بخطوة (Module Breakdown)

### 🔴 أولاً: الواجهة الخلفية - Backend (`/src`)

#### 📁 `src/app/Models` (النماذج وقواعد البيانات):
- [User.php](file:///home/madany/Projects/edu-platform/src/app/Models/User.php): نموذج المستخدم الأساسي (يدعم الأدوار عبر Spatie Permissions أو Enums).
- [Student.php](file:///home/madany/Projects/edu-platform/src/app/Models/Student.php): بيانات الطالب الأكاديمية (المركز التعليمي، السنة الدراسية، ولي الأمر، كود الـ QR).
- [Course.php](file:///home/madany/Projects/edu-platform/src/app/Models/Course.php) & [Lecture.php](file:///home/madany/Projects/edu-platform/src/app/Models/Lecture.php): إدارة الكورسات والمحاضرات والأقسام وحالة النشر.
- [Enrollment.php](file:///home/madany/Projects/edu-platform/src/app/Models/Enrollment.php) & [Entitlement.php](file:///home/madany/Projects/edu-platform/src/app/Models/Entitlement.php): إدارة اشتراكات الكورسات واستحقاقات المحاضرات المنفصلة وتاريخ انتهائها.
- [CenterExam.php](file:///home/madany/Projects/edu-platform/src/app/Models/CenterExam.php) & [Attendance.php](file:///home/madany/Projects/edu-platform/src/app/Models/Attendance.php): رصد حضور وغياب الطلاب بالسنتر والامتحانات الورقية.
- [Order.php](file:///home/madany/Projects/edu-platform/src/app/Models/Order.php): الفواتير والعمليات المالية والربط مع بوابات الدفع.

#### 📁 `src/app/Services` (طبقة منطق الأعمال):
- [AuthService.php](file:///home/madany/Projects/edu-platform/src/app/Services/AuthService.php): تسجيل الدخول، إنشاء الـ Tokens، وإدارة ملفات المستخدمين.
- [BunnyStreamService.php](file:///home/madany/Projects/edu-platform/src/app/Services/BunnyStreamService.php): التواصل المباشر مع Bunny.net API لإنشاء ورفع الفيديو وحساب التوقيعات.
- [VideoAccessService.php](file:///home/madany/Projects/edu-platform/src/app/Services/VideoAccessService.php): التحقق الحازم من أحقية الطالب في مشاهدة المحاضرة (الاشتراك، اجتياز الامتحانات المانعة Blocking Exams).
- [VideoTokenService.php](file:///home/madany/Projects/edu-platform/src/app/Services/VideoTokenService.php): تشفير وفك تشفير الـ HLS Single-Use Token الخاصة بمشغل الفيديو.
- [ExamService.php](file:///home/madany/Projects/edu-platform/src/app/Services/ExamService.php): تصحيح الامتحانات الأونلاين وحساب الدرجات والأوقات والنتيجة تلقائياً.
- [Payment/PaymentService.php](file:///home/madany/Projects/edu-platform/src/app/Services/Payment/PaymentService.php): معالجة عمليات الدفع والـ Webhooks بأسلوب منظم ومدعوم بـ Gateways.

#### 📁 `src/app/Http/Controllers/Api` (متحكمات الـ API):
- [AuthController.php](file:///home/madany/Projects/edu-platform/src/app/Http/Controllers/Api/AuthController.php): نقاط الانطلاق لـ Login, Register, Profile, Change Password.
- [CourseController.php](file:///home/madany/Projects/edu-platform/src/app/Http/Controllers/Api/CourseController.php): عرض الكورسات، التفاصيل، المحاضرات، وتشغيل أجزاء الميديا.
- [ExamController.php](file:///home/madany/Projects/edu-platform/src/app/Http/Controllers/Api/ExamController.php): بدء الامتحانات، تسليم الإجابات، واستعراض الإحصائيات.
- [PaymentWebhookController.php](file:///home/madany/Projects/edu-platform/src/app/Http/Controllers/Api/PaymentWebhookController.php): استقبال وتوثيق إشعارات الدفع الفورية من Paymob و Fawry.
- [CenterStaffController.php](file:///home/madany/Projects/edu-platform/src/app/Http/Controllers/Api/CenterStaffController.php): واجهات موظف السنتر لتسجيل الحضور، البحث عن الطلاب، ورصد الدرجات.

#### 📁 `src/app/Filament` (لوحة تحكم الإدارة):
- تحتوي على الـ Resources و Pages المخصصة لإدارة المستخدمين، الكورسات، المبيعات، الامتحانات، والإعدادات بسهولة وبواجهة رسومية تفاعلية.

---

### 🔵 ثانياً: الواجهة الأمامية - Frontend (`/frontend`)

#### 📁 `frontend/src/app` (مسارات وقواعد الصفحات - Next.js App Router):
- `(auth)/`: صفحات تسجيل الدخول، إنشاء حساب، واستعادة كلمة المرور.
- `(dashboard)/student/`: لوحة الطالب (المقررات المسجلة، الجدول، النتائج، الحضور بالسنتر).
- `(dashboard)/instructor/`: لوحة المدرس ومتابعة الأداء.
- `courses/[id]/`: صفحة تفاصيل الكورس وعرض المحاضرات.
- `watch/[lectureId]/`: صفحة مشغل الفيديو المحمي والتفاعل مع الأسئلة والملفات.
- `exams/[examId]/`: صفحة خوض الامتحان التفاعلي المؤقت.

#### 📁 `frontend/src/services` & `frontend/src/lib`:
- `api.ts`: إعداد مكتبة Axios مع Interceptors لإرفاق الـ Sanctum Token تلقائياً ومعالجة أخطاء `401 Unauthorized`.
- `auth.ts` / `course.ts` / `exam.ts`: دواء وأغلفة API لاستدعاء مخرجات الـ Backend بمرونة وبناء أنواع البيانات TypeScript Types.

---

## 🛠️ 6. التوصيات ومقترحات التحسين للإنتاج (Actionable Recommendations)

1. **إغلاق الثغرات الأمنية المباشرة:**
   - حذف الـ Volume Mount الخاصة بـ `./src` من ملف `docker-compose.prod.yml`.
   - إضافة `requirepass` لحاوية Redis وتعيين قيمة سرية لها في `.env`.
   - قصر منفذ Nginx Proxy Manager Admin Port `81` على الـ Localhost فقط `127.0.0.1:81:81`.
2. **تحسين أداء التوسع (Performance & Scalability Optimization):**
   - استبدال التخزين المؤقت المحلي بـ Redis Cache في [VideoAccessService.php](file:///home/madany/Projects/edu-platform/src/app/Services/VideoAccessService.php).
   - استخدام `with()` و `withCount()` في استعلامات الامتحانات والكورسات لمنع N+1 Query.
   - جدولة مهمة دورية تنظف الـ Sanctum Expired Tokens يومياً.
3. **تطبيق Single Device Session:**
   - إلغاء التوكينات القديمة عند تسجيل الدخول من متصفح جديد لمنع مشاركة الحسابات وتخسير المنصة اشتراكات مستحقة.
