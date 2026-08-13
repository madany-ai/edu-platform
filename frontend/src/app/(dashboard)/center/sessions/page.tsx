"use client";

import { useState, useEffect } from "react";
import { centerService, AcademicSession, Group, AttendanceRecord } from "@/services/center.service";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { CenterAttendanceScanner } from "@/components/center/center-attendance-scanner";
import {
  Calendar,
  Plus,
  Save,
  Loader2,
  CheckCircle2,
  UserCheck,
  Search,
  QrCode,
  Check,
  X,
  Clock,
  Sparkles,
} from "lucide-react";

import { useCenterFilters } from "@/providers/center-filters-provider";

export default function SessionsPage() {
  const [sessions, setSessions] = useState<AcademicSession[]>([]);
  const [groups, setGroups] = useState<Group[]>([]);
  const [selectedSessionId, setSelectedSessionId] = useState<string>("");
  const [attendance, setAttendance] = useState<AttendanceRecord[]>([]);
  const [searchQuery, setSearchQuery] = useState<string>("");

  const { selectedYearId, selectedGrade, selectedTerm } = useCenterFilters();
  
  const [loading, setLoading] = useState<boolean>(true);
  const [attLoading, setAttLoading] = useState<boolean>(false);
  const [saving, setSaving] = useState<boolean>(false);
  const [msg, setMsg] = useState<string>("");

  // Modals State
  const [showAddModal, setShowAddModal] = useState<boolean>(false);
  const [showScannerModal, setShowScannerModal] = useState<boolean>(false);
  const [newGroupId, setNewGroupId] = useState("");
  const [newTopic, setNewTopic] = useState("");
  const [newDate, setNewDate] = useState(new Date().toISOString().split("T")[0]);

  useEffect(() => {
    initData();
  }, [selectedYearId, selectedGrade, selectedTerm]);

  const initData = async () => {
    setLoading(true);
    try {
      const filterParams = {
        academic_year_id: selectedYearId,
        academic_year: selectedGrade,
        term: selectedTerm,
      };
      
      const [sessionsData, groupsData] = await Promise.all([
        centerService.getSessions(filterParams),
        centerService.getGroups(filterParams),
      ]);
      setSessions(sessionsData);
      setGroups(groupsData);
      
      if (sessionsData.length > 0) {
        if (!selectedSessionId || !sessionsData.find(s => s.id === selectedSessionId)) {
          setSelectedSessionId(sessionsData[0].id);
        }
      } else {
        setSelectedSessionId("");
        setAttendance([]);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (selectedSessionId) {
      loadAttendance(selectedSessionId);
    }
  }, [selectedSessionId]);

  const loadAttendance = async (sessionId: string) => {
    setAttLoading(true);
    setMsg("");
    try {
      const res = await centerService.getSessionAttendance(sessionId);
      setAttendance(res.attendance);
    } catch (e) {
      console.error(e);
    } finally {
      setAttLoading(false);
    }
  };

  const handleStatusChange = (studentId: string, status: "present" | "absent" | "late" | "guest") => {
    setAttendance((prev) =>
      prev.map((a) => (a.student_id === studentId ? { ...a, status } : a))
    );
  };

  const handleBulkStatusChange = (targetStatus: "present" | "absent") => {
    const filteredIds = new Set(filteredAttendance.map((a) => a.student_id));
    setAttendance((prev) =>
      prev.map((a) => (filteredIds.has(a.student_id) ? { ...a, status: targetStatus } : a))
    );
  };

  const handleSaveAttendance = async () => {
    if (!selectedSessionId) return;
    setSaving(true);
    setMsg("");
    try {
      const payload = attendance.map((a) => ({
        student_id: a.student_id,
        status: a.status,
      }));
      await centerService.updateAttendance(selectedSessionId, payload);
      setMsg("تم حفظ كشف الحضور والغياب بنجاح وصدرت التنبيهات لأولياء الأمور! 🟢");
    } catch (e) {
      setMsg("حدث خطأ أثناء حفظ الكشف.");
    } finally {
      setSaving(false);
    }
  };

  const [broadcastToGrade, setBroadcastToGrade] = useState<boolean>(false);

  const handleCreateSession = async (e: React.FormEvent) => {
    e.preventDefault();
    if (groups.length === 0) {
      alert("يرجى إضافة مجموعة دراسية واحدة على الأقل قبل إنشاء الحصص.");
      return;
    }
    try {
      const selectedG = groups.find((g) => g.id === (newGroupId || groups[0]?.id));
      const created = await centerService.createSession({
        group_id: broadcastToGrade ? undefined : (newGroupId || groups[0]?.id),
        academic_year: broadcastToGrade ? selectedG?.academic_year : undefined,
        topic: newTopic,
        date: newDate,
      });
      setShowAddModal(false);
      setNewTopic("");
      setBroadcastToGrade(false);
      await initData();
      if (created?.id) setSelectedSessionId(created.id);
    } catch (err) {
      alert("حدث خطأ أثناء إضافة الحصة. تأكد من إدخال البيانات بشكل صحيح.");
    }
  };

  if (loading) {
    return (
      <div className="min-h-[70vh] flex flex-col items-center justify-center gap-3">
        <Loader2 className="h-10 w-10 animate-spin text-primary" />
        <p className="text-sm font-semibold text-muted-foreground">جاري تحميل الحصص وكشوف الغياب...</p>
      </div>
    );
  }

  const selectedSession = sessions.find((s) => s.id === selectedSessionId);

  // Realtime Filtered Attendance
  const filteredAttendance = attendance.filter((a) => {
    if (!searchQuery.trim()) return true;
    const q = searchQuery.trim().toLowerCase();
    return (
      a.full_name.toLowerCase().includes(q) ||
      a.student_code.toLowerCase().includes(q) ||
      (a.phone && a.phone.includes(q)) ||
      (a.father_phone && a.father_phone.includes(q))
    );
  });

  const presentCount = attendance.filter((a) => a.status === "present").length;
  const lateCount = attendance.filter((a) => a.status === "late").length;
  const absentCount = attendance.filter((a) => a.status === "absent").length;

  return (
    <div className="w-full max-w-7xl mx-auto px-4 py-8 space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border pb-4">
        <div className="flex items-center gap-3">
          <div className="p-3 rounded-2xl bg-primary/10 text-primary">
            <Calendar className="h-6 w-6" />
          </div>
          <div>
            <h1 className="text-2xl font-extrabold text-foreground">الحصص الدراسية وكشوف الحضور والغياب</h1>
            <p className="text-xs text-muted-foreground mt-0.5">
              إضافة عنوان الدرس مرة واحدة وتحديد كشف الحضور لكل مجموعة دراسية بسهولة.
            </p>
          </div>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <Button onClick={() => setShowScannerModal(true)} variant="secondary" className="gap-2 font-bold shadow-md">
            <QrCode className="h-4 w-4 text-primary" /> كاميرا الـ QR السريعة
          </Button>
          <Button onClick={() => setShowAddModal(true)} className="gap-2 font-bold shadow-md">
            <Plus className="h-4 w-4" /> إضافة حصة جديدة
          </Button>
        </div>
      </div>

      {/* Session Select & Live Stats Bar */}
      <div className="glass-card p-6 rounded-2xl border border-border space-y-4">
        <div className="flex flex-col md:flex-row items-center justify-between gap-4">
          <div className="w-full md:w-96 max-w-full">
            <Label className="text-xs font-bold mb-1 block">اختر الحصة والمجموعة الدراسية:</Label>
            <select
              value={selectedSessionId}
              onChange={(e) => setSelectedSessionId(e.target.value)}
              className="w-full max-w-full h-11 rounded-xl bg-background border border-border px-3 text-sm font-semibold focus:ring-2 focus:ring-primary truncate"
            >
              {sessions.length === 0 ? (
                <option value="">لا توجد حصص مضافة</option>
              ) : (
                sessions.map((s) => (
                  <option key={s.id} value={s.id} className="truncate">
                    {s.group?.name} — {s.topic} ({s.date})
                  </option>
                ))
              )}
            </select>
          </div>

          {/* Attendance Stats Badges */}
          <div className="flex items-center gap-3 flex-wrap">
            <div className="px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs font-bold text-emerald-400">
              حاضر: {presentCount}
            </div>
            <div className="px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs font-bold text-amber-400">
              متأخر: {lateCount}
            </div>
            <div className="px-3 py-1.5 rounded-xl bg-destructive/10 border border-destructive/20 text-xs font-bold text-destructive">
              غائب: {absentCount}
            </div>
            <div className="px-3 py-1.5 rounded-xl bg-primary/10 border border-primary/20 text-xs font-bold text-primary">
              إجمالي الطلاب: {attendance.length}
            </div>
          </div>

          <Button
            onClick={handleSaveAttendance}
            disabled={saving || attLoading || attendance.length === 0}
            className="h-11 px-6 font-bold gap-2 bg-gradient-to-r from-primary to-secondary w-full md:w-auto shadow-md"
          >
            {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
            حفظ وتأكيد الكشف
          </Button>
        </div>

        {/* Live Filter Search & Bulk Actions Bar */}
        <div className="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-border/40">
          {/* Instant Search Box */}
          <div className="relative w-full sm:w-80">
            <Search className="absolute right-3 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="ابحث باسم الطالب، الكود أو رقم الهاتف..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="h-9 pr-9 text-xs"
            />
          </div>

          {/* Quick Bulk Actions */}
          <div className="flex items-center gap-2 w-full sm:w-auto justify-end">
            <button
              type="button"
              onClick={() => handleBulkStatusChange("present")}
              className="text-xs font-bold px-3 py-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 transition-all"
            >
              تعليم الكل حاضر 🟢
            </button>
            <button
              type="button"
              onClick={() => handleBulkStatusChange("absent")}
              className="text-xs font-bold px-3 py-1.5 rounded-lg bg-destructive/10 hover:bg-destructive/20 text-destructive border border-destructive/30 transition-all"
            >
              تثبيت الكل غائب 🔴
            </button>
          </div>
        </div>
      </div>

      {msg && (
        <div className="glass-card p-4 rounded-xl border border-emerald-500/40 bg-emerald-500/10 text-emerald-400 text-sm font-bold flex items-center gap-2">
          <CheckCircle2 className="h-5 w-5" />
          <span>{msg}</span>
        </div>
      )}

      {/* Attendance Table */}
      <div className="glass-card rounded-2xl border border-border overflow-hidden">
        {attLoading ? (
          <div className="py-16 text-center text-muted-foreground flex flex-col items-center gap-2">
            <Loader2 className="h-8 w-8 animate-spin text-primary" />
            <span className="text-xs">جاري تحميل كشف طلاب الحصة...</span>
          </div>
        ) : filteredAttendance.length === 0 ? (
          <div className="py-16 text-center text-muted-foreground text-xs">
            {searchQuery ? `لم يتم العثور على أي طالب يطابق البحث: "${searchQuery}"` : "لا يوجد طلاب مسجلون في هذه الحصة."}
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-right text-sm border-collapse">
              <thead>
                <tr className="bg-muted/50 border-b border-border text-xs font-bold text-muted-foreground whitespace-nowrap">
                  <th className="py-3.5 px-4">#</th>
                  <th className="py-3.5 px-4">كود الطالب</th>
                  <th className="py-3.5 px-4">اسم الطالب</th>
                  <th className="py-3.5 px-4">حالة الحضور والغياب (تغيير فوري)</th>
                  <th className="py-3.5 px-4">هاتف الطالب</th>
                  <th className="py-3.5 px-4">هاتف ولي الأمر</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border/40">
                {filteredAttendance.map((att, idx) => (
                  <tr key={att.student_id} className="hover:bg-muted/30 transition-colors whitespace-nowrap">
                    <td className="py-3 px-4 font-mono text-xs text-muted-foreground">{idx + 1}</td>
                    <td className="py-3 px-4 font-mono text-xs text-primary font-bold">{att.student_code}</td>
                    <td className="py-3 px-4 font-bold text-foreground">
                      {att.full_name}
                      {att.is_guest && <span className="mr-2 text-[10px] bg-blue-500/10 text-blue-400 px-2 py-0.5 rounded font-bold">ضيف 🔵</span>}
                      {att.other_group_note && <p className="text-[11px] text-blue-400 font-semibold mt-0.5">{att.other_group_note}</p>}
                    </td>
                    <td className="py-3 px-4">
                      <div className="flex items-center gap-1.5">
                        <button
                          type="button"
                          onClick={() => handleStatusChange(att.student_id, "present")}
                          className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                            att.status === "present"
                              ? "bg-emerald-500 text-white shadow-md shadow-emerald-500/20"
                              : "bg-muted text-muted-foreground hover:bg-emerald-500/10"
                          }`}
                        >
                          حاضر 🟢
                        </button>
                        <button
                          type="button"
                          onClick={() => handleStatusChange(att.student_id, "late")}
                          className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                            att.status === "late"
                              ? "bg-amber-500 text-white shadow-md shadow-amber-500/20"
                              : "bg-muted text-muted-foreground hover:bg-amber-500/10"
                          }`}
                        >
                          متأخر 🟡
                        </button>
                        <button
                          type="button"
                          onClick={() => handleStatusChange(att.student_id, "absent")}
                          className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                            att.status === "absent"
                              ? "bg-destructive text-white shadow-md shadow-destructive/20"
                              : "bg-muted text-muted-foreground hover:bg-destructive/10"
                          }`}
                        >
                          غائب 🔴
                        </button>
                      </div>
                    </td>
                    <td className="py-3 px-4 font-mono text-xs text-muted-foreground">{att.phone || "-"}</td>
                    <td className="py-3 px-4 font-mono text-xs text-muted-foreground">{att.father_phone || "-"}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Modal: Live Camera QR Scanner */}
      {showScannerModal && (
        <div className="fixed inset-0 z-50 bg-background/80 backdrop-blur-md flex items-center justify-center p-4">
          <div className="glass-card w-full max-w-3xl p-6 rounded-3xl border border-primary/20 space-y-4 shadow-2xl relative">
            <button
              onClick={() => {
                setShowScannerModal(false);
                loadAttendance(selectedSessionId);
              }}
              className="absolute top-4 left-4 p-2 rounded-full bg-muted hover:bg-muted/80 text-muted-foreground hover:text-foreground"
            >
              <X className="h-5 w-5" />
            </button>
            <h3 className="text-base font-bold flex items-center gap-2">
              <QrCode className="h-5 w-5 text-primary" />
              كاميرا المسح الفوري لـ QR الكود
            </h3>
            <CenterAttendanceScanner sessions={sessions} onAttendanceUpdated={() => loadAttendance(selectedSessionId)} />
          </div>
        </div>
      )}

      {/* Modal: Add Session */}
      {showAddModal && (
        <div className="fixed inset-0 z-50 bg-background/80 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="glass-card w-full max-w-md p-6 rounded-2xl border border-border space-y-4 shadow-2xl">
            <h3 className="text-base font-bold">إضافة حصة دراسية جديدة</h3>
            <form onSubmit={handleCreateSession} className="space-y-3">
              <div>
                <Label className="text-xs">المجموعة الدراسية:</Label>
                <select
                  value={newGroupId}
                  onChange={(e) => setNewGroupId(e.target.value)}
                  className="w-full h-10 rounded-lg bg-background border border-border px-3 text-xs"
                  required
                >
                  <option value="" disabled>
                    {groups.length === 0 ? "لا توجد مجموعات مضافة (أضف من صفحة المجموعات)" : "اختر المجموعة الدراسية"}
                  </option>
                  {groups.map((g) => (
                    <option key={g.id} value={g.id}>
                      {g.name}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <Label className="text-xs">موضوع / عنوان الحصة:</Label>
                <Input
                  placeholder="مثال: الاشتقاق وتطبيقاته - الدرس الأول"
                  value={newTopic}
                  onChange={(e) => setNewTopic(e.target.value)}
                  required
                />
              </div>

              <div>
                <Label className="text-xs">التاريخ:</Label>
                <Input type="date" value={newDate} onChange={(e) => setNewDate(e.target.value)} required />
              </div>

              <div className="flex items-center gap-2 p-3 rounded-xl bg-primary/10 border border-primary/20">
                <input
                  type="checkbox"
                  id="broadcastCheck"
                  checked={broadcastToGrade}
                  onChange={(e) => setBroadcastToGrade(e.target.checked)}
                  className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary cursor-pointer"
                />
                <label htmlFor="broadcastCheck" className="text-xs font-bold text-primary cursor-pointer">
                  تعميم هذه الحصة على جميع مجموعات هذا الصف الدراسي 🌟
                </label>
              </div>

              <div className="flex gap-2 pt-2">
                <Button type="submit" className="flex-1 font-bold">
                  حفظ الحصة
                </Button>
                <Button type="button" variant="outline" onClick={() => setShowAddModal(false)}>
                  إلغاء
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
