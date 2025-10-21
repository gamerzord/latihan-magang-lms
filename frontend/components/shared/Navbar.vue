<template>
  <v-app-bar app color="primary" dark elevation="2">
    <v-toolbar-title class="font-weight-bold">
      <v-icon start>mdi-school</v-icon>
      LMS
    </v-toolbar-title>

    <v-spacer />

    <template v-if="user">
      <template v-if="user.role === 'student'">
        <v-btn to="/student" text>
          <v-icon start>mdi-book-open</v-icon>
          My Courses
        </v-btn>
        <v-btn to="/student/grades" text>
          <v-icon start>mdi-chart-line</v-icon>
          Grades
        </v-btn>
      </template>

      <template v-else-if="user.role === 'teacher'">
        <v-btn to="/teacher" text>
          <v-icon start>mdi-book-multiple</v-icon>
          My Courses
        </v-btn>
        <v-btn to="/teacher/courses/create" text>
          <v-icon start>mdi-plus-circle</v-icon>
          Create Course
        </v-btn>
        <v-btn to="/teacher/analytics" text>
          <v-icon start>mdi-chart-bar</v-icon>
          Analytics
        </v-btn>
      </template>

      <template v-else-if="user.role === 'admin'">
        <v-btn to="/admin" text>
          <v-icon start>mdi-view-dashboard</v-icon>
          Dashboard
        </v-btn>
        <v-btn to="/admin/users" text>
          <v-icon start>mdi-account-group</v-icon>
          Users
        </v-btn>
        <v-btn to="/admin/courses" text>
          <v-icon start>mdi-book-multiple</v-icon>
          Courses
        </v-btn>
        <v-btn to="/admin/settings" text>
          <v-icon start>mdi-cog</v-icon>
          Settings
        </v-btn>
      </template>

      <v-menu offset-y>
        <template v-slot:activator="{ props }">
          <v-btn text v-bind="props">
            <v-icon start>mdi-account</v-icon>
            {{ user.name }}
            <v-icon end>mdi-chevron-down</v-icon>
          </v-btn>
        </template>
        <v-list>
          <v-list-item to="/profile">
            <v-list-item-title>
              <v-icon start>mdi-account-cog</v-icon>
              Profile
            </v-list-item-title>
          </v-list-item>
          <v-list-item @click="logout">
            <v-list-item-title>
              <v-icon start>mdi-logout</v-icon>
              Logout
            </v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </template>

    <template v-else>
      <v-btn to="/login" text>
        <v-icon start>mdi-login</v-icon>
        Login
      </v-btn>
      <v-btn to="/register" text>
        <v-icon start>mdi-account-plus</v-icon>
        Register
      </v-btn>
    </template>
  </v-app-bar>
</template>

<script setup lang="ts">
const { user, logout } = useAuth()

const confirmLogout = async () => {
  if (confirm('Are you sure you want to logout?')) {
    await logout()
  }
}
</script>