<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Monitoring Administrasi & Dokumen</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pengawasan kelengkapan legalitas transaksi (PO, Surat Jalan, BAST, dan Faktur Pajak).
          </p>
        </div>
      </div>
    </template>

    <!-- KPIS FOR DOCUMENT MONITORING (SPECIFIED IN PROMPT) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
      <KpiCard title="PO Terbit" :value="poCount" icon-variant="blue" />
      <KpiCard title="Surat Jalan" :value="sjCount" icon-variant="indigo" />
      <KpiCard title="BAST Terbit" :value="bastCount" icon-variant="emerald" />
      <KpiCard title="Faktur Terbit" :value="fakturCount" icon-variant="blue" />
      <KpiCard title="Dokumen Lengkap" :value="completeOrderCount" icon-variant="emerald" />
      <KpiCard title="Belum Lengkap" :value="incompleteOrderCount" icon-variant="amber" />
      <KpiCard title="Tuntas Selesai" :value="completeOrderCount" icon-variant="emerald" />
    </div>

    <!-- DOCUMENT COMPLETENESS CHECKLIST TABLE -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
        <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider">
          Status Kelengkapan Dokumen Per Order Disetujui
        </h3>
        <span class="text-xs text-slate-500">Standar 4 Berkas Legalitas Proyek</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">No. Order</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4 text-center">Purchase Order</th>
              <th class="py-3 px-4 text-center">Surat Jalan</th>
              <th class="py-3 px-4 text-center">BAST</th>
              <th class="py-3 px-4 text-center">Faktur</th>
              <th class="py-3 px-4 text-center">Progress Berkas</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="order in store.orders" :key="order.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ order.orderNumber }}</td>
              <td class="py-3.5 px-4 font-medium text-slate-800">{{ order.clientName }}</td>
              
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :status="order.documents?.po?.status || 'Belum Dibuat'" />
              </td>
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :status="order.documents?.suratJalan?.status || 'Belum Dibuat'" />
              </td>
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :status="order.documents?.bast?.status || 'Belum Dibuat'" />
              </td>
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :status="order.documents?.faktur?.status || 'Belum Dibuat'" />
              </td>

              <td class="py-3.5 px-4 text-center">
                <div class="inline-flex items-center gap-2">
                  <div class="w-20 bg-slate-200 h-2 rounded-full overflow-hidden">
                    <div
                      :class="[getDocProgress(order) === 4 ? 'bg-emerald-500' : 'bg-alpharo-500', 'h-full']"
                      :style="{ width: `${(getDocProgress(order) / 4) * 100}%` }"
                    ></div>
                  </div>
                  <span class="font-bold text-[11px] text-slate-700">{{ getDocProgress(order) }}/4</span>
                </div>
              </td>

              <td class="py-3.5 px-4 text-right">
                <button
                  type="button"
                  class="px-2.5 py-1 text-alpharo-500 hover:bg-blue-50 rounded border border-blue-200 font-bold text-xs"
                  @click="openInspect(order)"
                >
                  Detail Berkas
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- INSPECT DRAWER -->
    <Drawer
      v-model="drawerOpen"
      :title="`Berkas Legalitas: ${selectedOrder?.orderNumber || ''}`"
      :subtitle="selectedOrder?.clientName || ''"
      size="md"
    >
      <div v-if="selectedOrder" class="space-y-6 text-xs">
        <DocumentChecklist :documents="selectedOrder.documents" />

        <div class="space-y-3">
          <h4 class="font-bold uppercase text-slate-500 tracking-wider">Detail Tiap Berkas</h4>
          
          <div
            v-for="(docKey, dIdx) in ['po', 'suratJalan', 'bast', 'faktur']"
            :key="dIdx"
            class="p-3 rounded-lg border border-slate-200 bg-white flex items-center justify-between"
          >
            <div>
              <span class="font-bold text-slate-800 uppercase text-[11px] block">
                {{ docKey === 'po' ? 'Purchase Order (PO)' : docKey === 'suratJalan' ? 'Surat Jalan Pengiriman' : docKey === 'bast' ? 'Berita Acara Serah Terima (BAST)' : 'Faktur Pajak / Komersial' }}
              </span>
              <span class="font-mono text-slate-500 text-[11px]">
                {{ selectedOrder.documents?.[docKey]?.number || 'Belum Diterbitkan' }}
              </span>
            </div>
            <StatusBadge :status="selectedOrder.documents?.[docKey]?.status || 'Belum Dibuat'" />
          </div>
        </div>
      </div>
    </Drawer>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import KpiCard from '../../components/common/KpiCard.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import DocumentChecklist from '../../components/common/DocumentChecklist.vue';
import Drawer from '../../components/common/Drawer.vue';
import { store } from '../../stores/mockData';

const drawerOpen = ref(false);
const selectedOrder = ref(null);

const poCount = computed(() => store.orders.filter((o) => o.documents?.po?.status === 'Lengkap').length);
const sjCount = computed(() => store.orders.filter((o) => o.documents?.suratJalan?.status === 'Lengkap').length);
const bastCount = computed(() => store.orders.filter((o) => o.documents?.bast?.status === 'Lengkap').length);
const fakturCount = computed(() => store.orders.filter((o) => o.documents?.faktur?.status === 'Lengkap').length);

const getDocProgress = (order) => {
  let count = 0;
  if (order.documents?.po?.status === 'Lengkap') count++;
  if (order.documents?.suratJalan?.status === 'Lengkap') count++;
  if (order.documents?.bast?.status === 'Lengkap') count++;
  if (order.documents?.faktur?.status === 'Lengkap') count++;
  return count;
};

const completeOrderCount = computed(() => store.orders.filter((o) => getDocProgress(o) === 4).length);
const incompleteOrderCount = computed(() => store.orders.length - completeOrderCount.value);

const openInspect = (order) => {
  selectedOrder.value = order;
  drawerOpen.value = true;
};
</script>
