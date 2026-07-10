import api from "@/lib/api";
import type { Course, Enrollment, Lecture } from "@/lib/types";

interface CoursesParams {
  category?: string;
  search?: string;
}

export async function getCourses(params?: CoursesParams): Promise<Course[]> {
  const { data } = await api.get("/courses", { params });
  return data.data;
}

export async function getCourse(id: string): Promise<Course> {
  const { data } = await api.get(`/courses/${id}`);
  return data.data;
}

export async function enrollCourse(courseId: number): Promise<Enrollment> {
  const { data } = await api.post(`/courses/${courseId}/enroll`);
  return data;
}

export async function getMyEnrollments(): Promise<Enrollment[]> {
  const { data } = await api.get("/my-enrollments");
  return data.data;
}

export async function purchaseCourse(courseId: number): Promise<Enrollment> {
  const { data } = await api.post(`/courses/${courseId}/purchase`);
  return data;
}

export async function getLecture(lectureId: number): Promise<Lecture> {
  const { data } = await api.get(`/lectures/${lectureId}`);
  return data;
}
