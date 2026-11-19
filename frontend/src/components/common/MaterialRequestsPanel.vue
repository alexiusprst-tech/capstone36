<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { purchaseWorkflow } from '../../services/purchaseWorkflow';
import { orderWorkflow, orderError } from '../../services/orderWorkflow';
import { useToast } from '../../services/toast';

const orders = ref([]), requests = ref([]), loading = ref(true), busy = ref(false), error = ref('');
const showForm = ref(false), orderNumber = ref(''), purpose = ref(''), lines = ref([]);
const toast = useToast();
const code = request => request.request_code || request.requestCode;
const selectedOrder = computed(() => orders.value.find(order => order.orderNumber === orderNumber.value));
const eligibleOrders = computed(() => orders.value.filter(order => order.status === 'Draft' && order.serverManaged !== false && (order.stockReadiness?.items || []).some(item => item.catalogFound !== false && Number(item.unrequestedShortage || 0) > 0)));
const statusLabel = status => ({ MENUNGGU_FINANCE: 'Menunggu Finance', DIPROSES_PEMBELIAN: 'Dalam pengadaan', DITERIMA_SEBAGIAN: 'Diterima sebagian', PERLU_TINDAK_LANJUT: 'Perlu tindak lanjut', SELESAI: 'Selesai', DIBATALKAN: 'Dibatalkan' })[status] || status;
watch(selectedOrder, order => {
  lines.value = (order?.stockReadiness?.items || []).filter(item => item.catalogFound !== false).map(item => {
    const available = Number(item.unrequestedShortage || 0);
    return { sku: item.sku, name: item.name || item.sku, unit: item.unit || '', max: available, qty: available, est_price: 0 };
  }).filter(item => item.max > 0);
});
async function load() {
  loading.value = true; error.value = '';
  try { const result = await Promise.all([orderWorkflow.list(), purchaseWorkflow.requests()]); orders.value = result[0]; requests.value = result[1]; }
  catch (cause) { error.value = orderError(cause); }
  finally { loading.value = false; }
}
async function submit() {
  if (busy.value) return;
  if (!selectedOrder.value || !lines.value.length || lines.value.some(line => !Number.isInteger(Number(line.qty)) || Number(line.qty) < 0 || Number(line.qty) > line.max) || !lines.value.some(line => Number(line.qty) > 0)) { error.value = 'Pilih pesanan tersimpan dan isi jumlah pengadaan sesuai kekurangan stok.'; return; }
  busy.value = true; error.value = '';
  try {
    await purchaseWorkflow.request({ order_number: orderNumber.value, purpose: purpose.value.trim(), items: lines.value.filter(line => Number(line.qty) > 0).map(({ sku, qty, est_price }) => ({ sku, qty: Number(qty), est_price: Number(est_price) })) });
    showForm.value = false; orderNumber.value = ''; purpose.value = ''; toast.success('Pengajuan kebutuhan tersimpan. Finance dapat menerbitkan PO.'); await load();
  } catch (cause) { error.value = orderError(cause); }
  finally { busy.value = false; }
}
async function cancel(request) {
  if (busy.value) return;
  busy.value = true; error.value = '';
  try { await purchaseWorkflow.cancelRequest(code(request)); toast.success('Pengajuan dibatalkan. Kebutuhan dapat diajukan kembali.'); await load(); }
  catch (cause) { error.value = orderError(cause); }
  finally { busy.value = false; }
}
onMounted(load);
</script>

<template>
  <section class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div><h2 class="text-lg font-semibold text-slate-900">Pengajuan Kebutuhan Barang</h2><p class="mt-1 text-sm text-slate-600">Ajukan kekurangan stok dari pesanan Draft yang sudah disimpan. Finance melanjutkan pengadaan ke supplier.</p></div>
      <div class="flex flex-wrap gap-2"><button type="button" class="rounded-lg border px-4 py-2 text-sm" :disabled="loading || busy" @click="load">Muat Ulang</button><button type="button" class="rounded-lg bg-sky-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" :disabled="loading || busy" @click="showForm = !showForm">{{ showForm ? 'Tutup Form Pengajuan' : 'Buat Pengajuan Kebutuhan' }}</button></div>
    </div>
    <p v-if="error" role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ error }}</p>
    <form v-if="showForm" class="space-y-4 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="submit">
      <h3 class="font-semibold">Pengajuan Baru</h3>
      <label class="block text-sm font-medium">Pesanan Sumber <span aria-hidden="true">*</span><select v-model="orderNumber" required :disabled="busy" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white p-2.5"><option value="">Pilih pesanan dengan kekurangan stok</option><option v-for="order in eligibleOrders" :key="order.orderNumber" :value="order.orderNumber">{{ order.orderNumber }} — {{ order.projectName }}</option></select></label>
      <p v-if="!eligibleOrders.length" class="text-sm text-slate-600">Belum ada pesanan Draft dengan kebutuhan stok yang belum diajukan. Simpan pesanan dan periksa stok terlebih dahulu.</p>
      <label class="block text-sm font-medium">Keperluan Pengadaan <span aria-hidden="true">*</span><textarea v-model="purpose" required maxlength="500" :disabled="busy" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 p-2.5"></textarea></label>
      <div v-if="lines.length" class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b text-slate-600"><th class="p-2">Barang</th><th class="p-2">Belum Diajukan</th><th class="p-2">Jumlah Pengadaan</th><th class="p-2">Estimasi Harga / Unit (Rp)</th></tr></thead><tbody><tr v-for="line in lines" :key="line.sku" class="border-b"><td class="p-2">{{ line.name }}<span class="block text-xs text-slate-500">{{ line.sku }} · {{ line.unit }}</span></td><td class="p-2">{{ line.max }}</td><td class="p-2"><input v-model.number="line.qty" :aria-label="`Jumlah Pengadaan ${line.sku}`" type="number" min="0" :max="line.max" step="1" required :disabled="busy" class="w-28 rounded-lg border p-2" /></td><td class="p-2"><input v-model.number="line.est_price" :aria-label="`Estimasi Harga ${line.sku}`" type="number" min="0" max="1000000000" step="0.01" required :disabled="busy" class="w-40 rounded-lg border p-2" /></td></tr></tbody></table></div>
      <button type="submit" :disabled="busy || !lines.length" class="rounded-lg bg-sky-700 px-4 py-2 font-semibold text-white disabled:opacity-50">{{ busy ? 'Menyimpan…' : 'Kirim Pengajuan' }}</button>
    </form>
    <p v-if="loading" role="status" class="p-5 text-sm text-slate-600">Memuat pengajuan dan kebutuhan pesanan…</p>
    <p v-else-if="!requests.length" class="ui-empty-state rounded-xl border border-dashed bg-white p-8 text-center text-sm text-slate-600">Belum ada pengajuan kebutuhan barang.</p>
    <div v-else class="space-y-3"><article v-for="request in requests" :key="request.id || code(request)" class="min-w-0 break-words rounded-xl border border-slate-200 bg-white p-5"><div class="flex flex-wrap items-start justify-between gap-3"><div><h3 class="font-semibold text-slate-900">{{ code(request) }}</h3><p class="mt-1 text-sm text-slate-600">Pesanan {{ request.order_number || 'Referensi pesanan belum tersedia' }} · {{ request.project_name }}</p></div><span class="rounded-lg bg-slate-100 px-3 py-1 text-xs font-semibold">{{ statusLabel(request.status) }}</span></div><p v-if="request.requirements_changed" class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Kebutuhan pesanan berubah. Batalkan pengajuan yang belum memiliki PO, lalu ajukan ulang kebutuhan terbaru.</p><p class="mt-3 text-sm">{{ request.purpose }}</p><ul class="mt-3 space-y-1 text-sm text-slate-700"><li v-for="(item, index) in request.items" :key="`${item.sku}-${index}`">{{ item.sku }} — {{ item.name }}: {{ item.qty }} {{ item.unit }}</li></ul><p v-if="request.purchase_code" class="mt-3 text-sm font-medium">PO: {{ request.purchase_code }}</p><button v-else-if="!['DIBATALKAN', 'CANCELLED', 'Dibatalkan'].includes(request.status)" type="button" :disabled="busy" class="mt-4 rounded-lg border border-red-200 px-3 py-2 text-sm text-red-700 disabled:opacity-50" @click="cancel(request)">Batalkan Pengajuan {{ code(request) }}</button></article></div>
  </section>
</template>
