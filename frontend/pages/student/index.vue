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
                elevation="3"
                @click="goToCourse(course.id)"
              >
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
              <StudentCourseList :courses="courses" />
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
import type { Course } from '~/types/models'

const { fetchUser } = useAuth()
const config = useRuntimeConfig()
const courses = ref<Course[]>([])
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

const goToCourse = (id: number) => {
  navigateTo(`/student/courses/${id}`)
}

useAutoRefresh(async () => {
  await fetchUser()
  await loadCourses()
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
}

.hover-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}
</style>
