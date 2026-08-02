# Platform Feature Inventory

> **Purpose:** Complete reference of every feature currently built and working. Use this to plan future additions after going to production.

**Status:** Production-ready MVP — 437 tests passing (407 backend + 30 frontend)

---

## Architecture at a Glance

| Layer | Tech | Status |
|-------|------|--------|
| Backend API | Laravel 13 + Sanctum | **Done** |
| Admin Panel | Filament v5 (Instructor/Assistant) | **Done** |
| Student Web | Next.js 16 + React 19 | **Done** |
| Student Mobile | Flutter | **To be built** |
| Database | PostgreSQL 18 | **Done** |
| Cache/Queue | Redis 7 | **Done** |
| Object Storage | MinIO (S3-compatible) | **Done** |
| Video CDN | Bunny Stream | **Done** |
| Email Testing | Mailpit | **Done** |
| Containerization | Docker Compose (8 services) | **Done** |

---

## 1. Authentication & Users

### What's Built
- [x] Multi-step Arabic registration (4-part name, phone, guardian info, geography, grade level)
- [x] Cloudflare Turnstile CAPTCHA on registration
- [x] Pending approval workflow (new accounts blocked until instructor approves)
- [x] Multi-field login (email / phone / student code / student phone)
- [x] Rate-limited login attempts
- [x] Forgot password → email reset token → new password
- [x] Sanctum API token (Bearer auth)
- [x] Last login tracking
- [x] Profile view/edit
- [x] Role-based access control (Spatie) — 4 roles: super_admin, instructor, assistant, student
- [x] Unique code generation: students (ST30042), assistants (TA1234), courses (CR5678)

### Not Built Yet
- [ ] Email verification on registration
- [ ] Social login (Google, Apple)
- [ ] Two-factor authentication (2FA)
- [ ] Account deletion by student
- [ ] Session management (view/revoke active sessions)
- [ ] Push notification token registration

---

## 2. Student Management

### What's Built
- [x] Student CRUD (Filament)
- [x] Approve / reject pending students with notification
- [x] Grant / revoke lecture entitlements
- [x] Toggle `is_verified` (controls purchase eligibility)
- [x] Inline password change
- [x] Profile: 4-part name, phones, guardian info, governorate, grade level, gender, birth date
- [x] Student statistics (watch minutes, completed lectures, exam scores)
- [x] Student activity log (video progress, completions)

### Not Built Yet
- [ ] Student profile image upload
- [ ] Bulk student import (CSV/Excel)
- [ ] Student notes / internal comments (instructor)
- [ ] Student banning / suspension by instructor
- [ ] Attendance tracking
- [ ] Parent/guardian portal
- [ ] Student-to-student messaging

---

## 3. Course Management

### What's Built
- [x] Hierarchical content: Course → Section → Lecture
- [x] Course CRUD (title, description, thumbnail, price, status)
- [x] Course caching (2-hour TTL, auto-invalidation on changes)
- [x] Course code generation (CR{4-digit})
- [x] Section CRUD with sort ordering
- [x] Lecture CRUD with sort ordering
- [x] Lecture video upload (MP4 → MinIO, max 1GB)
- [x] YouTube URL support (alternative to uploaded video)
- [x] PDF file attachments per lecture
- [x] Course assistant assignment (pivot relationship)
- [x] Status management: draft → published → archived
- [x] Activity logging (title, status, price changes)

### Not Built Yet
- [ ] Lecture notes / chapter markers
- [ ] Lecture prerequisites (beyond blocking exams)
- [ ] Course prerequisites
- [ ] Course cloning / duplication
- [ ] Course preview (free preview lectures)
- [ ] Course ratings / reviews
- [ ] Course categories / tags
- [ ] Course duration estimation (auto-calculated)
- [ ] Drip content (scheduled release)
- [ ] Live streaming / webinar support
- [ ] Discussion forums (separate from Q&A)

---

## 4. Video Streaming & Security

### What's Built
- [x] HLS encrypted video (AES-128) with MinIO storage
- [x] Bunny Stream CDN integration
- [x] YouTube embed support
- [x] Custom Netflix-style video player (play/pause, seek, volume, quality switching, fullscreen)
- [x] Auto-resume from last position
- [x] Auto-hide controls after 3 seconds
- [x] Progress tracking (heartbeat every 20 seconds)
- [x] Completion detection (≥ 90% watched)
- [x] HMAC token security for streaming (4-hour expiry)
- [x] Encrypted token for decryption key (5-minute expiry, IP-bound)
- [x] Rate limiting: playlist (120/min), segments (600/min)
- [x] Dynamic watermark (student name + email, rotating position)
- [x] DevTools detection (pauses video)
- [x] Keyboard shortcut blocking (F12, Ctrl+Shift+I/J/C/K, Ctrl+U)
- [x] Right-click blocking on video
- [x] Drag blocking on video
- [x] Picture-in-Picture disabled
- [x] Background job: ProcessVideoHLS (MP4 → HLS transcode → Bunny upload)
- [x] Video processing status tracking (pending → processing → completed/failed)

### Not Built Yet
- [ ] Adaptive bitrate streaming (ABR) with multiple quality levels
- [ ] Offline video download (with DRM)
- [ ] Video chapters / segments
- [ ] Speed control (0.5x, 0.75x, 1.25x, 1.5x, 2x)
- [ ] Subtitles / closed captions
- [ ] Video notes (timestamped)
- [ ] Picture-in-Picture support (mobile)
- [ ] Screen recording detection (mobile)
- [ ] Video analytics (watch time heatmap, drop-off points)
- [ ] Content delivery via multiple CDN providers

---

## 5. Exams & Quizzes

### What's Built
- [x] Question types: multiple choice, true/false, essay
- [x] Exam CRUD with nested questions/choices (Filament)
- [x] Assignments (same model, separate flag)
- [x] Exam configuration: title, duration, pass percentage, blocking toggle
- [x] Image support per question
- [x] Quiz UI: one question at a time, quick-jump map, countdown timer
- [x] Auto-submit on timer expiry
- [x] Confirmation dialog for incomplete submissions
- [x] Instant result display after submission
- [x] Auto-grading (MC + TF)
- [x] Essay grading placeholder (0 points until manual review)
- [x] Blocking exam gating (sequential: section → lecture → exam order)
- [x] Mandatory exam-first redirect (blocking exam shown before video)
- [x] Attempt history across all courses
- [x] Resume existing unsubmitted attempts

### Not Built Yet
- [ ] Question bank / pooling (random questions from a bank)
- [ ] Question shuffling (randomize question order)
- [ ] Choice shuffling (randomize answer options)
- [ ] Exam time extension
- [ ] Exam retake policy (max attempts)
- [ ] Exam results comparison (before/after)
- [ ] Essay grading interface (instructor side)
- [ ] Bulk exam import
- [ ] Proctoring / anti-cheating (webcam, tab detection)
- [ ] Certificates of completion
- [ ] Practice mode (no grade recorded)

---

## 6. Commerce & Payments

### What's Built
- [x] Polymorphic products: Course / Section / Lecture
- [x] Product catalog with pricing (EGP)
- [x] Bundles (multiple products at discount)
- [x] Order creation (manual payment)
- [x] Order confirmation by instructor (Filament)
- [x] Entitlement auto-grant on confirmation
- [x] Time-limited or permanent access
- [x] Idempotent entitlement creation
- [x] Order status tracking: pending → completed / failed / refunded

### Not Built Yet
- [ ] Integrated payment gateway (Paymob, Stripe, Fawry)
- [ ] Subscription / recurring payments
- [ ] Coupon / discount codes
- [ ] Refund processing
- [ ] Invoice / receipt generation (PDF)
- [ ] Sales analytics / reports
- [ ] Revenue dashboard
- [ ] Tax calculation
- [ ] Multi-currency support
- [ ] Affiliate / referral system
- [ ] Waitlist for out-of-stock courses

---

## 7. Enrollment & Progress

### What's Built
- [x] Free course enrollment (direct)
- [x] Paid course enrollment (via purchase)
- [x] Enrollment statuses: active, expired, suspended
- [x] Enrollment sources: manual, purchase
- [x] Instructor can revoke enrollments
- [x] Synthetic entitlement-only enrollment view
- [x] Video progress tracking (heartbeat)
- [x] Lecture completion marking (≥ 90%)
- [x] Student statistics (watch minutes, completed lectures, exam scores)
- [x] Dynamic completed courses calculation

### Not Built Yet
- [ ] Course completion certificates
- [ ] Learning paths (sequential course order)
- [ ] Prerequisite courses
- [ ] Streak tracking (consecutive days)
- [ ] Gamification (badges, points, leaderboard)
- [ ] Course bookmarks / favorites
- [ ] Watch history / recently viewed
- [ ] Estimated time to complete
- [ ] Progress export

---

## 8. Q&A (Questions & Answers)

### What's Built
- [x] Post questions per lecture (enrollment-gated)
- [x] Reply to any question
- [x] Delete own questions / replies
- [x] Public Q&A board (all students see all questions)
- [x] My Questions page (all questions across courses)
- [x] 15-second auto-polling
- [x] Toast notifications for new replies
- [x] Reply count tracking
- [x] Instructor / assistant reply from Filament
- [x] Notification on new question (to instructor + assistants)
- [x] Notification on new reply (to question author)

### Not Built Yet
- [ ] Upvote / helpful answers
- [ ] Best answer marking
- [ ] Question categories / tags
- [ ] Search within Q&A
- [ ] Image attachments in questions / replies
- [ ] Anonymous questions
- [ ] Q&A moderation (hide / flag)
- [ ] Email notifications (not just in-app)

---

## 9. Dashboard & Analytics

### What's Built
- [x] Student dashboard: stats overview, enrolled courses, completed lectures, watch time, exam scores
- [x] Instructor dashboard: courses, students, revenue, lectures, exams, Q&A counts
- [x] Instructor recent enrollments
- [x] Instructor course performance (top 5 by enrollment)
- [x] Filament widgets: 10 stat cards, recent enrollments, course performance
- [x] Activity audit log (Spatie ActivityLog)

### Not Built Yet
- [ ] Charts / graphs (enrollment trends, revenue over time)
- [ ] Student engagement analytics
- [ ] Video analytics (watch time, completion rate, drop-off)
- [ ] Export reports (CSV, PDF)
- [ ] Custom date range filtering
- [ ] Cohort analysis
- [ ] Funnel analysis (registration → enrollment → completion)

---

## 10. Notifications

### What's Built
- [x] In-app notification system (custom model)
- [x] Triggers: new student registration, approval, rejection, new Q&A question, new Q&A reply
- [x] Notification list with read/unread status
- [x] Instructor notifications endpoint
- [x] Notification polling in instructor dashboard

### Not Built Yet
- [ ] Push notifications (FCM / APNs)
- [ ] Email notifications
- [ ] SMS notifications
- [ ] Notification preferences (opt-in/out per type)
- [ ] Notification grouping
- [ ] Notification actions (deep links)
- [ ] Bulk notification to all students
- [ ] Scheduled notifications

---

## 11. Filament Admin Panel

### What's Built
- [x] 13 resources with full CRUD
- [x] 3 dashboard widgets
- [x] Custom login page (Arabic)
- [x] Settings page (writes to .env)
- [x] Role-based access: assistants limited, instructors scoped, super_admin full
- [x] Activity log viewer (read-only)
- [x] Real-time Q&A polling (15s)

### Not Built Yet
- [ ] Custom Filament theme / branding
- [ ] Export functionality on tables
- [ ] Bulk actions (mass approve, mass enroll)
- [ ] Custom dashboard widgets (charts)
- [ ] Multi-language support in admin
- [ ] Audit trail comparison (before/after)
- [ ] Webhook configuration

---

## 12. Security & Infrastructure

### What's Built
- [x] Docker Compose (8 containers: app, nginx, postgres, redis, queue, scheduler, mailpit, minio)
- [x] Production Dockerfile (PHP 8.4 FPM Alpine + FFmpeg)
- [x] Security headers middleware (HSTS, CSP, X-Frame-Options, etc.)
- [x] Rate limiting on auth endpoints
- [x] Rate limiting on video streaming
- [x] Bcrypt password hashing
- [x] UUID primary keys everywhere
- [x] Database indexes for performance
- [x] Queue system (Redis + Horizon)
- [x] Background jobs (video processing)
- [x] Scheduler (cron tasks)

### Not Built Yet
- [ ] HTTPS / SSL termination (let's encrypt)
- [ ] WAF configuration
- [ ] DDoS protection
- [ ] Log aggregation (ELK, Sentry)
- [ ] Monitoring / alerting (Uptime Kuma, Grafana)
- [ ] CI/CD pipeline (GitHub Actions)
- [ ] Staging environment
- [ ] Database backups (automated)
- [ ] Health check endpoints
- [ ] API versioning

---

## 13. Frontend (Web Student App)

### What's Built
- [x] 26 pages across 4 route groups
- [x] Full Arabic RTL support
- [x] Light/dark mode
- [x] Responsive design (320px → 1920px+)
- [x] 43 components (21 shadcn/ui + 22 custom)
- [x] 8 React Query hooks
- [x] 8 API service modules
- [x] Auth guards (loading / auth / guest variants)
- [x] Error states with retry
- [x] Loading skeletons
- [x] Empty states
- [x] Debounced search
- [x] Pagination everywhere

### Not Built Yet
- [ ] PWA support (service worker, offline)
- [ ] i18n framework (next-intl)
- [ ] SEO optimization (meta tags, sitemap)
- [ ] Analytics integration (Google Analytics, Mixpanel)
- [ ] A/B testing framework
- [ ] Cookie consent banner
- [ ] Accessibility audit (WCAG 2.1 AA full compliance)

---

## 14. Testing

### What's Built
- [x] 407 backend tests (Pest PHP)
- [x] 30 frontend tests (Vitest + React Testing Library)
- [x] Auth tests (28)
- [x] Course CRUD tests (43)
- [x] Entitlement & order tests (39)
- [x] Video security tests (57)
- [x] Exam & assignment tests (41)
- [x] Enrollment & dashboard tests (62)
- [x] Filament & RBAC tests (31)
- [x] Middleware tests (23)
- [x] Q&A tests (27)
- [x] Notification tests (9)
- [x] Edge case tests (13)
- [x] Background job tests (5)

### Not Built Yet
- [ ] Integration tests (full API flows)
- [ ] E2E tests (Playwright / Cypress)
- [ ] Load / performance testing (k6, Artillery)
- [ ] Security penetration testing
- [ ] Mobile app tests (Flutter)

---

## 15. Database

### What's Built
- [x] 32 migration files
- [x] ~35 tables
- [x] UUID primary keys
- [x] Foreign key constraints with cascading deletes
- [x] Polymorphic relationships (products, orders, activities)
- [x] Pivot tables (enrollments, course_assistants, bundle_products, permissions)
- [x] Indexes for performance
- [x] Strict column types

### Not Built Yet
- [ ] Database seeding with realistic data (done separately)
- [ ] Database backups
- [ ] Read replicas
- [ ] Database partitioning

---

## Summary Statistics

| Category | Count |
|----------|-------|
| API Endpoints | 62 |
| Filament Resources | 13 |
| Filament Widgets | 3 |
| Backend Models | 31 |
| Backend Services | 13 |
| Backend Controllers | 11 |
| Backend Middleware | 4 |
| Backend Policies | 5 |
| Backend Form Requests | 8 |
| Backend Jobs | 1 |
| Frontend Pages | 26 |
| Frontend Components | 43 |
| Frontend Hooks | 10 |
| Frontend Services | 8 |
| TypeScript Types | 8 |
| Enums | 5 |
| Database Tables | ~35 |
| Backend Tests | 407 |
| Frontend Tests | 30 |
| **Total Tests** | **437** |

---

## Roadmap Suggestion

### Phase 1: Go to Production (Now)
- Deploy current MVP
- Flutter mobile app for students
- Real payment gateway integration

### Phase 2: Post-Production (After Stability)
- Push notifications (FCM)
- Email notifications
- Course categories / tags
- Course ratings / reviews
- Certificates of completion
- Offline video download
- Speed control on video player

### Phase 3: Growth Features
- Subscriptions / recurring payments
- Coupon system
- Learning paths
- Gamification (badges, leaderboard)
- Analytics dashboard with charts
- Parent/guardian portal
- Live streaming support

### Phase 4: Scale
- Multi-language support
- Multi-currency
- API versioning
- CI/CD pipeline
- Monitoring & alerting
- Load testing & optimization

---

*Last updated: July 2026*
