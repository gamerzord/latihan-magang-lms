<template>
  <v-container fluid class="page-container">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" class="card">
          <v-card-title class="d-flex justify-space-between align-center card-header">
            <div class="d-flex align-center">
              <span class="text-h5 font-weight-bold">Edit Course</span>
              <v-tooltip location="bottom">
                <template #activator="{ props }">
                  <v-icon v-bind="props" color="grey" class="ml-2" size="small">
                    mdi-information
                  </v-icon>
                </template>
                <span>Update course information and assigned teacher</span>
              </v-tooltip>
            </div>
          </v-card-title>

          <v-card-text class="pa-6">
            <v-alert v-if="successMessage" type="success" class="mb-4">
              {{ successMessage }}
            </v-alert>
            <v-alert v-if="errorMessage" type="error" class="mb-4">
              {{ errorMessage }}
            </v-alert>
            
            <v-form v-if="course" @submit.prevent="updateCourse">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="course.title"
                    label="Course Title"
                    outlined
                    required
                    :rules="[v => !!v || 'Course title is required']"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="course.course_code"
                    label="Course Code"
                    outlined
                    required
                    :rules="[v => !!v || 'Course code is required']"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="course.description"
                    label="Description"
                    outlined
                    rows="3"
                  />
                </v-col>
              </v-row>

              <v-card-actions class="mt-6 px-0">
                <v-btn 
                  color="grey" 
                  variant="outlined" 
                  @click="$router.back()"
                  :disabled="loading"
                >
                  Cancel
                </v-btn>
                <v-spacer />
                <v-btn
                  color="primary"
                  type="submit"
                  :loading="loading"
                  variant="flat"
                  prepend-icon="mdi-content-save"
                >
                  Save Changes
                </v-btn>
              </v-card-actions>
            </v-form>
            
            <v-alert v-else type="info" class="mt-4">
              <div class="d-flex align-center">
                <v-progress-circular
                  indeterminate
                  size="20"
                  width="2"
                  class="mr-3"
                />
                Loading course data...
              </div>
            </v-alert>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import type { Course, User } from '~/types/models'

const route = useRoute()
const config = useRuntimeConfig()
const id = route.params.id

const successMessage = ref('')
const errorMessage = ref('')
const loading = ref(false)
const teachers = ref<User[]>([])

const { data } = await useFetch<{ course: Course }>(
  `${config.public.apiBase}/courses/${id}`
)

const course = computed(() => data.value?.course)
const updateCourse = async () => {
  if (!course.value) return

  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await $fetch(`${config.public.apiBase}/courses/${id}`, {
      method: 'PUT',
      body: course.value,
    })

    successMessage.value = 'Course updated successfully!'
    setTimeout(() => navigateTo('/admin'), 1500)
  } catch (err: any) {
    console.error('Update Failed', err)
    errorMessage.value = err?.data?.message || 'Failed to update course'
  } finally {
    loading.value = false
  }
}

useHead({
  title: `Edit Course - ${course.value?.title || 'Loading...'}`
})
</script>

<style scoped>
.page-container {
  max-width: 1200px;
  margin: 0 auto;
}

.card {
  border-radius: 12px;
}

.card-header {
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
  padding: 20px 24px;
}
</style>