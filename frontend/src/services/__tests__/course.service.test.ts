import { describe, it, expect, vi, beforeEach } from "vitest";
import { courseService } from "@/services/course.service";
import api from "@/services/api.client";

vi.mock("@/services/api.client", () => ({
  default: {
    get: vi.fn(),
  },
}));

describe("courseService", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe("getAll", () => {
    it("should return paginated courses", async () => {
      const mockResponse = {
        data: [
          { id: "1", title: "Course 1" },
          { id: "2", title: "Course 2" },
        ],
        meta: { current_page: 1, last_page: 1, total: 2 },
      };

      vi.mocked(api.get).mockResolvedValue({ data: mockResponse });

      const result = await courseService.getAll();

      expect(api.get).toHaveBeenCalledWith("/courses", { params: undefined });
      expect(result).toEqual(mockResponse);
    });

    it("should pass search params", async () => {
      const mockResponse = { data: [], meta: { current_page: 1, last_page: 1, total: 0 } };
      vi.mocked(api.get).mockResolvedValue({ data: mockResponse });

      await courseService.getAll({ search: "test", page: 2 });

      expect(api.get).toHaveBeenCalledWith("/courses", {
        params: { search: "test", page: 2 },
      });
    });
  });

  describe("getById", () => {
    it("should unwrap {data} layer and return course", async () => {
      const mockCourse = { id: "1", title: "Course 1" };
      vi.mocked(api.get).mockResolvedValue({ data: { data: mockCourse } });

      const result = await courseService.getById("1");

      expect(api.get).toHaveBeenCalledWith("/courses/1");
      expect(result).toEqual(mockCourse);
    });
  });

  describe("getLecture", () => {
    it("should unwrap {data} layer and return lecture", async () => {
      const mockLecture = { id: "l1", title: "Lecture 1" };
      vi.mocked(api.get).mockResolvedValue({ data: { data: mockLecture } });

      const result = await courseService.getLecture("l1");

      expect(api.get).toHaveBeenCalledWith("/lectures/l1");
      expect(result).toEqual(mockLecture);
    });
  });
});
