"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/utils";
import { useAuth } from "@/providers/auth-provider";
import { ROUTES } from "@/lib/constants";
import {
  LayoutDashboard,
  BookOpen,
  GraduationCap,
  Settings,
  Bell,
} from "lucide-react";

const studentLinks = [
  { href: ROUTES.DASHBOARD, label: "لوحة التحكم", icon: LayoutDashboard },
  { href: "/dashboard/courses", label: "دوراتي", icon: BookOpen },
  { href: "/dashboard/certificates", label: "شهاداتي", icon: GraduationCap },
  { href: "/dashboard/notifications", label: "الإشعارات", icon: Bell },
  { href: "/dashboard/settings", label: "الإعدادات", icon: Settings },
];



export function MobileNav() {
  const pathname = usePathname();
  
  // Only display if route is in dashboard
  if (!pathname.startsWith("/dashboard") && !pathname.startsWith("/courses")) {
    return null;
  }

  const links = studentLinks;

  return (
    <div className="fixed bottom-0 left-0 right-0 z-50 h-16 bg-[#141a15]/90 backdrop-blur-md border-t border-[#3b413c] flex items-center justify-around px-2 lg:hidden">
      {links.map((link) => {
        const Icon = link.icon;
        const isActive = link.href === ROUTES.DASHBOARD
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
              "flex flex-col items-center justify-center gap-1 flex-1 py-1 transition-all rounded-xl",
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
