import { Navbar } from "@/components/layout/navbar";

export default function PlayerLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen flex flex-col bg-background text-on-background">
      <Navbar />
      <main className="flex-grow flex flex-col lg:h-[calc(100vh-4rem)] lg:overflow-hidden min-h-[calc(100vh-4rem)] overflow-auto">
        {children}
      </main>
    </div>
  );
}
