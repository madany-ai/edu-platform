import api from "./api.client";
import type { Exam, ExamAttempt } from "@/types";

export interface SubmitAnswer {
  question_id: string;
  answer: string;
}

export interface SubmitPayload {
  answers: SubmitAnswer[];
}

export const examService = {
  getLectureExam: async (lectureId: string): Promise<Exam | null> => {
    try {
      const { data } = await api.get<Exam>(`/lectures/${lectureId}/exam`);
      return data;
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
};
