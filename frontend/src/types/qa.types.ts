export interface Question {
  id: string;
  body: string;
  student: {
    id: string;
    name: string;
    student_code: string;
  };
  lecture: {
    id: string;
    title: string;
    course: {
      id: string;
      title: string;
    } | null;
  };
  replies_count: number;
  replies: QuestionReply[];
  created_at: string;
  updated_at: string;
}

export interface QuestionReply {
  id: string;
  body: string;
  user: {
    id: string;
    name: string;
  };
  created_at: string;
  updated_at: string;
}

export interface StoreQuestionPayload {
  body: string;
}

export interface StoreReplyPayload {
  body: string;
}
