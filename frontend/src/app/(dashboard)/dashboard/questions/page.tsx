"use client";

import { useState } from "react";
import Link from "next/link";
import { useMyQuestions, useDeleteQuestion, useReplyToQuestion, useDeleteReply, useQAReplyTracker } from "@/hooks/useQA";
import { useAuth } from "@/providers/auth-provider";
import { PageLoading } from "@/components/shared/loading-spinner";
import { MessageCircle, ChevronDown, ChevronUp, Trash2, Send, ExternalLink, BookOpen } from "lucide-react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import type { Question, QuestionReply } from "@/types/qa.types";

export default function MyQuestionsPage() {
  const { user } = useAuth();
  const [page, setPage] = useState(1);
  const [expandedQuestions, setExpandedQuestions] = useState<Set<string>>(new Set());
  const [replyingTo, setReplyingTo] = useState<string | null>(null);
  const [replyBody, setReplyBody] = useState("");

  const { data: questionsResponse, isLoading, error, refetch } = useMyQuestions({ page, per_page: 10 });
  const deleteQuestionMutation = useDeleteQuestion();
  const replyMutation = useReplyToQuestion(replyingTo || "");
  const deleteReplyMutation = useDeleteReply(replyingTo || "");

  const questions = questionsResponse?.data || [];
  const meta = questionsResponse?.meta;

  useQAReplyTracker(questions);

  const toggleExpand = (questionId: string) => {
    setExpandedQuestions(prev => {
      const next = new Set(prev);
      if (next.has(questionId)) next.delete(questionId);
      else next.add(questionId);
      return next;
    });
  };

  const handleSubmitReply = (questionId: string) => {
    if (!replyBody.trim()) return;
    replyMutation.mutate({ body: replyBody.trim() }, {
      onSuccess: () => {
        setReplyBody("");
        setReplyingTo(null);
      },
    });
  };

  if (isLoading) return <PageLoading />;

  if (error) {
    return (
      <div className="p-6 lg:p-10 flex flex-col items-center justify-center min-h-[50vh] space-y-4">
        <p className="text-muted-foreground">فشل تحميل الأسئلة</p>
        <Button variant="outline" onClick={() => refetch()}>إعادة المحاولة</Button>
      </div>
    );
  }

  return (
    <div className="p-6 lg:p-10 space-y-8 max-w-7xl mx-auto">
      {/* Header */}
      <div className="p-6 rounded-2xl bg-linear-to-r from-primary/10 via-secondary/5 to-transparent border border-white/5">
        <h1 className="text-3xl font-extrabold text-gradient mb-2">
          أسئلتي 💬
        </h1>
        <p className="text-sm text-muted-foreground">
          تابع أسئلتك والأجوبة عليها من المدرس والمساعدين في جميع المحاضرات.
        </p>
      </div>

      {/* Stats */}
      {meta && meta.total > 0 && (
        <div className="flex gap-4">
          <div className="glass-card px-5 py-3 rounded-xl border border-white/5 flex items-center gap-3">
            <div className="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
              <MessageCircle className="h-4 w-4 text-primary" />
            </div>
            <div>
              <p className="text-lg font-bold text-foreground">{meta.total}</p>
              <p className="text-xs text-muted-foreground">سؤال</p>
            </div>
          </div>
        </div>
      )}

      {/* Questions List */}
      {questions.length === 0 ? (
        <div className="glass-card p-12 rounded-2xl text-center border border-white/5 max-w-2xl mx-auto space-y-4">
          <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 border border-primary/20 text-primary mx-auto cosmic-border-glow">
            <MessageCircle className="h-8 w-8" />
          </div>
          <div className="space-y-2">
            <h3 className="text-lg font-bold text-foreground">لا توجد أسئلة بعد</h3>
            <p className="text-sm text-muted-foreground max-w-md mx-auto leading-relaxed">
              عند مشاهدة أي محاضرة، يمكنك كتابة سؤالك في تبويب &quot;الأسئلة والأجوبة&quot; وسيتم إشعار المدرس والمساعدين.
            </p>
          </div>
        </div>
      ) : (
        <div className="space-y-4">
          {questions.map((question) => (
            <QuestionCard
              key={question.id}
              question={question}
              currentUser={user}
              isExpanded={expandedQuestions.has(question.id)}
              onToggleExpand={() => toggleExpand(question.id)}
              onReply={() => {
                setReplyingTo(replyingTo === question.id ? null : question.id);
                setReplyBody("");
              }}
              isReplying={replyingTo === question.id}
              replyBody={replyBody}
              onReplyBodyChange={setReplyBody}
              onSubmitReply={() => handleSubmitReply(question.id)}
              isReplyPending={replyMutation.isPending}
              onDeleteQuestion={() => deleteQuestionMutation.mutate(question.id)}
              onDeleteReply={(replyId) => deleteReplyMutation.mutate(replyId)}
            />
          ))}

          {/* Pagination */}
          {meta && meta.last_page > 1 && (
            <div className="flex items-center justify-center gap-2 pt-4">
              <button
                onClick={() => setPage(p => Math.max(1, p - 1))}
                disabled={page === 1}
                className={cn(
                  "px-3 py-1.5 rounded-lg text-sm font-medium border transition-colors",
                  page === 1 ? "opacity-50 cursor-not-allowed" : "hover:bg-muted"
                )}
              >
                السابق
              </button>
              <span className="text-sm text-muted-foreground px-2">
                صفحة {meta.current_page} من {meta.last_page}
              </span>
              <button
                onClick={() => setPage(p => Math.min(meta.last_page, p + 1))}
                disabled={page === meta.last_page}
                className={cn(
                  "px-3 py-1.5 rounded-lg text-sm font-medium border transition-colors",
                  page === meta.last_page ? "opacity-50 cursor-not-allowed" : "hover:bg-muted"
                )}
              >
                التالي
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

interface QuestionCardProps {
  question: Question;
  currentUser: any;
  isExpanded: boolean;
  onToggleExpand: () => void;
  onReply: () => void;
  isReplying: boolean;
  replyBody: string;
  onReplyBodyChange: (value: string) => void;
  onSubmitReply: () => void;
  isReplyPending: boolean;
  onDeleteQuestion: () => void;
  onDeleteReply: (replyId: string) => void;
}

function QuestionCard({
  question,
  currentUser,
  isExpanded,
  onToggleExpand,
  onReply,
  isReplying,
  replyBody,
  onReplyBodyChange,
  onSubmitReply,
  isReplyPending,
  onDeleteQuestion,
  onDeleteReply,
}: QuestionCardProps) {
  const hasReplies = question.replies_count > 0;
  const lectureCourseId = question.lecture?.course?.id;

  return (
    <div className="glass-card rounded-2xl border border-white/5 overflow-hidden">
      {/* Question Header */}
      <div className="p-5">
        <div className="flex items-start gap-3">
          <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-sm shrink-0">
            {question.author_name.charAt(0)}
          </div>
          <div className="flex-grow min-w-0">
            <div className="flex items-center gap-2 mb-1 flex-wrap">
              <span className="text-sm font-bold text-foreground">{question.author_name}</span>
              <span className="text-xs text-muted-foreground">
                {new Date(question.created_at).toLocaleDateString("ar-EG", {
                  year: "numeric",
                  month: "short",
                  day: "numeric",
                  hour: "2-digit",
                  minute: "2-digit",
                })}
              </span>
            </div>
            <p className="text-sm text-foreground leading-relaxed whitespace-pre-wrap">{question.body}</p>
          </div>
          <button
            onClick={onDeleteQuestion}
            className="text-muted-foreground hover:text-destructive transition-colors shrink-0 p-1"
            title="حذف السؤال"
          >
            <Trash2 className="h-4 w-4" />
          </button>
        </div>

        {/* Lecture Info + Actions */}
        <div className="flex items-center justify-between mt-4 ml-13">
          <div className="flex items-center gap-3">
            {/* Lecture badge */}
            <div className="flex items-center gap-1.5 text-xs text-muted-foreground bg-muted/50 px-2.5 py-1 rounded-lg">
              <BookOpen className="h-3 w-3" />
              {question.lecture.title}
            </div>
            {/* Replies count badge */}
            {hasReplies && (
              <div className="flex items-center gap-1 text-xs text-primary bg-primary/10 px-2.5 py-1 rounded-lg font-medium">
                <MessageCircle className="h-3 w-3" />
                {question.replies_count} {question.replies_count === 1 ? "رد" : "ردود"}
              </div>
            )}
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={onReply}
              className="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-primary transition-colors px-2 py-1"
            >
              <MessageCircle className="h-3.5 w-3.5" />
              رد
            </button>
            {hasReplies && (
              <button
                onClick={onToggleExpand}
                className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors px-2 py-1"
              >
                {isExpanded ? <ChevronUp className="h-3.5 w-3.5" /> : <ChevronDown className="h-3.5 w-3.5" />}
                {isExpanded ? "طي" : "عرض الردود"}
              </button>
            )}
            {lectureCourseId && (
              <Link
                href={`/courses/${lectureCourseId}/lectures/${question.lecture.id}`}
                className="flex items-center gap-1 text-xs text-primary hover:text-primary/80 transition-colors px-2 py-1"
              >
                <ExternalLink className="h-3 w-3" />
                الذهاب للمحاضرة
              </Link>
            )}
          </div>
        </div>
      </div>

      {/* Replies */}
      {hasReplies && isExpanded && (
        <div className="border-t border-white/5 bg-muted/10">
          {question.replies.map((reply) => (
            <ReplyCard
              key={reply.id}
              reply={reply}
              currentUser={currentUser}
              onDelete={() => onDeleteReply(reply.id)}
              isOwnReply={reply.user.id === currentUser?.id}
            />
          ))}
        </div>
      )}

      {/* Reply Form */}
      {isReplying && (
        <div className="border-t border-white/5 p-4 bg-muted/10">
          <div className="flex gap-3">
            <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs shrink-0">
              {currentUser?.name?.charAt(0) || "U"}
            </div>
            <div className="flex-grow">
              <textarea
                className="w-full bg-background border rounded-lg p-2.5 text-sm focus:ring-1 focus:ring-primary focus:border-primary min-h-[80px] transition-all resize-none"
                placeholder="اكتب ردك..."
                value={replyBody}
                onChange={(e) => onReplyBodyChange(e.target.value)}
                disabled={isReplyPending}
                autoFocus
              />
              <div className="flex justify-end gap-2 mt-2">
                <button
                  onClick={onReply}
                  className="px-3 py-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors"
                >
                  إلغاء
                </button>
                <button
                  onClick={onSubmitReply}
                  disabled={!replyBody.trim() || isReplyPending}
                  className={cn(
                    "flex items-center gap-1.5 bg-primary text-primary-foreground px-3 py-1.5 rounded-lg text-xs font-medium transition-colors",
                    replyBody.trim() && !isReplyPending
                      ? "hover:bg-primary/90"
                      : "opacity-50 cursor-not-allowed"
                  )}
                >
                  <Send className="h-3 w-3" />
                  {isReplyPending ? "جاري الإرسال..." : "إرسال"}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

interface ReplyCardProps {
  reply: QuestionReply;
  currentUser: any;
  onDelete: () => void;
  isOwnReply: boolean;
}

function ReplyCard({ reply, currentUser, onDelete, isOwnReply }: ReplyCardProps) {
  return (
    <div className="px-5 py-3 border-b border-white/5 last:border-b-0 ml-6">
      <div className="flex items-start gap-3">
        <div className="w-7 h-7 rounded-full bg-secondary/50 flex items-center justify-center text-secondary-foreground text-xs font-bold shrink-0">
          {reply.user.name.charAt(0)}
        </div>
        <div className="flex-grow min-w-0">
          <div className="flex items-center gap-2 mb-1">
            <span className="text-xs font-semibold text-foreground">{reply.user.name}</span>
            <span className="text-[10px] text-muted-foreground">
              {new Date(reply.created_at).toLocaleDateString("ar-EG", {
                year: "numeric",
                month: "short",
                day: "numeric",
                hour: "2-digit",
                minute: "2-digit",
              })}
            </span>
          </div>
          <p className="text-sm text-foreground leading-relaxed whitespace-pre-wrap">{reply.body}</p>
        </div>
        {isOwnReply && (
          <button
            onClick={onDelete}
            className="text-muted-foreground hover:text-destructive transition-colors shrink-0 p-1"
            title="حذف الرد"
          >
            <Trash2 className="h-3.5 w-3.5" />
          </button>
        )}
      </div>
    </div>
  );
}
