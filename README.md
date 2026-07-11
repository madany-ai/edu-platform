# Backend (src/) -- Laravel 13



src/
├── app/
│   ├── Enums/                          # 4 enums
│   │   ├── CourseStatus.php
│   │   ├── EnrollmentSource.php
│   │   ├── EnrollmentStatus.php
│   │   └── UserStatus.php
│   ├── Filament/
│   │   ├── Pages/
│   │   │   ├── Auth/Login.php
│   │   │   └── Settings.php
│   │   ├── Resources/
│   │   │   ├── Assignments/       (AssignmentResource + Pages + RelationManagers)
│   │   │   ├── Assistants/        (AssistantResource + Pages)
│   │   │   ├── Bundles/           (BundleResource + RelationManagers)
│   │   │   ├── Courses/           (CourseResource + Pages + RelationManagers)
│   │   │   ├── Enrollments/       (EnrollmentResource + Pages)
│   │   │   ├── Exams/             (ExamResource + Pages)
│   │   │   ├── Lectures/          (LectureResource + Pages)
│   │   │   ├── Orders/            (OrderResource + Pages)
│   │   │   ├── Pricing/           (ProductResource + Pages)
│   │   │   ├── QA/                (QAResource + Pages)
│   │   │   └── Students/          (StudentResource + Pages)
│   │   └── Widgets/
│   │       ├── CoursePerformanceWidget.php
│   │       ├── InstructorStatsOverview.php
│   │       └── RecentEnrollmentsWidget.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php
│   │   │   └── Api/
│   │   │       ├── AuthController.php
│   │   │       ├── CourseController.php
│   │   │       ├── DashboardController.php
│   │   │       ├── EnrollmentController.php
│   │   │       └── ExamController.php
│   │   ├── Middleware/
│   │   │   ├── CheckEnrollment.php
│   │   │   ├── CheckFilamentRole.php
│   │   │   └── CheckUserStatus.php
│   │   ├── Requests/
│   │   │   ├── LoginRequest.php
│   │   │   ├── RegisterRequest.php
│   │   │   └── StoreCourseRequest.php
│   │   └── Resources/
│   │       ├── CourseResource.php
│   │       └── EnrollmentResource.php
│   ├── Models/                        # 30 models
│   │   ├── Answer.php, Assignment.php, AssignmentSubmission.php
│   │   ├── Bundle.php, Choice.php, City.php
│   │   ├── Course.php, CourseAssistant.php, CourseSection.php
│   │   ├── Enrollment.php, Entitlement.php
│   │   ├── Exam.php, ExamAttempt.php
│   │   ├── Governorate.php, GradeLevel.php, AcademicTrack.php
│   │   ├── Lecture.php, LectureFile.php, LectureVideo.php
│   │   ├── Notification.php, Order.php
│   │   ├── Product.php, Permission.php, Role.php
│   │   ├── Question.php, QuestionReply.php, QuestionsPost.php
│   │   ├── School.php
│   │   ├── Student.php, StudentActivity.php, StudentStatistic.php
│   │   └── User.php
│   ├── Policies/
│   │   └── CoursePolicy.php
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── Filament/AdminPanelProvider.php
│   │   └── HorizonServiceProvider.php
│   └── Services/                      # 8 services (business logic layer)
│       ├── AuthService.php
│       ├── CodeGeneratorService.php
│       ├── CourseService.php
│       ├── DashboardService.php
│       ├── EnrollmentService.php
│       ├── ExamService.php
│       ├── GrantEntitlementService.php
│       └── NotificationService.php
├── config/
├── database/
│   ├── factories/       (StudentFactory.php, UserFactory.php)
│   ├── migrations/      (22 migration files, ranging 2026-01 through 2026-07)
│   └── seeders/         (DatabaseSeeder.php)
├── resources/
│   ├── css/app.css
│   ├── js/app.js
│   └── views/
│       ├── welcome.blade.php
│       └── filament/    (hooks/head.blade.php, pages/settings.blade.php)
├── routes/
│   ├── api.php          (74 lines, 30+ routes defined)
│   ├── console.php
│   └── web.php
├── public/
│   ├── index.php
│   ├── robots.txt
│   ├── favicon.ico
│   ├── .htaccess
│   ├── css/filament/    (app.css)
│   ├── js/filament/     (tables, forms, schemas, actions, etc.)
│   └── fonts/filament/  (Inter web fonts)
├── .env                 # Live environment config (92 lines)
├── .env.example         # Template environment (65 lines)
├── .gitattributes
├── artisan
├── composer.json
├── composer.lock
├── phpstan.neon         # PHPStan level 6, includes larastan
├── package.json
├── vite.config.js       # Laravel Vite + TailwindCSS
├── README.md
└── .php/                # Bundled PHP extension .so files




# Frontend (frontend/src/) -- Next.js 16 + React 19


frontend/src/
├── app/
│   ├── layout.tsx                         # Root layout with AuthProvider
│   ├── page.tsx                           # Landing page
│   ├── globals.css                        # TailwindCSS v4 + shadcn theme vars (Tajawal font)
│   ├── (auth)/
│   │   ├── layout.tsx                     # Auth layout wrapper
│   │   ├── login/page.tsx                 # Login page
│   │   └── register/page.tsx              # 3-step registration wizard
│   ├── courses/
│   │   ├── page.tsx                       # Course catalog with search + grid
│   │   └── [id]/
│   │       ├── page.tsx                   # Course detail (sections accordion)
│   │       └── lectures/[lectureId]/
│   │           ├── page.tsx               # Lecture view (video player placeholder)
│   │           └── exam/page.tsx          # Exam taking page
│   └── dashboard/
│       ├── page.tsx                       # Student dashboard
│       └── instructor/page.tsx            # Instructor dashboard
├── components/
│   ├── ui/                                # shadcn/ui components (10 installed)
│   │   ├── avatar.tsx, badge.tsx, button.tsx
│   │   ├── card.tsx, input.tsx, label.tsx
│   │   ├── progress.tsx, select.tsx
│   │   ├── separator.tsx, skeleton.tsx
│   ├── course-card.tsx                    # Course card component
│   ├── features-section.tsx               # Landing features section
│   ├── stats-section.tsx                  # Landing stats section
│   ├── testimonials-section.tsx           # Landing testimonials
│   └── layout/
│       ├── footer.tsx
│       └── navbar.tsx
├── contexts/
│   └── auth-context.tsx                   # Auth provider (React Context)
├── lib/
│   ├── api.ts                             # Axios instance with interceptors
│   ├── utils.ts                           # cn() helper + utilities
│   ├── types.ts                           # TypeScript interfaces
│   └── api/
│       ├── courses.ts                     # Course-related API calls
│       ├── dashboard.ts                   # Dashboard API calls
│       └── exams.ts                       # Exam API calls
└── middleware.ts                           # Route protection (cookie-based, known bug)