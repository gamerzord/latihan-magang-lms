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
  course_id: number | null // Allow null
  title: string
  lesson_code: string
  content: string
  created_at: Date | string
  updated_at: Date | string

  course?: Course
  attachments?: LessonAttachment[]
}

export interface Assignment {
  id: number
  course_id: number | null
  title: string
  assignment_code: string
  description: string
  due_date: string
  status?: string
  course?: Course
}

export interface Submission {
  id: number
  assignment_id: number | null
  student_id: number | null
  file_url: string
  mimetype: string
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

export interface CalendarDay {
  date: number
  fullDate: Date
  isCurrentMonth: boolean
  isToday: boolean
  assignments: Assignment[]
}

export interface StudentCoursesResponse {
  courses: Course[]
}

export interface ScheduleEvent {
  id: string
  title: string
  description?: string
  category: string
  start: Date
  end: Date
  color: string
  allDay?: boolean
}

export interface EventForm {
  title: string
  description: string
  category: string
  date?: string
  startTime: string
  endTime: string
}

export interface LessonAttachment {
  id: number
  lesson_id: number
  file_name: string
  file_path: string
  file_url: string
  file_type: string
  mime_type: string
  file_size: number
  created_at?: string
  updated_at?: string
  lessons?: Lesson[]
  file_size_human?: string
}

export interface Conference {
  id: number
  course_id: number
  teacher_id: number
  title: string
  room_id: string
  status: 'scheduled' | 'active' | 'ended'
  started_at: string | null
  ended_at: string | null
  created_at: string
  updated_at: string
  course?: Course
  teacher?: User
}

export interface ActiveConference {
  id: number
  course_id: number | null
  course_title: string
  title: string
  room_id: string
  teacher_name: string
  started_at: string
}