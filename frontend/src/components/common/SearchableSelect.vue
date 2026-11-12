<template>
  <button
    ref="trigger"
    type="button"
    role="combobox"
    :aria-label="ariaLabel"
    :aria-expanded="open"
    :aria-controls="listId"
    aria-haspopup="listbox"
    :disabled="disabled"
    class="flex min-h-9 w-full min-w-0 items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-left text-xs leading-relaxed text-slate-800 outline-hidden focus:border-alpharo-500 focus:ring-2 focus:ring-alpharo-100 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400"
    @click="open ? close() : openMenu()"
    @keydown.down.prevent="openMenu()"
    @keydown.up.prevent="openMenu()"
  >
    <span class="min-w-0 whitespace-normal wrap-anywhere">{{ selected?.label || placeholder }}</span>
    <ChevronDown class="h-3.5 w-3.5 shrink-0 text-slate-400" aria-hidden="true" />
  </button>

  <Teleport to="body">
    <div v-if="open" ref="panel" :style="position" class="ui-scope fixed z-[100] flex min-h-0 flex-col overflow-hidden rounded-lg border border-slate-200 bg-white text-slate-800 shadow-lg">
      <div class="relative shrink-0 border-b border-slate-100 p-2">
        <Search class="pointer-events-none absolute top-1/2 left-4 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" aria-hidden="true" />
        <input
          ref="searchInput"
          v-model="query"
          type="search"
          role="searchbox"
          :aria-label="searchPlaceholder"
          :placeholder="searchPlaceholder"
          :aria-controls="listId"
          :aria-activedescendant="activeIndex >= 0 ? `${listId}-${activeIndex}` : undefined"
          autocomplete="off"
          class="min-h-9 w-full rounded-md border border-slate-300 bg-white py-2 pr-2 pl-8 text-xs outline-hidden focus:border-alpharo-500 focus:ring-2 focus:ring-alpharo-100"
          @keydown.down.prevent="move(1)"
          @keydown.up.prevent="move(-1)"
          @keydown.enter.prevent="chooseActive()"
          @keydown.esc.stop.prevent="close(true)"
          @keydown.tab="close(true)"
        />
      </div>
      <ul :id="listId" role="listbox" :aria-label="ariaLabel" class="min-h-0 flex-1 overflow-y-auto overscroll-auto py-1" @scroll="loadMore">
        <li
          v-for="(option, index) in visibleOptions"
          :id="`${listId}-${index}`"
          :key="option.value ?? 'empty'"
          role="option"
          :aria-selected="sameValue(option.value, modelValue)"
          :data-highlighted="index === activeIndex"
          :class="[index === activeIndex ? 'bg-alpharo-50 text-alpharo-900' : 'text-slate-800', 'flex cursor-pointer items-start gap-2 px-3 py-2 text-xs leading-relaxed']"
          @mousedown.prevent
          @mousemove="activeIndex = index"
          @click="choose(option)"
        >
          <span class="min-w-0 flex-1">
            <span class="block whitespace-normal wrap-anywhere">{{ option.label }}</span>
            <span v-if="option.description" class="mt-0.5 block whitespace-normal wrap-anywhere text-[11px] text-slate-500">{{ option.description }}</span>
          </span>
          <Check v-if="sameValue(option.value, modelValue)" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-alpharo-600" aria-hidden="true" />
        </li>
        <li v-if="!filteredOptions.length" role="presentation" class="px-3 py-6 text-center text-xs text-slate-500">Tidak ada pilihan yang cocok.</li>
      </ul>
      <p role="status" class="shrink-0 border-t border-slate-100 px-3 py-2 text-[11px] text-slate-500">
        {{ filteredOptions.length }} pilihan{{ visibleOptions.length < filteredOptions.length ? ' · Gulir untuk melihat lainnya atau persempit pencarian.' : '' }}
      </p>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';
import { Check, ChevronDown, Search } from 'lucide-vue-next';

const props = defineProps({
  modelValue: { type: [String, Number], default: null },
  options: { type: Array, default: () => [] },
  ariaLabel: { type: String, required: true },
  placeholder: { type: String, default: 'Pilih...' },
  searchPlaceholder: { type: String, default: 'Cari pilihan...' },
  disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'change']);
const listId = `searchable-select-${useId()}`;
const trigger = ref(null);
const panel = ref(null);
const searchInput = ref(null);
const open = ref(false);
const query = ref('');
const activeIndex = ref(-1);
const visibleCount = ref(100);
const position = ref({});
const sameValue = (a, b) => a == null || a === '' ? b == null || b === '' : String(a) === String(b);
const normalize = value => String(value || '').normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
const indexedOptions = computed(() => props.options.map(option => ({ option, text: normalize(`${option.label} ${option.description || ''} ${option.searchText || ''}`) })));
const selected = computed(() => props.options.find(option => sameValue(option.value, props.modelValue)));
const filteredOptions = computed(() => {
  const terms = normalize(query.value).trim().split(/\s+/).filter(Boolean);
  return indexedOptions.value.filter(({ text }) => terms.every(term => text.includes(term))).map(({ option }) => option);
});
const visibleOptions = computed(() => filteredOptions.value.slice(0, visibleCount.value));

function updatePosition() {
  if (!open.value || !trigger.value) return;
  const rect = trigger.value.getBoundingClientRect();
  const viewport = window.visualViewport;
  const leftEdge = viewport?.offsetLeft || 0;
  const topEdge = viewport?.offsetTop || 0;
  const viewportWidth = viewport?.width || window.innerWidth;
  const bottomEdge = topEdge + (viewport?.height || window.innerHeight);
  if (rect.bottom < topEdge || rect.top > bottomEdge || rect.right < leftEdge || rect.left > leftEdge + viewportWidth) {
    close();
    return;
  }
  const width = Math.min(Math.max(rect.width, 280), viewportWidth - 16);
  const below = bottomEdge - rect.bottom - 8;
  const above = rect.top - topEdge - 8;
  const upwards = below < 240 && above > below;
  const height = Math.min(360, Math.max(120, upwards ? above : below));
  position.value = {
    width: `${width}px`, maxHeight: `${height}px`,
    left: `${Math.max(leftEdge + 8, Math.min(rect.left, leftEdge + viewportWidth - width - 8))}px`,
    ...(upwards ? { bottom: `${window.innerHeight - rect.top + 4}px` } : { top: `${rect.bottom + 4}px` }),
  };
}

function onOutside(event) {
  if (!trigger.value?.contains(event.target) && !panel.value?.contains(event.target)) close();
}
function onScroll(event) {
  if (!panel.value?.contains(event.target)) updatePosition();
}
function removeListeners() {
  document.removeEventListener('pointerdown', onOutside, true);
  window.removeEventListener('scroll', onScroll, true);
  window.removeEventListener('resize', updatePosition);
  window.visualViewport?.removeEventListener('resize', updatePosition);
  window.visualViewport?.removeEventListener('scroll', updatePosition);
}
async function openMenu() {
  if (props.disabled || open.value) return;
  open.value = true;
  query.value = '';
  visibleCount.value = 100;
  const selectedIndex = filteredOptions.value.findIndex(option => sameValue(option.value, props.modelValue));
  activeIndex.value = selectedIndex >= 0 && selectedIndex < 100 ? selectedIndex : filteredOptions.value.length ? 0 : -1;
  updatePosition();
  document.addEventListener('pointerdown', onOutside, true);
  window.addEventListener('scroll', onScroll, true);
  window.addEventListener('resize', updatePosition);
  window.visualViewport?.addEventListener('resize', updatePosition);
  window.visualViewport?.addEventListener('scroll', updatePosition);
  await nextTick();
  searchInput.value?.focus({ preventScroll: true });
  panel.value?.querySelector('[data-highlighted="true"]')?.scrollIntoView({ block: 'nearest' });
}
function close(restoreFocus = false) {
  open.value = false;
  removeListeners();
  if (restoreFocus) trigger.value?.focus({ preventScroll: true });
}
function choose(option) {
  emit('update:modelValue', option.value);
  emit('change', option.value);
  close(true);
}
function chooseActive() {
  const option = filteredOptions.value[activeIndex.value];
  if (option) choose(option);
}
async function move(direction) {
  if (!filteredOptions.value.length) return;
  activeIndex.value = Math.max(0, Math.min(filteredOptions.value.length - 1, activeIndex.value + direction));
  visibleCount.value = Math.max(visibleCount.value, activeIndex.value + 1);
  await nextTick();
  panel.value?.querySelector('[data-highlighted="true"]')?.scrollIntoView({ block: 'nearest' });
}
function loadMore(event) {
  const list = event.target;
  if (list.scrollHeight - list.scrollTop - list.clientHeight < 48) visibleCount.value += 100;
}
watch(query, () => {
  visibleCount.value = 100;
  const selectedIndex = query.value ? -1 : filteredOptions.value.findIndex(option => sameValue(option.value, props.modelValue));
  activeIndex.value = selectedIndex >= 0 && selectedIndex < 100 ? selectedIndex : filteredOptions.value.length ? 0 : -1;
  const list = panel.value?.querySelector('[role="listbox"]');
  if (list) list.scrollTop = 0;
});
watch(() => props.disabled, disabled => { if (disabled) close(); });
onBeforeUnmount(removeListeners);
</script>
