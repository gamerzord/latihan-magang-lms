<template>
  <v-container
    fluid
    class="auth-container d-flex flex-column justify-center align-center"
  >
    <v-card class="auth-card pa-8" elevation="0">
      <v-card-title class="text-h5 font-weight-bold text-center mb-6">
        Create Your LMS Account
      </v-card-title>

      <v-form ref="form" v-model="valid" @submit.prevent="register">
        <v-card variant="outlined" class="mb-4 text-center rounded-lg">
          <v-card-text>
            <p class="text-subtitle-2 mb-3 text-medium-emphasis">I am a:</p>
            <v-btn-toggle
              v-model="role"
              mandatory
              color="primary"
              class="w-100"
            >
              <v-btn value="student" class="flex-grow-1">Student</v-btn>
              <v-btn value="teacher" class="flex-grow-1">Teacher</v-btn>
            </v-btn-toggle>
          </v-card-text>
        </v-card>

        <v-text-field
          v-model="name"
          :rules="nameRules"
          label="Full Name"
          prepend-icon="mdi-account-outline"
          variant="outlined"
          density="comfortable"
          class="mb-4"
        />

        <v-text-field
          v-model="email"
          :rules="emailRules"
          label="Email"
          prepend-icon="mdi-email-outline"
          variant="outlined"
          density="comfortable"
          class="mb-4"
          type="email"
        />

        <v-text-field
          v-model="password"
          :rules="passwordRules"
          label="Password"
          prepend-icon="mdi-lock-outline"
          variant="outlined"
          density="comfortable"
          class="mb-4"
          :type="showPassword ? 'text' : 'password'"
          :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
          @click:append="showPassword = !showPassword"
        />

        <v-text-field
          v-model="confirmPassword"
          :rules="confirmPasswordRules"
          label="Confirm Password"
          prepend-icon="mdi-lock-check-outline"
          variant="outlined"
          density="comfortable"
          class="mb-4"
          :type="showConfirmPassword ? 'text' : 'password'"
          :append-icon="showConfirmPassword ? 'mdi-eye' : 'mdi-eye-off'"
          @click:append="showConfirmPassword = !showConfirmPassword"
        />

        <v-alert
          v-if="errorMessage"
          type="error"
          variant="tonal"
          class="mb-4"
        >
          {{ errorMessage }}
        </v-alert>

        <v-alert
          v-if="successMessage"
          type="success"
          variant="tonal"
          class="mb-4"
        >
          {{ successMessage }}
        </v-alert>

        <v-btn
          color="primary"
          block
          class="py-3 mb-4"
          :loading="loading"
          :disabled="!valid"
          type="submit"
          rounded
        >
          Register
        </v-btn>

        <div class="text-center">
          <p class="text-body-2 text-medium-emphasis">
            Already have an account?
            <v-btn variant="text" color="primary" @click="navigateTo('/login')">
              Login
            </v-btn>
          </p>
        </div>
      </v-form>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'blank'
})

const loading = ref(false)
const valid = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const config = useRuntimeConfig()

const role = ref<'student' | 'teacher'>('student')
const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')

const nameRules = [(v: string) => !!v || 'Name is required']
const emailRules = [
  (v: string) => !!v || 'Email is required',
  (v: string) => /.+@.+\..+/.test(v) || 'Email must be valid',
]
const passwordRules = [
  (v: string) => !!v || 'Password is required',
  (v: string) => v.length >= 8 || 'At least 8 characters',
]
const confirmPasswordRules = [
  (v: string) => !!v || 'Confirm your password',
  (v: string) => v === password.value || 'Passwords do not match',
]

const register = async () => {
  if (!valid.value) return
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await $fetch('https://localhost:8000/sanctum/csrf-cookie')
    await $fetch(`${config.public.apiBase}/users`, {
      method: 'POST',
      body: {
        name: name.value,
        email: email.value,
        password: password.value,
        password_confirmation: confirmPassword.value,
        role: role.value,
      },
    })
    successMessage.value = 'Registration successful! Redirecting to login...'
    setTimeout(() => navigateTo('/login'), 2000)
  } catch (error: any) {
    console.error('Registration error:', error)
    errorMessage.value = 'An unexpected error occurred. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-container {
  min-height: 100vh;
  background-color: #f8f9fc;
}

.auth-card {
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.06);
  width: 100%;
  max-width: 480px;
}
</style>
