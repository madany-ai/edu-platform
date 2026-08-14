import type { NextConfig } from "next";
import withSerwistInit from "@serwist/next";

const withSerwist = withSerwistInit({
  swSrc: "src/app/sw.ts",
  swDest: "public/sw.js",
  // Disable in dev: SW causes infinite request loops with webpack HMR
  disable: process.env.NODE_ENV === "development",
});

const nextConfig: NextConfig = {
  allowedDevOrigins: [
    "*.trycloudflare.com",
    "*.loca.lt",
  ],
  env: {
    NEXT_PUBLIC_API_URL: process.env.NEXT_PUBLIC_API_URL || "/api",
  },
  async rewrites() {
    return [
      {
        source: '/api/:path*',
        destination: 'http://localhost:8000/api/:path*',
      },
      {
        source: '/sanctum/:path*',
        destination: 'http://localhost:8000/sanctum/:path*',
      },
    ];
  },
  async headers() {
    return [
      {
        source: "/:path*",
        headers: [
          { key: "X-Frame-Options", value: "DENY" },
          { key: "X-Content-Type-Options", value: "nosniff" },
          { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
          { key: "Permissions-Policy", value: "camera=(self), microphone=(), geolocation=()" },
          { key: "Content-Security-Policy", value: `default-src 'self'; img-src 'self' data: blob: https:; script-src 'self' 'unsafe-inline' blob: ${process.env.NODE_ENV !== 'production' ? "'unsafe-eval'" : ''} https://challenges.cloudflare.com https://www.youtube.com https://s.ytimg.com; worker-src 'self' blob:; style-src 'self' 'unsafe-inline' https:; connect-src 'self' http://localhost:8000 https:; frame-src 'self' https://challenges.cloudflare.com https://www.youtube.com https://www.youtube-nocookie.com https://*.bunny.net https://iframe.mediadelivery.net; media-src 'self' blob: https:;` },
        ],
      },
    ];
  },
};

export default withSerwist(nextConfig);
