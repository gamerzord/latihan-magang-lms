<template>
  <v-navigation-drawer
    v-model="drawer"
    :permanent="!isMobile"
    :temporary="isMobile"
    app
    color="surface"
    elevation="2"
  >
    <!-- User Info -->
    <v-list-item class="px-3 pt-3">
      <v-list-item-avatar>
        <v-icon color="primary" size="36">mdi-account-circle</v-icon>
      </v-list-item-avatar>
      <v-list-item-content>
        <v-list-item-title class="font-weight-medium">
          {{ user?.name || 'Guest' }}
        </v-list-item-title>
        <v-list-item-subtitle class="text-capitalize">
          {{ user?.role || 'visitor' }}
        </v-list-item-subtitle>
      </v-list-item-content>
    </v-list-item>

    <v-divider class="my-2" />

    <!-- Navigation Links -->
    <v-list nav density="comfortable">
      <template v-if="user?.role === 'student'">
        <v-list-item to="/student" prepend-icon="mdi-view-dashboard" title="Dashboard" />
        <v-list-item to="/student/courses" prepend-icon="mdi-book-multiple" title="Courses" />
      </template>

      <template v-else-if="user?.role === 'teacher'">
        <v-list-item to="/teacher" prepend-icon="mdi-view-dashboard" title="Dashboard" />
        <v-list-item to="/teacher/courses/create" prepend-icon="mdi-plus-circle" title="Create Course" />
      </template>

      <template v-else-if="user?.role === 'admin'">
        <v-list-item to="/admin" prepend-icon="mdi-book-multiple" title="Course Management" />
        <v-list-item to="/admin/users" prepend-icon="mdi-account-group" title="User Management" />
        <v-list-item to="/admin/enrollments" prepend-icon="mdi-account-arrow-right" title="Enrollments" />
      </template>
    </v-list>

    <!-- Bottom Section -->
    <template #append>
      <v-divider class="my-2" />
      <v-list>
        <v-list-item @click="confirmLogout" prepend-icon="mdi-logout" title="Logout" />
      </v-list>
    </template>
  </v-navigation-drawer>
</template>

<script setup lang="ts">
const { user, logout } = useAuth()
const drawer = ref(true)
const isMobile = ref(false)

const updateMobile = () => {
  isMobile.value = window.innerWidth < 960
}

onMounted(() => {
  updateMobile()
  window.addEventListener('resize', updateMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', updateMobile)
})

const confirmLogout = async () => {
  if (confirm('Are you sure you want to logout?')) {
    await logout()
    navigateTo('/')
  }
}
</script>

<style scoped>
.v-list-item--active {
  background-color: rgba(var(--v-theme-primary), 0.1);
}

.v-list-item--active .v-list-item-title {
  color: rgb(var(--v-theme-primary));
  font-weight: 600;
}
</style>
