"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/utils";
import { useAuth } from "@/providers/auth-provider";
import { ROUTES } from "@/lib/constants";
import {
  LayoutDashboard,
  BookOpen,
  Settings,
  Bell,
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
  { href: ROUTES.DASHBOARD, label: "الرئيسية", icon: LayoutDashboard },
  { href: "/dashboard/center-report", label: "تقريري", icon: Building2 },
  { href: "/dashboard/courses", label: "دوراتي", icon: BookOpen },
  { href: "/dashboard/exams", label: "الامتحانات", icon: BookOpen },
  { href: "/dashboard/attendance", label: "الغياب", icon: UserCheck },
  { href: "/dashboard/center-grades", label: "الدرجات", icon: Award },
  { href: "/dashboard/questions", label: "أسئلتي", icon: MessageCircle },
  { href: "/dashboard/notifications", label: "الإشعارات", icon: Bell },
  { href: "/dashboard/settings", label: "الإعدادات", icon: Settings },
];

const staffLinks = [
  { href: "/center", label: "السنتر", icon: LayoutDashboard },
  { href: "/center/scanner", label: "الماسح", icon: QrCode },
  { href: "/center/sessions", label: "الحصص", icon: Calendar },
  { href: "/center/exams", label: "الامتحانات", icon: FileSpreadsheet },
  { href: "/center/rankings", label: "الأوائل", icon: Trophy },
  { href: "/center/groups", label: "المجموعات", icon: Users },
  { href: "/center/students", label: "الطلاب", icon: Search },
  { href: "/dashboard/settings", label: "الإعدادات", icon: Settings },
];



export function MobileNav() {
  const pathname = usePathname();
  const { isInstructor, isAssistant, user } = useAuth();
  
  // Only display if route is in dashboard or center
  if (!pathname.startsWith("/dashboard") && !pathname.startsWith("/courses") && !pathname.startsWith("/center")) {
    return null;
  }

  const isStaff = isInstructor || isAssistant || user?.roles?.some((r: any) => ["instructor", "assistant"].includes(typeof r === "string" ? r : r.name));
  const links = isStaff ? staffLinks : studentLinks;

  return (
    <div className="fixed bottom-0 left-0 right-0 z-50 h-16 bg-[#141a15]/90 backdrop-blur-md border-t border-[#3b413c] flex items-center overflow-x-auto overflow-y-hidden px-2 xl:hidden no-scrollbar" style={{ scrollbarWidth: "none" }}>
      {links.map((link) => {
        const Icon = link.icon;
        const isActive = link.href === ROUTES.DASHBOARD || link.href === "/center"
          ? pathname === link.href
          : pathname === link.href ||
            (link.href !== "/" &&
              pathname.startsWith(link.href) &&
              link.href !== ROUTES.COURSES);
        return (
          <Link
            key={link.href}
            href={link.href}
            className={cn(
              "flex flex-col items-center justify-center gap-1 min-w-[72px] flex-shrink-0 py-1 transition-all rounded-xl",
              isActive
                ? "text-primary font-bold"
                : "text-muted-foreground hover:text-foreground"
            )}
          >
            <Icon className={cn("h-5 w-5", isActive && "science-glow-text")} />
            <span className="text-[10px] truncate max-w-[64px]">{link.label}</span>
          </Link>
        );
      })}
    </div>
  );
}
