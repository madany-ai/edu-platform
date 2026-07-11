import api from "./api.client";
import type { ApiResponse, PaginatedResponse, Course, Lecture } from "@/types";

export interface CoursesParams {
  search?: string;
  page?: number;
}

export const courseService = {
  getAll: async (params?: CoursesParams): Promise<PaginatedResponse<Course>> => {
    const { data } = await api.get<PaginatedResponse<Course>>("/courses", { params });
    return data;
  },

  getById: async (id: string): Promise<ApiResponse<Course>> => {
    const { data } = await api.get<ApiResponse<Course>>(`/courses/${id}`);
    return data;
  },

  getLecture: async (lectureId: string): Promise<Lecture> => {
    const { data } = await api.get<Lecture>(`/lectures/${lectureId}`);
    return data;
  },
};
