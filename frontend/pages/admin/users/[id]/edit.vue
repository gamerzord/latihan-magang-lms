<template>
  <v-container fluid class="page-container">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" class="card">
          <v-card-title class="d-flex justify-space-between align-center card-header">
            <div class="d-flex align-center">
              <span class="text-h5 font-weight-bold">Edit User</span>
              <v-tooltip location="bottom">
                <template #activator="{ props }">
                  <v-icon v-bind="props" color="grey" class="ml-2" size="small">
                    mdi-information
                  </v-icon>
                </template>
                <span>Update user information and role</span>
              </v-tooltip>
            </div>
          </v-card-title>

          <v-card-text class="pa-6">
            <v-alert v-if="successMessage" type="success" class="mb-4">
              {{ successMessage }}
            </v-alert>
            <v-alert v-if="errorMessage" type="error" class="mb-4">
              {{ errorMessage }}
            </v-alert>
            
            <v-form v-if="user" @submit.prevent="updateUser">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="user.name"
                    label="Full Name"
                    outlined
                    required
                    :rules="[v => !!v || 'Name is required']"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="user.email"
                    label="Email"
                    type="email"
                    outlined
                    required
                    :rules="[
                      v => !!v || 'Email is required',
                      v => /.+@.+\..+/.test(v) || 'Email must be valid'
                    ]"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="user.password"
                    label="Password"
                    type="password"
                    outlined
                    :rules="[v => !v || v.length >= 6 || 'Password must be at least 6 characters']"
                    hint="Leave blank to keep current password"
                    persistent-hint
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="user.role"
                    :items="roleOptions"
                    item-title="text"
                    item-value="value"
                    label="Role"
                    outlined
                    required
                    :rules="[v => !!v || 'Role is required']"
                  >
                    <template #item="{ props, item }">
                      <v-list-item v-bind="props">
                        <template #prepend>
                          <v-avatar :color="getRoleColor(item.value)" size="32">
                            <v-icon :icon="getRoleIcon(item.value)" />
                          </v-avatar>
                        </template>
                      </v-list-item>
                    </template>
                    <template #selection="{ item }">
                      <v-chip :color="getRoleColor(item.value)" class="ma-1">
                        <v-icon start :icon="getRoleIcon(item.value)" />
                        {{ item.title }}
                      </v-chip>
                    </template>
                  </v-select>
                </v-col>
              </v-row>

              <v-card-actions class="mt-6 px-0">
                <v-btn 
                  color="grey" 
                  variant="outlined" 
                  @click="$router.back()"
                  :disabled="loading"
                >
                  Cancel
                </v-btn>
                <v-spacer />
                <v-btn
                  color="primary"
                  type="submit"
                  :loading="loading"
                  variant="flat"
                  prepend-icon="mdi-content-save"
                >
                  Save Changes
                </v-btn>
              </v-card-actions>
            </v-form>
            
            <v-alert v-else type="info" class="mt-4">
              <div class="d-flex align-center">
                <v-progress-circular
                  indeterminate
                  size="20"
                  width="2"
                  class="mr-3"
                />
                Loading user data...
              </div>
            </v-alert>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import type { User } from '~/types/models'

const ROLE_COLORS = {
  admin: 'deep-purple',
  teacher: 'blue',
  student: 'green',
} as const

const ROLE_ICONS = {
  admin: 'mdi-shield-account',
  teacher: 'mdi-human-male-board',
  student: 'mdi-school',
} as const

const route = useRoute()
const config = useRuntimeConfig()
const id = route.params.id

const successMessage = ref('')
const errorMessage = ref('')
const loading = ref(false)

const { data } = await useFetch<{ user: User }>(
  `${config.public.apiBase}/users/${id}`
)

const user = computed(() => data.value?.user)

const roleOptions = [
  { value: 'admin', text: 'Administrator' },
  { value: 'teacher', text: 'Teacher' },
  { value: 'student', text: 'Student' },
]

const getRoleColor = (role: string) => ROLE_COLORS[role as keyof typeof ROLE_COLORS] || 'grey'
const getRoleIcon = (role: string) => ROLE_ICONS[role as keyof typeof ROLE_ICONS] || 'mdi-account'

const updateUser = async () => {
  if (!user.value) return

  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const updateData = {
      name: user.value.name,
      email: user.value.email,
      role: user.value.role,
      ...(user.value.password && { password: user.value.password })
    }

    await $fetch(`${config.public.apiBase}/users/${id}`, {
      method: 'PUT',
      body: updateData,
    })

    successMessage.value = 'User updated successfully!'
    setTimeout(() => navigateTo('/admin/users'), 1500)
  } catch (err: any) {
    console.error('Update Failed', err)
    errorMessage.value = err?.data?.message || 'Failed to update user'
  } finally {
    loading.value = false
  }
}

useHead({
  title: `Edit User - ${user.value?.name || 'Loading...'}`
})
</script>