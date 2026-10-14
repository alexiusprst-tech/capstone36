<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Laporan Eksekutif & Analitik</h1>
          <p class="text-sm text-slate-500 mt-1">
            Kompilasi laporan terintegrasi lintas divisi CV Alpharo Artha Jaya siap cetak & ekspor.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
            @click="exportReport('Excel')"
          >
            <Download class="w-4 h-4" />
            <span>Ekspor Excel (.xlsx)</span>
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
            @click="exportReport('PDF')"
          >
            <Printer class="w-4 h-4" />
            <span>Cetak PDF (.pdf)</span>
          </button>
        </div>
      </div>
    </template>

    <!-- REPORT CATEGORIES TABS -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-5">
      <button
        v-for="cat in categories"
        :key="cat"
        type="button"
        :class="[
          selectedCategory === cat
            ? 'bg-alpharo-500 text-white font-bold shadow-xs'
            : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200',
          'px-4 py-2 rounded-xl text-xs whitespace-nowrap transition-all'
        ]"
        @click="selectedCategory = cat"
      >
        {{ cat }}
      </button>
    </div>

    <!-- FILTER BAR -->
    <div class="bg-white p-4 rounded-xl border border-slate-200/90 mb-5 grid grid-cols-1 sm:grid-cols-4 gap-3 shadow-2xs text-xs">
      <div>
        <label class="block font-semibold text-slate-600 mb-1">Cari Kata Kunci</label>
        <div class="relative">
          <Search class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari data..."
            class="w-full pl-8 pr-3 py-2 border border-slate-300 rounded-lg text-xs"
          />
        </div>
      </div>

      <div>
        <label class="block font-semibold text-slate-600 mb-1">Filter Client</label>
        <select v-model="clientFilter" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs bg-white">
          <option value="">Semua Client</option>
          <option v-for="c in store.clients" :key="c.id" :value="c.name">{{ c.name }}</option>
        </select>
      </div>

      <div>
        <label class="block font-semibold text-slate-600 mb-1">Dari Tanggal</label>
        <input v-model="startDate" type="date" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs bg-white" />
      </div>

      <div>
        <label class="block font-semibold text-slate-600 mb-1">Sampai Tanggal</label>
        <input v-model="endDate" type="date" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs bg-white" />
      </div>
    </div>

    <!-- DYNAMIC REPORT TABLE -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-900 uppercase">Hasil Rekapitulasi: Laporan {{ selectedCategory }}</span>
        <span class="text-slate-500">Menampilkan {{ reportData.length }} baris data</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th v-for="col in tableColumns" :key="col.key" :class="[col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '', 'py-3 px-4']">
                {{ col.label }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(row, rIdx) in reportData" :key="rIdx" class="hover:bg-slate-50/70 transition-colors">
              <td v-for="col in tableColumns" :key="col.key" :class="[col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '', 'py-3.5 px-4 font-medium']">
                <StatusBadge v-if="col.isBadge" :status="row[col.key]" />
                <span v-else-if="col.isCurrency" class="font-mono font-bold text-slate-900">
                  Rp {{ (row[col.key] || 0).toLocaleString('id-ID') }}
                </span>
                <span v-else>{{ row[col.key] }}</span>
              </td>
            </tr>

            <tr v-if="reportData.length === 0">
              <td :colspan="tableColumns.length" class="p-12 text-center text-slate-400">
                Tidak ada data pada kategori laporan ini.
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
import { Download, Printer, Search } from 'lucide-vue-next';

const toast = useToast();

const categories = [
  'Order',
  'Client',
  'Barang',
  'Fulfillment',
  'Dokumen',
  'Invoice',
  'Pembayaran',
  'Piutang',
];

const selectedCategory = ref('Order');
const searchQuery = ref('');
const clientFilter = ref('');
const startDate = ref('2026-09-01');
const endDate = ref('2026-10-31');

const tableColumns = computed(() => {
  switch (selectedCategory.value) {
    case 'Client':
      return [
        { key: 'code', label: 'Kode Client' },
        { key: 'name', label: 'Nama Client' },
        { key: 'type', label: 'Jenis' },
        { key: 'contactPerson', label: 'PIC' },
        { key: 'totalOrders', label: 'Total Order', align: 'center' },
        { key: 'totalTransaction', label: 'Total Transaksi', isCurrency: true, align: 'right' },
        { key: 'status', label: 'Status', isBadge: true, align: 'center' },
      ];
    case 'Barang':
      return [
        { key: 'sku', label: 'SKU' },
        { key: 'name', label: 'Nama Barang' },
        { key: 'category', label: 'Kategori' },
        { key: 'stock', label: 'Stok', align: 'center' },
        { key: 'unit', label: 'Satuan', align: 'center' },
        { key: 'price', label: 'Harga Satuan', isCurrency: true, align: 'right' },
        { key: 'status', label: 'Status', isBadge: true, align: 'center' },
      ];
    case 'Fulfillment':
      return [
        { key: 'orderNumber', label: 'No. Order' },
        { key: 'clientName', label: 'Client' },
        { key: 'checkedBy', label: 'PIC Gudang' },
        { key: 'notes', label: 'Keterangan' },
        { key: 'status', label: 'Status Pemenuhan', isBadge: true, align: 'center' },
      ];
    case 'Invoice':
      return [
        { key: 'invoiceNumber', label: 'No. Invoice' },
        { key: 'orderNumber', label: 'No. Order' },
        { key: 'clientName', label: 'Client' },
        { key: 'invoiceDate', label: 'Tanggal' },
        { key: 'dueDate', label: 'Jatuh Tempo' },
        { key: 'totalAmount', label: 'Nilai Invoice', isCurrency: true, align: 'right' },
        { key: 'status', label: 'Status', isBadge: true, align: 'center' },
      ];
    case 'Pembayaran':
      return [
        { key: 'paymentNumber', label: 'No. Kwitansi' },
        { key: 'invoiceNumber', label: 'No. Invoice' },
        { key: 'clientName', label: 'Client' },
        { key: 'paymentDate', label: 'Tanggal Bayar' },
        { key: 'paymentType', label: 'Jenis Bayar' },
        { key: 'amount', label: 'Jumlah Diterima', isCurrency: true, align: 'right' },
        { key: 'status', label: 'Status', isBadge: true, align: 'center' },
      ];
    case 'Piutang':
      return [
        { key: 'clientName', label: 'Client' },
        { key: 'invoiceNumber', label: 'No. Invoice' },
        { key: 'totalAmount', label: 'Total Tagihan', isCurrency: true, align: 'right' },
        { key: 'paidAmount', label: 'Terbayar', isCurrency: true, align: 'right' },
        { key: 'remainingAmount', label: 'Sisa Piutang', isCurrency: true, align: 'right' },
        { key: 'dueDate', label: 'Jatuh Tempo' },
        { key: 'status', label: 'Status', isBadge: true, align: 'center' },
      ];
    default: // Order
      return [
        { key: 'orderNumber', label: 'No. Order' },
        { key: 'clientName', label: 'Client' },
        { key: 'projectName', label: 'Proyek' },
        { key: 'date', label: 'Tanggal' },
        { key: 'submittedBy', label: 'Staff' },
        { key: 'grandTotal', label: 'Nilai Transaksi', isCurrency: true, align: 'right' },
        { key: 'status', label: 'Status', isBadge: true, align: 'center' },
      ];
  }
});

const reportData = computed(() => {
  switch (selectedCategory.value) {
    case 'Client':
      return store.clients;
    case 'Barang':
      return store.products;
    case 'Fulfillment':
      return store.warehouseFulfillments;
    case 'Invoice':
      return store.invoices;
    case 'Pembayaran':
      return store.payments;
    case 'Piutang':
      return store.invoices.filter((i) => i.remainingAmount > 0);
    default:
      return store.orders;
  }
});

const exportReport = (format) => {
  toast.success(`Laporan ${selectedCategory.value} berhasil di-generate ke format ${format}! Berkas siap diunduh.`);
};
</script>
