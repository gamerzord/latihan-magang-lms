export interface User {
  id: number
  name: string
  email: string
  password: string
  role: 'admin' | 'teacher' | 'student'
}

export interface LoginResponse {
  token: string
  user: User
}

export interface Course {
  id: number
  title: string
  course_code: string
  description: string
  teacher_id: number | null 
  created_at: Date | string
  updated_at: Date | string
  image?: string
  students_count?: number
  lessons_count?: number
  teacher_name?: string
  lessons?: Lesson[]
  thumbnail?: string
  assignments?: Assignment[]
}

export interface Lesson {
  id: number
  course_id: number | null
  title: string
  lesson_code: string
  content: string
  created_at: Date | string
  updated_at: Date | string
  attachments?: Array<{
    id: number
    file_name: string
    file_url: string
    file_type: string
    file_size: number
  }>
}

export interface Assignment {
  id: number
  course_id: number | null
  title: string
  assignment_code: string
  description: string
  due_date: string // Fixed typo from 'st' to 'string'
  status?: string
  course?: Course // Added course relation for calendar component
}

export interface Submission {
  id: number
  assignment_id: number | null
  student_id: number | null
  file_url: string
  mimetype?: string
  filename?: string
  status: 'submitted' | 'late' | 'not submitted'
  grade: number | null
  created_at: Date | string
  updated_at: Date | string
}

export interface Enrollment {
  id: number
  course_id: number | null
  student_id: number | null
  course?: Course
  created_at: Date | string
  student?: User
}

export interface AdminLoginResponse {
  user: User
  token: string
  message: string
}