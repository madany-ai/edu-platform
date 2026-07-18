import { useQuery } from "@tanstack/react-query";
import { dashboardService } from "@/services/dashboard.service";

export function useStudentDashboard() {
  return useQuery({
    queryKey: ["dashboard", "student"],
    queryFn: () => dashboardService.getStudentDashboard(),
    refetchOnWindowFocus: true,
  });
}

export function useInstructorDashboard() {
  return useQuery({
    queryKey: ["dashboard", "instructor"],
    queryFn: () => dashboardService.getInstructorDashboard(),
    refetchOnWindowFocus: true,
  });
}

export function useInstructorCourses() {
  return useQuery({
    queryKey: ["dashboard", "instructor", "courses"],
    queryFn: () => dashboardService.getInstructorCourses(),
  });
}

export function useInstructorRecentEnrollments() {
  return useQuery({
    queryKey: ["dashboard", "instructor", "recent-enrollments"],
    queryFn: () => dashboardService.getInstructorRecentEnrollments(),
  });
}

export function useInstructorCoursePerformance() {
  return useQuery({
    queryKey: ["dashboard", "instructor", "course-performance"],
    queryFn: () => dashboardService.getInstructorCoursePerformance(),
  });
}

export function useInstructorNotifications() {
  return useQuery({
    queryKey: ["dashboard", "instructor", "notifications"],
    queryFn: () => dashboardService.getInstructorNotifications(),
  });
}

export function useInstructorStudents() {
  return useQuery({
    queryKey: ["dashboard", "instructor", "students"],
    queryFn: () => dashboardService.getInstructorStudents(),
  });
}
