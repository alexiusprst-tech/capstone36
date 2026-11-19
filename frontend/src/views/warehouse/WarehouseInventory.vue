<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Stok & Mutasi Persediaan</h1>
          <p class="text-sm text-slate-500 mt-1">Riwayat stok fisik yang tersimpan di server, termasuk saldo awal, penyesuaian, dan penerimaan pembelian.</p>
        </div>
        <button type="button" class="px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs" @click="router.push('/warehouse/products')">Kelola Stok di Data Barang</button>
      </div>
    </template>
    <p class="mb-5 text-sm text-slate-600">Alokasi pesanan mengurangi stok tersedia, tanpa mengubah stok fisik atau membuat mutasi. Saldo awal migrasi mencatat stok saat ledger mulai digunakan.</p>
    <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
      <form class="p-4 flex flex-col sm:flex-row gap-3 border-b border-slate-200" @submit.prevent="load(1)">
        <div class="flex-1">
          <label for="movement-search" class="block text-xs font-semibold text-slate-700 mb-1">Cari SKU, barang, atau referensi</label>
          <input id="movement-search" v-model="search" maxlength="255" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Masukkan kata pencarian" />
        </div>
        <div>
          <label for="movement-type" class="block text-xs font-semibold text-slate-700 mb-1">Jenis mutasi</label>
          <select id="movement-type" v-model="type" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
            <option value="">Semua jenis</option><option value="OPENING">Saldo awal</option><option value="ADJUSTMENT">Penyesuaian</option><option value="RECEIPT">Penerimaan pembelian</option>
          </select>
        </div>
        <button type="submit" :disabled="loading" class="self-end px-4 py-2 rounded-lg bg-slate-800 text-white text-sm disabled:opacity-50">Terapkan</button>
      </form>
      <div v-if="error" role="alert" class="p-5 text-sm text-red-700 bg-red-50">{{ error }} <button type="button" class="underline font-semibold ml-2" @click="load(meta.current_page)">Coba lagi</button></div>
      <p v-else-if="loading" role="status" class="p-6 text-sm text-slate-500">Memuat riwayat mutasi dari server…</p>
      <p v-else-if="!movements.length" class="p-6 text-sm text-slate-500">Tidak ada riwayat mutasi untuk filter ini.</p>
      <div v-else class="overflow-x-auto">
        <table class="ui-table-wide w-full text-left text-xs border-collapse">
          <thead class="bg-slate-50 text-slate-600 border-b border-slate-200"><tr>
            <th class="p-4">Waktu</th><th class="p-4">SKU & Barang</th><th class="p-4">Jenis</th><th class="p-4 text-right">Perubahan</th><th class="p-4 text-right">Sebelum → Sesudah</th><th class="p-4">Referensi & Alasan</th><th class="p-4">Petugas</th>
          </tr></thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="movement in movements" :key="movement.id" class="hover:bg-slate-50">
              <td class="p-4 whitespace-nowrap text-slate-500">{{ formatDate(movement.created_at) }}</td>
              <td class="p-4"><span class="block font-semibold">{{ movement.name_snapshot }}</span><span class="font-mono text-slate-500">{{ movement.sku_snapshot }}</span></td>
              <td class="p-4 whitespace-nowrap">{{ labels[movement.type] || movement.type }}</td>
              <td class="p-4 text-right font-mono font-semibold" :class="movement.delta > 0 ? 'text-emerald-700' : movement.delta < 0 ? 'text-rose-700' : 'text-slate-600'">{{ movement.delta > 0 ? '+' : '' }}{{ number(movement.delta) }}</td>
              <td class="p-4 text-right whitespace-nowrap font-mono">{{ number(movement.before_stock) }} → {{ number(movement.after_stock) }}</td>
              <td class="p-4 min-w-48"><span v-if="movement.reference_id" class="block font-mono">{{ movement.reference_type }} #{{ movement.reference_id }}</span><span class="text-slate-600">{{ movement.reason || '—' }}</span></td>
              <td class="p-4">{{ movement.actor?.name || (movement.reference_type === 'migration' ? 'Migrasi sistem' : 'Sistem / petugas tidak tersedia') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="p-4 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600">
        <span>{{ number(meta.total) }} mutasi · Halaman {{ meta.current_page }} dari {{ meta.last_page }}</span>
        <div class="flex gap-2">
          <button type="button" :disabled="loading || meta.current_page <= 1" class="border rounded-lg px-3 py-2 disabled:opacity-40" @click="load(meta.current_page - 1)">Sebelumnya</button>
          <button type="button" :disabled="loading || meta.current_page >= meta.last_page" class="border rounded-lg px-3 py-2 disabled:opacity-40" @click="load(meta.current_page + 1)">Berikutnya</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from '../../navigation';
import AppLayout from '../../components/layout/AppLayout.vue';
import api from '../../services/api';
const router = useRouter();
const movements = ref([]);
const search = ref('');
const type = ref('');
const loading = ref(false);
const error = ref('');
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const labels = { OPENING: 'Saldo awal', ADJUSTMENT: 'Penyesuaian', RECEIPT: 'Penerimaan pembelian' };
const number = (value) => Number(value || 0).toLocaleString('id-ID');
const formatDate = (value) => value ? new Date(value).toLocaleString('id-ID') : '—';
let requestId = 0;
async function load(page = 1) {
  const id = ++requestId;
  loading.value = true;
  error.value = '';
  try {
    const response = await api.get('/inventory/movements', { params: { page, per_page: 25, search: search.value || undefined, type: type.value || undefined } });
    if (id !== requestId) return;
    movements.value = response.data.data;
    meta.value = response.data.meta;
  } catch (err) {
    if (id !== requestId) return;
    error.value = err.response?.data?.message || 'Riwayat mutasi gagal dimuat. Periksa koneksi lalu coba lagi.';
  } finally {
    if (id === requestId) loading.value = false;
  }
}
onMounted(() => load());
</script>
