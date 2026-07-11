"use client";

import { useQuery } from "@tanstack/react-query";
import { dashboardService } from "@/services/dashboard.service";
import { Users, Phone, ShieldCheck, Mail, Search } from "lucide-react";
import { useState } from "react";
import { SearchInput } from "@/components/shared/search-input";

export default function InstructorStudentsPage() {
  const { data: students = [], isLoading } = useQuery({
    queryKey: ["instructor-students"],
    queryFn: dashboardService.getInstructorStudents,
  });

  const [search, setSearch] = useState("");

  const filteredStudents = students.filter((student: any) => {
    const fullName = `${student.first_name || ""} ${student.last_name || ""}`.toLowerCase();
    const code = (student.student_code || "").toLowerCase();
    const phone = (student.phone || "");
    const searchLower = search.toLowerCase();
    return (
      fullName.includes(searchLower) ||
      code.includes(searchLower) ||
      phone.includes(searchLower)
    );
  });

  return (
    <div className="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
      <div className="p-6 rounded-2xl bg-linear-to-r from-primary/10 via-secondary/5 to-transparent border border-white/5">
        <h1 className="text-3xl font-extrabold text-gradient mb-2 flex items-center gap-3">
          إدارة الطلاب 👥
        </h1>
        <p className="text-sm text-muted-foreground">
          تابع بيانات الطلاب المسجلين وحالة تفعيل حساباتهم في كورساتك.
        </p>
      </div>

      <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
        <div className="w-full sm:max-w-md">
          <SearchInput
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            onClear={() => setSearch("")}
            showClear={search.length > 0}
            placeholder="البحث بالاسم أو الكود أو رقم الهاتف..."
          />
        </div>
        <div className="text-xs text-muted-foreground">
          إجمالي عدد الطلاب: <span className="text-primary font-bold">{students.length}</span>
        </div>
      </div>

      {isLoading ? (
        <div className="py-20 text-center">
          <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-primary mx-auto mb-4" />
          <p className="text-sm text-muted-foreground">جاري تحميل قائمة الطلاب...</p>
        </div>
      ) : filteredStudents.length === 0 ? (
        <div className="glass-card p-12 rounded-2xl text-center border border-white/5 space-y-4">
          <Users className="h-12 w-12 text-muted-foreground/30 mx-auto" />
          <p className="text-sm text-muted-foreground">لا يوجد طلاب مطابقين لمعايير البحث حالياً.</p>
        </div>
      ) : (
        <div className="glass-card rounded-2xl border border-white/5 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-right border-collapse text-sm">
              <thead>
                <tr className="border-b border-white/5 bg-background/50 text-muted-foreground font-semibold">
                  <th className="p-4">اسم الطالب</th>
                  <th className="p-4">كود الطالب</th>
                  <th className="p-4">رقم الهاتف</th>
                  <th className="p-4">هاتف الأب والأم</th>
                  <th className="p-4">الحالة</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-white/5">
                {filteredStudents.map((student: any) => (
                  <tr key={student.id} className="hover:bg-background/20 transition-colors">
                    <td className="p-4 font-bold text-foreground">
                      {student.first_name} {student.last_name}
                    </td>
                    <td className="p-4 font-mono text-xs text-primary font-bold">
                      {student.student_code}
                    </td>
                    <td className="p-4 font-mono text-xs text-foreground">
                      {student.phone || "غير متوفر"}
                    </td>
                    <td className="p-4 space-y-1">
                      {student.father_phone && (
                        <div className="text-[11px] text-muted-foreground flex items-center gap-1">
                          <span className="font-semibold text-foreground">الأب:</span> {student.father_phone}
                        </div>
                      )}
                      {student.mother_phone && (
                        <div className="text-[11px] text-muted-foreground flex items-center gap-1">
                          <span className="font-semibold text-foreground">الأم:</span> {student.mother_phone}
                        </div>
                      )}
                      {!student.father_phone && !student.mother_phone && (
                        <span className="text-xs text-muted-foreground/50">غير مسجل</span>
                      )}
                    </td>
                    <td className="p-4">
                      <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border ${
                        student.user?.status === "active"
                          ? "bg-success/10 text-success border-success/20"
                          : student.user?.status === "pending"
                          ? "bg-warning/10 text-warning border-warning/20"
                          : "bg-destructive/10 text-destructive border-destructive/20"
                      }`}>
                        {student.user?.status === "active"
                          ? "نشط ومفعل"
                          : student.user?.status === "pending"
                          ? "قيد المراجعة"
                          : "مرفوض"}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}
