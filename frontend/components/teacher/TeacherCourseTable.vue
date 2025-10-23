<template>
  <v-card elevation="2" class="pa-4">
    <v-card-title class="d-flex justify-space-between align-center">
      <span class="text-h6 font-weight-bold">My Courses</span>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="$emit('create')">
        New Course
      </v-btn>
    </v-card-title>

    <v-data-table
      :headers="headers"
      :items="courses"
      item-value="id"
      class="elevation-1"
      density="comfortable"
    >
      <template #item.title="{ item }">
        <div>
          <strong>{{ item.title }}</strong>
          <div class="text-caption text-medium-emphasis">
            {{ item.description || 'No description' }}
          </div>
        </div>
      </template>

      <template #item.actions="{ item }">
        <v-btn icon size="small" color="primary" variant="text" @click="$emit('view', item)">
          <v-icon>mdi-eye</v-icon>
        </v-btn>
        <v-btn icon size="small" color="amber" variant="text" @click="$emit('submissions', item)">
          <v-icon>mdi-file-document</v-icon>
        </v-btn>
        <v-btn icon size="small" color="indigo" variant="text" @click="$emit('edit', item)">
          <v-icon>mdi-pencil</v-icon>
        </v-btn>
      </template>

      <template #no-data>
        <div class="text-center py-10 text-medium-emphasis">
          <v-icon size="48" color="grey-lighten-1">mdi-book-off-outline</v-icon>
          <p class="mt-2">You haven't created any courses yet.</p>
        </div>
      </template>
    </v-data-table>
  </v-card>
</template>

<script setup lang="ts">
import type { Course } from '~/types/models'

defineProps<{
  courses: Course[]
}>()

const headers = [
  { title: 'Title', key: 'title' },
  { title: 'Students', key: 'enrolled_count'},
  { title: 'Updated', key: 'updated_at'},
  { title: 'Actions', key: 'actions', sortable: false}
]
</script>
