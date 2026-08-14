"use client";

import { useAuth } from "@/providers/auth-provider";
import { useRouter } from "next/navigation";
import { useEffect, type ReactNode } from "react";
import { PageLoading } from "@/components/shared/loading-spinner";

export default function CenterLayout({ children }: { children: ReactNode }) {
  const { user, isAuthenticated, loading, isInstructor, isAssistant } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (loading) return;

    if (!isAuthenticated) {
      router.push("/login?redirect=/center");
      return;
    }

    const isStaff = isInstructor || isAssistant || user?.roles?.some((r: any) => ["instructor", "assistant", "super_admin", "admin"].includes(typeof r === "string" ? r : r.name));

    if (!isStaff) {
      // If student tries to access center, redirect them to dashboard
      router.replace("/dashboard");
    }
  }, [loading, isAuthenticated, isInstructor, isAssistant, user, router]);

  // While checking, show loading
  if (loading) {
    return <PageLoading />;
  }

  // Double check before rendering
  const isStaff = isInstructor || isAssistant || user?.roles?.some((r: any) => ["instructor", "assistant", "super_admin", "admin"].includes(typeof r === "string" ? r : r.name));

  if (!isStaff) {
    return <PageLoading />; // Will redirect in useEffect
  }

  return <>{children}</>;
}
