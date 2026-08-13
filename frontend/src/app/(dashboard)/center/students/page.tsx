"use client";

import { useState, useEffect } from "react";
import { centerService, Group } from "@/services/center.service";
import { StudentReportModal } from "@/components/center/student-report-modal";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Search, Eye, Loader2, User, Plus, ArrowRightLeft, CheckCircle2, Printer } from "lucide-react";

import { GRADE_LEVELS } from "@/lib/constants";
import { useCenterFilters } from "@/providers/center-filters-provider";
import { toast } from "sonner";

export default function StudentsDirectoryPage() {
  const [students, setStudents] = useState<any[]>([]);
  const [groups, setGroups] = useState<Group[]>([]);
  const [searchQuery, setSearchQuery] = useState("");
  const [loading, setLoading] = useState(true);
  const [selectedStudentId, setSelectedStudentId] = useState<string | null>(null);
  const [selectedStudents, setSelectedStudents] = useState<any[]>([]);

  const { selectedYearId, selectedGrade, selectedTerm } = useCenterFilters();

  // Modals
  const [showAddStudentModal, setShowAddStudentModal] = useState(false);
  const [transferStudent, setTransferStudent] = useState<any | null>(null);
  const [targetGroupId, setTargetGroupId] = useState("");
  const [msg, setMsg] = useState("");

  // Add student form
  const [firstName, setFirstName] = useState("");
  const [secondName, setSecondName] = useState("");
  const [lastName, setLastName] = useState("");
  const [phone, setPhone] = useState("");
  const [fatherPhone, setFatherPhone] = useState("");
  const [selectedGradeLevel, setSelectedGradeLevel] = useState("");
  const [selectedGroupId, setSelectedGroupId] = useState("");



  useEffect(() => {
    initData();
  }, [selectedYearId, selectedGrade, selectedTerm]);

  const initData = async () => {
    setLoading(true);
    try {
      const [studentsRes, groupsData] = await Promise.all([
        centerService.getStudents({
          search: searchQuery,
          academic_year_id: selectedYearId,
          academic_year: selectedGrade,
          term: selectedTerm,
        }),
        centerService.getGroups({
          academic_year_id: selectedYearId,
          academic_year: selectedGrade,
          term: selectedTerm,
        }),
      ]);
      setStudents(studentsRes.data || []);
      setGroups(groupsData);
      setSelectedGradeLevel(selectedGrade || GRADE_LEVELS[0].id);
      if (groupsData.length > 0) setSelectedGroupId(groupsData[0].id);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = async () => {
    setLoading(true);
    try {
      const res = await centerService.getStudents({
        search: searchQuery,
        academic_year_id: selectedYearId,
        academic_year: selectedGrade,
        term: selectedTerm,
      });
      setStudents(res.data || []);
    } catch (e) {
    } finally {
      setLoading(false);
    }
  };

  const handleCreateStudent = async (e: React.FormEvent) => {
    e.preventDefault();
    setMsg("");
    try {
      const res = await centerService.createStudent({
        first_name: firstName,
        second_name: secondName,
        last_name: lastName,
        phone,
        father_phone: fatherPhone,
        academic_year: selectedGradeLevel,
        group_id: selectedGroupId,
      });
      setMsg(res.message);
      setShowAddStudentModal(false);
      setFirstName("");
      setLastName("");
      setPhone("");
      setFatherPhone("");
      initData();
    } catch (err) {
      alert("حدث خطأ أثناء إضافة الطالب.");
    }
  };

  const handleSelectAll = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.checked) {
      const newSelections = students.filter(s => !selectedStudents.some(sel => sel.id === s.id));
      setSelectedStudents([...selectedStudents, ...newSelections]);
    } else {
      setSelectedStudents(selectedStudents.filter(sel => !students.some(s => s.id === sel.id)));
    }
  };

  const handleSelectStudent = (student: any) => {
    setSelectedStudents(prev => 
      prev.some(s => s.id === student.id) 
        ? prev.filter(s => s.id !== student.id) 
        : [...prev, student]
    );
  };

  const [showBulkTransferModal, setShowBulkTransferModal] = useState(false);

  const handleTransferGroup = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!targetGroupId) return;
    try {
      if (transferStudent) {
        await centerService.updateStudentGroup(transferStudent.id, targetGroupId);
        setMsg(`تم نقل الطالب ${transferStudent.first_name} إلى المجموعة الجديدة بنجاح! 🟢`);
        setTransferStudent(null);
      } else if (showBulkTransferModal && selectedStudents.length > 0) {
        await centerService.bulkUpdateStudentGroup(selectedStudents.map(s => s.id), targetGroupId);
        setMsg(`تم نقل ${selectedStudents.length} طلاب إلى المجموعة الجديدة بنجاح! 🟢`);
        setShowBulkTransferModal(false);
        setSelectedStudents([]);
      }
      initData();
    } catch (err) {
      alert("حدث خطأ أثناء نقل الطلاب.");
    }
  };

  return (
    <div className="w-full max-w-7xl mx-auto px-4 py-8 space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border pb-4">
        <div className="flex items-center gap-3">
          <div className="p-3 rounded-2xl bg-primary/10 text-primary">
            <User className="h-6 w-6" />
          </div>
          <div>
            <h1 className="text-2xl font-extrabold text-foreground">دليل الطلاب والتقارير الشاملة</h1>
            <p className="text-xs text-muted-foreground mt-0.5">
              إضافة طلاب جدد، نقل الطلاب بين المجموعات، وتصفح التقرير الشامل والتواصل مع أولياء الأمور.
            </p>
          </div>
        </div>

        <div className="flex flex-wrap gap-2">
          <Button 
            variant="secondary" 
            onClick={() => window.print()} 
            disabled={selectedStudents.length === 0}
            className="gap-2 font-bold shadow-md bg-white border border-gray-200 text-gray-800 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Printer className="h-4 w-4" /> 
            {selectedStudents.length > 0 ? `طباعة الكارنيهات (${selectedStudents.length})` : 'طباعة الكارنيهات'}
          </Button>
          <Button 
            variant="outline" 
            onClick={() => {
              setTargetGroupId(groups[0]?.id || "");
              setShowBulkTransferModal(true);
            }} 
            disabled={selectedStudents.length === 0}
            className="gap-2 font-bold shadow-md text-primary border-primary/20 hover:bg-primary/5 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <ArrowRightLeft className="h-4 w-4" /> 
            نقل مجموعة
          </Button>
          <Button onClick={() => setShowAddStudentModal(true)} className="gap-2 font-bold shadow-md">
            <Plus className="h-4 w-4" /> إضافة طالب جديد
          </Button>
        </div>
      </div>

      {msg && (
        <div className="glass-card p-4 rounded-xl border border-emerald-500/40 bg-emerald-500/10 text-emerald-400 text-sm font-bold flex items-center gap-2">
          <CheckCircle2 className="h-5 w-5" />
          <span>{msg}</span>
        </div>
      )}

      {/* Search Bar */}
      <div className="glass-card p-4 rounded-2xl border border-border flex gap-2">
        <Input
          placeholder="ابحث باسم الطالب، كود الطالب (ST2026101) أو رقم الهاتف..."
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          onKeyDown={(e) => e.key === "Enter" && handleSearch()}
          className="h-11 text-sm font-medium"
        />
        <Button onClick={handleSearch} className="h-11 px-6 font-bold gap-2">
          <Search className="h-4 w-4" /> بحث
        </Button>
      </div>

      {/* Students Directory Table */}
      <div className="glass-card rounded-2xl border border-border overflow-hidden">
        {loading ? (
          <div className="py-16 text-center text-muted-foreground flex flex-col items-center gap-2">
            <Loader2 className="h-8 w-8 animate-spin text-primary" />
            <span className="text-xs">جاري البحث وتجميـع نتائج الطلاب...</span>
          </div>
        ) : students.length === 0 ? (
          <div className="py-16 text-center text-muted-foreground text-xs">
            لم يتم العثور على أي طالب يطابق البحث الحالي.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-right text-sm border-collapse">
              <thead>
                <tr className="bg-muted/50 border-b border-border text-xs font-bold text-muted-foreground whitespace-nowrap">
                  <th className="py-3.5 px-4 w-12 text-center">
                    <input 
                      type="checkbox" 
                      className="w-4 h-4 rounded border-gray-300 cursor-pointer"
                      checked={students.length > 0 && students.every(s => selectedStudents.some(sel => sel.id === s.id))}
                      onChange={handleSelectAll}
                    />
                  </th>
                  <th className="py-3.5 px-4">كود الطالب</th>
                  <th className="py-3.5 px-4">اسم الطالب</th>
                  <th className="py-3.5 px-4">المجموعة الدراسية</th>
                  <th className="py-3.5 px-4">الصف الدراسي</th>
                  <th className="py-3.5 px-4">هاتف الطالب</th>
                  <th className="py-3.5 px-4">هاتف ولي الأمر</th>
                  <th className="py-3.5 px-4 text-center">إجراءات الطالب</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border/40">
                {students.map((st) => (
                  <tr key={st.id} className="hover:bg-muted/30 transition-colors whitespace-nowrap">
                    <td className="py-3.5 px-4 text-center">
                      <input 
                        type="checkbox" 
                        className="w-4 h-4 rounded border-gray-300 cursor-pointer"
                        checked={selectedStudents.some(sel => sel.id === st.id)}
                        onChange={() => handleSelectStudent(st)}
                      />
                    </td>
                    <td className="py-3.5 px-4 font-mono text-xs text-primary font-bold">{st.student_code}</td>
                    <td className="py-3.5 px-4 font-bold text-foreground">
                      {st.first_name} {st.second_name} {st.last_name}
                    </td>
                    <td className="py-3.5 px-4 text-xs font-semibold">{st.group?.name || "غير محدد"}</td>
                    <td className="py-3.5 px-4 text-xs text-muted-foreground">
                      {GRADE_LEVELS.find((y) => y.id === (st.academic_year || st.group?.academic_year))?.name || "غير محدد"}
                    </td>
                    <td className="py-3.5 px-4 font-mono text-xs text-muted-foreground">{st.phone || "-"}</td>
                    <td className="py-3.5 px-4 font-mono text-xs text-muted-foreground">{st.father_phone || "-"}</td>
                    <td className="py-3.5 px-4 text-center">
                      <div className="flex items-center justify-center gap-2">
                        <Button
                          size="sm"
                          variant="secondary"
                          onClick={() => setSelectedStudentId(st.id)}
                          className="h-8 text-xs font-bold gap-1.5"
                        >
                          <Eye className="h-3.5 w-3.5 text-primary" /> التقرير
                        </Button>
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => {
                            setTransferStudent(st);
                            setTargetGroupId(st.group_id || groups[0]?.id || "");
                          }}
                          className="h-8 text-xs font-bold gap-1 text-muted-foreground hover:text-foreground"
                        >
                          <ArrowRightLeft className="h-3.5 w-3.5" /> نقل مجموعة
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Modal: Add New Student */}
      {showAddStudentModal && (
        <div className="fixed inset-0 z-50 bg-background/80 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="glass-card w-full max-w-md p-6 rounded-2xl border border-border space-y-4 shadow-2xl">
            <h3 className="text-base font-bold">تسجيل طالب جديد بالسنتر</h3>
            <form onSubmit={handleCreateStudent} className="space-y-3">
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

              <div>
                <Label className="text-xs">الصف الدراسي:</Label>
                <select
                  value={selectedGradeLevel}
                  onChange={(e) => setSelectedGradeLevel(e.target.value)}
                  className="w-full h-10 rounded-lg bg-background border border-border px-3 text-xs"
                >
                  {GRADE_LEVELS.map((gl) => (
                    <option key={gl.id} value={gl.id}>
                      {gl.name}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <Label className="text-xs">المجموعة الدراسية:</Label>
                <select
                  value={selectedGroupId}
                  onChange={(e) => setSelectedGroupId(e.target.value)}
                  className="w-full h-10 rounded-lg bg-background border border-border px-3 text-xs"
                >
                  {groups.map((g) => (
                    <option key={g.id} value={g.id}>
                      {g.name}
                    </option>
                  ))}
                </select>
              </div>

              <div className="flex gap-2 pt-2">
                <Button type="submit" className="flex-1 font-bold">
                  حفظ وتسجيل الطالب
                </Button>
                <Button type="button" variant="outline" onClick={() => setShowAddStudentModal(false)}>
                  إلغاء
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Transfer Student Group */}
      {(transferStudent || showBulkTransferModal) && (
        <div className="fixed inset-0 z-50 bg-background/80 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="glass-card w-full max-w-md p-6 rounded-2xl border border-border space-y-4 shadow-2xl">
            <h3 className="text-base font-bold">
              {transferStudent 
                ? `نقل الطالب (${transferStudent.first_name} ${transferStudent.last_name}) لمجموعة جديدة`
                : `نقل ${selectedStudents.length} طلاب إلى مجموعة جديدة`
              }
            </h3>
            <form onSubmit={handleTransferGroup} className="space-y-4">
              <div>
                <Label className="text-xs font-semibold block mb-1">اختر المجموعة الدراسية الجديدة:</Label>
                <select
                  value={targetGroupId}
                  onChange={(e) => setTargetGroupId(e.target.value)}
                  className="w-full h-11 rounded-xl bg-background border border-border px-3 text-sm font-semibold"
                >
                  {groups.map((g) => (
                    <option key={g.id} value={g.id}>
                      {g.name} ({GRADE_LEVELS.find((y) => y.id === g.academic_year)?.name})
                    </option>
                  ))}
                </select>
              </div>

              <div className="flex gap-2 pt-2">
                <Button type="submit" className="flex-1 font-bold">
                  تأكيد النقل
                </Button>
                <Button type="button" variant="outline" onClick={() => {
                  setTransferStudent(null);
                  setShowBulkTransferModal(false);
                }}>
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

      {/* Printable Area */}
      <div id="printable-bulk-cards" className="hidden print:block">
        <style>{`
          @page {
              size: A4 portrait;
              margin: 10mm;
          }
          @media print {
              body * { visibility: hidden; }
              #printable-bulk-cards, #printable-bulk-cards * { visibility: visible; }
              #printable-bulk-cards {
                  position: absolute;
                  left: 0;
                  top: 0;
                  width: 100%;
                  display: block !important;
              }
              .print-page {
                  page-break-after: always;
                  display: grid !important;
                  grid-template-columns: repeat(2, 85mm) !important;
                  justify-content: space-between !important;
                  gap: 8mm 5mm !important;
                  width: 100% !important;
                  padding-left: 15mm !important;
                  padding-right: 5mm !important;
                  margin: 0 !important;
                  box-sizing: border-box !important;
              }
              .print-page:last-child {
                  page-break-after: auto;
              }
              .bulk-card-item {
                  width: 85mm !important;
                  min-height: 54mm !important;
                  margin: 0 auto !important;
                  page-break-inside: avoid !important;
                  break-inside: avoid !important;
                  border: 2px solid #000 !important;
                  border-radius: 10px !important;
                  padding: 15px !important;
                  box-sizing: border-box !important;
                  background: #fff !important;
                  display: flex;
                  flex-direction: column;
                  align-items: center;
                  justify-content: center;
                  text-align: center;
              }
              .bulk-card-item * {
                  color: #000 !important;
              }
          }
        `}</style>
        
        {Array.from({ length: Math.ceil(selectedStudents.length / 8) }).map((_, pageIndex) => {
          const pageStudents = selectedStudents.slice(pageIndex * 8, (pageIndex + 1) * 8);
          
          return (
            <div key={pageIndex} className="print-page">
              {pageStudents.map(student => (
                <div key={student.id} className="bulk-card-item">
                  <div className="mb-2 bg-white flex-shrink-0">
                    <img
                        src={`https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(student.student_code || student.id)}`}
                        alt="QR Code"
                        className="w-28 h-28 object-contain mx-auto"
                    />
                  </div>
                  <div className="space-y-1.5 w-full border-t-2 border-gray-200 pt-3 mt-1">
                      <div className="text-sm font-mono font-bold tracking-widest text-gray-900">
                          {student.student_code || 'بدون كود'}
                      </div>
                      <div className="font-bold text-base text-gray-900 leading-snug line-clamp-1">
                          {student.first_name} {student.second_name} {student.third_name}
                      </div>
                  </div>
                </div>
              ))}
            </div>
          );
        })}
      </div>
    </div>
  );
}
