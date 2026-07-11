import Link from "next/link";
import { Button } from "@/components/ui/button";
import { GraduationCap, ArrowLeft, Play, Atom, FlaskConical, Sparkles } from "lucide-react";
import { FeaturesSection } from "@/components/features-section";
import { StatsSection } from "@/components/stats-section";
import { TestimonialsSection } from "@/components/testimonials-section";

export default function Home() {
  return (
    <>
      <section className="relative overflow-hidden pt-24 pb-20 lg:pt-32 lg:pb-28">
        {/* Glow Background Grids */}
        <div className="absolute inset-0 -z-10 bg-[linear-gradient(to_right,rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_40%,#000,transparent)]" />
        <div className="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[350px] h-[350px] bg-primary/20 rounded-full blur-[120px] -z-10" />
        <div className="absolute top-1/3 left-1/3 w-[250px] h-[250px] bg-secondary/15 rounded-full blur-[100px] -z-10 animate-pulse" />

        <div className="mx-auto max-w-7xl px-4 sm:px-6 text-center relative">
          <div className="mx-auto max-w-3xl">
            <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5 text-xs font-semibold text-primary shadow-lg cosmic-border-glow">
              <Atom className="h-4 w-4 text-primary animate-spin" style={{ animationDuration: '6s' }} />
              <span>🧪 مختبر العلوم الكوني الرقمي</span>
            </div>
            <h1 className="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl text-foreground leading-tight">
              اكتشف أسرار العلوم والفيزياء
              <br />
              <span className="text-gradient science-glow-text">بطريقة ممتعة وتفاعلية</span>
            </h1>
            <p className="mt-6 text-base text-muted-foreground sm:text-lg max-w-2xl mx-auto leading-relaxed">
              منصة تعليمية مبتكرة مصممة بأحدث التقنيات لتبسيط مناهج الساينس والفيزياء لطلاب المرحلة الإعدادية من خلال بث مرئي مشفر وآمن، وتجارب حية ملهمة.
            </p>
            <div className="mt-8 flex flex-wrap items-center justify-center gap-4">
              <Link href="/courses">
                <Button size="lg" className="gap-2 bg-gradient-to-r from-primary to-secondary text-primary-foreground font-bold shadow-lg shadow-primary/10">
                  استكشف المحاضرات 🔬
                  <ArrowLeft className="h-4 w-4" />
                </Button>
              </Link>
              <Link href="/register">
                <Button size="lg" variant="outline" className="gap-2 border-white/10 hover:bg-muted text-foreground">
                  <Play className="h-4 w-4 text-primary fill-primary" />
                  سجل مجاناً وابدأ
                </Button>
              </Link>
            </div>
          </div>
        </div>
      </section>

      <FeaturesSection />
      <StatsSection />
      <TestimonialsSection />

      <section className="relative overflow-hidden py-24 border-t border-white/5 bg-gradient-to-b from-transparent to-muted/10">
        <div className="absolute inset-0 -z-10 bg-[linear-gradient(to_right,rgba(255,255,255,0.01)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.01)_1px,transparent_1px)] bg-[size:3rem_3rem]" />
        <div className="mx-auto max-w-5xl px-4 sm:px-6 text-center">
          <div className="inline-flex items-center justify-center p-3 rounded-full bg-secondary/10 text-secondary mb-4">
            <FlaskConical className="h-8 w-8 animate-pulse" />
          </div>
          <h2 className="text-3xl font-extrabold text-gradient mb-4">هل أنت مستعد لتصبح عالماً مستقبلياً؟</h2>
          <p className="text-muted-foreground mb-8 max-w-xl mx-auto text-sm leading-relaxed">
            سجل حسابك اليوم لتنضم لزملائك الطلاب وتبدأ في حضور الحصص، حل الاختبارات التفاعلية، ومتابعة لوحة الإحصائيات الخاصة بك.
          </p>
          <Link href="/register">
            <Button size="lg" className="gap-2 bg-gradient-to-r from-primary to-secondary text-primary-foreground font-bold shadow-lg">
              سجل حسابك مجاناً الآن
              <ArrowLeft className="h-4 w-4" />
            </Button>
          </Link>
        </div>
      </section>
    </>
  );
}
