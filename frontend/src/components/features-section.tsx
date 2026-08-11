import { Monitor, Trophy, Users, Headphones } from "lucide-react";

const features = [
  {
    icon: Monitor,
    title: "شرح تفاعلي مبسط",
    desc: "شرح وافي لمناهج الرياضيات بأسلوب مبسط يعتمد على الفهم والتطبيق المستمر",
  },
  {
    icon: Trophy,
    title: "تدريب وامتحانات",
    desc: "امتحانات دورية وتدريبات مستمرة على أحدث أفكار المسائل لضمان التفوق",
  },
  {
    icon: Users,
    title: "متابعة مستمرة",
    desc: "متابعة دقيقة مع الطالب وولي الأمر لضمان تقدم المستوى خطوة بخطوة",
  },
  {
    icon: Headphones,
    title: "دعم على مدار الساعة",
    desc: "فريق مساعدين متواجد للرد على أسئلة واستفسارات الطلاب في أي وقت",
  },
];

export function FeaturesSection() {
  return (
    <section className="py-20">
      <div className="mx-auto max-w-7xl px-4 sm:px-6">
        <div className="text-center mb-12">
          <h2 className="text-3xl font-bold mb-2">لماذا تختار مستر حفني محمد؟</h2>
          <p className="text-muted-foreground">مميزات تجعل رحلتك مع الرياضيات أكثر متعة ونجاحاً</p>
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
