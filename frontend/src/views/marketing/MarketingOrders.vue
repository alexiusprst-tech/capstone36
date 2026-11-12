<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Kelola Order & Proyek</h1>
          <p class="text-sm text-slate-500 mt-1">
            Ajukan pesanan setelah stok teralokasi penuh. Nilai di atas Rp5 juta memerlukan persetujuan Direktur.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Link
            href="/marketing/orders/create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-600 text-white font-semibold rounded-lg text-xs shadow-xs transition-all hover:bg-alpharo-700"
          >
            <Plus class="w-4 h-4 text-amber-300 stroke-[2.5]" />
            <span>Buat Order Baru</span>
          </Link>
        </div>
      </div>
    </template>

    <!-- BANNER: PENGAJUAN PESANAN MASUK DARI CLIENT -->
    <div
      v-if="pendingClientOrders.length > 0"
      class="bg-linear-to-r from-alpharo-50 via-alpharo-50 to-alpharo-50 border border-alpharo-200/90 rounded-xl p-4 mb-5 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
    >
      <div class="flex items-start sm:items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-alpharo-600 text-white flex items-center justify-center shrink-0 shadow-xs">
          <Inbox class="w-5 h-5 text-amber-300 stroke-[2.5]" />
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h3 class="text-sm font-semibold text-slate-900">Pengajuan Pesanan Baru dari Rekanan Client</h3>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-alpharo-100 text-alpharo-700 border border-alpharo-200 animate-pulse">
              {{ pendingClientOrders.length }} Pengajuan Menunggu
            </span>
          </div>
          <p class="text-xs text-slate-600 mt-0.5">
            Instansi sekolah/rekanan telah mengajukan daftar kebutuhan barang mandiri. Tinjau spesifikasi, ketersediaan stok, dan lakukan konfirmasi penerimaan untuk diteruskan ke Direktur.
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <button
          type="button"
          class="px-3.5 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg shadow-xs flex items-center gap-1.5 transition-colors"
          @click="statusFilter = statusFilter === 'Diajukan Client' ? '' : 'Diajukan Client'"
        >
          <Filter class="w-3.5 h-3.5" />
          <span>{{ statusFilter === 'Diajukan Client' ? 'Tampilkan Semua Order' : 'Filter Pengajuan Client' }}</span>
        </button>
      </div>
    </div>

    <!-- QUICK STATUS TABS & SEARCH / FILTER -->
    <div class="ui-panel bg-white p-4 rounded-xl border border-slate-200/90 mb-5 space-y-3 shadow-2xs">
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="relative w-full sm:w-80">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari no order, client, proyek..."
            class="w-full pl-9 pr-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-600"
          />
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
          <select
            v-model="statusFilter"
            class="text-xs px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 focus:outline-hidden focus:ring-2 focus:ring-alpharo-600"
          >
            <option value="">Semua Status</option>
            <option value="Diajukan Client">Diajukan Client</option>
            <option value="Draft">Draft</option>
            <option value="Siap Invoice">Siap Invoice</option>
            <option value="Menunggu Verifikasi">Menunggu Verifikasi</option>
            <option value="Disetujui">Disetujui</option>
            <option value="Menunggu Pembayaran">Menunggu Pembayaran</option>
            <option value="Ditolak">Ditolak</option>
            <option value="Diproses">Diproses</option>
            <option value="Selesai">Selesai</option>
          </select>
        </div>
      </div>

      <!-- FILTER TABS -->
      <div class="flex items-center gap-1.5 overflow-x-auto pt-2 border-t border-slate-100 text-[11px]">
        <button
          type="button"
          :class="[
            !statusFilter
              ? 'bg-alpharo-600 text-white font-semibold'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
            'px-3 py-1 rounded-full whitespace-nowrap transition-colors'
          ]"
          @click="statusFilter = ''"
        >
          Semua ({{ totalOrdersCount }})
        </button>

        <button
          type="button"
          :class="[
            statusFilter === 'Diajukan Client'
              ? 'bg-alpharo-600 text-white font-semibold shadow-xs'
              : pendingClientOrders.length > 0
                ? 'bg-alpharo-50 text-alpharo-700 font-semibold border border-alpharo-200 hover:bg-alpharo-100'
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
            'px-3 py-1 rounded-full whitespace-nowrap transition-colors flex items-center gap-1.5'
          ]"
          @click="statusFilter = 'Diajukan Client'"
        >
          <span>Pengajuan Masuk Client</span>
          <span
            v-if="pendingClientOrders.length > 0"
            :class="statusFilter === 'Diajukan Client' ? 'bg-white text-alpharo-700' : 'bg-alpharo-600 text-white'"
            class="px-1.5 py-0.2 rounded-full text-[11px] font-semibold"
          >
            {{ pendingClientOrders.length }}
          </span>
        </button>

        <button
          type="button"
          :class="[
            statusFilter === 'Draft'
              ? 'bg-slate-800 text-white font-semibold'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
            'px-3 py-1 rounded-full whitespace-nowrap transition-colors'
          ]"
          @click="statusFilter = 'Draft'"
        >
          Draft ({{ getStatusCount('Draft') }})
        </button>

        <button
          type="button"
          :class="[
            statusFilter === 'Menunggu Verifikasi'
              ? 'bg-amber-500 text-white font-semibold'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
            'px-3 py-1 rounded-full whitespace-nowrap transition-colors'
          ]"
          @click="statusFilter = 'Menunggu Verifikasi'"
        >
          Menunggu Verifikasi ({{ getStatusCount('Menunggu Verifikasi') }})
        </button>

        <button
          type="button"
          :class="[
            statusFilter === 'Disetujui'
              ? 'bg-emerald-600 text-white font-semibold'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
            'px-3 py-1 rounded-full whitespace-nowrap transition-colors'
          ]"
          @click="statusFilter = 'Disetujui'"
        >
          Disetujui ({{ getStatusCount('Disetujui') }})
        </button>

        <button
          type="button"
          :class="[
            statusFilter === 'Diproses'
              ? 'bg-alpharo-600 text-white font-semibold'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
            'px-3 py-1 rounded-full whitespace-nowrap transition-colors'
          ]"
          @click="statusFilter = 'Diproses'"
        >
          Diproses ({{ getStatusCount('Diproses') }})
        </button>

        <button
          type="button"
          :class="[
            statusFilter === 'Selesai'
              ? 'bg-emerald-700 text-white font-semibold'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
            'px-3 py-1 rounded-full whitespace-nowrap transition-colors'
          ]"
          @click="statusFilter = 'Selesai'"
        >
          Selesai ({{ getStatusCount('Selesai') }})
        </button>
      </div>
    </div>

    <!-- ORDERS TABLE -->
    <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="ui-table-wide ui-table w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
              <th class="py-3 px-4 min-w-[140px] whitespace-nowrap">No. Order</th>
              <th class="py-3 px-4 min-w-[200px]">Client & Proyek</th>
              <th class="py-3 px-4 whitespace-nowrap">Tanggal</th>
              <th class="py-3 px-4 text-right whitespace-nowrap">Nilai Transaksi</th>
              <th class="py-3 px-4 text-center whitespace-nowrap min-w-[120px]">Status</th>
              <th class="py-3 px-4 text-center whitespace-nowrap min-w-[140px]">Kelengkapan</th>
              <th class="py-3 px-4 text-right whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="order in filteredOrders"
              :key="order.id"
              :class="[
                order.status === 'Diajukan Client' ? 'bg-alpharo-50/30 hover:bg-alpharo-50/60' : 'hover:bg-slate-50/70',
                'transition-colors'
              ]"
            >
              <td class="py-3.5 px-4 font-mono font-semibold text-slate-900 whitespace-nowrap">
                <div class="flex flex-col items-start gap-1">
                  <!-- If Client-submitted order, quick route to create order -->
                  <Link
                    v-if="order.status === 'Diajukan Client'"
                    :href="`/marketing/orders/create?fromOrderId=${order.id}`"
                    class="text-alpharo-600 hover:text-alpharo-800 hover:underline inline-flex items-center gap-1.5 group transition-colors cursor-pointer"
                    :title="`Klik ${order.orderNumber} untuk buka 'Buat Order Baru' berbasis pengajuan ini`"
                  >
                    <span class="underline decoration-alpharo-600/40 group-hover:decoration-alpharo-600">{{ order.orderNumber }}</span>
                    <ExternalLink class="w-3.5 h-3.5 text-alpharo-500 opacity-60 group-hover:opacity-100 transition-opacity shrink-0" />
                  </Link>
                  <!-- For standard orders, clicking order number directly opens Drawer Detail & Monitoring -->
                  <button
                    v-else
                    type="button"
                    @click="viewDetail(order)"
                    class="text-alpharo-600 hover:text-alpharo-800 hover:underline inline-flex items-center gap-1.5 group transition-colors cursor-pointer text-left font-mono font-semibold"
                    :title="`Buka Rincian & Monitoring ${order.orderNumber}`"
                  >
                    <span class="underline decoration-alpharo-600/40 group-hover:decoration-alpharo-600">{{ order.orderNumber }}</span>
                  </button>

                  <div v-if="order.isHold">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-700 border border-rose-200 whitespace-nowrap">
                      Ditahan Direktur
                    </span>
                  </div>
                </div>
              </td>
              <td class="py-3.5 px-4">
                <span class="font-semibold text-slate-900 block">{{ order.clientName }}</span>
                <span class="text-[11px] text-slate-400 block truncate max-w-xs">{{ order.projectName }}</span>
                <span v-if="order.marketingName" class="text-[10px] text-slate-500 font-medium inline-block mt-0.5">
                  PIC: {{ order.marketingName }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                {{ order.date }}
              </td>
              <td class="py-3.5 px-4 text-right font-semibold text-slate-900 whitespace-nowrap">
                Rp {{ (order.grandTotal || 0).toLocaleString('id-ID') }}
              </td>
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :status="order.status" />
                <p v-if="order.serverManaged && order.status === 'Draft'" class="mt-1 text-[11px]" :class="catalogIssues(order).length ? 'text-amber-800' : 'text-slate-500'">{{ catalogIssues(order).length ? 'Perbarui barang katalog' : order.stockReadiness?.status }}</p>
              </td>
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <span
                  v-if="order.status === 'Diajukan Client'"
                  class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-alpharo-700 bg-alpharo-50 px-2.5 py-1 rounded-full border border-alpharo-200 whitespace-nowrap shadow-2xs"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-alpharo-600 shrink-0"></span>
                  <span class="whitespace-nowrap">{{ (order.items || []).length }} Item Diajukan</span>
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full whitespace-nowrap"
                >
                  <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="getDocCount(order.documents) === 4 ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                  <span class="whitespace-nowrap">{{ getDocCount(order.documents) }}/4 Dokumen</span>
                </span>
              </td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- KHUSUS DIAJUKAN CLIENT: TOMBOL BUAT ORDER BARU -->
                  <Link
                    v-if="order.status === 'Diajukan Client'"
                    :href="`/marketing/orders/create?fromOrderId=${order.id}`"
                    class="px-2.5 py-1.5 bg-alpharo-600 text-white rounded-lg text-[11px] font-semibold flex items-center gap-1.5 shadow-2xs transition-all hover:bg-alpharo-700"
                    :title="`Buat Order Baru berbasis pengajuan ${order.orderNumber}`"
                  >
                    <ClipboardCheck class="w-3.5 h-3.5 text-amber-300 stroke-[2.5]" />
                    <span>Buat Order Baru</span>
                  </Link>

                  <!-- SATU BUTTON AKSI: RINCIAN BARANG & MONITORING -->
                  <button
                    type="button"
                    class="p-1.5 text-alpharo-500 hover:text-alpharo-700 hover:bg-alpharo-50 rounded transition-colors cursor-pointer"
                    title="Lihat Rincian Barang & Monitoring Order"
                    @click="viewDetail(order)"
                  >
                    <Eye class="w-4 h-4" />
                  </button>

                  <Link
                    v-if="order.status === 'Draft'"
                    :href="`/marketing/orders/${order.id}/edit`"
                    class="p-1.5 text-slate-600 hover:bg-slate-100 rounded transition-colors cursor-pointer"
                    :title="catalogIssues(order).length ? 'Perbarui Barang' : 'Edit Order'"
                    :aria-label="catalogIssues(order).length ? 'Perbarui Barang' : 'Edit Order'"
                  >
                    <Pencil class="w-4 h-4" />
                  </Link>

                  <button
                    v-if="['Draft', 'Diajukan Client'].includes(order.status) || (!order.serverManaged && order.status === 'Menunggu Verifikasi')"
                    type="button"
                    :class="[
                      (Number(order.grandTotal) || 0) > 5000000
                        ? 'bg-amber-600 hover:bg-amber-700 text-white shadow-amber-600/20'
                        : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-600/20',
                      'px-2.5 py-1.5 rounded-lg text-[11px] font-semibold flex items-center gap-1.5 cursor-pointer shadow-2xs transition-all whitespace-nowrap active:scale-95'
                    ]"
                    :title="(Number(order.grandTotal) || 0) > 5000000
                      ? `Nominal Rp ${Number(order.grandTotal || 0).toLocaleString('id-ID')} (> Rp 5 Juta) - Ajukan ke Direktur untuk verifikasi & persetujuan`
                      : `Nominal Rp ${Number(order.grandTotal || 0).toLocaleString('id-ID')} (<= Rp 5 Juta) - Ajukan langsung ke Finance (Siap Invoice)`"
                    :disabled="orderSync.busy || orderSync.loading"
                    @click="submitVerification(order)"
                  >
                    <Send class="w-3.5 h-3.5" />
                    <span>{{ (Number(order.grandTotal) || 0) > 5000000 ? 'Ajukan ke Direktur' : 'Ajukan ke Finance' }}</span>
                  </button>

                  <button
                    v-if="order.status === 'Draft'"
                    type="button"
                    class="p-1.5 text-rose-600 hover:bg-rose-50 rounded transition-colors cursor-pointer"
                    title="Hapus Draft"
                    @click="confirmDelete(order)"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="filteredOrders.length === 0">
              <td colspan="7" class="py-12 text-center text-slate-400"><div class="ui-empty-state">
                Tidak ada data order yang sesuai.
              </div></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL REVIEW & ACCEPTANCE PENGAJUAN CLIENT -->
    <Modal
      v-model="reviewModalOpen"
      :title="`Tinjau & Terima Pengajuan Pesanan: ${reviewOrder?.orderNumber || ''}`"
      :subtitle="`Pengajuan kebutuhan mandiri oleh ${reviewOrder?.clientName || ''} (${reviewOrder?.contactPerson || ''})`"
      size="2xl"
    >
      <div v-if="reviewOrder" class="space-y-6">
        <!-- HEADER SUMMARY BADGE -->
        <div class="bg-linear-to-r from-alpharo-50 via-alpharo-50 to-alpharo-50 border border-alpharo-200 rounded-xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-alpharo-600 text-white flex items-center justify-center shrink-0 shadow-xs">
              <ClipboardCheck class="w-5 h-5 text-amber-300 stroke-[2.5]" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-900">{{ reviewOrder.projectName }}</span>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-alpharo-100 text-alpharo-700 border border-alpharo-200">
                  Pengajuan Rekanan Client
                </span>
              </div>
              <p class="text-[11px] text-slate-500 mt-0.5">
                Tanggal Pengajuan: <strong class="text-slate-700">{{ reviewOrder.date }}</strong> &bull; Pemohon: <strong class="text-slate-700">{{ reviewOrder.submittedBy }}</strong>
              </p>
            </div>
          </div>
          <div class="text-right shrink-0">
            <span class="text-[11px] uppercase font-semibold text-slate-400 block tracking-wider">Estimasi Nilai</span>
            <span class="text-base font-semibold font-mono text-alpharo-600">
              Rp {{ reviewGrandTotal.toLocaleString('id-ID') }}
            </span>
          </div>
        </div>

        <!-- SECTION 1: PROFIL CLIENT & DETAIL PENGIRIMAN -->
        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
          <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-700 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-alpharo-600 text-white flex items-center justify-center text-[11px]">1</span>
            Data Instansi, PIC & Lokasi Pengiriman
          </h4>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div class="bg-white p-3 rounded-lg border border-slate-200">
              <span class="text-[11px] font-semibold text-slate-400 uppercase block">Instansi & PIC</span>
              <p class="font-semibold text-slate-800 mt-0.5">{{ reviewOrder.clientName }}</p>
              <p class="text-slate-600 flex items-center gap-1.5 mt-1">
                <User class="w-3.5 h-3.5 text-slate-400" />
                <span>{{ reviewOrder.contactPerson || '-' }}</span>
              </p>
              <p class="text-slate-600 flex items-center gap-1.5 mt-0.5">
                <Phone class="w-3.5 h-3.5 text-slate-400" />
                <a
                  :href="`https://wa.me/${(reviewOrder.phone || '').replace(/[^0-9]/g, '')}`"
                  target="_blank"
                  class="text-alpharo-600 font-semibold hover:underline flex items-center gap-1"
                >
                  {{ reviewOrder.phone || '-' }}
                  <ExternalLink class="w-3 h-3" />
                </a>
              </p>
              <p class="text-slate-600 flex items-center gap-1.5 mt-0.5">
                <Mail class="w-3.5 h-3.5 text-slate-400" />
                <span>{{ reviewOrder.email || '-' }}</span>
              </p>
            </div>

            <div class="bg-white p-3 rounded-lg border border-slate-200">
              <span class="text-[11px] font-semibold text-slate-400 uppercase block">Lokasi & Target Pengiriman</span>
              <p class="text-slate-700 flex items-start gap-1.5 mt-1 leading-relaxed">
                <MapPin class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" />
                <span>{{ reviewOrder.address || '-' }}</span>
              </p>
              <div class="flex items-center justify-between pt-2 mt-2 border-t border-slate-100">
                <span class="text-slate-500">Target Tanggal Kirim:</span>
                <span class="font-semibold text-slate-800">{{ reviewOrder.targetDeliveryDate || 'Fleksibel' }}</span>
              </div>
              <div class="flex items-center justify-between mt-1">
                <span class="text-slate-500">Tingkat Urgensi:</span>
                <span
                  class="px-2 py-0.5 rounded text-[11px] font-semibold"
                  :class="reviewOrder.urgency === 'Mendesak' ? 'bg-rose-100 text-rose-700' : 'bg-alpharo-100 text-alpharo-700'"
                >
                  {{ reviewOrder.urgency || 'Normal' }}
                </span>
              </div>
            </div>
          </div>

          <!-- CATATAN KHUSUS & BERKAS DARI CLIENT -->
          <div v-if="reviewOrder.notes || reviewOrder.supportingDocument" class="bg-white p-3 rounded-lg border border-slate-200 text-xs">
            <div v-if="reviewOrder.notes" class="mb-2">
              <span class="text-[11px] font-semibold text-slate-400 uppercase block">Catatan Kebutuhan Client</span>
              <p class="text-slate-700 italic mt-0.5">"{{ reviewOrder.notes }}"</p>
            </div>
            <div v-if="reviewOrder.supportingDocument" class="flex items-center justify-between pt-2 border-t border-slate-100">
              <span class="text-slate-500 flex items-center gap-1.5">
                <FileText class="w-4 h-4 text-alpharo-500" />
                <span>Lampiran Dokumen Rekanan: <strong>{{ reviewOrder.supportingDocument }}</strong></span>
              </span>
              <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                Tersedia
              </span>
            </div>
          </div>
        </div>

        <!-- SECTION 2: REVIEW DAFTAR ITEM & VERIFIKASI STOK GUDANG -->
        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-700 flex items-center gap-2">
              <span class="w-5 h-5 rounded-full bg-alpharo-600 text-white flex items-center justify-center text-[11px]">2</span>
              Review Barang & Pengecekan Ketersediaan Stok Gudang
            </h4>
            <span class="text-[11px] text-slate-500 font-medium">
              {{ reviewForm.items.length }} Item Dipesan
            </span>
          </div>

          <div class="border border-slate-200 rounded-lg overflow-hidden bg-white">
            <table class="ui-table w-full text-left text-xs">
              <thead class="bg-slate-100 text-slate-600 font-semibold text-[11px] border-b border-slate-200">
                <tr>
                  <th class="p-2.5">Barang & Spesifikasi</th>
                  <th class="p-2.5 text-center">Status Stok Gudang</th>
                  <th class="p-2.5 text-center">Qty Diminta</th>
                  <th class="p-2.5 text-right">Harga Satuan (Rp)</th>
                  <th class="p-2.5 text-right">Total (Rp)</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(it, idx) in reviewForm.items" :key="idx" class="hover:bg-slate-50">
                  <td class="p-2.5">
                    <div class="font-semibold text-slate-800">{{ it.name }}</div>
                    <div class="text-[11px] font-mono text-slate-400 mt-0.5">{{ it.sku }}</div>
                    <div v-if="it.specs" class="text-[11px] text-slate-500 italic mt-0.5">
                      Spec: {{ it.specs }}
                    </div>
                  </td>
                  <td class="p-2.5 text-center whitespace-nowrap">
                    <span
                      class="px-2 py-0.5 rounded text-[11px] font-semibold"
                      :class="getStockInfo(it.sku, it.qty).class"
                    >
                      {{ getStockInfo(it.sku, it.qty).label }}
                    </span>
                  </td>
                  <td class="p-2.5 text-center whitespace-nowrap">
                    <span class="font-semibold text-slate-900">{{ it.qty }}</span>
                    <span class="text-slate-500 ml-1 text-[11px]">{{ it.unit }}</span>
                  </td>
                  <td class="p-2.5 text-right">
                    <input
                      v-model.number="it.price" step="0.01" min="0.01"
                      type="number"
                      class="w-28 px-2 py-1 text-right text-xs rounded border border-slate-300 font-mono font-semibold"
                      title="Ubah harga satuan bila ada negosiasi / kesepakatan"
                    />
                  </td>
                  <td class="p-2.5 text-right font-mono font-semibold text-slate-800 whitespace-nowrap">
                    Rp {{ ((it.qty || 0) * (it.price || 0)).toLocaleString('id-ID') }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- RINGKASAN KALKULASI NILAI -->
          <div class="bg-white p-3 rounded-lg border border-slate-200 text-xs space-y-1.5 max-w-sm ml-auto">
            <div class="flex justify-between text-slate-600">
              <span>Subtotal Item:</span>
              <span class="font-mono">Rp {{ reviewSubtotal.toLocaleString('id-ID') }}</span>
            </div>
            <div class="flex justify-between text-slate-600">
              <span>PPN 11%:</span>
              <span class="font-mono">Rp {{ reviewTax.toLocaleString('id-ID') }}</span>
            </div>
            <div class="pt-1.5 border-t border-slate-200 flex justify-between font-semibold text-sm text-slate-900">
              <span>Total Penawaran:</span>
              <span class="font-mono text-alpharo-600">Rp {{ reviewGrandTotal.toLocaleString('id-ID') }}</span>
            </div>
          </div>
        </div>

        <!-- SECTION 3: KEPUTUSAN PENERIMAAN MARKETING -->
        <div class="ui-panel bg-white p-4 rounded-xl border border-alpharo-200 bg-alpharo-50/40 space-y-3">
          <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-800 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-alpharo-600 text-white flex items-center justify-center text-[11px]">3</span>
            Konfirmasi Keputusan & Tindakan Penerimaan
          </h4>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">
              Catatan Telaah Marketing (Akan masuk ke Timeline & Surat Penawaran)
            </label>
            <textarea
              v-model="reviewForm.notes"
              rows="2"
              required
              class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-600"
              placeholder="Catatan validasi spesifikasi barang, ketersediaan gudang, kesiapan armada..."
            ></textarea>
          </div>

          <div class="space-y-2 pt-1">
            <label class="block text-xs font-semibold text-slate-700">Pilih Tindakan Lanjutan:</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <label
                class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-colors"
                :class="reviewForm.actionType === 'verify' ? 'bg-alpharo-50 border-alpharo-600 ring-1 ring-alpharo-600' : 'bg-white border-slate-200'"
              >
                <input
                  v-model="reviewForm.actionType"
                  type="radio"
                  value="verify"
                  class="mt-0.5 text-alpharo-600 focus:ring-alpharo-600"
                />
                <div>
                  <span class="text-xs font-semibold text-slate-900 block">
                    {{ reviewGrandTotal > 5000000 ? 'Terima & Ajukan ke Direktur' : 'Terima & Ajukan ke Finance' }}
                  </span>
                  <span class="text-[11px] text-slate-500 leading-relaxed block mt-0.5">
                    {{ reviewGrandTotal > 5000000
                      ? 'Stok wajib terpenuhi. Nominal Rp ' + reviewGrandTotal.toLocaleString('id-ID') + ' (> Rp 5 Juta) memerlukan verifikasi & approval Direktur.'
                      : 'Stok wajib terpenuhi. Nominal Rp ' + reviewGrandTotal.toLocaleString('id-ID') + ' (<= Rp 5 Juta) langsung masuk ke Finance (Siap Invoice).'
                    }}
                  </span>
                </div>
              </label>

              <label
                class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-colors"
                :class="reviewForm.actionType === 'draft' ? 'bg-alpharo-50 border-alpharo-600 ring-1 ring-alpharo-600' : 'bg-white border-slate-200'"
              >
                <input
                  v-model="reviewForm.actionType"
                  type="radio"
                  value="draft"
                  class="mt-0.5 text-alpharo-600 focus:ring-alpharo-600"
                />
                <div>
                  <span class="text-xs font-semibold text-slate-900 block">Terima & Simpan ke Draft SPH</span>
                  <span class="text-[11px] text-slate-500 leading-relaxed block mt-0.5">
                    Status menjadi <strong>Draft</strong> untuk penyusunan surat penawaran harga formal sebelum diajukan untuk persetujuan.
                  </span>
                </div>
              </label>
            </div>
          </div>
        </div>

        <!-- FOOTER BUTTONS -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
          <button
            type="button"
            class="text-xs font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50 px-3 py-2 rounded-lg transition-colors flex items-center gap-1.5"
            @click="openRejectPrompt"
          >
            <XCircle class="w-4 h-4" />
            <span>Tolak / Minta Klarifikasi</span>
          </button>

          <div class="flex items-center gap-2">
            <button
              type="button"
              class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg"
              @click="reviewModalOpen = false"
            >
              Tutup
            </button>
            <Link
              v-if="reviewOrder"
              :href="`/marketing/orders/create?fromOrderId=${reviewOrder.id}`"
              class="px-4 py-2 text-xs font-semibold text-alpharo-700 bg-alpharo-50 hover:bg-alpharo-100 border border-alpharo-200 rounded-lg flex items-center gap-1.5 transition-colors"
              @click="reviewModalOpen = false"
            >
              <ClipboardCheck class="w-3.5 h-3.5 text-alpharo-600" />
              <span>Buka di Buat Order Baru →</span>
            </Link>
            <button
              type="button"
              class="px-5 py-2 text-xs font-semibold text-white bg-alpharo-600 rounded-lg shadow-xs flex items-center gap-1.5 transition-all hover:bg-alpharo-700"
              @click="executeAcceptOrder"
            >
              <CheckCircle2 class="w-4 h-4 text-emerald-400 stroke-[2.5]" />
              <span>Konfirmasi Terima & Proses Pesanan</span>
            </button>
          </div>
        </div>
      </div>
    </Modal>

    <!-- REJECT PROMPT MODAL -->
    <Modal
      v-model="rejectPromptOpen"
      title="Tolak / Minta Klarifikasi Pengajuan Client"
      subtitle="Berikan alasan spesifik kepada pihak sekolah/rekanan pemohon"
      size="md"
    >
      <div class="space-y-4 text-xs">
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-800">
          <p class="font-semibold">
            Pengajuan {{ reviewOrder?.orderNumber }} ({{ reviewOrder?.clientName }}) akan ditolak dan notifikasi resmi dikirimkan ke kontak PIC.
          </p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Alasan Penolakan / Catatan Klarifikasi <span class="text-rose-500">*</span>
          </label>
          <textarea
            v-model="rejectReason"
            rows="3"
            placeholder="Contoh: Spesifikasi barang custom tidak dapat dipenuhi sebelum target tanggal kirim / Stok semen tipe tersebut sedang dialokasikan..."
            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-rose-500"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button
            type="button"
            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg"
            @click="rejectPromptOpen = false"
          >
            Batal
          </button>
          <button
            type="button"
            class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-xs"
            @click="executeRejectOrder"
          >
            Kirim Penolakan
          </button>
        </div>
      </div>
    </Modal>

    <!-- CONFIRMATION DIALOG FOR DELETE DRAFT -->
    <ConfirmationDialog
      v-model="deleteConfirmOpen"
      title="Hapus Draft Order?"
      :message="`Apakah Anda yakin ingin menghapus order ${orderToDelete?.orderNumber}? Data draft yang dihapus tidak dapat dipulihkan.`"
      confirm-text="Hapus Order"
      cancel-text="Batal"
      variant="danger"
      @confirm="executeDeleteDraft"
      @cancel="deleteConfirmOpen = false"
    />
    <ConfirmationDialog
      v-model="submitConfirmOpen"
      :title="(Number(orderToSubmit?.grandTotal) || 0) > 5000000 ? 'Ajukan Pesanan ke Direktur?' : 'Ajukan Pesanan ke Finance?'"
      :message="(Number(orderToSubmit?.grandTotal) || 0) > 5000000
        ? `Total nilai pesanan adalah Rp ${Number(orderToSubmit?.grandTotal || 0).toLocaleString('id-ID')} (di atas limit Rp 5.000.000). Sesuai ketentuan, pesanan dengan nominal di atas Rp 5 Juta wajib diajukan ke Direktur untuk verifikasi & persetujuan terlebih dahulu sebelum invoice diterbitkan.`
        : `Total nilai pesanan adalah Rp ${Number(orderToSubmit?.grandTotal || 0).toLocaleString('id-ID')} (di bawah/sama dengan batas limit Rp 5.000.000). Pesanan tidak memerlukan persetujuan Direktur dan langsung dialihkan ke Finance dengan status Siap Invoice.`"
      :confirm-text="(Number(orderToSubmit?.grandTotal) || 0) > 5000000 ? 'Ajukan ke Direktur' : 'Ajukan ke Finance'"
      :variant="(Number(orderToSubmit?.grandTotal) || 0) > 5000000 ? 'warning' : 'success'"
      @confirm="executeSubmission"
      @cancel="submitConfirmOpen = false"
    />

    <!-- ORDER DETAIL & MONITORING DRAWER -->
    <Drawer
      v-model="detailDrawerOpen"
      :title="`Rincian & Monitoring Order: ${selectedOrder?.orderNumber || ''}`"
      :subtitle="`${selectedOrder?.clientName || ''} • ${selectedOrder?.projectName || ''}`"
      size="lg"
    >
      <div v-if="selectedOrder" class="space-y-6 text-xs">
        <!-- BANNER KHUSUS PENGAJUAN CLIENT -->
        <div
          v-if="selectedOrder.status === 'Diajukan Client'"
          class="p-4 bg-alpharo-50 rounded-xl border border-alpharo-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs"
        >
          <div>
            <span class="font-semibold text-alpharo-900 text-xs block">Pengajuan Baru Rekanan Client</span>
            <span class="text-[11px] text-alpharo-700 block mt-0.5">
              Pesanan ini diajukan mandiri dan menunggu verifikasi & konfirmasi penerimaan oleh Marketing.
            </span>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <Link
              :href="`/marketing/orders/create?fromOrderId=${selectedOrder.id}`"
              class="px-3.5 py-1.5 bg-alpharo-600 text-white text-xs font-semibold rounded-lg shadow-xs flex items-center gap-1.5 transition-all hover:bg-alpharo-700"
              @click="detailDrawerOpen = false"
            >
              <ClipboardCheck class="w-3.5 h-3.5 text-amber-300 stroke-[2.5]" />
              <span>Buat Order Baru →</span>
            </Link>
            <button
              type="button"
              class="px-3 py-1.5 bg-white border border-alpharo-200 text-alpharo-700 hover:bg-alpharo-50 text-xs font-semibold rounded-lg shadow-2xs transition-colors cursor-pointer"
              @click="openReviewModal(selectedOrder); detailDrawerOpen = false;"
            >
              Tinjau Detail
            </button>
          </div>
        </div>

        <!-- BANNER DITAHAN DIREKTUR -->
        <div v-if="selectedOrder.isHold" class="p-3.5 bg-rose-50 rounded-xl border border-rose-200 text-rose-800 shadow-2xs">
          <div class="font-semibold text-xs flex items-center gap-1.5 text-rose-900">
            <AlertCircle class="w-4 h-4 text-rose-600 shrink-0" />
            <span>⛔ PENGIRIMAN DITAHAN OLEH DIREKTUR</span>
          </div>
          <p class="mt-1 text-[11px] text-rose-700">Alasan: <strong>{{ selectedOrder.holdReason }}</strong></p>
          <span class="text-[11px] text-rose-500 block mt-0.5">Ditetapkan oleh {{ selectedOrder.holdBy }} pada {{ selectedOrder.holdDate }}</span>
        </div>

        <!-- RINGKASAN STATUS & NILAI TRANSAKSI -->
        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200 shadow-2xs">
          <div>
            <span class="text-slate-500 block text-[11px]">Status Verifikasi Order</span>
            <div class="mt-1"><StatusBadge :status="selectedOrder.status" /></div>
          </div>
          <div class="text-right">
            <span class="text-slate-500 block text-[11px]">Total Transaksi</span>
            <span class="text-base font-semibold font-mono text-slate-900 mt-1 block">
              Rp {{ Number(selectedOrder.grandTotal || selectedOrder.nilaiProject || 0).toLocaleString('id-ID') }}
            </span>
          </div>
        </div>

        <!-- SECTION 1: INFORMASI CLIENT & PROYEK -->
        <div>
          <h4 class="font-semibold uppercase text-slate-500 tracking-wider mb-2 flex items-center gap-1.5">
            <User class="w-3.5 h-3.5 text-slate-400" />
            Informasi Client & Proyek
          </h4>
          <div class="border border-slate-200 rounded-lg p-3 space-y-1.5 bg-white shadow-2xs">
            <div class="flex justify-between">
              <span class="text-slate-500">Nama Client:</span>
              <span class="font-semibold text-slate-800">{{ selectedOrder.clientName }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Nama Proyek:</span>
              <span class="font-semibold text-slate-800">{{ selectedOrder.projectName }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Tanggal Pengajuan:</span>
              <span class="font-mono text-slate-800">{{ selectedOrder.date }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Dibuat Oleh:</span>
              <span class="text-slate-800">{{ selectedOrder.submittedBy }}</span>
            </div>
            <div v-if="selectedOrder.marketingName" class="flex justify-between">
              <span class="text-slate-500">PIC Marketing:</span>
              <span class="font-semibold text-slate-800">{{ selectedOrder.marketingName }}</span>
            </div>
            <div v-if="selectedOrder.contactPerson || getClientPIC(selectedOrder.clientId)" class="flex justify-between">
              <span class="text-slate-500">PIC Pemesan:</span>
              <span class="text-slate-800 font-medium">
                {{ selectedOrder.contactPerson || getClientPIC(selectedOrder.clientId) }} ({{ selectedOrder.phone || getClientPhone(selectedOrder.clientId) || '-' }})
              </span>
            </div>
            <div v-if="selectedOrder.address || getClientAddress(selectedOrder.clientId)" class="flex justify-between">
              <span class="text-slate-500">Alamat Pengiriman:</span>
              <span class="text-slate-800 text-right max-w-xs">{{ selectedOrder.address || getClientAddress(selectedOrder.clientId) }}</span>
            </div>
          </div>
        </div>

        <!-- SECTION 2: RINCIAN ITEM BARANG / JASA -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <h4 class="font-semibold uppercase text-slate-500 tracking-wider flex items-center gap-1.5">
              <FileText class="w-3.5 h-3.5 text-slate-400" />
              Item Barang / Jasa Dipesan
            </h4>
            <span class="text-xs font-semibold text-slate-500">{{ (selectedOrder.items || []).length }} Jenis Barang</span>
          </div>
          <div class="border border-slate-200 rounded-lg overflow-hidden bg-white shadow-2xs">
            <table class="ui-table w-full text-left">
              <thead class="bg-slate-50 border-b border-slate-200 font-semibold text-slate-600">
                <tr>
                  <th class="p-2.5">Barang</th>
                  <th class="p-2.5 text-center">Qty</th>
                  <th class="p-2.5 text-right">Harga Satuan</th>
                  <th class="p-2.5 text-right">Total</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="it in (selectedOrder.items || [])" :key="it.sku || it.name">
                  <td class="p-2.5">
                    <span class="font-medium text-slate-800">{{ it.name }}</span>
                    <span class="block text-[11px] text-slate-400 font-mono">{{ it.sku || '-' }}</span>
                  </td>
                  <td class="p-2.5 text-center font-semibold text-slate-700">{{ it.qty }} {{ it.unit || 'Unit' }}</td>
                  <td class="p-2.5 text-right font-mono">Rp {{ (Number(it.price) || 0).toLocaleString('id-ID') }}</td>
                  <td class="p-2.5 text-right font-semibold font-mono text-slate-900">
                    Rp {{ (Number(it.total) || (Number(it.qty || 0) * Number(it.price || 0))).toLocaleString('id-ID') }}
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-slate-50 border-t border-slate-200">
                <tr>
                  <td colspan="3" class="p-2.5 text-right font-semibold text-slate-700">Grand Total:</td>
                  <td class="p-2.5 text-right font-semibold font-mono text-alpharo-600 text-sm">
                    Rp {{ Number(selectedOrder.grandTotal || selectedOrder.nilaiProject || 0).toLocaleString('id-ID') }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
          <div v-if="selectedOrder.stockReadiness" class="mt-3 space-y-1 text-xs text-slate-600">
            <p class="font-semibold text-slate-800">Alokasi stok: {{ selectedOrder.stockReadiness.status }}</p>
            <p v-for="item in selectedOrder.stockReadiness.items" :key="item.sku" class="break-words">
              <template v-if="item.catalogFound === false">{{ stockItemName(item, selectedOrder) }} — belum terdaftar di katalog gudang. Pilih ulang di Edit Order.</template>
              <template v-else>{{ stockItemName(item, selectedOrder) }} — teralokasi {{ item.allocated }}/{{ item.required }}, kurang {{ item.shortage }} {{ item.unit || '' }}.</template>
            </p>
          </div>
          <div v-if="selectedOrder.notes || selectedOrder.supportingDocument" class="bg-slate-50 p-3 rounded-lg border border-slate-200 space-y-2 mt-2 shadow-2xs">
            <div v-if="selectedOrder.notes">
              <span class="font-semibold text-slate-700 uppercase tracking-wider text-[11px] block">Catatan Pesanan:</span>
              <p class="text-slate-700 italic mt-0.5">"{{ selectedOrder.notes }}"</p>
            </div>
            <div v-if="selectedOrder.supportingDocument" class="pt-2 border-t border-slate-200 text-[11px] text-slate-600 flex items-center justify-between">
              <span>Lampiran Dokumen: <strong>{{ selectedOrder.supportingDocument }}</strong></span>
            </div>
          </div>
        </div>

        <!-- SECTION 3: MONITORING KELENGKAPAN DOKUMEN -->
        <div>
          <h4 class="font-semibold uppercase text-slate-500 tracking-wider mb-2 flex items-center gap-1.5">
            <FileText class="w-3.5 h-3.5 text-alpharo-600" />
            Monitoring Kelengkapan Dokumen (PO, Surat Jalan, BAST, Faktur)
          </h4>
          <DocumentChecklist :documents="selectedOrder.documents" />
        </div>

        <!-- SECTION 4: MONITORING TIMELINE & RIWAYAT PROSES (REAL-TIME) -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <h4 class="font-semibold uppercase text-slate-500 tracking-wider flex items-center gap-1.5 text-xs">
              <Clock class="w-3.5 h-3.5 text-amber-500" />
              Timeline & Riwayat Monitoring Proses
            </h4>
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              Real-Time Sync
            </span>
          </div>
          <Timeline :timeline="realtimeTimeline" />
        </div>
      </div>
    </Drawer>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute } from '../../navigation';
import AppLayout from '../../components/layout/AppLayout.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import Modal from '../../components/common/Modal.vue';
import Drawer from '../../components/common/Drawer.vue';
import ConfirmationDialog from '../../components/common/ConfirmationDialog.vue';
import DocumentChecklist from '../../components/common/DocumentChecklist.vue';
import Timeline from '../../components/common/Timeline.vue';
import { store, storeActions, orderSync } from '../../stores/mockData';
import { orderError } from '../../services/orderWorkflow';
import api from '../../services/api';
import { useToast } from '../../services/toast';

import {
  Search,
  Plus,
  Eye,
  Send,
  Trash2,
  Pencil,
  ClipboardCheck,
  Inbox,
  Filter,
  CheckCircle2,
  XCircle,
  FileText,
  User,
  Phone,
  Mail,
  MapPin,
  ExternalLink,
  Clock,
  AlertCircle,
  Zap,
} from 'lucide-vue-next';

const route = useRoute();
const toast = useToast();

const searchQuery = ref('');
const statusFilter = ref('');

const detailDrawerOpen = ref(false);
const deleteConfirmOpen = ref(false);

const selectedOrder = ref(null);
const orderToDelete = ref(null);
const submitConfirmOpen = ref(false);
const orderToSubmit = ref(null);

// Review & Acceptance Modal
const reviewModalOpen = ref(false);
const reviewOrder = ref(null);
const reviewForm = reactive({
  notes: '',
  actionType: 'verify', // 'verify' or 'draft'
  items: [],
});

const rejectPromptOpen = ref(false);
const rejectReason = ref('');

onMounted(async () => {
  if (route.query.filter === 'diajukan') {
    statusFilter.value = 'Diajukan Client';
  }
  try {
    const response = await api.get('/products?per_page=5000');
    if (Array.isArray(response.data.data)) store.products = response.data.data.map(product => ({
      ...product, price: product.sell_price ?? product.price, costPrice: product.hpp ?? product.costPrice,
    }));
  } catch { /* Submission always validates the authoritative inventory on the server. */ }
});

const totalOrdersCount = computed(() => {
  return store.orders.filter((o) => {
    if (o.orderNumber === 'ORD-2026-007' || o.orderNumber === 'ORD-2026-008') return false;
    if (o.date && String(o.date).startsWith('2026-10')) return false;
    return true;
  }).length;
});

const pendingClientOrders = computed(() => {
  return store.orders
    .filter((o) => {
      if (o.orderNumber === 'ORD-2026-007' || o.orderNumber === 'ORD-2026-008') return false;
      if (o.date && String(o.date).startsWith('2026-10')) return false;
      return o.status === 'Diajukan Client';
    })
    .slice()
    .sort((a, b) => {
      const dateA = a.date || '';
      const dateB = b.date || '';
      if (dateA !== dateB) return dateB.localeCompare(dateA);
      const numA = parseInt(String(a.orderNumber || '').replace(/\D/g, ''), 10) || Number(a.id) || 0;
      const numB = parseInt(String(b.orderNumber || '').replace(/\D/g, ''), 10) || Number(b.id) || 0;
      return numB - numA;
    });
});

const getStatusCount = (status) => {
  return store.orders.filter((o) => {
    if (o.orderNumber === 'ORD-2026-007' || o.orderNumber === 'ORD-2026-008') return false;
    if (o.date && String(o.date).startsWith('2026-10')) return false;
    return o.status === status;
  }).length;
};

const filteredOrders = computed(() => {
  return store.orders
    .filter((o) => {
      if (o.orderNumber === 'ORD-2026-007' || o.orderNumber === 'ORD-2026-008') return false;
      if (o.date && String(o.date).startsWith('2026-10')) return false;

      const q = searchQuery.value.toLowerCase();
      const matchSearch =
        !q ||
        o.orderNumber.toLowerCase().includes(q) ||
        o.clientName.toLowerCase().includes(q) ||
        o.projectName.toLowerCase().includes(q);
      const matchStatus = !statusFilter.value || o.status === statusFilter.value;
      return matchSearch && matchStatus;
    })
    .slice()
    .sort((a, b) => {
      // Urutan orderan terbaru paling atas
      const dateA = a.date || a.createdAt || '';
      const dateB = b.date || b.createdAt || '';
      if (dateA !== dateB) return dateB.localeCompare(dateA);
      const numA = parseInt(String(a.orderNumber || '').replace(/\D/g, ''), 10) || Number(a.id) || 0;
      const numB = parseInt(String(b.orderNumber || '').replace(/\D/g, ''), 10) || Number(b.id) || 0;
      return numB - numA;
    });
});

const getDocCount = (docs) => {
  if (!docs) return 0;
  let count = 0;
  if (docs.po?.status === 'Lengkap') count++;
  if (docs.suratJalan?.status === 'Lengkap') count++;
  if (docs.bast?.status === 'Lengkap') count++;
  if (docs.faktur?.status === 'Lengkap') count++;
  return count;
};

// Review Modal Calculations
const reviewSubtotal = computed(() => {
  if (!reviewForm.items) return 0;
  return reviewForm.items.reduce((sum, it) => sum + (Number(it.qty || 0) * Number(it.price || 0)), 0);
});

const reviewTax = computed(() => Math.round(reviewSubtotal.value * 0.11));
const reviewGrandTotal = computed(() => Math.round(reviewSubtotal.value * 1.11));

// Check stock status for each product
const stockItemName = (item, order) => item.name || order?.items?.find(line => line.sku === item.sku)?.name || 'Barang dalam pesanan';
const catalogIssues = order => (order.stockReadiness?.items || []).filter(item => item.catalogFound === false);

const getStockInfo = (sku, requestedQty) => {
  if (!sku) {
    return {
      status: 'custom',
      label: 'Item Non-Katalog (Khusus)',
      class: 'bg-alpharo-100 text-alpharo-700',
    };
  }
  const prod = store.products.find((p) => p.sku === sku);
  if (!prod) {
    return {
      status: 'unknown',
      label: 'Stok Tidak Terdata',
      class: 'bg-slate-100 text-slate-600',
    };
  }
  if ((prod.available_stock ?? 0) >= requestedQty) {
    return {
      status: 'available',
      label: `Stok Tersedia (${prod.available_stock ?? 0} ${prod.unit})`,
      class: 'bg-emerald-100 text-emerald-800',
    };
  }
  return {
    status: 'shortage',
    label: `Stok Menipis (${prod.available_stock ?? 0}/${requestedQty} ${prod.unit})`,
    class: 'bg-amber-100 text-amber-800',
  };
};

const openReviewModal = (order) => {
  reviewOrder.value = order;
  reviewForm.notes = 'Spesifikasi telah divalidasi dan sesuai dengan kebutuhan pengadaan rekanan.';
  reviewForm.actionType = 'verify';
  reviewForm.items = JSON.parse(JSON.stringify(order.items || []));
  reviewModalOpen.value = true;
};

const executeAcceptOrder = async () => {
  try {
  if (!reviewOrder.value) return;

  const isAbove5M = (Number(reviewOrder.value.grandTotal) || 0) > 5000000;
  const result = await storeActions.acceptClientOrder(reviewOrder.value.id, {
    notes: reviewForm.notes,
    actionType: reviewForm.actionType,
    items: reviewForm.items,
  });

  reviewModalOpen.value = false;
  toast.success(
    reviewForm.actionType === 'draft'
      ? `Pesanan ${reviewOrder.value.orderNumber} berhasil disimpan ke Draft Penawaran SPH.`
      : isAbove5M
        ? `Pesanan ${result.orderNumber} berhasil diajukan ke Direktur untuk proses verifikasi.`
        : `Pesanan ${result.orderNumber} berhasil diajukan ke Finance (status Siap Invoice).`
  );
  } catch (error) { toast.error(orderError(error)); }
};

const openRejectPrompt = () => {
  rejectReason.value = '';
  rejectPromptOpen.value = true;
};

const executeRejectOrder = () => {
  if (!reviewOrder.value) return;
  if (!rejectReason.value.trim()) {
    toast.error('Mohon isi alasan penolakan / klarifikasi pesanan.');
    return;
  }

  storeActions.rejectClientOrder(reviewOrder.value.id, rejectReason.value);
  rejectPromptOpen.value = false;
  reviewModalOpen.value = false;
  toast.info(`Pengajuan ${reviewOrder.value.orderNumber} ditolak dengan catatan.`);
};

const submitVerification = async (order) => {
  if (orderSync.busy) return;
  try {
    const currentOrder = store.orders.find(o => o.orderNumber === order.orderNumber) || order;
    if (['Menunggu Verifikasi', 'Siap Invoice', 'Disetujui', 'Diproses', 'Selesai'].includes(currentOrder.status)) {
      toast.info(`Pesanan ${currentOrder.orderNumber} saat ini berstatus ${currentOrder.status}.`);
      return;
    }
    const saved = currentOrder.serverManaged ? currentOrder : await storeActions.saveOrderDraft({ ...currentOrder, status: 'Draft' });
    orderToSubmit.value = await storeActions.checkOrderStock(saved);
    const missing = catalogIssues(orderToSubmit.value);
    if (missing.length) {
      const name = stockItemName(missing[0], orderToSubmit.value);
      throw new Error(`Barang belum terdaftar: ${name}${missing.length > 1 ? ` dan ${missing.length - 1} lainnya` : ''}. Pilih ulang di Edit Order.`);
    }
    if (orderToSubmit.value.stockReadiness?.status !== 'Siap Diajukan') {
      const shortages = orderToSubmit.value.stockReadiness?.items.filter(item => item.shortage > 0)
        .map(item => `${stockItemName(item, orderToSubmit.value)} (${item.shortage}${item.unit ? ` ${item.unit}` : ''})`).join(', ');
      throw new Error(`Stok kurang: ${shortages}.`);
    }
    submitConfirmOpen.value = true;
  } catch (error) { toast.error(orderError(error)); }
};

const executeSubmission = async () => {
  if (orderSync.busy || !orderToSubmit.value) return;
  try {
    const isAbove5M = (Number(orderToSubmit.value.grandTotal) || 0) > 5000000;
    const result = await storeActions.submitOrderForVerification(orderToSubmit.value.id);
    submitConfirmOpen.value = false;
    if (isAbove5M) {
      toast.success(`Pesanan ${result.orderNumber} berhasil diajukan ke Direktur (Menunggu Verifikasi).`);
    } else {
      toast.success(`Pesanan ${result.orderNumber} berhasil diajukan ke Finance (Siap Invoice).`);
    }
  } catch (error) { toast.error(orderError(error)); }
};

const confirmDelete = (order) => {
  orderToDelete.value = order;
  deleteConfirmOpen.value = true;
};

const executeDeleteDraft = async () => {
  try {
  if (orderToDelete.value) {
    await storeActions.deleteOrder(orderToDelete.value.id);
    toast.info(`Draft order ${orderToDelete.value.orderNumber} telah dihapus.`);
    deleteConfirmOpen.value = false;
  }
  } catch (error) { toast.error(orderError(error)); }
};

// Timeline Real-Time Generator: STRICT SINGLE ACTIVE STEP (Hanya Tepat 1 Tahap Berjalan)
const realtimeTimeline = computed(() => {
  const o = selectedOrder.value;
  if (!o) return [];

  if (o.serverManaged) {
    const stages = { Draft: 'Pemeriksaan stok dan pengajuan Marketing', 'Menunggu Verifikasi': 'Menunggu persetujuan Direktur', 'Siap Invoice': 'Pemeriksaan final dan penerbitan invoice oleh Finance', 'Menunggu Pembayaran': 'Menunggu pembayaran Customer' };
    return [...(o.timeline || []), ...(stages[o.status] ? [{ stage: stages[o.status], user: '-', timestamp: '-', status: 'in_progress', notes: o.status === 'Menunggu Verifikasi' ? 'Invoice belum dapat diterbitkan sebelum Direktur menyetujui.' : 'Melanjutkan proses sesuai hak akses.' }] : [])];
  }
  const status = o.status;
  const isHold = o.isHold;
  const isDiajukan = status === 'Diajukan Client';

  // Helper mencari catatan asli jika ada di history o.timeline
  const findLogged = (terms) => {
    if (!o.timeline || !Array.isArray(o.timeline)) return null;
    return o.timeline.find((t) => terms.some((term) => (t.stage || '').toLowerCase().includes(term.toLowerCase())));
  };

  const logCreated = findLogged(['dibuat', 'pengajuan']);
  const logVerif = findLogged(['verifikasi', 'review']);
  const logApproved = findLogged(['disetujui']);
  const logRejected = findLogged(['ditolak']);
  const logDoc = findLogged(['dokumen', 'po', 'surat jalan']);
  const logWarehouse = findLogged(['gudang', 'dipenuhi', 'alokasi']);
  const logShipment = findLogged(['kirim', 'ekspedisi']);
  const logBast = findLogged(['bast', 'diterima', 'serah terima']);
  const logInvoice = findLogged(['invoice']);
  const logPayment = findLogged(['pembayaran', 'lunas']);
  const logCompleted = findLogged(['selesai']);

  // Logika Penentuan EXACTLY ONE ACTIVE STEP:
  // Step 0: Pembuatan / Pengajuan Order
  // Step 1: Verifikasi & Approval Direktur (atau Validasi Marketing untuk Diajukan Client)
  // Step 2: Penerbitan Dokumen PO & Administrasi
  // Step 3: Alokasi Stok & Pemenuhan Gudang
  // Step 4: Pengiriman & Serah Terima (BAST)
  // Step 5: Penagihan Invoice & Pelunasan
  // Step 6: Pesanan Selesai Tuntas
  // Step 7: All Completed (status 'Selesai')
  let activeStep = 0;

  const isPoReady = o.documents?.po && o.documents.po.status === 'Lengkap';
  const isWarehouseDone = o.fulfillmentStatus === 'Selesai';
  const isBastDone = (o.documents?.bast && o.documents.bast.status === 'Lengkap') || ['Menunggu Pembayaran', 'Selesai'].includes(status);

  if (status === 'Draft') {
    activeStep = 0;
  } else if (status === 'Diajukan Client') {
    activeStep = 1; // Validasi Marketing & Pengajuan ke Direktur
  } else if (status === 'Menunggu Verifikasi') {
    activeStep = 1; // Sedang di meja Direktur
  } else if (status === 'Ditolak') {
    activeStep = 1; // Berhenti di penolakan Direktur
  } else if (status === 'Disetujui') {
    if (!isPoReady) {
      activeStep = 2; // Sedang penerbitan Dokumen PO & Administrasi
    } else {
      activeStep = 3; // PO sudah ada, sedang Alokasi Gudang
    }
  } else if (status === 'Diproses') {
    if (!isWarehouseDone && !isBastDone) {
      activeStep = 3; // Masih alokasi gudang
    } else if (!isBastDone) {
      activeStep = 4; // Gudang beres, sedang Pengiriman & Menunggu BAST
    } else {
      activeStep = 5; // BAST selesai, sedang Invoice
    }
  } else if (status === 'Menunggu Pembayaran') {
    activeStep = 5; // Sedang penagihan Invoice & menunggu pelunasan
  } else if (status === 'Selesai') {
    activeStep = 7; // Seluruh tahap 0 s/d 6 tuntas (completed)
  }

  // Fungsi penentu status tahap: Menjamin HANYA TEPAT SATU yang 'in_progress'
  const getStepStatus = (stepIdx) => {
    if (status === 'Ditolak' && stepIdx === 1) return 'rejected';
    if (stepIdx < activeStep) return 'completed';
    if (stepIdx === activeStep) {
      if (isHold && stepIdx >= 2) return 'pending'; // Jika di-hold, tahap berikutnya pending
      return 'in_progress';
    }
    return 'pending';
  };

  const list = [];

  // ----------------------------------------------------
  // TAHAP 0: PENGAJUAN / PEMBUATAN ORDER
  // ----------------------------------------------------
  if (isDiajukan) {
    list.push({
      stage: 'Pengajuan Mandiri oleh Rekanan Client',
      user: o.contactPerson ? `${o.contactPerson} (${o.clientName})` : o.clientName,
      timestamp: logCreated?.timestamp || (o.date ? `${o.date} 09:30` : 'Hari ini'),
      status: 'completed',
      notes: o.notes || 'Daftar kebutuhan sarana diajukan langsung melalui Portal Rekanan Sekolah.',
    });
    // Tahap 1 untuk Diajukan Client adalah Review Marketing
    list.push({
      stage: 'Review & Validasi Tim Marketing',
      user: 'Tim Marketing Alpharo',
      timestamp: activeStep === 1 ? 'Sedang Berjalan' : (logVerif?.timestamp || `${o.date} 10:00`),
      status: getStepStatus(1),
      notes: activeStep === 1
        ? 'Marketing sedang memverifikasi spesifikasi barang dan ketersediaan stok fisik gudang sebelum diajukan untuk persetujuan.'
        : 'Spesifikasi barang dan estimasi kebutuhan telah divalidasi oleh Marketing.',
    });
  } else {
    list.push({
      stage: 'Order Dibuat oleh Marketing',
      user: o.submittedBy || 'Budi Hartono (Marketing)',
      timestamp: logCreated?.timestamp || (o.date ? `${o.date} 09:00` : 'Hari ini'),
      status: getStepStatus(0),
      notes: activeStep === 0
        ? 'Rincian spesifikasi barang sedang disusun oleh marketing dalam status Draft.'
        : (logCreated?.notes || 'Pesanan pengadaan barang/jasa berhasil diregistrasi di sistem.'),
    });

    // ----------------------------------------------------
    // TAHAP 1: VERIFIKASI & APPROVAL DIREKTUR (Untuk Order Non-Client)
    // ----------------------------------------------------
    if (status === 'Ditolak') {
      list.push({
        stage: 'Verifikasi Direktur (Ditolak)',
        user: o.approvedBy || logRejected?.user || 'Ir. Alpharo Wijaya (Direktur)',
        timestamp: o.approvalDate || logRejected?.timestamp || o.date,
        status: 'rejected',
        notes: o.approvalNotes || logRejected?.notes || 'Pengajuan pesanan ditolak oleh Direktur. Proses pengadaan dihentikan.',
      });
      return list; // Berhenti di tahap penolakan
    } else {
      list.push({
        stage: activeStep < 1 ? 'Verifikasi & Approval Direktur' : 'Verifikasi Direktur (Disetujui)',
        user: 'Ir. Alpharo Wijaya (Direktur)',
        timestamp: activeStep === 1 ? 'Sedang Berjalan' : (o.approvalDate || logApproved?.timestamp || `${o.date} 10:15`),
        status: getStepStatus(1),
        notes: activeStep < 1
          ? 'Menunggu pengajuan resmi masuk ke meja Direktur.'
          : activeStep === 1
            ? (logVerif?.notes || 'Berkas pengadaan berada di meja Direktur untuk penelaahan margin dan persetujuan pengadaan.')
            : (o.approvalNotes || logApproved?.notes || 'Pengajuan disetujui penuh oleh Direktur untuk diproses ke administrasi dan gudang.'),
      });
    }
  }

  // ----------------------------------------------------
  // BANNER / NODE HOLD OLEH DIREKTUR (JIKA ADA)
  // ----------------------------------------------------
  if (isHold) {
    list.push({
      stage: '⛔ Pengiriman Ditahan Direktur (Hold)',
      user: o.holdBy || 'Ir. Alpharo Wijaya (Direktur)',
      timestamp: o.holdDate || 'Hari ini',
      status: 'rejected',
      notes: `Pengiriman dan proses dihentikan sementara. Alasan: ${o.holdReason || 'Penyelesaian kewajiban administrasi/pembayaran.'}`,
    });
  }

  // ----------------------------------------------------
  // TAHAP 2: PENERBITAN DOKUMEN PO & ADMINISTRASI
  // ----------------------------------------------------
  const poDoc = o.documents?.po;
  list.push({
    stage: 'Penerbitan Dokumen PO & Surat Jalan',
    user: logDoc?.user || 'Siti Rahmawati (Administrasi)',
    timestamp: isPoReady ? (poDoc?.date || logDoc?.timestamp || `${o.date} 11:30`) : (activeStep === 2 ? 'Sedang Berjalan' : '-'),
    status: getStepStatus(2),
    notes: getStepStatus(2) === 'completed'
      ? (isPoReady ? `PO resmi (${poDoc?.number || 'PO'}) dan Surat Jalan telah diterbitkan.` : 'Dokumen administrasi PO telah disiapkan.')
      : getStepStatus(2) === 'in_progress'
        ? 'Tim Administrasi sedang memproses berkas PO resmi dan kelengkapan surat jalan.'
        : 'Akan diproses setelah order mendapatkan approval dari Direktur.',
  });

  // ----------------------------------------------------
  // TAHAP 3: ALOKASI GUDANG (WAREHOUSE)
  // ----------------------------------------------------
  list.push({
    stage: 'Alokasi Stok & Pemenuhan Gudang',
    user: logWarehouse?.user || 'Agus Santoso (Warehouse)',
    timestamp: getStepStatus(3) === 'completed' ? (logWarehouse?.timestamp || `${o.date} 13:45`) : (activeStep === 3 ? 'Sedang Berjalan' : '-'),
    status: getStepStatus(3),
    notes: getStepStatus(3) === 'completed'
      ? 'Seluruh item barang telah dialokasikan, diuji kualitasnya (QC), dan siap didistribusikan.'
      : getStepStatus(3) === 'in_progress'
        ? 'Tim warehouse sedang mempersiapkan fisik barang, pengujian mutu (QC), dan pengepakan di loading dock.'
        : 'Menunggu penerbitan nomor PO dan instruksi alokasi stok dari Administrasi.',
  });

  // ----------------------------------------------------
  // TAHAP 4: PENGIRIMAN & BAST
  // ----------------------------------------------------
  const bastDoc = o.documents?.bast;
  list.push({
    stage: 'Pengiriman & Berita Acara Serah Terima (BAST)',
    user: getStepStatus(4) === 'completed' ? (logBast?.user || 'Client & Tim Logistik') : (activeStep === 4 ? 'Tim Ekspedisi / Driver' : 'Tim Logistik & Client'),
    timestamp: getStepStatus(4) === 'completed' ? (bastDoc?.date || logBast?.timestamp || `${o.date} 16:00`) : (activeStep === 4 ? (logShipment?.timestamp || 'Dalam Perjalanan') : '-'),
    status: getStepStatus(4),
    notes: getStepStatus(4) === 'completed'
      ? `Barang telah diterima di lokasi dan dokumen Berita Acara Serah Terima (${bastDoc?.number || 'BAST'}) telah ditandatangani sah.`
      : getStepStatus(4) === 'in_progress'
        ? (isHold ? 'Pengiriman ditahan sementara oleh Direktur.' : 'Armada pengiriman sedang dalam perjalanan menuju lokasi instansi client.')
        : 'Menunggu penyelesaian pengepakan barang dari bagian gudang.',
  });

  // ----------------------------------------------------
  // TAHAP 5: PENAGIHAN INVOICE & PEMBAYARAN
  // ----------------------------------------------------
  const inv = (store.invoices || []).find((i) => i.orderNumber === o.orderNumber);
  list.push({
    stage: status === 'Selesai' ? 'Penagihan Invoice & Pelunasan' : 'Penagihan Invoice & Pembayaran',
    user: logPayment?.user || 'Dewi Lestari (Finance)',
    timestamp: getStepStatus(5) === 'completed' ? (logPayment?.timestamp || `${o.date} 17:00`) : (activeStep === 5 ? (logInvoice?.timestamp || 'Menunggu Pembayaran') : '-'),
    status: getStepStatus(5),
    notes: getStepStatus(5) === 'completed'
      ? 'Seluruh tagihan invoice telah lunas 100% dan bukti transfer kas telah diverifikasi.'
      : getStepStatus(5) === 'in_progress'
        ? (inv ? `Invoice ${inv.invoiceNumber} telah diterbitkan. Jatuh tempo: ${inv.dueDate}. Sisa tagihan: Rp ${Number(inv.remainingAmount || 0).toLocaleString('id-ID')}.` : 'Invoice tagihan resmi telah diterbitkan dan menunggu proses pembayaran client.')
        : 'Invoice akan diterbitkan setelah konfirmasi serah terima fisik barang (BAST) terlaksana.',
  });

  // ----------------------------------------------------
  // TAHAP 6: PESANAN SELESAI TUNTAS
  // ----------------------------------------------------
  list.push({
    stage: 'Pesanan Selesai Tuntas',
    user: 'Sistem & Direktur',
    timestamp: status === 'Selesai' ? (logCompleted?.timestamp || `${o.date} 17:30`) : '-',
    status: getStepStatus(6),
    notes: status === 'Selesai'
      ? 'Order resmi selesai secara paripurna: barang telah diterima dengan baik dan pembayaran lunas.'
      : 'Order akan ditutup setelah seluruh serah terima barang dan pembayaran tuntas 100%.',
  });

  return list;
});

const viewDetail = (order) => {
  selectedOrder.value = order;
  detailDrawerOpen.value = true;
};

// Client contact helpers for drawer display
const getClientPIC = (clientId) => {
  const c = (store.clients || []).find((x) => x.id === clientId);
  return c ? c.contactPerson : '-';
};

const getClientPhone = (clientId) => {
  const c = (store.clients || []).find((x) => x.id === clientId);
  return c ? c.phone : '';
};

const getClientAddress = (clientId) => {
  const c = (store.clients || []).find((x) => x.id === clientId);
  return c ? c.address : '-';
};
</script>
