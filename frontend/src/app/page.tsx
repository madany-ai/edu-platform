import Link from "next/link";
import { Button } from "@/components/ui/button";
import { GraduationCap, ArrowLeft, Play } from "lucide-react";
import { FeaturesSection } from "@/components/features-section";
import { StatsSection } from "@/components/stats-section";
import { TestimonialsSection } from "@/components/testimonials-section";

export default function Home() {
  return (
    <>
      <section className="relative overflow-hidden bg-gradient-to-b from-primary/5 via-primary/5 to-background">
        <div className="mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28 lg:py-32">
          <div className="mx-auto max-w-3xl text-center">
            <div className="mb-6 inline-flex items-center gap-2 rounded-full border bg-background px-4 py-1.5 text-sm text-muted-foreground shadow-sm">
              <GraduationCap className="h-4 w-4 text-primary" />
              منصة تعليمية متكاملة
            </div>
            <h1 className="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
              تعلم{" "}
              <span className="text-primary">مهارات المستقبل</span>
              <br />
              من أفضل المدربين
            </h1>
            <p className="mt-6 text-lg text-muted-foreground sm:text-xl">
              انضم إلى آلاف الطلاب وابدأ رحلتك التعليمية مع دورات احترافية
              في البرمجة والتصميم والتسويق والمزيد
            </p>
            <div className="mt-8 flex flex-wrap items-center justify-center gap-4">
              <Link href="/courses">
                <Button size="lg" className="gap-2">
                  استعرض الدورات
                  <ArrowLeft className="h-4 w-4" />
                </Button>
              </Link>
              <Link href="/register">
                <Button size="lg" variant="outline" className="gap-2">
                  <Play className="h-4 w-4" />
                  ابدأ مجاناً
                </Button>
              </Link>
            </div>
          </div>
        </div>
        <div className="absolute inset-0 -z-10 bg-[linear-gradient(to_right,#f0f0f0_1px,transparent_1px),linear-gradient(to_bottom,#f0f0f0_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000,transparent)]" />
      </section>

      <FeaturesSection />
      <StatsSection />
      <TestimonialsSection />

      <section className="bg-primary/5 py-20">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 text-center">
          <h2 className="text-3xl font-bold mb-4">هل أنت مستعد لبدء رحلتك التعليمية؟</h2>
          <p className="text-muted-foreground mb-8 max-w-xl mx-auto">
            سجل الآن واحصل على وصول غير محدود إلى جميع دوراتنا
          </p>
          <Link href="/register">
            <Button size="lg" className="gap-2">
              سجل مجاناً
              <ArrowLeft className="h-4 w-4" />
            </Button>
          </Link>
        </div>
      </section>
    </>
  );
}
