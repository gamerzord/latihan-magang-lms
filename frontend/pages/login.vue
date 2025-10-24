<template>
  <v-container
    fluid
    class="auth-container d-flex flex-column justify-center align-center"
  >
    <v-card class="auth-card pa-8" elevation="0">
      <v-card-title class="text-h5 font-weight-bold text-center mb-6">
        Login to LMS
      </v-card-title>

      <v-form ref="form" v-model="valid" @submit.prevent="handleLogin">
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

        <!-- Admin Login Toggle -->
        <div class="mb-4">
          <v-checkbox
            v-model="isAdminLogin"
            label="Admin Login"
            color="primary"
            density="compact"
            hide-details
          />
        </div>

        <v-alert
          v-if="errorMessage"
          type="error"
          variant="tonal"
          class="mb-4"
        >
          {{ errorMessage }}
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
          {{ isAdminLogin ? 'Admin Login' : 'Login' }}
        </v-btn>

        <div class="text-center">
          <p class="text-body-2 text-medium-emphasis">
            Don't have an account?
            <v-btn variant="text" color="primary" @click="navigateTo('/register')">
              Register
            </v-btn>
          </p>
        </div>
      </v-form>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
import type { AdminLoginResponse } from '~/types/models'

definePageMeta({
  layout: 'blank'
})

const config = useRuntimeConfig()
const { login, setAdminAuth } = useAuth()

const loading = ref(false)
const valid = ref(false)
const errorMessage = ref('')
const showPassword = ref(false)
const isAdminLogin = ref(false)
const email = ref('')
const password = ref('')

const emailRules = [
  (v: string) => !!v || 'Email is required',
  (v: string) => /.+@.+\..+/.test(v) || 'Email must be valid',
]
const passwordRules = [
  (v: string) => !!v || 'Password is required',
  (v: string) => v.length >= 6 || 'Minimum 6 characters',
]

const handleLogin = async () => {
  if (!valid.value) return
  loading.value = true
  errorMessage.value = ''

  try {
    if (isAdminLogin.value) {

      const response = await $fetch<AdminLoginResponse>(`${config.public.apiBase}/admin/login`, {
        method: 'POST',
        body: {
          email: email.value,
          password: password.value
        }
      })

      setAdminAuth(response.user)
      navigateTo('/admin')
    } else {
      await login({ email: email.value, password: password.value })
      navigateTo('/')
    }
  } catch (error: any) {
    errorMessage.value = error.data?.message || 
      (isAdminLogin.value ? 'Invalid admin credentials' : 'Invalid email or password')
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
  max-width: 440px;
}

.v-input input {
  font-size: 15px;
}
</style>