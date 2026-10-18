import axios, { type AxiosError } from "axios";
import env from "@/config/env";
import type { ApiError } from "@/types";

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

let memoryAccessToken: string | null = null;

export const setAccessToken = (token: string | null) => {
  memoryAccessToken = token;
};

export const getAccessToken = () => memoryAccessToken;

api.interceptors.request.use((config) => {
  if (memoryAccessToken) {
    config.headers.Authorization = `Bearer ${memoryAccessToken}`;
  }
  return config;
});


api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError<ApiError>) => {
    const originalRequest = error.config as any;
    
    if (error.response?.status === 401 && originalRequest && !originalRequest._retry) {
      originalRequest._retry = true;
      
      if (typeof window !== "undefined") {
        const isAuthEndpoint = 
          originalRequest.url?.includes("/auth/login") ||
          originalRequest.url?.includes("/auth/refresh-token") ||
          originalRequest.url?.includes("/auth/me");

        const isAuthPage = 
          window.location.pathname === "/login" || 
          window.location.pathname === "/register";

        if (!isAuthEndpoint && !isAuthPage) {
          try {
            // Attempt to refresh the token using HttpOnly Cookie (sent automatically)
            const { data } = await axios.post(
              `${env.NEXT_PUBLIC_API_URL}/auth/refresh-token`,
              {},
              {
                withCredentials: true,
                headers: { Accept: "application/json" },
              }
            );
            
            if (data?.access_token) {
              setAccessToken(data.access_token);
              // Retry the original request
              return api(originalRequest);
            }
          } catch (refreshError) {
            // Refresh failed, clear everything and redirect to login
            setAccessToken(null);
            document.cookie = "laravel_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            document.cookie = "XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            if (!isAuthPage) {
              window.location.href = "/login";
            }
            return Promise.reject(refreshError);
          }
        } else {
          // Auth endpoint or already on auth page, clean session state without looping
          setAccessToken(null);
          if (isAuthEndpoint && !originalRequest.url?.includes("/auth/me")) {
            document.cookie = "laravel_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            document.cookie = "XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
          }
          
          if (!isAuthPage && !originalRequest.url?.includes("/auth/me")) {
            window.location.href = "/login";
          }
        }
      }
    }
    return Promise.reject(error);
  }
);

export default api;
