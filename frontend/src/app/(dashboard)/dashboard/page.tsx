"use client";

import { useAuth } from "@/providers/auth-provider";
import { useRouter } from "next/navigation";
import { useEffect } from "react";
import { PageLoading } from "@/components/shared/loading-spinner";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { useQuery } from "@tanstack/react-query";
import { dashboardService } from "@/services/dashboard.service";
import { enrollmentService } from "@/services/enrollment.service";
import { ROUTES } from "@/lib/constants";
import {
  BookOpen,
  PlayCircle,
  Clock,
  Award,
  TrendingUp,
  ChevronLeft,
  Atom,
} from "lucide-react";
import Link from "next/link";

export default function StudentDashboardPage() {
  const { user, isInstructor } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (isInstructor) {
      router.replace(ROUTES.INSTRUCTOR_DASHBOARD);
    }
  }, [isInstructor, router]);

  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ["student-dashboard"],
    queryFn: dashboardService.getStudentDashboard,
  });

  const { data: enrollmentsData, isLoading: enrollmentsLoading } = useQuery({
    queryKey: ["my-enrollments"],
    queryFn: enrollmentService.getMyEnrollments,
  });

  if (statsLoading || enrollmentsLoading) return <PageLoading />;

  const enrollments = enrollmentsData?.data ?? [];

  const statCards = [
    {
      icon: BookOpen,
      label: "الدورات المسجلة",
      value: stats?.enrollments_count ?? 0,
      color: "text-primary bg-primary/10 border-primary/20 cosmic-border-glow",
    },
    {
      icon: PlayCircle,
      label: "المحاضرات المكتملة",
      value: stats?.completed_lectures ?? 0,
      color: "text-secondary bg-secondary/10 border-secondary/20 cosmic-border-glow-purple",
    },
    {
      icon: Clock,
      label: "ساعات التعلم",
      value: Math.round((stats?.total_watch_minutes ?? 0) / 60),
      color: "text-accent bg-accent/10 border-accent/20",
    },
    {
      icon: Award,
      label: "الدورات المكتملة",
      value: stats?.completed_courses ?? 0,
      color: "text-purple-400 bg-purple-500/10 border-purple-500/20",
    },
  ];

  return (
    <div className="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
      <div className="p-6 rounded-2xl bg-gradient-to-r from-primary/10 via-secondary/5 to-transparent border border-white/5">
        <h1 className="text-3xl font-extrabold text-gradient mb-2">
          مرحباً بعودتك إلى المختبر، {user?.name} 🧪
        </h1>
        <p className="text-sm text-muted-foreground">
          شفرة النجاح تبدأ بالتجربة والمشاهدة الحية. واصل رحلتك العلمية اليوم.
        </p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {statCards.map((s) => (
          <div key={s.label} className="glass-card p-6 rounded-2xl flex items-center gap-4 transition-all hover:translate-y-[-4px]">
            <div className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border ${s.color}`}>
              <s.icon className="h-6 w-6" />
            </div>
            <div>
              <p className="text-3xl font-black text-foreground science-glow-text">{s.value}</p>
              <p className="text-xs text-muted-foreground font-medium mt-0.5">{s.label}</p>
            </div>
          </div>
        ))}
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="glass-card p-6 rounded-2xl lg:col-span-2 border border-white/5 flex flex-col justify-between">
          <div>
            <div className="flex items-center justify-between mb-6">
              <h3 className="text-lg font-bold text-gradient">الدورات المسجل فيها</h3>
              <Link
                href={ROUTES.COURSES}
                className="flex items-center gap-1 text-xs text-primary hover:underline hover:text-primary-fixed transition-all"
              >
                عرض الكل <ChevronLeft className="h-4 w-4" />
              </Link>
            </div>
            <div className="space-y-4">
              {enrollments.length === 0 ? (
                <div className="py-12 text-center text-muted-foreground">
                  <Atom className="h-12 w-12 mx-auto text-muted-foreground/30 mb-3 animate-spin" />
                  <p className="text-sm">لم تسجل في أي دورة علمية بعد</p>
                  <Link href={ROUTES.COURSES}>
                    <Button variant="outline" className="mt-4 border-primary/30 hover:bg-primary/10 hover:text-primary">
                      تصفح الدورات الكونية
                    </Button>
                  </Link>
                </div>
              ) : (
                enrollments.slice(0, 3).map((enrollment) => (
                  <div key={enrollment.id} className="rounded-xl border border-white/5 bg-background/30 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 transition-all hover:bg-background/50">
                    <div className="space-y-1">
                      <p className="font-bold text-foreground text-base">{enrollment.course.title}</p>
                      <p className="text-xs text-muted-foreground">
                        إشراف الأستاذ: {enrollment.course.instructor?.name || "معلم المادة"}
                      </p>
                    </div>
                    <div className="flex items-center gap-4 justify-between md:justify-end">
                      <div className="text-right">
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                          {enrollment.status === "active" ? "نشط" : enrollment.status}
                        </span>
                        <p className="text-[10px] text-muted-foreground mt-1">
                          بدء: {enrollment.created_at
                            ? new Date(enrollment.created_at).toLocaleDateString("ar-SA")
                            : ""}
                        </p>
                      </div>
                      <Link href={`/courses/${enrollment.course.id}`}>
                        <Button size="sm" className="bg-gradient-to-r from-primary to-secondary text-primary-foreground font-bold">
                          متابعة التعلم
                        </Button>
                      </Link>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>

        <div className="glass-card p-6 rounded-2xl border border-white/5 space-y-6">
          <div className="flex items-center gap-2 pb-4 border-b border-white/5">
            <TrendingUp className="h-5 w-5 text-primary" />
            <h3 className="text-lg font-bold text-gradient">نشاطك التعليمي</h3>
          </div>
          <div className="space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-white/5">
              <span className="text-xs text-muted-foreground">الدورات المسجل فيها</span>
              <span className="font-bold text-foreground text-sm">{stats?.enrollments_count ?? 0}</span>
            </div>
            <div className="flex items-center justify-between pb-3 border-b border-white/5">
              <span className="text-xs text-muted-foreground">المحاضرات المكتملة</span>
              <span className="font-bold text-foreground text-sm">{stats?.completed_lectures ?? 0}</span>
            </div>
            <div className="flex items-center justify-between pb-3 border-b border-white/5">
              <span className="text-xs text-muted-foreground">إجمالي وقت التعلم</span>
              <span className="font-bold text-foreground text-sm">
                {Math.round((stats?.total_watch_minutes ?? 0) / 60)} ساعة
              </span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-xs text-muted-foreground">متوسط درجات الامتحانات</span>
              <span className="font-bold text-primary text-sm science-glow-text">{stats?.average_exam_score ?? 0}%</span>
            </div>
          </div>

          <div className="pt-2">
            <div className="p-4 rounded-xl bg-gradient-to-br from-secondary/5 to-accent/5 border border-white/5">
              <p className="text-xs font-semibold text-foreground mb-1">💡 نصيحة علمية اليوم:</p>
              <p className="text-[11px] text-muted-foreground leading-relaxed">
                التكرار والمشاهدة بتركيز يساعدان في ترسيخ المعادلات الفيزيائية والتفاعلات الكيميائية في الذاكرة طويلة المدى.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
