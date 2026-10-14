import { reactive } from 'vue';

const toastState = reactive({
  toasts: [],
});

export const useToast = () => {
  const show = (message, type = 'success', duration = 3500) => {
    const id = Date.now() + Math.random();
    toastState.toasts.push({ id, message, type });
    setTimeout(() => {
      dismiss(id);
    }, duration);
  };

  const success = (message, duration) => show(message, 'success', duration);
  const error = (message, duration) => show(message, 'error', duration);
  const info = (message, duration) => show(message, 'info', duration);
  const warning = (message, duration) => show(message, 'warning', duration);

  const dismiss = (id) => {
    const idx = toastState.toasts.findIndex((t) => t.id === id);
    if (idx !== -1) {
      toastState.toasts.splice(idx, 1);
    }
  };

  return {
    state: toastState,
    show,
    success,
    error,
    info,
    warning,
    dismiss,
  };
};
