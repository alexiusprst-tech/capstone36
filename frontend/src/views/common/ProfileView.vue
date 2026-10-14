<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Profil Pengguna & Hak Akses</h1>
          <p class="text-sm text-slate-500 mt-1">
            Informasi akun internal, unit kerja divisi, dan wewenang operasional sistem.
          </p>
        </div>
      </div>
    </template>

    <div class="max-w-4xl mx-auto space-y-6">
      <!-- USER OVERVIEW CARD -->
      <div class="bg-white rounded-xl border border-slate-200/90 p-6 shadow-xs flex flex-col sm:flex-row items-center sm:items-start gap-6">
        <img
          :src="store.currentUser.avatar"
          alt="Avatar"
          class="w-24 h-24 rounded-full object-cover ring-4 ring-slate-100"
        />
        <div class="flex-1 text-center sm:text-left space-y-1">
          <div class="flex flex-col sm:flex-row sm:items-center gap-2">
            <h2 class="text-xl font-bold text-slate-900">{{ store.currentUser.name }}</h2>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-alpharo-600 w-fit mx-auto sm:mx-0">
              {{ store.currentUser.role }}
            </span>
          </div>
          <p class="text-xs text-slate-500 font-medium">{{ store.currentUser.division }}</p>
          <p class="text-xs text-slate-600 font-mono">{{ store.currentUser.email }} &bull; {{ store.currentUser.phone }}</p>
          <div class="pt-3 flex flex-wrap gap-2 justify-center sm:justify-start">
            <span class="text-[11px] bg-slate-100 px-2.5 py-1 rounded text-slate-700 font-medium">CV Alpharo Artha Jaya</span>
            <span class="text-[11px] bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded font-medium">Status: Aktif</span>
          </div>
        </div>
      </div>

      <!-- ROLE & PERMISSION MATRIX CARD -->
      <div class="bg-white rounded-xl border border-slate-200/90 p-6 shadow-xs">
        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">
          Wewenang & Hak Akses Peran ({{ store.currentUser.role }})
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
          <div
            v-for="(perm, idx) in currentPermissions"
            :key="idx"
            class="p-3 bg-slate-50 rounded-lg border border-slate-100 flex items-center gap-2.5"
          >
            <span class="text-emerald-600 font-bold">✓</span>
            <span class="font-medium text-slate-800">{{ perm }}</span>
          </div>
        </div>
      </div>

      <!-- SECURITY SETTINGS -->
      <div class="bg-white rounded-xl border border-slate-200/90 p-6 shadow-xs">
        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">
          Keamanan & Kata Sandi
        </h3>
        <form class="space-y-4 max-w-md text-xs" @submit.prevent="updatePassword">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Kata Sandi Saat Ini</label>
            <input type="password" value="password123" disabled class="w-full px-3 py-2 border rounded-lg bg-slate-100 text-slate-500" />
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Kata Sandi Baru</label>
            <input v-model="newPassword" type="password" placeholder="Minimal 8 karakter..." class="w-full px-3 py-2 border rounded-lg" />
          </div>
          <button type="submit" class="px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-bold rounded-lg shadow-xs">
            Perbarui Kata Sandi
          </button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import { store } from '../../stores/mockData';
import { useToast } from '../../services/toast';

const toast = useToast();
const newPassword = ref('');

const currentPermissions = computed(() => {
  const role = store.currentUser.role;
  if (role === 'MARKETING') {
    return [
      'Pendaftaran & Manajemen Rekanan Client (CRUD)',
      'Pembuatan & Pengeditan Draft Order Proyek (CRUD)',
      'Pengajuan Order ke Direktur untuk Verifikasi',
      'Monitoring Siklus Pipeline Order Transaksi',
    ];
  }
  if (role === 'DIREKTUR') {
    return [
      'Hak Eksklusif Verifikasi & Approval / Reject Order',
      'Pemeriksaan Margin Kontrak & Catatan Penolakan Wajib',
      'Monitoring Menyeluruh: Warehouse, Administrasi, Finance',
      'Akses Laporan Analitik Terpadu & Audit Activity Log',
    ];
  }
  if (role === 'ADMINISTRASI') {
    return [
      'Penerbitan & Unduh Purchase Order (PO)',
      'Penerbitan Surat Jalan Pengiriman 3 Rangkap',
      'Penerbitan Berita Acara Serah Terima (BAST)',
      'Penerbitan Faktur Pajak & Pengarsipan Berkas Digital',
    ];
  }
  if (role === 'WAREHOUSE') {
    return [
      'Manajemen Katalog Data Barang & SKU Material Gudang',
      'Pencatatan Mutasi Masuk, Keluar, dan Stock Opname',
      'Pengajuan Kebutuhan Barang (Material Request)',
      'Alokasi Pemenuhan Fisik & Penjadwalan Armada Truk',
    ];
  }
  if (role === 'FINANCE') {
    return [
      'Penerbitan Invoice Tagihan Berdasarkan Order',
      'Pencatatan Pembayaran Penuh & Termin Bertahap',
      'Monitoring Umur Piutang (Aging Schedule) Rekanan',
      'Rekonsiliasi Rekening Koran & Laporan Finansial',
    ];
  }
  if (role === 'CLIENT') {
    return [
      'Pemantauan Status & Tracking Progres Pesanan Real-time',
      'Informasi Rincian Invoice & Jadwal Termin Pembayaran',
      'Pengiriman Konfirmasi Pembayaran & Bukti Transfer Bank',
      'Unduh Berkas Proyek: SPJ, Surat Jalan, dan BAST',
    ];
  }
  return [];
});

const updatePassword = () => {
  if (newPassword.value) {
    toast.success('Kata sandi berhasil diperbarui.');
    newPassword.value = '';
  }
};
</script>
