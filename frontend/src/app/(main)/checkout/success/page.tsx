"use client";

import { Button } from "@/components/ui/button";
import { CheckCircle2, Home, BookOpen } from "lucide-react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { Suspense } from "react";

function SuccessContent() {
  const searchParams = useSearchParams();
  const orderId = searchParams.get("order") || searchParams.get("merchantRefNum");

  return (
    <div className="container py-12 max-w-2xl mx-auto text-center space-y-6">
      <div className="flex justify-center mb-6">
        <div className="h-24 w-24 bg-emerald-500/10 text-emerald-500 rounded-full flex items-center justify-center">
          <CheckCircle2 className="h-12 w-12" />
        </div>
      </div>
      
      <h1 className="text-3xl font-bold">تم الدفع بنجاح!</h1>
      <p className="text-muted-foreground text-lg">
        شكراً لثقتك بنا. تمت عملية الشراء بنجاح وتم تفعيل المحتوى في حسابك.
      </p>

      {orderId && (
        <div className="bg-muted p-4 rounded-lg inline-block">
          <p className="text-sm font-medium">رقم الطلب المرجعي:</p>
          <p className="font-mono mt-1 text-primary">{orderId}</p>
        </div>
      )}

      <div className="flex flex-col sm:flex-row justify-center gap-4 mt-8">
        <Link href="/dashboard/courses">
          <Button size="lg" className="w-full sm:w-auto gap-2">
            <BookOpen className="h-4 w-4" />
            الذهاب إلى دوراتي
          </Button>
        </Link>
        <Link href="/dashboard">
          <Button variant="outline" size="lg" className="w-full sm:w-auto gap-2">
            <Home className="h-4 w-4" />
            الرئيسية
          </Button>
        </Link>
      </div>
    </div>
  );
}

export default function CheckoutSuccessPage() {
  return (
    <Suspense fallback={<div className="container py-12 text-center">جاري التحميل...</div>}>
      <SuccessContent />
    </Suspense>
  );
}
