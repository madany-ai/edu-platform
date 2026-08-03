"use client";

import { Button } from "@/components/ui/button";
import { XCircle, RefreshCcw, Home } from "lucide-react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { Suspense } from "react";

function FailedContent() {
  const searchParams = useSearchParams();
  const reason = searchParams.get("reason") || "حدث خطأ غير متوقع أثناء معالجة عملية الدفع.";

  return (
    <div className="container py-12 max-w-2xl mx-auto text-center space-y-6">
      <div className="flex justify-center mb-6">
        <div className="h-24 w-24 bg-destructive/10 text-destructive rounded-full flex items-center justify-center">
          <XCircle className="h-12 w-12" />
        </div>
      </div>
      
      <h1 className="text-3xl font-bold">فشلت عملية الدفع</h1>
      <p className="text-muted-foreground text-lg">
        للأسف، لم نتمكن من إتمام عملية الدفع.
      </p>

      <div className="bg-destructive/10 text-destructive p-4 rounded-lg inline-block max-w-md w-full">
        <p className="text-sm">{reason}</p>
      </div>

      <div className="flex flex-col sm:flex-row justify-center gap-4 mt-8">
        <Button size="lg" variant="default" className="w-full sm:w-auto gap-2" onClick={() => window.history.back()}>
          <RefreshCcw className="h-4 w-4" />
          المحاولة مرة أخرى
        </Button>
        <Link href="/dashboard">
          <Button variant="outline" size="lg" className="w-full sm:w-auto gap-2">
            <Home className="h-4 w-4" />
            العودة للرئيسية
          </Button>
        </Link>
      </div>
    </div>
  );
}

export default function CheckoutFailedPage() {
  return (
    <Suspense fallback={<div className="container py-12 text-center">جاري التحميل...</div>}>
      <FailedContent />
    </Suspense>
  );
}
