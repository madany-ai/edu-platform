import { useQuery } from "@tanstack/react-query";
import { courseService, type CoursesParams } from "@/services/course.service";

export function useCourses(params?: CoursesParams) {
  return useQuery({
    queryKey: ["courses", params],
    queryFn: () => courseService.getAll(params),
  });
}

export function useCourse(id: string, enabled = true) {
  return useQuery({
    queryKey: ["course", id],
    queryFn: () => courseService.getById(id),
    enabled: !!id && enabled,
  });
}

export function useLecture(lectureId: string, enabled = true) {
  return useQuery({
    queryKey: ["lecture", lectureId],
    queryFn: () => courseService.getLecture(lectureId),
    enabled: !!lectureId && enabled,
  });
}
