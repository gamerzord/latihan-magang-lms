<template>
  <v-card elevation="2" class="calendar-card">
    <v-card-title class="d-flex justify-space-between align-center">
      <span class="text-h6">Assignment Calendar</span>
      <v-chip 
        v-if="upcomingCount > 0" 
        color="primary" 
        size="small"
      >
        {{ upcomingCount }} upcoming
      </v-chip>
    </v-card-title>

    <v-divider />

    <v-card-text>
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-8">
        <v-progress-circular indeterminate color="primary" />
        <p class="text-body-2 text-medium-emphasis mt-2">Loading assignments...</p>
      </div>

      <!-- Calendar -->
      <div v-else>
        <!-- Month Navigation -->
        <div class="d-flex justify-space-between align-center mb-4">
          <v-btn 
            icon 
            size="small" 
            variant="text"
            @click="previousMonth"
          >
            <v-icon>mdi-chevron-left</v-icon>
          </v-btn>
          
          <span class="text-h6">{{ currentMonthYear }}</span>
          
          <v-btn 
            icon 
            size="small" 
            variant="text"
            @click="nextMonth"
          >
            <v-icon>mdi-chevron-right</v-icon>
          </v-btn>
        </div>

        <!-- Calendar Grid -->
        <div class="calendar-grid">
          <!-- Day Headers -->
          <div 
            v-for="day in dayHeaders" 
            :key="day" 
            class="calendar-day-header"
          >
            {{ day }}
          </div>

          <!-- Calendar Days -->
          <div
            v-for="(day, index) in calendarDays"
            :key="index"
            :class="[
              'calendar-day',
              {
                'other-month': !day.isCurrentMonth,
                'today': day.isToday,
                'has-assignment': day.assignments.length > 0,
                'selected': selectedDay?.fullDate.getTime() === day.fullDate.getTime()
              }
            ]"
            @click="selectDate(day)"
          >
            <span class="day-number">{{ day.date }}</span>
            <div v-if="day.assignments.length > 0" class="assignment-indicator">
              <v-badge
                :content="day.assignments.length"
                color="error"
                inline
              >
                <v-icon size="small" color="error">mdi-clipboard-text</v-icon>
              </v-badge>
            </div>
          </div>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import type { Assignment, Course, CalendarDay, StudentCoursesResponse } from '~/types/models'

const config = useRuntimeConfig()

const emit = defineEmits<{
  dateSelected: [date: Date, assignments: Assignment[]]
}>()

const loading = ref(true)
const courses = ref<Course[]>([])
const currentDate = ref(new Date())
const selectedDay = ref<CalendarDay | null>(null)

const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const currentMonthYear = computed(() => {
  return currentDate.value.toLocaleDateString('en-US', { 
    month: 'long', 
    year: 'numeric' 
  })
})

const allAssignments = computed(() => {
  const assignments: Assignment[] = []
  courses.value.forEach(course => {
    if (course.assignments?.length) {
      course.assignments.forEach(assignment => {
        assignments.push({
          ...assignment,
          course_id: course.id
        })
      })
    }
  })
  return assignments
})

const upcomingCount = computed(() => {
  const now = new Date()
  now.setHours(0, 0, 0, 0)
  return allAssignments.value.filter(a => {
    const dueDate = new Date(a.due_date)
    dueDate.setHours(0, 0, 0, 0)
    return dueDate >= now
  }).length
})

const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()
  
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  const prevLastDay = new Date(year, month, 0)
  
  const firstDayOfWeek = firstDay.getDay()
  const lastDateOfMonth = lastDay.getDate()
  const prevLastDate = prevLastDay.getDate()
  
  const days: CalendarDay[] = []
  
  for (let i = firstDayOfWeek - 1; i >= 0; i--) {
    const date = prevLastDate - i
    const fullDate = new Date(year, month - 1, date)
    days.push({
      date,
      fullDate,
      isCurrentMonth: false,
      isToday: false,
      assignments: getAssignmentsForDate(fullDate)
    })
  }
  
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  for (let date = 1; date <= lastDateOfMonth; date++) {
    const fullDate = new Date(year, month, date)
    const isToday = fullDate.getTime() === today.getTime()
    
    days.push({
      date,
      fullDate,
      isCurrentMonth: true,
      isToday,
      assignments: getAssignmentsForDate(fullDate)
    })
  }
  
  const remainingDays = 42 - days.length
  for (let date = 1; date <= remainingDays; date++) {
    const fullDate = new Date(year, month + 1, date)
    days.push({
      date,
      fullDate,
      isCurrentMonth: false,
      isToday: false,
      assignments: getAssignmentsForDate(fullDate)
    })
  }
  
  return days
})

const loadAssignments = async () => {
  loading.value = true
  try {
    const { data, error } = await useFetch<StudentCoursesResponse>(
      `${config.public.apiBase}/student/courses`
    )
    
    if (error.value) throw new Error(error.value.message)
    courses.value = data.value?.courses || []
  } catch (err) {
    console.error('Error loading assignments:', err)
  } finally {
    loading.value = false
  }
}

const getAssignmentsForDate = (date: Date): Assignment[] => {
  const dateStr = date.toISOString().split('T')[0]
  return allAssignments.value.filter(assignment => {
    const dueDate = new Date(assignment.due_date)
    const dueDateStr = dueDate.toISOString().split('T')[0]
    return dueDateStr === dateStr
  })
}

const selectDate = (day: CalendarDay) => {
  selectedDay.value = day
  emit('dateSelected', day.fullDate, day.assignments)
}

const previousMonth = () => {
  currentDate.value = new Date(
    currentDate.value.getFullYear(),
    currentDate.value.getMonth() - 1,
    1
  )
}

const nextMonth = () => {
  currentDate.value = new Date(
    currentDate.value.getFullYear(),
    currentDate.value.getMonth() + 1,
    1
  )
}

onMounted(() => {
  loadAssignments()
})

defineExpose({
  refresh: loadAssignments
})
</script>

<style scoped>
.calendar-card {
  height: 100%;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
}

.calendar-day-header {
  text-align: center;
  font-weight: 600;
  font-size: 0.875rem;
  padding: 8px 4px;
  color: rgb(var(--v-theme-primary));
}

.calendar-day {
  aspect-ratio: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4px;
  border-radius: 8px;
  position: relative;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.calendar-day.other-month {
  opacity: 0.3;
}

.calendar-day.today {
  background-color: rgba(var(--v-theme-primary), 0.1);
  border-color: rgb(var(--v-theme-primary));
  font-weight: 700;
}

.calendar-day.has-assignment {
  background-color: rgba(var(--v-theme-error), 0.05);
}

.calendar-day.selected {
  background-color: rgba(var(--v-theme-primary), 0.2);
  border: 2px solid rgb(var(--v-theme-primary));
}

.calendar-day:hover {
  background-color: rgba(var(--v-theme-primary), 0.1);
  transform: scale(1.05);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.day-number {
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 2px;
}

.assignment-indicator {
  position: absolute;
  bottom: 4px;
}

@media (max-width: 600px) {
  .calendar-day-header,
  .day-number {
    font-size: 0.75rem;
  }
  
  .calendar-day {
    padding: 2px;
  }
}
</style>