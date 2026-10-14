<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';

const router = useRouter();

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const isLoading = ref(false);
const errorMessage = ref('');

const handleRegister = async () => {
  if (form.value.password !== form.value.password_confirmation) {
    errorMessage.value = 'Konfirmasi kata sandi tidak cocok.';
    return;
  }

  isLoading.value = true;
  errorMessage.value = '';

  try {
    const response = await api.post('/auth/register', {
      name: form.value.name,
      email: form.value.email,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
    });

    const { user, token } = response.data;
    localStorage.setItem('token', token);
    localStorage.setItem('user', JSON.stringify(user));

    router.push('/');
  } catch (error) {
    if (error.response && error.response.data && error.response.data.message) {
      errorMessage.value = error.response.data.message;
    } else {
      errorMessage.value = 'Gagal mendaftar. Silakan lengkapi formulir dengan benar.';
    }
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <div class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md space-y-8 rounded-3xl border border-slate-200/80 bg-white p-8 shadow-xl shadow-slate-200/50 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
      <div class="text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-500/20">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
          </svg>
        </div>
        <h2 class="mt-4 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Buat Akun Baru
        </h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
          Sudah punya akun?
          <router-link to="/login" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
            Masuk di sini
          </router-link>
        </p>
      </div>

      <!-- Error Alert -->
      <div
        v-if="errorMessage"
        class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300"
      >
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleRegister" class="mt-8 space-y-4">
        <div>
          <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
            Nama Lengkap
          </label>
          <div class="mt-1">
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              placeholder="Budi Pratama"
              class="block w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500"
            />
          </div>
        </div>

        <div>
          <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
            Alamat Email
          </label>
          <div class="mt-1">
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              placeholder="budi@email.com"
              class="block w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500"
            />
          </div>
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
            Kata Sandi
          </label>
          <div class="mt-1">
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              placeholder="Minimal 8 karakter"
              class="block w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500"
            />
          </div>
        </div>

        <div>
          <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
            Ulangi Kata Sandi
          </label>
          <div class="mt-1">
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              required
              placeholder="Ulangi kata sandi"
              class="block w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500"
            />
          </div>
        </div>

        <div class="pt-2">
          <button
            type="submit"
            :disabled="isLoading"
            class="flex w-full justify-center rounded-xl bg-indigo-600 px-4 py-3.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/20 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
          >
            <span v-if="!isLoading">Daftar Akun</span>
            <span v-else class="flex items-center space-x-2">
              <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>Mendaftarkan...</span>
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
