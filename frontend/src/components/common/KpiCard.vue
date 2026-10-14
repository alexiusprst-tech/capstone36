<template>
  <div class="bg-white rounded-xl border border-slate-200/90 p-4 sm:p-5 shadow-xs hover:border-slate-300 transition-all duration-200 flex flex-col justify-between min-w-0 overflow-hidden group">
    <div class="flex items-start justify-between gap-2.5">
      <div class="flex-1 min-w-0">
        <!-- Title with graceful multi-line or tooltip -->
        <p
          :title="title"
          class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 leading-snug line-clamp-2 min-h-[28px]"
        >
          {{ title }}
        </p>

        <!-- Value Container: Guaranteed No Wrap on Currency Prefix, Responsive Sizing -->
        <div class="mt-2 min-w-0">
          <div v-if="isCurrency" class="flex items-baseline gap-1 min-w-0 overflow-hidden" :title="`Rp ${formattedNumber}`">
            <span class="text-xs font-bold text-slate-400 font-mono shrink-0 select-none">Rp</span>
            <span
              :class="[
                valueFontSizeClass,
                'font-extrabold text-slate-900 tracking-tight tabular-nums truncate max-w-full'
              ]"
            >
              {{ formattedNumber }}
            </span>
          </div>

          <div v-else class="flex items-baseline gap-1.5 min-w-0 overflow-hidden" :title="String(value)">
            <span
              :class="[
                valueFontSizeClass,
                'font-extrabold text-slate-900 tracking-tight tabular-nums truncate max-w-full'
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
        :class="[
          iconBgClass,
          'w-9 h-9 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center shrink-0 border transition-transform group-hover:scale-105'
        ]"
      >
        <component :is="iconComponent" :class="[iconColorClass, 'w-4 h-4 sm:w-5 sm:h-5']" />
      </div>
    </div>

    <!-- Subtitle / Trend Footer -->
    <div v-if="subtitle || trend" class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] min-w-0">
      <span class="text-slate-400 truncate" :title="subtitle">{{ subtitle }}</span>
      <span
        v-if="trend"
        :class="[
          trendPositive ? 'text-emerald-700 bg-emerald-50' : 'text-slate-600 bg-slate-100',
          'px-1.5 py-0.5 rounded font-medium shrink-0 ml-2 font-mono text-[10px]'
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
  return 'text-2xl sm:text-3xl';
});

const iconBgClass = computed(() => {
  switch (props.iconVariant) {
    case 'emerald':
      return 'bg-emerald-50 border-emerald-100';
    case 'amber':
      return 'bg-amber-50 border-amber-100';
    case 'rose':
      return 'bg-rose-50 border-rose-100';
    case 'indigo':
      return 'bg-indigo-50 border-indigo-100';
    case 'slate':
      return 'bg-slate-50 border-slate-200';
    default:
      return 'bg-blue-50 border-blue-100';
  }
});

const iconColorClass = computed(() => {
  switch (props.iconVariant) {
    case 'emerald':
      return 'text-emerald-600';
    case 'amber':
      return 'text-amber-600';
    case 'rose':
      return 'text-rose-600';
    case 'indigo':
      return 'text-indigo-600';
    case 'slate':
      return 'text-slate-600';
    default:
      return 'text-alpharo-500';
  }
});
</script>
