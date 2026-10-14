<template>
  <AppLayout>
    <!-- TOP HEADER -->
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-2.5">
          <div class="p-2 bg-emerald-50 text-emerald-700 rounded-xl">
            <ShoppingBag class="w-6 h-6 stroke-[2.2]" />
          </div>
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
              Transaksi Pembelian (Purchasing)
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
              Pencatatan faktur pembelian barang dari supplier, realisasi pengadaan proyek, dan pembayaran.
            </p>
          </div>
        </div>

        <!-- Top Right Action -->
        <router-link
          to="/finance/purchases/create"
          class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors cursor-pointer"
        >
          <Plus class="w-4 h-4 stroke-[2.5]" />
          <span>Tambah Transaksi Pembelian</span>
        </router-link>
      </div>
    </template>

    <!-- SECTION 1: PENGAJUAN BARANG DARI GUDANG (MATERIAL REQUESTS MENUNGGU PROSES) -->
    <div class="bg-gradient-to-r from-amber-50 via-orange-50 to-amber-50/60 border border-amber-200/90 rounded-xl p-4 sm:p-5 mb-6 shadow-2xs">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3 pb-3 border-b border-amber-200/70">
        <div class="flex items-center gap-2.5">
          <span class="p-1.5 bg-amber-500 text-white rounded-lg">
            <ClipboardList class="w-4 h-4 stroke-[2.5]" />
          </span>
          <div>
            <h3 class="font-bold text-amber-950 text-xs sm:text-sm">
              Pengajuan Kebutuhan Barang Gudang (Material Request)
            </h3>
            <p class="text-[11px] text-amber-800">
              Gudang telah mengajukan kebutuhan barang. Klik <strong>"Proses Pembelian"</strong> untuk men-generate formulir pembelian secara otomatis.
            </p>
          </div>
        </div>
        <span class="px-2.5 py-1 bg-amber-100 text-amber-900 rounded-full text-xs font-bold shrink-0">
          {{ pendingWarehouseRequests.length }} Pengajuan Menunggu
        </span>
      </div>

      <!-- Requests List Cards -->
      <div v-if="pendingWarehouseRequests.length === 0" class="text-xs text-amber-800/80 py-2">
        Tidak ada pengajuan material gudang baru yang menunggu proses pembelian.
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div
          v-for="req in pendingWarehouseRequests"
          :key="req.id"
          class="bg-white p-3.5 rounded-lg border border-amber-200 shadow-2xs hover:border-amber-400 transition-all flex flex-col justify-between gap-2"
        >
          <div>
            <div class="flex items-center justify-between mb-1">
              <span class="font-mono font-bold text-slate-900 text-xs">
                {{ req.requestCode || req.request_code }}
              </span>
              <span class="px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-800 rounded text-[10px] font-bold">
                Menunggu Finance
              </span>
            </div>
            <div class="font-semibold text-slate-800 text-xs line-clamp-1">
              {{ req.projectName || req.project_name || 'Proyek Umum' }}
            </div>
            <div class="text-[11px] text-slate-500 line-clamp-2 mt-0.5">
              {{ req.purpose }}
            </div>

            <!-- Items List -->
            <div class="mt-2 pt-2 border-t border-slate-100 flex flex-wrap gap-1">
              <span
                v-for="(it, idx) in req.items"
                :key="idx"
                class="text-[10px] bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-mono"
              >
                {{ it.name }} ({{ it.qty }} {{ it.unit || 'UNIT' }})
              </span>
            </div>
          </div>

          <!-- Bottom Action -->
          <div class="flex items-center justify-between pt-2 border-t border-slate-100 mt-1">
            <span class="text-[11px] font-mono font-bold text-slate-900">
              Est: Rp {{ Number(req.totalEstCost || req.total_est_cost || 0).toLocaleString('id-ID') }}
            </span>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg text-xs font-bold shadow-2xs transition-colors cursor-pointer"
              @click="processPurchaseFromRequest(req)"
            >
              <Zap class="w-3.5 h-3.5 stroke-[2.5]" />
              <span>Proses Pembelian</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- FILTER & SEARCH CARD (Green top line accent matching system) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden mb-5">
      <div class="h-1 bg-emerald-600 w-full"></div>
      <div class="p-4 sm:p-5">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
          <!-- Search Bar -->
          <div class="md:col-span-5 relative">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari kode pembelian, no faktur, supplier, atau barang..."
              class="w-full pl-9 pr-8 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-emerald-500 bg-white text-slate-800 placeholder-slate-400"
            />
            <button
              v-if="searchQuery"
              type="button"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
              @click="searchQuery = ''"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Project Filter -->
          <div class="md:col-span-4">
            <select
              v-model="projectFilter"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-emerald-500 bg-white text-slate-800 cursor-pointer"
            >
              <option value="">Semua Proyek Pembelian</option>
              <option v-for="proj in availableProjects" :key="proj.id" :value="proj.name">
                {{ proj.name }}
              </option>
            </select>
          </div>

          <!-- Payment Method Filter -->
          <div class="md:col-span-2">
            <select
              v-model="paymentMethodFilter"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-emerald-500 bg-white text-slate-800 cursor-pointer"
            >
              <option value="">Semua Pembayaran</option>
              <option value="Tunai">Tunai</option>
              <option value="Transfer Bank BCA">Transfer Bank BCA</option>
              <option value="Transfer Bank Mandiri">Transfer Bank Mandiri</option>
              <option value="Tempo">Tempo / Kredit</option>
            </select>
          </div>

          <!-- Reset Filter -->
          <div class="md:col-span-1 flex justify-end">
            <button
              type="button"
              class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-medium border border-slate-300 shadow-2xs transition-colors cursor-pointer"
              title="Reset Filter"
              @click="resetFilters"
            >
              <RotateCcw class="w-3.5 h-3.5 text-slate-500" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- PURCHASES TABLE CARD -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden mb-6">
      <div class="h-1 bg-emerald-600 w-full"></div>

      <!-- Card Table Header -->
      <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px]">
          Daftar Transaksi Pembelian Supplier
        </span>
        <span class="text-slate-500 font-medium font-mono">
          {{ filteredPurchases.length }} Transaksi Terdaftar
        </span>
      </div>

      <!-- Table Container -->
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-[11px] uppercase tracking-wide">
              <th class="py-3 px-4 whitespace-nowrap">Kode & Tanggal</th>
              <th class="py-3 px-4 whitespace-nowrap">No Faktur</th>
              <th class="py-3 px-4">Proyek & Supplier</th>
              <th class="py-3 px-4">Rincian Barang</th>
              <th class="py-3 px-4 text-right whitespace-nowrap">Total Pembelian</th>
              <th class="py-3 px-4 text-center whitespace-nowrap">Metode Bayar</th>
              <th class="py-3 px-4 text-center whitespace-nowrap">Status</th>
              <th class="py-3 px-4 text-right whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="filteredPurchases.length === 0">
              <td colspan="8" class="py-12 text-center text-slate-500">
                <div class="flex flex-col items-center justify-center gap-2">
                  <Package class="w-8 h-8 text-slate-300" />
                  <span class="text-xs font-semibold text-slate-700">Belum ada transaksi pembelian yang dicatat.</span>
                  <span class="text-[11px] text-slate-400">Klik "Tambah Transaksi Pembelian" atau proses pengajuan dari gudang di atas.</span>
                </div>
              </td>
            </tr>

            <tr
              v-for="p in filteredPurchases"
              :key="p.purchase_code || p.id"
              class="hover:bg-slate-50/70 transition-colors"
            >
              <!-- Kode & Tanggal -->
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="font-mono font-bold text-slate-900 block">{{ p.purchase_code }}</span>
                <span class="text-[11px] text-slate-500 font-mono">{{ p.transaction_date }}</span>
              </td>

              <!-- No Faktur -->
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="font-mono text-slate-800 font-semibold bg-slate-100 px-2 py-0.5 rounded">
                  {{ p.invoice_number || '-' }}
                </span>
              </td>

              <!-- Proyek & Supplier -->
              <td class="py-3 px-4 max-w-xs">
                <div class="font-semibold text-slate-900 leading-snug">
                  {{ p.project_name }}
                </div>
                <div class="text-[11px] text-emerald-700 font-medium mt-0.5 flex items-center gap-1">
                  <Truck class="w-3 h-3" />
                  <span>{{ p.default_supplier || p.items?.[0]?.supplier || 'Supplier Umum' }}</span>
                </div>
              </td>

              <!-- Rincian Barang -->
              <td class="py-3 px-4 max-w-sm">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="(it, idx) in p.items"
                    :key="idx"
                    class="inline-flex items-center gap-1 text-[11px] bg-slate-100 border border-slate-200 text-slate-800 px-2 py-0.5 rounded"
                  >
                    <span>{{ it.barang || it.name }}</span>
                    <span class="font-bold font-mono text-emerald-700">({{ it.qty }})</span>
                  </span>
                </div>
              </td>

              <!-- Total Pembelian -->
              <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                Rp {{ Number(p.total_price || p.totalAmount || 0).toLocaleString('id-ID') }}
              </td>

              <!-- Metode Bayar -->
              <td class="py-3 px-4 text-center whitespace-nowrap">
                <span class="px-2 py-0.5 bg-slate-100 border border-slate-200 text-slate-700 rounded font-semibold text-[11px]">
                  {{ p.payment_method }}
                </span>
              </td>

              <!-- Status -->
              <td class="py-3 px-4 text-center whitespace-nowrap">
                <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold text-[10px]">
                  Lunas
                </span>
              </td>

              <!-- Aksi -->
              <td class="py-3 px-4 text-right whitespace-nowrap">
                <button
                  type="button"
                  class="px-2.5 py-1 text-slate-700 hover:text-emerald-700 hover:bg-emerald-50 border border-slate-300 hover:border-emerald-300 rounded text-xs font-semibold transition-colors cursor-pointer"
                  @click="openDetail(p)"
                >
                  Detail
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- DETAIL MODAL -->
    <Modal
      v-model="detailModalOpen"
      :title="'Detail Transaksi Pembelian: ' + (selectedPurchase?.purchase_code || '')"
      subtitle="Faktur pembelian barang supplier dan rincian item."
      size="xl"
    >
      <div v-if="selectedPurchase" class="space-y-4 text-xs">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
          <div>
            <div class="text-[10px] text-slate-400 uppercase font-semibold">No Faktur</div>
            <div class="font-bold text-slate-900 font-mono mt-0.5">{{ selectedPurchase.invoice_number || '-' }}</div>
          </div>
          <div>
            <div class="text-[10px] text-slate-400 uppercase font-semibold">Tanggal</div>
            <div class="font-bold text-slate-900 font-mono mt-0.5">{{ selectedPurchase.transaction_date }}</div>
          </div>
          <div>
            <div class="text-[10px] text-slate-400 uppercase font-semibold">Supplier</div>
            <div class="font-bold text-slate-900 mt-0.5">{{ selectedPurchase.default_supplier || '-' }}</div>
          </div>
          <div>
            <div class="text-[10px] text-slate-400 uppercase font-semibold">Jenis Pembayaran</div>
            <div class="font-bold text-slate-900 mt-0.5">{{ selectedPurchase.payment_method }}</div>
          </div>
        </div>

        <div>
          <div class="font-bold text-slate-700 uppercase text-[11px] mb-1">Proyek Terkait</div>
          <div class="p-2.5 bg-white border border-slate-200 rounded-lg text-slate-800 font-semibold">
            {{ selectedPurchase.project_name }}
          </div>
        </div>

        <!-- Items Table -->
        <div class="border border-slate-200 rounded-lg overflow-hidden">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-[11px]">
              <tr>
                <th class="py-2.5 px-3">Nama Barang</th>
                <th class="py-2.5 px-3 text-center">Jumlah</th>
                <th class="py-2.5 px-3 text-right">Harga Beli</th>
                <th class="py-2.5 px-3 text-right">Subtotal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(it, idx) in selectedPurchase.items" :key="idx">
                <td class="py-2.5 px-3 font-medium text-slate-900">{{ it.barang || it.name }}</td>
                <td class="py-2.5 px-3 text-center font-bold text-slate-800">{{ it.qty }}</td>
                <td class="py-2.5 px-3 text-right font-mono text-slate-700">
                  Rp {{ Number(it.harga_beli || it.est_price || 0).toLocaleString('id-ID') }}
                </td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">
                  Rp {{ Number(it.subtotal || 0).toLocaleString('id-ID') }}
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-slate-50 border-t border-slate-200 font-bold">
              <tr>
                <td colspan="3" class="py-2.5 px-3 text-right uppercase text-[11px] text-slate-600">Total Harga:</td>
                <td class="py-2.5 px-3 text-right font-mono text-emerald-800 text-sm">
                  Rp {{ Number(selectedPurchase.total_price || 0).toLocaleString('id-ID') }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-200">
          <button
            type="button"
            class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold"
            @click="detailModalOpen = false"
          >
            Tutup
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AppLayout from '../../components/layout/AppLayout.vue';
import Modal from '../../components/common/Modal.vue';
import { store } from '../../stores/mockData';
import {
  ShoppingBag,
  Plus,
  Search,
  X,
  RotateCcw,
  ClipboardList,
  Zap,
  Truck,
  Package,
} from 'lucide-vue-next';

const router = useRouter();

const searchQuery = ref('');
const projectFilter = ref('');
const paymentMethodFilter = ref('');

const detailModalOpen = ref(false);
const selectedPurchase = ref(null);

const availableProjects = ref([
  { id: 'PR00001', name: 'pengadaan komputer sekolah (SMA NEGRI 2 BOGOR)' },
  { id: 'PR00002', name: 'Pembangunan Fasilitas Workshop & Lab Vokasi' },
  { id: 'PR00003', name: 'Pekerjaan Pasangan & Struktur Jembatan Kanal' },
  { id: 'PR00004', name: 'Renovasi Gedung Laboratorium Komputer' },
]);

// Initialize default store purchases if empty
if (!store.purchases) {
  store.purchases = [
    {
      id: 1,
      purchase_code: 'PB20261014114148',
      transaction_date: '2026-10-14',
      invoice_number: 'FAK-SNX-99812',
      project_id: 'PR00001',
      project_name: 'pengadaan komputer sekolah (SMA NEGRI 2 BOGOR)',
      default_supplier: 'PT Synnex Metrodata Indonesia',
      subtotal: 10000000,
      discount: 0,
      total_price: 10000000,
      payment_method: 'Tunai',
      items: [
        {
          barang: 'A00001770 - Pemeliharaan Komputer Note Book/ Laptop',
          sku: 'A00001770',
          supplier: 'PT Synnex Metrodata Indonesia',
          qty: 100,
          harga_beli: 100000,
          diskon_percent: 0,
          diskon_nominal: 0,
          subtotal: 10000000,
        },
      ],
    },
  ];
}

onMounted(async () => {
  try {
    const res = await fetch('/api/purchases');
    if (res.ok) {
      const data = await res.json();
      if (data.data?.length) {
        store.purchases = data.data;
      }
    }
  } catch (e) {}

  try {
    const reqRes = await fetch('/api/warehouse/requests');
    if (reqRes.ok) {
      const reqData = await reqRes.json();
      if (reqData.data?.length) {
        store.warehouseRequests = reqData.data;
      }
    }
  } catch (e) {}
});

// Pending requests from warehouse
const pendingWarehouseRequests = computed(() => {
  return (store.warehouseRequests || []).filter(
    (r) => r.status === 'MENUNGGU_FINANCE' || r.status === 'Draft' || r.status === 'Menunggu Verifikasi'
  );
});

const filteredPurchases = computed(() => {
  return (store.purchases || []).filter((p) => {
    if (projectFilter.value && p.project_name !== projectFilter.value) {
      return false;
    }
    if (paymentMethodFilter.value) {
      if (paymentMethodFilter.value === 'Tempo' && !p.payment_method?.includes('Tempo')) {
        return false;
      } else if (paymentMethodFilter.value !== 'Tempo' && p.payment_method !== paymentMethodFilter.value) {
        return false;
      }
    }
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase();
      const code = (p.purchase_code || '').toLowerCase();
      const inv = (p.invoice_number || '').toLowerCase();
      const proj = (p.project_name || '').toLowerCase();
      const sup = (p.default_supplier || '').toLowerCase();
      const hasItem = p.items?.some((i) => (i.barang || i.name || '').toLowerCase().includes(q));
      if (!code.includes(q) && !inv.includes(q) && !proj.includes(q) && !sup.includes(q) && !hasItem) {
        return false;
      }
    }
    return true;
  });
});

const resetFilters = () => {
  searchQuery.value = '';
  projectFilter.value = '';
  paymentMethodFilter.value = '';
};

// 1-click auto-generation from warehouse request to purchase form
const processPurchaseFromRequest = (req) => {
  router.push({
    path: '/finance/purchases/create',
    query: {
      request_id: req.id,
      requestCode: req.requestCode || req.request_code,
    },
  });
};

const openDetail = (p) => {
  selectedPurchase.value = p;
  detailModalOpen.value = true;
};
</script>
