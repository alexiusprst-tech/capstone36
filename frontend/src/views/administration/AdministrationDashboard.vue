<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Dashboard Administrasi</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pusat penerbitan PO, Surat Jalan, BAST, Faktur, dan monitoring kelengkapan berkas proyek.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Link
            href="/administration/spj"
            class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
          >
            <FolderCheck class="w-4 h-4" />
            <span>Kelola Dokumen SPJ</span>
          </Link>
          <Link
            href="/administration/accounts"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-semibold rounded-lg text-xs shadow-xs transition-colors"
          >
            <Users class="w-4 h-4" />
            <span>Kelola Akun</span>
          </Link>
        </div>
      </div>
    </template>

    <!-- KPIS FOR ADMINISTRASI: 4 PRIMARY KPIS ACTION-ORIENTED -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <KpiCard
        title="Order Perlu SPJ"
        :value="approvedOrders.length"
        unit="Order"
        subtitle="Lolos persetujuan Direktur"
        :icon-component="FileCheck2"
        icon-variant="blue"
      />
      <KpiCard
        title="Dokumen Belum Lengkap"
        :value="incompleteCount"
        unit="Order"
        subtitle="PO / SJ / BAST / Faktur tertunda"
        :icon-component="Clock"
        icon-variant="blue"
        :trend="incompleteCount > 0 ? 'Perlu Dibuat' : 'Tuntas'"
        :trend-positive="incompleteCount === 0"
      />
      <KpiCard
        title="Sedang Diproses"
        :value="inProcessCount"
        unit="Order"
        subtitle="Verifikasi tanda tangan & stempel"
        :icon-component="Layers"
        icon-variant="blue"
      />
      <KpiCard
        title="Dokumen SPJ Lengkap"
        :value="completeCount"
        unit="Order"
        subtitle="100% legalitas proyek tuntas"
        :icon-component="FolderCheck"
        icon-variant="blue"
        :trend="'100% Lengkap'"
        :trend-positive="true"
      />
    </div>

    <!-- MAIN TWO-COLUMN LAYOUT -->
    <div class="dashboard-workspace min-w-0 flex flex-col gap-4 sm:gap-5">
      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-semibold text-slate-900 text-sm">Order Disetujui Membutuhkan Dokumen</h3>
              <p class="text-xs text-slate-500">Order yang telah lolos approval Direktur dan siap diproses berkasnya</p>
            </div>
            <Link
              href="/administration/spj"
              class="text-xs font-semibold text-alpharo-500 hover:underline flex items-center gap-1"
            >
              Semua Order Disetujui <ChevronRight class="w-3.5 h-3.5" />
            </Link>
          </div>

          <div class="overflow-x-auto xl:max-h-[26rem] xl:overflow-y-auto overscroll-auto">
            <table class="ui-table w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                  <th class="py-2.5 px-3">No. Order</th>
                  <th class="py-2.5 px-3">Client</th>
                  <th class="py-2.5 px-3 text-center">Kelengkapan</th>
                  <th class="py-2.5 px-3 text-right">Aksi Buat</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="order in approvedOrders" :key="order.id" class="hover:bg-slate-50/60 transition-colors">
                  <td class="py-3 px-3 font-mono font-semibold text-slate-900">{{ order.orderNumber }}</td>
                  <td class="py-3 px-3">
                    <div class="font-semibold text-slate-800">{{ order.clientName }}</div>
                    <div class="text-[11px] text-slate-400">{{ order.projectName }}</div>
                  </td>
                  <td class="py-3 px-3 text-center">
                    <span
                      :class="[
                        getCompletedCount(order) === 4 ? 'text-emerald-700' : 'text-amber-700',
                        'font-medium text-[11px] whitespace-nowrap'
                      ]"
                    >
                      {{ getCompletedCount(order) }} / 4 Lengkap
                    </span>
                  </td>
                  <td class="py-3 px-3 text-right whitespace-nowrap">
                    <button
                      type="button"
                      class="px-2.5 py-1 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded text-[11px] font-semibold"
                      @click="openDocProcess(order)"
                    >
                      Proses Dokumen
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <h3 class="font-semibold text-slate-900 text-sm mb-3 pb-2 border-b border-slate-100">
            Daftar Berkas Belum Lengkap (Perlu Tindak Lanjut)
          </h3>
          <div class="divide-y divide-slate-100 text-xs xl:max-h-80 xl:overflow-y-auto overscroll-auto">
            <div
              v-for="order in incompleteOrders"
              :key="order.id"
              class="py-3 flex flex-col sm:flex-row sm:items-start justify-between gap-3"
            >
              <div class="min-w-0 flex-1">
                <span class="font-mono font-semibold text-slate-900">{{ order.orderNumber }}</span>
                <span class="mx-2 text-slate-300">&bull;</span>
                <span class="font-semibold text-slate-800">{{ order.clientName }}</span>
                <div class="text-[11px] text-slate-500 mt-0.5">
                  Berkas kurang:
                  <span v-if="!order.documents?.po?.exists" class="text-rose-600 font-semibold">[PO] </span>
                  <span v-if="!order.documents?.suratJalan?.exists" class="text-rose-600 font-semibold">[Surat Jalan] </span>
                  <span v-if="!order.documents?.bast?.exists" class="text-rose-600 font-semibold">[BAST] </span>
                  <span v-if="!order.documents?.faktur?.exists" class="text-rose-600 font-semibold">[Faktur] </span>
                </div>
              </div>
              <button
                type="button"
                class="px-3 py-1 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 font-semibold rounded text-[11px] self-start sm:self-center"
                @click="openDocProcess(order)"
              >
                Lengkapi Sekarang →
              </button>
            </div>
          </div>
        </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <h3 class="font-semibold text-slate-900 text-sm mb-3 pb-2 border-b border-slate-100">
            Dokumen Baru Diterbitkan
          </h3>
          <div class="divide-y divide-slate-100 text-xs">
            <div
              v-for="(doc, idx) in recentDocs"
              :key="idx"
              class="py-3 flex items-start justify-between gap-4"
            >
              <div class="min-w-0 flex-1">
                <span class="font-mono font-semibold text-slate-900 block">{{ doc.number }}</span>
                <span class="text-[11px] text-slate-500">{{ doc.type }} &bull; {{ doc.orderNumber }}</span>
              </div>
              <StatusBadge plain status="Lengkap" />
            </div>
          </div>
        </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <h3 class="font-semibold text-slate-900 text-sm mb-4 pb-2 border-b border-slate-100">
            Progress Penerbitan Dokumen
          </h3>
          <div class="space-y-4 text-xs">
            <div>
              <div class="flex justify-between font-semibold mb-1">
                <span>Purchase Order (PO)</span>
                <span>{{ poPercent }}%</span>
              </div>
              <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                <div class="bg-alpharo-500 h-full" :style="{ width: `${poPercent}%` }"></div>
              </div>
            </div>

            <div>
              <div class="flex justify-between font-semibold mb-1">
                <span>Surat Jalan</span>
                <span>{{ sjPercent }}%</span>
              </div>
              <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                <div class="bg-alpharo-500 h-full" :style="{ width: `${sjPercent}%` }"></div>
              </div>
            </div>

            <div>
              <div class="flex justify-between font-semibold mb-1">
                <span>BAST</span>
                <span>{{ bastPercent }}%</span>
              </div>
              <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                <div class="bg-emerald-500 h-full" :style="{ width: `${bastPercent}%` }"></div>
              </div>
            </div>

            <div>
              <div class="flex justify-between font-semibold mb-1">
                <span>Faktur Komersial/Pajak</span>
                <span>{{ fakturPercent }}%</span>
              </div>
              <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                <div class="bg-alpharo-500 h-full" :style="{ width: `${fakturPercent}%` }"></div>
              </div>
            </div>
          </div>
        </div>

      <ActivityLogCard class="dashboard-activity" />
    </div>

    <!-- QUICK PROCESS MODAL -->
    <Modal
      v-model="processModalOpen"
      :title="`Penerbitan Dokumen: ${activeOrder?.orderNumber || ''}`"
      :subtitle="activeOrder?.clientName || ''"
      size="md"
    >
      <div v-if="activeOrder" class="space-y-4 text-xs">
        <DocumentChecklist :documents="activeOrder.documents" />

        <div class="space-y-3 pt-2 border-t border-slate-200">
          <p class="font-semibold text-slate-800 uppercase tracking-wider">Aksi Cepat Terbitkan Berkas:</p>
          
          <div class="grid grid-cols-2 gap-2">
            <button
              type="button"
              class="p-2.5 border rounded-lg hover:bg-slate-50 text-left font-medium flex items-center justify-between"
              @click="generateDoc('po')"
            >
              <span>Terbitkan PO</span>
              <span class="text-alpharo-500 font-semibold">+</span>
            </button>
            <button
              type="button"
              class="p-2.5 border rounded-lg hover:bg-slate-50 text-left font-medium flex items-center justify-between"
              @click="generateDoc('suratJalan')"
            >
              <span>Terbitkan Surat Jalan</span>
              <span class="text-alpharo-500 font-semibold">+</span>
            </button>
            <button
              type="button"
              class="p-2.5 border rounded-lg hover:bg-slate-50 text-left font-medium flex items-center justify-between"
              @click="generateDoc('bast')"
            >
              <span>Terbitkan BAST</span>
              <span class="text-alpharo-500 font-semibold">+</span>
            </button>
            <button
              type="button"
              class="p-2.5 border rounded-lg hover:bg-slate-50 text-left font-medium flex items-center justify-between"
              @click="generateDoc('faktur')"
            >
              <span>Terbitkan Faktur</span>
              <span class="text-alpharo-500 font-semibold">+</span>
            </button>
          </div>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import KpiCard from '../../components/common/KpiCard.vue';
import ActivityLogCard from '../../components/common/ActivityLogCard.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import DocumentChecklist from '../../components/common/DocumentChecklist.vue';
import Modal from '../../components/common/Modal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import {
  Archive,
  ChevronRight,
  FolderCheck,
  Users,
  FileCheck2,
  FileText,
  Truck,
  ClipboardList,
  Receipt,
  Clock,
  Layers,
} from 'lucide-vue-next';

const toast = useToast();

const processModalOpen = ref(false);
const activeOrder = ref(null);

const approvedOrders = computed(() => store.orders.filter((o) => o.status === 'Disetujui' || o.status === 'Diproses' || o.status === 'Selesai'));

const getCompletedCount = (order) => {
  let count = 0;
  if (order.documents?.po?.status === 'Lengkap') count++;
  if (order.documents?.suratJalan?.status === 'Lengkap') count++;
  if (order.documents?.bast?.status === 'Lengkap') count++;
  if (order.documents?.faktur?.status === 'Lengkap') count++;
  return count;
};

const incompleteOrders = computed(() => approvedOrders.value.filter((o) => getCompletedCount(o) < 4));

const missingPoCount = computed(() => approvedOrders.value.filter((o) => !o.documents?.po?.exists).length);
const missingSjCount = computed(() => approvedOrders.value.filter((o) => !o.documents?.suratJalan?.exists).length);
const missingBastCount = computed(() => approvedOrders.value.filter((o) => !o.documents?.bast?.exists).length);
const missingFakturCount = computed(() => approvedOrders.value.filter((o) => !o.documents?.faktur?.exists).length);

const incompleteCount = computed(() => incompleteOrders.value.length);
const inProcessCount = computed(() => approvedOrders.value.filter((o) => getCompletedCount(o) > 0 && getCompletedCount(o) < 4).length);
const completeCount = computed(() => approvedOrders.value.filter((o) => getCompletedCount(o) === 4).length);

const totalApproved = computed(() => approvedOrders.value.length || 1);
const poPercent = computed(() => Math.round(((totalApproved.value - missingPoCount.value) / totalApproved.value) * 100));
const sjPercent = computed(() => Math.round(((totalApproved.value - missingSjCount.value) / totalApproved.value) * 100));
const bastPercent = computed(() => Math.round(((totalApproved.value - missingBastCount.value) / totalApproved.value) * 100));
const fakturPercent = computed(() => Math.round(((totalApproved.value - missingFakturCount.value) / totalApproved.value) * 100));

const recentDocs = computed(() => {
  const docs = [];
  for (const order of store.orders || []) {
    if (order.documents) {
      if (order.documents.po?.exists) docs.push({ number: order.documents.po.number, type: 'Purchase Order', orderNumber: order.orderNumber });
      if (order.documents.suratJalan?.exists) docs.push({ number: order.documents.suratJalan.number, type: 'Surat Jalan', orderNumber: order.orderNumber });
      if (order.documents.bast?.exists) docs.push({ number: order.documents.bast.number, type: 'BAST', orderNumber: order.orderNumber });
      if (order.documents.faktur?.exists) docs.push({ number: order.documents.faktur.number, type: 'Faktur Pajak', orderNumber: order.orderNumber });
    }
  }
  return docs.slice(0, 5);
});

const openDocProcess = (order) => {
  activeOrder.value = order;
  processModalOpen.value = true;
};

const generateDoc = (docType) => {
  if (activeOrder.value) {
    const docNum = `${docType.toUpperCase()}-2026-${String(Math.floor(100 + Math.random() * 900))}`;
    storeActions.generateDocument(activeOrder.value.orderNumber, docType, docNum);
    toast.success(`Dokumen ${docType.toUpperCase()} berhasil diterbitkan.`);
  }
};
</script>
