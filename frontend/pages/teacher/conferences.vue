<template>
  <v-container class="conferences-page">
    <!-- Header Section -->
    <v-row class="mb-6">
      <v-col cols="12" class="d-flex justify-space-between align-center">
        <div>
          <h1 class="text-h4 font-weight-bold mb-1">Video Conferences</h1>
          <p class="text-body-2 text-medium-emphasis">Manage your live classes and meetings</p>
        </div>
        <v-btn 
          color="primary" 
          size="large"
          elevation="2"
          @click="showCreateDialog = true"
        >
          <v-icon left>mdi-plus-circle</v-icon>
          New Conference
        </v-btn>
      </v-col>
    </v-row>

    <!-- Stats Cards -->
    <v-row class="mb-6">
      <v-col cols="12" sm="4">
        <v-card class="stat-card" color="blue-grey-lighten-5">
          <v-card-text>
            <div class="d-flex align-center">
              <v-avatar color="blue" size="48" class="mr-3">
                <v-icon color="white">mdi-calendar-clock</v-icon>
              </v-avatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ scheduledCount }}</div>
                <div class="text-caption text-medium-emphasis">Scheduled</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="4">
        <v-card class="stat-card" color="green-lighten-5">
          <v-card-text>
            <div class="d-flex align-center">
              <v-avatar color="success" size="48" class="mr-3">
                <v-icon color="white" class="pulse-icon">mdi-video</v-icon>
              </v-avatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ activeCount }}</div>
                <div class="text-caption text-medium-emphasis">Active Now</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="4">
        <v-card class="stat-card" color="grey-lighten-4">
          <v-card-text>
            <div class="d-flex align-center">
              <v-avatar color="grey-darken-1" size="48" class="mr-3">
                <v-icon color="white">mdi-check-circle</v-icon>
              </v-avatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ endedCount }}</div>
                <div class="text-caption text-medium-emphasis">Completed</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Conferences List -->
    <v-row v-if="conferences.length > 0">
      <v-col 
        v-for="conference in conferences" 
        :key="conference.id"
        cols="12"
      >
        <v-card 
          class="conference-card"
          :class="{ 'active-conference': conference.status === 'active' }"
          elevation="2"
        >
          <v-card-text class="pa-5">
            <v-row align="center">
              <!-- Conference Icon/Status -->
              <v-col cols="auto">
                <v-avatar 
                  :color="getStatusAvatarColor(conference.status)" 
                  size="64"
                  class="conference-avatar"
                >
                  <v-icon 
                    size="32" 
                    color="white"
                    :class="{ 'pulse-icon': conference.status === 'active' }"
                  >
                    {{ getStatusIcon(conference.status) }}
                  </v-icon>
                </v-avatar>
              </v-col>

              <!-- Conference Details -->
              <v-col>
                <div class="d-flex align-center mb-2">
                  <h3 class="text-h6 font-weight-bold mr-3">{{ conference.title }}</h3>
                  <v-chip 
                    :color="getStatusColor(conference.status)" 
                    size="small"
                    :class="{ 'pulse-chip': conference.status === 'active' }"
                  >
                    <v-icon 
                      v-if="conference.status === 'active'" 
                      size="small" 
                      start
                      class="pulse-icon"
                    >
                      mdi-circle
                    </v-icon>
                    {{ conference.status.toUpperCase() }}
                  </v-chip>
                </div>
                
                <div class="text-body-2 text-medium-emphasis mb-2">
                  <v-icon size="small" class="mr-1">mdi-book-open-variant</v-icon>
                  {{ conference.course?.title || 'No course' }}
                </div>

                <div v-if="conference.started_at" class="text-caption text-medium-emphasis">
                  <v-icon size="small" class="mr-1">mdi-clock-outline</v-icon>
                  Started: {{ formatDate(conference.started_at) }}
                </div>
              </v-col>

              <!-- Actions -->
              <v-col cols="auto">
                <div class="d-flex flex-column ga-2">
                  <!-- Scheduled: Start Button -->
                  <v-btn
                    v-if="conference.status === 'scheduled'"
                    color="success"
                    size="large"
                    variant="elevated"
                    @click="startConference(conference)"
                    class="action-btn"
                  >
                    <v-icon left>mdi-play-circle</v-icon>
                    Start Conference
                  </v-btn>

                  <!-- Active: Join & End Buttons -->
                  <template v-if="conference.status === 'active'">
                    <v-btn
                      color="primary"
                      size="large"
                      variant="elevated"
                      @click="joinConference(conference)"
                      class="action-btn pulse-btn"
                    >
                      <v-icon left>mdi-video</v-icon>
                      Join Now
                    </v-btn>
                    <v-btn
                      color="error"
                      size="small"
                      variant="outlined"
                      @click="endConference(conference)"
                    >
                      <v-icon left size="small">mdi-stop-circle</v-icon>
                      End Conference
                    </v-btn>
                  </template>

                  <!-- Ended: Delete Button -->
                  <v-btn
                    v-if="conference.status === 'ended'"
                    color="error"
                    size="small"
                    variant="text"
                    @click="deleteConference(conference)"
                  >
                    <v-icon left size="small">mdi-delete</v-icon>
                    Delete
                  </v-btn>
                </div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Empty State -->
    <v-row v-else>
      <v-col cols="12">
        <v-card class="empty-state pa-12 text-center" elevation="0" color="grey-lighten-5">
          <v-icon size="80" color="grey-lighten-1" class="mb-4">mdi-video-off</v-icon>
          <h3 class="text-h5 mb-2">No Conferences Yet</h3>
          <p class="text-body-1 text-medium-emphasis mb-6">
            Create your first video conference to start teaching live
          </p>
          <v-btn 
            color="primary" 
            size="large"
            @click="showCreateDialog = true"
          >
            <v-icon left>mdi-plus-circle</v-icon>
            Create Your First Conference
          </v-btn>
        </v-card>
      </v-col>
    </v-row>

    <!-- Create Conference Dialog -->
    <v-dialog v-model="showCreateDialog" max-width="600px">
      <v-card>
        <v-card-title class="pa-6 bg-primary">
          <span class="text-h5 text-white">Create New Conference</span>
        </v-card-title>
        
        <v-card-text class="pa-6">
          <v-form ref="form" v-model="formValid">
            <v-text-field
              v-model="conferenceForm.title"
              label="Conference Title"
              placeholder="e.g., Week 1 Lecture, Office Hours"
              :rules="[v => !!v || 'Title is required']"
              variant="outlined"
              prepend-inner-icon="mdi-text"
              class="mb-4"
              required
            ></v-text-field>

            <v-select
              v-model="conferenceForm.course_id"
              :items="teacherCourses"
              item-title="title"
              item-value="id"
              label="Select Course"
              placeholder="Choose a course"
              :rules="[v => !!v || 'Course is required']"
              variant="outlined"
              prepend-inner-icon="mdi-book-open-variant"
              required
            >
              <template #item="{ props, item }">
                <v-list-item v-bind="props">
                  <template #prepend>
                    <v-avatar color="primary" size="40">
                      <v-icon color="white">mdi-book</v-icon>
                    </v-avatar>
                  </template>
                </v-list-item>
              </template>
            </v-select>

            <v-alert type="info" variant="tonal" class="mt-4">
              <div class="text-caption">
                <v-icon size="small" class="mr-1">mdi-information</v-icon>
                The conference will be created in "scheduled" status. Click "Start" to begin the live session.
              </div>
            </v-alert>
          </v-form>
        </v-card-text>
        
        <v-card-actions class="pa-6 pt-0">
          <v-spacer></v-spacer>
          <v-btn 
            variant="text" 
            @click="showCreateDialog = false"
            size="large"
          >
            Cancel
          </v-btn>
          <v-btn 
            color="primary" 
            :disabled="!formValid" 
            @click="createConference"
            size="large"
            variant="elevated"
          >
            <v-icon left>mdi-check-circle</v-icon>
            Create Conference
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar for notifications -->
    <v-snackbar 
      v-model="snackbar.show" 
      :color="snackbar.color" 
      :timeout="3000"
      location="top"
    >
      {{ snackbar.text }}
      <template #actions>
        <v-btn icon size="small" @click="snackbar.show = false">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup lang="ts">
import type { Conference, Course } from '~/types/models'

const config = useRuntimeConfig()
const conferences = ref<Conference[]>([])
const teacherCourses = ref<Course[]>([])
const showCreateDialog = ref(false)
const formValid = ref(false)

const conferenceForm = ref({
  title: '',
  course_id: null as number | null
})

const snackbar = ref({
  show: false,
  text: '',
  color: 'success'
})

// Computed stats
const scheduledCount = computed(() => 
  conferences.value.filter(c => c.status === 'scheduled').length
)
const activeCount = computed(() => 
  conferences.value.filter(c => c.status === 'active').length
)
const endedCount = computed(() => 
  conferences.value.filter(c => c.status === 'ended').length
)

const getStatusColor = (status: string) => {
  switch (status) {
    case 'scheduled': return 'blue'
    case 'active': return 'success'
    case 'ended': return 'grey'
    default: return 'grey'
  }
}

const getStatusAvatarColor = (status: string) => {
  switch (status) {
    case 'scheduled': return 'blue'
    case 'active': return 'success'
    case 'ended': return 'grey-darken-1'
    default: return 'grey'
  }
}

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'scheduled': return 'mdi-calendar-clock'
    case 'active': return 'mdi-video'
    case 'ended': return 'mdi-check-circle'
    default: return 'mdi-help-circle'
  }
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  })
}

const { data: conferencesData, refresh: refreshConferences } = useFetch<{ conferences: Conference[] }>(
  `${config.public.apiBase}/conferences`,
  {
    key: 'teacher-conferences',
    lazy: false,
  }
)

const { data: coursesData } = useFetch<{ courses: Course[] }>(
  `${config.public.apiBase}/teacher/courses`,
  {
    key: 'teacher-courses-conferences',
    lazy: false,
  }
)

watch(conferencesData, (newData) => {
  if (newData) {
    conferences.value = newData.conferences || []
  }
}, { immediate: true })

watch(coursesData, (newData) => {
  if (newData) {
    teacherCourses.value = newData.courses || []
  }
}, { immediate: true })

const createConference = async () => {
  try {
    await $fetch(`${config.public.apiBase}/conferences`, {
      method: 'POST',
      body: conferenceForm.value
    })
    
    showSnackbar('Conference created successfully', 'success')
    showCreateDialog.value = false
    conferenceForm.value = { title: '', course_id: null }
    refreshConferences()
  } catch (error) {
    console.error('Error creating conference:', error)
    showSnackbar('Failed to create conference', 'error')
  }
}

const startConference = async (conference: Conference) => {
  try {
    await $fetch(`${config.public.apiBase}/conferences/${conference.id}/start`, {
      method: 'POST',
    })
    
    showSnackbar('Conference started', 'success')
    refreshConferences()
    
    setTimeout(() => {
      navigateTo(`/teacher/conference/${conference.id}/room`)
    }, 500)
  } catch (error) {
    console.error('Error starting conference:', error)
    showSnackbar('Failed to start conference', 'error')
  }
}

const joinConference = (conference: Conference) => {
  navigateTo(`/teacher/conference/${conference.id}/room`)
}

const endConference = async (conference: Conference) => {
  if (!confirm('Are you sure you want to end this conference?')) return
  
  try {
    await $fetch(`${config.public.apiBase}/conferences/${conference.id}/end`, {
      method: 'POST',
    })
    
    showSnackbar('Conference ended', 'success')
    refreshConferences()
  } catch (error) {
    console.error('Error ending conference:', error)
    showSnackbar('Failed to end conference', 'error')
  }
}

const deleteConference = async (conference: Conference) => {
  if (!confirm('Are you sure you want to delete this conference?')) return
  
  try {
    await $fetch(`${config.public.apiBase}/conferences/${conference.id}`, {
      method: 'DELETE',
    })
    
    showSnackbar('Conference deleted', 'success')
    refreshConferences()
  } catch (error) {
    console.error('Error deleting conference:', error)
    showSnackbar('Failed to delete conference', 'error')
  }
}

const showSnackbar = (text: string, color: string) => {
  snackbar.value = { show: true, text, color }
}

useAutoRefresh(async () => {
  await refreshConferences()
})
</script>

<style scoped>
.conferences-page {
  max-width: 1200px;
}

.stat-card {
  transition: transform 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-4px);
}

.conference-card {
  transition: all 0.3s ease;
  border-left: 4px solid transparent;
}

.conference-card:hover {
  transform: translateX(4px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12) !important;
}

.active-conference {
  border-left-color: #4caf50 !important;
  background: linear-gradient(to right, rgba(76, 175, 80, 0.05), transparent);
}

.conference-avatar {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.action-btn {
  min-width: 160px;
  font-weight: 600;
}

.pulse-btn {
  animation: button-pulse 2s ease-in-out infinite;
}

.pulse-chip {
  animation: chip-pulse 2s ease-in-out infinite;
}

.pulse-icon {
  animation: icon-pulse 1.5s ease-in-out infinite;
}

@keyframes button-pulse {
  0%, 100% {
    box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
  }
  50% {
    box-shadow: 0 6px 20px rgba(25, 118, 210, 0.5);
  }
}

@keyframes chip-pulse {
  0%, 100% {
    box-shadow: 0 0 10px rgba(76, 175, 80, 0.4);
  }
  50% {
    box-shadow: 0 0 20px rgba(76, 175, 80, 0.7);
  }
}

@keyframes icon-pulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.6;
    transform: scale(1.1);
  }
}

.empty-state {
  border: 2px dashed #ccc;
}

.ga-2 {
  gap: 8px;
}
</style>