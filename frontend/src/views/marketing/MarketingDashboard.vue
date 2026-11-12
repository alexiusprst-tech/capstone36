<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Dashboard Marketing</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pantau akuisisi client, status pipeline order, dan persetujuan verifikasi Direktur.
          </p>
        </div>
      </div>
    </template>

    <!-- BANNER: PENGAJUAN MASUK DARI CLIENT -->
    <div
      v-if="pendingClientOrders > 0"
      class="bg-alpharo-50/70 border border-alpharo-200/80 rounded-xl p-4 mb-6 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
    >
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-alpharo-600 text-white flex items-center justify-center shrink-0 shadow-xs">
          <Inbox class="w-5 h-5 text-amber-300 stroke-[2.5]" />
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h3 class="text-sm font-semibold text-slate-900">
              Terdapat {{ pendingClientOrders }} Pengajuan Kebutuhan Baru dari Rekanan Client
            </h3>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-alpharo-100 text-alpharo-600 border border-alpharo-200 animate-pulse">
              Perlu Verifikasi
            </span>
          </div>
          <p class="text-xs text-slate-600 mt-0.5">
            Instansi sekolah/rekanan telah mengajukan daftar kebutuhan sarana. Tinjau spesifikasi barang, ketersediaan stok, dan konfirmasi penerimaan untuk diteruskan ke Direktur.
          </p>
        </div>
      </div>
      <Link
        href="/marketing/orders?filter=diajukan"
        class="px-3.5 py-2 bg-alpharo-600 hover:bg-alpharo-800 text-white font-semibold rounded-lg text-xs shadow-xs flex items-center gap-1.5 shrink-0 transition-colors"
      >
        <span>Tinjau & Terima Pengajuan</span>
        <ChevronRight class="w-4 h-4" />
      </Link>
    </div>

    <!-- KPI CARDS GRID (SIMPLIFIED 4 CARDS - HARMONIZED CORPORATE BLUE) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <KpiCard
        title="Pengajuan Client"
        :value="pendingClientOrders"
        :icon-component="Inbox"
        icon-variant="blue"
        subtitle="Menunggu tinjauan Marketing"
      />
      <KpiCard
        title="Menunggu Verifikasi"
        :value="waitingVerification"
        :icon-component="Clock"
        icon-variant="blue"
        subtitle="Di meja Direktur"
      />
      <KpiCard
        title="Siap Invoice"
        :value="approvedOrders"
        :icon-component="CheckCircle2"
        icon-variant="blue"
        :subtitle="`${inProcessOrders} diproses & ${completedOrders} selesai`"
      />
      <KpiCard
        title="Client Aktif"
        :value="activeClients"
        :icon-component="Users"
        icon-variant="blue"
        :subtitle="`Dari ${totalClients} entitas terdaftar`"
      />
    </div>

    <!-- MAIN DASHBOARD CONTENT: 2-COLUMN BALANCED LAYOUT -->
    <div class="dashboard-workspace min-w-0 flex flex-col gap-4 sm:gap-5">
      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-semibold text-slate-900 text-base">Order Terbaru</h3>
              <p class="text-xs text-slate-500 mt-0.5">Daftar transaksi order terkini yang diajukan</p>
            </div>
            <Link
              href="/marketing/orders"
              class="text-xs font-semibold text-alpharo-500 hover:underline flex items-center gap-1"
            >
              Lihat Semua Order <ChevronRight class="w-3.5 h-3.5" />
            </Link>
          </div>

          <div class="overflow-x-auto xl:max-h-[26rem] xl:overflow-y-auto overscroll-auto">
            <table class="ui-table-wide ui-table w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50/70 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
                  <th class="py-2.5 px-3.5 whitespace-nowrap">Nomor Order</th>
                  <th class="py-2.5 px-3.5">Client & Proyek</th>
                  <th class="py-2.5 px-3.5 whitespace-nowrap">Tanggal</th>
                  <th class="py-2.5 px-3.5 text-right whitespace-nowrap">Nilai Order</th>
                  <th class="py-2.5 px-3.5 text-center whitespace-nowrap">Status</th>
                  <th class="py-2.5 px-3.5 text-center whitespace-nowrap">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr
                  v-for="order in recentOrders"
                  :key="order.id"
                  :class="[
                    order.status === 'Diajukan Client' ? 'bg-alpharo-50/30 hover:bg-alpharo-50/60' : 'hover:bg-slate-50/60',
                    'transition-colors'
                  ]"
                >
                  <td class="py-3 px-3.5 font-semibold text-slate-900 font-mono whitespace-nowrap">
                    {{ order.orderNumber }}
                  </td>
                  <td class="py-3 px-3.5">
                    <div class="font-semibold text-slate-900 leading-snug">{{ order.clientName }}</div>
                    <div class="text-[11px] text-slate-400 truncate max-w-xs sm:max-w-sm mt-0.5" :title="order.projectName">
                      {{ order.projectName }}
                    </div>
                  </td>
                  <td class="py-3 px-3.5 text-slate-500 whitespace-nowrap">
                    {{ order.date }}
                  </td>
                  <td class="py-3 px-3.5 text-right font-semibold font-mono text-slate-900 whitespace-nowrap">
                    Rp {{ (order.grandTotal || 0).toLocaleString('id-ID') }}
                  </td>
                  <td class="py-3 px-3.5 text-center whitespace-nowrap">
                    <StatusBadge plain :status="order.status" />
                  </td>
                  <td class="py-3 px-3.5 text-center whitespace-nowrap">
                    <Link
                      v-if="order.status === 'Diajukan Client'"
                      href="/marketing/orders?filter=diajukan"
                      class="px-2.5 py-1 text-[11px] font-semibold text-white bg-alpharo-600 hover:bg-alpharo-800 rounded shadow-xs transition-colors inline-block"
                    >
                      Tinjau & Terima
                    </Link>
                    <button
                      v-else
                      type="button"
                      class="px-2.5 py-1 text-[11px] font-semibold text-alpharo-500 hover:bg-alpharo-50 rounded border border-alpharo-200 transition-colors"
                      @click="viewOrderDetail(order)"
                    >
                      Detail
                    </button>
                  </td>
                </tr>
                <tr v-if="recentOrders.length === 0">
                  <td colspan="6" class="py-8 text-center text-slate-400 text-xs"><div class="ui-empty-state">
                    Belum ada transaksi order terbaru.
                  </div></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <h3 class="font-semibold text-slate-900 text-sm flex items-center gap-2">
              <Bell class="w-4 h-4 text-alpharo-500" />
              Notifikasi Pemasaran
            </h3>
            <span class="text-[11px] text-slate-500">
              {{ marketingNotifications.length }} Pesan
            </span>
          </div>

          <div class="divide-y divide-slate-100 max-h-[460px] overflow-y-auto">
            <div
              v-for="notif in marketingNotifications"
              :key="notif.id"
              class="py-3 flex items-start gap-3"
              :class="notif.isRead ? 'text-slate-500' : 'text-slate-800'"
            >
              <div
                class="w-2 h-2 rounded-full mt-1.5 shrink-0"
                :class="notif.isRead ? 'bg-slate-300' : 'bg-alpharo-500'"
              ></div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                  <h4 class="font-semibold text-xs text-slate-800 whitespace-normal [overflow-wrap:anywhere]">{{ notif.title }}</h4>
                  <span class="text-[11px] text-slate-400 shrink-0">{{ notif.time }}</span>
                </div>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed whitespace-normal [overflow-wrap:anywhere]">{{ notif.message }}</p>
                <Link
                  v-if="notif.link"
                  :href="notif.link"
                  class="text-[11px] font-semibold text-alpharo-500 hover:underline mt-1 inline-block"
                >
                  Buka &rarr;
                </Link>
              </div>
            </div>

            <div v-if="marketingNotifications.length === 0" class="py-8 text-center text-slate-400 text-xs">
              Tidak ada notifikasi baru.
            </div>
          </div>
        </div>

      <ActivityLogCard class="dashboard-activity" />
    </div>

    <!-- ORDER DETAIL DRAWER -->
    <Drawer
      v-model="drawerOpen"
      :title="`Rincian Order: ${selectedOrder?.orderNumber || ''}`"
      :subtitle="selectedOrder?.clientName || ''"
      size="lg"
    >
      <div v-if="selectedOrder" class="space-y-6 text-xs">
        <div
          v-if="selectedOrder.status === 'Diajukan Client'"
          class="p-4 bg-alpharo-50 rounded-xl border border-alpharo-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
        >
          <div>
            <span class="font-semibold text-slate-900 font-semibold text-xs block">Pengajuan Baru Rekanan Client</span>
            <span class="text-[11px] text-slate-600 block mt-0.5">
              Pesanan ini diajukan mandiri dan menunggu verifikasi & konfirmasi penerimaan oleh Marketing.
            </span>
          </div>
          <Link
            href="/marketing/orders?filter=diajukan"
            class="px-3.5 py-1.5 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg shadow-xs flex items-center gap-1.5 shrink-0"
          >
            <span>Tinjau & Terima di Kelola Order</span>
            <ChevronRight class="w-3.5 h-3.5" />
          </Link>
        </div>

        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200">
          <div>
            <span class="text-slate-500 block">Status Order</span>
            <div class="mt-1"><StatusBadge :status="selectedOrder.status" /></div>
          </div>
          <div class="text-right">
            <span class="text-slate-500 block">Total Nilai</span>
            <div class="text-base font-semibold font-mono text-slate-900 mt-1">
              Rp {{ (selectedOrder.grandTotal || 0).toLocaleString('id-ID') }}
            </div>
          </div>
        </div>

        <div>
          <h4 class="text-xs font-semibold uppercase text-slate-500 tracking-wider mb-2">Item Barang / Jasa</h4>
          <div class="border border-slate-200 rounded-lg overflow-hidden">
            <table class="ui-table w-full text-xs text-left">
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
                    <span class="block text-[11px] font-mono text-slate-400">{{ it.sku }}</span>
                  </td>
                  <td class="p-2.5 text-center">{{ it.qty }} {{ it.unit }}</td>
                  <td class="p-2.5 text-right">Rp {{ (it.price || 0).toLocaleString('id-ID') }}</td>
                  <td class="p-2.5 text-right font-semibold">Rp {{ (it.total || ((it.qty || 0) * (it.price || 0))).toLocaleString('id-ID') }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <DocumentChecklist :documents="selectedOrder.documents" />

        <div>
          <h4 class="text-xs font-semibold uppercase text-slate-500 tracking-wider mb-3">Timeline Proses</h4>
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
import ActivityLogCard from '../../components/common/ActivityLogCard.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import Drawer from '../../components/common/Drawer.vue';
import Timeline from '../../components/common/Timeline.vue';
import DocumentChecklist from '../../components/common/DocumentChecklist.vue';
import { store } from '../../stores/mockData';

import {
  Users,
  Clock,
  CheckCircle2,
  ChevronRight,
  Bell,
  Inbox,
} from 'lucide-vue-next';

const drawerOpen = ref(false);
const selectedOrder = ref(null);

const totalClients = computed(() => store.clients.length);
const activeClients = computed(() => store.clients.filter((c) => c.status === 'Aktif').length);
const pendingClientOrders = computed(() => store.orders.filter((o) => o.status === 'Diajukan Client').length);
const waitingVerification = computed(() => store.orders.filter((o) => o.status === 'Menunggu Verifikasi').length);
const approvedOrders = computed(() => store.orders.filter((o) => o.status === 'Siap Invoice').length);
const inProcessOrders = computed(() => store.orders.filter((o) => o.status === 'Diproses').length);
const completedOrders = computed(() => store.orders.filter((o) => o.status === 'Selesai').length);

const recentOrders = computed(() => store.orders.slice(0, 5));

const marketingNotifications = computed(() => {
  return store.notifications.filter((n) => n.role === 'MARKETING' || n.role === 'ALL');
});

const viewOrderDetail = (order) => {
  selectedOrder.value = order;
  drawerOpen.value = true;
};
</script>
