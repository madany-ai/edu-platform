"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";
import { Menu, X, LogOut } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useAuth } from "@/providers/auth-provider";
import { ROUTES } from "@/lib/constants";
import { cn } from "@/lib/utils";

const navLinks = [
  { href: ROUTES.COURSES, label: "الدورات" },
];

export function Navbar() {
  const [mobileOpen, setMobileOpen] = useState(false);
  const { user, isAuthenticated, isInstructor, logout } = useAuth();
  const pathname = usePathname();

  return (
    <header className="sticky top-0 z-50 w-full glass border-b border-[#3b413c] shadow-sm">
      <nav className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 lg:px-8">
        <Link
          href={ROUTES.HOME}
          className={cn(
            "flex items-center gap-2",
            pathname.startsWith("/dashboard") && "lg:hidden"
          )}
        >
          <span className="text-lg font-bold text-primary">مختبر العلوم الرقمي</span>
        </Link>

        <div className="hidden items-center gap-8 md:flex">
          {isAuthenticated && (
            <>
              {isInstructor ? (
                <Link
                  href={ROUTES.INSTRUCTOR_DASHBOARD}
                  className={cn(
                    "text-sm font-medium transition-colors hover:text-primary",
                    pathname.startsWith("/dashboard/instructor")
                      ? "border-b-2 border-primary pb-1 font-bold text-primary"
                      : "text-on-surface-variant"
                  )}
                >
                  لوحة المدرب
                </Link>
              ) : (
                <Link
                  href={ROUTES.DASHBOARD}
                  className={cn(
                    "text-sm font-medium transition-colors hover:text-primary",
                    pathname.startsWith("/dashboard") && !isInstructor
                      ? "border-b-2 border-primary pb-1 font-bold text-primary"
                      : "text-on-surface-variant"
                  )}
                >
                  لوحة التحكم
                </Link>
              )}
            </>
          )}
          {navLinks.map((link) => (
            <Link
              key={link.href}
              href={link.href}
              className={cn(
                "text-sm font-medium transition-colors hover:text-primary",
                pathname === link.href ? "border-b-2 border-primary pb-1 font-bold text-primary" : "text-on-surface-variant"
              )}
            >
              {link.label}
            </Link>
          ))}
        </div>

        <div className="hidden items-center gap-3 md:flex">
          {isAuthenticated ? (
            <div className="flex items-center gap-3">
              <span className="text-sm text-muted-foreground">{user?.name}</span>
              <Button variant="ghost" size="sm" onClick={logout}>
                <LogOut className="ml-2 h-4 w-4" />
                خروج
              </Button>
            </div>
          ) : (
            <>
              <Link href={ROUTES.LOGIN}>
                <Button variant="ghost" size="sm">
                  تسجيل الدخول
                </Button>
              </Link>
              <Link href={ROUTES.REGISTER}>
                <Button size="sm" className="rounded-full">
                  إنشاء حساب
                </Button>
              </Link>
            </>
          )}
        </div>

        <button className="md:hidden" onClick={() => setMobileOpen(!mobileOpen)}>
          {mobileOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
        </button>
      </nav>

      {mobileOpen && (
        <div className="glass border-t border-white/20 px-4 pb-4 pt-2 md:hidden">
          <div className="flex flex-col gap-2">
            {isAuthenticated && (
              <>
                {isInstructor ? (
                  <Link
                    href={ROUTES.INSTRUCTOR_DASHBOARD}
                    onClick={() => setMobileOpen(false)}
                    className={cn(
                      "rounded-lg px-4 py-2 text-sm font-medium transition-colors",
                      pathname.startsWith("/dashboard/instructor") ? "bg-primary/10 text-primary" : "text-on-surface-variant hover:bg-muted"
                    )}
                  >
                    لوحة المدرب
                  </Link>
                ) : (
                  <Link
                    href={ROUTES.DASHBOARD}
                    onClick={() => setMobileOpen(false)}
                    className={cn(
                      "rounded-lg px-4 py-2 text-sm font-medium transition-colors",
                      pathname.startsWith("/dashboard") ? "bg-primary/10 text-primary" : "text-on-surface-variant hover:bg-muted"
                    )}
                  >
                    لوحة التحكم
                  </Link>
                )}
              </>
            )}
            {navLinks.map((link) => (
              <Link
                key={link.href}
                href={link.href}
                onClick={() => setMobileOpen(false)}
                className={cn(
                  "rounded-lg px-4 py-2 text-sm font-medium transition-colors",
                  pathname === link.href ? "bg-primary/10 text-primary" : "text-on-surface-variant hover:bg-muted"
                )}
              >
                {link.label}
              </Link>
            ))}
            {isAuthenticated && (
              <>
                <hr className="my-2 border-border" />
                <span className="px-4 py-2 text-sm text-muted-foreground">{user?.name}</span>
                <button
                  onClick={() => {
                    setMobileOpen(false);
                    logout();
                  }}
                  className="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-destructive hover:bg-destructive/10"
                >
                  <LogOut className="h-4 w-4" />
                  خروج
                </button>
              </>
            )}
            {!isAuthenticated && (
              <>
                <hr className="my-2 border-border" />
                <Link href={ROUTES.LOGIN} onClick={() => setMobileOpen(false)}>
                  <Button variant="outline" className="w-full">
                    تسجيل الدخول
                  </Button>
                </Link>
                <Link href={ROUTES.REGISTER} onClick={() => setMobileOpen(false)}>
                  <Button className="w-full rounded-full">إنشاء حساب</Button>
                </Link>
              </>
            )}
          </div>
        </div>
      )}
    </header>
  );
}
