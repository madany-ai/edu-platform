# PRD — White-Label Self-Hosted Instructor Education Platform

**Date:** July 2026
**Owner:** Solo Developer (Product Vendor, building with AI coding agent assistance)
**Platform Type:** White-Label, Single-Tenant, Instructor-Hosted Educational Platform (sold per instructor)

---

## 1. Overview

### 1.1 Product Description
A deployable educational platform **product**, built once by the vendor (this developer) and sold to individual instructors as an independent, single-tenant instance. Each instructor gets their own fully isolated deployment, running under **their own custom domain**, with **their own branding** (name, logo, photo) — visitors and students only ever see that one instructor's platform, with no visibility into any other instructor or a shared marketplace. Each instance includes video lectures, PDFs, exams, assignments, flexible pricing (single lecture / full course / subscription / bundles), Teaching Assistants with scoped permissions, and a Q&A/FAQ system.

### 1.2 Problem Statement
- Individual tutors/instructors want a professional, fully-branded online teaching platform under their own name and domain, without building software themselves.
- Instructors want ownership and control over their hosting and their money (payment gateway, revenue) — they don't want a third party sitting between them and their students' payments.
- The vendor (this developer) wants to build **one reusable, well-engineered product** and deploy it repeatedly for many instructor clients, without the operational cost of running a live multi-tenant SaaS or manually rebuilding the platform per client.

### 1.3 Product Type & Business Model
- **Product type:** a white-label, single-tenant platform — one codebase, deployed independently per instructor.
- **Sale model:** the instructor makes a **one-time purchase** of the platform (not a recurring SaaS subscription, not a commission on their sales).
- **Hosting & money:** the **instructor** is responsible for their own server/hosting costs and for setting up and owning their own payment gateway account (Paymob/Kashier) — revenue from their students goes directly to them, not through the vendor.
- **Maintenance:** the **vendor** (this developer) is responsible for maintaining and updating the software (bug fixes, security patches, new features) across all deployed instances, via a versioned release process (see Section 6).

### 1.4 Scale Target — Reframed
Because each instructor runs an independent instance, "scaling to thousands of instructors" does **not** mean one system serving thousands of tenants simultaneously. It means two separate things:
1. **Per-instance scale:** a single instructor's deployment must comfortably handle that instructor's own student base (hundreds to tens of thousands of students) — this is the same technical scaling concern as before (Section 6.1).
2. **Fleet scale (the real challenge):** the vendor must be able to deploy, update, and maintain **many independent instances** without per-instance effort growing linearly. This is a packaging, automation, and release-management problem — solved primarily through Docker-based, config-driven deployment (Section 6.2) — not a database-scaling problem.

---

## 2. User Personas

### 2.1 Instructor (Instance Owner)
- The top authority within their own deployed instance — equivalent to what would be an "admin" role, but scoped to their single instance only.
- Creates courses, chapters, lectures, exams, and assignments.
- Sets pricing (per lecture / per course / bundles / subscription plans).
- Owns and configures their own payment gateway and video hosting accounts within their instance's settings.
- Adds and manages Teaching Assistants, assigning each one specific permissions — can grant a trusted TA **all available permissions**, effectively making them an operational manager, without needing a separate "admin" role in the system.
- Reviews sales reports and student performance analytics.
- Approves/activates new student account requests within their own instance (or delegates this to a TA with that permission).

### 2.2 Teaching Assistant (TA)
- Added by the instructor; scoped entirely to that instance (there is only one instructor per instance, so this is inherently true).
- Operates strictly within the permissions granted by the instructor (see 3.7).
- Common responsibilities: answering student questions, grading assignments, managing content, or — if given all permissions — acting as a full operational manager for the instance.

### 2.3 Student
- Visits the instructor's own branded domain (e.g., `ahmedmath.com`) — sees only that instructor's platform, courses, and identity.
- Account creation is a **request-based flow**: the student submits detailed information, and the account is manually activated by the instructor or an authorized TA (matching the platform's current registration UX).
- Watches lectures (video + PDF), takes a pre-lecture exam when required, completes assignments, and asks questions.
- Purchases access in the format that suits them (single lecture, full course, subscription, bundle).
- Needs to track personal progress and grades.

**Student Profile Fields (based on current registration form):**
| Field | Notes |
|---|---|
| First / Second / Third / Last Name | Full four-part Arabic name convention |
| Student Phone Number | Required |
| Father's Phone Number | Required |
| Mother's Phone Number | Required |
| Guardian's Occupation | Free text |
| Governorate | Dropdown (e.g., Suez) |
| Gender | Dropdown |
| Grade Level | Dropdown (e.g., Third Year Secondary) |
| Academic Track/Section | Dropdown (e.g., Science / Literary) |
| Email | Required, used for login |
| Password + Confirmation | Required |
| Profile Photo | Optional upload |

**Design implication:** since each instance already belongs to one instructor, Governorate/Grade/Track are used only for **within-instance filtering** (e.g., an instructor teaching multiple grades can organize/target content by grade and track), not for cross-instructor discovery — that concept no longer applies.

### 2.4 Vendor (the Developer) — Not an in-app role
The vendor is **not a role inside any instructor's instance** — there is no platform-wide admin panel spanning multiple instructors, since each instance is independent. The vendor's relationship to each instance is purely operational:
- Publishes new versions of the product (bug fixes, features, security patches) as versioned Docker images.
- Provides an update mechanism the instructor (or their hired ops person) can run to pull the latest version (see 6.3).
- May be granted **temporary, audited, revocable** access to a specific instance strictly for maintenance/support purposes, only with the instructor's consent — this must never be a standing backdoor (see 4.1 Security).

---

## 3. Functional Scope

### 3.1 User Management & Authentication
- Request-based account creation for students (submit → instructor/TA review → activation), matching the current UX.
- Instant login for existing users (Email + Password), with future OAuth (Google) support.
- Roles within a single instance: Instructor (owner) / Teaching Assistant / Student (via Spatie Laravel-Permission on top of Laravel). No cross-instance "platform admin" role exists inside the product (see 2.4).
- Instructor's public-facing profile (bio, photo, branding) — this **is** the platform's homepage identity, not one listing among many.

### 3.2 Content Management (Course Structure)

Hierarchical content structure:

```
Course
 └── Chapter / Module
      └── Lecture
           ├── Pre-Exam (optional gate — must be completed before video access)
           ├── Video(s) — one lecture can contain multiple videos
           ├── PDF Attachment(s)
           └── Assignment (auto-graded where possible)
```

**Lecture flow:**
1. If a pre-exam is configured, the student must complete it before unlocking the video(s).
2. The student watches one or more videos attached to the lecture.
3. After completing the videos, the student submits the assignment, which is auto-graded for objective question types (multiple choice, true/false) and queued for manual review for essay/file-upload types.

- Instructors can create/edit/delete courses and lectures.
- Videos are uploaded to a video hosting service **configured by the instructor with their own account credentials** (see 3.11), never stored on the application server directly.
- Each lecture can be sold individually or as part of a course/bundle.

### 3.3 Pricing & Access Engine (Commerce & Entitlement) — **Core of the Platform**

This is the most critical part of the platform and the foundation everything else is built on. It must remain fully decoupled from other systems.

**Core Entities:**

| Entity | Description |
|---|---|
| `Product` | Any sellable unit. It maps via a polymorphic relation (`sellable`) to a `Lecture`, `CourseSection` (which acts as a "Month"), or a `Course`. |
| `Bundle` | A group of `Product`s sold together at a special price (e.g., Month 1 + Month 2 offer). |
| `Order` | The actual purchase transaction, processed through the instructor's own payment gateway account. Points to a `purchasable` (Product or Bundle). |
| `Entitlement` | A record defining "Student X has access to Lecture Y until Date Z". **Entitlement is ALWAYS recorded at the Lecture level**, regardless of whether the student bought a single lecture, a month, or a full course. |

**The "Chapter = Month" Paradigm:**
- The structural `CourseSection` table (which groups lectures) acts naturally as a "Month" or "Chapter".
- We do NOT need a separate "Month" table. A `Product` simply points to a `CourseSection` to sell it as a one-month package.

**The "Entitlement" Logic:**
- The "who can access what" logic (Entitlement Check) must be a single unified layer that every content access request (video, PDF, exam, assignment) passes through.
- When an `Order` for a `Product` or `Bundle` is paid, a service (`GrantEntitlementService`) resolves all the nested `Lecture` IDs contained within that purchase.
- It then issues an individual `Entitlement` row for *each* lecture.
- Consequently, checking access anywhere in the platform is always a simple query:
  `Entitlement::where('student_id', $id)->where('lecture_id', $lecture_id)->whereValid()->exists();`
- This ensures the video player, exams, and assignments do not need to know *how* the student bought the content.

### 3.4 Exams System
- Question Bank: multiple choice, true/false, essay.
- Exam configuration: time limit, allowed attempts, optional randomized question order.
- Automatic grading for objective questions; manual grading for essay questions (by the instructor or an authorized TA).
- Pre-lecture "gate" exams supported (see 3.2) in addition to standalone post-lecture exams.
- Grade records are tied to Entitlement (a student must hold access to a lecture to take its exam).

### 3.5 Assignments System
- Instructor uploads the assignment description (text/attached file).
- Student submits their answer (file or text).
- Auto-grading applies where the assignment is structured (e.g., multiple choice-based assignments); free-form submissions are queued for manual review by the instructor or an authorized TA.
- Notification sent to the student once grading is complete.

### 3.6 Q&A System
Two complementary layers:
1. **Per-Lecture Q&A:** Students can post a question directly under a specific lecture, tied to that lecture's content. Answered by the instructor or an authorized Teaching Assistant.
2. **Course-Level FAQ:** A general, course-wide question hub where frequently asked questions are visible to all enrolled students, reducing repeated questions and helping future students self-serve.
- Only students with an active Entitlement to the relevant lecture/course can post questions there.
- Questions can be marked as "answered" and optionally pinned/featured by the instructor as FAQ entries.
- **Image/attachment support:** both the student's question and the instructor's/TA's answer can include one or more image attachments (e.g., a photo of a handwritten math problem, a screenshot of an exercise), in addition to plain text.
  - Accepted types at MVP: images (JPEG/PNG/WEBP), with a reasonable per-file size limit and a max attachment count per question (e.g., 3 images).
  - Attachments are stored in the instance's own object storage account — the database only stores a reference (URL/path + metadata), not the binary content.
  - Attachment access is subject to the same Entitlement check as the rest of the lecture content.
  - Uploaded images are validated (file type/size) server-side and stripped of EXIF/location metadata for student privacy (ties into 4.1).
- **Storage decision:** question/answer text and metadata implemented entirely in PostgreSQL (see 6.4) — no separate document database is needed for this feature.

### 3.7 Teaching Assistants & Permissions
- The instructor invites Teaching Assistants (via email) within their instance.
- Permissions are granted via a **fixed checkbox list** selected by the instructor per assistant:
  - Answer student questions (Q&A)
  - Grade assignments (including essay/manual review)
  - Upload / edit lecture content (video, PDF)
  - Create / edit exams and question banks
  - View sales & performance analytics/reports
  - Manage announcements/notifications to students
  - Approve/activate new student accounts
- Granting all checkboxes to one trusted TA effectively creates an "operations manager" role without a separate role type in the system.
- The instructor can revoke or modify a TA's permissions at any time.
- All TA actions are attributable and auditable (logged against the TA's own account).

### 3.8 Payments
- The instructor connects **their own** payment gateway account (Paymob or Kashier — Vodafone Cash, cards, InstaPay) via API credentials configured in their instance settings.
- The platform code integrates with these gateways generically; it does not hold or process funds on the instructor's behalf.
- Recurring billing management for monthly subscriptions runs against the instructor's own gateway account.
- Configurable refund policy managed by the instructor.

### 3.9 Notifications
- Triggers: successful purchase, subscription expiring soon, assignment graded, exam result available, new lecture published, new answer to a posted question.
- Channels: In-app + Email at launch, with SMS (OTP/critical alerts) and Push Notifications (Mobile) planned for a later phase.

### 3.10 Analytics & Reporting
- Sales, student count, lecture completion rate, average exam scores, most-asked questions — all scoped to the instructor's own instance (no cross-instance reporting exists, since there is no shared platform view).

### 3.11 Instance Configuration (New — Core to the White-Label Model)
Each deployed instance needs a configuration layer the instructor (or the vendor, during setup) fills in once at provisioning time:
- Branding: instructor name, logo, profile photo, brand colors, platform display name.
- Domain: the instructor's custom domain (e.g., `ahmedmath.com`), with automated SSL certificate issuance (Let's Encrypt).
- Payment gateway credentials (Paymob/Kashier API keys).
- Video hosting account credentials (e.g., Bunny Stream API key/library ID).
- Object storage credentials (Cloudflare R2 bucket/keys).
- Email sending credentials (transactional email provider).

This configuration is stored as environment variables / a config file per instance — never hardcoded — so the same codebase serves every instructor without code changes.

---

## 4. Non-Functional Requirements

| Requirement | Detail |
|---|---|
| **Performance** | API response time under 300ms for core requests, per instance |
| **Scalability** | Two dimensions: (1) each instance scales vertically/horizontally on its own (Section 6.1), and (2) the deployment/update process scales across many instances without linear vendor effort (Section 6.2) |
| **Availability** | 99.5% uptime target per instance, dependent on the instructor's chosen hosting quality — documented as a hosting requirement, not solely a code guarantee |
| **Localization** | Full Arabic (RTL) support across all interfaces |

### 4.1 Security & Data Privacy (High Priority)

Given this platform stores sensitive personal data on minors (students' names, phone numbers, parents' phone numbers, and academic records), security and privacy are first-class requirements, not an afterthought.

- **Legal compliance:** the platform must comply with Egypt's Personal Data Protection Law (Law No. 151 of 2020), which governs collection, storage, and processing of personal data, including parental/guardian contact information. Consent language must be shown at registration, and a data retention/deletion policy must be defined and documented. Since instructors are the data controllers of their own instance, the vendor should ship clear documentation helping instructors meet their own compliance obligations.
- **Encryption in transit:** HTTPS/TLS enforced everywhere per instance (automated via Let's Encrypt, see 3.11), HSTS enabled.
- **Encryption at rest:** database-level encryption for sensitive fields (phone numbers, addresses) in addition to standard disk-level encryption provided by the instructor's chosen hosting.
- **Password & credential security:** bcrypt/argon2 hashing (Laravel default), rate-limited login attempts, mandatory strong password rules.
- **Video content protection:** time-limited signed URLs for all video/PDF access; no permanent public links; optional DRM if the instructor's video hosting plan supports it.
- **Authorization enforcement:** every permission check (Entitlement, TA permissions) must be enforced **server-side**, never trusted from the client.
- **PII minimization:** only collect what is functionally necessary; avoid storing sensitive data (e.g., full ID numbers) unless a specific feature requires it.
- **Audit logging:** all administrative and TA actions (grading, content edits, permission changes, refunds) are logged with actor, timestamp, and action for accountability.
- **Vendor maintenance access:** any access the vendor takes to an instance for support/maintenance must be temporary, explicitly granted, and logged — never a standing credential (see 2.4).
- **Rate limiting & abuse prevention:** API rate limiting per user/IP to prevent scraping of course content, brute-force login attempts, and automated account creation abuse.
- **Dependency & vulnerability management:** automated dependency scanning (e.g., GitHub Dependabot) as part of CI/CD, since a large share of this codebase will be AI-agent-generated and must be checked against known-vulnerability patterns.
- **Backups & disaster recovery:** automated, encrypted, off-site daily database backups per instance, with a tested restore procedure — the instructor must be clearly informed this is their responsibility to enable/monitor, with the platform making it as close to one-command-easy as possible.

---

## 5. Software Engineering Principles & Development Methodology

Since this platform will be built with heavy assistance from an AI coding agent, this section exists specifically to give that agent (and any future human collaborator) a consistent set of engineering rules to follow throughout the build — not just a feature list.

### 5.1 Development Lifecycle (SDLC)
The project follows an iterative, phase-based SDLC rather than a single big-bang release:
1. **Requirements & problem definition** — this PRD, kept as the living source of truth and updated whenever scope changes.
2. **Design** — ERD and module/domain boundaries defined *before* code is written for each domain (see 11. Next Steps).
3. **Implementation** — built domain-by-domain (Users → Courses → Commerce → Exams → Q&A → TAs), following the MVP → Phase 2 → Phase 3 order defined in Section 8.
4. **Testing & verification** — see 5.3.
5. **Packaging & release** — every change is shipped as a versioned, deployable artifact (Section 6.2), never a manual/ad-hoc change to a running instance.
6. **Maintenance & iteration** — monitored via error tracking and analytics per instance, feeding back into requirements.

### 5.2 Separation of Concerns (SoC)
This principle is enforced at every layer of the system, and is especially important when an AI agent is generating large portions of the code, since it prevents logic from leaking into the wrong layer:
- **Domain layer** (business rules — e.g., Entitlement logic, pricing calculation, exam grading rules) must not depend on Laravel/HTTP/Eloquent specifics.
- **Application layer** (use cases — e.g., "PurchaseCourse", "GrantEntitlement", "GradeAssignment") orchestrates domain logic and talks to infrastructure through interfaces, not concrete implementations.
- **Infrastructure layer** (Eloquent models, external API clients for Paymob/Bunny Stream/email providers, queue jobs) implements those interfaces — and is the layer that reads per-instance configuration (3.11), so switching an instance's payment gateway or video provider never touches domain/application code.
- **Presentation layer** (Controllers, API Resources/DTOs, Next.js/React Native UI) is a thin layer that only translates HTTP requests into application-layer calls and formats responses.
- Concretely: a Controller should never contain pricing math, entitlement checks, or grading logic directly — it calls a use case that does.

### 5.3 Code Quality & Testing Strategy
- **Automated tests are mandatory for the Commerce/Entitlement domain and the Exams grading logic** — unit tests for domain logic, feature tests for API endpoints.
- Static analysis (PHPStan/Larastan) and a formatter (Laravel Pint) run automatically before code is merged.
- Every AI-agent-generated pull request/change must pass: automated tests, static analysis, and a manual review checklist (5.5) before being merged to the main branch.
- No direct commits to the release branch; all changes go through a review step, since every merge can become part of the next version shipped to every deployed instance.

### 5.4 CI/CD, Packaging & Environments
- Three environments: **Local** (developer machine, via Docker on WSL2 — see 6.2) → **Staging** (the vendor's own reference deployment, used to validate a release before it ships to any instructor) → **Instructor instances** (production, one per instructor).
- CI pipeline (GitHub Actions) runs on every push: install dependencies, run static analysis, run automated tests, run a security/dependency scan, and — on a tagged release — build and publish versioned Docker images.
- Database migrations are version-controlled and reversible; the update process for any instance is: pull new image → run migrations → restart containers.

### 5.5 Code Review Checklist (for AI-agent-generated code)
Before any generated code is accepted, it is checked against:
- [ ] Does business logic live in the domain/application layer, not the controller (5.2)?
- [ ] Is every content-access or grading check enforced server-side (4.1)?
- [ ] Are there automated tests covering the new behavior, especially for Commerce/Entitlement/Exams (5.3)?
- [ ] Does it introduce any new external dependency, and is it read from per-instance configuration rather than hardcoded (3.11)?
- [ ] Does it handle errors and edge cases explicitly (e.g., failed payment, expired entitlement, duplicate submission)?
- [ ] Is any personally identifiable student/parent data logged, cached, or exposed unnecessarily (4.1)?
- [ ] Will this change apply safely to an existing instructor's instance during an update, not just a fresh install (6.2)?

### 5.6 Professional & Ethical Principles
- **Public interest first:** when a technical shortcut conflicts with student data privacy or fair access to purchased content, privacy and fairness win.
- **Honesty in what's promised vs. delivered:** feature scope communicated to instructors/students must match actual system behavior.
- **Quality and integrity of the product:** favor correctness of grading, payments, and access control over speed of shipping new features.
- **Data stewardship:** guardians' and students' personal data is handled as a responsibility, retained only as long as needed and deletable on request.

---

## 6. Technical Architecture

Based on the developer being a Solo Developer/Vendor with a Laravel/React background (building with AI agent assistance), and updated to the latest stable versions as of July 2026:

| Layer | Technology |
|---|---|
| Backend | **Laravel 13** (PHP 8.4) — Modular Monolith + Domain-Driven Design (DDD), introduced incrementally (see 6.6) |
| Instructor/TA Admin Panel | **Filament** (Laravel admin panel package) — auto-generated CRUD UI, session-based auth guard, saves weeks vs. a custom-built dashboard |
| Database | **PostgreSQL 18** (one database per instance — no multi-tenant schema needed) |
| Cache & Queues | Redis + Laravel Horizon |
| Real-time | **Laravel Reverb** (self-hosted WebSocket server) + Redis pub/sub — for instant Q&A answer notifications, live notification badges |
| Authentication | Laravel Sanctum |
| Authorization (RBAC) | Spatie Laravel-Permission (Instructor / TA / Student roles, scoped entirely within one instance) |
| Web Frontend | **Next.js 16** (React 19, TypeScript) |
| Mobile | **React Native (Expo SDK 56)** — see 6.4 for the white-label distribution strategy |
| Desktop (optional, later phase) | Electron |
| Video Hosting | Bunny Stream — **instructor's own account** (see 3.11) |
| File Storage | Cloudflare R2 — **instructor's own account** |
| Search | Meilisearch (optional, added once a single instructor's catalog grows large) |
| Payments | Paymob / Kashier — **instructor's own account** |
| Deployment Packaging | Docker Compose (Laravel Sail-based), developed on **WSL2** for local dev/prod parity |

**Key architectural decision:** No Microservices, and no multi-tenant database. The application follows a **Modular Monolith** structure combined with **Domain-Driven Design (DDD)** principles: the codebase is organized into bounded contexts/domains (Users, Courses, Commerce, Exams, Q&A, Teaching Assistants, Payments), each with clearly defined boundaries. Because each instructor gets a fully separate deployment, there is no tenant-isolation complexity inside the code at all — isolation is achieved for free by deploying separate instances, which is simpler to secure and reason about than a shared multi-tenant database.

**Version note:** Laravel 13 (released March 2026) supports PHP 8.4 (current stable) and ships with a first-party AI SDK — available if AI-assisted features (e.g., auto-generating quiz questions) are considered later.

### 6.1 Per-Instance Scalability Path
- **Vertical scaling first:** a single well-indexed PostgreSQL instance with Redis caching comfortably handles one instructor's full student base (tens of thousands of active users) — the default for MVP and Phase 2.
- **Read replicas** and **horizontal application scaling** remain available per instance if a specific instructor's traffic genuinely requires it, without affecting any other instructor's deployment.
- **CDN-first for static/video assets:** offloading bandwidth-heavy delivery (video, PDFs, images) to the video provider and CDN means the application server only handles API logic — the single biggest scalability lever per instance.

### 6.2 Fleet Scalability — Deployment, Packaging & Updates (New — Core Concern)
This is the real scaling challenge for a solo vendor supporting many independent instances:
- **Single deployable artifact:** the entire product (Laravel app, Reverb, queue workers) is packaged as versioned Docker images, orchestrated via Docker Compose. Local development happens inside Docker on **WSL2**, guaranteeing the exact same environment runs in every instructor's production deployment (dev/prod parity, a core 12-factor app principle).
- **Config-driven, not code-driven, customization:** every per-instructor difference (branding, domain, payment/video credentials) lives in environment variables/config (3.11) — never in a code branch or fork. This is what makes "one codebase, many instances" actually maintainable; the vendor should never end up maintaining divergent copies of the code per instructor.
- **Provisioning a new instance:** a scripted process (ideally a single command or short runbook) that: spins up the Docker Compose stack on the instructor's chosen server, issues an SSL certificate for their domain, runs initial migrations, and prompts for their branding/payment/video configuration.
- **Shipping updates:** the vendor tags a new release → CI builds and publishes new Docker images → each instance is updated by pulling the new image and running any pending migrations. Whether this is triggered by the instructor (self-service update command) or by the vendor (with temporary, audited access) is a process decision to finalize before Phase 2 (see Risks, Section 9) — but the technical mechanism is the same either way.
- **This is why Docker/WSL2 was the right instinct from the start** — it's not just a dev convenience here, it's the backbone of the entire business model.

### 6.3 Why not Microservices
Microservices solve organizational scaling problems (many teams working independently) more than technical scaling problems. As a solo developer/vendor, the operational overhead (service discovery, distributed transactions, multiple deployments per instance, network failure handling) would multiply badly across many instances. The DDD-within-a-monolith approach captures most of the technical benefit (clear boundaries, testability, future extractability) with a single deployable unit per instance — which is exactly what the fleet-scalability model in 6.2 needs.

### 6.4 Why not MongoDB for Q&A/Forum
Still applies unchanged from the previous version — the Q&A data is relational in shape (student, lecture/course, instructor/TA, answer, "answered" status), PostgreSQL's `JSONB` covers any semi-structured needs, and introducing a second database engine per instance would multiply the operational burden **per instance**, which is the opposite of what 6.2 is trying to achieve. **Bottom line:** stay on PostgreSQL.

### 6.5 White-Label Mobile Distribution (New — Flagged Risk)
If every instructor eventually wants their own native app listed under their own name on the App Store and Google Play, that means a **separate developer account, app listing, and review process per instructor** — a significant operational burden at fleet scale. Recommended approach:
- **MVP/Phase 2:** ship a installable **Progressive Web App (PWA)** built from the same Next.js codebase — gives an app-like experience (home screen icon, offline shell, push notifications where supported) with zero app-store overhead per instructor.
- **Phase 3, if genuinely needed:** automate native app generation per instructor using a CI-driven white-label build pipeline (e.g., Expo EAS Build with per-instructor branding injected at build time), rather than manually managing each submission.

### 6.6 Backend Route & Layer Separation (New)
The backend serves two distinct frontends and must keep their concerns from bleeding into each other:
- **API routes (`routes/api.php`):** consumed by the Next.js student app (and later the mobile app), authenticated via Sanctum tokens.
- **Filament panel:** consumed by Instructors and TAs, self-routed by the Filament package under its own path (e.g., `/admin`), authenticated via a separate session-based guard. No custom routes are hand-written for this.
- **`routes/web.php`:** kept minimal — only payment gateway webhooks (Paymob/Kashier server callbacks) and any OAuth redirect endpoints.

**The rule that prevents duplicated logic:** any real business decision (purchasing a course, granting an Entitlement, grading an exam) lives in a single Service class (e.g., `PurchaseCourseService`), called by *both* the API Controller and the Filament Resource/Action. Controllers and Filament classes stay "dumb" — they translate input/output only, never contain business rules themselves.

**Pragmatic MVP folder structure** (used before the full DDD layering in 5.2 is introduced):
```
app/
├── Filament/            ← Instructor/TA resources (auto-generated CRUD)
├── Http/
│   ├── Controllers/Api/ ← API controllers for the student app
│   └── Requests/        ← Validation, shared where useful
├── Services/            ← Real business logic (PurchaseCourseService, EntitlementService)
└── Models/              ← Eloquent models
```
This structure is intentionally simpler than full DDD (5.2) — it is the **fast-build starting point** (see 8.1). As the codebase grows past MVP, `Services/` classes are incrementally promoted into proper Domain/Application layers per module, rather than rewritten from scratch.

---

## 7. Per-Instance Service Requirements & Vendor-Side Infrastructure

This section separates what the **instructor** must provision and pay for (per 1.3) from what the **vendor** needs to maintain the product itself.

### 7.1 What the Instructor Provisions (documented for them at setup)
| Service | Recommended Choice | Why |
|---|---|---|
| Application server | A budget VPS (Hetzner, DigitalOcean, Contabo), sized to their student base | Runs the Docker Compose stack (6.2) |
| Video hosting | **Bunny Stream** | Cheapest at this scale (~$0.01/GB storage, ~$0.005–0.01/GB delivery), encoding/HLS/signed URLs included — see rationale below |
| Object storage | Cloudflare R2 | No egress fees, cheap storage for PDFs/backups/Q&A attachments |
| Payment gateway | Paymob or Kashier | Local Egyptian payment methods (Vodafone Cash, cards, InstaPay); revenue goes directly to the instructor |
| Transactional email | Amazon SES or Resend | Fractions of a cent per email; purchase confirmations, grading notifications |
| CDN / WAF | Cloudflare (free tier) | Basic DDoS protection and caching in front of their domain, at no cost |
| Domain + SSL | Instructor's own domain purchase; SSL automated via Let's Encrypt during provisioning | Matches the "own domain" requirement (1.3) |

**Video hosting rationale (Bunny Stream vs. alternatives):** researched against Mux, Cloudflare Stream, and AWS (S3 + MediaConvert + CloudFront). Bunny Stream includes encoding, adaptive HLS streaming, a player, and signed URLs at no extra charge, with no fixed monthly minimum — an indicative small-instance monthly cost lands in the $5–20/month range, scaling linearly with actual library size and viewership, which is easy for an individual instructor to understand and budget for.

**Indicative monthly cost for one instructor's instance (early stage):** roughly **$20–50/month total**, scaling with their own usage — this should be documented clearly in the product's setup guide so instructors know what to expect before purchasing.

### 7.2 What the Vendor Maintains
- **Source code repository** (private) and **Docker image registry** for versioned releases (6.2).
- **Reference/staging deployment** to validate every release before it's made available to instructors.
- **Error tracking:** GlitchTip (self-hosted, Sentry-compatible, no recurring SaaS cost) — the vendor can optionally offer instructors a way to opt into error reporting from their instance back to the vendor, to speed up support, with explicit consent (ties into 4.1).
- **CI/CD:** GitHub Actions (free tier is sufficient at this stage) for build/test/release automation (5.4).
- **Documentation:** a setup/runbook guide for instructors covering provisioning (6.2), the service list in 7.1, and the update process.
- **Licensing/update distribution mechanism:** to be finalized — whether instructors self-serve updates via a provided script, or the vendor applies updates with temporary, audited access per instance (see Risks, Section 9).

---

## 8. Release Scope — MVP (Phase 1)

Goal: the simplest usable, **deployable** version that proves both the product itself and the packaging/deployment model, without building every scenario at once.

### 8.1 Fast-Track Approach — Walking Skeleton
Given the priority to build quickly, the MVP is built as a **Walking Skeleton**: the thinnest possible end-to-end slice (a student can actually purchase and watch a lecture) before any breadth is added. Concretely, over roughly a 4-week build:
- **Weeks 1–2:** Auth (Laravel Breeze/Sanctum, not a custom-built flow) with only the essential student fields (name, email, phone — the rest of the profile fields in 2.3 are added once the skeleton works, not before); Course + Lecture creation (one video, one PDF) via Filament, uploaded directly to Bunny Stream.
- **Week 3:** Full-course purchase only, via the instructor's payment gateway; a single `Entitlement` table (`student_id, course_id, expires_at`) — no Products/Bundles/Subscriptions abstraction yet (3.3's full model is introduced once this simple case works end-to-end).
- **Week 4:** Video playback via signed URL + PDF viewer. At this point the product is genuinely usable, even if minimal.
- During this phase, the codebase uses the pragmatic `Services/` structure from 6.6, **not** full DDD (5.2) — DDD layering is introduced incrementally as modules mature, not upfront.
- Explicitly deferred out of the Walking Skeleton (even though listed below as "Required in MVP" once the skeleton is done): pre-lecture gate exams, Q&A, TA permissions, Reverb real-time, Docker packaging for external instructors. The rule of thumb for every feature: *"if this doesn't exist, does the product still work end-to-end?"* — if yes, it's deferred.

**Required in MVP (once the Walking Skeleton above is working):**
- The core deployable Docker package: Laravel app + Postgres + Redis, configurable via environment variables (3.11, 6.2) — Reverb added only once a real-time feature is actually built (Phase 2).
- Request-based account creation and login (Student), plus Instructor login, including the full student profile fields.
- Course creation with lectures (video + PDF), hosted on the instructor's own Bunny Stream account.
- Full-course purchase only (no subscriptions or bundles yet), via the instructor's own payment gateway.
- Simple exam system (multiple choice only) — the pre-lecture gate capability is deferred to Phase 2 per 8.1.
- Basic Instructor Dashboard via Filament (their courses, their sales, their branding settings).
- Core security baseline from 4.1 (HTTPS via automated SSL, signed video URLs, rate limiting, encrypted backups).
- One real, working reference deployment (the vendor's own staging instance) proving the provisioning process end-to-end.
- Web only, built as an installable PWA (6.5). Native mobile and Desktop deferred.

**Deferred to Phase 2:**
- Single-lecture purchases.
- Monthly subscriptions and bundled offers.
- Pre-lecture gate exams.
- Assignments (with auto-grading for structured types).
- Per-lecture Q&A and course-level FAQ (including image attachments).
- Teaching Assistants module with full permission system.
- Real-time layer (Laravel Reverb).
- SMS/OTP-based registration verification.
- Formalized update-distribution process for already-deployed instances (self-service script vs. vendor-applied).
- Incremental migration of `Services/` classes toward full DDD layering (5.2, 6.6) for modules that have stabilized.

**Deferred to Phase 3:**
- Native mobile app via automated white-label build pipeline (6.5) — only once demand across multiple instructors justifies the automation investment.
- Desktop app (Electron) — only if a genuine need is validated.
- Advanced analytics and detailed reporting (including TA activity reports).
- Push notifications.
- Optional video DRM if piracy becomes a measurable problem for a given instructor.

---

## 9. Risks & Assumptions

| Risk | Mitigation |
|---|---|
| Building an overly complex Entitlement system from day one | Start with one simple scenario (full course purchase) and extend gradually on the same foundation |
| Video leakage | Signed, time-limited URLs (built into Bunny Stream) + prevention of direct downloads |
| Single developer/vendor workload overload across many instances | Strict prioritization across MVP → Phase 2 → Phase 3; the Docker/config-driven packaging (6.2) is what keeps per-instance maintenance cost low |
| TA permission scope leaking beyond intended access | Enforce all permission checks server-side per request, never rely on frontend-only restrictions |
| Manual account approval becoming a bottleneck at scale | Consider semi-automated verification (e.g., phone OTP) once volume grows, while keeping manual review as a fallback |
| AI-agent-generated code introducing subtle bugs or insecure patterns | Mandatory automated tests + static analysis + review checklist (5.3, 5.5) before merging any agent-produced change |
| Personal data (especially of minors) mishandled or over-retained | Explicit data retention/deletion policy and Egypt Personal Data Protection Law compliance (4.1) |
| **Update distribution mechanism not yet finalized** — self-service by instructor vs. vendor-applied with temporary access | Must be decided before Phase 2; whichever is chosen, all vendor access to a live instance must be temporary and audited (4.1) |
| Instructor mismanaging their own hosting, domain, or payment gateway setup causing support burden | Ship clear setup documentation (7.2) and a guided provisioning script (6.2) to minimize misconfiguration |
| Native app-per-instructor operational overhead at fleet scale | Default to PWA for MVP/Phase 2; only automate native app builds in Phase 3 if genuinely needed (6.5) |
| Divergent, forked code per instructor breaking "one codebase" maintainability | All customization must go through config (3.11), enforced by the code review checklist (5.5) |

---

## 10. Success Metrics

- Number of instructors who purchase and successfully deploy an instance within the first 3 months.
- Average time from purchase to a fully working, branded instance (a direct measure of how well 6.2's provisioning process works).
- Number of registered and paying students per active instance (Conversion Rate).
- Lecture completion rate.
- Monthly Recurring Revenue for the instructor (MRR) after subscriptions launch — tracked per instance.
- Average response time to student questions (Q&A) per instance.
- Zero critical security/privacy incidents (tracked via error monitoring and audit logs, 4.1/7.2).
- Vendor time spent per instance update (should trend toward flat, not linear, as the fleet grows — the key indicator that 6.2 is working).

---

## 11. Next Steps

1. Design the detailed ERD for the Commerce/Entitlement tables (recommended before writing any code).
2. Set up the local environment: Laravel 13 + PHP 8.4 + PostgreSQL 18 + Redis, packaged in **Docker Compose running on WSL2** from day one — this becomes both the dev environment and the template for every future instance.
3. Define the initial domain boundaries (Users, Courses, Commerce, Exams, Q&A, Teaching Assistants) and the folder/module structure for the DDD approach before writing feature code.
4. Set up the CI/CD pipeline (build, test, static analysis, versioned Docker image publishing) before writing feature code, so every subsequent change is tested, reviewed, and packaged consistently.
5. Build the Auth + Users domain first (including the request-based student registration flow) as the foundation for all other domains.
6. Build the Courses domain (without complex pricing yet), integrate Bunny Stream using per-instance config, and connect it to a simple Commerce domain.
7. Build and validate the full **provisioning process end-to-end** on one real reference deployment (the vendor's own staging instance) — proving domain + SSL + branding + payment config actually works before onboarding a first real instructor.
8. Decide and document the update-distribution mechanism (Risk table, Section 9) before taking on a second instructor instance.
9. Test the MVP with a small number of real students on the reference instance before expanding to Phase 2 features (subscriptions, bundles, TAs, FAQ).
