<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pengiriman Armada (Shipments)</h1>
          <p class="text-sm text-slate-500 mt-1">
            Penjadwalan keberangkatan truk ekspedisi, pelacakan driver, dan konfirmasi barang tiba di lokasi proyek.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
          @click="openDispatchModal"
        >
          <Truck class="w-4 h-4" />
          <span>Berangkatkan Pengiriman Baru</span>
        </button>
      </div>
    </template>

    <!-- STATUS TABS -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-5">
      <button
        v-for="st in ['Semua', 'Siap Dikirim', 'Sedang Dikirim', 'Selesai']"
        :key="st"
        type="button"
        :class="[
          activeFilter === st
            ? 'bg-alpharo-500 text-white font-bold shadow-xs'
            : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200',
          'px-4 py-2 rounded-xl text-xs whitespace-nowrap transition-all'
        ]"
        @click="activeFilter = st"
      >
        {{ st }}
      </button>
    </div>

    <!-- SHIPMENTS TABLE -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">No. Pengiriman</th>
              <th class="py-3 px-4">No. Order</th>
              <th class="py-3 px-4">Client Penerima</th>
              <th class="py-3 px-4">Alamat Proyek Site</th>
              <th class="py-3 px-4">Armada / Driver</th>
              <th class="py-3 px-4">Waktu Berangkat</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="s in filteredShipments" :key="s.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ s.shipmentNumber }}</td>
              <td class="py-3.5 px-4">
                <span class="font-mono text-slate-700 font-medium block">{{ s.orderNumber }}</span>
                <span
                  v-if="getOrderHold(s.orderNumber)"
                  class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200 mt-0.5"
                  :title="getOrderHold(s.orderNumber)?.holdReason"
                >
                  ⛔ Ditahan Direktur
                </span>
              </td>
              <td class="py-3.5 px-4 font-bold text-slate-800">{{ s.clientName }}</td>
              <td class="py-3.5 px-4 text-slate-600 truncate max-w-xs">{{ s.destination }}</td>
              <td class="py-3.5 px-4">
                <div class="font-medium text-slate-900">{{ s.driverName }}</div>
                <div class="text-[11px] text-slate-400">{{ s.expedition }}</div>
              </td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ s.departureDate }}</td>
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :status="s.status" />
              </td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    v-if="s.status === 'Sedang Dikirim'"
                    type="button"
                    class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold text-xs"
                    @click="confirmDelivered(s)"
                  >
                    Konfirmasi Tiba
                  </button>
                  <button
                    type="button"
                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold text-xs"
                    @click="openDetail(s)"
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

    <!-- DISPATCH MODAL -->
    <Modal v-model="dispatchModalOpen" title="Berangkatkan Armada Pengiriman" subtitle="Buat jadwal keberangkatan armada & driver ke site proyek" size="md">
      <form class="space-y-4 text-xs" @submit.prevent="saveDispatch">
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Pilih Order Siap Kirim</label>
          <select v-model="form.orderNumber" required class="w-full px-3 py-2 border rounded-lg bg-white" @change="onOrderSelect">
            <option v-for="o in allCandidateOrders" :key="o.orderNumber" :value="o.orderNumber" :disabled="o.isHold">
              {{ o.orderNumber }} - {{ o.clientName }} {{ o.isHold ? '(⛔ DITAHAN DIREKTUR: ' + o.holdReason + ')' : '' }}
            </option>
          </select>
          <p v-if="selectedOrderObj?.isHold" class="text-[11px] text-rose-600 font-bold mt-1">
            ⚠️ Order ini sedang DITAHAN oleh Direktur. Pengiriman diblokir hingga tahanan dilepas.
          </p>
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Tujuan / Alamat Site Proyek</label>
          <input v-model="form.destination" required placeholder="Lokasi penerimaan barang..." class="w-full px-3 py-2 border rounded-lg" />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Armada / Nopol Truk</label>
            <input v-model="form.expedition" required placeholder="Armada Internal Truk N-8841-AA" class="w-full px-3 py-2 border rounded-lg" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Nama Driver & No. HP</label>
            <input v-model="form.driverName" required placeholder="Sopir (0812-XXXX)" class="w-full px-3 py-2 border rounded-lg" />
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button type="button" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg" @click="dispatchModalOpen = false">Batal</button>
          <button
            type="submit"
            :disabled="selectedOrderObj?.isHold"
            :class="[
              selectedOrderObj?.isHold ? 'bg-slate-300 text-slate-500 cursor-not-allowed' : 'bg-alpharo-500 hover:bg-alpharo-600 text-white',
              'px-4 py-1.5 font-bold rounded-lg transition-colors'
            ]"
          >
            Konfirmasi Keberangkatan
          </button>
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
import { Truck } from 'lucide-vue-next';

const toast = useToast();

const activeFilter = ref('Semua');
const dispatchModalOpen = ref(false);

const form = ref({
  orderNumber: '',
  clientName: '',
  destination: '',
  expedition: 'Armada Internal CV Alpharo',
  driverName: 'Joko Purwanto (0812-7711-2233)',
});

const allCandidateOrders = computed(() => {
  return store.orders.filter((o) => ['Disetujui', 'Diproses', 'Menunggu Pembayaran'].includes(o.status));
});

const readyOrders = computed(() => {
  return allCandidateOrders.value.filter((o) => !o.isHold);
});

const selectedOrderObj = computed(() => {
  return store.orders.find((o) => o.orderNumber === form.value.orderNumber);
});

const filteredShipments = computed(() => {
  if (activeFilter.value === 'Semua') return store.shipments;
  return store.shipments.filter((s) => s.status === activeFilter.value);
});

const getOrderHold = (orderNumber) => {
  const o = store.orders.find((ord) => ord.orderNumber === orderNumber);
  return o && o.isHold ? o : null;
};

const openDispatchModal = () => {
  const first = readyOrders.value[0] || allCandidateOrders.value[0];
  form.value = {
    orderNumber: first ? first.orderNumber : 'ORD-2026-001',
    clientName: first ? first.clientName : 'Client',
    destination: 'Site Proyek Konstruksi',
    expedition: 'Truk Tronton CV Alpharo N-8841-AA',
    driverName: 'Joko Purwanto (0812-7711-2233)',
  };
  dispatchModalOpen.value = true;
};

const onOrderSelect = () => {
  const order = store.orders.find((o) => o.orderNumber === form.value.orderNumber);
  if (order) {
    form.value.clientName = order.clientName;
  }
};

const saveDispatch = () => {
  const order = store.orders.find((o) => o.orderNumber === form.value.orderNumber);
  if (order && order.isHold) {
    toast.error(`Order ${order.orderNumber} sedang DITAHAN oleh Direktur: ${order.holdReason}. Pengiriman tidak diizinkan!`);
    return;
  }

  storeActions.createShipment(form.value);
  toast.success(`Pengiriman untuk ${form.value.orderNumber} berhasil diberangkatkan.`);
  dispatchModalOpen.value = false;
};

const confirmDelivered = (s) => {
  storeActions.updateShipmentStatus(s.id, 'Selesai');
  toast.success(`Pengiriman ${s.shipmentNumber} dikonfirmasi telah selesai tiba di lokasi!`);
};

const openDetail = (s) => {
  toast.info(`Detail pengiriman ${s.shipmentNumber} dibuka.`);
};
</script>
