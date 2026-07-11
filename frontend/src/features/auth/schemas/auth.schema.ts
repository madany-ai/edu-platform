import { z } from "zod";

export const loginSchema = z.object({
  email: z.string().min(1, "البريد الإلكتروني أو رقم الهاتف مطلوب"),
  password: z.string().min(1, "كلمة المرور مطلوبة"),
});

export type LoginFormData = z.infer<typeof loginSchema>;

export const registerStep1Schema = z
  .object({
    email: z.string().email("البريد الإلكتروني غير صحيح"),
    password: z.string().min(8, "كلمة المرور يجب أن تكون 8 أحرف على الأقل"),
    password_confirmation: z.string(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "كلمتا المرور غير متطابقتين",
    path: ["password_confirmation"],
  });

export type RegisterStep1Data = z.infer<typeof registerStep1Schema>;

export const registerStep2Schema = z.object({
  first_name: z.string().min(1, "الاسم الأول مطلوب"),
  second_name: z.string().min(1, "الاسم الثاني مطلوب"),
  third_name: z.string().min(1, "الاسم الثالث مطلوب"),
  last_name: z.string().min(1, "الاسم الأخير مطلوب"),
  phone: z.string().min(1, "رقم الهاتف مطلوب"),
  gender: z.enum(["male", "female"], "الجنس مطلوب"),
  birth_date: z.string().min(1, "تاريخ الميلاد مطلوب"),
});

export type RegisterStep2Data = z.infer<typeof registerStep2Schema>;

export const registerStep3Schema = z.object({
  father_phone: z.string().min(1, "رقم هاتف الأب مطلوب"),
  mother_phone: z.string().min(1, "رقم هاتف الأم مطلوب"),
  guardian_job: z.string().min(1, "وظيفة ولي الأمر مطلوبة"),
});

export type RegisterStep3Data = z.infer<typeof registerStep3Schema>;

export const registerSchema = registerStep1Schema
  .merge(registerStep2Schema)
  .merge(registerStep3Schema);

export type RegisterFormData = z.infer<typeof registerSchema>;
