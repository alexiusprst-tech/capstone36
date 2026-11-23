<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import { useRoute, useRouter } from '../../navigation';
import { purchaseWorkflow } from '../../services/purchaseWorkflow';
import api from '../../services/api';
import { orderError } from '../../services/orderWorkflow';
import { useToast } from '../../services/toast';
const route = useRoute(), router = useRouter(), toast = useToast();
const requests = ref([]), suppliers = ref([]), selectedCode = ref(''), supplierId = ref(''), lines = ref([]);
const loading = ref(true), busy = ref(false), error = ref(''), discount = ref(0), payment = ref('Transfer'), notes = ref('');
const date = ref(new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' })), invoice = ref('');
const pending = computed(() => requests.value.filter(record => record.status === 'MENUNGGU_FINANCE' && record.sales_order_id && !record.requirements_changed));
const selected = computed(() => pending.value.find(record => record.request_code === selectedCode.value));
const supplier = computed(() => suppliers.value.find(record => record.id === Number(supplierId.value)));
const cents = value => Math.round(Number(value || 0) * 100);
const lineCents = line => Number(line.qty) * Math.max(0, cents(line.price) - cents(line.discount));
const subtotal = computed(() => lines.value.reduce((total, line) => total + lineCents(line), 0));
const total = computed(() => subtotal.value - cents(discount.value));
const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(value / 100);
watch(selectedCode, () => {
  const record = selected.value, draft = record?.procurement_draft;
  lines.value = (record?.items || []).map(line => ({ ...line, price: Number(line.est_price || 0), discount: Number(line.est_discount || 0) }));
  supplierId.value = draft?.supplier_id || '';
  date.value = draft?.transaction_date || new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' });
  invoice.value = draft?.invoice_number || ''; discount.value = Number(draft?.discount || 0);
  payment.value = draft?.payment_method || 'Transfer'; notes.value = record?.notes || '';
});
async function load() {
  loading.value = true; error.value = '';
  try {
    const [records, master] = await Promise.all([purchaseWorkflow.requests(), api.get('/suppliers?per_page=-1')]);
    requests.value = records; suppliers.value = (master.data.data || []).filter(record => record.is_active !== false);
    const requested = route.query.requestCode || route.query.request_id;
    selectedCode.value = records.find(record => record.request_code === requested || String(record.id) === String(requested))?.request_code || '';
  } catch (cause) { error.value = orderError(cause); } finally { loading.value = false; }
}
async function save() {
  if (busy.value) return;
  if (!selected.value || !supplier.value || !lines.value.length || total.value < 0 || lines.value.some(line => !Number.isFinite(Number(line.price)) || Number(line.price) < 0 || Number(line.discount) < 0 || cents(line.discount) > cents(line.price))) { error.value = 'Pilih pengajuan dan supplier, lalu periksa harga serta diskon.'; return; }
  busy.value = true; error.value = '';
  try {
    await purchaseWorkflow.create({ purchase_code: selected.value.procurement_draft?.purchase_code, transaction_date: date.value, invoice_number: invoice.value, warehouse_request_id: selected.value.id,
      warehouse_request_code: selected.value.request_code, supplier_id: supplier.value.id, default_supplier: supplier.value.name,
      payment_method: payment.value, discount: Number(discount.value), notes: notes.value,
      items: lines.value.map(line => ({ sku: line.sku, barang: line.name, unit: line.unit, qty: Number(line.qty), supplier: supplier.value.name,
        harga_beli: Number(line.price), diskon_nominal: Number(line.discount) })) });
    toast.success('PO tersimpan. Stok bertambah setelah pemeriksaan penerimaan oleh Gudang.'); router.push('/finance/purchases');
  } catch (cause) { error.value = orderError(cause); } finally { busy.value = false; }
}
onMounted(load);
</script>
<template>
  <AppLayout><template #header><div class="flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-semibold text-slate-900">Tambah Transaksi Pembelian</h1><p class="mt-1 text-sm text-slate-600">Terbitkan satu PO untuk seluruh barang dalam satu pengajuan Gudang.</p></div><button type="button" class="rounded-lg border bg-white px-4 py-2 text-sm" @click="router.push('/finance/purchases')">Kembali</button></div></template>
  <div class="space-y-5"><p v-if="error" role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-800">{{ error }}</p><p v-if="loading" role="status">Memuat pengajuan dan supplier…</p><p v-else-if="!pending.length" class="ui-empty-state rounded-xl border bg-white p-6 text-sm text-slate-600">Belum ada pengajuan Gudang yang dapat diproses. Pengajuan harus terhubung ke pesanan dan sesuai kebutuhan terbaru.</p>
  <form v-else class="min-w-0 space-y-5 rounded-xl border bg-white p-5" @submit.prevent="save"><fieldset :disabled="busy" class="min-w-0 space-y-5">
    <label class="block text-sm font-medium">Kode Pembelian<input :value="selected?.procurement_draft?.purchase_code || ''" readonly placeholder="Otomatis saat disimpan" class="mt-1 block w-full rounded-lg border bg-slate-100 p-2.5" /></label>
    <label class="block text-sm font-medium">Pengajuan Gudang<select v-model="selectedCode" required class="mt-1 block w-full rounded-lg border p-2.5"><option value="">Pilih pengajuan</option><option v-for="record in pending" :key="record.id" :value="record.request_code">{{ record.request_code }} · {{ record.order_number }} · {{ record.project_name }}</option></select></label>
    <p v-if="selected" class="text-sm text-slate-600">{{ selected.purpose }} · {{ selected.requested_by }} · {{ selected.request_date }}</p>
    <label class="block text-sm font-medium">Supplier<select v-model="supplierId" required class="mt-1 block w-full rounded-lg border p-2.5"><option value="">Pilih supplier terdaftar</option><option v-for="record in suppliers" :key="record.id" :value="record.id">{{ record.code }} · {{ record.name }}</option></select></label><p v-if="!suppliers.length" class="text-sm text-slate-600">Tambahkan supplier di Data Supplier terlebih dahulu.</p>
    <div class="grid gap-4 sm:grid-cols-2"><label class="block text-sm font-medium">Tanggal Transaksi<input v-model="date" type="date" required class="mt-1 block w-full rounded-lg border p-2.5" /></label><label class="block text-sm font-medium">Nomor Faktur Supplier (Opsional)<input v-model="invoice" maxlength="100" class="mt-1 block w-full rounded-lg border p-2.5" /></label></div>
    <div v-if="lines.length" class="overflow-x-auto"><table class="w-full text-left text-sm"><caption class="mb-3 text-left text-sm text-slate-600">SKU, jumlah, dan satuan mengikuti pengajuan. Diskon barang berlaku per unit; diskon transaksi dikurangi sekali dari subtotal.</caption><thead><tr class="border-b"><th class="p-2">Barang</th><th class="p-2">Jumlah</th><th class="p-2">Harga Beli / Unit (Rp)</th><th class="p-2">Diskon / Unit (Rp)</th><th class="p-2">Subtotal</th></tr></thead><tbody><tr v-for="line in lines" :key="line.sku" class="border-b"><td class="p-2">{{ line.name }}<span class="block text-xs text-slate-500">{{ line.sku }} · {{ line.unit }}</span></td><td class="p-2">{{ line.qty }}</td><td class="p-2"><input v-model.number="line.price" :aria-label="`Harga Beli ${line.sku}`" type="number" min="0" max="1000000000" step="0.01" required class="w-36 rounded-lg border p-2" /></td><td class="p-2"><input v-model.number="line.discount" :aria-label="`Diskon ${line.sku}`" type="number" min="0" :max="line.price" step="0.01" required class="w-36 rounded-lg border p-2" /></td><td class="p-2 whitespace-nowrap">{{ money(lineCents(line)) }}</td></tr></tbody></table></div>
    <div class="grid gap-4 sm:grid-cols-2"><label class="block text-sm font-medium">Diskon Transaksi (Rp)<input v-model.number="discount" type="number" min="0" :max="subtotal / 100" step="0.01" required class="mt-1 block w-full rounded-lg border p-2.5" /></label><label class="block text-sm font-medium">Metode Pembayaran Supplier<select v-model="payment" class="mt-1 block w-full rounded-lg border p-2.5"><option>Transfer</option><option>Tunai</option><option>Termin</option></select></label></div>
    <label class="block text-sm font-medium">Catatan<textarea v-model="notes" maxlength="5000" rows="2" class="mt-1 block w-full rounded-lg border p-2.5"></textarea></label>
    <dl class="space-y-2 rounded-lg bg-slate-50 p-4 text-sm"><div class="flex justify-between gap-3"><dt>Subtotal Barang</dt><dd>{{ money(subtotal) }}</dd></div><div class="flex justify-between gap-3"><dt>Diskon Transaksi</dt><dd>{{ money(cents(discount)) }}</dd></div><div class="flex justify-between gap-3 font-semibold"><dt>Total Pembelian</dt><dd>{{ money(total) }}</dd></div></dl>
    <button type="submit" :disabled="!selected || !supplier || busy" class="rounded-lg border border-alpharo-600 bg-alpharo-600 hover:border-alpharo-700 hover:bg-alpharo-700 active:bg-alpharo-800 px-5 py-2.5 font-semibold text-white disabled:opacity-50">{{ busy ? 'Menyimpan…' : 'Simpan Pembelian' }}</button>
  </fieldset></form></div></AppLayout>
</template>
