<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard Keuangan & Finansial</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pengelolaan invoice penagihan, arus kas pembayaran penuh/termin, dan rekonsiliasi piutang usaha.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <router-link
            to="/finance/payments"
            class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
          >
            <CreditCard class="w-4 h-4" />
            <span>Pencatatan Pembayaran</span>
          </router-link>
        </div>
      </div>
    </template>

    <!-- KPIS FOR FINANCE: STATUS OPERASIONAL & METRIK KEUANGAN -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
      <KpiCard title="Total Invoice" :value="store.invoices.length" subtitle="Semua berkas tagihan" icon-variant="slate" />
      <KpiCard title="Belum Dibayar" :value="unpaidCount" subtitle="Menunggu pencairan" icon-variant="amber" />
      <KpiCard title="Jatuh Tempo" :value="overdueCount" subtitle="Perlu follow-up segera" icon-variant="rose" />
      <KpiCard title="Invoice Lunas" :value="paidCount" subtitle="Pembayaran selesai" icon-variant="emerald" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <KpiCard title="Dibayar Sebagian" :value="partialCount" subtitle="Termin sedang berjalan" icon-variant="blue" />
      <KpiCard title="Total Nilai Invoice" :value="totalInvoiceValue" :is-currency="true" subtitle="Akumulasi tagihan" icon-variant="blue" />
      <KpiCard title="Total Penerimaan" :value="totalPaymentValue" :is-currency="true" subtitle="Realisasi dana masuk" icon-variant="emerald" />
      <KpiCard title="Total Piutang" :value="totalReceivableValue" :is-currency="true" subtitle="Sisa tagihan belum cair" icon-variant="rose" />
    </div>

    <!-- MAIN TWO-COLUMN DASHBOARD SECTIONS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- LEFT 2 COLS: SECTION A (INVOICE TERBARU) & SECTION B (PAYMENT TERBARU) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- SECTION A: INVOICE TERBARU -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-bold text-slate-900 text-sm">Invoice Tagihan Terbaru</h3>
              <p class="text-xs text-slate-500">Daftar berkas tagihan yang diterbitkan ke rekanan kontraktor</p>
            </div>
            <router-link to="/finance/invoices" class="text-xs font-bold text-alpharo-500 hover:underline flex items-center gap-1">
              Semua Invoice <ChevronRight class="w-3.5 h-3.5" />
            </router-link>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                  <th class="py-2.5 px-3">No. Invoice</th>
                  <th class="py-2.5 px-3">Client</th>
                  <th class="py-2.5 px-3 text-right">Nilai Total</th>
                  <th class="py-2.5 px-3 text-right">Sisa Tagihan</th>
                  <th class="py-2.5 px-3 text-center">Status</th>
                  <th class="py-2.5 px-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="inv in store.invoices.slice(0, 4)" :key="inv.id" class="hover:bg-slate-50/60 transition-colors">
                  <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ inv.invoiceNumber }}</td>
                  <td class="py-3 px-3 font-medium text-slate-800">{{ inv.clientName }}</td>
                  <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                    Rp {{ inv.totalAmount.toLocaleString('id-ID') }}
                  </td>
                  <td class="py-3 px-3 text-right font-mono text-rose-600 font-semibold whitespace-nowrap">
                    Rp {{ inv.remainingAmount.toLocaleString('id-ID') }}
                  </td>
                  <td class="py-3 px-3 text-center"><StatusBadge :status="inv.status" /></td>
                  <td class="py-3 px-3 text-right whitespace-nowrap">
                    <router-link
                      to="/finance/payments"
                      class="px-2.5 py-1 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded font-bold text-[11px]"
                    >
                      Bayar
                    </router-link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- SECTION B: PAYMENT TERBARU -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-bold text-slate-900 text-sm">Penerimaan Pembayaran Terbaru</h3>
              <p class="text-xs text-slate-500">Riwayat transfer pembayaran (Penuh maupun Termin) yang telah dicatat</p>
            </div>
            <router-link to="/finance/payments" class="text-xs font-bold text-alpharo-500 hover:underline flex items-center gap-1">
              Semua Pembayaran <ChevronRight class="w-3.5 h-3.5" />
            </router-link>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                  <th class="py-2.5 px-3">No. Kwitansi</th>
                  <th class="py-2.5 px-3">Client</th>
                  <th class="py-2.5 px-3">Jenis Bayar</th>
                  <th class="py-2.5 px-3 text-right">Jumlah Diterima</th>
                  <th class="py-2.5 px-3">Metode</th>
                  <th class="py-2.5 px-3 text-center">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="pay in store.payments.slice(0, 4)" :key="pay.id" class="hover:bg-slate-50/60 transition-colors">
                  <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ pay.paymentNumber }}</td>
                  <td class="py-3 px-3 font-medium text-slate-800">{{ pay.clientName }}</td>
                  <td class="py-3 px-3">
                    <span class="font-medium text-slate-800">{{ pay.paymentType }}</span>
                    <span class="block text-[10px] text-slate-400">{{ pay.terminSequence }}</span>
                  </td>
                  <td class="py-3 px-3 text-right font-mono font-bold text-emerald-600 whitespace-nowrap">
                    Rp {{ pay.amount.toLocaleString('id-ID') }}
                  </td>
                  <td class="py-3 px-3 text-slate-600">{{ pay.paymentMethod }}</td>
                  <td class="py-3 px-3 text-center"><StatusBadge status="Lunas" /></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- RIGHT 1 COL: C. OUTSTANDING RECEIVABLES & D. DUE INVOICES -->
      <div class="space-y-6">
        <!-- D. DUE & OVERDUE INVOICES -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
            <div class="flex items-center gap-2">
              <AlertCircle class="w-4 h-4 text-rose-500" />
              <h3 class="font-bold text-slate-900 text-sm">Invoice Mendekati / Jatuh Tempo</h3>
            </div>
            <router-link to="/finance/receivables" class="text-xs text-alpharo-500 font-bold hover:underline">
              Piutang →
            </router-link>
          </div>

          <div class="space-y-3 text-xs">
            <div
              v-for="inv in dueInvoices"
              :key="inv.id"
              class="p-2.5 rounded-lg border border-rose-100 bg-rose-50/40 flex items-center justify-between"
            >
              <div>
                <span class="font-mono font-bold text-slate-900 block">{{ inv.invoiceNumber }}</span>
                <span class="font-medium text-slate-800">{{ inv.clientName }}</span>
                <span class="block text-[10px] text-rose-600 mt-0.5">Jatuh Tempo: {{ inv.dueDate }}</span>
              </div>
              <div class="text-right">
                <span class="font-mono font-bold text-slate-900 block">
                  Rp {{ inv.remainingAmount.toLocaleString('id-ID') }}
                </span>
                <StatusBadge :status="inv.status" />
              </div>
            </div>

            <div v-if="dueInvoices.length === 0" class="p-4 text-center text-slate-400">
              ✓ Tidak ada invoice yang melewati masa jatuh tempo.
            </div>
          </div>
        </div>

        <!-- C. OUTSTANDING RECEIVABLES SUMMARY -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <h3 class="font-bold text-slate-900 text-sm mb-3 pb-2 border-b border-slate-100">
            Ringkasan Tagihan Piutang Mitra
          </h3>

          <div class="space-y-3 text-xs">
            <div
              v-for="inv in store.invoices.filter(i => i.remainingAmount > 0)"
              :key="inv.id"
              class="p-2.5 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-between"
            >
              <div>
                <span class="font-bold text-slate-900 block">{{ inv.clientName }}</span>
                <span class="font-mono text-[10px] text-slate-400">{{ inv.invoiceNumber }} &bull; {{ inv.orderNumber }}</span>
              </div>
              <div class="text-right">
                <span class="font-mono font-bold text-rose-600 block">
                  Rp {{ inv.remainingAmount.toLocaleString('id-ID') }}
                </span>
                <span class="text-[10px] text-slate-400">Dari Rp {{ inv.totalAmount.toLocaleString('id-ID') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import KpiCard from '../../components/common/KpiCard.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import { store } from '../../stores/mockData';
import { ChevronRight, AlertCircle, CreditCard } from 'lucide-vue-next';

const unpaidCount = computed(() => store.invoices.filter((i) => i.status === 'Belum Dibayar').length);
const overdueCount = computed(() => store.invoices.filter((i) => i.status === 'Jatuh Tempo').length);
const partialCount = computed(() => store.invoices.filter((i) => i.status === 'Sebagian / Termin').length);
const paidCount = computed(() => store.invoices.filter((i) => i.status === 'Lunas').length);

const totalInvoiceValue = computed(() => store.invoices.reduce((sum, i) => sum + i.totalAmount, 0));
const totalPaymentValue = computed(() => store.payments.reduce((sum, p) => sum + p.amount, 0));
const totalReceivableValue = computed(() => store.invoices.reduce((sum, i) => sum + i.remainingAmount, 0));

const dueInvoices = computed(() => store.invoices.filter((i) => i.status === 'Jatuh Tempo' || (i.status === 'Sebagian / Termin' && i.remainingAmount > 0)));
</script>
