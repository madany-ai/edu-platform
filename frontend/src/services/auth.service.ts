import api from "./api.client";
import type { User } from "@/types";

export interface LoginPayload {
  email: string;
  password: string;
  "cf-turnstile-response"?: string;
}

export interface LoginResponse {
  user: User;
  token?: string;
}

export interface RegisterPayload {
  email: string;
  password: string;
  password_confirmation: string;
  "cf-turnstile-response"?: string;
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

export interface ForgotPasswordPayload {
  email: string;
  "cf-turnstile-response"?: string;
}

export interface ResetPasswordPayload {
  email: string;
  token: string;
  password: string;
  password_confirmation: string;
}

export const authService = {
  login: async (payload: LoginPayload): Promise<LoginResponse> => {
    await api.get("/sanctum/csrf-cookie");
    const { data } = await api.post<LoginResponse>("/auth/login", payload);
    return data;
  },

  register: async (payload: RegisterPayload): Promise<RegisterResponse> => {
    await api.get("/sanctum/csrf-cookie");
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

  forgotPassword: async (payload: ForgotPasswordPayload): Promise<{ message: string }> => {
    const { data } = await api.post<{ message: string }>("/auth/forgot-password", payload);
    return data;
  },

  resetPassword: async (payload: ResetPasswordPayload): Promise<{ message: string }> => {
    const { data } = await api.post<{ message: string }>("/auth/reset-password", payload);
    return data;
  },
};
