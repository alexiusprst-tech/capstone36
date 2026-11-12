<template>
  <AppLayout>
    <!-- TOP HEADER (Only on list view) -->
    <template #header v-if="currentView === 'list'">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-alpharo-50 text-alpharo-700">
              Role Administrasi
            </span>
            <span class="text-xs text-slate-400 font-medium">CV Alpharo Artha Jaya</span>
          </div>
          <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mt-1">Dokumen SPJ (Surat Pertanggungjawaban)</h1>
          <p class="text-sm text-slate-500">
            Pusat penyusunan, verifikasi kelengkapan lintas modul (Marketing, Direktur, Warehouse, Finance), dan penerbitan 15 dokumen SPJ resmi.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-lg text-xs border border-slate-300 shadow-2xs transition-colors cursor-pointer"
            @click="openProjectDataView"
          >
            <FolderKanban class="w-4 h-4 text-alpharo-600" />
            <span>Data Project</span>
          </button>

          <button
            type="button"
            :disabled="selectedDocKeys.length === 0 || isBulkGenerating"
            :class="[
              'inline-flex items-center gap-1.5 px-4 py-2 font-semibold rounded-lg text-xs shadow-2xs transition-all',
              selectedDocKeys.length > 0
                ? 'bg-alpharo-600 hover:bg-alpharo-700 text-white cursor-pointer'
                : 'bg-slate-200 text-slate-400 cursor-not-allowed'
            ]"
            @click="handleBulkGenerate"
          >
            <Sparkles class="w-4 h-4" :class="{ 'animate-spin': isBulkGenerating }" />
            <span>{{ isBulkGenerating ? 'Memproses Dokumen...' : `Generate Terpilih (${selectedDocKeys.length})` }}</span>
          </button>

          <!-- PROBIS TO-BE Tahap 11 & 12: Serahkan SPJ ke Gudang untuk Pengiriman -->
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 font-semibold rounded-lg text-xs shadow-2xs transition-all border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 cursor-pointer"
            title="Selesaikan SPJ dan kirim notifikasi ke Gudang untuk persiapan pengiriman armada"
            @click="notifyWarehouseSpjReady"
          >
            <Truck class="w-4 h-4" />
            <span>Kirim SPJ ke Gudang</span>
          </button>
        </div>
      </div>
    </template>

    <!-- ========================================================================= -->
    <!-- VIEW 1: DAFTAR 15 DOKUMEN SPJ (CHECKLIST VERTIKAL DENGAN SOFT GREEN THEME) -->
    <!-- ========================================================================= -->
    <div v-if="currentView === 'list'">
      <!-- TOP SECTION: PROJECT SELECTOR & QUICK SUMMARY -->
      <div class="mb-6 space-y-4">
        <!-- Project Selection & Quick Details Card -->
        <div class="ui-panel bg-white rounded-xl border border-slate-200 p-4 sm:p-5 shadow-2xs">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-center">
            <!-- Dropdown Project -->
            <div class="lg:col-span-5">
              <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Pilih Transaksi / Project Aktif
              </label>
              <div class="relative">
                <select
                  v-model="selectedProjectId" :disabled="Boolean(isGeneratingKey)"
                  class="w-full pl-3 pr-10 py-2.5 text-xs sm:text-sm font-semibold text-slate-900 bg-slate-50 border border-slate-300 rounded-lg focus:ring-2 focus:ring-alpharo-400 focus:border-alpharo-400"
                  @change="handleProjectChange"
                >
                  <option v-for="proj in projectsList" :key="proj.project_id" :value="proj.project_id">
                    {{ proj.project_id }} — {{ proj.project_name }} ({{ proj.customer_name }})
                  </option>
                </select>
              </div>
            </div>

            <!-- Project Metadata Snippets -->
            <div class="lg:col-span-7 grid min-w-0 grid-cols-2 gap-3 text-xs sm:grid-cols-4 lg:border-l lg:border-slate-100 lg:pl-4">
              <div>
                <p class="text-slate-400 text-[11px] font-medium">Customer</p>
                <p class="font-semibold text-slate-800 truncate" :title="activeProject?.customer_name">
                  {{ activeProject?.customer_name || '-' }}
                </p>
              </div>
              <div>
                <p class="text-slate-400 text-[11px] font-medium">Marketing</p>
                <p class="font-semibold text-slate-800 truncate">
                  {{ activeProject?.marketing_name || '-' }}
                </p>
              </div>
              <div>
                <p class="text-slate-400 text-[11px] font-medium">Nilai Project / Pagu</p>
                <p class="font-semibold text-emerald-700 truncate">
                  {{ formatRupiah(activeProject?.project_value || activeProject?.budget_ceiling || 0) }}
                </p>
              </div>
              <div>
                <p class="text-slate-400 text-[11px] font-medium">Sumber Dana</p>
                <p class="font-semibold text-slate-800">
                  {{ activeProject?.funding_source || '-' }} {{ activeProject?.fiscal_year ? `(${activeProject.fiscal_year})` : '' }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- METRICS STRIP -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
          <div class="ui-panel bg-white p-3 rounded-xl border border-slate-200 flex items-center gap-2.5 shadow-2xs min-w-0">
            <div class="p-2 rounded-lg bg-emerald-50 text-emerald-700 shrink-0">
              <ClipboardCheck class="w-5 h-5" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-[11px] text-slate-400 font-medium truncate">Total Dokumen</p>
              <p class="text-sm sm:text-base font-semibold text-slate-800 truncate">{{ documentItems.length }} Dokumen</p>
            </div>
          </div>

          <div class="ui-panel bg-white p-3 rounded-xl border border-slate-200 flex items-center gap-2.5 shadow-2xs min-w-0">
            <div class="p-2 rounded-lg bg-emerald-50 text-emerald-700 shrink-0">
              <CheckCircle2 class="w-5 h-5" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-[11px] text-slate-400 font-medium truncate">Data Siap (Lengkap)</p>
              <p class="text-sm sm:text-base font-semibold text-emerald-700 truncate">{{ readyCount }} Dokumen</p>
            </div>
          </div>

          <div class="ui-panel bg-white p-3 rounded-xl border border-slate-200 flex items-center gap-2.5 shadow-2xs min-w-0">
            <div class="p-2 rounded-lg bg-amber-50 text-amber-700 shrink-0">
              <AlertTriangle class="w-5 h-5" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-[11px] text-slate-400 font-medium truncate">Butuh Data Role</p>
              <p class="text-sm sm:text-base font-semibold text-amber-700 truncate">{{ incompleteCount }} Dokumen</p>
            </div>
          </div>

          <div class="ui-panel bg-white p-3 rounded-xl border border-slate-200 flex items-center gap-2.5 shadow-2xs min-w-0">
            <div class="p-2 rounded-lg bg-alpharo-50 text-alpharo-700 shrink-0">
              <Sparkles class="w-5 h-5" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-[11px] text-slate-400 font-medium truncate">Sudah Terbit (Final)</p>
              <p class="text-sm sm:text-base font-semibold text-alpharo-700 truncate">{{ completedCount }} Dokumen</p>
            </div>
          </div>

          <div class="ui-panel bg-white p-3 rounded-xl border border-slate-200 flex items-center gap-2.5 shadow-2xs min-w-0">
            <div class="p-2 rounded-lg bg-alpharo-50 text-alpharo-700 shrink-0">
              <CreditCard class="w-5 h-5" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-[11px] text-slate-400 font-medium truncate">Status Keuangan</p>
              <p
                class="font-semibold text-alpharo-700 leading-tight truncate mt-0.5 text-xs sm:text-sm"
                :title="formatFinanceStatusFull(activeProject?.finance_status)"
              >
                {{ formatFinanceStatus(activeProject?.finance_status) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- SPJ checklist: neutral rows with separate metadata, status and actions -->
      <section aria-label="Dokumen SPJ" class="ui-panel min-w-0 rounded-xl border border-slate-200 bg-white p-4 shadow-2xs sm:p-6">
        <div class="flex min-w-0 flex-col gap-3 border-b border-slate-200 pb-4 lg:flex-row lg:items-center lg:justify-between">
          <label class="inline-flex min-w-0 cursor-pointer items-start gap-2 text-xs font-semibold leading-relaxed text-slate-700">
            <input type="checkbox" :checked="isAllSelected" :indeterminate.prop="isIndeterminate" @change="toggleSelectAll"
              class="mt-0.5 h-4 w-4 shrink-0 cursor-pointer rounded border-slate-300 text-alpharo-600 focus:ring-alpharo-400" />
            <span>Pilih Semua Dokumen ({{ selectedDocKeys.length }}/{{ documentItems.length }})</span>
          </label>
          <div aria-label="Keterangan status dokumen" class="flex min-w-0 flex-wrap items-center gap-x-4 gap-y-2 text-[11px] text-slate-600">
            <span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-600"></span>Siap Dibuat</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500"></span>Butuh Data Role</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-alpharo-600"></span>Sudah Digenerate</span>
          </div>
        </div>

        <ol aria-label="Daftar dokumen SPJ" class="min-w-0 divide-y divide-slate-200">
          <li v-for="item in documentItems" :key="item.doc_key" :data-doc-key="item.doc_key" :aria-label="item.doc_name"
            :class="['grid min-w-0 gap-3 py-4 transition-colors sm:gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start',
              selectedDocKeys.includes(item.doc_key) ? 'bg-alpharo-50/60' : 'hover:bg-slate-50/70']">
            <div class="grid min-w-0 grid-cols-[1rem_1.25rem_minmax(0,1fr)] items-start gap-x-3">
              <input type="checkbox" :value="item.doc_key" v-model="selectedDocKeys" :aria-label="'Pilih dokumen ' + item.doc_name"
                class="mt-1 h-4 w-4 shrink-0 cursor-pointer rounded border-slate-300 text-alpharo-600 focus:ring-alpharo-400" />
              <span class="pt-0.5 text-center font-mono text-xs leading-relaxed text-slate-400">{{ item.doc_order }}</span>
              <div class="flex min-w-0 items-start gap-2">
                <component :is="item.iconComponent" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                <div class="min-w-0">
                  <div class="flex min-w-0 flex-wrap items-baseline gap-x-2 gap-y-1">
                    <h3 class="min-w-0 wrap-anywhere text-sm font-semibold leading-relaxed text-slate-900">{{ item.doc_name }}</h3>
                    <span class="text-[11px] leading-relaxed text-slate-500">{{ item.is_external ? 'Eksternal / Upload' : 'Otomatis Template' }}</span>
                  </div>
                  <div class="mt-1 flex min-w-0 flex-wrap gap-x-3 gap-y-1 text-[11px] leading-relaxed text-slate-500">
                    <span v-if="item.doc_number" class="min-w-0 wrap-anywhere font-mono">No: {{ item.doc_number }}</span>
                    <span v-if="item.doc_date">Tgl: {{ item.doc_date }}</span>
                    <span v-if="!item.doc_number && !item.doc_date" class="min-w-0 wrap-anywhere">{{ item.defaultDescription }}</span>
                  </div>
                  <div class="mt-2 flex min-w-0 flex-wrap items-start gap-x-4 gap-y-1.5 text-[11px] leading-relaxed">
                    <span v-if="item.is_data_complete" class="inline-flex min-w-0 items-start gap-1.5 text-emerald-700" title="Semua prasyarat data telah terpenuhi dari modul terkait">
                      <CheckCircle2 class="mt-0.5 h-3.5 w-3.5 shrink-0" /><span>Siap Dibuat</span>
                    </span>
                    <span v-else class="inline-flex min-w-0 max-w-full items-start gap-1.5 text-amber-800" :title="getMissingTooltip(item.missing_requirements)">
                      <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" /><span class="min-w-0 wrap-anywhere">{{ formatMissingSummary(item.missing_requirements) }}</span>
                    </span>
                    <span :class="['min-w-0 wrap-anywhere', getDocumentStatusClass(item.status)]"><span class="text-slate-500">Dokumen: </span>{{ formatStatusLabel(item.status) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div role="group" :aria-label="'Aksi ' + item.doc_name" class="grid min-w-0 grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center xl:justify-end">
              <button type="button" class="inline-flex min-h-9 min-w-0 items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold leading-4 text-slate-700 transition-colors hover:bg-slate-50" @click="openDocumentForm(item)">
                <Eye class="h-3.5 w-3.5 shrink-0" /><span>Buka Form</span>
              </button>
              <button type="button" :disabled="Boolean(isGeneratingKey)" class="inline-flex min-h-9 min-w-0 items-center justify-center gap-1.5 rounded-lg border border-alpharo-600 bg-alpharo-600 px-2.5 py-2 text-xs font-semibold leading-4 text-white transition-colors hover:border-alpharo-700 hover:bg-alpharo-700 disabled:cursor-wait disabled:border-slate-300 disabled:bg-slate-300" @click="handleSingleGenerate(item)">
                <Sparkles v-if="isGeneratingKey !== item.doc_key" class="h-3.5 w-3.5 shrink-0" /><RotateCcw v-else class="h-3.5 w-3.5 shrink-0 animate-spin" />
                <span>{{ item.file_path ? 'Unduh Word' : 'Generate' }}</span>
              </button>
              <button v-if="item.is_external || item.doc_key === 'bukti_foto'" type="button" class="inline-flex min-h-9 min-w-0 items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold leading-4 text-slate-700 transition-colors hover:bg-slate-50" @click="openUploadExternalModal(item)" title="Unggah berkas eksternal atau bukti foto">
                <Upload class="h-3.5 w-3.5 shrink-0" /><span>Upload</span>
              </button>
              <button v-if="item.source_file_path" type="button" class="inline-flex min-h-9 items-center justify-center rounded-lg border border-slate-300 px-2.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="downloadWithFeedback(item.doc_key, 'original')">Berkas Upload</button>
              <button type="button" :class="{ 'col-span-2 sm:col-span-1': !item.is_external && item.doc_key !== 'bukti_foto' }" class="inline-flex min-h-9 min-w-0 items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold leading-4 text-slate-700 transition-colors hover:bg-slate-50" title="Pratinjau / Cetak PDF" @click="openPdfPreview(item)">
                <Printer class="h-3.5 w-3.5 shrink-0" /><span>PDF / Cetak</span>
              </button>
            </div>
          </li>
        </ol>
      </section>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 2: EDIT FAKTUR (SCREENSHOT 11 - IV20261006143815)                   -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'edit_faktur'" class="space-y-5">
      <!-- Top Bar: Title & Kembali Button -->
      <div class="flex items-center justify-between pb-3 border-b border-slate-200">
        <div class="flex items-center gap-2">
          <Receipt class="w-6 h-6 text-emerald-600" />
          <h2 class="text-xl font-semibold text-slate-900">Edit Faktur</h2>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition-colors cursor-pointer shadow-2xs"
          @click="goBackToList"
        >
          <ArrowLeft class="w-3.5 h-3.5" />
          <span>Kembali</span>
        </button>
      </div>

      <!-- CARD 1: Header Faktur -->
      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden border-t border-t-slate-200">
        <div class="p-4 sm:p-5">
          <div class="flex items-center gap-2 mb-4 text-slate-800 font-semibold text-sm">
            <Info class="w-4 h-4 text-emerald-600" />
            <span>Header Faktur</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">Nomor Faktur</label>
              <input
                type="text"
                v-model="fakturForm.doc_number"
                class="w-full px-3 py-2 text-xs sm:text-sm font-semibold text-slate-800 bg-slate-100 border border-slate-300 rounded-lg"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">Tanggal *</label>
              <input
                type="text"
                v-model="fakturForm.doc_date"
                class="w-full px-3 py-2 text-xs sm:text-sm font-semibold text-slate-900 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-alpharo-400"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">Jenis Harga</label>
              <select
                v-model="fakturForm.jenis_harga"
                class="w-full px-3 py-2 text-xs sm:text-sm font-semibold text-slate-900 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-alpharo-400"
              >
                <option value="Harga Nett">Harga Nett</option>
                <option value="Harga Bruto">Harga Bruto</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">Project *</label>
              <select
                v-model="fakturForm.project_id"
                class="w-full px-3 py-2 text-xs sm:text-sm font-semibold text-slate-900 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-alpharo-400"
              >
                <option :value="activeProject?.project_id">
                  {{ activeProject?.project_id }} - {{ activeProject?.project_name }} ({{ activeProject?.customer_name }})
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- CARD 2: Detail Barang -->
      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden border-t border-t-slate-200">
        <div class="p-4 sm:p-5">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2 text-slate-800 font-semibold text-sm">
              <FolderKanban class="w-4 h-4 text-emerald-600" />
              <span>Detail Barang</span>
            </div>
            <button
              type="button"
              class="px-2.5 py-1 text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-md border border-emerald-200 flex items-center gap-1 cursor-pointer"
              @click="addFakturItemRow"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Tambah Baris</span>
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="ui-table w-full text-xs text-left">
              <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
                <tr>
                  <th class="py-2.5 px-3">Barang</th>
                  <th class="py-2.5 px-3 w-28 text-center">Jumlah</th>
                  <th class="py-2.5 px-3 w-40 text-right">Harga Jual</th>
                  <th class="py-2.5 px-3 w-44 text-right">Subtotal</th>
                  <th class="py-2.5 px-3 w-14 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(it, idx) in (fakturForm.items || [])" :key="idx" class="hover:bg-slate-50/50">
                  <td class="py-2.5 px-3">
                    <div class="flex items-center gap-1.5">
                      <input
                        type="text"
                        v-model="it.code"
                        placeholder="Pilih / input nama barang"
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded font-medium text-slate-900"
                      />
                      <button type="button" class="p-1.5 bg-alpharo-50 hover:bg-alpharo-100 text-alpharo-600 rounded border border-alpharo-200 cursor-pointer">
                        <Search class="w-3.5 h-3.5" />
                      </button>
                      <button type="button" class="p-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded border border-emerald-200 cursor-pointer">
                        <Plus class="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </td>
                  <td class="py-2.5 px-3 text-center">
                    <input
                      type="number"
                      v-model.number="it.quantity"
                      @input="recalculateFaktur"
                      placeholder="0"
                      class="w-full text-center px-2 py-1.5 bg-slate-50 border border-slate-300 rounded font-semibold text-slate-900"
                    />
                  </td>
                  <td class="py-2.5 px-3 text-right">
                    <input
                      type="number"
                      v-model.number="it.price"
                      @input="recalculateFaktur"
                      placeholder="0"
                      class="w-full text-right px-2 py-1.5 bg-slate-50 border border-slate-300 rounded font-semibold text-slate-900"
                    />
                  </td>
                  <td class="py-2.5 px-3 text-right font-semibold text-slate-800">
                    {{ formatRupiah(it.subtotal || (it.quantity * it.price) || 0) }}
                  </td>
                  <td class="py-2.5 px-3 text-center">
                    <button
                      type="button"
                      class="p-1.5 text-rose-600 hover:bg-rose-50 rounded transition-colors cursor-pointer"
                      @click="removeFakturItemRow(idx)"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- CARD 3: Total dan Pembayaran -->
      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden border-t border-t-slate-200">
        <div class="p-4 sm:p-5">
          <div class="flex items-center gap-2 mb-4 text-slate-800 font-semibold text-sm">
            <Calculator class="w-4 h-4 text-emerald-600" />
            <span>Total dan Pembayaran</span>
          </div>

          <div class="flex justify-end">
            <div class="w-full sm:w-96 space-y-2 text-xs">
              <div class="flex items-center justify-between py-1">
                <span class="text-slate-500 font-medium">Jumlah Harga:</span>
                <span class="font-semibold text-slate-800 text-sm">{{ formatRupiah(fakturForm.subtotal) }}</span>
              </div>

              <div class="flex items-center justify-between py-1">
                <span class="text-slate-500 font-medium">PPN:</span>
                <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 rounded bg-slate-100 font-mono text-[11px] font-semibold text-slate-600 border border-slate-200">11,00 %</span>
                  <span class="font-semibold text-slate-800">{{ formatRupiah(fakturForm.ppn) }}</span>
                </div>
              </div>

              <div class="flex items-center justify-between py-1 border-t border-slate-200">
                <span class="text-slate-700 font-semibold">Total Harga:</span>
                <span class="font-semibold text-slate-900 text-sm">{{ formatRupiah(fakturForm.total_harga) }}</span>
              </div>

              <div class="flex items-center justify-between py-1">
                <span class="text-rose-600 font-medium">PPh:</span>
                <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 rounded bg-rose-50 font-mono text-[11px] font-semibold text-rose-700 border border-rose-200">1,50 %</span>
                  <span class="font-semibold text-rose-700">- {{ formatRupiah(fakturForm.pph) }}</span>
                </div>
              </div>

              <div class="flex items-center justify-between py-2 border-t-2 border-slate-300 bg-emerald-50/70 p-2 rounded">
                <span class="text-emerald-950 font-semibold text-sm">Nilai Setelah PPh:</span>
                <span class="font-semibold text-emerald-900 text-base">{{ formatRupiah(fakturForm.nilai_setelah_pph) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- BOTTOM ACTIONS BAR -->
      <div class="flex flex-wrap items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
        <button
          type="button"
          class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer transition-colors"
          :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('faktur', 'word')"
        >
          <Sparkles class="w-4 h-4" />
          <span>Cetak Faktur Word</span>
        </button>

        <button
          type="button"
          class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer transition-colors"
          :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('faktur', 'pdf')"
        >
          <Printer class="w-4 h-4" />
          <span>Cetak Faktur PDF</span>
        </button>

        <button
          type="button"
          class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg shadow-2xs cursor-pointer transition-colors"
          @click="goBackToList"
        >
          Batal
        </button>

        <button
          type="button"
          class="px-5 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer transition-colors"
          @click="saveFakturDocument"
        >
          <CheckCircle2 class="w-4 h-4" />
          <span>Simpan</span>
        </button>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 3: EDIT PEMBAYARAN KUITANSI (SCREENSHOT 12 - PI20261006143924)       -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'edit_kuitansi'" class="space-y-5">
      <!-- Top Bar: Title & Buttons -->
      <div class="flex items-center justify-between pb-3 border-b border-slate-200">
        <div class="flex items-center gap-2">
          <CreditCard class="w-6 h-6 text-emerald-600" />
          <h2 class="text-xl font-semibold text-slate-900">Edit Pembayaran Kuitansi</h2>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-alpharo-300 bg-alpharo-50 hover:bg-alpharo-100 text-alpharo-800 font-semibold text-xs transition-colors cursor-pointer"
            @click="currentView = 'riwayat_kuitansi'"
          >
            <Clock class="w-3.5 h-3.5" />
            <span>Lihat Riwayat Kuitansi</span>
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition-colors cursor-pointer shadow-2xs"
            @click="goBackToList"
          >
            <ArrowLeft class="w-3.5 h-3.5" />
            <span>Kembali</span>
          </button>
        </div>
      </div>

      <!-- 2 COLUMN LAYOUT -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <!-- LEFT CARD: Detail Faktur -->
        <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden border-t border-t-slate-200 p-5 space-y-4">
          <div class="flex items-center gap-2 text-slate-800 font-semibold text-sm mb-1">
            <Info class="w-4 h-4 text-emerald-600" />
            <span>Detail Faktur</span>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Nomor Faktur</label>
            <input
              type="text"
              readonly
              :value="fakturForm?.doc_number || 'IV20261006143815'"
              class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg text-xs font-semibold text-slate-800"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Project</label>
            <input
              type="text"
              readonly
              :value="activeProject?.project_name || 'pengadaan komputer sekolah'"
              class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg text-xs font-semibold text-slate-800"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Tagihan</label>
            <input
              type="text"
              readonly
              :value="formatRupiah(fakturForm?.nilai_setelah_pph || 10950000)"
              class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg text-xs font-semibold text-slate-900 text-right"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Sisa Piutang</label>
            <input
              type="text"
              readonly
              value="Rp 0"
              class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg text-xs font-semibold text-rose-600 text-right"
            />
          </div>
        </div>

        <!-- RIGHT CARD: Input Pembayaran -->
        <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden border-t border-t-slate-200 p-5 space-y-3.5">
          <div class="flex items-center gap-2 text-slate-800 font-semibold text-sm mb-1">
            <CreditCard class="w-4 h-4 text-emerald-600" />
            <span>Input Pembayaran</span>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">No Transaksi</label>
            <input
              type="text"
              v-model="kuitansiForm.doc_number"
              class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg text-xs font-semibold text-slate-800"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Tanggal</label>
            <input
              type="text"
              v-model="kuitansiForm.doc_date"
              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-900"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Metode</label>
            <select
              v-model="kuitansiForm.metode"
              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-900"
            >
              <option value="Tunai">Tunai</option>
              <option value="Transfer Bank DKI">Transfer Bank DKI</option>
              <option value="Transfer Bank Lain">Transfer Bank Lain</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Jumlah</label>
            <input
              type="text"
              v-model="kuitansiForm.jumlah"
              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-900 text-right"
            />
            <p class="text-[11px] text-slate-500 mt-0.5">Maksimal: {{ formatRupiah(fakturForm?.nilai_setelah_pph || 10950000) }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Keterangan</label>
            <input
              type="text"
              v-model="kuitansiForm.keterangan"
              placeholder="-"
              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Bukti Bayar</label>
            <input
              type="file"
              class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200"
            />
            <div class="mt-2 p-2 bg-slate-50 rounded border border-slate-200 flex items-center justify-between">
              <span class="text-xs text-slate-600 flex items-center gap-1.5">
                <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                <span>Bukti pembayaran tersimpan</span>
              </span>
              <button type="button" class="px-2 py-0.5 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded text-[11px] font-semibold border border-rose-200">
                Hapus
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- BOTTOM ACTIONS BAR -->
      <div class="flex flex-wrap items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
        <button
          type="button"
          class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg shadow-2xs cursor-pointer transition-colors"
          @click="goBackToList"
        >
          Batal
        </button>

        <button
          type="button"
          class="px-5 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer transition-colors"
          @click="saveKuitansiDocument"
        >
          <CheckCircle2 class="w-4 h-4" />
          <span>Simpan</span>
        </button>

        <button
          type="button"
          class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer transition-colors"
          :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('kuitansi', 'word')"
        >
          <Sparkles class="w-4 h-4" />
          <span>Cetak Kuitansi Word</span>
        </button>

        <button
          type="button"
          class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer transition-colors"
          :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('kuitansi', 'pdf')"
        >
          <Printer class="w-4 h-4" />
          <span>Cetak Kuitansi PDF</span>
        </button>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 4: RIWAYAT KUITANSI (SCREENSHOT 13)                                 -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'riwayat_kuitansi'" class="space-y-5">
      <!-- Top Bar: Title & Subtitle & Kembali Button -->
      <div class="flex items-center justify-between pb-3 border-b border-slate-200">
        <div>
          <div class="flex items-center gap-2">
            <Clock class="w-6 h-6 text-emerald-600" />
            <h2 class="text-xl font-semibold text-slate-900">Riwayat Kuitansi</h2>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">
            {{ fakturForm?.doc_number || 'IV20261006143815' }} - {{ activeProject?.project_name || 'pengadaan komputer sekolah' }}
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition-colors cursor-pointer shadow-2xs"
          @click="goBackToList"
        >
          <ArrowLeft class="w-3.5 h-3.5" />
          <span>Kembali</span>
        </button>
      </div>

      <!-- 2 TOP CARDS: Detail Faktur & Ringkasan Pembayaran -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <!-- Detail Faktur -->
        <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs p-5 border-t border-t-slate-200 space-y-2 text-xs">
          <div class="flex items-center gap-2 text-slate-800 font-semibold text-sm mb-2">
            <Info class="w-4 h-4 text-emerald-600" />
            <span>Detail Faktur</span>
          </div>
          <div class="flex justify-between py-1 border-b border-slate-100">
            <span class="text-slate-500">Customer:</span>
            <span class="font-semibold text-slate-800">{{ activeProject?.customer_name }}</span>
          </div>
          <div class="flex justify-between py-1 border-b border-slate-100">
            <span class="text-slate-500">Tagihan:</span>
            <span class="font-semibold text-slate-800">{{ formatRupiah(10950000) }}</span>
          </div>
          <div class="flex justify-between py-1 border-b border-slate-100">
            <span class="text-slate-500">Dibayar:</span>
            <span class="font-semibold text-slate-800">{{ formatRupiah(10950000) }}</span>
          </div>
          <div class="flex justify-between py-1">
            <span class="text-slate-500">Sisa Piutang:</span>
            <span class="font-semibold text-rose-600">Rp 0</span>
          </div>
        </div>

        <!-- Ringkasan Pembayaran -->
        <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs p-5 border-t border-t-slate-200 space-y-2 text-xs">
          <div class="flex items-center gap-2 text-slate-800 font-semibold text-sm mb-2">
            <CreditCard class="w-4 h-4 text-emerald-600" />
            <span>Ringkasan Pembayaran</span>
          </div>
          <div class="flex justify-between py-1 border-b border-slate-100">
            <span class="text-slate-500">Jumlah Bayar:</span>
            <span class="font-semibold text-slate-800">1 transaksi</span>
          </div>
          <div class="flex justify-between py-1 border-b border-slate-100">
            <span class="text-slate-500">Total Bayar:</span>
            <span class="font-semibold text-slate-800">{{ formatRupiah(10950000) }}</span>
          </div>
          <div class="flex justify-between items-center py-1">
            <span class="text-slate-500">Status:</span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Lunas</span>
          </div>
        </div>
      </div>

      <!-- TABLE TRANSAKSI -->
      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="ui-table-wide ui-table w-full text-xs text-left">
            <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
              <tr>
                <th class="py-2.5 px-3 w-10 text-center">#</th>
                <th class="py-2.5 px-3">No Transaksi</th>
                <th class="py-2.5 px-3">Tanggal</th>
                <th class="py-2.5 px-3">Metode</th>
                <th class="py-2.5 px-3">Jumlah Keterangan</th>
                <th class="py-2.5 px-3 w-16 text-center">User</th>
                <th class="py-2.5 px-3 w-36 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr class="hover:bg-slate-50/50">
                <td class="py-3 px-3 text-center font-semibold text-slate-600">1</td>
                <td class="py-3 px-3 font-mono font-semibold text-slate-800">{{ kuitansiForm?.doc_number || 'PI20261006143924' }}</td>
                <td class="py-3 px-3 font-medium text-slate-700">2026-10-06</td>
                <td class="py-3 px-3 font-medium text-slate-700">{{ kuitansiForm?.metode || 'Tunai' }}</td>
                <td class="py-3 px-3 font-semibold text-slate-900">{{ formatRupiah(10950000) }}</td>
                <td class="py-3 px-3 text-center font-semibold text-slate-500">su</td>
                <td class="py-3 px-3 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <button
                      type="button"
                      class="p-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded shadow-2xs cursor-pointer"
                      title="Edit Pembayaran"
                      @click="currentView = 'edit_kuitansi'"
                    >
                      <Eye class="w-3.5 h-3.5" />
                    </button>
                    <button
                      type="button"
                      class="p-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded shadow-2xs cursor-pointer"
                      title="Hapus"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                    <button
                      type="button"
                      class="p-1.5 bg-alpharo-600 hover:bg-alpharo-700 text-white rounded shadow-2xs cursor-pointer"
                      title="Cetak Kuitansi Word"
                      :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('kuitansi', 'word')"
                    >
                      <Sparkles class="w-3.5 h-3.5" />
                    </button>
                    <button
                      type="button"
                      class="p-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded shadow-2xs cursor-pointer"
                      title="Cetak Kuitansi PDF"
                      :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('kuitansi', 'pdf')"
                    >
                      <Printer class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 5: LAMPIRAN BUKTI FOTO (MATCHING TEMPLATE LAMPIRAN-DOKUMENTASI)     -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'bukti_foto'" class="space-y-5">
      <!-- Top Bar: Title & Kembali Button -->
      <div class="flex items-center justify-between pb-3 border-b border-slate-200">
        <div>
          <div class="flex items-center gap-2">
            <Camera class="w-6 h-6 text-emerald-600" />
            <h2 class="text-xl font-semibold text-slate-900">Lampiran Dokumentasi & Bukti Foto</h2>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">
            Disesuaikan dengan format Lampiran-Dokumentasi-TEMPLATE.docx (CV ALPHARO ARTHA JAYA)
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition-colors cursor-pointer shadow-2xs"
          @click="goBackToList"
        >
          <ArrowLeft class="w-3.5 h-3.5" />
          <span>Kembali</span>
        </button>
      </div>

      <!-- CARD 1: Header Project & Lokasi -->
      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden border-t border-t-slate-200 p-5">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nama Pekerjaan</label>
            <input
              type="text"
              v-model="buktiFotoForm.project_name"
              class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg font-semibold text-slate-800"
            />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Customer / Sekolah</label>
            <input
              type="text"
              v-model="buktiFotoForm.customer_name"
              class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg font-semibold text-slate-800"
            />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Lokasi Pekerjaan</label>
            <input
              type="text"
              v-model="buktiFotoForm.lokasi"
              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-slate-900"
            />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Tahap Dokumentasi</label>
            <select
              v-model="buktiFotoForm.tahap"
              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-slate-900"
            >
              <option value="Serah Terima">Serah Terima</option>
              <option value="Pemeriksaan Fisik Barang">Pemeriksaan Fisik Barang</option>
              <option value="Uji Coba & Instalasi">Uji Coba & Instalasi</option>
            </select>
          </div>
        </div>
      </div>

      <!-- CARD 2: Slot Foto Sesuai Template Word (Grid 3 Foto) -->
      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden border-t border-t-slate-200 p-5">
        <div class="flex items-center gap-2 text-slate-800 font-semibold text-sm mb-4">
          <Camera class="w-4 h-4 text-emerald-600" />
          <span>Dokumentasi Foto Pekerjaan (Sesuai Template Dokumen)</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
          <div
            v-for="photo in buktiFotoForm.photos"
            :key="photo.id"
            class="border border-slate-200 rounded-xl p-4 bg-slate-50/60 flex flex-col justify-between"
          >
            <div>
              <div class="h-44 bg-slate-200 rounded-lg border-2 border-dashed border-slate-300 flex flex-col items-center justify-center p-3 text-center mb-3">
                <img v-if="photo.data_url" :src="photo.data_url" :alt="photo.title" class="h-full w-full object-contain" />
                <template v-else>
                  <Camera class="w-10 h-10 text-slate-400 mb-2" />
                  <p class="text-xs font-semibold text-slate-600">{{ photo.title }}</p>
                  <p class="text-[11px] text-slate-400 mt-1">Unggah JPG/PNG maksimal 3 MB</p>
                </template>
              </div>

              <div class="space-y-2">
                <input
                  type="text"
                  v-model="photo.title"
                  class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded text-xs font-semibold text-slate-900"
                  placeholder="Judul Foto"
                />
                <textarea
                  v-model="photo.desc"
                  rows="2"
                  class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded text-xs text-slate-700"
                  placeholder="Keterangan foto"
                ></textarea>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-between mt-3">
              <input
                type="file"
                accept="image/jpeg,image/png"
                :aria-label="'Unggah ' + photo.title"
                @change="setDocumentationPhoto($event, photo)"
                class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-[11px] file:font-semibold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200"
              />
              <button
                type="button"
                @click="photo.data_url = ''"
                class="text-rose-600 hover:text-rose-800 text-xs font-semibold"
              >
                Reset
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- BOTTOM ACTIONS BAR -->
      <div class="flex flex-wrap items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
        <button
          type="button"
          class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer transition-colors"
          :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('bukti_foto', 'word')"
        >
          <Sparkles class="w-4 h-4" />
          <span>Cetak Bukti Foto Word</span>
        </button>

        <button
          type="button"
          class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer transition-colors"
          :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('bukti_foto', 'pdf')"
        >
          <Printer class="w-4 h-4" />
          <span>Cetak Bukti Foto PDF</span>
        </button>

        <button
          type="button"
          class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg shadow-2xs cursor-pointer transition-colors"
          @click="goBackToList"
        >
          Batal
        </button>

        <button
          type="button"
          class="px-5 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer transition-colors"
          @click="saveBuktiFotoDocument"
        >
          <CheckCircle2 class="w-4 h-4" />
          <span>Simpan</span>
        </button>
      </div>
    </div>

        <!-- ========================================================================= -->
    <!-- VIEW: EDIT COVER PROJECT (DOKUMEN 1)                                      -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'edit_cover_project'" class="space-y-5">
      <div class="flex items-center justify-between pb-3 border-b border-slate-200">
        <div class="flex items-center gap-2">
          <FolderKanban class="w-6 h-6 text-emerald-600" />
          <h2 class="text-xl font-semibold text-slate-900">Tambah / Edit Cover Project SPJ</h2>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition-colors cursor-pointer shadow-2xs"
          @click="goBackToList"
        >
          <ArrowLeft class="w-3.5 h-3.5" />
          <span>Kembali</span>
        </button>
      </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden border-t border-t-slate-200 p-5 space-y-4">
        <div class="flex items-center gap-2 text-slate-800 font-semibold text-sm mb-2">
          <Info class="w-4 h-4 text-emerald-600" />
          <span>Informasi Sampul Laporan Pertanggungjawaban (SPJ)</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nomor Berkas / Dokumen</label>
            <input type="text" v-model="coverProjectForm.doc_number" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Tanggal Sampul *</label>
            <input type="text" v-model="coverProjectForm.doc_date" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Tahun Anggaran</label>
            <input type="text" v-model="coverProjectForm.fiscal_year" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nama Project / Pekerjaan</label>
            <input type="text" v-model="coverProjectForm.project_name" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nama Customer / Sekolah</label>
            <input type="text" v-model="coverProjectForm.customer_name" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
          <div>
            <label class="block font-medium text-slate-600 mb-1">Sumber Dana</label>
            <input type="text" v-model="coverProjectForm.funding_source" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Total Nilai Kontrak / SPJ</label>
            <input type="number" v-model.number="coverProjectForm.total_value" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-emerald-800" />
          </div>
        </div>

        <div class="text-xs">
          <label class="block font-medium text-slate-600 mb-1">Keterangan Judul Sampul</label>
          <textarea v-model="coverProjectForm.keterangan" rows="2" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg"></textarea>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
        <button type="button" class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('cover_project', 'word')">
          <Sparkles class="w-4 h-4" /><span>Cetak Cover Word</span>
        </button>
        <button type="button" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('cover_project', 'pdf')">
          <Printer class="w-4 h-4" /><span>Cetak Cover PDF</span>
        </button>
        <button type="button" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg cursor-pointer" @click="goBackToList">Batal</button>
        <button type="button" class="px-5 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer" @click="saveCoverProjectDocument">
          <CheckCircle2 class="w-4 h-4" /><span>Simpan</span>
        </button>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW: EDIT PERENCANAAN (DOKUMEN 2)                                       -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'edit_perencanaan'" class="space-y-5">
      <div class="flex items-center justify-between pb-3 border-b border-slate-200">
        <div class="flex items-center gap-2">
          <ClipboardCheck class="w-6 h-6 text-emerald-600" />
          <h2 class="text-xl font-semibold text-slate-900">Tambah / Edit Dokumen Perencanaan</h2>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition-colors cursor-pointer shadow-2xs"
          @click="goBackToList"
        >
          <ArrowLeft class="w-3.5 h-3.5" />
          <span>Kembali</span>
        </button>
      </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden border-t border-t-slate-200 p-5 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nomor Surat Perencanaan</label>
            <input type="text" v-model="perencanaanForm.doc_number" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Tanggal Perencanaan *</label>
            <input type="text" v-model="perencanaanForm.doc_date" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Sumber Dana</label>
            <input type="text" v-model="perencanaanForm.funding_source" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nama Kegiatan Pengadaan</label>
            <input type="text" v-model="perencanaanForm.activity_name" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Waktu Pelaksanaan Pekerjaan</label>
            <input type="text" v-model="perencanaanForm.waktu_pelaksanaan" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
        </div>

        <!-- Tabel Spesifikasi Barang (Editable Admin) -->
        <div class="pt-2 space-y-2">
          <div class="flex items-center justify-between">
            <p class="font-semibold text-slate-800 flex items-center gap-1.5">
              <span>Rincian Spesifikasi & Estimasi Biaya</span>
            </p>
            <button
              type="button"
              class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold leading-4 text-emerald-700 transition-colors hover:bg-emerald-100 cursor-pointer"
              @click="addPerencanaanItem"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Tambah Komponen</span>
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="ui-table-wide ui-table w-full text-xs text-left border border-slate-200 border-collapse">
              <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
                <tr>
                  <th class="p-2 w-10 text-center">No</th>
                  <th class="p-2">Komponen / Spesifikasi Barang</th>
                  <th class="p-2 w-24 text-center">Volume</th>
                  <th class="p-2 w-24 text-center">Satuan</th>
                  <th class="p-2 w-36 text-right">Harga Estimasi (Rp)</th>
                  <th class="p-2 w-36 text-right">Subtotal</th>
                  <th class="p-2 w-14 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(it, idx) in perencanaanForm.items" :key="idx" class="hover:bg-slate-50/50">
                  <td class="p-2 text-center text-slate-500 font-medium">{{ idx + 1 }}</td>
                  <td class="p-2">
                    <input
                      type="text"
                      v-model="it.item_name"
                      placeholder="Nama spesifikasi barang"
                      class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded font-medium text-slate-900 focus:ring-1 focus:ring-alpharo-400"
                    />
                  </td>
                  <td class="p-2 text-center">
                    <input
                      type="number"
                      v-model.number="it.quantity"
                      min="1"
                      class="w-20 px-2 py-1.5 text-center bg-white border border-slate-300 rounded font-semibold text-slate-900"
                    />
                  </td>
                  <td class="p-2 text-center">
                    <input
                      type="text"
                      v-model="it.unit"
                      placeholder="UNIT"
                      class="w-20 px-2 py-1.5 text-center bg-white border border-slate-300 rounded text-slate-800"
                    />
                  </td>
                  <td class="p-2 text-right">
                    <input
                      type="number"
                      v-model.number="it.price"
                      min="0"
                      class="w-32 px-2 py-1.5 text-right bg-white border border-slate-300 rounded font-semibold text-slate-900"
                    />
                  </td>
                  <td class="p-2 text-right font-semibold text-emerald-800">
                    {{ formatRupiah((it.quantity || 0) * (it.price || 0)) }}
                  </td>
                  <td class="p-2 text-center">
                    <button
                      type="button"
                      class="p-1.5 text-rose-600 hover:bg-rose-50 rounded cursor-pointer"
                      title="Hapus baris"
                      @click="removePerencanaanItem(idx)"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-slate-50 font-semibold border-t border-slate-200">
                <tr>
                  <td colspan="5" class="p-2.5 text-right">TOTAL ESTIMASI PERENCANAAN:</td>
                  <td class="p-2.5 text-right font-black text-emerald-900 text-sm">
                    {{ formatRupiah(calculateItemsTotal(perencanaanForm.items || []).subtotal) }}
                  </td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div></div>

      <div class="flex flex-wrap items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
        <button type="button" class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('perencanaan', 'word')">
          <Sparkles class="w-4 h-4" /><span>Cetak Perencanaan Word</span>
        </button>
        <button type="button" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('perencanaan', 'pdf')">
          <Printer class="w-4 h-4" /><span>Cetak Perencanaan PDF</span>
        </button>
        <button type="button" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg cursor-pointer" @click="goBackToList">Batal</button>
        <button type="button" class="px-5 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer" @click="savePerencanaanDocument">
          <CheckCircle2 class="w-4 h-4" /><span>Simpan</span>
        </button>
      </div>
    </div>
    <!-- ========================================================================= -->
    <!-- VIEW 6: EDIT HPS (SCREENSHOT 2)                                          -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'edit_hps'" class="space-y-5">
      <div class="flex items-center justify-between pb-3 border-b border-slate-200">
        <div class="flex items-center gap-2">
          <Calculator class="w-6 h-6 text-emerald-600" />
          <h2 class="text-xl font-semibold text-slate-900">Tambah / Edit HPS</h2>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition-colors cursor-pointer shadow-2xs"
          @click="goBackToList"
        >
          <ArrowLeft class="w-3.5 h-3.5" />
          <span>Kembali</span>
        </button>
      </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden border-t border-t-slate-200 p-5 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nomor HPS</label>
            <input type="text" v-model="hpsForm.doc_number" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Tanggal *</label>
            <input type="text" v-model="hpsForm.doc_date" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Kegiatan</label>
            <input type="text" v-model="hpsForm.activity_name" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Sumber Dana</label>
            <input type="text" v-model="hpsForm.funding_source" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
        </div>

        <!-- Tabel HPS (Editable Admin) -->
        <div class="pt-2 space-y-2">
          <div class="flex items-center justify-between">
            <p class="font-semibold text-slate-800 flex items-center gap-1.5">
              <span>Rincian Harga Perkiraan Sendiri (HPS)</span>
            </p>
            <button
              type="button"
              class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold leading-4 text-emerald-700 transition-colors hover:bg-emerald-100 cursor-pointer"
              @click="addHpsItem"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Tambah Komponen</span>
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="ui-table-wide ui-table w-full text-xs text-left border border-slate-200 border-collapse">
              <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
                <tr>
                  <th class="p-2 w-10 text-center">No</th>
                  <th class="p-2">Komponen / Uraian Barang</th>
                  <th class="p-2 w-24 text-center">Vol</th>
                  <th class="p-2 w-24 text-center">Sat</th>
                  <th class="p-2 w-36 text-right">Harga Satuan (Rp)</th>
                  <th class="p-2 w-36 text-right">Jumlah (Rp)</th>
                  <th class="p-2 w-14 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(it, idx) in hpsForm.items" :key="idx" class="hover:bg-slate-50/50">
                  <td class="p-2 text-center text-slate-500 font-medium">{{ idx + 1 }}</td>
                  <td class="p-2">
                    <input
                      type="text"
                      v-model="it.item_name"
                      placeholder="Nama uraian barang"
                      class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded font-medium text-slate-900 focus:ring-1 focus:ring-alpharo-400"
                    />
                  </td>
                  <td class="p-2 text-center">
                    <input
                      type="number"
                      v-model.number="it.quantity"
                      min="1"
                      class="w-20 px-2 py-1.5 text-center bg-white border border-slate-300 rounded font-semibold text-slate-900"
                    />
                  </td>
                  <td class="p-2 text-center">
                    <input
                      type="text"
                      v-model="it.unit"
                      placeholder="UNIT"
                      class="w-20 px-2 py-1.5 text-center bg-white border border-slate-300 rounded text-slate-800"
                    />
                  </td>
                  <td class="p-2 text-right">
                    <input
                      type="number"
                      v-model.number="it.price"
                      min="0"
                      class="w-32 px-2.5 py-1.5 text-right bg-white border border-slate-300 rounded font-semibold text-slate-900"
                    />
                  </td>
                  <td class="p-2 text-right font-semibold text-emerald-800">
                    {{ formatRupiah((it.quantity || 0) * (it.price || 0)) }}
                  </td>
                  <td class="p-2 text-center">
                    <button
                      type="button"
                      class="p-1.5 text-rose-600 hover:bg-rose-50 rounded cursor-pointer"
                      title="Hapus baris"
                      @click="removeHpsItem(idx)"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-slate-50 font-semibold border-t border-slate-200">
                <tr>
                  <td colspan="5" class="p-2.5 text-right">TOTAL NILAI HPS:</td>
                  <td class="p-2.5 text-right font-black text-emerald-900 text-sm">
                    {{ formatRupiah(calculateItemsTotal(hpsForm.items || []).subtotal) }}
                  </td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
        <button type="button" class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('hps', 'word')">
          <Sparkles class="w-4 h-4" /><span>Cetak HPS Word</span>
        </button>
        <button type="button" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('hps', 'pdf')">
          <Printer class="w-4 h-4" /><span>Cetak HPS PDF</span>
        </button>
        <button type="button" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg" @click="goBackToList">Batal</button>
        <button type="button" class="px-5 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg" @click="saveHpsDocument">Simpan</button>
      </div>
    </div>

        <!-- ========================================================================= -->
    <!-- VIEW: EDIT SPPH (DOKUMEN 4)                                              -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'edit_spph'" class="space-y-5">
      <div class="flex items-center justify-between pb-3 border-b border-slate-200">
        <div class="flex items-center gap-2">
          <Mail class="w-6 h-6 text-emerald-600" />
          <h2 class="text-xl font-semibold text-slate-900">Tambah / Edit Surat Permintaan Penawaran Harga (SPPH)</h2>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition-colors cursor-pointer shadow-2xs"
          @click="goBackToList"
        >
          <ArrowLeft class="w-3.5 h-3.5" />
          <span>Kembali</span>
        </button>
      </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden border-t border-t-slate-200 p-5 space-y-4 text-xs">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nomor Surat SPPH</label>
            <input type="text" v-model="spphForm.doc_number" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Tanggal Surat *</label>
            <input type="text" v-model="spphForm.doc_date" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Batas Akhir Penawaran</label>
            <input type="text" v-model="spphForm.batas_akhir_penawaran" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
        </div>

        <div>
          <label class="block font-medium text-slate-600 mb-1">Paket Pengadaan Pekerjaan</label>
          <input type="text" v-model="spphForm.pekerjaan" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
        </div>

        <div>
          <label class="block font-medium text-slate-600 mb-1">Narasi Surat Permintaan Penawaran</label>
          <textarea v-model="spphForm.content" rows="3" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-800 leading-relaxed"></textarea>
        </div>

        <!-- Tabel SPPH (Editable Admin) -->
        <div class="pt-2 space-y-2">
          <div class="flex items-center justify-between">
            <p class="font-semibold text-slate-800 flex items-center gap-1.5">
              <span>Daftar Barang yang Diminta Penawaran</span>
            </p>
            <button
              type="button"
              class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold rounded-lg border border-emerald-200 flex items-center gap-1 cursor-pointer transition-colors"
              @click="addSpphItem"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Tambah Barang</span>
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="ui-table w-full text-xs text-left border border-slate-200 border-collapse">
              <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
                <tr>
                  <th class="p-2 w-10 text-center">No</th>
                  <th class="p-2">Daftar Barang yang Diminta</th>
                  <th class="p-2 w-28 text-center">Volume</th>
                  <th class="p-2 w-28 text-center">Satuan</th>
                  <th class="p-2 w-14 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(it, idx) in spphForm.items" :key="idx" class="hover:bg-slate-50/50">
                  <td class="p-2 text-center text-slate-500 font-medium">{{ idx + 1 }}</td>
                  <td class="p-2">
                    <input
                      type="text"
                      v-model="it.item_name"
                      placeholder="Nama barang / spesifikasi"
                      class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded font-medium text-slate-900 focus:ring-1 focus:ring-alpharo-400"
                    />
                  </td>
                  <td class="p-2 text-center">
                    <input
                      type="number"
                      v-model.number="it.quantity"
                      min="1"
                      class="w-24 px-2 py-1.5 text-center bg-white border border-slate-300 rounded font-semibold text-slate-900"
                    />
                  </td>
                  <td class="p-2 text-center">
                    <input
                      type="text"
                      v-model="it.unit"
                      placeholder="UNIT"
                      class="w-24 px-2 py-1.5 text-center bg-white border border-slate-300 rounded text-slate-800"
                    />
                  </td>
                  <td class="p-2 text-center">
                    <button
                      type="button"
                      class="p-1.5 text-rose-600 hover:bg-rose-50 rounded cursor-pointer"
                      title="Hapus baris"
                      @click="removeSpphItem(idx)"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
        <button type="button" class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('spph', 'word')">
          <Sparkles class="w-4 h-4" /><span>Cetak SPPH Word</span>
        </button>
        <button type="button" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('spph', 'pdf')">
          <Printer class="w-4 h-4" /><span>Cetak SPPH PDF</span>
        </button>
        <button type="button" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg cursor-pointer" @click="goBackToList">Batal</button>
        <button type="button" class="px-5 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer" @click="saveSpphDocument">
          <CheckCircle2 class="w-4 h-4" /><span>Simpan</span>
        </button>
      </div>
    </div>
    <!-- ========================================================================= -->
    <!-- VIEW 7: EDIT PENAWARAN (SCREENSHOT 4)                                     -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'edit_penawaran'" class="space-y-5">
      <div class="flex items-center justify-between pb-3 border-b border-slate-200">
        <div class="flex items-center gap-2">
          <Send class="w-6 h-6 text-emerald-600" />
          <h2 class="text-xl font-semibold text-slate-900">Edit Penawaran</h2>
        </div>
        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold text-xs cursor-pointer" @click="goBackToList">
          <ArrowLeft class="w-3.5 h-3.5" /><span>Kembali</span>
        </button>
      </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs border-t border-t-slate-200 p-5 space-y-4 text-xs">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nomor Surat Penawaran</label>
            <input type="text" v-model="penawaranForm.doc_number" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Tanggal</label>
            <input type="text" v-model="penawaranForm.doc_date" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
        </div>
        <div>
          <label class="block font-medium text-slate-600 mb-1">Isi Narasi Surat Penawaran</label>
          <textarea v-model="penawaranForm.content" rows="4" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-800 leading-relaxed"></textarea>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
        <button type="button" class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('penawaran', 'word')">
          <Sparkles class="w-4 h-4" /><span>Cetak Penawaran Word</span>
        </button>
        <button type="button" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('penawaran', 'pdf')">
          <Printer class="w-4 h-4" /><span>Cetak Penawaran PDF</span>
        </button>
        <button type="button" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg" @click="goBackToList">Batal</button>
        <button type="button" class="px-5 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg" @click="savePenawaranDocument">Simpan</button>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 8: EDIT KLARIFIKASI/NEGO (SCREENSHOT 5)                              -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'edit_nego'" class="space-y-5">
      <div class="flex items-center justify-between pb-3 border-b border-slate-200">
        <div class="flex items-center gap-2">
          <MessageSquare class="w-6 h-6 text-emerald-600" />
          <h2 class="text-xl font-semibold text-slate-900">Edit Klarifikasi Negosiasi</h2>
        </div>
        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold text-xs cursor-pointer" @click="goBackToList">
          <ArrowLeft class="w-3.5 h-3.5" /><span>Kembali</span>
        </button>
      </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs border-t border-t-slate-200 p-5 space-y-4 text-xs">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nomor Undangan/Nego</label>
            <input type="text" v-model="negoForm.doc_number" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Tanggal</label>
            <input type="text" v-model="negoForm.doc_date" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Hasil Negosiasi</label>
            <input type="text" v-model="negoForm.nego_result" class="w-full px-3 py-2 bg-emerald-50 border border-emerald-300 rounded-lg font-semibold text-emerald-800" />
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="ui-table w-full text-xs text-left">
            <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
              <tr>
                <th class="py-2 px-3">Komponen</th>
                <th class="py-2 px-3 w-20 text-center">Vol</th>
                <th class="py-2 px-3 w-32 text-right">Harga Penawaran</th>
                <th class="py-2 px-3 w-32 text-right">Harga Negosiasi</th>
                <th class="py-2 px-3 w-36 text-right">Total Nego</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(it, idx) in (negoForm.items || [])" :key="idx">
                <td class="py-2.5 px-3 font-medium text-slate-800">{{ it.item_name }}</td>
                <td class="py-2.5 px-3 text-center font-semibold">{{ it.quantity }}</td>
                <td class="py-2.5 px-3 text-right line-through text-slate-400">{{ formatRupiah(it.original_price) }}</td>
                <td class="py-2.5 px-3 text-right font-semibold text-emerald-700">
                  <input type="number" v-model.number="it.nego_price" class="w-28 text-right px-2 py-1 bg-white border border-slate-300 rounded font-semibold text-slate-900" />
                </td>
                <td class="py-2.5 px-3 text-right font-semibold text-emerald-900">{{ formatRupiah(it.quantity * it.nego_price) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
        <button type="button" class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('klarifikasi_nego', 'word')">
          <Sparkles class="w-4 h-4" /><span>Cetak Nego Word</span>
        </button>
        <button type="button" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('klarifikasi_nego', 'pdf')">
          <Printer class="w-4 h-4" /><span>Cetak Nego PDF</span>
        </button>
        <button type="button" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg" @click="goBackToList">Batal</button>
        <button type="button" class="px-5 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg" @click="saveNegoDocument">Simpan</button>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 9: EDIT SURAT PESANAN (SCREENSHOT 6 / ITEM 7)                        -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'edit_permintaan_penawaran'" class="space-y-5">
      <div class="flex items-center justify-between pb-3 border-b border-slate-200">
        <div class="flex items-center gap-2">
          <Inbox class="w-6 h-6 text-emerald-600" />
          <h2 class="text-xl font-semibold text-slate-900">Edit Permintaan Penawaran / Surat Pesanan</h2>
        </div>
        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold text-xs cursor-pointer" @click="goBackToList">
          <ArrowLeft class="w-3.5 h-3.5" /><span>Kembali</span>
        </button>
      </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs border-t border-t-slate-200 p-5 space-y-4 text-xs">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nomor Surat Pesanan</label>
            <input type="text" v-model="suratPesananForm.doc_number" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Tanggal</label>
            <input type="text" v-model="suratPesananForm.doc_date" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
        </div>
        <div>
          <label class="block font-medium text-slate-600 mb-1">Keterangan / Instruksi</label>
          <textarea v-model="suratPesananForm.content" rows="3" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg"></textarea>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
        <button type="button" class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('permintaan_penawaran', 'word')">
          <Sparkles class="w-4 h-4" /><span>Cetak Pesanan Word</span>
        </button>
        <button type="button" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('permintaan_penawaran', 'pdf')">
          <Printer class="w-4 h-4" /><span>Cetak Pesanan PDF</span>
        </button>
        <button type="button" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg" @click="goBackToList">Batal</button>
        <button type="button" class="px-5 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg" @click="saveSuratPesananDocument">Simpan</button>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 10: EDIT SPK (SCREENSHOT 7 / ITEM 8)                                 -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'edit_spk'" class="space-y-5">
      <div class="flex items-center justify-between pb-3 border-b border-slate-200">
        <div class="flex items-center gap-2">
          <Briefcase class="w-6 h-6 text-emerald-600" />
          <h2 class="text-xl font-semibold text-slate-900">Edit Surat Perintah Kerja (SPK)</h2>
        </div>
        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold text-xs cursor-pointer" @click="goBackToList">
          <ArrowLeft class="w-3.5 h-3.5" /><span>Kembali</span>
        </button>
      </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs border-t border-t-slate-200 p-5 space-y-4 text-xs">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nomor SPK</label>
            <input type="text" v-model="spkForm.doc_number" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Tanggal SPK</label>
            <input type="text" v-model="spkForm.doc_date" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Paket Pekerjaan</label>
            <input type="text" v-model="spkForm.paket_pekerjaan" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg" />
          </div>
        </div>
        <div>
          <label class="block font-medium text-slate-600 mb-1">Instruksi Kepada Penyedia</label>
          <textarea v-model="spkForm.instruksi_penyedia" rows="3" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg"></textarea>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
        <button type="button" class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('spk', 'word')">
          <Sparkles class="w-4 h-4" /><span>Cetak SPK Word</span>
        </button>
        <button type="button" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('spk', 'pdf')">
          <Printer class="w-4 h-4" /><span>Cetak SPK PDF</span>
        </button>
        <button type="button" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg" @click="goBackToList">Batal</button>
        <button type="button" class="px-5 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg" @click="saveSpkDocument">Simpan</button>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 11: EDIT HASIL PEMERIKSAAN (SCREENSHOT 8 / ITEM 9)                   -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'edit_hasil_pemeriksaan'" class="space-y-5">
      <div class="flex items-center justify-between pb-3 border-b border-slate-200">
        <div class="flex items-center gap-2">
          <SearchCheck class="w-6 h-6 text-emerald-600" />
          <h2 class="text-xl font-semibold text-slate-900">Edit Hasil Pemeriksaan</h2>
        </div>
        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold text-xs cursor-pointer" @click="goBackToList">
          <ArrowLeft class="w-3.5 h-3.5" /><span>Kembali</span>
        </button>
      </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs border-t border-t-slate-200 p-5 space-y-4 text-xs">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nomor Berita Acara</label>
            <input type="text" v-model="hasilPemeriksaanForm.doc_number" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Tanggal</label>
            <input type="text" v-model="hasilPemeriksaanForm.doc_date" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Kondisi Hasil</label>
            <input type="text" v-model="hasilPemeriksaanForm.hasil_pemeriksaan" class="w-full px-3 py-2 bg-emerald-50 border border-emerald-300 rounded-lg font-semibold text-emerald-800" />
          </div>
        </div>
        <div>
          <label class="block font-medium text-slate-600 mb-1">Catatan Pemeriksaan</label>
          <textarea v-model="hasilPemeriksaanForm.catatan" rows="3" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg"></textarea>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
        <button type="button" class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('hasil_pemeriksaan', 'word')">
          <Sparkles class="w-4 h-4" /><span>Cetak HP Word</span>
        </button>
        <button type="button" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('hasil_pemeriksaan', 'pdf')">
          <Printer class="w-4 h-4" /><span>Cetak HP PDF</span>
        </button>
        <button type="button" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg" @click="goBackToList">Batal</button>
        <button type="button" class="px-5 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg" @click="saveHasilPemeriksaanDocument">Simpan</button>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 12: EDIT BAST (SCREENSHOT 9 / ITEMS 10 & 11)                         -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'edit_bast'" class="space-y-5">
      <div class="flex items-center justify-between pb-3 border-b border-slate-200">
        <div class="flex items-center gap-2">
          <GraduationCap class="w-6 h-6 text-emerald-600" />
          <h2 class="text-xl font-semibold text-slate-900">Edit Berita Acara Serah Terima (BAST)</h2>
        </div>
        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold text-xs cursor-pointer" @click="goBackToList">
          <ArrowLeft class="w-3.5 h-3.5" /><span>Kembali</span>
        </button>
      </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs border-t border-t-slate-200 p-5 space-y-4 text-xs">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nomor BAST</label>
            <input type="text" v-model="bastForm.doc_number" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Tanggal</label>
            <input type="text" v-model="bastForm.doc_date" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
        </div>
        <div>
          <label class="block font-medium text-slate-600 mb-1">Pernyataan Serah Terima</label>
          <textarea v-model="bastForm.content" rows="4" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg leading-relaxed"></textarea>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
        <button type="button" class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate(activeDocItem?.doc_key || 'bast_sekolah', 'word')">
          <Sparkles class="w-4 h-4" /><span>Cetak BAST Word</span>
        </button>
        <button type="button" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate(activeDocItem?.doc_key || 'bast_sekolah', 'pdf')">
          <Printer class="w-4 h-4" /><span>Cetak BAST PDF</span>
        </button>
        <button type="button" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg" @click="goBackToList">Batal</button>
        <button type="button" class="px-5 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg" @click="saveBastDocument">Simpan</button>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 13: EDIT SURAT JALAN (SCREENSHOT 10 / ITEM 12)                       -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'edit_surat_jalan'" class="space-y-5">
      <div class="flex items-center justify-between pb-3 border-b border-slate-200">
        <div class="flex items-center gap-2">
          <Truck class="w-6 h-6 text-emerald-600" />
          <h2 class="text-xl font-semibold text-slate-900">Edit Surat Jalan</h2>
        </div>
        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold text-xs cursor-pointer" @click="goBackToList">
          <ArrowLeft class="w-3.5 h-3.5" /><span>Kembali</span>
        </button>
      </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs border-t border-t-slate-200 p-5 space-y-4 text-xs">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nomor Surat Jalan</label>
            <input type="text" v-model="suratJalanForm.doc_number" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Tanggal</label>
            <input type="text" v-model="suratJalanForm.doc_date" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Jenis Kendaraan</label>
            <input type="text" v-model="suratJalanForm.kendaraan" placeholder="Mobil Box Engkel" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nomor Plat Kendaraan</label>
            <input type="text" v-model="suratJalanForm.no_kendaraan" placeholder="B 9812 AAJ" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
          <div>
            <label class="block font-medium text-slate-600 mb-1">Nama Supir / Pengantar</label>
            <input type="text" v-model="suratJalanForm.nama_supir" placeholder="Agus Santoso" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold" />
          </div>
        </div>

        <!-- Tabel Surat Jalan (Editable Admin) -->
        <div class="pt-2 space-y-2">
          <div class="flex items-center justify-between">
            <p class="font-semibold text-slate-800 flex items-center gap-1.5">
              <span>Daftar Barang yang Dikirim</span>
            </p>
            <button
              type="button"
              class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold rounded-lg border border-emerald-200 flex items-center gap-1 cursor-pointer transition-colors"
              @click="addSuratJalanItem"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Tambah Barang</span>
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="ui-table w-full text-xs text-left border border-slate-200 border-collapse">
              <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
                <tr>
                  <th class="p-2 w-10 text-center">No</th>
                  <th class="p-2">Kode & Nama Barang</th>
                  <th class="p-2 w-28 text-center">Stok</th>
                  <th class="p-2 w-28 text-center">Qty Kirim</th>
                  <th class="p-2 w-14 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(it, idx) in suratJalanForm.items" :key="idx" class="hover:bg-slate-50/50">
                  <td class="p-2 text-center text-slate-500 font-medium">{{ idx + 1 }}</td>
                  <td class="p-2">
                    <input
                      type="text"
                      v-model="it.code"
                      placeholder="Nama / kode barang"
                      class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded font-medium text-slate-900 focus:ring-1 focus:ring-alpharo-400"
                    />
                  </td>
                  <td class="p-2 text-center">
                    <input
                      type="number"
                      v-model.number="it.stock"
                      class="w-24 px-2 py-1.5 text-center bg-white border border-slate-300 rounded text-slate-700"
                    />
                  </td>
                  <td class="p-2 text-center">
                    <input
                      type="number"
                      v-model.number="it.qty_sent"
                      min="1"
                      class="w-24 px-2 py-1.5 text-center bg-white border border-slate-300 rounded font-semibold text-emerald-800"
                    />
                  </td>
                  <td class="p-2 text-center">
                    <button
                      type="button"
                      class="p-1.5 text-rose-600 hover:bg-rose-50 rounded cursor-pointer"
                      title="Hapus baris"
                      @click="removeSuratJalanItem(idx)"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
        <button type="button" class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('surat_jalan', 'word')">
          <Sparkles class="w-4 h-4" /><span>Cetak SJ Word</span>
        </button>
        <button type="button" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5" :disabled="Boolean(isGeneratingKey)" @click="handleSaveAndGenerate('surat_jalan', 'pdf')">
          <Printer class="w-4 h-4" /><span>Cetak SJ PDF</span>
        </button>
        <button type="button" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg" @click="goBackToList">Batal</button>
        <button type="button" class="px-5 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg" @click="saveSuratJalanDocument">Simpan</button>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 14: DATA PROJECT (SCREENSHOT 1)                                      -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'data_project'" class="space-y-5">
      <div class="flex items-center justify-between pb-3 border-b border-slate-200">
        <div class="flex items-center gap-2">
          <FolderKanban class="w-6 h-6 text-emerald-600" />
          <div>
            <h2 class="text-xl font-semibold text-slate-900">Data Project (Editable Lintas Modul)</h2>
            <p class="text-xs text-slate-500">Administrasi memiliki hak penuh mengedit data proyek, anggaran, penanggung jawab, logistik, dan daftar barang.</p>
          </div>
        </div>
        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs cursor-pointer shadow-2xs" @click="goBackToList">
          <ArrowLeft class="w-3.5 h-3.5" /><span>Kembali</span>
        </button>
      </div>

      <!-- 4 KUADRAN FORM + LOGISTIK/KEUANGAN -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 text-xs">
        <!-- Kuadran 1: Data Identitas Project -->
        <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs border-t border-t-slate-200 p-4 space-y-3">
          <p class="font-semibold text-sm text-slate-800 flex items-center gap-1.5">
            <Info class="w-4 h-4 text-emerald-600" />
            <span>1. Identitas Project</span>
          </p>
          <div>
            <label class="block text-slate-600 mb-1">ID Project</label>
            <input type="text" readonly :value="projectForm?.project_id" class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded font-semibold text-slate-600" />
          </div>
          <div>
            <label class="block text-slate-600 mb-1">Nama Project *</label>
            <input type="text" v-model="projectForm.project_name" class="w-full px-3 py-2 bg-white border border-slate-300 rounded font-semibold text-slate-900 focus:ring-2 focus:ring-alpharo-400" />
          </div>
          <div>
            <label for="spj-project-customer" class="block text-slate-600 mb-1">Customer / Klien Sekolah *</label>
            <input id="spj-project-customer" type="text" v-model="projectForm.customer_name" @change="changeProjectCustomer" class="w-full px-3 py-2 bg-white border border-slate-300 rounded font-semibold text-slate-900 focus:ring-2 focus:ring-alpharo-400" />
          </div>
          <div>
            <label class="block text-slate-600 mb-1">Petugas Marketing</label>
            <input type="text" v-model="projectForm.marketing_name" class="w-full px-3 py-2 bg-white border border-slate-300 rounded text-slate-800" />
          </div>
        </div>

        <!-- Kuadran 2: Pengadaan & Anggaran -->
        <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs border-t border-t-slate-200 p-4 space-y-3">
          <p class="font-semibold text-sm text-slate-800 flex items-center gap-1.5">
            <Calculator class="w-4 h-4 text-emerald-600" />
            <span>2. Pengadaan & Anggaran</span>
          </p>
          <div>
            <label class="block text-slate-600 mb-1">Nama Kegiatan Pengadaan</label>
            <input type="text" v-model="projectForm.activity_name" class="w-full px-3 py-2 bg-white border border-slate-300 rounded text-slate-900" />
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-slate-600 mb-1">Sumber Dana</label>
              <input type="text" v-model="projectForm.funding_source" class="w-full px-3 py-2 bg-white border border-slate-300 rounded font-semibold" />
            </div>
            <div>
              <label class="block text-slate-600 mb-1">Tahun Anggaran</label>
              <input type="text" v-model="projectForm.fiscal_year" class="w-full px-3 py-2 bg-white border border-slate-300 rounded font-semibold" />
            </div>
          </div>
          <div>
            <label class="block text-slate-600 mb-1">Pagu Anggaran (Rp)</label>
            <input type="number" v-model.number="projectForm.budget_ceiling" class="w-full px-3 py-2 bg-white border border-slate-300 rounded font-semibold text-emerald-800" />
          </div>
          <div>
            <label class="block text-slate-600 mb-1">Kode Rekening Anggaran</label>
            <input type="text" v-model="projectForm.account_code" placeholder="5.1.02.01.01.0024" class="w-full px-3 py-2 bg-white border border-slate-300 rounded text-slate-700" />
          </div>
        </div>

        <!-- Kuadran 3: Nilai Transaksi & Jadwal -->
        <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs border-t border-t-slate-200 p-4 space-y-3">
          <p class="font-semibold text-sm text-slate-800 flex items-center gap-1.5">
            <Calendar class="w-4 h-4 text-emerald-600" />
            <span>3. Nilai & Jadwal</span>
          </p>
          <div>
            <label class="block text-slate-600 mb-1">Nilai Project Kontrak (Rp)</label>
            <input type="number" v-model.number="projectForm.project_value" class="w-full px-3 py-2 bg-white border border-slate-300 rounded font-semibold text-slate-900" />
          </div>
          <div>
            <label class="block text-slate-600 mb-1">Nilai Invoice / Tagihan (Rp)</label>
            <input type="number" v-model.number="projectForm.invoice_value" class="w-full px-3 py-2 bg-white border border-slate-300 rounded font-semibold text-slate-900" />
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-slate-600 mb-1">Tgl Mulai</label>
              <input type="date" v-model="projectForm.start_date" class="w-full px-3 py-2 bg-white border border-slate-300 rounded text-xs" />
            </div>
            <div>
              <label class="block text-slate-600 mb-1">Tgl Selesai</label>
              <input type="date" v-model="projectForm.end_date" class="w-full px-3 py-2 bg-white border border-slate-300 rounded text-xs" />
            </div>
          </div>
        </div>

        <!-- Kuadran 4: Penanggung Jawab Customer (Sekolah / PPK) -->
        <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs border-t border-t-slate-200 p-4 space-y-3">
          <p class="font-semibold text-sm text-slate-800 flex items-center gap-1.5">
            <GraduationCap class="w-4 h-4 text-emerald-600" />
            <span>4. Penanggung Jawab Klien</span>
          </p>
          <div>
            <label class="block text-slate-600 mb-1">Nama Kepala Sekolah / PPK *</label>
            <input type="text" v-model="projectForm.pic_name" class="w-full px-3 py-2 bg-white border border-slate-300 rounded font-semibold text-slate-900" />
          </div>
          <div>
            <label class="block text-slate-600 mb-1">NIP Penanggung Jawab</label>
            <input type="text" v-model="projectForm.pic_nip" class="w-full px-3 py-2 bg-white border border-slate-300 rounded text-slate-800" />
          </div>
          <div>
            <label class="block text-slate-600 mb-1">Jabatan Resmi</label>
            <input type="text" v-model="projectForm.pic_title" placeholder="Kepala Sekolah" class="w-full px-3 py-2 bg-white border border-slate-300 rounded text-slate-800" />
          </div>
          <div>
            <label class="block text-slate-600 mb-1">Kota Tempat Tanda Tangan</label>
            <input type="text" v-model="projectForm.city_signature" placeholder="Bogor" class="w-full px-3 py-2 bg-white border border-slate-300 rounded text-slate-800" />
          </div>
        </div>

        <!-- Kuadran 5: Status Logistik (Warehouse) & Keuangan (Finance) -->
        <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs border-t border-t-slate-200 p-4 space-y-3 lg:col-span-2">
          <p class="font-semibold text-sm text-slate-800 flex items-center gap-1.5">
            <Truck class="w-4 h-4 text-emerald-600" />
            <span>5. Integrasi Gudang & Keuangan (Override oleh Admin)</span>
          </p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-600 mb-1">Nama Supir / Pengantar</label>
              <input type="text" v-model="projectForm.warehouse_data.driver_name" placeholder="Agus Santoso" class="w-full px-3 py-2 bg-white border border-slate-300 rounded font-medium" />
            </div>
            <div>
              <label class="block text-slate-600 mb-1">Nomor Plat Kendaraan</label>
              <input type="text" v-model="projectForm.warehouse_data.vehicle_plate" placeholder="B 9812 AAJ" class="w-full px-3 py-2 bg-white border border-slate-300 rounded font-medium" />
            </div>
            <div>
              <label class="block text-slate-600 mb-1">Status Pengiriman Gudang</label>
              <select v-model="projectForm.warehouse_status" class="w-full px-3 py-2 bg-white border border-slate-300 rounded font-semibold text-emerald-800">
                <option value="TERKIRIM">TERKIRIM (Lengkap Diterima)</option>
                <option value="DALAM_PERJALANAN">DALAM PERJALANAN</option>
                <option value="PROSES">DALAM PROSES PACKING</option>
                <option value="PENDING">PENDING DI GUDANG</option>
              </select>
            </div>
            <div>
              <label class="block text-slate-600 mb-1">Status Pembayaran Keuangan</label>
              <select v-model="projectForm.finance_status" class="w-full px-3 py-2 bg-white border border-slate-300 rounded font-semibold text-alpharo-800">
                <option value="LUNAS">LUNAS</option>
                <option value="MENUNGGU_PEMBAYARAN">MENUNGGU PEMBAYARAN</option>
                <option value="BELUM_LUNAS">BELUM LUNAS</option>
                <option value="SEBAGIAN">DIBAYAR SEBAGIAN</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <section aria-label="Kop Surat Instansi" class="ui-panel space-y-4 rounded-xl border border-slate-200 bg-white p-4 text-xs sm:p-5">
        <div>
          <h3 class="text-sm font-semibold text-slate-800">Kop Surat Instansi</h3>
          <p class="mt-1 text-xs leading-relaxed text-slate-500">Mengikuti sekolah asal pesanan. Lengkapi sekali untuk dipakai pada seluruh dokumen yang diterbitkan sekolah dalam proyek ini.</p>
        </div>
        <div class="grid min-w-0 grid-cols-1 gap-4 md:grid-cols-2">
          <div v-for="field in letterheadFields" :key="field.key" :class="field.key === 'letterheadAddress' ? 'md:col-span-2' : ''">
            <label :for="'spj-kop-' + field.key" class="mb-1 block font-medium text-slate-600">{{ field.label }}</label>
            <textarea v-if="field.key === 'letterheadAddress'" :id="'spj-kop-' + field.key" v-model="projectForm.metadata.customer_profile[field.key]" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-900"></textarea>
            <input v-else :id="'spj-kop-' + field.key" v-model="projectForm.metadata.customer_profile[field.key]" type="text" :placeholder="field.placeholder || ''" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-900" />
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4">
          <img v-if="projectForm.metadata.customer_profile.logoDataUrl" :src="projectForm.metadata.customer_profile.logoDataUrl" alt="Logo instansi untuk kop surat" class="h-16 w-16 object-contain" />
          <div class="min-w-0">
            <label for="spj-kop-logo" class="mb-1 block font-medium text-slate-600">Logo Instansi / Daerah</label>
            <input id="spj-kop-logo" type="file" accept="image/png,image/jpeg" @change="setLetterheadLogo" class="w-full max-w-sm text-xs text-slate-600" />
            <p class="mt-1 text-[11px] text-slate-500">JPG/PNG, maksimal 1 MB. Gunakan logo resmi yang sesuai dengan instansi.</p>
          </div>
          <button v-if="projectForm.metadata.customer_profile.logoDataUrl" type="button" class="text-xs font-semibold text-rose-600" @click="projectForm.metadata.customer_profile.logoDataUrl = ''">Hapus Logo</button>
        </div>
      </section>

      <!-- KUADRAN 6: TABEL EDITOR DAFTAR BARANG / PEKERJAAN -->
      <div class="ui-panel bg-white rounded-xl border border-slate-200 shadow-2xs border-t border-t-slate-200 p-4 sm:p-5 space-y-3 text-xs">
        <div class="flex items-center justify-between">
          <div>
            <p class="font-semibold text-sm text-slate-800 flex items-center gap-1.5">
              <ClipboardCheck class="w-4 h-4 text-emerald-600" />
              <span>Daftar Rincian Barang / Komponen Transaksi</span>
            </p>
            <p class="text-[11px] text-slate-500">Admin dapat menambah, mengubah kuantitas, harga, atau menghapus komponen yang otomatis disinkronkan ke seluruh dokumen SPJ.</p>
          </div>
          <button
            type="button"
            class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold rounded-lg border border-emerald-200 flex items-center gap-1 cursor-pointer transition-colors"
            @click="addProjectItem"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Tambah Barang</span>
          </button>
        </div>

        <div class="overflow-x-auto pt-2">
          <table class="ui-table-wide ui-table w-full text-left border border-slate-200 border-collapse">
            <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
              <tr>
                <th class="p-2 w-10 text-center">No</th>
                <th class="p-2">Nama Komponen / Rincian Barang</th>
                <th class="p-2 w-24 text-center">Volume</th>
                <th class="p-2 w-24 text-center">Satuan</th>
                <th class="p-2 w-36 text-right">Harga Satuan (Rp)</th>
                <th class="p-2 w-40 text-right">Subtotal (Rp)</th>
                <th class="p-2 w-16 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(it, idx) in projectForm.items" :key="idx" class="hover:bg-slate-50/50">
                <td class="p-2 text-center text-slate-500 font-semibold">{{ idx + 1 }}</td>
                <td class="p-2">
                  <input
                    type="text"
                    v-model="it.item_name"
                    placeholder="Nama barang / spesifikasi"
                    class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded font-medium text-slate-900 focus:ring-1 focus:ring-alpharo-400"
                  />
                </td>
                <td class="p-2 text-center">
                  <input
                    type="number"
                    v-model.number="it.quantity"
                    min="1"
                    class="w-20 px-2 py-1.5 text-center bg-white border border-slate-300 rounded font-semibold text-slate-900"
                  />
                </td>
                <td class="p-2 text-center">
                  <input
                    type="text"
                    v-model="it.unit"
                    placeholder="UNIT"
                    class="w-20 px-2 py-1.5 text-center bg-white border border-slate-300 rounded text-slate-800"
                  />
                </td>
                <td class="p-2 text-right">
                  <input
                    type="number"
                    v-model.number="it.price"
                    min="0"
                    class="w-32 px-2.5 py-1.5 text-right bg-white border border-slate-300 rounded font-semibold text-slate-900"
                  />
                </td>
                <td class="p-2 text-right font-semibold text-emerald-800">
                  {{ formatRupiah((it.quantity || 0) * (it.price || 0)) }}
                </td>
                <td class="p-2 text-center">
                  <button
                    type="button"
                    class="p-1.5 text-rose-600 hover:bg-rose-50 rounded cursor-pointer transition-colors"
                    title="Hapus baris barang"
                    @click="removeProjectItem(idx)"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-slate-50 font-semibold border-t border-slate-200">
              <tr>
                <td colspan="5" class="p-2.5 text-right">TOTAL NILAI KOMPONEN:</td>
                <td class="p-2.5 text-right font-black text-emerald-900 text-sm">
                  {{ formatRupiah(calculateItemsTotal(projectForm.items || []).subtotal) }}
                </td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- ACTION BUTTONS -->
      <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
        <button type="button" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg shadow-2xs hover:bg-slate-50 cursor-pointer transition-colors" @click="goBackToList">Batal</button>
        <button type="button" class="px-5 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white text-xs font-semibold rounded-lg shadow-2xs flex items-center gap-1.5 cursor-pointer transition-colors" @click="saveProjectData">
          <CheckCircle2 class="w-4 h-4" />
          <span>Simpan Data Project</span>
        </button>
      </div>
    </div>
<!-- ========================================================================= -->
    <!-- Canonical server PDF preview: same saved data as the Word document. -->
    <Modal v-model="showPdfModal" :title="`Pratinjau PDF: ${pdfDocItem?.doc_name || 'Dokumen SPJ'}`" size="xl">
      <p v-if="isPdfLoading" role="status" class="py-12 text-center text-sm text-slate-500">Menyiapkan PDF dari data dokumen...</p>
      <iframe v-else-if="pdfPreviewUrl" ref="pdfPreviewFrame" :src="pdfPreviewUrl" :title="`PDF ${pdfDocItem?.doc_name || 'Dokumen SPJ'}`" class="h-[65vh] w-full rounded-lg border border-slate-200"></iframe>
      <template #footer>
        <button type="button" class="rounded-lg px-4 py-2 text-xs text-slate-600 hover:bg-slate-100" @click="showPdfModal = false">Tutup</button>
        <button type="button" :disabled="isPdfLoading || !pdfPreviewUrl" class="rounded-lg bg-alpharo-600 px-4 py-2 text-xs font-semibold text-white disabled:opacity-50" @click="downloadWithFeedback(pdfDocItem.doc_key, 'pdf')">Unduh PDF</button>
        <button type="button" :disabled="isPdfLoading || !pdfPreviewUrl" class="rounded-lg border border-slate-300 px-4 py-2 text-xs text-slate-700 disabled:opacity-50" @click="printDocument">Cetak</button>
      </template>
    </Modal>

    <!-- UPLOAD MODAL UNTUK BERKAS EKSTERNAL -->
    <Modal
      v-model="showUploadModal"
      :title="`Unggah Berkas Eksternal: ${uploadDocItem?.doc_name || ''}`"
      size="md"
    >
      <div class="space-y-4 text-xs">
        <div>
          <label class="block font-medium text-slate-700 mb-1">Nomor Dokumen (Opsional)</label>
          <input type="text" v-model="uploadMeta.doc_number" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg" />
        </div>
        <div>
          <label class="block font-medium text-slate-700 mb-1">Pilih File (PDF, Word, JPG, PNG)</label>
          <input type="file" @change="handleFileUploadChange" class="w-full text-xs text-slate-500" />
        </div>
      </div>
      <template #footer>
        <button type="button" class="px-3 py-1.5 text-xs text-slate-600" @click="showUploadModal = false">Batal</button>
        <button type="button" :disabled="!selectedUploadFile || isUploading" class="px-4 py-1.5 text-xs bg-alpharo-600 text-white font-bold rounded-lg" @click="submitUploadExternal">
          {{ isUploading ? 'Mengunggah...' : 'Unggah' }}
        </button>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { onBeforeUnmount, ref, computed, onMounted, shallowRef, watch } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import Modal from '../../components/common/Modal.vue';
import { useToast } from '../../services/toast';
import spjService from '../../services/spjService';
import { store, storeActions } from '../../stores/mockData';
import { customerProfile, findProjectCustomer, expandSchoolName } from '../../services/customerProfile';

const letterheadFields = [
  { key: 'orgFullName', label: 'Nama Resmi Sekolah / Instansi', placeholder: 'Contoh: SEKOLAH MENENGAH ATAS NEGERI 2 BOGOR' },
  { key: 'province', label: 'Provinsi' },
  { key: 'governmentName', label: 'Nama Pemerintah / Yayasan', placeholder: 'Otomatis dari provinsi jika belum diisi' },
  { key: 'educationOffice', label: 'Dinas / Unit Organisasi', placeholder: 'DINAS PENDIDIKAN' },
  { key: 'letterheadAddress', label: 'Alamat Kop Surat' },
  { key: 'orgPhone', label: 'Telepon Instansi' },
  { key: 'fax', label: 'Faksimile' },
  { key: 'website', label: 'Laman / Website' },
  { key: 'email', label: 'Surel / Email' },
  { key: 'postalCode', label: 'Kode Pos' },
];

function projectWithLetterhead(project) {
  const profile = customerProfile(project.metadata?.customer_profile || project.customer_profile || {});
  profile.name ||= project.customer_name || '';
  profile.orgFullName = expandSchoolName(profile.orgFullName || project.customer_name || '');
  return { ...project, metadata: { ...project.metadata, customer_profile: profile } };
}

function changeProjectCustomer() {
  const client = findProjectCustomer(projectForm.value, store.clients);
  projectForm.value.customer_code = client?.code || '';
  const prof = customerProfile(client || { name: projectForm.value.customer_name, orgFullName: projectForm.value.customer_name });
  prof.orgFullName = expandSchoolName(prof.orgFullName || projectForm.value.customer_name || '');
  projectForm.value.metadata.customer_profile = prof;
}

// Lucide Icons
import {
  FolderKanban,
  ClipboardCheck,
  Calculator,
  Mail,
  Send,
  MessageSquare,
  Inbox,
  Briefcase,
  SearchCheck,
  GraduationCap,
  Building,
  Truck,
  Receipt,
  CreditCard,
  Camera,
  RotateCcw,
  Sparkles,
  Download,
  Upload,
  Printer,
  CheckCircle2,
  AlertTriangle,
  Info,
  Calendar,
  Eye,
  Search,
  Trash2,
  Plus,
  Clock,
  ArrowLeft,
} from 'lucide-vue-next';

const toast = useToast();

// Subview State: 'list' | 'edit_faktur' | 'edit_kuitansi' | 'riwayat_kuitansi' | 'bukti_foto' | ...
const currentView = ref('list');

// Project Data
const projectsList = ref([]);
const selectedProjectId = ref('PR00001');
const activeProject = ref(null);
const documentItems = ref([]);
const selectedDocKeys = ref([]);

// Loaders
const isBulkGenerating = ref(false);
const isGeneratingKey = ref(null);
const isUploading = ref(false);

// Modals
const showPdfModal = ref(false);
const showUploadModal = ref(false);
const activeDocItem = ref(null);
const uploadDocItem = ref(null);
const pdfDocItem = ref(null);
const isPdfLoading = ref(false);
const pdfPreviewUrl = ref('');
const pdfPreviewFrame = ref(null);
watch(showPdfModal, open => { if (!open) releasePdfPreview(); });
onBeforeUnmount(releasePdfPreview);
const selectedUploadFile = ref(null);
const uploadMeta = ref({ doc_number: '', doc_date: '', notes: '' });

// Forms
const projectForm = ref({});
const coverProjectForm = ref({
  doc_number: '',
  doc_date: '',
  project_name: '',
  customer_name: '',
  funding_source: '',
  fiscal_year: '',
  total_value: 0,
  keterangan: 'Surat Pertanggungjawaban (SPJ) Pengadaan Barang/Jasa',
});

const perencanaanForm = ref({
  doc_number: '',
  doc_date: '',
  activity_name: '',
  funding_source: '',
  fiscal_year: '',
  lokasi_pekerjaan: '',
  waktu_pelaksanaan: '30 (Tiga Puluh) Hari Kalender',
  items: [],
});

const spphForm = ref({
  doc_number: '',
  doc_date: '',
  pekerjaan: '',
  batas_akhir_penawaran: '',
  content: '',
  items: [],
});
const fakturForm = ref({
  doc_number: 'IV20261006143815',
  doc_date: '06/10/2026',
  jenis_harga: 'Harga Nett',
  project_id: 'PR00001',
  items: [
    { code: 'A00001770 - Pemeliharaan Komputer Note Book/ Laptop', quantity: 100, price: 100000, subtotal: 10000000 },
    { code: '', quantity: null, price: null, subtotal: 0 }
  ],
  subtotal: 10000000,
  ppn_rate: 11,
  ppn: 1100000,
  total_harga: 11100000,
  pph_rate: 1.5,
  pph: 150000,
  nilai_setelah_pph: 10950000,
});

const kuitansiForm = ref({
  doc_number: 'PI20261006143924',
  doc_date: '06/10/2026',
  metode: 'Tunai',
  jumlah: '10.950.000',
  keterangan: '-',
});

const buktiFotoForm = ref({
  doc_number: 'DOK-001/AAJ/X/2026',
  doc_date: '01/10/2026',
  project_name: 'pengadaan komputer sekolah',
  customer_name: 'SEKOLAH MENENGAH ATAS NEGERI 2 BOGOR',
  lokasi: 'Lab Komputer SMAN 2 BOGOR',
  tahap: 'Serah Terima',
  photos: [
    { id: 1, title: 'Foto 1: Pemeriksaan Fisik Barang', desc: 'Pemeriksaan 100 unit komputer note book / laptop di Lab Komputer SMAN 2 BOGOR' },
    { id: 2, title: 'Foto 2: Uji Coba Fungsi & Kelengkapan', desc: 'Pengujian booting sistem operasi dan kelengkapan aksesoris unit' },
    { id: 3, title: 'Foto 3: Serah Terima kepada Sekolah', desc: 'Penyerahan fisik dan penandatanganan dokumen BAST bersama Kepala Sekolah' },
  ],
});

const hpsForm = ref({ doc_number: '', doc_date: '', activity_name: '', funding_source: '', items: [] });
const penawaranForm = ref({ doc_number: '', doc_date: '', content: '' });
const negoForm = ref({ doc_number: '', doc_date: '', nego_result: 'Sepakat', items: [] });
const suratPesananForm = ref({ doc_number: '', doc_date: '', content: '' });
const spkForm = ref({ doc_number: '', doc_date: '', paket_pekerjaan: '', instruksi_penyedia: '' });
const hasilPemeriksaanForm = ref({ doc_number: '', doc_date: '', hasil_pemeriksaan: 'Baik dan lengkap', catatan: '' });
const bastForm = ref({ doc_number: '', doc_date: '', content: '' });
const suratJalanForm = ref({ doc_number: '', doc_date: '', kendaraan: '', no_kendaraan: '', items: [] });

// Icon Map
const iconMap = {
  cover_project: shallowRef(FolderKanban),
  perencanaan: shallowRef(ClipboardCheck),
  hps: shallowRef(Calculator),
  spph: shallowRef(Mail),
  penawaran: shallowRef(Send),
  klarifikasi_nego: shallowRef(MessageSquare),
  permintaan_penawaran: shallowRef(Inbox),
  spk: shallowRef(Briefcase),
  hasil_pemeriksaan: shallowRef(SearchCheck),
  bast_sekolah: shallowRef(GraduationCap),
  bast_company: shallowRef(Building),
  surat_jalan: shallowRef(Truck),
  faktur: shallowRef(Receipt),
  kuitansi: shallowRef(CreditCard),
  bukti_foto: shallowRef(Camera),
};

// Computed
const readyCount = computed(() => documentItems.value.filter((d) => d.is_data_complete).length);
const incompleteCount = computed(() => documentItems.value.filter((d) => !d.is_data_complete).length);
const completedCount = computed(() => documentItems.value.filter((d) => d.status === 'FINAL' || d.file_path).length);
const isAllSelected = computed(() => documentItems.value.length > 0 && selectedDocKeys.value.length === documentItems.value.length);
const isIndeterminate = computed(() => selectedDocKeys.value.length > 0 && selectedDocKeys.value.length < documentItems.value.length);

function toggleSelectAll(e) {
  selectedDocKeys.value = e.target.checked ? documentItems.value.map((d) => d.doc_key) : [];
}

// Lifecycle
onMounted(async () => {
  await fetchProjects();
  if (projectsList.value.length > 0) {
    selectedProjectId.value = projectsList.value[0].project_id;
    await loadProjectData(selectedProjectId.value);
  }
});

async function fetchProjects() {
  try {
    const res = await spjService.getProjects();
    if (res.success && res.data) {
      projectsList.value = res.data;
    }
  } catch (err) {
    console.error(err);
    toast.error('Gagal memuat daftar proyek SPJ.');
  }
}

async function loadProjectData(projectId) {
  try {
    const res = await spjService.getDocuments(projectId);
    if (res.success) {
      const client = findProjectCustomer(res.project, store.clients);
      if (client && !Object.hasOwn(res.project.metadata || {}, 'customer_profile') && !Object.keys(res.project.customer_profile || {}).length) {
        const saved = await spjService.updateProject(projectId, { customer_code: client.code, metadata: { customer_profile: customerProfile(client) } });
        res.project = saved.data;
      }
      activeProject.value = res.project;
      documentItems.value = (res.data || []).map((d) => ({
        ...d,
        iconComponent: iconMap[d.doc_key] || shallowRef(FolderKanban),
        defaultDescription: getDefaultDocDescription(d.doc_key),
      }));

      initDocumentForms();
    }
  } catch (err) {
    console.error(err);
    toast.error(`Gagal memuat data dokumen proyek ${projectId}`);
  }
}

async function handleProjectChange() {
  await loadProjectData(selectedProjectId.value);
  selectedDocKeys.value = [];
}

function getDefaultDocDescription(key) {
  const map = {
    cover_project: 'Sampul resmi dokumen pertanggungjawaban kegiatan',
    perencanaan: 'Spesifikasi teknis & jadwal perencanaan pengadaan',
    hps: 'Harga Perkiraan Sendiri yang ditetapkan PPK/Sekolah',
    spph: 'Surat Permintaan Penawaran Harga dari instansi',
    penawaran: 'Surat penawaran harga resmi dari CV Alpharo Artha Jaya',
    klarifikasi_nego: 'Berita acara klarifikasi teknis dan negosiasi harga',
    permintaan_penawaran: 'Surat Pesanan pembelian langsung dari hasil negosiasi',
    spk: 'Surat Perintah Kerja resmi pelaksanaan pekerjaan',
    hasil_pemeriksaan: 'Berita acara pemeriksaan fisik barang oleh tim pemeriksa',
    bast_sekolah: 'Berita Acara Serah Terima format standar instansi/sekolah',
    bast_company: 'Berita Acara Serah Terima format CV Alpharo Artha Jaya',
    surat_jalan: 'Surat jalan bukti pengiriman barang ke alamat customer',
    faktur: 'Faktur komersial & faktur pajak tagihan pekerjaan',
    kuitansi: 'Bukti kuitansi penerimaan pembayaran resmi bermeterai',
    bukti_foto: 'Dokumentasi foto serah terima barang di lokasi pekerjaan',
  };
  return map[key] || 'Dokumen administrasi kelengkapan SPJ';
}

function initDocumentForms() {
  const p = activeProject.value;
  if (!p) return;

  projectForm.value = projectWithLetterhead(p);

  // Cover Project
  const cpDoc = getDocumentByKey('cover_project');
  const cpSaved = cpDoc?.form_data || {};
  coverProjectForm.value = {
    doc_number: cpDoc?.doc_number || cpSaved.doc_number || `001/SPJ/${p.project_id}/2026`,
    doc_date: cpDoc?.doc_date || cpSaved.doc_date || '01/10/2026',
    project_name: cpSaved.project_name || p.project_name || '',
    customer_name: cpSaved.customer_name || p.customer_name || '',
    funding_source: cpSaved.funding_source || p.funding_source || 'BOP',
    fiscal_year: cpSaved.fiscal_year || p.fiscal_year || '2026',
    total_value: cpSaved.total_value || p.project_value || p.budget_ceiling || 0,
    keterangan: cpSaved.keterangan || 'Berkas Laporan Pertanggungjawaban Pengadaan Barang/Jasa Lengkap',
  };

  // Perencanaan
  const perDoc = getDocumentByKey('perencanaan');
  const perSaved = perDoc?.form_data || {};
  perencanaanForm.value = {
    doc_number: perDoc?.doc_number || perSaved.doc_number || `PER-${p.project_id}/2026`,
    doc_date: perDoc?.doc_date || perSaved.doc_date || '01/10/2026',
    activity_name: perSaved.activity_name || p.activity_name || p.project_name || '',
    funding_source: perSaved.funding_source || p.funding_source || 'BOP',
    fiscal_year: perSaved.fiscal_year || p.fiscal_year || '2026',
    lokasi_pekerjaan: perSaved.lokasi_pekerjaan || p.customer_name || '',
    waktu_pelaksanaan: perSaved.waktu_pelaksanaan || '30 (Tiga Puluh) Hari Kalender',
    items: perSaved.items && perSaved.items.length > 0 ? JSON.parse(JSON.stringify(perSaved.items)) : normalizeItems(p.items || []),
  };

  // HPS
  const hpsDoc = getDocumentByKey('hps');
  const hpsSaved = hpsDoc?.form_data || {};
  hpsForm.value = {
    doc_number: hpsDoc?.doc_number || hpsSaved.doc_number || `HPS-${p.project_id}/2026`,
    doc_date: hpsDoc?.doc_date || hpsSaved.doc_date || '01/10/2026',
    activity_name: hpsSaved.activity_name || p.activity_name || p.project_name || '',
    funding_source: hpsSaved.funding_source || p.funding_source || 'BOP',
    items: hpsSaved.items && hpsSaved.items.length > 0 ? JSON.parse(JSON.stringify(hpsSaved.items)) : normalizeItems(p.items || []),
  };

  // SPPH
  const spphDoc = getDocumentByKey('spph');
  const spphSaved = spphDoc?.form_data || {};
  spphForm.value = {
    doc_number: spphDoc?.doc_number || spphSaved.doc_number || `SPPH-${p.project_id}/2026`,
    doc_date: spphDoc?.doc_date || spphSaved.doc_date || '01/10/2026',
    pekerjaan: spphSaved.pekerjaan || p.project_name || '',
    batas_akhir_penawaran: spphSaved.batas_akhir_penawaran || '05/10/2026',
    content: spphSaved.content || `Sehubungan dengan rencana pelaksanaan kegiatan ${p.project_name || ''}, dengan ini kami meminta penawaran harga untuk pekerjaan pengadaan sebagaimana terlampir.`,
    items: spphSaved.items && spphSaved.items.length > 0 ? JSON.parse(JSON.stringify(spphSaved.items)) : normalizeItems(p.items || []),
  };

  // Penawaran
  const penDoc = getDocumentByKey('penawaran');
  const penSaved = penDoc?.form_data || {};
  penawaranForm.value = {
    doc_number: penDoc?.doc_number || penSaved.doc_number || '001/AAJ/SPH/X/2026',
    doc_date: penDoc?.doc_date || penSaved.doc_date || '01/10/2026',
    content: penSaved.content || `Menunjuk Surat Permintaan Penawaran Harga dari ${p.customer_name}, bersama ini kami mengajukan penawaran harga untuk ${p.project_name} ${p.funding_source || 'BOP'} Tahun Anggaran ${p.fiscal_year || '2026'}. Harga penawaran sudah termasuk PPN dan biaya pengiriman ke lokasi.`,
  };

  // Klarifikasi / Nego
  const negoDoc = getDocumentByKey('klarifikasi_nego');
  const negoSaved = negoDoc?.form_data || {};
  negoForm.value = {
    doc_number: negoDoc?.doc_number || negoSaved.doc_number || `KN-${p.project_id}/2026`,
    doc_date: negoDoc?.doc_date || negoSaved.doc_date || '01/10/2026',
    nego_result: negoSaved.nego_result || 'Sepakat',
    items: negoSaved.items && negoSaved.items.length > 0
      ? JSON.parse(JSON.stringify(negoSaved.items))
      : normalizeItems(p.items || []).map(it => ({
          ...it,
          original_price: it.price,
          nego_price: it.price_nego ?? it.price,
        })),
  };

  // Permintaan Penawaran / Surat Pesanan
  const pesananDoc = getDocumentByKey('permintaan_penawaran');
  const pesananSaved = pesananDoc?.form_data || {};
  suratPesananForm.value = {
    doc_number: pesananDoc?.doc_number || pesananSaved.doc_number || `PO-${p.project_id}/2026`,
    doc_date: pesananDoc?.doc_date || pesananSaved.doc_date || '01/10/2026',
    content: pesananSaved.content || `Sehubungan dengan kegiatan ${p.project_name || 'pengadaan barang'}, dengan ini kami mengajukan permintaan pembelian langsung kepada saudara.`,
  };

  // SPK
  const spkDoc = getDocumentByKey('spk');
  const spkSaved = spkDoc?.form_data || {};
  spkForm.value = {
    doc_number: spkDoc?.doc_number || spkSaved.doc_number || `SPK-${p.project_id}/2026`,
    doc_date: spkDoc?.doc_date || spkSaved.doc_date || '01/10/2026',
    paket_pekerjaan: spkSaved.paket_pekerjaan || p.project_name || '',
    instruksi_penyedia: spkSaved.instruksi_penyedia || 'Penagihan hanya dapat dilakukan setelah penyelesaian pekerjaan yang diperintahkan dalam SPK ini dan dibuktikan dengan Surat Hasil Pemeriksaan dan Berita Acara Serah Terima.',
  };

  // Hasil Pemeriksaan
  const hpDoc = getDocumentByKey('hasil_pemeriksaan');
  const hpSaved = hpDoc?.form_data || {};
  hasilPemeriksaanForm.value = {
    doc_number: hpDoc?.doc_number || hpSaved.doc_number || `HP-${p.project_id}/2026`,
    doc_date: hpDoc?.doc_date || hpSaved.doc_date || '01/10/2026',
    hasil_pemeriksaan: hpSaved.hasil_pemeriksaan || 'Baik dan lengkap',
    catatan: hpSaved.catatan || `Berdasarkan hasil pemeriksaan, pekerjaan ${p.project_name || 'pengadaan'} telah diterima dalam keadaan baik dan lengkap.`,
  };

  // BAST
  const bastDoc = getDocumentByKey('bast_sekolah') || getDocumentByKey('bast_company');
  const bastSaved = bastDoc?.form_data || {};
  bastForm.value = {
    doc_number: bastDoc?.doc_number || bastSaved.doc_number || `001/AAJ/BAST/${p.project_id}/2026`,
    doc_date: bastDoc?.doc_date || bastSaved.doc_date || '01/10/2026',
    content: bastSaved.content || `Berdasarkan hasil pemeriksaan pekerjaan, maka pekerjaan diserahterimakan dari penyedia kepada pihak sekolah dalam keadaan baik dan lengkap.`,
  };

  // Surat Jalan
  const sjDoc = getDocumentByKey('surat_jalan');
  const sjSaved = sjDoc?.form_data || {};
  const whData = p.warehouse_data || {};
  suratJalanForm.value = {
    doc_number: sjDoc?.doc_number || sjSaved.doc_number || `SJ-${p.project_id}/2026`,
    doc_date: sjDoc?.doc_date || sjSaved.doc_date || '01/10/2026',
    kendaraan: sjSaved.kendaraan || 'Mobil Box Engkel',
    no_kendaraan: sjSaved.no_kendaraan || whData.vehicle_plate || 'B 9812 AAJ',
    nama_supir: sjSaved.nama_supir || whData.driver_name || 'Agus Santoso',
    items: sjSaved.items && sjSaved.items.length > 0
      ? JSON.parse(JSON.stringify(sjSaved.items))
      : normalizeItems(p.items || []).map(it => ({
          code: it.item_name || it.code || 'Barang Pengadaan',
          stock: 100,
          qty_sent: it.quantity || it.volume || 1,
        })),
  };

  // Faktur
  const fakturDoc = getDocumentByKey('faktur');
  const fakturSaved = fakturDoc?.form_data || {};
  const normalizedItems = normalizeItems(p.items || []);

  const initialFakturItems = (fakturSaved.items && fakturSaved.items.length > 0)
    ? JSON.parse(JSON.stringify(fakturSaved.items))
    : (normalizedItems.length > 0
        ? normalizedItems.map(it => ({
            code: it.item_name || it.code,
            quantity: it.quantity || 1,
            price: it.price_nego || it.price || 0,
            subtotal: (it.quantity || 1) * (it.price_nego || it.price || 0),
          }))
        : [{ code: p.project_name || 'Pengadaan Barang', quantity: 1, price: p.project_value || 0, subtotal: p.project_value || 0 }]
      );

  fakturForm.value = {
    doc_number: fakturDoc?.doc_number || fakturSaved.doc_number || `INV-${p.project_id}/2026`,
    doc_date: fakturDoc?.doc_date || fakturSaved.doc_date || '06/10/2026',
    jenis_harga: fakturSaved.jenis_harga || 'Harga Nett',
    project_id: p.project_id,
    items: initialFakturItems,
    subtotal: fakturSaved.subtotal || 0,
    ppn_rate: fakturSaved.ppn_rate ?? 11,
    ppn: fakturSaved.ppn || 0,
    total_harga: fakturSaved.total_harga || 0,
    pph_rate: fakturSaved.pph_rate ?? 1.5,
    pph: fakturSaved.pph || 0,
    nilai_setelah_pph: fakturSaved.nilai_setelah_pph || 0,
  };
  recalculateFaktur();

  // Kuitansi
  const kuitansiDoc = getDocumentByKey('kuitansi');
  const kuitansiSaved = kuitansiDoc?.form_data || {};
  const nominalKuitansi = kuitansiSaved.jumlah || formatNumber(fakturForm.value.nilai_setelah_pph || p.project_value || 0);

  kuitansiForm.value = {
    doc_number: kuitansiDoc?.doc_number || kuitansiSaved.doc_number || `KWT-${p.project_id}/2026`,
    doc_date: kuitansiDoc?.doc_date || kuitansiSaved.doc_date || '06/10/2026',
    metode: kuitansiSaved.metode || 'Tunai',
    jumlah: nominalKuitansi,
    keterangan: kuitansiSaved.keterangan || `Pembayaran Lunas Pekerjaan ${p.project_name || ''}`,
  };

  // Bukti Foto
  const fotoDoc = getDocumentByKey('bukti_foto');
  const fotoSaved = fotoDoc?.form_data || {};
  buktiFotoForm.value = {
    doc_number: fotoDoc?.doc_number || fotoSaved.doc_number || `DOK-001/AAJ/X/2026`,
    doc_date: fotoDoc?.doc_date || fotoSaved.doc_date || '01/10/2026',
    project_name: fotoSaved.project_name || p.project_name || '',
    customer_name: fotoSaved.customer_name || p.customer_name || '',
    lokasi: fotoSaved.lokasi || `Lab / Lokasi ${p.customer_name || ''}`,
    tahap: fotoSaved.tahap || 'Serah Terima',
    photos: fotoSaved.photos && fotoSaved.photos.length > 0
      ? JSON.parse(JSON.stringify(fotoSaved.photos))
      : [
          { id: 1, title: 'Foto 1: Pemeriksaan Fisik Barang', desc: `Pemeriksaan unit barang di ${p.customer_name || 'lokasi serah terima'}` },
          { id: 2, title: 'Foto 2: Uji Coba Fungsi & Kelengkapan', desc: 'Pengujian fungsi dan kelengkapan aksesoris unit' },
          { id: 3, title: 'Foto 3: Serah Terima kepada Sekolah', desc: 'Penyerahan fisik dan penandatanganan dokumen BAST bersama perwakilan pihak sekolah' },
        ],
  };
}

function getDocumentByKey(docKey) {
  return documentItems.value.find((d) => d.doc_key === docKey) || null;
}

// Navigation between views
function goBackToList() {
  currentView.value = 'list';
}

function openProjectDataView() {
  const p = activeProject.value || {};
  projectForm.value = {
    ...projectWithLetterhead(JSON.parse(JSON.stringify(p))),
    warehouse_data: p.warehouse_data ? { ...p.warehouse_data } : { driver_name: '', vehicle_plate: '' },
    finance_data: p.finance_data ? { ...p.finance_data } : {},
    items: Array.isArray(p.items) && p.items.length > 0
      ? JSON.parse(JSON.stringify(p.items))
      : [{ item_name: p.project_name || '', quantity: 1, unit: 'UNIT', price: p.project_value || 0 }],
  };
  currentView.value = 'data_project';
}

function addProjectItem() {
  if (!projectForm.value.items) projectForm.value.items = [];
  projectForm.value.items.push({ item_name: '', quantity: 1, unit: 'UNIT', price: 0 });
}

function removeProjectItem(idx) {
  if (projectForm.value.items && projectForm.value.items.length > 1) {
    projectForm.value.items.splice(idx, 1);
  } else if (projectForm.value.items && projectForm.value.items.length === 1) {
    projectForm.value.items = [{ item_name: '', quantity: 1, unit: 'UNIT', price: 0 }];
  }
}

function addPerencanaanItem() {
  if (!perencanaanForm.value.items) perencanaanForm.value.items = [];
  perencanaanForm.value.items.push({ item_name: '', quantity: 1, unit: 'UNIT', price: 0 });
}

function removePerencanaanItem(idx) {
  if (perencanaanForm.value.items && perencanaanForm.value.items.length > 1) {
    perencanaanForm.value.items.splice(idx, 1);
  }
}

function addHpsItem() {
  if (!hpsForm.value.items) hpsForm.value.items = [];
  hpsForm.value.items.push({ item_name: '', quantity: 1, unit: 'UNIT', price: 0 });
}

function removeHpsItem(idx) {
  if (hpsForm.value.items && hpsForm.value.items.length > 1) {
    hpsForm.value.items.splice(idx, 1);
  }
}

function addSpphItem() {
  if (!spphForm.value.items) spphForm.value.items = [];
  spphForm.value.items.push({ item_name: '', quantity: 1, unit: 'UNIT' });
}

function removeSpphItem(idx) {
  if (spphForm.value.items && spphForm.value.items.length > 1) {
    spphForm.value.items.splice(idx, 1);
  }
}

function addSuratJalanItem() {
  if (!suratJalanForm.value.items) suratJalanForm.value.items = [];
  suratJalanForm.value.items.push({ code: '', stock: 0, qty_sent: 1 });
}

function removeSuratJalanItem(idx) {
  if (suratJalanForm.value.items && suratJalanForm.value.items.length > 1) {
    suratJalanForm.value.items.splice(idx, 1);
  }
}

function openDocumentForm(item) {
  activeDocItem.value = item;
  switch (item.doc_key) {
    case 'cover_project':
      currentView.value = 'edit_cover_project';
      break;
    case 'perencanaan':
      currentView.value = 'edit_perencanaan';
      break;
    case 'spph':
      currentView.value = 'edit_spph';
      break;
    case 'faktur':
      currentView.value = 'edit_faktur';
      break;
    case 'kuitansi':
      currentView.value = 'edit_kuitansi';
      break;
    case 'bukti_foto':
      currentView.value = 'bukti_foto';
      break;
    case 'hps':
      currentView.value = 'edit_hps';
      break;
    case 'penawaran':
      currentView.value = 'edit_penawaran';
      break;
    case 'klarifikasi_nego':
      currentView.value = 'edit_nego';
      break;
    case 'permintaan_penawaran':
      currentView.value = 'edit_permintaan_penawaran';
      break;
    case 'spk':
      currentView.value = 'edit_spk';
      break;
    case 'hasil_pemeriksaan':
      currentView.value = 'edit_hasil_pemeriksaan';
      break;
    case 'bast_sekolah':
    case 'bast_company':
      bastForm.value = {
        doc_number: item.doc_number || item.form_data?.doc_number || '',
        doc_date: item.doc_date || item.form_data?.doc_date || '',
        content: item.form_data?.content ?? item.form_data?.isi_bast ?? `Pekerjaan ${activeProject.value?.project_name || ''} diserahterimakan dari penyedia kepada ${activeProject.value?.customer_name || 'penerima'} sesuai hasil pemeriksaan.`,
      };
      currentView.value = 'edit_bast';
      break;
    case 'surat_jalan':
      currentView.value = 'edit_surat_jalan';
      break;
    default:
      openPdfPreview(item);
      break;
  }
}

// Faktur Calculation
function recalculateFaktur() {
  let subtotal = 0;
  (fakturForm.value.items || []).forEach(it => {
    if (it.quantity && it.price) {
      it.subtotal = it.quantity * it.price;
      subtotal += it.subtotal;
    } else {
      it.subtotal = 0;
    }
  });
  fakturForm.value.subtotal = subtotal;
  fakturForm.value.ppn = Math.round(subtotal * (fakturForm.value.ppn_rate / 100));
  fakturForm.value.total_harga = subtotal + fakturForm.value.ppn;
  fakturForm.value.pph = Math.round(subtotal * (fakturForm.value.pph_rate / 100));
  fakturForm.value.nilai_setelah_pph = fakturForm.value.total_harga - fakturForm.value.pph;
}

function addFakturItemRow() {
  fakturForm.value.items.push({ code: '', quantity: null, price: null, subtotal: 0 });
}

function removeFakturItemRow(idx) {
  if (fakturForm.value.items.length > 1) {
    fakturForm.value.items.splice(idx, 1);
    recalculateFaktur();
  }
}

// Save Handlers
async function saveCoverProjectDocument(notify = true) {
  try {
    const res = await spjService.updateDocument(selectedProjectId.value, 'cover_project', {
      doc_number: coverProjectForm.value.doc_number,
      doc_date: coverProjectForm.value.doc_date,
      form_data: coverProjectForm.value,
    });
    if (res.success && notify) {
      toast.success('Dokumen Cover Project berhasil disimpan.');
      currentView.value = 'list';
      await loadProjectData(selectedProjectId.value);
    }
  } catch (err) {
    console.error(err);
    if (!notify) throw err;
    if (notify) toast.error('Gagal menyimpan dokumen Cover Project.');
  }
}

async function savePerencanaanDocument(notify = true) {
  const form = JSON.parse(JSON.stringify(perencanaanForm.value));
  try {
    const res = await spjService.updateDocument(selectedProjectId.value, 'perencanaan', {
      doc_number: form.doc_number,
      doc_date: form.doc_date,
      form_data: form,
    });
    // Sinkronkan rincian barang & anggaran yang diedit ke master proyek
    await spjService.updateProject(selectedProjectId.value, {
      items: form.items,
      activity_name: form.activity_name,
      funding_source: form.funding_source,
    });
    if (res.success && notify) {
      toast.success('Dokumen Perencanaan & rincian barang berhasil disimpan.');
      currentView.value = 'list';
      await loadProjectData(selectedProjectId.value);
    }
  } catch (err) {
    console.error(err);
    if (!notify) throw err;
    if (notify) toast.error('Gagal menyimpan dokumen Perencanaan.');
  }
}

async function saveSpphDocument(notify = true) {
  try {
    const res = await spjService.updateDocument(selectedProjectId.value, 'spph', {
      doc_number: spphForm.value.doc_number,
      doc_date: spphForm.value.doc_date,
      form_data: spphForm.value,
    });
    if (res.success && notify) {
      toast.success('Dokumen SPPH berhasil disimpan.');
      currentView.value = 'list';
      await loadProjectData(selectedProjectId.value);
    }
  } catch (err) {
    console.error(err);
    if (!notify) throw err;
    if (notify) toast.error('Gagal menyimpan dokumen SPPH.');
  }
}

async function saveProjectData() {
  try {
    if (projectForm.value.metadata?.customer_profile?.orgFullName) {
      projectForm.value.metadata.customer_profile.orgFullName = expandSchoolName(projectForm.value.metadata.customer_profile.orgFullName);
    }
    const res = await spjService.updateProject(selectedProjectId.value, { ...projectForm.value, metadata: { customer_profile: projectForm.value.metadata.customer_profile } });
    if (res.success) {
      toast.success('Data proyek berhasil diperbarui.');
      currentView.value = 'list';
      await loadProjectData(selectedProjectId.value);
    }
  } catch (err) {
    console.error(err);
    toast.error('Gagal menyimpan data proyek.');
  }
}

async function setLetterheadLogo(event) {
  const file = event.target.files?.[0];
  if (!file) return;
  try {
    if (!['image/jpeg', 'image/png'].includes(file.type) || file.size > 1024 * 1024) throw new Error('Gunakan logo JPG/PNG maksimal 1 MB.');
    const url = await new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result);
      reader.onerror = reject;
      reader.readAsDataURL(file);
    });
    const image = new Image();
    image.src = url;
    await image.decode();
    if (image.naturalWidth * image.naturalHeight > 4000000) throw new Error('Resolusi logo maksimal 4 megapiksel.');
    projectForm.value.metadata.customer_profile.logoDataUrl = url;
  } catch (error) { toast.error(error.message || 'Logo gagal dibaca. Pilih ulang berkas.'); }
  finally { event.target.value = ''; }
}

async function saveFakturDocument(notify = true) {
  try {
    recalculateFaktur();
    const effectiveTerbilang = formatTerbilang(fakturForm.value.nilai_setelah_pph || fakturForm.value.total_harga || 0);
    const res = await spjService.updateDocument(selectedProjectId.value, 'faktur', {
      doc_number: fakturForm.value.doc_number,
      doc_date: fakturForm.value.doc_date,
      form_data: {
        ...fakturForm.value,
        terbilang: effectiveTerbilang,
      },
    });
    if (res.success && notify) {
      toast.success('Dokumen Faktur berhasil disimpan.');
      currentView.value = 'list';
      await loadProjectData(selectedProjectId.value);
    }
  } catch (err) {
    console.error(err);
    if (!notify) throw err;
    if (notify) toast.error('Gagal menyimpan dokumen Faktur.');
  }
}

async function saveKuitansiDocument(notify = true) {
  try {
    const rawVal = Number(String(kuitansiForm.value.jumlah || '').replace(/[^0-9]/g, '')) || fakturForm.value.nilai_setelah_pph || 0;
    const effectiveTerbilang = formatTerbilang(rawVal);
    const res = await spjService.updateDocument(selectedProjectId.value, 'kuitansi', {
      doc_number: kuitansiForm.value.doc_number,
      doc_date: kuitansiForm.value.doc_date,
      form_data: {
        ...kuitansiForm.value,
        terbilang: effectiveTerbilang,
        nilai_setelah_pph: rawVal,
      },
    });
    if (res.success && notify) {
      toast.success('Dokumen Kuitansi berhasil disimpan.');
      currentView.value = 'list';
      await loadProjectData(selectedProjectId.value);
    }
  } catch (err) {
    console.error(err);
    if (!notify) throw err;
    if (notify) toast.error('Gagal menyimpan dokumen Kuitansi.');
  }
}

async function saveBuktiFotoDocument(notify = true) {
  try {
    const res = await spjService.updateDocument(selectedProjectId.value, 'bukti_foto', {
      doc_number: buktiFotoForm.value.doc_number,
      doc_date: buktiFotoForm.value.doc_date,
      form_data: buktiFotoForm.value,
    });
    if (res.success && notify) {
      toast.success('Lampiran Bukti Foto berhasil disimpan.');
      currentView.value = 'list';
      await loadProjectData(selectedProjectId.value);
    }
  } catch (err) {
    console.error(err);
    if (!notify) throw err;
    if (notify) toast.error('Gagal menyimpan Lampiran Bukti Foto.');
  }
}

async function setDocumentationPhoto(event, photo) {
  const file = event.target.files?.[0];
  if (!file) return;
  if (!['image/jpeg', 'image/png'].includes(file.type) || file.size > 3 * 1024 * 1024) {
    toast.error('Gunakan foto JPG/PNG maksimal 3 MB.');
    event.target.value = '';
    return;
  }
  try {
    photo.data_url = await new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result);
      reader.onerror = reject;
      reader.readAsDataURL(file);
    });
  } catch { toast.error('Foto gagal dibaca. Pilih ulang berkas.'); }
  finally { event.target.value = ''; }
}

async function saveHpsDocument(notify = true) {
  const form = JSON.parse(JSON.stringify(hpsForm.value));
  try {
    const res = await spjService.updateDocument(selectedProjectId.value, 'hps', {
      doc_number: form.doc_number,
      doc_date: form.doc_date,
      form_data: form,
    });
    // Sinkronkan rincian barang & estimasi harga ke master proyek
    await spjService.updateProject(selectedProjectId.value, {
      items: form.items,
      activity_name: form.activity_name,
      funding_source: form.funding_source,
    });
    if (res.success && notify) {
      toast.success('Dokumen HPS & rincian harga berhasil disimpan.');
      currentView.value = 'list';
      await loadProjectData(selectedProjectId.value);
    }
  } catch (err) {
    console.error(err);
    if (!notify) throw err;
    if (notify) toast.error('Gagal menyimpan dokumen HPS.');
  }
}

async function savePenawaranDocument(notify = true) {
  try {
    const res = await spjService.updateDocument(selectedProjectId.value, 'penawaran', {
      doc_number: penawaranForm.value.doc_number,
      doc_date: penawaranForm.value.doc_date,
      form_data: penawaranForm.value,
    });
    if (res.success && notify) {
      toast.success('Dokumen Penawaran berhasil disimpan.');
      currentView.value = 'list';
      await loadProjectData(selectedProjectId.value);
    }
  } catch (err) {
    console.error(err);
    if (!notify) throw err;
    if (notify) toast.error('Gagal menyimpan dokumen Penawaran.');
  }
}

async function saveNegoDocument(notify = true) {
  try {
    const res = await spjService.updateDocument(selectedProjectId.value, 'klarifikasi_nego', {
      doc_number: negoForm.value.doc_number,
      doc_date: negoForm.value.doc_date,
      form_data: negoForm.value,
    });
    if (res.success && notify) {
      toast.success('Dokumen Klarifikasi/Nego berhasil disimpan.');
      currentView.value = 'list';
      await loadProjectData(selectedProjectId.value);
    }
  } catch (err) {
    console.error(err);
    if (!notify) throw err;
    if (notify) toast.error('Gagal menyimpan dokumen Klarifikasi/Nego.');
  }
}

async function saveSuratPesananDocument(notify = true) {
  try {
    const res = await spjService.updateDocument(selectedProjectId.value, 'permintaan_penawaran', {
      doc_number: suratPesananForm.value.doc_number,
      doc_date: suratPesananForm.value.doc_date,
      form_data: suratPesananForm.value,
    });
    if (res.success && notify) {
      toast.success('Surat Pesanan berhasil disimpan.');
      currentView.value = 'list';
      await loadProjectData(selectedProjectId.value);
    }
  } catch (err) {
    console.error(err);
    if (!notify) throw err;
    if (notify) toast.error('Gagal menyimpan Surat Pesanan.');
  }
}

async function saveSpkDocument(notify = true) {
  try {
    const res = await spjService.updateDocument(selectedProjectId.value, 'spk', {
      doc_number: spkForm.value.doc_number,
      doc_date: spkForm.value.doc_date,
      form_data: spkForm.value,
    });
    if (res.success && notify) {
      toast.success('Dokumen SPK berhasil disimpan.');
      currentView.value = 'list';
      await loadProjectData(selectedProjectId.value);
    }
  } catch (err) {
    console.error(err);
    if (!notify) throw err;
    if (notify) toast.error('Gagal menyimpan dokumen SPK.');
  }
}

async function saveHasilPemeriksaanDocument(notify = true) {
  try {
    const res = await spjService.updateDocument(selectedProjectId.value, 'hasil_pemeriksaan', {
      doc_number: hasilPemeriksaanForm.value.doc_number,
      doc_date: hasilPemeriksaanForm.value.doc_date,
      form_data: hasilPemeriksaanForm.value,
    });
    if (res.success && notify) {
      toast.success('Dokumen Hasil Pemeriksaan berhasil disimpan.');
      currentView.value = 'list';
      await loadProjectData(selectedProjectId.value);
    }
  } catch (err) {
    console.error(err);
    if (!notify) throw err;
    if (notify) toast.error('Gagal menyimpan Hasil Pemeriksaan.');
  }
}

async function saveBastDocument(notify = true) {
  try {
    const docKey = activeDocItem.value?.doc_key === 'bast_company' ? 'bast_company' : 'bast_sekolah';
    const res = await spjService.updateDocument(selectedProjectId.value, docKey, {
      doc_number: bastForm.value.doc_number,
      doc_date: bastForm.value.doc_date,
      form_data: bastForm.value,
    });
    if (res.success && notify) {
      toast.success('Dokumen BAST berhasil disimpan.');
      currentView.value = 'list';
      await loadProjectData(selectedProjectId.value);
    }
  } catch (err) {
    console.error(err);
    if (!notify) throw err;
    if (notify) toast.error('Gagal menyimpan dokumen BAST.');
  }
}

async function saveSuratJalanDocument(notify = true) {
  const form = JSON.parse(JSON.stringify(suratJalanForm.value));
  try {
    const res = await spjService.updateDocument(selectedProjectId.value, 'surat_jalan', {
      doc_number: form.doc_number,
      doc_date: form.doc_date,
      form_data: form,
    });
    // Sync warehouse driver and vehicle plate to project
    await spjService.updateProject(selectedProjectId.value, {
      warehouse_data: {
        ...activeProject.value?.warehouse_data,
        driver_name: form.nama_supir,
        vehicle_plate: form.no_kendaraan,
      },
    });
    if (res.success && notify) {
      toast.success('Dokumen Surat Jalan & data logistik berhasil disimpan.');
      currentView.value = 'list';
      await loadProjectData(selectedProjectId.value);
    }
  } catch (err) {
    console.error(err);
    if (!notify) throw err;
    if (notify) toast.error('Gagal menyimpan dokumen Surat Jalan.');
  }
}

// Integrated Save and Generate Handler
async function handleSaveAndGenerate(docKey, format) {
  if (isGeneratingKey.value) return;
  const projectId = selectedProjectId.value;
  const formData = JSON.parse(JSON.stringify(getFormDataForDoc(docKey)));
  isGeneratingKey.value = docKey;
  try {
    const saveHandlers = {
      cover_project: saveCoverProjectDocument, perencanaan: savePerencanaanDocument,
      spph: saveSpphDocument, faktur: saveFakturDocument, kuitansi: saveKuitansiDocument,
      bukti_foto: saveBuktiFotoDocument, hps: saveHpsDocument, penawaran: savePenawaranDocument,
      klarifikasi_nego: saveNegoDocument, permintaan_penawaran: saveSuratPesananDocument,
      spk: saveSpkDocument, hasil_pemeriksaan: saveHasilPemeriksaanDocument,
      bast_sekolah: saveBastDocument, bast_company: saveBastDocument, surat_jalan: saveSuratJalanDocument,
    };
    await saveHandlers[docKey]?.(false);
    const fileFormat = format === 'pdf' ? 'pdf' : 'docx';
    await spjService.generateDocument(projectId, docKey, formData, fileFormat);
    await downloadActiveDocFile(docKey, fileFormat, projectId);
    await loadProjectData(projectId);
    toast.success(`Dokumen ${fileFormat === 'pdf' ? 'PDF' : 'Word'} berhasil dibuat dari data tersimpan.`);
  } catch (error) { toast.error(await documentError(error)); }
  finally { isGeneratingKey.value = null; }
}

async function handleSingleGenerate(item, overrideData = null) {
  if (isGeneratingKey.value) return;
  const projectId = selectedProjectId.value;
  isGeneratingKey.value = item.doc_key;
  try {
    await spjService.generateDocument(projectId, item.doc_key, overrideData || item.form_data || {});
    await downloadActiveDocFile(item.doc_key, 'docx', projectId);
    await loadProjectData(projectId);
    toast.success(`Dokumen ${item.doc_name} berhasil dibuat.`);
  } catch (error) { toast.error(await documentError(error)); }
  finally { isGeneratingKey.value = null; }
}

async function handleBulkGenerate() {
  if (selectedDocKeys.value.length === 0) return;

  isBulkGenerating.value = true;
  try {
    const res = await spjService.bulkGenerate(selectedProjectId.value, selectedDocKeys.value);
    if (res.success) {
      const generatedCount = res.data?.success_count || 0;
      if (res.data?.failed_count) toast.error(res.message);
      else toast.success(res.message);
      await loadProjectData(selectedProjectId.value);
      if (!generatedCount) return;

      // PROBIS TO-BE Tahap 11: Dokumen SPJ di-generate
      const pName = activeProject.value?.title || activeProject.value?.name || selectedProjectId.value;
      storeActions.addNotification(
        'WAREHOUSE',
        'Dokumen SPJ Siap Diunduh / Dikirim',
        `Administrasi telah men-generate ${generatedCount} dokumen SPJ untuk proyek "${pName}". Persiapkan barang & armada pengiriman.`,
        'document',
        '/warehouse/shipments'
      );
      storeActions.addNotification(
        'MARKETING',
        'Penyusunan SPJ Berjalan',
        `Dokumen SPJ proyek "${pName}" (${generatedCount} dokumen) telah di-generate oleh Administrasi.`,
        'document',
        '/marketing/orders'
      );
    }
  } catch (err) {
    console.error(err);
    toast.error('Gagal memproses bulk generate dokumen.');
  } finally {
    isBulkGenerating.value = false;
  }
}

function notifyWarehouseSpjReady() {
  const pName = activeProject.value?.title || activeProject.value?.name || selectedProjectId.value;
  storeActions.addNotification(
    'WAREHOUSE',
    'Berkas SPJ Lengkap - Siapkan Pengiriman Armada',
    `Administrasi telah merampungkan penyusunan berkas SPJ proyek "${pName}". Mohon Gudang segera menyiapkan armada dan nomor resi untuk dikirim bersama dokumen SPJ ke customer.`,
    'warehouse',
    '/warehouse/shipments'
  );
  storeActions.addNotification(
    'MARKETING',
    'Laporan SPJ Rampung Disusun',
    `Berkas Surat Pertanggungjawaban (SPJ) proyek "${pName}" telah lengkap dan diserahkan ke bagian Gudang untuk pengiriman.`,
    'document',
    '/marketing/orders'
  );
  storeActions.addActivityLog('Penyusunan SPJ Selesai', selectedProjectId.value, `Berkas SPJ ${pName} diserahkan ke Gudang`);
  toast.success('Pemberitahuan telah dikirimkan ke Gudang untuk persiapan pengiriman barang & SPJ.');
}

async function documentError(error) {
  let data = error.response?.data;
  if (data instanceof Blob) {
    try { data = JSON.parse(await data.text()); } catch { data = null; }
  }
  return data?.errors ? Object.values(data.errors).flat().join(' ') : data?.message || error.message || 'Dokumen gagal dibuat. Periksa data dan coba lagi.';
}

async function downloadActiveDocFile(docKey, format = 'docx', projectId = selectedProjectId.value) {
  const response = await spjService.downloadDocument(projectId, docKey, format);
  const disposition = response.headers['content-disposition'] || '';
  const match = disposition.match(/filename="?([^";]+)"?/i);
  const url = URL.createObjectURL(response.data);
  const link = document.createElement('a');
  link.href = url;
  link.download = match ? match[1] : `${docKey}_${projectId}.${format}`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

async function downloadWithFeedback(docKey, format) {
  try { await downloadActiveDocFile(docKey, format); }
  catch (error) { toast.error(await documentError(error)); }
}

// Upload External
function openUploadExternalModal(item) {
  uploadDocItem.value = item;
  selectedUploadFile.value = null;
  uploadMeta.value = { doc_number: item.doc_number || '', doc_date: item.doc_date || '', notes: '' };
  showUploadModal.value = true;
}

function handleFileUploadChange(e) {
  const file = e.target.files[0];
  if (file) selectedUploadFile.value = file;
}

async function submitUploadExternal() {
  if (!selectedUploadFile.value || !uploadDocItem.value) return;

  isUploading.value = true;
  try {
    const res = await spjService.uploadExternal(
      selectedProjectId.value,
      uploadDocItem.value.doc_key,
      selectedUploadFile.value,
      uploadMeta.value
    );
    if (res.success) {
      toast.success(res.message || 'Berkas eksternal berhasil diunggah.');
      showUploadModal.value = false;
      await loadProjectData(selectedProjectId.value);
    }
  } catch (err) {
    console.error(err);
    toast.error('Gagal mengunggah berkas eksternal.');
  } finally {
    isUploading.value = false;
  }
}

// PDF Print Preview
async function openPdfPreview(item) {
  if (!item || isPdfLoading.value) return;
  pdfDocItem.value = item;
  showPdfModal.value = true;
  isPdfLoading.value = true;
  releasePdfPreview();
  try {
    const response = await spjService.downloadDocument(selectedProjectId.value, item.doc_key, 'pdf');
    if (showPdfModal.value) pdfPreviewUrl.value = URL.createObjectURL(response.data);
  } catch (error) {
    showPdfModal.value = false;
    toast.error(await documentError(error));
  } finally { isPdfLoading.value = false; }
}

function releasePdfPreview() {
  if (pdfPreviewUrl.value) URL.revokeObjectURL(pdfPreviewUrl.value);
  pdfPreviewUrl.value = '';
}

function printDocument() {
  try { pdfPreviewFrame.value?.contentWindow?.print(); }
  catch { toast.info('Gunakan Unduh PDF untuk menyimpan atau mencetak dokumen.'); }
}

// Helpers
function calculateItemsTotal(items = []) {
  const subtotal = (items || []).reduce((acc, curr) => acc + (curr.quantity || 0) * (curr.price || 0), 0);
  const ppn = subtotal * 0.11;
  return { subtotal, ppn, total: subtotal + ppn };
}

function formatMissingSummary(missing = []) {
  if (!missing || missing.length === 0) return 'Data Lengkap';
  const roles = [...new Set(missing.map((m) => typeof m === 'string' ? m.split(':')[0] : m.role).filter(Boolean))];
  return `Butuh: ${roles.join(', ')}`;
}

function getMissingTooltip(missing = []) {
  if (!missing || missing.length === 0) return 'Semua data lengkap';
  return missing.map((m) => typeof m === 'string' ? m : `[${m.role}] ${m.label}`).join(' | ');
}

function getDocumentStatusClass(status) {
  switch (status) {
    case 'FINAL':
      return 'text-alpharo-700';
    case 'MENUNGGU_PERSETUJUAN':
      return 'text-amber-800';
    case 'PERLU_REVISI':
      return 'text-rose-700';
    default:
      return 'text-slate-600';
  }
}

function formatStatusLabel(status) {
  switch (status) {
    case 'BELUM_DIBUAT':
      return 'Belum Dibuat';
    case 'DRAFT':
      return 'Draft';
    case 'MENUNGGU_PERSETUJUAN':
      return 'Menunggu Approval';
    case 'PERLU_REVISI':
      return 'Perlu Revisi';
    case 'FINAL':
      return 'Final';
    case 'DOKUMEN_EKSTERNAL':
      return 'Eksternal';
    default:
      return status;
  }
}

function formatRupiah(value) {
  const val = Number(value || 0);
  return 'Rp ' + val.toLocaleString('id-ID');
}

function formatNumber(value) {
  const val = Number(value || 0);
  return val.toLocaleString('id-ID');
}

function formatFinanceStatus(status) {
  if (!status) return 'Lunas';
  const s = String(status).trim().toUpperCase();
  if (s === 'MENUNGGU_PEMBAYARAN' || s === 'MENUNGGU PEMBAYARAN') return 'Menunggu Bayar';
  if (s === 'BELUM_DIBAYAR' || s === 'BELUM_LUNAS' || s === 'BELUM DIBAYAR') return 'Belum Lunas';
  if (s === 'LUNAS') return 'Lunas';
  if (s === 'SEBAGIAN' || s === 'DIBAYAR_SEBAGIAN') return 'Sebagian';
  return s.replace(/_/g, ' ').toLowerCase().replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatFinanceStatusFull(status) {
  if (!status) return 'Lunas';
  const s = String(status).trim().toUpperCase();
  if (s === 'MENUNGGU_PEMBAYARAN' || s === 'MENUNGGU PEMBAYARAN') return 'Menunggu Pembayaran';
  if (s === 'BELUM_DIBAYAR' || s === 'BELUM_LUNAS' || s === 'BELUM DIBAYAR') return 'Belum Lunas';
  if (s === 'LUNAS') return 'Lunas';
  if (s === 'SEBAGIAN' || s === 'DIBAYAR_SEBAGIAN') return 'Dibayar Sebagian';
  return s.replace(/_/g, ' ').toLowerCase().replace(/\b\w/g, (c) => c.toUpperCase());
}

function normalizeItems(items) {
  if (!Array.isArray(items) || items.length === 0) return [];
  return items.map((it, idx) => {
    const itemName = it.item_name || it.component || it.code || it.name || `Barang ${idx + 1}`;
    const code = it.code || it.item_code || `BRG-${idx + 1}`;
    const qty = Number(it.quantity ?? it.volume ?? it.qty ?? it.qty_sent ?? 1);
    const unit = it.unit || it.satuan || 'Unit';
    const price = Number(it.price ?? it.price_offer ?? it.original_price ?? 0);
    const priceNego = Number(it.price_nego ?? it.nego_price ?? price);
    const subtotal = Number(it.subtotal ?? (qty * price));
    const subtotalNego = Number(it.subtotal_nego ?? (qty * priceNego));
    return {
      ...it,
      item_name: itemName,
      code,
      quantity: qty,
      volume: qty,
      unit,
      price,
      price_offer: price,
      price_nego: priceNego,
      nego_price: priceNego,
      subtotal,
      subtotal_nego: subtotalNego,
    };
  });
}

function formatTerbilang(num) {
  const n = Math.abs(Math.round(Number(num) || 0));
  if (n === 0) return 'Nol Rupiah';

  const satuan = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

  function convert(val) {
    if (val < 12) return satuan[val];
    if (val < 20) return convert(val - 10) + ' Belas';
    if (val < 100) return convert(Math.floor(val / 10)) + ' Puluh ' + convert(val % 10);
    if (val < 200) return 'Seratus ' + convert(val - 100);
    if (val < 1000) return convert(Math.floor(val / 100)) + ' Ratus ' + convert(val % 100);
    if (val < 2000) return 'Seribu ' + convert(val - 1000);
    if (val < 1000000) return convert(Math.floor(val / 1000)) + ' Ribu ' + convert(val % 1000);
    if (val < 1000000000) return convert(Math.floor(val / 1000000)) + ' Juta ' + convert(val % 1000000);
    if (val < 1000000000000) return convert(Math.floor(val / 1000000000)) + ' Miliar ' + convert(val % 1000000000);
    return convert(Math.floor(val / 1000000000000)) + ' Triliun ' + convert(val % 1000000000000);
  }

  const result = convert(n).replace(/\s+/g, ' ').trim();
  return result + ' Rupiah';
}

function getFormDataForDoc(docKey) {
  switch (docKey) {
    case 'cover_project':
      return { ...coverProjectForm.value };
    case 'perencanaan':
      return { ...perencanaanForm.value };
    case 'hps':
      return { ...hpsForm.value };
    case 'spph':
      return { ...spphForm.value };
    case 'penawaran':
      return { ...penawaranForm.value };
    case 'klarifikasi_nego':
      return { ...negoForm.value };
    case 'permintaan_penawaran':
      return { ...suratPesananForm.value };
    case 'spk':
      return { ...spkForm.value };
    case 'hasil_pemeriksaan':
      return { ...hasilPemeriksaanForm.value };
    case 'bast_sekolah':
    case 'bast_company':
      return { ...bastForm.value };
    case 'surat_jalan':
      return { ...suratJalanForm.value };
    case 'faktur':
      return { ...fakturForm.value };
    case 'kuitansi':
      return { ...kuitansiForm.value };
    case 'bukti_foto':
      return { ...buktiFotoForm.value };
    default:
      return {};
  }
}

function getEffectiveItems(docKey) {
  const p = activeProject.value || {};
  let rawItems = [];

  if (docKey === 'faktur' && fakturForm.value.items && fakturForm.value.items.length > 0) {
    rawItems = fakturForm.value.items.filter(it => it.code || it.item_name || it.quantity);
  } else if (docKey === 'surat_jalan' && suratJalanForm.value.items && suratJalanForm.value.items.length > 0) {
    rawItems = suratJalanForm.value.items;
  } else if (docKey === 'klarifikasi_nego' && negoForm.value.items && negoForm.value.items.length > 0) {
    rawItems = negoForm.value.items;
  } else if (docKey === 'hps' && hpsForm.value.items && hpsForm.value.items.length > 0) {
    rawItems = hpsForm.value.items;
  } else if (docKey === 'perencanaan' && perencanaanForm.value.items && perencanaanForm.value.items.length > 0) {
    rawItems = perencanaanForm.value.items;
  } else if (docKey === 'spph' && spphForm.value.items && spphForm.value.items.length > 0) {
    rawItems = spphForm.value.items;
  } else if (fakturForm.value.items && fakturForm.value.items.length > 0 && fakturForm.value.items[0].price) {
    rawItems = fakturForm.value.items.filter(it => it.code || it.item_name || it.quantity);
  } else {
    rawItems = p.items || [];
  }

  if (rawItems.length === 0 && p.items && p.items.length > 0) {
    rawItems = p.items;
  }

  return normalizeItems(rawItems);
}

function getEffectiveDocTotals(docKey) {
  if (docKey === 'faktur' || docKey === 'kuitansi') {
    const subtotal = Number(fakturForm.value.subtotal || 0);
    const ppn = Number(fakturForm.value.ppn || Math.round(subtotal * (fakturForm.value.ppn_rate / 100)));
    const totalHarga = Number(fakturForm.value.total_harga || (subtotal + ppn));
    const pph = Number(fakturForm.value.pph || Math.round(subtotal * (fakturForm.value.pph_rate / 100)));
    const netto = Number(fakturForm.value.nilai_setelah_pph || (totalHarga - pph));
    return {
      subtotal,
      ppn,
      ppn_rate: fakturForm.value.ppn_rate || 11,
      total_harga: totalHarga,
      pph,
      pph_rate: fakturForm.value.pph_rate || 1.5,
      nilai_setelah_pph: netto,
      terbilang: formatTerbilang(netto || totalHarga),
      terbilang_total: formatTerbilang(totalHarga),
    };
  }

  const items = getEffectiveItems(docKey);
  let subtotal = 0;
  let subtotalNego = 0;

  items.forEach(it => {
    subtotal += (it.quantity || 1) * (it.price || 0);
    subtotalNego += (it.quantity || 1) * (it.price_nego || it.price || 0);
  });

  if (subtotal === 0 && activeProject.value?.project_value) {
    subtotal = Number(activeProject.value.project_value);
    subtotalNego = subtotal;
  }

  const ppn = Math.round(subtotalNego * 0.11);
  const total = subtotalNego + ppn;
  const pph = Math.round(subtotalNego * 0.015);
  const netto = total - pph;

  return {
    subtotal,
    subtotal_nego: subtotalNego,
    ppn,
    ppn_rate: 11,
    total_harga: total,
    pph,
    pph_rate: 1.5,
    nilai_setelah_pph: netto,
    terbilang: formatTerbilang(netto || total),
    terbilang_nego: formatTerbilang(total),
    terbilang_penawaran: formatTerbilang(subtotal + Math.round(subtotal * 0.11)),
  };
}
</script>

<style scoped>
@media print {
  body * {
    visibility: hidden;
  }
  #printable-area,
  #printable-area * {
    visibility: visible;
  }
  #printable-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    border: none;
    box-shadow: none;
    margin: 0;
    padding: 20px;
  }
}
</style>
