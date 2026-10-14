<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Verifikasi & Approval Order</h1>
          <p class="text-sm text-slate-500 mt-1">
            Wewenang eksekutif Direktur untuk memeriksa kelaikan margin, ketersediaan stok, dan menerbitkan persetujuan.
          </p>
        </div>
      </div>
    </template>

    <!-- PENDING APPROVAL TABLE -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden mb-6">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
          <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
            Antrean Menunggu Telaah Direktur ({{ pendingOrders.length }} Order)
          </h3>
        </div>
        <span class="text-xs text-slate-500">Pilih baris untuk membuka panel telaah verifikasi 3 kolom</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
              <th class="py-3 px-4">Order Number</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">Date</th>
              <th class="py-3 px-4 text-right">Total</th>
              <th class="py-3 px-4">Submitted By</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="order in pendingOrders"
              :key="order.id"
              :class="[selectedOrder?.id === order.id ? 'bg-blue-50/50' : 'hover:bg-slate-50/60', 'transition-colors cursor-pointer']"
              @click="selectForVerification(order)"
            >
              <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ order.orderNumber }}</td>
              <td class="py-3.5 px-4">
                <span class="font-bold text-slate-900 block">{{ order.clientName }}</span>
                <span class="text-[11px] text-slate-400 block truncate max-w-xs">{{ order.projectName }}</span>
              </td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ order.date }}</td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                Rp {{ order.grandTotal.toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-slate-600">{{ order.submittedBy }}</td>
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :status="order.status" />
              </td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <button
                  type="button"
                  class="px-3 py-1 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded font-bold text-xs"
                  @click.stop="selectForVerification(order)"
                >
                  Telaah & Putuskan
                </button>
              </td>
            </tr>

            <tr v-if="pendingOrders.length === 0">
              <td colspan="7" class="py-12 text-center text-slate-400">
                Semua antrean order telah diverifikasi.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- DETAIL VERIFICATION PANEL (3 COLUMNS: LEFT, CENTER, RIGHT + BOTTOM ACTION) -->
    <div v-if="selectedOrder" class="bg-white rounded-xl border border-slate-200/90 shadow-sm p-6 space-y-6 animate-in fade-in duration-200">
      <div class="flex items-center justify-between pb-4 border-b border-slate-200">
        <div>
          <span class="text-xs font-semibold text-alpharo-500 uppercase tracking-wider">Panel Verifikasi Direktur</span>
          <h2 class="text-lg font-bold text-slate-900">
            Telaah Wewenang: {{ selectedOrder.orderNumber }} &bull; {{ selectedOrder.clientName }}
          </h2>
        </div>
        <StatusBadge :status="selectedOrder.status" />
      </div>

      <!-- 3 COLUMNS GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 text-xs">
        <!-- LEFT COLUMN (4 COLS): ORDER INFORMATION & CLIENT -->
        <div class="lg:col-span-4 bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
          <h4 class="font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-alpharo-500"></span>
            LEFT: Order & Client Information
          </h4>

          <div class="space-y-2">
            <div>
              <span class="text-slate-400 text-[11px] block">Nama Rekanan Client</span>
              <span class="font-bold text-slate-900 text-sm block">{{ selectedOrder.clientName }}</span>
            </div>
            <div>
              <span class="text-slate-400 text-[11px] block">Nama Kegiatan / Proyek</span>
              <span class="font-medium text-slate-800 block">{{ selectedOrder.projectName }}</span>
            </div>
            <div>
              <span class="text-slate-400 text-[11px] block">Tanggal Pengajuan</span>
              <span class="font-mono text-slate-700">{{ selectedOrder.date }}</span>
            </div>
            <div>
              <span class="text-slate-400 text-[11px] block">Diajukan Oleh</span>
              <span class="text-slate-700 font-medium">{{ selectedOrder.submittedBy }}</span>
            </div>
            <div>
              <span class="text-slate-400 text-[11px] block">Catatan Marketing</span>
              <p class="text-slate-600 bg-white p-2.5 rounded border border-slate-200 mt-1 italic">
                {{ selectedOrder.notes || 'Tidak ada catatan tambahan.' }}
              </p>
            </div>
          </div>
        </div>

        <!-- CENTER COLUMN (5 COLS): ITEMS & SERVICES -->
        <div class="lg:col-span-5 bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
          <h4 class="font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-alpharo-500"></span>
            CENTER: Items Barang & Jasa
          </h4>

          <div class="border border-slate-200 rounded-lg overflow-hidden bg-white">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-100/70 border-b border-slate-200 text-slate-600 font-semibold">
                <tr>
                  <th class="p-2.5">Item</th>
                  <th class="p-2.5 text-center">Volume</th>
                  <th class="p-2.5 text-right">Harga Satuan</th>
                  <th class="p-2.5 text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="it in selectedOrder.items" :key="it.sku">
                  <td class="p-2.5">
                    <span class="font-medium text-slate-800 block">{{ it.name }}</span>
                    <span class="font-mono text-[10px] text-slate-400">{{ it.sku }}</span>
                  </td>
                  <td class="p-2.5 text-center whitespace-nowrap">{{ it.qty }} {{ it.unit }}</td>
                  <td class="p-2.5 text-right whitespace-nowrap">Rp {{ it.price.toLocaleString('id-ID') }}</td>
                  <td class="p-2.5 text-right font-bold whitespace-nowrap">Rp {{ it.total.toLocaleString('id-ID') }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- RIGHT COLUMN (3 COLS): SUMMARY & VALUE -->
        <div class="lg:col-span-3 bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4 flex flex-col justify-between">
          <div>
            <h4 class="font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2 mb-3">
              <span class="w-2 h-2 rounded-full bg-alpharo-500"></span>
              RIGHT: Summary Nilai
            </h4>

            <div class="space-y-2 bg-white p-3.5 rounded-lg border border-slate-200">
              <div class="flex justify-between text-slate-600">
                <span>Subtotal Items:</span>
                <span class="font-mono">Rp {{ selectedOrder.subtotal.toLocaleString('id-ID') }}</span>
              </div>
              <div class="flex justify-between text-slate-600">
                <span>PPN 11%:</span>
                <span class="font-mono">Rp {{ selectedOrder.tax.toLocaleString('id-ID') }}</span>
              </div>
              <div class="pt-2 border-t border-slate-200 flex justify-between font-bold text-sm text-slate-900">
                <span>Grand Total:</span>
                <span class="font-mono text-alpharo-600">Rp {{ selectedOrder.grandTotal.toLocaleString('id-ID') }}</span>
              </div>
            </div>
          </div>

          <div class="p-3 bg-blue-50/70 border border-blue-200 rounded-lg text-[11px] text-slate-600">
            <strong>Catatan Otoritas:</strong> Menyetujui order akan otomatis menerbitkan sinyal kerja ke Administrasi (Dokumen) & Warehouse (Pemenuhan Barang).
          </div>
        </div>
      </div>

      <!-- BOTTOM: APPROVAL ACTION -->
      <div class="pt-4 border-t border-slate-200 bg-slate-50/50 -mx-6 -mb-6 p-6 rounded-b-xl flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="w-full sm:w-auto">
          <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Verifikasi Direktur (Opsional untuk Approve):</label>
          <input
            v-model="directorNotes"
            type="text"
            placeholder="Masukkan instruksi khusus atau catatan telaah..."
            class="w-full sm:w-96 px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white"
          />
        </div>

        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
          <button
            type="button"
            class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-lg shadow-xs transition-colors flex items-center gap-1.5"
            @click="openRejectModal(selectedOrder)"
          >
            <AlertCircle class="w-4 h-4" />
            <span>Tolak Order (Reject)</span>
          </button>

          <button
            type="button"
            class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg shadow-xs transition-colors flex items-center gap-1.5"
            @click="executeApprove(selectedOrder)"
          >
            <CheckCircle2 class="w-4 h-4" />
            <span>Setujui Order (Approve)</span>
          </button>
        </div>
      </div>
    </div>

    <!-- APPROVAL HISTORY SECTION -->
    <div class="mt-8 bg-white rounded-xl border border-slate-200/90 shadow-xs p-5">
      <h3 class="font-bold text-slate-900 text-sm mb-4 pb-2 border-b border-slate-100">
        Riwayat Keputusan Verifikasi & Approval Masa Lalu
      </h3>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-2.5 px-3">No. Order</th>
              <th class="py-2.5 px-3">Client</th>
              <th class="py-2.5 px-3">Tanggal Keputusan</th>
              <th class="py-2.5 px-3">Approver</th>
              <th class="py-2.5 px-3 text-center">Status</th>
              <th class="py-2.5 px-3">Catatan / Alasan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="ord in pastDecisions" :key="ord.id">
              <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ ord.orderNumber }}</td>
              <td class="py-3 px-3 font-medium text-slate-800">{{ ord.clientName }}</td>
              <td class="py-3 px-3 text-slate-500 whitespace-nowrap">{{ ord.approvalDate }}</td>
              <td class="py-3 px-3 text-slate-700">{{ ord.approvedBy }}</td>
              <td class="py-3 px-3 text-center"><StatusBadge :status="ord.status" /></td>
              <td class="py-3 px-3 text-slate-600 italic">"{{ ord.approvalNotes }}"</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- REJECT MODAL WITH MANDATORY REASON -->
    <Modal
      v-model="rejectModalOpen"
      title="Tolak Pengajuan Order (Wajib Alasan Tertulis)"
      :subtitle="`Order ${orderToReject?.orderNumber} - ${orderToReject?.clientName}`"
      size="md"
    >
      <div class="space-y-4">
        <p class="text-xs text-slate-600 leading-relaxed">
          Alasan penolakan akan dicatat permanen dalam audit trail dan dikirimkan sebagai notifikasi ke staff Marketing.
        </p>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
            Alasan / Catatan Penolakan (Wajib Diisi) *
          </label>
          <textarea
            v-model="rejectReason"
            rows="3"
            required
            placeholder="Tuliskan alasan penolakan secara jelas dan objektif..."
            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-rose-500"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button
            type="button"
            class="px-3.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg"
            @click="rejectModalOpen = false"
          >
            Batal
          </button>
          <button
            type="button"
            :disabled="!rejectReason.trim()"
            class="px-4 py-1.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg disabled:opacity-50"
            @click="executeReject"
          >
            Tolak Pengajuan
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import Modal from '../../components/common/Modal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import { CheckCircle2, AlertCircle } from 'lucide-vue-next';

const toast = useToast();

const selectedOrder = ref(null);
const directorNotes = ref('');
const rejectModalOpen = ref(false);
const orderToReject = ref(null);
const rejectReason = ref('');

const pendingOrders = computed(() => {
  return store.orders.filter((o) => o.status === 'Menunggu Verifikasi');
});

const pastDecisions = computed(() => {
  return store.orders.filter((o) => o.approvalDate);
});

// Auto-select first pending order if exists
if (pendingOrders.value.length > 0) {
  selectedOrder.value = pendingOrders.value[0];
}

const selectForVerification = (order) => {
  selectedOrder.value = order;
};

const executeApprove = (order) => {
  storeActions.approveOrder(order.id, directorNotes.value || 'Disetujui penuh oleh Direktur.');
  toast.success(`Order ${order.orderNumber} telah disetujui! Administrasi & Warehouse telah dinotifikasi.`);
  selectedOrder.value = pendingOrders.value[0] || null;
  directorNotes.value = '';
};

const openRejectModal = (order) => {
  orderToReject.value = order;
  rejectReason.value = '';
  rejectModalOpen.value = true;
};

const executeReject = () => {
  if (orderToReject.value && rejectReason.value.trim()) {
    storeActions.rejectOrder(orderToReject.value.id, rejectReason.value.trim());
    toast.error(`Order ${orderToReject.value.orderNumber} telah ditolak.`);
    rejectModalOpen.value = false;
    selectedOrder.value = pendingOrders.value[0] || null;
  }
};
</script>
