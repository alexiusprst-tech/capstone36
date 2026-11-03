<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <!-- Title with Truck icon matching screenshot -->
        <div class="flex items-center gap-2.5">
          <div class="p-1.5 bg-slate-100 rounded-lg text-slate-800 flex items-center justify-center">
            <Truck class="w-6 h-6 stroke-[2.2]" />
          </div>
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Data Supplier</h1>
          </div>
        </div>

        <!-- Top Right Actions -->
        <div class="flex items-center flex-wrap gap-2.5">
          <!-- Info Alur PROBIS TO-BE Button -->
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-[#0B4DA2] font-semibold rounded-lg text-xs border border-blue-200 shadow-2xs transition-colors cursor-pointer"
            title="Klik untuk melihat peran Supplier pada Alur PROBIS TO-BE"
            @click="showProbisInfo = !showProbisInfo"
          >
            <Info class="w-4 h-4 text-[#0B4DA2]" />
            <span>Alur PROBIS TO-BE</span>
          </button>

          <!-- Tambah Button matching screenshot (+ Tambah in Blue) -->
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0284c7] hover:bg-[#0369a1] active:bg-[#075985] text-white font-semibold rounded-lg text-xs shadow-xs transition-colors cursor-pointer"
            @click="openAddModal"
          >
            <Plus class="w-4 h-4 stroke-[2.5]" />
            <span>Tambah</span>
          </button>
        </div>
      </div>
    </template>

    <!-- PROBIS TO-BE CONTEXT BANNER (Collapsible) -->
    <div
      v-if="showProbisInfo"
      class="bg-blue-50/80 border border-blue-200 rounded-xl p-4 mb-5 text-xs text-slate-700 animate-in fade-in duration-200"
    >
      <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-2.5">
          <Info class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
          <div class="space-y-1">
            <p class="font-bold text-blue-900 text-sm">Peran Finance & Supplier dalam Alur Bisnis TO-BE (CV Alpharo Artha Jaya)</p>
            <p class="leading-relaxed text-slate-600">
              Berdasarkan alur proses bisnis TO-BE:
            </p>
            <ul class="list-disc list-inside space-y-0.5 text-slate-600 pl-1">
              <li><strong>Tahap 4 (Pengadaan Barang):</strong> Jika stok di gudang tidak tersedia, Warehouse mengajukan kebutuhan pengadaan ke Finance. Finance bertugas memesan barang ke supplier sesuai database ini.</li>
              <li><strong>Tahap 5 (Pemeriksaan Fisik Gudang):</strong> Warehouse memeriksa barang yang dikirim supplier. Apabila terdapat ketidaksesuaian/cacat fisik, divisi Finance yang menindaklanjuti ketidaksesuaian kepada supplier untuk penggantian/pemenuhan ulang.</li>
              <li><strong>Faktur & Pembayaran:</strong> Finance melakukan verifikasi faktur pembelian, termin/tempo, serta penyelesaian pembayaran supplier.</li>
            </ul>
          </div>
        </div>
        <button
          type="button"
          class="text-slate-400 hover:text-slate-600 p-1"
          @click="showProbisInfo = false"
        >
          <X class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- PENCARIAN CARD (Matching Screenshot Exactly) -->
    <div class="bg-white rounded-xl border border-slate-200/90 mb-5 shadow-2xs overflow-hidden">
      <!-- Top green accent line -->
      <div class="h-1 bg-emerald-600 w-full"></div>

      <div class="p-4 sm:p-5">
        <label class="block text-xs font-semibold text-slate-700 mb-2">Pencarian</label>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
          <!-- Search Input with placeholder Cari ID / nama / alamat / kontak / no HP / email / NPWP -->
          <div class="relative flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari ID / nama / alamat / kontak / no HP / email / NPWP"
              class="w-full pl-3.5 pr-8 py-2 text-xs rounded-lg border border-slate-300 bg-white placeholder:text-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0284c7] focus:border-transparent transition-all text-slate-800"
              @keydown.enter.prevent="handleSearch"
            />
            <button
              v-if="searchQuery"
              type="button"
              class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 cursor-pointer"
              title="Hapus pencarian"
              @click="clearSearch"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Buttons: Cari (Blue), Reset (White), Excel (Green) -->
          <div class="flex items-center gap-2">
            <!-- Cari Button -->
            <button
              type="button"
              class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-[#0284c7] hover:bg-[#0369a1] active:bg-[#075985] text-white rounded-lg text-xs font-semibold shadow-xs transition-colors cursor-pointer"
              @click="handleSearch"
            >
              <Search class="w-3.5 h-3.5 stroke-[2.5]" />
              <span>Cari</span>
            </button>

            <!-- Reset Button -->
            <button
              type="button"
              class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 rounded-lg text-xs font-medium border border-slate-300 shadow-2xs transition-colors cursor-pointer"
              @click="handleReset"
            >
              <RotateCcw class="w-3.5 h-3.5" />
              <span>Reset</span>
            </button>

            <!-- Excel Button -->
            <button
              type="button"
              class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-[#15803d] hover:bg-[#166534] active:bg-[#14532d] text-white rounded-lg text-xs font-semibold shadow-xs transition-colors cursor-pointer"
              @click="exportToExcel"
            >
              <FileSpreadsheet class="w-3.5 h-3.5" />
              <span>Excel</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- DATA TABLE CONTAINER (Matching Screenshot Exactly) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
      <!-- Top green accent line -->
      <div class="h-1 bg-emerald-600 w-full"></div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-700 font-semibold text-[11px] tracking-normal select-none">
              <!-- ID Column with ▲ sort indicator -->
              <th
                class="py-3 px-4 cursor-pointer hover:bg-slate-100/60 transition-colors whitespace-nowrap"
                @click="sortByCol('code')"
              >
                <div class="flex items-center gap-1">
                  <span>ID</span>
                  <span class="text-[10px] text-slate-500 font-mono">
                    {{ sortCol === 'code' ? (sortDir === 'asc' ? '▲' : '▼') : '▲' }}
                  </span>
                </div>
              </th>

              <!-- Nama Supplier with ↓↑ sort indicator -->
              <th
                class="py-3 px-4 cursor-pointer hover:bg-slate-100/60 transition-colors whitespace-nowrap"
                @click="sortByCol('name')"
              >
                <div class="flex items-center gap-1">
                  <span>Nama Supplier</span>
                  <span class="text-[10px] text-slate-400">
                    {{ sortCol === 'name' ? (sortDir === 'asc' ? '▲' : '▼') : '↓↑' }}
                  </span>
                </div>
              </th>

              <!-- Alamat -->
              <th class="py-3 px-4 whitespace-nowrap">
                <span>Alamat</span>
              </th>

              <!-- Contact Person with ↓↑ sort indicator -->
              <th
                class="py-3 px-4 cursor-pointer hover:bg-slate-100/60 transition-colors whitespace-nowrap"
                @click="sortByCol('contactPerson')"
              >
                <div class="flex items-center gap-1">
                  <span>Contact Person</span>
                  <span class="text-[10px] text-slate-400">
                    {{ sortCol === 'contactPerson' ? (sortDir === 'asc' ? '▲' : '▼') : '↓↑' }}
                  </span>
                </div>
              </th>

              <!-- No HP with ↓↑ sort indicator -->
              <th
                class="py-3 px-4 cursor-pointer hover:bg-slate-100/60 transition-colors whitespace-nowrap"
                @click="sortByCol('phone')"
              >
                <div class="flex items-center gap-1">
                  <span>No HP</span>
                  <span class="text-[10px] text-slate-400">
                    {{ sortCol === 'phone' ? (sortDir === 'asc' ? '▲' : '▼') : '↓↑' }}
                  </span>
                </div>
              </th>

              <!-- Email with ↓↑ sort indicator -->
              <th
                class="py-3 px-4 cursor-pointer hover:bg-slate-100/60 transition-colors whitespace-nowrap"
                @click="sortByCol('email')"
              >
                <div class="flex items-center gap-1">
                  <span>Email</span>
                  <span class="text-[10px] text-slate-400">
                    {{ sortCol === 'email' ? (sortDir === 'asc' ? '▲' : '▼') : '↓↑' }}
                  </span>
                </div>
              </th>

              <!-- NPWP with ↓↑ sort indicator -->
              <th
                class="py-3 px-4 cursor-pointer hover:bg-slate-100/60 transition-colors whitespace-nowrap"
                @click="sortByCol('npwp')"
              >
                <div class="flex items-center gap-1">
                  <span>NPWP</span>
                  <span class="text-[10px] text-slate-400">
                    {{ sortCol === 'npwp' ? (sortDir === 'asc' ? '▲' : '▼') : '↓↑' }}
                  </span>
                </div>
              </th>

              <!-- Aksi -->
              <th class="py-3 px-4 text-center whitespace-nowrap">
                <span>Aksi</span>
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100">
            <!-- Row Items when data exists -->
            <tr
              v-for="sup in paginatedSuppliers"
              :key="sup.id || sup.code"
              class="hover:bg-slate-50/70 transition-colors"
            >
              <!-- ID -->
              <td class="py-3.5 px-4 font-bold text-[#1e40af] font-mono whitespace-nowrap">
                {{ sup.code || `SUP${String(sup.id).padStart(5, '0')}` }}
              </td>

              <!-- Nama Supplier -->
              <td class="py-3.5 px-4 font-medium text-slate-900 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <span>{{ sup.name }}</span>
                  <span
                    v-if="sup.status === 'Nonaktif'"
                    class="px-1.5 py-0.2 bg-slate-100 text-slate-500 rounded text-[10px] font-semibold"
                  >
                    Nonaktif
                  </span>
                </div>
              </td>

              <!-- Alamat -->
              <td class="py-3.5 px-4 text-slate-600 max-w-xs md:max-w-sm truncate text-[11px] leading-relaxed" :title="sup.address">
                {{ sup.address || '-' }}
              </td>

              <!-- Contact Person -->
              <td class="py-3.5 px-4 text-slate-700 whitespace-nowrap text-xs">
                {{ sup.contactPerson || sup.contact_person || '-' }}
              </td>

              <!-- No HP -->
              <td class="py-3.5 px-4 text-slate-700 font-mono whitespace-nowrap text-xs">
                {{ sup.phone || '-' }}
              </td>

              <!-- Email -->
              <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap text-xs">
                {{ sup.email || '-' }}
              </td>

              <!-- NPWP -->
              <td class="py-3.5 px-4 text-slate-700 font-mono whitespace-nowrap text-xs">
                {{ sup.npwp || '-' }}
              </td>

              <!-- Aksi -->
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <div class="flex items-center justify-center gap-1.5">
                  <!-- Detail Button -->
                  <button
                    type="button"
                    class="p-1.5 bg-[#0284c7] hover:bg-[#0369a1] text-white rounded shadow-2xs transition-colors cursor-pointer"
                    title="Lihat Detail Supplier"
                    @click="openDetailModal(sup)"
                  >
                    <Eye class="w-3.5 h-3.5" />
                  </button>

                  <!-- Edit Button (Amber) -->
                  <button
                    type="button"
                    class="p-1.5 bg-[#eab308] hover:bg-[#ca8a04] text-white rounded shadow-2xs transition-colors cursor-pointer"
                    title="Edit Data Supplier"
                    @click="openEditModal(sup)"
                  >
                    <Edit3 class="w-3.5 h-3.5" />
                  </button>

                  <!-- Quick Create PO to this Supplier (PROBIS TO-BE Stage 4) -->
                  <button
                    type="button"
                    class="p-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded shadow-2xs transition-colors cursor-pointer"
                    title="Buat Pesanan Pembelian ke Supplier Ini (PO)"
                    @click="goToPurchaseCreate(sup)"
                  >
                    <ShoppingBag class="w-3.5 h-3.5" />
                  </button>

                  <!-- Delete Button (Red) -->
                  <button
                    type="button"
                    class="p-1.5 bg-[#ef4444] hover:bg-[#dc2626] text-white rounded shadow-2xs transition-colors cursor-pointer"
                    title="Hapus Supplier"
                    @click="confirmDelete(sup)"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>

            <!-- Empty State matching screenshot: "Tidak ada data" -->
            <tr v-if="filteredSuppliers.length === 0">
              <td colspan="8" class="py-12 text-center text-slate-400 font-medium text-xs">
                Tidak ada data
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- PAGINATION FOOTER (Matching Screenshot Exactly) -->
      <div class="p-4 bg-white border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600 select-none">
        <!-- Left: Menampilkan 0 - 0 dari 0 data -->
        <div>
          <span>
            Menampilkan
            <strong class="font-semibold text-slate-800">{{ totalItems > 0 ? startItemIndex : 0 }}</strong>
            -
            <strong class="font-semibold text-slate-800">{{ endItemIndex }}</strong>
            dari
            <strong class="font-semibold text-slate-800">{{ totalItems }}</strong>
            data
          </span>
        </div>

        <!-- Right: First | Prev | 1 | Next | Last | [10 data v] -->
        <div class="flex items-center gap-1.5 flex-wrap">
          <!-- First -->
          <button
            type="button"
            :disabled="currentPage === 1 || totalPages === 0"
            class="px-2.5 py-1 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed font-medium text-xs cursor-pointer transition-colors"
            @click="currentPage = 1"
          >
            First
          </button>

          <!-- Prev -->
          <button
            type="button"
            :disabled="currentPage === 1 || totalPages === 0"
            class="px-2.5 py-1 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed font-medium text-xs cursor-pointer transition-colors"
            @click="currentPage--"
          >
            Prev
          </button>

          <!-- Dynamic Page Buttons -->
          <button
            v-for="page in displayedPages"
            :key="page"
            type="button"
            :class="[
              currentPage === page
                ? 'bg-[#1e40af] text-white font-bold border-[#1e40af]'
                : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300 font-medium',
              'px-3 py-1 rounded border text-xs cursor-pointer transition-colors'
            ]"
            @click="currentPage = page"
          >
            {{ page }}
          </button>

          <!-- Next -->
          <button
            type="button"
            :disabled="currentPage >= totalPages || totalPages === 0"
            class="px-2.5 py-1 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed font-medium text-xs cursor-pointer transition-colors"
            @click="currentPage++"
          >
            Next
          </button>

          <!-- Last -->
          <button
            type="button"
            :disabled="currentPage >= totalPages || totalPages === 0"
            class="px-2.5 py-1 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed font-medium text-xs cursor-pointer transition-colors"
            @click="currentPage = totalPages"
          >
            Last
          </button>

          <!-- Items per page dropdown matching screenshot: 10 data -->
          <div class="relative ml-1">
            <select
              v-model="perPage"
              class="px-2.5 py-1 pr-6 text-xs rounded border border-slate-300 bg-white text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-[#0284c7] cursor-pointer"
              @change="handlePerPageChange"
            >
              <option :value="10">10 data</option>
              <option :value="25">25 data</option>
              <option :value="50">50 data</option>
              <option :value="100">100 data</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL TAMBAH & EDIT SUPPLIER -->
    <Modal
      v-model="supplierModalOpen"
      :title="isEditing ? 'Edit Data Supplier' : 'Tambah Supplier Baru'"
      :subtitle="isEditing ? 'Perbarui informasi profil dan legalitas supplier' : 'Lengkapi data rekanan pengadaan barang CV Alpharo'"
      max-width="2xl"
    >
      <form class="space-y-4" @submit.prevent="saveSupplier">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Kode Supplier -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">
              ID / Kode Supplier <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="supplierForm.code"
              type="text"
              placeholder="Contoh: SUP00001"
              required
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-[#0284c7] font-mono bg-slate-50"
            />
          </div>

          <!-- Status -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Status Keaktifan</label>
            <select
              v-model="supplierForm.status"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-[#0284c7] bg-white cursor-pointer"
            >
              <option value="Aktif">Aktif</option>
              <option value="Nonaktif">Nonaktif</option>
            </select>
          </div>

          <!-- Nama Supplier -->
          <div class="sm:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">
              Nama Supplier / Perusahaan <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="supplierForm.name"
              type="text"
              placeholder="Contoh: PT Synnex Metrodata Indonesia"
              required
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-[#0284c7]"
            />
          </div>

          <!-- Contact Person -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Contact Person (PIC)</label>
            <input
              v-model="supplierForm.contactPerson"
              type="text"
              placeholder="Contoh: Hendra Gunawan (Sales)"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-[#0284c7]"
            />
          </div>

          <!-- No HP / Telepon -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">No HP / Telepon / WhatsApp</label>
            <input
              v-model="supplierForm.phone"
              type="text"
              placeholder="Contoh: 021-29345800 / 081298765432"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-[#0284c7] font-mono"
            />
          </div>

          <!-- Email -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Email</label>
            <input
              v-model="supplierForm.email"
              type="email"
              placeholder="Contoh: sales@synnexmetrodata.com"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-[#0284c7]"
            />
          </div>

          <!-- NPWP -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">NPWP Supplier</label>
            <input
              v-model="supplierForm.npwp"
              type="text"
              placeholder="Contoh: 01.345.678.9-031.000"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-[#0284c7] font-mono"
            />
          </div>

          <!-- Rekening Bank (Finance Payment Info) -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Bank</label>
            <input
              v-model="supplierForm.bankName"
              type="text"
              placeholder="Contoh: BCA / Mandiri / BNI"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-[#0284c7]"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Rekening</label>
            <input
              v-model="supplierForm.bankAccountNumber"
              type="text"
              placeholder="Contoh: 5001239841"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-[#0284c7] font-mono"
            />
          </div>

          <div class="sm:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Atas Nama Rekening</label>
            <input
              v-model="supplierForm.bankAccountName"
              type="text"
              placeholder="Contoh: PT Synnex Metrodata Indonesia"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-[#0284c7]"
            />
          </div>

          <!-- Alamat -->
          <div class="sm:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Kantor / Gudang</label>
            <textarea
              v-model="supplierForm.address"
              rows="2"
              placeholder="Alamat lengkap supplier..."
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-[#0284c7]"
            ></textarea>
          </div>

          <!-- Catatan / Spesialisasi Produk -->
          <div class="sm:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Spesialisasi Pengadaan</label>
            <textarea
              v-model="supplierForm.notes"
              rows="2"
              placeholder="Catatan barang/layanan, kontak darurat, atau ketentuan retur barang..."
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-[#0284c7]"
            ></textarea>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
          <button
            type="button"
            class="px-4 py-2 text-xs font-medium border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer"
            @click="supplierModalOpen = false"
          >
            Batal
          </button>
          <button
            type="submit"
            class="px-5 py-2 text-xs font-bold bg-[#0284c7] hover:bg-[#0369a1] text-white rounded-lg shadow-xs transition-colors cursor-pointer"
          >
            {{ isEditing ? 'Simpan Perubahan' : 'Simpan Supplier' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- MODAL DETAIL SUPPLIER -->
    <Modal
      v-model="detailModalOpen"
      title="Detail Profil Supplier"
      subtitle="Informasi lengkap data rekanan pengadaan barang CV Alpharo"
      max-width="2xl"
    >
      <div v-if="selectedSupplier" class="space-y-4">
        <!-- Top Info Header -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 bg-blue-100 text-[#1e40af] font-mono font-bold rounded text-xs">
                {{ selectedSupplier.code || `SUP${String(selectedSupplier.id).padStart(5, '0')}` }}
              </span>
              <span
                :class="[
                  selectedSupplier.status === 'Nonaktif' ? 'bg-slate-200 text-slate-600' : 'bg-emerald-100 text-emerald-800',
                  'px-2 py-0.5 rounded text-[11px] font-semibold'
                ]"
              >
                {{ selectedSupplier.status || 'Aktif' }}
              </span>
            </div>
            <h3 class="text-base font-bold text-slate-900 mt-1.5">{{ selectedSupplier.name }}</h3>
          </div>

          <!-- Quick Action PO -->
          <button
            type="button"
            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs shadow-xs transition-colors cursor-pointer"
            @click="goToPurchaseCreate(selectedSupplier)"
          >
            <ShoppingBag class="w-3.5 h-3.5" />
            <span>Buat PO Pembelian</span>
          </button>
        </div>

        <!-- Detail Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
          <div class="p-3 bg-white border border-slate-200 rounded-lg">
            <span class="text-slate-400 block text-[11px] font-medium">Contact Person (PIC)</span>
            <span class="font-bold text-slate-800 text-sm mt-0.5 block">
              {{ selectedSupplier.contactPerson || selectedSupplier.contact_person || '-' }}
            </span>
          </div>

          <div class="p-3 bg-white border border-slate-200 rounded-lg">
            <span class="text-slate-400 block text-[11px] font-medium">Nomor HP / Telepon</span>
            <span class="font-bold text-slate-800 text-sm mt-0.5 block font-mono">
              {{ selectedSupplier.phone || '-' }}
            </span>
          </div>

          <div class="p-3 bg-white border border-slate-200 rounded-lg">
            <span class="text-slate-400 block text-[11px] font-medium">Email Resmi</span>
            <span class="font-semibold text-slate-800 mt-0.5 block">
              {{ selectedSupplier.email || '-' }}
            </span>
          </div>

          <div class="p-3 bg-white border border-slate-200 rounded-lg">
            <span class="text-slate-400 block text-[11px] font-medium">NPWP Supplier</span>
            <span class="font-semibold text-slate-800 mt-0.5 block font-mono">
              {{ selectedSupplier.npwp || '-' }}
            </span>
          </div>

          <!-- Bank Account Info -->
          <div class="sm:col-span-2 p-3 bg-slate-50/70 border border-slate-200 rounded-lg">
            <span class="text-slate-500 block text-[11px] font-bold uppercase tracking-wider mb-1">Informasi Pembayaran Bank (Finance)</span>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-1">
              <div>
                <span class="text-slate-400 text-[10px] block">Bank:</span>
                <span class="font-bold text-slate-800">{{ selectedSupplier.bankName || selectedSupplier.bank_name || '-' }}</span>
              </div>
              <div>
                <span class="text-slate-400 text-[10px] block">No Rekening:</span>
                <span class="font-mono font-bold text-slate-800">{{ selectedSupplier.bankAccountNumber || selectedSupplier.bank_account_number || '-' }}</span>
              </div>
              <div>
                <span class="text-slate-400 text-[10px] block">Atas Nama:</span>
                <span class="font-semibold text-slate-800">{{ selectedSupplier.bankAccountName || selectedSupplier.bank_account_name || selectedSupplier.name }}</span>
              </div>
            </div>
          </div>

          <!-- Alamat -->
          <div class="sm:col-span-2 p-3 bg-white border border-slate-200 rounded-lg">
            <span class="text-slate-400 block text-[11px] font-medium">Alamat Kantor / Gudang</span>
            <p class="text-slate-700 leading-relaxed mt-0.5">
              {{ selectedSupplier.address || '-' }}
            </p>
          </div>

          <!-- Catatan -->
          <div v-if="selectedSupplier.notes" class="sm:col-span-2 p-3 bg-white border border-slate-200 rounded-lg">
            <span class="text-slate-400 block text-[11px] font-medium">Catatan / Spesialisasi Pengadaan</span>
            <p class="text-slate-700 leading-relaxed mt-0.5">
              {{ selectedSupplier.notes }}
            </p>
          </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-200">
          <div class="flex items-center gap-2">
            <a
              v-if="selectedSupplier.phone"
              :href="`https://wa.me/${formatWhatsAppNumber(selectedSupplier.phone)}`"
              target="_blank"
              class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-semibold transition-colors"
            >
              <span>Hubungi WhatsApp</span>
            </a>
            <a
              v-if="selectedSupplier.email"
              :href="`mailto:${selectedSupplier.email}`"
              class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-semibold transition-colors"
            >
              <span>Kirim Email</span>
            </a>
          </div>

          <div class="flex items-center gap-2">
            <button
              type="button"
              class="px-3.5 py-1.5 bg-[#eab308] hover:bg-[#ca8a04] text-white font-bold rounded-lg text-xs transition-colors cursor-pointer"
              @click="openEditFromDetail"
            >
              Edit Data
            </button>
            <button
              type="button"
              class="px-4 py-1.5 border border-slate-300 text-slate-700 rounded-lg text-xs hover:bg-slate-50 transition-colors cursor-pointer"
              @click="detailModalOpen = false"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </Modal>

    <!-- MODAL CONFIRM DELETE -->
    <Modal
      v-model="deleteModalOpen"
      title="Konfirmasi Hapus Supplier"
      max-width="md"
    >
      <div v-if="deletingSupplier" class="space-y-3">
        <p class="text-xs text-slate-600 leading-relaxed">
          Apakah Anda yakin ingin menghapus data supplier
          <strong class="text-slate-900">{{ deletingSupplier.name }}</strong> ({{ deletingSupplier.code }})?
        </p>
        <p class="text-[11px] text-amber-700 bg-amber-50 p-2.5 rounded-lg border border-amber-200">
          Data supplier yang sudah memiliki histori transaksi pembelian (PO) disarankan untuk dinonaktifkan daripada dihapus permanen.
        </p>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button
            type="button"
            class="px-3.5 py-1.5 border border-slate-300 text-slate-700 rounded-lg text-xs hover:bg-slate-50 transition-colors cursor-pointer"
            @click="deleteModalOpen = false"
          >
            Batal
          </button>
          <button
            type="button"
            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-xs transition-colors cursor-pointer"
            @click="executeDelete"
          >
            Hapus Supplier
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
import api from '../../services/api';
import * as XLSX from 'xlsx';
import {
  Truck,
  Plus,
  Search,
  RotateCcw,
  FileSpreadsheet,
  Edit3,
  Trash2,
  Eye,
  ShoppingBag,
  Info,
  X,
} from 'lucide-vue-next';

const router = useRouter();
const toast = useToast();

// State
const isLoading = ref(false);
const showProbisInfo = ref(false);
const suppliersList = ref([]);

// Search Query (Matching placeholder: Cari ID / nama / alamat / kontak / no HP / email / NPWP)
const searchQuery = ref('');
const appliedSearch = ref('');

// Sorting: default sorted by code ascending
const sortCol = ref('code');
const sortDir = ref('asc');

// Pagination: matching screenshot default 10 per page
const currentPage = ref(1);
const perPage = ref(10);

// Modals State
const supplierModalOpen = ref(false);
const detailModalOpen = ref(false);
const deleteModalOpen = ref(false);
const isEditing = ref(false);
const selectedSupplier = ref(null);
const deletingSupplier = ref(null);

// Form State
const supplierForm = ref({
  id: null,
  code: '',
  name: '',
  contactPerson: '',
  phone: '',
  email: '',
  address: '',
  npwp: '',
  bankName: '',
  bankAccountNumber: '',
  bankAccountName: '',
  status: 'Aktif',
  notes: '',
});

// Helper: load suppliers from API or store
const loadSuppliers = async () => {
  isLoading.value = true;
  try {
    const res = await api.get('/suppliers?per_page=all');
    if (res.data?.success && Array.isArray(res.data.data) && res.data.data.length > 0) {
      suppliersList.value = res.data.data.map((s) => ({
        id: s.id,
        code: s.code,
        name: s.name,
        contactPerson: s.contact_person || s.contactPerson || '',
        phone: s.phone || '',
        email: s.email || '',
        address: s.address || '',
        npwp: s.npwp || '',
        bankName: s.bank_name || s.bankName || '',
        bankAccountNumber: s.bank_account_number || s.bankAccountNumber || '',
        bankAccountName: s.bank_account_name || s.bankAccountName || s.name,
        status: s.is_active === false ? 'Nonaktif' : (s.status || 'Aktif'),
        notes: s.notes || '',
      }));
      // Sync to store
      store.suppliers = [...suppliersList.value];
    } else {
      throw new Error('API returned empty supplier list');
    }
  } catch (err) {
    // Fallback to store.suppliers
    if (Array.isArray(store.suppliers) && store.suppliers.length > 0) {
      suppliersList.value = [...store.suppliers];
    } else {
      suppliersList.value = [];
    }
  } finally {
    isLoading.value = false;
  }
};

// Filtered suppliers based on search query across ID, nama, alamat, kontak, no HP, email, NPWP
const filteredSuppliers = computed(() => {
  let list = [...suppliersList.value];
  const query = (appliedSearch.value || '').trim().toLowerCase();

  if (query) {
    list = list.filter((s) => {
      const code = (s.code || '').toLowerCase();
      const name = (s.name || '').toLowerCase();
      const address = (s.address || '').toLowerCase();
      const contact = (s.contactPerson || s.contact_person || '').toLowerCase();
      const phone = (s.phone || '').toLowerCase();
      const email = (s.email || '').toLowerCase();
      const npwp = (s.npwp || '').toLowerCase();

      return (
        code.includes(query) ||
        name.includes(query) ||
        address.includes(query) ||
        contact.includes(query) ||
        phone.includes(query) ||
        email.includes(query) ||
        npwp.includes(query)
      );
    });
  }

  // Sorting
  list.sort((a, b) => {
    let aVal = a[sortCol.value] ?? '';
    let bVal = b[sortCol.value] ?? '';

    if (sortCol.value === 'contactPerson') {
      aVal = a.contactPerson || a.contact_person || '';
      bVal = b.contactPerson || b.contact_person || '';
    }

    if (typeof aVal === 'string') {
      const comp = aVal.localeCompare(bVal, undefined, { numeric: true, sensitivity: 'base' });
      return sortDir.value === 'asc' ? comp : -comp;
    }

    if (aVal < bVal) return sortDir.value === 'asc' ? -1 : 1;
    if (aVal > bVal) return sortDir.value === 'asc' ? 1 : -1;
    return 0;
  });

  return list;
});

// Pagination calculations
const totalItems = computed(() => filteredSuppliers.value.length);
const totalPages = computed(() => Math.ceil(totalItems.value / perPage.value) || 1);
const startItemIndex = computed(() => (currentPage.value - 1) * perPage.value + 1);
const endItemIndex = computed(() => Math.min(currentPage.value * perPage.value, totalItems.value));

const paginatedSuppliers = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredSuppliers.value.slice(start, start + perPage.value);
});

// Dynamic pagination page numbers (display max 5 pages)
const displayedPages = computed(() => {
  const pages = [];
  const total = totalPages.value;
  const cur = currentPage.value;

  let start = Math.max(1, cur - 2);
  let end = Math.min(total, start + 4);

  if (end - start < 4) {
    start = Math.max(1, end - 4);
  }

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  return pages;
});

// Search Actions
const handleSearch = () => {
  appliedSearch.value = searchQuery.value;
  currentPage.value = 1;
};

const clearSearch = () => {
  searchQuery.value = '';
  appliedSearch.value = '';
  currentPage.value = 1;
};

const handleReset = () => {
  searchQuery.value = '';
  appliedSearch.value = '';
  sortCol.value = 'code';
  sortDir.value = 'asc';
  currentPage.value = 1;
  perPage.value = 10;
};

// Sort Handler
const sortByCol = (col) => {
  if (sortCol.value === col) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortCol.value = col;
    sortDir.value = 'asc';
  }
};

const handlePerPageChange = () => {
  currentPage.value = 1;
};

// Auto generate next supplier code
const generateNextSupplierCode = () => {
  let nextNum = 1;
  if (suppliersList.value.length > 0) {
    const nums = suppliersList.value.map((s) => {
      const m = (s.code || '').match(/(\d+)/);
      return m ? parseInt(m[1], 10) : (s.id || 0);
    });
    nextNum = Math.max(...nums, 0) + 1;
  }
  return `SUP${String(nextNum).padStart(5, '0')}`;
};

// Modal Openers
const openAddModal = () => {
  isEditing.value = false;
  supplierForm.value = {
    id: null,
    code: generateNextSupplierCode(),
    name: '',
    contactPerson: '',
    phone: '',
    email: '',
    address: '',
    npwp: '',
    bankName: '',
    bankAccountNumber: '',
    bankAccountName: '',
    status: 'Aktif',
    notes: '',
  };
  supplierModalOpen.value = true;
};

const openEditModal = (sup) => {
  isEditing.value = true;
  supplierForm.value = {
    id: sup.id,
    code: sup.code || '',
    name: sup.name || '',
    contactPerson: sup.contactPerson || sup.contact_person || '',
    phone: sup.phone || '',
    email: sup.email || '',
    address: sup.address || '',
    npwp: sup.npwp || '',
    bankName: sup.bankName || sup.bank_name || '',
    bankAccountNumber: sup.bankAccountNumber || sup.bank_account_number || '',
    bankAccountName: sup.bankAccountName || sup.bank_account_name || sup.name,
    status: sup.status || 'Aktif',
    notes: sup.notes || '',
  };
  supplierModalOpen.value = true;
};

const openDetailModal = (sup) => {
  selectedSupplier.value = sup;
  detailModalOpen.value = true;
};

const openEditFromDetail = () => {
  const sup = selectedSupplier.value;
  detailModalOpen.value = false;
  if (sup) openEditModal(sup);
};

const confirmDelete = (sup) => {
  deletingSupplier.value = sup;
  deleteModalOpen.value = true;
};

// Save Supplier (Create or Update)
const saveSupplier = async () => {
  if (!supplierForm.value.name.trim()) {
    toast.error('Nama Supplier wajib diisi.');
    return;
  }

  const payload = {
    code: supplierForm.value.code,
    name: supplierForm.value.name,
    contact_person: supplierForm.value.contactPerson,
    phone: supplierForm.value.phone,
    email: supplierForm.value.email,
    address: supplierForm.value.address,
    npwp: supplierForm.value.npwp,
    bank_name: supplierForm.value.bankName,
    bank_account_number: supplierForm.value.bankAccountNumber,
    bank_account_name: supplierForm.value.bankAccountName,
    is_active: supplierForm.value.status === 'Aktif',
    notes: supplierForm.value.notes,
  };

  try {
    if (isEditing.value && supplierForm.value.id) {
      // API Update
      await api.put(`/suppliers/${supplierForm.value.id}`, payload).catch(() => {});
      // Store Update
      storeActions.updateSupplier(supplierForm.value.id, {
        ...supplierForm.value,
      });
      toast.success(`Data supplier ${supplierForm.value.name} berhasil diperbarui.`);
    } else {
      // API Create
      await api.post('/suppliers', payload).catch(() => {});
      // Store Add
      storeActions.addSupplier({
        ...supplierForm.value,
      });
      toast.success(`Supplier baru ${supplierForm.value.name} berhasil ditambahkan.`);
    }
    supplierModalOpen.value = false;
    await loadSuppliers();
  } catch (e) {
    console.error('Error saving supplier:', e);
    toast.error('Terjadi kesalahan saat menyimpan data supplier.');
  }
};

// Execute Delete
const executeDelete = async () => {
  if (!deletingSupplier.value) return;
  const target = deletingSupplier.value;
  try {
    if (target.id) {
      await api.delete(`/suppliers/${target.id}`).catch(() => {});
    }
    storeActions.deleteSupplier(target.id || target.code);
    toast.success(`Supplier ${target.name} berhasil dihapus.`);
    deleteModalOpen.value = false;
    deletingSupplier.value = null;
    await loadSuppliers();
  } catch (e) {
    console.error('Error deleting supplier:', e);
    toast.error('Gagal menghapus supplier.');
  }
};

// Quick Navigation to Purchase Order Creation for this supplier (Conforms to PROBIS TO-BE)
const goToPurchaseCreate = (sup) => {
  if (detailModalOpen.value) detailModalOpen.value = false;
  router.push({
    path: '/finance/purchases/create',
    query: {
      supplier: sup.name,
      supplier_code: sup.code,
    },
  });
};

// Format WhatsApp Link
const formatWhatsAppNumber = (rawPhone) => {
  if (!rawPhone) return '';
  let cleaned = rawPhone.replace(/\D/g, '');
  if (cleaned.startsWith('0')) {
    cleaned = '62' + cleaned.slice(1);
  }
  return cleaned;
};

// Export to Excel (Using SheetJS xlsx)
const exportToExcel = () => {
  const listToExport = filteredSuppliers.value.length > 0 ? filteredSuppliers.value : suppliersList.value;

  if (listToExport.length === 0) {
    toast.error('Tidak ada data supplier untuk diexport.');
    return;
  }

  const dataToExport = listToExport.map((s, idx) => ({
    'No': idx + 1,
    'ID Supplier': s.code || `SUP${String(s.id).padStart(5, '0')}`,
    'Nama Supplier': s.name,
    'Alamat': s.address || '-',
    'Contact Person': s.contactPerson || s.contact_person || '-',
    'No HP': s.phone || '-',
    'Email': s.email || '-',
    'NPWP': s.npwp || '-',
    'Bank': s.bankName || s.bank_name || '-',
    'No Rekening': s.bankAccountNumber || s.bank_account_number || '-',
    'Atas Nama': s.bankAccountName || s.bank_account_name || s.name,
    'Status': s.status || 'Aktif',
    'Catatan': s.notes || '-',
  }));

  const ws = XLSX.utils.json_to_sheet(dataToExport);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Data Supplier');

  // Set column widths
  ws['!cols'] = [
    { wch: 6 },
    { wch: 14 },
    { wch: 35 },
    { wch: 45 },
    { wch: 25 },
    { wch: 18 },
    { wch: 30 },
    { wch: 22 },
    { wch: 15 },
    { wch: 20 },
    { wch: 30 },
    { wch: 12 },
    { wch: 35 },
  ];

  const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
  XLSX.writeFile(wb, `Data_Supplier_Alpharo_${dateStr}.xlsx`);
  toast.success(`Berhasil mengexport ${listToExport.length} data supplier ke Excel.`);
};

// Lifecycle
onMounted(async () => {
  await loadSuppliers();
});
</script>

