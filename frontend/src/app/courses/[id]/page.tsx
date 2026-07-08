"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Separator } from "@/components/ui/separator";
import { getCourse, enrollCourse } from "@/lib/api/courses";
import { useAuth } from "@/contexts/auth-context";
import type { Course as CourseType } from "@/lib/types";
import {
  CheckCircle2, Clock, BookOpen, Users, PlayCircle,
  ArrowRight, Share2, Heart, Award
} from "lucide-react";

export default function CourseDetailPage() {
  const { id } = useParams<{ id: string }>();
  const { user } = useAuth();
  const [course, setCourse] = useState<CourseType | null>(null);
  const [loading, setLoading] = useState(true);
  const [enrolling, setEnrolling] = useState(false);
  const [enrolled, setEnrolled] = useState(false);

  useEffect(() => {
    getCourse(id).then((data) => {
      setCourse(data);
      setLoading(false);
    });
  }, [id]);

  const handleEnroll = async () => {
    if (!course) return;
    setEnrolling(true);
    try {
      await enrollCourse(course.id);
      setEnrolled(true);
    } finally {
      setEnrolling(false);
    }
  };

  if (loading) {
    return (
      <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <Skeleton className="h-8 w-64 mb-4" />
        <Skeleton className="h-6 w-96 mb-8" />
        <div className="grid gap-8 lg:grid-cols-3">
          <div className="lg:col-span-2 space-y-6">
            <Skeleton className="h-32" />
            <Skeleton className="h-48" />
          </div>
          <div>
            <Skeleton className="h-96" />
          </div>
        </div>
      </div>
    );
  }

  if (!course) {
    return <div className="text-center py-20">الدورة غير موجودة</div>;
  }

  const outcomes = [
    "بناء تطبيقات ويب تفاعلية باستخدام React",
    "فهم مفاهيم React الأساسية والمتقدمة",
    "إدارة حالة التطبيق باستخدام Redux",
    "بناء REST APIs متكاملة",
    "نشر التطبيقات على منصات السحابة",
  ];

  const levelMap: Record<string, string> = {
    beginner: "مبتدئ",
    intermediate: "متوسط",
    advanced: "متقدم",
  };

  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <div className="grid gap-8 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-8">
          <div>
            <div className="flex items-center gap-2 text-sm text-muted-foreground mb-4">
              <Link href="/" className="hover:text-foreground">الرئيسية</Link>
              <span>/</span>
              <Link href="/courses" className="hover:text-foreground">الدورات</Link>
              <span>/</span>
              <span className="text-foreground">{course.category?.name}</span>
            </div>

            <h1 className="text-3xl font-bold mb-4">{course.title}</h1>

            <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground mb-6">
              <span className="flex items-center gap-1">
                <PlayCircle className="h-4 w-4" />
                {course.lessons_count} درس
              </span>
              <span className="flex items-center gap-1">
                <Clock className="h-4 w-4" />
                {Math.round(course.duration_minutes / 60)} ساعة
              </span>
              <span className="flex items-center gap-1">
                <Users className="h-4 w-4" />
                {course.students_count} طالب
              </span>
              <Badge variant="secondary">{levelMap[course.level] || course.level}</Badge>
            </div>

            <p className="text-muted-foreground leading-relaxed">
              {course.description}
            </p>
          </div>

          <Separator />

          <div>
            <h2 className="text-xl font-bold mb-4">ماذا ستتعلم</h2>
            <div className="grid gap-3 sm:grid-cols-2">
              {outcomes.map((outcome) => (
                <div key={outcome} className="flex items-start gap-2">
                  <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                  <span className="text-sm">{outcome}</span>
                </div>
              ))}
            </div>
          </div>

          <Separator />

          {course.lessons && course.lessons.length > 0 && (
            <div>
              <h2 className="text-xl font-bold mb-4">محتوى الدورة</h2>
              <div className="space-y-2">
                {course.lessons.map((lesson, i) => (
                  <div key={lesson.id} className="flex items-center justify-between rounded-lg border p-4">
                    <div className="flex items-center gap-3">
                      <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-sm font-medium text-primary">
                        {i + 1}
                      </div>
                      <div>
                        <p className="font-medium">{lesson.title}</p>
                        <p className="text-xs text-muted-foreground">{lesson.duration_minutes} دقيقة</p>
                      </div>
                    </div>
                    <PlayCircle className="h-5 w-5 text-muted-foreground" />
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        <div className="lg:col-span-1">
          <div className="sticky top-24 space-y-6">
            <Card>
              <div className="aspect-video bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center rounded-t-lg">
                <PlayCircle className="h-16 w-16 text-primary/60" />
              </div>
              <CardContent className="p-6 space-y-4">
                <div className="flex items-end gap-2">
                  <span className="text-3xl font-bold text-primary">
                    {course.price === 0 ? "مجاني" : `${course.price} د.م`}
                  </span>
                  {course.price > 0 && (
                    <span className="text-sm text-muted-foreground line-through mb-1">
                      {Math.round(course.price * 2)} د.م
                    </span>
                  )}
                </div>

                {user ? (
                  enrolled ? (
                    <Button className="w-full gap-2" size="lg" disabled>
                      <CheckCircle2 className="h-4 w-4" />
                      أنت مسجل في هذه الدورة
                    </Button>
                  ) : (
                    <Button
                      className="w-full gap-2"
                      size="lg"
                      onClick={handleEnroll}
                      disabled={enrolling}
                    >
                      {enrolling ? "جاري التسجيل..." : "سجل الآن"}
                      <ArrowRight className="h-4 w-4" />
                    </Button>
                  )
                ) : (
                  <Link href="/login">
                    <Button className="w-full gap-2" size="lg">
                      سجل دخول للتسجيل
                      <ArrowRight className="h-4 w-4" />
                    </Button>
                  </Link>
                )}

                <Separator />

                <div className="space-y-3 text-sm">
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">المدة</span>
                    <span className="font-medium">{Math.round(course.duration_minutes / 60)} ساعة</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">المستوى</span>
                    <span className="font-medium">{levelMap[course.level] || course.level}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">الدروس</span>
                    <span className="font-medium">{course.lessons_count}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">الطلاب</span>
                    <span className="font-medium">{course.students_count}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">اللغة</span>
                    <span className="font-medium">{course.language}</span>
                  </div>
                </div>

                <div className="flex gap-2 pt-2">
                  <Button variant="outline" size="sm" className="flex-1 gap-2">
                    <Share2 className="h-4 w-4" />
                    مشاركة
                  </Button>
                  <Button variant="outline" size="sm" className="flex-1 gap-2">
                    <Heart className="h-4 w-4" />
                    حفظ
                  </Button>
                </div>
              </CardContent>
            </Card>

            {course.instructor && (
              <Card>
                <CardContent className="p-6">
                  <div className="flex items-center gap-4 mb-4">
                    <Avatar>
                      <AvatarFallback className="bg-primary/10 text-primary font-bold">
                        {course.instructor.name.charAt(0)}
                      </AvatarFallback>
                    </Avatar>
                    <div>
                      <p className="font-semibold">{course.instructor.name}</p>
                      <p className="text-xs text-muted-foreground">مدرب معتمد</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-1 text-sm">
                    <Award className="h-4 w-4 text-amber-500" />
                    <span className="text-muted-foreground">4.8 (128 تقييم)</span>
                  </div>
                </CardContent>
              </Card>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
