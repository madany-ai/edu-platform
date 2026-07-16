const env = {
  NEXT_PUBLIC_API_URL: process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api",
  NEXT_PUBLIC_APP_NAME: process.env.NEXT_PUBLIC_APP_NAME || "مختبر العلوم الرقمي",
  NEXT_PUBLIC_BUNNY_CDN_HOSTNAME: process.env.NEXT_PUBLIC_BUNNY_CDN_HOSTNAME || "",
  NEXT_PUBLIC_BUNNY_LIBRARY_ID: process.env.NEXT_PUBLIC_BUNNY_LIBRARY_ID || "",
} as const;

export default env;
