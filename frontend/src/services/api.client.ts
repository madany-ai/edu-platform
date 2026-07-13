import axios, { type AxiosError, type InternalAxiosRequestConfig } from "axios";
import env from "@/config/env";
import { STORAGE_KEYS } from "@/lib/constants";
import type { ApiError } from "@/types";

const api = axios.create({
  baseURL: env.NEXT_PUBLIC_API_URL,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  timeout: 30000,
});

api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    if (typeof window !== "undefined") {
      const token = localStorage.getItem(STORAGE_KEYS.TOKEN);
      if (token && config.headers) {
        config.headers.Authorization = `Bearer ${token}`;
      }
    }
    return config;
  },
  (error) => Promise.reject(error)
);

api.interceptors.response.use(
  (response) => response,
  (error: AxiosError<ApiError>) => {
    if (error.response?.status === 401) {
      if (typeof window !== "undefined" && window.location.pathname !== "/login") {
        localStorage.removeItem(STORAGE_KEYS.TOKEN);
        window.location.href = "/login";
      }
    }
    return Promise.reject(error);
  }
);

export default api;
