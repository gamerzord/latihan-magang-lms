<template>
  <v-container>
    <v-row v-if="pending">
      <v-col cols="12" class="text-center">
        <v-progress-circular indeterminate size="64" color="primary" />
        <p class="mt-4 text-body-1">pending your courses...</p>
      </v-col>
    </v-row>

    <v-row v-else-if="error">
      <v-col cols="12" class="text-center">
        <v-alert type="error" class="mb-4">
          Failed to load courses: {{ error }}
        </v-alert>
        <v-btn color="primary" @click="loadCourses">Try Again</v-btn>
      </v-col>
    </v-row>

    <div v-else>
      <div v-if="viewMode === 'grid'">
        <v-row class="mb-6">
          <v-col cols="12" class="d-flex justify-space-between align-center">
            <h1 class="text-h4">My Courses</h1>
            <v-btn-toggle
              v-model="viewMode"
              mandatory
              color="primary"
            >
              <v-btn value="grid" size="small">
                <v-icon>mdi-view-grid</v-icon>
              </v-btn>
              <v-btn value="list" size="small">
                <v-icon>mdi-view-list</v-icon>
              </v-btn>
            </v-btn-toggle>
          </v-col>
        </v-row>

        <v-row>
          <v-col 
            v-for="course in courses" 
            :key="course.id" 
            cols="12" sm="6" md="4" lg="3"
          >
            <CourseCard :course="course" />
          </v-col>
        </v-row>

        <v-row v-if="!courses.length">
          <v-col cols="12" class="text-center py-12">
            <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-school-outline</v-icon>
            <h3 class="text-h6 text-medium-emphasis mb-2">No courses enrolled</h3>
            <p class="text-body-2 text-medium-emphasis">You haven't enrolled in any courses yet.</p>
            <v-btn color="primary" class="mt-4" to="/courses">
              Browse Available Courses
            </v-btn>
          </v-col>
        </v-row>
      </div>

      <div v-else>
        <StudentCourseList :courses="courses" />
      </div>
    </div>
  </v-container>
</template>

<script setup lang="ts">
import type { Course } from '~/types/models'

const courses = ref<Course[]>([])
const pending = ref(true)
const config = useRuntimeConfig()
const error = ref<string | null>(null)
const viewMode = ref<'grid' | 'list'>('grid')

const loadCourses = async () => {
  pending.value = true
  error.value = null
  
  try {
    const { data, error: fetchError } = await useFetch<{ courses: Course[] }>(`${config.public.apiBase}/courses`)
    
    if (fetchError.value) {
      throw new Error(fetchError.value.message || 'Failed to fetch courses')
    }
    
    courses.value = data.value?.courses || courses.value || []
  } catch (err: any) {
    console.error('Error pending courses:', err)
    error.value = err.message || 'An unexpected error occurred'
  } finally {
    pending.value = false
  }
}

onMounted(() => {
  loadCourses()
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) loadCourses()
  })
})

</script>

<style scoped>
</style>