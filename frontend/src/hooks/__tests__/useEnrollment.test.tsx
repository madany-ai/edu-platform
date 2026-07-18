import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderHook, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import React from "react";
import { useMyEnrollments, useEnroll, usePurchase } from "../useEnrollment";
import { enrollmentService } from "@/services/enrollment.service";

vi.mock("@/services/enrollment.service", () => ({
  enrollmentService: {
    getMyEnrollments: vi.fn(),
    enroll: vi.fn(),
    purchase: vi.fn(),
  },
}));

vi.mock("@/services/api.client", () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
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

describe("useEnrollment hooks", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe("useMyEnrollments", () => {
    it("should fetch user enrollments", async () => {
      const mockEnrollments = {
        data: [
          { id: "1", course_id: "c1", status: "active" },
        ],
        meta: { current_page: 1, last_page: 1, total: 1 },
      };

      vi.mocked(enrollmentService.getMyEnrollments).mockResolvedValue(mockEnrollments as any);

      const { result } = renderHook(() => useMyEnrollments(), {
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(result.current.isSuccess).toBe(true);
      });

      expect(result.current.data).toEqual(mockEnrollments);
    });
  });

  describe("useEnroll", () => {
    it("should enroll in a course and invalidate queries", async () => {
      const mockEnrollment = { id: "1", course_id: "c1", status: "active" };
      vi.mocked(enrollmentService.enroll).mockResolvedValue(mockEnrollment as any);

      const { result } = renderHook(() => useEnroll(), {
        wrapper: createWrapper(),
      });

      result.current.mutate("c1");

      await waitFor(() => {
        expect(result.current.isSuccess).toBe(true);
      });

      expect(enrollmentService.enroll).toHaveBeenCalledWith("c1");
    });
  });

  describe("usePurchase", () => {
    it("should purchase a course and invalidate queries", async () => {
      const mockEnrollment = { id: "1", course_id: "c1", status: "active" };
      vi.mocked(enrollmentService.purchase).mockResolvedValue(mockEnrollment as any);

      const { result } = renderHook(() => usePurchase(), {
        wrapper: createWrapper(),
      });

      result.current.mutate("c1");

      await waitFor(() => {
        expect(result.current.isSuccess).toBe(true);
      });

      expect(enrollmentService.purchase).toHaveBeenCalledWith("c1");
    });
  });
});
