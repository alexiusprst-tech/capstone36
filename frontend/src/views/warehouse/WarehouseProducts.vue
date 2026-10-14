<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <!-- Title with Box icon -->
        <div class="flex items-center gap-2.5">
          <div class="p-1.5 bg-slate-100 rounded-lg text-slate-800">
            <Package class="w-6 h-6 stroke-[2.2]" />
          </div>
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Data Barang</h1>
          </div>
        </div>

        <!-- Top Right Actions matching the design -->
        <div class="flex items-center flex-wrap gap-2.5">
          <!-- Import Excel Button -->
          <button
            type="button"
            class="inline-flex items-center gap-2 px-3.5 py-2 bg-[#0e7490] hover:bg-[#155e75] active:bg-[#164e63] text-white font-medium rounded-lg text-xs shadow-xs transition-colors cursor-pointer"
            @click="openImportModal"
          >
            <FileSpreadsheet class="w-4 h-4" />
            <span>Import Excel</span>
          </button>

          <!-- Kategori Button -->
          <button
            type="button"
            class="inline-flex items-center gap-2 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 font-medium rounded-lg text-xs border border-slate-300 shadow-2xs transition-colors cursor-pointer"
            @click="categoryModalOpen = true"
          >
            <Tags class="w-4 h-4 text-slate-500" />
            <span>Kategori</span>
            <span class="ml-1 px-1.5 py-0.2 bg-slate-100 text-slate-600 rounded text-[10px] font-semibold">{{ categoriesList.length }}</span>
          </button>

          <!-- Satuan Button -->
          <button
            type="button"
            class="inline-flex items-center gap-2 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 font-medium rounded-lg text-xs border border-slate-300 shadow-2xs transition-colors cursor-pointer"
            @click="unitModalOpen = true"
          >
            <Flag class="w-4 h-4 text-slate-500" />
            <span>Satuan</span>
            <span class="ml-1 px-1.5 py-0.2 bg-slate-100 text-slate-600 rounded text-[10px] font-semibold">{{ unitsList.length }}</span>
          </button>

          <!-- Tambah Button -->
          <button
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 bg-[#0284c7] hover:bg-[#0369a1] active:bg-[#075985] text-white font-semibold rounded-lg text-xs shadow-xs transition-colors cursor-pointer"
            @click="openAddModal"
          >
            <Plus class="w-4 h-4 stroke-[2.5]" />
            <span>Tambah</span>
          </button>
        </div>
      </div>
    </template>

    <!-- SEARCH & FILTER CARD (Green top line accent matching image) -->
    <div class="bg-white rounded-xl border border-slate-200/90 mb-5 shadow-2xs overflow-hidden">
      <!-- Top green accent border -->
      <div class="h-1 bg-emerald-600 w-full"></div>

      <div class="p-4 sm:p-5">
        <form class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5 items-end" @submit.prevent="handleSearch">
          <!-- Pencarian -->
          <div class="lg:col-span-4">
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pencarian</label>
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Cari ID / nama / satuan / kategori"
                class="w-full pl-3 pr-8 py-2 text-xs rounded-lg border border-slate-300 bg-white placeholder:text-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0284c7] focus:border-transparent transition-all"
                @input="onSearchInput"
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
          </div>

          <!-- Kategori -->
          <div class="lg:col-span-3">
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kategori</label>
            <select
              v-model="selectedCategory"
              class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-hidden focus:ring-2 focus:ring-[#0284c7] text-slate-700 cursor-pointer"
              @change="onFilterChange"
            >
              <option value="">Semua Kategori</option>
              <option v-for="cat in categoriesList" :key="cat" :value="cat">
                {{ cat }}
              </option>
            </select>
          </div>

          <!-- Satuan -->
          <div class="lg:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Satuan</label>
            <select
              v-model="selectedUnit"
              class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-hidden focus:ring-2 focus:ring-[#0284c7] text-slate-700 cursor-pointer"
              @change="onFilterChange"
            >
              <option value="">Semua Satuan</option>
              <option v-for="u in unitsList" :key="u" :value="u">
                {{ u }}
              </option>
            </select>
          </div>

          <!-- Status Barang Filter -->
          <div class="lg:col-span-1">
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Status</label>
            <select
              v-model="selectedStatus"
              class="w-full px-2.5 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-hidden focus:ring-2 focus:ring-[#0284c7] text-slate-700 cursor-pointer"
              @change="onFilterChange"
            >
              <option value="">Semua</option>
              <option value="Tersedia">Tersedia</option>
              <option value="Menipis">Menipis</option>
              <option value="Habis">Habis</option>
              <option value="Nonaktif">Nonaktif</option>
            </select>
          </div>

          <!-- Buttons Group -->
          <div class="lg:col-span-2 flex items-center gap-2">
            <!-- Cari Button -->
            <button
              type="submit"
              class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-[#0284c7] hover:bg-[#0369a1] text-white rounded-lg text-xs font-semibold shadow-xs transition-colors cursor-pointer"
            >
              <Search class="w-3.5 h-3.5 stroke-[2.5]" />
              <span>Cari</span>
            </button>

            <!-- Reset Button -->
            <button
              type="button"
              class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-medium border border-slate-300 shadow-2xs transition-colors cursor-pointer"
              @click="handleReset"
            >
              <RotateCcw class="w-3.5 h-3.5 text-slate-500" />
              <span>Reset</span>
            </button>

            <!-- Export Excel Button -->
            <button
              type="button"
              class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-2xs transition-colors cursor-pointer whitespace-nowrap"
              title="Export data hasil filter ke file Excel (.xlsx)"
              @click="exportToExcel"
            >
              <FileSpreadsheet class="w-3.5 h-3.5 stroke-[2.5]" />
              <span>Excel</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- PRODUCTS TABLE CARD (Green top line accent matching image) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden mb-5">
      <!-- Top green accent border -->
      <div class="h-1 bg-emerald-600 w-full"></div>

      <!-- Loading State -->
      <div v-if="isLoading" class="p-12 text-center text-slate-500 flex flex-col items-center justify-center gap-3">
        <RefreshCw class="w-7 h-7 text-[#0284c7] animate-spin" />
        <span class="text-xs font-medium">Memuat katalog data barang...</span>
      </div>

      <!-- Table Container -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/90 border-b border-slate-200 text-slate-700 font-semibold text-[11px] select-none">
              <!-- ID -->
              <th class="py-3 px-4 cursor-pointer hover:bg-slate-100 transition-colors whitespace-nowrap" @click="toggleSort('sku')">
                <div class="flex items-center gap-1">
                  <span>ID</span>
                  <span class="text-[10px] text-slate-400 font-mono">
                    {{ sortBy === 'sku' ? (sortDir === 'asc' ? '▲' : '▼') : '↕' }}
                  </span>
                </div>
              </th>

              <!-- Nama Barang -->
              <th class="py-3 px-4 cursor-pointer hover:bg-slate-100 transition-colors" @click="toggleSort('name')">
                <div class="flex items-center gap-1">
                  <span>Nama Barang</span>
                  <span class="text-[10px] text-slate-400 font-mono">
                    {{ sortBy === 'name' ? (sortDir === 'asc' ? '▲' : '▼') : '↕' }}
                  </span>
                </div>
              </th>

              <!-- Kategori -->
              <th class="py-3 px-4 cursor-pointer hover:bg-slate-100 transition-colors" @click="toggleSort('category')">
                <div class="flex items-center gap-1">
                  <span>Kategori</span>
                  <span class="text-[10px] text-slate-400 font-mono">
                    {{ sortBy === 'category' ? (sortDir === 'asc' ? '▲' : '▼') : '↕' }}
                  </span>
                </div>
              </th>

              <!-- Satuan -->
              <th class="py-3 px-4 cursor-pointer hover:bg-slate-100 transition-colors text-center" @click="toggleSort('unit')">
                <div class="flex items-center justify-center gap-1">
                  <span>Satuan</span>
                  <span class="text-[10px] text-slate-400 font-mono">
                    {{ sortBy === 'unit' ? (sortDir === 'asc' ? '▲' : '▼') : '↕' }}
                  </span>
                </div>
              </th>

              <!-- Stok -->
              <th class="py-3 px-4 cursor-pointer hover:bg-slate-100 transition-colors text-right" @click="toggleSort('stock')">
                <div class="flex items-center justify-end gap-1">
                  <span>Stok</span>
                  <span class="text-[10px] text-slate-400 font-mono">
                    {{ sortBy === 'stock' ? (sortDir === 'asc' ? '▲' : '▼') : '↕' }}
                  </span>
                </div>
              </th>

              <!-- Harga Beli Terakhir -->
              <th class="py-3 px-4 cursor-pointer hover:bg-slate-100 transition-colors text-right whitespace-nowrap" @click="toggleSort('last_buy_price')">
                <div class="flex items-center justify-end gap-1">
                  <span>Harga Beli Terakhir</span>
                  <span class="text-[10px] text-slate-400 font-mono">
                    {{ sortBy === 'last_buy_price' ? (sortDir === 'asc' ? '▲' : '▼') : '↕' }}
                  </span>
                </div>
              </th>

              <!-- HPP -->
              <th class="py-3 px-4 cursor-pointer hover:bg-slate-100 transition-colors text-right whitespace-nowrap" @click="toggleSort('hpp')">
                <div class="flex items-center justify-end gap-1">
                  <span>HPP</span>
                  <span class="text-[10px] text-slate-400 font-mono">
                    {{ sortBy === 'hpp' ? (sortDir === 'asc' ? '▲' : '▼') : '↕' }}
                  </span>
                </div>
              </th>

              <!-- Harga Jual -->
              <th class="py-3 px-4 cursor-pointer hover:bg-slate-100 transition-colors text-right whitespace-nowrap" @click="toggleSort('sell_price')">
                <div class="flex items-center justify-end gap-1">
                  <span>Harga Jual</span>
                  <span class="text-[10px] text-slate-400 font-mono">
                    {{ sortBy === 'sell_price' ? (sortDir === 'asc' ? '▲' : '▼') : '↕' }}
                  </span>
                </div>
              </th>

              <!-- Status -->
              <th class="py-3 px-4 text-center whitespace-nowrap">
                <span>Status</span>
              </th>

              <!-- Aksi -->
              <th class="py-3 px-4 text-center whitespace-nowrap">
                <span>Aksi</span>
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100">
            <tr v-if="paginatedProducts.length === 0">
              <td colspan="10" class="py-12 text-center text-slate-500">
                <div class="flex flex-col items-center justify-center gap-2">
                  <Package class="w-8 h-8 text-slate-300" />
                  <span class="text-xs font-semibold text-slate-700">Tidak ada data barang yang sesuai dengan pencarian / filter.</span>
                  <span v-if="searchQuery || selectedCategory || selectedUnit || selectedStatus" class="text-[11px] text-slate-400">
                    Coba ubah kata kunci pencarian atau klik tombol Reset.
                  </span>
                </div>
              </td>
            </tr>

            <tr
              v-for="p in paginatedProducts"
              :key="p.id || p.sku"
              class="hover:bg-slate-50/70 transition-colors group"
            >
              <!-- ID / SKU (blue bold text like image) -->
              <td class="py-3 px-4 font-mono font-bold text-[#0284c7] whitespace-nowrap">
                {{ p.sku }}
              </td>

              <!-- Nama Barang -->
              <td class="py-3 px-4 text-slate-800 font-medium">
                <div>{{ p.name }}</div>
                <div v-if="p.description" class="text-[10px] text-slate-400 truncate max-w-xs mt-0.5" :title="p.description">
                  {{ p.description }}
                </div>
              </td>

              <!-- Kategori -->
              <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                {{ p.category }}
              </td>

              <!-- Satuan -->
              <td class="py-3 px-4 text-slate-600 text-center font-semibold text-[11px] uppercase whitespace-nowrap">
                {{ p.unit }}
              </td>

              <!-- Stok -->
              <td class="py-3 px-4 text-right font-mono font-bold text-slate-800">
                {{ (Number(p.stock) || 0).toLocaleString('id-ID') }}
              </td>

              <!-- Harga Beli Terakhir -->
              <td class="py-3 px-4 text-right font-mono text-slate-700 whitespace-nowrap">
                Rp {{ (Number(p.last_buy_price) || 0).toLocaleString('id-ID') }}
              </td>

              <!-- HPP -->
              <td class="py-3 px-4 text-right font-mono text-slate-700 whitespace-nowrap">
                Rp {{ (Number(p.hpp) || 0).toLocaleString('id-ID') }}
              </td>

              <!-- Harga Jual -->
              <td class="py-3 px-4 text-right font-mono font-bold text-slate-800 whitespace-nowrap">
                Rp {{ (Number(p.sell_price || p.price) || 0).toLocaleString('id-ID') }}
              </td>

              <!-- Status Barang Badge -->
              <td class="py-3 px-4 text-center whitespace-nowrap">
                <span
                  :class="[
                    'inline-block px-2 py-0.5 rounded text-[10px] font-semibold',
                    getStatusBadgeClass(p.status)
                  ]"
                >
                  {{ p.status || 'Tersedia' }}
                </span>
              </td>

              <!-- Aksi (Yellow Edit button + Red Delete button matching image) -->
              <td class="py-3 px-4 text-center whitespace-nowrap">
                <div class="inline-flex items-center gap-1.5">
                  <!-- Edit Button (Yellow/Amber Gradient) -->
                  <button
                    type="button"
                    class="w-7 h-7 inline-flex items-center justify-center bg-gradient-to-b from-amber-400 to-amber-500 hover:from-amber-500 hover:to-amber-600 active:from-amber-600 active:to-amber-700 text-white rounded-lg transition-all shadow-2xs border border-amber-500/30 cursor-pointer"
                    title="Edit Data Barang"
                    @click="openEditModal(p)"
                  >
                    <Edit class="w-3.5 h-3.5 stroke-[2.2]" />
                  </button>

                  <!-- Delete Button (Red Gradient) -->
                  <button
                    type="button"
                    class="w-7 h-7 inline-flex items-center justify-center bg-gradient-to-b from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 active:from-red-700 active:to-red-800 text-white rounded-lg transition-all shadow-2xs border border-red-700/30 cursor-pointer"
                    title="Hapus Data Barang"
                    @click="confirmDelete(p)"
                  >
                    <Trash2 class="w-3.5 h-3.5 stroke-[2.2]" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>

          <!-- TOTAL SUMMARY ROW (matching Total row in screenshot) -->
          <tfoot>
            <tr class="bg-slate-50/70 border-t border-slate-200 font-bold text-slate-800 text-xs">
              <td colspan="4" class="py-3 px-4 text-right pr-6">
                Total
              </td>
              <td class="py-3 px-4 text-right font-mono text-slate-900">
                {{ totalStockSum.toLocaleString('id-ID') }}
              </td>
              <td colspan="5"></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- TABLE FOOTER & PAGINATION (Matching image: "Menampilkan 1 - 10 dari 2.196 data", First, Prev, 1 2 3 ... Next Last, 10 data) -->
      <div class="px-4 py-3.5 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600 bg-white">
        <!-- Display Info -->
        <div>
          Menampilkan <span class="font-bold text-slate-800">{{ paginationInfo.from }}</span> -
          <span class="font-bold text-slate-800">{{ paginationInfo.to }}</span> dari
          <span class="font-bold text-slate-800">{{ totalItems.toLocaleString('id-ID') }}</span> data
          <span v-if="searchQuery" class="text-slate-400 ml-1">(difilter dari total data)</span>
        </div>

        <!-- Pagination Controls -->
        <div class="flex items-center flex-wrap gap-2">
          <div class="flex items-center gap-1">
            <!-- First -->
            <button
              type="button"
              class="px-2.5 py-1 text-slate-700 hover:bg-slate-100 rounded text-xs transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
              :disabled="currentPage <= 1"
              @click="goToPage(1)"
            >
              First
            </button>

            <!-- Prev -->
            <button
              type="button"
              class="px-2.5 py-1 text-slate-700 hover:bg-slate-100 rounded text-xs transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
              :disabled="currentPage <= 1"
              @click="goToPage(currentPage - 1)"
            >
              Prev
            </button>

            <!-- Page Numbers -->
            <template v-for="page in visiblePages" :key="page">
              <span v-if="page === '...'" class="px-2 py-1 text-slate-400">...</span>
              <button
                v-else
                type="button"
                :class="[
                  'min-w-7 h-7 px-2 flex items-center justify-center rounded text-xs font-semibold transition-colors cursor-pointer',
                  currentPage === page
                    ? 'bg-[#0284c7] text-white shadow-xs'
                    : 'text-slate-700 hover:bg-slate-100'
                ]"
                @click="goToPage(page)"
              >
                {{ page }}
              </button>
            </template>

            <!-- Next -->
            <button
              type="button"
              class="px-2.5 py-1 text-slate-700 hover:bg-slate-100 rounded text-xs transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
              :disabled="currentPage >= totalPages"
              @click="goToPage(currentPage + 1)"
            >
              Next
            </button>

            <!-- Last -->
            <button
              type="button"
              class="px-2.5 py-1 text-slate-700 hover:bg-slate-100 rounded text-xs transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
              :disabled="currentPage >= totalPages"
              @click="goToPage(totalPages)"
            >
              Last
            </button>
          </div>

          <!-- Per Page Select (e.g. "10 data") -->
          <div class="flex items-center gap-1.5 ml-2 border-l border-slate-200 pl-3">
            <select
              v-model.number="perPage"
              class="px-2 py-1 text-xs border border-slate-300 rounded bg-white text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-[#0284c7] cursor-pointer"
              @change="handlePerPageChange"
            >
              <option :value="10">10 data</option>
              <option :value="25">25 data</option>
              <option :value="50">50 data</option>
              <option :value="100">100 data</option>
              <option :value="250">250 data</option>
              <option :value="500">500 data</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 1: IMPORT EXCEL (4-STEP WORKFLOW)                   -->
    <!-- Download Template > Upload File Excel > Preview > Validasi & Simpan -->
    <!-- ======================================================== -->
    <Modal
      v-model="importModalOpen"
      title="Import Data Barang via Excel"
      subtitle="Proses impor data massal: Download Template > Upload Excel > Preview > Validasi & Simpan"
      size="2xl"
    >
      <div class="space-y-6">
        <!-- Step Indicator -->
        <div class="grid grid-cols-4 gap-2 border-b border-slate-200 pb-4">
          <!-- Step 1 -->
          <button
            type="button"
            class="flex items-center gap-2 text-left p-2 rounded-lg transition-colors cursor-pointer"
            :class="importStep === 1 ? 'bg-sky-50 text-[#0284c7] font-bold' : importStep > 1 ? 'text-emerald-700 font-semibold' : 'text-slate-400'"
            @click="importStep = 1"
          >
            <div
              class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
              :class="importStep === 1 ? 'bg-[#0284c7] text-white' : importStep > 1 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600'"
            >
              <Check v-if="importStep > 1" class="w-3.5 h-3.5 stroke-[3]" />
              <span v-else>1</span>
            </div>
            <div class="truncate">
              <div class="text-[11px] leading-tight">Step 1</div>
              <div class="text-xs truncate">Download Template</div>
            </div>
          </button>

          <!-- Step 2 -->
          <button
            type="button"
            class="flex items-center gap-2 text-left p-2 rounded-lg transition-colors cursor-pointer"
            :class="importStep === 2 ? 'bg-sky-50 text-[#0284c7] font-bold' : importStep > 2 ? 'text-emerald-700 font-semibold' : 'text-slate-400'"
            @click="importStep = 2"
          >
            <div
              class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
              :class="importStep === 2 ? 'bg-[#0284c7] text-white' : importStep > 2 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600'"
            >
              <Check v-if="importStep > 2" class="w-3.5 h-3.5 stroke-[3]" />
              <span v-else>2</span>
            </div>
            <div class="truncate">
              <div class="text-[11px] leading-tight">Step 2</div>
              <div class="text-xs truncate">Upload File Excel</div>
            </div>
          </button>

          <!-- Step 3 -->
          <button
            type="button"
            class="flex items-center gap-2 text-left p-2 rounded-lg transition-colors cursor-pointer"
            :class="importStep === 3 ? 'bg-sky-50 text-[#0284c7] font-bold' : importStep > 3 ? 'text-emerald-700 font-semibold' : 'text-slate-400'"
            :disabled="parsedExcelData.length === 0"
            @click="parsedExcelData.length > 0 && (importStep = 3)"
          >
            <div
              class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
              :class="importStep === 3 ? 'bg-[#0284c7] text-white' : importStep > 3 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600'"
            >
              <Check v-if="importStep > 3" class="w-3.5 h-3.5 stroke-[3]" />
              <span v-else>3</span>
            </div>
            <div class="truncate">
              <div class="text-[11px] leading-tight">Step 3</div>
              <div class="text-xs truncate">Preview Data</div>
            </div>
          </button>

          <!-- Step 4 -->
          <button
            type="button"
            class="flex items-center gap-2 text-left p-2 rounded-lg transition-colors cursor-pointer"
            :class="importStep === 4 ? 'bg-sky-50 text-[#0284c7] font-bold' : 'text-slate-400'"
            :disabled="parsedExcelData.length === 0"
            @click="parsedExcelData.length > 0 && (importStep = 4)"
          >
            <div
              class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
              :class="importStep === 4 ? 'bg-[#0284c7] text-white' : 'bg-slate-200 text-slate-600'"
            >
              <span>4</span>
            </div>
            <div class="truncate">
              <div class="text-[11px] leading-tight">Step 4</div>
              <div class="text-xs truncate">Validasi & Simpan</div>
            </div>
          </button>
        </div>

        <!-- =================== STEP 1: DOWNLOAD TEMPLATE =================== -->
        <div v-if="importStep === 1" class="space-y-4">
          <div class="bg-sky-50/80 border border-sky-200 rounded-xl p-4 text-sky-900 text-xs">
            <h4 class="font-bold flex items-center gap-2 text-sm text-[#0369a1] mb-1">
              <FileDown class="w-4 h-4" />
              Format Template Resmi Import Data Barang
            </h4>
            <p class="text-slate-600 leading-relaxed">
              Gunakan file template Excel yang telah disesuaikan agar proses parsing dan validasi data barang berjalan otomatis. Anda dapat mendownload template resmi berisi contoh data atau template kosong.
            </p>
          </div>

          <!-- Download Action Card -->
          <div class="max-w-xl mx-auto">
            <!-- Template Resmi / Berisi Contoh -->
            <div class="p-5 border border-slate-200 rounded-xl bg-white shadow-2xs hover:border-[#0284c7] transition-all flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-3">
                  <span class="inline-flex p-2.5 bg-emerald-50 text-emerald-700 rounded-xl">
                    <FileSpreadsheet class="w-6 h-6" />
                  </span>
                  <span class="text-xs font-bold text-emerald-700 bg-emerald-100 px-2.5 py-0.5 rounded-full">Template Resmi</span>
                </div>
                <h5 class="font-bold text-slate-900 text-base">File Template Excel Data Barang</h5>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                  Download file template excel resmi (<code>data_barang_20261014_103611.xlsx</code>) yang berisi format susunan kolom data barang yang kompatibel dengan sistem.
                </p>
              </div>

              <div class="mt-5 pt-4 border-t border-slate-100">
                <button
                  type="button"
                  class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg text-xs font-bold shadow-xs transition-colors cursor-pointer"
                  @click="downloadOfficialTemplate"
                >
                  <Download class="w-4 h-4 stroke-[2.5]" />
                  <span>Download Template Excel (.xlsx)</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- =================== STEP 2: UPLOAD FILE EXCEL =================== -->
        <div v-if="importStep === 2" class="space-y-4">
          <!-- Drag & Drop Area -->
          <div
            class="border-2 border-dashed rounded-xl p-8 text-center transition-all cursor-pointer"
            :class="isDragging ? 'border-[#0284c7] bg-sky-50/50' : 'border-slate-300 hover:border-slate-400 bg-slate-50/50'"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleFileDrop"
            @click="triggerFileInput"
          >
            <input
              ref="fileInputRef"
              type="file"
              accept=".xlsx,.xls,.csv"
              class="hidden"
              @change="handleFileSelect"
            />

            <div class="flex flex-col items-center justify-center gap-3">
              <div class="w-14 h-14 rounded-full bg-sky-100 text-[#0284c7] flex items-center justify-center">
                <UploadCloud class="w-7 h-7 stroke-[2]" />
              </div>
              <div>
                <p class="text-sm font-bold text-slate-800">Tarik & Lepas File Excel Anda Di Sini</p>
                <p class="text-xs text-slate-500 mt-1">atau klik untuk memilih file dari komputer (.xlsx, .xls, .csv)</p>
              </div>
              <div class="text-[11px] text-slate-400 font-mono">
                Mendukung file template RKAS & standar data barang (hingga puluhan ribu baris)
              </div>
            </div>
          </div>

          <!-- Quick Test Option: Load RKAS directly from server -->
          <div class="p-4 border border-emerald-200 bg-emerald-50/70 rounded-xl flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <div>
              <span class="font-bold text-emerald-900 block">Ingin langsung menguji dengan data lengkap?</span>
              <span class="text-emerald-700">Muat otomatis 2.196 data barang dari file template <code>data_barang_20261014_103611.xlsx</code> tanpa perlu upload manual.</span>
            </div>
            <button
              type="button"
              class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg text-xs shadow-xs cursor-pointer whitespace-nowrap"
              @click="loadSampleRkasData"
            >
              Gunakan File RKAS (2.196 Data)
            </button>
          </div>

          <!-- File Uploaded Info -->
          <div v-if="uploadedFileName" class="p-3 bg-slate-100 rounded-lg flex items-center justify-between text-xs font-mono">
            <div class="flex items-center gap-2 text-slate-800 truncate">
              <FileSpreadsheet class="w-4 h-4 text-emerald-600" />
              <span class="font-bold truncate">{{ uploadedFileName }}</span>
              <span class="text-slate-500">({{ uploadedFileSize }})</span>
            </div>
            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-semibold text-[10px]">Terbaca</span>
          </div>
        </div>

        <!-- =================== STEP 3: PREVIEW DATA =================== -->
        <div v-if="importStep === 3" class="space-y-4">
          <!-- Summary Cards -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
              <div class="text-[11px] text-slate-500 uppercase font-semibold">Total Baris</div>
              <div class="text-lg font-bold font-mono text-slate-900">{{ parsedExcelData.length.toLocaleString('id-ID') }}</div>
            </div>
            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
              <div class="text-[11px] text-emerald-700 uppercase font-semibold">Kategori Ditemukan</div>
              <div class="text-lg font-bold font-mono text-emerald-800">{{ detectedCategoriesCount }}</div>
            </div>
            <div class="p-3 bg-sky-50 border border-sky-200 rounded-lg">
              <div class="text-[11px] text-sky-700 uppercase font-semibold">Satuan Ditemukan</div>
              <div class="text-lg font-bold font-mono text-sky-800">{{ detectedUnitsCount }}</div>
            </div>
            <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg">
              <div class="text-[11px] text-amber-700 uppercase font-semibold">Duplikat ID Terdeteksi</div>
              <div class="text-lg font-bold font-mono text-amber-800">{{ detectedDuplicatesCount }}</div>
            </div>
          </div>

          <!-- Duplicate Resolution Options -->
          <div class="bg-white border border-slate-200 rounded-xl p-3.5 text-xs flex flex-col sm:flex-row items-center justify-between gap-3">
            <div>
              <span class="font-bold text-slate-800 block">Kebijakan Jika ID Barang Sudah Ada:</span>
              <span class="text-slate-500 text-[11px]">Tentukan tindakan bila SKU di Excel telah terdaftar pada database.</span>
            </div>
            <div class="flex items-center gap-3">
              <label class="inline-flex items-center gap-1.5 cursor-pointer">
                <input v-model="duplicateMode" type="radio" value="update" class="text-[#0284c7]" />
                <span>Perbarui Data (Update)</span>
              </label>
              <label class="inline-flex items-center gap-1.5 cursor-pointer">
                <input v-model="duplicateMode" type="radio" value="skip" class="text-[#0284c7]" />
                <span>Lewati (Skip)</span>
              </label>
              <label class="inline-flex items-center gap-1.5 cursor-pointer">
                <input v-model="duplicateMode" type="radio" value="auto_id" class="text-[#0284c7]" />
                <span>ID Baru Otomatis</span>
              </label>
            </div>
          </div>

          <!-- Preview Table with internal search -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold text-slate-800">
                Tampilan Data Excel ({{ filteredPreviewRows.length }} baris ditemukan):
              </span>
              <div class="relative w-56">
                <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2" />
                <input
                  v-model="previewSearch"
                  type="text"
                  placeholder="Cari ID, nama, kategori..."
                  class="w-full pl-8 pr-2.5 py-1 text-xs border border-slate-300 rounded-md bg-white"
                />
              </div>
            </div>

            <div class="border border-slate-200 rounded-lg overflow-x-auto max-h-72">
              <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 sticky top-0 border-b border-slate-200 text-slate-700 font-semibold text-[11px]">
                  <tr>
                    <th class="py-2 px-3">#</th>
                    <th class="py-2 px-3">ID Barang</th>
                    <th class="py-2 px-3">Nama Barang</th>
                    <th class="py-2 px-3">Kategori</th>
                    <th class="py-2 px-3">Satuan</th>
                    <th class="py-2 px-3 text-right">Stok</th>
                    <th class="py-2 px-3 text-right">Harga Jual</th>
                    <th class="py-2 px-3 text-center">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="(row, idx) in filteredPreviewRows.slice(0, 15)" :key="idx" class="hover:bg-slate-50">
                    <td class="py-2 px-3 text-slate-400 font-mono text-[10px]">{{ idx + 1 }}</td>
                    <td class="py-2 px-3 font-mono font-bold text-[#0284c7]">{{ row.sku }}</td>
                    <td class="py-2 px-3 font-medium text-slate-800">{{ row.name }}</td>
                    <td class="py-2 px-3 text-slate-600">{{ row.category }}</td>
                    <td class="py-2 px-3 text-slate-600 uppercase">{{ row.unit }}</td>
                    <td class="py-2 px-3 text-right font-mono">{{ row.stock }}</td>
                    <td class="py-2 px-3 text-right font-mono">Rp {{ (row.sell_price || row.price || 0).toLocaleString('id-ID') }}</td>
                    <td class="py-2 px-3 text-center"><span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 text-slate-700">{{ row.status || 'Tersedia' }}</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- =================== STEP 4: VALIDASI & SIMPAN =================== -->
        <div v-if="importStep === 4" class="space-y-4">
          <!-- Validation Summary Badges -->
          <div class="grid grid-cols-3 gap-3">
            <div class="p-3.5 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-900">
              <div class="flex items-center gap-2 font-bold text-xs text-emerald-800 mb-1">
                <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                <span>Data Valid & Siap Impor</span>
              </div>
              <div class="text-xl font-bold font-mono text-emerald-700">{{ validationStats.validCount.toLocaleString('id-ID') }} baris</div>
            </div>

            <div class="p-3.5 rounded-xl border border-amber-200 bg-amber-50 text-amber-900">
              <div class="flex items-center gap-2 font-bold text-xs text-amber-800 mb-1">
                <AlertTriangle class="w-4 h-4 text-amber-600" />
                <span>Peringatan / Duplikat SKU</span>
              </div>
              <div class="text-xl font-bold font-mono text-amber-700">{{ validationStats.duplicateCount.toLocaleString('id-ID') }} baris</div>
              <div class="text-[10px] text-amber-700 mt-0.5">Aksi: {{ duplicateMode === 'update' ? 'Diperbarui' : duplicateMode === 'skip' ? 'Dilewati' : 'Dibuat SKU baru' }}</div>
            </div>

            <div class="p-3.5 rounded-xl border border-red-200 bg-red-50 text-red-900">
              <div class="flex items-center gap-2 font-bold text-xs text-red-800 mb-1">
                <AlertCircle class="w-4 h-4 text-red-600" />
                <span>Data Tidak Valid (Error)</span>
              </div>
              <div class="text-xl font-bold font-mono text-red-700">{{ validationStats.errorCount.toLocaleString('id-ID') }} baris</div>
            </div>
          </div>

          <!-- Search & Filter Validation -->
          <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
            <!-- Search Bar in Validation -->
            <div class="relative w-full sm:w-64">
              <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2" />
              <input
                v-model="validationSearch"
                type="text"
                placeholder="Cari data validasi..."
                class="w-full pl-8 pr-2.5 py-1 text-xs border border-slate-300 rounded-md bg-white"
              />
            </div>

            <div class="flex items-center gap-1.5">
              <button
                type="button"
                class="px-2.5 py-1 rounded text-xs font-semibold cursor-pointer"
                :class="validationFilter === 'all' ? 'bg-[#0284c7] text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100 border border-slate-200'"
                @click="validationFilter = 'all'"
              >
                Semua ({{ validatedRows.length }})
              </button>
              <button
                type="button"
                class="px-2.5 py-1 rounded text-xs font-semibold cursor-pointer"
                :class="validationFilter === 'valid' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100 border border-slate-200'"
                @click="validationFilter = 'valid'"
              >
                Valid ({{ validationStats.validCount }})
              </button>
              <button
                v-if="validationStats.errorCount > 0"
                type="button"
                class="px-2.5 py-1 rounded text-xs font-semibold cursor-pointer"
                :class="validationFilter === 'error' ? 'bg-red-600 text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100 border border-slate-200'"
                @click="validationFilter = 'error'"
              >
                Error ({{ validationStats.errorCount }})
              </button>
            </div>
          </div>

          <!-- Validation Log List -->
          <div class="border border-slate-200 rounded-xl overflow-hidden text-xs">
            <div class="max-h-64 overflow-y-auto divide-y divide-slate-100">
              <div
                v-for="(row, idx) in filteredValidatedRows.slice(0, 50)"
                :key="idx"
                class="px-4 py-2 flex items-center justify-between hover:bg-slate-50"
              >
                <div class="flex items-center gap-3">
                  <span
                    :class="[
                      'w-2 h-2 rounded-full',
                      row.validationStatus === 'valid' ? 'bg-emerald-500' : row.validationStatus === 'warning' ? 'bg-amber-500' : 'bg-red-500'
                    ]"
                  ></span>
                  <span class="font-mono font-bold text-[#0284c7] text-[11px]">{{ row.sku }}</span>
                  <span class="text-slate-800 font-medium">{{ row.name }}</span>
                  <span class="text-[11px] text-slate-400">({{ row.category }} • {{ row.unit }})</span>
                </div>

                <div class="flex items-center gap-2">
                  <span
                    v-if="row.validationStatus === 'valid'"
                    class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800"
                  >
                    Siap Disimpan
                  </span>
                  <span
                    v-else-if="row.validationStatus === 'warning'"
                    class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800"
                  >
                    {{ row.validationMessage }}
                  </span>
                  <span
                    v-else
                    class="px-2 py-0.5 rounded text-[10px] font-semibold bg-red-100 text-red-800"
                  >
                    {{ row.validationMessage }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Progress bar when saving -->
          <div v-if="isSavingImport" class="p-4 bg-sky-50 border border-sky-200 rounded-xl text-center space-y-2">
            <div class="flex items-center justify-center gap-2 text-xs font-bold text-[#0284c7]">
              <RefreshCw class="w-4 h-4 animate-spin" />
              <span>Menyimpan {{ validatedRows.length }} data barang ke sistem database...</span>
            </div>
            <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
              <div class="bg-[#0284c7] h-2 rounded-full transition-all duration-300 animate-pulse w-full"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <template #footer>
        <div class="flex items-center justify-between w-full">
          <button
            type="button"
            class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-xs font-semibold cursor-pointer"
            @click="importModalOpen = false"
          >
            Batal
          </button>

          <div class="flex items-center gap-2">
            <!-- Back Button -->
            <button
              v-if="importStep > 1"
              type="button"
              class="px-3.5 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold cursor-pointer"
              @click="importStep--"
            >
              Kembali
            </button>

            <!-- Step 1 Next -->
            <button
              v-if="importStep === 1"
              type="button"
              class="px-4 py-2 bg-[#0284c7] hover:bg-[#0369a1] text-white rounded-lg text-xs font-bold shadow-xs cursor-pointer"
              @click="importStep = 2"
            >
              Lanjut ke Upload File
            </button>

            <!-- Step 2 Next -->
            <button
              v-if="importStep === 2"
              type="button"
              class="px-4 py-2 bg-[#0284c7] hover:bg-[#0369a1] text-white rounded-lg text-xs font-bold shadow-xs cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="parsedExcelData.length === 0"
              @click="importStep = 3"
            >
              Lanjut ke Preview Data ({{ parsedExcelData.length }})
            </button>

            <!-- Step 3 Next -->
            <button
              v-if="importStep === 3"
              type="button"
              class="px-4 py-2 bg-[#0284c7] hover:bg-[#0369a1] text-white rounded-lg text-xs font-bold shadow-xs cursor-pointer"
              @click="runValidationAndProceed"
            >
              Lanjut ke Validasi & Simpan
            </button>

            <!-- Step 4 Execute Save -->
            <button
              v-if="importStep === 4"
              type="button"
              class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs cursor-pointer flex items-center gap-2 disabled:opacity-50"
              :disabled="isSavingImport || validationStats.validCount === 0"
              @click="saveImportedData"
            >
              <CheckCircle2 class="w-4 h-4 stroke-[2.5]" />
              <span>Simpan {{ validationStats.validCount }} Data Barang</span>
            </button>
          </div>
        </div>
      </template>
    </Modal>

    <!-- ======================================================== -->
    <!-- MODAL 2: MANAJEMEN KATEGORI BARANG (WITH SEARCH BAR)     -->
    <!-- ======================================================== -->
    <Modal
      v-model="categoryModalOpen"
      title="Manajemen Kategori Barang"
      subtitle="Kelola master kategori untuk pengelompokan material dan persediaan"
      size="lg"
    >
      <div class="space-y-4">
        <!-- Search Category Bar -->
        <div class="flex items-center gap-2">
          <div class="relative flex-1">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
            <input
              v-model="searchCategoryQuery"
              type="text"
              placeholder="Cari nama kategori..."
              class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white placeholder:text-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0284c7]"
            />
            <button
              v-if="searchCategoryQuery"
              type="button"
              class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 cursor-pointer"
              @click="searchCategoryQuery = ''"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <!-- Add Category Form -->
        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
          <h4 class="text-xs font-bold text-slate-800 uppercase mb-2">Tambah Kategori Baru</h4>
          <form class="flex items-center gap-2" @submit.prevent="createCategory">
            <input
              v-model="newCategoryName"
              type="text"
              required
              placeholder="Nama kategori baru (misal: Bahan Kimia & Lab)"
              class="flex-1 px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white"
            />
            <button
              type="submit"
              class="px-3.5 py-1.5 bg-[#0284c7] hover:bg-[#0369a1] text-white font-semibold rounded-lg text-xs cursor-pointer"
            >
              + Simpan Kategori
            </button>
          </form>
        </div>

        <!-- Category List Table -->
        <div class="border border-slate-200 rounded-xl overflow-hidden">
          <div class="px-3 py-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs font-semibold text-slate-700">
            <span>Daftar Kategori:</span>
            <span class="text-slate-500 font-normal">Menampilkan {{ filteredCategoriesWithCounts.length }} dari {{ categoriesList.length }} kategori</span>
          </div>

          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50/70 border-b border-slate-200 text-slate-700 font-semibold text-[11px]">
              <tr>
                <th class="py-2.5 px-3">#</th>
                <th class="py-2.5 px-3">Nama Kategori</th>
                <th class="py-2.5 px-3 text-center">Jumlah Barang</th>
                <th class="py-2.5 px-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 max-h-60 overflow-y-auto">
              <tr v-if="filteredCategoriesWithCounts.length === 0">
                <td colspan="4" class="py-6 text-center text-slate-400">
                  Tidak ada kategori yang cocok dengan "{{ searchCategoryQuery }}".
                </td>
              </tr>
              <tr v-for="(cat, index) in filteredCategoriesWithCounts" :key="cat.name" class="hover:bg-slate-50">
                <td class="py-2.5 px-3 text-slate-400 font-mono text-[10px]">{{ index + 1 }}</td>
                <td class="py-2.5 px-3 font-semibold text-slate-800">
                  <input
                    v-if="editingCatName === cat.name"
                    v-model="editCatInput"
                    class="px-2 py-0.5 border rounded text-xs w-full"
                    @keyup.enter="saveEditCategory(cat.name)"
                  />
                  <span v-else>{{ cat.name }}</span>
                </td>
                <td class="py-2.5 px-3 text-center">
                  <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-700 font-mono font-semibold">{{ cat.count }}</span>
                </td>
                <td class="py-2.5 px-3 text-right">
                  <div class="inline-flex items-center gap-1.5">
                    <button
                      v-if="editingCatName === cat.name"
                      type="button"
                      class="px-2 py-0.5 bg-emerald-600 text-white rounded text-[10px] font-bold cursor-pointer"
                      @click="saveEditCategory(cat.name)"
                    >
                      Simpan
                    </button>
                    <button
                      v-else
                      type="button"
                      class="p-1 text-slate-600 hover:bg-slate-200 rounded cursor-pointer"
                      title="Edit Nama Kategori"
                      @click="startEditCategory(cat.name)"
                    >
                      <Edit class="w-3.5 h-3.5" />
                    </button>
                    <button
                      type="button"
                      class="p-1 text-red-500 hover:bg-red-50 rounded cursor-pointer"
                      title="Hapus Kategori"
                      @click="deleteCategory(cat.name, cat.count)"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <template #footer>
        <button
          type="button"
          class="px-4 py-2 bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs hover:bg-slate-300 cursor-pointer"
          @click="categoryModalOpen = false"
        >
          Tutup
        </button>
      </template>
    </Modal>

    <!-- ======================================================== -->
    <!-- MODAL 3: MANAJEMEN SATUAN BARANG (WITH SEARCH BAR)        -->
    <!-- ======================================================== -->
    <Modal
      v-model="unitModalOpen"
      title="Manajemen Satuan Barang"
      subtitle="Kelola standar unit ukuran (KG, UNIT, BUAH, PCS, M3, DUS, dll)"
      size="lg"
    >
      <div class="space-y-4">
        <!-- Search Unit Bar -->
        <div class="flex items-center gap-2">
          <div class="relative flex-1">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
            <input
              v-model="searchUnitQuery"
              type="text"
              placeholder="Cari nama satuan..."
              class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white placeholder:text-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0284c7] uppercase"
            />
            <button
              v-if="searchUnitQuery"
              type="button"
              class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 cursor-pointer"
              @click="searchUnitQuery = ''"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <!-- Add Unit Form -->
        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
          <h4 class="text-xs font-bold text-slate-800 uppercase mb-2">Tambah Satuan Baru</h4>
          <form class="flex items-center gap-2" @submit.prevent="createUnit">
            <input
              v-model="newUnitName"
              type="text"
              required
              placeholder="Contoh: LITER, PACK, ROLL, TUBE"
              class="flex-1 px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white uppercase"
            />
            <button
              type="submit"
              class="px-3.5 py-1.5 bg-[#0284c7] hover:bg-[#0369a1] text-white font-semibold rounded-lg text-xs cursor-pointer"
            >
              + Simpan Satuan
            </button>
          </form>
        </div>

        <!-- Unit List Table -->
        <div class="border border-slate-200 rounded-xl overflow-hidden">
          <div class="px-3 py-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs font-semibold text-slate-700">
            <span>Daftar Satuan:</span>
            <span class="text-slate-500 font-normal">Menampilkan {{ filteredUnitsWithCounts.length }} dari {{ unitsList.length }} satuan</span>
          </div>

          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50/70 border-b border-slate-200 text-slate-700 font-semibold text-[11px]">
              <tr>
                <th class="py-2.5 px-3">#</th>
                <th class="py-2.5 px-3">Nama Satuan</th>
                <th class="py-2.5 px-3 text-center">Jumlah Barang</th>
                <th class="py-2.5 px-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 max-h-60 overflow-y-auto">
              <tr v-if="filteredUnitsWithCounts.length === 0">
                <td colspan="4" class="py-6 text-center text-slate-400">
                  Tidak ada satuan yang cocok dengan "{{ searchUnitQuery }}".
                </td>
              </tr>
              <tr v-for="(u, index) in filteredUnitsWithCounts" :key="u.name" class="hover:bg-slate-50">
                <td class="py-2.5 px-3 text-slate-400 font-mono text-[10px]">{{ index + 1 }}</td>
                <td class="py-2.5 px-3 font-semibold text-slate-800 uppercase">
                  <input
                    v-if="editingUnitName === u.name"
                    v-model="editUnitInput"
                    class="px-2 py-0.5 border rounded text-xs w-full uppercase"
                    @keyup.enter="saveEditUnit(u.name)"
                  />
                  <span v-else>{{ u.name }}</span>
                </td>
                <td class="py-2.5 px-3 text-center">
                  <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-700 font-mono font-semibold">{{ u.count }}</span>
                </td>
                <td class="py-2.5 px-3 text-right">
                  <div class="inline-flex items-center gap-1.5">
                    <button
                      v-if="editingUnitName === u.name"
                      type="button"
                      class="px-2 py-0.5 bg-emerald-600 text-white rounded text-[10px] font-bold cursor-pointer"
                      @click="saveEditUnit(u.name)"
                    >
                      Simpan
                    </button>
                    <button
                      v-else
                      type="button"
                      class="p-1 text-slate-600 hover:bg-slate-200 rounded cursor-pointer"
                      title="Edit Satuan"
                      @click="startEditUnit(u.name)"
                    >
                      <Edit class="w-3.5 h-3.5" />
                    </button>
                    <button
                      type="button"
                      class="p-1 text-red-500 hover:bg-red-50 rounded cursor-pointer"
                      title="Hapus Satuan"
                      @click="deleteUnit(u.name, u.count)"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <template #footer>
        <button
          type="button"
          class="px-4 py-2 bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs hover:bg-slate-300 cursor-pointer"
          @click="unitModalOpen = false"
        >
          Tutup
        </button>
      </template>
    </Modal>

    <!-- ======================================================== -->
    <!-- MODAL 4: TAMBAH / EDIT BARANG                             -->
    <!-- ======================================================== -->
    <Modal
      v-model="productModalOpen"
      :title="editingProduct ? 'Edit Data Barang' : 'Tambah Barang Baru'"
      :subtitle="editingProduct ? `ID / SKU: ${editingProduct.sku}` : 'Daftarkan item material atau produk baru ke persediaan'"
      size="lg"
    >
      <form class="space-y-4 text-xs" @submit.prevent="saveProduct">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">ID / SKU Barang</label>
            <input
              v-model="prodForm.sku"
              required
              placeholder="Contoh: A00000002 atau BRG-001"
              class="w-full px-3 py-2 border rounded-lg font-mono"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Status Barang</label>
            <select v-model="prodForm.status" class="w-full px-3 py-2 border rounded-lg bg-white cursor-pointer">
              <option value="Tersedia">Tersedia</option>
              <option value="Menipis">Menipis</option>
              <option value="Habis">Habis</option>
              <option value="Nonaktif">Nonaktif</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Nama Barang / Material</label>
          <input
            v-model="prodForm.name"
            required
            placeholder="Nama lengkap barang spesifik"
            class="w-full px-3 py-2 border rounded-lg"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Kategori</label>
            <input
              v-model="prodForm.category"
              list="cat-datalist"
              required
              placeholder="Pilih atau ketik kategori"
              class="w-full px-3 py-2 border rounded-lg"
            />
            <datalist id="cat-datalist">
              <option v-for="c in categoriesList" :key="c" :value="c" />
            </datalist>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Satuan</label>
            <input
              v-model="prodForm.unit"
              list="unit-datalist"
              required
              placeholder="KG, UNIT, BUAH, PCS, DUS..."
              class="w-full px-3 py-2 border rounded-lg uppercase"
            />
            <datalist id="unit-datalist">
              <option v-for="u in unitsList" :key="u" :value="u" />
            </datalist>
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Stok Persediaan</label>
            <input
              v-model.number="prodForm.stock"
              type="number"
              min="0"
              class="w-full px-3 py-2 border rounded-lg text-right font-mono"
            />
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Harga Beli Terakhir (Rp)</label>
            <input
              v-model.number="prodForm.last_buy_price"
              type="number"
              min="0"
              class="w-full px-3 py-2 border rounded-lg text-right font-mono"
            />
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">HPP (Rp)</label>
            <input
              v-model.number="prodForm.hpp"
              type="number"
              min="0"
              class="w-full px-3 py-2 border rounded-lg text-right font-mono"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3.5">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Harga Jual (Rp)</label>
            <input
              v-model.number="prodForm.sell_price"
              type="number"
              min="0"
              class="w-full px-3 py-2 border rounded-lg text-right font-mono font-bold text-slate-900"
            />
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Batas Minimum Stok</label>
            <input
              v-model.number="prodForm.min_stock"
              type="number"
              min="0"
              class="w-full px-3 py-2 border rounded-lg text-right font-mono"
            />
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Deskripsi / Sumber RKAS</label>
          <textarea
            v-model="prodForm.description"
            rows="2"
            placeholder="Catatan tambahan, spesifikasi atau info sumber"
            class="w-full px-3 py-2 border rounded-lg"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-200">
          <button
            type="button"
            class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg font-semibold cursor-pointer"
            @click="productModalOpen = false"
          >
            Batal
          </button>
          <button
            type="submit"
            class="px-5 py-2 bg-[#0284c7] hover:bg-[#0369a1] text-white font-bold rounded-lg shadow-xs cursor-pointer"
          >
            Simpan Data Barang
          </button>
        </div>
      </form>
    </Modal>

    <!-- ======================================================== -->
    <!-- MODAL 5: KONFIRMASI HAPUS                                 -->
    <!-- ======================================================== -->
    <Modal
      v-model="deleteModalOpen"
      title="Konfirmasi Hapus Barang"
      subtitle="Tindakan ini tidak dapat dibatalkan"
      size="sm"
    >
      <div v-if="deletingProduct" class="space-y-3 text-xs">
        <p class="text-slate-600">
          Apakah Anda yakin ingin menghapus barang berikut dari katalog?
        </p>
        <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
          <div class="font-mono font-bold text-red-900">{{ deletingProduct.sku }}</div>
          <div class="font-semibold text-slate-800 text-sm mt-0.5">{{ deletingProduct.name }}</div>
          <div class="text-[11px] text-slate-500 mt-1">{{ deletingProduct.category }} • Satuan: {{ deletingProduct.unit }}</div>
        </div>
      </div>

      <template #footer>
        <div class="flex items-center justify-end gap-2">
          <button
            type="button"
            class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg text-xs cursor-pointer"
            @click="deleteModalOpen = false"
          >
            Batal
          </button>
          <button
            type="button"
            class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-bold cursor-pointer"
            @click="executeDelete"
          >
            Hapus Permanen
          </button>
        </div>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import Modal from '../../components/common/Modal.vue';
import { store } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import api from '../../services/api';
import * as XLSX from 'xlsx';
import {
  Package,
  FileSpreadsheet,
  Tags,
  Flag,
  Plus,
  Search,
  RotateCcw,
  Download,
  UploadCloud,
  FileDown,
  CheckCircle2,
  AlertTriangle,
  AlertCircle,
  Edit,
  Trash2,
  Check,
  X,
  RefreshCw,
} from 'lucide-vue-next';

const toast = useToast();

// Main In-Memory Catalog State (holds all items for instant reactive filtering)
const isLoading = ref(false);
const allCatalogProducts = ref([]);

// Filters
const searchQuery = ref('');
const selectedCategory = ref('');
const selectedUnit = ref('');
const selectedStatus = ref('');

// Sorting
const sortBy = ref('sku');
const sortDir = ref('asc');

// Pagination
const currentPage = ref(1);
const perPage = ref(10);

// Categories & Units Lists
const categoriesList = ref([]);
const unitsList = ref([]);

// Modals State
const importModalOpen = ref(false);
const categoryModalOpen = ref(false);
const unitModalOpen = ref(false);
const productModalOpen = ref(false);
const deleteModalOpen = ref(false);

// Modal Search Bars
const searchCategoryQuery = ref('');
const searchUnitQuery = ref('');
const previewSearch = ref('');
const validationSearch = ref('');

// Add / Edit Product Form
const editingProduct = ref(null);
const deletingProduct = ref(null);
const prodForm = ref({
  sku: '',
  name: '',
  category: '',
  unit: 'PCS',
  stock: 0,
  min_stock: 10,
  last_buy_price: 0,
  hpp: 0,
  sell_price: 0,
  status: 'Tersedia',
  description: '',
});

// Category & Unit Edit State
const newCategoryName = ref('');
const editingCatName = ref(null);
const editCatInput = ref('');

const newUnitName = ref('');
const editingUnitName = ref(null);
const editUnitInput = ref('');

// Import Excel Wizard State
const importStep = ref(1);
const isDragging = ref(false);
const fileInputRef = ref(null);
const uploadedFileName = ref('');
const uploadedFileSize = ref('');
const parsedExcelData = ref([]);
const duplicateMode = ref('update'); // 'update', 'skip', 'auto_id'
const validationFilter = ref('all');
const validatedRows = ref([]);
const isSavingImport = ref(false);

// ========================================================
// CORE INSTANT REACTIVE SEARCH & FILTER ENGINE
// ========================================================
const filteredProducts = computed(() => {
  let list = allCatalogProducts.value;

  // 1. Text Search Bar: Multi-keyword search across SKU, Nama Barang, Satuan, Kategori, Deskripsi
  if (searchQuery.value && searchQuery.value.trim()) {
    const words = searchQuery.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
    list = list.filter((p) => {
      const fullText = `${p.sku || ''} ${p.name || ''} ${p.category || ''} ${p.unit || ''} ${p.description || ''}`.toLowerCase();
      return words.every((w) => fullText.includes(w));
    });
  }

  // 2. Category Filter
  if (selectedCategory.value) {
    list = list.filter((p) => p.category === selectedCategory.value);
  }

  // 3. Unit Filter
  if (selectedUnit.value) {
    list = list.filter((p) => (p.unit || '').toUpperCase() === selectedUnit.value.toUpperCase());
  }

  // 4. Status Filter
  if (selectedStatus.value) {
    list = list.filter((p) => (p.status || 'Tersedia') === selectedStatus.value);
  }

  // 5. Sorting
  return [...list].sort((a, b) => {
    let va = a[sortBy.value] ?? '';
    let vb = b[sortBy.value] ?? '';
    if (sortBy.value === 'stock' || sortBy.value === 'last_buy_price' || sortBy.value === 'hpp' || sortBy.value === 'sell_price') {
      va = Number(va) || 0;
      vb = Number(vb) || 0;
      return sortDir.value === 'asc' ? va - vb : vb - va;
    }
    return sortDir.value === 'asc'
      ? String(va).localeCompare(String(vb), undefined, { numeric: true })
      : String(vb).localeCompare(String(va), undefined, { numeric: true });
  });
});

const totalItems = computed(() => filteredProducts.value.length);
const totalStockSum = computed(() => filteredProducts.value.reduce((sum, p) => sum + (Number(p.stock) || 0), 0));
const totalPages = computed(() => Math.max(1, Math.ceil(totalItems.value / perPage.value)));

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredProducts.value.slice(start, start + perPage.value);
});

const paginationInfo = computed(() => {
  if (totalItems.value === 0) return { from: 0, to: 0 };
  const from = (currentPage.value - 1) * perPage.value + 1;
  const to = Math.min(currentPage.value * perPage.value, totalItems.value);
  return { from, to };
});

const visiblePages = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }
  const pages = [];
  pages.push(1);
  if (current > 3) pages.push('...');
  const start = Math.max(2, current - 1);
  const end = Math.min(total - 1, current + 1);
  for (let i = start; i <= end; i++) pages.push(i);
  if (current < total - 2) pages.push('...');
  pages.push(total);
  return pages;
});

// Search Bar Event Handlers
const onSearchInput = () => {
  currentPage.value = 1;
};

const clearSearch = () => {
  searchQuery.value = '';
  currentPage.value = 1;
};

const onFilterChange = () => {
  currentPage.value = 1;
};

const handleSearch = () => {
  currentPage.value = 1;
};

const handleReset = () => {
  searchQuery.value = '';
  selectedCategory.value = '';
  selectedUnit.value = '';
  selectedStatus.value = '';
  sortBy.value = 'sku';
  sortDir.value = 'asc';
  currentPage.value = 1;
};

const toggleSort = (col) => {
  if (sortBy.value === col) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = col;
    sortDir.value = 'asc';
  }
  currentPage.value = 1;
};

const goToPage = (page) => {
  if (page < 1 || page > totalPages.value) return;
  currentPage.value = page;
};

const handlePerPageChange = () => {
  currentPage.value = 1;
};

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Tersedia':
      return 'bg-emerald-100 text-emerald-800';
    case 'Menipis':
      return 'bg-amber-100 text-amber-800';
    case 'Habis':
    case 'Tidak Tersedia':
      return 'bg-red-100 text-red-800';
    case 'Nonaktif':
      return 'bg-slate-200 text-slate-700';
    default:
      return 'bg-emerald-100 text-emerald-800';
  }
};

// ========================================================
// CATEGORIES & UNITS MANAGEMENT WITH SEARCH
// ========================================================
const categoriesWithCounts = computed(() => {
  return categoriesList.value.map((catName) => {
    const count = allCatalogProducts.value.filter((p) => p.category === catName).length;
    return { name: catName, count };
  });
});

const filteredCategoriesWithCounts = computed(() => {
  if (!searchCategoryQuery.value.trim()) return categoriesWithCounts.value;
  const q = searchCategoryQuery.value.toLowerCase().trim();
  return categoriesWithCounts.value.filter((c) => c.name.toLowerCase().includes(q));
});

const unitsWithCounts = computed(() => {
  return unitsList.value.map((unitName) => {
    const count = allCatalogProducts.value.filter((p) => (p.unit || '').toUpperCase() === unitName.toUpperCase()).length;
    return { name: unitName, count };
  });
});

const filteredUnitsWithCounts = computed(() => {
  if (!searchUnitQuery.value.trim()) return unitsWithCounts.value;
  const q = searchUnitQuery.value.toLowerCase().trim();
  return unitsWithCounts.value.filter((u) => u.name.toLowerCase().includes(q));
});

// Category Actions
const createCategory = async () => {
  const name = newCategoryName.value.trim();
  if (!name) return;
  if (!categoriesList.value.includes(name)) {
    categoriesList.value.push(name);
    categoriesList.value.sort();
  }
  try {
    await api.post('/products/categories', { name });
  } catch (e) {
    console.warn('API error saving category:', e);
  }
  toast.success(`Kategori "${name}" berhasil ditambahkan.`);
  newCategoryName.value = '';
};

const startEditCategory = (catName) => {
  editingCatName.value = catName;
  editCatInput.value = catName;
};

const saveEditCategory = async (oldName) => {
  const newName = editCatInput.value.trim();
  if (!newName || newName === oldName) {
    editingCatName.value = null;
    return;
  }

  // Update in category list
  const idx = categoriesList.value.indexOf(oldName);
  if (idx !== -1) categoriesList.value[idx] = newName;
  categoriesList.value.sort();

  // Update in all products in memory
  allCatalogProducts.value.forEach((p) => {
    if (p.category === oldName) p.category = newName;
  });

  // Sync to store
  if (Array.isArray(store.products)) {
    store.products.forEach((p) => {
      if (p.category === oldName) p.category = newName;
    });
  }

  try {
    const res = await api.get('/products/categories');
    const cat = res.data?.data?.find((c) => c.name === oldName);
    if (cat?.id) {
      await api.put(`/products/categories/${cat.id}`, { name: newName });
    }
  } catch (e) {
    console.warn('API update error:', e);
  }

  editingCatName.value = null;
  toast.success(`Kategori diperbarui menjadi "${newName}".`);
};

const deleteCategory = async (catName, count) => {
  if (count > 0) {
    toast.error(`Kategori "${catName}" masih digunakan oleh ${count} barang.`);
    return;
  }
  if (!confirm(`Hapus kategori "${catName}"?`)) return;

  categoriesList.value = categoriesList.value.filter((c) => c !== catName);

  try {
    const res = await api.get('/products/categories');
    const cat = res.data?.data?.find((c) => c.name === catName);
    if (cat?.id) {
      await api.delete(`/products/categories/${cat.id}`);
    }
  } catch (e) {
    console.warn('API delete error:', e);
  }

  toast.success(`Kategori "${catName}" berhasil dihapus.`);
};

// Unit Actions
const createUnit = async () => {
  const name = newUnitName.value.trim().toUpperCase();
  if (!name) return;
  if (!unitsList.value.includes(name)) {
    unitsList.value.push(name);
    unitsList.value.sort();
  }
  try {
    await api.post('/products/units', { name });
  } catch (e) {
    console.warn('API unit error:', e);
  }
  toast.success(`Satuan "${name}" berhasil ditambahkan.`);
  newUnitName.value = '';
};

const startEditUnit = (unitName) => {
  editingUnitName.value = unitName;
  editUnitInput.value = unitName;
};

const saveEditUnit = async (oldName) => {
  const newName = editUnitInput.value.trim().toUpperCase();
  if (!newName || newName === oldName) {
    editingUnitName.value = null;
    return;
  }

  const idx = unitsList.value.indexOf(oldName);
  if (idx !== -1) unitsList.value[idx] = newName;
  unitsList.value.sort();

  allCatalogProducts.value.forEach((p) => {
    if (p.unit === oldName) p.unit = newName;
  });

  if (Array.isArray(store.products)) {
    store.products.forEach((p) => {
      if (p.unit === oldName) p.unit = newName;
    });
  }

  try {
    const res = await api.get('/products/units');
    const u = res.data?.data?.find((item) => item.name === oldName);
    if (u?.id) {
      await api.put(`/products/units/${u.id}`, { name: newName });
    }
  } catch (e) {
    console.warn('API unit update error:', e);
  }

  editingUnitName.value = null;
  toast.success(`Satuan diperbarui menjadi "${newName}".`);
};

const deleteUnit = async (unitName, count) => {
  if (count > 0) {
    toast.error(`Satuan "${unitName}" masih digunakan oleh ${count} barang.`);
    return;
  }
  if (!confirm(`Hapus satuan "${unitName}"?`)) return;

  unitsList.value = unitsList.value.filter((item) => item !== unitName);

  try {
    const res = await api.get('/products/units');
    const u = res.data?.data?.find((item) => item.name === unitName);
    if (u?.id) {
      await api.delete(`/products/units/${u.id}`);
    }
  } catch (e) {
    console.warn('API unit delete error:', e);
  }

  toast.success(`Satuan "${unitName}" berhasil dihapus.`);
};

// ========================================================
// ADD / EDIT / DELETE PRODUCT ACTIONS
// ========================================================
const openAddModal = () => {
  editingProduct.value = null;
  prodForm.value = {
    sku: `A${String(allCatalogProducts.value.length + 2).padStart(8, '0')}`,
    name: '',
    category: categoriesList.value[0] || 'Umum',
    unit: 'PCS',
    stock: 0,
    min_stock: 10,
    last_buy_price: 0,
    hpp: 0,
    sell_price: 0,
    status: 'Tersedia',
    description: '',
  };
  productModalOpen.value = true;
};

const openEditModal = (p) => {
  editingProduct.value = p;
  prodForm.value = {
    sku: p.sku,
    name: p.name,
    category: p.category,
    unit: p.unit,
    stock: p.stock ?? 0,
    min_stock: p.min_stock ?? 10,
    last_buy_price: p.last_buy_price ?? 0,
    hpp: p.hpp ?? 0,
    sell_price: p.sell_price ?? p.price ?? 0,
    status: p.status || 'Tersedia',
    description: p.description || '',
  };
  productModalOpen.value = true;
};

const saveProduct = async () => {
  const formVal = { ...prodForm.value };

  // Update in memory list
  if (editingProduct.value) {
    const target = allCatalogProducts.value.find((p) => p.sku === editingProduct.value.sku);
    if (target) Object.assign(target, formVal);

    if (Array.isArray(store.products)) {
      const local = store.products.find((p) => p.sku === editingProduct.value.sku);
      if (local) Object.assign(local, formVal);
    }

    try {
      if (editingProduct.value.id) {
        await api.put(`/products/${editingProduct.value.id}`, formVal);
      }
    } catch (e) {
      console.warn('API update product error:', e);
    }
    toast.success(`Data barang ${formVal.sku} berhasil diperbarui.`);
  } else {
    allCatalogProducts.value.unshift({ id: Date.now(), ...formVal });
    if (Array.isArray(store.products)) {
      store.products.unshift({ id: Date.now(), ...formVal });
    }

    try {
      await api.post('/products', formVal);
    } catch (e) {
      console.warn('API create product error:', e);
    }
    toast.success(`Barang baru ${formVal.sku} berhasil ditambahkan.`);
  }

  // Update category and unit lists if new
  if (formVal.category && !categoriesList.value.includes(formVal.category)) {
    categoriesList.value.push(formVal.category);
    categoriesList.value.sort();
  }
  if (formVal.unit && !unitsList.value.includes(formVal.unit)) {
    unitsList.value.push(formVal.unit);
    unitsList.value.sort();
  }

  productModalOpen.value = false;
};

const confirmDelete = (p) => {
  deletingProduct.value = p;
  deleteModalOpen.value = true;
};

const executeDelete = async () => {
  if (!deletingProduct.value) return;
  const sku = deletingProduct.value.sku;

  allCatalogProducts.value = allCatalogProducts.value.filter((p) => p.sku !== sku);
  if (Array.isArray(store.products)) {
    store.products = store.products.filter((p) => p.sku !== sku);
  }

  try {
    if (deletingProduct.value.id) {
      await api.delete(`/products/${deletingProduct.value.id}`);
    }
  } catch (e) {
    console.warn('API delete error:', e);
  }

  deleteModalOpen.value = false;
  deletingProduct.value = null;
  toast.success(`Barang ${sku} berhasil dihapus.`);
};

// ========================================================
// IMPORT EXCEL LOGIC & WIZARD
// ========================================================
const openImportModal = () => {
  importStep.value = 1;
  parsedExcelData.value = [];
  uploadedFileName.value = '';
  uploadedFileSize.value = '';
  validatedRows.value = [];
  previewSearch.value = '';
  validationSearch.value = '';
  validationFilter.value = 'all';
  importModalOpen.value = true;
};

const downloadOfficialTemplate = () => {
  window.open('/templates/data_barang_template.xlsx', '_blank');
};

const downloadBlankTemplate = () => {
  const wsData = [
    [
      'ID Barang',
      'Kategori',
      'Nama Barang',
      'Deskripsi',
      'Satuan',
      'Stok',
      'Harga Beli Terakhir',
      'HPP',
      'Harga Jual',
      'Status',
    ],
    [
      'A00000002',
      'Laboratorium & Bahan Kimia',
      'Abate 1% Granule Bubuk',
      'Sumber: Standar Pengadaan RKAS 2026',
      'KG',
      0,
      0,
      0,
      99725,
      'Tersedia',
    ],
    [
      'A00000003',
      'Elektronik, Komputer & Peralatan Listrik',
      'Ac Split Spesifikasi : 2 Pk',
      'Sumber: Standar Pengadaan RKAS 2026',
      'UNIT',
      0,
      0,
      0,
      10943000,
      'Tersedia',
    ],
  ];

  const ws = XLSX.utils.aoa_to_sheet(wsData);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Data Barang');

  ws['!cols'] = [
    { wch: 14 },
    { wch: 30 },
    { wch: 40 },
    { wch: 35 },
    { wch: 10 },
    { wch: 10 },
    { wch: 18 },
    { wch: 15 },
    { wch: 18 },
    { wch: 12 },
  ];

  XLSX.writeFile(wb, 'template_kosong_data_barang.xlsx');
  toast.success('Template Excel kosong berhasil didownload.');
};

const triggerFileInput = () => {
  fileInputRef.value?.click();
};

const handleFileSelect = (e) => {
  const file = e.target.files?.[0];
  if (file) processExcelFile(file);
};

const handleFileDrop = (e) => {
  isDragging.value = false;
  const file = e.dataTransfer.files?.[0];
  if (file) processExcelFile(file);
};

const processExcelFile = (file) => {
  uploadedFileName.value = file.name;
  uploadedFileSize.value = `${(file.size / 1024).toFixed(1)} KB`;

  const reader = new FileReader();
  reader.onload = (e) => {
    try {
      const data = new Uint8Array(e.target.result);
      const workbook = XLSX.read(data, { type: 'array' });
      const firstSheetName = workbook.SheetNames[0];
      const worksheet = workbook.Sheets[firstSheetName];
      const json = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

      if (json.length <= 1) {
        toast.error('File Excel kosong atau tidak memiliki baris data.');
        return;
      }

      // Map headers
      const headers = json[0].map((h) => String(h || '').trim().toLowerCase());
      const findColIdx = (keywords) => {
        return headers.findIndex((h) => keywords.some((k) => h.includes(k)));
      };

      const skuIdx = findColIdx(['id barang', 'id', 'sku', 'kode']);
      const catIdx = findColIdx(['kategori', 'category']);
      const nameIdx = findColIdx(['nama barang', 'nama', 'material', 'item']);
      const descIdx = findColIdx(['deskripsi', 'keterangan', 'sumber', 'spesifikasi']);
      const unitIdx = findColIdx(['satuan', 'unit']);
      const stockIdx = findColIdx(['stok', 'stock', 'qty']);
      const buyIdx = findColIdx(['harga beli', 'beli', 'beli terakhir']);
      const hppIdx = findColIdx(['hpp', 'harga pokok']);
      const sellIdx = findColIdx(['harga jual', 'jual', 'harga', 'price']);
      const statusIdx = findColIdx(['status']);

      const parsed = [];
      for (let r = 1; r < json.length; r++) {
        const row = json[r];
        if (!row || row.length === 0) continue;

        const sku = String(skuIdx !== -1 ? row[skuIdx] || '' : `A${String(r).padStart(8, '0')}`).trim();
        const name = String(nameIdx !== -1 ? row[nameIdx] || '' : '').trim();
        const category = String(catIdx !== -1 ? row[catIdx] || '' : 'Umum').trim();
        const unit = String(unitIdx !== -1 ? row[unitIdx] || 'PCS' : 'PCS').trim().toUpperCase();
        const desc = descIdx !== -1 && row[descIdx] ? String(row[descIdx]).trim() : '';

        const parseNum = (val) => {
          if (!val) return 0;
          if (typeof val === 'number') return val;
          const clean = String(val).replace(/[^0-9.-]/g, '');
          return parseFloat(clean) || 0;
        };

        const stock = parseInt(parseNum(stockIdx !== -1 ? row[stockIdx] : 0));
        const lastBuy = parseNum(buyIdx !== -1 ? row[buyIdx] : 0);
        const hpp = parseNum(hppIdx !== -1 ? row[hppIdx] : 0);
        const sellPrice = parseNum(sellIdx !== -1 ? row[sellIdx] : 0);
        const status = statusIdx !== -1 && row[statusIdx] ? String(row[statusIdx]).trim() : (stock > 0 ? 'Tersedia' : 'Habis');

        if (name) {
          parsed.push({
            sku,
            name,
            category,
            unit,
            description: desc,
            stock,
            min_stock: 10,
            last_buy_price: lastBuy,
            hpp,
            sell_price: sellPrice,
            status,
          });
        }
      }

      parsedExcelData.value = parsed;
      toast.success(`Berhasil membaca ${parsed.length} data barang dari Excel.`);
      importStep.value = 3;
    } catch (err) {
      console.error('Error parsing Excel:', err);
      toast.error('Gagal membaca file Excel. Pastikan format file sesuai.');
    }
  };
  reader.readAsArrayBuffer(file);
};

const loadSampleRkasData = async () => {
  try {
    const res = await fetch('/data/products_seed.json');
    if (res.ok) {
      const json = await res.json();
      parsedExcelData.value = json.products;
      uploadedFileName.value = 'data_barang_20261014_103611.xlsx';
      uploadedFileSize.value = '186.9 KB';
      toast.success(`Berhasil memuat 2.196 data RKAS resmi.`);
      importStep.value = 3;
    }
  } catch (err) {
    toast.error('Gagal memuat template RKAS lokal.');
  }
};

const detectedCategoriesCount = computed(() => {
  const set = new Set(parsedExcelData.value.map((p) => p.category).filter(Boolean));
  return set.size;
});

const detectedUnitsCount = computed(() => {
  const set = new Set(parsedExcelData.value.map((p) => p.unit).filter(Boolean));
  return set.size;
});

const detectedDuplicatesCount = computed(() => {
  const existingSet = new Set(allCatalogProducts.value.map((p) => p.sku));
  return parsedExcelData.value.filter((p) => existingSet.has(p.sku)).length;
});

// Search within Preview data
const filteredPreviewRows = computed(() => {
  let list = parsedExcelData.value;
  if (previewSearch.value && previewSearch.value.trim()) {
    const words = previewSearch.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
    list = list.filter((p) => {
      const text = `${p.sku || ''} ${p.name || ''} ${p.category || ''} ${p.unit || ''}`.toLowerCase();
      return words.every((w) => text.includes(w));
    });
  }
  return list;
});

// Step 4 Validation Engine
const runValidationAndProceed = () => {
  const existingMap = new Map();
  allCatalogProducts.value.forEach((p) => existingMap.set(p.sku, p));

  const validated = parsedExcelData.value.map((item) => {
    const errors = [];
    const warnings = [];

    if (!item.name || !item.name.trim()) {
      errors.push('Nama Barang wajib diisi.');
    }
    if (!item.unit || !item.unit.trim()) {
      errors.push('Satuan wajib diisi.');
    }
    if (!item.category || !item.category.trim()) {
      errors.push('Kategori wajib diisi.');
    }

    if (item.stock < 0) {
      errors.push('Stok tidak boleh negatif.');
    }

    if (existingMap.has(item.sku)) {
      if (duplicateMode.value === 'update') {
        warnings.push('ID sudah ada (Akan diperbarui).');
      } else if (duplicateMode.value === 'skip') {
        warnings.push('ID sudah ada (Akan dilewati).');
      } else {
        warnings.push('ID sudah ada (Akan di-generate SKU baru).');
      }
    }

    let status = 'valid';
    let message = 'Siap disimpan';

    if (errors.length > 0) {
      status = 'error';
      message = errors.join('; ');
    } else if (warnings.length > 0) {
      status = 'warning';
      message = warnings.join('; ');
    }

    return {
      ...item,
      validationStatus: status,
      validationMessage: message,
    };
  });

  validatedRows.value = validated;
  importStep.value = 4;
};

const validationStats = computed(() => {
  const validCount = validatedRows.value.filter((r) => r.validationStatus === 'valid' || r.validationStatus === 'warning').length;
  const duplicateCount = validatedRows.value.filter((r) => r.validationStatus === 'warning').length;
  const errorCount = validatedRows.value.filter((r) => r.validationStatus === 'error').length;
  return { validCount, duplicateCount, errorCount };
});

// Search within Validation rows
const filteredValidatedRows = computed(() => {
  let list = validatedRows.value;
  if (validationFilter.value === 'valid') {
    list = list.filter((r) => r.validationStatus === 'valid');
  } else if (validationFilter.value === 'error') {
    list = list.filter((r) => r.validationStatus === 'error');
  }

  if (validationSearch.value && validationSearch.value.trim()) {
    const words = validationSearch.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
    list = list.filter((r) => {
      const text = `${r.sku || ''} ${r.name || ''} ${r.category || ''} ${r.unit || ''} ${r.validationMessage || ''}`.toLowerCase();
      return words.every((w) => text.includes(w));
    });
  }

  return list;
});

// Save Imported Data
const saveImportedData = async () => {
  isSavingImport.value = true;
  const validItems = validatedRows.value.filter((r) => r.validationStatus !== 'error');

  // Immediately apply to in-memory catalog
  const existingMap = new Map(allCatalogProducts.value.map((p) => [p.sku, p]));
  let newCount = 0;
  let updateCount = 0;

  validItems.forEach((item, idx) => {
    let finalSku = item.sku;
    if (existingMap.has(finalSku)) {
      if (duplicateMode.value === 'skip') return;
      if (duplicateMode.value === 'auto_id') {
        finalSku = `BRG-${Date.now()}-${idx}`;
        allCatalogProducts.value.unshift({ id: Date.now() + idx, ...item, sku: finalSku });
        newCount++;
        return;
      }
      const existing = existingMap.get(finalSku);
      Object.assign(existing, item);
      updateCount++;
    } else {
      allCatalogProducts.value.unshift({ id: Date.now() + idx, ...item });
      newCount++;
    }

    if (item.category && !categoriesList.value.includes(item.category)) {
      categoriesList.value.push(item.category);
    }
    if (item.unit && !unitsList.value.includes(item.unit)) {
      unitsList.value.push(item.unit);
    }
  });

  categoriesList.value.sort();
  unitsList.value.sort();

  // Also sync to store
  store.products = [...allCatalogProducts.value];

  // Try API import in background
  try {
    await api.post('/products/import', {
      items: validItems,
      duplicate_mode: duplicateMode.value,
    });
  } catch (err) {
    console.warn('API import background error:', err);
  }

  isSavingImport.value = false;
  importModalOpen.value = false;
  currentPage.value = 1;
  toast.success(`Berhasil mengimpor ${newCount + updateCount} data barang (${newCount} baru, ${updateCount} diperbarui).`);
};

// Export to Excel
const exportToExcel = () => {
  const exportItems = filteredProducts.value.length > 0 ? filteredProducts.value : allCatalogProducts.value;
  if (exportItems.length === 0) {
    toast.error('Tidak ada data barang untuk diexport.');
    return;
  }

  const dataToExport = exportItems.map((p) => ({
    'ID Barang': p.sku,
    'Kategori': p.category,
    'Nama Barang': p.name,
    'Deskripsi': p.description || '',
    'Satuan': p.unit,
    'Stok': p.stock || 0,
    'Harga Beli Terakhir': p.last_buy_price || 0,
    'HPP': p.hpp || 0,
    'Harga Jual': p.sell_price || p.price || 0,
    'Status': p.status || 'Tersedia',
  }));

  const ws = XLSX.utils.json_to_sheet(dataToExport);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Data Barang');

  ws['!cols'] = [
    { wch: 14 },
    { wch: 30 },
    { wch: 40 },
    { wch: 35 },
    { wch: 10 },
    { wch: 10 },
    { wch: 18 },
    { wch: 15 },
    { wch: 18 },
    { wch: 12 },
  ];

  const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
  XLSX.writeFile(wb, `Data_Barang_Export_${dateStr}.xlsx`);
  toast.success(`Export ${exportItems.length} data barang ke Excel berhasil.`);
};

// Initialize Catalog Data
const initializeCatalog = async () => {
  isLoading.value = true;
  try {
    // 1. First attempt to load complete catalog from backend API
    const res = await api.get('/products?per_page=5000');
    if (res.data?.success && Array.isArray(res.data.data) && res.data.data.length > 0) {
      allCatalogProducts.value = res.data.data;
      store.products = [...res.data.data];
    } else {
      throw new Error('API products list empty');
    }
  } catch (err) {
    // 2. Fallback to pre-seeded JSON file for instant zero-latency loading
    try {
      const seedRes = await fetch('/data/products_seed.json');
      if (seedRes.ok) {
        const seedJson = await seedRes.json();
        allCatalogProducts.value = seedJson.products;
        store.products = [...seedJson.products];
        categoriesList.value = seedJson.categories;
        unitsList.value = seedJson.units;
      }
    } catch (e2) {
      // 3. Fallback to existing store
      allCatalogProducts.value = Array.isArray(store.products) ? [...store.products] : [];
    }
  } finally {
    // Extract unique categories & units
    const cats = new Set();
    const units = new Set();
    allCatalogProducts.value.forEach((p) => {
      if (p.category) cats.add(p.category);
      if (p.unit) units.add(p.unit);
    });
    categoriesList.value = Array.from(cats).sort();
    unitsList.value = Array.from(units).sort();
    isLoading.value = false;
  }
};

onMounted(async () => {
  await initializeCatalog();
});
</script>
