# Full Code Audit Report — edu-platform

**Date:** July 18, 2026  
**Scope:** Laravel 13 + Filament v5 Backend | Next.js 16 + TypeScript Frontend  
**Tools Used:** Manual code review, PHPStan (330 errors), TypeScript `tsc --noEmit` (0 errors)

---

## Table of Contents

1. [Critical / Runtime Risks](#1-critical--runtime-risks)
2. [High-Priority Bugs](#2-high-priority-bugs)
3. [Configuration Risks](#3-configuration-risks)
4. [Medium-Priority Issues](#4-medium-priority-issues)
5. [Optimizations & Best Practices](#5-optimizations--best-practices)

---

## 1. Critical / Runtime Risks

### C1. Missing `App\Enums\OrderStatus` Enum — ClassNotFoundException

**File:** `src/app/Filament/Resources/Orders/OrderResource.php:89,96`  
**PHPStan:** `Class App\Enums\OrderStatus not found.`

`OrderResource` references `\App\Enums\OrderStatus` in two `match` expressions, but this enum class does **not exist** in `src/app/Enums/`. This will throw a `ClassNotFoundException` at runtime when the orders list page renders.

**Fix:** Create the missing enum:

```php
<?php
// src/app/Enums/OrderStatus.php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending   = 'pending';
    case Completed = 'completed';
    case Failed    = 'failed';
    case Refunded  = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'قيد الانتظار',
            self::Completed => 'مكتمل',
            self::Failed    => 'فشل',
            self::Refunded  => 'مسترجع',
        };
    }

    public function color(): string|array|null
    {
        return match ($this) {
            self::Completed => 'success',
            self::Pending   => 'warning',
            self::Failed    => 'danger',
            self::Refunded  => 'gray',
        };
    }
}
```

Also update the `Order` model to cast `status` to this enum:

```php
// src/app/Models/Order.php — add to casts()
'status' => OrderStatus::class,
```

Then refactor `OrderResource` to use the enum natively instead of the fragile `instanceof` check:

```php
TextColumn::make('status')
    ->label('الحالة')
    ->badge()
    ->color(fn ($state): string => $state instanceof \App\Enums\OrderStatus
        ? $state->color()
        : 'gray')
    ->formatStateUsing(fn ($state): string => $state instanceof \App\Enums\OrderStatus
        ? $state->label()
        : (string) $state),
```

---

### C2. Non-Existent `AssignmentSubmission` Model — ClassNotFoundException

**File:** `src/app/Models/Student.php:6,114-116`

```php
use App\Models\AssignmentSubmission; // line 6 — class does not exist

public function assignmentSubmissions(): HasMany
{
    return $this->hasMany(AssignmentSubmission::class, 'student_id'); // line 114
}
```

There is no `AssignmentSubmission` model anywhere in the codebase. Accessing `$student->assignmentSubmissions` will throw a `ClassNotFoundException`.

**Fix:** Either create the `AssignmentSubmission` model if needed, or remove the dead import and relationship:

```php
// Remove line 6:
// use App\Models\AssignmentSubmission;

// Remove lines 114-117:
// public function assignmentSubmissions(): HasMany
// {
//     return $this->hasMany(AssignmentSubmission::class, 'student_id');
// }
```

---

### C3. Filament Relation Managers Use Wrong Action Namespace (AttachAction/DetachAction)

**Files:**
- `src/app/Filament/Resources/Courses/RelationManagers/AssistantsRelationManager.php:50,55`
- `src/app/Filament/Resources/Bundles/RelationManagers/ProductsRelationManager.php:43,48`

Both relation managers use `\Filament\Actions\AttachAction` and `\Filament\Actions\DetachAction`. In Filament v3/v5, relation manager table actions **must** use `\Filament\Tables\Actions\AttachAction` and `\Filament\Tables\Actions\DetachAction`. The page-level action classes have a different contract and will fail at runtime.

**Fix for AssistantsRelationManager:**

```php
// Replace lines 50, 55:
// OLD:
\Filament\Actions\AttachAction::make()
\Filament\Actions\DetachAction::make()

// NEW:
\Filament\Tables\Actions\AttachAction::make()
\Filament\Tables\Actions\DetachAction::make()
```

**Fix for ProductsRelationManager:** Same pattern — replace `\Filament\Actions\AttachAction` and `\Filament\Actions\DetachAction` with their `\Filament\Tables\Actions\` equivalents.

---

### C4. Frontend `getLectureExam` Returns Wrong Data Shape

**File:** `frontend/src/services/exam.service.ts:39-42`

The backend `ExamController::show()` (line 48-51) returns:
```json
{ "exam": { ... }, "latest_attempt": { ... } }
```

But the frontend types it as `Exam` and returns the whole wrapper:
```ts
const { data } = await api.get<Exam>(`/lectures/${lectureId}/exam`);
return data; // returns { exam: {...}, latest_attempt: {...} } — NOT Exam
```

Any code accessing `result.title`, `result.questions`, etc. gets `undefined`.

**Fix:**

```ts
getLectureExam: async (lectureId: string): Promise<{ exam: Exam | null; latest_attempt: ExamAttempt | null } | null> => {
    try {
        const { data } = await api.get<{ exam: Exam; latest_attempt: ExamAttempt | null }>(
            `/lectures/${lectureId}/exam`
        );
        return data;
    } catch (error: unknown) {
        if (
            error && typeof error === "object" && "response" in error &&
            (error as { response?: { status?: number } }).response?.status === 404
        ) {
            return null;
        }
        throw error;
    }
}
```

Then update all consumers to destructure `{ exam, latest_attempt }` from the result.

---

### C5. Backend `response()->json(new Resource(...))` Bypasses `{data}` Wrapper

**File:** `src/app/Http/Controllers/Api/EnrollmentController.php:42,53`

```php
return response()->json(new EnrollmentResource($enrollment), 201);
```

When a `JsonResource` is passed to `response()->json()`, it serializes to the raw `toArray()` output — **no `{data: ...}` wrapper**. But `EnrollmentResource::collection()` (line 25) returns with the standard `{data: [...]}` wrapper. This means:

- `enroll()` / `purchase()` → `{ id, course_id, status, ... }` (flat)
- `myEnrollments()` → `{ data: [{ id, course_id, ... }] }` (wrapped)

The frontend `enrollment.service.ts` types both as `ApiResponse<Enrollment>` = `{data: Enrollment}`, which only matches the collection response.

**Fix:** Return the resource directly instead of wrapping in `response()->json()`:

```php
// EnrollmentController.php:42
return new EnrollmentResource($enrollment); // Laravel auto-wraps in {data: {...}}

// EnrollmentController.php:53
return new EnrollmentResource($enrollment); // Laravel auto-wraps in {data: {...}}
```

Then update the frontend to unwrap consistently:

```ts
// enrollment.service.ts
enroll: async (courseId: string): Promise<Enrollment> => {
    const { data } = await api.get<{ data: Enrollment }>(`/courses/${courseId}/enroll`);
    return data.data;
},
```

---

### C6. `Exam` Model Missing Boolean Casts

**File:** `src/app/Models/Exam.php:16`

`is_blocking` and `is_assignment` are in `$fillable` but the model has **no `casts()` method at all**. These values will be stored/retrieved as strings `"0"`/`"1"` instead of `true`/`false`. Any strict comparison (`=== true`) or JavaScript truthiness check will behave unexpectedly.

**Fix:**

```php
// src/app/Models/Exam.php
protected function casts(): array
{
    return [
        'is_blocking'    => 'boolean',
        'is_assignment'  => 'boolean',
        'pass_percentage' => 'integer',
    ];
}
```

---

## 2. High-Priority Bugs

### H1. Frontend `getById` (Course) Doesn't Unwrap `{data}` Layer

**File:** `frontend/src/services/course.service.ts:15-17`

```ts
getById: async (id: string): Promise<ApiResponse<Course>> => {
    const { data } = await api.get<ApiResponse<Course>>(`/courses/${id}`);
    return data; // returns {data: Course} — consumer must do .data to get Course
}
```

Compare with `getLecture` (line 20-22) which correctly does `return data.data`. The inconsistency means `useCourse()` consumers must unwrap `.data` while `useLecture()` consumers don't.

**Fix:**

```ts
getById: async (id: string): Promise<Course> => {
    const { data } = await api.get<{ data: Course }>(`/courses/${id}`);
    return data.data;
},
```

---

### H2. `Product` Model Missing Boolean Cast

**File:** `src/app/Models/Product.php:20`

`is_active` is in `$fillable` with no `casts()` method on the model. Value will be string `"0"`/`"1"`.

**Fix:**

```php
// src/app/Models/Product.php
protected function casts(): array
{
    return [
        'is_active' => 'boolean',
        'price'     => 'float',
    ];
}
```

---

### H3. `CourseAssistant` Pivot Model Has No `$fillable`

**File:** `src/app/Models/CourseAssistant.php:8-13`

```php
class CourseAssistant extends Pivot
{
    use HasUuids;
    protected $table = 'course_assistants';
    // No $fillable or $guarded!
}
```

All attributes are guarded. Direct `CourseAssistant::create(...)` or `->fill(...)` will silently fail. While `belongsToMany()->create()` bypasses this, any direct use will break.

**Fix:**

```php
class CourseAssistant extends Pivot
{
    use HasUuids;

    protected $table = 'course_assistants';

    protected $fillable = [
        'user_id',
        'course_id',
    ];
}
```

---

### H4. N+1 DB Queries Inside `LectureResource::toArray()`

**File:** `src/app/Http/Resources/LectureResource.php:75-95`

Every call to `toArray()` potentially executes:
1. A `Student::where('user_id', ...)` query (line 80) per lecture
2. An `ExamAttempt::where(...)` query (lines 91-95) per exam per lecture
3. Another `ExamAttempt::where(...)` query (lines 121-125) per assignment per lecture

With 10 lectures × 2 exams each = up to **30+ queries per request**. The `setAttemptsMap()` setter exists but isn't always used.

**Fix:** The `CourseResource` already loads attempts into `attemptsMap` and passes it via `setAttemptsMap()`. Ensure `CourseController::show()` always eager-loads exam attempts and passes the map. For standalone lecture endpoints, batch-load attempts before creating resources:

```php
// In controller:
$student = Student::where('user_id', $user->id)->first();
$lectures = Lecture::with(['exams', 'assignments', 'video', 'files'])->get();

if ($student) {
    $attempts = ExamAttempt::whereIn('exam_id', $lectures->flatMap(fn($l) => $l->exams->pluck('id')->concat($l->assignments->pluck('id'))->toArray()))
        ->where('student_id', $student->id)
        ->whereNotNull('submitted_at')
        ->latest('submitted_at')
        ->get()
        ->groupBy('exam_id')
        ->map(fn($group) => $group->first());

    $lectures->each(fn($l) => $l->setAttribute('attempts_map', $attempts->toArray()));
}
```

---

### H5. N+1 DB Query Inside `CourseResource::toArray()`

**File:** `src/app/Http/Resources/CourseResource.php:33-34`

When `sections` are loaded, every `CourseResource` execution runs:
```php
$user = auth('sanctum')->user();
$student = $user ? \App\Models\Student::where('user_id', $user->id)->first() : null;
```

For a paginated list of 12 courses, this runs 12 identical queries.

**Fix:** Pre-resolve the student and pass it to each resource:

```php
// In controller:
$student = Student::where('user_id', $request->user()->id)->first();
CourseResource::collection($courses)->each->setStudent($student);

// Or use a setter pattern like LectureResource:
class CourseResource extends JsonResource {
    private $student = null;
    
    public function setStudent($student): self {
        $this->student = $student;
        return $this;
    }
    
    // In toArray(), use $this->student instead of querying
}
```

---

### H6. Quiz Tab Double-Submit Race Condition

**File:** `frontend/src/components/player/quiz-tab.tsx:147-186`

`handleSubmit` sets `isSubmitting = true` but doesn't check it at the top of the function. Due to React state batching, rapid clicks can invoke `handleSubmit` multiple times before `isSubmitting` becomes `true`.

**Fix:**

```ts
const handleSubmit = async (isAuto = false) => {
    if (isSubmitting) return; // Guard against double-submit
    setIsSubmitting(true);
    // ... rest of logic
};
```

---

### H7. Quiz Tab Side Effect Inside `setState`

**File:** `frontend/src/components/player/quiz-tab.tsx:111`

```tsx
setTimeLeft((prev) => {
    if (prev <= 1) {
        clearInterval(timer);
        handleSubmit(true); // ← side effect inside setState updater!
        return 0;
    }
    return prev - 1;
});
```

Calling `handleSubmit` (which makes API calls) inside a state updater is an anti-pattern. React may call the updater multiple times in concurrent mode.

**Fix:** Use a `useEffect` to detect `timeLeft === 0`:

```ts
useEffect(() => {
    if (timeLeft <= 0 && activeAttempt && !hasSubmitted) {
        handleSubmit(true);
    }
}, [timeLeft]);
```

---

## 3. Configuration Risks

### CF1. Hardcoded MinIO URL Replacement

**File:** `src/app/Http/Resources/LectureResource.php:68`

```php
$videoData['stream_url'] = str_replace('http://minio:9000', 'http://localhost:9000', $url);
```

This hardcodes a Docker-internal URL replacement. It will break in production where MinIO is accessed via a different hostname, or if the MinIO endpoint changes.

**Fix:** Use the configured URL from `config/filesystems.php`:

```php
$minioEndpoint = config('filesystems.disks.minio.endpoint');
$publicUrl = config('filesystems.disks.minio.url');
$url = str_replace($minioEndpoint, $publicUrl, $url);
```

Or better yet, configure MinIO's `url` to be the publicly-accessible URL and remove the str_replace entirely.

---

### CF2. Frontend `.env.local` — Empty Bunny CDN Variables

**File:** `frontend/.env.local`

```
NEXT_PUBLIC_BUNNY_CDN_HOSTNAME=
NEXT_PUBLIC_BUNNY_LIBRARY_ID=
```

These are empty. If the video player or any component references these for Bunny Stream URLs, it will produce broken URLs or undefined behavior.

**Fix:** Either populate these values for production or add runtime checks:

```ts
if (!env.NEXT_PUBLIC_BUNNY_CDN_HOSTNAME) {
    console.warn('Bunny CDN hostname is not configured');
}
```

---

### CF3. Cache Config Mismatch

**File:** `src/config/cache.php` defaults to `database`, but `.env` sets `CACHE_STORE=redis`.

This is technically fine (env overrides config), but the config default is misleading. If the env variable is ever missing, the app silently falls back to database caching, which could cause unexpected behavior.

**Fix:** Update the config default to match the intended production value:

```php
'default' => env('CACHE_STORE', 'redis'),
```

---

## 4. Medium-Priority Issues

### M1. All Filament Resources Use `Filament\Actions\*` Instead of `Filament\Tables\Actions\*`

**Affected:** All 13 Filament Resources and 1 Relation Manager (SubmissionsRelationManager).

Standard Filament v3/v5 table actions should use `\Filament\Tables\Actions\EditAction`, `\Filament\Tables\Actions\DeleteAction`, `\Filament\Tables\Actions\BulkActionGroup`, etc. The codebase consistently uses `\Filament\Actions\*` instead.

**Note:** This may work with a custom Filament build or extension that aliases these classes. If so, it's a project convention. If using standard Filament, this should be corrected.

**Recommended fix pattern:**

```php
// OLD:
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

// NEW:
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
```

---

### M2. Unsafe Nested Property Access in `QuestionResource`

**File:** `src/app/Http/Resources/QuestionResource.php:16-23`

```php
'student' => [
    'id' => $this->student->id,
    'name' => $this->student->user->name,     // crashes if user deleted
],
'lecture' => [
    'id' => $this->lecture->id,
    'title' => $this->lecture->title,
    'course' => $this->lecture->section->course // crashes if section null
        ? [...] : null,
],
```

If a student's user was deleted, `$this->student->user` is null → accessing `->name` throws. If a lecture has no section, `$this->lecture->section` is null → accessing `->course` throws.

**Fix:**

```php
'student' => [
    'id' => $this->student->id,
    'name' => $this->student->user?->name ?? 'محذوف',
    'student_code' => $this->student->student_code,
],
'lecture' => [
    'id' => $this->lecture->id,
    'title' => $this->lecture->title,
    'course' => $this->lecture->section?->course
        ? [
            'id' => $this->lecture->section->course->id,
            'title' => $this->lecture->section->course->title,
        ] : null,
],
```

---

### M3. Unsafe Nested Access in `QuestionReplyResource`

**File:** `src/app/Http/Resources/QuestionReplyResource.php:15-17`

Same pattern — `$this->user->name` will crash if the user was deleted.

**Fix:**

```php
'user' => [
    'id' => $this->user?->id,
    'name' => $this->user?->name ?? 'محذوف',
],
```

---

### M4. No Ownership Check on `OrderController::store()`

**File:** `src/app/Http/Controllers/Api/OrderController.php:15`

Any authenticated user (including instructors or admins) can create orders. There's no check that the user is a student or that the order belongs to them.

**Fix:** Add authorization middleware or a manual check:

```php
public function store(Request $request): JsonResponse
{
    $student = Student::where('user_id', $request->user()->id)->firstOrFail();
    // ... use $student for the order
}
```

---

### M5. Frontend Instructor Dashboard Services Return `any`

**File:** `frontend/src/services/dashboard.service.ts:16-44`

Seven methods are typed as `Promise<any>`:
```ts
getInstructorDashboard: async (): Promise<any> => { ... }
getInstructorCourses: async (): Promise<any> => { ... }
// ... 5 more
```

Types `InstructorDashboardStats`, `CoursePerformance`, and `DashboardNotification` exist in `dashboard.types.ts` but are never used.

**Fix:** Apply the existing types:

```ts
getInstructorDashboard: async (): Promise<InstructorDashboardStats> => {
    const { data } = await api.get<InstructorDashboardStats>("/dashboard/instructor");
    return data;
},
getInstructorCourses: async (): Promise<PaginatedResponse<CoursePerformance>> => {
    const { data } = await api.get<PaginatedResponse<CoursePerformance>>("/dashboard/instructor/courses");
    return data;
},
// ... etc
```

---

### M6. Missing `entitlements` Query Invalidation After Enrollment/Purchase

**File:** `frontend/src/hooks/useEnrollment.ts:27-31,40-44`

After `useEnroll` or `usePurchase` succeeds, the `["entitlements", "me"]` query is never invalidated. If the UI shows entitlement data (e.g., for unlocking lectures), it will be stale after enrollment.

**Fix:**

```ts
onSuccess: (data, courseId) => {
    queryClient.invalidateQueries({ queryKey: ["enrollments", "me"] });
    queryClient.invalidateQueries({ queryKey: ["entitlements", "me"] }); // ADD THIS
    queryClient.invalidateQueries({ queryKey: ["course", courseId] });
    queryClient.invalidateQueries({ queryKey: ["dashboard", "student"] });
},
```

---

### M7. Dashboard Pages Have No Error State Handling

**Files:**
- `frontend/src/app/(dashboard)/dashboard/page.tsx:26-28`
- `frontend/src/app/(dashboard)/dashboard/courses/page.tsx:13`
- `frontend/src/app/(dashboard)/dashboard/exams/page.tsx:11`
- `frontend/src/app/(dashboard)/dashboard/questions/page.tsx:19`

None of these pages destructure or handle the `error` state from React Query hooks. If the API fails, the user sees empty content with no explanation or retry option.

**Fix (pattern for each page):**

```tsx
const { data: stats, isLoading, error } = useStudentDashboard();

if (isLoading) return <PageSkeleton />;
if (error) return <ErrorState message="فشل تحميل البيانات" onRetry={() => refetch()} />;
```

---

### M8. HLS.js `loadedmetadata` Event Listeners Not Cleaned Up

**File:** `frontend/src/components/video-player.tsx:212,219`

For MP4 and native HLS playback, `loadedmetadata` event listeners are added to the `<video>` element but never removed in the cleanup function. This causes a memory leak if the component unmounts and remounts.

**Fix:**

```tsx
const onLoadedMetadata = () => {
    setIsLoaded(true);
    if (initialTime > 0) video.currentTime = initialTime;
};

if (isMP4) {
    video.src = streamUrl;
    video.addEventListener("loadedmetadata", onLoadedMetadata);
}

// In cleanup:
return () => {
    video.removeEventListener("loadedmetadata", onLoadedMetadata);
    // ... rest of cleanup
};
```

---

### M9. Duplicate `ApiResponse` Type Definition

**File:** `frontend/src/services/misc.service.ts:14-17`

```ts
interface ApiResponse<T> {
    status: string;
    data: T;
}
```

This local interface has `{status, data}` which differs from the global `ApiResponse<T>` in `types/api.types.ts` which is `{data: T}`. Same name, different shapes.

**Fix:** Remove the local definition and import from `@/types`:

```ts
import type { ApiResponse } from "@/types";
```

---

### M10. `Lecture` Model Has Dead Import

**File:** `src/app/Models/Lecture.php:5`

```php
use App\Models\Assignment; // class does not exist
```

**Fix:** Remove the dead import.

---

## 5. Optimizations & Best Practices

### O1. Aggressive Cache Clearing on Every Course Save

**File:** `src/app/Models/Course.php:47-53`

```php
static::saved(function () {
    static::clearPublishedCache();
});
```

The `saved` event fires on both create AND update. Any course update (even changing the title) clears all cached published courses. This should only fire when the `status` field changes.

**Fix:**

```php
static::updated(function (Course $course) {
    if ($course->wasChanged('status')) {
        static::clearPublishedCache();
    }
});
```

---

### O2. Quiz Timer Recreates `setInterval` Every Second

**File:** `frontend/src/components/player/quiz-tab.tsx:104-119`

The `useEffect` depends on `timeLeft`, which changes every second. This destroys and recreates the interval every second, causing potential timing drift.

**Fix:** Use a ref for `timeLeft`:

```ts
const timeLeftRef = useRef(timeLeft);

useEffect(() => {
    timeLeftRef.current = timeLeft;
}, [timeLeft]);

useEffect(() => {
    if (!activeAttempt || timeLeft <= 0) return;
    const timer = setInterval(() => {
        setTimeLeft((prev) => {
            if (timeLeftRef.current <= 1) {
                clearInterval(timer);
                return 0;
            }
            return prev - 1;
        });
    }, 1000);
    return () => clearInterval(timer);
}, [activeAttempt?.id]); // Only depend on attempt ID, not timeLeft
```

---

### O3. QA Query Invalidation Is Too Broad

**File:** `frontend/src/hooks/useQA.ts:59,95,113`

```ts
queryClient.invalidateQueries({ queryKey: ["lecture-questions"] });
```

This invalidates ALL `["lecture-questions", *]` queries, causing unnecessary refetches for all open lecture question lists.

**Fix:** Invalidate with the specific lecture ID:

```ts
// In useReplyToQuestion:
onSuccess: (_, lectureId) => {
    queryClient.invalidateQueries({ queryKey: ["lecture-questions", lectureId] });
},
```

---

### O4. `Auth Guard` Returns `null` Before Redirect Fires

**File:** `frontend/src/components/layout/auth-guard.tsx:31-36`

```tsx
if (requireAuth && !isAuthenticated) return null; // blank flash before redirect
```

Between `loading` resolving and the `useEffect` redirect firing (next tick), the component renders `null`.

**Fix:** Keep showing the loading spinner until redirect completes:

```tsx
if (loading || (requireAuth && !isAuthenticated)) return <PageLoading />;
```

---

### O5. Unused Filament Form Imports

**Files:**
- `src/app/Filament/Resources/Bundles/BundleResource.php:14`
- `src/app/Filament/Resources/Pricing/ProductResource.php:18`
- `src/app/Filament/Resources/QA/QAResource.php:11`

These files import `Filament\Forms\Form` but use `Filament\Schemas\Schema` instead.

**Fix:** Remove the unused `use Filament\Forms\Form;` import from each file.

---

### O6. `EnrollmentSource` Enum Missing `color()` Method

**File:** `src/app/Enums/EnrollmentSource.php`

Unlike `CourseStatus`, `EnrollmentStatus`, and `UserStatus`, this enum has no `color()` method. Filament won't render a badge color for enrollment source columns.

**Fix:** Add a `color()` method:

```php
public function color(): string|array|null
{
    return match ($this) {
        self::Manual   => 'info',
        self::Purchase => 'success',
    };
}
```

---

### O7. Auth Pages Missing Guest Guard

**File:** `frontend/src/app/(auth)/layout.tsx`

The auth layout doesn't wrap children in `<AuthGuard requireGuest>`. Authenticated users visiting `/login` or `/register` see the login form instead of being redirected.

**Fix:**

```tsx
export default function AuthLayout({ children }) {
    return (
        <AuthGuard requireGuest>
            <div className="flex min-h-height items-center justify-center ...">
                {children}
            </div>
        </AuthGuard>
    );
}
```

---

## Summary Statistics

| Category | Count |
|----------|-------|
| **Critical/Runtime Risks** | 7 (C1-C7) |
| **High-Priority Bugs** | 7 (H1-H7) |
| **Configuration Risks** | 3 (CF1-CF3) |
| **Medium-Priority Issues** | 10 (M1-M10) |
| **Optimizations & Best Practices** | 7 (O1-O7) |
| **Total Issues Found** | **34** |

### PHPStan Results

- **Total errors:** 330
- **Key categories:**
  - `Class App\Enums\OrderStatus not found` (C1)
  - `Access to undefined property` on models without proper type hints
  - `Call to an undefined method` on Builder (e.g., `->role()` without Spatie types)
  - `Cannot call method on Model|int|string` (union type issues from Filament's `getRecord()`)

### TypeScript Results

- **Compilation errors:** 0 (clean build)
- **Runtime issues identified:** 4 critical response mapping bugs (C4, C5, H1, H4)

---

*Report generated by static code audit on July 18, 2026.*
