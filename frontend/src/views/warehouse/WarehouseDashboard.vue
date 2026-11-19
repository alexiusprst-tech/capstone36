<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Dashboard Warehouse & Logistik</h1>
          <p class="text-sm text-slate-500 mt-1">
            Operasional pengelolaan persediaan material gudang, antrean pemenuhan order, dan armada pengiriman.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Link
            href="/warehouse/fulfillment?tab=requests"
            class="inline-flex items-center gap-2 px-3.5 py-2 bg-alpharo-600 hover:bg-alpharo-700 active:bg-alpharo-800 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors cursor-pointer"
          >
            <ClipboardList class="w-4 h-4 stroke-[2.2]" />
            <span>Pengajuan Kebutuhan Barang</span>
          </Link>
          <Link
            href="/warehouse/fulfillment"
            class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
          >
            <CheckCircle2 class="w-4 h-4" />
            <span>Antrean Pemenuhan ({{ store.warehouseFulfillments.length }})</span>
          </Link>
        </div>
      </div>
    </template>

    <!-- KPIS FOR WAREHOUSE: 4 PRIMARY ACTION-ORIENTED METRICS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <KpiCard
        title="Stok Kritis / Habis"
        :value="oosCount"
        unit="SKU"
        subtitle="Perlu restock pengadaan segera"
        :icon-component="AlertTriangle"
        icon-variant="blue"
        :trend="oosCount > 0 ? 'Perlu Pengadaan' : 'Aman'"
        :trend-positive="oosCount === 0"
      />
      <KpiCard
        title="Barang Tersedia"
        :value="availableCount"
        unit="SKU"
        subtitle="Stok aman di gudang utama"
        :icon-component="CheckCircle2"
        icon-variant="blue"
      />
      <KpiCard
        title="Order Perlu Dipenuhi"
        :value="store.warehouseFulfillments.length"
        unit="Order"
        subtitle="Antrean kemas & alokasi fisik"
        :icon-component="Clock"
        icon-variant="blue"
      />
      <KpiCard
        title="Siap & Sedang Dikirim"
        :value="readyShipCount + shippingCount"
        unit="Armada"
        :subtitle="`${readyShipCount} siap muat & ${shippingCount} dalam perjalanan`"
        :icon-component="Truck"
        icon-variant="blue"
      />
    </div>

    <!-- MAIN TWO-COLUMN DASHBOARD SECTIONS -->
    <div class="dashboard-workspace min-w-0 flex flex-col gap-4 sm:gap-5">
      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-semibold text-slate-900 text-sm">Antrean Pemenuhan Order Disetujui</h3>
              <p class="text-xs text-slate-500">Order yang telah diapprove dan membutuhkan alokasi fisik barang</p>
            </div>
            <Link
              href="/warehouse/fulfillment"
              class="text-xs font-semibold text-alpharo-500 hover:underline flex items-center gap-1"
            >
              Kelola Pemenuhan <ChevronRight class="w-3.5 h-3.5" />
            </Link>
          </div>

          <div class="overflow-x-auto xl:max-h-[26rem] xl:overflow-y-auto overscroll-auto">
            <table class="ui-table w-full text-left text-xs border-collapse">
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
                  <td class="py-3 px-3 font-mono font-semibold text-slate-900">{{ f.orderNumber }}</td>
                  <td class="py-3 px-3 font-semibold text-slate-800">{{ f.clientName }}</td>
                  <td class="py-3 px-3">
                    <span v-for="it in f.items" :key="it.sku" class="block text-[11px] text-slate-600 leading-relaxed">
                      {{ it.name }} ({{ it.reqQty }} {{ it.unit }})
                    </span>
                  </td>
                  <td class="py-3 px-3 text-center"><StatusBadge plain :status="f.status" /></td>
                  <td class="py-3 px-3 text-right">
                    <button
                      v-if="f.status === 'Diproses'"
                      type="button"
                      class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-semibold text-[11px]"
                      @click="handleConfirmFulfill(f)"
                    >
                      Konfirmasi Siap
                    </button>
                    <span v-else class="text-[11px] text-emerald-600 font-semibold">Siap Kirim ✓</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-semibold text-slate-900 text-sm">Pengiriman Armada Terkini</h3>
              <p class="text-xs text-slate-500">Pergerakan truk dan delivery material ke lokasi proyek</p>
            </div>
            <Link
              href="/warehouse/shipments"
              class="text-xs font-semibold text-alpharo-500 hover:underline flex items-center gap-1"
            >
              Semua Pengiriman <ChevronRight class="w-3.5 h-3.5" />
            </Link>
          </div>

          <div class="overflow-x-auto xl:max-h-[26rem] xl:overflow-y-auto overscroll-auto">
            <table class="ui-table w-full text-left text-xs border-collapse">
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
                  <td class="py-3 px-3 font-mono font-semibold text-slate-900">{{ s.shipmentNumber }}</td>
                  <td class="py-3 px-3 font-mono text-slate-600">{{ s.orderNumber }}</td>
                  <td class="py-3 px-3 font-medium text-slate-800">{{ s.destination }}</td>
                  <td class="py-3 px-3 text-slate-600">{{ s.driverName }} ({{ s.expedition }})</td>
                  <td class="py-3 px-3 text-center"><StatusBadge plain :status="s.status" /></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
            <h3 class="font-semibold text-slate-900 text-sm">Mutasi Stok Terbaru</h3>
            <Link href="/warehouse/inventory" class="text-xs text-alpharo-500 font-semibold hover:underline">
              Detail →
            </Link>
          </div>

          <div class="divide-y divide-slate-100 text-xs">
            <div
              v-for="m in store.stockMovements.slice(0, 4)"
              :key="m.id"
              class="py-3 flex items-start justify-between gap-4"
            >
              <div class="min-w-0 flex-1">
                <span class="font-semibold text-slate-900 block whitespace-normal [overflow-wrap:anywhere] leading-relaxed">{{ m.name }}</span>
                <span class="text-[11px] text-slate-400 block whitespace-normal [overflow-wrap:anywhere]">{{ m.reference }}</span>
              </div>
              <div class="text-right shrink-0 whitespace-nowrap">
                <span
                  :class="[
                    m.type === 'Masuk' ? 'text-emerald-600 font-semibold' : m.type === 'Keluar' ? 'text-rose-600 font-semibold' : 'text-alpharo-600 font-semibold',
                    'font-mono text-xs'
                  ]"
                >
                  {{ m.type === 'Masuk' ? '+' : m.type === 'Keluar' ? '-' : '' }}{{ Math.abs(m.qty) }} {{ m.unit }}
                </span>
                <span class="block text-[11px] text-slate-400">{{ m.type }}</span>
              </div>
            </div>
          </div>
        </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
            <div class="flex items-center gap-2">
              <AlertTriangle class="w-4 h-4 text-amber-500" />
              <h3 class="font-semibold text-slate-900 text-sm">Peringatan Stok Rendah / Habis</h3>
            </div>
            <Link href="/warehouse/fulfillment?tab=requests" class="text-[11px] text-alpharo-500 font-semibold hover:underline">
              + Ajukan
            </Link>
          </div>

          <div class="divide-y divide-slate-100 text-xs xl:max-h-80 xl:overflow-y-auto overscroll-auto">
            <div
              v-for="p in lowStockProducts"
              :key="p.sku"
              class="py-3 flex items-start justify-between gap-4"
            >
              <div class="min-w-0 flex-1">
                <span class="font-semibold text-slate-900 block">{{ p.name }}</span>
                <span class="font-mono text-[11px] text-slate-500">{{ p.sku }} &bull; Min: {{ p.minStock }} {{ p.unit }}</span>
              </div>
              <div class="shrink-0 text-right">
                <span :class="[p.stock === 0 ? 'text-rose-600' : 'text-amber-700', 'font-semibold font-mono text-xs block']">
                  {{ p.stock }} {{ p.unit }}
                </span>
                <StatusBadge plain :status="p.status" />
              </div>
            </div>

            <div v-if="lowStockProducts.length === 0" class="p-4 text-center text-slate-400">
              ✓ Semua stok berada pada level aman.
            </div>
          </div>
        </div>

      <ActivityLogCard class="dashboard-activity" />
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import KpiCard from '../../components/common/KpiCard.vue';
import ActivityLogCard from '../../components/common/ActivityLogCard.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import {
  CheckCircle2,
  ChevronRight,
  AlertTriangle,
  Boxes,
  Package,
  Clock,
  PackageSearch,
  PackageCheck,
  Truck,
  ClipboardList,
} from 'lucide-vue-next';

const toast = useToast();

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
