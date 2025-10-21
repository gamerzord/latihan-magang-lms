export interface User {
  id: number
  name: string
  email: string
  password: string
  role: 'admin' | 'teacher' | 'student'
}

export interface LoginResponse {
  token: string
  user: user
}

export interface Course {
    id: number
    title: string
    course_code: string
    description: string
    teacher_id: number | null 
    created_at: Date | string
    updated_at: Date | string
    progress?: number
    image?: string
    students_count?: number
    lessons_count?: number
    teacher_name?: string
}

export interface Lesson {
    id: number
    course_id: number | null
    title: string
    lesson_code: string
    content: string

}

export interface Assignment {
    id: number
    course_id: number | null
    title: string
    assignment_code: string
    description: string
    due_date: Date | string
}