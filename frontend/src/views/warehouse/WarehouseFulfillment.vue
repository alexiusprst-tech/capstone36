<script setup>
import { computed, ref, watch } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import MaterialRequestsPanel from '../../components/common/MaterialRequestsPanel.vue';
import ProcurementPurchasesPanel from '../../components/common/ProcurementPurchasesPanel.vue';
import { useRoute, useRouter } from '../../navigation';
import { orderWorkflow, orderError } from '../../services/orderWorkflow';
import { useLiveRefresh } from '../../composables/useLiveRefresh';
const route = useRoute(), router = useRouter();
const activeTab = computed(() => ['requests', 'inbound', 'fulfillment'].includes(route.query.tab) ? route.query.tab : 'requests');
const tabs = [{ key: 'requests', label: 'Pengajuan Kebutuhan (Material Request)' }, { key: 'inbound', label: 'Penerimaan Fisik & Resi Supplier' }, { key: 'fulfillment', label: 'Pemenuhan Order Proyek' }];
const orders = ref([]), loading = ref(false), error = ref('');
async function loadOrders() {
  loading.value = !orders.value.length;
  try { orders.value = await orderWorkflow.list(); error.value = ''; }
  catch (cause) { error.value = orderError(cause); throw cause; }
  finally { loading.value = false; }
}
const { refresh, lastUpdated } = useLiveRefresh(loadOrders, { enabled: () => activeTab.value === 'fulfillment' });
const lastUpdatedLabel = computed(() => lastUpdated.value ? lastUpdated.value.toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta' }) : '');
function switchTab(tab) { router.replace({ query: { ...route.query, tab } }); }
watch(activeTab, tab => { if (tab === 'fulfillment') void refresh(); });
</script>
<template>
  <AppLayout>
    <template #header><div><h1 class="text-2xl font-semibold text-slate-900">Pengadaan &amp; Penerimaan Logistik</h1><p class="mt-1 text-sm text-slate-600">Kebutuhan pesanan, pengadaan supplier, pemeriksaan fisik dan kesiapan stok.</p></div></template>
    <div class="space-y-6"><div class="flex flex-wrap gap-2" role="group" aria-label="Alur pengadaan"><button v-for="tab in tabs" :key="tab.key" type="button" :aria-pressed="activeTab === tab.key" class="rounded-lg border px-4 py-2.5 text-left text-sm font-semibold transition-colors" :class="activeTab === tab.key ? 'border-alpharo-600 bg-alpharo-600 text-white' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'" @click="switchTab(tab.key)">{{ tab.label }}</button></div>
    <MaterialRequestsPanel v-if="activeTab === 'requests'" />
    <ProcurementPurchasesPanel v-else-if="activeTab === 'inbound'" mode="warehouse" />
    <section v-else class="space-y-4"><div class="flex flex-wrap justify-between gap-3"><div><h2 class="text-lg font-semibold">Kesiapan Stok Pesanan</h2><p class="mt-1 text-sm text-slate-600">Barang yang diterima dan lolos pemeriksaan dialokasikan ke pesanan. Pengajuan approval menunggu seluruh kebutuhan terpenuhi.</p></div><button type="button" :disabled="loading" class="rounded-lg border bg-white px-4 py-2 text-sm" @click="refresh">Muat Ulang Pesanan</button></div><p v-if="lastUpdatedLabel" class="text-xs text-slate-500">Pembaruan otomatis aktif. Terakhir diperbarui {{ lastUpdatedLabel }} WIB.</p><p v-if="error" role="alert" class="rounded-lg bg-red-50 p-3 text-sm text-red-800">{{ error }}</p><p v-if="loading" role="status" class="text-sm text-slate-600">Memuat pesanan…</p><p v-else-if="!orders.length" class="ui-empty-state rounded-xl border border-dashed bg-white p-8 text-center text-sm text-slate-600">Belum ada pesanan tersimpan.</p><article v-for="order in orders" v-else :key="order.orderNumber" class="space-y-3 rounded-xl border bg-white p-5"><h3 class="font-semibold">{{ order.orderNumber }} · {{ order.projectName }}</h3><p class="text-sm text-slate-600">{{ order.status }} · {{ order.stockReadiness?.status === 'Siap Diajukan' ? 'Stok siap untuk pengajuan approval' : 'Kebutuhan stok belum terpenuhi' }}</p><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b text-slate-600"><th class="p-2">Barang</th><th class="p-2">Kebutuhan</th><th class="p-2">Dialokasikan</th><th class="p-2">Kekurangan</th></tr></thead><tbody><tr v-for="(item,index) in order.stockReadiness?.items || []" :key="`${item.sku}-${index}`" class="border-b"><td class="p-2">{{ item.name || item.sku }}</td><td class="p-2">{{ item.required ?? item.qty }}</td><td class="p-2">{{ item.allocated ?? item.allocatedQty }}</td><td class="p-2">{{ item.shortage }}</td></tr></tbody></table></div></article></section>
    </div>
  </AppLayout>
</template>
