"use client";

import { createContext, useContext, useState, useEffect, useCallback, type ReactNode } from "react";
import { useRouter } from "next/navigation";
import { authService, type LoginPayload, type RegisterPayload } from "@/services/auth.service";
import type { User } from "@/types";

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (payload: LoginPayload) => Promise<User | null>;
  register: (payload: RegisterPayload) => Promise<string>;
  logout: () => Promise<void>;
  isAuthenticated: boolean;
  isInstructor: boolean;
  isAssistant: boolean;
  isStudent: boolean;
  isAdmin: boolean;
  isSuperAdmin: boolean;
}

const AuthContext = createContext<AuthContextType | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const router = useRouter();

  const fetchUser = useCallback(async () => {
    try {
      const userData = await authService.me();
      setUser(userData);
      return userData;
    } catch {
      setUser(null);
      return null;
    }
  }, []);

  const isAuthenticated = !!user;
  const isInstructor = user?.roles?.some((r: any) => (typeof r === "string" ? r : r.name) === "instructor") ?? false;
  const isAssistant = user?.roles?.some((r: any) => (typeof r === "string" ? r : r.name) === "assistant") ?? false;
  const isStudent = user?.roles?.some((r: any) => (typeof r === "string" ? r : r.name) === "student") ?? false;
  const isAdmin = user?.roles?.some((r: any) => (typeof r === "string" ? r : r.name) === "admin") ?? false;
  const isSuperAdmin = user?.roles?.some((r: any) => (typeof r === "string" ? r : r.name) === "super_admin") ?? false;

  useEffect(() => {
    let cancelled = false;
    const load = async () => {
      const u = await fetchUser();
      if (!cancelled) {
        setLoading(false);
        if (u?.must_change_password && !window.location.pathname.includes('/change-password') && !window.location.pathname.includes('/logout')) {
          window.location.href = '/change-password';
        }
      }
    };
    load();
    return () => { cancelled = true; };
  }, [fetchUser, router]);

  // Silent background heartbeat to keep the session & token alive
  useEffect(() => {
    if (!isAuthenticated) return;
    
    // Ping the server every 15 minutes silently
    const heartbeat = setInterval(async () => {
      try {
        // 1. Extend the stateful session cookie by pinging /auth/me
        await api.get("/auth/me", { _retry: true } as any);
        
        // 2. Optionally refresh the Bearer token in the background silently
        if (typeof window !== "undefined") {
          const token = localStorage.getItem("edu_platform_token");
          if (token) {
            const { data } = await api.post("/auth/refresh-token", {}, {
              headers: { Authorization: `Bearer ${token}` },
              _retry: true,
            } as any);
            if (data?.token) {
              localStorage.setItem("edu_platform_token", data.token);
            }
          }
        }
      } catch (err) {
        // Silently ignore background errors so the user feels nothing
      }
    }, 15 * 60 * 1000); // 15 minutes

    return () => clearInterval(heartbeat);
  }, [isAuthenticated]);

  const login = async (payload: LoginPayload) => {
    await authService.login(payload);
    const u = await fetchUser();
    if (u?.must_change_password) {
      window.location.href = '/change-password';
    } else {
      // Assuming redirect logic is handled where login is called, but we can do it here or let the caller handle it.
      // We will let the caller handle success, but we force push if must_change_password is true.
    }
    return u;
  };

  const register = async (payload: RegisterPayload) => {
    const response = await authService.register(payload);
    return response.message;
  };

  const logout = async () => {
    try {
      await authService.logout();
    } finally {
      setUser(null);
      router.push("/login");
    }
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout, isAuthenticated, isInstructor, isAssistant, isStudent, isAdmin, isSuperAdmin }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return context;
}
