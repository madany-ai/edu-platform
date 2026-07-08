"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { useAuth } from "@/contexts/auth-context";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { AlertCircle, Loader2 } from "lucide-react";

interface FieldError {
  field: string;
  message: string;
}

export default function RegisterPage() {
  const router = useRouter();
  const { register } = useAuth();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [errors, setErrors] = useState<FieldError[]>([]);
  const [loading, setLoading] = useState(false);

  const getFieldError = (field: string) =>
    errors.find((e) => e.field === field)?.message;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrors([]);
    setLoading(true);
    try {
      await register(name, email, password);
      router.push("/");
    } catch (err: unknown) {
      if (err && typeof err === "object" && "response" in err) {
        const resp = (err as { response: { data?: { errors?: Record<string, string[]>; message?: string } } }).response;
        const data = resp?.data;
        if (data?.errors) {
          const mapped: FieldError[] = [];
          for (const [field, msgs] of Object.entries(data.errors)) {
            mapped.push({ field, message: msgs[0] });
          }
          setErrors(mapped);
        } else {
          setErrors([{ field: "general", message: data?.message || "حدث خطأ في التسجيل" }]);
        }
      } else {
        setErrors([{ field: "general", message: "حدث خطأ في التسجيل" }]);
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <Card className="w-full max-w-md">
      <CardHeader className="text-center">
        <CardTitle className="text-2xl">إنشاء حساب جديد</CardTitle>
        <CardDescription>أدخل بياناتك لإنشاء حساب جديد</CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          {getFieldError("general") && (
            <div className="flex items-center gap-2 rounded-lg bg-destructive/15 p-3 text-sm text-destructive">
              <AlertCircle className="h-4 w-4 shrink-0" />
              <span>{getFieldError("general")}</span>
            </div>
          )}
          <div className="space-y-2">
            <Label htmlFor="name">الاسم الكامل</Label>
            <Input
              id="name"
              type="text"
              placeholder="محمد أحمد"
              value={name}
              onChange={(e) => setName(e.target.value)}
              required
            />
            {getFieldError("name") && (
              <p className="text-sm text-destructive">{getFieldError("name")}</p>
            )}
          </div>
          <div className="space-y-2">
            <Label htmlFor="email">البريد الإلكتروني</Label>
            <Input
              id="email"
              type="email"
              placeholder="name@example.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
            {getFieldError("email") && (
              <p className="text-sm text-destructive">{getFieldError("email")}</p>
            )}
          </div>
          <div className="space-y-2">
            <Label htmlFor="password">كلمة المرور</Label>
            <Input
              id="password"
              type="password"
              placeholder="••••••••"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
            {getFieldError("password") && (
              <p className="text-sm text-destructive">{getFieldError("password")}</p>
            )}
          </div>
          <div className="space-y-2">
            <Label htmlFor="password_confirmation">تأكيد كلمة المرور</Label>
            <Input
              id="password_confirmation"
              type="password"
              placeholder="••••••••"
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
              required
            />
          </div>
          <Button type="submit" className="w-full" disabled={loading}>
            {loading && <Loader2 className="ml-2 h-4 w-4 animate-spin" />}
            إنشاء حساب
          </Button>
          <p className="text-center text-sm text-muted-foreground">
            لديك حساب بالفعل؟{" "}
            <Link href="/login" className="font-medium text-primary hover:underline">
              تسجيل الدخول
            </Link>
          </p>
        </form>
      </CardContent>
    </Card>
  );
}
