import api from "./api.client";
import type { ApiResponse } from "@/types";

export interface GovernorateInfo {
  id: string;
  name: string;
}

export interface GradeLevelInfo {
  id: string;
  name: string;
  sort_order: number;
}

export const miscService = {
  getGovernorates: async (): Promise<GovernorateInfo[]> => {
    const { data } = await api.get<ApiResponse<GovernorateInfo[]>>("/governorates");
    return data.data;
  },

  getGradeLevels: async (): Promise<GradeLevelInfo[]> => {
    const { data } = await api.get<ApiResponse<GradeLevelInfo[]>>("/grade-levels");
    return data.data;
  },
};
