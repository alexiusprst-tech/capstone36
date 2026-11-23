<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Dashboard Keuangan & Finansial</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pengelolaan invoice penagihan, arus kas pembayaran tagihan, dan rekonsiliasi piutang usaha.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Link
            href="/finance/payments"
            class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
          >
            <CreditCard class="w-4 h-4" />
            <span>Pencatatan Pembayaran</span>
          </Link>
        </div>
      </div>
    </template>

    <!-- KPIS FOR FINANCE: 4 PRIMARY KPIS ACTION-ORIENTED -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <KpiCard
        title="Realisasi Kas Masuk"
        :value="totalPaymentValue"
        :is-currency="true"
        subtitle="Akumulasi dana diterima"
        :icon-component="Wallet"
        icon-variant="blue"
      />
      <KpiCard
        title="Total Piutang Berjalan"
        :value="totalReceivableValue"
        :is-currency="true"
        :subtitle="`${unpaidCount + partialCount} invoice belum lunas`"
        :icon-component="DollarSign"
        icon-variant="blue"
      />
      <KpiCard
        title="Tagihan Jatuh Tempo"
        :value="overdueCount"
        unit="Invoice"
        :subtitle="overdueCount > 0 ? 'Perlu follow-up penagihan' : 'Semua pembayaran tepat waktu'"
        :icon-component="AlertCircle"
        icon-variant="blue"
        :trend="overdueCount > 0 ? 'Perlu Tindakan' : 'Aman'"
        :trend-positive="overdueCount === 0"
      />
      <KpiCard
        title="Kebutuhan Gudang Pending"
        :value="pendingWarehouseRequests.length"
        unit="Pengajuan"
        :subtitle="pendingWarehouseRequests.length > 0 ? 'Menunggu pemilihan supplier & PO' : 'Semua pengadaan tertangani'"
        :icon-component="Building"
        icon-variant="blue"
      />
    </div>

    <!-- ACTION BANNER: PESANAN SIAP INVOICE DARI MARKETING / DIREKTUR -->
    <div
      v-if="readyOrders.length > 0"
      class="mb-6 p-4 bg-linear-to-r from-emerald-50 via-teal-50/60 to-emerald-50 border border-emerald-300 rounded-xl shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
    >
      <div class="flex items-center gap-3">
        <span class="p-2 bg-emerald-600 text-white rounded-lg shrink-0">
          <Receipt class="w-5 h-5" />
        </span>
        <div>
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="font-semibold text-emerald-950 text-sm">
              {{ readyOrders.length }} Pesanan Menunggu Penerbitan Invoice Resmi
            </span>
          </div>
          <p class="text-emerald-800 text-[11px] mt-0.5">
            Pesanan dari Marketing telah memenuhi verifikasi. Silakan terbitkan berkas invoice tagihan untuk rekanan kontraktor.
          </p>
        </div>
      </div>

      <Link
        href="/finance/invoices"
        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold rounded-lg text-xs shadow-2xs transition-colors shrink-0 cursor-pointer"
      >
        <span>Lihat & Terbitkan Invoice</span>
        <ChevronRight class="w-4 h-4" />
      </Link>
    </div>

    <!-- ACTION BANNER: PENGAJUAN BARANG GUDANG MENUNGGU SUPPLIER -->
    <div
      v-if="pendingWarehouseRequests.length > 0"
      class="mb-6 p-4 bg-linear-to-r from-amber-50 via-orange-50/60 to-amber-50 border border-amber-300 rounded-xl shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
    >
      <div class="flex items-center gap-3">
        <span class="p-2 bg-amber-500 text-white rounded-lg shrink-0">
          <Building class="w-5 h-5" />
        </span>
        <div>
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
            <span class="font-semibold text-amber-950 text-sm">
              {{ pendingWarehouseRequests.length }} Pengajuan Kebutuhan Gudang Menunggu Pemilihan Supplier
            </span>
          </div>
          <p class="text-amber-800 text-[11px] mt-0.5">
            Tim gudang telah mengajukan kebutuhan barang yang perlu dibeli ke vendor rekanan. Silakan pilih supplier dan terbitkan PO.
          </p>
        </div>
      </div>

      <Link
        href="/finance/purchases"
        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-alpharo-600 hover:bg-alpharo-700 active:bg-alpharo-800 text-white font-semibold rounded-lg text-xs shadow-2xs transition-colors shrink-0"
      >
        <span>Pilih Supplier & Beli</span>
        <ChevronRight class="w-4 h-4" />
      </Link>
    </div>

    <!-- MAIN TWO-COLUMN DASHBOARD SECTIONS -->
    <div class="dashboard-workspace min-w-0 flex flex-col gap-4 sm:gap-5">
      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-semibold text-slate-900 text-sm">Invoice Tagihan Terbaru</h3>
              <p class="text-xs text-slate-500">Daftar berkas tagihan yang diterbitkan ke rekanan kontraktor</p>
            </div>
            <Link href="/finance/invoices" class="text-xs font-semibold text-alpharo-500 hover:underline flex items-center gap-1">
              Semua Invoice <ChevronRight class="w-3.5 h-3.5" />
            </Link>
          </div>

          <div class="overflow-x-auto xl:max-h-[26rem] xl:overflow-y-auto overscroll-auto">
            <table class="ui-table-wide ui-table w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                  <th class="py-2.5 px-3">No. Invoice</th>
                  <th class="py-2.5 px-3">Client</th>
                  <th class="py-2.5 px-3 text-right">Nilai Total</th>
                  <th class="py-2.5 px-3 text-right">Sisa Tagihan</th>
                  <th class="py-2.5 px-3 text-center">Status</th>
                  <th class="py-2.5 px-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="inv in issuedInvoices.slice(0, 4)" :key="inv.id" class="hover:bg-slate-50/60 transition-colors">
                  <td class="py-3 px-3 font-mono font-semibold text-slate-900">{{ inv.invoiceNumber }}</td>
                  <td class="py-3 px-3 font-medium text-slate-800">{{ inv.clientName }}</td>
                  <td class="py-3 px-3 text-right font-mono font-semibold text-slate-900 whitespace-nowrap">
                    Rp {{ Number(inv.totalAmount || 0).toLocaleString('id-ID') }}
                  </td>
                  <td class="py-3 px-3 text-right font-mono text-rose-600 font-semibold whitespace-nowrap">
                    Rp {{ Number(inv.remainingAmount || 0).toLocaleString('id-ID') }}
                  </td>
                  <td class="py-3 px-3 text-center"><StatusBadge plain :status="inv.status" /></td>
                  <td class="py-3 px-3 text-right whitespace-nowrap">
                    <Link
                      href="/finance/payments"
                      class="px-2.5 py-1 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded font-semibold text-[11px]"
                    >
                      Bayar
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-semibold text-slate-900 text-sm">Penerimaan Pembayaran Terbaru</h3>
              <p class="text-xs text-slate-500">Riwayat transfer pembayaran tagihan invoice yang telah dicatat</p>
            </div>
            <Link href="/finance/payments" class="text-xs font-semibold text-alpharo-500 hover:underline flex items-center gap-1">
              Semua Pembayaran <ChevronRight class="w-3.5 h-3.5" />
            </Link>
          </div>

          <div class="overflow-x-auto xl:max-h-[26rem] xl:overflow-y-auto overscroll-auto">
            <table class="ui-table-wide ui-table w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                  <th class="py-2.5 px-3">No. Kwitansi</th>
                  <th class="py-2.5 px-3">Client</th>
                  <th class="py-2.5 px-3">Jenis Bayar</th>
                  <th class="py-2.5 px-3 text-right">Jumlah Diterima</th>
                  <th class="py-2.5 px-3">Metode</th>
                  <th class="py-2.5 px-3 text-center">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="pay in store.payments.slice(0, 4)" :key="pay.id" class="hover:bg-slate-50/60 transition-colors">
                  <td class="py-3 px-3 font-mono font-semibold text-slate-900">{{ pay.paymentNumber }}</td>
                  <td class="py-3 px-3 font-medium text-slate-800">{{ pay.clientName }}</td>
                  <td class="py-3 px-3">
                    <span class="font-medium text-slate-800">{{ pay.paymentType }}</span>
                  </td>
                  <td class="py-3 px-3 text-right font-mono font-semibold text-emerald-600 whitespace-nowrap">
                    Rp {{ Number(pay.amount || 0).toLocaleString('id-ID') }}
                  </td>
                  <td class="py-3 px-3 text-slate-600">{{ pay.paymentMethod }}</td>
                  <td class="py-3 px-3 text-center"><StatusBadge plain status="Lunas" /></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <h3 class="font-semibold text-slate-900 text-sm mb-3 pb-2 border-b border-slate-100">
            Ringkasan Tagihan Piutang Mitra
          </h3>

          <div class="divide-y divide-slate-100 text-xs xl:max-h-80 xl:overflow-y-auto overscroll-auto">
            <div
              v-for="inv in issuedInvoices.filter(i => (i.remainingAmount || 0) > 0)"
              :key="inv.id"
              class="py-3 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-4"
            >
              <div class="min-w-0 flex-1">
                <span class="font-semibold text-slate-900 block">{{ inv.clientName }}</span>
                <span class="font-mono text-[11px] text-slate-400">{{ inv.invoiceNumber }} &bull; {{ inv.orderNumber }}</span>
              </div>
              <div class="shrink-0 text-right">
                <span class="font-mono font-semibold text-rose-600 block">
                  Rp {{ Number(inv.remainingAmount || 0).toLocaleString('id-ID') }}
                </span>
                <span class="text-[11px] text-slate-400">Dari Rp {{ Number(inv.totalAmount || 0).toLocaleString('id-ID') }}</span>
              </div>
            </div>
          </div>
        </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
            <div class="flex items-center gap-2">
              <AlertCircle class="w-4 h-4 text-rose-500" />
              <h3 class="font-semibold text-slate-900 text-sm">Invoice Mendekati / Jatuh Tempo</h3>
            </div>
            <Link href="/finance/receivables" class="text-xs text-alpharo-500 font-semibold hover:underline">
              Piutang →
            </Link>
          </div>

          <div class="divide-y divide-slate-100 text-xs xl:max-h-80 xl:overflow-y-auto overscroll-auto">
            <div
              v-for="inv in dueInvoices"
              :key="inv.id"
              class="py-3 flex items-start justify-between gap-4"
            >
              <div class="min-w-0 flex-1">
                <span class="font-mono font-semibold text-slate-900 block">{{ inv.invoiceNumber }}</span>
                <span class="font-medium text-slate-800">{{ inv.clientName }}</span>
                <span class="block text-[11px] text-rose-600 mt-0.5">Jatuh Tempo: {{ inv.dueDate }}</span>
              </div>
              <div class="shrink-0 text-right">
                <span class="font-mono font-semibold text-slate-900 block">
                  Rp {{ Number(inv.remainingAmount || 0).toLocaleString('id-ID') }}
                </span>
                <StatusBadge plain :status="inv.status" />
              </div>
            </div>

            <div v-if="dueInvoices.length === 0" class="p-4 text-center text-slate-400">
              ✓ Tidak ada invoice yang melewati masa jatuh tempo.
            </div>
          </div>
        </div>

      <ActivityLogCard class="dashboard-activity" />
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import KpiCard from '../../components/common/KpiCard.vue';
import ActivityLogCard from '../../components/common/ActivityLogCard.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import { store, storeActions, readyInvoiceOrders as readyOrders, issuedInvoices } from '../../stores/mockData';
import {
  ChevronRight,
  AlertCircle,
  CreditCard,
  FileText,
  Clock,
  CheckCircle2,
  Receipt,
  Wallet,
  DollarSign,
  Building,
} from 'lucide-vue-next';

onMounted(() => {
  storeActions.loadOrders();
});

const unpaidCount = computed(() => issuedInvoices.value.filter((i) => i.status === 'Belum Dibayar').length);
const overdueCount = computed(() => issuedInvoices.value.filter((i) => i.status === 'Jatuh Tempo').length);
const partialCount = computed(() => issuedInvoices.value.filter((i) => i.status === 'Sebagian' || i.status === 'Sebagian / Termin').length);
const paidCount = computed(() => issuedInvoices.value.filter((i) => i.status === 'Lunas').length);

const totalInvoiceValue = computed(() => issuedInvoices.value.reduce((sum, i) => sum + Number(i.totalAmount || 0), 0));
const totalPaymentValue = computed(() => store.payments.reduce((sum, p) => sum + Number(p.amount || 0), 0));
const totalReceivableValue = computed(() => issuedInvoices.value.reduce((sum, i) => sum + Number(i.remainingAmount || 0), 0));

const pendingWarehouseRequests = computed(() => {
  return (store.warehouseRequests || []).filter(
    (r) => r.status === 'MENUNGGU_FINANCE' || r.status === 'Draft' || r.status === 'Menunggu Verifikasi'
  );
});

const dueInvoices = computed(() => issuedInvoices.value.filter((i) => i.status === 'Jatuh Tempo' || ((i.status === 'Sebagian' || i.status === 'Sebagian / Termin') && i.remainingAmount > 0)));
</script>
