export interface StudentDashboard {
  enrollments_count: number;
  active_enrollments: number;
  completed_lectures: number;
  total_watch_minutes: number;
  average_exam_score: number;
  completed_courses: number;
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

export interface CoursePerformance {
  id: string;
  title: string;
  status: string;
  price: number;
  enrollments_count: number;
  lectures_count: number;
  sections_count: number;
}

export interface DashboardNotification {
  id: string;
  title: string;
  body: string;
  read_at: string | null;
  created_at: string;
}
