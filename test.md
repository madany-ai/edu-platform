Now I have read every file in the codebase. Let me compile the comprehensive analysis.

---

# Comprehensive Test Coverage Analysis: edu-platform

## SECTION 1: WHAT IS ALREADY TESTED

### AuthTest.php -- 12 tests
| # | Test Name | Endpoint / Logic | Status Code |
|---|-----------|-----------------|-------------|
| 1 | `registers a new student with pending status` | POST /api/auth/register | 201 |
| 2 | `creates user with pending status and student role` | POST /api/auth/register | -- |
| 3 | `rejects registration with existing email` | POST /api/auth/register | 422 |
| 4 | `rejects registration with invalid data` | POST /api/auth/register | 422 |
| 5 | `allows login with correct credentials` | POST /api/auth/login | 200 |
| 6 | `rejects login for pending user` | POST /api/auth/login | 403 |
| 7 | `rejects login for rejected user` | POST /api/auth/login | 403 |
| 8 | `rejects login with wrong password` | POST /api/auth/login | 401 |
| 9 | `allows authenticated user to access /me` | GET /api/auth/me | 200 |
| 10 | `rejects unauthenticated access to /me` | GET /api/auth/me | 401 |
| 11 | `allows user to logout` | POST /api/auth/logout | 200 |
| 12 | `rejects unauthenticated logout` | POST /api/auth/logout | 401 |

### RolesAndAuthTest.php -- 10 tests
| # | Test Name | Logic |
|---|-----------|-------|
| 1 | `returns 401 for unauthenticated GET requests` | Multiple endpoints |
| 2 | `returns 401 for unauthenticated POST requests` | logout |
| 3 | `prevents student from creating a course` | POST /api/courses -- 403 |
| 4 | `prevents student from viewing instructor students list` | GET /api/instructor/students -- 403 |
| 5 | `prevents assistant from deleting a course` | DELETE /api/courses/{id} -- 403 |
| 6 | `allows assigned assistant to access course lectures` | GET /api/lectures/{id} -- 200 |
| 7 | `prevents unassigned assistant from accessing lectures` | GET /api/lectures/{id} -- 403 |
| 8 | `allows instructor to manage own course` | PUT /api/courses/{id} -- 200 |
| 9 | `prevents instructor from managing other instructors course` | PUT /api/courses/{id} -- 403 |
| 10 | `prevents student from viewing course enrollments` | GET /api/courses/{id}/enrollments -- 403 |

### EntitlementAccessTest.php -- 9 tests
| # | Test Name | Logic |
|---|-----------|-------|
| 1 | `denies access with expired entitlement` | GET /api/lectures/{id} -- 403 |
| 2 | `grants access with valid entitlement` | GET /api/lectures/{id} -- 200 |
| 3 | `denies access without entitlement` | GET /api/lectures/{id} -- 403 |
| 4 | `creates entitlement via order API for single product` | POST /api/orders -- 201 |
| 5 | `creates entitlements via order API for bundle` | POST /api/orders -- 201 |
| 6 | `rejects purchase from unverified student` | POST /api/orders -- 403 |
| 7 | `returns entitlements via my-entitlements endpoint` | GET /api/my-entitlements -- 200 |
| 8 | `sets correct expires_at from product access_duration_days` | GrantEntitlementService |
| 9 | `grants access to free course via enrollment` | VideoAccessService.canAccess |

### PreExamGatingTest.php -- 9 tests
| # | Test Name | Logic |
|---|-----------|-------|
| 1 | `blocks video when pre-exam is not passed` | isBlockedByExam -- true |
| 2 | `allows video after passing pre-exam` | isBlockedByExam -- false |
| 3 | `keeps video blocked when exam is failed` | isBlockedByExam -- true |
| 4 | `saves exam score correctly in database` | ExamService -- score=100 |
| 5 | `works through exam attempt API flow` | start -> submit -> result |
| 6 | `blocks lecture access when exam not passed` | GET /api/lectures/{id} -- 403 |
| 7 | `allows lecture access after passing exam` | GET /api/lectures/{id} -- 200 |
| 8 | `does not block lecture by its own exam` | isBlockedByExam -- false |
| 9 | `never blocks instructor by exams` | isBlockedByExam -- false |

### AssignmentTest.php -- 6 tests
| # | Test Name | Logic |
|---|-----------|-------|
| 1 | `allows student to view assignment with questions and choices` | GET /api/lectures/{id}/assignment -- 200 |
| 2 | `allows student to start and submit assignment with full score` | POST start + submit -- score 100 |
| 3 | `gives zero score for wrong answer` | POST submit -- score 0 |
| 4 | `allows student to view submission result with answers` | GET /api/attempts/{id}/result -- 200 |
| 5 | `returns 404 when no assignment exists for lecture` | GET /api/lectures/{id}/assignment -- 404 |
| 6 | `returns submitted attempts in my-attempts endpoint` | GET /api/my-attempts -- 200 |

### BackgroundJobsTest.php -- 5 tests
| # | Test Name | Logic |
|---|-----------|-------|
| 1 | `sends notification to instructors on student registration` | NotificationService |
| 2 | `creates notification in database correctly` | NotificationService.send |
| 3 | `creates notification with correct structure` | Notification model attributes |
| 4 | `dispatches ProcessVideoHLS job when lecture has video` | Queue::fake |
| 5 | `does not dispatch ProcessVideoHLS job without video` | Queue::fake |

### EntitlementEngineTest.php -- 3 tests
| # | Test Name | Logic |
|---|-----------|-------|
| 1 | `grants entitlement for single lecture product` | GrantEntitlementService |
| 2 | `grants entitlement for section product` | GrantEntitlementService -- all lectures |
| 3 | `grants entitlement for bundle` | GrantEntitlementService -- multiple products |

---

## SECTION 2: WHAT IS NOT TESTED -- Every Missing Scenario

---

### A. AUTH CONTROLLER & SERVICE (Register/Login/Me/Logout)

**File: `src/app/Http/Controllers/Api/AuthController.php` + `src/app/Services/AuthService.php`**

**Already tested:** Valid registration, duplicate email, invalid data, correct login, wrong password, pending/rejected status, /me authenticated/unauthenticated, logout.

**MISSING SCENARIOS:**

1. **Login by student_code** -- AuthService line 78: `Student::where('student_code', $emailOrCode)` -- there is zero test coverage for logging in with a student_code instead of email.

2. **Login by user phone** -- AuthService line 73: `orWhere('phone', $emailOrCode)` -- login using a phone number on the users table is never tested.

3. **Login by student phone** -- AuthService line 79: `Student::where('phone', $emailOrCode)` -- login using the student record's phone field is never tested.

4. **Login updates `last_login_at`** -- AuthService line 98: `$user->update(['last_login_at' => now()])` -- never verified that the field is set after login.

5. **Registration creates Student record with correct fields** -- AuthService lines 31-45 create a Student record. The test only checks the User model (`assertDatabaseHas('users', ...)`), never the `students` table.

6. **Registration stores `governorate_id` and `grade_level_id`** -- Student model receives these but are never asserted.

7. **Registration returns correct user name format** -- AuthService line 23: `'name' => $data['first_name'] . ' ' . $data['last_name']` -- the test checks `name => 'Test User'` but does not explicitly test that `second_name` and `third_name` are excluded from the User name.

8. **`/me` returns `roles` array** -- AuthController line 75 returns `'roles' => $roles` -- the test only checks `id` and `email`, never the `roles` key.

9. **`/me` returns `student` data when user is a student** -- AuthController lines 66-68 load the student relation for students. Never tested.

10. **`/me` returns `student: null` when user is not a student** -- AuthController line 76: `'student' => $user->relationLoaded('student') ? $user->student : null`. Never tested for non-student users (instructor, assistant).

11. **`/me` returns `status` field** -- AuthController line 74 returns `'status' => $user->status`. Never asserted.

12. **Registration with password shorter than 8 characters** -- RegisterRequest requires `min:8`. Not tested.

13. **Registration with password confirmation mismatch** -- RegisterRequest requires `confirmed`. Not tested.

14. **Registration with invalid email format** -- RegisterRequest requires `email` validation. Not tested.

15. **Registration with invalid gender** -- RegisterRequest: `in:male,female`. Not tested.

16. **Registration with non-existing `governorate_id`** -- RegisterRequest: `exists:governorates,id`. Not tested.

17. **Registration with non-existing `grade_level_id`** -- RegisterRequest: `exists:grade_levels,id`. Not tested.

18. **Login with non-existent email** -- Returns null (401). The existing "wrong password" test assumes the user exists. A non-existent email scenario is subtly different.

19. **Rate limiting on `/auth/register` and `/auth/login`** -- Routes apply `throttle:login` middleware. Never tested (e.g., sending >N requests and checking for 429).

20. **StoreCourseRequest authorization** -- `authorize()` returns `$this->user()?->hasRole('instructor') ?? false`. Non-instructor gets automatic 403 before validation. Never tested.

---

### B. COURSE CONTROLLER (Full CRUD + Sections + Lectures + Stream + Progress)

**File: `src/app/Http/Controllers/Api/CourseController.php`**

**Already tested:** Instructor can update own course, cannot update another's, student cannot create course, assistant access to lectures.

**MISSING SCENARIOS:**

#### GET /api/courses (Public Index)
21. **Returns only published courses** -- CourseService filters `where('status', 'published')`. A draft course should not appear.

22. **Search filter works** -- CourseService line 17: `where('title', 'like', "%{$filters['search']}%")`. Not tested.

23. **Returns paginated results (12 per page)** -- CourseService line 23: `->paginate(12)`. Not tested.

24. **Returns course with instructor, sections count, enrollments count** -- CourseService eager-loads these. Not tested.

#### GET /api/courses/{course} (Public Show)
25. **Returns course with sections, lectures, exams, assignments** -- CourseController line 32 loads these relations. Not tested.

26. **Returns `progress_map` for authenticated student** -- CourseController lines 37-49 build progress_map from StudentActivity. Not tested.

27. **Returns no `progress_map` for unauthenticated user** -- The `if ($user)` guard. Not tested.

28. **Returns 404 for non-existent course** -- Implicit from route model binding. Not explicitly tested.

#### POST /api/courses (Store)
29. **Instructor creates course successfully** -- Returns CourseResource with 200/201. Not tested.

30. **Course gets `instructor_id` set to current user** -- CourseController line 62: `'instructor_id' => $request->user()->id`. Not tested.

31. **Course gets auto-generated `course_code`** -- Course model boot event line 43. Not tested.

32. **Validation: title required, description required, price required** -- StoreCourseRequest rules. Not tested.

33. **Validation: status in:draft,published,archived** -- StoreCourseRequest rule. Not tested.

#### PUT /api/courses/{course} (Update)
34. **Updates course fields correctly** -- Tested in RolesAndAuthTest for title but not for other fields (thumbnail, status, price edge cases).

35. **Validation errors on update** -- StoreCourseRequest applies to update too. Not tested.

#### DELETE /api/courses/{course} (Destroy)
36. **Instructor deletes own course -- returns 200 with message** -- Not tested.

37. **Instructor cannot delete another instructor's course -- 403** -- Not tested.

38. **Student cannot delete a course -- 403** -- Not tested.

#### POST /api/courses/{course}/sections (Store Section)
39. **Instructor creates section successfully** -- Returns 201. Not tested.

40. **Section gets correct sort_order** -- Not tested.

41. **Validation: title required** -- Not tested.

42. **Authorization: only course instructor can create sections** -- No middleware/policy protects this explicitly (it is inline). Not tested.

#### PUT /api/courses/{course}/sections/{section} (Update Section)
43. **Instructor updates section** -- Not tested.

44. **Authorization: non-instructor cannot update section** -- Not tested.

#### DELETE /api/courses/{course}/sections/{section} (Destroy Section)
45. **Instructor deletes section -- returns success message** -- Not tested.

46. **Authorization: non-instructor cannot delete section** -- Not tested.

#### POST /api/sections/{section}/lectures (Store Lecture)
47. **Instructor creates lecture successfully** -- Returns 201. Not tested.

48. **Creating lecture with `youtube_url` creates LectureVideo record** -- CourseController lines 158-165. Not tested.

49. **Creating lecture without `youtube_url` does NOT create LectureVideo** -- Not tested.

50. **Validation: title required, youtube_url must be URL format** -- Not tested.

51. **Authorization: non-instructor cannot create lectures** -- Not tested.

#### PUT /api/sections/{section}/lectures/{lecture} (Update Lecture)
52. **Instructor updates lecture fields** -- Not tested.

53. **Updating lecture with `youtube_url` uses `updateOrCreate` on video** -- CourseController lines 183-192. Not tested.

54. **Updating lecture without `youtube_url` does not touch video** -- Not tested.

55. **Authorization: non-instructor cannot update lecture** -- Not tested.

#### DELETE /api/sections/{section}/lectures/{lecture} (Destroy Lecture)
56. **Instructor deletes lecture -- returns success message** -- Not tested.

57. **Authorization: non-instructor cannot delete lecture** -- Not tested.

#### GET /api/lectures/{lecture} (Show Lecture)
58. **Returns lecture with video, files, section.course, exams, assignments** -- CourseController line 88. Not tested.

59. **Returns lecture progress data for authenticated student** -- CourseController lines 93-101. Not tested.

60. **CheckEnrollment middleware blocks unenrolled student** -- Indirectly tested via PreExamGatingTest but never tested in isolation for an enrolled student viewing a lecture from a paid course.

#### GET /api/lectures/{lecture}/stream (Stream Lecture)
61. **Access control: VideoAccessService.canAccess denied returns 403** -- Not tested.

62. **Video not found (null or not completed) returns 404** -- CourseController lines 211-213. Not tested.

63. **Storage file not found returns 404** -- CourseController line 217. Not tested.

64. **HLS playlist has key URI replaced with signed token URL** -- CourseController lines 228-229. Not tested.

65. **HLS playlist has segment paths replaced with absolute MinIO URLs** -- CourseController lines 232-233. Not tested.

66. **Rate limiting (throttle:video)** -- Not tested.

#### GET /api/lectures/{lecture}/key (Stream Key)
67. **Missing token returns 400** -- CourseController line 244. Not tested.

68. **Invalid or expired token returns 403** -- CourseController line 248. Not tested.

69. **Encryption key not found returns 404** -- CourseController line 253. Not tested.

70. **Valid token returns binary key with correct Content-Type** -- CourseController lines 256-261. Not tested.

#### POST /api/lectures/{lecture}/progress (Update Progress)
71. **Creates/updates StudentActivity with video_progress type** -- Not tested.

72. **When `is_completed=true`, creates video_completed activity** -- ProgressService lines 39-46. Not tested.

73. **Idempotent: completing same lecture twice does not double-count** -- ProgressService lines 33-37 check `$wasCompleted`. Not tested.

74. **Updates StudentStatistic** -- ProgressService lines 31, 47-50, 53-54. Not tested.

75. **Duration added to `total_watch_minutes`** -- ProgressService line 50. Not tested.

76. **Default duration (10 min) when lecture duration is null** -- ProgressService line 49. Not tested.

77. **No student record returns 404** -- CourseController lines 274-276. Not tested.

78. **Validation: `current_time` required numeric, `is_completed` required boolean** -- Not tested.

---

### C. ENROLLMENT CONTROLLER & SERVICE

**File: `src/app/Http/Controllers/Api/EnrollmentController.php` + `src/app/Services/EnrollmentService.php`**

**Already tested:** Unverified student rejected, my-entitlements returns data, free course access via enrollment.

**MISSING SCENARIOS:**

#### GET /api/my-enrollments
79. **Returns student's enrollments with course.instructor and course.sections** -- Not tested.

80. **Returns empty array when student has no enrollments** -- Not tested.

81. **Returns fake enrollments for courses accessed via entitlements only** -- EnrollmentService lines 84-103 create "fake" enrollments for entitlement-only courses. Not tested.

82. **Returns empty collection for user without student record** -- EnrollmentService lines 63-66. Not tested.

#### POST /api/courses/{course}/enroll
83. **Creates enrollment with `manual` source** -- Not tested.

84. **Returns 201 with EnrollmentResource** -- Not tested.

85. **Idempotent (firstOrCreate)** -- Enrolling same student twice does not duplicate. Not tested.

86. **Non-student user gets error** -- `Student::where('user_id', ...)->firstOrFail()` throws 404. Not tested.

#### POST /api/courses/{course}/purchase
87. **Creates enrollment with `purchase` source** -- Not tested.

88. **Returns 201** -- Not tested.

#### GET /api/courses/{course}/enrollments (Instructor only)
89. **Returns enrollments with student.user** -- Not tested.

90. **Instructor can view enrollments for their course** -- Not tested.

91. **Instructor cannot view enrollments for another instructor's course** -- No explicit guard exists. The `role:instructor` middleware only checks role, not ownership. Not tested.

#### DELETE /api/courses/{course}/enrollments/{student} (Revoke)
92. **Sets enrollment status to `suspended`** -- EnrollmentService line 37. Not tested.

93. **Instructor can revoke enrollment** -- Not tested.

94. **Student cannot revoke enrollment (403)** -- Not tested.

95. **Revoking non-existent enrollment returns false** -- Not tested (what happens to the response?).

#### EnrollmentService Unit Tests
96. **`isEnrolled` returns false when user has no Student record** -- EnrollmentService lines 42-44. Not tested.

97. **`isEnrolled` returns false when student exists but not enrolled** -- Not tested.

98. **`isEnrolled` returns false when enrollment is not active** -- Not tested.

99. **`getStudentEntitlements` returns empty collection for user without student** -- EnrollmentService lines 111-113. Not tested.

---

### D. PRODUCT CONTROLLER

**File: `src/app/Http/Controllers/Api/ProductController.php`**

**Already tested:** Nothing for this controller.

**MISSING SCENARIOS:**

100. **GET /api/products -- returns active products** -- ProductController line 15: `where('is_active', true)`. Not tested.

101. **GET /api/products -- inactive products excluded** -- Not tested.

102. **GET /api/products -- filter by type=lecture** -- ProductController lines 18-27. Not tested.

103. **GET /api/products -- filter by type=section** -- Not tested.

104. **GET /api/products -- filter by type=course** -- Not tested.

105. **GET /api/products -- includes sellable relation** -- Not tested.

106. **GET /api/products/{product} -- course-type product loads sellable.sections.lectures** -- ProductController lines 51-52. Not tested.

107. **GET /api/products/{product} -- section-type product loads sellable.lectures** -- ProductController line 50. Not tested.

108. **GET /api/products/{product} -- lecture-type product loads sellable** -- ProductController line 54. Not tested.

109. **GET /api/bundles -- returns bundles with products.sellable** -- Not tested.

110. **GET /api/bundles/{bundle} -- returns bundle with products.sellable** -- Not tested.

---

### E. ORDER CONTROLLER

**File: `src/app/Http/Controllers/Api/OrderController.php`**

**Already tested:** Order creation for product, order creation for bundle, unverified student rejection.

**MISSING SCENARIOS:**

111. **Missing `purchasable_id` returns validation error** -- Not tested.

112. **Missing `purchasable_type` returns validation error** -- Not tested.

113. **Invalid `purchasable_type` (not product/bundle) returns validation error** -- Not tested.

114. **User without student record returns 404** -- OrderController lines 27-31. Not tested.

115. **Product not found returns 404** -- OrderController lines 47-51. Not tested.

116. **Bundle not found returns 404** -- Same path as above. Not tested.

117. **Order `amount_cents` calculated correctly (price * 100)** -- OrderController line 59. Not tested.

118. **Order has `currency` = 'EGP'** -- Not tested.

119. **Order has `payment_method` = 'mock'** -- Not tested.

120. **Order has `transaction_id` starting with 'MOCK-'** -- Not tested.

121. **Order has `status` = 'completed' and `paid_at` set** -- Not tested.

122. **Transaction wraps order creation + entitlement granting** -- OrderController lines 54-70. Not tested (e.g., verifying both happen atomically).

---

### F. EXAM CONTROLLER & SERVICE

**File: `src/app/Http/Controllers/Api/ExamController.php` + `src/app/Services/ExamService.php`**

**Already tested:** Assignment view, start/submit/score flow, result view, 404 for missing assignment, my-attempts, pre-exam gating.

**MISSING SCENARIOS:**

#### GET /api/lectures/{lecture}/exam
123. **Returns exam with questions and choices** -- ExamController lines 21-29. Not tested.

124. **Returns `latest_attempt` for the student** -- ExamController lines 37-44. Not tested.

125. **Returns 404 when no exam exists** -- ExamController line 33. Not tested.

126. **With `exam_id` query param, filters to specific exam** -- ExamController lines 22-26. Not tested.

127. **With invalid `exam_id`, returns 404** -- Not tested.

#### POST /api/lectures/{lecture}/exam (Instructor Store)
128. **Instructor creates exam with questions and choices** -- ExamController lines 84-101. Not tested.

129. **Exam created with default duration (30 min)** -- ExamService line 27. Not tested.

130. **`is_assignment` flag set correctly** -- Not tested.

131. **Validation: title required, questions.*.question required, choices min:2** -- Not tested.

132. **Non-instructor gets 403** -- Role middleware. Not tested.

#### PUT /api/exams/{exam} (Instructor Update)
133. **Updates exam title and duration** -- Not tested.

134. **Replaces questions (deletes old, creates new)** -- ExamService lines 66-84. Not tested.

135. **Non-instructor gets 403** -- Not tested.

#### DELETE /api/exams/{exam} (Instructor Destroy)
136. **Deletes exam successfully** -- Not tested.

137. **Non-instructor gets 403** -- Not tested.

#### POST /api/exams/{exam}/start
138. **Returns existing unsubmitted attempt (idempotent)** -- ExamService lines 98-104. Not tested.

139. **Creates new attempt when no unsubmitted exists** -- Not tested (the PreExamGatingTest does test start, but not the idempotency branch).

140. **Non-existent student record causes firstOrFail (404)** -- ExamController line 132. Not tested.

#### POST /api/attempts/{attempt}/submit
141. **Essay questions auto-grant full marks if answered** -- ExamService lines 147-150. Not tested.

142. **Essay questions give 0 if answer is blank** -- ExamService line 149. Not tested.

143. **Mixed question types (multiple_choice + essay)** -- Not tested.

144. **Partial score (some correct, some wrong)** -- Not tested (only 100% and 0% tested).

145. **Validation: answers must be array, answers.*.question_id must exist** -- Not tested.

#### GET /api/attempts/{attempt}/result
146. **Returns answers with question.choices loaded** -- ExamController line 154. Not tested independently.

#### GET /api/my-attempts
147. **Returns `data: []` when no student record** -- ExamController lines 162-164. Not tested.

148. **Only returns submitted attempts (submitted_at not null)** -- ExamController line 168. Not tested.

149. **Includes exam.lecture.section.course relations** -- ExamController line 166. Not tested.

#### ExamService Unit Tests
150. **`gradeAttempt` with totalPoints=0 returns 0** -- ExamService lines 163-165. Not tested.

151. **`gradeAttempt` partial score calculation** -- ExamService lines 140-167. Not tested.

152. **`getStudentResult` returns latest submitted attempt** -- ExamService lines 170-178. Not tested.

153. **`getStudentResult` returns null when no submitted attempt** -- Not tested.

---

### G. DASHBOARD CONTROLLER & SERVICE

**File: `src/app/Http/Controllers/Api/DashboardController.php` + `src/app/Services/DashboardService.php`**

**Already tested:** Nothing for this controller.

**MISSING SCENARIOS:**

#### GET /api/dashboard/student
154. **Returns stats: enrollments_count, active_enrollments, completed_lectures, total_watch_minutes** -- DashboardController lines 76-93. Not tested.

155. **Returns default zeros when no student record** -- DashboardController lines 81-87. Not tested.

156. **Returns `average_exam_score`** -- DashboardService line 153. Not tested.

157. **Returns `completed_courses` (dynamic calculation)** -- DashboardService lines 118-142. Not tested.

#### GET /api/dashboard/instructor
158. **Returns courses stats (total, published, draft)** -- DashboardService lines 18-22. Not tested.

159. **Returns students stats (total, active, recent_enrollments)** -- DashboardService lines 24-26, 40-42. Not tested.

160. **Returns revenue (sum of course prices)** -- DashboardService line 28. Not tested.

161. **Returns content (total_lectures)** -- DashboardService lines 30-33. Not tested.

162. **Returns pending_enrollments (manual source, active status)** -- DashboardService lines 35-38. Not tested.

#### GET /api/dashboard/instructor/courses
163. **Returns courses with sections_count, enrollments_count, lectures_count** -- DashboardService lines 66-71. Not tested.

#### GET /api/dashboard/instructor/recent-enrollments
164. **Returns enrollments with student.user and course** -- DashboardService lines 73-81. Not tested.

165. **Limited to 10 results** -- DashboardService line 81. Not tested.

#### GET /api/dashboard/instructor/course-performance
166. **Returns top 5 published courses by enrollment** -- DashboardService lines 84-100. Not tested.

167. **Only published courses included** -- DashboardService line 88. Not tested.

168. **Correct data structure (id, title, status, price, counts)** -- Not tested.

#### GET /api/dashboard/instructor/notifications
169. **Returns notifications for instructor, limited to 10** -- DashboardService lines 103-108. Not tested.

#### GET /api/instructor/students
170. **Returns students enrolled in instructor's courses with user relation** -- DashboardController lines 67-73. Not tested.

171. **Instructor only (role:instructor middleware)** -- Not tested.

172. **Empty list when instructor has no courses** -- Not tested.

---

### H. MISC CONTROLLER

**File: `src/app/Http/Controllers/Api/MiscController.php`**

**Already tested:** Nothing.

173. **GET /api/governorates -- returns governorates sorted by name** -- Not tested.

174. **GET /api/governorates -- returns only id and name** -- Not tested.

175. **GET /api/governorates -- empty table returns empty data array** -- Not tested.

176. **GET /api/grade-levels -- returns grade levels sorted by sort_order** -- Not tested.

177. **GET /api/grade-levels -- returns id, name, sort_order** -- Not tested.

178. **GET /api/grade-levels -- empty table returns empty data array** -- Not tested.

---

### I. MIDDLEWARE

**File: `src/app/Http/Middleware/CheckEnrollment.php`, `CheckUserStatus.php`, `CheckFilamentRole.php`**

**Already tested:** CheckEnrollment (indirectly via PreExamGatingTest blocking).

**MISSING SCENARIOS:**

#### CheckUserStatus
179. **Rejects non-active user with 403 and Arabic message** -- CheckUserStatus lines 15-18. Never tested. This middleware is applied as `user.active` on all authenticated routes.

180. **Allows active user to pass through** -- Never tested.

#### CheckEnrollment
181. **Allows course instructor to pass** -- CheckEnrollment line 31. Not tested directly.

182. **Allows super_admin to pass** -- CheckEnrollment line 36. Not tested.

183. **Allows assigned assistant to pass** -- CheckEnrollment lines 39-46. Not tested.

184. **Returns 403 when lecture has no course_id** -- CheckEnrollment line 26. Not tested.

185. **Returns 403 when student has no enrollment AND no entitlement** -- CheckEnrollment lines 68-69. Not tested directly (only indirectly in PreExamGatingTest).

186. **Blocks lecture access when blocked by preceding exam** -- CheckEnrollment lines 73-76 with `lecture_access` type. The PreExamGatingTest tests `video` type but not `lecture_access` type.

#### CheckFilamentRole
187. **Rejects unauthenticated user (403)** -- CheckFilamentRole lines 15-17. Not tested.

188. **Rejects user without required role (403)** -- CheckFilamentRole lines 27-29. Not tested.

189. **Allows user with any of the specified roles** -- CheckFilamentRole lines 19-25. Not tested.

---

### J. VIDEO ACCESS SERVICE (Full Unit Coverage)

**File: `src/app/Services/VideoAccessService.php`**

**Already tested:** `isBlockedByExam` for blocking/non-blocking, `canAccess` for free course enrollment.

**MISSING SCENARIOS:**

190. **`canAccess` for super_admin -- always returns true** -- VideoAccessService line 19. Not tested.

191. **`canAccess` for admin -- always returns true** -- VideoAccessService line 19. Not tested.

192. **`canAccess` for instructor -- own course returns true** -- VideoAccessService lines 25-26. Not tested.

193. **`canAccess` for instructor -- other instructor's course returns false** -- Not tested.

194. **`canAccess` for assistant -- assigned course returns true** -- VideoAccessService lines 30-35. Not tested.

195. **`canAccess` for assistant -- unassigned course returns false** -- Not tested.

196. **`canAccess` for student with null `expires_at` entitlement (permanent) returns true** -- VideoAccessService lines 46-49 (the `whereNull('expires_at')` branch). Not tested.

197. **`canAccess` for student with valid entitlement but blocked by exam returns false** -- VideoAccessService lines 53-56. Not tested.

198. **`canAccess` for non-student user without any role returns false** -- VideoAccessService line 77. Not tested.

199. **`canAccess` for student enrolled in free course but blocked by exam returns false** -- VideoAccessService lines 69-71. Not tested.

200. **`canAccess` for student NOT enrolled in free course with no entitlement returns false** -- VideoAccessService line 77. Not tested.

201. **`isBlockedByExam` for `exam`/`assignment` item type** -- VideoAccessService lines 107-112. Not tested (only `video` type tested).

202. **`isBlockedByExam` when student record not found -- returns true (blocked by default)** -- VideoAccessService line 102. Not tested.

203. **`isBlockedByExam` for admin role -- returns false** -- VideoAccessService lines 86-88. Not tested.

204. **`isBlockedByExam` for instructor own course -- returns false** -- VideoAccessService lines 96-98. Not tested.

205. **`isBlockedByExam` across sections (exam in earlier section)** -- VideoAccessService line 138. Not tested.

206. **`isBlockedByExam` within same section, different lecture** -- VideoAccessService lines 140-142. Not tested.

207. **`isBlockedByExam` -- exam does not block itself** -- VideoAccessService lines 122-124. Not tested.

#### Token Methods
208. **`generateSignedToken` creates encrypted payload with user_id, lecture_id, ip, expires_at** -- Not tested.

209. **`validateToken` with valid token returns true** -- Not tested.

210. **`validateToken` with expired token returns false** -- VideoAccessService lines 199-201. Not tested.

211. **`validateToken` with wrong lecture_id returns false** -- VideoAccessService lines 204-206. Not tested.

212. **`validateToken` with wrong IP returns false** -- VideoAccessService lines 209-211. Not tested.

213. **`validateToken` with deleted user returns false** -- VideoAccessService lines 214-217. Not tested.

214. **`validateToken` with inactive user returns false** -- VideoAccessService line 216. Not tested.

215. **`validateToken` with garbage/corrupt token returns false (exception caught)** -- VideoAccessService lines 222-224. Not tested.

---

### K. GRANT ENTITLEMENT SERVICE (Edge Cases)

**File: `src/app/Services/GrantEntitlementService.php`**

**Already tested:** Single product, section product, bundle.

**MISSING SCENARIOS:**

216. **When `order.purchasable` is null, returns early** -- GrantEntitlementService lines 15-17. Not tested.

217. **Product with `access_duration_days=0` or null -- `expires_at` is null (permanent)** -- GrantEntitlementService lines 34-36. Not tested.

218. **Multiple orders for same student + same product (updateOrCreate behavior)** -- Not tested. Does it update the existing entitlement or create a new one?

219. **Product sellable is a Course -- resolves all lectures in the course** -- Product.resolveLectureIds for Course type. Not tested.

220. **Empty bundle (no products) -- no entitlements created** -- Not tested.

---

### L. PROGRESS SERVICE (Unit Tests)

**File: `src/app/Services/ProgressService.php`**

**Already tested:** Nothing at the service level.

221. **First progress update creates StudentActivity with video_progress type** -- ProgressService line 16. Not tested.

222. **Second progress update overwrites existing (UpdateOrCreate)** -- Not tested.

223. **`is_completed=true` creates video_completed StudentActivity** -- ProgressService lines 39-46. Not tested.

224. **`is_completed=false` does not create video_completed** -- Not tested.

225. **Idempotent completion: same lecture marked complete twice** -- ProgressService lines 33-37 check `$wasCompleted`. Not tested.

226. **StudentStatistic created via firstOrCreate** -- ProgressService line 31. Not tested.

227. **`completed_lectures` incremented on first completion** -- ProgressService line 47. Not tested.

228. **`total_watch_minutes` incremented by lecture duration** -- ProgressService line 50. Not tested.

229. **Default 10 minutes when lecture duration is null** -- ProgressService line 49. Not tested.

230. **`last_activity_at` updated** -- ProgressService line 53. Not tested.

---

### M. ENROLLMENT SERVICE (Unit Tests)

**File: `src/app/Services/EnrollmentService.php`**

**Already tested:** Nothing at the service level.

231. **`enrollStudent` creates enrollment with correct status, source, started_at** -- Not tested.

232. **`enrollStudent` is idempotent (firstOrCreate)** -- Not tested.

233. **`enrollByUserId` throws when user has no student record** -- Not tested.

234. **`revokeEnrollment` sets status to 'suspended'** -- Not tested.

235. **`revokeEnrollment` returns false when enrollment doesn't exist** -- Not tested.

236. **`getCourseEnrollments` includes student.user** -- Not tested.

---

### N. COURSE SERVICE (Unit Tests)

**File: `src/app/Services/CourseService.php`**

237. **`listPublished` excludes non-published courses** -- Not tested.

238. **`listPublished` with search filter** -- Not tested.

239. **`listPublished` returns paginated results** -- Not tested.

240. **`findById` loads relations** -- Not tested.

241. **`create` returns created course** -- Not tested.

242. **`update` returns fresh course** -- Not tested.

243. **`delete` returns boolean** -- Not tested.

244. **`getInstructorCourses` returns courses with counts** -- Not tested.

---

### O. DASHBOARD SERVICE (Unit Tests)

245. **`getInstructorStats` returns correct structure with all keys** -- Not tested.

246. **`getInstructorStats` with zero courses** -- Not tested.

247. **`getInstructorStats` counts only active enrollments for active students** -- Not tested.

248. **`getInstructorCoursePerformance` limits to 5 courses** -- Not tested.

249. **`getStudentStats` counts completed courses dynamically** -- Not tested.

250. **`getStudentStats` reads from student_statistics table** -- Not tested.

---

### P. NOTIFICATION SERVICE

251. **`send` creates notification with correct user_id, title, body** -- Tested in BackgroundJobsTest, but no edge cases tested.

252. **`send` returns Notification model instance** -- Not tested.

---

### Q. CODE GENERATOR SERVICE

**File: `src/app/Services/CodeGeneratorService.php`**

253. **`generateStudentCode` includes grade prefix (e.g., ST3)** -- Not tested.

254. **`generateStudentCode` uses 'X' when no grade level** -- CodeGeneratorService line 14. Not tested.

255. **`generateAssistantCode` uses 'TA' prefix** -- Not tested.

256. **`generateCourseCode` uses 'CR' prefix** -- Not tested.

257. **Generated codes are unique** -- Not tested.

258. **Fallback code after 100 collisions** -- CodeGeneratorService lines 44-52. Not tested.

---

### R. MODELS (Boot Events & Relationships)

259. **Course auto-generates `course_code` on creation** -- Course model boot, line 42. Not tested.

260. **Student auto-generates `student_code` on creation** -- Student model boot, line 50. Not tested.

261. **Student `student_code` includes grade prefix** -- CodeGeneratorService integration. Not tested.

262. **Lecture `saved` event dispatches ProcessVideoHLS when video_path changes** -- Lecture model line 49-57. Partially tested in BackgroundJobsTest but only for creation, not for update.

263. **Lecture `saved` event re-dispatches when video status is 'failed'** -- Lecture model line 51: `$isFailed`. Not tested.

264. **Course `sections()` ordered by sort_order** -- Not tested.

265. **CourseSection `lectures()` ordered by sort_order** -- Not tested.

266. **Product `resolveLectureIds` for Lecture type** -- Not tested at model level.

267. **Product `resolveLectureIds` for CourseSection type** -- Not tested at model level.

268. **Product `resolveLectureIds` for Course type** -- Not tested at model level.

269. **Product `resolveLectureIds` for unknown type returns empty collection** -- Not tested.

270. **Bundle `resolveLectureIds` aggregates from all products** -- Not tested at model level.

---

### S. FORM REQUEST VALIDATION

271. **RegisterRequest: password min:8 enforced** -- Not tested.

272. **RegisterRequest: password confirmed enforced** -- Not tested.

273. **RegisterRequest: email format validation** -- Not tested.

274. **RegisterRequest: gender in:male,female validation** -- Not tested.

275. **RegisterRequest: governorate_id exists validation** -- Not tested.

276. **RegisterRequest: grade_level_id exists validation** -- Not tested.

277. **LoginRequest: TurnstileRule validation** -- Not tested.

278. **StoreCourseRequest: authorize() blocks non-instructor** -- Not tested.

279. **StoreCourseRequest: status in:draft,published,archived** -- Not tested.

---

### T. EDGE CASES & INTEGRATION SCENARIOS

280. **Same student purchases same product twice -- updateOrCreate behavior** -- Does it update expires_at or create duplicate? Not tested.

281. **Entitlement with `expires_at` exactly `now()` -- edge of expiry** -- Not tested.

282. **Student with both enrollment AND entitlement for same course** -- Not tested (both paths should grant access).

283. **Free course: student enrolled but no entitlement needed for lecture access** -- Partially tested, but not for the full HTTP flow.

284. **Exam attempt for non-existent exam -- 404 from route model binding** -- Not tested.

285. **Submit attempt for already-submitted attempt** -- Not tested (what happens if attempt.submitted_at is already set?).

286. **Result for non-existent attempt -- 404** -- Not tested.

287. **Multiple blocking exams in the same course** -- Not tested (only one blocking exam tested).

288. **Blocking exam in a later section does NOT block earlier lectures** -- Not tested.

289. **Enrollment status transitions (active -> suspended -> re-enrolled)** -- Not tested.

290. **Course with zero sections and zero lectures** -- Not tested.

291. **Course with sections but no lectures in any section** -- Not tested.

292. **Order for a course-type product (not lecture/section)** -- Not tested.

293. **Exam with true_false question type** -- ExamController validation allows `true_false`. Not tested.

294. **Exam with `sort_order` field** -- Exam model has `sort_order`. Not tested via API.

295. **Exam with `pass_percentage` -- how it interacts with score check** -- ExamService line 159: `where('score', '>=', $exam->pass_percentage)`. Only tested with exact score=100 or score=0. Not tested with pass_percentage=70 and score=80.

---

## SECTION 3: SUMMARY BY COVERAGE LEVEL

### Completely Uncovered Controllers (0 tests):
- **ProductController** (4 endpoints, 0 tests)
- **MiscController** (2 endpoints, 0 tests)
- **DashboardController** (7 endpoints, 0 tests)

### Heavily Under-tested Controllers:
- **EnrollmentController** (6 endpoints, only 2 tangentially tested)
- **ExamController** (8 endpoints, only 4 partially tested)
- **CourseController** (14 endpoints, only 4 partially tested)
- **OrderController** (1 endpoint, partially tested)

### Completely Uncovered Services (0 unit tests):
- **CourseService** (0/7 methods)
- **DashboardService** (0/7 methods)
- **EnrollmentService** (0/8 methods)
- **ProgressService** (0/1 method)

### Uncovered Middleware:
- **CheckUserStatus** -- 0 tests
- **CheckFilamentRole** -- 0 tests
- **CheckEnrollment** -- 0 direct tests

### Priority Recommendations (Highest Impact First):
1. **Dashboard endpoints** -- Entirely untested, contain complex aggregation logic
2. **Product/Bundle listing and detail** -- Core commerce endpoints with zero coverage
3. **Section/Lecture CRUD** -- Core content management with zero coverage
4. **Enrollment flow (enroll/purchase/revoke)** -- Critical business logic
5. **VideoAccessService token methods** -- Security-critical (generateSignedToken, validateToken)
6. **CheckUserStatus middleware** -- Applied to all authenticated routes
7. **Exam CRUD (store/update/destroy)** -- Instructor-facing, zero coverage
8. **ProgressService** -- Tracks student learning, zero coverage
9. **Login by student_code/phone** -- Alternative login paths never tested
10. **CodeGeneratorService** -- Auto-generated codes never validated
11. **Course search and pagination** -- Public-facing features
12. **Edge cases in grading** -- Essay questions, partial scores, zero-total-points