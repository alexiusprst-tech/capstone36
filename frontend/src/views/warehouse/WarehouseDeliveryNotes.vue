<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Surat Jalan Gudang</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pemeriksaan fisik Surat Jalan sebelum muat armada dan konfirmasi serah terima pengemudi.
          </p>
        </div>
      </div>
    </template>

    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-900 uppercase">Daftar Surat Jalan Aktif</span>
        <span class="text-slate-500">{{ sjOrders.length }} Dokumen Surat Jalan</span>
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
              <th class="py-3 px-4 text-center">Status Gudang</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="order in sjOrders" :key="order.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                {{ order.documents?.suratJalan?.number }}
              </td>
              <td class="py-3.5 px-4 font-mono text-slate-600">{{ order.orderNumber }}</td>
              <td class="py-3.5 px-4 font-bold text-slate-800">{{ order.clientName }}</td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ order.documents?.suratJalan?.date }}</td>
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :status="order.documents?.suratJalan?.status" />
              </td>
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :status="order.fulfillmentStatus || 'Siap Dikirim'" />
              </td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    v-if="order.fulfillmentStatus !== 'Sedang Dikirim' && order.fulfillmentStatus !== 'Selesai'"
                    type="button"
                    class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold text-xs"
                    @click="confirmDispatched(order)"
                  >
                    Konfirmasi Muat Truk
                  </button>
                  <button
                    type="button"
                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold text-xs"
                    @click="openInspect(order)"
                  >
                    Detail SJ
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- DETAIL MODAL -->
    <Modal v-model="modalOpen" :title="`Detail Surat Jalan: ${selectedOrder?.documents?.suratJalan?.number || ''}`" :subtitle="selectedOrder?.clientName || ''" size="md">
      <div v-if="selectedOrder" class="space-y-4 text-xs">
        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1.5">
          <div class="flex justify-between">
            <span class="text-slate-500">Order Terkait:</span>
            <span class="font-mono font-bold text-slate-900">{{ selectedOrder.orderNumber }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Tujuan:</span>
            <span class="font-semibold text-slate-800">{{ selectedOrder.projectName }}</span>
          </div>
        </div>

        <div>
          <h4 class="font-bold uppercase text-slate-500 tracking-wider mb-2">Item Muatan Fisik</h4>
          <div class="border border-slate-200 rounded-lg divide-y divide-slate-100">
            <div v-for="it in selectedOrder.items" :key="it.sku" class="p-2.5 flex justify-between">
              <span>{{ it.name }}</span>
              <span class="font-bold">{{ it.qty }} {{ it.unit }}</span>
            </div>
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

const toast = useToast();

const modalOpen = ref(false);
const selectedOrder = ref(null);

const sjOrders = computed(() => store.orders.filter((o) => o.documents?.suratJalan?.exists));

const confirmDispatched = (order) => {
  order.fulfillmentStatus = 'Sedang Dikirim';
  toast.success(`Surat Jalan ${order.documents?.suratJalan?.number} dikonfirmasi berangkat bersama armada!`);
};

const openInspect = (order) => {
  selectedOrder.value = order;
  modalOpen.value = true;
};
</script>
