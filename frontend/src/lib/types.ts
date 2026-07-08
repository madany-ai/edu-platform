export interface User {
  id: number;
  name: string;
  email: string;
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  icon?: string;
  courses_count: number;
}

export interface Instructor {
  id: number;
  name: string;
  email: string;
}

export interface Course {
  id: number;
  title: string;
  slug: string;
  description: string;
  short_description?: string;
  price: number;
  thumbnail?: string;
  level: string;
  duration_minutes: number;
  language: string;
  category?: Category;
  instructor?: Instructor;
  lessons_count: number;
  students_count: number;
  lessons?: CourseLesson[];
  is_published: boolean;
  created_at: string;
}

export interface CourseLesson {
  id: number;
  title: string;
  duration_minutes: number;
  sort_order: number;
}

export interface Enrollment {
  id: number;
  course: Course;
  progress: number;
  completed_at: string | null;
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
