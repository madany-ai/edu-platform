import api from "./api.client";

export interface AcademicYear {
  id: string;
  name: string;
  start_date?: string;
  end_date?: string;
  is_active: boolean;
}

export interface Group {
  id: string;
  name: string;
  academic_year: string;
  academic_year_id?: string;
  capacity: number;
  schedule?: Array<{ day: string; time: string }>;
  is_active: boolean;
  students_count?: number;
  academic_year_session?: { id: string; name: string };
}

export interface AcademicSession {
  id: string;
  group_id?: string;
  academic_year?: string;
  date: string;
  topic: string;
  notes?: string;
  group?: Group;
}

export interface AttendanceRecord {
  student_id: string;
  student_code: string;
  full_name: string;
  phone: string;
  father_phone: string;
  status: "present" | "absent" | "late" | "guest";
  is_guest: boolean;
  other_group_note?: string;
  attendance_id?: string;
}

export interface CenterExam {
  id: string;
  name: string;
  description?: string;
  total_marks: number;
  date: string;
  group_id?: string;
  semester_id?: string;
  academic_year_id?: string;
  group?: Group;
}

export interface ExamGradeRecord {
  student_id: string;
  student_code: string;
  full_name: string;
  phone: string;
  father_phone: string;
  score: number;
  notes?: string;
  grade_id?: string;
}

export interface RankingItem {
  student_id: string;
  student_code?: string;
  first_name: string;
  second_name?: string;
  third_name?: string;
  last_name: string;
  total_score: number;
  max_score: number;
  exams_count: number;
  percentage: number;
}

export interface StudentReport {
  student: any;
  attendances: any[];
  grades: any[];
  stats: {
    total_sessions: number;
    present_count: number;
    absent_count: number;
    late_count: number;
    total_exams: number;
    percentage: number;
  };
}

export const centerService = {
  // Grade Levels
  getGradeLevels: async (): Promise<Array<{ id: string; name: string }>> => {
    const { data } = await api.get<{ data: Array<{ id: string; name: string }> }>("/grade-levels");
    return data.data;
  },

  // Academic Years
  getAcademicYears: async (): Promise<AcademicYear[]> => {
    const { data } = await api.get<{ data: AcademicYear[] }>("/center/staff/academic-years");
    return data.data;
  },

  createAcademicYear: async (payload: Partial<AcademicYear>): Promise<AcademicYear> => {
    const { data } = await api.post<{ data: AcademicYear }>("/center/staff/academic-years", payload);
    return data.data;
  },

  updateAcademicYear: async (id: string, payload: Partial<AcademicYear>): Promise<AcademicYear> => {
    const { data } = await api.put<{ data: AcademicYear }>(`/center/staff/academic-years/${id}`, payload);
    return data.data;
  },

  // Groups
  getGroups: async (academicYear?: string): Promise<Group[]> => {
    const { data } = await api.get<{ data: Group[] }>("/center/staff/groups", {
      params: { academic_year: academicYear },
    });
    return data.data;
  },

  getGroupDetails: async (groupId: string): Promise<{
    group: Group;
    students: any[];
    sessions: AcademicSession[];
    exams: CenterExam[];
    rankings: RankingItem[];
  }> => {
    const { data } = await api.get(`/center/staff/groups/${groupId}`);
    return data;
  },

  createGroup: async (payload: Partial<Group>): Promise<Group> => {
    const { data } = await api.post<{ data: Group }>("/center/staff/groups", payload);
    return data.data;
  },

  updateGroup: async (id: string, payload: Partial<Group>): Promise<Group> => {
    const { data } = await api.put<{ data: Group }>(`/center/staff/groups/${id}`, payload);
    return data.data;
  },

  // Sessions & Attendance
  getSessions: async (groupId?: string): Promise<AcademicSession[]> => {
    const { data } = await api.get<{ data: AcademicSession[] }>("/center/staff/sessions", {
      params: { group_id: groupId },
    });
    return data.data;
  },

  createSession: async (payload: Partial<AcademicSession>): Promise<AcademicSession> => {
    const { data } = await api.post<{ data: AcademicSession }>("/center/staff/sessions", payload);
    return data.data;
  },

  getSessionAttendance: async (sessionId: string): Promise<{ session: AcademicSession; attendance: AttendanceRecord[] }> => {
    const { data } = await api.get<{ session: AcademicSession; attendance: AttendanceRecord[] }>(
      `/center/staff/sessions/${sessionId}/attendance`
    );
    return data;
  },

  updateAttendance: async (sessionId: string, records: Array<{ student_id: string; status: string }>): Promise<void> => {
    await api.post(`/center/staff/sessions/${sessionId}/attendance`, { records });
  },

  scanAttendance: async (sessionId: string, code: string, status?: string): Promise<any> => {
    const { data } = await api.post("/center/staff/attendance/scan", {
      session_id: sessionId,
      code,
      status,
    });
    return data;
  },

  // Exams & Grades
  getExams: async (groupId?: string): Promise<CenterExam[]> => {
    const { data } = await api.get<{ data: CenterExam[] }>("/center/staff/exams", {
      params: { group_id: groupId },
    });
    return data.data;
  },

  createExam: async (payload: Partial<CenterExam>): Promise<CenterExam> => {
    const { data } = await api.post<{ data: CenterExam }>("/center/staff/exams", payload);
    return data.data;
  },

  getExamGrades: async (examId: string): Promise<{ exam: CenterExam; grades: ExamGradeRecord[] }> => {
    const { data } = await api.get<{ exam: CenterExam; grades: ExamGradeRecord[] }>(
      `/center/staff/exams/${examId}/grades`
    );
    return data;
  },

  saveExamGrades: async (examId: string, grades: Array<{ student_id: string; score: number; notes?: string }>): Promise<void> => {
    await api.post(`/center/staff/exams/${examId}/grades`, { grades });
  },

  // Rankings
  getRankings: async (params?: { group_id?: string; academic_year?: string }): Promise<RankingItem[]> => {
    const { data } = await api.get<{ data: RankingItem[] }>("/center/staff/rankings", { params });
    return data.data;
  },

  // Students & Student Report Card
  getStudents: async (params?: { search?: string; group_id?: string; page?: number }): Promise<any> => {
    const { data } = await api.get("/center/staff/students", { params });
    return data;
  },

  createStudent: async (payload: any): Promise<any> => {
    const { data } = await api.post("/center/staff/students", payload);
    return data;
  },

  updateStudentGroup: async (studentId: string, groupId: string): Promise<any> => {
    const { data } = await api.put(`/center/staff/students/${studentId}/group`, { group_id: groupId });
    return data;
  },

  getStudentReport: async (studentId: string): Promise<StudentReport> => {
    const { data } = await api.get<StudentReport>(`/center/staff/students/${studentId}/report`);
    return data;
  },
};
