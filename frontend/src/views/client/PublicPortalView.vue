<template>
  <div class="portal-shell ui-scope min-h-screen flex flex-col font-sans text-slate-800">
    <a href="#portal-content" class="skip-link">Lewati ke konten</a>
    <header class="company-portal-header sticky top-0 z-40">
      <div class="company-portal-header-inner">
        <button type="button" class="company-portal-brand" aria-label="Beranda profil CV Alpharo Artha Jaya" @click="setTab('profile')">
          <AlpharoLogo theme="dark" size="md" :show-tagline="true" />
        </button>
        <nav class="company-portal-nav" aria-label="Navigasi portal publik">
          <button v-for="tab in portalTabs" :key="tab.key" type="button" :aria-current="activeTab === tab.key ? 'page' : undefined" :class="{ 'is-active': activeTab === tab.key }" @click="setTab(tab.key)">
            <component :is="tab.icon" class="size-4" />
            <span>{{ tab.label }}</span>
          </button>
        </nav>
        <Link href="/login" class="company-staff-login"><LogIn class="size-4" /><span>Login Staff Internal</span></Link>
      </div>
    </header>

    <section v-if="activeTab !== 'profile'" class="bg-white border-b border-slate-200 px-5 py-7">
      <div class="max-w-7xl mx-auto">
        <p class="text-[11px] text-alpharo-600 font-semibold uppercase tracking-widest mb-2">Portal Publik Rekanan</p>
        <h1 class="text-xl sm:text-2xl font-semibold text-slate-900">{{ portalTabs.find(tab => tab.key === activeTab)?.title }}</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-2">Layanan mandiri pengadaan, logistik & pembayaran CV Alpharo Artha Jaya.</p>
      </div>
    </section>

    <!-- CONTENT BODY -->
    <main id="portal-content" class="flex-1 w-full" :class="activeTab === 'profile' ? 'bg-white' : 'max-w-7xl mx-auto p-4 sm:p-6 lg:p-8'">
      
      <!-- ============================================================== -->
      <!-- TAB 1: PROFIL PERUSAHAAN (COMPANY PROFILE) -->
      <!-- ============================================================== -->
      <CompanyProfile v-if="activeTab === 'profile'" @navigate="setTab" />

      <!-- ============================================================== -->
      <!-- TAB 2: AJUKAN KEBUTUHAN BARANG (PENGADAAN MANDIRI REKANAN) -->
      <!-- ============================================================== -->
      <div v-if="activeTab === 'request'" class="space-y-6">
        <!-- ALUR PROSES BANNER -->
        <div class="bg-linear-to-r from-alpharo-50 via-alpharo-50 to-sky-50 border border-alpharo-200/90 rounded-2xl p-4 sm:p-5 shadow-2xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-alpharo-600 text-white flex items-center justify-center shrink-0 shadow-xs">
              <ClipboardCheck class="w-5 h-5 text-amber-300" />
            </div>
            <div>
              <h2 class="text-xs sm:text-sm font-semibold text-slate-900">Alur Pengadaan Mandiri Rekanan & Sekolah</h2>
              <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">
                1. Ajukan Kebutuhan &rarr; 2. Verifikasi Stok & Penawaran (Marketing) &rarr; 3. Approval Direktur & SPJ &rarr; 4. Pengiriman Gudang &rarr; 5. Konfirmasi BAST & Pembayaran Tagihan.
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2 text-xs font-medium text-slate-500 shrink-0">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white border border-alpharo-200 rounded-full text-[11px] font-semibold text-alpharo-600 shadow-2xs">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              2.196 SKU Katalog Terhubung
            </span>
          </div>
        </div>

        <!-- MAIN PROCUREMENT REQUEST FORM -->
        <form class="space-y-6" @submit.prevent="handleSubmitRequest">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            
            <!-- CARD 1: DATA INSTANSI & PEMOHON -->
            <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-2 border-t-slate-200">
              <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2">
                  <School class="w-4 h-4 text-alpharo-600" />
                  <h3 class="font-semibold text-slate-800 text-sm">1. Data Instansi & Pemohon</h3>
                </div>
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Identitas PIC</span>
              </div>

              <div class="p-5 space-y-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Nama Sekolah / Instansi Rekanan <span class="text-rose-500">*</span>
                  </label>
                  <select
                    v-model="form.clientId"
                    required
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-alpharo-600/20 focus:border-alpharo-600"
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
                      class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-600/20 focus:border-alpharo-600"
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
                      class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-600/20 focus:border-alpharo-600"
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
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-600/20 focus:border-alpharo-600"
                  />
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Pilih Staf Marketing yang Menangani <span class="text-rose-500">*</span>
                  </label>
                  <select
                    v-model="form.marketingName"
                    required
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-alpharo-600/20 focus:border-alpharo-600"
                  >
                    <option value="" disabled>Pilih Staf Marketing yang Menangani</option>
                    <option v-for="m in marketingOptions" :key="m.name" :value="m.name">
                      {{ m.name }} &bull; {{ m.title || 'Marketing Officer' }} ({{ m.phone }})
                    </option>
                  </select>
                  <p class="text-[11px] text-slate-500 mt-1">
                    Pilih staf pemasaran CV Alpharo yang akan mengawal pengadaan dan menyusun penawaran resmi untuk instansi Anda.
                  </p>
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
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-600/20 focus:border-alpharo-600"
                  ></textarea>
                </div>
              </div>
            </div>

            <!-- CARD 2: INFORMASI PROYEK & RENCANA PENGADAAN -->
            <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-2 border-t-slate-200">
              <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2">
                  <FolderKanban class="w-4 h-4 text-amber-600" />
                  <h3 class="font-semibold text-slate-800 text-sm">2. Ruang Lingkup & Waktu Kebutuhan</h3>
                </div>
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Target Waktu</span>
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
                    Deskripsi Kebutuhan
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
          <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-2 border-t-slate-200">
            <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
              <div>
                <div class="flex items-center gap-2">
                  <Boxes class="w-4 h-4 text-emerald-600" />
                  <h3 class="font-semibold text-slate-800 text-sm">3. Rincian Barang Kebutuhan yang Diajukan</h3>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                  Pilih item dari master katalog resmi CV Alpharo (2.196 SKU) atau tambahkan spesifikasi khusus instansi Anda.
                </p>
              </div>

              <!-- DUAL ACTION BUTTONS (DENGAN GRADASI ICON PUTIH SESUAI DESIGN GUIDELINE) -->
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 px-3 py-2 bg-alpharo-600 text-white rounded-xl text-xs font-semibold shadow-xs transition-all cursor-pointer hover:bg-alpharo-700"
                  @click="openCatalogModal"
                >
                  <Search class="w-3.5 h-3.5 text-white stroke-[2.5]" />
                  <span>Pilih dari Master Katalog</span>
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 px-3 py-2 bg-alpharo-600 text-white rounded-xl text-xs font-semibold shadow-xs transition-all cursor-pointer hover:bg-alpharo-700"
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
                  <table class="ui-table-wide ui-table w-full text-left text-xs border-collapse">
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
                          <div class="font-semibold text-slate-900">{{ item.name }}</div>
                          <div class="flex items-center gap-2 mt-0.5">
                            <span class="font-mono text-[11px] text-slate-400">{{ item.sku || 'CUSTOM' }}</span>
                            <span v-if="item.isCatalog" class="text-[9px] font-semibold text-alpharo-700 bg-alpharo-50 px-1.5 py-0.5 rounded border border-alpharo-200">Katalog</span>
                            <span v-else class="text-[9px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">Custom</span>
                          </div>
                        </td>
                        <td class="py-3 px-3">
                          <input
                            v-model="item.specs"
                            type="text"
                            placeholder="Merk/tipe/warna tertentu..."
                            class="w-full px-2 py-1 text-xs border border-slate-200 rounded-lg focus:border-alpharo-500 bg-white"
                          />
                        </td>
                        <td class="py-3 px-3 text-center">
                          <input
                            v-model.number="item.qty"
                            type="number"
                            min="1"
                            required
                            class="w-20 px-2 py-1 text-xs text-center border border-slate-300 rounded-lg font-semibold focus:border-alpharo-500"
                          />
                        </td>
                        <td class="py-3 px-3 text-center">
                          <span class="font-semibold text-slate-700 bg-slate-100 px-2 py-1 rounded text-xs">
                            {{ item.unit }}
                          </span>
                        </td>
                        <td class="py-3 px-3 text-right">
                          <input
                            v-model.number="item.price"
                            type="number"
                            min="0"
                            step="500"
                            class="w-28 px-2 py-1 text-xs text-right font-mono border border-slate-300 rounded-lg focus:border-alpharo-500"
                          />
                        </td>
                        <td class="py-3 px-3 text-right font-mono font-semibold text-slate-900 whitespace-nowrap">
                          Rp {{ (Number(item.qty || 0) * Number(item.price || 0)).toLocaleString('id-ID') }}
                        </td>
                        <td class="py-3 px-3 text-center">
                          <button
                            type="button"
                            class="p-1.5 rounded-lg bg-linear-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white shadow-xs transition-all cursor-pointer inline-flex items-center justify-center"
                            title="Hapus Barang"
                            @click="removeItem(idx)"
                          >
                            <Trash2 class="w-3.5 h-3.5 text-white stroke-[2.2]" />
                          </button>
                        </td>
                      </tr>

                      <!-- EMPTY STATE -->
                      <tr v-if="form.items.length === 0">
                        <td colspan="8" class="py-10 text-center"><div class="ui-empty-state">
                          <div class="max-w-sm mx-auto text-center space-y-2">
                            <ShoppingBag class="w-10 h-10 text-slate-300 mx-auto" />
                            <p class="text-xs font-semibold text-slate-700">Belum Ada Barang yang Ditambahkan</p>
                            <p class="text-[11px] text-slate-400">
                              Klik tombol <strong>"Pilih dari Master Katalog"</strong> untuk memilih dari 2.196 SKU atau gunakan <strong>"Tambah Item Custom"</strong> untuk kebutuhan spesifik.
                            </p>
                          </div>
                        </div></td>
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
                      <span class="font-mono font-semibold text-slate-900">Rp {{ subtotal.toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                      <span>Estimasi PPN (11%):</span>
                      <span class="font-mono text-slate-800">Rp {{ tax.toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-1.5 border-t border-slate-200 text-sm font-semibold text-alpharo-600">
                      <span>Total Estimasi Proyek:</span>
                      <span class="font-mono text-base">Rp {{ grandTotal.toLocaleString('id-ID') }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>


          <!-- SUBMIT ACTION BAR -->
          <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-xs p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
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
                class="px-6 py-2.5 bg-alpharo-600 text-white font-semibold rounded-xl text-xs sm:text-sm shadow-sm shadow-alpharo-900/30 transition-all cursor-pointer flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-alpharo-700"
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
        <!-- SEARCH RESI & INVOICE BOX -->
        <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-6 sm:p-8 shadow-xs text-center space-y-4 border-t-2 border-t-slate-200">
          <div class="max-w-xl mx-auto">
            <h2 class="text-base sm:text-lg font-semibold text-slate-900">Lacak Pengiriman & Pembayaran Pesanan Rekanan</h2>
            <p class="text-xs text-slate-500 mt-1">
              Masukkan Nomor Resi, Surat Jalan, Nomor Order, atau Nomor Invoice untuk memantau pengiriman barang dan melakukan pembayaran tagihan.
            </p>

            <form class="mt-4 flex flex-col sm:flex-row items-center gap-2" @submit.prevent="handleSearchResi">
              <div class="relative w-full">
                <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  v-model="trackingInput"
                  type="text"
                  required
                  placeholder="Ketik nomor resi / surat jalan / no. order / no. invoice..."
                  class="w-full pl-10 pr-4 py-3 text-xs sm:text-sm rounded-xl border border-slate-300 font-mono font-semibold focus:outline-hidden focus:ring-2 focus:ring-alpharo-600 bg-slate-50/50"
                />
              </div>
              <button
                type="submit"
                class="w-full sm:w-auto px-6 py-3 bg-alpharo-600 text-white font-semibold rounded-xl text-xs sm:text-sm shadow-xs transition-all shrink-0 cursor-pointer flex items-center justify-center gap-2 hover:bg-alpharo-700"
              >
                <Truck class="w-4 h-4 text-amber-300" />
                <span>Lacak & Periksa Tagihan</span>
              </button>
            </form>
          </div>

          <!-- QUICK SUGGESTIONS -->
          <div v-if="sampleTrackingOrders.length > 0" class="pt-2 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-500">
            <span>Pesanan aktif:</span>
            <button
              v-for="ex in sampleTrackingOrders"
              :key="ex"
              type="button"
              class="font-mono px-2.5 py-1 bg-slate-100 hover:bg-alpharo-50 text-slate-700 hover:text-alpharo-600 rounded-lg border border-slate-200 cursor-pointer transition-colors"
              @click="trackingInput = ex; handleSearchResi();"
            >
              {{ ex }}
            </button>
          </div>
        </div>

        <!-- SEARCH RESULT -->
        <div v-if="searchedOrder" class="space-y-6">
          <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-6 shadow-xs space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 pb-4 border-b border-slate-100">
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-mono font-semibold text-slate-900 text-lg">{{ searchedOrder.orderNumber }}</span>
                  <StatusBadge :status="searchedOrder.fulfillmentStatus || searchedOrder.status" />
                </div>
                <h3 class="text-sm sm:text-base font-semibold text-slate-800">{{ searchedOrder.projectName }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">
                  Instansi: <strong class="text-slate-800">{{ searchedOrder.clientName }}</strong> &bull; Tanggal: {{ searchedOrder.date }}
                  <span v-if="searchedOrder.marketingName">&bull; Marketing PIC: <strong class="text-slate-800">{{ searchedOrder.marketingName }}</strong></span>
                </p>
              </div>

              <!-- CONFIRM RECEIPT CTA BUTTON (FR-CL-05) -->
              <div v-if="searchedOrder.fulfillmentStatus !== 'Selesai'" class="text-left sm:text-right">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 px-5 py-2.5 bg-linear-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold rounded-xl text-xs shadow-xs transition-all cursor-pointer"
                  @click="openConfirmModal"
                >
                  <CheckCircle2 class="w-4 h-4 stroke-[2.5]" />
                  <span>Konfirmasi Terima Barang (BAST)</span>
                </button>
                <p class="text-[11px] text-slate-400 mt-1">Klik jika barang telah tiba lengkap di sekolah</p>
              </div>

              <div v-else class="text-left sm:text-right">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold text-xs">
                  <CheckCircle2 class="w-4 h-4" />
                  Barang Selesai Diterima (BAST)
                </span>
                <p class="text-[11px] text-slate-400 mt-1">BAST Fisik telah ditandatangani</p>
              </div>
            </div>

            <!-- STEPPER PROGRESS TAHAPAN (REAL-TIME VISUAL PIPELINE) -->
            <div>
              <h4 class="text-xs font-semibold text-slate-900 uppercase tracking-wider mb-4">
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
                      ? 'bg-alpharo-50 border-alpharo-600 ring-2 ring-alpharo-500/20 text-alpharo-600 font-semibold'
                      : 'bg-slate-50 border-slate-200 text-slate-400',
                    'p-3 rounded-xl border text-center transition-all'
                  ]"
                >
                  <div class="flex items-center justify-center mb-1.5">
                    <component
                      :is="st.done ? CheckCircle2 : st.current ? Clock : Circle"
                      :class="[
                        st.done ? 'text-emerald-600' : st.current ? 'text-alpharo-600 animate-pulse' : 'text-slate-300',
                        'w-4 h-4'
                      ]"
                    />
                  </div>
                  <p class="text-[11px] font-semibold uppercase leading-tight">{{ st.label }}</p>
                  <p class="text-[11px] mt-0.5 leading-tight opacity-80">{{ st.detail }}</p>
                </div>
              </div>
            </div>

            <!-- SHIPMENT LOGISTICS DETAIL -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
              <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                <h5 class="font-semibold text-slate-900 flex items-center gap-1.5">
                  <Truck class="w-4 h-4 text-alpharo-600" />
                  <span>Informasi Logistik & Pengemudi</span>
                </h5>
                <div class="flex justify-between py-1 border-b border-slate-200/60">
                  <span class="text-slate-500">Ekspedisi / Armada:</span>
                  <span class="font-semibold text-slate-800">{{ shipmentDetail.expedition }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60">
                  <span class="text-slate-500">No. Polisi Armada:</span>
                  <span class="font-mono font-semibold text-slate-900">{{ shipmentDetail.vehiclePlate }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60">
                  <span class="text-slate-500">Nama Sopir / Driver:</span>
                  <span class="font-semibold text-slate-800">{{ shipmentDetail.driverName }}</span>
                </div>
                <div class="flex justify-between py-1">
                  <span class="text-slate-500">Estimasi Tiba di Lokasi:</span>
                  <span class="font-semibold text-emerald-700">{{ shipmentDetail.eta }}</span>
                </div>
              </div>

              <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                <h5 class="font-semibold text-slate-900 flex items-center gap-1.5">
                  <MapPin class="w-4 h-4 text-rose-500" />
                  <span>Tujuan Pengiriman</span>
                </h5>
                <p class="font-semibold text-slate-800">{{ searchedOrder.clientName }}</p>
                <p class="text-slate-600 leading-relaxed">{{ searchedOrder.address || 'Alamat instansi terdaftar di DKI Jakarta' }}</p>
                <p class="text-[11px] text-slate-400 pt-1">PIC Penerima: {{ searchedOrder.contactPerson || 'Petugas Sekolah' }}</p>
              </div>
            </div>

            <!-- ITEMS SUMMARY -->
            <div>
              <h4 class="text-xs font-semibold text-slate-900 uppercase tracking-wider mb-2">
                Rincian Barang dalam Pengiriman
              </h4>
              <div class="border border-slate-200 rounded-xl overflow-hidden">
                <table class="ui-table-wide ui-table w-full text-left text-xs">
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
                      <td class="py-2.5 px-3 text-center font-semibold text-slate-800">{{ it.qty }}</td>
                      <td class="py-2.5 px-3 text-center text-slate-600">{{ it.unit }}</td>
                      <td class="py-2.5 px-3 text-right font-mono text-slate-700">Rp {{ Number(it.price || 0).toLocaleString('id-ID') }}</td>
                      <td class="py-2.5 px-3 text-right font-mono font-semibold text-slate-900">Rp {{ (Number(it.qty || 0) * Number(it.price || 0)).toLocaleString('id-ID') }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- ============================================================== -->
            <!-- SEKSI PEMBAYARAN & TAGIHAN TERPADU DI DALAM LACAK PESANAN -->
            <!-- ============================================================== -->
            <div class="mt-6 pt-6 border-t border-slate-200">
              <div class="ui-panel bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden border-t-4 border-t-emerald-600">
                <!-- Header Kartu Pembayaran -->
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/70">
                  <div class="space-y-1">
                    <div class="flex items-center gap-2">
                      <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <CreditCard class="w-4 h-4 stroke-[2.2]" />
                      </div>
                      <div>
                        <h4 class="text-sm font-semibold text-slate-900">Status Pembayaran & Tagihan Pesanan</h4>
                        <p class="text-[11px] text-slate-500">
                          Informasi faktur tagihan dan konfirmasi bukti transfer untuk pesanan <strong>{{ searchedOrder.orderNumber }}</strong>
                        </p>
                      </div>
                    </div>
                  </div>

                  <div v-if="searchedOrderInvoice" class="flex items-center gap-3">
                    <StatusBadge :status="searchedOrderInvoice.status" />
                    <button
                      v-if="searchedOrderInvoice.remainingAmount > 0"
                      type="button"
                      class="px-4 py-2 bg-linear-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold rounded-xl text-xs shadow-xs transition-all cursor-pointer flex items-center gap-1.5"
                      @click="openPaymentModal(searchedOrderInvoice)"
                    >
                      <UploadCloud class="w-4 h-4" />
                      <span>Konfirmasi Pembayaran</span>
                    </button>
                    <span v-else class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-100 text-emerald-800 text-xs font-semibold">
                      <CheckCircle2 class="w-4 h-4" />
                      Tagihan Lunas 100%
                    </span>
                  </div>
                </div>

                <!-- Konten Tagihan Jika Invoice Ditemukan -->
                <div v-if="searchedOrderInvoice" class="p-5 space-y-6">
                  <!-- Ringkasan Finansial Tagihan -->
                  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                      <p class="text-[11px] font-semibold text-slate-400 uppercase">No. Faktur Tagihan</p>
                      <p class="text-sm font-semibold font-mono text-slate-900 mt-1">{{ searchedOrderInvoice.invoiceNumber }}</p>
                      <p class="text-[11px] text-slate-500 mt-0.5">Jatuh Tempo: {{ searchedOrderInvoice.dueDate || '-' }}</p>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                      <p class="text-[11px] font-semibold text-slate-400 uppercase">Total Nilai Tagihan</p>
                      <p class="text-sm font-semibold font-mono text-slate-900 mt-1">
                        Rp {{ Number(searchedOrderInvoice.totalAmount || 0).toLocaleString('id-ID') }}
                      </p>
                      <p class="text-[11px] text-emerald-600 mt-0.5 font-medium">Pembayaran Penuh (100%)</p>
                    </div>

                    <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200">
                      <p class="text-[11px] font-semibold text-emerald-700 uppercase">Dana Terbayar</p>
                      <p class="text-sm font-semibold font-mono text-emerald-800 mt-1">
                        Rp {{ Number(searchedOrderInvoice.paidAmount || (searchedOrderInvoice.totalAmount - searchedOrderInvoice.remainingAmount) || 0).toLocaleString('id-ID') }}
                      </p>
                      <p class="text-[11px] text-emerald-600 mt-0.5 font-medium">{{ paidPercentage(searchedOrderInvoice) }}% Lunas</p>
                    </div>

                    <div class="p-3.5 rounded-xl bg-rose-50/70 border border-rose-200">
                      <p class="text-[11px] font-semibold text-rose-700 uppercase">Sisa Tagihan</p>
                      <p class="text-sm font-semibold font-mono text-rose-800 mt-1">
                        Rp {{ Number(searchedOrderInvoice.remainingAmount || 0).toLocaleString('id-ID') }}
                      </p>
                      <p class="text-[11px] text-rose-600 mt-0.5">
                        {{ searchedOrderInvoice.remainingAmount > 0 ? 'Menunggu Pelunasan' : 'Tuntas Lunas' }}
                      </p>
                    </div>
                  </div>

                  <!-- Progress Bar Pelunasan -->
                  <div>
                    <div class="flex items-center justify-between text-xs text-slate-600 mb-1.5">
                      <span>Progres Pelunasan Tagihan:</span>
                      <strong class="text-emerald-700 font-mono">{{ paidPercentage(searchedOrderInvoice) }}%</strong>
                    </div>
                    <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                      <div
                        class="h-full bg-emerald-500 rounded-full transition-all duration-300"
                        :style="{ width: paidPercentage(searchedOrderInvoice) + '%' }"
                      ></div>
                    </div>
                  </div>

                  <!-- Rekening Resmi Pembayaran -->
                  <div class="p-4 rounded-xl bg-linear-to-r from-slate-900 via-slate-800 to-alpharo-900 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-white/20 text-white uppercase tracking-wider">
                        Rekening Resmi Pembayaran (Tunggal & Tetap)
                      </span>
                      <p class="text-xs text-slate-200">Transfer ditujukan langsung ke rekening resmi:</p>
                      <p class="text-sm font-semibold text-white">Bank BCA Jakarta &bull; a.n. CV ALPHARO ARTHA JAYA</p>
                    </div>
                    <div class="bg-white/10 px-4 py-2 rounded-xl border border-white/20 text-left sm:text-right">
                      <span class="text-[10px] text-amber-300 uppercase font-semibold">Nomor Rekening BCA</span>
                      <p class="font-mono text-base sm:text-lg font-bold text-white tracking-widest select-all">52323628813</p>
                    </div>
                  </div>

                  <!-- Status Pelunasan & Verifikasi Pembayaran Tagihan -->
                  <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="space-y-1">
                      <span class="text-xs font-semibold text-slate-800">Status Pembayaran & Verifikasi Tagihan</span>
                      <p class="text-xs text-slate-500">
                        {{ searchedOrderInvoice.remainingAmount <= 0 ? 'Seluruh kewajiban pembayaran tagihan pesanan ini telah tuntas lunas 100%.' : 'Kirimkan bukti transfer pembayaran tagihan untuk diverifikasi oleh Keuangan CV Alpharo Artha Jaya.' }}
                      </p>
                    </div>
                    <button
                      v-if="searchedOrderInvoice.remainingAmount > 0"
                      type="button"
                      class="px-4 py-2 bg-linear-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold rounded-xl text-xs shadow-xs transition-all cursor-pointer flex items-center gap-1.5 shrink-0"
                      @click="openPaymentModal(searchedOrderInvoice)"
                    >
                      <UploadCloud class="w-4 h-4" />
                      <span>Konfirmasi Pembayaran</span>
                    </button>
                    <span v-else class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-100 text-emerald-800 text-xs font-semibold shrink-0">
                      <CheckCircle2 class="w-4 h-4" />
                      Lunas 100%
                    </span>
                  </div>
                </div>

                <!-- Jika Belum Ada Invoice -->
                <div v-else class="p-6 text-center space-y-2 text-xs text-slate-500">
                  <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-1">
                    <Clock class="w-5 h-5" />
                  </div>
                  <p class="font-semibold text-slate-700 text-sm">Faktur Tagihan Sedang Disiapkan</p>
                  <p class="max-w-md mx-auto text-slate-500 leading-relaxed">
                    Faktur invoice resmi akan diterbitkan oleh Bagian Keuangan setelah pengajuan disetujui Direktur dan barang dipersiapkan di gudang.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- SEARCH NOT FOUND -->
        <div v-else-if="hasSearched && !searchedOrder" class="ui-panel bg-white rounded-xl border border-slate-200 p-8 text-center space-y-2">
          <AlertCircle class="w-10 h-10 text-amber-500 mx-auto" />
          <p class="text-sm font-semibold text-slate-800">Nomor Resi / Pesanan Tidak Ditemukan</p>
          <p class="text-xs text-slate-500 max-w-md mx-auto">
            Pastikan nomor yang Anda masukkan sesuai dengan format pada konfirmasi email atau bukti Surat Jalan (contoh: <strong class="font-mono text-alpharo-600">ORD-2026-XXXXX</strong> atau No. Faktur Invoice).
          </p>
        </div>

        <!-- DAFTAR PESANAN & STATUS TAGIHAN REKANAN -->
        <div class="mt-8 pt-6 border-t border-slate-200 space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h3 class="text-sm sm:text-base font-semibold text-slate-900">Daftar Pesanan & Status Tagihan Rekanan</h3>
              <p class="text-xs text-slate-500">Pilih salah satu pesanan untuk membuka informasi pelacakan pengiriman dan pembayaran secara lengkap.</p>
            </div>
            <button
              type="button"
              class="px-4 py-2 bg-linear-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold rounded-xl text-xs shadow-xs transition-all cursor-pointer flex items-center gap-1.5 self-start sm:self-auto"
              @click="openPaymentModal(null)"
            >
              <UploadCloud class="w-4 h-4" />
              <span>Konfirmasi Pembayaran Bebas</span>
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="ord in store.orders.slice(0, 4)"
              :key="ord.id"
              class="ui-panel bg-white rounded-xl border border-slate-200 p-4 shadow-2xs hover:border-alpharo-300 transition-all flex flex-col justify-between gap-3"
            >
              <div>
                <div class="flex items-center justify-between gap-2 mb-1.5">
                  <span class="font-mono font-semibold text-xs text-slate-900">{{ ord.orderNumber }}</span>
                  <StatusBadge :status="ord.fulfillmentStatus || ord.status" />
                </div>
                <h4 class="font-semibold text-xs text-slate-800 line-clamp-1">{{ ord.projectName }}</h4>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ ord.clientName }} &bull; {{ ord.date }}</p>
                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                  <span class="text-slate-500">Total Nilai:</span>
                  <span class="font-mono font-semibold text-slate-900">Rp {{ Number(ord.grandTotal || 0).toLocaleString('id-ID') }}</span>
                </div>
              </div>

              <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                <span class="text-[11px] text-slate-400">
                  {{ (store.invoices || []).find(i => i.orderNumber === ord.orderNumber) ? 'Invoice Terbit' : 'Dalam Proses' }}
                </span>
                <button
                  type="button"
                  class="px-3 py-1.5 bg-alpharo-50 hover:bg-alpharo-100 text-alpharo-700 border border-alpharo-200 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer"
                  @click="quickTrackAndPay(ord.orderNumber)"
                >
                  <Truck class="w-3.5 h-3.5" />
                  <span>Lacak & Bayar</span>
                </button>
              </div>
            </div>
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
              class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-alpharo-500"
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
          <table class="ui-table-wide ui-table w-full text-left text-xs">
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
                class="hover:bg-alpharo-50/40 transition-colors"
              >
                <td class="py-2.5 px-3">
                  <div class="font-mono font-semibold text-slate-800 text-[11px]">{{ prod.sku }}</div>
                  <div class="text-[11px] text-slate-400">{{ prod.category || 'Umum' }}</div>
                </td>
                <td class="py-2.5 px-3 font-medium text-slate-900">
                  {{ prod.name }}
                </td>
                <td class="py-2.5 px-3 text-center font-semibold text-slate-700">
                  <span class="bg-slate-100 px-2 py-0.5 rounded text-[11px]">{{ prod.unit }}</span>
                </td>
                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                  <span
                    :class="[
                      prod.stock > 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-1 ring-emerald-500/10' : 'bg-amber-50 text-amber-700 border-amber-200 ring-1 ring-amber-500/10',
                      'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border whitespace-nowrap'
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
                    class="px-3 py-1 bg-alpharo-600 text-white rounded-lg font-semibold text-xs shadow-2xs transition-all cursor-pointer whitespace-nowrap hover:bg-alpharo-700"
                    @click="addCatalogItemToForm(prod)"
                  >
                    Pilih
                  </button>
                </td>
              </tr>
              <tr v-if="filteredCatalog.length === 0">
                <td colspan="6" class="py-8 text-center text-xs text-slate-400"><div class="ui-empty-state">
                  Tidak ditemukan barang yang sesuai filter pencarian.
                </div></td>
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
              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 text-center font-semibold"
            />
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Satuan <span class="text-rose-500">*</span></label>
            <input
              v-model="customItem.unit"
              type="text"
              required
              placeholder="Unit / Set / Paket / Pcs"
              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 text-center font-semibold"
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
            class="px-4 py-1.5 bg-alpharo-600 hover:bg-alpharo-700 text-white rounded-lg font-semibold shadow-xs cursor-pointer"
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
          <p class="text-xl font-semibold font-mono text-alpharo-600 mt-1 tracking-wider">{{ createdOrder?.orderNumber }}</p>
          <p class="text-xs text-slate-600 mt-2 font-medium">{{ createdOrder?.projectName }}</p>
          <p class="text-xs text-slate-400">Total Estimasi: <strong class="text-slate-800">Rp {{ Number(createdOrder?.grandTotal || 0).toLocaleString('id-ID') }}</strong></p>
        </div>

        <div class="p-3 bg-alpharo-50/70 border border-alpharo-200 rounded-xl text-left text-xs text-alpharo-900 space-y-1">
          <p class="font-semibold flex items-center gap-1.5">
            <Info class="w-4 h-4 text-alpharo-600 shrink-0" />
            <span>Langkah Selanjutnya:</span>
          </p>
          <p class="text-[11px] text-alpharo-800">
            Staf Marketing yang Anda pilih, <strong>{{ createdOrder?.marketingName || 'Tim Marketing CV Alpharo' }}</strong>, akan segera memverifikasi ketersediaan stok & menghubungi PIC pemesan ({{ createdOrder?.contactPerson }}) untuk penerbitan Surat Penawaran Harga (SPH). Anda dapat memantau setiap proses melalui tab Lacak Pesanan & Pembayaran.
          </p>
        </div>

        <div class="flex items-center justify-center gap-3 pt-2">
          <button
            type="button"
            class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 text-white rounded-xl font-semibold text-xs shadow-xs transition-colors cursor-pointer flex items-center gap-1.5"
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
          <p class="font-semibold flex items-center gap-1.5">
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
            class="px-5 py-2 bg-alpharo-600 text-white font-semibold rounded-xl shadow-xs cursor-pointer flex items-center gap-1.5 hover:bg-alpharo-700"
          >
            <CheckCircle2 class="w-4 h-4" />
            <span>Simpan Konfirmasi Penerimaan</span>
          </button>
        </div>
      </form>
    </Modal>

    <!-- MODAL 5: KONFIRMASI PEMBAYARAN TAGIHAN -->
    <Modal
      v-model="showPaymentModal"
      title="Konfirmasi Pembayaran Tagihan"
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

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Nominal yang Ditransfer (Rp) <span class="text-rose-500">*</span></label>
          <input
            v-model.number="paymentForm.amount"
            type="number"
            min="1000"
            required
            placeholder="Contoh: 15000000"
            class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono font-semibold"
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

        <!-- UNGGAH BUKTI TRANSFER DENGAN TAMPILAN PERSIS WAREHOUSE LAPOR KERUSAKAN (MAKS. 1 BERKAS) -->
        <div>
          <label class="block font-semibold text-slate-700 mb-1.5 flex items-center justify-between">
            <span>Unggah Bukti Transfer (File Foto/PDF) <span class="text-rose-500">*</span></span>
            <span class="text-[11px] text-slate-400 font-normal">Maks. 1 berkas (Foto/PDF maks 10MB)</span>
          </label>

          <input
            ref="proofFileInput"
            type="file"
            accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
            class="hidden"
            @change="handleProofFileChange"
          />

          <!-- Media Thumbnails Grid (Identik seperti Warehouse Lapor Kerusakan) -->
          <div class="flex items-center gap-3 overflow-x-auto py-2">
            <!-- Uploaded Items -->
            <div
              v-for="(item, idx) in proofMediaList"
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
              <div v-else class="w-full h-full flex flex-col items-center justify-center bg-slate-800 p-2 text-white">
                <FileText class="w-8 h-8 text-sky-400 mb-1" />
                <span class="text-[10px] font-mono text-center truncate max-w-full px-1">{{ item.name }}</span>
                <span class="text-[9px] text-slate-400 font-mono">{{ item.size }}</span>
              </div>

              <!-- Close / Delete Button -->
              <button
                type="button"
                class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-black/60 hover:bg-rose-600 text-white flex items-center justify-center transition-colors shadow-xs cursor-pointer z-10"
                title="Hapus berkas ini"
                @click.stop="removeProofMedia(idx)"
              >
                <X class="w-3.5 h-3.5" />
              </button>
            </div>

            <!-- Upload Placeholder Box (Hanya muncul jika belum ada berkas terpilih) -->
            <button
              v-if="proofMediaList.length === 0"
              type="button"
              class="w-24 h-24 sm:w-28 sm:h-28 rounded-xl border-2 border-dashed border-slate-300 hover:border-slate-400 bg-white hover:bg-slate-50/80 flex flex-col items-center justify-center gap-1.5 shrink-0 transition-colors cursor-pointer group"
              @click="triggerProofFileInput"
            >
              <div class="w-8 h-8 rounded-full bg-slate-100 group-hover:bg-slate-200 flex items-center justify-center text-slate-600 transition-colors">
                <Camera class="w-4 h-4 stroke-[2]" />
              </div>
              <span class="text-xs font-semibold text-slate-700">Foto / PDF</span>
            </button>
          </div>
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
            class="px-5 py-2 rounded-xl bg-linear-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold shadow-xs cursor-pointer flex items-center gap-1.5"
          >
            <CheckCircle2 class="w-4 h-4" />
            <span>Kirim Konfirmasi Pembayaran</span>
          </button>
        </div>
      </form>
    </Modal>

    <!-- MODAL PREVIEW BUKTI TRANSFER (FULL SIZE SEPERTI WAREHOUSE MEDIA PREVIEW) -->
    <Modal
      v-model="mediaPreviewModalOpen"
      :title="activePreviewMedia?.name || 'Pratinjau Bukti Pembayaran'"
      subtitle="Dokumen atau foto struk transfer yang dilampirkan"
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
          <div v-else class="text-center py-12 text-white space-y-3">
            <FileText class="w-16 h-16 text-sky-400 mx-auto" />
            <p class="font-semibold text-base">{{ activePreviewMedia.name }}</p>
            <p class="text-xs text-slate-400 font-mono">{{ activePreviewMedia.size }}</p>
            <a
              :href="activePreviewMedia.url"
              target="_blank"
              download="Bukti_Transfer.pdf"
              class="inline-flex items-center gap-1.5 px-4 py-2 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded-lg text-xs font-semibold cursor-pointer"
            >
              Buka / Unduh Berkas PDF
            </a>
          </div>
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

    <!-- PUBLIC FOOTER -->
    <footer class="mt-auto border-t border-slate-200 bg-white py-8 px-4 sm:px-6 lg:px-8 text-xs text-slate-500">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
          <p class="font-semibold text-slate-800">CV. Alpharo Artha Jaya — Portal Publik Rekanan & Layanan Pengadaan Mandiri</p>
          <p class="text-slate-400 text-[11px] mt-0.5">Mitra Terpercaya Pengadaan Sarana Prasarana Sekolah & Instansi di DKI Jakarta</p>
        </div>
        <div class="flex flex-wrap items-center gap-4 text-[11px]">
          <button type="button" class="text-alpharo-600 font-semibold hover:underline cursor-pointer" @click="setTab('request')">Ajukan Kebutuhan</button>
          <span>&bull;</span>
          <button type="button" class="text-alpharo-600 font-semibold hover:underline cursor-pointer" @click="setTab('tracking')">Lacak Pesanan & Pembayaran</button>
          <span>&bull;</span>
          <Link href="/login" class="text-slate-700 font-semibold hover:underline">Staff Login</Link>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from '../../navigation';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import AlpharoLogo from '../../components/common/AlpharoLogo.vue';
import CompanyProfile from '../../components/company/CompanyProfile.vue';
import Modal from '../../components/common/Modal.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';

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
  Building2,
  FileText,
  Camera,
  X,
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const toast = useToast();

// Active tab: 'profile' | 'request' | 'tracking'
const activeTab = ref('profile');
const portalTabs = [
  { key: 'profile', label: 'Profil Perusahaan', title: 'Profil Perusahaan', icon: Building2 },
  { key: 'request', label: 'Ajukan Kebutuhan', title: 'Ajukan Kebutuhan Pengadaan Mandiri Rekanan', icon: ClipboardCheck },
  { key: 'tracking', label: 'Lacak Pesanan & Pembayaran', title: 'Lacak Pesanan, Pengiriman & Pembayaran Tagihan', icon: Truck },
];

const setTab = (tabName) => {
  const target = (tabName === 'payment' || tabName === 'pembayaran') ? 'tracking' : tabName;
  activeTab.value = target;
  router.replace({ query: { ...route.query, tab: target } });
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
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

const defaultMarketingStaff = [
  { id: 1, name: 'Budi Hartono, S.T.', email: 'marketing@alpharo.co.id', phone: '0812-3456-7890', title: 'Senior Account Executive' },
  { id: 2, name: 'Ahmad Fauzi', email: 'ahmad.fauzi@alpharo.co.id', phone: '0813-8821-4455', title: 'Marketing Officer' },
  { id: 3, name: 'Rina Wijaya, S.E.', email: 'rina.wijaya@alpharo.co.id', phone: '0815-9922-3344', title: 'Marketing Representative' },
  { id: 4, name: 'Siti Rahma', email: 'siti.rahma@alpharo.co.id', phone: '0817-6655-4433', title: 'Education Sector Specialist' },
];

const marketingOptions = computed(() => {
  return (store.marketingStaff && store.marketingStaff.length > 0)
    ? store.marketingStaff
    : defaultMarketingStaff;
});

const form = reactive({
  clientId: 1,
  clientName: '',
  contactPerson: '',
  phone: '',
  email: '',
  marketingName: 'Budi Hartono, S.T.',
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
      marketingName: form.marketingName,
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
const trackingInput = ref('');
const sampleTrackingOrders = computed(() => (store.orders || []).slice(0, 3).map((o) => o.orderNumber));
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

  // 1. Cari berdasarkan No. Order atau Surat Jalan
  let found = store.orders.find((o) =>
    o.orderNumber.toUpperCase().includes(q) ||
    (o.documents?.suratJalan?.number && o.documents.suratJalan.number.toUpperCase().includes(q))
  );

  // 2. Jika tidak ditemukan, coba cari berdasarkan No. Faktur Invoice
  if (!found) {
    const matchedInv = (store.invoices || []).find((inv) =>
      inv.invoiceNumber && inv.invoiceNumber.toUpperCase().includes(q)
    );
    if (matchedInv) {
      found = store.orders.find((o) => o.orderNumber === matchedInv.orderNumber);
    }
  }

  searchedOrder.value = found || null;
  if (!found) {
    toast.error(`Pesanan atau tagihan dengan kode "${q}" tidak ditemukan.`);
  } else {
    toast.success(`Data pesanan & tagihan ${found.orderNumber} berhasil ditemukan.`);
  }
};

const searchedOrderInvoice = computed(() => {
  if (!searchedOrder.value) return null;
  return (store.invoices || []).find((inv) => inv.orderNumber === searchedOrder.value.orderNumber);
});

const quickTrackAndPay = (orderNumber) => {
  trackingInput.value = orderNumber;
  handleSearchResi();
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 120, behavior: 'smooth' });
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
// 3. STATE & LOGIC: PEMBAYARAN TAGIHAN (TAB 3)
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
  amount: 0,
  paymentMethod: 'Transfer BCA',
  paymentDate: new Date().toISOString().split('T')[0],
  referenceNumber: `TRF-${Math.floor(100000 + Math.random() * 900000)}`,
  notes: '',
  proofFile: '',
  proofUrl: '',
});

const proofFileInput = ref(null);
const proofMediaList = ref([]);
const mediaPreviewModalOpen = ref(false);
const activePreviewMedia = ref(null);

const triggerProofFileInput = () => {
  if (proofFileInput.value) proofFileInput.value.click();
};

const handleProofFileChange = (e) => {
  const file = e.target.files?.[0];
  if (!file) return;

  if (file.size > 10 * 1024 * 1024) {
    toast.error(`Berkas ${file.name} melebihi batas 10MB.`);
    e.target.value = '';
    return;
  }

  const isImage = file.type.startsWith('image/');
  const isPdf = file.type.includes('pdf') || file.name.toLowerCase().endsWith('.pdf');
  if (!isImage && !isPdf) {
    toast.error(`Format berkas ${file.name} tidak didukung. Harap pilih foto (JPG, PNG) atau dokumen PDF.`);
    e.target.value = '';
    return;
  }

  const reader = new FileReader();
  reader.onload = (loadEvent) => {
    const itemObj = {
      name: file.name,
      type: isImage ? 'image' : 'pdf',
      url: loadEvent.target.result,
      size: (file.size / 1024).toFixed(0) + ' KB',
    };
    // Batasi strictly hanya 1 berkas foto/PDF saja
    proofMediaList.value = [itemObj];
    paymentForm.value.proofFile = itemObj.name;
    paymentForm.value.proofUrl = itemObj.url;
    toast.success(`Berkas ${file.name} berhasil dilampirkan.`);
  };
  reader.readAsDataURL(file);

  e.target.value = '';
};

const removeProofMedia = () => {
  proofMediaList.value = [];
  paymentForm.value.proofFile = '';
  paymentForm.value.proofUrl = '';
};

const openMediaPreview = (item) => {
  activePreviewMedia.value = item;
  mediaPreviewModalOpen.value = true;
};

const openPaymentModal = (inv = null) => {
  const targetInv = inv || filteredInvoices.value[0] || store.invoices[0];
  if (!targetInv) {
    toast.error('Belum ada data invoice untuk dikonfirmasi.');
    return;
  }

  // Reset proof file state
  proofMediaList.value = [];
  mediaPreviewModalOpen.value = false;
  activePreviewMedia.value = null;
  paymentForm.value.proofFile = '';
  paymentForm.value.proofUrl = '';

  paymentForm.value.invoiceNumber = targetInv.invoiceNumber;
  paymentForm.value.amount = targetInv.remainingAmount || targetInv.totalAmount || 0;
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

  if (!proofMediaList.value.length) {
    toast.error('Harap unggah bukti transfer / SP2D terlebih dahulu sebelum mengirim.');
    return;
  }

  storeActions.recordPayment({
    invoiceId: inv.id,
    invoiceNumber: inv.invoiceNumber,
    amount: paymentForm.value.amount,
    paymentDate: paymentForm.value.paymentDate,
    paymentMethod: paymentForm.value.paymentMethod,
    referenceNumber: paymentForm.value.referenceNumber,
    paymentType: 'Pembayaran Tagihan',
    notes: paymentForm.value.notes,
    proofFile: proofMediaList.value[0]?.name || 'BUKTI_TRANSFER_REKANAN.pdf',
    proofUrl: proofMediaList.value[0]?.url || null,
  });

  storeActions.addNotification(
    'FINANCE',
    'Konfirmasi Pembayaran Rekanan',
    `Rekanan ${inv.clientName} telah mengirimkan konfirmasi pembayaran Rp ${Number(paymentForm.value.amount || 0).toLocaleString('id-ID')} untuk ${inv.invoiceNumber}.`,
    'payment',
    '/finance/payments'
  );

  toast.success('Konfirmasi pembayaran dan berkas bukti berhasil dikirim ke bagian Keuangan Alpharo!');
  showPaymentModal.value = false;
};

// ==========================================
// LIFECYCLE HOOK
// ==========================================
onMounted(() => {
  // Query param sync
  const qTab = route.query.tab;
  if (qTab === 'payment' || qTab === 'pembayaran') {
    activeTab.value = 'tracking';
  } else if (qTab === 'tracking' || qTab === 'lacak' || route.query.resi) {
    activeTab.value = 'tracking';
    if (route.query.resi) {
      trackingInput.value = route.query.resi;
    }
    handleSearchResi();
  } else if (qTab === 'request' || qTab === 'pesanan' || qTab === 'order') {
    activeTab.value = 'request';
  } else {
    // Default tab saat membuka portal: Profil Perusahaan
    activeTab.value = 'profile';
  }

  // Prepopulate client data in request form
  onClientSelect();
});
</script>

<style scoped>
.company-portal-header { background: #fffffff5; border-bottom: 1px solid #e6ebf2; backdrop-filter: blur(12px); }
.company-portal-header-inner { width: min(1200px, 100% - 64px); min-height: 88px; margin-inline: auto; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
.company-portal-brand { text-align: left; cursor: pointer; flex-shrink: 0; }
.company-portal-nav { display: flex; align-self: stretch; align-items: stretch; gap: 26px; }
.company-portal-nav button { display: inline-flex; position: relative; align-items: center; justify-content: center; gap: 7px; color: #657184; font-size: 12px; font-weight: 500; cursor: pointer; }
.company-portal-nav button > svg { display: none; }
.company-portal-nav button::after { position: absolute; content: ''; height: 2px; bottom: 20px; inset-inline: 0; background: transparent; }
.company-portal-nav button:hover, .company-portal-nav button.is-active { color: #1d4ed8; }
.company-portal-nav button.is-active::after { background: #2563eb; }
.company-staff-login { display: inline-flex; justify-content: center; align-items: center; gap: 8px; font-size: 11px; font-weight: 600; color: #1d4ed8; border: 1px solid #d9e5f7; background: #f6f9ff; border-radius: 7px; padding: 10px 13px; }
.company-staff-login:hover { background: #eaf1ff; }
@media (max-width: 1100px) {
  .company-portal-header-inner { gap: 18px; }
  .company-portal-nav { gap: 16px; }
  .company-portal-nav button { font-size: 11px; }
}
@media (max-width: 900px) {
  .company-portal-header-inner { display: grid; grid-template-columns: 1fr auto; gap: 0 12px; padding-top: 14px; }
  .company-portal-nav { grid-column: 1 / -1; grid-row: 2; justify-content: space-between; gap: 12px; min-height: 55px; }
  .company-portal-nav button { font-size: 11px; }
  .company-portal-nav button::after { bottom: 0; }
  .company-staff-login { grid-column: 2; grid-row: 1; }
}
@media (max-width: 767px) {
  .company-portal-header-inner { width: calc(100% - 40px); }
  .company-portal-brand :deep(.w-10) { width: 32px; height: 32px; }
  .company-portal-brand :deep(.text-sm) { font-size: 11px; }
  .company-portal-brand :deep(.text-\[10px\]) { font-size: 8px; }
  .company-staff-login { font-size: 10px; padding: 8px 10px; }
  .company-portal-nav { gap: 10px; min-height: 51px; }
  .company-portal-nav button { font-size: 10px; }
}
@media (max-width: 359px) {
  .company-portal-header-inner { width: calc(100% - 32px); }
  .company-staff-login { gap: 5px; font-size: 9px; padding-inline: 7px; }
  .company-portal-nav { gap: 7px; }
  .company-portal-nav button { font-size: 9px; }
}
</style>
