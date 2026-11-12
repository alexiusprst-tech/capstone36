<template>
  <ul v-if="items?.length" aria-label="Rincian barang" class="min-w-0 divide-y divide-slate-100">
    <li
      v-for="(item, index) in items"
      :key="index"
      class="grid grid-cols-[minmax(0,1fr)_max-content] items-start gap-x-4 py-2 text-xs leading-relaxed first:pt-0 last:pb-0"
    >
      <span class="min-w-0 whitespace-normal wrap-break-word font-medium text-slate-800">{{ item.barang || item.name }}</span>
      <span class="whitespace-nowrap font-mono text-slate-600 tabular-nums">{{ item.qty }} {{ item.unit || 'UNIT' }}</span>
    </li>
  </ul>
</template>

<script setup>
defineProps({ items: { type: Array, default: () => [] } });
</script>
