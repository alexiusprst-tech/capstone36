<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard Warehouse & Logistik</h1>
          <p class="text-sm text-slate-500 mt-1">
            Operasional pengelolaan persediaan material gudang, antrean pemenuhan order, dan armada pengiriman.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <router-link
            to="/warehouse/fulfillment"
            class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
          >
            <CheckCircle2 class="w-4 h-4" />
            <span>Antrean Pemenuhan ({{ store.warehouseFulfillments.length }})</span>
          </router-link>
        </div>
      </div>
    </template>

    <!-- KPIS FOR WAREHOUSE: STATUS PERSEDIAAN & LOGISTIK PEMENUHAN -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
      <KpiCard title="Total Ragam Barang" :value="store.products.length" subtitle="Katalog material" icon-variant="slate" />
      <KpiCard title="Total Stok Fisik" :value="totalStockUnits" unit="Unit" subtitle="Akumulasi stok" icon-variant="blue" />
      <KpiCard title="Barang Tersedia" :value="availableCount" subtitle="Stok aman di gudang" icon-variant="emerald" />
      <KpiCard title="Stok Menipis / Habis" :value="oosCount" subtitle="Perlu restock segera" icon-variant="rose" />
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
      <KpiCard title="Harus Dipenuhi" :value="store.warehouseFulfillments.length" subtitle="Antrean order masuk" icon-variant="amber" />
      <KpiCard title="Proses Pemenuhan" :value="fulfillProcessCount" subtitle="Pengemasan & sortir" icon-variant="blue" />
      <KpiCard title="Siap Dikirim" :value="readyShipCount" subtitle="Menunggu armada logistik" icon-variant="indigo" />
      <KpiCard title="Sedang Dikirim" :value="shippingCount" subtitle="Dalam perjalanan kurir" icon-variant="emerald" />
    </div>

    <!-- MAIN TWO-COLUMN DASHBOARD SECTIONS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- LEFT 2 COLS: FULFILLMENT QUEUE & RECENT SHIPMENTS -->
      <div class="lg:col-span-2 space-y-6">
        <!-- FULFILLMENT QUEUE -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-bold text-slate-900 text-sm">Antrean Pemenuhan Order Disetujui</h3>
              <p class="text-xs text-slate-500">Order yang telah diapprove dan membutuhkan alokasi fisik barang</p>
            </div>
            <router-link
              to="/warehouse/fulfillment"
              class="text-xs font-bold text-alpharo-500 hover:underline flex items-center gap-1"
            >
              Kelola Pemenuhan <ChevronRight class="w-3.5 h-3.5" />
            </router-link>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                  <th class="py-2.5 px-3">No. Order</th>
                  <th class="py-2.5 px-3">Client</th>
                  <th class="py-2.5 px-3">Barang Dipesan</th>
                  <th class="py-2.5 px-3 text-center">Status</th>
                  <th class="py-2.5 px-3 text-right">Aksi Gudang</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="f in store.warehouseFulfillments" :key="f.id" class="hover:bg-slate-50/60 transition-colors">
                  <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ f.orderNumber }}</td>
                  <td class="py-3 px-3 font-bold text-slate-800">{{ f.clientName }}</td>
                  <td class="py-3 px-3">
                    <span v-for="it in f.items" :key="it.sku" class="inline-block text-[11px] bg-slate-100 px-1.5 py-0.5 rounded mr-1">
                      {{ it.name }} ({{ it.reqQty }} {{ it.unit }})
                    </span>
                  </td>
                  <td class="py-3 px-3 text-center"><StatusBadge :status="f.status" /></td>
                  <td class="py-3 px-3 text-right">
                    <button
                      v-if="f.status === 'Diproses'"
                      type="button"
                      class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold text-[11px]"
                      @click="handleConfirmFulfill(f)"
                    >
                      Konfirmasi Siap
                    </button>
                    <span v-else class="text-[11px] text-emerald-600 font-bold">Siap Kirim ✓</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- RECENT SHIPMENTS -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-bold text-slate-900 text-sm">Pengiriman Armada Terkini</h3>
              <p class="text-xs text-slate-500">Pergerakan truk dan delivery material ke lokasi proyek</p>
            </div>
            <router-link
              to="/warehouse/shipments"
              class="text-xs font-bold text-alpharo-500 hover:underline flex items-center gap-1"
            >
              Semua Pengiriman <ChevronRight class="w-3.5 h-3.5" />
            </router-link>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                  <th class="py-2.5 px-3">No. Pengiriman</th>
                  <th class="py-2.5 px-3">No. Order</th>
                  <th class="py-2.5 px-3">Tujuan Site</th>
                  <th class="py-2.5 px-3">Armada / Driver</th>
                  <th class="py-2.5 px-3 text-center">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="s in store.shipments" :key="s.id" class="hover:bg-slate-50/60 transition-colors">
                  <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ s.shipmentNumber }}</td>
                  <td class="py-3 px-3 font-mono text-slate-600">{{ s.orderNumber }}</td>
                  <td class="py-3 px-3 font-medium text-slate-800">{{ s.destination }}</td>
                  <td class="py-3 px-3 text-slate-600">{{ s.driverName }} ({{ s.expedition }})</td>
                  <td class="py-3 px-3 text-center"><StatusBadge :status="s.status" /></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- RIGHT 1 COL: LOW STOCK ALERTS & STOCK MOVEMENTS -->
      <div class="space-y-6">
        <!-- LOW STOCK ALERTS -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
            <div class="flex items-center gap-2">
              <AlertTriangle class="w-4 h-4 text-amber-500" />
              <h3 class="font-bold text-slate-900 text-sm">Peringatan Stok Rendah / Habis</h3>
            </div>
            <router-link to="/warehouse/requests" class="text-[11px] text-alpharo-500 font-bold hover:underline">
              + Ajukan
            </router-link>
          </div>

          <div class="space-y-2.5 text-xs">
            <div
              v-for="p in lowStockProducts"
              :key="p.sku"
              class="p-2.5 rounded-lg border border-rose-100 bg-rose-50/40 flex items-center justify-between"
            >
              <div>
                <span class="font-bold text-slate-900 block">{{ p.name }}</span>
                <span class="font-mono text-[10px] text-slate-500">{{ p.sku }} &bull; Min: {{ p.minStock }} {{ p.unit }}</span>
              </div>
              <div class="text-right">
                <span :class="[p.stock === 0 ? 'text-rose-600' : 'text-amber-700', 'font-bold font-mono text-xs block']">
                  {{ p.stock }} {{ p.unit }}
                </span>
                <StatusBadge :status="p.status" />
              </div>
            </div>

            <div v-if="lowStockProducts.length === 0" class="p-4 text-center text-slate-400">
              ✓ Semua stok berada pada level aman.
            </div>
          </div>
        </div>

        <!-- RECENT STOCK MOVEMENT -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
            <h3 class="font-bold text-slate-900 text-sm">Mutasi Stok Terbaru</h3>
            <router-link to="/warehouse/inventory" class="text-xs text-alpharo-500 font-bold hover:underline">
              Detail →
            </router-link>
          </div>

          <div class="space-y-2 text-xs">
            <div
              v-for="m in store.stockMovements.slice(0, 4)"
              :key="m.id"
              class="p-2 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-between"
            >
              <div>
                <span class="font-semibold text-slate-900 block truncate max-w-[150px]">{{ m.name }}</span>
                <span class="text-[10px] text-slate-400">{{ m.reference }}</span>
              </div>
              <div class="text-right">
                <span
                  :class="[
                    m.type === 'Masuk' ? 'text-emerald-600 font-bold' : m.type === 'Keluar' ? 'text-rose-600 font-bold' : 'text-blue-600 font-bold',
                    'font-mono text-xs'
                  ]"
                >
                  {{ m.type === 'Masuk' ? '+' : m.type === 'Keluar' ? '-' : '' }}{{ Math.abs(m.qty) }} {{ m.unit }}
                </span>
                <span class="block text-[10px] text-slate-400">{{ m.type }}</span>
              </div>
            </div>
          </div>
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
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import { CheckCircle2, ChevronRight, AlertTriangle } from 'lucide-vue-next';

const toast = useToast();

const totalStockUnits = computed(() => store.products.reduce((sum, p) => sum + (p.unit === 'M3' ? 0 : p.stock), 0));
const availableCount = computed(() => store.products.filter((p) => p.status === 'Tersedia').length);
const oosCount = computed(() => store.products.filter((p) => p.status === 'Tidak Tersedia').length);
const fulfillProcessCount = computed(() => store.warehouseFulfillments.filter((f) => f.status === 'Diproses').length);
const readyShipCount = computed(() => store.warehouseFulfillments.filter((f) => f.status === 'Siap Dikirim').length);
const shippingCount = computed(() => store.shipments.filter((s) => s.status === 'Sedang Dikirim').length);

const lowStockProducts = computed(() => store.products.filter((p) => p.stock <= p.minStock));

const handleConfirmFulfill = (f) => {
  storeActions.confirmFulfillment(f.id);
  toast.success(`Pemenuhan order ${f.orderNumber} telah dikonfirmasi siap kirim.`);
};
</script>
