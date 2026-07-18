import { describe, it, expect, vi, beforeEach } from "vitest";
import { dashboardService } from "@/services/dashboard.service";
import api from "@/services/api.client";

vi.mock("@/services/api.client", () => ({
  default: {
    get: vi.fn(),
  },
}));

describe("dashboardService", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe("getStudentDashboard", () => {
    it("should return student dashboard stats", async () => {
      const mockStats = {
        enrollments_count: 5,
        active_enrollments: 3,
        completed_lectures: 10,
        total_watch_minutes: 120,
        average_exam_score: 85,
      };

      vi.mocked(api.get).mockResolvedValue({ data: mockStats });

      const result = await dashboardService.getStudentDashboard();

      expect(api.get).toHaveBeenCalledWith("/dashboard/student");
      expect(result).toEqual(mockStats);
    });
  });

  describe("getInstructorDashboard", () => {
    it("should return instructor dashboard stats", async () => {
      const mockStats = {
        courses: { total: 10, published: 8, draft: 2 },
        students: { total: 100, active: 80, recent_enrollments: 15 },
        revenue: { total: 5000 },
        content: { total_lectures: 50 },
        pending_enrollments: 5,
      };

      vi.mocked(api.get).mockResolvedValue({ data: mockStats });

      const result = await dashboardService.getInstructorDashboard();

      expect(api.get).toHaveBeenCalledWith("/dashboard/instructor");
      expect(result).toEqual(mockStats);
    });
  });

  describe("getInstructorCourses", () => {
    it("should return paginated instructor courses", async () => {
      const mockCourses = {
        data: [
          { id: "1", title: "Course 1", status: "published", price: 100, enrollments_count: 20, lectures_count: 10, sections_count: 5 },
        ],
        meta: { current_page: 1, last_page: 1, total: 1 },
      };

      vi.mocked(api.get).mockResolvedValue({ data: mockCourses });

      const result = await dashboardService.getInstructorCourses();

      expect(api.get).toHaveBeenCalledWith("/dashboard/instructor/courses");
      expect(result).toEqual(mockCourses);
    });
  });

  describe("getNotifications", () => {
    it("should return notifications", async () => {
      const mockNotifications = [
        { id: "1", title: "Test", body: "Body", read_at: null, created_at: "2024-01-01" },
      ];

      vi.mocked(api.get).mockResolvedValue({ data: mockNotifications });

      const result = await dashboardService.getNotifications();

      expect(api.get).toHaveBeenCalledWith("/notifications");
      expect(result).toEqual(mockNotifications);
    });
  });
});
