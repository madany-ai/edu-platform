import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderHook, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import React from "react";
import { useStudentDashboard, useInstructorDashboard } from "../useDashboard";
import { dashboardService } from "@/services/dashboard.service";

vi.mock("@/services/dashboard.service", () => ({
  dashboardService: {
    getStudentDashboard: vi.fn(),
    getInstructorDashboard: vi.fn(),
    getInstructorCourses: vi.fn(),
    getNotifications: vi.fn(),
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

describe("useDashboard hooks", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe("useStudentDashboard", () => {
    it("should fetch student dashboard stats", async () => {
      const mockStats = {
        enrollments_count: 5,
        active_enrollments: 3,
        completed_lectures: 10,
        total_watch_minutes: 120,
        average_exam_score: 85,
      };

      vi.mocked(dashboardService.getStudentDashboard).mockResolvedValue(mockStats);

      const { result } = renderHook(() => useStudentDashboard(), {
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(result.current.isSuccess).toBe(true);
      });

      expect(result.current.data).toEqual(mockStats);
    });
  });

  describe("useInstructorDashboard", () => {
    it("should fetch instructor dashboard stats", async () => {
      const mockStats = {
        courses: { total: 10, published: 8, draft: 2 },
        students: { total: 100, active: 80, recent_enrollments: 15 },
        revenue: { total: 5000 },
        content: { total_lectures: 50 },
        pending_enrollments: 5,
      };

      vi.mocked(dashboardService.getInstructorDashboard).mockResolvedValue(mockStats);

      const { result } = renderHook(() => useInstructorDashboard(), {
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(result.current.isSuccess).toBe(true);
      });

      expect(result.current.data).toEqual(mockStats);
    });
  });
});
