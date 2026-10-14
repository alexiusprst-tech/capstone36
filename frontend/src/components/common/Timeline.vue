<template>
  <div class="flow-root">
    <ul role="list" class="-mb-8">
      <li v-for="(item, itemIdx) in timeline" :key="itemIdx">
        <div class="relative pb-8">
          <!-- Connecting vertical line -->
          <span
            v-if="itemIdx !== timeline.length - 1"
            class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200"
            aria-hidden="true"
          ></span>

          <div class="relative flex items-start space-x-3">
            <!-- Stage Icon Pin -->
            <div>
              <div
                :class="[
                  item.status === 'completed'
                    ? 'bg-emerald-500 text-white ring-4 ring-emerald-50'
                    : item.status === 'in_progress'
                    ? 'bg-alpharo-500 text-white ring-4 ring-blue-50 animate-pulse'
                    : 'bg-slate-200 text-slate-400',
                  'h-8 w-8 rounded-full flex items-center justify-center'
                ]"
              >
                <svg v-if="item.status === 'completed'" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                <svg v-else-if="item.status === 'in_progress'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span v-else class="text-xs font-bold">{{ itemIdx + 1 }}</span>
              </div>
            </div>

            <!-- Content -->
            <div class="min-w-0 flex-1 pt-1">
              <div class="flex items-center justify-between text-xs">
                <p class="font-semibold text-slate-900 text-sm">
                  {{ item.stage }}
                </p>
                <span class="text-slate-400 whitespace-nowrap ml-2">
                  {{ item.timestamp || '-' }}
                </span>
              </div>

              <div class="mt-1 flex items-center gap-2 text-xs text-slate-600">
                <span class="font-medium text-slate-700">{{ item.user }}</span>
                <span v-if="item.user && item.user !== '-'">•</span>
                <span
                  :class="[
                    item.status === 'completed'
                      ? 'text-emerald-700'
                      : item.status === 'in_progress'
                      ? 'text-blue-700 font-medium'
                      : 'text-slate-400'
                  ]"
                >
                  {{ item.status === 'completed' ? 'Tuntas' : item.status === 'in_progress' ? 'Sedang Berjalan' : 'Menunggu' }}
                </span>
              </div>

              <p v-if="item.notes && item.notes !== '-'" class="mt-1.5 text-xs text-slate-500 bg-slate-50 p-2 rounded border border-slate-100">
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
