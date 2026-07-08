# LMS ERD (MVP + Scalable)

> Production White-Label LMS
>
> Laravel + PostgreSQL + Filament + Next.js

---

# Identity

## users
- id
- email
- password
- status
- last_login_at

---

## roles
- id
- name

---

## permissions
- id
- name

---

# Students

## students
- id
- user_id
- student_code
- first_name
- second_name
- third_name
- last_name
- phone
- father_phone
- mother_phone
- guardian_job
- governorate_id
- city_id
- school_id
- grade_level_id
- academic_track_id
- gender
- birth_date
- profile_image
- status 
- is_verified

---

## student_statistics
- student_id
- total_watch_minutes
- attendance_rate
- average_exam_score
- completed_courses
- completed_lectures
- last_activity_at

---

## student_activity
- id
- student_id
- type
- entity_type
- entity_id
- metadata
- created_at

---

## student_documents
- id
- student_id
- type
- file_path

---

# Academic

## courses
- id
- instructor_id
- title
- description
- thumbnail
- status
- price

---

## course_sections
- id
- course_id
- title
- sort_order

---

## lectures
- id
- section_id
- title
- description
- duration
- order

---

## lecture_videos
- id
- lecture_id
- bunny_video_id
- duration

---

## lecture_files
- id
- lecture_id
- type
- file_path

---

# Enrollment

## enrollments
- id
- student_id
- course_id
- status
- started_at
- expires_at

---

# Commerce

## products
- id
- type
- reference_id
- name
- price

---

## orders
- id
- student_id
- total
- status

---

## order_items
- id
- order_id
- product_id
- price

---

## payments
- id
- order_id
- provider
- transaction_id
- amount
- status

---

## subscriptions
- id
- student_id
- plan_id
- start_at
- end_at
- status

---

# Exams

## exams
- id
- lecture_id
- title
- duration

---

## questions
- id
- exam_id
- type
- question
- degree

---

## choices
- id
- question_id
- answer
- is_correct

---

## exam_attempts
- id
- exam_id
- student_id
- score
- started_at
- submitted_at

---

## answers
- id
- attempt_id
- question_id
- answer

---

# Assignments

## assignments
- id
- lecture_id
- title
- degree

---

## assignment_submissions
- id
- assignment_id
- student_id
- file
- score

---

# Q&A

## questions_posts
- id
- lecture_id
- student_id
- body

---

## question_replies
- id
- question_id
- user_id
- body

---

# Notifications

## notifications
- id
- user_id
- title
- body
- read_at

---

# Analytics

## activity_logs
- id
- user_id
- action
- entity
- entity_id
- metadata

---

# Media

## media
- id
- disk
- path
- mime
- size

---

# Geography

## governorates

## cities

## schools

## grade_levels

## academic_tracks

---

# Relations

```text
User
 ├── Student
 │      ├── StudentStatistics
 │      ├── StudentActivity
 │      ├── StudentDocuments
 │      ├── Enrollments
 │      ├── Orders
 │      ├── Payments
 │      ├── ExamAttempts
 │      ├── AssignmentSubmissions
 │      └── QuestionsPosts
 │
 └── Instructor
        ├── Courses
        ├── Students
        ├── Analytics
        └── Notifications

Course
 ├── Sections
 │      └── Lectures
 │              ├── Videos
 │              ├── Files
 │              ├── Exams
 │              ├── Assignments
 │              └── QuestionsPosts
 │
 └── Enrollments

Exam
 ├── Questions
 │      └── Choices
 │
 └── Attempts
        └── Answers

Order
 ├── OrderItems
 └── Payment