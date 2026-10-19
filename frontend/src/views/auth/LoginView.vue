<template>
  <div class="min-h-screen bg-slate-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Ambient enterprise subtle background accents -->
    <div class="absolute inset-0 bg-gradient-to-br from-[#072B5E] via-[#0B4DA2] to-[#041A3B] opacity-95"></div>
    <div class="absolute inset-0 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:24px_24px] opacity-10"></div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10 text-center">
      <!-- Logo Branding -->
      <div class="flex justify-center mb-4">
        <AlpharoLogo
          theme="white"
          size="lg"
          :show-tagline="true"
          :vertical="true"
        />
      </div>
      <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white">
        Sistem Informasi Terintegrasi
      </h2>
      <p class="mt-1 text-xs sm:text-sm text-blue-200">
        Enterprise Supply & Contractor Management Platform
      </p>
    </div>

    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-xl relative z-10 px-4 sm:px-0">
      <div class="bg-white py-8 px-6 sm:px-10 shadow-2xl rounded-2xl border border-slate-200">
        <!-- Error Alert -->
        <div
          v-if="errorMessage"
          class="mb-5 p-3 rounded-lg bg-rose-50 border border-rose-200 text-xs text-rose-700 flex items-center gap-2"
        >
          <span class="font-bold">⚠</span>
          <span>{{ errorMessage }}</span>
        </div>

        <!-- Login Form -->
        <form class="space-y-4" @submit.prevent="handleSubmit">
          <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Alamat Email Perusahaan
            </label>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="nama@alpharo.co.id"
              class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-slate-900 text-sm focus:ring-2 focus:ring-alpharo-500 focus:border-alpharo-500 transition-all outline-hidden"
            />
          </div>

          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Kata Sandi
              </label>
              <span class="text-[11px] text-slate-400">Default: password123</span>
            </div>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="••••••••"
                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-slate-900 text-sm focus:ring-2 focus:ring-alpharo-500 focus:border-alpharo-500 transition-all outline-hidden pr-10"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 text-xs"
                @click="showPassword = !showPassword"
              >
                {{ showPassword ? 'Sembunyikan' : 'Lihat' }}
              </button>
            </div>
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full mt-2 py-2.5 px-4 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg shadow-sm text-sm transition-all flex items-center justify-center gap-2 disabled:opacity-70"
          >
            <span v-if="loading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span>{{ loading ? 'Memverifikasi Kredensial...' : 'Masuk ke Sistem' }}</span>
          </button>
        </form>

        <!-- DEMO ROLE QUICK SWITCHER FOR CAPSTONE EVALUATION -->
        <div class="mt-8 pt-6 border-t border-slate-200">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
              Akses Cepat Penguji / Demo Role
            </span>
            <span class="text-[10px] text-slate-400 font-mono">Password: password123</span>
          </div>
          <p class="text-xs text-slate-500 mb-3 leading-relaxed">
            Klik tombol role di bawah untuk mengisi kredensial dan langsung masuk ke hak akses terkait:
          </p>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button
              v-for="r in demoRoles"
              :key="r.role"
              type="button"
              class="flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-200 hover:border-alpharo-500 hover:bg-blue-50/50 text-left transition-all group cursor-pointer"
              @click="loginAs(r)"
            >
              <span :class="[r.badgeColor, 'w-2 h-2 rounded-full mt-1.5 shrink-0']"></span>
              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-1.5">
                  <span class="text-xs font-bold text-slate-900 group-hover:text-alpharo-600">{{ r.title }}</span>
                  <span class="text-[9px] px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-mono">{{ r.role }}</span>
                </div>
                <p class="text-[11px] text-slate-500 truncate mt-0.5">{{ r.email }}</p>
                <p class="text-[10px] text-slate-400 line-clamp-1 mt-0.5">{{ r.desc }}</p>
              </div>
            </button>
          </div>
        </div>
      </div>

      <p class="mt-6 text-center text-xs text-blue-200/70">
        CV Alpharo Artha Jaya &bull; Supplier & Contractor &bull; Capstone Evaluation 2026
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AlpharoLogo from '../../components/common/AlpharoLogo.vue';
import { storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';

const router = useRouter();
const toast = useToast();

const form = ref({
  email: 'marketing@alpharo.co.id',
  password: 'password123',
});
const showPassword = ref(false);
const loading = ref(false);
const errorMessage = ref('');

const demoRoles = [
  {
    role: 'MARKETING',
    title: 'Marketing',
    email: 'marketing@alpharo.co.id',
    badgeColor: 'bg-blue-500',
    desc: 'Kelola Client, Order Draft, & Pengajuan Verifikasi',
    target: '/marketing/dashboard',
  },
  {
    role: 'DIREKTUR',
    title: 'Direktur',
    email: 'direktur@alpharo.co.id',
    badgeColor: 'bg-purple-500',
    desc: 'Verifier, Approver & Managerial Monitoring',
    target: '/director/dashboard',
  },
  {
    role: 'ADMINISTRASI',
    title: 'Administrasi',
    email: 'admin@alpharo.co.id',
    badgeColor: 'bg-emerald-500',
    desc: 'Penerbitan PO, Surat Jalan, BAST, Faktur & Dokumen SPJ',
    target: '/administration/dashboard',
  },
  {
    role: 'WAREHOUSE',
    title: 'Warehouse',
    email: 'warehouse@alpharo.co.id',
    badgeColor: 'bg-amber-500',
    desc: 'Data Barang, Mutasi Stok, Pemenuhan & Pengiriman',
    target: '/warehouse/dashboard',
  },
  {
    role: 'FINANCE',
    title: 'Finance',
    email: 'finance@alpharo.co.id',
    badgeColor: 'bg-rose-500',
    desc: 'Invoice, Pembayaran (Penuh/Termin), & Laporan',
    target: '/finance/dashboard',
  },
  {
    role: 'CLIENT',
    title: 'Portal Publik Rekanan',
    email: 'client@alpharo.co.id',
    badgeColor: 'bg-teal-500',
    desc: 'Pengadaan Mandiri, Lacak Resi BAST & Pembayaran',
    target: '/portal',
  },
];

// 1-Click Login directly from Demo Role cards
const loginAs = (demo) => {
  form.value.email = demo.email;
  form.value.password = 'password123';
  storeActions.switchRole(demo.role);
  toast.success(`Berhasil masuk sebagai ${demo.title}`);
  router.push(demo.target);
};

const quickFill = (demo) => {
  form.value.email = demo.email;
  form.value.password = 'password123';
  toast.info(`Kredensial ${demo.title} diisi. Silakan klik Masuk.`);
};

const handleSubmit = async () => {
  errorMessage.value = '';
  loading.value = true;

  try {
    const emailLower = (form.value.email || '').toLowerCase().trim();
    let matchedRole = 'MARKETING';
    let targetRoute = '/marketing/dashboard';

    if (emailLower.includes('direktur') || emailLower.includes('alpharo')) {
      matchedRole = 'DIREKTUR';
      targetRoute = '/director/dashboard';
    } else if (emailLower.includes('admin') || emailLower.includes('administrasi') || emailLower.includes('siti')) {
      matchedRole = 'ADMINISTRASI';
      targetRoute = '/administration/dashboard';
    } else if (emailLower.includes('warehouse') || emailLower.includes('gudang') || emailLower.includes('agus')) {
      matchedRole = 'WAREHOUSE';
      targetRoute = '/warehouse/dashboard';
    } else if (emailLower.includes('finance') || emailLower.includes('keuangan') || emailLower.includes('dewi')) {
      matchedRole = 'FINANCE';
      targetRoute = '/finance/dashboard';
    } else if (emailLower.includes('client') || emailLower.includes('sekolah') || emailLower.includes('sma')) {
      matchedRole = 'CLIENT';
      targetRoute = '/portal';
    } else {
      matchedRole = 'MARKETING';
      targetRoute = '/marketing/dashboard';
    }

    if (form.value.password && form.value.password !== 'password123' && form.value.password !== 'admin' && form.value.password !== '123456') {
      errorMessage.value = 'Kata sandi salah. Gunakan default: password123';
      loading.value = false;
      return;
    }

    // Switch role in reactive store & sync localStorage
    storeActions.switchRole(matchedRole);
    toast.success(`Berhasil masuk sebagai ${matchedRole}`);
    router.push(targetRoute);
  } catch (err) {
    errorMessage.value = 'Terjadi kesalahan autentikasi.';
  } finally {
    loading.value = false;
  }
};
</script>
