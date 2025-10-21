<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col>
        <v-card class="elevation-12">
          <v-toolbar color="primary" dark flat>
            <v-toolbar-title>Login to LMS</v-toolbar-title>
          </v-toolbar>
          
          <v-card-text>
            <v-form ref="form" v-model="valid" @submit.prevent="handleLogin">
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

              <v-alert
                v-if="errorMessage"
                type="error"
                dense
                class="mb-4"
              >
                {{ errorMessage }}
              </v-alert>
            </v-form>
          </v-card-text>

          <v-card-actions>
            <v-spacer />
            <v-btn
              color="primary"
              :loading="loading"
              :disabled="!valid"
              @click="handleLogin"
            >
              Login
            </v-btn>
          </v-card-actions>

          <v-divider />

          <v-card-actions>
            <v-btn text small @click="navigateTo('/register')">
              Don't have an account? Register
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

const { login, authenticated } = useAuth()

const loading = ref(false)
const valid = ref(false)
const errorMessage = ref('')
const showPassword = ref(false)

const email = ref('')
const password = ref('')

const emailRules = [
  (v: string) => !!v || 'Email is required',
  (v: string) => /.+@.+\..+/.test(v) || 'Email must be valid',
]

const passwordRules = [
  (v: string) => !!v || 'Password is required',
  (v: string) => v.length >= 6 || 'Password must be at least 6 characters',
]

if (authenticated.value) {
  navigateTo('/')
}

const handleLogin = async () => {
  if (!valid.value) return
  
  loading.value = true
  errorMessage.value = ''

  try {
    await login({
      email: email.value,
      password: password.value
    })

      errorMessage.value = 'Invalid email or password'

  } catch (error: any) {
    console.error('Login error:', error)
    errorMessage.value = error.data?.message || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>