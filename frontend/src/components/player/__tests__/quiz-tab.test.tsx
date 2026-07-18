import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import QuizTab from "../quiz-tab";
import api from "@/services/api.client";
import { useQueryClient } from "@tanstack/react-query";

vi.mock("@/services/api.client", () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}));

vi.mock("@tanstack/react-query", () => ({
  useQueryClient: vi.fn(),
}));

vi.mock("next/navigation", () => ({
  useParams: () => ({ id: "course-1" }),
}));

vi.mock("sonner", () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
  },
}));

vi.mock("lucide-react", () => ({
  CheckCircle: () => <span>CheckCircle</span>,
  AlertCircle: () => <span>AlertCircle</span>,
  Timer: () => <span>Timer</span>,
  Award: () => <span>Award</span>,
  Play: () => <span>Play</span>,
  ChevronLeft: () => <span>ChevronLeft</span>,
  ChevronRight: () => <span>ChevronRight</span>,
  RefreshCw: () => <span>RefreshCw</span>,
  Eye: () => <span>Eye</span>,
}));

describe("QuizTab", () => {
  const mockInvalidateQueries = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(useQueryClient).mockReturnValue({
      invalidateQueries: mockInvalidateQueries,
    } as any);
  });

  it("should show loading state initially", () => {
    vi.mocked(api.get).mockResolvedValue({
      data: { exam: null, latest_attempt: null },
    });

    render(<QuizTab lectureId="l1" />);

    expect(screen.getByText(/جاري تحميل بيانات/)).toBeInTheDocument();
  });

  it("should show error state when exam fetch fails", async () => {
    vi.mocked(api.get).mockRejectedValue({
      response: { data: { message: "Exam not found" } },
    });

    render(<QuizTab lectureId="l1" />);

    await waitFor(() => {
      expect(screen.getByText("Exam not found")).toBeInTheDocument();
    });
  });

  it("should display exam info when loaded", async () => {
    const mockExam = {
      id: "exam-1",
      title: "Test Exam",
      duration: 30,
      pass_percentage: 60,
      is_blocking: false,
      questions: [
        { id: "q1", type: "multiple_choice", question: "Q1", degree: 10, choices: [] },
      ],
    };

    vi.mocked(api.get).mockResolvedValue({
      data: { exam: mockExam, latest_attempt: null },
    });

    render(<QuizTab lectureId="l1" />);

    await waitFor(() => {
      expect(screen.getByText("Test Exam")).toBeInTheDocument();
      expect(screen.getByText("1 أسئلة")).toBeInTheDocument();
      expect(screen.getByText("30 دقيقة")).toBeInTheDocument();
    });
  });

  it("should have double-submit guard in handleSubmit", async () => {
    const mockExam = {
      id: "exam-1",
      title: "Test Exam",
      duration: 30,
      pass_percentage: 60,
      is_blocking: false,
      questions: [
        { id: "q1", type: "multiple_choice", question: "Q1", degree: 10, choices: [] },
      ],
    };

    vi.mocked(api.get).mockResolvedValue({
      data: { exam: mockExam, latest_attempt: null },
    });

    // Mock start exam
    vi.mocked(api.post).mockResolvedValue({
      data: { id: "attempt-1", score: 0, started_at: new Date().toISOString(), submitted_at: null },
    });

    render(<QuizTab lectureId="l1" />);

    await waitFor(() => {
      expect(screen.getByText("Test Exam")).toBeInTheDocument();
    });

    // Click start button
    const startButton = screen.getByText(/بدء الاختبار الآن/);
    await userEvent.click(startButton);

    await waitFor(() => {
      expect(api.post).toHaveBeenCalledWith("/exams/exam-1/start");
    });
  });
});
