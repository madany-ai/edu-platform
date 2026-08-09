"use client";

import { useState, useEffect } from "react";
import { centerService, StudentReport } from "@/services/center.service";
import QRCode from "react-qr-code";
import { Button } from "@/components/ui/button";
import { X, User, Phone, MessageSquare, Calendar, Award, CheckCircle2, XCircle, Clock, AlertTriangle, Loader2 } from "lucide-react";

interface StudentReportModalProps {
  studentId: string | null;
  onClose: () => void;
}

export function StudentReportModal({ studentId, onClose }: StudentReportModalProps) {
  const [report, setReport] = useState<StudentReport | null>(null);
  const [loading, setLoading] = useState<boolean>(true);

  useEffect(() => {
    if (studentId) {
      loadReport(studentId);
    }
  }, [studentId]);

  const loadReport = async (id: string) => {
    setLoading(true);
    try {
      const data = await centerService.getStudentReport(id);
      setReport(data);
    } catch (e) {
      console.error("Failed to load student report", e);
    } finally {
      setLoading(false);
    }
  };

  if (!studentId) return null;

  return (
    <div className="fixed inset-0 z-50 bg-background/80 backdrop-blur-md flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200">
      <div className="glass-card w-full max-w-3xl rounded-3xl border border-primary/20 p-6 sm:p-8 space-y-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        {/* Close Button */}
        <button
          onClick={onClose}
          className="absolute top-5 left-5 p-2 rounded-full bg-muted hover:bg-muted/80 text-muted-foreground hover:text-foreground transition-all z-10"
        >
          <X className="h-5 w-5" />
        </button>

        {loading || !report ? (
          <div className="py-20 text-center flex flex-col items-center gap-3">
            <Loader2 className="h-10 w-10 animate-spin text-primary" />
            <span className="text-sm font-medium text-muted-foreground">جاري تحميل تقرير الطالب الشامل...</span>
          </div>
        ) : (
          <>
            {/* Student Header Info */}
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-border/60 pb-6">
              <div className="flex items-center gap-4">
                {report.student.student_code ? (
                  <div className="bg-white p-2 rounded-xl shadow-lg border border-border shrink-0">
                    <QRCode value={report.student.student_code} size={64} className="rounded-sm" />
                  </div>
                ) : (
                  <div className="h-16 w-16 rounded-2xl bg-gradient-to-tr from-primary to-secondary flex items-center justify-center text-primary-foreground font-bold text-2xl shadow-lg shrink-0">
                    {report.student.first_name?.[0]}
                  </div>
                )}
                <div>
                  <h3 className="text-xl font-extrabold text-foreground">
                    {report.student.first_name} {report.student.second_name} {report.student.third_name} {report.student.last_name}
                  </h3>
                  <div className="flex items-center gap-3 text-xs text-muted-foreground mt-1 font-medium">
                    <span className="font-mono bg-primary/10 text-primary px-2 py-0.5 rounded font-bold">
                      كود: {report.student.student_code}
                    </span>
                    <span>• {report.student.group?.name || "بدون مجموعة"}</span>
                    <span>• {report.student.grade_level?.name}</span>
                  </div>
                </div>
              </div>

              {/* Action Contact Buttons */}
              <div className="flex items-center gap-2">
                {report.student.father_phone && (
                  <>
                    <a
                      href={`tel:${report.student.father_phone}`}
                      className="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-all"
                    >
                      <Phone className="h-4 w-4" />
                      <span>اتصال بالأب</span>
                    </a>
                    <a
                      href={`https://wa.me/${report.student.father_phone.replace(/[^\d]/g, "")}`}
                      target="_blank"
                      rel="noreferrer"
                      className="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition-all"
                    >
                      <MessageSquare className="h-4 w-4" />
                      <span>واتساب</span>
                    </a>
                  </>
                )}
              </div>
            </div>

            {/* Quick Stats Grid */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div className="glass-card p-3.5 rounded-2xl border border-border text-center">
                <span className="text-[11px] text-muted-foreground font-semibold block mb-1">نسبة المتفوق</span>
                <span className="text-xl font-extrabold text-amber-500">{report.stats.percentage}%</span>
              </div>
              <div className="glass-card p-3.5 rounded-2xl border border-border text-center">
                <span className="text-[11px] text-muted-foreground font-semibold block mb-1">مرات الحضور</span>
                <span className="text-xl font-extrabold text-emerald-500">{report.stats.present_count}</span>
              </div>
              <div className="glass-card p-3.5 rounded-2xl border border-border text-center">
                <span className="text-[11px] text-muted-foreground font-semibold block mb-1">مرات الغياب</span>
                <span className="text-xl font-extrabold text-destructive">{report.stats.absent_count}</span>
              </div>
              <div className="glass-card p-3.5 rounded-2xl border border-border text-center">
                <span className="text-[11px] text-muted-foreground font-semibold block mb-1">الامتحانات المؤداة</span>
                <span className="text-xl font-extrabold text-primary">{report.stats.total_exams}</span>
              </div>
            </div>

            {/* Attendance & Grades Tabs */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
              {/* Attendance Log */}
              <div className="space-y-3">
                <h4 className="text-sm font-bold flex items-center gap-2">
                  <Calendar className="h-4 w-4 text-primary" />
                  سجل الحضور والغياب الأخيرة
                </h4>
                <div className="space-y-2 max-h-60 overflow-y-auto pr-1">
                  {report.attendances.length === 0 ? (
                    <p className="text-xs text-muted-foreground text-center py-6">لا يوجد سجل حضور مسجل بعد.</p>
                  ) : (
                    report.attendances.map((att: any) => (
                      <div
                        key={att.id}
                        className="flex items-center justify-between p-3 rounded-xl bg-background/60 border border-border/50 text-xs"
                      >
                        <div>
                          <p className="font-bold text-foreground">{att.session?.topic || "حصة دراسية"}</p>
                          <p className="text-[11px] text-muted-foreground">{att.session?.date || att.created_at?.slice(0, 10)}</p>
                        </div>
                        <span
                          className={`px-2.5 py-0.5 rounded-full font-bold text-[10px] ${
                            att.status === "present"
                              ? "bg-emerald-500/20 text-emerald-400"
                              : att.status === "late"
                              ? "bg-amber-500/20 text-amber-400"
                              : att.status === "guest"
                              ? "bg-blue-500/20 text-blue-400"
                              : "bg-destructive/20 text-destructive"
                          }`}
                        >
                          {att.status === "present"
                            ? "حاضر 🟢"
                            : att.status === "late"
                            ? "متأخر 🟡"
                            : att.status === "guest"
                            ? "ضيف 🔵"
                            : "غائب 🔴"}
                        </span>
                      </div>
                    ))
                  )}
                </div>
              </div>

              {/* Exam Grades Log */}
              <div className="space-y-3">
                <h4 className="text-sm font-bold flex items-center gap-2">
                  <Award className="h-4 w-4 text-amber-500" />
                  سجل درجات الامتحانات الورقية
                </h4>
                <div className="space-y-2 max-h-60 overflow-y-auto pr-1">
                  {report.grades.length === 0 ? (
                    <p className="text-xs text-muted-foreground text-center py-6">لا توجد درجات امتحانات مسجلة بعد.</p>
                  ) : (
                    report.grades.map((gr: any) => (
                      <div
                        key={gr.id}
                        className="flex items-center justify-between p-3 rounded-xl bg-background/60 border border-border/50 text-xs"
                      >
                        <div>
                          <p className="font-bold text-foreground">{gr.exam?.name || "امتحان سنتر"}</p>
                          <p className="text-[11px] text-muted-foreground">{gr.notes || "لا توجد ملاحظات"}</p>
                        </div>
                        <div className="text-left font-bold">
                          <span className="text-sm text-primary">{gr.score}</span>
                          <span className="text-[11px] text-muted-foreground"> / {gr.exam?.total_marks || 0}</span>
                        </div>
                      </div>
                    ))
                  )}
                </div>
              </div>
            </div>
          </>
        )}
      </div>
    </div>
  );
}
