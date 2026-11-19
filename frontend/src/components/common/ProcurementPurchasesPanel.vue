<script setup>
import { computed, onMounted, ref } from 'vue';
import Modal from './Modal.vue';
import { purchaseWorkflow } from '../../services/purchaseWorkflow';
import { orderError } from '../../services/orderWorkflow';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
const props = defineProps({ mode: { type: String, default: 'warehouse' } });
const purchases = ref([]), loading = ref(true), busy = ref(false), error = ref(''), filter = ref('');
const selected = ref(null), receiptOpen = ref(false), followupOpen = ref(false), receiptLines = ref([]);
const date = ref(''), notes = ref(''), reference = ref(''), followupNotes = ref(''), retryPayload = ref(null), receiptKey = ref('');
const toast = useToast();
const visible = computed(() => purchases.value.filter(purchase => `${purchase.purchase_code} ${purchase.order_number || ''} ${purchase.project_name} ${purchase.default_supplier}`.toLowerCase().includes(filter.value.toLowerCase())));
const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
const storageKey = purchase => `alpharo-receipt-retry:${store.currentUser?.id || store.currentUser?.email || 'warehouse'}:${purchase.purchase_code}`;
const needsFollowup = purchase => purchase.pending_followup || ['Ketidaksesuaian Fisik (Menunggu Retur)', 'Ketidaksesuaian', 'Ditolak Supplier'].includes(purchase.supplier_status);
const statusLabel = status => ({ DIPESAN: 'Dipesan', DITERIMA_SEBAGIAN: 'Diterima sebagian', PERLU_TINDAK_LANJUT: 'Perlu tindak lanjut', DITERIMA_GUDANG: 'Diterima lengkap' })[status] || status;
function localDate() { return new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' }); }
async function load() {
  loading.value = true; error.value = '';
  try {
    purchases.value = await purchaseWorkflow.list(); storeActions.mergePurchases(purchases.value);
    for (const purchase of purchases.value) {
      try {
        const saved = JSON.parse(localStorage.getItem(storageKey(purchase)) || 'null');
        if (saved && purchase.receipts?.some(receipt => receipt.idempotency_key === saved.idempotency_key)) localStorage.removeItem(storageKey(purchase));
      } catch { /* Corrupt browser drafts do not prevent loading authoritative PO records. */ }
    }
  }
  catch (cause) { error.value = orderError(cause); }
  finally { loading.value = false; }
}
function openReceipt(purchase) {
  selected.value = purchase; error.value = ''; retryPayload.value = null;
  try {
    const saved = JSON.parse(localStorage.getItem(storageKey(purchase)) || 'null');
    retryPayload.value = saved && typeof saved.idempotency_key === 'string' && Array.isArray(saved.items) ? saved : null;
  } catch { /* Ignore a corrupt browser draft; the server remains authoritative. */ }
  receiptKey.value = retryPayload.value?.idempotency_key || crypto.randomUUID();
  date.value = retryPayload.value?.received_date || localDate(); notes.value = retryPayload.value?.notes || '';
  receiptLines.value = (purchase.receipt_summary || []).filter(line => Number(line.remaining_qty) > 0).map(line => ({ ...line, accepted_qty: retryPayload.value?.items.find(item => item.line_index === line.line_index)?.accepted_qty || 0, rejected_qty: retryPayload.value?.items.find(item => item.line_index === line.line_index)?.rejected_qty || 0, reason: retryPayload.value?.items.find(item => item.line_index === line.line_index)?.reason || '' }));
  receiptOpen.value = true;
}
async function receive() {
  if (busy.value) return;
  const items = receiptLines.value.map(line => ({ line_index: line.line_index, accepted_qty: Number(line.accepted_qty), rejected_qty: Number(line.rejected_qty), reason: line.reason.trim() })).filter(line => line.accepted_qty > 0 || line.rejected_qty > 0);
  if (!retryPayload.value && (!items.length || receiptLines.value.some(line => !Number.isInteger(Number(line.accepted_qty)) || !Number.isInteger(Number(line.rejected_qty)) || Number(line.accepted_qty) < 0 || Number(line.rejected_qty) < 0 || Number(line.accepted_qty) + Number(line.rejected_qty) > Number(line.remaining_qty) || (Number(line.rejected_qty) > 0 && !line.reason.trim())))) { error.value = 'Isi jumlah diterima / ditolak sesuai sisa PO dan alasan untuk setiap penolakan.'; return; }
  const payload = retryPayload.value || { idempotency_key: receiptKey.value, version: selected.value.progress_version || 0, received_date: date.value, notes: notes.value.trim(), items };
  // Keep the exact request across uncertain network failures, navigation and reload.
  try { localStorage.setItem(storageKey(selected.value), JSON.stringify(payload)); }
  catch { error.value = 'Browser tidak dapat menyimpan kunci percobaan ulang. Aktifkan penyimpanan browser sebelum mencatat penerimaan.'; return; }
  retryPayload.value = payload; busy.value = true; error.value = '';
  try {
    const result = await purchaseWorkflow.receive(selected.value, payload); storeActions.mergePurchases([result.purchase || result]);
    localStorage.removeItem(storageKey(selected.value)); receiptOpen.value = false; toast.success('Penerimaan tersimpan. Hanya barang diterima yang menambah stok.'); await load(); await storeActions.loadOrders();
  } catch (cause) {
    error.value = orderError(cause);
    if (cause.response && cause.response.status < 500) { localStorage.removeItem(storageKey(selected.value)); retryPayload.value = null; receiptKey.value = crypto.randomUUID(); }
    if (cause.response?.status === 409) {
      const message = error.value; receiptOpen.value = false; await load(); error.value = `${message} Data PO sudah diperbarui; periksa sisa dan status sebelum melanjutkan.`;
    }
  } finally { busy.value = false; }
}
function openFollowup(purchase) { selected.value = purchase; reference.value = ''; followupNotes.value = ''; error.value = ''; followupOpen.value = true; }
async function followup() {
  if (busy.value || !reference.value.trim() || !followupNotes.value.trim()) return;
  busy.value = true; error.value = '';
  try { const result = await purchaseWorkflow.followup(selected.value, { reference: reference.value.trim(), notes: followupNotes.value.trim() }); storeActions.mergePurchases([result]); followupOpen.value = false; toast.success('Tindak lanjut supplier tersimpan pada PO asal.'); await load(); }
  catch (cause) {
    error.value = orderError(cause);
    if (cause.response?.status === 409) { const message = error.value; followupOpen.value = false; await load(); error.value = `${message} Data PO sudah diperbarui.`; }
  }
  finally { busy.value = false; }
}
onMounted(load);
</script>

<template>
  <section class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-lg font-semibold text-slate-900">{{ mode === 'warehouse' ? 'Penerimaan Fisik Barang Supplier' : 'Daftar Purchase Order' }}</h2><p class="mt-1 text-sm text-slate-600">{{ mode === 'warehouse' ? 'Periksa setiap barang. Catat jumlah diterima, ditolak, dan alasan ketidaksesuaian.' : 'Pantau pengadaan dan catat tindak lanjut supplier pada PO yang sama.' }}</p></div><button type="button" :disabled="loading || busy" class="rounded-lg border bg-white px-4 py-2 text-sm disabled:opacity-50" @click="load">Muat Ulang PO</button></div>
    <label class="block text-sm font-medium">Cari PO, Pesanan, Proyek atau Supplier<input v-model="filter" type="search" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white p-2.5" /></label>
    <p v-if="error && !receiptOpen && !followupOpen" role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ error }}</p>
    <p v-if="loading" role="status" class="p-5 text-sm text-slate-600">Memuat purchase order…</p>
    <p v-else-if="!visible.length" class="ui-empty-state rounded-xl border border-dashed bg-white p-8 text-center text-sm text-slate-600">Tidak ada purchase order yang sesuai.</p>
    <article v-for="purchase in visible" v-else :key="purchase.purchase_code" class="min-w-0 space-y-4 break-words rounded-xl border border-slate-200 bg-white p-5">
      <div class="flex flex-wrap justify-between gap-3"><div><h3 class="font-semibold text-slate-900">{{ purchase.purchase_code }}</h3><p class="mt-1 text-sm text-slate-600">{{ purchase.default_supplier }} · {{ purchase.project_name }}</p><p class="mt-1 text-xs text-slate-500">Pesanan {{ purchase.order_number || '—' }} · Pengajuan {{ purchase.warehouse_request_code || '—' }}</p></div><div><span class="inline-block rounded-lg bg-slate-100 px-3 py-1 text-xs font-semibold">{{ statusLabel(purchase.status) }}</span><p class="mt-2 text-sm font-semibold">{{ money(purchase.total_price) }}</p></div></div>
      <p v-if="purchase.delivery_info?.tracking_number" class="text-sm text-slate-700">Pengiriman: {{ purchase.delivery_info.courier }} · Resi {{ purchase.delivery_info.tracking_number }} · {{ purchase.delivery_info.shipping_status || purchase.supplier_status }}</p>
      <div class="overflow-x-auto"><table class="w-full text-left text-sm"><caption class="sr-only">Ringkasan penerimaan {{ purchase.purchase_code }}</caption><thead><tr class="border-b text-slate-600"><th class="p-2">Barang / Satuan</th><th class="p-2">Dipesan</th><th class="p-2">Diterima</th><th class="p-2">Ditolak (Riwayat)</th><th class="p-2">Sisa Belum Diterima</th></tr></thead><tbody><tr v-for="line in purchase.receipt_summary || []" :key="line.line_index" class="border-b"><td class="p-2">{{ line.name }}<span class="block text-xs text-slate-500">{{ line.sku }} · {{ line.unit }}</span></td><td class="p-2">{{ line.expected_qty }}</td><td class="p-2">{{ line.accepted_qty }}</td><td class="p-2">{{ line.rejected_qty }}</td><td class="p-2 font-semibold">{{ line.remaining_qty }}</td></tr></tbody></table></div>
      <p v-if="needsFollowup(purchase)" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Ada ketidaksesuaian barang. Finance harus mencatat tindak lanjut supplier sebelum penerimaan berikutnya.</p>
      <div class="flex flex-wrap gap-2"><button v-if="mode === 'warehouse' && purchase.status !== 'DITERIMA_GUDANG' && (purchase.receipt_summary || []).some(line => Number(line.remaining_qty) > 0)" type="button" :disabled="busy || needsFollowup(purchase)" class="rounded-lg bg-sky-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" @click="openReceipt(purchase)">Catat Penerimaan {{ purchase.purchase_code }}</button><button v-if="mode === 'finance' && needsFollowup(purchase)" type="button" :disabled="busy" class="rounded-lg bg-sky-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" @click="openFollowup(purchase)">Tindak Lanjut {{ purchase.purchase_code }}</button></div>
      <div v-if="purchase.followup_reference" class="rounded-lg bg-sky-50 p-3 text-sm text-sky-900"><p class="font-semibold">Tindak Lanjut: {{ purchase.followup_reference }}</p><p class="mt-1">{{ purchase.followup_notes }}</p></div>
      <details v-if="purchase.receipts?.length" class="rounded-lg border p-3 text-sm"><summary class="cursor-pointer font-semibold">Riwayat Penerimaan ({{ purchase.receipts.length }})</summary><div v-for="receipt in purchase.receipts" :key="receipt.id || receipt.idempotency_key" class="mt-3 border-t pt-3"><p class="font-medium">{{ receipt.received_date }} · {{ receipt.notes || 'Tanpa catatan tambahan' }}</p><ul class="mt-2 space-y-1"><li v-for="(line, index) in receipt.lines" :key="index">{{ line.sku || `Baris ${Number(line.line_index) + 1}` }}: diterima {{ line.accepted_qty }}, ditolak {{ line.rejected_qty }}<span v-if="line.reason"> — {{ line.reason }}</span></li></ul></div></details>
    </article>
    <Modal v-model="receiptOpen" :title="`Catat Penerimaan ${selected?.purchase_code || ''}`" subtitle="Isi jumlah fisik per barang. Barang ditolak tidak masuk stok." size="xl">
      <form class="space-y-4" @submit.prevent="receive">
        <p v-if="error" role="alert" class="rounded-lg bg-red-50 p-3 text-sm text-red-800">{{ error }}</p>
        <p v-if="retryPayload" role="status" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Percobaan sebelumnya belum terkonfirmasi. Kirim ulang data yang sama agar stok tidak tercatat ganda.</p>
        <fieldset :disabled="busy || !!retryPayload" class="min-w-0 space-y-4"><label class="block text-sm font-medium">Tanggal Penerimaan<input v-model="date" type="date" :max="localDate()" required class="mt-1 block rounded-lg border p-2.5" /></label><div v-for="line in receiptLines" :key="line.line_index" class="space-y-3 rounded-lg border p-4"><p class="text-sm font-semibold">{{ line.name }} · {{ line.sku }} · Sisa {{ line.remaining_qty }} {{ line.unit }}</p><div class="grid gap-3 sm:grid-cols-2"><label class="block text-sm">Jumlah Diterima<input v-model.number="line.accepted_qty" :aria-label="`Diterima ${line.sku}`" type="number" min="0" :max="line.remaining_qty" step="1" required class="mt-1 block w-full rounded-lg border p-2.5" /></label><label class="block text-sm">Jumlah Ditolak<input v-model.number="line.rejected_qty" :aria-label="`Ditolak ${line.sku}`" type="number" min="0" :max="Math.max(0, line.remaining_qty - Number(line.accepted_qty))" step="1" required class="mt-1 block w-full rounded-lg border p-2.5" /></label></div><label v-if="Number(line.rejected_qty) > 0" class="block text-sm">Alasan Penolakan<input v-model="line.reason" :aria-label="`Alasan penolakan ${line.sku}`" required maxlength="2000" class="mt-1 block w-full rounded-lg border p-2.5" /></label></div><label class="block text-sm">Catatan Penerimaan<textarea v-model="notes" maxlength="2000" rows="2" class="mt-1 block w-full rounded-lg border p-2.5"></textarea></label></fieldset>
        <button type="submit" :disabled="busy || !receiptLines.length" class="rounded-lg bg-sky-700 px-4 py-2 font-semibold text-white disabled:opacity-50">{{ busy ? 'Menyimpan…' : 'Simpan Penerimaan' }}</button>
      </form>
    </Modal>
    <Modal v-model="followupOpen" :title="`Tindak Lanjut ${selected?.purchase_code || ''}`" subtitle="Catat komunikasi atau pemesanan pengganti dengan supplier pada PO asal.">
      <form class="space-y-4" @submit.prevent="followup"><p v-if="error" role="alert" class="rounded-lg bg-red-50 p-3 text-sm text-red-800">{{ error }}</p><label class="block text-sm font-medium">Referensi Tindak Lanjut<input v-model="reference" required maxlength="255" :disabled="busy" class="mt-1 block w-full rounded-lg border p-2.5" /></label><label class="block text-sm font-medium">Catatan Tindak Lanjut<textarea v-model="followupNotes" required maxlength="2000" rows="4" :disabled="busy" class="mt-1 block w-full rounded-lg border p-2.5"></textarea></label><button type="submit" :disabled="busy" class="rounded-lg bg-sky-700 px-4 py-2 font-semibold text-white disabled:opacity-50">{{ busy ? 'Menyimpan…' : 'Simpan Tindak Lanjut' }}</button></form>
    </Modal>
  </section>
</template>
