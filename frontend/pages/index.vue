<template>
  <v-container
    fluid
    class="landing-container d-flex flex-column justify-center align-center text-center"
  >
    <v-card class="landing-card pa-8" elevation="0">
      <v-card-title class="landing-title text-h4 font-weight-bold mb-4">
        Percobaan Aplikasi LMS
      </v-card-title>

      <v-card-text>
        <p class="landing-subtitle text-body-1 mb-6">
          Latihan Pembuatan Aplikasi LMS Simple — Tugas Magang.
        </p>

        <div v-if="!pending && !authenticated">
          <v-btn
            color="primary"
            class="ma-2 px-6 py-3 text-body-1 font-weight-medium"
            rounded
            to="/login"
          >
            Login
          </v-btn>

          <v-btn
            color="secondary"
            class="ma-2 px-6 py-3 text-body-1 font-weight-medium"
            variant="outlined"
            rounded
            to="/register"
          >
            Register
          </v-btn>
        </div>

        <div v-else-if="pending">
          <v-progress-circular indeterminate color="primary" />
          <p class="mt-2 text-medium-emphasis">Loading...</p>
        </div>

        <div v-else>
          <p class="text-body-1 mb-4">
            Welcome back, <strong>{{ user?.name }}</strong>!
          </p>
          <v-btn
            color="primary"
            rounded
            class="px-6 py-3"
            @click="goToDashboard"
            :loading="navigating"
          >
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

const route = useRoute()

if (!authenticated && route.path !== '/') {
  await navigateTo('/login')
}

const goToDashboard = async () => {
  if (!user.value) return
  navigating.value = true

  try {
    if (hasRole('admin')) navigateTo('/admin')
    else if (hasRole('teacher')) navigateTo('/teacher')
    else if (hasRole('student')) navigateTo('/student')
  } catch (error) {
    console.error('Navigation error:', error)
  } finally {
    navigating.value = false
  }
}
</script>

<style scoped>
.landing-container {
  min-height: 100vh;
  background-color: #f8f9fc; /* matches dashboard */
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.landing-card {
  background-color: #ffffff;
  border-radius: 20px;
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.06);
  max-width: 500px;
  width: 90%;
}

.landing-title {
  color: #0170B9;
  letter-spacing: 0.5px;
}

.landing-subtitle {
  color: #6c757d;
  line-height: 1.6;
}
</style>
