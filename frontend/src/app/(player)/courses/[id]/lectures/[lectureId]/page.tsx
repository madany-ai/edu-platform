"use client";

import { useEffect, useState } from "react";
import { useRouter, useParams } from "next/navigation";
import { useAuth } from "@/providers/auth-provider";
import { PageLoading } from "@/components/shared/loading-spinner";
import { useCourse, useLecture } from "@/hooks/useCourses";
import { ArrowRight, Play, FileText, CheckCircle, Lock, PlayCircle, ArrowLeft, Download, Timer, SignalHigh } from "lucide-react";
import Link from "next/link";
import dynamic from "next/dynamic";
import { cn } from "@/lib/utils";

const VideoPlayer = dynamic(() => import("@/components/video-player"), {
  ssr: false,
  loading: () => (
    <div className="w-full aspect-video bg-black flex items-center justify-center text-white">
      <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-white"></div>
    </div>
  ),
});

export default function LectureViewPage() {
  const { user, loading: authLoading } = useAuth();
  const router = useRouter();
  const params = useParams();
  const lectureId = params.lectureId as string;
  const courseId = params.id as string;

  const [activeTab, setActiveTab] = useState<"overview" | "resources" | "qa">("overview");
  const [openSections, setOpenSections] = useState<Set<string>>(new Set());

  useEffect(() => {
    if (!authLoading && !user) {
      router.push("/login");
    }
  }, [user, authLoading, router]);

  const { data: courseResponse, isLoading: courseLoading } = useCourse(courseId, !!user);
  const { data: lecture, isLoading: lectureLoading, error: lectureError } = useLecture(lectureId, !!user);

  useEffect(() => {
    if (lectureError) {
      router.push(`/courses/${courseId}`);
    }
  }, [lectureError, router, courseId]);

  // Open the section containing the current lecture by default
  useEffect(() => {
    if (courseResponse?.data?.sections && lectureId) {
      const section = courseResponse.data.sections.find(s => 
        s.lectures?.some(l => l.id === lectureId)
      );
      if (section) {
        setOpenSections(prev => {
          const next = new Set(prev);
          next.add(section.id);
          return next;
        });
      }
    }
  }, [courseResponse, lectureId]);

  if (authLoading || lectureLoading || courseLoading) return <PageLoading />;
  if (!lecture || !courseResponse?.data) return null;

  const course = courseResponse.data;

  // Flatten lectures to find next/prev
  const allLectures = course.sections?.flatMap(s => s.lectures || []) || [];
  const currentLectureIndex = allLectures.findIndex(l => l.id === lectureId);
  const prevLecture = currentLectureIndex > 0 ? allLectures[currentLectureIndex - 1] : null;
  const nextLecture = currentLectureIndex < allLectures.length - 1 ? allLectures[currentLectureIndex + 1] : null;

  const toggleSection = (sectionId: string) => {
    setOpenSections(prev => {
      const next = new Set(prev);
      if (next.has(sectionId)) next.delete(sectionId);
      else next.add(sectionId);
      return next;
    });
  };

  return (
    <div className="flex flex-col lg:flex-row w-full h-full">
      {/* Left/Center Area (Main Content) */}
      <div className="flex-grow flex flex-col h-full overflow-y-auto p-4 md:p-6 space-y-6 lg:w-3/4">
        {/* Video Stage */}
        <div className="relative w-full aspect-video bg-black rounded-2xl overflow-hidden shadow-xl border border-white/10 shrink-0">
          {lecture.video?.status === "completed" ? (
            <VideoPlayer 
              lectureId={lecture.id} 
              streamUrl={lecture.video.stream_url} 
              streamType={lecture.video.stream_type} 
              initialTime={lecture.progress?.current_time ? Number(lecture.progress.current_time) : 0}
            />
          ) : lecture.video?.status === "processing" ? (
            <div className="absolute inset-0 flex flex-col items-center justify-center text-white">
              <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-white mb-4" />
              <p className="text-sm font-semibold">جاري معالجة وتشفير الفيديو لضمان الحماية، يرجى الانتظار...</p>
            </div>
          ) : lecture.video?.status === "failed" ? (
            <div className="absolute inset-0 flex items-center justify-center text-red-500">
              <p className="text-sm font-semibold">فشلت عملية معالجة وتشفير الفيديو.</p>
            </div>
          ) : (
            <div className="absolute inset-0 flex flex-col items-center justify-center">
              <PlayCircle className="h-16 w-16 text-muted-foreground mb-2" />
              <p className="text-sm text-muted-foreground">لا يوجد فيديو لهذه المحاضرة بعد</p>
            </div>
          )}
        </div>

        {/* Navigation Buttons */}
        <div className="flex justify-between items-center bg-card rounded-xl p-4 shadow-sm border">
          {prevLecture ? (
            <Link href={`/courses/${courseId}/lectures/${prevLecture.id}`}>
              <button className="flex items-center gap-2 px-4 py-2 rounded-lg border hover:bg-muted transition-colors text-sm font-medium">
                <ArrowRight className="h-4 w-4" />
                الدرس السابق
              </button>
            </Link>
          ) : (
            <div />
          )}
          
          <h1 className="text-lg md:text-xl font-bold text-center mx-4">{lecture.title}</h1>
          
          {nextLecture ? (
            <Link href={`/courses/${courseId}/lectures/${nextLecture.id}`}>
              <button className="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-primary-foreground hover:bg-primary/90 transition-colors text-sm font-medium">
                الدرس التالي
                <ArrowLeft className="h-4 w-4" />
              </button>
            </Link>
          ) : (
            <Link href={`/courses/${courseId}`}>
              <button className="flex items-center gap-2 px-4 py-2 rounded-lg bg-secondary text-secondary-foreground hover:bg-secondary/90 transition-colors text-sm font-medium">
                إنهاء الدورة
                <CheckCircle className="h-4 w-4" />
              </button>
            </Link>
          )}
        </div>

        {/* Tabs Section */}
        <div className="bg-card rounded-2xl border shadow-sm flex flex-col min-h-[400px]">
          <div className="flex border-b px-2 overflow-x-auto">
            <button 
              className={cn("px-6 py-4 text-sm font-medium border-b-2 transition-all whitespace-nowrap", activeTab === "overview" ? "border-primary text-primary" : "border-transparent text-muted-foreground hover:text-foreground")}
              onClick={() => setActiveTab("overview")}
            >
              نظرة عامة
            </button>
            <button 
              className={cn("px-6 py-4 text-sm font-medium border-b-2 transition-all whitespace-nowrap", activeTab === "resources" ? "border-primary text-primary" : "border-transparent text-muted-foreground hover:text-foreground")}
              onClick={() => setActiveTab("resources")}
            >
              المصادر ({lecture.files?.length || 0})
            </button>
            <button 
              className={cn("px-6 py-4 text-sm font-medium border-b-2 transition-all whitespace-nowrap", activeTab === "qa" ? "border-primary text-primary" : "border-transparent text-muted-foreground hover:text-foreground")}
              onClick={() => setActiveTab("qa")}
            >
              الأسئلة والأجوبة
            </button>
          </div>
          
          <div className="p-6">
            {activeTab === "overview" && (
              <div className="space-y-6 animate-in fade-in duration-300">
                <div>
                  <h2 className="text-lg font-bold mb-3">عن هذا الدرس</h2>
                  <p className="text-muted-foreground leading-relaxed whitespace-pre-wrap">
                    {lecture.description || "لا يوجد وصف متاح لهذه المحاضرة."}
                  </p>
                </div>
                <div className="grid grid-cols-2 md:grid-cols-3 gap-4 pt-4 border-t">
                  <div className="p-4 bg-muted/50 rounded-xl flex items-center gap-3">
                    <Timer className="h-5 w-5 text-primary" />
                    <div>
                      <p className="text-xs text-muted-foreground">المدة</p>
                      <p className="text-sm font-medium">{lecture.duration} دقيقة</p>
                    </div>
                  </div>
                  <div className="p-4 bg-muted/50 rounded-xl flex items-center gap-3">
                    <SignalHigh className="h-5 w-5 text-primary" />
                    <div>
                      <p className="text-xs text-muted-foreground">القسم</p>
                      <p className="text-sm font-medium">{lecture.section?.title || "غير محدد"}</p>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {activeTab === "resources" && (
              <div className="space-y-3 animate-in fade-in duration-300">
                {lecture.files && lecture.files.length > 0 ? (
                  lecture.files.map(file => (
                    <a 
                      key={file.id} 
                      href={file.file_path} 
                      target="_blank" 
                      rel="noopener noreferrer"
                      className="flex items-center justify-between p-4 bg-muted/30 rounded-xl hover:bg-muted cursor-pointer transition-colors border hover:border-primary/20 group"
                    >
                      <div className="flex items-center gap-4">
                        <div className="w-10 h-10 bg-primary/10 text-primary rounded-lg flex items-center justify-center">
                          <FileText className="h-5 w-5" />
                        </div>
                        <div>
                          <p className="text-sm font-medium group-hover:text-primary transition-colors">{file.type}</p>
                          <p className="text-xs text-muted-foreground">ملف مرفق</p>
                        </div>
                      </div>
                      <Download className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors" />
                    </a>
                  ))
                ) : (
                  <div className="text-center py-10 text-muted-foreground">
                    <FileText className="h-12 w-12 mx-auto mb-3 opacity-20" />
                    <p>لا توجد ملفات مرفقة لهذا الدرس.</p>
                  </div>
                )}
              </div>
            )}

            {activeTab === "qa" && (
              <div className="animate-in fade-in duration-300">
                <div className="flex gap-4 mb-6">
                  <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold shrink-0">
                    {user?.name?.charAt(0) || "U"}
                  </div>
                  <div className="flex-grow">
                    <textarea 
                      className="w-full bg-muted/30 border rounded-xl p-3 text-sm focus:ring-1 focus:ring-primary focus:border-primary min-h-[100px] transition-all resize-none" 
                      placeholder="اسأل سؤالاً حول هذا الدرس..."
                    />
                    <div className="flex justify-end mt-2">
                      <button className="bg-primary text-primary-foreground px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/90 transition-colors">
                        إرسال السؤال
                      </button>
                    </div>
                  </div>
                </div>
                <div className="text-center py-10 text-muted-foreground border-t">
                  <p className="text-sm">لا توجد أسئلة بعد. كن أول من يسأل!</p>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Right Sidebar: Curriculum */}
      <aside className="lg:w-1/4 w-full bg-card border-r flex flex-col h-full shrink-0">
        <div className="p-4 md:p-6 border-b">
          <Link href={`/courses/${courseId}`} className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground mb-4 transition-colors">
            <ArrowRight className="h-4 w-4" />
            العودة للدورة
          </Link>
          <h3 className="text-lg font-bold text-foreground line-clamp-2" title={course.title}>
            {course.title}
          </h3>
          <div className="mt-4">
            <div className="flex justify-between items-center text-xs mb-2">
              <span className="text-muted-foreground">تقدم الدورة</span>
              <span className="text-primary font-bold">0% مكتمل</span>
            </div>
            <div className="h-1.5 bg-muted rounded-full overflow-hidden">
              <div className="h-full bg-primary transition-all duration-700 w-[0%]"></div>
            </div>
          </div>
        </div>

        <div className="flex-grow overflow-y-auto p-3 space-y-4">
          {course.sections?.map((section, idx) => {
            const isOpen = openSections.has(section.id);
            return (
              <div key={section.id} className="space-y-1">
                <button 
                  onClick={() => toggleSection(section.id)}
                  className="flex items-center justify-between w-full p-2 rounded-lg hover:bg-muted/50 transition-colors group"
                >
                  <span className="text-sm font-bold text-foreground text-right">
                    الوحدة {idx + 1}: {section.title}
                  </span>
                  <ArrowRight className={cn("h-4 w-4 text-muted-foreground transition-transform duration-200", isOpen ? "-rotate-90" : "rotate-180")} />
                </button>
                
                {isOpen && (
                  <div className="space-y-1 px-1 mt-1">
                    {section.lectures?.map((lec, lecIdx) => {
                      const isActive = lec.id === lectureId;
                      // For now, assume all are accessible since we passed CheckEnrollment. 
                      // In a real app, you'd check lecture completion status.
                      const isCompleted = false; 

                      return (
                        <Link key={lec.id} href={`/courses/${courseId}/lectures/${lec.id}`}>
                          <div className={cn(
                            "p-2.5 rounded-xl flex items-center gap-3 transition-all cursor-pointer border",
                            isActive 
                              ? "bg-primary/10 border-primary/20 shadow-sm" 
                              : "bg-card border-transparent hover:border-border hover:bg-muted/30"
                          )}>
                            <div className={cn(
                              "w-7 h-7 rounded-full flex items-center justify-center shrink-0",
                              isActive ? "bg-primary text-primary-foreground animate-pulse" : 
                              isCompleted ? "bg-primary/20 text-primary" : "bg-muted text-muted-foreground"
                            )}>
                              {isActive ? (
                                <Play className="h-3 w-3 ml-0.5 fill-current" />
                              ) : isCompleted ? (
                                <CheckCircle className="h-4 w-4" />
                              ) : (
                                <PlayCircle className="h-4 w-4" />
                              )}
                            </div>
                            <div className="flex-grow min-w-0">
                              <p className={cn(
                                "text-sm truncate",
                                isActive ? "font-bold text-primary" : "text-foreground"
                              )}>
                                {lecIdx + 1}. {lec.title}
                              </p>
                              <p className="text-xs text-muted-foreground mt-0.5">
                                {lec.duration} دقيقة {isActive && "• جاري المشاهدة"}
                              </p>
                            </div>
                          </div>
                        </Link>
                      );
                    })}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </aside>
    </div>
  );
}
