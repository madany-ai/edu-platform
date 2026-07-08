import { GraduationCap } from "lucide-react";
import Link from "next/link";

export function Footer() {
  return (
    <footer className="border-t bg-muted/50">
      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <div className="flex items-center gap-2 font-bold text-lg mb-4">
              <GraduationCap className="h-5 w-5 text-primary" />
              المنصة التعليمية
            </div>
            <p className="text-sm text-muted-foreground">
              منصة تعليمية متكاملة تهدف إلى تقديم محتوى تعليمي عالي الجودة في مختلف المجالات.
            </p>
          </div>
          <div>
            <h3 className="font-semibold mb-3">دورات</h3>
            <ul className="space-y-2 text-sm text-muted-foreground">
              <li><Link href="/courses" className="hover:text-foreground transition-colors">جميع الدورات</Link></li>
              <li><Link href="/courses?category=programming" className="hover:text-foreground transition-colors">برمجة</Link></li>
              <li><Link href="/courses?category=design" className="hover:text-foreground transition-colors">تصميم</Link></li>
              <li><Link href="/courses?category=marketing" className="hover:text-foreground transition-colors">تسويق</Link></li>
            </ul>
          </div>
          <div>
            <h3 className="font-semibold mb-3">روابط سريعة</h3>
            <ul className="space-y-2 text-sm text-muted-foreground">
              <li><Link href="/about" className="hover:text-foreground transition-colors">عن المنصة</Link></li>
              <li><Link href="/contact" className="hover:text-foreground transition-colors">اتصل بنا</Link></li>
              <li><Link href="/faq" className="hover:text-foreground transition-colors">الأسئلة الشائعة</Link></li>
              <li><Link href="/policy" className="hover:text-foreground transition-colors">الشروط والأحكام</Link></li>
            </ul>
          </div>
          <div>
            <h3 className="font-semibold mb-3">تواصل معنا</h3>
            <ul className="space-y-2 text-sm text-muted-foreground">
              <li>البريد: info@platform.com</li>
              <li>الهاتف: +123 456 7890</li>
            </ul>
          </div>
        </div>
        <div className="mt-8 border-t pt-6 text-center text-sm text-muted-foreground">
          &copy; {new Date().getFullYear()} المنصة التعليمية. جميع الحقوق محفوظة.
        </div>
      </div>
    </footer>
  );
}
