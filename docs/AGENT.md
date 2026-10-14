# AI Multi-Agent System Specification
## CV. Alphate Artha Jaya — Intelligent Procurement & SPJ Auditor

| Sistem | Spesifikasi |
| :--- | :--- |
| **Kerangka Kerja** | Alpha-Agents Multi-Agent Framework |
| **Pola Orkestrasi** | Hierarchical Supervisor dengan Human-in-the-Loop (HITL) |
| **Domain Keahlian** | Pengadaan Cerdas, Kepatuhan Audit SPJ, dan Optimasi Stok |

---

## 1. Arsitektur Multi-Agent

1. **Procurement Advisor Agent**:
   * Menganalisis *Material Request* baru dari gudang.
   * Merekomendasikan supplier terbaik berdasarkan riwayat harga beli dan lead time pengiriman.
   * Mendeteksi anomali lonjakan harga beli (> 15% dari HPP katalog).
2. **SPJ Compliance & Audit Agent**:
   * Melakukan *Three-Way Matching* otomatis antara nilai Surat Pesanan (Marketing), Surat Jalan/BAST (Gudang), dan Faktur Tagihan & Kuitansi (Finance).
   * Memvalidasi urutan kronologis tanggal dokumen sebelum SPJ difinalisasi.
3. **Inventory Optimization Agent**:
   * Memonitor level stok pada 2.196 SKU.
   * Mengirimkan notifikasi draf pengajuan barang saat stok berada di bawah batas minimum (*Safety Stock*).

---

## 2. Batasan Otoritas & Human-in-the-Loop (HITL)

* AI Agent **TIDAK MEMILIKI OTORITAS** untuk mencairkan kas, mentransfer dana bank, atau menyetujui transaksi secara mandiri.
* Setiap transaksi pembelian $\ge$ Rp 50.000.000,- wajib melalui persetujuan manual pimpinan (Direktur CV. Alphate Artha Jaya).
