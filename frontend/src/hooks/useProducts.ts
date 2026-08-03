import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import api from "@/services/api.client";

export interface Product {
  id: string;
  instructor_id: string;
  name: string;
  sellable_id: string;
  sellable_type: string;
  price: number;
  access_duration_days: number | null;
  is_active: boolean;
  sellable?: any;
}

export interface Bundle {
  id: string;
  instructor_id: string;
  name: string;
  price: number;
  products?: Product[];
}

export function useProducts(type?: string) {
  return useQuery({
    queryKey: ["products", type],
    queryFn: async () => {
      const { data } = await api.get<{ status: string; data: Product[] }>("/products", {
        params: type ? { type } : undefined,
      });
      return data.data;
    },
  });
}

export function useBundles() {
  return useQuery({
    queryKey: ["bundles"],
    queryFn: async () => {
      const { data } = await api.get<{ status: string; data: Bundle[] }>("/bundles");
      return data.data;
    },
  });
}

export function useCreateOrder() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload: { purchasable_id: string; purchasable_type: 'product' | 'bundle', payment_gateway: 'paymob' | 'fawry' }) => {
      const { data } = await api.post<{ status: string; message: string; data: any; payment_url: string }>("/orders", payload);
      return data;
    },
    onSuccess: () => {
      // Invalidate entitlements and dashboard queries so newly purchased items are unlocked
      queryClient.invalidateQueries({ queryKey: ["enrollments", "me"] });
      queryClient.invalidateQueries({ queryKey: ["entitlements", "me"] });
      queryClient.invalidateQueries({ queryKey: ["dashboard", "student"] });
      queryClient.invalidateQueries({ queryKey: ["course"] });
      queryClient.invalidateQueries({ queryKey: ["lecture"] });
      queryClient.invalidateQueries({ queryKey: ["products"] });
      queryClient.invalidateQueries({ queryKey: ["bundles"] });
    },
  });
}
