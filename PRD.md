# PRD — White-Label Self-Hosted Instructor Education Platform

**Date:** July 2026
**Owner:** Solo Developer (Product Vendor, building with AI coding agent assistance)
**Platform Type:** White-Label, Single-Tenant, Instructor-Hosted Educational Platform (sold per instructor)
**Current Status:** MVP Backend Complete — 360 automated tests passing, full API + Filament admin panel functional

---

## 1. Overview

### 1.1 What Is This Project?
An educational platform **product** built once and deployed independently per instructor. Each instructor gets their own isolated instance under their own custom domain with their own branding — students only see that one instructor's platform with no visibility into others or a shared marketplace.

The platform enables instructors to:
- Create and sell online courses with video lectures, PDFs, exams, and assignments
- Manage students (registration approval, enrollment, progress tracking)
- Handle payments through their own payment gateway accounts
- Delegate work to Teaching Assistants with scoped permissions
- Run a Q&A system for student questions

### 1.2 What Problem Does It Solve?
| Stakeholder | Problem |
|---|---|
| **Instructors** | Want a professional, fully-branded online teaching platform under their own name and domain — without building software themselves. Want ownership and control over hosting and revenue (no middleman taking a cut). |
| **Students** | Need a reliable platform to access video lectures, take exams, track progress, and ask questions — all in one place under a trusted instructor's brand. |
| **The Vendor (Developer)** | Wants to build **one reusable, well-engineered product** and deploy it repeatedly for many instructor clients, without the operational cost of running a live multi-tenant SaaS or rebuilding per client. |

### 1.3 Business Model
- **Product:** White-label, single-tenant platform — one codebase, deployed independently per instructor.
- **Sale:** One-time purchase by the instructor (not SaaS subscription, not commission-based).
- **Hosting & Revenue:** The instructor pays for their own server/hosting and owns their own payment gateway — student payments go directly to them.
- **Maintenance:** The vendor maintains and updates the software across all deployed instances via versioned releases.

### 1.4 Scale Model
Because each instructor runs an independent instance, "scaling" means two things:
1. **Per-instance:** A single deployment handles that instructor's student base (hundreds to tens of thousands).
2. **Fleet (the real challenge):** The vendor deploys, updates, and maintains many independent instances without per-instance effort growing linearly — solved through Docker-based, config-driven deployment.

---

## 2. Technology Stack

| Layer | Technology | Why |
|---|---|---|
| **Backend** | Laravel 13 (PHP 8.4) | Mature ecosystem, built-in auth/queues/caching, excellent for rapid development with AI assistance |
| **Admin Panel** | Filament v4 | Auto-generated CRUD for instructors/TAs — saves weeks vs. custom dashboard |
| **Database** | PostgreSQL 18 (SQLite for tests) | One database per instance, no multi-tenant complexity |
| **Cache & Queues** | Redis + Laravel Horizon | Session caching, queue management, rate limiting |
| **Video Processing** | FFmpeg + HLS/AES-128 Encryption | Self-hosted video transcoding to encrypted HLS segments on MinIO (S3-compatible) |
| **Object Storage** | MinIO (development) / Cloudflare R2 (production) | Stores video segments, PDFs, Q&A attachments — instructor's own account |
| **Authentication** | Laravel Sanctum (API tokens) + Session-based (Filament) | Token auth for student API, session auth for admin panel |
| **Authorization (RBAC)** | Spatie Laravel-Permission | Roles: super_admin, instructor, assistant, student |
| **Testing** | Pest PHP v4 | Modern, expressive test syntax with Laravel plugin |
| **Containerization** | Docker Compose (Laravel Sail-based) | Dev/prod parity, one deployable artifact per instance |
| **Web Frontend** | Next.js 16 + React 19 (planned) | Not yet implemented — backend API is ready |

---

## 3. Current Implementation (MVP Backend — Complete)

### 3.1 Architecture Overview

The backend follows a **Modular Monolith** structure with service-layer business logic:

```
app/
├── Filament/                  ← Instructor/TA admin panel (11 resources)
├── Http/Controllers/Api/      ← Student-facing API (8 controllers, ~42 routes)
├── Http/Middleware/            ← Custom middleware (4)
├── Http/Requests/             ← Form validation (3)
├── Jobs/                      ← Queued jobs (1: ProcessVideoHLS)
├── Models/                    ← Eloquent models (33)
├── Providers/                 ← Service providers
├── Rules/                     ← Custom validation rules (TurnstileRule)
├── Services/                  ← Business logic (11 services)
└── Enums/                     ← Status enums (UserStatus, CourseStatus, EnrollmentStatus)
```

### 3.2 Implemented Features (360 Tests Passing)

#### 3.2.1 Authentication & User Management
- **Request-based registration:** Students submit detailed profile (4-part name, phone, guardian info, governorate, grade level, gender) → account is `pending` until instructor approves.
- **Multi-field login:** Email, phone number, or student code — all work.
- **Status-based access control:** `pending` users blocked from login, `rejected` users blocked, `active` users proceed.
- **Roles:** `super_admin`, `instructor`, `assistant`, `student` — managed via Spatie.
- **Profile endpoint:** `/api/auth/me` returns user info, roles, and student profile.

**Tests:** AuthTest (12) + AuthComprehensiveTest (16) = 28 tests

#### 3.2.2 Course Management (Full CRUD)
- **Hierarchical content structure:**
  ```
  Course → CourseSection (Month/Chapter) → Lecture
    ├── LectureVideo (HLS encrypted, AES-128)
    ├── LectureFile (PDF attachments)
    ├── Exam (multiple_choice, true_false, essay)
    ├── Assignment (exam with is_assignment=true)
    └── QuestionsPost → QuestionReply (Q&A)
  ```
- **Instructor CRUD:** Create, update, delete courses/sections/lectures via API and Filament.
- **Student browsing:** Published courses listed with pagination and search.
- **Course detail:** Full content tree with sections, lectures, video metadata, exams, assignments, and progress map.
- **Lecture detail:** Video, files, exams, assignments — enrollment/entitlement gated.
- **Unique codes:** Students get `ST...` codes, assistants get `TA...` codes, courses get `CR...` codes.

**Tests:** CourseCrudTest (28) + PaginationTest (6) + ProductDetailTest (9) = 43 tests

#### 3.2.3 Pricing & Entitlement Engine (Core)
- **Product model:** Polymorphic sellable — points to `Course`, `CourseSection`, or `Lecture`. Each product has `name`, `price`, `access_duration_days`, `is_active`, `instructor_id`.
- **Bundle model:** Groups multiple Products at a combined price.
- **Order flow (Manual Payment):**
  1. Student creates order → Order saved as `pending`
  2. Student pays manually (Vodafone Cash / bank transfer / InstaPay)
  3. Instructor/admin opens Filament → sees pending order → clicks "تأكيد الدفع" (Confirm Payment)
  4. On confirmation: `status → completed`, `paid_at → now()`, entitlements granted via `GrantEntitlementService`
- **Entitlement resolution:** When an order is confirmed, `GrantEntitlementService` resolves all lecture IDs:
  - Product pointing to `Course` → all lectures in all sections
  - Product pointing to `CourseSection` → all lectures in that section
  - Product pointing to `Lecture` → that single lecture
  - Bundle → union of all products' lectures
- **Entitlement check:** Always at the lecture level: `Entitlement::where(student_id, lecture_id, not expired)`
- **Access duration:** Products can have `access_duration_days` — entitlements get `expires_at`. Null = permanent.

**Tests:** OrderControllerTest (9) + PurchaseIdempotencyTest (7) + EntitlementAccessTest (8) + EntitlementEngineTest (3) + ProductOrderTest (12) = 39 tests

#### 3.2.4 Video Streaming & Security
- **HLS processing:** `ProcessVideoHLS` queued job downloads MP4 from MinIO, transcodes to HLS via FFmpeg, encrypts with AES-128, uploads segments back to MinIO.
- **LectureVideo model:** Tracks `video_path` (HLS playlist), `encryption_key` (hex), `status` (pending/processing/completed/failed).
- **Signed token access:** `VideoAccessService::generateSignedToken()` creates encrypted tokens bound to user + lecture + IP, valid for 5 minutes.
- **Stream endpoint:** `/api/lectures/{id}/stream` returns the HLS playlist with segment paths replaced by MinIO URLs.
- **Key endpoint:** `/api/lectures/{id}/key?token=...` returns the binary AES-128 decryption key.
- **Access checks:** Validates user status, entitlement (not expired), IP match, token expiry, blocking exams.
- **Auto-dispatch:** `Lecture::booted()` observer dispatches `ProcessVideoHLS` when `video_path` is set or changed.

**Tests:** VideoAccessServiceTest (22) + VideoStreamSecurityTest (19) + ProcessVideoHLSTest (11) + BackgroundJobsTest (5) = 57 tests

#### 3.2.5 Exams & Assignments
- **Exam types:** `multiple_choice`, `true_false`, `essay` — configured per question.
- **Exam lifecycle:** Create exam → add questions/choices → student starts attempt → submits answers → auto-graded → result available.
- **Auto-grading:** MC and TF questions graded instantly. Essay questions stored for manual review.
- **Blocking exams:** `is_blocking` flag + `pass_percentage` — must pass before accessing subsequent content.
- **Sequential gating:** Exams are checked by section → lecture → exam sort_order. Failing one blocks everything after it.
- **Self-exemption:** Exam on lecture X doesn't block access to lecture X itself (only subsequent lectures).
- **Assignments:** Same `Exam` model with `is_assignment=true`, separate Filament resource, submissions tracked.

**Tests:** ExamCrudTest (17) + ExamServiceTest (10) + PreExamGatingTest (8) + AssignmentTest (6) = 41 tests

#### 3.2.6 Enrollment & Progress
- **Enrollment:** Students enroll in courses (free courses via API, paid via order flow). Status: `active`/`expired`/`suspended`.
- **Revocation:** Instructors can revoke enrollments.
- **Progress tracking:** `StudentActivity` records for `video_progress` and `video_completed`.
- **Student statistics:** Aggregated `total_watch_minutes`, `completed_lectures`, `average_exam_score`.
- **Dashboard stats:** Instructor sees courses, students, revenue, lectures, pending orders. Student sees enrollments, completed lectures, watch time, scores.

**Tests:** EnrollmentFlowTest (13) + EnrollmentServiceTest (12) + ProgressTest (9) + DashboardTest (16) + DashboardServiceTest (12) = 62 tests

#### 3.2.7 Filament Admin Panel (Instructor/TA Interface)
11 Filament resources providing full admin UI:

| Resource | Features |
|---|---|
| **StudentResource** | List, approve, reject, edit, delete students; revoke entitlements |
| **CourseResource** | Full CRUD; Assistants relation manager; scoped to instructor |
| **LectureResource** | Full CRUD; nested exam/assignment repeaters |
| **ExamResource** | CRUD for exams; filters non-assignment exams |
| **AssignmentResource** | CRUD for assignments; Submissions relation manager |
| **ProductResource** | CRUD; polymorphic sellable selection (Course/Section/Lecture) |
| **BundleResource** | CRUD; Products relation manager |
| **OrderResource** | List orders; "Confirm Payment" action (triggers entitlement grant) |
| **AssistantResource** | CRUD for teaching assistants; scoped to assistant role |
| **QAResource** | View questions; reply to students |
| **ActivityResource** | Read-only audit log (Spatie ActivityLog) |

**Role-based access:**
- `super_admin`: Full access to everything
- `instructor`: Full access to own courses/resources
- `assistant`: Limited — can view courses (scoped), dashboard; cannot create courses, manage assistants, or view settings
- `student`: Cannot access admin panel at all

**Tests:** AdminPanelTest (14) + AssistantAccessBoundaryTest (17) = 31 tests

#### 3.2.8 Security Layer
- **Middleware:**
  - `CheckUserStatus`: Blocks non-active users (403).
  - `CheckEnrollment`: Verifies access to lectures (instructor ownership, assistant assignment, student entitlement, or free enrollment + blocking exam check).
  - `SecurityHeaders`: HSTS, CSP, X-Frame-Options, etc.
  - `TurnstileRule`: Cloudflare Turnstile captcha validation (bypassed in testing env).
- **Rate limiting:** Login throttling, video streaming throttling.
- **Token security:** Video access tokens encrypted, IP-bound, lecture-bound, 5-minute expiry.
- **Password security:** bcrypt hashing, strong password rules.

**Tests:** MiddlewareTest (10) + RateLimitTest (3) + RolesAndAuthTest (10) = 23 tests

#### 3.2.9 Notifications
- **In-app notifications:** `NotificationService` sends to users (title + body).
- **Triggers implemented:** New student registration → notify all instructors; Student approval → notify student; Student rejection → notify student.
- **Known gaps (not yet implemented):** Purchase confirmation, exam grading result, subscription expiry.

**Tests:** NotificationGapsTest (9) — includes both existing notifications and documented gaps

#### 3.2.10 Supporting Systems
- **Geography data:** Governorates, cities, schools (lookup tables for student profiles).
- **Grade levels & academic tracks:** For content organization and student filtering.
- **Activity logging:** Spatie ActivityLog for audit trail on admin/instructor actions.
- **Unique code generation:** Collision-resistant codes for students, assistants, courses.

**Tests:** MiscTest (6) + EdgeCaseTest (7) = 13 tests

### 3.3 Test Suite Summary

| Category | Test Files | Tests | Assertions |
|---|---|---|---|
| Authentication | 2 | 28 | ~70 |
| Course CRUD | 3 | 43 | ~110 |
| Entitlement & Orders | 5 | 39 | ~100 |
| Video & Security | 4 | 57 | ~140 |
| Exams & Assignments | 4 | 41 | ~100 |
| Enrollment & Dashboard | 5 | 62 | ~150 |
| Filament & RBAC | 2 | 31 | ~70 |
| Middleware & Auth Guards | 3 | 23 | ~50 |
| Notifications | 1 | 9 | ~20 |
| Edge Cases & Misc | 2 | 13 | ~30 |
| Background Jobs | 1 | 5 | ~10 |
| **Total** | **34 files** | **360 tests** | **760 assertions** |

**Testing approach:**
- Framework: **Pest PHP v4** (expressive, closure-based syntax)
- Database: **SQLite in-memory** (fast, isolated per test)
- Queue: **Sync** (jobs execute immediately in tests)
- Cache: **Array driver**
- Mail: **Array driver**
- Key patterns: `RefreshDatabase` trait, `beforeEach` setup, role seeding via `Role::findOrCreate()`, `actingAs()` for auth, `Queue::fake()` for job assertions.

---

## 4. What's NOT Yet Implemented (Gaps & Known Issues)

### 4.1 Payment Gateway Integration
- **Current:** Orders are `pending` until manually confirmed in Filament. No actual Paymob/Kashier integration.
- **Missing:** Webhook handlers for payment callbacks, signature verification, redirect flows, refund handling.
- **Impact:** Instructors must manually verify each payment — acceptable for launch but must be automated at scale.

### 4.2 Assistant Granular Permissions (PRD Section 3.7)
- **Current:** Assistants have a binary role — either assigned to a course or not.
- **Missing:** Per-assistant checkbox permissions (can_grade, can_answer_qa, can_approve, can_upload, can_manage_exams, can_view_reports, can_manage_notifications).
- **Impact:** Assistants currently get full access to their assigned courses — no fine-grained control.

### 4.3 Missing Notification Types
- **Current:** Only registration + approval/rejection notifications.
- **Missing:** Purchase confirmation, exam grading result, subscription expiry, new Q&A reply, new lecture published.

### 4.4 Q&A System (UI/UX)
- **Backend:** `QuestionsPost` and `QuestionReply` models exist with Filament resource.
- **Missing:** Student-facing API endpoints for posting questions and viewing replies. Image attachment upload for questions/answers.

### 4.5 Frontend (Student App)
- **Current:** API-only — no web or mobile frontend.
- **Planned:** Next.js 16 web app (PWA-capable), later React Native mobile.

### 4.6 Real-time Features
- **Missing:** Laravel Reverb (WebSocket server) for live notifications, Q&A updates, live progress.

### 4.7 Video Hosting Provider Integration
- **Current:** Videos processed locally via FFmpeg to HLS, stored on MinIO (S3-compatible).
- **Planned:** Bunny Stream integration for production (instructor's own account, signed URLs, CDN delivery).

### 4.8 Password Reset Flow
- **Missing:** No forgot-password / reset-password endpoint.

### 4.9 Health Check Endpoint
- **Missing:** No `/up` or `/health` endpoint for monitoring.

---

## 5. Future Roadmap

### Phase 2 (Post-MVP)
- Payment gateway integration (Paymob/Kashier webhooks)
- Assistant granular permission checkboxes
- Missing notification types
- Student-facing Q&A API
- Single-lecture and bundle purchases via payment gateway
- Pre-lecture gate exams
- Assignments with file upload and manual grading
- Laravel Reverb for real-time
- SMS/OTP registration verification
- Password reset flow
- Health check endpoint

### Phase 3 (Scale)
- Next.js 16 web frontend (PWA)
- React Native mobile app (automated white-label builds)
- Advanced analytics and reporting
- Push notifications
- Video DRM (if piracy becomes measurable)
- Desktop app (Electron, if validated)
- Meilisearch for course catalog search

---

## 6. Non-Functional Requirements

| Requirement | Detail |
|---|---|
| **Performance** | API response time under 300ms for core requests, per instance |
| **Availability** | 99.5% uptime target per instance |
| **Localization** | Full Arabic (RTL) support across all interfaces |
| **Security** | HTTPS/TLS everywhere, encrypted video content, rate limiting, audit logging, PII minimization |
| **Legal Compliance** | Egypt's Personal Data Protection Law (Law No. 151 of 2020) — parental consent at registration, data retention/deletion policy |

---

## 7. Project Structure

```
edu-platform/
├── PRD.md                          ← This document
├── docker-compose.yml              ← Docker stack (Laravel Sail)
├── src/                            ← Laravel application root
│   ├── app/
│   │   ├── Enums/                  ← UserStatus, CourseStatus, EnrollmentStatus
│   │   ├── Filament/               ← Admin panel (11 resources, 3 relation managers)
│   │   ├── Http/
│   │   │   ├── Controllers/Api/    ← 8 API controllers
│   │   │   ├── Middleware/         ← 4 custom middleware
│   │   │   └── Requests/          ← 3 form request validators
│   │   ├── Jobs/                   ← 1 queued job (ProcessVideoHLS)
│   │   ├── Models/                 ← 33 Eloquent models
│   │   ├── Rules/                  ← TurnstileRule (captcha)
│   │   └── Services/               ← 11 service classes
│   ├── config/                     ← Laravel + app config
│   ├── database/
│   │   ├── migrations/             ← 29 migration files
│   │   └── seeders/                ← Database seeders
│   ├── routes/
│   │   └── api.php                 ← ~42 API routes
│   ├── tests/
│   │   ├── Feature/Api/            ← 28 API test files
│   │   ├── Feature/Filament/       ← 1 Filament test file
│   │   └── Pest.php               ← Test configuration
│   └── phpunit.xml                 ← Test configuration (SQLite, sync queue)
└── PRD.md
```

---

## 8. Key Domain Models (33 Total)

| Domain | Models |
|---|---|
| **Users & Auth** | User, Role, Permission, PersonalAccessToken |
| **Student Profile** | Student, Governorate, City, School, GradeLevel, AcademicTrack |
| **Content** | Course, CourseSection, Lecture, LectureVideo, LectureFile |
| **Exams & Assignments** | Exam, Question, Choice, Answer, ExamAttempt |
| **Commerce** | Product, Bundle, Order, Entitlement |
| **Enrollment** | Enrollment, CourseAssistant |
| **Progress & Activity** | StudentActivity, StudentStatistic |
| **Communication** | Notification, QuestionsPost, QuestionReply |
| **Audit** | Activity (Spatie ActivityLog) |
| **Legacy** | Assignment, AssignmentSubmission (deprecated — use Exam with is_assignment) |

---

## 9. Deployment Model

```
┌─────────────────────────────────────────────┐
│            VENDOR (Developer)                │
│  - Builds & tests codebase                  │
│  - Publishes versioned Docker images         │
│  - Maintains CI/CD (GitHub Actions)          │
│  - Provides update scripts                   │
└─────────────────┬───────────────────────────┘
                  │ Docker images
    ┌─────────────┼─────────────┐
    ▼             ▼             ▼
┌─────────┐ ┌─────────┐ ┌─────────┐
│Instance 1│ │Instance 2│ │Instance N│
│ ahmed.com│ │ sarah.edu│ │ ...     │
│ PostgreSQL│ │ PostgreSQL│ │ PostgreSQL│
│ Redis    │ │ Redis    │ │ Redis    │
│ MinIO/R2 │ │ MinIO/R2 │ │ MinIO/R2 │
│ Own domain│ │ Own domain│ │ Own domain│
│ Own payment│ │ Own payment│ │ Own payment│
└─────────┘ └─────────┘ └─────────┘
```

Each instance is fully isolated — different database, different payment gateway, different domain, different branding. The same codebase serves all of them through configuration.

---

## 10. Security Architecture

- **Authentication:** Sanctum tokens for API (student app), session-based for Filament (instructor/TA).
- **Authorization:** Spatie roles (super_admin, instructor, assistant, student) enforced server-side on every route.
- **Video protection:** AES-128 encrypted HLS streams, signed tokens with IP+lecture binding, 5-minute expiry.
- **Payment safety:** Orders start as `pending`, require manual confirmation before entitlements are granted.
- **Data privacy:** PII minimized, Egyptian data protection law compliance, audit logging via Spatie ActivityLog.
- **Infrastructure:** Security headers middleware (HSTS, CSP, X-Frame-Options), rate limiting, bcrypt passwords.

---

## 11. Lessons Learned During Development

### 11.1 Pest PHP v4 Behavior
- **`uses()` without `->in()`** only targets `tests/Pest.php` itself — must chain `->in('Feature')` to apply to test files.
- **Global `beforeEach`** must be chained on `uses()`, not standalone.
- **`(int)` casts** needed for `round()` comparisons — Pest's `toBe()` does strict type comparison.

### 11.2 Laravel Model Observers
- `Lecture::booted()` observer fires on `saved` event (both create and update). The dispatch condition must check `wasChanged('video_path')`, `wasRecentlyCreated`, AND `$hasVideo` to avoid unnecessary re-processing.
- Eloquent model defaults set via `Schema::table()->default()` are not reflected on the model object after `create()` without explicit passing or `fresh()`.

### 11.3 Filament v4 Behavior
- Resources in subdirectories (e.g., `Pricing/ProductResource`) may register at different URL slugs than expected.
- When `canViewAny()` returns false, Filament returns **404** (not 403) — security through obscurity.
- Filament uses session-based auth (`$this->be($user)`) not token-based — different from API tests.

### 11.4 Payment Flow Design
- Auto-completing orders on creation is insecure without a payment gateway — manual confirmation is the correct default.
- Entitlements should only be granted after payment is confirmed, not on order creation.
- The `GrantEntitlementService` must be callable independently from the order flow (for manual confirmation).

### 11.5 Video Security
- Encrypted HLS (AES-128) requires a separate key endpoint with its own access control.
- Tokens for video access must be bound to user + lecture + IP to prevent sharing.
- S3-compatible storage (MinIO) cannot use `Storage::fake()` — must use `Queue::fake()` instead for job testing.

### 11.6 Testing Strategy
- **SQLite in-memory** for speed and isolation — but some PostgreSQL-specific features need attention.
- **Sync queue** in tests ensures jobs execute immediately — but `Queue::fake()` is needed when testing observer dispatch counts.
- **Role seeding** in `Pest.php` `beforeEach` ensures consistent role availability across all tests.
- **Documenting gaps as tests** (e.g., `NotificationGapsTest`, `PurchaseIdempotencyTest`) serves as regression guards when features are eventually implemented.
