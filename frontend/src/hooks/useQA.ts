import { useRef, useEffect } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { qaService, type QAPaginationParams } from "@/services/qa.service";
import type { StoreQuestionPayload, StoreReplyPayload } from "@/types/qa.types";
import { toast } from "sonner";

const QA_POLL_INTERVAL = 15_000;

export function useLectureQuestions(lectureId: string, params?: QAPaginationParams, enabled = true) {
  return useQuery({
    queryKey: ["lecture-questions", lectureId, params],
    queryFn: () => qaService.getLectureQuestions(lectureId, params),
    enabled: !!lectureId && enabled,
    refetchInterval: QA_POLL_INTERVAL,
  });
}

export function useQuestion(questionId: string, enabled = true) {
  return useQuery({
    queryKey: ["question", questionId],
    queryFn: () => qaService.getQuestion(questionId),
    enabled: !!questionId && enabled,
    refetchInterval: QA_POLL_INTERVAL,
  });
}

export function useMyQuestions(params?: QAPaginationParams) {
  return useQuery({
    queryKey: ["my-questions", params],
    queryFn: () => qaService.getMyQuestions(params),
    refetchInterval: QA_POLL_INTERVAL,
  });
}

export function usePostQuestion(lectureId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: StoreQuestionPayload) => qaService.postQuestion(lectureId, payload),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ["lecture-questions", lectureId] });
      queryClient.invalidateQueries({ queryKey: ["my-questions"] });
      toast.success(data.message);
    },
    onError: (error: any) => {
      const message = error?.response?.data?.message || "حدث خطأ أثناء نشر السؤال";
      toast.error(message);
    },
  });
}

export function useReplyToQuestion(questionId: string, lectureId?: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: StoreReplyPayload) => qaService.replyToQuestion(questionId, payload),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ["question", questionId] });
      if (lectureId) {
        queryClient.invalidateQueries({ queryKey: ["lecture-questions", lectureId] });
      } else {
        queryClient.invalidateQueries({ queryKey: ["lecture-questions"] });
      }
      queryClient.invalidateQueries({ queryKey: ["my-questions"] });
      toast.success(data.message);
    },
    onError: (error: any) => {
      const message = error?.response?.data?.message || "حدث خطأ أثناء إضافة الرد";
      toast.error(message);
    },
  });
}

export function useQAReplyTracker(questions: { id: string; replies_count: number }[]) {
  const prevCountsRef = useRef<Record<string, number>>({});

  useEffect(() => {
    const prev = prevCountsRef.current;
    for (const q of questions) {
      const oldCount = prev[q.id];
      if (oldCount !== undefined && q.replies_count > oldCount) {
        const diff = q.replies_count - oldCount;
        toast.info(`رد جديد على سؤالك (${diff} رد${diff > 1 ? "ود" : ""})`, {
          description: "سيتم تحديث القائمة تلقائياً",
          duration: 5000,
        });
      }
      prev[q.id] = q.replies_count;
    }
  }, [questions]);
}

export function useDeleteQuestion(lectureId?: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (questionId: string) => qaService.deleteQuestion(questionId),
    onSuccess: (data) => {
      if (lectureId) {
        queryClient.invalidateQueries({ queryKey: ["lecture-questions", lectureId] });
      } else {
        queryClient.invalidateQueries({ queryKey: ["lecture-questions"] });
      }
      queryClient.invalidateQueries({ queryKey: ["my-questions"] });
      toast.success(data.message);
    },
    onError: (error: any) => {
      const message = error?.response?.data?.message || "حدث خطأ أثناء حذف السؤال";
      toast.error(message);
    },
  });
}

export function useDeleteReply(questionId: string, lectureId?: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (replyId: string) => qaService.deleteReply(replyId),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ["question", questionId] });
      if (lectureId) {
        queryClient.invalidateQueries({ queryKey: ["lecture-questions", lectureId] });
      } else {
        queryClient.invalidateQueries({ queryKey: ["lecture-questions"] });
      }
      queryClient.invalidateQueries({ queryKey: ["my-questions"] });
      toast.success(data.message);
    },
    onError: (error: any) => {
      const message = error?.response?.data?.message || "حدث خطأ أثناء حذف الرد";
      toast.error(message);
    },
  });
}
