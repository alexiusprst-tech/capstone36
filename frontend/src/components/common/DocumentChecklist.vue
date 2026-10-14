<template>
  <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
    <div class="flex items-center justify-between mb-2.5">
      <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">
        Kelengkapan Dokumen
      </span>
      <span class="text-xs font-bold text-slate-800">
        {{ completeCount }} / 4 Lengkap
      </span>
    </div>

    <!-- Progress bar -->
    <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden mb-3">
      <div
        :class="[
          completeCount === 4 ? 'bg-emerald-500' : completeCount >= 2 ? 'bg-alpharo-500' : 'bg-amber-500',
          'h-full transition-all duration-300'
        ]"
        :style="{ width: `${(completeCount / 4) * 100}%` }"
      ></div>
    </div>

    <!-- Document Items -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
      <!-- PO -->
      <div
        :class="[
          documents?.po?.status === 'Lengkap' ? 'bg-white border-emerald-200 text-emerald-800' : 'bg-slate-100 border-slate-200 text-slate-500',
          'p-2.5 rounded-lg border flex flex-col justify-between'
        ]"
      >
        <div class="flex items-center justify-between font-medium">
          <span>Purchase Order (PO)</span>
          <span v-if="documents?.po?.status === 'Lengkap'">✓</span>
          <span v-else>○</span>
        </div>
        <span class="text-[10px] text-slate-500 truncate mt-1">
          {{ documents?.po?.number || 'Belum Dibuat' }}
        </span>
      </div>

      <!-- Surat Jalan -->
      <div
        :class="[
          documents?.suratJalan?.status === 'Lengkap' ? 'bg-white border-emerald-200 text-emerald-800' : 'bg-slate-100 border-slate-200 text-slate-500',
          'p-2.5 rounded-lg border flex flex-col justify-between'
        ]"
      >
        <div class="flex items-center justify-between font-medium">
          <span>Surat Jalan</span>
          <span v-if="documents?.suratJalan?.status === 'Lengkap'">✓</span>
          <span v-else>○</span>
        </div>
        <span class="text-[10px] text-slate-500 truncate mt-1">
          {{ documents?.suratJalan?.number || 'Belum Dibuat' }}
        </span>
      </div>

      <!-- BAST -->
      <div
        :class="[
          documents?.bast?.status === 'Lengkap' ? 'bg-white border-emerald-200 text-emerald-800' : 'bg-slate-100 border-slate-200 text-slate-500',
          'p-2.5 rounded-lg border flex flex-col justify-between'
        ]"
      >
        <div class="flex items-center justify-between font-medium">
          <span>BAST</span>
          <span v-if="documents?.bast?.status === 'Lengkap'">✓</span>
          <span v-else>○</span>
        </div>
        <span class="text-[10px] text-slate-500 truncate mt-1">
          {{ documents?.bast?.number || 'Belum Dibuat' }}
        </span>
      </div>

      <!-- Faktur -->
      <div
        :class="[
          documents?.faktur?.status === 'Lengkap' ? 'bg-white border-emerald-200 text-emerald-800' : 'bg-slate-100 border-slate-200 text-slate-500',
          'p-2.5 rounded-lg border flex flex-col justify-between'
        ]"
      >
        <div class="flex items-center justify-between font-medium">
          <span>Faktur</span>
          <span v-if="documents?.faktur?.status === 'Lengkap'">✓</span>
          <span v-else>○</span>
        </div>
        <span class="text-[10px] text-slate-500 truncate mt-1">
          {{ documents?.faktur?.number || 'Belum Dibuat' }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  documents: {
    type: Object,
    default: () => ({}),
  },
});

const completeCount = computed(() => {
  if (!props.documents) return 0;
  let count = 0;
  if (props.documents.po?.status === 'Lengkap') count++;
  if (props.documents.suratJalan?.status === 'Lengkap') count++;
  if (props.documents.bast?.status === 'Lengkap') count++;
  if (props.documents.faktur?.status === 'Lengkap') count++;
  return count;
});
</script>
