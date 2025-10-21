<template>
  <v-navigation-drawer
    v-model="drawer"
    :permanent="!isMobile"
    :temporary="isMobile"
    app
    color="surface"
  >
    <v-list-item class="px-2">
      <v-list-item-avatar>
        <v-icon color="primary">mdi-account-circle</v-icon>
      </v-list-item-avatar>
      <v-list-item-content>
        <v-list-item-title class="text-h6">
          {{ user?.name }}
        </v-list-item-title>
        <v-list-item-subtitle class="text-capitalize">
          {{ user?.role }}
        </v-list-item-subtitle>
      </v-list-item-content>
    </v-list-item>

    <v-divider class="my-2" />

    <v-list dense nav>
      <template v-if="user?.role === 'student'">
        <v-list-item to="/student" exact>
          <v-list-item-icon>
            <v-icon>mdi-view-dashboard</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Dashboard</v-list-item-title>
        </v-list-item>

        <v-list-item to="/student/courses">
          <v-list-item-icon>
            <v-icon>mdi-book-multiple</v-icon>
          </v-list-item-icon>
          <v-list-item-title>My Courses</v-list-item-title>
        </v-list-item>

        <v-list-item to="/student/grades">
          <v-list-item-icon>
            <v-icon>mdi-chart-line</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Grades</v-list-item-title>
        </v-list-item>

        <v-list-item to="/student/calendar">
          <v-list-item-icon>
            <v-icon>mdi-calendar</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Calendar</v-list-item-title>
        </v-list-item>
      </template>

      <template v-else-if="user?.role === 'teacher'">
        <v-list-item to="/teacher" exact>
          <v-list-item-icon>
            <v-icon>mdi-view-dashboard</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Dashboard</v-list-item-title>
        </v-list-item>

        <v-list-item to="/teacher/courses">
          <v-list-item-icon>
            <v-icon>mdi-book-multiple</v-icon>
          </v-list-item-icon>
          <v-list-item-title>My Courses</v-list-item-title>
        </v-list-item>

        <v-list-item to="/teacher/courses/create">
          <v-list-item-icon>
            <v-icon>mdi-plus-circle</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Create Course</v-list-item-title>
        </v-list-item>

        <v-list-item to="/teacher/assignments">
          <v-list-item-icon>
            <v-icon>mdi-assignment</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Assignments</v-list-item-title>
        </v-list-item>

        <v-list-item to="/teacher/analytics">
          <v-list-item-icon>
            <v-icon>mdi-chart-bar</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Analytics</v-list-item-title>
        </v-list-item>
      </template>

      <template v-else-if="user?.role === 'admin'">
        <v-list-item to="/admin" exact>
          <v-list-item-icon>
            <v-icon>mdi-view-dashboard</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Dashboard</v-list-item-title>
        </v-list-item>

        <v-list-item to="/admin/users">
          <v-list-item-icon>
            <v-icon>mdi-account-group</v-icon>
          </v-list-item-icon>
          <v-list-item-title>User Management</v-list-item-title>
        </v-list-item>

        <v-list-item to="/admin/courses">
          <v-list-item-icon>
            <v-icon>mdi-book-multiple</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Course Management</v-list-item-title>
        </v-list-item>

        <v-list-item to="/admin/enrollments">
          <v-list-item-icon>
            <v-icon>mdi-account-arrow-right</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Enrollments</v-list-item-title>
        </v-list-item>

        <v-list-item to="/admin/settings">
          <v-list-item-icon>
            <v-icon>mdi-cog</v-icon>
          </v-list-item-icon>
          <v-list-item-title>System Settings</v-list-item-title>
        </v-list-item>
      </template>
    </v-list>

    <template v-slot:append>
      <div class="pa-2">
        <v-list-item to="/profile">
          <v-list-item-icon>
            <v-icon>mdi-account-cog</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Profile Settings</v-list-item-title>
        </v-list-item>

        <v-list-item @click="logout">
          <v-list-item-icon>
            <v-icon>mdi-logout</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Logout</v-list-item-title>
        </v-list-item>
      </div>
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
  }
}
</script>

<style scoped>
.v-list-item--active {
  background-color: rgba(25, 118, 210, 0.08);
}

.v-list-item--active .v-list-item__title {
  color: rgb(25, 118, 210);
  font-weight: 600;
}
</style>