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
  has_exam?: boolean;
  has_assignment?: boolean;
  progress?: {
    current_time?: string | number;
    is_completed?: boolean;
  };
  is_locked?: boolean;
  video_locked?: boolean;
  has_access?: boolean;
  exams?: CourseExam[];
  assignments?: CourseExam[];
}

export interface CourseExam {
  id: string;
  title: string;
  sort_order: number;
  is_blocking: boolean;
  pass_percentage: number;
  duration: number;
  latest_attempt: {
    id: string;
    score: number | null;
    submitted_at: string | null;
  } | null;
  passed: boolean;
}

export interface LectureVideo {
  id: string;
  bunny_video_id?: string;
  duration: number;
  status?: string;
  video_path?: string;
  stream_url?: string;
  stream_type?: string;
}

export interface LectureFile {
  id: string;
  type: string;
  file_path: string;
}
