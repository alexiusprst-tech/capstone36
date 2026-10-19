<template>
  <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-800">
    <!-- PUBLIC HEADER -->
    <header class="bg-gradient-to-r from-[#0B4DA2] via-[#09428F] to-[#062F69] text-white shadow-md sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <AlpharoLogo theme="white" size="md" :show-tagline="true" />
          <div class="hidden md:block pl-4 border-l border-white/20">
            <span class="text-xs font-bold text-amber-300 uppercase tracking-widest block">Portal Publik Rekanan</span>
            <span class="text-[11px] text-blue-100">Layanan Mandiri Pengadaan, Logistik & Pembayaran</span>
          </div>
        </div>

        <!-- QUICK TAB SWITCHERS IN HEADER (DESKTOP) -->
        <nav class="hidden lg:flex items-center gap-1 bg-white/10 p-1 rounded-xl backdrop-blur-xs border border-white/10 text-xs font-semibold">
          <button
            type="button"
            :class="[
              activeTab === 'request'
                ? 'bg-amber-400 text-slate-950 font-bold shadow-xs'
                : 'text-white/90 hover:text-white hover:bg-white/10',
              'px-3.5 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1.5'
            ]"
            @click="setTab('request')"
          >
            <ClipboardCheck class="w-3.5 h-3.5" />
            <span>Ajukan Kebutuhan</span>
          </button>

          <button
            type="button"
            :class="[
              activeTab === 'tracking'
                ? 'bg-amber-400 text-slate-950 font-bold shadow-xs'
                : 'text-white/90 hover:text-white hover:bg-white/10',
              'px-3.5 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1.5'
            ]"
            @click="setTab('tracking')"
          >
            <Truck class="w-3.5 h-3.5" />
            <span>Lacak Resi & BAST</span>
          </button>

          <button
            type="button"
            :class="[
              activeTab === 'payment'
                ? 'bg-amber-400 text-slate-950 font-bold shadow-xs'
                : 'text-white/90 hover:text-white hover:bg-white/10',
              'px-3.5 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1.5'
            ]"
            @click="setTab('payment')"
          >
            <CreditCard class="w-3.5 h-3.5" />
            <span>Pembayaran & Termin</span>
          </button>
        </nav>

        <div class="flex items-center gap-3">
          <router-link
            to="/login"
            class="inline-flex items-center gap-2 px-3.5 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg text-xs font-semibold backdrop-blur-xs transition-colors border border-white/15"
          >
            <LogIn class="w-3.5 h-3.5 text-amber-300" />
            <span>Login Staff Internal</span>
          </router-link>
        </div>
      </div>
    </header>

    <!-- HERO SECTION -->
    <section class="bg-white border-b border-slate-200/90 py-8 px-4 sm:px-6 lg:px-8">
      <div class="max-w-5xl mx-auto text-center space-y-3">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-[#0B4DA2] border border-blue-200">
          <Sparkles class="w-3.5 h-3.5 text-amber-500" />
          Portal Terpadu Rekanan & Sekolah DKI Jakarta
        </span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
          Layanan Pengadaan Mandiri, Pelacakan Pengiriman & Pembayaran
        </h1>
        <p class="text-xs sm:text-sm text-slate-600 max-w-3xl mx-auto leading-relaxed">
          Satu portal publik untuk seluruh interaksi rekanan sekolah & instansi bersama CV Alpharo Artha Jaya. Ajukan pesanan sarpras terhubung katalog 2.196 SKU, pantau status logistik ekspedisi & BAST, serta kelola konfirmasi pembayaran termin secara transparan.
        </p>

        <!-- 3 MAIN NAVIGATION TABS -->
        <div class="pt-3 grid grid-cols-1 sm:grid-cols-3 gap-3 max-w-3xl mx-auto">
          <button
            type="button"
            :class="[
              activeTab === 'request'
                ? 'bg-gradient-to-r from-[#0B4DA2] to-[#062F69] text-white shadow-md shadow-blue-900/20 ring-2 ring-[#0B4DA2]/20'
                : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200/80',
              'p-3.5 rounded-2xl text-left transition-all cursor-pointer flex items-center gap-3'
            ]"
            @click="setTab('request')"
          >
            <div
              :class="[
                activeTab === 'request' ? 'bg-white/20 text-amber-300' : 'bg-blue-50 text-[#0B4DA2]',
                'w-10 h-10 rounded-xl flex items-center justify-center shrink-0'
              ]"
            >
              <ClipboardCheck class="w-5 h-5" />
            </div>
            <div>
              <p class="text-xs sm:text-sm font-bold leading-tight">1. Ajukan Kebutuhan</p>
              <p class="text-[11px] opacity-80 mt-0.5 leading-tight">Katalog 2.196 SKU & Custom</p>
            </div>
          </button>

          <button
            type="button"
            :class="[
              activeTab === 'tracking'
                ? 'bg-gradient-to-r from-[#0B4DA2] to-[#062F69] text-white shadow-md shadow-blue-900/20 ring-2 ring-[#0B4DA2]/20'
                : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200/80',
              'p-3.5 rounded-2xl text-left transition-all cursor-pointer flex items-center gap-3'
            ]"
            @click="setTab('tracking')"
          >
            <div
              :class="[
                activeTab === 'tracking' ? 'bg-white/20 text-amber-300' : 'bg-emerald-50 text-emerald-600',
                'w-10 h-10 rounded-xl flex items-center justify-center shrink-0'
              ]"
            >
              <Truck class="w-5 h-5" />
            </div>
            <div>
              <p class="text-xs sm:text-sm font-bold leading-tight">2. Lacak Resi & BAST</p>
              <p class="text-[11px] opacity-80 mt-0.5 leading-tight">Status Logistik & Ekspedisi</p>
            </div>
          </button>

          <button
            type="button"
            :class="[
              activeTab === 'payment'
                ? 'bg-gradient-to-r from-[#0B4DA2] to-[#062F69] text-white shadow-md shadow-blue-900/20 ring-2 ring-[#0B4DA2]/20'
                : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200/80',
              'p-3.5 rounded-2xl text-left transition-all cursor-pointer flex items-center gap-3'
            ]"
            @click="setTab('payment')"
          >
            <div
              :class="[
                activeTab === 'payment' ? 'bg-white/20 text-amber-300' : 'bg-amber-50 text-amber-600',
                'w-10 h-10 rounded-xl flex items-center justify-center shrink-0'
              ]"
            >
              <CreditCard class="w-5 h-5" />
            </div>
            <div>
              <p class="text-xs sm:text-sm font-bold leading-tight">3. Pembayaran & Termin</p>
              <p class="text-[11px] opacity-80 mt-0.5 leading-tight">Rekening Resmi & Tagihan</p>
            </div>
          </button>
        </div>
      </div>
    </section>

    <!-- CONTENT BODY -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8">
      
      <!-- ============================================================== -->
      <!-- TAB 1: AJUKAN KEBUTUHAN BARANG (PENGADAAN MANDIRI REKANAN) -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'request'" class="space-y-6">
        <!-- ALUR PROSES BANNER -->
        <div class="bg-gradient-to-r from-blue-50 via-indigo-50 to-sky-50 border border-blue-200/90 rounded-2xl p-4 sm:p-5 shadow-2xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#0B4DA2] text-white flex items-center justify-center shrink-0 shadow-xs">
              <ClipboardCheck class="w-5 h-5 text-amber-300" />
            </div>
            <div>
              <h2 class="text-xs sm:text-sm font-bold text-slate-900">Alur Pengadaan Mandiri Rekanan & Sekolah</h2>
              <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">
                1. Ajukan Kebutuhan &rarr; 2. Verifikasi Stok & Penawaran (Marketing) &rarr; 3. Approval Direktur & SPJ &rarr; 4. Pengiriman Gudang &rarr; 5. Konfirmasi BAST & Pembayaran Termin.
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2 text-xs font-medium text-slate-500 shrink-0">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white border border-blue-200 rounded-full text-[11px] font-bold text-[#0B4DA2] shadow-2xs">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              2.196 SKU Katalog Terhubung
            </span>
          </div>
        </div>

        <!-- MAIN PROCUREMENT REQUEST FORM -->
        <form class="space-y-6" @submit.prevent="handleSubmitRequest">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            
            <!-- CARD 1: DATA INSTANSI & PEMOHON -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[4px] border-t-[#0B4DA2]">
              <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2">
                  <School class="w-4 h-4 text-[#0B4DA2]" />
                  <h3 class="font-bold text-slate-800 text-sm">1. Data Instansi & Pemohon</h3>
                </div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Identitas PIC</span>
              </div>

              <div class="p-5 space-y-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Nama Sekolah / Instansi Rekanan <span class="text-rose-500">*</span>
                  </label>
                  <select
                    v-model="form.clientId"
                    required
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-[#0B4DA2]/20 focus:border-[#0B4DA2]"
                    @change="onClientSelect"
                  >
                    <option value="" disabled selected>Pilih Instansi Rekanan Terdaftar</option>
                    <option v-for="c in store.clients" :key="c.id" :value="c.id">
                      {{ c.name }} — {{ c.code }} ({{ c.city }})
                    </option>
                  </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                      Nama PIC / Penanggung Jawab <span class="text-rose-500">*</span>
                    </label>
                    <input
                      v-model="form.contactPerson"
                      type="text"
                      required
                      placeholder="Nama lengkap PIC pemesan..."
                      class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-[#0B4DA2]/20 focus:border-[#0B4DA2]"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                      Nomor WhatsApp / HP <span class="text-rose-500">*</span>
                    </label>
                    <input
                      v-model="form.phone"
                      type="text"
                      required
                      placeholder="0812-xxxx-xxxx"
                      class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-[#0B4DA2]/20 focus:border-[#0B4DA2]"
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Email Resmi Instansi / PIC <span class="text-rose-500">*</span>
                  </label>
                  <input
                    v-model="form.email"
                    type="email"
                    required
                    placeholder="email@sekolah.sch.id"
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-[#0B4DA2]/20 focus:border-[#0B4DA2]"
                  />
                </div>

                <!-- WILAYAH DKI JAKARTA CASCADER -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kota Administrasi</label>
                    <select
                      v-model="form.city"
                      class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-300 bg-white"
                      @change="onCityChange"
                    >
                      <option value="">Pilih Kota</option>
                      <option v-for="kota in availableCities" :key="kota" :value="kota">
                        {{ kota }}
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kecamatan</label>
                    <select
                      v-model="form.district"
                      :disabled="!form.city"
                      class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-300 bg-white disabled:bg-slate-100"
                      @change="onDistrictChange"
                    >
                      <option value="">Pilih Kecamatan</option>
                      <option v-for="kec in availableDistricts" :key="kec" :value="kec">
                        {{ kec }}
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kelurahan</label>
                    <select
                      v-model="form.subdistrict"
                      :disabled="!form.district"
                      class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-300 bg-white disabled:bg-slate-100"
                    >
                      <option value="">Pilih Kelurahan</option>
                      <option v-for="kel in availableSubdistricts" :key="kel" :value="kel">
                        {{ kel }}
                      </option>
                    </select>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Alamat Lengkap Lokasi Pengiriman <span class="text-rose-500">*</span>
                  </label>
                  <textarea
                    v-model="form.address"
                    rows="2"
                    required
                    placeholder="Jl. ..., No. Gedung, RT/RW, Patokan..."
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-[#0B4DA2]/20 focus:border-[#0B4DA2]"
                  ></textarea>
                </div>
              </div>
            </div>

            <!-- CARD 2: INFORMASI PROYEK & RENCANA PENGADAAN -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[4px] border-t-amber-500">
              <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2">
                  <FolderKanban class="w-4 h-4 text-amber-600" />
                  <h3 class="font-bold text-slate-800 text-sm">2. Ruang Lingkup & Waktu Kebutuhan</h3>
                </div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Target Waktu</span>
              </div>

              <div class="p-5 space-y-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Judul Pengadaan / Nama Kebutuhan <span class="text-rose-500">*</span>
                  </label>
                  <input
                    v-model="form.projectName"
                    type="text"
                    required
                    placeholder="Contoh: Pengadaan Meja Kursi Belajar & Papan Tulis Kelas TA 2026"
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 font-medium text-slate-800"
                  />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                      Kategori Pengadaan <span class="text-rose-500">*</span>
                    </label>
                    <select
                      v-model="form.category"
                      required
                      class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white"
                    >
                      <option value="Sarana & Fasilitas Belajar">Sarana & Fasilitas Belajar</option>
                      <option value="Alat Laboratorium & Praktik">Alat Laboratorium & Praktik</option>
                      <option value="Perlengkapan Kantor & ATK">Perlengkapan Kantor & ATK</option>
                      <option value="Material & Konstruksi Renovasi">Material & Konstruksi Renovasi</option>
                      <option value="Elektronik & Multimedia Kelas">Elektronik & Multimedia Kelas</option>
                      <option value="Lainnya">Lainnya</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                      Tingkat Urgensi Kebutuhan <span class="text-rose-500">*</span>
                    </label>
                    <select
                      v-model="form.urgency"
                      required
                      class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white"
                    >
                      <option value="Normal">Normal (1-2 Minggu)</option>
                      <option value="Prioritas">Prioritas (3-5 Hari)</option>
                      <option value="Mendesak">Mendesak (&le; 48 Jam)</option>
                    </select>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                      Target Tanggal Dibutuhkan / Tiba
                    </label>
                    <input
                      v-model="form.targetDeliveryDate"
                      type="date"
                      class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white"
                    />
                  </div>

                  <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                      Sumber Anggaran (Opsional)
                    </label>
                    <input
                      v-model="form.fundingSource"
                      type="text"
                      placeholder="Contoh: BOS Reguler / APBD / Yayasan"
                      class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300"
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Uraian Latar Belakang / Kebutuhan
                  </label>
                  <textarea
                    v-model="form.description"
                    rows="3"
                    placeholder="Jelaskan kebutuhan pengadaan sarpras atau spesifikasi umum ruangan yang akan dipasang..."
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300"
                  ></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- CARD 3: DAFTAR BARANG YANG DIAJUKAN (INTERACTIVE ITEM PICKER) -->
          <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[4px] border-t-emerald-600">
            <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
              <div>
                <div class="flex items-center gap-2">
                  <Boxes class="w-4 h-4 text-emerald-600" />
                  <h3 class="font-bold text-slate-800 text-sm">3. Rincian Barang Kebutuhan yang Diajukan</h3>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                  Pilih item dari master katalog resmi CV Alpharo (2.196 SKU) atau tambahkan spesifikasi khusus instansi Anda.
                </p>
              </div>

              <!-- DUAL ACTION BUTTONS (DENGAN GRADASI ICON PUTIH SESUAI DESIGN GUIDELINE) -->
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 px-3 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl text-xs font-semibold shadow-xs transition-all cursor-pointer"
                  @click="openCatalogModal"
                >
                  <Search class="w-3.5 h-3.5 text-white stroke-[2.5]" />
                  <span>Pilih dari Master Katalog</span>
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 px-3 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-xs font-semibold shadow-xs transition-all cursor-pointer"
                  @click="openCustomItemModal"
                >
                  <Plus class="w-3.5 h-3.5 text-white stroke-[2.5]" />
                  <span>Tambah Item Custom</span>
                </button>
              </div>
            </div>

            <div class="p-5">
              <!-- ITEMS TABLE -->
              <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                <div class="overflow-x-auto">
                  <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                      <tr>
                        <th class="py-2.5 px-3 w-10 text-center">No</th>
                        <th class="py-2.5 px-3 min-w-[200px]">Nama Barang & SKU</th>
                        <th class="py-2.5 px-3 min-w-[180px]">Spesifikasi / Catatan</th>
                        <th class="py-2.5 px-3 w-28 text-center">Jumlah (Qty)</th>
                        <th class="py-2.5 px-3 w-24 text-center">Satuan</th>
                        <th class="py-2.5 px-3 w-32 text-right">Est. Harga Satuan</th>
                        <th class="py-2.5 px-3 w-36 text-right">Est. Subtotal</th>
                        <th class="py-2.5 px-3 w-12 text-center">Aksi</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                      <tr
                        v-for="(item, idx) in form.items"
                        :key="idx"
                        class="hover:bg-slate-50/70 transition-colors"
                      >
                        <td class="py-3 px-3 text-center text-slate-400 font-mono">{{ idx + 1 }}</td>
                        <td class="py-3 px-3">
                          <div class="font-bold text-slate-900">{{ item.name }}</div>
                          <div class="flex items-center gap-2 mt-0.5">
                            <span class="font-mono text-[10px] text-slate-400">{{ item.sku || 'CUSTOM' }}</span>
                            <span v-if="item.isCatalog" class="text-[9px] font-bold text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200">Katalog</span>
                            <span v-else class="text-[9px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">Custom</span>
                          </div>
                        </td>
                        <td class="py-3 px-3">
                          <input
                            v-model="item.specs"
                            type="text"
                            placeholder="Merk/tipe/warna tertentu..."
                            class="w-full px-2 py-1 text-xs border border-slate-200 rounded-lg focus:border-blue-500 bg-white"
                          />
                        </td>
                        <td class="py-3 px-3 text-center">
                          <input
                            v-model.number="item.qty"
                            type="number"
                            min="1"
                            required
                            class="w-20 px-2 py-1 text-xs text-center border border-slate-300 rounded-lg font-bold focus:border-blue-500"
                          />
                        </td>
                        <td class="py-3 px-3 text-center">
                          <span class="font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded text-xs">
                            {{ item.unit }}
                          </span>
                        </td>
                        <td class="py-3 px-3 text-right">
                          <input
                            v-model.number="item.price"
                            type="number"
                            min="0"
                            step="500"
                            class="w-28 px-2 py-1 text-xs text-right font-mono border border-slate-300 rounded-lg focus:border-blue-500"
                          />
                        </td>
                        <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                          Rp {{ (Number(item.qty || 0) * Number(item.price || 0)).toLocaleString('id-ID') }}
                        </td>
                        <td class="py-3 px-3 text-center">
                          <button
                            type="button"
                            class="p-1.5 rounded-lg bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white shadow-xs transition-all cursor-pointer inline-flex items-center justify-center"
                            title="Hapus Barang"
                            @click="removeItem(idx)"
                          >
                            <Trash2 class="w-3.5 h-3.5 text-white stroke-[2.2]" />
                          </button>
                        </td>
                      </tr>

                      <!-- EMPTY STATE -->
                      <tr v-if="form.items.length === 0">
                        <td colspan="8" class="py-10 text-center">
                          <div class="max-w-sm mx-auto text-center space-y-2">
                            <ShoppingBag class="w-10 h-10 text-slate-300 mx-auto" />
                            <p class="text-xs font-bold text-slate-700">Belum Ada Barang yang Ditambahkan</p>
                            <p class="text-[11px] text-slate-400">
                              Klik tombol <strong>"Pilih dari Master Katalog"</strong> untuk memilih dari 2.196 SKU atau gunakan <strong>"Tambah Item Custom"</strong> untuk kebutuhan spesifik.
                            </p>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- SUMMARY FOOTER -->
                <div class="bg-slate-50/90 border-t border-slate-200 p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                  <div class="text-xs text-slate-500 space-y-0.5">
                    <div>Jumlah Barang: <strong class="text-slate-900">{{ form.items.length }}</strong> jenis</div>
                    <div>Total Unit Fisik: <strong class="text-slate-900">{{ totalQty }}</strong> kuantitas</div>
                  </div>

                  <div class="w-full sm:w-auto sm:min-w-[320px] space-y-1.5 text-xs">
                    <div class="flex items-center justify-between text-slate-600">
                      <span>Estimasi Subtotal:</span>
                      <span class="font-mono font-bold text-slate-900">Rp {{ subtotal.toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                      <span>Estimasi PPN (11%):</span>
                      <span class="font-mono text-slate-800">Rp {{ tax.toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-1.5 border-t border-slate-200 text-sm font-bold text-[#0B4DA2]">
                      <span>Total Estimasi Proyek:</span>
                      <span class="font-mono text-base">Rp {{ grandTotal.toLocaleString('id-ID') }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>


          <!-- SUBMIT ACTION BAR -->
          <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2 text-xs text-slate-500">
              <ShieldCheck class="w-4 h-4 text-emerald-600 shrink-0" />
              <span>Pengajuan akan tercatat resmi dengan nomor resi tracking & langsung dinotifikasikan ke Marketing.</span>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
              <button
                type="button"
                class="px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold rounded-xl text-xs shadow-2xs transition-colors cursor-pointer"
                @click="resetForm"
              >
                Reset
              </button>
              <button
                type="submit"
                :disabled="form.items.length === 0 || isSubmitting"
                class="px-6 py-2.5 bg-gradient-to-r from-[#0B4DA2] to-[#062F69] hover:from-[#09428F] hover:to-[#042452] text-white font-bold rounded-xl text-xs sm:text-sm shadow-sm shadow-blue-900/30 transition-all cursor-pointer flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <Send class="w-4 h-4 text-amber-300" />
                <span>{{ isSubmitting ? 'Mengirimkan...' : 'Kirim Pengajuan Kebutuhan Barang' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 2: PUBLIC TRACKING SECTION (FR-CL-03, FR-CL-04, FR-CL-05) -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'tracking'" class="space-y-6 max-w-5xl mx-auto">
        <!-- SEARCH RESI BOX -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 sm:p-8 shadow-xs text-center space-y-4 border-t-[4px] border-t-[#0B4DA2]">
          <div class="max-w-xl mx-auto">
            <h2 class="text-base sm:text-lg font-bold text-slate-900">Lacak Pengiriman & Status Pesanan Rekanan</h2>
            <p class="text-xs text-slate-500 mt-1">
              Masukkan Nomor Resi, Surat Jalan, atau Nomor Order pengadaan sarana Anda tanpa perlu login.
            </p>

            <form class="mt-4 flex flex-col sm:flex-row items-center gap-2" @submit.prevent="handleSearchResi">
              <div class="relative w-full">
                <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  v-model="trackingInput"
                  type="text"
                  required
                  placeholder="Ketik nomor resi / surat jalan / no. order..."
                  class="w-full pl-10 pr-4 py-3 text-xs sm:text-sm rounded-xl border border-slate-300 font-mono font-bold focus:outline-hidden focus:ring-2 focus:ring-[#0B4DA2] bg-slate-50/50"
                />
              </div>
              <button
                type="submit"
                class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-[#0B4DA2] to-[#062F69] hover:from-[#09428F] hover:to-[#042452] text-white font-bold rounded-xl text-xs sm:text-sm shadow-xs transition-all shrink-0 cursor-pointer flex items-center justify-center gap-2"
              >
                <Truck class="w-4 h-4 text-amber-300" />
                <span>Lacak Pesanan</span>
              </button>
            </form>
          </div>

          <!-- QUICK SUGGESTIONS -->
          <div class="pt-2 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-500">
            <span>Coba lacak pesanan demo:</span>
            <button
              v-for="ex in ['ORD-2026-001', 'ORD-2026-002', 'ORD-2026-007']"
              :key="ex"
              type="button"
              class="font-mono px-2.5 py-1 bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-[#0B4DA2] rounded-lg border border-slate-200 cursor-pointer transition-colors"
              @click="trackingInput = ex; handleSearchResi();"
            >
              {{ ex }}
            </button>
          </div>
        </div>

        <!-- SEARCH RESULT -->
        <div v-if="searchedOrder" class="space-y-6">
          <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-xs space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 pb-4 border-b border-slate-100">
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-mono font-bold text-slate-900 text-lg">{{ searchedOrder.orderNumber }}</span>
                  <StatusBadge :status="searchedOrder.fulfillmentStatus || searchedOrder.status" />
                </div>
                <h3 class="text-sm sm:text-base font-bold text-slate-800">{{ searchedOrder.projectName }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">
                  Instansi: <strong class="text-slate-800">{{ searchedOrder.clientName }}</strong> &bull; Tanggal: {{ searchedOrder.date }}
                </p>
              </div>

              <!-- CONFIRM RECEIPT CTA BUTTON (FR-CL-05) -->
              <div v-if="searchedOrder.fulfillmentStatus !== 'Selesai'" class="text-left sm:text-right">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-xl text-xs shadow-xs transition-all cursor-pointer"
                  @click="openConfirmModal"
                >
                  <CheckCircle2 class="w-4 h-4 stroke-[2.5]" />
                  <span>Konfirmasi Terima Barang (BAST)</span>
                </button>
                <p class="text-[10px] text-slate-400 mt-1">Klik jika barang telah tiba lengkap di sekolah</p>
              </div>

              <div v-else class="text-left sm:text-right">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-xs">
                  <CheckCircle2 class="w-4 h-4" />
                  Barang Selesai Diterima (BAST)
                </span>
                <p class="text-[10px] text-slate-400 mt-1">BAST Fisik telah ditandatangani</p>
              </div>
            </div>

            <!-- STEPPER PROGRESS TAHAPAN (REAL-TIME VISUAL PIPELINE) -->
            <div>
              <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4">
                Progres Pelacakan & Tahapan Pengadaan
              </h4>

              <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                <div
                  v-for="(st, sIdx) in trackingStages"
                  :key="sIdx"
                  :class="[
                    st.done
                      ? 'bg-emerald-50 border-emerald-300 text-emerald-800'
                      : st.current
                      ? 'bg-blue-50 border-[#0B4DA2] ring-2 ring-blue-500/20 text-[#0B4DA2] font-bold'
                      : 'bg-slate-50 border-slate-200 text-slate-400',
                    'p-3 rounded-xl border text-center transition-all'
                  ]"
                >
                  <div class="flex items-center justify-center mb-1.5">
                    <component
                      :is="st.done ? CheckCircle2 : st.current ? Clock : Circle"
                      :class="[
                        st.done ? 'text-emerald-600' : st.current ? 'text-[#0B4DA2] animate-pulse' : 'text-slate-300',
                        'w-4 h-4'
                      ]"
                    />
                  </div>
                  <p class="text-[11px] font-bold uppercase leading-tight">{{ st.label }}</p>
                  <p class="text-[10px] mt-0.5 leading-tight opacity-80">{{ st.detail }}</p>
                </div>
              </div>
            </div>

            <!-- SHIPMENT LOGISTICS DETAIL -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
              <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                <h5 class="font-bold text-slate-900 flex items-center gap-1.5">
                  <Truck class="w-4 h-4 text-[#0B4DA2]" />
                  <span>Informasi Logistik & Pengemudi</span>
                </h5>
                <div class="flex justify-between py-1 border-b border-slate-200/60">
                  <span class="text-slate-500">Ekspedisi / Armada:</span>
                  <span class="font-semibold text-slate-800">{{ shipmentDetail.expedition }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60">
                  <span class="text-slate-500">No. Polisi Armada:</span>
                  <span class="font-mono font-bold text-slate-900">{{ shipmentDetail.vehiclePlate }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60">
                  <span class="text-slate-500">Nama Sopir / Driver:</span>
                  <span class="font-semibold text-slate-800">{{ shipmentDetail.driverName }}</span>
                </div>
                <div class="flex justify-between py-1">
                  <span class="text-slate-500">Estimasi Tiba di Lokasi:</span>
                  <span class="font-bold text-emerald-700">{{ shipmentDetail.eta }}</span>
                </div>
              </div>

              <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                <h5 class="font-bold text-slate-900 flex items-center gap-1.5">
                  <MapPin class="w-4 h-4 text-rose-500" />
                  <span>Tujuan Pengiriman</span>
                </h5>
                <p class="font-bold text-slate-800">{{ searchedOrder.clientName }}</p>
                <p class="text-slate-600 leading-relaxed">{{ searchedOrder.address || 'Alamat instansi terdaftar di DKI Jakarta' }}</p>
                <p class="text-[11px] text-slate-400 pt-1">PIC Penerima: {{ searchedOrder.contactPerson || 'Petugas Sekolah' }}</p>
              </div>
            </div>

            <!-- ITEMS SUMMARY -->
            <div>
              <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2">
                Rincian Barang dalam Pengiriman
              </h4>
              <div class="border border-slate-200 rounded-xl overflow-hidden">
                <table class="w-full text-left text-xs">
                  <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-[11px]">
                    <tr>
                      <th class="py-2.5 px-3">No</th>
                      <th class="py-2.5 px-3">Nama Barang</th>
                      <th class="py-2.5 px-3 text-center">Kuantitas</th>
                      <th class="py-2.5 px-3 text-center">Satuan</th>
                      <th class="py-2.5 px-3 text-right">Harga Acuan</th>
                      <th class="py-2.5 px-3 text-right">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr v-for="(it, idx) in searchedOrder.items" :key="idx" class="hover:bg-slate-50">
                      <td class="py-2.5 px-3 text-slate-400 font-mono">{{ idx + 1 }}</td>
                      <td class="py-2.5 px-3 font-semibold text-slate-900">{{ it.name }}</td>
                      <td class="py-2.5 px-3 text-center font-bold text-slate-800">{{ it.qty }}</td>
                      <td class="py-2.5 px-3 text-center text-slate-600">{{ it.unit }}</td>
                      <td class="py-2.5 px-3 text-right font-mono text-slate-700">Rp {{ Number(it.price || 0).toLocaleString('id-ID') }}</td>
                      <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">Rp {{ (Number(it.qty || 0) * Number(it.price || 0)).toLocaleString('id-ID') }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- SEARCH NOT FOUND -->
        <div v-else-if="hasSearched && !searchedOrder" class="bg-white rounded-2xl border border-slate-200 p-8 text-center space-y-2">
          <AlertCircle class="w-10 h-10 text-amber-500 mx-auto" />
          <p class="text-sm font-bold text-slate-800">Nomor Resi / Pesanan Tidak Ditemukan</p>
          <p class="text-xs text-slate-500 max-w-md mx-auto">
            Pastikan nomor yang Anda masukkan sesuai dengan format pada konfirmasi email atau bukti Surat Jalan (contoh: <strong class="font-mono text-blue-600">ORD-2026-001</strong>).
          </p>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 3: FITUR PEMBAYARAN & TERMIN (STATUS INVOICE REKANAN) -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'payment'" class="space-y-6">
        
        <!-- BANK ACCOUNT & OFFICIAL PAYMENT INSTRUCTIONS BANNER -->
        <div class="bg-gradient-to-r from-blue-900 via-[#0B4DA2] to-blue-800 rounded-2xl p-6 text-white shadow-md">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-xl">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-white/20 text-white uppercase tracking-wider">
                Rekening Resmi Perusahaan (Tunggal & Tetap)
              </span>
              <h3 class="text-lg font-bold text-white">Pembayaran Ditujukan Kepada: CV Alpharo Artha Jaya</h3>
              <p class="text-xs text-blue-100 leading-relaxed">
                Gunakan nomor rekening resmi Bank BCA di samping untuk seluruh transaksi pembayaran pesanan pengadaan, SP2D BOS, termin, maupun pelunasan instansi Anda.
              </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
              <div class="bg-white/10 backdrop-blur-xs rounded-xl p-4 border border-white/20 min-w-[270px] shadow-sm">
                <div class="flex items-center justify-between gap-2 mb-1">
                  <p class="text-[11px] text-amber-300 font-bold uppercase tracking-wider">Bank BCA Jakarta</p>
                  <span class="text-[9px] font-extrabold bg-emerald-500/80 text-white px-2 py-0.5 rounded-full">Rekening Resmi Tetap</span>
                </div>
                <p class="font-mono text-xl font-extrabold text-white tracking-widest my-1 select-all">52323628813</p>
                <p class="text-[11px] text-blue-100 font-semibold">a.n. CV ALPHARO ARTHA JAYA</p>
              </div>
            </div>
          </div>
        </div>

        <!-- KPI SUMMARY -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <KpiCard title="Total Nilai Tagihan" :value="totalInvoiceValue" :is-currency="true" subtitle="Akumulasi tagihan rekanan" icon-variant="slate" />
          <KpiCard title="Realisasi Dibayar" :value="totalPaid" :is-currency="true" subtitle="Dana telah diverifikasi" icon-variant="emerald" />
          <KpiCard title="Sisa Tagihan" :value="totalOutstanding" :is-currency="true" subtitle="Menunggu pelunasan termin" icon-variant="rose" />
          <KpiCard title="Invoice Lunas" :value="paidCount" subtitle="Tagihan tuntas 100%" icon-variant="blue" />
        </div>

        <!-- FILTER & REKANAN SELECTOR -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex flex-wrap items-center gap-3">
            <div>
              <label class="block text-[11px] font-semibold text-slate-500 mb-1">Pilih Rekanan Sekolah:</label>
              <select
                v-model="paymentClientFilter"
                class="px-3 py-1.5 text-xs rounded-xl border border-slate-300 bg-white font-medium text-slate-800"
              >
                <option value="all">Semua Rekanan Terdaftar</option>
                <option v-for="c in store.clients" :key="c.id" :value="c.id">
                  {{ c.name }} ({{ c.city }})
                </option>
              </select>
            </div>

            <div>
              <label class="block text-[11px] font-semibold text-slate-500 mb-1">Status Pembayaran:</label>
              <div class="flex items-center gap-1">
                <button
                  v-for="f in paymentFilterTabs"
                  :key="f.key"
                  type="button"
                  :class="[
                    paymentStatusFilter === f.key
                      ? 'bg-slate-900 text-white font-bold'
                      : 'bg-slate-100 hover:bg-slate-200 text-slate-700',
                    'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer'
                  ]"
                  @click="paymentStatusFilter = f.key"
                >
                  {{ f.label }}
                </button>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <button
              type="button"
              class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-xl text-xs shadow-xs transition-all cursor-pointer flex items-center gap-1.5"
              @click="openPaymentModal(null)"
            >
              <UploadCloud class="w-4 h-4" />
              <span>Konfirmasi Pembayaran Baru</span>
            </button>
          </div>
        </div>

        <!-- INVOICE CARDS WITH TERMIN DETAIL -->
        <div class="space-y-6">
          <div
            v-for="inv in filteredInvoices"
            :key="inv.id"
            class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden border-t-[3px] border-t-blue-600"
          >
            <!-- Header invoice card -->
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-mono font-bold text-slate-900 text-base">{{ inv.invoiceNumber }}</span>
                  <StatusBadge :status="inv.status" />
                </div>
                <p class="text-xs text-slate-700 font-semibold">
                  {{ inv.clientName }} &bull; Order No: <span class="font-mono font-bold text-slate-900">{{ inv.orderNumber }}</span>
                </p>
                <p class="text-xs text-slate-400 mt-0.5">
                  Skema: <span class="font-medium text-slate-700">{{ inv.paymentType || 'Termin' }} ({{ inv.terminCount || 2 }} Termin)</span>
                  &bull; Batas Jatuh Tempo: <span class="font-medium text-slate-700">{{ inv.dueDate }}</span>
                </p>
              </div>

              <div class="flex items-center gap-4">
                <div class="text-left sm:text-right">
                  <p class="text-[10px] text-slate-400 uppercase font-semibold">Total Tagihan</p>
                  <p class="text-base font-bold text-slate-900 font-mono">Rp {{ (inv.totalAmount || 0).toLocaleString('id-ID') }}</p>
                  <p class="text-xs text-rose-600 font-semibold font-mono">
                    Sisa: Rp {{ (inv.remainingAmount || 0).toLocaleString('id-ID') }}
                  </p>
                </div>

                <button
                  v-if="inv.remainingAmount > 0"
                  type="button"
                  class="px-4 py-2 bg-gradient-to-r from-[#0B4DA2] to-[#062F69] hover:from-[#09428F] hover:to-[#042452] text-white rounded-xl text-xs font-bold shadow-xs transition-all shrink-0 flex items-center gap-1.5 cursor-pointer"
                  @click="openPaymentModal(inv)"
                >
                  <CreditCard class="w-4 h-4 text-amber-300" />
                  <span>Bayar / Konfirmasi</span>
                </button>
              </div>
            </div>

            <!-- Termin schedule table -->
            <div class="p-5">
              <div class="flex items-center justify-between mb-3">
                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                  Jadwal & Riwayat Termin Pembayaran
                </h4>
                <span class="text-xs text-slate-500">
                  Realisasi: <strong class="text-emerald-600 font-mono">{{ paidPercentage(inv) }}% Lunas</strong>
                </span>
              </div>

              <!-- Progress bar -->
              <div class="h-2 bg-slate-100 rounded-full overflow-hidden mb-4">
                <div
                  class="h-full bg-emerald-500 rounded-full transition-all duration-300"
                  :style="{ width: paidPercentage(inv) + '%' }"
                ></div>
              </div>

              <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                  <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                      <th class="py-2.5 px-3">Termin</th>
                      <th class="py-2.5 px-3 text-right">Jumlah Tagihan</th>
                      <th class="py-2.5 px-3">Batas Jatuh Tempo</th>
                      <th class="py-2.5 px-3">Metode Bayar</th>
                      <th class="py-2.5 px-3">Tanggal Pelunasan</th>
                      <th class="py-2.5 px-3 text-center">Status</th>
                      <th class="py-2.5 px-3 text-right">Aksi</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr
                      v-for="termin in (inv.termins || [])"
                      :key="termin.termNumber"
                      class="hover:bg-slate-50/60 transition-colors"
                    >
                      <td class="py-3 px-3 font-bold text-slate-900">
                        Termin Ke-{{ termin.termNumber }}
                      </td>
                      <td class="py-3 px-3 text-right font-mono font-semibold text-slate-900 whitespace-nowrap">
                        Rp {{ (termin.amount || 0).toLocaleString('id-ID') }}
                      </td>
                      <td class="py-3 px-3 text-slate-700 whitespace-nowrap">{{ termin.dueDate }}</td>
                      <td class="py-3 px-3 text-slate-600">{{ termin.method || '-' }}</td>
                      <td class="py-3 px-3 text-slate-600 whitespace-nowrap">{{ termin.paidDate || '-' }}</td>
                      <td class="py-3 px-3 text-center">
                        <StatusBadge :status="termin.status" />
                      </td>
                      <td class="py-3 px-3 text-right whitespace-nowrap">
                        <button
                          v-if="termin.status !== 'Lunas'"
                          type="button"
                          class="text-blue-600 hover:text-blue-800 font-bold hover:underline cursor-pointer"
                          @click="openPaymentModal(inv, termin)"
                        >
                          Konfirmasi Bayar
                        </button>
                        <span v-else class="text-emerald-600 font-bold text-[11px] flex items-center justify-end gap-1">
                          <CheckCircle2 class="w-3.5 h-3.5" /> Lunas
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div
            v-if="filteredInvoices.length === 0"
            class="bg-white rounded-2xl border border-slate-200/90 p-12 shadow-xs text-center text-slate-400 text-xs"
          >
            <Receipt class="w-12 h-12 text-slate-300 mx-auto mb-3" />
            <p class="font-bold text-slate-700 text-sm">Tidak ada berkas invoice tagihan</p>
            <p class="mt-1">Tidak ditemukan tagihan yang sesuai dengan filter atau sekolah terpilih.</p>
          </div>
        </div>
      </div>
    </main>

    <!-- ============================================================== -->
    <!-- MODALS SECTION -->
    <!-- ============================================================== -->

    <!-- MODAL 1: MASTER CATALOG QUICK PICKER (2.196 SKU) -->
    <Modal
      v-model="catalogModalOpen"
      title="Pilih Barang dari Master Katalog CV Alpharo (2.196 SKU)"
      subtitle="Cari berdasarkan kode SKU, nama barang, atau kategori. Satuan resmi akan otomatis terkunci."
      size="xl"
    >
      <div class="space-y-4">
        <!-- Search & Filter Controls -->
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
          <div class="sm:col-span-7 relative">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
            <input
              v-model="catalogSearch"
              type="text"
              placeholder="Cari SKU atau nama barang (misal: Meja, Semen, Besi)..."
              class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div class="sm:col-span-5">
            <select
              v-model="catalogCategory"
              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white"
            >
              <option value="">Semua Kategori</option>
              <option v-for="cat in availableCategories" :key="cat" :value="cat">
                {{ cat }}
              </option>
            </select>
          </div>
        </div>

        <!-- Catalog List Table -->
        <div class="border border-slate-200 rounded-xl max-h-[380px] overflow-y-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 sticky top-0 text-slate-600 font-semibold text-[11px]">
              <tr>
                <th class="py-2 px-3">SKU & Kategori</th>
                <th class="py-2 px-3">Nama Barang</th>
                <th class="py-2 px-3 text-center">Satuan</th>
                <th class="py-2.5 px-3 text-center whitespace-nowrap min-w-[130px]">Status Stok</th>
                <th class="py-2.5 px-3 text-right whitespace-nowrap min-w-[120px]">Harga Acuan</th>
                <th class="py-2.5 px-3 text-center whitespace-nowrap w-24">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="prod in filteredCatalog"
                :key="prod.id || prod.sku"
                class="hover:bg-blue-50/40 transition-colors"
              >
                <td class="py-2.5 px-3">
                  <div class="font-mono font-bold text-slate-800 text-[11px]">{{ prod.sku }}</div>
                  <div class="text-[10px] text-slate-400">{{ prod.category || 'Umum' }}</div>
                </td>
                <td class="py-2.5 px-3 font-medium text-slate-900">
                  {{ prod.name }}
                </td>
                <td class="py-2.5 px-3 text-center font-bold text-slate-700">
                  <span class="bg-slate-100 px-2 py-0.5 rounded text-[11px]">{{ prod.unit }}</span>
                </td>
                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                  <span
                    :class="[
                      prod.stock > 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-1 ring-emerald-500/10' : 'bg-amber-50 text-amber-700 border-amber-200 ring-1 ring-amber-500/10',
                      'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border whitespace-nowrap'
                    ]"
                  >
                    <span
                      class="w-1.5 h-1.5 rounded-full shrink-0"
                      :class="prod.stock > 0 ? 'bg-emerald-500' : 'bg-amber-500'"
                    ></span>
                    <span>{{ prod.stock > 0 ? 'Tersedia (' + prod.stock + ')' : 'PO Gudang' }}</span>
                  </span>
                </td>
                <td class="py-2.5 px-3 text-right font-mono text-slate-800 font-semibold whitespace-nowrap">
                  Rp {{ Number(prod.price || 0).toLocaleString('id-ID') }}
                </td>
                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                  <button
                    type="button"
                    class="px-3 py-1 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg font-bold text-xs shadow-2xs transition-all cursor-pointer whitespace-nowrap"
                    @click="addCatalogItemToForm(prod)"
                  >
                    Pilih
                  </button>
                </td>
              </tr>
              <tr v-if="filteredCatalog.length === 0">
                <td colspan="6" class="py-8 text-center text-xs text-slate-400">
                  Tidak ditemukan barang yang sesuai filter pencarian.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-100">
          <span>Menampilkan {{ filteredCatalog.length }} barang acuan</span>
          <button
            type="button"
            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold text-xs cursor-pointer"
            @click="catalogModalOpen = false"
          >
            Selesai Memilih
          </button>
        </div>
      </div>
    </Modal>

    <!-- MODAL 2: CUSTOM ITEM INPUT MODAL -->
    <Modal
      v-model="customItemModalOpen"
      title="Tambah Kebutuhan Barang Custom / Khusus"
      subtitle="Gunakan form ini jika barang yang Anda butuhkan belum terdaftar di katalog master."
      size="md"
    >
      <form class="space-y-4 text-xs" @submit.prevent="saveCustomItem">
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Nama Barang / Fasilitas <span class="text-rose-500">*</span></label>
          <input
            v-model="customItem.name"
            type="text"
            required
            placeholder="Contoh: Paket Papan Tulis Whiteboard Magnetik 120x240"
            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Kuantitas (Jumlah) <span class="text-rose-500">*</span></label>
            <input
              v-model.number="customItem.qty"
              type="number"
              min="1"
              required
              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 text-center font-bold"
            />
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Satuan <span class="text-rose-500">*</span></label>
            <input
              v-model="customItem.unit"
              type="text"
              required
              placeholder="Unit / Set / Paket / Pcs"
              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 text-center font-bold"
            />
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Estimasi Harga Satuan (Rp)</label>
          <input
            v-model.number="customItem.price"
            type="number"
            min="0"
            step="1000"
            placeholder="Perkiraan harga satuan..."
            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 font-mono text-right"
          />
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Spesifikasi Detail / Keterangan Khusus</label>
          <textarea
            v-model="customItem.specs"
            rows="2"
            placeholder="Dimensi ukuran, merk referensi, warna, kelengkapan baut/aksesori..."
            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
          <button
            type="button"
            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold cursor-pointer"
            @click="customItemModalOpen = false"
          >
            Batal
          </button>
          <button
            type="submit"
            class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold shadow-xs cursor-pointer"
          >
            Tambahkan ke Daftar
          </button>
        </div>
      </form>
    </Modal>

    <!-- MODAL 3: SUCCESS SUBMISSION MODAL -->
    <Modal
      v-model="successModalOpen"
      title="Pengajuan Kebutuhan Berhasil Terkirim!"
      subtitle="Permohonan pesanan Anda telah berhasil dicatat dalam sistem ERP CV Alpharo Artha Jaya."
      size="md"
    >
      <div class="text-center py-4 space-y-4">
        <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto shadow-xs">
          <CheckCircle2 class="w-10 h-10 stroke-[2.5]" />
        </div>

        <div>
          <p class="text-xs text-slate-500 uppercase font-semibold">Nomor Kode Pengajuan Anda</p>
          <p class="text-xl font-bold font-mono text-[#0B4DA2] mt-1 tracking-wider">{{ createdOrder?.orderNumber }}</p>
          <p class="text-xs text-slate-600 mt-2 font-medium">{{ createdOrder?.projectName }}</p>
          <p class="text-xs text-slate-400">Total Estimasi: <strong class="text-slate-800">Rp {{ Number(createdOrder?.grandTotal || 0).toLocaleString('id-ID') }}</strong></p>
        </div>

        <div class="p-3 bg-blue-50/70 border border-blue-200 rounded-xl text-left text-xs text-blue-900 space-y-1">
          <p class="font-bold flex items-center gap-1.5">
            <Info class="w-4 h-4 text-blue-600 shrink-0" />
            <span>Langkah Selanjutnya:</span>
          </p>
          <p class="text-[11px] text-blue-800">
            Tim Marketing Alpharo akan menghubungi PIC ({{ createdOrder?.contactPerson }}) untuk verifikasi ketersediaan stok & penerbitan Surat Penawaran Harga (SPH). Anda dapat memantau setiap proses melalui tab Lacak Resi.
          </p>
        </div>

        <div class="flex items-center justify-center gap-3 pt-2">
          <button
            type="button"
            class="px-4 py-2 bg-[#0B4DA2] hover:bg-[#09428F] text-white rounded-xl font-bold text-xs shadow-xs transition-colors cursor-pointer flex items-center gap-1.5"
            @click="viewInTracking(createdOrder?.orderNumber)"
          >
            <Truck class="w-4 h-4 text-amber-300" />
            <span>Lacak Pengajuan Ini Sekarang</span>
          </button>
          <button
            type="button"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold text-xs transition-colors cursor-pointer"
            @click="createNewAnother"
          >
            Ajukan Pesanan Lain
          </button>
        </div>
      </div>
    </Modal>

    <!-- MODAL 4: CONFIRM RECEIPT MODAL (FR-CL-05) -->
    <Modal
      v-model="confirmModalOpen"
      title="Konfirmasi Penerimaan Barang (BAST)"
      subtitle="Pastikan seluruh fisik barang telah diperiksa dan diterima dengan baik di lokasi sekolah/instansi Anda."
      size="md"
    >
      <form class="space-y-4 text-xs" @submit.prevent="submitReceiptConfirmation">
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900 space-y-1">
          <p class="font-bold flex items-center gap-1.5">
            <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
            <span>Konfirmasi untuk Pesanan: {{ searchedOrder?.orderNumber }}</span>
          </p>
          <p class="text-[11px] text-emerald-800">{{ searchedOrder?.projectName }}</p>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">
            Nama Petugas Penerima / PIC di Lokasi <span class="text-rose-500">*</span>
          </label>
          <input
            v-model="receiptForm.recipientName"
            type="text"
            required
            placeholder="Contoh: Bpk. Supriyadi (Kepala Sarpras / Guru)"
            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300"
          />
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">
            Nomor Telepon / WhatsApp Penerima <span class="text-rose-500">*</span>
          </label>
          <input
            v-model="receiptForm.phone"
            type="text"
            required
            placeholder="0812-xxxx-xxxx"
            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300"
          />
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">
            Catatan Kondisi Fisik Barang <span class="text-rose-500">*</span>
          </label>
          <textarea
            v-model="receiptForm.notes"
            rows="2"
            required
            placeholder="Contoh: Barang telah diperiksa fisik lengkap, jumlah sesuai Surat Jalan, kondisi mulus tanpa cacat."
            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300"
          ></textarea>
        </div>

        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-[11px] text-slate-600 flex items-start gap-2">
          <Info class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" />
          <span>
            Dengan mengonfirmasi, status pesanan akan diperbarui menjadi <strong>SELESAI</strong> di bagian Logistik Gudang dan Administrasi CV Alpharo Artha Jaya untuk pengarsipan BAST fisik.
          </span>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button
            type="button"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold cursor-pointer"
            @click="confirmModalOpen = false"
          >
            Batal
          </button>
          <button
            type="submit"
            class="px-5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-xl shadow-xs cursor-pointer flex items-center gap-1.5"
          >
            <CheckCircle2 class="w-4 h-4" />
            <span>Simpan Konfirmasi Penerimaan</span>
          </button>
        </div>
      </form>
    </Modal>

    <!-- MODAL 5: KONFIRMASI PEMBAYARAN TAGIHAN & TERMIN -->
    <Modal
      v-model="showPaymentModal"
      title="Konfirmasi Pembayaran Tagihan / Termin"
      subtitle="Kirimkan bukti transfer pembayaran tagihan pesanan Anda untuk diverifikasi oleh Keuangan CV Alpharo Artha Jaya."
      size="md"
    >
      <form class="space-y-4 text-xs" @submit.prevent="submitPaymentConfirmation">
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Pilih Invoice yang Dibayar <span class="text-rose-500">*</span></label>
          <select
            v-model="paymentForm.invoiceNumber"
            required
            class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white"
            @change="onModalInvoiceChange"
          >
            <option v-for="inv in store.invoices" :key="inv.id" :value="inv.invoiceNumber">
              {{ inv.invoiceNumber }} — {{ inv.clientName }} (Sisa: Rp {{ (inv.remainingAmount || 0).toLocaleString('id-ID') }})
            </option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Skema Pembayaran</label>
            <select
              v-model="paymentForm.paymentType"
              class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white"
            >
              <option value="Termin">Pembayaran Termin</option>
              <option value="Penuh">Pelunasan Penuh</option>
            </select>
          </div>

          <div v-if="paymentForm.paymentType === 'Termin'">
            <label class="block font-semibold text-slate-700 mb-1">Termin Ke</label>
            <select
              v-model="paymentForm.terminNumber"
              class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white"
            >
              <option :value="1">Termin Ke-1</option>
              <option :value="2">Termin Ke-2</option>
              <option :value="3">Termin Ke-3</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Nominal yang Ditransfer (Rp) <span class="text-rose-500">*</span></label>
          <input
            v-model.number="paymentForm.amount"
            type="number"
            min="1000"
            required
            placeholder="Contoh: 15000000"
            class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono font-bold"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Metode Transfer Bank <span class="text-rose-500">*</span></label>
            <select
              v-model="paymentForm.paymentMethod"
              class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white"
            >
              <option value="Transfer BCA">Transfer BCA</option>
              <option value="Transfer Mandiri">Transfer Bank Mandiri</option>
              <option value="Transfer BNI">Transfer Bank BNI</option>
              <option value="SIPLah / Virtual Account">Sistem SIPLah / SP2D BOS</option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Tanggal Transfer <span class="text-rose-500">*</span></label>
            <input
              v-model="paymentForm.paymentDate"
              type="date"
              required
              class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white"
            />
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">No. Referensi / Ref Struk Bank <span class="text-rose-500">*</span></label>
          <input
            v-model="paymentForm.referenceNumber"
            type="text"
            required
            placeholder="Contoh: TRF-BCA-889102"
            class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono"
          />
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Unggah Bukti Transfer / SP2D (File Foto/PDF)</label>
          <div class="border-2 border-dashed border-slate-300 rounded-xl p-3 text-center bg-slate-50">
            <UploadCloud class="w-6 h-6 text-slate-400 mx-auto mb-1" />
            <p class="text-[11px] text-slate-600 font-medium">Struk_Transfer_Rekanan.pdf (Simulasi Terpilih)</p>
            <p class="text-[10px] text-slate-400">Format didukung: JPG, PNG, atau PDF max 5MB</p>
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
          <textarea
            v-model="paymentForm.notes"
            rows="2"
            placeholder="Catatan dari bendahara sekolah..."
            class="w-full px-3 py-2 rounded-xl border border-slate-300"
          ></textarea>
        </div>

        <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
          <button
            type="button"
            class="px-4 py-2 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold cursor-pointer"
            @click="showPaymentModal = false"
          >
            Batal
          </button>
          <button
            type="submit"
            class="px-5 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold shadow-xs cursor-pointer flex items-center gap-1.5"
          >
            <CheckCircle2 class="w-4 h-4" />
            <span>Kirim Konfirmasi Pembayaran</span>
          </button>
        </div>
      </form>
    </Modal>

    <!-- PUBLIC FOOTER -->
    <footer class="mt-auto border-t border-slate-200 bg-white py-8 px-4 sm:px-6 lg:px-8 text-xs text-slate-500">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
          <p class="font-bold text-slate-800">CV. Alpharo Artha Jaya — Portal Publik Rekanan & Layanan Pengadaan Mandiri</p>
          <p class="text-slate-400 text-[11px] mt-0.5">Mitra Terpercaya Pengadaan Sarana Prasarana Sekolah & Instansi di DKI Jakarta</p>
        </div>
        <div class="flex flex-wrap items-center gap-4 text-[11px]">
          <button type="button" class="text-blue-600 font-semibold hover:underline cursor-pointer" @click="setTab('request')">Ajukan Kebutuhan</button>
          <span>&bull;</span>
          <button type="button" class="text-blue-600 font-semibold hover:underline cursor-pointer" @click="setTab('tracking')">Lacak Resi Pengiriman</button>
          <span>&bull;</span>
          <button type="button" class="text-blue-600 font-semibold hover:underline cursor-pointer" @click="setTab('payment')">Status Pembayaran</button>
          <span>&bull;</span>
          <router-link to="/login" class="text-slate-700 font-bold hover:underline">Staff Login</router-link>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import AlpharoLogo from '../../components/common/AlpharoLogo.vue';
import Modal from '../../components/common/Modal.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import KpiCard from '../../components/common/KpiCard.vue';

// Lucide Icons
import {
  Truck,
  ShoppingBag,
  Search,
  CheckCircle2,
  Clock,
  Circle,
  MapPin,
  AlertCircle,
  LogIn,
  School,
  Sparkles,
  Info,
  ClipboardCheck,
  Plus,
  Trash2,
  UploadCloud,
  Send,
  FolderKanban,
  Boxes,
  ShieldCheck,
  CreditCard,
  Receipt,
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const toast = useToast();

// Active tab: 'request' | 'tracking' | 'payment'
const activeTab = ref('request');

const setTab = (tabName) => {
  activeTab.value = tabName;
  router.replace({ query: { ...route.query, tab: tabName } });
};

// ==========================================
// 1. STATE & LOGIC: AJUKAN KEBUTUHAN (TAB 1)
// ==========================================
const catalogModalOpen = ref(false);
const customItemModalOpen = ref(false);
const successModalOpen = ref(false);
const isSubmitting = ref(false);
const uploadedFileName = ref('');
const createdOrder = ref(null);

const catalogSearch = ref('');
const catalogCategory = ref('');

const form = reactive({
  clientId: 1,
  clientName: '',
  contactPerson: '',
  phone: '',
  email: '',
  city: 'JAKARTA TIMUR',
  district: 'Makasar',
  subdistrict: 'Kebon Pala',
  address: '',
  projectName: '',
  category: 'Sarana & Fasilitas Belajar',
  urgency: 'Normal',
  targetDeliveryDate: '',
  fundingSource: '',
  description: '',
  notes: '',
  items: [],
});

const customItem = reactive({
  name: '',
  sku: 'CUSTOM',
  qty: 1,
  unit: 'Unit',
  price: 0,
  specs: '',
  isCatalog: false,
});

// Wilayah DKI Jakarta Data
const availableCities = computed(() => {
  return Object.keys(store.wilayahDki || {});
});

const availableDistricts = computed(() => {
  if (!form.city || !store.wilayahDki?.[form.city]) return [];
  return Object.keys(store.wilayahDki[form.city]);
});

const availableSubdistricts = computed(() => {
  if (!form.city || !form.district || !store.wilayahDki?.[form.city]?.[form.district]) return [];
  return store.wilayahDki[form.city][form.district];
});

const onCityChange = () => {
  form.district = '';
  form.subdistrict = '';
};

const onDistrictChange = () => {
  form.subdistrict = '';
};

const onClientSelect = () => {
  const c = store.clients.find((item) => item.id === Number(form.clientId));
  if (c) {
    form.clientName = c.name;
    form.contactPerson = c.contactPerson || '';
    form.phone = c.phone || '';
    form.email = c.email || '';
    form.city = c.city || 'JAKARTA TIMUR';
    form.district = c.district || '';
    form.subdistrict = c.subdistrict || '';
    form.address = c.address || '';
  }
};

const totalQty = computed(() => {
  return form.items.reduce((sum, it) => sum + Number(it.qty || 0), 0);
});

const subtotal = computed(() => {
  return form.items.reduce((sum, it) => sum + (Number(it.qty || 0) * Number(it.price || 0)), 0);
});

const tax = computed(() => {
  return Math.round(subtotal.value * 0.11);
});

const grandTotal = computed(() => {
  return subtotal.value + tax.value;
});

const availableCategories = computed(() => {
  const cats = new Set(store.products.map((p) => p.category).filter(Boolean));
  return Array.from(cats);
});

const filteredCatalog = computed(() => {
  let list = store.products || [];
  if (catalogCategory.value) {
    list = list.filter((p) => p.category === catalogCategory.value);
  }
  if (catalogSearch.value) {
    const q = catalogSearch.value.toLowerCase();
    list = list.filter((p) =>
      p.name.toLowerCase().includes(q) ||
      (p.sku && p.sku.toLowerCase().includes(q))
    );
  }
  return list;
});

const openCatalogModal = () => {
  catalogModalOpen.value = true;
};

const addCatalogItemToForm = (prod) => {
  const existing = form.items.find((it) => it.sku === prod.sku);
  if (existing) {
    existing.qty += 1;
    toast.info(`Kuantitas ${prod.name} ditambah (+1)`);
  } else {
    form.items.push({
      productId: prod.id,
      sku: prod.sku,
      name: prod.name,
      specs: '',
      qty: 1,
      unit: prod.unit || 'Unit',
      price: Number(prod.price || 0),
      isCatalog: true,
    });
    toast.success(`${prod.name} ditambahkan ke daftar kebutuhan`);
  }
};

const openCustomItemModal = () => {
  customItem.name = '';
  customItem.sku = 'CUSTOM';
  customItem.qty = 1;
  customItem.unit = 'Unit';
  customItem.price = 0;
  customItem.specs = '';
  customItemModalOpen.value = true;
};

const saveCustomItem = () => {
  if (!customItem.name) {
    toast.error('Nama barang wajib diisi!');
    return;
  }
  form.items.push({
    productId: null,
    sku: `CST-${Math.floor(1000 + Math.random() * 9000)}`,
    name: customItem.name,
    specs: customItem.specs || '',
    qty: Number(customItem.qty || 1),
    unit: customItem.unit || 'Unit',
    price: Number(customItem.price || 0),
    isCatalog: false,
  });
  customItemModalOpen.value = false;
  toast.success(`Item khusus "${customItem.name}" berhasil ditambahkan`);
};

const removeItem = (idx) => {
  form.items.splice(idx, 1);
};

const handleFileUpload = (e) => {
  const file = e.target.files[0];
  if (file) {
    uploadedFileName.value = file.name;
    toast.success(`Berkas "${file.name}" siap dilampirkan.`);
  }
};

const resetForm = () => {
  form.projectName = '';
  form.description = '';
  form.notes = '';
  form.items = [];
  uploadedFileName.value = '';
};

const createNewAnother = () => {
  successModalOpen.value = false;
  resetForm();
  onClientSelect();
};

const viewInTracking = (orderCode) => {
  successModalOpen.value = false;
  setTab('tracking');
  if (orderCode) {
    trackingInput.value = orderCode;
    handleSearchResi();
  }
};

const handleSubmitRequest = () => {
  if (form.items.length === 0) {
    toast.error('Daftar barang kebutuhan tidak boleh kosong!');
    return;
  }
  if (!form.projectName) {
    toast.error('Judul pengadaan kebutuhan wajib diisi!');
    return;
  }

  isSubmitting.value = true;
  setTimeout(() => {
    const newOrder = storeActions.submitClientOrderRequest({
      clientId: form.clientId,
      clientName: form.clientName,
      contactPerson: form.contactPerson,
      phone: form.phone,
      email: form.email,
      address: `${form.address}, ${form.subdistrict}, ${form.district}, ${form.city}`,
      projectName: form.projectName,
      urgency: form.urgency,
      targetDeliveryDate: form.targetDeliveryDate,
      items: form.items,
      notes: form.notes,
      supportingDocument: uploadedFileName.value || null,
    });

    isSubmitting.value = false;
    createdOrder.value = newOrder;
    successModalOpen.value = true;
    toast.success('Pengajuan kebutuhan berhasil terkirim ke Marketing CV Alpharo!');
  }, 400);
};

// ==========================================
// 2. STATE & LOGIC: LACAK RESI & BAST (TAB 2)
// ==========================================
const trackingInput = ref('ORD-2026-001');
const searchedOrder = ref(null);
const hasSearched = ref(false);
const confirmModalOpen = ref(false);

const receiptForm = reactive({
  recipientName: '',
  phone: '',
  notes: 'Barang telah diperiksa fisik lengkap dan sesuai spesifikasi Surat Jalan.',
});

const handleSearchResi = () => {
  hasSearched.value = true;
  const q = trackingInput.value.trim().toUpperCase();
  if (!q) {
    searchedOrder.value = null;
    return;
  }

  const found = store.orders.find((o) =>
    o.orderNumber.toUpperCase().includes(q) ||
    (o.documents?.suratJalan?.number && o.documents.suratJalan.number.toUpperCase().includes(q))
  );

  searchedOrder.value = found || null;
  if (!found) {
    toast.error(`Pesanan dengan resi "${q}" tidak ditemukan.`);
  } else {
    toast.success(`Data pesanan ${found.orderNumber} berhasil ditemukan.`);
  }
};

const trackingStages = computed(() => {
  if (!searchedOrder.value) return [];
  const status = searchedOrder.value.fulfillmentStatus || searchedOrder.value.status;
  const isDone = status === 'Selesai';
  const isShipping = status === 'Sedang Dikirim';
  const isReady = status === 'Siap Dikirim' || isShipping || isDone;

  return [
    { label: 'Pengajuan', detail: 'Tercatat', done: true, current: false },
    { label: 'Verifikasi', detail: 'Stok & SPJ', done: true, current: false },
    { label: 'Gudang', detail: 'Barang Siap', done: isReady, current: !isReady },
    { label: 'Pengiriman', detail: isShipping ? 'Dalam Armada' : isDone ? 'Tiba di Lokasi' : 'Antrean Truk', done: isDone, current: isShipping },
    { label: 'Selesai BAST', detail: isDone ? 'Diterima PIC' : 'Menunggu Serah Terima', done: isDone, current: false },
  ];
});

const shipmentDetail = computed(() => {
  return {
    expedition: 'Armada Internal Logistik CV Alpharo',
    vehiclePlate: 'B 9421 QK (Truk Colt Diesel)',
    driverName: 'Pak Joko Triyono (0812-9844-1234)',
    eta: 'Hari ini (Pukul 13:00 - 15:00 WIB)',
  };
});

const openConfirmModal = () => {
  receiptForm.recipientName = searchedOrder.value?.contactPerson || '';
  receiptForm.phone = searchedOrder.value?.phone || '';
  confirmModalOpen.value = true;
};

const submitReceiptConfirmation = () => {
  if (!searchedOrder.value) return;

  storeActions.confirmClientReceipt(searchedOrder.value.orderNumber, {
    recipientName: receiptForm.recipientName,
    notes: receiptForm.notes,
  });

  confirmModalOpen.value = false;
  toast.success('Penerimaan barang telah berhasil dikonfirmasi! BAST diterbitkan.');
};

// ==========================================
// 3. STATE & LOGIC: PEMBAYARAN & TERMIN (TAB 3)
// ==========================================
const paymentClientFilter = ref('all');
const paymentStatusFilter = ref('all');

const paymentFilterTabs = [
  { key: 'all', label: 'Semua Tagihan' },
  { key: 'unpaid', label: 'Belum Lunas' },
  { key: 'paid', label: 'Lunas' },
];

const filteredInvoices = computed(() => {
  let list = store.invoices || [];

  if (paymentClientFilter.value !== 'all') {
    list = list.filter((inv) => inv.clientId === Number(paymentClientFilter.value));
  }

  if (paymentStatusFilter.value === 'unpaid') {
    list = list.filter((inv) => inv.status !== 'Lunas');
  } else if (paymentStatusFilter.value === 'paid') {
    list = list.filter((inv) => inv.status === 'Lunas');
  }

  return list;
});

const totalInvoiceValue = computed(() =>
  filteredInvoices.value.reduce((sum, inv) => sum + (inv.totalAmount || 0), 0)
);

const totalPaid = computed(() =>
  filteredInvoices.value.reduce((sum, inv) => sum + (inv.paidAmount || 0), 0)
);

const totalOutstanding = computed(() =>
  filteredInvoices.value.reduce((sum, inv) => sum + (inv.remainingAmount || 0), 0)
);

const paidCount = computed(() =>
  filteredInvoices.value.filter((inv) => inv.status === 'Lunas').length
);

const paidPercentage = (inv) => {
  if (!inv.totalAmount) return 0;
  return Math.min(100, Math.round(((inv.paidAmount || 0) / inv.totalAmount) * 100));
};

const showPaymentModal = ref(false);
const paymentForm = ref({
  invoiceNumber: '',
  paymentType: 'Termin',
  terminNumber: 1,
  amount: 0,
  paymentMethod: 'Transfer BCA',
  paymentDate: new Date().toISOString().split('T')[0],
  referenceNumber: `TRF-${Math.floor(100000 + Math.random() * 900000)}`,
  notes: '',
  proofFile: 'BUKTI_TRANSFER_REKANAN.pdf',
});

const openPaymentModal = (inv = null, termin = null) => {
  const targetInv = inv || filteredInvoices.value[0] || store.invoices[0];
  if (!targetInv) {
    toast.error('Belum ada data invoice untuk dikonfirmasi.');
    return;
  }

  paymentForm.value.invoiceNumber = targetInv.invoiceNumber;
  if (termin) {
    paymentForm.value.paymentType = 'Termin';
    paymentForm.value.terminNumber = termin.termNumber;
    paymentForm.value.amount = termin.amount || targetInv.remainingAmount;
  } else {
    paymentForm.value.amount = targetInv.remainingAmount || targetInv.totalAmount;
    paymentForm.value.paymentType = targetInv.remainingAmount === targetInv.totalAmount ? 'Termin' : 'Penuh';
  }

  paymentForm.value.referenceNumber = `TRF-${Math.floor(100000 + Math.random() * 900000)}`;
  paymentForm.value.paymentDate = new Date().toISOString().split('T')[0];
  showPaymentModal.value = true;
};

const onModalInvoiceChange = () => {
  const inv = store.invoices.find((i) => i.invoiceNumber === paymentForm.value.invoiceNumber);
  if (inv) {
    paymentForm.value.amount = inv.remainingAmount || inv.totalAmount;
  }
};

const submitPaymentConfirmation = () => {
  const inv = store.invoices.find((i) => i.invoiceNumber === paymentForm.value.invoiceNumber);
  if (!inv) {
    toast.error('Invoice tidak ditemukan.');
    return;
  }

  storeActions.recordPayment({
    invoiceId: inv.id,
    amount: paymentForm.value.amount,
    paymentDate: paymentForm.value.paymentDate,
    paymentMethod: paymentForm.value.paymentMethod,
    referenceNumber: paymentForm.value.referenceNumber,
    paymentType: paymentForm.value.paymentType,
    terminNumber: paymentForm.value.terminNumber,
    notes: paymentForm.value.notes,
    proofFile: paymentForm.value.proofFile,
  });

  storeActions.addNotification(
    'FINANCE',
    'Konfirmasi Pembayaran Rekanan',
    `Rekanan ${inv.clientName} telah mengirimkan konfirmasi pembayaran Rp ${Number(paymentForm.value.amount || 0).toLocaleString('id-ID')} untuk ${inv.invoiceNumber}.`,
    'payment',
    '/finance/payments'
  );

  toast.success('Konfirmasi pembayaran berhasil dikirim ke bagian Keuangan Alpharo!');
  showPaymentModal.value = false;
};

// ==========================================
// LIFECYCLE HOOK
// ==========================================
onMounted(() => {
  // Query param sync
  const qTab = route.query.tab;
  if (qTab === 'payment' || qTab === 'pembayaran') {
    activeTab.value = 'payment';
  } else if (qTab === 'tracking' || qTab === 'lacak' || route.query.resi) {
    activeTab.value = 'tracking';
    if (route.query.resi) {
      trackingInput.value = route.query.resi;
    }
    handleSearchResi();
  } else {
    activeTab.value = 'request';
  }

  // Prepopulate client data in request form
  onClientSelect();
});
</script>
