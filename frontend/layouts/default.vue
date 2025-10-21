<template>
  <v-app>
    <Navbar />
    <Sidebar />
    <v-main>
      <v-container fluid>
        <slot />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
import Navbar from '~/components/shared/Navbar.vue';
import Sidebar from '~/components/shared/Sidebar.vue';

</script>