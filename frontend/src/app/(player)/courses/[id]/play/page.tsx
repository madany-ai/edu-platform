"use client";

import { useEffect } from "react";
import { useRouter, useParams } from "next/navigation";
import { useCourse } from "@/hooks/useCourses";
import { useMyEnrollments, useMyEntitlements } from "@/hooks/useEnrollment";
import { PageLoading } from "@/components/shared/loading-spinner";
import { useAuth } from "@/providers/auth-provider";

export default function PlayCourseRedirect() {
  const params = useParams();
  const router = useRouter();
  const { user, loading: authLoading } = useAuth();
  const courseId = params.id as string;

  const { data: courseResponse, isLoading, error } = useCourse(courseId, !!user);
  const { data: enrollmentsData } = useMyEnrollments();
  const { data: entitlements } = useMyEntitlements();

  // Real enrollment = not a synthesized "entitlement-fake-" enrollment
  const hasRealEnrollment = enrollmentsData?.data?.some(
    (e) => (e.course_id === courseId || e.course?.id === courseId) && !String(e.id).startsWith("entitlement-fake-")
  ) ?? false;
  const unlockedLectures = new Set(entitlements?.map((e: any) => e.lecture_id) || []);

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
    
    // Find the first lecture the student has access to
    const allLectures = course.sections?.flatMap(s => s.lectures || []) || [];
    const firstAccessible = allLectures.find(
      (lecture: any) => hasRealEnrollment || unlockedLectures.has(lecture.id)
    );

    if (firstAccessible) {
      router.replace(`/courses/${courseId}/lectures/${firstAccessible.id}`);
    } else {
      router.replace(`/courses/${courseId}`);
    }
  }, [user, authLoading, isLoading, error, courseResponse, courseId, router, hasRealEnrollment, unlockedLectures]);

  return <PageLoading />;
}
