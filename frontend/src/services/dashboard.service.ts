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

};
