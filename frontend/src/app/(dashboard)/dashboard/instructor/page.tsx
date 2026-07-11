"use client";

import Link from "next/link";
import { useAuth } from "@/providers/auth-provider";
import { PageHeader } from "@/components/shared/page-header";
import { PageLoading } from "@/components/shared/loading-spinner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { useQuery } from "@tanstack/react-query";
import { dashboardService } from "@/services/dashboard.service";
import {
  BookOpen,
  Users,
  DollarSign,
  Play,
  Plus,
  ArrowUpRight,
  TrendingUp,
  ChevronLeft,
  Clock,
} from "lucide-react";
import type { CoursePerformance, Enrollment, Course } from "@/types";

export default function InstructorDashboardPage() {
  const { user } = useAuth();

  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ["instructor-dashboard"],
    queryFn: dashboardService.getInstructorDashboard,
  });

  const { data: coursesData, isLoading: coursesLoading } = useQuery({
    queryKey: ["instructor-courses"],
    queryFn: dashboardService.getInstructorCourses,
  });

  const { data: enrollmentsData, isLoading: enrollmentsLoading } = useQuery({
    queryKey: ["instructor-recent-enrollments"],
    queryFn: dashboardService.getInstructorRecentEnrollments,
  });

  const { data: performance, isLoading: performanceLoading } = useQuery({
    queryKey: ["instructor-course-performance"],
    queryFn: dashboardService.getInstructorCoursePerformance,
  });

  if (statsLoading || coursesLoading || enrollmentsLoading || performanceLoading)
    return <PageLoading />;

  const courses = coursesData?.data ?? [];
  const recentEnrollments = enrollmentsData?.data ?? [];

  return (
    <div className="p-6 lg:p-10">
      <PageHeader
        title="لوحة المدرب"
        description={`مرحباً بعودتك، ${user?.name}`}
        actions={
          <Link href="/courses">
            <Button className="gap-2">
              <Plus className="h-4 w-4" />
              دورة جديدة
            </Button>
          </Link>
        }
      />

      <div className="mb-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          icon={BookOpen}
          label="الدورات"
          value={stats?.courses.total ?? 0}
          subValue={`${stats?.courses.published ?? 0} منشور`}
          color="text-blue-600 bg-blue-100"
        />
        <StatCard
          icon={Users}
          label="الطلاب"
          value={stats?.students.total ?? 0}
          subValue={`+${stats?.students.recent_enrollments ?? 0} هذا الأسبوع`}
          color="text-green-600 bg-green-100"
        />
        <StatCard
          icon={DollarSign}
          label="الإيرادات"
          value={`${stats?.revenue.total ?? 0} د.م`}
          color="text-orange-600 bg-orange-100"
        />
        <StatCard
          icon={Play}
          label="المحاضرات"
          value={stats?.content.total_lectures ?? 0}
          subValue={`في ${stats?.courses.total ?? 0} دورة`}
          color="text-purple-600 bg-purple-100"
        />
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle className="flex items-center gap-2 text-lg">
                <TrendingUp className="h-5 w-5 text-primary" />
                أداء الدورات
              </CardTitle>
              <Link
                href="/courses"
                className="flex items-center gap-1 text-sm text-primary hover:underline"
              >
                عرض الكل <ChevronLeft className="h-4 w-4" />
              </Link>
            </CardHeader>
            <CardContent>
              {performance && performance.length > 0 ? (
                <div className="space-y-4">
                  {performance.map((course) => (
                    <CoursePerformanceRow key={course.id} course={course} />
                  ))}
                </div>
              ) : (
                <div className="py-8 text-center text-muted-foreground">
                  <p>لا توجد دورات منشورة بعد</p>
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        <div>
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle className="flex items-center gap-2 text-lg">
                <Clock className="h-5 w-5 text-primary" />
                آخر التسجيلات
              </CardTitle>
            </CardHeader>
            <CardContent>
              {recentEnrollments.length > 0 ? (
                <div className="space-y-3">
                  {recentEnrollments.slice(0, 8).map((enrollment) => (
                    <EnrollmentRow key={enrollment.id} enrollment={enrollment} />
                  ))}
                </div>
              ) : (
                <div className="py-8 text-center text-muted-foreground">
                  <p>لا توجد تسجيلات بعد</p>
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>

      {courses.length > 0 && (
        <Card className="mt-6">
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="flex items-center gap-2 text-lg">
              جميع الدورات ({courses.length})
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {courses.map((course) => (
                <CourseRow key={course.id} course={course} />
              ))}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}

function StatCard({
  icon: Icon,
  label,
  value,
  subValue,
  color,
}: {
  icon: React.ElementType;
  label: string;
  value: string | number;
  subValue?: string;
  color: string;
}) {
  return (
    <Card>
      <CardContent className="flex items-center gap-4 p-6">
        <div className={`flex h-12 w-12 items-center justify-center rounded-full ${color}`}>
          <Icon className="h-6 w-6" />
        </div>
        <div>
          <p className="text-2xl font-bold">{value}</p>
          <p className="text-sm text-muted-foreground">{label}</p>
          {subValue && (
            <p className="mt-0.5 text-xs text-muted-foreground">{subValue}</p>
          )}
        </div>
      </CardContent>
    </Card>
  );
}

function CoursePerformanceRow({ course }: { course: CoursePerformance }) {
  return (
    <div className="flex items-center justify-between rounded-lg border p-4">
      <div className="min-w-0 flex-1">
        <p className="truncate font-medium">{course.title}</p>
        <div className="mt-1 flex items-center gap-3 text-sm text-muted-foreground">
          <span>{course.enrollments_count} طالب</span>
          <span>{course.lectures_count} محاضرة</span>
          <span>{course.price} د.م</span>
        </div>
      </div>
      <Link href={`/courses/${course.id}`} className="mr-4 text-primary hover:text-primary/80">
        <ArrowUpRight className="h-5 w-5" />
      </Link>
    </div>
  );
}

function EnrollmentRow({ enrollment }: { enrollment: Enrollment }) {
  return (
    <div className="flex items-center justify-between border-b pb-3 last:border-0 last:pb-0">
      <div className="min-w-0">
        <p className="truncate text-sm font-medium">{enrollment.course?.title || "دورة"}</p>
        <p className="text-xs text-muted-foreground">
          {enrollment.source === "purchase" ? "شراء" : "يدوي"}
        </p>
      </div>
      <span className="mr-2 whitespace-nowrap text-xs text-muted-foreground">
        {enrollment.created_at
          ? new Date(enrollment.created_at).toLocaleDateString("ar-SA")
          : ""}
      </span>
    </div>
  );
}

function CourseRow({ course }: { course: Course }) {
  return (
    <div className="flex items-center justify-between rounded-lg border p-4">
      <div className="min-w-0 flex-1">
        <div className="flex items-center gap-2">
          <p className="truncate font-medium">{course.title}</p>
          <span
            className={`rounded-full px-2 py-0.5 text-xs ${
              course.status === "published"
                ? "bg-green-100 text-green-700"
                : course.status === "draft"
                  ? "bg-gray-100 text-gray-700"
                  : "bg-red-100 text-red-700"
            }`}
          >
            {course.status === "published"
              ? "منشور"
              : course.status === "draft"
                ? "مسودة"
                : "مؤرشف"}
          </span>
        </div>
        <div className="mt-1 flex items-center gap-4 text-sm text-muted-foreground">
          <span>{course.students_count ?? 0} طالب</span>
          <span>{course.sections_count ?? 0} قسم</span>
          <span>{course.price} د.م</span>
        </div>
      </div>
      <Link href={`/courses/${course.id}`} className="mr-4 text-primary hover:text-primary/80">
        <ArrowUpRight className="h-5 w-5" />
      </Link>
    </div>
  );
}
