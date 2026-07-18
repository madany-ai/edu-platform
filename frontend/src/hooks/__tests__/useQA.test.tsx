import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderHook, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import React from "react";
import { useLectureQuestions, usePostQuestion, useReplyToQuestion } from "../useQA";
import { qaService } from "@/services/qa.service";

vi.mock("@/services/qa.service", () => ({
  qaService: {
    getLectureQuestions: vi.fn(),
    postQuestion: vi.fn(),
    replyToQuestion: vi.fn(),
  },
}));

vi.mock("sonner", () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
  },
}));

const createWrapper = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
    },
  });
  return ({ children }: { children: React.ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );
};

describe("useQA hooks", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe("useLectureQuestions", () => {
    it("should fetch lecture questions", async () => {
      const mockQuestions = {
        data: [
          { id: "q1", body: "Test question", replies_count: 0 },
        ],
        meta: { current_page: 1, last_page: 1, total: 1 },
      };

      vi.mocked(qaService.getLectureQuestions).mockResolvedValue(mockQuestions as any);

      const { result } = renderHook(() => useLectureQuestions("l1"), {
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(result.current.isSuccess).toBe(true);
      });

      expect(result.current.data).toEqual(mockQuestions);
      expect(qaService.getLectureQuestions).toHaveBeenCalledWith("l1", undefined);
    });

    it("should not fetch when lectureId is empty", () => {
      const { result } = renderHook(() => useLectureQuestions(""), {
        wrapper: createWrapper(),
      });

      expect(result.current.isFetching).toBe(false);
      expect(qaService.getLectureQuestions).not.toHaveBeenCalled();
    });
  });

  describe("usePostQuestion", () => {
    it("should post a question and invalidate queries", async () => {
      const mockResponse = { message: "تم نشر السؤال بنجاح" };
      vi.mocked(qaService.postQuestion).mockResolvedValue(mockResponse as any);

      const { result } = renderHook(() => usePostQuestion("l1"), {
        wrapper: createWrapper(),
      });

      result.current.mutate({ body: "Test question" });

      await waitFor(() => {
        expect(result.current.isSuccess).toBe(true);
      });

      expect(qaService.postQuestion).toHaveBeenCalledWith("l1", { body: "Test question" });
    });
  });

  describe("useReplyToQuestion", () => {
    it("should reply to a question with lectureId", async () => {
      const mockResponse = { message: "تم إضافة الرد بنجاح" };
      vi.mocked(qaService.replyToQuestion).mockResolvedValue(mockResponse as any);

      const { result } = renderHook(() => useReplyToQuestion("q1", "l1"), {
        wrapper: createWrapper(),
      });

      result.current.mutate({ body: "Test reply" });

      await waitFor(() => {
        expect(result.current.isSuccess).toBe(true);
      });

      expect(qaService.replyToQuestion).toHaveBeenCalledWith("q1", { body: "Test reply" });
    });

    it("should reply to a question without lectureId", async () => {
      const mockResponse = { message: "تم إضافة الرد بنجاح" };
      vi.mocked(qaService.replyToQuestion).mockResolvedValue(mockResponse as any);

      const { result } = renderHook(() => useReplyToQuestion("q1"), {
        wrapper: createWrapper(),
      });

      result.current.mutate({ body: "Test reply" });

      await waitFor(() => {
        expect(result.current.isSuccess).toBe(true);
      });

      expect(qaService.replyToQuestion).toHaveBeenCalledWith("q1", { body: "Test reply" });
    });
  });
});
