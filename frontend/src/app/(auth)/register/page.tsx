"use client";

import { useState } from "react";
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { AlertCircle, CheckCircle2, Loader2 } from "lucide-react";

interface FieldError {
  field: string;
  message: string;
}

const GENDER_OPTIONS = [
  { value: "male", label: "ذكر" },
  { value: "female", label: "أنثى" },
];

export default function RegisterPage() {
  const { register } = useAuth();
  const [step, setStep] = useState(1);
  const [submitted, setSubmitted] = useState(false);

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");

  const [firstName, setFirstName] = useState("");
  const [secondName, setSecondName] = useState("");
  const [thirdName, setThirdName] = useState("");
  const [lastName, setLastName] = useState("");
  const [gender, setGender] = useState("");
  const [birthDate, setBirthDate] = useState("");
  const [phone, setPhone] = useState("");

  const [fatherPhone, setFatherPhone] = useState("");
  const [motherPhone, setMotherPhone] = useState("");
  const [guardianJob, setGuardianJob] = useState("");

  const [errors, setErrors] = useState<FieldError[]>([]);
  const [loading, setLoading] = useState(false);

  const getFieldError = (field: string) =>
    errors.find((e) => e.field === field)?.message;

  const clearFieldError = (field: string) =>
    setErrors((prev) => prev.filter((e) => e.field !== field));

  const handleNext = () => {
    setErrors([]);
    if (step === 1) {
      const newErrors: FieldError[] = [];
      if (!email) newErrors.push({ field: "email", message: "البريد الإلكتروني مطلوب" });
      if (!password) newErrors.push({ field: "password", message: "كلمة المرور مطلوبة" });
      if (password.length < 8) newErrors.push({ field: "password", message: "كلمة المرور يجب أن تكون 8 أحرف على الأقل" });
      if (password !== passwordConfirmation) newErrors.push({ field: "password_confirmation", message: "كلمة المرور غير متطابقة" });
      if (newErrors.length > 0) {
        setErrors(newErrors);
        return;
      }
    }
    if (step === 2) {
      const newErrors: FieldError[] = [];
      if (!firstName) newErrors.push({ field: "first_name", message: "الاسم الأول مطلوب" });
      if (!lastName) newErrors.push({ field: "last_name", message: "الاسم الأخير مطلوب" });
      if (newErrors.length > 0) {
        setErrors(newErrors);
        return;
      }
    }
    setStep((s) => s + 1);
  };

  const handleBack = () => {
    setErrors([]);
    setStep((s) => s - 1);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrors([]);
    setLoading(true);
    try {
      await register({
        email,
        password,
        password_confirmation: passwordConfirmation,
        first_name: firstName,
        second_name: secondName || undefined,
        third_name: thirdName || undefined,
        last_name: lastName,
        phone: phone || undefined,
        father_phone: fatherPhone || undefined,
        mother_phone: motherPhone || undefined,
        guardian_job: guardianJob || undefined,
        gender: gender || undefined,
        birth_date: birthDate || undefined,
      });
      setSubmitted(true);
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

  if (submitted) {
    return (
      <Card className="w-full max-w-md">
        <CardContent className="pt-8 text-center">
          <div className="mb-4 flex justify-center">
            <div className="rounded-full bg-primary/10 p-4">
              <CheckCircle2 className="h-12 w-12 text-primary" />
            </div>
          </div>
          <CardTitle className="mb-2 text-2xl">تم التسجيل بنجاح</CardTitle>
          <CardDescription className="mb-6 text-base">
            تم إنشاء حسابك بنجاح. حسابك قيد المراجعة من قبل الإدارة.
            <br />
            سيتم إشعارك عند اعتماد حسابك.
          </CardDescription>
          <div className="rounded-lg bg-muted p-4 text-right text-sm text-muted-foreground">
            <p className="mb-2 font-medium text-foreground">ماذا يحدث الآن؟</p>
            <ol className="list-inside list-decimal space-y-1">
              <li>سيتم مراجعة طلبك من قبل الإدارة</li>
              <li>عند الاعتماد، ستتلقى إشعاراً</li>
              <li>يمكنك بعدها تسجيل الدخول والبدء في التعلم</li>
            </ol>
          </div>
          <Link href="/login">
            <Button className="mt-6 w-full">تسجيل الدخول</Button>
          </Link>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="w-full max-w-lg">
      <CardHeader className="text-center">
        <CardTitle className="text-2xl">إنشاء حساب جديد</CardTitle>
        <CardDescription>
          {step === 1 && "أدخل بيانات الدخول"}
          {step === 2 && "البيانات الشخصية"}
          {step === 3 && "بيانات ولي الأمر"}
        </CardDescription>
        <div className="mt-4 flex items-center justify-center gap-2">
          {[1, 2, 3].map((s) => (
            <div
              key={s}
              className={`h-2 w-12 rounded-full transition-colors ${
                s <= step ? "bg-primary" : "bg-muted"
              }`}
            />
          ))}
        </div>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          {getFieldError("general") && (
            <div className="flex items-center gap-2 rounded-lg bg-destructive/15 p-3 text-sm text-destructive">
              <AlertCircle className="h-4 w-4 shrink-0" />
              <span>{getFieldError("general")}</span>
            </div>
          )}

          {step === 1 && (
            <>
              <div className="space-y-2">
                <Label htmlFor="email">البريد الإلكتروني</Label>
                <Input
                  id="email"
                  type="email"
                  placeholder="name@example.com"
                  value={email}
                  onChange={(e) => { setEmail(e.target.value); clearFieldError("email"); }}
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
                  onChange={(e) => { setPassword(e.target.value); clearFieldError("password"); }}
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
                  onChange={(e) => { setPasswordConfirmation(e.target.value); clearFieldError("password_confirmation"); }}
                  required
                />
                {getFieldError("password_confirmation") && (
                  <p className="text-sm text-destructive">{getFieldError("password_confirmation")}</p>
                )}
              </div>
              <Button type="button" className="w-full" onClick={handleNext}>
                التالي
              </Button>
            </>
          )}

          {step === 2 && (
            <>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="first_name">الاسم الأول *</Label>
                  <Input
                    id="first_name"
                    type="text"
                    placeholder="محمد"
                    value={firstName}
                    onChange={(e) => { setFirstName(e.target.value); clearFieldError("first_name"); }}
                    required
                  />
                  {getFieldError("first_name") && (
                    <p className="text-sm text-destructive">{getFieldError("first_name")}</p>
                  )}
                </div>
                <div className="space-y-2">
                  <Label htmlFor="second_name">الاسم الثاني</Label>
                  <Input
                    id="second_name"
                    type="text"
                    placeholder="أحمد"
                    value={secondName}
                    onChange={(e) => setSecondName(e.target.value)}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="third_name">الاسم الثالث</Label>
                  <Input
                    id="third_name"
                    type="text"
                    placeholder="علي"
                    value={thirdName}
                    onChange={(e) => setThirdName(e.target.value)}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="last_name">الاسم الأخير *</Label>
                  <Input
                    id="last_name"
                    type="text"
                    placeholder="الحسن"
                    value={lastName}
                    onChange={(e) => { setLastName(e.target.value); clearFieldError("last_name"); }}
                    required
                  />
                  {getFieldError("last_name") && (
                    <p className="text-sm text-destructive">{getFieldError("last_name")}</p>
                  )}
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="gender">الجنس</Label>
                  <Select value={gender} onValueChange={setGender}>
                    <SelectTrigger id="gender">
                      <SelectValue placeholder="اختر" />
                    </SelectTrigger>
                    <SelectContent>
                      {GENDER_OPTIONS.map((opt) => (
                        <SelectItem key={opt.value} value={opt.value}>
                          {opt.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="birth_date">تاريخ الميلاد</Label>
                  <Input
                    id="birth_date"
                    type="date"
                    value={birthDate}
                    onChange={(e) => setBirthDate(e.target.value)}
                  />
                </div>
              </div>
              <div className="space-y-2">
                <Label htmlFor="phone">رقم الهاتف</Label>
                <Input
                  id="phone"
                  type="tel"
                  placeholder="+966501234567"
                  value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                />
              </div>
              <div className="flex gap-3">
                <Button type="button" variant="outline" className="flex-1" onClick={handleBack}>
                  السابق
                </Button>
                <Button type="button" className="flex-1" onClick={handleNext}>
                  التالي
                </Button>
              </div>
            </>
          )}

          {step === 3 && (
            <>
              <div className="space-y-2">
                <Label htmlFor="father_phone">هاتف الأب</Label>
                <Input
                  id="father_phone"
                  type="tel"
                  placeholder="+966501234567"
                  value={fatherPhone}
                  onChange={(e) => setFatherPhone(e.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="mother_phone">هاتف الأم</Label>
                <Input
                  id="mother_phone"
                  type="tel"
                  placeholder="+966501234567"
                  value={motherPhone}
                  onChange={(e) => setMotherPhone(e.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="guardian_job">وظيفة ولي الأمر</Label>
                <Input
                  id="guardian_job"
                  type="text"
                  placeholder="مهندس"
                  value={guardianJob}
                  onChange={(e) => setGuardianJob(e.target.value)}
                />
              </div>
              <div className="flex gap-3">
                <Button type="button" variant="outline" className="flex-1" onClick={handleBack}>
                  السابق
                </Button>
                <Button type="submit" className="flex-1" disabled={loading}>
                  {loading && <Loader2 className="ml-2 h-4 w-4 animate-spin" />}
                  إنشاء حساب
                </Button>
              </div>
            </>
          )}

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
