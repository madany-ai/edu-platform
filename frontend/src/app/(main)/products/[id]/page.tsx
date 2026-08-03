"use client";

import { useQuery } from "@tanstack/react-query";
import api from "@/services/api.client";
import { useParams, useRouter } from "next/navigation";
import { PageHeader } from "@/components/shared/page-header";
import { PageLoading } from "@/components/shared/loading-spinner";
import { ErrorState } from "@/components/shared/error-state";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Separator } from "@/components/ui/separator";
import { ArrowLeft, BookOpen, Layers, PlayCircle, Sparkles, CheckCircle2, Users, Share2, Heart, Clock, GraduationCap, Video } from "lucide-react";
import { useCreateOrder } from "@/hooks/useProducts";
import { toast } from "sonner";
import { useAuth } from "@/providers/auth-provider";

export default function ProductPage() {
  const { id } = useParams<{ id: string }>();
  const router = useRouter();
  const { user } = useAuth();
  const createOrderMutation = useCreateOrder();

  const { data: response, isLoading, error } = useQuery({
    queryKey: ["products", id],
    queryFn: () => api.get(`/products/${id}`).then((res) => res.data),
  });

  const handlePurchase = () => {
    if (!user) {
      toast.error("يرجى تسجيل الدخول أولاً");
      router.push("/login");
      return;
    }
    
    if (!product) return;
    
    createOrderMutation.mutate(
      {
        purchasable_id: product.id,
        purchasable_type: "product",
      },
      {
        onSuccess: () => {
          toast.success("تم إرسال طلب الشراء بنجاح!");
          router.push("/dashboard");
        },
        onError: (err: any) => {
          toast.error(err.response?.data?.message || "فشلت عملية الشراء.");
        }
      }
    );
  };

  if (isLoading) return <PageLoading />;
  
  if (error || !response?.data) {
    return (
      <ErrorState
        title="المنتج غير موجود"
        description="عذراً، المنتج الذي تبحث عنه غير موجود أو لم يعد متاحاً."
      />
    );
  }

  const product = response.data;
  const isLecture = product.sellable_type.includes("Lecture");
  const isCourse = product.sellable_type.includes("Course") && !product.sellable_type.includes("Section");
  
  const sellable = product.sellable;
  const instructorName = sellable?.instructor?.name || "معلم المادة";
  
  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <PageHeader
        title={product.name}
        breadcrumbs={[
          { label: "الرئيسية", href: "/" },
          { label: "المتجر", href: "/courses" },
          { label: product.name },
        ]}
      />

      <div className="grid gap-8 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-8">
          <div>
            <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground mb-6">
              {isLecture ? (
                <>
                  <span className="flex items-center gap-1">
                    <Video className="h-4 w-4" />
                    محاضرة مفردة
                  </span>
                  {sellable?.duration > 0 && (
                    <span className="flex items-center gap-1">
                      <Clock className="h-4 w-4" />
                      {sellable.duration} دقيقة
                    </span>
                  )}
                  {sellable?.course?.title && (
                    <span className="flex items-center gap-1 bg-primary/10 text-primary px-2 py-0.5 rounded-md font-bold">
                      <BookOpen className="h-4 w-4" />
                      من كورس: {sellable.course.title}
                    </span>
                  )}
                </>
              ) : (
                <>
                  <span className="flex items-center gap-1">
                    <Layers className="h-4 w-4" />
                    {sellable?.sections?.length || 0} أقسام
                  </span>
                  <span className="flex items-center gap-1">
                    <PlayCircle className="h-4 w-4" />
                    {sellable?.sections?.reduce((acc: number, sec: any) => acc + (sec.lectures_count || 0), 0) || 0} محاضرات
                  </span>
                </>
              )}
            </div>

            <p className="text-lg text-muted-foreground leading-relaxed">
              {isLecture ? sellable?.description || product.description || "لا يوجد وصف إضافي لهذه المحاضرة." : product.description || "لا يوجد وصف إضافي متوفر."}
            </p>
          </div>

          <Separator />

          {/* Additional Content based on type */}
          {isLecture ? (
            <div>
              <h2 className="text-xl font-bold mb-4">ماذا تتضمن هذه المحاضرة؟</h2>
              <div className="grid sm:grid-cols-2 gap-4">
                <div className="glass-card p-4 rounded-xl flex items-center gap-3">
                  <div className="h-10 w-10 bg-blue-500/10 text-blue-500 rounded-full flex items-center justify-center">
                    <Video className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="font-bold">فيديو مسجل</p>
                    <p className="text-xs text-muted-foreground">شرح تفصيلي ومسجل بجودة عالية</p>
                  </div>
                </div>
                <div className="glass-card p-4 rounded-xl flex items-center gap-3">
                  <div className="h-10 w-10 bg-emerald-500/10 text-emerald-500 rounded-full flex items-center justify-center">
                    <CheckCircle2 className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="font-bold">وصول دائم / محدد</p>
                    <p className="text-xs text-muted-foreground">صلاحية مشاهدة حسب الباقة المشتراة</p>
                  </div>
                </div>
              </div>
            </div>
          ) : (
             <div className="space-y-4">
              <h2 className="text-xl font-bold mb-4">المحتوى المشمول:</h2>
              <div className="grid gap-3">
                {sellable?.sections?.flatMap((s: any) => s.lectures)?.map((lecture: any) => (
                  <div key={lecture.id} className="glass-card p-4 rounded-xl flex items-center gap-4">
                    <div className="h-10 w-10 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                      <PlayCircle className="h-5 w-5" />
                    </div>
                    <div>
                      <h4 className="font-bold text-foreground">{lecture.title}</h4>
                      <p className="text-xs text-muted-foreground">{lecture.duration ? `${lecture.duration} دقيقة` : "فيديو مسجل"}</p>
                    </div>
                  </div>
                ))}
                {(!sellable?.sections || sellable.sections.length === 0) && (
                   <p className="text-muted-foreground text-sm">لا يوجد تفاصيل إضافية للمحتوى.</p>
                )}
              </div>
            </div>
          )}
        </div>

        <div className="lg:col-span-1">
          <div className="sticky top-24 space-y-6">
            <Card>
              <div className="aspect-video bg-gradient-to-br from-primary/20 via-slate-900 to-primary/5 flex items-center justify-center rounded-t-lg relative">
                {isLecture ? (
                  <PlayCircle className="h-16 w-16 text-primary/80 science-glow-text" />
                ) : (
                  <Layers className="h-16 w-16 text-primary/80 science-glow-text" />
                )}
                {isLecture && (
                  <span className="absolute bottom-3 right-3 text-[10px] font-bold px-2 py-0.5 rounded-full bg-primary/20 text-primary border border-primary/30 backdrop-blur-sm">
                    محاضرة مفردة
                  </span>
                )}
              </div>
              <CardContent className="p-6 space-y-4">
                <div className="flex items-end gap-2">
                  <span className="text-3xl font-bold text-primary">
                    {product.price === 0 ? "مجاني" : `${product.price} ج.م`}
                  </span>
                </div>

                <Button
                  className="w-full gap-2 bg-primary hover:bg-primary-hover text-primary-foreground font-bold shadow-md shadow-primary/20"
                  size="lg"
                  onClick={handlePurchase}
                  disabled={createOrderMutation.isPending}
                >
                  <Sparkles className="h-4 w-4" />
                  {createOrderMutation.isPending ? "جاري المعالجة..." : "شراء الآن"}
                </Button>

                <Separator />

                <div className="space-y-3 text-sm">
                  {product.access_duration_days ? (
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">صلاحية الوصول</span>
                      <span className="font-medium">{product.access_duration_days} يوماً</span>
                    </div>
                  ) : (
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">صلاحية الوصول</span>
                      <span className="font-medium text-emerald-500">مدى الحياة</span>
                    </div>
                  )}
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">النوع</span>
                    <span className="font-medium">{isLecture ? "محاضرة" : "كورس / قسم"}</span>
                  </div>
                </div>

                <div className="flex gap-2 pt-2">
                  <Button variant="outline" size="sm" className="flex-1 gap-2 border-white/10 hover:bg-white/5">
                    <Share2 className="h-4 w-4" />
                    مشاركة
                  </Button>
                  <Button variant="outline" size="sm" className="flex-1 gap-2 border-white/10 hover:bg-white/5">
                    <Heart className="h-4 w-4" />
                    حفظ
                  </Button>
                </div>
              </CardContent>
            </Card>

            {(isLecture || isCourse) && instructorName && (
              <Card>
                <CardContent className="p-6">
                  <div className="flex items-center gap-4">
                    <Avatar className="h-12 w-12">
                      <AvatarFallback className="bg-primary/10 text-primary font-bold text-lg">
                        {instructorName.charAt(0)}
                      </AvatarFallback>
                    </Avatar>
                    <div>
                      <p className="font-bold text-foreground">{instructorName}</p>
                      <p className="text-xs text-muted-foreground flex items-center gap-1 mt-1">
                        <GraduationCap className="h-3 w-3" />
                        مدرب معتمد
                      </p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
