<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Laporan Keuangan & Piutang</h1>
          <p class="text-sm text-slate-500 mt-1">
            Rekapitulasi transaksi keuangan: Invoice, Pembayaran, Piutang, dan Transaksi Proyek.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
            @click="exportDoc('Excel')"
          >
            <Download class="w-4 h-4" />
            <span>Ekspor Excel (.xlsx)</span>
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
            @click="exportDoc('PDF')"
          >
            <Printer class="w-4 h-4" />
            <span>Cetak PDF</span>
          </button>
        </div>
      </div>
    </template>

    <!-- TABS -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-5">
      <button
        v-for="cat in ['Invoice', 'Pembayaran', 'Piutang', 'Transaksi']"
        :key="cat"
        type="button"
        :class="[
          selectedCat === cat
            ? 'bg-alpharo-500 text-white font-bold shadow-xs'
            : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200',
          'px-4 py-2 rounded-xl text-xs whitespace-nowrap transition-all'
        ]"
        @click="selectedCat = cat"
      >
        Laporan {{ cat }}
      </button>
    </div>

    <!-- TABLE -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-900 uppercase">Rekapitulasi {{ selectedCat }}</span>
        <span class="text-slate-500">{{ listData.length }} Data Ditemukan</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th v-for="c in cols" :key="c.key" :class="[c.align === 'right' ? 'text-right' : c.align === 'center' ? 'text-center' : '', 'py-3 px-4']">
                {{ c.label }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(row, idx) in listData" :key="idx" class="hover:bg-slate-50/70 transition-colors">
              <td v-for="c in cols" :key="c.key" :class="[c.align === 'right' ? 'text-right' : c.align === 'center' ? 'text-center' : '', 'py-3.5 px-4']">
                <StatusBadge v-if="c.isBadge" :status="row[c.key]" />
                <span v-else-if="c.isCurrency" class="font-mono font-bold text-slate-900">
                  Rp {{ (row[c.key] || 0).toLocaleString('id-ID') }}
                </span>
                <span v-else>{{ row[c.key] }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import { store } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import { Download, Printer } from 'lucide-vue-next';

const toast = useToast();
const selectedCat = ref('Invoice');

const cols = computed(() => {
  if (selectedCat.value === 'Pembayaran') {
    return [
      { key: 'paymentNumber', label: 'No. Kwitansi' },
      { key: 'invoiceNumber', label: 'No. Invoice' },
      { key: 'clientName', label: 'Client' },
      { key: 'paymentDate', label: 'Tanggal' },
      { key: 'amount', label: 'Jumlah Diterima', isCurrency: true, align: 'right' },
      { key: 'paymentMethod', label: 'Metode' },
      { key: 'status', label: 'Status', isBadge: true, align: 'center' },
    ];
  }
  if (selectedCat.value === 'Piutang') {
    return [
      { key: 'clientName', label: 'Client' },
      { key: 'invoiceNumber', label: 'No. Invoice' },
      { key: 'totalAmount', label: 'Total Tagihan', isCurrency: true, align: 'right' },
      { key: 'remainingAmount', label: 'Sisa Piutang', isCurrency: true, align: 'right' },
      { key: 'dueDate', label: 'Jatuh Tempo' },
      { key: 'status', label: 'Status', isBadge: true, align: 'center' },
    ];
  }
  if (selectedCat.value === 'Transaksi') {
    return [
      { key: 'orderNumber', label: 'No. Order' },
      { key: 'clientName', label: 'Client' },
      { key: 'date', label: 'Tanggal' },
      { key: 'grandTotal', label: 'Nilai Transaksi', isCurrency: true, align: 'right' },
      { key: 'status', label: 'Status', isBadge: true, align: 'center' },
    ];
  }
  return [
    { key: 'invoiceNumber', label: 'No. Invoice' },
    { key: 'clientName', label: 'Client' },
    { key: 'invoiceDate', label: 'Tanggal' },
    { key: 'dueDate', label: 'Jatuh Tempo' },
    { key: 'totalAmount', label: 'Nilai Total', isCurrency: true, align: 'right' },
    { key: 'paidAmount', label: 'Terbayar', isCurrency: true, align: 'right' },
    { key: 'status', label: 'Status', isBadge: true, align: 'center' },
  ];
});

const listData = computed(() => {
  if (selectedCat.value === 'Pembayaran') return store.payments;
  if (selectedCat.value === 'Piutang') return store.invoices.filter((i) => i.remainingAmount > 0);
  if (selectedCat.value === 'Transaksi') return store.orders;
  return store.invoices;
});

const exportDoc = (fmt) => {
  toast.success(`Laporan ${selectedCat.value} berhasil di-generate ke format ${fmt}!`);
};
</script>
