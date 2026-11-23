# API & Route Contract — Sistem Informasi Operasional CV Alpharo Artha Jaya

**Version:** 2.0  
**Baseline:** 21 November 2026 (s.d. CAPS-26)  
**Backend:** Laravel 13.x / PHP 8.3+  
**Frontend:** Vue 3 + Inertia.js v2 + Tailwind CSS v4  
**Database:** MariaDB / MySQL (SQLite terisolasi untuk pengujian)  
**Authentication:** Laravel Sanctum (Stateful Session + CSRF / Bearer Token)  
**Authorization:** `EnsureApiAccess` + `EnsurePageRole` + Controller-level RBAC & Ownership Checks

---

## 1. Purpose

Dokumen ini mendefinisikan kontrak lengkap rute halaman Inertia dan endpoint API internal untuk **Sistem Informasi Operasional CV Alpharo Artha Jaya**. Kontrak ini bersumber langsung dari implementasi aktif pada [routes/api.php](../backend/routes/api.php), [routes/web.php](../backend/routes/web.php), [config/pages.php](../backend/config/pages.php), [EnsureApiAccess.php](../backend/app/Http/Middleware/EnsureApiAccess.php), serta seluruh controller dan service domain terkait.

Ruang lingkup endpoint mencakup:
- Autentikasi sesi web & token Sanctum.
- Pengelolaan Master Data (Customer, Katalog Barang/Kategori/Satuan, Supplier, Wilayah DKI).
- Siklus Pesanan Penjualan (*Sales Order*), alokasi stok, dan persetujuan Direktur (`> Rp5.000.000`).
- Siklus Pengadaan Gudang–Finance–Supplier (*Material Request* → *Purchase Order* → *Receiving/QC* → *Follow-up & Replacement*).
- Penerbitan Invoice Finance (DOCX/PDF/Pratinjau) dan arsip dokumen.
- Pengelolaan Template & Dokumen SPJ (15 jenis dokumen).
- Manajemen Akun internal dan jejak audit/mutasi stok (*Stock Ledger*).

API internal memakai autentikasi sesuai peran. Login dan referensi wilayah tersedia untuk publik. Customer membuka invoice melalui rute web dengan token khusus yang dijelaskan di bagian 10. Bagian 19 mencatat kontrak Sprint 2 yang masih direncanakan.

---

## 2. General Conventions

### 2.1 Base URL
- **Development:** `http://localhost:8000` (API prefix `/api`).
- **Production:** mengikuti `APP_URL`.
- Kirim header `Accept: application/json` untuk seluruh request API; `Content-Type: application/json` untuk payload JSON; `multipart/form-data` untuk unggah berkas.

### 2.2 Authentication
- Seluruh endpoint API selain `POST /api/auth/login`, `GET /api/regions`, dan `GET /api/regions/tree` wajib melewati middleware `auth:sanctum`.
- Halaman web terproteksi wajib melewati middleware `auth` dan `EnsurePageRole`.
- **Unduhan dokumen & template** (`/api/orders/{order}/invoice/download`, `/api/documents/{id}/download`, `/api/document-templates/{id}/download-*`, `/api/spj/projects/{id}/documents/{docKey}/download`) tetap memerlukan autentikasi dan otorisasi peran.

### 2.3 Authorization Chain
Otorisasi **tidak boleh** hanya dilakukan di frontend. Backend wajib menjalankan rantai pemeriksaan:

```text
Authentication (auth / auth:sanctum)
  → Role Middleware (EnsurePageRole / EnsureApiAccess)
  → Controller Role & Ownership Check
  → Transactional Lock (inventory_locks / lockForUpdate) & Business Rule
```

### 2.4 Non-Uniform Response Envelope Notice
Respons API aktif **tidak seragam** di seluruh modul:
- **Orders & Customers:** umumnya `{ "data": ... }`
- **Products, Purchases, Suppliers, Documents, SPJ:** umumnya `{ "success": true, "message": "...", "data": ..., "meta": ... }`
- Gunakan kontrak masing-masing endpoint; jangan mengasumsikan semua respons memakai `{ "status": "success" }`.

---

## 3. Role Matrix

Lima peran internal (`INTERNAL`): `MARKETING`, `WAREHOUSE`, `FINANCE`, `ADMINISTRASI`, `DIREKTUR`.  
Dua peran eksternal: `SUPPLIER`, `CLIENT`.

| Modul | MARKETING | WAREHOUSE | FINANCE | ADMINISTRASI | DIREKTUR | SUPPLIER | CLIENT |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| Dashboard Role | ✓ | ✓ | ✓ | ✓ | ✓ | Portal | Portal Publik |
| Wilayah (`/api/regions`) | Publik | Publik | Publik | Publik | Publik | Publik | Publik |
| Customer (`/api/customers`) | CRUD | Read | Read | Read | Read | - | - |
| Katalog Barang, Kategori, Satuan | Read | CRUD + Import/Restore | Read | Read | Read | - | - |
| Stock Ledger (`/api/inventory/movements`) | Read | Read | Read | Read | Read | - | - |
| Sales Order — Draft & Submit | CRUD + Submit | Read | Read | Read | Read | - | - |
| Sales Order — Approval (`> Rp5jt`) | - | - | - | - | Approve | - | - |
| Sales Order — Terbitkan Invoice | - | - | Issue | - | - | - | - |
| Invoice Delivery & Revoke (`/invoice/delivery`) | - | - | Read + Send + Revoke | - | - | - | - |
| Tautan Invoice Customer (`/invoice-access/{token}`) | - | - | - | - | - | - | Publik bertoken (64-hex) |
| Invoice Download (PDF/DOCX/Preview) | Read | - | Read | Read | Read | - | Read (milik sendiri) |
| Supplier Master (`/api/suppliers`) | Read | Read | CRUD | Read | Read | Read (scope) | - |
| Material Request (`/api/warehouse/requests`) | Read | Create + Cancel | Read | Read | Read | - | - |
| Purchase Order (`/api/purchases`) | Read | Read + Receive | Create + Followup | Read | Read | Read (PO sendiri) | - |
| PO Progress (`/supplier-progress`) | - | Arrival / Discrepancy | Followup / Recovery | - | - | Confirm / Dispatch / Tracking / Replacement / Reject | - |
| Template Dokumen (`/api/document-templates`) | Read | Read | Read | Read + Upload + Toggle | Read | - | - |
| Arsip Dokumen (`/api/documents`) | Read + Write | Read | Read + Write | Read + Write | Read | - | - |
| SPJ (`/api/spj/*`) | Read | Read | Read | CRUD + Generate + Upload | Read | - | - |
| Manajemen Akun (`/api/admin/accounts`) | - | - | - | Manage (controller-scoped) | - | - | - |

---

## 4. HTTP Methods & Action Conventions

```text
GET    → Read / Halaman Inertia / Download
POST   → Create / Workflow Action / Import / Upload
PUT    → Full Update / Idempotent Upsert (Order Draft)
DELETE → Delete / Soft Archive
```

Seluruh aksi transisi workflow menggunakan `POST /{resource}/{id}/{action}`, misalnya:
- `POST /api/orders/{order}/stock-check`
- `POST /api/orders/{order}/submit`
- `POST /api/orders/{order}/approve`
- `POST /api/orders/{order}/invoice`
- `POST /api/products/{id}/restore`
- `POST /api/warehouse/requests/{code}/cancel`
- `POST /api/purchases/{id}/receive`
- `POST /api/purchases/{code}/supplier-progress`

---

## 5. Standard HTTP Status Codes

| Code | Meaning | Penggunaan di Sistem |
| :---: | :--- | :--- |
| `200` | OK | Read, update, transisi workflow, unduh berkas berhasil |
| `201` | Created | Resource baru berhasil dibuat (customer, product, PO, MR, dokumen) |
| `302` | Found | Redirect login/logout/route alias web Inertia |
| `401` | Unauthenticated | Sesi habis atau token Sanctum tidak valid / belum login |
| `403` | Forbidden | Peran tidak berhak atau akses objek milik pihak lain (`CLIENT`/`SUPPLIER`) |
| `404` | Not Found | Resource, nomor order, invoice, atau berkas fisik tidak ditemukan |
| `409` | Conflict | Konflik versi (*optimistic lock*), transisi status tidak valid, duplikasi invoice/PO/MR, SKU terarsip, stok di bawah alokasi aktif, retry UUID dengan payload berbeda, hapus master yang masih direferensikan transaksi |
| `422` | Unprocessable Entity | Validasi input gagal, stok belum siap saat submit/approve/invoice, satuan tidak sesuai master, jatuh tempo sebelum tanggal invoice, pilihan `Termin` pada versi pembayaran penuh |
| `429` | Too Many Requests | Rate limit login (`throttle:10,1`) terlampaui |
| `500` | Internal Server Error | Kegagalan tak terduga server (detail hanya dicatat ke log Laravel, bukan ke UI) |
| `503` | Service Unavailable | Layanan eksternal/konverter/database sementara tidak tersedia |

---

## 6. Standard Validation & Error Response

Validasi gagal mengembalikan `HTTP 422`:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "dueDate": [
      "The due date field must be a date after or equal to invoice date."
    ]
  }
}
```

Untuk request unduhan (`responseType: 'blob'`), frontend wajib mem-parse body JSON error dari `Blob` agar pesan error server tetap tampil utuh ke pengguna (bukan sekadar `"Request failed with status code ..."`).

---

## 7. Authentication & Public Reference

### 7.1 Web Session (Inertia)
- `GET /` → redirect ke home sesuai role (`config('pages.homes')`) atau `/login`.
- `GET /login` → halaman login publik.
- `POST /login` → autentikasi sesi (`throttle:10,1`), redirect `302` ke dashboard role.
- `POST /logout` → keluar sesi (`auth`), redirect `302` ke `/login`.
- `GET /templates/{filename}` → unduh template Excel data barang (`data_barang_template (2).xlsx`).

### 7.2 API Auth (Sanctum)
- `POST /api/auth/login` — Publik, `throttle:10,1`. Request: `email`, `password`.
- `GET /api/auth/user` — Terautentikasi. Mengembalikan profil user aktif.
- `POST /api/auth/logout` — Terautentikasi. Mencabut token akses aktif.

### 7.3 Wilayah Referensi (Publik)
- `GET /api/regions` — Daftar wilayah administratif DKI Jakarta.
- `GET /api/regions/tree` — Hirarki Provinsi → Kota → Kecamatan → Kelurahan + Kode Pos.

---

## 8. Customer Management (`CAPS-11`)

Prefix: `/api/customers` — Read: `INTERNAL`, Write: `MARKETING`.

| Method | Endpoint | Kontrak & Aturan |
| :--- | :--- | :--- |
| `GET` | `/api/customers` | Daftar customer server; query `search` opsional |
| `GET` | `/api/customers/{identifier}` | Detail berdasarkan ID atau kode (`CS00001`), menyertakan `version` |
| `POST` | `/api/customers` | Buat customer; wajib `name`, `contactPerson`, `phone` (7–15 digit), `address`; ID, kode, dan `version` awal dibuat server |
| `PUT` | `/api/customers/{identifier}` | Update customer; `version` wajib (*optimistic locking*, mismatch → `409`) |
| `DELETE` | `/api/customers/{identifier}` | Hapus customer; ditolak `409` jika masih direferensikan `sales_orders` |

---

## 9. Katalog Barang, Kategori, Satuan & Stock Ledger (`CAPS-10`)

Read: `INTERNAL`, Write: `WAREHOUSE`.

| Method | Endpoint | Kontrak & Aturan |
| :--- | :--- | :--- |
| `GET` | `/api/products` | Daftar barang aktif dengan `allocated_stock` & `available_stock`; query: `search`, `category`, `unit`, `status`, `sort_by` (whitelisted), `sort_dir`, `per_page` |
| `GET` | `/api/products/{id}` | Detail barang |
| `POST` | `/api/products` | Tambah barang baru; SKU unik (termasuk arsip → `409`); status dihitung server (`Habis` / `Menipis` / `Tersedia`); mencatat `OPENING` di stock ledger |
| `PUT` | `/api/products/{id}` | Update barang; SKU permanen (`409` jika diubah); stok tidak boleh `< allocated_stock` (`409`); satuan terkunci selama ada alokasi/pengadaan aktif (`409`) |
| `DELETE` | `/api/products/{id}` | Soft-delete (arsip); ditolak `409` jika ada alokasi aktif atau pengadaan berjalan |
| `POST` | `/api/products/{id}/restore` | Memulihkan barang terarsip |
| `GET` | `/api/products/template` | Unduh template resmi `data_barang_template (2).xlsx` |
| `POST` | `/api/products/import` | Impor massal JSON `items[]` (1–10.000 baris, parsed di frontend), `duplicate_mode`: `update\|skip\|auto_id`. Mode `update` menolak `409` jika SKU terarsip, stok `< alokasi aktif`, atau ubah satuan saat ada alokasi/pengadaan aktif |
| `GET/POST/PUT/DELETE` | `/api/products/categories` (`/{id}`) | Master kategori barang |
| `GET/POST/PUT/DELETE` | `/api/products/units` (`/{id}`) | Master satuan barang (perubahan satuan terpakai alokasi/pengadaan → `409`) |
| `GET` | `/api/inventory/movements` | Stock ledger read-only; filter `search`, `type`, `product_id`, paginasi maks 100 |

---

## 10. Sales Orders, Approval & Invoice (`CAPS-12`, `CAPS-15`, `CAPS-16`, `CAPS-23`, `CAPS-25`)

| Method | Endpoint | Role | Kontrak & Aturan |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/orders` | `INTERNAL` | Daftar order + `stockReadiness` (termasuk `estimatedUnitCost` nullable), `procurement`, `timeline`, `invoice` |
| `PUT` | `/api/orders/{number}` | `MARKETING` | Buat (`version: 0`) atau ubah Draft (`version` terkini, mismatch → `409`). Hanya status `Draft` (`409` jika sudah diajukan). SKU/qty terkunci selama ada PO aktif (`409`). Total + PPN 11% dihitung server dalam sen lalu dibulatkan sekali ke rupiah. Harga Rp0 diterima. Menyinkronkan proyek SPJ |
| `DELETE` | `/api/orders/{order}` | `MARKETING` | Hapus Draft tanpa riwayat MR (`409` jika sudah diajukan atau punya MR); melepaskan alokasi & realokasi Draft lain |
| `POST` | `/api/orders/{order}/stock-check` | `MARKETING` | Hitung ulang alokasi stok Draft (`409` jika bukan Draft) |
| `POST` | `/api/orders/{order}/submit` | `MARKETING` | Wajib `{ "version": <int> }`. Seluruh item harus terdaftar & teralokasi penuh (`422` jika belum siap). `grand_total <= 5.000.000` → `Siap Invoice`; `grand_total > 5.000.000` → `Menunggu Verifikasi` |
| `POST` | `/api/orders/{order}/approve` | `DIREKTUR` | Hanya status `Menunggu Verifikasi` & `grand_total > 5.000.000` (`409` jika tidak). `notes` opsional (maks 2.000 karakter). Memeriksa ulang stok (`422` jika stok fisik turun). Hasil: `Siap Invoice` |
| `POST` | `/api/orders/{order}/invoice` | `FINANCE` | Hanya status `Siap Invoice` + `isApprovedForInvoice()` + belum punya invoice (`409` jika duplikat/belum disetujui). Payload: `invoiceDate` (`Y-m-d`), `dueDate` (`>= invoiceDate`), opsional `paymentType: "Penuh"`, `terminCount: 1` (nilai lain → `422`). Nomor unik `INV-<tahun>-S<id>`, metode `["Virtual Account", "QRIS"]`, status order → `Menunggu Pembayaran`. Bila `INVOICE_USE_DEFAULT_RECIPIENT=true` pada `mailtrap_demo`, server otomatis mencoba pengiriman email pertama dan mengembalikan ringkasan `delivery: { sent, recipient }` tanpa membatalkan invoice bila pengiriman gagal |
| `GET` | `/api/orders/{order}/invoice/download` | `FINANCE`, `MARKETING`, `DIREKTUR`, `ADMINISTRASI`, `CLIENT` (milik `client_id` sendiri) | Query `format=pdf\|docx` (default `pdf`), `preview=1` (inline disposition). Menggunakan master `Kuitansi-TEMPLATE.docx`. Konversi PDF mencoba `soffice` / Word COM (Windows lokal) lalu fallback ke **Dompdf** (`resources/views/invoice/kuitansi.blade.php`) dengan kop A4, watermark, kotak jajar genjang kuning, dan footer identik dengan Word. Tidak memutasi status pembayaran |
| `GET` | `/api/orders/{order}/invoice/delivery` | `FINANCE` | Status pengiriman email invoice (`recipient`, `defaultRecipient`, `channel`, `latest: { status, channel, recipient, createdAt }`, `active`, `expiresAt`) |
| `POST` | `/api/orders/{order}/invoice/delivery` | `FINANCE` | Kirim / kirim ulang email tautan invoice ke email customer terdaftar (atau `default_recipient` uji bila aktif). Mengabaikan override `recipient` dari body request. Menolak `409` bila invoice belum terbit atau pengiriman sedang `pending`, `422` bila email customer belum valid, `503` bila transport gagal (tautan lama yang aktif tetap dipertahankan sampai pengiriman pengganti berhasil) |
| `DELETE` | `/api/orders/{order}/invoice/delivery` | `FINANCE` | Mencabut (`revoked_at`) seluruh tautan akses invoice aktif untuk pesanan tersebut |
| `GET` | `/invoice-access/{token}` | Publik bertoken (tanpa sesi `web`) | Halaman HTML invoice terbatas (`CustomerInvoiceController`) dengan token 64-hex (dicocokkan terhadap `token_hash` SHA-256 di `invoice_deliveries`). Memvalidasi status `sent`, belum dicabut, belum kedaluwarsa, kecocokan `customer_id`, `invoice_number`, dan email penerima aktif. Mengembalikan `404` + `Cache-Control: private, no-store`, `Referrer-Policy: no-referrer`, `X-Robots-Tag: noindex, nofollow` bila tidak valid |

---

### Pengiriman invoice kepada customer (CAPS-26)

Dengan `INVOICE_MAILER=mailtrap_demo` dan `INVOICE_USE_DEFAULT_RECIPIENT=true`, penerbitan melalui `POST /api/orders/{order}/invoice` otomatis mengirim pemberitahuan ke `MAILTRAP_DEMO_RECIPIENT`. Respons menyertakan `delivery.sent` dan `delivery.recipient` di samping `data`. Kegagalan pengiriman mempertahankan invoice yang sudah diterbitkan. Finance dapat mencoba kembali melalui rute pengiriman pada tabel. Kirim ulang menggunakan tujuan yang sama, termasuk saat email customer kosong.

Dengan opsi default nonaktif, pengiriman dilakukan oleh Finance ke alamat customer. Dalam mode demo, alamat yang cocok dengan penerima demo memakai Mailtrap Email Sending dan alamat lain memakai Sandbox. Ringkasan menyajikan `defaultRecipient`, `channel` tujuan berikutnya dan `latest.channel` percobaan terakhir. Status `sent` berarti penyedia menerima pesan.

| Metode | Rute | Akses dan hasil |
| --- | --- | --- |
| GET | `/api/orders/{order}/invoice/delivery` | Finance membaca alamat tujuan efektif, status upaya terakhir, keaktifan dan kedaluwarsa tautan. |
| POST | `/api/orders/{order}/invoice/delivery` | Finance mengirim atau mengirim ulang invoice yang sudah terbit. Server memilih penerima. Pengiriman yang diterima penyedia email menghasilkan tautan baru dan menggantikan tautan sebelumnya. |
| DELETE | `/api/orders/{order}/invoice/delivery` | Finance mencabut akses customer untuk pesanan itu. Riwayat pengiriman tetap tercatat. |
| GET | `/invoice-access/{token}` | Customer membuka satu invoice melalui email tanpa login. Token tidak berlaku, sudah dicabut, kedaluwarsa, atau penerima yang berubah menghasilkan respons 404 yang sama. |

Ringkasan Finance ada dalam `data` dengan field `recipient`, `latest` (status, recipient, createdAt), `active`, dan `expiresAt`. Tautan hanya dikirim lewat email. Finance mendapat 409 saat invoice belum terbit atau pengiriman masih berjalan, 422 saat kontak belum valid, dan 503 saat penyedia email gagal. Gagal kirim ulang mempertahankan tautan sebelumnya yang masih sah. Pengiriman dan pencabutan mempertahankan nilai serta status pembayaran.

## 11. Supplier Master

Prefix: `/api/suppliers` — Read: `INTERNAL` + `SUPPLIER` (scoped), Write: `FINANCE`.
- `GET /api/suppliers`, `GET /api/suppliers/{id}`
- `POST /api/suppliers`, `PUT /api/suppliers/{id}`, `DELETE /api/suppliers/{id}`

---

## 12. Pengadaan: Material Request, Purchase Order & Penerimaan (`CAPS-13`, `CAPS-14`, `CAPS-17`, `CAPS-18`)

| Method | Endpoint | Role | Kontrak & Aturan |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/warehouse/requests` | `INTERNAL` | Daftar MR beserta referensi order, estimasi draft `PB...`, dan PO terkait |
| `POST` | `/api/warehouse/requests` | `WAREHOUSE` | Buat MR dari order Draft dengan kekurangan nyata (`order_number` atau `sales_order_id`, `purpose`, `items[]`, opsional `procurement_draft`). Qty dibatasi maksimal sisa kekurangan; estimasi dihitung server |
| `POST` | `/api/warehouse/requests/{code}/cancel` | `WAREHOUSE` | Batalkan MR yang belum memiliki PO (`409` jika sudah punya PO atau sudah dibatalkan) |
| `GET` | `/api/purchases` | `INTERNAL`, `SUPPLIER` (PO sendiri) | Daftar PO beserta ringkasan penerimaan, progres supplier, dan riwayat receipt |
| `POST` | `/api/purchases` | `FINANCE` | Terbitkan 1 PO dari 1 MR valid (`warehouse_request_id`/`warehouse_request_code`, supplier aktif tunggal, `transaction_date`, `payment_method`, `items[]`). Status awal `DIPESAN`; **tidak** menambah stok fisik |
| `POST` | `/api/purchases/{id}/receive` | `WAREHOUSE` | Penerimaan per baris: `version`, `idempotency_key` (UUID), `received_date`, `items[]` (`line_index`, `accepted_qty`, `rejected_qty`, `reason` wajib jika ada penolakan). Retry UUID + payload sama → idempoten (`replayed: true`); payload beda → `409`. Hanya `accepted_qty` menambah stok & ledger |
| `POST` | `/api/purchases/{code}/supplier-progress` | `WAREHOUSE` (`arrival`, `discrepancy`), `FINANCE` (`followup`, `recover`, aksi PO), `SUPPLIER` (`confirm`, `dispatch`, `tracking`, `replacement`, `reject`) | Payload: `action`, `version` (`progress_version`), `progress`. Penolakan QC mengaktifkan `pending_followup=true`; `followup` wajib mencatat `followup_reference` & `followup_notes` oleh Finance sebelum `replacement` diizinkan (`409`) |

---

## 13. Dokumen Template, Arsip Dokumen & Akun Internal

| Method | Endpoint | Role | Kontrak & Aturan |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/document-templates`, `/{id}` | `INTERNAL` | Katalog master template dokumen |
| `GET` | `/api/document-templates/{id}/download-blank`, `/download-master` | `INTERNAL` | Unduh template kosong/master `.docx` |
| `POST` | `/api/document-templates/{id}/upload` | `ADMINISTRASI` | Unggah/perbarui file master `.docx` (maks 10MB) |
| `POST` | `/api/document-templates/{id}/toggle-status` | `ADMINISTRASI` | Aktifkan/nonaktifkan template (`DocumentTemplateController::toggleStatus`) |
| `GET` | `/api/documents`, `/{id}/download` | `INTERNAL` | Daftar & unduh arsip dokumen order |
| `POST` | `/api/documents/generate` | `ADMINISTRASI`, `MARKETING`, `FINANCE` | Generate arsip dari template; kategori `FAKTUR`/`INVOICE` memverifikasi `isApprovedForInvoice()` (`409`). Tidak membuat `sales_orders.invoice` |
| `POST` | `/api/documents/upload-scan` | `ADMINISTRASI`, `MARKETING`, `FINANCE` | Unggah scan basah (`pdf,docx,jpg,jpeg,png`, maks 20MB) |
| `DELETE` | `/api/documents/{id}` | `ADMINISTRASI`, `MARKETING`, `FINANCE` | Hapus arsip dokumen |
| `GET`, `POST` | `/api/admin/accounts` | `ADMINISTRASI` (controller-scoped) | Daftar & pembuatan akun internal (bukan registrasi publik) |

---

## 14. SPJ Aktif (`/api/spj/*`)

Read: `INTERNAL`, Write: `ADMINISTRASI`.

| Method | Endpoint | Perilaku Aktif & Catatan Batas |
| :--- | :--- | :--- |
| `GET` | `/api/spj/projects`, `/{id}` | Daftar & detail proyek SPJ (belum difilter khusus antrean lunas → target CAPS-24) |
| `GET` | `/api/spj/projects/{id}/documents`, `/{docKey}` | Checklist & detail 15 jenis dokumen SPJ |
| `PUT` | `/api/spj/projects/{id}` | Update metadata administratif proyek |
| `PUT` | `/api/spj/projects/{id}/documents/{docKey}` | Simpan form isian dokumen |
| `POST` | `/api/spj/projects/{id}/documents/{docKey}/generate`, `/bulk-generate` | Generate dokumen `.docx`/`.pdf` per dokumen |
| `GET` | `/api/spj/projects/{id}/documents/{docKey}/download` | `format=docx\|pdf\|original` (target CAPS-24: jadikan read-only murni tanpa mutasi) |
| `POST` | `/api/spj/projects/{id}/documents/{docKey}/upload-external` | Simpan berkas eksternal/susulan terpisah dari hasil generate |

---

## 15. Status Transition Contracts

### 15.1 Sales Order (s.d. CAPS-25)
```text
Draft
  ├─ (grand_total <= Rp5.000.000 & stok siap) ──▶ Siap Invoice
  └─ (grand_total >  Rp5.000.000 & stok siap) ──▶ Menunggu Verifikasi
                                                      │ (approve Direktur)
                                                      ▼
                                                  Siap Invoice
                                                      │ (issue invoice Finance)
                                                      ▼
                                                Menunggu Pembayaran
```
Transisi tidak sah atau aksi berulang wajib ditolak dengan `409 Conflict`.

### 15.2 Purchase Order
```text
DIPESAN ──▶ DITERIMA_SEBAGIAN / PERLU_TINDAK_LANJUT ──▶ DITERIMA_GUDANG
```
Jika terdapat barang ditolak saat QC (`rejected_qty > 0`), `pending_followup = true` mengunci pengiriman pengganti hingga Finance mencatat tindak lanjut (`followup`).

---

## 16. Concurrency, Transactions & Idempotency

- Seluruh operasi mutasi stok, alokasi, MR, PO, penerimaan, tindak lanjut, dan transisi order wajib berjalan dalam `InventoryTransaction::run(...)` dengan kunci mutex `inventory_locks.id = 1` + `lockForUpdate()` dan retry deadlock.
- **Optimistic Locking (`version`):** `PUT /api/customers/{id}`, `PUT /api/orders/{number}`, `POST /api/orders/{order}/submit`, `POST /api/purchases/{id}/receive`, `POST /api/purchases/{code}/supplier-progress`.
- **Idempotency (`idempotency_key` UUID):** `POST /api/purchases/{id}/receive` mencegah penambahan stok ganda saat retry jaringan.

---

## 17. Security, Upload/Download & Backend Source of Truth

1. **Backend Source of Truth:** Frontend **tidak boleh** menentukan nilai otoritatif: `role`, `created_by`, `submitted_by`, `approved_by`, `approved_at`, `version`, `subtotal`, `tax`, `grand_total`, `invoiceNumber`, `status` barang/order/PO, `allocated_stock`, atau `available_stock`.
2. **File Upload Security:** Validasi ekstensi, MIME type, dan ukuran berkas; nama/path penyimpanan ditentukan server di `storage/app/`; berkas sensitif tidak diekspos tanpa autentikasi.
3. **Sorting & Pagination Whitelist:** Seluruh parameter `sort_by`/`sort_dir` wajib menggunakan whitelist kolom; `per_page` dibatasi sesuai kontrak endpoint.
4. **Database Error Translation:** Pelanggaran constraint unik/FK/konflik bisnis diterjemahkan menjadi `409` atau `422` yang dapat dipahami pengguna — dilarang membocorkan query SQL, stack trace, atau kredensial.

---

## 18. Inertia Role-Specific Page Routes (`config/pages.php`)

- **Publik:** `/login`, `/portal` (alias: `/company-profile`, `/tracking`, `/client/*`)
- **MARKETING:** `/marketing/dashboard`, `/marketing/clients`, `/marketing/clients/create`, `/marketing/clients/{id}/edit`, `/marketing/orders`, `/marketing/orders/create`, `/marketing/orders/{id}/edit`, `/marketing/notifications`, `/marketing/profile`
- **DIREKTUR:** `/director/dashboard`, `/director/verification`, `/director/monitoring`, `/director/reports`, `/director/notifications`, `/director/profile`
- **ADMINISTRASI:** `/administration/dashboard`, `/administration/spj`, `/administration/accounts`, `/administration/notifications`, `/administration/profile`
- **WAREHOUSE:** `/warehouse/dashboard`, `/warehouse/products`, `/warehouse/inventory`, `/warehouse/fulfillment`, `/warehouse/shipments`, `/warehouse/delivery-notes`, `/warehouse/notifications`, `/warehouse/profile`
- **FINANCE:** `/finance/dashboard`, `/finance/suppliers`, `/finance/purchases`, `/finance/purchases/create`, `/finance/invoices`, `/finance/payments`, `/finance/monitoring`, `/finance/reports`, `/finance/notifications`, `/finance/profile`
- **SUPPLIER:** `/supplier/portal`, `/supplier/notifications`

---

## 19. Kontrak Target Sprint 2 (Belum Menjadi Endpoint Aktif)

| Jira | Kemampuan Target | Prasyarat & Jaminan Sebelum Implementasi |
| :--- | :--- | :--- |
| **CAPS-28** | Instruksi VA/QRIS | Satu provider sandbox resmi, referensi attempt unik, nominal/expiry dari server |
| **CAPS-31 / 33** | Verifikasi callback & penanganan belum lunas | Verifikasi signature provider, deduplikasi event, korelasi nominal/status atomik, tidak downgrade status lunas |
| **CAPS-29** | Konfirmasi pelunasan & email | Idempoten pasca-commit; gagal email tidak rollback pembayaran |
| **CAPS-24** | Kesiapan SPJ pra-kirim | Antrean khusus pesanan lunas, pemisahan bundel pra-kirim vs susulan (signed BAST/foto terima), download read-only |
| **CAPS-30** | Dispatch & pengurangan stok fisik | Gerbang server wajib `lunas + SPJ Siap`, mutasi stok & pelepasan alokasi atomik sekali |
| **CAPS-32** | Tracking & konfirmasi terima customer | Tautan terbatas, konfirmasi idempoten pasca-dispatch |

---

## 20. Implementation Rule & Definition of Done

Setiap penambahan atau perubahan endpoint wajib memenuhi:
1. Selaras dengan [PRD.md](PRD.md), [DATABASE.md](DATABASE.md), [SCHEMA.md](SCHEMA.md), [ARCHITECTURE.md](ARCHITECTURE.md), [RULES.md](RULES.md), dan [DESIGN.md](DESIGN.md).
2. Route terdaftar dengan autentikasi (`auth` / `auth:sanctum`) dan otorisasi berlapis (`EnsureApiAccess` + controller/ownership check).
3. Validasi input lengkap (`422`), aturan bisnis + locking transaksional (`409`), dan error handling tanpa kebocoran internal (`500`/`503`).
4. Audit/ledger/history dicatat oleh server untuk mutasi transaksi.
5. Terhubung ke halaman Inertia/Vue dengan loading state, success feedback, dan error state yang jelas.
6. Memiliki feature test PHPUnit (serta unit/UI test bila relevan) yang lulus penuh.
7. Dokumen `docs/API.md` ini diperbarui setiap kali kontrak endpoint berubah.
