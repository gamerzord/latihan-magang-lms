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
      <!-- Header Section -->
      <v-col cols="12" class="d-flex justify-space-between align-center mb-6">
        <div>
          <h1 class="text-h4 font-weight-bold mb-1">My Teaching Courses</h1>
          <p class="text-body-1 text-medium-emphasis mb-0">
            Welcome back, {{ auth.user.value?.name || 'Teacher' }}! Manage your courses and track student progress.
          </p>
        </div>

        <div class="d-flex align-center gap-2">
          <!-- View Mode Toggle -->
          <v-btn-toggle v-model="viewMode" mandatory color="primary" variant="outlined" density="comfortable">
            <v-btn value="grid" size="small" icon>
              <v-icon>mdi-view-grid</v-icon>
            </v-btn>
            <v-btn value="list" size="small" icon>
              <v-icon>mdi-view-list</v-icon>
            </v-btn>
          </v-btn-toggle>

          <!-- Create Course Button -->
          <v-btn color="primary" variant="flat" @click="createCourse">
            <v-icon start>mdi-plus</v-icon>
            Create Course
          </v-btn>

          <!-- Logout Button -->
          <v-btn color="error" variant="outlined" @click="logoutTeacher" class="ml-2">
            <v-icon start>mdi-logout</v-icon>
            Logout
          </v-btn>
        </div>
      </v-col>

      <!-- Grid View -->
      <template v-if="viewMode === 'grid'">
        <v-col
          v-for="course in courses"
          :key="course.id"
          cols="12"
          sm="6"
          md="4"
          lg="3"
          class="d-flex"
        >
          <v-card 
            class="pa-4 flex-grow-1 hover-card"
            elevation="3"
            @click="goToCourse(course.id)"
          >
            <!-- Course Thumbnail -->
            <v-img
              :src="course.thumbnail || '/placeholder-course.jpg'"
              height="160"
              class="rounded-lg mb-3"
              cover
            />
            
            <!-- Course Info -->
            <v-card-title class="text-h6 mb-1 px-0">
              {{ course.title }}
            </v-card-title>
            
            <v-card-subtitle class="text-body-2 text-medium-emphasis mb-2 px-0">
              {{ course.students_count || 0 }} students • {{ course.lessons_count || 0 }} lessons
            </v-card-subtitle>
            
            <v-card-text class="text-body-2 text-truncate-2 px-0">
              {{ course.description || 'No description available.' }}
            </v-card-text>

            <!-- Action Buttons -->
            <v-card-actions class="px-0 pt-2">
              <v-btn 
                color="primary" 
                variant="text" 
                size="small"
                @click.stop="viewSubmissions(course)"
              >
                <v-icon start>mdi-format-list-checks</v-icon>
                Submissions
              </v-btn>
              <v-spacer />
              <v-btn 
                icon 
                size="small" 
                variant="text"
                @click.stop="editCourse(course)"
              >
                <v-icon>mdi-pencil</v-icon>
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </template>

      <!-- List View -->
      <template v-else>
        <v-col cols="12">
          <v-card elevation="2">
            <v-list>
              <v-list-item
                v-for="course in courses"
                :key="course.id"
                class="py-4"
              >
                <template #prepend>
                  <v-avatar size="60" rounded class="mr-4">
                    <v-img
                      :src="course.thumbnail || '/placeholder-course.jpg'"
                      cover
                    />
                  </v-avatar>
                </template>

                <v-list-item-title class="text-h6 mb-1">
                  {{ course.title }}
                </v-list-item-title>
                <v-list-item-subtitle class="text-body-2 mb-2">
                  {{ course.students_count || 0 }} students • {{ course.lessons_count || 0 }} lessons
                </v-list-item-subtitle>
                <v-list-item-subtitle class="text-body-2">
                  {{ course.description || 'No description available.' }}
                </v-list-item-subtitle>

                <template #append>
                  <div class="d-flex align-center gap-2">
                    <v-btn 
                      color="primary" 
                      variant="text" 
                      @click="viewSubmissions(course)"
                    >
                      <v-icon start>mdi-format-list-checks</v-icon>
                      Submissions
                    </v-btn>
                    <v-btn 
                      icon 
                      variant="text"
                      @click="editCourse(course)"
                    >
                      <v-icon>mdi-pencil</v-icon>
                    </v-btn>
                    <v-btn 
                      icon 
                      variant="text"
                      @click="goToCourse(course.id)"
                    >
                      <v-icon>mdi-arrow-right</v-icon>
                    </v-btn>
                  </div>
                </template>
              </v-list-item>
            </v-list>
          </v-card>
        </v-col>
      </template>

      <!-- Empty State -->
      <v-col v-if="!pending && !error && !courses.length" cols="12" class="text-center py-12">
        <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-teach</v-icon>
        <h3 class="text-h6 text-medium-emphasis mb-2">No courses created yet</h3>
        <p class="text-body-2 text-medium-emphasis mb-4">
          Start by creating your first course to share knowledge with students.
        </p>
        <v-btn color="primary" variant="flat" @click="createCourse">
          <v-icon start>mdi-plus</v-icon>
          Create Your First Course
        </v-btn>
      </v-col>

      <!-- Submissions Section -->
      <v-row v-if="showSubmissions" class="mt-6">
        <v-col cols="12">
          <v-card elevation="2">
            <v-card-title class="d-flex justify-space-between align-center">
              <span>Submissions for {{ selectedCourse?.title }}</span>
              <v-btn icon @click="showSubmissions = false">
                <v-icon>mdi-close</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <SubmissionsTable
                :submissions="selectedSubmissions"
                @view="openSubmission"
                @refresh="() => selectedCourse && fetchSubmissions(selectedCourse.id)"
              />
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import type { Course, Submission } from '~/types/models'
import SubmissionsTable from '~/components/teacher/SubmissionsTable.vue'
import { useAuth } from '~/composables/useAuth'

const auth = useAuth()
const config = useRuntimeConfig()

const courses = ref<Course[]>([])
const selectedSubmissions = ref<Submission[]>([])
const selectedCourse = ref<Course | null>(null)
const showSubmissions = ref(false)
const pending = ref(true)
const error = ref<string | null>(null)
const viewMode = ref<'grid' | 'list'>('grid')

const loadCourses = async () => {
  pending.value = true
  error.value = null

  try {
    const { data, error: fetchError } = await useFetch<{ data: Course[] }>(
      `${config.public.apiBase}/teacher/courses`, {
      }
    )

    if (fetchError.value) throw new Error(fetchError.value.message)
    courses.value = data.value?.data || []
  } catch (err: any) {
    console.error('Error loading courses:', err)
    error.value = err.message || 'An unexpected error occurred'
  } finally {
    pending.value = false
  }
}

const fetchSubmissions = async (courseId: number) => {
  pending.value = true
  try {
    const { data, error: fetchError } = await useFetch<{ submissions: Submission[] }>(
      `${config.public.apiBase}/teacher/courses/${courseId}/submissions`, {
      }
    )
    
    if (fetchError.value) throw fetchError.value
    selectedCourse.value = courses.value.find(c => c.id === courseId) || null
    showSubmissions.value = true
  } catch (err) {
    console.error('Failed to fetch submissions:', err)
    selectedSubmissions.value = []
  } finally {
    pending.value = false
  }
}

const goToCourse = (id: number) => {
  navigateTo(`/teacher/courses/${id}`)
}

const editCourse = (course: Course) => {
  navigateTo(`/teacher/courses/${course.id}/edit`)
}

const createCourse = () => {
  navigateTo(`/teacher/courses/create`)
}

const openSubmission = (submission: any) => {
  navigateTo(`/teacher/submissions/${submission.id}`)
}

const viewSubmissions = (course: Course) => {
  fetchSubmissions(course.id)
}

const logoutTeacher = async () => {
  await auth.logout()
}

onMounted(async () => {
  if (!auth.authenticated.value) {
    await auth.fetchUser()
  }
  await loadCourses()
  
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) loadCourses()
  })
})

onUnmounted(() => {
  document.removeEventListener('visibilitychange', loadCourses)
})
</script>

<style scoped>
.text-truncate-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
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

.gap-2 {
  gap: 8px;
}
</style>