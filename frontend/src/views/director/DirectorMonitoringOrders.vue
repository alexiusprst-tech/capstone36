<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Monitoring Order Eksekutif</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pengawasan menyeluruh terhadap seluruh status pesanan, kontrol risiko kredit, dan hak veto penahanan pengiriman.
          </p>
        </div>
      </div>
    </template>

    <!-- KPIS -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
      <KpiCard title="Total Order" :value="store.orders.length" icon-variant="blue" />
      <KpiCard title="Menunggu Approval" :value="countStatus('Menunggu Verifikasi')" icon-variant="blue" />
      <KpiCard title="Disetujui" :value="countStatus('Disetujui')" icon-variant="blue" />
      <KpiCard title="Menunggu Bayar" :value="countStatus('Menunggu Pembayaran')" icon-variant="blue" />
      <KpiCard title="Sedang Diproses" :value="countStatus('Diproses')" icon-variant="blue" />
      <KpiCard title="Tuntas Selesai" :value="countStatus('Selesai')" icon-variant="blue" />
    </div>

    <!-- FILTER & SEARCH -->
    <div class="bg-white p-4 rounded-xl border border-slate-200/90 mb-5 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-2xs">
      <div class="relative w-full sm:w-80">
        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari order, client, proyek..."
          class="w-full pl-9 pr-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-500"
        />
      </div>

      <div class="flex items-center gap-3 w-full sm:w-auto">
        <select
          v-model="statusFilter"
          class="text-xs px-3 py-2 rounded-lg border border-slate-300 bg-white"
        >
          <option value="">Semua Status</option>
          <option value="Menunggu Verifikasi">Menunggu Verifikasi</option>
          <option value="Disetujui">Disetujui</option>
          <option value="Menunggu Pembayaran">Menunggu Pembayaran</option>
          <option value="Diproses">Diproses</option>
          <option value="Selesai">Selesai</option>
          <option value="Ditolak">Ditolak</option>
          <option value="Draft">Draft</option>
        </select>
      </div>
    </div>

    <!-- MONITORING TABLE -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">No. Order</th>
              <th class="py-3 px-4">Client & Proyek</th>
              <th class="py-3 px-4">Tanggal</th>
              <th class="py-3 px-4 text-right">Nilai Total</th>
              <th class="py-3 px-4 text-center">Status Transaksi</th>
              <th class="py-3 px-4 text-center">Gudang</th>
              <th class="py-3 px-4 text-right">Aksi Eksekutif</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="order in filteredOrders" :key="order.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4">
                <div class="font-mono font-bold text-slate-900">{{ order.orderNumber }}</div>
                <div v-if="order.isHold" class="mt-0.5">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">
                    ⛔ Ditahan Direktur
                  </span>
                </div>
              </td>
              <td class="py-3.5 px-4">
                <span class="font-bold text-slate-900 block">{{ order.clientName }}</span>
                <span class="text-[11px] text-slate-400 block truncate max-w-xs">{{ order.projectName }}</span>
              </td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ order.date }}</td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                Rp {{ Number(order.grandTotal || 0).toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :status="order.status" />
              </td>
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :status="order.fulfillmentStatus || 'Diproses'" />
              </td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <button
                  type="button"
                  class="px-2.5 py-1 text-alpharo-500 hover:bg-blue-50 rounded border border-blue-200 text-xs font-bold"
                  @click="openDetail(order)"
                >
                  Detail & Kontrol
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- DETAIL DRAWER -->
    <Drawer
      v-model="drawerOpen"
      :title="`Monitoring Order: ${selectedOrder?.orderNumber || ''}`"
      :subtitle="selectedOrder?.clientName || ''"
      size="lg"
    >
      <div v-if="selectedOrder" class="space-y-6 text-xs">
        <!-- HOLD STATE / EXECUTIVE VETO CONTROLS -->
        <div v-if="selectedOrder.isHold" class="p-4 bg-rose-50 rounded-xl border border-rose-200 space-y-2">
          <div class="flex items-center justify-between">
            <span class="font-bold text-rose-800 uppercase tracking-wider text-xs">⛔ STATUS: PENGIRIMAN DITAHAN</span>
            <button
              type="button"
              class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition-colors shadow-2xs"
              @click="openReleaseModal"
            >
              ✓ Lepas Tahanan Pengiriman
            </button>
          </div>
          <p class="text-rose-700 text-xs">
            Alasan Penahanan: <strong>{{ selectedOrder.holdReason }}</strong>
          </p>
          <span class="text-[10px] text-rose-500 block">
            Ditetapkan oleh {{ selectedOrder.holdBy }} pada {{ selectedOrder.holdDate }}
          </span>
        </div>
        <div v-else-if="canHoldOrder" class="p-4 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
          <div>
            <h4 class="font-bold text-slate-800 text-xs">Kontrol Penahanan Pengiriman (Hold Order)</h4>
            <p class="text-[11px] text-slate-500">Tahan pengiriman barang armada gudang jika kepastian pembayaran/DP bermasalah.</p>
          </div>
          <button
            type="button"
            class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-xs transition-colors shadow-2xs"
            @click="openHoldModal"
          >
            ⛔ Tahan Pengiriman
          </button>
        </div>

        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 flex justify-between items-center">
          <div>
            <span class="text-slate-500 block">Status Saat Ini</span>
            <div class="mt-1 flex items-center gap-1.5">
              <StatusBadge :status="selectedOrder.status" />
              <span v-if="selectedOrder.isHold" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">
                Ditahan
              </span>
            </div>
          </div>
          <div class="text-right">
            <span class="text-slate-500 block">Grand Total</span>
            <span class="text-base font-bold font-mono text-slate-900 mt-0.5 block">
              Rp {{ Number(selectedOrder?.grandTotal || 0).toLocaleString('id-ID') }}
            </span>
          </div>
        </div>

        <!-- ASSOCIATED INVOICE / PAYMENT INSIGHT FOR DIRECTOR -->
        <div v-if="associatedInvoice" class="p-4 bg-blue-50/60 rounded-xl border border-blue-200 space-y-2">
          <div class="flex items-center justify-between">
            <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px]">Status Tagihan & Piutang (Finance)</span>
            <StatusBadge :status="associatedInvoice.status" />
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 text-slate-700">
            <div>
              <span class="text-slate-500 text-[10px] block">No. Invoice</span>
              <span class="font-mono font-bold">{{ associatedInvoice.invoiceNumber }}</span>
            </div>
            <div>
              <span class="text-slate-500 text-[10px] block">Total Tagihan</span>
              <span class="font-mono font-bold">Rp {{ Number(associatedInvoice.totalAmount || 0).toLocaleString('id-ID') }}</span>
            </div>
            <div>
              <span class="text-slate-500 text-[10px] block">Telah Masuk</span>
              <span class="font-mono font-bold text-emerald-600">Rp {{ Number(associatedInvoice.paidAmount || 0).toLocaleString('id-ID') }}</span>
            </div>
            <div>
              <span class="text-slate-500 text-[10px] block">Sisa Piutang</span>
              <span class="font-mono font-bold text-rose-600">Rp {{ Number(associatedInvoice.remainingAmount || 0).toLocaleString('id-ID') }}</span>
            </div>
          </div>
        </div>

        <!-- MARKETING FOLLOW-UP RECORDS -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
          <h4 class="font-bold uppercase text-slate-700 tracking-wider text-[11px]">Riwayat Follow-Up Marketing ke Client</h4>
          <div v-if="selectedOrder.followUpLogs && selectedOrder.followUpLogs.length > 0" class="space-y-2 max-h-48 overflow-y-auto pr-1">
            <div
              v-for="log in selectedOrder.followUpLogs"
              :key="log.id"
              class="p-2.5 bg-white rounded-lg border border-slate-200 text-xs space-y-1"
            >
              <div class="flex items-center justify-between text-[11px]">
                <span class="font-bold text-slate-800">{{ log.contactPerson }} ({{ log.method }})</span>
                <span class="text-slate-400 font-mono">{{ log.date }}</span>
              </div>
              <p class="text-slate-700">{{ log.outcome }}</p>
              <div class="text-[11px] text-slate-500 pt-1 border-t border-slate-100 flex justify-between">
                <span>Janji Bayar: <strong>{{ log.promisedPaymentDate || '-' }}</strong></span>
                <span class="italic text-[10px]">{{ log.recordedBy }}</span>
              </div>
            </div>
          </div>
          <div v-else class="text-slate-400 italic text-[11px]">
            Belum ada catatan follow-up dari Marketing untuk pesanan ini.
          </div>
        </div>

        <DocumentChecklist :documents="selectedOrder.documents" />

        <div>
          <h4 class="font-bold uppercase text-slate-500 tracking-wider mb-3">Timeline Lengkap</h4>
          <Timeline :timeline="selectedOrder.timeline" />
        </div>
      </div>
    </Drawer>

    <!-- HOLD ORDER MODAL -->
    <Modal
      v-model="holdModalOpen"
      title="Tahan Pengiriman Pesanan (Hold Order)"
      subtitle="Pengiriman armada gudang akan otomatis diblokir sampai tahanan dilepas."
      size="md"
    >
      <form class="space-y-4 text-xs" @submit.prevent="submitHold">
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Alasan Penahanan Pengiriman</label>
          <textarea
            v-model="holdReasonInput"
            required
            rows="3"
            placeholder="Contoh: Menunggu kepastian pembayaran termin 1 / DP 50% dari client sebelum pengiriman semen & pipa diberangkatkan."
            class="w-full px-3 py-2 border rounded-lg bg-white"
          ></textarea>
        </div>

        <div class="p-3 bg-amber-50 rounded-lg border border-amber-200 text-amber-800 text-[11px]">
          ⚠️ <strong>Konsekuensi:</strong> Modul Pengiriman Gudang (Warehouse) akan memblokir keberangkatan pengiriman untuk order ini dan notifikasi prioritas akan dikirimkan ke Marketing & Gudang.
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button type="button" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg" @click="holdModalOpen = false">Batal</button>
          <button type="submit" class="px-4 py-1.5 bg-rose-600 text-white font-bold rounded-lg hover:bg-rose-700">Tahan Pengiriman Sekarang</button>
        </div>
      </form>
    </Modal>

    <!-- RELEASE HOLD MODAL -->
    <Modal
      v-model="releaseModalOpen"
      title="Lepas Tahanan Pengiriman (Release Hold)"
      subtitle="Izinkan divisi gudang & logistik untuk melanjutkan jadwal pengiriman barang."
      size="md"
    >
      <form class="space-y-4 text-xs" @submit.prevent="submitRelease">
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Catatan Direktur (Opsional)</label>
          <textarea
            v-model="releaseNotesInput"
            rows="3"
            placeholder="Contoh: Pembayaran DP telah terverifikasi oleh Finance. Pengiriman diizinkan jalan."
            class="w-full px-3 py-2 border rounded-lg bg-white"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button type="button" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg" @click="releaseModalOpen = false">Batal</button>
          <button type="submit" class="px-4 py-1.5 bg-emerald-600 text-white font-bold rounded-lg hover:bg-emerald-700">Lepas Tahanan & Izinkan Pengiriman</button>
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
import Drawer from '../../components/common/Drawer.vue';
import Modal from '../../components/common/Modal.vue';
import DocumentChecklist from '../../components/common/DocumentChecklist.vue';
import Timeline from '../../components/common/Timeline.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import { Search } from 'lucide-vue-next';

const toast = useToast();

const searchQuery = ref('');
const statusFilter = ref('');
const drawerOpen = ref(false);
const selectedOrder = ref(null);

const holdModalOpen = ref(false);
const holdReasonInput = ref('');

const releaseModalOpen = ref(false);
const releaseNotesInput = ref('');

const countStatus = (s) => store.orders.filter((o) => o.status === s).length;

const filteredOrders = computed(() => {
  return store.orders.filter((o) => {
    const q = searchQuery.value.toLowerCase();
    const matchSearch =
      !q ||
      o.orderNumber.toLowerCase().includes(q) ||
      o.clientName.toLowerCase().includes(q) ||
      o.projectName.toLowerCase().includes(q);
    const matchStatus = !statusFilter.value || o.status === statusFilter.value;
    return matchSearch && matchStatus;
  });
});

const canHoldOrder = computed(() => {
  if (!selectedOrder.value) return false;
  return !selectedOrder.value.isHold && !['Draft', 'Ditolak', 'Selesai'].includes(selectedOrder.value.status);
});

const associatedInvoice = computed(() => {
  if (!selectedOrder.value) return null;
  return store.invoices.find((i) => i.orderNumber === selectedOrder.value.orderNumber) || null;
});

const openDetail = (order) => {
  selectedOrder.value = order;
  drawerOpen.value = true;
};

const openHoldModal = () => {
  holdReasonInput.value = 'Menunggu kepastian pembayaran / DP client sebelum barang dikirim.';
  holdModalOpen.value = true;
};

const submitHold = () => {
  if (!selectedOrder.value) return;
  storeActions.holdOrder(selectedOrder.value.id, holdReasonInput.value);
  toast.warning(`Order ${selectedOrder.value.orderNumber} berhasil ditahan. Pengiriman gudang diblokir.`);
  holdModalOpen.value = false;
};

const openReleaseModal = () => {
  releaseNotesInput.value = 'Instruksi pengiriman dapat dilanjutkan.';
  releaseModalOpen.value = true;
};

const submitRelease = () => {
  if (!selectedOrder.value) return;
  storeActions.releaseOrderHold(selectedOrder.value.id, releaseNotesInput.value);
  toast.success(`Tahanan untuk ${selectedOrder.value.orderNumber} dilepas. Pengiriman diizinkan.`);
  releaseModalOpen.value = false;
};
</script>
