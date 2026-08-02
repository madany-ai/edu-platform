# Mobile App — Product Requirements Document (PRD)

> **Purpose:** Complete feature specification for the student-facing mobile application. The backend APIs are fully built and ready. This document defines the scope of work for cost estimation.

---

## Project Overview

An Arabic-first (RTL) white-label education platform with an instructor-student model:

| Layer | Tech Stack | Status |
|-------|-----------|--------|
| Backend API | Laravel 13 + Sanctum Auth | **Done** |
| Admin Panel (Instructor/Assistant) | Filament v5 (Web only) | **Done** |
| Student Web App | Next.js 16 + React 19 | **Done** |
| **Student Mobile App** | **Flutter (iOS + Android)** | **To be built** |

### Key Architectural Decisions

- **Mobile = Students only.** Instructors and assistants manage everything through the web admin panel. No admin features needed in the mobile app.
- **All APIs are ready.** The mobile app consumes the same REST APIs as the web frontend (~55 endpoints). No backend changes required.
- **Arabic RTL throughout.** All UI text, layouts, and components must support right-to-left Arabic.

---

## User Roles

| Role | Description | Mobile App? |
|------|-------------|-------------|
| **Student** | Watches lectures, takes exams, purchases courses, asks Q&A questions | **Yes** |
| **Instructor** | Manages courses, students, exams, payments | **No** — uses Filament web panel |
| **Teaching Assistant** | Replies to Q&A, views assigned courses | **No** — uses Filament web panel |

---

## Feature Specifications

### 1. Authentication & Account Management

#### 1.1 Registration
- Multi-step Arabic registration form
- Required fields: 4-part Arabic name (first, second, third, last), email, password, phone number, father's phone, mother's phone, guardian's job title, gender, birth date, governorate, grade level
- Cloudflare Turnstile CAPTCHA validation
- Account starts in **pending** status — instructor must approve it before login

#### 1.2 Login
- Multi-field login: email, phone number, student code (`ST30042`), or student phone number
- Status-based messaging: pending → "waiting for approval", rejected → "account rejected"
- Rate-limited attempts (brute-force protection)

#### 1.3 Password Reset
- Email-based reset token
- New password form with token validation

#### 1.4 Profile
- View and edit student profile data
- Change password

#### 1.5 API Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Create new student account |
| POST | `/auth/login` | Login (email/phone/student code + password) |
| POST | `/auth/logout` | Revoke token |
| GET | `/auth/me` | Get current user profile |
| POST | `/auth/forgot-password` | Send reset email |
| POST | `/auth/reset-password` | Reset with token |
| GET | `/governorates` | List governorates for registration |
| GET | `/grade-levels` | List grade levels for registration |

---

### 2. Course Catalog & Content Browsing

#### 2.1 Content Hierarchy
```
Course → Section (Month/Unit) → Lecture
                                    ├── Video (HLS / Bunny Stream / YouTube)
                                    ├── PDF Files (attachments)
                                    ├── Exam (multiple choice, true/false, essay)
                                    ├── Assignment (same as exam, marked separately)
                                    └── Q&A Board (student questions & replies)
```

#### 2.2 Course Listing (Public)
- Paginated catalog with search (debounced)
- Course card: thumbnail, title, instructor name, price (EGP), section count
- Course detail page: full description, content tree (sections → lectures), pricing, instructor info

#### 2.3 Lecture Detail
- Title, description, duration (minutes)
- Ordered sections and lectures
- Completion status indicators (checkmarks, lock icons)

#### 2.4 API Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/courses` | List published courses (paginated + search) |
| GET | `/courses/{id}` | Course detail with sections, lectures, exams |
| GET | `/courses/{id}/lectures/{lectureId}` | Lecture detail |

---

### 3. Video Player (Core Feature)

#### 3.1 Supported Video Sources
| Source | Storage | Protocol |
|--------|---------|----------|
| **HLS (Self-hosted)** | MinIO object storage | AES-128 encrypted HLS |
| **Bunny Stream** | Bunny CDN | Signed iframe embed |
| **YouTube** | YouTube servers | IFrame API |

#### 3.2 Player Controls
- Play / Pause
- Seek bar with buffer indicator
- Volume control with expandable slider
- Quality level switching (auto + manual)
- Fullscreen toggle
- Auto-resume from last position
- Auto-hide controls after 3 seconds of inactivity

#### 3.3 Anti-Piracy / Security (CRITICAL)
These protections must be implemented on mobile too:

| Protection | Description |
|------------|-------------|
| **Dynamic Watermark** | Student name + email overlaid on video, rotating position every 30 seconds |
| **Screenshot Prevention** | Prevent screen capture on Android/iOS (FLAG_SECURE / equivalent) |
| **DevTools Detection** | Pause video if developer tools detected (web) |
| **Keyboard Lockdown** | Block F12, Ctrl+Shift+I/J/C/K, Ctrl+U |
| **Right-Click Blocking** | Disable context menu on video |
| **Drag Blocking** | Prevent drag on video elements |
| **Picture-in-Picture** | Explicitly disabled |

> **Note for Mobile:** Some web protections (DevTools, keyboard shortcuts) don't apply on mobile. Focus on **watermark overlay** and **screen capture prevention** as the primary mobile anti-piracy measures.

#### 3.4 Progress Tracking
- Heartbeat: report current playback position every 20 seconds via `POST /lectures/{id}/progress`
- Completion: mark lecture as complete when ≥ 90% watched
- Update student statistics: watch minutes, completed lectures

#### 3.5 API Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/video/{videoId}/playlist` | Get HLS playlist (rate-limited: 120/min) |
| GET | `/video/{videoId}/segment` | Proxy HLS segment (rate-limited: 600/min) |
| GET | `/lectures/{id}/key?token=...` | Get AES-128 decryption key (token: 5-min expiry) |
| POST | `/lectures/{id}/progress` | Report playback progress |

#### 3.6 Token Security
- **HMAC Token (VideoTokenService):** HMAC-SHA256 signed, binds `video_id + user_id + lecture_id`, 4-hour expiry. Used for playlist/segment access.
- **Encrypted Token (VideoAccessService):** Laravel-encrypted, binds `user_id + lecture_id + IP + expiry`, 5-minute expiry. Used for decryption key access.

---

### 4. Exam & Quiz System

#### 4.1 Question Types
| Type | Description |
|------|-------------|
| **Multiple Choice** | Single correct answer from N choices |
| **True/False** | Binary correct/incorrect |
| **Essay** | Free-text response (manually graded by instructor) |

#### 4.2 Exam-Taking Interface
- One question at a time with quick-jump navigation map
- Countdown timer (auto-submits on expiry)
- Progress bar showing current position
- Image support per question
- Confirmation dialog for incomplete submissions
- Instant result display after submission

#### 4.3 Blocking Exams (Gating Mechanism)
- An exam marked as **blocking** prevents access to subsequent lectures until passed
- When a blocking exam exists and hasn't been passed → exam tab is shown automatically instead of video
- After passing → "Continue Lesson" button returns to video
- Sequential gating: section order → lecture order → exam order

#### 4.4 Exam Attempt History
- Table of all attempts across all courses
- Shows: exam title, score, course name, date, status
- Filterable by course

#### 4.5 API Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/lectures/{id}/exam` | Get exam + latest attempt |
| POST | `/exams/{id}/start` | Start or resume attempt |
| POST | `/attempts/{id}/submit` | Submit answers + auto-grade |
| GET | `/attempts/{id}/result` | Get graded result with correct answers |
| GET | `/my-attempts` | List all student's attempts |

---

### 5. Purchasing & Entitlements

#### 5.1 Product Types (Polymorphic)
| Type | What It Grants |
|------|----------------|
| **Full Course** | Access to all lectures in all sections |
| **Section/Unit** | Access to all lectures in one section |
| **Single Lecture** | Access to one lecture only |
| **Bundle** | Multiple products at a discounted price |

#### 5.2 Free vs. Paid Courses
- **Free courses:** Direct enrollment via `POST /courses/{id}/enroll`
- **Paid courses:** Require purchase → instructor confirms payment → entitlements granted

#### 5.3 Purchase Flow
1. Student views product/bundle detail (price, included lectures)
2. Student creates order → `POST /orders` with `purchasable_id` + `purchasable_type`
3. Student pays manually (Vodafone Cash, bank transfer, InstaPay, etc.)
4. Instructor confirms payment via web admin panel
5. Entitlements are auto-granted for all included lectures

#### 5.4 Entitlement Rules
- Access is checked at the **lecture level** (not course level)
- Either a valid entitlement OR an active enrollment grants access
- Entitlements can be permanent or time-limited (based on `access_duration_days`)
- Idempotent: duplicate grants are prevented

#### 5.5 API Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/products` | List active products |
| GET | `/products/{id}` | Product detail |
| GET | `/bundles` | List bundles |
| GET | `/bundles/{id}` | Bundle detail with included products |
| POST | `/orders` | Create purchase order |
| POST | `/courses/{id}/enroll` | Enroll in free course |
| GET | `/my-enrollments` | List enrolled courses |
| GET | `/my-entitlements` | List active entitlements |

---

### 6. Q&A (Questions & Answers)

#### 6.1 Features
- **Public Q&A board per lecture:** all students see all questions
- Post a text question under any lecture (requires enrollment/entitlement)
- Reply to any question (any authenticated user)
- Delete own questions and replies
- Reply count tracking with real-time updates

#### 6.2 Real-Time Updates
- Auto-polling every 15 seconds
- Toast notification when new replies arrive
- Reply tracker using ref-based change detection

#### 6.3 Notifications
- New question → notifies instructor + assigned assistants
- New reply → notifies the original question author

#### 6.4 API Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/lectures/{id}/questions` | Post a question |
| GET | `/lectures/{id}/questions` | List questions (paginated) |
| GET | `/questions/{id}` | View question with replies |
| POST | `/questions/{id}/replies` | Reply to question |
| GET | `/my-questions` | Student's questions across all courses |
| DELETE | `/questions/{id}` | Delete own question |
| DELETE | `/replies/{id}` | Delete own reply |

---

### 7. Student Dashboard

#### 7.1 Overview
- Active enrollments count
- Completed lectures count
- Total watch minutes
- Average exam score
- Completed courses count

#### 7.2 My Courses
- Enrolled course cards with progress bar
- "Continue Learning" button → last viewed lecture

#### 7.3 My Exams
- Table of all exam attempts with scores, course info, status

#### 7.4 My Questions
- All questions posted across courses with replies

#### 7.5 Notifications
- Notification feed with read/unread status

#### 7.6 API Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/dashboard/student` | Student stats overview |
| GET | `/notifications` | List notifications |

---

### 8. Notifications

#### 8.1 Trigger Events
| Event | Recipient |
|-------|-----------|
| Account approved | The student |
| Account rejected | The student |
| New Q&A reply | Question author |
| General announcement | All students |

#### 8.2 Display
- Notification list with read/unread badges
- Unread count indicator

---

### 9. Search & Filtering

- Debounced course search by title
- Exam history filter by course

---

### 10. Reference Data

| Data | Description |
|------|-------------|
| Governorates | 27 Egyptian governorates |
| Grade Levels | Prep school (1-3), Secondary school (1-3) with academic tracks |

---

## Scope Summary

| # | Feature | Complexity | Priority |
|---|---------|-----------|----------|
| 1 | Auth (register, login, password reset) | Medium | High |
| 2 | Course catalog (browse, search, detail) | Medium | High |
| 3 | Video player (HLS / Bunny / YouTube) | **Very High** | **Critical** |
| 4 | Anti-piracy (watermark, screen capture prevention) | **Very High** | **Critical** |
| 5 | Exam system (timer, question types, instant results) | **High** | High |
| 6 | Blocking exams (gating mechanism) | Medium | High |
| 7 | Course enrollment (free + paid) | Medium | High |
| 8 | Purchasing & product browsing | Medium | High |
| 9 | Q&A with real-time polling | Medium | Medium |
| 10 | Student dashboard (stats, my courses, my exams) | Medium | High |
| 11 | Notifications | Low | Medium |
| 12 | Progress tracking (heartbeat, completion %) | Medium | High |
| 13 | Registration form (multi-step, Arabic, cascading selects) | Medium | High |
| 14 | Password reset flow | Low | Medium |
| 15 | Student profile | Low | Medium |

---

## Technical Notes for Developers

### API & Backend
- **All 55+ REST APIs are built and tested** (407 backend tests, 893 assertions)
- **No backend work needed** — mobile app consumes existing endpoints
- Authentication: **Sanctum API Tokens** (Bearer token in Authorization header)
- All IDs are **UUIDs**
- All prices in **Egyptian Pounds (EGP)**
- All text in **Arabic (RTL)**
- Dates formatted as **ar-EG** locale

### Authentication Flow
```
1. POST /auth/login → returns { token, user }
2. Store token in secure storage (Keychain / EncryptedSharedPreferences)
3. Attach token to all requests: Authorization: Bearer {token}
4. On 401 response → clear token → redirect to login
```

### Video Streaming (Most Complex Feature)
- **HLS:** Use `react-native-video` or `video_player` with HLS support. The app needs to:
  1. Fetch playlist from `GET /video/{videoId}/playlist?token=...`
  2. Rewrite segment URLs to go through the proxy
  3. Fetch decryption key from `GET /lectures/{id}/key?token=...`
  4. Overlay dynamic watermark (student name + email)
- **Bunny Stream:** Embed Bunny player iframe with signed URLs
- **YouTube:** YouTube IFrame API or native YouTube player
- Token lifetime: HMAC tokens = 4 hours, Key tokens = 5 minutes (refresh before expiry)

### Performance Patterns
- **Pagination** on all list endpoints
- **Caching layer** (React Query / similar) to avoid redundant API calls
- **Debounce** on search inputs (300ms)
- **Polling** for Q&A updates (15-second interval)
- **Optimistic updates** for mutations (enroll, post question, etc.)

### Security Requirements
- TLS/HTTPS mandatory for all API calls
- Bearer token on every request
- Rate limiting on login endpoints
- Rate limiting on video streaming (120 playlist/min, 600 segment/min)

### Tech Stack
- **Flutter** (cross-platform: iOS + Android)
- HTTP client: `dio` or `http` package
- State management: `flutter_riverpod` or `flutter_bloc`
- Video player: `video_player` or `chewie` with HLS support
- Secure storage: `flutter_secure_storage` (Keychain / EncryptedSharedPreferences)
- Image caching: `cached_network_image`
- Push notifications: `firebase_messaging`
- Arabic RTL: built-in Flutter RTL support

---

## What's NOT in Scope

- Instructor/assistant admin panel (web only)
- Course creation/management
- Student approval workflow
- Payment confirmation (instructor does this on web)
- Exam grading (instructor does this on web)
- Settings/configuration management
- Activity logs

---

*Last updated: July 2026*
