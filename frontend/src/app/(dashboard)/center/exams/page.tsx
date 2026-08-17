"use client";

import { useState, useEffect } from "react";
import { centerService, CenterExam, Group } from "@/services/center.service";
import { CenterGradeMatrix } from "@/components/center/center-grade-matrix";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { FileSpreadsheet, Plus, Loader2 } from "lucide-react";

import { useCenterFilters } from "@/providers/center-filters-provider";
import { ACADEMIC_TRACKS } from "@/lib/constants";

export default function ExamsPage() {
  const [exams, setExams] = useState<CenterExam[]>([]);
  const [groups, setGroups] = useState<Group[]>([]);
  const [loading, setLoading] = useState(true);

  const { selectedYearId, selectedGrade, selectedTerm } = useCenterFilters();
  const [academicTrack, setAcademicTrack] = useState("");

  // Modal State
  const [showAddModal, setShowAddModal] = useState(false);
  const [newName, setNewName] = useState("");
  const [newGroupId, setNewGroupId] = useState("");
  const [newMarks, setNewMarks] = useState("30");
  const [newDate, setNewDate] = useState(new Date().toISOString().split("T")[0]);

  useEffect(() => {
    if (selectedGrade !== 'sec_3') {
      setAcademicTrack("");
    }
  }, [selectedGrade]);

  useEffect(() => {
    loadData();
  }, [selectedYearId, selectedGrade, selectedTerm, academicTrack]);

  const loadData = async () => {
    setLoading(true);
    try {
      const filterParams = {
        academic_year_id: selectedYearId,
        academic_year: selectedGrade,
        term: selectedTerm,
        academic_track: selectedGrade === 'sec_3' ? academicTrack : undefined,
      };
      const [examsData, groupsData] = await Promise.all([
        centerService.getExams(filterParams),
        centerService.getGroups(filterParams),
      ]);
      setExams(examsData);
      setGroups(groupsData);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const handleCreateExam = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await centerService.createExam({
        name: newName,
        group_id: newGroupId || groups[0]?.id,
        total_marks: parseFloat(newMarks) || 30,
        date: newDate,
      });
      setShowAddModal(false);
      setNewName("");
      loadData();
    } catch (err) {
      alert("حدث خطأ أثناء إضافة الامتحان.");
    }
  };

  if (loading) {
    return (
      <div className="min-h-[70vh] flex flex-col items-center justify-center gap-3">
        <Loader2 className="h-10 w-10 animate-spin text-amber-500" />
        <p className="text-sm font-semibold text-muted-foreground">جاري تحميل الامتحانات الورقية ورصد الدرجات...</p>
      </div>
    );
  }

  return (
    <div className="w-full max-w-7xl mx-auto px-4 py-8 space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border pb-4">
        <div className="flex items-center gap-3">
          <div className="p-3 rounded-2xl bg-amber-500/10 text-amber-500">
            <FileSpreadsheet className="h-6 w-6" />
          </div>
          <div>
            <h1 className="text-2xl font-extrabold text-foreground">الامتحانات الورقية ورصد درجات السنتر</h1>
            <p className="text-xs text-muted-foreground mt-0.5">
              إضافة الامتحانات الورقية ورصد الدرجات في مصفوفة سريعة وإصدار تنبيهات أولياء الأمور.
            </p>
          </div>
        </div>

        <Button onClick={() => setShowAddModal(true)} className="gap-2 font-bold shadow-md bg-amber-500 hover:bg-amber-600 text-white">
          <Plus className="h-4 w-4" /> إضافة امتحان ورقي جديد
        </Button>
      </div>

      {selectedGrade === 'sec_3' && (
        <div className="glass-card p-4 rounded-2xl border border-border">
          <div className="flex items-center gap-3">
            <span className="text-xs font-bold text-muted-foreground">الشعبة:</span>
            <select
              value={academicTrack}
              onChange={(e) => setAcademicTrack(e.target.value)}
              className="h-10 rounded-lg bg-background border border-border px-3 text-xs font-semibold min-w-[180px]"
            >
              <option value="">جميع الشعب</option>
              {ACADEMIC_TRACKS.map((track) => (
                <option key={track.id} value={track.id}>
                  {track.name}
                </option>
              ))}
            </select>
          </div>
        </div>
      )}

      <CenterGradeMatrix exams={exams} onGradesSaved={loadData} academicTrack={selectedGrade === 'sec_3' ? academicTrack : undefined} />

      {/* Modal: Add Exam */}
      {showAddModal && (
        <div className="fixed inset-0 z-50 bg-background/80 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="glass-card w-full max-w-md p-6 rounded-2xl border border-border space-y-4 shadow-2xl">
            <h3 className="text-base font-bold">إضافة امتحان ورقي للسنتر</h3>
            <form onSubmit={handleCreateExam} className="space-y-3">
              <div>
                <Label className="text-xs">عنوان الامتحان:</Label>
                <Input
                  placeholder="مثال: اختبار شهر سبتمبر في العلوم"
                  value={newName}
                  onChange={(e) => setNewName(e.target.value)}
                  required
                />
              </div>

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
                <Label className="text-xs">الدرجة العظمى (النهائية):</Label>
                <Input type="number" value={newMarks} onChange={(e) => setNewMarks(e.target.value)} required />
              </div>

              <div>
                <Label className="text-xs">التاريخ:</Label>
                <Input type="date" value={newDate} onChange={(e) => setNewDate(e.target.value)} required />
              </div>

              <div className="flex gap-2 pt-2">
                <Button type="submit" className="flex-1 font-bold bg-amber-500 hover:bg-amber-600">
                  حفظ الامتحان
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
