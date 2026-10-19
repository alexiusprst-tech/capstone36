<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Purchase Order (PO) Proyek</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pengelolaan surat pesanan resmi pengadaan material dan kontrak kerja.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
          @click="openCreateModal"
        >
          <Plus class="w-4 h-4" />
          <span>Buat PO Baru</span>
        </button>
      </div>
    </template>

    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-900 uppercase">Daftar Purchase Order</span>
        <span class="text-slate-500">{{ poList.length }} Berkas PO</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">PO Number</th>
              <th class="py-3 px-4">Order Number</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">Date</th>
              <th class="py-3 px-4 text-right">Amount</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="po in poList" :key="po.number" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ po.number }}</td>
              <td class="py-3.5 px-4 font-mono text-slate-600">{{ po.orderNumber }}</td>
              <td class="py-3.5 px-4 font-bold text-slate-800">{{ po.clientName }}</td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ po.date }}</td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                Rp {{ Number(po.amount || 0).toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :status="po.status" />
              </td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    class="p-1.5 text-slate-600 hover:bg-slate-100 rounded"
                    title="Download PDF"
                    @click="actionTrigger('Download', po.number)"
                  >
                    <Download class="w-4 h-4" />
                  </button>
                  <button
                    type="button"
                    class="p-1.5 text-slate-600 hover:bg-slate-100 rounded"
                    title="Cetak PO"
                    @click="actionTrigger('Cetak', po.number)"
                  >
                    <Printer class="w-4 h-4" />
                  </button>
                  <button
                    type="button"
                    class="px-2 py-1 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded text-[11px] font-bold"
                    @click="openDetail(po)"
                  >
                    Detail
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CREATE PO MODAL -->
    <Modal
      v-model="createModalOpen"
      title="Terbitkan Purchase Order Baru"
      subtitle="Hubungkan dokumen PO resmi dengan order yang disetujui"
      size="md"
    >
      <form class="space-y-4 text-xs" @submit.prevent="savePo">
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Pilih Order Disetujui</label>
          <select v-model="newPo.orderId" required class="w-full px-3 py-2 border rounded-lg bg-white">
            <option value="" disabled>-- Pilih Order --</option>
            <option v-for="o in approvedOrders" :key="o.id" :value="o.id">
              {{ o.orderNumber }} - {{ o.clientName }} (Rp {{ Number(o.grandTotal || 0).toLocaleString('id-ID') }})
            </option>
          </select>
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Nomor PO Resmi</label>
          <input v-model="newPo.number" required placeholder="PO-2026-XXX" class="w-full px-3 py-2 border rounded-lg" />
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Tanggal Terbit</label>
          <input v-model="newPo.date" type="date" required class="w-full px-3 py-2 border rounded-lg bg-white" />
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Upload Berkas Fisik (Opsional)</label>
          <input type="file" class="w-full px-3 py-2 border rounded-lg bg-slate-50" />
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button type="button" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg" @click="createModalOpen = false">
            Batal
          </button>
          <button type="submit" class="px-4 py-1.5 bg-alpharo-500 hover:bg-alpharo-600 text-white font-bold rounded-lg">
            Terbitkan PO
          </button>
        </div>
      </form>
    </Modal>

    <!-- PO DETAIL MODAL -->
    <Modal
      v-model="detailModalOpen"
      :title="`Berkas: ${selectedPo?.number || ''}`"
      :subtitle="selectedPo?.clientName || ''"
      size="md"
    >
      <div v-if="selectedPo" class="space-y-4 text-xs">
        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
          <div class="flex justify-between">
            <span class="text-slate-500">Nomor PO:</span>
            <span class="font-mono font-bold text-slate-900">{{ selectedPo.number }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Nomor Order:</span>
            <span class="font-mono text-slate-900">{{ selectedPo.orderNumber }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Rekanan Client:</span>
            <span class="font-bold text-slate-900">{{ selectedPo.clientName }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Tanggal Terbit:</span>
            <span class="text-slate-900">{{ selectedPo.date }}</span>
          </div>
          <div class="flex justify-between font-bold text-sm pt-2 border-t border-slate-200">
            <span>Nilai Kontrak PO:</span>
            <span class="font-mono text-alpharo-600">Rp {{ Number(selectedPo?.amount || 0).toLocaleString('id-ID') }}</span>
          </div>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import Modal from '../../components/common/Modal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import { Plus, Download, Printer } from 'lucide-vue-next';

const toast = useToast();

const createModalOpen = ref(false);
const detailModalOpen = ref(false);
const selectedPo = ref(null);

const newPo = ref({
  orderId: '',
  number: 'PO-2026-004',
  date: new Date().toISOString().split('T')[0],
});

const approvedOrders = computed(() => store.orders.filter((o) => o.status !== 'Draft' && o.status !== 'Ditolak'));

const poList = computed(() => {
  return store.orders
    .filter((o) => o.documents?.po?.exists)
    .map((o) => ({
      number: o.documents.po.number,
      orderNumber: o.orderNumber,
      clientName: o.clientName,
      date: o.documents.po.date,
      amount: o.grandTotal,
      status: o.documents.po.status,
    }));
});

const openCreateModal = () => {
  newPo.value = {
    orderId: approvedOrders.value[0]?.id || '',
    number: `PO-2026-${String(Math.floor(100 + Math.random() * 900))}`,
    date: new Date().toISOString().split('T')[0],
  };
  createModalOpen.value = true;
};

const savePo = () => {
  const order = store.orders.find((o) => o.id === newPo.value.orderId);
  if (order) {
    storeActions.generateDocument(order.orderNumber, 'po', newPo.value.number);
    toast.success(`Purchase Order ${newPo.value.number} berhasil diterbitkan.`);
    createModalOpen.value = false;
  }
};

const openDetail = (po) => {
  selectedPo.value = po;
  detailModalOpen.value = true;
};

const actionTrigger = (type, number) => {
  toast.info(`${type} berkas ${number} berhasil dijalankan.`);
};
</script>
