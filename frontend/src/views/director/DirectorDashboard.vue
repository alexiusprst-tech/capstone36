<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2.5">
            <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Executive Dashboard Direktur</h1>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-alpharo-50 text-alpharo-600 border border-alpharo-200">
              Otoritas Eksekutif
            </span>
          </div>
          <p class="text-sm text-slate-500 mt-1">
            Ringkasan performa finansial, wewenang verifikasi & approval, serta pengawasan operasional lintas divisi.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link
            href="/director/verification"
            class="inline-flex items-center gap-2 px-4 py-2 bg-alpharo-600 hover:bg-alpharo-800 text-white font-semibold rounded-xl text-sm shadow-xs transition-all active:scale-95"
          >
            <FileCheck2 class="w-4 h-4 text-amber-300 stroke-[2.5]" />
            <span>Pusat Verifikasi</span>
            <span
              v-if="waitingOrders.length > 0"
              class="ml-1 px-1.5 py-0.2 rounded-full text-xs font-mono font-semibold bg-amber-400 text-slate-900 animate-pulse"
            >
              {{ waitingOrders.length }}
            </span>
            <span v-else class="ml-1 px-1.5 py-0.2 rounded-full text-xs font-mono bg-white/20 text-white">
              0
            </span>
          </Link>
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <!-- 1. PRIMARY EXECUTIVE KPIS (4 PILAR UTAMA DIREKTUR) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1: Menunggu Verifikasi (Action Item) -->
        <KpiCard
          title="Menunggu Verifikasi & Approval"
          :value="waitingOrders.length"
          unit="Order"
          :icon-component="Clock"
          icon-variant="blue"
          :subtitle="waitingOrders.length > 0 ? 'Perlu tindakan review Direktur' : 'Semua pengajuan telah ditelaah'"
          :trend="waitingOrders.length > 0 ? 'Prioritas' : 'Tuntas'"
          :trend-positive="waitingOrders.length === 0"
        />

        <!-- KPI 2: Total Nilai Proyek Pengadaan -->
        <KpiCard
          title="Total Nilai Pengadaan (Omzet)"
          :value="totalOrderValue"
          :is-currency="true"
          :icon-component="DollarSign"
          icon-variant="blue"
          :subtitle="`Akumulasi dari ${totalOrders} pesanan proyek`"
        />

        <!-- KPI 3: Realisasi Kas Masuk (Pembayaran) -->
        <KpiCard
          title="Realisasi Pembayaran Kas"
          :value="totalPaidValue"
          :is-currency="true"
          :icon-component="Wallet"
          icon-variant="blue"
          :subtitle="`${paidPercentage}% tertagih dari invoice resmi`"
          :trend="`${paidPercentage}% Terbayar`"
          :trend-positive="true"
        />

        <!-- KPI 4: Total Piutang Berjalan -->
        <KpiCard
          title="Total Piutang Berjalan"
          :value="totalReceivableValue"
          :is-currency="true"
          :icon-component="CreditCard"
          icon-variant="blue"
          :subtitle="`${unpaidInvoicesCount} invoice aktif (${overdueInvoicesCount} jatuh tempo)`"
          :trend="overdueInvoicesCount > 0 ? `${overdueInvoicesCount} Jatuh Tempo` : 'Aman'"
          :trend-positive="overdueInvoicesCount === 0"
        />
      </div>

      <!-- 2. ANALITIK EKSEKUTIF: GRAFIK PERMINTAAN & PENJUALAN (AREA SPLINE BERSIH TANPA DIAGRAM BATANG & TANPA CARD KECIL) -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- GRAFIK 1: TREN PERMINTAAN (DEMAND) -->
        <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs flex flex-col justify-between">
          <div>
            <!-- Header Grafik (Bersih & Rapi tanpa Card Kecil) -->
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
              <div class="flex items-center gap-2.5">
                <div class="flex items-center text-alpharo-600 shrink-0">
                  <TrendingUp class="w-4 h-4" />
                </div>
                <div>
                  <h3 class="font-semibold text-slate-900 text-sm">Grafik Tren Permintaan (Demand)</h3>
                  <p class="text-[11px] text-slate-500">Volume pengajuan order & kuantitas kebutuhan material</p>
                </div>
              </div>
            </div>

            <!-- Area SVG Chart Permintaan (Smooth Area Spline, Tanpa Bar) -->
            <div class="relative w-full h-48 select-none" @mouseleave="hoveredDemandIdx = null">
              <svg class="w-full h-full overflow-visible" viewBox="0 0 460 170" preserveAspectRatio="none">
                <defs>
                  <!-- Gradient for Order Count (Blue) -->
                  <linearGradient id="demandOrderGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#2563eb" stop-opacity="0.3" />
                    <stop offset="100%" stop-color="#2563eb" stop-opacity="0.0" />
                  </linearGradient>
                  <!-- Gradient for Material Volume (Emerald) -->
                  <linearGradient id="demandVolumeGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#10b981" stop-opacity="0.25" />
                    <stop offset="100%" stop-color="#10b981" stop-opacity="0.0" />
                  </linearGradient>
                </defs>

                <!-- Gridlines & Y-Axis Labels -->
                <g class="text-[9px] font-mono fill-slate-400">
                  <line x1="30" y1="20" x2="450" y2="20" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="3 3" />
                  <text x="22" y="23" text-anchor="end">10</text>

                  <line x1="30" y1="55" x2="450" y2="55" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="3 3" />
                  <text x="22" y="58" text-anchor="end">7</text>

                  <line x1="30" y1="90" x2="450" y2="90" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="3 3" />
                  <text x="22" y="93" text-anchor="end">4</text>

                  <line x1="30" y1="125" x2="450" y2="125" stroke="#f1f5f9" stroke-width="1" />
                  <text x="22" y="128" text-anchor="end">0</text>
                </g>

                <!-- Area Fills -->
                <path :d="demandVolumeAreaPath" fill="url(#demandVolumeGradient)" />
                <path :d="demandOrderAreaPath" fill="url(#demandOrderGradient)" />

                <!-- Smooth Spline Lines -->
                <path
                  :d="demandVolumeLinePath"
                  fill="none"
                  stroke="#10b981"
                  stroke-width="2.5"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <path
                  :d="demandOrderLinePath"
                  fill="none"
                  stroke="#2563eb"
                  stroke-width="2.5"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />

                <!-- Interactive Points & Vertical Guides -->
                <g v-for="(item, idx) in demandMonthlyData" :key="'demand-pt-' + idx">
                  <!-- Vertical Hover Line -->
                  <line
                    v-if="hoveredDemandIdx === idx"
                    :x1="item.x"
                    y1="20"
                    :x2="item.x"
                    y2="125"
                    stroke="#94a3b8"
                    stroke-width="1"
                    stroke-dasharray="2 2"
                  />

                  <!-- Dot for Order Count (Blue) -->
                  <circle
                    :cx="item.x"
                    :cy="item.orderY"
                    r="4"
                    :fill="hoveredDemandIdx === idx ? '#1e40af' : '#ffffff'"
                    stroke="#2563eb"
                    stroke-width="2.5"
                    class="cursor-pointer transition-all"
                    @mouseenter="hoveredDemandIdx = idx"
                  />

                  <!-- Dot for Material Volume (Emerald) -->
                  <circle
                    :cx="item.x"
                    :cy="item.volumeY"
                    r="3.5"
                    :fill="hoveredDemandIdx === idx ? '#059669' : '#ffffff'"
                    stroke="#10b981"
                    stroke-width="2"
                    class="cursor-pointer transition-all"
                    @mouseenter="hoveredDemandIdx = idx"
                  />

                  <!-- X-Axis Month Label -->
                  <text
                    :x="item.x"
                    y="145"
                    text-anchor="middle"
                    class="text-[11px] font-semibold tracking-tight"
                    :class="hoveredDemandIdx === idx ? 'fill-alpharo-600 font-semibold' : 'fill-slate-500'"
                  >
                    {{ item.month }}
                  </text>
                </g>
              </svg>

              <!-- Interactive Floating Tooltip -->
              <div
                v-if="hoveredDemandIdx !== null"
                class="absolute z-20 pointer-events-none bg-slate-900/95 backdrop-blur-xs text-white p-2.5 rounded-lg shadow-lg text-[11px] min-w-[185px] border border-slate-700 transform -translate-x-1/2 -translate-y-full transition-all duration-150"
                :style="{
                  left: `${((demandMonthlyData[hoveredDemandIdx].x) / 460) * 100}%`,
                  top: `${((demandMonthlyData[hoveredDemandIdx].orderY) / 170) * 100 - 8}%`
                }"
              >
                <div class="font-semibold text-amber-300 border-b border-slate-700 pb-1 mb-1 flex items-center justify-between">
                  <span>{{ demandMonthlyData[hoveredDemandIdx].month }} 2026</span>
                  <span class="text-[11px] text-slate-300 font-mono">{{ demandMonthlyData[hoveredDemandIdx].orders }} Order Masuk</span>
                </div>
                <div class="space-y-1 text-slate-200">
                  <div class="flex justify-between items-center">
                    <span class="text-alpharo-400 flex items-center gap-1">
                      <span class="w-1.5 h-1.5 rounded-full bg-alpharo-400"></span>
                      Volume Order:
                    </span>
                    <span class="font-mono font-semibold text-white">{{ demandMonthlyData[hoveredDemandIdx].orders }} Order</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-emerald-400 flex items-center gap-1">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                      Kebutuhan Material:
                    </span>
                    <span class="font-mono font-semibold text-white">{{ demandMonthlyData[hoveredDemandIdx].volume }} unit</span>
                  </div>
                  <div class="flex justify-between items-center pt-0.5 border-t border-slate-700/80 text-[11px]">
                    <span class="text-slate-400">Kategori Top:</span>
                    <span class="font-medium text-amber-300 truncate max-w-[100px]">{{ demandMonthlyData[hoveredDemandIdx].dominantCategory }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer Keterangan Permintaan -->
          <div class="pt-3 mt-2 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs text-slate-600 gap-2">
            <div class="flex items-center gap-3">
              <span class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-alpharo-600"></span>
                <span class="text-[11px] text-slate-600">Volume Order Masuk</span>
              </span>
              <span class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <span class="text-[11px] text-slate-600">Kuantitas Material Proyek</span>
              </span>
            </div>
            <span class="text-[11px] text-slate-500 font-medium">
              Tertinggi: <strong>Oktober ({{ totalOrders }} Order)</strong>
            </span>
          </div>
        </div>

        <!-- GRAFIK 2: REALISASI PENJUALAN & OMZET (SALES & REVENUE) -->
        <div class="ui-panel bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs flex flex-col justify-between">
          <div>
            <!-- Header Grafik (Bersih & Rapi tanpa Card Kecil) -->
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
              <div class="flex items-center gap-2.5">
                <div class="flex items-center text-emerald-600 shrink-0">
                  <BadgeDollarSign class="w-4 h-4" />
                </div>
                <div>
                  <h3 class="font-semibold text-slate-900 text-sm">Grafik Realisasi Penjualan & Arus Kas</h3>
                  <p class="text-[11px] text-slate-500">Perbandingan omzet kontrak penjualan vs penerimaan kas masuk</p>
                </div>
              </div>
            </div>

            <!-- Area SVG Chart Penjualan (Smooth Spline Area) -->
            <div class="relative w-full h-48 select-none" @mouseleave="hoveredSalesIdx = null">
              <svg class="w-full h-full overflow-visible" viewBox="0 0 460 170" preserveAspectRatio="none">
                <defs>
                  <!-- Gradient for Sales/Omzet -->
                  <linearGradient id="salesGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#10b981" stop-opacity="0.3" />
                    <stop offset="100%" stop-color="#10b981" stop-opacity="0.0" />
                  </linearGradient>
                  <!-- Gradient for Cash Inflow -->
                  <linearGradient id="cashGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#2563eb" stop-opacity="0.25" />
                    <stop offset="100%" stop-color="#2563eb" stop-opacity="0.0" />
                  </linearGradient>
                </defs>

                <!-- Gridlines & Y-Axis Labels -->
                <g class="text-[9px] font-mono fill-slate-400">
                  <line x1="42" y1="20" x2="450" y2="20" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="3 3" />
                  <text x="36" y="23" text-anchor="end">300 Jt</text>

                  <line x1="42" y1="55" x2="450" y2="55" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="3 3" />
                  <text x="36" y="58" text-anchor="end">200 Jt</text>

                  <line x1="42" y1="90" x2="450" y2="90" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="3 3" />
                  <text x="36" y="93" text-anchor="end">100 Jt</text>

                  <line x1="42" y1="125" x2="450" y2="125" stroke="#f1f5f9" stroke-width="1" />
                  <text x="36" y="128" text-anchor="end">0</text>
                </g>

                <!-- Area Fills -->
                <path :d="salesAreaPath" fill="url(#salesGradient)" />
                <path :d="cashAreaPath" fill="url(#cashGradient)" />

                <!-- Lines -->
                <path
                  :d="salesLinePath"
                  fill="none"
                  stroke="#10b981"
                  stroke-width="2.5"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <path
                  :d="cashLinePath"
                  fill="none"
                  stroke="#2563eb"
                  stroke-width="2.5"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />

                <!-- Interactive Points & Vertical Guides -->
                <g v-for="(item, idx) in salesMonthlyData" :key="'sales-pt-' + idx">
                  <!-- Vertical Hover Line -->
                  <line
                    v-if="hoveredSalesIdx === idx"
                    :x1="item.x"
                    y1="20"
                    :x2="item.x"
                    y2="125"
                    stroke="#94a3b8"
                    stroke-width="1"
                    stroke-dasharray="2 2"
                  />

                  <!-- Dot for Sales (Emerald) -->
                  <circle
                    :cx="item.x"
                    :cy="item.salesY"
                    r="4"
                    :fill="hoveredSalesIdx === idx ? '#059669' : '#ffffff'"
                    stroke="#10b981"
                    stroke-width="2.5"
                    class="cursor-pointer transition-all"
                    @mouseenter="hoveredSalesIdx = idx"
                  />

                  <!-- Dot for Cash (Blue) -->
                  <circle
                    :cx="item.x"
                    :cy="item.cashY"
                    r="3.5"
                    :fill="hoveredSalesIdx === idx ? '#1e40af' : '#ffffff'"
                    stroke="#2563eb"
                    stroke-width="2"
                    class="cursor-pointer transition-all"
                    @mouseenter="hoveredSalesIdx = idx"
                  />

                  <!-- X-Axis Labels -->
                  <text
                    :x="item.x"
                    y="145"
                    text-anchor="middle"
                    class="text-[11px] font-semibold tracking-tight"
                    :class="hoveredSalesIdx === idx ? 'fill-emerald-700 font-semibold' : 'fill-slate-500'"
                  >
                    {{ item.month }}
                  </text>
                </g>
              </svg>

              <!-- Interactive Floating Tooltip -->
              <div
                v-if="hoveredSalesIdx !== null"
                class="absolute z-20 pointer-events-none bg-slate-900/95 backdrop-blur-xs text-white p-2.5 rounded-lg shadow-lg text-[11px] min-w-[190px] border border-slate-700 transform -translate-x-1/2 -translate-y-full transition-all duration-150"
                :style="{
                  left: `${((salesMonthlyData[hoveredSalesIdx].x) / 460) * 100}%`,
                  top: `${((salesMonthlyData[hoveredSalesIdx].salesY) / 170) * 100 - 8}%`
                }"
              >
                <div class="font-semibold text-emerald-300 border-b border-slate-700 pb-1 mb-1 flex items-center justify-between">
                  <span>{{ salesMonthlyData[hoveredSalesIdx].month }} 2026</span>
                  <span class="text-[11px] text-slate-300 font-mono">{{ salesMonthlyData[hoveredSalesIdx].ratio }} Terbayar</span>
                </div>
                <div class="space-y-1 text-slate-200">
                  <div class="flex justify-between items-center">
                    <span class="text-emerald-400 flex items-center gap-1">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                      Omzet Penjualan:
                    </span>
                    <span class="font-mono font-semibold text-white">Rp {{ salesMonthlyData[hoveredSalesIdx].salesStr }}</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-alpharo-400 flex items-center gap-1">
                      <span class="w-1.5 h-1.5 rounded-full bg-alpharo-400"></span>
                      Realisasi Kas:
                    </span>
                    <span class="font-mono font-semibold text-white">Rp {{ salesMonthlyData[hoveredSalesIdx].cashStr }}</span>
                  </div>
                  <div class="flex justify-between items-center pt-0.5 border-t border-slate-700/80 text-[11px]">
                    <span class="text-slate-400">Sisa Piutang:</span>
                    <span class="font-mono font-medium text-amber-300">Rp {{ salesMonthlyData[hoveredSalesIdx].receivableStr }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer Keterangan Penjualan -->
          <div class="pt-3 mt-2 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs text-slate-600 gap-2">
            <div class="flex items-center gap-3">
              <span class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <span class="text-[11px] text-slate-600">Realisasi Omzet Penjualan</span>
              </span>
              <span class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-alpharo-600"></span>
                <span class="text-[11px] text-slate-600">Realisasi Penerimaan Kas</span>
              </span>
            </div>
            <span class="text-[11px] text-slate-500 font-medium">
              Efektivitas Kas: <strong>{{ paidPercentage }}%</strong>
            </span>
          </div>
        </div>
      </div>

      <!-- 3. MAIN WORKSPACE: BALANCED 2-COLUMN LAYOUT -->
      <div class="dashboard-workspace min-w-0 flex flex-col gap-4 sm:gap-5">
      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs p-5">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
              <div>
                <h3 class="font-semibold text-slate-900 text-sm">Monitoring Pengawasan 3 Divisi Operasional</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Kontrol status kesiapan stok gudang, legalitas administrasi, dan penagihan piutang.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 divide-y divide-slate-100 sm:divide-y-0 sm:divide-x">
              <!-- B1. DIVISI GUDANG (WAREHOUSE) -->
              <div class="py-3 sm:py-0 sm:px-5 first:pt-0 last:pb-0 sm:first:pl-0 sm:last:pr-0">
                <div>
                  <div class="flex items-center justify-between pb-2 mb-2.5 border-b border-slate-200/60">
                    <span class="font-semibold text-xs uppercase text-slate-800 tracking-wider flex items-center gap-1.5">
                      <Package class="w-3.5 h-3.5 text-alpharo-600" />
                      Gudang
                    </span>
                    <Link href="/director/warehouse" class="text-[11px] text-alpharo-600 font-semibold hover:underline">
                      Detail →
                    </Link>
                  </div>
                  <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                      <span class="text-slate-500 text-[11px]">Antrean Alokasi:</span>
                      <span class="font-semibold text-slate-800">{{ store.warehouseFulfillments.length }} Order</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-500 text-[11px]">Pengiriman:</span>
                      <span class="font-semibold text-alpharo-600">{{ activeShipmentsCount }} Armada</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-500 text-[11px]">Stok Menipis:</span>
                      <span :class="lowStockCount > 0 ? 'text-rose-600 font-semibold' : 'text-slate-600 font-medium'">
                        {{ lowStockCount }} Item
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- B2. DIVISI ADMINISTRASI -->
              <div class="py-3 sm:py-0 sm:px-5 first:pt-0 last:pb-0 sm:first:pl-0 sm:last:pr-0">
                <div>
                  <div class="flex items-center justify-between pb-2 mb-2.5 border-b border-slate-200/60">
                    <span class="font-semibold text-xs uppercase text-slate-800 tracking-wider flex items-center gap-1.5">
                      <FileCheck2 class="w-3.5 h-3.5 text-alpharo-600" />
                      Administrasi
                    </span>
                    <Link href="/director/administration" class="text-[11px] text-alpharo-600 font-semibold hover:underline">
                      Detail →
                    </Link>
                  </div>
                  <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                      <span class="text-slate-500 text-[11px]">Berkas Lengkap:</span>
                      <span class="font-semibold text-emerald-600">{{ completeDocsOrders }} Order</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-500 text-[11px]">Belum Lengkap:</span>
                      <span class="font-semibold text-amber-600">{{ incompleteDocsOrders }} Order</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-500 text-[11px]">PO Diterbitkan:</span>
                      <span class="font-semibold text-slate-800">{{ poCount }} Berkas</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- B3. DIVISI FINANCE -->
              <div class="py-3 sm:py-0 sm:px-5 first:pt-0 last:pb-0 sm:first:pl-0 sm:last:pr-0">
                <div>
                  <div class="flex items-center justify-between pb-2 mb-2.5 border-b border-slate-200/60">
                    <span class="font-semibold text-xs uppercase text-slate-800 tracking-wider flex items-center gap-1.5">
                      <CreditCard class="w-3.5 h-3.5 text-emerald-600" />
                      Finance
                    </span>
                    <Link href="/director/finance" class="text-[11px] text-alpharo-600 font-semibold hover:underline">
                      Detail →
                    </Link>
                  </div>
                  <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                      <span class="text-slate-500 text-[11px]">Jatuh Tempo:</span>
                      <span :class="overdueInvoicesCount > 0 ? 'text-rose-600 font-semibold' : 'text-slate-600 font-medium'">
                        {{ overdueInvoicesCount }} Berkas
                      </span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-500 text-[11px]">Belum Lunas:</span>
                      <span class="font-semibold text-amber-600">{{ partialInvoicesCount }} Berkas</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-500 text-[11px]">Invoice Lunas:</span>
                      <span class="font-semibold text-emerald-600">{{ paidInvoicesCount }} Berkas</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
              <div class="flex items-center gap-2.5">
                <span
                  class="w-2.5 h-2.5 rounded-full"
                  :class="waitingOrders.length > 0 ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500'"
                ></span>
                <h3 class="font-semibold text-slate-900 text-sm">Antrean Verifikasi & Persetujuan Order</h3>
                <span
                  class="text-[11px] font-semibold px-2 py-0.5 rounded-full"
                  :class="waitingOrders.length > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'"
                >
                  {{ waitingOrders.length }} Order
                </span>
              </div>
              <Link
                href="/director/verification"
                class="text-xs text-alpharo-600 font-semibold hover:underline inline-flex items-center gap-1"
              >
                <span>Buka Detail</span>
                <ChevronRight class="w-3.5 h-3.5" />
              </Link>
            </div>

            <!-- Table Jika Ada Antrean -->
            <div v-if="waitingOrders.length > 0" class="overflow-x-auto xl:max-h-[26rem] xl:overflow-y-auto overscroll-auto">
              <table class="ui-table w-full text-left text-xs">
                <thead class="bg-slate-50/90 text-slate-600 font-semibold uppercase text-[11px] border-b border-slate-200">
                  <tr>
                    <th class="py-2.5 px-4">No. Order</th>
                    <th class="py-2.5 px-4">Client & Proyek</th>
                    <th class="py-2.5 px-3 text-right">Nilai Proyek</th>
                    <th class="py-2.5 px-4 text-center whitespace-nowrap">Aksi Cepat</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr
                    v-for="order in waitingOrders"
                    :key="order.id"
                    class="hover:bg-amber-50/30 transition-colors"
                  >
                    <td class="py-3 px-4 font-mono font-semibold text-slate-900 whitespace-nowrap">
                      {{ order.orderNumber }}
                    </td>
                    <td class="py-3 px-4 min-w-[180px]">
                      <div class="font-semibold text-slate-900">{{ order.clientName }}</div>
                      <div class="text-[11px] text-slate-500 line-clamp-1">{{ order.projectName }}</div>
                    </td>
                    <td class="py-3 px-3 text-right font-mono font-semibold text-slate-900 whitespace-nowrap">
                      Rp {{ Number(order.grandTotal || 0).toLocaleString('id-ID') }}
                    </td>
                    <td class="py-3 px-4 text-center whitespace-nowrap">
                      <div class="flex items-center justify-center gap-1.5">
                        <button
                          type="button"
                          class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold text-[11px] shadow-2xs transition-all cursor-pointer"
                          title="Setujui order langsung"
                          :disabled="orderSync.busy" @click="handleQuickApprove(order)"
                        >
                          Setujui
                        </button>
                        
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Sleek Compact Empty State -->
            <div v-else class="p-6 text-center">
              <div class="flex flex-col sm:flex-row items-center justify-center gap-3 text-slate-600">
                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                  <CheckCircle2 class="w-5 h-5 stroke-[2.5]" />
                </div>
                <div class="text-center sm:text-left">
                  <p class="text-xs font-semibold text-slate-800">Semua Pengajuan Order Telah Diverifikasi</p>
                  <p class="text-[11px] text-slate-500">Tidak ada order yang tertunda. Seluruh proses berjalan lancar pada divisi terkait.</p>
                </div>
              </div>
            </div>
          </div>

      <div class="ui-panel bg-white rounded-xl border border-slate-200/90 shadow-2xs p-5">
            <div class="flex items-center justify-between mb-3 pb-2.5 border-b border-slate-100">
              <div class="flex items-center gap-2">
                <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                <h3 class="font-semibold text-slate-900 text-sm">Riwayat Keputusan Approval</h3>
              </div>
              <span class="text-[11px] text-slate-400">Log Direktur</span>
            </div>

            <div class="divide-y divide-slate-100">
              <div
                v-for="app in approvedOrRejectedOrders.slice(0, 3)"
                :key="app.id"
                class="py-4 text-xs"
              >
                <div class="flex items-center justify-between">
                  <span class="font-mono font-semibold text-slate-900">{{ app.orderNumber }}</span>
                  <StatusBadge plain :status="app.status" />
                </div>
                <p class="font-medium text-slate-800 mt-1 whitespace-normal [overflow-wrap:anywhere]">{{ app.clientName }}</p>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed whitespace-normal [overflow-wrap:anywhere]">
                  "{{ app.approvalNotes || 'Persetujuan eksekutif Direktur' }}"
                </p>
                <div class="flex items-center justify-between text-[11px] text-slate-400 mt-1.5 pt-1 border-t border-slate-100">
                  <span>{{ app.approvalDate }}</span>
                  <span class="font-mono font-semibold text-slate-700">Rp {{ Number(app.grandTotal || 0).toLocaleString('id-ID') }}</span>
                </div>
              </div>
            </div>
          </div>

      <ActivityLogCard class="dashboard-activity" />
    </div>
    </div>

    
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import KpiCard from '../../components/common/KpiCard.vue';
import ActivityLogCard from '../../components/common/ActivityLogCard.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import { store, storeActions, orderSync } from '../../stores/mockData';
import { orderError } from '../../services/orderWorkflow';
import { useToast } from '../../services/toast';

import {
  Clock,
  CheckCircle2,
  Package,
  DollarSign,
  Wallet,
  CreditCard,
  ChevronRight,
  FileCheck2,
  TrendingUp,
  BadgeDollarSign,
} from 'lucide-vue-next';

const toast = useToast();


// Analytics Hover States
const hoveredDemandIdx = ref(null);
const hoveredSalesIdx = ref(null);

const totalOrders = computed(() => store.orders.length);
const waitingOrders = computed(() => store.orders.filter((o) => o.serverManaged && o.status === 'Menunggu Verifikasi'));

const totalOrderValue = computed(() => store.orders.reduce((sum, o) => sum + (Number(o.grandTotal) || 0), 0));
const unpaidInvoicesCount = computed(() => store.invoices.filter((i) => i.status !== 'Lunas').length);
const totalPaidValue = computed(() => store.invoices.reduce((sum, i) => sum + (Number(i.paidAmount) || 0), 0));
const totalReceivableValue = computed(() => store.invoices.reduce((sum, i) => sum + (Number(i.remainingAmount) || 0), 0));

const paidPercentage = computed(() => {
  const total = totalOrderValue.value || 1;
  return Math.min(100, Math.round(((totalPaidValue.value || 0) / total) * 100));
});

const lowStockCount = computed(() => store.products.filter((p) => p.stock <= p.minStock).length);
const activeShipmentsCount = computed(() => store.shipments.filter((s) => s.status === 'Sedang Dikirim').length);

const completeDocsOrders = computed(() => store.orders.filter((o) => o.documents && Object.values(o.documents).every((d) => d.status === 'Lengkap')).length);
const incompleteDocsOrders = computed(() => store.orders.length - completeDocsOrders.value);
const poCount = computed(() => store.orders.filter((o) => o.documents?.po?.exists).length);

const overdueInvoicesCount = computed(() => store.invoices.filter((i) => i.status === 'Jatuh Tempo').length);
const partialInvoicesCount = computed(() => store.invoices.filter((i) => i.status === 'Sebagian' || i.status === 'Sebagian / Termin').length);
const paidInvoicesCount = computed(() => store.invoices.filter((i) => i.status === 'Lunas').length);

const approvedOrRejectedOrders = computed(() => store.orders.filter((o) => o.approvalDate));

// Helper for cubic spline
function buildSpline(pts) {
  if (!pts || !pts.length) return '';
  let d = `M ${pts[0].x} ${pts[0].y.toFixed(1)}`;
  for (let i = 0; i < pts.length - 1; i++) {
    const p0 = i > 0 ? pts[i - 1] : pts[i];
    const p1 = pts[i];
    const p2 = pts[i + 1];
    const p3 = i + 2 < pts.length ? pts[i + 2] : p2;
    const cp1x = p1.x + (p2.x - p0.x) / 5;
    const cp1y = p1.y + (p2.y - p0.y) / 5;
    const cp2x = p2.x - (p3.x - p1.x) / 5;
    const cp2y = p2.y - (p3.y - p1.y) / 5;
    d += ` C ${cp1x.toFixed(1)} ${cp1y.toFixed(1)}, ${cp2x.toFixed(1)} ${cp2y.toFixed(1)}, ${p2.x} ${p2.y.toFixed(1)}`;
  }
  return d;
}

// --- GRAFIK PERMINTAAN DATA (DEMAND) ---
// 6 Bulan: Mei, Jun, Jul, Ags, Sep, Okt (terhubung dinamis dengan data store)
const demandMonthlyData = computed(() => {
  const currentOrdersCount = totalOrders.value || 8;
  const currentItemsCount = store.orders.reduce((sum, o) => {
    return sum + (o.items ? o.items.reduce((s, it) => s + (Number(it.qty) || 0), 0) : 0);
  }, 0) || 3150;

  // Koordinat X: 65, 135, 205, 275, 345, 415 di viewBox 460
  // Y baseline = 125, Peak Y = 20 (height = 105 px)
  // Max scale Order: 10 order
  // Max scale Volume: 3500 unit
  const months = [
    { month: 'Mei', orders: 4, rawVolume: 1450, volume: '1.450', dominantCategory: 'Besi Beton Ulir', x: 65 },
    { month: 'Jun', orders: 6, rawVolume: 1980, volume: '1.980', dominantCategory: 'Semen Portland', x: 135 },
    { month: 'Jul', orders: 5, rawVolume: 1620, volume: '1.620', dominantCategory: 'Baja Ringan C75', x: 205 },
    { month: 'Ags', orders: 7, rawVolume: 2400, volume: '2.400', dominantCategory: 'Spandek Pasir', x: 275 },
    { month: 'Sep', orders: 8, rawVolume: 2850, volume: '2.850', dominantCategory: 'Besi Beton & Wiremesh', x: 345 },
    {
      month: 'Okt',
      orders: currentOrdersCount,
      rawVolume: currentItemsCount,
      volume: currentItemsCount.toLocaleString('id-ID'),
      dominantCategory: 'Material Proyek Vokasi',
      x: 415,
    },
  ];

  return months.map(d => ({
    ...d,
    orderY: 125 - (Math.min(10, d.orders) / 10) * 105,
    volumeY: 125 - (Math.min(3500, d.rawVolume) / 3500) * 105,
  }));
});

const demandOrderLinePath = computed(() => {
  const pts = demandMonthlyData.value.map(d => ({ x: d.x, y: d.orderY }));
  return buildSpline(pts);
});

const demandOrderAreaPath = computed(() => {
  const pts = demandMonthlyData.value.map(d => ({ x: d.x, y: d.orderY }));
  if (!pts.length) return '';
  const line = buildSpline(pts);
  return `${line} L ${pts[pts.length - 1].x} 125 L ${pts[0].x} 125 Z`;
});

const demandVolumeLinePath = computed(() => {
  const pts = demandMonthlyData.value.map(d => ({ x: d.x, y: d.volumeY }));
  return buildSpline(pts);
});

const demandVolumeAreaPath = computed(() => {
  const pts = demandMonthlyData.value.map(d => ({ x: d.x, y: d.volumeY }));
  if (!pts.length) return '';
  const line = buildSpline(pts);
  return `${line} L ${pts[pts.length - 1].x} 125 L ${pts[0].x} 125 Z`;
});

// --- GRAFIK PENJUALAN DATA (SALES & CASH) ---
// Max Y scale = 320 Jt, Baseline Y = 125, Peak Y = 20 (height = 105 px)
const salesMonthlyData = computed(() => {
  const currentSalesJt = (totalOrderValue.value || 292272600) / 1000000;
  const currentCashJt = (totalPaidValue.value || 153490000) / 1000000;
  const currentRecJt = (totalReceivableValue.value || 138782600) / 1000000;

  const data = [
    { month: 'Mei', sales: 85, cash: 82, salesStr: '85.000.000', cashStr: '82.000.000', receivableStr: '3.000.000', ratio: '96.5%', x: 65 },
    { month: 'Jun', sales: 125, cash: 110, salesStr: '125.000.000', cashStr: '110.000.000', receivableStr: '15.000.000', ratio: '88.0%', x: 135 },
    { month: 'Jul', sales: 110, cash: 95, salesStr: '110.000.000', cashStr: '95.000.000', receivableStr: '15.000.000', ratio: '86.4%', x: 205 },
    { month: 'Ags', sales: 175, cash: 140, salesStr: '175.000.000', cashStr: '140.000.000', receivableStr: '35.000.000', ratio: '80.0%', x: 275 },
    { month: 'Sep', sales: 210, cash: 165, salesStr: '210.000.000', cashStr: '165.000.000', receivableStr: '45.000.000', ratio: '78.6%', x: 345 },
    {
      month: 'Okt',
      sales: currentSalesJt,
      cash: currentCashJt,
      salesStr: (totalOrderValue.value || 0).toLocaleString('id-ID'),
      cashStr: (totalPaidValue.value || 0).toLocaleString('id-ID'),
      receivableStr: (totalReceivableValue.value || 0).toLocaleString('id-ID'),
      ratio: `${paidPercentage.value}%`,
      x: 415,
    },
  ];

  return data.map(d => ({
    ...d,
    salesY: 125 - Math.min(320, d.sales) / 320 * 105,
    cashY: 125 - Math.min(320, d.cash) / 320 * 105,
  }));
});

const salesLinePath = computed(() => {
  const pts = salesMonthlyData.value.map(d => ({ x: d.x, y: d.salesY }));
  return buildSpline(pts);
});

const salesAreaPath = computed(() => {
  const pts = salesMonthlyData.value.map(d => ({ x: d.x, y: d.salesY }));
  if (!pts.length) return '';
  const line = buildSpline(pts);
  return `${line} L ${pts[pts.length - 1].x} 125 L ${pts[0].x} 125 Z`;
});

const cashLinePath = computed(() => {
  const pts = salesMonthlyData.value.map(d => ({ x: d.x, y: d.cashY }));
  return buildSpline(pts);
});

const cashAreaPath = computed(() => {
  const pts = salesMonthlyData.value.map(d => ({ x: d.x, y: d.cashY }));
  if (!pts.length) return '';
  const line = buildSpline(pts);
  return `${line} L ${pts[pts.length - 1].x} 125 L ${pts[0].x} 125 Z`;
});

const handleQuickApprove = async (order) => {
  if (orderSync.busy) return;
  try {
    await storeActions.approveOrder(order.id, 'Disetujui oleh Direktur via Dashboard.');
    toast.success(`Pesanan ${order.orderNumber} siap diperiksa Finance.`);
  } catch (error) { toast.error(orderError(error)); }
};


</script>
