<template>
  <v-container fluid class="page-container">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" class="card">
          <v-card-title class="d-flex justify-space-between align-center card-header">
            <div class="d-flex align-center">
              <span class="text-h5 font-weight-bold">Edit Assignment</span>
              <v-tooltip location="bottom">
                <template #activator="{ props }">
                  <v-icon v-bind="props" color="grey" class="ml-2" size="small">
                    mdi-information
                  </v-icon>
                </template>
                <span>Update assignment details and due date</span>
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
            
            <v-form v-if="assignment" v-model="formValid" @submit.prevent="updateAssignment">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="assignment.assignment_code"
                    label="Assignment Code"
                    placeholder="e.g., ASSIGN-01"
                    variant="outlined"
                    :rules="[requiredRule]"
                    required
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="assignment.title"
                    label="Title"
                    placeholder="e.g., HTML Project"
                    variant="outlined"
                    :rules="[requiredRule]"
                    required
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="assignment.description"
                    label="Description"
                    placeholder="Brief instructions or goals for this assignment..."
                    variant="outlined"
                    rows="4"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="formattedDueDate"
                    label="Due Date"
                    type="datetime-local"
                    variant="outlined"
                    :rules="[requiredRule]"
                    required
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
                Loading assignment data...
              </div>
            </v-alert>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import type { Assignment } from '~/types/models'

const route = useRoute()
const config = useRuntimeConfig()
const id = route.params.id

const successMessage = ref('')
const errorMessage = ref('')
const loading = ref(false)
const formValid = ref(false)

const { data } = await useFetch<{ assignment: Assignment }>(
  `${config.public.apiBase}/assignments/${id}`
)

const assignment = computed(() => data.value?.assignment)

const formattedDueDate = computed({
  get: () => {
    if (!assignment.value?.due_date) return ''
    const date = new Date(assignment.value.due_date)
    return date.toISOString().slice(0, 16)
  },
  set: (value: string) => {
    if (assignment.value && value) {
      assignment.value.due_date = new Date(value).toISOString()
    }
  }
})


const requiredRule = (v: string) => !!v || 'This field is required'

const updateAssignment = async () => {
  if (!assignment.value || !formValid.value) return

  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await $fetch(`${config.public.apiBase}/assignments/${id}`, {
      method: 'PUT',
      body: assignment.value,
    })

    successMessage.value = 'Assignment updated successfully!'
    setTimeout(() => navigateTo('/teacher/courses'), 1500)
  } catch (err: any) {
    console.error('Update Failed', err)
    errorMessage.value = err?.data?.message || 'Failed to update assignment'
  } finally {
    loading.value = false
  }
}

useHead({
  title: `Edit Assignment - ${assignment.value?.title || 'Loading...'}`
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