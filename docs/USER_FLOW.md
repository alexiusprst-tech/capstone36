# End-to-End User Flow & BPMN Alignment
## CV. Alphate Artha Jaya — Alur Interaksi Pengguna Berdasarkan BPMN To-Be

Diagram BPMN To-Be menghubungkan 6 entitas kerja secara runtut:

```text
[Klien] --> [Marketing] --> [Warehouse] --> [Finance] --> [Supplier] --> [Administrasi SPJ]
```

---

## 1. Tahapan Alur Kerja End-to-End

### Fase 1: Input Pesanan Klien (Marketing)
1. Klien memberikan pesanan proyek pengadaan/konstruksi.
2. Marketing mendaftarkan pesanan di `/marketing/orders/create` (memilih klien dari master wilayah DKI Jakarta).
3. Marketing meneruskan data kebutuhan barang ke Gudang.

### Fase 2: Pengecekan Stok & Pengajuan Pembelian (Warehouse)
1. Gudang mengecek ketersediaan fisik barang pada katalog 2.196 SKU.
2. **Percabangan Stok**:
   * *Stok Cukup*: Gudang mengonfirmasi barang siap dikirim ke Marketing.
   * *Stok Kurang*: Gudang membuka `/warehouse/requests` dan menyusun *Material Request*.
3. Pada tabel pengajuan: Staf gudang memilih item via modal katalog 2.196 SKU, satuan terkunci otomatis, memasukkan kuantitas, dan mengirim pengajuan ke Finance.

### Fase 3: Transaksi Pembelian Supplier (Finance)
1. Finance membuka `/finance/purchases/create`.
2. Sistem otomatis menampilkan banner auto-sync dari pengajuan gudang yang berstatus `MENUNGGU_FINANCE`.
3. Seluruh baris barang terisi otomatis. Finance melengkapi:
   * No Faktur resmi dari supplier fisik.
   * Supplier Default rekanan.
   * Harga beli aktual dan diskon/satuan.
   * Metode pembayaran (Transfer Bank / Tunai).
4. Finance menyimpan transaksi `TRX-YYYYMMDD-XXXX`. Status pengajuan gudang berubah menjadi `DIPROSES_PEMBELIAN`.

### Fase 4: Penerimaan Fisik & Quality Control (Warehouse)
1. Supplier mengirimkan fisik material ke gudang perusahaan.
2. Staf gudang melakukan inspeksi mutu & kuantitas (QC) di `/warehouse/fulfillment`.
3. Mengonfirmasi penerimaan barang $ightarrow$ Kuantitas stok master bertambah otomatis.

### Fase 5: Pengiriman Fisik & Penagihan Paralel
* **Cabang Logistik**: Logistik/Marketing mencetak Surat Jalan $ightarrow$ Klien menerima barang & menandatangani BAST/Surat Jalan.
* **Cabang Keuangan**: Finance menerbitkan Faktur Penjualan / Invoice $ightarrow$ Klien membayar $ightarrow$ Finance menerbitkan Kuitansi Pelunasan.

### Fase 6: Konsolidasi & Penerbitan Dokumen SPJ (Administrasi)
1. Administrasi membuka `/administration/spj`.
2. Sistem menampilkan checklist otomatis kelengkapan 5 dokumen pokok (Surat Pesanan, Faktur Pembelian, Surat Jalan, Invoice, Kuitansi).
3. Administrasi menekan tombol "Generate Dokumen SPJ Lengkap" untuk menghasilkan bundel berkas siap audit.
