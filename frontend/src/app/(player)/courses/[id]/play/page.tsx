"use client";

import { useEffect } from "react";
import { useRouter, useParams } from "next/navigation";
import { useCourse } from "@/hooks/useCourses";
import { PageLoading } from "@/components/shared/loading-spinner";
import { useAuth } from "@/providers/auth-provider";

export default function PlayCourseRedirect() {
  const params = useParams();
  const router = useRouter();
  const { user, loading: authLoading } = useAuth();
  const courseId = params.id as string;

  const { data: courseResponse, isLoading, error } = useCourse(courseId, !!user);

  useEffect(() => {
    if (authLoading) return;
    if (!user) {
      router.push("/login");
      return;
    }

    if (isLoading) return;

    if (error || !courseResponse?.data) {
      router.push(`/courses/${courseId}`);
      return;
    }

    const course = courseResponse.data;
    
    // In a real implementation, you would check student_activities for the last watched lecture.
    // For now, we redirect to the first lecture of the first section.
    const firstLectureId = course.sections?.[0]?.lectures?.[0]?.id;

    if (firstLectureId) {
      router.replace(`/courses/${courseId}/lectures/${firstLectureId}`);
    } else {
      // If there are no lectures, fallback to the course details page
      router.replace(`/courses/${courseId}`);
    }
  }, [user, authLoading, isLoading, error, courseResponse, courseId, router]);

  return <PageLoading />;
}
