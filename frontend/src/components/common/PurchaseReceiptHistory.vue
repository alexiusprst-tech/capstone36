<script setup>
defineProps({ receipts: { type: Array, default: () => [] } });
const date = value => value ? String(value).slice(0, 10) : 'Tanggal tidak dicatat';
</script>
<template>
  <details class="rounded-lg border border-slate-200 text-xs">
    <summary class="cursor-pointer bg-slate-50 px-4 py-3 font-semibold text-slate-700">Riwayat Penerimaan ({{ receipts.length }})</summary>
    <p v-if="!receipts.length" class="p-4 text-slate-500">Belum ada pemeriksaan penerimaan.</p>
    <div v-for="receipt in receipts" :key="receipt.id || receipt.idempotency_key" class="border-t border-slate-200 p-4">
      <div class="flex flex-wrap items-start justify-between gap-2"><p class="font-semibold">Penerimaan #{{ receipt.id }} · {{ date(receipt.received_date) }}</p><p class="text-slate-600">Penerima: {{ receipt.receiver?.name || receipt.receiver_name || 'Tidak dicatat (data historis)' }}</p></div>
      <p v-if="receipt.notes" class="mt-2 text-slate-600">{{ receipt.notes }}</p>
      <div class="mt-3 min-w-0 sm:overflow-x-auto">
        <table class="block w-full min-w-0 text-left sm:table sm:min-w-[540px]">
          <thead class="hidden border-b bg-slate-50 text-slate-500 sm:table-header-group"><tr><th class="p-2">Barang</th><th class="p-2 text-right">Diterima</th><th class="p-2 text-right">Ditolak / Retur</th><th class="p-2">Alasan Penolakan</th></tr></thead>
          <tbody class="block divide-y divide-slate-100 sm:table-row-group"><tr v-for="(line, index) in receipt.lines || []" :key="index" class="grid min-w-0 grid-cols-2 sm:table-row">
            <td class="col-span-2 block min-w-0 break-words p-2 sm:table-cell">{{ line.name || line.sku }}<span class="block break-all text-[11px] text-slate-500">{{ line.sku }} · {{ line.unit }}</span></td>
            <td class="block min-w-0 p-2 sm:table-cell sm:text-right"><span class="mb-1 block text-[11px] text-slate-500 sm:hidden">Diterima</span>{{ line.accepted_qty }}</td>
            <td class="block min-w-0 p-2 sm:table-cell sm:text-right"><span class="mb-1 block text-[11px] text-slate-500 sm:hidden">Ditolak / Retur</span>{{ line.rejected_qty }}</td>
            <td class="col-span-2 block min-w-0 break-words p-2 sm:table-cell"><span class="mb-1 block text-[11px] text-slate-500 sm:hidden">Alasan Penolakan</span>{{ line.reason || '—' }}</td>
          </tr></tbody>
        </table>
      </div>
    </div>
  </details>
</template>
