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
            <v-alert v-if="successMessage" type="success" class="mb-4">
              {{ successMessage }}
            </v-alert>
            <v-alert v-if="errorMessage" type="error" class="mb-4">
              {{ errorMessage }}
            </v-alert>
            
            <v-form v-if="lesson" v-model="formValid" @submit.prevent="updateLesson">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="lesson.lesson_code"
                    label="Lesson Code"
                    placeholder="e.g., HTML-01, CSS-BASICS"
                    variant="outlined"
                    :rules="[requiredRule]"
                    required
                    hint="Unique identifier for this lesson"
                    persistent-hint
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="lesson.title"
                    label="Lesson Title"
                    placeholder="e.g., Introduction to HTML"
                    variant="outlined"
                    :rules="[requiredRule]"
                    required
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="lesson.content"
                    label="Lesson Content"
                    placeholder="Enter lesson content, instructions, or paste YouTube/PDF URLs..."
                    variant="outlined"
                    rows="6"
                    :rules="[requiredRule]"
                    required
                  />
                </v-col>
                
                <!-- Content Preview -->
                <v-col cols="12" v-if="lesson.content">
                  <v-card variant="outlined" class="pa-3">
                    <v-card-title class="text-subtitle-1 font-weight-bold">
                      Content Preview
                    </v-card-title>
                    
                    <!-- YouTube Preview -->
                    <div v-if="youtubeVideoId" class="mb-4">
                      <p class="text-caption text-medium-emphasis mb-2">YouTube Preview:</p>
                      <iframe
                        width="100%"
                        height="300"
                        :src="`https://www.youtube.com/embed/${youtubeVideoId}`"
                        frameborder="0"
                        allowfullscreen
                      ></iframe>
                    </div>

                    <!-- PDF Preview -->
                    <div v-else-if="isPdfUrl" class="mb-4">
                      <p class="text-caption text-medium-emphasis mb-2">PDF Document:</p>
                      <iframe
                        width="100%"
                        height="500"
                        :src="processedPdfUrl"
                        frameborder="0"
                        class="pdf-iframe"
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

                    <!-- Regular Text Content -->
                    <div v-else>
                      <div class="text-body-2" style="white-space: pre-wrap;">{{ lesson.content }}</div>
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
                Loading lesson data...
              </div>
            </v-alert>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import type { Lesson } from '~/types/models'

const route = useRoute()
const config = useRuntimeConfig()
const id = route.params.id

const successMessage = ref('')
const errorMessage = ref('')
const loading = ref(false)
const formValid = ref(false)

const { data } = await useFetch<{ lesson: Lesson }>(
  `${config.public.apiBase}/lessons/${id}`
)

const lesson = computed(() => data.value?.lesson)

const requiredRule = (v: string) => !!v || 'This field is required'

const youtubeVideoId = computed(() => {
  if (!lesson.value?.content) return null
  
  const text = lesson.value.content
  const patterns = [
    /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/,
    /youtube\.com\/watch\?v=([^"&?\/\s]{11})/,
    /youtu\.be\/([^"&?\/\s]{11})/,
    /youtube\.com\/embed\/([^"&?\/\s]{11})/
  ]
  
  for (const pattern of patterns) {
    const match = text.match(pattern)
    if (match) return match[1]
  }
  
  return null
})

const isPdfUrl = computed(() => {
  if (!lesson.value?.content) return false
  const content = lesson.value.content.toLowerCase()
  return content
})

const processedPdfUrl = computed(() => {
  if (!lesson.value?.content) return ''
  
  const url = lesson.value.content.trim()
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
})

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

const updateLesson = async () => {
  if (!lesson.value || !formValid.value) return

  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await $fetch(`${config.public.apiBase}/lessons/${id}`, {
      method: 'PUT',
      body: lesson.value,
    })

    successMessage.value = 'Lesson updated successfully!'
    setTimeout(() => navigateTo('/teacher/courses'), 1500)
  } catch (err: any) {
    console.error('Update Failed', err)
    errorMessage.value = err?.data?.message || 'Failed to update lesson'
  } finally {
    loading.value = false
  }
}

useHead({
  title: `Edit Lesson - ${lesson.value?.title || 'Loading...'}`
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

.pdf-iframe {
  border: 1px solid #e0e0e0;
  border-radius: 4px;
}
</style>