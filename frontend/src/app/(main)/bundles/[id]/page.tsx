"use client";

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import api from "@/services/api.client";
import { useParams, useRouter } from "next/navigation";
import { PageHeader } from "@/components/shared/page-header";
import { PageLoading } from "@/components/shared/loading-spinner";
import { Button } from "@/components/ui/button";
import { ArrowLeft, BookOpen, Layers, Sparkles } from "lucide-react";
import { useCreateOrder } from "@/hooks/useProducts";
import { PaymentModal } from "@/components/shared/payment-modal";
import { useAuth } from "@/providers/auth-provider";
import { toast } from "sonner";

export default function BundlePage() {
  const { id } = useParams<{ id: string }>();
  const router = useRouter();
  const { user } = useAuth();
  const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);

  const { data: response, isLoading } = useQuery({
    queryKey: ["bundles", id],
    queryFn: () => api.get(`/bundles/${id}`).then((res) => res.data),
  });

  const handlePurchase = () => {
    if (!user) {
      toast.error("يرجى تسجيل الدخول أولاً");
      router.push("/login");
      return;
    }
    
    if (!bundle) return;
    
    setIsPaymentModalOpen(true);
  };

  if (isLoading) return <PageLoading />;
  
  const bundle = response?.data;
  if (!bundle) {
    return <div className="p-10 text-center">الباقة غير موجودة</div>;
  }

  return (
    <div className="p-6 lg:p-10 space-y-8 max-w-5xl mx-auto">
      <Button variant="ghost" onClick={() => router.back()} className="gap-2 text-muted-foreground">
        <ArrowLeft className="h-4 w-4" /> العودة
      </Button>

      <div className="glass-card rounded-2xl overflow-hidden border border-white/5 relative">
        <div className="absolute top-0 right-0 bg-primary/20 text-primary text-xs font-bold px-4 py-1.5 rounded-bl-xl border-l border-b border-primary/30">
          باقة توفير 💎
        </div>

        <div className="p-8 md:p-12 flex flex-col md:flex-row items-start md:items-center gap-8 mt-4">
          <div className="h-32 w-32 bg-primary/10 rounded-3xl flex items-center justify-center text-primary shrink-0">
            <Layers className="h-16 w-16" />
          </div>
          
          <div className="flex-1 space-y-4">
            <h1 className="text-3xl md:text-4xl font-black text-foreground">{bundle.name}</h1>
            <p className="text-lg text-muted-foreground leading-relaxed">
              {bundle.description || "لا يوجد وصف إضافي لهذه الباقة."}
            </p>
            
            <div className="pt-4 flex items-center gap-6">
              <div>
                <span className="text-sm text-muted-foreground block mb-1">السعر الإجمالي</span>
                <span className="text-3xl font-black text-primary science-glow-text">
                  {bundle.price} EGP
                </span>
              </div>
              <Button 
                size="lg" 
                onClick={handlePurchase}
                className="bg-primary hover:bg-primary-hover text-primary-foreground font-bold shadow-lg shadow-primary/20 w-full sm:w-auto px-8 gap-2"
              >
                <Sparkles className="h-5 w-5" />
                شراء الباقة الآن
              </Button>
            </div>
          </div>
        </div>
      </div>

      <div className="mt-12 space-y-4">
        <h3 className="text-xl font-bold text-gradient mb-4">المحتويات المشمولة في هذه الباقة:</h3>
        <div className="grid gap-4 sm:grid-cols-2">
          {bundle.products?.map((product: any) => (
            <div key={product.id} className="glass-card p-5 rounded-xl flex items-start gap-4">
              <div className="h-10 w-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary shrink-0 mt-1">
                <BookOpen className="h-5 w-5" />
              </div>
              <div>
                <h4 className="font-bold text-foreground text-lg">{product.name}</h4>
                {product.sellable?.title && (
                  <p className="text-xs text-primary/80 mb-1">{product.sellable.title}</p>
                )}
                <p className="text-sm text-muted-foreground line-clamp-2">
                  {product.description || product.sellable?.description || "لا يوجد وصف متوفر"}
                </p>
              </div>
            </div>
          ))}
          {(!bundle.products || bundle.products.length === 0) && (
             <p className="text-muted-foreground text-sm col-span-2">لا توجد منتجات داخل هذه الباقة حالياً.</p>
          )}
        </div>
      </div>
      
      {bundle && (
        <PaymentModal 
          isOpen={isPaymentModalOpen}
          onClose={() => setIsPaymentModalOpen(false)}
          purchasableId={bundle.id}
          purchasableType="bundle"
          price={bundle.price}
        />
      )}
    </div>
  );
}
