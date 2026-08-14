"use client";

import Link from "next/link";
import { Button } from "@/components/ui/button";
import { ArrowLeft, Play, Calculator, Sigma, Sparkles, BookOpen } from "lucide-react";
import { FeaturesSection } from "@/components/features-section";
import { StatsSection } from "@/components/stats-section";
import { TestimonialsSection } from "@/components/testimonials-section";
import { useAuth } from "@/providers/auth-provider";
import Image from "next/image";
import { motion } from "framer-motion";

export default function Home() {
  const { isAuthenticated, user, isInstructor, isAssistant } = useAuth();
  const isStaff = isInstructor || isAssistant || user?.roles?.some((r: any) => ["instructor", "assistant", "super_admin", "admin"].includes(typeof r === "string" ? r : r.name));

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
            <motion.div 
              initial={{ opacity: 0, x: 50 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.8, type: "spring", bounce: 0.4 }}
              className="lg:col-span-7 text-right space-y-6"
            >
              <motion.div 
                initial={{ scale: 0.8, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                transition={{ delay: 0.2, duration: 0.5 }}
                className="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5 text-xs font-semibold text-primary shadow-[0_0_15px_rgba(var(--primary),0.2)] cosmic-border-glow"
              >
                <Calculator className="h-4 w-4 text-primary animate-bounce" />
                <span>➗ Mr Hefni Muhammad | حفني محمد</span>
              </motion.div>
              
              <motion.h1 
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.4, duration: 0.5 }}
                className="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl text-foreground leading-tight"
              >
                التفوق في الرياضيات أصبح مضموناً
                <br />
                <span className="text-transparent bg-clip-text bg-gradient-to-l from-primary via-secondary to-primary bg-[length:200%_auto] animate-gradient science-glow-text relative inline-block after:content-[''] after:absolute after:-bottom-2 after:right-0 after:w-1/2 after:h-1 after:bg-primary after:rounded-full after:transition-all after:duration-300 hover:after:w-full">مع حفني محمد</span>
              </motion.h1>
              
              <motion.p 
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                transition={{ delay: 0.6, duration: 0.5 }}
                className="text-base text-muted-foreground sm:text-lg leading-relaxed"
              >
                شرح مبسط، متابعة مستمرة، وتدريب مكثف على أحدث الأنظمة. منصة تعليمية مبتكرة مصممة لتسهيل مادة الرياضيات لطلاب الثانوية العامة والصف الثالث الإعدادي. 
              </motion.p>
              
              <motion.div 
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.8, duration: 0.5 }}
                className="flex flex-wrap items-center justify-start gap-4"
              >
                <Link href="/courses">
                  <Button size="lg" className="group gap-2 bg-gradient-to-r from-primary to-secondary text-primary-foreground font-bold shadow-[0_0_20px_rgba(var(--primary),0.3)] hover:shadow-[0_0_30px_rgba(var(--primary),0.5)] hover:scale-105 transition-all duration-300">
                    استكشف المحاضرات 📐
                    <ArrowLeft className="h-4 w-4 group-hover:-translate-x-1 transition-transform" />
                  </Button>
                </Link>
                {isAuthenticated ? (
                  <Link href={isStaff ? "/center" : "/dashboard"}>
                    <Button size="lg" variant="outline" className="gap-2 border-white/10 hover:bg-muted text-foreground">
                      {isStaff ? "إدارة السنتر" : "حساب الطالب الخاص بي"}
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
              </motion.div>
            </motion.div>

            {/* Teacher Image */}
            <motion.div 
              initial={{ opacity: 0, scale: 0.8, rotate: -5 }}
              animate={{ opacity: 1, scale: 1, rotate: 0 }}
              transition={{ duration: 0.8, type: "spring", bounce: 0.5, delay: 0.2 }}
              className="lg:col-span-5 flex justify-center relative group"
            >
              <div className="absolute inset-0 bg-primary/20 rounded-full blur-[80px] -z-10 group-hover:bg-primary/30 transition-all duration-500 group-hover:blur-[100px]" />
              <motion.div 
                animate={{ y: [0, -15, 0] }}
                transition={{ repeat: Infinity, duration: 4, ease: "easeInOut" }}
                className="relative w-[280px] h-[280px] sm:w-[340px] sm:h-[340px] rounded-full overflow-hidden border-[6px] border-primary/30 shadow-2xl shadow-primary/20 transition-transform duration-500 hover:scale-[1.05] cosmic-border-glow hover:border-primary/50"
              >
                <Image 
                  src="/logo.jpg" 
                  alt="حفني محمد" 
                  fill
                  priority
                  className="object-cover object-top group-hover:scale-110 transition-transform duration-700 ease-out"
                  sizes="(max-width: 768px) 280px, 340px"
                />
              </motion.div>
              {/* Floating particles */}
              <motion.div 
                animate={{ y: [0, -20, 0], opacity: [0.5, 1, 0.5] }}
                transition={{ repeat: Infinity, duration: 3, ease: "easeInOut", delay: 0.5 }}
                className="absolute top-10 right-10 bg-surface-tonal-a10/60 backdrop-blur-md p-3 rounded-2xl shadow-lg border border-white/10"
              >
                <Calculator className="h-6 w-6 text-primary" />
              </motion.div>
              <motion.div 
                animate={{ y: [0, 20, 0], opacity: [0.5, 1, 0.5] }}
                transition={{ repeat: Infinity, duration: 4, ease: "easeInOut", delay: 1 }}
                className="absolute bottom-10 left-10 bg-surface-tonal-a10/60 backdrop-blur-md p-3 rounded-2xl shadow-lg border border-white/10"
              >
                <Sigma className="h-6 w-6 text-secondary" />
              </motion.div>
            </motion.div>
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
            <Sigma className="h-8 w-8 animate-pulse" />
          </div>
          <h2 className="text-3xl font-extrabold text-gradient mb-4">هل أنت مستعد لتقفيل الرياضيات؟</h2>
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
