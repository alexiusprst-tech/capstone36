<template>
  <div class="login-shell">
    <aside class="login-side" aria-labelledby="login-title">
      <div class="login-brand">
        <img
          src="/images/logo.png"
          alt="Logo CV. Alpharo Artha Jaya"
          class="login-logo"
          width="56"
          height="56"
        />
        <div>
          <strong>CV. Alpharo Artha Jaya</strong>
          <span>Supplier &amp; kontraktor</span>
        </div>
      </div>

      <div class="login-intro">
        <h1 id="login-title">Sistem Informasi Terintegrasi</h1>
        <p>Kelola pengadaan barang, distribusi logistik, dan pekerjaan proyek dalam satu portal.</p>
      </div>

      <ul class="login-divisions">
        <li>
          <HardHat aria-hidden="true" />
          <div>
            <strong>Kontraktor</strong>
            <span>Pelaksanaan proyek, pengawasan, dan Berita Acara (BAST)</span>
          </div>
        </li>
        <li>
          <Truck aria-hidden="true" />
          <div>
            <strong>Supplier</strong>
            <span>Pesanan pembelian (PO) dan pengiriman Surat Jalan</span>
          </div>
        </li>
        <li>
          <Warehouse aria-hidden="true" />
          <div>
            <strong>Gudang &amp; inventaris</strong>
            <span>Stok terpusat, bongkar muat, dan persetujuan</span>
          </div>
        </li>
      </ul>
    </aside>

    <main class="login-main">
      <div class="login-card">
        <h2>Selamat datang kembali</h2>
        <p class="login-subtitle">Masuk untuk mengelola aktivitas dan operasional perusahaan.</p>

        <form @submit.prevent="handleSubmit" :aria-busy="loading">
          <div class="login-field">
            <label for="login-email">Email perusahaan</label>
            <div class="login-input">
              <Mail aria-hidden="true" />
              <input
                id="login-email"
                v-model="form.email"
                type="email"
                name="email"
                autocomplete="username"
                placeholder="nama@alpharo.co.id"
                :aria-invalid="errorMessage ? true : undefined"
                :aria-describedby="errorMessage ? 'login-error' : undefined"
                required
              />
            </div>
          </div>

          <div class="login-field">
            <label for="login-password">Kata sandi</label>
            <div class="login-input">
              <Lock aria-hidden="true" />
              <input
                id="login-password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                name="password"
                class="login-password"
                autocomplete="current-password"
                placeholder="Masukkan kata sandi"
                :aria-invalid="errorMessage ? true : undefined"
                :aria-describedby="errorMessage ? 'login-error' : undefined"
                required
              />
              <button
                type="button"
                class="login-toggle"
                :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                :aria-pressed="showPassword"
                aria-controls="login-password"
                @click="showPassword = !showPassword"
              >
                <component :is="showPassword ? EyeOff : Eye" aria-hidden="true" />
                <span>{{ showPassword ? 'Tutup' : 'Lihat' }}</span>
              </button>
            </div>
          </div>

          <p v-if="errorMessage" id="login-error" class="login-error" role="alert">{{ errorMessage }}</p>

          <button type="submit" class="login-submit" :disabled="loading">
            <span v-if="loading" class="login-spinner" aria-hidden="true"></span>
            <span>{{ loading ? 'Memverifikasi...' : 'Masuk ke sistem' }}</span>
            <ArrowRight v-if="!loading" aria-hidden="true" />
          </button>
        </form>

        <p class="mt-5 text-sm text-slate-500">Gunakan akun yang diberikan administrator. Hak akses mengikuti peran akun Anda.</p>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from '../../navigation';
import { useToast } from '../../services/toast';
import { login } from '../../services/session';
import { HardHat, Truck, Warehouse, Mail, Lock, Eye, EyeOff, ArrowRight } from 'lucide-vue-next';

const router = useRouter();
const route = useRoute();
const toast = useToast();

const form = ref({
  email: '',
  password: '',
});
const showPassword = ref(false);
const loading = ref(false);
const errorMessage = ref('');

const roleHome = {
  MARKETING: '/marketing/dashboard',
  DIREKTUR: '/director/dashboard',
  ADMINISTRASI: '/administration/dashboard',
  WAREHOUSE: '/warehouse/dashboard',
  FINANCE: '/finance/dashboard',
  CLIENT: '/portal',
  SUPPLIER: '/supplier/portal',
};

const handleSubmit = async () => {
  errorMessage.value = '';
  loading.value = true;
  try {
    const user = await login({ email: form.value.email.trim().toLowerCase(), password: form.value.password });
    const requested = route.query.redirect;
    const home = roleHome[user.role] || '/';
    const prefix = home.split('/')[1];
    const destination = typeof requested === 'string' && requested.startsWith(`/${prefix}/`)
      ? requested : home;
    toast.success(`Berhasil masuk sebagai ${user.role}`);
    router.replace(destination);
  } catch (error) {
    errorMessage.value = Object.values(error.response?.data?.errors || {}).flat()[0]
      || error.response?.data?.message || 'Tidak dapat menghubungi server. Silakan coba lagi.';
  } finally {
    loading.value = false;
  }
};

</script>

<style scoped>
.login-shell {
  --login-navy: #1e3a8a;
  --login-blue: #2563eb;
  --login-tint: #eff6ff;
  --login-ink: #1c2130;
  --login-muted: #686e7e;
  --login-line: #e5e7ed;
  display: grid;
  grid-template-columns: minmax(320px, 5fr) minmax(360px, 6fr);
  min-height: 100vh;
  min-height: 100svh;
  color: var(--login-ink);
  background: #fafafb;
  color-scheme: light;
}

.login-side {
  position: relative;
  isolation: isolate;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 48px;
  padding: 48px clamp(28px, 4vw, 72px);
  color: var(--login-navy);
  background: #eff6ff;
  border-right: 1px solid #dbeafe;
}

.login-side::before {
  content: '';
  position: absolute;
  inset: 0;
  z-index: -1;
  pointer-events: none;
  opacity: .06;
  mix-blend-mode: multiply;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='3' stitchTiles='stitch'/%3E%3CfeColorMatrix values='0 0 0 0 .2 0 0 0 0 .3 0 0 0 0 .55 0 0 0 .55 0'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}

.login-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.login-logo {
  width: 56px;
  height: 56px;
  flex: none;
  object-fit: contain;
}

.login-brand strong {
  display: block;
  font-size: 17px;
  font-weight: 700;
  line-height: 1.3;
}

.login-brand span {
  display: block;
  margin-top: 4px;
  font-size: 13px;
  color: #515868;
}

.login-intro h1 {
  max-width: 13ch;
  margin: 0 0 20px;
  font-size: clamp(30px, 3.2vw, 44px);
  line-height: 1.1;
  font-weight: 600;
  letter-spacing: -.035em;
}

.login-intro p {
  max-width: 40ch;
  margin: 0;
  color: #515868;
  font-size: 16px;
  line-height: 1.7;
}

.login-divisions {
  list-style: none;
  margin: 0;
  padding: 0;
}

.login-divisions li {
  display: grid;
  grid-template-columns: 28px minmax(0, 1fr);
  gap: 14px;
  padding: 17px 0;
  border-top: 1px solid rgb(37 99 235 / 10%);
}

.login-divisions li:last-child {
  border-bottom: 1px solid rgb(37 99 235 / 10%);
}

.login-divisions svg {
  width: 24px;
  height: 24px;
  margin-top: 2px;
  stroke-width: 1.7;
}

.login-divisions strong {
  display: block;
  font-size: 15px;
  font-weight: 700;
}

.login-divisions span {
  display: block;
  margin-top: 3px;
  color: #515868;
  font-size: 13px;
  line-height: 1.6;
}

.login-main {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 0;
  padding: 40px clamp(20px, 4vw, 64px);
}

.login-card {
  width: 100%;
  max-width: 420px;
}

.login-card h2 {
  margin: 0 0 8px;
  color: var(--login-navy);
  font-size: 28px;
  font-weight: 600;
  line-height: 1.25;
  letter-spacing: -.025em;
}

.login-subtitle {
  margin: 0 0 28px;
  color: var(--login-muted);
  font-size: 14px;
  line-height: 1.7;
}

.login-field {
  margin-bottom: 18px;
}

.login-field label {
  display: block;
  margin-bottom: 6px;
  font-size: 14px;
  font-weight: 600;
}

.login-input {
  position: relative;
}

.login-input > svg {
  position: absolute;
  top: 50%;
  left: 14px;
  width: 18px;
  height: 18px;
  transform: translateY(-50%);
  color: var(--login-muted);
  pointer-events: none;
}

.login-input input {
  width: 100%;
  height: 48px;
  padding: 0 14px 0 42px;
  border: 1px solid var(--login-line);
  border-radius: 8px;
  background: #fff;
  color: var(--login-ink);
  font: inherit;
  font-size: 15px;
  transition: border-color .15s, box-shadow .15s;
}

.login-input input:focus {
  outline: none;
  border-color: var(--login-blue);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 12%);
}

.login-input input[aria-invalid='true'] {
  border-color: #b3261e;
}

.login-input .login-password {
  padding-right: 100px;
}

.login-toggle {
  position: absolute;
  top: 50%;
  right: 6px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  min-height: 36px;
  padding: 8px 10px;
  transform: translateY(-50%);
  border: 0;
  border-radius: 6px;
  background: none;
  color: var(--login-blue);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.login-toggle svg {
  width: 15px;
  height: 15px;
}

.login-toggle:hover {
  background: var(--login-tint);
}

.login-submit {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 100%;
  height: 50px;
  border: 0;
  border-radius: 8px;
  background: var(--login-blue);
  color: #fff;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: background .15s;
}

.login-submit:hover {
  background: #1d4ed8;
}

.login-submit svg {
  width: 17px;
  height: 17px;
}

.login-error {
  margin: 0 0 16px;
  color: #b3261e;
  font-size: 13px;
  line-height: 1.6;
}

.login-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgb(255 255 255 / 40%);
  border-top-color: #fff;
  border-radius: 50%;
  animation: login-spin .8s linear infinite;
}

@keyframes login-spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 860px) {
  .login-shell { grid-template-columns: minmax(0, 1fr); }
  .login-side { gap: 28px; padding: 28px 22px 32px; }
  .login-intro h1 { max-width: none; font-size: 28px; margin-bottom: 12px; }
  .login-intro p { max-width: 48ch; font-size: 14px; }
  .login-divisions { display: none; }
  .login-main { padding: 24px 16px 40px; }
  .login-card { padding: 24px 20px; }
}

@media (max-width: 380px) {
  .login-brand strong { font-size: 15px; }
  .login-card h2 { font-size: 25px; }
}

@media (prefers-reduced-motion: reduce) {
  .login-spinner { animation: none; }
}
</style>
