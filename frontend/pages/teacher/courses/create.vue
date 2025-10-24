<template>
  <v-container class="py-6">
    <!-- Header -->
    <v-row>
      <v-col cols="12">
        <div class="d-flex align-center mb-2">
          <v-btn
            icon
            variant="text"
            class="mr-2"
            @click="$router.back()"
          >
            <v-icon>mdi-arrow-left</v-icon>
          </v-btn>
          <h1 class="text-h4 font-weight-bold">Create New Course</h1>
        </div>
        <p class="text-body-1 text-medium-emphasis">
          Fill in the details below to create a new course for your students.
        </p>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12" md="8" lg="6">
        <v-card elevation="2" class="pa-6">
          <v-form v-model="formValid" @submit.prevent="createCourse">
            <!-- Course Title -->
            <v-text-field
              v-model="form.title"
              label="Course Title"
              placeholder="e.g., Introduction to Web Development"
              variant="outlined"
              :rules="[requiredRule]"
              class="mb-4"
            />

            <!-- Course Code -->
            <v-text-field
              v-model="form.course_code"
              label="Course Code"
              placeholder="e.g., CS101, MATH202"
              variant="outlined"
              :rules="[requiredRule]"
              class="mb-4"
              hint="Unique identifier for your course"
              persistent-hint
            />

            <!-- Course Description -->
            <v-textarea
              v-model="form.description"
              label="Course Description"
              placeholder="Describe what students will learn in this course..."
              variant="outlined"
              rows="4"
              class="mb-4"
            />

            <!-- Action Buttons -->
            <div class="d-flex gap-2">
              <v-btn
                type="submit"
                color="primary"
                variant="flat"
                :loading="loading"
                :disabled="!formValid || loading"
                size="large"
              >
                <v-icon start>mdi-plus</v-icon>
                Create Course
              </v-btn>

              <v-btn
                variant="outlined"
                @click="$router.back()"
                :disabled="loading"
                size="large"
              >
                Cancel
              </v-btn>
            </div>
          </v-form>
        </v-card>
      </v-col>

      <!-- Preview Section -->
      <v-col cols="12" md="4" lg="6">
        <v-card elevation="2" class="pa-6">
          <h3 class="text-h6 mb-4">Course Preview</h3>
          
          <v-card class="pa-4" elevation="1">
            <v-img
              src="/placeholder-course.jpg"
              height="160"
              class="rounded-lg mb-3"
              cover
            />
            
            <h4 class="text-h6 mb-2">
              {{ form.title || 'Course Title' }}
            </h4>
            
            <p class="text-body-2 text-medium-emphasis mb-2">
              {{ form.course_code || 'COURSE-CODE' }}
            </p>
            
            <p class="text-body-2">
              {{ form.description || 'Course description will appear here...' }}
            </p>
            
            <div class="mt-3">
              <v-chip size="small" variant="outlined" class="mr-2">
                {{ auth.user.value?.name || 'Teacher' }}
              </v-chip>
              <v-chip size="small" variant="outlined" color="primary">
                0 students
              </v-chip>
            </div>
          </v-card>
        </v-card>
      </v-col>
    </v-row>

    <!-- Success Dialog -->
    <v-dialog v-model="successDialog" max-width="400">
      <v-card>
        <v-card-title class="text-h6 d-flex align-center">
          <v-icon color="success" class="mr-2">mdi-check-circle</v-icon>
          Course Created!
        </v-card-title>
        <v-card-text>
          Your course "{{ createdCourseTitle }}" has been created successfully.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn color="primary" @click="goToCourse">
            View Course
          </v-btn>
          <v-btn variant="text" @click="createAnother">
            Create Another
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
import { useAuth } from '~/composables/useAuth'

const auth = useAuth()
const config = useRuntimeConfig()
const router = useRouter()

const formValid = ref(false)
const loading = ref(false)
const successDialog = ref(false)
const createdCourseId = ref<number | null>(null)
const createdCourseTitle = ref('')

const form = ref({
  title: '',
  course_code: '',
  description: '',
  teacher_id: auth.user.value?.id
})

const requiredRule = (value: string) => {
  return !!value || 'This field is required'
}

const createCourse = async () => {
  if (!formValid.value) return

  loading.value = true
  try {
    if (!auth.user.value?.id) {
      throw new Error('User not authenticated')
    }
    await $fetch(`${config.public.apiBase}/courses`, {
      method: 'POST',
      body: {
        title: form.value.title,
        course_code: form.value.course_code,
        description: form.value.description,
        teacher_id: auth.user.value.id
      }
    })
    successDialog.value = true

  } catch (err: any) {
    console.error('Failed to create course:', err)
        if (err.data?.message?.includes('course_code') && err.data?.message?.includes('unique')) {
      alert('Course code already exists. Please use a different course code.')
    } else {
      alert(err.data?.message || 'Failed to create course. Please try again.')
    }
  } finally {
    loading.value = false
  }
}

const goToCourse = () => {
  if (createdCourseId.value) {
    router.push(`/teacher/courses/${createdCourseId.value}`)
  }
  successDialog.value = false
}

const createAnother = () => {
  form.value = {
    title: '',
    course_code: '',
    description: '',
    teacher_id: auth.user.value?.id
  }
  successDialog.value = false
  createdCourseId.value = null
  createdCourseTitle.value = ''
}

onMounted(async () => {
  if (!auth.authenticated.value) {
    await auth.fetchUser()
  }
  
  if (auth.user.value?.role !== 'teacher') {
    router.push('/')
    return
  }

  if (auth.user.value?.id) {
    form.value.teacher_id = auth.user.value.id
  }
})
</script>

<style scoped>
.gap-2 {
  gap: 8px;
}
</style>