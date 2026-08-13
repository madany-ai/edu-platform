"use client";

import { useState, useEffect } from "react";
import { useAuth } from "@/providers/auth-provider";
import { useRouter } from "next/navigation";
import Link from "next/link";
import {
  centerService,
  Group,
  AcademicSession,
  CenterExam,
  RankingItem,
} from "@/services/center.service";
import { Button } from "@/components/ui/button";
import {
  Building2,
  Users,
  Calendar,
  QrCode,
  FileSpreadsheet,
  Trophy,
  Plus,
  Search,
  Eye,
  Loader2,
  CheckCircle2,
  TrendingUp,
  ArrowRight,
  ShieldCheck,
  UserCheck,
} from "lucide-react";

export default function CenterDashboardPage() {
  const { user, isInstructor, isAssistant, loading: authLoading } = useAuth();
  const router = useRouter();

  const [groups, setGroups] = useState<Group[]>([]);
  const [sessions, setSessions] = useState<AcademicSession[]>([]);
  const [exams, setExams] = useState<CenterExam[]>([]);
  const [rankings, setRankings] = useState<RankingItem[]>([]);
  const [loading, setLoading] = useState<boolean>(true);

  useEffect(() => {
    if (!authLoading) {
      const isStaff = isInstructor || isAssistant || user?.roles?.some((r: any) => ["instructor", "assistant"].includes(typeof r === "string" ? r : r.name));
      if (!isStaff) {
        router.push("/dashboard");
        return;
      }
      initData();
    }
  }, [authLoading, isInstructor, isAssistant, user]);

  const initData = async () => {
    setLoading(true);
    try {
      const [groupsData, sessionsData, examsData, rankingsData] = await Promise.all([
        centerService.getGroups(),
        centerService.getSessions(),
        centerService.getExams(),
        centerService.getRankings(),
      ]);

      setGroups(groupsData);
      setSessions(sessionsData);
      setExams(examsData);
      setRankings(rankingsData.slice(0, 5));
    } catch (e) {
      console.error("Failed to load center stats", e);
    } finally {
      setLoading(false);
    }
  };

  if (authLoading || loading) {
    return (
      <div className="min-h-[70vh] flex flex-col items-center justify-center gap-3">
        <Loader2 className="h-10 w-10 animate-spin text-primary" />
        <p className="text-sm font-semibold text-muted-foreground">جاري تحميل رئيسية السنتر...</p>
      </div>
    );
  }

  const totalStudents = groups.reduce((acc, g) => acc + (g.students_count || 0), 0);

  return (
    <div className="w-full max-w-7xl mx-auto px-4 py-8 space-y-8">
      {/* Welcome Banner */}
      <div className="glass-card p-8 rounded-3xl border border-primary/20 bg-gradient-to-r from-primary/10 via-background to-secondary/10 relative overflow-hidden">
        <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
          <div className="space-y-2">
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-primary/20 text-primary">
              <ShieldCheck className="h-4 w-4" />
              <span>لوحة تحكم إدارة السنتر الأوفلاين</span>
            </div>
            <h1 className="text-3xl font-extrabold text-gradient">مرحباً بك، {user?.name} 🏫</h1>
            <p className="text-sm text-muted-foreground max-w-2xl">
              إشراف شامل على المجموعات الدراسية، تسجيل الحضور الفوري بالكاميرا، رصد درجات الامتحانات الورقية، ومتابعة الطلاب الأوائل.
            </p>
          </div>

          <Link href="/center/scanner">
            <Button size="lg" className="h-12 px-6 font-bold gap-2 shadow-xl shadow-primary/20 text-sm">
              <QrCode className="h-5 w-5 animate-pulse" />
              فتح ماسح الحضور بالكاميرا
            </Button>
          </Link>
        </div>
      </div>

      {/* KPI Stats Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="glass-card p-6 rounded-2xl border border-border flex items-center justify-between">
          <div>
            <span className="text-xs text-muted-foreground font-semibold block">إجمالي طلاب السنتر</span>
            <span className="text-2xl font-extrabold text-foreground mt-1 block">{totalStudents} طالب</span>
          </div>
          <div className="p-3.5 rounded-2xl bg-primary/10 text-primary">
            <Users className="h-6 w-6" />
          </div>
        </div>

        <div className="glass-card p-6 rounded-2xl border border-border flex items-center justify-between">
          <div>
            <span className="text-xs text-muted-foreground font-semibold block">المجموعات الدراسية النشطة</span>
            <span className="text-2xl font-extrabold text-foreground mt-1 block">{groups.length} مجموعات</span>
          </div>
          <div className="p-3.5 rounded-2xl bg-blue-500/10 text-blue-400">
            <Building2 className="h-6 w-6" />
          </div>
        </div>

        <div className="glass-card p-6 rounded-2xl border border-border flex items-center justify-between">
          <div>
            <span className="text-xs text-muted-foreground font-semibold block">الحصص المسجلة</span>
            <span className="text-2xl font-extrabold text-foreground mt-1 block">{sessions.length} حصة</span>
          </div>
          <div className="p-3.5 rounded-2xl bg-emerald-500/10 text-emerald-400">
            <Calendar className="h-6 w-6" />
          </div>
        </div>

        <div className="glass-card p-6 rounded-2xl border border-border flex items-center justify-between">
          <div>
            <span className="text-xs text-muted-foreground font-semibold block">الامتحانات الورقية</span>
            <span className="text-2xl font-extrabold text-foreground mt-1 block">{exams.length} امتحان</span>
          </div>
          <div className="p-3.5 rounded-2xl bg-amber-500/10 text-amber-500">
            <FileSpreadsheet className="h-6 w-6" />
          </div>
        </div>
      </div>

      {/* Quick Navigation Cards Grid */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {/* Card 1: Scanner */}
        <Link href="/center/scanner">
          <div className="glass-card p-6 rounded-2xl border border-primary/30 hover:border-primary transition-all group space-y-4 cursor-pointer">
            <div className="flex items-center justify-between">
              <div className="p-3 rounded-xl bg-primary/10 text-primary group-hover:scale-110 transition-transform">
                <QrCode className="h-6 w-6" />
              </div>
              <ArrowRight className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors" />
            </div>
            <div>
              <h3 className="text-base font-bold text-foreground">ماسح الحضور للكاميرا</h3>
              <p className="text-xs text-muted-foreground mt-1">
                امسح كود الطالب بالكامل بواسطة كاميرا الهاتف أو الكمبيوتر لتسجيل الحضور الفوري.
              </p>
            </div>
          </div>
        </Link>

        {/* Card 2: Exams */}
        <Link href="/center/exams">
          <div className="glass-card p-6 rounded-2xl border border-amber-500/30 hover:border-amber-500 transition-all group space-y-4 cursor-pointer">
            <div className="flex items-center justify-between">
              <div className="p-3 rounded-xl bg-amber-500/10 text-amber-500 group-hover:scale-110 transition-transform">
                <FileSpreadsheet className="h-6 w-6" />
              </div>
              <ArrowRight className="h-5 w-5 text-muted-foreground group-hover:text-amber-500 transition-colors" />
            </div>
            <div>
              <h3 className="text-base font-bold text-foreground">الامتحانات ورصد الدرجات</h3>
              <p className="text-xs text-muted-foreground mt-1">
                إضافة الامتحانات ورصد درجات الطلاب في مصفوفة تفاعلية وإشعار أولياء الأمور.
              </p>
            </div>
          </div>
        </Link>

        {/* Card 3: Rankings */}
        <Link href="/center/rankings">
          <div className="glass-card p-6 rounded-2xl border border-emerald-500/30 hover:border-emerald-500 transition-all group space-y-4 cursor-pointer">
            <div className="flex items-center justify-between">
              <div className="p-3 rounded-xl bg-emerald-500/10 text-emerald-400 group-hover:scale-110 transition-transform">
                <Trophy className="h-6 w-6" />
              </div>
              <ArrowRight className="h-5 w-5 text-muted-foreground group-hover:text-emerald-400 transition-colors" />
            </div>
            <div>
              <h3 className="text-base font-bold text-foreground">ترتيب الأوائل والتفوق</h3>
              <p className="text-xs text-muted-foreground mt-1">
                لوحة الأوائل التفاعلية وعرض المراكز الأولى والأوسمة لكل مجموعة وصف دراسي.
              </p>
            </div>
          </div>
        </Link>
      </div>

      {/* Top 5 Students Preview & Recent Sessions */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* Top 5 Star Students */}
        <div className="lg:col-span-6 glass-card p-6 rounded-2xl border border-border space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="text-sm font-bold flex items-center gap-2">
              <Trophy className="h-4 w-4 text-amber-500" />
              أوائل السنتر المتفوقون
            </h3>
            <Link href="/center/rankings" className="text-xs text-primary hover:underline font-semibold">
              عرض الكورنيش الكامل ➔
            </Link>
          </div>

          <div className="space-y-2">
            {rankings.length === 0 ? (
              <p className="text-xs text-muted-foreground py-6 text-center">لا توجد درجات مسجلة بعد.</p>
            ) : (
              rankings.map((st, idx) => (
                <div
                  key={st.student_id + idx}
                  className="flex items-center justify-between p-3 rounded-xl bg-background/60 border border-border/50 text-xs"
                >
                  <div className="flex items-center gap-3">
                    <span className="font-bold text-sm">
                      {idx === 0 ? "🥇" : idx === 1 ? "🥈" : idx === 2 ? "🥉" : `#${idx + 1}`}
                    </span>
                    <div>
                      <p className="font-bold text-foreground">
                        {st.first_name} {st.last_name}
                      </p>
                      <p className="text-[11px] font-mono text-muted-foreground">{st.student_code}</p>
                    </div>
                  </div>
                  <span className="px-2.5 py-1 rounded-full font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20">
                    {st.percentage}%
                  </span>
                </div>
              ))
            )}
          </div>
        </div>

        {/* Recent Academic Sessions */}
        <div className="lg:col-span-6 glass-card p-6 rounded-2xl border border-border space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="text-sm font-bold flex items-center gap-2">
              <Calendar className="h-4 w-4 text-primary" />
              آخر الحصص الدراسية
            </h3>
            <Link href="/center/sessions" className="text-xs text-primary hover:underline font-semibold">
              إدارة كل الحصص ➔
            </Link>
          </div>

          <div className="space-y-2">
            {sessions.length === 0 ? (
              <p className="text-xs text-muted-foreground py-6 text-center">لا توجد حصص مضافة بعد.</p>
            ) : (
              sessions.slice(0, 5).map((s) => (
                <div
                  key={s.id}
                  className="flex items-center justify-between p-3 rounded-xl bg-background/60 border border-border/50 text-xs"
                >
                  <div>
                    <p className="font-bold text-foreground">{s.topic}</p>
                    <p className="text-[11px] text-muted-foreground">{s.group?.name}</p>
                  </div>
                  <span className="font-mono text-xs text-muted-foreground">{s.date}</span>
                </div>
              ))
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
