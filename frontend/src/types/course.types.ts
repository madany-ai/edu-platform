import type { Instructor } from "./user.types";

export interface Course {
  id: string;
  title: string;
  description: string;
  price: number;
  thumbnail: string | null;
  status: "draft" | "published" | "archived";
  instructor: Instructor | null;
  sections_count?: number;
  students_count?: number;
  sections?: CourseSection[];
  created_at: string;
  updated_at: string;
}

export interface CourseSection {
  id: string;
  title: string;
  sort_order: number;
  lectures?: Lecture[];
}

export interface Lecture {
  id: string;
  title: string;
  description: string | null;
  duration: number;
  sort_order: number;
  video?: LectureVideo;
  files?: LectureFile[];
  section?: CourseSection;
}

export interface LectureVideo {
  id: string;
  bunny_video_id?: string;
  duration: number;
  status?: string;
  video_path?: string;
}

export interface LectureFile {
  id: string;
  type: string;
  file_path: string;
}
