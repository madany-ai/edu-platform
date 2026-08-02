# New Features — Detailed Task Breakdown

> **Purpose:** Deep analysis of 4 major features to add post-MVP. Each feature is broken into small, implementable tasks with database changes, API endpoints, and frontend work.

---

## Feature 1: Payment Gateway Integration

### Current State
- Orders are created as `pending` → instructor confirms manually via Filament
- No automated payment processing
- Manual methods only (Vodafone Cash, bank transfer, InstaPay)

### Target State
- Students can pay directly in the app
- Multiple payment methods supported
- Automatic order confirmation on successful payment
- Refund support

---

### Task 1.1: Payment Gateway Abstraction Layer

**Database:**
- [ ] Create `payment_gateways` table:
  ```
  id, name (paymob/fawry/stripe), driver, config (json), is_active, created_at, updated_at
  ```
- [ ] Create migration

**Backend:**
- [ ] Create `PaymentGateway` model
- [ ] Create `PaymentGatewayService` interface (abstract contract):
  ```php
  interface PaymentGatewayInterface {
      public function initiatePayment(Order $order): PaymentResponse;
      public function verifyPayment(string $transactionId): PaymentResult;
      public function refund(Order $order, int $amountCents): RefundResult;
      public function handleWebhook(array $payload): WebhookResult;
  }
  ```
- [ ] Create `PaymentService` (orchestrator):
  ```php
  class PaymentService {
      public function initiate(Order $order, string $gateway): PaymentResponse;
      public function handleSuccess(string $transactionId): void;
      public function handleFailure(string $transactionId, string $reason): void;
      public function processRefund(Order $order, ?int $amount): void;
  }
  ```
- [ ] Create Filament `PaymentGatewayResource` (CRUD for gateway config)
- [ ] Add payment gateway settings to Filament Settings page

---

### Task 1.2: Paymob Integration (Primary - Egypt)

**Backend:**
- [ ] Create `PaymobGateway` implements `PaymentGatewayInterface`:
  - `initiatePayment()`: Call Paymob API to get payment token
  - `verifyPayment()`: Verify transaction via Paymob API
  - `handleWebhook()`: Process Paymob callback
- [ ] Create `PaymobController` (webhook endpoint):
  ```
  POST /webhooks/paymob
  ```
- [ ] Create `PaymobWebhookSignature` verification
- [ ] Add Paymob config to `.env`:
  ```
  PAYMOB_API_KEY, PAYMOB_INTEGRATION_ID, PAYMOB_HMAC_SECRET
  ```
- [ ] Handle Paymob payment methods: credit card, mobile wallet, bank installments

---

### Task 1.3: Fawry Integration (Secondary - Egypt)

**Backend:**
- [ ] Create `FawryGateway` implements `PaymentGatewayInterface`:
  - `initiatePayment()`: Generate Fawry payment request
  - `verifyPayment()`: Check Fawry transaction status
- [ ] Create `FawryController` (callback endpoint):
  ```
  POST /webhooks/fawry
  ```

---

### Task 1.4: Order Flow Update

**Database:**
- [ ] Add columns to `orders` table:
  ```
  gateway_response (json, nullable), refund_amount_cents (int, nullable), refunded_at (timestamp, nullable)
  ```

**Backend:**
- [ ] Update `OrderController@store` to accept `payment_method` (gateway name)
- [ ] Update `PaymentService` to:
  - Set order status to `processing` when payment initiated
  - Set to `completed` on success callback
  - Set to `failed` on failure callback
  - Auto-grant entitlements on success (call `GrantEntitlementService`)
- [ ] Add refund endpoint:
  ```
  POST /orders/{order}/refund
  ```
- [ ] Update Filament `OrderResource`:
  - Show gateway response data
  - Show refund status
  - Remove manual confirm button when gateway is used

---

### Task 1.5: Frontend Payment Flow

**Frontend Pages:**
- [ ] Update `/products/[id]` page: add "Pay Now" button
- [ ] Update `/bundles/[id]` page: add "Pay Now" button
- [ ] Create `/checkout` page:
  - Order summary (product name, price, payment methods)
  - Payment method selector (credit card / mobile wallet)
  - Redirect to payment gateway
- [ ] Create `/payment/success` page (callback handler)
- [ ] Create `/payment/failed` page
- [ ] Create `/payment/pending` page (waiting for confirmation)

**Frontend Services:**
- [ ] Add `payment.service.ts`:
  ```typescript
  initiatePayment(orderId, gateway): Promise<PaymentResponse>
  checkPaymentStatus(orderId): Promise<PaymentStatus>
  ```

---

### Task 1.6: Hybrid Payment Mode

**Design Decision:** Support both manual AND gateway payments simultaneously

**Backend:**
- [ ] Add `is_manual` boolean to `orders` table (default: true)
- [ ] When `is_manual = true`: current flow (pending → instructor confirms)
- [ ] When `is_manual = false`: gateway flow (processing → webhook → auto-confirm)
- [ ] Filament `OrderResource` shows payment method type
- [ ] Instructors can still manually confirm orders even with gateway active

---

## Feature 2: Complete Student Tracking & Monitoring System

### Current State
- `student_activities` table tracks: `video_progress`, `video_completed` only
- `student_statistics` table has: `total_watch_minutes`, `completed_lectures` (populated), `attendance_rate`, `average_exam_score` (always 0)
- No per-course progress tracking
- No exam activity tracking
- No login/session tracking
- Dashboard shows basic stats only

### Target State
- Instructor sees per-student, per-course, per-lecture activity timeline
- Real-time progress percentages
- Exam performance analytics
- Activity heatmaps
- Exportable reports

---

### Task 2.1: Enhanced Activity Logging

**Database:**
- [ ] Create `student_activity_logs` table (append-only, high-volume):
  ```
  id, student_id, course_id, lecture_id, activity_type (enum), metadata (json), ip_address, user_agent, created_at
  ```
  Activity types: `login`, `logout`, `course_view`, `lecture_view`, `video_start`, `video_progress`, `video_complete`, `exam_start`, `exam_submit`, `question_post`, `question_reply`, `file_download`, `page_view`

**Backend:**
- [ ] Create `ActivityLogger` service:
  ```php
  class ActivityLogger {
      public function log(Student $student, string $type, ?Course $course, ?Lecture $lecture, array $metadata = []): void;
      public function getStudentTimeline(Student $student, ?Course $course, int $limit = 100): Collection;
      public function getCourseActivity(Course $course, Carbon $from, Carbon $to): Collection;
  }
  ```
- [ ] Update `ProgressService` to use `ActivityLogger`
- [ ] Add login/logout logging in `AuthService`
- [ ] Add exam activity logging in `ExamService`
- [ ] Add Q&A activity logging in `QAService`
- [ ] Add page view logging middleware

---

### Task 2.2: Per-Course Progress Tracking

**Database:**
- [ ] Create `student_course_progress` table:
  ```
  id, student_id, course_id, total_lectures (int), completed_lectures (int), progress_percentage (decimal 5,2),
  total_watch_minutes (int), last_lecture_id (uuid, nullable), last_position_seconds (int, default 0),
  started_at (timestamp), last_activity_at (timestamp), created_at, updated_at
  ```
  UNIQUE constraint on `(student_id, course_id)`

**Backend:**
- [ ] Create `StudentCourseProgress` model
- [ ] Create `ProgressTracker` service:
  ```php
  class ProgressTracker {
      public function updateProgress(Student $student, Lecture $lecture, int $currentPosition, bool $completed): void;
      public function getStudentCourseProgress(Student $student, Course $course): StudentCourseProgress;
      public function getCourseProgressSummary(Course $course): Collection; // all students' progress
      public function getTopPerformers(Course $course, int $limit = 10): Collection;
      public function getStrugglingStudents(Course $course, float $threshold = 30): Collection;
  }
  ```
- [ ] Auto-calculate `total_lectures` from course structure on enrollment
- [ ] Update on every video progress heartbeat
- [ ] Update `last_lecture_id` and `last_position_seconds` for resume

---

### Task 2.3: Exam Performance Analytics

**Database:**
- [ ] Create `student_exam_analytics` table:
  ```
  id, student_id, course_id, exam_id, attempt_id, score (decimal 5,2), time_taken_seconds (int),
  questions_total (int), questions_correct (int), questions_wrong (int), questions_skipped (int),
  created_at
  ```

**Backend:**
- [ ] Create `StudentExamAnalytics` model
- [ ] Update `ExamService::submitAttempt()` to populate analytics
- [ ] Create `ExamAnalyticsService`:
  ```php
  class ExamAnalyticsService {
      public function getStudentExamSummary(Student $student, Course $course): array;
      public function getCourseExamSummary(Course $course): array;
      public function getQuestionAnalysis(Exam $exam): array; // which questions most missed
      public function getScoreTrend(Student $student, Course $course): array; // over time
  }
  ```
- [ ] Update `DashboardService` to include exam analytics

---

### Task 2.4: Instructor Student Detail Page

**Backend:**
- [ ] Create `StudentDetailController`:
  ```
  GET /instructor/students/{student}           → full student profile
  GET /instructor/students/{student}/courses    → per-course progress
  GET /instructor/students/{student}/timeline   → activity timeline
  GET /instructor/students/{student}/exams      → exam performance
  GET /instructor/students/{student}/report     → downloadable report (PDF/CSV)
  ```

**Frontend (Instructor Web):**
- [ ] Create `/instructor/students/[id]` page:
  - Profile card (name, phone, code, status)
  - Course progress cards (progress bars, last active)
  - Exam performance chart (score trend)
  - Activity timeline (recent activities)
  - Quick actions (grant access, revoke, message)

---

### Task 2.5: Course-Level Analytics Dashboard

**Backend:**
- [ ] Create `CourseAnalyticsController`:
  ```
  GET /instructor/courses/{course}/analytics
  ```
  Returns:
  - Total enrolled students
  - Average progress across all students
  - Completion rate (% of students who finished)
  - Average exam score
  - Most/least viewed lectures
  - Student engagement heatmap (day/hour)
  - Drop-off points (where students stop watching)
  - Revenue per course

**Frontend:**
- [ ] Create `/instructor/courses/[id]/analytics` page:
  - Summary stats cards
  - Student progress distribution chart
  - Lecture engagement heatmap
  - Exam score distribution
  - Time-series enrollment chart

---

### Task 2.6: Student Export Reports

**Backend:**
- [ ] Create `ReportService`:
  ```php
  class ReportService {
      public function generateStudentReport(Student $student, Course $course): PdfReport;
      public function generateCourseReport(Course $course): CsvReport;
      public function generateAllStudentsReport(): CsvReport;
  }
  ```
- [ ] Add PDF generation (use `barryvdh/laravel-dompdf` or `snappy`)
- [ ] Add CSV export for bulk data
- [ ] Create export endpoints:
  ```
  GET /instructor/students/{student}/export?format=pdf
  GET /instructor/courses/{course}/export?format=csv
  ```

---

### Task 2.7: Real-Time Activity Feed (Optional - Advanced)

**Backend:**
- [ ] Add Laravel Broadcasting (WebSocket) for live activity:
  ```php
  broadcast(new StudentActivityEvent($student, $activity))->toOthers();
  ```
- [ ] Instructor sees live feed: "محمد اتفرج على محاضرة X", "فاطمة جبت 85% في امتحان Y"
- [ ] Use Laravel Reverb (WebSocket server) + Pusher

**Frontend:**
- [ ] Add WebSocket listener for real-time updates
- [ ] Live activity feed component on instructor dashboard

---

## Feature 3: Parent/Guardian Portal

### Current State
- Parent data stored (father_phone, mother_phone, guardian_job) but never used
- No parent accounts
- No parent-facing interface

### Target State
- Parents can log in and see their child's progress
- Parents receive notifications about their child
- Parents cannot access course content (view-only)

---

### Task 3.1: Parent Account System

**Database:**
- [ ] Create `parents` table:
  ```
  id (uuid), user_id (FK → users), relationship (enum: father, mother, guardian),
  phone, occupation, created_at, updated_at
  ```
- [ ] Create `parent_student` pivot table:
  ```
  parent_id (uuid FK → parents), student_id (uuid FK → students),
  is_primary (boolean, default false), created_at, updated_at
  ```
  UNIQUE constraint on `(parent_id, student_id)`

**Backend:**
- [ ] Create `Parent` model with relationships
- [ ] Create `ParentService`:
  ```php
  class ParentService {
      public function register(array $data, Student $student): Parent;
      public function linkToStudent(Parent $parent, Student $student): void;
      public function getStudents(Parent $parent): Collection;
  }
  ```
- [ ] Add `parent` role via Spatie
- [ ] Create parent auth endpoints:
  ```
  POST /parent/auth/register    → register parent account
  POST /parent/auth/login       → login (email/phone + password)
  POST /parent/auth/logout      → logout
  GET  /parent/auth/me          → parent profile
  ```

---

### Task 3.2: Parent Registration Flow

**Frontend:**
- [ ] Create `/parent/register` page:
  - Parent name, email, phone, password
  - Relationship selector (father/mother/guardian)
  - Student code input (ST30042) — to link to existing student
  - Verification: parent phone must match student's father_phone or mother_phone
- [ ] Create `/parent/login` page

**Backend:**
- [ ] Validation: student code must exist, parent phone must match student's guardian phone
- [ ] Send notification to student: "Your father has registered on the platform"
- [ ] Instructor approval required (optional, configurable)

---

### Task 3.3: Parent Dashboard — Student Progress View

**Backend:**
- [ ] Create `ParentDashboardController`:
  ```
  GET /parent/dashboard                   → overview of all linked children
  GET /parent/dashboard/{student}         → detailed view of one child
  GET /parent/dashboard/{student}/courses → child's course progress
  GET /parent/dashboard/{student}/exams   → child's exam results
  GET /parent/dashboard/{student}/activity → child's recent activity
  ```

**Frontend:**
- [ ] Create `/parent/dashboard` page:
  - Children cards (name, grade, overall progress)
  - Click into child → detailed view
- [ ] Create `/parent/dashboard/[studentId]` page:
  - Course progress cards (with progress bars)
  - Exam results table (scores, dates)
  - Activity timeline (recent login, watched lectures, etc.)
  - Statistics: watch time, completed lectures, average score
  - Last active timestamp

---

### Task 3.4: Parent Notifications

**Backend:**
- [ ] Create `ParentNotificationService`:
  ```php
  class ParentNotificationService {
      public function notifyExamResult(Parent $parent, Student $student, ExamAttempt $attempt): void;
      public function notifyCourseCompletion(Parent $parent, Student $student, Course $course): void;
      public function notifyInactivity(Parent $parent, Student $student, int $daysInactive): void;
      public function notifyWeeklyReport(Parent $parent, Student $student, array $stats): void;
  }
  ```
- [ ] Create parent notification table (or reuse notifications with parent_id)
- [ ] Add scheduled job: weekly summary email/notification to parents
- [ ] Add inactivity alert: if student hasn't logged in for 7 days, notify parent

**Frontend:**
- [ ] Add notifications page in parent portal
- [ ] Add notification badge in parent navbar

---

### Task 3.5: Parent Restrictions

**Backend:**
- [ ] Middleware `CheckParentRole`: parent can only access `/parent/*` routes
- [ ] Parent cannot access course content (no video, no exams)
- [ ] Parent can only view their linked children's data
- [ ] Parent cannot modify student data (read-only)

---

## Feature 4: QR Code Attendance System

### Current State
- No attendance tracking at all
- No QR code system
- No Google Sheets integration

### Target State
- Instructor generates QR code per session/class
- Students scan QR with mobile app to mark attendance
- Attendance synced to database AND Google Sheets
- Real-time attendance dashboard for instructor
- Automated reports

---

### Task 4.1: Attendance Database Schema

**Database:**
- [ ] Create `attendance_sessions` table:
  ```
  id (uuid), course_id (FK → courses), instructor_id (FK → users),
  title (string, nullable — e.g. "Session 1: Chapter 3"),
  qr_code (string, unique — UUID or hash), qr_expires_at (timestamp),
  location (string, nullable), notes (text, nullable),
  status (enum: active, closed), created_at, closed_at, updated_at
  ```
- [ ] Create `attendance_records` table:
  ```
  id (uuid), session_id (FK → attendance_sessions, cascade delete),
  student_id (FK → students), status (enum: present, late, absent, excused),
  scanned_at (timestamp), ip_address (string, nullable),
  latitude (decimal, nullable), longitude (decimal, nullable),
  notes (text, nullable), created_at, updated_at
  ```
  UNIQUE constraint on `(session_id, student_id)`
- [ ] Create `google_sheets_config` table:
  ```
  id (uuid), instructor_id (FK → users), spreadsheet_id (string),
  sheet_name (string), course_id (FK → courses, nullable),
  last_synced_at (timestamp, nullable), is_active (boolean, default true),
  created_at, updated_at
  ```

---

### Task 4.2: QR Code Generation & Validation

**Backend:**
- [ ] Create `AttendanceService`:
  ```php
  class AttendanceService {
      public function createSession(Course $course, User $instructor, array $data): AttendanceSession;
      public function generateQR(AttendanceSession $session): QrCodeImage;
      public function validateQR(string $qrCode, Student $student): ValidationResult;
      public function markAttendance(AttendanceSession $session, Student $student, array $location): AttendanceRecord;
      public function closeSession(AttendanceSession $session): void;
      public function getSessionStats(AttendanceSession $session): SessionStats;
  }
  ```
- [ ] QR code content: encrypted JSON `{ session_id, instructor_id, expires_at, signature }`
- [ ] QR code validity: configurable (default 5 minutes, refreshes automatically)
- [ ] Use `simplesoftwareio/simple-qrcode` or `bacon/bacon-qr-code` package
- [ ] Anti-cheat measures:
  - QR changes every X minutes (configurable)
  - Location validation (optional, within X meters)
  - Device fingerprinting (prevent multiple scans from same device)
  - Time window validation

---

### Task 4.3: Instructor Attendance Management

**Backend:**
- [ ] Create `AttendanceController` (instructor):
  ```
  POST   /instructor/attendance/sessions              → create session + generate QR
  GET    /instructor/attendance/sessions/{session}/qr  → get/refresh QR image (SVG/PNG)
  POST   /instructor/attendance/sessions/{session}/close → close session
  GET    /instructor/attendance/sessions/{session}     → session details + attendance list
  GET    /instructor/attendance/courses/{course}/sessions → all sessions for a course
  POST   /instructor/attendance/sessions/{session}/students/{student}/override → manual override
  GET    /instructor/attendance/courses/{course}/report → course attendance report
  ```
- [ ] Filament `AttendanceResource`:
  - List sessions per course
  - View session attendance (who attended, who didn't)
  - Manual override (mark student present/absent)
  - Export attendance to CSV

**Frontend (Instructor Web):**
- [ ] Create `/instructor/attendance` page:
  - Course selector
  - "Start Session" button → generates QR code
  - QR code display (large, scannable)
  - Auto-refresh QR every configurable interval
  - Live attendance list (students who scanned)
  - "Close Session" button
  - Attendance summary (X present, Y absent, Z late)

---

### Task 4.4: Student QR Scan (Mobile App)

**Backend:**
- [ ] Create `StudentAttendanceController`:
  ```
  POST /attendance/scan               → scan QR code (send qr_code string)
  GET  /attendance/my-history         → student's attendance history
  GET  /attendance/my-stats           → attendance percentage per course
  ```
- [ ] Validation flow:
  1. Receive QR code string from mobile
  2. Decrypt and validate signature
  3. Check QR hasn't expired
  4. Check session is active
  5. Check student is enrolled in course
  6. Check student hasn't already scanned this session
  7. Record attendance
  8. Return success with timestamp

**Flutter App:**
- [ ] Add QR scanner screen using `mobile_scanner` package
- [ ] "Scan Attendance" button on course page
- [ ] Camera permission handling
- [ ] Success animation after scan
- [ ] Attendance history page
- [ ] Attendance stats per course

---

### Task 4.5: Google Sheets Integration

**Backend:**
- [ ] Create `GoogleSheetsService`:
  ```php
  class GoogleSheetsService {
      public function __construct();
      public function connect(string $spreadsheetId): void;
      public function createSheet(string $spreadsheetId, string $sheetName): void;
      public function appendRow(string $spreadsheetId, string $sheetName, array $data): void;
      public function updateRow(string $spreadsheetId, string $sheetName, int $row, array $data): void;
      public function syncAttendance(AttendanceSession $session): void;
      public function syncAllCourseAttendance(Course $course): void;
  }
  ```
- [ ] Use `google/apiclient` package (Google Sheets API v4)
- [ ] OAuth2 flow for instructor to authorize Google account
- [ ] Service account option for server-side sync

**Google Sheets Structure:**
```
Sheet: "Attendance - [Course Name]"
Columns: Date | Session | Student Name | Student Code | Status | Scan Time | Location
Row 1: Header
Row 2+: Each attendance record
Auto-formatting: Green=Present, Yellow=Late, Red=Absent
```

**Endpoints:**
```
POST /instructor/google-sheets/connect     → initiate OAuth flow
GET  /instructor/google-sheets/callback    → OAuth callback
POST /instructor/google-sheets/configure   → link spreadsheet to course
POST /instructor/google-sheets/sync/{session} → sync session to sheet
GET  /instructor/google-sheets/status      → connection status
```

---

### Task 4.6: Automated Attendance Sync

**Backend:**
- [ ] Create `SyncAttendanceJob` (queued job):
  ```php
  class SyncAttendanceJob implements ShouldQueue {
      public function handle(AttendanceSession $session): void;
  }
  ```
- [ ] Auto-dispatch when session is closed
- [ ] Also dispatch on each new attendance record (debounced, batch after 30 seconds)
- [ ] Create `AttendanceSyncScheduler` (queued job):
  - Runs every 5 minutes
  - Syncs any unsynced sessions
  - Retries failed syncs

**Automation Rules:**
- [ ] On session close → auto-sync to Google Sheets
- [ ] On new scan → queue sync (batched, not immediate)
- [ ] On manual override → re-sync that session
- [ ] Failed sync → retry 3 times, then notify instructor

---

### Task 4.7: Attendance Reports & Analytics

**Backend:**
- [ ] Create `AttendanceReportService`:
  ```php
  class AttendanceReportService {
      public function getStudentAttendance(Student $student, Course $course): AttendanceReport;
      public function getCourseAttendance(Course $course, ?Carbon $from, ?Carbon $to): CourseReport;
      public function getAttendanceRate(Student $student, Course $course): float;
      public function getLowAttendanceStudents(Course $course, float $threshold = 75): Collection;
      public function generatePDFReport(Course $course, Carbon $from, Carbon $to): PdfReport;
  }
  ```
- [ ] Update `StudentStatistic.attendance_rate` to be populated from attendance records
- [ ] Create report endpoints:
  ```
  GET /instructor/attendance/courses/{course}/report
  GET /instructor/attendance/students/{student}/report
  GET /instructor/attendance/courses/{course}/export?format=csv
  GET /parent/dashboard/{student}/attendance    → parent view
  ```

**Frontend:**
- [ ] Instructor: attendance report page with charts
- [ ] Student: my attendance history page
- [ ] Parent: child's attendance view

---

### Task 4.8: Anti-Cheat & Security

**Backend:**
- [ ] QR code encryption (HMAC-signed, time-limited):
  ```php
  class QRCodeService {
      public function generatePayload(AttendanceSession $session): string;
      public function validatePayload(string $payload): ValidationResult;
  }
  ```
- [ ] Device fingerprinting:
  - Hash of device_id + student_id
  - Prevent same device scanning for multiple students
- [ ] Location validation (optional):
  - Compare scan location with session location
  - Configurable radius (default 500m)
  - Uses phone GPS
- [ ] Time window:
  - QR valid for X minutes (configurable per session)
  - QR auto-refreshes (new QR displayed on instructor screen)
  - Late threshold: if scan time > session start + 10 minutes → marked as "late"
- [ ] Duplicate scan prevention:
  - UNIQUE constraint on (session_id, student_id)
  - Return friendly message if already scanned

---

## Implementation Priority & Dependencies

### Phase 1: Foundation (Week 1-2)
| Task | Feature | Depends On |
|------|---------|-----------|
| 1.1 Payment Abstraction | Payments | None |
| 2.1 Enhanced Activity Logging | Tracking | None |
| 4.1 Attendance DB Schema | Attendance | None |
| 3.1 Parent Account System | Parents | None |

### Phase 2: Core Implementation (Week 3-5)
| Task | Feature | Depends On |
|------|---------|-----------|
| 1.2 Paymob Integration | Payments | 1.1 |
| 2.2 Per-Course Progress | Tracking | 2.1 |
| 2.3 Exam Analytics | Tracking | 2.1 |
| 4.2 QR Code Generation | Attendance | 4.1 |
| 3.2 Parent Registration | Parents | 3.1 |

### Phase 3: Integration (Week 6-8)
| Task | Feature | Depends On |
|------|---------|-----------|
| 1.4 Order Flow Update | Payments | 1.1, 1.2 |
| 1.5 Frontend Payment | Payments | 1.4 |
| 2.4 Student Detail Page | Tracking | 2.2, 2.3 |
| 2.5 Course Analytics | Tracking | 2.2, 2.3 |
| 4.3 Instructor Attendance | Attendance | 4.2 |
| 4.4 Student QR Scan | Attendance | 4.2 |
| 3.3 Parent Dashboard | Parents | 3.1 |

### Phase 4: Polish & Automation (Week 9-10)
| Task | Feature | Depends On |
|------|---------|-----------|
| 1.6 Hybrid Payment Mode | Payments | 1.4, 1.5 |
| 2.6 Export Reports | Tracking | 2.4 |
| 2.7 Real-Time Feed | Tracking | 2.1 |
| 4.5 Google Sheets | Attendance | 4.3 |
| 4.6 Automated Sync | Attendance | 4.5 |
| 4.7 Reports & Analytics | Attendance | 4.3, 4.4 |
| 4.8 Anti-Cheat | Attendance | 4.2 |
| 3.4 Parent Notifications | Parents | 3.3 |
| 3.5 Parent Restrictions | Parents | 3.1 |

---

## Total Task Count

| Feature | Tasks | Estimated Days |
|---------|-------|---------------|
| Payment Gateways | 6 tasks | 8-10 days |
| Student Tracking | 7 tasks | 10-12 days |
| Parent Portal | 5 tasks | 6-8 days |
| QR Attendance | 8 tasks | 12-15 days |
| **Total** | **26 tasks** | **36-45 days** |

---

*Last updated: July 2026*
