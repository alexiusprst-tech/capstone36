<template>
  <AppLayout>
    <!-- TOP HEADER -->
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-2.5">
          <div class="p-2 bg-emerald-50 text-emerald-700 rounded-xl">
            <ShoppingBag class="w-6 h-6 stroke-[2.2]" />
          </div>
          <div>
            <h1 class="text-xl sm:text-2xl font-semibold text-slate-900 tracking-tight">
              Transaksi Pembelian (Purchasing)
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
              Pencatatan faktur pembelian barang dari supplier, realisasi pengadaan proyek, dan pemilihan vendor rekanan.
            </p>
          </div>
        </div>

        <!-- Top Right Action: Strictly Locked if no warehouse requests (PROBIS TO-BE) -->
        <div class="flex items-center gap-2">
          <button
            v-if="pendingWarehouseRequests.length === 0"
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-400 font-semibold rounded-lg text-xs border border-slate-300 shadow-2xs transition-colors cursor-not-allowed"
            title="Aktivitas pembelian terkunci karena belum ada pengajuan barang dari Warehouse"
            @click="handleBlockedCreatePurchase"
          >
            <Lock class="w-4 h-4 text-slate-400 stroke-[2.2]" />
            <span>Tambah Transaksi Pembelian (Terkunci)</span>
          </button>

          <button
            v-else
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 active:bg-alpharo-800 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors cursor-pointer"
            @click="handleCreatePurchaseClick"
          >
            <Plus class="w-4 h-4 stroke-[2.5]" />
            <span>Proses Pembelian Pengajuan Gudang ({{ pendingWarehouseRequests.length }})</span>
          </button>
        </div>
      </div>
    </template>

    <!-- SECTION 1: PENGAJUAN BARANG DARI GUDANG (MATERIAL REQUESTS MENUNGGU PEMILIHAN SUPPLIER) -->
    <div class="bg-linear-to-r from-amber-50 via-orange-50 to-amber-50/60 border border-amber-200/90 rounded-xl p-4 sm:p-5 mb-6 shadow-2xs">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3 pb-3 border-b border-amber-200/70">
        <div class="flex items-center gap-2.5">
          <span class="p-1.5 bg-amber-500 text-white rounded-lg">
            <ClipboardList class="w-4 h-4 stroke-[2.5]" />
          </span>
          <div>
            <h3 class="font-semibold text-amber-950 text-xs sm:text-sm">
              Pengajuan Kebutuhan Barang Gudang (Material Request Menunggu Pemilihan Supplier)
            </h3>
            <p class="text-[11px] text-amber-800">
              Tim Gudang telah mengajukan kebutuhan barang. Klik <strong>"Pilih Supplier & Beli"</strong> untuk memilih vendor rekanan dan menerbitkan Purchase Order (PO).
            </p>
          </div>
        </div>
        <span class="px-2.5 py-1 bg-amber-100 text-amber-900 rounded-full text-xs font-semibold shrink-0">
          {{ pendingWarehouseRequests.length }} Pengajuan Menunggu
        </span>
      </div>

      <!-- Requests List Cards -->
      <div v-if="pendingWarehouseRequests.length === 0" class="p-4 bg-white/90 rounded-lg border border-amber-200/90 text-xs text-amber-900 flex items-start gap-3 shadow-2xs">
        <Lock class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
        <div>
          <div class="font-semibold text-slate-800 text-xs">Aktivitas Pembelian Belum Dapat Dilakukan (Terkunci)</div>
          <p class="text-slate-600 mt-0.5 leading-relaxed">
            Sesuai alur operasional (PROBIS TO-BE), bagian Finance hanya memproses pembelian barang setelah menerima <strong>Pengajuan Kebutuhan Barang (Material Request)</strong> dari tim Warehouse. Saat ini belum ada pengajuan baru yang menunggu proses pembelian.
          </p>
        </div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div
          v-for="req in pendingWarehouseRequests"
          :key="req.id"
          class="bg-white p-3.5 rounded-lg border border-amber-200 shadow-2xs hover:border-amber-400 transition-all flex min-w-0 flex-col justify-between gap-3"
        >
          <div>
            <div class="mb-2 flex min-w-0 flex-wrap items-center justify-between gap-2">
              <span class="font-mono font-semibold text-slate-900 text-xs">
                {{ req.requestCode || req.request_code }}
              </span>
              <span class="px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-800 rounded text-[11px] font-semibold">
                Menunggu Pemilihan Supplier
              </span>
            </div>
            <div class="wrap-anywhere text-xs font-semibold leading-relaxed text-slate-800">
              {{ req.projectName || req.project_name || 'Proyek Umum' }}
            </div>
            <div class="mt-2 whitespace-pre-line wrap-anywhere text-xs leading-relaxed text-slate-600">
              {{ req.purpose }}
            </div>

            <!-- Items List -->
            <PurchaseItemList :items="req.items" class="mt-3 border-t border-slate-100 pt-3" />
          </div>

          <!-- Bottom Action Buttons -->
          <div class="mt-1 flex min-w-0 flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
            <span class="text-[11px] font-mono font-semibold text-slate-900">
              Est: Rp {{ Number(req.totalEstCost || req.total_est_cost || 0).toLocaleString('id-ID') }}
            </span>
            <div class="flex min-w-0 flex-wrap items-center gap-2">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-alpharo-600 hover:bg-alpharo-700 active:bg-alpharo-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                title="Buka dialog pemilihan supplier untuk membeli barang ini"
                @click="openSupplierModal(req)"
              >
                <Building class="w-3.5 h-3.5" />
                <span>Pilih Supplier & Beli</span>
              </button>

              <button
                type="button"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                title="Buka di formulir pembelian lengkap"
                @click="processPurchaseFromRequest(req)"
              >
                <Zap class="w-3.5 h-3.5 text-amber-500" />
                <span>Form Lengkap</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FILTER & SEARCH CARD (Green top line accent matching system) -->
    <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden mb-5">
      <div class="h-px bg-slate-200 w-full"></div>
      <div class="p-4 sm:p-5">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
          <!-- Search Bar -->
          <div class="md:col-span-5 relative">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari kode pembelian, no faktur, supplier, atau barang..."
              class="w-full pl-9 pr-8 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-alpharo-400 bg-white text-slate-800 placeholder-slate-400"
            />
            <button
              v-if="searchQuery"
              type="button"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
              @click="searchQuery = ''"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Project Filter -->
          <div class="md:col-span-4">
            <select
              v-model="projectFilter"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-alpharo-400 bg-white text-slate-800 cursor-pointer"
            >
              <option value="">Semua Proyek Pembelian</option>
              <option v-for="proj in availableProjects" :key="proj.id" :value="proj.name">
                {{ proj.name }}
              </option>
            </select>
          </div>

          <!-- Payment Method Filter -->
          <div class="md:col-span-2">
            <select
              v-model="paymentMethodFilter"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-alpharo-400 bg-white text-slate-800 cursor-pointer"
            >
              <option value="">Semua Pembayaran</option>
              <option value="Tunai">Tunai</option>
              <option value="Transfer Bank BCA">Transfer Bank BCA</option>
              <option value="Transfer Bank Mandiri">Transfer Bank Mandiri</option>
              <option value="Tempo">Tempo / Kredit</option>
            </select>
          </div>

          <!-- Reset Filter -->
          <div class="md:col-span-1 flex justify-end">
            <button
              type="button"
              class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-medium border border-slate-300 shadow-2xs transition-colors cursor-pointer"
              title="Reset Filter"
              @click="resetFilters"
            >
              <RotateCcw class="w-3.5 h-3.5 text-slate-500" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Responsive purchase list -->
    <section aria-label="Daftar transaksi pembelian" class="ui-panel mb-6 min-w-0 rounded-xl border border-slate-200/90 bg-white shadow-2xs">
      <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-4 text-xs sm:px-5">
        <h2 class="text-[11px] font-semibold uppercase tracking-wider text-slate-800">Daftar Transaksi Pembelian Supplier</h2>
        <span class="font-mono text-slate-500">{{ filteredPurchases.length }} Transaksi Terdaftar</span>
      </div>

      <div v-if="filteredPurchases.length === 0" class="flex flex-col items-center gap-2 px-4 py-12 text-center text-xs text-slate-500">
        <Package class="h-8 w-8 text-slate-300" />
        <p class="font-semibold text-slate-700">Belum ada transaksi pembelian yang dicatat.</p>
        <p>Klik "Tambah Transaksi Pembelian" atau proses pengajuan dari gudang di atas.</p>
      </div>

      <ol v-else class="min-w-0 divide-y divide-slate-200">
        <li v-for="p in filteredPurchases" :key="p.purchase_code || p.id" class="min-w-0">
          <article :aria-label="'PO ' + p.purchase_code" class="min-w-0 px-4 py-5 sm:px-5">
            <div class="grid min-w-0 gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
              <div class="min-w-0">
                <h3 class="wrap-anywhere font-mono text-xs font-semibold text-slate-900">{{ p.purchase_code }}</h3>
                <dl class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-slate-500">
                  <div class="flex min-w-0 gap-1.5"><dt>Tanggal</dt><dd class="wrap-anywhere font-mono">{{ p.transaction_date }}</dd></div>
                  <div class="flex min-w-0 gap-1.5"><dt>Faktur</dt><dd class="wrap-anywhere font-mono text-slate-700">{{ p.invoice_number || '-' }}</dd></div>
                </dl>
              </div>
              <div class="flex min-w-0 max-w-full flex-wrap items-center gap-3 sm:justify-end">
                <StatusBadge plain :status="p.supplier_status || 'Menunggu Konfirmasi'" class="min-w-0 max-w-[260px] wrap-anywhere" style="white-space: normal" />
                <button type="button" class="shrink-0 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition-colors hover:border-alpharo-300 hover:bg-alpharo-50 hover:text-alpharo-700" @click="openDetail(p)">Detail</button>
              </div>
            </div>

            <div class="mt-4 grid min-w-0 gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)] md:gap-6">
              <div class="min-w-0">
                <p class="mb-1.5 text-[11px] text-slate-500">Proyek &amp; Supplier</p>
                <p class="wrap-anywhere text-xs font-semibold leading-relaxed text-slate-900">{{ p.project_name }}</p>
                <div class="mt-2 flex min-w-0 items-start gap-1.5 text-[11px] font-medium leading-relaxed text-emerald-700">
                  <Truck class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                  <span class="min-w-0 wrap-anywhere">{{ p.default_supplier || p.items?.[0]?.supplier || 'Supplier Umum' }}</span>
                </div>
              </div>
              <div class="min-w-0">
                <p class="mb-1.5 text-[11px] text-slate-500">Rincian Barang</p>
                <PurchaseItemList :items="p.items" />
              </div>
            </div>

            <dl class="mt-4 flex min-w-0 flex-wrap justify-between gap-x-6 gap-y-3 border-t border-slate-100 pt-3 text-xs">
              <div class="min-w-0 max-w-full"><dt class="text-[11px] text-slate-500">Total Pembelian</dt><dd class="mt-1 wrap-anywhere font-mono text-sm font-semibold text-slate-900">Rp {{ Number(p.total_price || p.totalAmount || 0).toLocaleString('id-ID') }}</dd></div>
              <div class="min-w-0 max-w-full"><dt class="text-[11px] text-slate-500">Metode Bayar</dt><dd class="mt-1 wrap-anywhere font-medium text-slate-700">{{ p.payment_method }}</dd></div>
            </dl>
          </article>
        </li>
      </ol>
    </section>

    <!-- MODAL 1: PILIH SUPPLIER & PROSES PO PEMBELIAN (QUICK PURCHASE FROM WAREHOUSE REQUEST) -->
    <Modal
      v-model="supplierModalOpen"
      :title="`Pilih Supplier untuk Pembelian: ${selectedRequest?.requestCode || selectedRequest?.request_code || ''}`"
      :subtitle="`Proyek: ${selectedRequest?.projectName || selectedRequest?.project_name || 'Pengadaan'} • Pemohon: ${selectedRequest?.requestedBy || selectedRequest?.requested_by || 'Gudang'}`"
      size="2xl"
    >
      <div v-if="selectedRequest" class="space-y-5 text-xs">
        <!-- STEP 1: RINGKASAN BARANG YANG DIAJUKAN GUDANG -->
        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200">
          <div class="flex items-center justify-between mb-2">
            <h4 class="font-semibold text-slate-800 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
              <Package class="w-4 h-4 text-emerald-600" />
              <span>1. Rincian Barang Yang Diajukan Gudang</span>
            </h4>
            <span class="text-[11px] text-slate-500 font-medium">
              {{ supplierForm.items.length }} Item Terdaftar
            </span>
          </div>

          <div class="border border-slate-200 rounded-lg overflow-hidden bg-white shadow-2xs">
            <table class="ui-table w-full text-left text-xs">
              <thead class="bg-slate-100/70 border-b border-slate-200 text-slate-600 font-semibold text-[11px]">
                <tr>
                  <th class="p-2.5">Barang</th>
                  <th class="p-2.5 text-center">Jumlah</th>
                  <th class="p-2.5 text-right">Est. Harga Satuan</th>
                  <th class="p-2.5 text-right">Harga Beli Disepakati</th>
                  <th class="p-2.5 text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(it, idx) in supplierForm.items" :key="idx">
                  <td class="p-2.5 font-medium text-slate-800">
                    <div>{{ it.barang }}</div>
                    <div class="font-mono text-[11px] text-slate-400">{{ it.sku }}</div>
                  </td>
                  <td class="p-2.5 text-center whitespace-nowrap font-semibold">
                    {{ it.qty }} {{ it.unit }}
                  </td>
                  <td class="p-2.5 text-right whitespace-nowrap font-mono text-slate-500">
                    Rp {{ Number(it.est_price || 0).toLocaleString('id-ID') }}
                  </td>
                  <td class="p-2.5 text-right whitespace-nowrap">
                    <input
                      v-model.number="it.harga_beli"
                      type="number"
                      min="0"
                      class="w-28 px-2 py-1 text-xs text-right font-mono font-semibold border border-slate-300 rounded focus:ring-1 focus:ring-alpharo-400 bg-white"
                      @input="recalculateModalItem(it)"
                    />
                  </td>
                  <td class="p-2.5 text-right whitespace-nowrap font-mono font-semibold text-slate-900">
                    Rp {{ Number(it.subtotal || 0).toLocaleString('id-ID') }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- STEP 2: PILIH SUPPLIER REKANAN -->
        <div class="bg-emerald-50/50 p-4 rounded-xl border border-emerald-200/90 space-y-3">
          <div class="flex items-center justify-between">
            <h4 class="font-semibold text-emerald-950 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
              <Building class="w-4 h-4 text-emerald-700" />
              <span>2. Pilih Supplier Rekanan untuk Pembelian Barang Ini</span>
            </h4>
            <span class="text-[11px] bg-emerald-100 text-emerald-800 font-semibold px-2 py-0.5 rounded-full">
              Wajib Dipilih
            </span>
          </div>

          <!-- Supplier Select Dropdown -->
          <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <div class="md:col-span-7">
              <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                Pilih Nama Supplier <span class="text-rose-500">*</span>
              </label>
              <select
                v-model="supplierForm.supplier_name"
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white font-medium text-slate-800 focus:ring-2 focus:ring-alpharo-400 focus:outline-hidden cursor-pointer"
                @change="onSupplierDropdownChange"
              >
                <option value="" disabled>-- Pilih Supplier Rekanan --</option>
                <option v-for="sup in suppliersList" :key="sup.code || sup.id" :value="sup.name">
                  {{ sup.name }} ({{ sup.code || 'SUP' }}) - {{ sup.address || '-' }}
                </option>
              </select>
            </div>

            <div class="md:col-span-5">
              <label class="block text-[11px] font-semibold text-slate-700 mb-1">Metode Pembayaran</label>
              <select
                v-model="supplierForm.payment_method"
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white text-slate-800 focus:ring-2 focus:ring-alpharo-400 focus:outline-hidden cursor-pointer"
              >
                <option value="Tunai">Tunai / Cash</option>
                <option value="Transfer Bank BCA">Transfer Bank BCA</option>
                <option value="Transfer Bank Mandiri">Transfer Bank Mandiri</option>
                <option value="Tempo 14 Hari">Tempo 14 Hari (Kredit)</option>
                <option value="Tempo 30 Hari">Tempo 30 Hari (Kredit)</option>
              </select>
            </div>
          </div>

          <!-- SELECTED SUPPLIER PROFILE CARD -->
          <div v-if="selectedSupplierProfile" class="p-3 bg-white border border-emerald-300 rounded-lg shadow-2xs space-y-1.5 text-xs text-slate-700">
            <div class="flex items-center justify-between pb-1 border-b border-slate-100">
              <span class="font-semibold text-slate-900 text-sm flex items-center gap-1.5">
                <Truck class="w-4 h-4 text-emerald-600" />
                <span>{{ selectedSupplierProfile.name }}</span>
              </span>
              <span class="font-mono text-[11px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-semibold">
                {{ selectedSupplierProfile.code }}
              </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px]">
              <div>
                <span class="text-slate-400 block">Kontak PIC / Sales:</span>
                <span class="font-medium text-slate-800">{{ selectedSupplierProfile.contactPerson || '-' }} ({{ selectedSupplierProfile.phone || '-' }})</span>
              </div>
              <div>
                <span class="text-slate-400 block">Email:</span>
                <span class="font-medium text-slate-800">{{ selectedSupplierProfile.email || '-' }}</span>
              </div>
              <div>
                <span class="text-slate-400 block">Rekening Bank Vendor:</span>
                <span class="font-medium text-emerald-800 font-mono">{{ selectedSupplierProfile.bankAccountName || selectedSupplierProfile.bankName ? `${selectedSupplierProfile.bankName} ${selectedSupplierProfile.bankAccountNumber}` : 'BCA 5001239841' }}</span>
              </div>
            </div>
            <div class="text-[11px]">
              <span class="text-slate-400 block">Alamat Gudang/Kantor Vendor:</span>
              <span class="text-slate-700">{{ selectedSupplierProfile.address || '-' }}</span>
            </div>
          </div>
        </div>

        <!-- STEP 3: RINGKASAN FINANSIAL & CATATAN PO -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center bg-slate-50 p-3.5 rounded-xl border border-slate-200">
          <div class="md:col-span-7">
            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
              Catatan Instruksi untuk Supplier / Pengiriman Gudang:
            </label>
            <input
              v-model="supplierForm.notes"
              type="text"
              placeholder="Misal: 'Barang mohon dikirim langsung ke Gudang Utama, sertakan surat jalan resmi'..."
              class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg bg-white text-slate-800 focus:ring-1 focus:ring-alpharo-400"
            />
          </div>

          <div class="md:col-span-5 bg-white p-3 rounded-lg border border-slate-200 text-right space-y-1">
            <div class="text-slate-500 text-[11px] flex justify-between">
              <span>Subtotal Pembelian:</span>
              <span class="font-mono font-medium">Rp {{ Number(modalSubtotal).toLocaleString('id-ID') }}</span>
            </div>
            <div class="text-slate-900 font-semibold text-sm flex justify-between pt-1 border-t border-slate-100">
              <span>Total Nilai PO Supplier:</span>
              <span class="font-mono text-emerald-700">Rp {{ Number(modalSubtotal).toLocaleString('id-ID') }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- MODAL FOOTER ACTIONS -->
      <template #footer>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 w-full">
          <button
            type="button"
            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer"
            @click="supplierModalOpen = false"
          >
            Batal
          </button>

          <div class="flex items-center gap-2">
            <button
              type="button"
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 transition-colors flex items-center gap-1.5 cursor-pointer"
              @click="continueToFullForm"
            >
              <Zap class="w-3.5 h-3.5 text-amber-500" />
              <span>Buka di Formulir Lengkap</span>
            </button>

            <button
              type="button"
              :disabled="!supplierForm.supplier_name"
              class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-semibold rounded-lg shadow-xs transition-colors flex items-center gap-1.5 disabled:opacity-50 cursor-pointer"
              @click="executeDirectPurchase"
            >
              <CheckCircle2 class="w-4 h-4" />
              <span>Terbitkan PO ke Supplier Ini</span>
            </button>
          </div>
        </div>
      </template>
    </Modal>

    <!-- MODAL 2: DETAIL TRANSAKSI PEMBELIAN -->
    <Modal
      v-model="detailModalOpen"
      :title="'Detail Transaksi Pembelian: ' + (selectedPurchase?.purchase_code || '')"
      subtitle="Faktur pembelian barang supplier dan rincian item."
      size="xl"
    >
      <div v-if="selectedPurchase" class="space-y-4 text-xs">
        <div class="grid min-w-0 grid-cols-2 gap-3 border-b border-slate-200 pb-3 sm:grid-cols-4 [&>div]:min-w-0 [&>div]:wrap-anywhere">
          <div>
            <div class="text-[11px] text-slate-400 uppercase font-semibold">No Faktur</div>
            <div class="font-semibold text-slate-900 font-mono mt-0.5">{{ selectedPurchase.invoice_number || '-' }}</div>
          </div>
          <div>
            <div class="text-[11px] text-slate-400 uppercase font-semibold">Tanggal</div>
            <div class="font-semibold text-slate-900 font-mono mt-0.5">{{ selectedPurchase.transaction_date }}</div>
          </div>
          <div>
            <div class="text-[11px] text-slate-400 uppercase font-semibold">Supplier</div>
            <div class="font-semibold text-slate-900 mt-0.5">{{ selectedPurchase.default_supplier || '-' }}</div>
          </div>
          <div>
            <div class="text-[11px] text-slate-400 uppercase font-semibold">Jenis Pembayaran</div>
            <div class="font-semibold text-slate-900 mt-0.5">{{ selectedPurchase.payment_method }}</div>
          </div>
        </div>

        <div>
          <div class="font-semibold text-slate-700 uppercase text-[11px] mb-1">Proyek Terkait</div>
          <div class="wrap-anywhere font-semibold leading-relaxed text-slate-800">
            {{ selectedPurchase.project_name }}
          </div>
        </div>

        <section aria-label="Rincian harga barang" class="min-w-0 border-y border-slate-200">
          <ul class="min-w-0 divide-y divide-slate-100">
            <li v-for="(it, idx) in selectedPurchase.items" :key="idx" class="min-w-0 py-3">
              <p class="wrap-anywhere font-medium leading-relaxed text-slate-900">{{ it.barang || it.name }}</p>
              <dl class="mt-2 grid min-w-0 grid-cols-3 gap-3 text-[11px]">
                <div class="min-w-0"><dt class="text-slate-500">Jumlah</dt><dd class="mt-1 wrap-anywhere font-medium text-slate-800">{{ it.qty }} {{ it.unit || 'UNIT' }}</dd></div>
                <div class="min-w-0"><dt class="text-slate-500">Harga Beli</dt><dd class="mt-1 wrap-anywhere font-mono text-slate-700">Rp {{ Number(it.harga_beli || it.est_price || 0).toLocaleString('id-ID') }}</dd></div>
                <div class="min-w-0"><dt class="text-slate-500">Subtotal</dt><dd class="mt-1 wrap-anywhere font-mono font-semibold text-slate-900">Rp {{ Number(it.subtotal || 0).toLocaleString('id-ID') }}</dd></div>
              </dl>
            </li>
          </ul>
          <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 py-3">
            <span class="text-[11px] font-semibold text-slate-600">Total Harga</span>
            <span class="wrap-anywhere font-mono text-sm font-semibold text-emerald-800">Rp {{ Number(selectedPurchase.total_price || 0).toLocaleString('id-ID') }}</span>
          </div>
        </section>

        <!-- Vendor Fulfillment & Delivery Info (PROBIS TO-BE Tahap 4 & 5) -->
        <div v-if="selectedPurchase.delivery_info || selectedPurchase.supplier_status" class="p-3 bg-alpharo-50/60 border border-alpharo-200 rounded-lg text-xs space-y-1">
          <div class="font-semibold text-alpharo-950 uppercase text-[11px] flex items-center justify-between">
            <span>Status Logistik Vendor / Supplier:</span>
            <span class="font-mono text-alpharo-800 font-semibold bg-white px-2 py-0.5 rounded border border-alpharo-200">
              {{ selectedPurchase.supplier_status || 'Menunggu Konfirmasi' }}
            </span>
          </div>
          <div v-if="selectedPurchase.delivery_info" class="pt-1 text-slate-700">
            <div>Surat Jalan: <span class="font-mono font-semibold text-slate-900">{{ selectedPurchase.delivery_info.surat_jalan }}</span></div>
            <div>Kurir: {{ selectedPurchase.delivery_info.courier }} (Resi/Plat: {{ selectedPurchase.delivery_info.tracking_number }})</div>
            <div>Tgl Kirim: {{ selectedPurchase.delivery_info.dispatch_date }} &bull; Est: {{ selectedPurchase.delivery_info.est_arrival }}</div>
            <div v-if="selectedPurchase.delivery_info.notes" class="text-slate-500 italic mt-0.5">"{{ selectedPurchase.delivery_info.notes }}"</div>
          </div>
          <div v-if="selectedPurchase.discrepancy_reason" class="mt-2 p-2.5 bg-white border border-slate-200 rounded-lg text-xs space-y-1.5 shadow-2xs">
            <div class="font-semibold text-[11px] text-slate-900 uppercase flex items-center justify-between">
              <span class="text-rose-600 flex items-center gap-1">
                <span>⚠️ Laporan Kerusakan Fisik dari Gudang:</span>
              </span>
              <span v-if="selectedPurchase.discrepancy_media?.length" class="text-slate-500 font-mono text-[10px]">
                {{ selectedPurchase.discrepancy_media.length }} Bukti Foto/Video
              </span>
            </div>
            <p class="text-slate-600 italic">"{{ selectedPurchase.discrepancy_reason }}"</p>

            <!-- Rincian Barang yang Diretur & Kondisi Baik -->
            <div v-if="(selectedPurchase.discrepancy_items && selectedPurchase.discrepancy_items.length > 0) || (selectedPurchase.discrepancy_info?.items && selectedPurchase.discrepancy_info.items.length > 0)" class="pt-1.5 border-t border-slate-200">
              <span class="text-[10px] font-bold text-slate-700 block mb-1">Barang yang Diretur (Cacat/Rusak):</span>
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="(di, diIdx) in (selectedPurchase.discrepancy_items || selectedPurchase.discrepancy_info?.items || [])"
                  :key="diIdx"
                  class="px-2 py-0.5 rounded bg-slate-50 border border-slate-200 text-slate-800 font-mono text-[10px] font-semibold"
                >
                  ⚠️ {{ di.barang }} ({{ di.damaged_qty }} {{ di.unit }}){{ di.notes ? ` - ${di.notes}` : '' }}
                </span>
              </div>
            </div>

            <div v-if="(selectedPurchase.undamaged_items && selectedPurchase.undamaged_items.length > 0) || (selectedPurchase.discrepancy_info?.undamaged_items && selectedPurchase.discrepancy_info.undamaged_items.length > 0)" class="pt-1 border-t border-slate-100">
              <span class="text-[10px] font-bold text-emerald-900 block mb-1">Barang Kondisi Baik (Diterima):</span>
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="(ui, uiIdx) in (selectedPurchase.undamaged_items || selectedPurchase.discrepancy_info?.undamaged_items || [])"
                  :key="uiIdx"
                  class="px-2 py-0.5 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono text-[10px]"
                >
                  ✓ {{ ui.barang }} ({{ ui.good_qty || ui.total_qty }} {{ ui.unit }})
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-200">
          <button
            type="button"
            class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold cursor-pointer"
            @click="detailModalOpen = false"
          >
            Tutup
          </button>
        </div>
      </div>
    </Modal>

    <!-- MODAL 3: PILIH PENGAJUAN BARANG GUDANG (MATERIAL REQUEST) UNTUK DIPROSES -->
    <Modal
      v-model="selectRequestModalOpen"
      title="Pilih Pengajuan Kebutuhan Barang Gudang"
      subtitle="Pilih pengajuan dari tim Warehouse untuk diproses menjadi transaksi pembelian (PO)."
      size="lg"
    >
      <div class="space-y-3">
        <div
          v-for="req in pendingWarehouseRequests"
          :key="req.id"
          class="p-3.5 bg-slate-50 hover:bg-emerald-50/50 border border-slate-200 hover:border-emerald-300 rounded-xl transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3"
        >
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="font-mono font-semibold text-slate-900 text-xs bg-white px-2 py-0.5 rounded border border-slate-200">
                {{ req.requestCode || req.request_code }}
              </span>
              <span class="text-[11px] text-slate-500 font-medium">
                {{ req.date || req.request_date }}
              </span>
            </div>
            <div class="font-semibold text-slate-800 text-xs">
              {{ req.projectName || req.project_name || 'Proyek Umum' }}
            </div>
            <div class="text-[11px] text-slate-500">
              {{ req.purpose }}
            </div>
            <div class="text-[11px] text-emerald-700 font-medium">
              {{ req.items?.length || 0 }} jenis barang &bull; Est: Rp {{ Number(req.totalEstCost || req.total_est_cost || 0).toLocaleString('id-ID') }}
            </div>
          </div>

          <button
            type="button"
            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-alpharo-600 hover:bg-alpharo-700 active:bg-alpharo-800 text-white rounded-lg text-xs font-semibold shadow-2xs shrink-0 cursor-pointer"
            @click="processPurchaseFromRequest(req); selectRequestModalOpen = false;"
          >
            <span>Proses Pengajuan Ini</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end">
          <button
            type="button"
            class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold cursor-pointer"
            @click="selectRequestModalOpen = false"
          >
            Tutup
          </button>
        </div>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from '../../navigation';
import AppLayout from '../../components/layout/AppLayout.vue';
import Modal from '../../components/common/Modal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import PurchaseItemList from '../../components/common/PurchaseItemList.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import { orderError } from '../../services/orderWorkflow';
import {
  ShoppingBag,
  Plus,
  Search,
  X,
  RotateCcw,
  ClipboardList,
  Zap,
  Truck,
  Package,
  Building,
  CheckCircle2,
  Lock,
  ArrowRight,
} from 'lucide-vue-next';

const router = useRouter();
const toast = useToast();

const searchQuery = ref('');
const projectFilter = ref('');
const paymentMethodFilter = ref('');

const detailModalOpen = ref(false);
const selectedPurchase = ref(null);

const supplierModalOpen = ref(false);
const selectRequestModalOpen = ref(false);
const selectedRequest = ref(null);
const rawSuppliers = ref([]);

const supplierForm = ref({
  supplier_id: null,
  supplier_name: '',
  payment_method: 'Tunai',
  transaction_date: new Date().toISOString().split('T')[0],
  invoice_number: '',
  notes: '',
  items: [],
});

const availableProjects = ref([
  { id: 'PR00001', name: 'pengadaan komputer sekolah (SMA NEGRI 2 BOGOR)' },
  { id: 'PR00002', name: 'Pembangunan Fasilitas Workshop & Lab Vokasi' },
  { id: 'PR00003', name: 'Pekerjaan Pasangan & Struktur Jembatan Kanal' },
  { id: 'PR00004', name: 'Renovasi Gedung Laboratorium Komputer' },
]);

// Initialize default store purchases if empty
if (!store.purchases) {
  store.purchases = [
    {
      id: 1,
      purchase_code: 'PB20261014114148',
      transaction_date: '2026-10-14',
      invoice_number: 'FAK-SNX-99812',
      project_id: 'PR00001',
      project_name: 'pengadaan komputer sekolah (SMA NEGRI 2 BOGOR)',
      default_supplier: 'PT Synnex Metrodata Indonesia',
      subtotal: 10000000,
      discount: 0,
      total_price: 10000000,
      payment_method: 'Tunai',
      items: [
        {
          barang: 'A00001770 - Pemeliharaan Komputer Note Book/ Laptop',
          sku: 'A00001770',
          supplier: 'PT Synnex Metrodata Indonesia',
          qty: 100,
          harga_beli: 100000,
          diskon_percent: 0,
          diskon_nominal: 0,
          subtotal: 10000000,
        },
      ],
    },
  ];
}

onMounted(async () => {
  try { await storeActions.loadPurchases(); } catch (error) { toast.error(orderError(error)); }

  try {
    const reqRes = await fetch('/api/warehouse/requests');
    if (reqRes.ok) {
      const reqData = await reqRes.json();
      if (Array.isArray(reqData.data)) {
        store.warehouseRequests = reqData.data;
      }
    }
  } catch (e) {}

  try {
    const supRes = await fetch('/api/suppliers');
    if (supRes.ok) {
      const supData = await supRes.json();
      if (supData.data?.length) {
        rawSuppliers.value = supData.data;
      }
    }
  } catch (e) {}
});

// Merged suppliers list (store.suppliers + API)
const suppliersList = computed(() => {
  const list = store.suppliers && store.suppliers.length ? [...store.suppliers] : [];
  if (rawSuppliers.value && rawSuppliers.value.length) {
    rawSuppliers.value.forEach((s) => {
      if (!list.some((existing) => existing.name === s.name || existing.code === s.code)) {
        list.push(s);
      }
    });
  }
  return list;
});

// Profile of the currently selected supplier
const selectedSupplierProfile = computed(() => {
  if (!supplierForm.value.supplier_name) return null;
  return suppliersList.value.find((s) => s.name === supplierForm.value.supplier_name) || null;
});

// Pending requests from warehouse
const pendingWarehouseRequests = computed(() => {
  return (store.warehouseRequests || []).filter(
    (r) => r.status === 'MENUNGGU_FINANCE' || r.status === 'Draft' || r.status === 'Menunggu Verifikasi'
  );
});

const filteredPurchases = computed(() => {
  return (store.purchases || []).filter((p) => {
    if (projectFilter.value && p.project_name !== projectFilter.value) {
      return false;
    }
    if (paymentMethodFilter.value) {
      if (paymentMethodFilter.value === 'Tempo' && !p.payment_method?.includes('Tempo')) {
        return false;
      } else if (paymentMethodFilter.value !== 'Tempo' && p.payment_method !== paymentMethodFilter.value) {
        return false;
      }
    }
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase();
      const code = (p.purchase_code || '').toLowerCase();
      const inv = (p.invoice_number || '').toLowerCase();
      const proj = (p.project_name || '').toLowerCase();
      const sup = (p.default_supplier || '').toLowerCase();
      const hasItem = p.items?.some((i) => (i.barang || i.name || '').toLowerCase().includes(q));
      if (!code.includes(q) && !inv.includes(q) && !proj.includes(q) && !sup.includes(q) && !hasItem) {
        return false;
      }
    }
    return true;
  });
});

const resetFilters = () => {
  searchQuery.value = '';
  projectFilter.value = '';
  paymentMethodFilter.value = '';
};

// Generate PB Purchase Code matching format PB + YYYYMMDDHHmmss
const generatePurchaseCode = () => {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, '0');
  const d = String(now.getDate()).padStart(2, '0');
  const hh = String(now.getHours()).padStart(2, '0');
  const mm = String(now.getMinutes()).padStart(2, '0');
  const ss = String(now.getSeconds()).padStart(2, '0');
  return `PB${y}${m}${d}${hh}${mm}${ss}${crypto.randomUUID().slice(0, 4).toUpperCase()}`;
};

// Open Quick Supplier Selection Modal
const openSupplierModal = (req) => {
  selectedRequest.value = req;
  const defaultSup = suppliersList.value[0] || { name: 'PT Synnex Metrodata Indonesia' };

  supplierForm.value = {
    supplier_id: defaultSup.id || 1,
    supplier_name: defaultSup.name,
    payment_method: 'Tunai',
    transaction_date: new Date().toISOString().split('T')[0],
    invoice_number: `FAK-PO-${Date.now().toString().slice(-6)}`,
    notes: '',
    items: (req.items || []).map((it) => {
      const qty = Number(it.qty) || 1;
      const price = Number(it.estPrice || it.est_price || 0);
      return {
        barang: it.name,
        sku: it.sku || '-',
        qty,
        unit: it.unit || 'UNIT',
        est_price: price,
        harga_beli: price,
        supplier: defaultSup.name,
        subtotal: qty * price,
      };
    }),
  };

  supplierModalOpen.value = true;
};

const onSupplierDropdownChange = () => {
  const chosenSup = supplierForm.value.supplier_name;
  if (chosenSup) {
    supplierForm.value.items.forEach((it) => {
      it.supplier = chosenSup;
    });
  }
};

const recalculateModalItem = (it) => {
  const qty = Number(it.qty) || 0;
  const price = Number(it.harga_beli) || 0;
  it.subtotal = qty * price;
};

const modalSubtotal = computed(() => {
  return supplierForm.value.items.reduce((sum, it) => sum + (Number(it.subtotal) || 0), 0);
});

// Execute PO Direct from Modal
const executeDirectPurchase = async () => {
  try {
    if (!supplierForm.value.supplier_name) {
      toast.error('Harap pilih supplier rekanan terlebih dahulu!');
      return;
    }

    const req = selectedRequest.value;
    const purchaseCode = generatePurchaseCode();
    const subtotal = modalSubtotal.value;

    const payload = {
      id: Date.now(),
      purchase_code: purchaseCode,
      transaction_date: supplierForm.value.transaction_date,
      invoice_number: supplierForm.value.invoice_number,
      project_id: req.projectId || req.project_id || 'PR00001',
      project_name: req.projectName || req.project_name || 'Pengadaan Material Bebas',
      default_supplier: supplierForm.value.supplier_name,
      warehouse_request_id: req.id,
      warehouse_request_code: req.requestCode || req.request_code,
      subtotal,
      discount: 0,
      total_price: subtotal,
      payment_method: supplierForm.value.payment_method,
      items: supplierForm.value.items.map((it) => ({
        barang: it.barang,
        sku: it.sku,
        supplier: it.supplier || supplierForm.value.supplier_name,
        qty: it.qty,
        harga_beli: it.harga_beli,
        diskon_percent: 0,
        diskon_nominal: 0,
        subtotal: it.subtotal,
      })),
      supplier_status: 'Menunggu Konfirmasi',
      notes: supplierForm.value.notes || '',
    };

    await storeActions.createPurchase(payload);

    // 3. Multi-role Notifications (PROBIS TO-BE Tahap 4)
    storeActions.addNotification(
      'WAREHOUSE',
      'Pesanan Barang Supplier Diterbitkan (PO)',
      `Finance telah memesan barang ke Supplier ${payload.default_supplier} (${purchaseCode}) untuk proyek "${payload.project_name}". Mohon bersiap menerima dan memeriksa fisik barang saat tiba.`,
      'purchase',
      '/warehouse/fulfillment'
    );

    storeActions.addNotification(
      'MARKETING',
      'Pemesanan Barang Supplier Berhasil',
      `Barang pengadaan proyek "${payload.project_name}" telah dipesan oleh Finance ke Supplier ${payload.default_supplier} (${purchaseCode}).`,
      'finance',
      '/marketing/orders'
    );

    storeActions.addNotification(
      'SUPPLIER',
      'Pesanan Pembelian Baru (PO) dari CV Alpharo',
      `Finance CV Alpharo Artha Jaya telah menerbitkan pesanan PO ${purchaseCode} untuk proyek "${payload.project_name}". Silakan tinjau dan konfirmasi ketersediaan barang.`,
      'purchase',
      '/supplier/portal'
    );

    storeActions.addActivityLog(
      'Menerbitkan Pembelian Supplier',
      purchaseCode,
      `Supplier: ${payload.default_supplier}, Proyek: ${payload.project_name}, Total: Rp ${subtotal.toLocaleString('id-ID')}`
    );

    toast.success(`Purchase Order ${purchaseCode} berhasil diterbitkan ke Supplier "${payload.default_supplier}"!`);
    supplierModalOpen.value = false;
  } catch (error) { toast.error(orderError(error)); }
};

// Route to full form with pre-filled supplier
const continueToFullForm = () => {
  supplierModalOpen.value = false;
  router.push({
    path: '/finance/purchases/create',
    query: {
      request_id: selectedRequest.value?.id,
      requestCode: selectedRequest.value?.requestCode || selectedRequest.value?.request_code,
      supplier: supplierForm.value.supplier_name,
    },
  });
};

// Action handlers for strictly enforcing warehouse material request prerequisite (PROBIS TO-BE)
const handleBlockedCreatePurchase = () => {
  toast.warning('Aktivitas pembelian terkunci! Pembelian baru bisa dilakukan apabila ada Pengajuan Kebutuhan Barang dari Warehouse.');
};

const handleCreatePurchaseClick = () => {
  if (pendingWarehouseRequests.value.length === 0) {
    handleBlockedCreatePurchase();
    return;
  }
  if (pendingWarehouseRequests.value.length === 1) {
    processPurchaseFromRequest(pendingWarehouseRequests.value[0]);
  } else {
    selectRequestModalOpen.value = true;
  }
};

// 1-click auto-generation from warehouse request to purchase form
const processPurchaseFromRequest = (req) => {
  router.push({
    path: '/finance/purchases/create',
    query: {
      request_id: req.id,
      requestCode: req.requestCode || req.request_code,
    },
  });
};

const openDetail = (p) => {
  selectedPurchase.value = p;
  detailModalOpen.value = true;
};
</script>
