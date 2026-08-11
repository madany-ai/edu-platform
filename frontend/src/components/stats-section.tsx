import { GraduationCap, BookOpen, Users, Award } from "lucide-react";

const stats = [
  { icon: Users, value: "5K+", label: "طالب وطالبة" },
  { icon: BookOpen, value: "100+", label: "محاضرة مسجلة وتفاعلية" },
  { icon: GraduationCap, value: "50+", label: "طالب من الأوائل" },
  { icon: Award, value: "99%", label: "نسبة النجاح" },
];

export function StatsSection() {
  return (
    <section className="bg-primary py-16 text-primary-foreground">
      <div className="mx-auto max-w-7xl px-4 sm:px-6">
        <div className="grid grid-cols-2 gap-8 md:grid-cols-4">
          {stats.map((stat) => (
            <div key={stat.label} className="flex flex-col items-center gap-2 text-center">
              <stat.icon className="h-8 w-8 opacity-80" />
              <span className="text-3xl font-bold">{stat.value}</span>
              <span className="text-sm opacity-80">{stat.label}</span>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
