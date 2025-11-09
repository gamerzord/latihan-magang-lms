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
                <span v-if="getSubmission(item.id)?.grade !== undefined && getSubmission(item.id)?.grade !== null">
                  {{ getSubmission(item.id)?.grade }}/100
                </span>
                <span v-else class="text-medium-emphasis">Not graded</span>
              </div>
            </template>

            <template #item.actions="{ item }">
              <div class="text-center d-flex justify-center gap-2">
                <v-btn
                  v-if="!getSubmission(item.id)"
                  color="primary"
                  variant="flat"
                  size="small"
                  @click="openSubmitDialog(item)"
                >
                  <v-icon start>mdi-upload</v-icon>
                  Submit
                </v-btn>
                <template v-else>
                  <v-btn
                    color="success"
                    variant="outlined"
                    size="small"
                    :href="getDownloadUrl(getSubmission(item.id)?.file_url)"
                    target="_blank"
                  >
                    <v-icon start>mdi-download</v-icon>
                    View
                  </v-btn>
                  <v-btn
                    color="primary"
                    variant="text"
                    size="small"
                    @click="openResubmitDialog(item)"
                  >
                    <v-icon start>mdi-refresh</v-icon>
                    Resubmit
                  </v-btn>
                </template>
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

    <!-- Submit/Resubmit Dialog -->
    <v-dialog v-model="showDialog" max-width="600" persistent>
      <v-card>
        <v-card-title class="d-flex justify-space-between align-center">
          <span>{{ isResubmit ? 'Resubmit' : 'Submit' }} Assignment</span>
          <v-btn icon variant="text" @click="closeDialog">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-divider />

        <v-card-text class="pt-4">
          <div v-if="selectedAssignment" class="mb-4">
            <h4 class="text-subtitle-1 mb-2">{{ selectedAssignment.title }}</h4>
            <p class="text-body-2 text-medium-emphasis">
              {{ selectedAssignment.description }}
            </p>
            <div class="d-flex align-center mt-2 text-caption">
              <v-icon small class="mr-1">mdi-calendar</v-icon>
              Due: {{ formatDate(selectedAssignment.due_date) }}
            </div>
          </div>

          <v-divider class="my-4" />

          <!-- Current Submission Info (for resubmit) -->
          <div v-if="isResubmit && currentSubmission" class="mb-4 pa-3 bg-grey-lighten-4 rounded">
            <div class="text-caption text-medium-emphasis mb-2">Current Submission:</div>
            <div class="d-flex align-center justify-space-between">
              <div class="d-flex align-center">
                <v-icon :color="getFileIconColor(currentSubmission.mimetype)" class="mr-2">
                  {{ getFileIcon(currentSubmission.mimetype) }}
                </v-icon>
                <div>
                  <div class="text-body-2">{{ currentSubmission.filename }}</div>
                  <div class="text-caption text-medium-emphasis">
                    Submitted: {{ formatDate(currentSubmission.created_at) }}
                  </div>
                </div>
              </div>
              <v-chip size="small" :color="getStatusColor(currentSubmission.status)">
                {{ currentSubmission.status }}
              </v-chip>
            </div>
          </div>

          <!-- File Upload -->
          <div class="upload-box">
            <v-file-input
              v-model="selectedFile"
              label="Select your submission file"
              accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.rar"
              prepend-icon="mdi-file-upload-outline"
              show-size
              variant="outlined"
              density="comfortable"
              clearable
              :rules="[fileRequiredRule, fileSizeRule]"
              hint="Accepted formats: PDF, DOC, DOCX, PPT, PPTX, ZIP, RAR (Max 10MB)"
              persistent-hint
            >
              <template #selection="{ fileNames }">
                <v-chip
                  v-if="fileNames.length"
                  color="primary"
                  label
                  class="me-2"
                >
                  <v-icon start>mdi-file-document</v-icon>
                  {{ fileNames[0] }}
                </v-chip>
              </template>
            </v-file-input>
          </div>

          <!-- File Preview -->
          <div v-if="selectedFile" class="mt-4 pa-3 bg-grey-lighten-5 rounded">
            <div class="text-caption text-medium-emphasis mb-2">Selected File:</div>
            <div class="d-flex align-center">
              <v-icon :color="getFileIconColor(selectedFile.type)" class="mr-2">
                {{ getFileIcon(selectedFile.type) }}
              </v-icon>
              <div>
                <div class="text-body-2">{{ selectedFile.name }}</div>
                <div class="text-caption">{{ formatFileSize(selectedFile.size) }}</div>
              </div>
            </div>
          </div>

          <!-- Error Message -->
          <v-alert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            class="mt-4"
            closable
            @click:close="errorMessage = ''"
          >
            {{ errorMessage }}
          </v-alert>

          <!-- Success Message -->
          <v-alert
            v-if="successMessage"
            type="success"
            variant="tonal"
            class="mt-4"
          >
            {{ successMessage }}
          </v-alert>
        </v-card-text>

        <v-divider />

        <v-card-actions class="px-4 py-3">
          <v-spacer />
          <v-btn
            variant="outlined"
            @click="closeDialog"
            :disabled="uploading"
          >
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            variant="flat"
            @click="submitAssignment"
            :loading="uploading"
            :disabled="!selectedFile || uploading"
          >
            <v-icon start>mdi-upload</v-icon>
            {{ isResubmit ? 'Resubmit' : 'Submit' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import type { Assignment, Submission, Course } from '~/types/models'

interface Props {
  courseId: number
  studentId?: number
}

const props = defineProps<Props>()
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

const getDownloadUrl = (fileUrl: string | undefined) => {
  if (!fileUrl) return ''
  
  if (fileUrl.startsWith('http://') || fileUrl.startsWith('https://')) {
    return fileUrl
  }
  
  const baseUrl = config.public.apiBase.replace('/api', '')
  return `${baseUrl}/storage/${fileUrl}`
}

const getFileIcon = (mimeType: string) => {
  if (mimeType.includes('pdf')) return 'mdi-file-pdf-box'
  if (mimeType.includes('word') || mimeType.includes('document')) return 'mdi-file-word'
  if (mimeType.includes('sheet') || mimeType.includes('excel')) return 'mdi-file-excel'
  if (mimeType.includes('presentation') || mimeType.includes('powerpoint')) return 'mdi-file-powerpoint'
  if (mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('compressed')) return 'mdi-folder-zip'
  return 'mdi-file-document'
}

const getFileIconColor = (mimeType: string) => {
  if (mimeType.includes('pdf')) return 'red'
  if (mimeType.includes('word')) return 'blue'
  if (mimeType.includes('sheet') || mimeType.includes('excel')) return 'green'
  if (mimeType.includes('presentation')) return 'orange'
  if (mimeType.includes('zip') || mimeType.includes('rar')) return 'purple'
  return 'grey'
}

const formatFileSize = (bytes: number) => {
  if (!bytes) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
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
  { title: 'Title', key: 'title', align: 'start', sortable: true },
  { title: 'Description', key: 'description', align: 'start', sortable: false },
  { title: 'Due Date', key: 'due_date', align: 'center', sortable: true },
  { title: 'Status', key: 'status', align: 'center', sortable: true },
  { title: 'Grade', key: 'grade', align: 'center', sortable: true },
  { title: 'Action', key: 'actions', align: 'center', sortable: false }
] as const

const showDialog = ref(false)
const selectedAssignment = ref<Assignment | null>(null)
const selectedFile = ref<File | null>(null)
const uploading = ref(false)
const isResubmit = ref(false)
const currentSubmission = ref<Submission | null>(null)
const errorMessage = ref('')
const successMessage = ref('')

const fileRequiredRule = (value: File | null) => {
  return !!value || 'Please select a file'
}

const fileSizeRule = (value: File | null) => {
  if (!value) return true
  const maxSize = 10 * 1024 * 1024 // 10MB
  return value.size <= maxSize || 'File size must be less than 10MB'
}

const openSubmitDialog = (assignment: Assignment) => {
  selectedAssignment.value = assignment
  isResubmit.value = false
  currentSubmission.value = null
  showDialog.value = true
}

const openResubmitDialog = (assignment: Assignment) => {
  selectedAssignment.value = assignment
  isResubmit.value = true
  currentSubmission.value = getSubmission(assignment.id) || null
  showDialog.value = true
}

const closeDialog = () => {
  showDialog.value = false
  selectedFile.value = null
  selectedAssignment.value = null
  isResubmit.value = false
  currentSubmission.value = null
  errorMessage.value = ''
  successMessage.value = ''
}

const submitAssignment = async () => {
  if (!selectedAssignment.value || !selectedFile.value || !props.studentId) {
    errorMessage.value = 'Please select a file to submit'
    return
  }

  uploading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const formData = new FormData()
    formData.append('assignment_id', selectedAssignment.value.id.toString())
    formData.append('student_id', props.studentId.toString())
    formData.append('file', selectedFile.value)

    const endpoint = isResubmit.value && currentSubmission.value
      ? `${config.public.apiBase}/submissions/${currentSubmission.value.id}`
      : `${config.public.apiBase}/submissions`

    const method = isResubmit.value && currentSubmission.value ? 'PUT' : 'POST'

    await $fetch(endpoint, {
      method,
      body: formData,
    })

    successMessage.value = isResubmit.value 
      ? 'Assignment resubmitted successfully!' 
      : 'Assignment submitted successfully!'

    await refresh()
    
    setTimeout(() => {
      closeDialog()
    }, 1500)

  } catch (err: any) {
    console.error('Submission error:', err)
    errorMessage.value = err.data?.message || 'Failed to submit assignment. Please try again.'
  } finally {
    uploading.value = false
  }
}
</script>

<style scoped>
.v-data-table :deep(th.text-center),
.v-data-table :deep(td.text-center) {
  text-align: center;
}

.v-data-table :deep(th.text-start),
.v-data-table :deep(td.text-start) {
  text-align: left;
}

.upload-box {
  border: 2px dashed rgb(var(--v-theme-primary));
  border-radius: 12px;
  padding: 24px;
  background-color: rgba(0, 0, 0, 0.02);
  transition: all 0.2s ease;
}

.upload-box:hover {
  background-color: rgba(0, 0, 0, 0.05);
  border-color: rgb(var(--v-theme-primary-darken-1));
}

.gap-2 {
  gap: 8px;
}
</style>