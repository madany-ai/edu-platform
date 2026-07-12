"use client";

import { useEffect, useState } from "react";
import api from "@/services/api.client";
import { CheckCircle, AlertCircle, Timer, Award, Play, ChevronLeft, ChevronRight, RefreshCw, Eye } from "lucide-react";
import { cn } from "@/lib/utils";
import { useParams } from "next/navigation";
import { useQueryClient } from "@tanstack/react-query";

interface Choice {
  id: string;
  answer: string;
  is_correct: boolean;
}

interface Question {
  id: string;
  type: "multiple_choice" | "true_false" | "essay";
  question: string;
  degree: number;
  image_path?: string;
  choices?: Choice[];
}

interface Exam {
  id: string;
  title: string;
  duration: number;
  pass_percentage: number;
  is_blocking: boolean;
  questions: Question[];
}

interface Attempt {
  id: string;
  score: number;
  started_at: string;
  submitted_at: string | null;
}

interface QuizTabProps {
  lectureId: string;
  isAssignment?: boolean;
  examId?: string;
}

export default function QuizTab({ lectureId, isAssignment = false, examId }: QuizTabProps) {
  const params = useParams();
  const courseId = params.id as string;
  const queryClient = useQueryClient();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [exam, setExam] = useState<Exam | null>(null);
  const [latestAttempt, setLatestAttempt] = useState<Attempt | null>(null);

  // Take Exam States
  const [activeAttempt, setActiveAttempt] = useState<Attempt | null>(null);
  const [answers, setAnswers] = useState<Record<string, string>>({});
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [timeLeft, setTimeLeft] = useState(0);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [examFinished, setExamFinished] = useState(false);
  const [gradedAttempt, setGradedAttempt] = useState<any>(null);
  const [showReview, setShowReview] = useState(false);

  const label = isAssignment ? "الواجب" : "الاختبار";
  const fetchEndpoint = isAssignment
    ? `/lectures/${lectureId}/assignment${examId ? `?exam_id=${examId}` : ""}`
    : `/lectures/${lectureId}/exam${examId ? `?exam_id=${examId}` : ""}`;

  const fetchExamData = async () => {
    try {
      setLoading(true);
      setError(null);
      const res = await api.get<{ exam: Exam; latest_attempt: Attempt | null }>(fetchEndpoint);
      setExam(res.data.exam);
      setLatestAttempt(res.data.latest_attempt);
      
      // If there is an active (unsubmitted) attempt, resume it
      if (res.data.latest_attempt && !res.data.latest_attempt.submitted_at) {
        setActiveAttempt(res.data.latest_attempt);
        // Calculate remaining time
        const startTime = new Date(res.data.latest_attempt.started_at).getTime();
        const durationMs = res.data.exam.duration * 60 * 1000;
        const elapsed = Date.now() - startTime;
        const remaining = Math.max(0, Math.floor((durationMs - elapsed) / 1000));
        setTimeLeft(remaining);
      }
    } catch (err: any) {
      console.error(err);
      setError(err.response?.data?.message || `لا يوجد ${label} متاح لهذه المحاضرة.`);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchExamData();
  }, [lectureId, examId]);

  // Timer effect
  useEffect(() => {
    if (!activeAttempt || timeLeft <= 0) return;

    const timer = setInterval(() => {
      setTimeLeft((prev) => {
        if (prev <= 1) {
          clearInterval(timer);
          handleSubmit(true); // Auto submit
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(timer);
  }, [activeAttempt, timeLeft]);

  const handleStart = async () => {
    if (!exam) return;
    try {
      setLoading(true);
      const res = await api.post<Attempt>(`/exams/${exam.id}/start`);
      setActiveAttempt(res.data);
      setTimeLeft(exam.duration * 60);
      setAnswers({});
      setCurrentQuestionIndex(0);
      setExamFinished(false);
      setGradedAttempt(null);
    } catch (err: any) {
      console.error(err);
      alert(err.response?.data?.message || "فشل بدء الاختبار.");
    } finally {
      setLoading(false);
    }
  };

  const handleSelectAnswer = (questionId: string, value: string) => {
    setAnswers((prev) => ({
      ...prev,
      [questionId]: value,
    }));
  };

  const handleSubmit = async (isAuto = false) => {
    const attemptToSubmit = activeAttempt || latestAttempt;
    if (!exam || !attemptToSubmit) return;

    if (!isAuto && Object.keys(answers).length < exam.questions.length) {
      const confirmSubmit = window.confirm(
        "لم تقم بالإجابة على جميع الأسئلة. هل أنت متأكد من رغبتك في تسليم الاختبار؟"
      );
      if (!confirmSubmit) return;
    }

    try {
      setIsSubmitting(true);
      // Format answers for API
      const formattedAnswers = exam.questions.map((q) => ({
        question_id: q.id,
        answer: answers[q.id] || "",
      }));

      const res = await api.post(`/attempts/${attemptToSubmit.id}/submit`, {
        answers: formattedAnswers,
      });

      // Get graded results
      const resultRes = await api.get(`/attempts/${attemptToSubmit.id}/result`);
      setGradedAttempt(resultRes.data);
      setExamFinished(true);
      setActiveAttempt(null);
      fetchExamData(); // Refresh main state
      
      // Invalidate course and lecture queries to refresh locking status and sidebar tree immediately
      queryClient.invalidateQueries({ queryKey: ["course", courseId] });
      queryClient.invalidateQueries({ queryKey: ["lecture", lectureId] });
    } catch (err: any) {
      console.error(err);
      alert("حدث خطأ أثناء تسليم الإجابات.");
    } finally {
      setIsSubmitting(false);
    }
  };

  const loadGradedResult = async (attemptId: string) => {
    try {
      setLoading(true);
      const res = await api.get(`/attempts/${attemptId}/result`);
      setGradedAttempt(res.data);
      setExamFinished(true);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const formatTime = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, "0")}:${secs.toString().padStart(2, "0")}`;
  };

  if (loading && !activeAttempt) {
    return (
      <div className="flex flex-col items-center justify-center py-12">
        <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-primary mb-4" />
        <p className="text-sm text-muted-foreground">جاري تحميل بيانات {label}...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex flex-col items-center justify-center text-center py-12 px-4">
        <AlertCircle className="h-12 w-12 text-muted-foreground mb-4 opacity-40" />
        <p className="text-base font-semibold text-foreground mb-1">{error}</p>
        <p className="text-xs text-muted-foreground">سيتم إخطارك فور تفعيل {label} لهذه المحاضرة.</p>
      </div>
    );
  }

  if (!exam) return null;

  // Render Quiz Result View
  if (examFinished && gradedAttempt) {
    const passed = gradedAttempt.score >= exam.pass_percentage;
    return (
      <div className="space-y-6 max-w-2xl mx-auto py-4">
        <div className="text-center space-y-4">
          <div className="inline-flex p-4 rounded-full bg-muted">
            <Award className={cn("h-16 w-16", passed ? "text-green-500" : "text-red-500")} />
          </div>
          <h2 className="text-2xl font-bold text-foreground">
            {passed ? `تهانينا! لقد اجتزت ${label} بنجاح 🎉` : `للأسف، لم تجتز ${label} هذه المرة`}
          </h2>
          <p className="text-muted-foreground text-sm max-w-md mx-auto">
            {passed
              ? `لقد حصلت على درجة مميزة وتجاوزت الحد الأدنى للنجاح بنجاح.`
              : `درجة النجاح المطلوبة هي ${exam.pass_percentage}%. يمكنك مراجعة إجاباتك وإعادة المحاولة.`}
          </p>

          {/* Score Display */}
          <div className="flex items-center justify-center gap-6 py-4">
            <div className="bg-card border p-4 rounded-2xl shadow-sm text-center min-w-[120px]">
              <p className="text-xs text-muted-foreground mb-1">درجتك</p>
              <p className={cn("text-3xl font-extrabold", passed ? "text-green-500" : "text-red-500")}>
                {gradedAttempt.score}%
              </p>
            </div>
            <div className="bg-card border p-4 rounded-2xl shadow-sm text-center min-w-[120px]">
              <p className="text-xs text-muted-foreground mb-1">نسبة القبول</p>
              <p className="text-3xl font-extrabold text-foreground">{exam.pass_percentage}%</p>
            </div>
          </div>

          <div className="flex items-center justify-center gap-4">
            <button
              onClick={() => setShowReview(!showReview)}
              className="flex items-center gap-2 px-6 py-2.5 rounded-xl border font-bold hover:bg-muted transition-colors text-sm"
            >
              <Eye className="h-4 w-4" />
              {showReview ? "إخفاء مراجعة الأسئلة" : "مراجعة أسئلتي وإجاباتي"}
            </button>
            <button
              onClick={handleStart}
              className="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-primary text-primary-foreground font-bold hover:bg-primary/95 transition-colors text-sm"
            >
              <RefreshCw className="h-4 w-4" />
              إعادة المحاولة
            </button>
          </div>
        </div>

        {/* Question Review Section */}
        {showReview && (
          <div className="space-y-4 pt-6 border-t animate-in fade-in duration-300">
            <h3 className="text-lg font-bold">مراجعة الإجابات:</h3>
            {exam.questions.map((q, idx) => {
              const studentAnswer = gradedAttempt.answers?.find((a: any) => a.question_id === q.id);
              const isEssay = q.type === "essay";
              
              // Find correct choice
              const correctChoice = q.choices?.find((c) => c.is_correct);
              const isCorrect = !isEssay && studentAnswer && correctChoice && String(studentAnswer.answer) === String(correctChoice.id);

              return (
                <div key={q.id} className="bg-card border rounded-2xl p-5 space-y-3">
                  <div className="flex items-start justify-between gap-4">
                    <span className="text-sm font-bold text-muted-foreground">السؤال {idx + 1}</span>
                    <span className={cn(
                      "text-xs px-2.5 py-1 rounded-full font-semibold",
                      isEssay ? "bg-blue-500/10 text-blue-600" :
                      isCorrect ? "bg-green-500/10 text-green-600" : "bg-red-500/10 text-red-600"
                    )}>
                      {isEssay ? "إجابة مقالية" : isCorrect ? `صحيح (+${q.degree} درجات)` : "خاطئ (0 درجة)"}
                    </span>
                  </div>

                  <p className="text-foreground font-medium">{q.question}</p>

                  {/* Multiple Choice Choices review */}
                  {!isEssay && q.choices && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2">
                      {q.choices.map((c) => {
                        const isStudentSelected = studentAnswer && String(studentAnswer.answer) === String(c.id);
                        return (
                          <div
                            key={c.id}
                            className={cn(
                              "p-3 rounded-xl border flex items-center justify-between text-sm",
                              c.is_correct
                                ? "bg-green-500/10 border-green-200 text-green-700 font-medium"
                                : isStudentSelected
                                ? "bg-red-500/10 border-red-200 text-red-700"
                                : "bg-muted/30 border-transparent text-muted-foreground"
                            )}
                          >
                            <span>{c.answer}</span>
                            {c.is_correct && <CheckCircle className="h-4 w-4 text-green-600 shrink-0" />}
                          </div>
                        );
                      })}
                    </div>
                  )}

                  {/* Essay Answer review */}
                  {isEssay && (
                    <div className="bg-muted/30 p-3 rounded-xl border text-sm space-y-1">
                      <p className="text-xs text-muted-foreground font-bold font-bold">إجابتك المكتوبة:</p>
                      <p className="text-foreground whitespace-pre-wrap">{studentAnswer?.answer || "لا يوجد إجابة"}</p>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        )}
      </div>
    );
  }

  // Render Taking Exam/Assignment View
  if (activeAttempt && exam.questions.length > 0) {
    const currentQuestion = exam.questions[currentQuestionIndex];
    const isFirst = currentQuestionIndex === 0;
    const isLast = currentQuestionIndex === exam.questions.length - 1;
    const isEssay = currentQuestion.type === "essay";

    return (
      <div className="flex flex-col h-full space-y-6 max-w-3xl mx-auto py-2">
        {/* Quiz Header Info */}
        <div className="flex items-center justify-between border-b pb-4 shrink-0">
          <div className="space-y-1 text-right">
            <h2 className="text-lg font-bold text-foreground">{exam.title}</h2>
            <p className="text-xs text-muted-foreground">
              سؤال {currentQuestionIndex + 1} من {exam.questions.length} • درجة السؤال: {currentQuestion.degree} نقاط
            </p>
          </div>

          {/* Circular/Text Timer */}
          <div className="flex items-center gap-2 bg-red-500/10 text-red-600 px-3.5 py-1.5 rounded-full font-bold text-sm select-none">
            <Timer className="h-4 w-4" />
            <span>{formatTime(timeLeft)}</span>
          </div>
        </div>

        {/* Progress Bar */}
        <div className="h-1.5 w-full bg-muted rounded-full overflow-hidden shrink-0">
          <div
            className="h-full bg-primary transition-all duration-300"
            style={{ width: `${((currentQuestionIndex + 1) / exam.questions.length) * 100}%` }}
          />
        </div>

        {/* Question Panel */}
        <div className="flex-grow space-y-5 animate-in fade-in duration-300">
          {currentQuestion.image_path && (
            <div className="w-full max-h-[300px] overflow-hidden rounded-2xl border">
              <img
                src={currentQuestion.image_path}
                alt="سؤال توضيحي"
                className="w-full h-full object-contain bg-muted"
              />
            </div>
          )}

          <div className="bg-card border rounded-2xl p-6 shadow-sm space-y-6">
            <h3 className="text-lg md:text-xl font-bold text-foreground leading-relaxed whitespace-pre-wrap">
              {currentQuestion.question}
            </h3>

            {/* Answer Controls */}
            {isEssay ? (
              <textarea
                value={answers[currentQuestion.id] || ""}
                onChange={(e) => handleSelectAnswer(currentQuestion.id, e.target.value)}
                placeholder="اكتب إجابتك هنا بالتفصيل..."
                className="w-full min-h-[150px] p-4 border rounded-xl focus:ring-1 focus:ring-primary focus:border-primary resize-none text-sm leading-relaxed text-right"
              />
            ) : (
              <div className="grid grid-cols-1 gap-3">
                {currentQuestion.choices?.map((choice) => {
                  const isSelected = answers[currentQuestion.id] === choice.id;
                  return (
                    <button
                      key={choice.id}
                      onClick={() => handleSelectAnswer(currentQuestion.id, choice.id)}
                      className={cn(
                        "p-4 rounded-xl border text-right transition-all flex items-center justify-between text-sm group hover:scale-[1.01]",
                        isSelected
                          ? "bg-primary/10 border-primary text-primary font-bold shadow-sm"
                          : "bg-card border-border hover:bg-muted/40 hover:border-muted-foreground/30 text-foreground"
                      )}
                    >
                      <span>{choice.answer}</span>
                      <div
                        className={cn(
                          "w-5 h-5 rounded-full border flex items-center justify-center shrink-0 transition-all",
                          isSelected
                            ? "border-primary bg-primary text-primary-foreground"
                            : "border-muted-foreground/30 group-hover:border-muted-foreground"
                        )}
                      >
                        {isSelected && <div className="w-2 h-2 rounded-full bg-white" />}
                      </div>
                    </button>
                  );
                })}
              </div>
            )}
          </div>
        </div>

        {/* Question Quick Jump Map (Premium UI/UX element) */}
        <div className="flex flex-wrap gap-2 justify-center py-2 shrink-0 select-none">
          {exam.questions.map((q, idx) => {
            const isAnswered = !!answers[q.id];
            const isActive = idx === currentQuestionIndex;
            return (
              <button
                key={q.id}
                onClick={() => setCurrentQuestionIndex(idx)}
                className={cn(
                  "w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold transition-all border",
                  isActive
                    ? "bg-primary text-primary-foreground border-primary shadow-sm scale-110"
                    : isAnswered
                    ? "bg-green-500/10 border-green-200 text-green-600 font-semibold"
                    : "bg-muted border-transparent text-muted-foreground hover:border-border"
                )}
              >
                {idx + 1}
              </button>
            );
          })}
        </div>

        {/* Footer Navigation Controls */}
        <div className="flex items-center justify-between pt-4 border-t shrink-0">
          <button
            onClick={() => setCurrentQuestionIndex((prev) => Math.max(0, prev - 1))}
            disabled={isFirst}
            className="flex items-center gap-2 px-5 py-2.5 rounded-xl border font-bold hover:bg-muted transition-colors disabled:opacity-40 disabled:pointer-events-none text-sm"
          >
            <ChevronRight className="h-4 w-4" />
            السابق
          </button>

          {isLast ? (
            <button
              onClick={() => handleSubmit(false)}
              disabled={isSubmitting}
              className="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-primary text-primary-foreground font-bold hover:bg-primary/95 transition-colors disabled:opacity-50 text-sm shadow-md"
            >
              {isSubmitting ? "جاري التسليم..." : `تسليم ${label}`}
              <CheckCircle className="h-4 w-4" />
            </button>
          ) : (
            <button
              onClick={() => setCurrentQuestionIndex((prev) => Math.min(exam.questions.length - 1, prev + 1))}
              className="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-secondary text-secondary-foreground font-bold hover:bg-secondary/90 transition-colors text-sm"
            >
              التالي
              <ChevronLeft className="h-4 w-4" />
            </button>
          )}
        </div>
      </div>
    );
  }

  // Render Start/Result Overview
  return (
    <div className="max-w-xl mx-auto py-8 space-y-6 text-center">
      <div className="inline-flex p-4 rounded-full bg-primary/10 text-primary">
        <Award className="h-16 w-16" />
      </div>

      <div className="space-y-2">
        <h2 className="text-xl md:text-2xl font-bold text-foreground">{exam.title}</h2>
        <p className="text-xs md:text-sm text-muted-foreground">
          يرجى قراءة الأسئلة جيداً قبل الإجابة. بمجرد بدء المحاولة سيبدأ عداد الوقت بالعد التنازلي.
        </p>
      </div>

      <div className="grid grid-cols-2 gap-4 max-w-sm mx-auto">
        <div className="p-4 bg-muted/40 rounded-2xl border text-center">
          <p className="text-xs text-muted-foreground mb-1">عدد الأسئلة</p>
          <p className="text-lg font-bold text-foreground">{exam.questions.length} أسئلة</p>
        </div>
        <div className="p-4 bg-muted/40 rounded-2xl border text-center">
          <p className="text-xs text-muted-foreground mb-1">زمن الإجابة</p>
          <p className="text-lg font-bold text-foreground">{exam.duration} دقيقة</p>
        </div>
      </div>

      {latestAttempt && latestAttempt.submitted_at && (
        <div className="bg-card border p-4 rounded-2xl max-w-sm mx-auto flex items-center justify-between text-right shadow-sm">
          <div>
            <p className="text-xs text-muted-foreground">المحاولة الأخيرة</p>
            <p className={cn("text-base font-bold mt-0.5", latestAttempt.score >= exam.pass_percentage ? "text-green-500" : "text-red-500")}>
              النتيجة: {latestAttempt.score}% ({latestAttempt.score >= exam.pass_percentage ? "ناجح" : "راسب"})
            </p>
          </div>
          <button
            onClick={() => loadGradedResult(latestAttempt.id)}
            className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-bold hover:bg-muted transition-colors"
          >
            <Eye className="h-3.5 w-3.5" />
            عرض تفاصيلها
          </button>
        </div>
      )}

      <div className="pt-4">
        <button
          onClick={handleStart}
          className="inline-flex items-center gap-2 px-8 py-3 rounded-full bg-primary text-primary-foreground font-bold hover:bg-primary/95 transition-all hover:scale-105 active:scale-95 shadow-md shadow-primary/20 text-sm"
        >
          <Play className="h-4 w-4 fill-current" />
          {latestAttempt ? `بدء محاولة جديدة` : `بدء ${label} الآن`}
        </button>
      </div>
    </div>
  );
}
