<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-semibold text-slate-900 tracking-tight">Pengadaan & Penerimaan Logistik</h1>
          <p class="text-xs text-slate-500 mt-1">
            Pusat operasional siklus pengadaan gudang: Pengajuan kebutuhan material ke Finance, pelacakan & penerimaan fisik barang supplier, serta alokasi pemenuhan pesanan proyek.
          </p>
        </div>

        <!-- Header Quick Action -->
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 active:bg-alpharo-800 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors cursor-pointer"
            @click="handleHeaderCreateRequest"
          >
            <Plus class="w-4 h-4 stroke-[2.5]" />
            <span>Buat Pengajuan Kebutuhan</span>
          </button>
        </div>
      </div>
    </template>

    <!-- NAVIGATION TABS BAR (OPSI 1: TERPADU SIKLUS PENGADAAN GUDANG) -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 mb-6 border-b border-slate-200/80">
      <button
        v-for="t in procurementTabs"
        :key="t.key"
        type="button"
        :class="[
          activeTab === t.key
            ? 'bg-alpharo-600 text-white font-semibold shadow-xs'
            : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 font-medium',
          'px-4 py-2.5 rounded-xl text-xs whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer'
        ]"
        @click="switchTab(t.key)"
      >
        <component :is="t.icon" class="w-4 h-4" />
        <span>{{ t.label }}</span>
        <span
          v-if="t.badge"
          :class="[
            activeTab === t.key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700',
            'px-2 py-0.5 rounded-full text-[11px] font-semibold font-mono'
          ]"
        >
          {{ t.badge }}
        </span>
      </button>
    </div>

    <!-- ========================================================================================= -->
    <!-- TAB 1: PENGAJUAN KEBUTUHAN BARANG (MATERIAL REQUEST KE FINANCE - TAHAP 3 & 4)             -->
    <!-- ========================================================================================= -->
    <div v-if="activeTab === 'requests'" class="space-y-5">
      <!-- INFO BANNER: ALUR BPMN WAREHOUSE -> FINANCE -->
      <div class="bg-linear-to-r from-emerald-50 via-teal-50 to-sky-50 border border-emerald-200/80 rounded-xl p-4 text-xs text-slate-700 flex flex-col md:flex-row items-start md:items-center justify-between gap-3 shadow-2xs">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-2xs">
            <ArrowRightLeft class="w-4 h-4" />
          </div>
          <div>
            <span class="font-semibold text-emerald-950 block">Alur Proses Pengadaan: Gudang &rarr; Finance (PROBIS TO-BE)</span>
            <span class="text-slate-600">
              Pengajuan kebutuhan yang dibuat di sini akan membuka akses pembelian pada <strong class="text-emerald-800">Finance</strong> untuk memilih supplier rekanan & menerbitkan Purchase Order (PO).
            </span>
          </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <span class="px-2.5 py-1 bg-amber-100 text-amber-800 font-semibold rounded-full text-[11px] border border-amber-200">
            {{ pendingRequestsCount }} Menunggu Finance
          </span>
          <span class="px-2.5 py-1 bg-sky-100 text-sky-800 font-semibold rounded-full text-[11px] border border-sky-200">
            {{ inProgressRequestsCount }} Diproses Pembelian
          </span>
        </div>
      </div>

      <!-- FILTER & SEARCH CARD -->
      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="h-px bg-slate-200 w-full"></div>
        <div class="p-4 sm:p-5">
          <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <!-- Search Bar -->
            <div class="md:col-span-5 relative">
              <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                v-model="requestsSearchQuery"
                type="text"
                placeholder="Cari kode pengajuan, proyek, atau material..."
                class="w-full pl-9 pr-8 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-alpharo-400 bg-white text-slate-800 placeholder-slate-400"
              />
              <button
                v-if="requestsSearchQuery"
                type="button"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                @click="requestsSearchQuery = ''"
              >
                <X class="w-3.5 h-3.5" />
              </button>
            </div>

            <!-- Status Filter -->
            <div class="md:col-span-3">
              <select
                v-model="requestsStatusFilter"
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-alpharo-400 bg-white text-slate-800 cursor-pointer"
              >
                <option value="">Semua Status Pengajuan</option>
                <option value="MENUNGGU_FINANCE">Menunggu Finance</option>
                <option value="DIPROSES_PEMBELIAN">Diproses Pembelian</option>
                <option value="SELESAI">Selesai</option>
              </select>
            </div>

            <!-- Project Filter -->
            <div class="md:col-span-3">
              <select
                v-model="requestsProjectFilter"
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-alpharo-400 bg-white text-slate-800 cursor-pointer"
              >
                <option value="">Semua Proyek Pengadaan</option>
                <option v-for="proj in availableProjects" :key="proj.id" :value="proj.name">
                  {{ proj.name }}
                </option>
              </select>
            </div>

            <!-- Reset Filter -->
            <div class="md:col-span-1 flex justify-end">
              <button
                type="button"
                class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-medium border border-slate-300 shadow-2xs transition-colors cursor-pointer"
                title="Reset Filter"
                @click="resetRequestsFilters"
              >
                <RotateCcw class="w-3.5 h-3.5 text-slate-500" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Responsive warehouse requests: full project descriptions and purposes -->
      <section aria-label="Daftar pengajuan kebutuhan barang" class="ui-panel min-w-0 rounded-xl border border-slate-200/90 bg-white shadow-2xs">
        <div class="flex min-w-0 flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-4 text-xs sm:px-5">
          <h2 class="min-w-0 text-[11px] font-semibold uppercase tracking-wider text-slate-800">Daftar Pengajuan Kebutuhan Barang Gudang</h2>
          <div class="flex min-w-0 flex-wrap items-center gap-3">
            <span class="font-mono text-slate-500">{{ filteredWarehouseRequests.length }} Pengajuan Ditemukan</span>
          </div>
        </div>
        <div v-if="filteredWarehouseRequests.length === 0" class="flex flex-col items-center gap-2 px-4 py-12 text-center text-xs text-slate-500">
          <ClipboardList class="h-8 w-8 text-slate-300" />
          <p class="font-semibold text-slate-700">Tidak ada data pengajuan yang sesuai.</p>
          <p>Klik "Buat Pengajuan Baru" untuk mengajukan kebutuhan ke Finance.</p>
        </div>
        <ol v-else class="min-w-0 divide-y divide-slate-200">
          <li v-for="req in filteredWarehouseRequests" :key="req.id" class="min-w-0">
            <article :aria-label="'Pengajuan ' + (req.requestCode || req.request_code)" class="min-w-0 px-4 py-5 sm:px-5">
              <div class="grid min-w-0 gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                <div class="min-w-0">
                  <h3 class="wrap-anywhere font-mono text-xs font-semibold text-slate-900">{{ req.requestCode || req.request_code }}</h3>
                  <div class="mt-1 flex min-w-0 flex-wrap gap-x-4 gap-y-1 text-[11px] leading-relaxed text-slate-500">
                    <span class="min-w-0 wrap-anywhere">Oleh: {{ req.requestedBy || req.requested_by }}</span>
                    <span class="min-w-0 wrap-anywhere font-mono">{{ req.date || req.request_date }}</span>
                  </div>
                </div>
                <div class="flex min-w-0 max-w-full flex-wrap items-center gap-3 sm:justify-end">
                  <StatusBadge plain :status="['MENUNGGU_FINANCE', 'Draft', 'Menunggu Verifikasi'].includes(req.status) ? 'Menunggu Finance' : ['DIPROSES_PEMBELIAN', 'Diproses'].includes(req.status) ? 'Diproses Pembelian' : 'Selesai'" class="min-w-0 max-w-full wrap-anywhere" style="white-space: normal" />
                  <button type="button" class="shrink-0 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50" @click="openDetailRequest(req)">Detail</button>
                </div>
              </div>
              <div class="mt-4 grid min-w-0 gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)] md:gap-6">
                <div class="min-w-0">
                  <p class="mb-1.5 text-[11px] text-slate-500">Proyek &amp; Keperluan</p>
                  <p class="wrap-anywhere text-xs font-semibold leading-relaxed text-slate-900">{{ req.projectName || req.project_name || 'Umum / Restock' }}</p>
                  <p class="mt-2 whitespace-pre-line wrap-anywhere text-xs leading-relaxed text-slate-600">{{ req.purpose }}</p>
                </div>
                <div class="min-w-0">
                  <p class="mb-1.5 text-[11px] text-slate-500">Item Material yang Diajukan</p>
                  <PurchaseItemList :items="req.items" />
                </div>
              </div>
              <div class="mt-4 flex min-w-0 flex-wrap items-end justify-between gap-3 border-t border-slate-100 pt-3">
                <div class="min-w-0"><p class="text-[11px] text-slate-500">Estimasi Biaya</p><p class="mt-1 wrap-anywhere font-mono text-sm font-semibold text-slate-900">Rp {{ Number(req.totalEstCost || req.total_est_cost || 0).toLocaleString('id-ID') }}</p></div>
                <p v-if="req.purchase_code || req.purchaseCode" class="min-w-0 wrap-anywhere font-mono text-[11px] text-slate-500">PO: {{ req.purchase_code || req.purchaseCode }}</p>
              </div>
            </article>
          </li>
        </ol>
      </section>
    </div>

    <!-- ========================================================================================= -->
    <!-- TAB 2: PENERIMAAN FISIK & RESI SUPPLIER (INBOUND LOGISTICS - TAHAP 4 & 5)                 -->
    <!-- ========================================================================================= -->
    <div v-if="activeTab === 'inbound'" class="space-y-6">
      <!-- FITUR FORM LACAK NO. RESI & SURAT JALAN SUPPLIER -->
      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs p-4 sm:p-5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div class="max-w-xl">
            <div class="flex items-center gap-2 mb-1">
              <Truck class="w-5 h-5 text-alpharo-600" />
              <h2 class="text-sm sm:text-base font-semibold text-slate-900 tracking-tight">
                Form Lacak No. Resi & Pengiriman Supplier
              </h2>
            </div>
            <p class="text-xs text-slate-500">
              Ketik nomor resi pengiriman, plat nomor armada, atau nomor surat jalan vendor untuk memantau pergerakan kurir secara real-time.
            </p>
          </div>

          <!-- Form Input Lacak Resi -->
          <form @submit.prevent="handleTrackingSearch" class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto shrink-0">
            <div class="relative w-full sm:w-80">
              <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                v-model="trackingSearchQuery"
                type="text"
                placeholder="Masukkan No. Resi (TRK-...) / No. SJ..."
                class="w-full pl-9 pr-8 py-2 text-xs bg-slate-50 border border-slate-300 rounded-lg text-slate-800 placeholder-slate-400 focus:outline-hidden focus:ring-1 focus:ring-alpharo-500 focus:bg-white font-mono transition-colors"
              />
              <button
                v-if="trackingSearchQuery"
                type="button"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                @click="trackingSearchQuery = ''"
              >
                <X class="w-3.5 h-3.5" />
              </button>
            </div>
            <button
              type="submit"
              class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 active:bg-alpharo-800 text-white font-semibold rounded-lg text-xs shadow-2xs transition-colors flex items-center justify-center gap-1.5 cursor-pointer whitespace-nowrap"
            >
              <Truck class="w-4 h-4" />
              <span>Lacak Pengiriman</span>
            </button>
          </form>
        </div>

        <!-- Quick Chips: Resi Aktif dalam Perjalanan -->
        <div v-if="activeIncomingTrackingChips.length > 0" class="mt-3.5 pt-3 border-t border-slate-100 flex items-center gap-2 flex-wrap text-xs">
          <span class="text-[11px] text-slate-500 font-medium">Resi Aktif Menuju Gudang:</span>
          <button
            v-for="shp in activeIncomingTrackingChips"
            :key="shp.purchase_code"
            type="button"
            class="px-2.5 py-1 rounded-md bg-slate-50 hover:bg-alpharo-50 border border-slate-200 hover:border-alpharo-300 text-[11px] font-mono text-slate-700 hover:text-alpharo-900 flex items-center gap-1.5 transition-colors cursor-pointer group shadow-2xs"
            :title="`Klik untuk melacak ${shp.delivery_info?.courier} (${shp.delivery_info?.tracking_number})`"
            @click="openTrackingModal(shp)"
          >
            <Truck class="w-3 h-3 text-alpharo-600 group-hover:scale-105 transition-transform" />
            <span class="font-semibold text-alpharo-950">{{ shp.delivery_info?.tracking_number }}</span>
            <span class="text-slate-400 text-[11px]">({{ shp.default_supplier }})</span>
          </button>
        </div>
      </div>

      <!-- SEKSI 1: PENERIMAAN FISIK BARANG SUPPLIER (PROBIS TO-BE TAHAP 4 & 5) -->
      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="h-px bg-slate-200 w-full"></div>
        <div class="p-4 bg-amber-50/60 border-b border-amber-200/80 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Truck class="w-4 h-4 text-amber-600" />
            <h3 class="font-semibold text-slate-900 text-xs sm:text-sm">
              Penerimaan Fisik Barang dari Supplier (Surat Jalan Masuk)
            </h3>
          </div>
          <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-900 border border-amber-200">
            {{ incomingSupplierShipments.length }} Kiriman Vendor Menuju Gudang
          </span>
        </div>

        <div v-if="incomingSupplierShipments.length === 0" class="p-6 text-center text-slate-500 text-xs">
          <p>Tidak ada pengiriman supplier yang sedang dalam perjalanan menuju gudang saat ini.</p>
        </div>

        <div v-else class="divide-y divide-slate-100">
          <div
            v-for="p in incomingSupplierShipments"
            :key="p.purchase_code"
            class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 hover:bg-slate-50/60 transition-colors"
          >
            <div>
              <div class="flex items-center gap-2 mb-1 flex-wrap">
                <span class="font-mono font-semibold text-slate-900 text-xs">{{ p.delivery_info?.surat_jalan || 'SJ-SUP' }}</span>
                <span class="px-2 py-0.2 rounded text-[11px] font-semibold bg-alpharo-100 text-alpharo-800">
                  PO: {{ p.purchase_code }}
                </span>
                <span class="text-xs font-semibold text-slate-700">&bull; {{ p.default_supplier }}</span>


                <!-- Status Badge Skenario 1: Retur & Penggantian -->
                <span
                  v-if="p.supplier_status === 'Barang Pengganti Dikirim'"
                  class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-alpharo-100 text-alpharo-800 border border-alpharo-200 inline-flex items-center gap-1"
                >
                  <RefreshCw class="w-3 h-3 text-alpharo-600 animate-spin" />
                  <span>Barang Pengganti Sedang Dikirim Supplier</span>
                </span>
                <span
                  v-else-if="p.supplier_status === 'Ketidaksesuaian Fisik (Menunggu Retur)'"
                  class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-900 border border-amber-200 inline-flex items-center gap-1"
                >
                  <AlertTriangle class="w-3 h-3 text-amber-600" />
                  <span>Retur Dilaporkan (Menunggu Supplier Kirim Pengganti)</span>
                </span>
              </div>

              <!-- Preview Catatan Retur Jika Ada -->
              <div
                v-if="p.discrepancy_reason && p.supplier_status !== 'Diterima Gudang (Lengkap & Sesuai)'"
                class="mb-2 p-2.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 space-y-1.5 shadow-2xs"
              >
                <div class="flex items-center justify-between font-semibold text-[11px] text-slate-900">
                  <span class="flex items-center gap-1.5 text-rose-600">
                    <AlertTriangle class="w-3.5 h-3.5 text-rose-600 shrink-0" />
                    <span>Laporan Kerusakan & Retur Barang</span>
                  </span>
                  <span class="text-[10px] text-slate-500 font-mono">{{ p.discrepancy_reported_at || 'Hari ini' }}</span>
                </div>
                <p class="text-slate-600 italic">"{{ p.discrepancy_reason }}"</p>

                <!-- Rincian Barang yang Diretur -->
                <div v-if="(p.discrepancy_items && p.discrepancy_items.length > 0) || (p.discrepancy_info?.items && p.discrepancy_info.items.length > 0)" class="pt-1.5 border-t border-slate-200">
                  <span class="text-[10px] font-semibold text-slate-700 block mb-1">Barang yang Diretur:</span>
                  <div class="flex flex-wrap gap-1">
                    <span
                      v-for="(di, diIdx) in (p.discrepancy_items || p.discrepancy_info?.items || [])"
                      :key="diIdx"
                      class="px-2 py-0.5 rounded bg-slate-50 border border-slate-200 text-slate-800 font-mono text-[10px] font-semibold"
                    >
                      {{ di.barang }} ({{ di.damaged_qty }} {{ di.unit }}){{ di.notes ? ` - ${di.notes}` : '' }}
                    </span>
                  </div>
                </div>

                <div v-if="p.discrepancy_media && p.discrepancy_media.length > 0" class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                  <Camera class="w-3 h-3 text-slate-400" />
                  <span>{{ p.discrepancy_media.length }} Bukti Foto/Video telah dikirim ke Supplier</span>
                </div>
              </div>

              <p class="text-xs text-slate-600">
                Proyek: <strong>{{ p.project_name }}</strong>
              </p>

              <!-- Delivery details -->
              <div class="text-[11px] text-slate-500 mt-1 flex flex-wrap items-center gap-3">
                <span v-if="p.delivery_info?.courier">Kurir: <strong>{{ p.delivery_info.courier }}</strong></span>
                <span v-if="p.delivery_info?.tracking_number" class="font-mono text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded">
                  Resi: {{ p.delivery_info.tracking_number }}
                </span>
                <span v-if="p.delivery_info?.dispatch_date">Kirim: {{ p.delivery_info.dispatch_date }}</span>
                <span v-if="p.delivery_info?.est_arrival" class="text-alpharo-600 font-semibold">
                  Est. Tiba: {{ p.delivery_info.est_arrival }}
                </span>
              </div>

              <!-- Items list -->
              <PurchaseItemList :items="p.items" class="mt-3" />
            </div>

            <!-- Actions: Check Resi, Retur, & ACC Masuk Gudang -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-1.5 shrink-0 pt-2 md:pt-0">
              <!-- SEBELUM Pesanan Tiba: HANYA Tombol Lacak Resi / Kurir -->
              <button
                v-if="!isWarehouseArrived(p)"
                type="button"
                class="px-3 py-1.5 bg-alpharo-50 hover:bg-alpharo-100 text-alpharo-700 border border-alpharo-200 rounded-lg text-xs font-semibold transition-colors flex items-center justify-center gap-1 cursor-pointer"
                title="Buka rincian pelacakan resi pengiriman vendor"
                @click="openTrackingModal(p)"
              >
                <Truck class="w-3.5 h-3.5 text-alpharo-600" />
                <span>Lacak Resi / Kurir</span>
              </button>

              <!-- SETELAH Pesanan Tiba: HANYA Laporkan Kerusakan dan Konfirmasi Barang -->
              <template v-else>
                <!-- Laporkan Kerusakan Fisik ke Finance & Supplier -->
                <button
                  type="button"
                  class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition-colors flex items-center justify-center gap-1 cursor-pointer"
                  title="Laporkan ketidaksesuaian/kerusakan barang fisik kiriman supplier"
                  @click="openShipmentDiscrepancyModal(p)"
                >
                  <AlertTriangle class="w-3.5 h-3.5 text-rose-600" />
                  <span>Laporkan Kerusakan</span>
                </button>

                <!-- Tombol Konfirmasi Barang Masuk Stok (Warehouse Authority) -->
                <button
                  type="button"
                  class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold rounded-lg text-xs shadow-2xs transition-colors flex items-center justify-center gap-1 cursor-pointer"
                  title="Verifikasi fisik barang tiba dan tambahkan ke stok gudang"
                  @click="openConfirmReceiptModal(p)"
                >
                  <CheckCircle2 class="w-3.5 h-3.5" />
                  <span>Konfirmasi Barang</span>
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================================================================= -->
    <!-- TAB 3: PEMENUHAN ORDER PROYEK (FULFILLMENT KE KLIEN - TAHAP 5)                             -->
    <!-- ========================================================================================= -->
    <div v-if="activeTab === 'fulfillment'" class="space-y-5">
      <!-- Context Info PROBIS TO-BE -->
      <div class="bg-alpharo-50/70 border border-alpharo-200 rounded-xl p-3.5 text-xs text-slate-700">
        <div class="flex items-start gap-2.5">
          <Info class="w-4 h-4 text-alpharo-600 shrink-0 mt-0.5" />
          <p class="leading-relaxed">
            <strong>Tahap 5 PROBIS TO-BE:</strong> Warehouse menerima dan memeriksa kesesuaian fisik barang pesanan. Jika barang <strong>sesuai & lengkap</strong>, konfirmasi siap kirim untuk meneruskan notifikasi ke Marketing. Jika terdapat <strong>ketidaksesuaian/kerusakan</strong>, laporkan ke Finance agar segera ditindaklanjuti ke pihak Supplier.
          </p>
        </div>
      </div>

      <!-- Antrean Pemenuhan Pesanan Table Card -->
      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="h-px bg-slate-200 w-full"></div>
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Boxes class="w-4 h-4 text-alpharo-600" />
            <h3 class="font-semibold text-slate-900 text-xs sm:text-sm">Antrean Pemenuhan Barang Proyek Customer</h3>
          </div>
          <span class="text-xs text-slate-500 font-mono">
            {{ store.warehouseFulfillments?.length || 0 }} Order Aktif
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="ui-table-wide ui-table w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-[11px] uppercase tracking-wide">
              <tr>
                <th class="py-3 px-4">No. Order</th>
                <th class="py-3 px-4">Customer</th>
                <th class="py-3 px-4">Item Barang</th>
                <th class="py-3 px-4 text-center">Qty Order</th>
                <th class="py-3 px-4 text-center">Stok Fisik</th>
                <th class="py-3 px-4 text-center">Qty Siap</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="ful in store.warehouseFulfillments"
                :key="ful.id"
                class="hover:bg-slate-50/60 transition-colors"
              >
                <td class="py-3.5 px-4 font-mono font-semibold text-slate-900">
                  {{ ful.orderNumber }}
                  <span
                    v-if="getOrderHold(ful.orderNumber)"
                    class="block text-[11px] text-rose-600 font-sans font-semibold mt-0.5"
                  >
                    ⛔ Ditahan Direktur
                  </span>
                </td>
                <td class="py-3.5 px-4 font-semibold text-slate-800">{{ ful.clientName }}</td>
                <td class="py-3.5 px-4">
                  <div v-for="it in ful.items" :key="it.sku" class="text-xs">
                    <span class="font-medium text-slate-900">{{ it.name }}</span>
                  </div>
                </td>
                <td class="py-3.5 px-4 text-center font-mono">
                  <div v-for="it in ful.items" :key="it.sku">
                    {{ it.reqQty }} {{ it.unit }}
                  </div>
                </td>
                <td class="py-3.5 px-4 text-center font-mono text-emerald-600 font-semibold">
                  <div v-for="it in ful.items" :key="it.sku">
                    {{ it.availQty }} {{ it.unit }}
                  </div>
                </td>
                <td class="py-3.5 px-4 text-center font-mono text-slate-900 font-semibold">
                  <div v-for="it in ful.items" :key="it.sku">
                    {{ it.fulfilledQty }} {{ it.unit }}
                  </div>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <span
                    v-if="ful.status === 'Ketidaksesuaian'"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-200"
                  >
                    ⚠ Ketidaksesuaian
                  </span>
                  <StatusBadge v-else :status="ful.status" />
                </td>
                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                  <div v-if="getOrderHold(ful.orderNumber)">
                    <span
                      class="text-rose-700 font-semibold text-xs bg-rose-50 px-2.5 py-1 rounded border border-rose-200 inline-block cursor-help"
                      :title="getOrderHold(ful.orderNumber)?.holdReason"
                    >
                      ⛔ Ditahan Direktur
                    </span>
                  </div>
                  <div v-else class="flex items-center justify-end gap-1.5">
                    <!-- Button Laporkan Ketidaksesuaian ke Finance -->
                    <button
                      v-if="ful.status === 'Diproses'"
                      type="button"
                      class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-300 rounded font-semibold text-xs inline-flex items-center gap-1 transition-colors cursor-pointer"
                      title="Laporkan jika barang pesanan supplier cacat/tidak sesuai (Notifikasi ke Finance)"
                      @click="openDiscrepancyModal(ful)"
                    >
                      <AlertTriangle class="w-3.5 h-3.5 text-amber-600" />
                      <span>Lapor Ketidaksesuaian</span>
                    </button>

                    <!-- Button Konfirmasi Siap Kirim -->
                    <button
                      v-if="ful.status === 'Diproses'"
                      type="button"
                      class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-semibold text-xs shadow-2xs transition-colors cursor-pointer"
                      title="Konfirmasi kesesuaian fisik barang dan teruskan notifikasi ke Marketing & Administrasi"
                      @click="confirmFulfillment(ful)"
                    >
                      Konfirmasi Siap Kirim
                    </button>
                    <span
                      v-else-if="ful.status === 'Ketidaksesuaian'"
                      class="text-amber-800 font-semibold text-xs bg-amber-50 px-2.5 py-1 rounded border border-amber-200"
                    >
                      Menunggu Tindak Lanjut Finance
                    </span>
                    <span
                      v-else
                      class="text-emerald-700 font-semibold text-xs bg-emerald-50 px-2.5 py-1 rounded border border-emerald-200"
                    >
                      Siap Kirim ✓
                    </span>
                  </div>
                </td>
              </tr>

              <tr v-if="!store.warehouseFulfillments || store.warehouseFulfillments.length === 0">
                <td colspan="8" class="py-12 text-center text-slate-400"><div class="ui-empty-state">
                  Tidak ada data antrean pemenuhan barang aktif.
                </div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ========================================================================================= -->
    <!-- MODALS SECTION (SHARED ACROSS TABS)                                                       -->
    <!-- ========================================================================================= -->

    <!-- MODAL 1: BUAT PENGAJUAN BARU KE FINANCE -->
    <Modal
      v-model="createModalOpen"
      title="Pengajuan Kebutuhan Barang Baru"
      subtitle="Kirimkan permohonan material ke Finance untuk diterbitkan transaksi pembelian resmi."
      size="2xl"
    >
      <form class="space-y-4 text-xs" @submit.prevent="saveRequest">
        <!-- Proyek Target -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 uppercase text-[11px] mb-1">
              Proyek Pengadaan <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="requestForm.project_name"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-alpharo-400 bg-white"
            >
              <option v-for="proj in availableProjects" :key="proj.id" :value="proj.name">
                {{ proj.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 uppercase text-[11px] mb-1">
              Pemohon <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="requestForm.requested_by"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-slate-50 text-slate-700 font-medium"
              readonly
            />
          </div>
        </div>

        <!-- Keperluan Pengadaan -->
        <div>
          <label class="block font-semibold text-slate-700 uppercase text-[11px] mb-1">
            Keperluan / Alasan Pengadaan <span class="text-rose-500">*</span>
          </label>
          <input
            v-model="requestForm.purpose"
            required
            placeholder="Contoh: Kebutuhan pengadaan 100 unit komputer sekolah & notebook lab TIK..."
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-alpharo-400"
          />
        </div>

        <!-- Item Material yang Diajukan -->
        <div class="border border-slate-200 rounded-xl p-3 bg-slate-50/70 space-y-3">
          <div class="flex items-center justify-between">
            <span class="font-semibold text-slate-800 uppercase text-[11px] flex items-center gap-1.5">
              <Boxes class="w-4 h-4 text-emerald-600" />
              Item Material yang Diajukan ({{ requestForm.items.length }} Barang)
            </span>
            <button
              type="button"
              class="inline-flex items-center gap-1 text-emerald-700 font-semibold hover:text-emerald-800 cursor-pointer"
              @click="addRequestItem"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Tambah Baris</span>
            </button>
          </div>

          <div
            v-for="(it, idx) in requestForm.items"
            :key="idx"
            class="bg-white p-3 rounded-lg border border-slate-200 shadow-2xs space-y-2"
          >
            <div class="grid grid-cols-12 gap-2 items-center">
              <!-- Item Selector from Data Barang -->
              <div class="col-span-12 sm:col-span-6">
                <div class="flex items-center justify-between mb-0.5">
                  <label class="block text-[11px] text-slate-600 font-semibold uppercase tracking-wider">
                    PILIH BARANG DARI DATA BARANG <span class="text-rose-500">*</span>
                  </label>
                  <span v-if="it.sku" class="text-[11px] text-emerald-700 font-mono font-semibold flex items-center gap-0.5">
                    <Check class="w-3 h-3 stroke-[3]" /> Master Terdaftar
                  </span>
                </div>

                <!-- Clickable Selector Box -->
                <div
                  class="w-full flex items-center justify-between px-3 py-2 text-xs border rounded-lg cursor-pointer transition-all bg-white hover:border-emerald-500 shadow-2xs group"
                  :class="it.sku ? 'border-emerald-400 bg-emerald-50/20 ring-1 ring-emerald-200' : 'border-slate-300 text-slate-400 hover:bg-slate-50'"
                  @click="openCatalogPicker(idx)"
                >
                  <div class="truncate flex items-center gap-2">
                    <Boxes class="w-4 h-4 text-emerald-600 shrink-0" />
                    <div v-if="it.sku" class="truncate text-left">
                      <span class="font-mono text-emerald-900 font-semibold bg-emerald-100 px-1.5 py-0.5 rounded text-[11px] mr-1.5">
                        {{ it.sku }}
                      </span>
                      <span class="font-semibold text-slate-900">{{ it.name }}</span>
                    </div>
                    <span v-else class="text-slate-400 font-medium italic">
                      -- Klik di sini untuk memilih barang dari Data Barang --
                    </span>
                  </div>

                  <div class="flex items-center gap-1 shrink-0 ml-2">
                    <span class="px-2.5 py-1 bg-linear-to-b from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700 text-white border border-sky-600/30 rounded-md font-semibold text-[11px] flex items-center gap-1 transition-all shadow-2xs">
                      <Search class="w-3 h-3 stroke-[2.5]" />
                      <span>{{ it.sku ? 'Ganti' : 'Pilih Barang' }}</span>
                    </span>
                  </div>
                </div>

                <!-- Item metadata from master data barang -->
                <div v-if="it.sku" class="flex flex-wrap items-center gap-2 mt-1 text-[11px] text-slate-500">
                  <span v-if="it.category" class="bg-slate-100 px-1.5 py-0.2 rounded font-medium">
                    Kategori: <strong>{{ it.category }}</strong>
                  </span>
                  <span class="bg-amber-50 text-amber-800 border border-amber-200 px-1.5 py-0.2 rounded font-medium">
                    Stok Gudang: <strong>{{ it.current_stock ?? 0 }} {{ it.unit || 'UNIT' }}</strong>
                  </span>
                </div>
              </div>

              <!-- Qty & Satuan -->
              <div class="col-span-6 sm:col-span-3">
                <label class="block text-[11px] text-slate-600 font-semibold uppercase mb-0.5">Jumlah & Satuan</label>
                <div class="flex gap-1.5">
                  <input
                    v-model.number="it.qty"
                    type="number"
                    min="1"
                    required
                    placeholder="Qty"
                    class="w-full px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg text-center font-semibold text-slate-900 bg-white focus:outline-hidden focus:ring-1 focus:ring-alpharo-400"
                  />
                  <div
                    class="w-20 px-2 py-1.5 text-xs border border-slate-200 bg-slate-100 rounded-lg text-center font-semibold text-slate-700 uppercase shrink-0 flex items-center justify-center select-none"
                    title="Satuan otomatis mengikuti master data barang"
                  >
                    {{ it.unit || 'UNIT' }}
                  </div>
                </div>
              </div>

              <!-- Estimasi Harga -->
              <div class="col-span-5 sm:col-span-2">
                <label class="block text-[11px] text-slate-600 font-semibold uppercase mb-0.5">Est. Harga Satuan</label>
                <input
                  v-model.number="it.est_price"
                  type="number"
                  min="0"
                  placeholder="Rp"
                  class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded-lg text-right font-mono text-slate-900 bg-white focus:outline-hidden focus:ring-1 focus:ring-alpharo-400"
                />
              </div>

              <!-- Delete Action -->
              <div class="col-span-1 flex items-end justify-center pb-0.5">
                <button
                  v-if="requestForm.items.length > 1"
                  type="button"
                  class="w-7 h-7 inline-flex items-center justify-center bg-linear-to-b from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 active:from-red-700 active:to-red-800 text-white rounded-lg shadow-2xs border border-red-700/30 transition-all cursor-pointer"
                  title="Hapus baris"
                  @click="removeRequestItem(idx)"
                >
                  <Trash2 class="w-3.5 h-3.5 stroke-[2.2]" />
                </button>
              </div>
            </div>

            <!-- Subtotal Baris -->
            <div class="flex justify-end text-[11px] text-slate-500 font-mono pt-1 border-t border-slate-100">
              Subtotal: <span class="font-semibold text-slate-800 ml-1">Rp {{ ((it.qty || 0) * (it.est_price || 0)).toLocaleString('id-ID') }}</span>
            </div>
          </div>

          <!-- Total Estimasi -->
          <div class="flex items-center justify-between p-3 bg-emerald-50/70 border border-emerald-200 rounded-lg">
            <span class="font-semibold text-emerald-950 uppercase text-[11px]">Total Estimasi Biaya Pengadaan:</span>
            <span class="text-sm font-semibold font-mono text-emerald-900">
              Rp {{ computedTotalEstCost.toLocaleString('id-ID') }}
            </span>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
          <button
            type="button"
            class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-lg font-medium transition-colors cursor-pointer"
            @click="createModalOpen = false"
          >
            Batal
          </button>
          <button
            type="submit"
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white font-semibold rounded-lg shadow-xs transition-colors cursor-pointer"
          >
            <Send class="w-3.5 h-3.5" />
            <span>Kirim Pengajuan ke Finance</span>
          </button>
        </div>
      </form>
    </Modal>

    <!-- MODAL 2: DETAIL PENGAJUAN KEBUTUHAN BARANG -->
    <Modal
      v-model="detailModalOpen"
      :title="'Detail Pengajuan: ' + (selectedRequest?.requestCode || selectedRequest?.request_code || '')"
      subtitle="Informasi kebutuhan material gudang dan progres di bagian Keuangan (Finance)."
      size="lg"
    >
      <div v-if="selectedRequest" class="space-y-4 text-xs">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
          <div>
            <div class="text-[11px] text-slate-400 uppercase font-semibold">Kode Pengajuan</div>
            <div class="font-semibold text-slate-900 font-mono mt-0.5">{{ selectedRequest.requestCode || selectedRequest.request_code }}</div>
          </div>
          <div>
            <div class="text-[11px] text-slate-400 uppercase font-semibold">Tanggal</div>
            <div class="font-semibold text-slate-900 font-mono mt-0.5">{{ selectedRequest.date || selectedRequest.request_date }}</div>
          </div>
          <div>
            <div class="text-[11px] text-slate-400 uppercase font-semibold">Diajukan Oleh</div>
            <div class="font-semibold text-slate-900 mt-0.5">{{ selectedRequest.requestedBy || selectedRequest.requested_by }}</div>
          </div>
          <div>
            <div class="text-[11px] text-slate-400 uppercase font-semibold">Status Alur</div>
            <div class="font-semibold mt-0.5" :class="selectedRequest.status === 'MENUNGGU_FINANCE' ? 'text-amber-700' : 'text-emerald-700'">
              {{ selectedRequest.status }}
            </div>
          </div>
        </div>

        <div>
          <div class="font-semibold text-slate-700 uppercase text-[11px] mb-1">Proyek Target & Alasan Pengadaan</div>
          <div class="p-3 bg-white border border-slate-200 rounded-lg space-y-1">
            <div class="font-semibold text-slate-900">{{ selectedRequest.projectName || selectedRequest.project_name }}</div>
            <div class="text-slate-600 text-xs">{{ selectedRequest.purpose }}</div>
          </div>
        </div>

        <!-- Items Table -->
        <div>
          <div class="font-semibold text-slate-700 uppercase text-[11px] mb-1">Rincian Material yang Diajukan</div>
          <div class="border border-slate-200 rounded-lg overflow-hidden">
            <table class="ui-table w-full text-left text-xs">
              <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-[11px]">
                <tr>
                  <th class="py-2.5 px-3">Nama Barang</th>
                  <th class="py-2.5 px-3 text-center">Jumlah</th>
                  <th class="py-2.5 px-3 text-right">Est. Harga</th>
                  <th class="py-2.5 px-3 text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(it, idx) in selectedRequest.items" :key="idx">
                  <td class="py-2.5 px-3">
                    <span class="font-semibold text-slate-900 block">{{ it.name }}</span>
                    <span v-if="it.sku" class="text-[11px] text-slate-400 font-mono">SKU: {{ it.sku }}</span>
                  </td>
                  <td class="py-2.5 px-3 text-center font-semibold text-slate-800">
                    {{ it.qty }} {{ it.unit || 'UNIT' }}
                  </td>
                  <td class="py-2.5 px-3 text-right font-mono text-slate-600">
                    Rp {{ Number(it.estPrice || it.est_price || 0).toLocaleString('id-ID') }}
                  </td>
                  <td class="py-2.5 px-3 text-right font-mono font-semibold text-slate-900">
                    Rp {{ Number((it.qty || 0) * (it.estPrice || it.est_price || 0)).toLocaleString('id-ID') }}
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-slate-50 border-t border-slate-200 font-semibold">
                <tr>
                  <td colspan="3" class="py-2.5 px-3 text-right uppercase text-[11px] text-slate-600">Total Estimasi:</td>
                  <td class="py-2.5 px-3 text-right font-mono text-emerald-800 text-sm">
                    Rp {{ Number(selectedRequest.totalEstCost || selectedRequest.total_est_cost || 0).toLocaleString('id-ID') }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Status Flow Card -->
        <div class="p-3 bg-sky-50/70 border border-sky-200 rounded-xl text-sky-900 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Info class="w-4 h-4 text-[#0284c7] shrink-0" />
            <span>
              Status saat ini: <strong>{{ selectedRequest.status }}</strong>
              <span v-if="selectedRequest.purchase_code || selectedRequest.purchaseCode"> (No PO: {{ selectedRequest.purchase_code || selectedRequest.purchaseCode }})</span>
            </span>
          </div>
          <button
            type="button"
            class="px-3 py-1 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-md font-semibold text-xs cursor-pointer"
            @click="detailModalOpen = false"
          >
            Tutup
          </button>
        </div>
      </div>
    </Modal>

    <!-- MODAL 3: QUICK PICKER MASTER DATA BARANG -->
    <Modal
      v-model="catalogPickerOpen"
      title="Pilih Barang dari Master Data Barang"
      subtitle="Pilih dari 2.196 data barang resmi yang tersimpan di sistem."
      size="2xl"
    >
      <div class="space-y-3 text-xs">
        <div class="flex flex-col sm:flex-row gap-2">
          <!-- Search Bar -->
          <div class="relative flex-1">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="catalogSearch"
              type="text"
              placeholder="Cari barang (contoh: A00001770, Komputer, Semen, Cat, Besi)..."
              class="w-full pl-9 pr-8 py-2 border border-slate-300 rounded-lg text-xs focus:outline-hidden focus:ring-1 focus:ring-alpharo-400"
              autofocus
            />
            <button
              v-if="catalogSearch"
              type="button"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
              @click="catalogSearch = ''"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Category Filter -->
          <div class="sm:w-56">
            <select
              v-model="catalogCategoryFilter"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs bg-white text-slate-800 focus:outline-hidden focus:ring-1 focus:ring-alpharo-400"
            >
              <option value="">Semua Kategori</option>
              <option v-for="cat in catalogCategories" :key="cat" :value="cat">
                {{ cat }}
              </option>
            </select>
          </div>
        </div>

        <!-- Search Counter -->
        <div class="flex items-center justify-between text-[11px] text-slate-500 px-1">
          <span>Menampilkan <strong>{{ filteredCatalogItems.length }}</strong> data barang</span>
          <span class="text-emerald-700 font-medium">Klik pada baris barang untuk memilih langsung</span>
        </div>

        <!-- Items Table -->
        <div class="max-h-80 overflow-y-auto border border-slate-200 rounded-lg">
          <table class="ui-table-wide ui-table w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-[11px] sticky top-0">
              <tr>
                <th class="py-2.5 px-3">Kode SKU</th>
                <th class="py-2.5 px-3">Nama Barang</th>
                <th class="py-2.5 px-3">Kategori</th>
                <th class="py-2.5 px-3 text-center">Satuan</th>
                <th class="py-2.5 px-3 text-center">Stok Gudang</th>
                <th class="py-2.5 px-3 text-right">Harga Acuan</th>
                <th class="py-2.5 px-3 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="filteredCatalogItems.length === 0">
                <td colspan="7" class="py-8 text-center text-slate-400"><div class="ui-empty-state">
                  Tidak ada barang yang sesuai dengan kata kunci pencarian.
                </div></td>
              </tr>
              <tr
                v-for="p in filteredCatalogItems"
                :key="p.sku"
                class="hover:bg-emerald-50/70 transition-colors cursor-pointer group"
                @click="selectCatalogItem(p)"
              >
                <td class="py-2.5 px-3 font-mono font-semibold text-emerald-800 whitespace-nowrap">
                  {{ p.sku }}
                </td>
                <td class="py-2.5 px-3 font-semibold text-slate-900 group-hover:text-emerald-900">
                  {{ p.name }}
                </td>
                <td class="py-2.5 px-3 text-slate-600 whitespace-nowrap">
                  {{ p.category || '-' }}
                </td>
                <td class="py-2.5 px-3 text-center font-semibold text-slate-700 uppercase whitespace-nowrap">
                  {{ p.unit || 'UNIT' }}
                </td>
                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                  <span
                    class="px-2 py-0.5 rounded-full text-[11px] font-semibold"
                    :class="p.stock > 10 ? 'bg-emerald-100 text-emerald-800' : p.stock > 0 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800'"
                  >
                    {{ p.stock ?? 0 }}
                  </span>
                </td>
                <td class="py-2.5 px-3 text-right font-mono text-slate-700 whitespace-nowrap">
                  Rp {{ Number(p.last_buy_price || p.hpp || p.sell_price || 0).toLocaleString('id-ID') }}
                </td>
                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                  <button
                    type="button"
                    class="px-2.5 py-1 bg-emerald-600 group-hover:bg-emerald-700 text-white rounded text-[11px] font-semibold shadow-2xs transition-colors cursor-pointer"
                  >
                    Pilih
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </Modal>

    <!-- MODAL 4: KONFIRMASI PENERIMAAN FISIK BARANG & REAL-TIME STOK ACC -->
    <Modal
      v-model="receiptModalOpen"
      title="Konfirmasi Penerimaan Fisik Barang & Update Stok (ACC)"
      :subtitle="`PO: ${selectedShipmentForReceipt?.purchase_code || ''} • Supplier: ${selectedShipmentForReceipt?.default_supplier || ''}`"
      size="lg"
    >
      <div v-if="selectedShipmentForReceipt" class="space-y-4 text-xs">
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-950">
          <span class="font-semibold text-sm block">Verifikasi Fisik Kedatangan Barang Supplier</span>
          <span class="text-[11px] text-emerald-800">
            Proyek: {{ selectedShipmentForReceipt.project_name }} • Surat Jalan: {{ selectedShipmentForReceipt.delivery_info?.surat_jalan || 'SJ-VENDOR' }}
          </span>
        </div>

        <!-- Real-Time Stock Increment Table -->
        <div>
          <label class="block font-semibold text-slate-700 mb-1.5 uppercase text-[11px]">
            Simulasi Penambahan Stok Fisik Gudang Secara Real-Time:
          </label>
          <div class="border border-slate-200 rounded-lg overflow-hidden bg-white shadow-2xs">
            <table class="ui-table w-full text-left text-xs">
              <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-[11px]">
                <tr>
                  <th class="p-2.5">Item Barang</th>
                  <th class="p-2.5 text-center">Jumlah Diterima</th>
                  <th class="p-2.5 text-center">Stok Sebelum</th>
                  <th class="p-2.5 text-center">Stok Sesudah (ACC)</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(it, idx) in selectedShipmentForReceipt.items" :key="idx">
                  <td class="p-2.5">
                    <span class="font-medium text-slate-900 block">{{ it.barang || it.name }}</span>
                    <span class="font-mono text-[11px] text-slate-400">{{ it.sku || '-' }}</span>
                  </td>
                  <td class="p-2.5 text-center font-semibold text-emerald-700 font-mono">
                    +{{ it.qty }} {{ it.unit || 'Unit' }}
                  </td>
                  <td class="p-2.5 text-center font-mono text-slate-500">
                    {{ getStockProjection(it).currentStock }} {{ getStockProjection(it).unit }}
                  </td>
                  <td class="p-2.5 text-center font-mono font-semibold text-emerald-700 bg-emerald-50/50">
                    {{ getStockProjection(it).newStock }} {{ getStockProjection(it).unit }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Catatan Hasil Pemeriksaan Fisik Gudang:</label>
          <textarea
            v-model="receiptNotes"
            rows="2"
            class="w-full p-2.5 border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-alpharo-400 bg-slate-50 text-slate-800"
            placeholder="Kondisi kemasan, segel, kelengkapan surat jalan..."
          ></textarea>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between pt-3 border-t border-slate-200">
          <button
            type="button"
            class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-lg font-medium cursor-pointer"
            @click="receiptModalOpen = false"
          >
            Batal
          </button>
          <button
            type="button"
            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg shadow-xs flex items-center gap-1.5 cursor-pointer"
            @click="executeConfirmReceipt"
          >
            <CheckCircle2 class="w-4 h-4" />
            <span>Konfirmasi Barang</span>
          </button>
        </div>
      </div>
    </Modal>

    <!-- MODAL 5: LAPORKAN KERUSAKAN FISIK KE SUPPLIER & FINANCE (WITH MEDIA UPLOAD) -->
    <Modal
      v-model="shipmentDiscrepancyModalOpen"
      title="Laporkan Kerusakan Fisik Barang ke Supplier & Finance"
      :subtitle="`PO: ${selectedShipmentForDiscrepancy?.purchase_code || ''} • Supplier: ${selectedShipmentForDiscrepancy?.default_supplier || ''}`"
      size="lg"
    >
      <div v-if="selectedShipmentForDiscrepancy" class="space-y-4 text-xs">
        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-950 flex items-start gap-2.5">
          <AlertTriangle class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
          <div>
            <span class="font-semibold block">Skenario 1 PROBIS TO-BE: Retur & Penggantian Barang</span>
            <span class="text-amber-800 text-[11px] leading-relaxed">
              Jika barang cacat, pecah, atau tidak sesuai spesifikasi PO, laporkan di sini. Laporan ini otomatis diteruskan ke portal <strong>Supplier</strong> untuk pengiriman barang pengganti dan dicatat di <strong>Finance</strong>.
            </span>
          </div>
        </div>

        <!-- PEMILIHAN BARANG YANG RUSAK / DIRETUR -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <div>
              <label class="block font-semibold text-slate-800">
                Pilih Barang yang Rusak / Diretur <span class="text-rose-500">*</span>
              </label>
              <p class="text-[11px] text-slate-500">
                Centang barang yang mengalami kerusakan. Barang yang tidak dicentang dianggap dalam kondisi baik (tidak diretur).
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="text-[11px] text-alpharo-600 hover:text-alpharo-700 font-semibold hover:underline cursor-pointer"
                @click="toggleAllDiscrepancyItems(true)"
              >
                Pilih Semua
              </button>
              <span class="text-slate-300">|</span>
              <button
                type="button"
                class="text-[11px] text-slate-500 hover:text-slate-700 font-semibold hover:underline cursor-pointer"
                @click="toggleAllDiscrepancyItems(false)"
              >
                Batal Semua
              </button>
            </div>
          </div>

          <!-- Daftar Barang PO untuk Dipilih -->
          <div class="border border-slate-200 rounded-xl overflow-hidden divide-y divide-slate-100 bg-white shadow-2xs">
            <div
              v-for="it in discrepancyItemsList"
              :key="it.id"
              class="p-3 transition-colors bg-white hover:bg-slate-50/60"
            >
              <div class="flex items-start justify-between gap-3">
                <label class="flex items-start gap-2.5 cursor-pointer select-none grow">
                  <input
                    v-model="it.is_damaged"
                    type="checkbox"
                    class="mt-0.5 rounded border-slate-300 text-alpharo-600 focus:ring-alpharo-500 w-4 h-4 cursor-pointer"
                  />
                  <div>
                    <span class="font-semibold block text-xs text-slate-900">
                      {{ it.barang }}
                    </span>
                    <span class="text-[11px] text-slate-500 font-mono">
                      Total Kiriman: {{ it.total_qty }} {{ it.unit }}
                    </span>
                  </div>
                </label>

                <!-- Status Badge -->
                <div class="shrink-0 text-right">
                  <span
                    v-if="it.is_damaged"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-rose-700 border border-slate-200"
                  >
                    <AlertTriangle class="w-3 h-3 text-rose-600" /> Diretur (Rusak)
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                  >
                    <CheckCircle2 class="w-3 h-3 text-emerald-600" /> Kondisi Baik
                  </span>
                </div>
              </div>

              <!-- Input Rincian Tambahan jika Barang Dicap Rusak -->
              <div v-if="it.is_damaged" class="mt-2.5 pt-2 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                <div>
                  <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                    Jumlah Rusak / Diretur (Maks: {{ it.total_qty }} {{ it.unit }}) <span class="text-rose-500">*</span>
                  </label>
                  <div class="flex items-center gap-1.5">
                    <input
                      v-model.number="it.damaged_qty"
                      type="number"
                      min="1"
                      :max="it.total_qty"
                      required
                      class="w-24 px-2 py-1 rounded-lg border border-slate-300 focus:border-alpharo-500 focus:ring-1 focus:ring-alpharo-500 bg-white font-mono font-semibold text-slate-900"
                    />
                    <span class="text-slate-600 font-mono text-[11px]">{{ it.unit }}</span>
                  </div>
                </div>

                <div>
                  <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                    Keterangan Cacat / Kerusakan Spesifik
                  </label>
                  <input
                    v-model="it.notes"
                    type="text"
                    placeholder="Contoh: Layar retak, pecah, fungsi mati..."
                    class="w-full px-2 py-1 rounded-lg border border-slate-300 focus:border-alpharo-500 focus:ring-1 focus:ring-alpharo-500 bg-white text-xs text-slate-900 placeholder:text-slate-400"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>


        <!-- FOTO/VIDEO UPLOAD AREA -->
        <div>
          <label class="block font-semibold text-slate-700 mb-1.5 flex items-center justify-between">
            <span>Unggah Bukti Foto / Video Kerusakan:</span>
            <span class="text-[11px] text-slate-400 font-normal">Mendukung format JPG, PNG, MP4, WebM (Maks 25MB)</span>
          </label>

          <input
            ref="fileInputRef"
            type="file"
            multiple
            accept="image/*,video/*"
            class="hidden"
            @change="handleMediaUpload"
          />

          <!-- Media Thumbnails Grid -->
          <div class="flex items-center gap-3 overflow-x-auto py-2">
            <!-- Uploaded Items -->
            <div
              v-for="(item, idx) in discrepancyMedia"
              :key="idx"
              class="relative w-24 h-24 sm:w-28 sm:h-28 rounded-2xl overflow-hidden bg-slate-900 border border-slate-200 shadow-sm shrink-0 group cursor-pointer"
              @click="openMediaPreview(item)"
            >
              <img
                v-if="item.type === 'image'"
                :src="item.url"
                :alt="item.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
              <div v-else class="w-full h-full relative flex items-center justify-center bg-slate-950">
                <video :src="item.url" class="w-full h-full object-cover opacity-80" muted></video>
                <div class="absolute inset-0 bg-black/20 flex items-center justify-center">
                  <div class="w-8 h-8 rounded-full bg-black/40 text-white flex items-center justify-center text-xs">▶</div>
                </div>
                <div class="absolute bottom-1.5 left-1.5 right-1.5 flex items-center justify-between text-white text-[11px] font-mono drop-shadow-md">
                  <div class="flex items-center gap-1">
                    <Video class="w-3.5 h-3.5" />
                  </div>
                  <span>{{ item.duration || '0:24' }}</span>
                </div>
              </div>

              <!-- Close / Delete Button -->
              <button
                type="button"
                class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-black/60 hover:bg-rose-600 text-white flex items-center justify-center transition-colors shadow-xs cursor-pointer z-10"
                title="Hapus file ini"
                @click.stop="removeDiscrepancyMedia(idx)"
              >
                <X class="w-3.5 h-3.5" />
              </button>
            </div>

            <!-- Upload Placeholder Box -->
            <button
              type="button"
              class="w-24 h-24 sm:w-28 sm:h-28 rounded-xl border-2 border-dashed border-slate-300 hover:border-slate-400 bg-white hover:bg-slate-50/80 flex flex-col items-center justify-center gap-1.5 shrink-0 transition-colors cursor-pointer group"
              @click="triggerFileInput"
            >
              <div class="w-8 h-8 rounded-full bg-slate-100 group-hover:bg-slate-200 flex items-center justify-center text-slate-600 transition-colors">
                <Camera class="w-4 h-4 stroke-[2]" />
              </div>
              <span class="text-xs font-semibold text-slate-700">Video/Foto</span>
            </button>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between pt-3 border-t border-slate-200">
          <button
            type="button"
            class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-lg font-medium cursor-pointer"
            @click="shipmentDiscrepancyModalOpen = false"
          >
            Batal
          </button>
          <button
            type="button"
            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg shadow-xs flex items-center gap-1.5 cursor-pointer"
            @click="submitShipmentDiscrepancy"
          >
            <AlertTriangle class="w-4 h-4" />
            <span>Kirim Laporan Retur ke Supplier</span>
          </button>
        </div>
      </div>
    </Modal>

    <!-- MODAL 6: TIMELINE LACAK RESI & KURIR SUPPLIER -->
    <Modal
      v-model="trackingModalOpen"
      title="Live Tracking Pengiriman Supplier"
      :subtitle="`PO: ${selectedShipmentForTracking?.purchase_code || ''} • Supplier: ${selectedShipmentForTracking?.default_supplier || ''}`"
      size="lg"
    >
      <div v-if="selectedShipmentForTracking" class="space-y-4 text-xs">
        <!-- Status Box -->
        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Ekspedisi / Kurir Rekanan:</span>
            <p class="text-sm font-semibold text-slate-900 mt-0.5">{{ selectedShipmentForTracking.delivery_info?.courier || 'Armada Internal Supplier' }}</p>
            <p class="text-xs text-slate-600 mt-0.5">
              No. Resi / Plat Mobil: <strong class="font-mono text-alpharo-700 bg-alpharo-50 px-2 py-0.5 rounded border border-alpharo-200">{{ selectedShipmentForTracking.delivery_info?.tracking_number || '-' }}</strong>
            </p>
          </div>
          <div class="sm:text-right shrink-0">
            <div class="flex items-center sm:justify-end gap-1.5 mb-1">
              <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status Kurir:</span>
              <span
                class="px-2 py-0.5 rounded-full text-[11px] font-semibold border inline-flex items-center gap-1"
                :class="isCourierArrived(selectedShipmentForTracking) ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-alpharo-50 text-alpharo-800 border-alpharo-200'"
              >
                <span class="w-1.5 h-1.5 rounded-full" :class="isCourierArrived(selectedShipmentForTracking) ? 'bg-emerald-500' : 'bg-alpharo-500 animate-pulse'"></span>
                <span>{{ selectedShipmentForTracking.delivery_info?.shipping_status || 'Dalam Perjalanan Menuju Gudang' }}</span>
              </span>
            </div>
            <p class="text-xs text-slate-500">
              Estimasi Tiba: <strong class="text-slate-900 font-mono">{{ selectedShipmentForTracking.delivery_info?.est_arrival || 'Hari Ini' }}</strong>
            </p>
          </div>
        </div>

        <!-- Titik Lokasi Terkini Box (Image 2) -->
        <div class="ui-panel p-3 bg-white border border-slate-200 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs">
          <div class="flex items-center gap-2 text-xs">
            <span class="text-rose-500 text-sm">📍</span>
            <span class="text-slate-600">Titik Lokasi Terkini: <strong class="text-slate-900 font-semibold">{{ selectedShipmentForTracking.delivery_info?.current_location || 'Dalam Perjalanan Menuju Gudang Proyek' }}</strong></span>
          </div>
          <div class="text-[11px] text-slate-500 font-medium sm:text-right">
            <span>Diperbarui: <strong class="text-slate-700 font-mono">{{ selectedShipmentForTracking.delivery_info?.last_updated || 'Real-time' }}</strong></span>
          </div>
        </div>

        <!-- Alert Banner Tracking -->
        <div
          v-if="!isCourierArrived(selectedShipmentForTracking)"
          class="p-3 bg-amber-50/90 border border-amber-200 rounded-xl flex items-start gap-2.5 text-xs text-amber-900"
        >
          <Lock class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
          <div class="space-y-0.5">
            <p class="font-semibold text-amber-950">Tombol Pesanan Tiba Masih Terkunci (Disable)</p>
            <p class="text-[11px] text-amber-800 leading-relaxed">
              Status kurir saat ini masih <strong>"{{ selectedShipmentForTracking.delivery_info?.shipping_status || 'Dalam Pengiriman' }}"</strong>. Tombol konfirmasi kedatangan di bawah otomatis aktif setelah status kurir diperbarui menjadi <strong>"Menuju Alamat Gudang (Out for Delivery)"</strong>.
            </p>
          </div>
        </div>
        <div
          v-else-if="!isWarehouseArrived(selectedShipmentForTracking)"
          class="p-3 bg-emerald-50/90 border border-emerald-200 rounded-xl flex items-start gap-2.5 text-xs text-emerald-900"
        >
          <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
          <div class="space-y-0.5">
            <p class="font-semibold text-emerald-950">Kurir Sedang Menuju Alamat Gudang (Siap Tiba)</p>
            <p class="text-[11px] text-emerald-800 leading-relaxed">
              Armada kurir sedang menuju alamat gudang. Tombol <strong>"Pesanan Tiba"</strong> telah terbuka otomatis. Silakan klik tombol tersebut saat armada sampai di lokasi untuk membuka aksi pemeriksaan barang (Laporkan Kerusakan & Konfirmasi Barang).
            </p>
          </div>
        </div>
        <div
          v-else
          class="p-3 bg-emerald-50/90 border border-emerald-200 rounded-xl flex items-start gap-2.5 text-xs text-emerald-900"
        >
          <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
          <div class="space-y-0.5">
            <p class="font-semibold text-emerald-950">Kedatangan Pesanan Telah Dikonfirmasi Gudang</p>
            <p class="text-[11px] text-emerald-800 leading-relaxed">
              Barang fisik telah tiba di lokasi. Silakan lakukan pemeriksaan barang (Laporkan Kerusakan jika bermasalah, atau Konfirmasi Barang untuk menambah stok).
            </p>
          </div>
        </div>

        <!-- Live Delivery Timeline -->
        <div>
          <div class="mb-3">
            <h4 class="font-semibold text-slate-900 text-xs flex items-center gap-1.5">
              <Clock class="w-4 h-4 text-alpharo-600" />
              <span>Riwayat Perjalanan Kurir:</span>
            </h4>
          </div>

          <!-- Dynamic Milestones If Present -->
          <div v-if="selectedShipmentForTracking.delivery_info?.milestones?.length" class="space-y-3 relative before:absolute before:inset-0 before:left-3 before:w-0.5 before:bg-alpharo-100 pl-7">
            <div
              v-for="(ms, idx) in selectedShipmentForTracking.delivery_info.milestones"
              :key="ms.id || idx"
              class="relative"
            >
              <div
                class="absolute -left-7 top-0.5 w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-semibold shadow-2xs"
                :class="(!isCourierArrived(selectedShipmentForTracking) && idx === 0) ? 'bg-alpharo-600 text-white animate-pulse' : 'bg-emerald-600 text-white'"
              >
                {{ (!isCourierArrived(selectedShipmentForTracking) && idx === 0) ? '🚚' : '✓' }}
              </div>
              <div class="flex items-center gap-2">
                <span class="font-semibold text-slate-900 text-xs">{{ ms.status }}</span>
                <span class="text-[11px] text-slate-400 font-mono">{{ ms.time || ms.timestamp }}</span>
              </div>
              <p class="text-slate-600 text-[11px] mt-0.5">{{ ms.description || ms.notes || ms.location }}</p>
            </div>
          </div>

          <!-- Fallback Default Milestones -->
          <div v-else class="space-y-4 relative before:absolute before:inset-0 before:left-3 before:w-0.5 before:bg-alpharo-100 pl-7">
            <div class="relative">
              <div class="absolute -left-7 top-0.5 w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[11px] font-semibold shadow-2xs">✓</div>
              <p class="font-semibold text-slate-900">Pesanan Dikonfirmasi & Dipersiapkan Supplier</p>
              <p class="text-slate-500 text-[11px]">Supplier telah mengemas barang sesuai spesifikasi PO {{ selectedShipmentForTracking.purchase_code }}.</p>
            </div>
            <div class="relative">
              <div class="absolute -left-7 top-0.5 w-6 h-6 rounded-full bg-alpharo-600 text-white flex items-center justify-center text-[11px] font-semibold animate-pulse shadow-xs">🚚</div>
              <p class="font-semibold text-alpharo-950">Sedang Dalam Perjalanan Menuju Gudang CV Alpharo</p>
              <p class="text-slate-600 text-[11px]">Kurir sedang bergerak menuju alamat gudang proyek (Catatan: "{{ selectedShipmentForTracking.delivery_info?.notes || 'Barang dalam kondisi baik' }}")</p>
            </div>
            <div class="relative">
              <div class="absolute -left-7 top-0.5 w-6 h-6 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center text-[11px] font-semibold">🏁</div>
              <p class="font-semibold text-slate-500">Penerimaan & Pemeriksaan Fisik Gudang</p>
              <p class="text-slate-400 text-[11px]">Tim gudang siap memeriksa fisik barang begitu kurir tiba di lokasi.</p>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="flex items-center justify-between pt-3 border-t border-slate-200">
          <button
            type="button"
            class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 font-medium cursor-pointer"
            @click="trackingModalOpen = false"
          >
            Tutup
          </button>
          <div class="flex items-center gap-2">
            <!-- Jika SUDAH klik Pesanan Tiba: Tombol berubah jadi Konfirmasi Barang langsung -->
            <button
              v-if="isWarehouseArrived(selectedShipmentForTracking)"
              type="button"
              class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer transition-colors"
              @click="trackingModalOpen = false; openConfirmReceiptModal(selectedShipmentForTracking)"
            >
              <CheckCircle2 class="w-4 h-4" />
              <span>Konfirmasi Barang</span>
            </button>

            <!-- Jika status kurir SUDAH 'Menuju Alamat': tombol Pesanan Tiba OTOMATIS AKTIF -->
            <button
              v-else-if="isCourierArrived(selectedShipmentForTracking)"
              type="button"
              class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer transition-colors"
              @click="handleConfirmPesananTiba(selectedShipmentForTracking)"
            >
              <CheckCircle2 class="w-4 h-4" />
              <span>Pesanan Tiba</span>
            </button>

            <!-- Jika status kurir BELUM 'Menuju Alamat': tombol Pesanan Tiba TERKUNCI -->
            <button
              v-else
              type="button"
              disabled
              class="px-4 py-2 bg-slate-100 text-slate-400 border border-slate-200 rounded-lg font-semibold flex items-center gap-1.5 cursor-not-allowed opacity-80"
              :title="`Terkunci: Status kurir saat ini masih '${selectedShipmentForTracking?.delivery_info?.shipping_status || 'Dalam Pengiriman'}'. Tombol baru aktif otomatis setelah status kurir berubah menjadi 'Menuju Alamat Gudang (Out for Delivery)'.`"
            >
              <Lock class="w-4 h-4 text-slate-400" />
              <span>Pesanan Tiba</span>
            </button>
          </div>
        </div>
      </div>
    </Modal>

    <!-- MODAL 7: PRATINJAU FOTO/VIDEO MEDIA (POPUP) -->
    <Modal
      v-model="mediaPreviewModalOpen"
      :title="activePreviewMedia?.name || 'Pratinjau Bukti Media'"
      :subtitle="activePreviewMedia?.type === 'video' ? 'Pemutaran Video Bukti Kerusakan Fisik' : 'Foto Detail Kondisi Fisik Barang'"
      size="xl"
    >
      <div v-if="activePreviewMedia" class="space-y-3">
        <div class="w-full rounded-2xl overflow-hidden bg-black flex items-center justify-center max-h-[70vh]">
          <img
            v-if="activePreviewMedia.type === 'image'"
            :src="activePreviewMedia.url"
            :alt="activePreviewMedia.name"
            class="w-full h-auto max-h-[70vh] object-contain"
          />
          <video
            v-else
            :src="activePreviewMedia.url"
            controls
            autoplay
            class="w-full h-auto max-h-[70vh]"
          ></video>
        </div>
        <div class="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-200">
          <div>
            <span class="font-semibold text-slate-800">{{ activePreviewMedia.name }}</span>
            <span class="ml-2 font-mono">({{ activePreviewMedia.size }})</span>
          </div>
          <button
            type="button"
            class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg cursor-pointer"
            @click="mediaPreviewModalOpen = false"
          >
            Tutup
          </button>
        </div>
      </div>
    </Modal>

    <!-- MODAL 8: LAPOR KETIDAKSESUAIAN ORDER CUSTOMER KE FINANCE -->
    <Modal
      v-model="discrepancyModalOpen"
      title="Laporkan Ketidaksesuaian Barang ke Finance"
      subtitle="Sesuai PROBIS TO-BE: Divisi Finance akan menindaklanjuti retur/penggantian ke Supplier"
      size="md"
    >
      <div v-if="selectedDiscrepancyItem" class="space-y-4 text-xs">
        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-900 space-y-1">
          <p class="font-semibold">Order: {{ selectedDiscrepancyItem.orderNumber }} ({{ selectedDiscrepancyItem.clientName }})</p>
          <p class="text-[11px] text-amber-800">
            Laporan ini akan langsung dikirimkan ke <strong>Notifikasi Role Finance</strong> sebagai dasar penanganan ketidaksesuaian barang supplier.
          </p>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">
            Rincian Ketidaksesuaian / Kerusakan Barang <span class="text-rose-500">*</span>
          </label>
          <textarea
            v-model="discrepancyReason"
            rows="3"
            class="w-full p-2.5 border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-amber-500"
            placeholder="Contoh: Barang diterima dalam keadaan segel terbuka, 5 unit rusak fisik..."
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button
            type="button"
            class="px-3.5 py-1.5 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50"
            @click="discrepancyModalOpen = false"
          >
            Batal
          </button>
          <button
            type="button"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg shadow-xs"
            @click="submitDiscrepancyReport"
          >
            Kirim Laporan ke Finance
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from '../../navigation';
import AppLayout from '../../components/layout/AppLayout.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import Modal from '../../components/common/Modal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import api from '../../services/api';
import PurchaseItemList from '../../components/common/PurchaseItemList.vue';
import { orderError } from '../../services/orderWorkflow';
import {
  Info,
  AlertTriangle,
  Truck,
  CheckCircle2,
  Camera,
  Video,
  X,
  RefreshCw,
  Image,
  Clock,
  Search,
  ClipboardList,
  Plus,
  RotateCcw,
  Boxes,
  Package,
  Trash2,
  Send,
  Check,
  ArrowRightLeft,
  Lock,
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const toast = useToast();

// Active tab ('requests' | 'inbound' | 'fulfillment')
const validTabs = ['requests', 'inbound', 'fulfillment'];
const initialTab = route.query.tab && validTabs.includes(route.query.tab) ? route.query.tab : 'requests';
const activeTab = ref(initialTab);

watch(
  () => route.query.tab,
  (newTab) => {
    if (newTab && validTabs.includes(newTab)) {
      activeTab.value = newTab;
    }
  }
);

const switchTab = (tabKey) => {
  activeTab.value = tabKey;
  router.replace({ query: { ...route.query, tab: tabKey } });
};

// =========================================================================
// TAB 1: PENGAJUAN KEBUTUHAN BARANG (MATERIAL REQUESTS) STATE & LOGIC
// =========================================================================
const requestsSearchQuery = ref('');
const requestsStatusFilter = ref('');
const requestsProjectFilter = ref('');

const createModalOpen = ref(false);
const detailModalOpen = ref(false);
const selectedRequest = ref(null);

const catalogPickerOpen = ref(false);
const catalogSearch = ref('');
const catalogCategoryFilter = ref('');
const activeItemIndex = ref(null);
const catalogItems = ref([]);

const availableProjects = ref([
  { id: 'PR00001', name: 'pengadaan komputer sekolah (SMA NEGRI 2 BOGOR)' },
  { id: 'PR00002', name: 'Pembangunan Fasilitas Workshop & Lab Vokasi' },
  { id: 'PR00003', name: 'Pekerjaan Pasangan & Struktur Jembatan Kanal' },
  { id: 'PR00004', name: 'Renovasi Gedung Laboratorium Komputer' },
]);

const requestForm = ref({
  project_name: 'pengadaan komputer sekolah (SMA NEGRI 2 BOGOR)',
  requested_by: 'Agus Santoso (Staff Gudang)',
  purpose: 'Kebutuhan pengadaan komputer & notebook lab TIK proyek SMA Negeri 2 Bogor',
  items: [
    {
      sku: 'A00001770',
      name: 'Pemeliharaan Komputer Note Book/ Laptop',
      category: 'Pemeliharaan',
      current_stock: 0,
      qty: 100,
      unit: 'UNIT',
      est_price: 100000,
    },
  ],
});

// Load products seed data on mount
onMounted(async () => {
  try {
    const res = await fetch('/data/products_seed.json');
    if (res.ok) {
      const data = await res.json();
      catalogItems.value = Array.isArray(data) ? data : (data.products || []);
    }
  } catch (e) {
    console.error('Failed to load products seed:', e);
  }
});

const catalogCategories = computed(() => {
  const set = new Set();
  const list = Array.isArray(catalogItems.value) ? catalogItems.value : [];
  list.forEach((p) => {
    if (p.category) set.add(p.category);
  });
  return Array.from(set).sort();
});

const filteredCatalogItems = computed(() => {
  let list = Array.isArray(catalogItems.value) ? catalogItems.value : [];
  if (catalogCategoryFilter.value) {
    list = list.filter((p) => p.category === catalogCategoryFilter.value);
  }
  if (!catalogSearch.value) return list.slice(0, 30);
  const q = catalogSearch.value.toLowerCase();
  return list
    .filter((p) => p.name?.toLowerCase().includes(q) || p.sku?.toLowerCase().includes(q) || p.category?.toLowerCase().includes(q))
    .slice(0, 40);
});

const filteredWarehouseRequests = computed(() => {
  return (store.warehouseRequests || []).filter((req) => {
    const status = req.status;
    if (requestsStatusFilter.value && status !== requestsStatusFilter.value) {
      if (requestsStatusFilter.value === 'MENUNGGU_FINANCE' && (status === 'Draft' || status === 'Menunggu Verifikasi')) {
        // match
      } else {
        return false;
      }
    }
    const proj = req.projectName || req.project_name || '';
    if (requestsProjectFilter.value && proj !== requestsProjectFilter.value) {
      return false;
    }
    if (requestsSearchQuery.value) {
      const q = requestsSearchQuery.value.toLowerCase();
      const code = (req.requestCode || req.request_code || '').toLowerCase();
      const purp = (req.purpose || '').toLowerCase();
      const matchText = `${code} ${proj.toLowerCase()} ${purp}`;
      const matchItem = req.items?.some((i) => i.name?.toLowerCase().includes(q) || i.sku?.toLowerCase().includes(q));
      if (!matchText.includes(q) && !matchItem) return false;
    }
    return true;
  });
});

const pendingRequestsCount = computed(() => {
  return (store.warehouseRequests || []).filter((r) => r.status === 'MENUNGGU_FINANCE' || r.status === 'Draft' || r.status === 'Menunggu Verifikasi').length;
});

const inProgressRequestsCount = computed(() => {
  return (store.warehouseRequests || []).filter((r) => r.status === 'DIPROSES_PEMBELIAN' || r.status === 'Diproses').length;
});

const computedTotalEstCost = computed(() => {
  return requestForm.value.items.reduce((sum, item) => sum + (item.qty || 0) * (item.est_price || 0), 0);
});

const resetRequestsFilters = () => {
  requestsSearchQuery.value = '';
  requestsStatusFilter.value = '';
  requestsProjectFilter.value = '';
};

const handleHeaderCreateRequest = () => {
  activeTab.value = 'requests';
  openCreateRequestModal();
};

const openCreateRequestModal = () => {
  requestForm.value = {
    project_name: 'pengadaan komputer sekolah (SMA NEGRI 2 BOGOR)',
    requested_by: store.currentUser?.name || 'Agus Santoso (Staff Gudang)',
    purpose: 'Kebutuhan pengadaan komputer & notebook lab TIK proyek SMA Negeri 2 Bogor',
    items: [
      {
        sku: 'A00001770',
        name: 'Pemeliharaan Komputer Note Book/ Laptop',
        category: 'Pemeliharaan',
        current_stock: 0,
        qty: 100,
        unit: 'UNIT',
        est_price: 100000,
      },
    ],
  };
  createModalOpen.value = true;
};

const addRequestItem = () => {
  const newIdx = requestForm.value.items.length;
  requestForm.value.items.push({
    sku: '',
    name: '',
    category: '',
    current_stock: 0,
    qty: 1,
    unit: '',
    est_price: 0,
  });
  openCatalogPicker(newIdx);
};

const removeRequestItem = (idx) => {
  requestForm.value.items.splice(idx, 1);
};

const openCatalogPicker = (idx) => {
  activeItemIndex.value = idx;
  catalogSearch.value = '';
  catalogCategoryFilter.value = '';
  catalogPickerOpen.value = true;
};

const selectCatalogItem = (prod) => {
  if (activeItemIndex.value !== null && requestForm.value.items[activeItemIndex.value]) {
    requestForm.value.items[activeItemIndex.value] = {
      sku: prod.sku,
      name: prod.name,
      category: prod.category || 'Umum',
      current_stock: prod.stock ?? 0,
      qty: requestForm.value.items[activeItemIndex.value].qty || 1,
      unit: prod.unit || 'UNIT',
      est_price: prod.last_buy_price || prod.hpp || prod.sell_price || 0,
    };
  }
  catalogPickerOpen.value = false;
};

const saveRequest = async () => {
  const unselected = requestForm.value.items.filter((i) => !i.sku || !i.name);
  if (unselected.length > 0) {
    toast.error('Harap pilih barang resmi dari Data Barang untuk semua baris.');
    return;
  }
  const nextId = (store.warehouseRequests?.length || 0) + 1;
  const requestCode = `REQ-2026-${String(nextId).padStart(3, '0')}`;
  const total = computedTotalEstCost.value;

  const newReq = {
    id: nextId,
    requestCode,
    request_code: requestCode,
    date: new Date().toISOString().split('T')[0],
    request_date: new Date().toISOString().split('T')[0],
    requestedBy: requestForm.value.requested_by,
    requested_by: requestForm.value.requested_by,
    projectName: requestForm.value.project_name,
    project_name: requestForm.value.project_name,
    purpose: requestForm.value.purpose,
    status: 'MENUNGGU_FINANCE',
    items: requestForm.value.items.map((i) => ({
      sku: i.sku || '-',
      name: i.name,
      qty: i.qty,
      unit: i.unit || 'UNIT',
      estPrice: i.est_price,
      est_price: i.est_price,
      subtotal: (i.qty || 0) * (i.est_price || 0),
    })),
    totalEstCost: total,
    total_est_cost: total,
  };

  if (!store.warehouseRequests) store.warehouseRequests = [];
  store.warehouseRequests.unshift(newReq);

  // Send Notifications
  storeActions.addNotification(
    'FINANCE',
    'Pengajuan Kebutuhan Barang (Pengadaan)',
    `Gudang mengajukan pengadaan barang ${requestCode} untuk proyek "${requestForm.value.project_name}" senilai Rp ${Number(total).toLocaleString('id-ID')}. Segera lakukan pemesanan ke Supplier.`,
    'procurement',
    `/finance/purchases/create?request_id=${newReq.id}`
  );

  storeActions.addNotification(
    'MARKETING',
    'Pengadaan Barang Dalam Proses',
    `Kebutuhan pengadaan barang belum tersedia untuk proyek "${requestForm.value.project_name}" (${requestCode}) telah diajukan ke Finance oleh Gudang.`,
    'warehouse',
    '/marketing/orders'
  );

  storeActions.addActivityLog('Pengajuan Kebutuhan Barang', requestCode, `Diajukan ke Finance untuk proyek ${requestForm.value.project_name} senilai Rp ${Number(total).toLocaleString('id-ID')}`);

  try {
    api.post('/warehouse/requests', newReq).catch(() => {});
  } catch (e) {}

  toast.success(`Pengajuan ${requestCode} berhasil dikirim ke Finance.`);
  createModalOpen.value = false;
};

const openDetailRequest = (req) => {
  selectedRequest.value = req;
  detailModalOpen.value = true;
};

// =========================================================================
// TAB 2 & 3: INBOUND SUPPLIER & ORDER FULFILLMENT STATE & LOGIC
// =========================================================================
const trackingSearchQuery = ref('');
const discrepancyModalOpen = ref(false);
const selectedDiscrepancyItem = ref(null);
const discrepancyReason = ref('');

const shipmentDiscrepancyModalOpen = ref(false);
const selectedShipmentForDiscrepancy = ref(null);
const discrepancyMedia = ref([]);
const discrepancyItemsList = ref([]);
const fileInputRef = ref(null);
const mediaPreviewModalOpen = ref(false);
const activePreviewMedia = ref(null);
const trackingModalOpen = ref(false);
const selectedTrackingPurchaseCode = ref('');

const toggleAllDiscrepancyItems = (status) => {
  discrepancyItemsList.value.forEach((item) => {
    item.is_damaged = status;
  });
};

const selectedShipmentForTracking = computed(() => {
  if (!selectedTrackingPurchaseCode.value) return null;
  return (store.purchases || []).find(
    (p) => p.purchase_code === selectedTrackingPurchaseCode.value || p.id === selectedTrackingPurchaseCode.value
  ) || null;
});

const openTrackingModal = (p) => {
  if (!p) return;
  selectedTrackingPurchaseCode.value = p.purchase_code || p.id;
  trackingModalOpen.value = true;
};

const activeIncomingTrackingChips = computed(() => {
  return incomingSupplierShipments.value.filter(
    (p) => p.delivery_info?.tracking_number
  );
});

const handleTrackingSearch = () => {
  const q = (trackingSearchQuery.value || '').trim().toLowerCase();
  if (!q) {
    toast.info('Silakan ketik nomor resi atau nomor surat jalan.');
    return;
  }
  const matched = (store.purchases || []).find((p) => {
    const resi = (p.delivery_info?.tracking_number || '').toLowerCase();
    const sj = (p.delivery_info?.surat_jalan || '').toLowerCase();
    const po = (p.purchase_code || '').toLowerCase();
    return resi.includes(q) || sj.includes(q) || po.includes(q);
  });
  if (matched) {
    openTrackingModal(matched);
    toast.success(`Pengiriman ditemukan: ${matched.delivery_info?.tracking_number || matched.purchase_code} (${matched.default_supplier})`);
  } else {
    toast.error(`Nomor resi/SJ "${trackingSearchQuery.value}" tidak ditemukan pada daftar pengiriman aktif vendor.`);
  }
};

const openMediaPreview = (item) => {
  activePreviewMedia.value = item;
  mediaPreviewModalOpen.value = true;
};

const triggerFileInput = () => {
  if (fileInputRef.value) fileInputRef.value.click();
};

const handleMediaUpload = (e) => {
  const files = Array.from(e.target.files || []);
  if (!files.length) return;

  files.forEach((file) => {
    if (file.size > 25 * 1024 * 1024) {
      toast.error(`File ${file.name} melebihi batas 25MB.`);
      return;
    }
    const isImage = file.type.startsWith('image/');
    const isVideo = file.type.startsWith('video/');
    if (!isImage && !isVideo) {
      toast.error(`Format file ${file.name} tidak didukung. Harap pilih gambar atau video.`);
      return;
    }
    const reader = new FileReader();
    reader.onload = (loadEvent) => {
      let durationStr = '0:24';
      if (isVideo) {
        try {
          const tempVideo = document.createElement('video');
          tempVideo.preload = 'metadata';
          tempVideo.src = loadEvent.target.result;
          tempVideo.onloadedmetadata = () => {
            const sec = Math.round(tempVideo.duration || 24);
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            itemObj.duration = `${m}:${s < 10 ? '0' : ''}${s}`;
          };
        } catch (err) {
          console.debug(err);
        }
      }

      const itemObj = {
        name: file.name,
        type: isImage ? 'image' : 'video',
        url: loadEvent.target.result,
        size: (file.size / (1024 * 1024)).toFixed(2) + ' MB',
        duration: isVideo ? durationStr : null,
      };
      discrepancyMedia.value.push(itemObj);
    };
    reader.readAsDataURL(file);
  });
  e.target.value = '';
};

const removeDiscrepancyMedia = (idx) => {
  discrepancyMedia.value.splice(idx, 1);
};

const receiptModalOpen = ref(false);
const selectedShipmentForReceipt = ref(null);
const receiptNotes = ref('Barang fisik telah diperiksa lengkap, sesuai spesifikasi PO, dan segel baik.');

const incomingSupplierShipments = computed(() => {
  return (store.purchases || []).filter(
    (p) => p.status !== 'DITERIMA_GUDANG' &&
           p.supplier_status !== 'Diterima Gudang (Lengkap & Sesuai)' &&
           p.supplier_status !== 'Diterima Gudang'
  );
});

const getStockProjection = (item) => {
  const rawName = item.name || item.barang || '';
  const cleanName = rawName.replace(/^[A-Z0-9_\-]+\s*-\s*/, '').trim();
  const sku = (item.sku && item.sku !== '-') ? item.sku : (rawName.includes(' - ') ? rawName.split(' - ')[0].trim() : '');
  const qty = Number(item.qty) || 0;

  const prod = (store.products || []).find((pr) => {
    if (sku && pr.sku && pr.sku.toLowerCase() === sku.toLowerCase()) return true;
    if (cleanName && pr.name && pr.name.toLowerCase() === cleanName.toLowerCase()) return true;
    if (rawName && pr.name && pr.name.toLowerCase() === rawName.toLowerCase()) return true;
    if (pr.name && cleanName && pr.name.toLowerCase().includes(cleanName.toLowerCase())) return true;
    return false;
  });

  const currentStock = prod ? Number(prod.stock || 0) : 0;
  const newStock = currentStock + qty;
  return {
    found: !!prod,
    currentStock,
    newStock,
    unit: prod?.unit || item.unit || 'Unit',
  };
};

const isCourierArrived = (p) => {
  if (!p) return false;
  if (p.warehouse_arrived) return true;
  const shippingStatus = String(p.delivery_info?.shipping_status || '').toLowerCase();
  const supplierStatus = String(p.supplier_status || '').toLowerCase();
  return (
    shippingStatus.includes('menuju') ||
    shippingStatus.includes('out for delivery') ||
    shippingStatus.includes('tiba') ||
    shippingStatus.includes('sampai') ||
    supplierStatus.includes('diterima gudang')
  );
};

const isWarehouseArrived = (p) => {
  if (!p) return false;
  return Boolean(p.warehouse_arrived);
};

const handleConfirmPesananTiba = async (p) => {
  try {
    if (!p) return;
    if (!isCourierArrived(p)) {
      toast.warning(
        `Tombol terkunci: Status kurir saat ini masih "${p.delivery_info?.shipping_status || 'Dalam Pengiriman'}". Tombol aktif otomatis setelah status kurir berubah menjadi "Menuju Alamat Gudang (Out for Delivery)".`
      );
      return;
    }
    await storeActions.confirmWarehouseArrival(p.purchase_code);
    trackingModalOpen.value = false;
    toast.success(`Pesanan PO ${p.purchase_code} berhasil dikonfirmasi tiba! Aksi Laporkan Kerusakan dan Konfirmasi Barang sekarang aktif di daftar penerimaan.`);
  } catch (error) { toast.error(orderError(error)); }
};

const openConfirmReceiptModal = (p) => {
  if (!isCourierArrived(p)) {
    toast.warning(
      `Barang belum dapat di-ACC karena status kurir saat ini masih "${p.delivery_info?.shipping_status || 'Dalam Pengiriman'}". Menunggu kurir berstatus "Menuju Alamat Gudang (Out for Delivery)".`
    );
    return;
  }
  selectedShipmentForReceipt.value = p;
  receiptNotes.value = 'Barang fisik telah diperiksa lengkap, sesuai spesifikasi PO, dan segel baik.';
  receiptModalOpen.value = true;
};

const executeConfirmReceipt = async () => {
  try {
    if (!selectedShipmentForReceipt.value) return;
    const p = selectedShipmentForReceipt.value;
    const received = await storeActions.confirmSupplierReceipt(p.purchase_code, { notes: receiptNotes.value });
    if (!received) {
      toast.info('Barang PO ini sudah diterima Gudang.');
      receiptModalOpen.value = false;
      return;
    }
    toast.success(`Barang PO ${p.purchase_code} dari Supplier ${p.default_supplier} BERHASIL DI-ACC! Data stok fisik otomatis bertambah secara real-time.`);
    receiptModalOpen.value = false;
  } catch (error) { toast.error(orderError(error)); }
};

const openShipmentDiscrepancyModal = (p) => {
  selectedShipmentForDiscrepancy.value = p;
  discrepancyMedia.value = [];

  const rawItems = Array.isArray(p.items) ? p.items : [];
  discrepancyItemsList.value = rawItems.map((item, idx) => ({
    id: item.id || idx + 1,
    barang: item.barang || item.name || item.product_name || `Barang #${idx + 1}`,
    sku: item.sku || '-',
    total_qty: Number(item.qty || 1),
    unit: item.unit || 'UNIT',
    is_damaged: rawItems.length === 1,
    damaged_qty: Number(item.qty || 1),
    notes: '',
  }));

  shipmentDiscrepancyModalOpen.value = true;
};

const submitShipmentDiscrepancy = async () => {
  try {
    const selectedDamaged = discrepancyItemsList.value.filter((i) => i.is_damaged);
    if (selectedDamaged.length === 0) {
      toast.error('Harap pilih minimal 1 barang yang rusak/diretur dengan mencentang checkbox.');
      return;
    }

    for (const item of selectedDamaged) {
      if (!item.damaged_qty || item.damaged_qty <= 0) {
        toast.error(`Jumlah rusak untuk "${item.barang}" minimal 1.`);
        return;
      }
      if (item.damaged_qty > item.total_qty) {
        toast.error(`Jumlah rusak untuk "${item.barang}" tidak boleh melebihi total kiriman (${item.total_qty} ${item.unit}).`);
        return;
      }
    }

    const autoReason = selectedDamaged
      .map((i) => `${i.barang} (${i.damaged_qty} ${i.unit})${i.notes ? `: ${i.notes}` : ''}`)
      .join('; ');
    const finalReason = autoReason || 'Terdapat kerusakan fisik pada barang pesanan saat tiba di gudang.';

    const damagedDetails = selectedDamaged.map((i) => ({
      barang: i.barang,
      sku: i.sku,
      total_qty: i.total_qty,
      damaged_qty: i.damaged_qty,
      unit: i.unit,
      notes: i.notes || '',
    }));

    const undamagedDetails = discrepancyItemsList.value
      .filter((i) => !i.is_damaged)
      .map((i) => ({
        barang: i.barang,
        sku: i.sku,
        total_qty: i.total_qty,
        good_qty: i.total_qty,
        unit: i.unit,
      }));

    await storeActions.reportSupplierShipmentDiscrepancy(
      selectedShipmentForDiscrepancy.value.purchase_code,
      finalReason,
      discrepancyMedia.value,
      damagedDetails,
      undamagedDetails
    );
    toast.warning(`Laporan ketidaksesuaian PO ${selectedShipmentForDiscrepancy.value.purchase_code} berhasil dikirim ke Supplier & Finance.`);
    shipmentDiscrepancyModalOpen.value = false;
    discrepancyMedia.value = [];
    discrepancyItemsList.value = [];
  } catch (error) { toast.error(orderError(error)); }
};

const getOrderHold = (orderNumber) => {
  const o = store.orders.find((ord) => ord.orderNumber === orderNumber);
  return o && o.isHold ? o : null;
};

const confirmFulfillment = (ful) => {
  const hold = getOrderHold(ful.orderNumber);
  if (hold) {
    toast.error(`Order ${ful.orderNumber} sedang DITAHAN oleh Direktur: ${hold.holdReason}. Pemenuhan pengiriman ditangguhkan!`);
    return;
  }

  storeActions.confirmFulfillment(ful.id);
  toast.success(`Pemenuhan order ${ful.orderNumber} berhasil dikonfirmasi. Notifikasi diteruskan ke Marketing & Administrasi.`);
};

const openDiscrepancyModal = (ful) => {
  selectedDiscrepancyItem.value = ful;
  discrepancyReason.value = '';
  discrepancyModalOpen.value = true;
};

const submitDiscrepancyReport = () => {
  if (!discrepancyReason.value.trim()) {
    toast.error('Harap isi rincian ketidaksesuaian barang.');
    return;
  }

  storeActions.reportSupplierDiscrepancy(selectedDiscrepancyItem.value.id, discrepancyReason.value.trim());
  toast.warning(`Ketidaksesuaian order ${selectedDiscrepancyItem.value.orderNumber} berhasil dilaporkan ke Finance.`);
  discrepancyModalOpen.value = false;
};

// Tabs configuration
const procurementTabs = computed(() => [
  {
    key: 'requests',
    label: 'Pengajuan Kebutuhan (Material Request)',
    icon: ClipboardList,
    badge: pendingRequestsCount.value || null,
  },
  {
    key: 'inbound',
    label: 'Penerimaan Fisik & Resi Supplier',
    icon: Truck,
    badge: incomingSupplierShipments.value.length || null,
  },
  {
    key: 'fulfillment',
    label: 'Pemenuhan Order Proyek',
    icon: Boxes,
    badge: (store.warehouseFulfillments || []).filter((f) => f.status === 'Diproses').length || null,
  },
]);
</script>
