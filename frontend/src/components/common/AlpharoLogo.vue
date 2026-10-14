<template>
  <div :class="['select-none', vertical ? 'flex flex-col items-center text-center gap-3' : 'flex items-center gap-3']">
    <!-- Real Emblem Image from assets/logo.png / public/images/logo.png -->
    <div :class="[sizeClasses.emblem, 'relative flex items-center justify-center shrink-0']">
      <img
        :src="logoSrc"
        alt="Logo CV Alpharo Artha Jaya"
        class="w-full h-full object-contain filter drop-shadow-md select-none pointer-events-none"
        loading="eager"
      />
    </div>

    <!-- Brand Typography -->
    <div v-if="!collapsed" :class="[vertical ? 'flex flex-col items-center text-center' : 'flex flex-col text-left min-w-0']">
      <span :class="[textColorClass, sizeClasses.titleText, 'font-bold tracking-tight leading-tight']">
        CV. Alpharo
      </span>
      <span :class="[textColorClass, sizeClasses.titleText, 'font-bold tracking-tight leading-tight']">
        Artha Jaya
      </span>
      <span
        v-if="showTagline"
        :class="[taglineColorClass, sizeClasses.taglineText, 'font-semibold uppercase tracking-wider mt-1 block']"
      >
        Supplier & Contractor
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import logoSrc from '../../assets/logo.png';

const props = defineProps({
  collapsed: { type: Boolean, default: false },
  theme: { type: String, default: 'white' }, // 'white' (navy sidebar), 'dark' (white background)
  size: { type: String, default: 'md' }, // 'sm', 'md', 'lg', 'xl'
  showTagline: { type: Boolean, default: false },
  vertical: { type: Boolean, default: false },
});

const sizeClasses = computed(() => {
  if (props.size === 'sm') {
    return {
      emblem: 'w-7 h-7',
      titleText: 'text-xs',
      taglineText: 'text-[9px]',
    };
  }
  if (props.size === 'lg') {
    return {
      emblem: 'w-16 h-16 sm:w-20 sm:h-20',
      titleText: 'text-lg sm:text-xl',
      taglineText: 'text-xs tracking-widest',
    };
  }
  if (props.size === 'xl') {
    return {
      emblem: 'w-24 h-24 sm:w-28 sm:h-28',
      titleText: 'text-xl sm:text-2xl',
      taglineText: 'text-xs sm:text-sm tracking-widest',
    };
  }
  // Default 'md' (sidebar standard)
  return {
    emblem: 'w-10 h-10',
    titleText: 'text-sm',
    taglineText: 'text-[10px]',
  };
});

const textColorClass = computed(() => {
  return props.theme === 'dark' ? 'text-slate-900' : 'text-white';
});

const taglineColorClass = computed(() => {
  return props.theme === 'dark' ? 'text-slate-500' : 'text-blue-200/90';
});
</script>
