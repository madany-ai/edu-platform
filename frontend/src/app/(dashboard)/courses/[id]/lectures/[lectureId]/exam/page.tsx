"use client";

import { useEffect, useState, useCallback, useRef } from "react";
import { useRouter, useParams } from "next/navigation";
import { useAuth } from "@/providers/auth-provider";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { PageLoading } from "@/components/shared/loading-spinner";
import { examService, type SubmitAnswer } from "@/services/exam.service";
import type { Exam, ExamAttempt } from "@/types";
import { ArrowRight, Clock, CheckCircle, XCircle, Loader2 } from "lucide-react";
import Link from "next/link";

export default function ExamPage() {
  const { user, loading: authLoading } = useAuth();
  const router = useRouter();
  const params = useParams();

  const [exam, setExam] = useState<Exam | null>(null);
  const [attempt, setAttempt] = useState<ExamAttempt | null>(null);
  const [answers, setAnswers] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [timeLeft, setTimeLeft] = useState(0);

  const answersRef = useRef(answers);
  const attemptRef = useRef(attempt);

  useEffect(() => {
    answersRef.current = answers;
  });

  useEffect(() => {
    attemptRef.current = attempt;
  });

  const doSubmit = useCallback(async () => {
    if (!attemptRef.current) return;
    setSubmitting(true);
    try {
      const answerArray: SubmitAnswer[] = Object.entries(answersRef.current).map(
        ([questionId, answer]) => ({
          question_id: questionId,
          answer,
        })
      );
      const result = await examService.submitAttempt(attemptRef.current.id, {
        answers: answerArray,
      });
      setAttempt(result);
    } catch {
      setSubmitting(false);
    }
  }, []);

  const doSubmitRef = useRef(doSubmit);

  useEffect(() => {
    doSubmitRef.current = doSubmit;
  });

  useEffect(() => {
    if (authLoading) return;
    if (!user) {
      router.push("/login");
      return;
    }

    examService
      .getLectureExam(params.lectureId as string)
      .then((data) => {
        setExam(data);
        if (data) {
          examService.startAttempt(data.id).then((a) => {
            setAttempt(a);
            if (a.started_at) {
              const elapsed = Math.floor(
                (Date.now() - new Date(a.started_at).getTime()) / 1000
              );
              const remaining = data.duration * 60 - elapsed;
              setTimeLeft(Math.max(0, remaining));
            }
          });
        }
      })
      .finally(() => setLoading(false));
  }, [user, authLoading, router, params.lectureId]);

  useEffect(() => {
    if (timeLeft <= 0 || attempt?.submitted_at) return;
    const timer = setInterval(() => {
      setTimeLeft((prev) => {
        if (prev <= 1) {
          clearInterval(timer);
          doSubmitRef.current();
          return 0;
        }
        return prev - 1;
      });
    }, 1000);
    return () => clearInterval(timer);
  }, [timeLeft, attempt?.submitted_at]);

  if (authLoading || loading) return <PageLoading />;

  if (!exam) {
    return (
      <div className="mx-auto max-w-3xl px-4 py-10 text-center">
        <p className="text-muted-foreground mb-4">لا يوجد امتحان لهذه المحاضرة</p>
        <Link href={`/courses/${params.id}`}>
          <Button variant="outline">العودة للدورة</Button>
        </Link>
      </div>
    );
  }

  if (attempt?.submitted_at) {
    return (
      <div className="mx-auto max-w-3xl px-4 py-10">
        <Link
          href={`/courses/${params.id}`}
          className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground mb-6"
        >
          <ArrowRight className="h-4 w-4" />
          العودة للدورة
        </Link>

        <Card>
          <CardHeader className="text-center">
            <CardTitle className="text-2xl">نتيجة الامتحان</CardTitle>
          </CardHeader>
          <CardContent className="text-center space-y-6">
            <div
              className={`text-6xl font-bold ${
                (attempt.score ?? 0) >= 50 ? "text-green-600" : "text-red-600"
              }`}
            >
              {attempt.score}%
            </div>
            <p className="text-muted-foreground">
              {(attempt.score ?? 0) >= 50
                ? "أحسنت! لقد نجحت في الامتحان"
                : "لم تحقق النسبة المطلوبة. حظاً أوفر المرة القادمة"}
            </p>
            <div className="flex items-center justify-center gap-4 text-sm text-muted-foreground">
              <span className="flex items-center gap-1">
                <CheckCircle className="h-4 w-4 text-green-500" />
                الإجابات الصحيحة:{" "}
                {attempt.answers?.filter((a) =>
                  a.question?.choices?.some(
                    (c) => c.id === a.answer && c.is_correct
                  )
                ).length ?? 0}
              </span>
              <span className="flex items-center gap-1">
                <XCircle className="h-4 w-4 text-red-500" />
                الإجابات الخاطئة:{" "}
                {(attempt.answers?.length ?? 0) -
                  (attempt.answers?.filter((a) =>
                    a.question?.choices?.some(
                      (c) => c.id === a.answer && c.is_correct
                    )
                  ).length ?? 0)}
              </span>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  const formatTime = (seconds: number) => {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${s.toString().padStart(2, "0")}`;
  };

  return (
    <div className="mx-auto max-w-3xl px-4 py-10">
      <Link
        href={`/courses/${params.id}`}
        className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground mb-6"
      >
        <ArrowRight className="h-4 w-4" />
        العودة للدورة
      </Link>

      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold">{exam.title}</h1>
        <div className="flex items-center gap-2 text-sm">
          <Clock className="h-4 w-4" />
          <span className={timeLeft < 60 ? "text-red-600 font-bold" : ""}>
            {formatTime(timeLeft)}
          </span>
        </div>
      </div>

      <div className="space-y-6">
        {exam.questions.map((question, idx) => (
          <Card key={question.id}>
            <CardHeader>
              <CardTitle className="text-base">
                <span className="text-muted-foreground ml-2">السؤال {idx + 1}</span>
                {question.question}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                {question.choices.map((choice) => (
                  <label
                    key={choice.id}
                    className={`flex items-center gap-3 rounded-lg border p-3 cursor-pointer transition-colors ${
                      answers[question.id] === choice.id
                        ? "border-primary bg-primary/5"
                        : "hover:bg-muted"
                    }`}
                  >
                    <input
                      type="radio"
                      name={`q_${question.id}`}
                      value={choice.id}
                      checked={answers[question.id] === choice.id}
                      onChange={() =>
                        setAnswers((prev) => ({ ...prev, [question.id]: choice.id }))
                      }
                      className="h-4 w-4 accent-primary"
                    />
                    <span className="flex-1 text-sm">{choice.answer}</span>
                  </label>
                ))}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="mt-8 flex justify-end">
        <Button onClick={doSubmit} disabled={submitting} size="lg">
          {submitting && <Loader2 className="ml-2 h-4 w-4 animate-spin" />}
          تسليم الامتحان
        </Button>
      </div>
    </div>
  );
}
