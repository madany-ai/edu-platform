"use client";

import Link from "next/link";
import { Button } from "@/components/ui/button";
import { GraduationCap, ArrowLeft, Play, Atom, FlaskConical, Sparkles } from "lucide-react";
import { FeaturesSection } from "@/components/features-section";
import { StatsSection } from "@/components/stats-section";
import { TestimonialsSection } from "@/components/testimonials-section";
import { useAuth } from "@/providers/auth-provider";

export default function Home() {
  const { isAuthenticated } = useAuth();

  return (
    <>
      <section className="relative overflow-hidden pt-24 pb-20 lg:pt-32 lg:pb-28">
        {/* Glow Background Grids */}
        <div className="absolute inset-0 -z-10 bg-[linear-gradient(to_right,rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_40%,#000,transparent)]" />
        <div className="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[350px] h-[350px] bg-primary/20 rounded-full blur-[120px] -z-10" />
        <div className="absolute top-1/3 left-1/3 w-[250px] h-[250px] bg-secondary/15 rounded-full blur-[100px] -z-10 animate-pulse" />

        <div className="mx-auto max-w-7xl px-4 sm:px-6 relative">
          <div className="grid gap-12 lg:grid-cols-12 items-center">
            {/* Text Content */}
            <div className="lg:col-span-7 text-right space-y-6">
              <div className="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5 text-xs font-semibold text-primary shadow-lg cosmic-border-glow">
                <Atom className="h-4 w-4 text-primary animate-spin" style={{ animationDuration: '6s' }} />
                <span>🧪 Mr Islam Science | مستر إسلام عبد الجليل</span>
              </div>
              <h1 className="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl text-foreground leading-tight">
                اكتشف أسرار الساينس والعلوم
                <br />
                <span className="text-gradient science-glow-text">مع مستر إسلام عبد الجليل</span>
              </h1>
              <p className="text-base text-muted-foreground sm:text-lg leading-relaxed">
                شرح مبسط، متابعة مستمرة، وتجارب حية ملهمة. منصة تعليمية مبتكرة ومصممة بأحدث التقنيات لتبسيط مناهج الساينس والعلوم لطلاب المرحلة الإعدادية من خلال بث مرئي مشفر وآمن.
              </p>
              <div className="flex flex-wrap items-center justify-start gap-4">
                <Link href="/courses">
                  <Button size="lg" className="gap-2 bg-gradient-to-r from-primary to-secondary text-primary-foreground font-bold shadow-lg shadow-primary/10">
                    استكشف المحاضرات 🔬
                    <ArrowLeft className="h-4 w-4" />
                  </Button>
                </Link>
                {isAuthenticated ? (
                  <Link href="/dashboard">
                    <Button size="lg" variant="outline" className="gap-2 border-white/10 hover:bg-muted text-foreground">
                      حساب الطالب الخاص بي
                    </Button>
                  </Link>
                ) : (
                  <Link href="/register">
                    <Button size="lg" variant="outline" className="gap-2 border-white/10 hover:bg-muted text-foreground">
                      <Play className="h-4 w-4 text-primary fill-primary" />
                      سجل مجاناً وابدأ الآن
                  </Button>
                </Link>
              )}
              </div>
            </div>

            {/* Teacher Image */}
            <div className="lg:col-span-5 flex justify-center relative">
              <div className="absolute inset-0 bg-primary/20 rounded-full blur-[80px] -z-10" />
              <div className="relative w-[280px] h-[280px] sm:w-[340px] sm:h-[340px] rounded-full overflow-hidden border-[6px] border-primary/30 shadow-2xl shadow-primary/20 scale-[1.02] transition-transform duration-500 hover:scale-[1.05] cosmic-border-glow">
                <img 
                  src="/teacher.jpg" 
                  alt="مستر إسلام عبد الجليل" 
                  className="w-full h-full object-cover object-top"
                />
              </div>
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
          <h2 className="text-3xl font-extrabold text-gradient mb-4">هل أنت مستعد لتصبح متفوقاً في الساينس؟</h2>
          <p className="text-muted-foreground mb-8 max-w-xl mx-auto text-sm leading-relaxed">
            {isAuthenticated
              ? "ابدأ بمتابعة محاضراتك، وحل الواجبات والاختبارات التفاعلية مباشرة من حسابك."
              : "سجل حسابك اليوم لتنضم لزملائك وتبدأ في حضور الحصص، حل الاختبارات التفاعلية، ومتابعة درجاتك أولاً بأول."}
          </p>
          {isAuthenticated ? (
            <Link href="/dashboard">
              <Button size="lg" className="gap-2 bg-gradient-to-r from-primary to-secondary text-primary-foreground font-bold shadow-lg">
                اذهب إلى حسابي الدراسي
                <ArrowLeft className="h-4 w-4" />
              </Button>
            </Link>
          ) : (
            <Link href="/register">
              <Button size="lg" className="gap-2 bg-gradient-to-r from-primary to-secondary text-primary-foreground font-bold shadow-lg">
                سجل حسابك مجاناً الآن
                <ArrowLeft className="h-4 w-4" />
              </Button>
            </Link>
          )}
        </div>
      </section>
    </>
  );
}
