<template>
  <div class="student-assignments mt-8">
    <v-card elevation="2" class="pa-4">
      <v-card-title>
        <h3 class="text-h6">Assignments</h3>
      </v-card-title>

      <v-card-text>
        <!-- Loading -->
        <template v-if="pending">
          <div class="text-center py-6">
            <v-progress-circular indeterminate color="primary" />
          </div>
        </template>

        <!-- Data Table -->
        <template v-else-if="assignments && assignments.length">
          <v-data-table
            :headers="headers"
            :items="assignments"
            density="compact"
            class="elevation-1"
          >
            <template #item.due_date="{ item }">
              <div class="text-center">
                {{ formatDate(item.due_date) }}
              </div>
            </template>

            <template #item.status="{ item }">
              <div class="text-center">
                <v-chip
                  :color="getStatusColor(getSubmissionStatus(item.id))"
                  size="small"
                  variant="outlined"
                >
                  {{ getSubmissionStatus(item.id) }}
                </v-chip>
              </div>
            </template>

            <template #item.grade="{ item }">
              <div class="text-center">
                <span v-if="getSubmission(item.id)?.grade !== undefined">
                  {{ getSubmission(item.id)?.grade ?? '-' }}
                </span>
                <span v-else class="text-medium-emphasis">-</span>
              </div>
            </template>

            <template #item.actions="{ item }">
              <div class="text-center">
                <v-btn
                  color="primary"
                  variant="text"
                  size="small"
                  @click="openSubmitDialog(item)"
                >
                  Submit
                </v-btn>
              </div>
            </template>
          </v-data-table>
        </template>

        <!-- Empty State -->
        <template v-else>
          <div class="text-center py-8 text-medium-emphasis">
            <v-icon size="48" class="mb-2">mdi-clipboard-off-outline</v-icon>
            <p>No assignments yet.</p>
            <p class="text-caption">Check back later for updates.</p>
          </div>
        </template>
      </v-card-text>
    </v-card>

    <!-- Submit Dialog -->
    <v-dialog v-model="showDialog" max-width="500">
      <v-card>
        <v-card-title>Submit Assignment</v-card-title>
            <v-card-text>
                <div class="upload-box">
                    <v-file-input
                        v-model="selectedFile"
                        label="Select or drop your submission file"
                        accept=".pdf,.doc,.docx,.zip,.rar"
                        prepend-icon="mdi-file-upload-outline"
                        show-size
                        variant="outlined"
                        density="comfortable"
                        clearable
                        hide-details
                    />
                </div>
            </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn text @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" @click="submitAssignment" :loading="uploading">
            Submit
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import type { Assignment, Submission, Course } from '~/types/models'

const props = defineProps<{
  courseId: number
  studentId?: number
}>()

const config = useRuntimeConfig()

const { data, pending } = await useFetch<{ course: Course }>(
  `${config.public.apiBase}/student/courses/${props.courseId}`
)

const assignments = computed(() => data.value?.course.assignments || [])

const { data: submissionsData, refresh } = await useFetch<{ submissions: Submission[] }>(
  `${config.public.apiBase}/submissions`
)

const submissions = computed(() => submissionsData.value?.submissions || [])

const getSubmission = (assignmentId: number) =>
  submissions.value?.find(
    (s) => s.assignment_id === assignmentId && s.student_id === props.studentId
  )

const getSubmissionStatus = (assignmentId: number) => {
  const submission = getSubmission(assignmentId)
  return submission?.status ?? 'not_submitted'
}

const getStatusColor = (status: string) => {
  const map: Record<string, string> = {
    submitted: 'green',
    late: 'orange',
    not_submitted: 'grey'
  }
  return map[status] || 'grey'
}

const formatDate = (dateString: string | Date) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const headers = [
  { title: 'Title', key: 'title'},
  { title: 'Due Date', key: 'due_date'},
  { title: 'Status', key: 'status'},
  { title: 'Grade', key: 'grade'},
  { title: 'Action', key: 'actions'}
]

const showDialog = ref(false)
const selectedAssignment = ref<Assignment | null>(null)
const selectedFile = ref<File | null>(null)
const uploading = ref(false)

const openSubmitDialog = (assignment: Assignment) => {
  selectedAssignment.value = assignment
  showDialog.value = true
}

const closeDialog = () => {
  showDialog.value = false
  selectedFile.value = null
}

const submitAssignment = async () => {
  if (!selectedAssignment.value || !selectedFile.value || !props.studentId) return
  uploading.value = true

  try {
    const formData = new FormData()
    formData.append('assignment_id', selectedAssignment.value.id.toString())
    formData.append('student_id', props.studentId.toString())
    formData.append('file', selectedFile.value)

    await $fetch(`${config.public.apiBase}/submissions`, {
      method: 'POST',
      body: formData,
      headers: {
        'Accept': 'application/json',
        // Don't set Content-Type - let browser set it with boundary for FormData
      },
    })

    await refresh()
    closeDialog()
  } catch (err) {
    console.error(err)
  } finally {
    uploading.value = false
  }
}
</script>

<style scoped>
.v-data-table th.text-center,
.v-data-table td.text-center {
  text-align: center;
}
.v-data-table th.text-start,
.v-data-table td.text-start {
  text-align: left;
}

.upload-box {
  border: 2px dashed var(--v-theme-primary);
  border-radius: 12px;
  padding: 24px;
  background-color: rgba(0, 0, 0, 0.02);
  transition: background-color 0.2s ease, border-color 0.2s ease;
}

.upload-box:hover {
  background-color: rgba(0, 0, 0, 0.05);
  border-color: var(--v-theme-primary-darken-1);
}

.upload-box .v-file-input {
  width: 100%;
}

</style>
