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

  useEffect(() => {
    let cancelled = false;
    const load = async () => {
      const u = await fetchUser();
      if (!cancelled) {
        setLoading(false);
        if (u?.must_change_password && !window.location.pathname.includes('/change-password') && !window.location.pathname.includes('/logout')) {
          router.push('/change-password');
        }
      }
    };
    load();
    return () => { cancelled = true; };
  }, [fetchUser, router]);

  const login = async (payload: LoginPayload) => {
    await authService.login(payload);
    const u = await fetchUser();
    if (u?.must_change_password) {
      router.push('/change-password');
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

  const isAuthenticated = !!user;
  const isInstructor = user?.roles?.some((r) => r === "instructor") ?? false;
  const isAssistant = user?.roles?.some((r) => r === "assistant") ?? false;
  const isStudent = user?.roles?.includes("student") ?? false;

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout, isAuthenticated, isInstructor, isAssistant, isStudent }}>
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
