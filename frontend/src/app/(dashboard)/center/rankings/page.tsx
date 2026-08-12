"use client";

import { useState, useEffect } from "react";
import { centerService, Group } from "@/services/center.service";
import { CenterRankingsCard } from "@/components/center/center-rankings-card";
import { Trophy, Loader2 } from "lucide-react";

export default function RankingsPage() {
  const [groups, setGroups] = useState<Group[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadGroups();
  }, []);

  const loadGroups = async () => {
    setLoading(true);
    try {
      const groupsData = await centerService.getGroups();
      setGroups(groupsData);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-[70vh] flex flex-col items-center justify-center gap-3">
        <Loader2 className="h-10 w-10 animate-spin text-amber-500" />
        <p className="text-sm font-semibold text-muted-foreground">جاري تحميل لوحة تكريم المتفوقين الأوائل...</p>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8 space-y-6">
      <div className="flex items-center gap-3 border-b border-border pb-4">
        <div className="p-3 rounded-2xl bg-amber-500/10 text-amber-500">
          <Trophy className="h-6 w-6" />
        </div>
        <div>
          <h1 className="text-2xl font-extrabold text-foreground">لوحة التكريم وترتيب الطلاب الأوائل</h1>
          <p className="text-xs text-muted-foreground mt-0.5">
            عرض وترتيب المتفوقين في امتحانات السنتر حركياً حسب المجموع والنسبة المئوية.
          </p>
        </div>
      </div>

      <CenterRankingsCard groups={groups} />
    </div>
  );
}
