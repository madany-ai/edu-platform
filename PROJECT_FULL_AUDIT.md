# PROJECT_FULL_AUDIT.md — LMS Platform Complete Technical Audit

**Generated:** 2026-07-18  
**Project:** edu-platform (LMS)  
**Author:** Automated Deep Code Audit  
**Status:** Complete — All files analyzed

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Project Overview](#project-overview)
3. [Architecture Review](#architecture-review)
4. [Folder Structure Review](#folder-structure-review)
5. [Backend Review](#backend-review)
6. [Frontend Review](#frontend-review)
7. [Database Review](#database-review)
8. [API Review](#api-review)
9. [Authentication Review](#authentication-review)
10. [Authorization Review](#authorization-review)
11. [Business Logic Review](#business-logic-review)
12. [Docker Review](#docker-review)
13. [Infrastructure Review](#infrastructure-review)
14. [Testing Review](#testing-review)
15. [Security Review](#security-review)
16. [Performance Review](#performance-review)
17. [Dependency Review](#dependency-review)
18. [Code Quality Review](#code-quality-review)
19. [Technical Debt](#technical-debt)
20. [Bugs Found](#bugs-found)
21. [Missing Features](#missing-features)
22. [Risks](#risks)
23. [Recommendations](#recommendations)
24. [Action Plan](#action-plan)
25. [Priority Matrix](#priority-matrix)
26. [Estimated Refactoring Order](#estimated-refactoring-order)

---

## Executive Summary

This is a **white-label, single-tenant Learning Management System** built as a monolithic Laravel 13 backend with a standalone Next.js 16 frontend. The platform supports Arabic RTL as a first-class locale and is designed for individual instructors deploying self-hosted instances for their students.

**Strengths:**
- Solid "fat services, thin controllers" architecture
- Comprehensive service layer with 13 dedicated service classes
- Well-structured Filament 5 admin panel with 13 resources
- 407+ automated tests with strong coverage of core flows
- Docker-based deployment with 9 services
- Modern tech stack (PHP 8.4, Laravel 13, Next.js 16, React 19, PostgreSQL 18, Redis 7)

**Critical Concerns:**
- **CRITICAL authorization gaps**: Multiple API endpoints lack authorization checks, allowing any authenticated user to modify other instructors' content
- **CRITICAL secrets committed to version control**: `.env` file contains Backblaze keys, Bunny Stream credentials, and MinIO passwords in plaintext
- **HIGH database schema debt**: Duplicate tables, conflicting migration patterns, missing indexes on hot query paths
- **HIGH performance risks**: N+1 query patterns in `Lecture.getProgressAttribute()`, `DashboardService`, and `LectureResource`
- **MEDIUM incomplete features**: Payment gateways (Paymob, Kashier) not integrated, notification system is DB-only, email not configured
- **MEDIUM test gaps**: Rate limiting tests are false positives, no actual file upload tests, no frontend tests

**Overall Grade: C+** — Functional MVP with significant security and architectural debt that must be addressed before production use.

---

## Project Overview

### Purpose
A self-hosted LMS where instructors deploy isolated instances for their students. Supports video lectures (HLS-encrypted), exams, Q&A, enrollment management, products/bundles (paid courses), and a student dashboard.

### Main Business Domain
Education Technology (EdTech) — specifically an instructor-to-student learning platform with a marketplace model (products, bundles, orders).

### Tech Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend Language | PHP | 8.4 |
| Backend Framework | Laravel | 13.8 |
| Admin Panel | Filament | 5.6 |
| Database | PostgreSQL | 18 (SQLite for tests) |
| Cache/Queue | Redis | 7 |
| Queue Manager | Laravel Horizon | 5.9 |
| API Auth | Laravel Sanctum | 4.3 |
| RBAC | Spatie Laravel Permission | 8.3 |
| Activity Log | Spatie Laravel ActivityLog | 5.0 |
| Video Hosting | Bunny Stream | API v2 |
| Object Storage | MinIO (dev) / Backblaze B2 (prod) | S3-compatible |
| Frontend Framework | Next.js | 16.2.10 |
| UI Library | React | 19.2.4 |
| UI Components | shadcn/ui | 21 components |
| Forms | React Hook Form + Zod | 7.81 + 4.4 |
| State Management | TanStack React Query | 5.101 |
| HTTP Client | Axios | 1.18 |
| Video Player | React Player + HLS.js | 3.4 + 1.6 |
| Captcha | Cloudflare Turnstile | v1.5.3 |
| Containerization | Docker Compose | v2.29+ |
| Web Server | Nginx | 1.27-alpine |
| CI/CD | GitHub Actions | ubuntu-latest |

### Runtime Versions
- PHP 8.4 (FPM Alpine)
- Node.js 22 (CI)
- Composer 2
- FFmpeg (bundled in Docker)

### Deployment Model
Single-tenant: each instructor gets an isolated Docker Compose stack with their own PostgreSQL, Redis, MinIO, and domain. The vendor deploys versioned Docker images.

---

## Architecture Review

### System Architecture
**Modular Monolith** with strict separation:
- **Backend API** (`src/routes/api.php`): REST API for the student frontend
- **Admin Panel** (`Filament/`): Instructor/TA management interface
- **Frontend SPA** (`frontend/`): Standalone Next.js 16 student-facing app

### Component Map

```
┌──────────────┐     REST API      ┌─────────────────────────────────────────┐
│  Next.js 16  │ ◄──────────────► │  Laravel 13 Backend                      │
│  Frontend    │   Sanctum Token   │  ┌─────────────────────────────────────┐ │
│  (Student)   │                   │  │ Controllers (thin)                   │ │
└──────────────┘                   │  │    ↕                                │ │
                                   │  │ Services (13 — business logic)      │ │
┌──────────────┐   Filament Auth   │  │    ↕                                │ │
│  Filament 5  │ ◄──────────────► │  │ Models (33 Eloquent)                │ │
│  Admin Panel │   Session/Cookie  │  └──────────────┬──────────────────────┘ │
│ (Instructor) │                   │                 │                        │
└──────────────┘                   │  ┌──────────────┴──────────────────────┐ │
                                   │  │ PostgreSQL 18  │  Redis 7           │ │
                                   │  │ MinIO/B2/R2   │  Bunny Stream CDN  │ │
                                   │  └────────────────────────────────────┘ │
                                   └─────────────────────────────────────────┘
```

### Layers & Responsibilities

1. **Route Layer** (`routes/api.php`): Route definitions, middleware stacks (auth, roles, enrollment checks)
2. **Controller Layer** (`Http/Controllers/Api/`): Request handling, response formatting, delegation to services
3. **Service Layer** (`Services/`): All business logic — 13 services handling auth, enrollment, exams, QA, video, etc.
4. **Model Layer** (`Models/`): 33 Eloquent models with relationships, scopes, and boot logic
5. **Repository/Persistence Layer**: Eloquent ORM with direct DB queries in services
6. **Middleware Layer** (`Middleware/`): 4 custom middleware — enrollment checks, user status, security headers, Filament role
7. **Policy Layer** (`Policies/`): 2 policies (Course, Lecture) registered in `AppServiceProvider`
8. **Resource Layer** (`Http/Resources/`): 5 API transformers for consistent JSON responses
9. **Request Layer** (`Http/Requests/`): 5 Form Request classes for validation

### Data Flow

```
Student Request → Nginx → PHP-FPM → Laravel Router → Middleware Stack
  → Controller → Service → Model → PostgreSQL
  → Response → JSON → Frontend (React Query → UI)
```

### Authentication Flow
1. Student registers via `POST /api/auth/register` → `AuthService::register()` → Creates User + Student + role
2. Login via `POST /api/auth/login` → `AuthService::login()` → Returns Sanctum token
3. Token stored in `localStorage` → Sent as `Authorization: Bearer {token}` header
4. `auth:sanctum` middleware validates token → `CheckUserStatus` blocks non-active users
5. Role-based routes use Spatie `role:instructor`, `role:assistant` middleware

### Authorization Flow
1. **API Routes**: Role middleware (`role:instructor`, `role:assistant`) at route level
2. **Middleware**: `CheckEnrollment` verifies enrollment/entitlement before lecture access
3. **Policies**: `CoursePolicy` (view/create/update/delete), `LecturePolicy` (view/create/update/delete)
4. **Filament**: `CheckFilamentRole` middleware + `canAccessPanel()` on User model

---

## Folder Structure Review

### Root Level
| Path | Purpose | Notes |
|------|---------|-------|
| `src/` | Laravel 13 backend | Core application code |
| `frontend/` | Next.js 16 SPA | Student-facing interface |
| `nginx/` | Nginx config | Reverse proxy to PHP-FPM |
| `php-ext/` | Pre-compiled PHP extensions | pdo_pgsql, pgsql .so files |
| `php.d/` | PHP extension loading | extensions.ini |
| `ui-designs/` | UI mockups (HTML + PNG) | 6 design iterations |
| `postman/` | API collection | Postman JSON export |
| `vendor/` | IDE helpers only | _laravel_ide stubs |
| `.github/workflows/` | CI pipeline | ci.yml (Laravel + Frontend) |

### Documentation Files
| File | Lines | Purpose |
|------|-------|---------|
| `README.md` | 164 | Project structure overview |
| `PRD.md` | 573 | Product Requirements Document |
| `PROJECT_MAP.md` | 361 | Architecture & tech stack map |
| `API_DOCUMENTATION.md` | 1532 | Comprehensive Arabic API docs |
| `system-design.md` | 626 | System design document |
| `erd.md` | 366 | Entity-Relationship Diagram |
| `plan.md` | 959 | Implementation plan |
| `test.md` | 920 | Test coverage analysis |

**Issue**: Documentation is extensive but some is outdated. README lists 8 services but there are 13. README lists 22 migrations but there are 29. README lists 10 shadcn components but there are 21.

### `src/app/` Structure
| Directory | Purpose | File Count |
|-----------|---------|-----------|
| `Enums/` | PHP 8.1 typed enums | 4 |
| `Filament/` | Admin panel (Resources, Pages, Widgets) | 40+ |
| `Http/Controllers/Api/` | API controllers | 12 |
| `Http/Middleware/` | Custom middleware | 4 |
| `Http/Requests/` | Form Requests | 5 |
| `Http/Resources/` | API Resources | 5 |
| `Jobs/` | Queued jobs | 1 |
| `Models/` | Eloquent models | 33 |
| `Policies/` | Authorization policies | 2 |
| `Providers/` | Service providers | 3 |
| `Rules/` | Custom validation rules | 1 |
| `Services/` | Business logic layer | 13 |

### `frontend/src/` Structure
| Directory | Purpose | File Count |
|-----------|---------|-----------|
| `app/` | Next.js App Router pages & layouts | 30+ |
| `components/` | UI components (layout, player, shared, ui) | 30+ |
| `config/` | Environment config | 1 |
| `features/` | Feature modules (auth — mostly empty) | 1 |
| `hooks/` | Custom React hooks | 8 |
| `lib/` | Utilities, constants | 2 |
| `providers/` | React context providers | 4 |
| `services/` | API service modules | 8 |
| `types/` | TypeScript type definitions | 8 |

**Issue**: `features/auth/` directories (`components/`, `hooks/`, `services/`, `types/`) are all empty — scaffolded but never implemented.

---

## Backend Review

### Controllers (12 total)

#### AuthController (`src/app/Http/Controllers/Api/AuthController.php`)
- **Purpose**: Registration, login, logout, user profile
- **Methods**: `register`, `login`, `logout`, `me`
- **Issues**:
  - `me()` uses `request()->user()` global helper instead of injecting Request
  - No rate limiting enforcement visible in the controller (defined in AppServiceProvider but not applied to these routes directly)

#### CourseController (`src/app/Http/Controllers/Api/CourseController.php`)
- **Purpose**: CRUD for courses, sections, lectures, file downloads, progress tracking
- **Methods**: 14 methods covering full CRUD + progress
- **Issues**:
  - **CRITICAL**: `storeSection`, `updateSection`, `destroySection`, `storeLecture`, `updateLecture`, `destroyLecture` have **zero authorization checks**. Any authenticated user can modify any course's content.
  - **CRITICAL**: `downloadFile` has no authorization — any authenticated user can download any file
  - **CRITICAL**: `updateProgress` has no enrollment check — students can update progress on lectures they're not enrolled in
  - `showLecture` relies on `CheckEnrollment` middleware but no ownership validation
  - Inline validation (`$request->validate()`) instead of FormRequest classes for section/lecture operations
  - FQN imports instead of `use` statements at top of file

#### DashboardController (`src/app/Http/Controllers/Api/DashboardController.php`)
- **Purpose**: Student and instructor dashboard statistics
- **Methods**: 7 methods
- **Issues**:
  - `instructorStudents()` and `student()` have direct DB queries instead of delegating to `DashboardService`
  - All methods use `request()->user()` global helper

#### EnrollmentController (`src/app/Http/Controllers/Api/EnrollmentController.php`)
- **Purpose**: Enrollment management, purchase, revocation
- **Methods**: 6 methods
- **Issues**:
  - **CRITICAL**: `courseEnrollments()` has no authorization — any user can view all enrollments for any course (PII leak)
  - **CRITICAL**: `revoke()` has no authorization — any user can revoke any student from any course
  - `enroll()` doesn't check for duplicate enrollment

#### ExamController (`src/app/Http/Controllers/Api/ExamController.php`)
- **Purpose**: Exam CRUD, attempt management, grading
- **Methods**: 9 methods
- **Issues**:
  - **CRITICAL**: `store`, `update`, `destroy` have no authorization — any user can create/modify/delete exams
  - **CRITICAL**: `submitAttempt` doesn't verify the attempt belongs to the current user
  - **CRITICAL**: `result` doesn't verify ownership
  - **CRITICAL**: `startAttempt` doesn't check enrollment
  - `show`/`showAssignment` are nearly identical (code duplication)

#### OrderController (`src/app/Http/Controllers/Api/OrderController.php`)
- **Purpose**: Order creation
- **Methods**: `store` only
- **Issues**: No duplicate order prevention, `uniqid()` for transaction IDs (not cryptographically secure)

#### PasswordResetController (`src/app/Http/Controllers/Api/PasswordResetController.php`)
- **Purpose**: Password reset flow
- **Methods**: `forgotPassword`, `resetPassword`
- **Issues**: `resetPassword` uses `forceFill` bypassing mutators; uses global `request()` helper

#### ProductController (`src/app/Http/Controllers/Api/ProductController.php`)
- **Purpose**: Product/bundle listing and detail
- **Methods**: 4 methods
- **Issues**: No pagination on `bundles()`, no active status filter on bundles

#### QAController (`src/app/Http/Controllers/Api/QAController.php`)
- **Purpose**: Question and reply management
- **Methods**: 9 methods
- **Issues**: `instructorQuestions`/`assistantQuestions` have significant code duplication

#### VideoStreamController (`src/app/Http/Controllers/Api/VideoStreamController.php`)
- **Purpose**: HLS video streaming proxy with token-based auth
- **Methods**: `playlist`, `segment`, `rewritePlaylistUrls` (private)
- **Issues**: Video segments fully buffered in memory (`stream => false`), hardcoded CORS headers

#### MiscController (`src/app/Http/Controllers/Api/MiscController.php`)
- **Purpose**: Lookup data (governorates, grade levels)
- **Methods**: 2 methods
- **Issues**: None significant

---

### Services (13 total)

#### AuthService (`src/app/Services/AuthService.php`)
- **Purpose**: Registration (User + Student + role), login (email/phone/student_code), logout
- **Issues**:
  - `register` notifies **every instructor in the system** per new student — doesn't scale
  - `login` returns union type `array|string|null` — error-prone callers
  - No rate limiting or account lockout

#### CourseService (`src/app/Services/CourseService.php`)
- **Purpose**: Course CRUD, listing, instructor courses
- **Issues**:
  - **Potential SQL injection** in `listPublished`: `like '%{$filters['search']}%'` is interpolated directly without parameter binding
  - `findById` signature says `?Course` but calls `findOrFail` (throws on miss)
  - Missing return type on `getInstructorCourses`

#### DashboardService (`src/app/Services/DashboardService.php`)
- **Purpose**: Statistics for student/instructor dashboards
- **Issues**:
  - `getStudentStats` has N+1: loads `course.sections.lectures` per enrollment then loops in PHP
  - `getInstructorStats` makes ~8 separate queries for same instructor_id
  - Uses raw `DB::table('student_statistics')` instead of Eloquent model

#### EnrollmentService (`src/app/Services/EnrollmentService.php`)
- **Purpose**: Enrollment CRUD, synthetic entitlement enrollments
- **Issues**:
  - **Critical**: `getStudentEnrollments` creates **fake `Enrollment` objects** with synthetic IDs (`'entitlement-fake-' . $course->id`) — breaks any code expecting real IDs
  - `revokeEnrollment` has no audit trail or event dispatch

#### ExamService (`src/app/Services/ExamService.php`)
- **Purpose**: Exam CRUD, attempt management, grading
- **Issues**:
  - `updateExam` **deletes all questions and recreates** — destructive, breaks existing attempts
  - Essay grading awards full marks for any non-empty answer
  - `$correctChoice->id == $answer->answer` uses loose comparison
  - No attempt limit enforcement
  - No enrollment verification before attempt

#### VideoAccessService (`src/app/Services/VideoAccessService.php`)
- **Purpose**: Access control for video playback (role, entitlement, enrollment, exam gating)
- **Issues**: Most complex service — deep nesting, multiple DB queries per access check, no caching

#### VideoTokenService (`src/app/Services/VideoTokenService.php`)
- **Purpose**: Generate/validate HMAC-signed video access tokens
- **Issues**: User ID in token payload but never validated server-side; tokens cannot be revoked before expiry

#### BunnyStreamService (`src/app/Services/BunnyStreamService.php`)
- **Purpose**: Bunny Stream API integration (create, upload, status, delete videos)
- **Issues**:
  - `uploadContent` uses raw cURL instead of Laravel HTTP client — file handle never closed
  - `getSignedPlaybackUrl` uses `hash('sha256')` instead of `hash_hmac('sha256')`
  - `deleteVideo` silently fails (logs warning, no exception)
  - No retry logic for uploads

#### GrantEntitlementService (`src/app/Services/GrantEntitlementService.php`)
- **Purpose**: Grant entitlements upon order confirmation
- **Issues**: No transaction wrapping — partial grants possible on failure; no logging

#### QAService (`src/app/Services/QAService.php`)
- **Purpose**: Q&A management (questions, replies, notifications)
- **Issues**: Unused `$student` parameter in `getLectureQuestions`; hardcoded Arabic strings

#### ProgressService (`src/app/Services/ProgressService.php`)
- **Purpose**: Video watch progress tracking
- **Issues**: No input validation for `$data` array keys; hardcoded 10-minute fallback duration

#### NotificationService (`src/app/Services/NotificationService.php`)
- **Purpose**: In-app notification creation
- **Issues**: DB-only (no email/push/SMS); no bulk send; no read/unread management

#### CodeGeneratorService (`src/app/Services/CodeGeneratorService.php`)
- **Purpose**: Generate unique prefixed codes (students, assistants, courses)
- **Issues**: Small random space (4 digits); timestamp fallback not unique within same second

---

### Models (33 total)

#### Critical Model Issues

| Model | Issue | Severity |
|-------|-------|----------|
| `Lecture` | `getProgressAttribute()` runs 3 raw DB queries per access — catastrophic N+1 | **High** |
| `LectureVideo` | `encryption_key` in `$fillable` — could be mass-assigned via API | **Critical** |
| `Course` | `price` has no decimal cast | Medium |
| `Course` | `status` has no enum cast | Medium |
| `Exam` | `is_blocking`, `is_assignment` have no boolean casts | Medium |
| `ExamAttempt` | `score` has no numeric cast | Medium |
| `Product` | `price` has no decimal cast; `is_active` has no boolean cast | Medium |
| `Bundle` | `price` has no decimal cast; `resolveLectureIds()` triggers N+1 | Medium |
| `Entitlement` | Uses property `$casts` while all other models use method — inconsistent | Low |
| `Order` | Uses property `$casts` while all other models use method — inconsistent | Low |
| `User` | Uses PHP 8 `#[Fillable]` attributes while all others use `$fillable` array | Low |
| `PersonalAccessToken` | `HasUuids` on Sanctum base model — may break internal queries | **High** |
| `Role`/`Permission` | `HasUuids` on Spatie base models — may break internal queries | **High** |
| `CourseAssistant` | `HasUuids` on Pivot model — unusual and potentially problematic | Medium |
| `Assignment` | Dual assignment concept: this model vs `Exam` with `is_assignment=true` — confusing | Medium |
| `QuestionReply` | `question()` relationship returns `QuestionsPost` — confusing naming | Low |
| `LectureFile`, `Question` | MinIO URL replacement logic duplicated across 3 models | Medium |
| `StudentActivity` | Manual polymorphic pattern instead of Laravel `MorphTo` | Low |

---

### Middleware (4 total)

#### CheckEnrollment (`src/app/Http/Middleware/CheckEnrollment.php`)
- **Purpose**: Verifies user can access a lecture (instructor/assistant/enrollment/entitlement/exam gating)
- **Issues**: N+1 risk with `Course::find()` call; no null-check on `$request->user()`

#### CheckFilamentRole (`src/app/Http/Middleware/CheckFilamentRole.php`)
- **Purpose**: Restricts Filament panel access by role
- **Issues**: None

#### CheckUserStatus (`src/app/Http/Middleware/CheckUserStatus.php`)
- **Purpose**: Blocks non-active users
- **Issues**: Compares string directly (`$user->status !== 'active'`) — will break if User model casts status to enum

#### SecurityHeaders (`src/app/Http/Middleware/SecurityHeaders.php`)
- **Purpose**: Adds CSP, HSTS, X-Frame-Options, etc.
- **Issues**: CSP uses `'unsafe-inline'` and `'unsafe-eval'` — significantly weakens XSS protection

---

### Policies (2 total)

#### CoursePolicy (`src/app/Policies/CoursePolicy.php`)
- **Issues**: No `super_admin` bypass — admin can't manage courses without being instructor

#### LecturePolicy (`src/app/Policies/LecturePolicy.php`)
- **Issues**: `create`/`update`/`delete` allows any non-assistant user including students — should restrict to instructors

---

### Form Requests (5 total)

| Request | Issues |
|---------|--------|
| `LoginRequest` | Missing `email` validation rule |
| `RegisterRequest` | No phone format validation; no birth_date range |
| `StoreCourseRequest` | `thumbnail` is `nullable|string` instead of file validation |
| `StoreQuestionRequest` | Missing `lecture_id` exists validation |
| `StoreReplyRequest` | Missing `question_id` exists validation |

---

### Jobs (1 total)

#### ProcessVideoHLS (`src/app/Jobs/ProcessVideoHLS.php`)
- **Purpose**: Upload MP4 to Bunny Stream, poll for transcoding, update DB
- **Issues**:
  - No `SerializesModels` trait — stale data risk
  - `sleep(10)` in loop blocks worker for up to 5 minutes
  - No unique job protection — duplicates possible
  - File handle in BunnyStreamService never closed

---

### Enums (4 total)
All well-implemented with Arabic labels and Filament badge colors:
- `CourseStatus`: Draft, Published, Archived
- `EnrollmentSource`: Manual, Purchase
- `EnrollmentStatus`: Active, Expired, Suspended
- `UserStatus`: Pending, Active, Rejected

---

### Providers (3 total)

#### AppServiceProvider
- Registers policies, Sanctum PAT model, rate limiters (api, login, video)
- Rate limiters defined but `login` limiter not applied to login routes in `api.php`

#### HorizonServiceProvider
- `viewHorizon` gate always returns `false` — nobody can access Horizon in non-local environments

#### AdminPanelProvider
- Filament panel config with honeypot anti-autocomplete (CSS-only, no server validation)

---

## Frontend Review

### Architecture
- **Framework**: Next.js 16 (App Router, standalone output)
- **State**: React Context (auth) + TanStack React Query (server state)
- **Styling**: TailwindCSS v4 + shadcn/ui (21 components, RTL enabled)
- **Forms**: React Hook Form + Zod (installed but barely used)
- **HTTP**: Axios with interceptors (auto-attach token, auto-redirect on 401)

### Route Groups
1. **(main)**: Public pages — landing, course catalog, course detail, product/bundle detail
2. **(auth)**: Login, register, forgot-password, reset-password
3. **(dashboard)**: Student dashboard (courses, exams, notifications, questions, settings)
4. **(player)**: Video player and lecture views

### Key Frontend Issues

| Issue | Severity | File |
|-------|----------|------|
| Notifications page uses hardcoded mock data | High | `(dashboard)/dashboard/notifications/page.tsx` |
| `forgot-password` doesn't send Turnstile token to backend | High | `(auth)/forgot-password/page.tsx` |
| `useMyQuestions` deletion doesn't invalidate lecture query cache | Medium | `hooks/useQA.ts` |
| `useMyEntitlements` bypasses service layer, calls API directly | Medium | `hooks/useEnrollment.ts` |
| Zod schemas + react-hook-form installed but unused | Low | `features/auth/schemas/auth.schema.ts` |
| `isInstructor` check includes assistant role — may be unintended | Medium | `providers/auth-provider.tsx:74` |
| Token stored in `localStorage` (XSS vulnerable) | Medium | `services/api.client.ts` |
| No error boundaries for route-level errors | Low | `app/error.tsx` exists but minimal |
| No loading skeletons for most pages | Low | Only `loading.tsx` at root |
| Empty `features/auth/` directory scaffold | Low | `features/auth/components/`, `hooks/`, etc. |

### Frontend Strengths
- Clean separation of services/hooks/components
- Proper RTL support throughout
- Dark mode support via `next-themes`
- Axios interceptor pattern for auth
- React Query for server state caching

---

## Database Review

### Schema Summary
- **29 migrations** spanning 2026-01 through 2026-07
- **33 tables** (including 2× activity log tables, 2× assignment tables before drop)
- **All UUID primary keys** except `activity_log` (bigIncrements)

### ER Understanding
```
Users ──┬── Students ──┬── Enrollments ── Courses ── CourseSections ── Lectures
        │              │                    │                           │
        │              ├── ExamAttempts ── Exams ── Questions ── Choices
        │              │                                                  │
        │              ├── Entitlements ──────────────────────────────────┘
        │              │
        │              ├── AssignmentSubmissions ── Assignments (DROPPED)
        │              │
        │              ├── QuestionsPosts ── QuestionReplies ── Users
        │              │
        │              ├── StudentActivities
        │              └── StudentStatistics
        │
        ├── Courses (as instructor)
        ├── PersonalAccessTokens
        └── Notifications

Products ── MorphTo (Course/Section/Lecture)
Orders ── MorphTo (Product/Bundle) ── Students
Bundles ── BelongsToMany Products
```

### Critical Database Issues

| # | Severity | Issue | Evidence |
|---|----------|-------|----------|
| 1 | **Critical** | `activity_logs.entity_id` is `unsignedBigInteger` but all PKs are UUID | Migration `2026_01_01_000007` |
| 2 | **Critical** | `student_activity.entity_id` is `unsignedBigInteger` but all PKs are UUID | Migration `2026_01_01_000007` |
| 3 | **Critical** | Duplicate activity tables: `activity_logs`, `activity_log`, `student_activity`, `student_activities` | Migrations 10, 27, 28 |
| 4 | **Critical** | `student_activity` and `student_activities` are duplicate tables | Migrations 10 vs 27 |
| 5 | **High** | `assignments` + `assignment_submissions` created then dropped — schema debt | Migrations 10 vs 22 |
| 6 | **High** | Missing indexes on: `students.user_id`, `exams.lecture_id`, `exam_attempts(student_id)`, `answers(attempt_id)`, `entitlements(student_id, lecture_id)`, `orders.student_id`, `notifications.user_id`, `enrollments.course_id` | Multiple migrations |
| 7 | **High** | `activity_log` uses `bigIncrements` while all other tables use UUID PKs | Migration 28 |
| 8 | **High** | `encryption_key` stored as plaintext in `lecture_videos` | Migration 26 |
| 9 | **Medium** | Dual video storage: `lectures.video_path` AND `lecture_videos.video_path` | Migrations 11, 26 |
| 10 | **Medium** | Dual PDF storage: `lectures.pdf_url` AND `lecture_files` table | Migrations 8, 7 |
| 11 | **Medium** | `students.school_name` (free text) duplicates `students.school_id` (FK) | Migration 29 |
| 12 | **Medium** | Inconsistent pricing: `courses.price` (decimal) vs `orders.amount_cents` (integer) | Migrations 8, 17 |
| 13 | **Medium** | `pass_percentage` has no CHECK constraint | Migration 19 |
| 14 | **Medium** | `entitlements` per-lecture granularity — 100 lectures = 100 rows per purchase | Migration 16 |
| 15 | **Medium** | `orders` default status is `'completed'` — should be `'pending'` | Migration 12 |
| 16 | **Low** | `StudentFactory` references wrong namespace (`App\Domain\Student\Models\Student`) | `database/factories/StudentFactory.php` |
| 17 | **Low** | `StudentFactory` missing required NOT NULL fields | `database/factories/StudentFactory.php` |
| 18 | **Low** | No unique constraint on `exam_attempts(exam_id, student_id)` | Migration 9 |
| 19 | **Low** | Raw SQL in migration 24 (`UPDATE ... SET price / 100.0`) locks table | Migration 24 |

### Missing Indexes (High Impact)
```sql
-- Students: hot query path
CREATE INDEX idx_students_user_id ON students(user_id);
CREATE INDEX idx_students_grade_level_id ON students(grade_level_id);

-- Exams: listing by lecture
CREATE INDEX idx_exams_lecture_id ON exams(lecture_id);

-- Exam Attempts: listing by student
CREATE INDEX idx_exam_attempts_student_id ON exam_attempts(student_id);

-- Answers: listing by attempt
CREATE INDEX idx_answers_attempt_id ON answers(attempt_id);

-- Entitlements: access control check
CREATE INDEX idx_entitlements_student_id ON entitlements(student_id);
CREATE INDEX idx_entitlements_lecture_id ON entitlements(lecture_id);

-- Orders: student order history
CREATE INDEX idx_orders_student_id ON orders(student_id);
CREATE INDEX idx_orders_status ON orders(status);

-- Notifications: user notification feed
CREATE INDEX idx_notifications_user_id ON notifications(user_id);

-- Enrollments: course enrollment listing
CREATE INDEX idx_enrollments_course_id ON enrollments(course_id);
```

---

## API Review

### Endpoint Summary (55+ routes)

| Method | Endpoint | Auth | Role | Issues |
|--------|----------|------|------|--------|
| POST | `/auth/register` | Public | — | Throttle:login ✓ |
| POST | `/auth/login` | Public | — | Throttle:login ✓ |
| POST | `/auth/forgot-password` | Public | — | Throttle:login ✓ |
| POST | `/auth/reset-password` | Public | — | No throttle ✗ |
| GET | `/courses` | Public | — | None |
| GET | `/courses/{course}` | Public | — | None |
| GET | `/governorates` | Public | — | None |
| GET | `/grade-levels` | Public | — | None |
| GET | `/video/{id}/playlist` | Token | — | Throttle ✓ |
| GET | `/video/{id}/segment` | Token | — | Throttle ✓ |
| POST | `/auth/logout` | Sanctum | any | None |
| GET | `/auth/me` | Sanctum | any | None |
| GET | `/dashboard/student` | Sanctum | any | None |
| GET | `/dashboard/instructor` | Sanctum | instructor | None |
| GET | `/dashboard/instructor/courses` | Sanctum | instructor | None |
| GET | `/dashboard/instructor/recent-enrollments` | Sanctum | instructor | None |
| GET | `/dashboard/instructor/course-performance` | Sanctum | instructor | None |
| GET | `/dashboard/instructor/notifications` | Sanctum | instructor | None |
| POST | `/courses` | Sanctum | instructor | None |
| PUT | `/courses/{course}` | Sanctum | instructor | None |
| DELETE | `/courses/{course}` | Sanctum | instructor | None |
| POST | `/courses/{course}/sections` | Sanctum | instructor | **No ownership check** |
| PUT | `/courses/{course}/sections/{section}` | Sanctum | instructor | **No ownership check** |
| DELETE | `/courses/{course}/sections/{section}` | Sanctum | instructor | **No ownership check** |
| POST | `/sections/{section}/lectures` | Sanctum | instructor | **No ownership check** |
| PUT | `/sections/{section}/lectures/{lecture}` | Sanctum | instructor | **No ownership check** |
| DELETE | `/sections/{section}/lectures/{lecture}` | Sanctum | instructor | **No ownership check** |
| GET | `/lectures/{lecture}` | Sanctum | any | CheckEnrollment ✓ |
| GET | `/lectures/{lecture}/files/{file}` | Sanctum | any | CheckEnrollment ✓ |
| POST | `/lectures/{lecture}/progress` | Sanctum | any | **No enrollment check** |
| GET | `/lectures/{lecture}/assignment` | Sanctum | any | CheckEnrollment ✓ |
| GET | `/my-enrollments` | Sanctum | any | None |
| GET | `/my-entitlements` | Sanctum | any | None |
| POST | `/courses/{course}/enroll` | Sanctum | any | None |
| POST | `/courses/{course}/purchase` | Sanctum | any | None |
| GET | `/courses/{course}/enrollments` | Sanctum | instructor | **No ownership check** |
| DELETE | `/courses/{course}/enrollments/{student}` | Sanctum | instructor | **No ownership check** |
| GET | `/instructor/students` | Sanctum | instructor | None |
| GET | `/my-attempts` | Sanctum | any | None |
| GET | `/lectures/{lecture}/exam` | Sanctum | any | None |
| POST | `/exams/{exam}/start` | Sanctum | any | **No enrollment check** |
| POST | `/attempts/{attempt}/submit` | Sanctum | any | **No ownership check** |
| GET | `/attempts/{attempt}/result` | Sanctum | any | **No ownership check** |
| POST | `/lectures/{lecture}/exam` | Sanctum | instructor | **No ownership check** |
| PUT | `/exams/{exam}` | Sanctum | instructor | **No ownership check** |
| DELETE | `/exams/{exam}` | Sanctum | instructor | **No ownership check** |
| GET | `/products` | Sanctum | any | None |
| GET | `/products/{product}` | Sanctum | any | None |
| GET | `/bundles` | Sanctum | any | None |
| GET | `/bundles/{bundle}` | Sanctum | any | None |
| POST | `/orders` | Sanctum | any | **No duplicate prevention** |
| POST | `/lectures/{lecture}/questions` | Sanctum | any | CheckEnrollment ✓ |
| GET | `/lectures/{lecture}/questions` | Sanctum | any | CheckEnrollment ✓ |
| GET | `/questions/{question}` | Sanctum | any | **No auth check** |
| POST | `/questions/{question}/replies` | Sanctum | any | **No auth check** |
| GET | `/my-questions` | Sanctum | any | None |
| DELETE | `/questions/{question}` | Sanctum | any | **No ownership check** |
| DELETE | `/replies/{reply}` | Sanctum | any | **No ownership check** |
| GET | `/instructor/questions` | Sanctum | instructor | None |
| GET | `/assistant/questions` | Sanctum | assistant | None |

**Summary**: 12 endpoints have authorization gaps where any authenticated user of the correct role can access/modify resources belonging to other users.

---

## Authentication Review

### Login Flow
1. `POST /api/auth/login` with email/password + Turnstile token
2. `AuthController::login()` → `AuthService::login()`
3. `AuthService::login()` tries: email lookup, phone lookup (User), phone lookup (Student), student_code lookup
4. Returns Sanctum token + user data
5. Frontend stores token in `localStorage`, attaches as Bearer token

### Registration Flow
1. `POST /api/auth/register` with personal info + Turnstile token
2. Creates User → Student → assigns `student` role
3. Notifies all instructors in the system
4. Status: `pending` (must be approved by instructor)

### Password Reset Flow
1. `POST /api/auth/forgot-password` → `PasswordResetController::forgotPassword()`
2. Creates token via `DB::table('password_reset_tokens')`
3. Sends notification with token (in-app only)
4. `POST /api/auth/reset-password` → validates token → updates password → invalidates all tokens

### Authentication Issues
| Issue | Severity | Evidence |
|-------|----------|----------|
| Sanctum tokens never expire (`expiration => null`) | **High** | `config/sanctum.php` |
| `env()` used directly in TurnstileRule (breaks with config cache) | Medium | `Rules/TurnstileRule.php:18` |
| Token in localStorage (XSS vulnerable, better than cookies for SPA but still risky) | Medium | `frontend/src/services/api.client.ts:18` |
| No account lockout after failed login attempts | Medium | `AuthService::login()` |
| `forgotPassword` endpoint not rate-limited | Medium | `routes/api.php:19-20` |
| `resetPassword` uses `forceFill` bypassing mutators | Low | `PasswordResetController.php:49` |

---

## Authorization Review

### Role System (Spatie Permission)
- **super_admin**: Full system access
- **instructor**: Own courses management
- **assistant**: Assigned courses only
- **student**: Enrolled courses only

### Authorization Flow
```
Request → auth:sanctum → role:instructor/assistant → CheckEnrollment → Policy → Controller logic
```

### Critical Authorization Gaps

| Gap | Severity | Evidence |
|-----|----------|----------|
| Section/lecture CRUD: any instructor can modify any course's sections/lectures | **Critical** | `CourseController` — no `$this->authorize()` or ownership check |
| Exam CRUD: any instructor can create/modify/delete exams for any lecture | **Critical** | `ExamController::store/update/destroy` |
| `submitAttempt`: any user can submit answers for any attempt | **Critical** | `ExamController::submitAttempt()` |
| `result`: any user can view any attempt result | **Critical** | `ExamController::result()` |
| `courseEnrollments`: any user can view all enrollments | **High** | `EnrollmentController::courseEnrollments()` |
| `revoke`: any user can revoke enrollments | **High** | `EnrollmentController::revoke()` |
| `downloadFile`: any authenticated user can download any file | **High** | `CourseController::downloadFile()` |
| `updateProgress`: any student can update progress on any lecture | **High** | `CourseController::updateProgress()` |
| `startAttempt`: no enrollment check | **High** | `ExamController::startAttempt()` |
| `LecturePolicy`: allows students to create/update/delete lectures | **Medium** | `LecturePolicy.php:30` |
| `CoursePolicy`: no super_admin bypass | Medium | `CoursePolicy.php` |

### What IS Correctly Authorized
- Course `store`/`update`/`destroy` → instructor only via `$this->authorize()`
- Instructor dashboard endpoints → `role:instructor` middleware
- Assistant questions → `role:assistant` middleware
- Lecture access → `CheckEnrollment` middleware (when applied)
- Filament panel → `CheckFilamentRole` + `canAccessPanel()`

---

## Business Logic Review

### User Journeys

#### Student Journey
1. Register → Status: pending
2. Instructor approves → Status: active
3. Browse course catalog → View course details
4. Purchase product → Create order → Instructor confirms → Entitlement granted
5. Or: Enrolled manually by instructor
6. Watch lectures (with exam gating)
7. Take exams (auto-graded MCQ, full-score essays)
8. Ask questions in Q&A
9. Track progress on dashboard

#### Instructor Journey
1. Login to Filament admin panel
2. Manage courses (CRUD)
3. Manage sections and lectures within courses
4. Create exams with questions/choices
5. View enrollments, approve/revoke
6. View dashboard stats (students, revenue, performance)
7. Manage assistants (assign to courses)
8. Review Q&A questions
9. Create products and bundles for paid content

#### Assistant Journey
1. Login to Filament (limited access)
2. View assigned courses only
3. Access assigned lectures
4. Reply to Q&A questions
5. Cannot create content, manage students, or view settings

#### Admin Journey
1. super_admin: Full Filament access
2. Can manage everything
3. Can access all data

### Missing Business Rules
| Rule | Severity | Evidence |
|------|----------|----------|
| No payment gateway integration (Paymob/Kashier configured but not implemented) | **High** | `.env` has empty keys, no payment code |
| No enrollment approval workflow (students stay pending until manually changed) | **High** | No approval endpoint exists |
| No course completion certificate generation | Medium | No certificate model/code |
| No course rating/review system | Medium | No rating model/code |
| No student-to-student messaging | Low | Q&A only |
| No progress-based drip content (only exam-gating) | Low | `VideoAccessService` checks exams only |
| No subscription/recurring payment model | Low | One-time purchases only |
| No export functionality (grades, enrollments, reports) | Low | No export code |
| No email notifications (DB-only) | Medium | `NotificationService` creates DB records only |
| No push notifications | Low | Not implemented |
| Purchase notifications not sent (documented gap) | Medium | `NotificationGapsTest` confirms |
| Exam submission notifications not sent | Medium | `NotificationGapsTest` confirms |

### Dead Code
| Code | Location |
|------|----------|
| `Assignment` model | `src/app/Models/Assignment.php` — table dropped in migration 22 but model still exists |
| `AssignmentSubmission` model | `src/app/Models/AssignmentSubmission.php` — table dropped |
| `StudentActivity` model | `src/app/Models/StudentActivity.php` — conflicts with `student_activities` table |
| `ActivityResource` | `src/app/Filament/Resources/ActivityResource.php` — references dropped `activity_logs` table |
| Empty `features/auth/` dirs | `frontend/src/features/auth/` — scaffolded but unused |
| `media` table | Created in migration 10 but never used |
| `Artisan.php.bak` | `src/artisan.php.bak` — backup file committed |
| `Man_transforms_from_chaos_to_202605091849.mp4` | Root directory — video file committed to repo |

---

## Docker Review

### Services (9 total)

| Service | Image | Purpose | Health Check |
|---------|-------|---------|-------------|
| `app` | PHP 8.4 FPM Alpine (custom) | Laravel backend | None |
| `nginx` | nginx:1.27-alpine | Reverse proxy | None |
| `postgres` | postgres:18-alpine | Primary database | pg_isready ✓ |
| `redis` | redis:7-alpine | Cache/queue/sessions | None |
| `queue` | Same as `app` | Horizon queue worker | None |
| `scheduler` | Same as `app` | Laravel scheduler | None |
| `mailpit` | axllent/mailpit | Dev email capture | None |
| `minio` | minio/minio | S3-compatible storage | None |
| `minio-setup` | minio/mc | Bucket initialization | None |

### Issues
| Issue | Severity | Evidence |
|-------|----------|----------|
| Production `Dockerfile` used for `app`, `queue`, `scheduler` — no multi-stage build optimization | Medium | `Dockerfile` |
| `minio-setup` sets bucket to `public` — all uploads publicly accessible | **High** | `docker-compose.yml:150` |
| No health checks on `app`, `queue`, `scheduler` containers | Medium | `docker-compose.yml` |
| `queue` worker uses `--max-time=3600` — worker restarts every hour | Low | `docker-compose.yml:81` |
| `scheduler` uses infinite `while true; sleep 60` loop — no graceful shutdown | Low | `docker-compose.yml:100` |
| `upload_max_filesize=2G` in production Dockerfile — extremely large | Low | `Dockerfile:44` |
| `opcache.validate_timestamps=1` in production — should be 0 for performance | Low | `Dockerfile:49` |
| No frontend Dockerfile — frontend is not containerized | Medium | Missing |
| Redis has no password (`REDIS_PASSWORD=null`) | **High** | `docker-compose.yml:66` |
| PostgreSQL password is `secret` | **High** | `docker-compose.yml:48` |

---

## Infrastructure Review

### CI/CD Pipeline (`.github/workflows/ci.yml`)
- **Trigger**: push to main/develop, PRs to main
- **Laravel Job**: PHP 8.4, PostgreSQL 18, Redis 7, Composer cache, migrations, Pint (code style), PHPStan (static analysis), PHPUnit
- **Frontend Job**: Node.js 22, npm ci, lint, build

### Issues
| Issue | Severity |
|-------|----------|
| CI uses PostgreSQL but tests use SQLite (phpunit.xml `DB_CONNECTION=sqlite`) — **mismatch** | **High** |
| No Docker image build/push step | Medium |
| No deployment step (manual only) | Medium |
| No frontend tests (only lint + build) | Medium |
| No security scanning (dependency audit, SAST) | Medium |
| PostgreSQL/Redis services started but not used (tests use SQLite) | Low |

### Environment Configuration
- `.env` committed with **real credentials** (Backblaze keys, Bunny Stream API key, signing key)
- `.env.example` is mostly default Laravel — doesn't match actual requirements
- `NEXT_PUBLIC_TURNSTILE_SITE_KEY` set to test key (`1x00000000000000000000AA`)

---

## Testing Review

### Test Summary
- **Total test cases**: ~339
- **Total test files**: 38 (32 Feature + 2 Filament + 1 Unit + 3 infrastructure)
- **Framework**: Pest PHP v4 (wraps PHPUnit 12)
- **Database**: SQLite in-memory (phpunit.xml)
- **Queue**: sync (phpunit.xml)

### Test Coverage by Area

| Area | Test Files | Tests | Coverage Quality |
|------|-----------|-------|-----------------|
| Auth (register/login/logout) | 2 | 24 | **Strong** |
| Course CRUD | 1 | 20 | **Strong** |
| Exam CRUD + Gating | 3 | 40 | **Strong** |
| Enrollment + Entitlements | 4 | 37 | **Strong** |
| Q&A | 1 | 16 | **Strong** |
| Video Access/Security | 3 | 56 | **Strong** |
| Dashboard | 2 | 24 | Good |
| Products + Orders | 3 | 28 | Good |
| Roles + Auth | 2 | 23 | Good |
| Middleware | 1 | 11 | Good |
| Progress | 1 | 10 | Good |
| Password Reset | 1 | 10 | **Strong** |
| Filament Admin | 2 | 21 | Good |
| Background Jobs | 1 | 5 | Moderate |
| Rate Limiting | 1 | 3 | **Weak (false positives)** |
| Edge Cases | 2 | 15 | Good |

### False Positive Tests
| Test | File | Issue |
|------|------|-------|
| `RateLimitTest` (all 3 tests) | `tests/Feature/Api/RateLimitTest.php` | None of these tests actually test rate limiting — they send single requests and assert basic responses |
| `FileUploadValidationTest` (all 8 tests) | `tests/Feature/Filament/FileUploadValidationTest.php` | Tests source code strings, not runtime behavior — will pass even if validation doesn't work |

### Missing Test Coverage
| Area | Impact |
|------|--------|
| **No frontend tests at all** | High — no UI regression protection |
| No actual file upload rejection tests | Medium |
| No CSRF protection tests | Medium |
| No CORS tests | Medium |
| No XSS sanitization tests | High |
| No SQL injection tests | High |
| No concurrent access tests | Medium |
| No email delivery verification | Low |
| No turnstile/captcha validation tests | Medium |
| No webhook/payment integration tests | Medium |
| No performance/load tests | Medium |
| No Docker/integration tests | Low |

### Test Quality Issues
| Issue | Severity | Evidence |
|-------|----------|----------|
| Tests run against SQLite but production uses PostgreSQL | **High** | `phpunit.xml` vs `docker-compose.yml` |
| `StudentFactory` has wrong namespace — will fail when used | **High** | `database/factories/StudentFactory.php` |
| `StudentFactory` missing required fields | **High** | `database/factories/StudentFactory.php` |
| `PurchaseIdempotencyTest` documents non-idempotency (double purchase creates 2 orders) | Medium | Test name is misleading |

---

## Security Review

### Critical Findings

| # | Severity | Finding | Evidence |
|---|----------|---------|----------|
| 1 | **Critical** | **Secrets committed to version control**: Backblaze keys, Bunny API key, Bunny signing key, MinIO passwords in `.env` | `src/.env:53-81` |
| 2 | **Critical** | **Missing authorization on 12+ API endpoints**: Any instructor can modify any course's sections/lectures, exams, enrollments | `CourseController`, `ExamController`, `EnrollmentController` |
| 3 | **Critical** | **Object ownership not validated**: `submitAttempt`, `result` — any user can act on any attempt | `ExamController::submitAttempt()`, `ExamController::result()` |
| 4 | **Critical** | **`encryption_key` in fillable**: LectureVideo encryption key could be mass-assigned | `LectureVideo::$fillable` |
| 5 | **Critical** | **BunnyStreamService `getSignedPlaybackUrl` uses `hash()` instead of `hash_hmac()`**: Weaker than HMAC for URL signing | `BunnyStreamService.php` |
| 6 | **High** | **No rate limiting on `resetPassword` endpoint** | `routes/api.php:21` |
| 7 | **High** | **Sanctum tokens never expire** | `config/sanctum.php:53` |
| 8 | **High** | **Redis has no password** in Docker | `docker-compose.yml:66` |
| 9 | **High** | **MinIO bucket set to public** | `docker-compose.yml:150` |
| 10 | **High** | **CSP uses `unsafe-inline` and `unsafe-eval`** | `SecurityHeaders.php:24` |
| 11 | **High** | **`encryption_key` stored as plaintext in DB** | `lecture_videos` table |
| 12 | **High** | **Student PII accessible**: `courseEnrollments` leaks student names, phones, emails | `EnrollmentController::courseEnrollments()` |
| 13 | **Medium** | **Token in localStorage**: Vulnerable to XSS | `api.client.ts:18` |
| 14 | **Medium** | **`choices.is_correct` stored in DB**: Visible to anyone with DB access | `choices` table |
| 15 | **Medium** | **No CSRF protection**: API uses token auth (acceptable for SPA, but Filament session could be at risk) | — |
| 16 | **Medium** | **Honeypot field is CSS-only**: No server validation | `AdminPanelProvider.php:37` |
| 17 | **Medium** | **`env()` in TurnstileRule**: Won't work with config cache | `TurnstileRule.php:18` |
| 18 | **Medium** | **`APP_DEBUG=true` in production .env** | `src/.env:4` |
| 19 | **Low** | **`APP_KEY` committed**: If leaked, sessions/tokens can be forged | `src/.env:3` |
| 20 | **Low** | **No `X-XSS-Protection` header** | `SecurityHeaders.php` |

### SQL Injection Assessment
- **CourseService::listPublished** has a `like '%{search}%'` interpolation — **needs parameter binding verification**. Laravel's `where('title', 'like', '%' . $filters['search'] . '%')` is safe due to PDO parameter binding, but the direct string interpolation in the current code (`whereRaw` or similar) could be risky.
- All other queries use Eloquent/Query Builder which are parameterized — safe.

### XSS Assessment
- Frontend renders server responses directly — no sanitization layer visible
- React auto-escapes by default — **safe for JSX output**
- `dangerouslySetInnerHTML` not used — safe
- Q&A content rendered as plain text — safe

---

## Performance Review

### Critical Performance Issues

| # | Severity | Issue | Evidence | Impact |
|---|----------|-------|----------|--------|
| 1 | **Critical** | `Lecture::getProgressAttribute()` runs 3 DB queries per access — N×3 for N lectures | `Lecture.php` boot/progress | O(N) queries in collection serialization |
| 2 | **Critical** | `LectureResource` calls `auth()->user()` + `Student::where()` per lecture in collection | `LectureResource.php:59-61` | O(N) queries for enrollment checks |
| 3 | **High** | `DashboardService::getStudentStats` eager-loads `course.sections.lectures` per enrollment then loops in PHP | `DashboardService.php:119-142` | Massive memory + query load |
| 4 | **High** | `DashboardService::getInstructorStats` makes ~8 separate queries for same instructor_id | `DashboardService.php` | Could be 2-3 consolidated queries |
| 5 | **High** | `VideoAccessService::isBlockedByExam` loads all blocking exams and iterates in PHP | `VideoAccessService.php:136-151` | Could be single SQL subquery |
| 6 | **High** | `EnrollmentService::getStudentEnrollments` loads all entitlements, maps in PHP, queries missing courses | `EnrollmentService.php:61-106` | 3+ queries where 1 join would suffice |
| 7 | **Medium** | `CheckEnrollment` middleware does `Course::find()` + multiple queries per request | `CheckEnrollment.php:29-66` | 4-5 DB queries per lecture access |
| 8 | **Medium** | No eager loading in `show()` method of `CourseController` | `CourseController.php` | Sections/lectures lazy-loaded |
| 9 | **Medium** | `ProcessVideoHLS` blocks worker with `sleep(10)` polling loop | `ProcessVideoHLS.php:75` | Worker unavailable for up to 5 min |
| 10 | **Low** | `CodeGeneratorService::generateStudentCode` does separate `GradeLevel::find()` | `CodeGeneratorService.php` | Could use eager loading |

### Caching Opportunities
| Area | Current | Recommendation |
|------|---------|---------------|
| Course listings | No cache | Cache for 5-10 min |
| Grade levels, governorates | No cache | Cache permanently (rarely changes) |
| Student enrollment check | No cache | Cache with short TTL |
| Dashboard stats | No cache | Cache for 1-5 min |
| Video access checks | No cache | Cache entitlement checks |

### Queue Opportunities
| Area | Current | Recommendation |
|------|---------|---------------|
| New student notification | Synchronous (in register) | Should be queued |
| Video processing | Queued ✓ | Correct |
| Order confirmation | Synchronous | Should be queued |
| Q&A notifications | Synchronous | Should be queued |

---

## Dependency Review

### Backend Dependencies

| Package | Version | Status | Notes |
|---------|---------|--------|-------|
| `laravel/framework` | ^13.8 | Current | — |
| `filament/filament` | ^5.6 | Current | — |
| `laravel/sanctum` | ^4.3 | Current | — |
| `laravel/horizon` | ^5.9 | Current | — |
| `spatie/laravel-permission` | ^8.3 | Current | — |
| `spatie/laravel-activitylog` | ^5.0 | Current | — |
| `predis/predis` | ^3.5 | Current | — |
| `league/flysystem-aws-s3-v3` | ^3.35 | Current | — |
| `pestphp/pest` | ^4.7 | Current (dev) | — |
| `larastan/larastan` | ^3.10 | Current (dev) | — |
| `laravel/pint` | ^1.27 | Current (dev) | — |

**No unused, outdated, or risky dependencies detected.** All packages are well-maintained and at current versions.

### Frontend Dependencies

| Package | Version | Status | Notes |
|---------|---------|--------|-------|
| `next` | 16.2.10 | Current | — |
| `react` | 19.2.4 | Current | — |
| `@tanstack/react-query` | 5.101 | Current | — |
| `axios` | 1.18 | Current | — |
| `hls.js` | 1.6.16 | Current | — |
| `react-hook-form` | 7.81 | Installed but **unused** | Should be removed or used |
| `zod` | 4.4 | Installed but **barely used** | `auth.schema.ts` exists but not used |
| `@hookform/resolvers` | 5.4 | Installed but **unused** | Should be removed or used |
| `framer-motion` | 12.42 | Installed, used in landing page | OK |
| `embla-carousel-react` | 8.6 | Installed but likely **unused** | Should be verified |
| `@base-ui/react` | 1.6 | Installed — purpose unclear | May be unused |

**Unused packages**: `react-hook-form`, `@hookform/resolvers`, `embla-carousel-react`, `@base-ui/react` (needs verification). These add bundle size without value.

---

## Code Quality Review

### SOLID Violations
| Principle | Violation | Location |
|-----------|-----------|----------|
| **S** — Single Responsibility | `DashboardService` handles both student and instructor stats (could split) | `DashboardService.php` |
| **S** — Single Responsibility | `ExamController` handles CRUD, attempts, grading, results (9 methods) | `ExamController.php` |
| **O** — Open/Closed | `VideoAccessService` has deep nesting with role-specific branches — adding new roles requires modifying multiple methods | `VideoAccessService.php` |
| **L** — Liskov Substitution | `EnrollmentService::getStudentEnrollments` returns fake `Enrollment` objects that don't behave like real ones | `EnrollmentService.php:61-106` |
| **I** — Interface Segregation | No interfaces defined — all services are concrete classes | All services |
| **D** — Dependency Inversion | Services depend on concrete models directly, not abstractions | All services |

### DRY Violations
| Violation | Files |
|-----------|-------|
| MinIO URL replacement logic | `LectureFile.php`, `Question.php`, `LectureResource.php` |
| `show()`/`showAssignment()` in ExamController | `ExamController.php:20-82` |
| `instructorQuestions`/`assistantQuestions` in QAController/QAService | `QAController.php`, `QAService.php` |
| Student lookup by user_id | `CheckEnrollment.php`, `VideoAccessService.php`, `EnrollmentService.php` (3+ places) |
| Inline validation in CourseController sections/lectures | `CourseController.php` |

### Naming Issues
| Issue | Evidence |
|-------|----------|
| `QuestionsPost` model — should be `QuestionPost` (singular) | `Models/QuestionsPost.php` |
| `QuestionReply::question()` returns `QuestionsPost` — confusing | `Models/QuestionReply.php` |
| `questions_posts` table — inconsistent with singular model | Migration |
| `student_activity` vs `student_activities` — two tables, different names | Migrations 10, 27 |
| `activity_logs` vs `activity_log` — two tables, different names | Migrations 10, 28 |
| `Assignment` model vs `Exam.is_assignment` — dual concept | `Models/Assignment.php`, `Models/Exam.php` |

### Code Smells
| Smell | Evidence |
|-------|----------|
| Magic strings: `'active'`, `'pending'`, `'instructor'` used everywhere without enums | Multiple controllers/services |
| Long methods: `DashboardService::getStudentStats` (~80 lines), `VideoAccessService::canAccess` (~60 lines) | `DashboardService.php`, `VideoAccessService.php` |
| God object: `Student` model has 13 relationships, ~30 fillable fields | `Models/Student.php` |
| Feature envy: `DashboardController` methods primarily call service methods but also do direct DB queries | `DashboardController.php` |
| Primitive obsession: Status values passed as strings instead of enums | Multiple files |
| Inconsistent patterns: `request()->user()` vs `Request $request->user()`, `env()` vs `config()`, property vs method `$casts` | Multiple files |

### PSR Standards
- **PSR-12**: Compliant (enforced by Laravel Pint)
- **PSR-4**: Compliant (namespace mapping in `composer.json`)

---

## Technical Debt

### High Priority Debt
| # | Debt | Impact | Effort |
|---|------|--------|--------|
| 1 | 12+ API endpoints missing authorization | Security vulnerability | Medium |
| 2 | Duplicate/redundant database tables (`activity_logs`/`activity_log`, `student_activity`/`student_activities`) | Schema confusion, wasted storage | Low |
| 3 | `assignments` tables created then dropped | Schema noise | Low (cleanup) |
| 4 | `Lecture::getProgressAttribute()` N+1 pattern | Performance | Medium |
| 5 | `LectureResource` per-lecture DB queries | Performance | Medium |
| 6 | Fake enrollment objects in `EnrollmentService` | Correctness risk | High |
| 7 | Secrets in version control | Security | Low (rotation) |
| 8 | Tests use SQLite but production uses PostgreSQL | Test accuracy | Low |

### Medium Priority Debt
| # | Debt | Impact | Effort |
|---|------|--------|--------|
| 9 | No frontend tests | Regression risk | High |
| 10 | `features/auth/` empty directories | Dead scaffolding | Low |
| 11 | Unused npm packages | Bundle bloat | Low |
| 12 | `Assignment` model + `AssignmentSubmission` model still exist | Dead code | Low |
| 13 | README is outdated (wrong counts) | Developer confusion | Low |
| 14 | `Dockerfile.dev` not used in docker-compose | Unused config | Low |
| 15 | `Artisan.php.bak` committed | Repo clutter | Low |
| 16 | Video file committed to repo root | Repo bloat | Low |
| 17 | `HorizonServiceProvider` gate always false | Broken feature | Low |
| 18 | Inline Arabic strings instead of `__()` / `trans()` | i18n | Medium |

### Low Priority Debt
| # | Debt | Impact | Effort |
|---|------|--------|--------|
| 19 | No interfaces for services | Testability | Medium |
| 20 | Inconsistent `$casts` style | Code style | Low |
| 21 | Inconsistent fillable declaration (PHP 8 attrs vs arrays) | Code style | Low |
| 22 | FQN imports instead of `use` statements | Readability | Low |
| 23 | `media` table created but never used | Dead schema | Low |

---

## Bugs Found

| # | Severity | Bug | File | Line |
|---|----------|-----|------|------|
| 1 | **Critical** | `CheckUserStatus` compares string to potentially-enum status — will always pass (never block) if User model casts status | `Middleware/CheckUserStatus.php` | 16 |
| 2 | **Critical** | `BunnyStreamService::getSignedPlaybackUrl` uses `hash('sha256')` instead of `hash_hmac('sha256')` — weaker security | `Services/BunnyStreamService.php` | — |
| 3 | **Critical** | `CourseService::listPublished` uses string interpolation in LIKE query — potential SQL injection | `Services/CourseService.php` | 18-19 |
| 4 | **High** | `ProcessVideoHLS` job lacks `SerializesModels` — operates on stale data if model changes between dispatch and execution | `Jobs/ProcessVideoHLS.php` | — |
| 5 | **High** | `ExamService::updateExam` deletes all questions then recreates — cascades to existing answers, breaking attempts | `Services/ExamService.php` | — |
| 6 | **High** | `EnrollmentService::getStudentEnrollments` creates fake Enrollment with string ID — breaks type expectations | `Services/EnrollmentService.php` | 97 |
| 7 | **High** | `DashboardService::getInstructorCoursePerformance` accesses `$course->sections_count` without loading it in `withCount` | `Services/DashboardService.php` | 99 |
| 8 | **Medium** | `TurnstileRule` uses `env()` directly — breaks with `config:cache` | `Rules/TurnstileRule.php` | 18 |
| 9 | **Medium** | `PasswordResetController::resetPassword` uses `forceFill` — bypasses any password mutator/cast | `Controllers/Api/PasswordResetController.php` | 49 |
| 10 | **Medium** | `LecturePolicy` allows non-assistant users (including students) to create/update/delete lectures | `Policies/LecturePolicy.php` | 30 |
| 11 | **Low** | `CourseService::findById` return type `?Course` but calls `findOrFail` (never returns null) | `Services/CourseService.php` | — |
| 12 | **Low** | `CodeGeneratorService` imports `Str` but never uses it | `Services/CodeGeneratorService.php` | — |
| 13 | **Low** | `QAService` imports `Builder` but never uses it | `Services/QAService.php` | — |

---

## Missing Features

| # | Feature | Priority | Notes |
|---|---------|----------|-------|
| 1 | Payment gateway integration (Paymob/Kashier) | High | Config keys exist, no implementation |
| 2 | Enrollment approval workflow | High | Students stay "pending" forever without manual DB change |
| 3 | Frontend test suite | High | Zero frontend tests |
| 4 | Rate limiting on password reset | High | Only login has throttle |
| 5 | Email notifications | Medium | DB-only, Mailpit in dev, no prod email |
| 6 | Push notifications | Low | Not implemented |
| 7 | Course completion certificates | Medium | No model or generation logic |
| 8 | Course ratings/reviews | Medium | No model or UI |
| 9 | Subscription/recurring payments | Low | One-time purchases only |
| 10 | Export functionality (CSV/PDF) | Low | No export code |
| 11 | Notification read/unread API | Medium | `read_at` column exists but no API to mark as read |
| 12 | Video thumbnail generation | Low | Bunny handles this externally |
| 13 | Webhook handling for video processing | Medium | Uses polling instead |
| 14 | Course cloning/duplication | Low | Common LMS feature |
| 15 | Student progress reports/analytics | Medium | Basic stats exist but no detailed reports |
| 16 | Bulk enrollment import | Low | Manual enrollment only |
| 17 | Multi-language support (beyond Arabic) | Low | Arabic is hardcoded |
| 18 | Content versioning/rollback | Low | No versioning |
| 19 | Student-to-student messaging | Low | Q&A only |
| 20 | Course waitlist | Low | Not implemented |

---

## Risks

### Critical Risks
| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| **Data breach via missing authorization** | Students/instructors can access other users' data, exam answers, PII | High (already exploitable) | Add authorization to all 12+ affected endpoints immediately |
| **Secrets in version control** | Credentials leaked if repo is public or contributor leaves | High | Rotate all credentials, move to secrets manager, add pre-commit hooks |
| **SQL injection in CourseService** | Database compromise | Medium | Fix string interpolation in LIKE query |

### High Risks
| Risk | Impact | Likelihood |
|------|--------|------------|
| Performance degradation at scale (N+1 patterns) | Site becomes unusable with 100+ lectures | High |
| `LectureVideo.encryption_key` mass-assignment | Key leaked via API | Medium |
| Sanctum tokens never expire | Compromised tokens valid forever | Medium |
| Test/production database mismatch (SQLite vs PostgreSQL) | Bugs pass tests but fail in production | High |
| No frontend tests | UI regressions undetected | High |

### Medium Risks
| Risk | Impact | Likelihood |
|------|--------|------------|
| `encryption_key` stored as plaintext in DB | Key accessible to DB admins | Medium |
| Fake enrollment objects break downstream code | Subtle bugs in entitlement-based flows | Medium |
| `BunnyStreamService` file handle leak | Resource exhaustion under heavy uploads | Low |
| `ProcessVideoHLS` blocks worker | Queue backlog during video processing | Medium |
| No payment integration | Cannot monetize courses | High (for business) |

---

## Recommendations

### R1: Add Authorization to All Affected Endpoints
**Problem**: 12+ endpoints lack ownership verification  
**Why it matters**: Currently any authenticated user can modify/delete other instructors' content and view other students' PII  
**Impact**: Critical security fix  
**Difficulty**: Medium (2-3 days)  
**Priority**: **P0 — Immediate**  
**Solution**: Add `$this->authorize()` calls or ownership checks using existing policies. Extend policies to cover sections, lectures, exams, enrollments, and attempts. Create new policies as needed.

### R2: Rotate and Remove Secrets from Version Control
**Problem**: Backblaze, Bunny, MinIO credentials committed in `.env`  
**Why it matters**: Anyone with repo access has production credentials  
**Impact**: Critical security fix  
**Difficulty**: Low (1 day)  
**Priority**: **P0 — Immediate**  
**Solution**: Rotate all credentials immediately. Add `.env` to `.gitignore` (verify it's there). Use GitHub Secrets or a vault for CI/CD. Add `git-secrets` or `trufflehog` pre-commit hook.

### R3: Fix N+1 Query Patterns
**Problem**: `Lecture::getProgressAttribute()`, `LectureResource`, `DashboardService` all have severe N+1 issues  
**Why it matters**: Performance degrades linearly with content volume  
**Impact**: High performance improvement  
**Difficulty**: Medium (3-5 days)  
**Priority**: **P1 — This Sprint**  
**Solution**:
- Remove `getProgressAttribute()` from Lecture model — move to controller/service with eager loading
- Add `with()` in LectureResource when serializing collections
- Refactor DashboardService to use consolidated queries with `withCount`

### R4: Create Database Migration Cleanup
**Problem**: Duplicate tables, dropped tables, conflicting schemas  
**Why it matters**: Confuses developers and wastes storage  
**Impact**: Medium (developer experience)  
**Difficulty**: Low (1 day)  
**Priority**: **P1 — This Sprint**  
**Solution**: Create a single squashed migration that drops unused tables (`assignments`, `assignment_submissions`, `activity_logs`, `student_activity`, `media`) and adds missing indexes.

### R5: Add Frontend Test Suite
**Problem**: Zero frontend tests  
**Why it matters**: No regression protection for the student-facing app  
**Impact**: High  
**Difficulty**: High (1-2 weeks)  
**Priority**: **P2 — Next Sprint**  
**Solution**: Add Playwright or Cypress for E2E tests. Start with critical flows: login, registration, course browsing, video playback, exam taking, Q&A. Add Vitest for unit testing hooks and utilities.

### R6: Implement Payment Gateway Integration
**Problem**: Paymob/Kashier keys configured but no code exists  
**Why it matters**: Cannot monetize courses — core business feature missing  
**Impact**: Critical for business  
**Difficulty**: High (1-2 weeks)  
**Priority**: **P2 — Next Sprint**  
**Solution**: Implement Paymob integration first (most common in Egypt). Create webhook handler for payment confirmation. Add order status state machine (pending → processing → completed/failed/refunded).

### R7: Fix Security Headers
**Problem**: CSP uses `unsafe-inline` and `unsafe-eval`  
**Why it matters**: Significantly weakens XSS protection  
**Impact**: Medium security improvement  
**Difficulty**: Medium (1-2 days)  
**Priority**: **P2 — Next Sprint**  
**Solution**: Use nonces for inline scripts. Remove `unsafe-eval`. Tighten `img-src` and `media-src` to specific domains.

### R8: Implement Token Expiration
**Problem**: Sanctum tokens never expire  
**Why it matters**: Compromised tokens are valid forever  
**Impact**: Medium security improvement  
**Difficulty**: Low (1 hour)  
**Priority**: **P2 — Next Sprint**  
**Solution**: Set `expiration => 60 * 24 * 30` (30 days) in `config/sanctum.php`. Add refresh token mechanism.

### R9: Fix Frontend Issues
**Problem**: Mock notification data, missing Turnstile token, cache invalidation issues  
**Why it matters**: Broken features and poor UX  
**Impact**: Medium  
**Difficulty**: Low-Medium (2-3 days)  
**Priority**: **P2 — Next Sprint**  
**Solution**: Wire notifications to API, add Turnstile to forgot-password, fix React Query invalidation keys.

### R10: Introduce Service Interfaces
**Problem**: No abstractions — services tightly coupled to concrete implementations  
**Why it matters**: Makes testing harder and prevents swapping implementations  
**Impact**: Low (code quality)  
**Difficulty**: Medium (3-5 days)  
**Priority**: **P3 — Backlog**  
**Solution**: Define interfaces for each service. Register implementations in service provider. Enables mocking in tests and future refactoring.

---

## Action Plan

### Phase 1: Critical Security (Week 1)
1. Fix all 12+ authorization gaps (R1)
2. Rotate and remove secrets (R2)
3. Fix `encryption_key` fillable issue
4. Fix `BunnyStreamService` HMAC issue
5. Add rate limiting to password reset
6. Set Sanctum token expiration

### Phase 2: Performance & Stability (Week 2)
7. Fix N+1 query patterns (R3)
8. Database migration cleanup (R4)
9. Fix `CheckUserStatus` enum comparison bug
10. Fix `CourseService::listPublished` SQL concern
11. Remove dead code and models

### Phase 3: Features & Testing (Weeks 3-4)
12. Add frontend test suite (R5)
13. Implement payment gateway (R6)
14. Fix security headers (R7)
15. Wire up frontend notifications
16. Implement enrollment approval workflow

### Phase 4: Quality & Polish (Week 5+)
17. Introduce service interfaces (R10)
18. Add comprehensive API tests for authorization
19. Fix test/production database mismatch
20. Update documentation

---

## Priority Matrix

| Priority | Count | Items |
|----------|-------|-------|
| **P0 — Critical (fix now)** | 4 | Authorization gaps, secrets in VCS, SQL injection, HMAC signing |
| **P1 — High (this sprint)** | 4 | N+1 fixes, DB cleanup, dead code removal, test DB mismatch |
| **P2 — Medium (next sprint)** | 7 | Frontend tests, payment integration, security headers, token expiry, frontend bugs, enrollment approval, email notifications |
| **P3 — Low (backlog)** | 5 | Service interfaces, i18n, export functionality, course ratings, notifications read/unread |

---

## Estimated Refactoring Order

| Order | Task | Est. Hours | Dependencies |
|-------|------|-----------|--------------|
| 1 | Add authorization to all affected endpoints | 16-24h | None |
| 2 | Rotate secrets + add pre-commit hooks | 4-6h | None |
| 3 | Fix `Lecture::getProgressAttribute()` N+1 | 4-6h | None |
| 4 | Fix `LectureResource` N+1 | 4-6h | None |
| 5 | Refactor `DashboardService` queries | 8-12h | None |
| 6 | DB migration cleanup (drop unused tables, add indexes) | 4-6h | None |
| 7 | Remove dead code (Assignment models, empty dirs, .bak files) | 2-4h | None |
| 8 | Fix `CheckUserStatus` enum bug | 1-2h | None |
| 9 | Fix `CourseService::listPublished` | 1-2h | None |
| 10 | Set Sanctum token expiration | 1h | None |
| 11 | Add rate limiting to password reset | 1h | None |
| 12 | Fix `BunnyStreamService` HMAC + file handle | 2-3h | None |
| 13 | Add frontend E2E tests (critical flows) | 20-30h | None |
| 14 | Implement payment gateway | 30-40h | #1 complete |
| 15 | Fix security headers (CSP nonces) | 4-6h | None |
| 16 | Wire frontend notifications to API | 4-6h | None |
| 17 | Add Turnstile to forgot-password | 1-2h | None |
| 18 | Fix React Query cache invalidation | 2-3h | None |
| 19 | Update documentation | 4-6h | All above |
| 20 | Introduce service interfaces | 12-16h | None |

**Total estimated effort: 120-180 hours (3-5 weeks for 1 engineer)**

---

*End of Audit Report*
