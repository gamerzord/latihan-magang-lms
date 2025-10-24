<template>
  <v-card elevation="2" class="pa-4">
    <div class="d-flex justify-space-between align-center mb-4">
      <h3 class="text-h6">Course Assignments</h3>
      <span class="text-body-2 text-medium-emphasis">
        {{ assignmentCount }} assignments
      </span>
    </div>

    <template v-if="pending">
      <div class="text-center py-8">
        <v-progress-circular indeterminate color="primary" />
        <p class="mt-4 text-body-1">Loading assignments...</p>
      </div>
    </template>

    <template v-else-if="displayAssignments && displayAssignments.length">
      <v-list class="bg-transparent">
        <v-list-item
          v-for="(assignment, index) in displayAssignments"
          :key="assignment.id"
          class="assignment-item"
        >
          <template #prepend>
            <div class="assignment-number mr-4">
              <v-avatar color="secondary" size="32">
                <span class="text-caption text-white">
                  {{ index + 1 }}
                </span>
              </v-avatar>
            </div>
          </template>

          <v-list-item-title class="text-body-1 font-weight-medium">
            {{ assignment.title }}
          </v-list-item-title>

          <v-list-item-subtitle class="text-caption">
            {{ assignment.assignment_code }} <span v-if="assignment.due_date">• Due: {{ formatDate(assignment.due_date) }}</span>
          </v-list-item-subtitle>

          <template #append>
            <div class="d-flex gap-1">
              <v-btn
                icon
                variant="text"
                size="small"
                color="primary"
                @click="$emit('edit', assignment)"
              >
                <v-icon>mdi-pencil</v-icon>
              </v-btn>
              <v-btn
                icon
                variant="text"
                size="small"
                color="error"
                @click="$emit('delete', assignment)"
              >
                <v-icon>mdi-delete</v-icon>
              </v-btn>
            </div>
          </template>
        </v-list-item>
      </v-list>
    </template>

    <template v-else>
      <div class="text-center py-8 text-medium-emphasis">
        <v-icon size="48" class="mb-2">mdi-file-outline</v-icon>
        <p>No assignments yet.</p>
        <p class="text-caption">Start by adding your first assignment for this course.</p>
        <v-btn color="secondary" class="mt-4" @click="$emit('add')">
          <v-icon start>mdi-plus</v-icon>
          Create First Assignment
        </v-btn>
      </div>
    </template>
  </v-card>
</template>

<script setup lang="ts">
import type { Course, Assignment } from '~/types/models'

const props = defineProps<{
  assignments?: Assignment[]
  course?: Course | null
  pending?: boolean
}>()

defineEmits<{
  (e: 'add'): void
  (e: 'edit', assignment: Assignment): void
  (e: 'delete', assignment: Assignment): void
}>()

const displayAssignments = computed(() => {
  return props.assignments ?? []
})

const assignmentCount = computed(() => (displayAssignments.value?.length ?? 0))

const formatDate = (dateString: Date | string | null) => {
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
.assignment-item {
  border-radius: 8px;
  margin-bottom: 4px;
  transition: all 0.2s ease;
}
.assignment-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
}
.assignment-number {
  min-width: 40px;
  display: flex;
  justify-content: center;
}
.gap-1 {
  gap: 4px;
}
</style>
