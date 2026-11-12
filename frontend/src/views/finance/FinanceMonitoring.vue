<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Monitoring Arus Keuangan & Tagihan</h1>
          <p class="text-sm text-slate-500 mt-1">
            Filter status keuangan: Belum Dibayar, Dibayar Sebagian, Lunas, dan Jatuh Tempo.
          </p>
        </div>
      </div>
    </template>

    <!-- FILTER BUTTONS -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-5">
      <button
        v-for="st in ['Semua', 'Belum Dibayar', 'Sebagian', 'Lunas', 'Jatuh Tempo']"
        :key="st"
        type="button"
        :class="[
          activeFilter === st
            ? 'bg-alpharo-500 text-white font-semibold shadow-xs'
            : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200',
          'px-4 py-2 rounded-xl text-xs whitespace-nowrap transition-all'
        ]"
        @click="activeFilter = st"
      >
        {{ st }}
      </button>
    </div>

    <!-- MONITORING TABLE -->
    <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="ui-table-wide ui-table w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">No. Invoice</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4 text-right">Total Tagihan</th>
              <th class="py-3 px-4 text-right">Terbayar</th>
              <th class="py-3 px-4 text-right">Sisa Piutang</th>
              <th class="py-3 px-4">Jatuh Tempo</th>
              <th class="py-3 px-4 text-center">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="inv in filteredList" :key="inv.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-mono font-semibold text-slate-900">{{ inv.invoiceNumber }}</td>
              <td class="py-3.5 px-4 font-semibold text-slate-800">{{ inv.clientName }}</td>
              <td class="py-3.5 px-4 text-right font-mono font-semibold text-slate-900">Rp {{ Number(inv.totalAmount || 0).toLocaleString('id-ID') }}</td>
              <td class="py-3.5 px-4 text-right font-mono text-emerald-600 font-semibold">Rp {{ Number(inv.paidAmount || 0).toLocaleString('id-ID') }}</td>
              <td class="py-3.5 px-4 text-right font-mono text-rose-600 font-semibold">Rp {{ Number(inv.remainingAmount || 0).toLocaleString('id-ID') }}</td>
              <td class="py-3.5 px-4 text-slate-500">{{ inv.dueDate }}</td>
              <td class="py-3.5 px-4 text-center"><StatusBadge :status="inv.status" /></td>
            </tr>
            <tr v-if="filteredList.length === 0">
              <td colspan="7" class="py-12 text-center text-slate-400">
                <div class="ui-empty-state">
                  Belum ada data monitoring tagihan untuk status "{{ activeFilter }}".
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import { store } from '../../stores/mockData';

const activeFilter = ref('Semua');

const filteredList = computed(() => {
  if (activeFilter.value === 'Semua') return store.invoices;
  return store.invoices.filter((i) => i.status === activeFilter.value);
});
</script>
