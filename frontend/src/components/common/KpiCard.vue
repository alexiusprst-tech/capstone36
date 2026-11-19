<template>
  <div class="ui-panel kpi-card bg-white rounded-xl border border-slate-200/90 p-4 sm:p-5 hover:border-alpharo-200 transition-all duration-200 flex flex-col justify-between min-w-0 overflow-hidden group">
    <div class="flex items-start justify-between gap-2.5">
      <div class="flex-1 min-w-0">
        <!-- Title with graceful multi-line or tooltip -->
        <p
          :title="title"
          class="text-xs font-medium text-slate-500 leading-snug line-clamp-2"
        >
          {{ title }}
        </p>

        <!-- Value Container: Guaranteed No Wrap on Currency Prefix, Responsive Sizing -->
        <div class="mt-2 min-w-0">
          <div v-if="isCurrency" class="flex items-baseline gap-1 min-w-0 overflow-hidden" :title="unavailable ? 'Data belum tersedia' : `Rp ${formattedNumber}`">
            <span v-if="!unavailable" class="text-xs font-bold text-slate-400 font-mono shrink-0 select-none">Rp</span>
            <span
              :class="[
                valueFontSizeClass,
                'font-semibold text-slate-900 tracking-tight tabular-nums max-w-full whitespace-normal break-all'
              ]"
            >
              {{ formattedNumber }}
            </span>
          </div>

          <div v-else class="flex items-baseline gap-1.5 min-w-0 overflow-hidden" :title="String(value)">
            <span
              :class="[
                valueFontSizeClass,
                'font-semibold text-slate-900 tracking-tight tabular-nums max-w-full whitespace-normal break-all'
              ]"
            >
              {{ formattedNumber }}
            </span>
            <span v-if="unit" class="text-xs font-semibold text-slate-400 shrink-0">
              {{ unit }}
            </span>
          </div>
        </div>
      </div>

      <!-- Icon Component -->
      <div
        v-if="iconComponent"
        class="flex items-center justify-center shrink-0 pt-0.5"
      >
        <component :is="iconComponent" :class="[iconColorClass, 'w-4 h-4']" :stroke-width="1.7" />
      </div>
    </div>

    <!-- Subtitle / Trend Footer -->
    <div v-if="subtitle || trend" class="mt-3 flex flex-wrap gap-y-1 items-center justify-between text-[11px] min-w-0">
      <span class="text-slate-500 truncate" :title="subtitle">{{ subtitle }}</span>
      <span
        v-if="trend"
        :class="[
          trendPositive ? 'text-emerald-700' : 'text-slate-600',
          'font-medium shrink-0 ml-2 text-[11px]'
        ]"
      >
        {{ trend }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: { type: String, required: true },
  unavailable: { type: Boolean, default: false },
  value: { type: [Number, String], required: true },
  unit: { type: String, default: '' },
  isCurrency: { type: Boolean, default: false },
  iconComponent: { type: Object, default: null },
  iconVariant: { type: String, default: 'blue' }, // 'blue', 'amber', 'emerald', 'rose', 'slate', 'indigo'
  subtitle: { type: String, default: '' },
  trend: { type: String, default: '' },
  trendPositive: { type: Boolean, default: true },
});

const formattedNumber = computed(() => {
  if (props.unavailable || (typeof props.value === 'number' && !Number.isFinite(props.value))) return '—';
  if (typeof props.value === 'number') {
    return props.value.toLocaleString('id-ID');
  }
  return String(props.value || '0');
});

const valueFontSizeClass = computed(() => {
  const strLen = formattedNumber.value.length;
  if (props.isCurrency) {
    if (strLen > 11) return 'text-base sm:text-lg'; // e.g. 1.234.567.890
    if (strLen > 8) return 'text-lg sm:text-xl';   // e.g. 232.472.500
    return 'text-xl sm:text-2xl';                  // e.g. 1.500.000
  }
  if (strLen > 8) return 'text-lg sm:text-xl';
  if (strLen > 5) return 'text-xl sm:text-2xl';
  return 'text-2xl';
});

const iconColorClass = computed(() => {
  const variants = { amber: 'text-amber-600', emerald: 'text-emerald-600', rose: 'text-rose-600', slate: 'text-slate-500' };
  return variants[props.iconVariant] || 'text-alpharo-600';
});
</script>
