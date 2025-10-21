<template>
  <v-card class="course-card" elevation="2" :to="courseLink" hover>
    <v-img height="120" :src="course.image || '/default-course.jpg'" class="course-image">
      <v-chip v-if="user?.role === 'teacher'" color="primary" small class="ma-2">
        {{ course.students_count }} students
      </v-chip>
      
      <v-progress-circular 
        v-if="user?.role === 'student' && course.progress" 
        :value="course.progress" 
        size="40"
        color="white"
        class="ma-2"
      >
        {{ course.progress }}%
      </v-progress-circular>
    </v-img>
    
    <v-card-title class="text-h6">{{ course.title }}</v-card-title>
    
    <v-card-text>
      <p class="text-caption text-truncate-2">{{ course.description }}</p>
      
      <div class="d-flex align-center mt-2">
        <v-icon small class="mr-1">mdi-account</v-icon>
        <span class="text-caption">{{ course.teacher_name }}</span>
      </div>
      
      <div class="d-flex align-center mt-1">
        <v-icon small class="mr-1">mdi-book-open</v-icon>
        <span class="text-caption">{{ course.lessons_count }} lessons</span>
      </div>

      <div class="d-flex align-center mt-1">
        <v-icon small class="mr-1">mdi-identifier</v-icon>
        <span class="text-caption">{{ course.course_code }}</span>
      </div>
    </v-card-text>

  </v-card>
</template>

<script setup lang="ts">
import type { Course } from '~/types/models'

interface Props {
  course: Course
}

const props = defineProps<Props>()
const { user } = useAuth()

const courseLink = computed(() => {
  if (user.value?.role === 'student') return `/student/courses/${props.course.id}`
  if (user.value?.role === 'teacher') return `/teacher/courses/${props.course.id}`
  return '/'
})

const enterCourse = () => {
  navigateTo(courseLink.value)
}

const editCourse = () => {
  navigateTo(`/teacher/courses/${props.course.id}/edit`)
}

const viewAnalytics = () => {
  navigateTo(`/teacher/courses/${props.course.id}/analytics`)
}
</script>

<style scoped>
.course-card {
  cursor: pointer;
  transition: transform 0.2s;
}

.course-card:hover {
  transform: translateY(-4px);
}

.text-truncate-2 {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>