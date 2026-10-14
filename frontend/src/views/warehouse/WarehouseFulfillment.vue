<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pemenuhan Barang (Fulfillment Order)</h1>
          <p class="text-sm text-slate-500 mt-1">
            Alur alokasi stok fisik: Approved Order &rarr; Cek Stok &rarr; Siapkan Barang &rarr; Konfirmasi Pemenuhan &rarr; Siap Kirim.
          </p>
        </div>
      </div>
    </template>

    <!-- STEP PROCESS WORKFLOW BANNER -->
    <div class="p-4 bg-white rounded-xl border border-slate-200/90 mb-6 shadow-2xs">
      <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">Alur Operasional Pemenuhan Gudang:</h3>
      <div class="grid grid-cols-1 sm:grid-cols-5 gap-2 text-xs text-center">
        <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
          <span class="text-slate-400 font-bold block text-[10px]">Langkah 1</span>
          <span class="font-bold text-slate-800">Approved Order</span>
        </div>
        <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
          <span class="text-slate-400 font-bold block text-[10px]">Langkah 2</span>
          <span class="font-bold text-slate-800">Cek Ketersediaan Stok</span>
        </div>
        <div class="p-2.5 bg-blue-50 rounded-lg border border-blue-200">
          <span class="text-alpharo-500 font-bold block text-[10px]">Langkah 3</span>
          <span class="font-bold text-slate-800">Persiapan & Packing</span>
        </div>
        <div class="p-2.5 bg-amber-50 rounded-lg border border-amber-200">
          <span class="text-amber-600 font-bold block text-[10px]">Langkah 4</span>
          <span class="font-bold text-slate-800">Konfirmasi Pemenuhan</span>
        </div>
        <div class="p-2.5 bg-emerald-50 rounded-lg border border-emerald-200">
          <span class="text-emerald-600 font-bold block text-[10px]">Langkah 5</span>
          <span class="font-bold text-slate-800">Siap Dikirim</span>
        </div>
      </div>
    </div>

    <!-- FULFILLMENT TABLE -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-900 uppercase">Daftar Antrean Pemenuhan Fisik</span>
        <span class="text-slate-500">{{ store.warehouseFulfillments.length }} Order Aktif</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">No. Order</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">Rincian Barang</th>
              <th class="py-3 px-4 text-center">Required Qty</th>
              <th class="py-3 px-4 text-center">Available Qty</th>
              <th class="py-3 px-4 text-center">Fulfilled Qty</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="ful in store.warehouseFulfillments" :key="ful.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4">
                <span class="font-mono font-bold text-slate-900 block">{{ ful.orderNumber }}</span>
                <span
                  v-if="getOrderHold(ful.orderNumber)"
                  class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200 mt-0.5"
                  :title="getOrderHold(ful.orderNumber)?.holdReason"
                >
                  ⛔ Ditahan Direktur
                </span>
              </td>
              <td class="py-3.5 px-4 font-bold text-slate-800">{{ ful.clientName }}</td>
              <td class="py-3.5 px-4">
                <div v-for="it in ful.items" :key="it.sku" class="text-xs">
                  <span class="font-medium text-slate-900">{{ it.name }}</span>
                </div>
              </td>
              <td class="py-3.5 px-4 text-center font-mono">
                <div v-for="it in ful.items" :key="it.sku">
                  {{ it.reqQty }} {{ it.unit }}
                </div>
              </td>
              <td class="py-3.5 px-4 text-center font-mono text-emerald-600 font-bold">
                <div v-for="it in ful.items" :key="it.sku">
                  {{ it.availQty }} {{ it.unit }}
                </div>
              </td>
              <td class="py-3.5 px-4 text-center font-mono text-slate-900 font-bold">
                <div v-for="it in ful.items" :key="it.sku">
                  {{ it.fulfilledQty }} {{ it.unit }}
                </div>
              </td>
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :status="ful.status" />
              </td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div v-if="getOrderHold(ful.orderNumber)">
                  <span
                    class="text-rose-700 font-bold text-xs bg-rose-50 px-2.5 py-1 rounded border border-rose-200 inline-block cursor-help"
                    :title="getOrderHold(ful.orderNumber)?.holdReason"
                  >
                    ⛔ Ditahan Direktur
                  </span>
                </div>
                <div v-else>
                  <button
                    v-if="ful.status === 'Diproses'"
                    type="button"
                    class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold text-xs"
                    @click="confirmFulfillment(ful)"
                  >
                    Konfirmasi Siap Kirim
                  </button>
                  <span v-else class="text-emerald-700 font-bold text-xs bg-emerald-50 px-2.5 py-1 rounded border border-emerald-200">
                    Siap Kirim ✓
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '../../components/layout/AppLayout.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';

const toast = useToast();

const getOrderHold = (orderNumber) => {
  const o = store.orders.find((ord) => ord.orderNumber === orderNumber);
  return o && o.isHold ? o : null;
};

const confirmFulfillment = (ful) => {
  const hold = getOrderHold(ful.orderNumber);
  if (hold) {
    toast.error(`Order ${ful.orderNumber} sedang DITAHAN oleh Direktur: ${hold.holdReason}. Pemenuhan pengiriman ditangguhkan!`);
    return;
  }

  storeActions.confirmFulfillment(ful.id);
  toast.success(`Pemenuhan order ${ful.orderNumber} berhasil dikonfirmasi. Status diubah menjadi Siap Kirim.`);
};
</script>
