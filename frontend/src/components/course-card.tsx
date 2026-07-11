import Link from "next/link";
import { Card, CardContent, CardFooter, CardHeader } from "@/components/ui/card";
import { BookOpen, Users } from "lucide-react";

interface CourseCardProps {
  id: string;
  title: string;
  instructor: string;
  category: string;
  lessons: number;
  students: number;
  price: number;
  image?: string;
}

export function CourseCard({ id, title, instructor, category, lessons, students, price, image }: CourseCardProps) {
  return (
    <Link href={`/courses/${id}`}>
      <Card className="group overflow-hidden transition-all hover:shadow-lg hover:-translate-y-1">
        <div className="aspect-video bg-gradient-to-br from-primary/10 to-primary/5 flex items-center justify-center">
          {image ? (
            <img src={image} alt={title} className="h-full w-full object-cover" />
          ) : (
            <BookOpen className="h-12 w-12 text-primary/40" />
          )}
        </div>
        <CardHeader className="p-4 pb-0">
          <div className="flex items-center gap-2 mb-2">
            <span className="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary">
              {category}
            </span>
          </div>
          <h3 className="font-semibold line-clamp-2 group-hover:text-primary transition-colors">
            {title}
          </h3>
          <p className="text-sm text-muted-foreground">{instructor}</p>
        </CardHeader>
        <CardContent className="p-4 pt-3">
          <div className="flex items-center gap-4 text-xs text-muted-foreground">
            <span className="flex items-center gap-1">
              <BookOpen className="h-3.5 w-3.5" />
              {lessons} أقسام
            </span>
            <span className="flex items-center gap-1">
              <Users className="h-3.5 w-3.5" />
              {students}
            </span>
          </div>
        </CardContent>
        <CardFooter className="p-4 pt-0 flex items-center justify-between">
          <span className="text-lg font-bold text-primary">
            {price === 0 ? "مجاني" : `${price} د.م`}
          </span>
        </CardFooter>
      </Card>
    </Link>
  );
}
