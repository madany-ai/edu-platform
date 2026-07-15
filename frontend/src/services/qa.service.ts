import api from "./api.client";
import type { Question, QuestionReply, StoreQuestionPayload, StoreReplyPayload } from "@/types/qa.types";
import type { PaginatedResponse } from "@/types";

export interface QAPaginationParams {
  page?: number;
  per_page?: number;
}

export const qaService = {
  postQuestion: async (lectureId: string, payload: StoreQuestionPayload): Promise<{ message: string; question: Question }> => {
    const { data } = await api.post<{ message: string; question: Question }>(
      `/lectures/${lectureId}/questions`,
      payload
    );
    return data;
  },

  getLectureQuestions: async (lectureId: string, params?: QAPaginationParams): Promise<PaginatedResponse<Question>> => {
    const { data } = await api.get<PaginatedResponse<Question>>(
      `/lectures/${lectureId}/questions`,
      { params }
    );
    return data;
  },

  getQuestion: async (questionId: string): Promise<{ data: Question }> => {
    const { data } = await api.get<{ data: Question }>(`/questions/${questionId}`);
    return data;
  },

  replyToQuestion: async (questionId: string, payload: StoreReplyPayload): Promise<{ message: string; reply: QuestionReply }> => {
    const { data } = await api.post<{ message: string; reply: QuestionReply }>(
      `/questions/${questionId}/replies`,
      payload
    );
    return data;
  },

  getMyQuestions: async (params?: QAPaginationParams): Promise<PaginatedResponse<Question>> => {
    const { data } = await api.get<PaginatedResponse<Question>>("/my-questions", { params });
    return data;
  },

  deleteQuestion: async (questionId: string): Promise<{ message: string }> => {
    const { data } = await api.delete<{ message: string }>(`/questions/${questionId}`);
    return data;
  },

  deleteReply: async (replyId: string): Promise<{ message: string }> => {
    const { data } = await api.delete<{ message: string }>(`/replies/${replyId}`);
    return data;
  },
};
