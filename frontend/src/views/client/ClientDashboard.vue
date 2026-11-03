<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard Rekanan Client</h1>
          <p class="text-sm text-slate-500 mt-1">
            Ringkasan pesanan, status pembayaran termin, dan riwayat transaksi instansi Anda dengan CV Alpharo Artha Jaya.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <router-link
            to="/client/order-request"
            class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-[#0B4DA2] to-[#062F69] hover:from-[#09428F] hover:to-[#042452] text-white font-bold rounded-lg text-xs shadow-xs transition-all"
          >
            <Plus class="w-4 h-4 text-amber-300 stroke-[2.5]" />
            <span>Ajukan Pesanan Baru</span>
          </router-link>
          <router-link
            to="/client/orders"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold rounded-lg text-xs shadow-xs transition-colors"
          >
            <PackageSearch class="w-4 h-4" />
            <span>Lacak Pesanan</span>
          </router-link>
          <router-link
            to="/client/payments"
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
          >
            <CreditCard class="w-4 h-4" />
            <span>Status Pembayaran</span>
          </router-link>
        </div>
      </div>
    </template>

    <!-- HERO CTA ORDER REQUEST BANNER -->
    <div class="bg-gradient-to-r from-[#0B4DA2] via-[#09428F] to-[#062F69] rounded-2xl p-6 mb-6 text-white shadow-md relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div class="relative z-10 max-w-2xl space-y-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-amber-400/20 text-amber-300 border border-amber-300/30">
          <Sparkles class="w-3.5 h-3.5" />
          Layanan Pengadaan Terbuka
        </span>
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white">
          Butuh Pengadaan Barang atau Sarana Baru?
        </h2>
        <p class="text-xs sm:text-sm text-blue-100 leading-relaxed">
          Ajukan permohonan pesanan secara mandiri sesuai kebutuhan sekolah atau instansi Anda. Pilih dari katalog resmi 2.196 SKU atau input spesifikasi khusus, pantau verifikasi stok, dan lacak pengiriman hingga serah terima fisik (BAST).
        </p>
      </div>

      <div class="relative z-10 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
        <router-link
          to="/client/order-request"
          class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-[#F5A623] hover:bg-amber-500 text-slate-950 font-extrabold rounded-xl text-xs sm:text-sm shadow-md transition-all cursor-pointer"
        >
          <Plus class="w-4 h-4 stroke-[3]" />
          <span>Ajukan Pesanan Baru</span>
        </router-link>
        <router-link
          to="/portal"
          class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl text-xs backdrop-blur-xs transition-colors cursor-pointer"
        >
          <Globe class="w-4 h-4" />
          <span>Buka Portal Publik</span>
        </router-link>
      </div>
    </div>

    <!-- CLIENT IDENTITY BANNER -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-alpharo-500/10 text-alpharo-600 flex items-center justify-center shrink-0">
          <School class="w-6 h-6" />
        </div>
        <div>
          <h2 class="text-sm font-bold text-slate-900">{{ activeClient.name }}</h2>
          <p class="text-xs text-slate-500 mt-0.5">{{ activeClient.code }} &bull; {{ activeClient.address }}</p>
          <p class="text-xs text-slate-500">PIC: {{ activeClient.contactPerson }} &bull; {{ activeClient.phone }}</p>
        </div>
      </div>
      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 self-start sm:self-center">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
        Rekanan Aktif
      </span>
    </div>

    <!-- KPI CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <KpiCard title="Total Pesanan" :value="clientOrders.length" subtitle="Order atas nama instansi" :icon-component="ShoppingBag" icon-variant="blue" />
      <KpiCard title="Pesanan Selesai" :value="completedOrders" subtitle="Barang telah diterima" :icon-component="CheckCircle2" icon-variant="blue" />
      <KpiCard title="Menunggu Pembayaran" :value="outstandingCount" subtitle="Termin belum lunas" :icon-component="Clock" icon-variant="blue" />
      <KpiCard title="Total Tagihan" :value="totalOutstanding" :is-currency="true" subtitle="Sisa yang harus dibayar" :icon-component="CreditCard" icon-variant="blue" />
    </div>

    <!-- DETAILED SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- LEFT 2 COLS -->
      <div class="lg:col-span-2 space-y-6">
        <!-- RECENT ORDERS -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-bold text-slate-900 text-sm">Pesanan Terbaru</h3>
              <p class="text-xs text-slate-500">Daftar order dan status terkini permintaan instansi Anda</p>
            </div>
            <router-link to="/client/orders" class="text-xs font-bold text-alpharo-500 hover:underline flex items-center gap-1">
              Lacak Semua <ChevronRight class="w-3.5 h-3.5" />
            </router-link>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                  <th class="py-2.5 px-3">No. Order</th>
                  <th class="py-2.5 px-3">Proyek</th>
                  <th class="py-2.5 px-3 text-right">Nilai Order</th>
                  <th class="py-2.5 px-3 text-center">Status</th>
                  <th class="py-2.5 px-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="order in clientOrders.slice(0, 5)" :key="order.id" class="hover:bg-slate-50/60 transition-colors">
                  <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ order.orderNumber }}</td>
                  <td class="py-3 px-3">
                    <div class="font-medium text-slate-800">{{ order.projectName }}</div>
                    <div class="text-[11px] text-slate-400">{{ order.date }}</div>
                  </td>
                  <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                    Rp {{ Number(order.grandTotal || 0).toLocaleString('id-ID') }}
                  </td>
                  <td class="py-3 px-3 text-center">
                    <StatusBadge :status="order.isHold ? 'Ditahan' : order.status" />
                  </td>
                  <td class="py-3 px-3 text-right whitespace-nowrap">
                    <router-link to="/client/orders" class="text-alpharo-500 font-bold hover:underline">Detail</router-link>
                  </td>
                </tr>
                <tr v-if="clientOrders.length === 0">
                  <td colspan="5" class="py-8 text-center text-slate-400">Belum ada pesanan atas nama instansi Anda.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- PAYMENT SUMMARY -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-bold text-slate-900 text-sm">Ringkasan Tagihan & Pembayaran</h3>
              <p class="text-xs text-slate-500">Invoice dan termin pembayaran atas nama instansi Anda</p>
            </div>
            <router-link to="/client/payments" class="text-xs font-bold text-alpharo-500 hover:underline flex items-center gap-1">
              Semua Pembayaran <ChevronRight class="w-3.5 h-3.5" />
            </router-link>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                  <th class="py-2.5 px-3">No. Invoice</th>
                  <th class="py-2.5 px-3">Skema</th>
                  <th class="py-2.5 px-3 text-right">Total</th>
                  <th class="py-2.5 px-3 text-right">Sisa</th>
                  <th class="py-2.5 px-3 text-center">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="inv in clientInvoices.slice(0, 4)" :key="inv.id" class="hover:bg-slate-50/60 transition-colors">
                  <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ inv.invoiceNumber }}</td>
                  <td class="py-3 px-3 text-slate-700">{{ inv.paymentScheme || inv.paymentType || 'Penuh' }}</td>
                  <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                    Rp {{ Number(inv.totalAmount || 0).toLocaleString('id-ID') }}
                  </td>
                  <td class="py-3 px-3 text-right font-mono text-rose-600 font-semibold whitespace-nowrap">
                    Rp {{ Number(inv.remainingAmount || 0).toLocaleString('id-ID') }}
                  </td>
                  <td class="py-3 px-3 text-center"><StatusBadge :status="inv.status" /></td>
                </tr>
                <tr v-if="clientInvoices.length === 0">
                  <td colspan="5" class="py-8 text-center text-slate-400">Belum ada tagihan untuk instansi Anda.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- RIGHT SIDE: TIMELINE & NOTIFICATIONS -->
      <div class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <h3 class="font-bold text-slate-900 text-sm mb-4 pb-3 border-b border-slate-100">Progres Pesanan Terakhir</h3>
          <Timeline v-if="latestOrder" :timeline="latestOrder.timeline" />
          <p v-else class="text-xs text-slate-400 text-center py-6">Belum ada pesanan untuk ditampilkan.</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <h3 class="font-bold text-slate-900 text-sm">Notifikasi Terbaru</h3>
            <router-link to="/client/notifications" class="text-xs font-bold text-alpharo-500 hover:underline">Semua</router-link>
          </div>
          <div class="space-y-3">
            <div v-for="notif in recentNotifs" :key="notif.id" class="p-3 bg-slate-50 rounded-lg border border-slate-100">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-900">{{ notif.title }}</span>
                <span class="text-[10px] text-slate-400 whitespace-nowrap">{{ notif.time }}</span>
              </div>
              <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">{{ notif.message }}</p>
            </div>
            <p v-if="recentNotifs.length === 0" class="text-xs text-slate-400 text-center py-6">Tidak ada notifikasi.</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import KpiCard from '../../components/common/KpiCard.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import Timeline from '../../components/common/Timeline.vue';
import { store } from '../../stores/mockData';
import {
  CreditCard,
  PackageSearch,
  ChevronRight,
  School,
  Plus,
  Sparkles,
  Globe,
  ShoppingBag,
  CheckCircle2,
  Clock,
} from 'lucide-vue-next';

const clientId = computed(() => store.currentUser.clientId || 1);

const activeClient = computed(() => {
  return (
    store.clients.find((c) => c.id === clientId.value) || {
      name: store.currentUser.name,
      code: 'CS00000',
      address: '-',
      contactPerson: store.currentUser.name,
      phone: store.currentUser.phone,
    }
  );
});

const clientOrders = computed(() => store.orders.filter((o) => o.clientId === clientId.value));

const completedOrders = computed(
  () => clientOrders.value.filter((o) => o.status === 'Selesai').length
);

const clientInvoices = computed(() => store.invoices.filter((inv) => inv.clientId === clientId.value));

const outstandingCount = computed(
  () => clientInvoices.value.filter((inv) => inv.status !== 'Lunas').length
);

const totalOutstanding = computed(() =>
  clientInvoices.value.reduce((sum, inv) => sum + (inv.remainingAmount || 0), 0)
);

const latestOrder = computed(() => clientOrders.value[0] || null);

const recentNotifs = computed(() =>
  store.notifications.filter((n) => n.role === 'CLIENT' || n.role === 'ALL').slice(0, 4)
);
</script>
