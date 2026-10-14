import { createRouter, createWebHistory } from 'vue-router';
import { store, storeActions } from '../stores/mockData';

// Auth Views
import LoginView from '../views/auth/LoginView.vue';
import RegisterView from '../views/RegisterView.vue';

// Marketing Views
import MarketingDashboard from '../views/marketing/MarketingDashboard.vue';
import MarketingClients from '../views/marketing/MarketingClients.vue';
import ClientFormView from '../views/marketing/ClientFormView.vue';
import MarketingOrders from '../views/marketing/MarketingOrders.vue';
import OrderFormView from '../views/marketing/OrderFormView.vue';
import MarketingOrderMonitoring from '../views/marketing/MarketingOrderMonitoring.vue';

// Direktur Views
import DirectorDashboard from '../views/director/DirectorDashboard.vue';
import DirectorVerification from '../views/director/DirectorVerification.vue';
import DirectorMonitoringOrders from '../views/director/DirectorMonitoringOrders.vue';
import DirectorMonitoringWarehouse from '../views/director/DirectorMonitoringWarehouse.vue';
import DirectorMonitoringAdministration from '../views/director/DirectorMonitoringAdministration.vue';
import DirectorMonitoringFinance from '../views/director/DirectorMonitoringFinance.vue';
import DirectorReports from '../views/director/DirectorReports.vue';
import DirectorActivities from '../views/director/DirectorActivities.vue';

// Administrasi Views
import AdministrationDashboard from '../views/administration/AdministrationDashboard.vue';
import AdministrationOrders from '../views/administration/AdministrationOrders.vue';
import AdministrationPurchaseOrders from '../views/administration/AdministrationPurchaseOrders.vue';
import AdministrationDeliveryNotes from '../views/administration/AdministrationDeliveryNotes.vue';
import AdministrationBast from '../views/administration/AdministrationBast.vue';
import AdministrationInvoices from '../views/administration/AdministrationInvoices.vue';
import AdministrationDocuments from '../views/administration/AdministrationDocuments.vue';
import AdministrationMonitoring from '../views/administration/AdministrationMonitoring.vue';
import AdministrationSpj from '../views/administration/AdministrationSpj.vue';

// Warehouse Views
import WarehouseDashboard from '../views/warehouse/WarehouseDashboard.vue';
import WarehouseProducts from '../views/warehouse/WarehouseProducts.vue';
import WarehouseInventory from '../views/warehouse/WarehouseInventory.vue';
import WarehouseRequests from '../views/warehouse/WarehouseRequests.vue';
import WarehouseFulfillment from '../views/warehouse/WarehouseFulfillment.vue';
import WarehouseShipments from '../views/warehouse/WarehouseShipments.vue';
import WarehouseDeliveryNotes from '../views/warehouse/WarehouseDeliveryNotes.vue';

// Finance Views
import FinanceDashboard from '../views/finance/FinanceDashboard.vue';
import FinanceInvoices from '../views/finance/FinanceInvoices.vue';
import FinancePayments from '../views/finance/FinancePayments.vue';
import FinanceReceivables from '../views/finance/FinanceReceivables.vue';
import FinanceMonitoring from '../views/finance/FinanceMonitoring.vue';
import FinanceReports from '../views/finance/FinanceReports.vue';
import FinancePurchases from '../views/finance/FinancePurchases.vue';
import FinancePurchaseCreate from '../views/finance/FinancePurchaseCreate.vue';

// Client Views
import ClientDashboard from '../views/client/ClientDashboard.vue';
import ClientPayments from '../views/client/ClientPayments.vue';
import ClientOrderTracking from '../views/client/ClientOrderTracking.vue';

// Common Views
import NotificationsView from '../views/common/NotificationsView.vue';
import ProfileView from '../views/common/ProfileView.vue';

const routes = [
  // Root Redirect
  {
    path: '/',
    redirect: () => {
      const token = localStorage.getItem('token');
      if (!token) return '/login';
      const role = store.currentUser?.role || 'MARKETING';
      const roleMap = {
        MARKETING: '/marketing/dashboard',
        DIREKTUR: '/director/dashboard',
        ADMINISTRASI: '/administration/dashboard',
        WAREHOUSE: '/warehouse/dashboard',
        FINANCE: '/finance/dashboard',
        CLIENT: '/client/dashboard',
      };
      return roleMap[role] || '/marketing/dashboard';
    },
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { title: 'Masuk ke Sistem', guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { title: 'Daftar Akun' },
  },

  // 1. MARKETING ROUTES
  {
    path: '/marketing/dashboard',
    name: 'marketing-dashboard',
    component: MarketingDashboard,
    meta: { title: 'Dashboard Marketing', role: 'MARKETING', requiresAuth: true },
  },
  {
    path: '/marketing/clients',
    name: 'marketing-clients',
    component: MarketingClients,
    meta: { title: 'Data Client Rekanan', role: 'MARKETING', requiresAuth: true },
  },
  {
    path: '/marketing/clients/create',
    name: 'marketing-clients-create',
    component: ClientFormView,
    meta: { title: 'Tambah Data Client', role: 'MARKETING', requiresAuth: true },
  },
  {
    path: '/marketing/clients/:id/edit',
    name: 'marketing-clients-edit',
    component: ClientFormView,
    meta: { title: 'Edit Data Client', role: 'MARKETING', requiresAuth: true },
  },
  {
    path: '/marketing/orders',
    name: 'marketing-orders',
    component: MarketingOrders,
    meta: { title: 'Kelola Order Proyek', role: 'MARKETING', requiresAuth: true },
  },
  {
    path: '/marketing/orders/create',
    name: 'marketing-orders-create',
    component: OrderFormView,
    meta: { title: 'Tambah Order & Proyek Baru', role: 'MARKETING', requiresAuth: true },
  },
  {
    path: '/marketing/orders/:id/edit',
    name: 'marketing-orders-edit',
    component: OrderFormView,
    meta: { title: 'Edit Order & Proyek', role: 'MARKETING', requiresAuth: true },
  },
  {
    path: '/marketing/orders/monitoring',
    name: 'marketing-order-monitoring',
    component: MarketingOrderMonitoring,
    meta: { title: 'Monitoring Order', role: 'MARKETING', requiresAuth: true },
  },
  {
    path: '/marketing/notifications',
    name: 'marketing-notifications',
    component: NotificationsView,
    meta: { title: 'Notifikasi Marketing', role: 'MARKETING', requiresAuth: true },
  },
  {
    path: '/marketing/profile',
    name: 'marketing-profile',
    component: ProfileView,
    meta: { title: 'Profil Marketing', role: 'MARKETING', requiresAuth: true },
  },

  // 2. DIREKTUR ROUTES
  {
    path: '/director/dashboard',
    name: 'director-dashboard',
    component: DirectorDashboard,
    meta: { title: 'Dashboard Direktur', role: 'DIREKTUR', requiresAuth: true },
  },
  {
    path: '/director/verification',
    name: 'director-verification',
    component: DirectorVerification,
    meta: { title: 'Verifikasi & Approval', role: 'DIREKTUR', requiresAuth: true },
  },
  {
    path: '/director/orders',
    name: 'director-orders',
    component: DirectorMonitoringOrders,
    meta: { title: 'Monitoring Order Eksekutif', role: 'DIREKTUR', requiresAuth: true },
  },
  {
    path: '/director/warehouse',
    name: 'director-warehouse',
    component: DirectorMonitoringWarehouse,
    meta: { title: 'Monitoring Gudang & Logistik', role: 'DIREKTUR', requiresAuth: true },
  },
  {
    path: '/director/administration',
    name: 'director-administration',
    component: DirectorMonitoringAdministration,
    meta: { title: 'Monitoring Dokumen Administrasi', role: 'DIREKTUR', requiresAuth: true },
  },
  {
    path: '/director/finance',
    name: 'director-finance',
    component: DirectorMonitoringFinance,
    meta: { title: 'Monitoring Keuangan & Piutang', role: 'DIREKTUR', requiresAuth: true },
  },
  {
    path: '/director/reports',
    name: 'director-reports',
    component: DirectorReports,
    meta: { title: 'Laporan Eksekutif', role: 'DIREKTUR', requiresAuth: true },
  },
  {
    path: '/director/activities',
    name: 'director-activities',
    component: DirectorActivities,
    meta: { title: 'Activity Audit Log', role: 'DIREKTUR', requiresAuth: true },
  },
  {
    path: '/director/notifications',
    name: 'director-notifications',
    component: NotificationsView,
    meta: { title: 'Notifikasi Direktur', role: 'DIREKTUR', requiresAuth: true },
  },
  {
    path: '/director/profile',
    name: 'director-profile',
    component: ProfileView,
    meta: { title: 'Profil Direktur', role: 'DIREKTUR', requiresAuth: true },
  },

  // 3. ADMINISTRASI ROUTES
  {
    path: '/administration/dashboard',
    name: 'administration-dashboard',
    component: AdministrationDashboard,
    meta: { title: 'Dashboard Administrasi', role: 'ADMINISTRASI', requiresAuth: true },
  },
  {
    path: '/administration/orders',
    redirect: '/administration/spj',
  },
  {
    path: '/administration/spj',
    name: 'administration-spj',
    component: AdministrationSpj,
    meta: { title: 'Dokumen SPJ (Surat Pertanggungjawaban)', role: 'ADMINISTRASI', requiresAuth: true },
  },
  {
    path: '/administration/purchase-orders',
    redirect: '/administration/spj',
  },
  {
    path: '/administration/delivery-notes',
    redirect: '/administration/spj',
  },
  {
    path: '/administration/bast',
    redirect: '/administration/spj',
  },
  {
    path: '/administration/invoices',
    redirect: '/administration/spj',
  },
  {
    path: '/administration/documents',
    redirect: '/administration/spj',
  },
  {
    path: '/administration/monitoring',
    redirect: '/administration/spj',
  },
  {
    path: '/administration/notifications',
    name: 'administration-notifications',
    component: NotificationsView,
    meta: { title: 'Notifikasi Administrasi', role: 'ADMINISTRASI', requiresAuth: true },
  },
  {
    path: '/administration/profile',
    name: 'administration-profile',
    component: ProfileView,
    meta: { title: 'Profil Administrasi', role: 'ADMINISTRASI', requiresAuth: true },
  },

  // 4. WAREHOUSE ROUTES
  {
    path: '/warehouse/dashboard',
    name: 'warehouse-dashboard',
    component: WarehouseDashboard,
    meta: { title: 'Dashboard Warehouse', role: 'WAREHOUSE', requiresAuth: true },
  },
  {
    path: '/warehouse/products',
    name: 'warehouse-products',
    component: WarehouseProducts,
    meta: { title: 'Data Barang & Material', role: 'WAREHOUSE', requiresAuth: true },
  },
  {
    path: '/warehouse/inventory',
    name: 'warehouse-inventory',
    component: WarehouseInventory,
    meta: { title: 'Stok & Mutasi Gudang', role: 'WAREHOUSE', requiresAuth: true },
  },
  {
    path: '/warehouse/requests',
    name: 'warehouse-requests',
    component: WarehouseRequests,
    meta: { title: 'Pengajuan Kebutuhan Barang', role: 'WAREHOUSE', requiresAuth: true },
  },
  {
    path: '/warehouse/fulfillment',
    name: 'warehouse-fulfillment',
    component: WarehouseFulfillment,
    meta: { title: 'Pemenuhan Barang', role: 'WAREHOUSE', requiresAuth: true },
  },
  {
    path: '/warehouse/shipments',
    name: 'warehouse-shipments',
    component: WarehouseShipments,
    meta: { title: 'Pengiriman Armada', role: 'WAREHOUSE', requiresAuth: true },
  },
  {
    path: '/warehouse/delivery-notes',
    name: 'warehouse-delivery-notes',
    component: WarehouseDeliveryNotes,
    meta: { title: 'Surat Jalan Gudang', role: 'WAREHOUSE', requiresAuth: true },
  },
  {
    path: '/warehouse/notifications',
    name: 'warehouse-notifications',
    component: NotificationsView,
    meta: { title: 'Notifikasi Warehouse', role: 'WAREHOUSE', requiresAuth: true },
  },
  {
    path: '/warehouse/profile',
    name: 'warehouse-profile',
    component: ProfileView,
    meta: { title: 'Profil Warehouse', role: 'WAREHOUSE', requiresAuth: true },
  },

  // 5. FINANCE ROUTES
  {
    path: '/finance/dashboard',
    name: 'finance-dashboard',
    component: FinanceDashboard,
    meta: { title: 'Dashboard Finance', role: 'FINANCE', requiresAuth: true },
  },
  {
    path: '/finance/purchases',
    name: 'finance-purchases',
    component: FinancePurchases,
    meta: { title: 'Transaksi Pembelian (Purchasing)', role: 'FINANCE', requiresAuth: true },
  },
  {
    path: '/finance/purchases/create',
    name: 'finance-purchases-create',
    component: FinancePurchaseCreate,
    meta: { title: 'Tambah Transaksi Pembelian', role: 'FINANCE', requiresAuth: true },
  },
  {
    path: '/finance/invoices',
    name: 'finance-invoices',
    component: FinanceInvoices,
    meta: { title: 'Tagihan Invoice', role: 'FINANCE', requiresAuth: true },
  },
  {
    path: '/finance/payments',
    name: 'finance-payments',
    component: FinancePayments,
    meta: { title: 'Pembayaran (Penuh & Termin)', role: 'FINANCE', requiresAuth: true },
  },
  {
    path: '/finance/receivables',
    redirect: '/finance/monitoring',
  },
  {
    path: '/finance/monitoring',
    name: 'finance-monitoring',
    component: FinanceMonitoring,
    meta: { title: 'Monitoring Finansial', role: 'FINANCE', requiresAuth: true },
  },
  {
    path: '/finance/reports',
    name: 'finance-reports',
    component: FinanceReports,
    meta: { title: 'Laporan Keuangan', role: 'FINANCE', requiresAuth: true },
  },
  {
    path: '/finance/notifications',
    name: 'finance-notifications',
    component: NotificationsView,
    meta: { title: 'Notifikasi Finance', role: 'FINANCE', requiresAuth: true },
  },
  {
    path: '/finance/profile',
    name: 'finance-profile',
    component: ProfileView,
    meta: { title: 'Profil Finance', role: 'FINANCE', requiresAuth: true },
  },

  // 6. CLIENT / SEKOLAH ROUTES
  {
    path: '/client/dashboard',
    name: 'client-dashboard',
    component: ClientDashboard,
    meta: { title: 'Dashboard Rekanan Client', role: 'CLIENT', requiresAuth: true },
  },
  {
    path: '/client/payments',
    name: 'client-payments',
    component: ClientPayments,
    meta: { title: 'Pembayaran (Penuh & Termin)', role: 'CLIENT', requiresAuth: true },
  },
  {
    path: '/client/orders',
    name: 'client-orders',
    component: ClientOrderTracking,
    meta: { title: 'Tracking Pesanan', role: 'CLIENT', requiresAuth: true },
  },
  {
    path: '/client/tracking',
    redirect: '/client/orders',
  },
  {
    path: '/client/notifications',
    name: 'client-notifications',
    component: NotificationsView,
    meta: { title: 'Notifikasi Rekanan Client', role: 'CLIENT', requiresAuth: true },
  },
  {
    path: '/client/profile',
    name: 'client-profile',
    component: ProfileView,
    meta: { title: 'Profil Rekanan Client', role: 'CLIENT', requiresAuth: true },
  },

  // Catch-all route
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token');

  // If requiresAuth and no token, redirect to login
  if (to.meta.requiresAuth && !token) {
    return next({ name: 'login', query: { redirect: to.fullPath } });
  }

  // If guestOnly and user is logged in, redirect to their role dashboard
  if (to.meta.guestOnly && token) {
    const role = store.currentUser?.role || 'MARKETING';
    const roleMap = {
      MARKETING: '/marketing/dashboard',
      DIREKTUR: '/director/dashboard',
      ADMINISTRASI: '/administration/dashboard',
      WAREHOUSE: '/warehouse/dashboard',
      FINANCE: '/finance/dashboard',
      CLIENT: '/client/dashboard',
    };
    return next(roleMap[role] || '/marketing/dashboard');
  }

  // Auto sync store role if route specifies a role
  if (to.meta.role && store.currentUser.role !== to.meta.role) {
    storeActions.switchRole(to.meta.role);
  }

  next();
});

export default router;
