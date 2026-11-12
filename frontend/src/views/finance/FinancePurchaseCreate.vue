<template>
  <AppLayout>
    <!-- TOP HEADER -->
    <template #header>
      <div class="flex items-center justify-between gap-4">
        <!-- Title with Shopping Bag Icon matching user image -->
        <div class="flex items-center gap-2">
          <div class="p-1.5 text-slate-800">
            <ShoppingBag class="w-6 h-6 stroke-[2.2]" />
          </div>
          <h1 class="text-xl sm:text-2xl font-semibold text-slate-900 tracking-tight">
            Tambah Transaksi Pembelian
          </h1>
        </div>

        <!-- Kembali Button matching user image (top right) -->
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-semibold border border-slate-300 shadow-2xs transition-colors cursor-pointer"
          @click="goBack"
        >
          <RotateCcw class="w-3.5 h-3.5 text-slate-500" />
          <span>Kembali</span>
        </button>
      </div>
    </template>

    <!-- 1. BLOCKED / LOCKED STATE: NO PENDING WAREHOUSE REQUEST (PROBIS TO-BE) -->
    <div
      v-if="!sourceRequest && pendingWarehouseRequests.length === 0"
      class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden mb-8"
    >
      <div class="h-px bg-slate-200 w-full"></div>
      <div class="p-6 sm:p-10 text-center max-w-2xl mx-auto space-y-4">
        <div class="w-16 h-16 bg-amber-50 border border-amber-200 rounded-2xl flex items-center justify-center mx-auto text-amber-600 shadow-xs">
          <Lock class="w-8 h-8 stroke-[2.2]" />
        </div>

        <div class="space-y-1.5">
          <h2 class="text-lg sm:text-xl font-semibold text-slate-900">
            Aktivitas Pembelian Terkunci
          </h2>
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
            Sesuai Standar Operasional Prosedur (PROBIS TO-BE), bagian <span class="font-semibold text-slate-800">Finance</span> baru dapat melakukan transaksi pembelian setelah ada <span class="font-semibold text-amber-900 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">Pengajuan Kebutuhan Barang (Material Request)</span> yang diajukan oleh tim <span class="font-semibold text-slate-800">Warehouse</span>.
          </p>
        </div>

        <!-- Flow explanation -->
        <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-xl text-left space-y-2 text-xs">
          <div class="font-semibold text-slate-800 flex items-center gap-1.5 text-[11px] uppercase tracking-wider">
            <ClipboardList class="w-4 h-4 text-emerald-600" />
            <span>Alur Operasional Pengadaan Sesuai SOP:</span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-1 text-[11px]">
            <div class="p-2.5 bg-white rounded-lg border border-slate-200">
              <span class="font-semibold text-slate-800 block mb-0.5">1. Warehouse</span>
              <span class="text-slate-500">Mendata kekurangan stok & menerbitkan Pengajuan Barang.</span>
            </div>
            <div class="p-2.5 bg-amber-50 rounded-lg border border-amber-300">
              <span class="font-semibold text-amber-900 block mb-0.5">2. Finance (Saat Ini)</span>
              <span class="text-amber-800 font-medium">Menunggu pengajuan dari Warehouse sebelum membuat PO.</span>
            </div>
            <div class="p-2.5 bg-white rounded-lg border border-slate-200">
              <span class="font-semibold text-slate-800 block mb-0.5">3. Supplier</span>
              <span class="text-slate-500">Menerima PO & memproses pengiriman ke gudang.</span>
            </div>
          </div>
        </div>

        <!-- Action buttons -->
        <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
          <button
            type="button"
            class="w-full sm:w-auto px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs border border-slate-300 transition-colors flex items-center justify-center gap-2 cursor-pointer"
            @click="goBack"
          >
            <RotateCcw class="w-4 h-4 text-slate-500" />
            <span>Kembali ke Transaksi Pembelian</span>
          </button>

          <Link
            href="/warehouse/fulfillment?tab=requests"
            class="w-full sm:w-auto px-5 py-2.5 bg-alpharo-600 hover:bg-alpharo-700 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors flex items-center justify-center gap-2 cursor-pointer"
          >
            <ClipboardList class="w-4 h-4" />
            <span>Buka Modul Pengajuan Warehouse</span>
          </Link>
        </div>
      </div>
    </div>

    <!-- 2. SELECTOR GATE: PENDING WAREHOUSE REQUESTS AVAILABLE TO CHOOSE -->
    <div
      v-if="!sourceRequest && pendingWarehouseRequests.length > 0"
      class="space-y-4 mb-8"
    >
      <div class="bg-linear-to-r from-emerald-50 via-teal-50 to-emerald-50/60 border border-emerald-200 rounded-xl p-4 sm:p-5 shadow-2xs">
        <div class="flex items-center gap-2.5 mb-1">
          <div class="p-2 bg-emerald-600 text-white rounded-lg shadow-2xs">
            <ClipboardList class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <h2 class="font-semibold text-slate-900 text-sm sm:text-base">
              Pilih Pengajuan Kebutuhan Barang dari Warehouse
            </h2>
            <p class="text-xs text-slate-600">
              Aktivitas pembelian harus didasarkan pada Pengajuan Kebutuhan Barang Gudang. Pilih salah satu pengajuan di bawah ini untuk diproses:
            </p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="req in pendingWarehouseRequests"
          :key="req.id"
          class="ui-panel bg-white border border-slate-200 hover:border-emerald-500 rounded-xl p-4 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between gap-4"
        >
          <div class="space-y-2.5">
            <div class="flex items-center justify-between">
              <span class="font-mono font-semibold text-slate-900 text-xs px-2.5 py-1 bg-slate-100 rounded border border-slate-300">
                {{ req.requestCode || req.request_code }}
              </span>
              <span class="px-2 py-0.5 bg-amber-50 text-amber-800 text-[11px] font-semibold rounded border border-amber-200">
                Menunggu Pembelian
              </span>
            </div>

            <div>
              <div class="font-semibold text-slate-900 text-sm">
                {{ req.projectName || req.project_name || 'Proyek Umum' }}
              </div>
              <div class="text-xs text-slate-600 mt-0.5">
                {{ req.purpose }}
              </div>
              <div class="text-[11px] text-slate-400 mt-1">
                Diajukan oleh: <span class="text-slate-600 font-medium">{{ req.requestedBy || req.requested_by }}</span> &bull; {{ req.date || req.request_date }}
              </div>
            </div>

            <!-- Items list -->
            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-100 space-y-1">
              <div class="text-[11px] uppercase font-semibold text-slate-500 tracking-wider">
                Item yang Diajukan ({{ req.items?.length || 0 }} Barang):
              </div>
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="(it, idx) in req.items"
                  :key="idx"
                  class="text-[11px] bg-white border border-slate-200 text-slate-700 px-2 py-0.5 rounded font-medium"
                >
                  {{ it.name }} &times; {{ it.qty }} {{ it.unit || 'UNIT' }}
                </span>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <div>
              <span class="text-[11px] text-slate-400 uppercase font-semibold block">Est. Biaya Total</span>
              <span class="font-mono font-semibold text-slate-900 text-sm">
                Rp {{ Number(req.totalEstCost || req.total_est_cost || 0).toLocaleString('id-ID') }}
              </span>
            </div>

            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 active:bg-alpharo-800 text-white font-semibold rounded-lg text-xs shadow-2xs transition-colors cursor-pointer"
              @click="loadFromWarehouseRequest(req.id)"
            >
              <span>Pilih & Proses Pengajuan Ini</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. AUTO-GENERATE NOTIFICATION BANNER (When generated from Warehouse Request) -->
    <div
      v-if="sourceRequest"
      class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl space-y-3 shadow-2xs overflow-hidden"
    >
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-2.5 text-xs text-emerald-950">
        <div class="flex items-center gap-2.5 min-w-0">
          <span class="p-1.5 bg-emerald-600 text-white rounded-lg shrink-0 shadow-2xs">
            <Check class="w-4 h-4 stroke-[3]" />
          </span>
          <div class="min-w-0">
            <span class="font-semibold">Pengajuan Kebutuhan Barang dari Gudang: </span>
            <span class="font-mono font-semibold bg-emerald-100 text-emerald-900 px-2 py-0.5 rounded border border-emerald-300 inline-block">
              {{ sourceRequest.requestCode || sourceRequest.request_code }}
            </span>
            <span class="text-emerald-800 ml-1.5 font-medium truncate block sm:inline">({{ sourceRequest.purpose || sourceRequest.projectName }})</span>
          </div>
        </div>
        <div class="flex items-center gap-2 shrink-0 self-start md:self-auto">
          <span class="text-[11px] font-semibold text-emerald-800 bg-emerald-100 px-3 py-1 rounded-full border border-emerald-200 shadow-2xs">
            Auto-Sync Material Request Gudang
          </span>
          <button
            v-if="pendingWarehouseRequests.length > 1"
            type="button"
            class="text-[11px] font-semibold text-slate-700 bg-white hover:bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-300 shadow-2xs transition-colors cursor-pointer"
            @click="resetSelectedRequest"
          >
            Ganti Pengajuan
          </button>
        </div>
      </div>

      <!-- Quick Supplier Selection Bar (Responsive, Never Overflows) -->
      <div class="pt-3 border-t border-emerald-200/80 flex flex-col lg:flex-row lg:items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2 text-slate-800 font-semibold shrink-0">
          <div class="w-6 h-6 rounded-md bg-emerald-100 border border-emerald-300 flex items-center justify-center text-emerald-800 shrink-0">
            <Building class="w-3.5 h-3.5" />
          </div>
          <span>Pilih Supplier Rekanan untuk Membeli Barang Ini:</span>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-1 lg:max-w-xl min-w-0">
          <div class="relative flex-1 min-w-0">
            <select
              v-model="form.default_supplier"
              class="w-full min-w-0 px-3 py-2 text-xs border border-emerald-300 rounded-lg bg-white font-medium text-slate-800 focus:ring-2 focus:ring-alpharo-400 focus:border-alpharo-400 cursor-pointer shadow-2xs truncate"
              @change="applySupplierToAllRows"
            >
              <option value="" disabled>-- Pilih Supplier Rekanan --</option>
              <option v-for="sup in suppliersList" :key="sup.code || sup.name" :value="sup.name">
                {{ sup.name }} ({{ sup.code || 'SUP' }})
              </option>
            </select>
          </div>
          <button
            type="button"
            class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold rounded-lg shadow-2xs transition-colors shrink-0 cursor-pointer text-xs whitespace-nowrap"
            @click="applySupplierToAllRows"
          >
            <Check class="w-3.5 h-3.5" />
            <span>Terapkan ke Seluruh Baris</span>
          </button>
        </div>
      </div>
    </div>

    <!-- 4. MAIN FORM CARD WITH GREEN ACCENT LINE MATCHING USER IMAGE -->
    <div v-if="sourceRequest" class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden mb-8">
      <!-- Top green accent border matching user screenshot -->
      <div class="h-px bg-slate-200 w-full"></div>

      <form class="p-4 sm:p-6 space-y-6" @submit.prevent="savePurchase">
        <!-- ======================================================== -->
        <!-- SECTION 1: HEADER PEMBELIAN                               -->
        <!-- ======================================================== -->
        <div class="space-y-3">
          <!-- Section Title with [i] icon and dark blue underline -->
          <div class="flex items-center gap-2 pb-1.5 border-b-2 border-[#1e3a8a]">
            <div class="w-4 h-4 border border-slate-400 rounded-xs flex items-center justify-center text-[11px] font-semibold text-slate-600">
              i
            </div>
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900">Header Pembelian</h2>
          </div>

          <!-- 5 Fields Grid matching user screenshot -->
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3 pt-1">
            <!-- Kode Pembelian (ReadOnly e.g. PB20261014114148) -->
            <div class="md:col-span-2">
              <label class="block text-[11px] font-semibold text-slate-700 mb-1">Kode Pembelian</label>
              <input
                v-model="form.purchase_code"
                type="text"
                readonly
                class="w-full px-3 py-2 text-xs border border-slate-200 rounded-lg bg-slate-100 text-slate-700 font-mono font-semibold select-all cursor-not-allowed"
              />
            </div>

            <!-- Tanggal * -->
            <div class="md:col-span-2 relative">
              <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                Tanggal <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <input
                  v-model="form.transaction_date"
                  type="date"
                  required
                  class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-[#0284c7] bg-white text-slate-800"
                />
              </div>
            </div>

            <!-- No Faktur -->
            <div class="md:col-span-2">
              <label class="block text-[11px] font-semibold text-slate-700 mb-1">No Faktur</label>
              <input
                v-model="form.invoice_number"
                type="text"
                placeholder="No Faktur"
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-[#0284c7] bg-white text-slate-800"
              />
            </div>

            <!-- Project * (Dropdown) -->
            <div class="md:col-span-3">
              <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                Project <span class="text-rose-500">*</span>
              </label>
              <select
                v-model="form.project_name"
                required
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-[#0284c7] bg-white text-slate-800 cursor-pointer"
                @change="onProjectChange"
              >
                <option v-for="p in availableProjects" :key="p.id" :value="p.name">
                  {{ p.name }}
                </option>
              </select>
            </div>

            <!-- Supplier Default (Input with [Q] [+] [X] buttons) -->
            <div class="md:col-span-3">
              <label class="block text-[11px] font-semibold text-slate-700 mb-1">Supplier Default</label>
              <div class="flex items-center gap-1">
                <input
                  v-model="form.default_supplier"
                  type="text"
                  placeholder="Isi baris kosong"
                  class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-[#0284c7] bg-white text-slate-800"
                />
                <!-- Search Button [Q] (Blue Gradient) -->
                <button
                  type="button"
                  class="w-8 h-8 inline-flex items-center justify-center bg-linear-to-b from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700 active:from-sky-700 active:to-sky-800 text-white rounded-lg shadow-2xs border border-sky-600/30 shrink-0 cursor-pointer transition-all"
                  title="Cari Supplier"
                  @click="openSupplierPicker('default')"
                >
                  <Search class="w-4 h-4 stroke-[2.3]" />
                </button>
                <!-- Add Button [+] (Green Gradient) -->
                <button
                  type="button"
                  class="w-8 h-8 inline-flex items-center justify-center bg-linear-to-b from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 active:from-emerald-700 active:to-emerald-800 text-white rounded-lg shadow-2xs border border-emerald-600/30 shrink-0 cursor-pointer transition-all"
                  title="Tambah Supplier Baru"
                  @click="supplierCreateModalOpen = true"
                >
                  <Plus class="w-4 h-4 stroke-[2.3]" />
                </button>
                <!-- Reset Button [X] (Red Gradient) -->
                <button
                  type="button"
                  class="w-8 h-8 inline-flex items-center justify-center bg-linear-to-b from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 active:from-rose-700 active:to-rose-800 text-white rounded-lg shadow-2xs border border-rose-600/30 shrink-0 cursor-pointer transition-all"
                  title="Kosongkan Supplier"
                  @click="form.default_supplier = ''"
                >
                  <X class="w-4 h-4 stroke-[2.3]" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- SECTION 2: DETAIL PEMBELIAN                               -->
        <!-- ======================================================== -->
        <div class="space-y-3 pt-2">
          <!-- Section Title with [≡!] icon and dark blue underline -->
          <div class="flex items-center gap-2 pb-1.5 border-b-2 border-[#1e3a8a]">
            <div class="w-4 h-4 border border-slate-400 rounded-xs flex items-center justify-center text-[11px] font-semibold text-slate-600">
              <span class="tracking-tighter">≡!</span>
            </div>
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900">Detail Pembelian</h2>
          </div>

          <!-- Detail Table matching user screenshot -->
          <div class="overflow-x-auto border border-slate-200 rounded-lg">
            <table class="ui-table-wide ui-table w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50/90 border-b border-slate-200 text-slate-700 font-semibold text-[11px]">
                  <th class="py-2.5 px-3 min-w-[280px]">Barang</th>
                  <th class="py-2.5 px-3 min-w-[220px]">
                    Supplier <span class="text-rose-500">*</span>
                  </th>
                  <th class="py-2.5 px-3 text-center w-24">Jumlah</th>
                  <th class="py-2.5 px-3 text-right w-28">Harga Beli</th>
                  <th class="py-2.5 px-3 text-center w-20">% Diskon</th>
                  <th class="py-2.5 px-3 text-right w-28">Diskon/Satuan</th>
                  <th class="py-2.5 px-3 text-right w-32">Subtotal</th>
                  <th class="py-2.5 px-3 text-center w-12"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr
                  v-for="(row, idx) in form.items"
                  :key="idx"
                  class="hover:bg-slate-50/70 transition-colors"
                >
                  <!-- 1. Barang column: picker from Data Barang + [Q] + [+] -->
                  <td class="py-2 px-3">
                    <div class="flex items-center gap-1">
                      <input
                        v-model="row.barang"
                        type="text"
                        readonly
                        placeholder="Pilih barang dari Data Barang..."
                        class="w-full px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg bg-white text-slate-800 focus:outline-hidden focus:ring-1 focus:ring-[#0284c7] cursor-pointer font-medium"
                        title="Klik untuk memilih barang dari master Data Barang"
                        @click="openCatalogPicker(idx)"
                      />
                      <!-- [Q] Search from 2,196 Catalog (Blue Gradient) -->
                      <button
                        type="button"
                        class="w-7 h-7 inline-flex items-center justify-center bg-linear-to-b from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700 active:from-sky-700 active:to-sky-800 text-white rounded-lg shadow-2xs border border-sky-600/30 shrink-0 cursor-pointer transition-all"
                        title="Cari dari katalog 2.196 data barang"
                        @click="openCatalogPicker(idx)"
                      >
                        <Search class="w-3.5 h-3.5 stroke-[2.3]" />
                      </button>
                      <!-- [+] Quick Add Item (Green Gradient) -->
                      <button
                        type="button"
                        class="w-7 h-7 inline-flex items-center justify-center bg-linear-to-b from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 active:from-emerald-700 active:to-emerald-800 text-white rounded-lg shadow-2xs border border-emerald-600/30 shrink-0 cursor-pointer transition-all"
                        title="Tambah Barang Baru"
                        @click="openAddCatalogModal(idx)"
                      >
                        <Plus class="w-3.5 h-3.5 stroke-[2.3]" />
                      </button>
                    </div>
                  </td>

                  <!-- 2. Supplier column: input + [Q] + [+] + [X] -->
                  <td class="py-2 px-3">
                    <div class="flex items-center gap-1">
                      <input
                        v-model="row.supplier"
                        type="text"
                        placeholder="Pilih supplier"
                        class="w-full px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg bg-white text-slate-800 focus:outline-hidden focus:ring-1 focus:ring-[#0284c7]"
                      />
                      <!-- [Q] Search Supplier (Blue Gradient) -->
                      <button
                        type="button"
                        class="w-7 h-7 inline-flex items-center justify-center bg-linear-to-b from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700 active:from-sky-700 active:to-sky-800 text-white rounded-lg shadow-2xs border border-sky-600/30 shrink-0 cursor-pointer transition-all"
                        title="Pilih Supplier"
                        @click="openSupplierPicker(idx)"
                      >
                        <Search class="w-3.5 h-3.5 stroke-[2.3]" />
                      </button>
                      <!-- [+] Add Supplier (Green Gradient) -->
                      <button
                        type="button"
                        class="w-7 h-7 inline-flex items-center justify-center bg-linear-to-b from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 active:from-emerald-700 active:to-emerald-800 text-white rounded-lg shadow-2xs border border-emerald-600/30 shrink-0 cursor-pointer transition-all"
                        title="Tambah Supplier Baru"
                        @click="supplierCreateModalOpen = true"
                      >
                        <Plus class="w-3.5 h-3.5 stroke-[2.3]" />
                      </button>
                      <!-- [X] Reset Supplier (Red Gradient) -->
                      <button
                        type="button"
                        class="w-7 h-7 inline-flex items-center justify-center bg-linear-to-b from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 active:from-rose-700 active:to-rose-800 text-white rounded-lg shadow-2xs border border-rose-600/30 shrink-0 cursor-pointer transition-all"
                        title="Hapus Supplier"
                        @click="row.supplier = ''"
                      >
                        <X class="w-3.5 h-3.5 stroke-[2.3]" />
                      </button>
                    </div>
                  </td>

                  <!-- 3. Jumlah (Qty) -->
                  <td class="py-2 px-3 text-center">
                    <input
                      v-model.number="row.qty"
                      type="number"
                      min="0"
                      class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded-lg text-center font-semibold text-slate-900 bg-white focus:outline-hidden focus:ring-1 focus:ring-[#0284c7]"
                      @input="recalculateRow(row)"
                    />
                  </td>

                  <!-- 4. Harga Beli -->
                  <td class="py-2 px-3 text-right">
                    <input
                      v-model.number="row.harga_beli"
                      type="number"
                      min="0"
                      class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded-lg text-right font-mono text-slate-900 bg-white focus:outline-hidden focus:ring-1 focus:ring-[#0284c7]"
                      @input="recalculateRow(row)"
                    />
                  </td>

                  <!-- 5. % Diskon -->
                  <td class="py-2 px-3 text-center">
                    <input
                      v-model.number="row.diskon_percent"
                      type="number"
                      min="0"
                      max="100"
                      class="w-full px-1.5 py-1.5 text-xs border border-slate-300 rounded-lg text-center font-mono text-slate-800 bg-white focus:outline-hidden focus:ring-1 focus:ring-[#0284c7]"
                      @input="onDiskonPercentChange(row)"
                    />
                  </td>

                  <!-- 6. Diskon/Satuan -->
                  <td class="py-2 px-3 text-right">
                    <input
                      v-model.number="row.diskon_nominal"
                      type="number"
                      min="0"
                      class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded-lg text-right font-mono text-slate-800 bg-white focus:outline-hidden focus:ring-1 focus:ring-[#0284c7]"
                      @input="onDiskonNominalChange(row)"
                    />
                  </td>

                  <!-- 7. Subtotal -->
                  <td class="py-2 px-3 text-right">
                    <div class="px-2.5 py-1.5 text-xs border border-slate-200 rounded-lg bg-slate-100 text-slate-800 font-mono font-semibold text-right">
                      Rp {{ Number(row.subtotal || 0).toLocaleString('id-ID') }}
                    </div>
                  </td>

                  <!-- 8. Red Trash Button matching user screenshot (Red Gradient) -->
                  <td class="py-2 px-3 text-center">
                    <button
                      type="button"
                      class="w-7 h-7 inline-flex items-center justify-center bg-linear-to-b from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 active:from-red-700 active:to-red-800 text-white rounded-lg shadow-2xs border border-red-700/30 transition-all cursor-pointer"
                      title="Hapus Baris"
                      @click="removeRow(idx)"
                    >
                      <Trash2 class="w-3.5 h-3.5 stroke-[2.2]" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Add Row Action Button -->
          <div class="flex justify-start">
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
              @click="addRow"
            >
              <Plus class="w-3.5 h-3.5 text-emerald-600" />
              <span>Tambah Baris Barang</span>
            </button>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- SECTION 3: TOTAL DAN PEMBAYARAN                           -->
        <!-- ======================================================== -->
        <div class="space-y-3 pt-2">
          <!-- Section Title with [#] icon and dark blue underline -->
          <div class="flex items-center gap-2 pb-1.5 border-b-2 border-[#1e3a8a]">
            <div class="w-4 h-4 border border-slate-400 rounded-xs flex items-center justify-center text-[11px] font-semibold text-slate-600">
              #
            </div>
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900">Total dan Pembayaran</h2>
          </div>

          <!-- Total Calculation Section aligned to the right matching user screenshot -->
          <div class="flex flex-col items-end gap-2.5 pt-1 pr-2">
            <!-- Jumlah Harga -->
            <div class="flex items-center gap-4 text-xs">
              <span class="font-medium text-slate-600 w-32 text-right">Jumlah Harga</span>
              <div class="w-56 px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-right font-mono font-semibold text-slate-900">
                Rp {{ computedJumlahHarga.toLocaleString('id-ID') }}
              </div>
            </div>

            <!-- Discount -->
            <div class="flex items-center gap-4 text-xs">
              <span class="font-medium text-slate-600 w-32 text-right">Discount</span>
              <div class="w-56">
                <input
                  v-model.number="form.discount"
                  type="number"
                  min="0"
                  class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg text-right font-mono text-slate-900 bg-white focus:outline-hidden focus:ring-1 focus:ring-[#0284c7]"
                />
              </div>
            </div>

            <!-- Total Harga (Bold) -->
            <div class="flex items-center gap-4 text-xs">
              <span class="font-semibold text-slate-900 w-32 text-right">Total Harga</span>
              <div class="w-56 px-3 py-1.5 bg-slate-100 border border-slate-300 rounded-lg text-right font-mono font-semibold text-slate-900 text-sm">
                Rp {{ computedTotalHarga.toLocaleString('id-ID') }}
              </div>
            </div>

            <!-- Jenis Pembayaran -->
            <div class="flex items-center gap-4 text-xs">
              <span class="font-medium text-slate-600 w-32 text-right">Jenis Pembayaran</span>
              <div class="w-56">
                <select
                  v-model="form.payment_method"
                  class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg bg-white text-slate-800 font-medium focus:outline-hidden focus:ring-1 focus:ring-[#0284c7] cursor-pointer"
                >
                  <option value="Tunai">Tunai</option>
                  <option value="Transfer Bank BCA">Transfer Bank BCA</option>
                  <option value="Transfer Bank Mandiri">Transfer Bank Mandiri</option>
                  <option value="Tempo 14 Hari">Tempo 14 Hari</option>
                  <option value="Tempo 30 Hari">Tempo 30 Hari</option>
                  <option value="Tempo 60 Hari">Tempo 60 Hari</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- FOOTER BUTTONS: BATAL & SIMPAN                            -->
        <!-- ======================================================== -->
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200">
          <!-- Batal Button (White with border) -->
          <button
            type="button"
            class="px-5 py-2 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 shadow-2xs transition-colors cursor-pointer"
            @click="goBack"
          >
            Batal
          </button>

          <!-- Simpan Button (Green with Save/Check icon matching user screenshot) -->
          <button
            type="submit"
            class="inline-flex items-center gap-2 px-5 py-2 bg-alpharo-600 hover:bg-alpharo-700 active:bg-alpharo-800 text-white text-xs font-semibold rounded-lg shadow-xs transition-colors cursor-pointer"
          >
            <Save class="w-4 h-4 stroke-[2.2]" />
            <span>Simpan</span>
          </button>
        </div>
      </form>
    </div>

    <!-- MODAL 1: PICKER CATALOG BARANG (2.196 DATA BARANG) -->
    <Modal
      v-model="catalogPickerOpen"
      title="Cari & Pilih Barang (Katalog 2.196 Barang)"
      subtitle="Ketik nama material atau kode SKU untuk memilih barang secara instan."
      size="xl"
    >
      <div class="space-y-3 text-xs">
        <div class="relative">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            v-model="catalogSearchQuery"
            type="text"
            placeholder="Cari barang (contoh: A00001770, Komputer, Cat, Semen)..."
            class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-xs focus:outline-hidden focus:ring-1 focus:ring-[#0284c7]"
            autofocus
          />
        </div>

        <div class="max-h-80 overflow-y-auto border border-slate-200 rounded-lg divide-y divide-slate-100">
          <div
            v-for="p in filteredCatalogItems"
            :key="p.sku"
            class="p-3 hover:bg-sky-50/70 transition-colors flex items-center justify-between cursor-pointer"
            @click="selectCatalogItem(p)"
          >
            <div>
              <div class="font-semibold text-slate-900">{{ p.name }}</div>
              <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                <span class="font-mono text-[#0284c7] font-semibold">{{ p.sku }}</span>
                <span>•</span>
                <span>{{ p.category }}</span>
                <span>•</span>
                <span>Satuan: {{ p.unit }}</span>
                <span>•</span>
                <span>Stok: {{ p.stock }}</span>
              </div>
            </div>
            <div class="text-right shrink-0 ml-3">
              <span class="text-[11px] font-mono text-slate-600 block">
                Acuan: Rp {{ Number(p.last_buy_price || p.hpp || p.sell_price || 0).toLocaleString('id-ID') }}
              </span>
              <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-semibold text-[11px] mt-1 inline-block">
                Pilih
              </span>
            </div>
          </div>
        </div>
      </div>
    </Modal>

    <!-- MODAL 2: PICKER SUPPLIER -->
    <Modal
      v-model="supplierPickerOpen"
      title="Pilih Rekanan Supplier"
      subtitle="Pilih supplier terdaftar untuk transaksi pembelian ini."
      size="lg"
    >
      <div class="space-y-3 text-xs">
        <div class="relative">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            v-model="supplierSearchQuery"
            type="text"
            placeholder="Cari nama supplier atau kontak..."
            class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-xs focus:outline-hidden focus:ring-1 focus:ring-[#0284c7]"
          />
        </div>

        <div class="max-h-72 overflow-y-auto border border-slate-200 rounded-lg divide-y divide-slate-100">
          <div
            v-for="sup in filteredSuppliers"
            :key="sup.code"
            class="p-3 hover:bg-emerald-50/70 transition-colors flex items-center justify-between cursor-pointer"
            @click="selectSupplier(sup)"
          >
            <div>
              <div class="font-semibold text-slate-900">{{ sup.name }}</div>
              <div class="text-[11px] text-slate-500 mt-0.5">
                <span class="font-mono text-emerald-700 font-semibold">{{ sup.code }}</span>
                <span v-if="sup.phone"> • {{ sup.phone }}</span>
                <span v-if="sup.address"> • {{ sup.address }}</span>
              </div>
            </div>
            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-semibold text-[11px]">
              Gunakan
            </span>
          </div>
        </div>
      </div>
    </Modal>

    <!-- MODAL 3: TAMBAH SUPPLIER BARU -->
    <Modal
      v-model="supplierCreateModalOpen"
      title="Tambah Data Supplier Baru"
      subtitle="Daftarkan supplier baru ke master database pengadaan."
      size="md"
    >
      <form class="space-y-3 text-xs" @submit.prevent="saveNewSupplier">
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Nama Supplier / Perusahaan *</label>
          <input
            v-model="newSupplierForm.name"
            required
            placeholder="Contoh: PT Komputer Nusantara Mandiri"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-[#0284c7]"
          />
        </div>
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Nomor Telepon / Kontak</label>
          <input
            v-model="newSupplierForm.phone"
            placeholder="021-xxxxxxx / 0812xxxxxxx"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-[#0284c7]"
          />
        </div>
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Email</label>
          <input
            v-model="newSupplierForm.email"
            type="email"
            placeholder="sales@perusahaan.com"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-[#0284c7]"
          />
        </div>
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Alamat Kantor / Gudang</label>
          <textarea
            v-model="newSupplierForm.address"
            rows="2"
            placeholder="Alamat lengkap supplier..."
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-[#0284c7]"
          ></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button
            type="button"
            class="px-3.5 py-1.5 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50"
            @click="supplierCreateModalOpen = false"
          >
            Batal
          </button>
          <button
            type="submit"
            class="px-4 py-1.5 bg-alpharo-600 hover:bg-alpharo-700 text-white font-semibold rounded-lg shadow-xs"
          >
            Simpan Supplier
          </button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from '../../navigation';
import AppLayout from '../../components/layout/AppLayout.vue';
import Modal from '../../components/common/Modal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import api from '../../services/api';
import { orderError } from '../../services/orderWorkflow';
import {
  ShoppingBag,
  RotateCcw,
  Search,
  Plus,
  X,
  Trash2,
  Save,
  Check,
  Building,
  Lock,
  ClipboardList,
  ArrowRight,
} from 'lucide-vue-next';

const router = useRouter();
const route = useRoute();
const toast = useToast();

const catalogPickerOpen = ref(false);
const catalogSearchQuery = ref('');
const activeRowIndex = ref(null);

const supplierPickerOpen = ref(false);
const supplierSearchQuery = ref('');
const supplierTargetRow = ref(null); // 'default' or number

const supplierCreateModalOpen = ref(false);
const newSupplierForm = ref({
  name: '',
  phone: '',
  email: '',
  address: '',
});

const catalogItems = ref([]);
const sourceRequest = ref(null);

// Pending requests from warehouse (Prerequisite for finance purchases)
const pendingWarehouseRequests = computed(() => {
  return (store.warehouseRequests || []).filter(
    (r) => r.status === 'MENUNGGU_FINANCE' || r.status === 'Draft' || r.status === 'Menunggu Verifikasi'
  );
});

const resetSelectedRequest = () => {
  sourceRequest.value = null;
  form.value.warehouse_request_id = null;
  form.value.warehouse_request_code = null;
};

const availableProjects = ref([
  { id: 'PR00001', name: 'pengadaan komputer sekolah (SMA NEGRI 2 BOGOR)' },
  { id: 'PR00002', name: 'Pembangunan Fasilitas Workshop & Lab Vokasi' },
  { id: 'PR00003', name: 'Pekerjaan Pasangan & Struktur Jembatan Kanal' },
  { id: 'PR00004', name: 'Renovasi Gedung Laboratorium Komputer' },
]);

const suppliersList = ref([
  { code: 'SUP00001', name: 'PT Synnex Metrodata Indonesia', phone: '021-29345800', address: 'Jakarta Barat' },
  { code: 'SUP00002', name: 'CV Sumber Makmur Komputer', phone: '0251-8321900', address: 'Kota Bogor' },
  { code: 'SUP00003', name: 'PT Indo Komputer Perkasa', phone: '021-62301122', address: 'Jakarta Pusat' },
  { code: 'SUP00004', name: 'CV Mandiri Perkasa Prima', phone: '0251-8456789', address: 'Sentul, Bogor' },
  { code: 'SUP00005', name: 'PT Cipta Karya Niaga', phone: '021-58902233', address: 'Jakarta Barat' },
]);

// Generate default purchase code matching format PB + YYYYMMDDHHmmss
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

// Form state matching user's image exactly
const form = ref({
  purchase_code: generatePurchaseCode(),
  transaction_date: new Date().toISOString().split('T')[0],
  invoice_number: '',
  project_id: 'PR00001',
  project_name: 'pengadaan komputer sekolah (SMA NEGRI 2 BOGOR)',
  default_supplier: '',
  warehouse_request_id: null,
  warehouse_request_code: null,
  items: [
    // Row 1 matching user screenshot exactly
    {
      barang: 'A00001770 - Pemeliharaan Komputer Note Book/ Laptop',
      sku: 'A00001770',
      supplier: '',
      qty: 100,
      harga_beli: 0,
      diskon_percent: 0,
      diskon_nominal: 0,
      subtotal: 0,
    },
    // Row 2 matching quick add line in user screenshot
    {
      barang: '',
      sku: '',
      supplier: '',
      qty: 0,
      harga_beli: 0,
      diskon_percent: 0,
      diskon_nominal: 0,
      subtotal: 0,
    },
  ],
  discount: 0,
  payment_method: 'Tunai',
});

// Load catalog & suppliers on mount
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

  try {
    const supRes = await fetch('/api/suppliers');
    if (supRes.ok) {
      const data = await supRes.json();
      if (data.data?.length) {
        suppliersList.value = data.data;
      }
    }
    // Also include all store.suppliers
    if (store.suppliers && store.suppliers.length) {
      store.suppliers.forEach((s) => {
        if (!suppliersList.value.some((existing) => existing.name === s.name || existing.code === s.code)) {
          suppliersList.value.push(s);
        }
      });
    }
  } catch (e) {}

  // Check if routed from a Warehouse Request (Auto-generate data!)
  try {
    const response = await fetch('/api/warehouse/requests');
    if (response.ok) {
      const data = await response.json();
      if (Array.isArray(data.data)) store.warehouseRequests = data.data;
    }
  } catch { toast.error('Daftar pengajuan Gudang belum tersinkron.'); }
  const reqId = route.query.request_id || route.query.requestCode;
  if (reqId) {
    loadFromWarehouseRequest(reqId);
  }
});

const loadFromWarehouseRequest = (reqId) => {
  const req = (store.warehouseRequests || []).find(
    (r) => String(r.id) === String(reqId) || r.requestCode === reqId || r.request_code === reqId
  );

  if (req) {
    sourceRequest.value = req;
    form.value.warehouse_request_id = req.id;
    form.value.warehouse_request_code = req.requestCode || req.request_code;
    form.value.project_name = req.projectName || req.project_name || form.value.project_name;
    form.value.project_id = req.projectId || req.project_id || null;
    if (!availableProjects.value.some(project => project.name === form.value.project_name)) {
      availableProjects.value.push({ id: form.value.project_id || req.requestCode || req.request_code, name: form.value.project_name });
    }

    const initialSupplier = route.query.supplier || form.value.default_supplier || (suppliersList.value[0]?.name || '');
    if (initialSupplier) {
      form.value.default_supplier = initialSupplier;
    }

    if (req.items && req.items.length > 0) {
      form.value.items = req.items.map((it) => {
        const qty = Number(it.qty) || 1;
        const hargaBeli = Number(it.estPrice || it.est_price) || 0;
        return {
          barang: it.name,
          sku: it.sku || '',
          unit: it.unit || 'UNIT',
          supplier: form.value.default_supplier || '',
          qty,
          harga_beli: hargaBeli,
          diskon_percent: 0,
          diskon_nominal: 0,
          subtotal: qty * hargaBeli,
        };
      });

      // Append an empty row for quick additions if needed
      form.value.items.push({
        barang: '',
        sku: '',
        supplier: '',
        qty: 0,
        harga_beli: 0,
        diskon_percent: 0,
        diskon_nominal: 0,
        subtotal: 0,
      });
    }

    toast.info(`Data otomatis diisi dari Pengajuan Gudang ${req.requestCode || req.request_code}`);
  }
};

const filteredCatalogItems = computed(() => {
  const items = Array.isArray(catalogItems.value) ? catalogItems.value : [];
  if (!catalogSearchQuery.value) return items.slice(0, 30);
  const q = catalogSearchQuery.value.toLowerCase();
  return items
    .filter((p) => p.name?.toLowerCase().includes(q) || p.sku?.toLowerCase().includes(q))
    .slice(0, 40);
});

const filteredSuppliers = computed(() => {
  if (!supplierSearchQuery.value) return suppliersList.value;
  const q = supplierSearchQuery.value.toLowerCase();
  return suppliersList.value.filter(
    (s) => s.name?.toLowerCase().includes(q) || s.code?.toLowerCase().includes(q)
  );
});

const computedJumlahHarga = computed(() => {
  return form.value.items.reduce((sum, r) => sum + (Number(r.subtotal) || 0), 0);
});

const computedTotalHarga = computed(() => {
  const total = computedJumlahHarga.value - (Number(form.value.discount) || 0);
  return total > 0 ? total : 0;
});

const recalculateRow = (row) => {
  const qty = Number(row.qty) || 0;
  const price = Number(row.harga_beli) || 0;
  const discountPerUnit = Number(row.diskon_nominal) || 0;
  const finalPrice = Math.max(0, price - discountPerUnit);
  row.subtotal = qty * finalPrice;
};

const onDiskonPercentChange = (row) => {
  const price = Number(row.harga_beli) || 0;
  const pct = Number(row.diskon_percent) || 0;
  row.diskon_nominal = Math.round((price * pct) / 100);
  recalculateRow(row);
};

const onDiskonNominalChange = (row) => {
  const price = Number(row.harga_beli) || 0;
  const nom = Number(row.diskon_nominal) || 0;
  if (price > 0) {
    row.diskon_percent = Math.round((nom / price) * 100);
  }
  recalculateRow(row);
};

const addRow = () => {
  form.value.items.push({
    barang: '',
    sku: '',
    supplier: form.value.default_supplier || '',
    qty: 0,
    harga_beli: 0,
    diskon_percent: 0,
    diskon_nominal: 0,
    subtotal: 0,
  });
};

const removeRow = (idx) => {
  if (form.value.items.length > 1) {
    form.value.items.splice(idx, 1);
  } else {
    // Reset the only row
    form.value.items[0] = {
      barang: '',
      sku: '',
      supplier: '',
      qty: 0,
      harga_beli: 0,
      diskon_percent: 0,
      diskon_nominal: 0,
      subtotal: 0,
    };
  }
};

const openCatalogPicker = (idx) => {
  activeRowIndex.value = idx;
  catalogSearchQuery.value = '';
  catalogPickerOpen.value = true;
};

const selectCatalogItem = (p) => {
  if (activeRowIndex.value !== null && form.value.items[activeRowIndex.value]) {
    const row = form.value.items[activeRowIndex.value];
    row.barang = `${p.sku} - ${p.name}`;
    row.sku = p.sku;
    row.harga_beli = p.last_buy_price || p.hpp || p.sell_price || 0;
    if (!row.qty || row.qty === 0) row.qty = 1;
    if (!row.supplier && form.value.default_supplier) {
      row.supplier = form.value.default_supplier;
    }
    recalculateRow(row);
  }
  catalogPickerOpen.value = false;
};

const openAddCatalogModal = (idx) => {
  activeRowIndex.value = idx;
  toast.info('Silakan ketik nama barang langsung pada kolom input atau pilih dari katalog.');
};

const openSupplierPicker = (target) => {
  supplierTargetRow.value = target;
  supplierSearchQuery.value = '';
  supplierPickerOpen.value = true;
};

const selectSupplier = (sup) => {
  if (supplierTargetRow.value === 'default') {
    form.value.default_supplier = sup.name;
    // Auto-fill empty suppliers in rows
    form.value.items.forEach((r) => {
      if (!r.supplier) r.supplier = sup.name;
    });
  } else if (typeof supplierTargetRow.value === 'number') {
    if (form.value.items[supplierTargetRow.value]) {
      form.value.items[supplierTargetRow.value].supplier = sup.name;
    }
  }
  supplierPickerOpen.value = false;
};

const applySupplierToAllRows = () => {
  if (form.value.default_supplier) {
    form.value.items.forEach((r) => {
      if (r.barang) {
        r.supplier = form.value.default_supplier;
      }
    });
    toast.success(`Supplier "${form.value.default_supplier}" berhasil diterapkan ke semua item barang.`);
  }
};

const saveNewSupplier = async () => {
  const newSup = {
    code: `SUP${String(suppliersList.value.length + 1).padStart(5, '0')}`,
    name: newSupplierForm.value.name,
    phone: newSupplierForm.value.phone,
    email: newSupplierForm.value.email,
    address: newSupplierForm.value.address,
  };
  suppliersList.value.push(newSup);
  if (storeActions?.addSupplier) {
    storeActions.addSupplier(newSup);
  }

  // Sync to backend
  try {
    api.post('/suppliers', newSup).catch(() => {});
  } catch (e) {}

  toast.success(`Supplier "${newSup.name}" berhasil ditambahkan.`);
  form.value.default_supplier = newSup.name;
  supplierCreateModalOpen.value = false;
};

const onProjectChange = () => {
  const p = availableProjects.value.find((proj) => proj.name === form.value.project_name);
  if (p) form.value.project_id = p.id;
};

const goBack = () => {
  router.push('/finance/purchases');
};

const savePurchase = async () => {
  try {
    // Validate warehouse request prerequisite (Strict PROBIS TO-BE)
    if (!sourceRequest.value && !form.value.warehouse_request_code) {
      toast.error('Aktivitas pembelian ditolak: Transaksi harus didasarkan pada Pengajuan Kebutuhan Barang dari Warehouse.');
      return;
    }

    // Validate valid rows
    const validItems = form.value.items.filter((r) => r.barang && r.barang.trim() !== '');
    if (validItems.length === 0) {
      toast.error('Harap isi minimal 1 barang pada tabel Detail Pembelian.');
      return;
    }

    const payload = {
      purchase_code: form.value.purchase_code,
      transaction_date: form.value.transaction_date,
      invoice_number: form.value.invoice_number || `FAK-${Date.now().toString().slice(-6)}`,
      project_id: form.value.project_id,
      project_name: form.value.project_name,
      default_supplier: form.value.default_supplier,
      warehouse_request_id: form.value.warehouse_request_id,
      warehouse_request_code: form.value.warehouse_request_code,
      subtotal: computedJumlahHarga.value,
      discount: form.value.discount || 0,
      total_price: computedTotalHarga.value,
      payment_method: form.value.payment_method,
      items: validItems,
      supplier_status: 'Menunggu Konfirmasi',
    };

    await storeActions.createPurchase(payload);

    // PROBIS TO-BE Tahap 4: Notifikasi multi-role
    storeActions.addNotification(
      'WAREHOUSE',
      'Pesanan Barang Supplier Diterbitkan (PO)',
      `Finance telah memesan barang ke Supplier ${form.value.default_supplier || 'Rekanan'} (No: ${form.value.purchase_code}) untuk proyek "${form.value.project_name}". Mohon bersiap menerima dan memeriksa fisik barang saat tiba.`,
      'purchase',
      '/warehouse/fulfillment'
    );

    storeActions.addNotification(
      'MARKETING',
      'Pemesanan Barang Supplier Berhasil',
      `Barang pengadaan proyek "${form.value.project_name}" telah dipesan oleh Finance ke Supplier ${form.value.default_supplier || ''} (${form.value.purchase_code}).`,
      'finance',
      '/marketing/orders'
    );

    storeActions.addNotification(
      'SUPPLIER',
      'Pesanan Pembelian Baru (PO) dari CV Alpharo',
      `Finance CV Alpharo Artha Jaya telah menerbitkan pesanan PO ${form.value.purchase_code} untuk proyek "${form.value.project_name}". Silakan tinjau dan konfirmasi ketersediaan barang.`,
      'purchase',
      '/supplier/portal'
    );

    storeActions.addActivityLog('Menerbitkan Pembelian Supplier', form.value.purchase_code, `Supplier: ${form.value.default_supplier}, Proyek: ${form.value.project_name}`);

    toast.success(`Transaksi Pembelian ${form.value.purchase_code} berhasil disimpan!`);
    router.push('/finance/purchases');
  } catch (error) { toast.error(orderError(error)); }
};
</script>
