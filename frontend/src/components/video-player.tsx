"use client";
/* eslint-disable @typescript-eslint/no-explicit-any */

import { useEffect, useRef, useState, useCallback } from "react";
import { Play, Pause, Maximize, Minimize, Volume2, VolumeX } from "lucide-react";
import Hls from "hls.js";
import { STORAGE_KEYS } from "@/lib/constants";
import api from "@/services/api.client";
import { useAuth } from "@/providers/auth-provider";

interface VideoPlayerProps {
  lectureId: string;
  streamUrl: string;
  streamType: string;
  initialTime?: number;
}

export default function VideoPlayer({ lectureId, streamUrl, streamType, initialTime = 0 }: VideoPlayerProps) {
  // ── YouTube detection ──
  if (streamType === "video/youtube" || streamUrl.includes("youtube.com") || streamUrl.includes("youtu.be")) {
    const videoIdMatch = streamUrl.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))((\w|-){11})/);
    return <YouTubeSecurePlayer videoId={videoIdMatch ? videoIdMatch[1] : null} />;
  }

  // ── Bunny Stream — proxied embed with watermark overlay ──
  if (
    streamType === "video/bunny-hls" ||
    (streamType === "application/x-mpegURL" && streamUrl.includes("mediadelivery.net"))
  ) {
    return <BunnyEmbedPlayer embedUrl={streamUrl} />;
  }

  return <HLSPlayer lectureId={lectureId} streamUrl={streamUrl} streamType={streamType} initialTime={initialTime} />;
}

/* ════════════════════════════════════════════════════════════════════════════
   Bunny Stream Embed Player — with watermark overlay
   ════════════════════════════════════════════════════════════════════════════ */

const WATERMARK_POSITIONS = [
  { top: '8%',  left: '5%'  },
  { top: '8%',  right: '5%' },
  { top: '50%', left: '5%'  },
  { top: '50%', right: '5%' },
  { top: '80%', left: '5%'  },
  { top: '80%', right: '5%' },
  { top: '30%', left: '30%' },
];

function BunnyEmbedPlayer({ embedUrl }: { embedUrl: string }) {
  const { user } = useAuth();
  const iframeRef = useRef<HTMLIFrameElement>(null);
  const [watermarkPos, setWatermarkPos] = useState<{top?:string;left?:string;right?:string}>(WATERMARK_POSITIONS[0]);

  useEffect(() => {
    const blockContextMenu = (e: Event) => e.preventDefault();
    const iframe = iframeRef.current;
    if (iframe) {
      iframe.addEventListener("contextmenu", blockContextMenu);
      return () => iframe.removeEventListener("contextmenu", blockContextMenu);
    }
  }, []);

  // ── Fullscreen orientation lock ──
  useEffect(() => {
    const onChange = () => {
      const isFs = !!document.fullscreenElement;
      const orientation = (screen as any).orientation;
      if (isFs) {
        if (orientation && orientation.lock) {
          orientation.lock("landscape").catch(() => {});
        }
      } else {
        if (orientation && orientation.unlock) {
          orientation.unlock();
        }
      }
    };
    document.addEventListener("fullscreenchange", onChange);
    return () => document.removeEventListener("fullscreenchange", onChange);
  }, []);

  // Move watermark to random position every 30s
  useEffect(() => {
    const rotate = () => {
      const next = WATERMARK_POSITIONS[Math.floor(Math.random() * WATERMARK_POSITIONS.length)];
      setWatermarkPos(next);
    };
    const t = setInterval(rotate, 30_000);
    return () => clearInterval(t);
  }, []);

  const watermarkText = user ? `${user.name} • ${user.email}` : 'محمي';

  return (
    <div
      className="relative w-full h-full bg-black rounded-lg overflow-hidden"
      onContextMenu={(e) => e.preventDefault()}
      style={{ isolation: 'isolate' }}
    >
      <iframe
        ref={iframeRef}
        src={embedUrl}
        className="absolute inset-0 w-full h-full border-0"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        allowFullScreen
        title="Video Player"
        onContextMenu={(e) => e.preventDefault()}
        style={{ zIndex: 1 }}
      />

      {/* Watermark — last in DOM, highest z-index, always paints on top */}
      <div
        className="absolute pointer-events-none transition-all duration-1000"
        style={{ ...watermarkPos, zIndex: 9999 }}
      >
        <span
          style={{
            display: 'inline-block',
            fontSize: 'clamp(10px, 1.2vw, 13px)',
            color: 'rgba(255,255,255,0.9)',
            background: 'rgba(0,0,0,0.4)',
            padding: '2px 8px',
            borderRadius: '4px',
            userSelect: 'none',
            WebkitUserSelect: 'none',
            whiteSpace: 'nowrap',
            direction: 'ltr',
            fontFamily: 'monospace',
            letterSpacing: '0.04em',
            pointerEvents: 'none',
          }}
        >
          {watermarkText}
        </span>
      </div>
    </div>
  );
}




/* ════════════════════════════════════════════════════════════════════════════
   HLS Player — Netflix-style with security hardening
   ════════════════════════════════════════════════════════════════════════════ */



function HLSPlayer({ lectureId, streamUrl, streamType, initialTime = 0 }: VideoPlayerProps) {
  const { user } = useAuth();
  const containerRef = useRef<HTMLDivElement>(null);
  const videoRef = useRef<HTMLVideoElement | null>(null);
  const hlsRef = useRef<Hls | null>(null);
  const progressTimerRef = useRef<NodeJS.Timeout | null>(null);
  const hideControlsTimerRef = useRef<NodeJS.Timeout | null>(null);
  const watermarkTimerRef = useRef<NodeJS.Timeout | null>(null);
  const playPromiseRef = useRef<Promise<void> | null>(null);
  const isTogglingRef = useRef(false);

  const [isPlaying, setIsPlaying] = useState(false);
  const [isMuted, setIsMuted] = useState(false);
  const [currentTime, setCurrentTime] = useState(0);
  const [duration, setDuration] = useState(0);
  const [buffered, setBuffered] = useState(0);
  const [volume, setVolume] = useState(1);
  const [isFullscreen, setIsFullscreen] = useState(false);
  const [showControls, setShowControls] = useState(true);
  const [isLoaded, setIsLoaded] = useState(false);
  const [qualityLevels, setQualityLevels] = useState<{level: number, height: number}[]>([]);
  const [selectedQuality, setSelectedQuality] = useState<number>(-1); // -1 = auto
  const [watermarkPos, setWatermarkPos] = useState<{top?:string;left?:string;right?:string}>(WATERMARK_POSITIONS[0]);

  // ── Progress reporter ──
  const reportProgress = useCallback(async (time: number, completed: boolean) => {
    try {
      await api.post(`/lectures/${lectureId}/progress`, {
        current_time: time,
        is_completed: completed,
      });
    } catch { /* silent */ }
  }, [lectureId]);

  // ── Initialize HLS.js + security hardening ──
  useEffect(() => {
    const video = videoRef.current;
    if (!video) return;

    const handleLoadedMetadata = () => {
      setIsLoaded(true);
      if (initialTime > 0) video.currentTime = initialTime;
    };

    // Determine source
    const isHLS = streamType === "application/x-mpegURL" || streamType === "hls" || streamType === "video/bunny-hls";
    const isMP4 = streamType === "video/mp4" || streamUrl.endsWith(".mp4");

    if (isHLS && Hls.isSupported()) {
      // Inject Bearer token so proxy can validate user in addition to HMAC
      const authToken = typeof window !== "undefined"
        ? localStorage.getItem(STORAGE_KEYS.TOKEN) || ""
        : "";

      const hls = new Hls({
        enableWorker: true,
        lowLatencyMode: false,
        backBufferLength: 90,
        maxBufferLength: 30,
        startLevel: -1, // auto quality
        xhrSetup: (xhr: XMLHttpRequest, url: string) => {
          const apiHost = process.env.NEXT_PUBLIC_API_URL || "";
          const isLocalApi = url.startsWith("/") || (apiHost && url.startsWith(apiHost));
          if (authToken && isLocalApi) {
            xhr.setRequestHeader("Authorization", `Bearer ${authToken}`);
          }
        },

      });

      hls.loadSource(streamUrl);
      hls.attachMedia(video);


      hls.on(Hls.Events.MANIFEST_PARSED, (_e, data) => {
        const levels = data.levels.map((lvl: any, i: number) => ({ level: i, height: lvl.height || 0 }));
        setQualityLevels(levels.filter(l => l.height > 0)); // Only show valid video qualities
        handleLoadedMetadata();
      });

      hls.on(Hls.Events.LEVEL_SWITCHED, (_e, data) => {
        setSelectedQuality(data.level);
      });

      hlsRef.current = hls;
    } else if (isMP4) {
      video.src = streamUrl;
      video.addEventListener("loadedmetadata", handleLoadedMetadata);
    } else if (video.canPlayType("application/vnd.apple.mpegurl")) {
      // Native HLS (Safari)
      video.src = streamUrl;
      video.addEventListener("loadedmetadata", handleLoadedMetadata);
    }

    // ── Event listeners ──
    const onPlay = () => setIsPlaying(true);
    const onPause = () => setIsPlaying(false);
    const onTimeUpdate = () => {
      setCurrentTime(video.currentTime);
      if (video.buffered.length > 0) {
        setBuffered(video.buffered.end(video.buffered.length - 1));
      }
    };
    const onDurationChange = () => setDuration(video.duration);
    const onEnded = () => {
      setIsPlaying(false);
      reportProgress(video.duration, true);
    };

    video.addEventListener("play", onPlay);
    video.addEventListener("pause", onPause);
    video.addEventListener("timeupdate", onTimeUpdate);
    video.addEventListener("durationchange", onDurationChange);
    video.addEventListener("ended", onEnded);

    // ── Periodic progress heartbeat (every 20s) ──
    progressTimerRef.current = setInterval(() => {
      if (!video.paused && video.duration > 0) {
        const pct = video.currentTime / video.duration;
        reportProgress(video.currentTime, pct >= 0.9);
      }
    }, 20000);

    // ── Security: disable right-click on player ──
    const blockContextMenu = (e: Event) => e.preventDefault();
    video.addEventListener("contextmenu", blockContextMenu);

    // ── Security: disable drag ──
    const blockDrag = (e: Event) => e.preventDefault();
    video.addEventListener("dragstart", blockDrag);

    // ── Security: disable Picture-in-Picture ──
    video.disablePictureInPicture = true;
    const blockPiP = (e: Event) => e.preventDefault();
    video.addEventListener("enterpictureinpicture", blockPiP);

    return () => {
      video.removeEventListener("play", onPlay);
      video.removeEventListener("pause", onPause);
      video.removeEventListener("timeupdate", onTimeUpdate);
      video.removeEventListener("durationchange", onDurationChange);
      video.removeEventListener("ended", onEnded);
      video.removeEventListener("contextmenu", blockContextMenu);
      video.removeEventListener("dragstart", blockDrag);
      video.removeEventListener("enterpictureinpicture", blockPiP);
      video.removeEventListener("loadedmetadata", handleLoadedMetadata);
      if (progressTimerRef.current) clearInterval(progressTimerRef.current);
      if (hlsRef.current) {
        hlsRef.current.destroy();
        hlsRef.current = null;
      }
    };
  }, [streamUrl, streamType, initialTime, reportProgress]);

  // ── Watermark: move to a new random position every 30s ──
  useEffect(() => {
    const rotate = () => {
      const next = WATERMARK_POSITIONS[Math.floor(Math.random() * WATERMARK_POSITIONS.length)];
      setWatermarkPos(next);
    };
    watermarkTimerRef.current = setInterval(rotate, 30_000);
    return () => { if (watermarkTimerRef.current) clearInterval(watermarkTimerRef.current); };
  }, []);

  // ── Security: DevTools detection — pause video when DevTools open ──
  useEffect(() => {
    let devToolsOpen = false;
    const threshold = 160;
    const detect = () => {
      const widthDiff  = window.outerWidth  - window.innerWidth  > threshold;
      const heightDiff = window.outerHeight - window.innerHeight > threshold;
      const isOpen = widthDiff || heightDiff;
      if (isOpen && !devToolsOpen) {
        devToolsOpen = true;
        videoRef.current?.pause();
      } else if (!isOpen && devToolsOpen) {
        devToolsOpen = false;
      }
    };
    const timer = setInterval(detect, 1000);
    return () => clearInterval(timer);
  }, []);

  // ── Security: keyboard shortcuts lockdown ──
  useEffect(() => {
    const blockKeys = (e: KeyboardEvent) => {
      // F12, Ctrl+Shift+I/J/C, Ctrl+U, Ctrl+S
      if (
        e.key === "F12" ||
        (e.ctrlKey && e.shiftKey && ["I", "J", "C", "K"].includes(e.key.toUpperCase())) ||
        (e.ctrlKey && e.key.toUpperCase() === "U") ||
        (e.ctrlKey && e.key.toUpperCase() === "S")
      ) {
        e.preventDefault();
        e.stopPropagation();
        return false;
      }
    };
    document.addEventListener("keydown", blockKeys, true);
    return () => document.removeEventListener("keydown", blockKeys, true);
  }, []);

  // ── Auto-hide controls after 3s of inactivity ──
  const resetHideTimer = useCallback(() => {
    setShowControls(true);
    if (hideControlsTimerRef.current) clearTimeout(hideControlsTimerRef.current);
    if (isPlaying) {
      hideControlsTimerRef.current = setTimeout(() => setShowControls(false), 3000);
    }
  }, [isPlaying]);

  useEffect(() => {
    if (!isPlaying) {
      setShowControls(true);
      if (hideControlsTimerRef.current) clearTimeout(hideControlsTimerRef.current);
    } else {
      resetHideTimer();
    }
  }, [isPlaying, resetHideTimer]);

  // ── Fullscreen listener ──
  useEffect(() => {
    const onChange = () => {
      const isFs = !!document.fullscreenElement;
      setIsFullscreen(isFs);
      const orientation = (screen as any).orientation;
      if (isFs) {
        if (orientation && orientation.lock) {
          orientation.lock("landscape").catch(() => {});
        }
      } else {
        if (orientation && orientation.unlock) {
          orientation.unlock();
        }
      }
    };
    document.addEventListener("fullscreenchange", onChange);
    return () => document.removeEventListener("fullscreenchange", onChange);
  }, []);

  // ── Controls ──
  const togglePlay = useCallback(async () => {
    const v = videoRef.current;
    if (!v || isTogglingRef.current) return;
    isTogglingRef.current = true;
    try {
      if (v.paused) {
        playPromiseRef.current = v.play();
        await playPromiseRef.current;
      } else {
        // Wait for any pending play() to settle before pausing
        if (playPromiseRef.current) {
          await playPromiseRef.current.catch(() => {});
        }
        v.pause();
      }
    } catch {
      // AbortError or NotAllowedError — ignore
    } finally {
      isTogglingRef.current = false;
    }
  }, []);

  const toggleMute = () => {
    const v = videoRef.current;
    if (!v) return;
    v.muted = !v.muted;
    setIsMuted(v.muted);
  };

  const handleVolume = (val: number) => {
    const v = videoRef.current;
    if (!v) return;
    v.volume = val;
    setVolume(val);
    if (val === 0) { v.muted = true; setIsMuted(true); }
    else if (v.muted) { v.muted = false; setIsMuted(false); }
  };

  const handleSeek = (e: React.ChangeEvent<HTMLInputElement>) => {
    const v = videoRef.current;
    if (!v) return;
    v.currentTime = parseFloat(e.target.value);
  };

  const toggleFullscreen = () => {
    const el = containerRef.current;
    if (!el) return;
    document.fullscreenElement ? document.exitFullscreen() : el.requestFullscreen();
  };

  const setQuality = (level: number) => {
    if (hlsRef.current) hlsRef.current.currentLevel = level;
  };

  const formatTime = (s: number) => {
    if (isNaN(s)) return "0:00";
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = Math.floor(s % 60);
    if (h > 0) return `${h}:${m < 10 ? "0" : ""}${m}:${sec < 10 ? "0" : ""}${sec}`;
    return `${m}:${sec < 10 ? "0" : ""}${sec}`;
  };

  // Watermark text
  const watermarkText = user
    ? `${user.name} • ${user.email}`
    : 'محمي';

  return (
    <div
      ref={containerRef}
      className="relative w-full h-full bg-black rounded-lg overflow-hidden select-none group"
      onMouseMove={resetHideTimer}
      onContextMenu={(e) => e.preventDefault()}
    >
      {/* ── Video Element ── */}
      <video
        ref={videoRef}
        className="w-full h-full object-contain"
        playsInline
        preload="metadata"
        disablePictureInPicture
        onClick={togglePlay}
        onDoubleClick={toggleFullscreen}
        style={{ pointerEvents: "auto" }}
      />

      {/* ── Watermark Overlay ── */}
      {isLoaded && (
        <div
          className="absolute z-25 pointer-events-none transition-all duration-1000"
          style={{
            ...watermarkPos,
            maxWidth: '55%',
          }}
        >
          <p
            className="text-white font-medium leading-tight"
            style={{
              fontSize: 'clamp(10px, 1.3vw, 14px)',
              opacity: 0.13,
              textShadow: '0 1px 3px rgba(0,0,0,0.8)',
              userSelect: 'none',
              WebkitUserSelect: 'none',
              whiteSpace: 'nowrap',
              direction: 'ltr',
            }}
          >
            {watermarkText}
          </p>
        </div>
      )}

      {/* ── Loading spinner ── */}
      {!isLoaded && (
        <div className="absolute inset-0 flex items-center justify-center z-30">
          <div className="w-10 h-10 border-2 border-white/20 border-t-white rounded-full animate-spin" />
        </div>
      )}

      {/* ── Center play button (when paused) ── */}
      {!isPlaying && isLoaded && (
        <button
          onClick={(e) => { e.stopPropagation(); togglePlay(); }}
          className="absolute inset-0 z-20 flex items-center justify-center"
        >
          <div className="w-16 h-16 rounded-full bg-white/15 backdrop-blur-sm flex items-center justify-center hover:bg-white/25 transition-all active:scale-90">
            <Play className="h-7 w-7 text-white fill-white ml-1" />
          </div>
        </button>
      )}

      {/* ── Control bar ── */}
      <div
        className={`absolute bottom-0 left-0 right-0 z-30 transition-opacity duration-300 ${
          showControls ? "opacity-100" : "opacity-0 pointer-events-none"
        }`}
      >
        {/* Gradient background */}
        <div className="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent pointer-events-none" />

        <div className="relative px-4 pb-4 pt-10 space-y-2">
          {/* ── Seek bar ── */}
          <div className="relative h-1 group/seek hover:h-2 transition-all cursor-pointer">
            {/* Buffered */}
            <div
              className="absolute top-0 left-0 h-full bg-white/20 rounded-full"
              style={{ width: duration ? `${(buffered / duration) * 100}%` : "0%" }}
            />
            {/* Progress */}
            <div
              className="absolute top-0 left-0 h-full bg-red-500 rounded-full"
              style={{ width: duration ? `${(currentTime / duration) * 100}%` : "0%" }}
            />
            {/* Thumb */}
            <div
              className="absolute top-1/2 -translate-y-1/2 w-3 h-3 bg-red-500 rounded-full opacity-0 group-hover/seek:opacity-100 transition-opacity"
              style={{ left: duration ? `calc(${(currentTime / duration) * 100}% - 6px)` : "0" }}
            />
            {/* Hidden range input for accessibility */}
            <input
              type="range"
              min={0}
              max={duration || 100}
              value={currentTime}
              onChange={handleSeek}
              className="absolute inset-0 w-full opacity-0 cursor-pointer"
            />
          </div>

          {/* ── Bottom controls row ── */}
          <div className="flex items-center justify-between text-white">
            <div className="flex items-center gap-3">
              {/* Play/Pause */}
              <button onClick={togglePlay} className="hover:text-red-400 transition-colors focus:outline-none">
                {isPlaying ? <Pause className="h-5 w-5 fill-current" /> : <Play className="h-5 w-5 fill-current" />}
              </button>

              {/* Volume */}
              <div className="flex items-center gap-1 group/vol">
                <button onClick={toggleMute} className="hover:text-red-400 transition-colors focus:outline-none">
                  {isMuted || volume === 0 ? <VolumeX className="h-5 w-5" /> : <Volume2 className="h-5 w-5" />}
                </button>
                <input
                  type="range"
                  min={0}
                  max={1}
                  step={0.05}
                  value={isMuted ? 0 : volume}
                  onChange={(e) => handleVolume(parseFloat(e.target.value))}
                  className="w-0 group-hover/vol:w-20 transition-all opacity-0 group-hover/vol:opacity-100 accent-red-500 cursor-pointer"
                />
              </div>

              {/* Time */}
              <span className="text-xs font-medium tabular-nums">
                {formatTime(currentTime)} <span className="text-white/50">/</span> {formatTime(duration)}
              </span>
            </div>

            <div className="flex items-center gap-3">
              {/* Quality selector */}
              {qualityLevels.length > 0 && (
                <div className="relative group/q">
                  <button className="text-xs font-bold px-2 py-1 rounded border border-white/30 hover:border-white/60 transition-colors">
                    {selectedQuality === -1 ? "تلقائي" : `${qualityLevels.find(q => q.level === selectedQuality)?.height || "HD"}p`}
                  </button>
                  <div className="absolute bottom-full right-0 mb-2 bg-black/90 backdrop-blur-sm rounded-lg overflow-hidden opacity-0 group-hover/q:opacity-100 pointer-events-none group-hover/q:pointer-events-auto transition-opacity">
                    <button
                      onClick={() => setQuality(-1)}
                      className={`block w-full px-4 py-2 text-xs text-right whitespace-nowrap hover:bg-white/10 ${selectedQuality === -1 ? "text-red-400 font-bold" : ""}`}
                    >
                      تلقائي
                    </button>
                    {qualityLevels.map((q) => (
                      <button
                        key={q.level}
                        onClick={() => setQuality(q.level)}
                        className={`block w-full px-4 py-2 text-xs text-right whitespace-nowrap hover:bg-white/10 ${selectedQuality === q.level ? "text-red-400 font-bold" : ""}`}
                      >
                        {q.height}p
                      </button>
                    ))}
                  </div>
                </div>
              )}

              {/* Fullscreen */}
              <button onClick={toggleFullscreen} className="hover:text-red-400 transition-colors focus:outline-none">
                {isFullscreen ? <Minimize className="h-5 w-5" /> : <Maximize className="h-5 w-5" />}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

/* ════════════════════════════════════════════════════════════════════════════
   YouTube Secure Player (unchanged)
   ════════════════════════════════════════════════════════════════════════════ */

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
  const [playbackRate, setPlaybackRate] = useState(1);
  const [showSpeedMenu, setShowSpeedMenu] = useState(false);
  const speedOptions = [0.5, 1, 1.25, 1.5, 2];

  useEffect(() => {
    if (!window.YT) {
      const tag = document.createElement("script");
      tag.src = "https://www.youtube.com/iframe_api";
      const firstScriptTag = document.getElementsByTagName("script")[0];
      if (firstScriptTag?.parentNode) {
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
      }
      window.onYouTubeIframeAPIReady = () => setIsApiReady(true);
    } else {
      setIsApiReady(true);
    }
  }, []);

  useEffect(() => {
    if (isApiReady && videoId && containerRef.current && !playerRef.current) {
      playerRef.current = new window.YT.Player(containerRef.current, {
        videoId,
        playerVars: { controls: 0, disablekb: 1, rel: 0, modestbranding: 1, iv_load_policy: 3, fs: 0, playsinline: 1 },
        events: {
          onReady: (e: any) => setDuration(e.target.getDuration()),
          onStateChange: (e: any) => {
            if (e.data === window.YT.PlayerState.PLAYING) { setIsPlaying(true); setDuration(e.target.getDuration()); }
            else if (e.data === window.YT.PlayerState.PAUSED || e.data === window.YT.PlayerState.ENDED) setIsPlaying(false);
          },
        },
      });
    }
    return () => { if (playerRef.current) { playerRef.current.destroy(); playerRef.current = null; } };
  }, [isApiReady, videoId]);

  useEffect(() => {
    let i: NodeJS.Timeout;
    if (isPlaying && playerRef.current?.getCurrentTime) {
      i = setInterval(() => setCurrentTime(playerRef.current.getCurrentTime()), 500);
    }
    return () => clearInterval(i);
  }, [isPlaying]);

  useEffect(() => {
    const h = () => setIsFullscreen(!!document.fullscreenElement);
    document.addEventListener("fullscreenchange", h);
    return () => document.removeEventListener("fullscreenchange", h);
  }, []);

  const togglePlay = (e: React.MouseEvent) => { e.stopPropagation(); if (!playerRef.current || typeof playerRef.current.playVideo !== "function") return; isPlaying ? playerRef.current.pauseVideo() : playerRef.current.playVideo(); };
  const handleSeek = (e: React.ChangeEvent<HTMLInputElement>) => { const t = parseFloat(e.target.value); setCurrentTime(t); playerRef.current?.seekTo?.(t, true); };
  const toggleFullscreen = (e: React.MouseEvent) => { e.stopPropagation(); document.fullscreenElement ? document.exitFullscreen() : wrapperRef.current?.requestFullscreen(); };
  const formatTime = (s: number) => { if (isNaN(s)) return "0:00"; const m = Math.floor(s / 60); const sec = Math.floor(s % 60); return `${m}:${sec < 10 ? "0" : ""}${sec}`; };

  const handleContainerClick = (e: React.MouseEvent) => {
    e.stopPropagation();
    setShowSpeedMenu(false);
    setIsHovered(!isHovered);
  };

  const changeSpeed = (e: React.MouseEvent, rate: number) => {
    e.stopPropagation();
    setPlaybackRate(rate);
    setShowSpeedMenu(false);
    playerRef.current?.setPlaybackRate?.(rate);
  };

  if (!videoId) return <div className="w-full h-full flex items-center justify-center bg-black text-white text-sm">رابط الفيديو غير صالح</div>;

  return (
    <div ref={wrapperRef} className="w-full h-full relative rounded-lg overflow-hidden bg-black select-none group flex flex-col" onMouseEnter={() => setIsHovered(true)} onMouseLeave={() => { setIsHovered(false); setShowSpeedMenu(false); }} onContextMenu={(e) => e.preventDefault()}>
      <div className="flex-1 relative w-full h-full cursor-pointer" onClick={handleContainerClick}>
        <div className="absolute inset-0 pointer-events-none scale-[1.05]"><div ref={containerRef} className="w-full h-full" /></div>
        <div className="absolute inset-0 z-10 bg-transparent pointer-events-none" />
        <div className={`absolute inset-0 z-20 flex items-center justify-center bg-black/40 transition-opacity duration-300 ${(!isPlaying || isHovered) ? "opacity-100" : "opacity-0 pointer-events-none"}`}>
          <div className="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-primary/95 text-primary-foreground flex items-center justify-center shadow-lg transition-transform active:scale-95 hover:bg-primary cursor-pointer pointer-events-auto" onClick={togglePlay}>
            {isPlaying ? <Pause className="h-8 w-8 sm:h-10 sm:w-10 fill-current" /> : <Play className="h-8 w-8 sm:h-10 sm:w-10 fill-current ml-1 sm:ml-2" />}
          </div>
        </div>
      </div>
      <div className={`absolute bottom-0 left-0 right-0 z-30 bg-gradient-to-t from-black/90 to-transparent p-3 sm:p-4 transition-opacity duration-300 ${isHovered || !isPlaying ? "opacity-100" : "opacity-0 pointer-events-none"}`}>
        <div className="flex items-center gap-2 sm:gap-4 text-white text-[10px] sm:text-xs font-medium">
          <button onClick={togglePlay} className="p-1.5 sm:p-2 hover:text-primary transition-colors focus:outline-none pointer-events-auto">
            {isPlaying ? <Pause className="h-5 w-5" /> : <Play className="h-5 w-5" />}
          </button>
          <span>{formatTime(currentTime)}</span>
          <input type="range" min={0} max={duration || 100} value={currentTime} onChange={handleSeek} className="flex-1 h-1.5 sm:h-2 bg-white/20 rounded-lg appearance-none cursor-pointer accent-primary pointer-events-auto" style={{ direction: "ltr" }} />
          <span>{formatTime(duration)}</span>
          
          <div className="relative pointer-events-auto">
            <button onClick={(e) => { e.stopPropagation(); setShowSpeedMenu(!showSpeedMenu); }} className="px-2 py-1 bg-white/10 rounded hover:bg-white/20 transition-colors ml-2 font-mono">
              {playbackRate}x
            </button>
            {showSpeedMenu && (
              <div className="absolute bottom-full right-0 mb-2 bg-zinc-900 border border-zinc-800 rounded-md shadow-xl py-1 flex flex-col overflow-hidden w-16 z-50">
                {speedOptions.map((rate) => (
                  <button key={rate} onClick={(e) => changeSpeed(e, rate)} className={`px-3 py-1.5 text-xs hover:bg-zinc-800 text-right ${playbackRate === rate ? 'text-primary font-bold' : 'text-white'}`}>
                    {rate}x
                  </button>
                ))}
              </div>
            )}
          </div>

          <button onClick={toggleFullscreen} className="p-1.5 sm:p-2 hover:text-primary transition-colors focus:outline-none pointer-events-auto ml-1 sm:ml-2">
            {isFullscreen ? <Minimize className="h-5 w-5" /> : <Maximize className="h-5 w-5" />}
          </button>
        </div>
      </div>
    </div>
  );
}
