"use client";

import { useQuery } from "@tanstack/react-query";
import { Bell, Calendar, Sparkles } from "lucide-react";
import { dashboardService } from "@/services/dashboard.service";
import { PageLoading } from "@/components/shared/loading-spinner";
import { Button } from "@/components/ui/button";

interface NotificationItem {
  id: string;
  title: string;
  body: string;
  created_at: string;
  read_at: string | null;
}

export default function NotificationsPage() {
  const { data: notifications, isLoading, error, refetch } = useQuery<NotificationItem[]>({
    queryKey: ["notifications"],
    queryFn: () => dashboardService.getNotifications(),
    staleTime: 30_000,
  });

  return (
    <div className="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
      <div className="p-6 rounded-2xl bg-gradient-to-r from-primary/10 via-secondary/5 to-transparent border border-white/5">
        <h1 className="text-3xl font-extrabold text-gradient mb-2">
          مركز الإشعارات 🔔
        </h1>
        <p className="text-sm text-muted-foreground">
          تابع آخر التنبيهات والأخبار الموجهة لك من معلم المادة والإدارة.
        </p>
      </div>

      {isLoading ? (
        <PageLoading />
      ) : error ? (
        <div className="flex flex-col items-center justify-center py-20 space-y-4">
          <p className="text-muted-foreground">فشل تحميل الإشعارات</p>
          <Button variant="outline" onClick={() => refetch()}>إعادة المحاولة</Button>
        </div>
      ) : (notifications || []).length === 0 ? (
        <div className="glass-card p-12 text-center rounded-2xl border border-white/5 space-y-3">
          <div className="inline-flex h-12 w-12 items-center justify-center rounded-full bg-muted/10 text-muted-foreground">
            <Bell className="h-6 w-6" />
          </div>
          <p className="text-sm text-muted-foreground">لا توجد إشعارات جديدة حالياً.</p>
        </div>
      ) : (
        <div className="space-y-4 max-w-3xl">
          {(notifications || []).map((notif) => {
            const isSystem = notif.title.includes("أمان") || notif.title.includes("تحديث");
            const Icon = isSystem ? Bell : Sparkles;
            const formattedDate = new Date(notif.created_at).toLocaleDateString("ar-EG", {
              day: "numeric",
              month: "long",
              hour: "2-digit",
              minute: "2-digit",
            });

            return (
              <div key={notif.id} className="glass-card p-6 rounded-2xl border border-white/5 flex gap-4 transition-all hover:bg-[#272d28]/80">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 border border-primary/20 text-primary">
                  <Icon className="h-5 w-5" />
                </div>
                <div className="space-y-1.5 flex-1">
                  <div className="flex items-center justify-between">
                    <h4 className="font-bold text-foreground text-sm">{notif.title}</h4>
                    <span className="text-[10px] text-muted-foreground flex items-center gap-1">
                      <Calendar className="h-3 w-3" />
                      {formattedDate}
                    </span>
                  </div>
                  <p className="text-xs text-muted-foreground leading-relaxed">{notif.body}</p>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
