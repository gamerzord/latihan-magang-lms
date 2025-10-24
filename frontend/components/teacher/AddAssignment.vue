<template>
  <v-dialog v-model="open" max-width="600">
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span class="text-h6">Add Assignment</span>
        <v-btn icon @click="close">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-card-text>
        <v-form v-model="formValid" @submit.prevent="submitAssignment">
          <v-text-field
            v-model="form.assignment_code"
            label="Assignment Code"
            placeholder="e.g., ASSIGN-01"
            variant="outlined"
            :rules="[requiredRule]"
            class="mb-3"
          />

          <v-text-field
            v-model="form.title"
            label="Title"
            placeholder="e.g., HTML Project"
            variant="outlined"
            :rules="[requiredRule]"
            class="mb-3"
          />

          <v-textarea
            v-model="form.description"
            label="Description"
            placeholder="Brief instructions or goals for this assignment..."
            variant="outlined"
            rows="4"
            class="mb-3"
          />

          <v-text-field
            v-model="form.due_date"
            label="Due Date"
            type="date"
            variant="outlined"
            :rules="[requiredRule]"
            class="mb-3"
          />

          <div class="d-flex justify-end gap-2 mt-2">
            <v-btn variant="outlined" @click="close">
              Cancel
            </v-btn>
            <v-btn
              color="primary"
              type="submit"
              :loading="submitting"
              :disabled="!formValid || submitting"
            >
              <v-icon start>mdi-check</v-icon>
              Create
            </v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
const props = defineProps<{
  courseId: number
  modelValue: boolean
}>()

const emit = defineEmits(['update:modelValue', 'created'])

const config = useRuntimeConfig()
const open = ref(props.modelValue)
watch(() => props.modelValue, (v) => (open.value = v))
watch(open, (v) => emit('update:modelValue', v))

const form = ref({
  assignment_code: '',
  title: '',
  description: '',
  due_date: '',
  course_id: props.courseId,
})

const formValid = ref(false)
const submitting = ref(false)

const requiredRule = (v: string) => !!v || 'This field is required'

const close = () => {
  open.value = false
}

const submitAssignment = async () => {
  if (!formValid.value) return
  submitting.value = true

  try {
    await $fetch(`${config.public.apiBase}/assignments`, {
      method: 'POST',
      body: form.value,
    })

    emit('created') 
    close()
  } catch (err: any) {
    alert(err.data?.message || 'Failed to create assignment')
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.gap-2 {
  gap: 8px;
}
</style>
