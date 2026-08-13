"use client";

import { useState, useEffect } from "react";
import { centerService, AcademicSession } from "@/services/center.service";
import { CenterAttendanceScanner } from "@/components/center/center-attendance-scanner";
import { Loader2, QrCode } from "lucide-react";

import { useCenterFilters } from "@/providers/center-filters-provider";

export default function ScannerPage() {
  const [sessions, setSessions] = useState<AcademicSession[]>([]);
  const [loading, setLoading] = useState(true);

  const { selectedYearId, selectedGrade, selectedTerm } = useCenterFilters();

  useEffect(() => {
    loadSessions();
  }, [selectedYearId, selectedGrade, selectedTerm]);

  const loadSessions = async () => {
    setLoading(true);
    try {
      const data = await centerService.getSessions({
        academic_year_id: selectedYearId,
        academic_year: selectedGrade,
        term: selectedTerm,
      });
      setSessions(data);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-[70vh] flex flex-col items-center justify-center gap-3">
        <Loader2 className="h-10 w-10 animate-spin text-primary" />
        <p className="text-sm font-semibold text-muted-foreground">جاري تحميل ماسح الحضور بالكاميرا...</p>
      </div>
    );
  }

  return (
    <div className="w-full max-w-7xl mx-auto px-4 py-8 space-y-6">
      <div className="flex items-center gap-3 border-b border-border pb-4">
        <div className="p-3 rounded-2xl bg-primary/10 text-primary">
          <QrCode className="h-6 w-6" />
        </div>
        <div>
          <h1 className="text-2xl font-extrabold text-foreground">ماسح الحضور والغياب المباشر (Camera Scanner)</h1>
          <p className="text-xs text-muted-foreground mt-0.5">
            امسح كود الطالب بالكامل بواسطة كاميرا جهازك أو ادخل الكود يدويًا للتسجيل التلقائي.
          </p>
        </div>
      </div>

      <CenterAttendanceScanner sessions={sessions} />
    </div>
  );
}
