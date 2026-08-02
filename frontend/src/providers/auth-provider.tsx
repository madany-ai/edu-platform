"use client";

import { createContext, useContext, useState, useEffect, useCallback, type ReactNode } from "react";
import { useRouter } from "next/navigation";
import { authService, type LoginPayload, type RegisterPayload } from "@/services/auth.service";
import type { User } from "@/types";

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (payload: LoginPayload) => Promise<void>;
  register: (payload: RegisterPayload) => Promise<string>;
  logout: () => Promise<void>;
  isAuthenticated: boolean;
  isInstructor: boolean;
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
    } catch {
      setUser(null);
    }
  }, []);

  useEffect(() => {
    let cancelled = false;
    const load = async () => {
      await fetchUser();
      if (!cancelled) setLoading(false);
    };
    load();
    return () => { cancelled = true; };
  }, [fetchUser]);

  const login = async (payload: LoginPayload) => {
    await authService.login(payload);
    await fetchUser();
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
  const isInstructor = user?.roles?.some((r) => r === "instructor" || r === "assistant") ?? false;
  const isStudent = user?.roles?.includes("student") ?? false;

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout, isAuthenticated, isInstructor, isStudent }}>
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
