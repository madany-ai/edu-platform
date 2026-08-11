"use client";

import { useEffect, useState } from "react";
import api from "@/services/api.client";
import { PageHeader } from "@/components/shared/page-header";
import { PageLoading } from "@/components/shared/loading-spinner";
import { ErrorState } from "@/components/shared/error-state";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Award, FileText, TrendingUp } from "lucide-react";

interface CenterGradeRecord {
  id: string;
  exam_name: string;
  total_marks: number;
  score: number;
  percentage: number;
  date: string;
  notes?: string;
}

export default function StudentCenterGradesPage() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [grades, setGrades] = useState<CenterGradeRecord[]>([]);

  useEffect(() => {
    async function fetchGrades() {
      try {
        setLoading(true);
        const { data } = await api.get("/center/my-grades");
        if (data.status === "success") {
          setGrades(data.data);
        }
      } catch (err: any) {
        setError(err.response?.data?.message || "فشل في تحميل درجات الامتحانات الورقية");
      } finally {
        setLoading(false);
      }
    }
    fetchGrades();
  }, []);

  if (loading) return <PageLoading />;
  if (error) return <ErrorState message={error} />;

  const getScoreBadge = (percentage: number) => {
    if (percentage >= 85) {
      return <Badge className="bg-emerald-500/10 text-emerald-500 border-emerald-500/20">ممتاز 🌟 ({percentage}%)</Badge>;
    } else if (percentage >= 70) {
      return <Badge className="bg-blue-500/10 text-blue-500 border-blue-500/20">جيد جداً 👍 ({percentage}%)</Badge>;
    } else if (percentage >= 50) {
      return <Badge className="bg-amber-500/10 text-amber-500 border-amber-500/20">مقبول 👌 ({percentage}%)</Badge>;
    } else {
      return <Badge className="bg-red-500/10 text-red-500 border-red-500/20">ضعيف ⚠️ ({percentage}%)</Badge>;
    }
  };

  const avgPercentage = grades.length > 0
    ? roundTo(grades.reduce((sum, g) => sum + g.percentage, 0) / grades.length, 1)
    : 0;

  function roundTo(num: number, decimals: number) {
    return Math.round(num * Math.pow(10, decimals)) / Math.pow(10, decimals);
  }

  return (
    <div className="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
      <PageHeader
        title="نتائج الامتحانات الورقية (السنتر)"
        description="عرض درجاتك في الاختبارات الدورية والشهرية بالسنتر"
      />

      {/* Summary Cards */}
      <div className="grid gap-4 md:grid-cols-2">
        <Card className="border-primary/10 bg-primary/5">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">عدد الامتحانات المسجلة</CardTitle>
            <FileText className="h-4 w-4 text-primary" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{grades.length}</div>
          </CardContent>
        </Card>

        <Card className="border-emerald-500/10 bg-emerald-500/5">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">متوسط النسبة المئوية</CardTitle>
            <TrendingUp className="h-4 w-4 text-emerald-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-emerald-500">{avgPercentage}%</div>
          </CardContent>
        </Card>
      </div>

      {/* Grades List */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg flex items-center gap-2">
            <Award className="h-5 w-5 text-primary" />
            سجل الامتحانات والدرجات
          </CardTitle>
        </CardHeader>
        <CardContent>
          {grades.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              لا يوجد درجات امتحانات ورقية مسجلة حتى الآن.
            </div>
          ) : (
            <div className="divide-y">
              {grades.map((grade) => (
                <div key={grade.id} className="py-4 flex items-center justify-between">
                  <div className="space-y-1">
                    <p className="font-medium">{grade.exam_name}</p>
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                      <span>التاريخ: {grade.date}</span>
                      {grade.notes && (
                        <>
                          <span>•</span>
                          <span>ملاحظة: {grade.notes}</span>
                        </>
                      )}
                    </div>
                  </div>
                  <div className="text-left space-y-1">
                    <div className="text-lg font-bold">
                      {grade.score} <span className="text-xs text-muted-foreground">/ {grade.total_marks}</span>
                    </div>
                    <div>{getScoreBadge(grade.percentage)}</div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
