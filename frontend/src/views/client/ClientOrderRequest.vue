<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <router-link
              to="/client/dashboard"
              class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors"
            >
              <ArrowLeft class="w-3.5 h-3.5" />
              <span>Kembali ke Dashboard Client</span>
            </router-link>
          </div>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Pengajuan Pesanan & Kebutuhan Barang
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
            Ajukan kebutuhan sarana prasarana sekolah atau instansi Anda secara mandiri. Tim Marketing CV Alpharo Artha Jaya akan segera memverifikasi ketersediaan stok & menyiapkan penawaran resmi.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <router-link
            to="/client/orders"
            class="inline-flex items-center gap-2 px-3.5 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold rounded-lg text-xs shadow-2xs transition-colors"
          >
            <Truck class="w-4 h-4 text-slate-500" />
            <span>Riwayat Pengajuan</span>
          </router-link>
        </div>
      </div>
    </template>

    <!-- INFO BANNER: ALUR KERJA ROLE CLIENT -->
    <div class="bg-gradient-to-r from-blue-50 via-indigo-50 to-sky-50 border border-blue-200/90 rounded-xl p-4 mb-6 shadow-2xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div class="flex items-start gap-3">
        <div class="w-9 h-9 rounded-xl bg-[#0B4DA2] text-white flex items-center justify-center shrink-0 shadow-xs">
          <ClipboardCheck class="w-5 h-5 text-amber-300" />
        </div>
        <div>
          <h2 class="text-xs font-bold text-slate-900">Alur Pengadaan Mandiri Rekanan Client (Portal Mandiri)</h2>
          <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">
            1. Ajukan Kebutuhan Barang &rarr; 2. Verifikasi Stok & Penawaran oleh Marketing &rarr; 3. Approval Direktur & SPJ &rarr; 4. Pengiriman Gudang &rarr; 5. Konfirmasi BAST di Lokasi.
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2 text-xs font-medium text-slate-500 shrink-0">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white border border-blue-200 rounded-full text-[11px] font-bold text-[#0B4DA2] shadow-2xs">
          <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
          Katalog 2.196 SKU Terhubung
        </span>
      </div>
    </div>

    <!-- MAIN FORM -->
    <form class="space-y-6 max-w-7xl mx-auto pb-12" @submit.prevent="handleSubmit">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        
        <!-- CARD 1: DATA INSTANSI & PEMOHON -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[3px] border-t-[#0B4DA2]">
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
                class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-[#0B4DA2]/20 focus:border-[#0B4DA2]"
                @change="onClientSelect"
              >
                <option value="" disabled selected>Pilih Instansi Rekanan</option>
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
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-[#0B4DA2]/20 focus:border-[#0B4DA2]"
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
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-[#0B4DA2]/20 focus:border-[#0B4DA2]"
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
                class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-[#0B4DA2]/20 focus:border-[#0B4DA2]"
              />
            </div>

            <!-- WILAYAH DKI JAKARTA CASCADER -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kota Administrasi</label>
                <select
                  v-model="form.city"
                  class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white"
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
                  class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white disabled:bg-slate-100"
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
                  class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white disabled:bg-slate-100"
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
                class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-[#0B4DA2]/20 focus:border-[#0B4DA2]"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- CARD 2: INFORMASI PROYEK & RENCANA PENGADAAN -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[3px] border-t-amber-500">
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
                class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 font-medium text-slate-800"
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
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white"
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
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white"
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
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300"
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
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300"
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
                placeholder="Jelaskan kebutuhan pengadaan barang atau spesifikasi umum ruangan yang akan dipasang..."
                class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300"
              ></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- CARD 3: DAFTAR BARANG YANG DIAJUKAN (INTERACTIVE ITEM PICKER) -->
      <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden border-t-[3px] border-t-emerald-600">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
          <div>
            <div class="flex items-center gap-2">
              <Boxes class="w-4 h-4 text-emerald-600" />
              <h3 class="font-bold text-slate-800 text-sm">3. Rincian Barang Kebutuhan yang Diajukan</h3>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
              Pilih item dari master katalog resmi CV Alpharo atau tambahkan spesifikasi khusus instansi Anda.
            </p>
          </div>

          <!-- DUAL ACTION BUTTONS (DENGAN GRADASI ICON PUTIH SESUAI DESIGN GUIDELINE) -->
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-all cursor-pointer"
              @click="openCatalogModal"
            >
              <Search class="w-3.5 h-3.5 text-white stroke-[2.5]" />
              <span>Pilih dari Master Katalog</span>
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-all cursor-pointer"
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
                    <th class="py-2.5 px-3 min-w-[180px]">Spesifikasi / Catatan Kebutuhan</th>
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
                        class="w-full px-2 py-1 text-xs border border-slate-200 rounded focus:border-blue-500 bg-white"
                      />
                    </td>
                    <td class="py-3 px-3 text-center">
                      <input
                        v-model.number="item.qty"
                        type="number"
                        min="1"
                        required
                        class="w-20 px-2 py-1 text-xs text-center border border-slate-300 rounded font-bold focus:border-blue-500"
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
                        class="w-28 px-2 py-1 text-xs text-right font-mono border border-slate-300 rounded focus:border-blue-500"
                      />
                    </td>
                    <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                      Rp {{ (Number(item.qty || 0) * Number(item.price || 0)).toLocaleString('id-ID') }}
                    </td>
                    <td class="py-3 px-3 text-center">
                      <!-- DELETE BUTTON WITH GRADIENT ROSE-RED -->
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
                          Klik tombol <strong>"Pilih dari Master Katalog"</strong> untuk memilih dari 2.196 SKU atau gunakan <strong>"Tambah Item Custom"</strong> untuk kebutuhan khusus.
                        </p>
                        <div class="pt-2 flex justify-center gap-2">
                          <button
                            type="button"
                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold cursor-pointer"
                            @click="openCatalogModal"
                          >
                            Buka Katalog Barang
                          </button>
                        </div>
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
                <div>Total Unit: <strong class="text-slate-900">{{ totalQty }}</strong> kuantitas fisik</div>
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
      <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-2 text-xs text-slate-500">
          <ShieldCheck class="w-4 h-4 text-emerald-600" />
          <span>Pengajuan akan tercatat resmi dengan nomor tracking & langsung dinotifikasikan ke Marketing.</span>
        </div>

        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
          <button
            type="button"
            class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold rounded-lg text-xs shadow-2xs transition-colors cursor-pointer"
            @click="resetForm"
          >
            Reset
          </button>
          <button
            type="submit"
            :disabled="form.items.length === 0 || isSubmitting"
            class="px-6 py-2.5 bg-gradient-to-r from-[#0B4DA2] to-[#062F69] hover:from-[#09428F] hover:to-[#042452] active:from-[#062F69] text-white font-bold rounded-lg text-xs shadow-sm shadow-blue-900/30 transition-all cursor-pointer flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Send class="w-4 h-4 text-amber-300" />
            <span>{{ isSubmitting ? 'Mengirimkan...' : 'Kirim Pengajuan Kebutuhan Barang' }}</span>
          </button>
        </div>
      </div>
    </form>

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
              placeholder="Cari SKU atau nama barang (misal: Meja, Semen, Spidol)..."
              class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div class="sm:col-span-5">
            <select
              v-model="catalogCategory"
              class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white"
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
            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300"
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
              class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 text-center font-bold"
            />
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Satuan <span class="text-rose-500">*</span></label>
            <input
              v-model="customItem.unit"
              type="text"
              required
              placeholder="Unit / Set / Paket / Pcs"
              class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 text-center font-bold"
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
            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 font-mono text-right"
          />
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Spesifikasi Detail / Keterangan Khusus</label>
          <textarea
            v-model="customItem.specs"
            rows="2"
            placeholder="Dimensi ukuran, merk referensi, warna, kelengkapan baut/aksesori..."
            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300"
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
            Tim Marketing Alpharo akan menghubungi PIC ({{ createdOrder?.contactPerson }}) untuk konfirmasi ketersediaan barang dan penerbitan Surat Penawaran Harga (SPH). Anda dapat memantau setiap proses secara berkala melalui halaman tracking.
          </p>
        </div>

        <div class="flex items-center justify-center gap-3 pt-2">
          <router-link
            :to="`/client/orders?code=${createdOrder?.orderNumber || ''}`"
            class="px-4 py-2 bg-[#0B4DA2] hover:bg-[#09428F] text-white rounded-lg font-bold text-xs shadow-xs transition-colors"
          >
            Lihat di Tracking Pesanan
          </router-link>
          <button
            type="button"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold text-xs transition-colors cursor-pointer"
            @click="createNewAnother"
          >
            Ajukan Pesanan Lain
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import AppLayout from '../../components/layout/AppLayout.vue';
import Modal from '../../components/common/Modal.vue';

// Lucide Icons
import {
  ArrowLeft,
  School,
  FolderKanban,
  Boxes,
  Truck,
  Plus,
  Trash2,
  Search,
  CheckCircle2,
  Send,
  ClipboardCheck,
  ShoppingBag,
  Info,
  ShieldCheck,
} from 'lucide-vue-next';

const router = useRouter();
const toast = useToast();

const catalogModalOpen = ref(false);
const customItemModalOpen = ref(false);
const successModalOpen = ref(false);
const isSubmitting = ref(false);
const uploadedFileName = ref('');
const createdOrder = ref(null);

const catalogSearch = ref('');
const catalogCategory = ref('');

// Form Data
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

// Auto-fill client data
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

// Initial Setup
onMounted(() => {
  onClientSelect();
});

// Calculations
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

// Catalog Filtering
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

// Actions
const openCatalogModal = () => {
  catalogModalOpen.value = true;
};

const addCatalogItemToForm = (prod) => {
  // Check if exists
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
    toast.success(`${prod.name} ditambahkan ke daftar`);
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
  toast.success(`Item custom "${customItem.name}" ditambahkan`);
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

const handleSubmit = () => {
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
    toast.success('Pengajuan kebutuhan berhasil terkirim ke Marketing!');
  }, 400);
};
</script>
