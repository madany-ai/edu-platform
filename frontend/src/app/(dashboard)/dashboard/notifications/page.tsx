"use client";

import { Bell, Calendar, Sparkles } from "lucide-react";

export default function NotificationsPage() {
  const notifications = [
    {
      id: 1,
      title: "مرحباً بك في مختبر العلوم الرقمي 🧪",
      body: "أهلاً بك في منصتك التعليمية الجديدة. يمكنك الآن تصفح المحاضرات، حل الامتحانات، ومتابعة إحصائياتك من لوحة التحكم.",
      date: "اليوم",
      icon: Sparkles,
    },
    {
      id: 2,
      title: "تحديث أمان الحساب 🔐",
      body: "تم تفعيل حماية حسابك بنجاح. تسجيل الدخول متاح حصرياً عبر رقم هاتفك أو كود الطالب الخاص بك.",
      date: "أمس",
      icon: Bell,
    },
  ];

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

      <div className="space-y-4 max-w-3xl">
        {notifications.map((notif) => {
          const Icon = notif.icon;
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
                    {notif.date}
                  </span>
                </div>
                <p className="text-xs text-muted-foreground leading-relaxed">{notif.body}</p>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
