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

export function AuthGuard({ children, requireAuth = true, requireGuest = false }: AuthGuardProps) {
  const { isAuthenticated, loading } = useAuth();
  const router = useRouter();
  const pathname = usePathname();

  useEffect(() => {
    if (loading) return;

    if (requireAuth && !isAuthenticated) {
      router.push(`/login?redirect=${encodeURIComponent(pathname)}`);
    }

    if (requireGuest && isAuthenticated) {
      router.push("/");
    }
  }, [loading, isAuthenticated, requireAuth, requireGuest, router, pathname]);

  if (loading) return <PageLoading />;

  if (requireAuth && !isAuthenticated) return null;
  if (requireGuest && isAuthenticated) return null;

  return <>{children}</>;
}
