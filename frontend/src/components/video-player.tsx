"use client";
/* eslint-disable @typescript-eslint/no-explicit-any */

import { useEffect, useRef } from "react";
import videojs from "video.js";
import "video.js/dist/video-js.css";
import env from "@/config/env";
import { STORAGE_KEYS } from "@/lib/constants";

interface VideoPlayerProps {
  lectureId: string;
  onHeartbeat?: () => void;
}

export default function VideoPlayer({ lectureId, onHeartbeat }: VideoPlayerProps) {
  const videoRef = useRef<HTMLDivElement | null>(null);
  const playerRef = useRef<ReturnType<typeof videojs> | null>(null);

  useEffect(() => {
    if (!videoRef.current) return;

    // Create video element
    const videoElement = document.createElement("video");
    videoElement.className = "video-js vjs-big-play-centered w-full h-full rounded-lg";
    // Client-side protections: disable picture-in-picture and remote playback
    videoElement.setAttribute("disablePictureInPicture", "true");
    videoElement.setAttribute("x-webkit-airplay", "deny");
    videoRef.current.appendChild(videoElement);

    // Setup HLS authorization header injection using video.js hooks
    const token = typeof window !== "undefined" ? localStorage.getItem(STORAGE_KEYS.TOKEN) : null;
    
    // Register the Vhs beforeRequest hook to append Authorization header
    const vhs = (videojs as any).Vhs;
    if (vhs && vhs.xhr && typeof vhs.xhr.beforeRequest === "function") {
      const originalBeforeRequest = vhs.xhr.beforeRequest;
      vhs.xhr.beforeRequest = function (options: any) {
        const opt = originalBeforeRequest ? originalBeforeRequest(options) : options;
        if (token) {
          opt.headers = opt.headers || {};
          opt.headers.Authorization = `Bearer ${token}`;
        }
        return opt;
      };
    } else {
      (videojs as any).hook("beforeRequest", (options: any) => {
        if (token) {
          options.headers = options.headers || {};
          options.headers.Authorization = `Bearer ${token}`;
        }
        return options;
      });
    }

    const streamUrl = `${env.NEXT_PUBLIC_API_URL}/lectures/${lectureId}/stream`;

    const player = videojs(videoElement, {
      autoplay: false,
      controls: true,
      responsive: true,
      fluid: true,
      html5: {
        vhs: {
          withCredentials: true,
        },
      },
      sources: [
        {
          src: streamUrl,
          type: "application/x-mpegURL",
        },
      ],
      controlBar: {
        pictureInPictureToggle: false,
      },
    });

    playerRef.current = player;

    // 20-second heartbeat check
    const heartbeatInterval = setInterval(() => {
      if (player && !player.paused()) {
        if (onHeartbeat) {
          onHeartbeat();
        }
      }
    }, 20000);

    // Disable right click on video element container
    const handleContextMenu = (e: Event) => {
      e.preventDefault();
    };

    const playerEl = player.el();
    if (playerEl) {
      playerEl.addEventListener("contextmenu", handleContextMenu);
    }

    return () => {
      clearInterval(heartbeatInterval);
      if (playerEl) {
        playerEl.removeEventListener("contextmenu", handleContextMenu);
      }
      if (player) {
        player.dispose();
      }
    };
  }, [lectureId, onHeartbeat]);

  return (
    <div className="w-full relative aspect-video" ref={videoRef} />
  );
}
