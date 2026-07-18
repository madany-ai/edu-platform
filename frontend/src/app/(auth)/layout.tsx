import { AuthGuard } from "@/components/layout/auth-guard";

export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <AuthGuard requireGuest>
      <div className="flex min-h-screen items-center justify-center bg-surface-container-low px-4">
        <div className="w-full max-w-lg">{children}</div>
      </div>
    </AuthGuard>
  );
}
