# 📋 Comprehensive Implementation Plan — edu-platform

> **Last Updated:** 2026-07-10
> **Source:** Full codebase audit against [PRD.md](file:///home/madany/Projects/edu-platform/PRD.md)
> **Goal:** Detailed plan that any AI model can execute autonomously without ambiguity

---

## 📁 Current Project Structure

```
edu-platform/
├── src/                              ← Laravel Backend (PHP 8.4 / Laravel 13)
│   ├── app/
│   │   ├── Enums/                    ✅ 4 enums
│   │   ├── Filament/
│   │   │   ├── Resources/
│   │   │   │   ├── Courses/          ✅ CourseResource + ManageCourses page
│   │   │   │   ├── Enrollments/      ✅ EnrollmentResource + ManageEnrollments page
│   │   │   │   └── Students/         ✅ StudentResource + ManageStudents page
│   │   │   └── Widgets/              🐛 3 widgets (crashing due to HasManyThrough bug)
│   │   ├── Http/
│   │   │   ├── Controllers/Api/      ✅ 4 controllers (Auth, Course, Dashboard, Enrollment)
│   │   │   ├── Middleware/           ✅ 2 middleware (CheckFilamentRole, CheckUserStatus)
│   │   │   ├── Requests/            ✅ 3 form requests (Login, Register, StoreCourse)
│   │   │   └── Resources/           ✅ 2 API resources (Course, Enrollment)
│   │   ├── Models/                   ✅ 25 models
│   │   ├── Policies/                 🐛 1 policy (CoursePolicy — authorization bug)
│   │   ├── Providers/                ✅ AppServiceProvider, AdminPanelProvider
│   │   └── Services/                 🐛 5 services (DashboardService has bugs)
│   ├── database/
│   │   ├── migrations/               ✅ 10 clean migration files
│   │   └── seeders/                  ✅ DatabaseSeeder with realistic test data
│   └── routes/
│       └── api.php                   ✅ 20+ routes defined
├── frontend/                         ← Next.js 16 (React 19, TypeScript)
│   └── src/
│       ├── app/
│       │   ├── (auth)/login/         ✅ Login page
│       │   ├── (auth)/register/      ✅ Register page (3-step wizard)
│       │   ├── courses/              ✅ Courses listing + detail + lecture view
│       │   ├── dashboard/            🐛 Student dashboard (works) + Instructor (broken)
│       │   ├── page.tsx              ✅ Landing page
│       │   └── layout.tsx            ✅ Root layout
│       ├── components/               ✅ UI components (shadcn/ui) + course-card
│       ├── contexts/                 ✅ auth-context.tsx
│       ├── lib/                      ✅ api.ts, types.ts, api/courses.ts, api/dashboard.ts
│       └── middleware.ts             ⚠️ Checks cookie but token is in localStorage
└── docker-compose.yml                ✅ All services (app, postgres, redis, nginx, queue, scheduler, mailpit)
```

---

## Phase 1: Critical Bug Fixes

> **CAUTION:** These bugs block the core Happy Path from working. Fix them FIRST before anything else.

### 1.1 ✅ DONE — Docker Setup
- **File:** [docker-compose.yml](file:///home/madany/Projects/edu-platform/docker-compose.yml)
- **Status:** All services running (app, postgres, redis, nginx, queue, scheduler, mailpit)
- **DB_HOST:** Correctly set to `postgres` (Docker service name)

---

### 1.2 🐛 BUG: HasManyThrough column mismatch — `Course::lectures()`

**File:** [src/app/Models/Course.php](file:///home/madany/Projects/edu-platform/src/app/Models/Course.php#L43-L46)

**Problem:** The `HasManyThrough` relation assumes the foreign key is `course_section_id`, but the migration ([2026_01_01_000004](file:///home/madany/Projects/edu-platform/src/database/migrations/2026_01_01_000004_create_courses_sections_lectures_tables.php#L32)) defines the column as `section_id`.

**Error:**
```
SQLSTATE[42703]: Undefined column: lectures.course_section_id does not exist
```

**Fix:** Explicitly specify the foreign keys in the `HasManyThrough`:

```php
// File: src/app/Models/Course.php — lines 43-46
// OLD:
public function lectures(): HasManyThrough
{
    return $this->HasManyThrough(Lecture::class, CourseSection::class);
}

// NEW:
public function lectures(): HasManyThrough
{
    return $this->hasManyThrough(
        Lecture::class,
        CourseSection::class,
        'course_id',     // FK on course_sections table
        'section_id',    // FK on lectures table
        'id',            // Local key on courses table
        'id'             // Local key on course_sections table
    );
}
```

**Files affected by this bug (all will auto-fix once this is resolved):**
- [DashboardService::getInstructorStats](file:///home/madany/Projects/edu-platform/src/app/Services/DashboardService.php#L30-L33) — `withCount('lectures')` → 500 Error
- [DashboardService::getInstructorCourses](file:///home/madany/Projects/edu-platform/src/app/Services/DashboardService.php#L67) — `withCount(['lectures'])` → 500 Error
- [DashboardService::getInstructorCoursePerformance](file:///home/madany/Projects/edu-platform/src/app/Services/DashboardService.php#L86) — `withCount(['lectures'])` → 500 Error
- [InstructorStatsOverview widget](file:///home/madany/Projects/edu-platform/src/app/Filament/Widgets/InstructorStatsOverview.php) — crashes Filament dashboard
- [CoursePerformanceWidget](file:///home/madany/Projects/edu-platform/src/app/Filament/Widgets/CoursePerformanceWidget.php#L22) — crashes

**Verification:**
```bash
curl -s http://localhost:8000/api/dashboard/instructor -H "Authorization: Bearer TOKEN"
# Expected: valid JSON with courses/students/revenue stats
```

---

### 1.3 🐛 BUG: `getStudentEnrollments` — Invalid `withCount` syntax

**File:** [src/app/Services/EnrollmentService.php](file:///home/madany/Projects/edu-platform/src/app/Services/EnrollmentService.php#L69-L71)

**Problem:** `->withCount('course.sections')` is not valid Laravel syntax. `withCount` does NOT support dot notation for nested relationships.

**Error:**
```
Call to undefined method App\Models\Enrollment::course.sections()
```

**Fix:**
```php
// File: src/app/Services/EnrollmentService.php
// OLD (around line 69-73):
return Enrollment::with(['course.instructor', 'course.sections'])
    ->withCount('course.sections')
    ->where('student_id', $student->id)
    ->latest()
    ->get();

// NEW — simply remove the withCount:
return Enrollment::with(['course.instructor', 'course.sections'])
    ->where('student_id', $student->id)
    ->latest()
    ->get();
```

**Verification:**
```bash
curl -s http://localhost:8000/api/my-enrollments -H "Authorization: Bearer STUDENT_TOKEN"
# Expected: JSON array of enrollments
```

---

### 1.4 🐛 BUG: `EnrollmentResource` — Does not return nested course object

**File:** [src/app/Http/Resources/EnrollmentResource.php](file:///home/madany/Projects/edu-platform/src/app/Http/Resources/EnrollmentResource.php)

**Problem:** The frontend expects `enrollment.course.title` as a nested object, but the API resource only returns `course_title` as a flat string. Also missing `student` and `source` fields.

**Fix — Replace entire `toArray` method:**
```php
// File: src/app/Http/Resources/EnrollmentResource.php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'course_id' => $this->course_id,
        'course' => $this->whenLoaded('course', fn () => [
            'id' => $this->course->id,
            'title' => $this->course->title,
            'price' => (float) $this->course->price,
            'status' => $this->course->status,
            'instructor' => $this->course->instructor ? [
                'id' => $this->course->instructor->id,
                'name' => $this->course->instructor->name,
            ] : null,
        ]),
        'student' => $this->whenLoaded('student', fn () => [
            'id' => $this->student->id,
            'user' => $this->student->user ? [
                'name' => $this->student->user->name,
            ] : null,
        ]),
        'status' => $this->status,
        'source' => $this->source,
        'started_at' => $this->started_at,
        'expires_at' => $this->expires_at,
        'created_at' => $this->created_at,
    ];
}
```

---

### 1.5 🐛 BUG: `CoursePolicy` — Any instructor can edit/delete any course

**File:** [src/app/Policies/CoursePolicy.php](file:///home/madany/Projects/edu-platform/src/app/Policies/CoursePolicy.php#L25-L33)

**Problem:** `update` and `delete` allow ANY user with `instructor` role to modify ANY course, even if they don't own it.

**Fix:**
```php
// File: src/app/Policies/CoursePolicy.php
// OLD:
public function update(User $user, Course $course): bool
{
    return $user->id === $course->instructor_id || $user->hasRole('instructor');
}

public function delete(User $user, Course $course): bool
{
    return $user->id === $course->instructor_id || $user->hasRole('instructor');
}

// NEW — only the owner can modify:
public function update(User $user, Course $course): bool
{
    return $user->id === $course->instructor_id;
}

public function delete(User $user, Course $course): bool
{
    return $user->id === $course->instructor_id;
}
```

---

### 1.6 🐛 BUG: `CourseService` — Duplicate enrollment logic

**File:** [src/app/Services/CourseService.php](file:///home/madany/Projects/edu-platform/src/app/Services/CourseService.php#L52-L63)

**Problem:** `CourseService::enrollStudent()` duplicates enrollment logic already in `EnrollmentService`. This causes confusion and potential inconsistencies.

**Fix:** Delete the entire `enrollStudent()` method (lines 52-63) from `CourseService`. All enrollment logic should live exclusively in `EnrollmentService`.

---

### 1.7 🐛 BUG: Frontend API URL — `getMyEnrollments` uses wrong path

**File:** [frontend/src/lib/api/courses.ts](file:///home/madany/Projects/edu-platform/frontend/src/lib/api/courses.ts#L24-L27)

**Problem:** The frontend calls `/courses/my-enrollments` but the backend route is `/my-enrollments`.

**Fix:**
```typescript
// File: frontend/src/lib/api/courses.ts — line 25
// OLD:
const { data } = await api.get("/courses/my-enrollments");

// NEW:
const { data } = await api.get("/my-enrollments");
```

---

### 1.8 🐛 BUG: Frontend middleware — checks cookie but token is in localStorage

**File:** [frontend/src/middleware.ts](file:///home/madany/Projects/edu-platform/frontend/src/middleware.ts#L5)

**Problem:** Next.js middleware reads `request.cookies.get("token")` but the token is stored in `localStorage` (not cookies). The middleware never finds the token, so it never actually protects routes.

**Fix (simplest for MVP — rely on client-side auth checks already in place):**
```typescript
// File: frontend/src/middleware.ts
import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

export function middleware(request: NextRequest) {
  // Auth protection is handled client-side via auth-context
  return NextResponse.next();
}

export const config = {
  matcher: ["/dashboard/:path*"],
};
```

---

## Phase 2: Complete Missing Backend APIs

### 2.1 ✅ DONE — Auth APIs

| Endpoint | Status | File |
|---|---|---|
| `POST /api/auth/register` | ✅ Working | [AuthController::register](file:///home/madany/Projects/edu-platform/src/app/Http/Controllers/Api/AuthController.php#L17) |
| `POST /api/auth/login` | ✅ Working | [AuthController::login](file:///home/madany/Projects/edu-platform/src/app/Http/Controllers/Api/AuthController.php#L24) |
| `POST /api/auth/logout` | ✅ Working | [AuthController::logout](file:///home/madany/Projects/edu-platform/src/app/Http/Controllers/Api/AuthController.php#L52) |
| `GET /api/auth/me` | ✅ Working | [AuthController::me](file:///home/madany/Projects/edu-platform/src/app/Http/Controllers/Api/AuthController.php#L59) |

**Note:** `AuthService::register` ([lines 44-51](file:///home/madany/Projects/edu-platform/src/app/Services/AuthService.php#L44-L51)) loops over all instructors to send notifications. This is an N+1 risk and should be converted to a queued job later.

---

### 2.2 ✅ DONE (needs authorize) — Course APIs

| Endpoint | Status | Notes |
|---|---|---|
| `GET /api/courses` | ✅ | Returns published courses with pagination |
| `GET /api/courses/{id}` | ✅ | Returns sections + lectures |
| `POST /api/courses` | ✅ | Protected by `StoreCourseRequest` |
| `PUT /api/courses/{id}` | ✅ | Needs `authorize()` call |
| `DELETE /api/courses/{id}` | ✅ | Needs `authorize()` call |

**Required:** Wire `CoursePolicy` to `CourseController`. Currently the controller does NOT call `$this->authorize()`.

**Fix in [CourseController](file:///home/madany/Projects/edu-platform/src/app/Http/Controllers/Api/CourseController.php):**
```php
public function store(StoreCourseRequest $request): CourseResource
{
    $this->authorize('create', Course::class);  // ← ADD
    // ... rest of existing code
}

public function update(StoreCourseRequest $request, Course $course): CourseResource
{
    $this->authorize('update', $course);  // ← ADD
    // ... rest of existing code
}

public function destroy(Course $course): JsonResponse
{
    $this->authorize('delete', $course);  // ← ADD
    // ... rest of existing code
}
```

---

### 2.3 ✅ DONE — Section/Lecture CRUD APIs

| Endpoint | Status |
|---|---|
| `POST /api/courses/{course}/sections` | ✅ |
| `PUT /api/courses/{course}/sections/{section}` | ✅ |
| `DELETE /api/courses/{course}/sections/{section}` | ✅ |
| `POST /api/sections/{section}/lectures` | ✅ |
| `PUT /api/sections/{section}/lectures/{lecture}` | ✅ |
| `DELETE /api/sections/{section}/lectures/{lecture}` | ✅ |

**TODO (later):** Add authorization — currently any authenticated user can add/edit sections.

---

### 2.4 ❌ MISSING — Lecture View API

**Problem:** The frontend ([lecture view page line 24](file:///home/madany/Projects/edu-platform/frontend/src/app/courses/%5Bid%5D/lectures/%5BlectureId%5D/page.tsx#L24)) calls `GET /api/lectures/{lectureId}` but this route does **NOT exist** in [api.php](file:///home/madany/Projects/edu-platform/src/routes/api.php).

**Required:**

1. Add route in `routes/api.php` inside the authenticated group:
```php
Route::get('lectures/{lecture}', [CourseController::class, 'showLecture']);
```

2. Add method in [CourseController](file:///home/madany/Projects/edu-platform/src/app/Http/Controllers/Api/CourseController.php):
```php
public function showLecture(\App\Models\Lecture $lecture): JsonResponse
{
    $lecture->load(['video', 'files', 'section.course']);

    // TODO: Add enrollment check middleware later (Phase 2.6)

    return response()->json($lecture);
}
```

---

### 2.5 ❌ MISSING — Exam System API

**Schema exists:** [migration](file:///home/madany/Projects/edu-platform/src/database/migrations/2026_01_01_000006_create_exam_tables.php) — tables: `exams`, `questions`, `choices`, `exam_attempts`, `answers`

**Models exist:** [Exam](file:///home/madany/Projects/edu-platform/src/app/Models/Exam.php), [Question](file:///home/madany/Projects/edu-platform/src/app/Models/Question.php), [Choice](file:///home/madany/Projects/edu-platform/src/app/Models/Choice.php), [ExamAttempt](file:///home/madany/Projects/edu-platform/src/app/Models/ExamAttempt.php), [Answer](file:///home/madany/Projects/edu-platform/src/app/Models/Answer.php)

**Everything below needs to be created from scratch:**

#### 2.5.1 Create `ExamService` — New file: `src/app/Services/ExamService.php`
```php
class ExamService
{
    // Create a new exam for a lecture
    public function createExam(Lecture $lecture, array $data): Exam;

    // Add a question with choices to an exam
    public function addQuestion(Exam $exam, array $data): Question;

    // Start a new exam attempt for a student
    public function startAttempt(Exam $exam, Student $student): ExamAttempt;

    // Submit student answers and auto-grade MCQ
    public function submitAttempt(ExamAttempt $attempt, array $answers): ExamAttempt;

    // Auto-grade by comparing selected choice IDs to correct choices
    public function gradeAttempt(ExamAttempt $attempt): float;

    // Get student's result for a specific exam
    public function getStudentResults(Student $student, Exam $exam): ?ExamAttempt;
}
```

#### 2.5.2 Create `ExamController` — New file: `src/app/Http/Controllers/Api/ExamController.php`

**Routes to add in `api.php`:**
```php
// Student exam routes (authenticated)
Route::get('lectures/{lecture}/exam', [ExamController::class, 'show']);
Route::post('exams/{exam}/start', [ExamController::class, 'startAttempt']);
Route::post('attempts/{attempt}/submit', [ExamController::class, 'submitAttempt']);
Route::get('attempts/{attempt}/result', [ExamController::class, 'result']);

// Instructor exam management routes
Route::post('lectures/{lecture}/exam', [ExamController::class, 'store'])
    ->middleware('role:instructor');
Route::put('exams/{exam}', [ExamController::class, 'update'])
    ->middleware('role:instructor');
Route::delete('exams/{exam}', [ExamController::class, 'destroy'])
    ->middleware('role:instructor');
```

#### 2.5.3 Create `ExamResource` — New file: `src/app/Http/Resources/ExamResource.php`

---

### 2.6 ❌ MISSING — Enrollment Check Middleware (Entitlement)

**New file:** `src/app/Http/Middleware/CheckEnrollment.php`

Verifies the student is enrolled in a course before they can view a lecture. Instructors (course owner) bypass the check.

```php
class CheckEnrollment
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $lecture = $request->route('lecture');
        if (!$lecture) return $next($request);

        $courseId = $lecture->section->course_id;
        $course = Course::find($courseId);

        // Instructors always have access to their own courses
        if ($course && $course->instructor_id === $user->id) {
            return $next($request);
        }

        // Check student enrollment
        $student = $user->student;
        if (!$student) {
            return response()->json(['message' => 'Not enrolled in this course.'], 403);
        }

        $isEnrolled = Enrollment::where('student_id', $student->id)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->exists();

        if (!$isEnrolled) {
            return response()->json(['message' => 'Not enrolled in this course.'], 403);
        }

        return $next($request);
    }
}
```

Register the middleware in `bootstrap/app.php` and apply it to lecture view routes.

---

### 2.7 ❌ MISSING — Include roles in `/auth/me` response

**File:** [AuthController::me](file:///home/madany/Projects/edu-platform/src/app/Http/Controllers/Api/AuthController.php#L59-L62)

**Problem:** The response doesn't include user roles. The frontend needs roles for role-based dashboard routing.

**Fix:**
```php
// File: src/app/Http/Controllers/Api/AuthController.php
public function me(): JsonResponse
{
    $user = request()->user();
    $user->load('roles');
    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'status' => $user->status,
        'roles' => $user->roles->pluck('name'),
    ]);
}
```

---

## Phase 3: Complete Filament Admin Panel (Instructor Dashboard)

### 3.1 ✅ DONE (needs additions) — CourseResource

**File:** [CourseResource.php](file:///home/madany/Projects/edu-platform/src/app/Filament/Resources/Courses/CourseResource.php)

**What exists:**
- ✅ Form: title, description, price, status, is_published
- ✅ Nested Repeater: sections → lectures (title, description, duration, sort_order)
- ✅ Table: id, title, price, status (badges), sections_count, enrollments_count, created_at
- ✅ Actions: Edit, Delete, Bulk Delete
- ✅ Instructor scope: `getEloquentQuery()` filters by logged-in instructor

**What's missing:**
- ❌ No video field (Bunny ID) in lecture form
- ❌ No PDF upload/URL field in lecture form
- ❌ `is_published` toggle is not backed by a DB column (uses `status` enum instead)

**Fix — Add fields to the lectures repeater ([lines 84-107](file:///home/madany/Projects/edu-platform/src/app/Filament/Resources/Courses/CourseResource.php#L84-L107)):**
```php
// After TextInput::make('sort_order'), ADD:
TextInput::make('bunny_video_id')
    ->label('Video ID (Bunny Stream)')
    ->placeholder('Leave empty if no video')
    ->maxLength(255),

TextInput::make('pdf_url')
    ->label('PDF File URL')
    ->url()
    ->placeholder('https://...')
    ->maxLength(500),
```

**Note:** You need to either:
- (a) Add a new migration to add `bunny_video_id` and `pdf_url` columns to the `lectures` table AND update `Lecture` model `$fillable`, OR
- (b) Use the existing `lecture_videos` and `lecture_files` relations with Filament relationship handling (more complex)

Option (a) is recommended for MVP.

---

### 3.2 ✅ DONE — StudentResource

**File:** [StudentResource.php](file:///home/madany/Projects/edu-platform/src/app/Filament/Resources/Students/StudentResource.php)

**What exists:**
- ✅ Form: all fields (user_id, 4 names, phones, guardian_job, gender, birth_date, geography)
- ✅ Table: full_name (computed), email, phone, status badges, created_at
- ✅ Actions: Edit, Approve (with confirmation + notification), Reject (with notification), Delete
- ✅ Filters: status filter (pending/active/rejected)

**Fully working ✅ — No changes needed.**

---

### 3.3 ✅ DONE (potential fix needed) — EnrollmentResource

**File:** [EnrollmentResource.php](file:///home/madany/Projects/edu-platform/src/app/Filament/Resources/Enrollments/EnrollmentResource.php)

**Potential issue:**
- ⚠️ `Select::make('student_id')->relationship('student.user', 'name')` — nested relationships in Filament Select may not work. If it fails, replace with:

```php
Select::make('student_id')
    ->label('Student')
    ->options(function () {
        return \App\Models\Student::with('user')
            ->get()
            ->mapWithKeys(fn ($s) => [$s->id => $s->user?->name ?? 'Unknown']);
    })
    ->searchable()
    ->required(),
```

---

### 3.4 🐛 Filament Dashboard Widgets — Crashing

| Widget | File | Status after Phase 1.2 fix |
|---|---|---|
| [InstructorStatsOverview](file:///home/madany/Projects/edu-platform/src/app/Filament/Widgets/InstructorStatsOverview.php) | Stats cards: courses, students, revenue, lectures | ✅ Will work |
| [RecentEnrollmentsWidget](file:///home/madany/Projects/edu-platform/src/app/Filament/Widgets/RecentEnrollmentsWidget.php) | Table: recent enrollments | ✅ Already works (doesn't use lectures) |
| [CoursePerformanceWidget](file:///home/madany/Projects/edu-platform/src/app/Filament/Widgets/CoursePerformanceWidget.php) | Table: courses + enrollments_count + lectures_count | ✅ Will work after fix |

**No additional changes needed** — fixing bug 1.2 resolves all widget crashes.

---

### 3.5 ❌ MISSING — ExamResource (Filament)

**New file:** `src/app/Filament/Resources/Exams/ExamResource.php`

```php
class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;
    protected static ?string $navigationLabel = 'Exams';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('lecture_id')
                ->label('Lecture')
                ->relationship('lecture', 'title')
                ->searchable()
                ->required(),

            TextInput::make('title')
                ->label('Exam Title')
                ->required(),

            TextInput::make('duration')
                ->label('Duration (minutes)')
                ->numeric()
                ->default(30),

            Repeater::make('questions')
                ->relationship()
                ->schema([
                    Select::make('type')
                        ->label('Question Type')
                        ->options([
                            'multiple_choice' => 'Multiple Choice',
                            'true_false' => 'True/False',
                        ])
                        ->default('multiple_choice'),

                    Textarea::make('question')
                        ->label('Question Text')
                        ->required(),

                    TextInput::make('degree')
                        ->label('Points')
                        ->numeric()
                        ->default(1),

                    Repeater::make('choices')
                        ->relationship()
                        ->schema([
                            TextInput::make('answer')
                                ->label('Answer Text')
                                ->required(),
                            Toggle::make('is_correct')
                                ->label('Correct Answer'),
                        ])
                        ->columns(2)
                        ->label('Choices'),
                ])
                ->label('Questions'),
        ]);
    }
}
```

**Also create:** `src/app/Filament/Resources/Exams/Pages/ManageExams.php`

---

### 3.6 ❌ MISSING — TA Management

**Current state:**
- ✅ `course_assistants` pivot table exists in [migration](file:///home/madany/Projects/edu-platform/src/database/migrations/2026_01_01_000005_create_enrollments_and_assistants_tables.php#L23-L29)
- ✅ `Course::assistants()` relation exists in [Course model](file:///home/madany/Projects/edu-platform/src/app/Models/Course.php#L27-L31)
- ✅ `User::assistedCourses()` relation exists in [User model](file:///home/madany/Projects/edu-platform/src/app/Models/User.php#L38-L42)
- ✅ `assistant` role exists in [seeder](file:///home/madany/Projects/edu-platform/src/database/seeders/DatabaseSeeder.php#L22)
- ❌ No Filament UI to manage TAs
- ❌ No permission system for TAs

**Required (simplest approach):** Add a `RelationManager` to `CourseResource`:

New file: `src/app/Filament/Resources/Courses/RelationManagers/AssistantsRelationManager.php`
```php
class AssistantsRelationManager extends RelationManager
{
    protected static string $relationship = 'assistants';
    protected static ?string $title = 'Teaching Assistants';

    // form: select user (with assistant role)
    // table: name, email, detach action
}
```

Then register it in `CourseResource::getRelations()`.

---

## Phase 4: Complete Frontend (Next.js)

### 4.1 ✅ DONE — Core Pages

| Page | File | Status |
|---|---|---|
| Landing | [page.tsx](file:///home/madany/Projects/edu-platform/frontend/src/app/page.tsx) | ✅ |
| Login | [login/page.tsx](file:///home/madany/Projects/edu-platform/frontend/src/app/(auth)/login) | ✅ |
| Register | [register/page.tsx](file:///home/madany/Projects/edu-platform/frontend/src/app/(auth)/register/page.tsx) | ✅ 3-step wizard, 12 fields |
| Courses List | [courses/page.tsx](file:///home/madany/Projects/edu-platform/frontend/src/app/courses/page.tsx) | ✅ Search + grid |
| Course Detail | [courses/[id]/page.tsx](file:///home/madany/Projects/edu-platform/frontend/src/app/courses/%5Bid%5D/page.tsx) | ✅ Sections accordion, enroll/purchase |
| Student Dashboard | [dashboard/page.tsx](file:///home/madany/Projects/edu-platform/frontend/src/app/dashboard/page.tsx) | ✅ (data shows zero) |
| Instructor Dashboard | [dashboard/instructor/page.tsx](file:///home/madany/Projects/edu-platform/frontend/src/app/dashboard/instructor/page.tsx) | 🐛 Broken — auto-fixes with Phase 1 |
| Lecture View | [lectures/[lectureId]/page.tsx](file:///home/madany/Projects/edu-platform/frontend/src/app/courses/%5Bid%5D/lectures/%5BlectureId%5D/page.tsx) | ⚠️ Placeholder — no video player |

### 4.2 ✅ DONE — Auth Context + API Layer — No changes needed

---

### 4.3 ❌ MISSING — Lecture Video Player

**File:** [lectures/[lectureId]/page.tsx](file:///home/madany/Projects/edu-platform/frontend/src/app/courses/%5Bid%5D/lectures/%5BlectureId%5D/page.tsx#L52-L64)

**Current state:** Placeholder showing "waiting for video to be linked"

**Fix — Replace the placeholder (lines 52-64) with Bunny Stream iframe:**
```tsx
{lecture.video?.bunny_video_id ? (
  <iframe
    src={`https://iframe.mediadelivery.net/embed/${process.env.NEXT_PUBLIC_BUNNY_LIBRARY_ID}/${lecture.video.bunny_video_id}?autoplay=false`}
    className="aspect-video w-full rounded-lg"
    allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
    allowFullScreen
  />
) : (
  <div className="aspect-video bg-muted rounded-lg flex items-center justify-center">
    <p className="text-muted-foreground">No video available yet</p>
  </div>
)}
```

Add `NEXT_PUBLIC_BUNNY_LIBRARY_ID` to `frontend/.env.local`.

---

### 4.4 ❌ MISSING — Exam Taking Page

**New file:** `frontend/src/app/courses/[id]/lectures/[lectureId]/exam/page.tsx`

**Requirements:**
- Fetch exam questions from `GET /api/lectures/{id}/exam`
- Display multiple choice questions
- Countdown timer based on `exam.duration`
- Submit answers to `POST /api/attempts/{id}/submit`
- Show result after submission

**New API file:** `frontend/src/lib/api/exams.ts`
```typescript
export async function getLectureExam(lectureId: number): Promise<Exam>;
export async function startExamAttempt(examId: number): Promise<ExamAttempt>;
export async function submitAttempt(attemptId: number, answers: SubmitAnswer[]): Promise<ExamResult>;
```

**New types to add in `frontend/src/lib/types.ts`:**
```typescript
export interface Exam {
  id: number; title: string; duration: number; questions: ExamQuestion[];
}
export interface ExamQuestion {
  id: number; type: string; question: string; degree: number; choices: ExamChoice[];
}
export interface ExamChoice {
  id: number; answer: string;
}
export interface ExamAttempt {
  id: number; score: number; started_at: string; submitted_at: string | null;
}
```

---

### 4.5 ❌ MISSING — Dashboard Role-Based Routing

**Required in [dashboard/page.tsx](file:///home/madany/Projects/edu-platform/frontend/src/app/dashboard/page.tsx):**
```tsx
useEffect(() => {
  if (user && user.roles?.includes('instructor')) {
    router.replace('/dashboard/instructor');
  }
}, [user]);
```

**Depends on:** Phase 2.7 (roles in `/auth/me`)

**Also update `User` type in `frontend/src/lib/types.ts`:**
```typescript
export interface User {
  id: number;
  name: string;
  email: string;
  status: string;
  roles?: string[];   // ← ADD
  student?: Student;
}
```

---

## Phase 5: PRD Features Not in MVP (Deferred to Phase 2+)

> **NOTE:** These features are NOT required for MVP per PRD Section 8, but are documented for completeness.

| Feature | PRD Section | Schema | Models | Missing |
|---|---|---|---|---|
| Q&A System | 3.6 | ✅ tables exist | ✅ models exist | Controller, Service, API, Frontend |
| Payments | 3.8 | ❌ | ❌ | Everything (Paymob/Kashier) |
| Assignments | 3.5 | ✅ tables exist | ✅ models exist | Controller, Service, API, Frontend |
| Instance Config | 3.11 | ❌ | ❌ | Everything (branding, domain) |
| Real-time (Reverb) | 6.1 | ❌ | ❌ | Everything |
| Email Notifications | 3.9 | ✅ DB only | ✅ basic service | Email channels, templates |

---

## ✅ Complete File Status Summary

### Backend Files

| File | Status | Notes |
|---|---|---|
| `Models/User.php` | ✅ | HasRoles, HasApiTokens, student relation |
| `Models/Student.php` | ✅ | All fields + relations |
| `Models/Course.php` | 🐛 | `lectures()` HasManyThrough crashes — Phase 1.2 |
| `Models/CourseSection.php` | ✅ | |
| `Models/Lecture.php` | ✅ | section, video, files, exam, assignment relations |
| `Models/LectureVideo.php` | ✅ | |
| `Models/LectureFile.php` | ✅ | |
| `Models/Enrollment.php` | ✅ | |
| `Models/Exam.php` | ✅ | No controller yet |
| `Models/Question.php` | ✅ | |
| `Models/Choice.php` | ✅ | |
| `Models/ExamAttempt.php` | ✅ | |
| `Models/Answer.php` | ✅ | |
| `Models/Assignment.php` | ✅ | Phase 2 |
| `Models/AssignmentSubmission.php` | ✅ | Phase 2 |
| `Models/QuestionsPost.php` | ✅ | Phase 2 |
| `Models/QuestionReply.php` | ✅ | Phase 2 |
| `Models/Notification.php` | ✅ | |
| `Models/Governorate.php` | ✅ | |
| `Models/City.php` | ✅ | |
| `Models/School.php` | ✅ | |
| `Models/GradeLevel.php` | ✅ | |
| `Models/AcademicTrack.php` | ✅ | |
| `Models/StudentActivity.php` | ✅ | |
| `Models/StudentStatistic.php` | ✅ | |
| `Services/AuthService.php` | ✅ | ⚠️ N+1 risk on instructor notification loop |
| `Services/CourseService.php` | 🐛 | Delete `enrollStudent()` — Phase 1.6 |
| `Services/EnrollmentService.php` | 🐛 | Fix `withCount` — Phase 1.3 |
| `Services/DashboardService.php` | 🐛 | Crashes — auto-fixes with Phase 1.2 |
| `Services/NotificationService.php` | ✅ | DB-only, simple |
| `Controllers/Api/AuthController.php` | ✅ | Needs roles in `me()` — Phase 2.7 |
| `Controllers/Api/CourseController.php` | ⚠️ | Missing `authorize()` calls — Phase 2.2 |
| `Controllers/Api/DashboardController.php` | ✅ | |
| `Controllers/Api/EnrollmentController.php` | ✅ | |
| `Requests/RegisterRequest.php` | ✅ | 12 required fields |
| `Requests/LoginRequest.php` | ✅ | |
| `Requests/StoreCourseRequest.php` | ✅ | |
| `Resources/CourseResource.php` | ✅ | |
| `Resources/EnrollmentResource.php` | 🐛 | Missing nested course object — Phase 1.4 |
| `Policies/CoursePolicy.php` | 🐛 | Any instructor can modify any course — Phase 1.5 |
| `Middleware/CheckFilamentRole.php` | ✅ | |
| `Middleware/CheckUserStatus.php` | ✅ | |
| `Enums/*` | ✅ | 4 enums: CourseStatus, EnrollmentStatus, UserStatus, EnrollmentSource |
| `Filament/Resources/Courses/` | ✅ | Needs video/PDF fields — Phase 3.1 |
| `Filament/Resources/Students/` | ✅ | Fully working |
| `Filament/Resources/Enrollments/` | ⚠️ | Student select may fail — Phase 3.3 |
| `Filament/Widgets/*` | 🐛 | Auto-fixes with Phase 1.2 |

### Frontend Files

| File | Status | Notes |
|---|---|---|
| `app/page.tsx` | ✅ | Landing page |
| `app/layout.tsx` | ✅ | Root layout with AuthProvider |
| `app/(auth)/login/` | ✅ | |
| `app/(auth)/register/page.tsx` | ✅ | 3-step wizard |
| `app/courses/page.tsx` | ✅ | Search + grid |
| `app/courses/[id]/page.tsx` | ✅ | Sections accordion |
| `app/courses/[id]/lectures/[lectureId]/page.tsx` | ⚠️ | Placeholder — Phase 4.3 |
| `app/dashboard/page.tsx` | ✅ | Needs role redirect — Phase 4.5 |
| `app/dashboard/instructor/page.tsx` | 🐛 | Auto-fixes with Phase 1 |
| `middleware.ts` | 🐛 | Phase 1.8 |
| `contexts/auth-context.tsx` | ✅ | |
| `lib/api.ts` | ✅ | Axios + interceptors |
| `lib/types.ts` | ✅ | Needs roles + exam types — Phases 2.7, 4.4 |
| `lib/api/courses.ts` | 🐛 | Wrong URL — Phase 1.7 |
| `lib/api/dashboard.ts` | ✅ | |

---

## 📝 Recommended Execution Order

```
Phase 1 (Hours 1-2): Critical Bug Fixes
  ├── 1.2  Fix Course::lectures() HasManyThrough
  ├── 1.3  Fix EnrollmentService::getStudentEnrollments
  ├── 1.4  Fix EnrollmentResource response format
  ├── 1.5  Fix CoursePolicy authorization
  ├── 1.6  Remove duplicate CourseService::enrollStudent
  ├── 1.7  Fix frontend API URL for my-enrollments
  └── 1.8  Fix frontend middleware

Phase 2 (Hours 3-6): Backend APIs
  ├── 2.2  Add authorize() calls in CourseController
  ├── 2.4  Add Lecture View API endpoint
  ├── 2.5  Exam System (Service + Controller + Routes)
  ├── 2.6  Enrollment Check Middleware
  └── 2.7  Add roles to /auth/me response

Phase 3 (Hours 7-10): Filament Admin
  ├── 3.1  Add video/PDF fields to CourseResource
  ├── 3.3  Fix EnrollmentResource student select
  ├── 3.5  Create ExamResource (Filament)
  └── 3.6  TA Management (basic RelationManager)

Phase 4 (Hours 11-14): Frontend
  ├── 4.3  Lecture Video Player (Bunny iframe)
  ├── 4.4  Exam Taking Page
  └── 4.5  Dashboard role-based routing

Phase 5: Deferred Features (NOT MVP)
  ├── Q&A System
  ├── Payment Integration
  ├── Assignments
  ├── Instance Config
  └── Real-time (Reverb)
```

---

## 🧪 Verification Plan

### After Phase 1 (Bug Fixes):
```bash
# 1. Instructor Dashboard API should return valid JSON
curl -s http://localhost:8000/api/dashboard/instructor \
  -H "Authorization: Bearer INSTRUCTOR_TOKEN" | python3 -m json.tool

# 2. Student Enrollments should return array
curl -s http://localhost:8000/api/my-enrollments \
  -H "Authorization: Bearer STUDENT_TOKEN" | python3 -m json.tool

# 3. Filament admin should load without errors
curl -s http://localhost:8000/admin -o /dev/null -w "%{http_code}"
# Expected: 200 or 302
```

### After Phase 2 (APIs):
```bash
# 4. Lecture view API
curl -s http://localhost:8000/api/lectures/1 \
  -H "Authorization: Bearer TOKEN" | python3 -m json.tool

# 5. Auth/me should include roles
curl -s http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer TOKEN" | python3 -m json.tool
# Expected: {"roles": ["instructor"]}
```

### End-to-End Flow Test:
1. Instructor logs into Filament (`/admin`)
2. Instructor creates a course with sections, lectures, and video IDs
3. Student registers a new account
4. Instructor approves the student from Filament
5. Instructor enrolls the student in the course from Filament
6. Student logs in and browses courses
7. Student views course details and opens a lecture
8. Student watches the video (Bunny Stream)
9. Student takes an exam and sees the result
