<template>
  <AppLayout>
    <main class="space-y-6 p-4 sm:p-6 lg:p-8">
      <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900">Kelola Akun</h1>
          <p class="mt-1 text-sm text-slate-500">Buat akun untuk seluruh divisi dan lihat akun yang terdaftar.</p>
        </div>
        <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-800">
          Akses khusus Admin
        </div>
      </header>

      <div
        v-if="errorMessage"
        role="alert"
        class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
      >
        {{ errorMessage }}
      </div>
      <div
        v-if="successMessage"
        role="status"
        class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
      >
        {{ successMessage }}
      </div>

      <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="text-lg font-semibold text-slate-900">Buat Akun Baru</h2>
        <form class="mt-5 grid gap-4 md:grid-cols-2" @submit.prevent="createAccount">
          <label class="block text-sm font-medium text-slate-700">
            Nama lengkap
            <input
              v-model.trim="form.name"
              required
              maxlength="255"
              autocomplete="name"
              class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            />
          </label>
          <label class="block text-sm font-medium text-slate-700">
            Email
            <input
              v-model.trim="form.email"
              required
              type="email"
              autocomplete="email"
              class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            />
          </label>
          <label class="block text-sm font-medium text-slate-700">
            Divisi
            <select
              v-model="form.role"
              required
              class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            >
              <option disabled value="">Pilih divisi</option>
              <option v-for="division in divisions" :key="division.role" :value="division.role">
                {{ division.label }}
              </option>
            </select>
          </label>
          <label class="block text-sm font-medium text-slate-700">
            Kata sandi
            <input
              v-model="form.password"
              required
              type="password"
              minlength="8"
              autocomplete="new-password"
              class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            />
          </label>
          <label class="block text-sm font-medium text-slate-700 md:col-span-2">
            Konfirmasi kata sandi
            <input
              v-model="form.password_confirmation"
              required
              type="password"
              minlength="8"
              autocomplete="new-password"
              class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 md:max-w-[calc(50%-0.5rem)]"
            />
          </label>
          <div class="md:col-span-2">
            <button
              type="submit"
              :disabled="isCreating"
              class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {{ isCreating ? 'Menyimpan...' : 'Buat Akun' }}
            </button>
          </div>
        </form>
      </section>

      <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">Daftar Akun</h2>
            <p class="mt-1 text-sm text-slate-500">{{ accounts.length }} akun</p>
          </div>
          <button
            type="button"
            :disabled="isLoading"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
            @click="loadAccounts"
          >
            {{ isLoading ? 'Memuat...' : 'Muat ulang' }}
          </button>
        </div>

        <div v-if="isLoading && !accounts.length" class="px-6 py-10 text-center text-sm text-slate-500">
          Memuat daftar akun...
        </div>
        <div v-else-if="!accounts.length" class="px-6 py-10 text-center text-sm text-slate-500">
          Belum ada akun yang terdaftar.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th class="px-5 py-3 font-semibold sm:px-6">Nama</th>
                <th class="px-5 py-3 font-semibold sm:px-6">Email</th>
                <th class="px-5 py-3 font-semibold sm:px-6">Divisi</th>
                <th class="px-5 py-3 font-semibold sm:px-6">Dibuat</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="account in accounts" :key="account.id">
                <td class="whitespace-nowrap px-5 py-3.5 font-medium text-slate-900 sm:px-6">{{ account.name }}</td>
                <td class="whitespace-nowrap px-5 py-3.5 text-slate-600 sm:px-6">{{ account.email }}</td>
                <td class="whitespace-nowrap px-5 py-3.5 text-slate-600 sm:px-6">{{ divisionLabel(account.role) }}</td>
                <td class="whitespace-nowrap px-5 py-3.5 text-slate-500 sm:px-6">{{ formatDate(account.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </AppLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import api from '../../services/api';

const divisions = [
  { role: 'MARKETING', label: 'Marketing' },
  { role: 'DIREKTUR', label: 'Direktur' },
  { role: 'ADMINISTRASI', label: 'Administrasi (Admin)' },
  { role: 'WAREHOUSE', label: 'Warehouse' },
  { role: 'FINANCE', label: 'Finance' },
  { role: 'CLIENT', label: 'Client / Sekolah' },
];

const accounts = ref([]);
const isLoading = ref(false);
const isCreating = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const form = ref({
  name: '',
  email: '',
  role: '',
  password: '',
  password_confirmation: '',
});

const responseError = (error, fallback) => {
  const validationMessage = Object.values(error.response?.data?.errors || {}).flat()[0];
  return validationMessage || error.response?.data?.message || fallback;
};

const loadAccounts = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const response = await api.get('/admin/accounts');
    accounts.value = response.data.users;
  } catch (error) {
    errorMessage.value = responseError(error, 'Gagal memuat daftar akun.');
  } finally {
    isLoading.value = false;
  }
};

const createAccount = async () => {
  errorMessage.value = '';
  successMessage.value = '';

  if (form.value.password !== form.value.password_confirmation) {
    errorMessage.value = 'Konfirmasi kata sandi tidak cocok.';
    return;
  }

  isCreating.value = true;
  try {
    const response = await api.post('/admin/accounts', form.value);
    accounts.value.unshift(response.data.user);
    successMessage.value = `Akun ${response.data.user.name} berhasil dibuat.`;
    form.value = { name: '', email: '', role: '', password: '', password_confirmation: '' };
  } catch (error) {
    errorMessage.value = responseError(error, 'Gagal membuat akun.');
  } finally {
    isCreating.value = false;
  }
};

const divisionLabel = (role) => divisions.find((division) => division.role === role)?.label || role;

const formatDate = (date) => new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(date));

onMounted(loadAccounts);
</script>
