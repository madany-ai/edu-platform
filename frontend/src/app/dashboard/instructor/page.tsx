"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/contexts/auth-context";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { getInstructorDashboard, getInstructorCourses } from "@/lib/api/dashboard";
import type { InstructorDashboard, Course } from "@/lib/types";
import {
  Loader2, BookOpen, Users, DollarSign, Star, Plus, Eye, TrendingUp
} from "lucide-react";

export default function InstructorDashboardPage() {
  const { user, loading: authLoading } = useAuth();
  const router = useRouter();
  const [stats, setStats] = useState<InstructorDashboard | null>(null);
  const [courses, setCourses] = useState<Course[]>([]);

  useEffect(() => {
    if (authLoading) return;
    if (!user) { router.push("/login"); return; }

    Promise.all([
      getInstructorDashboard(),
      getInstructorCourses(),
    ]).then(([s, c]) => {
      setStats(s);
      setCourses(c);
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
    { icon: BookOpen, label: "الدورات المنشورة", value: stats.courses_count, color: "text-blue-600 bg-blue-100" },
    { icon: Users, label: "إجمالي الطلاب", value: stats.total_students, color: "text-green-600 bg-green-100" },
    { icon: DollarSign, label: "الأرباح", value: `${stats.total_revenue} د.م`, color: "text-orange-600 bg-orange-100" },
    { icon: Star, label: "التقييم", value: stats.average_rating, color: "text-purple-600 bg-purple-100" },
  ];

  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <div className="mb-8 flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold mb-1">لوحة المدرب</h1>
          <p className="text-muted-foreground">مرحباً بعودتك، {user?.name}</p>
        </div>
        <Button className="gap-2">
          <Plus className="h-4 w-4" />
          دورة جديدة
        </Button>
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
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <Eye className="h-5 w-5 text-primary" />
              أداء الدورات
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {courses.length === 0 ? (
              <div className="text-center py-8 text-muted-foreground">
                <p>لا توجد دورات بعد</p>
                <Button variant="outline" className="mt-4 gap-2">
                  <Plus className="h-4 w-4" />
                  إنشاء دورة
                </Button>
              </div>
            ) : (
              courses.map((course) => (
                <div key={course.id} className="rounded-lg border p-4">
                  <div className="flex items-center justify-between mb-2">
                    <p className="font-medium">{course.title}</p>
                    <span className="text-sm text-muted-foreground">{course.students_count} طالب</span>
                  </div>
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">{course.price} د.م</span>
                    <span className="flex items-center gap-1">
                      <Star className="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                      {stats.average_rating}
                    </span>
                  </div>
                </div>
              ))
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <TrendingUp className="h-5 w-5 text-primary" />
              نظرة عامة
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center justify-between border-b pb-3">
              <span className="text-sm">الدورات المنشورة</span>
              <span className="font-bold">{stats.courses_count}</span>
            </div>
            <div className="flex items-center justify-between border-b pb-3">
              <span className="text-sm">إجمالي الطلاب</span>
              <span className="font-bold text-green-600">+{stats.total_students}</span>
            </div>
            <div className="flex items-center justify-between border-b pb-3">
              <span className="text-sm">الإيرادات</span>
              <span className="font-bold">{stats.total_revenue} د.م</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-sm">متوسط التقييم</span>
              <span className="font-bold">{stats.average_rating} / 5.0</span>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
