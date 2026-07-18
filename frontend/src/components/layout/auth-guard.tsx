"use client";

import { useAuth } from "@/providers/auth-provider";
import { useRouter, usePathname } from "next/navigation";
import { useEffect, type ReactNode } from "react";
import { PageLoading } from "@/components/shared/loading-spinner";

interface AuthGuardProps {
  children: ReactNode;
  requireAuth?: boolean;
  requireGuest?: boolean;
}

export function AuthGuard({ children, requireAuth, requireGuest = false }: AuthGuardProps) {
  const { isAuthenticated, loading } = useAuth();
  const router = useRouter();
  const pathname = usePathname();

  const actualRequireAuth = requireAuth ?? !requireGuest;

  useEffect(() => {
    if (loading) return;

    if (actualRequireAuth && !isAuthenticated) {
      router.push(`/login?redirect=${encodeURIComponent(pathname)}`);
    }

    if (requireGuest && isAuthenticated) {
      router.push("/");
    }
  }, [loading, isAuthenticated, actualRequireAuth, requireGuest, router, pathname]);

  if (loading || (actualRequireAuth && !isAuthenticated) || (requireGuest && isAuthenticated)) {
    return <PageLoading />;
  }

  return <>{children}</>;
}
