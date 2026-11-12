<template>
  <div class="min-h-full flex flex-col bg-slate-50 text-slate-900 font-sans">
    <div class="flex-1">
      <Head :title="page.props.navigation?.title" />
      <slot />
    </div>
  </div>
</template>

<script setup>
import { watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { storeActions } from './stores/mockData';

const page = usePage();
watch(() => page.props.auth?.user, user => {
  if (user) {
    localStorage.setItem('user', JSON.stringify(user));
    storeActions.setAuthenticatedUser(user);
  } else {
    localStorage.removeItem('user');
    localStorage.removeItem('token');
  }
}, { immediate: true });
</script>
