<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Stok & Mutasi Persediaan</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pencatatan arus masuk barang, pengeluaran order proyek, opname, dan penyesuaian stok fisik.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
          @click="openAdjustmentModal"
        >
          <Plus class="w-4 h-4" />
          <span>Input Mutasi Stok</span>
        </button>
      </div>
    </template>

    <!-- KPIS FOR INVENTORY MOVEMENTS -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
      <KpiCard title="Total SKU Aktif" :value="store.products.length" icon-variant="blue" />
      <KpiCard title="Mutasi Masuk" :value="incomingCount" icon-variant="blue" />
      <KpiCard title="Mutasi Keluar" :value="outgoingCount" icon-variant="blue" />
      <KpiCard title="Penyesuaian Opname" :value="adjustCount" icon-variant="blue" />
    </div>

    <!-- STOCK MOVEMENTS TABLE -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-900 uppercase">Riwayat Mutasi & Pergerakan Barang</span>
        <span class="text-slate-500">{{ store.stockMovements.length }} Transaksi Mutasi</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">Waktu</th>
              <th class="py-3 px-4">SKU & Barang</th>
              <th class="py-3 px-4 text-center">Jenis Mutasi</th>
              <th class="py-3 px-4 text-right">Volume</th>
              <th class="py-3 px-4">No. Referensi</th>
              <th class="py-3 px-4">Petugas Lapangan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="m in store.stockMovements" :key="m.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ m.date }}</td>
              <td class="py-3.5 px-4">
                <span class="font-bold text-slate-900 block">{{ m.name }}</span>
                <span class="font-mono text-[10px] text-slate-400">{{ m.sku }}</span>
              </td>
              <td class="py-3.5 px-4 text-center">
                <span
                  :class="[
                    m.type === 'Masuk' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : m.type === 'Keluar' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-blue-50 text-blue-700 border-blue-200',
                    'px-2 py-0.5 rounded-full border font-bold text-[10px]'
                  ]"
                >
                  {{ m.type }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                {{ m.type === 'Masuk' ? '+' : m.type === 'Keluar' ? '-' : '' }}{{ Math.abs(m.qty) }} {{ m.unit }}
              </td>
              <td class="py-3.5 px-4 font-mono text-slate-600">{{ m.reference }}</td>
              <td class="py-3.5 px-4 text-slate-700">{{ m.pic }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- INPUT MUTASI MODAL -->
    <Modal v-model="modalOpen" title="Input Mutasi / Penyesuaian Stok" subtitle="Catat barang masuk suplier, pemakaian site, atau hasil stock opname" size="md">
      <form class="space-y-4 text-xs" @submit.prevent="saveMovement">
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Pilih Material</label>
          <select v-model="form.sku" required class="w-full px-3 py-2 border rounded-lg bg-white">
            <option v-for="p in store.products" :key="p.sku" :value="p.sku">
              [{{ p.sku }}] {{ p.name }} (Stok Saat Ini: {{ p.stock }} {{ p.unit }})
            </option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Jenis Mutasi</label>
            <select v-model="form.type" required class="w-full px-3 py-2 border rounded-lg bg-white">
              <option value="Masuk">Barang Masuk (Penerimaan)</option>
              <option value="Keluar">Barang Keluar (Pengiriman)</option>
              <option value="Penyesuaian">Penyesuaian (Stock Opname)</option>
            </select>
          </div>
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Jumlah Volume</label>
            <input v-model.number="form.qty" type="number" min="1" required class="w-full px-3 py-2 border rounded-lg text-center" />
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Nomor Referensi Dokumen</label>
          <input v-model="form.reference" required placeholder="Contoh: PO-SUPPLIER-102 / OPNAME-OKT" class="w-full px-3 py-2 border rounded-lg font-mono" />
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button type="button" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg" @click="modalOpen = false">Batal</button>
          <button type="submit" class="px-4 py-1.5 bg-alpharo-500 text-white font-bold rounded-lg">Simpan Mutasi</button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import KpiCard from '../../components/common/KpiCard.vue';
import Modal from '../../components/common/Modal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import { Plus } from 'lucide-vue-next';

const toast = useToast();

const modalOpen = ref(false);
const form = ref({
  sku: store.products[0]?.sku || '',
  type: 'Masuk',
  qty: 10,
  reference: 'SURAT-JALAN-SUPPLIER',
});

const incomingCount = computed(() => store.stockMovements.filter((m) => m.type === 'Masuk').length);
const outgoingCount = computed(() => store.stockMovements.filter((m) => m.type === 'Keluar').length);
const adjustCount = computed(() => store.stockMovements.filter((m) => m.type === 'Penyesuaian').length);

const openAdjustmentModal = () => {
  form.value = {
    sku: store.products[0]?.sku || '',
    type: 'Masuk',
    qty: 50,
    reference: `OPNAME-${new Date().toISOString().slice(5, 10)}`,
  };
  modalOpen.value = true;
};

const saveMovement = () => {
  storeActions.addStockMovement(form.value);
  toast.success(`Mutasi stok ${form.value.sku} berhasil dicatat.`);
  modalOpen.value = false;
};
</script>
