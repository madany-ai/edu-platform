import { Navbar } from "@/components/layout/navbar";
import { Sidebar } from "@/components/layout/sidebar";
import { MobileNav } from "@/components/layout/mobile-nav";
import { AuthGuard } from "@/components/layout/auth-guard";
import { CenterFiltersProvider } from "@/providers/center-filters-provider";

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  return (
    <AuthGuard requireAuth>
      <CenterFiltersProvider>
        <div className="min-h-screen w-full bg-background flex flex-col overflow-x-hidden">
          <Navbar />
          <div className="grid grid-cols-1 xl:grid-cols-[16rem_1fr] w-full pb-16 xl:pb-0">
            <Sidebar className="xl:col-start-1" />
            <main className="min-w-0 w-full xl:col-start-2 overflow-x-hidden">{children}</main>
            <MobileNav />
          </div>
        </div>
      </CenterFiltersProvider>
    </AuthGuard>
  );
}
