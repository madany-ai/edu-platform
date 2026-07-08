import api from "@/lib/api";
import type { Course, Category, Enrollment } from "@/lib/types";

interface CoursesParams {
  category?: string;
  level?: string;
  search?: string;
}

export async function getCourses(params?: CoursesParams): Promise<Course[]> {
  const { data } = await api.get("/courses", { params });
  return data.data;
}

export async function getCourse(slug: string): Promise<Course> {
  const { data } = await api.get(`/courses/${slug}`);
  return data.data;
}

export async function getCategories(): Promise<Category[]> {
  const { data } = await api.get("/categories");
  return data.data;
}

export async function enrollCourse(courseId: number): Promise<Enrollment> {
  const { data } = await api.post(`/courses/${courseId}/enroll`);
  return data;
}

export async function getMyEnrollments(): Promise<Enrollment[]> {
  const { data } = await api.get("/courses/my-enrollments");
  return data.data;
}
