"use client";
/* eslint-disable @typescript-eslint/no-explicit-any */

import { useEffect, useRef, useState } from "react";
import { Play, Pause, Maximize, Minimize } from "lucide-react";
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

declare global {
  interface Window {
    YT: any;
    onYouTubeIframeAPIReady: () => void;
  }
}

function YouTubeSecurePlayer({ videoId }: { videoId: string | null }) {
  const containerRef = useRef<HTMLDivElement>(null);
  const wrapperRef = useRef<HTMLDivElement>(null);
  const playerRef = useRef<any>(null);
  const [isPlaying, setIsPlaying] = useState(false);
  const [isHovered, setIsHovered] = useState(false);
  const [currentTime, setCurrentTime] = useState(0);
  const [duration, setDuration] = useState(0);
  const [isApiReady, setIsApiReady] = useState(false);
  const [isFullscreen, setIsFullscreen] = useState(false);

  useEffect(() => {
    // Load YouTube IFrame API
    if (!window.YT) {
      const tag = document.createElement("script");
      tag.src = "https://www.youtube.com/iframe_api";
      const firstScriptTag = document.getElementsByTagName("script")[0];
      if (firstScriptTag && firstScriptTag.parentNode) {
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
      }
      window.onYouTubeIframeAPIReady = () => {
        setIsApiReady(true);
      };
    } else {
      setIsApiReady(true);
    }
  }, []);

  useEffect(() => {
    if (isApiReady && videoId && containerRef.current && !playerRef.current) {
      playerRef.current = new window.YT.Player(containerRef.current, {
        videoId: videoId,
        playerVars: {
          controls: 0,
          disablekb: 1,
          rel: 0,
          modestbranding: 1,
          iv_load_policy: 3,
          fs: 0,
          playsinline: 1
        },
        events: {
          onReady: (event: any) => {
            setDuration(event.target.getDuration());
          },
          onStateChange: (event: any) => {
            if (event.data === window.YT.PlayerState.PLAYING) {
              setIsPlaying(true);
              setDuration(event.target.getDuration());
            } else if (event.data === window.YT.PlayerState.PAUSED || event.data === window.YT.PlayerState.ENDED) {
              setIsPlaying(false);
            }
          }
        }
      });
    }

    return () => {
      if (playerRef.current) {
        playerRef.current.destroy();
        playerRef.current = null;
      }
    };
  }, [isApiReady, videoId]);

  useEffect(() => {
    let interval: NodeJS.Timeout;
    if (isPlaying && playerRef.current && playerRef.current.getCurrentTime) {
      interval = setInterval(() => {
        setCurrentTime(playerRef.current.getCurrentTime());
      }, 500);
    }
    return () => clearInterval(interval);
  }, [isPlaying]);

  useEffect(() => {
    const handleFullscreenChange = () => {
      setIsFullscreen(!!document.fullscreenElement);
    };
    document.addEventListener("fullscreenchange", handleFullscreenChange);
    return () => document.removeEventListener("fullscreenchange", handleFullscreenChange);
  }, []);

  const togglePlay = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (!playerRef.current) return;
    
    if (isPlaying) {
      playerRef.current.pauseVideo();
    } else {
      playerRef.current.playVideo();
    }
  };

  const handleSeek = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newTime = parseFloat(e.target.value);
    setCurrentTime(newTime);
    if (playerRef.current && playerRef.current.seekTo) {
      playerRef.current.seekTo(newTime, true);
    }
  };

  const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
      wrapperRef.current?.requestFullscreen().catch(err => {
        console.error(`Error attempting to enable full-screen mode: ${err.message}`);
      });
    } else {
      document.exitFullscreen();
    }
  };

  const formatTime = (seconds: number) => {
    if (isNaN(seconds)) return "0:00";
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m}:${s < 10 ? '0' : ''}${s}`;
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
      ref={wrapperRef}
      className="w-full h-full relative rounded-lg overflow-hidden bg-black select-none group flex flex-col"
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      onContextMenu={(e) => e.preventDefault()}
    >
      <div className="flex-1 relative w-full h-full" onClick={togglePlay}>
        <div className="absolute inset-0 pointer-events-none scale-[1.05]">
          {/* YT API replaces this div with iframe */}
          <div ref={containerRef} className="w-full h-full" />
        </div>

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

      {/* Custom Control Bar */}
      <div className={`absolute bottom-0 left-0 right-0 z-30 bg-gradient-to-t from-black/90 to-transparent p-4 transition-opacity duration-300 ${isHovered || !isPlaying ? 'opacity-100' : 'opacity-0'}`}>
        <div className="flex items-center gap-4 text-white text-xs font-medium">
          <button onClick={togglePlay} className="hover:text-primary transition-colors focus:outline-none">
            {isPlaying ? <Pause className="h-5 w-5" /> : <Play className="h-5 w-5" />}
          </button>
          
          <span>{formatTime(currentTime)}</span>
          
          <input
            type="range"
            min={0}
            max={duration || 100}
            value={currentTime}
            onChange={handleSeek}
            className="flex-1 h-1.5 bg-white/20 rounded-lg appearance-none cursor-pointer accent-primary"
            style={{ direction: "ltr" }}
          />
          
          <span>{formatTime(duration)}</span>

          <button onClick={toggleFullscreen} className="hover:text-primary transition-colors focus:outline-none ml-2">
            {isFullscreen ? <Minimize className="h-5 w-5" /> : <Maximize className="h-5 w-5" />}
          </button>
        </div>
      </div>
    </div>
  );
}
