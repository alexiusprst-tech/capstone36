<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Monitoring Warehouse & Logistik</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pengawasan manajerial terhadap ketersediaan material, antrean pemenuhan, dan pergerakan armada.
          </p>
        </div>
      </div>
    </template>

    <!-- KPIS FOR WAREHOUSE MONITORING (SPECIFIED IN PROMPT) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
      <KpiCard title="Order Harus Dipenuhi" :value="store.warehouseFulfillments.length" icon-variant="slate" />
      <KpiCard title="Barang Tersedia" :value="availableCount" icon-variant="emerald" />
      <KpiCard title="Tidak Tersedia / OOS" :value="oosCount" icon-variant="rose" />
      <KpiCard title="Pemenuhan Diproses" :value="fulfillProcessCount" icon-variant="blue" />
      <KpiCard title="Siap Dikirim" :value="readyShipCount" icon-variant="indigo" />
      <KpiCard title="Sedang / Sudah Dikirim" :value="shippingCount" icon-variant="emerald" />
    </div>

    <!-- MAIN TWO TABLES: 1. FULFILLMENT QUEUE & 2. CRITICAL INVENTORY -->
    <div class="space-y-6">
      <!-- FULFILLMENT QUEUE TABLE -->
      <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider">
            Antrean Pemenuhan Order Disetujui
          </h3>
          <span class="text-xs text-slate-500">{{ store.warehouseFulfillments.length }} Order Aktif</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                <th class="py-3 px-4">No. Order</th>
                <th class="py-3 px-4">Client</th>
                <th class="py-3 px-4">Rincian Kebutuhan Barang</th>
                <th class="py-3 px-4">PIC Gudang</th>
                <th class="py-3 px-4 text-center">Status Pemenuhan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="ful in store.warehouseFulfillments" :key="ful.id" class="hover:bg-slate-50/70 transition-colors">
                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ ful.orderNumber }}</td>
                <td class="py-3.5 px-4 font-bold text-slate-800">{{ ful.clientName }}</td>
                <td class="py-3.5 px-4">
                  <div v-for="it in ful.items" :key="it.sku" class="text-[11px] text-slate-600">
                    &bull; {{ it.name }}: <span class="font-semibold">{{ it.fulfilledQty }}/{{ it.reqQty }} {{ it.unit }}</span>
                  </div>
                </td>
                <td class="py-3.5 px-4 text-slate-600">{{ ful.checkedBy || 'Staf Gudang' }}</td>
                <td class="py-3.5 px-4 text-center">
                  <StatusBadge :status="ful.status" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- INVENTORY STOCK LEVELS TABLE -->
      <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider">
            Monitoring Level Stok Material & Komoditas
          </h3>
          <span class="text-xs text-slate-500">Total {{ store.products.length }} Item Terdaftar</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                <th class="py-3 px-4">SKU</th>
                <th class="py-3 px-4">Nama Material</th>
                <th class="py-3 px-4">Kategori</th>
                <th class="py-3 px-4 text-center">Stok Saat Ini</th>
                <th class="py-3 px-4 text-center">Batas Minimum</th>
                <th class="py-3 px-4 text-right">Harga Satuan</th>
                <th class="py-3 px-4 text-center">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="p in store.products" :key="p.id" class="hover:bg-slate-50/70 transition-colors">
                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ p.sku }}</td>
                <td class="py-3.5 px-4 font-medium text-slate-900">{{ p.name }}</td>
                <td class="py-3.5 px-4 text-slate-600">{{ p.category }}</td>
                <td class="py-3.5 px-4 text-center font-bold text-slate-900">{{ p.stock }} {{ p.unit }}</td>
                <td class="py-3.5 px-4 text-center text-slate-500 font-mono">{{ p.minStock }} {{ p.unit }}</td>
                <td class="py-3.5 px-4 text-right font-mono font-semibold">Rp {{ p.price.toLocaleString('id-ID') }}</td>
                <td class="py-3.5 px-4 text-center">
                  <StatusBadge :status="p.status" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import KpiCard from '../../components/common/KpiCard.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import { store } from '../../stores/mockData';

const availableCount = computed(() => store.products.filter((p) => p.status === 'Tersedia').length);
const oosCount = computed(() => store.products.filter((p) => p.status === 'Tidak Tersedia').length);
const fulfillProcessCount = computed(() => store.warehouseFulfillments.filter((f) => f.status === 'Diproses').length);
const readyShipCount = computed(() => store.warehouseFulfillments.filter((f) => f.status === 'Siap Dikirim').length);
const shippingCount = computed(() => store.shipments.filter((s) => s.status === 'Sedang Dikirim' || s.status === 'Selesai').length);
</script>
