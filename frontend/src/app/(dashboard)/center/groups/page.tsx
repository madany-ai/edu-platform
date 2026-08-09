"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { centerService, Group, AcademicYear } from "@/services/center.service";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Users, Plus, Loader2, Calendar, Filter, GraduationCap, Clock, CheckCircle2 } from "lucide-react";

export default function GroupsPage() {
  const [groups, setGroups] = useState<Group[]>([]);
  const [academicYears, setAcademicYears] = useState<AcademicYear[]>([]);
  const [gradeLevels, setGradeLevels] = useState<Array<{ id: string; name: string }>>([]);
  const [loading, setLoading] = useState(true);

  // Selected Hierarchical Filters
  const [selectedYearId, setSelectedYearId] = useState<string>("");
  const [selectedGradeId, setSelectedGradeId] = useState<string>("");

  // Modal State
  const [showAddGroupModal, setShowAddGroupModal] = useState(false);
  const [showAddYearModal, setShowAddYearModal] = useState(false);

  const [newGroupName, setNewGroupName] = useState("");
  const [newGroupGradeId, setNewGroupGradeId] = useState("");
  const [newGroupCapacity, setNewGroupCapacity] = useState("40");

  const [newYearName, setNewYearName] = useState("");
  const [newYearStart, setNewYearStart] = useState("");
  const [newYearEnd, setNewYearEnd] = useState("");

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    setLoading(true);
    try {
      const [groupsData, yearsData, gradesData] = await Promise.all([
        centerService.getGroups(),
        centerService.getAcademicYears(),
        centerService.getGradeLevels(),
      ]);
      setGroups(groupsData);
      setAcademicYears(yearsData);
      setGradeLevels(gradesData);

      if (yearsData.length > 0 && !selectedYearId) {
        setSelectedYearId(yearsData[0].id);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const handleCreateGroup = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await centerService.createGroup({
        name: newGroupName,
        grade_level_id: newGroupGradeId || gradeLevels[0]?.id,
        academic_year_id: selectedYearId || academicYears[0]?.id,
        capacity: parseInt(newGroupCapacity) || 40,
        is_active: true,
      });
      setShowAddGroupModal(false);
      setNewGroupName("");
      loadData();
    } catch (err) {
      alert("حدث خطأ أثناء إضافة المجموعة.");
    }
  };

  const handleCreateYear = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await centerService.createAcademicYear({
        name: newYearName,
        start_date: newYearStart || undefined,
        end_date: newYearEnd || undefined,
        is_active: true,
      });
      setShowAddYearModal(false);
      setNewYearName("");
      loadData();
    } catch (err) {
      alert("حدث خطأ أثناء إضافة السنة الدراسية.");
    }
  };

  if (loading) {
    return (
      <div className="min-h-[70vh] flex flex-col items-center justify-center gap-3">
        <Loader2 className="h-10 w-10 animate-spin text-primary" />
        <p className="text-sm font-semibold text-muted-foreground">جاري تحميل هيكل المجموعات والسنوات الدراسية...</p>
      </div>
    );
  }

  // Filter groups hierarchically
  const filteredGroups = groups.filter((g) => {
    const matchesYear = !selectedYearId || !g.academic_year_id || g.academic_year_id === selectedYearId;
    const matchesGrade = !selectedGradeId || g.grade_level_id === selectedGradeId;
    return matchesYear && matchesGrade;
  });

  return (
    <div className="container mx-auto px-4 py-8 space-y-8">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border pb-4">
        <div className="flex items-center gap-3">
          <div className="p-3 rounded-2xl bg-primary/10 text-primary">
            <Users className="h-6 w-6" />
          </div>
          <div>
            <h1 className="text-2xl font-extrabold text-foreground">تصفية وهيكلة المجموعات والسنوات الدراسية</h1>
            <p className="text-xs text-muted-foreground mt-0.5">
              اختيار العام الدراسي ثم الصف الدراسي لعرض وتعديل المجموعات والمواعيد بانتظام.
            </p>
          </div>
        </div>

        <div className="flex items-center gap-2">
          <Button onClick={() => setShowAddGroupModal(true)} className="gap-2 font-bold shadow-md">
            <Plus className="h-4 w-4" /> إضافة مجموعة دراسية
          </Button>
          <Button onClick={() => setShowAddYearModal(true)} variant="outline" className="gap-2 font-bold">
            <Plus className="h-4 w-4" /> سنة دراسية جديدة
          </Button>
        </div>
      </div>

      {/* Step 1 & Step 2 Hierarchical Filter Bar */}
      <div className="glass-card p-6 rounded-3xl border border-primary/20 bg-gradient-to-r from-primary/5 via-background to-secondary/5 space-y-6">
        {/* Step 1: Academic Year Filter */}
        <div className="space-y-3">
          <Label className="text-xs font-bold text-primary flex items-center gap-2">
            <Calendar className="h-4 w-4" />
            الخطوة 1: اختر العام الدراسي المسجل
          </Label>

          <div className="flex items-center gap-3 flex-wrap">
            <button
              onClick={() => setSelectedYearId("")}
              className={`px-4 py-2 rounded-xl text-xs font-bold transition-all border ${
                !selectedYearId
                  ? "bg-primary text-white border-primary shadow-md shadow-primary/20"
                  : "bg-background/60 text-muted-foreground border-border hover:border-primary/40"
              }`}
            >
              جميع السنوات الدراسية
            </button>
            {academicYears.map((y) => (
              <button
                key={y.id}
                onClick={() => setSelectedYearId(y.id)}
                className={`px-4 py-2 rounded-xl text-xs font-bold transition-all border ${
                  selectedYearId === y.id
                    ? "bg-primary text-white border-primary shadow-md shadow-primary/20"
                    : "bg-background/60 text-muted-foreground border-border hover:border-primary/40"
                }`}
              >
                {y.name}
              </button>
            ))}
          </div>
        </div>

        {/* Step 2: Grade Level Filter */}
        <div className="space-y-3 pt-4 border-t border-border/40">
          <Label className="text-xs font-bold text-primary flex items-center gap-2">
            <GraduationCap className="h-4 w-4" />
            الخطوة 2: اختر الصف الدراسي
          </Label>

          <div className="flex items-center gap-2 flex-wrap">
            <button
              onClick={() => setSelectedGradeId("")}
              className={`px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all border ${
                !selectedGradeId
                  ? "bg-secondary text-secondary-foreground border-secondary shadow-sm"
                  : "bg-background/60 text-muted-foreground border-border hover:border-secondary/40"
              }`}
            >
              جميع الصفوف
            </button>

            {gradeLevels.map((gl) => (
              <button
                key={gl.id}
                onClick={() => setSelectedGradeId(gl.id)}
                className={`px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all border ${
                  selectedGradeId === gl.id
                    ? "bg-secondary text-secondary-foreground border-secondary shadow-sm"
                    : "bg-background/60 text-muted-foreground border-border hover:border-secondary/40"
                }`}
              >
                {gl.name}
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Filtered Groups Display Grid */}
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-sm font-bold flex items-center gap-2">
            <Users className="h-4 w-4 text-primary" />
            المجموعات المحددة ({filteredGroups.length})
          </h3>
          <span className="text-xs text-muted-foreground">
            عرض المجموعات حسب الصف والعام الدراسي
          </span>
        </div>

        {filteredGroups.length === 0 ? (
          <div className="py-16 text-center text-muted-foreground text-xs glass-card rounded-2xl">
            لا توجد مجموعات مسجلة تفي بالعام أو الصف الدراسي المختار.
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredGroups.map((g) => (
              <div
                key={g.id}
                className="glass-card p-6 rounded-2xl border border-border space-y-4 hover:border-primary/40 transition-all shadow-sm"
              >
                <div className="flex items-start justify-between">
                  <div>
                    <h4 className="text-base font-bold text-foreground">{g.name}</h4>
                    <p className="text-xs text-primary font-medium mt-0.5">{g.grade_level?.name || "صف دراسي غير محدد"}</p>
                  </div>
                  <span className="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400">
                    نشطة 🟢
                  </span>
                </div>

                <div className="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-border/40">
                  <div>
                    <span className="text-muted-foreground block font-semibold">السعة الاستيعابية:</span>
                    <span className="font-bold">{g.capacity} طالب</span>
                  </div>
                  <div>
                    <span className="text-muted-foreground block font-semibold">الطلاب الحافلين:</span>
                    <span className="font-bold text-primary">{g.students_count || 0} طالب</span>
                  </div>
                </div>

                <div className="pt-2">
                  <Link href={`/center/groups/${g.id}`}>
                    <Button size="sm" className="w-full font-bold text-xs gap-1.5 shadow-sm">
                      <Users className="h-3.5 w-3.5" /> فتح ورشة عمل المجموعة ➔
                    </Button>
                  </Link>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Modal: Add Group */}
      {showAddGroupModal && (
        <div className="fixed inset-0 z-50 bg-background/80 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="glass-card w-full max-w-md p-6 rounded-2xl border border-border space-y-4 shadow-2xl">
            <h3 className="text-base font-bold">إضافة مجموعة دراسية جديدة</h3>
            <form onSubmit={handleCreateGroup} className="space-y-3">
              <div>
                <Label className="text-xs">اسم المجموعة والمواعيد:</Label>
                <Input
                  placeholder="مثال: أولى ثانوي - مجموعة السبت والثلاثاء (5:30 مساءً)"
                  value={newGroupName}
                  onChange={(e) => setNewGroupName(e.target.value)}
                  required
                />
              </div>

              <div>
                <Label className="text-xs">الصف الدراسي:</Label>
                <select
                  value={newGroupGradeId}
                  onChange={(e) => setNewGroupGradeId(e.target.value)}
                  className="w-full h-10 rounded-lg bg-background border border-border px-3 text-xs"
                >
                  {gradeLevels.map((gl) => (
                    <option key={gl.id} value={gl.id}>
                      {gl.name}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <Label className="text-xs">السعة الاستيعابية للطلاب:</Label>
                <Input type="number" value={newGroupCapacity} onChange={(e) => setNewGroupCapacity(e.target.value)} required />
              </div>

              <div className="flex gap-2 pt-2">
                <Button type="submit" className="flex-1 font-bold">
                  حفظ المجموعة
                </Button>
                <Button type="button" variant="outline" onClick={() => setShowAddGroupModal(false)}>
                  إلغاء
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Add Year */}
      {showAddYearModal && (
        <div className="fixed inset-0 z-50 bg-background/80 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="glass-card w-full max-w-md p-6 rounded-2xl border border-border space-y-4 shadow-2xl">
            <h3 className="text-base font-bold">إضافة سنة دراسية جديدة</h3>
            <form onSubmit={handleCreateYear} className="space-y-3">
              <div>
                <Label className="text-xs">اسم السنة الدراسية:</Label>
                <Input
                  placeholder="مثال: العام الدراسي 2026 - 2027"
                  value={newYearName}
                  onChange={(e) => setNewYearName(e.target.value)}
                  required
                />
              </div>

              <div>
                <Label className="text-xs">تاريخ البداية:</Label>
                <Input type="date" value={newYearStart} onChange={(e) => setNewYearStart(e.target.value)} />
              </div>

              <div>
                <Label className="text-xs">تاريخ النهاية:</Label>
                <Input type="date" value={newYearEnd} onChange={(e) => setNewYearEnd(e.target.value)} />
              </div>

              <div className="flex gap-2 pt-2">
                <Button type="submit" className="flex-1 font-bold">
                  حفظ السنة الدراسية
                </Button>
                <Button type="button" variant="outline" onClick={() => setShowAddYearModal(false)}>
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
