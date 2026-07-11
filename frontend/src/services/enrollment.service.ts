import api from "./api.client";
import type { ApiResponse, PaginatedResponse, Enrollment } from "@/types";

export const enrollmentService = {
  getMyEnrollments: async (): Promise<PaginatedResponse<Enrollment>> => {
    const { data } = await api.get<PaginatedResponse<Enrollment>>("/my-enrollments");
    return data;
  },

  enroll: async (courseId: string): Promise<ApiResponse<Enrollment>> => {
    const { data } = await api.post<ApiResponse<Enrollment>>(`/courses/${courseId}/enroll`);
    return data;
  },

  purchase: async (courseId: string): Promise<ApiResponse<Enrollment>> => {
    const { data } = await api.post<ApiResponse<Enrollment>>(`/courses/${courseId}/purchase`);
    return data;
  },
};
