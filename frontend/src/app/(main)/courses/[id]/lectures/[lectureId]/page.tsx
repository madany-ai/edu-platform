"use client";

import { useEffect } from "react";
import { useParams, useRouter } from "next/navigation";
import { PageLoading } from "@/components/shared/loading-spinner";

export default function CourseLectureRedirectPage() {
  const { id, lectureId } = useParams<{ id: string; lectureId: string }>();
  const router = useRouter();

  useEffect(() => {
    if (id && lectureId) {
      router.replace(`/(player)/courses/${id}/lectures/${lectureId}`);
    }
  }, [id, lectureId, router]);

  return <PageLoading />;
}
