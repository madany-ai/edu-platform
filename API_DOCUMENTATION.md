# توثيق الـ API — الدليل الشامل لفريق الفرونت اند

**آخر تحديث:** يوليو 2026
**عدد الـ Endpoints:** 42
**الحالة:** Backend مكتمل — 380 اختبار يمر جميعها

---

## ملخص سريع للبنية

### طريقة العمل
- **Base URL:** `https://{domin}/api`
- **Auth:** Sanctum Token — يُرسل في الـ Header مع كل request محتاجة authentication
- **Content-Type:** `application/json`
- **اللغة:** كل الـ Messages بالعربي
- **العملات:** EGP (جنيه مصري)

### طريقة تسجيل الدخول
```
POST /api/auth/login
Body: { "email": "...", "password": "...", "cf-turnstile-response": "..." }
Response: { "user": {...}, "token": "1|abc..." }
```
- الـ Token يُستخدم في كل API call: `Authorization: Bearer {token}`
- الـ Login يقبل: email، أو phone، أو student_code، أو رقم تليفون الطالب

### الأدوار (Roles)
| الدور | الوصول |
|---|---|
| `super_admin` | كل شيء (لوحة التحكم + API) |
| `instructor` | إدارة كورساته + طلابه + مشاهدة لوحة التحكم |
| `assistant` | مشاهدة كورسات محددة فقط (تم تعيينه لها) — لا يملك صلاحيات تعديل |
| `student` | API فقط (浏览، شراء، امتحانات، تقدم) — لا يصل للوحة التحكم |

### حالات المستخدم (User Status)
| الحالة | المعنى |
|---|---|
| `active` | حساب فعال — يمكنه تسجيل الدخول |
| `pending` | حساب قيد المراجعة — لا يمكنه تسجيل الدخول |
| `rejected` | حساب مرفوض — لا يمكنه تسجيل الدخول |

---

## المحتويات

1. [المصادقة (Auth)](#1-المصادقة-auth)
2. [الكورسات (Courses)](#2-الكورسات-courses)
3. [الأقسام والمحاضرات (Sections & Lectures)](#3-الأقسام-والمحاضرات)
4. [بث الفيديو (Video Streaming)](#4-بث-الفيديو)
5. [التقدم (Progress)](#5-التقدم-progress)
6. [التسجيلات (Enrollments)](#6-التسجيلات-enrollments)
7. [الحقوق (Entitlements)](#7-الحقوق-entitlements)
8. [الامتحانات والواجبات (Exams & Assignments)](#8-الامتحانات-والواجبات)
9. [المنتجات والبندلات (Products & Bundles)](#9-المنتجات-والبندلات)
10. [الطلبات (Orders)](#10-الطلبات-orders)
11. [لوحة التحكم (Dashboard)](#11-لوحة-التحكم-dashboard)
12. [بيانات مساعدة (Misc)](#12-بيانات-مساعدة)
13. [الأدوار والصلاحيات](#13-الأدوار-والصلاحيات)
14. [الميزات الناقصة في الفرونت](#14-الميزات-الناقصة-في-الفرونت)

---

## 1. المصادقة (Auth)

### 1.1 التسجيل
```
POST /api/auth/register
```
**Middleware:** `throttle:login`

**Body (JSON):**
```json
{
  "first_name": "أحمد",
  "second_name": "محمد",
  "third_name": "علي",
  "last_name": "عبدالله",
  "email": "ahmed@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "01012345678",
  "father_phone": "01112345678",
  "mother_phone": "01212345678",
  "guardian_job": "مهندس",
  "gender": "male",
  "birth_date": "2005-01-15",
  "governorate_id": "uuid-من-governorates",
  "grade_level_id": "uuid-من-grade_levels",
  "cf-turnstile-response": "token-من-cloudflare-turnstile"
}
```

**الحقول المطلوبة:**
| الحقل | النوع | الوصف |
|---|---|---|
| `first_name` | string | الاسم الأول |
| `second_name` | string | الاسم الثاني |
| `third_name` | string | الاسم الثالث |
| `last_name` | string | الاسم الأخير |
| `email` | email | البريد الإلكتروني (يجب أن يكون فريدًا) |
| `password` | string | كلمة المرور (8 أحرف على الأقل) |
| `password_confirmation` | string | تأكيد كلمة المرور |
| `phone` | string | رقم تليفون الطالب |
| `father_phone` | string | رقم تليفون الأب |
| `mother_phone` | string | رقم تليفون الأم |
| `guardian_job` | string | وظيفة ولي الأمر |
| `gender` | `male` أو `female` | الجنس |
| `birth_date` | date | تاريخ الميلاد (Y-m-d) |
| `governorate_id` | uuid | معرّف المحافظة |
| `grade_level_id` | uuid | معرّف المرحلة الدراسية |
| `cf-turnstile-response` | string | رمز التحقق من Cloudflare Turnstile |

**Response (201):**
```json
{
  "user": {
    "id": "uuid",
    "name": "أحمد عبدالله",
    "email": "ahmed@example.com",
    "created_at": "...",
    "updated_at": "..."
  },
  "message": "تم إنشاء الحساب بنجاح. يرجى انتظار الموافقة من قبل الإدارة."
}
```

**ملاحظات:**
- الحساب يبدأ بحالة `pending` — لا يمكن تسجيل الدخول حتى يوافق المدرس
- جميع المدرسين يحصلون على إشعار بالتسجيل الجديد
- `governorate_id` و `grade_level_id` يجب أن يكونا UUIDs موجودين في قاعدة البيانات

---

### 1.2 تسجيل الدخول
```
POST /api/auth/login
```
**Middleware:** `throttle:login`

**Body (JSON):**
```json
{
  "email": "ahmed@example.com",
  "password": "password123",
  "cf-turnstile-response": "token-من-cloudflare-turnstile"
}
```

**الحقول:**
| الحقل | النوع | ملاحظة |
|---|---|---|
| `email` | string | يمكن أن يكون: email، أو phone، أو student_code |
| `password` | string | كلمة المرور |
| `cf-turnstile-response` | string | رمز التحقق من Cloudflare Turnstile |

**Response (200):**
```json
{
  "user": {
    "id": "uuid",
    "name": "أحمد عبدالله",
    "email": "ahmed@example.com"
  },
  "token": "1|abc123..."
}
```

**الأخطاء المحتملة:**
| الحالة | الرسالة | السبب |
|---|---|---|
| 401 | "بيانات الدخول غير صحيحة." | كلمة المرور خاطئة أو الحساب غير موجود |
| 403 | "حسابك قيد المراجعة..." | الحساب بحالة `pending` |
| 403 | "لم يتم الموافقة على حسابك..." | الحساب بحالة `rejected` |

**ملاحظة:** الـ `email` field يقبل أي من:
- البريد الإلكتروني: `user@example.com`
- رقم التليفون: `01012345678`
- كود الطالب: `ST123456`
- تليفون الطالب من جدول `students`

---

### 1.3 تسجيل الخروج
```
POST /api/auth/logout
```
**Header:** `Authorization: Bearer {token}`

**Response (200):**
```json
{ "message": "Logged out" }
```

**الملاحظات:**
- يحذف الـ Token الحالي فقط
- يرجع 401 لو المستخدم غير مسجل دخول

---

### 1.4 معلومات المستخدم الحالي
```
GET /api/auth/me
```
**Header:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "id": "uuid",
  "name": "أحمد عبدالله",
  "email": "ahmed@example.com",
  "status": "active",
  "roles": ["student"],
  "student": {
    "id": "uuid",
    "user_id": "uuid",
    "first_name": "أحمد",
    "second_name": "محمد",
    "third_name": "علي",
    "last_name": "عبدالله",
    "phone": "01012345678",
    "student_code": "ST123456",
    "is_verified": true,
    "governorate_id": "...",
    "grade_level_id": "...",
    "gender": "male",
    "birth_date": "2005-01-15"
  }
}
```

**ملاحظات:**
- لو المستخدم instructor أو assistant → `student` يرجع `null`
- `roles` يرجع array من الأدوار: `["student"]` أو `["instructor"]` أو `["assistant"]`

---

### 1.5 نسيت كلمة المرور
```
POST /api/auth/forgot-password
```
**Middleware:** `throttle:login`

**Body (JSON):**
```json
{ "email": "ahmed@example.com" }
```

**Response (200):**
```json
{ "message": "تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني." }
```

**Response (404):**
```json
{ "message": "البريد الإلكتروني غير مسجل في النظام." }
```

**ملاحظات:**
- يتم إرسال إشعار في التطبيق يحتوي على الـ Token
- الـ Token صالح لمدة 60 دقيقة فقط
- الـ Frontend يحتاج صفحة `/reset-password?token=...&email=...`

---

### 1.6 إعادة تعيين كلمة المرور
```
POST /api/auth/reset-password
```

**Body (JSON):**
```json
{
  "email": "ahmed@example.com",
  "token": "الـ token-من-البريد",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**الحقول:**
| الحقل | النوع | ملاحظة |
|---|---|---|
| `email` | email | نفس البريد المستخدم في forgot-password |
| `token` | string | الـ Token المستلم من الإشعار |
| `password` | string | كلمة المرور الجديدة (8 أحرف على الأقل) |
| `password_confirmation` | string | تأكيد كلمة المرور الجديدة |

**Response (200):**
```json
{ "message": "تم إعادة تعيين كلمة المرور بنجاح. يرجى تسجيل الدخول بكلمة المرور الجديدة." }
```

**Response (422):**
```json
{ "message": "رابط إعادة التعيين غير صالح أو منتهي الصلاحية." }
```

**الملاحظات الأمنية:**
- بعد إعادة التعيين، جميع الـ Tokens القديمة تُحذف (يجب إعادة تسجيل الدخول)
- الـ Token ينتهي بعد 60 دقيقة
- لا يمكن استخدام الـ Token مرتين

---

## 2. الكورسات (Courses)

### 2.1 عرض الكورسات المتاحة (عام)
```
GET /api/courses
```
**Middleware:** لا يوجد (عام — بدون authentication)

**Query Parameters:**
| Param | Type | ملاحظة |
|---|---|---|
| `search` | string | بحث في عنوان الكورس |
| `page` | int | رقم الصفحة (default: 1) |
| `per_page` | int | عدد الكورسات بالصفحة (default: 12) |

**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "title": "الفيزياء للثانوية العامة",
      "description": "شرح كامل لمادة الفيزياء...",
      "price": 750.0,
      "thumbnail": "https://.../courses/thumbnails/xxx.jpg",
      "status": "published",
      "instructor": {
        "id": "uuid",
        "name": "م. أحمد",
        "email": "instructor@example.com"
      },
      "sections_count": 5,
      "students_count": 120
    }
  ],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 12,
    "total": 58
  }
}
```

**ملاحظات:**
- يعرض الكورسات بحالة `published` فقط
- pagination: 12 كورس لكل صفحة
- يدعم البحث بالعنوان (partial match)

---

### 2.2 تفاصيل كورس محدد
```
GET /api/courses/{course}
```
**Middleware:** لا يوجد (عام — لكن التفاصيل الكاملة تتطلب authentication للمستخدم المسجل)

**Response (200):**
```json
{
  "data": {
    "id": "uuid",
    "title": "الفيزياء للثانوية العامة",
    "description": "...",
    "price": 750.0,
    "thumbnail": "https://...",
    "status": "published",
    "instructor": {
      "id": "uuid",
      "name": "م. أحمد",
      "email": "instructor@example.com"
    },
    "sections_count": 5,
    "students_count": 120,
    "sections": [
      {
        "id": "uuid",
        "title": "الفصل الأول: الميكانيكا",
        "sort_order": 1,
        "lectures": [
          {
            "id": "uuid",
            "title": "الحركةخطية المستقيمة",
            "description": "...",
            "duration": 3600,
            "sort_order": 1,
            "video": {
              "id": "uuid",
              "video_path": "hls/uuid/playlist.m3u8",
              "status": "completed",
              "duration": 3600,
              "stream_url": "/api/lectures/uuid/stream",
              "stream_type": "application/x-mpegURL"
            },
            "has_exam": true,
            "has_assignment": false,
            "exams": [
              {
                "id": "uuid",
                "title": "امتحان الفصل الأول",
                "sort_order": 1,
                "is_blocking": true,
                "pass_percentage": 50,
                "duration": 30,
                "latest_attempt": null,
                "passed": false
              }
            ],
            "assignments": [],
            "is_locked": false,
            "video_locked": false,
            "progress": { "current_time": 120, "is_completed": false }
          }
        ]
      }
    ],
    "progress_map": {
      "uuid-ل Lecture": { "current_time": 120, "is_completed": false }
    }
  }
}
```

**ملاحظات هامة:**
- `progress_map` → array يحتوي على تقدم الطالب في كل محاضرة (فقط لو المستخدم مسجل دخول كطالب)
- `is_locked` → true لو الطالب محجوب من الوصول بسبب امتحان سابق لم ينجح فيه
- `video_locked` → true لو فيديو هذه المحاضرة محجوب بسبب امتحان
- `stream_url` → رابط بث الفيديو (HLS playlist أو YouTube link)
- `stream_type` → نوع المحتوى: `application/x-mpegURL` (HLS) أو `video/youtube`
- `latest_attempt` → آخر محاولة للطالب在这个 المحاضرة (null لو لم يحاول)
- `passed` → true لو الـ `score >= pass_percentage`

---

### 2.3 إنشاء كورس (مدرس فقط)
```
POST /api/courses
```
**Header:** `Authorization: Bearer {token}`
**Middleware:** `role:instructor`

**Body (JSON):**
```json
{
  "title": "اسم الكورس",
  "description": "وصف الكورس",
  "price": 500,
  "status": "draft"
}
```

**Response (201):** [CourseResource](#22-تفاصيل-كورس-محدد)

**ملاحظات:**
- `instructor_id` يُضاف تلقائيًا من الـ Token
- `thumbnail` لا يُرفع من الـ API — يُرفع من Filament فقط
- `price` بالجنيه المصري

---

### 2.4 تعديل كورس (المالك فقط)
```
PUT /api/courses/{course}
```
**Header:** `Authorization: Bearer {token}`
**Middleware:** `role:instructor`

**Body:** مماثل لإنشاء الكورس

**Response (200):** [CourseResource](#22-تفاصيل-كورس-محدد)

---

### 2.5 حذف كورس (المالك فقط)
```
DELETE /api/courses/{course}
```
**Header:** `Authorization: Bearer {token}`
**Middleware:** `role:instructor`

**Response (200):**
```json
{ "message": "Course deleted" }
```

**ملاحظات:**
- لا يمكن حذف كورس لمدرس آخر
- لا يمكن حذف كورس فيه طلاب مسجلين (يُرج خطأ)

---

## 3. الأقسام والمحاضرات

### 3.1 إنشاء قسم في كورس (مدرس فقط)
```
POST /api/courses/{course}/sections
```
**Header:** `Authorization: Bearer {token}`
**Middleware:** `role:instructor`

**Body (JSON):**
```json
{
  "title": "الفصل الأول: الميكانيكا",
  "sort_order": 1
}
```

**Response (201):**
```json
{
  "id": "uuid",
  "course_id": "uuid",
  "title": "الفصل الأول: الميكانيكا",
  "sort_order": 1,
  "created_at": "...",
  "updated_at": "..."
}
```

---

### 3.2 تعديل قسم (المالك فقط)
```
PUT /api/courses/{course}/sections/{section}
```
**Body:** مماثل لإنشاء القسم

**Response (200):** مماثل لإنشاء القسم

---

### 3.3 حذف قسم
```
DELETE /api/courses/{course}/sections/{section}
```
**Response (200):**
```json
{ "message": "تم حذف القسم بنجاح." }
```

---

### 3.4 إنشاء محاضرة في قسم (مدرس فقط)
```
POST /api/sections/{section}/lectures
```
**Header:** `Authorization: Bearer {token}`
**Middleware:** `role:instructor`

**Body (JSON):**
```json
{
  "title": "الحركةخطية المستقيمة",
  "description": "شرح مفاهيم الحركة...",
  "duration": 3600,
  "sort_order": 1,
  "youtube_url": "https://youtube.com/watch?v=xxx"
}
```

**الحقول:**
| الحقل | Type | مطلوب | ملاحظة |
|---|---|---|---|
| `title` | string | نعم | عنوان المحاضرة |
| `description` | string | لا | وصف المحاضرة |
| `duration` | integer | لا | مدة بالثواني |
| `sort_order` | integer | لا | ترتيب العرض |
| `youtube_url` | url | لا | رابط يوتيوب (بديل لرفع فيديو) |

**Response (201):**
```json
{
  "id": "uuid",
  "title": "الحركةخطية المستقيمة",
  "description": "...",
  "duration": 3600,
  "sort_order": 1,
  "video": {
    "id": "uuid",
    "video_path": "https://youtube.com/watch?v=xxx",
    "status": "completed",
    "bunny_video_id": "youtube",
    "duration": 3600
  }
}
```

**ملاحظات:**
- لو `youtube_url` مُرسل → يتم إنشاء `LectureVideo` تلقائيًا بحالة `completed`
- رفع فيديو HLS يتم من Filament فقط (وليس من الـ API)

---

### 3.5 تعديل محاضرة (المالك فقط)
```
PUT /api/sections/{section}/lectures/{lecture}
```
**Body:** مماثل لإنشاء المحاضرة

---

### 3.6 حذف محاضرة
```
DELETE /api/sections/{section}/lectures/{lecture}
```
**Response (200):**
```json
{ "message": "تم حذف المحاضرة بنجاح." }
```

---

### 3.7 عرض تفاصيل محاضرة
```
GET /api/lectures/{lecture}
```
**Header:** `Authorization: Bearer {token}`
**Middleware:** `CheckEnrollment` (يتحقق من صلاحية الوصول)

**Response (200):** مماثل لمحاضرة داخل [تفاصيل الكورس](#22-تفاصيل-كورس-محدد) لكن بشكل مفصّل أكثر — يشمل ملفات PDF والامتحانات والواجبات.

**ملاحظات:**
- هذا Endpoint يتحقق من صلاحية الوصول (ownership، entitlement، أو enrollment)
- يرجع 403 لو الطالب لا يملك صلاحية الوصول

---

## 4. بث الفيديو (Video Streaming)

### 4.1 بث فيديو المحاضرة (HLS)
```
GET /api/lectures/{lecture}/stream
```
**Header:** `Authorization: Bearer {token}`
**Middleware:** `throttle:video`

**Response:** HLS Playlist (.m3u8) مع روابط.segments محدّثة

**Content-Type:** `application/x-mpegURL`

**الملاحظات:**
- هذا Endpoint يرجع HLS playlist مع:
  - رابط الـ Key محدّث: `/api/lectures/{lecture}/key?token={token}`
  - روابط الـ Segments محدّtea لـ MinIO مباشرة
- الـ Token صالح لمدة 5 دقائق فقط
- الـ Token مقيّد بـ: المستخدم + المحاضرة + IP Address
- لا يمكن مشاركة الـ Token مع جهاز آخر

---

### 4.2 مفتاح تشفير الفيديو
```
GET /api/lectures/{lecture}/key?token={token}
```
**Middleware:** `throttle:video`

**Response:** Binary (16 bytes) — مفتاح AES-128

**Content-Type:** `application/octet-stream`

**ملاحظات:**
- هذا Endpoint لا يحتاج authentication عادي — يحتاج فقط الـ Token من `/stream`
- الـ Token يتحقق من: المستخدم، المحاضرة، IP،نتهاء الصلاحية

---

## 5. التقدم (Progress)

### 5.1 تحديث تقدم المحاضرة
```
POST /api/lectures/{lecture}/progress
```
**Header:** `Authorization: Bearer {token}`
**Middleware:** `CheckEnrollment`

**Body (JSON):**
```json
{
  "current_time": 120.5,
  "is_completed": false
}
```

**الحقول:**
| الحقل | Type | ملاحظة |
|---|---|---|
| `current_time` | numeric | الوقت الحالي بالثواني (required) |
| `is_completed` | boolean | هل أكمل الطالب المحاضرة (required) |

**Response (200):**
```json
{
  "message": "Progress updated successfully.",
  "progress": {
    "current_time": 120.5,
    "is_completed": false
  }
}
```

**ملاحظات:**
- لو `is_completed = true` → يتم تحديث `student_statistics` تلقائيًا (عدد المحاضرات المكتملة + وقت المشاهدة الكلي)
- الـ Progress مُحدّث عبر `updateOrCreate` (idempotent — لا يتكرر)

---

## 6. التسجيلات (Enrollments)

### 6.1 تسجيلاتي (للطالب)
```
GET /api/my-enrollments
```
**Header:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "course_id": "uuid",
      "course": {
        "id": "uuid",
        "title": "الفيزياء للثانوية العامة",
        "price": 750.0,
        "status": "published",
        "instructor": {
          "id": "uuid",
          "name": "م. أحمد"
        }
      },
      "student": null,
      "status": "active",
      "source": "enrollment",
      "started_at": "2026-01-15T10:00:00.000000Z",
      "expires_at": null,
      "created_at": "2026-01-15T10:00:00.000000Z"
    }
  ]
}
```

**ملاحظات:**
- `source` → `"enrollment"` (تسجيل مجاني) أو `"purchase"` (شراء) أو `"synthetic"` (من entitlement)
- `expires_at` → null لو الوصول دائم، أو تاريخ لو منتج محدود الصلاحية

---

### 6.2 تسجيل مجاني في كورس (للطالب)
```
POST /api/courses/{course}/enroll
```
**Header:** `Authorization: Bearer {token}`

**Response (201):** EnrollmentResource

**ملاحظات:**
- يسجل الطالب في الكورس مجانًا (للـ free courses)
- لا يحتاج طلب شراء

---

### 6.3 شراء كورس (للطالب)
```
POST /api/courses/{course}/purchase
```
**Header:** `Authorization: Bearer {token}`

**Response (201):** EnrollmentResource

**ملاحظات:**
- هذا Endpoint يسجّل الطالب فقط (لا ينشئ order)
- للشراء الفعلي → استخدم [إنشاء طلب شراء](#101-إنشاء-طلب-شراء)

---

### 6.4 التسجيلات المدفوعة (لمدرس)
```
GET /api/courses/{course}/enrollments
```
**Header:** `Authorization: Bearer {token}`
**Middleware:** `role:instructor`

**Response:** Array من EnrollmentResource

---

### 6.5 إلغاء تسجيل طالب (لمدرس)
```
DELETE /api/courses/{course}/enrollments/{student}
```
**Header:** `Authorization: Bearer {token}`
**Middleware:** `role:instructor`

**Response (200):**
```json
{ "message": "تم إلغاء التسجيل بنجاح." }
```

**ملاحظات:**
- يُلغي تسجيل الطالب ويحذف الـ Entitlements
- لا يمكن للطالب إلغاء تسجيل نفسه

---

## 7. الحقوق (Entitlements)

### 7.1 حقوقي (للطالب)
```
GET /api/my-entitlements
```
**Header:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": "uuid",
      "student_id": "uuid",
      "lecture_id": "uuid",
      "order_id": "uuid",
      "expires_at": "2026-08-15T00:00:00.000000Z",
      "created_at": "2026-07-15T10:00:00.000000Z"
    }
  ]
}
```

**ملاحظات:**
- كل entitlement يخص محاضرة واحدة
- `expires_at` → null لو الوصول دائم
- الـ Entitlements تُمنح فقط بعد تأكيد الدفع يدويًا من المدرس

---

## 8. الامتحانات والواجبات

### 8.1 عرض امتحان محاضرة
```
GET /api/lectures/{lecture}/exam
```
**Header:** `Authorization: Bearer {token}`

**Query Parameters:**
| Param | Type | ملاحظة |
|---|---|---|
| `exam_id` | uuid | معرّف امتحان محدد (اختياري — لو المحاضرة فيها أكثر من امتحان) |

**Response (200):**
```json
{
  "exam": {
    "id": "uuid",
    "title": "امتحان الفصل الأول",
    "sort_order": 1,
    "is_blocking": true,
    "pass_percentage": 50,
    "duration": 30,
    "questions": [
      {
        "id": "uuid",
        "type": "multiple_choice",
        "question": "ما هو قانون نيوتن الثاني؟",
        "degree": 10,
        "choices": [
          { "id": "uuid", "answer": "F = ma", "is_correct": true },
          { "id": "uuid", "answer": "F = mv", "is_correct": false }
        ]
      }
    ]
  },
  "latest_attempt": {
    "id": "uuid",
    "score": 75.5,
    "submitted_at": "2026-07-15T12:00:00.000000Z"
  }
}
```

**ملاحظات:**
- `is_blocking` → true لو الامتحان يحجب باقي المحتوى حتى النجاح
- `latest_attempt` → null لو الطالب لم يحاول بعد
- الأسئلة تشمل الخيارات مع `is_correct` (لا يُرجعها للطالب — هذا للـ instructor فقط)

---

### 8.2 عرض واجب محاضرة
```
GET /api/lectures/{lecture}/assignment
```
**Response:** مماثل لعرض الامتحان

---

### 8.3 بدء محاولة امتحان (للطالب)
```
POST /api/exams/{exam}/start
```
**Header:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "id": "uuid",
  "exam_id": "uuid",
  "student_id": "uuid",
  "started_at": "2026-07-15T12:00:00.000000Z",
  "submitted_at": null,
  "score": null
}
```

**ملاحظات:**
- لو الطالب عنده محاولة غير مُقدّمة → يرجعها (لا ينشئ محاولة جديدة)
- كل محاولة تنشئ في `exam_attempts` table

---

### 8.4 تقديم إجابات المحاولة (للطالب)
```
POST /api/attempts/{attempt}/submit
```
**Header:** `Authorization: Bearer {token}`

**Body (JSON):**
```json
{
  "answers": [
    {
      "question_id": "uuid-للسؤال",
      "answer": "F = ma"
    },
    {
      "question_id": "uuid-للسؤال-الأخر",
      "answer": "true"
    }
  ]
}
```

**Response (200):** ExamAttempt مع الـ `score` المحسوب تلقائيًا

**ملاحظات:**
- التصحيح التلقائي: MCQ و True/False تُصحّح فورًا
- الأسئلة المقالية (essay) تحصل على الدرجة الكاملة تلقائيًا (تصحيح يدوي من المدرس عبر Filament)
- `answer` → نص الإجابة (for MCQ: نص الخيار، for True/False: "true"/"false", for essay: نص الإجابة)

---

### 8.5 نتيجة محاولة
```
GET /api/attempts/{attempt}/result
```
**Response (200):**
```json
{
  "id": "uuid",
  "exam_id": "uuid",
  "student_id": "uuid",
  "score": 75.5,
  "submitted_at": "...",
  "answers": [
    {
      "id": "uuid",
      "question_id": "uuid",
      "answer": "F = ma",
      "choices": [
        { "id": "uuid", "answer": "F = ma", "is_correct": true },
        { "id": "uuid", "answer": "F = mv", "is_correct": false }
      ]
    }
  ]
}
```

---

### 8.6 محاولاتي (للطالب)
```
GET /api/my-attempts
```
**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "exam_id": "uuid",
      "score": 75.5,
      "submitted_at": "...",
      "exam": {
        "id": "uuid",
        "title": "امتحان الفصل الأول",
        "lecture": {
          "id": "uuid",
          "title": "الحركةخطية المستقيمة",
          "section": {
            "id": "uuid",
            "title": "الفصل الأول",
            "course": {
              "id": "uuid",
              "title": "الفيزياء"
            }
          }
        }
      }
    }
  ]
}
```

**ملاحظات:**
- يرجع المحاولات المُقدّمة فقط (where `submitted_at IS NOT NULL`)
- مرتبة بالأحدث أولاً

---

### 8.7 إنشاء امتحان (مدرس فقط)
```
POST /api/lectures/{lecture}/exam
```
**Header:** `Authorization: Bearer {token}`
**Middleware:** `role:instructor`

**Body (JSON):**
```json
{
  "title": "امتحان الفصل الأول",
  "duration": 30,
  "questions": [
    {
      "type": "multiple_choice",
      "question": "ما هو قانون نيوتن الثاني؟",
      "degree": 10,
      "choices": [
        { "answer": "F = ma", "is_correct": true },
        { "answer": "F = mv", "is_correct": false }
      ]
    }
  ]
}
```

---

### 8.8 تعديل امتحان (المالك فقط)
```
PUT /api/exams/{exam}
```
**Body:** مماثل لإنشاء الامتحان

---

### 8.9 حذف امتحان (المالك فقط)
```
DELETE /api/exams/{exam}
```
**Response (200):**
```json
{ "message": "تم حذف الامتحان بنجاح." }
```

---

## 9. المنتجات والبندلات

### 9.1 عرض المنتجات المتاحة
```
GET /api/products
```
**Middleware:** لا يوجد (عام)

**Query Parameters:**
| Param | Type | ملاحظة |
|---|---|---|
| `type` | string | `course` أو `section` أو `lecture` (اختياري) |

**Response (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": "uuid",
      "name": "كورس الفيزياء الكامل",
      "price": 750.0,
      "access_duration_days": 90,
      "is_active": true,
      "sellable": {
        "id": "uuid",
        "title": "الفيزياء للثانوية العامة"
      }
    }
  ]
}
```

**ملاحظات:**
- `sellable` → الكورس أو القسم أو المحاضرة المرتبطة بالمنتج (polymorphic)
- `access_duration_days` → مدة الصلاحية بالأيام (null = دائم)

---

### 9.2 تفاصيل منتج
```
GET /api/products/{product}
```

**Response (200):**
```json
{
  "status": "success",
  "data": {
    "id": "uuid",
    "name": "كورس الفيزياء الكامل",
    "price": 750.0,
    "sellable": {
      "id": "uuid",
      "title": "الفيزياء للثانوية العامة",
      "sections": [...]
    }
  }
}
```

**ملاحظات:**
- لو `sellable_type = Course` → يload `sections.lectures`
- لو `sellable_type = CourseSection` → يload `lectures`
- لو `sellable_type = Lecture` → يload `sellable` مباشرة

---

### 9.3 عرض البندلات
```
GET /api/bundles
```

**Response (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": "uuid",
      "name": "باقة الفيزياء والكيمياء",
      "price": 1200.0,
      "products": [
        {
          "id": "uuid",
          "name": "كورس الفيزياء",
          "price": 750.0,
          "sellable": {
            "id": "uuid",
            "title": "الفيزياء للثانوية العامة"
          }
        }
      ]
    }
  ]
}
```

---

### 9.4 تفاصيل بندلة
```
GET /api/bundles/{bundle}
```
**Response:** مماثل لعرض البندلات

---

## 10. الطلبات (Orders)

### 10.1 إنشاء طلب شراء
```
POST /api/orders
```
**Header:** `Authorization: Bearer {token}`

**Body (JSON):**
```json
{
  "purchasable_id": "uuid-للمنتج-أو-البندلة",
  "purchasable_type": "product"
}
```

**الحقول:**
| الحقل | Type | ملاحظة |
|---|---|---|
| `purchasable_id` | string | معرّف المنتج أو البندلة |
| `purchasable_type` | string | `product` أو `bundle` |

**Response (201):**
```json
{
  "status": "success",
  "message": "تم إرسال طلب الشراء بنجاح. سيتم تفعيل المحتوى بعد التحقق من الدفع.",
  "data": {
    "id": "uuid",
    "student_id": "uuid",
    "purchasable_id": "uuid",
    "purchasable_type": "App\\Models\\Product",
    "amount_cents": 75000,
    "currency": "EGP",
    "payment_method": "manual",
    "transaction_id": "PENDING-ABC123",
    "status": "pending",
    "paid_at": null,
    "created_at": "..."
  }
}
```

**ملاحظات مهمة:**
- **لا يوجد دفع أونلاين** — الطلب يبدأ بحالة `pending`
- الطالب يدفع يدويًا (فودافون كاش / تحويل بنكي / InstaPay)
- المدرس يؤكد الدفع من Filament → يتم تفعيل المحتوى تلقائيًا
- `amount_cents` → السعر بالقروش (price × 100)
- `purchasable_type` → يجب أن يكون `product` أو `bundle` (وليس اسم الكلاس)
- `purchasable_id` → UUID للمنتج أو البندلة

**الأخطاء المحتملة:**
| الحالة | الرسالة |
|---|---|
| 404 | "طالب غير موجود." (لو المستخدم ليس طالبًا) |
| 403 | "لا تملك الصلاحيات لشراء هذا المحتوى." (طالب غير مُعتمد) |
| 404 | "المنتج غير موجود." |

---

## 11. لوحة التحكم (Dashboard)

### 11.1 إحصائيات الطالب
```
GET /api/dashboard/student
```
**Header:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "enrollments_count": 3,
  "active_enrollments": 2,
  "completed_lectures": 15,
  "total_watch_minutes": 450,
  "average_exam_score": 82.5
}
```

---

### 11.2 إحصائيات المدرس
```
GET /api/dashboard/instructor
```
**Header:** `Authorization: Bearer {token}`
**Middleware:** `role:instructor`

**Response (200):**
```json
{
  "courses_count": 5,
  "total_students": 120,
  "total_revenue": 75000.0,
  "published_courses": 3,
  "draft_courses": 2,
  "total_lectures": 45,
  "pending_orders": 3,
  "recent_enrollments_count": 10
}
```

---

### 11.3 كورسات المدرس
```
GET /api/dashboard/instructor/courses
```
**Middleware:** `role:instructor`

**Response:** Array من CourseResource

---

### 11.4 التسجيلات الأخيرة (لمدرس)
```
GET /api/dashboard/instructor/recent-enrollments
```
**Middleware:** `role:instructor`

**Response:** Array من EnrollmentResource

---

### 11.5 أداء الكورسات (لمدرس)
```
GET /api/dashboard/instructor/course-performance
```
**Middleware:** `role:instructor`

**Response (200):**
```json
[
  {
    "course_id": "uuid",
    "course_title": "الفيزياء",
    "enrollments_count": 30,
    "completion_rate": 65.5
  }
]
```

---

### 11.6 إشعارات المدرس
```
GET /api/dashboard/instructor/notifications
```
**Middleware:** `role:instructor`

**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "user_id": "uuid",
      "title": "تسجيل طالب جديد",
      "body": "قام أحمد بالتسجيل في المنصة...",
      "read_at": null,
      "created_at": "..."
    }
  ]
}
```

---

### 11.7 طلاب المدرس
```
GET /api/instructor/students
```
**Middleware:** `role:instructor`

**Response (200):**
```json
[
  {
    "id": "uuid",
    "first_name": "أحمد",
    "last_name": "عبدالله",
    "phone": "01012345678",
    "student_code": "ST123456",
    "is_verified": true,
    "user": {
      "id": "uuid",
      "name": "أحمد عبدالله",
      "email": "ahmed@example.com"
    }
  }
]
```

---

## 12. بيانات مساعدة

### 12.1 المحافظات
```
GET /api/governorates
```
**Response (200):**
```json
{
  "status": "success",
  "data": [
    { "id": "uuid", "name": "القاهرة" },
    { "id": "uuid", "name": "الجيزة" },
    { "id": "uuid", "name": "الإسكندرية" }
  ]
}
```

---

### 12.2 المراحل الدراسية
```
GET /api/grade-levels
```
**Response (200):**
```json
{
  "status": "success",
  "data": [
    { "id": "uuid", "name": "الصف الأول الثانوي", "sort_order": 1 },
    { "id": "uuid", "name": "الصف الثاني الثانوي", "sort_order": 2 },
    { "id": "uuid", "name": "الصف الثالث الثانوي", "sort_order": 3 }
  ]
}
```

---

## 13. الأدوار والصلاحيات

### للطالب (student):
| الوظيفة | Endpoint | الصلاحية |
|---|---|---|
| التسجيل | `POST /api/auth/register` | عام |
| تسجيل الدخول | `POST /api/auth/login` | عام |
| نسيت كلمة المرور | `POST /api/auth/forgot-password` | عام |
| إعادة التعيين | `POST /api/auth/reset-password` | عام |
| معلوماتي | `GET /api/auth/me` | authenticated |
| تسجيل الخروج | `POST /api/auth/logout` | authenticated |
| كورساتي | `GET /api/my-enrollments` | authenticated |
| حقوقي | `GET /api/my-entitlements` | authenticated |
| تسجيل مجاني | `POST /api/courses/{id}/enroll` | authenticated |
| شراء | `POST /api/orders` | authenticated |
| تفاصيل كورس | `GET /api/courses/{id}` | عام |
| تفاصيل محاضرة | `GET /api/lectures/{id}` | enrolled/entitled |
| بث فيديو | `GET /api/lectures/{id}/stream` | enrolled/entitled |
| تحديث تقدم | `POST /api/lectures/{id}/progress` | enrolled/entitled |
| امتحان | `GET /api/lectures/{id}/exam` | enrolled/entitled |
| بدء محاولة | `POST /api/exams/{id}/start` | enrolled |
| تقديم إجابات | `POST /api/attempts/{id}/submit` | enrolled |
| نتيجة محاولة | `GET /api/attempts/{id}/result` | enrolled |
| محاولاتي | `GET /api/my-attempts` | authenticated |
| لوحة تحكمي | `GET /api/dashboard/student` | authenticated |

### للمدرس (instructor):
| الوظيفة | Endpoint | الصلاحية |
|---|---|---|
| إدارة الكورسات | CRUD `/api/courses/*` | instructor (owner) |
| إدارة الأقسام | CRUD `/api/courses/{id}/sections/*` | instructor (owner) |
| إدارة المحاضرات | CRUD `/api/sections/{id}/lectures/*` | instructor (owner) |
| إدارة الامتحانات | CRUD `/api/lectures/{id}/exam` | instructor (owner) |
| إحصائياتي | `GET /api/dashboard/instructor` | instructor |
| كورساتي | `GET /api/dashboard/instructor/courses` | instructor |
| تسجيلاتي | `GET /api/dashboard/instructor/recent-enrollments` | instructor |
| أداء الكورسات | `GET /api/dashboard/instructor/course-performance` | instructor |
| إشعاراتي | `GET /api/dashboard/instructor/notifications` | instructor |
| طلابي | `GET /api/instructor/students` | instructor |
| تسجيلات كورس | `GET /api/courses/{id}/enrollments` | instructor (owner) |
| إلغاء تسجيل | `DELETE /api/courses/{id}/enrollments/{student}` | instructor (owner) |

### للمساعد (assistant):
| الوظيفة | Endpoint | الصلاحية |
|---|---|---|
| عرض الكورسات المعينة | `GET /api/courses` | عام (يُرجع الكورسات فقط) |
| لوحة تحكم محدودة | `GET /api/dashboard/instructor` | assistant |
| عرض محاضرة معينة | `GET /api/lectures/{id}` | assistant (assigned only) |
| بث فيديو | `GET /api/lectures/{id}/stream` | assistant (assigned only) |

**ملاحظات assistants:**
- لا يمكنه إنشاء أو تعديل أو حذف أي شيء
- لا يمكنه الوصول لإدارة الطلاب أو المنتجات أو الطلبات
- لا يمكنه الوصول لصفحة الإعدادات في Filament
- الوصول مقيّد بالكورسات التي عُيّن لها فقط

---

## 14. الميزات الناقصة في الفرونت

بعد مراجعة الـ API الكاملة، هذه الميزات التي يحتاجها الباك اند لكن قد لا تكون موجودة في الفرونت:

### 14.1 ميزات أساسية (يجب أن تكون موجودة)
- [ ] **صفحة تسجيل مستخدم** — جميع الحقول المطلوبة (4 أسماء، هاتف، ولي الأمر، المحافظة، المرحلة)
- [ ] **صفحة تسجيل الدخول** — مع دعم تسجيل الدخول بالبريد أو الهاتف أو كود الطالب
- [ ] **صفحة نسيت كلمة المرور** — إرسال طلب + صفحة إعادة التعيين
- [ ] **صفحة إعادة تعيين كلمة المرور** — استقبال الـ Token من الرابط + إدخال كلمة المرور الجديدة
- [ ] **صفحة "حسابي قيد المراجعة"** — لما المستخدم يكون بحالة `pending`
- [ ] **صفحة "حسابك مرفوض"** — لما المستخدم يكون بحالة `rejected`

### 14.2 الكورسات
- [ ] **浏览 الكورسات المتاحة** — مع pagination (12 لكل صفحة) + بحث
- [ ] **صفحة تفاصيل كورس** — عرض الأقسام والمحاضرات مع حالة التقدم
- [ ] **حالة التقدم لكل محاضرة** — `progress` + `has_exam` + `is_locked` + `video_locked`
- [ ] **تسجيل مجاني في كورس** — زر "سجل الآن" للـ free courses

### 14.3 الفيديو والبث
- [ ] **مشغل فيديو HLS** — يدعم `application/x-mpegURL` (Video.js أو hls.js)
- [ ] **دعم فيديو YouTube** — لو `stream_type = video/youtube`
- [ ] **دعم فيديو MP4** — لو `stream_type = video/mp4`
- [ ] **تحديث التقدم أثناء المشاهدة** — إرسال `current_time` كل ثواني

### 14.4 الامتحانات والواجبات
- [ ] **صفحة الامتحان** — عرض الأسئلة + timer + تقديم
- [ ] **صفحة 결과** — عرض الدرجة + الإجابات الصحيحة
- [ ] **صفحة محاولاتي** — قائمة بجميع المحاولات المقدّمة
- [ ] **قفل المحتوى** — لو `is_locked = true` → إظهار رسالة "يلزم نجاح في امتحان سابق"
- [ ] **قفل الفيديو** — لو `video_locked = true` → إظهار رسالة "يلزم نجاح في امتحان سابق لفتح هذا الفيديو"

### 14.5 المشتريات
- [ ] **صفحة المنتجات** — عرض جميع المنتجات مع الفلترة حسب النوع
- [ ] **صفحة تفاصيل منتج** — الكورس أو القسم المرتبط + السعر + مدة الصلاحية
- [ ] **صفحة البندلات** — عرض البندلات مع المنتجات داخلها
- [ ] **طلب شراء** — زر "اشترِ الآن" مع تأكيد
- [ ] **صفحة "طلبك قيد المراجعة"** — لما الطلب يكون بحالة `pending`

### 14.6 لوحة تحكم الطالب
- [ ] **إحصائياتي** — عدد التسجيلات + المحاضرات المكتملة + وقت المشاهدة
- [ ] **كورساتي** — قائمة الكورسات المسجل فيها
- [ ] **حقوقي** — قائمة بالـ Entitlements (مع تاريخ الانتهاء)

### 14.7 للمساعد (assistant)
- [ ] **عرض الكورسات المعينة** — فقط الكورسات التي عُيّن لها
- [ ] **عرض محاضرات هذه الكورسات** — مع الصلاحيات المحددة

### 14.8 ملاحظات تقنية مهمة
- [ ] **Cloudflare Turnstile** — يجب تحميل السكربت + إرسال `cf-turnstile-response` مع كل login/register
- [ ] **Token Management** — حفظ الـ Token في localStorage/cookie + إرساله مع كل authenticated request
- [ ] **Error Handling** — معالجة جميع أكواد الخطأ (401, 403, 404, 422) وعرض رسائل واضحة
- [ ] **RTL Support** — جميع الـ Messages بالعربي — يجب دعم الاتجاه من اليمين لليسار
- [ ] **Pagination** — استخدام `meta.current_page`, `meta.last_page`, `links.next` للتنقل بين الصفحات
- [ ] **Loading States** — إظهار loading أثناء تحميل البيانات
- [ ] **Offline Handling** — التعامل مع عدم الاتصال بالإنترنت
