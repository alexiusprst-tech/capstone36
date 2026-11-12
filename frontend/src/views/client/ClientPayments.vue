<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Status Pembayaran & Tagihan</h1>
          <p class="text-sm text-slate-500 mt-1">
            Rincian invoice tagihan dan riwayat konfirmasi pembayaran instansi Anda dengan CV Alpharo Artha Jaya.
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
    <div class="bg-linear-to-r from-alpharo-900 via-alpharo-600 to-alpharo-800 rounded-2xl p-5 text-white shadow-md mb-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-white/20 text-white uppercase tracking-wider mb-2">
            Rekening Resmi Perusahaan (Tunggal & Tetap)
          </span>
          <h3 class="text-base font-semibold text-white">Pembayaran Ditujukan Kepada: CV Alpharo Artha Jaya</h3>
          <p class="text-xs text-alpharo-100 mt-0.5">
            Gunakan nomor rekening resmi Bank BCA berikut untuk seluruh transaksi pembayaran pesanan dan tagihan instansi Anda.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <div class="ui-panel bg-white/10 backdrop-blur-xs rounded-xl p-4 border border-white/20 min-w-[260px] shadow-sm">
            <div class="flex items-center justify-between gap-2 mb-1">
              <p class="text-[11px] text-amber-300 font-semibold uppercase tracking-wider">Bank BCA Jakarta</p>
              <span class="text-[9px] font-semibold bg-emerald-500/80 text-white px-2 py-0.5 rounded-full">Resmi Tetap</span>
            </div>
            <p class="font-mono text-lg font-semibold text-white tracking-widest my-1 select-all">52323628813</p>
            <p class="text-[11px] text-alpharo-100 font-semibold">a.n. CV ALPHARO ARTHA JAYA</p>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI SUMMARY -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <KpiCard title="Total Tagihan" :value="totalInvoiceValue" :is-currency="true" subtitle="Seluruh nilai invoice" icon-variant="blue" />
      <KpiCard title="Sudah Dibayar" :value="totalPaid" :is-currency="true" subtitle="Realisasi dana diterima" icon-variant="blue" />
      <KpiCard title="Sisa Tagihan" :value="totalOutstanding" :is-currency="true" subtitle="Menunggu pelunasan" icon-variant="blue" />
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
              ? 'bg-alpharo-600 text-white font-semibold'
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

    <!-- INVOICE CARDS WITH PAYMENT DETAIL -->
    <div class="space-y-6">
      <div
        v-for="inv in filteredInvoices"
        :key="inv.id"
        class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden"
      >
        <!-- Header invoice card -->
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="font-mono font-semibold text-slate-900 text-sm">{{ inv.invoiceNumber }}</span>
              <StatusBadge :status="inv.status" />
            </div>
            <p class="text-xs text-slate-600">
              Order No: <span class="font-mono font-semibold text-slate-800">{{ inv.orderNumber }}</span>
              &bull; Skema: <span class="font-semibold text-emerald-700">Pembayaran Penuh</span>
            </p>
            <p class="text-xs text-slate-400 mt-0.5">
              Tanggal Terbit: {{ inv.invoiceDate }} &bull; Batas Jatuh Tempo: {{ inv.dueDate }}
            </p>
          </div>

          <div class="flex items-center gap-4">
            <div class="text-left sm:text-right">
              <p class="text-[11px] text-slate-400 uppercase font-semibold">Total Tagihan</p>
              <p class="text-base font-semibold text-slate-900 font-mono">Rp {{ (inv.totalAmount || 0).toLocaleString('id-ID') }}</p>
              <p class="text-xs text-rose-600 font-semibold font-mono">
                Sisa: Rp {{ (inv.remainingAmount || 0).toLocaleString('id-ID') }}
              </p>
            </div>

            <button
              type="button"
              class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-semibold shadow-xs transition-colors shrink-0 flex items-center gap-1.5 cursor-pointer"
              title="Unduh Invoice Resmi (PDF)"
              @click="downloadClientInvoice(inv)"
            >
              <Download class="w-3.5 h-3.5" />
              <span>Unduh Invoice</span>
            </button>

            <button
              v-if="inv.remainingAmount > 0"
              type="button"
              class="px-3.5 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors shrink-0 flex items-center gap-1.5"
              @click="openPaymentModal(inv)"
            >
              <CreditCard class="w-4 h-4" />
              <span>Bayar / Konfirmasi</span>
            </button>
          </div>
        </div>

        <!-- Status Pelunasan Tagihan -->
        <div class="p-5">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">
              Progres Pelunasan Tagihan
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

          <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
            <div class="space-y-1">
              <span class="font-semibold text-slate-800">Status Kewajiban Tagihan</span>
              <p class="text-slate-500">
                {{ inv.remainingAmount <= 0 ? 'Seluruh kewajiban pembayaran invoice ini telah lunas 100%.' : 'Menunggu pelunasan pembayaran tagihan dari pihak rekanan.' }}
              </p>
            </div>
            <button
              v-if="inv.remainingAmount > 0"
              type="button"
              class="px-3.5 py-1.5 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded-lg font-semibold shrink-0"
              @click="openPaymentModal(inv)"
            >
              Konfirmasi Bayar
            </button>
            <span v-else class="text-emerald-600 font-semibold flex items-center gap-1 shrink-0">
              <CheckCircle2 class="w-4 h-4" /> Lunas 100%
            </span>
          </div>
        </div>
      </div>

      <div
        v-if="filteredInvoices.length === 0"
        class="ui-panel bg-white rounded-xl border border-slate-200/90 p-12 shadow-xs text-center text-slate-400 text-xs"
      >
        <Receipt class="w-12 h-12 text-slate-300 mx-auto mb-3" />
        <p class="font-semibold text-slate-700 text-sm">Tidak ada invoice tagihan</p>
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

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Nominal yang Ditransfer (Rp)</label>
          <input
            v-model.number="paymentForm.amount"
            type="number"
            min="1000"
            required
            placeholder="Contoh: 15000000"
            class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono font-semibold"
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

        <!-- UNGGAH BUKTI TRANSFER IDENTIK WAREHOUSE LAPOR KERUSAKAN (MAKS. 1 BERKAS) -->
        <div>
          <label class="block font-semibold text-slate-700 mb-1.5 flex items-center justify-between">
            <span>Unggah Bukti Transfer (File Foto/PDF) <span class="text-rose-500">*</span></span>
            <span class="text-[11px] text-slate-400 font-normal">Maks. 1 berkas (Foto/PDF maks 10MB)</span>
          </label>

          <input
            ref="proofFileInput"
            type="file"
            accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
            class="hidden"
            @change="handleProofFileChange"
          />

          <!-- Media Thumbnails Grid (Persis Warehouse Lapor Kerusakan) -->
          <div class="flex items-center gap-3 overflow-x-auto py-2">
            <!-- Uploaded Items -->
            <div
              v-for="(item, idx) in proofMediaList"
              :key="idx"
              class="relative w-24 h-24 sm:w-28 sm:h-28 rounded-2xl overflow-hidden bg-slate-900 border border-slate-200 shadow-sm shrink-0 group cursor-pointer"
              @click="openMediaPreview(item)"
            >
              <img
                v-if="item.type === 'image'"
                :src="item.url"
                :alt="item.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
              <div v-else class="w-full h-full flex flex-col items-center justify-center bg-slate-800 p-2 text-white">
                <FileText class="w-8 h-8 text-sky-400 mb-1" />
                <span class="text-[10px] font-mono text-center truncate max-w-full px-1">{{ item.name }}</span>
                <span class="text-[9px] text-slate-400 font-mono">{{ item.size }}</span>
              </div>

              <!-- Close / Delete Button -->
              <button
                type="button"
                class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-black/60 hover:bg-rose-600 text-white flex items-center justify-center transition-colors shadow-xs cursor-pointer z-10"
                title="Hapus berkas ini"
                @click.stop="removeProofMedia(idx)"
              >
                <X class="w-3.5 h-3.5" />
              </button>
            </div>

            <!-- Upload Placeholder Box (Hanya muncul jika belum ada berkas terpilih) -->
            <button
              v-if="proofMediaList.length === 0"
              type="button"
              class="w-24 h-24 sm:w-28 sm:h-28 rounded-xl border-2 border-dashed border-slate-300 hover:border-slate-400 bg-white hover:bg-slate-50/80 flex flex-col items-center justify-center gap-1.5 shrink-0 transition-colors cursor-pointer group"
              @click="triggerProofFileInput"
            >
              <div class="w-8 h-8 rounded-full bg-slate-100 group-hover:bg-slate-200 flex items-center justify-center text-slate-600 transition-colors">
                <Camera class="w-4 h-4 stroke-[2]" />
              </div>
              <span class="text-xs font-semibold text-slate-700">Foto / PDF</span>
            </button>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
          <button
            type="button"
            class="px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold cursor-pointer"
            @click="showPaymentModal = false"
          >
            Batal
          </button>
          <button
            type="submit"
            class="px-4 py-2 rounded-lg bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold shadow-xs cursor-pointer"
          >
            Kirim Konfirmasi
          </button>
        </div>
      </form>
    </Modal>

    <!-- MODAL PREVIEW BUKTI TRANSFER (FULL SIZE PERSIS WAREHOUSE) -->
    <Modal
      v-model="mediaPreviewModalOpen"
      :title="activePreviewMedia?.name || 'Pratinjau Bukti Pembayaran'"
      subtitle="Dokumen atau foto struk transfer yang dilampirkan"
      size="xl"
    >
      <div v-if="activePreviewMedia" class="space-y-3">
        <div class="w-full rounded-2xl overflow-hidden bg-black flex items-center justify-center max-h-[70vh]">
          <img
            v-if="activePreviewMedia.type === 'image'"
            :src="activePreviewMedia.url"
            :alt="activePreviewMedia.name"
            class="w-full h-auto max-h-[70vh] object-contain"
          />
          <div v-else class="text-center py-12 text-white space-y-3">
            <FileText class="w-16 h-16 text-sky-400 mx-auto" />
            <p class="font-semibold text-base">{{ activePreviewMedia.name }}</p>
            <p class="text-xs text-slate-400 font-mono">{{ activePreviewMedia.size }}</p>
            <a
              :href="activePreviewMedia.url"
              target="_blank"
              download="Bukti_Transfer.pdf"
              class="inline-flex items-center gap-1.5 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded-lg text-xs font-semibold cursor-pointer"
            >
              Buka / Unduh Berkas PDF
            </a>
          </div>
        </div>
        <div class="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-200">
          <div>
            <span class="font-semibold text-slate-800">{{ activePreviewMedia.name }}</span>
            <span class="ml-2 font-mono">({{ activePreviewMedia.size }})</span>
          </div>
          <button
            type="button"
            class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg cursor-pointer"
            @click="mediaPreviewModalOpen = false"
          >
            Tutup
          </button>
        </div>
      </div>
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
import { orderError, orderWorkflow } from '../../services/orderWorkflow';
import { useToast } from '../../services/toast';
import {
  CreditCard,
  UploadCloud,
  CheckCircle2,
  Receipt,
  Download,
  FileText,
  Trash2,
  Camera,
  X,
} from 'lucide-vue-next';

const toast = useToast();

const clientId = computed(() => store.currentUser.clientId || 1);

const clientInvoices = computed(() => {
  return store.invoices.filter((inv) => inv.clientId === clientId.value);
});

const currentFilter = ref('all');

const downloadClientInvoice = async (inv) => {
  if (!inv) return;
  try {
    toast.info(`Menyiapkan unduhan Invoice ${inv.invoiceNumber}...`);
    await orderWorkflow.downloadInvoice(inv.orderNumber, false);
    toast.success(`Invoice ${inv.invoiceNumber} berhasil diunduh.`);
  } catch (error) {
    toast.error('Gagal mengunduh invoice: ' + orderError(error));
  }
};

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
  amount: 0,
  paymentMethod: 'Transfer BCA',
  paymentDate: new Date().toISOString().split('T')[0],
  referenceNumber: `TRF-${Math.floor(100000 + Math.random() * 900000)}`,
  notes: '',
  proofFile: '',
  proofUrl: '',
});

const proofFileInput = ref(null);
const proofMediaList = ref([]);
const mediaPreviewModalOpen = ref(false);
const activePreviewMedia = ref(null);

const triggerProofFileInput = () => {
  if (proofFileInput.value) proofFileInput.value.click();
};

const handleProofFileChange = (e) => {
  const file = e.target.files?.[0];
  if (!file) return;

  if (file.size > 10 * 1024 * 1024) {
    toast.error(`Berkas ${file.name} melebihi batas 10MB.`);
    e.target.value = '';
    return;
  }

  const isImage = file.type.startsWith('image/');
  const isPdf = file.type.includes('pdf') || file.name.toLowerCase().endsWith('.pdf');
  if (!isImage && !isPdf) {
    toast.error(`Format berkas ${file.name} tidak didukung. Harap pilih foto (JPG, PNG) atau dokumen PDF.`);
    e.target.value = '';
    return;
  }

  const reader = new FileReader();
  reader.onload = (loadEvent) => {
    const itemObj = {
      name: file.name,
      type: isImage ? 'image' : 'pdf',
      url: loadEvent.target.result,
      size: (file.size / 1024).toFixed(0) + ' KB',
    };
    // Batasi strictly hanya 1 berkas foto/PDF saja
    proofMediaList.value = [itemObj];
    paymentForm.value.proofFile = itemObj.name;
    paymentForm.value.proofUrl = itemObj.url;
    toast.success(`Berkas ${file.name} berhasil dilampirkan.`);
  };
  reader.readAsDataURL(file);

  e.target.value = '';
};

const removeProofMedia = () => {
  proofMediaList.value = [];
  paymentForm.value.proofFile = '';
  paymentForm.value.proofUrl = '';
};

const openMediaPreview = (item) => {
  activePreviewMedia.value = item;
  mediaPreviewModalOpen.value = true;
};

const openPaymentModal = (inv = null) => {
  const targetInv = inv || clientInvoices.value[0];
  if (!targetInv) {
    toast.error('Belum ada data invoice untuk dikonfirmasi.');
    return;
  }

  // Reset proof file state
  proofMediaList.value = [];
  mediaPreviewModalOpen.value = false;
  activePreviewMedia.value = null;
  paymentForm.value.proofFile = '';
  paymentForm.value.proofUrl = '';

  paymentForm.value.invoiceNumber = targetInv.invoiceNumber;
  paymentForm.value.amount = targetInv.remainingAmount || targetInv.totalAmount;
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

  if (!proofMediaList.value.length) {
    toast.error('Harap unggah bukti transfer terlebih dahulu sebelum mengirim.');
    return;
  }

  // Call store action to record payment
  storeActions.recordPayment({
    invoiceId: inv.id,
    invoiceNumber: inv.invoiceNumber,
    amount: paymentForm.value.amount,
    paymentDate: paymentForm.value.paymentDate,
    paymentMethod: paymentForm.value.paymentMethod,
    referenceNumber: paymentForm.value.referenceNumber,
    paymentType: 'Pembayaran Tagihan',
    notes: paymentForm.value.notes,
    proofFile: proofMediaList.value[0]?.name || 'BUKTI_TRANSFER_SMA.pdf',
    proofUrl: proofMediaList.value[0]?.url || null,
  });

  // Also add client confirmation notification to Finance
  storeActions.addNotification(
    'FINANCE',
    'Konfirmasi Pembayaran Rekanan',
    `${store.currentUser.name} telah mengirimkan konfirmasi pembayaran Rp ${Number(paymentForm.value.amount || 0).toLocaleString('id-ID')} untuk ${inv.invoiceNumber}.`,
    'payment',
    '/finance/payments'
  );

  toast.success('Konfirmasi pembayaran dan bukti transfer berhasil dikirim ke bagian Keuangan Alpharo!');
  showPaymentModal.value = false;
};
</script>
