# Business Rules & System Governance
## CV. Alphate Artha Jaya — Aturan Bisnis & Kontrol Operasional

| Dokumen | Panduan Aturan Bisnis, Validasi, & Otorisasi |
| :--- | :--- |
| **Penerapan** | Backend Laravel 12 & Frontend Vue 3 |
| **Dasar Analisis** | Diagram BPMN To-Be: Proses Pesanan Hingga Dokumen SPJ |

---

## 1. Aturan Pemisahan Tugas (Segregation of Duties - SoD)

* **SoD-01 (Gudang vs Finance)**: Staf Gudang hanya berhak membuat dokumen pengajuan barang (*Material Request*) dan mengecek fisik barang masuk (QC). Staf Gudang **DILARANG KERAS** menginputkan harga beli aktual, diskon supplier, atau melakukan transaksi pembayaran.
* **SoD-02 (Pembelian vs Update Stok)**: Pembuatan transaksi pembelian oleh Finance **TIDAK DAPAT** menambah kuantitas stok secara langsung. Kuantitas stok produk hanya bertambah setelah staf Gudang menerima barang fisik dan menekan tombol konfirmasi inspeksi QC.
* **SoD-03 (Administrasi SPJ)**: Bagian Administrasi menyusun dokumen SPJ dari data transaksi yang sah (Order Marketing, Surat Jalan Gudang, Faktur & Kuitansi Finance). Administrasi dilarang membuat transaksi SPJ fiktif.

---

## 2. Aturan Inventaris & Master Data

* **INV-01 (SKU Immutability)**: Kode SKU berformat 9 karakter (`A` diikuti 8 digit: `A00000001` - `A00002196`). SKU bersifat permanen dan tidak boleh diubah setelah transaksi terkait terbentuk.
* **INV-02 (Unit Locking)**: Satuan barang (ZAK, BATANG, PCS, MTR, KALENG) terkunci otomatis mengikuti master katalog produk. Pengguna dilarang mengubah satuan secara manual di baris transaksi.
* **INV-03 (Non-Negative Stock)**: Kuantitas stok tidak boleh bernilai negatif ($Stok \ge 0$). Jika stok kurang, wajib membuat pengajuan material request.

---

## 3. Aturan Transaksi Pembelian & Finansial

* **Kalkulasi Baris**:
  $$\\text{Subtotal Baris} = (\\text{Qty} \\times \\text{Harga Beli}) - \\text{Diskon Nominal}$$
* **Konsistensi Diskon**:
  * Input Diskon Persen ($Diskon_{\%}$) otomatis menghitung Diskon Nominal.
  * Input Diskon Nominal ($Diskon_{Nominal}$) otomatis menghitung Diskon Persen.
  * Nilai diskon tidak boleh melebihi nilai kotor baris ($\\text{Diskon} \\le \\text{Qty} \\times \\text{Harga Beli}$).

---

## 4. Aturan Kronologis Dokumen SPJ

Tanggal seluruh dokumen proyek wajib memenuhi urutan kronologis yang sah:
$$\\text{Tgl Pesanan} \\le \\text{Tgl Pengajuan} \\le \\text{Tgl Pembelian} \\le \\text{Tgl Surat Jalan} \\le \\text{Tgl Faktur} \\le \\text{Tgl SPJ}$$
