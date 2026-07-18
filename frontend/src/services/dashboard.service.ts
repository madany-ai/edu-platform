import api from "./api.client";
import type {
  StudentDashboard,
  InstructorDashboardStats,
  CoursePerformance,
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

  getInstructorCourses: async (): Promise<PaginatedResponse<CoursePerformance>> => {
    const { data } = await api.get<PaginatedResponse<CoursePerformance>>("/dashboard/instructor/courses");
    return data;
  },

  getInstructorRecentEnrollments: async (): Promise<DashboardNotification[]> => {
    const { data } = await api.get<DashboardNotification[]>("/dashboard/instructor/recent-enrollments");
    return data;
  },

  getInstructorCoursePerformance: async (): Promise<CoursePerformance[]> => {
    const { data } = await api.get<CoursePerformance[]>("/dashboard/instructor/course-performance");
    return data;
  },

  getInstructorNotifications: async (): Promise<DashboardNotification[]> => {
    const { data } = await api.get<DashboardNotification[]>("/dashboard/instructor/notifications");
    return data;
  },

  getInstructorStudents: async (): Promise<{ id: string; name: string; email: string; enrolled_at: string }[]> => {
    const { data } = await api.get<{ id: string; name: string; email: string; enrolled_at: string }[]>("/dashboard/instructor/students");
    return data;
  },

  getNotifications: async (): Promise<DashboardNotification[]> => {
    const { data } = await api.get<DashboardNotification[]>("/notifications");
    return data;
  },
};
