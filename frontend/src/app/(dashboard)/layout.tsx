import { Navbar } from "@/components/layout/navbar";
import { Sidebar } from "@/components/layout/sidebar";
import { MobileNav } from "@/components/layout/mobile-nav";
import { AuthGuard } from "@/components/layout/auth-guard";

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  return (
    <AuthGuard requireAuth>
      <div className="min-h-screen bg-background flex flex-col">
        <Navbar />
        <div className="flex flex-1 flex-col lg:flex-row pb-16 lg:pb-0">
          <Sidebar />
          <main className="flex-1">{children}</main>
          <MobileNav />
        </div>
      </div>
    </AuthGuard>
  );
}
