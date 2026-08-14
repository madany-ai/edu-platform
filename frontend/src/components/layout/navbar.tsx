"use client";

import Link from "next/link";
import Image from "next/image";
import { usePathname } from "next/navigation";
import { useState } from "react";
import { Menu, X, LogOut } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useAuth } from "@/providers/auth-provider";
import { ROUTES } from "@/lib/constants";
import { cn } from "@/lib/utils";

import { CenterFiltersPanel } from "./center-filters-panel";

const navLinks = [
  { href: ROUTES.COURSES, label: "الدورات" },
];

export function Navbar() {
  const [mobileOpen, setMobileOpen] = useState(false);
  const { user, isAuthenticated, logout, isInstructor, isAssistant } = useAuth();
  const pathname = usePathname();

  const isStaff = isInstructor || isAssistant || user?.roles?.some((r: any) => ["instructor", "assistant", "super_admin", "admin"].includes(typeof r === "string" ? r : r.name));
  const dashboardHref = isStaff ? "/center" : ROUTES.DASHBOARD;
  const dashboardLabel = isStaff ? "إدارة السنتر" : "حساب الطالب";
  const isDashboardActive = isStaff ? pathname.startsWith("/center") : pathname.startsWith("/dashboard");

  return (
    <header className="sticky top-0 z-50 w-full glass border-b border-[#3b413c] shadow-sm">
      <nav className="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 xl:px-8">
        <Link
          href={ROUTES.HOME}
          className={cn(
            "flex items-center gap-2",
            pathname.startsWith("/dashboard") && "xl:hidden"
          )}
        >
          <div className="flex items-center gap-2">
            <Image src="/logo.jpg" alt="Mr Hefni Muhammad" width={32} height={32} className="rounded-full object-cover border border-primary/20 shadow-sm" />
            <span className="text-lg font-bold text-primary">Mr Hefni Muhammad</span>
          </div>
        </Link>
 
        <div className="hidden items-center gap-8 xl:flex">
          {isAuthenticated && (
            <Link
              href={dashboardHref}
              className={cn(
                "text-sm font-medium transition-colors hover:text-primary",
                isDashboardActive
                  ? "border-b-2 border-primary pb-1 font-bold text-primary"
                  : "text-on-surface-variant"
              )}
            >
              {dashboardLabel}
            </Link>
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
 
        <div className="hidden items-center gap-3 xl:flex">
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
 
        <button className="xl:hidden p-2" onClick={() => setMobileOpen(!mobileOpen)}>
          {mobileOpen ? <X className="h-6 w-6 text-foreground" /> : <Menu className="h-6 w-6 text-foreground" />}
        </button>
      </nav>
 
      {mobileOpen && (
        <div className="glass border-t border-white/20 px-4 pb-4 pt-2 xl:hidden overflow-y-auto max-h-[85vh]">
          <div className="flex flex-col gap-2">
            {isStaff && pathname.startsWith("/center") && (
              <div className="mb-4 mt-2">
                <CenterFiltersPanel onClose={() => setMobileOpen(false)} />
              </div>
            )}
            {isAuthenticated && (
              <Link
                href={dashboardHref}
                onClick={() => setMobileOpen(false)}
                className={cn(
                  "rounded-lg px-4 py-2 text-sm font-medium transition-colors",
                  isDashboardActive ? "bg-primary/10 text-primary" : "text-on-surface-variant hover:bg-muted"
                )}
              >
                {dashboardLabel}
              </Link>
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

            <Button 
              variant="secondary" 
              className="mt-4 w-full gap-2 font-bold"
              onClick={() => setMobileOpen(false)}
            >
              <X className="h-4 w-4" /> إغلاق القائمة
            </Button>
          </div>
        </div>
      )}
    </header>
  );
}
