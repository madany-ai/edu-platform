"use client";

import { useEffect, useState } from "react";
import { useRouter, useParams } from "next/navigation";
import { useAuth } from "@/providers/auth-provider";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { PageLoading } from "@/components/shared/loading-spinner";
import { courseService } from "@/services/course.service";
import type { Lecture } from "@/types";
import { ArrowRight, Play, FileText } from "lucide-react";
import Link from "next/link";
import VideoPlayer from "@/components/video-player";

export default function LectureViewPage() {
  const { user, loading: authLoading } = useAuth();
  const router = useRouter();
  const params = useParams();
  const [lecture, setLecture] = useState<Lecture | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (authLoading) return;
    if (!user) {
      router.push("/login");
      return;
    }

    courseService
      .getLecture(params.lectureId as string)
      .then((data) => setLecture(data))
      .catch(() => router.push("/courses"))
      .finally(() => setLoading(false));
  }, [user, authLoading, router, params.lectureId, params.id]);

  if (authLoading || loading) return <PageLoading />;
  if (!lecture) return null;

  return (
    <div className="mx-auto max-w-5xl px-4 py-10">
      <Link
        href={`/courses/${params.id}`}
        className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground mb-6"
      >
        <ArrowRight className="h-4 w-4" />
        العودة للدورة
      </Link>

      <h1 className="text-2xl font-bold mb-2">{lecture.title}</h1>
      {lecture.description && (
        <p className="text-muted-foreground mb-6">{lecture.description}</p>
      )}

      <div className="aspect-video bg-black rounded-lg overflow-hidden flex items-center justify-center mb-8 relative">
        {lecture.video?.status === "completed" ? (
          <VideoPlayer lectureId={lecture.id} />
        ) : lecture.video?.status === "processing" ? (
          <div className="text-center text-white">
            <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-white mx-auto mb-4" />
            <p className="text-sm font-semibold">جاري معالجة وتشفير الفيديو لضمان الحماية، يرجى الانتظار...</p>
          </div>
        ) : lecture.video?.status === "failed" ? (
          <div className="text-center text-red-500">
            <p className="text-sm font-semibold">فشلت عملية معالجة وتشفير الفيديو.</p>
          </div>
        ) : (
          <div className="text-center">
            <Play className="h-16 w-16 text-muted-foreground mx-auto mb-2" />
            <p className="text-sm text-muted-foreground">لا يوجد فيديو لهذه المحاضرة بعد</p>
          </div>
        )}
      </div>

      {lecture.files && lecture.files.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <FileText className="h-5 w-5" />
              الملفات المرفقة
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            {lecture.files.map((file) => (
              <a
                key={file.id}
                href={file.file_path}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-2 rounded-lg border p-3 hover:bg-muted transition-colors"
              >
                <FileText className="h-4 w-4" />
                <span className="text-sm">{file.type}</span>
              </a>
            ))}
          </CardContent>
        </Card>
      )}
    </div>
  );
}
