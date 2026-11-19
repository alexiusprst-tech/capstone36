<script setup>
import { computed } from 'vue';
const props = defineProps({ timeline: { type: Array, default: () => [] } });
const labels = {
  clientId: 'ID Customer', clientName: 'Nama Customer', projectName: 'Nama Proyek', projectId: 'Kode Proyek',
  description: 'Deskripsi', notes: 'Catatan', date: 'Tanggal Pesanan', company: 'Perusahaan', marketingName: 'PIC Marketing',
  namaKegiatan: 'Nama Kegiatan', rincianKegiatan: 'Rincian Kegiatan', kodeRekening: 'Kode Rekening', nomorRekening: 'Nomor Rekening',
  bankPenerima: 'Bank Penerima', tahunAnggaran: 'Tahun Anggaran', sumberDana: 'Sumber Dana', kategori: 'Kategori',
  paguAnggaran: 'Pagu Anggaran', tglMulai: 'Tanggal Mulai', estimasiSelesai: 'Estimasi Selesai',
  penanggungJawabNama: 'Nama Penanggung Jawab', penanggungJawabJabatan: 'Jabatan Penanggung Jawab',
  penanggungJawabNip: 'NIP Penanggung Jawab', kotaTandaTangan: 'Kota Tanda Tangan',
  customerProfile: 'Profil Customer', docNumbers: 'Nomor Dokumen', name: 'Nama', code: 'Kode', address: 'Alamat',
  contactPerson: 'PIC', phone: 'Telepon', email: 'Email', qty: 'Jumlah', unit: 'Satuan', price: 'Harga Satuan', total: 'Total Barang',
  specs: 'Spesifikasi', sku: 'SKU', subtotal: 'Subtotal', tax: 'PPN', grandTotal: 'Total Pesanan',
};
const moneyFields = new Set(['price', 'total', 'subtotal', 'tax', 'grandTotal', 'paguAnggaran']);
const label = key => labels[key] || key.replace(/([a-z])([A-Z])/g, '$1 $2').replaceAll('_', ' ');
function flatten(value, path = [], result = new Map()) {
  for (const [key, entry] of Object.entries(value || {})) {
    const next = [...path, key];
    if (entry && typeof entry === 'object' && !Array.isArray(entry)) flatten(entry, next, result);
    else result.set(next.join('.'), { path: next, value: Array.isArray(entry) ? entry.join(', ') : entry });
  }
  return result;
}
function valueText(value, key) {
  if (value === null || value === undefined || value === '') return '—';
  if (moneyFields.has(key) && Number.isFinite(Number(value))) return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value));
  return String(value);
}
function detailChanges(before, after) {
  const old = flatten(before), next = flatten(after), changes = [];
  for (const key of new Set([...old.keys(), ...next.keys()])) {
    const previous = old.get(key), current = next.get(key);
    if (String(previous?.value ?? '') === String(current?.value ?? '')) continue;
    const path = current?.path || previous.path;
    changes.push({ field: path.map(label).join(' · '), before: valueText(previous?.value, path.at(-1)), after: valueText(current?.value, path.at(-1)) });
  }
  return changes;
}
function indexedItems(items) {
  const seen = new Map();
  return new Map((items || []).map((item, index) => {
    const sku = item.sku || item.name || `Baris ${index + 1}`, count = (seen.get(sku) || 0) + 1; seen.set(sku, count);
    return [`${sku}:${count}`, { ...item, caption: `${sku}${count > 1 ? ` (baris ${count})` : ''}` }];
  }));
}
function snapshotChanges(event) {
  const before = event.before || {}, after = event.after || {};
  const changes = detailChanges(before.details, after.details);
  const old = indexedItems(before.items), next = indexedItems(after.items);
  for (const key of new Set([...old.keys(), ...next.keys()])) {
    const previous = old.get(key), current = next.get(key);
    for (const field of ['sku', 'name', 'qty', 'unit', 'price', 'specs', 'total']) {
      if (String(previous?.[field] ?? '') === String(current?.[field] ?? '')) continue;
      changes.push({ field: `Barang ${current?.caption || previous.caption} · ${label(field)}`, before: valueText(previous?.[field], field), after: valueText(current?.[field], field) });
    }
  }
  for (const field of ['subtotal', 'tax', 'grandTotal']) {
    if (String(before[field] ?? '') !== String(after[field] ?? '')) changes.push({ field: label(field), before: valueText(before[field], field), after: valueText(after[field], field) });
  }
  return changes;
}
const events = computed(() => props.timeline.filter(event => event.before && event.after).map(event => ({ ...event, changes: snapshotChanges(event) })).reverse());
function timestamp(value) { const date = new Date(value); return Number.isNaN(date.valueOf()) ? value || 'Waktu tidak tersedia' : date.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }); }
</script>

<template>
  <section aria-label="Riwayat Perubahan Pesanan" class="space-y-3">
    <h4 class="font-semibold uppercase tracking-wider text-slate-600">Riwayat Perubahan Pesanan</h4>
    <p class="text-xs text-slate-500">Perubahan rincian dan nilai pesanan yang tersimpan di server, beserta pelaku dan waktunya.</p>
    <p v-if="!events.length" class="rounded-lg border border-dashed p-3 text-xs text-slate-500">Belum ada rekaman perubahan rincian pesanan.</p>
    <details v-for="event in events" :key="event.id || event.timestamp" class="min-w-0 rounded-lg border border-slate-200 bg-white">
      <summary class="cursor-pointer break-words p-3 text-xs"><span class="font-semibold text-slate-900">{{ event.stage }} · Versi {{ event.id }}</span><span class="mt-1 block text-slate-600">{{ event.user || 'Pelaku tidak tersedia' }}<span v-if="event.role"> ({{ event.role }})</span> · {{ timestamp(event.timestamp) }} · {{ event.changes.length }} perubahan</span></summary>
      <div class="overflow-x-auto border-t"><table v-if="event.changes.length" class="w-full min-w-[480px] table-fixed text-left text-xs"><caption class="sr-only">Nilai sebelum dan sesudah versi {{ event.id }}</caption><thead class="bg-slate-50 text-slate-600"><tr><th class="w-2/5 p-3">Bagian yang Berubah</th><th class="p-3">Sebelum</th><th class="p-3">Sesudah</th></tr></thead><tbody><tr v-for="(change, index) in event.changes" :key="index" class="border-t"><th class="break-words p-3 font-medium text-slate-700">{{ change.field }}</th><td class="break-words p-3 text-slate-600">{{ change.before }}</td><td class="break-words p-3 text-slate-900">{{ change.after }}</td></tr></tbody></table><p v-else class="p-3 text-xs text-slate-500">Pesanan disimpan tanpa perubahan rincian atau nilai.</p></div>
    </details>
  </section>
</template>
