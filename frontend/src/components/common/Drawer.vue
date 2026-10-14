<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-50 overflow-hidden"
      role="dialog"
      aria-modal="true"
    >
      <!-- Backdrop -->
      <div
        class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
        @click="$emit('update:modelValue', false)"
      ></div>

      <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
        <div
          :class="[
            widthClass,
            'w-screen bg-white shadow-2xl flex flex-col border-l border-slate-200 animate-in slide-in-from-right duration-200'
          ]"
        >
          <!-- Drawer Header -->
          <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div>
              <h2 class="text-base font-semibold text-slate-900">
                {{ title }}
              </h2>
              <p v-if="subtitle" class="text-xs text-slate-500 mt-0.5">
                {{ subtitle }}
              </p>
            </div>
            <button
              type="button"
              class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors"
              @click="$emit('update:modelValue', false)"
            >
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>

          <!-- Drawer Body -->
          <div class="flex-1 overflow-y-auto px-6 py-5">
            <slot></slot>
          </div>

          <!-- Drawer Footer -->
          <div v-if="$slots.footer" class="border-t border-slate-200 px-6 py-4 bg-slate-50 flex items-center justify-end gap-3">
            <slot name="footer"></slot>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  size: { type: String, default: 'md' }, // 'md', 'lg', 'xl'
});

defineEmits(['update:modelValue']);

const widthClass = computed(() => {
  switch (props.size) {
    case 'lg':
      return 'max-w-2xl';
    case 'xl':
      return 'max-w-4xl';
    default:
      return 'max-w-xl';
  }
});
</script>
