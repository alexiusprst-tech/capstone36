<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kelola Invoice Tagihan Proyek</h1>
          <p class="text-sm text-slate-500 mt-1">
            Penerbitan tagihan resmi berdasarkan pesanan disetujui, penetapan termin pembayaran, dan cetak invoice.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors"
          @click="openCreateModal"
        >
          <Plus class="w-4 h-4" />
          <span>Terbitkan Invoice Baru</span>
        </button>
      </div>
    </template>

    <!-- INVOICES TABLE -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-900 uppercase">Daftar Tagihan Invoice</span>
        <span class="text-slate-500">{{ store.invoices.length }} Berkas Invoice</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">Invoice Number</th>
              <th class="py-3 px-4">Order Number</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">Invoice Date</th>
              <th class="py-3 px-4">Due Date</th>
              <th class="py-3 px-4 text-right">Total Tagihan</th>
              <th class="py-3 px-4 text-right">Paid</th>
              <th class="py-3 px-4 text-right">Remaining</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="inv in store.invoices" :key="inv.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ inv.invoiceNumber }}</td>
              <td class="py-3.5 px-4 font-mono text-slate-600">{{ inv.orderNumber }}</td>
              <td class="py-3.5 px-4 font-bold text-slate-800">{{ inv.clientName }}</td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ inv.invoiceDate }}</td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ inv.dueDate }}</td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                Rp {{ Number(inv.totalAmount || 0).toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono text-emerald-600 font-semibold whitespace-nowrap">
                Rp {{ Number(inv.paidAmount || 0).toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono text-rose-600 font-bold whitespace-nowrap">
                Rp {{ Number(inv.remainingAmount || 0).toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-center"><StatusBadge :status="inv.status" /></td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button type="button" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded" title="Download" @click="triggerAction('Download', inv.invoiceNumber)">
                    <Download class="w-4 h-4" />
                  </button>
                  <button type="button" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded" title="Cetak" @click="triggerAction('Cetak', inv.invoiceNumber)">
                    <Printer class="w-4 h-4" />
                  </button>
                  <button type="button" class="px-2.5 py-1 bg-alpharo-500 text-white rounded font-bold text-xs" @click="openDetail(inv)">
                    Detail
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CREATE INVOICE MODAL -->
    <Modal v-model="createModalOpen" title="Terbitkan Invoice Tagihan Baru" subtitle="Tetapkan skema pembayaran penuh atau termin bertahap" size="lg">
      <form class="space-y-4 text-xs" @submit.prevent="saveInvoice">
        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Pilih Order Disetujui</label>
          <select v-model="form.orderNumber" required class="w-full px-3 py-2 border rounded-lg bg-white" @change="onOrderChange">
            <option v-for="o in readyOrders" :key="o.orderNumber" :value="o.orderNumber">
              {{ o.orderNumber }} - {{ o.clientName }} (Rp {{ Number(o.grandTotal || 0).toLocaleString('id-ID') }})
            </option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Tanggal Terbit Invoice</label>
            <input v-model="form.invoiceDate" type="date" required class="w-full px-3 py-2 border rounded-lg bg-white" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Tanggal Jatuh Tempo</label>
            <input v-model="form.dueDate" type="date" required class="w-full px-3 py-2 border rounded-lg bg-white" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 uppercase mb-1">Skema Pembayaran</label>
            <select v-model="form.paymentType" class="w-full px-3 py-2 border rounded-lg bg-white">
              <option value="Penuh">Pembayaran Penuh (100% Sekaligus)</option>
              <option value="Termin">Pembayaran Termin (Bertahap)</option>
            </select>
          </div>
          <div v-if="form.paymentType === 'Termin'">
            <label class="block font-bold text-slate-700 uppercase mb-1">Jumlah Termin</label>
            <select v-model.number="form.terminCount" class="w-full px-3 py-2 border rounded-lg bg-white">
              <option :value="2">2 Termin (50% - 50%)</option>
              <option :value="3">3 Termin (30% - 30% - 40%)</option>
              <option :value="4">4 Termin (25% x 4)</option>
            </select>
          </div>
        </div>

        <div class="p-3 bg-slate-50 border rounded-lg">
          <div class="flex justify-between font-bold text-slate-900">
            <span>Total Nilai Tagihan:</span>
            <span class="font-mono text-alpharo-600">Rp {{ (form.totalAmount || 0).toLocaleString('id-ID') }}</span>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button type="button" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg" @click="createModalOpen = false">Batal</button>
          <button type="submit" class="px-4 py-1.5 bg-alpharo-500 text-white font-bold rounded-lg">Terbitkan Tagihan</button>
        </div>
      </form>
    </Modal>

    <!-- INVOICE DETAIL DRAWER -->
    <Drawer v-model="detailDrawerOpen" :title="`Invoice: ${selectedInv?.invoiceNumber || ''}`" :subtitle="selectedInv?.clientName || ''" size="md">
      <div v-if="selectedInv" class="space-y-6 text-xs">
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
          <div class="flex justify-between items-center">
            <span class="text-slate-500">Status</span>
            <StatusBadge :status="selectedInv.status" />
          </div>
          <div class="flex justify-between">
            <span class="text-slate-600">Total Tagihan:</span>
            <span class="font-bold font-mono text-slate-900">Rp {{ Number(selectedInv?.totalAmount || 0).toLocaleString('id-ID') }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-600">Telah Dibayar:</span>
            <span class="font-bold font-mono text-emerald-600">Rp {{ Number(selectedInv?.paidAmount || 0).toLocaleString('id-ID') }}</span>
          </div>
          <div class="flex justify-between font-bold text-sm pt-2 border-t border-slate-200">
            <span>Sisa Piutang:</span>
            <span class="font-mono text-rose-600">Rp {{ Number(selectedInv?.remainingAmount || 0).toLocaleString('id-ID') }}</span>
          </div>
        </div>

        <div>
          <h4 class="font-bold uppercase text-slate-500 tracking-wider mb-2">Jadwal & Status Termin</h4>
          <div class="border border-slate-200 rounded-lg divide-y divide-slate-100">
            <div v-for="t in selectedInv.termins" :key="t.termNumber" class="p-3 flex justify-between items-center">
              <div>
                <span class="font-bold text-slate-800">Termin Ke-{{ t.termNumber }}</span>
                <span class="block text-[11px] text-slate-500">Jatuh Tempo: {{ t.dueDate }}</span>
              </div>
              <div class="text-right">
                <span class="font-mono font-bold text-slate-900 block">Rp {{ Number(t?.amount || 0).toLocaleString('id-ID') }}</span>
                <StatusBadge :status="t.status" />
              </div>
            </div>
          </div>
        </div>

        <div v-if="selectedInv.remainingAmount > 0" class="pt-2">
          <button
            type="button"
            class="w-full py-2 px-3 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-bold text-xs flex items-center justify-center gap-1.5 transition-colors shadow-2xs"
            @click="remindMarketing(selectedInv)"
          >
            <span>📢 Kirim Pengingat Penagihan ke Marketing</span>
          </button>
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
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import { Plus, Download, Printer } from 'lucide-vue-next';

const toast = useToast();

const createModalOpen = ref(false);
const detailDrawerOpen = ref(false);
const selectedInv = ref(null);

const readyOrders = computed(() => store.orders.filter((o) => o.status !== 'Draft' && o.status !== 'Ditolak'));

const form = ref({
  orderNumber: '',
  invoiceDate: new Date().toISOString().split('T')[0],
  dueDate: '2026-10-31',
  paymentType: 'Termin',
  terminCount: 2,
  totalAmount: 0,
});

const openCreateModal = () => {
  const o = readyOrders.value[0];
  form.value = {
    orderNumber: o ? o.orderNumber : 'ORD-2026-001',
    invoiceDate: new Date().toISOString().split('T')[0],
    dueDate: '2026-10-31',
    paymentType: 'Termin',
    terminCount: 2,
    totalAmount: o ? o.grandTotal : 50000000,
  };
  createModalOpen.value = true;
};

const onOrderChange = () => {
  const o = store.orders.find((ord) => ord.orderNumber === form.value.orderNumber);
  if (o) form.value.totalAmount = o.grandTotal;
};

const saveInvoice = () => {
  const termins = [];
  const part = Math.round(form.value.totalAmount / form.value.terminCount);
  for (let i = 1; i <= form.value.terminCount; i++) {
    termins.push({
      termNumber: i,
      amount: i === form.value.terminCount ? form.value.totalAmount - part * (form.value.terminCount - 1) : part,
      dueDate: form.value.dueDate,
      status: 'Belum Dibayar',
      paidDate: null,
      method: '-',
    });
  }

  storeActions.createInvoice({
    ...form.value,
    termins,
  });

  toast.success('Invoice baru berhasil diterbitkan.');
  createModalOpen.value = false;
};

const openDetail = (inv) => {
  selectedInv.value = inv;
  detailDrawerOpen.value = true;
};

const triggerAction = (type, num) => {
  toast.info(`${type} invoice ${num} berhasil dijalankan.`);
};

const remindMarketing = (inv) => {
  storeActions.sendPaymentReminderToMarketing(
    inv.invoiceNumber,
    `Tagihan invoice ${inv.invoiceNumber} (${inv.clientName}) belum lunas (Sisa: Rp ${Number(inv.remainingAmount || 0).toLocaleString('id-ID')}). Mohon bantu koordinasi dengan PIC client.`
  );
  toast.success(`Pengingat penagihan ${inv.invoiceNumber} berhasil dikirim ke Marketing.`);
};
</script>
