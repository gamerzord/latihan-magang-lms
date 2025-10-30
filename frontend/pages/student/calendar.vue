<template>
  <v-container fluid class="pa-6">
    <v-row>
      <!-- Page Header -->
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center mb-4">
          <div>
            <h1 class="text-h4 font-weight-bold">Calendar</h1>
            <p class="text-body-2 text-medium-emphasis">
              Manage your assignments and schedule
            </p>
          </div>
          <v-btn-toggle v-model="activeCalendar" mandatory color="primary" variant="outlined">
            <v-btn value="assignments">
              <v-icon start>mdi-clipboard-text</v-icon>
              Assignments
            </v-btn>
            <v-btn value="schedule">
              <v-icon start>mdi-calendar-clock</v-icon>
              Schedule
            </v-btn>
          </v-btn-toggle>
        </div>
      </v-col>

      <!-- Assignment Calendar View -->
      <template v-if="activeCalendar === 'assignments'">
        <v-col cols="12" lg="5">
          <StudentAssignmentCalendar 
            ref="assignmentCalendarRef"
            @date-selected="handleDateSelected"
          />
        </v-col>

        <v-col cols="12" lg="7">
          <!-- Selected Date Assignments -->
          <v-card elevation="2">
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi-clipboard-list</v-icon>
              <span v-if="selectedDate">
                Assignments for {{ formatSelectedDate(selectedDate) }}
              </span>
              <span v-else>Select a date to view assignments</span>
            </v-card-title>

            <v-divider />

            <v-card-text>
              <!-- No Date Selected -->
              <div v-if="!selectedDate" class="text-center py-12">
                <v-icon size="64" color="grey-lighten-1" class="mb-4">
                  mdi-calendar-blank
                </v-icon>
                <p class="text-h6 text-medium-emphasis">No date selected</p>
                <p class="text-body-2 text-medium-emphasis">
                  Click on a date in the calendar to view assignments
                </p>
              </div>

              <!-- No Assignments for Selected Date -->
              <div v-else-if="selectedAssignments.length === 0" class="text-center py-12">
                <v-icon size="64" color="grey-lighten-1" class="mb-4">
                  mdi-clipboard-check-outline
                </v-icon>
                <p class="text-h6 text-medium-emphasis">No assignments due</p>
                <p class="text-body-2 text-medium-emphasis">
                  You have no assignments due on this date
                </p>
              </div>

              <!-- Assignment List -->
              <v-list v-else lines="three">
                <v-list-item
                  v-for="(assignment, index) in selectedAssignments"
                  :key="assignment.id"
                  class="assignment-item mb-3"
                  elevation="1"
                >
                  <template #prepend>
                    <v-avatar :color="getStatusColor(assignment.due_date)" size="48">
                      <v-icon color="white">mdi-file-document</v-icon>
                    </v-avatar>
                  </template>

                  <v-list-item-title class="text-h6 mb-1">
                    {{ assignment.title }}
                  </v-list-item-title>

                  <v-list-item-subtitle class="mb-2">
                    <v-icon size="small" class="mr-1">mdi-book-open-variant</v-icon>
                    {{ getCourseTitle(assignment.course_id) }}
                  </v-list-item-subtitle>

                  <v-list-item-subtitle v-if="assignment.description" class="text-body-2">
                    {{ assignment.description }}
                  </v-list-item-subtitle>

                  <template #append>
                    <div class="d-flex flex-column align-end gap-2">
                      <v-chip
                        :color="getStatusColor(assignment.due_date)"
                        size="small"
                        variant="flat"
                      >
                        {{ getStatusText(assignment.due_date) }}
                      </v-chip>
                      <v-btn
                        color="primary"
                        variant="outlined"
                        size="small"
                        @click="goToCourse(assignment.course_id)"
                      >
                        <v-icon start>mdi-arrow-right</v-icon>
                        View
                      </v-btn>
                    </div>
                  </template>
                  <v-divider v-if="index < selectedAssignments.length - 1" class="my-2" />
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>

          <!-- Assignment Statistics -->
          <v-row class="mt-4">
            <v-col cols="12" sm="4">
              <v-card color="error" variant="tonal">
                <v-card-text class="text-center">
                  <v-icon size="32" class="mb-2">mdi-alert-circle</v-icon>
                  <div class="text-h4 font-weight-bold">{{ overdueCount }}</div>
                  <div class="text-caption">Overdue</div>
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" sm="4">
              <v-card color="warning" variant="tonal">
                <v-card-text class="text-center">
                  <v-icon size="32" class="mb-2">mdi-clock-alert</v-icon>
                  <div class="text-h4 font-weight-bold">{{ dueSoonCount }}</div>
                  <div class="text-caption">Due Soon (3 days)</div>
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" sm="4">
              <v-card color="success" variant="tonal">
                <v-card-text class="text-center">
                  <v-icon size="32" class="mb-2">mdi-check-circle</v-icon>
                  <div class="text-h4 font-weight-bold">{{ upcomingCount }}</div>
                  <div class="text-caption">Upcoming</div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-col>
      </template>

      <!-- Schedule Calendar View -->
      <template v-else>
        <v-col cols="12">
          <StudentScheduleCalendar ref="scheduleCalendarRef" />
        </v-col>
      </template>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import type { Assignment, Course } from '~/types/models'

const config = useRuntimeConfig()
const router = useRouter()

// State
const activeCalendar = ref<'assignments' | 'schedule'>('assignments')
const selectedDate = ref<Date | null>(null)
const selectedAssignments = ref<Assignment[]>([])
const assignmentCalendarRef = ref()
const scheduleCalendarRef = ref()
const courses = ref<Course[]>([])

const { data: coursesData } = useFetch<{ courses: Course[] }>(
  `${config.public.apiBase}/student/courses`,
  {
    key: 'calendar-courses',
    lazy: false,
  }
)

watch(coursesData, (newData) => {
  if (newData) {
    courses.value = newData.courses || []
  }
}, { immediate: true })

// Computed
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

const overdueCount = computed(() => {
  const now = new Date()
  now.setHours(0, 0, 0, 0)
  return allAssignments.value.filter(a => {
    const dueDate = new Date(a.due_date)
    dueDate.setHours(0, 0, 0, 0)
    return dueDate < now
  }).length
})

const dueSoonCount = computed(() => {
  const now = new Date()
  now.setHours(0, 0, 0, 0)
  const threeDaysFromNow = new Date(now)
  threeDaysFromNow.setDate(now.getDate() + 3)
  
  return allAssignments.value.filter(a => {
    const dueDate = new Date(a.due_date)
    dueDate.setHours(0, 0, 0, 0)
    return dueDate >= now && dueDate <= threeDaysFromNow
  }).length
})

const upcomingCount = computed(() => {
  const now = new Date()
  now.setHours(0, 0, 0, 0)
  const threeDaysFromNow = new Date(now)
  threeDaysFromNow.setDate(now.getDate() + 3)
  
  return allAssignments.value.filter(a => {
    const dueDate = new Date(a.due_date)
    dueDate.setHours(0, 0, 0, 0)
    return dueDate > threeDaysFromNow
  }).length
})

const handleDateSelected = (date: Date, assignments: Assignment[]) => {
  selectedDate.value = date
  selectedAssignments.value = assignments
}

const getCourseTitle = (courseId: number | null): string => {
  if (!courseId) return 'Unknown Course'
  const course = courses.value.find(c => c.id === courseId)
  return course?.title || 'Unknown Course'
}

const goToCourse = (courseId: number | null) => {
  if (courseId) {
    router.push(`/student/courses/${courseId}`)
  }
}

const formatSelectedDate = (date: Date): string => {
  return date.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
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

useAutoRefresh(async () => {
  if (assignmentCalendarRef.value) {
    await assignmentCalendarRef.value.refresh()
  } else if (scheduleCalendarRef.value) {
    await scheduleCalendarRef.value.refresh()
  }
})
</script>

<style scoped>
.assignment-item {
  border-radius: 8px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  padding: 12px;
}

.assignment-item:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

.gap-2 {
  gap: 8px;
}
</style>