<template>
  <v-alert 
    v-if="activeConference"
    type="error"
    prominent
    border="start"
    class="mb-6 live-conference-banner"
  >
    <template #prepend>
      <v-icon size="50" class="pulse-icon">mdi-video</v-icon>
    </template>
    
    <v-row align="center">
      <v-col cols="12" md="8">
        <div class="text-h5 mb-2 font-weight-bold">
          <v-icon class="pulse-icon mr-2">mdi-circle</v-icon>
          Live Conference in Progress
        </div>
        <div class="text-h6 mb-1">{{ activeConference.title }}</div>
        <div class="text-body-1">
          Your teacher is hosting a live video conference right now!
        </div>
      </v-col>
      <v-col cols="12" md="4" class="text-md-right">
        <v-btn
          color="white"
          size="x-large"
          variant="elevated"
          @click="joinConference"
          class="join-btn"
        >
          <v-icon left size="large">mdi-video</v-icon>
          Join Conference
        </v-btn>
      </v-col>
    </v-row>
  </v-alert>
</template>

<script setup lang="ts">
import type { ActiveConference } from '~/types/models'

const props = defineProps<{
  courseId: number
}>()

const config = useRuntimeConfig()

const activeConference = ref<ActiveConference | null>(null)

const checkActiveConference = async () => {
  try {
    const { data } = await useFetch<{ conference: ActiveConference | null }>(
      `${config.public.apiBase}/student/courses/${props.courseId}/active-conference`
    )
    activeConference.value = data.value?.conference || null
  } catch (error) {
    console.error('Error checking conference:', error)
  }
}

const joinConference = () => {
  if (activeConference.value) {
    navigateTo(`/student/conference/${activeConference.value.id}/room`)
  }
}

onMounted(() => {
  checkActiveConference()
  
  const interval = setInterval(() => {
    checkActiveConference()
  }, 30000)
  
  onBeforeUnmount(() => {
    clearInterval(interval)
  })
})
</script>

<style scoped>
.live-conference-banner {
  animation: gentle-pulse 3s ease-in-out infinite;
  border-left-width: 8px !important;
}

.join-btn {
  font-weight: 600;
  letter-spacing: 0.5px;
  animation: button-pulse 2s ease-in-out infinite;
}

.pulse-icon {
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.6;
    transform: scale(1.1);
  }
}

@keyframes gentle-pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.005);
  }
}

@keyframes button-pulse {
  0%, 100% {
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
  }
  50% {
    box-shadow: 0 8px 24px rgba(255, 255, 255, 0.5);
  }
}
</style>