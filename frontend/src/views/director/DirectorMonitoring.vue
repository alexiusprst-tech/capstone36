<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2.5">
            <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Monitoring Operasional Terpadu</h1>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-alpharo-50 text-alpharo-600 border border-alpharo-200">
              Pengawasan Lintas Divisi
            </span>
          </div>
          <p class="text-sm text-slate-500 mt-1">
            Pengawasan manajerial satu pintu terhadap pesanan proyek, kesiapan logistik gudang, kelengkapan berkas SPJ, dan arus kas keuangan.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <Link
            href="/director/verification"
            class="inline-flex items-center gap-2 px-3.5 py-2 bg-alpharo-600 hover:bg-alpharo-800 text-white font-semibold rounded-xl text-xs shadow-xs transition-all cursor-pointer"
          >
            <ShieldCheck class="w-4 h-4 text-amber-300" />
            <span>Pusat Persetujuan ({{ waitingApprovalCount }})</span>
          </Link>
        </div>
      </div>
    </template>

    <!-- 4 TABS OPERASIONAL LINTAS DIVISI -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-6 border-b border-slate-200/80">
      <button
        v-for="t in tabs"
        :key="t.key"
        type="button"
        :class="[
          activeTab === t.key
            ? 'bg-alpharo-600 text-white font-semibold shadow-xs'
            : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 font-medium',
          'px-4 py-2.5 rounded-xl text-xs whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer'
        ]"
        @click="switchTab(t.key)"
      >
        <component :is="t.icon" class="w-4 h-4" />
        <span>{{ t.label }}</span>
        <span
          v-if="t.badge"
          :class="[
            activeTab === t.key ? 'bg-white/25 text-white' : 'bg-slate-100 text-slate-700',
            'px-1.5 py-0.2 rounded-full text-[11px] font-mono font-semibold'
          ]"
        >
          {{ t.badge }}
        </span>
      </button>
    </div>

    <!-- ============================================================ -->
    <!-- TAB 1: MONITORING PESANAN & KONTRAK                          -->
    <!-- ============================================================ -->
    <div v-if="activeTab === 'orders'" class="space-y-6">
      <!-- KPIS PESANAN -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <KpiCard title="Total Pesanan" :value="store.orders.length" icon-variant="blue" />
        <KpiCard title="Menunggu Approval" :value="countOrderStatus('Menunggu Verifikasi')" icon-variant="blue" />
        <KpiCard title="Disetujui" :value="countOrderStatus('Disetujui')" icon-variant="blue" />
        <KpiCard title="Menunggu Bayar" :value="countOrderStatus('Menunggu Pembayaran')" icon-variant="blue" />
        <KpiCard title="Sedang Diproses" :value="countOrderStatus('Diproses')" icon-variant="blue" />
        <KpiCard title="Tuntas Selesai" :value="countOrderStatus('Selesai')" icon-variant="blue" />
      </div>

      <!-- FILTER & SEARCH PESANAN -->
      <div class="ui-panel bg-white p-4 rounded-xl border border-slate-200/90 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-2xs">
        <div class="relative w-full sm:w-80">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
          <input
            v-model="orderSearch"
            type="text"
            placeholder="Cari no. order, client, proyek..."
            class="w-full pl-9 pr-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-500"
          />
        </div>

        <div class="flex items-center gap-3 w-full sm:w-auto">
          <select
            v-model="orderStatusFilter"
            class="text-xs px-3 py-2 rounded-lg border border-slate-300 bg-white"
          >
            <option value="">Semua Status Transaksi</option>
            <option value="Menunggu Verifikasi">Menunggu Verifikasi</option>
            <option value="Disetujui">Disetujui</option>
            <option value="Menunggu Pembayaran">Menunggu Pembayaran</option>
            <option value="Diproses">Diproses</option>
            <option value="Selesai">Selesai</option>
            <option value="Ditolak">Ditolak</option>
          </select>
        </div>
      </div>

      <!-- TABEL PESANAN -->
      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="ui-table-wide ui-table w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                <th class="py-3 px-4">No. Order</th>
                <th class="py-3 px-4">Client & Proyek</th>
                <th class="py-3 px-4">Tanggal</th>
                <th class="py-3 px-4 text-right">Nilai Total</th>
                <th class="py-3 px-4 text-center">Status Transaksi</th>
                <th class="py-3 px-4 text-center">Kesiapan Gudang</th>
                <th class="py-3 px-4 text-right">Aksi Eksekutif</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="order in filteredOrders" :key="order.id" class="hover:bg-slate-50/70 transition-colors">
                <td class="py-3.5 px-4">
                  <div class="font-mono font-semibold text-slate-900">{{ order.orderNumber }}</div>
                  <div v-if="order.isHold" class="mt-0.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-100 text-rose-700 border border-rose-200">
                      ⛔ Ditahan Direktur
                    </span>
                  </div>
                </td>
                <td class="py-3.5 px-4 min-w-[200px]">
                  <span class="font-semibold text-slate-900 block">{{ order.clientName }}</span>
                  <span class="text-[11px] text-slate-500 block truncate max-w-xs">{{ order.projectName }}</span>
                </td>
                <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ order.date }}</td>
                <td class="py-3.5 px-4 text-right font-mono font-semibold text-slate-900 whitespace-nowrap">
                  Rp {{ Number(order.grandTotal || 0).toLocaleString('id-ID') }}
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <StatusBadge :status="order.status" />
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <StatusBadge :status="order.fulfillmentStatus || 'Menunggu Verifikasi'" />
                </td>
                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      v-if="!order.serverManaged"
                      type="button"
                      :class="[
                        order.isHold
                          ? 'bg-rose-50 text-rose-700 border-rose-300 hover:bg-rose-100'
                          : 'bg-slate-100 text-slate-700 border-slate-300 hover:bg-slate-200',
                        'px-2.5 py-1 rounded text-[11px] font-semibold border transition-colors cursor-pointer'
                      ]"
                      :title="order.isHold ? 'Lepaskan penahanan order' : 'Tahan order ini dari proses pengiriman'"
                      @click="toggleHoldOrder(order)"
                    >
                      {{ order.isHold ? 'Lepas Tahan' : 'Tahan (Hold)' }}
                    </button>
                    <button
                      type="button"
                      class="px-2.5 py-1 text-[11px] font-semibold text-alpharo-700 hover:bg-alpharo-50 rounded border border-alpharo-200 transition-colors cursor-pointer"
                      @click="openOrderDetail(order)"
                    >
                      Detail
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredOrders.length === 0">
                <td colspan="7" class="py-8 text-center text-slate-400 text-xs"><div class="ui-empty-state">
                  Tidak ada data pesanan yang sesuai filter.
                </div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB 2: MONITORING GUDANG & LOGISTIK                          -->
    <!-- ============================================================ -->
    <div v-if="activeTab === 'warehouse'" class="space-y-6">
      <!-- KPIS GUDANG -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <KpiCard title="Order Perlu Dipenuhi" :value="store.warehouseFulfillments.length" icon-variant="blue" />
        <KpiCard title="Barang Tersedia" :value="availableCount" icon-variant="blue" />
        <KpiCard title="Stok Habis / OOS" :value="oosCount" icon-variant="blue" />
        <KpiCard title="Diproses Gudang" :value="fulfillProcessCount" icon-variant="blue" />
        <KpiCard title="Siap Dikirim" :value="readyShipCount" icon-variant="blue" />
        <KpiCard title="Sedang Dikirim" :value="shippingCount" icon-variant="blue" />
      </div>

      <!-- TABEL ANTREAN GUDANG -->
      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <h3 class="font-semibold text-slate-900 text-xs uppercase tracking-wider">
            Antrean Pemenuhan Order Disetujui
          </h3>
          <span class="text-xs text-slate-500 font-mono">{{ store.warehouseFulfillments.length }} Order Aktif</span>
        </div>

        <div class="overflow-x-auto">
          <table class="ui-table w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                <th class="py-3 px-4">No. Order</th>
                <th class="py-3 px-4">Client</th>
                <th class="py-3 px-4">Rincian Kebutuhan Barang</th>
                <th class="py-3 px-4">PIC Gudang</th>
                <th class="py-3 px-4 text-center">Status Pemenuhan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="ful in store.warehouseFulfillments" :key="ful.id" class="hover:bg-slate-50/70 transition-colors">
                <td class="py-3.5 px-4 font-mono font-semibold text-slate-900">{{ ful.orderNumber }}</td>
                <td class="py-3.5 px-4 font-semibold text-slate-800">{{ ful.clientName }}</td>
                <td class="py-3.5 px-4">
                  <div v-for="it in ful.items" :key="it.sku" class="text-[11px] text-slate-600">
                    &bull; {{ it.name }}: <span class="font-semibold">{{ it.fulfilledQty }}/{{ it.reqQty }} {{ it.unit }}</span>
                  </div>
                </td>
                <td class="py-3.5 px-4 text-slate-600">{{ ful.checkedBy || 'Staf Gudang' }}</td>
                <td class="py-3.5 px-4 text-center">
                  <StatusBadge :status="ful.status" />
                </td>
              </tr>
              <tr v-if="store.warehouseFulfillments.length === 0">
                <td colspan="5" class="py-8 text-center text-slate-400 text-xs"><div class="ui-empty-state">
                  Tidak ada antrean pemenuhan aktif di gudang.
                </div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB 3: MONITORING BERKAS & DOKUMEN SPJ                       -->
    <!-- ============================================================ -->
    <div v-if="activeTab === 'administration'" class="space-y-6">
      <!-- KPIS SPJ -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <KpiCard title="PO Terbit" :value="poCount" icon-variant="blue" />
        <KpiCard title="Surat Jalan Terbit" :value="sjCount" icon-variant="blue" />
        <KpiCard title="BAST Terbit" :value="bastCount" icon-variant="blue" />
        <KpiCard title="Dokumen SPJ Lengkap" :value="completeOrderCount" icon-variant="blue" />
      </div>

      <!-- TABEL KELENGKAPAN SPJ -->
      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <h3 class="font-semibold text-slate-900 text-xs uppercase tracking-wider">
            Status Kelengkapan 4 Berkas Legalitas Proyek
          </h3>
          <span class="text-xs text-slate-500">PO &bull; Surat Jalan &bull; BAST &bull; Faktur Pajak</span>
        </div>

        <div class="overflow-x-auto">
          <table class="ui-table-wide ui-table w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                <th class="py-3 px-4">No. Order</th>
                <th class="py-3 px-4">Client</th>
                <th class="py-3 px-4 text-center">Purchase Order</th>
                <th class="py-3 px-4 text-center">Surat Jalan</th>
                <th class="py-3 px-4 text-center">BAST</th>
                <th class="py-3 px-4 text-center">Faktur Pajak</th>
                <th class="py-3 px-4 text-center">Progres Berkas</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="order in store.orders" :key="order.id" class="hover:bg-slate-50/70 transition-colors">
                <td class="py-3.5 px-4 font-mono font-semibold text-slate-900">{{ order.orderNumber }}</td>
                <td class="py-3.5 px-4 font-medium text-slate-800">{{ order.clientName }}</td>
                <td class="py-3.5 px-4 text-center">
                  <StatusBadge :status="order.documents?.po?.status || 'Belum Dibuat'" />
                </td>
                <td class="py-3.5 px-4 text-center">
                  <StatusBadge :status="order.documents?.suratJalan?.status || 'Belum Dibuat'" />
                </td>
                <td class="py-3.5 px-4 text-center">
                  <StatusBadge :status="order.documents?.bast?.status || 'Belum Dibuat'" />
                </td>
                <td class="py-3.5 px-4 text-center">
                  <StatusBadge :status="order.documents?.faktur?.status || 'Belum Dibuat'" />
                </td>
                <td class="py-3.5 px-4 text-center">
                  <span
                    :class="[
                      getCompletedDocCount(order) === 4 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200',
                      'px-2 py-0.5 rounded-full border font-semibold text-[11px]'
                    ]"
                  >
                    {{ getCompletedDocCount(order) }} / 4 Lengkap
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB 4: MONITORING KEUANGAN & PIUTANG                        -->
    <!-- ============================================================ -->
    <div v-if="activeTab === 'finance'" class="space-y-6">
      <!-- KPIS KEUANGAN -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <KpiCard title="Total Tagihan Invoice" :value="store.invoices.length" icon-variant="blue" />
        <KpiCard title="Belum Dibayar" :value="unpaidFinanceCount" icon-variant="blue" />
        <KpiCard title="Jatuh Tempo" :value="overdueFinanceCount" icon-variant="blue" />
        <KpiCard title="Total Piutang Berjalan" :value="totalReceivableFinance" :is-currency="true" icon-variant="blue" />
      </div>

      <!-- TABEL INVOICE -->
      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <h3 class="font-semibold text-slate-900 text-xs uppercase tracking-wider">
            Portofolio Tagihan & Status Penerimaan Termin
          </h3>
          <span class="text-xs text-slate-500 font-mono">{{ store.invoices.length }} Berkas Invoice</span>
        </div>

        <div class="overflow-x-auto">
          <table class="ui-table-wide ui-table w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                <th class="py-3 px-4">No. Invoice</th>
                <th class="py-3 px-4">No. Order</th>
                <th class="py-3 px-4">Client</th>
                <th class="py-3 px-4 text-right">Total Tagihan</th>
                <th class="py-3 px-4 text-right">Terbayar</th>
                <th class="py-3 px-4 text-right">Sisa Piutang</th>
                <th class="py-3 px-4">Jatuh Tempo</th>
                <th class="py-3 px-4 text-center">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="inv in store.invoices" :key="inv.id" class="hover:bg-slate-50/70 transition-colors">
                <td class="py-3.5 px-4 font-mono font-semibold text-slate-900">{{ inv.invoiceNumber }}</td>
                <td class="py-3.5 px-4 font-mono text-slate-600">{{ inv.orderNumber }}</td>
                <td class="py-3.5 px-4 font-semibold text-slate-800">{{ inv.clientName }}</td>
                <td class="py-3.5 px-4 text-right font-mono font-semibold text-slate-900">
                  Rp {{ Number(inv.totalAmount || 0).toLocaleString('id-ID') }}
                </td>
                <td class="py-3.5 px-4 text-right font-mono text-emerald-600 font-semibold">
                  Rp {{ Number(inv.paidAmount || 0).toLocaleString('id-ID') }}
                </td>
                <td class="py-3.5 px-4 text-right font-mono text-rose-600 font-semibold">
                  Rp {{ Number(inv.remainingAmount || 0).toLocaleString('id-ID') }}
                </td>
                <td class="py-3.5 px-4 text-slate-500">{{ inv.dueDate }}</td>
                <td class="py-3.5 px-4 text-center">
                  <StatusBadge :status="inv.status" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL DETAIL ORDER -->
    <Modal v-model="detailModalOpen" title="Rincian Order Eksekutif" size="lg">
      <div v-if="selectedOrder" class="space-y-4 text-xs">
        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div>
            <span class="text-slate-400 block text-[11px] uppercase font-semibold">No. Order</span>
            <span class="font-mono font-semibold text-slate-900">{{ selectedOrder.orderNumber }}</span>
          </div>
          <div>
            <span class="text-slate-400 block text-[11px] uppercase font-semibold">Client</span>
            <span class="font-semibold text-slate-800">{{ selectedOrder.clientName }}</span>
          </div>
          <div>
            <span class="text-slate-400 block text-[11px] uppercase font-semibold">Nilai Proyek</span>
            <span class="font-mono font-semibold text-emerald-700">Rp {{ Number(selectedOrder.grandTotal || 0).toLocaleString('id-ID') }}</span>
          </div>
          <div>
            <span class="text-slate-400 block text-[11px] uppercase font-semibold">Status Transaksi</span>
            <StatusBadge :status="selectedOrder.status" />
          </div>
        </div>

        <div>
          <h4 class="font-semibold text-slate-900 mb-2">Item Barang Dipesan:</h4>
          <div class="border border-slate-200 rounded-lg overflow-hidden">
            <table class="ui-table w-full text-left text-xs">
              <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                <tr>
                  <th class="py-2 px-3">Nama Barang</th>
                  <th class="py-2 px-3 text-center">Qty</th>
                  <th class="py-2 px-3 text-right">Harga Satuan</th>
                  <th class="py-2 px-3 text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="it in selectedOrder.items" :key="it.sku">
                  <td class="py-2 px-3 font-medium text-slate-800">{{ it.name }}</td>
                  <td class="py-2 px-3 text-center">{{ it.qty }} {{ it.unit }}</td>
                  <td class="py-2 px-3 text-right font-mono">Rp {{ Number(it.price || 0).toLocaleString('id-ID') }}</td>
                  <td class="py-2 px-3 text-right font-mono font-semibold">Rp {{ Number((it.qty || 0) * (it.price || 0)).toLocaleString('id-ID') }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="flex justify-end pt-3 border-t border-slate-200">
          <button
            type="button"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold cursor-pointer"
            @click="detailModalOpen = false"
          >
            Tutup
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from '../../navigation';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import AppLayout from '../../components/layout/AppLayout.vue';
import KpiCard from '../../components/common/KpiCard.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import Modal from '../../components/common/Modal.vue';

import {
  ShoppingCart,
  Package,
  FileText,
  CreditCard,
  Search,
  ShieldCheck,
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const activeTab = ref('orders');
const orderSearch = ref('');
const orderStatusFilter = ref('');
const detailModalOpen = ref(false);
const selectedOrder = ref(null);

const waitingApprovalCount = computed(() => {
  return (store.orders || []).filter((o) => o.status === 'Menunggu Verifikasi').length;
});

const tabs = computed(() => [
  { key: 'orders', label: 'Pesanan & Kontrak', icon: ShoppingCart, badge: store.orders.length },
  { key: 'warehouse', label: 'Logistik & Gudang', icon: Package, badge: store.warehouseFulfillments.length },
  { key: 'administration', label: 'Dokumen & SPJ', icon: FileText, badge: store.orders.length },
  { key: 'finance', label: 'Keuangan & Piutang', icon: CreditCard, badge: store.invoices.length },
]);

const switchTab = (tabKey) => {
  activeTab.value = tabKey;
  router.replace({ query: { ...route.query, tab: tabKey } });
};

onMounted(() => {
  if (route.query.tab && ['orders', 'warehouse', 'administration', 'finance'].includes(route.query.tab)) {
    activeTab.value = route.query.tab;
  }
});

// Pesanan helpers
const countOrderStatus = (st) => (store.orders || []).filter((o) => o.status === st).length;

const filteredOrders = computed(() => {
  return (store.orders || []).filter((o) => {
    if (orderStatusFilter.value && o.status !== orderStatusFilter.value) return false;
    if (orderSearch.value.trim()) {
      const q = orderSearch.value.toLowerCase();
      const matchNum = (o.orderNumber || '').toLowerCase().includes(q);
      const matchClient = (o.clientName || '').toLowerCase().includes(q);
      const matchProj = (o.projectName || '').toLowerCase().includes(q);
      if (!matchNum && !matchClient && !matchProj) return false;
    }
    return true;
  });
});

const toggleHoldOrder = (order) => {
  if (order.serverManaged) {
    toast.warning('Tahan atau revisi pesanan server belum ditetapkan dalam alur TO-BE.');
    return;
  }
  order.isHold = !order.isHold;
  if (order.isHold) {
    toast.error(`Pesanan ${order.orderNumber} berhasil DITAHAN (Hold) oleh Direktur.`);
    storeActions.addActivityLog('Veto Direktur', order.orderNumber, 'Pesanan ditahan dari proses pengiriman.');
  } else {
    toast.success(`Penahanan pesanan ${order.orderNumber} dilepaskan.`);
    storeActions.addActivityLog('Lepas Hold', order.orderNumber, 'Penahanan pesanan dilepaskan oleh Direktur.');
  }
};

const openOrderDetail = (order) => {
  selectedOrder.value = order;
  detailModalOpen.value = true;
};

// Gudang metrics
const availableCount = computed(() => (store.products || []).filter((p) => (p.stock || p.actual_stock || 0) > 10).length);
const oosCount = computed(() => (store.products || []).filter((p) => (p.stock || p.actual_stock || 0) <= 0).length);
const fulfillProcessCount = computed(() => (store.warehouseFulfillments || []).filter((f) => f.status === 'Diproses').length);
const readyShipCount = computed(() => (store.warehouseFulfillments || []).filter((f) => f.status === 'Siap Dikirim').length);
const shippingCount = computed(() => (store.warehouseFulfillments || []).filter((f) => f.status === 'Sedang Dikirim').length);

// SPJ metrics
const getCompletedDocCount = (o) => {
  let c = 0;
  if (o.documents?.po?.status === 'Final' || o.documents?.po?.status === 'Terbit') c++;
  if (o.documents?.suratJalan?.status === 'Final' || o.documents?.suratJalan?.status === 'Terbit') c++;
  if (o.documents?.bast?.status === 'Final' || o.documents?.bast?.status === 'Terbit') c++;
  if (o.documents?.faktur?.status === 'Final' || o.documents?.faktur?.status === 'Terbit') c++;
  return c;
};

const poCount = computed(() => (store.orders || []).filter((o) => o.documents?.po?.status === 'Final').length);
const sjCount = computed(() => (store.orders || []).filter((o) => o.documents?.suratJalan?.status === 'Final').length);
const bastCount = computed(() => (store.orders || []).filter((o) => o.documents?.bast?.status === 'Final').length);
const completeOrderCount = computed(() => (store.orders || []).filter((o) => getCompletedDocCount(o) === 4).length);

// Finance metrics
const unpaidFinanceCount = computed(() => (store.invoices || []).filter((i) => i.status === 'Belum Dibayar').length);
const overdueFinanceCount = computed(() => (store.invoices || []).filter((i) => i.status === 'Jatuh Tempo').length);
const totalReceivableFinance = computed(() => {
  return (store.invoices || []).reduce((acc, cur) => acc + (cur.remainingAmount || 0), 0);
});
</script>

