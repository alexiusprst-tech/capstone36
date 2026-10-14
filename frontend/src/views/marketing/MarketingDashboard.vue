<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard Marketing</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pantau akuisisi client, status pipeline order, dan persetujuan verifikasi Direktur.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <router-link
            to="/marketing/orders/create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-sm shadow-xs transition-colors"
          >
            <Plus class="w-4 h-4" />
            <span>Buat Order Baru</span>
          </router-link>
        </div>
      </div>
    </template>

    <!-- KPI CARDS GRID -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-3 mb-6">
      <KpiCard
        title="Total Client"
        :value="totalClients"
        :icon-component="Users"
        icon-variant="blue"
        subtitle="Entitas terdaftar"
      />
      <KpiCard
        title="Client Aktif"
        :value="activeClients"
        :icon-component="Users"
        icon-variant="emerald"
        subtitle="Memiliki transaksi"
      />
      <KpiCard
        title="Total Order"
        :value="totalOrders"
        :icon-component="ShoppingCart"
        icon-variant="slate"
        subtitle="Sepanjang waktu"
      />
      <KpiCard
        title="Menunggu Verifikasi"
        :value="waitingVerification"
        :icon-component="Clock"
        icon-variant="amber"
        subtitle="Meja Direktur"
      />
      <KpiCard
        title="Disetujui"
        :value="approvedOrders"
        :icon-component="CheckCircle2"
        icon-variant="emerald"
        subtitle="Lolos approval"
      />
      <KpiCard
        title="Diproses"
        :value="inProcessOrders"
        :icon-component="Package"
        icon-variant="blue"
        subtitle="Admin & Gudang"
      />
      <KpiCard
        title="Selesai"
        :value="completedOrders"
        :icon-component="CheckCircle2"
        icon-variant="emerald"
        subtitle="Lunas & terkirim"
      />
    </div>

    <!-- MAIN TWO-COLUMN DASHBOARD GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- LEFT 2 COLS: SECTION A (ORDER TERBARU) & SECTION B (STATUS ORDER) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- SECTION A: ORDER TERBARU -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-bold text-slate-900 text-base">Order Terbaru</h3>
              <p class="text-xs text-slate-500">Daftar transaksi order terkini yang diajukan</p>
            </div>
            <router-link
              to="/marketing/orders"
              class="text-xs font-semibold text-alpharo-500 hover:underline flex items-center gap-1"
            >
              Lihat Semua Order <ChevronRight class="w-3.5 h-3.5" />
            </router-link>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50/70 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
                  <th class="py-2.5 px-3">Nomor Order</th>
                  <th class="py-2.5 px-3">Client</th>
                  <th class="py-2.5 px-3">Tanggal</th>
                  <th class="py-2.5 px-3 text-right">Nilai Order</th>
                  <th class="py-2.5 px-3 text-center">Status</th>
                  <th class="py-2.5 px-3 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-slate-50/60 transition-colors">
                  <td class="py-3 px-3 font-semibold text-slate-900 font-mono">
                    {{ order.orderNumber }}
                  </td>
                  <td class="py-3 px-3">
                    <div class="font-medium text-slate-800">{{ order.clientName }}</div>
                    <div class="text-[11px] text-slate-400 truncate max-w-xs">{{ order.projectName }}</div>
                  </td>
                  <td class="py-3 px-3 text-slate-500 whitespace-nowrap">
                    {{ order.date }}
                  </td>
                  <td class="py-3 px-3 text-right font-bold text-slate-900 whitespace-nowrap">
                    Rp {{ order.grandTotal.toLocaleString('id-ID') }}
                  </td>
                  <td class="py-3 px-3 text-center">
                    <StatusBadge :status="order.status" />
                  </td>
                  <td class="py-3 px-3 text-center">
                    <button
                      type="button"
                      class="px-2.5 py-1 text-[11px] font-semibold text-alpharo-500 hover:bg-blue-50 rounded border border-blue-200 transition-colors"
                      @click="viewOrderDetail(order)"
                    >
                      Detail
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- SECTION B: STATUS ORDER VISUALIZATION -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-bold text-slate-900 text-base">Distribusi Status Order</h3>
              <p class="text-xs text-slate-500">Persebaran siklus transaksi berdasarkan tahapan bisnis</p>
            </div>
            <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
              Total: {{ totalOrders }} Order
            </span>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div
              v-for="stat in orderStatusDist"
              :key="stat.label"
              class="p-3 rounded-lg border border-slate-100 bg-slate-50 flex flex-col justify-between"
            >
              <div class="flex items-center justify-between">
                <StatusBadge :status="stat.label" />
                <span class="text-xs font-bold text-slate-900">{{ stat.count }}</span>
              </div>
              <div class="w-full bg-slate-200 h-1.5 rounded-full mt-2.5 overflow-hidden">
                <div
                  :class="[stat.colorClass, 'h-full rounded-full transition-all duration-300']"
                  :style="{ width: `${totalOrders ? (stat.count / totalOrders) * 100 : 0}%` }"
                ></div>
              </div>
              <span class="text-[10px] text-slate-400 mt-1">
                {{ totalOrders ? Math.round((stat.count / totalOrders) * 100) : 0 }}% dari keseluruhan
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT 1 COL: SECTION C (AKTIVITAS) & SECTION D (NOTIFIKASI) -->
      <div class="space-y-6">
        <!-- SECTION D: NOTIFIKASI MARKETING -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
            <div class="flex items-center gap-2">
              <Bell class="w-4 h-4 text-alpharo-500" />
              <h3 class="font-bold text-slate-900 text-sm">Notifikasi Terkini</h3>
            </div>
            <span class="text-[10px] bg-blue-50 text-alpharo-600 font-bold px-2 py-0.5 rounded-full">
              Marketing
            </span>
          </div>

          <div class="divide-y divide-slate-100">
            <div
              v-for="notif in marketingNotifications"
              :key="notif.id"
              class="py-2.5 first:pt-0 last:pb-0 text-xs"
            >
              <div class="flex items-start justify-between gap-1">
                <p class="font-semibold text-slate-800">{{ notif.title }}</p>
                <span class="text-[10px] text-slate-400 shrink-0">{{ notif.time }}</span>
              </div>
              <p class="text-slate-600 mt-1 text-[11px] leading-relaxed">{{ notif.message }}</p>
            </div>
          </div>
        </div>

        <!-- SECTION C: AKTIVITAS TERBARU -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
            <div class="flex items-center gap-2">
              <Activity class="w-4 h-4 text-alpharo-500" />
              <h3 class="font-bold text-slate-900 text-sm">Aktivitas Terakhir</h3>
            </div>
          </div>

          <div class="space-y-3">
            <div
              v-for="act in store.activityLogs.slice(0, 5)"
              :key="act.id"
              class="text-xs flex items-start gap-2.5"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-alpharo-500 mt-1.5 shrink-0"></span>
              <div class="min-w-0">
                <p class="font-semibold text-slate-800">{{ act.action }} <span class="font-mono text-slate-500">[{{ act.target }}]</span></p>
                <p class="text-slate-500 text-[11px] mt-0.5 truncate">{{ act.details }}</p>
                <span class="text-[10px] text-slate-400">{{ act.timestamp }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ORDER DETAIL DRAWER -->
    <Drawer
      v-model="drawerOpen"
      :title="`Detail Order: ${selectedOrder?.orderNumber || ''}`"
      :subtitle="selectedOrder?.clientName || ''"
      size="lg"
    >
      <div v-if="selectedOrder" class="space-y-5 text-sm">
        <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl border border-slate-200">
          <div>
            <span class="text-xs text-slate-500">Status Saat Ini</span>
            <div class="mt-1">
              <StatusBadge :status="selectedOrder.status" />
            </div>
          </div>
          <div class="text-right">
            <span class="text-xs text-slate-500">Total Nilai Order (Termasuk PPN)</span>
            <div class="text-lg font-bold text-slate-900 font-mono mt-0.5">
              Rp {{ selectedOrder.grandTotal.toLocaleString('id-ID') }}
            </div>
          </div>
        </div>

        <div>
          <h4 class="text-xs font-bold uppercase text-slate-500 tracking-wider mb-2">Item Barang / Jasa</h4>
          <div class="border border-slate-200 rounded-lg overflow-hidden">
            <table class="w-full text-xs text-left">
              <thead class="bg-slate-50 border-b border-slate-200 font-semibold text-slate-600">
                <tr>
                  <th class="p-2.5">Barang / Jasa</th>
                  <th class="p-2.5 text-center">Qty</th>
                  <th class="p-2.5 text-right">Harga</th>
                  <th class="p-2.5 text-right">Total</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="it in selectedOrder.items" :key="it.sku">
                  <td class="p-2.5">
                    <span class="font-medium text-slate-800">{{ it.name }}</span>
                    <span class="block text-[10px] font-mono text-slate-400">{{ it.sku }}</span>
                  </td>
                  <td class="p-2.5 text-center">{{ it.qty }} {{ it.unit }}</td>
                  <td class="p-2.5 text-right">Rp {{ it.price.toLocaleString('id-ID') }}</td>
                  <td class="p-2.5 text-right font-semibold">Rp {{ it.total.toLocaleString('id-ID') }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <DocumentChecklist :documents="selectedOrder.documents" />

        <div>
          <h4 class="text-xs font-bold uppercase text-slate-500 tracking-wider mb-3">Timeline Proses</h4>
          <Timeline :timeline="selectedOrder.timeline" />
        </div>
      </div>
    </Drawer>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import KpiCard from '../../components/common/KpiCard.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import Drawer from '../../components/common/Drawer.vue';
import Timeline from '../../components/common/Timeline.vue';
import DocumentChecklist from '../../components/common/DocumentChecklist.vue';
import { store } from '../../stores/mockData';

import {
  Users,
  ShoppingCart,
  Clock,
  CheckCircle2,
  Package,
  Plus,
  ChevronRight,
  Bell,
  Activity,
} from 'lucide-vue-next';

const drawerOpen = ref(false);
const selectedOrder = ref(null);

const totalClients = computed(() => store.clients.length);
const activeClients = computed(() => store.clients.filter((c) => c.status === 'Aktif').length);
const totalOrders = computed(() => store.orders.length);
const waitingVerification = computed(() => store.orders.filter((o) => o.status === 'Menunggu Verifikasi').length);
const approvedOrders = computed(() => store.orders.filter((o) => o.status === 'Disetujui').length);
const inProcessOrders = computed(() => store.orders.filter((o) => o.status === 'Diproses').length);
const completedOrders = computed(() => store.orders.filter((o) => o.status === 'Selesai').length);

const recentOrders = computed(() => store.orders.slice(0, 5));

const marketingNotifications = computed(() => {
  return store.notifications.filter((n) => n.role === 'MARKETING' || n.role === 'ALL');
});

const orderStatusDist = computed(() => [
  { label: 'Draft', count: store.orders.filter((o) => o.status === 'Draft').length, colorClass: 'bg-slate-400' },
  { label: 'Menunggu Verifikasi', count: waitingVerification.value, colorClass: 'bg-amber-500' },
  { label: 'Disetujui', count: approvedOrders.value, colorClass: 'bg-emerald-500' },
  { label: 'Ditolak', count: store.orders.filter((o) => o.status === 'Ditolak').length, colorClass: 'bg-rose-500' },
  { label: 'Diproses', count: inProcessOrders.value, colorClass: 'bg-blue-500' },
  { label: 'Selesai', count: completedOrders.value, colorClass: 'bg-emerald-600' },
]);

const viewOrderDetail = (order) => {
  selectedOrder.value = order;
  drawerOpen.value = true;
};
</script>
