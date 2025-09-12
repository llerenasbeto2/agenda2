<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UserUnloginLayout from '@/Layouts/UserUnloginLayout.vue';

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth.user);
</script>

<template>
  <AuthenticatedLayout v-if="isAuthenticated">
    <template #header v-if="$slots.header">
      <slot name="header"></slot>
    </template>
    <slot></slot>
  </AuthenticatedLayout>
  
  <UserUnloginLayout v-else>
    <template #header v-if="$slots.header">
      <slot name="header"></slot>
    </template>
    <slot></slot>
  </UserUnloginLayout>
</template>