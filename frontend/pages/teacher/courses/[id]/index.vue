<template>
  <div class="teacher-course-details">
    <v-container>
      <v-row>
        <v-col cols="12">
          <!-- Course Header -->
          <v-card elevation="2" class="pa-4 mb-6">
            <v-card-title class="d-flex justify-space-between align-center">
              <div>
                <h2 class="text-h5">{{ course?.title }}</h2>
                <div class="text-subtitle-2 text-medium-emphasis">
                  Course Code: {{ course?.course_code }} • {{ course?.students_count || 0 }} students
                </div>
              </div>
              <div class="d-flex gap-2">
                <v-btn color="primary" @click="showCreateLessonDialog = true">
                  <v-icon start>mdi-plus</v-icon>
                  Add Lesson
                </v-btn>
                <v-btn color="secondary" @click="showAddAssignmentDialog = true">
                  <v-icon start>mdi-plus</v-icon>
                  Add Assignment
                </v-btn>
                <v-btn variant="outlined" :to="`/teacher/courses/${course?.id}/edit`">
                  <v-icon start>mdi-pencil</v-icon>
                  Edit Course
                </v-btn>
              </div>
            </v-card-title>

            <v-card-text>
              <p class="text-body-1 mb-4">{{ course?.description }}</p>
              
              <div class="d-flex gap-4 text-caption text-medium-emphasis">
                <div class="d-flex align-center">
                  <v-icon small class="mr-1">mdi-book-open</v-icon>
                  <span>{{ course?.lessons_count || 0 }} lessons</span>
                </div>
                <div class="d-flex align-center">
                  <v-icon small class="mr-1">mdi-account-group</v-icon>
                  <span>{{ course?.students_count || 0 }} students enrolled</span>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12">
          <!-- Lessons Section -->
          <v-card elevation="2" class="pa-4">
            <template v-if="pending">
              <div class="text-center py-8">
                <v-progress-circular indeterminate color="primary" />
                <p class="mt-4 text-body-1">Loading course content...</p>
              </div>
            </template>

            <template v-else-if="course">
              <div class="d-flex justify-space-between align-center mb-4">
                <h3 class="text-h6">Course Lessons</h3>
                <span class="text-body-2 text-medium-emphasis">
                  {{ course.lessons?.length || 0 }} lessons
                </span>
              </div>

              <div v-if="course.lessons?.length">
                <v-list class="bg-transparent">
                  <v-list-item
                    v-for="(lesson, index) in course.lessons"
                    :key="lesson.id"
                    class="lesson-item"
                  >
                    <template #prepend>
                      <div class="lesson-number mr-4">
                        <v-avatar color="primary" size="32">
                          <span class="text-caption text-white">
                            {{ index + 1 }}
                          </span>
                        </v-avatar>
                      </div>
                    </template>

                    <v-list-item-title class="text-body-1 font-weight-medium">
                      {{ lesson.title }}
                    </v-list-item-title>
                    
                    <v-list-item-subtitle class="text-caption">
                      {{ lesson.lesson_code }} • {{ formatDate(lesson.created_at) }}
                    </v-list-item-subtitle>

                    <template #append>
                      <div class="d-flex gap-1">
                        <v-btn
                          icon
                          variant="text"
                          size="small"
                          color="primary"
                          @click="editLesson(lesson)"
                        >
                          <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn
                          icon
                          variant="text"
                          size="small"
                          color="error"
                          @click="deleteLesson(lesson)"
                        >
                          <v-icon>mdi-delete</v-icon>
                        </v-btn>
                      </div>
                    </template>
                  </v-list-item>
                </v-list>
              </div>

              <div v-else class="text-center py-8 text-medium-emphasis">
                <v-icon size="48" class="mb-2">mdi-book-off-outline</v-icon>
                <p>No lessons created yet.</p>
                <p class="text-caption">Start by adding your first lesson to this course.</p>
                <v-btn color="primary" class="mt-4" @click="showCreateLessonDialog = true">
                  <v-icon start>mdi-plus</v-icon>
                  Create First Lesson
                </v-btn>
              </div>
            </template>

            <template v-else>
              <div class="text-center py-8 text-error">
                <v-icon size="48" color="error" class="mb-2">mdi-alert-circle-outline</v-icon>
                <p>Course not found.</p>
                <v-btn color="primary" class="mt-4" to="/teacher/courses">
                  Back to Courses
                </v-btn>
              </div>
            </template>
          </v-card>
          <!-- Assignments Section -->
           <v-row class="mt-6">
            <v-col cols="12">
              <TeacherAssignmentsList
              :assignments="course?.assignments || []"
              :pending="pending"
              @add="showAddAssignmentDialog = true"
              @edit="editAssignment"
              @delete="deleteAssignment"
              />
            </v-col>
           </v-row>
        </v-col>
      </v-row>
    </v-container>

    <!-- Create Lesson Dialog -->
    <v-dialog v-model="showCreateLessonDialog" max-width="600">
      <v-card>
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6">Create New Lesson</span>
          <v-btn icon @click="showCreateLessonDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-card-text>
          <v-form v-model="lessonFormValid" @submit.prevent="createLesson">
            <v-text-field
              v-model="lessonForm.title"
              label="Lesson Title"
              placeholder="e.g., Introduction to HTML"
              variant="outlined"
              :rules="[requiredRule]"
              class="mb-4"
            />

            <v-text-field
              v-model="lessonForm.lesson_code"
              label="Lesson Code"
              placeholder="e.g., HTML-01, CSS-BASICS"
              variant="outlined"
              :rules="[requiredRule]"
              class="mb-4"
              hint="Unique identifier for this lesson"
              persistent-hint
            />

            <v-textarea
              v-model="lessonForm.content"
              label="Lesson Content"
              placeholder="Enter lesson content or paste YouTube URLs..."
              variant="outlined"
              rows="4"
              class="mb-2"
            />

            <!-- YouTube Preview -->
            <div v-if="youtubeVideoId" class="mb-4">
              <p class="text-caption text-medium-emphasis mb-2">YouTube Preview:</p>
              <iframe
                width="100%"
                height="200"
                :src="`https://www.youtube.com/embed/${youtubeVideoId}`"
                frameborder="0"
                allowfullscreen
              ></iframe>
            </div>

                <!-- PDF Preview -->
            <div v-else-if="isPdfUrl">
              <p class="text-caption text-medium-emphasis mb-2">PDF Document:</p>

                  <!-- Service-specific instructions -->
              <v-alert v-if="detectedService" :type="serviceInstructions ? 'info' : 'success'" density="compact" class="mb-2">
                <div v-if="serviceInstructions" class="text-caption">
                  {{ serviceInstructions }}
                </div>
                <div v-else class="text-caption">
                  ✓ Direct PDF link detected
                </div>
              </v-alert>

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
                  :href="processedPdfUrl"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <v-icon start>mdi-download</v-icon>
                  Download PDF
                </v-btn>
              </div>
            </div>

            <!-- Content Preview -->
            <div v-if="lessonForm.content && !youtubeVideoId && !isPdfUrl" class="mb-4">
              <p class="text-caption text-medium-emphasis mb-2">Content Preview:</p>
              <v-card variant="outlined" class="pa-3">
                <div class="text-body-2" style="white-space: pre-wrap;">{{ lessonForm.content }}</div>
              </v-card>
            </div>

            <div class="d-flex gap-2 justify-end">
              <v-btn variant="outlined" @click="showCreateLessonDialog = false">
                Cancel
              </v-btn>
              <v-btn
                type="submit"
                color="primary"
                variant="flat"
                :loading="creatingLesson"
                :disabled="!lessonFormValid || creatingLesson"
              >
                <v-icon start>mdi-plus</v-icon>
                Create Lesson
              </v-btn>
            </div>
          </v-form>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="showDeleteDialog" max-width="400">
      <v-card>
        <v-card-title class="text-h6">Delete Lesson</v-card-title>
        <v-card-text>
          Are you sure you want to delete "{{ lessonToDelete?.title }}"?
          This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="outlined" @click="showDeleteDialog = false">
            Cancel
          </v-btn>
          <v-btn color="error" variant="flat" @click="confirmDelete" :loading="deletingLesson">
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <AddAssignment
      v-model="showAddAssignmentDialog"
      :course-id="parseInt(id as string)"
      @created="onAssignmentCreated"
    />

    <v-alert v-if="successMessage" type="success" class="mb-4">
        {{ successMessage }}
    </v-alert>
  </div>
</template>

<script setup lang="ts">
import AddAssignment from '~/components/teacher/AddAssignment.vue'
import TeacherAssignmentsList from '~/components/teacher/TeacherAssignmentsList.vue'
import type { Course, Lesson } from '~/types/models'
const showAddAssignmentDialog = ref(false)


const route = useRoute()
const id = route.params.id
const successMessage = ref('')
const config = useRuntimeConfig()

const { data, pending, refresh } = await useFetch<{ course: Course }>(
  `${config.public.apiBase}/teacher/courses/${id}`,
)

const course = computed(() => data.value?.course)

const showCreateLessonDialog = ref(false)
const showDeleteDialog = ref(false)
const creatingLesson = ref(false)
const deletingLesson = ref(false)
const lessonFormValid = ref(false)
const lessonToDelete = ref<Lesson | null>(null)

const isPdfUrl = computed(() => {
  if (!lessonForm.value.content) return false
  
  const content = lessonForm.value.content.trim()
  const service = detectPdfService(content)
  const isKnownService = service === 'dropbox' || service === 'google-drive'
  const isDirectPdf = service === 'direct' && content.toLowerCase().endsWith('.pdf')
  
  return isKnownService || isDirectPdf
})

const processedPdfUrl = computed(() => {
  if (!lessonForm.value.content) return ''
  
  const url = lessonForm.value.content.trim()
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


const detectedService = computed(() => {
  if (!lessonForm.value.content) return null
  return detectPdfService(lessonForm.value.content.trim())
})

const serviceInstructions = computed(() => {
  const service = detectedService.value
  const url = lessonForm.value.content.trim()
  
  switch (service) {
    case 'dropbox':
      return 'Dropbox link detected. For better results, use the direct download link.'
    
    case 'google-drive':
      return 'Google Drive link detected. Make sure sharing is set to "Anyone with the link".'
    
    default:
      return null
  }
})

const detectPdfService = (url: string): 'dropbox' | 'google-drive' | 'direct' => {
  if (url.includes('dropbox.com')) return 'dropbox'
  if (url.includes('drive.google.com')) return 'google-drive'
  return 'direct'
}

const lessonForm = ref({
  title: '',
  lesson_code: '',
  content: '',
  course_id: parseInt(route.params.id as string)
})

const requiredRule = (value: string) => {
  return !!value || 'This field is required'
}

const youtubeVideoId = computed(() => {
  if (!lessonForm.value.content || isPdfUrl.value) return null
  
  const text = lessonForm.value.content
  
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

const formatDate = (dateString: Date | string) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const createLesson = async () => {
  successMessage.value = ''  
  if (!lessonFormValid.value) return

  creatingLesson.value = true
  try {
    await $fetch(`${config.public.apiBase}/lessons`, {
      method: 'POST',
      body: lessonForm.value
    })
    lessonForm.value = {
      title: '',
      lesson_code: '',
      content: '',
      course_id: parseInt(route.params.id as string)
    }
    showCreateLessonDialog.value = false
    
    refresh()
    successMessage.value = 'Lesson created successfully!'
  } catch (err: any) {
    console.error('Failed to create lesson:', err)
    alert(err.data?.message || 'Failed to create lesson. Please try again.')
  } finally {
    creatingLesson.value = false
  }
}

const editLesson = (lesson: Lesson) => {
  navigateTo(`/teacher/courses/${id}/lessons/${lesson.id}/edit`)
}

const deleteLesson = (lesson: Lesson) => {
  lessonToDelete.value = lesson
  showDeleteDialog.value = true
}

const onAssignmentCreated = () => {
  successMessage.value = 'Assignment created successfully!'
  refresh()
}

const confirmDelete = async () => {
  if (!lessonToDelete.value) return

  deletingLesson.value = true
  try {
    await $fetch(`${config.public.apiBase}/lessons/${lessonToDelete.value.id}`, {
      method: 'DELETE',
    })

    refresh()
    showDeleteDialog.value = false
    lessonToDelete.value = null
    
    alert('Lesson deleted successfully!')
  } catch (err: any) {
    console.error('Failed to delete lesson:', err)
    alert(err.data?.message || 'Failed to delete lesson. Please try again.')
  } finally {
    deletingLesson.value = false
  }
}

const editAssignment = (assignment: any) => {
  navigateTo(`/teacher/assignments/${assignment.id}/edit`)
}

const deleteAssignment = async (assignment: any) => {
  if (!confirm(`Are you sure you want to delete "${assignment.title}"?`)) return
  try {
    await $fetch(`${config.public.apiBase}/assignments/${assignment.id}`, {
      method: 'DELETE',
    })
    await refresh()
    alert('Assignment deleted successfully!')
  } catch (err: any) {
    console.error('Failed to delete assignment:', err)
    alert(err.data?.message || 'Failed to delete assignment.')
  }
}

</script>

<style scoped>
.lesson-item {
  border-radius: 8px;
  margin-bottom: 4px;
  transition: all 0.2s ease;
}

.lesson-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.lesson-number {
  min-width: 40px;
  display: flex;
  justify-content: center;
}

.gap-1 {
  gap: 4px;
}

.gap-2 {
  gap: 8px;
}

.gap-4 {
  gap: 16px;
}
</style>