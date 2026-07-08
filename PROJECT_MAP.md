# PROJECT_MAP — Instructor Education Platform

> Last updated: 2026-07-08
> Architecture: Modular Monolith (DDD-ready)
> Build phase: Pre-MVP (Walking Skeleton)
> SDLC phase: Design — awaiting implementation approval
> SWE principles: Modularity | Simplicity | Requirements-first | Quality-gated

---

## TECH_STACK

| Layer | Technology | Version | Status |
|---|---|---|---|
| Backend | Laravel | v13.19.0 | Latest stable — verified 2026-07-08 |
| Admin Panel | Filament | v5.6.8 | Latest stable (v5.7.0 is beta) |
| DB | PostgreSQL | 18-alpine | Latest stable |
| Cache/Queue | Redis | 7-alpine | Latest stable |
| Queue UI | Laravel Horizon | v5.9.9 | Latest stable |
| Realtime | Laravel Reverb | v1.9.0 | Latest stable |
| Auth (API) | Laravel Sanctum | v4.3.2 | Latest stable |
| RBAC | Spatie Laravel Permission | v8.3.0 | Latest stable |
| Student Frontend | Next.js | 16.2.10 | Latest stable |
| UI Library | React | 19.2.7 | Latest stable |
| Language | TypeScript | 6.0.3 | Latest stable |
| Styling | TailwindCSS | v4.3.2 | CSS-first config (v4, no tailwind.config.js) |
| Component System | shadcn/ui | latest (CLI) | Flat-file components via `shadcn@latest` CLI; fully open code |
| Icons | Lucide React | latest | Default icon library for shadcn/ui |
| Forms | React Hook Form | latest | Recommended form library for shadcn/ui |
| Mobile | PWA from Next.js | — | MVP/Phase 2; native deferred |
| Video Hosting | Bunny Stream | API v2 | Instructor's own account |
| File Storage | Cloudflare R2 | S3-compat | Instructor's own account; MinIO for local dev |
| Payments | Paymob / Kashier | — | Instructor's own account |
| Search | Meilisearch | — | Deferred (optional per instance) |
| Email Dev | Mailpit | latest | Replace with SES/Resend in production |
| Container | Docker Compose | v2.29+ | WSL2-based dev/prod parity |
| PHP | PHP | 8.4.23 | Required by Laravel 13 |
| Web Server (Laravel) | Nginx | 1.27-alpine | Reverse proxy for Laravel |

### Dependency notes
- No deprecated packages detected.
- **shadcn/ui**: not an npm package — CLI copies component source into `frontend/components/ui/*`. Full ownership, no vendor lock-in.
- **TailwindCSS v4**: CSS-first config via `@import "tailwindcss"` — no `tailwind.config.js`.
- **shadcn/ui RTL**: first-class Arabic RTL support via CLI `--rtl` flag — auto-converts `left`/`right` to `start`/`end` logical properties.
- **Sanctum v4**: ships with Laravel 13 natively.
- **Noto fonts**: recommended for Arabic/RTL text (pairs with Inter/Geist for UI).
- All packages confirmed compatible with PHP 8.4.

---

## SYSTEM_FLOW

### 1. Deployment & Provisioning Flow
```
Vendor tags release
  → CI builds versioned Docker images
  → Instructor (or vendor with temp access) pulls image on VPS
  → docker compose up -d
  → First-run migration seeds Instructor account
  → Instructor configures branding / payment / video keys via Filament
  → Platform live on instructor's custom domain
```

### 2. Student Journey (MVP)
```
Land on instructor's domain
  → Browse public course catalog
  → Register (instant activation — MVP decision)
  → Purchase full course via Paymob/Kashier
  → Entitlement granted immediately via webhook
  → Watch lecture videos (Bunny Stream signed URLs)
  → View PDF attachments (R2 signed URLs)
  → Take MCQ exam (auto-graded)
  → View score & track progress
```

### 3. Instructor Journey (MVP)
```
Login to Filament admin panel
  → Create course → Add sections → Add lectures
  → Upload video (direct to Bunny Stream via API)
  → Upload PDF (to R2)
  → Create MCQ exam with questions/answers
  → View enrollments and sales
  → Configure branding / payment / storage keys
```

### 4. Purchase & Entitlement Flow (Critical Path)
```
Student clicks "Buy Course" → API → CreateOrder
  → Redirect to Paymob/Kashier iframe
  → Payment processed on gateway (instructor's account)
  → Webhook received on /api/webhooks/payment
  → PaymentService verifies signature
  → Order marked paid → EntitlementService grants access
  → Student can now view all course lectures
```

### 5. Data Flow: API Request
```
Next.js → REST (Sanctum token) → routes/api.php
  → Controller (thin: parse input, call service)
  → Service (business logic, no Eloquent awareness)
  → Model/DB
  → Resource/Response
  → Next.js renders
```

### 6. Data Flow: Filament Action
```
Filament (session auth) → Service
  → Model/DB
  No HTTP API needed — same Laravel process
```

---

## ARCHITECTURE

### High-Level Structure
```
edu-platform/
├── docker-compose.yml       ← Single compose for all services
├── Dockerfile               ← PHP 8.4 FPM Alpine
├── Dockerfile.nextjs        ← Node 22 multi-stage for Next.js
├── nginx/
│   └── default.conf
├── .env.example
├── PROJECT_MAP.md
├── src/                     ← Laravel application (Modular Monolith)
│   ├── app/
│   │   ├── Filament/        ← Instructor/TA panel
│   │   │   └── Resources/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   ├── Middleware/
│   │   │   └── Requests/
│   │   ├── Models/          ← Eloquent models
│   │   ├── Services/        ← ALL business logic (fat services, thin controllers)
│   │   │   ├── Entitlement/
│   │   │   ├── Commerce/
│   │   │   ├── Assessment/
│   │   │   ├── Media/
│   │   │   └── ...
│   │   ├── Jobs/
│   │   ├── Events/ Listeners/
│   │   ├── Mail/
│   │   ├── Enums/
│   │   └── Exceptions/
│   ├── config/
│   ├── database/
│   │   └── migrations/
│   ├── routes/
│   │   ├── api.php          ← Student API
│   │   ├── web.php          ← Webhooks, OAuth only
│   │   └── filament.php     ← Auto by Filament
│   └── resources/
├── frontend/                ← Next.js 16 student app (App Router)
│   ├── app/                 ← App Router: pages, layouts, loading states
│   │   ├── (auth)/          ← Login, register pages
│   │   ├── (dashboard)/     ← Student dashboard (protected)
│   │   ├── courses/         ├─ Catalog & course detail
│   │   └── lectures/        ├─ Video player, PDF viewer, exam
│   ├── components/
│   │   ├── ui/              ← shadcn/ui components (installed via CLI)
│   │   ├── layout/          ← Navbar, sidebar, footer
│   │   ├── forms/           ← Form components (React Hook Form)
│   │   └── shared/          ← Shared feature components
│   ├── lib/
│   │   ├── api-client.ts    ← Axios/fetch wrapper, Sanctum token mgmt
│   │   ├── auth-context.tsx ← Auth provider (React Context)
│   │   ├── utils.ts         ← cn() helper, misc utilities
│   │   └── validations/     ← Zod schemas matching Laravel validation
│   ├── hooks/               ← Custom React hooks
│   ├── public/
│   ├── styles/
│   │   └── globals.css      ← TailwindCSS v4 import + shadcn theme vars
│   ├── components.json      ← shadcn/ui config
│   ├── next.config.ts
│   ├── tailwind.config.ts   ← Not needed in v4 (CSS config instead)
│   └── package.json
└── docs/                    ← Setup guides, runbooks
```

### Frontend Architecture

#### shadcn/ui Integration
- **Not a package dependency** — components are copied to `frontend/components/ui/` via `pnpm dlx shadcn@latest add <component>`.
- **Full code ownership** — every component is editable source code, no wrapper layers needed.
- **RTL by default** — project initialized with `shadcn@latest init --rtl`; CLI auto-transforms physical CSS classes (`left`/`right`) to logical equivalents (`start`/`end`) for Arabic.
- **Component selection for MVP** (installed and themed during M0):
  - `button`, `card`, `input`, `label`, `form`, `select` — core form/page building
  - `dialog`, `sheet` — modals, checkout overlay
  - `toast`, `sonner` — notifications
  - `skeleton` — loading states
  - `badge`, `avatar`, `separator` — UI elements
  - `data-table` — course/student listings
  - `tabs`, `accordion` — section/lecture navigation
  - `progress` — watch progress bars
  - `pagination` — browsing courses
  - `direction` — RTL context provider wrapper
- **Icons**: Lucide React (`lucide-react`), installed as npm dependency.
- **Forms**: React Hook Form + Zod validation (schemas mirror Laravel `Requests/` validation rules).

#### Arabic/RTL Strategy
- `DirectionProvider` wraps the root layout (from shadcn `direction` component).
- `dir="rtl"` set on `<html>` element.
- Noto Sans Arabic for body text; Inter for numbers/English.
- TailwindCSS v4 logical properties used throughout (e.g., `ps-4` not `pl-4`).
- All shadcn components installed with `--rtl` flag; icons flipped via `rtl:rotate-180`.

#### Next.js Route Design (MVP)
| Route | Auth | Description |
|---|---|---|
| `/` | Public | Landing / course catalog |
| `/login` | Public | Student login |
| `/register` | Public | Student registration (instant) |
| `/courses` | Public | Course catalog |
| `/courses/[id]` | Public | Course detail + section list |
| `/courses/[id]/lectures/[lid]` | Auth+Entitlement | Video player, PDF viewer |
| `/courses/[id]/lectures/[lid]/exam` | Auth+Entitlement | Take exam |
| `/dashboard` | Auth | Student dashboard (progress, grades) |
| `/dashboard/courses` | Auth | My enrolled courses |
| `/dashboard/results` | Auth | Exam scores |
| `/checkout/[courseId]` | Auth | Purchase page |
| `/checkout/success` | Auth | Post-payment confirmation |

#### Data Fetching Pattern
```
Server Component → fetch() directly to Laravel API
  └─ On mutation: Client Component → useApi() hook → POST to Laravel
  └─ Auth token: HttpOnly cookie (Laravel Sanctum SPA mode) or Bearer header
```

---

### Domain Mapping (Bounded Contexts)

| Domain | Core Entities | Filament Resource? | API Endpoints |
|---|---|---|---|
| **Identity** | User, Role, Permission | Yes (User mgmt) | Auth (login/register) |
| **Students** | Student, StudentStatistics, StudentActivity | Yes | Profile, stats |
| **Courses** | Course, Section, Lecture, LectureVideo, LectureFile | Yes | Catalog, content |
| **Commerce** | Product, Price, Bundle, Order, Payment, Entitlement, Subscription | Yes | Order, webhook |
| **Assessment** | Exam, Question, Choice, ExamAttempt, Answer | Yes | Take exam, submit |
| **Assignments** | Assignment, AssignmentSubmission | Yes | Submit, grade |
| **Q&A** | QuestionPost, QuestionReply | Yes | CRUD per lecture |
| **Notifications** | Notification | Read-only | List, mark read |
| **Analytics** | ActivityLog | Dashboard widgets | Chart data |
| **Media** | Media | — | Upload, serve |
| **Geography** | Governorate, City, School, GradeLevel, AcademicTrack | Yes | Lookups |

### Key Architectural Rules
1. **Controllers NEVER contain business logic** — call a Service class.
2. **Filament Resources NEVER contain business logic** — call a Service class.
3. **EntitlementService** is the single gate: every content access check goes through it.
4. **Payment gateway abstraction** — `PaymentGatewayInterface` with Paymob/Kashier implementations.
5. **Video hosting abstraction** — `VideoHostInterface` with BunnyStream implementation.
6. **Storage abstraction** — Laravel's Filesystem with R2 (production) / MinIO (local) disks.
7. **Per-instance config** — all in `.env` / config files, never hardcoded.
8. **No cross-module direct DB queries** — use Service method calls.

### Commerce ERD (Full Schema — MVP wiring only)
```
Product (id, type[lecture/course/subscription], reference_id, name, price)
  ↑
Price (id, product_id, amount, currency, starts_at, ends_at)  ← for discounts

Bundle (id, name, price, active)
BundleItem (id, bundle_id, product_id)

SubscriptionPlan (id, name, price, interval_months, lectures_per_month, active)

Order (id, student_id, total, status[ pending/paid/failed/refunded ], metadata)
OrderItem (id, order_id, product_id, price_snapshot)

Payment (id, order_id, provider[paymob/kashier], transaction_id, amount, status, raw_webhook)

Entitlement (id, student_id, product_id, order_id, starts_at, expires_at, metadata)

Enrollment (id, student_id, course_id, status, started_at, expires_at)
  — kept for backward compat; Entitlement is source of truth going forward
```

### Software Engineering Principles (Governance)

These rules govern ALL code produced for this project, whether by humans or AI agents.

| Principle | Enforcement |
|---|---|
| **Modularity** | Bounded contexts (Identity, Courses, Commerce, Assessment) are independent modules. No cross-module DB queries — only Service method calls. `app/Services/` is the only cross-module bridge. |
| **Simplicity** | Walking Skeleton MVP. Every feature must answer: *"If this doesn't exist, does the product still work end-to-end?"* If yes → defer. |
| **Requirements-first** | No code is written without a corresponding requirement in `LMS_PRD.md`. Changes to scope require updating the PRD first, then code. |
| **Quality-gated** | Every merge must pass: PHPStan level 6, Pint formatting, PHPUnit tests, security scan. AI-generated code goes through the review checklist (PRD Section 5.5). |
| **Thin controllers** | Controllers parse input and call Services. Zero business logic in controllers. Zero business logic in Filament Resources. |
| **Config over code** | Every per-instance difference (branding, payment keys, video hosting) lives in `.env` — never in a code branch or fork. |
| **RTL-first** | All frontend development assumes Arabic RTL as the primary language. shadcn/ui `--rtl` flag is non-negotiable. |

### SDLC Application

```
Phase 1: Requirements  → LMS_PRD.md (done)
Phase 2: Design        → PROJECT_MAP.md, ERD, System Design (current phase)
Phase 3: Implementation → Domain-by-domain: Identity → Courses → Commerce → Assessment
Phase 4: Testing       → PHPUnit + Playwright E2E per domain, gated by CI
Phase 5: Maintenance   → Versioned Docker images; per-instance update via pull + migrate
```

---

### Logging Architecture
```
Laravel Monolog (async by default in v13)
  └─ Channel: stack = [daily, stderr]
  └─ Daily: storage/logs/laravel-{date}.log (90 day retention)
  └─ Stderr: docker logs

Audit trail (separate from app logs):
  └─ activity_logs table (actor_id, action, entity_type, entity_id, metadata, ip, user_agent)
  └─ Logged for: all Instructor/TA actions (grade, edit, refund, permission change)
  └─ NEVER log: student passwords, full payment card data, raw webhook secrets

Log levels used:
  emergency — system is unusable
  critical — critical condition (DB down, payment webhook failure)
  error — error conditions (payment declined, job failed after retries)
  warning — warning conditions (expired entitlement attempt, rate limit hit)
  notice — normal but significant (student registered, course purchased)
  info — informational (lecture watched, exam submitted)
  debug — debug-level messages (disabled in production)
```

---

## ORPHANS & PENDING

| Item | Status | Action Required |
|---|---|---|
| `src/` directory | ✅ Created (empty) | Laravel project not yet initialized |
| `frontend/` directory | ❌ Not created | Next.js 16 scaffold via `create-next-app` |
| `frontend/components/ui/` | ❌ Not created | shadcn/ui components to install via CLI (`shadcn@latest init --rtl`) |
| Laravel project init | ❌ Not started | `laravel new` inside `src/` with Breeze (API stack) + Sanctum + Horizon |
| `Dockerfile.nextjs` | ❌ Not created | Node 22 multi-stage: build → standalone output |
| MinIO service in docker-compose | ❌ Missing | Needed for local R2-compatible dev |
| `docker-compose.yml` Next.js service | ❌ Missing | Must add `nextjs` service |
| `docker-compose.yml` Mailpit version | ❌ Unpinned | Pin `axllent/mailpit:latest` to specific tag |
| Meilisearch service | 🟡 Deferred | Phase 2 — not in MVP compose |
| Update distribution script | ❌ Not designed | Must decide self-serve vs vendor-applied before Phase 2 |
| Instructor seeding migration | ❌ Not created | First-run seeder for initial instructor account |
| `docs/setup-guide.md` | ❌ Not created | Instructor-facing provisioning documentation |
| `docs/security-compliance.md` | ❌ Not created | Egypt PDL compliance documentation |
| `activity_logs` migration | ❌ Not created | Audit trail table |
| `.env.example` | ❌ Not created | Template for per-instance config |
| CI/CD pipeline | ❌ Not set up | GitHub Actions: test, static analysis, build, publish |
| PHPStan / Larastan config | ❌ Not created | Static analysis baseline level 6 |
| Laravel Pint config | ❌ Not created | PSR-12 + Laravel conventions |
| Initial migration files | ❌ Not created | All domain tables |
| `components.json` (shadcn) | ❌ Not created | Generated by `shadcn@latest init` |
| Noto Arabic font setup | ❌ Not configured | Google Fonts import in `globals.css` |
| Playwright E2E tests | ❌ Not created | Student purchase-to-watch flow |
| GitHub Dependabot config | ❌ Not created | Automated dependency vulnerability scanning |
