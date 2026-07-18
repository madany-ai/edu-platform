import api from "./api.client";
import type { Exam, ExamAttempt } from "@/types";

export interface SubmitAnswer {
  question_id: string;
  answer: string;
}

export interface SubmitPayload {
  answers: SubmitAnswer[];
}

export interface MyAttempt {
  id: string;
  exam_id: string;
  score: number | null;
  submitted_at: string | null;
  exam: {
    id: string;
    title: string;
    is_assignment: boolean;
    total_score?: number;
    lecture: {
      id: string;
      title: string;
      section: {
        id: string;
        title: string;
        course: {
          id: string;
          title: string;
        };
      };
    };
  };
}
export const examService = {
  getLectureExam: async (lectureId: string): Promise<Exam | null> => {
    try {
      const { data } = await api.get<{ exam: Exam; latest_attempt: ExamAttempt | null }>(`/lectures/${lectureId}/exam`);
      return data.exam;
    } catch (error: unknown) {
      if (
        error &&
        typeof error === "object" &&
        "response" in error &&
        (error as { response?: { status?: number } }).response?.status === 404
      ) {
        return null;
      }
      throw error;
    }
  },

  startAttempt: async (examId: string): Promise<ExamAttempt> => {
    const { data } = await api.post<ExamAttempt>(`/exams/${examId}/start`);
    return data;
  },

  submitAttempt: async (attemptId: string, payload: SubmitPayload): Promise<ExamAttempt> => {
    const { data } = await api.post<ExamAttempt>(`/attempts/${attemptId}/submit`, payload);
    return data;
  },

  getAttemptResult: async (attemptId: string): Promise<ExamAttempt> => {
    const { data } = await api.get<ExamAttempt>(`/attempts/${attemptId}/result`);
    return data;
  },

  getMyAttempts: async (): Promise<MyAttempt[]> => {
    const { data } = await api.get<{ data: MyAttempt[] }>("/my-attempts");
    return data.data;
  },
};
