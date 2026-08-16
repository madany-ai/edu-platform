import axios, { type AxiosError } from "axios";
import env from "@/config/env";
import type { ApiError } from "@/types";
import { STORAGE_KEYS } from "@/lib/constants";

const api = axios.create({
  baseURL: env.NEXT_PUBLIC_API_URL,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  timeout: 30000,
});

api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError<ApiError>) => {
    const originalRequest = error.config as any;
    
    if (error.response?.status === 401 && originalRequest && !originalRequest._retry) {
      originalRequest._retry = true;
      
      if (typeof window !== "undefined") {
        const token = localStorage.getItem(STORAGE_KEYS.TOKEN);
        if (token && originalRequest.url !== "/auth/login" && originalRequest.url !== "/auth/refresh-token") {
          try {
            // Attempt to refresh the token using the existing Bearer token
            const { data } = await axios.post(
              `${env.NEXT_PUBLIC_API_URL}/auth/refresh-token`,
              {},
              {
                headers: {
                  Authorization: `Bearer ${token}`,
                  Accept: "application/json",
                },
                withCredentials: true,
              }
            );
            
            if (data?.token) {
              localStorage.setItem(STORAGE_KEYS.TOKEN, data.token);
              // Retry the original request
              return api(originalRequest);
            }
          } catch (refreshError) {
            // Refresh failed, clear everything and redirect to login
            localStorage.removeItem(STORAGE_KEYS.TOKEN);
            document.cookie = "laravel_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            document.cookie = "XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            if (window.location.pathname !== "/login") {
              window.location.href = "/login";
            }
            return Promise.reject(refreshError);
          }
        } else {
          // No token or already trying to login/refresh, just clear cookies
          localStorage.removeItem(STORAGE_KEYS.TOKEN);
          document.cookie = "laravel_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
          document.cookie = "XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
          
          if (window.location.pathname !== "/login" && error.config?.url !== "/auth/me") {
            window.location.href = "/login";
          }
        }
      }
    }
    return Promise.reject(error);
  }
);

export default api;
