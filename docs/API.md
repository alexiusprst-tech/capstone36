# RESTful API Contracts Specification
## CV. Alphate Artha Jaya — Dokumentasi Rute & Kontrak API

Dokumentasi ini merinci seluruh endpoint RESTful API yang aktif pada `backend/routes/api.php`. Seluruh request dan response menggunakan format standar JSON.

---

## 1. Konvensi Umum API

* **Base URL**: `http://127.0.0.1:8000/api` (Development)
* **Headers Wajib**:
  * `Accept: application/json`
  * `Content-Type: application/json` (Kecuali upload multipart/form-data)
  * `Authorization: Bearer <token>` (Untuk endpoint yang terotentikasi via Sanctum)
* **Format Response Sukses**:
```json
{
  "status": "success",
  "message": "Operasi berhasil dieksekusi",
  "data": { ... }
}
```

---

## 2. Autentikasi Pengguna (`/api/auth`)

### 2.1 Login Pengguna
* **Endpoint**: `POST /api/auth/login`
* **Body Request**:
```json
{
  "email": "finance@example.com",
  "password": "password"
}
```
* **Response (200 OK)**:
```json
{
  "status": "success",
  "token": "1|laravel_sanctum_token...",
  "user": {
    "id": 3,
    "name": "Finance Staff",
    "email": "finance@example.com",
    "role": "FINANCE"
  }
}
```

### 2.2 Profil Pengguna Aktif
* **Endpoint**: `GET /api/auth/user` (Protected: `auth:sanctum`)
* **Response (200 OK)**: Data profil dan role akun aktif.

---

## 3. Modul Produk & Inventori (`/api/products`)

### 3.1 Ambil Katalog Barang (2.196 SKU)
* **Endpoint**: `GET /api/products`
* **Query Parameters**:
  * `page` (integer, default: 1)
  * `per_page` (integer, default: 10)
  * `search` (string, mencari pada SKU `A0000xxxx` atau Nama Barang)
  * `category` (string, filter nama kategori)
* **Response (200 OK)**:
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "sku": "A00000001",
        "name": "Semen Tiga Roda 50kg",
        "category": "Bahan Bangunan",
        "unit": "ZAK",
        "stock": 150,
        "last_buy_price": 68000,
        "sell_price": 75000,
        "status": "Tersedia"
      }
    ],
    "total": 2196,
    "per_page": 10
  }
}
```

### 3.2 Unduh Template Excel
* **Endpoint**: `GET /api/products/template`
* **Response (200 OK)**: File binary download `data_barang_template.xlsx`.

### 3.3 Impor Data Barang dari Excel
* **Endpoint**: `POST /api/products/import`
* **Content-Type**: `multipart/form-data`
* **Body Request**: `file` (File Excel, max 10MB)
* **Response (200 OK)**:
```json
{
  "status": "success",
  "message": "Berhasil memproses 2.196 data barang.",
  "data": { "imported_count": 2196, "errors": [] }
}
```

### 3.4 Kategori & Satuan Barang
* `GET /api/products/categories` — Daftar kategori produk aktif.
* `POST /api/products/categories` — Menambah kategori baru.
* `GET /api/products/units` — Daftar satuan terstandarisasi.
* `POST /api/products/units` — Menambah satuan baru.

---

## 4. Modul Supplier & Pengadaan

### 4.1 Daftar Supplier (`/api/suppliers`)
* **Endpoint**: `GET /api/suppliers`
* **Response (200 OK)**: Daftar rekanan supplier (`code`, `name`, `phone`, `email`, `address`).
* **Endpoint**: `POST /api/suppliers` — Menambah supplier baru.

### 4.2 Pengajuan Barang Gudang (`/api/warehouse/requests`)
* **Endpoint**: `GET /api/warehouse/requests`
  * Mengembalikan daftar *Material Request* gudang berstatus `MENUNGGU_FINANCE`, `DIPROSES_PEMBELIAN`, `SELESAI`.
* **Endpoint**: `POST /api/warehouse/requests`
  * **Body Request**:
```json
{
  "project_id": "PRJ-2026-001",
  "project_name": "Renovasi Gedung Arsip",
  "purpose": "Bahan Pondasi",
  "requested_by": "Staf Gudang",
  "items": [
    {
      "sku": "A00001770",
      "name": "Besi Beton Ulir 12mm",
      "qty": 50,
      "unit": "BATANG",
      "est_price": 115000,
      "subtotal": 5750000
    }
  ],
  "total_est_cost": 5750000
}
```

### 4.3 Laporan Ketidaksesuaian Barang Warehouse
* **Endpoint**: `POST /api/warehouse/discrepancies`
  * Menyimpan laporan pemeriksaan fisik Warehouse agar catatan dapat diakses lintas role dan sesi.
  * **Body Request**:
```json
{
  "order_number": "ORD-2026-001",
  "client_name": "SMA 9",
  "reason": "Barang yang diterima kurang 2 unit.",
  "items": [
    {
      "sku": "MAT-002",
      "name": "Besi Beton Ulir D16",
      "qty": 2,
      "unit": "Batang"
    }
  ],
  "reported_by": "Staf Warehouse"
}
```
* **Endpoint**: `GET /api/warehouse/discrepancies` — Daftar laporan untuk ditampilkan di halaman Finance; respons mencakup catatan, item, pelapor, status, dan waktu pelaporan.

### 4.4 Transaksi Pembelian Finance (`/api/purchases`)
* **Endpoint**: `GET /api/purchases`
  * Mengembalikan daftar transaksi pembelian supplier.
* **Endpoint**: `POST /api/purchases`
  * **Body Request**:
```json
{
  "transaction_date": "2026-10-14",
  "invoice_number": "INV-SUP-88912",
  "project_id": "PRJ-2026-001",
  "project_name": "Renovasi Gedung Arsip",
  "default_supplier": "PT. Baja Mandiri Perkasa",
  "warehouse_request_id": 1,
  "warehouse_request_code": "REQ-2026-001",
  "payment_method": "Transfer Bank",
  "items": [
    {
      "barang": "Besi Beton Ulir 12mm",
      "supplier": "PT. Baja Mandiri Perkasa",
      "qty": 50,
      "harga_beli": 112000,
      "diskon_percent": 2,
      "diskon_nominal": 112000,
      "subtotal": 5488000
    }
  ],
  "subtotal": 5600000,
  "discount": 112000,
  "total_price": 5488000
}
```
  * **Efek Sistem**:
    1. Membuat transaksi `TRX-YYYYMMDD-XXXX` berstatus `LUNAS`.
    2. Mengubah status `WarehouseRequest` terkait menjadi `DIPROSES_PEMBELIAN`.

### 4.5 Tindak Lanjut Kekurangan Barang Supplier
* **Laporan Warehouse**: `POST /api/purchases/{id}/supplier-discrepancies` dengan body `{"reason":"Jumlah yang diterima kurang 2 unit."}`. Laporan menyimpan status masalah dan alasan pada transaksi pembelian agar terlihat oleh Finance.
* **Endpoint**: `POST /api/purchases/{id}/supplier-follow-ups` (`{id}` dapat berupa ID transaksi atau kode pembelian)
* **Body Request**:
```json
{
  "action": "PENERIMAAN_ULANG",
  "notes": "Supplier diminta mengirim ulang 2 unit yang kurang.",
  "expected_date": "2026-11-05",
  "recorded_by": "Finance"
}
```
* `action` menerima `PEMESANAN_ULANG` atau `PENERIMAAN_ULANG`; `notes` wajib diisi dan `expected_date` bersifat opsional.
* **Efek Sistem**: Menambahkan catatan ke riwayat `supplier_follow_ups` pada transaksi dan memperbarui `supplier_status`. Pencatatan tindak lanjut tidak mengubah stok; Warehouse tetap melakukan pemeriksaan dan konfirmasi penerimaan fisik.
* **Pengiriman susulan Supplier**: `POST /api/purchases/{id}/supplier-replacement-dispatch` menyimpan status `Barang Pengganti Dikirim`, nomor surat jalan, aksi, dan catatan Supplier pada `discrepancy_resolution`. Warehouse menerima notifikasi berdasarkan data transaksi server.
* Pengiriman melalui formulir surat jalan Supplier memakai `POST /api/purchases/{id}/supplier-dispatch`. Jika status PO sedang `Menunggu Pemesanan Ulang` atau `Menunggu Penerimaan Ulang`, endpoint otomatis menyimpannya sebagai `Barang Pengganti Dikirim` agar dapat dihitung dan ditampilkan Warehouse.
* Warehouse mencatat penerimaan fisik barang susulan melalui `POST /api/purchases/{id}/supplier-replacement-receive` dengan `notes` dan `items_received` berisi SKU, nama, jumlah yang benar-benar diterima, serta satuan. Endpoint memperbarui stok hanya untuk jumlah susulan dan mengubah status Supplier menjadi `Barang Pengganti Diterima Gudang`; pengiriman yang sama tidak dapat diterima dua kali.

---

## 5. Modul SPJ Terintegrasi (`/api/spj`)

* `GET /api/spj/projects` — Daftar seluruh proyek pemantauan SPJ.
* `GET /api/spj/projects/{id}` — Detail metadata proyek SPJ.
* `GET /api/spj/projects/{id}/documents` — Checklist status 15 dokumen SPJ proyek.
* `POST /api/spj/projects/{id}/bulk-generate` — Generate otomatis seluruh berkas dokumen SPJ dari data transaksi.
* `POST /api/spj/projects/{id}/documents/{docKey}/upload-external` — Upload berkas scan BAST / Surat Jalan bertanda tangan.
