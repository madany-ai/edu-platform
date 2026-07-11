export interface Exam {
  id: string;
  lecture_id: string;
  title: string;
  duration: number;
  is_assignment: boolean;
  questions: ExamQuestion[];
}

export interface ExamQuestion {
  id: string;
  type: "multiple_choice" | "true_false";
  question: string;
  degree: number;
  choices: ExamChoice[];
}

export interface ExamChoice {
  id: string;
  answer: string;
  is_correct?: boolean;
}

export interface ExamAttempt {
  id: string;
  exam_id: string;
  student_id: string;
  score: number | null;
  started_at: string | null;
  submitted_at: string | null;
  answers?: ExamAnswer[];
}

export interface ExamAnswer {
  id: string;
  question_id: string;
  answer: string;
  score: number | null;
  question?: ExamQuestion;
}
