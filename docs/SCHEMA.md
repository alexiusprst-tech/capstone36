# Schema domain dan status

Baseline 20 November 2026. [DATABASE.md](DATABASE.md) menjelaskan penyimpanan; [API.md](API.md) menjelaskan transport. Schema di sini membedakan kolom, JSON tersimpan, presentasi API dan konsep target. Jangan menambahkan field dari browser ke `$fillable` tanpa validasi/kewenangan.

## Model aktif dan bentuk nilai

| Model/tabel | Bentuk penting |
| --- | --- |
| Customer/customers | profile array, version integer; kode server unik, snapshot customer pada order terpisah |
| Product/products | stock/min_stock integer; nilai harga/HPP pada model berupa angka; SKU/satuan master dan arsip soft delete |
| ProductCategory, ProductUnit, Supplier, Region, User | Master/kewenangan; tidak menyimpan status pembayaran order |
| SalesOrder/sales_orders | details/items/history/invoice cast array; submitted_at/approved_at datetime; subtotal/tax/grand_total integer rupiah |
| WarehouseRequest/warehouse_requests | items dan procurement_draft array, total_est_cost decimal:2; FK order dan hash rincian kebutuhan |
| PurchaseTransaction/purchase_transactions | items/supplier_progress/progress_history array, progress_version integer, pending_followup boolean, subtotal/discount/total_price decimal:2 |
| PurchaseReceipt dan PurchaseReceiptLine | Header retry+pelaku/tanggal, baris kuantitas accepted/rejected dan alasan; snapshot item sesuai PO |
| InventoryMovement/inventory_movements | Delta fisik, stok sebelum/sesudah, referensi serta pelaku; bukan alokasi virtual |
| SpjProject/spj_projects | items/metadata/director_approval/warehouse_data/finance_data array; nilai proyek/invoice disalin, cast float pada model aktif |
| SpjDocument/spj_documents | form_data/missing_requirements array; boolean external/complete; doc_order/version integer; file hasil dan sumber terpisah |
| DocumentTemplate dan OrderDocument | Master/hasil dokumen order, berbeda dari checklist proyek SPJ |

Daftar aktual berada di [app/Models](../backend/app/Models). `sales_order_allocations` dan `inventory_locks` dipakai service/query tanpa perlu model terpisah. Cast float aktif pada sejumlah model bukan izin memakai floating point browser untuk perhitungan final; gunakan normalisasi sen/decimal di service.

### Order tersimpan versus respons

`details` berisi identitas proyek/customer dan snapshot profil; `items` berisi SKU, nama, satuan, qty, price serta spesifikasi. Histori menyimpan peristiwa dan snapshot pelaku/perubahan. `submitted_by/at`, `approved_by/at`, `approval_notes`, status dan version adalah kolom server. Route binding menggunakan `order_number`.

`submittedBy`, `submittedById`, `submittedAt`, `approvedById`, `approvedBy`, `approvalDate`, `approvalNotes`, `timeline` dan `stockReadiness` adalah presentasi API, bukan seluruhnya nama kolom baru. `stockReadiness.items[].estimatedUnitCost` adalah HPP master positif atau null. “Siap Diajukan” berasal kesiapan seluruh alokasi, bukan status order. Nama pengaju dari snapshot kejadian aktual; timestamp tampil WIB, tanpa mengganti tanggal input order sebagai waktu pengajuan.

Draft aktif dapat menyimpan item dengan SKU yang belum ditemukan di katalog sebagai kebutuhan yang belum terhubung. Kesiapannya `catalogFound=false` dan tidak bisa submit sampai master/relasinya benar. MR/PO/receipt memerlukan katalog aktif. Kemampuan menyimpan Draft tersebut bukan alasan membuat stok atau SKU master otomatis.

### Pengajuan Gudang

`items[]`: SKU, snapshot nama/satuan, qty, est_price, est_discount per satuan dan nilai hasil hitung server. `procurement_draft` nullable menyimpan kode rencana `PB...`, transaction_date, invoice_number opsional, supplier_id beserta snapshot supplier, payment_method, subtotal, discount dan total. Bentuk respons lengkap ditetapkan controller, bukan input bebas.

`purchase_code` utama pada MR tetap null sampai PO Finance terbit. Harga/diskon/metode pada draft adalah rencana pembelian, bukan pembayaran customer. Finance memeriksa kembali seluruh kebutuhan dan supplier; tidak mempercayai total kiriman client.

### Pengadaan dan receipt

PO `items[]` menyimpan SKU/barang/unit/supplier/qty/harga_beli/diskon_nominal/diskon_percent/subtotal. `supplier_progress` berisi status perjalanan/kedatangan dan informasi kurir/resi/tindak lanjut; `progress_history` merekam versi, pelaku, waktu dan perubahan. Status teks supplier tidak identik dengan status PO atau order penjualan.

Receipt memuat `line_index`, `accepted_qty`, `rejected_qty`, `reason`. Summary sisa dihitung dari dipesan minus **diterima layak kumulatif**; rejected tetap memerlukan pengganti. UUID/hash retry disimpan di header receipt; server menentukan efek stok. Tidak ada schema retur stok sah/refund yang dapat disimpulkan dari field rejected.

## State aktif yang dapat dijadikan kontrak

| Domain | State/transisi aktif | Catatan |
| --- | --- | --- |
| SalesOrder | `Draft → Menunggu Verifikasi → Siap Invoice → Menunggu Pembayaran` | Cabang ≤Rp5 juta melompati Menunggu Verifikasi setelah submit; bukan bypass submit |
| SalesOrder legacy | Model mengenali `Selesai` | Tidak membuktikan ada endpoint lunas/dispatch/selesai aktif |
| MR | `MENUNGGU_FINANCE → DIPROSES_PEMBELIAN`; receipt dapat menjadi `DITERIMA_SEBAGIAN`, `PERLU_TINDAK_LANJUT` atau `SELESAI`; followup kebutuhan tersisa kembali `DIPROSES_PEMBELIAN`; `DIBATALKAN` sebelum PO | Status diperbarui service sesuai hasil QC, bukan hanya jalur lurus hingga selesai |
| PO | `DIPESAN → DITERIMA_SEBAGIAN` atau `PERLU_TINDAK_LANJUT → DIPESAN` setelah followup → `DITERIMA_GUDANG` | Penerimaan parsial dan pengganti berulang sampai seluruhnya layak |
| Supplier progress | Menunggu Konfirmasi, Dikonfirmasi Supplier, Dikirim ke Gudang CV, Diterima Sebagian, Ketidaksesuaian Fisik (Menunggu Retur), Barang Pengganti Dipesan/Dikirim, dan status yang diizinkan controller | State teks tracking bukan enum pembayaran/pengiriman customer |
| Invoice JSON | `Belum Dibayar`, paidAmount 0, paymentType Penuh dan terminCount 1 saat terbit | CAPS-25 menyimpan rincian customer, proyek, barang, subtotal, pajak, total dan metode pembayaran bersama nomor invoice. PDF invoice lama melengkapi rincian yang belum tersimpan dari pesanan asal yang terkunci. |
| SPJ per dokumen | Inisialisasi `BELUM_DIBUAT` atau `DRAFT`; penyimpanan form `DRAFT`, generate `FINAL`, upload `DOKUMEN_EKSTERNAL` | FINAL/file_path bukan Siap bundel pra-kirim |
| Pengiriman invoice | `pending → sent` atau `failed` per percobaan email | `sent` mencatat penerimaan oleh penyedia email. `channel` merekam kanal pengiriman. `default_recipient` (boolean, default false) menandai pengiriman ke tujuan uji tetap. Tautan harus cocok dengan pesanan, customer dan tujuan yang tercatat. Untuk pengiriman default, alamat uji itu harus tetap aktif. |

Status ini tidak digabung menjadi satu enum global. Order, pembelian, pembayaran, dokumen, bundel dan pengiriman punya fakta berbeda. UI memakai label manusia yang jelas tanpa mengubah nilai API diam-diam.

Komentar migrasi SPJ juga mencantumkan `DATA_BELUM_LENGKAP`, `MENUNGGU_PERSETUJUAN` dan `PERLU_REVISI`. Label legacy/cadangan tersebut tidak membuktikan tersedia transisi review/revisi formal pada service aktif. Kekurangan data dapat dikembalikan sebagai hasil validasi tanpa menulis semua label itu ke status dokumen.

## Konsep target Sprint 2 — belum field/enum/tabel final

- Pembayaran: invoice yang stabil, attempt instruksi tertunda/gagal/kedaluwarsa, hasil provider terverifikasi lunas. Paid tidak diturunkan oleh event lama. Token/signature provider tidak berasal isian UI.
- SPJ: fase persiapan metadata, antrean aktual setelah lunas, bundel pra-kirim belum siap/Siap serta kelengkapan susulan setelah penyerahan. Nama internal state final dan daftar wajib ditetapkan saat CAPS-24; bukan penambahan `VERIFIED/ARCHIVED` seolah sudah ada.
- Pengiriman customer: belum dikirim → dispatch sah → penerimaan terkonfirmasi, dengan bukti kurir/resi/waktu dan versi SPJ. Status pengiriman supplier tidak boleh menggerakkan state ini.
- Konten susulan: BAST bertanda tangan/foto penerimaan diberi fase pascapenyerahan, tidak menuntut `TERKIRIM` untuk seluruh bundel pra-kirim. Draft dan file bertanda tangan adalah bukti berbeda.

Tabel dan API target dirancang bersama sesuai provider dan keputusan cakupan. `source_order_number` proyek saat ini menunjuk satu sumber utama; order tambahan dengan instansi sama tidak otomatis teragregasi. Cakupan multi-order masih terbuka. Jangan menganggap nilai/default lama di proyek sebagai pembayaran/approval/pengiriman otoritatif.
