<script setup>
import { computed, ref, watch } from 'vue';
import { Calculator, ClipboardList, List, Plus, Save, Search, Trash2, X } from 'lucide-vue-next';
import Modal from './Modal.vue';
import './procurement-form.css';

const props = defineProps({ orders: { type: Array, default: () => [] }, suppliers: { type: Array, default: () => [] }, busy: Boolean, error: { type: String, default: '' }, actor: { type: String, default: '' } });
const emit = defineEmits(['submit', 'cancel', 'clear-error']);
const orderNumber = ref(''), purpose = ref(''), notes = ref(''), lines = ref([]), supplierId = ref('');
const date = ref(new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' })), invoice = ref(''), discount = ref(0), payment = ref('Tunai');
const pickerOpen = ref(false), pickerSearch = ref(''), replaceSku = ref(''), supplierOpen = ref(false), supplierSearch = ref('');
const localError = ref(''), sourceSnapshot = ref('');
const selectedOrder = computed(() => props.orders.find(order => order.orderNumber === orderNumber.value));
const selectedSupplier = computed(() => props.suppliers.find(record => Number(record.id) === Number(supplierId.value) && record.is_active !== false));
const filteredSuppliers = computed(() => props.suppliers.filter(record => record.is_active !== false && `${record.code} ${record.name} ${record.address || ''}`.toLocaleLowerCase('id-ID').includes(supplierSearch.value.trim().toLocaleLowerCase('id-ID'))));
const needsFor = order => (order?.stockReadiness?.items || []).filter(item => item.catalogFound !== false && Number(item.unrequestedShortage || 0) > 0).map(item => ({ sku: item.sku, name: item.name || item.sku, unit: item.unit || '', max: Number(item.unrequestedShortage), limit: Math.min(1000000, Number(item.unrequestedShortage)) }));
const eligibleOrders = computed(() => props.orders.filter(order => order.status === 'Draft' && order.serverManaged !== false && needsFor(order).length));
const orderOptions = computed(() => selectedOrder.value && !eligibleOrders.value.some(order => order.orderNumber === orderNumber.value) ? [selectedOrder.value, ...eligibleOrders.value] : eligibleOrders.value);
const currentNeeds = computed(() => new Map(needsFor(selectedOrder.value).map(item => [item.sku, item])));
const signature = order => JSON.stringify({ status: order?.status, project: order?.projectName, items: needsFor(order) });
const sourceChanged = computed(() => Boolean(orderNumber.value) && signature(selectedOrder.value) !== sourceSnapshot.value);
const availableForPicker = computed(() => {
  if (selectedOrder.value?.status !== 'Draft') return [];
  const query = pickerSearch.value.trim().toLocaleLowerCase('id-ID');
  return [...currentNeeds.value.values()].filter(item => !lines.value.some(line => line.sku === item.sku && line.sku !== replaceSku.value) && (!query || `${item.sku} ${item.name} ${item.unit}`.toLocaleLowerCase('id-ID').includes(query)));
});
const missingCount = computed(() => [...currentNeeds.value.keys()].filter(sku => !lines.value.some(line => line.sku === sku)).length);
const cents = value => Math.round(Number(value) * 100);
const validAmount = (value, max = 1000000000) => value !== '' && value !== null && Number.isFinite(Number(value)) && Number(value) >= 0 && Number(value) <= max && Math.abs(Number(value) * 100 - cents(value)) < 0.0001;
const validQuantity = value => value !== '' && value !== null && Number.isInteger(Number(value)) && Number(value) > 0 && Number(value) <= 1000000;
const validLine = line => validQuantity(line.qty) && validAmount(line.est_price) && validAmount(line.est_discount) && cents(line.est_discount) <= cents(line.est_price);
const lineCents = line => validLine(line) ? Number(line.qty) * (cents(line.est_price) - cents(line.est_discount)) : 0;
const subtotalCents = computed(() => lines.value.reduce((total, line) => total + lineCents(line), 0));
const totalCents = computed(() => subtotalCents.value - cents(discount.value));
const estimateReady = computed(() => lines.value.every(validLine) && validAmount(discount.value, 1000000000000) && Number.isSafeInteger(totalCents.value) && subtotalCents.value <= 100000000000000 && totalCents.value >= 0);
const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value / 100);
const currentLine = line => currentNeeds.value.get(line.sku);
const makeLine = item => ({ ...item, qty: item.limit, est_price: 0, est_discount: 0, discount_percent: 0 });
function lineIssue(line) {
  const current = currentLine(line);
  if (!current) return 'Barang ini tidak lagi memiliki kebutuhan yang dapat diajukan.';
  if (current.unit !== line.unit) return 'Satuan barang telah berubah. Sesuaikan kebutuhan terbaru.';
  if (Number(line.qty) > current.max) return `Jumlah melebihi sisa terbaru (${current.max} ${current.unit}).`;
  return '';
}
watch(orderNumber, () => { lines.value = needsFor(selectedOrder.value).map(makeLine); sourceSnapshot.value = signature(selectedOrder.value); localError.value = ''; pickerSearch.value = ''; emit('clear-error'); });
function openItems(sku = '') { if (props.busy) return; replaceSku.value = sku; pickerSearch.value = ''; pickerOpen.value = true; }
function addItem(item) {
  const current = currentNeeds.value.get(item.sku);
  if (props.busy || !current || selectedOrder.value?.status !== 'Draft' || lines.value.some(line => line.sku === item.sku && line.sku !== replaceSku.value)) return;
  const index = lines.value.findIndex(line => line.sku === replaceSku.value);
  if (index >= 0) { if (replaceSku.value !== current.sku) lines.value.splice(index, 1, makeLine(current)); }
  else lines.value.push(makeLine(current));
  pickerOpen.value = false; pickerSearch.value = ''; localError.value = '';
}
function removeItem(sku) { if (!props.busy) lines.value = lines.value.filter(line => line.sku !== sku); }
function openSuppliers() { if (!props.busy) { supplierSearch.value = ''; supplierOpen.value = true; } }
function chooseSupplier(record) { if (!props.busy) { supplierId.value = record.id; supplierOpen.value = false; } }
function syncPercent(line) { line.discount_percent = cents(line.est_price) > 0 ? Number((cents(line.est_discount) * 100 / cents(line.est_price)).toFixed(4)) : 0; }
function syncDiscount(line) { if (Number.isFinite(Number(line.discount_percent))) line.est_discount = Math.round(cents(line.est_price) * Number(line.discount_percent) / 100) / 100; }
function reconcile() {
  if (props.busy) return;
  lines.value = lines.value.flatMap(line => { const current = currentNeeds.value.get(line.sku); return current ? [{ ...line, ...current, qty: Math.min(current.limit, validQuantity(line.qty) ? Number(line.qty) : current.limit) }] : []; });
  sourceSnapshot.value = signature(selectedOrder.value); localError.value = ''; emit('clear-error');
}
function submit() {
  if (props.busy) return;
  localError.value = '';
  if (!selectedOrder.value || selectedOrder.value.status !== 'Draft') localError.value = 'Pilih pesanan Draft yang masih dapat diproses.';
  else if (!selectedSupplier.value) localError.value = 'Pilih supplier aktif yang terdaftar.';
  else if (!purpose.value.trim()) localError.value = 'Isi keperluan pengadaan terlebih dahulu.';
  else if (!lines.value.length) localError.value = 'Tambahkan minimal satu barang dari kebutuhan pesanan.';
  else if (lines.value.some(line => lineIssue(line))) localError.value = 'Kebutuhan pesanan telah berubah. Periksa baris barang dan sesuaikan kebutuhan terbaru.';
  else if (lines.value.some(line => !validLine(line) || Number(line.discount_percent) < 0 || Number(line.discount_percent) > 100)) localError.value = 'Periksa jumlah, harga dan diskon per satuan. Diskon tidak boleh melebihi harga barang.';
  else if (!estimateReady.value) localError.value = 'Periksa diskon transaksi dan total. Diskon tidak boleh melebihi jumlah harga; total maksimal Rp1.000.000.000.000.';
  if (localError.value) return;
  emit('submit', { order_number: orderNumber.value, purpose: purpose.value.trim(), notes: notes.value.trim(),
    procurement_draft: { transaction_date: date.value, invoice_number: invoice.value.trim(), supplier_id: selectedSupplier.value.id, payment_method: payment.value, discount: cents(discount.value) / 100 },
    items: lines.value.map(line => ({ sku: line.sku, qty: Number(line.qty), est_price: cents(line.est_price) / 100, est_discount: cents(line.est_discount) / 100 })) });
}
</script>

<template>
  <form aria-label="Form Pengajuan Kebutuhan" class="procurement-form" @submit.prevent="submit">
    <p v-if="localError || error" role="alert" class="mb-3 rounded border border-red-200 bg-red-50 p-3 text-xs text-red-800">{{ localError || error }}</p>
    <section aria-labelledby="mr-header-title" class="procurement-section">
      <h3 id="mr-header-title" class="procurement-heading"><ClipboardList aria-hidden="true" />Header Pembelian</h3>
      <div class="grid min-w-0 gap-3 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_1.5fr_1.5fr]">
        <div class="min-w-0"><label for="mr-purchase-code">Kode Pembelian</label><input id="mr-purchase-code" readonly placeholder="Otomatis setelah disimpan" title="Kode rencana pembelian dibuat server saat pengajuan disimpan. PO diterbitkan oleh Finance." /></div>
        <div class="min-w-0"><label for="mr-purchase-date">Tanggal <span class="text-red-500">*</span></label><input id="mr-purchase-date" v-model="date" type="date" required :disabled="busy" /></div>
        <div class="min-w-0"><label for="mr-invoice">No Faktur</label><input id="mr-invoice" v-model="invoice" maxlength="100" :disabled="busy" autocomplete="off" /></div>
        <div class="min-w-0"><label for="mr-source-order">Project <span class="text-red-500">*</span></label><select id="mr-source-order" v-model="orderNumber" aria-label="Pesanan Sumber" required :disabled="busy"><option value="">Pilih Project / Pesanan</option><option v-for="order in orderOptions" :key="order.orderNumber" :value="order.orderNumber">{{ order.projectName }} — {{ order.orderNumber }}{{ order.status !== 'Draft' ? ` (${order.status})` : '' }}</option></select></div>
        <div class="min-w-0"><label for="mr-default-supplier">Supplier Default <span class="text-red-500">*</span></label><div class="procurement-control"><select id="mr-default-supplier" v-model="supplierId" required :disabled="busy"><option value="">Pilih supplier</option><option v-for="record in suppliers.filter(item => item.is_active !== false)" :key="record.id" :value="record.id">{{ record.name }}</option></select><button type="button" aria-label="Cari Supplier Default" class="procurement-icon-button" :disabled="busy" @click="openSuppliers"><Search /></button><button type="button" aria-label="Kosongkan Supplier Default" class="procurement-icon-button !text-red-500" :disabled="busy || !supplierId" @click="supplierId = ''"><X /></button></div></div>
      </div>
      <p class="mt-2 text-[10px] text-slate-500">Supplier default berlaku untuk seluruh barang. Pengaju: {{ actor || 'Pengguna aktif' }}. Kode dibuat otomatis saat disimpan.</p>
      <p v-if="!eligibleOrders.length && !orderNumber" class="mt-2 text-xs text-amber-800">Belum ada pesanan Draft dengan kekurangan stok yang belum diajukan.</p>
    </section>
    <section aria-labelledby="mr-items-title" class="procurement-section">
      <div class="procurement-heading justify-between"><h3 id="mr-items-title" class="flex items-center gap-1"><List aria-hidden="true" />Detail Pembelian</h3><button type="button" :disabled="busy || !missingCount || selectedOrder?.status !== 'Draft'" class="inline-flex items-center gap-1 text-[11px] font-semibold text-alpharo-600 disabled:opacity-40" @click="openItems()"><Plus class="h-3.5 w-3.5" />Tambah Barang</button></div>
      <div v-if="sourceChanged" role="status" class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded border border-amber-200 bg-amber-50 p-2 text-xs text-amber-900"><p>Kebutuhan pesanan diperbarui. Isian tetap tersimpan; periksa jumlah dan satuan terbaru.</p><button type="button" :disabled="busy || selectedOrder?.status !== 'Draft'" class="rounded border border-amber-300 bg-white px-2 py-1 font-semibold" @click="reconcile">Sesuaikan Kebutuhan Terbaru</button></div>
      <table class="procurement-table"><caption class="sr-only">Detail barang pengajuan kebutuhan</caption><colgroup><col style="width:24%" /><col style="width:21%" /><col style="width:8%" /><col style="width:11%" /><col style="width:8%" /><col style="width:11%" /><col style="width:13%" /><col style="width:4%" /></colgroup><thead><tr><th>Barang</th><th>Supplier <span class="text-red-500">*</span></th><th>Jumlah</th><th>Harga Beli</th><th>% Diskon</th><th>Diskon/Satuan</th><th class="text-right">Subtotal</th><th><span class="sr-only">Hapus</span></th></tr></thead><tbody>
        <tr v-for="line in lines" :key="line.sku">
          <td data-label="Barang" data-wide><div class="procurement-control"><input :value="currentLine(line)?.name || line.name" :aria-label="`Barang ${line.sku}`" readonly :title="`${line.sku} — ${line.name}`" /><button type="button" :aria-label="`Pilih Barang ${line.sku}`" class="procurement-icon-button" :disabled="busy" @click="openItems(line.sku)"><Search /></button></div><p class="mt-1 break-all text-[10px] text-slate-500">{{ line.sku }} · {{ line.unit }} · Sisa {{ currentLine(line)?.max || 0 }}</p><p v-if="lineIssue(line)" class="mt-1 text-[10px] text-amber-800">{{ lineIssue(line) }}</p></td>
          <td data-label="Supplier" data-wide><div class="procurement-control"><input :value="selectedSupplier?.name || ''" :aria-label="`Supplier ${line.sku}`" readonly placeholder="Pilih supplier" /><button type="button" :aria-label="`Pilih Supplier ${line.sku}`" class="procurement-icon-button" :disabled="busy" @click="openSuppliers"><Search /></button></div></td>
          <td data-label="Jumlah"><input v-model.number="line.qty" :aria-label="`Jumlah Pengadaan ${line.sku}`" type="number" min="1" :max="currentLine(line)?.limit || 1" step="1" required :disabled="busy" /></td>
          <td data-label="Harga Beli"><input v-model.number="line.est_price" :aria-label="`Estimasi Harga ${line.sku}`" type="number" min="0" max="1000000000" step="0.01" required :disabled="busy" @input="syncPercent(line)" /></td>
          <td data-label="% Diskon"><input v-model.number="line.discount_percent" :aria-label="`Persen Diskon ${line.sku}`" type="number" min="0" max="100" step="any" :disabled="busy" @input="syncDiscount(line)" /></td>
          <td data-label="Diskon/Satuan"><input v-model.number="line.est_discount" :aria-label="`Diskon Satuan ${line.sku}`" type="number" min="0" :max="line.est_price" step="0.01" required :disabled="busy" @input="syncPercent(line)" /></td>
          <td data-label="Subtotal"><output class="block min-h-[30px] break-words rounded border border-slate-200 bg-slate-200/60 px-2 py-1.5 text-right">{{ validLine(line) ? money(lineCents(line)) : 'Periksa nilai' }}</output></td>
          <td class="!px-1"><button type="button" :aria-label="`Hapus Barang ${line.sku}`" :disabled="busy" class="inline-flex h-[30px] w-full items-center justify-center rounded bg-rose-500 text-white hover:bg-rose-600 disabled:opacity-40" @click="removeItem(line.sku)"><Trash2 class="h-3.5 w-3.5" /></button></td>
        </tr>
        <tr v-if="!lines.length"><td colspan="8" data-wide class="!py-5 text-center text-slate-500">{{ !orderNumber ? 'Pilih Project untuk menampilkan barang yang dibutuhkan.' : !currentNeeds.size ? 'Kebutuhan sudah terpenuhi atau sudah diajukan. Pilih pesanan lain.' : 'Klik Tambah Barang untuk memilih kebutuhan pesanan.' }}</td></tr>
      </tbody></table>
    </section>
    <section aria-labelledby="mr-summary-title" class="procurement-section">
      <h3 id="mr-summary-title" class="procurement-heading"><Calculator aria-hidden="true" />Total dan Pembayaran</h3>
      <div class="grid min-w-0 gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(300px,380px)]">
        <div class="min-w-0 space-y-2"><div><label for="mr-purpose">Keperluan Pengadaan <span class="text-red-500">*</span></label><input id="mr-purpose" v-model="purpose" required maxlength="500" :disabled="busy" placeholder="Keperluan barang untuk Project ini" /></div><div><label for="mr-notes">Catatan Pengajuan (Opsional)</label><textarea id="mr-notes" v-model="notes" maxlength="5000" rows="2" :disabled="busy" placeholder="Keterangan tambahan pengajuan"></textarea></div><p class="text-[10px] text-slate-500">Nilai pengajuan menjadi acuan Finance saat menerbitkan PO. Pengajuan tidak menambah stok atau mencatat pelunasan.</p></div>
        <dl class="procurement-summary"><div><dt>Jumlah Harga</dt><dd><output>{{ money(subtotalCents) }}</output></dd></div><div><dt><label for="mr-transaction-discount" class="!mb-0">Discount</label></dt><dd><input id="mr-transaction-discount" v-model.number="discount" aria-label="Diskon Transaksi Pengajuan (Rp)" type="number" min="0" :max="subtotalCents / 100" step="0.01" required :disabled="busy" /></dd></div><div class="font-bold"><dt class="!text-slate-800">Total Harga<span class="sr-only"> / Total Estimasi Pengadaan</span></dt><dd><output data-testid="mr-total-estimate" class="font-bold">{{ estimateReady ? money(totalCents) : 'Periksa isian barang' }}</output></dd></div><div><dt><label for="mr-payment" class="!mb-0">Jenis Pembayaran</label></dt><dd><select id="mr-payment" v-model="payment" :disabled="busy"><option>Tunai</option><option>Transfer</option><option>Termin</option></select></dd></div></dl>
      </div>
    </section>
    <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 pt-3"><button type="button" :disabled="busy" class="rounded border border-slate-400 bg-white px-3 py-2 text-xs text-slate-600" @click="emit('cancel')">Batal</button><button type="submit" :disabled="busy || !lines.length || selectedOrder?.status !== 'Draft'" class="inline-flex items-center gap-1 rounded bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"><Save class="h-3.5 w-3.5" />{{ busy ? 'Menyimpan...' : 'Simpan Pengajuan' }}</button></div>
  </form>
  <Modal v-model="pickerOpen" title="Pilih Barang Kebutuhan" subtitle="Barang mengikuti kekurangan stok Project / pesanan yang dipilih." size="lg"><div class="space-y-3"><label class="block text-xs font-medium">Cari Barang Kebutuhan<input v-model="pickerSearch" type="search" class="mt-1 w-full rounded border p-2 text-base" placeholder="Kode atau nama barang" /></label><button v-for="item in availableForPicker" :key="item.sku" type="button" :aria-label="`Tambahkan ${item.sku}`" class="flex w-full items-center justify-between gap-3 rounded border border-slate-200 p-3 text-left text-xs hover:bg-alpharo-50" @click="addItem(item)"><span class="min-w-0 break-words"><strong>{{ item.name }}</strong><span class="mt-1 block break-all text-slate-500">{{ item.sku }} · {{ item.unit }} · Sisa {{ item.max }}</span></span><Plus class="h-4 w-4 shrink-0 text-alpharo-600" /></button><p v-if="!availableForPicker.length" class="p-4 text-center text-xs text-slate-500">Tidak ada kebutuhan barang yang sesuai atau seluruh barang sudah dipilih.</p></div></Modal>
  <Modal v-model="supplierOpen" title="Pilih Supplier" subtitle="Pilihan berlaku untuk seluruh barang dalam pengajuan." size="lg"><div class="space-y-3"><label class="block text-xs font-medium">Cari Supplier<input v-model="supplierSearch" type="search" class="mt-1 w-full rounded border p-2 text-base" placeholder="Kode, nama atau alamat supplier" /></label><button v-for="record in filteredSuppliers" :key="record.id" type="button" :aria-label="`Gunakan Supplier ${record.code}`" class="block w-full rounded border border-slate-200 p-3 text-left text-xs hover:bg-alpharo-50" @click="chooseSupplier(record)"><strong>{{ record.name }}</strong><span class="mt-1 block text-slate-500">{{ record.code }} · {{ record.address || 'Alamat belum diisi' }}</span></button><p v-if="!filteredSuppliers.length" class="p-4 text-center text-xs text-slate-500">Tidak ada supplier aktif yang sesuai.</p></div></Modal>
</template>
