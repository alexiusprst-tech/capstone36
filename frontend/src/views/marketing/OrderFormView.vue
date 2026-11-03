<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <router-link
              to="/marketing/orders/monitoring"
              class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors"
            >
              <ArrowLeft class="w-3.5 h-3.5" />
              <span>Kembali ke Monitoring Order</span>
            </router-link>
          </div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
            {{ isEdit ? 'Edit Order & Proyek' : 'Tambah Order & Proyek Baru' }}
          </h1>
          <p class="text-sm text-slate-500 mt-0.5">
            {{ isEdit ? `Perbarui informasi proyek ${form.projectId} - ${form.projectName}` : 'Lengkapi data project, pengadaan, jadwal, penanggung jawab, dan nomor dokumen resmi' }}
          </p>
        </div>
      </div>
    </template>

    <form class="space-y-6 max-w-7xl mx-auto pb-12" @submit.prevent="handleSubmit">
      <!-- BANNER JIKA MERINCI DARI PENGAJUAN KLIEN -->
      <div
        v-if="fromClientOrder"
        class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl shadow-2xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
      >
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
            <ClipboardList class="w-5 h-5 text-amber-300 stroke-[2.5]" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold text-emerald-900">Merinci Pengajuan Rekanan Klien:</span>
              <span class="font-mono font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded text-xs">
                {{ fromClientOrder.orderNumber }} &bull; {{ fromClientOrder.clientName }}
              </span>
            </div>
            <p class="text-[11px] text-emerald-700 mt-0.5">
              Data instansi, permohonan proyek, dan {{ (fromClientOrder.items || []).length }} item barang telah dimuat otomatis. Silakan lengkapi spesifikasi pengadaan & nomor dokumen resmi di bawah.
            </p>
          </div>
        </div>
        <router-link
          to="/marketing/orders/monitoring"
          class="text-xs font-bold text-emerald-700 hover:text-emerald-900 underline whitespace-nowrap"
        >
          ← Kembali ke Monitoring
        </router-link>
      </div>

      <!-- 4-CARD 2x2 GRID (SESUAI DOKUMEN TANGKAPAN LAYAR) -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        
        <!-- CARD 1: DATA PROJECT -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[3px] border-t-emerald-600">
          <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-2.5 bg-slate-50/50">
            <FolderKanban class="w-4 h-4 text-slate-700" />
            <h2 class="font-bold text-slate-800 text-sm">Data Project</h2>
          </div>
          
          <div class="p-5 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">ID Project</label>
              <input
                :value="displayProjectId"
                type="text"
                disabled
                readonly
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-600 font-mono font-bold cursor-not-allowed focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Nama Project <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.projectName"
                type="text"
                required
                placeholder="Masukkan nama project / pekerjaan..."
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 text-slate-800"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi</label>
              <textarea
                v-model="form.description"
                rows="3"
                placeholder="Deskripsi ruang lingkup project..."
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 text-slate-800 resize-y"
              ></textarea>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Customer <span class="text-rose-500">*</span>
              </label>
              <div class="flex items-center gap-2">
                <select
                  v-model="form.customerId"
                  required
                  class="flex-1 px-3.5 py-2 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600"
                  @change="onCustomerChange"
                >
                  <option value="" disabled selected>Pilih Customer</option>
                  <option v-for="c in store.clients" :key="c.id" :value="c.id">
                    {{ c.name }} ({{ c.code }})
                  </option>
                </select>
                <button
                  type="button"
                  class="h-[38px] w-[38px] rounded-lg border border-emerald-500 hover:bg-emerald-50 flex items-center justify-center shrink-0 font-bold transition-colors shadow-2xs"
                  title="Tambah Customer"
                  @click="quickCustomerModalOpen = true"
                >
                  <Plus class="w-4 h-4 text-emerald-600 stroke-[2.5]" />
                </button>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Marketing <span class="text-rose-500">*</span>
              </label>
              <select
                v-model="form.marketingName"
                required
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600"
              >
                <option value="" disabled selected>Pilih Marketing</option>
                <option value="Budi Hartono, S.T.">Budi Hartono, S.T.</option>
                <option value="Ahmad Fauzi">Ahmad Fauzi</option>
                <option value="Rina Wijaya, S.E.">Rina Wijaya, S.E.</option>
                <option value="Staff Pemasaran Internal">Staff Pemasaran Internal</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Company <span class="text-rose-500">*</span>
              </label>
              <select
                v-model="form.company"
                required
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600"
                @change="updateDocNumbers"
              >
                <option value="" disabled selected>Pilih Company</option>
                <option value="CV Alpharo Artha Jaya">CV Alpharo Artha Jaya</option>
                <option value="PT Alpharo Multi Kreasi">PT Alpharo Multi Kreasi</option>
              </select>
            </div>
          </div>
        </div>

        <!-- CARD 2: DATA PENGADAAN -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[3px] border-t-emerald-600">
          <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-2.5 bg-slate-50/50">
            <ClipboardList class="w-4 h-4 text-slate-700" />
            <h2 class="font-bold text-slate-800 text-sm">Data Pengadaan</h2>
          </div>
          
          <div class="p-5 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Kegiatan</label>
              <input
                v-model="form.namaKegiatan"
                type="text"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 text-slate-800"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Rincian Kegiatan</label>
              <input
                v-model="form.rincianKegiatan"
                type="text"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 text-slate-800"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kode Rekening</label>
              <input
                type="text"
                value="52323628813 (Bank BCA Jakarta - a.n. CV ALPHARO ARTHA JAYA)"
                disabled
                readonly
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-700 font-mono font-bold cursor-not-allowed focus:outline-hidden"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tahun Anggaran</label>
                <input
                  v-model="form.tahunAnggaran"
                  type="text"
                  placeholder="2026"
                  class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 text-slate-800 font-mono"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sumber Dana</label>
                <select
                  v-model="form.sumberDana"
                  class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600"
                >
                  <option value="BOP">BOP</option>
                  <option value="BOS">BOS</option>
                  <option value="APBD">APBD</option>
                  <option value="Mandiri / Komite">Mandiri / Komite</option>
                  <option value="DAK Fisik">DAK Fisik</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kategori Barang/Jasa</label>
              <select
                v-model="form.kategori"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600"
              >
                <option value="Barang/Jasa">Barang/Jasa</option>
                <option value="Pengadaan Barang">Pengadaan Barang</option>
                <option value="Jasa Pemborongan/Konstruksi">Jasa Pemborongan/Konstruksi</option>
                <option value="Jasa Pemeliharaan">Jasa Pemeliharaan</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pagu Anggaran</label>
              <input
                v-model.number="form.paguAnggaran"
                type="number"
                min="0"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 text-slate-800 text-right font-mono"
              />
            </div>
          </div>
        </div>

        <!-- CARD 3: NILAI DAN JADWAL -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[3px] border-t-emerald-600">
          <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-2.5 bg-slate-50/50">
            <CalendarClock class="w-4 h-4 text-slate-700" />
            <h2 class="font-bold text-slate-800 text-sm">Nilai dan Jadwal</h2>
          </div>
          
          <div class="p-5 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Tgl Mulai <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.tglMulai"
                type="date"
                required
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 text-slate-800"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Estimasi Tgl Selesai <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.estimasiSelesai"
                type="date"
                required
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 text-slate-800"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nilai Project</label>
              <input
                v-model.number="form.nilaiProject"
                type="number"
                min="0"
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 text-slate-800 text-right font-mono font-bold"
                @input="onNilaiProjectChange"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nilai Invoice</label>
                <input
                  :value="form.nilaiInvoice"
                  type="number"
                  disabled
                  readonly
                  class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-600 text-right font-mono cursor-not-allowed"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Invoice Dibayar</label>
                <input
                  :value="form.invoiceDibayar"
                  type="number"
                  disabled
                  readonly
                  class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-600 text-right font-mono cursor-not-allowed"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- CARD 4: PENANGGUNG JAWAB DOKUMEN -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[3px] border-t-emerald-600">
          <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-2.5 bg-slate-50/50">
            <UserCheck class="w-4 h-4 text-slate-700" />
            <h2 class="font-bold text-slate-800 text-sm">Penanggung Jawab Dokumen</h2>
          </div>
          
          <div class="p-5 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Penanggung Jawab</label>
              <div class="flex items-center gap-2">
                <select
                  v-model="form.penanggungJawabSelection"
                  class="flex-1 px-3.5 py-2 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600"
                  @change="onPenanggungJawabSelect"
                >
                  <option value="" selected>Belum ada penanggung jawab aktif</option>
                  <option
                    v-for="(pj, idx) in availablePenanggungJawab"
                    :key="idx"
                    :value="pj.nama"
                  >
                    {{ pj.nama }} - {{ pj.jabatan }}
                  </option>
                </select>
                <button
                  type="button"
                  class="h-[38px] w-[38px] rounded-lg border border-emerald-500 hover:bg-emerald-50 flex items-center justify-center shrink-0 font-bold transition-colors shadow-2xs"
                  title="Tambah Penanggung Jawab"
                  @click="quickPjModalOpen = true"
                >
                  <Plus class="w-4 h-4 text-emerald-600 stroke-[2.5]" />
                </button>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kota Tanda Tangan</label>
              <input
                v-model="form.kotaTandaTangan"
                type="text"
                readonly
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-600 cursor-not-allowed select-none focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Penanggung Jawab</label>
              <input
                v-model="form.penanggungJawabNama"
                type="text"
                readonly
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-600 cursor-not-allowed select-none focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jabatan Penanggung Jawab</label>
              <input
                v-model="form.penanggungJawabJabatan"
                type="text"
                readonly
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-600 cursor-not-allowed select-none focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">NIP Penanggung Jawab</label>
              <input
                v-model="form.penanggungJawabNip"
                type="text"
                readonly
                class="w-full px-3.5 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-600 font-mono cursor-not-allowed select-none focus:outline-hidden"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- CARD 5: PREVIEW NOMOR DOKUMEN COMPANY (BOTTOM FULL-WIDTH CARD) -->
      <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[3px] border-t-emerald-600">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-2.5 bg-slate-50/50">
          <Hash class="w-4 h-4 text-slate-700" />
          <h2 class="font-bold text-slate-800 text-sm"># Preview Nomor Dokumen Company</h2>
        </div>

        <div class="p-5 space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Penawaran</label>
              <input
                v-model="form.docNumbers.penawaran"
                type="text"
                :placeholder="form.company ? '001/SPH/AAJ/X/2026' : 'Pilih company dahulu'"
                :disabled="!form.company"
                :class="[
                  !form.company ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white text-slate-800',
                  'w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 font-mono focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600'
                ]"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Surat Jalan</label>
              <input
                v-model="form.docNumbers.suratJalan"
                type="text"
                :placeholder="form.company ? '001/SJ/AAJ/X/2026' : 'Pilih company dahulu'"
                :disabled="!form.company"
                :class="[
                  !form.company ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white text-slate-800',
                  'w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 font-mono focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600'
                ]"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Faktur</label>
              <input
                v-model="form.docNumbers.faktur"
                type="text"
                :placeholder="form.company ? '001/FP/AAJ/X/2026' : 'Pilih company dahulu'"
                :disabled="!form.company"
                :class="[
                  !form.company ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white text-slate-800',
                  'w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 font-mono focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600'
                ]"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kuitansi</label>
              <input
                v-model="form.docNumbers.kuitansi"
                type="text"
                :placeholder="form.company ? '001/KW/AAJ/X/2026' : 'Pilih company dahulu'"
                :disabled="!form.company"
                :class="[
                  !form.company ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white text-slate-800',
                  'w-full px-3.5 py-2 text-xs rounded-lg border border-slate-300 font-mono focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600'
                ]"
              />
            </div>
          </div>

          <div class="flex items-start gap-1.5 text-xs text-slate-500 pt-1">
            <Info class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
            <p>
              Nomor dapat diubah. Saat Project disimpan, nomor akan dicadangkan dan dipakai oleh dokumen pertama pada masing-masing jenis.
            </p>
          </div>
        </div>
      </div>

      <!-- OPSIONAL: DAFTAR BARANG YANG DIPESAN (ACCORDION COLLAPSIBLE UNTUK INTEGRASI LOGISTIK & GUDANG) -->
      <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <button
          type="button"
          class="w-full px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 hover:bg-slate-100/50 transition-colors text-left"
          @click="showItemsSection = !showItemsSection"
        >
          <div class="flex items-center gap-2.5">
            <Boxes class="w-4 h-4 text-slate-700" />
            <div>
              <h2 class="font-bold text-slate-800 text-sm">Rincian Barang / Material Pengadaan (Opsional)</h2>
              <p class="text-[11px] text-slate-500">
                {{ form.items.length }} item barang terdaftar &bull; Total Kalkulasi: Rp {{ calculatedItemsTotal.toLocaleString('id-ID') }}
              </p>
            </div>
          </div>
          <ChevronDown :class="[showItemsSection ? 'rotate-180' : '', 'w-4 h-4 text-slate-500 transition-transform']" />
        </button>

        <div v-if="showItemsSection" class="p-5 space-y-4">
          <div class="flex items-center justify-between">
            <p class="text-xs text-slate-600">
              Pilih katalog barang gudang untuk pemenuhan logistik otomatis:
            </p>
            <button
              type="button"
              class="px-3 py-1.5 text-xs font-semibold bg-emerald-50 border border-emerald-300 text-emerald-700 hover:bg-emerald-100 rounded-lg flex items-center gap-1.5 transition-colors"
              @click="addItemRow"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Tambah Baris Barang</span>
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                  <th class="py-2.5 px-3">Produk / Material Gudang</th>
                  <th class="py-2.5 px-3 text-center w-28">Jumlah Qty</th>
                  <th class="py-2.5 px-3 text-right w-36">Harga Satuan (Rp)</th>
                  <th class="py-2.5 px-3 text-right w-40">Subtotal</th>
                  <th class="py-2.5 px-3 text-center w-12">Hapus</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(it, idx) in form.items" :key="idx" class="hover:bg-slate-50/50">
                  <td class="py-2.5 px-3 space-y-1">
                    <select
                      v-model="it.productId"
                      class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 bg-white"
                      @change="onProductSelect(it)"
                    >
                      <option :value="null">-- Non-Katalog / Kebutuhan Klien --</option>
                      <option v-for="p in store.products" :key="p.id" :value="p.id">
                        [{{ p.sku }}] {{ p.name }} (Stok: {{ p.stock }} {{ p.unit }})
                      </option>
                    </select>
                    <input
                      v-model="it.name"
                      type="text"
                      placeholder="Nama barang / spesifikasi..."
                      class="w-full px-2 py-1 text-xs rounded border border-slate-200 bg-slate-50/70 focus:bg-white text-slate-800 font-medium"
                    />
                    <div v-if="it.specs" class="text-[10px] text-amber-800 bg-amber-50/80 border border-amber-200/60 rounded px-1.5 py-0.5">
                      <strong>Permintaan Klien:</strong> {{ it.specs }}
                    </div>
                  </td>
                  <td class="py-2.5 px-3">
                    <input
                      v-model.number="it.qty"
                      type="number"
                      min="1"
                      class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 text-center font-bold"
                    />
                  </td>
                  <td class="py-2.5 px-3">
                    <input
                      v-model.number="it.price"
                      type="number"
                      min="0"
                      class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 text-right font-mono"
                    />
                  </td>
                  <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">
                    Rp {{ ((it.qty || 0) * (it.price || 0)).toLocaleString('id-ID') }}
                  </td>
                  <td class="py-2.5 px-3 text-center">
                    <button
                      type="button"
                      class="text-rose-500 hover:text-rose-700 p-1"
                      @click="removeItemRow(idx)"
                    >
                      <Trash2 class="w-4 h-4 mx-auto" />
                    </button>
                  </td>
                </tr>
                <tr v-if="form.items.length === 0">
                  <td colspan="5" class="py-6 text-center text-slate-400">
                    Belum ada item barang ditambahkan. (Opsional)
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs">
            <span class="text-slate-600">Gunakan total kalkulasi barang sebagai Nilai Project:</span>
            <button
              v-if="calculatedItemsTotal > 0"
              type="button"
              class="px-3 py-1 bg-white border border-slate-300 hover:bg-slate-100 rounded text-slate-700 font-semibold"
              @click="applyItemsTotalToProject"
            >
              Terapkan Rp {{ calculatedItemsTotal.toLocaleString('id-ID') }} ke Nilai Project
            </button>
          </div>
        </div>
      </div>

      <!-- ACTION BUTTONS (BATAL & SIMPAN SEPERTI GAMBAR) -->
      <div class="flex items-center justify-end gap-3 pt-4">
        <router-link
          to="/marketing/orders/monitoring"
          class="px-5 py-2.5 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg transition-colors shadow-2xs"
        >
          Batal
        </router-link>

        <button
          type="submit"
          class="px-6 py-2.5 text-xs font-bold text-white bg-[#059669] hover:bg-emerald-700 rounded-lg shadow-sm transition-all flex items-center gap-2"
        >
          <CheckSquare class="w-4 h-4" />
          <span>Simpan</span>
        </button>
      </div>
    </form>

    <!-- TAMBAH CUSTOMER MODAL (PERSIS SESUAI GAMBAR TANGKAPAN LAYAR) -->
    <CustomerFormModal
      :show="quickCustomerModalOpen"
      @close="quickCustomerModalOpen = false"
      @saved="onCustomerCreated"
    />

    <!-- QUICK ADD PENANGGUNG JAWAB MODAL -->
    <Modal
      :show="quickPjModalOpen"
      title="Tambah Penanggung Jawab Baru"
      size="md"
      @close="quickPjModalOpen = false"
    >
      <form class="space-y-4 text-xs" @submit.prevent="saveQuickPj">
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Nama Penanggung Jawab *</label>
          <input
            v-model="quickPj.nama"
            type="text"
            required
            placeholder="Nama lengkap beserta gelar..."
            class="w-full px-3 py-2 rounded-lg border border-slate-300"
          />
        </div>
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Jabatan *</label>
          <input
            v-model="quickPj.jabatan"
            type="text"
            required
            placeholder="Kepala Sekolah / Pejabat Pengadaan / KPA"
            class="w-full px-3 py-2 rounded-lg border border-slate-300"
          />
        </div>
        <div>
          <label class="block font-semibold text-slate-700 mb-1">NIP</label>
          <input
            v-model="quickPj.nip"
            type="text"
            placeholder="197xxxx xxxxx xx x"
            class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono"
          />
        </div>
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Kota Tanda Tangan</label>
          <input
            v-model="quickPj.kota"
            type="text"
            placeholder="Jakarta Timur"
            class="w-full px-3 py-2 rounded-lg border border-slate-300"
          />
        </div>
        <div class="pt-2 flex justify-end gap-2">
          <button
            type="button"
            class="px-3 py-2 border rounded-lg text-slate-600 hover:bg-slate-50"
            @click="quickPjModalOpen = false"
          >
            Batal
          </button>
          <button
            type="submit"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg"
          >
            Gunakan Penanggung Jawab
          </button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import AppLayout from '../../components/layout/AppLayout.vue';
import Modal from '../../components/common/Modal.vue';
import CustomerFormModal from '../../components/common/CustomerFormModal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import {
  ArrowLeft,
  FolderKanban,
  ClipboardList,
  CalendarClock,
  UserCheck,
  Hash,
  Boxes,
  Plus,
  Trash2,
  ChevronDown,
  Info,
  CheckSquare,
} from 'lucide-vue-next';

const router = useRouter();
const route = useRoute();
const toast = useToast();

const isEdit = computed(() => Boolean(route.params.id));
const showItemsSection = ref(false);

const quickCustomerModalOpen = ref(false);
const quickCustomer = ref({
  name: '',
  contactPerson: '',
  phone: '',
  city: 'Jakarta Timur',
});

const quickPjModalOpen = ref(false);
const quickPj = ref({
  nama: '',
  jabatan: '',
  nip: '',
  kota: 'Jakarta Timur',
});

const fromClientOrder = ref(null);

// Dynamic next project ID
const displayProjectId = computed(() => {
  if (isEdit.value && form.value.projectId) {
    return form.value.projectId;
  }
  const nextNum = (store.orders?.length || 0) + 1;
  return `PR${String(nextNum).padStart(5, '0')}`;
});


const todayDate = new Date().toISOString().split('T')[0];

const form = ref({
  id: null,
  fromOrderId: null,
  projectId: '',
  projectName: '',
  description: '',
  customerId: '',
  marketingName: '',
  company: '',
  namaKegiatan: '',
  rincianKegiatan: '',
  kodeRekening: '',
  tahunAnggaran: '2026',
  sumberDana: 'BOP',
  kategori: 'Barang/Jasa',
  paguAnggaran: 0,
  tglMulai: todayDate,
  estimasiSelesai: todayDate,
  nilaiProject: 0,
  nilaiInvoice: 0,
  invoiceDibayar: 0,
  penanggungJawabSelection: '',
  penanggungJawabNama: '',
  penanggungJawabJabatan: '',
  penanggungJawabNip: '',
  kotaTandaTangan: '',
  docNumbers: {
    penawaran: '',
    suratJalan: '',
    faktur: '',
    kuitansi: '',
  },
  items: [],
});

// Available Penanggung Jawab list
const availablePenanggungJawab = ref([
  {
    nama: 'Bpk. supri, M.Pd.',
    jabatan: 'Kepala Sekolah / Kuasa Pengguna Anggaran',
    nip: '19720815 199803 1 004',
    kota: 'Jakarta Timur',
  },
  {
    nama: 'Bu Nis Kepsek, M.Si.',
    jabatan: 'Kepala Sekolah / Kuasa Pengguna Anggaran',
    nip: '19740512 200003 2 003',
    kota: 'Jakarta Timur',
  },
  {
    nama: 'Ibu Shifa, S.Pd.',
    jabatan: 'Kepala Sekolah / KPA',
    nip: '19810920 200604 2 015',
    kota: 'Jakarta Timur',
  },
  {
    nama: 'Bu Murti, M.Pd.',
    jabatan: 'Kepala Sekolah / KPA',
    nip: '19730419 199903 2 002',
    kota: 'Jakarta Pusat',
  },
  {
    nama: 'Bu Eka Kepsek, M.M.',
    jabatan: 'Kepala Sekolah / KPA',
    nip: '19751108 200112 2 001',
    kota: 'Jakarta Timur',
  },
]);

const calculatedItemsTotal = computed(() => {
  return form.value.items.reduce((sum, it) => sum + (it.qty || 0) * (it.price || 0), 0);
});

const onNilaiProjectChange = () => {
  // If user adjusts Nilai Project, auto sync invoice calculations
  form.value.nilaiInvoice = form.value.nilaiProject;
};

const applyItemsTotalToProject = () => {
  form.value.nilaiProject = calculatedItemsTotal.value;
  form.value.nilaiInvoice = calculatedItemsTotal.value;
  form.value.paguAnggaran = calculatedItemsTotal.value;
  toast.info(`Nilai Project diperbarui: Rp ${calculatedItemsTotal.value.toLocaleString('id-ID')}`);
};

const onCustomerChange = () => {
  const client = store.clients.find((c) => c.id === Number(form.value.customerId));
  if (client) {
    form.value.kotaTandaTangan = client.city || 'Jakarta Timur';
    if (client.contactPerson) {
      form.value.penanggungJawabSelection = client.contactPerson;
      form.value.penanggungJawabNama = client.contactPerson;
      form.value.penanggungJawabJabatan = 'Kepala Sekolah / Kuasa Pengguna Anggaran';
      form.value.penanggungJawabNip = '19750101 200212 1 005';
    }
  }
};

const onPenanggungJawabSelect = () => {
  const match = availablePenanggungJawab.value.find((pj) => pj.nama === form.value.penanggungJawabSelection);
  if (match) {
    form.value.penanggungJawabNama = match.nama;
    form.value.penanggungJawabJabatan = match.jabatan;
    form.value.penanggungJawabNip = match.nip;
    form.value.kotaTandaTangan = match.kota;
  }
};

const updateDocNumbers = () => {
  if (!form.value.company) {
    form.value.docNumbers = {
      penawaran: '',
      suratJalan: '',
      faktur: '',
      kuitansi: '',
    };
    return;
  }

  const nextNum = String((store.orders?.length || 0) + 1).padStart(3, '0');
  const romanMonth = 'X';
  const year = '2026';

  form.value.docNumbers = {
    penawaran: `${nextNum}/SPH/AAJ/${romanMonth}/${year}`,
    suratJalan: `${nextNum}/SJ/AAJ/${romanMonth}/${year}`,
    faktur: `${nextNum}/FP/AAJ/${romanMonth}/${year}`,
    kuitansi: `${nextNum}/KW/AAJ/${romanMonth}/${year}`,
  };
};

const addItemRow = () => {
  form.value.items.push({
    productId: '',
    name: 'Item Baru',
    sku: 'SKU-NEW',
    qty: 1,
    unit: 'Unit',
    price: 0,
  });
};

const removeItemRow = (idx) => {
  form.value.items.splice(idx, 1);
};

const onProductSelect = (it) => {
  const prod = store.products.find((p) => p.id === Number(it.productId));
  if (prod) {
    it.name = prod.name;
    it.sku = prod.sku;
    it.unit = prod.unit;
    it.price = prod.sell_price || prod.price || 0;
  }
};

const onCustomerCreated = (newClient) => {
  form.value.customerId = newClient.id;
  onCustomerChange();
  toast.success(`Customer ${newClient.name} terpilih otomatis!`);
};

const saveQuickPj = () => {
  const newPj = {
    nama: quickPj.value.nama,
    jabatan: quickPj.value.jabatan,
    nip: quickPj.value.nip || '-',
    kota: quickPj.value.kota || 'Jakarta Timur',
  };
  availablePenanggungJawab.value.unshift(newPj);
  form.value.penanggungJawabSelection = newPj.nama;
  form.value.penanggungJawabNama = newPj.nama;
  form.value.penanggungJawabJabatan = newPj.jabatan;
  form.value.penanggungJawabNip = newPj.nip;
  form.value.kotaTandaTangan = newPj.kota;
  quickPjModalOpen.value = false;
  toast.success('Penanggung jawab berhasil ditambahkan!');
};

const initForm = () => {
  if (isEdit.value) {
    const existing = store.orders.find((o) => o.id === Number(route.params.id));
    if (existing) {
      form.value = {
        ...form.value,
        id: existing.id,
        projectId: existing.projectId || `PR${String(existing.id).padStart(5, '0')}`,
        projectName: existing.projectName || '',
        description: existing.description || '',
        customerId: existing.clientId || '',
        marketingName: existing.submittedBy || store.currentUser?.name || 'Budi Hartono, S.T.',
        company: existing.company || 'CV Alpharo Artha Jaya',
        namaKegiatan: existing.namaKegiatan || existing.projectName || '',
        rincianKegiatan: existing.rincianKegiatan || '',
        kodeRekening: existing.kodeRekening || '52323628813',
        tahunAnggaran: existing.tahunAnggaran || '2026',
        sumberDana: existing.sumberDana || 'BOP',
        kategori: existing.kategori || 'Barang/Jasa',
        paguAnggaran: existing.paguAnggaran || existing.grandTotal || 0,
        tglMulai: existing.date || todayDate,
        estimasiSelesai: existing.estimasiSelesai || todayDate,
        nilaiProject: existing.grandTotal || 0,
        nilaiInvoice: existing.nilaiInvoice || existing.grandTotal || 0,
        invoiceDibayar: existing.invoiceDibayar || 0,
        penanggungJawabSelection: existing.penanggungJawabNama || '',
        penanggungJawabNama: existing.penanggungJawabNama || '',
        penanggungJawabJabatan: existing.penanggungJawabJabatan || '',
        penanggungJawabNip: existing.penanggungJawabNip || '',
        kotaTandaTangan: existing.kotaTandaTangan || '',
        docNumbers: existing.docNumbers || {
          penawaran: `00${existing.id}/SPH/AAJ/X/2026`,
          suratJalan: `00${existing.id}/SJ/AAJ/X/2026`,
          faktur: `00${existing.id}/FP/AAJ/X/2026`,
          kuitansi: `00${existing.id}/KW/AAJ/X/2026`,
        },
        items: existing.items && existing.items.length ? JSON.parse(JSON.stringify(existing.items)) : [],
      };
      updateDocNumbers();
      if (form.value.items.length > 0) {
        showItemsSection.value = true;
      }
    }
  } else if (route.query.fromOrderId) {
    const target = String(route.query.fromOrderId).trim();
    const fromOrder = store.orders.find(
      (o) =>
        String(o.id) === target ||
        (o.orderNumber && o.orderNumber.trim().toUpperCase() === target.toUpperCase())
    );
    if (fromOrder) {
      fromClientOrder.value = fromOrder;
      form.value.fromOrderId = fromOrder.id;
      form.value.projectId = displayProjectId.value;
      form.value.projectName = fromOrder.projectName || '';
      form.value.description = fromOrder.notes || `Pengajuan kebutuhan dari rekanan ${fromOrder.clientName}`;
      form.value.customerId = fromOrder.clientId || '';

      // Fallback: match by client name if clientId wasn't directly found
      if (!form.value.customerId && fromOrder.clientName) {
        const foundClient = store.clients.find(
          (c) =>
            c.name.toLowerCase() === fromOrder.clientName.toLowerCase() ||
            c.name.toLowerCase().includes(fromOrder.clientName.toLowerCase()) ||
            fromOrder.clientName.toLowerCase().includes(c.name.toLowerCase())
        );
        if (foundClient) {
          form.value.customerId = foundClient.id;
        }
      }

      form.value.marketingName = store.currentUser?.name || 'Budi Hartono, S.T.';
      form.value.company = fromOrder.company || 'CV Alpharo Artha Jaya';
      form.value.namaKegiatan = fromOrder.projectName || '';
      form.value.rincianKegiatan = fromOrder.notes || '';
      form.value.kodeRekening = '52323628813';
      form.value.tahunAnggaran = '2026';
      form.value.sumberDana = 'BOP';
      form.value.kategori = 'Barang/Jasa';
      form.value.paguAnggaran = fromOrder.grandTotal || 0;
      form.value.nilaiProject = fromOrder.grandTotal || fromOrder.subtotal || 0;
      form.value.nilaiInvoice = fromOrder.grandTotal || 0;
      form.value.invoiceDibayar = 0;
      form.value.items = fromOrder.items && fromOrder.items.length ? JSON.parse(JSON.stringify(fromOrder.items)) : [];
      showItemsSection.value = true;
      updateDocNumbers();
      onCustomerChange();
      if (fromOrder.contactPerson) {
        form.value.penanggungJawabSelection = fromOrder.contactPerson;
        form.value.penanggungJawabNama = fromOrder.contactPerson;
        form.value.penanggungJawabJabatan = 'Kepala Sekolah / Kuasa Pengguna Anggaran';
        form.value.penanggungJawabNip = '19750101 200212 1 005';
      }
    }
  } else {
    form.value.projectId = displayProjectId.value;
    form.value.marketingName = '';
    form.value.company = '';
    form.value.docNumbers = {
      penawaran: '',
      suratJalan: '',
      faktur: '',
      kuitansi: '',
    };
    form.value.items = [];
  }
};

onMounted(initForm);
watch(() => [route.params.id, route.query.fromOrderId], initForm);

const handleSubmit = () => {
  if (!form.value.customerId) {
    toast.error('Silakan pilih Customer terlebih dahulu.');
    return;
  }
  if (!form.value.projectName) {
    toast.error('Nama Project wajib diisi.');
    return;
  }

  const client = store.clients.find((c) => c.id === Number(form.value.customerId)) || { name: 'Customer' };

  // Final items from form
  const finalItems = form.value.items.length > 0 && form.value.items[0].name
    ? form.value.items
    : [
        {
          productId: 1,
          name: form.value.projectName || 'Pengadaan Paket Barang & Jasa',
          sku: 'PKG-PRJ',
          qty: 1,
          unit: 'Paket',
          price: form.value.nilaiProject || 10000000,
        },
      ];

  const payload = {
    projectId: displayProjectId.value,
    clientId: Number(form.value.customerId),
    projectName: form.value.projectName,
    description: form.value.description,
    company: form.value.company || 'CV Alpharo Artha Jaya',
    marketingName: form.value.marketingName || store.currentUser?.name,
    namaKegiatan: form.value.namaKegiatan,
    rincianKegiatan: form.value.rincianKegiatan,
    kodeRekening: '52323628813',
    nomorRekening: '52323628813',
    bankPenerima: 'Bank BCA Jakarta (a.n. CV ALPHARO ARTHA JAYA)',
    tahunAnggaran: form.value.tahunAnggaran,
    sumberDana: form.value.sumberDana,
    kategori: form.value.kategori,
    paguAnggaran: form.value.paguAnggaran,
    tglMulai: form.value.tglMulai,
    estimasiSelesai: form.value.estimasiSelesai,
    nilaiProject: form.value.nilaiProject,
    nilaiInvoice: form.value.nilaiInvoice,
    invoiceDibayar: form.value.invoiceDibayar,
    penanggungJawabNama: form.value.penanggungJawabNama,
    penanggungJawabJabatan: form.value.penanggungJawabJabatan,
    penanggungJawabNip: form.value.penanggungJawabNip,
    kotaTandaTangan: form.value.kotaTandaTangan,
    docNumbers: form.value.docNumbers,
    submitDirectly: false, // Disimpan sebagai project / order
    items: finalItems,
  };

  if (isEdit.value && form.value.id) {
    const idx = store.orders.findIndex((o) => o.id === form.value.id);
    if (idx !== -1) {
      store.orders[idx] = {
        ...store.orders[idx],
        ...payload,
        clientName: client.name,
        grandTotal: form.value.nilaiProject || payload.nilaiProject,
      };
      toast.success(`Data Project ${displayProjectId.value} berhasil diperbarui!`);
    }
  } else if (form.value.fromOrderId) {
    const target = String(form.value.fromOrderId).trim();
    const existingIdx = store.orders.findIndex(
      (o) =>
        String(o.id) === target ||
        (o.orderNumber && o.orderNumber.trim().toUpperCase() === target.toUpperCase())
    );
    if (existingIdx !== -1) {
      store.orders[existingIdx] = {
        ...store.orders[existingIdx],
        ...payload,
        clientName: client.name,
        status: 'Menunggu Verifikasi', // Langsung siap telaah Direktur setelah dirinci marketing!
        grandTotal: form.value.nilaiProject || payload.nilaiProject,
      };
      const alreadyHasTimeline = store.orders[existingIdx].timeline.some((t) =>
        t.stage.includes('Spesifikasi Project')
      );
      if (!alreadyHasTimeline) {
        store.orders[existingIdx].timeline.push({
          stage: 'Spesifikasi Project & Dokumen Lengkap',
          user: store.currentUser.name,
          timestamp: new Date().toLocaleString('id-ID'),
          status: 'completed',
          notes: `Marketing merinci data pengadaan, jadwal, penanggung jawab, dan nomor dokumen resmi (${displayProjectId.value}). Diteruskan ke Direktur.`,
        });
      }
      storeActions.addNotification(
        'DIREKTUR',
        'Verifikasi Order Spesifik Siap Telaah',
        `Marketing telah merinci spesifikasi lengkap order ${store.orders[existingIdx].orderNumber} (${client.name}). Silakan telaah & verifikasi.`,
        'order',
        '/director/verification'
      );
      toast.success(`Order dari ${client.name} berhasil dirinci dan diteruskan ke Direktur!`);
      router.push('/marketing/orders/monitoring');
      return;
    }
  } else {
    const newOrder = storeActions.createOrder(payload);
    Object.assign(newOrder, {
      projectId: displayProjectId.value,
      company: form.value.company || 'CV Alpharo Artha Jaya',
      namaKegiatan: form.value.namaKegiatan,
      rincianKegiatan: form.value.rincianKegiatan,
      kodeRekening: '52323628813',
      nomorRekening: '52323628813',
      bankPenerima: 'Bank BCA Jakarta (a.n. CV ALPHARO ARTHA JAYA)',
      tahunAnggaran: form.value.tahunAnggaran,
      sumberDana: form.value.sumberDana,
      kategori: form.value.kategori,
      paguAnggaran: form.value.paguAnggaran,
      tglMulai: form.value.tglMulai,
      estimasiSelesai: form.value.estimasiSelesai,
      penanggungJawabNama: form.value.penanggungJawabNama,
      penanggungJawabJabatan: form.value.penanggungJawabJabatan,
      penanggungJawabNip: form.value.penanggungJawabNip,
      kotaTandaTangan: form.value.kotaTandaTangan,
      docNumbers: form.value.docNumbers,
    });
    if (form.value.nilaiProject > 0) {
      newOrder.grandTotal = form.value.nilaiProject;
    }
    toast.success(`Project ${displayProjectId.value} (${form.value.projectName}) berhasil disimpan!`);
  }

  router.push('/marketing/orders/monitoring');
};
</script>
