"use client";

import { useState } from "react";
import { useCenterFilters } from "@/providers/center-filters-provider";
import { GRADE_LEVELS } from "@/lib/constants";
import { Button } from "@/components/ui/button";
import { Plus, X, Loader2 } from "lucide-react";
import { centerService } from "@/services/center.service";

export function CenterFiltersPanel({ onClose }: { onClose?: () => void }) {
  const {
    academicYears,
    selectedYearId,
    selectedGrade,
    selectedTerm,
    setSelectedYearId,
    setSelectedGrade,
    setSelectedTerm,
    refreshAcademicYears,
  } = useCenterFilters();

  const [showAddYearModal, setShowAddYearModal] = useState(false);
  const [newYearName, setNewYearName] = useState("");
  const [isAddingYear, setIsAddingYear] = useState(false);

  const handleAddYear = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newYearName.trim()) return;
    
    setIsAddingYear(true);
    try {
      await centerService.createAcademicYear({ name: newYearName, is_active: true });
      await refreshAcademicYears();
      setShowAddYearModal(false);
      setNewYearName("");
    } catch (err) {
      console.error(err);
      alert("حدث خطأ أثناء إضافة العام الدراسي.");
    } finally {
      setIsAddingYear(false);
    }
  };

  return (
    <div className="mb-6 space-y-4 rounded-2xl bg-black/20 p-4 border border-white/5">
      <div className="flex items-center justify-between">
        <h4 className="text-xs font-bold text-muted-foreground uppercase tracking-wider">نطاق العمل</h4>
      </div>

      {/* Academic Year Selection */}
      <div className="space-y-1.5">
        <div className="flex items-center justify-between">
          <label className="text-[11px] font-semibold text-muted-foreground">العام الدراسي</label>
          <button 
            onClick={() => setShowAddYearModal(true)}
            className="text-[10px] flex items-center gap-1 text-primary hover:text-primary/80 transition-colors font-bold"
          >
            <Plus className="h-3 w-3" /> جديد
          </button>
        </div>
        <select
          value={selectedYearId}
          onChange={(e) => setSelectedYearId(e.target.value)}
          className="w-full h-8 rounded-lg bg-background/50 border border-white/10 px-2 text-xs font-medium focus:ring-1 focus:ring-primary focus:border-primary transition-all text-foreground"
        >
          {academicYears.length === 0 ? (
            <option value="">لا يوجد عام دراسي</option>
          ) : (
            academicYears.map((y) => (
              <option key={y.id} value={y.id}>
                {y.name}
              </option>
            ))
          )}
        </select>
      </div>

      {/* Grade Level Selection */}
      <div className="space-y-1.5">
        <label className="text-[11px] font-semibold text-muted-foreground">الصف الدراسي</label>
        <select
          value={selectedGrade}
          onChange={(e) => setSelectedGrade(e.target.value)}
          className="w-full h-8 rounded-lg bg-background/50 border border-white/10 px-2 text-xs font-medium focus:ring-1 focus:ring-primary focus:border-primary transition-all text-foreground"
        >
          {GRADE_LEVELS.map((g) => (
            <option key={g.id} value={g.id}>
              {g.name}
            </option>
          ))}
        </select>
      </div>

      {/* Term Selection */}
      <div className="space-y-1.5">
        <label className="text-[11px] font-semibold text-muted-foreground">الترم</label>
        <div className="grid grid-cols-2 gap-2">
          <button
            onClick={() => setSelectedTerm("term_1")}
            className={`h-8 rounded-lg text-xs font-bold transition-all border ${
              selectedTerm === "term_1"
                ? "bg-primary/20 border-primary/40 text-primary"
                : "bg-background/50 border-white/10 text-muted-foreground hover:bg-background/80"
            }`}
          >
            الترم 1
          </button>
          <button
            onClick={() => setSelectedTerm("term_2")}
            className={`h-8 rounded-lg text-xs font-bold transition-all border ${
              selectedTerm === "term_2"
                ? "bg-primary/20 border-primary/40 text-primary"
                : "bg-background/50 border-white/10 text-muted-foreground hover:bg-background/80"
            }`}
          >
            الترم 2
          </button>
        </div>
      </div>

      {/* Add Year Modal */}
      {showAddYearModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4">
          <div className="glass-card w-full max-w-sm rounded-2xl p-6 border border-border animate-in zoom-in-95 duration-200">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-bold">إضافة عام دراسي جديد</h3>
              <button onClick={() => setShowAddYearModal(false)} className="text-muted-foreground hover:text-foreground">
                <X className="h-5 w-5" />
              </button>
            </div>
            <form onSubmit={handleAddYear} className="space-y-4">
              <div className="space-y-2">
                <label className="text-sm font-semibold">اسم العام الدراسي (مثال: 2026-2027)</label>
                <input
                  type="text"
                  required
                  value={newYearName}
                  onChange={(e) => setNewYearName(e.target.value)}
                  className="w-full h-10 rounded-lg bg-background border border-border px-3 text-sm font-medium focus:ring-2 focus:ring-primary"
                  placeholder="2026-2027"
                />
              </div>
              <Button type="submit" disabled={isAddingYear} className="w-full font-bold">
                {isAddingYear ? <Loader2 className="h-4 w-4 animate-spin" /> : "حفظ العام الدراسي"}
              </Button>
            </form>
          </div>
        </div>
      )}
      {onClose && (
        <div className="pt-2">
          <Button onClick={onClose} className="w-full text-xs font-bold bg-primary hover:bg-primary-fixed text-white shadow-md">
            تطبيق وإغلاق
          </Button>
        </div>
      )}
    </div>
  );
}
