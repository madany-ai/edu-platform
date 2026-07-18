"use client";

import { useState } from "react";
import { useAuth } from "@/providers/auth-provider";
import { useLectureQuestions, usePostQuestion, useReplyToQuestion, useDeleteQuestion, useDeleteReply, useQAReplyTracker } from "@/hooks/useQA";
import type { Question, QuestionReply } from "@/types/qa.types";
import { LoadingSpinner } from "@/components/shared/loading-spinner";
import { MessageCircle, Send, Trash2, ChevronDown, ChevronUp, UserCircle } from "lucide-react";
import { cn } from "@/lib/utils";

interface QATabProps {
  lectureId: string;
}

export default function QATab({ lectureId }: QATabProps) {
  const { user } = useAuth();
  const [questionBody, setQuestionBody] = useState("");
  const [expandedQuestions, setExpandedQuestions] = useState<Set<string>>(new Set());
  const [replyingTo, setReplyingTo] = useState<string | null>(null);
  const [replyBody, setReplyBody] = useState("");
  const [page, setPage] = useState(1);

  const { data: questionsResponse, isLoading } = useLectureQuestions(lectureId, { page, per_page: 10 });
  const postQuestionMutation = usePostQuestion(lectureId);
  const replyMutation = useReplyToQuestion(replyingTo || "", lectureId);
  const deleteQuestionMutation = useDeleteQuestion(lectureId);
  const deleteReplyMutation = useDeleteReply(replyingTo || "", lectureId);

  const questions = questionsResponse?.data || [];
  const meta = questionsResponse?.meta;

  useQAReplyTracker(questions);

  const handleSubmitQuestion = () => {
    if (!questionBody.trim()) return;
    postQuestionMutation.mutate({ body: questionBody.trim() }, {
      onSuccess: () => setQuestionBody(""),
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

  const toggleExpand = (questionId: string) => {
    setExpandedQuestions(prev => {
      const next = new Set(prev);
      if (next.has(questionId)) next.delete(questionId);
      else next.add(questionId);
      return next;
    });
  };

  const handleDeleteQuestion = (questionId: string) => {
    deleteQuestionMutation.mutate(questionId);
  };

  const handleDeleteReply = (replyId: string) => {
    deleteReplyMutation.mutate(replyId);
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-16">
        <LoadingSpinner size="md" />
      </div>
    );
  }

  return (
    <div className="space-y-6 animate-in fade-in duration-300">
      {/* Post Question Form */}
      <div className="flex gap-4">
        <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold shrink-0">
          {user?.name?.charAt(0) || "U"}
        </div>
        <div className="flex-grow">
          <textarea
            className="w-full bg-muted/30 border rounded-xl p-3 text-sm focus:ring-1 focus:ring-primary focus:border-primary min-h-[100px] transition-all resize-none"
            placeholder="اسأل سؤالاً حول هذا الدرس..."
            value={questionBody}
            onChange={(e) => setQuestionBody(e.target.value)}
            disabled={postQuestionMutation.isPending}
          />
          <div className="flex justify-end mt-2">
            <button
              onClick={handleSubmitQuestion}
              disabled={!questionBody.trim() || postQuestionMutation.isPending}
              className={cn(
                "flex items-center gap-2 bg-primary text-primary-foreground px-4 py-2 rounded-lg text-sm font-medium transition-colors",
                questionBody.trim() && !postQuestionMutation.isPending
                  ? "hover:bg-primary/90"
                  : "opacity-50 cursor-not-allowed"
              )}
            >
              <Send className="h-4 w-4" />
              {postQuestionMutation.isPending ? "جاري الإرسال..." : "إرسال السؤال"}
            </button>
          </div>
        </div>
      </div>

      {/* Questions List */}
      {questions.length === 0 ? (
        <div className="text-center py-10 text-muted-foreground border-t">
          <MessageCircle className="h-12 w-12 mx-auto mb-3 opacity-20" />
          <p className="text-sm">لا توجد أسئلة بعد. كن أول من يسأل!</p>
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
              onDeleteQuestion={() => handleDeleteQuestion(question.id)}
              onDeleteReply={handleDeleteReply}
              isOwnQuestion={question.student.id === user?.student?.id}
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
  isOwnQuestion: boolean;
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
  isOwnQuestion,
}: QuestionCardProps) {
  const hasReplies = question.replies_count > 0;

  return (
    <div className="border rounded-xl overflow-hidden bg-card">
      {/* Question */}
      <div className="p-4">
        <div className="flex items-start gap-3">
          <div className="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-sm shrink-0">
            {question.student.name.charAt(0)}
          </div>
          <div className="flex-grow min-w-0">
            <div className="flex items-center gap-2 mb-1">
              <span className="text-sm font-semibold">{question.student.name}</span>
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
          {isOwnQuestion && (
            <button
              onClick={onDeleteQuestion}
              className="text-muted-foreground hover:text-destructive transition-colors shrink-0 p-1"
              title="حذف السؤال"
            >
              <Trash2 className="h-4 w-4" />
            </button>
          )}
        </div>

        {/* Actions */}
        <div className="flex items-center gap-4 mt-3 mr-12">
          <button
            onClick={onReply}
            className="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-primary transition-colors"
          >
            <MessageCircle className="h-3.5 w-3.5" />
            رد
          </button>
          {hasReplies && (
            <button
              onClick={onToggleExpand}
              className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors"
            >
              {isExpanded ? <ChevronUp className="h-3.5 w-3.5" /> : <ChevronDown className="h-3.5 w-3.5" />}
              {question.replies_count} ردود
            </button>
          )}
        </div>
      </div>

      {/* Replies */}
      {hasReplies && isExpanded && (
        <div className="border-t bg-muted/20">
          {question.replies.map((reply) => (
            <ReplyCard
              key={reply.id}
              reply={reply}
              questionId={question.id}
              currentUser={currentUser}
              onDelete={onDeleteReply}
              isOwnReply={reply.user.id === currentUser?.id}
            />
          ))}
        </div>
      )}

      {/* Reply Form */}
      {isReplying && (
        <div className="border-t p-4 bg-muted/10">
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
  questionId: string;
  currentUser: any;
  onDelete: (replyId: string) => void;
  isOwnReply: boolean;
}

function ReplyCard({ reply, currentUser, onDelete, isOwnReply }: ReplyCardProps) {
  return (
    <div className="px-4 py-3 border-b last:border-b-0 mr-6">
      <div className="flex items-start gap-3">
        <div className="w-7 h-7 rounded-full bg-secondary flex items-center justify-center text-secondary-foreground text-xs font-bold shrink-0">
          {reply.user.name.charAt(0)}
        </div>
        <div className="flex-grow min-w-0">
          <div className="flex items-center gap-2 mb-1">
            <span className="text-xs font-semibold">{reply.user.name}</span>
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
            onClick={() => onDelete(reply.id)}
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
