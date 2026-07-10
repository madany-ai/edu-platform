"use client";

import { useEffect, useState } from "react";
import { useRouter, useParams } from "next/navigation";
import { useAuth } from "@/contexts/auth-context";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Loader2, ArrowRight, Play, FileText } from "lucide-react";
import Link from "next/link";
import api from "@/lib/api";
import type { Lecture } from "@/lib/types";

export default function LectureViewPage() {
  const { user, loading: authLoading } = useAuth();
  const router = useRouter();
  const params = useParams();
  const [lecture, setLecture] = useState<Lecture | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (authLoading) return;
    if (!user) { router.push("/login"); return; }

    api.get(`/lectures/${params.lectureId}`)
      .then(({ data }) => setLecture(data))
      .catch(() => router.push("/courses"))
      .finally(() => setLoading(false));
  }, [user, authLoading, router, params.lectureId]);

  if (authLoading || loading) {
    return (
      <div className="flex min-h-[60vh] items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!lecture) return null;

  return (
    <div className="mx-auto max-w-5xl px-4 py-10">
      <Link href={`/courses/${params.id}`} className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground mb-6">
        <ArrowRight className="h-4 w-4" />
        العودة للدورة
      </Link>

      <h1 className="text-2xl font-bold mb-2">{lecture.title}</h1>
      {lecture.description && (
        <p className="text-muted-foreground mb-6">{lecture.description}</p>
      )}

      <div className="aspect-video bg-muted rounded-lg flex items-center justify-center mb-8">
        {lecture.video?.bunny_video_id ? (
          <iframe
            src={`https://iframe.mediadelivery.net/embed/${process.env.NEXT_PUBLIC_BUNNY_LIBRARY_ID}/${lecture.video.bunny_video_id}?autoplay=false`}
            className="aspect-video w-full rounded-lg"
            allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
            allowFullScreen
          />
        ) : (
          <div className="text-center">
            <Play className="h-16 w-16 text-muted-foreground mx-auto mb-2" />
            <p className="text-sm text-muted-foreground">لا يوجد فيديو بعد</p>
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
