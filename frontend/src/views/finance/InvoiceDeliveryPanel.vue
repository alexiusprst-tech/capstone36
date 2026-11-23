<template>
  <section aria-labelledby="invoice-delivery-title" class="space-y-3 border-t border-slate-200 pt-4 text-xs">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h3 id="invoice-delivery-title" class="font-semibold text-sm text-slate-900">
        Pengiriman invoice ke customer
      </h3>
      <button type="button" class="delivery-action border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"
        :disabled="!!busy" @click="refresh">
        <RefreshCw aria-hidden="true" class="size-3.5" /> Muat ulang status
      </button>
    </div>

    <p v-if="errorMessage" role="alert" class="text-rose-700">{{ errorMessage }}</p>
    <p v-if="busy === 'load'" role="status" class="text-slate-600">Memuat status pengiriman…</p>
    <template v-if="delivery">
      <dl class="space-y-2">
        <div class="flex flex-wrap justify-between gap-x-3 gap-y-1">
          <dt class="text-slate-600">{{ delivery.defaultRecipient ? 'Penerima default invoice' : 'Email customer' }}</dt>
          <dd class="break-all font-medium text-slate-900">{{ delivery.recipient || 'Belum tersedia' }}</dd>
        </div>
        <div v-if="delivery.channel" class="flex flex-wrap justify-between gap-x-3 gap-y-1">
          <dt class="text-slate-600">Tujuan pengiriman</dt>
          <dd class="text-slate-900">{{ channels[delivery.channel] }}</dd>
        </div>
        <div class="flex flex-wrap justify-between gap-x-3 gap-y-1">
          <dt class="text-slate-600">Pengiriman terakhir</dt>
          <dd class="text-slate-900" aria-live="polite">{{ deliveryStatus }}</dd>
        </div>
        <div v-if="delivery.latest && delivery.latest.recipient !== delivery.recipient"
          class="flex flex-wrap justify-between gap-x-3 gap-y-1">
          <dt class="text-slate-600">Penerima terakhir</dt>
          <dd class="break-all text-slate-900">{{ delivery.latest.recipient }}</dd>
        </div>
        <div v-if="delivery.latest || delivery.active" class="flex flex-wrap justify-between gap-x-3 gap-y-1">
          <dt class="text-slate-600">Tautan customer</dt>
          <dd class="text-slate-900" aria-live="polite">{{ linkStatus }}</dd>
        </div>
      </dl>
      <p v-if="!delivery.recipient" class="text-amber-800">
        {{ delivery.defaultRecipient ? 'Periksa konfigurasi penerima default invoice.' : 'Lengkapi email pada data customer, lalu muat ulang status untuk mengirim invoice.' }}
      </p>
      <p v-else-if="delivery.active" class="text-slate-600">
        Setelah pengiriman ulang berhasil, customer perlu memakai tautan dari email terbaru.
      </p>
      <div class="flex flex-wrap gap-2">
        <button type="button" class="delivery-action bg-alpharo-600 text-white hover:bg-alpharo-700"
          :disabled="!!busy || !delivery.recipient || delivery.latest?.status === 'pending'" @click="changeDelivery('send')">
          <Send aria-hidden="true" class="size-3.5" />
          {{ busy === 'send' ? 'Mengirim…' : delivery.latest ? 'Kirim ulang invoice' : 'Kirim invoice' }}
        </button>
        <button v-if="delivery.active" type="button"
          class="delivery-action border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"
          :disabled="!!busy" @click="changeDelivery('revoke')">
          <Link2Off aria-hidden="true" class="size-3.5" />
          {{ busy === 'revoke' ? 'Mencabut…' : 'Cabut tautan' }}
        </button>
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Link2Off, RefreshCw, Send } from 'lucide-vue-next';
import api from '../../services/api';

const props = defineProps({ orderNumber: { type: String, required: true } });
const endpoint = computed(() => '/orders/' + encodeURIComponent(props.orderNumber) + '/invoice/delivery');
const delivery = ref(null);
const busy = ref('');
const errorMessage = ref('');
const channels = { mailtrap_demo: 'Inbox email penerima (uji Mailtrap)', mailtrap_sandbox: 'Inbox uji Mailtrap Sandbox', smtp: 'Server SMTP' };
const dateTime = new Intl.DateTimeFormat('id-ID', {
  timeZone: 'Asia/Jakarta', dateStyle: 'medium', timeStyle: 'short',
});

function timestamp(value) {
  const date = new Date(value);
  return value && Number.isFinite(date.getTime()) ? dateTime.format(date) + ' WIB' : '';
}

const deliveryStatus = computed(() => {
  const latest = delivery.value?.latest;
  if (!latest) return 'Belum dikirim';
  const statuses = { sent: 'Terkirim', failed: 'Gagal dikirim', pending: 'Sedang diproses' };
  return [statuses[latest.status], channels[latest.channel], timestamp(latest.createdAt)].filter(Boolean).join(' · ');
});

const linkStatus = computed(() => {
  if (!delivery.value.active) return 'Tidak aktif';
  const expiry = timestamp(delivery.value.expiresAt);
  return expiry ? 'Aktif hingga ' + expiry : 'Aktif';
});

async function loadDelivery() {
  const response = await api.get(endpoint.value);
  delivery.value = response.data.data;
}

async function refresh() {
  if (busy.value) return;
  busy.value = 'load';
  errorMessage.value = '';
  try {
    await loadDelivery();
  } catch {
    delivery.value = null;
    errorMessage.value = 'Status pengiriman belum dapat dimuat. Silakan muat ulang status.';
  } finally {
    busy.value = '';
  }
}

async function changeDelivery(action) {
  if (busy.value || !delivery.value) return;
  busy.value = action;
  errorMessage.value = '';
  try {
    const response = action === 'send' ? await api.post(endpoint.value) : await api.delete(endpoint.value);
    delivery.value = response.data.data;
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Permintaan belum dapat dikonfirmasi. Periksa status pengiriman.';
    if (error.response?.data?.data) {
      delivery.value = error.response.data.data;
    } else {
      try {
        await loadDelivery();
      } catch {
        delivery.value = null;
      }
    }
  } finally {
    busy.value = '';
  }
}

onMounted(refresh);
watch(() => props.orderNumber, () => {
  delivery.value = null;
  refresh();
});
watch(delivery, (current, previous, onCleanup) => {
  if (current?.latest?.status === 'pending') {
    const timer = window.setTimeout(refresh, 10000);
    onCleanup(() => window.clearTimeout(timer));
  }
});
</script>

<style scoped>
@reference "../../style.css";
.delivery-action {
  @apply inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg px-3 py-2 font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-alpharo-600 disabled:cursor-not-allowed disabled:opacity-50;
}
</style>
