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
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
              Transaksi Pembelian (Purchasing)
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
              Pencatatan faktur pembelian barang dari supplier, realisasi pengadaan proyek, dan pemilihan vendor rekanan.
            </p>
          </div>
        </div>

        <!-- Top Right Action -->
        <router-link
          to="/finance/purchases/create"
          class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors cursor-pointer"
        >
          <Plus class="w-4 h-4 stroke-[2.5]" />
          <span>Tambah Transaksi Pembelian</span>
        </router-link>
      </div>
    </template>

    <!-- SECTION 1: PENGAJUAN BARANG DARI GUDANG (MATERIAL REQUESTS MENUNGGU PEMILIHAN SUPPLIER) -->
    <div class="bg-gradient-to-r from-amber-50 via-orange-50 to-amber-50/60 border border-amber-200/90 rounded-xl p-4 sm:p-5 mb-6 shadow-2xs">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3 pb-3 border-b border-amber-200/70">
        <div class="flex items-center gap-2.5">
          <span class="p-1.5 bg-amber-500 text-white rounded-lg">
            <ClipboardList class="w-4 h-4 stroke-[2.5]" />
          </span>
          <div>
            <h3 class="font-bold text-amber-950 text-xs sm:text-sm">
              Pengajuan Kebutuhan Barang Gudang (Material Request Menunggu Pemilihan Supplier)
            </h3>
            <p class="text-[11px] text-amber-800">
              Tim Gudang telah mengajukan kebutuhan barang. Klik <strong>"Pilih Supplier & Beli"</strong> untuk memilih vendor rekanan dan menerbitkan Purchase Order (PO).
            </p>
          </div>
        </div>
        <span class="px-2.5 py-1 bg-amber-100 text-amber-900 rounded-full text-xs font-bold shrink-0">
          {{ pendingWarehouseRequests.length }} Pengajuan Menunggu
        </span>
      </div>

      <!-- Requests List Cards -->
      <div v-if="pendingWarehouseRequests.length === 0" class="text-xs text-amber-800/80 py-2">
        Tidak ada pengajuan material gudang baru yang menunggu pemilihan supplier.
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div
          v-for="req in pendingWarehouseRequests"
          :key="req.id"
          class="bg-white p-3.5 rounded-lg border border-amber-200 shadow-2xs hover:border-amber-400 transition-all flex flex-col justify-between gap-3"
        >
          <div>
            <div class="flex items-center justify-between mb-1">
              <span class="font-mono font-bold text-slate-900 text-xs">
                {{ req.requestCode || req.request_code }}
              </span>
              <span class="px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-800 rounded text-[10px] font-bold">
                Menunggu Pemilihan Supplier
              </span>
            </div>
            <div class="font-semibold text-slate-800 text-xs line-clamp-1">
              {{ req.projectName || req.project_name || 'Proyek Umum' }}
            </div>
            <div class="text-[11px] text-slate-500 line-clamp-2 mt-0.5">
              {{ req.purpose }}
            </div>

            <!-- Items List -->
            <div class="mt-2 pt-2 border-t border-slate-100 flex flex-wrap gap-1">
              <span
                v-for="(it, idx) in req.items"
                :key="idx"
                class="text-[10px] bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-mono"
              >
                {{ it.name }} ({{ it.qty }} {{ it.unit || 'UNIT' }})
              </span>
            </div>
          </div>

          <!-- Bottom Action Buttons -->
          <div class="flex items-center justify-between pt-2 border-t border-slate-100 mt-1">
            <span class="text-[11px] font-mono font-bold text-slate-900">
              Est: Rp {{ Number(req.totalEstCost || req.total_est_cost || 0).toLocaleString('id-ID') }}
            </span>
            <div class="flex items-center gap-1.5">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg text-xs font-bold shadow-2xs transition-colors cursor-pointer"
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
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden mb-5">
      <div class="h-1 bg-emerald-600 w-full"></div>
      <div class="p-4 sm:p-5">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
          <!-- Search Bar -->
          <div class="md:col-span-5 relative">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari kode pembelian, no faktur, supplier, atau barang..."
              class="w-full pl-9 pr-8 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-emerald-500 bg-white text-slate-800 placeholder-slate-400"
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
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-emerald-500 bg-white text-slate-800 cursor-pointer"
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
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-emerald-500 bg-white text-slate-800 cursor-pointer"
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

    <!-- PURCHASES TABLE CARD -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden mb-6">
      <div class="h-1 bg-emerald-600 w-full"></div>

      <!-- Card Table Header -->
      <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px]">
          Daftar Transaksi Pembelian Supplier
        </span>
        <span class="text-slate-500 font-medium font-mono">
          {{ filteredPurchases.length }} Transaksi Terdaftar
        </span>
      </div>

      <!-- Table Container -->
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-[11px] uppercase tracking-wide">
              <th class="py-3 px-4 whitespace-nowrap">Kode & Tanggal</th>
              <th class="py-3 px-4 whitespace-nowrap">No Faktur</th>
              <th class="py-3 px-4">Proyek & Supplier</th>
              <th class="py-3 px-4">Rincian Barang</th>
              <th class="py-3 px-4 text-right whitespace-nowrap">Total Pembelian</th>
              <th class="py-3 px-4 text-center whitespace-nowrap">Metode Bayar</th>
              <th class="py-3 px-4 text-center whitespace-nowrap">Status Supplier</th>
              <th class="py-3 px-4 text-right whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="filteredPurchases.length === 0">
              <td colspan="8" class="py-12 text-center text-slate-500">
                <div class="flex flex-col items-center justify-center gap-2">
                  <Package class="w-8 h-8 text-slate-300" />
                  <span class="text-xs font-semibold text-slate-700">Belum ada transaksi pembelian yang dicatat.</span>
                  <span class="text-[11px] text-slate-400">Klik "Tambah Transaksi Pembelian" atau proses pengajuan dari gudang di atas.</span>
                </div>
              </td>
            </tr>

            <tr
              v-for="p in filteredPurchases"
              :key="p.purchase_code || p.id"
              class="hover:bg-slate-50/70 transition-colors"
            >
              <!-- Kode & Tanggal -->
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="font-mono font-bold text-slate-900 block">{{ p.purchase_code }}</span>
                <span class="text-[11px] text-slate-500 font-mono">{{ p.transaction_date }}</span>
              </td>

              <!-- No Faktur -->
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="font-mono text-slate-800 font-semibold bg-slate-100 px-2 py-0.5 rounded">
                  {{ p.invoice_number || '-' }}
                </span>
              </td>

              <!-- Proyek & Supplier -->
              <td class="py-3 px-4 max-w-xs">
                <div class="font-semibold text-slate-900 leading-snug">
                  {{ p.project_name }}
                </div>
                <div class="text-[11px] text-emerald-700 font-medium mt-0.5 flex items-center gap-1">
                  <Truck class="w-3 h-3" />
                  <span>{{ p.default_supplier || p.items?.[0]?.supplier || 'Supplier Umum' }}</span>
                </div>
              </td>

              <!-- Rincian Barang -->
              <td class="py-3 px-4 max-w-sm">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="(it, idx) in p.items"
                    :key="idx"
                    class="inline-flex items-center gap-1 text-[11px] bg-slate-100 border border-slate-200 text-slate-800 px-2 py-0.5 rounded"
                  >
                    <span>{{ it.barang || it.name }}</span>
                    <span class="font-bold font-mono text-emerald-700">({{ it.qty }})</span>
                  </span>
                </div>
              </td>

              <!-- Total Pembelian -->
              <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                Rp {{ Number(p.total_price || p.totalAmount || 0).toLocaleString('id-ID') }}
              </td>

              <!-- Metode Bayar -->
              <td class="py-3 px-4 text-center whitespace-nowrap">
                <span class="px-2 py-0.5 bg-slate-100 border border-slate-200 text-slate-700 rounded font-semibold text-[11px]">
                  {{ p.payment_method }}
                </span>
              </td>

              <!-- Status Supplier & Pembayaran -->
              <td class="py-3 px-4 text-center whitespace-nowrap">
                <span
                  v-if="p.supplier_status === 'Menunggu Konfirmasi' || !p.supplier_status"
                  class="px-2.5 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 rounded-full font-bold text-[10px] block"
                >
                  ⏳ Menunggu Konfirmasi Vendor
                </span>
                <span
                  v-else-if="p.supplier_status === 'Dikonfirmasi Supplier' || p.supplier_status === 'Sedang Disiapkan'"
                  class="px-2.5 py-0.5 bg-blue-50 text-blue-800 border border-blue-200 rounded-full font-bold text-[10px] block"
                >
                  📦 Diproses Vendor
                </span>
                <span
                  v-else-if="p.supplier_status === 'Dikirim ke Gudang CV'"
                  class="px-2.5 py-0.5 bg-indigo-50 text-indigo-800 border border-indigo-200 rounded-full font-bold text-[10px] block"
                  :title="p.delivery_info?.surat_jalan ? 'No. SJ: ' + p.delivery_info.surat_jalan : ''"
                >
                  🚚 Vendor Mengirim (SJ)
                </span>
                <span
                  v-else-if="p.supplier_status === 'Diterima Gudang'"
                  class="px-2.5 py-0.5 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full font-bold text-[10px] block"
                >
                  ✓ Diterima Gudang
                </span>
                <span
                  v-else
                  class="px-2.5 py-0.5 bg-slate-100 text-slate-800 border border-slate-200 rounded-full font-bold text-[10px] block"
                >
                  {{ p.supplier_status }}
                </span>
                <span class="text-[9px] text-emerald-700 font-semibold block mt-0.5">
                  ● Lunas
                </span>
              </td>

              <!-- Aksi -->
              <td class="py-3 px-4 text-right whitespace-nowrap space-x-1.5">
                <button
                  type="button"
                  class="px-2.5 py-1 text-slate-700 hover:text-emerald-700 hover:bg-emerald-50 border border-slate-300 hover:border-emerald-300 rounded text-xs font-semibold transition-colors cursor-pointer"
                  @click="openDetail(p)"
                >
                  Detail
                </button>
                <button
                  v-if="p.status !== 'DITERIMA_GUDANG' && p.supplier_status !== 'Diterima Gudang (Lengkap & Sesuai)' && p.supplier_status !== 'Diterima Gudang'"
                  type="button"
                  class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-bold transition-colors cursor-pointer inline-flex items-center gap-1 shadow-2xs"
                  title="Konfirmasi fisik barang telah tiba di gudang dan update stok barang secara real-time"
                  @click="quickConfirmReceipt(p)"
                >
                  <Check class="w-3 h-3 stroke-[3]" />
                  <span>ACC Gudang</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

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
            <h4 class="font-bold text-slate-800 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
              <Package class="w-4 h-4 text-emerald-600" />
              <span>1. Rincian Barang Yang Diajukan Gudang</span>
            </h4>
            <span class="text-[11px] text-slate-500 font-medium">
              {{ supplierForm.items.length }} Item Terdaftar
            </span>
          </div>

          <div class="border border-slate-200 rounded-lg overflow-hidden bg-white shadow-2xs">
            <table class="w-full text-left text-xs">
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
                    <div class="font-mono text-[10px] text-slate-400">{{ it.sku }}</div>
                  </td>
                  <td class="p-2.5 text-center whitespace-nowrap font-bold">
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
                      class="w-28 px-2 py-1 text-xs text-right font-mono font-bold border border-slate-300 rounded focus:ring-1 focus:ring-emerald-500 bg-white"
                      @input="recalculateModalItem(it)"
                    />
                  </td>
                  <td class="p-2.5 text-right whitespace-nowrap font-mono font-bold text-slate-900">
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
            <h4 class="font-bold text-emerald-950 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
              <Building class="w-4 h-4 text-emerald-700" />
              <span>2. Pilih Supplier Rekanan untuk Pembelian Barang Ini</span>
            </h4>
            <span class="text-[10px] bg-emerald-100 text-emerald-800 font-semibold px-2 py-0.5 rounded-full">
              Wajib Dipilih
            </span>
          </div>

          <!-- Supplier Select Dropdown -->
          <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <div class="md:col-span-7">
              <label class="block text-[11px] font-bold text-slate-700 mb-1">
                Pilih Nama Supplier <span class="text-rose-500">*</span>
              </label>
              <select
                v-model="supplierForm.supplier_name"
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white font-medium text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden cursor-pointer"
                @change="onSupplierDropdownChange"
              >
                <option value="" disabled>-- Pilih Supplier Rekanan --</option>
                <option v-for="sup in suppliersList" :key="sup.code || sup.id" :value="sup.name">
                  {{ sup.name }} ({{ sup.code || 'SUP' }}) - {{ sup.address || '-' }}
                </option>
              </select>
            </div>

            <div class="md:col-span-5">
              <label class="block text-[11px] font-bold text-slate-700 mb-1">Metode Pembayaran</label>
              <select
                v-model="supplierForm.payment_method"
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden cursor-pointer"
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
              <span class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                <Truck class="w-4 h-4 text-emerald-600" />
                <span>{{ selectedSupplierProfile.name }}</span>
              </span>
              <span class="font-mono text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-bold">
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
            <label class="block text-[11px] font-bold text-slate-700 mb-1">
              Catatan Instruksi untuk Supplier / Pengiriman Gudang:
            </label>
            <input
              v-model="supplierForm.notes"
              type="text"
              placeholder="Misal: 'Barang mohon dikirim langsung ke Gudang Utama, sertakan surat jalan resmi'..."
              class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg bg-white text-slate-800 focus:ring-1 focus:ring-emerald-500"
            />
          </div>

          <div class="md:col-span-5 bg-white p-3 rounded-lg border border-slate-200 text-right space-y-1">
            <div class="text-slate-500 text-[11px] flex justify-between">
              <span>Subtotal Pembelian:</span>
              <span class="font-mono font-medium">Rp {{ Number(modalSubtotal).toLocaleString('id-ID') }}</span>
            </div>
            <div class="text-slate-900 font-bold text-sm flex justify-between pt-1 border-t border-slate-100">
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
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg border border-slate-300 transition-colors flex items-center gap-1.5 cursor-pointer"
              @click="continueToFullForm"
            >
              <Zap class="w-3.5 h-3.5 text-amber-500" />
              <span>Buka di Formulir Lengkap</span>
            </button>

            <button
              type="button"
              :disabled="!supplierForm.supplier_name"
              class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold rounded-lg shadow-xs transition-colors flex items-center gap-1.5 disabled:opacity-50 cursor-pointer"
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
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
          <div>
            <div class="text-[10px] text-slate-400 uppercase font-semibold">No Faktur</div>
            <div class="font-bold text-slate-900 font-mono mt-0.5">{{ selectedPurchase.invoice_number || '-' }}</div>
          </div>
          <div>
            <div class="text-[10px] text-slate-400 uppercase font-semibold">Tanggal</div>
            <div class="font-bold text-slate-900 font-mono mt-0.5">{{ selectedPurchase.transaction_date }}</div>
          </div>
          <div>
            <div class="text-[10px] text-slate-400 uppercase font-semibold">Supplier</div>
            <div class="font-bold text-slate-900 mt-0.5">{{ selectedPurchase.default_supplier || '-' }}</div>
          </div>
          <div>
            <div class="text-[10px] text-slate-400 uppercase font-semibold">Jenis Pembayaran</div>
            <div class="font-bold text-slate-900 mt-0.5">{{ selectedPurchase.payment_method }}</div>
          </div>
        </div>

        <div>
          <div class="font-bold text-slate-700 uppercase text-[11px] mb-1">Proyek Terkait</div>
          <div class="p-2.5 bg-white border border-slate-200 rounded-lg text-slate-800 font-semibold">
            {{ selectedPurchase.project_name }}
          </div>
        </div>

        <!-- Items Table -->
        <div class="border border-slate-200 rounded-lg overflow-hidden">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-[11px]">
              <tr>
                <th class="py-2.5 px-3">Nama Barang</th>
                <th class="py-2.5 px-3 text-center">Jumlah</th>
                <th class="py-2.5 px-3 text-right">Harga Beli</th>
                <th class="py-2.5 px-3 text-right">Subtotal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(it, idx) in selectedPurchase.items" :key="idx">
                <td class="py-2.5 px-3 font-medium text-slate-900">{{ it.barang || it.name }}</td>
                <td class="py-2.5 px-3 text-center font-bold text-slate-800">{{ it.qty }}</td>
                <td class="py-2.5 px-3 text-right font-mono text-slate-700">
                  Rp {{ Number(it.harga_beli || it.est_price || 0).toLocaleString('id-ID') }}
                </td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">
                  Rp {{ Number(it.subtotal || 0).toLocaleString('id-ID') }}
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-slate-50 border-t border-slate-200 font-bold">
              <tr>
                <td colspan="3" class="py-2.5 px-3 text-right uppercase text-[11px] text-slate-600">Total Harga:</td>
                <td class="py-2.5 px-3 text-right font-mono text-emerald-800 text-sm">
                  Rp {{ Number(selectedPurchase.total_price || 0).toLocaleString('id-ID') }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Vendor Fulfillment & Delivery Info (PROBIS TO-BE Tahap 4 & 5) -->
        <div v-if="selectedPurchase.delivery_info || selectedPurchase.supplier_status" class="p-3 bg-indigo-50/60 border border-indigo-200 rounded-lg text-xs space-y-1">
          <div class="font-bold text-indigo-950 uppercase text-[10px] flex items-center justify-between">
            <span>Status Logistik Vendor / Supplier:</span>
            <span class="font-mono text-indigo-800 font-bold bg-white px-2 py-0.5 rounded border border-indigo-200">
              {{ selectedPurchase.supplier_status || 'Menunggu Konfirmasi' }}
            </span>
          </div>
          <div v-if="selectedPurchase.delivery_info" class="pt-1 text-slate-700">
            <div>Surat Jalan: <span class="font-mono font-bold text-slate-900">{{ selectedPurchase.delivery_info.surat_jalan }}</span></div>
            <div>Kurir: {{ selectedPurchase.delivery_info.courier }} (Resi/Plat: {{ selectedPurchase.delivery_info.tracking_number }})</div>
            <div>Tgl Kirim: {{ selectedPurchase.delivery_info.dispatch_date }} &bull; Est: {{ selectedPurchase.delivery_info.est_arrival }}</div>
            <div v-if="selectedPurchase.delivery_info.notes" class="text-slate-500 italic mt-0.5">"{{ selectedPurchase.delivery_info.notes }}"</div>
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
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AppLayout from '../../components/layout/AppLayout.vue';
import Modal from '../../components/common/Modal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
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
} from 'lucide-vue-next';

const router = useRouter();
const toast = useToast();

const searchQuery = ref('');
const projectFilter = ref('');
const paymentMethodFilter = ref('');

const detailModalOpen = ref(false);
const selectedPurchase = ref(null);

const supplierModalOpen = ref(false);
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
  try {
    const res = await fetch('/api/purchases');
    if (res.ok) {
      const data = await res.json();
      if (data.data?.length) {
        store.purchases = data.data;
      }
    }
  } catch (e) {}

  try {
    const reqRes = await fetch('/api/warehouse/requests');
    if (reqRes.ok) {
      const reqData = await reqRes.json();
      if (reqData.data?.length) {
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
  return `PB${y}${m}${d}${hh}${mm}${ss}`;
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

  // 1. Add to store.purchases
  if (!store.purchases) store.purchases = [];
  store.purchases.unshift(payload);

  // 2. Update Warehouse Request Status
  req.status = 'DIPROSES_PEMBELIAN';
  req.purchaseCode = purchaseCode;
  req.purchase_code = purchaseCode;

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

  // 4. Sync to backend API
  try {
    fetch('/api/purchases', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    }).catch(() => {});
  } catch (e) {}

  toast.success(`Purchase Order ${purchaseCode} berhasil diterbitkan ke Supplier "${payload.default_supplier}"!`);
  supplierModalOpen.value = false;
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

const quickConfirmReceipt = (p) => {
  storeActions.confirmSupplierReceipt(p.purchase_code || p.id, {
    notes: 'Barang fisik diterima lengkap dan di-ACC via Finance / Gudang.',
  });
  toast.success(
    `Barang PO ${p.purchase_code} berhasil di-ACC! Data stok fisik di gudang otomatis bertambah secara real-time.`
  );
};
</script>
