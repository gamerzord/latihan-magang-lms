<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>Video Conferences</span>
            <v-btn color="primary" @click="showCreateDialog = true">
              <v-icon left>mdi-plus</v-icon>
              Create Conference
            </v-btn>
          </v-card-title>

          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="conferences"
              :loading="loading"
              class="elevation-1"
            >
              <template #item.course="{ item }">
                {{ item.course?.title }}
              </template>

              <template #item.status="{ item }">
                <v-chip :color="getStatusColor(item.status)" small>
                  {{ item.status }}
                </v-chip>
              </template>

              <template #item.started_at="{ item }">
                {{ item.started_at ? formatDate(item.started_at) : '-' }}
              </template>

              <template #item.actions="{ item }">
                <v-btn
                  v-if="item.status === 'scheduled'"
                  color="success"
                  small
                  @click="startConference(item)"
                  class="mr-2"
                >
                  <v-icon small left>mdi-play</v-icon>
                  Start
                </v-btn>
                
                <v-btn
                  v-if="item.status === 'active'"
                  color="primary"
                  small
                  @click="joinConference(item)"
                  class="mr-2"
                >
                  <v-icon small left>mdi-video</v-icon>
                  Join
                </v-btn>

                <v-btn
                  v-if="item.status === 'active'"
                  color="error"
                  small
                  @click="endConference(item)"
                  class="mr-2"
                >
                  <v-icon small left>mdi-stop</v-icon>
                  End
                </v-btn>

                <v-btn
                  v-if="item.status === 'ended'"
                  color="error"
                  small
                  icon
                  @click="deleteConference(item)"
                >
                  <v-icon small>mdi-delete</v-icon>
                </v-btn>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Create Conference Dialog -->
    <v-dialog v-model="showCreateDialog" max-width="500px">
      <v-card>
        <v-card-title>Create New Conference</v-card-title>
        <v-card-text>
          <v-form ref="form" v-model="formValid">
            <v-text-field
              v-model="conferenceForm.title"
              label="Conference Title"
              :rules="[v => !!v || 'Title is required']"
              required
            ></v-text-field>

            <v-select
              v-model="conferenceForm.course_id"
              :items="teacherCourses"
              item-title="title"
              item-value="id"
              label="Select Course"
              :rules="[v => !!v || 'Course is required']"
              required
            ></v-select>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="showCreateDialog = false">Cancel</v-btn>
          <v-btn color="primary" :disabled="!formValid" @click="createConference">
            Create
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar for notifications -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000">
      {{ snackbar.text }}
    </v-snackbar>
  </v-container>
</template>

<script setup lang="ts">
import type { Conference, Course } from '~/types/models'

const config = useRuntimeConfig()
const loading = ref(false)
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

const headers = [
  { title: 'Title', key: 'title', align: 'start' },
  { title: 'Course', key: 'course', align: 'start' },
  { title: 'Status', key: 'status', align: 'center' },
  { title: 'Started At', key: 'started_at', align: 'start' },
  { title: 'Actions', key: 'actions', align: 'center', sortable: false }
] as const

const getStatusColor = (status: string) => {
  switch (status) {
    case 'scheduled': return 'grey'
    case 'active': return 'success'
    case 'ended': return 'error'
    default: return 'grey'
  }
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleString()
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