"use client";

import { type ReactNode } from "react";
import { Toaster } from "sonner";
import QueryProvider from "./query-provider";
import ThemeProvider from "./theme-provider";
import { AuthProvider } from "./auth-provider";

export default function RootProviders({ children }: { children: ReactNode }) {
  return (
    <ThemeProvider>
      <QueryProvider>
        <AuthProvider>
          {children}
          <Toaster
            position="top-center"
            dir="rtl"
            richColors
            closeButton
            duration={4000}
            toastOptions={{
              className: "font-sans",
            }}
          />
        </AuthProvider>
      </QueryProvider>
    </ThemeProvider>
  );
}
