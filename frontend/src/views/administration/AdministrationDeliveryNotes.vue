<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Surat Jalan Pengiriman (Delivery Notes)</h1>
          <p class="text-sm text-slate-500 mt-1">
            Dokumen legal pengantar barang dari gudang menuju lokasi proyek konstruksi client.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
          @click="openCreateModal"
        >
          <Plus class="w-4 h-4" />
          <span>Buat Surat Jalan</span>
        </button>
      </div>
    </template>

    <!-- VISUAL RELATIONSHIP BANNER: ORDER -> SURAT JALAN -> PENGIRIMAN -->
    <div class="p-4 bg-white rounded-xl border border-slate-200/90 mb-6 shadow-2xs">
      <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">Relasi Proses Pengiriman Barang:</h3>
      <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div class="flex-1 w-full bg-slate-50 p-3 rounded-lg border border-slate-200 text-center">
          <span class="text-slate-400 text-[10px] uppercase font-bold block">Tahap 1</span>
          <span class="font-bold text-slate-900 text-sm">Order Disetujui Direktur</span>
          <p class="text-[11px] text-slate-500 mt-0.5">Kontrak & volume barang divalidasi</p>
        </div>
        <span class="text-slate-400 font-bold hidden sm:inline">➔</span>
        <div class="flex-1 w-full bg-blue-50 p-3 rounded-lg border border-blue-200 text-center">
          <span class="text-alpharo-500 text-[10px] uppercase font-bold block">Tahap 2 (Administrasi)</span>
          <span class="font-bold text-slate-900 text-sm">Surat Jalan Diterbitkan</span>
          <p class="text-[11px] text-slate-500 mt-0.5">Dokumen jalan resmi 3 rangkap dicetak</p>
        </div>
        <span class="text-slate-400 font-bold hidden sm:inline">➔</span>
        <div class="flex-1 w-full bg-emerald-50 p-3 rounded-lg border border-emerald-200 text-center">
          <span class="text-emerald-600 text-[10px] uppercase font-bold block">Tahap 3 (Warehouse)</span>
          <span class="font-bold text-slate-900 text-sm">Armada Pengiriman Berangkat</span>
          <p class="text-[11px] text-slate-500 mt-0.5">Barang dimuat dan dikonfirmasi sopir</p>
        </div>
      </div>
    </div>

    <!-- DELIVERY NOTES TABLE -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-900 uppercase">Daftar Surat Jalan</span>
        <span class="text-slate-500">{{ sjList.length }} Berkas Surat Jalan</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">Nomor SJ</th>
              <th class="py-3 px-4">No. Order</th>
              <th class="py-3 px-4">Client Penerima</th>
              <th class="py-3 px-4">Tanggal Terbit</th>
              <th class="py-3 px-4 text-center">Status Berkas</th>
              <th class="py-3 px-4 text-center">Status Kirim</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="sj in sjList" :key="sj.number" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ sj.number }}</td>
              <td class="py-3.5 px-4 font-mono text-slate-600">{{ sj.orderNumber }}</td>
              <td class="py-3.5 px-4 font-bold text-slate-800">{{ sj.clientName }}</td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ sj.date }}</td>
              <td class="py-3.5 px-4 text-center"><StatusBadge :status="sj.status" /></td>
              <td class="py-3.5 px-4 text-center"><StatusBadge :status="sj.shipmentStatus" /></td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    class="p-1.5 text-slate-600 hover:bg-slate-100 rounded"
                    title="Cetak Surat Jalan"
                    @click="printSj(sj.number)"
                  >
                    <Printer class="w-4 h-4" />
                  </button>
                  <button
                    type="button"
                    class="px-2 py-1 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded text-[11px] font-bold"
                    @click="openDetail(sj)"
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

    <!-- CREATE SJ MODAL -->
    <Modal
      v-model="createModalOpen"
      title="Terbitkan Surat Jalan Baru"
      subtitle="Dokumen pengantar untuk order proyek disetujui"
      size="md"
    >
      <form class="space-y-4 text-xs" @submit.prevent="saveSj">
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Pilih Order Disetujui</label>
          <select v-model="newSj.orderId" required class="w-full px-3 py-2 border rounded-lg bg-white">
            <option value="" disabled>-- Pilih Order --</option>
            <option v-for="o in approvedOrders" :key="o.id" :value="o.id">
              {{ o.orderNumber }} - {{ o.clientName }}
            </option>
          </select>
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Nomor Surat Jalan</label>
          <input v-model="newSj.number" required placeholder="SJ-2026-XXX" class="w-full px-3 py-2 border rounded-lg" />
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Tanggal Kirim / Terbit</label>
          <input v-model="newSj.date" type="date" required class="w-full px-3 py-2 border rounded-lg bg-white" />
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button type="button" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg" @click="createModalOpen = false">
            Batal
          </button>
          <button type="submit" class="px-4 py-1.5 bg-alpharo-500 hover:bg-alpharo-600 text-white font-bold rounded-lg">
            Simpan & Terbitkan
          </button>
        </div>
      </form>
    </Modal>

    <!-- SJ DETAIL MODAL -->
    <Modal
      v-model="detailModalOpen"
      :title="`Surat Jalan: ${selectedSj?.number || ''}`"
      :subtitle="selectedSj?.clientName || ''"
      size="md"
    >
      <div v-if="selectedSj" class="space-y-4 text-xs">
        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
          <div class="flex justify-between">
            <span class="text-slate-500">Nomor Surat Jalan:</span>
            <span class="font-mono font-bold text-slate-900">{{ selectedSj.number }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Nomor Order:</span>
            <span class="font-mono text-slate-900">{{ selectedSj.orderNumber }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Penerima:</span>
            <span class="font-bold text-slate-900">{{ selectedSj.clientName }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Status Pengiriman:</span>
            <StatusBadge :status="selectedSj.shipmentStatus" />
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
import { Plus, Printer } from 'lucide-vue-next';

const toast = useToast();

const createModalOpen = ref(false);
const detailModalOpen = ref(false);
const selectedSj = ref(null);

const newSj = ref({
  orderId: '',
  number: 'SJ-2026-004',
  date: new Date().toISOString().split('T')[0],
});

const approvedOrders = computed(() => store.orders.filter((o) => o.status !== 'Draft' && o.status !== 'Ditolak'));

const sjList = computed(() => {
  return store.orders
    .filter((o) => o.documents?.suratJalan?.exists)
    .map((o) => ({
      number: o.documents.suratJalan.number,
      orderNumber: o.orderNumber,
      clientName: o.clientName,
      date: o.documents.suratJalan.date,
      status: o.documents.suratJalan.status,
      shipmentStatus: o.fulfillmentStatus || 'Siap Dikirim',
    }));
});

const openCreateModal = () => {
  newSj.value = {
    orderId: approvedOrders.value[0]?.id || '',
    number: `SJ-2026-${String(Math.floor(100 + Math.random() * 900))}`,
    date: new Date().toISOString().split('T')[0],
  };
  createModalOpen.value = true;
};

const saveSj = () => {
  const order = store.orders.find((o) => o.id === newSj.value.orderId);
  if (order) {
    storeActions.generateDocument(order.orderNumber, 'suratJalan', newSj.value.number);
    toast.success(`Surat Jalan ${newSj.value.number} berhasil diterbitkan.`);
    createModalOpen.value = false;
  }
};

const openDetail = (sj) => {
  selectedSj.value = sj;
  detailModalOpen.value = true;
};

const printSj = (num) => {
  toast.info(`Perintah cetak 3 rangkap untuk ${num} dikirim.`);
};
</script>
