"use client";

import { useRouter } from "next/navigation";
import { useAuth } from "@/contexts/auth-context";
import { Button } from "@/components/ui/button";
import { Loader2, LogOut, User, GraduationCap } from "lucide-react";

export default function Home() {
  const router = useRouter();
  const { user, loading, logout } = useAuth();

  if (loading) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!user) {
    return (
      <div className="flex min-h-screen flex-col items-center justify-center gap-6 p-4 text-center">
        <GraduationCap className="h-16 w-16 text-primary" />
        <h1 className="text-3xl font-bold">المنصة التعليمية</h1>
        <p className="text-muted-foreground">منصة التعليم التفاعلية المتكاملة</p>
        <div className="flex gap-4">
          <Button size="lg" onClick={() => router.push("/login")}>
            تسجيل الدخول
          </Button>
          <Button size="lg" variant="outline" onClick={() => router.push("/register")}>
            إنشاء حساب
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className="flex min-h-screen flex-col">
      <header className="flex items-center justify-between border-b px-6 py-4">
        <div className="flex items-center gap-2 font-semibold">
          <GraduationCap className="h-6 w-6" />
          المنصة التعليمية
        </div>
        <div className="flex items-center gap-4">
          <div className="flex items-center gap-2 text-sm text-muted-foreground">
            <User className="h-4 w-4" />
            {user.name}
          </div>
          <Button variant="outline" size="sm" onClick={logout}>
            <LogOut className="ml-2 h-4 w-4" />
            تسجيل الخروج
          </Button>
        </div>
      </header>
      <main className="flex flex-1 items-center justify-center p-6">
        <div className="text-center">
          <h2 className="text-2xl font-bold">مرحباً بك، {user.name}</h2>
          <p className="mt-2 text-muted-foreground">
            البريد الإلكتروني: {user.email}
          </p>
        </div>
      </main>
    </div>
  );
}
