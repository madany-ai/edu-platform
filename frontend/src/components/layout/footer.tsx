import Link from "next/link";
import { ROUTES, APP_NAME } from "@/lib/constants";

const footerLinks = [
  {
    title: "الدورات",
    links: [
      { label: "جميع الدورات", href: ROUTES.COURSES },
      { label: "دورات العلوم", href: `${ROUTES.COURSES}?category=science` },
      { label: "دورات الرياضيات", href: `${ROUTES.COURSES}?category=math` },
    ],
  },
  {
    title: "روابط سريعة",
    links: [
      { label: "الرئيسية", href: ROUTES.HOME },
      { label: "من نحن", href: ROUTES.ABOUT },
      { label: "الأسئلة الشائعة", href: "/faq" },
    ],
  },
  {
    title: "تواصل معنا",
    links: [
      { label: "info@platform.com", href: "mailto:info@platform.com" },
      { label: "+123 456 7890", href: "tel:+1234567890" },
    ],
  },
];

export function Footer() {
  return (
    <footer className="bg-[#141a15] border-t border-[#3b413c] text-muted-foreground">
      <div className="mx-auto max-w-7xl px-4 py-16 lg:px-8">
        <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
          <div>
            <h3 className="mb-4 text-lg font-bold text-foreground">{APP_NAME}</h3>
            <p className="text-sm text-muted-foreground/80 leading-relaxed">
              منصة تعليمية شاملة تقدم دورات متنوعة للطلاب والمعلمين.
            </p>
          </div>
          {footerLinks.map((group) => (
            <div key={group.title}>
              <h4 className="mb-4 text-sm font-bold text-foreground">{group.title}</h4>
              <ul className="space-y-2">
                {group.links.map((link) => (
                  <li key={link.label}>
                    <Link
                      href={link.href}
                      className="text-sm text-muted-foreground transition-colors hover:text-primary"
                    >
                      {link.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
        <div className="mt-8 border-t border-[#3b413c] pt-8 text-center text-xs text-muted-foreground/60">
          © {new Date().getFullYear()} {APP_NAME}. جميع الحقوق محفوظة.
        </div>
      </div>
    </footer>
  );
}
