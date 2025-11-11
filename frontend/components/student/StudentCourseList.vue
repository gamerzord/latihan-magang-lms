<template v-if="user?.role === 'teacher'">
  <div class="student-course-list">
    <h1 class="text-h4 mb-6">Courses</h1>

    <div class="course-list">
      <div 
        v-for="course in courses" 
        :key="course.id"
        class="course-item mb-4"
      >
        <v-card class="course-card" elevation="1" @click="enterCourse(course)">
          <v-card-text class="pa-4">
            <div class="mb-2">
              <h3 class="text-h6 text-primary">{{ course.title }}</h3>
              <div class="text-caption text-medium-emphasis">
                {{ course.teacher_name }}
              </div>
            </div>

            <div class="d-flex align-center mb-2">
              <v-chip size="small" color="green" variant="outlined" class="mr-2">
                Published
              </v-chip>
            </div>

            <p class="text-body-2 text-medium-emphasis mb-0">
              {{ course.description }}
            </p>
          </v-card-text>
        </v-card>
      </div>
    </div>

    <div v-if="!courses.length" class="text-center py-12">
      <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-school-outline</v-icon>
      <h3 class="text-h6 text-medium-emphasis">No courses found</h3>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Course } from '~/types/models'

defineProps<{
  courses: Course[]
}>()

const enterCourse = (course: Course) => {
  navigateTo(`/student/courses/${course.id}`)
}
</script>

<style scoped>
.course-card {
  cursor: pointer;
  transition: all 0.2s ease;
}

.course-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
}
</style>
