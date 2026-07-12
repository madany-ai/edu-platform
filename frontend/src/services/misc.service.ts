import api from "./api.client";

export interface GovernorateInfo {
  id: string;
  name: string;
}

export interface GradeLevelInfo {
  id: string;
  name: string;
  sort_order: number;
}

interface ApiResponse<T> {
  status: string;
  data: T;
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
