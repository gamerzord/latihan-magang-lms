<template>
  <v-card elevation="2">
    <v-list>
      <v-list-item
        v-for="course in courses"
        :key="course.id"
        class="py-4"
      >
        <template #prepend>
          <v-avatar size="60" rounded class="mr-4">
            <v-img :src="course.thumbnail || '/placeholder-course.jpg'" cover />
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
              @click="$emit('submissions', course)"
            >
              <v-icon start>mdi-format-list-checks</v-icon>
              Submissions
            </v-btn>

            <v-btn
              icon
              variant="text"
              @click="$emit('edit', course)"
            >
              <v-icon>mdi-pencil</v-icon>
            </v-btn>

            <v-btn
              icon
              variant="text"
              @click="$emit('open', course.id)"
            >
              <v-icon>mdi-arrow-right</v-icon>
            </v-btn>
          </div>
        </template>
      </v-list-item>
    </v-list>
  </v-card>
</template>

<script setup lang="ts">
import type { Course } from '~/types/models'

defineProps<{
  courses: Course[]
}>()

defineEmits<{
  (e: 'open', id: number): void
  (e: 'edit', course: Course): void
  (e: 'submissions', course: Course): void
}>()
</script>

<style scoped>
.gap-2 {
  gap: 8px;
}
</style>
