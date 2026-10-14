<template>
  <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-800">
    <!-- Toast notifications container -->
    <ToastContainer />

    <!-- MOBILE SIDEBAR OVERLAY -->
    <div
      v-if="mobileSidebarOpen"
      class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs lg:hidden"
      @click="mobileSidebarOpen = false"
    ></div>

    <!-- MAIN WRAPPER -->
    <div class="flex flex-1 min-h-screen">
      <!-- SIDEBAR -->
      <aside
        :class="[
          sidebarCollapsed ? 'w-20' : 'w-64',
          mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
          'fixed inset-y-0 left-0 z-50 flex flex-col transition-all duration-300 ease-in-out',
          'bg-gradient-to-b from-[#0B4DA2] via-[#09428F] to-[#062F69] text-white shadow-xl'
        ]"
      >
        <!-- Sidebar Header with Logo -->
        <div class="h-20 flex items-center justify-between px-5 border-b border-white/10 shrink-0">
          <AlpharoLogo
            :collapsed="sidebarCollapsed"
            theme="white"
            size="md"
            :show-tagline="!sidebarCollapsed"
          />
          <button
            type="button"
            class="hidden lg:flex p-1.5 rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition-colors"
            :title="sidebarCollapsed ? 'Buka Sidebar' : 'Ciutkan Sidebar'"
            @click="sidebarCollapsed = !sidebarCollapsed"
          >
            <component :is="sidebarCollapsed ? ChevronRight : ChevronLeft" class="w-5 h-5" />
          </button>
          <button
            type="button"
            class="lg:hidden p-1.5 rounded-lg text-white/70 hover:text-white hover:bg-white/10"
            @click="mobileSidebarOpen = false"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Navigation Links Grouped -->
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
          <div v-for="(group, gIdx) in navGroups" :key="gIdx" class="space-y-1">
            <p
              v-if="!sidebarCollapsed && group.title"
              class="px-3 text-[11px] font-semibold tracking-wider uppercase text-blue-200/70 mb-2"
            >
              {{ group.title }}
            </p>

            <router-link
              v-for="item in group.items"
              :key="item.path"
              :to="item.path"
              @click="mobileSidebarOpen = false"
              v-slot="{ isActive, href, navigate }"
              custom
            >
              <a
                :href="href"
                @click="navigate"
                :class="[
                  isActive
                    ? 'bg-[#F5A623] text-slate-900 font-bold shadow-md shadow-amber-900/20'
                    : 'text-white/85 hover:text-white hover:bg-white/10 font-medium',
                  'group flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition-all duration-150',
                  sidebarCollapsed ? 'justify-center' : ''
                ]"
                :title="sidebarCollapsed ? item.label : ''"
              >
                <component
                  :is="item.icon"
                  :class="[
                    isActive ? 'text-slate-900' : 'text-white/80 group-hover:text-white',
                    'w-5 h-5 shrink-0'
                  ]"
                />
                <span v-if="!sidebarCollapsed" class="truncate">
                  {{ item.label }}
                </span>
                <span
                  v-if="!sidebarCollapsed && item.badge"
                  :class="[
                    isActive ? 'bg-slate-900 text-amber-300' : 'bg-white/20 text-white',
                    'ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full'
                  ]"
                >
                  {{ item.badge }}
                </span>
              </a>
            </router-link>
          </div>
        </nav>

        <!-- Sidebar Bottom Footer -->
        <div class="p-3 border-t border-white/10 bg-white/5 shrink-0">
          <div v-if="!sidebarCollapsed" class="px-3 py-2 flex items-center justify-between text-xs text-blue-200/80">
            <div class="flex items-center gap-2 truncate">
              <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
              <span class="truncate">{{ store.currentUser.name }}</span>
            </div>
            <button
              type="button"
              class="text-blue-200 hover:text-white p-1 rounded hover:bg-white/10"
              title="Logout"
              @click="handleLogout"
            >
              <LogOut class="w-4 h-4" />
            </button>
          </div>
        </div>
      </aside>

      <!-- MAIN CONTENT WRAPPER -->
      <div
        :class="[
          sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64',
          'flex-1 flex flex-col min-w-0 transition-all duration-300'
        ]"
      >
        <!-- TOPBAR -->
        <header class="sticky top-0 z-30 h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 lg:px-8 shadow-2xs">
          <!-- Left: Mobile Menu Toggle & Breadcrumbs -->
          <div class="flex items-center gap-4 min-w-0">
            <button
              type="button"
              class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100"
              @click="mobileSidebarOpen = true"
            >
              <Menu class="w-5 h-5" />
            </button>

            <!-- Breadcrumbs -->
            <nav class="hidden sm:flex items-center space-x-2 text-xs font-medium text-slate-500 truncate">
              <span class="text-slate-400">Alpharo</span>
              <ChevronRight class="w-3.5 h-3.5 text-slate-400 shrink-0" />
              <span class="text-slate-600 font-semibold">{{ roleLabel }}</span>
              <ChevronRight class="w-3.5 h-3.5 text-slate-400 shrink-0" />
              <span class="text-slate-900 font-semibold truncate">{{ pageTitle }}</span>
            </nav>
          </div>

          <!-- Right: Actions, Notifications, Role Switcher & User Profile -->
          <div class="flex items-center gap-3">
            <!-- Quick Role Switcher (For Capstone Evaluation & Review) -->
            <div class="relative">
              <button
                type="button"
                class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs font-medium text-slate-700 transition-colors"
                @click="roleDropdownOpen = !roleDropdownOpen"
              >
                <ShieldCheck class="w-3.5 h-3.5 text-alpharo-500" />
                <span class="font-semibold text-slate-900">Peran: {{ roleLabel }}</span>
                <ChevronDown class="w-3.5 h-3.5 text-slate-400" />
              </button>

              <!-- Role Switcher Dropdown -->
              <div
                v-if="roleDropdownOpen"
                class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-50 animate-in fade-in zoom-in-95 duration-150"
              >
                <div class="px-4 py-2 border-b border-slate-100 text-xs">
                  <p class="font-semibold text-slate-900">Pilih Role Pengguna</p>
                  <p class="text-slate-500 text-[11px]">Beralih konteks kerja secara instan</p>
                </div>
                <div class="py-1">
                  <button
                    v-for="r in store.roles"
                    :key="r.key"
                    type="button"
                    :class="[
                      store.currentUser.role === r.key ? 'bg-blue-50 text-alpharo-500 font-semibold' : 'text-slate-700 hover:bg-slate-50',
                      'w-full text-left px-4 py-2 text-xs flex items-center justify-between'
                    ]"
                    @click="onSelectRole(r.key)"
                  >
                    <div>
                      <p class="font-bold">{{ r.label }}</p>
                      <p class="text-[11px] text-slate-500">{{ r.division }}</p>
                    </div>
                    <span v-if="store.currentUser.role === r.key" class="text-alpharo-500">●</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Notifications Center Dropdown -->
            <div class="relative">
              <button
                type="button"
                class="relative p-2 text-slate-500 hover:text-slate-800 rounded-lg hover:bg-slate-100 transition-colors"
                title="Pusat Notifikasi"
                @click="notificationOpen = !notificationOpen"
              >
                <Bell class="w-5 h-5" />
                <span
                  v-if="unreadCount > 0"
                  class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-white animate-pulse"
                ></span>
              </button>

              <!-- Notifications Dropdown Box -->
              <div
                v-if="notificationOpen"
                class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-xl border border-slate-200 z-50 overflow-hidden"
              >
                <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Notifikasi</h4>
                    <span class="bg-blue-100 text-blue-700 text-[10px] font-bold px-1.5 py-0.5 rounded-full">
                      {{ unreadCount }} Baru
                    </span>
                  </div>
                  <router-link
                    :to="getNotificationRoute()"
                    class="text-xs text-alpharo-500 hover:underline font-medium"
                    @click="notificationOpen = false"
                  >
                    Lihat Semua
                  </router-link>
                </div>

                <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                  <div
                    v-for="notif in roleNotifications.slice(0, 5)"
                    :key="notif.id"
                    :class="[notif.isRead ? 'bg-white' : 'bg-blue-50/40', 'p-3 hover:bg-slate-50 transition-colors cursor-pointer']"
                    @click="handleNotificationClick(notif)"
                  >
                    <div class="flex items-start justify-between gap-2">
                      <p class="text-xs font-semibold text-slate-900 leading-tight">{{ notif.title }}</p>
                      <span class="text-[10px] text-slate-400 whitespace-nowrap">{{ notif.time }}</span>
                    </div>
                    <p class="text-xs text-slate-600 mt-1 line-clamp-2 leading-relaxed">{{ notif.message }}</p>
                  </div>

                  <div v-if="roleNotifications.length === 0" class="p-6 text-center text-xs text-slate-400">
                    Tidak ada notifikasi aktif untuk peran ini.
                  </div>
                </div>
              </div>
            </div>

            <!-- Profile Menu -->
            <div class="flex items-center pl-2 border-l border-slate-200">
              <router-link
                :to="getProfileRoute()"
                class="flex items-center gap-3 p-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                title="Profil Saya"
              >
                <img
                  :src="store.currentUser.avatar"
                  alt="Avatar"
                  class="w-8 h-8 rounded-full object-cover ring-1 ring-slate-200"
                />
                <div class="hidden md:flex flex-col text-left">
                  <span class="text-xs font-bold text-slate-900 leading-tight">
                    {{ store.currentUser.name }}
                  </span>
                  <span class="text-[10px] text-slate-500 font-medium">
                    {{ roleLabel }}
                  </span>
                </div>
              </router-link>
            </div>
          </div>
        </header>

        <!-- PAGE CONTENT CONTAINER -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
          <!-- Page Header Slot -->
          <div v-if="$slots.header" class="mb-6">
            <slot name="header"></slot>
          </div>

          <!-- Page Body Slot -->
          <slot></slot>
        </main>

        <!-- Enterprise Footer -->
        <footer class="mt-auto border-t border-slate-200/80 bg-white py-3.5 px-6 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-2">
          <span>&copy; 2026 CV Alpharo Artha Jaya — Sistem Informasi Terintegrasi Enterprise</span>
          <div class="flex items-center gap-4 text-[11px] text-slate-400">
            <span>Versi 1.0.0 (Capstone Release)</span>
            <span>•</span>
            <span class="text-emerald-600 font-medium flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Sistem Online
            </span>
          </div>
        </footer>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import ToastContainer from '../common/ToastContainer.vue';
import AlpharoLogo from '../common/AlpharoLogo.vue';

// Lucide Icons
import {
  LayoutDashboard,
  Users,
  ShoppingCart,
  ShoppingBag,
  FileText,
  FileCheck2,
  Package,
  Boxes,
  Truck,
  CreditCard,
  Receipt,
  FileSpreadsheet,
  CheckCircle2,
  Clock,
  ShieldCheck,
  Bell,
  LogOut,
  ChevronLeft,
  ChevronRight,
  ChevronDown,
  Menu,
  X,
  Search,
  Eye,
  Activity,
  Layers,
  Archive,
  BarChart3,
  ClipboardList,
  FolderCheck,
} from 'lucide-vue-next';

const router = useRouter();
const route = useRoute();
const toast = useToast();

const sidebarCollapsed = ref(false);
const mobileSidebarOpen = ref(false);
const roleDropdownOpen = ref(false);
const notificationOpen = ref(false);

const roleLabel = computed(() => {
  const currentRole = store.currentUser.role;
  const match = store.roles.find((r) => r.key === currentRole);
  return match ? match.label : currentRole;
});

const pageTitle = computed(() => {
  return route.meta?.title || 'Dashboard';
});

// Role-Scoped Notifications
const roleNotifications = computed(() => {
  return store.notifications.filter((n) => n.role === store.currentUser.role || n.role === 'ALL');
});

const unreadCount = computed(() => {
  return roleNotifications.value.filter((n) => !n.isRead).length;
});

// Sidebar Navigation Items Per Role
const navGroups = computed(() => {
  const role = store.currentUser.role;

  if (role === 'MARKETING') {
    return [
      {
        title: 'Utama',
        items: [
          { label: 'Dashboard', path: '/marketing/dashboard', icon: LayoutDashboard },
        ],
      },
      {
        title: 'Operasional Marketing',
        items: [
          { label: 'Client', path: '/marketing/clients', icon: Users },
          { label: 'Order', path: '/marketing/orders', icon: ShoppingCart },
          { label: 'Monitoring Order', path: '/marketing/orders/monitoring', icon: Clock },
        ],
      },
      {
        title: 'Akun',
        items: [
          { label: 'Notifikasi', path: '/marketing/notifications', icon: Bell, badge: unreadCount.value || null },
          { label: 'Profile', path: '/marketing/profile', icon: ShieldCheck },
        ],
      },
    ];
  }

  if (role === 'DIREKTUR') {
    return [
      {
        title: 'Utama',
        items: [
          { label: 'Dashboard', path: '/director/dashboard', icon: LayoutDashboard },
        ],
      },
      {
        title: 'Monitoring Eksekutif',
        items: [
          { label: 'Monitoring Order', path: '/director/orders', icon: ShoppingCart },
          { label: 'Monitoring Warehouse', path: '/director/warehouse', icon: Package },
          { label: 'Monitoring Administrasi', path: '/director/administration', icon: FileText },
          { label: 'Monitoring Finance', path: '/director/finance', icon: CreditCard },
        ],
      },
      {
        title: 'Analitik & Audit',
        items: [
          { label: 'Laporan', path: '/director/reports', icon: BarChart3 },
          { label: 'Activity Log', path: '/director/activities', icon: Activity },
        ],
      },
      {
        title: 'Akun',
        items: [
          { label: 'Notifikasi', path: '/director/notifications', icon: Bell, badge: unreadCount.value || null },
        ],
      },
    ];
  }

  if (role === 'ADMINISTRASI') {
    return [
      {
        title: 'Utama',
        items: [
          { label: 'Dashboard', path: '/administration/dashboard', icon: LayoutDashboard },
          { label: 'Dokumen SPJ', path: '/administration/spj', icon: FolderCheck },
        ],
      },
      {
        title: 'Akun',
        items: [
          { label: 'Notifikasi', path: '/administration/notifications', icon: Bell, badge: unreadCount.value || null },
          { label: 'Profile', path: '/administration/profile', icon: ShieldCheck },
        ],
      },
    ];
  }

  if (role === 'WAREHOUSE') {
    return [
      {
        title: 'Utama',
        items: [
          { label: 'Dashboard', path: '/warehouse/dashboard', icon: LayoutDashboard },
        ],
      },
      {
        title: 'Logistik & Inventori',
        items: [
          { label: 'Data Barang', path: '/warehouse/products', icon: Boxes },
          { label: 'Stok & Mutasi', path: '/warehouse/inventory', icon: Package },
          { label: 'Pengajuan Kebutuhan', path: '/warehouse/requests', icon: ClipboardList },
          { label: 'Pemenuhan Barang', path: '/warehouse/fulfillment', icon: CheckCircle2 },
          { label: 'Pengiriman', path: '/warehouse/shipments', icon: Truck },
          { label: 'Surat Jalan Gudang', path: '/warehouse/delivery-notes', icon: FileText },
        ],
      },
      {
        title: 'Akun',
        items: [
          { label: 'Notifikasi', path: '/warehouse/notifications', icon: Bell, badge: unreadCount.value || null },
          { label: 'Profile', path: '/warehouse/profile', icon: ShieldCheck },
        ],
      },
    ];
  }

  if (role === 'FINANCE') {
    return [
      {
        title: 'Utama',
        items: [
          { label: 'Dashboard', path: '/finance/dashboard', icon: LayoutDashboard },
        ],
      },
      {
        title: 'Keuangan & Pembayaran',
        items: [
          { label: 'Transaksi Pembelian', path: '/finance/purchases', icon: ShoppingBag },
          { label: 'Invoice', path: '/finance/invoices', icon: Receipt },
          { label: 'Pembayaran (Penuh & Termin)', path: '/finance/payments', icon: CreditCard },
          { label: 'Monitoring Keuangan', path: '/finance/monitoring', icon: Clock },
          { label: 'Laporan Keuangan', path: '/finance/reports', icon: BarChart3 },
        ],
      },
      {
        title: 'Akun',
        items: [
          { label: 'Notifikasi', path: '/finance/notifications', icon: Bell, badge: unreadCount.value || null },
          { label: 'Profile', path: '/finance/profile', icon: ShieldCheck },
        ],
      },
    ];
  }

  if (role === 'CLIENT') {
    return [
      {
        title: 'Utama',
        items: [
          { label: 'Dashboard', path: '/client/dashboard', icon: LayoutDashboard },
        ],
      },
      {
        title: 'Layanan Rekanan',
        items: [
          { label: 'Pembayaran', path: '/client/payments', icon: CreditCard },
          { label: 'Tracking Pesanan', path: '/client/orders', icon: Truck },
        ],
      },
      {
        title: 'Akun',
        items: [
          { label: 'Notifikasi', path: '/client/notifications', icon: Bell, badge: unreadCount.value || null },
          { label: 'Profile', path: '/client/profile', icon: ShieldCheck },
        ],
      },
    ];
  }

  return [];
});

const onSelectRole = (newRoleKey) => {
  storeActions.switchRole(newRoleKey);
  roleDropdownOpen.value = false;
  toast.success(`Beralih ke peran: ${roleLabel.value}`);

  // Navigate to appropriate role dashboard
  const targetMap = {
    MARKETING: '/marketing/dashboard',
    DIREKTUR: '/director/dashboard',
    ADMINISTRASI: '/administration/dashboard',
    WAREHOUSE: '/warehouse/dashboard',
    FINANCE: '/finance/dashboard',
    CLIENT: '/client/dashboard',
  };
  router.push(targetMap[newRoleKey] || '/marketing/dashboard');
};

const getNotificationRoute = () => {
  const r = store.currentUser.role.toLowerCase();
  return `/${r}/notifications`;
};

const getProfileRoute = () => {
  const r = store.currentUser.role.toLowerCase();
  return `/${r}/profile`;
};

const handleNotificationClick = (notif) => {
  notif.isRead = true;
  notificationOpen.value = false;
  if (notif.link) {
    router.push(notif.link);
  }
};

const handleLogout = () => {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  toast.info('Anda telah keluar dari sistem.');
  router.push('/login');
};
</script>
