"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { useAuth } from "@/providers/auth-provider";
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
import { AlertCircle, CheckCircle2, Loader2, Calculator } from "lucide-react";
import { miscService, type GovernorateInfo, type GradeLevelInfo } from "@/services/misc.service";
import { Turnstile } from "@marsidev/react-turnstile";
import env from "@/config/env";

interface FieldError {
  field: string;
  message: string;
}

const GENDER_OPTIONS = [
  { value: "male", label: "ذكر" },
  { value: "female", label: "أنثى" },
];

const ACADEMIC_YEARS = [
  { value: "prep_1", label: "الصف الأول الإعدادي" },
  { value: "prep_2", label: "الصف الثاني الإعدادي" },
  { value: "prep_3", label: "الصف الثالث الإعدادي" },
  { value: "sec_1", label: "الصف الأول الثانوي" },
  { value: "sec_2", label: "الصف الثاني الثانوي" },
  { value: "sec_3", label: "الصف الثالث الثانوي" },
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
  const [gender, setGender] = useState<"male" | "female" | "">("");
  const [birthDate, setBirthDate] = useState("");
  const [phone, setPhone] = useState("");

  const [fatherPhone, setFatherPhone] = useState("");
  const [motherPhone, setMotherPhone] = useState("");
  const [guardianJob, setGuardianJob] = useState("");

  const [governorates, setGovernorates] = useState<GovernorateInfo[]>([]);
  const [governorateId, setGovernorateId] = useState("");
  const [academicYear, setAcademicYear] = useState("");

  const [errors, setErrors] = useState<FieldError[]>([]);
  const [loading, setLoading] = useState(false);
  const [turnstileToken, setTurnstileToken] = useState("");

  useEffect(() => {
    const loadData = async () => {
      try {
        const govs = await miscService.getGovernorates();
        setGovernorates(govs);
      } catch (err) {
        console.error("Failed to load registration dropdowns:", err);
      }
    };
    loadData();
  }, []);

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
      if (!secondName) newErrors.push({ field: "second_name", message: "الاسم الثاني مطلوب" });
      if (!thirdName) newErrors.push({ field: "third_name", message: "الاسم الثالث مطلوب" });
      if (!lastName) newErrors.push({ field: "last_name", message: "الاسم الأخير مطلوب" });
      if (!gender) newErrors.push({ field: "gender", message: "الجنس مطلوب" });
      if (!birthDate) newErrors.push({ field: "birth_date", message: "تاريخ الميلاد مطلوب" });
      if (!phone) newErrors.push({ field: "phone", message: "رقم الهاتف مطلوب" });
      if (!governorateId) newErrors.push({ field: "governorate_id", message: "المحافظة مطلوبة" });
      if (!academicYear) newErrors.push({ field: "academic_year", message: "الصف الدراسي مطلوب" });
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
    
    // Step 3 validation before submitting
    const newErrors: FieldError[] = [];
    if (!fatherPhone) newErrors.push({ field: "father_phone", message: "هاتف الأب مطلوب" });
    if (!motherPhone) newErrors.push({ field: "mother_phone", message: "هاتف الأم مطلوب" });
    if (!guardianJob) newErrors.push({ field: "guardian_job", message: "وظيفة ولي الأمر مطلوبة" });
    if (newErrors.length > 0) {
      setErrors(newErrors);
      return;
    }

    setLoading(true);
    try {
      await register({
        email,
        password,
        password_confirmation: passwordConfirmation,
        first_name: firstName,
        second_name: secondName,
        third_name: thirdName,
        last_name: lastName,
        phone: phone.replace(/[^\d+]/g, ""),
        father_phone: fatherPhone.replace(/[^\d+]/g, ""),
        mother_phone: motherPhone.replace(/[^\d+]/g, ""),
        guardian_job: guardianJob,
        gender: gender as "male" | "female",
        birth_date: birthDate,
        governorate_id: governorateId,
        academic_year: academicYear,
        "cf-turnstile-response": turnstileToken,
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
      <div className="w-full max-w-md p-1 rounded-2xl bg-gradient-to-b from-primary/30 to-secondary/10 cosmic-border-glow animate-fade-in">
        <div className="glass-card w-full p-8 rounded-2xl text-center">
          <div className="mb-6 flex justify-center">
            <div className="rounded-full bg-primary/10 p-4 animate-bounce">
              <CheckCircle2 className="h-12 w-12 text-primary science-glow-text" />
            </div>
          </div>
          <h2 className="mb-2 text-2xl font-bold text-gradient">تم التسجيل بنجاح!</h2>
          <p className="mb-6 text-sm text-muted-foreground leading-relaxed">
            تم إنشاء حسابك بنجاح. حسابك حالياً قيد المراجعة والاعتماد من قبل الإدارة.
            <br />
            سيصلك إشعار فور اعتماد الحساب وتفعيله.
          </p>
          <div className="rounded-xl bg-muted/30 border border-border/40 p-5 text-right text-sm text-muted-foreground mb-6">
            <p className="mb-3 font-semibold text-foreground flex items-center gap-2">
              <Calculator className="h-4 w-4 text-primary" />
              <span>خطواتك التالية:</span>
            </p>
            <ol className="space-y-2 list-decimal list-inside pr-1">
              <li>سيتم مراجعة طلبك من قبل المسؤول المختص.</li>
              <li>عند اعتماد حسابك، ستصبح قادراً على تسجيل الدخول بكودك أو هاتفك.</li>
              <li>يمكنك حينها بدء مشاهدة محاضرات الساينس وحل الواجبات.</li>
            </ol>
          </div>
          <Link href="/login">
            <Button className="w-full py-2 bg-gradient-to-r from-primary to-secondary text-primary-foreground font-bold shadow-lg">
              الانتقال لتسجيل الدخول
            </Button>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="w-full max-w-lg p-1 rounded-2xl bg-gradient-to-b from-primary/30 to-secondary/10 cosmic-border-glow animate-fade-in">
      <div className="glass-card w-full p-8 rounded-2xl">
        <div className="text-center mb-6">
          <div className="inline-flex items-center justify-center p-3 rounded-full bg-primary/10 text-primary mb-3">
            <Calculator className="h-8 w-8 text-primary science-glow-text animate-pulse" />
          </div>
          <h2 className="text-2xl font-bold tracking-tight text-gradient mb-1">
            إنشاء حساب طالب جديد
          </h2>
          <p className="text-xs text-muted-foreground">
            {step === 1 && "الخطوة الأولى: بيانات تسجيل الدخول"}
            {step === 2 && "الخطوة الثانية: المعلومات الشخصية"}
            {step === 3 && "الخطوة الثالثة: بيانات ولي الأمر والاتصال"}
          </p>

          {/* Stepper Indicators */}
          <div className="mt-6 flex items-center justify-center gap-3">
            {[1, 2, 3].map((s) => (
              <div key={s} className="flex items-center">
                <div
                  className={`h-7 w-7 rounded-full flex items-center justify-center text-xs font-bold transition-all ${
                    s === step
                      ? "bg-primary text-primary-foreground shadow-lg cosmic-border-glow"
                      : s < step
                      ? "bg-secondary text-secondary-foreground"
                      : "bg-muted text-muted-foreground border border-border/60"
                  }`}
                >
                  {s}
                </div>
                {s < 3 && (
                  <div
                    className={`h-[2px] w-8 mx-1 transition-all ${
                      s < step ? "bg-secondary" : "bg-muted"
                    }`}
                  />
                )}
              </div>
            ))}
          </div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          {getFieldError("general") && (
            <div className="flex items-center gap-2 rounded-lg bg-destructive/10 border border-destructive/20 p-3 text-sm text-destructive">
              <AlertCircle className="h-4 w-4 shrink-0" />
              <span>{getFieldError("general")}</span>
            </div>
          )}

          {step === 1 && (
            <div className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="email">البريد الإلكتروني</Label>
                <Input
                  id="email"
                  type="email"
                  placeholder="student@example.com"
                  value={email}
                  onChange={(e) => { setEmail(e.target.value); clearFieldError("email"); }}
                  required
                  className="bg-background/50 border-border/60 text-foreground"
                />
                {getFieldError("email") && (
                  <p className="text-xs text-destructive">{getFieldError("email")}</p>
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
                  className="bg-background/50 border-border/60 text-foreground"
                />
                {getFieldError("password") && (
                  <p className="text-xs text-destructive">{getFieldError("password")}</p>
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
                  className="bg-background/50 border-border/60 text-foreground"
                />
                {getFieldError("password_confirmation") && (
                  <p className="text-xs text-destructive">{getFieldError("password_confirmation")}</p>
                )}
              </div>
              <Button type="button" className="w-full mt-2 bg-primary text-primary-foreground font-semibold" onClick={handleNext}>
                التالي
              </Button>
            </div>
          )}

          {step === 2 && (
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-1">
                  <Label htmlFor="first_name" className="text-xs">الاسم الأول *</Label>
                  <Input
                    id="first_name"
                    type="text"
                    placeholder="محمد"
                    value={firstName}
                    onChange={(e) => { setFirstName(e.target.value); clearFieldError("first_name"); }}
                    required
                    className="bg-background/50 border-border/60 text-foreground text-sm"
                  />
                  {getFieldError("first_name") && (
                    <p className="text-[10px] text-destructive">{getFieldError("first_name")}</p>
                  )}
                </div>
                <div className="space-y-1">
                  <Label htmlFor="second_name" className="text-xs">الاسم الثاني *</Label>
                  <Input
                    id="second_name"
                    type="text"
                    placeholder="أحمد"
                    value={secondName}
                    onChange={(e) => { setSecondName(e.target.value); clearFieldError("second_name"); }}
                    required
                    className="bg-background/50 border-border/60 text-foreground text-sm"
                  />
                  {getFieldError("second_name") && (
                    <p className="text-[10px] text-destructive">{getFieldError("second_name")}</p>
                  )}
                </div>
                <div className="space-y-1">
                  <Label htmlFor="third_name" className="text-xs">الاسم الثالث *</Label>
                  <Input
                    id="third_name"
                    type="text"
                    placeholder="علي"
                    value={thirdName}
                    onChange={(e) => { setThirdName(e.target.value); clearFieldError("third_name"); }}
                    required
                    className="bg-background/50 border-border/60 text-foreground text-sm"
                  />
                  {getFieldError("third_name") && (
                    <p className="text-[10px] text-destructive">{getFieldError("third_name")}</p>
                  )}
                </div>
                <div className="space-y-1">
                  <Label htmlFor="last_name" className="text-xs">العائلة / اللقب *</Label>
                  <Input
                    id="last_name"
                    type="text"
                    placeholder="الحسن"
                    value={lastName}
                    onChange={(e) => { setLastName(e.target.value); clearFieldError("last_name"); }}
                    required
                    className="bg-background/50 border-border/60 text-foreground text-sm"
                  />
                  {getFieldError("last_name") && (
                    <p className="text-[10px] text-destructive">{getFieldError("last_name")}</p>
                  )}
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="gender" className="text-xs">الجنس *</Label>
                  <Select value={gender} onValueChange={(val) => { setGender(val as "male" | "female"); clearFieldError("gender"); }}>
                    <SelectTrigger id="gender" className="bg-background/50 border-border/60 text-foreground text-sm">
                      <SelectValue placeholder="اختر" />
                    </SelectTrigger>
                    <SelectContent className="bg-popover border-border/60 text-foreground">
                      {GENDER_OPTIONS.map((opt) => (
                        <SelectItem key={opt.value} value={opt.value}>
                          {opt.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {getFieldError("gender") && (
                    <p className="text-xs text-destructive">{getFieldError("gender")}</p>
                  )}
                </div>
                <div className="space-y-2">
                  <Label htmlFor="birth_date" className="text-xs">تاريخ الميلاد *</Label>
                  <Input
                    id="birth_date"
                    type="date"
                    value={birthDate}
                    onChange={(e) => { setBirthDate(e.target.value); clearFieldError("birth_date"); }}
                    required
                    className="bg-background/50 border-border/60 text-foreground text-sm"
                  />
                  {getFieldError("birth_date") && (
                    <p className="text-xs text-destructive">{getFieldError("birth_date")}</p>
                  )}
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="governorate_id" className="text-xs">المحافظة *</Label>
                  <Select value={governorateId} onValueChange={(val) => { setGovernorateId(val); clearFieldError("governorate_id"); }}>
                    <SelectTrigger id="governorate_id" className="bg-background/50 border-border/60 text-foreground text-sm">
                      <SelectValue placeholder="اختر المحافظة" />
                    </SelectTrigger>
                    <SelectContent className="bg-popover border-border/60 text-foreground max-h-[200px] overflow-y-auto">
                      {governorates.map((gov) => (
                        <SelectItem key={gov.id} value={gov.id}>
                          {gov.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {getFieldError("governorate_id") && (
                    <p className="text-xs text-destructive">{getFieldError("governorate_id")}</p>
                  )}
                </div>
                <div className="space-y-2">
                  <Label htmlFor="academic_year" className="text-xs">الصف الدراسي *</Label>
                  <Select value={academicYear} onValueChange={(val) => { setAcademicYear(val); clearFieldError("academic_year"); }}>
                    <SelectTrigger id="academic_year" className="bg-background/50 border-border/60 text-foreground text-sm">
                      <SelectValue placeholder="اختر الصف الدراسي" />
                    </SelectTrigger>
                    <SelectContent className="bg-popover border-border/60 text-foreground">
                      {ACADEMIC_YEARS.map((grade) => (
                        <SelectItem key={grade.value} value={grade.value}>
                          {grade.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {getFieldError("academic_year") && (
                    <p className="text-xs text-destructive">{getFieldError("academic_year")}</p>
                  )}
                </div>
              </div>
              <div className="space-y-2">
                <Label htmlFor="phone">رقم هاتف الطالب *</Label>
                <Input
                  id="phone"
                  type="tel"
                  placeholder="مثال: 01000000000"
                  value={phone}
                  onChange={(e) => { setPhone(e.target.value); clearFieldError("phone"); }}
                  required
                  className="bg-background/50 border-border/60 text-foreground"
                />
                {getFieldError("phone") && (
                  <p className="text-xs text-destructive">{getFieldError("phone")}</p>
                )}
              </div>
              <div className="flex gap-3 pt-2">
                <Button type="button" variant="outline" className="flex-1 border-border/60 hover:bg-muted text-foreground" onClick={handleBack}>
                  السابق
                </Button>
                <Button type="button" className="flex-1 bg-primary text-primary-foreground font-semibold" onClick={handleNext}>
                  التالي
                </Button>
              </div>
            </div>
          )}

          {step === 3 && (
            <div className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="father_phone">رقم هاتف الأب *</Label>
                <Input
                  id="father_phone"
                  type="tel"
                  placeholder="مثال: 01100000000"
                  value={fatherPhone}
                  onChange={(e) => { setFatherPhone(e.target.value); clearFieldError("father_phone"); }}
                  required
                  className="bg-background/50 border-border/60 text-foreground"
                />
                {getFieldError("father_phone") && (
                  <p className="text-xs text-destructive">{getFieldError("father_phone")}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="mother_phone">رقم هاتف الأم *</Label>
                <Input
                  id="mother_phone"
                  type="tel"
                  placeholder="مثال: 01200000000"
                  value={motherPhone}
                  onChange={(e) => { setMotherPhone(e.target.value); clearFieldError("mother_phone"); }}
                  required
                  className="bg-background/50 border-border/60 text-foreground"
                />
                {getFieldError("mother_phone") && (
                  <p className="text-xs text-destructive">{getFieldError("mother_phone")}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="guardian_job">وظيفة ولي الأمر *</Label>
                <Input
                  id="guardian_job"
                  type="text"
                  placeholder="مثال: مهندس أو محاسب"
                  value={guardianJob}
                  onChange={(e) => { setGuardianJob(e.target.value); clearFieldError("guardian_job"); }}
                  required
                  className="bg-background/50 border-border/60 text-foreground"
                />
                {getFieldError("guardian_job") && (
                  <p className="text-xs text-destructive">{getFieldError("guardian_job")}</p>
                )}
              </div>
              
              <div className="flex justify-center my-4">
                <Turnstile
                  siteKey={env.NEXT_PUBLIC_TURNSTILE_SITE_KEY}
                  onSuccess={(token) => setTurnstileToken(token)}
                  onError={() => setErrors([{ field: "general", message: "حدث خطأ في التحقق. يرجى إعادة تحميل الصفحة." }])}
                  options={{ theme: "dark" }}
                />
              </div>

              <div className="flex gap-3 pt-2">
                <Button type="button" variant="outline" className="flex-1 border-border/60 hover:bg-muted text-foreground" onClick={handleBack}>
                  السابق
                </Button>
                <Button type="submit" className="flex-1 bg-gradient-to-r from-primary to-secondary text-primary-foreground font-bold shadow-lg" disabled={loading}>
                  {loading && <Loader2 className="ml-2 h-4 w-4 animate-spin" />}
                  تسجيل في المنصة 🔬
                </Button>
              </div>
            </div>
          )}

          <p className="text-center text-sm text-muted-foreground pt-3 border-t border-border/20">
            لديك حساب بالفعل؟{" "}
            <Link href="/login" className="font-semibold text-primary hover:underline hover:text-primary-fixed transition-colors">
              تسجيل الدخول
            </Link>
          </p>
        </form>
      </div>
    </div>
  );
}
