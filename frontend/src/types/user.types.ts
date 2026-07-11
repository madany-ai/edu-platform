export interface User {
  id: string;
  name: string;
  email: string;
  status: "pending" | "active" | "rejected";
  roles: string[];
  student?: Student;
  created_at: string;
  updated_at: string;
}

export interface Student {
  id: string;
  user_id: string;
  student_code: string;
  first_name: string;
  second_name: string | null;
  third_name: string | null;
  last_name: string;
  phone: string | null;
  father_phone: string | null;
  mother_phone: string | null;
  guardian_job: string | null;
  gender: "male" | "female" | null;
  birth_date: string | null;
  is_verified: boolean;
}

export interface Instructor {
  id: string;
  name: string;
  email: string;
}
