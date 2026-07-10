"use client";

import Link from "next/link";
import { useAuth } from "@/contexts/auth-context";
import { Button } from "@/components/ui/button";
import { GraduationCap, LogOut, User, Menu, X } from "lucide-react";
import { useState } from "react";

export function Navbar() {
  const { user, logout } = useAuth();
  const [open, setOpen] = useState(false);

  return (
    <header className="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
      <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
        <div className="flex items-center gap-6">
          <Link href="/" className="flex items-center gap-2 font-bold text-xl">
            <GraduationCap className="h-6 w-6 text-primary" />
            المنصة التعليمية
          </Link>
          <nav className="hidden md:flex items-center gap-6 text-sm font-medium">
            <Link href="/courses" className="text-muted-foreground hover:text-foreground transition-colors">
              الدورات
            </Link>
            {user && (
              <Link href="/dashboard" className="text-muted-foreground hover:text-foreground transition-colors">
                لوحة التحكم
              </Link>
            )}
            {user && (
              <Link href="/dashboard/instructor" className="text-muted-foreground hover:text-foreground transition-colors">
                لوحة المدرب
              </Link>
            )}
          </nav>
        </div>

        <div className="hidden md:flex items-center gap-4">
          {user ? (
            <>
              <Link href="/dashboard">
                <Button variant="ghost" size="sm" className="gap-2">
                  <User className="h-4 w-4" />
                  {user.name}
                </Button>
              </Link>
              <Button variant="outline" size="sm" onClick={logout}>
                <LogOut className="ml-2 h-4 w-4" />
                تسجيل الخروج
              </Button>
            </>
          ) : (
            <>
              <Link href="/login">
                <Button variant="ghost" size="sm">تسجيل الدخول</Button>
              </Link>
              <Link href="/register">
                <Button size="sm">إنشاء حساب</Button>
              </Link>
            </>
          )}
        </div>

        <Button variant="ghost" size="icon" className="md:hidden" onClick={() => setOpen(!open)}>
          {open ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
        </Button>
      </div>

      {open && (
        <div className="border-t md:hidden">
          <nav className="flex flex-col gap-2 p-4">
            <Link href="/courses" className="text-sm font-medium py-2" onClick={() => setOpen(false)}>الدورات</Link>
            {user ? (
              <>
                <Link href="/dashboard" className="text-sm font-medium py-2" onClick={() => setOpen(false)}>لوحة التحكم</Link>
                <button onClick={() => { logout(); setOpen(false); }} className="text-sm font-medium py-2 text-right text-destructive">تسجيل الخروج</button>
              </>
            ) : (
              <>
                <Link href="/login" className="text-sm font-medium py-2" onClick={() => setOpen(false)}>تسجيل الدخول</Link>
                <Link href="/register" className="text-sm font-medium py-2" onClick={() => setOpen(false)}>إنشاء حساب</Link>
              </>
            )}
          </nav>
        </div>
      )}
    </header>
  );
}
