"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/utils";
import { useAuth } from "@/providers/auth-provider";
import { ROUTES } from "@/lib/constants";
import {
  LayoutDashboard,
  BookOpen,
  PlayCircle,
  Settings,
  LogOut,
  Bell,
  Atom,
  MessageCircle,
  UserCheck,
  Award,
  Building2,
  QrCode,
  Calendar,
  FileSpreadsheet,
  Trophy,
  Users,
  Search,
} from "lucide-react";

const studentLinks = [
  { href: ROUTES.DASHBOARD, label: "لوحة التحكم", icon: LayoutDashboard },
  { href: "/dashboard/center-report", label: "تقرير السنتر الخاص بي", icon: Building2 },
  { href: "/dashboard/courses", label: "دوراتي", icon: BookOpen },
  { href: "/dashboard/lectures", label: "محاضراتي", icon: PlayCircle },
  { href: "/dashboard/exams", label: "الامتحانات والواجبات", icon: BookOpen },
  { href: "/dashboard/attendance", label: "سجل الحضور والغياب", icon: UserCheck },
  { href: "/dashboard/center-grades", label: "درجات السنتر الورقية", icon: Award },
  { href: "/dashboard/questions", label: "أسئلتي", icon: MessageCircle },
  { href: "/dashboard/notifications", label: "الإشعارات", icon: Bell },
  { href: "/dashboard/settings", label: "الإعدادات", icon: Settings },
];

const staffLinks = [
  { href: "/center", label: "رئيسية السنتر والإحصائيات", icon: LayoutDashboard },
  { href: "/center/scanner", label: "ماسح الحضور بالكاميرا", icon: QrCode },
  { href: "/center/sessions", label: "الحصص وكشوف الغياب", icon: Calendar },
  { href: "/center/exams", label: "الامتحانات ورصد الدرجات", icon: FileSpreadsheet },
  { href: "/center/rankings", label: "ترتيب الأوائل والتفوق", icon: Trophy },
  { href: "/center/groups", label: "المجموعات والسنوات", icon: Users },
  { href: "/center/students", label: "دليل الطلاب والتقارير", icon: Search },
  { href: "/dashboard/settings", label: "الإعدادات", icon: Settings },
];

interface SidebarProps {
  className?: string;
}

export function Sidebar({ className }: SidebarProps) {
  const pathname = usePathname();
  const { logout, isInstructor, isAssistant, user } = useAuth();
  
  const isStaff = isInstructor || isAssistant || user?.roles?.some((r: any) => ["instructor", "assistant"].includes(typeof r === "string" ? r : r.name));
  const links = isStaff ? staffLinks : studentLinks;

  return (
    <aside
      className={cn(
        "hidden h-screen w-64 shrink-0 glass border-y-0 border-r-0 border-l border-[#3b413c] lg:sticky lg:top-0 lg:block",
        className
      )}
    >
      <div className="flex h-full flex-col justify-between p-4">
        <div className="space-y-6">
          <div className="flex items-center justify-between px-3 py-2 border-b border-white/5 pb-4">
            <div className="flex items-center gap-2">
              <Atom className="h-6 w-6 text-primary animate-spin" style={{ animationDuration: "6s" }} />
              <span className="font-bold text-foreground text-sm">مختبر العلوم 🧪</span>
            </div>
            {isStaff && (
              <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-primary/20 text-primary border border-primary/30">
                إدارة السنتر
              </span>
            )}
          </div>
          <nav className="flex flex-col gap-1.5">
            {links.map((link) => {
              const Icon = link.icon;
              const isActive =
                link.href === "/center" || link.href === ROUTES.DASHBOARD
                  ? pathname === link.href
                  : pathname.startsWith(link.href);

              return (
                <Link
                  key={link.href}
                  href={link.href}
                  className={cn(
                    "flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all hover:translate-x-[-4px] border border-transparent",
                    isActive
                      ? "bg-primary text-white border-primary/20 shadow-md shadow-primary/20"
                      : "text-muted-foreground hover:bg-[#272d28] hover:text-foreground"
                  )}
                >
                  <Icon className={cn("h-5 w-5", isActive ? "text-white" : "text-primary")} />
                  {link.label}
                </Link>
              );
            })}
          </nav>
        </div>
        <button
          onClick={logout}
          className="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-destructive transition-colors hover:bg-destructive/10"
        >
          <LogOut className="h-5 w-5" />
          تسجيل الخروج
        </button>
      </div>
    </aside>
  );
}
