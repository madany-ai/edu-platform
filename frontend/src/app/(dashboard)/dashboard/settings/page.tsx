"use client";

import { useAuth } from "@/providers/auth-provider";
import { User, Phone, Shield, Lock } from "lucide-react";
import { PwaManager } from "@/components/pwa-manager";

export default function SettingsPage() {
  const { user } = useAuth();

  return (
    <div className="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
      <div className="p-6 rounded-2xl bg-gradient-to-r from-primary/10 via-secondary/5 to-transparent border border-white/5">
        <h1 className="text-3xl font-extrabold text-gradient mb-2">
          إعدادات الحساب ⚙️
        </h1>
        <p className="text-sm text-muted-foreground">
          استعرض بياناتك الشخصية المسجلة في النظام وحالة حسابك.
        </p>
      </div>

      <div className="grid gap-6 md:grid-cols-2 max-w-4xl">
        {/* البيانات الشخصية */}
        <div className="glass-card p-6 rounded-2xl border border-white/5 space-y-6">
          <h3 className="text-lg font-bold text-foreground pb-3 border-b border-white/5 flex items-center gap-2">
            <User className="h-5 w-5 text-primary" />
            البيانات الشخصية
          </h3>
          <div className="space-y-4 text-sm">
            <div className="flex justify-between items-center py-1 border-b border-white/5">
              <span className="text-muted-foreground">الاسم الكامل</span>
              <span className="font-semibold text-foreground">{user?.name || "غير مسجل"}</span>
            </div>
            <div className="flex justify-between items-center py-1 border-b border-white/5">
              <span className="text-muted-foreground">كود الطالب</span>
              <span className="font-mono text-primary font-bold">{user?.student?.student_code || "غير متوفر"}</span>
            </div>
            <div className="flex justify-between items-center py-1 border-b border-white/5">
              <span className="text-muted-foreground">البريد الإلكتروني</span>
              <span className="font-mono text-foreground">{user?.email || "غير مسجل"}</span>
            </div>
            <div className="flex justify-between items-center py-1">
              <span className="text-muted-foreground">حالة الحساب</span>
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                {user?.status === "active" ? "نشط ومفعل" : user?.status}
              </span>
            </div>
          </div>
        </div>

        {/* أرقام التواصل والتسجيل */}
        <div className="glass-card p-6 rounded-2xl border border-white/5 space-y-6">
          <h3 className="text-lg font-bold text-foreground pb-3 border-b border-white/5 flex items-center gap-2">
            <Phone className="h-5 w-5 text-primary" />
            أرقام التواصل والتسجيل
          </h3>
          <div className="space-y-4 text-sm">
            <div className="flex justify-between items-center py-1 border-b border-white/5">
              <span className="text-muted-foreground">رقم الهاتف الشخصي</span>
              <span className="font-mono font-medium text-foreground">
                {user?.student?.phone || "غير مسجل"}
              </span>
            </div>
            <div className="flex justify-between items-center py-1 border-b border-white/5">
              <span className="text-muted-foreground">رقم هاتف الأب</span>
              <span className="font-mono font-medium text-foreground">
                {user?.student?.father_phone || "غير مسجل"}
              </span>
            </div>
            <div className="flex justify-between items-center py-1">
              <span className="text-muted-foreground">رقم هاتف الأم</span>
              <span className="font-mono font-medium text-foreground">
                {user?.student?.mother_phone || "غير مسجل"}
              </span>
            </div>
          </div>
        </div>

        {/* أمان الحساب (عرض فقط) */}
        <div className="glass-card p-6 rounded-2xl border border-white/5 space-y-4 md:col-span-2">
          <h3 className="text-lg font-bold text-foreground pb-3 border-b border-white/5 flex items-center gap-2">
            <Shield className="h-5 w-5 text-primary" />
            حماية البيانات وأمان الحساب
          </h3>
          <div className="flex items-start gap-3 p-4 rounded-xl bg-white/5 border border-white/5 text-xs text-muted-foreground leading-relaxed">
            <Lock className="h-5 w-5 text-amber-400 shrink-0 mt-0.5" />
            <div>
              <p className="font-semibold text-foreground mb-1">البيانات محمية ولا يمكن تعديلها مباشرة</p>
              <p>
                يتم استعراض هذه البيانات للعرض فقط من قاعدة البيانات لمنع أي تغيير غير مصرح به. لتحديث رقم الهاتف أو بيانات التواصل الخاصة بك، يرجى تقديم طلب مباشر للأستاذ أو لأحد المساعدين المعتمدين.
              </p>
            </div>
          </div>
        </div>

        {/* إعدادات التطبيق والإشعارات */}
        <div className="glass-card p-6 rounded-2xl border border-white/5 space-y-4 md:col-span-2">
          <PwaManager />
        </div>
      </div>
    </div>
  );
}
