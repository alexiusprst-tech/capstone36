<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Faktur Pajak & Administrasi</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pengelolaan faktur pajak e-Faktur dan faktur komersial proyek rekanan.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
          @click="openCreateModal"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Faktur</span>
        </button>
      </div>
    </template>

    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-900 uppercase">Daftar Faktur Pajak Terbit</span>
        <span class="text-slate-500">{{ fakturList.length }} Berkas Faktur</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">Nomor Faktur</th>
              <th class="py-3 px-4">No. Order</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">Tanggal Faktur</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="f in fakturList" :key="f.number" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ f.number }}</td>
              <td class="py-3.5 px-4 font-mono text-slate-600">{{ f.orderNumber }}</td>
              <td class="py-3.5 px-4 font-bold text-slate-800">{{ f.clientName }}</td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ f.date }}</td>
              <td class="py-3.5 px-4 text-center"><StatusBadge :status="f.status" /></td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button type="button" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded" title="Download" @click="triggerAction('Download', f.number)">
                    <Download class="w-4 h-4" />
                  </button>
                  <button type="button" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded" title="Print" @click="triggerAction('Cetak', f.number)">
                    <Printer class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CREATE FAKTUR MODAL -->
    <Modal v-model="createModalOpen" title="Tambah Faktur Pajak Baru" subtitle="Penerbitan faktur administrasi order" size="md">
      <form class="space-y-4 text-xs" @submit.prevent="saveFaktur">
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Pilih Order</label>
          <select v-model="newFaktur.orderId" required class="w-full px-3 py-2 border rounded-lg bg-white">
            <option value="" disabled>-- Pilih Order --</option>
            <option v-for="o in approvedOrders" :key="o.id" :value="o.id">
              {{ o.orderNumber }} - {{ o.clientName }}
            </option>
          </select>
        </div>
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Nomor Seri Faktur Pajak (NSFP)</label>
          <input v-model="newFaktur.number" required placeholder="010.002-26.000001" class="w-full px-3 py-2 border rounded-lg font-mono" />
        </div>
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Tanggal Faktur</label>
          <input v-model="newFaktur.date" type="date" required class="w-full px-3 py-2 border rounded-lg bg-white" />
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button type="button" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg" @click="createModalOpen = false">Batal</button>
          <button type="submit" class="px-4 py-1.5 bg-alpharo-500 text-white font-bold rounded-lg">Terbitkan Faktur</button>
        </div>
      </form>
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
const newFaktur = ref({ orderId: '', number: 'FK-2026-004', date: new Date().toISOString().split('T')[0] });

const approvedOrders = computed(() => store.orders.filter((o) => o.status !== 'Draft' && o.status !== 'Ditolak'));

const fakturList = computed(() => {
  return store.orders
    .filter((o) => o.documents?.faktur?.exists)
    .map((o) => ({
      number: o.documents.faktur.number,
      orderNumber: o.orderNumber,
      clientName: o.clientName,
      date: o.documents.faktur.date,
      status: o.documents.faktur.status,
    }));
});

const openCreateModal = () => {
  newFaktur.value = {
    orderId: approvedOrders.value[0]?.id || '',
    number: `010.002-26.${String(Math.floor(100000 + Math.random() * 900000))}`,
    date: new Date().toISOString().split('T')[0],
  };
  createModalOpen.value = true;
};

const saveFaktur = () => {
  const order = store.orders.find((o) => o.id === newFaktur.value.orderId);
  if (order) {
    storeActions.generateDocument(order.orderNumber, 'faktur', newFaktur.value.number);
    toast.success(`Faktur ${newFaktur.value.number} berhasil diterbitkan.`);
    createModalOpen.value = false;
  }
};

const triggerAction = (type, num) => {
  toast.info(`${type} faktur ${num} berhasil diproses.`);
};
</script>
