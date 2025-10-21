<template>
  <v-container class="fill-height d-flex flex-column justify-center align-center text-center">
    <v-card class="pa-8" elevation="3" max-width="500">
      <v-card-title class="text-h4 font-weight-bold mb-4">
        Percobaan Aplikasi LMS
      </v-card-title>

      <v-card-text>
        <p class="text-body-1 mb-4">
          Latihan Pembuatan Aplikasi LMS Simple, Tugas Magang.
        </p>

        <div v-if="!pending && !authenticated">
          <v-btn color="primary" class="ma-2" to="/login">Login</v-btn>
          <v-btn color="secondary" class="ma-2" to="/register">Register</v-btn>
        </div>

        <div v-else-if="pending">
          <v-progress-circular indeterminate color="primary"></v-progress-circular>
          <p class="mt-2">Loading...</p>
        </div>

        <div v-else>
          <p class="text-body-1 mb-4">Welcome back, {{ user?.name }}!</p>
          <v-btn color="primary" @click="goToDashboard" :loading="navigating">
            Go to {{ user?.role }} Dashboard
          </v-btn>
        </div>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'blank'
})

const { user, authenticated, pending, hasRole } = useAuth()
const navigating = ref(false)


const goToDashboard = async () => {
  if (!user.value) return
  
  navigating.value = true
  
  try {
     if (hasRole('admin')) {
        navigateTo('/admin')
    } else if (hasRole('teacher')) {
        navigateTo('/teacher')
    } else if (hasRole('student')) {
        navigateTo('/student')
    }
  } catch (error) {
    console.error('Navigation error:', error)
  } finally {
    navigating.value = false
  }
}
</script>