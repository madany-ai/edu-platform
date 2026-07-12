"use client";

import { BookOpen, CheckCircle, ExternalLink, Timer } from "lucide-react";
import { Button } from "@/components/ui/button";
import Link from "next/link";
import { ROUTES } from "@/lib/constants";
import { useQuery } from "@tanstack/react-query";
import api from "@/services/api.client";
import { PageLoading } from "@/components/shared/loading-spinner";

export default function ExamsPage() {
  const { data: response, isLoading } = useQuery({
    queryKey: ["my-attempts"],
    queryFn: () => api.get("/my-attempts").then((res) => res.data),
  });

  if (isLoading) return <PageLoading />;

  const attempts = response?.data || [];

  return (
    <div className="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
      <div className="p-6 rounded-2xl bg-linear-to-r from-primary/10 via-secondary/5 to-transparent border border-white/5">
        <h1 className="text-3xl font-extrabold text-gradient mb-2">
          الامتحانات والواجبات 📝
        </h1>
        <p className="text-sm text-muted-foreground">
          استعرض درجاتك في الامتحانات والواجبات التي قمت بإتمامها.
        </p>
      </div>

      {attempts.length === 0 ? (
        <div className="glass-card p-12 rounded-2xl text-center border border-white/5 max-w-2xl mx-auto space-y-4">
          <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 border border-primary/20 text-primary mx-auto cosmic-border-glow">
            <BookOpen className="h-8 w-8" />
          </div>
          <div className="space-y-2">
            <h3 className="text-lg font-bold text-foreground">لا توجد امتحانات مكتملة بعد</h3>
            <p className="text-sm text-muted-foreground max-w-md mx-auto leading-relaxed">
              بمجرد إتمام الامتحانات والواجبات المرفقة مع الكورسات، ستتمكن من مراجعة درجاتك وتفاصيلها من هنا.
            </p>
          </div>
          <div className="pt-2">
            <Link href={ROUTES.COURSES}>
              <Button className="bg-linear-to-r from-primary to-secondary text-primary-foreground font-bold">
                تصفح الكورسات وابدأ التعلم
              </Button>
            </Link>
          </div>
        </div>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {attempts.map((attempt: any) => {
            const exam = attempt.exam;
            const lecture = exam?.lecture;
            const course = lecture?.section?.course;
            const degree = attempt.score ?? 0;
            const totalDegree = exam?.total_score ?? 100;
            const percentage = Math.round((degree / totalDegree) * 100);

            let scoreColor = "text-red-400";
            if (percentage >= 85) scoreColor = "text-green-400";
            else if (percentage >= 50) scoreColor = "text-yellow-400";

            return (
              <div key={attempt.id} className="glass-card p-5 rounded-2xl border border-white/5 flex flex-col gap-4 hover:border-primary/20 transition-colors">
                <div className="flex justify-between items-start">
                  <div>
                    <h3 className="font-bold text-foreground line-clamp-1">{exam?.title || (exam?.is_assignment ? "واجب بدون عنوان" : "امتحان بدون عنوان")}</h3>
                    <p className="text-xs text-muted-foreground mt-1 line-clamp-1">
                      {course?.title} - {lecture?.title}
                    </p>
                  </div>
                  <div className={`font-black text-xl ${scoreColor}`}>
                    {percentage}%
                  </div>
                </div>

                <div className="flex items-center gap-4 text-xs text-muted-foreground border-t border-white/5 pt-3">
                  <div className="flex items-center gap-1">
                    <CheckCircle className="h-3 w-3 text-primary" />
                    الدرجة: {degree} / {totalDegree}
                  </div>
                  <div className="flex items-center gap-1">
                    <Timer className="h-3 w-3 text-secondary" />
                    {new Date(attempt.submitted_at).toLocaleDateString("ar-SA")}
                  </div>
                </div>

                {course && lecture && (
                  <Link href={`/courses/${course.id}/lectures/${lecture.id}?tab=${exam?.is_assignment ? 'assignment' : 'quiz'}&exam_id=${exam.id}`} className="mt-auto">
                    <Button variant="outline" size="sm" className="w-full gap-2 border-primary/20 text-primary hover:bg-primary/10">
                      مراجعة الإجابات
                      <ExternalLink className="h-3 w-3" />
                    </Button>
                  </Link>
                )}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
