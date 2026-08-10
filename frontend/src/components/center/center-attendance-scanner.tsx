"use client";

import { useState, useEffect, useRef } from "react";
import { centerService, AcademicSession, AttendanceRecord } from "@/services/center.service";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { RefreshCw, UserCheck, CheckCircle2, XCircle, Camera, CameraOff, QrCode, AlertCircle, Clock } from "lucide-react";
import { BrowserMultiFormatReader } from "@zxing/library";

interface ScannerProps {
  sessions: AcademicSession[];
  onAttendanceUpdated?: () => void;
}

export function CenterAttendanceScanner({ sessions, onAttendanceUpdated }: ScannerProps) {
  const [selectedSessionId, setSelectedSessionId] = useState<string>(sessions[0]?.id || "");
  const [manualCode, setManualCode] = useState<string>("");
  const [loading, setLoading] = useState<boolean>(false);
  const [scanResult, setScanResult] = useState<any>(null);
  const [scanError, setScanError] = useState<string>("");
  const [recentScans, setRecentScans] = useState<any[]>([]);
  
  // Camera State
  const [isCameraActive, setIsCameraActive] = useState<boolean>(false);
  const videoRef = useRef<HTMLVideoElement>(null);
  const streamRef = useRef<MediaStream | null>(null);
  const codeReaderRef = useRef<BrowserMultiFormatReader | null>(null);
  const lastScannedCode = useRef<string | null>(null);
  const lastScanTime = useRef<number>(0);

  useEffect(() => {
    if (sessions.length > 0 && !selectedSessionId) {
      setSelectedSessionId(sessions[0].id);
    }
  }, [sessions, selectedSessionId]);

  // Audio Beep Effect using Web Audio API
  const playSound = (type: "success" | "error" | "guest") => {
    try {
      const ctx = new (window.AudioContext || (window as any).webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);

      if (type === "success") {
        osc.frequency.setValueAtTime(880, ctx.currentTime); // A5
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        osc.start();
        osc.stop(ctx.currentTime + 0.15);
      } else if (type === "guest") {
        osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        osc.start();
        osc.stop(ctx.currentTime + 0.25);
      } else {
        osc.frequency.setValueAtTime(300, ctx.currentTime); // Error low pitch
        gain.gain.setValueAtTime(0.4, ctx.currentTime);
        osc.start();
        osc.stop(ctx.currentTime + 0.3);
      }
    } catch (e) {
      // Audio context might be restricted before interaction
    }
  };

  const handleScanCode = async (codeToScan: string, statusOverride?: string) => {
    console.log("handleScanCode called with:", codeToScan, "session:", selectedSessionId);
    if (!selectedSessionId) {
      setScanError("يرجى اختيار الحصة الدراسية أولاً.");
      return;
    }
    if (!codeToScan || !codeToScan.trim()) return;

    setLoading(true);
    setScanError("");
    setScanResult(null);

    try {
      console.log("Calling API for scan...", codeToScan.trim());
      const res = await centerService.scanAttendance(selectedSessionId, codeToScan.trim(), statusOverride);
      console.log("API Scan Result:", res);
      setScanResult(res);
      
      if (res.student?.is_guest) {
        playSound("guest");
      } else {
        playSound("success");
      }

      setRecentScans((prev) => [res.student, ...prev.filter((s) => s?.id !== res.student?.id)]);
      setManualCode("");
      if (onAttendanceUpdated) onAttendanceUpdated();
    } catch (err: any) {
      console.error("API Scan Error:", err);
      playSound("error");
      const msg = err.response?.data?.message || `لم يتم العثور على طالب بالكود: ${codeToScan}`;
      setScanError(msg);
    } finally {
      console.log("Scan finally block executed");
      setLoading(false);
    }
  };

  const handleScanCodeRef = useRef(handleScanCode);
  useEffect(() => {
    handleScanCodeRef.current = handleScanCode;
  }, [handleScanCode]);

  // Start Camera Stream
  const toggleCamera = async () => {
    if (isCameraActive) {
      if (streamRef.current) {
        streamRef.current.getTracks().forEach((track) => track.stop());
      }
      setIsCameraActive(false);
    } else {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: "environment" },
        });
        streamRef.current = stream;
        if (videoRef.current) {
          videoRef.current.srcObject = stream;
        }
        setIsCameraActive(true);
      } catch (e) {
        setScanError("تعذر الوصول إلى الكاميرا. يرجى التأكد من إعطاء الصلاحية للكاميرا في المتصفح.");
      }
    }
  };

  const handleManualCapture = () => {
    if (!isCameraActive || !videoRef.current || !codeReaderRef.current) return;
    
    codeReaderRef.current.decodeFromVideoElement(videoRef.current)
      .then(result => {
        if (result && result.getText()) {
          try {
            const audioCtx = new (window.AudioContext || (window as any).webkitAudioContext)();
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            oscillator.type = "sine";
            oscillator.frequency.value = 800;
            gainNode.gain.setValueAtTime(1, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.1);
            oscillator.start();
            oscillator.stop(audioCtx.currentTime + 0.1);
          } catch (e) {}
          handleScanCodeRef.current(result.getText());
        }
      })
      .catch((err: any) => {
        console.error(err);
        setScanError("لم يتم التعرف على أي باركود في الصورة الحالية. تأكد من إضاءة ووضوح الكود.");
        setTimeout(() => setScanError(""), 3000);
      });
  };

  useEffect(() => {
    return () => {
      if (streamRef.current) {
        streamRef.current.getTracks().forEach((track) => track.stop());
      }
      if (codeReaderRef.current) {
        codeReaderRef.current.stopContinuousDecode();
      }
    };
  }, []);

  useEffect(() => {
    if (!codeReaderRef.current) {
      codeReaderRef.current = new BrowserMultiFormatReader();
    }

    if (isCameraActive && videoRef.current && streamRef.current) {
      videoRef.current.srcObject = streamRef.current;
      videoRef.current.play().catch(console.error);

      codeReaderRef.current.decodeFromVideoElementContinuously(videoRef.current, (result, err: any) => {
        if (result) {
          const text = result.getText();
          const now = Date.now();
          if (text !== lastScannedCode.current || (now - lastScanTime.current > 3000)) {
            lastScannedCode.current = text;
            lastScanTime.current = now;
            
            try {
              const audioCtx = new (window.AudioContext || (window as any).webkitAudioContext)();
              const oscillator = audioCtx.createOscillator();
              const gainNode = audioCtx.createGain();
              oscillator.connect(gainNode);
              gainNode.connect(audioCtx.destination);
              oscillator.type = "sine";
              oscillator.frequency.value = 800;
              gainNode.gain.setValueAtTime(1, audioCtx.currentTime);
              gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.1);
              oscillator.start();
              oscillator.stop(audioCtx.currentTime + 0.1);
            } catch (e) {}

            handleScanCodeRef.current(text);
          }
        }
      });
    }

    return () => {
      if (codeReaderRef.current) {
        codeReaderRef.current.stopContinuousDecode();
      }
    };
  }, [isCameraActive, selectedSessionId]);

  return (
    <div className="space-y-6">
      {/* Session Selection Header */}
      <div className="glass-card p-6 rounded-2xl border border-primary/20 bg-gradient-to-r from-primary/5 via-background to-secondary/5">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h3 className="text-lg font-bold flex items-center gap-2 text-foreground">
              <QrCode className="h-6 w-6 text-primary animate-pulse" />
              <span>ماسح الحضور والغياب الفوري (Scanner)</span>
            </h3>
            <p className="text-xs text-muted-foreground mt-1">
              امسح كود الطالب بالكامل بواسطة الكاميرا أو ادخل الكود/رقم الهاتف يدويًا لتسجيل الحضور وتنبيه ولي الأمر فورًا.
            </p>
          </div>

          <div className="w-full md:w-72">
            <Label className="text-xs font-semibold mb-1 block">اختر الحصة الدراسية:</Label>
            <select
              value={selectedSessionId}
              onChange={(e) => setSelectedSessionId(e.target.value)}
              className="w-full h-10 rounded-lg bg-background border border-border px-3 text-sm font-medium focus:ring-2 focus:ring-primary"
            >
              {sessions.length === 0 ? (
                <option value="">لا توجد حصص مضافة</option>
              ) : (
                sessions.map((s) => (
                  <option key={s.id} value={s.id}>
                    {s.group?.name} - {s.topic} ({s.date})
                  </option>
                ))
              )}
            </select>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* Scanner & Code Input Box */}
        <div className="lg:col-span-6 space-y-6">
          {/* Live Camera Box */}
          <div className="glass-card p-6 rounded-2xl border border-border relative overflow-hidden text-center">
            <div className="flex items-center justify-between mb-4">
              <span className="text-sm font-bold flex items-center gap-2">
                <Camera className="h-4 w-4 text-primary" />
                كاميرا الكود البصري
              </span>
              <Button
                variant={isCameraActive ? "destructive" : "default"}
                size="sm"
                onClick={toggleCamera}
                className="gap-2 text-xs"
              >
                {isCameraActive ? <CameraOff className="h-4 w-4" /> : <Camera className="h-4 w-4" />}
                {isCameraActive ? "إيقاف الكاميرا" : "تشغيل الكاميرا"}
              </Button>
            </div>

            {isCameraActive ? (
              <div className="relative aspect-video w-full rounded-xl overflow-hidden bg-black border-2 border-primary/50 shadow-inner flex items-center justify-center">
                <video ref={videoRef} autoPlay playsInline muted className="w-full h-full object-cover" />
                {/* Overlay Aim Frame */}
                <div className="absolute inset-0 border-2 border-dashed border-primary/70 rounded-2xl m-8 pointer-events-none flex flex-col items-center justify-center gap-4">
                  <div className="bg-primary/20 backdrop-blur-xs px-3 py-1 rounded-full text-[10px] text-white font-mono animate-pulse">
                    ضع الكود هنا
                  </div>
                </div>
                {/* Manual Capture Button */}
                <div className="absolute bottom-4 left-0 right-0 flex justify-center z-10">
                  <Button 
                    onClick={handleManualCapture}
                    className="rounded-full shadow-lg gap-2 animate-in slide-in-from-bottom-4"
                  >
                    <Camera className="h-4 w-4" />
                    التقاط الكود (يدوي)
                  </Button>
                </div>
              </div>
            ) : (
              <div className="aspect-video w-full rounded-xl border border-dashed border-border flex flex-col items-center justify-center gap-3 bg-muted/30">
                <Camera className="h-12 w-12 text-muted-foreground/40" />
                <p className="text-xs text-muted-foreground">الكاميرا متوقفة حالياً. اضغط "تشغيل الكاميرا" للمسح التلقائي.</p>
              </div>
            )}
          </div>

          {/* Manual Code Input Form */}
          <div className="glass-card p-6 rounded-2xl border border-border">
            <h4 className="text-sm font-bold mb-3 flex items-center gap-2">
              <QrCode className="h-4 w-4 text-primary" />
              إدخال كود الطالب أو رقم الهاتف يدويًا
            </h4>
            <form
              onSubmit={(e) => {
                e.preventDefault();
                handleScanCode(manualCode);
              }}
              className="flex gap-2"
            >
              <Input
                placeholder="مثال: ST2026101 أو 01012345671"
                value={manualCode}
                onChange={(e) => setManualCode(e.target.value)}
                className="h-11 font-mono text-base tracking-wider text-center"
                autoFocus
              />
              <Button type="submit" disabled={loading || !manualCode.trim()} className="h-11 px-6 font-bold gap-2">
                {loading ? <RefreshCw className="h-4 w-4 animate-spin" /> : <UserCheck className="h-4 w-4" />}
                تسجيل
              </Button>
            </form>
          </div>
        </div>

        {/* Scan Results & Realtime Log */}
        <div className="lg:col-span-6 space-y-6">
          {/* Result Alert Box */}
          {scanResult && (
            <div className="glass-card p-6 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 animate-in fade-in slide-in-from-top-4 duration-300">
              <div className="flex items-start gap-4">
                <div className="p-3 rounded-full bg-emerald-500/20 text-emerald-500">
                  <CheckCircle2 className="h-8 w-8" />
                </div>
                <div className="flex-1 space-y-1">
                  <div className="flex items-center justify-between">
                    <h4 className="text-base font-bold text-emerald-400">{scanResult.message}</h4>
                    <span className="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300">
                      {scanResult.student.is_guest ? "ضيف 🔵" : "حاضر 🟢"}
                    </span>
                  </div>
                  <p className="text-sm font-bold text-foreground mt-2">{scanResult.student.name}</p>
                  <p className="text-xs text-muted-foreground font-mono">الكود: {scanResult.student.code}</p>
                  <p className="text-xs text-muted-foreground">هاتف ولي الأمر: {scanResult.student.father_phone || "غير مضاف"}</p>
                </div>
              </div>
            </div>
          )}

          {scanError && (
            <div className="glass-card p-6 rounded-2xl border border-destructive/40 bg-destructive/10 animate-in fade-in slide-in-from-top-4 duration-300">
              <div className="flex items-center gap-3 text-destructive">
                <AlertCircle className="h-6 w-6 shrink-0" />
                <p className="text-sm font-bold">{scanError}</p>
              </div>
            </div>
          )}

          {/* Recent Live Scans Feed */}
          <div className="glass-card p-6 rounded-2xl border border-border">
            <h4 className="text-sm font-bold mb-4 flex items-center justify-between">
              <span className="flex items-center gap-2">
                <Clock className="h-4 w-4 text-primary" />
                سجل المسح المباشر في هذه الجلسة
              </span>
              <span className="text-xs font-normal text-muted-foreground">{recentScans.length} طالب</span>
            </h4>

            {recentScans.length === 0 ? (
              <div className="text-center py-10 text-xs text-muted-foreground">
                لم يتم تسجيل حضور أي طالب حتى الآن. قم بمسح كود الطالب للتسجيل.
              </div>
            ) : (
              <div className="space-y-2 max-h-80 overflow-y-auto pr-1">
                {recentScans.map((s, idx) => (
                  <div
                    key={s.id + idx}
                    className="flex items-center justify-between p-3 rounded-xl bg-background/60 border border-border/50 text-xs hover:border-primary/30 transition-all"
                  >
                    <div>
                      <p className="font-bold text-foreground">{s.name}</p>
                      <p className="font-mono text-[11px] text-muted-foreground">{s.code}</p>
                    </div>
                    <div className="flex items-center gap-2">
                      {s.is_guest && (
                        <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/10 text-blue-400">
                          ضيف من {s.original_group || "مجموعة أخرى"}
                        </span>
                      )}
                      <span className="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 flex items-center gap-1">
                        <CheckCircle2 className="h-3 w-3" /> تم التسجيل
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
