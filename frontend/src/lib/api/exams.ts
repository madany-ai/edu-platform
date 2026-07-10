import api from "@/lib/api";
import type { Exam, ExamAttempt } from "@/lib/types";

export async function getLectureExam(lectureId: number): Promise<Exam | null> {
  try {
    const { data } = await api.get(`/lectures/${lectureId}/exam`);
    return data;
  } catch {
    return null;
  }
}

export async function startExamAttempt(examId: number): Promise<ExamAttempt> {
  const { data } = await api.post(`/exams/${examId}/start`);
  return data;
}

export async function submitAttempt(
  attemptId: number,
  answers: { question_id: number; answer: string }[],
): Promise<ExamAttempt> {
  const { data } = await api.post(`/attempts/${attemptId}/submit`, { answers });
  return data;
}

export async function getAttemptResult(attemptId: number): Promise<ExamAttempt> {
  const { data } = await api.get(`/attempts/${attemptId}/result`);
  return data;
}
