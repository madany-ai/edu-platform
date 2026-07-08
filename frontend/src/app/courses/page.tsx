"use client";

import { useEffect, useState, useCallback } from "react";
import { useSearchParams } from "next/navigation";
import { CourseCard } from "@/components/course-card";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { getCourses, getCategories } from "@/lib/api/courses";
import type { Course, Category } from "@/lib/types";
import { Search, SlidersHorizontal } from "lucide-react";

export default function CoursesPage() {
  const searchParams = useSearchParams();
  const [courses, setCourses] = useState<Course[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState(searchParams.get("search") || "");
  const [activeCategory, setActiveCategory] = useState(searchParams.get("category") || "");

  const fetchCourses = useCallback(async () => {
    setLoading(true);
    try {
      const filters: Record<string, string> = {};
      if (search) filters.search = search;
      if (activeCategory) filters.category = activeCategory;
      const [coursesData, categoriesData] = await Promise.all([
        getCourses(filters),
        getCategories(),
      ]);
      setCourses(coursesData);
      setCategories(categoriesData);
    } finally {
      setLoading(false);
    }
  }, [search, activeCategory]);

  useEffect(() => {
    const timer = setTimeout(fetchCourses, 300);
    return () => clearTimeout(timer);
  }, [fetchCourses]);

  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
      <div className="mb-8">
        <h1 className="text-3xl font-bold mb-2">الدورات التدريبية</h1>
        <p className="text-muted-foreground">
          تصفح مجموعة من أفضل الدورات في مختلف المجالات
        </p>
      </div>

      <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="relative flex-1 max-w-md">
          <Search className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="ابحث عن دورة..."
            className="pr-9"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        <div className="flex items-center gap-2">
          <SlidersHorizontal className="h-4 w-4 text-muted-foreground" />
          <span className="text-sm text-muted-foreground">تصفية</span>
        </div>
      </div>

      <div className="mb-8 flex flex-wrap gap-2">
        <Badge
          variant={activeCategory === "" ? "default" : "outline"}
          className="cursor-pointer"
          onClick={() => setActiveCategory("")}
        >
          الكل
        </Badge>
        {categories.map((cat) => (
          <Badge
            key={cat.slug}
            variant={activeCategory === cat.slug ? "default" : "outline"}
            className="cursor-pointer"
            onClick={() => setActiveCategory(cat.slug)}
          >
            {cat.name}
          </Badge>
        ))}
      </div>

      {loading ? (
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {Array.from({ length: 8 }).map((_, i) => (
            <div key={i} className="space-y-3">
              <Skeleton className="aspect-video rounded-lg" />
              <Skeleton className="h-4 w-1/3" />
              <Skeleton className="h-5 w-3/4" />
              <Skeleton className="h-4 w-1/2" />
              <Skeleton className="h-4 w-full" />
            </div>
          ))}
        </div>
      ) : courses.length === 0 ? (
        <div className="text-center py-20">
          <p className="text-lg text-muted-foreground">لا توجد دورات مطابقة للبحث</p>
        </div>
      ) : (
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {courses.map((course) => (
            <CourseCard
              key={course.id}
              id={course.id}
              title={course.title}
              instructor={course.instructor?.name ?? ""}
              category={course.category?.name ?? ""}
              lessons={course.lessons_count}
              students={course.students_count}
              duration={`${Math.round(course.duration_minutes / 60)} ساعة`}
              price={course.price}
            />
          ))}
        </div>
      )}
    </div>
  );
}
