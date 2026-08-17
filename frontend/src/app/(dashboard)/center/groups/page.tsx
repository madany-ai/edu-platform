"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { centerService, Group, AcademicYear } from "@/services/center.service";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Users, Plus, Loader2, Calendar, Filter, GraduationCap, Clock, CheckCircle2, Trash2 } from "lucide-react";
import { GRADE_LEVELS } from "@/lib/constants";

import { toast } from "sonner";
import { useCenterFilters } from "@/providers/center-filters-provider";

export default function GroupsPage() {
  const [groups, setGroups] = useState<Group[]>([]);
  const [loading, setLoading] = useState(true);

  // Modal State
  const [showAddGroupModal, setShowAddGroupModal] = useState(false);
  const [groupToDelete, setGroupToDelete] = useState<Group | null>(null);

  const [newGroupName, setNewGroupName] = useState("");
  const [newGroupCapacity, setNewGroupCapacity] = useState("40");

  const { selectedYearId, selectedGrade, selectedTerm } = useCenterFilters();

  const fetchGroups = async () => {
    try {
      setLoading(true);
      const data = await centerService.getGroups({
        academic_year_id: selectedYearId,
        academic_year: selectedGrade,
        term: selectedTerm,
      });
      setGroups(data);
    } catch (err) {
      console.error(err);
      toast.error("حدث خطأ أثناء جلب المجموعات.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (!selectedYearId) return;
    fetchGroups();
  }, [selectedYearId, selectedGrade, selectedTerm]);



  const handleCreateGroup = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await centerService.createGroup({
        name: newGroupName,
        academic_year_id: selectedYearId,
        academic_year: selectedGrade,
        term: selectedTerm,
        capacity: parseInt(newGroupCapacity) || 40,
        is_active: true,
      });
      setShowAddGroupModal(false);
      setNewGroupName("");
      fetchGroups();
      toast.success("تم إضافة المجموعة بنجاح.");
    } catch (err) {
      toast.error("حدث خطأ أثناء إضافة المجموعة.");
    }
  };

  const handleDeleteGroup = async () => {
    if (!groupToDelete) return;
    try {
      await centerService.deleteGroup(groupToDelete.id);
      toast.success("تم حذف/أرشفة المجموعة بنجاح.");
      setGroupToDelete(null);
      fetchGroups();
    } catch (err: any) {
      if (err.response?.status === 422) {
        toast.error(err.response.data.message);
      } else {
        toast.error("حدث خطأ أثناء حذف المجموعة.");
      }
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

  const filteredGroups = groups;

  return (
    <div className="w-full max-w-7xl mx-auto px-4 py-8 space-y-8">
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

        <div className="flex flex-wrap items-center gap-2">
          <Button onClick={() => setShowAddGroupModal(true)} className="gap-2 font-bold shadow-md">
            <Plus className="h-4 w-4" /> إضافة مجموعة دراسية
          </Button>
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
                    <p className="text-xs text-primary font-medium mt-0.5">
                      {GRADE_LEVELS.find((y) => y.id === g.academic_year)?.name || "صف دراسي غير محدد"}
                    </p>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400">
                      نشطة 🟢
                    </span>
                    <button 
                      onClick={() => setGroupToDelete(g)}
                      className="text-red-400/50 hover:text-red-400 hover:bg-red-400/10 p-1.5 rounded-md transition-colors"
                      title="حذف المجموعة"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
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

      {/* Modal: Delete/Archive Group Confirmation */}
      {groupToDelete && (
        <div className="fixed inset-0 z-50 bg-background/80 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="glass-card w-full max-w-md p-6 rounded-2xl border border-red-500/20 space-y-4 shadow-2xl">
            <div className="flex items-center gap-3 text-red-500">
              <Trash2 className="h-6 w-6" />
              <h3 className="text-base font-bold">حذف / أرشفة المجموعة</h3>
            </div>
            <p className="text-sm text-muted-foreground">
              هل أنت متأكد من حذف المجموعة <strong className="text-foreground">{groupToDelete.name}</strong>؟
            </p>
            <div className="glass-card p-3 rounded-xl border border-amber-500/20 bg-amber-500/5">
              <p className="text-xs text-amber-500 font-semibold">
                إذا كانت المجموعة تحتوي على حصص دراسية، سيتم <strong>أرشفة المجموعة</strong> (تعطيلها) بدلاً من حذفها للحفاظ على سجلات الحضور والدرجات.
              </p>
            </div>
            <div className="flex gap-2 pt-2">
              <Button onClick={handleDeleteGroup} variant="destructive" className="flex-1 font-bold">
                نعم، حذف / أرشفة المجموعة
              </Button>
              <Button variant="outline" onClick={() => setGroupToDelete(null)}>
                إلغاء
              </Button>
            </div>
          </div>
        </div>
      )}


    </div>
  );
}
