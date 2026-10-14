<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Berita Acara Serah Terima (BAST)</h1>
          <p class="text-sm text-slate-500 mt-1">
            Bukti legalitas formal penerimaan material & penuntasan pekerjaan fisik di site proyek.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
          @click="openCreateModal"
        >
          <Plus class="w-4 h-4" />
          <span>Buat BAST Baru</span>
        </button>
      </div>
    </template>

    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-900 uppercase">Daftar Dokumen BAST</span>
        <span class="text-slate-500">{{ bastList.length }} Berkas</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">Nomor BAST</th>
              <th class="py-3 px-4">No. Order</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">Tanggal Serah Terima</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="b in bastList" :key="b.number" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ b.number }}</td>
              <td class="py-3.5 px-4 font-mono text-slate-600">{{ b.orderNumber }}</td>
              <td class="py-3.5 px-4 font-bold text-slate-800">{{ b.clientName }}</td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ b.date }}</td>
              <td class="py-3.5 px-4 text-center"><StatusBadge :status="b.status" /></td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button type="button" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded" title="Download" @click="triggerAction('Download', b.number)">
                    <Download class="w-4 h-4" />
                  </button>
                  <button type="button" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded" title="Print" @click="triggerAction('Cetak', b.number)">
                    <Printer class="w-4 h-4" />
                  </button>
                  <button type="button" class="px-2 py-1 bg-alpharo-500 text-white rounded text-[11px] font-bold" @click="openDetail(b)">
                    Detail
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CREATE BAST MODAL -->
    <Modal v-model="createModalOpen" title="Buat Dokumen BAST" subtitle="Hubungkan dengan order serah terima proyek" size="md">
      <form class="space-y-4 text-xs" @submit.prevent="saveBast">
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Pilih Order</label>
          <select v-model="newBast.orderId" required class="w-full px-3 py-2 border rounded-lg bg-white">
            <option value="" disabled>-- Pilih Order --</option>
            <option v-for="o in approvedOrders" :key="o.id" :value="o.id">
              {{ o.orderNumber }} - {{ o.clientName }}
            </option>
          </select>
        </div>
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Nomor BAST</label>
          <input v-model="newBast.number" required placeholder="BAST-2026-XXX" class="w-full px-3 py-2 border rounded-lg" />
        </div>
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Tanggal Serah Terima</label>
          <input v-model="newBast.date" type="date" required class="w-full px-3 py-2 border rounded-lg bg-white" />
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button type="button" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg" @click="createModalOpen = false">Batal</button>
          <button type="submit" class="px-4 py-1.5 bg-alpharo-500 text-white font-bold rounded-lg">Terbitkan BAST</button>
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
const newBast = ref({ orderId: '', number: 'BAST-2026-004', date: new Date().toISOString().split('T')[0] });

const approvedOrders = computed(() => store.orders.filter((o) => o.status !== 'Draft' && o.status !== 'Ditolak'));

const bastList = computed(() => {
  return store.orders
    .filter((o) => o.documents?.bast?.exists)
    .map((o) => ({
      number: o.documents.bast.number,
      orderNumber: o.orderNumber,
      clientName: o.clientName,
      date: o.documents.bast.date,
      status: o.documents.bast.status,
    }));
});

const openCreateModal = () => {
  newBast.value = {
    orderId: approvedOrders.value[0]?.id || '',
    number: `BAST-2026-${String(Math.floor(100 + Math.random() * 900))}`,
    date: new Date().toISOString().split('T')[0],
  };
  createModalOpen.value = true;
};

const saveBast = () => {
  const order = store.orders.find((o) => o.id === newBast.value.orderId);
  if (order) {
    storeActions.generateDocument(order.orderNumber, 'bast', newBast.value.number);
    toast.success(`BAST ${newBast.value.number} berhasil diterbitkan.`);
    createModalOpen.value = false;
  }
};

const openDetail = (b) => {
  toast.info(`Detail BAST ${b.number} dibuka.`);
};

const triggerAction = (type, num) => {
  toast.info(`${type} berkas ${num} berhasil dijalankan.`);
};
</script>
