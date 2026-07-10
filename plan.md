# 🔍 Staff Engineer Analysis & Revised MVP Plan

## Current State Assessment

### ✅ What's Built (≈25% of MVP)
| Area | Status | Notes |
|---|---|---|
| **Docker Setup** | ✅ Working | PostgreSQL, Redis, Nginx, Queue, Scheduler, Mailpit |
| **Auth (Register/Login)** | ✅ Working | Student registration with approval flow, Sanctum tokens |
| **User & Student Models** | ✅ Working | With Geography relations |
| **Course CRUD** | ✅ Working | Basic API + listing |
| **Course Sections/Lectures** | ⚠️ Schema Only | Migration exists, no controllers/services/API |
| **Course Assistants** | ✅ Basic | Attach/detach via pivot table — no permissions yet |
| **Commerce Tables** | ⚠️ Schema Only | Products/Orders/Payments migrations — no services |
| **Exam Tables** | ⚠️ Schema Only | No services/controllers |
| **Filament Admin** | ⚠️ Minimal | Only StudentResource with approve/reject |
| **Frontend (Next.js)** | ⚠️ Partial | Landing, login, register, courses list, basic dashboard |
| **Entitlement System** | ❌ Missing | **Critical gap** — no access control for content |

---

## 🚨 Critical Issues Found

### 1. Architecture: "Domain" Folder ≠ Domain-Driven Design

> [!CAUTION]
> The codebase uses `app/Domain/` folder naming, but the actual structure is **standard MVC** with renamed folders. This creates **false confidence** that bounded contexts are enforced — they aren't.

**Evidence:**
- [DashboardController](file:///home/madany/Projects/edu-platform/src/app/Domain/Dashboard/Controllers/DashboardController.php) directly queries `Course`, `Enrollment`, `Student`, `CourseReview` — **4 different domains** in one controller with raw Eloquent queries (no service layer)
- [CourseAssistantController](file:///home/madany/Projects/edu-platform/src/app/Domain/Course/Controllers/CourseAssistantController.php) has business logic directly in controller (role checks, authorization)
- [CourseService](file:///home/madany/Projects/edu-platform/src/app/Domain/Course/Services/CourseService.php) is doing enrollment (Commerce concern) and reviews — violating bounded context boundaries
- Commerce domain has only Models — no Controllers, Services, or Requests

**Recommendation:** **Stop using `Domain/` folder**. For a 1-week MVP, this adds complexity with zero benefit. Use the PRD's own recommended pragmatic structure:

```
app/
├── Http/Controllers/Api/    ← API controllers
├── Http/Requests/           ← Validation
├── Services/                ← Business logic (flat, simple)
├── Models/                  ← Eloquent models  
├── Resources/               ← API Resources
├── Filament/Resources/      ← Admin panel
├── Enums/                   ← Status enums
└── Policies/                ← Authorization
```

---

### 2. Missing Entitlement System — MVP Blocker

> [!IMPORTANT]
> The PRD explicitly states: **"EntitlementService is the single gate: every content access check goes through it."** This does not exist. Students can currently enroll in any course with `POST /courses/{course}/enroll` with **zero payment check**.

The enrollment logic in [CourseService::enrollStudent](file:///home/madany/Projects/edu-platform/src/app/Domain/Course/Services/CourseService.php#L57-L68) creates an enrollment unconditionally.

**For MVP, simplify:** Enrollment = the instructor approved access. No complex Product/Price/Order abstraction yet. Just `enrollments` table with status.

---

### 3. Separation of Concerns Violations

| File | Violation |
|---|---|
| [DashboardController](file:///home/madany/Projects/edu-platform/src/app/Domain/Dashboard/Controllers/DashboardController.php) | 50 lines of raw Eloquent queries, no service layer, queries across 4 domains |
| [CourseAssistantController](file:///home/madany/Projects/edu-platform/src/app/Domain/Course/Controllers/CourseAssistantController.php#L26-L63) | Validation, role checks, and business logic inline in controller |
| [CourseService](file:///home/madany/Projects/edu-platform/src/app/Domain/Course/Services/CourseService.php) | Handles enrollments + reviews + course CRUD — 3 different concerns mixed |
| [StudentResource (Filament)](file:///home/madany/Projects/edu-platform/src/app/Filament/Resources/Students/StudentResource.php#L176-L217) | Business logic (approve/reject + notifications) directly in Filament Actions |
| [AuthService](file:///home/madany/Projects/edu-platform/src/app/Domain/Auth/Services/AuthService.php#L44-L51) | Queries ALL instructors and sends notifications — N+1 risk, should be a queued job |

---

### 4. Database: SQLite in Use, PostgreSQL Not Connected

> [!WARNING]
> There's a `database.sqlite` file in [database/](file:///home/madany/Projects/edu-platform/src/database/database.sqlite) and `.env` says `DB_CONNECTION=pgsql` but `DB_HOST=localhost` instead of `postgres` (the Docker service name). The app likely runs on SQLite fallback locally and never tested on PostgreSQL.

---

### 5. Missing Authorization Layer

- No Laravel Policies exist
- No middleware checks for instructor-only routes
- `CourseController::store/update/destroy` are accessible by any authenticated user — no role check
- No `Entitlement` check on content access

---

### 6. Frontend Issues

- [auth-context.tsx](file:///home/madany/Projects/edu-platform/frontend/src/contexts/auth-context.tsx#L14) still has `name` field in `RegisterData` interface (removed from backend)
- Token stored in localStorage — vulnerable to XSS (PRD suggests HttpOnly cookies)
- No protected route middleware — dashboard pages accessible without auth
- No course section/lecture viewing pages — only course list/detail
- Landing page content is generic, not white-label (should read from instructor config)

---

### 7. Over-Engineering for MVP

| Item | Problem | Action |
|---|---|---|
| `Category` system | PRD doesn't mention categories — instructor has ONE set of courses | **Remove** |
| `CourseReview` system | Not in MVP scope | **Remove** |
| `Products/OrderItems` tables | Full commerce abstraction before a single purchase flow works | **Defer** |
| `Subscriptions` table | Phase 2 | **Defer** |
| `student_documents` table | Not needed for MVP | **Defer** |
| 10 Domain folders | Over-segmentation for a 1-week build | **Flatten** |

---

### 8. Missing Core Pieces for MVP

| Missing | Priority | Notes |
|---|---|---|
| **Filament Course Management** | 🔴 Critical | Instructor can't create courses from admin panel |
| **Section/Lecture Management** | 🔴 Critical | No CRUD for sections or lectures |
| **Lecture Content Viewing** | 🔴 Critical | Students can't watch videos or view PDFs |
| **Enrollment Management** | 🔴 Critical | Instructor can't manually enroll/grant access |
| **Exam Taking Flow** | 🟡 Important | MCQ exam for students |
| **Video Integration (Bunny)** | 🟡 Important | Signed URL generation |
| **TA Permission System** | 🟡 Important | Your MVP says instructor assigns TAs |
| **Student Progress Tracking** | 🟢 Nice-to-have | Lecture completion tracking |

---

## Proposed Revised Plan — 7-Day Sprint

> [!IMPORTANT]
> This plan is **ruthlessly scoped**. Every feature answers: *"Does the MVP work without this?"* If yes → cut it.

### Day 1-2: Fix Foundation + Backend Core

#### Architecture Restructure
Flatten `app/Domain/` to standard Laravel structure. Move all code to:

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php
│   │   ├── CourseController.php
│   │   ├── SectionController.php
│   │   ├── LectureController.php
│   │   ├── EnrollmentController.php
│   │   └── ExamController.php
│   ├── Requests/
│   └── Middleware/
├── Services/
│   ├── AuthService.php
│   ├── CourseService.php
│   ├── EnrollmentService.php
│   ├── ExamService.php
│   └── NotificationService.php
├── Models/           ← All models flat
├── Resources/        ← API Resources
├── Enums/            ← Status enums (UserStatus, EnrollmentStatus)
├── Policies/         ← Authorization
└── Filament/
    └── Resources/    ← All admin resources
```

#### Database Fix
- Verify PostgreSQL connection works (fix `DB_HOST` to `postgres` for Docker, or `localhost` for local dev)
- Squash 21 migration files into clean, ordered set
- Remove unused tables: `categories`, `course_reviews`, `course_lessons`, `student_documents`
- Add `entitlements` table (simplified: `student_id`, `course_id`, `granted_by`, `expires_at`)

#### Core Services
- **EnrollmentService** — separated from CourseService, handles enrollment logic
- **CourseService** — only course CRUD
- Add **Enums**: `UserStatus`, `CourseStatus`, `EnrollmentStatus`
- Add **Policies**: `CoursePolicy` (instructor-only write operations)

---

### Day 3-4: Filament Admin Panel (Instructor's Main Interface)

This is where the instructor lives. It must be fully functional:

- **CourseResource** — Create/Edit/Delete courses with sections and lectures (nested repeater)
- **SectionResource** — Manage sections with drag-and-drop ordering
- **LectureResource** — Manage lectures, link videos (Bunny ID input), upload PDFs (R2/local)
- **EnrollmentResource** — View enrollments, manually grant/revoke access
- **StudentResource** — Already exists, just fix business logic extraction
- **Dashboard Widgets** — Total students, courses, enrollments stats
- **TA Management** — Add TAs via email, assign to courses

---

### Day 5-6: Student Frontend (Next.js)

- Fix auth flow (remove `name` field, proper error handling)
- **Course Detail Page** — Show sections > lectures hierarchy
- **Lecture View Page** — Video player (Bunny Stream iframe/embed), PDF viewer
- **Student Dashboard** — My courses, progress
- **Enrollment Check** — Middleware/guard to verify access before showing content
- Protected routes via middleware

---

### Day 7: Integration, Testing, Polish

- End-to-end flow test: Register → Approve → Enroll → View Lecture
- Fix CORS and API integration issues
- Basic seed data (instructor account, sample course)
- Docker Compose verification
- README with setup instructions

---

## Open Questions

> [!IMPORTANT]
> **Q1: Database — PostgreSQL or SQLite for development?**
> The Docker setup has PostgreSQL, but a `database.sqlite` exists. Are you running the app directly with `artisan serve` (outside Docker) or via Docker? This affects DB_HOST configuration.

> [!IMPORTANT]  
> **Q2: Do you want to restructure to flat `app/` or keep the `Domain/` folders?**
> I strongly recommend flattening for this timeline, but you may have strong preferences about keeping the current structure. The restructure takes ~2 hours but saves confusion for the remaining 6 days.

> [!IMPORTANT]
> **Q3: Video hosting — is Bunny Stream configured, or should I mock it?**
> For MVP, we can use a simple video URL field instead of full Bunny Stream API integration, and add the API integration in Phase 2.

> [!IMPORTANT]
> **Q4: Enrollment model for MVP — which approach?**
> - **Option A (Recommended):** Instructor manually enrolls students from Filament admin panel. No payment flow. Just grant access.
> - **Option B:** Simple payment webhook. Student pays → auto-enrollment.
> Which fits your first instructor client better?

---

## Verification Plan

### Automated Tests
```bash
php artisan test --filter=AuthTest
php artisan test --filter=CourseTest
php artisan test --filter=EnrollmentTest
```

### Manual Verification
1. **Instructor flow:** Login to Filament → Create course → Add sections → Add lectures → Enroll student
2. **Student flow:** Register → Get approved → Login → Browse courses → View enrolled course → Watch lecture
3. **TA flow:** Instructor adds TA → TA logs into Filament → TA can see assigned course students
