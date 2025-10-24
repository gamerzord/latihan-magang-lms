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
      <!-- Student Info Column -->
      <template #item.student="{ item }">
        <div v-if="item.student">
          <strong>{{ item.student.name }}</strong>
          <div class="text-caption text-medium-emphasis">
            {{ item.student.email }}
          </div>
        </div>
        <div v-else class="text-medium-emphasis">
          Unknown Student
        </div>
      </template>

      <!-- Assignment Column -->
      <template #item.assignment="{ item }">
        <div v-if="item.assignment">
          {{ item.assignment.title }}
        </div>
        <div v-else class="text-medium-emphasis">
          Unknown Assignment
        </div>
      </template>

      <!-- Submitted At Column -->
      <template #item.submitted_at="{ item }">
        {{ formatDate(item.created_at) }}
      </template>

      <!-- Status Column -->
      <template #item.status="{ item }">
        <v-chip
          size="small"
          :color="getStatusColor(item.status)"
          variant="outlined"
        >
          {{ formatStatus(item.status) }}
        </v-chip>
      </template>

      <!-- Grade Column -->
      <template #item.grade="{ item }">
        <span v-if="item.grade !== null" class="font-weight-bold">
          {{ item.grade }}/100
        </span>
        <span v-else class="text-medium-emphasis">
          Not graded
        </span>
      </template>

      <!-- Actions Column -->
      <template #item.actions="{ item }">
        <v-btn 
          icon 
          size="small" 
          color="primary" 
          variant="text" 
          @click="$emit('view', item)"
          :disabled="!item.file_url"
        >
          <v-icon>mdi-eye</v-icon>
        </v-btn>
        <v-btn 
          v-if="item.file_url"
          icon 
          size="small" 
          color="secondary" 
          variant="text" 
          @click="downloadFile(item.id, item.filename)"
          :loading="downloadingId === item.id"
        >
          <v-icon>mdi-download</v-icon>
        </v-btn>
      </template>

      <template #no-data>
        <div class="text-center py-10 text-medium-emphasis">
          <v-icon size="48" color="grey-lighten-1">mdi-file-document-off-outline</v-icon>
          <p class="mt-2">No submissions yet.</p>
        </div>
      </template>
    </v-data-table>
  </v-card>
</template>

<script setup lang="ts">
import type { Submission } from '~/types/models'

interface SubmissionWithRelations extends Submission {
  student?: {
    name: string
    email: string
  }
  assignment?: {
    title: string
  }
}

const config = useRuntimeConfig()
const downloadingId = ref<number | null>(null)

defineProps<{
  submissions: SubmissionWithRelations[]
}>()

defineEmits<{
  view: [submission: SubmissionWithRelations]
  refresh: []
}>()

const headers = [
  { title: 'Student', key: 'student' },
  { title: 'Assignment', key: 'assignment' },
  { title: 'Submitted At', key: 'submitted_at' },
  { title: 'Status', key: 'status' },
  { title: 'Grade', key: 'grade' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const downloadFile = async (submissionId: number, filename?: string) => {
  downloadingId.value = submissionId
  try {
    const response = await $fetch(`${config.public.apiBase}/submissions/${submissionId}/download`, {
      method: 'GET',
      responseType: 'blob'
    })

    const blob = new Blob([response as BlobPart], { type: 'application/octet-stream' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename || 'submission_file'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    
  } catch (error) {
    console.error('Download failed:', error)
    alert('Failed to download file')
  } finally {
    downloadingId.value = null
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
    'submitted': 'Submitted',
    'late': 'Late',
    'not submitted': 'Not Submitted'
  }
  return statusMap[status] || status
}

const getStatusColor = (status: string) => {
  const colorMap: Record<string, string> = {
    'submitted': 'blue',
    'late': 'orange',
    'not submitted': 'grey'
  }
  return colorMap[status] || 'grey'
}
</script>