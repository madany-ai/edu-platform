"use client";

import { Button } from "@/components/ui/button";
import Link from "next/link";

export default function NotFound() {
  return (
    <div className="flex min-h-[60vh] flex-col items-center justify-center text-center">
      <h1 className="text-6xl font-bold text-primary">404</h1>
      <h2 className="mt-4 text-xl font-semibold text-on-surface">الصفحة غير موجودة</h2>
      <p className="mt-2 text-muted-foreground">عذراً، الصفحة التي تبحث عنها غير موجودة.</p>
      <Link href="/" className="mt-6">
        <Button>العودة للرئيسية</Button>
      </Link>
    </div>
  );
}
