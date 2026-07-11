import type { Course } from "./course.types";

export interface Enrollment {
  id: string;
  course_id: string;
  course: Course;
  student_id: string | null;
  status: "active" | "expired" | "suspended";
  source: "manual" | "purchase";
  started_at: string | null;
  expires_at: string | null;
  created_at: string;
}
