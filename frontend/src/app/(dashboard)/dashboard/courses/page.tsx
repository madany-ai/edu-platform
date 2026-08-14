"use client";

import { useMyEnrollments } from "@/hooks/useEnrollment";
import { PageHeader } from "@/components/shared/page-header";
import { PageLoading } from "@/components/shared/loading-spinner";
import { Button } from "@/components/ui/button";
import { EmptyState } from "@/components/shared/empty-state";
import { ROUTES } from "@/lib/constants";
import Link from "next/link";
import { PlayCircle, Clock, BookOpen, GraduationCap, ChevronLeft } from "lucide-react";

export default function StudentCoursesPage() {
  const { data: enrollmentsData, isLoading, error, refetch } = useMyEnrollments();

  if (isLoading) return <PageLoading />;

  if (error) {
    return (
      <div className="p-6 lg:p-10 flex flex-col items-center justify-center min-h-[50vh] space-y-4">
        <p className="text-muted-foreground">فشل تحميل الدورات</p>
        <Button variant="outline" onClick={() => refetch()}>إعادة المحاولة</Button>
      </div>
    );
  }

  const enrollments = enrollmentsData?.data ?? [];

  return (
    <div className="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
      <PageHeader
        title="دوراتي التدريبية"
        description="واصل تعلمك وتصفح جميع المواد الدراسية التي سجلت بها"
      />

      {enrollments.length === 0 ? (
        <EmptyState
          title="لم تسجل في أي دورة بعد"
          description="تصفح جميع الدورات التدريبية المتاحة وابدأ مسيرتك التعليمية اليوم."
          action={
            <Link href={ROUTES.COURSES}>
              <Button>تصفح الدورات</Button>
            </Link>
          }
        />
      ) : (
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {enrollments.filter(e => e.course).map((enrollment) => {
            const course = enrollment.course;
            return (
              <div
                key={enrollment.id}
                className="glass-card overflow-hidden rounded-2xl border border-white/5 flex flex-col justify-between hover:translate-y-[-4px] transition-all"
              >
                <div className="aspect-video bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center relative">
                  {course.thumbnail ? (
                    <img src={course.thumbnail} alt={course.title} className="h-full w-full object-cover" />
                  ) : (
                    <BookOpen className="h-12 w-12 text-primary/40" />
                  )}
                  <span className="absolute bottom-3 right-3 text-xs font-semibold px-2 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20">
                    {enrollment.status === "active" ? "نشط" : enrollment.status}
                  </span>
                </div>
                <div className="p-5 flex-1 flex flex-col justify-between space-y-4">
                  <div className="space-y-2">
                    <h3 className="font-bold text-foreground text-lg line-clamp-1">{course.title}</h3>
                    <p className="text-xs text-muted-foreground line-clamp-2">
                      {course.description || "لا يوجد وصف متوفر لهذه الدورة التدريبية حالياً."}
                    </p>
                  </div>

                  <div className="flex items-center justify-between text-xs text-muted-foreground border-t border-white/5 pt-3">
                    <span className="flex items-center gap-1">
                      <GraduationCap className="h-4 w-4" />
                      {course.instructor?.name || "معلم المادة"}
                    </span>
                    <span className="text-[10px]">
                      سجل في: {enrollment.created_at
                        ? new Date(enrollment.created_at).toLocaleDateString("ar-SA")
                        : ""}
                    </span>
                  </div>

                  <Link href={`/courses/${course.id}/play`} className="w-full">
                    <Button className="w-full gap-2 mt-2 bg-gradient-to-r from-primary to-secondary text-primary-foreground font-bold">
                      متابعة التعلم
                      <ChevronLeft className="h-4 w-4" />
                    </Button>
                  </Link>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
