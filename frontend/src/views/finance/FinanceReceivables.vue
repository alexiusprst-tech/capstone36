<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Piutang Usaha & Tagihan Rekanan</h1>
          <p class="text-sm text-slate-500 mt-1">
            Monitoring umur piutang (aging schedule), tagihan jatuh tempo, dan koordinasi penagihan dengan Marketing.
          </p>
        </div>
      </div>
    </template>

    <!-- KPIS FOR RECEIVABLES -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
      <KpiCard title="Total Piutang Usaha" :value="totalReceivable" :is-currency="true" icon-variant="blue" />
      <KpiCard title="Piutang Berjalan (Lancar)" :value="currentReceivable" :is-currency="true" icon-variant="blue" />
      <KpiCard title="Mendekati Tempo" :value="dueReceivable" :is-currency="true" icon-variant="blue" />
      <KpiCard title="Lewat Jatuh Tempo (Overdue)" :value="overdueReceivable" :is-currency="true" icon-variant="blue" />
    </div>

    <!-- RECEIVABLES TABLE -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-900 uppercase">Daftar Piutang Rekanan Belum Lunas</span>
        <span class="text-slate-500">{{ receivableList.length }} Rekening Tagihan</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">No. Invoice</th>
              <th class="py-3 px-4 text-right">Total Tagihan</th>
              <th class="py-3 px-4 text-right">Telah Dibayar</th>
              <th class="py-3 px-4 text-right">Sisa Piutang</th>
              <th class="py-3 px-4">Jatuh Tempo</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="inv in receivableList" :key="inv.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-bold text-slate-800">{{ inv.clientName }}</td>
              <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ inv.invoiceNumber }}</td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                Rp {{ Number(inv.totalAmount || 0).toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono text-emerald-600 font-semibold whitespace-nowrap">
                Rp {{ Number(inv.paidAmount || 0).toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono text-rose-600 font-bold whitespace-nowrap">
                Rp {{ Number(inv.remainingAmount || 0).toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ inv.dueDate }}</td>
              <td class="py-3.5 px-4 text-center"><StatusBadge :status="inv.status" /></td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded font-bold text-xs transition-colors shadow-2xs"
                    title="Kirim notifikasi ke Marketing untuk follow-up PIC"
                    @click="openRemindModal(inv)"
                  >
                    Ingatkan Marketing
                  </button>
                  <router-link
                    to="/finance/payments"
                    class="px-2.5 py-1 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded font-bold text-xs"
                  >
                    Bayar
                  </router-link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- REMIND MARKETING MODAL -->
    <Modal
      v-model="remindModalOpen"
      title="Kirim Notifikasi Penagihan ke Marketing"
      :subtitle="`Invoice: ${selectedInvoice?.invoiceNumber || ''} - ${selectedInvoice?.clientName || ''}`"
      size="md"
    >
      <form class="space-y-4 text-xs" @submit.prevent="sendReminder">
        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
          <div class="flex justify-between">
            <span class="text-slate-500">Sisa Tagihan Piutang:</span>
            <span class="font-mono font-bold text-rose-600">Rp {{ (selectedInvoice?.remainingAmount || 0).toLocaleString('id-ID') }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Tanggal Jatuh Tempo:</span>
            <span class="font-bold text-slate-800">{{ selectedInvoice?.dueDate }}</span>
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase mb-1">Catatan Instruksi untuk Marketing</label>
          <textarea
            v-model="remindNote"
            rows="3"
            required
            placeholder="Contoh: Tagihan mendekati jatuh tempo. Mohon bantu follow-up PIC client terkait SP2D atau transfer termin..."
            class="w-full px-3 py-2 border rounded-lg bg-white"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button type="button" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg" @click="remindModalOpen = false">Batal</button>
          <button type="submit" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-lg transition-colors">Kirim Notifikasi ke Marketing</button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import KpiCard from '../../components/common/KpiCard.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import Modal from '../../components/common/Modal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';

const toast = useToast();

const remindModalOpen = ref(false);
const selectedInvoice = ref(null);
const remindNote = ref('');

const receivableList = computed(() => store.invoices.filter((i) => i.remainingAmount > 0));

const totalReceivable = computed(() => receivableList.value.reduce((s, i) => s + (Number(i.remainingAmount) || 0), 0));
const overdueReceivable = computed(() => receivableList.value.filter((i) => i.status === 'Jatuh Tempo').reduce((s, i) => s + (Number(i.remainingAmount) || 0), 0));
const dueReceivable = computed(() => receivableList.value.filter((i) => i.status === 'Sebagian / Termin').reduce((s, i) => s + (Number(i.remainingAmount) || 0), 0));
const currentReceivable = computed(() => totalReceivable.value - overdueReceivable.value);

const openRemindModal = (inv) => {
  selectedInvoice.value = inv;
  remindNote.value = `Tagihan invoice ${inv.invoiceNumber} belum lunas (Sisa: Rp ${Number(inv.remainingAmount || 0).toLocaleString('id-ID')}). Mohon bantu konfirmasi tanggal pembayaran ke PIC client.`;
  remindModalOpen.value = true;
};

const sendReminder = () => {
  if (!selectedInvoice.value) return;
  storeActions.sendPaymentReminderToMarketing(selectedInvoice.value.invoiceNumber, remindNote.value);
  toast.success(`Notifikasi penagihan untuk ${selectedInvoice.value.invoiceNumber} berhasil dikirim ke Marketing.`);
  remindModalOpen.value = false;
};
</script>
