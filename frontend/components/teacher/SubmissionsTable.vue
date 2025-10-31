<template>
  <v-card elevation="2" class="pa-4">
    <v-card-title class="d-flex justify-space-between align-center">
      <span class="text-h6 font-weight-bold">Student Submissions</span>
      <v-btn color="secondary" prepend-icon="mdi-refresh" @click="$emit('refresh')">
        Refresh
      </v-btn>
    </v-card-title>

    <v-data-table
      :headers="headers"
      :items="submissions"
      item-value="id"
      density="comfortable"
      class="elevation-1"
    >
      <!-- Student Info -->
      <template #item.student="{ item }">
        <div v-if="item.student">
          <strong>{{ item.student.name }}</strong>
          <div class="text-caption text-medium-emphasis">
            {{ item.student.email }}
          </div>
        </div>
        <div v-else class="text-medium-emphasis">Unknown Student</div>
      </template>

      <!-- Assignment -->
      <template #item.assignment="{ item }">
        <div v-if="item.assignment">
          {{ item.assignment.title }}
        </div>
        <div v-else class="text-medium-emphasis">Unknown Assignment</div>
      </template>

      <!-- Date -->
      <template #item.submitted_at="{ item }">
        {{ formatDate(item.created_at) }}
      </template>

      <!-- Status -->
      <template #item.status="{ item }">
        <v-chip size="small" :color="getStatusColor(item.status)" variant="outlined">
          {{ formatStatus(item.status) }}
        </v-chip>
      </template>

      <!-- Grade -->
      <template #item.grade="{ item }">
        <span v-if="item.grade !== null" class="font-weight-bold">
          {{ item.grade }}/100
        </span>
        <span v-else class="text-medium-emphasis">Not graded</span>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <v-btn
          v-if="item.file_url"
          icon
          size="small"
          color="secondary"
          variant="text"
          :href="getDownloadUrl(item.id)"
          target="_blank"
          :loading="downloadingId === item.id"
          @click="handleDownloadClick"
        >
          <v-icon>mdi-download</v-icon>
        </v-btn>

        <v-btn
          icon
          size="small"
          color="primary"
          variant="text"
          @click="openGradeDialog(item)"
        >
          <v-icon>mdi-pencil</v-icon>
        </v-btn>
      </template>

      <template #no-data>
        <div class="text-center py-10 text-medium-emphasis">
          <v-icon size="48" color="grey-lighten-1">mdi-file-document-off-outline</v-icon>
          <p class="mt-2">No submissions yet.</p>
        </div>
      </template>
    </v-data-table>

    <!-- Grade Dialog -->
    <v-dialog v-model="gradeDialog" max-width="400px">
      <v-card>
        <v-card-title class="font-weight-bold">Grade Submission</v-card-title>
        <v-card-text>
          <div v-if="selectedSubmission">
            <p>
              <strong>Student:</strong> {{ selectedSubmission.student?.name }}
              <br />
              <strong>Assignment:</strong> {{ selectedSubmission.assignment?.title }}
            </p>
            <v-text-field
              v-model.number="gradeValue"
              type="number"
              label="Grade (0 - 100)"
              min="0"
              max="100"
              outlined
              required
            />
            <v-select
              v-model="statusValue"
              :items="['submitted', 'late', 'not_submitted']"
              label="Status"
              outlined
            />
          </div>
        </v-card-text>

        <v-card-actions class="d-flex justify-end">
          <v-btn variant="text" @click="gradeDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="submitGrade" :loading="grading">
            Save
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup lang="ts">
import type { Submission } from '~/types/models'

interface SubmissionWithRelations extends Submission {
  student?: { name: string; email: string }
  assignment?: { title: string }
}

const config = useRuntimeConfig()
const downloadingId = ref<number | null>(null)
const gradeDialog = ref(false)
const grading = ref(false)
const selectedSubmission = ref<SubmissionWithRelations | null>(null)
const gradeValue = ref<number | null>(null)
const statusValue = ref<string>('submitted')

defineProps<{
  submissions: SubmissionWithRelations[]
}>()

const emit = defineEmits<{
  view: [submission: SubmissionWithRelations]
  refresh: []
}>()

const headers = [
  { title: 'Student', key: 'student', align: 'center' },
  { title: 'Assignment', key: 'assignment', align: 'center' },
  { title: 'Submitted At', key: 'submitted_at', align: 'center' },
  { title: 'Status', key: 'status', align: 'center' },
  { title: 'Grade', key: 'grade', align: 'center' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'center' }
] as const

const handleDownloadClick = async (submissionId: number) => {
  downloadingId.value = submissionId
  setTimeout(() => {
    downloadingId.value = null
  }, 1000)
}

const getDownloadUrl = (submissionId: number) => {
  return `${config.public.apiBase}/submissions/${submissionId}/download`
}

const openGradeDialog = (submission: SubmissionWithRelations) => {
  selectedSubmission.value = submission
  gradeValue.value = submission.grade ?? 0
  statusValue.value = submission.status ?? 'submitted'
  gradeDialog.value = true
}

const submitGrade = async () => {
  if (!selectedSubmission.value || gradeValue.value === null) return

  grading.value = true
  try {
    await $fetch(`${config.public.apiBase}/submissions/${selectedSubmission.value.id}`, {
      method: 'PUT',
      body: {
        assignment_id: selectedSubmission.value.assignment_id,
        student_id: selectedSubmission.value.student_id,
        grade: gradeValue.value,
        status: statusValue.value
      }
    })
    alert('Grade saved successfully')
    gradeDialog.value = false
    emit('refresh')
  } catch (err) {
    console.error(err)
    alert('Failed to save grade')
  } finally {
    grading.value = false
  }
}

const formatDate = (dateString: Date | string) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatStatus = (status: string) => {
  const statusMap: Record<string, string> = {
    submitted: 'Submitted',
    late: 'Late',
    not_submitted: 'Not Submitted'
  }
  return statusMap[status] || status
}

const getStatusColor = (status: string) => {
  const colorMap: Record<string, string> = {
    submitted: 'blue',
    late: 'orange',
    not_submitted: 'grey'
  }
  return colorMap[status] || 'grey'
}
</script>
