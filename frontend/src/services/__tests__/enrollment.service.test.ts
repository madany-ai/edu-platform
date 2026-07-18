import { describe, it, expect, vi, beforeEach } from "vitest";
import { enrollmentService } from "@/services/enrollment.service";
import api from "@/services/api.client";

vi.mock("@/services/api.client", () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}));

describe("enrollmentService", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe("getMyEnrollments", () => {
    it("should return paginated enrollments", async () => {
      const mockResponse = {
        data: [
          { id: "1", course_id: "c1", status: "active" },
          { id: "2", course_id: "c2", status: "completed" },
        ],
        meta: { current_page: 1, last_page: 1, total: 2 },
      };

      vi.mocked(api.get).mockResolvedValue({ data: mockResponse });

      const result = await enrollmentService.getMyEnrollments();

      expect(api.get).toHaveBeenCalledWith("/my-enrollments");
      expect(result).toEqual(mockResponse);
    });
  });

  describe("enroll", () => {
    it("should enroll in a course and return enrollment", async () => {
      const mockEnrollment = {
        id: "1",
        course_id: "c1",
        status: "active",
        created_at: "2024-01-01",
      };

      vi.mocked(api.post).mockResolvedValue({ data: mockEnrollment });

      const result = await enrollmentService.enroll("c1");

      expect(api.post).toHaveBeenCalledWith("/courses/c1/enroll");
      expect(result).toEqual(mockEnrollment);
    });
  });

  describe("purchase", () => {
    it("should purchase a course and return enrollment", async () => {
      const mockEnrollment = {
        id: "1",
        course_id: "c1",
        status: "active",
        created_at: "2024-01-01",
      };

      vi.mocked(api.post).mockResolvedValue({ data: mockEnrollment });

      const result = await enrollmentService.purchase("c1");

      expect(api.post).toHaveBeenCalledWith("/courses/c1/purchase");
      expect(result).toEqual(mockEnrollment);
    });
  });
});
