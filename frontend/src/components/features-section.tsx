import { Monitor, Trophy, Users, Headphones } from "lucide-react";

const features = [
  {
    icon: Monitor,
    title: "تعلم أونلاين",
    desc: "دروس مسجلة ومباشرة من أفضل المدربين في مختلف المجالات",
  },
  {
    icon: Trophy,
    title: "شهادات معتمدة",
    desc: "احصل على شهادات إتمام معتمدة بعد إنهاء كل دورة تدريبية",
  },
  {
    icon: Users,
    title: "مجتمع تعليمي",
    desc: "انضم لمجتمع من الطلاب والمدرسين للتعاون وتبادل الخبرات",
  },
  {
    icon: Headphones,
    title: "دعم فني",
    desc: "فريق دعم متاح على مدار الساعة للرد على استفساراتك",
  },
];

export function FeaturesSection() {
  return (
    <section className="py-20">
      <div className="mx-auto max-w-7xl px-4 sm:px-6">
        <div className="text-center mb-12">
          <h2 className="text-3xl font-bold mb-2">لماذا تختار منصتنا؟</h2>
          <p className="text-muted-foreground">مميزات تجعل تجربتك التعليمية فريدة</p>
        </div>
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {features.map((f) => (
            <div key={f.title} className="flex flex-col items-center text-center p-6 rounded-xl border bg-card hover:shadow-md transition-shadow">
              <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                <f.icon className="h-6 w-6 text-primary" />
              </div>
              <h3 className="font-semibold mb-2">{f.title}</h3>
              <p className="text-sm text-muted-foreground">{f.desc}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
