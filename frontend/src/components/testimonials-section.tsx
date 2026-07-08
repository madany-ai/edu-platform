"use client";

import { useState } from "react";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { ChevronLeft, ChevronRight, Star } from "lucide-react";

const testimonials = [
  {
    name: "أحمد محمد",
    role: "طالب",
    content: "تجربة رائعة! المنصة سهلة الاستخدام والمحتوى التعليمي ممتاز. أنصح الجميع بالتسجيل.",
  },
  {
    name: "سارة خالد",
    role: "مطورة ويب",
    content: "دورات البرمجة هنا مختلفة تماماً. المنهج محدث ويلائم سوق العمل. استفدت كثيراً.",
  },
  {
    name: "محمد علي",
    role: "مصمم جرافيك",
    content: "أفضل منصة تعليمية جربتها. المدربون محترفون والمحتوى منظم بشكل رائع.",
  },
];

export function TestimonialsSection() {
  const [current, setCurrent] = useState(0);

  const next = () => setCurrent((c) => (c + 1) % testimonials.length);
  const prev = () => setCurrent((c) => (c - 1 + testimonials.length) % testimonials.length);

  return (
    <section className="py-20">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 text-center">
        <h2 className="text-3xl font-bold mb-2">ماذا يقول طلابنا</h2>
        <p className="text-muted-foreground mb-12">آراء الطلاب في تجربتهم التعليمية</p>

        <div className="relative mx-auto max-w-2xl">
          <Card className="border-0 bg-muted/30 shadow-none">
            <CardContent className="p-8">
              <div className="flex justify-center gap-1 mb-4">
                {Array.from({ length: 5 }).map((_, i) => (
                  <Star key={i} className="h-5 w-5 fill-amber-400 text-amber-400" />
                ))}
              </div>
              <blockquote className="text-lg mb-6 leading-relaxed">
                &ldquo;{testimonials[current].content}&rdquo;
              </blockquote>
              <p className="font-semibold">{testimonials[current].name}</p>
              <p className="text-sm text-muted-foreground">{testimonials[current].role}</p>
            </CardContent>
          </Card>

          <div className="flex justify-center gap-2 mt-6">
            <Button variant="outline" size="icon" onClick={prev}>
              <ChevronRight className="h-4 w-4" />
            </Button>
            <Button variant="outline" size="icon" onClick={next}>
              <ChevronLeft className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>
    </section>
  );
}
