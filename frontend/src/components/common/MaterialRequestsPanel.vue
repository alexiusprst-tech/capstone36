<script setup>
import { computed, ref } from 'vue';
import { Eye, Plus, RefreshCw, Search } from 'lucide-vue-next';
import MaterialRequestForm from './MaterialRequestForm.vue';
import Modal from './Modal.vue';
import { useLiveRefresh } from '../../composables/useLiveRefresh';
import { purchaseWorkflow } from '../../services/purchaseWorkflow';
import { orderWorkflow, orderError } from '../../services/orderWorkflow';
import { store } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import api from '../../services/api';

const orders = ref([]), requests = ref([]), loading = ref(true), busy = ref(false), hasLoaded = ref(false);
const error = ref(''), syncError = ref(''), showForm = ref(false), search = ref(''), status = ref('');
const detailOpen = ref(false), detailCode = ref('');
const suppliers = ref([]);
const toast = useToast();
let inFlight = null, mutationGeneration = 0;
const code = request => request.request_code || request.requestCode;
const selectedRequest = computed(() => requests.value.find(request => code(request) === detailCode.value));
const statuses = {
  MENUNGGU_FINANCE: 'Menunggu Finance', DIPROSES_PEMBELIAN: 'Dalam pengadaan',
  DITERIMA_SEBAGIAN: 'Diterima sebagian', PERLU_TINDAK_LANJUT: 'Perlu tindak lanjut', SELESAI: 'Selesai', DIBATALKAN: 'Dibatalkan',
};
const statusLabel = value => statuses[value] || value;
const filteredRequests = computed(() => {
  const query = search.value.trim().toLocaleLowerCase('id-ID');
  return requests.value.filter(request => (!status.value || request.status === status.value)
    && (!query || `${code(request)} ${request.order_number || ''} ${request.project_name || ''} ${request.purchase_code || ''} ${request.purpose || ''}`.toLocaleLowerCase('id-ID').includes(query)));
});
const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0));
const itemSubtotal = item => (Math.round(Number(item.est_price || 0) * 100) - Math.round(Number(item.est_discount || 0) * 100)) * Number(item.qty || 0) / 100;
const canCancel = request => !request.purchase_code && !['DIBATALKAN', 'CANCELLED', 'Dibatalkan'].includes(request.status);
function displayDate(value) {
  if (!value) return '-';
  const parsed = new Date(/^\d{4}-\d{2}-\d{2}$/.test(value) ? `${value}T00:00:00+07:00` : value);
  return Number.isNaN(parsed.valueOf()) ? value : new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'Asia/Jakarta' }).format(parsed);
}
function displayTime(value) { return value ? new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', timeZone: 'Asia/Jakarta' }).format(value) : ''; }
function load({ force = false } = {}) {
  if (inFlight) return inFlight;
  if (busy.value && !force) return Promise.resolve();
  const generation = mutationGeneration;
  inFlight = (async () => {
    try {
      const [freshOrders, freshRequests, master] = await Promise.all([orderWorkflow.list(), purchaseWorkflow.requests(), api.get('/suppliers?per_page=-1')]);
      if (!Array.isArray(freshOrders) || !Array.isArray(freshRequests)) throw new Error('Data pengadaan tidak dapat dibaca. Coba muat ulang.');
      if (generation !== mutationGeneration) return;
      orders.value = freshOrders; requests.value = freshRequests; syncError.value = ''; hasLoaded.value = true;
      suppliers.value = (master.data.data || []).filter(record => record.is_active !== false);
    } catch (cause) { if (generation !== mutationGeneration) return; syncError.value = orderError(cause); throw cause; }
    finally { loading.value = false; inFlight = null; }
  })();
  return inFlight;
}
const { refresh, refreshing, lastUpdated } = useLiveRefresh(() => load(), { intervalMs: 10000, enabled: () => !busy.value });
async function afterMutation() {
  if (inFlight) await inFlight.catch(() => {});
  await load({ force: true }).catch(() => {});
}
function announce(request, action) { window.dispatchEvent(new CustomEvent('procurement:updated', { detail: { action, requestCode: code(request) } })); }
function acceptMutation(saved) {
  mutationGeneration++;
  const index = requests.value.findIndex(request => code(request) === code(saved));
  if (index === -1) requests.value.unshift(saved);
  else requests.value.splice(index, 1, saved);
}
async function submit(payload) {
  if (busy.value) return;
  busy.value = true; error.value = '';
  try {
    const saved = await purchaseWorkflow.request(payload);
    acceptMutation(saved);
    showForm.value = false; toast.success('Pengajuan kebutuhan tersimpan dan diteruskan ke Finance.');
    announce(saved, 'material-request-created'); await afterMutation();
  } catch (cause) {
    error.value = orderError(cause);
    if ([409, 422].includes(cause.response?.status)) await afterMutation();
  } finally { busy.value = false; }
}
async function cancel(request) {
  if (busy.value) return;
  busy.value = true; error.value = '';
  try {
    const saved = await purchaseWorkflow.cancelRequest(code(request));
    acceptMutation(saved);
    toast.success('Pengajuan dibatalkan. Kebutuhan dapat diajukan kembali.'); announce(saved, 'material-request-cancelled'); await afterMutation();
  } catch (cause) { error.value = orderError(cause); if (cause.response?.status === 409) await afterMutation(); }
  finally { busy.value = false; }
}
function openDetail(request) { detailCode.value = code(request); detailOpen.value = true; }
</script>

<template>
  <section class="min-w-0 space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div><h2 class="text-lg font-semibold text-slate-900">Pengajuan Kebutuhan Barang</h2><p class="mt-1 max-w-2xl text-xs leading-relaxed text-slate-500">Kelola kebutuhan barang berdasarkan kekurangan stok pesanan. Finance melanjutkan pengajuan yang tersimpan menjadi purchase order.</p></div>
      <div class="flex flex-wrap gap-2"><button type="button" :disabled="refreshing || busy" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-50" @click="refresh"><RefreshCw :class="['h-4 w-4', refreshing ? 'animate-spin' : '']" />Muat Ulang</button><button v-if="!showForm" type="button" :disabled="loading || !hasLoaded || busy" class="inline-flex items-center gap-2 rounded-lg bg-alpharo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-alpharo-700 disabled:opacity-50" @click="error = ''; showForm = true"><Plus class="h-4 w-4" />Buat Pengajuan Kebutuhan</button></div>
    </div>
    <div v-if="syncError" role="alert" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">Pembaruan data belum berhasil: {{ syncError }} Isian pengajuan Anda tetap tersimpan. Gunakan Muat Ulang untuk mencoba kembali.</div>
    <p v-if="error && !showForm" role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ error }}</p>
    <MaterialRequestForm v-if="showForm" :orders="orders" :suppliers="suppliers" :busy="busy" :error="error" :actor="store.currentUser?.name || ''" @submit="submit" @clear-error="error = ''" @cancel="showForm = false; error = ''" />
    <section aria-labelledby="mr-list-title" class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
      <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-5"><div><h3 id="mr-list-title" class="text-sm font-semibold text-slate-900">Daftar Pengajuan Kebutuhan <span class="ml-1 rounded-md bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">{{ requests.length }}</span></h3><p class="mt-1 text-[11px] text-slate-500">{{ lastUpdated ? `Diperbarui ${displayTime(lastUpdated)} WIB` : 'Pembaruan otomatis saat halaman aktif' }}<span v-if="refreshing && !loading"> - Memperbarui...</span></p></div><div class="flex w-full flex-wrap gap-2 sm:w-auto"><div class="relative min-w-0 flex-1 sm:w-64"><label for="mr-list-search" class="sr-only">Cari Pengajuan</label><Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" /><input id="mr-list-search" v-model="search" type="search" placeholder="Cari pengajuan, pesanan, proyek..." class="w-full min-w-0 rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-xs" /></div><div><label for="mr-list-status" class="sr-only">Filter Status Pengajuan</label><select id="mr-list-status" v-model="status" class="max-w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-600"><option value="">Semua Status</option><option v-for="(text, value) in statuses" :key="value" :value="value">{{ text }}</option></select></div></div></div>
      <p v-if="loading" role="status" class="p-8 text-center text-sm text-slate-500">Memuat pengajuan dan kebutuhan pesanan...</p>
      <div v-else class="min-w-0 md:overflow-x-auto"><table class="mr-mobile-table block w-full min-w-0 text-left text-xs md:table md:min-w-[980px]"><caption class="sr-only">Daftar pengajuan kebutuhan barang</caption><thead class="hidden border-b border-slate-200 bg-slate-50 text-[11px] text-slate-500 md:table-header-group"><tr><th class="w-10 px-4 py-3">No.</th><th class="px-4 py-3">Pengajuan / Tanggal</th><th class="min-w-44 px-4 py-3">Pesanan / Proyek</th><th class="px-4 py-3 text-center">Jenis Barang</th><th class="px-4 py-3 text-right">Total Estimasi</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Aksi</th></tr></thead><tbody class="block divide-y divide-slate-100 md:table-row-group"><tr v-for="(request, index) in filteredRequests" :key="request.id || code(request)" class="grid min-w-0 grid-cols-2 p-3 hover:bg-slate-50/70 md:table-row md:p-0"><td class="hidden px-4 py-3.5 align-top text-slate-400 md:table-cell">{{ index + 1 }}</td><td class="col-span-2 block min-w-0 break-all px-2 py-2 align-top md:table-cell md:px-4 md:py-3.5"><h4 class="font-mono text-[11px] font-semibold text-slate-800">{{ code(request) }}</h4><p class="mt-1 text-[11px] text-slate-500">{{ displayDate(request.request_date) }}</p></td><td class="col-span-2 block min-w-0 break-words px-2 py-2 align-top md:table-cell md:px-4 md:py-3.5"><p class="font-medium text-slate-800">Pesanan {{ request.order_number || '-' }}</p><p class="mt-1 text-[11px] text-slate-500">{{ request.project_name || '-' }}</p><p v-if="request.purchase_code" class="mt-1 font-mono text-[10px] text-slate-500">PO: {{ request.purchase_code }}</p><p v-if="request.requirements_changed && canCancel(request)" class="mt-1 text-[11px] text-amber-700">Kebutuhan berubah; periksa pengajuan.</p></td><td data-label="Jenis Barang" class="block min-w-0 px-2 py-2 align-top text-slate-600 md:table-cell md:px-4 md:py-3.5 md:text-center">{{ request.items?.length || 0 }}</td><td data-label="Total Estimasi" class="block min-w-0 break-words px-2 py-2 align-top font-semibold text-slate-800 md:table-cell md:whitespace-nowrap md:px-4 md:py-3.5 md:text-right">{{ money(request.total_est_cost) }}</td><td class="col-span-2 block min-w-0 px-2 py-2 align-top md:table-cell md:px-4 md:py-3.5"><span :class="['inline-block rounded-md px-2 py-1 text-[10px] font-semibold', request.status === 'DIBATALKAN' ? 'bg-slate-100 text-slate-500' : request.status === 'SELESAI' ? 'bg-emerald-50 text-emerald-700' : request.status === 'PERLU_TINDAK_LANJUT' ? 'bg-amber-50 text-amber-800' : 'bg-blue-50 text-alpharo-600']">{{ statusLabel(request.status) }}</span></td><td class="col-span-2 block min-w-0 px-2 py-2 align-top md:table-cell md:px-4 md:py-2.5"><div class="flex flex-wrap gap-1.5"><button type="button" :aria-label="`Detail Pengajuan ${code(request)}`" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-2 text-[11px] font-medium text-slate-600 hover:bg-white" @click="openDetail(request)"><Eye class="h-3.5 w-3.5" />Detail</button><button v-if="canCancel(request)" type="button" :aria-label="`Batalkan Pengajuan ${code(request)}`" :disabled="busy" class="rounded-lg border border-red-200 px-2.5 py-2 text-[11px] font-medium text-red-600 hover:bg-red-50 disabled:opacity-50" @click="cancel(request)">Batalkan</button></div></td></tr><tr v-if="!filteredRequests.length" class="block md:table-row"><td colspan="7" class="ui-empty-state block px-4 py-10 md:table-cell text-center text-sm text-slate-500">{{ !hasLoaded && syncError ? 'Data pengajuan belum dapat dimuat. Gunakan Muat Ulang untuk mencoba kembali.' : requests.length ? 'Tidak ada pengajuan yang sesuai pencarian atau status.' : 'Belum ada pengajuan kebutuhan barang.' }}</td></tr></tbody></table></div>
    </section>
    <Modal v-model="detailOpen" :title="`Detail Pengajuan ${detailCode}`" subtitle="Rincian kebutuhan barang dan referensi pesanan sumber." size="xl"><div v-if="selectedRequest" class="min-w-0 space-y-5 break-words text-xs"><dl class="grid gap-4 rounded-lg bg-slate-50 p-4 sm:grid-cols-2"><div><dt class="text-slate-500">Pesanan Sumber</dt><dd class="mt-1 break-words font-semibold text-slate-800">{{ selectedRequest.order_number || '-' }}</dd></div><div><dt class="text-slate-500">Nama Proyek</dt><dd class="mt-1 break-words font-medium text-slate-800">{{ selectedRequest.project_name || '-' }}</dd></div><div><dt class="text-slate-500">Tanggal / Pengaju</dt><dd class="mt-1 text-slate-800">{{ displayDate(selectedRequest.request_date) }} / {{ selectedRequest.requested_by || '-' }}</dd></div><div><dt class="text-slate-500">Status Pengajuan</dt><dd class="mt-1 font-semibold text-alpharo-600">{{ statusLabel(selectedRequest.status) }}</dd></div><div v-if="selectedRequest.purchase_code" class="sm:col-span-2"><dt class="text-slate-500">Purchase Order</dt><dd class="mt-1 break-all font-mono font-semibold text-slate-800">{{ selectedRequest.purchase_code }}</dd></div></dl><section v-if="selectedRequest.procurement_draft" aria-label="Rencana Pembelian" class="rounded-lg border border-slate-200 bg-slate-50 p-4"><h4 class="mb-3 font-semibold text-slate-800">Header Pembelian</h4><dl class="grid min-w-0 gap-3 sm:grid-cols-2"><div><dt class="text-slate-500">Kode Pembelian</dt><dd class="mt-1 break-all font-mono">{{ selectedRequest.procurement_draft.purchase_code }}</dd></div><div><dt class="text-slate-500">Tanggal</dt><dd class="mt-1">{{ displayDate(selectedRequest.procurement_draft.transaction_date) }}</dd></div><div><dt class="text-slate-500">Supplier</dt><dd class="mt-1 break-words">{{ selectedRequest.procurement_draft.default_supplier }}</dd></div><div><dt class="text-slate-500">No Faktur</dt><dd class="mt-1 break-words">{{ selectedRequest.procurement_draft.invoice_number || '-' }}</dd></div><div><dt class="text-slate-500">Discount</dt><dd class="mt-1">{{ money(selectedRequest.procurement_draft.discount) }}</dd></div><div><dt class="text-slate-500">Jenis Pembayaran</dt><dd class="mt-1">{{ selectedRequest.procurement_draft.payment_method }}</dd></div></dl></section><div><h4 class="font-semibold text-slate-700">Keperluan Pengadaan</h4><p class="mt-1 whitespace-pre-wrap break-words text-slate-600">{{ selectedRequest.purpose }}</p></div><div v-if="selectedRequest.notes"><h4 class="font-semibold text-slate-700">Catatan Pengajuan</h4><p class="mt-1 whitespace-pre-wrap break-words text-slate-600">{{ selectedRequest.notes }}</p></div><p v-if="selectedRequest.requirements_changed && canCancel(selectedRequest)" class="rounded-lg bg-amber-50 p-3 text-amber-900">Kebutuhan pesanan telah berubah. Batalkan pengajuan ini jika belum memiliki PO, lalu ajukan kebutuhan terbaru.</p><div class="min-w-0 rounded-lg border border-slate-200 md:overflow-x-auto"><table class="mr-mobile-table block w-full min-w-0 text-left md:table md:min-w-[600px]"><thead class="hidden border-b bg-slate-50 text-slate-500 md:table-header-group"><tr><th class="p-3">Barang</th><th class="p-3 text-right">Jumlah</th><th class="p-3">Satuan</th><th class="p-3 text-right">Estimasi Harga / Unit</th><th class="p-3 text-right">Subtotal</th></tr></thead><tbody class="block divide-y md:table-row-group"><tr v-for="(item, index) in selectedRequest.items || []" :key="`${item.sku}-${index}`" class="grid min-w-0 grid-cols-2 md:table-row"><td class="col-span-2 block min-w-0 break-words p-3 md:table-cell"><p class="font-medium text-slate-800">{{ item.name }}</p><p class="mt-1 font-mono text-[11px] text-slate-500">{{ item.sku }}</p></td><td data-label="Jumlah" class="block min-w-0 p-3 md:table-cell md:text-right">{{ item.qty }}</td><td data-label="Satuan" class="block min-w-0 p-3 md:table-cell">{{ item.unit }}</td><td data-label="Estimasi Harga / Unit" class="block min-w-0 break-words p-3 md:table-cell md:whitespace-nowrap md:text-right">{{ money(item.est_price) }}<span v-if="Number(item.est_discount) > 0" class="mt-1 block text-[10px] text-slate-500">Diskon/satuan {{ money(item.est_discount) }}</span></td><td data-label="Subtotal" class="block min-w-0 break-words p-3 font-semibold md:table-cell md:whitespace-nowrap md:text-right">{{ money(item.subtotal ?? itemSubtotal(item)) }}</td></tr></tbody></table></div><div class="flex flex-wrap justify-between gap-3 rounded-lg bg-blue-50 p-4 text-alpharo-600"><span class="font-semibold">Total Estimasi Pengadaan</span><span class="text-base font-semibold">{{ money(selectedRequest.total_est_cost) }}</span></div></div><template #footer><button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-600" @click="detailOpen = false">Tutup</button></template></Modal>
  </section>
</template>




<style scoped>
@media (max-width: 767px) {
  .mr-mobile-table td[data-label]::before { content: attr(data-label); display: block; margin-bottom: 4px; font-size: 11px; font-weight: 400; color: var(--color-slate-500); }
}
</style>
