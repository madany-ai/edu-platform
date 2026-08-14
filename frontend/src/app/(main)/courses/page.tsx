"use client";

import { Suspense, useState } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import Link from "next/link";
import { useCourses } from "@/hooks/useCourses";
import { useProducts, useBundles } from "@/hooks/useProducts";
import { CourseCard } from "@/components/course-card";
import { PaymentModal } from "@/components/shared/payment-modal";
import { SearchInput } from "@/components/shared/search-input";
import { CourseCardSkeleton } from "@/components/shared/page-skeleton";
import { EmptyState } from "@/components/shared/empty-state";
import { PageHeader } from "@/components/shared/page-header";
import { useDebounce } from "@/hooks/use-debounce";
import { useAuth } from "@/providers/auth-provider";
import { cn } from "@/lib/utils";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import {
  PlayCircle,
  Layers,
  Sparkles,
} from "lucide-react";

function CoursesContent() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const { user } = useAuth();
  const [search, setSearch] = useState(searchParams.get("search") || "");
  const debouncedSearch = useDebounce(search);
  
  const [activeTab, setActiveTab] = useState<"courses" | "lectures" | "bundles">("courses");

  // Fetching data
  const { data: coursesData, isLoading: coursesLoading } = useCourses(
    debouncedSearch ? { search: debouncedSearch } : undefined
  );
  const { data: lecturesData, isLoading: lecturesLoading } = useProducts("lecture");
  const { data: bundlesData, isLoading: bundlesLoading } = useBundles();

  const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);
  const [purchaseTarget, setPurchaseTarget] = useState<{ id: string; type: 'product' | 'bundle'; price: number; name: string } | null>(null);

  const courses = coursesData?.data ?? [];
  const lectures = lecturesData ?? [];
  const bundles = bundlesData ?? [];

  const handlePurchase = (id: string, type: 'product' | 'bundle', price: number, name: string) => {
    if (!user) {
      toast.error("يرجى تسجيل الدخول أولاً لإتمام عملية الشراء");
      router.push("/login");
      return;
    }

    setPurchaseTarget({ id, type, price, name });
    setIsPaymentModalOpen(true);
  };

  const renderCoursesTab = () => {
    if (coursesLoading) {
      return (
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {Array.from({ length: 8 }).map((_, i) => (
            <CourseCardSkeleton key={i} />
          ))}
        </div>
      );
    }

    if (courses.length === 0) {
      return (
        <EmptyState
          title="لا توجد دورات مطابقة للبحث"
          description="جرب تغيير كلمات البحث أو تصفح جميع الدورات"
        />
      );
    }

    return (
      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        {courses.map((course) => (
          <CourseCard
            key={course.id}
            id={course.id}
            title={course.title}
            instructor={course.instructor?.name ?? ""}
            category=""
            lessons={course.sections_count ?? 0}
            students={course.students_count ?? 0}
            price={course.price}
            image={course.thumbnail || undefined}
          />
        ))}
      </div>
    );
  };

  const renderProductsList = (items: any[], typeLabel: string, typeIcon: any) => {
    const Icon = typeIcon;
    const filteredItems = items.filter(item => 
      item.name.toLowerCase().includes(debouncedSearch.toLowerCase())
    );

    if (filteredItems.length === 0) {
      return (
        <EmptyState
          title={`لا توجد ${typeLabel} متاحة`}
          description="يرجى مراجعة القسم لاحقاً لمعرفة المجموعات والمواد المتاحة."
        />
      );
    }

    return (
      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {filteredItems.map((item) => (
          <div
            key={item.id}
            className="glass-card rounded-2xl border border-[#3b413c] p-6 flex flex-col justify-between hover:translate-y-[-4px] transition-all"
          >
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <div className="h-10 w-10 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary">
                  <Icon className="h-5 w-5" />
                </div>
                <div>
                  <h3 className="font-bold text-foreground text-base line-clamp-1">{item.name}</h3>
                  <span className="text-[10px] text-muted-foreground">
                    {item.access_duration_days 
                      ? `مدة الوصول: ${item.access_duration_days} يوم` 
                      : "وصول دائم مدى الحياة"}
                  </span>
                </div>
              </div>
              
              {item.sellable?.description && (
                <p className="text-xs text-muted-foreground leading-relaxed line-clamp-3">
                  {item.sellable.description}
                </p>
              )}
            </div>

              <div className="border-t border-[#3b413c] pt-4 mt-6 flex items-center justify-between">
              <div>
                <span className="text-xs text-muted-foreground block">السعر</span>
                <span className="text-lg font-black text-primary science-glow-text">
                  {item.price === 0 ? "مجاني" : `${item.price} EGP`}
                </span>
              </div>
              
              <div className="flex gap-2">
                <Link href={`/products/${item.id}`}>
                  <Button
                    size="sm"
                    variant="outline"
                    className="border-primary/20 text-foreground font-bold rounded-xl"
                  >
                    عرض التفاصيل
                  </Button>
                </Link>
                <Button
                  onClick={() => handlePurchase(item.id, 'product', item.price, item.name)}
                  size="sm"
                  className="bg-primary hover:bg-primary-hover text-primary-foreground font-bold rounded-xl gap-1.5 hidden sm:flex"
                >
                  <Sparkles className="h-4 w-4" />
                  شراء
                </Button>
              </div>
            </div>
          </div>
        ))}
      </div>
    );
  };

  const renderBundlesTab = () => {
    const filteredBundles = bundles.filter(b => 
      b.name.toLowerCase().includes(debouncedSearch.toLowerCase())
    );

    if (filteredBundles.length === 0) {
      return (
        <EmptyState
          title="لا توجد باقات عروض حالية"
          description="تابعنا لاحقاً لمعرفة العروض الأكاديمية والاشتراكات المجمعة."
        />
      );
    }

    return (
      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {filteredBundles.map((bundle) => (
          <div
            key={bundle.id}
            className="glass-card rounded-2xl border border-[#3b413c] p-6 flex flex-col justify-between hover:translate-y-[-4px] transition-all relative overflow-hidden"
          >
            <div className="absolute top-0 right-0 bg-primary/20 text-primary text-[10px] font-bold px-3 py-1 rounded-bl-xl border-l border-b border-primary/30">
              باقة توفير 💎
            </div>
            
            <div className="space-y-4 mt-2">
              <div className="flex items-center gap-3">
                <div className="h-10 w-10 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary">
                  <Layers className="h-5 w-5" />
                </div>
                <div>
                  <h3 className="font-bold text-foreground text-base line-clamp-1 pr-12">{bundle.name}</h3>
                  <span className="text-[10px] text-muted-foreground">
                    تشمل {bundle.products?.length ?? 0} منتجات دراسية
                  </span>
                </div>
              </div>
              
              <div className="space-y-2">
                <p className="text-[10px] font-semibold text-muted-foreground">المحتويات المشمولة:</p>
                <ul className="text-xs text-muted-foreground space-y-1.5 list-disc list-inside">
                  {bundle.products?.slice(0, 3).map((p: any) => (
                    <li key={p.id} className="truncate">{p.name}</li>
                  ))}
                  {(bundle.products?.length ?? 0) > 3 && (
                    <li className="list-none text-[10px] text-primary">وغيرها من الدروس والمراجعات...</li>
                  )}
                </ul>
              </div>
            </div>

            <div className="border-t border-[#3b413c] pt-4 mt-6 flex items-center justify-between">
              <div>
                <span className="text-xs text-muted-foreground block">السعر الإجمالي للباقة</span>
                <span className="text-lg font-black text-primary science-glow-text">
                  {bundle.price} EGP
                </span>
              </div>
              
              <div className="flex gap-2">
                <Link href={`/bundles/${bundle.id}`}>
                  <Button
                    size="sm"
                    variant="outline"
                    className="border-primary/20 text-foreground font-bold rounded-xl"
                  >
                    عرض التفاصيل
                  </Button>
                </Link>
                <Button
                  onClick={() => handlePurchase(bundle.id, 'bundle', bundle.price, bundle.name)}
                  size="sm"
                  className="bg-primary hover:bg-primary-hover text-primary-foreground font-bold rounded-xl gap-1.5 hidden sm:flex"
                >
                  <Sparkles className="h-4 w-4" />
                  شراء الباقة
                </Button>
              </div>
            </div>
          </div>
        ))}
      </div>
    );
  };

  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <PageHeader
        title="المتجر الأكاديمي"
        description="تصفح واشترك في الدورات الكاملة، المحاضرات الفردية، أو باقات العروض مباشرة."
      />

      <div className="mb-8 flex flex-col md:flex-row gap-4 items-stretch md:items-center justify-between">
        <SearchInput
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          onClear={() => setSearch("")}
          showClear={search.length > 0}
          placeholder="ابحث في المتجر..."
          className="max-w-md w-full"
        />
      </div>

      {renderCoursesTab()}
    </div>
  );
}

export default function CoursesPage() {
  return (
    <Suspense
      fallback={
        <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {Array.from({ length: 8 }).map((_, i) => (
              <CourseCardSkeleton key={i} />
            ))}
          </div>
        </div>
      }
    >
      <CoursesContent />
    </Suspense>
  );
}
