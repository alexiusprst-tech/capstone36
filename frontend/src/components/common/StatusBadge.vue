<template>
  <span
    :class="[
      badgeClasses,
      'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium tracking-wide transition-colors whitespace-nowrap'
    ]"
  >
    <span :class="[dotClasses, 'w-1.5 h-1.5 rounded-full shrink-0']"></span>
    <slot>{{ status }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: {
    type: String,
    required: true,
  },
  type: {
    type: String,
    default: 'auto', // 'order', 'document', 'payment', 'warehouse', or 'auto'
  },
});

const normalized = computed(() => (props.status || '').toLowerCase().trim());

const colorScheme = computed(() => {
  const s = normalized.value;

  // Green / Success
  if (['disetujui', 'lengkap', 'lunas', 'tersedia', 'selesai', 'aktif'].includes(s)) {
    return 'success';
  }

  // Yellow / Amber / Warning
  if (
    ['menunggu verifikasi', 'sebagian / termin', 'termin', 'draft', 'menipis'].includes(s) ||
    s.includes('sebagian') ||
    s.includes('menunggu')
  ) {
    return 'warning';
  }

  // Blue / Info
  if (['diproses', 'siap dikirim', 'sedang dikirim'].includes(s) || s.includes('proses') || s.includes('kirim')) {
    return 'info';
  }

  // Purple / Client Submission
  if (['diajukan client', 'pengajuan client', 'diajukan'].includes(s) || s.includes('diajukan')) {
    return 'purple';
  }

  // Red / Danger
  if (
    ['ditolak', 'tidak tersedia', 'jatuh tempo', 'overdue', 'batal', 'nonaktif', 'ditahan', 'on hold', 'hold'].includes(s) ||
    s.includes('tahan') ||
    s.includes('hold')
  ) {
    return 'danger';
  }

  // Neutral / Slate
  // 'belum dibuat', 'belum dibayar', etc.
  return 'neutral';
});

const badgeClasses = computed(() => {
  switch (colorScheme.value) {
    case 'success':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    case 'warning':
      return 'bg-amber-50 text-amber-800 border border-amber-200';
    case 'info':
      return 'bg-blue-50 text-blue-700 border border-blue-200';
    case 'purple':
      return 'bg-purple-50 text-purple-700 border border-purple-200 font-semibold';
    case 'danger':
      return 'bg-rose-50 text-rose-700 border border-rose-200';
    default:
      return 'bg-slate-100 text-slate-700 border border-slate-200';
  }
});

const dotClasses = computed(() => {
  switch (colorScheme.value) {
    case 'success':
      return 'bg-emerald-500';
    case 'warning':
      return 'bg-amber-500';
    case 'info':
      return 'bg-blue-500';
    case 'purple':
      return 'bg-purple-500';
    case 'danger':
      return 'bg-rose-500';
    default:
      return 'bg-slate-400';
  }
});
</script>
