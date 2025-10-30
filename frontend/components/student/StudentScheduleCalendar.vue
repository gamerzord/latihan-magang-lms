<template>
  <v-card elevation="2" class="calendar-card">
    <v-card-title class="d-flex justify-space-between align-center">
      <span class="text-h6">My Schedule</span>
      <v-btn 
        color="primary" 
        size="small"
        @click="openAddDialog"
      >
        <v-icon start>mdi-plus</v-icon>
        Add Event
      </v-btn>
    </v-card-title>

    <v-divider />

    <v-card-text>
      <div>
        <v-sheet height="64">
          <v-toolbar flat>
            <v-btn
              class="ma-4"
              color="primary"
              variant="outlined"
              @click="setToday"
            >
              Today
            </v-btn>
            <v-btn
              variant="text"
              icon
              @click="prev"
            >
              <v-icon>mdi-chevron-left</v-icon>
            </v-btn>
            <v-btn
              variant="text"
              icon
              @click="next"
            >
              <v-icon>mdi-chevron-right</v-icon>
            </v-btn>
            <v-toolbar-title align="right" class="me-4" v-if="calendar">
              {{ calendar.title }}
            </v-toolbar-title>
          </v-toolbar>
        </v-sheet>
        
        <v-sheet height="600">
          <v-calendar
            ref="calendar"
            v-model="focus"
            :event-color="getEventColor"
            :events="events"
            :type="type"
            color="primary"
            @click:event="showEventDetails"
            @click:date="handleDateClick"
          >
            <template #event="{ event }">
              <div class="event-content">
                <v-icon size="small" class="mr-1">{{ getEventIcon(event.category) }}</v-icon>
                <span class="text-caption">{{ event.name }}</span>
              </div>
            </template>
          </v-calendar>
        </v-sheet>
      </div>
    </v-card-text>

    <!-- Add/Edit Event Dialog -->
    <v-dialog v-model="dialog" max-width="500">
      <v-card>
        <v-card-title>
          {{ editingEvent ? 'Edit Event' : 'Add New Event' }}
        </v-card-title>
        <v-divider />
        <v-card-text class="pt-4">
          <v-form ref="formRef">
            <v-text-field
              v-model="eventForm.title"
              label="Event Title"
              :rules="[v => !!v || 'Title is required']"
              variant="outlined"
              density="comfortable"
              class="mb-3"
            />

            <v-textarea
              v-model="eventForm.description"
              label="Description (Optional)"
              variant="outlined"
              density="comfortable"
              rows="2"
              class="mb-3"
            />

            <v-select
              v-model="eventForm.category"
              :items="categories"
              label="Category"
              variant="outlined"
              density="comfortable"
              class="mb-3"
            >
              <template #selection="{ item }">
                <v-icon :color="item.raw.color" class="mr-2">{{ item.raw.icon }}</v-icon>
                {{ item.title }}
              </template>
              <template #item="{ item, props }">
                <v-list-item v-bind="props">
                  <template #prepend>
                    <v-icon :color="item.raw.color">{{ item.raw.icon }}</v-icon>
                  </template>
                </v-list-item>
              </template>
            </v-select>

            <v-text-field
              v-model="eventForm.date"
              label="Date"
              type="date"
              :rules="[v => !!v || 'Date is required']"
              variant="outlined"
              density="comfortable"
              class="mb-3"
            />

            <v-row>
              <v-col cols="6">
                <v-text-field
                  v-model="eventForm.startTime"
                  label="Start Time"
                  type="time"
                  :rules="[v => !!v || 'Start time is required']"
                  variant="outlined"
                  density="comfortable"
                />
              </v-col>
              <v-col cols="6">
                <v-text-field
                  v-model="eventForm.endTime"
                  label="End Time"
                  type="time"
                  :rules="[v => !!v || 'End time is required']"
                  variant="outlined"
                  density="comfortable"
                />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn text @click="closeDialog">Cancel</v-btn>
          <v-btn 
            v-if="editingEvent" 
            color="error" 
            variant="text"
            @click="deleteEvent"
          >
            Delete
          </v-btn>
          <v-btn color="primary" @click="saveEvent">
            {{ editingEvent ? 'Update' : 'Save' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Event Details Dialog -->
    <v-dialog v-model="detailsDialog" max-width="400">
      <v-card v-if="selectedEvent">
        <v-card-title class="d-flex align-center">
          <v-icon :color="selectedEvent.color" class="mr-2">
            {{ getEventIcon(selectedEvent.category) }}
          </v-icon>
          {{ selectedEvent.title }}
        </v-card-title>
        <v-divider />
        <v-card-text>
          <div class="mb-2">
            <v-icon size="small" class="mr-2">mdi-calendar</v-icon>
            <span>{{ formatDate(selectedEvent.start) }}</span>
          </div>
          <div class="mb-2">
            <v-icon size="small" class="mr-2">mdi-clock-outline</v-icon>
            <span>{{ formatTime(selectedEvent.start) }} - {{ formatTime(selectedEvent.end) }}</span>
          </div>
          <div v-if="selectedEvent.description" class="mt-3">
            <p class="text-caption text-medium-emphasis mb-1">Description:</p>
            <p class="text-body-2">{{ selectedEvent.description }}</p>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn text @click="detailsDialog = false">Close</v-btn>
          <v-btn color="primary" @click="editEvent(selectedEvent)">
            <v-icon start>mdi-pencil</v-icon>
            Edit
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup lang="ts">
import type { VCalendar } from 'vuetify/labs/VCalendar'
import type { EventForm, ScheduleEvent } from '~/types/models'

const config = useRuntimeConfig()
const calendar = ref<InstanceType<typeof VCalendar> | null>(null)
const focus = ref('')
const type = ref<'month' | 'week' | 'day'>('month')
const dialog = ref(false)
const detailsDialog = ref(false)
const events = ref<ScheduleEvent[]>([])
const selectedEvent = ref<ScheduleEvent | null>(null)
const editingEvent = ref<ScheduleEvent | null>(null)
const formRef = ref()

const eventForm = ref<EventForm>({
  title: '',
  description: '',
  category: 'study',
  date: new Date().toISOString().split('T')[0],
  startTime: '09:00',
  endTime: '10:00'
})

const categories = [
  { value: 'study', title: 'Study Session', icon: 'mdi-book-open-variant', color: 'blue' },
  { value: 'exam', title: 'Exam', icon: 'mdi-clipboard-text', color: 'red' },
  { value: 'meeting', title: 'Meeting', icon: 'mdi-account-group', color: 'green' },
  { value: 'personal', title: 'Personal', icon: 'mdi-star', color: 'orange' },
  { value: 'other', title: 'Other', icon: 'mdi-calendar-blank', color: 'grey' }
]

function prev() {
  calendar.value?.prev()
}

function next() {
  calendar.value?.next()
}

function setToday() {
  focus.value = ''
}

const loadEvents = async () => {
  try {
    const { data } = await useFetch<{ events: ScheduleEvent[] }>(
      `${config.public.apiBase}/student/schedule`
    )
    if (data.value) {
      events.value = data.value.events.map(e => ({
        ...e,
        name: e.title,
        start: new Date(e.start),
        end: new Date(e.end),
        details: e.description
      }))
    }
  } catch (err) {
    console.error('Error loading events:', err)
  }
}

const openAddDialog = () => {
  editingEvent.value = null
  eventForm.value = {
    title: '',
    description: '',
    category: 'study',
    date: new Date().toISOString().split('T')[0],
    startTime: '09:00',
    endTime: '10:00'
  }
  dialog.value = true
}

const handleDateClick = (_: Event, { date }: { date: string }) => {
  eventForm.value.date = date
  openAddDialog()
}

const saveEvent = async () => {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  const startDateTime = new Date(`${eventForm.value.date}T${eventForm.value.startTime}`)
  const endDateTime = new Date(`${eventForm.value.date}T${eventForm.value.endTime}`)

  const category = categories.find(c => c.value === eventForm.value.category)!

  const newEvent: ScheduleEvent = {
    id: editingEvent.value?.id || `event-${Date.now()}`,
    title: eventForm.value.title,
    description: eventForm.value.description,
    category: eventForm.value.category,
    start: startDateTime,
    end: endDateTime,
    color: category.color
  }

  if (editingEvent.value) {
    const index = events.value.findIndex(e => e.id === editingEvent.value!.id)
    events.value[index] = newEvent
  } else {
    events.value.push(newEvent)
  }

  try {
    if (editingEvent.value) {
      await $fetch(`${config.public.apiBase}/student/schedule/${newEvent.id}`, {
        method: 'PUT',
        body: newEvent
      })
    } else {
      await $fetch(`${config.public.apiBase}/student/schedule`, {
        method: 'POST',
        body: newEvent
      })
    }
  } catch (err) {
    console.error('Error saving event:', err)
  }

  closeDialog()
}

const deleteEvent = async () => {
  if (!editingEvent.value) return
  events.value = events.value.filter(e => e.id !== editingEvent.value!.id)
  try {
    await $fetch(`${config.public.apiBase}/student/schedule/${editingEvent.value.id}`, {
      method: 'DELETE'
    })
  } catch (err) {
    console.error('Error deleting event:', err)
  }
  closeDialog()
}

const showEventDetails = (_: Event, { event }: { event: any }) => {
  selectedEvent.value = event as ScheduleEvent
  detailsDialog.value = true
}

const editEvent = (event: ScheduleEvent) => {
  editingEvent.value = event
  eventForm.value = {
    title: event.title,
    description: event.description || '',
    category: event.category,
    date: event.start.toISOString().split('T')[0],
    startTime: event.start.toTimeString().substring(0, 5),
    endTime: event.end.toTimeString().substring(0, 5)
  }
  detailsDialog.value = false
  dialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  editingEvent.value = null
}

const getEventColor = ((event: any) => event.color) as (event: unknown) => string

const getEventIcon = (category: string) =>
  categories.find(c => c.value === category)?.icon || 'mdi-calendar'

const formatDate = (date: Date) =>
  date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })

const formatTime = (date: Date) =>
  date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })

onMounted(() => {
  loadEvents()
})

defineExpose({
  refresh: loadEvents
})
</script>

<style scoped>
.calendar-card {
  height: 100%;
}

.event-content {
  display: flex;
  align-items: center;
  padding: 2px 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>