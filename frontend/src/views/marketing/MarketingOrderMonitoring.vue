<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Monitoring Order Pipeline</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pantau status progress transaksi dari drafting, telaah Direktur, penagihan, hingga penuntasan proyek.
          </p>
        </div>
      </div>
    </template>

    <!-- STATUS TABS / FILTER -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-5">
      <button
        v-for="tab in statusTabs"
        :key="tab.value"
        type="button"
        :class="[
          activeTab === tab.value
            ? 'bg-alpharo-500 text-white font-bold shadow-xs'
            : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200',
          'px-4 py-2 rounded-xl text-xs whitespace-nowrap transition-all flex items-center gap-2'
        ]"
        @click="activeTab = tab.value"
      >
        <span>{{ tab.label }}</span>
        <span
          :class="[
            activeTab === tab.value ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600',
            'px-1.5 py-0.2 rounded-full text-[10px] font-mono'
          ]"
        >
          {{ getCount(tab.value) }}
        </span>
      </button>
    </div>

    <!-- ORDERS LIST / CARDS IN PIPELINE -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="order in filteredOrders"
        :key="order.id"
        class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between"
      >
        <div>
          <div class="flex items-start justify-between gap-2 mb-2">
            <div class="flex items-center gap-1.5">
              <span class="font-mono font-bold text-slate-900 text-sm">{{ order.orderNumber }}</span>
              <span
                v-if="order.isHold"
                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200"
                title="Pengiriman ditahan Direktur"
              >
                ⛔ Hold
              </span>
            </div>
            <StatusBadge :status="order.status" />
          </div>
          <h3 class="font-bold text-slate-900 text-sm line-clamp-1">{{ order.clientName }}</h3>
          <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ order.projectName }}</p>

          <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-500">Nilai Transaksi:</span>
            <span class="font-bold font-mono text-slate-900">Rp {{ order.grandTotal.toLocaleString('id-ID') }}</span>
          </div>

          <div class="mt-2 text-xs text-slate-500 flex items-center justify-between">
            <span>Progress Dokumen:</span>
            <span class="font-semibold text-slate-700">{{ getDocCount(order.documents) }}/4 Dokumen</span>
          </div>

          <div v-if="order.status === 'Menunggu Pembayaran'" class="mt-2 p-2 bg-amber-50 rounded-lg border border-amber-200 text-[11px] text-amber-800 flex items-center justify-between">
            <span>Menunggu Pembayaran Client</span>
            <span class="font-semibold">{{ order.followUpLogs?.length || 0 }} Log Follow-Up</span>
          </div>
        </div>

        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
          <span class="text-[11px] text-slate-400">{{ order.date }}</span>
          <button
            type="button"
            class="text-xs font-bold text-alpharo-500 hover:text-alpharo-700"
            @click="viewDetail(order)"
          >
            Lihat Timeline & Detail →
          </button>
        </div>
      </div>

      <div v-if="filteredOrders.length === 0" class="col-span-full bg-white p-12 text-center rounded-xl border border-slate-200 text-slate-400 text-sm">
        Tidak ada order pada tahap status "{{ activeTab }}".
      </div>
    </div>

    <!-- DETAIL DRAWER -->
    <Drawer
      v-model="drawerOpen"
      :title="`Monitoring: ${selectedOrder?.orderNumber || ''}`"
      :subtitle="selectedOrder?.clientName || ''"
      size="lg"
    >
      <div v-if="selectedOrder" class="space-y-6 text-xs">
        <!-- HOLD BANNER IF ON HOLD -->
        <div v-if="selectedOrder.isHold" class="p-3.5 bg-rose-50 rounded-xl border border-rose-200 text-rose-800">
          <div class="flex items-center gap-2 font-bold text-xs">
            <span>⛔ PENGIRIMAN DITAHAN OLEH DIREKTUR</span>
          </div>
          <p class="mt-1 text-[11px] text-rose-700">
            Alasan: <strong>{{ selectedOrder.holdReason }}</strong>
          </p>
          <span class="text-[10px] text-rose-500 block mt-1">
            Oleh {{ selectedOrder.holdBy }} pada {{ selectedOrder.holdDate }}
          </span>
        </div>

        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 flex justify-between items-center">
          <div>
            <span class="text-slate-500 block">Status Terkini</span>
            <div class="mt-1 flex items-center gap-1.5">
              <StatusBadge :status="selectedOrder.status" />
              <span v-if="selectedOrder.isHold" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">
                Ditahan
              </span>
            </div>
          </div>
          <div class="text-right">
            <span class="text-slate-500 block">Total Nilai</span>
            <span class="text-base font-bold font-mono text-slate-900 mt-0.5 block">
              Rp {{ selectedOrder.grandTotal.toLocaleString('id-ID') }}
            </span>
          </div>
        </div>

        <!-- PAYMENT & INVOICE STATUS -->
        <div v-if="associatedInvoice" class="p-4 bg-blue-50/60 rounded-xl border border-blue-200 space-y-2">
          <div class="flex items-center justify-between">
            <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px]">Informasi Invoice & Piutang</span>
            <StatusBadge :status="associatedInvoice.status" />
          </div>
          <div class="grid grid-cols-2 gap-2 pt-1 text-slate-700">
            <div>
              <span class="text-slate-500 text-[10px] block">No. Invoice</span>
              <span class="font-mono font-bold">{{ associatedInvoice.invoiceNumber }}</span>
            </div>
            <div>
              <span class="text-slate-500 text-[10px] block">Jatuh Tempo</span>
              <span class="font-medium">{{ associatedInvoice.dueDate }}</span>
            </div>
            <div>
              <span class="text-slate-500 text-[10px] block">Telah Dibayar</span>
              <span class="font-mono font-bold text-emerald-600">Rp {{ associatedInvoice.paidAmount.toLocaleString('id-ID') }}</span>
            </div>
            <div>
              <span class="text-slate-500 text-[10px] block">Sisa Tagihan</span>
              <span class="font-mono font-bold text-rose-600">Rp {{ associatedInvoice.remainingAmount.toLocaleString('id-ID') }}</span>
            </div>
          </div>
        </div>

        <!-- CLIENT FOLLOW-UP LOGS & ACTION -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="font-bold uppercase text-slate-700 tracking-wider text-[11px]">Log Follow-Up Client</h4>
              <p class="text-[11px] text-slate-500">Catatan komunikasi tim Marketing dengan PIC client terkait pembayaran & SP2D.</p>
            </div>
            <button
              type="button"
              class="px-3 py-1.5 bg-alpharo-500 hover:bg-alpharo-600 text-white font-bold rounded-lg text-xs transition-colors flex items-center gap-1.5 shadow-2xs"
              @click="openFollowUpModal"
            >
              <span>+ Catat Follow-Up</span>
            </button>
          </div>

          <div v-if="selectedOrder.followUpLogs && selectedOrder.followUpLogs.length > 0" class="space-y-2 max-h-60 overflow-y-auto pr-1">
            <div
              v-for="log in selectedOrder.followUpLogs"
              :key="log.id"
              class="p-3 bg-white rounded-lg border border-slate-200 shadow-2xs text-xs space-y-1"
            >
              <div class="flex items-center justify-between text-[11px]">
                <span class="font-bold text-slate-800">{{ log.contactPerson }} ({{ log.method }})</span>
                <span class="text-slate-400 font-mono">{{ log.date }}</span>
              </div>
              <p class="text-slate-700 font-medium text-xs">{{ log.outcome }}</p>
              <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                <span>Janji Bayar: <strong class="text-slate-800">{{ log.promisedPaymentDate || '-' }}</strong></span>
                <span class="italic text-[10px]">Oleh: {{ log.recordedBy }}</span>
              </div>
              <p v-if="log.notes" class="text-[11px] text-slate-500 bg-slate-50 p-1.5 rounded">Catatan: {{ log.notes }}</p>
            </div>
          </div>
          <div v-else class="p-4 bg-white rounded-lg border border-slate-200 text-center text-slate-400 text-xs">
            Belum ada catatan follow-up client untuk pesanan ini. Klik tombol di atas untuk mencatat komunikasi.
          </div>
        </div>

        <DocumentChecklist :documents="selectedOrder.documents" />

        <!-- SEGREGATION OF DUTIES INFO NOTICE -->
        <div class="p-3 bg-amber-50/70 rounded-xl border border-amber-200/80 text-[11px] text-amber-900 leading-relaxed">
          <strong>ℹ️ Otoritas Peran (Segregation of Duties):</strong> Marketing bertugas mengawal hubungan dengan client dan mencatat hasil follow-up penagihan. Pengubahan status invoice dan verifikasi tanda lunas hanya dapat dilakukan oleh <strong>Divisi Keuangan (Finance)</strong>.
        </div>

        <div>
          <h4 class="font-bold uppercase text-slate-500 tracking-wider mb-3">Timeline Lengkap</h4>
          <Timeline :timeline="selectedOrder.timeline" />
        </div>
      </div>
    </Drawer>

    <!-- FOLLOW UP MODAL -->
    <Modal
      v-model="followUpModalOpen"
      title="Catat Hasil Follow-Up Client"
      :subtitle="`Order ${selectedOrder?.orderNumber || ''} - ${selectedOrder?.clientName || ''}`"
      size="md"
    >
      <form class="space-y-4 text-xs" @submit.prevent="submitFollowUp">
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Nama / Kontak PIC Client</label>
          <input
            v-model="followUpForm.contactPerson"
            required
            placeholder="Contoh: Ir. Hendra (Procurement) / Bpk. Rahmat (PPK)"
            class="w-full px-3 py-2 border rounded-lg bg-white"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Metode Kontak</label>
            <select v-model="followUpForm.method" class="w-full px-3 py-2 border rounded-lg bg-white">
              <option>Telepon Langsung</option>
              <option>WhatsApp / Chat</option>
              <option>Kunjungan Lapangan / Site Visit</option>
              <option>Email Resmi</option>
            </select>
          </div>
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Tanggal Janji Bayar</label>
            <input
              v-model="followUpForm.promisedPaymentDate"
              type="date"
              class="w-full px-3 py-2 border rounded-lg bg-white"
            />
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Hasil Komunikasi / Status Penagihan</label>
          <textarea
            v-model="followUpForm.outcome"
            required
            rows="2"
            placeholder="Contoh: Berkas BAST sudah diverifikasi Bagian Keuangan Client. SP2D dijadwalkan cair sebelum 25 Oktober."
            class="w-full px-3 py-2 border rounded-lg bg-white"
          ></textarea>
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Catatan Tambahan (Opsional)</label>
          <input
            v-model="followUpForm.notes"
            placeholder="Contoh: Memerlukan materai tambahan pada faktur fisik..."
            class="w-full px-3 py-2 border rounded-lg bg-white"
          />
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button type="button" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg" @click="followUpModalOpen = false">Batal</button>
          <button type="submit" class="px-4 py-1.5 bg-alpharo-500 text-white font-bold rounded-lg hover:bg-alpharo-600">Simpan Catatan Follow-Up</button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import Drawer from '../../components/common/Drawer.vue';
import Modal from '../../components/common/Modal.vue';
import DocumentChecklist from '../../components/common/DocumentChecklist.vue';
import Timeline from '../../components/common/Timeline.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';

const toast = useToast();

const activeTab = ref('Semua');
const drawerOpen = ref(false);
const selectedOrder = ref(null);
const followUpModalOpen = ref(false);

const followUpForm = ref({
  contactPerson: '',
  method: 'Telepon Langsung',
  outcome: '',
  promisedPaymentDate: '',
  notes: '',
});

const statusTabs = [
  { label: 'Semua Order', value: 'Semua' },
  { label: 'Draft', value: 'Draft' },
  { label: 'Menunggu Verifikasi', value: 'Menunggu Verifikasi' },
  { label: 'Disetujui', value: 'Disetujui' },
  { label: 'Diproses', value: 'Diproses' },
  { label: 'Menunggu Pembayaran', value: 'Menunggu Pembayaran' },
  { label: 'Selesai', value: 'Selesai' },
  { label: 'Ditolak', value: 'Ditolak' },
];

const getCount = (val) => {
  if (val === 'Semua') return store.orders.length;
  return store.orders.filter((o) => o.status === val).length;
};

const filteredOrders = computed(() => {
  if (activeTab.value === 'Semua') return store.orders;
  return store.orders.filter((o) => o.status === activeTab.value);
});

const getDocCount = (docs) => {
  if (!docs) return 0;
  let count = 0;
  if (docs.po?.status === 'Lengkap') count++;
  if (docs.suratJalan?.status === 'Lengkap') count++;
  if (docs.bast?.status === 'Lengkap') count++;
  if (docs.faktur?.status === 'Lengkap') count++;
  return count;
};

const associatedInvoice = computed(() => {
  if (!selectedOrder.value) return null;
  return store.invoices.find((i) => i.orderNumber === selectedOrder.value.orderNumber) || null;
});

const viewDetail = (order) => {
  selectedOrder.value = order;
  drawerOpen.value = true;
};

const openFollowUpModal = () => {
  if (!selectedOrder.value) return;
  const client = store.clients.find((c) => c.id === selectedOrder.value.clientId);
  followUpForm.value = {
    contactPerson: client ? `${client.contactPerson} (${client.phone})` : 'PIC Client',
    method: 'Telepon Langsung',
    outcome: '',
    promisedPaymentDate: '',
    notes: '',
  };
  followUpModalOpen.value = true;
};

const submitFollowUp = () => {
  if (!selectedOrder.value) return;
  storeActions.recordClientFollowUp(selectedOrder.value.id, followUpForm.value);
  toast.success('Hasil follow-up penagihan client berhasil disimpan.');
  followUpModalOpen.value = false;
};
</script>
