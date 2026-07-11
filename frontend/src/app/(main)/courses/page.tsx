"use client";

import { Suspense, useState } from "react";
import { useSearchParams } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { CourseCard } from "@/components/course-card";
import { SearchInput } from "@/components/shared/search-input";
import { CourseCardSkeleton } from "@/components/shared/page-skeleton";
import { EmptyState } from "@/components/shared/empty-state";
import { PageHeader } from "@/components/shared/page-header";
import { courseService } from "@/services/course.service";
import { useDebounce } from "@/hooks/use-debounce";

function CoursesContent() {
  const searchParams = useSearchParams();
  const [search, setSearch] = useState(searchParams.get("search") || "");
  const debouncedSearch = useDebounce(search);

  const { data, isLoading } = useQuery({
    queryKey: ["courses", debouncedSearch],
    queryFn: () =>
      courseService.getAll(debouncedSearch ? { search: debouncedSearch } : undefined),
  });

  const courses = data?.data ?? [];

  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <PageHeader
        title="الدورات التدريبية"
        description="تصفح مجموعة من أفضل الدورات في مختلف المجالات"
      />

      <div className="mb-8">
        <SearchInput
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          onClear={() => setSearch("")}
          showClear={search.length > 0}
          placeholder="ابحث عن دورة..."
          className="max-w-md"
        />
      </div>

      {isLoading ? (
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {Array.from({ length: 8 }).map((_, i) => (
            <CourseCardSkeleton key={i} />
          ))}
        </div>
      ) : courses.length === 0 ? (
        <EmptyState
          title="لا توجد دورات مطابقة للبحث"
          description="جرب تغيير كلمات البحث أو تصفح جميع الدورات"
        />
      ) : (
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {courses.map((course) => (
            <CourseCard
              key={course.id}
              id={course.id}
              title={course.title}
              instructor={course.instructor?.name ?? ""}
              category=""
              lessons={course.sections_count ?? 0}
              students={course.students_count ?? 0}
              price={course.price}
            />
          ))}
        </div>
      )}
    </div>
  );
}

export default function CoursesPage() {
  return (
    <Suspense
      fallback={
        <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {Array.from({ length: 8 }).map((_, i) => (
              <CourseCardSkeleton key={i} />
            ))}
          </div>
        </div>
      }
    >
      <CoursesContent />
    </Suspense>
  );
}
