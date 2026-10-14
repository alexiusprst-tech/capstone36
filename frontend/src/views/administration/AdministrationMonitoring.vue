<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Monitoring Kelengkapan Berkas</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pantau status pemenuhan dokumen legal proyek: Belum Lengkap, Sedang Diproses, dan Selesai.
          </p>
        </div>
      </div>
    </template>

    <!-- STATUS TABS -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-5">
      <button
        v-for="tab in ['Semua', 'Belum Lengkap', 'Sedang Diproses', 'Dokumen Selesai']"
        :key="tab"
        type="button"
        :class="[
          activeFilter === tab
            ? 'bg-alpharo-500 text-white font-bold shadow-xs'
            : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200',
          'px-4 py-2 rounded-xl text-xs whitespace-nowrap transition-all'
        ]"
        @click="activeFilter = tab"
      >
        {{ tab }}
      </button>
    </div>

    <!-- MONITORING TABLE -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">No. Order</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4 text-center">PO</th>
              <th class="py-3 px-4 text-center">Surat Jalan</th>
              <th class="py-3 px-4 text-center">BAST</th>
              <th class="py-3 px-4 text-center">Faktur</th>
              <th class="py-3 px-4 text-center">Status Kelengkapan</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="order in filteredList" :key="order.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ order.orderNumber }}</td>
              <td class="py-3.5 px-4 font-bold text-slate-800">{{ order.clientName }}</td>
              <td class="py-3.5 px-4 text-center"><StatusBadge :status="order.documents?.po?.status || 'Belum Dibuat'" /></td>
              <td class="py-3.5 px-4 text-center"><StatusBadge :status="order.documents?.suratJalan?.status || 'Belum Dibuat'" /></td>
              <td class="py-3.5 px-4 text-center"><StatusBadge :status="order.documents?.bast?.status || 'Belum Dibuat'" /></td>
              <td class="py-3.5 px-4 text-center"><StatusBadge :status="order.documents?.faktur?.status || 'Belum Dibuat'" /></td>
              <td class="py-3.5 px-4 text-center">
                <span
                  :class="[
                    getCompCount(order) === 4 ? 'bg-emerald-50 text-emerald-700' : getCompCount(order) > 0 ? 'bg-blue-50 text-blue-700' : 'bg-rose-50 text-rose-700',
                    'px-2 py-0.5 rounded font-bold text-[10px]'
                  ]"
                >
                  {{ getCompCount(order) === 4 ? 'Dokumen Selesai (4/4)' : getCompCount(order) > 0 ? 'Sedang Diproses' : 'Belum Lengkap (0/4)' }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-right">
                <button
                  type="button"
                  class="px-2.5 py-1 text-alpharo-500 hover:bg-blue-50 border border-blue-200 rounded font-bold text-xs"
                  @click="inspectOrder(order)"
                >
                  Detail
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- DRAWER -->
    <Drawer v-model="drawerOpen" :title="`Monitoring: ${selectedOrder?.orderNumber || ''}`" :subtitle="selectedOrder?.clientName || ''" size="md">
      <div v-if="selectedOrder" class="space-y-6 text-xs">
        <DocumentChecklist :documents="selectedOrder.documents" />
      </div>
    </Drawer>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import DocumentChecklist from '../../components/common/DocumentChecklist.vue';
import Drawer from '../../components/common/Drawer.vue';
import { store } from '../../stores/mockData';

const activeFilter = ref('Semua');
const drawerOpen = ref(false);
const selectedOrder = ref(null);

const getCompCount = (order) => {
  let count = 0;
  if (order.documents?.po?.status === 'Lengkap') count++;
  if (order.documents?.suratJalan?.status === 'Lengkap') count++;
  if (order.documents?.bast?.status === 'Lengkap') count++;
  if (order.documents?.faktur?.status === 'Lengkap') count++;
  return count;
};

const filteredList = computed(() => {
  const list = store.orders.filter((o) => o.status !== 'Draft');
  if (activeFilter.value === 'Dokumen Selesai') {
    return list.filter((o) => getCompCount(o) === 4);
  }
  if (activeFilter.value === 'Sedang Diproses') {
    return list.filter((o) => getCompCount(o) > 0 && getCompCount(o) < 4);
  }
  if (activeFilter.value === 'Belum Lengkap') {
    return list.filter((o) => getCompCount(o) < 4);
  }
  return list;
});

const inspectOrder = (order) => {
  selectedOrder.value = order;
  drawerOpen.value = true;
};
</script>
