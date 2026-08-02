# تحليل النظام الشامل — edu-platform

> **التاريخ:** 2026-08-02
> **الهدف:** تحليل كامل للنظام (Backend Laravel + Filament، Frontend Next.js، البنية التحتية) لتحديد المشاكل والثغرات والعيوب قبل إضافة:
> 1. **خدمات الدفع** (Gateway حقيقي).
> 2. **ميزة اختيار مكان المحاضرة عند رفعها**: محاضرة منفردة / داخل كورس / باقة / اشتراك.
>
> **الإجمالي:** 10 حرجة (Critical) · 24 عالية (High) · 20 متوسطة (Medium) · 15 منخفضة (Low)

> **تحديث 2026-08-02 (الجلسة الثانية):** تم تطبيق حزمة إصلاحات `cd7c78a` + ميلغريشنات `2026_08_02_*` غطّت معظم الثغرات الحرجة (انظر القسم 11)، وأُجريت مراجعة ثانية للواجهة الأمامية شملت صفحات المحتوى والأداء (القسم 12).

---

## 1) ملخص تنفيذي

النظام يملك أساسات جيدة: استخدام Sanctum، سياسات (Policies) منفصلة، فصل للخدمات، ونموذج Entitlement على مستوى المحاضرة (فكرة سليمة). لكنه **غير جاهز نهائيًا لإضافة الدفع**، ويحتوي على ثغرات تسمح بتسريب كل المحتوى المدفوع مجانًا.

أهم 5 مشاكل تمنع إضافة الدفع مباشرة:

| # | المشكلة | الخطورة | الملف |
|---|---------|---------|-------|
| 1 | إندبوينت `/enroll` و `/purchase` يمنحان اشتراكًا نشطًا **بدون دفع إطلاقًا** | Critical | `EnrollmentController.php:38-54` |
| 2 | صفحة الكورس العامة `GET /courses/{course}` **بدون Auth** تُرجع روابط بث شغّالة لكل المحاضرات | Critical | `routes/api.php:24` + `LectureResource.php:59-63` |
| 3 | إجابات الامتحان الصحيحة `is_correct` **متسربة للطالب قبل الامتحان** | Critical | `ExamController.php:25-31` + `Choice.php` |
| 4 | حذف Cascade للطلاب/الكورسات/الطلبات يدمر السجل المالي (لا يوجد Soft Deletes) | Critical | migrations + FKs |
| 5 | تسعير الفلوس بـ `float` و `intval($price*100)` (اقتطاع بدل تقريب) | High | `OrderController.php:56` + `Product.php:27` |

---

## 2) الثغرات الحرجة (Critical)

### 2.1 تجاوز الدفع كليًا — الاشتراك مجاني
- **الموقع:** `app/Http/Controllers/Api/EnrollmentController.php:38-54` · `app/Services/EnrollmentService.php:15-31` · `routes/api.php:84-85`
- **الوصف:** `POST /api/courses/{course}/enroll` و `POST /api/courses/{course}/purchase` يستدعيان `firstOrCreate` لإنشاء `Enrollment` بـ `status='active'` **دون** أي تحقق من: السعر، حالة الكورس (published)، تحقق الطالب (`is_verified`)، أو وجود Order مدفوع.
- **الاستغلال:** أي مستخدم مسجّل (حتى غير محقق) ينادي `purchase` → يحصل على وصول كامل لكل محاضرات الكورس المدفوعة والدورية.
- **الحل:** إلغاء هذين الإندبوينتين أو تقييدهما بالكورسات المجانية فقط (`price == 0` و `status == published`)، وجعل الشراء يمر عبر `Order → Gateway → GrantEntitlementService`.

### 2.2 تسريب روابط الفيديو من صفحة الكورس العامة
- **الموقع:** `routes/api.php:24` (`GET courses/{course}` عام) · `app/Http/Controllers/Api/CourseController.php:35-70` · `app/Http/Resources/CourseResource.php` · `app/Http/Resources/LectureResource.php:59-73`
- **الوصف:** الـ Show بلا Auth، و `LectureResource` يولّد `stream_url` (رابط Bunny Embed موقع + `bunny_video_id` + `video_path`) لأي فيديو `status='completed'`. كما أن `show` لا يفلتر الكورسات غير المنشورة.
- **الاستغلال:** `curl /api/courses/{id}` لأي زائر → يحصل على روابط بث شغّالة لكل الفيديوهات → مشاهدتها بدون دفع. كما يكشف محتوى دورات Draft.
- **الحل:** فصل Resource عام (بدون `stream_url`/`bunny_video_id`/`files`) عن Resource خاص يخضع لـ `CheckEnrollment`/`canAccess`.

### 2.3 تسريب إجابات الامتحان الصحيحة قبل الامتحان
- **الموقع:** `app/Http/Controllers/Api/ExamController.php:25-31, 48-51` · `app/Models/Choice.php` (لا يوجد `$hidden`) · `app/Services/ExamService.php:17-18`
- **الوصف:** `ExamController::show` يعيد الـ Exam كامل مع `questions.choices` شاملة `is_correct`. الـ API يُسلَّم للطلاب المسجلين (أي مَن سيؤدي الامتحان).
- **الاستغلال:** الطالب يفتح الامتحان → ينسخ UUIDs الاختيارات الصحيحة → نتيجة 100% مضمونة.
- **الحل:** إخفاء `is_correct` من أي رد يسبق التسليم، والتصحيح حصريًا على السيرفر.

### 2.4 حقن أسئلة من امتحان آخر لتزوير الدرجات
- **الموقع:** `app/Http/Controllers/Api/ExamController.php:147-151` · `app/Services/ExamService.php:152-203`
- **الوصف:** التحقق `exists:questions,id` فقط دون التأكد أن السؤال يخص امتحان الـ Attempt نفسه. `gradeAttempt` يحسب الدرجة من درجات الأسئلة المرسلة فعلًا.
- **الاستغلال:** إرسال أسئلة من امتحان أسهل (معرفة إجاباتها عبر 2.3) داخل أي Attempt → تزوير درجات أي امتحان حاجز.
- **الحل:** فرض `question_id ∈ exam.questions`، وحساب النسبة من إجمالي درجات الامتحان الحقيقي.

### 2.5 إعادة محاولات غير محدودة + بدون توقيت + إظهار الإجابات بعد كل محاولة
- **الموقع:** `app/Services/ExamService.php:134-203` · `ExamController.php:158-163`
- **الوصف:** (أ) لا حد أقصى للمحاولات (Attempt جديد بعد كل تسليم). (ب) لا يُفحص `duration` مقابل `started_at`. (ج) `result`/`submitAttempt` يعيدان `is_correct`.
- **الاستغلال:** حلقة: قدّم → اقرأ الإجابات → أعد المحاولة حتى 100%، وبالتالي فتح كل المحتوى المتسلسل.
- **الحل:** حد أقصى للمحاولات، تطبيق التوقيت على السيرفر، وعدم إظهار الإجابات الصحيحة أبدًا.

### 2.6 تسرب المفتاح السري للفيديو (DRM Key) من إندبوينت عام
- **الموقع:** `routes/api.php:139` (`GET /api/lectures/{lecture}/key` — عام، بدون throttle) · `CourseController.php:279-303` · `VideoAccessService.php:234-289`
- **الوصف:** الإندبوينت يعيد مفتاح AES-128 (`hex2bin(encryption_key)`) مقابل توكن يُشفَّر بـ `Crypt` (APP_KEY). لا يوجد إندبوينت يصدر هذه التوكنات حاليًا، لكن أي تسريب لـ APP_KEY يجعل التزوير ممكنًا.
- **الحل:** حماية الإندبوينت بـ `auth:sanctum` + rate limit، وإصدار التوكنات من إندبوينت سيرفر يتحقق من الوصول، وعدم تخزين مفاتيح التشفير كنص في DB.

### 2.7 حذف Cascade يدمّر السجل المالي (لا Soft Deletes)
- **الموقع:** `2026_07_10_000009_create_orders_table.php:13` (`orders.student_id` cascade) · `2026_07_10_133309_create_entitlements_table.php:15` (`entitlements.order_id` cascade) · `2026_01_01_000004:18` (`courses.instructor_id` cascade) · `2026_07_10_133307:13` و `133308:13` (منتجات/باقات instructor_id cascade)
- **الوصف:** حذف طالب/مدرب/طلب → حذف متسلسل لكل الطلبات، الانترولمنتس، الانتايتلمنتس، الامتحانات، إلخ. **لا يوجد أي SoftDeletes في أي جدول.**
- **الاستغلال/الأثر:** خسارة كاملة للتاريخ المالي والتدقيق؛ حذف Order يلغي وصول مدفوع (الانعكاس المعكوس للـ Refund: Refund لا يلغي الوصول، والحذف يلغي بدون استرداد).
- **الحل:** Soft deletes للكيانات المالية، و`restrictOnDelete`/`nullOnDelete` للـ FKs المالية.

### 2.8 `GET /courses/{course}` يكشف دورات Draft/غير منشورة
- **الموقع:** `routes/api.php:24` + `CourseController.php:35-70`
- **الوصف:** الـ `show` لا يفحص `status === 'published'` (فقط `index` يفلتر). أي UUID مسرَّب → محتوى غير مكتمل + روابط الفيديو (2.2).
- **الحل:** فرض النشر على `show` للمستخدمين غير المصرح لهم (المدرب/الأدمن يرون دوراته فقط).

### 2.9 `firstOrCreate` TOCTOU race على الإنترولمنت والكود
- **الموقع:** `EnrollmentService.php:17-24` · `CodeGeneratorService.php:40-53` · `ExamService.php:134-150`
- **الوصف:** طلبان متوازيان يمرّان من الـ SELECT ثم يفشلان في الـ INSERT (unique violation → 500). مولّد الأكواد (4 أرقام فقط لكل بادئة) بعد 100 محاولة يعيد كودًا **بدون فحص** وجود.
- **الحل:** `updateOrCreate`، ومعالجة `QueryException`، وتوسيع مساحة الأكواد أو use sequences.

### 2.10 تأكيد الطلب غير ذرّي: `completed` قبل منح الصلاحيات
- **الموقع:** `app/Filament/Resources/Orders/OrderResource.php:110-117` + `app/Services/GrantEntitlementService.php:23-35`
- **الوصف:** تحديث `status='completed'` يتم أولًا، ثم منح Entitlements في **معاملة منفصلة**؛ لو فشل المنح → الطالب مدفوع ومقفول.
- **الحل:** منح الصلاحيات داخل نفس المعاملة أو إعادة محاولة + معالجة فشل، ووضع التحقق من الكمية عند التأكيد.

---

## 3) الثغرات العالية (High)

### 3.1 الفلوس تُحسب بـ float ويتم اقتطاعها
- **الموقع:** `OrderController.php:56` (`intval($price*100)`) · `Product.php:27` (cast `float`) · `Bundle.php` (بلا cast) · `Course.php:105` (`decimal:2`)
- **المشكلة:** `intval()` يقتطع لا يقرّب (مثال مُتحقق: `0.29 → 28`، `0.58 → 57`). أنواع متناقضة (float/string/decimal/int) لنفس المال.
- **الحل:** تخزين سنتات صحيحة `(int) round($price * 100)` أو `bcmul`، وتوحيد الـ casts.

### 2.3 (مكرر للتحديد) نموذج الـ Order بدائي
- **الموقع:** `Order.php:14-24` · `2026_07_10_133310_alter_orders_table_for_pricing_engine.php` · `OrderStatus.php`
- **النواقص:** لا `order_items`/line items، لا `payment_gateway`، لا `checkout_id`/`payment_url`، لا `gateway_reference`، لا `metadata` JSON، لا `failure_reason`، لا `refunded_at`/`amount_refunded`، لا idempotency (تكرار POST ينشئ طلبات مكررة)، لا تاريخ حالات.
- **الحل:** قبل أي Gateway: إضافة هذه الحقول + حقل idempotency + فهرس unique للطلبات المعلّقة.

### 3.3 `Enrollment.expires_at` لا يُطبَّق في فحوص الوصول
- **الموقع:** `VideoAccessService.php:72-82` (`isEnrolled` لا يفحص `expires_at`) · `CheckEnrollment.php:56-71` · `ExamController.php:121-124`
- **الاستغلال:** اشتراك/دورة محدودة المدة لا تنتهي فعلًا.

### 3.4 الـ Refund لا يلغي الوصول (وحالة `Refunded` ميتة)
- **الموقع:** `OrderStatus.php:10` — لا يوجد كود يستخدم `Refunded`، ولا مسار Revoke عند الاسترداد. بينما حذف الـ Order يلغي الوصول (2.7). السلوك معكوس وغير متناسق.
- **الحل:** مسار Refund يلغي Entitlements/Enrollment، وحماية الطلبات المكتملة من الحذف.

### 3.5 لا يمكن بيع محاضرة منفردة بنيويًا
- **الموقع:** `2026_01_01_000004:32` (`lectures.section_id` NOT NULL) · `LectureResource.php:56-64`
- **الوصف:** كل محاضرة مرتبطة إجباريًا بقسم في كورس. "المنفردة" حاليًا تعني Product مع `sellable=lecture` لكنها تبقى داخل كورس → أي طالب منضم للكورس يصل إليها مجانًا، واحتمال بيع مزدوج (كورس + محاضرة منفردة).
- **الحل:** جعل `section_id` nullable أو نموذج محتوى منفصل، مع منع تداخل الحزم (كورس يحتوي محاضرة تُباع منفردة).

### 3.6 لا يوجد مفهوم اشتراكات إطلاقًا
- **الموقع:** البحث في كل migrations عن `plan`/`subscription` → لا شيء.
- **النواقص:** لا `plans`، لا `plan_subscriptions`، لا `billing_periods`، لا `renewals`، لا `EnrollmentSource::Subscription`، لا `subscription` access check في `VideoAccessService`.
- **الحل:** قبل ادعاء ميزة "محاضرة في اشتراك": بناء نموذج خطط + اشتراكات + تجديد + إلغاء.

### 3.7 `GrantEntitlementService` يمنح صفر صلاحيات بصمت
- **الموقع:** `app/Services/GrantEntitlementService.php:38-59` · `Product.php:36-44`
- **الوصف:** لو `resolveLectureIds()` عاد فارغًا (sellable محذوف/خاطئ/كورس فارغ) → Order مكتمل بلا منح شيء، بسطر Log فقط. ومبيعات منتجات لكورسات محذوفة تبقى `is_active=true`.
- **الحل:** رفض إكمال الطلب إذا لم تُحل أي محاضرة، وفحص سلامة sellable.

### 3.8 إجراء "منح صلاحية" في الأدمن مكسور (NOT NULL violation)
- **الموقع:** `StudentResource.php:361-376` + `2026_07_10_133309_create_entitlements_table.php:15`
- **الوصف:** `Entitlement::updateOrCreate(...)` بدون `order_id` بينما العمود NOT NULL → استثناء DB عند أي منح يدوي.
- **الحل:** جعل `order_id` nullable أو إنشاء Order صفري.

### 3.9 `instructorStudents` يسرّب PII كامل لكل الطلاب
- **الموقع:** `DashboardController.php:75-85` (موديلات خام بدون Resource)
- **الوصف:** يكشف `phone`، `father_phone`، `mother_phone`، `guardian_job`، `birth_date`، `gender`، `profile_image`، `is_verified` + `user.email/phone/status` لبيانات قُصّر. بدون pagination.
- **الحل:** Resource يعرض الحد الأدنى الضروري + Pagination.

### 3.10 `ExamPolicy::update/delete` يسمح لأي مدرب عندما `lecture_id` فارغ
- **الموقع:** `app/Policies/ExamPolicy.php:37-51`
- **الاستغلال:** امتحانات غير مرتبطة بمحاضرة يمكن تعديلها/حذفها من أي مدرب.
- **الحل:** إلزام ملكية الدورة أو إلغاء العمليات على الامتحانات اليتيمة.

### 3.11 `startAttempt` لا يطبّق الحظر التسلسلي للمحتوى
- **الموقع:** `ExamController.php:105-141` — لا `isBlockedByExam` هنا (موجود فقط في `CheckEnrollment` على الـ Lectures).
- **الاستغلال:** بدء أي امتحان حاجز خارج الترتيب وفتح محتوى لاحق.
- **الحل:** استدعاء `isBlockedByExam` قبل `startAttempt`.

### 3.12 التقدم يُزوَّر: `is_completed=true` فوريًا بدون مشاهدة
- **الموقع:** `UpdateProgressRequest.php:14-20` (لا حد أقصى لـ `current_time`) · `ProgressService.php:39-51`
- **الاستغلال:** POST `{current_time: 0, is_completed: true}` → شارات إتمام وإحصائيات وهمية.
- **الحل:** تحقق سيرفر من نسبة المشاهدة (progress ≥ X%)، وحد أقصى لـ current_time.

### 3.13 روابط CDN المباشرة مكشوفة (التوكين Auth غير مفعّل على Bunny)
- **الموقع:** `VideoStreamController.php:30-34, 82-88` (fetch بدون token/expires) · `LectureResource.php:50` (`bunny_video_id`) · `BunnyStreamService.php:160-172` (يكشف `library_id`)
- **الوصف:** لو تفعيل token auth عند مستوى المكتبة فسيفشل الـ proxy (لذا إما معطّل = رابط CDN عام، أو الـ proxy مكسور). الـ client يملك `library_id` + `bunny_video_id` = يكفي لبناء `https://{libraryId}.b-cdn.net/{videoId}/playlist.m3u8`.
- **الحل:** تفعيل Token Authentication في Bunny وإلحاق `token`+`expires` لكل fetch، وعدم إرجاع `bunny_video_id` للعموم.

### 3.14 الـ HLS proxy لا يتحقق من الوصول أثناء البث (إلغاء الاشتراك لا يوقف)
- **الموقع:** `VideoTokenService.php:37-68` (`validateVideoToken` يفحص التوقيع فقط) · `VideoStreamController.php:24,71` · `routes/api.php:29-34`
- **الوصف:** التوكن (4 ساعات) قابل للمشاركة، غير مربوط بـ IP، ولا يُعاد التحقق من `canAccess` وقت الطلب. الإلغاء/الاسترداد لا أثر له حتى انتهاء التوكن.
- **الحل:** إعادة التحقق من المستخدم/الصلاحية عند كل طلب، وربط التوكن بالمستخدم، ومدة أقصر.

### 3.15 إعدادات الدفع في Filament تكتب إلى `.env` لا أحد يقرأها
- **الموقع:** `Settings.php:45-46, 132-133` (يكتب `PAYMOB_*`) + `config/services.php` (لا يسجلها) + `.env:91-92` (فارغة)
- **الوصف:** مفاتيح Paymob تُكتب لملف `.env` عبر واجهة الأدمن لكن لا يوجد `config('services.paymob_*')` يستهلكها — ضياع صامت للاعتماديات.
- **الحل:** تسجيل مفاتيح الدفع في `config/services.php` وتعديل Settings ليقرأ/يكتب بشكل صحيح.

### 3.16 لا Webhook لبوابة الدفع إطلاقًا
- **الوصف:** لا مسارات webhook في `routes/api.php`، لا Controllers، لا تحقق توقيع (HMAC)، لا معالجة أحداث (paid/refunded/failed). كل تأكيد يتم يدويًا من لوحة Filament.
- **الحل:** إضافة `webhooks/paymob` + تحقق توقيع + معالجة آمنة (لا تعتمد أبدًا على `transaction_id` مرسل من العميل).

### 3.17 إحصائيات الإيراد متناقضة (مصدران متعارضان)
- **الموقع:** `DashboardService.php:23` (مجموع `courses.price`) مقابل `InstructorStatsOverview.php:38-51` (مجموع `orders.amount_cents`)
- **الاستغلال:** أرقام إيراد مختلفة للمدرب في لوحتين.
- **الحل:** مصدر حقيقة واحد = `orders.amount_cents` حيث `status=completed`، وحذف `courses.price` من الإحصائيات.

### 3.18 التوكن في query string لكل طلبات HLS + Segment يُخزن 1 ساعة
- **الموقع:** `VideoStreamController.php:143` (`?token=...&file=`) · `:115` (`max-age=3600`)
- **الأثر:** تسريب التوكنات عبر السجلات/التاريخ/الـ referrers، وإعادة تشغيل مقاطع مؤرشفة حتى بعد انتهاء التوكن.
- **الحل:** توكنات أقصر عمرًا + منع الكاش على المقاطع المحمية.

### 3.19 نقص فهارس على Postgres (FKs شائعة)
- **الموقع:** `students.user_id`، `orders.student_id`، `exams.lecture_id`، `questions_posts.lecture_id/.student_id`، `choices.question_id`، `notifications.user_id`، `question_replies.*`، `student_statistics.student_id` (ميلغريشن `2026_07_17_235012` غطى البعض فقط).
- **الأثر:** مسح متسلسل على كل فحص وصول/طلب/امتحان — يتباطأ خطيًا مع النمو.

### 3.20 `intval` عند تحويل العملة في `Bundle` بلا cast
- سبق تغطيته في 3.1 (الحالة الأسوأ للـ Bundle لأنه بلا cast أصلاً).

---

## 4) المشاكل المتوسطة (Medium)

| # | الموقع | المشكلة |
|---|--------|---------|
| 4.1 | `PasswordResetController.php:31-35` | Email enumeration: 200 عند الوجود، 404 عند الغياب. |
| 4.2 | `PasswordResetController.php:22-27` | توكن استعادة كلمة المرور مخزّن **نصًّا** في جدول notifications (بدل إيميل)، والتدفق مكسور وظيفيًا (الطالب الذي نسي كلمة المرور لا يستطيع الدخول لقراءة الإشعار). |
| 4.3 | `ExamService.php:174-203` | نسبة النجاح قابلة للتضخيم: تخطّي الأسئلة الصعبة/الأسئلة المقالية يرفع النسبة (totalPoints = درجات ما أُجيب فقط). الأسئلة المقالية لا تُدرج عبر API أصلاً (`min:2` choices). |
| 4.4 | `ProductController.php:47-71` | `show`/`showBundle` يعيدان الـ sellable خامًا: تسريب `video_path`، `pdf_url`، `instructor_id`. |
| 4.5 | `QuestionResource.php:15-19` | يعيد `student_code` لكل كتّاب الأسئلة — `student_code` يُستخدم كمعرّف دخول. |
| 4.6 | `VideoStreamController.php:83` | `videoId` غير مُنقّى قبل بناء URL → SSRF/open-proxy محتمل تجاه الـ CDN لو سُرق/زُوّر توكن. |
| 4.7 | `CheckEnrollment.php:23-33, 56-71` | لا يفحص أن الكورس Published/Active → وصول للمحتوى المسحوب. |
| 4.8 | `OrderController.php:42-61` | لا فحص `is_active` للمنتج/الباقة؛ شراء مكرر/منتجات مسحوبة مسموح؛ لا فحص "يملك بالفعل". |
| 4.9 | `CourseController.php:234-258` | `downloadFile` بدون قائمة MIME/امتدادات مسموحة (يُقدم أي نوع ملف من الـ app origin). |
| 4.10 | `VideoAccessService.php:266-268` | ربط التوكن بـ IP ينكسر خلف Reverse Proxy (لا trusted proxies في `bootstrap/app.php`). |
| 4.11 | `VideoStreamController` | Rate limit لكل IP فقط؛ إندبوينت `/key` بلا throttle؛ الـ proxy مكبّر للـ egress bandwidth (server-side fetch لكل طلب). |
| 4.12 | `OrderController.php:19,42` | التحقق يقبل `product|bundle` لكنه يخزّن FQCN — عقد API متناقض. |
| 4.13 | `routes/api.php:117` | لا `GET /orders` للطالب (لا يمكنه رؤية طلباته)؛ الرد يعيد الطلب خامًا (يتضمن `transaction_id` ملفّق). |
| 4.14 | `2026_07_10_133309` | لا unique constraint على `(student_id, lecture_id)` في Entitlements → صفوف مكررة وإلغاء غامض. |
| 4.15 | `2026_07_10_000009` | `orders.transaction_id` ليس unique ولا idempotency key → طلبات مكررة وازدواج في الدفع. |
| 4.16 | `Enrollment.php:39-45` + `EnrollmentService.php:74-103` | Hack الصفوف الوهمية `entitlement-fake-*` لعرض الكورسات المدفوعة في "دوراتي" — هشّ وسينكسر مع الاشتراكات. |
| 4.17 | `EnrollmentService.php:33-38` + `17-24` | إعادة الاشتراك بعد الإلغاء (suspended) لا تعيد التفعيل (firstOrCreate يعيد الصف القديم). |
| 4.18 | `getStudentEntitlements` | يعيد Entitlements منتهية بدون فلترة `expires_at` (تظهر كأنها مملوكة). |
| 4.19 | `Student.php` | لا `orders()` relation — صعوبة جلب تاريخ الطلبات. |
| 4.20 | `InstructorStatsOverview.php:45-51` | إيراد الباقات يُنسب كاملًا لمالك الباقة حتى لو كانت منتجاتها لمدرسين آخرين؛ جلب كل الطلبات وتفلترتها في PHP. |

---

## 5) المشاكل المنخفضة (Low)

| # | الموقع | المشكلة |
|---|--------|---------|
| 5.1 | `TurnstileRule.php:20-24` | CAPTCHA يُتخطى في non-production عند غياب المفتاح. |
| 5.2 | `AuthService.php:19-67` | لا تفعيل إيميل (email_verified_at أبدًا لا يُضبط) — يعتمد على الموافقة اليدوية فقط. |
| 5.3 | `AuthController.php:70-77` | `me` يعيد Student كامل (PII خاصة) — مقبول لكن يستحق Resource. |
| 5.4 | `CourseResource.php:27` | `students_count` عام على صفحة الكورس. |
| 5.5 | `VideoTokenService.php:7-13` | HMAC secret مشتق من APP_KEY — لو APP_KEY فارغ/ضعيف/مسرَّب فكل التوكنات قابلة للتزوير. |
| 5.6 | `.env` (0666) | ملف البيئة World-writable ويحتوي APP_KEY + مفاتيح Bunny + Backblaze. |
| 5.7 | `2026_07_17_234903` | ميلغريشن الإسقاط `down()` فارغ → ارتداد غير متسق. |
| 5.8 | `2026_07_17_235111` | CHECK constraint على `pass_percentage` PostgreSQL-only. |
| 5.9 | `answers.score` | عمود غير مستخدم وغير fillable — التصحيح اليدوي للمقالي ميت. |
| 5.10 | `users.status`/`courses.status`/`orders.status`/`products.is_active` | لا فهارس على أعمدة التصفية. |
| 5.11 | `sessions.user_id` | فهرس بدون FK. |
| 5.12 | `exams (lecture_id, is_assignment)` | لا unique → امتحان عشوائي يُقدَّم. |
| 5.13 | `DemoSeeder.php:529-539` | يزرع enrollments من نوع purchase بدون orders/entitlements — يضلل تطوير تدفق الدفع. |
| 5.14 | `frontend: query-provider.tsx:24` | React Query DevTools مضمّن في الإنتاج. |
| 5.15 | `frontend: video-player.tsx:191-195` | `xhrSetup` يرسل Bearer token الكامل لأي host للـ HLS (حتى لو CDN خارجي) — مخاطرة تسريب التوكن. |

---

## 6) الواجهة الأمامية (Next.js)

### عالية
- **H1 — التوكن في localStorage:** `src/services/api.client.ts:18` + `src/providers/auth-provider.tsx` — أي XSS يسرق الجلسة الكاملة. لا httpOnly cookie، لا middleware (`src/middleware.ts` غير موجود).
- **H2 — إجابات الامتحان في العميل:** `src/types/exam.types.ts:24` (`is_correct`) مستخدم في `quiz-tab.tsx:306,334,342` و `exam/page.tsx:151,161` — يُظهر الإجابات الصحيحة قبل الإجابة (مرتبط بالثغرة 2.3).

### متوسطة
- **M1 — لا CSP/security headers:** `next.config.ts:3-8` فارغ من `headers()`.
- **M3 — لا Auth على مجموعة `(player)`:** الحماية Client-side فقط (`router.push("/login")`). يُصحح فقط لأن الباك اند يفرض الأمان.
- **M4 — توكن استعادة كلمة المرور في الـ URL query:** `reset-password/page.tsx:13-15`.
- **M5 — أنواع عامة تحمل `bunny_video_id`/`stream_url`/`file_path`:** `src/types/course.types.ts:62-75` — يُطبع البنية الداخلية في الـ bundle العام.
- **M6 — البث يعتمد على `stream_url` + Bearer:** لا Media token مخصص قصير العمر.

### منخفضة
- **L1 — DevTools في الإنتاج** (مذكور 5.14). **L2 — مفتاح Turnstile هو مفتاح اختبار Cloudflare "دائم النجاح"** `1x00000000000000000000AA`. **L3 — `http://localhost:8000/api` افتراضيًا (HTTP)**. **L4 — إعادة توجيه 401 تفقد `redirect`**. **L5 — حماية الفيديو client-side شكلية.**

---

## 7) البنية التحتية (Docker / Nginx / الإعدادات)

### عالية
- **I1 — `.dockerignore` لا يستبعد `.env`:** `Dockerfile:55` (`COPY src/ .`) يدمج `.env` كامل (APP_KEY، DB password، Backblaze، Bunny، MinIO) في طبقات الصورة. **الإصلاح:** إضافة `src/.env` و`*.env*` إلى `.dockerignore`.
- **I2 — `APP_DEBUG=true`** في `.env` المركّب في الـ compose (حاويات app/queue/scheduler) — أي نشر غير محلي يكشف stack traces + قيم البيئة.
- **I3 — مفاتيح Bunny/Backblaze حقيقية في `.env` نصًّا** — تُدمج في الصورة (I1). **الإصلاح:** تدويرها لو خرجت الصورة.
- **I4 — `POSTGRES_PASSWORD=secret`** (افتراضي) + `compose` يعرض 5432.
- **I5 — MinIO S3 API `9000:9000` على كل الواجهات** بصلاحيات افتراضية `lms_minio_admin/lms_minio_secret` (المخزّنة أيضًا hard-coded في `config/filesystems.php:61-71`).
- **I6 — Mailpit `8025`/`1025` على كل الواجهات بلا Auth** — قراءة كل رسائل استعادة كلمة المرور.

### متوسطة
- **I7 — Nginx بلا TLS** (`listen 80`) والـ compose يعرض `8000:80`.
- **I8 — لا `SESSION_SECURE_COOKIE`** (غير مضبوط في `.env`).
- **I9 — Filament على `/admin` الافتراضي** بلا 2FA/Turnstile/IP allowlist، ويتجاوز `throttle:login`.
- **I10 — Seeders تُنشئ حسابات admin/مدرسين/طلاب بكلمة `password`** وتُشغَّل من `DatabaseSeeder.php` → خطر لو نُفذت على إنتاج.
- **I11 — CORS:** `allowed_methods`/`allowed_headers = ['*']` + `supports_credentials=true`.
- **I12 — Sanctum expiration 30 يوم** (`config/sanctum.php:53`).
- **I13 — حاويات PHP تعمل كـ root** (لا `USER` في الصورة).
- **I14 — compose queue/scheduler يستدعي `php artisan.php`** بينما الملف هو `artisan` → الوظائف المجدولة (انتهاء الصلاحيات/تجديد الاشتراكات) لا تعمل أبدًا.

### منخفضة
- **I15 — `LOG_LEVEL=debug`.** **I16 — لا headers أمان على nginx للاستاتيك.** **I17 — CSP فيه `unsafe-inline 'unsafe-eval'` و `connect-src https:`.** **I18 — جلسات ملفية (file driver).** **I19 — لا secret-scanning في CI.** **I20 — `client_max_body_size 2G`.** **I21 — Redis بلا password.**

---

## 8) خارطة الطريق: قبل إضافة الدفع

### المرحلة 1 — إغلاق الثغرات الحرجة (أمان + نزاهة امتحانات)
1. إلغاء/تقييد `enroll` و `purchase` — الشراء يمر عبر `Order → Gateway → Grant`. (2.1)
2. إخفاء روابط البث (`stream_url`، `bunny_video_id`، `files`) من الصفحة العامة؛ فرض النشر. (2.2، 2.8)
3. إخفاء `is_correct` قبل/أثناء الامتحان + تصحيح سيرفر فقط. (2.3)
4. ربط الأسئلة بامتحان الـ Attempt + حد المحاولات + تطبيق التوقيت. (2.4، 2.5)
5. إغلاق إندبوينت `/key` والتحقق من الوصول لحظيًا في الـ proxy. (2.6، 3.14)
6. Soft deletes + `restrictOnDelete` للكيانات المالية. (2.7)

### المرحلة 2 — تثبيت النموذج المالي
7. طلبات: إضافة `payment_gateway`، `checkout_id`/`payment_url`، `metadata`، idempotency، `order_items` (أو على الأقل snapshots)، فهرس `(student_id, status)`. (3.2، 4.15)
8. توحيد المال: سنتات صحيحة/`round`، إزالة cast الـ float، إضافة cast للـ Bundle. (3.1)
9. `entitlements.order_id` nullable + إصلاح منح الأدمن اليدوي. (3.8)
10. تفعيل `expires_at` في كل فحوص الوصول + مسار Refund يلغي الوصول. (3.3، 3.4)
11. الوصول الفوري: إما إنشاء Enrollment من الطلب المدفوع أو إزالة Hack الصفوف الوهمية. (4.16)
12. فحص `is_active`/ملكية في `OrderController::store`. (4.8)
13. توحيد الإيراد على `orders.amount_cents` حيث completed. (3.17)

### المرحلة 3 — البنية التحتية قبل Gateway
14. `.dockerignore` يستبعد `.env` + تدوير المفاتيح. (I1، I3)
15. بيئة إنتاج: `APP_DEBUG=false`، كلمات مرور قوية، `SESSION_SECURE_COOKIE=true`. (I2، I4، I8)
16. ربط MinIO/Mailpit بـ 127.0.0.1 + TLS في nginx + إزالة `/admin` الافتراضي أو حمايته. (I5-I9)
17. إصلاح `artisan.php` في compose + إضافة secret-scanning في CI. (I14، I19)
18. إصلاح هندسة: trusted proxies، Rate limit مناسب، جلسات Redis، فهارس Postgres. (4.10، 4.11، 3.19)

### المرحلة 4 — ثم أضف الدفع
19. حزمة Gateway (Paymob/Stripe) + `config/services.php` + Webhook بتحقق توقيع. (3.15، 3.16)
20. تدفق: `POST /orders` (idempotent) → إنشاء Checkout → Webhook → تحديث Order → `GrantEntitlementService` في معاملة واحدة. (2.10)

---

## 9) المتطلبات لتحقيق ميزة "اختر مكان المحاضرة عند الرفع"

الميزة المطلوبة: عند رفع محاضرة يستطيع المدرّس اختيار أنها: **منفردة / جزء من كورس / جزء من باقة / جزء من اشتراك.**

ما هو موجود فعلاً:
- ✅ المحاضرة يمكن أن تكون "منفردة" أو "في كورس" أو "في باقة" على مستوى **البيع** عبر `Product` (polymorphic على Lecture/CourseSection/Course) + `Bundle` (bundle_products). لكنه قرار Admin لاحق، وليس قرار رفع.

ما هو ناقص/مطلوب بنائياً:
1. **`lectures.section_id` nullable** (أو نموذج "Lecture Standalone") — المحاضرة المنفردة ليست مجبورة على كورس. (3.5)
2. **قاعدة منع التداخل**: لا يمكن بيع محاضرة في كورس وفي نفس الوقت بيعها منفردة (إلا بسياسة صريحة) — لمنع البيع المزدوج.
3. **اختيار عند الرفع**: نموذج `LectureResource` يحتاج قسم اختيار: `Standalone → سعر + أداة بيع`، أو `Course → اختيار قسم`، أو `Bundle → اختيار/إنشاء باقة`، أو `Subscription → اختيار خطة`. هذه الاختيارات يجب أن تنشئ/تربط `Product`/`Bundle` تلقائيًا.
4. **نموذج اشتراكات كامل**: `plans` + `plan_subscriptions` + بيلنج دوري + `EnrollmentSource::Subscription` + `canAccess` يفحص الاشتراك النشط. (3.6)
5. **`is_free` حقيقي**: الحقل يُقبل في `SaveLectureRequest` لكن لا عمود `lectures.is_free` ولا يُطبَّق في الوصول. (فحص 5)
6. **`Enrollment` منفصل لكل "حاوية"**: الاشتراك يجب أن يمنح وصولًا لكل محاضرات خطته (منفردة أو داخل كورسات) مع احترام `expires_at` لحظيًا.

---

## 10) ملخص الجدول المفضل للبدء (Top Priority)

| الأولوية | الملف | الإصلاح |
|----------|-------|---------|
| P0 | `EnrollmentController.php:38-54` | إغلاق التجاوز المالي |
| P0 | `LectureResource.php:59-73` + `routes/api.php:24` | إخفاء روابط البث العامة |
| P0 | `ExamController.php` + `Choice.php` | إخفاء `is_correct` + تصحيح سيرفر |
| P0 | migrations | Soft deletes + restrict FKs |
| P1 | `OrderController.php:56` + `Product.php:27` | سنتات صحيحة |
| P1 | `Order.php` + migration | حقل idempotency + gateway metadata |
| P1 | `VideoAccessService.php` + `CheckEnrollment.php` | تفعيل `expires_at` + فحص Published |
| P2 | `.dockerignore` + `.env` | عزل الأسرار + تدوير المفاتيح |
| P2 | `compose`/nginx | TLS + إغلاق المنافذ + APP_DEBUG=false |

---

## 11) مراجعة الإصلاحات المطبقة (commit `cd7c78a` + ميلغريشنات `2026_08_02_*`)

تمت مراجعة التغييرات الملتزمة حديثًا (Soft Deletes + RefundService + تحصين واجهات الوصول) وتبيّن أنها سليمة فعليًا:

| بند التقرير الأصلي | الحالة | ماذا طُبّق |
|---|---|---|
| 2.1 تجاوز الدفع (`enroll`/`purchase`) | ✅ مُصلَح | `enroll` يتطلب طالبًا محقّقًا + كورس Published + مجاني؛ `purchase` يُفوَّض إلى `enroll`. |
| 2.2 تسريب روابط البث العامة | ✅ مُصلَح | `LectureResource` لا يُرسل `video_path`/`bunny_video_id`/`stream_url` إلا إذا `canAccess` (للزائر `false`)، و`files` مشروطة بالوصول. |
| 2.3 تسريب `is_correct` قبل الامتحان | ✅ مُصلَح | `Choice::$hidden=['is_correct']`؛ لا يُكشف إلا في `result()` عبر `makeVisible`. |
| 2.4 حقن أسئلة من امتحان آخر | ✅ مُصلَح | `Rule::exists(...)->where('exam_id', $attempt->exam_id)` + تصحيح من درجات امتحان الـ Attempt الحقيقي. |
| 2.5 محاولات غير محدودة + بلا توقيت | ✅ مُصلَح | حد أقصى `max_attempts` (افتراضي 3) + انتهاء المدة يعيد تسليم الصفر تلقائيًا + رفض التسليم بعد الوقت. |
| 2.6 إندبوينت `/key` العام | ❌ لم يُصلَح | `routes/api.php:141` ما زال عامًا بلا throttle/auth. |
| 2.7 حذف Cascade يدمر السجل المالي | ✅ مُصلَح | SoftDeletes على `orders`/`enrollments`/`entitlements`/`students` (`000001`). |
| 2.8 كشف دورات Draft | ✅ مُصلَح | `CourseController::show` → 404 لغير المنشور إلا للمالك/سوبر أدمن. |
| 2.9 TOCTOU `firstOrCreate` | ⚠️ جزئيًا | `EnrollmentService` → `updateOrCreate`؛ لكن `ExamService::startAttempt` ومولّد الأكواد ما زالا بدون معالجة Race. |
| 2.10 تأكيد الطلب غير ذرّي | ❌ لم يُصلَح | تأكيد Filament ما زال: تحديث `completed` ثم منح في معاملة منفصلة. |
| 3.1 فلوس بـ float + `intval` | ✅ مُصلَح | `(int) round($price*100)` + casts `decimal:2` للمنتج والباقة. |
| 3.2 نموذج Order بدائي | ✅ مُصلَح | ميلغريشن `000002`: `payment_gateway`/`checkout_id`/`payment_url`/`gateway_reference`/`metadata`/`failure_reason`/`refunded_at`/`amount_refunded_cents`/`idempotency_key`(unique) + فهرس `(student_id,status)`. |
| 3.3 `expires_at` لا يُطبَّق | ✅ مُصلَح | فلاتر `expires_at` في `CheckEnrollment`/`VideoAccessService`/`EnrollmentService`/`getStudentEntitlements`. |
| 3.4 Refund لا يلغي الوصول | ✅ مُصلَح | `RefundService` يضبط `Refunded` + يحذف Entitlements المرتبطة بالطلب. |
| 3.5 لا يمكن بيع محاضرة منفردة | ✅ مُصلَح بنيويًا | ميلغريشن `100001`: `section_id` nullable + `instructor_id`/`status`/`price`/`thumbnail` على `lectures`. |
| 3.7 منح صفر صلاحيات بصمت | ✅ مُصلَح | `GrantEntitlementService` يرمي استثناء إذا لم تُحل أي محاضرة. |
| 3.8 منح الأدمن اليدوي مكسور | ✅ مُصلَح | `entitlements.order_id` أصبح nullable (`000003`). |
| 3.10 ExamPolicy على امتحان يتيم | ✅ مُصلَح | `update`/`delete` يعيدان `false` عند غياب `lecture`. |
| 3.11 `startAttempt` يتجاوز الحجب التسلسلي | ✅ مُصلَح | فحص `isBlockedByExam` قبل بدء المحاولة. |
| 3.12 تزوير التقدم | ✅ مُصلَح | تقييد `current_time` بمدة الفيديو + منع الإكمال تحت 80%. |
| 3.17 تضارب إحصائيات الإيراد | ✅ مُصلَح | `DashboardService` يحسب من `orders.amount_cents` (completed) بدل مجموع أسعار الكورسات. |
| 3.19 فهارس Postgres الناقصة | ✅ مُصلَح (معظمها) | ميلغريشن `000004` غطّى الفهارس المطلوبة + unique على `(lecture_id, is_assignment)`. |
| 4.8 طلبات بلا فحص `is_active` | ✅ مُصلَح | `OrderController::store` يفحص `is_active` + يرفض تكرار طلب معلق. |
| 4.14 تكرار Entitlements | ✅ مُصلَح | unique على `(student_id, lecture_id)` (`000003`). |
| 4.15 طلبات مكررة / بلا idempotency | ✅ مُصلَح | `idempotency_key` unique + فحص الطلب المعلّق المكرر. |
| 4.18 Entitlements منتهية تظهر مملوكة | ✅ مُصلَح | فلترة `expires_at` في `getStudentEntitlements`. |
| 5.14 DevTools في الإنتاج | ✅ مُصلَح | `query-provider.tsx` يقيّده بـ `NODE_ENV === "development"`. |
| 5.15 إرسال Bearer لأي HLS host | ✅ مُصلَح | `video-player.tsx` يرسل التوكن فقط لطلبات الـ API المحلية. |

**لم يُصلَح بعد:** 2.6، 2.10، 4.3 (تضخيم نسبة المقالية)، 4.4 (تسريب `ProductController::show` للـ sellable الخام)، 4.5-4.7، 4.9-4.13، 4.16 (Hack `entitlement-fake-*`)، 4.17، 4.19-4.20، 5.1-5.13، وكل بنود البنية التحتية (القسم 7).

---

## 12) مراجعة الواجهة الأمامية الثانية — صفحات المحتوى + السرعة (2026-08-02)

### 12.1 صفحات المحتوى المطلوبة — تأكيد الوجود
| الصفحة | الملف | الحالة |
|---|---|---|
| صفحة محتويات الكورس (عامة) | `frontend/src/app/(main)/courses/[id]/page.tsx` | ✅ موجودة: أقسام/محاضرات، حالات مسجّل/مفتوح/مقفول، تبويبات (محاضرات/أقسام/باقات). |
| صفحة المحاضرة (Playlist داخل كورس) | `frontend/src/app/(player)/courses/[id]/lectures/[lectureId]/page.tsx` | ✅ موجودة: تبويبات نظرة عامة/موارد/أسئلة + تحويل حاجز للامتحان. |
| صفحة المحاضرة المنفردة (جديدة — غير ملتزمة) | `frontend/src/app/(main)/lectures/[id]/page.tsx` | ✅ موجودة (untracked) لكن بها نواقص ربط (12.3). |
| صفحة محتويات الباقة | `frontend/src/app/(main)/bundles/[id]/page.tsx` | ✅ موجودة. |
| الكتالوج العام | `frontend/src/app/(main)/courses/page.tsx` | ✅ تبويبات كورسات/محاضرات/باقات. |

### 12.2 مسار مفقود → 404 (⚠️ عالية)
- `src/lib/constants.ts:12` يوجه روابط المحاضرة إلى `/courses/{courseId}/lectures/{lectureId}`.
- تحت `(main)` لا يوجد `page.tsx` لهذا المسار — يوجد فقط `exam/page.tsx`. صفحة المحاضرة موجودة حصريًا تحت `(player)`.
- أي `<Link>` من لوحة الطالب (`(dashboard)/dashboard/courses`) أو تحويل `(player)/play` يستهدف المسار العام → **404 أثناء التشغيل**.
- **الحل:** إضافة صفحة تحويل/عرض تحت `(main)` لنفس المسار، أو توجيه كل الروابط إلى `(player)`.

### 12.3 نواقص ربط صفحة المحاضرة المنفردة (⚠️)
- الباك اند `LectureResource` لا يُرجع `section` أو `instructor`، و`CourseController::showLecture` لا يحمّل `instructor` → بانر "متوفرة داخل كورس" وشارة المعلم في `(main)/lectures/[id]/page.tsx` **لن تظهر أبدًا**. الإصلاح: إضافة الحقلين للـ Resource + `->with('instructor')`.
- مطابقة المنتج عبر نص حرفي `p.sellable_type === "App\\Models\\Lecture"` — هشّ وقابل للكسر.
- `original_name` أُضيف لنوع الملفات الأمامي لكن لا يوجد عمود في `LectureFile` (يقع في المسار الاحتياطي — غير مؤذٍ).

### 12.4 إزالة الاشتراك الشهري — الحالة
- ✅ **مُلتَزَم (Backend):** لا يوجد أي نموذج اشتراكات (`plans`/`plan_subscriptions`)؛ `enroll` أصبح للكورسات المجانية فقط.
- 🚧 **قيد التنفيذ (غير ملتزم):**
  - تبويب "الاشتراكات الشهرية 📅" حُذف من `(main)/courses/page.tsx` ✅ — **لكن بقيت مخلفات:** سطر 43 ما زال يستدعي `useProducts("section")` (طلب API ميت في كل زيارة) + استيراد `Calendar` بلا استخدام + متغير `sections`.
  - `DemoSeeder` استبدل منتج "اشتراك شهر" بمحاضرتين منفردتين؛ `ProductResource` مخفي من قائمة Filament.
- ⚠️ **نص اشتراك متبقٍ:** `(main)/products/[id]/page.tsx:49` ("المحاضرات المشمولة في هذا الشهر/الوحدة") و`:109` ("سعر الاشتراك").
- ✅ لا يوجد أي "حوار اشتراك شهري" في الواجهة، ولا مسار دفع اشتراكات.

### 12.5 الأداء والسرعة (Performance)
- ❌ **لا يوجد `next/image` إطلاقًا** — كل الصور `<img>` عادية (`course-card.tsx:22`، `(main)/page.tsx:66`، لوحة الطالب، `quiz-tab.tsx`) → لا تحسين/`lazy`/`priority`، وشيفت في التخطيط.
- ❌ **اعتماد ميت:** `framer-motion@^12.42.2` في `package.json:25` بلا أي استخدام في `src` (يزيد حجم الحزمة). (`@base-ui/react` مستخدم فعليًا كأساس للمكونات).
- ❌ كل صفحات `(main)` بـ `"use client"` → شحن JS كامل لكل صفحة بدون استفادة من Server Components/Streaming.
- ❌ لا `loading.tsx` لكل مسار ولا حدود `Suspense` حول أقسام البيانات (فقط `PageLoading` عام). (`src/app/loading.tsx` فقط).
- ❌ **الكتالوج يطلق 4 طلبات متوازية عند كل فتح** (كورسات + منتجات محاضرات + منتجات أقسام ميتة + باقات)، و`useProducts`/`useBundles` تجلب **كل** `is_active=true` بلا Pagination → الحمولة تنمو بلا حد.
- ⚠️ صفحة الـ Player (300+ سطر) بها **صفر** `useMemo`/`useCallback`.
- ✅ إيجابي: `VideoPlayer` محمّل بـ `dynamic(..., {ssr:false})`؛ DevTools الإنتاج مقفول (12.2/5.14)؛ التوكن يُرسل للـ API المحلي فقط.
- ⚠️ `next.config.ts` headers بلا CSP (يوجد فقط clickjacking/no-sniff/referrer/permissions).
- ⚠️ لا وسائط موقّعة قصيرة العمر في الواجهة (تعتمد على Bearer + روابط Bunny الموقّعة).

---

## ملاحظات إيجابية (تستحق الحفاظ)
- لا أسرار ملتزمة في git (`.env*` معزولة ومُتحقق منها عبر `git ls-files`/`git log`).
- `$fillable` صارم في معظم الموديلات ولا يوجد `request()->all()` في مسارات API.
- سياسات المدرّب (Course/Section/Lecture/Exam) تتحقق من الملكية بشكل صحيح (عدا 3.10).
- لا يوجد ناقل API لرفع الدور صلاحيات (roles تُعطى من Filament فقط).
- Logout يحذف التوكن الحالي؛ استعادة كلمة المرور تحذف كل التوكنات.
- Q&A تتحقق من ملكية المؤلف/المدرّب.
- `downloadFile` آمن من Path Traversal (المسار من DB).

---

*هذا التقرير مبني على فحص مباشر للكود بتاريخ 2026-08-02. بعد إصلاح المرحلة 1 و2 يمكن إضافة الدفع بأمان، ثم بناء نموذج الاشتراكات لتحقيق ميزة "اختيار مكان المحاضرة عند الرفع".*
