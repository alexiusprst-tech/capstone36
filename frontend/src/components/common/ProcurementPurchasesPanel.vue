<script setup>
import { computed, ref } from 'vue';
import { Search } from 'lucide-vue-next';
import Modal from './Modal.vue';
import PurchaseStatusBadge from './PurchaseStatusBadge.vue';
import PurchaseTrackingModal from './PurchaseTrackingModal.vue';
import PurchaseReceiptHistory from './PurchaseReceiptHistory.vue';
import ShippingStatusLabel from './ShippingStatusLabel.vue';
import { useLiveRefresh } from '../../composables/useLiveRefresh';
import { purchaseWorkflow } from '../../services/purchaseWorkflow';
import { orderError } from '../../services/orderWorkflow';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';

defineProps({ mode: { type: String, default: 'warehouse' } });
const purchases = ref([]), loading = ref(true), busy = ref(false), error = ref(''), filter = ref(''), statusFilter = ref('');
const selected = ref(null), receiptOpen = ref(false), followupOpen = ref(false), trackingOpen = ref(false), receiptLines = ref([]);
const date = ref(''), notes = ref(''), reference = ref(''), followupNotes = ref(''), retryPayload = ref(null), receiptKey = ref('');
const collapsed = ref(new Set()), returnOpen = ref(new Set());
const toast = useToast();
const needsFollowup = purchase => purchase.pending_followup || ['Ketidaksesuaian Fisik (Menunggu Retur)', 'Ketidaksesuaian', 'Ditolak Supplier'].includes(purchase.supplier_status);
const complete = purchase => purchase.status === 'DITERIMA_GUDANG';
const visible = computed(() => purchases.value.filter(purchase => `${purchase.purchase_code} ${purchase.order_number || ''} ${purchase.project_name || ''} ${purchase.default_supplier || ''} ${purchase.delivery_info?.tracking_number || ''}`.toLowerCase().includes(filter.value.toLowerCase()) && (!statusFilter.value || (statusFilter.value === 'followup' ? needsFollowup(purchase) : statusFilter.value === 'open' ? !complete(purchase) : purchase.status === statusFilter.value))));
const latestSelected = computed(() => purchases.value.find(purchase => purchase.purchase_code === selected.value?.purchase_code));
const stale = computed(() => !!selected.value && (!latestSelected.value || Number(latestSelected.value.progress_version || 0) !== Number(selected.value.progress_version || 0)));
const retryConfirmed = computed(() => !!retryPayload.value && latestSelected.value?.receipts?.some(receipt => receipt.idempotency_key === retryPayload.value.idempotency_key));
const stats = computed(() => [
  { label: 'Total PO', count: purchases.value.length, filter: '' },
  { label: 'Belum Selesai', count: purchases.value.filter(purchase => !complete(purchase)).length, filter: 'open' },
  { label: 'Perlu Tindak Lanjut', count: purchases.value.filter(needsFollowup).length, filter: 'followup' },
  { label: 'Diterima Lengkap', count: purchases.value.filter(complete).length, filter: 'DITERIMA_GUDANG' },
]);
const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
const number = value => Number(value || 0).toLocaleString('id-ID');
const storageKey = purchase => `alpharo-receipt-retry:${store.currentUser?.id || store.currentUser?.email || 'warehouse'}:${purchase.purchase_code}`;
const statusLabel = status => ({ DIPESAN: 'Dipesan', DITERIMA_SEBAGIAN: 'Diterima sebagian', PERLU_TINDAK_LANJUT: 'Perlu tindak lanjut', DITERIMA_GUDANG: 'Diterima lengkap', DIBATALKAN: 'Dibatalkan' })[status] || status || 'Belum tersedia';
const statusTone = purchase => needsFollowup(purchase) ? 'amber' : complete(purchase) ? 'green' : 'blue';
const lineStatus = (line, purchase) => line.qc_label || (Number(line.remaining_qty) === 0 ? (Number(line.rejected_qty) > 0 ? 'Pengganti diterima' : 'Sesuai') : Number(line.rejected_qty) > 0 ? (needsFollowup(purchase) ? 'Ditolak / retur saat penerimaan' : 'Menunggu pengganti') : Number(line.accepted_qty) > 0 ? 'Diterima sebagian' : 'Belum diperiksa');
const totals = purchase => ({ ordered: (purchase.receipt_summary || []).reduce((sum, line) => sum + Number(line.expected_qty || 0), 0), accepted: (purchase.receipt_summary || []).reduce((sum, line) => sum + Number(line.accepted_qty || 0), 0), rejected: (purchase.receipt_summary || []).reduce((sum, line) => sum + Number(line.rejected_qty || 0), 0), remaining: (purchase.receipt_summary || []).reduce((sum, line) => sum + Number(line.remaining_qty || 0), 0) });
const returnItems = purchase => purchase.return_items || (purchase.receipts || []).flatMap(receipt => (receipt.lines || []).filter(line => Number(line.rejected_qty) > 0).map(line => ({ ...line, receipt_id: receipt.id, received_date: receipt.received_date, received_by_name: receipt.receiver?.name || receipt.receiver_name })));
const returnLabel = purchase => ({ PERLU_TINDAK_LANJUT: 'Menunggu tindak lanjut Finance', MENUNGGU_PENGGANTI: 'Menunggu penerimaan barang pengganti', PENGGANTI_DITERIMA: 'Barang pengganti telah diterima' })[purchase.inspection?.return_status] || (needsFollowup(purchase) ? 'Menunggu tindak lanjut Finance' : complete(purchase) ? 'Kebutuhan telah diterima lengkap' : 'Tindak lanjut / penggantian pada PO asal');
function localDate() { return new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' }); }
function toggle(set, code) { const next = new Set(set.value); next.has(code) ? next.delete(code) : next.add(code); set.value = next; }
function toggleDetails(code) { toggle(collapsed, code); }
function toggleReturns(code) {
  toggle(returnOpen, code);
  if (returnOpen.value.has(code) && collapsed.value.has(code)) toggle(collapsed, code);
}
let loadTask = null, mutationGeneration = 0;
function newer(record, candidate) {
  return candidate && Number(candidate.progress_version || 0) > Number(record.progress_version || 0) ? candidate : record;
}
function mergePurchase(record) {
  if (!record?.purchase_code) return;
  const index = purchases.value.findIndex(purchase => purchase.purchase_code === record.purchase_code);
  if (index < 0) purchases.value = [record, ...purchases.value];
  else purchases.value = purchases.value.map((purchase, i) => i === index ? newer(record, purchase) : purchase);
  storeActions.mergePurchases([record]);
}
function load({ silent = false } = {}) {
  if (loadTask) return loadTask;
  if (!silent) { loading.value = true; if (!receiptOpen.value && !followupOpen.value) error.value = ''; }
  const generation = mutationGeneration;
  loadTask = (async () => {
    try {
      const rows = await purchaseWorkflow.list();
      if (generation !== mutationGeneration) return;
      const current = new Map(purchases.value.map(purchase => [purchase.purchase_code, purchase]));
      const persisted = new Map((store.purchases || []).filter(purchase => purchase.progress_persisted).map(purchase => [purchase.purchase_code, purchase]));
      purchases.value = rows.map(row => newer(newer(row, current.get(row.purchase_code)), persisted.get(row.purchase_code)));
      storeActions.mergePurchases(purchases.value);
      for (const purchase of purchases.value) {
        try {
          const saved = JSON.parse(localStorage.getItem(storageKey(purchase)) || 'null');
          if (saved && purchase.receipts?.some(receipt => receipt.idempotency_key === saved.idempotency_key)) localStorage.removeItem(storageKey(purchase));
        } catch { /* A corrupt browser retry does not replace authoritative server records. */ }
      }
      liveError.value = '';
    } catch (cause) {
      if (silent) liveError.value = orderError(cause); else error.value = orderError(cause);
      throw cause;
    } finally { loading.value = false; loadTask = null; }
  })();
  return loadTask;
}
const liveError = ref('');
const { lastUpdated, refreshing } = useLiveRefresh(() => load({ silent: true }), { enabled: () => !busy.value });
async function manualLoad() { try { await load(); } catch { /* load exposes the error next to the current data. */ } }
function openReceipt(purchase) {
  selected.value = { ...purchase }; error.value = ''; retryPayload.value = null;
  try {
    const saved = JSON.parse(localStorage.getItem(storageKey(purchase)) || 'null');
    retryPayload.value = saved && typeof saved.idempotency_key === 'string' && Array.isArray(saved.items) ? saved : null;
  } catch { /* Ignore malformed local data, never invent receipt history. */ }
  receiptKey.value = retryPayload.value?.idempotency_key || crypto.randomUUID();
  date.value = retryPayload.value?.received_date || localDate(); notes.value = retryPayload.value?.notes || '';
  const summaries = purchase.receipt_summary || [];
  receiptLines.value = summaries.filter(line => Number(line.remaining_qty) > 0 || retryPayload.value?.items.some(item => item.line_index === line.line_index)).map(line => {
    const saved = retryPayload.value?.items.find(item => item.line_index === line.line_index);
    return { ...line, accepted_qty: saved?.accepted_qty || 0, rejected_qty: saved?.rejected_qty || 0, reason: saved?.reason || '' };
  });
  receiptOpen.value = true;
}
async function receive() {
  if (busy.value || retryConfirmed.value || (stale.value && !retryPayload.value)) return;
  const items = receiptLines.value.map(line => ({ line_index: line.line_index, accepted_qty: Number(line.accepted_qty), rejected_qty: Number(line.rejected_qty), reason: line.reason.trim() })).filter(line => line.accepted_qty > 0 || line.rejected_qty > 0);
  if (!retryPayload.value && (!items.length || receiptLines.value.some(line => !Number.isInteger(Number(line.accepted_qty)) || !Number.isInteger(Number(line.rejected_qty)) || Number(line.accepted_qty) < 0 || Number(line.rejected_qty) < 0 || Number(line.accepted_qty) + Number(line.rejected_qty) > Number(line.remaining_qty) || (Number(line.rejected_qty) > 0 && !line.reason.trim())))) { error.value = 'Isi jumlah diterima / ditolak sesuai sisa PO dan alasan untuk setiap penolakan.'; return; }
  const payload = retryPayload.value || { idempotency_key: receiptKey.value, version: selected.value.progress_version || 0, received_date: date.value, notes: notes.value.trim(), items };
  try { localStorage.setItem(storageKey(selected.value), JSON.stringify(payload)); }
  catch { error.value = 'Browser tidak dapat menyimpan kunci percobaan ulang. Aktifkan penyimpanan browser sebelum mencatat penerimaan.'; return; }
  retryPayload.value = payload; busy.value = true; error.value = ''; mutationGeneration++;
  try {
    const result = await purchaseWorkflow.receive(selected.value, payload); mergePurchase(result.purchase || result);
    localStorage.removeItem(storageKey(selected.value)); receiptOpen.value = false;
    toast.success('Penerimaan tersimpan. Hanya barang diterima yang menambah stok.');
    try { await load({ silent: true }); await storeActions.loadOrders(); } catch { /* The saved receipt remains successful even if the subsequent refresh fails. */ }
  } catch (cause) {
    error.value = orderError(cause);
    if (cause.response && cause.response.status < 500) { localStorage.removeItem(storageKey(selected.value)); retryPayload.value = null; receiptKey.value = crypto.randomUUID(); }
    if (cause.response?.status === 409) { try { await load({ silent: true }); } catch { /* Keep typed values and the conflict visible. */ } }
  } finally { busy.value = false; }
}
function openTracking(purchase) { selected.value = { ...purchase }; trackingOpen.value = true; }
function openFollowup(purchase) { selected.value = { ...purchase }; reference.value = ''; followupNotes.value = ''; error.value = ''; followupOpen.value = true; }
async function followup() {
  if (busy.value || stale.value || !reference.value.trim() || !followupNotes.value.trim()) return;
  busy.value = true; error.value = ''; mutationGeneration++;
  try {
    const result = await purchaseWorkflow.followup(selected.value, { reference: reference.value.trim(), notes: followupNotes.value.trim() }); mergePurchase(result); followupOpen.value = false;
    toast.success('Tindak lanjut supplier tersimpan pada PO asal.'); try { await load({ silent: true }); } catch { /* Mutation already succeeded. */ }
  } catch (cause) { error.value = orderError(cause); if (cause.response?.status === 409) { try { await load({ silent: true }); } catch { /* Preserve Finance input. */ } } }
  finally { busy.value = false; }
}
</script>

<template>
  <section class="min-w-0 space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div><h2 class="text-lg font-semibold text-slate-900">{{ mode === 'warehouse' ? 'Penerimaan Fisik Barang Supplier' : 'Daftar Purchase Order' }}</h2><p class="mt-1 text-xs text-slate-500">{{ mode === 'warehouse' ? 'Lacak kiriman, periksa barang, dan catat penerimaan pada PO asal.' : 'Pantau PO, pengiriman, hasil pemeriksaan, dan tindak lanjut Supplier.' }}</p></div>
      <button type="button" :disabled="loading || busy || refreshing" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50" @click="manualLoad">Muat Ulang PO</button>
    </div>
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4"><button v-for="stat in stats" :key="stat.label" type="button" :aria-pressed="statusFilter === stat.filter" :class="['rounded-lg border bg-white p-3 text-left', statusFilter === stat.filter ? 'border-blue-600 ring-1 ring-blue-600' : 'border-slate-200']" @click="statusFilter = stat.filter"><span class="block text-xs text-slate-500">{{ stat.label }}</span><span class="mt-1 block text-xl font-semibold text-slate-900">{{ number(stat.count) }}</span></button></div>
    <div class="grid gap-3 rounded-lg border border-slate-200 bg-white p-3 sm:grid-cols-[1fr_210px]">
      <label class="block text-xs font-medium text-slate-700">Cari PO, Pesanan, Proyek atau Supplier<span class="relative mt-1 block"><Search aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" /><input v-model="filter" type="search" placeholder="Kode PO, Supplier, atau nomor resi" class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm" /></span></label>
      <label class="block text-xs font-medium text-slate-700">Status PO<select v-model="statusFilter" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-base sm:text-sm"><option value="">Semua PO</option><option value="open">Belum selesai</option><option value="DIPESAN">Dipesan</option><option value="DITERIMA_SEBAGIAN">Diterima sebagian</option><option value="followup">Perlu tindak lanjut</option><option value="DITERIMA_GUDANG">Diterima lengkap</option></select></label>
    </div>
    <p class="text-[11px] text-slate-500" role="status">{{ refreshing ? 'Memperbarui data PO…' : 'Pembaruan otomatis setiap 10 detik saat halaman terlihat.' }}<span v-if="lastUpdated"> Terakhir {{ lastUpdated.toLocaleTimeString('id-ID') }}.</span></p>
    <p v-if="liveError" role="alert" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">Pembaruan otomatis gagal. Data terakhir tetap ditampilkan. {{ liveError }}</p>
    <p v-if="error && !receiptOpen && !followupOpen" role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ error }}</p>
    <p v-if="loading" role="status" class="p-5 text-sm text-slate-600">Memuat purchase order…</p>
    <p v-else-if="!visible.length" class="rounded-lg border border-dashed bg-white p-8 text-center text-sm text-slate-500">Tidak ada purchase order yang sesuai.</p>
    <article v-for="purchase in visible" v-else :key="purchase.purchase_code" class="min-w-0 overflow-hidden rounded-lg border border-slate-200 bg-white">
      <header class="flex flex-wrap justify-between gap-3 border-b border-slate-100 px-4 py-3">
        <div class="min-w-0"><h3 class="font-semibold text-slate-900">{{ purchase.purchase_code }}</h3><p class="mt-1 break-words text-xs text-slate-600">{{ purchase.default_supplier || 'Supplier belum terhubung' }} · {{ purchase.project_name || 'Proyek belum dicatat' }}</p><p class="mt-1 text-[11px] text-slate-500">Pesanan {{ purchase.order_number || '—' }} · Pengajuan {{ purchase.warehouse_request_code || '—' }} · {{ purchase.transaction_date || 'Tanggal belum tersedia' }}</p></div>
        <div class="text-right"><PurchaseStatusBadge :label="statusLabel(purchase.status)" :tone="statusTone(purchase)" /><p class="mt-2 text-sm font-semibold text-slate-900">{{ money(purchase.total_price) }}</p></div>
      </header>
      <div class="grid gap-3 border-b border-slate-100 bg-slate-50/60 px-4 py-3 text-xs sm:grid-cols-2 lg:grid-cols-4">
        <div><span class="block text-[11px] text-slate-500">Status Supplier / Pengiriman</span><ShippingStatusLabel class="mt-1 font-medium text-slate-700" :status="purchase.delivery_info?.shipping_status || purchase.supplier_status" fallback="Belum dikonfirmasi" /></div>
        <div><span class="block text-[11px] text-slate-500">Kurir · Resi / Plat</span><span class="mt-1 block break-all text-slate-700">{{ purchase.delivery_info?.courier || 'Belum diisi' }} · {{ purchase.delivery_info?.tracking_number || 'Belum tersedia' }}</span></div>
        <div><span class="block text-[11px] text-slate-500">Surat Jalan · Estimasi Tiba</span><span class="mt-1 block text-slate-700">{{ purchase.delivery_info?.surat_jalan || 'Belum tersedia' }} · {{ purchase.delivery_info?.est_arrival || 'Belum tersedia' }}</span></div>
        <div><span class="block text-[11px] text-slate-500">Ringkasan Jumlah Barang</span><span class="mt-1 block text-slate-700">{{ number(totals(purchase).accepted) }} diterima / {{ number(totals(purchase).ordered) }} dipesan · {{ number(totals(purchase).remaining) }} sisa</span></div>
      </div>
      <div class="flex flex-wrap items-center gap-2 px-4 py-3">
        <button type="button" class="rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50" @click="openTracking(purchase)">Cek Resi {{ purchase.purchase_code }}</button>
        <button type="button" :aria-expanded="!collapsed.has(purchase.purchase_code)" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50" @click="toggleDetails(purchase.purchase_code)">Detail PO {{ purchase.purchase_code }}</button>
        <button v-if="totals(purchase).rejected > 0" type="button" :aria-expanded="returnOpen.has(purchase.purchase_code)" class="rounded-lg border border-amber-200 px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-50" @click="toggleReturns(purchase.purchase_code)">Ditolak / Retur {{ purchase.purchase_code }} ({{ number(totals(purchase).rejected) }})</button>
        <button v-if="mode === 'warehouse' && !complete(purchase) && purchase.status !== 'DIBATALKAN' && totals(purchase).remaining > 0" type="button" :disabled="busy || needsFollowup(purchase)" class="rounded-lg bg-alpharo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50" @click="openReceipt(purchase)">Catat Penerimaan {{ purchase.purchase_code }}</button>
        <button v-if="mode === 'finance' && needsFollowup(purchase)" type="button" :disabled="busy" class="rounded-lg bg-alpharo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50" @click="openFollowup(purchase)">Tindak Lanjut {{ purchase.purchase_code }}</button>
      </div>
      <div v-if="!collapsed.has(purchase.purchase_code)" class="border-t border-slate-100">
        <div class="min-w-0 sm:overflow-x-auto">
          <table class="block w-full min-w-0 text-left text-xs sm:table sm:min-w-[760px]">
            <caption class="sr-only">Ringkasan penerimaan {{ purchase.purchase_code }}</caption>
            <thead class="hidden border-b bg-slate-50 text-[11px] text-slate-500 sm:table-header-group"><tr><th class="px-4 py-2.5">Barang / Satuan</th><th class="p-2.5 text-right">Dipesan</th><th class="p-2.5 text-right">Diterima</th><th class="p-2.5 text-right">Ditolak / Retur (Riwayat)</th><th class="p-2.5 text-right">Sisa Belum Diterima</th><th class="p-2.5">Status Barang</th></tr></thead>
            <tbody class="block divide-y divide-slate-100 sm:table-row-group">
              <tr v-for="line in purchase.receipt_summary || []" :key="line.line_index" class="grid min-w-0 grid-cols-2 px-1 py-2 sm:table-row sm:p-0">
                <td class="col-span-2 block min-w-0 break-words px-3 py-2 font-medium text-slate-800 sm:table-cell sm:px-4 sm:py-3">{{ line.name }}<span class="mt-1 block break-all text-[11px] font-normal text-slate-500">{{ line.sku }} · {{ line.unit }}</span></td>
                <td class="block min-w-0 px-3 py-2 sm:table-cell sm:p-2.5 sm:text-right"><span class="mb-1 block text-[11px] text-slate-500 sm:hidden">Dipesan</span>{{ number(line.expected_qty) }}</td>
                <td class="block min-w-0 px-3 py-2 sm:table-cell sm:p-2.5 sm:text-right"><span class="mb-1 block text-[11px] text-slate-500 sm:hidden">Diterima</span>{{ number(line.accepted_qty) }}</td>
                <td class="block min-w-0 px-3 py-2 sm:table-cell sm:p-2.5 sm:text-right"><span class="mb-1 block text-[11px] text-slate-500 sm:hidden">Ditolak / Retur (Riwayat)</span>{{ number(line.rejected_qty) }}</td>
                <td class="block min-w-0 px-3 py-2 font-semibold sm:table-cell sm:p-2.5 sm:text-right"><span class="mb-1 block text-[11px] font-normal text-slate-500 sm:hidden">Sisa Belum Diterima</span>{{ number(line.remaining_qty) }}</td>
                <td class="col-span-2 block min-w-0 px-3 py-2 sm:table-cell sm:p-2.5"><span class="mb-1 block text-[11px] text-slate-500 sm:hidden">Status Barang</span><PurchaseStatusBadge :label="lineStatus(line, purchase)" :tone="Number(line.remaining_qty) === 0 ? 'green' : Number(line.rejected_qty) > 0 ? 'amber' : 'neutral'" /></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="space-y-3 p-4">
          <p v-if="needsFollowup(purchase)" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">Ada ketidaksesuaian saat penerimaan. Finance harus mencatat tindak lanjut Supplier sebelum Gudang menerima barang berikutnya.</p>
          <div v-if="purchase.followup_reference" class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-xs text-blue-900"><p class="font-semibold">Finance Tindak Lanjut: {{ purchase.followup_reference }}</p><p class="mt-1">{{ purchase.followup_notes }}</p><p class="mt-1">{{ returnLabel(purchase) }}</p></div>
          <section v-if="returnOpen.has(purchase.purchase_code)" class="rounded-lg border border-amber-200 p-3 text-xs"><h4 class="font-semibold text-amber-900">Ditolak / Retur Saat Penerimaan</h4><p class="mt-1 text-slate-600">{{ returnLabel(purchase) }}. Barang ini ditolak saat pemeriksaan dan tidak pernah menambah stok. Sisa kebutuhan menunggu barang yang diterima sesuai PO.</p><ul class="mt-3 divide-y divide-slate-100"><li v-for="(line, index) in returnItems(purchase)" :key="`${line.receipt_id}-${index}`" class="py-2"><p class="font-medium">{{ line.sku }} · {{ line.name }} — {{ number(line.rejected_qty) }} {{ line.unit }}</p><p class="mt-1 text-slate-600">{{ line.reason || 'Alasan tidak dicatat' }}</p><p class="mt-1 text-[11px] text-slate-500">Penerimaan #{{ line.receipt_id }} · {{ String(line.received_date || '').slice(0, 10) }} · {{ line.received_by_name || 'Petugas tidak dicatat' }}</p></li></ul></section>
          <PurchaseReceiptHistory :receipts="purchase.receipts || []" />
        </div>
      </div>
    </article>
    <PurchaseTrackingModal v-model="trackingOpen" :purchase="latestSelected || selected" />
    <Modal v-model="receiptOpen" :title="`Catat Penerimaan ${selected?.purchase_code || ''}`" subtitle="Periksa fisik per barang. Hanya jumlah diterima yang masuk stok." size="xl">
      <form class="space-y-4" @submit.prevent="receive">
        <p v-if="error" role="alert" class="rounded-lg bg-red-50 p-3 text-xs text-red-800">{{ error }}</p>
        <p v-if="retryConfirmed" role="status" class="rounded-lg bg-emerald-50 p-3 text-xs text-emerald-800">Penerimaan sebelumnya sudah terkonfirmasi di server. Tutup dialog untuk melihat hasilnya.</p>
        <p v-else-if="retryPayload" role="status" class="rounded-lg bg-amber-50 p-3 text-xs text-amber-900">Percobaan sebelumnya belum terkonfirmasi. Kirim ulang data yang sama agar stok tidak tercatat ganda.</p>
        <p v-if="stale && !retryPayload" role="alert" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">PO berubah sejak dialog dibuka. Isian tetap tersimpan di dialog ini. Tutup lalu buka kembali Catat Penerimaan untuk memeriksa sisa dan status terbaru sebelum menyimpan.</p>
        <fieldset :disabled="busy || !!retryPayload || stale" class="min-w-0 space-y-4">
          <div class="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 sm:grid-cols-2"><div><p class="text-[11px] text-slate-500">Supplier</p><p class="mt-1 text-xs font-semibold">{{ selected?.default_supplier }}</p></div><label class="block text-xs font-medium">Tanggal Penerimaan<input v-model="date" type="date" :max="localDate()" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white p-2 text-base sm:text-sm" /></label></div>
          <div class="min-w-0 rounded-lg border border-slate-200 sm:overflow-x-auto">
            <table class="block w-full min-w-0 text-left text-xs sm:table sm:min-w-[620px]">
              <thead class="hidden bg-slate-50 text-[11px] text-slate-500 sm:table-header-group"><tr><th class="p-3">Barang / Satuan</th><th class="p-3 text-right">Sisa PO</th><th class="p-3">Diterima Sesuai</th><th class="p-3">Ditolak / Retur</th></tr></thead>
              <tbody class="block sm:table-row-group">
                <template v-for="line in receiptLines" :key="line.line_index">
                  <tr class="grid min-w-0 grid-cols-2 border-t border-slate-100 first:border-t-0 sm:table-row">
                    <td class="col-span-2 min-w-0 break-words px-3 pt-3 font-medium sm:p-3">{{ line.name }}<span class="mt-1 block break-all text-[11px] font-normal text-slate-500">{{ line.sku }} · {{ line.unit }}</span></td>
                    <td class="col-span-2 flex justify-between gap-2 px-3 pt-2 font-semibold sm:table-cell sm:p-3 sm:text-right"><span class="font-normal text-slate-500 sm:hidden">Sisa PO</span>{{ line.remaining_qty }}</td>
                    <td class="block min-w-0 p-3 sm:table-cell"><span class="mb-1 block text-[11px] text-slate-600 sm:hidden">Diterima Sesuai</span><input v-model.number="line.accepted_qty" :aria-label="`Diterima ${line.sku}`" type="number" min="0" :max="Math.max(0, line.remaining_qty - Number(line.rejected_qty))" step="1" required class="w-full min-w-0 rounded-lg border border-slate-300 p-2 text-base sm:w-24 sm:text-xs" /></td>
                    <td class="block min-w-0 p-3 sm:table-cell"><span class="mb-1 block text-[11px] text-slate-600 sm:hidden">Ditolak / Retur</span><input v-model.number="line.rejected_qty" :aria-label="`Ditolak ${line.sku}`" type="number" min="0" :max="Math.max(0, line.remaining_qty - Number(line.accepted_qty))" step="1" required class="w-full min-w-0 rounded-lg border border-slate-300 p-2 text-base sm:w-24 sm:text-xs" /></td>
                  </tr>
                  <tr v-if="Number(line.rejected_qty) > 0" class="block min-w-0 sm:table-row"><td colspan="4" class="block min-w-0 px-3 pb-3 sm:table-cell"><label class="block text-xs text-amber-900">Alasan Penolakan<input v-model="line.reason" :aria-label="`Alasan penolakan ${line.sku}`" required maxlength="2000" placeholder="Jelaskan kerusakan atau ketidaksesuaian barang" class="mt-1 block w-full min-w-0 rounded-lg border border-amber-300 bg-amber-50 p-2 text-base text-slate-800 sm:text-xs" /></label></td></tr>
                </template>
              </tbody>
            </table>
          </div>
          <p class="text-[11px] text-slate-500">Barang ditolak / retur dicatat saat penerimaan dan tidak masuk stok. Jumlah tersebut tetap menjadi sisa kebutuhan PO sampai barang pengganti diterima.</p>
          <label class="block text-xs font-medium text-slate-700">Catatan Penerimaan<textarea v-model="notes" maxlength="2000" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 p-2 text-base sm:text-sm"></textarea></label>
        </fieldset>
        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-3"><button type="button" :disabled="busy" class="rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-600" @click="receiptOpen = false">Tutup</button><button type="submit" :disabled="busy || !receiptLines.length || retryConfirmed || (stale && !retryPayload)" class="rounded-lg bg-alpharo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50">{{ busy ? 'Menyimpan…' : 'Simpan Penerimaan' }}</button></div>
      </form>
    </Modal>
    <Modal v-model="followupOpen" :title="`Tindak Lanjut ${selected?.purchase_code || ''}`" subtitle="Finance mencatat komunikasi atau penggantian barang ditolak pada PO asal.">
      <form class="space-y-4" @submit.prevent="followup"><p v-if="error" role="alert" class="rounded-lg bg-red-50 p-3 text-xs text-red-800">{{ error }}</p><p v-if="stale" role="alert" class="rounded-lg bg-amber-50 p-3 text-xs text-amber-900">PO berubah sejak dialog dibuka. Isian tetap ditampilkan. Tutup lalu buka kembali untuk memeriksa data terbaru.</p><fieldset :disabled="busy || stale" class="space-y-4"><label class="block text-xs font-medium">Referensi Tindak Lanjut<input v-model="reference" required maxlength="255" class="mt-1 block w-full rounded-lg border border-slate-300 p-2.5 text-base sm:text-sm" /></label><label class="block text-xs font-medium">Catatan Tindak Lanjut<textarea v-model="followupNotes" required maxlength="2000" rows="4" class="mt-1 block w-full rounded-lg border border-slate-300 p-2.5 text-base sm:text-sm"></textarea></label></fieldset><button type="submit" :disabled="busy || stale" class="rounded-lg bg-alpharo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50">{{ busy ? 'Menyimpan…' : 'Simpan Tindak Lanjut' }}</button></form>
    </Modal>
  </section>
</template>
