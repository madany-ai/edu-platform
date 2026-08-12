"use client";

import { useState, useEffect, use } from "react";
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
import { CenterAttendanceScanner } from "@/components/center/center-attendance-scanner";
import { CenterGradeMatrix } from "@/components/center/center-grade-matrix";
import { StudentReportModal } from "@/components/center/student-report-modal";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Users,
  Calendar,
  FileSpreadsheet,
  Trophy,
  Plus,
  Search,
  Eye,
  Loader2,
  CheckCircle2,
  ArrowRight,
  QrCode,
  UserPlus,
  ArrowRightLeft,
  X,
  Phone,
} from "lucide-react";

const ACADEMIC_YEARS = [
  { id: "prep_1", name: "الصف الأول الإعدادي" },
  { id: "prep_2", name: "الصف الثاني الإعدادي" },
  { id: "prep_3", name: "الصف الثالث الإعدادي" },
  { id: "sec_1", name: "الصف الأول الثانوي" },
  { id: "sec_2", name: "الصف الثاني الثانوي" },
  { id: "sec_3", name: "الصف الثالث الثانوي" },
];

export default function GroupWorkspacePage({ params }: { params: Promise<{ id: string }> }) {
  const { id: groupId } = use(params);
  const { user, isInstructor, isAssistant, loading: authLoading } = useAuth();
  const router = useRouter();

  const [data, setData] = useState<{
    group: Group;
    students: any[];
    sessions: AcademicSession[];
    exams: CenterExam[];
    rankings: RankingItem[];
  } | null>(null);
  const [loading, setLoading] = useState(true);

  // Tab State inside Group Page
  const [activeTab, setActiveTab] = useState<"students" | "sessions" | "exams" | "rankings" | "scanner">("students");

  // Modals
  const [showAddStudentModal, setShowAddStudentModal] = useState(false);
  const [showAddSessionModal, setShowAddSessionModal] = useState(false);
  const [showAddExamModal, setShowAddExamModal] = useState(false);
  const [selectedStudentId, setSelectedStudentId] = useState<string | null>(null);
  const [msg, setMsg] = useState("");

  // New Student Form
  const [firstName, setFirstName] = useState("");
  const [secondName, setSecondName] = useState("");
  const [lastName, setLastName] = useState("");
  const [phone, setPhone] = useState("");
  const [fatherPhone, setFatherPhone] = useState("");

  // New Session Form
  const [newTopic, setNewTopic] = useState("");
  const [newSessionDate, setNewSessionDate] = useState(new Date().toISOString().split("T")[0]);

  // New Exam Form
  const [newExamName, setNewExamName] = useState("");
  const [newExamMarks, setNewExamMarks] = useState("30");

  useEffect(() => {
    if (!authLoading) {
      loadGroupWorkspace();
    }
  }, [groupId, authLoading]);

  const loadGroupWorkspace = async () => {
    setLoading(true);
    try {
      const res = await centerService.getGroupDetails(groupId);
      setData(res);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const handleAddStudentToGroup = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!data?.group) return;
    setMsg("");
    try {
      const res = await centerService.createStudent({
        first_name: firstName,
        second_name: secondName,
        last_name: lastName,
        phone,
        father_phone: fatherPhone,
        academic_year: data.group.academic_year,
        group_id: groupId,
      });
      setMsg(res.message);
      setShowAddStudentModal(false);
      setFirstName("");
      setLastName("");
      setPhone("");
      setFatherPhone("");
      loadGroupWorkspace();
    } catch (err) {
      alert("حدث خطأ أثناء إضافة الطالب.");
    }
  };

  const handleCreateSessionForGroup = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await centerService.createSession({
        group_id: groupId,
        topic: newTopic,
        date: newSessionDate,
      });
      setShowAddSessionModal(false);
      setNewTopic("");
      loadGroupWorkspace();
    } catch (err) {
      alert("حدث خطأ أثناء إضافة الحصة.");
    }
  };

  const handleCreateExamForGroup = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await centerService.createExam({
        name: newExamName,
        group_id: groupId,
        total_marks: parseFloat(newExamMarks) || 30,
        date: newSessionDate,
      });
      setShowAddExamModal(false);
      setNewExamName("");
      loadGroupWorkspace();
    } catch (err) {
      alert("حدث خطأ أثناء إضافة الامتحان.");
    }
  };

  if (authLoading || loading || !data) {
    return (
      <div className="min-h-[70vh] flex flex-col items-center justify-center gap-3">
        <Loader2 className="h-10 w-10 animate-spin text-primary" />
        <p className="text-sm font-semibold text-muted-foreground">جاري فتح ورشة عمل المجموعة...</p>
      </div>
    );
  }

  const { group, students, sessions, exams, rankings } = data;

  return (
    <div className="container mx-auto px-4 py-8 space-y-6">
      {/* Back Link & Header Banner */}
      <div className="space-y-4">
        <Link href="/center/groups" className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-primary font-semibold">
          <ArrowRight className="h-4 w-4" /> العودة إلى تصفية المجموعات
        </Link>

        <div className="glass-card p-8 rounded-3xl border border-primary/20 bg-gradient-to-r from-primary/10 via-background to-secondary/10 relative overflow-hidden">
          <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div className="space-y-2">
              <div className="flex items-center gap-3 flex-wrap">
                <span className="px-3 py-1 rounded-full text-xs font-bold bg-primary/20 text-primary border border-primary/30">
                  {ACADEMIC_YEARS.find(y => y.id === group.academic_year)?.name || "صف دراسي"}
                </span>
                <span className="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                  سعة المجموعة: {group.capacity} طالب (مسجل {students.length})
                </span>
              </div>
              <h1 className="text-3xl font-extrabold text-foreground">{group.name} 👥</h1>
              <p className="text-xs text-muted-foreground">
                ورشة العمل الكاملة للمجموعة: إضافة الطلاب، كشوف الحضور والغياب، رصد الامتحانات، والأوائل.
              </p>
            </div>

            {/* Group Workspace Quick Action Buttons */}
            <div className="flex flex-wrap gap-2">
              <Button onClick={() => setShowAddStudentModal(true)} size="sm" className="gap-1.5 font-bold shadow-md">
                <UserPlus className="h-4 w-4" /> إضافة طالب لهذه المجموعة
              </Button>
              <Button onClick={() => setShowAddSessionModal(true)} size="sm" variant="secondary" className="gap-1.5 font-bold">
                <Plus className="h-4 w-4" /> حصة جديدة
              </Button>
              <Button onClick={() => setShowAddExamModal(true)} size="sm" variant="outline" className="gap-1.5 font-bold">
                <Plus className="h-4 w-4" /> امتحان ورقي
              </Button>
            </div>
          </div>
        </div>
      </div>

      {msg && (
        <div className="glass-card p-4 rounded-xl border border-emerald-500/40 bg-emerald-500/10 text-emerald-400 text-sm font-bold flex items-center gap-2">
          <CheckCircle2 className="h-5 w-5" />
          <span>{msg}</span>
        </div>
      )}

      {/* Tabs Menu inside Single Group Workspace */}
      <div className="flex items-center gap-2 p-1.5 rounded-2xl bg-muted/60 border border-border overflow-x-auto">
        <button
          onClick={() => setActiveTab("students")}
          className={`flex items-center gap-2 px-5 py-3 rounded-xl text-xs font-bold transition-all whitespace-nowrap ${
            activeTab === "students"
              ? "bg-background text-primary shadow-md border border-primary/20"
              : "text-muted-foreground hover:text-foreground"
          }`}
        >
          <Users className="h-4 w-4" />
          <span>طلاب المجموعة ({students.length})</span>
        </button>

        <button
          onClick={() => setActiveTab("sessions")}
          className={`flex items-center gap-2 px-5 py-3 rounded-xl text-xs font-bold transition-all whitespace-nowrap ${
            activeTab === "sessions"
              ? "bg-background text-primary shadow-md border border-primary/20"
              : "text-muted-foreground hover:text-foreground"
          }`}
        >
          <Calendar className="h-4 w-4" />
          <span>حصص وكشوف الغياب ({sessions.length})</span>
        </button>

        <button
          onClick={() => setActiveTab("scanner")}
          className={`flex items-center gap-2 px-5 py-3 rounded-xl text-xs font-bold transition-all whitespace-nowrap ${
            activeTab === "scanner"
              ? "bg-background text-primary shadow-md border border-primary/20"
              : "text-muted-foreground hover:text-foreground"
          }`}
        >
          <QrCode className="h-4 w-4 text-primary animate-pulse" />
          <span>ماسح الكاميرا بالكود للمجموعة</span>
        </button>

        <button
          onClick={() => setActiveTab("exams")}
          className={`flex items-center gap-2 px-5 py-3 rounded-xl text-xs font-bold transition-all whitespace-nowrap ${
            activeTab === "exams"
              ? "bg-background text-primary shadow-md border border-primary/20"
              : "text-muted-foreground hover:text-foreground"
          }`}
        >
          <FileSpreadsheet className="h-4 w-4" />
          <span>الامتحانات والدرجات ({exams.length})</span>
        </button>

        <button
          onClick={() => setActiveTab("rankings")}
          className={`flex items-center gap-2 px-5 py-3 rounded-xl text-xs font-bold transition-all whitespace-nowrap ${
            activeTab === "rankings"
              ? "bg-background text-primary shadow-md border border-primary/20"
              : "text-muted-foreground hover:text-foreground"
          }`}
        >
          <Trophy className="h-4 w-4 text-amber-500" />
          <span>أوائل هذه المجموعة</span>
        </button>
      </div>

      {/* TAB 1: Group Students */}
      {activeTab === "students" && (
        <div className="glass-card rounded-2xl border border-border overflow-hidden">
          {students.length === 0 ? (
            <div className="py-16 text-center text-muted-foreground text-xs space-y-3">
              <p>لا يوجد طلاب مسجلون في هذه المجموعة حتى الآن.</p>
              <Button onClick={() => setShowAddStudentModal(true)} size="sm" className="font-bold gap-1">
                <UserPlus className="h-4 w-4" /> إضافة أول طالب للمجموعة
              </Button>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-right text-sm border-collapse">
                <thead>
                  <tr className="bg-muted/50 border-b border-border text-xs font-bold text-muted-foreground">
                    <th className="py-3.5 px-4">#</th>
                    <th className="py-3.5 px-4">كود الطالب</th>
                    <th className="py-3.5 px-4">اسم الطالب</th>
                    <th className="py-3.5 px-4">هاتف الطالب</th>
                    <th className="py-3.5 px-4">هاتف ولي الأمر</th>
                    <th className="py-3.5 px-4 text-center">التقرير والتواصل</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border/40">
                  {students.map((st, idx) => (
                    <tr key={st.id} className="hover:bg-muted/30 transition-colors">
                      <td className="py-3.5 px-4 font-mono text-xs text-muted-foreground">{idx + 1}</td>
                      <td className="py-3.5 px-4 font-mono text-xs text-primary font-bold">{st.student_code}</td>
                      <td className="py-3.5 px-4 font-bold text-foreground">
                        {st.first_name} {st.second_name} {st.last_name}
                      </td>
                      <td className="py-3.5 px-4 font-mono text-xs text-muted-foreground">{st.phone || "-"}</td>
                      <td className="py-3.5 px-4 font-mono text-xs text-muted-foreground">{st.father_phone || "-"}</td>
                      <td className="py-3.5 px-4 text-center">
                        <Button
                          size="sm"
                          variant="secondary"
                          onClick={() => setSelectedStudentId(st.id)}
                          className="h-8 text-xs font-bold gap-1.5"
                        >
                          <Eye className="h-3.5 w-3.5 text-primary" /> التقرير والاتصال
                        </Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {/* TAB 2: Group Sessions */}
      {activeTab === "sessions" && (
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="text-sm font-bold">حصص المجموعة كود: {group.name}</h3>
            <Button onClick={() => setShowAddSessionModal(true)} size="sm" className="font-bold gap-1">
              <Plus className="h-4 w-4" /> إضافة حصة للمجموعة
            </Button>
          </div>

          <div className="grid grid-cols-1 gap-4">
            {sessions.length === 0 ? (
              <div className="py-12 text-center text-xs text-muted-foreground glass-card rounded-2xl">
                لا توجد حصص دراسية مضافة لهذه المجموعة حتى الآن.
              </div>
            ) : (
              sessions.map((s) => (
                <div key={s.id} className="glass-card p-4 rounded-xl border border-border flex items-center justify-between">
                  <div>
                    <h4 className="font-bold text-sm text-foreground">{s.topic}</h4>
                    <p className="text-xs text-muted-foreground font-mono mt-0.5">تاريخ الحصة: {s.date}</p>
                  </div>
                  <Link href={`/center/sessions?session_id=${s.id}`}>
                    <Button size="sm" variant="outline" className="text-xs font-bold gap-1">
                      <Calendar className="h-3.5 w-3.5 text-primary" /> عرض الكشف والغياب
                    </Button>
                  </Link>
                </div>
              ))
            )}
          </div>
        </div>
      )}

      {/* TAB 3: Group Scanner */}
      {activeTab === "scanner" && (
        <CenterAttendanceScanner sessions={sessions} onAttendanceUpdated={loadGroupWorkspace} />
      )}

      {/* TAB 4: Group Exams */}
      {activeTab === "exams" && (
        <CenterGradeMatrix exams={exams} onGradesSaved={loadGroupWorkspace} />
      )}

      {/* TAB 5: Group Rankings */}
      {activeTab === "rankings" && (
        <div className="glass-card rounded-2xl border border-border overflow-hidden">
          {rankings.length === 0 ? (
            <div className="py-16 text-center text-xs text-muted-foreground">
              لا توجد درجات مسجلة حتى الآن لحساب ترتيب الأوائل لهذه المجموعة.
            </div>
          ) : (
            <table className="w-full text-right text-sm border-collapse">
              <thead>
                <tr className="bg-amber-500/10 border-b border-amber-500/20 text-xs font-bold text-muted-foreground">
                  <th className="py-3.5 px-4">المركز</th>
                  <th className="py-3.5 px-4">كود الطالب</th>
                  <th className="py-3.5 px-4">اسم الطالب المتفوق</th>
                  <th className="py-3.5 px-4">إجمالي النقاط والنسبة المئوية</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border/40">
                {rankings.map((item, idx) => (
                  <tr key={item.student_id + idx} className="hover:bg-amber-500/5 transition-colors">
                    <td className="py-3 px-4 font-bold text-base">
                      {idx === 0 ? "🥇 الأول" : idx === 1 ? "🥈 الثاني" : idx === 2 ? "🥉 الثالث" : `#${idx + 1}`}
                    </td>
                    <td className="py-3 px-4 font-mono text-xs text-primary font-bold">{item.student_code}</td>
                    <td className="py-3 px-4 font-bold text-foreground">
                      {item.first_name} {item.second_name} {item.last_name}
                    </td>
                    <td className="py-3 px-4 font-bold text-amber-500">{item.percentage}%</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      )}

      {/* Modal: Add Student to THIS Group */}
      {showAddStudentModal && (
        <div className="fixed inset-0 z-50 bg-background/80 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="glass-card w-full max-w-md p-6 rounded-2xl border border-border space-y-4 shadow-2xl">
            <h3 className="text-base font-bold">إضافة طالب جديد للمجموعة مباشرة</h3>
            <form onSubmit={handleAddStudentToGroup} className="space-y-3">
              <div className="grid grid-cols-2 gap-2">
                <div>
                  <Label className="text-xs">الاسم الأول:</Label>
                  <Input placeholder="أحمد" value={firstName} onChange={(e) => setFirstName(e.target.value)} required />
                </div>
                <div>
                  <Label className="text-xs">اسم الأب:</Label>
                  <Input placeholder="محمود" value={secondName} onChange={(e) => setSecondName(e.target.value)} />
                </div>
              </div>

              <div>
                <Label className="text-xs">اسم العائلة:</Label>
                <Input placeholder="العربي" value={lastName} onChange={(e) => setLastName(e.target.value)} required />
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div>
                  <Label className="text-xs">هاتف الطالب:</Label>
                  <Input placeholder="010..." value={phone} onChange={(e) => setPhone(e.target.value)} />
                </div>
                <div>
                  <Label className="text-xs">هاتف ولي الأمر:</Label>
                  <Input placeholder="012..." value={fatherPhone} onChange={(e) => setFatherPhone(e.target.value)} />
                </div>
              </div>

              <div className="flex gap-2 pt-2">
                <Button type="submit" className="flex-1 font-bold">
                  تسجيل وإضافة للمجموعة
                </Button>
                <Button type="button" variant="outline" onClick={() => setShowAddStudentModal(false)}>
                  إلغاء
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Add Session */}
      {showAddSessionModal && (
        <div className="fixed inset-0 z-50 bg-background/80 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="glass-card w-full max-w-md p-6 rounded-2xl border border-border space-y-4 shadow-2xl">
            <h3 className="text-base font-bold">إضافة حصة للمجموعة ({group.name})</h3>
            <form onSubmit={handleCreateSessionForGroup} className="space-y-3">
              <div>
                <Label className="text-xs">موضوع الحصة:</Label>
                <Input
                  placeholder="مثال: الاشتقاق - الدرس الأول"
                  value={newTopic}
                  onChange={(e) => setNewTopic(e.target.value)}
                  required
                />
              </div>

              <div>
                <Label className="text-xs">التاريخ:</Label>
                <Input type="date" value={newSessionDate} onChange={(e) => setNewSessionDate(e.target.value)} required />
              </div>

              <div className="flex gap-2 pt-2">
                <Button type="submit" className="flex-1 font-bold">
                  حفظ الحصة
                </Button>
                <Button type="button" variant="outline" onClick={() => setShowAddSessionModal(false)}>
                  إلغاء
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Add Exam */}
      {showAddExamModal && (
        <div className="fixed inset-0 z-50 bg-background/80 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="glass-card w-full max-w-md p-6 rounded-2xl border border-border space-y-4 shadow-2xl">
            <h3 className="text-base font-bold">إضافة امتحان ورقي للمجموعة ({group.name})</h3>
            <form onSubmit={handleCreateExamForGroup} className="space-y-3">
              <div>
                <Label className="text-xs">عنوان الامتحان:</Label>
                <Input
                  placeholder="مثال: اختبار شهر سبتمبر"
                  value={newExamName}
                  onChange={(e) => setNewExamName(e.target.value)}
                  required
                />
              </div>

              <div>
                <Label className="text-xs">الدرجة العظمى:</Label>
                <Input type="number" value={newExamMarks} onChange={(e) => setNewExamMarks(e.target.value)} required />
              </div>

              <div className="flex gap-2 pt-2">
                <Button type="submit" className="flex-1 font-bold">
                  حفظ الامتحان
                </Button>
                <Button type="button" variant="outline" onClick={() => setShowAddExamModal(false)}>
                  إلغاء
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Student Detailed Report Modal */}
      {selectedStudentId && (
        <StudentReportModal studentId={selectedStudentId} onClose={() => setSelectedStudentId(null)} />
      )}
    </div>
  );
}
