import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Protected paths regex or checks
  const isProtectedRoute = pathname.startsWith('/player') || pathname.startsWith('/dashboard');

  if (isProtectedRoute) {
    // Check if session cookie or xsrf-token exists
    const hasSessionCookie = request.cookies.has('laravel_session') || 
                             request.cookies.has('XSRF-TOKEN') ||
                             Array.from(request.cookies.getAll()).some(c => c.name.includes('session'));

    if (!hasSessionCookie) {
      const loginUrl = new URL('/login', request.url);
      loginUrl.searchParams.set('redirect', pathname);
      return NextResponse.redirect(loginUrl);
    }
  }

  return NextResponse.next();
}

export const config = {
  matcher: ['/player/:path*', '/dashboard/:path*'],
};
