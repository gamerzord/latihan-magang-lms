<template>
  <v-container fluid class="page-container">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" class="card">
          <!-- Header -->
          <v-card-title class="d-flex justify-space-between align-center card-header">
            <div class="d-flex align-center">
              <span class="text-h5 font-weight-bold">Enrollment Management</span>
              <v-tooltip location="bottom">
                <template #activator="{ props }">
                  <v-icon v-bind="props" color="grey" class="ml-2" size="small">
                    mdi-information
                  </v-icon>
                </template>
                <span>Manage student enrollments in available courses</span>
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

          <!-- Table -->
          <v-card-text class="pa-0">
            <v-data-table
              :headers="headers"
              :items="enrollments || []"
              :loading="pending"
              item-value="id"
              class="elevation-0"
              loading-text="Loading enrollments..."
              no-data-text="No enrollments found"
              items-per-page-text="Enrollments per page:"
              :items-per-page-options="[10, 25, 50, 100]"
            >
              <!-- Course Column -->
              <template #item.course="{ item }">
                <div class="d-flex justify-center">
                  <v-chip color="blue-lighten-3" size="small">
                    <v-icon start small>mdi-book-open-variant</v-icon>
                    {{ item.course?.title || 'Unknown Course' }}
                  </v-chip>
                </div>
              </template>

              <!-- Student Column -->
              <template #item.student="{ item }">
                <div class="d-flex justify-center">
                  <v-chip color="green-lighten-3" size="small">
                    <v-icon start small>mdi-account</v-icon>
                    {{ item.student?.name || 'Unknown Student' }}
                  </v-chip>
                </div>
              </template>

              <!-- Created Date -->
              <template #item.created_at="{ item }">
                <div class="text-center">
                  {{ new Date(item.created_at).toLocaleString() }}
                </div>
              </template>

              <!-- Actions -->
              <template #item.actions="{ item }">
                <div class="d-flex justify-center">
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
                      Delete Enrollment
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
            Are you sure you want to delete this enrollment of
            <strong>{{ enrollmentToDelete?.student?.name }}</strong>
            in
            <strong>"{{ enrollmentToDelete?.course?.title }}"</strong>?
          </p>
          <p class="text-caption text-medium-emphasis mt-2">
            This action cannot be undone.
          </p>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog = false" :disabled="loading">
            Cancel
          </v-btn>
          <v-btn color="error" @click="deleteEnrollment" :loading="loading" variant="flat">
            Delete Enrollment
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
import type { Enrollment } from '~/types/models'

const config = useRuntimeConfig()
const loading = ref(false)
const currentActionId = ref<number | null>(null)
const deleteDialog = ref(false)
const enrollmentToDelete = ref<Enrollment | null>(null)

const snackbar = ref({
  show: false,
  message: '',
  color: 'success',
  icon: 'mdi-check-circle'
})

const { data, pending, refresh } = await useFetch<{ enrollments: Enrollment[] }>(
  `${config.public.apiBase}/enrollments`
)
const enrollments = computed(() => data.value?.enrollments || [])

const headers = [
  { title: 'ID', key: 'id', align: 'center', sortable: true },
  { title: 'Course', key: 'course', align: 'center', sortable: false },
  { title: 'Student', key: 'student', align: 'center', sortable: false },
  { title: 'Created At', key: 'created_at', align: 'center', sortable: true },
  { title: 'Actions', key: 'actions', align: 'center', sortable: false }
] as const

// Snackbar helper
const showSnackbar = (message: string, type: 'success' | 'error' = 'success') => {
  Object.assign(snackbar.value, {
    show: true,
    message,
    color: type === 'success' ? 'success' : 'error',
    icon: type === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle'
  })
}

const confirmDelete = (enrollment: Enrollment) => {
  enrollmentToDelete.value = enrollment
  deleteDialog.value = true
}

const deleteEnrollment = async () => {
  if (!enrollmentToDelete.value) return
  loading.value = true
  currentActionId.value = enrollmentToDelete.value.id

  try {
    await $fetch(`${config.public.apiBase}/enrollments/${enrollmentToDelete.value.id}`, {
      method: 'DELETE'
    })

    data.value!.enrollments = data.value!.enrollments.filter(
      e => e.id !== enrollmentToDelete.value?.id
    )

    showSnackbar('Enrollment deleted successfully')
    deleteDialog.value = false
    enrollmentToDelete.value = null
  } catch (err: any) {
    showSnackbar(err?.data?.message || 'Failed to delete enrollment', 'error')
  } finally {
    loading.value = false
    currentActionId.value = null
  }
}

useHead({
  title: 'Enrollment Management'
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

.gap-2 {
  gap: 8px;
}
</style>
