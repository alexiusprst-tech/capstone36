# System Architecture & Technical Specifications
## CV. Alphate Artha Jaya — Integrated Procurement & SPJ System

| Komponen | Spesifikasi Arsitektur |
| :--- | :--- |
| **Pola Arsitektur** | Decoupled Single-Page Application (SPA) + Headless RESTful API |
| **Backend Framework** | Laravel 12.x on PHP 8.4 (OPcache enabled) |
| **Frontend Framework** | Vue 3.5 (Composition API `<script setup>`), Vite 6, Tailwind CSS v4 |
| **Autentikasi** | Laravel Sanctum Token-Based Authentication |
| **Database** | SQLite 3 (Dev/Test) & MySQL 8.0+ InnoDB (Production) |

---

## 1. Struktur Layer Sistem

```text
[ Browser Client - Vue 3 / Vite 6 ]
   ├── Marketing Views (Clients, Orders)
   ├── Warehouse Views (2.196 Products, Requests, Fulfillment)
   ├── Finance Views (Purchases, Invoices, Payments)
   ├── Administration Views (SPJ Generator, Document Verification)
   └── Pinia Store & Vue Router 4 (Role-Based Route Guards)
            │
            ▼ HTTPS / JSON REST API
[ API Gateway & Security - Laravel 12 ]
   ├── routes/api.php
   ├── Sanctum Bearer Token Auth Guard
   ├── Role-Based Authorization Middleware
   └── Form Request Validation Engine
            │
            ▼ Domain Services
   ├── ProductController (Catalog, Excel Import/Export)
   ├── PurchaseController (Warehouse Requests & Purchase Transactions)
   ├── SpjController (SPJ Compilation & Bulk Generation)
   └── OrderDocumentController (Docx Template Engine & PDF Exporter)
            │
            ▼ Persistence & Storage
   ├── Database: SQLite (Testing) / MySQL 8.0+ (Production)
   └── File Storage: storage/app/public/ (Generated SPJ & Scanned BAST)
```

---

## 2. Pipeline Data Antar-Modul (BPMN Alignment)

1. **Order Intake**: Marketing menginput pesanan klien $ightarrow$ Instruksi cek stok dikirim ke Gudang.
2. **Stock Verification**: Gudang mengecek ketersediaan pada katalog 2.196 SKU. Jika kurang $ightarrow$ Terbitkan *Material Request* (`REQ-YYYY-XXX`).
3. **Purchase Processing**: Finance menarik pengajuan gudang $ightarrow$ Form "Tambah Transaksi Pembelian" ter-populate otomatis $ightarrow$ Input no faktur, supplier, harga beli, diskon $ightarrow$ Simpan transaksi (`TRX-YYYYMMDD-XXXX`).
4. **Goods Receipt & Stock-In**: Supplier kirim barang ke Gudang $ightarrow$ Gudang verifikasi QC $ightarrow$ Stok master bertambah.
5. **Parallel Delivery & Invoicing**: Logistik kirim barang (Surat Jalan/BAST) & Finance kirim invoice penagihan ke Klien.
6. **SPJ Compilation**: Administrasi memverifikasi 5 dokumen pokok $ightarrow$ Generate otomatis bundel dokumen SPJ.
