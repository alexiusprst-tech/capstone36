<template>
  <div class="flow-root">
    <ul role="list" class="-mb-8">
      <li v-for="(item, itemIdx) in timeline" :key="itemIdx">
        <div class="relative pb-8">
          <!-- Connecting vertical line -->
          <span
            v-if="itemIdx !== timeline.length - 1"
            :class="item.status === 'completed' ? 'bg-emerald-300' : 'bg-slate-200'"
            class="absolute left-4 top-4 -ml-px h-full w-0.5 transition-colors"
            aria-hidden="true"
          ></span>

          <div class="relative flex items-start space-x-3">
            <!-- Stage Icon Pin -->
            <div>
              <div
                :class="[
                  item.status === 'completed'
                    ? 'bg-emerald-500 text-white ring-4 ring-emerald-50 shadow-xs'
                    : item.status === 'in_progress'
                    ? 'bg-[#0B4DA2] text-white ring-4 ring-blue-50 animate-pulse shadow-xs'
                    : item.status === 'rejected'
                    ? 'bg-rose-500 text-white ring-4 ring-rose-50 shadow-xs'
                    : 'bg-slate-200 text-slate-400',
                  'h-8 w-8 rounded-full flex items-center justify-center transition-all shrink-0'
                ]"
              >
                <!-- Completed Check Icon -->
                <svg v-if="item.status === 'completed'" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                <!-- In Progress Clock Icon -->
                <svg v-else-if="item.status === 'in_progress'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <!-- Rejected / Ditahan X Icon -->
                <svg v-else-if="item.status === 'rejected'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <!-- Pending Step Number -->
                <span v-else class="text-xs font-bold">{{ itemIdx + 1 }}</span>
              </div>
            </div>

            <!-- Content -->
            <div class="min-w-0 flex-1 pt-0.5">
              <div class="flex items-center justify-between text-xs">
                <p class="font-bold text-slate-900 text-sm flex items-center gap-2">
                  <span>{{ item.stage }}</span>
                  <span
                    v-if="item.status === 'in_progress'"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-[#0B4DA2] border border-blue-200"
                  >
                    Aktif Saat Ini
                  </span>
                  <span
                    v-else-if="item.status === 'rejected'"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200"
                  >
                    Perhatian
                  </span>
                </p>
                <span class="text-slate-400 whitespace-nowrap ml-2 font-mono text-[11px]">
                  {{ item.timestamp || '-' }}
                </span>
              </div>

              <div class="mt-1 flex items-center gap-2 text-xs text-slate-600">
                <span class="font-medium text-slate-700">{{ item.user }}</span>
                <span v-if="item.user && item.user !== '-'">•</span>
                <span
                  :class="[
                    item.status === 'completed'
                      ? 'text-emerald-700 font-semibold'
                      : item.status === 'in_progress'
                      ? 'text-[#0B4DA2] font-bold'
                      : item.status === 'rejected'
                      ? 'text-rose-700 font-bold'
                      : 'text-slate-400'
                  ]"
                >
                  {{ item.status === 'completed' ? 'Tuntas' : item.status === 'in_progress' ? 'Sedang Berjalan' : item.status === 'rejected' ? 'Ditolak / Ditahan' : 'Menunggu' }}
                </span>
              </div>

              <p v-if="item.notes && item.notes !== '-'" class="mt-1.5 text-xs text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-200 leading-relaxed">
                {{ item.notes }}
              </p>
            </div>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
defineProps({
  timeline: {
    type: Array,
    default: () => [],
  },
});
</script>
