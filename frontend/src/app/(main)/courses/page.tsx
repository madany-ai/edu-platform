"use client";

import { Suspense, useState } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { useCourses } from "@/hooks/useCourses";
import { useProducts, useBundles, useCreateOrder } from "@/hooks/useProducts";
import { CourseCard } from "@/components/course-card";
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
  BookOpen,
  PlayCircle,
  Clock,
  Award,
  CreditCard,
  Calendar,
  Layers,
  Sparkles,
} from "lucide-react";

function CoursesContent() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const { user } = useAuth();
  const [search, setSearch] = useState(searchParams.get("search") || "");
  const debouncedSearch = useDebounce(search);
  
  const [activeTab, setActiveTab] = useState<"courses" | "lectures" | "sections" | "bundles">("courses");

  // Fetching data
  const { data: coursesData, isLoading: coursesLoading } = useCourses(
    debouncedSearch ? { search: debouncedSearch } : undefined
  );
  const { data: lecturesData, isLoading: lecturesLoading } = useProducts("lecture");
  const { data: sectionsData, isLoading: sectionsLoading } = useProducts("section");
  const { data: bundlesData, isLoading: bundlesLoading } = useBundles();

  const createOrderMutation = useCreateOrder();

  const courses = coursesData?.data ?? [];
  const lectures = lecturesData ?? [];
  const sections = sectionsData ?? [];
  const bundles = bundlesData ?? [];

  const handlePurchase = (id: string, type: 'product' | 'bundle', price: number, name: string) => {
    if (!user) {
      toast.error("يرجى تسجيل الدخول أولاً لإتمام عملية الشراء");
      router.push("/login");
      return;
    }

    toast.info(`جاري بدء معالجة شراء: ${name}...`);

    createOrderMutation.mutate(
      { purchasable_id: id, purchasable_type: type },
      {
        onSuccess: (res) => {
          toast.success(`تم الشراء بنجاح! تم تفعيل المحتوى الدراسي.`);
        },
        onError: (err: any) => {
          const errMsg = err.response?.data?.message || "فشلت عملية الشراء، يرجى المحاولة مرة أخرى.";
          toast.error(errMsg);
        },
      }
    );
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
              
              <Button
                onClick={() => handlePurchase(item.id, 'product', item.price, item.name)}
                disabled={createOrderMutation.isPending}
                size="sm"
                className="bg-primary hover:bg-primary-hover text-primary-foreground font-bold rounded-xl gap-1.5"
              >
                <Sparkles className="h-4 w-4" />
                {createOrderMutation.isPending ? "جاري المعالجة..." : "شراء الآن"}
              </Button>
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
                <span className="text-xs text-muted-foreground block">السعر الإجمالي للجمعة</span>
                <span className="text-lg font-black text-primary science-glow-text">
                  {bundle.price} EGP
                </span>
              </div>
              
              <Button
                onClick={() => handlePurchase(bundle.id, 'bundle', bundle.price, bundle.name)}
                disabled={createOrderMutation.isPending}
                size="sm"
                className="bg-primary hover:bg-primary-hover text-primary-foreground font-bold rounded-xl gap-1.5"
              >
                <Sparkles className="h-4 w-4" />
                {createOrderMutation.isPending ? "جاري المعالجة..." : "شراء الباقة"}
              </Button>
            </div>
          </div>
        ))}
      </div>
    );
  };

  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <PageHeader
        title="المتجر الأكاديمي 🧪"
        description="تصفح واشترك في الدورات الكاملة، الحلقات الشهرية، أو المحاضرات الفردية مباشرة."
      />

      <div className="mb-8 flex flex-col md:flex-row gap-4 items-stretch md:items-center justify-between">
        <SearchInput
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          onClear={() => setSearch("")}
          showClear={search.length > 0}
          placeholder="ابحث في المتجر..."
          className="max-w-md"
        />

        {/* Unified premium Store Tabs */}
        <div className="flex gap-1.5 p-1 rounded-xl bg-[#141a15] border border-[#3b413c] overflow-x-auto whitespace-nowrap max-w-full scrollbar-none">
          <button
            onClick={() => setActiveTab("courses")}
            className={cn(
              "px-4 py-2 rounded-lg font-bold text-xs transition-all",
              activeTab === "courses"
                ? "bg-primary text-primary-foreground science-glow-text"
                : "text-muted-foreground hover:text-foreground"
            )}
          >
            الدورات الكاملة 📚
          </button>
          <button
            onClick={() => setActiveTab("lectures")}
            className={cn(
              "px-4 py-2 rounded-lg font-bold text-xs transition-all",
              activeTab === "lectures"
                ? "bg-primary text-primary-foreground science-glow-text"
                : "text-muted-foreground hover:text-foreground"
            )}
          >
            المحاضرات الفردية 🧪
          </button>
          <button
            onClick={() => setActiveTab("sections")}
            className={cn(
              "px-4 py-2 rounded-lg font-bold text-xs transition-all",
              activeTab === "sections"
                ? "bg-primary text-primary-foreground science-glow-text"
                : "text-muted-foreground hover:text-foreground"
            )}
          >
            الاشتراكات الشهرية 📅
          </button>
          <button
            onClick={() => setActiveTab("bundles")}
            className={cn(
              "px-4 py-2 rounded-lg font-bold text-xs transition-all",
              activeTab === "bundles"
                ? "bg-primary text-primary-foreground science-glow-text"
                : "text-muted-foreground hover:text-foreground"
            )}
          >
            باقات العروض 💎
          </button>
        </div>
      </div>

      {activeTab === "courses" && renderCoursesTab()}
      {activeTab === "lectures" && (
        lecturesLoading ? (
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {Array.from({ length: 6 }).map((_, i) => (
              <div key={i} className="h-44 glass-card rounded-2xl animate-pulse" />
            ))}
          </div>
        ) : renderProductsList(lectures, "محاضرات منفردة", PlayCircle)
      )}
      {activeTab === "sections" && (
        sectionsLoading ? (
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {Array.from({ length: 6 }).map((_, i) => (
              <div key={i} className="h-44 glass-card rounded-2xl animate-pulse" />
            ))}
          </div>
        ) : renderProductsList(sections, "اشتراكات شهرية", Calendar)
      )}
      {activeTab === "bundles" && (
        bundlesLoading ? (
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {Array.from({ length: 3 }).map((_, i) => (
              <div key={i} className="h-52 glass-card rounded-2xl animate-pulse" />
            ))}
          </div>
        ) : renderBundlesTab()
      )}
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
