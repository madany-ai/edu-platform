"use client";

import { useState, useEffect } from "react";
import { centerService, Group, RankingItem } from "@/services/center.service";
import { Trophy, Medal, Award, Star, Loader2, Filter } from "lucide-react";

interface RankingsCardProps {
  groups: Group[];
}

const ACADEMIC_YEARS = [
  { id: "prep_1", name: "الصف الأول الإعدادي" },
  { id: "prep_2", name: "الصف الثاني الإعدادي" },
  { id: "prep_3", name: "الصف الثالث الإعدادي" },
  { id: "sec_1", name: "الصف الأول الثانوي" },
  { id: "sec_2", name: "الصف الثاني الثانوي" },
  { id: "sec_3", name: "الصف الثالث الثانوي" },
];

export function CenterRankingsCard({ groups }: RankingsCardProps) {
  const [selectedGroup, setSelectedGroup] = useState<string>(groups[0]?.id || "");
  const [selectedGrade, setSelectedGrade] = useState<string>("");
  const [rankings, setRankings] = useState<RankingItem[]>([]);
  const [loading, setLoading] = useState<boolean>(false);

  useEffect(() => {
    fetchRankings();
  }, [selectedGroup, selectedGrade]);

  const fetchRankings = async () => {
    setLoading(true);
    try {
      const data = await centerService.getRankings({
        group_id: selectedGroup || undefined,
        academic_year: selectedGrade || undefined,
      });
      setRankings(data);
    } catch (e) {
      console.error("Failed to load rankings", e);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header & Filter Controls */}
      <div className="glass-card p-6 rounded-2xl border border-amber-500/20 bg-gradient-to-r from-amber-500/10 via-background to-amber-500/5">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h3 className="text-lg font-bold flex items-center gap-2 text-foreground">
              <Trophy className="h-6 w-6 text-amber-500 animate-bounce" />
              <span>لوحة التكريم وترتيب الأوائل المتفوقين</span>
            </h3>
            <p className="text-xs text-muted-foreground mt-1">
              ترتيب الطلاب المتفوقين في امتحانات السنتر حركياً حسب إجمالي النقاط والنسب المئوية.
            </p>
          </div>

          {/* Filters */}
          <div className="flex flex-col sm:flex-row items-center gap-3">
            <div className="w-full sm:w-56">
              <select
                value={selectedGroup}
                onChange={(e) => {
                  setSelectedGroup(e.target.value);
                  if (e.target.value) setSelectedGrade("");
                }}
                className="w-full h-10 rounded-lg bg-background border border-border px-3 text-xs font-semibold focus:ring-2 focus:ring-amber-500"
              >
                <option value="">-- اختار حسب المجموعة --</option>
                {groups.map((g) => (
                  <option key={g.id} value={g.id}>
                    {g.name}
                  </option>
                ))}
              </select>
            </div>

            <div className="w-full sm:w-56">
              <select
                value={selectedGrade}
                onChange={(e) => {
                  setSelectedGrade(e.target.value);
                  if (e.target.value) setSelectedGroup("");
                }}
                className="w-full h-10 rounded-lg bg-background border border-border px-3 text-xs font-semibold focus:ring-2 focus:ring-amber-500"
              >
                <option value="">-- اختار حسب الصف الدراسي --</option>
                {ACADEMIC_YEARS.map((gl) => (
                  <option key={gl.id} value={gl.id}>
                    {gl.name}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </div>
      </div>

      {/* Leaderboard Table */}
      <div className="glass-card rounded-2xl border border-border overflow-hidden">
        {loading ? (
          <div className="py-16 text-center text-muted-foreground flex flex-col items-center gap-2">
            <Loader2 className="h-8 w-8 animate-spin text-amber-500" />
            <span className="text-xs">جاري حساب نتائج الأوائل...</span>
          </div>
        ) : rankings.length === 0 ? (
          <div className="py-16 text-center text-muted-foreground text-xs">
            لا توجد درجات مسجلة للمجموعة أو الصف المختار لعرض قائمة الأوائل.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-right text-sm border-collapse">
              <thead>
                <tr className="bg-amber-500/5 border-b border-amber-500/20 text-xs font-bold text-muted-foreground">
                  <th className="py-3.5 px-4">المركز</th>
                  <th className="py-3.5 px-4">كود الطالب</th>
                  <th className="py-3.5 px-4">اسم الطالب المتفوق</th>
                  <th className="py-3.5 px-4">إجمالي الدرجات</th>
                  <th className="py-3.5 px-4">النسبة المئوية</th>
                  <th className="py-3.5 px-4">عدد الامتحانات</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border/40">
                {rankings.map((item, idx) => {
                  const rank = idx + 1;
                  return (
                    <tr
                      key={item.student_id + idx}
                      className={`transition-colors hover:bg-amber-500/5 ${
                        rank === 1
                          ? "bg-amber-500/10 dark:bg-amber-500/15"
                          : rank === 2
                          ? "bg-slate-500/5"
                          : rank === 3
                          ? "bg-amber-700/5"
                          : ""
                      }`}
                    >
                      <td className="py-3 px-4 font-bold text-base">
                        {rank === 1 ? (
                          <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-500 border border-amber-500/30">
                            🥇 المركز الأول
                          </span>
                        ) : rank === 2 ? (
                          <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-400/20 text-slate-300 border border-slate-400/30">
                            🥈 المركز الثاني
                          </span>
                        ) : rank === 3 ? (
                          <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-700/20 text-amber-600 border border-amber-700/30">
                            🥉 المركز الثالث
                          </span>
                        ) : (
                          <span className="text-muted-foreground font-mono text-sm px-2">#{rank}</span>
                        )}
                      </td>
                      <td className="py-3 px-4 font-mono text-xs text-primary font-semibold">
                        {item.student_code || "بدون كود"}
                      </td>
                      <td className="py-3 px-4 font-bold text-foreground">
                        {item.first_name} {item.second_name} {item.third_name} {item.last_name}
                      </td>
                      <td className="py-3 px-4 font-bold text-foreground">
                        {item.total_score} <span className="text-xs text-muted-foreground font-normal">/ {item.max_score}</span>
                      </td>
                      <td className="py-3 px-4">
                        <span
                          className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-bold ${
                            item.percentage >= 90
                              ? "bg-emerald-500/20 text-emerald-400 border border-emerald-500/30"
                              : "bg-blue-500/20 text-blue-400 border border-blue-500/30"
                          }`}
                        >
                          {item.percentage}%
                        </span>
                      </td>
                      <td className="py-3 px-4 text-xs text-muted-foreground font-medium">
                        {item.exams_count} امتحانات
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
