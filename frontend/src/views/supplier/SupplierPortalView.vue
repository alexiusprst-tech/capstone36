<template>
  <div class="min-h-screen bg-slate-100 flex flex-col font-sans text-slate-800">
    <!-- TOP ENTERPRISE HEADER -->
    <header class="bg-gradient-to-r from-slate-900 via-slate-800 to-amber-950 text-white shadow-md sticky top-0 z-40 border-b border-amber-500/20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <!-- Logo & Portal Title -->
        <div class="flex items-center gap-3">
          <AlpharoLogo theme="white" size="md" :show-tagline="true" />
          <div class="hidden sm:block pl-4 border-l border-white/20">
            <span class="text-xs font-bold text-amber-400 uppercase tracking-widest block flex items-center gap-1.5">
              <Truck class="w-3.5 h-3.5" />
              <span>Portal Vendor & Supplier Rekanan</span>
            </span>
            <span class="text-[11px] text-slate-300">
              Penerimaan PO, Konfirmasi Pengadaan & Pengiriman Barang CV Alpharo
            </span>
          </div>
        </div>

        <!-- Supplier Profile & Quick Switcher -->
        <div class="flex items-center gap-3">
          <!-- Active Supplier Badge -->
          <div class="hidden md:flex flex-col text-right">
            <span class="text-xs font-bold text-white leading-tight">
              {{ currentSupplier?.name || store.currentUser?.name || 'Mitra Supplier' }}
            </span>
            <span class="text-[10px] text-amber-300/90 font-mono">
              PIC: {{ currentSupplier?.contactPerson || 'Sales Team' }} &bull; {{ currentSupplier?.phone || '' }}
            </span>
          </div>

          <!-- Quick Switch Supplier Dropdown (For demo & evaluation) -->
          <div class="relative">
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white rounded-lg text-xs font-semibold backdrop-blur-xs transition-colors border border-white/15 cursor-pointer"
              @click="supplierMenuOpen = !supplierMenuOpen"
            >
              <Building2 class="w-3.5 h-3.5 text-amber-300" />
              <span class="hidden sm:inline">Ganti Akun Supplier</span>
              <ChevronDown class="w-3 h-3 text-slate-300" />
            </button>

            <!-- Dropdown Menu -->
            <div
              v-if="supplierMenuOpen"
              class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-50 text-slate-800 text-xs"
            >
              <div class="px-3 py-1.5 border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase">
                Pilih Akun Supplier Terdaftar:
              </div>
              <div class="max-h-64 overflow-y-auto divide-y divide-slate-50">
                <button
                  v-for="s in (store.suppliers || [])"
                  :key="s.id"
                  type="button"
                  :class="[
                    currentSupplier?.id === s.id ? 'bg-amber-50 text-amber-900 font-bold' : 'hover:bg-slate-50 text-slate-700',
                    'w-full text-left px-3 py-2 flex items-start gap-2 transition-colors cursor-pointer'
                  ]"
                  @click="selectActiveSupplier(s)"
                >
                  <span class="w-2 h-2 rounded-full bg-amber-500 mt-1 shrink-0"></span>
                  <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold">{{ s.name }}</p>
                    <p class="text-[10px] text-slate-400 truncate">{{ s.email }} &bull; {{ s.phone }}</p>
                  </div>
                </button>
              </div>
            </div>
          </div>

          <!-- Internal Staff Switcher / Logout -->
          <router-link
            to="/login"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white rounded-lg text-xs font-semibold transition-colors cursor-pointer shadow-xs"
            title="Keluar ke Halaman Login"
          >
            <LogOut class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Keluar</span>
          </router-link>
        </div>
      </div>
    </header>

    <!-- HERO / SUPPLIER PROFILE CARD -->
    <div class="bg-white border-b border-slate-200/90 py-5 px-4 sm:px-6 lg:px-8 shadow-2xs">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="px-2.5 py-0.5 bg-amber-100 text-amber-900 font-bold text-[10px] rounded-full uppercase tracking-wider border border-amber-200">
              Vendor Resmi CV Alpharo Artha Jaya
            </span>
            <span class="text-xs text-slate-400 font-mono">Kode: {{ currentSupplier?.code || 'SUP00001' }}</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
            <span>{{ currentSupplier?.name || 'PT Synnex Metrodata Indonesia' }}</span>
            <span class="p-1 text-emerald-600 bg-emerald-50 rounded-full" title="Status Rekanan Aktif & Terverifikasi">
              <CheckCircle class="w-4 h-4" />
            </span>
          </h1>
          <p class="text-xs text-slate-500 mt-1 max-w-2xl">
            Alamat: {{ currentSupplier?.address || 'Jakarta' }} &bull; NPWP: <span class="font-mono text-slate-700">{{ currentSupplier?.npwp || '-' }}</span> &bull; Rekening: <span class="font-mono text-slate-700">{{ currentSupplier?.bankName }} {{ currentSupplier?.bankAccountNumber }} (a.n {{ currentSupplier?.bankAccountName }})</span>
          </p>
        </div>

        <!-- Direct Contact Trigger -->
        <div class="flex items-center gap-2 shrink-0">
          <a
            :href="'https://wa.me/' + cleanPhone(currentSupplier?.phone)"
            target="_blank"
            class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-2xs transition-colors"
          >
            <MessageSquare class="w-3.5 h-3.5" />
            <span>Chat Finance Alpharo</span>
          </a>
        </div>
      </div>
    </div>

    <!-- MAIN BODY CONTAINER -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 w-full space-y-6">
      <!-- STATS OVERVIEW CARDS -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <!-- 1. Total PO Masuk -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div class="flex items-center justify-between text-slate-500 mb-1">
            <span class="text-[11px] font-semibold uppercase tracking-wider">Total PO Masuk</span>
            <ShoppingBag class="w-4 h-4 text-blue-600" />
          </div>
          <div class="text-xl sm:text-2xl font-bold font-mono text-slate-900">
            {{ supplierPurchases.length }}
          </div>
          <div class="text-[10px] text-slate-400 mt-0.5">Dari Finance CV Alpharo</div>
        </div>

        <!-- 2. Menunggu Konfirmasi -->
        <div class="bg-white p-4 rounded-xl border border-amber-200 bg-amber-50/40 shadow-2xs">
          <div class="flex items-center justify-between text-amber-800 mb-1">
            <span class="text-[11px] font-semibold uppercase tracking-wider">Perlu Konfirmasi</span>
            <Clock class="w-4 h-4 text-amber-600" />
          </div>
          <div class="text-xl sm:text-2xl font-bold font-mono text-amber-900">
            {{ pendingConfirmationCount }}
          </div>
          <div class="text-[10px] text-amber-700 font-semibold mt-0.5">Harap segera diverifikasi</div>
        </div>

        <!-- 3. Sedang Disiapkan / Dikirim -->
        <div class="bg-white p-4 rounded-xl border border-blue-200 bg-blue-50/40 shadow-2xs">
          <div class="flex items-center justify-between text-blue-800 mb-1">
            <span class="text-[11px] font-semibold uppercase tracking-wider">Dalam Proses / Kirim</span>
            <Truck class="w-4 h-4 text-blue-600" />
          </div>
          <div class="text-xl sm:text-2xl font-bold font-mono text-blue-900">
            {{ inProgressDeliveryCount }}
          </div>
          <div class="text-[10px] text-blue-700 mt-0.5">Pengiriman ke gudang</div>
        </div>

        <!-- 4. Selesai Diterima Gudang -->
        <div class="bg-white p-4 rounded-xl border border-emerald-200 bg-emerald-50/40 shadow-2xs">
          <div class="flex items-center justify-between text-emerald-800 mb-1">
            <span class="text-[11px] font-semibold uppercase tracking-wider">Selesai / Diterima</span>
            <CheckCircle2 class="w-4 h-4 text-emerald-600" />
          </div>
          <div class="text-xl sm:text-2xl font-bold font-mono text-emerald-900">
            {{ completedCount }}
          </div>
          <div class="text-[10px] text-emerald-700 mt-0.5">Tuntas diperiksa fisik</div>
        </div>
      </div>

      <!-- TABS NAVIGATION -->
      <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-1.5 flex flex-wrap gap-1.5">
        <button
          v-for="t in tabs"
          :key="t.key"
          type="button"
          :class="[
            activeTab === t.key
              ? 'bg-slate-900 text-white font-bold shadow-xs'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium',
            'px-3.5 py-2 rounded-lg text-xs flex items-center gap-2 transition-all cursor-pointer'
          ]"
          @click="activeTab = t.key"
        >
          <component :is="t.icon" class="w-4 h-4" />
          <span>{{ t.label }}</span>
          <span
            v-if="t.badge !== undefined && t.badge > 0"
            :class="activeTab === t.key ? 'bg-amber-400 text-slate-950' : 'bg-amber-100 text-amber-900'"
            class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
          >
            {{ t.badge }}
          </span>
        </button>
      </div>

      <!-- ======================================================== -->
      <!-- TAB 1: DAFTAR PESANAN PEMBELIAN MASUK (PO DARI FINANCE)    -->
      <!-- ======================================================== -->
      <section v-if="activeTab === 'orders'" class="space-y-4">
        <!-- Filter & Search Toolbar -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-col sm:flex-row gap-3 items-center justify-between">
          <div class="relative w-full sm:w-80">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="orderSearch"
              type="text"
              placeholder="Cari kode PO, faktur, atau nama barang..."
              class="w-full pl-9 pr-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-amber-500 bg-white"
            />
          </div>

          <div class="flex items-center gap-2 w-full sm:w-auto">
            <select
              v-model="statusFilter"
              class="w-full sm:w-auto px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-amber-500 bg-white cursor-pointer"
            >
              <option value="">Semua Status Pesanan</option>
              <option value="Menunggu Konfirmasi">Menunggu Konfirmasi</option>
              <option value="Dikonfirmasi Supplier">Dikonfirmasi / Diproses</option>
              <option value="Dikirim ke Gudang CV">Dalam Pengiriman</option>
              <option value="Diterima Gudang">Selesai Diterima Gudang</option>
              <option value="Ketidaksesuaian">Ketidaksesuaian / Retur</option>
            </select>
          </div>
        </div>

        <!-- Orders Table Card -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
          <div class="h-1 bg-amber-500 w-full"></div>
          <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">
              Daftar Pesanan Masuk (Purchase Order CV Alpharo)
            </h2>
            <span class="text-xs text-slate-500 font-mono">
              {{ filteredPurchases.length }} Pesanan Ditemukan
            </span>
          </div>

          <!-- Empty State -->
          <div v-if="filteredPurchases.length === 0" class="p-12 text-center text-slate-500">
            <ShoppingBag class="w-10 h-10 text-slate-300 mx-auto mb-2" />
            <p class="text-xs font-semibold text-slate-700">Tidak ada pesanan pembelian yang sesuai filter.</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Pesanan baru yang diterbitkan Finance Alpharo akan muncul di sini secara otomatis.</p>
          </div>

          <!-- Table View -->
          <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                  <th class="py-3 px-4">Kode PO & Tanggal</th>
                  <th class="py-3 px-4">No. Faktur</th>
                  <th class="py-3 px-4">Proyek Pemesan</th>
                  <th class="py-3 px-4">Rincian Barang</th>
                  <th class="py-3 px-4 text-right">Total Transaksi</th>
                  <th class="py-3 px-4 text-center">Status</th>
                  <th class="py-3 px-4 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr
                  v-for="p in filteredPurchases"
                  :key="p.purchase_code || p.id"
                  class="hover:bg-amber-50/30 transition-colors"
                >
                  <!-- Kode PO & Tanggal -->
                  <td class="py-3 px-4 whitespace-nowrap">
                    <span class="font-mono font-bold text-slate-900 block">{{ p.purchase_code }}</span>
                    <span class="text-[11px] text-slate-500 font-mono">{{ p.transaction_date }}</span>
                  </td>

                  <!-- No Faktur -->
                  <td class="py-3 px-4 whitespace-nowrap">
                    <span class="font-mono text-slate-800 font-semibold bg-slate-100 px-2 py-0.5 rounded text-[11px]">
                      {{ p.invoice_number || '-' }}
                    </span>
                  </td>

                  <!-- Proyek -->
                  <td class="py-3 px-4 max-w-xs">
                    <div class="font-semibold text-slate-900 leading-snug">
                      {{ p.project_name }}
                    </div>
                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                      {{ p.project_id || 'Proyek Umum' }}
                    </div>
                  </td>

                  <!-- Rincian Barang -->
                  <td class="py-3 px-4 max-w-sm">
                    <div class="space-y-1">
                      <div
                        v-for="(it, idx) in p.items"
                        :key="idx"
                        class="text-[11px] flex items-center justify-between gap-2"
                      >
                        <span class="font-medium text-slate-800 truncate">{{ it.barang || it.name }}</span>
                        <span class="font-mono font-bold text-amber-900 shrink-0 bg-amber-50 px-1.5 py-0.2 rounded border border-amber-200/60">
                          {{ it.qty }} unit
                        </span>
                      </div>
                    </div>
                  </td>

                  <!-- Total Transaksi -->
                  <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                    Rp {{ Number(p.total_price || p.subtotal || 0).toLocaleString('id-ID') }}
                  </td>

                  <!-- Status -->
                  <td class="py-3 px-4 text-center whitespace-nowrap">
                    <span
                      v-if="p.supplier_status === 'Menunggu Konfirmasi' || !p.supplier_status"
                      class="px-2.5 py-1 bg-amber-100 text-amber-900 border border-amber-200 rounded-full font-bold text-[10px] inline-flex items-center gap-1"
                    >
                      <Clock class="w-3 h-3 text-amber-600" />
                      <span>Menunggu Konfirmasi</span>
                    </span>
                    <span
                      v-else-if="p.supplier_status === 'Dikonfirmasi Supplier' || p.supplier_status === 'Sedang Disiapkan'"
                      class="px-2.5 py-1 bg-blue-100 text-blue-900 border border-blue-200 rounded-full font-bold text-[10px] inline-flex items-center gap-1"
                    >
                      <Package class="w-3 h-3 text-blue-600" />
                      <span>Sedang Disiapkan</span>
                    </span>
                    <span
                      v-else-if="p.supplier_status === 'Dikirim ke Gudang CV'"
                      class="px-2.5 py-1 bg-indigo-100 text-indigo-900 border border-indigo-200 rounded-full font-bold text-[10px] inline-flex items-center gap-1"
                    >
                      <Truck class="w-3 h-3 text-indigo-600" />
                      <span>Dalam Pengiriman</span>
                    </span>
                    <span
                      v-else-if="p.supplier_status === 'Diterima Gudang'"
                      class="px-2.5 py-1 bg-emerald-100 text-emerald-900 border border-emerald-200 rounded-full font-bold text-[10px] inline-flex items-center gap-1"
                    >
                      <CheckCircle class="w-3 h-3 text-emerald-600" />
                      <span>Selesai Diterima Gudang</span>
                    </span>
                    <span
                      v-else-if="p.supplier_status === 'Barang Pengganti Dikirim'"
                      class="px-2.5 py-1 bg-purple-100 text-purple-900 border border-purple-200 rounded-full font-bold text-[10px] inline-flex items-center gap-1"
                    >
                      <RefreshCw class="w-3 h-3 text-purple-600" />
                      <span>Barang Pengganti Dikirim</span>
                    </span>
                    <span
                      v-else
                      class="px-2.5 py-1 bg-slate-100 text-slate-800 border border-slate-200 rounded-full font-bold text-[10px]"
                    >
                      {{ p.supplier_status }}
                    </span>
                  </td>

                  <!-- Aksi -->
                  <td class="py-3 px-4 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1.5">
                      <!-- Tombol Konfirmasi Pesanan (Jika masih Menunggu) -->
                      <button
                        v-if="p.supplier_status === 'Menunggu Konfirmasi' || !p.supplier_status"
                        type="button"
                        class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded font-bold text-xs shadow-2xs transition-colors cursor-pointer inline-flex items-center gap-1"
                        @click="openConfirmModal(p)"
                      >
                        <Check class="w-3.5 h-3.5" />
                        <span>Konfirmasi PO</span>
                      </button>

                      <!-- Tombol Kirim Barang (Jika sudah dikonfirmasi tapi belum dikirim) -->
                      <button
                        v-else-if="p.supplier_status === 'Dikonfirmasi Supplier' || p.supplier_status === 'Sedang Disiapkan'"
                        type="button"
                        class="px-3 py-1 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded font-bold text-xs shadow-2xs transition-colors cursor-pointer inline-flex items-center gap-1"
                        @click="openDispatchModal(p)"
                      >
                        <Truck class="w-3.5 h-3.5" />
                        <span>Kirim Barang ke CV</span>
                      </button>

                      <!-- Tombol Info Pengiriman (Jika sedang dikirim) -->
                      <button
                        v-else-if="p.supplier_status === 'Dikirim ke Gudang CV' || p.delivery_info"
                        type="button"
                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold text-xs border border-slate-300 transition-colors cursor-pointer"
                        @click="openDetail(p)"
                      >
                        Lacak Resi / SJ
                      </button>

                      <!-- Tombol Detail & Cetak PO -->
                      <button
                        type="button"
                        class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 rounded font-semibold text-xs border border-slate-300 transition-colors cursor-pointer"
                        @click="openDetail(p)"
                      >
                        Detail PO
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ======================================================== -->
      <!-- TAB 2: PENGIRIMAN & SURAT JALAN SUPPLIER                 -->
      <!-- ======================================================== -->
      <section v-if="activeTab === 'shipments'" class="space-y-4">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-4 sm:p-5">
          <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
            <div>
              <h2 class="text-sm font-bold text-slate-900">Arsip Pengiriman & Surat Jalan Supplier</h2>
              <p class="text-xs text-slate-500 mt-0.5">
                Daftar pengiriman barang fisik dari pihak supplier ke gudang logistik CV Alpharo Artha Jaya.
              </p>
            </div>
          </div>

          <div v-if="dispatchedPurchases.length === 0" class="text-center py-10 text-slate-500 text-xs">
            <Truck class="w-8 h-8 text-slate-300 mx-auto mb-2" />
            <p class="font-semibold text-slate-700">Belum ada riwayat pengiriman barang.</p>
            <p class="text-slate-400 mt-0.5">Konfirmasi pesanan di tab "Pesanan Masuk" lalu klik "Kirim Barang ke CV".</p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="p in dispatchedPurchases"
              :key="p.purchase_code"
              class="p-4 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-white hover:border-amber-300 transition-all shadow-2xs flex flex-col justify-between"
            >
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="font-mono font-bold text-slate-900 text-xs">{{ p.delivery_info?.surat_jalan || 'SJ-PENDING' }}</span>
                  <span
                    :class="[
                      p.supplier_status === 'Diterima Gudang' ? 'bg-emerald-100 text-emerald-900 border-emerald-200' : 'bg-blue-100 text-blue-900 border-blue-200',
                      'px-2 py-0.5 rounded text-[10px] font-bold border'
                    ]"
                  >
                    {{ p.supplier_status === 'Diterima Gudang' ? '✓ Diterima Gudang CV' : '🚚 Dalam Perjalanan' }}
                  </span>
                </div>

                <div class="text-xs font-semibold text-slate-800">
                  PO: <span class="font-mono text-slate-900">{{ p.purchase_code }}</span> &bull; {{ p.project_name }}
                </div>

                <div class="mt-2.5 p-2.5 bg-white rounded-lg border border-slate-200/90 text-xs space-y-1 text-slate-600">
                  <div><strong>Ekspedisi / Kurir:</strong> {{ p.delivery_info?.courier || '-' }}</div>
                  <div><strong>No. Resi / Plat:</strong> <span class="font-mono font-bold text-slate-900">{{ p.delivery_info?.tracking_number || '-' }}</span></div>
                  <div><strong>Tanggal Kirim:</strong> {{ p.delivery_info?.dispatch_date || p.transaction_date }} &bull; Est: {{ p.delivery_info?.est_arrival || '-' }}</div>
                  <div v-if="p.delivery_info?.notes" class="text-[11px] text-slate-500 italic mt-1">
                    "{{ p.delivery_info?.notes }}"
                  </div>
                </div>

                <!-- Items delivered -->
                <div class="mt-2.5 pt-2 border-t border-slate-200 flex flex-wrap gap-1">
                  <span
                    v-for="(it, idx) in p.items"
                    :key="idx"
                    class="text-[10px] bg-slate-200 text-slate-700 px-2 py-0.5 rounded font-mono"
                  >
                    {{ it.barang || it.name }} ({{ it.qty }})
                  </span>
                </div>
              </div>

              <div class="pt-3 border-t border-slate-200 mt-3 flex justify-end">
                <button
                  type="button"
                  class="px-3 py-1 bg-white hover:bg-slate-50 border border-slate-300 rounded text-xs font-semibold text-slate-700"
                  @click="openDetail(p)"
                >
                  Lihat Rincian Faktur & PO
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ======================================================== -->
      <!-- TAB 3: TINDAK LANJUT KETIDAKSESUAIAN / RETUR (TAHAP 5b)  -->
      <!-- ======================================================== -->
      <section v-if="activeTab === 'discrepancy'" class="space-y-4">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-4 sm:p-5">
          <div class="flex items-center gap-2 pb-3 border-b border-slate-200 mb-4">
            <AlertTriangle class="w-5 h-5 text-amber-600" />
            <div>
              <h2 class="text-sm font-bold text-slate-900">Tindak Lanjut Ketidaksesuaian Barang (PROBIS TO-BE Tahap 5b)</h2>
              <p class="text-xs text-slate-500">
                Pemberitahuan dari Gudang & Finance mengenai kerusakan atau ketidaksesuaian fisik barang yang memerlukan penggantian/resolusi.
              </p>
            </div>
          </div>

          <!-- Discrepancy Items -->
          <div v-if="discrepancyPurchases.length === 0" class="text-center py-12 text-slate-500 text-xs">
            <CheckCircle2 class="w-10 h-10 text-emerald-500 mx-auto mb-2" />
            <p class="font-bold text-slate-800 text-sm">Tidak Ada Laporan Ketidaksesuaian!</p>
            <p class="text-slate-400 mt-0.5">Semua barang yang dikirimkan ke gudang CV Alpharo dalam kondisi baik dan sesuai spesifikasi.</p>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="p in discrepancyPurchases"
              :key="p.purchase_code"
              class="p-4 rounded-xl border border-amber-300 bg-amber-50/50 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4"
            >
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-200 text-amber-900">
                    ⚠ Ketidaksesuaian Dilaporkan Gudang
                  </span>
                  <span class="font-mono text-xs font-bold text-slate-900">{{ p.purchase_code }}</span>
                </div>
                <div class="text-xs font-semibold text-slate-800">
                  Proyek: {{ p.project_name }}
                </div>
                <p class="text-xs text-slate-600 mt-1">
                  <strong>Catatan Masalah: </strong>
                  {{ p.discrepancy_info?.notes || 'Terdapat kerusakan fisik kemasan dan segel terkelupas pada saat dibongkar di gudang CV Alpharo.' }}
                </p>
                <div class="text-[11px] text-slate-400 mt-1 font-mono">
                  No. SJ Awal: {{ p.delivery_info?.surat_jalan || '-' }} &bull; Dilaporkan: {{ p.discrepancy_info?.date || p.transaction_date }}
                </div>
              </div>

              <!-- Action Resolution -->
              <div class="shrink-0 flex items-center gap-2">
                <button
                  type="button"
                  class="px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold shadow-2xs transition-colors cursor-pointer flex items-center gap-1.5"
                  @click="openResolutionModal(p)"
                >
                  <RefreshCw class="w-3.5 h-3.5" />
                  <span>Kirim Barang Pengganti</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ======================================================== -->
      <!-- TAB 4: PROFIL & DATA REKENING SUPPLIER                   -->
      <!-- ======================================================== -->
      <section v-if="activeTab === 'profile'" class="space-y-4">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-5 max-w-3xl">
          <h2 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-200">
            Informasi Profil & Rekening Pembayaran Supplier
          </h2>

          <div class="space-y-3 text-xs">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-slate-600 mb-1">Nama Perusahaan Supplier</label>
                <input
                  :value="currentSupplier?.name"
                  readonly
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 font-bold"
                />
              </div>
              <div>
                <label class="block font-semibold text-slate-600 mb-1">Kode Vendor</label>
                <input
                  :value="currentSupplier?.code"
                  readonly
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 font-mono font-bold"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-slate-600 mb-1">Contact Person (PIC)</label>
                <input
                  :value="currentSupplier?.contactPerson"
                  readonly
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-800"
                />
              </div>
              <div>
                <label class="block font-semibold text-slate-600 mb-1">Nomor Telepon / WhatsApp</label>
                <input
                  :value="currentSupplier?.phone"
                  readonly
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 font-mono"
                />
              </div>
            </div>

            <div>
              <label class="block font-semibold text-slate-600 mb-1">Alamat Email Resmi</label>
              <input
                :value="currentSupplier?.email"
                readonly
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 font-mono"
              />
            </div>

            <div>
              <label class="block font-semibold text-slate-600 mb-1">Alamat Kantor / Gudang Supplier</label>
              <textarea
                :value="currentSupplier?.address"
                readonly
                rows="2"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-800"
              ></textarea>
            </div>

            <div class="pt-3 border-t border-slate-200">
              <h3 class="text-xs font-bold text-slate-900 mb-2 uppercase">Rekening Pembayaran dari Finance Alpharo</h3>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label class="block font-semibold text-slate-600 mb-1">Bank</label>
                  <input
                    :value="currentSupplier?.bankName || 'BCA'"
                    readonly
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 font-bold"
                  />
                </div>
                <div>
                  <label class="block font-semibold text-slate-600 mb-1">Nomor Rekening</label>
                  <input
                    :value="currentSupplier?.bankAccountNumber || '-'"
                    readonly
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 font-mono font-bold"
                  />
                </div>
                <div>
                  <label class="block font-semibold text-slate-600 mb-1">Atas Nama Rekening</label>
                  <input
                    :value="currentSupplier?.bankAccountName || currentSupplier?.name"
                    readonly
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-800"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- ======================================================== -->
    <!-- MODAL 1: KONFIRMASI PESANAN (TERIMA PO)                   -->
    <!-- ======================================================== -->
    <Modal
      v-model="confirmModalOpen"
      :title="'Konfirmasi Penerimaan PO: ' + (activePO?.purchase_code || '')"
      subtitle="Verifikasi ketersediaan stok barang sebelum memproses pengiriman ke gudang CV Alpharo."
      size="lg"
    >
      <div v-if="activePO" class="space-y-4 text-xs">
        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-900 text-xs">
          Dengan mengonfirmasi pesanan ini, status PO akan diperbarui menjadi <strong>Dikonfirmasi Supplier</strong> dan notifikasi otomatis dikirimkan ke Tim Finance serta Gudang CV Alpharo Artha Jaya.
        </div>

        <div class="border border-slate-200 rounded-lg overflow-hidden">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
              <tr>
                <th class="py-2 px-3">Nama Barang</th>
                <th class="py-2 px-3 text-center">Jumlah</th>
                <th class="py-2 px-3 text-right">Harga Beli</th>
                <th class="py-2 px-3 text-right">Subtotal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(it, idx) in activePO.items" :key="idx">
                <td class="py-2 px-3 font-medium text-slate-800">{{ it.barang || it.name }}</td>
                <td class="py-2 px-3 text-center font-bold font-mono">{{ it.qty }}</td>
                <td class="py-2 px-3 text-right font-mono">Rp {{ Number(it.harga_beli || 0).toLocaleString('id-ID') }}</td>
                <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">Rp {{ Number(it.subtotal || 0).toLocaleString('id-ID') }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Catatan Konfirmasi / Estimasi Siap Kirim</label>
          <textarea
            v-model="confirmNotes"
            rows="2"
            placeholder="Contoh: Stok tersedia lengkap di gudang kami. Siap dikirim besok pagi."
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-emerald-500 text-xs"
          ></textarea>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-200">
          <button
            type="button"
            class="px-3 py-1.5 text-rose-600 hover:bg-rose-50 rounded font-semibold text-xs transition-colors"
            @click="rejectPO(activePO)"
          >
            Tolak Pesanan / Stok Kosong
          </button>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold"
              @click="confirmModalOpen = false"
            >
              Batal
            </button>
            <button
              type="button"
              class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold shadow-xs cursor-pointer"
              @click="submitConfirmPO"
            >
              Ya, Terima & Konfirmasi PO
            </button>
          </div>
        </div>
      </div>
    </Modal>

    <!-- ======================================================== -->
    <!-- MODAL 2: INPUT PENGIRIMAN & SURAT JALAN SUPPLIER          -->
    <!-- ======================================================== -->
    <Modal
      v-model="dispatchModalOpen"
      :title="'Kirim Barang ke CV Alpharo: ' + (activePO?.purchase_code || '')"
      subtitle="Input nomor surat jalan dan data armada ekspedisi untuk penerimaan gudang CV Alpharo."
      size="lg"
    >
      <form v-if="activePO" class="space-y-4 text-xs" @submit.prevent="submitDispatch">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 mb-1">
              Nomor Surat Jalan Supplier <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="dispatchForm.surat_jalan"
              type="text"
              required
              placeholder="Contoh: SJ/SNX/X/2026/041"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-blue-500 text-xs font-mono font-bold"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">
              Ekspedisi / Armada Pengantar <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="dispatchForm.courier"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-blue-500 text-xs cursor-pointer bg-white"
            >
              <option value="Armada Internal Supplier (Driver Perusahaan)">Armada Internal Supplier (Driver Perusahaan)</option>
              <option value="JNE Trucking (JTR)">JNE Trucking (JTR)</option>
              <option value="Deliveree Box">Deliveree Box</option>
              <option value="Lalamove Van / Pick-up">Lalamove Van / Pick-up</option>
              <option value="SiCepat Cargo">SiCepat Cargo</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <label class="block font-bold text-slate-700 mb-1">No. Resi / Plat Truk</label>
            <input
              v-model="dispatchForm.tracking_number"
              type="text"
              placeholder="Contoh: B 9481 UYT"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-blue-500 text-xs font-mono"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Tanggal Pengiriman</label>
            <input
              v-model="dispatchForm.dispatch_date"
              type="date"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-blue-500 text-xs"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Estimasi Tiba di CV</label>
            <input
              v-model="dispatchForm.est_arrival"
              type="date"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-blue-500 text-xs"
            />
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Catatan Pengiriman / Kemasan</label>
          <textarea
            v-model="dispatchForm.notes"
            rows="2"
            placeholder="Contoh: Dikemas dalam 4 koli kayu standar garansi resmi, mohon diverifikasi bersama driver saat serah terima."
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-blue-500 text-xs"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
          <button
            type="button"
            class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold"
            @click="dispatchModalOpen = false"
          >
            Batal
          </button>
          <button
            type="submit"
            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg font-bold shadow-xs cursor-pointer flex items-center gap-1.5"
          >
            <Truck class="w-3.5 h-3.5" />
            <span>Kirim Barang Sekarang</span>
          </button>
        </div>
      </form>
    </Modal>

    <!-- ======================================================== -->
    <!-- MODAL 3: DETAIL PO & CETAK DOKUMEN RESMI                  -->
    <!-- ======================================================== -->
    <Modal
      v-model="detailModalOpen"
      :title="'Dokumen Purchase Order: ' + (activePO?.purchase_code || '')"
      subtitle="Surat Pesanan Pembelian resmi dari CV Alpharo Artha Jaya."
      size="xl"
    >
      <div v-if="activePO" class="space-y-4 text-xs">
        <!-- Official PO Header View -->
        <div class="p-4 bg-white border border-slate-300 rounded-xl space-y-4 shadow-2xs font-sans print:border-none print:shadow-none">
          <!-- Kop Perusahaan Alpharo -->
          <div class="flex items-start justify-between border-b-2 border-slate-900 pb-3">
            <div>
              <h2 class="text-base font-extrabold text-slate-900 uppercase tracking-tight">CV ALPHARO ARTHA JAYA</h2>
              <p class="text-[11px] text-slate-600">Supplier & Contractor Management Platform</p>
              <p class="text-[10px] text-slate-500">Jl. Cipinang Muara No. 12, Jatinegara, Jakarta Timur &bull; Telp: (021) 8590-1234</p>
            </div>
            <div class="text-right">
              <span class="inline-block px-3 py-1 bg-slate-900 text-white font-mono font-bold text-xs rounded">
                PURCHASE ORDER (PO)
              </span>
              <p class="font-mono text-xs font-bold text-slate-800 mt-1">{{ activePO.purchase_code }}</p>
              <p class="text-[11px] text-slate-500 font-mono">Tgl: {{ activePO.transaction_date }}</p>
            </div>
          </div>

          <!-- PO Target Info -->
          <div class="grid grid-cols-2 gap-4 text-xs">
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
              <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Ditujukan Kepada Supplier:</span>
              <p class="font-bold text-slate-900">{{ activePO.default_supplier }}</p>
              <p class="text-slate-600 text-[11px] mt-0.5">{{ currentSupplier?.address || '-' }}</p>
              <p class="text-slate-600 text-[11px]">Kontak: {{ currentSupplier?.contactPerson }} ({{ currentSupplier?.phone }})</p>
            </div>
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
              <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Informasi Pengadaan Proyek:</span>
              <p class="font-bold text-slate-900">{{ activePO.project_name }}</p>
              <p class="text-slate-600 text-[11px] mt-0.5">Kode Proyek: <span class="font-mono">{{ activePO.project_id || '-' }}</span></p>
              <p class="text-slate-600 text-[11px]">Metode Pembayaran: <span class="font-bold text-emerald-800">{{ activePO.payment_method }}</span></p>
            </div>
          </div>

          <!-- Items Table -->
          <table class="w-full text-left text-xs border border-slate-200">
            <thead class="bg-slate-100 border-b border-slate-200 text-slate-700 font-bold">
              <tr>
                <th class="py-2 px-3">No</th>
                <th class="py-2 px-3">Deskripsi Barang / SKU</th>
                <th class="py-2 px-3 text-center">Jumlah</th>
                <th class="py-2 px-3 text-right">Harga Satuan (Rp)</th>
                <th class="py-2 px-3 text-right">Subtotal (Rp)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              <tr v-for="(it, idx) in activePO.items" :key="idx">
                <td class="py-2 px-3 text-slate-500 font-mono">{{ idx + 1 }}</td>
                <td class="py-2 px-3 font-medium text-slate-900">{{ it.barang || it.name }}</td>
                <td class="py-2 px-3 text-center font-bold font-mono">{{ it.qty }}</td>
                <td class="py-2 px-3 text-right font-mono">{{ Number(it.harga_beli || 0).toLocaleString('id-ID') }}</td>
                <td class="py-2 px-3 text-right font-mono font-bold">{{ Number(it.subtotal || 0).toLocaleString('id-ID') }}</td>
              </tr>
            </tbody>
            <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
              <tr>
                <td colspan="4" class="py-2 px-3 text-right uppercase text-[11px]">Total Transaksi Pembelian:</td>
                <td class="py-2 px-3 text-right font-mono text-emerald-800 text-sm">
                  Rp {{ Number(activePO.total_price || activePO.subtotal || 0).toLocaleString('id-ID') }}
                </td>
              </tr>
            </tfoot>
          </table>

          <!-- Delivery Information if present -->
          <div v-if="activePO.delivery_info" class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs space-y-1">
            <span class="font-bold text-blue-950 uppercase text-[10px] block">Data Pengiriman Barang ke CV Alpharo:</span>
            <div>No. Surat Jalan: <span class="font-mono font-bold text-blue-900">{{ activePO.delivery_info.surat_jalan }}</span></div>
            <div>Armada / Resi: {{ activePO.delivery_info.courier }} ({{ activePO.delivery_info.tracking_number }})</div>
            <div>Tgl Kirim: {{ activePO.delivery_info.dispatch_date }} &bull; Est Tiba: {{ activePO.delivery_info.est_arrival }}</div>
          </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-200">
          <button
            type="button"
            class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-bold text-xs inline-flex items-center gap-1.5 cursor-pointer shadow-xs"
            @click="printPO"
          >
            <Printer class="w-3.5 h-3.5" />
            <span>Cetak Dokumen PO</span>
          </button>
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

    <!-- ======================================================== -->
    <!-- MODAL 4: RESOLUSI RETUR / BARANG PENGGANTI (TAHAP 5b)     -->
    <!-- ======================================================== -->
    <Modal
      v-model="resolutionModalOpen"
      :title="'Kirim Barang Pengganti: ' + (activePO?.purchase_code || '')"
      subtitle="Kirimkan nomor surat jalan pengganti untuk mengatasi ketidaksesuaian barang di gudang CV Alpharo."
      size="md"
    >
      <form v-if="activePO" class="space-y-4 text-xs" @submit.prevent="submitResolution">
        <div>
          <label class="block font-bold text-slate-700 mb-1">
            No. Surat Jalan Pengganti / Retur <span class="text-rose-500">*</span>
          </label>
          <input
            v-model="resolutionForm.replacement_surat_jalan"
            type="text"
            required
            placeholder="Contoh: SJ-RETUR/SNX/2026/001"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold text-xs"
          />
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Tindakan Resolusi</label>
          <select
            v-model="resolutionForm.action"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs bg-white cursor-pointer"
          >
            <option value="Penggantian Unit Baru 100% Sesuai Spesifikasi">Penggantian Unit Baru 100% Sesuai Spesifikasi</option>
            <option value="Pengiriman Kekurangan Item / Qty Tambahan">Pengiriman Kekurangan Item / Qty Tambahan</option>
            <option value="Koreksi dan Servis Resmi Garansi Supplier">Koreksi dan Servis Resmi Garansi Supplier</option>
          </select>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Catatan untuk Gudang & Finance Alpharo</label>
          <textarea
            v-model="resolutionForm.notes"
            rows="2"
            placeholder="Contoh: Unit pengganti baru telah dikirim hari ini via kurir internal, segel utuh dan siap diperiksa kembali."
            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
          <button
            type="button"
            class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold"
            @click="resolutionModalOpen = false"
          >
            Batal
          </button>
          <button
            type="submit"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-bold shadow-xs cursor-pointer flex items-center gap-1.5"
          >
            <RefreshCw class="w-3.5 h-3.5" />
            <span>Kirim Barang Pengganti</span>
          </button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AlpharoLogo from '../../components/common/AlpharoLogo.vue';
import Modal from '../../components/common/Modal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import {
  Truck,
  Building2,
  ChevronDown,
  LogOut,
  CheckCircle,
  CheckCircle2,
  MessageSquare,
  ShoppingBag,
  Clock,
  Package,
  Search,
  Check,
  RefreshCw,
  AlertTriangle,
  Printer,
  FileText,
  User,
} from 'lucide-vue-next';

const router = useRouter();
const toast = useToast();

const activeTab = ref('orders');
const supplierMenuOpen = ref(false);
const orderSearch = ref('');
const statusFilter = ref('');

const detailModalOpen = ref(false);
const confirmModalOpen = ref(false);
const dispatchModalOpen = ref(false);
const resolutionModalOpen = ref(false);

const activePO = ref(null);
const confirmNotes = ref('');

const dispatchForm = ref({
  surat_jalan: '',
  courier: 'Armada Internal Supplier (Driver Perusahaan)',
  tracking_number: '',
  dispatch_date: new Date().toISOString().split('T')[0],
  est_arrival: new Date(Date.now() + 86400000).toISOString().split('T')[0],
  notes: '',
});

const resolutionForm = ref({
  replacement_surat_jalan: '',
  action: 'Penggantian Unit Baru 100% Sesuai Spesifikasi',
  notes: '',
});

// Detect current active supplier
const currentSupplier = computed(() => {
  const user = store.currentUser;
  if (user?.supplierId) {
    const s = (store.suppliers || []).find((x) => x.id === user.supplierId);
    if (s) return s;
  }
  if (user?.supplierName) {
    const s = (store.suppliers || []).find((x) => x.name.toLowerCase() === user.supplierName.toLowerCase());
    if (s) return s;
  }
  if (user?.email) {
    const s = (store.suppliers || []).find((x) => x.email?.toLowerCase() === user.email?.toLowerCase());
    if (s) return s;
  }
  return store.suppliers?.[0] || {
    id: 1,
    code: 'SUP00001',
    name: 'PT Synnex Metrodata Indonesia',
    contactPerson: 'Hendra Gunawan (Sales Enterprise)',
    phone: '021-29345800',
    email: 'sales@synnexmetrodata.com',
    npwp: '01.345.678.9-031.000',
    bankName: 'BCA',
    bankAccountNumber: '5001239841',
    bankAccountName: 'PT Synnex Metrodata Indonesia',
  };
});

// Purchases belonging to this supplier
const supplierPurchases = computed(() => {
  const supName = (currentSupplier.value?.name || '').toLowerCase();
  const supEmail = (currentSupplier.value?.email || '').toLowerCase();
  return (store.purchases || []).filter((p) => {
    const pSup = (p.default_supplier || '').toLowerCase();
    const pEmail = (p.supplier_email || '').toLowerCase();
    const hasItem = p.items?.some((i) => (i.supplier || '').toLowerCase().includes(supName));
    return pSup.includes(supName) || pEmail === supEmail || hasItem;
  });
});

const pendingConfirmationCount = computed(() => {
  return supplierPurchases.value.filter(
    (p) => p.supplier_status === 'Menunggu Konfirmasi' || !p.supplier_status
  ).length;
});

const inProgressDeliveryCount = computed(() => {
  return supplierPurchases.value.filter(
    (p) => p.supplier_status === 'Dikonfirmasi Supplier' || p.supplier_status === 'Sedang Disiapkan' || p.supplier_status === 'Dikirim ke Gudang CV'
  ).length;
});

const completedCount = computed(() => {
  return supplierPurchases.value.filter((p) => p.supplier_status === 'Diterima Gudang').length;
});

const tabs = computed(() => [
  { key: 'orders', label: 'Pesanan Pembelian Masuk', icon: ShoppingBag, badge: pendingConfirmationCount.value },
  { key: 'shipments', label: 'Surat Jalan & Pengiriman', icon: Truck, badge: 0 },
  { key: 'discrepancy', label: 'Tindak Lanjut Barang / Retur', icon: AlertTriangle, badge: discrepancyPurchases.value.length },
  { key: 'profile', label: 'Profil Rekanan & Rekening', icon: User, badge: 0 },
]);

const filteredPurchases = computed(() => {
  return supplierPurchases.value.filter((p) => {
    if (statusFilter.value && p.supplier_status !== statusFilter.value) {
      if (statusFilter.value === 'Menunggu Konfirmasi' && p.supplier_status && p.supplier_status !== 'Menunggu Konfirmasi') {
        return false;
      } else if (statusFilter.value !== 'Menunggu Konfirmasi' && p.supplier_status !== statusFilter.value) {
        return false;
      }
    }
    if (orderSearch.value) {
      const q = orderSearch.value.toLowerCase();
      const code = (p.purchase_code || '').toLowerCase();
      const inv = (p.invoice_number || '').toLowerCase();
      const proj = (p.project_name || '').toLowerCase();
      const hasItem = p.items?.some((i) => (i.barang || i.name || '').toLowerCase().includes(q));
      if (!code.includes(q) && !inv.includes(q) && !proj.includes(q) && !hasItem) {
        return false;
      }
    }
    return true;
  });
});

const dispatchedPurchases = computed(() => {
  return supplierPurchases.value.filter(
    (p) => p.delivery_info || p.supplier_status === 'Dikirim ke Gudang CV' || p.supplier_status === 'Diterima Gudang'
  );
});

const discrepancyPurchases = computed(() => {
  return supplierPurchases.value.filter(
    (p) => p.supplier_status === 'Ketidaksesuaian' || p.discrepancy_info
  );
});

// Quick switch supplier
const selectActiveSupplier = (sup) => {
  storeActions.switchRole('SUPPLIER', sup);
  supplierMenuOpen.value = false;
  toast.success(`Beralih ke akun vendor: ${sup.name}`);
};

const cleanPhone = (phone) => {
  if (!phone) return '6281234567890';
  const digits = phone.replace(/[^0-9]/g, '');
  if (digits.startsWith('0')) return '62' + digits.slice(1);
  return digits;
};

// Open Modals
const openDetail = (p) => {
  activePO.value = p;
  detailModalOpen.value = true;
};

const openConfirmModal = (p) => {
  activePO.value = p;
  confirmNotes.value = 'Stok barang tersedia lengkap di gudang kami dan siap diproses.';
  confirmModalOpen.value = true;
};

const submitConfirmPO = () => {
  if (!activePO.value) return;
  storeActions.confirmPurchaseOrderBySupplier(activePO.value.purchase_code, confirmNotes.value);
  toast.success(`Pesanan ${activePO.value.purchase_code} berhasil dikonfirmasi!`);
  confirmModalOpen.value = false;
};

const rejectPO = (p) => {
  const reason = prompt('Masukkan alasan penolakan / kendala stok supplier:');
  if (reason) {
    storeActions.rejectPurchaseOrderBySupplier(p.purchase_code, reason);
    toast.error(`Pesanan ${p.purchase_code} ditolak. Finance telah diberitahu.`);
    confirmModalOpen.value = false;
  }
};

const openDispatchModal = (p) => {
  activePO.value = p;
  const randNum = Math.floor(100 + Math.random() * 900);
  const codeShort = (currentSupplier.value?.code || 'SUP').slice(-3);
  dispatchForm.value = {
    surat_jalan: `SJ/${codeShort}/${new Date().getFullYear()}/${randNum}`,
    courier: 'Armada Internal Supplier (Driver Perusahaan)',
    tracking_number: `B ${Math.floor(1000 + Math.random() * 9000)} ${['UYT', 'JKT', 'BGR', 'SMN'][Math.floor(Math.random() * 4)]}`,
    dispatch_date: new Date().toISOString().split('T')[0],
    est_arrival: new Date(Date.now() + 86400000).toISOString().split('T')[0],
    notes: 'Barang dikemas rapi dalam kemasan bersegel resmi garansi distributor.',
  };
  dispatchModalOpen.value = true;
};

const submitDispatch = () => {
  if (!activePO.value) return;
  storeActions.dispatchSupplierDelivery(activePO.value.purchase_code, dispatchForm.value);
  toast.success(`Barang PO ${activePO.value.purchase_code} telah diberangkatkan ke CV Alpharo! Surat Jalan: ${dispatchForm.value.surat_jalan}`);
  dispatchModalOpen.value = false;
};

const openResolutionModal = (p) => {
  activePO.value = p;
  resolutionForm.value = {
    replacement_surat_jalan: `SJ-RETUR/${Date.now().toString().slice(-6)}`,
    action: 'Penggantian Unit Baru 100% Sesuai Spesifikasi',
    notes: 'Unit pengganti telah disiapkan dan dikirimkan kembali ke gudang CV Alpharo.',
  };
  resolutionModalOpen.value = true;
};

const submitResolution = () => {
  if (!activePO.value) return;
  storeActions.resolveSupplierDiscrepancy(activePO.value.purchase_code, resolutionForm.value);
  toast.success(`Barang pengganti telah dikirim untuk PO ${activePO.value.purchase_code}!`);
  resolutionModalOpen.value = false;
};

const printPO = () => {
  window.print();
};
</script>

