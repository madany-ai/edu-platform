"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { useAuth } from "@/providers/auth-provider";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { AlertCircle, Loader2, Atom, Phone, KeyRound } from "lucide-react";
import { Turnstile } from "@marsidev/react-turnstile";

export default function LoginPage() {
  const router = useRouter();
  const { login } = useAuth();
  const [activeTab, setActiveTab] = useState<"code" | "phone">("code");
  const [inputValue, setInputValue] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [turnstileToken, setTurnstileToken] = useState("");

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(false);

    // Clean inputs: if phone, remove spaces and hyphens
    let identifier = inputValue.trim();
    if (activeTab === "phone") {
      identifier = identifier.replace(/[^\d+]/g, "");
      if (!identifier) {
        setError("يرجى إدخال رقم هاتف صحيح");
        return;
      }
    } else {
      if (!identifier) {
        setError("يرجى إدخال كود الطالب الخاص بك");
        return;
      }
    }

    setLoading(true);
    try {
      await login({ email: identifier, password, "cf-turnstile-response": turnstileToken });
      router.push("/dashboard");
    } catch (err: unknown) {
      if (err && typeof err === "object" && "response" in err) {
        const resp = (err as { response: { data?: { message?: string } } }).response;
        setError(resp?.data?.message || "بيانات الدخول غير صحيحة. تأكد من الكود/الهاتف وكلمة المرور.");
      } else {
        setError("حدث خطأ أثناء الاتصال بالخادم. يرجى المحاولة لاحقاً.");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="w-full max-w-md p-1 rounded-2xl bg-gradient-to-b from-primary/30 to-secondary/10 cosmic-border-glow">
      <div className="glass-card w-full p-8 rounded-2xl">
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center p-3 rounded-full bg-primary/10 text-primary mb-4 animate-pulse">
            <Atom className="h-10 w-10 text-primary science-glow-text" />
          </div>
          <h2 className="text-2xl font-bold tracking-tight text-gradient mb-1">
            مختبر العلوم
          </h2>
          <p className="text-xs text-muted-foreground font-medium uppercase tracking-wider">
            عالم المعرفة والاستكشاف • مرحباً بك
          </p>
        </div>

        {/* Tab Selectors */}
        <div className="grid grid-cols-2 p-1 rounded-lg bg-muted mb-6 border border-border/50">
          <button
            type="button"
            onClick={() => {
              setActiveTab("code");
              setInputValue("");
              setError("");
            }}
            className={`flex items-center justify-center gap-2 py-2 text-sm font-medium rounded-md transition-all ${
              activeTab === "code"
                ? "bg-background text-primary shadow-sm"
                : "text-muted-foreground hover:text-foreground"
            }`}
          >
            <KeyRound className="h-4 w-4" />
            <span>كود الطالب</span>
          </button>
          <button
            type="button"
            onClick={() => {
              setActiveTab("phone");
              setInputValue("");
              setError("");
            }}
            className={`flex items-center justify-center gap-2 py-2 text-sm font-medium rounded-md transition-all ${
              activeTab === "phone"
                ? "bg-background text-primary shadow-sm"
                : "text-muted-foreground hover:text-foreground"
            }`}
          >
            <Phone className="h-4 w-4" />
            <span>رقم الهاتف</span>
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-5">
          {error && (
            <div className="flex flex-col gap-1 rounded-lg bg-destructive/10 border border-destructive/20 p-3 text-sm text-destructive">
              <div className="flex items-center gap-2 font-semibold">
                <AlertCircle className="h-4 w-4 shrink-0" />
                <span>خطأ في الدخول</span>
              </div>
              <span className="text-xs opacity-90 wrap-break-word max-h-32 overflow-y-auto mt-1">
                {error.includes("Not null violation") ? "حدث خطأ داخلي في الخادم. يرجى المحاولة لاحقاً." : error}
              </span>
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="login-input" className="text-sm font-medium text-foreground">
              {activeTab === "code" ? "كود الطالب الخاص بك" : "رقم هاتف الطالب"}
            </Label>
            <div className="relative">
              <Input
                id="login-input"
                type="text"
                placeholder={
                  activeTab === "code" ? "مثال: ST30012" : "مثال: 01000000000"
                }
                value={inputValue}
                onChange={(e) => {
                  setInputValue(e.target.value);
                  setError("");
                }}
                required
                className="w-full bg-background/50 border-border/60 focus-visible:ring-primary/50 text-foreground"
              />
            </div>
            <p className="text-[11px] text-muted-foreground font-medium">
              {activeTab === "code"
                ? "أدخل الكود المستلم من الإدارة لبدء تصفح محاضراتك"
                : "أدخل رقم الهاتف المسجل به حسابك لتسجيل الدخول"}
            </p>
          </div>

          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <Label htmlFor="password" className="text-sm font-medium text-foreground">
                كلمة المرور
              </Label>
            </div>
            <Input
              id="password"
              type="password"
              placeholder="••••••••"
              value={password}
              onChange={(e) => {
                setPassword(e.target.value);
                setError("");
              }}
              required
              className="w-full bg-background/50 border-border/60 focus-visible:ring-primary/50 text-foreground"
            />
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
            <span>دخول المختبر 🧪</span>
          </Button>

          <p className="text-center text-sm text-muted-foreground pt-2">
            ليس لديك حساب طالب؟{" "}
            <Link href="/register" className="font-semibold text-primary hover:underline hover:text-primary-fixed transition-colors">
              إنشاء حساب جديد
            </Link>
          </p>
        </form>
      </div>
    </div>
  );
}
