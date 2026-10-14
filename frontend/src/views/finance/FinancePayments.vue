<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manajemen Pembayaran (Penuh & Termin)</h1>
          <p class="text-sm text-slate-500 mt-1">
            Modul terpadu seluruh transaksi penerimaan kas: Pembayaran Penuh (Full Payment) & Pembayaran Termin Bertahap.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
          @click="openInputPaymentModal"
        >
          <Plus class="w-4 h-4" />
          <span>Input Pembayaran Baru</span>
        </button>
      </div>
    </template>

    <!-- PAYMENTS TABLE -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-900 uppercase">Daftar Penerimaan Pembayaran</span>
        <span class="text-slate-500">{{ store.payments.length }} Transaksi Tercatat</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">No. Pembayaran</th>
              <th class="py-3 px-4">No. Invoice</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">Jenis Pembayaran</th>
              <th class="py-3 px-4 text-right">Nominal Diterima</th>
              <th class="py-3 px-4">Tanggal Bayar</th>
              <th class="py-3 px-4">Metode Bayar</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="pay in store.payments" :key="pay.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ pay.paymentNumber }}</td>
              <td class="py-3.5 px-4 font-mono text-slate-600">{{ pay.invoiceNumber }}</td>
              <td class="py-3.5 px-4 font-bold text-slate-800">{{ pay.clientName }}</td>
              <td class="py-3.5 px-4">
                <span class="font-semibold text-slate-900 block">{{ pay.paymentType }}</span>
                <span class="text-[11px] text-slate-400 block">{{ pay.terminSequence }}</span>
              </td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-600 whitespace-nowrap">
                Rp {{ pay.amount.toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ pay.paymentDate }}</td>
              <td class="py-3.5 px-4 text-slate-700">{{ pay.paymentMethod }}</td>
              <td class="py-3.5 px-4 text-center"><StatusBadge :status="pay.status" /></td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <button
                  type="button"
                  class="px-2.5 py-1 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded font-bold text-xs"
                  @click="openPaymentDetail(pay)"
                >
                  Detail & Termin
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- INPUT PAYMENT MODAL (FULL / TERMIN) -->
    <Modal v-model="inputModalOpen" title="Input Pembayaran Masuk" subtitle="Catat pelunasan penuh atau cicilan termin kontraktor" size="md">
      <form class="space-y-4 text-xs" @submit.prevent="savePayment">
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Pilih Invoice Tagihan</label>
          <select v-model="form.invoiceNumber" required class="w-full px-3 py-2 border rounded-lg bg-white" @change="onInvoiceSelect">
            <option v-for="inv in unpaidInvoices" :key="inv.invoiceNumber" :value="inv.invoiceNumber">
              {{ inv.invoiceNumber }} - {{ inv.clientName }} (Sisa: Rp {{ inv.remainingAmount.toLocaleString('id-ID') }})
            </option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Jenis Pembayaran</label>
            <select v-model="form.paymentType" class="w-full px-3 py-2 border rounded-lg bg-white">
              <option value="Penuh">Pembayaran Penuh</option>
              <option value="Termin">Pembayaran Termin</option>
            </select>
          </div>
          <div v-if="form.paymentType === 'Termin'">
            <label class="block font-bold text-slate-700 uppercase mb-1">Termin Ke-</label>
            <select v-model.number="form.terminNumber" class="w-full px-3 py-2 border rounded-lg bg-white">
              <option :value="1">Termin Ke-1</option>
              <option :value="2">Termin Ke-2</option>
              <option :value="3">Termin Ke-3</option>
              <option :value="4">Termin Ke-4</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Nominal Pembayaran (Rp)</label>
            <input v-model.number="form.amount" type="number" min="1" required class="w-full px-3 py-2 border rounded-lg text-right font-mono" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Tanggal Pembayaran</label>
            <input v-model="form.paymentDate" type="date" required class="w-full px-3 py-2 border rounded-lg bg-white" />
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Metode Pembayaran</label>
          <select v-model="form.paymentMethod" class="w-full px-3 py-2 border rounded-lg bg-white">
            <option>Transfer Bank BCA</option>
            <option>Transfer Bank Mandiri</option>
            <option>Transfer BNI</option>
            <option>SP2D Bank Jatim</option>
            <option>Bilyet Giro (BG)</option>
            <option>Tunai Kasir</option>
          </select>
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Upload Bukti Transfer / Rekening Koran</label>
          <input type="file" class="w-full px-3 py-2 border rounded-lg bg-slate-50" />
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button type="button" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg" @click="inputModalOpen = false">Batal</button>
          <button type="submit" class="px-4 py-1.5 bg-alpharo-500 text-white font-bold rounded-lg">Konfirmasi Pembayaran</button>
        </div>
      </form>
    </Modal>

    <!-- PAYMENT & TERMIN DETAIL DRAWER (EXACTLY AS SPECIFIED IN USER PROMPT) -->
    <Drawer v-model="detailDrawerOpen" :title="`Rincian Pembayaran: ${selectedPayment?.paymentNumber || ''}`" :subtitle="selectedPayment?.clientName || ''" size="md">
      <div v-if="selectedPayment" class="space-y-6 text-xs">
        <!-- Overview Card -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
          <div class="flex justify-between">
            <span class="text-slate-500">Invoice Terkait:</span>
            <span class="font-mono font-bold text-slate-900">{{ selectedPayment.invoiceNumber }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Rekanan Client:</span>
            <span class="font-bold text-slate-900">{{ selectedPayment.clientName }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Total Nilai Tagihan:</span>
            <span class="font-mono font-bold text-slate-900">Rp {{ selectedPayment.totalInvoice.toLocaleString('id-ID') }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Nominal Pembayaran Ini:</span>
            <span class="font-mono font-bold text-emerald-600">Rp {{ selectedPayment.amount.toLocaleString('id-ID') }}</span>
          </div>
          <div class="flex justify-between pt-2 border-t border-slate-200">
            <span class="text-slate-500">Metode Bayar:</span>
            <span class="font-medium text-slate-800">{{ selectedPayment.paymentMethod }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">No. Referensi:</span>
            <span class="font-mono text-slate-700">{{ selectedPayment.referenceNumber }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Bukti Pembayaran:</span>
            <span class="text-alpharo-500 font-semibold cursor-pointer hover:underline">{{ selectedPayment.proofFile }}</span>
          </div>
        </div>

        <!-- TERMIN PROGRESS VISUALIZATION (SPECIFIED BY USER PROMPT) -->
        <div v-if="relatedInvoice" class="p-4 bg-white rounded-xl border border-slate-200 space-y-3">
          <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <h4 class="font-bold uppercase tracking-wider text-slate-800">
              Payment Progress (Jadwal Termin)
            </h4>
            <StatusBadge :status="relatedInvoice.status" />
          </div>

          <div class="space-y-2">
            <div
              v-for="term in relatedInvoice.termins"
              :key="term.termNumber"
              class="p-2.5 rounded-lg border border-slate-100 bg-slate-50 flex items-center justify-between"
            >
              <div>
                <span class="font-bold text-slate-900 block">Termin {{ term.termNumber }}</span>
                <span class="font-mono text-slate-600 text-xs">Rp {{ term.amount.toLocaleString('id-ID') }}</span>
              </div>
              <StatusBadge :status="term.status" />
            </div>
          </div>

          <div class="pt-3 border-t border-slate-200 space-y-1.5 text-xs">
            <div class="flex justify-between text-slate-700">
              <span>Total Paid:</span>
              <span class="font-mono font-bold text-emerald-600">Rp {{ relatedInvoice.paidAmount.toLocaleString('id-ID') }}</span>
            </div>
            <div class="flex justify-between text-slate-700">
              <span>Remaining:</span>
              <span class="font-mono font-bold text-rose-600">Rp {{ relatedInvoice.remainingAmount.toLocaleString('id-ID') }}</span>
            </div>
            <div class="flex justify-between font-bold text-slate-900 pt-1">
              <span>Status Invoice:</span>
              <span :class="relatedInvoice.status === 'Lunas' ? 'text-emerald-600' : 'text-amber-600'">
                {{ relatedInvoice.status }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </Drawer>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import Modal from '../../components/common/Modal.vue';
import Drawer from '../../components/common/Drawer.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import { Plus } from 'lucide-vue-next';

const toast = useToast();

const inputModalOpen = ref(false);
const detailDrawerOpen = ref(false);
const selectedPayment = ref(null);

const unpaidInvoices = computed(() => store.invoices.filter((i) => i.status !== 'Lunas'));

const form = ref({
  invoiceNumber: '',
  paymentType: 'Termin',
  terminNumber: 1,
  amount: 25000000,
  paymentDate: new Date().toISOString().split('T')[0],
  paymentMethod: 'Transfer Bank BCA',
});

const relatedInvoice = computed(() => {
  if (!selectedPayment.value) return null;
  return store.invoices.find((i) => i.invoiceNumber === selectedPayment.value.invoiceNumber);
});

const openInputPaymentModal = () => {
  const inv = unpaidInvoices.value[0];
  form.value = {
    invoiceNumber: inv ? inv.invoiceNumber : 'INV-2026-001',
    paymentType: 'Termin',
    terminNumber: 1,
    amount: inv ? Math.round(inv.remainingAmount / 2) : 10000000,
    paymentDate: new Date().toISOString().split('T')[0],
    paymentMethod: 'Transfer Bank BCA',
  };
  inputModalOpen.value = true;
};

const onInvoiceSelect = () => {
  const inv = store.invoices.find((i) => i.invoiceNumber === form.value.invoiceNumber);
  if (inv) {
    form.value.amount = Math.round(inv.remainingAmount / 2) || inv.remainingAmount;
  }
};

const savePayment = () => {
  const p = storeActions.recordPayment(form.value);
  if (p) {
    toast.success(`Pembayaran ${p.paymentNumber} senilai Rp ${p.amount.toLocaleString('id-ID')} berhasil dicatat.`);
    inputModalOpen.value = false;
  }
};

const openPaymentDetail = (pay) => {
  selectedPayment.value = pay;
  detailDrawerOpen.value = true;
};
</script>
