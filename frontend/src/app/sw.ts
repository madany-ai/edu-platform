import { defaultCache } from "@serwist/next/worker";
import { Serwist } from "serwist";
import type { PrecacheEntry, SerwistGlobalConfig } from "serwist";

declare global {
  interface WorkerGlobalScope extends SerwistGlobalConfig {
    __SW_MANIFEST: (PrecacheEntry | string)[] | undefined;
  }
}

// ts-ignore all service-worker-specific globals that are not in the default TS lib
const serwist = new Serwist({
  precacheEntries: (self as any).__SW_MANIFEST,
  skipWaiting: true,
  clientsClaim: true,
  navigationPreload: true,
  runtimeCaching: defaultCache,
});

serwist.addEventListeners();

// Push Notifications
(self as any).addEventListener("push", function (event: any) {
  if (event.data) {
    const data = event.data.json();
    const options = {
      body: data.body as string,
      icon: (data.icon as string) || "/icons/icon-192x192.png",
      badge: "/icons/icon-192x192.png",
      vibrate: [100, 50, 100],
      data: {
        dateOfArrival: Date.now(),
        primaryKey: "2",
      },
    };
    event.waitUntil((self as any).registration.showNotification(data.title, options));
  }
});

(self as any).addEventListener("notificationclick", function (event: any) {
  console.log("Notification click received.");
  event.notification.close();
  event.waitUntil((self as any).clients.openWindow("/dashboard"));
});
