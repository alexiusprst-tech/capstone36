<template>
  <Teleport to="body">
    <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm pointer-events-none">
      <div
        v-for="toast in toastService.state.toasts"
        :key="toast.id"
        :class="[
          toastClasses(toast.type),
          'pointer-events-auto flex items-center justify-between gap-3 px-4 py-3 rounded-xl shadow-lg border text-sm transition-all animate-in slide-in-from-bottom duration-200'
        ]"
      >
        <div class="flex items-center gap-2.5">
          <span v-if="toast.type === 'success'" class="text-emerald-500 font-bold">✓</span>
          <span v-else-if="toast.type === 'error'" class="text-rose-500 font-bold">✕</span>
          <span v-else-if="toast.type === 'warning'" class="text-amber-500 font-bold">⚠</span>
          <span v-else class="text-blue-500 font-bold">ℹ</span>
          <span class="font-medium text-slate-800">{{ toast.message }}</span>
        </div>
        <button
          type="button"
          class="text-slate-400 hover:text-slate-600 ml-2"
          @click="toastService.dismiss(toast.id)"
        >
          ×
        </button>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { useToast } from '../../services/toast';

const toastService = useToast();

const toastClasses = (type) => {
  switch (type) {
    case 'success':
      return 'bg-white border-emerald-200 shadow-emerald-500/5';
    case 'error':
      return 'bg-white border-rose-200 shadow-rose-500/5';
    case 'warning':
      return 'bg-white border-amber-200 shadow-amber-500/5';
    default:
      return 'bg-white border-slate-200 shadow-slate-500/5';
  }
};
</script>
