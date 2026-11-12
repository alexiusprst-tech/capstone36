<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      ref="dialogElement"
      tabindex="-1"
      :aria-label="title"
      class="ui-scope fixed inset-0 z-50 overflow-y-auto"
      role="dialog"
      aria-modal="true"
    >
      <!-- Backdrop -->
      <div
        class="fixed inset-0 bg-slate-950/30 backdrop-blur-xs transition-opacity"
        @click="$emit('update:modelValue', false)"
      ></div>

      <!-- Center Container -->
      <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-6">
        <div
          :class="[
            maxWidthClass,
            'ui-dialog-panel relative w-full max-h-[calc(100dvh-2rem)] flex flex-col overflow-hidden rounded-xl bg-white text-left border border-slate-200'
          ]"
        >
          <!-- Modal Header -->
          <div class="ui-dialog-header flex items-center justify-between gap-4 border-b border-slate-100 px-5 sm:px-6 py-4 shrink-0">
            <div>
              <h3 class="text-base font-semibold text-slate-900">
                {{ title }}
              </h3>
              <p v-if="subtitle" class="text-xs text-slate-500 mt-0.5">
                {{ subtitle }}
              </p>
            </div>
            <button
              type="button"
              aria-label="Tutup dialog"
              class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors"
              @click="$emit('update:modelValue', false)"
            >
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>

          <!-- Modal Body -->
          <div class="ui-dialog-body px-5 sm:px-6 py-5 min-h-0 flex-1 overflow-y-auto">
            <slot></slot>
          </div>

          <!-- Modal Footer -->
          <div v-if="$slots.footer" class="ui-dialog-footer border-t border-slate-100 px-5 sm:px-6 py-4 flex items-center justify-end gap-3 shrink-0">
            <slot name="footer"></slot>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useDialog } from '../../composables/useDialog';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  size: { type: String, default: 'md' }, // 'sm', 'md', 'lg', 'xl', '2xl', 'full'
});

const emit = defineEmits(['update:modelValue']);
const dialogElement = ref(null);
useDialog(dialogElement, () => props.modelValue, () => emit('update:modelValue', false));

const maxWidthClass = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'max-w-md';
    case 'lg':
      return 'max-w-3xl';
    case 'xl':
      return 'max-w-4xl';
    case '2xl':
      return 'max-w-5xl';
    case 'full':
      return 'max-w-6xl';
    default:
      return 'max-w-xl';
  }
});
</script>
