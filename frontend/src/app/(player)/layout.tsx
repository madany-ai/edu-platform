import { Navbar } from "@/components/layout/navbar";

export default function PlayerLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen flex flex-col bg-background text-on-background">
      <Navbar />
      <main className="flex-grow flex flex-col h-[calc(100vh-4rem)] overflow-hidden">
        {children}
      </main>
    </div>
  );
}
