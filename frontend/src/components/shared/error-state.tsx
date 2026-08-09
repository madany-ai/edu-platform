import { cn } from "@/lib/utils";
import { type ReactNode } from "react";
import { AlertTriangle } from "lucide-react";
import { Button } from "@/components/ui/button";

interface ErrorStateProps {
  title?: string;
  description?: string;
  message?: string;
  onRetry?: () => void;
  icon?: ReactNode;
  className?: string;
}

export function ErrorState({
  title = "حدث خطأ ما",
  description,
  message,
  onRetry,
  icon,
  className,
}: ErrorStateProps) {
  const displayDescription = message || description || "حدث خطأ أثناء تحميل البيانات. يرجى المحاولة مرة أخرى.";
  return (
    <div className={cn("flex flex-col items-center justify-center py-16 text-center", className)}>
      <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-error/10">
        {icon || <AlertTriangle className="h-8 w-8 text-error" />}
      </div>
      <h3 className="text-lg font-semibold text-on-surface">{title}</h3>
      <p className="mt-2 max-w-sm text-sm text-muted-foreground">{displayDescription}</p>
      {onRetry && (
        <Button onClick={onRetry} variant="outline" className="mt-6">
          إعادة المحاولة
        </Button>
      )}
    </div>
  );
}
