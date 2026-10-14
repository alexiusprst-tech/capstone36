<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pusat Notifikasi Sistem</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pemberitahuan aktivitas, perubahan status dokumen, pengajuan order, dan peringatan operasional.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 bg-white"
          @click="markAllAsRead"
        >
          <CheckCheck class="w-4 h-4 text-emerald-600" />
          <span>Tandai Semua Sudah Dibaca</span>
        </button>
      </div>
    </template>

    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-900 uppercase">Notifikasi Untuk Peran: {{ store.currentUser.role }}</span>
        <span class="text-slate-500">{{ unreadCount }} Belum Dibaca</span>
      </div>

      <div class="divide-y divide-slate-100">
        <div
          v-for="notif in roleNotifs"
          :key="notif.id"
          :class="[notif.isRead ? 'bg-white' : 'bg-blue-50/40', 'p-4 hover:bg-slate-50/80 transition-colors flex items-start gap-4 text-xs']"
        >
          <div
            :class="[
              notif.isRead ? 'bg-slate-100 text-slate-500' : 'bg-blue-100 text-alpharo-500',
              'w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5'
            ]"
          >
            <Bell class="w-4 h-4" />
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <span class="font-bold text-slate-900 text-sm">{{ notif.title }}</span>
              <span class="text-[11px] text-slate-400 whitespace-nowrap">{{ notif.time }}</span>
            </div>
            <p class="text-slate-600 mt-1 leading-relaxed text-xs">{{ notif.message }}</p>

            <div class="mt-3 flex items-center gap-3">
              <router-link
                v-if="notif.link"
                :to="notif.link"
                class="font-bold text-alpharo-500 hover:underline"
                @click="notif.isRead = true"
              >
                Lihat Rincian Halaman →
              </router-link>
              <button
                v-if="!notif.isRead"
                type="button"
                class="text-slate-400 hover:text-slate-600"
                @click="notif.isRead = true"
              >
                Tandai dibaca
              </button>
            </div>
          </div>
        </div>

        <div v-if="roleNotifs.length === 0" class="p-12 text-center text-slate-400 text-xs">
          Tidak ada notifikasi aktif saat ini.
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import { store } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import { Bell, CheckCheck } from 'lucide-vue-next';

const toast = useToast();

const roleNotifs = computed(() => {
  return store.notifications.filter((n) => n.role === store.currentUser.role || n.role === 'ALL');
});

const unreadCount = computed(() => roleNotifs.value.filter((n) => !n.isRead).length);

const markAllAsRead = () => {
  roleNotifs.value.forEach((n) => (n.isRead = true));
  toast.success('Semua notifikasi ditandai telah dibaca.');
};
</script>
