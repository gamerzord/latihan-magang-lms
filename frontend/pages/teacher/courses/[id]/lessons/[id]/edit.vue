<template>
  <v-container fluid class="page-container">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" class="card">
          <v-card-title class="d-flex justify-space-between align-center card-header">
            <div class="d-flex align-center">
              <span class="text-h5 font-weight-bold">Edit Lesson</span>
              <v-tooltip location="bottom">
                <template #activator="{ props }">
                  <v-icon v-bind="props" color="grey" class="ml-2" size="small">
                    mdi-information
                  </v-icon>
                </template>
                <span>Update lesson content and materials</span>
              </v-tooltip>
            </div>
          </v-card-title>

          <v-card-text class="pa-6">
            <!-- Loading State -->
            <div v-if="!lesson" class="text-center py-8">
              <v-progress-circular indeterminate color="primary" />
              <p class="mt-4">Loading lesson...</p>
            </div>

            <!-- Form -->
            <template v-else>
              <v-alert v-if="successMessage" type="success" class="mb-4" closable @click:close="successMessage = ''">
                {{ successMessage }}
              </v-alert>
              <v-alert v-if="errorMessage" type="error" class="mb-4" closable @click:close="errorMessage = ''">
                {{ errorMessage }}
              </v-alert>
              
              <v-form v-model="formValid" @submit.prevent="updateLesson">
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="lessonForm.lesson_code"
                      label="Lesson Code"
                      variant="outlined"
                      :rules="[requiredRule]"
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="lessonForm.title"
                      label="Lesson Title"
                      variant="outlined"
                      :rules="[requiredRule]"
                      required
                    />
                  </v-col>

                  <v-col cols="12">
                    <v-textarea
                      v-model="lessonForm.content"
                      label="Lesson Content"
                      placeholder="Enter content, instructions, or URLs..."
                      variant="outlined"
                      rows="6"
                      :rules="[requiredRule]"
                      required
                    />
                  </v-col>

                  <!--  File Attachment Section -->
                  <v-col cols="12">
                    <v-card variant="outlined" class="mb-4">
                      <v-card-title class="text-subtitle-1">
                        <v-icon class="mr-2">mdi-attachment</v-icon>
                        Attachments
                      </v-card-title>
                      <v-card-text>
                        
                        <!-- Existing Files -->
                        <div v-if="existingAttachments.length > 0" class="mb-4">
                          <v-list density="compact" class="existing-files-list">
                            <v-list-subheader>Existing Files</v-list-subheader>
                            <v-list-item
                              v-for="attachment in existingAttachments"
                              :key="attachment.id"
                              class="file-item"
                            >
                              <template #prepend>
                                <v-icon :color="getFileIconColor(attachment.mime_type || '')">
                                  {{ getFileIcon(attachment.mime_type || '') }}
                                </v-icon>
                              </template>
                              <v-list-item-title>
                                <a :href="attachment.file_url" target="_blank" class="text-decoration-none text-primary">
                                  {{ attachment.file_name }}
                                </a>
                              </v-list-item-title>
                              <v-list-item-subtitle>
                                {{ formatFileSize(attachment.file_size) }}
                              </v-list-item-subtitle>
                              <template #append>
                                <v-btn
                                  icon
                                  size="small"
                                  variant="text"
                                  color="error"
                                  @click="deleteAttachment(attachment.id)"
                                  :loading="deletingAttachments.includes(attachment.id)"
                                  :disabled="deletingAttachments.includes(attachment.id)"
                                >
                                  <v-icon>mdi-delete</v-icon>
                                </v-btn>
                              </template>
                            </v-list-item>
                          </v-list>
                        </div>

                        <!-- No Files Message -->
                        <v-alert v-else type="info" density="compact" class="mb-4">
                          No files attached yet. Upload files below to add materials.
                        </v-alert>

                        <!-- New File Upload -->
                        <v-file-input
                          v-model="selectedFiles"
                          label="Upload New Files"
                          placeholder="Select files to upload"
                          variant="outlined"
                          multiple
                          chips
                          show-size
                          accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.zip"
                          prepend-icon="mdi-paperclip"
                          :rules="[fileSizeRule]"
                          hint="Max 10MB per file. Supported: PDF, Word, Excel, PowerPoint, Images, ZIP"
                          persistent-hint
                        >
                          <template #selection="{ fileNames }">
                            <template v-for="(fileName, index) in fileNames" :key="fileName">
                              <v-chip
                                v-if="index < 2"
                                class="me-2"
                                color="primary"
                                size="small"
                                label
                              >
                                {{ fileName }}
                              </v-chip>
                              <span
                                v-else-if="index === 2"
                                class="text-overline text-grey-darken-3 mx-2"
                              >
                                +{{ fileNames.length - 2 }} File(s)
                              </span>
                            </template>
                          </template>
                        </v-file-input>

                        <!-- New File Preview List -->
                        <v-list v-if="selectedFiles.length > 0" density="compact" class="mt-3">
                          <v-list-subheader>New Files to Upload</v-list-subheader>
                          <v-list-item
                            v-for="(file, index) in selectedFiles"
                            :key="index"
                            class="file-preview-item"
                          >
                            <template #prepend>
                              <v-icon :color="getFileIconColor(file.type)">
                                {{ getFileIcon(file.type) }}
                              </v-icon>
                            </template>
                            <v-list-item-title>{{ file.name }}</v-list-item-title>
                            <v-list-item-subtitle>{{ formatFileSize(file.size) }}</v-list-item-subtitle>
                            <template #append>
                              <v-btn
                                icon
                                size="small"
                                variant="text"
                                @click="removeFile(index)"
                              >
                                <v-icon>mdi-close</v-icon>
                              </v-btn>
                            </template>
                          </v-list-item>
                        </v-list>

                        <v-alert
                          v-if="uploadError"
                          type="error"
                          density="compact"
                          class="mt-3"
                          closable
                          @click:close="uploadError = ''"
                        >
                          {{ uploadError }}
                        </v-alert>
                      </v-card-text>
                    </v-card>
                  </v-col>

                  <!--  Preview Section -->
                  <v-col cols="12" v-if="lessonForm.content">
                    <v-card variant="outlined" class="pa-3">
                      <v-card-title class="text-subtitle-1 font-weight-bold">
                        <v-icon class="mr-2">mdi-eye</v-icon>
                        Content Preview
                      </v-card-title>

                      <!-- YouTube Preview -->
                      <div v-if="youtubeVideoId" class="mt-3">
                        <p class="text-caption text-medium-emphasis mb-2">YouTube Video:</p>
                        <iframe
                          width="100%"
                          height="300"
                          :src="`https://www.youtube.com/embed/${youtubeVideoId}`"
                          frameborder="0"
                          allowfullscreen
                          class="rounded"
                        ></iframe>
                      </div>

                      <!-- PDF Preview -->
                      <div v-else-if="isPdfUrl" class="mt-3">
                        <p class="text-caption text-medium-emphasis mb-2">PDF Document:</p>
                        <iframe
                          width="100%"
                          height="500"
                          :src="processedPdfUrl"
                          frameborder="0"
                          class="pdf-iframe rounded"
                        ></iframe>
                      </div>

                      <!-- Text Content Preview -->
                      <div v-else class="mt-3">
                        <p class="text-caption text-medium-emphasis mb-2">Text Content:</p>
                        <div class="text-body-2 pa-3 bg-grey-lighten-4 rounded" style="white-space: pre-wrap;">
                          {{ lessonForm.content }}
                        </div>
                      </div>
                    </v-card>
                  </v-col>
                </v-row>

                <v-card-actions class="mt-6 px-0">
                  <v-btn 
                    color="grey" 
                    variant="outlined" 
                    @click="$router.back()" 
                    :disabled="loading"
                  >
                    <v-icon start>mdi-arrow-left</v-icon>
                    Cancel
                  </v-btn>
                  <v-spacer />
                  <v-btn
                    color="primary"
                    type="submit"
                    :loading="loading"
                    :disabled="!formValid || loading"
                    variant="flat"
                  >
                    <v-icon start>mdi-content-save</v-icon>
                    Save Changes
                  </v-btn>
                </v-card-actions>
              </v-form>
            </template>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import type { Lesson, LessonAttachment } from '~/types/models'

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const id = route.params.id

const successMessage = ref('')
const errorMessage = ref('')
const uploadError = ref('')
const loading = ref(false)
const formValid = ref(false)
const deletingAttachments = ref<number[]>([])
const selectedFiles = ref<File[]>([])

const { data, refresh: refreshLesson } = useFetch<{ lesson: Lesson }>(
  `${config.public.apiBase}/lessons/${id}`,
  {
    key: `lesson-${id}`,
    lazy: false,
  }
)

const lesson = computed(() => data.value?.lesson)

const lessonForm = ref({
  title: '',
  lesson_code: '',
  content: ''
})

watch(lesson, (newLesson) => {
  if (newLesson) {
    lessonForm.value = {
      title: newLesson.title,
      lesson_code: newLesson.lesson_code,
      content: newLesson.content
    }
  }
}, { immediate: true })

const existingAttachments = computed(() => {
  return lesson.value?.attachments || []
})

const removeFile = (index: number) => {
  selectedFiles.value.splice(index, 1)
}

const deleteAttachment = async (attachmentId: number) => {
  if (!confirm('Are you sure you want to delete this file?')) return
  
  deletingAttachments.value.push(attachmentId)
  
  try {
    await $fetch(`${config.public.apiBase}/attachments/${attachmentId}`, {
      method: 'DELETE'
    })
    
    successMessage.value = 'File deleted successfully!'
    await refreshLesson()
    
  } catch (err: any) {
    console.error('Delete failed:', err)
    errorMessage.value = err?.data?.message || 'Failed to delete file'
  } finally {
    deletingAttachments.value = deletingAttachments.value.filter(id => id !== attachmentId)
  }
}

const requiredRule = (v: string) => !!v || 'This field is required'

const fileSizeRule = (files: File[]) => {
  if (!files?.length) return true
  const maxSize = 10 * 1024 * 1024 // 10MB
  const invalid = files.find(f => f.size > maxSize)
  return invalid ? `File "${invalid.name}" exceeds 10MB` : true
}

const getFileIcon = (mime: string) => {
  if (mime.includes('pdf')) return 'mdi-file-pdf-box'
  if (mime.includes('word') || mime.includes('document')) return 'mdi-file-word'
  if (mime.includes('sheet') || mime.includes('excel')) return 'mdi-file-excel'
  if (mime.includes('presentation') || mime.includes('powerpoint')) return 'mdi-file-powerpoint'
  if (mime.includes('image')) return 'mdi-file-image'
  if (mime.includes('zip')) return 'mdi-folder-zip'
  return 'mdi-file-document'
}

const getFileIconColor = (mime: string) => {
  if (mime.includes('pdf')) return 'red'
  if (mime.includes('word')) return 'blue'
  if (mime.includes('excel') || mime.includes('sheet')) return 'green'
  if (mime.includes('powerpoint') || mime.includes('presentation')) return 'orange'
  if (mime.includes('image')) return 'purple'
  return 'grey'
}

const formatFileSize = (bytes: number) => {
  if (!bytes) return '0 B'
  if (bytes < 1024) return bytes + ' B'
  const kb = bytes / 1024
  if (kb < 1024) return kb.toFixed(1) + ' KB'
  return (kb / 1024).toFixed(1) + ' MB'
}

const youtubeVideoId = computed(() => {
  if (!lessonForm.value.content) return null
  const patterns = [
    /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/,
    /youtube\.com\/watch\?v=([^"&?\/\s]{11})/,
    /youtu\.be\/([^"&?\/\s]{11})/,
  ]
  for (const pattern of patterns) {
    const match = lessonForm.value.content.match(pattern)
    if (match) return match[1]
  }
  return null
})

const detectPdfService = (url: string) => {
  if (url.includes('drive.google.com')) return 'google-drive'
  if (url.includes('dropbox.com')) return 'dropbox'
  return 'direct'
}

const extractGoogleDriveFileId = (url: string) => {
  const patterns = [
    /\/file\/d\/([a-zA-Z0-9_-]+)/,
    /\/d\/([a-zA-Z0-9_-]+)\//,
  ]
  for (const pattern of patterns) {
    const match = url.match(pattern)
    if (match && match[1]) return match[1]
  }
  return ''
}

const isPdfUrl = computed(() => {
  if (!lessonForm.value.content) return false
  const content = lessonForm.value.content.trim()
  return content.toLowerCase().endsWith('.pdf') || 
         content.includes('drive.google.com') || 
         content.includes('dropbox.com')
})

const processedPdfUrl = computed(() => {
  if (!lessonForm.value.content) return ''
  const url = lessonForm.value.content.trim()
  const service = detectPdfService(url)
  
  if (service === 'dropbox') {
    return url.replace('www.dropbox.com', 'dl.dropboxusercontent.com').replace('?dl=0', '')
  }
  if (service === 'google-drive') {
    const fileId = extractGoogleDriveFileId(url)
    return `https://drive.google.com/file/d/${fileId}/preview`
  }
  return `https://docs.google.com/gview?url=${encodeURIComponent(url)}&embedded=true`
})

// Update lesson
const updateLesson = async () => {
  if (!formValid.value) return
  
  loading.value = true
  successMessage.value = ''
  errorMessage.value = ''
  uploadError.value = ''

  try {
    const formData = new FormData()
    formData.append('_method', 'PUT')
    formData.append('course_id', lesson.value!.course_id!.toString())
    formData.append('title', lessonForm.value.title)
    formData.append('lesson_code', lessonForm.value.lesson_code)
    formData.append('content', lessonForm.value.content)
    
    selectedFiles.value.forEach((file, i) => {
      formData.append(`attachments[${i}]`, file)
    })

    await $fetch(`${config.public.apiBase}/lessons/${id}`, {
      method: 'POST',
      body: formData,
    })

    successMessage.value = 'Lesson updated successfully!'
    selectedFiles.value = []
    
    await refreshLesson()
    
    window.scrollTo({ top: 0, behavior: 'smooth' })
    setTimeout(() => router.back(), 1500)
  } catch (err: any) {
    console.error('Update failed:', err)
    errorMessage.value = err?.data?.message || 'Failed to update lesson. Please try again.'
  } finally {
    loading.value = false
  }
}

useHead({ 
  title: computed(() => `Edit Lesson - ${lesson.value?.title || 'Loading...'}`)
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

.file-preview-item,
.file-item {
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  transition: background-color 0.2s;
}

.file-preview-item:hover,
.file-item:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

.file-preview-item:last-child,
.file-item:last-child {
  border-bottom: none;
}

.pdf-iframe {
  border: 1px solid #e0e0e0;
}

.existing-files-list {
  background-color: #f8f9fa;
  border-radius: 8px;
}
</style>