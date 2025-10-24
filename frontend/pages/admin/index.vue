<template>
  <v-container fluid class="page-container">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" class="card">
          <v-card-title class="d-flex justify-space-between align-center card-header">
            <div class="d-flex align-center">
              <span class="text-h5 font-weight-bold">Course Management</span>
              <v-tooltip location="bottom">
                <template #activator="{ props }">
                  <v-icon v-bind="props" color="grey" class="ml-2" size="small">
                    mdi-information
                  </v-icon>
                </template>
                <span>Manage available courses, teachers, and content</span>
              </v-tooltip>
            </div>

            <div class="d-flex gap-2">
              <v-btn
                variant="outlined"
                prepend-icon="mdi-refresh"
                @click="refresh"
                :loading="pending"
              >
                Refresh
              </v-btn>
            </div>
          </v-card-title>

          <v-card-text class="pa-0">
            <v-data-table
              :headers="headers"
              :items="courses || []"
              :loading="pending"
              item-value="id"
              class="elevation-0 data-table"
              loading-text="Loading courses..."
              no-data-text="No courses found"
              items-per-page-text="Courses per page:"
              :items-per-page-options="[10, 25, 50, 100]"
            >
              <!-- Teacher Column -->
              <template #item.teacher="{ item }">
                <v-chip color="blue-lighten-3" size="small">
                  <v-icon start small>mdi-account-tie</v-icon>
                  {{ item.teacher_name || 'Unknown' }}
                </v-chip>
              </template>

              <!-- Actions Column -->
              <template #item.actions="{ item }">
                <div class="d-flex gap-1">
                  <v-btn
                    color="primary"
                    size="small"
                    variant="text"
                    icon
                    @click="goToEdit(item.id)"
                    :loading="loading && currentActionId === item.id"
                  >
                    <v-icon size="small">mdi-pencil</v-icon>
                    <v-tooltip activator="parent" location="top">
                      Edit Course
                    </v-tooltip>
                  </v-btn>

                  <v-btn
                    color="error"
                    size="small"
                    variant="text"
                    icon
                    @click="confirmDelete(item)"
                    :loading="loading && currentActionId === item.id"
                  >
                    <v-icon size="small">mdi-delete</v-icon>
                    <v-tooltip activator="parent" location="top">
                      Delete Course
                    </v-tooltip>
                  </v-btn>
                </div>
              </template>

              <!-- Loading Skeleton -->
              <template #loading>
                <v-skeleton-loader type="table-row@10" />
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" max-width="500" persistent>
      <v-card>
        <v-card-title class="d-flex align-center">
          <v-icon color="error" class="mr-2">mdi-alert-circle</v-icon>
          <span class="text-h6">Confirm Deletion</span>
        </v-card-title>

        <v-card-text class="pt-4">
          <p class="text-body-1">
            Are you sure you want to delete the course
            <strong>"{{ courseToDelete?.title }}"</strong>?
          </p>
          <p class="text-caption text-medium-emphasis mt-2">
            This action cannot be undone and will permanently remove this course.
          </p>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog = false" :disabled="loading">
            Cancel
          </v-btn>
          <v-btn color="error" @click="deleteCourse" :loading="loading" variant="flat">
            Delete Course
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
      <div class="d-flex align-center">
        <v-icon class="mr-2">{{ snackbar.icon }}</v-icon>
        {{ snackbar.message }}
      </div>
    </v-snackbar>
  </v-container>
</template>

<script setup lang="ts">
import type { Course } from '~/types/models'

const config = useRuntimeConfig()
const loading = ref(false)
const currentActionId = ref<number | null>(null)
const deleteDialog = ref(false)
const courseToDelete = ref<Course | null>(null)

const snackbar = ref({
  show: false,
  message: '',
  color: 'success',
  icon: 'mdi-check-circle'
})

const { data, pending, refresh } = await useFetch<{ courses: Course[] }>(
  `${config.public.apiBase}/courses`
)

const courses = computed(() => data.value?.courses || [])

const headers = [
  { title: 'ID', key: 'id', sortable: true },
  { title: 'Title', key: 'title', sortable: true },
  { title: 'Course Code', key: 'course_code', sortable: true },
  { title: 'Teacher', key: 'teacher', sortable: false },
  { title: 'Students', key: 'students_count', sortable: true },
  { title: 'Lessons', key: 'lessons_count', sortable: true },
  { title: 'Created At', key: 'created_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false },
]

const goToEdit = (id: number) => navigateTo(`/admin/courses/${id}/edit`)

const showSnackbar = (message: string, type: 'success' | 'error' = 'success') => {
  Object.assign(snackbar.value, {
    show: true,
    message,
    color: type === 'success' ? 'success' : 'error',
    icon: type === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle'
  })
}

const confirmDelete = (course: Course) => {
  courseToDelete.value = course
  deleteDialog.value = true
}

const deleteCourse = async () => {
  if (!courseToDelete.value) return
  loading.value = true
  currentActionId.value = courseToDelete.value.id

  try {
    await $fetch(`${config.public.apiBase}/courses/${courseToDelete.value.id}`, {
      method: 'DELETE',
    })

    if (courses.value) {
      data.value!.courses = data.value!.courses.filter(c => c.id !== courseToDelete.value?.id)
    }

    showSnackbar('Course deleted successfully')
    deleteDialog.value = false
    courseToDelete.value = null
  } catch (err: any) {
    showSnackbar(err?.data?.message || 'Failed to delete course', 'error')
  } finally {
    loading.value = false
    currentActionId.value = null
  }
}

useHead({
  title: 'Course Management'
})
</script>

<style scoped>
.page-container {
  max-width: 1400px;
  margin: 0 auto;
}

.card {
  border-radius: 12px;
}

.card-header {
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
  padding: 20px 24px;
}

.data-table {
  border-radius: 0 0 12px 12px;
}

.gap-2 {
  gap: 8px;
}

.gap-1 {
  gap: 4px;
}
</style>
