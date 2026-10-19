<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Order Disetujui (Approved Orders)</h1>
          <p class="text-sm text-slate-500 mt-1">
            Daftar order proyek yang telah disetujui Direktur dan siap untuk penyiapan berkas administrasi legal.
          </p>
        </div>
      </div>
    </template>

    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-900 uppercase">Daftar Order Siap Kelola Dokumen</span>
        <span class="text-slate-500">{{ approvedOrders.length }} Order Disetujui</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">No. Order</th>
              <th class="py-3 px-4">Client & Proyek</th>
              <th class="py-3 px-4">Tanggal Approval</th>
              <th class="py-3 px-4 text-right">Nilai Transaksi</th>
              <th class="py-3 px-4 text-center">Status Berkas</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="order in approvedOrders" :key="order.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ order.orderNumber }}</td>
              <td class="py-3.5 px-4">
                <span class="font-bold text-slate-900 block">{{ order.clientName }}</span>
                <span class="text-[11px] text-slate-400 block truncate max-w-xs">{{ order.projectName }}</span>
              </td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ order.approvalDate || order.date }}</td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                Rp {{ Number(order.grandTotal || 0).toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-center">
                <DocumentChecklist :documents="order.documents" />
              </td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <button
                  type="button"
                  class="px-3 py-1 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded text-xs font-bold"
                  @click="openDetail(order)"
                >
                  Detail & Kelola
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
      :title="`Kelola Dokumen: ${selectedOrder?.orderNumber || ''}`"
      :subtitle="selectedOrder?.clientName || ''"
      size="lg"
    >
      <div v-if="selectedOrder" class="space-y-6 text-xs">
        <DocumentChecklist :documents="selectedOrder.documents" />

        <div>
          <h4 class="font-bold uppercase text-slate-500 tracking-wider mb-2">Item Pekerjaan / Barang</h4>
          <div class="border border-slate-200 rounded-lg overflow-hidden">
            <table class="w-full text-left">
              <thead class="bg-slate-50 border-b border-slate-200 font-semibold text-slate-600">
                <tr>
                  <th class="p-2.5">Item</th>
                  <th class="p-2.5 text-center">Qty</th>
                  <th class="p-2.5 text-right">Harga Satuan</th>
                  <th class="p-2.5 text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="it in selectedOrder.items" :key="it.sku">
                  <td class="p-2.5">
                    <span class="font-medium text-slate-800">{{ it.name }}</span>
                    <span class="block text-[10px] text-slate-400 font-mono">{{ it.sku }}</span>
                  </td>
                  <td class="p-2.5 text-center">{{ it.qty }} {{ it.unit }}</td>
                  <td class="p-2.5 text-right">Rp {{ Number(it.price || 0).toLocaleString('id-ID') }}</td>
                  <td class="p-2.5 text-right font-bold">Rp {{ Number(it.total || ((it.qty || 0) * (it.price || 0))).toLocaleString('id-ID') }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
          <h4 class="font-bold uppercase text-slate-700 tracking-wider mb-2">Terbitkan / Perbarui Berkas:</h4>
          <div class="grid grid-cols-2 gap-2">
            <button
              type="button"
              class="p-2.5 bg-white border border-slate-200 hover:border-alpharo-500 rounded-lg text-left font-medium"
              @click="generateDoc('po')"
            >
              Terbitkan Purchase Order (PO)
            </button>
            <button
              type="button"
              class="p-2.5 bg-white border border-slate-200 hover:border-alpharo-500 rounded-lg text-left font-medium"
              @click="generateDoc('suratJalan')"
            >
              Terbitkan Surat Jalan
            </button>
            <button
              type="button"
              class="p-2.5 bg-white border border-slate-200 hover:border-alpharo-500 rounded-lg text-left font-medium"
              @click="generateDoc('bast')"
            >
              Terbitkan BAST
            </button>
            <button
              type="button"
              class="p-2.5 bg-white border border-slate-200 hover:border-alpharo-500 rounded-lg text-left font-medium"
              @click="generateDoc('faktur')"
            >
              Terbitkan Faktur Pajak
            </button>
          </div>
        </div>
      </div>
    </Drawer>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import DocumentChecklist from '../../components/common/DocumentChecklist.vue';
import Drawer from '../../components/common/Drawer.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';

const toast = useToast();

const drawerOpen = ref(false);
const selectedOrder = ref(null);

const approvedOrders = computed(() => {
  return store.orders.filter((o) => o.status === 'Disetujui' || o.status === 'Diproses' || o.status === 'Selesai');
});

const openDetail = (order) => {
  selectedOrder.value = order;
  drawerOpen.value = true;
};

const generateDoc = (docType) => {
  if (selectedOrder.value) {
    const docNum = `${docType.toUpperCase()}-2026-${String(Math.floor(100 + Math.random() * 900))}`;
    storeActions.generateDocument(selectedOrder.value.orderNumber, docType, docNum);
    toast.success(`Dokumen ${docType.toUpperCase()} berhasil dibuat!`);
  }
};
</script>
