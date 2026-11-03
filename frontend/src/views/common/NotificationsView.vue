<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pusat Notifikasi Terpadu</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pemberitahuan aktivitas operasional antar role sesuai alur proses bisnis TO-BE terintegrasi.
          </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 bg-white shadow-2xs transition-colors cursor-pointer"
            @click="markAllAsRead"
          >
            <CheckCheck class="w-4 h-4 text-emerald-600" />
            <span>Tandai Semua Sudah Dibaca</span>
          </button>
          <button
            v-if="readCount > 0"
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-rose-600 hover:bg-rose-50 bg-white shadow-2xs transition-colors cursor-pointer"
            @click="clearReadNotifs"
          >
            <Trash2 class="w-4 h-4" />
            <span>Hapus Yang Sudah Dibaca</span>
          </button>
        </div>
      </div>
    </template>

    <!-- Context Info Per Role -->
    <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-3.5 mb-5 text-xs text-slate-700 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <Bell class="w-4 h-4 text-blue-600 shrink-0" />
        <span>
          Menampilkan notifikasi masuk khusus untuk peran aktif: <strong class="text-blue-900 uppercase">{{ store.currentUser.role }}</strong> ({{ store.currentUser.name }})
        </span>
      </div>
      <span class="px-2 py-0.5 bg-blue-100 text-blue-800 font-bold rounded text-[11px]">
        {{ unreadCount }} Belum Dibaca
      </span>
    </div>

    <!-- MAIN NOTIFICATIONS CONTAINER -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
      <!-- Filter Tabs -->
      <div class="p-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-1.5">
          <button
            type="button"
            :class="[
              filterTab === 'all'
                ? 'bg-white text-slate-900 shadow-2xs font-bold border-slate-300'
                : 'text-slate-600 hover:text-slate-900 border-transparent',
              'px-3 py-1.5 rounded-lg border text-xs transition-all cursor-pointer'
            ]"
            @click="filterTab = 'all'"
          >
            Semua ({{ roleNotifs.length }})
          </button>
          <button
            type="button"
            :class="[
              filterTab === 'unread'
                ? 'bg-white text-blue-700 shadow-2xs font-bold border-slate-300'
                : 'text-slate-600 hover:text-slate-900 border-transparent',
              'px-3 py-1.5 rounded-lg border text-xs transition-all cursor-pointer flex items-center gap-1.5'
            ]"
            @click="filterTab = 'unread'"
          >
            <span>Belum Dibaca</span>
            <span v-if="unreadCount > 0" class="px-1.5 py-0.2 bg-rose-500 text-white rounded-full text-[10px] font-bold">
              {{ unreadCount }}
            </span>
          </button>
          <button
            type="button"
            :class="[
              filterTab === 'read'
                ? 'bg-white text-slate-900 shadow-2xs font-bold border-slate-300'
                : 'text-slate-600 hover:text-slate-900 border-transparent',
              'px-3 py-1.5 rounded-lg border text-xs transition-all cursor-pointer'
            ]"
            @click="filterTab = 'read'"
          >
            Sudah Dibaca ({{ readCount }})
          </button>
        </div>

        <div class="text-slate-400 text-xs">
          Otomatis diperbarui saat terjadi alur kerja antar role
        </div>
      </div>

      <!-- Notifications List -->
      <div class="divide-y divide-slate-100">
        <div
          v-for="notif in displayedNotifs"
          :key="notif.id"
          :class="[
            notif.isRead ? 'bg-white' : 'bg-blue-50/40',
            'p-4 hover:bg-slate-50/80 transition-colors flex items-start gap-3.5 text-xs'
          ]"
        >
          <!-- Category Indicator Icon -->
          <div
            :class="[
              getCategoryBadge(notif.category).bgClass,
              'w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5 shadow-2xs'
            ]"
          >
            <component :is="getCategoryBadge(notif.category).icon" class="w-4 h-4" />
          </div>

          <!-- Body Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-bold text-slate-900 text-sm">{{ notif.title }}</span>
                  <span
                    :class="[
                      getCategoryBadge(notif.category).badgeClass,
                      'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider'
                    ]"
                  >
                    {{ getCategoryBadge(notif.category).label }}
                  </span>
                  <span
                    v-if="!notif.isRead"
                    class="px-1.5 py-0.2 bg-blue-100 text-blue-700 font-bold rounded-full text-[9px]"
                  >
                    Baru
                  </span>
                </div>
                <p class="text-slate-600 mt-1.5 leading-relaxed text-xs">{{ notif.message }}</p>
              </div>

              <!-- Time & Delete -->
              <div class="flex flex-col items-end gap-1.5 shrink-0">
                <span class="text-[11px] text-slate-400 whitespace-nowrap">{{ notif.time }}</span>
                <button
                  type="button"
                  class="text-slate-400 hover:text-rose-600 p-1 transition-colors cursor-pointer"
                  title="Hapus notifikasi ini"
                  @click="deleteSingleNotif(notif.id)"
                >
                  <X class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <!-- Action Link Buttons -->
            <div class="mt-3 flex items-center gap-3">
              <router-link
                v-if="notif.link"
                :to="notif.link"
                class="inline-flex items-center gap-1 font-bold text-alpharo-500 hover:text-blue-800 hover:underline cursor-pointer"
                @click="notif.isRead = true"
              >
                <span>Buka Halaman Terkait</span>
                <ArrowRight class="w-3.5 h-3.5" />
              </router-link>
              <button
                v-if="!notif.isRead"
                type="button"
                class="text-slate-400 hover:text-slate-700 cursor-pointer"
                @click="notif.isRead = true"
              >
                Tandai sudah dibaca
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="displayedNotifs.length === 0" class="p-12 text-center text-slate-400 text-xs space-y-1">
          <CheckCircle2 class="w-8 h-8 text-slate-300 mx-auto mb-2" />
          <p class="font-semibold text-slate-600">Tidak ada notifikasi dalam filter ini.</p>
          <p class="text-[11px] text-slate-400">Seluruh proses kerja yang ditujukan ke role Anda akan muncul secara langsung di sini.</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import { store } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import {
  Bell,
  CheckCheck,
  Trash2,
  CheckCircle2,
  ArrowRight,
  X,
  ShoppingBag,
  Package,
  CreditCard,
  FileText,
  AlertTriangle,
  Truck,
  Check,
} from 'lucide-vue-next';

const toast = useToast();
const filterTab = ref('all'); // 'all' | 'unread' | 'read'

const roleNotifs = computed(() => {
  return (store.notifications || []).filter(
    (n) => n.role === store.currentUser.role || n.role === 'ALL'
  );
});

const unreadCount = computed(() => roleNotifs.value.filter((n) => !n.isRead).length);
const readCount = computed(() => roleNotifs.value.filter((n) => n.isRead).length);

const displayedNotifs = computed(() => {
  if (filterTab.value === 'unread') {
    return roleNotifs.value.filter((n) => !n.isRead);
  }
  if (filterTab.value === 'read') {
    return roleNotifs.value.filter((n) => n.isRead);
  }
  return roleNotifs.value;
});

const markAllAsRead = () => {
  roleNotifs.value.forEach((n) => (n.isRead = true));
  toast.success('Semua notifikasi berhasil ditandai telah dibaca.');
};

const clearReadNotifs = () => {
  const currentRole = store.currentUser.role;
  store.notifications = store.notifications.filter(
    (n) => !(n.role === currentRole && n.isRead)
  );
  toast.info('Notifikasi yang sudah dibaca telah dibersihkan.');
};

const deleteSingleNotif = (notifId) => {
  const idx = store.notifications.findIndex((n) => n.id === notifId);
  if (idx !== -1) {
    store.notifications.splice(idx, 1);
  }
};

const getCategoryBadge = (category) => {
  switch (category) {
    case 'procurement':
    case 'purchase':
      return {
        label: 'Pengadaan Supplier',
        icon: ShoppingBag,
        bgClass: 'bg-purple-100 text-purple-700',
        badgeClass: 'bg-purple-50 text-purple-700 border border-purple-200',
      };
    case 'warehouse':
      return {
        label: 'Gudang & Stok',
        icon: Package,
        bgClass: 'bg-emerald-100 text-emerald-700',
        badgeClass: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
      };
    case 'finance':
    case 'invoice':
    case 'payment':
      return {
        label: 'Keuangan & Pembayaran',
        icon: CreditCard,
        bgClass: 'bg-blue-100 text-blue-700',
        badgeClass: 'bg-blue-50 text-blue-700 border border-blue-200',
      };
    case 'approval':
      return {
        label: 'Persetujuan Direktur',
        icon: Check,
        bgClass: 'bg-amber-100 text-amber-700',
        badgeClass: 'bg-amber-50 text-amber-700 border border-amber-200',
      };
    case 'document':
      return {
        label: 'Dokumen SPJ',
        icon: FileText,
        bgClass: 'bg-cyan-100 text-cyan-700',
        badgeClass: 'bg-cyan-50 text-cyan-700 border border-cyan-200',
      };
    case 'shipment':
      return {
        label: 'Pengiriman & Resi',
        icon: Truck,
        bgClass: 'bg-sky-100 text-sky-700',
        badgeClass: 'bg-sky-50 text-sky-700 border border-sky-200',
      };
    case 'warning':
      return {
        label: 'Kendala / Peringatan',
        icon: AlertTriangle,
        bgClass: 'bg-rose-100 text-rose-700',
        badgeClass: 'bg-rose-50 text-rose-700 border border-rose-200',
      };
    default:
      return {
        label: 'Informasi',
        icon: Bell,
        bgClass: 'bg-slate-100 text-slate-700',
        badgeClass: 'bg-slate-50 text-slate-700 border border-slate-200',
      };
  }
};
</script>
