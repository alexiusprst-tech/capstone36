import { onBeforeUnmount, onMounted, ref } from 'vue';

/** Visible-page polling only. Exposed controller keeps lifecycle behavior independently testable. */
export function createLiveRefresh(callback, options = {}) {
  const intervalMs = options.intervalMs ?? 10000;
  const enabled = options.enabled ?? (() => true);
  const page = options.document ?? globalThis.document;
  const browser = options.window ?? globalThis.window;
  const later = options.setTimeout ?? globalThis.setTimeout;
  const cancel = options.clearTimeout ?? globalThis.clearTimeout;
  const refreshing = ref(false), lastUpdated = ref(null), refreshError = ref(null);
  let active = false, timer = null, inFlight = null, queued = false;
  const visible = () => page?.visibilityState !== 'hidden' && browser?.navigator?.onLine !== false;
  function clear() { if (timer !== null) cancel(timer); timer = null; }
  function schedule() {
    clear();
    if (active && visible()) timer = later(() => { timer = null; void refresh(); }, intervalMs);
  }
  function refresh() {
    if (inFlight) return inFlight;
    if (!active || !visible() || !enabled()) { schedule(); return Promise.resolve(); }
    clear();
    refreshing.value = true;
    refreshError.value = null;
    inFlight = Promise.resolve().then(callback).then(() => {
      if (active) lastUpdated.value = new Date();
    }).catch(error => {
      if (active) refreshError.value = error;
    }).finally(() => {
      inFlight = null;
      refreshing.value = false;
      if (queued && active && visible()) { queued = false; void refresh(); }
      else { queued = false; schedule(); }
    });
    return inFlight;
  }
  const wake = () => {
    if (!visible()) { queued = false; clear(); return; }
    if (inFlight) queued = true; else void refresh();
  };
  function start() {
    if (active) return;
    active = true;
    page?.addEventListener('visibilitychange', wake);
    browser?.addEventListener('focus', wake);
    browser?.addEventListener('online', wake);
    browser?.addEventListener('offline', wake);
    browser?.addEventListener('procurement:updated', wake);
    if (options.immediate === false) schedule(); else void refresh();
  }
  function stop() {
    active = false;
    queued = false;
    clear();
    page?.removeEventListener('visibilitychange', wake);
    browser?.removeEventListener('focus', wake);
    browser?.removeEventListener('online', wake);
    browser?.removeEventListener('offline', wake);
    browser?.removeEventListener('procurement:updated', wake);
  }
  return { refresh, refreshing, lastUpdated, refreshError, start, stop };
}

export function useLiveRefresh(callback, options = {}) {
  const controller = createLiveRefresh(callback, options);
  onMounted(controller.start);
  onBeforeUnmount(controller.stop);
  return { refresh: controller.refresh, refreshing: controller.refreshing, lastUpdated: controller.lastUpdated, refreshError: controller.refreshError };
}
