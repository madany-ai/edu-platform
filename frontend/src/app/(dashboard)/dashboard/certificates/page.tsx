"use client";

import { Award } from "lucide-react";
import { Button } from "@/components/ui/button";
import Link from "next/link";
import { ROUTES } from "@/lib/constants";

export default function CertificatesPage() {
  return (
    <div className="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
      <div className="p-6 rounded-2xl bg-gradient-to-r from-primary/10 via-secondary/5 to-transparent border border-white/5">
        <h1 className="text-3xl font-extrabold text-gradient mb-2">
          شهاداتي المعتمدة 🏆
        </h1>
        <p className="text-sm text-muted-foreground">
          استعرض وحمّل شهادات التفوق والتميز التي حصلت عليها.
        </p>
      </div>

      <div className="glass-card p-12 rounded-2xl text-center border border-white/5 max-w-2xl mx-auto space-y-4">
        <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 border border-primary/20 text-primary mx-auto cosmic-border-glow">
          <Award className="h-8 w-8" />
        </div>
        <div className="space-y-2">
          <h3 className="text-lg font-bold text-foreground">لا توجد شهادات متوفرة بعد</h3>
          <p className="text-sm text-muted-foreground max-w-md mx-auto leading-relaxed">
            بمجرد إتمام مشاهدة جميع محاضرات الكورسات المسجلة، واجتياز الاختبارات التفاعلية بنجاح، ستتمكن من تحميل شهادتك المعتمدة مباشرة من هنا.
          </p>
        </div>
        <div className="pt-2">
          <Link href={ROUTES.COURSES}>
            <Button className="bg-gradient-to-r from-primary to-secondary text-primary-foreground font-bold">
              تصفح المحاضرات وابدأ التعلم
            </Button>
          </Link>
        </div>
      </div>
    </div>
  );
}
