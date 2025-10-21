<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col>
        <v-card class="elevation-12">
          <v-toolbar color="primary" dark flat>
            <v-toolbar-title>Join LMS</v-toolbar-title>
          </v-toolbar>
          
          <v-card-text>
            <v-form ref="form" v-model="valid" @submit.prevent="register">
              <v-card variant="outlined" class="mb-4">
                <v-card-text class="text-center">
                  <div class="text-subtitle-1 mb-2">I am a:</div>
                  <v-btn-toggle
                    v-model="role"
                    mandatory
                    color="primary"
                    class="w-100"
                  >
                    <v-btn value="student" class="flex-grow-1">
                      <v-icon start>mdi-school</v-icon>
                      Student
                    </v-btn>
                    <v-btn value="teacher" class="flex-grow-1">
                      <v-icon start>mdi-teach</v-icon>
                      Teacher
                    </v-btn>
                  </v-btn-toggle>
                </v-card-text>
              </v-card>

              <v-text-field
                v-model="name"
                :rules="nameRules"
                label="Full Name"
                prepend-icon="mdi-account"
                required
              />

              <v-text-field
                v-model="email"
                :rules="emailRules"
                label="Email"
                prepend-icon="mdi-email"
                required
                type="email"
              />

              <v-text-field
                v-model="password"
                :rules="passwordRules"
                label="Password"
                prepend-icon="mdi-lock"
                :type="showPassword ? 'text' : 'password'"
                :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                @click:append="showPassword = !showPassword"
                required
              />

              <v-text-field
                v-model="confirmPassword"
                :rules="confirmPasswordRules"
                label="Confirm Password"
                prepend-icon="mdi-lock-check"
                :type="showConfirmPassword ? 'text' : 'password'"
                :append-icon="showConfirmPassword ? 'mdi-eye' : 'mdi-eye-off'"
                @click:append="showConfirmPassword = !showConfirmPassword"
                required
              />

              <v-alert
                v-if="errorMessage"
                type="error"
                dense
                class="mb-4"
              >
                {{ errorMessage }}
              </v-alert>

              <v-alert
                v-if="successMessage"
                type="success"
                dense
                class="mb-4"
              >
                {{ successMessage }}
              </v-alert>
            </v-form>
          </v-card-text>

          <v-card-actions>
            <v-btn text @click="navigateTo('/login')">
              Already have an account? Login
            </v-btn>
            <v-spacer />
            <v-btn
              color="primary"
              :loading="loading"
              :disabled="!valid"
              @click="register"
            >
              Register
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'auth'
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

const nameRules = [
  (v: string) => !!v || 'Name is required',
  (v: string) => v.length >= 2 || 'Name must be at least 2 characters',
]

const emailRules = [
  (v: string) => !!v || 'Email is required',
  (v: string) => /.+@.+\..+/.test(v) || 'Email must be valid',
]

const passwordRules = [
  (v: string) => !!v || 'Password is required',
  (v: string) => v.length >= 8 || 'Password must be at least 8 characters',
  (v: string) => /[A-Z]/.test(v) || 'Password must contain at least one uppercase letter',
  (v: string) => /[a-z]/.test(v) || 'Password must contain at least one lowercase letter',
  (v: string) => /\d/.test(v) || 'Password must contain at least one number',
]

const confirmPasswordRules = [
  (v: string) => !!v || 'Please confirm your password',
  (v: string) => v === password.value || 'Passwords do not match',
]

const register = async () => {
  if (!valid.value) return
  
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await $fetch('https://localhost:8000/sanctum/csrf-cookie', {
    })
     await $fetch(`${config.public.apiBase}/users`, {
      method: 'POST',
      body: {
        name: name.value,
        email: email.value,
        password: password.value,
        password_confirmation: confirmPassword.value,
        role: role.value,
      }
    })
    successMessage.value = 'Registration successful! Redirecting to login...'
    setTimeout(() => {
      navigateTo('/login')
    }, 2000)

  } catch (error: any) {
    console.error('Registration error:', error)
    errorMessage.value = 'An unexpected error occurred. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>