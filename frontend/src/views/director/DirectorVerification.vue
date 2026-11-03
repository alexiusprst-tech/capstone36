<template>
  <AppLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Verifikasi & Approval Order</h1>
          <p class="text-sm text-slate-500 mt-1">
            Wewenang eksekutif Direktur untuk memeriksa kelaikan margin, ketersediaan stok, dan menerbitkan persetujuan resmi.
          </p>
        </div>
      </div>
    </template>

    <!-- TABS NAVIGATION -->
    <div class="flex items-center gap-2 border-b border-slate-200 mb-6 pb-2">
      <button
        type="button"
        :class="[
          'px-4 py-2 text-xs font-bold rounded-lg transition-colors flex items-center gap-2 cursor-pointer',
          activeTab === 'pending'
            ? 'bg-alpharo-500 text-white shadow-xs'
            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
        ]"
        @click="activeTab = 'pending'"
      >
        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
        <span>Antrean Menunggu Telaah</span>
        <span
          :class="[
            'px-1.5 py-0.5 rounded-full text-[10px]',
            activeTab === 'pending' ? 'bg-alpharo-600 text-white' : 'bg-slate-200 text-slate-700'
          ]"
        >
          {{ pendingOrders.length }}
        </span>
      </button>

      <button
        type="button"
        :class="[
          'px-4 py-2 text-xs font-bold rounded-lg transition-colors flex items-center gap-2 cursor-pointer',
          activeTab === 'hold'
            ? 'bg-alpharo-500 text-white shadow-xs'
            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
        ]"
        @click="activeTab = 'hold'"
      >
        <PauseCircle class="w-3.5 h-3.5" />
        <span>Order Ditahan / Revisi</span>
        <span
          :class="[
            'px-1.5 py-0.5 rounded-full text-[10px]',
            activeTab === 'hold' ? 'bg-alpharo-600 text-white' : 'bg-slate-200 text-slate-700'
          ]"
        >
          {{ heldOrders.length }}
        </span>
      </button>

      <button
        type="button"
        :class="[
          'px-4 py-2 text-xs font-bold rounded-lg transition-colors flex items-center gap-2 cursor-pointer',
          activeTab === 'history'
            ? 'bg-alpharo-500 text-white shadow-xs'
            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
        ]"
        @click="activeTab = 'history'"
      >
        <Clock class="w-3.5 h-3.5" />
        <span>Riwayat Keputusan</span>
        <span
          :class="[
            'px-1.5 py-0.5 rounded-full text-[10px]',
            activeTab === 'history' ? 'bg-alpharo-600 text-white' : 'bg-slate-200 text-slate-700'
          ]"
        >
          {{ pastDecisions.length }}
        </span>
      </button>
    </div>

    <!-- TAB 1: PENDING APPROVAL TABLE -->
    <div v-if="activeTab === 'pending'" class="space-y-6">
      <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
              Antrean Menunggu Telaah Direktur ({{ pendingOrders.length }} Order)
            </h3>
          </div>
          <span class="text-xs text-slate-500">
            Klik tombol <strong class="text-alpharo-600 font-semibold">"Telaah & Putuskan"</strong> untuk membuka dialog telaah eksekutif
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
                <th class="py-3 px-4">Order Number</th>
                <th class="py-3 px-4">Client</th>
                <th class="py-3 px-4">Date</th>
                <th class="py-3 px-4 text-right">Total Nilai</th>
                <th class="py-3 px-4">Submitted By</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-right">Aksi Eksekutif</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="order in pendingOrders"
                :key="order.id"
                :class="[
                  selectedOrder?.id === order.id ? 'bg-blue-50/50' : 'hover:bg-slate-50/80',
                  'transition-colors cursor-pointer'
                ]"
                @click="selectForVerification(order)"
              >
                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                  <div class="flex items-center gap-2">
                    <span v-if="selectedOrder?.id === order.id" class="w-1.5 h-1.5 rounded-full bg-alpharo-500"></span>
                    <span>{{ order.orderNumber }}</span>
                  </div>
                </td>
                <td class="py-3.5 px-4">
                  <span class="font-bold text-slate-900 block">{{ order.clientName }}</span>
                  <span class="text-[11px] text-slate-400 block truncate max-w-xs">{{ order.projectName || 'Pengadaan Barang & Jasa' }}</span>
                </td>
                <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">{{ order.date }}</td>
                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                  Rp {{ Number(order.grandTotal || 0).toLocaleString('id-ID') }}
                </td>
                <td class="py-3.5 px-4 text-slate-600">
                  <span class="font-medium text-slate-700 block">{{ order.submittedBy }}</span>
                  <span v-if="order.urgency" class="text-[10px] text-amber-600 font-semibold uppercase tracking-wider block">
                    Urgensi: {{ order.urgency }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <StatusBadge :status="order.status" />
                </td>
                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-alpharo-500 hover:bg-alpharo-600 active:bg-alpharo-700 text-white rounded-lg font-bold text-xs shadow-xs transition-all cursor-pointer transform hover:-translate-y-0.5"
                    title="Buka panel telaah kelaikan margin dan putuskan order"
                    @click.stop="openVerificationModal(order)"
                  >
                    <Eye class="w-3.5 h-3.5" />
                    <span>Telaah & Putuskan</span>
                  </button>
                </td>
              </tr>

              <tr v-if="pendingOrders.length === 0">
                <td colspan="7" class="py-12 text-center text-slate-400">
                  <div class="flex flex-col items-center justify-center gap-2">
                    <CheckCircle2 class="w-8 h-8 text-emerald-500" />
                    <p class="font-medium text-slate-600">Semua antrean order telah diverifikasi Direktur.</p>
                    <p class="text-xs text-slate-400">Tidak ada pengajuan yang membutuhkan keputusan saat ini.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- INLINE DETAIL VERIFICATION PANEL (3 COLUMNS: LEFT, CENTER, RIGHT + BOTTOM ACTION) -->
      <div
        v-if="selectedOrder"
        ref="verificationPanelRef"
        class="bg-white rounded-xl border border-slate-200/90 shadow-sm p-6 space-y-6 animate-in fade-in duration-200"
      >
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-200">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-semibold text-alpharo-500 uppercase tracking-wider">Panel Verifikasi Direktur</span>
              <span class="text-slate-300">&bull;</span>
              <span class="text-xs text-slate-500">Wewenang Eksekutif</span>
            </div>
            <h2 class="text-lg font-bold text-slate-900 mt-0.5">
              Telaah Wewenang: {{ selectedOrder.orderNumber }} &bull; {{ selectedOrder.clientName }}
            </h2>
          </div>

          <div class="flex items-center gap-2">
            <StatusBadge :status="selectedOrder.status" />
            <button
              type="button"
              class="px-3 py-1.5 text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors flex items-center gap-1.5 cursor-pointer"
              @click="openVerificationModal(selectedOrder)"
            >
              <Maximize2 class="w-3.5 h-3.5 text-slate-600" />
              <span>Buka Pop-up Layar Penuh</span>
            </button>
          </div>
        </div>

        <!-- 3 COLUMNS GRID -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 text-xs">
          <!-- LEFT COLUMN (4 COLS): ORDER INFORMATION & CLIENT -->
          <div class="lg:col-span-4 bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
            <h4 class="font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2 pb-2 border-b border-slate-200">
              <Building class="w-4 h-4 text-alpharo-500" />
              <span>1. Informasi Order & Rekanan Klien</span>
            </h4>

            <div class="space-y-3">
              <div>
                <span class="text-slate-400 text-[11px] block">Nama Rekanan Client</span>
                <span class="font-bold text-slate-900 text-sm block">{{ selectedOrder.clientName }}</span>
              </div>
              <div>
                <span class="text-slate-400 text-[11px] block">Nama Kegiatan / Proyek</span>
                <span class="font-medium text-slate-800 block">{{ selectedOrder.projectName || 'Pengadaan Barang & Jasa' }}</span>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <span class="text-slate-400 text-[11px] block">Tanggal Pengajuan</span>
                  <span class="font-mono text-slate-700 font-medium">{{ selectedOrder.date }}</span>
                </div>
                <div>
                  <span class="text-slate-400 text-[11px] block">Target Pengiriman</span>
                  <span class="font-mono text-slate-700 font-medium">{{ selectedOrder.targetDeliveryDate || 'Reguler (Sesuai PO)' }}</span>
                </div>
              </div>
              <div>
                <span class="text-slate-400 text-[11px] block">Diajukan Oleh</span>
                <span class="text-slate-700 font-medium">{{ selectedOrder.submittedBy }}</span>
              </div>
              <div>
                <span class="text-slate-400 text-[11px] block">Alamat Pengiriman Proyek</span>
                <p class="text-slate-700 bg-white p-2 rounded border border-slate-200 mt-1">
                  {{ selectedOrder.address || 'Alamat terdaftar pada master instansi client.' }}
                </p>
              </div>
              <div>
                <span class="text-slate-400 text-[11px] block">Catatan Tambahan Pengajuan</span>
                <p class="text-slate-600 bg-white p-2.5 rounded border border-slate-200 mt-1 italic text-[11px]">
                  "{{ selectedOrder.notes || 'Tidak ada catatan tambahan dari pemohon.' }}"
                </p>
              </div>
            </div>
          </div>

          <!-- CENTER COLUMN (5 COLS): ITEMS & SERVICES + LIVE STOCK CHECK -->
          <div class="lg:col-span-5 bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
              <h4 class="font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                <Box class="w-4 h-4 text-alpharo-500" />
                <span>2. Rincian Barang & Cek Stok Gudang</span>
              </h4>
              <span class="text-[11px] text-slate-500">{{ (selectedOrder.items || []).length }} Item</span>
            </div>

            <div class="border border-slate-200 rounded-lg overflow-hidden bg-white shadow-2xs">
              <table class="w-full text-left text-xs">
                <thead class="bg-slate-100/70 border-b border-slate-200 text-slate-600 font-semibold">
                  <tr>
                    <th class="p-2.5">Item Barang / Jasa</th>
                    <th class="p-2.5 text-center">Qty</th>
                    <th class="p-2.5 text-center">Kesiapan Stok</th>
                    <th class="p-2.5 text-right">Harga Jual</th>
                    <th class="p-2.5 text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="(it, idx) in (selectedOrder.items || [])" :key="it.id || it.sku + '-' + idx">
                    <td class="p-2.5">
                      <span class="font-medium text-slate-800 block">{{ it.name }}</span>
                      <span class="font-mono text-[10px] text-slate-400">{{ it.sku || 'SKU-CUSTOM' }}</span>
                    </td>
                    <td class="p-2.5 text-center whitespace-nowrap font-medium">
                      {{ it.qty }} {{ it.unit || 'Unit' }}
                    </td>
                    <td class="p-2.5 text-center whitespace-nowrap">
                      <span
                        v-if="getStockInfo(it).found"
                        :class="[
                          'px-2 py-0.5 rounded-full text-[10px] font-bold border inline-block',
                          getStockInfo(it).isReady
                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                            : 'bg-rose-50 text-rose-700 border-rose-200'
                        ]"
                      >
                        {{ getStockInfo(it).isReady ? `Ready (${getStockInfo(it).stock})` : `Kurang (${getStockInfo(it).stock})` }}
                      </span>
                      <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200 inline-block">
                        Custom / Jasa
                      </span>
                    </td>
                    <td class="p-2.5 text-right whitespace-nowrap text-slate-600">
                      Rp {{ Number(it.price || 0).toLocaleString('id-ID') }}
                    </td>
                    <td class="p-2.5 text-right font-bold whitespace-nowrap text-slate-900">
                      Rp {{ Number(it.total || ((it.qty || 0) * (it.price || 0))).toLocaleString('id-ID') }}
                    </td>
                  </tr>

                  <tr v-if="!selectedOrder.items || selectedOrder.items.length === 0">
                    <td colspan="5" class="p-4 text-center text-slate-400 italic">
                      Tidak ada rincian item barang pada order ini.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- STOCK SUMMARY ALERT -->
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-[11px] text-slate-700 flex items-start gap-2">
              <ShieldAlert class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
              <div class="leading-relaxed">
                <strong>Ketentuan Alur Pemenuhan:</strong> Jika stok gudang tidak mencukupi, persetujuan Direktur akan menerbitkan izin bagi Warehouse & Administrasi untuk menerbitkan Purchase Order (PO) pengadaan ke Supplier rekanan.
              </div>
            </div>
          </div>

          <!-- RIGHT COLUMN (3 COLS): SUMMARY & MARGIN FEASIBILITY -->
          <div class="lg:col-span-3 bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4 flex flex-col justify-between">
            <div>
              <h4 class="font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2 pb-2 border-b border-slate-200 mb-3">
                <DollarSign class="w-4 h-4 text-alpharo-500" />
                <span>3. Kelaikan Margin & Nilai</span>
              </h4>

              <div class="space-y-2 bg-white p-3.5 rounded-lg border border-slate-200 shadow-2xs">
                <div class="flex justify-between text-slate-600">
                  <span>Subtotal Penjualan:</span>
                  <span class="font-mono font-medium">Rp {{ Number(computedFinancials.subtotal).toLocaleString('id-ID') }}</span>
                </div>
                <div class="flex justify-between text-slate-500 text-[11px]">
                  <span>Estimasi HPP (Modal):</span>
                  <span class="font-mono">Rp {{ Number(computedFinancials.hpp).toLocaleString('id-ID') }}</span>
                </div>
                <div class="pt-2 border-t border-dashed border-slate-200 flex justify-between items-center">
                  <span class="font-semibold text-slate-700">Gross Margin:</span>
                  <span class="font-mono font-bold text-emerald-600">
                    Rp {{ Number(computedFinancials.grossMarginRp).toLocaleString('id-ID') }}
                    <span class="text-xs">({{ computedFinancials.grossMarginPct }}%)</span>
                  </span>
                </div>

                <!-- FEASIBILITY BADGE -->
                <div class="pt-2">
                  <div
                    :class="[
                      'p-2 rounded text-center font-bold text-xs border flex items-center justify-center gap-1.5',
                      computedFinancials.grossMarginPct >= 20
                        ? 'bg-emerald-50 text-emerald-800 border-emerald-300'
                        : computedFinancials.grossMarginPct >= 10
                        ? 'bg-amber-50 text-amber-800 border-amber-300'
                        : 'bg-rose-50 text-rose-800 border-rose-300'
                    ]"
                  >
                    <CheckCircle2 v-if="computedFinancials.grossMarginPct >= 20" class="w-3.5 h-3.5 text-emerald-600" />
                    <AlertTriangle v-else class="w-3.5 h-3.5 text-amber-600" />
                    <span>{{ computedFinancials.marginFeasibility }}</span>
                  </div>
                </div>

                <div class="pt-2 border-t border-slate-200 flex justify-between text-slate-600">
                  <span>PPN 11%:</span>
                  <span class="font-mono">Rp {{ Number(computedFinancials.tax).toLocaleString('id-ID') }}</span>
                </div>
                <div class="pt-2 border-t border-slate-200 flex justify-between font-bold text-sm text-slate-900">
                  <span>Grand Total:</span>
                  <span class="font-mono text-alpharo-600">Rp {{ Number(computedFinancials.grandTotal).toLocaleString('id-ID') }}</span>
                </div>
              </div>
            </div>

            <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-lg text-[11px] text-amber-800">
              <strong>Instruksi Eksekutif:</strong> Keputusan Anda mengikat seluruh divisi operasional. Pastikan rincian harga dan spesifikasi telah sesuai kesepakatan penawaran (SPH).
            </div>
          </div>
        </div>

        <!-- BOTTOM: APPROVAL ACTION -->
        <div class="pt-4 border-t border-slate-200 bg-slate-50/50 -mx-6 -mb-6 p-6 rounded-b-xl flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="w-full sm:w-auto flex-1 max-w-lg">
            <label class="block text-xs font-bold text-slate-700 mb-1">
              Catatan Verifikasi Direktur (Terekam dalam Audit Trail):
            </label>
            <input
              v-model="directorNotes"
              type="text"
              placeholder="Masukkan instruksi khusus (misal: 'Disetujui, utamakan pengiriman minggu ini')..."
              class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-alpharo-500 focus:outline-none"
            />
          </div>

          <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
            <!-- REJECT BUTTON -->
            <button
              type="button"
              class="px-4 py-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white font-bold text-xs rounded-lg shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer"
              @click="openRejectModal(selectedOrder)"
            >
              <AlertCircle class="w-4 h-4" />
              <span>Tolak Order (Reject)</span>
            </button>

            <!-- HOLD BUTTON -->
            <button
              type="button"
              class="px-4 py-2 bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-bold text-xs rounded-lg shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer"
              @click="openHoldModal(selectedOrder)"
            >
              <PauseCircle class="w-4 h-4" />
              <span>Tahan / Minta Revisi (Hold)</span>
            </button>

            <!-- APPROVE BUTTON -->
            <button
              type="button"
              class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-xs rounded-lg shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer"
              @click="executeApprove(selectedOrder)"
            >
              <CheckCircle2 class="w-4 h-4" />
              <span>Setujui Order (Approve)</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: HELD / REVISION ORDERS TABLE -->
    <div v-else-if="activeTab === 'hold'" class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <PauseCircle class="w-4 h-4 text-amber-500" />
          <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
            Daftar Order Yang Sedang Ditahan / Memerlukan Revisi ({{ heldOrders.length }} Order)
          </h3>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
              <th class="py-3 px-4">No. Order</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">Tanggal Ditahan</th>
              <th class="py-3 px-4 text-right">Nilai Total</th>
              <th class="py-3 px-4">Alasan Penahanan</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="ord in heldOrders" :key="ord.id" class="hover:bg-slate-50/80">
              <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ ord.orderNumber }}</td>
              <td class="py-3 px-4">
                <span class="font-bold text-slate-900 block">{{ ord.clientName }}</span>
                <span class="text-[11px] text-slate-400 block truncate max-w-xs">{{ ord.projectName }}</span>
              </td>
              <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ ord.holdDate || ord.date }}</td>
              <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                Rp {{ Number(ord.grandTotal || 0).toLocaleString('id-ID') }}
              </td>
              <td class="py-3 px-4 text-slate-700 italic">
                "{{ ord.holdReason || 'Ditahan oleh Direktur untuk verifikasi kepastian pembayaran.' }}"
              </td>
              <td class="py-3 px-4 text-center">
                <StatusBadge :status="ord.status || 'Ditahan'" />
              </td>
              <td class="py-3 px-4 text-right whitespace-nowrap space-x-2">
                <button
                  type="button"
                  class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold text-xs shadow-2xs transition-colors cursor-pointer"
                  @click="executeReleaseHold(ord)"
                >
                  Lepas Tahanan
                </button>
                <button
                  type="button"
                  class="px-3 py-1 bg-alpharo-500 hover:bg-alpharo-600 text-white rounded font-bold text-xs shadow-2xs transition-colors cursor-pointer"
                  @click="openVerificationModal(ord)"
                >
                  Telaah Ulang
                </button>
              </td>
            </tr>

            <tr v-if="heldOrders.length === 0">
              <td colspan="7" class="py-12 text-center text-slate-400">
                Tidak ada order yang sedang ditahan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 3: APPROVAL HISTORY SECTION -->
    <div v-else-if="activeTab === 'history'" class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5">
      <h3 class="font-bold text-slate-900 text-sm mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
        <Clock class="w-4 h-4 text-alpharo-500" />
        <span>Riwayat Keputusan Verifikasi & Approval Direktur</span>
      </h3>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
              <th class="py-2.5 px-3">No. Order</th>
              <th class="py-2.5 px-3">Client</th>
              <th class="py-2.5 px-3">Tanggal Keputusan</th>
              <th class="py-2.5 px-3">Approver</th>
              <th class="py-2.5 px-3 text-right">Nilai Total</th>
              <th class="py-2.5 px-3 text-center">Status</th>
              <th class="py-2.5 px-3">Catatan / Alasan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="ord in pastDecisions" :key="ord.id">
              <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ ord.orderNumber }}</td>
              <td class="py-3 px-3 font-medium text-slate-800">{{ ord.clientName }}</td>
              <td class="py-3 px-3 text-slate-500 whitespace-nowrap">{{ ord.approvalDate }}</td>
              <td class="py-3 px-3 text-slate-700">{{ ord.approvedBy }}</td>
              <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">
                Rp {{ Number(ord.grandTotal || 0).toLocaleString('id-ID') }}
              </td>
              <td class="py-3 px-3 text-center"><StatusBadge :status="ord.status" /></td>
              <td class="py-3 px-3 text-slate-600 italic">"{{ ord.approvalNotes || '-' }}"</td>
            </tr>

            <tr v-if="pastDecisions.length === 0">
              <td colspan="7" class="py-8 text-center text-slate-400">
                Belum ada riwayat keputusan approval masa lalu.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL 1: FULLSCREEN / 2XL VERIFICATION & DECISION MODAL (POPS UP ON "TELAAH & PUTUSKAN") -->
    <Modal
      v-model="verificationModalOpen"
      :title="`Telaah & Keputusan Verifikasi Order: ${selectedOrder?.orderNumber || ''}`"
      :subtitle="`${selectedOrder?.clientName || ''} • ${selectedOrder?.projectName || 'Pengadaan'} (Diajukan: ${selectedOrder?.date || ''})`"
      size="full"
    >
      <div v-if="selectedOrder" class="space-y-6 text-xs">
        <!-- 3 COLUMNS GRID IN MODAL -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- LEFT COLUMN (4 COLS): ORDER INFORMATION & CLIENT -->
          <div class="lg:col-span-4 bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
            <h4 class="font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2 pb-2 border-b border-slate-200">
              <Building class="w-4 h-4 text-alpharo-500" />
              <span>1. Informasi Order & Rekanan Klien</span>
            </h4>

            <div class="space-y-3">
              <div>
                <span class="text-slate-400 text-[11px] block">Nama Rekanan Client</span>
                <span class="font-bold text-slate-900 text-sm block">{{ selectedOrder.clientName }}</span>
              </div>
              <div>
                <span class="text-slate-400 text-[11px] block">Nama Kegiatan / Proyek</span>
                <span class="font-medium text-slate-800 block">{{ selectedOrder.projectName || 'Pengadaan Sarana Prasarana' }}</span>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <span class="text-slate-400 text-[11px] block">Tanggal Pengajuan</span>
                  <span class="font-mono text-slate-700 font-medium">{{ selectedOrder.date }}</span>
                </div>
                <div>
                  <span class="text-slate-400 text-[11px] block">Urgensi Proyek</span>
                  <span class="font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 inline-block text-[10px]">
                    {{ selectedOrder.urgency || 'Normal' }}
                  </span>
                </div>
              </div>
              <div>
                <span class="text-slate-400 text-[11px] block">Diajukan Oleh</span>
                <span class="text-slate-700 font-medium">{{ selectedOrder.submittedBy }}</span>
              </div>
              <div>
                <span class="text-slate-400 text-[11px] block">Alamat Pengiriman Proyek</span>
                <p class="text-slate-700 bg-white p-2.5 rounded border border-slate-200 mt-1">
                  {{ selectedOrder.address || 'Alamat terdaftar pada dokumen master instansi.' }}
                </p>
              </div>
              <div>
                <span class="text-slate-400 text-[11px] block">Catatan Marketing / Client</span>
                <p class="text-slate-600 bg-white p-2.5 rounded border border-slate-200 mt-1 italic">
                  "{{ selectedOrder.notes || 'Tidak ada catatan tambahan.' }}"
                </p>
              </div>
            </div>
          </div>

          <!-- CENTER COLUMN (5 COLS): ITEMS & SERVICES + LIVE STOCK CHECK -->
          <div class="lg:col-span-5 bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
              <h4 class="font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                <Box class="w-4 h-4 text-alpharo-500" />
                <span>2. Rincian Barang & Cek Kesiapan Stok</span>
              </h4>
              <span class="text-[11px] text-slate-500">{{ (selectedOrder.items || []).length }} Item</span>
            </div>

            <div class="border border-slate-200 rounded-lg overflow-hidden bg-white shadow-2xs max-h-64 overflow-y-auto">
              <table class="w-full text-left text-xs">
                <thead class="bg-slate-100/70 border-b border-slate-200 text-slate-600 font-semibold sticky top-0">
                  <tr>
                    <th class="p-2.5">Item Barang / Jasa</th>
                    <th class="p-2.5 text-center">Qty</th>
                    <th class="p-2.5 text-center">Stok Gudang</th>
                    <th class="p-2.5 text-right">Harga Jual</th>
                    <th class="p-2.5 text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="(it, idx) in (selectedOrder.items || [])" :key="it.id || it.sku + '-' + idx">
                    <td class="p-2.5">
                      <span class="font-medium text-slate-800 block">{{ it.name }}</span>
                      <span class="font-mono text-[10px] text-slate-400">{{ it.sku || 'SKU-CUSTOM' }}</span>
                    </td>
                    <td class="p-2.5 text-center whitespace-nowrap font-medium">
                      {{ it.qty }} {{ it.unit || 'Unit' }}
                    </td>
                    <td class="p-2.5 text-center whitespace-nowrap">
                      <span
                        v-if="getStockInfo(it).found"
                        :class="[
                          'px-2 py-0.5 rounded-full text-[10px] font-bold border inline-block',
                          getStockInfo(it).isReady
                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                            : 'bg-rose-50 text-rose-700 border-rose-200'
                        ]"
                      >
                        {{ getStockInfo(it).isReady ? `Ready (${getStockInfo(it).stock})` : `Kurang (${getStockInfo(it).stock})` }}
                      </span>
                      <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200 inline-block">
                        Custom / Jasa
                      </span>
                    </td>
                    <td class="p-2.5 text-right whitespace-nowrap text-slate-600">
                      Rp {{ Number(it.price || 0).toLocaleString('id-ID') }}
                    </td>
                    <td class="p-2.5 text-right font-bold whitespace-nowrap text-slate-900">
                      Rp {{ Number(it.total || ((it.qty || 0) * (it.price || 0))).toLocaleString('id-ID') }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-[11px] text-slate-700 flex items-start gap-2">
              <ShieldAlert class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
              <div class="leading-relaxed">
                <strong>Verifikasi Fisik:</strong> Jika stok barang mencukupi, order langsung dijadwalkan untuk packing oleh Tim Warehouse. Jika stok menipis, sistem akan membuka form pengadaan PO Supplier.
              </div>
            </div>
          </div>

          <!-- RIGHT COLUMN (3 COLS): SUMMARY & MARGIN FEASIBILITY -->
          <div class="lg:col-span-3 bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4 flex flex-col justify-between">
            <div>
              <h4 class="font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2 pb-2 border-b border-slate-200 mb-3">
                <DollarSign class="w-4 h-4 text-alpharo-500" />
                <span>3. Analisis Finansial & Margin</span>
              </h4>

              <div class="space-y-2.5 bg-white p-3.5 rounded-lg border border-slate-200 shadow-2xs">
                <div class="flex justify-between text-slate-600">
                  <span>Subtotal Nilai Jual:</span>
                  <span class="font-mono font-medium">Rp {{ Number(computedFinancials.subtotal).toLocaleString('id-ID') }}</span>
                </div>
                <div class="flex justify-between text-slate-500 text-[11px]">
                  <span>Estimasi Beban Modal (HPP):</span>
                  <span class="font-mono">Rp {{ Number(computedFinancials.hpp).toLocaleString('id-ID') }}</span>
                </div>
                <div class="pt-2 border-t border-dashed border-slate-200 flex justify-between items-center">
                  <span class="font-semibold text-slate-700">Gross Margin:</span>
                  <span class="font-mono font-bold text-emerald-600">
                    Rp {{ Number(computedFinancials.grossMarginRp).toLocaleString('id-ID') }}
                    <span class="text-xs">({{ computedFinancials.grossMarginPct }}%)</span>
                  </span>
                </div>

                <!-- FEASIBILITY BADGE -->
                <div class="pt-1">
                  <div
                    :class="[
                      'p-2 rounded text-center font-bold text-xs border flex items-center justify-center gap-1.5',
                      computedFinancials.grossMarginPct >= 20
                        ? 'bg-emerald-50 text-emerald-800 border-emerald-300'
                        : computedFinancials.grossMarginPct >= 10
                        ? 'bg-amber-50 text-amber-800 border-amber-300'
                        : 'bg-rose-50 text-rose-800 border-rose-300'
                    ]"
                  >
                    <CheckCircle2 v-if="computedFinancials.grossMarginPct >= 20" class="w-3.5 h-3.5 text-emerald-600" />
                    <AlertTriangle v-else class="w-3.5 h-3.5 text-amber-600" />
                    <span>{{ computedFinancials.marginFeasibility }}</span>
                  </div>
                </div>

                <div class="pt-2 border-t border-slate-200 flex justify-between text-slate-600">
                  <span>PPN 11%:</span>
                  <span class="font-mono">Rp {{ Number(computedFinancials.tax).toLocaleString('id-ID') }}</span>
                </div>
                <div class="pt-2 border-t border-slate-200 flex justify-between font-bold text-sm text-slate-900">
                  <span>Grand Total Tagihan:</span>
                  <span class="font-mono text-alpharo-600">Rp {{ Number(computedFinancials.grandTotal).toLocaleString('id-ID') }}</span>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">
                Catatan Verifikasi Direktur (Opsional untuk Setuju):
              </label>
              <textarea
                v-model="directorNotes"
                rows="2"
                placeholder="Instruksi khusus kepada Administrasi atau Gudang..."
                class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-alpharo-500 focus:outline-none"
              ></textarea>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 w-full">
          <button
            type="button"
            class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer"
            @click="verificationModalOpen = false"
          >
            Tutup Dialog
          </button>

          <div class="flex items-center gap-2 justify-end">
            <!-- REJECT BUTTON -->
            <button
              type="button"
              class="px-4 py-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white font-bold text-xs rounded-lg shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer"
              @click="openRejectModal(selectedOrder)"
            >
              <AlertCircle class="w-4 h-4" />
              <span>Tolak Order</span>
            </button>

            <!-- HOLD BUTTON -->
            <button
              type="button"
              class="px-4 py-2 bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-bold text-xs rounded-lg shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer"
              @click="openHoldModal(selectedOrder)"
            >
              <PauseCircle class="w-4 h-4" />
              <span>Tahan / Minta Revisi</span>
            </button>

            <!-- APPROVE BUTTON -->
            <button
              type="button"
              class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-xs rounded-lg shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer"
              @click="executeApprove(selectedOrder)"
            >
              <CheckCircle2 class="w-4 h-4" />
              <span>Setujui Order (Approve)</span>
            </button>
          </div>
        </div>
      </template>
    </Modal>

    <!-- MODAL 2: REJECT MODAL WITH MANDATORY REASON -->
    <Modal
      v-model="rejectModalOpen"
      title="Tolak Pengajuan Order (Wajib Alasan Tertulis)"
      :subtitle="`Order ${orderToReject?.orderNumber || ''} - ${orderToReject?.clientName || ''}`"
      size="md"
    >
      <div class="space-y-4">
        <p class="text-xs text-slate-600 leading-relaxed">
          Alasan penolakan akan dicatat permanen dalam audit trail dan dikirimkan sebagai notifikasi resmi ke staff Marketing.
        </p>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
            Alasan / Catatan Penolakan (Wajib Diisi) *
          </label>
          <textarea
            v-model="rejectReason"
            rows="3"
            required
            placeholder="Tuliskan alasan penolakan secara jelas (misal: 'Margin laba di bawah ambang batas minimal' atau 'Spesifikasi teknis tidak dapat dipenuhi')..."
            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-rose-500 focus:outline-none"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button
            type="button"
            class="px-3.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer"
            @click="rejectModalOpen = false"
          >
            Batal
          </button>
          <button
            type="button"
            :disabled="!rejectReason.trim()"
            class="px-4 py-1.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg disabled:opacity-50 cursor-pointer"
            @click="executeReject"
          >
            Konfirmasi Tolak Order
          </button>
        </div>
      </div>
    </Modal>

    <!-- MODAL 3: HOLD / REQUEST REVISION MODAL -->
    <Modal
      v-model="holdModalOpen"
      title="Tahan Order / Minta Revisi ke Marketing"
      :subtitle="`Order ${orderToHold?.orderNumber || ''} - ${orderToHold?.clientName || ''}`"
      size="md"
    >
      <div class="space-y-4">
        <p class="text-xs text-slate-600 leading-relaxed">
          Order akan ditandai berstatus <strong class="text-amber-700 font-semibold">"Ditahan"</strong>. Tim Marketing akan menerima notifikasi untuk melakukan konfirmasi revisi spesifikasi atau kepastian pembayaran dengan client.
        </p>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
            Alasan Penahanan / Instruksi Revisi (Wajib Diisi) *
          </label>
          <textarea
            v-model="holdReason"
            rows="3"
            required
            placeholder="Tuliskan alasan penahanan (misal: 'Menunggu konfirmasi uang muka DP 30%' atau 'Minta marketing negosiasi ulang harga semen')..."
            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-amber-500 focus:outline-none"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
          <button
            type="button"
            class="px-3.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer"
            @click="holdModalOpen = false"
          >
            Batal
          </button>
          <button
            type="button"
            :disabled="!holdReason.trim()"
            class="px-4 py-1.5 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-lg disabled:opacity-50 cursor-pointer"
            @click="executeHold"
          >
            Konfirmasi Tahan Order
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue';
import AppLayout from '../../components/layout/AppLayout.vue';
import StatusBadge from '../../components/common/StatusBadge.vue';
import Modal from '../../components/common/Modal.vue';
import { store, storeActions } from '../../stores/mockData';
import { useToast } from '../../services/toast';
import {
  CheckCircle2,
  AlertCircle,
  PauseCircle,
  Clock,
  Eye,
  Building,
  Box,
  DollarSign,
  ShieldAlert,
  AlertTriangle,
  Maximize2
} from 'lucide-vue-next';

const toast = useToast();

const activeTab = ref('pending');
const selectedOrder = ref(null);
const verificationPanelRef = ref(null);
const verificationModalOpen = ref(false);

const directorNotes = ref('');
const rejectModalOpen = ref(false);
const orderToReject = ref(null);
const rejectReason = ref('');

const holdModalOpen = ref(false);
const orderToHold = ref(null);
const holdReason = ref('');

// Computed Lists
const pendingOrders = computed(() => {
  return (store.orders || []).filter((o) => o.status === 'Menunggu Verifikasi');
});

const heldOrders = computed(() => {
  return (store.orders || []).filter((o) => o.status === 'Ditahan' || o.isHold);
});

const pastDecisions = computed(() => {
  return (store.orders || []).filter((o) => o.approvalDate);
});

// Auto-select first pending order if exists
if (pendingOrders.value.length > 0) {
  selectedOrder.value = pendingOrders.value[0];
}

// Live stock checking from master data products
const getStockInfo = (item) => {
  if (!item) return { found: false, stock: 0, isReady: true };
  const products = store.products || [];
  const product = products.find(
    (p) =>
      (p.sku && item.sku && p.sku.toLowerCase() === item.sku.toLowerCase()) ||
      (p.name && item.name && p.name.toLowerCase() === item.name.toLowerCase()) ||
      (item.productId && p.id === item.productId)
  );

  if (!product) {
    return {
      found: false,
      stock: 0,
      isReady: true,
      unit: item.unit || 'Unit',
    };
  }

  const stock = Number(product.stock || 0);
  const reqQty = Number(item.qty || 0);
  const isReady = stock >= reqQty;

  return {
    found: true,
    stock,
    isReady,
    unit: product.unit || item.unit || 'Unit',
  };
};

// Financial Margin Feasibility Calculation
const computedFinancials = computed(() => {
  const ord = selectedOrder.value;
  if (!ord) {
    return {
      subtotal: 0,
      hpp: 0,
      grossMarginRp: 0,
      grossMarginPct: 0,
      marginFeasibility: 'Menunggu Data',
      tax: 0,
      grandTotal: 0,
    };
  }

  const items = ord.items || [];
  const subtotal = Number(ord.subtotal) || items.reduce((sum, it) => sum + (Number(it.qty || 0) * Number(it.price || 0)), 0);

  let totalHpp = 0;
  items.forEach((it) => {
    const prod = (store.products || []).find(
      (p) =>
        (p.sku && it.sku && p.sku.toLowerCase() === it.sku.toLowerCase()) ||
        (p.name && it.name && p.name.toLowerCase() === it.name.toLowerCase())
    );
    const cost = prod?.costPrice || Math.round((Number(it.price) || 0) * 0.78);
    totalHpp += cost * (Number(it.qty) || 0);
  });

  if (totalHpp === 0 && subtotal > 0) {
    totalHpp = Math.round(subtotal * 0.78);
  }

  const grossMarginRp = Math.max(0, subtotal - totalHpp);
  const grossMarginPct = subtotal > 0 ? Math.round((grossMarginRp / subtotal) * 100) : 0;

  let marginFeasibility = 'Margin Sehat & Sangat Layak (> 20%)';
  if (grossMarginPct < 10) {
    marginFeasibility = 'Margin Rendah / Berisiko (< 10%)';
  } else if (grossMarginPct < 20) {
    marginFeasibility = 'Margin Wajar (10% - 20%)';
  }

  const tax = Number(ord.tax) || Math.round(subtotal * 0.11);
  const grandTotal = Number(ord.grandTotal) || (subtotal + tax);

  return {
    subtotal,
    hpp: totalHpp,
    grossMarginRp,
    grossMarginPct,
    marginFeasibility,
    tax,
    grandTotal,
  };
});

// Row Click -> Select and Smooth Scroll to Panel
const selectForVerification = (order) => {
  selectedOrder.value = order;
  nextTick(() => {
    verificationPanelRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
};

// "Telaah & Putuskan" Button Click -> Open Full Interactive Modal
const openVerificationModal = (order) => {
  selectedOrder.value = order;
  verificationModalOpen.value = true;
};

// Approval Execution
const executeApprove = (order) => {
  if (!order) return;
  storeActions.approveOrder(order.id, directorNotes.value || 'Disetujui penuh oleh Direktur.');
  toast.success(`Order ${order.orderNumber} telah disetujui! Notifikasi diterbitkan ke Administrasi & Warehouse.`);
  verificationModalOpen.value = false;
  directorNotes.value = '';
  selectedOrder.value = pendingOrders.value[0] || null;
};

// Reject Execution
const openRejectModal = (order) => {
  orderToReject.value = order;
  rejectReason.value = '';
  rejectModalOpen.value = true;
};

const executeReject = () => {
  if (orderToReject.value && rejectReason.value.trim()) {
    storeActions.rejectOrder(orderToReject.value.id, rejectReason.value.trim());
    toast.error(`Order ${orderToReject.value.orderNumber} telah ditolak.`);
    rejectModalOpen.value = false;
    verificationModalOpen.value = false;
    selectedOrder.value = pendingOrders.value[0] || null;
  }
};

// Hold Execution
const openHoldModal = (order) => {
  orderToHold.value = order;
  holdReason.value = '';
  holdModalOpen.value = true;
};

const executeHold = () => {
  if (orderToHold.value && holdReason.value.trim()) {
    storeActions.holdOrder(orderToHold.value.id, holdReason.value.trim());
    toast.warning(`Order ${orderToHold.value.orderNumber} telah ditahan untuk revisi.`);
    holdModalOpen.value = false;
    verificationModalOpen.value = false;
    selectedOrder.value = pendingOrders.value[0] || null;
  }
};

// Release Hold
const executeReleaseHold = (order) => {
  storeActions.releaseOrderHold(order.id, 'Tahanan dilepas Direktur.');
  toast.success(`Tahanan order ${order.orderNumber} telah dilepas. Status kembali ke antrean verifikasi.`);
  activeTab.value = 'pending';
  selectedOrder.value = order;
};
</script>
