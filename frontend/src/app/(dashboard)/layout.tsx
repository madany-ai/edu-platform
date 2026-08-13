import { Navbar } from "@/components/layout/navbar";
import { Sidebar } from "@/components/layout/sidebar";
import { MobileNav } from "@/components/layout/mobile-nav";
import { AuthGuard } from "@/components/layout/auth-guard";
import { CenterFiltersProvider } from "@/providers/center-filters-provider";

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  return (
    <AuthGuard requireAuth>
      <CenterFiltersProvider>
        <div className="min-h-screen bg-background flex flex-col">
          <Navbar />
          <div className="flex flex-1 flex-col xl:flex-row pb-16 xl:pb-0">
            <Sidebar />
            <main className="flex-1 min-w-0">{children}</main>
            <MobileNav />
          </div>
        </div>
      </CenterFiltersProvider>
    </AuthGuard>
  );
}
