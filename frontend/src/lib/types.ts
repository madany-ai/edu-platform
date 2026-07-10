export interface User {
  id: number;
  name: string;
  email: string;
  status: string;
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
  completed_lessons_count: number;
  total_learning_minutes: number;
  certificates_count: number;
}

export interface InstructorDashboard {
  courses_count: number;
  total_students: number;
  total_revenue: number;
  average_rating: number;
}
