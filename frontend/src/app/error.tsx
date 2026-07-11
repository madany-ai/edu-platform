"use client";

import { Button } from "@/components/ui/button";

export default function GlobalError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  return (
    <div className="flex min-h-[60vh] flex-col items-center justify-center text-center">
      <h2 className="text-xl font-semibold text-on-surface">حدث خطأ ما</h2>
      <p className="mt-2 max-w-md text-muted-foreground">
        {error.message || "حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى."}
      </p>
      <Button onClick={reset} variant="outline" className="mt-6">
        إعادة المحاولة
      </Button>
    </div>
  );
}
