"use client";

import { useState, useEffect } from "react";
import { centerService, CenterExam, ExamGradeRecord } from "@/services/center.service";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { FileSpreadsheet, Save, Loader2, CheckCircle2, AlertCircle, Award } from "lucide-react";

interface GradeMatrixProps {
  exams: CenterExam[];
  onGradesSaved?: () => void;
}

export function CenterGradeMatrix({ exams, onGradesSaved }: GradeMatrixProps) {
  const [selectedExamId, setSelectedExamId] = useState<string>(exams[0]?.id || "");
  const [loading, setLoading] = useState<boolean>(false);
  const [saving, setSaving] = useState<boolean>(false);
  const [exam, setExam] = useState<CenterExam | null>(null);
  const [grades, setGrades] = useState<ExamGradeRecord[]>([]);
  const [successMessage, setSuccessMessage] = useState<string>("");
  const [errorMessage, setErrorMessage] = useState<string>("");

  useEffect(() => {
    if (exams.length > 0 && !selectedExamId) {
      setSelectedExamId(exams[0].id);
    }
  }, [exams, selectedExamId]);

  useEffect(() => {
    if (selectedExamId) {
      loadGrades(selectedExamId);
    }
  }, [selectedExamId]);

  const loadGrades = async (examId: string) => {
    setLoading(true);
    setErrorMessage("");
    setSuccessMessage("");
    try {
      const res = await centerService.getExamGrades(examId);
      setExam(res.exam);
      setGrades(res.grades);
    } catch (e: any) {
      setErrorMessage("حدث خطأ أثناء تحميل كشف الدرجات.");
    } finally {
      setLoading(false);
    }
  };

  const handleScoreChange = (studentId: string, val: string) => {
    const score = parseFloat(val) || 0;
    setGrades((prev) =>
      prev.map((g) => (g.student_id === studentId ? { ...g, score } : g))
    );
  };

  const handleNotesChange = (studentId: string, notes: string) => {
    setGrades((prev) =>
      prev.map((g) => (g.student_id === studentId ? { ...g, notes } : g))
    );
  };

  const handleSaveGrades = async () => {
    if (!selectedExamId) return;
    setSaving(true);
    setSuccessMessage("");
    setErrorMessage("");

    try {
      const payload = grades.map((g) => ({
        student_id: g.student_id,
        score: g.score,
        notes: g.notes,
      }));
      await centerService.saveExamGrades(selectedExamId, payload);
      setSuccessMessage("تم حفظ درجات الامتحان بنجاح وصدرت الإشعارات لأولياء الأمور! 🌟");
      if (onGradesSaved) onGradesSaved();
    } catch (e: any) {
      setErrorMessage("حدث خطأ أثناء حفظ الدرجات. يرجى المحاولة لاحقاً.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header Selector & Save Action */}
      <div className="glass-card p-6 rounded-2xl border border-primary/20 bg-gradient-to-r from-primary/5 via-background to-secondary/5">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h3 className="text-lg font-bold flex items-center gap-2 text-foreground">
              <FileSpreadsheet className="h-6 w-6 text-primary" />
              <span>مصفوفة رصد درجات الامتحانات الورقية</span>
            </h3>
            <p className="text-xs text-muted-foreground mt-1">
              اختر الامتحان ورصّد درجات الطلاب بسرعة وسهولة وسيتم إشعار ولي الأمر بالدرجة فوراً.
            </p>
          </div>

          <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div className="w-full sm:w-64">
              <select
                value={selectedExamId}
                onChange={(e) => setSelectedExamId(e.target.value)}
                className="w-full h-10 rounded-lg bg-background border border-border px-3 text-sm font-medium focus:ring-2 focus:ring-primary"
              >
                {exams.length === 0 ? (
                  <option value="">لا توجد امتحانات ورقية</option>
                ) : (
                  exams.map((ex) => (
                    <option key={ex.id} value={ex.id}>
                      {ex.name} ({ex.total_marks} درجة)
                    </option>
                  ))
                )}
              </select>
            </div>

            <Button
              onClick={handleSaveGrades}
              disabled={saving || loading || grades.length === 0}
              className="h-10 px-6 font-bold gap-2 bg-gradient-to-r from-primary to-secondary hover:opacity-90 shadow-md"
            >
              {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
              حفظ الدرجات ورصد الإشعارات
            </Button>
          </div>
        </div>
      </div>

      {successMessage && (
        <div className="glass-card p-4 rounded-xl border border-emerald-500/40 bg-emerald-500/10 text-emerald-400 text-sm font-bold flex items-center gap-2">
          <CheckCircle2 className="h-5 w-5" />
          <span>{successMessage}</span>
        </div>
      )}

      {errorMessage && (
        <div className="glass-card p-4 rounded-xl border border-destructive/40 bg-destructive/10 text-destructive text-sm font-bold flex items-center gap-2">
          <AlertCircle className="h-5 w-5" />
          <span>{errorMessage}</span>
        </div>
      )}

      {/* Grade Entry Table */}
      <div className="glass-card rounded-2xl border border-border overflow-hidden">
        {loading ? (
          <div className="py-16 text-center text-muted-foreground flex flex-col items-center gap-2">
            <Loader2 className="h-8 w-8 animate-spin text-primary" />
            <span className="text-xs">جاري تحميل الطلاب والدرجات...</span>
          </div>
        ) : grades.length === 0 ? (
          <div className="py-16 text-center text-muted-foreground text-xs">
            يرجى اختيار امتحان لعرض جدول الطلاب ورصد الدرجات.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-right text-sm border-collapse">
              <thead>
                <tr className="bg-muted/50 border-b border-border text-xs font-bold text-muted-foreground">
                  <th className="py-3.5 px-4">#</th>
                  <th className="py-3.5 px-4">كود الطالب</th>
                  <th className="py-3.5 px-4">اسم الطالب</th>
                  <th className="py-3.5 px-4">الدرجة المكتسبة (من {exam?.total_marks})</th>
                  <th className="py-3.5 px-4">النسبة المئوية</th>
                  <th className="py-3.5 px-4">ملاحظات للمعلم / هاتف ولي الأمر</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border/40">
                {grades.map((g, idx) => {
                  const maxM = exam?.total_marks || 1;
                  const pct = Math.round(((g.score || 0) / maxM) * 100);
                  return (
                    <tr key={g.student_id} className="hover:bg-muted/30 transition-colors">
                      <td className="py-3 px-4 font-mono text-xs text-muted-foreground">{idx + 1}</td>
                      <td className="py-3 px-4 font-mono text-xs text-primary font-semibold">{g.student_code}</td>
                      <td className="py-3 px-4 font-bold text-foreground">{g.full_name}</td>
                      <td className="py-3 px-4">
                        <div className="flex items-center gap-2 w-36">
                          <Input
                            type="number"
                            step="0.5"
                            min="0"
                            max={maxM}
                            value={g.score}
                            onChange={(e) => handleScoreChange(g.student_id, e.target.value)}
                            className="h-9 font-mono font-bold text-center text-base"
                          />
                          <span className="text-xs text-muted-foreground font-semibold">/ {maxM}</span>
                        </div>
                      </td>
                      <td className="py-3 px-4">
                        <span
                          className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold ${
                            pct >= 85
                              ? "bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
                              : pct >= 60
                              ? "bg-blue-500/10 text-blue-400 border border-blue-500/20"
                              : "bg-destructive/10 text-destructive border border-destructive/20"
                          }`}
                        >
                          {pct}%
                        </span>
                      </td>
                      <td className="py-3 px-4">
                        <Input
                          placeholder="ملاحظات المدرس للطالب..."
                          value={g.notes || ""}
                          onChange={(e) => handleNotesChange(g.student_id, e.target.value)}
                          className="h-9 text-xs"
                        />
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
