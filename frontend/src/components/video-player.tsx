"use client";
/* eslint-disable @typescript-eslint/no-explicit-any */

import { useEffect, useRef } from "react";
import videojs from "video.js";
import "video.js/dist/video-js.css";
import { STORAGE_KEYS } from "@/lib/constants";
import api from "@/services/api.client";

interface VideoPlayerProps {
  lectureId: string;
  streamUrl: string;
  streamType: string;
  initialTime?: number;
}

export default function VideoPlayer({ lectureId, streamUrl, streamType, initialTime = 0 }: VideoPlayerProps) {
  const videoRef = useRef<HTMLDivElement | null>(null);
  const playerRef = useRef<ReturnType<typeof videojs> | null>(null);
  const progressTimerRef = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    if (!videoRef.current) return;

    // Create video element
    const videoElement = document.createElement("video");
    videoElement.className = "video-js vjs-big-play-centered w-full h-full rounded-lg";
    // Client-side protections: disable picture-in-picture and remote playback
    videoElement.setAttribute("disablePictureInPicture", "true");
    videoElement.setAttribute("x-webkit-airplay", "deny");
    videoRef.current.appendChild(videoElement);

    // Setup token injection for HLS stream requests (if using HLS)
    const token = typeof window !== "undefined" ? localStorage.getItem(STORAGE_KEYS.TOKEN) : null;
    
    if (streamType === "application/x-mpegURL") {
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
    }

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
          type: streamType,
        },
      ],
      controlBar: {
        pictureInPictureToggle: false,
      },
    });

    playerRef.current = player;

    // Seek to last watched time once player is ready and metadata is loaded
    player.ready(() => {
      if (initialTime > 0) {
        player.currentTime(initialTime);
      }
    });

    // Helper to send progress updates
    const reportProgress = async (currentTime: number, isCompleted: boolean) => {
      try {
        await api.post(`/lectures/${lectureId}/progress`, {
          current_time: currentTime,
          is_completed: isCompleted,
        });
      } catch (err) {
        console.error("Failed to save watch progress:", err);
      }
    };

    // Periodic 20-second progress tracking heartbeat
    progressTimerRef.current = setInterval(() => {
      if (player && !player.paused()) {
        const currentTime = player.currentTime() || 0;
        const duration = player.duration() || 0;
        // Mark as completed if student watched > 90% of the video duration
        const isCompleted = duration > 0 && (currentTime / duration) >= 0.9;
        reportProgress(currentTime, isCompleted);
      }
    }, 20000);

    // Track completion immediately when video ends
    player.on("ended", () => {
      const duration = player.duration() || 0;
      reportProgress(duration, true);
    });

    // Disable right click context menu on player elements
    const handleContextMenu = (e: Event) => {
      e.preventDefault();
    };

    const playerEl = player.el();
    if (playerEl) {
      playerEl.addEventListener("contextmenu", handleContextMenu);
    }

    return () => {
      if (progressTimerRef.current) {
        clearInterval(progressTimerRef.current);
      }
      if (playerEl) {
        playerEl.removeEventListener("contextmenu", handleContextMenu);
      }
      if (player) {
        player.dispose();
      }
    };
  }, [lectureId, streamUrl, streamType, initialTime]);

  return (
    <div className="w-full relative aspect-video" ref={videoRef} />
  );
}
