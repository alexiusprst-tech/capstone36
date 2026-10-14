<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard Administrasi & Legal Dokumen</h1>
          <p class="text-sm text-slate-500 mt-1">
            Pusat penerbitan PO, Surat Jalan, BAST, Faktur, dan monitoring kelengkapan berkas proyek.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <router-link
            to="/administration/spj"
            class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
          >
            <FolderCheck class="w-4 h-4" />
            <span>Kelola Dokumen SPJ</span>
          </router-link>
        </div>
      </div>
    </template>

    <!-- KPIS FOR ADMINISTRASI: DOKUMEN MENUNGGU & STATUS KELENGKAPAN -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
      <KpiCard title="Order Disetujui" :value="approvedOrders.length" subtitle="Siap dibuatkan berkas" icon-variant="emerald" />
      <KpiCard title="PO Belum Dibuat" :value="missingPoCount" subtitle="Purchase Order pending" icon-variant="amber" />
      <KpiCard title="SJ Belum Dibuat" :value="missingSjCount" subtitle="Surat Jalan pending" icon-variant="amber" />
      <KpiCard title="BAST Belum Dibuat" :value="missingBastCount" subtitle="Berita Acara pending" icon-variant="amber" />
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
      <KpiCard title="Faktur Belum Dibuat" :value="missingFakturCount" subtitle="Faktur Pajak pending" icon-variant="amber" />
      <KpiCard title="Berkas Belum Lengkap" :value="incompleteCount" subtitle="Ada dokumen tertunda" icon-variant="rose" />
      <KpiCard title="Sedang Diproses" :value="inProcessCount" subtitle="Verifikasi administrasi" icon-variant="blue" />
      <KpiCard title="Dokumen Selesai" :value="completeCount" subtitle="Semua berkas lengkap" icon-variant="emerald" />
    </div>

    <!-- MAIN TWO-COLUMN LAYOUT -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- LEFT 2 COLS: RECENT APPROVED ORDERS & INCOMPLETE DOCUMENTS -->
      <div class="lg:col-span-2 space-y-6">
        <!-- RECENT APPROVED ORDERS -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
              <h3 class="font-bold text-slate-900 text-sm">Order Disetujui Membutuhkan Dokumen</h3>
              <p class="text-xs text-slate-500">Order yang telah lolos approval Direktur dan siap diproses berkasnya</p>
            </div>
            <router-link
              to="/administration/orders"
              class="text-xs font-bold text-alpharo-500 hover:underline flex items-center gap-1"
            >
              Semua Order Disetujui <ChevronRight class="w-3.5 h-3.5" />
            </router-link>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
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
                  <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ order.orderNumber }}</td>
                  <td class="py-3 px-3">
                    <div class="font-bold text-slate-800">{{ order.clientName }}</div>
                    <div class="text-[11px] text-slate-400">{{ order.projectName }}</div>
                  </td>
                  <td class="py-3 px-3 text-center">
                    <span
                      :class="[
                        getCompletedCount(order) === 4 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200',
                        'px-2 py-0.5 rounded-full border font-bold text-[10px]'
                      ]"
                    >
                      {{ getCompletedCount(order) }} / 4 Lengkap
                    </span>
                  </td>
                  <td class="py-3 px-3 text-right whitespace-nowrap">
                    <button
                      type="button"
                      class="px-2.5 py-1 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded text-[11px] font-bold"
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

        <!-- INCOMPLETE DOCUMENTS LIST -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <h3 class="font-bold text-slate-900 text-sm mb-3 pb-2 border-b border-slate-100">
            Daftar Berkas Belum Lengkap (Perlu Tindak Lanjut)
          </h3>
          <div class="space-y-3 text-xs">
            <div
              v-for="order in incompleteOrders"
              :key="order.id"
              class="p-3 rounded-lg border border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-2"
            >
              <div>
                <span class="font-mono font-bold text-slate-900">{{ order.orderNumber }}</span>
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
      </div>

      <!-- RIGHT 1 COL: DOCUMENT PROGRESS & RECENT DOCUMENTS -->
      <div class="space-y-6">
        <!-- DOCUMENT PROGRESS SUMMARY -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <h3 class="font-bold text-slate-900 text-sm mb-4 pb-2 border-b border-slate-100">
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
                <div class="bg-indigo-500 h-full" :style="{ width: `${sjPercent}%` }"></div>
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
                <div class="bg-blue-500 h-full" :style="{ width: `${fakturPercent}%` }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- RECENT DOCUMENTS -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs">
          <h3 class="font-bold text-slate-900 text-sm mb-3 pb-2 border-b border-slate-100">
            Dokumen Baru Diterbitkan
          </h3>
          <div class="space-y-2.5 text-xs">
            <div
              v-for="(doc, idx) in recentDocs"
              :key="idx"
              class="p-2 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-between"
            >
              <div>
                <span class="font-mono font-bold text-slate-900 block">{{ doc.number }}</span>
                <span class="text-[11px] text-slate-500">{{ doc.type }} &bull; {{ doc.orderNumber }}</span>
              </div>
              <StatusBadge status="Lengkap" />
            </div>
          </div>
        </div>
      </div>
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
          <p class="font-bold text-slate-800 uppercase tracking-wider">Aksi Cepat Terbitkan Berkas:</p>
          
          <div class="grid grid-cols-2 gap-2">
            <button
              type="button"
              class="p-2.5 border rounded-lg hover:bg-slate-50 text-left font-medium flex items-center justify-between"
              @click="generateDoc('po')"
            >
              <span>Terbitkan PO</span>
              <span class="text-alpharo-500 font-bold">+</span>
            </button>
            <button
              type="button"
              class="p-2.5 border rounded-lg hover:bg-slate-50 text-left font-medium flex items-center justify-between"
              @click="generateDoc('suratJalan')"
            >
              <span>Terbitkan Surat Jalan</span>
              <span class="text-alpharo-500 font-bold">+</span>
            </button>
            <button
              type="button"
              class="p-2.5 border rounded-lg hover:bg-slate-50 text-left font-medium flex items-center justify-between"
              @click="generateDoc('bast')"
            >
              <span>Terbitkan BAST</span>
              <span class="text-alpharo-500 font-bold">+</span>
            </button>
            <button
              type="button"
              class="p-2.5 border rounded-lg hover:bg-slate-50 text-left font-medium flex items-center justify-between"
              @click="generateDoc('faktur')"
            >
              <span>Terbitkan Faktur</span>
              <span class="text-alpharo-500 font-bold">+</span>
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
import StatusBadge from '../../components/common/StatusBadge.vue';
import DocumentChecklist from '../../components/common/DocumentChecklist.vue';
import Modal from '../../components/common/Modal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import { Archive, ChevronRight, FolderCheck } from 'lucide-vue-next';

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

const recentDocs = computed(() => [
  { number: 'PO-2026-001', type: 'Purchase Order', orderNumber: 'ORD-2026-001' },
  { number: 'SJ-2026-001', type: 'Surat Jalan', orderNumber: 'ORD-2026-001' },
  { number: 'BAST-2026-001', type: 'BAST', orderNumber: 'ORD-2026-001' },
  { number: 'FK-2026-001', type: 'Faktur Pajak', orderNumber: 'ORD-2026-001' },
]);

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
