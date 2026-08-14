const getApiUrl = () => {
  return process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";
};

const env = {
  NEXT_PUBLIC_API_URL: getApiUrl(),
  NEXT_PUBLIC_APP_NAME: process.env.NEXT_PUBLIC_APP_NAME || "Mr Hefni Muhammad",
  NEXT_PUBLIC_BUNNY_CDN_HOSTNAME: process.env.NEXT_PUBLIC_BUNNY_CDN_HOSTNAME || "",
  NEXT_PUBLIC_BUNNY_LIBRARY_ID: process.env.NEXT_PUBLIC_BUNNY_LIBRARY_ID || "",
  NEXT_PUBLIC_TURNSTILE_SITE_KEY: process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY || "",
} as const;

export default env;
