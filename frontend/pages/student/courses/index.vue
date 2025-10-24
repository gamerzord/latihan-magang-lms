<template>
  <v-container class="py-6">
    <!-- Loading State -->
    <v-row v-if="pending">
      <v-col cols="12" class="text-center">
        <v-progress-circular indeterminate size="64" color="primary" />
        <p class="mt-4 text-body-1">Loading available courses...</p>
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
      <v-col cols="12" class="d-flex justify-space-between align-center mb-6">
        <h1 class="text-h4 font-weight-bold mb-0">Available Courses</h1>

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

      <!-- Grid View -->
      <template  v-if="viewMode === 'grid'">
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
            
            <v-card-text class="text-body-2 text-truncate-2 mb-3">
              {{ course.description || 'No description available.' }}
            </v-card-text>

            <!-- Course Stats -->
            <div class="mb-3">
              <div class="d-flex justify-space-between text-caption text-medium-emphasis">
                <span>
                  <v-icon small class="mr-1">mdi-account-group</v-icon>
                  {{ course.students_count || 0 }} students
                </span>
                <span>
                  <v-icon small class="mr-1">mdi-book-open</v-icon>
                  {{ course.lessons_count || 0 }} lessons
                </span>
              </div>
            </div>

            <!-- Enrollment Status & Actions -->
            <div class="d-flex justify-space-between align-center">
              <v-chip
                v-if="isEnrolled(course.id)"
                size="small"
                color="success"
                variant="outlined"
              >
                <v-icon start small>mdi-check</v-icon>
                Enrolled
              </v-chip>
              <v-chip
                v-else
                size="small"
                color="grey"
                variant="outlined"
              >
                Not Enrolled
              </v-chip>

              <div class="d-flex gap-1">
                <v-btn
                  v-if="!isEnrolled(course.id)"
                  color="primary"
                  variant="flat"
                  size="small"
                  :loading="enrollingCourseId === course.id"
                  @click="enrollInCourse(course)"
                >
                  Enroll
                </v-btn>
                <v-btn
                  v-else
                  color="primary"
                  variant="outlined"
                  size="small"
                  :to="`/student/courses/${course.id}`"
                >
                  View Course
                </v-btn>
              </div>
            </div>
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
                
                <v-list-item-subtitle  class="text-body-2 mb-2" >
                  {{ course.teacher_name || 'Unknown Instructor' }} • 
                  {{ course.students_count || 0 }} students • 
                  {{ course.lessons_count || 0 }} lessons
                </v-list-item-subtitle>
                
                <v-list-item-subtitle class="text-body-2">
                  {{ course.description || 'No description available.' }}
                </v-list-item-subtitle>

                <template #append>
                  <div class="d-flex align-center gap-2">
                    <v-chip
                      v-if="isEnrolled(course.id)"
                      size="small"
                      color="success"
                      variant="outlined"
                    >
                      Enrolled
                    </v-chip>
                    <v-chip
                      v-else
                      size="small"
                      color="grey"
                      variant="outlined"
                    >
                      Not Enrolled
                    </v-chip>

                    <v-btn
                      v-if="!isEnrolled(course.id)"
                      color="primary"
                      variant="flat"
                      :loading="enrollingCourseId === course.id"
                      @click="enrollInCourse(course)"
                    >
                      Enroll
                    </v-btn>
                    <v-btn
                      v-else
                      color="primary"
                      variant="outlined"
                      :to="`/student/courses/${course.id}`"
                    >
                      View Course
                    </v-btn>
                  </div>
                </template>
              </v-list-item>
            </v-list>
          </v-card>
        </v-col>
      </template>

      <!-- Empty State -->
      <v-col v-if="!courses.length" cols="12" class="text-center py-12">
        <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-school-outline</v-icon>
        <h3 class="text-h6 text-medium-emphasis mb-2">No courses available</h3>
        <p class="text-body-2 text-medium-emphasis">
          There are no courses available for enrollment at the moment.
        </p>
      </v-col>
    </v-row>

    <!-- Enrollment Success Dialog -->
    <v-dialog v-model="enrollmentSuccessDialog" max-width="400">
      <v-card>
        <v-card-title class="text-h6 d-flex align-center">
          <v-icon color="success" class="mr-2">mdi-check-circle</v-icon>
          Enrollment Successful!
        </v-card-title>
        <v-card-text>
          You have been successfully enrolled in "{{ enrolledCourseTitle }}".
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn color="primary" @click="goToEnrolledCourse">
            Start Learning
          </v-btn>
          <v-btn variant="text" @click="enrollmentSuccessDialog = false">
            Continue Browsing
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
import type { Course, Enrollment } from '~/types/models'

const { user, fetchUser } = useAuth()
const config = useRuntimeConfig()

const courses = ref<Course[]>([])
const enrolledCourses = ref<number[]>([])
const pending = ref(true)
const error = ref<string | null>(null)
const viewMode = ref<'grid' | 'list'>('grid')
const enrollingCourseId = ref<number | null>(null)
const enrollmentSuccessDialog = ref(false)
const enrolledCourseTitle = ref('')
const enrolledCourseId = ref<number | null>(null)

const loadCourses = async () => {
  pending.value = true
  error.value = null

  try {
    const { data, error: fetchError } = await useFetch<{ courses: Course[] }>(
      `${config.public.apiBase}/courses`, {
      }
    )

    if (fetchError.value) throw new Error(fetchError.value.message)
    courses.value = data.value?.courses || []
    
    await loadEnrollments()
  } catch (err: any) {
    console.error('Error loading courses:', err)
    error.value = err.message || 'An unexpected error occurred'
  } finally {
    pending.value = false
  }
}

const loadEnrollments = async () => {
  try {
    const { data } = await useFetch<{ enrollments: Enrollment[] }>(`${config.public.apiBase}/enrollments`, {
    })
    
    enrolledCourses.value = data.value?.enrollments?.map((enrollment: any) => enrollment.course_id) || []
  } catch (err) {
    console.error('Error loading enrollments:', err)
  }
}

const isEnrolled = (courseId: number) => {
  return enrolledCourses.value.includes(courseId)
}

const enrollInCourse = async (course: Course) => {
  if (!user.value?.id) {
    alert('Please log in to enroll in courses')
    return
  }

  enrollingCourseId.value = course.id

  try {
    await $fetch(`${config.public.apiBase}/enrollments`, {
      method: 'POST',
      body: {
        course_id: course.id,
        student_id: user.value.id
      }
    })
    
    enrolledCourses.value.push(course.id)
    enrolledCourseTitle.value = course.title
    enrolledCourseId.value = course.id
    enrollmentSuccessDialog.value = true

  } catch (err: any) {
    console.error('Error enrolling in course:', err)
    alert(err.data?.message || 'Failed to enroll in course. Please try again.')
  } finally {
    enrollingCourseId.value = null
  }
}

const goToEnrolledCourse = () => {
  if (enrolledCourseId.value) {
    navigateTo(`/student/courses/${enrolledCourseId.value}`)
  }
  enrollmentSuccessDialog.value = false
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

.gap-1 {
  gap: 4px;
}
</style>