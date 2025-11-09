<template>
  <div class="student-course-details">
    <v-container>
      <v-row>
        <v-col cols="12" md="8" offset-md="2">

          <LiveConferenceBanner 
          v-if="course"
          :course-id="course.id" 
          />

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

                <div v-if="course.lessons?.length">
                  <h3 class="text-h6 mb-4">Lessons</h3>
                  
                  <v-expansion-panels variant="accordion" class="lessons-accordion">
                    <v-expansion-panel
                      v-for="(lesson, index) in course.lessons"
                      :key="lesson.id"
                      class="mb-2"
                    >
                      <v-expansion-panel-title class="text-body-1 font-weight-medium">
                        <div class="d-flex align-center">
                          <v-avatar size="32" color="primary" class="mr-3">
                            <span class="text-white text-caption">{{ index + 1 }}</span>
                          </v-avatar>
                          {{ lesson.title }}
                        </div>
                      </v-expansion-panel-title>
                      
                      <v-expansion-panel-text>
                        <!-- Lesson Content -->
                        <div class="lesson-content">

                          <!-- Text Content -->
                          <div v-if="lesson.content" class="mb-4">
                            <p class="text-caption text-medium-emphasis mb-2">Lesson Content:</p>
                            <div class="text-body-2 lesson-text" style="white-space: pre-wrap;">
                              {{ extractLessonText(lesson.content) }}
                            </div>
                          </div>

                          <!-- YouTube Video Embed -->
                          <div v-if="getYouTubeVideoId(lesson.content)" class="mb-4">
                            <p class="text-caption text-medium-emphasis mb-2">Video Lesson:</p>
                            <iframe
                              width="100%"
                              height="315"
                              :src="`https://www.youtube.com/embed/${getYouTubeVideoId(lesson.content)}`"
                              frameborder="0"
                              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                              allowfullscreen
                              class="rounded-lg"
                            ></iframe>
                          </div>

                          <!-- PDF Embed -->
                          <div v-if="isPdfUrl(lesson.content)" class="mb-4">
                            <p class="text-caption text-medium-emphasis mb-2">PDF Document:</p>
                            <iframe
                              width="100%"
                              height="500"
                              :src="getProcessedPdfUrl(lesson.content)"
                              frameborder="0"
                              class="pdf-iframe rounded-lg"
                            ></iframe>
                            <div class="d-flex justify-end mt-2">
                              <v-btn
                                variant="outlined"
                                size="small"
                                :href="lesson.content"
                                target="_blank"
                                rel="noopener noreferrer"
                              >
                                <v-icon start>mdi-download</v-icon>
                                Download PDF
                              </v-btn>
                            </div>
                          </div>

                          <!-- File Attachments with Download -->
                          <div v-if="lesson.attachments?.length" class="mb-4">
                            <p class="text-caption text-medium-emphasis mb-3">
                              <v-icon small class="mr-1">mdi-paperclip</v-icon>
                              Course Materials ({{ lesson.attachments.length }})
                            </p>
                            <v-list density="compact" class="bg-grey-lighten-5 rounded-lg">
                              <v-list-item
                                v-for="attachment in lesson.attachments"
                                :key="attachment.id"
                                class="attachment-item"
                              >
                                <template #prepend>
                                  <v-icon :color="getFileIconColor(attachment.mime_type)">
                                    {{ getFileIcon(attachment.mime_type) }}
                                  </v-icon>
                                </template>
                                
                                <v-list-item-title class="text-body-2">
                                  {{ attachment.file_name }}
                                </v-list-item-title>
                                
                                <v-list-item-subtitle class="text-caption">
                                  {{ formatFileSize(attachment.file_size) }} • {{ getFileTypeLabel(attachment.file_type) }}
                                </v-list-item-subtitle>

                                <template #append>
                                  <v-btn
                                    icon
                                    variant="text"
                                    size="small"
                                    color="primary"
                                    :href="getDownloadUrl(attachment.file_url)"
                                    target="_blank"
                                    :loading="downloadingFile === attachment.id"
                                    :disabled="!attachment.file_url"
                                    @click="handleAttachmentDownload(attachment.id)"
                                  >
                                    <v-icon>mdi-download</v-icon>
                                  </v-btn>
                                </template>
                              </v-list-item>
                            </v-list>
                          </div>

                          <!-- Lesson Metadata -->
                          <div class="d-flex justify-space-between align-center mt-4 pt-3 border-t">
                            <div class="text-caption text-medium-emphasis">
                              <v-icon small class="mr-1">mdi-clock-outline</v-icon>
                              Created: {{ formatDate(lesson.created_at) }}
                            </div>
                            <v-chip
                              v-if="lesson.lesson_code"
                              size="small"
                              variant="outlined"
                              color="primary"
                            >
                              {{ lesson.lesson_code }}
                            </v-chip>
                          </div>
                        </div>
                      </v-expansion-panel-text>
                    </v-expansion-panel>
                  </v-expansion-panels>
                </div>

                <div v-else class="text-center py-8 text-medium-emphasis">
                  <v-icon size="48" class="mb-2">mdi-book-off-outline</v-icon>
                  <p>No lessons available yet.</p>
                  <p class="text-caption">Check back later for course content.</p>
                </div>
              </v-card-text>

              <StudentAssignmentsList 
                v-if="user?.id"
                :course-id="course.id" 
                :student-id="user.id" 
              />
              
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
import LiveConferenceBanner from '~/components/student/LiveConferenceBanner.vue'
import StudentAssignmentsList from '~/components/student/StudentAssignmentsList.vue'
import type { Course } from '~/types/models'

const route = useRoute()
const config = useRuntimeConfig()
const { user } = useAuth()
const downloadingFile = ref<number | null>(null)

const  { data, pending, refresh } = await useFetch<{ course: Course }>(
  `${config.public.apiBase}/student/courses/${route.params.id}`,
)

const course = computed(() => data.value?.course)

useAutoRefresh(async () => {
  if (!data.value) {
    refresh()
  }
})

const handleAttachmentDownload = (attachmentId: number) => {
  downloadingFile.value = attachmentId
  setTimeout(() => {
    downloadingFile.value = null
  }, 2000)
}

const getDownloadUrl = (fileUrl: string) => {
  if (fileUrl.startsWith('http://') || fileUrl.startsWith('https://')) {
    return fileUrl
  }

  const baseUrl = config.public.apiBase.replace('/api', '')
  return `${baseUrl}${fileUrl}`
}

const getFileIcon = (mimeType: string) => {
  if (mimeType.includes('pdf')) return 'mdi-file-pdf-box'
  if (mimeType.includes('word') || mimeType.includes('document')) return 'mdi-file-word'
  if (mimeType.includes('sheet') || mimeType.includes('excel')) return 'mdi-file-excel'
  if (mimeType.includes('presentation') || mimeType.includes('powerpoint')) return 'mdi-file-powerpoint'
  if (mimeType.includes('image')) return 'mdi-file-image'
  if (mimeType.includes('video')) return 'mdi-file-video'
  if (mimeType.includes('zip') || mimeType.includes('rar')) return 'mdi-folder-zip'
  return 'mdi-file-document'
}

const getFileIconColor = (mimeType: string) => {
  if (mimeType.includes('pdf')) return 'red'
  if (mimeType.includes('word')) return 'blue'
  if (mimeType.includes('sheet') || mimeType.includes('excel')) return 'green'
  if (mimeType.includes('presentation')) return 'orange'
  if (mimeType.includes('image')) return 'purple'
  if (mimeType.includes('video')) return 'pink'
  return 'grey'
}

const getFileTypeLabel = (fileType: string) => {
  const labels: Record<string, string> = {
    pdf: 'PDF',
    doc: 'Document',
    image: 'Image',
    video: 'Video',
    audio: 'Audio',
    other: 'File'
  }
  return labels[fileType] || 'File'
}

const isPdfUrl = (url: string) => {
  if (!url) return false
  
  const content = url.trim()
  const service = detectPdfService(content)
  
  const isKnownService = service === 'dropbox' || service === 'google-drive'
  const isDirectPdf = service === 'direct' && content.toLowerCase().endsWith('.pdf')
  
  return isKnownService || isDirectPdf
}

const getProcessedPdfUrl = (url: string) => {
  if (!url) return ''
  
  const service = detectPdfService(url)
  
  switch (service) {
    case 'dropbox':
      return url.replace('www.dropbox.com', 'dl.dropboxusercontent.com')
               .replace('?dl=0', '')
    
    case 'google-drive':
      const fileId = extractGoogleDriveFileId(url)
      return `https://drive.google.com/file/d/${fileId}/preview`
    
    case 'direct':
    default:
      return `https://docs.google.com/gview?url=${encodeURIComponent(url)}&embedded=true`
  }
}

const detectPdfService = (url: string): 'dropbox' | 'google-drive' | 'direct' => {
  if (url.includes('drive.google.com')) return 'google-drive'
  if (url.includes('dropbox.com')) return 'dropbox'
  return 'direct'
}

const extractGoogleDriveFileId = (url: string): string => {
  const patterns = [
    /\/file\/d\/([a-zA-Z0-9_-]+)/,
    /\/d\/([a-zA-Z0-9_-]+)\//,
    /id=([a-zA-Z0-9_-]+)/
  ]
  
  for (const pattern of patterns) {
    const match = url.match(pattern)
    if (match && match[1]) return match[1]
  }
  
  return url
}

const extractLessonText = (content: string) => {
  if (!content) return ''
  
  let text = content.replace(/https?:\/\/(www\.)?(youtube\.com|youtu\.be)\/[^\s]+/g, '')
  
  const lines = text.split('\n')
  const filteredLines = lines.filter(line => {
    const trimmedLine = line.trim()
    if (!trimmedLine) return true
    
    const service = detectPdfService(trimmedLine)
    const isKnownService = service === 'dropbox' || service === 'google-drive'
    const isDirectPdf = service === 'direct' && trimmedLine.toLowerCase().endsWith('.pdf')
    
    return !(isKnownService || isDirectPdf)
  })
  
  return filteredLines.join('\n').trim()
}

const getYouTubeVideoId = (url: string) => {
  if (!url) return null
  
  const patterns = [
    /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/,
    /youtube\.com\/watch\?v=([^"&?\/\s]{11})/,
    /youtu\.be\/([^"&?\/\s]{11})/,
    /youtube\.com\/embed\/([^"&?\/\s]{11})/
  ]
  
  for (const pattern of patterns) {
    const match = url.match(pattern)
    if (match) return match[1]
  }
  
  return null
}

const formatFileSize = (bytes: number) => {
  if (!bytes) return 'Unknown size'
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

const formatDate = (dateString: Date | string) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}
</script>

<style scoped>
.lessons-accordion {
  border-radius: 8px;
}

.lesson-content {
  padding: 8px 0;
}

.lesson-text {
  line-height: 1.6;
  max-height: 300px;
  overflow-y: auto;
  padding: 12px;
  background-color: #f8f9fa;
  border-radius: 4px;
  border-left: 3px solid #1976d2;
}

.border-t {
  border-top: 1px solid rgba(0, 0, 0, 0.12);
}

.pdf-iframe {
  border: 1px solid #e0e0e0;
  border-radius: 4px;
}

.attachment-item {
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  transition: background-color 0.2s ease;
}

.attachment-item:last-child {
  border-bottom: none;
}

.attachment-item:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

.gap-1 {
  gap: 4px;
}
</style>