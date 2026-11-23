<script setup>
defineProps({ label: { type: String, default: '' }, tone: { type: String, default: 'neutral' } });
const tones = { blue: 'bg-blue-50 text-blue-700 border-blue-200', green: 'bg-emerald-50 text-emerald-700 border-emerald-200', amber: 'bg-amber-50 text-amber-800 border-amber-200', red: 'bg-red-50 text-red-700 border-red-200', neutral: 'bg-slate-50 text-slate-600 border-slate-200' };
</script>
<template><span :class="['inline-flex rounded-md border px-2 py-1 text-[11px] font-semibold leading-4', tones[tone] || tones.neutral]">{{ label }}</span></template>
