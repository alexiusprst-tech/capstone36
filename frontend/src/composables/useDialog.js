import { nextTick, onBeforeUnmount, watch } from 'vue';

let openDialogs = 0;
let previousOverflow = '';

/** Keyboard and focus behavior shared by the existing modal and drawer components. */
export function useDialog(element, isOpen, close) {
  let previousFocus;
  let active = false;
  const focusable = () => [...(element.value?.querySelectorAll('a[href], button:not(:disabled), input:not(:disabled):not([type="hidden"]), select:not(:disabled), textarea:not(:disabled), [tabindex="0"]') || [])]
    .filter(node => node.getClientRects().length && !node.closest('[inert]'));
  const onKeydown = event => {
    const dialogs = document.querySelectorAll('[aria-modal="true"]');
    if (dialogs[dialogs.length - 1] !== element.value) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      event.stopPropagation();
      close();
    } else if (event.key === 'Tab') {
      const items = focusable();
      const first = items[0];
      const last = items[items.length - 1];
      if (!first) {
        event.preventDefault();
        element.value?.focus();
      } else if (event.shiftKey && (document.activeElement === first || !element.value.contains(document.activeElement))) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && (document.activeElement === last || !element.value.contains(document.activeElement))) {
        event.preventDefault();
        first.focus();
      }
    }
  };
  const cleanup = () => {
    if (!active) return;
    active = false;
    document.removeEventListener('keydown', onKeydown, true);
    if (--openDialogs === 0) document.body.style.overflow = previousOverflow;
    if (previousFocus?.isConnected) previousFocus.focus({ preventScroll: true });
  };
  watch(isOpen, async open => {
    if (!open) return cleanup();
    previousFocus = document.activeElement;
    await nextTick();
    if (!isOpen() || !element.value || active) return;
    active = true;
    if (openDialogs++ === 0) {
      previousOverflow = document.body.style.overflow;
      document.body.style.overflow = 'hidden';
    }
    document.addEventListener('keydown', onKeydown, true);
    (focusable()[0] || element.value).focus({ preventScroll: true });
  }, { immediate: true });
  onBeforeUnmount(cleanup);
}
