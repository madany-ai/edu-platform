"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/contexts/auth-context";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { getStudentDashboard } from "@/lib/api/dashboard";
import { getMyEnrollments } from "@/lib/api/courses";
import type { StudentDashboard, Enrollment } from "@/lib/types";
import {
  Loader2, BookOpen, PlayCircle, Clock, Award, TrendingUp, ChevronLeft
} from "lucide-react";
import Link from "next/link";

export default function StudentDashboardPage() {
  const { user, loading: authLoading } = useAuth();
  const router = useRouter();
  const [stats, setStats] = useState<StudentDashboard | null>(null);
  const [enrollments, setEnrollments] = useState<Enrollment[]>([]);

  useEffect(() => {
    if (authLoading) return;
    if (!user) { router.push("/login"); return; }

    if (user.roles?.includes("instructor")) {
      router.replace("/dashboard/instructor");
      return;
    }

    Promise.all([
      getStudentDashboard(),
      getMyEnrollments(),
    ]).then(([s, e]) => {
      setStats(s);
      setEnrollments(e);
    });
  }, [user, authLoading, router]);

  if (authLoading || !stats) {
    return (
      <div className="flex min-h-[60vh] items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  const statCards = [
    { icon: BookOpen, label: "الدورات المسجلة", value: stats.enrollments_count, color: "text-blue-600 bg-blue-100" },
    { icon: PlayCircle, label: "المحاضرات المكتملة", value: stats.completed_lectures, color: "text-green-600 bg-green-100" },
    { icon: Clock, label: "ساعات التعلم", value: Math.round(stats.total_watch_minutes / 60), color: "text-orange-600 bg-orange-100" },
    { icon: Award, label: "الدورات المكتملة", value: stats.completed_courses, color: "text-purple-600 bg-purple-100" },
  ];

  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <div className="mb-8">
        <h1 className="text-3xl font-bold mb-1">مرحباً بعودتك، {user?.name}</h1>
        <p className="text-muted-foreground">واصل رحلتك التعليمية من حيث توقفت</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-10">
        {statCards.map((s) => (
          <Card key={s.label}>
            <CardContent className="p-6 flex items-center gap-4">
              <div className={`flex h-12 w-12 items-center justify-center rounded-full ${s.color}`}>
                <s.icon className="h-6 w-6" />
              </div>
              <div>
                <p className="text-2xl font-bold">{s.value}</p>
                <p className="text-sm text-muted-foreground">{s.label}</p>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="text-lg">الدورات المسجل فيها</CardTitle>
            <Link href="/courses" className="text-sm text-primary hover:underline flex items-center gap-1">
              عرض الكل <ChevronLeft className="h-4 w-4" />
            </Link>
          </CardHeader>
          <CardContent className="space-y-4">
            {enrollments.length === 0 ? (
              <div className="text-center py-8 text-muted-foreground">
                <p>لم تسجل في أي دورة بعد</p>
                <Link href="/courses">
                  <Button variant="outline" className="mt-4">تصفح الدورات</Button>
                </Link>
              </div>
            ) : (
              enrollments.slice(0, 5).map((enrollment) => (
                <div key={enrollment.id} className="rounded-lg border p-4">
                  <div className="flex items-center justify-between mb-2">
                    <p className="font-medium">{enrollment.course.title}</p>
                    <span className="text-sm text-muted-foreground">
                      {enrollment.course.instructor?.name}
                    </span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">
                      {enrollment.status === 'active' ? 'جاري' : enrollment.status}
                    </span>
                    <span className="text-xs text-muted-foreground">
                      {enrollment.created_at ? new Date(enrollment.created_at).toLocaleDateString('ar-SA') : ''}
                    </span>
                  </div>
                  <Link
                    href={`/courses/${enrollment.course.id}`}
                    className="text-xs text-primary hover:underline mt-2 inline-block"
                  >
                    متابعة التعلم
                  </Link>
                </div>
              ))
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <TrendingUp className="h-5 w-5 text-primary" />
              نشاطك التعليمي
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center justify-between border-b pb-3">
              <span className="text-sm">الدورات المسجل فيها</span>
              <span className="font-bold">{stats.enrollments_count}</span>
            </div>
            <div className="flex items-center justify-between border-b pb-3">
              <span className="text-sm">المحاضرات المكتملة</span>
              <span className="font-bold">{stats.completed_lectures}</span>
            </div>
            <div className="flex items-center justify-between border-b pb-3">
              <span className="text-sm">إجمالي وقت التعلم</span>
              <span className="font-bold">{Math.round(stats.total_watch_minutes / 60)} ساعة</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-sm">متوسط درجات الامتحانات</span>
              <span className="font-bold">{stats.average_exam_score}%</span>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
