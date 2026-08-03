"use client";

import { useMyEntitlements } from "@/hooks/useEnrollment";
import { PageHeader } from "@/components/shared/page-header";
import { PageLoading } from "@/components/shared/loading-spinner";
import { Button } from "@/components/ui/button";
import { EmptyState } from "@/components/shared/empty-state";
import { ROUTES } from "@/lib/constants";
import Link from "next/link";
import { PlayCircle, Clock, GraduationCap, ChevronLeft, BookOpen } from "lucide-react";

export default function StudentLecturesPage() {
  const { data: entitlements, isLoading, error, refetch } = useMyEntitlements();

  if (isLoading) return <PageLoading />;

  if (error) {
    return (
      <div className="p-6 lg:p-10 flex flex-col items-center justify-center min-h-[50vh] space-y-4">
        <p className="text-muted-foreground">فشل تحميل المحاضرات الخاصة بك</p>
        <Button variant="outline" onClick={() => refetch()}>إعادة المحاولة</Button>
      </div>
    );
  }

  // Filter entitlements that have a valid lecture associated
  const validEntitlements = entitlements?.filter((e: any) => e.lecture) ?? [];

  return (
    <div className="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
      <PageHeader
        title="محاضراتي المستقلة 🧪"
        description="تصفح جميع المحاضرات التي تمتلك صلاحية مشاهدتها مباشرة"
      />

      {validEntitlements.length === 0 ? (
        <EmptyState
          title="لا توجد محاضرات في حسابك حالياً"
          description="تصفح المتجر الأكاديمي لشراء المحاضرات الفردية أو الباقات والاستفادة منها مباشرة."
          action={
            <Link href={ROUTES.COURSES}>
              <Button className="bg-primary hover:bg-primary-hover text-primary-foreground font-bold">
                تصفح المتجر الأكاديمي
              </Button>
            </Link>
          }
        />
      ) : (
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {validEntitlements.map((entitlement: any) => {
            const lecture = entitlement.lecture;
            const courseTitle = lecture?.section?.course?.title;
            const instructorName = lecture?.instructor?.name || "معلم المادة";
            const duration = lecture?.video?.duration || lecture?.duration || 0;

            return (
              <div
                key={entitlement.id}
                className="glass-card overflow-hidden rounded-2xl border border-[#3b413c] flex flex-col justify-between hover:translate-y-[-4px] transition-all"
              >
                <div className="aspect-video bg-gradient-to-br from-primary/20 via-slate-900 to-primary/5 flex items-center justify-center relative">
                  <PlayCircle className="h-16 w-16 text-primary/80 science-glow-text" />
                  <span className="absolute bottom-3 right-3 text-[10px] font-bold px-2 py-0.5 rounded-full bg-primary/20 text-primary border border-primary/30 backdrop-blur-sm">
                    محاضرة متاحة 🟢
                  </span>
                </div>

                <div className="p-5 flex-1 flex flex-col justify-between space-y-4">
                  <div className="space-y-2">
                    {courseTitle && (
                      <span className="text-[10px] font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md inline-block">
                        من كورس: {courseTitle}
                      </span>
                    )}
                    <h3 className="font-bold text-foreground text-lg line-clamp-1">{lecture.title}</h3>
                    <p className="text-xs text-muted-foreground line-clamp-2 leading-relaxed">
                      {lecture.description || "لا يوجد وصف إضافي لهذه المحاضرة حالياً."}
                    </p>
                  </div>

                  <div className="flex items-center justify-between text-xs text-muted-foreground border-t border-white/5 pt-3">
                    <span className="flex items-center gap-1.5">
                      <GraduationCap className="h-4 w-4 text-primary" />
                      {instructorName}
                    </span>
                    {duration > 0 && (
                      <span className="flex items-center gap-1 text-[11px]">
                        <Clock className="h-3.5 w-3.5 text-primary" />
                        {duration} دقيقة
                      </span>
                    )}
                  </div>

                  <Link href={`/lectures/${lecture.id}`} className="w-full">
                    <Button className="w-full gap-2 mt-2 bg-primary hover:bg-primary-hover text-primary-foreground font-bold rounded-xl shadow-md shadow-primary/15">
                      مشاهدة المحاضرة الآن
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
