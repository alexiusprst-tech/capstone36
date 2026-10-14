<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-50 overflow-y-auto"
      role="dialog"
      aria-modal="true"
    >
      <div
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"
        @click="$emit('cancel')"
      ></div>

      <div class="flex min-h-full items-center justify-center p-4 text-center">
        <div class="relative w-full max-w-md transform overflow-hidden rounded-xl bg-white p-6 text-left shadow-xl transition-all border border-slate-200">
          <div class="flex items-start gap-4">
            <div
              :class="[
                variant === 'danger' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600',
                'flex h-11 w-11 shrink-0 items-center justify-center rounded-full'
              ]"
            >
              <svg v-if="variant === 'danger'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <svg v-else class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>

            <div class="flex-1">
              <h3 class="text-base font-semibold leading-6 text-slate-900">
                {{ title }}
              </h3>
              <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                {{ message }}
              </p>
            </div>
          </div>

          <div class="mt-6 flex items-center justify-end gap-3">
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
              @click="$emit('cancel')"
            >
              {{ cancelText }}
            </button>
            <button
              type="button"
              :class="[
                variant === 'danger'
                  ? 'bg-rose-600 hover:bg-rose-700 text-white'
                  : 'bg-alpharo-500 hover:bg-alpharo-600 text-white',
                'px-4 py-2 text-sm font-semibold rounded-lg shadow-xs transition-colors'
              ]"
              @click="$emit('confirm')"
            >
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Konfirmasi Tindakan' },
  message: { type: String, default: 'Apakah Anda yakin ingin melanjutkan tindakan ini?' },
  confirmText: { type: String, default: 'Ya, Lanjutkan' },
  cancelText: { type: String, default: 'Batal' },
  variant: { type: String, default: 'danger' }, // 'danger', 'warning', 'primary'
});

defineEmits(['confirm', 'cancel', 'update:modelValue']);
</script>
