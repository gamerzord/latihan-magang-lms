<template>
  <v-container class="py-6">
    <!-- Loading State -->
    <v-row v-if="pending">
      <v-col cols="12" class="text-center">
        <v-progress-circular indeterminate size="64" color="primary" />
        <p class="mt-4 text-body-1">Loading your courses...</p>
      </v-col>
    </v-row>

    <!-- Error State -->
    <v-row v-else-if="error">
      <v-col cols="12" class="text-center">
        <v-alert type="error" variant="tonal" class="mb-4" border="start" border-color="error">
          Failed to load courses: {{ error }}
        </v-alert>
        <v-btn color="primary" variant="flat" @click="loadCourses">
          <v-icon start>mdi-refresh</v-icon> Try Again
        </v-btn>
      </v-col>
    </v-row>

    <!-- Main Content -->
    <v-row v-else>
      <!-- Header with View Toggle -->
      <v-col cols="12" class="d-flex justify-space-between align-center mb-6">
        <h1 class="text-h4 font-weight-bold mb-0">My Courses</h1>

        <!-- View Mode Toggle -->
        <v-btn-toggle v-model="viewMode" mandatory color="primary" variant="outlined" density="comfortable">
          <v-btn value="grid" size="small" icon>
            <v-icon>mdi-view-grid</v-icon>
          </v-btn>
          <v-btn value="list" size="small" icon>
            <v-icon>mdi-view-list</v-icon>
          </v-btn>
        </v-btn-toggle>
      </v-col>

      <v-col v-if="activeConferences.length > 0" cols="12">
        <v-alert
          type="info"
          variant="tonal"
          prominent
          border="start"
          class="mb-4 live-alert"
        >
          <template v-slot:prepend>
            <v-icon size="large" class="pulse-icon">mdi-video</v-icon>
          </template>
          <div class="d-flex flex-column">
            <div class="text-h6 mb-2">
              <v-icon color="error" class="pulse-icon mr-1">mdi-circle</v-icon>
              {{ activeConferences.length }} Live Conference{{ activeConferences.length > 1 ? 's' : '' }}
            </div>
            <div v-for="conf in activeConferences" :key="conf.id" class="mb-2">
              <strong>{{ conf.course_title }}</strong> - {{ conf.title }}
              <v-btn
                size="small"
                color="primary"
                variant="elevated"
                class="ml-2"
                @click="joinConference(conf)"
              >
                <v-icon left small>mdi-video</v-icon>
                Join Now
              </v-btn>
            </div>
          </div>
        </v-alert>
      </v-col>

      <!-- Courses Section -->
      <v-col cols="12" md="8" lg="9">
        <v-row>
          <!-- Grid View -->
          <template v-if="viewMode === 'grid'">
            <v-col
              v-for="course in courses"
              :key="course.id"
              cols="12"
              sm="6"
              md="6"
              lg="4"
              class="d-flex"
            >
              <v-card 
                class="pa-4 flex-grow-1 hover-card"
                :class="{ 'live-course-card': hasActiveConference(course.id) }"
                elevation="3"
                @click="goToCourse(course.id)"
              >
                <div v-if="hasActiveConference(course.id)" class="live-badge-overlay">
                  <v-chip color="error" size="small" class="pulse-chip">
                    <v-icon size="small" start class="pulse-icon">mdi-circle</v-icon>
                    LIVE NOW
                  </v-chip>
                </div>

                <v-img
                  :src="course.thumbnail || '/placeholder-course.jpg'"
                  height="160"
                  class="rounded-lg mb-3"
                  cover
                />
                <v-card-title class="text-h6 mb-1">
                  {{ course.title }}
                </v-card-title>
                <v-card-subtitle class="text-body-2 text-medium-emphasis mb-2">
                  {{ course.teacher_name || 'Unknown Instructor' }}
                </v-card-subtitle>
                <v-card-text class="text-body-2 text-truncate-2">
                  {{ course.description || 'No description available.' }}
                </v-card-text>
              </v-card>
            </v-col>
          </template>

          <!-- List View -->
          <template v-else>
            <v-col cols="12">
              <StudentCourseList 
                :courses="courses" 
                :active-conferences="activeConferences"
              />
            </v-col>
          </template>

          <!-- Empty State -->
          <v-col v-if="!courses.length" cols="12" class="text-center py-12">
            <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-school-outline</v-icon>
            <h3 class="text-h6 text-medium-emphasis mb-2">No courses enrolled</h3>
            <p class="text-body-2 text-medium-emphasis">
              You haven't enrolled in any courses yet.
            </p>
            <v-btn color="primary" class="mt-4" to="/student/courses">
              <v-icon start>mdi-book-search</v-icon>
              Browse Available Courses
            </v-btn>
          </v-col>
        </v-row>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import type { Course, ActiveConference, Conference } from '~/types/models'

const { fetchUser } = useAuth()
const config = useRuntimeConfig()
const courses = ref<Course[]>([])
const activeConferences = ref<ActiveConference[]>([])
const pending = ref(true)
const error = ref<string | null>(null)
const viewMode = ref<'grid' | 'list'>('grid')

const loadCourses = async () => {
  pending.value = true
  error.value = null

  try {
    const { data, error: fetchError } = await useFetch<{ courses: Course[] }>(
      `${config.public.apiBase}/student/courses`
    )

    if (fetchError.value) throw new Error(fetchError.value.message)
    courses.value = data.value?.courses || []
  } catch (err: any) {
    console.error('Error loading courses:', err)
    error.value = err.message || 'An unexpected error occurred'
  } finally {
    pending.value = false
  }
}

const loadActiveConferences = async () => {
  try {
    const { data } = await useFetch<{ conferences: Conference[] }>(
      `${config.public.apiBase}/student/active-conferences`
    )
    
    if (data.value?.conferences) {
      activeConferences.value = data.value.conferences.map((conf: Conference) => {
        const courseTitle = conf.course?.title || getCourseTitle(conf.course_id) || 'Unknown Course'
        
        return {
          id: conf.id,
          course_id: conf.course_id,
          course_title: courseTitle,
          title: conf.title,
          room_id: conf.room_id,
          teacher_name: conf.teacher?.name || 'Unknown Teacher',
          started_at: conf.started_at || new Date().toISOString()
        }
      })
    }
  } catch (err) {
    console.error('Error loading active conferences:', err)
  }
}

const getCourseTitle = (courseId: number) => {
  const course = courses.value.find(c => c.id === courseId)
  return course?.title
}

const hasActiveConference = (courseId: number) => {
  return activeConferences.value.some(conf => conf.course_id === courseId)
}

const joinConference = (conference: ActiveConference) => {
  navigateTo(`/student/conference/${conference.id}/room`)
}

const goToCourse = (id: number) => {
  navigateTo(`/student/courses/${id}`)
}

useAutoRefresh(async () => {
  await fetchUser()
  await loadCourses()
  await loadActiveConferences()
})

onMounted(() => {
  const interval = setInterval(() => {
    loadActiveConferences()
  }, 30000)
  
  onBeforeUnmount(() => {
    clearInterval(interval)
  })
})
</script>

<style scoped>
.text-truncate-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.hover-card {
  transition: transform 0.25s ease, box-shadow 0.25s ease;
  cursor: pointer;
  position: relative;
}

.hover-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}

.live-course-card {
  border: 2px solid #f44336 !important;
  box-shadow: 0 0 20px rgba(244, 67, 54, 0.3) !important;
}

.live-badge-overlay {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 2;
}

.pulse-chip {
  animation: pulse-glow 2s infinite;
}

.pulse-icon {
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.3;
  }
}

@keyframes pulse-glow {
  0%, 100% {
    box-shadow: 0 0 10px rgba(244, 67, 54, 0.5);
  }
  50% {
    box-shadow: 0 0 20px rgba(244, 67, 54, 0.8);
  }
}

.live-alert {
  animation: subtle-pulse 3s ease-in-out infinite;
}

@keyframes subtle-pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.01);
  }
}
</style>