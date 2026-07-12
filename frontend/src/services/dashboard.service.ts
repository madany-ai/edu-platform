import api from "./api.client";
import type {
  StudentDashboard,
  Course,
  Enrollment,
  DashboardNotification,
  PaginatedResponse,
} from "@/types";

export const dashboardService = {
  getStudentDashboard: async (): Promise<StudentDashboard> => {
    const { data } = await api.get<StudentDashboard>("/dashboard/student");
    return data;
  },

  getInstructorDashboard: async (): Promise<any> => {
    const { data } = await api.get<any>("/dashboard/instructor");
    return data;
  },

  getInstructorCourses: async (): Promise<any> => {
    const { data } = await api.get<any>("/dashboard/instructor/courses");
    return data;
  },

  getInstructorRecentEnrollments: async (): Promise<any> => {
    const { data } = await api.get<any>("/dashboard/instructor/recent-enrollments");
    return data;
  },

  getInstructorCoursePerformance: async (): Promise<any> => {
    const { data } = await api.get<any>("/dashboard/instructor/course-performance");
    return data;
  },

  getInstructorNotifications: async (): Promise<any> => {
    const { data } = await api.get<any>("/dashboard/instructor/notifications");
    return data;
  },

  getInstructorStudents: async (): Promise<any> => {
    const { data } = await api.get<any>("/dashboard/instructor/students");
    return data;
  },
};
