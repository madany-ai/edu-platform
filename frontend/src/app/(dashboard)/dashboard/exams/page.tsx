"use client";

import { BookOpen } from "lucide-react";
import { Button } from "@/components/ui/button";
import Link from "next/link";
import { ROUTES } from "@/lib/constants";

export default function ExamsPage() {
  return (
    <div className="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
      <div className="p-6 rounded-2xl bg-linear-to-r from-primary/10 via-secondary/5 to-transparent border border-white/5">
        <h1 className="text-3xl font-extrabold text-gradient mb-2">
          الامتحانات والواجبات 📝
        </h1>
        <p className="text-sm text-muted-foreground">
          استعرض درجاتك في الامتحانات والواجبات التي قمت بإتمامها.
        </p>
      </div>

      <div className="glass-card p-12 rounded-2xl text-center border border-white/5 max-w-2xl mx-auto space-y-4">
        <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 border border-primary/20 text-primary mx-auto cosmic-border-glow">
          <BookOpen className="h-8 w-8" />
        </div>
        <div className="space-y-2">
          <h3 className="text-lg font-bold text-foreground">لا توجد امتحانات مكتملة بعد</h3>
          <p className="text-sm text-muted-foreground max-w-md mx-auto leading-relaxed">
            بمجرد إتمام الامتحانات والواجبات المرفقة مع الكورسات، ستتمكن من مراجعة درجاتك وتفاصيلها من هنا.
          </p>
        </div>
        <div className="pt-2">
          <Link href={ROUTES.COURSES}>
            <Button className="bg-linear-to-r from-primary to-secondary text-primary-foreground font-bold">
              تصفح الكورسات وابدأ التعلم
            </Button>
          </Link>
        </div>
      </div>
    </div>
  );
}
