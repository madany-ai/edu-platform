import api from "@/lib/api";
import type { StudentDashboard, InstructorDashboard, Course } from "@/lib/types";

export async function getStudentDashboard(): Promise<StudentDashboard> {
  const { data } = await api.get("/dashboard/student");
  return data;
}

export async function getInstructorDashboard(): Promise<InstructorDashboard> {
  const { data } = await api.get("/dashboard/instructor");
  return data;
}

export async function getInstructorCourses(): Promise<Course[]> {
  const { data } = await api.get("/instructor/courses");
  return data.data;
}
