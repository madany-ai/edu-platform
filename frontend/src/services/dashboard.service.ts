import api from "./api.client";
import type {
  StudentDashboard,
  InstructorDashboardStats,
  Course,
  CoursePerformance,
  Enrollment,
  DashboardNotification,
  PaginatedResponse,
} from "@/types";

export const dashboardService = {
  getStudentDashboard: async (): Promise<StudentDashboard> => {
    const { data } = await api.get<StudentDashboard>("/dashboard/student");
    return data;
  },

  getInstructorDashboard: async (): Promise<InstructorDashboardStats> => {
    const { data } = await api.get<InstructorDashboardStats>("/dashboard/instructor");
    return data;
  },

  getInstructorCourses: async (): Promise<PaginatedResponse<Course>> => {
    const { data } = await api.get<PaginatedResponse<Course>>("/dashboard/instructor/courses");
    return data;
  },

  getInstructorRecentEnrollments: async (): Promise<PaginatedResponse<Enrollment>> => {
    const { data } = await api.get<PaginatedResponse<Enrollment>>(
      "/dashboard/instructor/recent-enrollments"
    );
    return data;
  },

  getInstructorCoursePerformance: async (): Promise<CoursePerformance[]> => {
    const { data } = await api.get<CoursePerformance[]>("/dashboard/instructor/course-performance");
    return data;
  },

  getInstructorNotifications: async (): Promise<DashboardNotification[]> => {
    const { data } = await api.get<DashboardNotification[]>(
      "/dashboard/instructor/notifications"
    );
    return data;
  },

  getInstructorStudents: async (): Promise<any[]> => {
    const { data } = await api.get<any[]>("/instructor/students");
    return data;
  },
};
