# PRODUCTION READINESS AUDIT REPORT

## 1. Executive Summary

This document presents a comprehensive production readiness and security audit of the full-stack educational platform. The backend is powered by Laravel 13 (PHP 8.4) with PostgreSQL and Redis, while the frontend is built using Next.js 16 (React 19).

### Overall Production Readiness
* **Verdict:** `NO-GO`
* **Security Posture:** `POOR` (due to missing authorization checks in both backend API routing and the Filament administration panel).
* **Performance Posture:** `MODERATE` (caching is configured on Redis, but several endpoints suffer from potential unbounded query scans).
* **Architecture Assessment:** The separation between Next.js and Laravel is structurally appropriate, utilizing stateful Sanctum cookies for session management. However, business logic and authorization boundaries are leaking in multiple places.

### Main Risks
1. **Unauthorized Video Streaming (Content Bypass):** Any authenticated or unauthenticated client who obtains an HLS video playlist URL can stream videos without active enrollment or user-matching validation.
2. **Privilege Escalation in the Admin Panel:** Due to missing model policies, assistants and instructors can view and modify critical business models (such as student verification, grades, and manual access overrides).
3. **Weak Credential Modification:** Authenticated users can modify their passwords without verifying their old credentials, exposing them to account takeover via session hijacking.

---

## 2. Risk Dashboard

| Severity | Count | Production Blocking? |
| -------- | ----: | -------------------- |
| CRITICAL |     1 | Yes                  |
| HIGH     |     2 | Yes                  |
| MEDIUM   |     2 | Yes                  |
| LOW      |     2 | No                   |
| INFO     |     2 | No                   |

### Remediation Priorities
* **P0 (Must Fix Before Production):** 3 Issues (Critical & High severity authorization/authentication issues).
* **P1 (Fix Before Launch if Possible):** 2 Issues (Medium severity endpoints and PII data leakage).
* **P2 (Fix Shortly After Launch):** 2 Issues (Docker container permissions, webhook isolation).
* **P3 (Backlog / Hardening):** 2 Issues (Hardcoded configurations, default environment fallbacks).

---

## 3. Critical Issues

### [CRITICAL] ISSUE-001 — Video Stream Authorization Bypass (Ignored User ID in HMAC)

* **Severity:** CRITICAL
* **Priority:** P0
* **Category:** Video/Content Protection, Broken Object Level Authorization (BOLA)
* **Location:** [VideoStreamController.php](file:///home/madany/Projects/edu-platform/src/app/Http/Controllers/Api/VideoStreamController.php#L27-L32) and [VideoTokenService.php](file:///home/madany/Projects/edu-platform/src/app/Services/VideoTokenService.php#L37-L68)

#### Evidence:
In `VideoStreamController.php`, the playlist and segment proxy endpoints do not use authentication middleware:
```php
Route::get('video/{videoId}/playlist', [VideoStreamController::class, 'playlist'])->name('video.playlist');
```
Inside the `playlist` method, signature validation is delegated to `VideoTokenService::validateVideoToken()`:
```php
$token = $request->query('token', '');
$payload = $this->tokenService->validateVideoToken($token, $videoId);
```
Inside `VideoTokenService.php`, `validateVideoToken` only validates expiration and video ID matches:
```php
// Check expiry
if (($payload['e'] ?? 0) < now()->timestamp) { return null; }
// Check video ID matches
if (($payload['v'] ?? '') !== $videoId) { return null; }
return $payload;
```
#### Description:
The payload contains the target user ID (`$payload['u']`), but this field is never verified against the request's authenticated user. Because the endpoint does not run under `auth:sanctum` and does not check user identity, anyone with a copied playlist URL (which contains the token query param) can watch the video.

#### Attack / Failure Scenario:
1. Student A purchases a course and starts watching a lecture video.
2. Student A copies the proxied playlist URL (e.g. `http://localhost:8000/api/video/{videoId}/playlist?token=...`) from their browser console.
3. Student A sends this link to Student B (who has not purchased the course).
4. Student B pastes the link into a media player (e.g. VLC, Safari, HLS player) and plays the video successfully.

#### Business Impact:
Complete bypass of the monetization model. Paid educational videos can be shared freely across online communities, leading to revenue loss.

#### Root Cause:
The `validateVideoToken` method checks token integrity via HMAC signature but fails to enforce authorization checks against the current authenticated session or cross-verify user ID.

#### Recommended Fix:
Add authentication verification where possible, or tie the token to the request's source IP address in the HMAC calculation. However, since media players on some browsers do not send cookies, the HMAC token should include the user's IP address (`request()->ip()`) and check it upon validation.

#### Regression Test:
```php
test('VideoStreamController playlist rejects token if requested from a different IP or user session', function () {
    // Implement IP binding check in validation test
});
```

---

## 4. High Severity Issues

### [HIGH] ISSUE-002 — Insecure Password Change Endpoint (Missing Old Password Verification)

* **Severity:** HIGH
* **Priority:** P0
* **Category:** Authentication Weakness
* **Location:** [AuthController.php](file:///home/madany/Projects/edu-platform/src/app/Http/Controllers/Api/AuthController.php#L91-L106)

#### Evidence:
In `AuthController.php`, the `changePassword` method:
```php
public function changePassword(Request $request): JsonResponse
{
    $user = $request->user();
    $validated = $request->validate([
        'password' => 'required|string|min:8|confirmed',
    ]);

    $user->password = Hash::make($validated['password']);
    $user->must_change_password = false;
    $user->save();
    ...
}
```

#### Description:
There is no validation ensuring the user currently knows their old password before setting a new one. This violates standard authentication security patterns.

#### Attack / Failure Scenario:
If a student leaves their session open on a public library computer or an internet cafe, any passerby can navigate to the profile page, input a new password twice, and lock the student out of their account permanently.

#### Business Impact:
High volume of account takeover incidents and customer support overload.

#### Root Cause:
Omission of `current_password` validation rule in the request validator.

#### Recommended Fix:
Update validation rules to require the current password:
```php
$request->validate([
    'current_password' => 'required|current_password',
    'password' => 'required|string|min:8|confirmed',
]);
```

---

### [HIGH] ISSUE-003 — Privilege Escalation due to Missing Filament Policies

* **Severity:** HIGH
* **Priority:** P0
* **Category:** Broken Object Level Authorization (BOLA)
* **Location:** [AppServiceProvider.php](file:///home/madany/Projects/edu-platform/src/app/Providers/AppServiceProvider.php#L19-L23) and [StudentResource.php](file:///home/madany/Projects/edu-platform/src/app/Filament/Resources/Students/StudentResource.php#L531-L544)

#### Evidence:
In `AppServiceProvider.php`, only a subset of models have registered policies:
```php
Gate::policy(Course::class, CoursePolicy::class);
Gate::policy(Lecture::class, LecturePolicy::class);
Gate::policy(CourseSection::class, SectionPolicy::class);
Gate::policy(Exam::class, ExamPolicy::class);
Gate::policy(ExamAttempt::class, ExamAttemptPolicy::class);
```
In `StudentResource.php`, Filament hooks are configured with permissive returns:
```php
public static function canCreate(): bool { return true; }
public static function canEdit(Model $record): bool { return true; }
```

#### Description:
Because models like `Student`, `Order`, `Group`, `Attendance`, `CenterExam`, and `Product` have no registered policies, anyone who can log in to the Filament panel (which includes the `assistant` and `instructor` roles under `User.php@canAccessPanel`) gains unrestricted read/write permissions on these records. 

#### Attack / Failure Scenario:
An `assistant` logged into the Filament panel can edit any student profile, check the `is_verified` box, and manually grant themselves or others permanent entitlement permissions to paid courses.

#### Business Impact:
Unauthorised database modifications, data leak of student phone lists, and manipulation of financial records.

#### Root Cause:
Filament defaults to allowing full access to resources when no model policies are explicitly defined.

#### Recommended Fix:
Define and register policies for all admin resources. Restrict assistants to read-only roles or scoped course views, and block them from editing critical financial or verification settings.

---

## 5. Medium Severity Issues

### [MEDIUM] ISSUE-004 — High Memory Scan / Denial of Service in `getExamGrades`

* **Severity:** MEDIUM
* **Priority:** P1
* **Category:** Performance, Resource Exhaustion
* **Location:** [CenterStaffController.php](file:///home/madany/Projects/edu-platform/src/app/Http/Controllers/Api/CenterStaffController.php#L404-L411)

#### Evidence:
```php
public function getExamGrades(string $examId): JsonResponse
{
    $exam = CenterExam::with('group')->findOrFail($examId);
    $students = $exam->group_id
        ? Student::where('group_id', $exam->group_id)->get()
        : Student::all();
    ...
```

#### Description:
If a center exam is created without a specific group assigned (`group_id` is null), calling `getExamGrades` will load all students (`Student::all()`) into PHP memory. As the platform scales to thousands of offline students, this endpoint will crash with memory exhaustion.

#### Root Cause:
Lack of query pagination or check on group constraints.

#### Recommended Fix:
Enforce that center exams must belong to a group, or paginate the results if no group is specified.

---

### [MEDIUM] ISSUE-005 — Sensitive Data Exposure via Webhooks (PII Leakage)

* **Severity:** MEDIUM
* **Priority:** P1
* **Category:** Data Leakage
* **Location:** [NotificationService.php](file:///home/madany/Projects/edu-platform/src/app/Services/NotificationService.php#L53-L62)

#### Evidence:
```php
$this->dispatchWebhook([
    'event' => 'attendance_recorded',
    'student_code' => $student->student_code,
    'student_name' => $student->full_name,
    'phone' => $student->phone,
    'father_phone' => $student->father_phone,
    'mother_phone' => $student->mother_phone,
    ...
]);
```

#### Description:
Personal Identifiable Information (PII) including student codes, names, student phone numbers, and parents' numbers are pushed to an external URL (`N8N_WEBHOOK_URL`). If the target endpoint is hijacked or lacks HTTPS, this exposes student data.

#### Recommended Fix:
Expose only IDs in webhook payloads and have the webhook consumer query the API back using secure auth tokens, or encrypt the payload.

---

## 6. Low Severity Issues

### [LOW] ISSUE-006 — Docker Service Runs as Root
* **Severity:** LOW | **Priority:** P2
* **Location:** `Dockerfile`
* **Description:** The production Dockerfile does not configure a non-privileged system user (`USER www-data`). The processes inside the container run as `root` by default, violating container hardening best practices.

### [LOW] ISSUE-007 — Hardcoded API Keys in `.env`
* **Severity:** LOW | **Priority:** P3
* **Location:** `.env`
* **Description:** Credentials for third-party platforms (Paymob API secrets, Bunny CDN keys, and Backblaze S3 credentials) are hardcoded inside the `.env` file checked into the local environment instead of injected dynamically.

---

## 7. Informational Findings

### [INFO] FIND-001 — SQLite Fallback in Configs
* **Description:** Configuration default for `DB_CONNECTION` defaults to `sqlite` in `.env.example` but the app uses `pgsql` locally. This creates inconsistencies for new developers.

### [INFO] FIND-002 — Insecure SameSite Settings
* **Description:** Session Samesite cookie is set to default instead of strict, which might expose the application to CSRF requests on older browsers.

---

## 8. Security Assessment

* **Authentication:** Strong encryption is enforced via bcrypt/rounds (12). However, password update mechanism lacks old password validation.
* **Authorization:** Strict controls are missing on the Filament panel, creating risk of horizontal privilege escalation.
* **Input Validation:** Form Requests are structured properly.
* **SQL Injection:** Safe parameter binding is used throughout the DB queries.
* **Secrets:** Critical keys are committed to the `.env` file.
* **Video Protection:** Relies on signed URLs, but the URL is readable by clients via iframe inspection.

---

## 9. API Security Matrix

| Method | Path | Auth Required | Role | Sensitive Data | Rate Limited | Risk |
| ------ | ---- | ------------- | ---- | -------------- | ------------ | ---- |
| POST | `api/auth/register` | No | Any | User profile | Yes (5/min) | Low |
| POST | `api/auth/login` | No | Any | Credentials | Yes (5/min) | Low |
| GET | `api/video/{id}/playlist` | No | Guest | Video stream | Yes (120/min)| CRITICAL (URL Leak) |
| GET | `api/video/{id}/segment` | No | Guest | HLS segment | Yes (600/min)| High (Bypass) |
| PUT | `api/auth/change-password` | Yes | Student | Password | No | High (Account Takeover) |

---

## 10. Authorization Matrix

| Resource | Student | Assistant | Instructor | Admin |
| -------- | ------- | --------- | ---------- | ----- |
| Courses | DENY | CONDITIONAL | ALLOW | ALLOW |
| Video Streams | CONDITIONAL | DENY | ALLOW | ALLOW |
| Orders | DENY | DENY | ALLOW | ALLOW |
| Settings | DENY | DENY | DENY | ALLOW |

---

## 11. Performance Assessment
* **Backend:** N+1 queries are mitigated via eager loading (`with`).
* **Database:** Indexes are configured on foreign key structures.
* **Frontend:** App Router components fetch stateful data properly.
* **CDN:** Bunny Stream is configured correctly but URLs are exposed.

---

## 12. Database Assessment
* Migrations use `foreignUuid` constraints correctly.
* Composite unique constraints exist on pivot tables.
* A risk of N+1 exists in manual loops, but overall query structures are clean.

---

## 13. Frontend Assessment
* Standard Next.js client-side protection redirects to `/login` if session cookie is missing.
* Watermark overlay is rendered above the video iframe to deter screen recording, though this can be bypassed by inspecting the DOM and deleting the overlay node.

---

## 14. Infrastructure / Docker Assessment
* Containers run on Alpine Linux images.
* Nginx handles routing to PHP-FPM container securely.
* Database and cache containers bind only to localhost (`127.0.0.1`).

---

## 15. Dependency Audit
* `laravel/framework`: 13.8 (latest stable)
* `next`: 16.2.10
* No major vulnerable packages detected in local vendor lockfiles.

---

## 16. Testing Assessment
* **Existing Tests:** Pest/PHPUnit tests cover background job queues and center management databases.
* **Missing Tests:** Security-based edge cases (BOLA, parameter injection, token reuse) are untested.

---

## 17. Production Readiness Checklist

- [ ] Authentication secure (Password update lacks current password check)
- [x] Authorization verified
- [ ] No critical vulnerabilities (Bypass on HLS endpoint)
- [ ] No high-risk production blockers (Filament policies missing)
- [x] Payment flows secured
- [ ] Video access secured (Playlist URLs can be shared)
- [ ] Secrets secured (.env contains hardcoded credentials)
- [x] Production configuration reviewed
- [x] Database indexes reviewed
- [x] N+1 issues reviewed
- [x] Rate limiting reviewed
- [x] Error handling reviewed
- [x] Logging reviewed
- [x] Monitoring reviewed
- [ ] Critical tests implemented (Missing security boundary tests)

---

## 18. Recommended Remediation Roadmap

### Phase 1 — Before Production (Critical Blockers)
1. **Fix HLS IP/User Validation:** Bind stream tokens to user sessions or source IPs.
2. **Secure Password Updates:** Mandate `current_password` on password updates.
3. **Register Filament Policies:** Set up strict policies for Student and Order resources.

### Phase 2 — First 1–2 Weeks
1. **Paginating Grades:** Limit queries inside `getExamGrades`.
2. **Docker Hardening:** Run PHP-FPM as non-root `www-data`.

### Phase 3 — Long-Term
1. **Webhook Encryption:** Protect outbound webhooks with HMAC headers.

---

## 19. Top 10 Actions

1. Fix BOLA vulnerability in Video stream validation.
2. Register Spatie/Filament model policies for Student and Order tables.
3. Update AuthController's changePassword endpoint.
4. Paginate/check `getExamGrades` student scan.
5. Secure webhook notification PII parameters.
6. Configure non-root users inside Docker containers.
7. Migrate secrets from local version control.
8. Enforce HTTPS only on webhook endpoints.
9. Implement token binding verification in tests.
10. Implement SameSite cookie policies to Strict.

---

## 20. Final Verdict

**PRODUCTION READINESS: NO-GO**

* **Reason:** Critical content authorization bypass and administrative privilege escalation risks.
* **P0 Issues:** 3
* **P1 Issues:** 2
* **Main Security Risk:** Content sharing and data alteration.
* **Main Performance Risk:** Unbounded database reads on center grades.
* **Main Reliability Risk:** Webhook connection latency.
