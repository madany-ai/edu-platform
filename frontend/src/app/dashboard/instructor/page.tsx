"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { useAuth } from "@/contexts/auth-context";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  getInstructorDashboard,
  getInstructorCourses,
  getInstructorRecentEnrollments,
  getInstructorCoursePerformance,
} from "@/lib/api/dashboard";
import type {
  InstructorDashboardStats,
  Course,
  Enrollment,
  CoursePerformance,
} from "@/lib/types";
import {
  Loader2,
  BookOpen,
  Users,
  DollarSign,
  Play,
  Plus,
  Eye,
  TrendingUp,
  ChevronLeft,
  Clock,
  ArrowUpRight,
} from "lucide-react";

export default function InstructorDashboardPage() {
  const { user, loading: authLoading } = useAuth();
  const router = useRouter();

  const [stats, setStats] = useState<InstructorDashboardStats | null>(null);
  const [courses, setCourses] = useState<Course[]>([]);
  const [recentEnrollments, setRecentEnrollments] = useState<Enrollment[]>([]);
  const [performance, setPerformance] = useState<CoursePerformance[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (authLoading) return;
    if (!user) {
      router.push("/login");
      return;
    }

    Promise.all([
      getInstructorDashboard(),
      getInstructorCourses(),
      getInstructorRecentEnrollments(),
      getInstructorCoursePerformance(),
    ])
      .then(([s, c, e, p]) => {
        setStats(s);
        setCourses(c);
        setRecentEnrollments(e);
        setPerformance(p);
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, [user, authLoading, router]);

  if (authLoading || loading) {
    return (
      <div className="flex min-h-[60vh] items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!stats) return null;

  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      {/* Header */}
      <div className="mb-8 flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold mb-1">لوحة المدرب</h1>
          <p className="text-muted-foreground">مرحباً بعودتك، {user?.name}</p>
        </div>
        <Link href="/courses">
          <Button className="gap-2">
            <Plus className="h-4 w-4" />
            دورة جديدة
          </Button>
        </Link>
      </div>

      {/* Stats Cards */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-10">
        <StatCard
          icon={BookOpen}
          label="الدورات"
          value={stats.courses.total}
          subValue={`${stats.courses.published} منشور`}
          color="text-blue-600 bg-blue-100"
        />
        <StatCard
          icon={Users}
          label="الطلاب"
          value={stats.students.total}
          subValue={`+${stats.students.recent_enrollments} هذا الأسبوع`}
          color="text-green-600 bg-green-100"
        />
        <StatCard
          icon={DollarSign}
          label="الإيرادات"
          value={`${stats.revenue.total} د.م`}
          color="text-orange-600 bg-orange-100"
        />
        <StatCard
          icon={Play}
          label="المحاضرات"
          value={stats.content.total_lectures}
          subValue={`في ${stats.courses.total} دورة`}
          color="text-purple-600 bg-purple-100"
        />
      </div>

      {/* Main Content Grid */}
      <div className="grid gap-6 lg:grid-cols-3">
        {/* Course Performance - Takes 2 columns */}
        <div className="lg:col-span-2">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle className="flex items-center gap-2 text-lg">
                <TrendingUp className="h-5 w-5 text-primary" />
                أداء الدورات
              </CardTitle>
              <Link
                href="/courses"
                className="text-sm text-primary hover:underline flex items-center gap-1"
              >
                عرض الكل <ChevronLeft className="h-4 w-4" />
              </Link>
            </CardHeader>
            <CardContent>
              {performance.length === 0 ? (
                <EmptyState
                  message="لا توجد دورات منشورة بعد"
                  actionLabel="إنشاء دورة"
                  actionHref="/courses"
                />
              ) : (
                <div className="space-y-4">
                  {performance.map((course) => (
                    <CoursePerformanceRow key={course.id} course={course} />
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Recent Enrollments - Takes 1 column */}
        <div>
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle className="flex items-center gap-2 text-lg">
                <Clock className="h-5 w-5 text-primary" />
                آخر التسجيلات
              </CardTitle>
            </CardHeader>
            <CardContent>
              {recentEnrollments.length === 0 ? (
                <EmptyState message="لا توجد تسجيلات بعد" />
              ) : (
                <div className="space-y-3">
                  {recentEnrollments.slice(0, 8).map((enrollment) => (
                    <EnrollmentRow key={enrollment.id} enrollment={enrollment} />
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>

      {/* All Courses */}
      {courses.length > 0 && (
        <Card className="mt-6">
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="flex items-center gap-2 text-lg">
              <Eye className="h-5 w-5 text-primary" />
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

/* ─── Sub-components ─── */

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
      <CardContent className="p-6 flex items-center gap-4">
        <div
          className={`flex h-12 w-12 items-center justify-center rounded-full ${color}`}
        >
          <Icon className="h-6 w-6" />
        </div>
        <div>
          <p className="text-2xl font-bold">{value}</p>
          <p className="text-sm text-muted-foreground">{label}</p>
          {subValue && (
            <p className="text-xs text-muted-foreground mt-0.5">{subValue}</p>
          )}
        </div>
      </CardContent>
    </Card>
  );
}

function CoursePerformanceRow({ course }: { course: CoursePerformance }) {
  return (
    <div className="flex items-center justify-between rounded-lg border p-4">
      <div className="flex-1 min-w-0">
        <p className="font-medium truncate">{course.title}</p>
        <div className="flex items-center gap-3 mt-1 text-sm text-muted-foreground">
          <span>{course.enrollments_count} طالب</span>
          <span>{course.lectures_count} محاضرة</span>
          <span>{course.price} د.م</span>
        </div>
      </div>
      <Link
        href={`/courses/${course.id}`}
        className="mr-4 text-primary hover:text-primary/80"
      >
        <ArrowUpRight className="h-5 w-5" />
      </Link>
    </div>
  );
}

function EnrollmentRow({ enrollment }: { enrollment: Enrollment }) {
  return (
    <div className="flex items-center justify-between border-b pb-3 last:border-0 last:pb-0">
      <div className="min-w-0">
        <p className="text-sm font-medium truncate">
          {enrollment.course?.title || "دورة"}
        </p>
        <p className="text-xs text-muted-foreground">
          {enrollment.source === "purchase" ? "شراء" : "يدوي"}
        </p>
      </div>
      <span className="text-xs text-muted-foreground whitespace-nowrap mr-2">
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
      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-2">
          <p className="font-medium truncate">{course.title}</p>
          <span
            className={`text-xs px-2 py-0.5 rounded-full ${
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
        <div className="flex items-center gap-4 mt-1 text-sm text-muted-foreground">
          <span>{course.students_count ?? 0} طالب</span>
          <span>{course.sections_count ?? 0} قسم</span>
          <span>{course.price} د.م</span>
        </div>
      </div>
      <Link
        href={`/courses/${course.id}`}
        className="mr-4 text-primary hover:text-primary/80"
      >
        <ArrowUpRight className="h-5 w-5" />
      </Link>
    </div>
  );
}

function EmptyState({
  message,
  actionLabel,
  actionHref,
}: {
  message: string;
  actionLabel?: string;
  actionHref?: string;
}) {
  return (
    <div className="text-center py-8 text-muted-foreground">
      <p>{message}</p>
      {actionLabel && actionHref && (
        <Link href={actionHref}>
          <Button variant="outline" className="mt-4 gap-2">
            <Plus className="h-4 w-4" />
            {actionLabel}
          </Button>
        </Link>
      )}
    </div>
  );
}
