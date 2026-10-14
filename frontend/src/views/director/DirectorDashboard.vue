<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Executive Dashboard Direktur</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pusat verifikasi, approval wewenang eksekutif, dan monitoring lintas divisi perusahaan.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <router-link
            to="/director/verification"
            class="inline-flex items-center gap-2 px-4 py-2 bg-[#F5A623] hover:bg-amber-600 text-slate-900 font-bold rounded-lg text-sm shadow-xs transition-colors"
          >
            <FileCheck2 class="w-4 h-4 text-slate-900" />
            <span>Pusat Verifikasi ({{ waitingOrders.length }})</span>
          </router-link>
        </div>
      </div>
    </template>

    <!-- EXECUTIVE KPIS (11 KPIS SPECIFIED IN REQUIREMENTS) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 mb-6">
      <KpiCard
        title="Total Client"
        :value="totalClients"
        :icon-component="Users"
        icon-variant="blue"
      />
      <KpiCard
        title="Total Order"
        :value="totalOrders"
        :icon-component="ShoppingCart"
        icon-variant="slate"
      />
      <KpiCard
        title="Menunggu Verifikasi"
        :value="waitingOrders.length"
        :icon-component="Clock"
        icon-variant="amber"
      />
      <KpiCard
        title="Disetujui"
        :value="approvedCount"
        :icon-component="CheckCircle2"
        icon-variant="emerald"
      />
      <KpiCard
        title="Ditolak"
        :value="rejectedCount"
        :icon-component="AlertCircle"
        icon-variant="rose"
      />
      <KpiCard
        title="Diproses"
        :value="inProcessCount"
        :icon-component="Package"
        icon-variant="blue"
      />
      <KpiCard
        title="Selesai"
        :value="completedCount"
        :icon-component="CheckCircle2"
        icon-variant="emerald"
      />
      <KpiCard
        title="Total Nilai Order"
        :value="totalOrderValue"
        :is-currency="true"
        :icon-component="DollarSign"
        icon-variant="blue"
      />
      <KpiCard
        title="Invoice Belum Bayar"
        :value="unpaidInvoicesCount"
        :icon-component="Receipt"
        icon-variant="amber"
      />
      <KpiCard
        title="Total Pembayaran"
        :value="totalPaidValue"
        :is-currency="true"
        :icon-component="Wallet"
        icon-variant="emerald"
      />
      <KpiCard
        title="Total Piutang"
        :value="totalReceivableValue"
        :is-currency="true"
        :icon-component="CreditCard"
        icon-variant="rose"
      />
    </div>

    <!-- MAIN TWO-COLUMN MANAGEMENT SECTIONS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- LEFT 2 COLS: SECTION A (WAITING APPROVAL), SECTION B (ORDER STATUS), C/D/E MONITORING -->
      <div class="lg:col-span-2 space-y-6">
        <!-- SECTION A: ORDER WAITING APPROVAL -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
              <h3 class="font-bold text-slate-900 text-base">Antrean Order Menunggu Verifikasi & Approval</h3>
            </div>
            <router-link
              to="/director/verification"
              class="text-xs font-bold text-alpharo-500 hover:underline flex items-center gap-1"
            >
              Lihat Detail Telaah <ChevronRight class="w-3.5 h-3.5" />
            </router-link>
          </div>

          <div v-if="waitingOrders.length > 0" class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                  <th class="py-2.5 px-3">No. Order</th>
                  <th class="py-2.5 px-3">Client</th>
                  <th class="py-2.5 px-3">Diajukan Oleh</th>
                  <th class="py-2.5 px-3 text-right">Nilai Transaksi</th>
                  <th class="py-2.5 px-3 text-center">Aksi Cepat</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="order in waitingOrders" :key="order.id" class="hover:bg-amber-50/30 transition-colors">
                  <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ order.orderNumber }}</td>
                  <td class="py-3 px-3">
                    <div class="font-bold text-slate-900">{{ order.clientName }}</div>
                    <div class="text-[11px] text-slate-400">{{ order.projectName }}</div>
                  </td>
                  <td class="py-3 px-3 text-slate-600">{{ order.submittedBy }}</td>
                  <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">
                    Rp {{ order.grandTotal.toLocaleString('id-ID') }}
                  </td>
                  <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1.5">
                      <button
                        type="button"
                        class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold text-[11px]"
                        @click="handleQuickApprove(order)"
                      >
                        Approve
                      </button>
                      <button
                        type="button"
                        class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded font-bold text-[11px]"
                        @click="openRejectModal(order)"
                      >
                        Reject
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="p-8 text-center text-xs text-slate-400">
            ✓ Semua pengajuan order telah diverifikasi. Tidak ada antrean yang tertunda.
          </div>
        </div>

        <!-- SECTIONS C, D, E: MINI MONITORING OVERVIEW (WAREHOUSE, DOCUMENT, FINANCE) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- C. WAREHOUSE MONITORING -->
          <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-xs">
            <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-100">
              <h4 class="font-bold text-xs uppercase text-slate-800 tracking-wider">Warehouse</h4>
              <router-link to="/director/warehouse" class="text-[11px] text-alpharo-500 font-bold hover:underline">
                Buka →
              </router-link>
            </div>
            <div class="space-y-2 text-xs">
              <div class="flex justify-between">
                <span class="text-slate-500">Stok Menipis / Habis:</span>
                <span class="font-bold text-rose-600">{{ lowStockCount }} Item</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Antrean Pemenuhan:</span>
                <span class="font-bold text-slate-800">{{ store.warehouseFulfillments.length }} Order</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Armada Pengiriman:</span>
                <span class="font-bold text-blue-600">{{ activeShipmentsCount }} OTW</span>
              </div>
            </div>
          </div>

          <!-- D. DOCUMENT MONITORING -->
          <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-xs">
            <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-100">
              <h4 class="font-bold text-xs uppercase text-slate-800 tracking-wider">Administrasi</h4>
              <router-link to="/director/administration" class="text-[11px] text-alpharo-500 font-bold hover:underline">
                Buka →
              </router-link>
            </div>
            <div class="space-y-2 text-xs">
              <div class="flex justify-between">
                <span class="text-slate-500">Dokumen Lengkap:</span>
                <span class="font-bold text-emerald-600">{{ completeDocsOrders }} Order</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Belum Lengkap:</span>
                <span class="font-bold text-amber-600">{{ incompleteDocsOrders }} Order</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">PO Diterbitkan:</span>
                <span class="font-bold text-slate-800">{{ poCount }} Berkas</span>
              </div>
            </div>
          </div>

          <!-- E. FINANCE MONITORING -->
          <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-xs">
            <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-100">
              <h4 class="font-bold text-xs uppercase text-slate-800 tracking-wider">Finance</h4>
              <router-link to="/director/finance" class="text-[11px] text-alpharo-500 font-bold hover:underline">
                Buka →
              </router-link>
            </div>
            <div class="space-y-2 text-xs">
              <div class="flex justify-between">
                <span class="text-slate-500">Invoice Jatuh Tempo:</span>
                <span class="font-bold text-rose-600">{{ overdueInvoicesCount }} Berkas</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Pembayaran Termin:</span>
                <span class="font-bold text-amber-600">{{ partialInvoicesCount }} Berjalan</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Invoice Lunas:</span>
                <span class="font-bold text-emerald-600">{{ paidInvoicesCount }} Berkas</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT 1 COL: F. RECENT ACTIVITIES & G. APPROVAL HISTORY -->
      <div class="space-y-6">
        <!-- G. APPROVAL HISTORY -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
            <h3 class="font-bold text-slate-900 text-sm">Riwayat Keputusan Approval</h3>
            <span class="text-[10px] text-slate-400 font-mono">Log Direktur</span>
          </div>
          <div class="space-y-3">
            <div
              v-for="app in approvedOrRejectedOrders.slice(0, 4)"
              :key="app.id"
              class="p-2.5 rounded-lg border border-slate-100 bg-slate-50 text-xs"
            >
              <div class="flex items-center justify-between">
                <span class="font-mono font-bold text-slate-900">{{ app.orderNumber }}</span>
                <StatusBadge :status="app.status" />
              </div>
              <p class="font-medium text-slate-800 mt-1 line-clamp-1">{{ app.clientName }}</p>
              <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2 italic">"{{ app.approvalNotes }}"</p>
              <span class="text-[10px] text-slate-400 block mt-1">{{ app.approvalDate }}</span>
            </div>
          </div>
        </div>

        <!-- F. RECENT ACTIVITIES -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
            <h3 class="font-bold text-slate-900 text-sm">Audit Aktivitas Sistem</h3>
            <router-link to="/director/activities" class="text-xs text-alpharo-500 font-bold hover:underline">
              Selengkapnya →
            </router-link>
          </div>
          <div class="space-y-3">
            <div
              v-for="act in store.activityLogs.slice(0, 4)"
              :key="act.id"
              class="text-xs flex items-start gap-2"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-alpharo-500 mt-1.5 shrink-0"></span>
              <div class="min-w-0">
                <p class="font-semibold text-slate-800 truncate">{{ act.action }}</p>
                <p class="text-[11px] text-slate-500 truncate">{{ act.details }}</p>
                <span class="text-[10px] text-slate-400">{{ act.timestamp }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- REJECT MODAL WITH MANDATORY REASON NOTES -->
    <Modal
      v-model="rejectModalOpen"
      title="Tolak Pengajuan Order"
      :subtitle="`Order: ${orderToReject?.orderNumber} (${orderToReject?.clientName})`"
      size="md"
    >
      <div class="space-y-3">
        <p class="text-xs text-slate-600">
          Sesuai SOP Direksi, penolakan order <strong>wajib mencantumkan alasan tertulis</strong> (misal: kendala limit kredit, stok habis, atau spesifikasi tidak sesuai).
        </p>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
            Alasan Penolakan (Wajib Diisi) *
          </label>
          <textarea
            v-model="rejectReason"
            rows="3"
            required
            placeholder="Contoh: Stok material tidak mencukupi dan termin pembayaran melanggar batas kredit limit..."
            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-rose-500"
          ></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button
            type="button"
            class="px-3.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg"
            @click="rejectModalOpen = false"
          >
            Batal
          </button>
          <button
            type="button"
            :disabled="!rejectReason.trim()"
            class="px-4 py-1.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg disabled:opacity-50"
            @click="executeReject"
          >
            Konfirmasi Tolak Order
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import KpiCard from '../../components/common/KpiCard.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import Modal from '../../components/common/Modal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';

import {
  Users,
  ShoppingCart,
  Clock,
  CheckCircle2,
  AlertCircle,
  Package,
  DollarSign,
  Receipt,
  Wallet,
  CreditCard,
  ChevronRight,
  FileCheck2,
} from 'lucide-vue-next';

const toast = useToast();

const rejectModalOpen = ref(false);
const orderToReject = ref(null);
const rejectReason = ref('');

const totalClients = computed(() => store.clients.length);
const totalOrders = computed(() => store.orders.length);
const waitingOrders = computed(() => store.orders.filter((o) => o.status === 'Menunggu Verifikasi'));
const approvedCount = computed(() => store.orders.filter((o) => o.status === 'Disetujui').length);
const rejectedCount = computed(() => store.orders.filter((o) => o.status === 'Ditolak').length);
const inProcessCount = computed(() => store.orders.filter((o) => o.status === 'Diproses').length);
const completedCount = computed(() => store.orders.filter((o) => o.status === 'Selesai').length);

const totalOrderValue = computed(() => store.orders.reduce((sum, o) => sum + o.grandTotal, 0));
const unpaidInvoicesCount = computed(() => store.invoices.filter((i) => i.status !== 'Lunas').length);
const totalPaidValue = computed(() => store.invoices.reduce((sum, i) => sum + i.paidAmount, 0));
const totalReceivableValue = computed(() => store.invoices.reduce((sum, i) => sum + i.remainingAmount, 0));

const lowStockCount = computed(() => store.products.filter((p) => p.stock <= p.minStock).length);
const activeShipmentsCount = computed(() => store.shipments.filter((s) => s.status === 'Sedang Dikirim').length);

const completeDocsOrders = computed(() => store.orders.filter((o) => o.documents && Object.values(o.documents).every((d) => d.status === 'Lengkap')).length);
const incompleteDocsOrders = computed(() => store.orders.length - completeDocsOrders.value);
const poCount = computed(() => store.orders.filter((o) => o.documents?.po?.exists).length);

const overdueInvoicesCount = computed(() => store.invoices.filter((i) => i.status === 'Jatuh Tempo').length);
const partialInvoicesCount = computed(() => store.invoices.filter((i) => i.status === 'Sebagian / Termin').length);
const paidInvoicesCount = computed(() => store.invoices.filter((i) => i.status === 'Lunas').length);

const approvedOrRejectedOrders = computed(() => store.orders.filter((o) => o.approvalDate));

const handleQuickApprove = (order) => {
  storeActions.approveOrder(order.id, 'Disetujui langsung oleh Direktur via Executive Dashboard.');
  toast.success(`Order ${order.orderNumber} berhasil disetujui.`);
};

const openRejectModal = (order) => {
  orderToReject.value = order;
  rejectReason.value = '';
  rejectModalOpen.value = true;
};

const executeReject = () => {
  if (orderToReject.value && rejectReason.value.trim()) {
    storeActions.rejectOrder(orderToReject.value.id, rejectReason.value.trim());
    toast.error(`Order ${orderToReject.value.orderNumber} telah ditolak.`);
    rejectModalOpen.value = false;
  }
};
</script>
