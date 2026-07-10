import api from "@/lib/api";
import type {
  StudentDashboard,
  InstructorDashboardStats,
  Enrollment,
  Course,
  CoursePerformance,
  DashboardNotification,
} from "@/lib/types";

export async function getStudentDashboard(): Promise<StudentDashboard> {
  const { data } = await api.get("/dashboard/student");
  return data;
}

export async function getInstructorDashboard(): Promise<InstructorDashboardStats> {
  const { data } = await api.get("/dashboard/instructor");
  return data;
}

export async function getInstructorCourses(): Promise<Course[]> {
  const { data } = await api.get("/dashboard/instructor/courses");
  return data.data;
}

export async function getInstructorRecentEnrollments(): Promise<Enrollment[]> {
  const { data } = await api.get("/dashboard/instructor/recent-enrollments");
  return data.data;
}

export async function getInstructorCoursePerformance(): Promise<CoursePerformance[]> {
  const { data } = await api.get("/dashboard/instructor/course-performance");
  return data;
}

export async function getInstructorNotifications(): Promise<DashboardNotification[]> {
  const { data } = await api.get("/dashboard/instructor/notifications");
  return data;
}
