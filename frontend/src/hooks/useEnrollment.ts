import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { enrollmentService } from "@/services/enrollment.service";
import api from "@/services/api.client";

export function useMyEntitlements() {
  return useQuery({
    queryKey: ["entitlements", "me"],
    queryFn: async () => {
      const { data } = await api.get<{ status: string; data: any[] }>("/my-entitlements");
      return data.data;
    },
  });
}

export function useMyEnrollments() {
  return useQuery({
    queryKey: ["enrollments", "me"],
    queryFn: () => enrollmentService.getMyEnrollments(),
  });
}

export function useEnroll() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (courseId: string) => enrollmentService.enroll(courseId),
    onSuccess: (data, courseId) => {
      queryClient.invalidateQueries({ queryKey: ["enrollments", "me"] });
      queryClient.invalidateQueries({ queryKey: ["entitlements", "me"] });
      queryClient.invalidateQueries({ queryKey: ["course", courseId] });
      queryClient.invalidateQueries({ queryKey: ["dashboard", "student"] });
    },
  });
}

export function usePurchase() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (courseId: string) => enrollmentService.purchase(courseId),
    onSuccess: (data, courseId) => {
      queryClient.invalidateQueries({ queryKey: ["enrollments", "me"] });
      queryClient.invalidateQueries({ queryKey: ["entitlements", "me"] });
      queryClient.invalidateQueries({ queryKey: ["course", courseId] });
      queryClient.invalidateQueries({ queryKey: ["dashboard", "student"] });
    },
  });
}
