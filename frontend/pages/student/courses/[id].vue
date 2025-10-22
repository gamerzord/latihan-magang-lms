<template>
  <div class="student-course-details">
    <v-container>
      <v-row>
        <v-col cols="12" md="8" offset-md="2">
          <v-card elevation="2" class="pa-4">
            <template v-if="pending">
              <div class="text-center py-8">
                <v-progress-circular indeterminate color="primary" />
              </div>
            </template>

            <template v-else-if="course">
              <v-card-title class="d-flex justify-space-between align-center">
                <div>
                  <h2 class="text-h5">{{ course.title }}</h2>
                  <div class="text-subtitle-2 text-medium-emphasis">
                    Taught by: {{ course.teacher_name || 'Unknown' }}
                  </div>
                </div>
                <v-chip color="green" size="small" variant="outlined">Active</v-chip>
              </v-card-title>

              <v-card-text>
                <p class="text-body-1 mb-4">{{ course.description }}</p>

                <v-progress-linear
                  :model-value="course.progress"
                  color="primary"
                  height="6"
                  class="mb-2"
                />
                <p class="text-caption text-medium-emphasis mb-6">
                  Progress: {{ course.progress }}%
                </p>

                <div v-if="course.lessons?.length">
                  <h3 class="text-h6 mb-2">Lessons</h3>
                  <v-list density="compact" class="bg-transparent">
                    <v-list-item
                      v-for="lesson in course.lessons"
                      :key="lesson.id"
                      prepend-icon="mdi-play-circle-outline"
                    >
                      <v-list-item-title>{{ lesson.title }}</v-list-item-title>
                    </v-list-item>
                  </v-list>
                </div>

                <div v-else class="text-center py-4 text-medium-emphasis">
                  No lessons available yet.
                </div>
              </v-card-text>
            </template>

            <template v-else>
              <div class="text-center py-8 text-error">
                <v-icon size="48" color="error" class="mb-2">mdi-alert-circle-outline</v-icon>
                <p>Course not found.</p>
              </div>
            </template>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup lang="ts">
import type { Course } from '~/types/models'

const route = useRoute()
const config = useRuntimeConfig()

const { data, pending } = await useFetch<{ data: Course }>(
  `${config.public.apiBase}/student/courses/${route.params.id}`,
)

const course = computed(() => data.value?.data)
</script>
