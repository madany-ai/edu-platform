"use client";

import { useState } from "react";
import { useParams } from "next/navigation";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Separator } from "@/components/ui/separator";
import { useAuth } from "@/providers/auth-provider";
import { PageHeader } from "@/components/shared/page-header";
import { PageLoading } from "@/components/shared/loading-spinner";
import { ErrorState } from "@/components/shared/error-state";
import { useCourse } from "@/hooks/useCourses";
import { useMyEnrollments, useEnroll, usePurchase } from "@/hooks/useEnrollment";
import {
  CheckCircle2,
  Users,
  PlayCircle,
  ArrowRight,
  Share2,
  Heart,
  ChevronDown,
} from "lucide-react";

export default function CourseDetailPage() {
  const { id } = useParams<{ id: string }>();
  const { user, isInstructor } = useAuth();
  const [openSections, setOpenSections] = useState<Set<string>>(new Set());

  const { data: courseData, isLoading: courseLoading, error: courseError } = useCourse(id);
  const { data: enrollmentsData } = useMyEnrollments();
  const enrollMutation = useEnroll();
  const purchaseMutation = usePurchase();

  const course = courseData?.data;
  const enrolled = enrollmentsData?.data?.some(
    (e) => e.course_id === id || e.course?.id === id
  ) ?? false;

  const handleEnroll = async () => {
    if (!course) return;
    enrollMutation.mutate(course.id);
  };

  const handlePurchase = async () => {
    if (!course) return;
    purchaseMutation.mutate(course.id);
  };

  const toggleSection = (sectionId: string) => {
    setOpenSections((prev) => {
      const next = new Set(prev);
      if (next.has(sectionId)) {
        next.delete(sectionId);
      } else {
        next.add(sectionId);
      }
      return next;
    });
  };

  if (courseLoading) return <PageLoading />;

  if (courseError || !course) {
    return (
      <ErrorState
        title="الدورة غير موجودة"
        description="عذراً، الدورة التي تبحث عنها غير موجودة."
      />
    );
  }

  const firstLectureId = course.sections?.[0]?.lectures?.[0]?.id;
  const continueLearningUrl = firstLectureId
    ? `/courses/${id}/lectures/${firstLectureId}`
    : `/courses/${id}`;

  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <PageHeader
        title={course.title}
        breadcrumbs={[
          { label: "الرئيسية", href: "/" },
          { label: "الدورات", href: "/courses" },
          { label: course.title },
        ]}
      />

      <div className="grid gap-8 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-8">
          <div>
            <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground mb-6">
              <span className="flex items-center gap-1">
                <PlayCircle className="h-4 w-4" />
                {course.sections_count ?? 0} قسم
              </span>
              <span className="flex items-center gap-1">
                <Users className="h-4 w-4" />
                {course.students_count} طالب
              </span>
            </div>

            <p className="text-muted-foreground leading-relaxed">{course.description}</p>
          </div>

          <Separator />

          {course.sections && course.sections.length > 0 && (
            <div>
              <h2 className="text-xl font-bold mb-4">محتوى الدورة</h2>
              <div className="space-y-3">
                {course.sections.map((section, index) => (
                  <div key={section.id} className="rounded-lg border overflow-hidden">
                    <button
                      className="flex w-full items-center justify-between p-4 text-right hover:bg-muted/50 transition-colors"
                      onClick={() => toggleSection(section.id)}
                    >
                      <div className="flex items-center gap-3">
                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-sm font-medium text-primary">
                          {index + 1}
                        </div>
                        <div>
                          <p className="font-medium">{section.title}</p>
                          <p className="text-xs text-muted-foreground">
                            {section.lectures?.length ?? 0} محاضرات
                          </p>
                        </div>
                      </div>
                      <ChevronDown
                        className={`h-5 w-5 text-muted-foreground transition-transform ${
                          openSections.has(section.id) ? "rotate-180" : ""
                        }`}
                      />
                    </button>
                    {openSections.has(section.id) && section.lectures && (
                      <div className="border-t">
                        {section.lectures.map((lecture) => (
                          <div
                            key={lecture.id}
                            className="flex items-center justify-between px-4 py-3 hover:bg-muted/30 transition-colors"
                          >
                            <div className="flex items-center gap-3">
                              <PlayCircle className="h-4 w-4 text-muted-foreground" />
                              <span className="text-sm">{lecture.title}</span>
                            </div>
                            <div className="flex items-center gap-3">
                              <span className="text-xs text-muted-foreground">
                                {lecture.duration} دقيقة
                              </span>
                              {(enrolled || isInstructor) && (
                                <Link href={`/courses/${id}/lectures/${lecture.id}`}>
                                  <Button variant="ghost" size="sm">
                                    مشاهدة
                                    <ArrowRight className="h-3 w-3 mr-1" />
                                  </Button>
                                </Link>
                              )}
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
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
                  (enrolled || isInstructor) ? (
                    <Link href={continueLearningUrl}>
                      <Button className="w-full gap-2" size="lg">
                        <CheckCircle2 className="h-4 w-4" />
                        متابعة التعلم
                      </Button>
                    </Link>
                  ) : course.price === 0 ? (
                    <Button
                      className="w-full gap-2"
                      size="lg"
                      onClick={handleEnroll}
                      disabled={enrollMutation.isPending}
                    >
                      {enrollMutation.isPending ? "جاري التسجيل..." : "سجل الآن مجاناً"}
                      <ArrowRight className="h-4 w-4" />
                    </Button>
                  ) : (
                    <Button
                      className="w-full gap-2"
                      size="lg"
                      onClick={handlePurchase}
                      disabled={purchaseMutation.isPending}
                    >
                      {purchaseMutation.isPending ? "جاري الشراء..." : `شراء - ${course.price} د.م`}
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
                    <span className="text-muted-foreground">الأقسام</span>
                    <span className="font-medium">{course.sections_count ?? 0}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">الطلاب</span>
                    <span className="font-medium">{course.students_count}</span>
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
                  <div className="flex items-center gap-4">
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
                </CardContent>
              </Card>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
