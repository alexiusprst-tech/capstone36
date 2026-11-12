<template>
  <section v-if="isInternal" class="ui-panel bg-white rounded-xl border border-slate-200/90" aria-label="Log Aktivitas">
    <div class="p-5 pb-4 border-b border-slate-100 flex flex-wrap items-start justify-between gap-3">
      <div class="flex items-start gap-2.5 min-w-0">
        <span class="text-alpharo-600 inline-flex items-center justify-center shrink-0 mt-0.5">
          <History class="w-4 h-4" aria-hidden="true" />
        </span>
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <h3 class="text-sm font-semibold text-slate-900">Log Aktivitas</h3>
            <p class="text-[11px] text-slate-500" role="status" aria-live="polite">{{ visibleLogs.length }} aktivitas tercatat</p>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">{{ scopeDescription }}</p>
        </div>
      </div>
      <Link v-if="role === 'DIREKTUR'" href="/director/reports?tab=activities" class="text-xs font-medium text-alpharo-600 hover:underline inline-flex items-center gap-1">
        Lihat audit lengkap <ArrowUpRight class="w-3.5 h-3.5" aria-hidden="true" />
      </Link>
    </div>

    <ol v-if="pageLogs.length" ref="activityList" class="divide-y divide-slate-100 px-5 xl:max-h-60 xl:overflow-y-auto overscroll-auto" aria-label="Riwayat aktivitas" tabindex="0">
      <li v-for="(log, index) in pageLogs" :key="`${log.id}-${index}`" class="py-3 flex items-start gap-3">
        <span class="w-2 h-2 mt-1.5 rounded-full bg-alpharo-500 shrink-0" aria-hidden="true"></span>
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
            <p class="font-medium text-xs text-slate-900 break-words min-w-0">{{ log.action || 'Aktivitas sistem' }}</p>
            <time :datetime="String(log.timestamp || '').replace(' ', 'T')" class="text-[11px] text-slate-400 tabular-nums whitespace-nowrap">{{ log.timestamp || 'Waktu tidak tersedia' }}</time>
          </div>
          <p class="mt-1 text-xs text-slate-500 break-words leading-relaxed">{{ log.details || 'Tidak ada keterangan tambahan.' }}</p>
          <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-slate-500">
            <span class="font-medium text-slate-600 break-words">{{ log.user || 'Sistem' }}</span>
            <span class="text-slate-400">{{ roleLabel(log.role) }}</span>
            <span v-if="log.target" class="font-mono text-alpharo-600 break-all">{{ log.target }}</span>
          </div>
        </div>
      </li>
    </ol>
    <div v-else class="px-5 py-10 text-center">
      <History class="w-7 h-7 text-slate-300 mx-auto mb-2" aria-hidden="true" />
      <p class="text-xs font-medium text-slate-600">Belum ada aktivitas yang tercatat.</p>
      <p class="text-[11px] text-slate-400 mt-1">Aktivitas akan muncul saat proses pada modul dijalankan.</p>
    </div>

    <div v-if="visibleLogs.length" class="px-5 py-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
      <p class="text-[11px] text-slate-500">{{ rangeStart }}–{{ rangeEnd }} dari {{ visibleLogs.length }} aktivitas</p>
      <div v-if="pageCount > 1" class="flex items-center gap-2">
        <button type="button" :disabled="page === 1" class="p-2 rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40" aria-label="Aktivitas sebelumnya" @click="page--"><ChevronLeft class="w-3.5 h-3.5" aria-hidden="true" /></button>
        <span class="text-[11px] text-slate-500 tabular-nums">{{ page }} / {{ pageCount }}</span>
        <button type="button" :disabled="page === pageCount" class="p-2 rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40" aria-label="Aktivitas berikutnya" @click="page++"><ChevronRight class="w-3.5 h-3.5" aria-hidden="true" /></button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { ArrowUpRight, ChevronLeft, ChevronRight, History } from 'lucide-vue-next';
import { store } from '../../stores/mockData';
import { ACTIVITY_ROLE_LABELS, INTERNAL_ACTIVITY_ROLES, getVisibleActivityLogs } from '../../services/activityLogs';

const pageSize = 5;
const page = ref(1);
const activityList = ref(null);
const role = computed(() => store.currentUser?.role);
const isInternal = computed(() => INTERNAL_ACTIVITY_ROLES.includes(role.value));
const roleLabel = key => ACTIVITY_ROLE_LABELS[key] || 'Sistem';
const scopeDescription = computed(() => role.value === 'DIREKTUR' ? 'Aktivitas seluruh divisi dan portal rekanan.' : `Aktivitas divisi ${roleLabel(role.value)}.`);
const visibleLogs = computed(() => getVisibleActivityLogs(store.activityLogs, role.value));
const pageCount = computed(() => Math.max(1, Math.ceil(visibleLogs.value.length / pageSize)));
const pageLogs = computed(() => visibleLogs.value.slice((page.value - 1) * pageSize, page.value * pageSize));
const rangeStart = computed(() => (page.value - 1) * pageSize + 1);
const rangeEnd = computed(() => Math.min(page.value * pageSize, visibleLogs.value.length));

watch(role, () => { page.value = 1; });
watch(pageCount, count => { page.value = Math.min(page.value, count); });
watch([role, page], () => { activityList.value?.scrollTo({ top: 0 }); }, { flush: 'post' });
</script>
