"use client";

import { useState } from "react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { AlertCircle, Loader2, Atom, Mail, CheckCircle2 } from "lucide-react";
import { authService } from "@/services/auth.service";
import { Turnstile } from "@marsidev/react-turnstile";

export default function ForgotPasswordPage() {
  const [email, setEmail] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);
  const [turnstileToken, setTurnstileToken] = useState("");

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");

    if (!email || !email.includes("@")) {
      setError("يرجى إدخال بريد إلكتروني صحيح");
      return;
    }

    setLoading(true);
    try {
      await authService.forgotPassword({ email, "cf-turnstile-response": turnstileToken });
      setSent(true);
    } catch (err: unknown) {
      if (err && typeof err === "object" && "response" in err) {
        const resp = (err as { response: { data?: { message?: string } } }).response;
        setError(resp?.data?.message || "حدث خطأ. يرجى المحاولة لاحقاً.");
      } else {
        setError("حدث خطأ أثناء الاتصال بالخادم.");
      }
    } finally {
      setLoading(false);
    }
  };

  if (sent) {
    return (
      <div className="w-full max-w-md p-1 rounded-2xl bg-gradient-to-b from-primary/30 to-secondary/10 cosmic-border-glow animate-fade-in">
        <div className="glass-card w-full p-8 rounded-2xl text-center">
          <div className="mb-6 flex justify-center">
            <div className="rounded-full bg-primary/10 p-4 animate-bounce">
              <CheckCircle2 className="h-12 w-12 text-primary science-glow-text" />
            </div>
          </div>
          <h2 className="mb-2 text-2xl font-bold text-gradient">تم الإرسال بنجاح!</h2>
          <p className="mb-6 text-sm text-muted-foreground leading-relaxed">
            تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.
            <br />
            يرجى مراجعة صندوق الوارد والبريد غير المرغوب فيه.
          </p>
          <Link href="/login">
            <Button className="w-full py-2 bg-gradient-to-r from-primary to-secondary text-primary-foreground font-bold shadow-lg">
              العودة لتسجيل الدخول
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
            <Atom className="h-10 w-10 text-primary science-glow-text" />
          </div>
          <h2 className="text-2xl font-bold tracking-tight text-gradient mb-1">
            نسيت كلمة المرور؟
          </h2>
          <p className="text-xs text-muted-foreground">
            أدخل بريدك الإلكتروني وسنرسل لك رابط لإعادة تعيين كلمة المرور
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
            <Label htmlFor="email" className="text-sm font-medium text-foreground">
              البريد الإلكتروني
            </Label>
            <div className="relative">
              <Mail className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                id="email"
                type="email"
                placeholder="student@example.com"
                value={email}
                onChange={(e) => { setEmail(e.target.value); setError(""); }}
                required
                className="w-full pr-10 bg-background/50 border-border/60 focus-visible:ring-primary/50 text-foreground"
              />
            </div>
          </div>

          <div className="flex justify-center my-4">
            <Turnstile
              siteKey={process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY || "1x00000000000000000000AA"}
              onSuccess={(token) => setTurnstileToken(token)}
              onError={() => setError("حدث خطأ في التحقق. يرجى إعادة تحميل الصفحة.")}
              options={{ theme: "dark" }}
            />
          </div>

          <Button
            type="submit"
            className="w-full py-2 bg-gradient-to-r from-primary to-secondary hover:opacity-90 text-primary-foreground font-bold shadow-lg transition-all"
            disabled={loading}
          >
            {loading && <Loader2 className="ml-2 h-4 w-4 animate-spin" />}
            <span>إرسال رابط إعادة التعيين</span>
          </Button>

          <p className="text-center text-sm text-muted-foreground pt-2">
            تذكرت كلمة المرور؟{" "}
            <Link href="/login" className="font-semibold text-primary hover:underline hover:text-primary-fixed transition-colors">
              تسجيل الدخول
            </Link>
          </p>
        </form>
      </div>
    </div>
  );
}
