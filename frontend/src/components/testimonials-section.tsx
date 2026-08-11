"use client";

import { useState } from "react";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { ChevronLeft, ChevronRight, Star, MessageSquareQuote } from "lucide-react";

const writtenReviews = [
  {
    name: "أحمد عبد الله",
    role: "طالب بالصف الثالث الإعدادي",
    content: "شكراً يا مستر حفني، الشرح ممتاز جداً ومبسط. حضرتك بتفهمنا الرياضيات صح وبنهتم بكل تفصيلة، وفعلاً حضرتك من أعظم المدرسين.",
  },
  {
    name: "محمود حسن",
    role: "طالب بالثانوية العامة",
    content: "شهادة لله يا مستر حضرتك من أعظم المدرسين اهتماماً بالطلاب، وأنا أتمنى إني أقابل أستاذ زي حضرتك. صعب جداً ألاقي حد بيراعي ربنا في الطلاب زيك.",
  },
  {
    name: "كريم سيد",
    role: "طالب بالثانوية العامة (علمي رياضة)",
    content: "يا مستر أنا واثق فيك وربنا يوفقك في كل مكان. حضرتك بجد تستاهل كل خير والله، وربنا يجعله في ميزان حسناتك.",
  },
];

export function TestimonialsSection() {
  const [current, setCurrent] = useState(0);

  const next = () => setCurrent((c) => (c + 1) % writtenReviews.length);
  const prev = () => setCurrent((c) => (c - 1 + writtenReviews.length) % writtenReviews.length);

  return (
    <section className="py-20 border-t border-white/5 bg-secondary/5">
      <div className="mx-auto max-w-7xl px-4 sm:px-6">
        <div className="text-center max-w-3xl mx-auto mb-16">
          <h2 className="text-3xl font-extrabold text-gradient mb-4">آراء طلابنا 🌟</h2>
          <p className="text-muted-foreground">
            فخورون بمرافقة آلاف الطلاب في رحلة التميز والتفوق في مادة الرياضيات.
          </p>
        </div>

        {/* Written Reviews Subsection */}
        <div className="max-w-3xl mx-auto">
          <h3 className="text-xl font-bold text-foreground mb-8 text-center flex items-center justify-center gap-2">
            <MessageSquareQuote className="h-5 w-5 text-primary" />
            ماذا يقولون عن تجربة التعلم مع مستر حفني؟
          </h3>
          <div className="relative">
            <Card className="border border-white/5 bg-surface-tonal-a10/40 backdrop-blur-md shadow-xl rounded-3xl">
              <CardContent className="p-8 md:p-10 text-center">
                <div className="flex justify-center gap-1 mb-6">
                  {Array.from({ length: 5 }).map((_, i) => (
                    <Star key={i} className="h-5 w-5 fill-warning-a0 text-warning-a0 animate-pulse" style={{ animationDelay: `${i * 150}ms` }} />
                  ))}
                </div>
                <blockquote className="text-lg md:text-xl text-foreground mb-8 italic leading-relaxed font-medium">
                  &ldquo;{writtenReviews[current].content}&rdquo;
                </blockquote>
                <p className="font-bold text-primary text-base mb-1">{writtenReviews[current].name}</p>
                <p className="text-xs text-muted-foreground">{writtenReviews[current].role}</p>
              </CardContent>
            </Card>

            <div className="flex justify-center gap-3 mt-6">
              <Button variant="outline" size="icon" onClick={prev} className="rounded-full hover:bg-primary hover:text-white border-white/10 transition-colors">
                <ChevronRight className="h-4 w-4" />
              </Button>
              <Button variant="outline" size="icon" onClick={next} className="rounded-full hover:bg-primary hover:text-white border-white/10 transition-colors">
                <ChevronLeft className="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
