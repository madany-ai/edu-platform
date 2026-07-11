"use client";

import { useAuth } from "@/providers/auth-provider";
import { User, Phone, Key, Shield } from "lucide-react";
import { Button } from "@/components/ui/button";

export default function SettingsPage() {
  const { user } = useAuth();

  return (
    <div className="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
      <div className="p-6 rounded-2xl bg-gradient-to-r from-primary/10 via-secondary/5 to-transparent border border-white/5">
        <h1 className="text-3xl font-extrabold text-gradient mb-2">
          إعدادات الحساب ⚙️
        </h1>
        <p className="text-sm text-muted-foreground">
          استعرض بياناتك الشخصية وحالة حسابك على المنصة.
        </p>
      </div>

      <div className="grid gap-6 md:grid-cols-2 max-w-4xl">
        <div className="glass-card p-6 rounded-2xl border border-white/5 space-y-6">
          <h3 className="text-lg font-bold text-foreground pb-3 border-b border-white/5 flex items-center gap-2">
            <User className="h-5 w-5 text-primary" />
            البيانات الشخصية
          </h3>
          <div className="space-y-4 text-sm">
            <div className="flex justify-between">
              <span className="text-muted-foreground">الاسم الكامل</span>
              <span className="font-medium text-foreground">{user?.name}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">كود الطالب</span>
              <span className="font-mono text-primary font-bold">{user?.student?.student_code || "غير متوفر"}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">حالة الحساب</span>
              <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-success/10 text-success border border-success/20">
                {user?.status === "active" ? "نشط ومفعل" : user?.status}
              </span>
            </div>
          </div>
        </div>

        <div className="glass-card p-6 rounded-2xl border border-white/5 space-y-6">
          <h3 className="text-lg font-bold text-foreground pb-3 border-b border-white/5 flex items-center gap-2">
            <Phone className="h-5 w-5 text-primary" />
            أرقام التواصل والتسجيل
          </h3>
          <div className="space-y-4 text-sm">
            <div className="flex justify-between">
              <span className="text-muted-foreground">رقم الهاتف</span>
              <span className="font-medium text-foreground">{user?.student?.phone || "غير مسجل"}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">رقم هاتف الأب</span>
              <span className="font-medium text-foreground">{user?.student?.father_phone || "غير مسجل"}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">رقم هاتف الأم</span>
              <span className="font-medium text-foreground">{user?.student?.mother_phone || "غير مسجل"}</span>
            </div>
          </div>
        </div>

        <div className="glass-card p-6 rounded-2xl border border-white/5 space-y-6 md:col-span-2">
          <h3 className="text-lg font-bold text-foreground pb-3 border-b border-white/5 flex items-center gap-2">
            <Shield className="h-5 w-5 text-primary" />
            أمان الحساب
          </h3>
          <div className="space-y-4 max-w-md">
            <p className="text-xs text-muted-foreground leading-relaxed">
              لتغيير كلمة المرور أو تحديث بيانات التواصل الخاصة بك، يرجى تقديم طلب مباشر للأستاذ أو لأحد المساعدين المعتمدين في المركز.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
