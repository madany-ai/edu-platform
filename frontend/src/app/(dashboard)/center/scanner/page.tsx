"use client";

import { useState, useEffect } from "react";
import { centerService, AcademicSession } from "@/services/center.service";
import { CenterAttendanceScanner } from "@/components/center/center-attendance-scanner";
import { Loader2, QrCode } from "lucide-react";

import { useCenterFilters } from "@/providers/center-filters-provider";
import { ACADEMIC_TRACKS } from "@/lib/constants";

export default function ScannerPage() {
  const [sessions, setSessions] = useState<AcademicSession[]>([]);
  const [loading, setLoading] = useState(true);
  const [academicTrack, setAcademicTrack] = useState("");

  const { selectedYearId, selectedGrade, selectedTerm } = useCenterFilters();

  useEffect(() => {
    if (selectedGrade !== 'sec_3') {
      setAcademicTrack("");
    }
  }, [selectedGrade]);

  useEffect(() => {
    loadSessions();
  }, [selectedYearId, selectedGrade, selectedTerm, academicTrack]);

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

      <CenterAttendanceScanner
        sessions={sessions}
        academicTrack={selectedGrade === 'sec_3' ? academicTrack : undefined}
        onAttendanceUpdated={loadSessions}
      />
    </div>
  );
}
