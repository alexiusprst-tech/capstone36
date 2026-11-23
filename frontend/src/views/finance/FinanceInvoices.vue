<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Kelola Invoice Tagihan Proyek</h1>
          <p class="text-sm text-slate-500 mt-1">
            Periksa pesanan, terbitkan tagihan, dan unduh invoice.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors cursor-pointer"
          :disabled="!readyOrders.length || Boolean(orderSync.error)"
          @click="openCreateModal()"
        >
          <Plus class="w-4 h-4" />
          <span>Terbitkan Invoice Baru</span>
        </button>
      </div>
    </template>

    <!-- ANTREAN PESANAN SIAP INVOICE (INCOMING ORDERS FROM MARKETING / DIREKTUR) -->
    <div class="mb-6 ui-panel bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-linear-to-r from-emerald-50/80 via-slate-50 to-emerald-50/40 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
        <div class="flex items-center gap-2.5">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
          <span class="font-semibold text-slate-900 uppercase tracking-wider">Antrean Pesanan Masuk (Siap Diterbitkan Invoice)</span>
          <span
            :class="[
              readyOrders.length > 0
                ? 'bg-emerald-600 text-white font-bold'
                : 'bg-slate-100 text-slate-600 font-semibold',
              'px-2.5 py-0.5 rounded-full text-[11px]'
            ]"
          >
            {{ readyOrders.length }} Pesanan Menunggu
          </span>
        </div>
        <span class="text-slate-500 text-[11px]">
          Pesanan terverifikasi yang menunggu penerbitan berkas invoice resmi oleh Finance
        </span>
      </div>

      <div v-if="orderSync.error" class="p-6 text-center text-slate-600 text-xs" role="alert">
        {{ orderSync.error }}
        <button type="button" class="ml-2 underline" @click="storeActions.loadOrders()">Muat ulang</button>
      </div>
      <div v-else-if="readyOrders.length > 0" class="overflow-x-auto">
        <table class="ui-table-wide ui-table w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/60 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-3 px-4">No. Order</th>
              <th class="py-3 px-4">Client & Proyek</th>
              <th class="py-3 px-4">Tanggal Pesanan</th>
              <th class="py-3 px-4 text-right">Nilai Transaksi</th>
              <th class="py-3 px-4 text-center">Jalur Verifikasi</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="ord in readyOrders" :key="ord.id || ord.orderNumber" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-mono font-semibold text-slate-900">
                <span class="text-alpharo-600 font-bold">{{ ord.orderNumber }}</span>
                <span v-if="ord.projectId" class="block text-[11px] text-slate-400 font-mono">{{ ord.projectId }}</span>
              </td>
              <td class="py-3.5 px-4">
                <div class="font-semibold text-slate-800">{{ ord.clientName }}</div>
                <div class="text-[11px] text-slate-500">{{ ord.projectName || '-' }}</div>
              </td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ ord.date || '-' }}</td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                Rp {{ Number(ord.grandTotal || 0).toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <span
                  v-if="(Number(ord.grandTotal) || 0) <= 5000000"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                  title="Nilai <= Rp 5 Juta: Langsung diteruskan dari Marketing"
                >
                  <CheckCircle2 class="w-3 h-3 text-emerald-600" />
                  Langsung Marketing (&le; 5 Jt)
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200"
                  title="Nilai > Rp 5 Juta: Telah diverifikasi dan disetujui Direktur"
                >
                  <CheckCircle2 class="w-3 h-3 text-blue-600" />
                  Disetujui Direktur (> 5 Jt)
                </span>
              </td>
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                  Siap Invoice
                </span>
              </td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-2">
                  <button
                    type="button"
                    class="px-2.5 py-1.5 text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-lg text-xs font-medium border border-slate-200 transition-colors cursor-pointer"
                    title="Lihat Rincian Pesanan"
                    @click="openOrderDrawer(ord)"
                  >
                    <Eye class="w-3.5 h-3.5 inline mr-1" />
                    <span>Rincian</span>
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition-all cursor-pointer"
                    title="Terbitkan Invoice untuk pesanan ini"
                    @click="openCreateModal(ord)"
                  >
                    <FileText class="w-3.5 h-3.5" />
                    <span>Terbitkan Invoice</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Jika antrean pesanan kosong -->
      <div v-else-if="orderSync.loading" class="p-6 text-center text-slate-600 text-xs" role="status">
        Memuat pesanan…
      </div>
      <div v-else class="p-6 text-center text-slate-400 text-xs">
        <CheckCircle2 class="w-7 h-7 text-emerald-500 mx-auto mb-1.5" />
        <p class="font-medium text-slate-700 text-xs">Tidak ada pesanan baru yang menunggu penerbitan invoice.</p>
        <p class="text-slate-400 text-[11px] mt-0.5">Pesanan baru yang diajukan oleh Marketing akan otomatis muncul di antrean ini.</p>
      </div>
    </div>

    <!-- FILTER STATUS PEMBAYARAN INVOICE -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-4">
      <button
        v-for="st in ['Semua', 'Belum Dibayar', 'Sebagian', 'Lunas', 'Jatuh Tempo']"
        :key="st"
        type="button"
        :class="[
          activeFilter === st
            ? 'bg-alpharo-600 text-white font-semibold shadow-xs'
            : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200',
          'px-4 py-2 rounded-xl text-xs whitespace-nowrap transition-all cursor-pointer font-medium'
        ]"
        @click="activeFilter = st"
      >
        {{ st }}
      </button>
    </div>

    <!-- INVOICES TABLE -->
    <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-semibold text-slate-900 uppercase">Daftar Tagihan Invoice Terbit</span>
        <span class="text-slate-500">{{ filteredInvoices.length }} Berkas Invoice</span>
      </div>

      <div class="overflow-x-auto">
        <table class="ui-table-wide ui-table w-full text-left text-xs border-collapse">
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
            <tr v-for="inv in filteredInvoices" :key="inv.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="py-3.5 px-4 font-mono font-semibold text-slate-900">{{ inv.invoiceNumber }}</td>
              <td class="py-3.5 px-4 font-mono text-slate-600">{{ inv.orderNumber }}</td>
              <td class="py-3.5 px-4 font-semibold text-slate-800">{{ inv.clientName }}</td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ inv.invoiceDate }}</td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ inv.dueDate }}</td>
              <td class="py-3.5 px-4 text-right font-mono font-semibold text-slate-900 whitespace-nowrap">
                Rp {{ Number(inv.totalAmount || 0).toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono text-emerald-600 font-semibold whitespace-nowrap">
                Rp {{ Number(inv.paidAmount || 0).toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono text-rose-600 font-semibold whitespace-nowrap">
                Rp {{ Number(inv.remainingAmount || 0).toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-center"><StatusBadge :status="inv.status" /></td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end">
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-alpharo-50 hover:bg-alpharo-100 text-alpharo-700 border border-alpharo-200 rounded-lg font-semibold text-xs transition-colors cursor-pointer"
                    title="Buka rincian invoice, unduh PDF, dan cetak"
                    @click="openDetail(inv)"
                  >
                    <Eye class="w-3.5 h-3.5" />
                    <span>Detail</span>
                  </button>
                </div>
              </td>
            </tr>

            <!-- EMPTY STATE INVOICE TABLE -->
            <tr v-if="filteredInvoices.length === 0">
              <td colspan="10" class="py-10 text-center text-slate-400">
                <div class="flex flex-col items-center justify-center gap-2">
                  <Receipt class="w-8 h-8 text-slate-300 stroke-[1.5]" />
                  <span class="font-semibold text-slate-600 text-xs">Belum ada berkas tagihan invoice pada filter "{{ activeFilter }}".</span>
                  <span v-if="readyOrders.length > 0" class="text-[11px] text-emerald-700 font-medium">
                    Terdapat <strong>{{ readyOrders.length }} pesanan</strong> pada antrean di atas yang siap diterbitkan invoice. Klik tombol <strong>"Terbitkan Invoice"</strong> di atas.
                  </span>
                  <span v-else class="text-[11px] text-slate-400">
                    Tagihan resmi yang telah diterbitkan akan tercatat di tabel ini beserta status pembayarannya.
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CREATE INVOICE MODAL -->
    <Modal v-model="createModalOpen" title="Terbitkan Invoice Tagihan Baru" subtitle="Tetapkan tanggal penerbitan dan jatuh tempo tagihan" size="lg">
      <form class="space-y-4 text-xs" @submit.prevent="saveInvoice">
        <div>
          <label for="invoice-order" class="block font-semibold text-slate-700 uppercase mb-1">Pilih Order Disetujui</label>
          <select id="invoice-order" v-model="form.orderNumber" required class="w-full px-3 py-2 border rounded-lg bg-white">
            <option disabled value="">Pilih pesanan Siap Invoice</option>
            <option v-for="o in readyOrders" :key="o.orderNumber" :value="o.orderNumber">
              {{ o.orderNumber }} - {{ o.clientName }} (Rp {{ Number(o.grandTotal || 0).toLocaleString('id-ID') }})
            </option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label for="invoice-date" class="block font-semibold text-slate-700 uppercase mb-1">Tanggal Terbit Invoice</label>
            <input id="invoice-date" v-model="form.invoiceDate" type="date" required class="w-full px-3 py-2 border rounded-lg bg-white" />
          </div>
          <div>
            <label for="invoice-due" class="block font-semibold text-slate-700 uppercase mb-1">Tanggal Jatuh Tempo</label>
            <input id="invoice-due" v-model="form.dueDate" :min="form.invoiceDate" type="date" required class="w-full px-3 py-2 border rounded-lg bg-white" />
          </div>
        </div>

        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between">
          <span class="font-semibold uppercase text-slate-700">Skema Tagihan:</span>
          <span class="font-semibold text-emerald-700">Pembayaran Penuh (100%)</span>
        </div>

        <div v-if="reviewOrder" class="p-3 bg-slate-50 border rounded-lg space-y-2">
          <p class="font-semibold">{{ reviewOrder.projectName }} · {{ reviewOrder.clientName }}</p>
          <p>{{ reviewOrder.grandTotal > 5000000 ? 'Disetujui Direktur' : 'Diajukan Marketing' }} · Stok terpenuhi</p>
          <ul class="divide-y divide-slate-200">
            <li v-for="item in reviewOrder.items" :key="item.sku" class="flex flex-wrap justify-between gap-2 py-1">
              <span>{{ item.name }} · {{ item.qty }} {{ item.unit }}</span>
              <span>Rp {{ Number(item.price).toLocaleString('id-ID') }} / {{ item.unit }}</span>
            </li>
          </ul>
          <div class="flex justify-between"><span>Subtotal</span><span>Rp {{ reviewOrder.subtotal.toLocaleString('id-ID') }}</span></div>
          <div class="flex justify-between"><span>Pajak</span><span>Rp {{ reviewOrder.tax.toLocaleString('id-ID') }}</span></div>
          <div class="flex justify-between font-semibold text-slate-900">
            <span>Total Nilai Tagihan:</span>
            <span class="font-mono text-alpharo-600">Rp {{ reviewOrder.grandTotal.toLocaleString('id-ID') }}</span>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button type="button" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer" @click="createModalOpen = false">Batal</button>
          <button type="submit" :disabled="orderSync.busy || !reviewOrder || Boolean(orderSync.error)" class="px-4 py-1.5 bg-alpharo-500 hover:bg-alpharo-600 text-white font-semibold rounded-lg cursor-pointer">Terbitkan Tagihan</button>
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
          <div v-if="selectedInv.paymentMethods?.length" class="flex justify-between gap-3">
            <span class="text-slate-600">Metode pembayaran</span>
            <span>{{ selectedInv.paymentMethods.join(' / ') }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-600">Total Tagihan:</span>
            <span class="font-semibold font-mono text-slate-900">Rp {{ Number(selectedInv?.totalAmount || 0).toLocaleString('id-ID') }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-600">Telah Dibayar:</span>
            <span class="font-semibold font-mono text-emerald-600">Rp {{ Number(selectedInv?.paidAmount || 0).toLocaleString('id-ID') }}</span>
          </div>
          <div class="flex justify-between font-semibold text-sm pt-2 border-t border-slate-200">
            <span>Sisa Piutang:</span>
            <span class="font-mono text-rose-600">Rp {{ Number(selectedInv?.remainingAmount || 0).toLocaleString('id-ID') }}</span>
          </div>
        </div>

        <InvoiceDeliveryPanel
          v-if="detailDrawerOpen && selectedInv.orderNumber"
          :key="selectedInv.orderNumber"
          :order-number="selectedInv.orderNumber"
        />

        <div class="grid gap-2 sm:grid-cols-3 pt-2 border-t border-slate-200">
          <button
            type="button"
            :disabled="isDownloading || isPreviewLoading"
            class="py-2 px-3 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg font-semibold text-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer disabled:opacity-50"
            @click="printInvoice(selectedInv)"
          >
            <Printer class="w-4 h-4 text-slate-500" />
            <span>{{ isPreviewLoading ? 'Memuat...' : 'Cetak / Pratinjau' }}</span>
          </button>
          <button type="button" :disabled="isDownloading" class="py-2 px-3 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg font-semibold text-xs flex items-center justify-center gap-1.5" @click="downloadInvoice(selectedInv, 'docx')">
            <Download class="w-4 h-4" />
            <span>Unduh Word</span>
          </button>
          <button
            type="button"
            class="py-2 px-3 bg-alpharo-600 hover:bg-alpharo-700 text-white rounded-lg font-semibold text-xs flex items-center justify-center gap-1.5 transition-colors shadow-2xs cursor-pointer"
            :disabled="isDownloading" @click="downloadInvoice(selectedInv)"
          >
            <Download class="w-4 h-4" />
            <span>Unduh PDF</span>
          </button>
        </div>

        <div v-if="selectedInv.remainingAmount > 0" class="pt-1">
          <button
            type="button"
            class="w-full py-2 px-3 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-semibold text-xs flex items-center justify-center gap-1.5 transition-colors shadow-2xs cursor-pointer"
            @click="remindMarketing(selectedInv)"
          >
            <span>📢 Kirim Pengingat Penagihan ke Marketing</span>
          </button>
        </div>
      </div>
    </Drawer>

    <!-- READY ORDER DETAIL DRAWER -->
    <Drawer v-model="orderDrawerOpen" :title="`Detail Pesanan: ${selectedOrderDrawer?.orderNumber || ''}`" :subtitle="selectedOrderDrawer?.clientName || ''" size="md">
      <div v-if="selectedOrderDrawer" class="space-y-5 text-xs">
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2.5">
          <div class="flex justify-between items-center">
            <span class="text-slate-500">Status Pesanan</span>
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
              {{ selectedOrderDrawer.status }}
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-600">ID Project:</span>
            <span class="font-mono font-semibold text-slate-900">{{ selectedOrderDrawer.projectId || '-' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-600">Nama Proyek:</span>
            <span class="font-semibold text-slate-900">{{ selectedOrderDrawer.projectName || '-' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-600">Tanggal Pesanan:</span>
            <span class="text-slate-900">{{ selectedOrderDrawer.date || '-' }}</span>
          </div>
          <div class="flex justify-between font-semibold text-sm pt-2 border-t border-slate-200">
            <span>Total Nilai Pesanan:</span>
            <span class="font-mono text-emerald-600 font-bold">
              Rp {{ Number(selectedOrderDrawer.grandTotal || 0).toLocaleString('id-ID') }}
            </span>
          </div>
        </div>

        <div>
          <h4 class="font-semibold uppercase text-slate-500 tracking-wider mb-2">Daftar Barang Pesanan</h4>
          <div class="border border-slate-200 rounded-lg overflow-hidden">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-[11px] border-b border-slate-200">
                <tr>
                  <th class="py-2.5 px-3">Barang</th>
                  <th class="py-2.5 px-3 text-center">Qty</th>
                  <th class="py-2.5 px-3 text-right">Harga</th>
                  <th class="py-2.5 px-3 text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(it, idx) in selectedOrderDrawer.items || []" :key="idx" class="hover:bg-slate-50/50">
                  <td class="py-2.5 px-3">
                    <div class="font-semibold text-slate-800">{{ it.name }}</div>
                    <div class="text-[11px] text-slate-400 font-mono">{{ it.sku }}</div>
                  </td>
                  <td class="py-2.5 px-3 text-center whitespace-nowrap">{{ it.qty }} {{ it.unit || 'Unit' }}</td>
                  <td class="py-2.5 px-3 text-right font-mono whitespace-nowrap">
                    Rp {{ Number(it.price || 0).toLocaleString('id-ID') }}
                  </td>
                  <td class="py-2.5 px-3 text-right font-mono font-semibold text-slate-900 whitespace-nowrap">
                    Rp {{ Number((it.qty || 0) * (it.price || 0)).toLocaleString('id-ID') }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-200">
          <button
            type="button"
            class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg font-semibold text-xs flex items-center justify-center gap-2 shadow-2xs transition-colors cursor-pointer"
            @click="orderDrawerOpen = false; openCreateModal(selectedOrderDrawer);"
          >
            <FileText class="w-4 h-4" />
            <span>Terbitkan Invoice untuk Pesanan Ini</span>
          </button>
        </div>
      </div>
    </Drawer>

    <!-- INVOICE PDF PREVIEW MODAL -->
    <Modal
      v-model="previewModalOpen"
      :title="`Pratinjau Invoice: ${previewInv?.invoiceNumber || ''}`"
      :subtitle="previewInv ? `${previewInv.clientName} • ${previewInv.orderNumber}` : ''"
      size="xl"
    >
      <div class="space-y-3">
        <div v-if="isPreviewLoading" class="h-[68vh] flex flex-col items-center justify-center gap-2 text-xs text-slate-500 bg-slate-50 rounded-lg border border-slate-200">
          <div class="w-6 h-6 border-2 border-alpharo-600 border-t-transparent rounded-full animate-spin"></div>
          <span>Menyiapkan pratinjau PDF invoice...</span>
        </div>
        <iframe
          v-else-if="previewPdfUrl"
          ref="previewIframeRef"
          :src="previewPdfUrl"
          title="Pratinjau PDF Invoice"
          class="w-full h-[68vh] rounded-lg border border-slate-200 bg-slate-100"
        />
        <div class="flex flex-wrap items-center justify-end gap-2 pt-2 border-t border-slate-200 text-xs">
          <a
            v-if="previewPdfUrl"
            :href="previewPdfUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg font-semibold inline-flex items-center gap-1.5"
          >
            <Eye class="w-4 h-4 text-slate-500" />
            <span>Buka di Tab Baru</span>
          </a>
          <button
            v-if="previewPdfUrl"
            type="button"
            class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg font-semibold inline-flex items-center gap-1.5 cursor-pointer"
            @click="triggerIframePrint"
          >
            <Printer class="w-4 h-4 text-slate-500" />
            <span>Cetak</span>
          </button>
          <button
            v-if="previewInv"
            type="button"
            :disabled="isDownloading"
            class="px-3 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white rounded-lg font-semibold inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            @click="downloadInvoice(previewInv, 'pdf')"
          >
            <Download class="w-4 h-4" />
            <span>Unduh PDF</span>
          </button>
          <button
            type="button"
            class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold cursor-pointer"
            @click="closePreviewModal"
          >
            Tutup
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import Modal from '../../components/common/Modal.vue';
import Drawer from '../../components/common/Drawer.vue';
import InvoiceDeliveryPanel from './InvoiceDeliveryPanel.vue';
import { issuedInvoices, readyInvoiceOrders as readyOrders, storeActions, orderSync } from '../../stores/mockData';
import { orderError, orderWorkflow } from '../../services/orderWorkflow';
import { useToast } from '../../services/toast';
import {
  Plus,
  Download,
  Printer,
  Eye,
  FileText,
  CheckCircle2,
  Receipt,
} from 'lucide-vue-next';

const toast = useToast();

onMounted(async () => {
  await storeActions.loadOrders();
});

const activeFilter = ref('Semua');
const filteredInvoices = computed(() => {
  if (activeFilter.value === 'Semua') return issuedInvoices.value;
  return issuedInvoices.value.filter(i => i.status === activeFilter.value);
});

const createModalOpen = ref(false);
const detailDrawerOpen = ref(false);
const selectedInv = ref(null);

const orderDrawerOpen = ref(false);
const selectedOrderDrawer = ref(null);

const businessDate = new Intl.DateTimeFormat('sv-SE', { timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit' });
const form = ref({ orderNumber: '', invoiceDate: '', dueDate: '' });
const reviewOrder = computed(() => readyOrders.value.find(order => order.orderNumber === form.value.orderNumber));

const openOrderDrawer = (order) => {
  selectedOrderDrawer.value = order;
  orderDrawerOpen.value = true;
};

const openCreateModal = (targetOrder = null) => {
  const o = (targetOrder && targetOrder.orderNumber) ? targetOrder : readyOrders.value[0];
  form.value = {
    orderNumber: o ? o.orderNumber : '',
    invoiceDate: businessDate.format(new Date()),
    dueDate: businessDate.format(Date.now() + 14 * 86400000),
  };
  createModalOpen.value = true;
};

const saveInvoice = async () => {
  if (orderSync.busy) return;
  try {
    const { delivery } = await storeActions.createInvoice(form.value);
    if (delivery && !delivery.sent) {
      toast.warning('Invoice sudah terbit. Email belum terkirim. Periksa pengiriman pada detail invoice.');
    } else {
      toast.success(delivery ? `Invoice terbit dan email dikirim ke ${delivery.recipient}.` : 'Invoice berhasil diterbitkan.');
    }
    createModalOpen.value = false;
  } catch (error) {
    toast.error(orderError(error));
    if (error.response?.status === 409 || error.response?.status === 422) await storeActions.loadOrders();
  }
};

const openDetail = (inv) => {
  selectedInv.value = inv;
  detailDrawerOpen.value = true;
};

const isDownloading = ref(false);
const isPreviewLoading = ref(false);
const previewModalOpen = ref(false);
const previewPdfUrl = ref('');
const previewInv = ref(null);
const previewIframeRef = ref(null);

const revokePreviewUrl = () => {
  if (previewPdfUrl.value) {
    URL.revokeObjectURL(previewPdfUrl.value);
    previewPdfUrl.value = '';
  }
};

const closePreviewModal = () => {
  previewModalOpen.value = false;
  revokePreviewUrl();
  previewInv.value = null;
};

watch(previewModalOpen, (open) => {
  if (!open) {
    revokePreviewUrl();
    previewInv.value = null;
  }
});

onBeforeUnmount(() => {
  revokePreviewUrl();
});

const downloadInvoice = async (inv, format = 'pdf') => {
  if (!inv) return;
  if (isDownloading.value) return;
  isDownloading.value = true;
  try {
    toast.info(`Menyiapkan ${format === 'docx' ? 'Word' : 'PDF'} invoice ${inv.invoiceNumber}...`);
    await orderWorkflow.downloadInvoice(inv.orderNumber, false, format);
    toast.success(`Invoice ${inv.invoiceNumber} berhasil diunduh.`);
  } catch (error) {
    toast.error('Gagal mengunduh invoice: ' + orderError(error));
  } finally {
    isDownloading.value = false;
  }
};

const printInvoice = async (inv) => {
  if (!inv || isPreviewLoading.value) return;
  isPreviewLoading.value = true;
  previewInv.value = inv;
  revokePreviewUrl();
  previewModalOpen.value = true;
  try {
    const file = await orderWorkflow.fetchInvoiceBlob(inv.orderNumber, 'pdf', true);
    previewPdfUrl.value = file.url;
  } catch (error) {
    previewModalOpen.value = false;
    previewInv.value = null;
    toast.error('Gagal membuka pratinjau invoice: ' + orderError(error));
  } finally {
    isPreviewLoading.value = false;
  }
};

const triggerIframePrint = () => {
  const frameWindow = previewIframeRef.value?.contentWindow;
  if (frameWindow) {
    frameWindow.focus();
    frameWindow.print();
  } else if (previewPdfUrl.value) {
    window.open(previewPdfUrl.value, '_blank', 'noopener,noreferrer');
  }
};

const remindMarketing = (inv) => {
  storeActions.sendPaymentReminderToMarketing(
    inv.invoiceNumber,
    `Tagihan invoice ${inv.invoiceNumber} (${inv.clientName}) belum lunas (Sisa: Rp ${Number(inv.remainingAmount || 0).toLocaleString('id-ID')}). Mohon bantu koordinasi dengan PIC client.`
  );
  toast.success(`Pengingat penagihan ${inv.invoiceNumber} berhasil dikirim ke Marketing.`);
};
</script>
