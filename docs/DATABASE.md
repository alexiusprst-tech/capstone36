# Database Architecture & Entity Relationship Design
## CV. Alphate Artha Jaya — Dokumentasi Basis Data Sistem

| Parameter | Spesifikasi |
| :--- | :--- |
| **Engine** | SQLite 3 (Dev / Test) & MySQL 8.0+ InnoDB (Production) |
| **Collation** | `utf8mb4_unicode_ci` |
| **Total Tabel** | 12 Tabel Relasional |

---

## 1. Kamus Data Tabel Utama

### 1.1 Tabel `products` (2.196 Master SKU)
* `id`: `BIGINT UNSIGNED`, Primary Key, Auto Increment.
* `sku`: `VARCHAR(50)`, Unique, Indexed (Format `A00000001` - `A00002196`).
* `name`: `VARCHAR(255)`, Indexed.
* `category`: `VARCHAR(150)`, Indexed.
* `unit`: `VARCHAR(50)`, Indexed (Locked: ZAK, BATANG, PCS, MTR, KALENG).
* `stock`: `INTEGER`, Default: 0.
* `min_stock`: `INTEGER`, Default: 0.
* `last_buy_price`: `DECIMAL(15,2)`, Default: 0.
* `hpp`: `DECIMAL(15,2)`, Default: 0.
* `sell_price`: `DECIMAL(15,2)`, Default: 0.
* `status`: `VARCHAR(30)`, Default: 'Tersedia'.

### 1.2 Tabel `warehouse_requests`
* `id`: `BIGINT UNSIGNED`, Primary Key, Auto Increment.
* `request_code`: `VARCHAR(255)`, Unique (`REQ-YYYY-XXX`).
* `request_date`: `DATE`.
* `project_id`: `VARCHAR(255)`, Nullable.
* `project_name`: `VARCHAR(255)`, Nullable.
* `purpose`: `TEXT`, Nullable.
* `requested_by`: `VARCHAR(255)`.
* `status`: `VARCHAR(255)`, Default: `'MENUNGGU_FINANCE'` (`DIPROSES_PEMBELIAN`, `SELESAI`, `DITOLAK`).
* `total_est_cost`: `DECIMAL(15,2)`, Default: 0.
* `items`: `JSON` (Array of items: `sku`, `name`, `qty`, `unit`, `est_price`, `subtotal`).
* `purchase_code`: `VARCHAR(255)`, Nullable.

### 1.3 Tabel `purchase_transactions`
* `id`: `BIGINT UNSIGNED`, Primary Key, Auto Increment.
* `purchase_code`: `VARCHAR(255)`, Unique (`TRX-YYYYMMDD-XXXX` / `PB...`).
* `transaction_date`: `DATE`.
* `invoice_number`: `VARCHAR(255)`, Nullable (Nomor faktur fisik supplier).
* `project_id`: `VARCHAR(255)`, Nullable.
* `project_name`: `VARCHAR(255)`, Nullable.
* `default_supplier`: `VARCHAR(255)`, Nullable.
* `warehouse_request_id`: `BIGINT UNSIGNED`, Nullable.
* `warehouse_request_code`: `VARCHAR(255)`, Nullable.
* `subtotal`: `DECIMAL(15,2)`, Default: 0.
* `discount`: `DECIMAL(15,2)`, Default: 0.
* `total_price`: `DECIMAL(15,2)`, Default: 0.
* `payment_method`: `VARCHAR(255)`, Default: 'Tunai'.
* `status`: `VARCHAR(255)`, Default: 'LUNAS'.
* `items`: `JSON` (Array of purchase rows: `barang`, `supplier`, `qty`, `harga_beli`, `diskon_percent`, `diskon_nominal`, `subtotal`).
* `created_by`: `VARCHAR(255)`, Nullable.

### 1.4 Tabel `suppliers`
* `id`: `BIGINT UNSIGNED`, Primary Key, Auto Increment.
* `code`: `VARCHAR(255)`, Unique (`SUP-001` - `SUP-005`).
* `name`: `VARCHAR(255)`.
* `phone`: `VARCHAR(255)`, Nullable.
* `email`: `VARCHAR(255)`, Nullable.
* `address`: `TEXT`, Nullable.
* `is_active`: `BOOLEAN`, Default: true.

### 1.5 Tabel `spj_projects` & `spj_documents`
* `spj_projects`: Metadata proyek pengadaan, plafon anggaran, nilai proyek, realisasi pembayaran, dan PIC penandatangan.
* `spj_documents`: 15 instrumen dokumen SPJ (`doc_key`, `doc_title`, `doc_order`, `status`, `file_path`, `is_uploaded`).

---

## 2. Seeder & Baseline Database

1. `ProductSeeder`: Memuat 2.196 master barang dari `products_seed.json`.
2. `PurchaseAndRequestSeeder`: Memuat 5 rekanan supplier dan contoh pengajuan aktif `REQ-2026-001`.
3. `SpjProjectSeeder`: Memuat proyek pengadaan dan 15 template dokumen SPJ.
4. `RegionSeeder`: Memuat hierarki wilayah DKI Jakarta (Kota, Kecamatan, Kelurahan).

Eksekusi seeder lengkap:
```bash
php artisan migrate:fresh --seed
```
