import api, { setAccessToken } from "./api.client";
import type { User } from "@/types";
import env from "@/config/env";

export interface LoginPayload {
  email: string;
  password: string;
}

export interface LoginResponse {
  user: User;
  access_token?: string;
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
  gender: "male" | "female";
  birth_date: string;
  governorate_id: string;
  academic_year: string;
  academic_track?: string;
}

export interface RegisterResponse {
  user: User;
  message: string;
}

export interface ForgotPasswordPayload {
  email: string;
}

export interface ResetPasswordPayload {
  email: string;
  token: string;
  password: string;
  password_confirmation: string;
}

export const authService = {
  login: async (payload: LoginPayload): Promise<LoginResponse> => {
    await api.get("/sanctum/csrf-cookie", { baseURL: env.NEXT_PUBLIC_API_URL?.replace(/\/api\/?$/, "") });
    const { data } = await api.post<LoginResponse>("/auth/login", payload);
    if (data.access_token) {
      setAccessToken(data.access_token);
    }
    return data;
  },

  register: async (payload: RegisterPayload): Promise<RegisterResponse> => {
    await api.get("/sanctum/csrf-cookie", { baseURL: env.NEXT_PUBLIC_API_URL?.replace(/\/api\/?$/, "") });
    const { data } = await api.post<RegisterResponse>("/auth/register", payload);
    return data;
  },

  logout: async (): Promise<void> => {
    setAccessToken(null);
    await api.post("/auth/logout");
  },

  me: async (): Promise<User> => {
    const { data } = await api.get<User>("/auth/me");
    return data;
  },

  forgotPassword: async (payload: ForgotPasswordPayload): Promise<{ message: string }> => {
    const { data } = await api.post<{ message: string }>("/auth/forgot-password", payload);
    return data;
  },

  resetPassword: async (payload: ResetPasswordPayload): Promise<{ message: string }> => {
    const { data } = await api.post<{ message: string }>("/auth/reset-password", payload);
    return data;
  },
};
