<script setup>
import { computed } from 'vue';
import { presentShippingStatus } from '../../services/shippingStatus';
const props = defineProps({ status: { type: String, default: '' }, fallback: { type: String, default: 'Belum tersedia' } });
const presentation = computed(() => presentShippingStatus(props.status, props.fallback));
</script>
<template>
  <span class="inline-flex min-w-0 max-w-full items-start gap-1.5 leading-relaxed" :data-shipping-status="presentation.label">
    <span aria-hidden="true" data-testid="shipping-status-icon" class="shrink-0">{{ presentation.icon }}</span>
    <span class="min-w-0 break-words">{{ presentation.label }}</span>
  </span>
</template>
