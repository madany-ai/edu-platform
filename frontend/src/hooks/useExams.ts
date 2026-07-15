import { useQuery } from "@tanstack/react-query";
import { examService } from "@/services/exam.service";

export function useMyAttempts() {
  return useQuery({
    queryKey: ["my-attempts"],
    queryFn: () => examService.getMyAttempts(),
  });
}
