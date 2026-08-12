"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { useAuth } from "@/providers/auth-provider";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import api from "@/services/api.client";
import { AuthGuard } from "@/components/layout/auth-guard";

const changePasswordSchema = z
  .object({
    password: z.string().min(8, "كلمة المرور يجب أن تكون 8 أحرف على الأقل"),
    password_confirmation: z.string(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "كلمات المرور غير متطابقة",
    path: ["password_confirmation"],
  });

type ChangePasswordFormValues = z.infer<typeof changePasswordSchema>;

export default function ChangePasswordPage() {
  const router = useRouter();
  const { user } = useAuth();
  const [loading, setLoading] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<ChangePasswordFormValues>({
    resolver: zodResolver(changePasswordSchema),
  });

  const onSubmit = async (data: ChangePasswordFormValues) => {
    setLoading(true);
    try {
      await api.put("/auth/change-password", {
        password: data.password,
        password_confirmation: data.password_confirmation,
      });
      toast.success("تم تغيير كلمة المرور بنجاح");
      window.location.href = "/dashboard";
    } catch (error: any) {
      toast.error(error.response?.data?.message || "حدث خطأ أثناء تغيير كلمة المرور");
    } finally {
      setLoading(false);
    }
  };

  if (user && !user.must_change_password) {
    router.push("/dashboard");
    return null;
  }

  return (
    <AuthGuard requireAuth>
      <div className="min-h-screen flex items-center justify-center bg-black/95 p-4">
        <div className="w-full max-w-md bg-zinc-900 border border-zinc-800 p-8 rounded-2xl shadow-xl space-y-6">
          <div className="text-center space-y-2">
            <h1 className="text-2xl font-bold text-white">تغيير كلمة المرور إجباري</h1>
            <p className="text-sm text-zinc-400">
              لأسباب أمنية، يرجى تغيير كلمة المرور الافتراضية الخاصة بك للمتابعة.
            </p>
          </div>

          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="password">كلمة المرور الجديدة</Label>
              <Input
                id="password"
                type="password"
                {...register("password")}
                className="bg-zinc-800/50 border-zinc-700"
                placeholder="••••••••"
              />
              {errors.password && (
                <p className="text-xs text-red-500">{errors.password.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="password_confirmation">تأكيد كلمة المرور</Label>
              <Input
                id="password_confirmation"
                type="password"
                {...register("password_confirmation")}
                className="bg-zinc-800/50 border-zinc-700"
                placeholder="••••••••"
              />
              {errors.password_confirmation && (
                <p className="text-xs text-red-500">{errors.password_confirmation.message}</p>
              )}
            </div>

            <Button
              type="submit"
              className="w-full bg-primary hover:bg-primary-hover text-primary-foreground"
              disabled={loading}
            >
              {loading ? "جاري الحفظ..." : "حفظ والمتابعة"}
            </Button>
          </form>
        </div>
      </div>
    </AuthGuard>
  );
}
