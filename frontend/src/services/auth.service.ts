import api from "./api.client";
import type { User } from "@/types";

export interface LoginPayload {
  email: string;
  password: string;
}

export interface LoginResponse {
  user: User;
  token: string;
}

export interface RegisterPayload {
  email: string;
  password: string;
  password_confirmation: string;
  first_name: string;
  second_name: string;
  third_name: string;
  last_name: string;
  phone: string;
  father_phone: string;
  mother_phone: string;
  guardian_job: string;
  gender: "male" | "female";
  birth_date: string;
  governorate_id: string;
  grade_level_id: string;
}

export interface RegisterResponse {
  user: User;
  message: string;
}

export const authService = {
  login: async (payload: LoginPayload): Promise<LoginResponse> => {
    const { data } = await api.post<LoginResponse>("/auth/login", payload);
    return data;
  },

  register: async (payload: RegisterPayload): Promise<RegisterResponse> => {
    const { data } = await api.post<RegisterResponse>("/auth/register", payload);
    return data;
  },

  logout: async (): Promise<void> => {
    await api.post("/auth/logout");
  },

  me: async (): Promise<User> => {
    const { data } = await api.get<User>("/auth/me");
    return data;
  },
};
