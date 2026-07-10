export interface User {
  id: number;
  name: string;
  email: string;
  status: string;
  roles?: string[];
  student?: Student;
}

export interface Student {
  id: number;
  user_id: number;
  first_name: string;
  second_name: string | null;
  third_name: string | null;
  last_name: string;
  phone: string | null;
  father_phone: string | null;
  mother_phone: string | null;
  guardian_job: string | null;
  gender: string | null;
  birth_date: string | null;
  is_verified: boolean;
}

export interface Instructor {
  id: number;
  name: string;
  email: string;
}

export interface Course {
  id: number;
  title: string;
  description: string;
  price: number;
  thumbnail?: string;
  status: string;
  instructor?: Instructor;
  sections_count: number;
  students_count: number;
  sections?: CourseSection[];
  created_at: string;
}

export interface CourseSection {
  id: number;
  title: string;
  sort_order: number;
  lectures?: Lecture[];
}

export interface Lecture {
  id: number;
  title: string;
  description?: string;
  duration: number;
  sort_order: number;
  video?: LectureVideo;
  files?: LectureFile[];
}

export interface LectureVideo {
  id: number;
  bunny_video_id: string;
  duration: number;
}

export interface LectureFile {
  id: number;
  type: string;
  file_path: string;
}

export interface Enrollment {
  id: number;
  course: Course;
  status: string;
  started_at: string | null;
  expires_at: string | null;
  created_at: string;
}

export interface StudentDashboard {
  enrollments_count: number;
  active_enrollments: number;
  completed_lectures: number;
  total_watch_minutes: number;
  average_exam_score: number;
  completed_courses: number;
}

export interface InstructorDashboard {
  courses_count: number;
  total_students: number;
  total_revenue: number;
  average_rating: number;
}

export interface InstructorDashboardStats {
  courses: {
    total: number;
    published: number;
    draft: number;
  };
  students: {
    total: number;
    active: number;
    recent_enrollments: number;
  };
  revenue: {
    total: number;
  };
  content: {
    total_lectures: number;
  };
  pending_enrollments: number;
}

export interface DashboardNotification {
  id: number;
  title: string;
  body: string;
  read_at: string | null;
  created_at: string;
}

export interface CoursePerformance {
  id: number;
  title: string;
  status: string;
  price: number;
  enrollments_count: number;
  lectures_count: number;
  sections_count: number;
}

export interface Exam {
  id: number;
  lecture_id: number;
  title: string;
  duration: number;
  questions: ExamQuestion[];
}

export interface ExamQuestion {
  id: number;
  type: string;
  question: string;
  degree: number;
  choices: ExamChoice[];
}

export interface ExamChoice {
  id: number;
  answer: string;
  is_correct?: boolean;
}

export interface ExamAttempt {
  id: number;
  exam_id: number;
  student_id: number;
  score: number;
  started_at: string | null;
  submitted_at: string | null;
  answers?: ExamAnswer[];
}

export interface ExamAnswer {
  id: number;
  question_id: number;
  answer: string;
  question?: ExamQuestion;
}
