# PRD — White-Label Self-Hosted Instructor Education Platform

**Date:** July 2026
**Owner:** Solo Developer (Product Vendor, building with AI coding agent assistance)
**Platform Type:** White-Label, Single-Tenant, Instructor-Hosted Educational Platform (sold per instructor)
**Current Status:** Full-Stack MVP — 437 automated tests passing (407 backend + 30 frontend), full API + Filament admin + Next.js 16 frontend with Vitest + React Testing Library

---

## 1. Overview

### 1.1 What Is This Project?
An educational platform **product** built once and deployed independently per instructor. Each instructor gets their own isolated instance under their own custom domain with their own branding — students only see that one instructor's platform with no visibility into others or a shared marketplace.

The platform enables instructors to:
- Create and sell online courses with video lectures, PDFs, exams, and assignments
- Manage students (registration approval, enrollment, progress tracking)
- Handle payments through their own payment gateway accounts
- Delegate work to Teaching Assistants with scoped permissions
- Run a Q&A system for student questions under lectures
- Monitor and reply to questions via admin panel with real-time polling

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
| **Admin Panel** | Filament v5.6.8 | Auto-generated CRUD for instructors/TAs — saves weeks vs. custom dashboard |
| **Database** | PostgreSQL 18 (SQLite for tests) | One database per instance, no multi-tenant complexity |
| **Cache & Queues** | Redis + Laravel Horizon | Session caching, queue management, rate limiting |
| **Video Processing** | FFmpeg + HLS/AES-128 Encryption | Self-hosted video transcoding to encrypted HLS segments on MinIO (S3-compatible) |
| **Object Storage** | MinIO (development) / Cloudflare R2 (production) | Stores video segments, PDFs, Q&A attachments — instructor's own account |
| **Authentication** | Laravel Sanctum (API tokens) + Session-based (Filament) | Token auth for student API, session auth for admin panel |
| **Authorization (RBAC)** | Spatie Laravel-Permission | Roles: super_admin, instructor, assistant, student |
| **Testing** | Pest PHP v4 | Modern, expressive test syntax with Laravel plugin |
| **Containerization** | Docker Compose (Laravel Sail-based) | Dev/prod parity, one deployable artifact per instance |
| **Web Frontend** | Next.js 16 (App Router) + React 19 + TypeScript | Full student-facing SPA with Arabic RTL, shadcn/ui, React Query |
| **UI Components** | shadcn/ui + Tailwind CSS v4 | Accessible, customizable component library with utility-first CSS |
| **State Management** | TanStack React Query v5 | Server state caching, polling, optimistic updates |
| **Video Player** | Video.js (HLS + DRM) | Encrypted HLS playback with quality switching |

---

## 3. Current Implementation (Full-Stack MVP — Complete)

### 3.1 Architecture Overview

The backend follows a **Modular Monolith** structure with service-layer business logic. The frontend is a full **Next.js 16 App Router** SPA with Arabic RTL, shadcn/ui, and React Query.

```
edu-platform/
├── src/                            ← Laravel backend
│   ├── app/
│   │   ├── Filament/               ← Instructor/TA admin panel (13 resources)
│   │   ├── Http/Controllers/Api/   ← Student-facing API (10 controllers, ~55 routes)
│   │   ├── Http/Middleware/         ← Custom middleware (5)
│   │   ├── Http/Requests/          ← Form validation (5)
│   │   ├── Jobs/                   ← Queued jobs (1: ProcessVideoHLS)
│   │   ├── Models/                 ← Eloquent models (34)
│   │   ├── Rules/                  ← Custom validation rules (TurnstileRule)
│   │   └── Services/               ← Business logic (12 services)
│   ├── routes/api.php              ← ~55 API routes
│   └── tests/                      ← 407 tests (893 assertions)
└── frontend/                       ← Next.js 16 student-facing SPA
    └── src/
        ├── app/                    ← App Router pages (4 route groups)
        ├── components/             ← 41 React components (shadcn/ui + custom)
        ├── hooks/                  ← 8 React Query hooks
        ├── services/               ← 8 API service modules
        ├── types/                  ← 8 TypeScript type definitions
        └── providers/              ← Theme, Query, Root providers
```

### 3.2 Implemented Features (407 Tests Passing)

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
    ├── LectureFile (PDF attachments — proxied download via backend)
    ├── Exam (multiple_choice, true_false, essay)
    ├── Assignment (exam with is_assignment=true)
    └── QuestionsPost → QuestionReply (Q&A)
  ```
- **Instructor CRUD:** Create, update, delete courses/sections/lectures via API and Filament.
- **Student browsing:** Published courses listed with pagination and search.
- **Course detail:** Full content tree with sections, lectures, video metadata, exams, assignments, and progress map.
- **Lecture detail:** Video, files, exams, assignments — enrollment/entitlement gated.
- **Lecture file downloads:** Files stored on MinIO, proxied through `downloadFile()` controller method to avoid signed URL issues. Frontend uses blob download with auth headers.
- **Per-lecture access control:** `has_access` field on lectures computed by `VideoAccessService::canAccess()` — checks admin/instructor/assistant status, entitlement, and enrollment.
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
- **Single-lecture purchase:** Frontend product pages support purchasing individual lectures, courses, or sections. The "Continue Learning" system routes students to the first accessible lecture based on their entitlements.
- **Fake enrollment detection:** `EnrollmentService::getStudentEnrollments()` creates synthesized entitlement-only enrollments (`id = 'entitlement-fake-{courseId}'`). Frontend detects these to distinguish from real paid enrollments for routing decisions.

**Tests:** OrderControllerTest (9) + PurchaseIdempotencyTest (7) + EntitlementAccessTest (8) + EntitlementEngineTest (3) + ProductOrderTest (12) = 39 tests

#### 3.2.4 Video Streaming & Security
- **HLS processing:** `ProcessVideoHLS` queued job downloads MP4 from MinIO, transcodes to HLS via FFmpeg, encrypts with AES-128, uploads segments back to MinIO.
- **LectureVideo model:** Tracks `video_path` (HLS playlist), `encryption_key` (hex), `status` (pending/processing/completed/failed).
- **Signed token access:** `VideoAccessService::generateSignedToken()` creates encrypted tokens bound to user + lecture + IP, valid for 5 minutes.
- **Stream endpoint:** `/api/lectures/{id}/stream` returns the HLS playlist with segment paths replaced by MinIO URLs.
- **Key endpoint:** `/api/lectures/{id}/key?token=...` returns the binary AES-128 decryption key.
- **Access checks:** Validates user status, entitlement (not expired), IP match, token expiry, blocking exams.
- **`canAccess()` fix:** Now correctly checks paid course enrollment (previously only worked for free courses + entitlements). Ensures students who paid for a course can access all lectures.
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
- **Mandatory exam-first redirect:** When a lecture has a blocking exam not yet passed, the exam tab is shown automatically on initial load (before the video). After passing, "متابعة المحاضرة" button returns to the video.
- **Sequential lock fix:** Access-based blocking (`!has_access`) no longer propagates to subsequent lectures. Only blocking exams/assignments propagate via `isBlockedByPreviousExam` — lectures are independently accessible.

**Tests:** ExamCrudTest (17) + ExamServiceTest (10) + PreExamGatingTest (8) + AssignmentTest (6) = 41 tests

#### 3.2.6 Enrollment & Progress
- **Enrollment:** Students enroll in courses (free courses via API, paid via order flow). Status: `active`/`expired`/`suspended`.
- **Revocation:** Instructors can revoke enrollments.
- **Progress tracking:** `StudentActivity` records for `video_progress` and `video_completed`.
- **Student statistics:** Aggregated `total_watch_minutes`, `completed_lectures`, `average_exam_score`.
- **Dashboard stats:** Instructor sees courses, students, revenue, lectures, pending orders. Student sees enrollments, completed lectures, watch time, scores.

**Tests:** EnrollmentFlowTest (13) + EnrollmentServiceTest (12) + ProgressTest (9) + DashboardTest (16) + DashboardServiceTest (12) = 62 tests

#### 3.2.7 Filament Admin Panel (Instructor/TA Interface)
13 Filament resources providing full admin UI:

| Resource | Features |
|---|---|
| **StudentResource** | List, approve, reject, edit, delete students; revoke entitlements |
| **CourseResource** | Full CRUD; Assistants relation manager; scoped to instructor |
| **LectureResource** | Full CRUD; nested exam/assignment repeaters; PDF file upload via Repeater; YouTube URL support |
| **ExamResource** | CRUD for exams; filters non-assignment exams |
| **AssignmentResource** | CRUD for assignments; Submissions relation manager |
| **ProductResource** | CRUD; polymorphic sellable selection (Course/Section/Lecture) |
| **BundleResource** | CRUD; Products relation manager |
| **OrderResource** | List orders; "Confirm Payment" action (triggers entitlement grant) |
| **AssistantResource** | CRUD for teaching assistants; scoped to assistant role |
| **QAResource** | View questions; reply to students; real-time polling (15s) |
| **EntitlementResource** | View all lecture-level access grants; student code, name, lecture, course, amount paid, expiry, grant date |
| **EnrollmentResource** | View all course-level enrollments; Grant Access modal for manual enrollment |
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
  - `CheckEnrollment`: Verifies access to lectures (instructor ownership, assistant assignment, student entitlement, or free enrollment + blocking exam check). Also validates entitlement-only access for single-lecture purchases.
  - `SecurityHeaders`: HSTS, CSP, X-Frame-Options, etc.
  - `TurnstileRule`: Cloudflare Turnstile captcha validation (bypassed in testing env).
  - `InjectBearerFromQuery`: Extracts Sanctum token from query param for Video.js (which can't send custom headers).
- **Rate limiting:** Login throttling, video streaming throttling.
- **Token security:** Video access tokens encrypted, IP-bound, lecture-bound, 5-minute expiry.
- **Password security:** bcrypt hashing, strong password rules.

**Tests:** MiddlewareTest (10) + RateLimitTest (3) + RolesAndAuthTest (10) = 23 tests

#### 3.2.9 Q&A System (Fully Implemented)
- **Backend API (9 endpoints):**
  - `POST /lectures/{id}/questions` — Students post questions under lectures (enrollment required).
  - `GET /lectures/{id}/questions` — List all questions for a lecture (public Q&A board — all students see all questions).
  - `GET /questions/{id}` — View a specific question with all replies.
  - `POST /questions/{id}/replies` — Any authenticated user (student, instructor, assistant) can reply.
  - `GET /my-questions` — Student's own questions across all courses.
  - `GET /instructor/questions` — Instructor's questions across own courses (scoped).
  - `GET /assistant/questions` — Assistant's questions across assisted courses (scoped).
  - `DELETE /questions/{id}` — Delete own questions.
  - `DELETE /replies/{id}` — Delete own replies.
- **Notifications:** New question → notifies instructor + assistants. New reply → notifies question author.
- **Filament integration:** `QAResource` with 15-second polling, reply actions, role-scoped views.
- **Form requests:** `StoreQuestionRequest`, `StoreReplyRequest` with validation.
- **HTTP Resources:** `QuestionResource`, `QuestionReplyResource` for consistent API responses.

**Tests:** QATest (27 tests)

#### 3.2.10 Notifications
- **In-app notifications:** `NotificationService` sends to users (title + body).
- **Triggers implemented:** New student registration → notify all instructors; Student approval → notify student; Student rejection → notify student; New Q&A question → notify instructor + assistants; New Q&A reply → notify question author.

**Tests:** NotificationGapsTest (9) — includes both existing notifications and documented gaps

#### 3.2.11 Password Reset Flow
- **Forgot password:** `POST /auth/forgot-password` — generates a reset token, sends email via configured mail driver.
- **Reset password:** `POST /auth/reset-password` — validates token, updates password, invalidates old tokens.
- **Frontend pages:** `/forgot-password` (request form) and `/reset-password` (new password form) with Arabic UI.
- **Rate limiting:** Throttled at `login` rate limit to prevent abuse.
- **Mailpit integration:** Development emails captured by Mailpit container for testing.

#### 3.2.12 Student Frontend (Next.js 16 — Full SPA)
A complete Arabic RTL student-facing web application:

**Route Groups:**
- `(main)` — Public pages: homepage, course catalog, course detail, product pages, bundle pages
- `(auth)` — Authentication: login, register (multi-step), forgot password, reset password
- `(player)` — Enrolled content: course play page, lecture player (video + exam + Q&A tabs)
- `(dashboard)` — Student dashboard: overview, courses, exams, questions, notifications, settings

**Key Pages:**
| Page | Features |
|---|---|
| **Homepage** | Hero section, featured courses, course catalog with search/pagination |
| **Course Detail** | Content tree, instructor info, pricing, enrollment/purchase buttons |
| **Lecture Player** | Video.js HLS player (with DRM/encryption), tabbed interface (Overview, Resources, Q&A) |
| **Dashboard** | Stats cards, enrolled courses, progress tracking, quick actions |
| **My Courses** | Course cards with progress bars, continue learning buttons |
| **My Exams** | Exam list, attempt history, scores |
| **My Questions** | Question cards, reply forms, real-time polling |
| **Notifications** | Notification feed with read/unread status |
| **Settings** | Profile management |

**Technical Implementation:**
- **41 React components** — shadcn/ui (Button, Card, Dialog, Input, Select, Tabs, Table, etc.) + custom components
- **8 API service modules** — Typed axios clients for auth, courses, exams, dashboard, products, QA, enrollment, misc
- **8 React Query hooks** — `useCourses`, `useExams`, `useDashboard`, `useEnrollment`, `useProducts`, `useQA`, `useMyQuestions`
- **TypeScript throughout** — Full type safety for API responses, models, forms
- **Tailwind CSS v4** — Utility-first styling with Arabic RTL support
- **shadcn/ui components** — Accessible, customizable component library
- **React Query** — Server state management, caching, optimistic updates, polling
- **Theme provider** — Light/dark mode support
- **Responsive design** — Mobile-first layout with sidebar navigation

**Frontend Testing (Vitest + React Testing Library):**
| Test File | Tests | Coverage |
|---|---|---|
| `enrollment.service.test.ts` | 3 | Service API calls, response shapes |
| `course.service.test.ts` | 4 | API calls, data unwrapping, params |
| `dashboard.service.test.ts` | 4 | API calls, typed responses |
| `auth-guard.test.tsx` | 5 | Loading, auth, guest guards |
| `useQA.test.tsx` | 3 | Query fetching, mutations, invalidation |
| `useEnrollment.test.tsx` | 3 | Query fetching, mutations |
| `useDashboard.test.tsx` | 2 | Query fetching, typed responses |
| `quiz-tab.test.tsx` | 4 | Loading, error, exam display, start |
| **Total** | **30 tests** | Services, hooks, components |

#### 3.2.13 Real-time Features (Polling-Based)
- **Q&A polling:** 15-second `refetchInterval` on lecture questions, dashboard questions.
- **Toast notifications:** `useQAReplyTracker` hook tracks reply count changes via `useRef`, displays Sonner toasts when new replies arrive.
- **Filament polling:** `->poll('15s')` on QAResource table for instructor view.
- **No WebSocket infrastructure:** Deliberately chose polling over WebSockets for simplicity and reliability.

#### 3.2.14 Filament Access Management Resources
- **EntitlementResource:** Displays all lecture-level access grants with student code, name, lecture, course, amount paid, expiry, and grant date. Role-scoped (instructors see own courses, assistants see assisted courses, super admin sees all). Navigation: "صلاحيات الوصول" with key icon.
- **EnrollmentResource:** Displays all course-level enrollments. Includes "Grant Access" modal for manually enrolling students in courses. Role-scoped.

#### 3.2.15 Supporting Systems
- **Geography data:** Governorates, cities, schools (lookup tables for student profiles).
- **Grade levels & academic tracks:** For content organization and student filtering.
- **Activity logging:** Spatie ActivityLog for audit trail on admin/instructor actions.
- **Unique code generation:** Collision-resistant codes for students, assistants, courses.

**Tests:** MiscTest (6) + EdgeCaseTest (7) = 13 tests

### 3.3 Test Suite Summary

#### Backend Tests (407 tests — Pest PHP)

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
| Q&A | 1 | 27 | ~70 |
| Notifications | 1 | 9 | ~20 |
| Edge Cases & Misc | 2 | 13 | ~30 |
| Background Jobs | 1 | 5 | ~10 |
| **Total (Backend)** | **38 files** | **407 tests** | **893 assertions** |

#### Frontend Tests (30 tests — Vitest + React Testing Library)

| Category | Test Files | Tests | Coverage |
|---|---|---|---|
| **Services** | 3 | 11 | enrollment, course, dashboard API calls |
| **Hooks** | 3 | 8 | useQA, useEnrollment, useDashboard |
| **Components** | 2 | 11 | AuthGuard, QuizTab |
| **Total (Frontend)** | **8 files** | **30 tests** | — |

#### Combined Total

| Suite | Tests | Framework |
|---|---|---|
| Backend | 407 | Pest PHP v4 |
| Frontend | 30 | Vitest + @testing-library/react |
| **Grand Total** | **437 tests** | — |

**Testing approach:**
- **Backend Framework:** Pest PHP v4 (expressive, closure-based syntax)
- **Frontend Framework:** Vitest + @testing-library/react + @testing-library/jest-dom + user-event
- **Backend Database:** SQLite in-memory (fast, isolated per test)
- **Frontend Environment:** jsdom (browser API simulation)
- **Queue:** Sync (jobs execute immediately in tests)
- **Cache:** Array driver
- **Mail:** Array driver
- **Key patterns:** RefreshDatabase trait, beforeEach setup, role seeding via Role::findOrCreate(), actingAs() for auth, Queue::fake() for job assertions, vi.mock() for module mocking, QueryClientProvider wrapper for React Query hooks.

**Running tests:**
```bash
# Backend (from src/)
php artisan test
# or
vendor/bin/pest

# Frontend (from frontend/)
npm run test          # Single run
npm run test:watch    # Watch mode
```

---

## 4. What's NOT Yet Implemented (Gaps & Known Issues)

### 4.1 Payment Gateway Integration
- **Current:** Orders are `pending` until manually confirmed in Filament. No actual Paymob/Kashier integration.
- **Missing:** Webhook handlers for payment callbacks, signature verification, redirect flows, refund handling.
- **Impact:** Instructors must manually verify each payment — acceptable for launch but must be automated at scale.

### 4.2 Assistant Granular Permissions
- **Current:** Assistants have a binary role — either assigned to a course or not.
- **Missing:** Per-assistant checkbox permissions (can_grade, can_answer_qa, can_approve, can_upload, can_manage_exams, can_view_reports, can_manage_notifications).
- **Impact:** Assistants currently get full access to their assigned courses — no fine-grained control.

### 4.3 Missing Notification Types
- **Current:** Registration, approval/rejection, Q&A questions and replies.
- **Missing:** Purchase confirmation, exam grading result, subscription expiry, new lecture published.

### 4.4 Q&A Image Attachments
- **Current:** Text-only questions and replies.
- **Missing:** Image/file attachment upload for questions and answers.
- **Impact:** Students and instructors cannot share screenshots, diagrams, or code snippets.

### 4.5 Real-time Features (WebSocket Upgrade)
- **Current:** Polling-based (15-second intervals) for Q&A and Filament.
- **Missing:** Laravel Reverb (WebSocket server) for instant notifications, live Q&A updates, live progress tracking.
- **Impact:** Good enough for MVP but polling doesn't scale well at high concurrency.

### 4.6 Video Hosting Provider Integration
- **Current:** Videos processed locally via FFmpeg to HLS, stored on MinIO (S3-compatible).
- **Planned:** Bunny Stream integration for production (instructor's own account, signed URLs, CDN delivery).

### 4.7 Health Check Endpoint
- **Missing:** No `/up` or `/health` endpoint for monitoring and load balancer health checks.

### 4.8 File Upload via Filament
- **Current:** Lecture PDF files can be uploaded via the LectureResource form (Repeater). No dedicated file management.
- **Missing:** Dedicated file management page in Filament for bulk operations, file preview, file versioning.

### 4.9 Student-Facing Mobile App
- **Current:** Next.js 16 web app (PWA-capable).
- **Missing:** React Native mobile app for offline access, push notifications, native camera integration.

---

## 5. Future Roadmap

### Phase 2 (Post-MVP)
- Payment gateway integration (Paymob/Kashier webhooks)
- Assistant granular permission checkboxes
- Missing notification types (purchase confirmation, exam grading, subscription expiry, new lecture published)
- Single-lecture and bundle purchases via payment gateway
- Assignments with file upload and manual grading
- Laravel Reverb for real-time (WebSocket upgrade from polling)
- SMS/OTP registration verification
- Health check endpoint (`/up`)
- Q&A image/file attachments
- Meilisearch for course catalog search

### Phase 3 (Scale)
- React Native mobile app (automated white-label builds)
- Advanced analytics and reporting (instructor dashboards, student engagement metrics)
- Push notifications (FCM/APNs)
- Video DRM (if piracy becomes measurable)
- Desktop app (Electron, if validated)
- Multi-language support (English UI option)
- AI-powered features (auto-grading essays, content recommendations)

---

## 6. Non-Functional Requirements

| Requirement | Detail |
|---|---|
| **Performance** | API response time under 300ms for core requests, per instance. Frontend loads under 2s on 3G. |
| **Availability** | 99.5% uptime target per instance |
| **Localization** | Full Arabic (RTL) support across all interfaces — frontend, Filament, emails |
| **Accessibility** | WCAG 2.1 AA compliance via shadcn/ui (keyboard navigation, screen reader support, focus management) |
| **Security** | HTTPS/TLS everywhere, encrypted video content, rate limiting, audit logging, PII minimization |
| **Legal Compliance** | Egypt's Personal Data Protection Law (Law No. 151 of 2020) — parental consent at registration, data retention/deletion policy |
| **Responsive Design** | Mobile-first frontend layout, works on phones (320px+) through desktop (1920px+) |
| **Browser Support** | Chrome 90+, Firefox 90+, Safari 15+, Edge 90+ (evergreen browsers) |

---

## 7. Project Structure

```
edu-platform/
├── PRD.md                              ← This document
├── docker-compose.yml                  ← Docker stack (Laravel Sail)
├── src/                                ← Laravel backend
│   ├── app/
│   │   ├── Enums/                      ← UserStatus, CourseStatus, EnrollmentStatus
│   │   ├── Filament/                   ← Admin panel (13 resources, 3 relation managers)
│   │   ├── Http/
│   │   │   ├── Controllers/Api/        ← 10 API controllers
│   │   │   ├── Middleware/             ← 5 custom middleware
│   │   │   └── Requests/              ← 5 form request validators
│   │   ├── Jobs/                       ← 1 queued job (ProcessVideoHLS)
│   │   ├── Models/                     ← 34 Eloquent models
│   │   ├── Rules/                      ← TurnstileRule (captcha)
│   │   └── Services/                   ← 12 service classes
│   ├── config/                         ← Laravel + app config
│   ├── database/
│   │   ├── migrations/                 ← 30+ migration files
│   │   └── seeders/                    ← Database seeders
│   ├── routes/
│   │   └── api.php                     ← ~55 API routes
│   ├── tests/
│   │   ├── Feature/Api/                ← 30+ API test files
│   │   ├── Feature/Filament/           ← 1 Filament test file
│   │   └── Pest.php                    ← Test configuration
│   └── phpunit.xml                     ← Test configuration (SQLite, sync queue)
└── frontend/                           ← Next.js 16 student-facing SPA
    ├── package.json                    ← Dependencies (Next.js 16, React 19, shadcn/ui)
    ├── tsconfig.json                   ← TypeScript configuration
    ├── vitest.config.ts                ← Vitest test configuration
    ├── next.config.ts                  ← Next.js config (API proxy, env vars)
    └── src/
        ├── app/                        ← App Router (4 route groups)
        │   ├── (main)/                 ← Public pages (homepage, courses, products)
        │   ├── (auth)/                 ← Auth pages (login, register, forgot/reset password)
        │   ├── (player)/               ← Enrolled content (play page, lecture player)
        │   └── (dashboard)/            ← Student dashboard (overview, courses, exams, questions)
        ├── components/                 ← 41 React components
        │   ├── player/                 ← LecturePlayer, VideoPlayer, QATab, QuizTab, ResourcesTab
        │   ├── dashboard/              ← DashboardSidebar, MobileNav, StatsCards
        │   └── ui/                     ← shadcn/ui components (Button, Card, Dialog, Input, etc.)
        ├── hooks/                      ← 8 React Query hooks (useCourses, useQA, useExams, etc.)
        ├── services/                   ← 8 API service modules (axios clients)
        ├── types/                      ← 8 TypeScript type definitions
        ├── providers/                  ← Theme, Query, Root providers
        ├── lib/                        ← Utilities, constants, cn()
        ├── config/                     ← Environment config
        └── test/                       ← Frontend test setup (Vitest)
            ├── setup.ts                ← Test environment configuration
            └── __tests__/              ← 8 test files (30 tests)
```

---

## 8. Key Domain Models (34 Total)

| Domain | Models |
|---|---|
| **Users & Auth** | User, Role, Permission, PersonalAccessToken |
| **Student Profile** | Student, Governorate, City, School, GradeLevel, AcademicTrack |
| **Content** | Course, CourseSection, Lecture, LectureVideo, LectureFile |
| **Exams & Assignments** | Exam, Question, Choice, Answer, ExamAttempt |
| **Commerce** | Product, Bundle, Order, Payment, Entitlement |
| **Enrollment** | Enrollment, CourseAssistant |
| **Progress & Activity** | StudentActivity, StudentStatistic |
| **Communication** | Notification, QuestionsPost, QuestionReply |
| **Audit** | Activity (Spatie ActivityLog) |

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
- **File download protection:** Files proxied through backend routes with enrollment/entitlement checks — no direct MinIO URL exposure to clients.
- **Payment safety:** Orders start as `pending`, require manual confirmation before entitlements are granted.
- **Data privacy:** PII minimized, Egyptian data protection law compliance, audit logging via Spatie ActivityLog.
- **Infrastructure:** Security headers middleware (HSTS, CSP, X-Frame-Options), rate limiting, bcrypt passwords.
- **Frontend:** Token stored in localStorage, axios interceptor auto-attaches Bearer header, 401 responses clear token and redirect to login.

---

## 11. Lessons Learned During Development

### 11.1 Pest PHP v4 Behavior
- **`uses()` without `->in()`** only targets `tests/Pest.php` itself — must chain `->in('Feature')` to apply to test files.
- **Global `beforeEach`** must be chained on `uses()`, not standalone.
- **`(int)` casts** needed for `round()` comparisons — Pest's `toBe()` does strict type comparison.

### 11.2 Laravel Model Observers
- `Lecture::booted()` observer fires on `saved` event (both create and update). The dispatch condition must check `wasChanged('video_path')`, `wasRecentlyCreated`, AND `$hasVideo` to avoid unnecessary re-processing.
- Eloquent model defaults set via `Schema::table()->default()` are not reflected on the model object after `create()` without explicit passing or `fresh()`.

### 11.3 Filament v5.6.8 Behavior
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

### 11.7 Frontend Testing & Code Quality
- **Vitest + React Testing Library** is the modern standard for Next.js testing. Vitest is faster than Jest and has native ESM/TypeScript support.
- **Service layer tests** are high-value, low-effort — they verify API contract compliance without mocking HTTP.
- **Hook tests** require a `QueryClientProvider` wrapper — create a `createWrapper()` helper that returns a fresh QueryClient per test to avoid state leakage.
- **Component tests** benefit from mocking heavy dependencies (navigation, React Query) at the module level with `vi.mock()`.
- **Code audit pattern:** Systematic categorization (API Integration, State Management, Routing, Cache) with severity levels helps prioritize fixes. Many "critical" issues turn out to be already fixed — always verify before rewriting.

### 11.8 MinIO Signed URL Pitfalls
- **Hostname mismatch breaks signatures:** MinIO generates signed URLs with the internal hostname (`minio:9000`), but rewriting to `localhost:9000` invalidates the signature. Solution: proxy file downloads through a backend route that streams from MinIO internally.
- **`$file->file_path` goes through the model accessor:** The `LectureFile` model's `getFilePathAttribute` accessor returns a full MinIO temporary URL, not the raw storage path. Accessing `$file->getOriginal('file_path')` or `$file->getAttributes()['file_path']` bypasses the accessor.
- **`response()->stream()` for file proxying:** Use `Storage::disk('minio')->readStream()` + `response()->stream()` for memory-efficient file proxying. Set `Content-Disposition: attachment` for downloads.
- **Frontend blob download:** Use `api.get(url, { responseType: "blob" })` with `URL.createObjectURL()` for authenticated file downloads. Axios interceptors automatically include the Bearer token.

### 11.9 Entitlement-Based Access Routing
- **Fake enrollment detection:** `EnrollmentService::getStudentEnrollments()` creates synthetic enrollment records (`id = 'entitlement-fake-{courseId}'`) for students with entitlement-only access. Frontend must detect these via `String(id).startsWith("entitlement-fake-")` to avoid routing errors.
- **Per-lecture independent access:** Lectures should be independently accessible, not cascading. Sequential blocking should only propagate from blocking exams, not from access denial. Using `isBlockedByPreviousExam` instead of `!has_access` prevents the entire sidebar from locking.
- **Mandatory exam-first UX:** When a lecture has a blocking exam not yet passed, show the exam tab automatically on initial load. The "متابعة المحاضرة" (Continue Lesson) button in the exam tab returns to the video after passing.

### 11.10 Next.js 16 + React Query Patterns
- **App Router layouts for route groups:** Using `(main)`, `(auth)`, `(player)`, `(dashboard)` route groups allows different layouts (sidebar vs. no sidebar) without duplicate page components.
- **React Query polling over WebSocket:** For a single-tenant platform with moderate user counts, 15-second polling with `refetchInterval` is simpler and more reliable than WebSocket infrastructure. Pair with `useRef` tracking + toast notifications for new data detection.
- **Filament `poll()` method:** Filament v5.6.8 uses `$table->poll('15s')` (not `polling()`). This is a breaking change from v4.
- **`course_assistants` pivot:** Uses `user_id` column (not `assistant_id`). Always verify pivot table schema before writing relationship queries.

### 11.11 Laravel 13 Response Methods
- **`response()->redirect()` removed:** In Laravel 13, `ResponseFactory::redirect()` no longer exists. Use `redirect()->away()` or `return redirect($url)` instead.
- **Route parameter binding:** When a route has `lectures/{lecture}/files/{file}`, ensure the `{file}` parameter resolves to the correct model. Route model binding works but verify with `route:list` that the route is registered correctly.
