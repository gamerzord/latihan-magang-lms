<template>
  <v-container fluid class="page-container">
    <v-row>
      <v-col cols="12">
        <v-card elevation="2" class="card">
          <v-card-title class="d-flex justify-space-between align-center card-header">
            <div class="d-flex align-center">
              <span class="text-h5 font-weight-bold">User Management</span>
              <v-tooltip location="bottom">
                <template #activator="{ props }">
                  <v-icon v-bind="props" color="grey" class="ml-2" size="small">
                    mdi-information
                  </v-icon>
                </template>
                <span>Manage system users and their permissions</span>
              </v-tooltip>
            </div>
            
            <div class="d-flex gap-2">
              <v-btn
                variant="outlined"
                prepend-icon="mdi-refresh"
                @click="refresh"
                :loading="pending"
              >
                Refresh
              </v-btn>
            </div>
          </v-card-title>

          <v-card-text class="pa-0">
            <!-- Data Table -->
            <v-data-table
              :headers="headers"
              :items="users || []"
              :loading="pending"
              item-value="id"
              class="elevation-0"
              loading-text="Loading users..."
              no-data-text="No users found"
              items-per-page-text="Users per page:"
              :items-per-page-options="[10, 25, 50, 100]"
            >
            
              <!-- Role Column -->
              <template #item.role="{ item }">
                <v-chip
                  :color="getRoleColor(item.role)"
                  size="small"
                  class="font-weight-medium"
                >
                  <v-icon start small :icon="getRoleIcon(item.role)" />
                  {{ capitalizeFirst(item.role) }}
                </v-chip>
              </template>

              <!-- Actions Column -->
              <template #item.actions="{ item }">
                <div>
                  <v-btn
                    color="primary"
                    size="small"
                    variant="text"
                    icon0
                    @click="goToEdit(item.id)"
                    :loading="loading && currentActionId === item.id"
                  >
                    <v-icon size="small">mdi-pencil</v-icon>
                    <v-tooltip activator="parent" location="top">
                      Edit User
                    </v-tooltip>
                  </v-btn>

                  <v-btn
                    color="error"
                    size="small"
                    variant="text"
                    icon
                    @click="confirmDelete(item)"
                    :loading="loading && currentActionId === item.id"
                    :disabled="item.role === 'admin'"
                  >
                    <v-icon size="small">mdi-delete</v-icon>
                    <v-tooltip activator="parent" location="top">
                      {{ item.role === 'admin' ? 'Cannot delete admin users' : 'Delete User' }}
                    </v-tooltip>
                  </v-btn>
                </div>
              </template>

              <!-- Loading Skeleton -->
              <template #loading>
                <v-skeleton-loader type="table-row@10" />
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" max-width="500" persistent>
      <v-card>
        <v-card-title class="d-flex align-center">
          <v-icon color="error" class="mr-2">mdi-alert-circle</v-icon>
          <span class="text-h6">Confirm Deletion</span>
        </v-card-title>
        
        <v-card-text class="pt-4">
          <p class="text-body-1">
            Are you sure you want to delete user 
            <strong>"{{ userToDelete?.name }}"</strong>?
          </p>
          <p class="text-caption text-medium-emphasis mt-2">
            This action cannot be undone and will permanently remove the user from the system.
          </p>
        </v-card-text>
        
        <v-card-actions>
          <v-spacer />
          <v-btn 
            variant="text" 
            @click="deleteDialog = false"
            :disabled="loading"
          >
            Cancel
          </v-btn>
          <v-btn 
            color="error" 
            @click="deleteUser"
            :loading="loading"
            variant="flat"
          >
            Delete User
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Success Snackbar -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
      <div class="d-flex align-center">
        <v-icon class="mr-2">{{ snackbar.icon }}</v-icon>
        {{ snackbar.message }}
      </div>
    </v-snackbar>
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

const loading = ref(false)
const currentActionId = ref<number | null>(null)
const deleteDialog = ref(false)
const userToDelete = ref<User | null>(null)

const snackbar = ref({
  show: false,
  message: '',
  color: 'success',
  icon: 'mdi-check-circle'
})

const config = useRuntimeConfig()

const { data, pending, refresh } =
  await useFetch<{ users: User[]}>(`${config.public.apiBase}/users`, {
  })
const users = computed(() => data.value?.users)
const headers = [
  { title: 'ID', key: 'id', align:'center', sortable: true },
  { title: 'Name', key: 'name', align: 'center', sortable: true },
  { title: 'Email', key: 'email', align: 'center', sortable: true },
  { title: 'Role', key: 'role', align: 'center', sortable: true },
  { title: 'Actions', key: 'actions', align: 'center', sortable: false},
] as const

const goToEdit = (id: number) => navigateTo(`/admin/users/${id}/edit`)

const getRoleColor = (role: string) => ROLE_COLORS[role as keyof typeof ROLE_COLORS] || 'grey'
const getRoleIcon = (role: string) => ROLE_ICONS[role as keyof typeof ROLE_ICONS] || 'mdi-account'

const capitalizeFirst = (str: string) => {
  return str.charAt(0).toUpperCase() + str.slice(1)
}

const showSnackbar = (message: string, type: 'success' | 'error' = 'success') => {
  Object.assign(snackbar.value, {
    show: true,
    message,
    color: type === 'success' ? 'success' : 'error',
    icon: type === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle'
  })
}

const confirmDelete = (user: User) => {
  if (user.role === 'admin') {
    showSnackbar('Admin users cannot be deleted', 'error')
    return
  }
  
  userToDelete.value = user
  deleteDialog.value = true
}

const deleteUser = async () => {
  if (!userToDelete.value) return

  loading.value = true
  currentActionId.value = userToDelete.value.id

  try {
    await $fetch(`${config.public.apiBase}/users/${userToDelete.value.id}`, {
      method: 'DELETE',
    })

    if (users.value) {
      data.value!.users = users.value.filter(u => u.id !== userToDelete.value?.id)
    }

    showSnackbar('User deleted successfully')
    deleteDialog.value = false
    userToDelete.value = null
  } catch (err: any) {
    showSnackbar(err?.data?.message || 'Failed to delete user', 'error')
  } finally {
    loading.value = false
    currentActionId.value = null
  }
}

useHead({
  title: 'User Management'
})
</script>

<style scoped>
.page-container {
  max-width: 1400px;
  margin: 0 auto;
}

.card {
  border-radius: 12px;
}

.card-header {
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
  padding: 20px 24px;
}

.toolbar {
  background: transparent;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.gap-2 {
  gap: 8px;
}
</style>