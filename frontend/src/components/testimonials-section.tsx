"use client";

import { useState } from "react";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { ChevronLeft, ChevronRight, Star, Play, MessageSquareQuote } from "lucide-react";

const youtubeReviews = [
  {
    title: "رأي الطالب المتفوق أحمد في مادة الساينس",
    videoId: "y4v6P3NqG3k", // Placeholder or generic education video, user can update
    student: "أحمد محمد (الصف الثالث الإعدادي)",
  },
  {
    title: "قصة نجاح الطالبة سارة والحصول على الدرجة النهائية",
    videoId: "8lJgK3v6e3g",
    student: "سارة أحمد (الصف الأول الإعدادي)",
  },
];

const writtenReviews = [
  {
    name: "مريم يوسف",
    role: "ولي أمر الطالبة جودي",
    content: "أشكر مستر إسلام جزيل الشكر على مجهوده الرائع. ابنتي كانت تكره الساينس والآن أصبحت من الأوائل بفضل شرحه التفاعلي الممتع.",
  },
  {
    name: "عمر خالد",
    role: "طالب بالصف الثاني الإعدادي",
    content: "الامتحانات التفاعلية على المنصة ساعدتني جداً في تثبيت المعلومة، والمحاضرات المسجلة أقدر أعيدها في أي وقت لو فاتني جزء.",
  },
  {
    name: "د. عبد الرحمن محمود",
    role: "ولي أمر الطالب يوسف",
    content: "المنصة احترافية جداً والآمان عالي فيها. شرح مستر إسلام للدروس بيعتمد على الفهم والتحليل وليس الحفظ والتلقين.",
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
          <h2 className="text-3xl font-extrabold text-gradient mb-4">آراء طلابنا وأولياء الأمور 🌟</h2>
          <p className="text-muted-foreground">
            فخورون بمرافقة آلاف الطلاب في رحلة التميز والتفوق في مادة العلوم والساينس.
          </p>
        </div>

        {/* Video Reviews Subsection */}
        <div className="mb-20">
          <h3 className="text-xl font-bold text-foreground mb-8 text-center flex items-center justify-center gap-2">
            <Play className="h-5 w-5 text-primary fill-primary" />
            فيديوهات وتجارب حية من الطلاب الأوائل
          </h3>
          <div className="grid gap-8 md:grid-cols-2 max-w-5xl mx-auto">
            {youtubeReviews.map((video, idx) => (
              <div key={idx} className="glass-card rounded-2xl overflow-hidden border border-white/5 flex flex-col group hover:border-primary/20 transition-all duration-300">
                <div className="relative aspect-video w-full bg-black">
                  <iframe
                    className="w-full h-full"
                    src={`https://www.youtube.com/embed/${video.videoId}?rel=0&modestbranding=1`}
                    title={video.title}
                    frameBorder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowFullScreen
                  ></iframe>
                </div>
                <div className="p-5 flex-1 flex flex-col justify-between">
                  <h4 className="font-bold text-foreground mb-2 group-hover:text-primary transition-colors">{video.title}</h4>
                  <p className="text-xs text-muted-foreground">{video.student}</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Written Reviews Subsection */}
        <div className="max-w-3xl mx-auto">
          <h3 className="text-xl font-bold text-foreground mb-8 text-center flex items-center justify-center gap-2">
            <MessageSquareQuote className="h-5 w-5 text-primary" />
            ماذا يقولون عن تجربة التعلم مع مستر إسلام؟
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
