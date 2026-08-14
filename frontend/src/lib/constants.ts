export const APP_NAME = "حفني محمد";
export const APP_DESCRIPTION = "منصة تعليمية شاملة للطلاب والمعلمين";

export const ROUTES = {
  HOME: "/",
  LOGIN: "/login",
  REGISTER: "/register",
  FORGOT_PASSWORD: "/forgot-password",
  VERIFY_EMAIL: "/verify-email",
  COURSES: "/courses",
  COURSE_DETAIL: (id: string) => `/courses/${id}`,
  LECTURE: (courseId: string, lectureId: string) => `/courses/${courseId}/lectures/${lectureId}`,
  EXAM: (courseId: string, lectureId: string) => `/courses/${courseId}/lectures/${lectureId}/exam`,
  DASHBOARD: "/dashboard",
  MY_QUESTIONS: "/dashboard/questions",

  ABOUT: "/about",
} as const;

export const ROLES = {
  STUDENT: "student",
  INSTRUCTOR: "instructor",
  ASSISTANT: "assistant",
  SUPER_ADMIN: "super_admin",
} as const;

export const COURSE_STATUS = {
  DRAFT: "draft",
  PUBLISHED: "published",
  ARCHIVED: "archived",
} as const;

export const SUBSCRIPTION_STATUS = {
  ACTIVE: 'active',
  INACTIVE: 'inactive',
  CANCELLED: 'cancelled',
} as const;

export const GRADE_LEVELS = [
  { id: "prep_1", name: "الصف الأول الإعدادي" },
  { id: "prep_2", name: "الصف الثاني الإعدادي" },
  { id: "prep_3", name: "الصف الثالث الإعدادي" },
  { id: "sec_1", name: "الصف الأول الثانوي" },
  { id: "sec_2", name: "الصف الثاني الثانوي" },
  { id: "sec_3", name: "الصف الثالث الثانوي" },
];

export const ENROLLMENT_STATUS = {
  ACTIVE: "active",
  EXPIRED: "expired",
  SUSPENDED: "suspended",
} as const;

export const USER_STATUS = {
  PENDING: "pending",
  ACTIVE: "active",
  REJECTED: "rejected",
} as const;

export const STORAGE_KEYS = {
  TOKEN: "edu_platform_token",
  THEME: "edu_platform_theme",
} as const;
