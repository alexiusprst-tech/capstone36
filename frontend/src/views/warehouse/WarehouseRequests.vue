<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <!-- Title with PackageCheck icon -->
        <div class="flex items-center gap-2.5">
          <div class="p-2 bg-emerald-50 text-emerald-700 rounded-xl">
            <ClipboardList class="w-6 h-6 stroke-[2.2]" />
          </div>
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
              Pengajuan Kebutuhan Barang (Material Request)
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
              Permohonan pengadaan bahan material gudang yang diajukan ke <span class="font-semibold text-emerald-700">Finance</span> untuk pembelian & persiapan proyek.
            </p>
          </div>
        </div>

        <!-- Top Right Action -->
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold rounded-lg text-xs shadow-xs transition-colors cursor-pointer"
            @click="openCreateModal"
          >
            <Plus class="w-4 h-4 stroke-[2.5]" />
            <span>Buat Pengajuan Baru</span>
          </button>
        </div>
      </div>
    </template>

    <!-- INFO BANNER: ALUR BPMN WAREHOUSE -> FINANCE -->
    <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-sky-50 border border-emerald-200/80 rounded-xl p-4 mb-5 text-xs text-slate-700 flex flex-col md:flex-row items-start md:items-center justify-between gap-3 shadow-2xs">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0">
          <ArrowRightLeft class="w-4 h-4" />
        </div>
        <div>
          <span class="font-bold text-emerald-950 block">Alur Proses Pengadaan: Gudang &rarr; Finance</span>
          <span class="text-slate-600">
            Pengajuan yang dibuat di halaman ini akan langsung diteruskan ke Finance untuk di-generate menjadi <strong>Transaksi Pembelian</strong> dengan No Faktur & Supplier.
          </span>
        </div>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <span class="px-2.5 py-1 bg-amber-100 text-amber-800 font-bold rounded-full text-[11px]">
          {{ pendingCount }} Menunggu Finance
        </span>
        <span class="px-2.5 py-1 bg-sky-100 text-sky-800 font-bold rounded-full text-[11px]">
          {{ inProgressCount }} Diproses Pembelian
        </span>
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
              placeholder="Cari kode pengajuan, proyek, atau material..."
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

          <!-- Status Filter -->
          <div class="md:col-span-3">
            <select
              v-model="statusFilter"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-emerald-500 bg-white text-slate-800 cursor-pointer"
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
              v-model="projectFilter"
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-emerald-500 bg-white text-slate-800 cursor-pointer"
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
              @click="resetFilters"
            >
              <RotateCcw class="w-3.5 h-3.5 text-slate-500" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- REQUESTS TABLE CARD -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden mb-6">
      <div class="h-1 bg-emerald-600 w-full"></div>

      <!-- Card Table Header -->
      <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between text-xs">
        <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px]">Daftar Pengajuan Kebutuhan Barang Gudang</span>
        <span class="text-slate-500 font-medium font-mono">{{ filteredRequests.length }} Pengajuan Ditemukan</span>
      </div>

      <!-- Table Container -->
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-[11px] uppercase tracking-wide">
              <th class="py-3 px-4 whitespace-nowrap">Kode Pengajuan</th>
              <th class="py-3 px-4 whitespace-nowrap">Tanggal</th>
              <th class="py-3 px-4">Proyek & Keperluan</th>
              <th class="py-3 px-4">Item Material yang Diajukan</th>
              <th class="py-3 px-4 text-right whitespace-nowrap">Estimasi Biaya</th>
              <th class="py-3 px-4 text-center whitespace-nowrap">Status Alur</th>
              <th class="py-3 px-4 text-right whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="filteredRequests.length === 0">
              <td colspan="7" class="py-12 text-center text-slate-500">
                <div class="flex flex-col items-center justify-center gap-2">
                  <Package class="w-8 h-8 text-slate-300" />
                  <span class="text-xs font-semibold text-slate-700">Tidak ada data pengajuan yang sesuai.</span>
                  <span class="text-[11px] text-slate-400">Klik "Buat Pengajuan Baru" untuk mengajukan kebutuhan ke Finance.</span>
                </div>
              </td>
            </tr>

            <tr
              v-for="req in filteredRequests"
              :key="req.id"
              class="hover:bg-slate-50/70 transition-colors"
            >
              <!-- Kode Pengajuan -->
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="font-mono font-bold text-slate-900 block">{{ req.requestCode || req.request_code }}</span>
                <span class="text-[10px] text-slate-400">Oleh: {{ req.requestedBy || req.requested_by }}</span>
              </td>

              <!-- Tanggal -->
              <td class="py-3 px-4 text-slate-600 whitespace-nowrap font-mono text-[11px]">
                {{ req.date || req.request_date }}
              </td>

              <!-- Proyek & Keperluan -->
              <td class="py-3 px-4 max-w-xs">
                <div class="font-semibold text-slate-900 leading-snug">
                  {{ req.projectName || req.project_name || 'Umum / Restock' }}
                </div>
                <div class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                  {{ req.purpose }}
                </div>
              </td>

              <!-- Item Material -->
              <td class="py-3 px-4 max-w-sm">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="(it, idx) in req.items"
                    :key="idx"
                    class="inline-flex items-center gap-1 text-[11px] bg-slate-100 border border-slate-200 text-slate-800 px-2 py-0.5 rounded-md"
                  >
                    <span class="font-semibold">{{ it.name }}</span>
                    <span class="text-emerald-700 font-mono font-bold">({{ it.qty }} {{ it.unit || 'UNIT' }})</span>
                  </span>
                </div>
              </td>

              <!-- Estimasi Biaya -->
              <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                Rp {{ Number(req.totalEstCost || req.total_est_cost || 0).toLocaleString('id-ID') }}
              </td>

              <!-- Status Alur -->
              <td class="py-3 px-4 text-center whitespace-nowrap">
                <span
                  v-if="(req.status === 'MENUNGGU_FINANCE' || req.status === 'Draft' || req.status === 'Menunggu Verifikasi')"
                  class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[10px] font-bold"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                  Menunggu Finance
                </span>
                <span
                  v-else-if="(req.status === 'DIPROSES_PEMBELIAN' || req.status === 'Diproses')"
                  class="inline-flex items-center gap-1 px-2.5 py-1 bg-sky-50 text-sky-700 border border-sky-200 rounded-full text-[10px] font-bold"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                  Diproses Pembelian
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-bold"
                >
                  <Check class="w-3 h-3 stroke-[3]" />
                  Selesai
                </span>

                <div v-if="req.purchase_code || req.purchaseCode" class="text-[10px] text-slate-400 font-mono mt-0.5">
                  PO: {{ req.purchase_code || req.purchaseCode }}
                </div>
              </td>

              <!-- Aksi -->
              <td class="py-3 px-4 text-right whitespace-nowrap">
                <div class="inline-flex items-center gap-1.5">
                  <button
                    type="button"
                    class="px-2.5 py-1 text-slate-700 hover:text-emerald-700 hover:bg-emerald-50 border border-slate-300 hover:border-emerald-300 rounded text-xs font-semibold transition-colors cursor-pointer"
                    @click="openDetail(req)"
                  >
                    Detail
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL 1: BUAT PENGAJUAN BARU -->
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
            <label class="block font-bold text-slate-700 uppercase text-[11px] mb-1">
              Proyek Pengadaan <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="form.project_name"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-emerald-500 bg-white"
            >
              <option v-for="proj in availableProjects" :key="proj.id" :value="proj.name">
                {{ proj.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 uppercase text-[11px] mb-1">
              Pemohon <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="form.requested_by"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-slate-50 text-slate-700 font-medium"
              readonly
            />
          </div>
        </div>

        <!-- Keperluan Pengadaan -->
        <div>
          <label class="block font-bold text-slate-700 uppercase text-[11px] mb-1">
            Keperluan / Alasan Pengadaan <span class="text-rose-500">*</span>
          </label>
          <input
            v-model="form.purpose"
            required
            placeholder="Contoh: Kebutuhan pengadaan 100 unit komputer sekolah & notebook lab TIK..."
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
          />
        </div>

        <!-- Item Material yang Diajukan -->
        <div class="border border-slate-200 rounded-xl p-3 bg-slate-50/70 space-y-3">
          <div class="flex items-center justify-between">
            <span class="font-bold text-slate-800 uppercase text-[11px] flex items-center gap-1.5">
              <Boxes class="w-4 h-4 text-emerald-600" />
              Item Material yang Diajukan ({{ form.items.length }} Barang)
            </span>
            <button
              type="button"
              class="inline-flex items-center gap-1 text-emerald-700 font-bold hover:text-emerald-800 cursor-pointer"
              @click="addItem"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Tambah Baris</span>
            </button>
          </div>

          <div
            v-for="(it, idx) in form.items"
            :key="idx"
            class="bg-white p-3 rounded-lg border border-slate-200 shadow-2xs space-y-2"
          >
            <div class="grid grid-cols-12 gap-2 items-center">
              <!-- Item Selector from Data Barang (Not manual typing) -->
              <div class="col-span-12 sm:col-span-6">
                <div class="flex items-center justify-between mb-0.5">
                  <label class="block text-[10px] text-slate-600 font-bold uppercase tracking-wider">
                    PILIH BARANG DARI DATA BARANG <span class="text-rose-500">*</span>
                  </label>
                  <span v-if="it.sku" class="text-[10px] text-emerald-700 font-mono font-bold flex items-center gap-0.5">
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
                      <span class="font-mono text-emerald-900 font-bold bg-emerald-100 px-1.5 py-0.5 rounded text-[11px] mr-1.5">
                        {{ it.sku }}
                      </span>
                      <span class="font-bold text-slate-900">{{ it.name }}</span>
                    </div>
                    <span v-else class="text-slate-400 font-medium italic">
                      -- Klik di sini untuk memilih barang dari Data Barang --
                    </span>
                  </div>

                  <div class="flex items-center gap-1 shrink-0 ml-2">
                    <span class="px-2.5 py-1 bg-gradient-to-b from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700 text-white border border-sky-600/30 rounded-md font-bold text-[10px] flex items-center gap-1 transition-all shadow-2xs">
                      <Search class="w-3 h-3 stroke-[2.5]" />
                      <span>{{ it.sku ? 'Ganti' : 'Pilih Barang' }}</span>
                    </span>
                  </div>
                </div>

                <!-- Item metadata from master data barang -->
                <div v-if="it.sku" class="flex flex-wrap items-center gap-2 mt-1 text-[10px] text-slate-500">
                  <span v-if="it.category" class="bg-slate-100 px-1.5 py-0.2 rounded font-medium">
                    Kategori: <strong>{{ it.category }}</strong>
                  </span>
                  <span class="bg-amber-50 text-amber-800 border border-amber-200 px-1.5 py-0.2 rounded font-medium">
                    Stok Gudang: <strong>{{ it.current_stock ?? 0 }} {{ it.unit || 'UNIT' }}</strong>
                  </span>
                </div>
              </div>

              <!-- Qty & Satuan (Satuan is locked to master Data Barang) -->
              <div class="col-span-6 sm:col-span-3">
                <label class="block text-[10px] text-slate-600 font-bold uppercase mb-0.5">Jumlah & Satuan</label>
                <div class="flex gap-1.5">
                  <input
                    v-model.number="it.qty"
                    type="number"
                    min="1"
                    required
                    placeholder="Qty"
                    class="w-full px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg text-center font-bold text-slate-900 bg-white focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
                  />
                  <div
                    class="w-20 px-2 py-1.5 text-xs border border-slate-200 bg-slate-100 rounded-lg text-center font-bold text-slate-700 uppercase shrink-0 flex items-center justify-center select-none"
                    title="Satuan otomatis mengikuti master data barang"
                  >
                    {{ it.unit || 'UNIT' }}
                  </div>
                </div>
              </div>

              <!-- Estimasi Harga -->
              <div class="col-span-5 sm:col-span-2">
                <label class="block text-[10px] text-slate-600 font-bold uppercase mb-0.5">Est. Harga Satuan</label>
                <input
                  v-model.number="it.est_price"
                  type="number"
                  min="0"
                  placeholder="Rp"
                  class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded-lg text-right font-mono text-slate-900 bg-white focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
                />
              </div>

              <!-- Delete Action (Red Gradient matching delete button) -->
              <div class="col-span-1 flex items-end justify-center pb-0.5">
                <button
                  v-if="form.items.length > 1"
                  type="button"
                  class="w-7 h-7 inline-flex items-center justify-center bg-gradient-to-b from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 active:from-red-700 active:to-red-800 text-white rounded-lg shadow-2xs border border-red-700/30 transition-all cursor-pointer"
                  title="Hapus baris"
                  @click="removeItem(idx)"
                >
                  <Trash2 class="w-3.5 h-3.5 stroke-[2.2]" />
                </button>
              </div>
            </div>

            <!-- Subtotal Baris -->
            <div class="flex justify-end text-[11px] text-slate-500 font-mono pt-1 border-t border-slate-100">
              Subtotal: <span class="font-bold text-slate-800 ml-1">Rp {{ (it.qty * (it.est_price || 0)).toLocaleString('id-ID') }}</span>
            </div>
          </div>

          <!-- Total Estimasi -->
          <div class="flex items-center justify-between p-3 bg-emerald-50/70 border border-emerald-200 rounded-lg">
            <span class="font-bold text-emerald-950 uppercase text-[11px]">Total Estimasi Biaya Pengadaan:</span>
            <span class="text-sm font-bold font-mono text-emerald-900">
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
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-xs transition-colors cursor-pointer"
          >
            <Send class="w-3.5 h-3.5" />
            <span>Kirim Pengajuan ke Finance</span>
          </button>
        </div>
      </form>
    </Modal>

    <!-- MODAL 2: DETAIL PENGAJUAN -->
    <Modal
      v-model="detailModalOpen"
      :title="'Detail Pengajuan: ' + (selectedRequest?.requestCode || selectedRequest?.request_code)"
      subtitle="Informasi kebutuhan material gudang dan progres di bagian Keuangan (Finance)."
      size="lg"
    >
      <div v-if="selectedRequest" class="space-y-4 text-xs">
        <!-- Header Info Card -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
          <div>
            <div class="text-[10px] text-slate-400 uppercase font-semibold">Proyek</div>
            <div class="font-bold text-slate-900 mt-0.5">{{ selectedRequest.projectName || selectedRequest.project_name || '-' }}</div>
          </div>
          <div>
            <div class="text-[10px] text-slate-400 uppercase font-semibold">Tanggal Diajukan</div>
            <div class="font-bold text-slate-900 font-mono mt-0.5">{{ selectedRequest.date || selectedRequest.request_date }}</div>
          </div>
          <div>
            <div class="text-[10px] text-slate-400 uppercase font-semibold">Pemohon</div>
            <div class="font-bold text-slate-900 mt-0.5">{{ selectedRequest.requestedBy || selectedRequest.requested_by }}</div>
          </div>
        </div>

        <div>
          <div class="text-[11px] font-bold text-slate-700 uppercase mb-1">Keperluan Pengadaan</div>
          <div class="p-3 bg-white border border-slate-200 rounded-lg text-slate-800">
            {{ selectedRequest.purpose }}
          </div>
        </div>

        <!-- Table of Items -->
        <div>
          <div class="text-[11px] font-bold text-slate-700 uppercase mb-1">Daftar Barang yang Diajukan</div>
          <div class="border border-slate-200 rounded-lg overflow-hidden">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-[11px]">
                <tr>
                  <th class="py-2.5 px-3">Nama Barang</th>
                  <th class="py-2.5 px-3 text-center">Jumlah</th>
                  <th class="py-2.5 px-3 text-right">Est. Harga Satuan</th>
                  <th class="py-2.5 px-3 text-right">Est. Subtotal</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(it, idx) in selectedRequest.items" :key="idx">
                  <td class="py-2.5 px-3">
                    <span class="font-semibold text-slate-900 block">{{ it.name }}</span>
                    <span v-if="it.sku" class="text-[10px] text-slate-400 font-mono">SKU: {{ it.sku }}</span>
                  </td>
                  <td class="py-2.5 px-3 text-center font-bold text-slate-800">
                    {{ it.qty }} {{ it.unit || 'UNIT' }}
                  </td>
                  <td class="py-2.5 px-3 text-right font-mono text-slate-600">
                    Rp {{ Number(it.estPrice || it.est_price || 0).toLocaleString('id-ID') }}
                  </td>
                  <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">
                    Rp {{ Number((it.qty || 0) * (it.estPrice || it.est_price || 0)).toLocaleString('id-ID') }}
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-slate-50 border-t border-slate-200 font-bold">
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

    <!-- MODAL 3: CATALOG QUICK PICKER FROM MASTER DATA BARANG -->
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
              class="w-full pl-9 pr-8 py-2 border border-slate-300 rounded-lg text-xs focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
              autofocus
            />
            <button
              v-if="catalogSearch"
              type="button"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
              @click="catalogSearch = ''"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Category Filter -->
          <div class="sm:w-56">
            <select
              v-model="catalogCategoryFilter"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs bg-white text-slate-800 focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
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
          <table class="w-full text-left text-xs border-collapse">
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
                <td colspan="7" class="py-8 text-center text-slate-400">
                  Tidak ada barang yang sesuai dengan kata kunci pencarian.
                </td>
              </tr>
              <tr
                v-for="p in filteredCatalogItems"
                :key="p.sku"
                class="hover:bg-emerald-50/70 transition-colors cursor-pointer group"
                @click="selectCatalogItem(p)"
              >
                <td class="py-2.5 px-3 font-mono font-bold text-emerald-800 whitespace-nowrap">
                  {{ p.sku }}
                </td>
                <td class="py-2.5 px-3 font-semibold text-slate-900 group-hover:text-emerald-900">
                  {{ p.name }}
                </td>
                <td class="py-2.5 px-3 text-slate-600 whitespace-nowrap">
                  {{ p.category || '-' }}
                </td>
                <td class="py-2.5 px-3 text-center font-bold text-slate-700 uppercase whitespace-nowrap">
                  {{ p.unit || 'UNIT' }}
                </td>
                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
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
                    class="px-2.5 py-1 bg-emerald-600 group-hover:bg-emerald-700 text-white rounded text-[10px] font-bold shadow-2xs transition-colors cursor-pointer"
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
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import Modal from '../../components/common/Modal.vue';
import { store } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import {
  ClipboardList,
  Plus,
  Search,
  X,
  RotateCcw,
  Boxes,
  Package,
  Trash2,
  Send,
  Info,
  Check,
  ArrowRightLeft,
} from 'lucide-vue-next';

const toast = useToast();

const searchQuery = ref('');
const statusFilter = ref('');
const projectFilter = ref('');

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

const catalogCategories = computed(() => {
  const set = new Set();
  const list = Array.isArray(catalogItems.value) ? catalogItems.value : [];
  list.forEach((p) => {
    if (p.category) set.add(p.category);
  });
  return Array.from(set).sort();
});

const form = ref({
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

// Load catalog products for quick picker
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

const filteredCatalogItems = computed(() => {
  let list = Array.isArray(catalogItems.value) ? catalogItems.value : [];
  if (catalogCategoryFilter.value) {
    list = list.filter((p) => p.category === catalogCategoryFilter.value);
  }
  if (catalogSearch.value) {
    const q = catalogSearch.value.toLowerCase();
    list = list.filter(
      (p) => p.name?.toLowerCase().includes(q) || p.sku?.toLowerCase().includes(q)
    );
  }
  return list.slice(0, 50);
});

// Ensure default realistic request REQ-2026-001 is present
if (!store.warehouseRequests || store.warehouseRequests.length === 0) {
  store.warehouseRequests = [
    {
      id: 1,
      requestCode: 'REQ-2026-001',
      date: '2026-10-14',
      requestedBy: 'Agus Santoso (Staff Gudang)',
      projectName: 'pengadaan komputer sekolah (SMA NEGRI 2 BOGOR)',
      purpose: 'Kebutuhan pengadaan komputer & notebook lab TIK proyek SMA Negeri 2 Bogor',
      status: 'MENUNGGU_FINANCE',
      totalEstCost: 10000000,
      items: [
        {
          sku: 'A00001770',
          name: 'A00001770 - Pemeliharaan Komputer Note Book/ Laptop',
          qty: 100,
          unit: 'UNIT',
          estPrice: 100000,
          subtotal: 10000000,
        },
      ],
    },
  ];
}

const filteredRequests = computed(() => {
  return (store.warehouseRequests || []).filter((req) => {
    const code = req.requestCode || req.request_code || '';
    const proj = req.projectName || req.project_name || '';
    const purp = req.purpose || '';
    const status = req.status || '';

    if (statusFilter.value && status !== statusFilter.value) {
      return false;
    }

    if (projectFilter.value && proj !== projectFilter.value) {
      return false;
    }

    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase();
      const matchText = `${code} ${proj} ${purp}`.toLowerCase();
      const matchItem = req.items?.some((i) => i.name?.toLowerCase().includes(q) || i.sku?.toLowerCase().includes(q));
      if (!matchText.includes(q) && !matchItem) return false;
    }

    return true;
  });
});

const pendingCount = computed(() => {
  return (store.warehouseRequests || []).filter((r) => r.status === 'MENUNGGU_FINANCE' || r.status === 'Draft').length;
});

const inProgressCount = computed(() => {
  return (store.warehouseRequests || []).filter((r) => r.status === 'DIPROSES_PEMBELIAN' || r.status === 'Diproses').length;
});

const computedTotalEstCost = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (item.qty || 0) * (item.est_price || 0), 0);
});

const resetFilters = () => {
  searchQuery.value = '';
  statusFilter.value = '';
  projectFilter.value = '';
};

const openCreateModal = () => {
  form.value = {
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

const addItem = () => {
  const newIdx = form.value.items.length;
  form.value.items.push({
    sku: '',
    name: '',
    category: '',
    current_stock: 0,
    qty: 1,
    unit: '',
    est_price: 0,
  });
  // Immediately open the picker so the user selects from Data Barang
  openCatalogPicker(newIdx);
};

const removeItem = (idx) => {
  form.value.items.splice(idx, 1);
};

const openCatalogPicker = (idx) => {
  activeItemIndex.value = idx;
  catalogSearch.value = '';
  catalogCategoryFilter.value = '';
  catalogPickerOpen.value = true;
};

const selectCatalogItem = (prod) => {
  if (activeItemIndex.value !== null && form.value.items[activeItemIndex.value]) {
    form.value.items[activeItemIndex.value] = {
      sku: prod.sku,
      name: prod.name,
      category: prod.category || 'Umum',
      current_stock: prod.stock ?? 0,
      qty: form.value.items[activeItemIndex.value].qty || 1,
      unit: prod.unit || 'UNIT',
      est_price: prod.last_buy_price || prod.hpp || prod.sell_price || 0,
    };
  }
  catalogPickerOpen.value = false;
};

const saveRequest = async () => {
  // Validate that all items have a valid selected SKU from Data Barang
  const unselected = form.value.items.filter((i) => !i.sku || !i.name);
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
    requestedBy: form.value.requested_by,
    requested_by: form.value.requested_by,
    projectName: form.value.project_name,
    project_name: form.value.project_name,
    purpose: form.value.purpose,
    status: 'MENUNGGU_FINANCE',
    items: form.value.items.map((i) => ({
      sku: i.sku || '-',
      name: i.name,
      qty: i.qty,
      unit: i.unit || 'UNIT',
      estPrice: i.est_price,
      est_price: i.est_price,
      subtotal: i.qty * (i.est_price || 0),
    })),
    totalEstCost: total,
    total_est_cost: total,
  };

  store.warehouseRequests.unshift(newReq);

  // Sync to backend API if available
  try {
    fetch('/api/warehouse/requests', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(newReq),
    }).catch(() => {});
  } catch (e) {}

  toast.success(`Pengajuan ${requestCode} berhasil diajukan ke Finance.`);
  createModalOpen.value = false;
};

const openDetail = (req) => {
  selectedRequest.value = req;
  detailModalOpen.value = true;
};
</script>
