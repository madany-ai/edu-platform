"use client";

import { useState } from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { useAuth } from "@/providers/auth-provider";
import { PageLoading } from "@/components/shared/loading-spinner";
import { ErrorState } from "@/components/shared/error-state";
import { useLecture } from "@/hooks/useCourses";
import { useMyEntitlements } from "@/hooks/useEnrollment";
import { useProducts, useCreateOrder } from "@/hooks/useProducts";
import { toast } from "sonner";
import {
  PlayCircle,
  ArrowLeft,
  Lock,
  FileText,
  Clock,
  Sparkles,
  Award,
  BookOpen,
  ShoppingBag,
} from "lucide-react";

export default function StandaloneLecturePage() {
  const { id } = useParams<{ id: string }>();
  const router = useRouter();
  const { user } = useAuth();

  const { data: lecture, isLoading, error } = useLecture(id);
  const { data: entitlements } = useMyEntitlements();
  const { data: products } = useProducts("lecture");
  const createOrderMutation = useCreateOrder();

  if (isLoading) return <PageLoading />;
  if (error || !lecture) return <ErrorState title="خطأ في التحميل" description="لم نتمكن من العثور على المحاضرة المحددة" />;

  const isUnlocked =
    lecture.has_access ||
    entitlements?.some((e: any) => e.lecture_id === lecture.id) ||
    false;

  // Find product for this lecture
  const lectureProduct = products?.find(
    (p: any) => p.sellable_id === lecture.id && p.sellable_type === "App\\Models\\Lecture"
  );

  const course = lecture.section?.course;

  const handlePurchaseLecture = () => {
    if (!user) {
      toast.error("يرجى تسجيل الدخول أولاً لتنفيذ عملية الشراء.");
      router.push("/login");
      return;
    }
    if (!lectureProduct) {
      toast.error("لا يوجد منتج متاح لشراء هذه المحاضرة حالياً. يرجى التواصل مع الإدارة.");
      return;
    }

    createOrderMutation.mutate(
      { purchasable_id: lectureProduct.id, purchasable_type: "product" },
      {
        onSuccess: () => {
          toast.success("تم إرسال طلب الشراء بنجاح. سيتم تفعيل المحاضرة لك فور تأكيد الطلب.");
        },
        onError: (err: any) => {
          toast.error(err.response?.data?.message || "فشلت عملية الشراء. يرجى المحاولة لاحقاً.");
        },
      }
    );
  };

  return (
    <div className="min-h-screen pb-16 pt-6">
      <div className="max-w-6xl mx-auto px-4 space-y-8">
        {/* Navigation & Header */}
        <div className="flex items-center justify-between">
          <Button
            variant="ghost"
            onClick={() => router.back()}
            className="gap-2 text-muted-foreground hover:text-foreground"
          >
            <ArrowLeft className="h-4 w-4" /> العودة
          </Button>

          <Badge variant="outline" className="px-3 py-1 bg-primary/10 text-primary border-primary/20">
            محاضرة منفردة 📌
          </Badge>
        </div>

        {/* Video / Preview Header */}
        <div className="glass-card rounded-2xl overflow-hidden border border-white/10 relative shadow-2xl">
          {isUnlocked ? (
            <div className="aspect-video w-full bg-black flex items-center justify-center relative">
              {lecture.video?.stream_url ? (
                <iframe
                  src={
                    lecture.video.stream_url.includes("youtube.com") || lecture.video.stream_url.includes("youtu.be")
                      ? lecture.video.stream_url.replace("watch?v=", "embed/")
                      : lecture.video.stream_url
                  }
                  className="w-full h-full border-0"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                  allowFullScreen
                />
              ) : (
                <div className="text-center p-8 text-muted-foreground">
                  <PlayCircle className="h-16 w-16 mx-auto mb-3 text-primary animate-pulse" />
                  <p>الفيديو غير جاهز للمشاهدة حالياً</p>
                </div>
              )}
            </div>
          ) : (
            <div className="aspect-video w-full bg-gradient-to-br from-slate-900 via-slate-950 to-black flex flex-col items-center justify-center p-6 text-center relative">
              <div className="h-20 w-20 rounded-full bg-primary/10 border border-primary/30 flex items-center justify-center text-primary mb-4 shadow-lg shadow-primary/20">
                <Lock className="h-10 w-10" />
              </div>
              <h2 className="text-2xl md:text-3xl font-black text-white mb-2">{lecture.title}</h2>
              <p className="text-slate-400 text-sm max-w-md mb-6">
                هذه المحاضرة محميّة. يرجى الشراء للحصول على إمكانية الوصول الكامل لمشاهدة الفيديو وتحميل الملفات.
              </p>
              {lectureProduct && (
                <Button
                  size="lg"
                  onClick={handlePurchaseLecture}
                  disabled={createOrderMutation.isPending}
                  className="bg-primary hover:bg-primary-hover text-primary-foreground font-bold px-8 py-6 rounded-xl shadow-xl shadow-primary/25 gap-3"
                >
                  <ShoppingBag className="h-5 w-5" />
                  {createOrderMutation.isPending
                    ? "جاري إرسال الطلب..."
                    : `شراء المحاضرة بـ ${lectureProduct.price} EGP`}
                </Button>
              )}
            </div>
          )}
        </div>

        {/* Details & Monetization Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Info (2 cols) */}
          <div className="lg:col-span-2 space-y-6">
            <div className="glass-card p-6 md:p-8 rounded-2xl border border-white/5 space-y-4">
              <h1 className="text-2xl md:text-3xl font-black text-foreground">{lecture.title}</h1>

              <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                {lecture.duration > 0 && (
                  <span className="flex items-center gap-1.5 bg-secondary/50 px-3 py-1 rounded-lg">
                    <Clock className="h-4 w-4 text-primary" /> {lecture.duration} دقيقة
                  </span>
                )}
                {lecture.instructor && (
                  <span className="flex items-center gap-1.5 bg-secondary/50 px-3 py-1 rounded-lg">
                    <BookOpen className="h-4 w-4 text-primary" /> د. {lecture.instructor.name}
                  </span>
                )}
              </div>

              <Separator className="my-4 bg-white/10" />

              <div>
                <h3 className="font-bold text-foreground mb-2">وصف المحاضرة:</h3>
                <p className="text-muted-foreground leading-relaxed text-sm md:text-base">
                  {lecture.description || "لا يوجد وصف إضافي لهذه المحاضرة حالياً."}
                </p>
              </div>
            </div>

            {/* Attachments & Files */}
            {lecture.files && lecture.files.length > 0 && (
              <div className="glass-card p-6 rounded-2xl border border-white/5 space-y-4">
                <h3 className="font-bold text-foreground text-lg flex items-center gap-2">
                  <FileText className="h-5 w-5 text-primary" /> المرفقات والملفات:
                </h3>
                <div className="space-y-2">
                  {lecture.files.map((file) => (
                    <div
                      key={file.id}
                      className="flex items-center justify-between p-3.5 rounded-xl bg-secondary/30 border border-white/5"
                    >
                      <span className="text-sm font-medium text-foreground">
                        {file.original_name || `ملف مرفق ${file.id.substring(0, 6)}`}
                      </span>
                      {isUnlocked ? (
                        <a
                          href={`/api/lectures/${lecture.id}/files/${file.id}`}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="text-xs bg-primary/20 text-primary hover:bg-primary/30 px-3 py-1.5 rounded-lg font-bold transition-all"
                        >
                          تحميل PDF 📥
                        </a>
                      ) : (
                        <Badge variant="outline" className="text-xs text-muted-foreground gap-1">
                          <Lock className="h-3 w-3" /> مقفل
                        </Badge>
                      )}
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Upsell Banner (If part of a course) */}
            {course && (
              <div className="p-6 rounded-2xl bg-gradient-to-r from-primary/20 via-purple-950/40 to-slate-900 border border-primary/30 relative overflow-hidden shadow-xl space-y-4">
                <div className="flex items-start gap-4">
                  <div className="h-12 w-12 rounded-2xl bg-primary/20 flex items-center justify-center text-primary shrink-0 mt-1">
                    <Sparkles className="h-6 w-6" />
                  </div>
                  <div className="flex-1 space-y-1">
                    <h3 className="font-black text-white text-lg">
                      هذه المحاضرة متوفرة أيضاً داخل كورس كامل! 🎓
                    </h3>
                    <p className="text-slate-300 text-sm leading-relaxed">
                      اشترك في كورس <span className="font-bold text-primary">{course.title}</span> واحصل على جميع المحاضرات والملفات والشهور بخصم مجمع!
                    </p>
                  </div>
                </div>
                <div className="flex justify-end pt-2">
                  <Link href={`/courses/${course.id}`}>
                    <Button variant="default" className="bg-primary hover:bg-primary-hover text-primary-foreground font-bold rounded-xl gap-2">
                      استعراض الكورس الكامل <ArrowLeft className="h-4 w-4 text-primary-foreground" />
                    </Button>
                  </Link>
                </div>
              </div>
            )}
          </div>

          {/* Sidebar Purchase Card (1 col) */}
          <div className="space-y-6">
            <Card className="glass-card border-white/10 overflow-hidden sticky top-24">
              <CardContent className="p-6 space-y-6">
                <div className="text-center space-y-2">
                  <span className="text-xs text-muted-foreground block font-medium">سعر المحاضرة المنفردة</span>
                  <div className="text-3xl font-black text-primary science-glow-text">
                    {lectureProduct ? `${lectureProduct.price} EGP` : "مجاناً"}
                  </div>
                  {lectureProduct?.access_duration_days && (
                    <p className="text-xs text-amber-400/90 font-medium">
                      صلاحية الوصول: {lectureProduct.access_duration_days} يوم من تاريخ الشراء
                    </p>
                  )}
                </div>

                <Separator className="bg-white/10" />

                <ul className="space-y-3 text-sm text-slate-300">
                  <li className="flex items-center gap-2">
                    <Award className="h-4 w-4 text-primary shrink-0" />
                    <span>مشاهدة بجودة عالية بدون إعلانات</span>
                  </li>
                  <li className="flex items-center gap-2">
                    <FileText className="h-4 w-4 text-primary shrink-0" />
                    <span>تحميل المرفقات ومذكرات الشرح</span>
                  </li>
                  <li className="flex items-center gap-2">
                    <BookOpen className="h-4 w-4 text-primary shrink-0" />
                    <span>وصول كامل للاختبارات التفاعلية</span>
                  </li>
                </ul>

                {!isUnlocked && lectureProduct && (
                  <Button
                    onClick={handlePurchaseLecture}
                    disabled={createOrderMutation.isPending}
                    className="w-full bg-primary hover:bg-primary-hover text-primary-foreground font-bold py-6 rounded-xl shadow-lg shadow-primary/20 gap-2"
                  >
                    <ShoppingBag className="h-5 w-5" />
                    {createOrderMutation.isPending ? "جاري الشراء..." : "طلب الشراء الآن"}
                  </Button>
                )}

                {isUnlocked && (
                  <div className="p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 rounded-xl text-center text-sm font-bold">
                    أنت تمتلك صلاحية الوصول لهذه المحاضرة ✅
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  );
}
