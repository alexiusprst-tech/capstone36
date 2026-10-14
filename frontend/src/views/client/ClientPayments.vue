<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Status Pembayaran & Termin</h1>
          <p class="text-sm text-slate-500 mt-1">
            Rincian invoice tagihan, jadwal termin, dan riwayat konfirmasi pembayaran instansi Anda dengan CV Alpharo Artha Jaya.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
            @click="openPaymentModal(null)"
          >
            <UploadCloud class="w-4 h-4" />
            <span>Konfirmasi Pembayaran</span>
          </button>
        </div>
      </div>
    </template>

    <!-- BANK ACCOUNT & PAYMENT INSTRUCTIONS BANNER -->
    <div class="bg-gradient-to-r from-blue-900 via-[#0B4DA2] to-blue-800 rounded-2xl p-5 text-white shadow-md mb-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white uppercase tracking-wider mb-2">
            Rekening Resmi Perusahaan
          </span>
          <h3 class="text-base font-bold text-white">Pembayaran Ditujukan Kepada: CV Alpharo Artha Jaya</h3>
          <p class="text-xs text-blue-100 mt-0.5">
            Gunakan nomor rekening resmi berikut untuk seluruh transaksi pembayaran pesanan dan termin instansi Anda.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <div class="bg-white/10 backdrop-blur-xs rounded-xl p-3 border border-white/10">
            <p class="text-[10px] text-blue-200 font-bold uppercase tracking-wider">Bank BCA</p>
            <p class="font-mono text-sm font-bold text-white tracking-wider">829-019-2811</p>
            <p class="text-[10px] text-blue-200">a.n. CV Alpharo Artha Jaya</p>
          </div>
          <div class="bg-white/10 backdrop-blur-xs rounded-xl p-3 border border-white/10">
            <p class="text-[10px] text-blue-200 font-bold uppercase tracking-wider">Bank Mandiri</p>
            <p class="font-mono text-sm font-bold text-white tracking-wider">133-00-98218-09</p>
            <p class="text-[10px] text-blue-200">a.n. CV Alpharo Artha Jaya</p>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI SUMMARY -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <KpiCard title="Total Tagihan" :value="totalInvoiceValue" :is-currency="true" subtitle="Seluruh nilai invoice" icon-variant="slate" />
      <KpiCard title="Sudah Dibayar" :value="totalPaid" :is-currency="true" subtitle="Realisasi dana diterima" icon-variant="emerald" />
      <KpiCard title="Sisa Tagihan" :value="totalOutstanding" :is-currency="true" subtitle="Menunggu pelunasan" icon-variant="rose" />
      <KpiCard title="Invoice Lunas" :value="paidCount" subtitle="Tagihan tuntas 100%" icon-variant="blue" />
    </div>

    <!-- FILTER TABS -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <button
          v-for="f in filterTabs"
          :key="f.key"
          type="button"
          :class="[
            currentFilter === f.key
              ? 'bg-slate-900 text-white font-bold'
              : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200',
            'px-3.5 py-1.5 rounded-lg text-xs font-semibold shadow-2xs transition-all'
          ]"
          @click="currentFilter = f.key"
        >
          {{ f.label }}
        </button>
      </div>

      <span class="text-xs text-slate-500">
        Menampilkan {{ filteredInvoices.length }} berkas invoice
      </span>
    </div>

    <!-- INVOICE CARDS WITH TERMIN DETAIL -->
    <div class="space-y-6">
      <div
        v-for="inv in filteredInvoices"
        :key="inv.id"
        class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden"
      >
        <!-- Header invoice card -->
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="font-mono font-bold text-slate-900 text-sm">{{ inv.invoiceNumber }}</span>
              <StatusBadge :status="inv.status" />
            </div>
            <p class="text-xs text-slate-600">
              Order No: <span class="font-mono font-bold text-slate-800">{{ inv.orderNumber }}</span>
              &bull; Skema: <span class="font-semibold text-slate-800">{{ inv.paymentType || 'Termin' }} ({{ inv.terminCount || 3 }} Termin)</span>
            </p>
            <p class="text-xs text-slate-400 mt-0.5">
              Tanggal Terbit: {{ inv.invoiceDate }} &bull; Batas Jatuh Tempo: {{ inv.dueDate }}
            </p>
          </div>

          <div class="flex items-center gap-4">
            <div class="text-left sm:text-right">
              <p class="text-[10px] text-slate-400 uppercase font-semibold">Total Tagihan</p>
              <p class="text-base font-bold text-slate-900 font-mono">Rp {{ (inv.totalAmount || 0).toLocaleString('id-ID') }}</p>
              <p class="text-xs text-rose-600 font-semibold font-mono">
                Sisa: Rp {{ (inv.remainingAmount || 0).toLocaleString('id-ID') }}
              </p>
            </div>

            <button
              v-if="inv.remainingAmount > 0"
              type="button"
              class="px-3.5 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded-lg text-xs font-bold shadow-xs transition-colors shrink-0 flex items-center gap-1.5"
              @click="openPaymentModal(inv)"
            >
              <CreditCard class="w-4 h-4" />
              <span>Bayar / Konfirmasi</span>
            </button>
          </div>
        </div>

        <!-- Termin schedule table -->
        <div class="p-5">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
              Jadwal & Riwayat Termin Pembayaran
            </h4>
            <span class="text-xs text-slate-500">
              Progress: <strong class="text-emerald-600 font-mono">{{ paidPercentage(inv) }}% Lunas</strong>
            </span>
          </div>

          <!-- Progress bar -->
          <div class="h-2 bg-slate-100 rounded-full overflow-hidden mb-4">
            <div
              class="h-full bg-emerald-500 rounded-full transition-all duration-300"
              :style="{ width: paidPercentage(inv) + '%' }"
            ></div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                  <th class="py-2.5 px-3">Termin</th>
                  <th class="py-2.5 px-3 text-right">Jumlah Tagihan</th>
                  <th class="py-2.5 px-3">Batas Jatuh Tempo</th>
                  <th class="py-2.5 px-3">Metode Bayar</th>
                  <th class="py-2.5 px-3">Tanggal Pelunasan</th>
                  <th class="py-2.5 px-3 text-center">Status</th>
                  <th class="py-2.5 px-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr
                  v-for="termin in (inv.termins || [])"
                  :key="termin.termNumber"
                  class="hover:bg-slate-50/60 transition-colors"
                >
                  <td class="py-3 px-3 font-bold text-slate-900">
                    Termin Ke-{{ termin.termNumber }}
                  </td>
                  <td class="py-3 px-3 text-right font-mono font-semibold text-slate-900 whitespace-nowrap">
                    Rp {{ (termin.amount || 0).toLocaleString('id-ID') }}
                  </td>
                  <td class="py-3 px-3 text-slate-700 whitespace-nowrap">{{ termin.dueDate }}</td>
                  <td class="py-3 px-3 text-slate-600">{{ termin.method || '-' }}</td>
                  <td class="py-3 px-3 text-slate-600 whitespace-nowrap">{{ termin.paidDate || '-' }}</td>
                  <td class="py-3 px-3 text-center">
                    <StatusBadge :status="termin.status" />
                  </td>
                  <td class="py-3 px-3 text-right whitespace-nowrap">
                    <button
                      v-if="termin.status !== 'Lunas'"
                      type="button"
                      class="text-alpharo-500 hover:text-alpharo-600 font-bold hover:underline"
                      @click="openPaymentModal(inv, termin)"
                    >
                      Konfirmasi Bayar
                    </button>
                    <span v-else class="text-emerald-600 font-bold text-[11px] flex items-center justify-end gap-1">
                      <CheckCircle2 class="w-3.5 h-3.5" /> Lunas
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div
        v-if="filteredInvoices.length === 0"
        class="bg-white rounded-xl border border-slate-200/90 p-12 shadow-xs text-center text-slate-400 text-xs"
      >
        <Receipt class="w-12 h-12 text-slate-300 mx-auto mb-3" />
        <p class="font-bold text-slate-700 text-sm">Tidak ada invoice tagihan</p>
        <p class="mt-1">Belum ada invoice yang sesuai dengan kriteria filter Anda.</p>
      </div>
    </div>

    <!-- MODAL KONFIRMASI PEMBAYARAN -->
    <Modal
      :show="showPaymentModal"
      title="Konfirmasi Pembayaran Tagihan"
      size="md"
      @close="showPaymentModal = false"
    >
      <form class="space-y-4 text-xs" @submit.prevent="submitPaymentConfirmation">
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Pilih Invoice yang Dibayar</label>
          <select
            v-model="paymentForm.invoiceNumber"
            required
            class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white"
            @change="onModalInvoiceChange"
          >
            <option v-for="inv in clientInvoices" :key="inv.id" :value="inv.invoiceNumber">
              {{ inv.invoiceNumber }} - {{ inv.clientName }} (Sisa: Rp {{ (inv.remainingAmount || 0).toLocaleString('id-ID') }})
            </option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Skema Pembayaran</label>
            <select
              v-model="paymentForm.paymentType"
              class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white"
            >
              <option value="Termin">Pembayaran Termin</option>
              <option value="Penuh">Pelunasan Penuh</option>
            </select>
          </div>

          <div v-if="paymentForm.paymentType === 'Termin'">
            <label class="block font-semibold text-slate-700 mb-1">Termin Ke</label>
            <select
              v-model="paymentForm.terminNumber"
              class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white"
            >
              <option :value="1">Termin Ke-1</option>
              <option :value="2">Termin Ke-2</option>
              <option :value="3">Termin Ke-3</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Nominal yang Ditransfer (Rp)</label>
          <input
            v-model.number="paymentForm.amount"
            type="number"
            min="1000"
            required
            placeholder="Contoh: 15000000"
            class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono font-bold"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Metode Transfer Bank</label>
            <select
              v-model="paymentForm.paymentMethod"
              class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white"
            >
              <option value="Transfer BCA">Transfer BCA</option>
              <option value="Transfer Mandiri">Transfer Bank Mandiri</option>
              <option value="Transfer BNI">Transfer Bank BNI</option>
              <option value="SIPLah / Virtual Account">Sistem SIPLah / VA</option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Tanggal Transfer</label>
            <input
              v-model="paymentForm.paymentDate"
              type="date"
              required
              class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white"
            />
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">No. Referensi / Ref Struk Bank</label>
          <input
            v-model="paymentForm.referenceNumber"
            type="text"
            required
            placeholder="Contoh: TRF-BCA-889102"
            class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono"
          />
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Unggah Bukti Transfer (File Foto/PDF)</label>
          <div class="border-2 border-dashed border-slate-300 rounded-lg p-3 text-center bg-slate-50">
            <UploadCloud class="w-6 h-6 text-slate-400 mx-auto mb-1" />
            <p class="text-[11px] text-slate-600 font-medium">Struk_Transfer_SMA9.pdf (Simulasi Dokumen Terpilih)</p>
            <p class="text-[10px] text-slate-400">Format: JPG, PNG, atau PDF max 5MB</p>
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
          <textarea
            v-model="paymentForm.notes"
            rows="2"
            placeholder="Catatan dari bendahara sekolah..."
            class="w-full px-3 py-2 rounded-lg border border-slate-300"
          ></textarea>
        </div>

        <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
          <button
            type="button"
            class="px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold"
            @click="showPaymentModal = false"
          >
            Batal
          </button>
          <button
            type="submit"
            class="px-4 py-2 rounded-lg bg-alpharo-500 hover:bg-alpharo-600 text-white font-bold shadow-xs"
          >
            Kirim Konfirmasi
          </button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import KpiCard from '../../components/common/KpiCard.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import Modal from '../../components/common/Modal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import {
  CreditCard,
  UploadCloud,
  CheckCircle2,
  Receipt,
} from 'lucide-vue-next';

const toast = useToast();

const clientId = computed(() => store.currentUser.clientId || 1);

const clientInvoices = computed(() => {
  return store.invoices.filter((inv) => inv.clientId === clientId.value);
});

const currentFilter = ref('all');

const filterTabs = [
  { key: 'all', label: 'Semua Invoice' },
  { key: 'unpaid', label: 'Belum Lunas' },
  { key: 'paid', label: 'Lunas' },
];

const filteredInvoices = computed(() => {
  if (currentFilter.value === 'unpaid') {
    return clientInvoices.value.filter((inv) => inv.status !== 'Lunas');
  }
  if (currentFilter.value === 'paid') {
    return clientInvoices.value.filter((inv) => inv.status === 'Lunas');
  }
  return clientInvoices.value;
});

const totalInvoiceValue = computed(() =>
  clientInvoices.value.reduce((sum, inv) => sum + (inv.totalAmount || 0), 0)
);

const totalPaid = computed(() =>
  clientInvoices.value.reduce((sum, inv) => sum + (inv.paidAmount || 0), 0)
);

const totalOutstanding = computed(() =>
  clientInvoices.value.reduce((sum, inv) => sum + (inv.remainingAmount || 0), 0)
);

const paidCount = computed(() => clientInvoices.value.filter((inv) => inv.status === 'Lunas').length);

const paidPercentage = (inv) => {
  if (!inv.totalAmount) return 0;
  return Math.min(100, Math.round(((inv.paidAmount || 0) / inv.totalAmount) * 100));
};

// Payment Confirmation Modal
const showPaymentModal = ref(false);
const paymentForm = ref({
  invoiceNumber: '',
  paymentType: 'Termin',
  terminNumber: 1,
  amount: 0,
  paymentMethod: 'Transfer BCA',
  paymentDate: new Date().toISOString().split('T')[0],
  referenceNumber: `TRF-${Math.floor(100000 + Math.random() * 900000)}`,
  notes: '',
  proofFile: 'BUKTI_TRANSFER_SMA.pdf',
});

const openPaymentModal = (inv = null, termin = null) => {
  const targetInv = inv || clientInvoices.value[0];
  if (!targetInv) {
    toast.error('Belum ada data invoice untuk dikonfirmasi.');
    return;
  }

  paymentForm.value.invoiceNumber = targetInv.invoiceNumber;
  if (termin) {
    paymentForm.value.paymentType = 'Termin';
    paymentForm.value.terminNumber = termin.termNumber;
    paymentForm.value.amount = termin.amount || targetInv.remainingAmount;
  } else {
    paymentForm.value.amount = targetInv.remainingAmount || targetInv.totalAmount;
    paymentForm.value.paymentType = targetInv.remainingAmount === targetInv.totalAmount ? 'Termin' : 'Penuh';
  }

  paymentForm.value.referenceNumber = `TRF-${Math.floor(100000 + Math.random() * 900000)}`;
  paymentForm.value.paymentDate = new Date().toISOString().split('T')[0];
  showPaymentModal.value = true;
};

const onModalInvoiceChange = () => {
  const inv = clientInvoices.value.find((i) => i.invoiceNumber === paymentForm.value.invoiceNumber);
  if (inv) {
    paymentForm.value.amount = inv.remainingAmount || inv.totalAmount;
  }
};

const submitPaymentConfirmation = () => {
  const inv = clientInvoices.value.find((i) => i.invoiceNumber === paymentForm.value.invoiceNumber);
  if (!inv) {
    toast.error('Invoice tidak ditemukan.');
    return;
  }

  // Call store action to record payment
  storeActions.recordPayment({
    invoiceId: inv.id,
    amount: paymentForm.value.amount,
    paymentDate: paymentForm.value.paymentDate,
    paymentMethod: paymentForm.value.paymentMethod,
    referenceNumber: paymentForm.value.referenceNumber,
    paymentType: paymentForm.value.paymentType,
    terminNumber: paymentForm.value.terminNumber,
    notes: paymentForm.value.notes,
    proofFile: paymentForm.value.proofFile,
  });

  // Also add client confirmation notification to Finance
  storeActions.addNotification(
    'FINANCE',
    'Konfirmasi Pembayaran Rekanan',
    `${store.currentUser.name} telah mengirimkan konfirmasi pembayaran Rp ${paymentForm.value.amount.toLocaleString('id-ID')} untuk ${inv.invoiceNumber}.`,
    'payment',
    '/finance/payments'
  );

  toast.success('Konfirmasi pembayaran berhasil dikirim ke bagian Keuangan Alpharo!');
  showPaymentModal.value = false;
};
</script>
