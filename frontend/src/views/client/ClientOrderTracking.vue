<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Tracking Pesanan</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pantau progres real-time setiap pesanan instansi Anda: dari pembuatan order hingga barang diterima dan lunas.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <router-link
            to="/client/payments"
            class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
          >
            <CreditCard class="w-4 h-4" />
            <span>Lihat Info Pembayaran</span>
          </router-link>
        </div>
      </div>
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- LEFT COLUMN: ORDER LIST & SEARCH -->
      <div class="lg:col-span-1 space-y-4">
        <!-- Search & Filter -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-xs space-y-3">
          <div class="relative">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari no. order atau proyek..."
              class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-alpharo-500"
            />
          </div>

          <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-[11px]">
            <button
              v-for="tab in filterTabs"
              :key="tab.key"
              type="button"
              :class="[
                activeTab === tab.key
                  ? 'bg-alpharo-500 text-white font-bold'
                  : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
                'px-2.5 py-1 rounded-full whitespace-nowrap transition-colors'
              ]"
              @click="activeTab = tab.key"
            >
              {{ tab.label }} ({{ getTabCount(tab.key) }})
            </button>
          </div>
        </div>

        <!-- Orders Cards -->
        <div class="space-y-3 max-h-[calc(100vh-280px)] overflow-y-auto pr-1">
          <button
            v-for="order in filteredOrders"
            :key="order.id"
            type="button"
            :class="[
              selectedOrder && selectedOrder.id === order.id
                ? 'border-alpharo-500 ring-2 ring-alpharo-500/20 bg-blue-50/30'
                : 'border-slate-200 hover:border-slate-300 bg-white',
              'w-full text-left rounded-xl border p-4 shadow-xs transition-all relative overflow-hidden'
            ]"
            @click="selectedOrder = order"
          >
            <div class="flex items-center justify-between gap-2">
              <span class="font-mono font-bold text-slate-900 text-xs">{{ order.orderNumber }}</span>
              <StatusBadge :status="order.isHold ? 'Ditahan' : order.status" />
            </div>

            <p class="text-xs font-semibold text-slate-800 mt-2 line-clamp-1">{{ order.projectName }}</p>
            
            <div class="flex items-center justify-between text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">
              <span>{{ order.date }}</span>
              <span class="font-mono font-bold text-slate-900">
                Rp {{ (order.grandTotal || 0).toLocaleString('id-ID') }}
              </span>
            </div>

            <!-- Hold Flag -->
            <div
              v-if="order.isHold"
              class="mt-2 text-[10px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 inline-block"
            >
              ⚠ Pengiriman Ditahan
            </div>
          </button>

          <div
            v-if="filteredOrders.length === 0"
            class="bg-white rounded-xl border border-slate-200/90 p-8 shadow-xs text-center text-slate-400 text-xs"
          >
            Tidak ada pesanan yang sesuai filter.
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: ORDER DETAIL & TRACKING -->
      <div class="lg:col-span-2">
        <div v-if="selectedOrder" class="space-y-6">
          <!-- ORDER SUMMARY CARD -->
          <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 pb-4 border-b border-slate-100">
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-mono font-bold text-slate-900 text-base">{{ selectedOrder.orderNumber }}</span>
                  <StatusBadge :status="selectedOrder.isHold ? 'Ditahan' : selectedOrder.status" />
                </div>
                <h3 class="text-sm font-bold text-slate-800">{{ selectedOrder.projectName }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">Diajukan: {{ selectedOrder.date }} &bull; Oleh: {{ selectedOrder.submittedBy || 'Marketing' }}</p>
              </div>

              <div class="text-left sm:text-right">
                <p class="text-[11px] text-slate-400 uppercase font-semibold">Total Nilai Pesanan</p>
                <p class="text-lg font-bold font-mono text-slate-900">
                  Rp {{ (selectedOrder.grandTotal || 0).toLocaleString('id-ID') }}
                </p>
                <p class="text-[11px] text-slate-500">Termasuk PPN 11%</p>
              </div>
            </div>

            <!-- HOLD ALERT -->
            <div
              v-if="selectedOrder.isHold"
              class="mt-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 flex items-start gap-3"
            >
              <AlertTriangle class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
              <div>
                <p class="font-bold">Pengiriman Ditahan Sementara (Hold Status)</p>
                <p class="mt-0.5 leading-relaxed">{{ selectedOrder.holdReason || 'Menunggu konfirmasi atau penyelesaian kewajiban pembayaran termin.' }}</p>
                <p class="text-[11px] text-rose-600 mt-1">Ditahan oleh {{ selectedOrder.holdBy || 'Direktur' }} pada {{ selectedOrder.holdDate || 'Hari ini' }}. Silakan hubungi bagian Finance untuk tindak lanjut.</p>
              </div>
            </div>

            <!-- TRACKING STEPPER PIPELINE -->
            <div class="mt-6">
              <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4">
                Alur Tracking Tahapan Proyek
              </h4>

              <div class="relative">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                  <div
                    v-for="(step, idx) in pipelineStages"
                    :key="idx"
                    :class="[
                      step.isDone
                        ? 'bg-emerald-50 border-emerald-300 text-emerald-800'
                        : step.isCurrent
                        ? 'bg-blue-50 border-alpharo-500 ring-2 ring-alpharo-500/20 text-alpharo-700 font-bold'
                        : 'bg-slate-50 border-slate-200 text-slate-400',
                      'p-2.5 rounded-xl border text-center transition-all'
                    ]"
                  >
                    <div class="flex items-center justify-center mb-1.5">
                      <component
                        :is="step.isDone ? CheckCircle2 : step.isCurrent ? Clock : Circle"
                        :class="[
                          step.isDone ? 'text-emerald-600' : step.isCurrent ? 'text-alpharo-600 animate-pulse' : 'text-slate-300',
                          'w-4 h-4'
                        ]"
                      />
                    </div>
                    <p class="text-[10px] font-bold uppercase tracking-wider leading-tight">{{ step.title }}</p>
                    <p class="text-[9px] mt-1 leading-tight opacity-80">{{ step.desc }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- DETAILED TIMELINE & SHIPMENT LOGISTICS -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- LOGISTICS & DELIVERY INFO -->
            <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
              <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                <div class="flex items-center gap-2">
                  <Truck class="w-4 h-4 text-alpharo-500" />
                  <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Status Pengiriman Logistik</h4>
                </div>
                <span class="text-[11px] font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                  {{ shipmentInfo.status }}
                </span>
              </div>

              <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                  <span class="text-slate-500">No. Surat Jalan Gudang</span>
                  <span class="font-mono font-bold text-slate-900">{{ shipmentInfo.deliveryNoteNumber }}</span>
                </div>
                <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                  <span class="text-slate-500">Armada Pengantar</span>
                  <span class="font-semibold text-slate-800">{{ shipmentInfo.vehicle }}</span>
                </div>
                <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                  <span class="text-slate-500">Nama Pengemudi / Driver</span>
                  <span class="font-semibold text-slate-800">{{ shipmentInfo.driver }}</span>
                </div>
                <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                  <span class="text-slate-500">Tanggal Kirim</span>
                  <span class="font-medium text-slate-700">{{ shipmentInfo.date }}</span>
                </div>
                <div class="flex items-center justify-between py-1.5">
                  <span class="text-slate-500">Estimasi Tiba di Lokasi</span>
                  <span class="font-bold text-emerald-600">{{ shipmentInfo.eta }}</span>
                </div>
              </div>

              <div class="mt-4 p-3 rounded-lg bg-blue-50/60 border border-blue-100 text-[11px] text-blue-900 leading-relaxed">
                <p class="font-semibold">Konfirmasi Penerimaan Barang:</p>
                <p class="mt-0.5 text-blue-800">
                  Mohon pastikan personil sekolah / PIC instansi siap memeriksa kondisi fisik barang dan menandatangani berkas Berita Acara Serah Terima (BAST).
                </p>
              </div>
            </div>

            <!-- RIWAYAT LOG TIMELINE -->
            <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
              <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4 pb-3 border-b border-slate-100">
                Riwayat Jejak Waktu (Timeline)
              </h4>

              <div class="space-y-4 max-h-72 overflow-y-auto pr-1">
                <div
                  v-for="(t, tIdx) in (selectedOrder.timeline || [])"
                  :key="tIdx"
                  class="flex items-start gap-3 relative"
                >
                  <div class="w-6 h-6 rounded-full bg-blue-50 text-alpharo-500 flex items-center justify-center shrink-0 border border-blue-200">
                    <CheckCircle2 class="w-3.5 h-3.5" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                      <p class="text-xs font-bold text-slate-900 truncate">{{ t.stage }}</p>
                      <span class="text-[10px] text-slate-400 whitespace-nowrap">{{ t.timestamp }}</span>
                    </div>
                    <p class="text-[11px] text-slate-600 mt-0.5">{{ t.notes }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">Oleh: {{ t.user }}</p>
                  </div>
                </div>

                <div v-if="!selectedOrder.timeline || selectedOrder.timeline.length === 0" class="text-center text-xs text-slate-400 py-6">
                  Belum ada log timeline.
                </div>
              </div>
            </div>
          </div>

          <!-- ORDER ITEMS TABLE -->
          <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">
              Rincian Barang yang Dipesan
            </h4>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs border-collapse">
                <thead>
                  <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                    <th class="py-2.5 px-3">No</th>
                    <th class="py-2.5 px-3">Nama Barang & SKU</th>
                    <th class="py-2.5 px-3 text-center">Jumlah</th>
                    <th class="py-2.5 px-3 text-right">Harga Satuan</th>
                    <th class="py-2.5 px-3 text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr
                    v-for="(item, idx) in (selectedOrder.items || [])"
                    :key="idx"
                    class="hover:bg-slate-50/60 transition-colors"
                  >
                    <td class="py-3 px-3 text-slate-400 font-mono">{{ idx + 1 }}</td>
                    <td class="py-3 px-3">
                      <p class="font-bold text-slate-900">{{ item.name }}</p>
                      <p class="text-[11px] text-slate-400 font-mono">{{ item.sku }}</p>
                    </td>
                    <td class="py-3 px-3 text-center font-semibold text-slate-800">
                      {{ item.qty }} {{ item.unit }}
                    </td>
                    <td class="py-3 px-3 text-right font-mono text-slate-700 whitespace-nowrap">
                      Rp {{ (item.price || 0).toLocaleString('id-ID') }}
                    </td>
                    <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                      Rp {{ ((item.qty || 0) * (item.price || 0)).toLocaleString('id-ID') }}
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="bg-slate-50/80 font-bold text-slate-900 border-t border-slate-200">
                    <td colspan="4" class="py-2.5 px-3 text-right">Subtotal:</td>
                    <td class="py-2.5 px-3 text-right font-mono">
                      Rp {{ (selectedOrder.subtotal || 0).toLocaleString('id-ID') }}
                    </td>
                  </tr>
                  <tr class="bg-slate-50/80 text-slate-600 border-t border-slate-100">
                    <td colspan="4" class="py-2 px-3 text-right text-[11px]">PPN (11%):</td>
                    <td class="py-2 px-3 text-right font-mono text-[11px]">
                      Rp {{ (selectedOrder.tax || 0).toLocaleString('id-ID') }}
                    </td>
                  </tr>
                  <tr class="bg-blue-50/40 font-bold text-alpharo-600 border-t border-slate-200 text-sm">
                    <td colspan="4" class="py-3 px-3 text-right">Grand Total:</td>
                    <td class="py-3 px-3 text-right font-mono">
                      Rp {{ (selectedOrder.grandTotal || 0).toLocaleString('id-ID') }}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- DOCUMENT DOWNLOADS & ACTIONS -->
          <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-blue-50 text-alpharo-500 flex items-center justify-center shrink-0">
                <FileText class="w-5 h-5" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900">Dokumen SPJ & Faktur Terkait</p>
                <p class="text-[11px] text-slate-500">Unduh atau cetak dokumen resmi untuk keperluan arsip bendahara instansi.</p>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <button
                type="button"
                class="px-3 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors flex items-center gap-1.5"
                @click="downloadDocumentSimulation('Surat Jalan')"
              >
                <Download class="w-3.5 h-3.5" />
                <span>Surat Jalan</span>
              </button>
              <button
                type="button"
                class="px-3 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors flex items-center gap-1.5"
                @click="downloadDocumentSimulation('BAST')"
              >
                <Download class="w-3.5 h-3.5" />
                <span>BAST</span>
              </button>
              <router-link
                to="/client/payments"
                class="px-3 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors flex items-center gap-1.5"
              >
                <CreditCard class="w-3.5 h-3.5" />
                <span>Buka Invoice</span>
              </router-link>
            </div>
          </div>
        </div>

        <div
          v-else
          class="bg-white rounded-xl border border-slate-200/90 p-16 shadow-xs text-center text-slate-400 text-xs"
        >
          <PackageSearch class="w-12 h-12 text-slate-300 mx-auto mb-3" />
          <p class="font-bold text-slate-700 text-sm">Pilih Pesanan untuk Melacak</p>
          <p class="mt-1">Pilih salah satu pesanan pada daftar di sebelah kiri untuk melihat rincian progres lengkap.</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import { store } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import {
  CreditCard,
  Search,
  PackageSearch,
  Truck,
  CheckCircle2,
  Clock,
  Circle,
  FileText,
  Download,
  AlertTriangle,
} from 'lucide-vue-next';

const toast = useToast();

const searchQuery = ref('');
const activeTab = ref('all');

const clientId = computed(() => store.currentUser.clientId || 1);

const clientOrders = computed(() => {
  return store.orders.filter((o) => o.clientId === clientId.value);
});

const selectedOrder = ref(clientOrders.value[0] || null);

const filterTabs = [
  { key: 'all', label: 'Semua Pesanan' },
  { key: 'active', label: 'Sedang Diproses' },
  { key: 'completed', label: 'Selesai' },
  { key: 'hold', label: 'Ditahan' },
];

const getTabCount = (tabKey) => {
  if (tabKey === 'all') return clientOrders.value.length;
  if (tabKey === 'active') return clientOrders.value.filter((o) => o.status !== 'Selesai' && !o.isHold).length;
  if (tabKey === 'completed') return clientOrders.value.filter((o) => o.status === 'Selesai').length;
  if (tabKey === 'hold') return clientOrders.value.filter((o) => o.isHold).length;
  return 0;
};

const filteredOrders = computed(() => {
  let list = clientOrders.value;

  if (activeTab.value === 'active') {
    list = list.filter((o) => o.status !== 'Selesai' && !o.isHold);
  } else if (activeTab.value === 'completed') {
    list = list.filter((o) => o.status === 'Selesai');
  } else if (activeTab.value === 'hold') {
    list = list.filter((o) => o.isHold);
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter(
      (o) =>
        o.orderNumber.toLowerCase().includes(q) ||
        (o.projectName && o.projectName.toLowerCase().includes(q))
    );
  }

  return list;
});

// Calculate interactive pipeline stages
const pipelineStages = computed(() => {
  const o = selectedOrder.value;
  if (!o) return [];

  const status = o.status;
  const isHold = o.isHold;

  // Stages:
  // 1. Order Dibuat
  // 2. Verifikasi Direktur
  // 3. Dokumen SPJ
  // 4. Gudang & Packing
  // 5. Pengiriman
  // 6. BAST & Selesai
  return [
    {
      title: 'Order Dibuat',
      desc: o.date,
      isDone: true,
      isCurrent: false,
    },
    {
      title: 'Verifikasi Direktur',
      desc: status === 'Draft' || status === 'Menunggu Verifikasi' ? 'Review wewenang' : 'Telah disetujui',
      isDone: status !== 'Draft' && status !== 'Menunggu Verifikasi',
      isCurrent: status === 'Menunggu Verifikasi',
    },
    {
      title: 'Dokumen SPJ',
      desc: ['Diproses', 'Selesai', 'Menunggu Pembayaran'].includes(status) ? 'SPJ Diterbitkan' : 'Antrean',
      isDone: ['Diproses', 'Selesai', 'Menunggu Pembayaran'].includes(status),
      isCurrent: status === 'Disetujui',
    },
    {
      title: 'Alokasi Gudang',
      desc: o.fulfillmentStatus === 'Selesai' || status === 'Selesai' ? 'Barang siap' : 'Persiapan fisik',
      isDone: o.fulfillmentStatus === 'Selesai' || status === 'Selesai',
      isCurrent: status === 'Diproses' && o.fulfillmentStatus !== 'Selesai',
    },
    {
      title: 'Pengiriman',
      desc: isHold ? 'Ditahan sementara' : status === 'Selesai' ? 'Telah terkirim' : 'Perjalanan armada',
      isDone: status === 'Selesai',
      isCurrent: status === 'Diproses' && !isHold,
    },
    {
      title: 'Serah Terima (BAST)',
      desc: status === 'Selesai' ? 'Tuntas 100%' : 'Menunggu serah terima',
      isDone: status === 'Selesai',
      isCurrent: false,
    },
  ];
});

// Logistics Delivery Details Simulation based on order
const shipmentInfo = computed(() => {
  const o = selectedOrder.value;
  if (!o) {
    return {
      deliveryNoteNumber: '-',
      vehicle: '-',
      driver: '-',
      date: '-',
      eta: '-',
      status: 'Belum Dikirim',
    };
  }

  const deliveryNote = store.deliveryNotes.find((dn) => dn.orderNumber === o.orderNumber);
  const shipment = store.shipments.find((s) => s.orderNumber === o.orderNumber);

  return {
    deliveryNoteNumber: deliveryNote?.deliveryNoteNumber || `SJ-2026-${String(o.id).padStart(3, '0')}`,
    vehicle: shipment?.vehicle || 'Truk Isuzu Elf Box (B 9182 KXA)',
    driver: shipment?.driver || 'Bambang Kusumo (0812-9988-7711)',
    date: shipment?.departureTime ? shipment.departureTime.split(' ')[0] : o.date,
    eta: o.status === 'Selesai' ? 'Sudah Tiba & Diterima' : 'Estimasi: 1 - 2 Hari Kerja',
    status: o.isHold ? 'Ditahan (On Hold)' : o.status === 'Selesai' ? 'Tiba di Lokasi' : 'Dalam Pengiriman Armada',
  };
});

const downloadDocumentSimulation = (docType) => {
  toast.success(`Mengunduh dokumen digital ${docType} untuk ${selectedOrder.value.orderNumber}`);
};
</script>
