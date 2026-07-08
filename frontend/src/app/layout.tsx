import type { Metadata } from "next";
import { Noto_Sans_Arabic } from "next/font/google";
import { AuthProvider } from "@/contexts/auth-context";
import "./globals.css";

const notoSansArabic = Noto_Sans_Arabic({
  subsets: ["arabic"],
  variable: "--font-sans",
});

export const metadata: Metadata = {
  title: "المنصة التعليمية",
  description: "منصة التعليم التفاعلية",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="ar"
      dir="rtl"
      className={`${notoSansArabic.variable} h-full antialiased`}
    >
      <body className="min-h-full font-sans bg-background text-foreground">
        <AuthProvider>{children}</AuthProvider>
      </body>
    </html>
  );
}
