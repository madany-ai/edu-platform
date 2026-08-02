"use client";

import { useQuery } from "@tanstack/react-query";
import api from "@/services/api.client";
import { useParams, useRouter } from "next/navigation";
import { PageHeader } from "@/components/shared/page-header";
import { PageLoading } from "@/components/shared/loading-spinner";
import { Button } from "@/components/ui/button";
import { ArrowLeft, BookOpen, Layers, PlayCircle, Sparkles } from "lucide-react";
import { useCreateOrder } from "@/hooks/useProducts";

export default function ProductPage() {
  const { id } = useParams<{ id: string }>();
  const router = useRouter();
  const createOrderMutation = useCreateOrder();

  const { data: response, isLoading } = useQuery({
    queryKey: ["products", id],
    queryFn: () => api.get(`/products/${id}`).then((res) => res.data),
  });

  const handlePurchase = () => {
    if (!product) return;
    createOrderMutation.mutate(
      {
        purchasable_id: product.id,
        purchasable_type: "product",
      },
      {
        onSuccess: () => {
          router.push("/dashboard/courses");
        },
      }
    );
  };

  if (isLoading) return <PageLoading />;
  
  const product = response?.data;
  if (!product) {
    return <div className="p-10 text-center">المنتج غير موجود</div>;
  }

  const renderContent = () => {
    if (product.sellable_type.includes("CourseSection")) {
      const section = product.sellable;
      return (
        <div className="space-y-4">
          <h3 className="text-xl font-bold text-gradient mb-4">المحاضرات المشمولة:</h3>
          <div className="grid gap-3">
            {section?.lectures?.map((lecture: any) => (
              <div key={lecture.id} className="glass-card p-4 rounded-xl flex items-center gap-4">
                <div className="h-10 w-10 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                  <PlayCircle className="h-5 w-5" />
                </div>
                <div>
                  <h4 className="font-bold text-foreground">{lecture.title}</h4>
                  <p className="text-xs text-muted-foreground">{lecture.description || "لا يوجد وصف"}</p>
                </div>
              </div>
            ))}
            {(!section?.lectures || section.lectures.length === 0) && (
              <p className="text-muted-foreground text-sm">لا يوجد محتوى مضاف حالياً.</p>
            )}
          </div>
        </div>
      );
    }

    if (product.sellable_type.includes("Lecture")) {
      return (
        <div className="space-y-4">
          <h3 className="text-xl font-bold text-gradient mb-4">تفاصيل المحاضرة:</h3>
          <div className="glass-card p-6 rounded-xl">
            <h4 className="font-bold text-lg mb-2">{product.sellable?.title}</h4>
            <p className="text-muted-foreground">{product.sellable?.description || "لا يوجد وصف متاح"}</p>
          </div>
        </div>
      );
    }

    return null;
  };

  return (
    <div className="p-6 lg:p-10 space-y-8 max-w-5xl mx-auto">
      <Button variant="ghost" onClick={() => router.back()} className="gap-2 text-muted-foreground">
        <ArrowLeft className="h-4 w-4" /> العودة
      </Button>

      <div className="glass-card rounded-2xl overflow-hidden border border-white/5">
        <div className="p-8 md:p-12 flex flex-col md:flex-row items-start md:items-center gap-8">
          <div className="h-32 w-32 bg-primary/10 rounded-3xl flex items-center justify-center text-primary shrink-0">
            {product.sellable_type.includes("Lecture") ? (
              <PlayCircle className="h-16 w-16" />
            ) : (
              <Layers className="h-16 w-16" />
            )}
          </div>
          
          <div className="flex-1 space-y-4">
            <h1 className="text-3xl md:text-4xl font-black text-foreground">{product.name}</h1>
            <p className="text-lg text-muted-foreground leading-relaxed">
              {product.description || "لا يوجد وصف إضافي متوفر."}
            </p>
            
            <div className="pt-4 flex items-center gap-6">
              <div>
                <span className="text-sm text-muted-foreground block mb-1">السعر</span>
                <span className="text-3xl font-black text-primary science-glow-text">
                  {product.price === 0 ? "مجاني" : `${product.price} EGP`}
                </span>
              </div>
              <Button
                size="lg"
                onClick={handlePurchase}
                disabled={createOrderMutation.isPending}
                className="bg-primary hover:bg-primary-hover text-primary-foreground font-bold px-8 rounded-xl gap-2 shadow-lg shadow-primary/20"
              >
                <Sparkles className="h-5 w-5" />
                {createOrderMutation.isPending ? "جاري الشراء..." : "شراء الآن"}
              </Button>
            </div>
          </div>
        </div>
      </div>

      <div className="mt-12">
        {renderContent()}
      </div>
    </div>
  );
}
