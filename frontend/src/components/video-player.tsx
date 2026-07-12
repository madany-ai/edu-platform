"use client";
/* eslint-disable @typescript-eslint/no-explicit-any */

import { useEffect, useRef, useState } from "react";
import { Play, Pause } from "lucide-react";
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

  // Handle YouTube links natively
  if (streamType === "video/youtube" || streamUrl.includes("youtube.com") || streamUrl.includes("youtu.be")) {
    const videoIdMatch = streamUrl.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))((\w|-){11})/);
    const videoId = videoIdMatch ? videoIdMatch[1] : null;
    
    return <YouTubeSecurePlayer videoId={videoId} />;
  }

  return (
    <div className="w-full h-full relative" ref={videoRef} />
  );
}

function YouTubeSecurePlayer({ videoId }: { videoId: string | null }) {
  const iframeRef = useRef<HTMLIFrameElement>(null);
  const [isPlaying, setIsPlaying] = useState(false);
  const [isHovered, setIsHovered] = useState(false);

  const togglePlay = (e: React.MouseEvent) => {
    e.stopPropagation();
    const iframe = iframeRef.current;
    if (!iframe || !iframe.contentWindow || !videoId) return;

    const command = isPlaying ? "pauseVideo" : "playVideo";
    iframe.contentWindow.postMessage(
      JSON.stringify({ event: "command", func: command, args: [] }),
      "*"
    );
    setIsPlaying(!isPlaying);
  };

  if (!videoId) {
    return (
      <div className="w-full h-full flex items-center justify-center bg-black text-white text-sm">
        رابط الفيديو غير صالح
      </div>
    );
  }

  return (
    <div 
      className="w-full h-full relative rounded-lg overflow-hidden bg-black select-none group"
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      onClick={togglePlay}
      onContextMenu={(e) => e.preventDefault()}
    >
      {/* YouTube Iframe with pointer-events-none to prevent direct clicks and context menu */}
      <iframe
        ref={iframeRef}
        className="w-full h-full absolute top-0 left-0 pointer-events-none scale-[1.05]"
        src={`https://www.youtube.com/embed/${videoId}?enablejsapi=1&controls=0&rel=0&modestbranding=1&disablekb=1&fs=0&iv_load_policy=3`}
        title="YouTube video player"
        frameBorder="0"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
      ></iframe>

      {/* Transparent Click Overlay */}
      <div className="absolute inset-0 z-10 bg-transparent cursor-pointer" />

      {/* Play/Pause HUD overlay */}
      <div className={`absolute inset-0 z-20 flex items-center justify-center bg-black/40 transition-opacity duration-300 ${(!isPlaying || isHovered) ? "opacity-100" : "opacity-0 pointer-events-none"}`}>
        <div className="w-16 h-16 rounded-full bg-primary/95 text-primary-foreground flex items-center justify-center shadow-lg transition-transform active:scale-95 hover:bg-primary">
          {isPlaying ? (
            <Pause className="h-8 w-8 fill-current" />
          ) : (
            <Play className="h-8 w-8 fill-current ml-1" />
          )}
        </div>
      </div>
    </div>
  );
}
