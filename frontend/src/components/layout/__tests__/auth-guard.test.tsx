import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen } from "@testing-library/react";
import { AuthGuard } from "../auth-guard";
import { useAuth } from "@/providers/auth-provider";
import { useRouter } from "next/navigation";

vi.mock("@/providers/auth-provider", () => ({
  useAuth: vi.fn(),
}));

vi.mock("next/navigation", () => ({
  useRouter: vi.fn(),
  usePathname: () => "/test",
}));

vi.mock("@/components/shared/loading-spinner", () => ({
  PageLoading: () => <div data-testid="page-loading">Loading...</div>,
}));

describe("AuthGuard", () => {
  const mockPush = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(useRouter).mockReturnValue({
      push: mockPush,
      back: vi.fn(),
      refresh: vi.fn(),
      replace: vi.fn(),
    } as any);
  });

  it("should show loading when auth is loading", () => {
    vi.mocked(useAuth).mockReturnValue({
      isAuthenticated: false,
      loading: true,
    } as any);

    render(
      <AuthGuard requireAuth>
        <div>Protected Content</div>
      </AuthGuard>
    );

    expect(screen.getByTestId("page-loading")).toBeInTheDocument();
    expect(screen.queryByText("Protected Content")).not.toBeInTheDocument();
  });

  it("should show loading when not authenticated and requireAuth is true", () => {
    vi.mocked(useAuth).mockReturnValue({
      isAuthenticated: false,
      loading: false,
    } as any);

    render(
      <AuthGuard requireAuth>
        <div>Protected Content</div>
      </AuthGuard>
    );

    expect(screen.getByTestId("page-loading")).toBeInTheDocument();
    expect(screen.queryByText("Protected Content")).not.toBeInTheDocument();
  });

  it("should render children when authenticated and requireAuth is true", () => {
    vi.mocked(useAuth).mockReturnValue({
      isAuthenticated: true,
      loading: false,
    } as any);

    render(
      <AuthGuard requireAuth>
        <div>Protected Content</div>
      </AuthGuard>
    );

    expect(screen.getByText("Protected Content")).toBeInTheDocument();
    expect(screen.queryByTestId("page-loading")).not.toBeInTheDocument();
  });

  it("should show loading when authenticated and requireGuest is true", () => {
    vi.mocked(useAuth).mockReturnValue({
      isAuthenticated: true,
      loading: false,
    } as any);

    render(
      <AuthGuard requireGuest>
        <div>Guest Content</div>
      </AuthGuard>
    );

    expect(screen.getByTestId("page-loading")).toBeInTheDocument();
    expect(screen.queryByText("Guest Content")).not.toBeInTheDocument();
  });

  it("should render children when not authenticated and requireGuest is true", () => {
    vi.mocked(useAuth).mockReturnValue({
      isAuthenticated: false,
      loading: false,
    } as any);

    render(
      <AuthGuard requireGuest>
        <div>Guest Content</div>
      </AuthGuard>
    );

    expect(screen.getByText("Guest Content")).toBeInTheDocument();
    expect(screen.queryByTestId("page-loading")).not.toBeInTheDocument();
  });
});
