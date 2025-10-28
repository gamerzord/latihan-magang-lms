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
                'has-assignment': day.assignments.length > 0
              }
            ]"
            @click="day.assignments.length > 0 && showAssignments(day)"
          >
            <span class="day-number">{{ day.date }}</span>
            <div v-if="day.assignments.length > 0" class="assignment-indicator">
              <v-tooltip location="top">
                <template v-slot:activator="{ props }">
                  <v-badge
                    :content="day.assignments.length"
                    color="error"
                    inline
                    v-bind="props"
                  >
                    <v-icon size="small" color="error">mdi-clipboard-text</v-icon>
                  </v-badge>
                </template>
                <span>{{ day.assignments.length }} assignment(s) due</span>
              </v-tooltip>
            </div>
          </div>
        </div>

        <!-- Assignment Details Dialog -->
        <v-dialog v-model="dialog" max-width="600">
          <v-card>
            <v-card-title class="d-flex justify-space-between align-center">
              <span>Assignments Due: {{ selectedDate }}</span>
              <v-btn icon variant="text" @click="dialog = false">
                <v-icon>mdi-close</v-icon>
              </v-btn>
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-0">
              <v-list>
                <v-list-item
                  v-for="assignment in selectedAssignments"
                  :key="assignment.id"
                  @click="goToAssignment(assignment)"
                  class="assignment-list-item"
                >
                  <template v-slot:prepend>
                    <v-avatar color="primary" size="40">
                      <v-icon>mdi-file-document</v-icon>
                    </v-avatar>
                  </template>
                  <v-list-item-title class="font-weight-medium">
                    {{ assignment.title }}
                  </v-list-item-title>
                  <v-list-item-subtitle>
                    {{ assignment.course?.title || 'Unknown Course' }}
                  </v-list-item-subtitle>
                  <template v-slot:append>
                    <v-chip
                      :color="getStatusColor(assignment.due_date)"
                      size="small"
                      variant="flat"
                    >
                      {{ getStatusText(assignment.due_date) }}
                    </v-chip>
                  </template>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </v-dialog>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import type { Assignment } from '~/types/models'

interface CalendarDay {
  date: number
  fullDate: Date
  isCurrentMonth: boolean
  isToday: boolean
  assignments: Assignment[]
}

const config = useRuntimeConfig()
const router = useRouter()

// State
const loading = ref(true)
const assignments = ref<Assignment[]>([])
const currentDate = ref(new Date())
const dialog = ref(false)
const selectedAssignments = ref<Assignment[]>([])
const selectedDate = ref('')

// Day headers
const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

// Computed
const currentMonthYear = computed(() => {
  return currentDate.value.toLocaleDateString('en-US', { 
    month: 'long', 
    year: 'numeric' 
  })
})

const upcomingCount = computed(() => {
  const now = new Date()
  now.setHours(0, 0, 0, 0)
  return assignments.value.filter(a => {
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
  
  // Previous month days
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
  
  // Current month days
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
  
  // Next month days
  const remainingDays = 42 - days.length // 6 rows × 7 days
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

// Methods
const loadAssignments = async () => {
  loading.value = true
  try {
    const { data, error } = await useFetch<{ assignments: Assignment[] }>(
      `${config.public.apiBase}/student/assignments`
    )
    
    if (error.value) throw new Error(error.value.message)
    assignments.value = data.value?.assignments || []
  } catch (err) {
    console.error('Error loading assignments:', err)
  } finally {
    loading.value = false
  }
}

const getAssignmentsForDate = (date: Date): Assignment[] => {
  const dateStr = date.toISOString().split('T')[0]
  return assignments.value.filter(assignment => {
    const dueDate = new Date(assignment.due_date)
    const dueDateStr = dueDate.toISOString().split('T')[0]
    return dueDateStr === dateStr
  })
}

const showAssignments = (day: CalendarDay) => {
  selectedAssignments.value = day.assignments
  selectedDate.value = day.fullDate.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
  dialog.value = true
}

const goToAssignment = (assignment: Assignment) => {
  dialog.value = false
  router.push(`/student/courses/${assignment.course_id}/assignments/${assignment.id}`)
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

const getStatusColor = (dueDate: string): string => {
  const now = new Date()
  now.setHours(0, 0, 0, 0)
  const due = new Date(dueDate)
  due.setHours(0, 0, 0, 0)
  
  const diffDays = Math.ceil((due.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
  
  if (diffDays < 0) return 'error'
  if (diffDays === 0) return 'warning'
  if (diffDays <= 3) return 'orange'
  return 'success'
}

const getStatusText = (dueDate: string): string => {
  const now = new Date()
  now.setHours(0, 0, 0, 0)
  const due = new Date(dueDate)
  due.setHours(0, 0, 0, 0)
  
  const diffDays = Math.ceil((due.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
  
  if (diffDays < 0) return 'Overdue'
  if (diffDays === 0) return 'Due Today'
  if (diffDays === 1) return 'Due Tomorrow'
  return `${diffDays} days left`
}

// Initialize
onMounted(() => {
  loadAssignments()
})

// Auto refresh
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
  cursor: default;
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
  cursor: pointer;
  background-color: rgba(var(--v-theme-error), 0.05);
}

.calendar-day.has-assignment:hover {
  background-color: rgba(var(--v-theme-error), 0.1);
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

.assignment-list-item {
  cursor: pointer;
}

.assignment-list-item:hover {
  background-color: rgba(var(--v-theme-primary), 0.05);
}

/* Responsive */
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