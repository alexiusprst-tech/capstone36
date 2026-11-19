<template>
  <div class="app-shell ui-scope min-h-screen flex flex-col font-sans text-slate-800"
    @keydown.esc="mobileSidebarOpen = false; notificationOpen = false"
    @click="closeOutsidePopover">
    <a href="#page-content" class="skip-link">Lewati ke konten</a>
    <ToastContainer />
    <div v-if="mobileSidebarOpen" class="fixed inset-0 z-40 bg-slate-950/30 backdrop-blur-xs lg:hidden" @click="mobileSidebarOpen = false"></div>

    <aside id="app-sidebar" :data-collapsed="sidebarCollapsed" :class="[
      sidebarCollapsed ? 'w-64 lg:w-20' : 'w-64',
      mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
      'app-sidebar fixed inset-y-0 left-0 z-50 flex flex-col transition-all duration-300 ease-in-out'
    ]" aria-label="Navigasi utama">
      <div class="sidebar-brand flex items-center justify-between gap-2 px-4 shrink-0">
        <AlpharoLogo :collapsed="sidebarCollapsed && !mobileSidebarOpen" theme="dark" size="sm" :show-tagline="!sidebarCollapsed || mobileSidebarOpen" />
        <button type="button" class="sidebar-toggle hidden lg:flex p-1.5 rounded-md text-slate-400 hover:text-alpharo-600 hover:bg-alpharo-50"
          :title="sidebarCollapsed ? 'Buka Sidebar' : 'Ciutkan Sidebar'" :aria-label="sidebarCollapsed ? 'Buka sidebar' : 'Ciutkan sidebar'"
          :aria-expanded="!sidebarCollapsed" aria-controls="app-sidebar" @click="sidebarCollapsed = !sidebarCollapsed">
          <component :is="sidebarCollapsed ? ChevronRight : ChevronLeft" class="w-4 h-4" />
        </button>
        <button type="button" class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Tutup navigasi" @click="mobileSidebarOpen = false"><X class="w-5 h-5" /></button>
      </div>
      <nav class="flex-1 overflow-y-auto px-3 py-5 space-y-6">
        <div v-for="(group, gIdx) in navGroups" :key="gIdx" class="space-y-1">
          <p v-if="(!sidebarCollapsed || mobileSidebarOpen) && group.title" class="px-3 mb-2 text-[10px] font-medium tracking-widest uppercase text-slate-400">{{ group.title }}</p>
          <Link v-for="item in group.items" :key="item.path" :href="item.path" @click="mobileSidebarOpen = false" :aria-current="isActivePath(item.path) ? 'page' : undefined"
              :class="['sidebar-link group flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] transition-colors', isActivePath(item.path) ? 'sidebar-link-active' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100', sidebarCollapsed && !mobileSidebarOpen ? 'justify-center' : '']"
              :title="item.label">
              <component :is="item.icon" :class="['w-[18px] h-[18px] shrink-0', isActivePath(item.path) ? 'text-alpharo-600' : 'text-slate-400 group-hover:text-slate-600']" :stroke-width="1.7" />
              <span v-if="!sidebarCollapsed || mobileSidebarOpen" class="min-w-0 flex-1 leading-snug">{{ item.label }}</span>
              <span v-if="(!sidebarCollapsed || mobileSidebarOpen) && item.badge" class="ml-auto min-w-5 h-5 px-1.5 inline-flex items-center justify-center text-[10px] font-semibold rounded-md bg-rose-50 text-rose-600">{{ item.badge }}</span>
          </Link>
        </div>
      </nav>
      <div class="sidebar-account p-3 shrink-0">
        <div class="flex items-center gap-2.5 rounded-lg px-2 py-2 bg-white border border-slate-200/70">
          <Link :href="getProfileRoute()" class="shrink-0" title="Profil Saya"><img :src="store.currentUser.avatar" alt="Foto profil" class="w-8 h-8 rounded-full object-cover ring-1 ring-slate-200" /></Link>
          <div v-if="!sidebarCollapsed || mobileSidebarOpen" class="min-w-0 flex-1">
            <p class="text-xs font-medium text-slate-800 truncate">{{ store.currentUser.name }}</p>
            <p class="text-[10px] text-slate-400 truncate">{{ roleLabel }}</p>
          </div>
          <button v-if="!sidebarCollapsed || mobileSidebarOpen" type="button" class="p-2 rounded-md text-slate-400 hover:text-rose-600 hover:bg-rose-50" title="Logout" aria-label="Keluar dari sistem" @click="handleLogout"><LogOut class="w-4 h-4" /></button>
        </div>
        <button v-if="sidebarCollapsed && !mobileSidebarOpen" type="button" class="mt-2 flex w-full justify-center p-2 rounded-md text-slate-400 hover:text-rose-600 hover:bg-rose-50" title="Logout" aria-label="Keluar dari sistem" @click="handleLogout"><LogOut class="w-4 h-4" /></button>
      </div>
    </aside>

    <div :class="[sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64', 'flex-1 flex flex-col min-w-0 transition-all duration-300']">
      <header class="app-topbar sticky top-0 z-30 flex items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 min-w-0">
          <button type="button" class="lg:hidden p-2 -ml-2 rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Buka navigasi" :aria-expanded="mobileSidebarOpen" aria-controls="app-sidebar" @click="mobileSidebarOpen = true"><Menu class="w-5 h-5" /></button>
          <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-slate-400 min-w-0">
            <span class="hidden xl:inline">Alpharo</span><ChevronRight class="hidden xl:block w-3 h-3 shrink-0" />
            <span class="hidden md:inline whitespace-nowrap">{{ roleLabel }}</span><ChevronRight class="hidden md:block w-3 h-3 shrink-0" />
            <span class="text-slate-700 font-medium truncate">{{ pageTitle }}</span>
          </nav>
        </div>
        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
          <Link href="/portal" class="hidden xl:inline-flex items-center gap-1.5 px-3 py-2 text-alpharo-700 bg-alpharo-50 rounded-lg text-xs font-medium border border-alpharo-100 hover:bg-alpharo-100 transition-colors" title="Buka Portal Publik Rekanan"><Globe class="w-3.5 h-3.5" /><span>Portal Publik ↗</span></Link>
          <span class="hidden sm:inline-flex items-center gap-2 px-3 py-2 text-xs text-slate-600" aria-label="Peran akun aktif">
            <ShieldCheck class="w-3.5 h-3.5 text-alpharo-600" />Peran: {{ roleLabel }}
          </span>
          <div class="relative" data-popover>
            <button type="button" class="relative p-2 text-slate-500 rounded-lg hover:bg-slate-100 transition-colors flex items-center justify-center" title="Pusat Notifikasi Real-time" aria-label="Buka pusat notifikasi" :aria-expanded="notificationOpen" aria-controls="notification-panel" @click="notificationOpen = !notificationOpen">
              <Bell :class="['w-[18px] h-[18px]', isBellPulsing ? 'text-alpharo-600 animate-bounce' : '']" />
              <span v-if="unreadCount > 0" :class="['absolute -top-0.5 -right-0.5 min-w-4 h-4 px-1 bg-rose-500 text-white text-[9px] font-semibold rounded-full flex items-center justify-center ring-2 ring-white', isBellPulsing ? 'animate-pulse' : '']">{{ unreadCount }}</span>
            </button>
            <div v-if="notificationOpen" id="notification-panel" class="ui-popover notification-popover absolute right-0 mt-3 w-80 sm:w-96 bg-white rounded-xl border border-slate-200 z-50 overflow-hidden">
              <div class="px-4 py-3 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2"><h4 class="text-xs font-semibold text-slate-900">Notifikasi</h4><span class="bg-alpharo-50 text-alpharo-700 text-[10px] font-medium px-1.5 py-0.5 rounded-md">{{ unreadCount }} Baru</span></div>
                <div class="flex items-center gap-3"><button v-if="unreadCount > 0" type="button" class="text-[11px] text-alpharo-600 hover:underline font-medium" @click="markAllAsRead">Tandai Dibaca</button><Link :href="getNotificationRoute()" class="text-[11px] text-slate-500 hover:text-alpharo-600" @click="notificationOpen = false">Lihat Semua</Link></div>
              </div>
              <div class="max-h-96 overflow-y-auto divide-y divide-slate-100">
                <button v-for="notif in roleNotifications.slice(0, 8)" :key="notif.id" type="button" :class="[notif.isRead ? 'bg-white' : 'bg-alpharo-50/40', 'w-full text-left p-4 hover:bg-slate-50 transition-colors']" @click="handleNotificationClick(notif)">
                  <div class="flex items-start justify-between gap-2"><div class="flex items-center gap-1.5 min-w-0"><span v-if="!notif.isRead" class="w-1.5 h-1.5 rounded-full bg-alpharo-600 shrink-0"></span><p class="text-xs font-medium text-slate-900 leading-snug truncate">{{ notif.title }}</p></div><span class="text-[10px] text-slate-400 whitespace-nowrap">{{ notif.time }}</span></div><p class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed">{{ notif.message }}</p>
                </button>
                <div v-if="roleNotifications.length === 0" class="p-8 text-center text-xs text-slate-400">Tidak ada notifikasi aktif untuk peran ini.</div>
              </div>
            </div>
          </div>
          <Link :href="getProfileRoute()" class="flex items-center gap-2.5 pl-3 border-l border-slate-200" title="Profil Saya"><img :src="store.currentUser.avatar" alt="Avatar" class="w-8 h-8 rounded-full object-cover ring-1 ring-slate-200" /><div class="hidden 2xl:flex flex-col text-left"><span class="text-xs font-medium text-slate-800">{{ store.currentUser.name }}</span><span class="text-[10px] text-slate-400">{{ roleLabel }}</span></div></Link>
        </div>
      </header>
      <main id="page-content" tabindex="-1" class="app-content flex-1 p-4 sm:p-6 lg:p-7 xl:p-8 max-w-[1600px] w-full mx-auto">
        <div v-if="$slots.header" class="page-heading mb-6"><slot name="header"></slot></div>
        <p v-if="orderSync.error" role="status" class="mb-4 flex flex-wrap items-center gap-2 text-xs text-amber-700">
          {{ orderSync.error }}
          <button type="button" class="font-semibold underline" :disabled="orderSync.loading" @click="storeActions.loadOrders()">Muat ulang</button>
        </p>
        <slot></slot>
      </main>
      <footer class="app-footer mt-auto px-4 sm:px-6 lg:px-8 py-4 text-[11px] text-slate-400 flex flex-col sm:flex-row items-center justify-between gap-2"><span>&copy; 2026 CV Alpharo Artha Jaya — Sistem Informasi Terintegrasi Enterprise</span><div class="flex items-center gap-3"><span>Versi pengembangan</span><span>·</span><span class="flex items-center gap-1.5" :class="orderSync.error ? 'text-amber-700' : 'text-slate-500'"><span class="w-1.5 h-1.5 rounded-full" :class="orderSync.error ? 'bg-amber-500' : 'bg-slate-400'"></span>{{ orderSync.error ? 'Sinkronisasi terganggu' : 'Sesi aktif' }}</span></div></footer>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { onRealtimeNotification } from '../../services/realtime';
import { useRouter, useRoute } from '../../navigation';
import { store, storeActions, orderSync } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import { logout } from '../../services/session';
import ToastContainer from '../common/ToastContainer.vue';
import AlpharoLogo from '../common/AlpharoLogo.vue';

// Lucide Icons
import {
  LayoutDashboard,
  Users,
  ShoppingCart,
  ShoppingBag,
  FileText,
  Package,
  Boxes,
  Truck,
  CreditCard,
  Receipt,
  CheckCircle2,
  Clock,
  ShieldCheck,
  Bell,
  LogOut,
  ChevronLeft,
  ChevronRight,
  Menu,
  X,
  Activity,
  Layers,
  BarChart3,
  ClipboardList,
  FolderCheck,
  Globe,
} from 'lucide-vue-next';

const router = useRouter();
const route = useRoute();
const isActivePath = path => route.path === path || route.path.startsWith(path + '/');
const toast = useToast();
const closeOutsidePopover = event => { if (!event.target.closest('[data-popover]')) notificationOpen.value = false; };

const sidebarCollapsed = ref(false);
const mobileSidebarOpen = ref(false);
const notificationOpen = ref(false);
const isBellPulsing = ref(false);
let unsubscribeRealtime = null;
let orderRefreshTimer = null;
const refreshOrders = () => {
  if (document.visibilityState === 'visible' && ['FINANCE', 'SUPPLIER', 'WAREHOUSE'].includes(store.currentUser?.role)) storeActions.loadPurchases().catch(() => {});
  if (!orderSync.busy && document.visibilityState === 'visible') storeActions.loadOrders();
};

onMounted(() => {
  storeActions.loadClients();
  refreshOrders();
  window.addEventListener('focus', refreshOrders);
  orderRefreshTimer = window.setInterval(refreshOrders, 30000);
  unsubscribeRealtime = onRealtimeNotification((notif, isTarget) => {
    if (isTarget) {
      isBellPulsing.value = true;
      setTimeout(() => {
        isBellPulsing.value = false;
      }, 4500);
    }
  });
});

onUnmounted(() => {
  window.removeEventListener('focus', refreshOrders);
  window.clearInterval(orderRefreshTimer);
  if (unsubscribeRealtime) unsubscribeRealtime();
});

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

// Dynamic Role Activity Badge Counters
const marketingPendingOrdersCount = computed(() => {
  return (store.orders || []).filter((o) => o.status === 'Diajukan Client').length;
});

const waitingApprovalCount = computed(() => {
  return (store.orders || []).filter((o) => o.status === 'Menunggu Verifikasi').length;
});

const adminPendingSpjCount = computed(() => {
  return (store.orders || []).filter((o) => {
    if (o.status !== 'Disetujui' && o.status !== 'Diproses') return false;
    const hasPo = o.documents?.po?.status === 'Final' || o.documents?.po?.status === 'Terbit';
    const hasSj = o.documents?.suratJalan?.status === 'Final' || o.documents?.suratJalan?.status === 'Terbit';
    const hasBast = o.documents?.bast?.status === 'Final' || o.documents?.bast?.status === 'Terbit';
    const hasFaktur = o.documents?.faktur?.status === 'Final' || o.documents?.faktur?.status === 'Terbit';
    return !(hasPo && hasSj && hasBast && hasFaktur);
  }).length;
});

const warehouseActionCount = computed(() => {
  const reqs = (store.warehouseRequests || []).filter(
    (r) => r.status === 'MENUNGGU_FINANCE' || r.status === 'Draft' || r.status === 'Menunggu Verifikasi'
  ).length;
  const incoming = (store.purchases || []).filter(
    (p) => p.status === 'Dikirim Supplier' || p.delivery_info?.status === 'Dalam Pengiriman'
  ).length;
  const fulfill = (store.warehouseFulfillments || []).filter((f) => f.status === 'Diproses').length;
  return reqs + incoming + fulfill;
});

const financePendingRequestsCount = computed(() => {
  return (store.warehouseRequests || []).filter(
    (r) => r.status === 'MENUNGGU_FINANCE' || r.status === 'Draft' || r.status === 'Menunggu Verifikasi'
  ).length;
});

const financeInvoiceBadgeCount = computed(() => {
  const readyCount = (store.orders || []).filter((o) => o.serverManaged && o.status === 'Siap Invoice').length;
  const overdueCount = (store.invoices || []).filter((i) => i.status === 'Jatuh Tempo').length;
  return (readyCount + overdueCount) || null;
});

const unreadCount = computed(() => {
  return roleNotifications.value.filter((n) => !n.isRead).length;
});

// Sidebar Navigation Items Per Role
const navGroups = computed(() => {
  const role = store.currentUser.role;

  // 1. MARKETING
  if (role === 'MARKETING') {
    return [
      {
        title: 'Utama',
        items: [
          { label: 'Dashboard Marketing', path: '/marketing/dashboard', icon: LayoutDashboard },
        ],
      },
      {
        title: 'Operasional Pemasaran',
        items: [
          { label: 'Data Customer / Klien', path: '/marketing/clients', icon: Users },
          { label: 'Manajemen Order Proyek', path: '/marketing/orders', icon: ShoppingCart, badge: marketingPendingOrdersCount.value || null },
        ],
      },
      {
        title: 'Akun',
        items: [
          { label: 'Profile', path: '/marketing/profile', icon: ShieldCheck },
        ],
      },
    ];
  }

  // 2. DIREKTUR (4 MENU UTAMA)
  if (role === 'DIREKTUR') {
    return [
      {
        title: 'Utama & Otoritas',
        items: [
          { label: 'Dashboard Eksekutif', path: '/director/dashboard', icon: LayoutDashboard },
          { label: 'Persetujuan Order', path: '/director/verification', icon: ShieldCheck, badge: waitingApprovalCount.value || null },
        ],
      },
      {
        title: 'Pengawasan & Audit',
        items: [
          { label: 'Monitoring Operasional', path: '/director/monitoring', icon: Layers },
          { label: 'Laporan & Audit', path: '/director/reports', icon: BarChart3 },
        ],
      },
      {
        title: 'Akun',
        items: [
          { label: 'Profile', path: '/director/profile', icon: ShieldCheck },
        ],
      },
    ];
  }

  // 3. ADMINISTRASI (3 MENU UTAMA)
  if (role === 'ADMINISTRASI') {
    return [
      {
        title: 'Utama',
        items: [
          { label: 'Dashboard Administrasi', path: '/administration/dashboard', icon: LayoutDashboard },
          { label: 'Dokumen SPJ Proyek', path: '/administration/spj', icon: FolderCheck, badge: adminPendingSpjCount.value || null },
          { label: 'Kelola Akun Pengguna', path: '/administration/accounts', icon: Users },
        ],
      },
      {
        title: 'Akun',
        items: [
          { label: 'Profile', path: '/administration/profile', icon: ShieldCheck },
        ],
      },
    ];
  }

  // 4. WAREHOUSE / GUDANG (4 MENU UTAMA)
  if (role === 'WAREHOUSE') {
    return [
      {
        title: 'Utama',
        items: [
          { label: 'Dashboard Gudang', path: '/warehouse/dashboard', icon: LayoutDashboard },
        ],
      },
      {
        title: 'Logistik & Persediaan',
        items: [
          { label: 'Katalog & Stok Barang', path: '/warehouse/products', icon: Boxes },
          { label: 'Pengadaan & Penerimaan', path: '/warehouse/fulfillment', icon: CheckCircle2, badge: warehouseActionCount.value || null },
          { label: 'Pengiriman Armada', path: '/warehouse/shipments', icon: Truck },
        ],
      },
      {
        title: 'Akun',
        items: [
          { label: 'Profile', path: '/warehouse/profile', icon: ShieldCheck },
        ],
      },
    ];
  }

  // 5. FINANCE (4 MENU UTAMA)
  if (role === 'FINANCE') {
    return [
      {
        title: 'Utama',
        items: [
          { label: 'Dashboard Keuangan', path: '/finance/dashboard', icon: LayoutDashboard },
        ],
      },
      {
        title: 'Keuangan & Pembayaran',
        items: [
          { label: 'Pembelian & Supplier', path: '/finance/purchases', icon: ShoppingBag, badge: financePendingRequestsCount.value || null },
          { label: 'Invoice Tagihan Customer', path: '/finance/invoices', icon: Receipt, badge: financeInvoiceBadgeCount.value },
          { label: 'Penerimaan Kas & Laporan', path: '/finance/payments', icon: CreditCard },
        ],
      },
      {
        title: 'Akun',
        items: [
          { label: 'Profile', path: '/finance/profile', icon: ShieldCheck },
        ],
      },
    ];
  }

  // 6. CLIENT (PORTAL PUBLIK)
  if (role === 'CLIENT') {
    return [
      {
        title: 'Portal Terbuka',
        items: [
          { label: 'Portal Publik Rekanan', path: '/portal', icon: Layers },
        ],
      },
    ];
  }

  // 7. SUPPLIER (PORTAL VENDOR)
  if (role === 'SUPPLIER') {
    return [
      {
        title: 'Portal Vendor & Supplier',
        items: [
          { label: 'Portal Vendor Rekanan', path: '/supplier/portal', icon: Truck },
        ],
      },
      {
        title: 'Akun',
        items: [
          { label: 'Profile', path: '/supplier/profile', icon: ShieldCheck },
        ],
      },
    ];
  }

  return [];
});

const getNotificationRoute = () => {
  const r = store.currentUser.role.toLowerCase();
  return `/${r}/notifications`;
};

const getProfileRoute = () => {
  const r = store.currentUser.role.toLowerCase();
  return `/${r}/profile`;
};

const markAllAsRead = () => {
  roleNotifications.value.forEach((n) => { n.isRead = true; });
  toast.success('Semua notifikasi telah ditandai dibaca.');
};

const handleNotificationClick = (notif) => {
  notif.isRead = true;
  notificationOpen.value = false;
  if (notif.link) {
    router.push(notif.link);
  }
};

const handleLogout = async () => {
  try {
    await logout();
  } catch (error) {
    toast.error(error.response?.data?.message || 'Sesi tidak dapat ditutup di server.');
  }
};

</script>
