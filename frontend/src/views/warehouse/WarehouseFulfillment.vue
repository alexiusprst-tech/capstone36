<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pemenuhan Barang & Pemeriksaan Fisik</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pemeriksaan ketersediaan stok fisik gudang, penerimaan barang supplier, dan alokasi pesanan (PROBIS TO-BE).
          </p>
        </div>
      </div>
    </template>

    <!-- Context Info PROBIS TO-BE -->
    <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-3.5 mb-5 text-xs text-slate-700">
      <div class="flex items-start gap-2.5">
        <Info class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
        <p class="leading-relaxed">
          <strong>Tahap 5 PROBIS TO-BE:</strong> Warehouse menerima dan memeriksa kesesuaian fisik barang pesanan. Jika barang <strong>sesuai & lengkap</strong>, konfirmasi siap kirim untuk meneruskan notifikasi ke Marketing. Jika terdapat <strong>ketidaksesuaian/kerusakan</strong>, laporkan ke Finance agar segera ditindaklanjuti ke pihak Supplier.
        </p>
      </div>
    </div>

    <!-- SEKSI 1: PENERIMAAN FISIK BARANG SUPPLIER (PROBIS TO-BE TAHAP 4 & 5) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden mb-6">
      <div class="h-1 bg-amber-500 w-full"></div>
      <div class="p-4 bg-amber-50/60 border-b border-amber-200/80 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <Truck class="w-4 h-4 text-amber-600" />
          <h3 class="font-bold text-slate-900 text-xs sm:text-sm">
            Penerimaan Fisik Barang dari Supplier (Surat Jalan Masuk)
          </h3>
        </div>
        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-200">
          {{ incomingSupplierShipments.length }} Kiriman Vendor Menuju Gudang
        </span>
      </div>

      <div v-if="incomingSupplierShipments.length === 0" class="p-6 text-center text-slate-500 text-xs">
        <p>Tidak ada pengiriman supplier yang sedang dalam perjalanan menuju gudang saat ini.</p>
        <p v-if="preparingPurchasesCount > 0" class="text-[11px] text-amber-700/80 font-medium mt-1">
          ({{ preparingPurchasesCount }} pesanan PO sedang dipersiapkan oleh vendor di Portal Supplier)
        </p>
      </div>

      <div v-else class="divide-y divide-slate-100">
        <div
          v-for="p in incomingSupplierShipments"
          :key="p.purchase_code"
          class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 hover:bg-slate-50/60 transition-colors"
        >
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="font-mono font-bold text-slate-900 text-xs">{{ p.delivery_info?.surat_jalan || 'SJ-SUP' }}</span>
              <span class="px-2 py-0.2 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800">
                PO: {{ p.purchase_code }}
              </span>
              <span class="text-xs font-semibold text-slate-700">&bull; {{ p.default_supplier }}</span>
            </div>
            <p class="text-xs text-slate-600">
              Proyek: <strong>{{ p.project_name }}</strong> &bull; Ekspedisi: {{ p.delivery_info?.courier }} ({{ p.delivery_info?.tracking_number }})
            </p>
            <div class="mt-1 flex flex-wrap gap-1">
              <span
                v-for="(it, idx) in p.items"
                :key="idx"
                class="text-[10px] bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-mono"
              >
                {{ it.barang || it.name }} ({{ it.qty }} unit)
              </span>
            </div>
          </div>

          <!-- Actions: Terima Fisik -->
          <div class="flex items-center gap-2 shrink-0">
            <button
              type="button"
              class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
              title="Laporkan jika barang supplier rusak/tidak sesuai (Tahap 5b)"
              @click="openShipmentDiscrepancyModal(p)"
            >
              ⚠ Cacat / Retur
            </button>
            <button
              type="button"
              class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-2xs transition-colors cursor-pointer"
              @click="openConfirmReceiptModal(p)"
            >
              ✓ Terima & ACC Fisik Masuk Stok
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Fulfillment Queue Table -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
        <h3 class="font-bold text-slate-900 text-sm">Daftar Antrean Pemeriksaan & Pemenuhan Barang</h3>
        <span class="text-xs text-slate-500">Total {{ store.warehouseFulfillments?.length || 0 }} Antrean</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">No. Order</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">Rincian Barang</th>
              <th class="py-3 px-4 text-center">Required Qty</th>
              <th class="py-3 px-4 text-center">Available Qty</th>
              <th class="py-3 px-4 text-center">Fulfilled Qty</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="ful in store.warehouseFulfillments" :key="ful.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4">
                <span class="font-mono font-bold text-slate-900 block">{{ ful.orderNumber }}</span>
                <span
                  v-if="getOrderHold(ful.orderNumber)"
                  class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200 mt-0.5"
                  :title="getOrderHold(ful.orderNumber)?.holdReason"
                >
                  ⛔ Ditahan Direktur
                </span>
              </td>
              <td class="py-3.5 px-4 font-bold text-slate-800">{{ ful.clientName }}</td>
              <td class="py-3.5 px-4">
                <div v-for="it in ful.items" :key="it.sku" class="text-xs">
                  <span class="font-medium text-slate-900">{{ it.name }}</span>
                </div>
              </td>
              <td class="py-3.5 px-4 text-center font-mono">
                <div v-for="it in ful.items" :key="it.sku">
                  {{ it.reqQty }} {{ it.unit }}
                </div>
              </td>
              <td class="py-3.5 px-4 text-center font-mono text-emerald-600 font-bold">
                <div v-for="it in ful.items" :key="it.sku">
                  {{ it.availQty }} {{ it.unit }}
                </div>
              </td>
              <td class="py-3.5 px-4 text-center font-mono text-slate-900 font-bold">
                <div v-for="it in ful.items" :key="it.sku">
                  {{ it.fulfilledQty }} {{ it.unit }}
                </div>
              </td>
              <td class="py-3.5 px-4 text-center">
                <span
                  v-if="ful.status === 'Ketidaksesuaian'"
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200"
                >
                  ⚠ Ketidaksesuaian
                </span>
                <StatusBadge v-else :status="ful.status" />
              </td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div v-if="getOrderHold(ful.orderNumber)">
                  <span
                    class="text-rose-700 font-bold text-xs bg-rose-50 px-2.5 py-1 rounded border border-rose-200 inline-block cursor-help"
                    :title="getOrderHold(ful.orderNumber)?.holdReason"
                  >
                    ⛔ Ditahan Direktur
                  </span>
                </div>
                <div v-else class="flex items-center justify-end gap-1.5">
                  <!-- Button Laporkan Ketidaksesuaian ke Finance -->
                  <button
                    v-if="ful.status === 'Diproses'"
                    type="button"
                    class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-300 rounded font-semibold text-xs inline-flex items-center gap-1 transition-colors cursor-pointer"
                    title="Laporkan jika barang pesanan supplier cacat/tidak sesuai (Notifikasi ke Finance)"
                    @click="openDiscrepancyModal(ful)"
                  >
                    <AlertTriangle class="w-3.5 h-3.5 text-amber-600" />
                    <span>Lapor Ketidaksesuaian</span>
                  </button>

                  <!-- Button Konfirmasi Siap Kirim -->
                  <button
                    v-if="ful.status === 'Diproses'"
                    type="button"
                    class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold text-xs shadow-2xs transition-colors cursor-pointer"
                    title="Konfirmasi kesesuaian fisik barang dan teruskan notifikasi ke Marketing & Administrasi"
                    @click="confirmFulfillment(ful)"
                  >
                    Konfirmasi Siap Kirim
                  </button>
                  <span
                    v-else-if="ful.status === 'Ketidaksesuaian'"
                    class="text-amber-800 font-semibold text-xs bg-amber-50 px-2.5 py-1 rounded border border-amber-200"
                  >
                    Menunggu Tindak Lanjut Finance
                  </span>
                  <span
                    v-else
                    class="text-emerald-700 font-bold text-xs bg-emerald-50 px-2.5 py-1 rounded border border-emerald-200"
                  >
                    Siap Kirim ✓
                  </span>
                </div>
              </td>
            </tr>

            <tr v-if="!store.warehouseFulfillments || store.warehouseFulfillments.length === 0">
              <td colspan="8" class="py-12 text-center text-slate-400">
                Tidak ada data antrean pemenuhan barang aktif.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL LAPOR KETIDAKSESUAIAN BARANG (NOTIFIKASI KE FINANCE) -->
    <Modal
      v-model="discrepancyModalOpen"
      title="Laporkan Ketidaksesuaian Barang ke Finance"
      subtitle="Sesuai PROBIS TO-BE: Divisi Finance akan menindaklanjuti retur/penggantian ke Supplier"
      max-width="md"
    >
      <div v-if="selectedDiscrepancyItem" class="space-y-4 text-xs">
        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-900 space-y-1">
          <p class="font-bold">Order: {{ selectedDiscrepancyItem.orderNumber }} ({{ selectedDiscrepancyItem.clientName }})</p>
          <p class="text-[11px] text-amber-800">
            Laporan ini akan langsung dikirimkan ke <strong>Notifikasi Role Finance</strong> sebagai dasar penanganan ketidaksesuaian barang supplier.
          </p>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">
            Rincian Ketidaksesuaian / Kerusakan Barang <span class="text-rose-500">*</span>
          </label>
          <textarea
            v-model="discrepancyReason"
            rows="3"
            placeholder="Contoh: Spesifikasi RAM komputer tidak sesuai PO (diterima 8GB dari pesanan 16GB) atau terdapat cacat fisik pada kemasan..."
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-amber-500 text-slate-800"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button
            type="button"
            class="px-3.5 py-1.5 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 cursor-pointer"
            @click="discrepancyModalOpen = false"
          >
            Batal
          </button>
          <button
            type="button"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg shadow-2xs cursor-pointer"
            @click="submitDiscrepancyReport"
          >
            Kirim Laporan ke Finance
          </button>
        </div>
      </div>
    </Modal>
    <!-- MODAL 2: LAPOR CACAT / KETIDAKSESUAIAN BARANG SUPPLIER (PROBIS TO-BE TAHAP 5b) -->
    <Modal
      v-model="shipmentDiscrepancyModalOpen"
      title="Laporkan Ketidaksesuaian / Kerusakan Fisik Barang Supplier"
      subtitle="Sesuai PROBIS TO-BE Tahap 5b: Laporan diteruskan ke Finance untuk proses retur/penggantian ke Supplier"
      max-width="md"
    >
      <div v-if="selectedShipmentForDiscrepancy" class="space-y-4 text-xs">
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-900 space-y-1">
          <p class="font-bold">PO: {{ selectedShipmentForDiscrepancy.purchase_code }} (Vendor: {{ selectedShipmentForDiscrepancy.default_supplier }})</p>
          <p class="text-[11px] text-rose-800">
            Surat Jalan: {{ selectedShipmentForDiscrepancy.delivery_info?.surat_jalan || '-' }} &bull; Proyek: {{ selectedShipmentForDiscrepancy.project_name }}
          </p>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">
            Rincian Kerusakan / Ketidaksesuaian Fisik <span class="text-rose-500">*</span>
          </label>
          <textarea
            v-model="shipmentDiscrepancyReason"
            rows="3"
            placeholder="Jelaskan ketidaksesuaian (misal: barang patah, segel terbuka, spesifikasi beda dengan PO, dsb)..."
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-rose-500 text-slate-800"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button
            type="button"
            class="px-3.5 py-1.5 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 cursor-pointer"
            @click="shipmentDiscrepancyModalOpen = false"
          >
            Batal
          </button>
          <button
            type="button"
            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg shadow-2xs cursor-pointer"
            @click="submitShipmentDiscrepancy"
          >
            Kirim Laporan Retur
          </button>
        </div>
      </div>
    </Modal>
    <!-- MODAL 3: KONFIRMASI PENERIMAAN FISIK BARANG & REAL-TIME STOK ACC -->
    <Modal
      v-model="receiptModalOpen"
      title="Konfirmasi Penerimaan Fisik Barang & Update Stok (ACC)"
      :subtitle="`PO: ${selectedShipmentForReceipt?.purchase_code || ''} • Supplier: ${selectedShipmentForReceipt?.default_supplier || ''}`"
      size="lg"
    >
      <div v-if="selectedShipmentForReceipt" class="space-y-4 text-xs">
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-950 flex items-center justify-between">
          <div>
            <span class="font-bold text-sm block">Verifikasi Fisik Kedatangan Barang Supplier</span>
            <span class="text-[11px] text-emerald-800">
              Proyek: {{ selectedShipmentForReceipt.project_name }} • Surat Jalan: {{ selectedShipmentForReceipt.delivery_info?.surat_jalan || 'SJ-VENDOR' }}
            </span>
          </div>
          <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 font-bold rounded-full text-[10px] border border-emerald-300">
            Real-Time Stock Update
          </span>
        </div>

        <!-- Real-Time Stock Increment Table -->
        <div>
          <label class="block font-bold text-slate-700 mb-1.5 uppercase text-[11px]">
            Simulasi Penambahan Stok Fisik Gudang Secara Real-Time:
          </label>
          <div class="border border-slate-200 rounded-lg overflow-hidden bg-white shadow-2xs">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-[11px]">
                <tr>
                  <th class="p-2.5">Item Barang</th>
                  <th class="p-2.5 text-center">Jumlah Diterima</th>
                  <th class="p-2.5 text-center">Stok Sebelum</th>
                  <th class="p-2.5 text-center">Stok Sesudah (ACC)</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(it, idx) in selectedShipmentForReceipt.items" :key="idx">
                  <td class="p-2.5">
                    <span class="font-medium text-slate-900 block">{{ it.barang || it.name }}</span>
                    <span class="font-mono text-[10px] text-slate-400">{{ it.sku || '-' }}</span>
                  </td>
                  <td class="p-2.5 text-center font-bold text-emerald-700 font-mono">
                    +{{ it.qty }} {{ it.unit || 'Unit' }}
                  </td>
                  <td class="p-2.5 text-center font-mono text-slate-500">
                    {{ getStockProjection(it).currentStock }} {{ getStockProjection(it).unit }}
                  </td>
                  <td class="p-2.5 text-center font-mono font-bold text-emerald-800 bg-emerald-50/60">
                    {{ getStockProjection(it).newStock }} {{ getStockProjection(it).unit }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">
            Catatan Pemeriksaan Fisik Gudang:
          </label>
          <input
            v-model="receiptNotes"
            type="text"
            placeholder="Kondisi kemasan utuh, segel baik, dan barang diverifikasi lengkap..."
            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs bg-white text-slate-800 focus:ring-1 focus:ring-emerald-500"
          />
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button
            type="button"
            class="px-3.5 py-1.5 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 cursor-pointer font-medium"
            @click="receiptModalOpen = false"
          >
            Batal
          </button>
          <button
            type="button"
            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-2xs cursor-pointer flex items-center gap-1.5"
            @click="executeConfirmReceipt"
          >
            <CheckCircle2 class="w-4 h-4" />
            <span>ACC & Tambahkan ke Stok Gudang Sekarang</span>
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
import { Info, AlertTriangle, Truck } from 'lucide-vue-next';

const toast = useToast();

const discrepancyModalOpen = ref(false);
const selectedDiscrepancyItem = ref(null);
const discrepancyReason = ref('');

const shipmentDiscrepancyModalOpen = ref(false);
const selectedShipmentForDiscrepancy = ref(null);
const shipmentDiscrepancyReason = ref('');

const receiptModalOpen = ref(false);
const selectedShipmentForReceipt = ref(null);
const receiptNotes = ref('Barang fisik telah diperiksa lengkap, sesuai spesifikasi PO, dan segel baik.');

const incomingSupplierShipments = computed(() => {
  return (store.purchases || []).filter(
    (p) => p.status !== 'DITERIMA_GUDANG' &&
           p.supplier_status !== 'Diterima Gudang (Lengkap & Sesuai)' &&
           p.supplier_status !== 'Diterima Gudang'
  );
});

const receivedSupplierShipments = computed(() => {
  return (store.purchases || []).filter(
    (p) => p.status === 'DITERIMA_GUDANG' ||
           p.supplier_status === 'Diterima Gudang (Lengkap & Sesuai)' ||
           p.supplier_status === 'Diterima Gudang'
  );
});

const preparingPurchasesCount = computed(() => {
  return (store.purchases || []).filter(
    (p) => !p.supplier_status || p.supplier_status === 'Menunggu Konfirmasi' || p.supplier_status === 'Dikonfirmasi Supplier'
  ).length;
});

const getStockProjection = (item) => {
  const rawName = item.name || item.barang || '';
  const cleanName = rawName.replace(/^[A-Z0-9_\-]+\s*-\s*/, '').trim();
  const sku = (item.sku && item.sku !== '-') ? item.sku : (rawName.includes(' - ') ? rawName.split(' - ')[0].trim() : '');
  const qty = Number(item.qty) || 0;

  const prod = (store.products || []).find((pr) => {
    if (sku && pr.sku && pr.sku.toLowerCase() === sku.toLowerCase()) return true;
    if (cleanName && pr.name && pr.name.toLowerCase() === cleanName.toLowerCase()) return true;
    if (rawName && pr.name && pr.name.toLowerCase() === rawName.toLowerCase()) return true;
    if (pr.name && cleanName && pr.name.toLowerCase().includes(cleanName.toLowerCase())) return true;
    return false;
  });

  const currentStock = prod ? Number(prod.stock || 0) : 0;
  const newStock = currentStock + qty;
  return {
    found: !!prod,
    currentStock,
    newStock,
    unit: prod?.unit || item.unit || 'Unit',
  };
};

const openConfirmReceiptModal = (p) => {
  selectedShipmentForReceipt.value = p;
  receiptNotes.value = 'Barang fisik telah diperiksa lengkap, sesuai spesifikasi PO, dan segel baik.';
  receiptModalOpen.value = true;
};

const executeConfirmReceipt = () => {
  if (!selectedShipmentForReceipt.value) return;
  const p = selectedShipmentForReceipt.value;
  storeActions.confirmSupplierReceipt(p.purchase_code, { notes: receiptNotes.value });
  toast.success(`Barang PO ${p.purchase_code} dari Supplier ${p.default_supplier} BERHASIL DI-ACC! Data stok fisik otomatis bertambah secara real-time.`);
  receiptModalOpen.value = false;
};

const confirmSupplierReceipt = (p) => {
  openConfirmReceiptModal(p);
};

const openShipmentDiscrepancyModal = (p) => {
  selectedShipmentForDiscrepancy.value = p;
  shipmentDiscrepancyReason.value = '';
  shipmentDiscrepancyModalOpen.value = true;
};

const submitShipmentDiscrepancy = () => {
  if (!shipmentDiscrepancyReason.value.trim()) {
    toast.error('Harap masukkan alasan ketidaksesuaian/kerusakan barang.');
    return;
  }
  storeActions.reportSupplierShipmentDiscrepancy(
    selectedShipmentForDiscrepancy.value.purchase_code,
    shipmentDiscrepancyReason.value.trim()
  );
  toast.warning(`Ketidaksesuaian barang PO ${selectedShipmentForDiscrepancy.value.purchase_code} berhasil dilaporkan ke Finance & Supplier.`);
  shipmentDiscrepancyModalOpen.value = false;
};

const getOrderHold = (orderNumber) => {
  const o = store.orders.find((ord) => ord.orderNumber === orderNumber);
  return o && o.isHold ? o : null;
};

const confirmFulfillment = (ful) => {
  const hold = getOrderHold(ful.orderNumber);
  if (hold) {
    toast.error(`Order ${ful.orderNumber} sedang DITAHAN oleh Direktur: ${hold.holdReason}. Pemenuhan pengiriman ditangguhkan!`);
    return;
  }

  storeActions.confirmFulfillment(ful.id);
  toast.success(`Pemenuhan order ${ful.orderNumber} berhasil dikonfirmasi. Notifikasi diteruskan ke Marketing & Administrasi.`);
};

const openDiscrepancyModal = (ful) => {
  selectedDiscrepancyItem.value = ful;
  discrepancyReason.value = '';
  discrepancyModalOpen.value = true;
};

const submitDiscrepancyReport = () => {
  if (!discrepancyReason.value.trim()) {
    toast.error('Harap isi rincian ketidaksesuaian barang.');
    return;
  }

  storeActions.reportSupplierDiscrepancy(selectedDiscrepancyItem.value.id, discrepancyReason.value.trim());
  toast.warning(`Ketidaksesuaian order ${selectedDiscrepancyItem.value.orderNumber} berhasil dilaporkan ke Finance.`);
  discrepancyModalOpen.value = false;
};
</script>
