# Design System & UI Specification — CV. Alpharo Artha Jaya

Dokumen ini adalah spesifikasi teknis antarmuka (UI/UX) sistem internal CV. Alpharo Artha Jaya (dalam dokumen proyek disebut juga CV. Alphate Artha Jaya). Seluruh nilai token, nama class, dan struktur komponen di bawah ini diambil langsung dari implementasi kode aktif di `frontend/src/` (Vue 3 + Tailwind CSS + Lucide Icons).

---

## 1. Palette Warna & Token CSS Aktif

### 1.1 Navigasi & Sidebar
| Elemen | Hex / Tailwind Class | Kegunaan |
| :--- | :--- | :--- |
| **Sidebar Background** | `from-[#0B4DA2] via-[#09428F] to-[#062F69]` | Gradasi vertikal biru royal pada sidebar kiri |
| **Sidebar Border** | `border-white/10` | Garis pemisah logo header dan footer akun |
| **Active Nav Item** | `bg-[#F5A623] text-slate-900` | Menu yang sedang dibuka (emas/oranye kontras tinggi) |
| **Active Nav Shadow** | `shadow-md shadow-amber-900/20` | Bayangan halus item aktif |
| **Inactive Nav Item** | `text-white/85 hover:text-white hover:bg-white/10` | Menu tidak aktif saat idle dan hover |
| **Nav Section Title** | `text-blue-200/70 text-[11px] font-semibold tracking-wider uppercase` | Header kelompok menu (misal: OPERASIONAL MARKETING) |
| **Active Nav Badge** | `bg-slate-900 text-amber-300 text-[10px] font-bold px-2 py-0.5 rounded-full` | Indikator jumlah notifikasi pada menu aktif |
| **Inactive Nav Badge** | `bg-white/20 text-white text-[10px] font-bold px-2 py-0.5 rounded-full` | Indikator jumlah notifikasi pada menu idle |

### 1.2 Layout & Dasar Halaman
| Elemen | Hex / Tailwind Class | Kegunaan |
| :--- | :--- | :--- |
| **Page Background** | `#F8FAFC` (`bg-slate-50`) | Latar belakang seluruh halaman aplikasi |
| **Topbar Background** | `#FFFFFF` (`bg-white`) | Header atas sticky setinggi 64px (`h-16`) |
| **Topbar Border** | `#E2E8F0` (`border-slate-200`) | Garis bawah topbar dan pemisah kartu |
| **Primary Text** | `#0F172A` (`text-slate-900`) | Judul halaman, label tebal, nominal penting |
| **Body Text** | `#334155` (`text-slate-700`) | Teks paragraf, input form, data sel tabel |
| **Muted Text** | `#64748B` (`text-slate-500`) | Metadata, keterangan tanggal, sub-label |

### 1.3 Action Icon Buttons (Standard Gradient Fill)
Semua icon button aksi pada tabel dan form wajib menggunakan gradasi vertikal penuh (`bg-gradient-to-b`) dengan icon warna putih pekat (`text-white`) berukuran seragam 28x28px (`w-7 h-7`) atau 32x32px (`w-8 h-8`). Tidak diperkenankan menggunakan tombol aksi bergaya outline/pucat.

| Aksi | Tailwind Classes | Visual & Kegunaan |
| :--- | :--- | :--- |
| **Search / Cari `[Q]`** | `w-7 h-7 inline-flex items-center justify-center bg-gradient-to-b from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700 active:from-sky-700 active:to-sky-800 text-white rounded-lg shadow-2xs border border-sky-600/30 transition-all cursor-pointer` | Buka katalog modal (2.196 SKU / Supplier) |
| **Tambah `[+]`** | `w-7 h-7 inline-flex items-center justify-center bg-gradient-to-b from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 active:from-emerald-700 active:to-emerald-800 text-white rounded-lg shadow-2xs border border-emerald-600/30 transition-all cursor-pointer` | Tambah cepat item atau supplier baru |
| **Reset / Clear `[X]`** | `w-7 h-7 inline-flex items-center justify-center bg-gradient-to-b from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 active:from-rose-700 active:to-rose-800 text-white rounded-lg shadow-2xs border border-rose-600/30 transition-all cursor-pointer` | Mengosongkan nilai input terpilih |
| **Hapus Baris `[Trash2]`** | `w-7 h-7 inline-flex items-center justify-center bg-gradient-to-b from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 active:from-red-700 active:to-red-800 text-white rounded-lg shadow-2xs border border-red-700/30 transition-all cursor-pointer` | Hapus baris transaksi atau hapus data |
| **Edit `[Edit]`** | `w-7 h-7 inline-flex items-center justify-center bg-gradient-to-b from-amber-400 to-amber-500 hover:from-amber-500 hover:to-amber-600 active:from-amber-600 active:to-amber-700 text-white rounded-lg shadow-2xs border border-amber-500/30 transition-all cursor-pointer` | Edit master barang, kategori, satuan |

### 1.4 Status Badges
| Status | Kelas Tailwind | Contoh Penggunaan |
| :--- | :--- | :--- |
| **Lunas / Selesai** | `bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold text-[10px] px-2.5 py-0.5` | Faktur lunas, BAST ditandatangani |
| **Menunggu / Proses** | `bg-amber-50 text-amber-800 border border-amber-200 rounded-full font-bold text-[10px] px-2.5 py-0.5` | Pengajuan gudang pending review |
| **Draft / Submitted** | `bg-sky-50 text-sky-700 border border-sky-200 rounded-full font-bold text-[10px] px-2.5 py-0.5` | Material request baru terbit |
| **Ditolak / Batal** | `bg-rose-50 text-rose-700 border border-rose-200 rounded-full font-bold text-[10px] px-2.5 py-0.5` | QC gagal, pesanan dibatalkan |

---

## 2. Struktur Layout Sistem

### 2.1 Sidebar (`AppLayout.vue`)
* **Lebar Lebar Penuh (Expanded)**: `w-64` (256px).
* **Lebar Ciut (Collapsed)**: `w-20` (80px), icon rata tengah (`justify-center`).
* **Responsif Mobile**: Tersembunyi di luar layar (`-translate-x-full`), dibuka lewat drawer dengan overlay `fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs`.
* **Header Sidebar**: Tinggi 80px (`h-20`), memuat logo `AlpharoLogo` dan tombol collapse toggle (`ChevronLeft` / `ChevronRight`).

### 2.2 Topbar
* **Tinggi**: `h-16` (64px) sticky di bagian atas (`sticky top-0 z-30`).
* **Komponen Kiri**: Tombol hamburger mobile (`Menu`), breadcrumb / judul halaman aktif (`pageTitle`).
* **Komponen Kanan**:
  * Global Search: Input pencarian dengan icon `Search` di sebelah kiri (`w-4 h-4 text-slate-400`).
  * Notifikasi: Tombol lonceng (`Bell`) dengan badge angka merah (`bg-red-500 text-white text-[10px] rounded-full`).
  * Role Switcher / Profile: Avatar pengguna, nama lengkap (`text-xs font-bold text-slate-900`), dan label role aktif (`text-[10px] text-slate-500 font-medium`).

### 2.3 Kontainer Konten Utama
* `max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8`
* Menampung slot `#header` (judul halaman dan tombol aksi primer seperti *Kembali*, *Tambah Transaksi*, *Export*) dan default slot untuk tabel/form.

---

## 3. Spesifikasi Komponen Halaman

### 3.1 Header Bagian Form (*Section Header*)
Digunakan pada formulir transaksi panjang (seperti `FinancePurchaseCreate.vue`):
```html
<div class="flex items-center gap-2 pb-1.5 border-b-2 border-[#1e3a8a]">
  <div class="w-4 h-4 border border-slate-400 rounded-xs flex items-center justify-center text-[10px] font-bold text-slate-600">
    <span class="tracking-tighter">≡!</span>
  </div>
  <h2 class="text-xs sm:text-sm font-bold text-slate-900">Detail Pembelian</h2>
</div>
```
* **Garis Bawah**: `border-b-2 border-[#1e3a8a]` (garis solid biru tua).
* **Icon Badge**: Kotak persegi kecil dengan simbol identifikasi section.

### 3.2 Banner Sinkronisasi Otomatis (*Auto-Sync Banner*)
Muncul saat data form ter-generate otomatis dari modul lain (misal dari *Material Request Gudang* ke *Pembelian Finance*):
```html
<div class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between text-xs text-emerald-900 shadow-2xs">
  <div class="flex items-center gap-2.5">
    <span class="p-1 bg-emerald-600 text-white rounded-md shrink-0">
      <Check class="w-4 h-4 stroke-[3]" />
    </span>
    <div>
      <span class="font-bold">Data Ter-generate Otomatis dari Pengajuan Gudang: </span>
      <span class="font-mono font-bold bg-emerald-100 px-1.5 py-0.5 rounded">REQ-2026-001</span>
      <span class="text-emerald-700 ml-1">(Renovasi Gedung Arsip)</span>
    </div>
  </div>
  <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-full">
    Auto-Sync Finance
  </span>
</div>
```

### 3.3 Tabel Transaksi & Inventori
* **Wadah Tabel**: `overflow-x-auto border border-slate-200 rounded-xl bg-white shadow-2xs`.
* **Header Kolom (`<thead>`)**:
  * Kelas: `bg-slate-50/90 border-b border-slate-200 text-slate-700 font-semibold text-[11px] uppercase tracking-wide`.
  * Padding: `py-2.5 px-3` (pada form padat) atau `py-3 px-4` (pada tabel monitoring).
* **Baris Data (`<tbody>`)**:
  * Kelas baris: `hover:bg-slate-50/70 transition-colors divide-y divide-slate-100`.
  * Kolom teks/deskripsi: `text-xs text-slate-800 font-medium`.
  * Kolom nominal/angka: `font-mono text-xs text-slate-800 text-right font-semibold`.
  * Kolom kode/SKU: `font-mono text-xs text-sky-700 font-bold bg-sky-50 px-2 py-0.5 rounded`.
* **Footer Tabel (`<tfoot>`)**:
  * Baris rekapitulasi / total stok: `bg-slate-50/80 border-t border-slate-200 font-bold text-slate-800 text-xs`.
  * Pagination: `px-4 py-3 border-t border-slate-200 flex items-center justify-between text-xs text-slate-600 bg-white`.
  * Indikator data: *"Menampilkan 1 - 10 dari 2.196 data"*.

### 3.4 Modal Dialog (*Picker Catalog & Input Cepat*)
* **Overlay**: `fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4`.
* **Kotak Modal**: `bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden`.
* **Header Modal**: `p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/80`.
* **Input Filter**: `relative w-full mb-3` dengan icon `Search` di sisi kiri (`pl-9 text-xs border border-slate-300 rounded-lg`).
* **List Item**: Daftar item tergulir (`overflow-y-auto divide-y divide-slate-100`) dengan efek `hover:bg-sky-50/70 cursor-pointer`.
* **Tombol Pilih**: Badge hijau `px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-bold text-[10px]`.

---

## 4. Struktur Navigasi Berdasarkan Role

Menu navigasi dikelompokkan sesuai batasan kewenangan di `AppLayout.vue`:

### 4.1 Marketing
* **Utama**: Dashboard (`/marketing/dashboard`)
* **Operasional Marketing**:
  * Client (`/marketing/clients`)
  * Order (`/marketing/orders`)
  * Monitoring Order (`/marketing/orders/monitoring`)
* **Akun**: Notifikasi, Profile

### 4.2 Warehouse (Gudang)
* **Utama**: Dashboard (`/warehouse/dashboard`)
* **Logistik & Inventori**:
  * Data Barang (`/warehouse/products` — 2.196 SKU, Import/Export Excel, Kategori, Satuan)
  * Stok & Mutasi (`/warehouse/inventory`)
  * Pengajuan Kebutuhan (`/warehouse/requests` — Material Request ke Finance)
  * Pemenuhan Barang (`/warehouse/fulfillment` — QC barang tiba dari Supplier)
  * Pengiriman (`/warehouse/shipments`)
  * Surat Jalan Gudang (`/warehouse/delivery-notes`)
* **Akun**: Notifikasi, Profile

### 4.3 Finance (Keuangan)
* **Utama**: Dashboard (`/finance/dashboard`)
* **Keuangan & Pembayaran**:
  * Transaksi Pembelian (`/finance/purchases` — Daftar PO & Form Tambah Pembelian Supplier)
  * Invoice (`/finance/invoices` — Penagihan klien)
  * Pembayaran Penuh & Termin (`/finance/payments`)
  * Monitoring Keuangan (`/finance/monitoring`)
  * Laporan Keuangan (`/finance/reports`)
* **Akun**: Notifikasi, Profile

### 4.4 Administrasi (SPJ)
* **Utama**: Dashboard (`/administration/dashboard`)
* **Dokumen Legal & Proyek**:
  * Dokumen SPJ (`/administration/spj` — Verifikasi 5 dokumen & kompilasi berkas SPJ)
* **Akun**: Notifikasi, Profile

### 4.5 Direktur (Executive)
* **Utama**: Dashboard (`/director/dashboard`)
* **Monitoring Eksekutif**:
  * Monitoring Order, Monitoring Warehouse, Monitoring Administrasi, Monitoring Finance
* **Analitik & Audit**:
  * Laporan (`/director/reports`), Activity Log (`/director/activities`)
* **Akun**: Notifikasi

---

## 5. Kaidah Tipografi & Format Data

* **Font Utama**: Inter / system-ui sans-serif (`font-sans`).
* **Font Monospace**: Consolas, Menlo, Monaco, monospace (`font-mono`) untuk:
  * Nomor SKU (`A00001770`)
  * Kode Pengajuan (`REQ-2026-001`)
  * Kode Transaksi (`TRX-20261014-0001`)
  * Nominal Uang (`Rp 1.450.000`)
  * Kuantitas Stok (`100 ZAK`)
* **Format Mata Uang**: Wajib diawali prefiks `Rp ` dengan pemisah ribuan titik (`toLocaleString('id-ID')`), contoh: `Rp 24.500.000`.
* **Format Tanggal**: Format ISO tampilan Indonesia `DD/MM/YYYY` atau `YYYY-MM-DD` yang konsisten.
