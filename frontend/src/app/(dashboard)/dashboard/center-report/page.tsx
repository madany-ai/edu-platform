"use client";

import { useState, useEffect } from "react";
import { useAuth } from "@/providers/auth-provider";
import api from "@/services/api.client";
import { PageLoading } from "@/components/shared/loading-spinner";
import { ErrorState } from "@/components/shared/error-state";
import QRCode from "react-qr-code";
import {
  Award,
  Calendar,
  CheckCircle2,
  XCircle,
  Clock,
  TrendingUp,
  UserCheck,
  Building2,
  Phone,
  QrCode,
  Printer,
} from "lucide-react";

export default function MyCenterReportPage() {
  const { user } = useAuth();
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadMyCenterReport();
  }, []);

  const loadMyCenterReport = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await api.get("/center/my-report");
      if (res.data && res.data.status === "success") {
        setData(res.data.data);
      } else {
        setError("فشل تحميل التقرير");
      }
    } catch (e: any) {
      setError(e.response?.data?.message || "عفواً، حدث خطأ أثناء تحميل التقرير. يرجى المحاولة لاحقاً.");
    } finally {
      setLoading(false);
    }
  };

  const handlePrintQR = () => {
    const svg = document.getElementById("qr-code-svg");
    if (!svg || !data?.student?.code) return;
    
    const svgData = new XMLSerializer().serializeToString(svg);
    const printWindow = window.open("", "_blank");
    if (!printWindow) return;
    
    printWindow.document.write(`
      <html dir="rtl">
        <head>
          <title>طباعة الباركود - ${user?.name}</title>
          <style>
            body { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; font-family: system-ui, sans-serif; background: #fff; color: #000; }
            .card { border: 2px dashed #ccc; padding: 2.5rem; border-radius: 1.5rem; text-align: center; }
            .title { font-size: 1rem; color: #666; margin-bottom: 0.5rem; }
            .name { font-size: 1.5rem; font-weight: bold; margin-bottom: 1.5rem; }
            .qr-container { display: flex; justify-content: center; }
            .qr-container svg { width: 150px; height: 150px; }
            .code { font-family: monospace; font-size: 1.5rem; font-weight: bold; margin-top: 1.5rem; letter-spacing: 0.15em; }
            @media print {
              body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
              .card { border: 2px solid #000; }
            }
          </style>
        </head>
        <body>
          <div class="card">
            <div class="title">الباركود الخاص بالطالب</div>
            <div class="name">${user?.name}</div>
            <div class="qr-container">${svgData}</div>
            <div class="code">${data.student.code}</div>
          </div>
          <script>
            window.onload = () => {
              window.print();
              setTimeout(() => window.close(), 500);
            };
          </script>
        </body>
      </html>
    `);
    printWindow.document.close();
  };

  if (loading) return <PageLoading />;
  if (error) return <ErrorState message={error} />;

  const stats = data?.stats || { total_sessions: 0, present_count: 0, absent_count: 0, late_count: 0, percentage: 0 };
  const student = data?.student;
  const attendances = data?.attendances || [];
  const grades = data?.grades || [];

  return (
    <div className="w-full max-w-7xl mx-auto px-4 py-8 space-y-8">
      {/* Banner */}
      <div className="glass-card p-8 rounded-3xl border border-primary/20 bg-gradient-to-r from-primary/10 via-background to-secondary/10 relative overflow-hidden">
        <div className="flex flex-col md:flex-row items-start justify-between gap-6">
          <div className="space-y-2 max-w-xl">
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-primary/20 text-primary">
              <Building2 className="h-4 w-4" />
              <span>تقرير السنتر الأوفلاين الخاص بي</span>
            </div>
            <h1 className="text-3xl font-extrabold text-gradient">مرحباً بك، {user?.name} 👋</h1>
            <p className="text-xs text-muted-foreground leading-relaxed">
              سجل حضورك وغيابك في حصص السنتر، درجات الامتحانات الورقية، ومؤشر التطور والمستوى الدراسي. 
              احتفظ بالكود السري (الباركود) الخاص بك لتسجيل الحضور بسهولة عن طريق ماسح الأكواد في السنتر.
            </p>
          </div>

          <div className="flex items-center gap-4">
            {/* QR Code Card */}
            {student?.code && (
              <div className="glass-card p-3 rounded-2xl border border-border bg-white text-center flex flex-col items-center justify-center">
                <QRCode id="qr-code-svg" value={student.code} size={90} className="rounded-md" />
                <span className="text-[10px] text-muted-foreground font-mono font-bold mt-2 tracking-wider">
                  {student.code}
                </span>
                <button
                  onClick={handlePrintQR}
                  className="mt-3 w-full flex items-center justify-center gap-1.5 bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors text-[11px] py-1.5 rounded-lg font-bold"
                >
                  <Printer className="h-3.5 w-3.5" />
                  طباعة الكود
                </button>
              </div>
            )}
            <div className="glass-card px-6 py-4 rounded-2xl border border-amber-500/30 text-center bg-amber-500/5 flex flex-col justify-center h-[130px]">
              <span className="text-xs text-muted-foreground font-semibold block mb-2">نسبة التميز والتفوق</span>
              <span className="text-4xl font-extrabold text-amber-500">{stats.percentage}%</span>
            </div>
          </div>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="glass-card p-5 rounded-2xl border border-border text-center">
          <span className="text-xs text-muted-foreground font-semibold block">إجمالي الحصص</span>
          <span className="text-2xl font-extrabold text-foreground mt-1 block">{stats.total_sessions}</span>
        </div>
        <div className="glass-card p-5 rounded-2xl border border-emerald-500/20 bg-emerald-500/5 text-center">
          <span className="text-xs text-emerald-400 font-semibold block">مرات الحضور 🟢</span>
          <span className="text-2xl font-extrabold text-emerald-500 mt-1 block">{stats.present_count}</span>
        </div>
        <div className="glass-card p-5 rounded-2xl border border-amber-500/20 bg-amber-500/5 text-center">
          <span className="text-xs text-amber-400 font-semibold block">مرات التأخير 🟡</span>
          <span className="text-2xl font-extrabold text-amber-500 mt-1 block">{stats.late_count}</span>
        </div>
        <div className="glass-card p-5 rounded-2xl border border-destructive/20 bg-destructive/5 text-center">
          <span className="text-xs text-destructive font-semibold block">مرات الغياب 🔴</span>
          <span className="text-2xl font-extrabold text-destructive mt-1 block">{stats.absent_count}</span>
        </div>
      </div>

      {/* Attendance & Grades Tables */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* Attendance Log */}
        <div className="lg:col-span-6 glass-card p-6 rounded-2xl border border-border space-y-4">
          <h3 className="text-sm font-bold flex items-center gap-2">
            <Calendar className="h-4 w-4 text-primary" />
            سجل الحضور والغياب في السنتر
          </h3>

          <div className="space-y-2 max-h-80 overflow-y-auto pr-1">
            {attendances.length === 0 ? (
              <p className="text-xs text-muted-foreground py-10 text-center">لا يوجد سجل حضور مسجل بعد.</p>
            ) : (
              attendances.map((att: any) => (
                <div key={att.id} className="flex items-center justify-between p-3 rounded-xl bg-background/60 border border-border/50 text-xs">
                  <div>
                    <p className="font-bold text-foreground">{att.session?.topic || "حصة دراسية"}</p>
                    <p className="text-[11px] text-muted-foreground">{att.session?.date || att.created_at?.slice(0, 10)}</p>
                  </div>
                  <span
                    className={`px-3 py-1 rounded-full font-bold text-[11px] ${
                      att.status === "present"
                        ? "bg-emerald-500/20 text-emerald-400"
                        : att.status === "late"
                        ? "bg-amber-500/20 text-amber-400"
                        : "bg-destructive/20 text-destructive"
                    }`}
                  >
                    {att.status === "present" ? "حاضر 🟢" : att.status === "late" ? "متأخر 🟡" : "غائب 🔴"}
                  </span>
                </div>
              ))
            )}
          </div>
        </div>

        {/* Grades Log */}
        <div className="lg:col-span-6 glass-card p-6 rounded-2xl border border-border space-y-4">
          <h3 className="text-sm font-bold flex items-center gap-2">
            <Award className="h-4 w-4 text-amber-500" />
            سجل درجات الامتحانات الورقية
          </h3>

          <div className="space-y-2 max-h-80 overflow-y-auto pr-1">
            {grades.length === 0 ? (
              <p className="text-xs text-muted-foreground py-10 text-center">لا توجد درجات امتحانات مسجلة بعد.</p>
            ) : (
              grades.map((gr: any) => (
                <div key={gr.id} className="flex items-center justify-between p-3 rounded-xl bg-background/60 border border-border/50 text-xs">
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
    </div>
  );
}
