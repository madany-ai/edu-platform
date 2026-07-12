import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

const guestRoutes = ["/login", "/register", "/forgot-password"];

export default function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;
  const token = request.cookies.get("edu_platform_token")?.value;

  // Note: For full protection, the token should also be checked via API.
  // This middleware provides basic redirect logic. Client-side AuthGuard handles
  // the definitive auth check using localStorage token.

  for (const route of guestRoutes) {
    if (pathname.startsWith(route) && token) {
      return NextResponse.redirect(new URL("/", request.url));
    }
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/((?!_next/static|_next/image|favicon.ico|api).*)"],
};
