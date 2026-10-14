<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Monitoring Keuangan & Piutang</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pengawasan arus kas masuk, tagihan piutang kontraktor, dan status realisasi termin pembayaran.
          </p>
        </div>
      </div>
    </template>

    <!-- KPIS FOR FINANCE MONITORING (SPECIFIED IN PROMPT) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
      <KpiCard title="Total Tagihan Invoice" :value="store.invoices.length" subtitle="Semua berkas tagihan" icon-variant="slate" />
      <KpiCard title="Belum Dibayar" :value="unpaidCount" subtitle="Menunggu pencairan" icon-variant="amber" />
      <KpiCard title="Jatuh Tempo" :value="overdueCount" subtitle="Perlu tindak lanjut" icon-variant="rose" />
      <KpiCard title="Pembayaran Sebagian" :value="partialCount" subtitle="Termin berjalan" icon-variant="blue" />
      <KpiCard title="Lunas Penuh" :value="paidCount" subtitle="Pembayaran rampung" icon-variant="emerald" />
      <KpiCard title="Total Piutang Berjalan" :value="totalReceivable" :is-currency="true" subtitle="Sisa tagihan rekanan" icon-variant="rose" />
    </div>

    <!-- INVOICE & RECEIVABLE MONITORING TABLE -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
        <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider">
          Portofolio Tagihan & Status Penerimaan Termin
        </h3>
        <span class="text-xs text-slate-500">Total Transaksi: Rp {{ totalInvoiceSum.toLocaleString('id-ID') }}</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
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
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="inv in store.invoices" :key="inv.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ inv.invoiceNumber }}</td>
              <td class="py-3.5 px-4 font-mono text-slate-600">{{ inv.orderNumber }}</td>
              <td class="py-3.5 px-4 font-bold text-slate-800">{{ inv.clientName }}</td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                Rp {{ inv.totalAmount.toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono text-emerald-600 font-semibold">
                Rp {{ inv.paidAmount.toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono text-rose-600 font-bold">
                Rp {{ inv.remainingAmount.toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ inv.dueDate }}</td>
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :status="inv.status" />
              </td>
              <td class="py-3.5 px-4 text-right">
                <button
                  type="button"
                  class="px-2.5 py-1 text-alpharo-500 hover:bg-blue-50 rounded border border-blue-200 font-bold text-xs"
                  @click="openInspect(inv)"
                >
                  Detail Termin
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- DETAIL DRAWER -->
    <Drawer
      v-model="drawerOpen"
      :title="`Rincian Keuangan: ${selectedInvoice?.invoiceNumber || ''}`"
      :subtitle="selectedInvoice?.clientName || ''"
      size="md"
    >
      <div v-if="selectedInvoice" class="space-y-6 text-xs">
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
          <div class="flex justify-between items-center">
            <span class="text-slate-500">Status Pembayaran</span>
            <StatusBadge :status="selectedInvoice.status" />
          </div>
          <div class="flex justify-between pt-2 border-t border-slate-200">
            <span class="text-slate-600">Total Nilai Tagihan:</span>
            <span class="font-bold font-mono text-slate-900">Rp {{ selectedInvoice.totalAmount.toLocaleString('id-ID') }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-600">Total Telah Diterima:</span>
            <span class="font-bold font-mono text-emerald-600">Rp {{ selectedInvoice.paidAmount.toLocaleString('id-ID') }}</span>
          </div>
          <div class="flex justify-between font-bold text-sm pt-1 border-t border-slate-200">
            <span>Sisa Piutang Berjalan:</span>
            <span class="font-mono text-rose-600">Rp {{ selectedInvoice.remainingAmount.toLocaleString('id-ID') }}</span>
          </div>
        </div>

        <div>
          <h4 class="font-bold uppercase text-slate-500 tracking-wider mb-3">Jadwal & Riwayat Termin</h4>
          <div class="border border-slate-200 rounded-lg overflow-hidden divide-y divide-slate-100">
            <div
              v-for="term in selectedInvoice.termins"
              :key="term.termNumber"
              class="p-3 bg-white flex items-center justify-between"
            >
              <div>
                <span class="font-bold text-slate-800">Termin Ke-{{ term.termNumber }}</span>
                <span class="block text-[11px] text-slate-500">Jatuh Tempo: {{ term.dueDate }}</span>
              </div>
              <div class="text-right">
                <span class="font-mono font-bold text-slate-900 block">Rp {{ term.amount.toLocaleString('id-ID') }}</span>
                <StatusBadge :status="term.status" />
              </div>
            </div>
          </div>
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
import { store } from '../../stores/mockData';

const drawerOpen = ref(false);
const selectedInvoice = ref(null);

const unpaidCount = computed(() => store.invoices.filter((i) => i.status === 'Belum Dibayar').length);
const overdueCount = computed(() => store.invoices.filter((i) => i.status === 'Jatuh Tempo').length);
const partialCount = computed(() => store.invoices.filter((i) => i.status === 'Sebagian / Termin').length);
const paidCount = computed(() => store.invoices.filter((i) => i.status === 'Lunas').length);

const totalInvoiceSum = computed(() => store.invoices.reduce((sum, i) => sum + i.totalAmount, 0));
const totalReceivable = computed(() => store.invoices.reduce((sum, i) => sum + i.remainingAmount, 0));

const openInspect = (inv) => {
  selectedInvoice.value = inv;
  drawerOpen.value = true;
};
</script>
