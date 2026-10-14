<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kelola Order & Proyek</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pengajuan pesanan barang/jasa supplier & kontraktor menuju proses verifikasi Direktur.
          </p>
        </div>
        <router-link
          to="/marketing/orders/create"
          class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-sm shadow-xs transition-colors"
        >
          <Plus class="w-4 h-4" />
          <span>Buat Order Baru</span>
        </router-link>
      </div>
    </template>

    <!-- SEARCH & FILTER -->
    <div class="bg-white p-4 rounded-xl border border-slate-200/90 mb-5 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-2xs">
      <div class="relative w-full sm:w-80">
        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari no order, client, proyek..."
          class="w-full pl-9 pr-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-500"
        />
      </div>

      <div class="flex items-center gap-3 w-full sm:w-auto">
        <select
          v-model="statusFilter"
          class="text-xs px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 focus:outline-hidden focus:ring-2 focus:ring-alpharo-500"
        >
          <option value="">Semua Status</option>
          <option value="Draft">Draft</option>
          <option value="Menunggu Verifikasi">Menunggu Verifikasi</option>
          <option value="Disetujui">Disetujui</option>
          <option value="Menunggu Pembayaran">Menunggu Pembayaran</option>
          <option value="Ditolak">Ditolak</option>
          <option value="Diproses">Diproses</option>
          <option value="Selesai">Selesai</option>
        </select>
      </div>
    </div>

    <!-- ORDERS TABLE -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
              <th class="py-3 px-4">No. Order</th>
              <th class="py-3 px-4">Client & Proyek</th>
              <th class="py-3 px-4">Tanggal</th>
              <th class="py-3 px-4 text-right">Nilai Transaksi</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-center">Kelengkapan</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="order in filteredOrders" :key="order.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                <div>{{ order.orderNumber }}</div>
                <div v-if="order.isHold" class="mt-0.5">
                  <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">
                    ⛔ Ditahan Direktur
                  </span>
                </div>
              </td>
              <td class="py-3.5 px-4">
                <span class="font-bold text-slate-900 block">{{ order.clientName }}</span>
                <span class="text-[11px] text-slate-400 block truncate max-w-xs">{{ order.projectName }}</span>
              </td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                {{ order.date }}
              </td>
              <td class="py-3.5 px-4 text-right font-bold text-slate-900 whitespace-nowrap">
                Rp {{ order.grandTotal.toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :status="order.status" />
              </td>
              <td class="py-3.5 px-4 text-center">
                <span class="text-[11px] font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                  {{ getDocCount(order.documents) }}/4 Dokumen
                </span>
              </td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    class="p-1.5 text-alpharo-500 hover:bg-blue-50 rounded transition-colors"
                    title="Lihat Detail"
                    @click="viewDetail(order)"
                  >
                    <Eye class="w-4 h-4" />
                  </button>

                  <router-link
                    v-if="order.status === 'Draft'"
                    :to="`/marketing/orders/${order.id}/edit`"
                    class="p-1.5 text-slate-600 hover:bg-slate-100 rounded transition-colors"
                    title="Edit Order"
                  >
                    <Pencil class="w-4 h-4" />
                  </router-link>

                  <button
                    v-if="order.status === 'Draft'"
                    type="button"
                    class="px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded text-[11px] font-bold flex items-center gap-1"
                    title="Ajukan Verifikasi"
                    @click="submitVerification(order)"
                  >
                    <Send class="w-3 h-3" /> Ajukan
                  </button>

                  <button
                    v-if="order.status === 'Draft'"
                    type="button"
                    class="p-1.5 text-rose-600 hover:bg-rose-50 rounded transition-colors"
                    title="Hapus Draft"
                    @click="confirmDelete(order)"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="filteredOrders.length === 0">
              <td colspan="7" class="py-12 text-center text-slate-400">
                Tidak ada data order yang sesuai.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CREATE ORDER MODAL (WITH 6 CLEAR SECTIONS) -->
    <Modal
      v-model="createModalOpen"
      title="Buat Order Baru (Supplier & Contractor)"
      subtitle="Isi rincian transaksi client, daftar item, dan kalkulasi nilai sebelum diajukan"
      size="2xl"
    >
      <form class="space-y-6" @submit.prevent="submitCreateOrder">
        <!-- SECTION 1: INFORMASI CLIENT -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-alpharo-500 text-white flex items-center justify-center text-[10px]">1</span>
            Informasi Client
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Rekanan Client</label>
              <select
                v-model="newOrder.clientId"
                required
                class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white"
              >
                <option value="" disabled>-- Pilih Client Terdaftar --</option>
                <option v-for="c in store.clients" :key="c.id" :value="c.id">
                  {{ c.code }} - {{ c.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Proyek / Kegiatan</label>
              <input
                v-model="newOrder.projectName"
                type="text"
                required
                placeholder="Contoh: Pekerjaan Pembangunan Gedung..."
                class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300"
              />
            </div>
          </div>
        </div>

        <!-- SECTION 2: INFORMASI ORDER -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-alpharo-500 text-white flex items-center justify-center text-[10px]">2</span>
            Informasi Order
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Transaksi</label>
              <input
                v-model="newOrder.date"
                type="date"
                required
                class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Staff Marketing Pembuat</label>
              <input
                :value="store.currentUser.name"
                disabled
                class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-500"
              />
            </div>
          </div>
        </div>

        <!-- SECTION 3: BARANG / JASA (DYNAMIC ROW SELECTOR) -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
              <span class="w-5 h-5 rounded-full bg-alpharo-500 text-white flex items-center justify-center text-[10px]">3</span>
              Barang / Jasa Yang Dipesan
            </h3>
            <button
              type="button"
              class="px-2.5 py-1 text-xs bg-white border border-slate-300 hover:bg-slate-100 font-semibold rounded text-slate-700 flex items-center gap-1"
              @click="addItemRow"
            >
              <Plus class="w-3.5 h-3.5" /> Tambah Baris Item
            </button>
          </div>

          <div class="space-y-2">
            <div
              v-for="(item, idx) in newOrder.items"
              :key="idx"
              class="p-2.5 bg-white rounded-lg border border-slate-200 grid grid-cols-12 gap-2 items-center"
            >
              <div class="col-span-12 sm:col-span-5">
                <select
                  v-model="item.productId"
                  required
                  class="w-full px-2 py-1.5 text-xs rounded border border-slate-300"
                  @change="onProductSelect(item)"
                >
                  <option value="" disabled>-- Pilih Barang dari Gudang --</option>
                  <option v-for="p in store.products" :key="p.id" :value="p.id">
                    [{{ p.sku }}] {{ p.name }} (Stok: {{ p.stock }} {{ p.unit }})
                  </option>
                </select>
              </div>

              <div class="col-span-4 sm:col-span-2">
                <input
                  v-model.number="item.qty"
                  type="number"
                  min="1"
                  required
                  placeholder="Qty"
                  class="w-full px-2 py-1.5 text-xs rounded border border-slate-300 text-center"
                />
              </div>

              <div class="col-span-4 sm:col-span-2">
                <input
                  v-model.number="item.price"
                  type="number"
                  required
                  placeholder="Harga"
                  class="w-full px-2 py-1.5 text-xs rounded border border-slate-300 text-right"
                />
              </div>

              <div class="col-span-3 sm:col-span-2 text-right font-mono font-bold text-xs text-slate-800 truncate">
                Rp {{ ((item.qty || 0) * (item.price || 0)).toLocaleString('id-ID') }}
              </div>

              <div class="col-span-1 text-center">
                <button
                  type="button"
                  class="text-rose-500 hover:text-rose-700 p-1"
                  :disabled="newOrder.items.length === 1"
                  @click="removeItemRow(idx)"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION 4: RINGKASAN NILAI ORDER -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-alpharo-500 text-white flex items-center justify-center text-[10px]">4</span>
            Ringkasan Nilai Order
          </h3>
          <div class="space-y-1.5 text-xs">
            <div class="flex justify-between text-slate-600">
              <span>Subtotal Item</span>
              <span class="font-mono">Rp {{ computedSubtotal.toLocaleString('id-ID') }}</span>
            </div>
            <div class="flex justify-between text-slate-600">
              <span>PPN 11%</span>
              <span class="font-mono">Rp {{ computedTax.toLocaleString('id-ID') }}</span>
            </div>
            <div class="pt-2 border-t border-slate-200 flex justify-between font-bold text-sm text-slate-900">
              <span>Total Nilai Transaksi</span>
              <span class="font-mono text-alpharo-600">Rp {{ computedGrandTotal.toLocaleString('id-ID') }}</span>
            </div>
          </div>
        </div>

        <!-- SECTION 5: CATATAN -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-alpharo-500 text-white flex items-center justify-center text-[10px]">5</span>
            Catatan Khusus & Ketentuan Pengiriman
          </h3>
          <textarea
            v-model="newOrder.notes"
            rows="2"
            placeholder="Kebutuhan armada tronton, jadwal dropping malam hari, instruksi koordinasi pengawas site..."
            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-alpharo-500"
          ></textarea>
        </div>

        <!-- SECTION 6: SUBMIT VERIFICATION -->
        <div class="p-4 bg-blue-50/60 rounded-xl border border-blue-200">
          <div class="flex items-start gap-3">
            <input
              id="submitDirectly"
              v-model="newOrder.submitDirectly"
              type="checkbox"
              class="mt-1 w-4 h-4 text-alpharo-500 rounded border-slate-300 focus:ring-alpharo-500"
            />
            <label for="submitDirectly" class="text-xs text-slate-800">
              <span class="font-bold block text-sm">Section 6: Ajukan Langsung untuk Verifikasi Direktur</span>
              <span class="text-slate-600">
                Jika dicentang, order langsung berstatus <strong>Menunggu Verifikasi</strong> dan muncul di meja approval Direktur. Jika tidak dicentang, order disimpan sebagai <strong>Draft</strong>.
              </span>
            </label>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button
            type="button"
            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg"
            @click="createModalOpen = false"
          >
            Batal
          </button>
          <button
            type="submit"
            class="px-5 py-2 text-xs font-bold text-white bg-alpharo-500 hover:bg-alpharo-600 rounded-lg shadow-xs"
          >
            {{ newOrder.submitDirectly ? 'Submit Untuk Verifikasi Direktur' : 'Simpan Sebagai Draft' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- CONFIRMATION DIALOG FOR DELETE DRAFT -->
    <ConfirmationDialog
      v-model="deleteConfirmOpen"
      title="Hapus Draft Order?"
      :message="`Apakah Anda yakin ingin menghapus order ${orderToDelete?.orderNumber}? Data draft yang dihapus tidak dapat dipulihkan.`"
      confirm-text="Hapus Order"
      cancel-text="Batal"
      variant="danger"
      @confirm="executeDeleteDraft"
      @cancel="deleteConfirmOpen = false"
    />

    <!-- ORDER DETAIL DRAWER -->
    <Drawer
      v-model="detailDrawerOpen"
      :title="`Rincian Order: ${selectedOrder?.orderNumber || ''}`"
      :subtitle="selectedOrder?.clientName || ''"
      size="lg"
    >
      <div v-if="selectedOrder" class="space-y-6 text-xs">
        <div v-if="selectedOrder.isHold" class="p-3.5 bg-rose-50 rounded-xl border border-rose-200 text-rose-800">
          <div class="font-bold text-xs">⛔ PENGIRIMAN DITAHAN OLEH DIREKTUR</div>
          <p class="mt-1 text-[11px] text-rose-700">Alasan: <strong>{{ selectedOrder.holdReason }}</strong></p>
          <span class="text-[10px] text-rose-500 block mt-0.5">Ditetapkan oleh {{ selectedOrder.holdBy }} pada {{ selectedOrder.holdDate }}</span>
        </div>

        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200">
          <div>
            <span class="text-slate-500 block">Status Order</span>
            <div class="mt-1"><StatusBadge :status="selectedOrder.status" /></div>
          </div>
          <div class="text-right">
            <span class="text-slate-500 block">Total Transaksi</span>
            <span class="text-base font-bold font-mono text-slate-900 mt-1 block">
              Rp {{ selectedOrder.grandTotal.toLocaleString('id-ID') }}
            </span>
          </div>
        </div>

        <div>
          <h4 class="font-bold uppercase text-slate-500 tracking-wider mb-2">Informasi Client & Proyek</h4>
          <div class="border border-slate-200 rounded-lg p-3 space-y-1.5 bg-white">
            <div class="flex justify-between">
              <span class="text-slate-500">Nama Client:</span>
              <span class="font-bold text-slate-800">{{ selectedOrder.clientName }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Nama Proyek:</span>
              <span class="font-semibold text-slate-800">{{ selectedOrder.projectName }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Tanggal Pengajuan:</span>
              <span class="font-mono text-slate-800">{{ selectedOrder.date }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Dibuat Oleh:</span>
              <span class="text-slate-800">{{ selectedOrder.submittedBy }}</span>
            </div>
          </div>
        </div>

        <div>
          <h4 class="font-bold uppercase text-slate-500 tracking-wider mb-2">Item Barang / Jasa</h4>
          <div class="border border-slate-200 rounded-lg overflow-hidden">
            <table class="w-full text-left">
              <thead class="bg-slate-50 border-b border-slate-200 font-semibold text-slate-600">
                <tr>
                  <th class="p-2.5">Barang</th>
                  <th class="p-2.5 text-center">Qty</th>
                  <th class="p-2.5 text-right">Harga</th>
                  <th class="p-2.5 text-right">Total</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="it in selectedOrder.items" :key="it.sku">
                  <td class="p-2.5">
                    <span class="font-medium text-slate-800">{{ it.name }}</span>
                    <span class="block text-[10px] text-slate-400 font-mono">{{ it.sku }}</span>
                  </td>
                  <td class="p-2.5 text-center">{{ it.qty }} {{ it.unit }}</td>
                  <td class="p-2.5 text-right">Rp {{ it.price.toLocaleString('id-ID') }}</td>
                  <td class="p-2.5 text-right font-bold">Rp {{ it.total.toLocaleString('id-ID') }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <DocumentChecklist :documents="selectedOrder.documents" />

        <div>
          <h4 class="font-bold uppercase text-slate-500 tracking-wider mb-3">Timeline & Riwayat Proses</h4>
          <Timeline :timeline="selectedOrder.timeline" />
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
import ConfirmationDialog from '../../components/common/ConfirmationDialog.vue';
import DocumentChecklist from '../../components/common/DocumentChecklist.vue';
import Timeline from '../../components/common/Timeline.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import { Search, Plus, Eye, Send, Trash2, Pencil } from 'lucide-vue-next';

const toast = useToast();

const searchQuery = ref('');
const statusFilter = ref('');

const createModalOpen = ref(false);
const detailDrawerOpen = ref(false);
const deleteConfirmOpen = ref(false);

const selectedOrder = ref(null);
const orderToDelete = ref(null);

const newOrder = ref({
  clientId: '',
  projectName: '',
  date: new Date().toISOString().split('T')[0],
  notes: '',
  submitDirectly: true,
  items: [
    { productId: '', sku: '', name: '', qty: 10, unit: 'Unit', price: 0 },
  ],
});

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

const computedSubtotal = computed(() => {
  return newOrder.value.items.reduce((sum, it) => sum + (Number(it.qty) || 0) * (Number(it.price) || 0), 0);
});

const computedTax = computed(() => Math.round(computedSubtotal.value * 0.11));
const computedGrandTotal = computed(() => computedSubtotal.value + computedTax.value);

const getDocCount = (docs) => {
  if (!docs) return 0;
  let count = 0;
  if (docs.po?.status === 'Lengkap') count++;
  if (docs.suratJalan?.status === 'Lengkap') count++;
  if (docs.bast?.status === 'Lengkap') count++;
  if (docs.faktur?.status === 'Lengkap') count++;
  return count;
};

const openCreateOrderModal = () => {
  newOrder.value = {
    clientId: store.clients[0]?.id || '',
    projectName: '',
    date: new Date().toISOString().split('T')[0],
    notes: '',
    submitDirectly: true,
    items: [
      { productId: 1, sku: 'MAT-001', name: 'Semen Portland Tipe 1 (50kg)', qty: 50, unit: 'Sak', price: 68000 },
    ],
  };
  createModalOpen.value = true;
};

const addItemRow = () => {
  newOrder.value.items.push({
    productId: '',
    sku: '',
    name: '',
    qty: 1,
    unit: 'Unit',
    price: 0,
  });
};

const removeItemRow = (idx) => {
  if (newOrder.value.items.length > 1) {
    newOrder.value.items.splice(idx, 1);
  }
};

const onProductSelect = (item) => {
  const product = store.products.find((p) => p.id === item.productId);
  if (product) {
    item.sku = product.sku;
    item.name = product.name;
    item.unit = product.unit;
    item.price = product.price;
  }
};

const submitCreateOrder = () => {
  const created = storeActions.createOrder(newOrder.value);
  createModalOpen.value = false;
  toast.success(
    newOrder.value.submitDirectly
      ? `Order ${created.orderNumber} diajukan ke Direktur untuk verifikasi.`
      : `Draft order ${created.orderNumber} berhasil disimpan.`
  );
};

const submitVerification = (order) => {
  storeActions.submitOrderForVerification(order.id);
  toast.success(`Order ${order.orderNumber} berhasil diajukan untuk verifikasi Direktur.`);
};

const confirmDelete = (order) => {
  orderToDelete.value = order;
  deleteConfirmOpen.value = true;
};

const executeDeleteDraft = () => {
  if (orderToDelete.value) {
    storeActions.deleteOrder(orderToDelete.value.id);
    toast.info(`Draft order ${orderToDelete.value.orderNumber} telah dihapus.`);
    deleteConfirmOpen.value = false;
  }
};

const viewDetail = (order) => {
  selectedOrder.value = order;
  detailDrawerOpen.value = true;
};
</script>
