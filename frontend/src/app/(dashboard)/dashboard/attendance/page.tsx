"use client";

import { useEffect, useState } from "react";
import api from "@/services/api.client";
import { PageHeader } from "@/components/shared/page-header";
import { PageLoading } from "@/components/shared/loading-spinner";
import { ErrorState } from "@/components/shared/error-state";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Calendar, CheckCircle, Clock, UserCheck, XCircle } from "lucide-react";

interface AttendanceRecord {
  id: string;
  date: string;
  topic: string;
  group_name: string;
  status: "present" | "absent" | "late" | "guest";
  is_guest: boolean;
}

interface AttendanceStats {
  total: number;
  present: number;
  absent: number;
  late: number;
  guest: number;
}

export default function StudentAttendancePage() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [stats, setStats] = useState<AttendanceStats | null>(null);
  const [records, setRecords] = useState<AttendanceRecord[]>([]);

  useEffect(() => {
    async function fetchAttendance() {
      try {
        setLoading(true);
        const { data } = await api.get("/center/my-attendance");
        if (data.status === "success") {
          setStats(data.data.stats);
          setRecords(data.data.attendances);
        }
      } catch (err: any) {
        setError(err.response?.data?.message || "فشل في تحميل سجل الحضور");
      } finally {
        setLoading(false);
      }
    }
    fetchAttendance();
  }, []);

  if (loading) return <PageLoading />;
  if (error) return <ErrorState message={error} />;

  const getStatusBadge = (status: string) => {
    switch (status) {
      case "present":
        return <Badge className="bg-emerald-500/10 text-emerald-500 border-emerald-500/20">حاضر ✅</Badge>;
      case "absent":
        return <Badge className="bg-red-500/10 text-red-500 border-red-500/20">غائب ❌</Badge>;
      case "late":
        return <Badge className="bg-amber-500/10 text-amber-500 border-amber-500/20">متأخر ⏳</Badge>;
      case "guest":
        return <Badge className="bg-purple-500/10 text-purple-500 border-purple-500/20">حاضر كضيف 👤</Badge>;
      default:
        return <Badge variant="outline">{status}</Badge>;
    }
  };

  return (
    <div className="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
      <PageHeader
        title="سجل الحضور والغياب"
        description="متابعة نسبة حضورك في حصص السنتر الأوفلاين والدروس"
      />

      {/* Stats Cards */}
      <div className="grid gap-4 md:grid-cols-4">
        <Card className="border-primary/10 bg-primary/5">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">إجمالي الحصص</CardTitle>
            <Calendar className="h-4 w-4 text-primary" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total || 0}</div>
          </CardContent>
        </Card>

        <Card className="border-emerald-500/10 bg-emerald-500/5">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">مرات الحضور</CardTitle>
            <CheckCircle className="h-4 w-4 text-emerald-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-emerald-500">{stats?.present || 0}</div>
          </CardContent>
        </Card>

        <Card className="border-red-500/10 bg-red-500/5">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">مرات الغياب</CardTitle>
            <XCircle className="h-4 w-4 text-red-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-red-500">{stats?.absent || 0}</div>
          </CardContent>
        </Card>

        <Card className="border-amber-500/10 bg-amber-500/5">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">مرات التأخير</CardTitle>
            <Clock className="h-4 w-4 text-amber-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-amber-500">{stats?.late || 0}</div>
          </CardContent>
        </Card>
      </div>

      {/* Attendance History */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg flex items-center gap-2">
            <UserCheck className="h-5 w-5 text-primary" />
            تفاصيل الحصص المسجلة
          </CardTitle>
        </CardHeader>
        <CardContent>
          {records.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              لا يوجد سجلات حضور مسجلة حتى الآن.
            </div>
          ) : (
            <div className="divide-y">
              {records.map((record) => (
                <div key={record.id} className="py-4 flex items-center justify-between">
                  <div className="space-y-1">
                    <p className="font-medium">{record.topic || "حصة دراسية"}</p>
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                      <span>{record.group_name}</span>
                      <span>•</span>
                      <span>{record.date}</span>
                    </div>
                  </div>
                  <div>{getStatusBadge(record.status)}</div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
