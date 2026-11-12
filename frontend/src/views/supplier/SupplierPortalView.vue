<template>
  <div class="portal-shell ui-scope min-h-screen flex flex-col font-sans text-slate-800">
    <!-- TOP ENTERPRISE HEADER -->
    <header class="portal-topbar sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <!-- Logo & Portal Title -->
        <div class="flex items-center gap-3">
          <AlpharoLogo theme="dark" size="sm" :show-tagline="true" />
          <div class="hidden sm:block pl-4 border-l border-slate-200">
            <span class="text-xs font-semibold text-alpharo-600 uppercase tracking-widest block flex items-center gap-1.5">
              <Truck class="w-3.5 h-3.5" />
              <span>Portal Vendor & Supplier Rekanan</span>
            </span>
            <span class="text-[11px] text-slate-500">
              Penerimaan PO, Konfirmasi Pengadaan & Pengiriman Barang CV Alpharo
            </span>
          </div>
        </div>

        <!-- Supplier Profile & Quick Switcher -->
        <div class="flex items-center gap-3">
          <!-- Active Supplier Badge -->
          <div class="hidden md:flex flex-col text-right">
            <span class="text-xs font-semibold text-slate-800 leading-tight">
              {{ currentSupplier?.name || store.currentUser?.name || 'Mitra Supplier' }}
            </span>
            <span class="text-[11px] text-alpharo-600 font-mono">
              PIC: {{ currentSupplier?.contactPerson || 'Sales Team' }} &bull; {{ currentSupplier?.phone || '' }}
            </span>
          </div>

          <!-- Quick Switch Supplier Dropdown (For demo & evaluation) -->
          <div class="relative">
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-alpharo-50 text-slate-800 rounded-lg text-xs font-semibold backdrop-blur-xs transition-colors border border-slate-200 cursor-pointer"
              @click="supplierMenuOpen = !supplierMenuOpen"
            >
              <Building2 class="w-3.5 h-3.5 text-alpharo-600" />
              <span class="hidden sm:inline">Ganti Akun Supplier</span>
              <ChevronDown class="w-3 h-3 text-slate-500" />
            </button>

            <!-- Dropdown Menu -->
            <div
              v-if="supplierMenuOpen"
              class="ui-panel absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-50 text-slate-800 text-xs"
            >
              <div class="px-3 py-1.5 border-b border-slate-100 text-[11px] font-semibold text-slate-400 uppercase">
                Pilih Akun Supplier Terdaftar:
              </div>
              <div class="max-h-64 overflow-y-auto divide-y divide-slate-50">
                <button
                  v-for="s in (store.suppliers || [])"
                  :key="s.id"
                  type="button"
                  :class="[
                    currentSupplier?.id === s.id ? 'bg-amber-50 text-amber-900 font-semibold' : 'hover:bg-slate-50 text-slate-700',
                    'w-full text-left px-3 py-2 flex items-start gap-2 transition-colors cursor-pointer'
                  ]"
                  @click="selectActiveSupplier(s)"
                >
                  <span class="w-2 h-2 rounded-full bg-amber-500 mt-1 shrink-0"></span>
                  <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold">{{ s.name }}</p>
                    <p class="text-[11px] text-slate-400 truncate">{{ s.email }} &bull; {{ s.phone }}</p>
                  </div>
                </button>
              </div>
            </div>
          </div>

          <!-- Logout Button -->
          <button
            type="button"
            @click="handleLogout"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-alpharo-600 hover:bg-alpharo-700 active:bg-alpharo-800 text-white rounded-lg text-xs font-semibold transition-colors cursor-pointer shadow-xs"
            title="Keluar ke Halaman Login"
          >
            <LogOut class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Keluar</span>
          </button>
        </div>
      </div>
    </header>

    <!-- HERO / SUPPLIER PROFILE CARD -->
    <div class="bg-white border-b border-slate-200/90 py-5 px-4 sm:px-6 lg:px-8 shadow-2xs">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="px-2.5 py-0.5 bg-alpharo-50 text-alpharo-700 font-medium text-[11px] rounded-md border border-alpharo-100">
              Vendor Resmi CV Alpharo Artha Jaya
            </span>
            <span class="text-xs text-slate-400 font-mono">Kode: {{ currentSupplier?.code || 'SUP00001' }}</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-semibold text-slate-900 tracking-tight flex items-center gap-2">
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
        <div class="ui-panel bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
          <div class="flex items-center justify-between text-slate-500 mb-1">
            <span class="text-[11px] font-semibold uppercase tracking-wider">Total PO Masuk</span>
            <ShoppingBag class="w-4 h-4 text-alpharo-600" />
          </div>
          <div class="text-xl sm:text-2xl font-semibold font-mono text-slate-900">
            {{ supplierPurchases.length }}
          </div>
          <div class="text-[11px] text-slate-400 mt-0.5">Dari Finance CV Alpharo</div>
        </div>

        <!-- 2. Menunggu Konfirmasi -->
        <div class="ui-panel bg-white p-4 rounded-xl border border-amber-200 bg-amber-50/40 shadow-2xs">
          <div class="flex items-center justify-between text-amber-800 mb-1">
            <span class="text-[11px] font-semibold uppercase tracking-wider">Perlu Konfirmasi</span>
            <Clock class="w-4 h-4 text-amber-600" />
          </div>
          <div class="text-xl sm:text-2xl font-semibold font-mono text-amber-900">
            {{ pendingConfirmationCount }}
          </div>
          <div class="text-[11px] text-amber-700 font-semibold mt-0.5">Harap segera diverifikasi</div>
        </div>

        <!-- 3. Sedang Disiapkan / Dikirim -->
        <div class="ui-panel bg-white p-4 rounded-xl border border-alpharo-200 bg-alpharo-50/40 shadow-2xs">
          <div class="flex items-center justify-between text-alpharo-800 mb-1">
            <span class="text-[11px] font-semibold uppercase tracking-wider">Dalam Proses / Kirim</span>
            <Truck class="w-4 h-4 text-alpharo-600" />
          </div>
          <div class="text-xl sm:text-2xl font-semibold font-mono text-alpharo-900">
            {{ inProgressDeliveryCount }}
          </div>
          <div class="text-[11px] text-alpharo-700 mt-0.5">Pengiriman ke gudang</div>
        </div>

        <!-- 4. Selesai Diterima Gudang -->
        <div class="ui-panel bg-white p-4 rounded-xl border border-emerald-200 bg-emerald-50/40 shadow-2xs">
          <div class="flex items-center justify-between text-emerald-800 mb-1">
            <span class="text-[11px] font-semibold uppercase tracking-wider">Selesai / Diterima</span>
            <CheckCircle2 class="w-4 h-4 text-emerald-600" />
          </div>
          <div class="text-xl sm:text-2xl font-semibold font-mono text-emerald-900">
            {{ completedCount }}
          </div>
          <div class="text-[11px] text-emerald-700 mt-0.5">Tuntas diperiksa fisik</div>
        </div>
      </div>

      <!-- TABS NAVIGATION -->
      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs p-1.5 flex flex-wrap gap-1.5">
        <button
          v-for="t in tabs"
          :key="t.key"
          type="button"
          :class="[
            activeTab === t.key
              ? 'bg-alpharo-600 text-white font-semibold shadow-xs'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium',
            'px-3.5 py-2 rounded-lg text-xs flex items-center gap-2 transition-all cursor-pointer'
          ]"
          @click="switchTab(t.key)"
        >
          <component :is="t.icon" class="w-4 h-4" />
          <span>{{ t.label }}</span>
          <span
            v-if="t.badge !== undefined && t.badge > 0"
            :class="activeTab === t.key ? 'bg-amber-400 text-slate-950' : 'bg-amber-100 text-amber-900'"
            class="px-2 py-0.5 rounded-full text-[11px] font-semibold"
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
        <div class="ui-panel bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-col sm:flex-row gap-3 items-center justify-between">
          <div class="relative w-full sm:w-80">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="orderSearch"
              type="text"
              placeholder="Cari kode PO, faktur, atau nama barang..."
              class="w-full pl-9 pr-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/20 focus:border-alpharo-500 bg-white transition-all text-slate-900"
            />
          </div>

          <div class="flex items-center gap-2 w-full sm:w-auto">
            <select
              v-model="statusFilter"
              class="w-full sm:w-auto px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/20 focus:border-alpharo-500 bg-white cursor-pointer transition-all text-slate-800 font-medium"
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

        <section aria-label="Daftar pesanan supplier" class="ui-panel min-w-0 rounded-xl border border-slate-200/90 bg-white shadow-2xs">
          <div class="flex min-w-0 flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-4 sm:px-5">
            <h2 class="text-xs font-semibold text-slate-900">Pesanan Masuk</h2>
            <span class="text-[11px] text-slate-500">{{ filteredPurchases.length }} pesanan</span>
          </div>

          <div v-if="filteredPurchases.length === 0" class="px-4 py-12 text-center text-slate-500">
            <ShoppingBag class="mx-auto mb-2 h-10 w-10 text-slate-300" />
            <p class="text-xs font-semibold text-slate-700">Tidak ada pesanan pembelian yang sesuai filter.</p>
            <p class="mt-0.5 text-[11px] text-slate-500">Pesanan baru dari Finance akan muncul di sini.</p>
          </div>

          <template v-else>
            <div aria-hidden="true" class="hidden grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)_minmax(12rem,0.65fr)] gap-6 border-b border-slate-100 px-5 py-2.5 text-[11px] font-medium text-slate-500 lg:grid">
              <span>Pesanan &amp; Proyek</span><span>Barang &amp; Total</span><span class="text-right">Status &amp; Aksi</span>
            </div>
            <ol class="min-w-0 divide-y divide-slate-200">
              <li v-for="p in filteredPurchases" :key="p.purchase_code || p.id" class="min-w-0">
                <article :aria-label="'PO ' + p.purchase_code" class="grid min-w-0 gap-4 px-4 py-4 sm:px-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)_minmax(12rem,0.65fr)] lg:gap-6">
                  <div class="min-w-0">
                    <div class="flex min-w-0 flex-wrap items-baseline gap-x-3 gap-y-1">
                      <h3 class="wrap-anywhere font-mono text-xs font-semibold text-slate-900">{{ p.purchase_code }}</h3>
                      <span class="wrap-anywhere text-[11px] text-slate-500">{{ p.transaction_date }}</span>
                    </div>
                    <p class="mt-2 wrap-anywhere text-xs leading-relaxed text-slate-700">{{ p.project_name }}</p>
                  </div>
                  <div class="min-w-0">
                    <PurchaseItemList :items="p.items" />
                    <p class="mt-2 wrap-anywhere font-mono text-xs font-semibold text-slate-900">Rp {{ Number(p.total_price || p.subtotal || 0).toLocaleString('id-ID') }}</p>
                  </div>
                  <div class="flex min-w-0 flex-col items-start gap-3 lg:items-end">
                    <span :data-order-status="getOrderStatus(p).label" :class="getOrderStatus(p).color" class="inline-flex max-w-full items-center gap-1.5 text-xs font-medium leading-relaxed lg:text-right">
                      <span aria-hidden="true" class="h-1.5 w-1.5 shrink-0 rounded-full bg-current"></span>
                      <span class="min-w-0 wrap-anywhere">{{ getOrderStatus(p).label }}</span>
                    </span>
                    <div class="flex max-w-full flex-wrap items-center gap-2 lg:justify-end">
                      <template v-if="!isReceiptConfirmedByWarehouse(p)">
                        <button v-if="p.supplier_status === 'Menunggu Konfirmasi' || !p.supplier_status" type="button" class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg bg-alpharo-600 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-alpharo-700" @click="openConfirmModal(p)">
                          <Check class="h-3.5 w-3.5 shrink-0" /><span>Konfirmasi PO</span>
                        </button>
                        <button v-else-if="p.supplier_status === 'Dikonfirmasi Supplier' || p.supplier_status === 'Sedang Disiapkan'" type="button" class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg bg-alpharo-600 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-alpharo-700" @click="openDispatchModal(p)">
                          <Truck class="h-3.5 w-3.5 shrink-0" /><span>Kirim Barang</span>
                        </button>
                        <button v-else-if="p.supplier_status === 'Ketidaksesuaian Fisik (Menunggu Retur)' || p.supplier_status === 'Ketidaksesuaian'" type="button" class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg bg-alpharo-600 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-alpharo-700" @click="openResolutionModal(p)">
                          <RefreshCw class="h-3.5 w-3.5 shrink-0" /><span>Kirim Pengganti</span>
                        </button>
                        <button v-else-if="p.supplier_status === 'Dikirim ke Gudang CV' || p.supplier_status === 'Barang Pengganti Dikirim' || p.delivery_info?.shipping_status" type="button" class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg bg-alpharo-600 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-alpharo-700" @click="openManageShippingModal(p)">
                          <Truck class="h-3.5 w-3.5 shrink-0" /><span>Kelola Pengiriman</span>
                        </button>
                      </template>
                      <button type="button" class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50" @click="openDetail(p)">
                        <Eye class="h-3.5 w-3.5 shrink-0" /><span>Detail</span>
                      </button>
                    </div>
                  </div>
                </article>
              </li>
            </ol>
          </template>
        </section>
      </section>

      <!-- ======================================================== -->
      <!-- TAB 2: PENGIRIMAN & SURAT JALAN SUPPLIER                 -->
      <!-- ======================================================== -->
      <section v-if="activeTab === 'shipments'" class="space-y-4">
        <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs p-4 sm:p-5">
          <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
            <div>
              <h2 class="text-sm font-semibold text-slate-900">Arsip Pengiriman & Surat Jalan Supplier</h2>
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
              class="ui-panel p-4 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-white hover:border-alpharo-300 hover:shadow-xs transition-all flex flex-col justify-between"
            >
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="font-mono font-semibold text-slate-900 text-xs">{{ p.delivery_info?.surat_jalan || 'SJ-PENDING' }}</span>
                  <span
                    :class="[
                      p.supplier_status === 'Diterima Gudang' ? 'bg-emerald-100 text-emerald-900 border-emerald-200' : 'bg-alpharo-100 text-alpharo-900 border-alpharo-200',
                      'px-2 py-0.5 rounded text-[11px] font-semibold border'
                    ]"
                  >
                    {{ p.supplier_status === 'Diterima Gudang' ? '✓ Diterima Gudang CV' : '🚚 Dalam Perjalanan' }}
                  </span>
                </div>

                <div class="text-xs font-semibold text-slate-800">
                  PO: <button type="button" @click="openDetail(p)" class="font-mono text-alpharo-600 hover:text-alpharo-900 hover:underline font-semibold cursor-pointer">{{ p.purchase_code }}</button> &bull; {{ p.project_name }}
                </div>

                <div class="mt-2.5 p-2.5 bg-white rounded-lg border border-slate-200/90 text-xs space-y-1 text-slate-600">
                  <div><strong>Ekspedisi / Kurir:</strong> {{ p.delivery_info?.courier || '-' }}</div>
                  <div><strong>No. Resi / Plat:</strong> <span class="font-mono font-semibold text-slate-900">{{ p.delivery_info?.tracking_number || '-' }}</span></div>
                  <div><strong>Tanggal Kirim:</strong> {{ p.delivery_info?.dispatch_date || p.transaction_date }} &bull; Est: {{ p.delivery_info?.est_arrival || '-' }}</div>
                  <div v-if="p.delivery_info?.shipping_status" class="text-alpharo-700 font-semibold text-[11px] flex items-center gap-1">
                    <Truck class="w-3.5 h-3.5" />
                    <span>Status Kurir: {{ p.delivery_info.shipping_status }}</span>
                  </div>
                  <div v-if="p.delivery_info?.notes" class="text-[11px] text-slate-500 italic mt-1">
                    "{{ p.delivery_info?.notes }}"
                  </div>
                </div>

                <!-- Items delivered -->
                <PurchaseItemList :items="p.items" class="mt-3 border-t border-slate-200 pt-3" />
              </div>

              <div class="pt-3 border-t border-slate-200 mt-3 flex items-center justify-between gap-2">
                <span class="text-[11px] text-slate-500 font-mono">
                  Update: {{ p.delivery_info?.last_updated || 'Terkirim' }}
                </span>
                <div class="flex items-center gap-1.5">
                  <button
                    v-if="!isReceiptConfirmedByWarehouse(p) && (p.supplier_status === 'Dikirim ke Gudang CV' || p.supplier_status === 'Barang Pengganti Dikirim')"
                    type="button"
                    class="px-3 py-1.5 bg-alpharo-600 hover:bg-alpharo-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 shadow-2xs transition-colors cursor-pointer"
                    @click="openManageShippingModal(p)"
                  >
                    <Truck class="w-3.5 h-3.5" />
                    <span>Update Status Kurir</span>
                  </button>
                  <button
                    type="button"
                    class="px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 cursor-pointer"
                    @click="openDetail(p)"
                  >
                    Detail
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ======================================================== -->
      <!-- TAB 3: TINDAK LANJUT KETIDAKSESUAIAN / RETUR (TAHAP 5b)  -->
      <!-- ======================================================== -->
      <section v-if="activeTab === 'discrepancy'" class="space-y-4">
        <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs p-4 sm:p-5">
          <div class="flex items-center gap-2 pb-3 border-b border-slate-200 mb-4">
            <AlertTriangle class="w-5 h-5 text-amber-600" />
            <div>
              <h2 class="text-sm font-semibold text-slate-900">Tindak Lanjut Ketidaksesuaian Barang (PROBIS TO-BE Tahap 5b)</h2>
              <p class="text-xs text-slate-500">
                Pemberitahuan dari Gudang & Finance mengenai kerusakan atau ketidaksesuaian fisik barang yang memerlukan penggantian/resolusi.
              </p>
            </div>
          </div>

          <!-- Discrepancy Items -->
          <div v-if="discrepancyPurchases.length === 0" class="text-center py-12 text-slate-500 text-xs">
            <CheckCircle2 class="w-10 h-10 text-emerald-500 mx-auto mb-2" />
            <p class="font-semibold text-slate-800 text-sm">Tidak Ada Laporan Ketidaksesuaian!</p>
            <p class="text-slate-400 mt-0.5">Semua barang yang dikirimkan ke gudang CV Alpharo dalam kondisi baik dan sesuai spesifikasi.</p>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="p in discrepancyPurchases"
              :key="p.purchase_code"
              class="p-4 sm:p-5 rounded-xl border border-amber-300 bg-amber-50/40 shadow-2xs flex flex-col justify-between gap-4"
            >
              <div>
                <div class="flex items-center justify-between gap-2 mb-2 flex-wrap">
                  <div class="flex items-center gap-2">
                    <span
                      v-if="p.supplier_status === 'Barang Pengganti Dikirim'"
                      class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-alpharo-100 text-alpharo-800 border border-alpharo-200 inline-flex items-center gap-1"
                    >
                      <RefreshCw class="w-3 h-3 text-alpharo-600 animate-spin" />
                      <span>Barang Pengganti Sedang Dikirim ke Gudang</span>
                    </span>
                    <span
                      v-else
                      class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-100 text-rose-900 border border-rose-200 inline-flex items-center gap-1"
                    >
                      <AlertTriangle class="w-3 h-3 text-rose-600" />
                      <span>Kerusakan Dilaporkan Gudang (Butuh Penggantian)</span>
                    </span>
                    <span class="font-mono text-xs font-semibold text-slate-900">PO: {{ p.purchase_code }}</span>
                  </div>
                  <span class="text-[11px] font-mono text-slate-500">
                    Dilaporkan: {{ p.discrepancy_reported_at || p.discrepancy_info?.date || 'Hari ini' }}
                  </span>
                </div>

                <div class="text-xs font-semibold text-slate-900 mb-1">
                  Proyek: {{ p.project_name }} &bull; Surat Jalan Awal: <span class="font-mono text-slate-700">{{ p.delivery_info?.surat_jalan || '-' }}</span>
                </div>

                <!-- Rincian Keluhan Kerusakan dari Gudang -->
                <div class="p-3 bg-white border border-amber-200 rounded-lg text-xs space-y-2 mt-2">
                  <div class="font-semibold text-rose-800 flex items-center gap-1.5">
                    <AlertTriangle class="w-3.5 h-3.5 text-rose-600 shrink-0" />
                    <span>Laporan Kerusakan / Ketidaksesuaian Fisik dari Gudang:</span>
                  </div>
                  <p class="text-slate-700 leading-relaxed pl-5 italic">
                    "{{ p.discrepancy_reason || p.discrepancy_info?.notes || 'Spesifikasi barang tidak sesuai atau terdapat kerusakan fisik saat tiba di gudang.' }}"
                  </p>

                  <!-- Rincian Barang yang Diretur (Dipilih oleh Gudang) -->
                  <div
                    v-if="(p.discrepancy_items && p.discrepancy_items.length > 0) || (p.discrepancy_info?.items && p.discrepancy_info.items.length > 0)"
                    class="pt-2 border-t border-amber-100"
                  >
                    <p class="font-semibold text-rose-950 mb-1.5 flex items-center gap-1">
                      <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                      <span>Barang yang Harus Diganti / Diretur ({{ (p.discrepancy_items || p.discrepancy_info?.items || []).length }} item):</span>
                    </p>
                    <div class="space-y-1.5 pl-3">
                      <div
                        v-for="(di, diIdx) in (p.discrepancy_items || p.discrepancy_info?.items || [])"
                        :key="diIdx"
                        class="p-2 rounded-lg bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-1"
                      >
                        <div>
                          <span class="font-bold text-slate-900">{{ di.barang }}</span>
                          <span v-if="di.notes" class="text-slate-600 block text-[11px]">
                            Keluhan: <span class="italic text-rose-600">{{ di.notes }}</span>
                          </span>
                        </div>
                        <div class="shrink-0 text-right">
                          <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 font-mono font-bold text-[11px]">
                            Rusak: {{ di.damaged_qty }} {{ di.unit }}
                          </span>
                          <span v-if="di.total_qty" class="text-[10px] text-slate-500 block font-mono">
                            dari total {{ di.total_qty }} {{ di.unit }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Barang yang Diterima dalam Kondisi Baik (Tidak Diretur) -->
                  <div
                    v-if="(p.undamaged_items && p.undamaged_items.length > 0) || (p.discrepancy_info?.undamaged_items && p.discrepancy_info.undamaged_items.length > 0)"
                    class="pt-2 border-t border-amber-100"
                  >
                    <p class="font-semibold text-emerald-950 mb-1 flex items-center gap-1">
                      <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600" />
                      <span>Barang yang Diterima Kondisi Baik (Tidak Perlu Diganti):</span>
                    </p>
                    <div class="flex flex-wrap gap-1.5 pl-3">
                      <span
                        v-for="(ui, uiIdx) in (p.undamaged_items || p.discrepancy_info?.undamaged_items || [])"
                        :key="uiIdx"
                        class="px-2 py-0.5 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono text-[11px]"
                      >
                        ✓ {{ ui.barang }} ({{ ui.good_qty || ui.total_qty }} {{ ui.unit }})
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Galeri Bukti Foto / Video Kerusakan dari Gudang -->
                <div
                  v-if="(p.discrepancy_media && p.discrepancy_media.length > 0) || (p.discrepancy_info?.media && p.discrepancy_info.media.length > 0)"
                  class="mt-3 pt-3 border-t border-amber-200/60"
                >
                  <p class="text-xs font-semibold text-slate-800 mb-2 flex items-center gap-1.5">
                    <Camera class="w-4 h-4 text-rose-600" />
                    <span>Bukti Foto & Video Kerusakan yang Diunggah Gudang ({{ (p.discrepancy_media || p.discrepancy_info?.media || []).length }} File):</span>
                  </p>
                  <div class="flex items-center gap-3 overflow-x-auto pb-2 pt-1 select-none">
                    <div
                      v-for="(m, mIdx) in (p.discrepancy_media || p.discrepancy_info?.media || [])"
                      :key="mIdx"
                      class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl overflow-hidden relative shadow-2xs group cursor-pointer border border-slate-200 bg-slate-950 shrink-0"
                      title="Klik untuk pratinjau pop-up"
                      @click="openMediaPreview(m)"
                    >
                      <img
                        v-if="m.type === 'image'"
                        :src="m.url"
                        :alt="m.name"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                      />
                      <div v-else class="w-full h-full relative flex items-center justify-center bg-black">
                        <video
                          :src="m.url"
                          class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                          preload="metadata"
                        ></video>
                        <span class="absolute bottom-1.5 left-1.5 px-1.5 py-0.5 bg-black/75 backdrop-blur-xs text-white rounded-md text-[11px] font-mono flex items-center gap-1 font-semibold z-10">
                          <Video class="w-3 h-3 text-white" />
                          <span>{{ m.duration || '0:24' }}</span>
                        </span>
                      </div>
                      <div class="absolute inset-0 bg-black/0 group-hover:bg-black/25 transition-colors pointer-events-none flex items-center justify-center">
                        <span class="opacity-0 group-hover:opacity-100 p-1.5 bg-black/60 text-white rounded-full transition-opacity">
                          <Eye class="w-4 h-4" />
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Info Barang Pengganti yang Telah Dikirim -->
                <div
                  v-if="p.supplier_status === 'Barang Pengganti Dikirim'"
                  class="mt-3 p-3 bg-alpharo-50 border border-alpharo-200 rounded-lg text-xs space-y-1 text-alpharo-950"
                >
                  <div class="font-semibold flex items-center gap-1.5 text-alpharo-900">
                    <CheckCircle2 class="w-4 h-4 text-alpharo-600" />
                    <span>Informasi Barang Pengganti (Skenario 1 - Redelivery):</span>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1 font-mono text-[11px]">
                    <div>No. SJ Pengganti: <strong>{{ p.discrepancy_resolution?.replacement_surat_jalan || p.delivery_info?.surat_jalan }}</strong></div>
                    <div>Kurir: <strong>{{ p.discrepancy_resolution?.courier || p.delivery_info?.courier }}</strong></div>
                    <div>Resi/Plat: <strong>{{ p.discrepancy_resolution?.tracking_number || p.delivery_info?.tracking_number }}</strong></div>
                  </div>
                  <p class="text-[11px] text-alpharo-800 italic pt-1">
                    "{{ p.discrepancy_resolution?.notes || 'Barang pengganti telah dikirimkan kembali ke gudang CV Alpharo.' }}"
                  </p>
                </div>
              </div>

              <!-- Action Resolution Button -->
              <div class="pt-3 border-t border-amber-200/80 flex items-center justify-between flex-wrap gap-2">
                <span class="text-xs text-slate-500">
                  {{ p.supplier_status === 'Barang Pengganti Dikirim' ? 'Gudang CV Alpharo sedang menunggu kiriman tiba untuk pemeriksaan fisik ulang.' : 'Harap segera proses barang pengganti agar alur proyek tidak terhambat.' }}
                </span>
                <button
                  type="button"
                  :class="[
                    p.supplier_status === 'Barang Pengganti Dikirim'
                      ? 'bg-alpharo-600 hover:bg-alpharo-700'
                      : 'bg-emerald-600 hover:bg-emerald-700',
                    'px-4 py-2 text-white rounded-lg text-xs font-semibold shadow-2xs transition-colors cursor-pointer flex items-center gap-1.5'
                  ]"
                  @click="openResolutionModal(p)"
                >
                  <RefreshCw class="w-3.5 h-3.5" />
                  <span>{{ p.supplier_status === 'Barang Pengganti Dikirim' ? 'Perbarui Info Pengiriman Pengganti' : 'Kirim Ulang Barang Pengganti Sekarang' }}</span>
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
        <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs p-5 max-w-3xl">
          <h2 class="text-sm font-semibold text-slate-900 mb-4 pb-2 border-b border-slate-200">
            Informasi Profil & Rekening Pembayaran Supplier
          </h2>

          <div class="space-y-3 text-xs">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-slate-600 mb-1">Nama Perusahaan Supplier</label>
                <input
                  :value="currentSupplier?.name"
                  readonly
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 font-semibold"
                />
              </div>
              <div>
                <label class="block font-semibold text-slate-600 mb-1">Kode Vendor</label>
                <input
                  :value="currentSupplier?.code"
                  readonly
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 font-mono font-semibold"
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
              <h3 class="text-xs font-semibold text-slate-900 mb-2 uppercase">Rekening Pembayaran dari Finance Alpharo</h3>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label class="block font-semibold text-slate-600 mb-1">Bank</label>
                  <input
                    :value="currentSupplier?.bankName || 'BCA'"
                    readonly
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 font-semibold"
                  />
                </div>
                <div>
                  <label class="block font-semibold text-slate-600 mb-1">Nomor Rekening</label>
                  <input
                    :value="currentSupplier?.bankAccountNumber || '-'"
                    readonly
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 font-mono font-semibold"
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
          <table class="ui-table w-full text-left text-xs">
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
                <td class="py-2 px-3 text-center font-semibold font-mono">{{ it.qty }}</td>
                <td class="py-2 px-3 text-right font-mono">Rp {{ Number(it.harga_beli || 0).toLocaleString('id-ID') }}</td>
                <td class="py-2 px-3 text-right font-mono font-semibold text-slate-900">Rp {{ Number(it.subtotal || 0).toLocaleString('id-ID') }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Catatan Konfirmasi / Estimasi Siap Kirim</label>
          <textarea
            v-model="confirmNotes"
            rows="2"
            placeholder="Contoh: Stok tersedia lengkap di gudang kami. Siap dikirim besok pagi."
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-alpharo-400 text-xs"
          ></textarea>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-200">
          <button
            type="button"
            class="px-3 py-1.5 text-rose-600 hover:bg-rose-50 rounded font-semibold text-xs transition-colors"
            @click="openRejectModal(activePO)"
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
              class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold shadow-xs cursor-pointer"
              @click="submitConfirmPO"
            >
              Ya, Terima PO
            </button>
          </div>
        </div>
      </div>
    </Modal>

    <!-- ======================================================== -->
    <!-- MODAL: PENOLAKAN PESANAN / KENDALA STOK SUPPLIER          -->
    <!-- ======================================================== -->
    <Modal
      v-model="rejectModalOpen"
      :title="'Tolak Pesanan PO: ' + (activePO?.purchase_code || '')"
      subtitle="Berikan alasan atau kendala penolakan agar Tim Finance dapat memproses alternatif pengadaan."
      size="md"
    >
      <div v-if="activePO" class="space-y-4 text-xs">
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-start gap-2.5">
          <AlertTriangle class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
          <div class="leading-relaxed">
            <p class="font-semibold text-rose-900">Konfirmasi Penolakan Pesanan</p>
            <p class="mt-0.5 text-rose-700">Status pesanan akan diubah menjadi <strong class="text-rose-900">Ditolak Supplier</strong> dan notifikasi otomatis dikirimkan ke Tim Finance serta Gudang CV Alpharo.</p>
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1.5">
            Alasan Penolakan / Kendala Stok Supplier <span class="text-rose-500">*</span>
          </label>
          <textarea
            v-model="rejectReason"
            rows="3"
            required
            placeholder="Ketikkan alasan kendala stok atau alasan penolakan pesanan ini..."
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 text-xs bg-slate-50 focus:bg-white transition-all text-slate-800"
          ></textarea>
        </div>

        <div>
          <span class="text-[11px] font-semibold text-slate-500 mb-1.5 block">Pilih Opsi Cepat:</span>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="opt in commonRejectReasons"
              :key="opt"
              type="button"
              class="px-2.5 py-1 rounded-md border border-slate-200 bg-white hover:bg-slate-50 text-[11px] text-slate-700 cursor-pointer transition-colors"
              @click="rejectReason = opt"
            >
              {{ opt }}
            </button>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
          <button
            type="button"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold cursor-pointer"
            @click="rejectModalOpen = false"
          >
            Batal
          </button>
          <button
            type="button"
            :disabled="!rejectReason.trim()"
            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 disabled:opacity-50 text-white rounded-lg font-semibold shadow-xs cursor-pointer flex items-center gap-1.5"
            @click="submitRejectPO"
          >
            <span>Kirim Penolakan</span>
          </button>
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
            <label class="block font-semibold text-slate-700 mb-1">
              Nomor Surat Jalan Supplier <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="dispatchForm.surat_jalan"
              type="text"
              required
              placeholder="Contoh: SJ/SNX/X/2026/041"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-alpharo-500 text-xs font-mono font-semibold"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">
              Ekspedisi / Armada Pengantar <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="dispatchForm.courier"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-alpharo-500 text-xs cursor-pointer bg-white"
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
            <label class="block font-semibold text-slate-700 mb-1">No. Resi / Plat Truk</label>
            <input
              v-model="dispatchForm.tracking_number"
              type="text"
              placeholder="Contoh: B 9481 UYT"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-alpharo-500 text-xs font-mono"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Tanggal Pengiriman</label>
            <input
              v-model="dispatchForm.dispatch_date"
              type="date"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-alpharo-500 text-xs"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Estimasi Tiba di CV</label>
            <input
              v-model="dispatchForm.est_arrival"
              type="date"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-alpharo-500 text-xs"
            />
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Catatan Pengiriman / Kemasan</label>
          <textarea
            v-model="dispatchForm.notes"
            rows="2"
            placeholder="Contoh: Dikemas dalam 4 koli kayu standar garansi resmi, mohon diverifikasi bersama driver saat serah terima."
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-alpharo-500 text-xs"
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
            class="px-4 py-1.5 bg-alpharo-600 hover:bg-alpharo-700 active:bg-alpharo-800 text-white rounded-lg font-semibold shadow-xs cursor-pointer flex items-center gap-1.5"
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
        <div class="ui-panel p-4 bg-white border border-slate-300 rounded-xl space-y-4 shadow-2xs font-sans print:border-none print:shadow-none">
          <!-- Kop Perusahaan Alpharo -->
          <div class="flex items-start justify-between border-b-2 border-slate-900 pb-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900 uppercase tracking-tight">CV ALPHARO ARTHA JAYA</h2>
              <p class="text-[11px] text-slate-600">Supplier & Contractor Management Platform</p>
              <p class="text-[11px] text-slate-500">Jl. Cipinang Muara No. 12, Jatinegara, Jakarta Timur &bull; Telp: (021) 8590-1234</p>
            </div>
            <div class="text-right">
              <span class="inline-block px-3 py-1 bg-slate-900 text-white font-mono font-semibold text-xs rounded">
                PURCHASE ORDER (PO)
              </span>
              <p class="font-mono text-xs font-semibold text-slate-800 mt-1">{{ activePO.purchase_code }}</p>
              <p class="text-[11px] text-slate-500 font-mono">Tgl: {{ activePO.transaction_date }}</p>
            </div>
          </div>

          <!-- PO Target Info -->
          <div class="grid grid-cols-2 gap-4 text-xs">
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
              <span class="text-[11px] uppercase font-semibold text-slate-400 block mb-1">Ditujukan Kepada Supplier:</span>
              <p class="font-semibold text-slate-900">{{ activePO.default_supplier }}</p>
              <p class="text-slate-600 text-[11px] mt-0.5">{{ currentSupplier?.address || '-' }}</p>
              <p class="text-slate-600 text-[11px]">Kontak: {{ currentSupplier?.contactPerson }} ({{ currentSupplier?.phone }})</p>
            </div>
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
              <span class="text-[11px] uppercase font-semibold text-slate-400 block mb-1">Informasi Pengadaan Proyek:</span>
              <p class="font-semibold text-slate-900">{{ activePO.project_name }}</p>
              <p class="text-slate-600 text-[11px] mt-0.5">Kode Proyek: <span class="font-mono">{{ activePO.project_id || '-' }}</span></p>
              <p class="wrap-anywhere text-[11px] text-slate-600">No. Faktur: <span class="font-mono">{{ activePO.invoice_number || '-' }}</span></p>
              <p class="text-slate-600 text-[11px]">Metode Pembayaran: <span class="font-semibold text-emerald-800">{{ activePO.payment_method }}</span></p>
              <p class="mt-1 wrap-anywhere text-[11px] text-slate-600">Status: <span :class="getOrderStatus(activePO).color">{{ getOrderStatus(activePO).label }}</span></p>
              <p v-if="activePO.delivery_info?.shipping_status && !isReceiptConfirmedByWarehouse(activePO)" class="mt-1 wrap-anywhere text-[11px] leading-relaxed text-slate-600">Perjalanan: {{ activePO.delivery_info.shipping_status }}</p>
            </div>
          </div>

          <!-- Items Table -->
          <table class="ui-table w-full text-left text-xs border border-slate-200">
            <thead class="bg-slate-100 border-b border-slate-200 text-slate-700 font-semibold">
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
                <td class="py-2 px-3 text-center font-semibold font-mono">{{ it.qty }}</td>
                <td class="py-2 px-3 text-right font-mono">{{ Number(it.harga_beli || 0).toLocaleString('id-ID') }}</td>
                <td class="py-2 px-3 text-right font-mono font-semibold">{{ Number(it.subtotal || 0).toLocaleString('id-ID') }}</td>
              </tr>
            </tbody>
            <tfoot class="bg-slate-50 font-semibold border-t border-slate-200">
              <tr>
                <td colspan="4" class="py-2 px-3 text-right uppercase text-[11px]">Total Transaksi Pembelian:</td>
                <td class="py-2 px-3 text-right font-mono text-emerald-800 text-sm">
                  Rp {{ Number(activePO.total_price || activePO.subtotal || 0).toLocaleString('id-ID') }}
                </td>
              </tr>
            </tfoot>
          </table>

          <!-- Print only delivery text -->
          <div v-if="activePO.delivery_info" class="hidden print:block p-3 border border-slate-300 rounded text-xs space-y-1">
            <div><strong>No. Surat Jalan:</strong> {{ activePO.delivery_info.surat_jalan }} &bull; <strong>Kurir:</strong> {{ activePO.delivery_info.courier }} ({{ activePO.delivery_info.tracking_number }})</div>
            <div><strong>Tgl Kirim:</strong> {{ activePO.delivery_info.dispatch_date }} &bull; <strong>Estimasi Tiba:</strong> {{ activePO.delivery_info.est_arrival }}</div>
          </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-200">
          <button
            type="button"
            class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-semibold text-xs inline-flex items-center gap-1.5 cursor-pointer shadow-xs"
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
          <label class="block font-semibold text-slate-700 mb-1">
            No. Surat Jalan Pengganti / Retur <span class="text-rose-500">*</span>
          </label>
          <input
            v-model="resolutionForm.replacement_surat_jalan"
            type="text"
            required
            placeholder="Contoh: SJ-REP/SNX/2026/001"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-semibold text-xs"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Kurir / Ekspedisi Pengganti</label>
            <input
              v-model="resolutionForm.courier"
              type="text"
              required
              placeholder="Armada Supplier / Driver Pabrik"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs"
            />
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">No. Resi / Plat Mobil</label>
            <input
              v-model="resolutionForm.tracking_number"
              type="text"
              placeholder="B 1234 XYZ"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono"
            />
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Tindakan Resolusi</label>
          <select
            v-model="resolutionForm.action"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs bg-white cursor-pointer"
          >
            <option value="Penggantian Unit Baru 100% Sesuai Spesifikasi">Penggantian Unit Baru 100% Sesuai Spesifikasi (Skenario 1)</option>
            <option value="Pengiriman Kekurangan Item / Qty Tambahan">Pengiriman Kekurangan Item / Qty Tambahan</option>
            <option value="Koreksi dan Servis Resmi Garansi Supplier">Koreksi dan Servis Resmi Garansi Supplier</option>
          </select>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Catatan untuk Gudang & Finance Alpharo</label>
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
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-semibold shadow-xs cursor-pointer flex items-center gap-1.5"
          >
            <RefreshCw class="w-3.5 h-3.5" />
            <span>Kirim Barang Pengganti</span>
          </button>
        </div>
      </form>
    </Modal>

    <!-- MODAL 5: PREVIEW MEDIA BUKTI KERUSAKAN GUDANG (FULL SIZE) -->
    <Modal
      v-model="mediaPreviewModalOpen"
      :title="activePreviewMedia?.name || 'Bukti Media Kerusakan Fisik'"
      subtitle="Bukti foto atau video kerusakan barang yang diunggah oleh Tim Gudang CV Alpharo"
      size="lg"
    >
      <div v-if="activePreviewMedia" class="flex flex-col items-center justify-center p-2 text-xs">
        <img
          v-if="activePreviewMedia.type === 'image'"
          :src="activePreviewMedia.url"
          :alt="activePreviewMedia.name"
          class="max-h-[65vh] max-w-full rounded-xl object-contain shadow-lg border border-slate-200"
        />
        <video
          v-else
          :src="activePreviewMedia.url"
          controls
          autoplay
          class="max-h-[65vh] w-full rounded-xl bg-black shadow-lg"
        ></video>
        <div class="mt-3 text-slate-500 font-mono text-center">
          Nama File: <strong>{{ activePreviewMedia.name }}</strong> &bull; Ukuran: {{ activePreviewMedia.size || '-' }}
        </div>
      </div>
    </Modal>

    <!-- ======================================================== -->
    <!-- MODAL 6: KELOLA STATUS PENGIRIMAN REAL-TIME (SUPPLIER)     -->
    <!-- ======================================================== -->
    <Modal
      v-model="manageShippingModalOpen"
      :title="'Kelola Status Pengiriman: ' + (activePO?.purchase_code || '')"
      subtitle="Perbarui status pergerakan kurir dan posisi pengiriman secara real-time ke Gudang CV Alpharo."
      size="lg"
    >
      <form v-if="activePO" class="space-y-4 text-xs" @submit.prevent="submitManageShipping">
        <!-- Info Ringkas Pengiriman & Petunjuk Alur -->
        <div class="space-y-2.5">
          <div class="p-3 bg-alpharo-50/70 border border-alpharo-200 rounded-xl flex items-center justify-between text-alpharo-950">
            <div>
              <span class="text-[11px] uppercase font-semibold text-alpharo-700 block">Surat Jalan Supplier:</span>
              <span class="font-mono font-semibold text-sm text-alpharo-900">{{ activePO.delivery_info?.surat_jalan || 'SJ-VENDOR' }}</span>
              <span class="text-[11px] text-slate-600 ml-2">({{ activePO.project_name }})</span>
            </div>
            <span class="text-xs font-mono font-semibold text-alpharo-800 bg-alpharo-100/80 px-2.5 py-1 rounded-md border border-alpharo-200">
              PO: {{ activePO.purchase_code }}
            </span>
          </div>

          <div class="p-3 bg-sky-50 border border-sky-200 rounded-xl flex items-start gap-2.5 text-xs text-sky-900">
            <Truck class="w-4 h-4 text-sky-600 shrink-0 mt-0.5" />
            <div class="leading-relaxed">
              <span class="font-semibold text-sky-950 block">Pembaruan Titik Perjalanan Kurir</span>
              <span class="text-sky-800 text-[11px]">
                Gunakan form ini untuk memperbarui progres kurir selama pengiriman ke gudang CV Alpharo. Konfirmasi fisik kedatangan & pemeriksaan barang dilakukan langsung oleh Tim Gudang saat kurir tiba.
              </span>
            </div>
          </div>
        </div>

        <!-- Form Input Real-time Status -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">
              Status Perjalanan Kurir Saat Ini <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="manageShippingForm.shipping_status"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/20 focus:border-alpharo-500 text-xs cursor-pointer bg-white font-medium"
            >
              <option v-for="option in shippingStatusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">
              Posisi / Titik Lokasi Terkini Kurir <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="manageShippingForm.location"
              type="text"
              required
              placeholder="Contoh: Tol Jakarta-Cikampek KM 14 / Hub Cakung"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/20 focus:border-alpharo-500 text-xs bg-white text-slate-900"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Ekspedisi / Kurir</label>
            <input
              v-model="manageShippingForm.courier"
              type="text"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/20 focus:border-alpharo-500 text-xs bg-white"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">No. Resi / Plat Mobil</label>
            <input
              v-model="manageShippingForm.tracking_number"
              type="text"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/20 focus:border-alpharo-500 text-xs font-mono font-semibold bg-white"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Estimasi Tiba (ETA)</label>
            <input
              v-model="manageShippingForm.est_arrival"
              type="text"
              placeholder="Contoh: Hari ini, 16:30 WIB"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/20 focus:border-alpharo-500 text-xs bg-white"
            />
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Catatan Tambahan Kurir / Kondisi Pengiriman</label>
          <textarea
            v-model="manageShippingForm.notes"
            rows="2"
            placeholder="Contoh: Driver Pak Joko (0812-xxxx-xxxx), armada truk colt diesel B 9123 ABC sudah keluar tol dekat lokasi gudang."
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-alpharo-500/20 focus:border-alpharo-500 text-xs bg-white text-slate-800"
          ></textarea>
        </div>

        <!-- Riwayat Checkpoint Pengiriman Sebelumnya -->
        <div v-if="activePO.delivery_info?.milestones?.length" class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
          <label class="block font-semibold text-slate-700 uppercase text-[11px]">Riwayat Checkpoint Terkirim:</label>
          <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
            <div
              v-for="ms in activePO.delivery_info.milestones"
              :key="ms.id"
              class="p-2 bg-white rounded border border-slate-200 text-[11px] flex items-center justify-between"
            >
              <div>
                <span class="font-semibold text-slate-900">{{ ms.status }}</span>
                <span class="text-slate-500 ml-1.5">&bull; {{ ms.location }}</span>
                <p v-if="ms.notes" class="text-slate-500 italic text-[11px] mt-0.5">{{ ms.notes }}</p>
              </div>
              <span class="text-[11px] font-mono text-slate-400 shrink-0">{{ ms.timestamp }}</span>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
          <button
            type="button"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold cursor-pointer transition-colors"
            @click="manageShippingModalOpen = false"
          >
            Batal
          </button>
          <button
            type="submit"
            class="px-4 py-2 bg-alpharo-600 hover:bg-alpharo-700 active:bg-alpharo-800 text-white rounded-lg font-semibold shadow-xs cursor-pointer flex items-center gap-1.5 transition-colors"
          >
            <Truck class="w-3.5 h-3.5" />
            <span>Simpan Update Kurir</span>
          </button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from '../../navigation';
import AlpharoLogo from '../../components/common/AlpharoLogo.vue';
import Modal from '../../components/common/Modal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import PurchaseItemList from '../../components/common/PurchaseItemList.vue';
import { SHIPPING_STATUS, SHIPPING_STATUS_OPTIONS, SUPPLIER_SHIPPING_STATUS_OPTIONS, normalizeShippingStatus } from '../../services/shippingStatus';
import { orderError } from '../../services/orderWorkflow';
import { login, logout } from '../../services/session';
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
  User,
  Camera,
  Video,
  Eye,
  X,
} from 'lucide-vue-next';

const router = useRouter();
const route = useRoute();
const toast = useToast();
const handleLogout = async () => {
  try { await logout(); } catch (error) { toast.error(orderError(error)); }
};

let purchaseRefreshTimer;
const refreshPurchases = () => {
  if (document.visibilityState === 'visible') storeActions.loadPurchases().catch(() => {});
};
onMounted(() => {
  storeActions.loadPurchases().catch(error => toast.error(orderError(error)));
  window.addEventListener('focus', refreshPurchases);
  purchaseRefreshTimer = window.setInterval(refreshPurchases, 30000);
});
onUnmounted(() => {
  window.removeEventListener('focus', refreshPurchases);
  window.clearInterval(purchaseRefreshTimer);
});

const validTabs = ['orders', 'shipments', 'discrepancy', 'profile'];
const activeTab = ref(validTabs.includes(route.query.tab) ? route.query.tab : 'orders');
watch(() => route.query.tab, tab => { activeTab.value = validTabs.includes(tab) ? tab : 'orders'; });
const switchTab = tab => {
  activeTab.value = tab;
  router.replace({ query: { ...route.query, tab } });
};
const supplierMenuOpen = ref(false);
const orderSearch = ref('');
const statusFilter = ref('');

const detailModalOpen = ref(false);
const confirmModalOpen = ref(false);
const rejectModalOpen = ref(false);
const rejectReason = ref('');
const commonRejectReasons = [
  'Stok barang kosong di gudang prinsipal',
  'Spesifikasi barang diskontinu / EOL',
  'Harga beli berubah dari prinsipal',
  'Estimasi pengiriman melebihi batas waktu tender',
];
const dispatchModalOpen = ref(false);
const resolutionModalOpen = ref(false);
const manageShippingModalOpen = ref(false);
const mediaPreviewModalOpen = ref(false);
const activePreviewMedia = ref(null);

const activePO = ref(null);
const confirmNotes = ref('');

const manageShippingForm = ref({
  shipping_status: SHIPPING_STATUS.DISPATCHED,
  location: '',
  courier: '',
  tracking_number: '',
  est_arrival: '',
  notes: '',
});

const openMediaPreview = (mediaItem) => {
  activePreviewMedia.value = mediaItem;
  mediaPreviewModalOpen.value = true;
};

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
  courier: 'Armada Internal Supplier (Driver Pengganti)',
  tracking_number: '',
  dispatch_date: new Date().toISOString().split('T')[0],
  est_arrival: new Date(Date.now() + 86400000).toISOString().split('T')[0],
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
    (p) => p.discrepancy_reason ||
           p.discrepancy_info ||
           p.supplier_status === 'Ketidaksesuaian Fisik (Menunggu Retur)' ||
           p.supplier_status === 'Barang Pengganti Dikirim' ||
           p.supplier_status === 'Ketidaksesuaian'
  );
});

// Quick switch supplier
const selectActiveSupplier = async (sup) => {
  supplierMenuOpen.value = false;
  try {
    await login({ email: sup.email, password: 'password' });
    router.replace({ query: { ...route.query } });
    toast.success(`Beralih ke akun vendor: ${sup.name}`);
  } catch (error) { toast.error(orderError(error)); }
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

const submitConfirmPO = async () => {
  try {
    if (!activePO.value) return;
    await storeActions.confirmPurchaseOrderBySupplier(activePO.value.purchase_code, confirmNotes.value);
    toast.success(`Pesanan ${activePO.value.purchase_code} berhasil dikonfirmasi!`);
    confirmModalOpen.value = false;
  } catch (error) { toast.error(orderError(error)); }
};

const openRejectModal = (p) => {
  activePO.value = p;
  rejectReason.value = '';
  confirmModalOpen.value = false;
  rejectModalOpen.value = true;
};

const rejectPO = openRejectModal;

const submitRejectPO = async () => {
  try {
    if (!activePO.value || !rejectReason.value.trim()) return;
    await storeActions.rejectPurchaseOrderBySupplier(activePO.value.purchase_code, rejectReason.value.trim());
    toast.error(`Pesanan ${activePO.value.purchase_code} ditolak. Tim Finance telah menerima notifikasi.`);
    rejectModalOpen.value = false;
  } catch (error) { toast.error(orderError(error)); }
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

const submitDispatch = async () => {
  try {
    if (!activePO.value) return;
    await storeActions.dispatchSupplierDelivery(activePO.value.purchase_code, dispatchForm.value);
    toast.success(`Barang PO ${activePO.value.purchase_code} telah diberangkatkan ke CV Alpharo! Surat Jalan: ${dispatchForm.value.surat_jalan}`);
    dispatchModalOpen.value = false;
  } catch (error) { toast.error(orderError(error)); }
};

const openResolutionModal = (p) => {
  activePO.value = p;
  const randNum = Math.floor(100 + Math.random() * 900);
  const codeShort = (currentSupplier.value?.code || 'SUP').slice(-3);
  resolutionForm.value = {
    replacement_surat_jalan: p.discrepancy_resolution?.replacement_surat_jalan || `SJ-REP/${codeShort}/${new Date().getFullYear()}/${randNum}`,
    action: p.discrepancy_resolution?.action || 'Penggantian Unit Baru 100% Sesuai Spesifikasi',
    courier: p.discrepancy_resolution?.courier || 'Armada Internal Supplier (Driver Pengganti)',
    tracking_number: p.discrepancy_resolution?.tracking_number || `B ${Math.floor(1000 + Math.random() * 9000)} ${['UYT', 'JKT', 'BGR', 'SMN'][Math.floor(Math.random() * 4)]}`,
    dispatch_date: new Date().toISOString().split('T')[0],
    est_arrival: new Date(Date.now() + 86400000).toISOString().split('T')[0],
    notes: p.discrepancy_resolution?.notes || 'Unit pengganti telah diperiksa lolos QC dan dikirimkan kembali ke gudang CV Alpharo.',
  };
  resolutionModalOpen.value = true;
};

const submitResolution = async () => {
  try {
    if (!activePO.value) return;
    await storeActions.resolveSupplierDiscrepancy(activePO.value.purchase_code, resolutionForm.value);
    toast.success(`Barang pengganti telah dikirim untuk PO ${activePO.value.purchase_code}!`);
    resolutionModalOpen.value = false;
  } catch (error) { toast.error(orderError(error)); }
};

const isReceiptConfirmedByWarehouse = (p) => {
  if (!p) return false;
  const s = String(p.supplier_status || '').toLowerCase();
  const st = String(p.status || '').toLowerCase();
  return (
    s.includes('diterima gudang') ||
    st === 'diterima_gudang' ||
    Boolean(p.received_at)
  );
};

// Compact display labels only; persisted workflow values remain unchanged.
const getOrderStatus = (p) => {
  const status = p.supplier_status;
  if (isReceiptConfirmedByWarehouse(p)) return { label: 'Diterima gudang', color: 'text-emerald-700' };
  if (!status || status === 'Menunggu Konfirmasi') return { label: 'Menunggu konfirmasi', color: 'text-amber-800' };
  if (['Dikonfirmasi Supplier', 'Sedang Disiapkan'].includes(status)) return { label: 'Disiapkan', color: 'text-blue-700' };
  if (['Ketidaksesuaian Fisik (Menunggu Retur)', 'Ketidaksesuaian'].includes(status)) return { label: 'Perlu penggantian', color: 'text-rose-700' };

  const shippingStatus = p.delivery_info?.shipping_status;
  if (shippingStatus) {
    const labels = {
      [SHIPPING_STATUS.PACKING]: { label: 'Dikemas', color: 'text-blue-700' },
      [SHIPPING_STATUS.DISPATCHED]: { label: 'Dijemput kurir', color: 'text-blue-700' },
      [SHIPPING_STATUS.OUT_FOR_DELIVERY]: { label: 'Menuju gudang', color: 'text-blue-700' },
      [SHIPPING_STATUS.ARRIVED]: { label: 'Tiba di gudang', color: 'text-blue-700' },
      [SHIPPING_STATUS.DELAYED]: { label: 'Pengiriman tertunda', color: 'text-amber-800' },
      [SHIPPING_STATUS.REPLACEMENT]: { label: 'Pengganti dikirim', color: 'text-blue-700' },
    };
    return labels[normalizeShippingStatus(shippingStatus)] || { label: shippingStatus, color: 'text-slate-600' };
  }
  if (status === 'Dikirim ke Gudang CV') return { label: 'Dalam pengiriman', color: 'text-blue-700' };
  if (status === 'Barang Pengganti Dikirim') return { label: 'Pengganti dikirim', color: 'text-blue-700' };
  return { label: status, color: 'text-slate-600' };
};

const shippingStatusOptions = computed(() => {
  return SUPPLIER_SHIPPING_STATUS_OPTIONS;
});

const openManageShippingModal = (p) => {
  activePO.value = p;
  let status = normalizeShippingStatus(p.delivery_info?.shipping_status);
  // Supplier cannot set or maintain ARRIVED ("Barang sudah sampai di gudang"); fallback to OUT_FOR_DELIVERY
  if (status === SHIPPING_STATUS.ARRIVED) {
    status = SHIPPING_STATUS.OUT_FOR_DELIVERY;
  }
  manageShippingForm.value = {
    shipping_status: status || SHIPPING_STATUS.DISPATCHED,
    location: p.delivery_info?.current_location || 'Dalam Perjalanan Menuju Gudang CV Alpharo',
    courier: p.delivery_info?.courier || 'Armada Internal Supplier',
    tracking_number: p.delivery_info?.tracking_number || '-',
    est_arrival: p.delivery_info?.est_arrival || 'Hari ini',
    notes: p.delivery_info?.notes || '',
  };
  manageShippingModalOpen.value = true;
};

const submitManageShipping = async () => {
  try {
    if (!activePO.value) return;
    await storeActions.updateSupplierShipmentStatus(activePO.value.purchase_code, manageShippingForm.value);
    toast.success(`Status pengiriman PO ${activePO.value.purchase_code} berhasil diperbarui secara real-time!`);
    manageShippingModalOpen.value = false;
  } catch (error) { toast.error(orderError(error)); }
};

const printPO = () => {
  window.print();
};
</script>

