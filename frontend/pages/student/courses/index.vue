<!-- pages/student/courses/index.vue -->
<template>
  <StudentCourseList :courses="courses" />
</template>

<script setup lang="ts">
const config = useRuntimeConfig()

import type { Course } from '~/types/models';

const { data: response } = await useFetch<{ courses: Course[] }>(`${config.public.apiBase}/courses`)
const courses = computed(() => response.value?.courses || [])
</script>
