<script setup>
import { computed } from 'vue';
import Modal from './Modal.vue';
import ShippingStatusLabel from './ShippingStatusLabel.vue';
const props = defineProps({ modelValue: Boolean, purchase: { type: Object, default: null } });
defineEmits(['update:modelValue']);
const delivery = computed(() => props.purchase?.delivery_info || {});
const events = computed(() => (props.purchase?.progress_history || []).filter(event => ['dispatch', 'tracking', 'arrival', 'replacement', 'discrepancy', 'followup', 'receipt'].includes(event.action)).slice().reverse());
const shippingUpdated = computed(() => events.value.find(event => ['dispatch', 'tracking', 'arrival', 'replacement'].includes(event.action))?.timestamp || delivery.value.last_updated);
const date = value => {
  if (!value) return 'Waktu tidak dicatat';
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? String(value) : parsed.toLocaleString('id-ID');
};
const action = value => ({ dispatch: 'Supplier mengirim barang', tracking: 'Pembaruan pengiriman', arrival: 'Barang tiba di Gudang', replacement: 'Pengiriman barang pengganti', discrepancy: 'Ketidaksesuaian penerimaan', followup: 'Finance menindaklanjuti Supplier', receipt: 'Pemeriksaan penerimaan' })[value] || value;
</script>
<template>
  <Modal :model-value="modelValue" :title="`Tracking Pengiriman ${purchase?.purchase_code || ''}`" subtitle="Data pengiriman dan pembaruan yang tersimpan pada PO." size="xl" @update:model-value="$emit('update:modelValue', $event)">
    <div class="space-y-5 text-sm">
      <p class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-blue-800">Cek Resi menampilkan catatan Supplier, Finance, dan Gudang di sistem. Status ini tidak mengambil data langsung dari layanan kurir.</p>
      <dl class="grid gap-x-5 gap-y-3 sm:grid-cols-2">
        <div><dt class="text-xs text-slate-500">Ekspedisi / Kurir</dt><dd class="mt-1 font-medium">{{ delivery.courier || 'Belum diisi Supplier' }}</dd></div>
        <div><dt class="text-xs text-slate-500">No. Resi / Plat Kendaraan</dt><dd class="mt-1 break-all font-mono font-semibold">{{ delivery.tracking_number || 'Belum tersedia' }}</dd></div>
        <div><dt class="text-xs text-slate-500">No. Surat Jalan Supplier</dt><dd class="mt-1">{{ delivery.surat_jalan || 'Belum tersedia' }}</dd></div>
        <div><dt class="text-xs text-slate-500">Status Pengiriman</dt><dd class="mt-1 font-medium"><ShippingStatusLabel :status="delivery.shipping_status || purchase?.supplier_status" /></dd></div>
        <div><dt class="text-xs text-slate-500">Tanggal Kirim</dt><dd class="mt-1">{{ delivery.dispatch_date || 'Belum tersedia' }}</dd></div>
        <div><dt class="text-xs text-slate-500">Estimasi Tiba</dt><dd class="mt-1">{{ delivery.est_arrival || 'Belum tersedia' }}</dd></div>
        <div><dt class="text-xs text-slate-500">Lokasi Terakhir Dicatat</dt><dd class="mt-1">{{ delivery.current_location || 'Belum dicatat' }}</dd></div>
        <div><dt class="text-xs text-slate-500">Pembaruan Terakhir</dt><dd class="mt-1">{{ shippingUpdated ? date(shippingUpdated) : 'Belum dicatat' }}</dd></div>
      </dl>
      <p v-if="delivery.notes" class="rounded-lg bg-slate-50 p-3 text-slate-600">{{ delivery.notes }}</p>
      <section><h4 class="border-b pb-2 font-semibold text-slate-900">Riwayat Pengiriman & Pemeriksaan</h4>
        <p v-if="!events.length && !delivery.milestones?.length" class="py-4 text-slate-500">Belum ada pembaruan pengiriman yang tercatat.</p>
        <ol v-if="events.length" class="divide-y divide-slate-100">
          <li v-for="(event, index) in events" :key="event.version || `${event.action}-${index}`" class="py-3">
            <div class="flex flex-wrap justify-between gap-2"><p class="font-medium">{{ action(event.action) }}</p><time class="text-xs text-slate-500">{{ date(event.timestamp) }}</time></div>
            <p class="mt-1 text-xs text-slate-500">{{ event.user || 'Petugas tidak dicatat' }}<span v-if="event.role"> · {{ event.role }}</span></p>
            <p v-if="event.after?.delivery_info?.shipping_status || event.after?.supplier_status" class="mt-2 text-slate-700"><ShippingStatusLabel :status="event.after?.delivery_info?.shipping_status || event.after?.supplier_status" /></p>
            <p v-if="event.after?.delivery_info?.current_location" class="mt-1 text-xs text-slate-600">Lokasi: {{ event.after.delivery_info.current_location }}</p>
            <p v-if="event.after?.delivery_info?.notes" class="mt-1 text-xs text-slate-600">{{ event.after.delivery_info.notes }}</p>
          </li>
        </ol>
        <h5 v-if="events.length && delivery.milestones?.length" class="mt-3 border-t pt-3 font-semibold text-slate-700">Catatan Posisi Supplier</h5>
        <ol class="divide-y divide-slate-100"><li v-for="(milestone, index) in delivery.milestones || []" :key="index" class="py-3"><p class="font-medium"><ShippingStatusLabel :status="milestone.status" fallback="Pembaruan pengiriman" /></p><p class="mt-1 text-xs text-slate-500">{{ milestone.date || milestone.timestamp || 'Waktu tidak dicatat' }}<span v-if="milestone.location"> · {{ milestone.location }}</span></p><p v-if="milestone.notes" class="mt-1">{{ milestone.notes }}</p></li></ol>
      </section>
    </div>
  </Modal>
</template>
