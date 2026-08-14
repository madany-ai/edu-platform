"use client";

import { useState } from "react";
import { useSearchParams } from "next/navigation";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { AlertCircle, Loader2, Calculator, CheckCircle2, Lock } from "lucide-react";
import { authService } from "@/services/auth.service";

export default function ResetPasswordPage() {
  const searchParams = useSearchParams();
  const token = searchParams.get("token") || "";
  const email = searchParams.get("email") || "";

  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");

    if (!token || !email) {
      setError("رابط إعادة التعيين غير صالح. يرجى طلب رابط جديد.");
      return;
    }

    if (password.length < 8) {
      setError("كلمة المرور يجب أن تكون 8 أحرف على الأقل");
      return;
    }

    if (password !== passwordConfirmation) {
      setError("كلمة المرور غير متطابقة");
      return;
    }

    setLoading(true);
    try {
      await authService.resetPassword({
        email,
        token,
        password,
        password_confirmation: passwordConfirmation,
      });
      setSuccess(true);
    } catch (err: unknown) {
      if (err && typeof err === "object" && "response" in err) {
        const resp = (err as { response: { data?: { message?: string } } }).response;
        setError(resp?.data?.message || "رابط إعادة التعيين غير صالح أو منتهي الصلاحية.");
      } else {
        setError("حدث خطأ أثناء الاتصال بالخادم.");
      }
    } finally {
      setLoading(false);
    }
  };

  if (!token || !email) {
    return (
      <div className="w-full max-w-md p-1 rounded-2xl bg-gradient-to-b from-primary/30 to-secondary/10 cosmic-border-glow">
        <div className="glass-card w-full p-8 rounded-2xl text-center">
          <AlertCircle className="h-12 w-12 text-destructive mx-auto mb-4" />
          <h2 className="mb-2 text-xl font-bold text-foreground">رابط غير صالح</h2>
          <p className="text-sm text-muted-foreground mb-6">
            رابط إعادة تعيين كلمة المرور غير صالح أو منتهي الصلاحية.
            يرجى طلب رابط جديد من صفحة نسيت كلمة المرور.
          </p>
          <Link href="/forgot-password">
            <Button className="w-full">طلب رابط جديد</Button>
          </Link>
        </div>
      </div>
    );
  }

  if (success) {
    return (
      <div className="w-full max-w-md p-1 rounded-2xl bg-gradient-to-b from-primary/30 to-secondary/10 cosmic-border-glow animate-fade-in">
        <div className="glass-card w-full p-8 rounded-2xl text-center">
          <div className="mb-6 flex justify-center">
            <div className="rounded-full bg-primary/10 p-4 animate-bounce">
              <CheckCircle2 className="h-12 w-12 text-primary science-glow-text" />
            </div>
          </div>
          <h2 className="mb-2 text-2xl font-bold text-gradient">تم إعادة التعيين بنجاح!</h2>
          <p className="mb-6 text-sm text-muted-foreground leading-relaxed">
            تم تغيير كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول بكلمة المرور الجديدة.
          </p>
          <Link href="/login">
            <Button className="w-full py-2 bg-gradient-to-r from-primary to-secondary text-primary-foreground font-bold shadow-lg">
              تسجيل الدخول
            </Button>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="w-full max-w-md p-1 rounded-2xl bg-gradient-to-b from-primary/30 to-secondary/10 cosmic-border-glow">
      <div className="glass-card w-full p-8 rounded-2xl">
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center p-3 rounded-full bg-primary/10 text-primary mb-4">
            <Lock className="h-10 w-10 text-primary science-glow-text" />
          </div>
          <h2 className="text-2xl font-bold tracking-tight text-gradient mb-1">
            إعادة تعيين كلمة المرور
          </h2>
          <p className="text-xs text-muted-foreground">
            أدخل كلمة المرور الجديدة لحسابك
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-5">
          {error && (
            <div className="flex flex-col gap-1 rounded-lg bg-destructive/10 border border-destructive/20 p-3 text-sm text-destructive">
              <div className="flex items-center gap-2 font-semibold">
                <AlertCircle className="h-4 w-4 shrink-0" />
                <span>خطأ</span>
              </div>
              <span className="text-xs opacity-90">{error}</span>
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="password" className="text-sm font-medium text-foreground">
              كلمة المرور الجديدة
            </Label>
            <Input
              id="password"
              type="password"
              placeholder="••••••••"
              value={password}
              onChange={(e) => { setPassword(e.target.value); setError(""); }}
              required
              className="w-full bg-background/50 border-border/60 focus-visible:ring-primary/50 text-foreground"
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="password_confirmation" className="text-sm font-medium text-foreground">
              تأكيد كلمة المرور
            </Label>
            <Input
              id="password_confirmation"
              type="password"
              placeholder="••••••••"
              value={passwordConfirmation}
              onChange={(e) => { setPasswordConfirmation(e.target.value); setError(""); }}
              required
              className="w-full bg-background/50 border-border/60 focus-visible:ring-primary/50 text-foreground"
            />
          </div>

          <Button
            type="submit"
            className="w-full py-2 bg-gradient-to-r from-primary to-secondary hover:opacity-90 text-primary-foreground font-bold shadow-lg transition-all"
            disabled={loading}
          >
            {loading && <Loader2 className="ml-2 h-4 w-4 animate-spin" />}
            <span>إعادة تعيين كلمة المرور</span>
          </Button>

          <p className="text-center text-sm text-muted-foreground pt-2">
            <Link href="/login" className="font-semibold text-primary hover:underline hover:text-primary-fixed transition-colors">
              العودة لتسجيل الدخول
            </Link>
          </p>
        </form>
      </div>
    </div>
  );
}
