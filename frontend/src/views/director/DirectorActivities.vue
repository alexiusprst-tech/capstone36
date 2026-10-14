<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Audit Trail & Log Aktivitas</h1>
          <p class="text-sm text-slate-500 mt-1">
            Rekam jejak seluruh operasi, mutasi data, dan keputusan penting pengguna di dalam sistem.
          </p>
        </div>
      </div>
    </template>

    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-900 uppercase">Kronologi Aktivitas Sistem</span>
        <span class="text-slate-500">{{ store.activityLogs.length }} Aktivitas Tercatat</span>
      </div>

      <div class="divide-y divide-slate-100">
        <div
          v-for="log in store.activityLogs"
          :key="log.id"
          class="p-4 hover:bg-slate-50/70 transition-colors flex items-start gap-4 text-xs"
        >
          <div class="w-8 h-8 rounded-full bg-blue-50 text-alpharo-500 flex items-center justify-center shrink-0 border border-blue-100 mt-0.5">
            <Activity class="w-4 h-4" />
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <span class="font-bold text-slate-900">{{ log.user }}</span>
                <span class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-mono text-[10px]">{{ log.role }}</span>
              </div>
              <span class="text-[11px] text-slate-400 font-mono">{{ log.timestamp }}</span>
            </div>

            <p class="text-slate-800 font-medium mt-1">
              {{ log.action }} &bull; <span class="font-mono text-alpharo-600 font-bold">[{{ log.target }}]</span>
            </p>
            <p class="text-slate-500 text-[11px] mt-0.5">{{ log.details }}</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '../../components/layout/AppLayout.vue';
import { store } from '../../stores/mockData';
import { Activity } from 'lucide-vue-next';
</script>
