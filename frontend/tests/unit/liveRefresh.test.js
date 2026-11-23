import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createLiveRefresh } from '../../src/composables/useLiveRefresh.js';

class Target {
  listeners = new Map();
  addEventListener(type, handler) { if (!this.listeners.has(type)) this.listeners.set(type, new Set()); this.listeners.get(type).add(handler); }
  removeEventListener(type, handler) { this.listeners.get(type)?.delete(handler); }
  emit(type) { for (const handler of this.listeners.get(type) || []) handler(); }
  count() { return [...this.listeners.values()].reduce((sum, handlers) => sum + handlers.size, 0); }
}
const flush = async () => { for (let step = 0; step < 20; step++) await Promise.resolve(); };
function setup(callback, options = {}) {
  const page = Object.assign(new Target(), { visibilityState: 'visible' });
  const browser = Object.assign(new Target(), { navigator: { onLine: true } });
  const timers = new Map();
  let sequence = 0;
  const controller = createLiveRefresh(callback, { document: page, window: browser, setTimeout(fn, ms) { assert.equal(ms, 10000); timers.set(++sequence, fn); return sequence; }, clearTimeout(id) { timers.delete(id); }, ...options });
  function tick() { const first = timers.entries().next().value; if (first) { timers.delete(first[0]); first[1](); } }
  return { controller, page, browser, timers, tick };
}

test('live refresh performs initial fetch then polls after completion with no overlapping requests', async () => {
  let calls = 0, release;
  const state = setup(() => { calls++; return new Promise(resolve => { release = resolve; }); });
  state.controller.start(); await flush();
  assert.equal(calls, 1); assert.equal(state.controller.refreshing.value, true); assert.equal(state.timers.size, 0);
  const first = state.controller.refresh(); const second = state.controller.refresh();
  assert.equal(first, second); assert.equal(calls, 1);
  release(); await first;
  assert.equal(state.controller.refreshing.value, false); assert.equal(state.timers.size, 1);
  state.tick(); await flush(); assert.equal(calls, 2); assert.equal(state.timers.size, 0);
  state.controller.stop(); release(); await flush(); assert.equal(state.timers.size, 0);
});

test('hidden or offline pages hold no polling timer and refresh on visibility or reconnect', async () => {
  let calls = 0;
  const state = setup(async () => { calls++; });
  state.controller.start(); await flush(); assert.equal(calls, 1); assert.equal(state.timers.size, 1);
  state.page.visibilityState = 'hidden'; state.page.emit('visibilitychange'); assert.equal(state.timers.size, 0);
  state.browser.emit('focus'); state.browser.emit('procurement:updated'); await flush(); assert.equal(calls, 1);
  state.page.visibilityState = 'visible'; state.page.emit('visibilitychange'); await flush(); assert.equal(calls, 2);
  state.browser.navigator.onLine = false; state.browser.emit('offline'); assert.equal(state.timers.size, 0);
  state.browser.emit('focus'); await flush(); assert.equal(calls, 2);
  state.browser.navigator.onLine = true; state.browser.emit('online'); await flush(); assert.equal(calls, 3);
  state.controller.stop(); assert.equal(state.timers.size, 0); assert.equal(state.page.count() + state.browser.count(), 0);
});

test('mutation and focus events during a request queue one fresh read without overlap', async () => {
  let calls = 0, release;
  const state = setup(() => { calls++; if (calls === 1) return new Promise(resolve => { release = resolve; }); return Promise.resolve(); });
  state.controller.start(); await flush();
  state.browser.emit('procurement:updated'); state.browser.emit('focus'); state.browser.emit('procurement:updated');
  await flush(); assert.equal(calls, 1);
  release(); await flush(); assert.equal(calls, 2); assert.equal(state.timers.size, 1);
  state.controller.stop();
});

test('stop cancels queued work and all listeners even while a request is pending', async () => {
  let calls = 0, release;
  const state = setup(() => { calls++; return new Promise(resolve => { release = resolve; }); });
  state.controller.start(); await flush(); state.browser.emit('procurement:updated');
  state.controller.stop(); release(); await flush(); state.browser.emit('focus'); state.page.emit('visibilitychange');
  assert.equal(calls, 1); assert.equal(state.timers.size, 0); assert.equal(state.page.count() + state.browser.count(), 0); assert.equal(state.controller.lastUpdated.value, null);
});

test('failed refresh retains last successful update and recovers on subsequent events', async () => {
  let fail = false;
  const failure = new Error('Network unavailable');
  const state = setup(async () => { if (fail) throw failure; });
  state.controller.start(); await flush(); const previous = state.controller.lastUpdated.value; assert.ok(previous instanceof Date);
  fail = true; await state.controller.refresh(); assert.equal(state.controller.lastUpdated.value, previous); assert.equal(state.controller.refreshError.value, failure); assert.equal(state.timers.size, 1);
  fail = false; state.browser.emit('procurement:updated'); await flush(); assert.equal(state.controller.refreshError.value, null); assert.ok(state.controller.lastUpdated.value instanceof Date);
  state.controller.stop();
});

test('busy guard prevents polling until enabled and immediate false does not fetch on mount', async () => {
  let calls = 0, enabled = false;
  const state = setup(async () => { calls++; }, { enabled: () => enabled, immediate: false });
  state.controller.start(); await flush(); assert.equal(calls, 0); assert.equal(state.timers.size, 1);
  state.tick(); await flush(); assert.equal(calls, 0); assert.equal(state.timers.size, 1);
  enabled = true; state.tick(); await flush(); assert.equal(calls, 1); assert.equal(state.timers.size, 1);
  state.controller.stop();
});
