# Kontrak aktual dan progres Sprint 1

19 November 2026. Acuan utama: Jira CAPS, sprint 4 **Sprint 1 - Epic 1-3**.

Target Sprint 1 adalah pesanan tercatat dan stok terpenuhi sampai **Siap Diajukan**. Dokumen ini menjadi kontrak implementasi Sprint 1 yang diuji untuk customer, pesanan, stok, pengadaan dan penerimaan. Approval/invoice/pembayaran/SPJ final/pengiriman merupakan Sprint 2.

## Customer — CAPS-8, CAPS-11, CAPS-12

Customer disimpan pada tabel `customers`. Pesanan baru mempunyai foreign key `sales_orders.customer_id`. Nama dan kode customer pada transaksi diambil dari server; snapshot identitas transaksi tidak ikut berubah ketika master customer diedit.

| Metode | Endpoint | Akses | Hasil |
|---|---|---|---|
| GET | `/api/customers` | Lima role internal | Daftar customer server; `search` opsional |
| GET | `/api/customers/{id atau kode}` | Lima role internal | Customer beserta `version` |
| POST | `/api/customers` | Marketing | Customer baru, kode dan ID dari server |
| PUT | `/api/customers/{id atau kode}` | Marketing | Ubah master dengan `version` terbaru |
| DELETE | `/api/customers/{id atau kode}` | Marketing | Dihapus jika belum dipakai transaksi |

Field wajib: `name`, `contactPerson`, `phone`, `address`. Nomor telepon berisi 7–15 digit; email dan website opsional harus valid jika diisi. Nama resmi `orgFullName`, alamat kop surat, wilayah, dan identitas instansi lainnya dapat disimpan. ID, kode, pembuat, dan versi awal tidak dapat ditentukan browser.

Respons customer memakai nama field yang sama dengan form Vue, termasuk `contactPerson`, `orgFullName`, `version`, dan `serverManaged: true`. PUT memerlukan `version`; perubahan bersamaan menghasilkan 409 tanpa menimpa data terbaru. Hapus customer yang masih dipakai menghasilkan 409. Pengguna tanpa autentikasi mendapat 401; role yang tidak berhak mendapat 403.

Form dan daftar menggunakan hasil API. Kegagalan API mempertahankan form dan menampilkan error. Data kosong tidak diganti customer contoh. Catatan aktivitas browser belum merupakan audit trail server lintas perangkat.

## Pesanan dan stok

`PUT /api/orders/{nomor}` memerlukan customer yang benar-benar tersimpan pada `details.clientId`, minimal satu barang, kuantitas bilangan bulat positif, dan harga tidak negatif. Harga Rp0 diterima sesuai acceptance criteria CAPS-12. Total dihitung server; browser tidak menentukan total final atau status approval.

Stok bebas = stok fisik layak − total alokasi aktif. Setiap pesanan hanya dapat menggunakan alokasinya sendiri. Siap Diajukan memerlukan semua barang terdaftar dan seluruh jumlah kebutuhan teralokasi. Edit/hapus draft melepaskan atau menyesuaikan alokasi. Pengeluaran fisik saat pengiriman merupakan pekerjaan Sprint 2.

Pesanan yang telah tersimpan tetap mempunyai snapshot customer. Customer master tidak dapat dihapus bila masih direferensikan transaksi. Pesanan lama tanpa hubungan customer server dipertahankan; ID lokal lama tidak ditebak sebagai ID server. Saat mengedit pesanan lama, pilih customer yang sudah terdaftar pada server.

## Penanganan data customer dari browser lama

- Record lokal lama dipertahankan pada salinan `alpharo_customers_legacy_v1`, bukan otomatis dimasukkan sebagai master server.
- Halaman Data Customer menyediakan **Unduh salinan customer lama**. Tinjau identitas, kontak, dan alamat sebelum mendaftarkannya lewat form server.
- ID lokal dapat bertabrakan antarperangkat. Karena itu, migrasi tidak menghubungkan transaksi berdasarkan ID lokal atau kecocokan nama sebagian.
- Jika penyimpanan perangkat penuh dan backup baru gagal, aplikasi mempertahankan storage lama dan menyediakan unduhan dari salinan tersebut.
- Master customer baru dimuat dari API setelah autentikasi; cache customer tidak dipakai sebagai identitas transaksi.

Migrasi `2026_11_19_093844_create_customers_and_link_sales_orders.php` menambah tabel customer dan kolom nullable pada pesanan. Data pesanan lama tidak diubah atau dihapus. Rollback migrasi akan menghapus tabel/kolom yang ditambahkan dan karenanya tidak dijalankan otomatis setelah modul dipakai.

## Bukti uji dan batas progres

- `CustomerApiTest`: autentikasi, role, field wajib, kontak, stale update, referensi transaksi, harga/kuantitas, identitas server, dan perlindungan ID lama.
- `customer-persistence.spec.js`: dua sesi browser, create/edit, pemilihan pada pesanan, order dengan stok siap, penolakan hapus customer terpakai, error simpan, serta unduhan data lama. Alur utama menggunakan API Laravel nyata pada SQLite pengujian.
- `SalesOrderApiTest`, `InventoryAuditTest`, dan `SpjGenerationTest` tetap memeriksa regresi transaksi terkait. Fixture kini membuat record customer server.
- `scripts/ui-test-server.mjs` menyiapkan database UI terpisah; seeder UI menolak berjalan di luar environment testing + SQLite. Jangan menjalankan reset database pengujian pada database operasional.

Catatan audit awal sebelum penyelesaian (ditutup oleh implementasi di bawah): referensi pesanan dan deduplikasi pengajuan (CAPS-13), PO wajib berdasar pengajuan (CAPS-14), penerimaan parsial layak/tidak sesuai per item (CAPS-17), tindak lanjut Finance tanpa akun wajib Supplier (CAPS-18), ledger stok (CAPS-10), riwayat delta pesanan (CAPS-15), serta review/integrasi CAPS-19–22. Review anggota lain dan merge belum digantikan oleh kelulusan tes otomatis.


## Kontrak penyelesaian Sprint 1 — CAPS-10, CAPS-13–22

Acuan aturan adalah Jira Sprint 1 dan BPMN TO-BE. Tanggal kalender bisnis memakai Asia/Jakarta (BUSINESS_TIMEZONE); penyimpanan timestamp tetap UTC agar waktu historis tidak ditafsirkan ulang. Semua penulisan stok, alokasi, MR, PO, penerimaan, dan tindak lanjut mengambil mutex database inventory_locks.id=1 terlebih dahulu dalam satu transaksi dengan retry deadlock. Pengujian MariaDB nyata memeriksa persaingan dua order, MR/PO ganda dan dua penerimaan serentak.

### Alokasi dan jejak stok

- Stok fisik layak tidak berkurang ketika order dialokasikan. Stok tersedia = fisik − seluruh alokasi aktif. Kekurangan order = kebutuhan − alokasinya sendiri.
- Order Draft kosong atau dengan SKU belum dikenal tidak siap. Edit jumlah/SKU menghitung ulang alokasi; pesanan yang telah diajukan tetap mempertahankan alokasinya.
- Penerimaan mendahulukan order asal, lalu order Draft lain menurut ID. Perubahan stok barang memperbarui seluruh alokasi Draft. Tidak ada stok negatif atau alokasi melebihi fisik.
- inventory_movements menyimpan SKU/nama snapshot, delta, saldo sebelum/sesudah, petugas, waktu, jenis dan referensi. Alokasi bukan mutasi fisik. Saldo awal migrasi menjelaskan kondisi awal ledger tanpa mengubah stok lama.
- SKU permanen. Penghapusan barang berupa arsip; arsip dan perubahan satuan ditolak jika masih mempunyai alokasi atau komitmen pengadaan aktif. Riwayat tetap tersedia; POST /api/products/{id}/restore memulihkan arsip.
- Status barang dihitung server: Habis untuk stok nol, Menipis hingga min_stock, Tersedia di atasnya. Nilai status dari browser tidak menentukan stok.

### Permintaan pengadaan dan PO

| Metode | Endpoint | Penulis / pembaca | Kontrak utama |
|---|---|---|---|
| GET | /api/warehouse/requests | Lima peran internal | Referensi order, pengaju, tanggal, barang, status dan PO |
| POST | /api/warehouse/requests | Warehouse | order_number atau sales_order_id, purpose, items: sku/qty/est_price |
| POST | /api/warehouse/requests/{code}/cancel | Warehouse | Hanya permintaan yang belum mempunyai PO; riwayat dipertahankan |
| GET | /api/purchases | Internal; Supplier terbatas PO sendiri | Ringkasan penerimaan dan riwayat yang tersimpan |
| POST | /api/purchases | Finance | Pengajuan wajib, satu supplier master aktif, seluruh SKU/jumlah/satuan mengikuti pengajuan |
| POST | /api/purchases/{code}/receive | Warehouse | Penerimaan per baris, version, UUID idempotency_key |
| POST | /api/purchases/{code}/supplier-progress | Finance; Supplier opsional; kedatangan oleh Warehouse | action dan version; followup hanya Finance |
| GET | /api/inventory/movements | Lima peran internal | Filter search/type/product_id, pagination sampai 100 baris |

MR hanya dibuat dari pesanan Draft tersimpan dengan kekurangan nyata. Nama dan satuan berasal dari kebutuhan tersimpan. Kebutuhan yang sudah diajukan atau masih dipesan dikurangi dari jumlah yang bisa diajukan lagi. Estimasi dihitung server. Perubahan SKU/jumlah sebelum PO membuat pengajuan usang; Warehouse membatalkan lalu menerbitkan pengajuan baru. Finance memeriksa ulang kekurangan saat menerbitkan PO, termasuk jika stok telah dipenuhi lewat jalur lain.

PO tidak menambah stok fisik. Supplier tidak memerlukan akun untuk menyelesaikan alur: Finance mencatat pembelian dan tindak lanjut. Portal Supplier merupakan fasilitas opsional. Nomor PO otomatis dari server; kode eksplisit harus diawali huruf. Referensi numerik lama yang ambigu ditolak agar penerimaan tidak salah PO.

Harga dalam sen saat perhitungan: subtotal baris = qty × (harga beli per unit − diskon per unit). Diskon transaksi dikurangi satu kali. Total dan pembuat dihitung/diambil server. Harga maksimal Rp1 miliar/unit, jumlah maksimal 1 juta/baris, total maksimal Rp1 triliun; pecahan harga sampai dua desimal. Pesanan penjualan memakai PPN 11% dan pembulatan rupiah final sesuai kontrak yang sudah diuji.

### Penerimaan, barang tidak sesuai dan pemulihan

Payload items berisi line_index, accepted_qty, rejected_qty dan reason wajib jika ditolak. Jumlah diterima+ditolak satu pengiriman tidak melebihi sisa PO; sisa = jumlah dipesan − jumlah diterima baik kumulatif. Barang ditolak tidak menjadi stok layak. Riwayat penolakan tidak mengurangi jumlah pengganti yang masih harus diterima.

Status PO: DIPESAN → DITERIMA_SEBAGIAN atau PERLU_TINDAK_LANJUT → DITERIMA_GUDANG. Selisih atau penolakan Supplier membuat pending_followup=true. Finance wajib mencatat followup_reference dan followup_notes pada PO asal sebelum penerimaan berikutnya; Supplier tidak bisa melewati langkah tersebut dengan aksi penggantian. Penggantian memakai PO asal dan hanya jumlah yang masih belum diterima baik. Retur/refund Supplier formal tetap di luar cakupan Sprint 1.

UUID retry disimpan browser sebelum mengirim. Pengiriman ulang kunci+isi sama mengembalikan hasil tersimpan tanpa menambah stok/ledger lagi, termasuk saat versi awal sudah usang. Isi berbeda dengan kunci sama atau versi usang pada pengiriman baru menghasilkan 409. Jika koneksi terputus setelah commit, form mengirim ulang isi yang sama; kasus ini diuji melalui browser dengan respons yang sengaja diputus.

Pesanan belum siap selama stok layaknya belum teralokasi penuh. Selama PO aktif, SKU/jumlah pesanan dikunci untuk menjaga kesesuaian pengadaan; metadata dan harga tetap dapat diedit. Setelah penerimaan penuh, perubahan kebutuhan dapat dilanjutkan. Amendemen atau pembatalan PO formal belum menjadi fitur Sprint 1.

### Audit, data lama dan penyerahan

Perubahan Draft menyimpan snapshot sebelum/sesudah untuk rincian, barang, jumlah, harga dan total beserta petugas/waktu/versi. Marketing dapat membaca perubahan tersebut dan referensi pengadaan pada detail pesanan. Riwayat sebelum modul audit tersedia tidak direkayasa.

Dua migrasi tambahan menambah ledger, arsip barang, referensi order/pengaju MR, supplier PO, serta tabel purchase_receipts dan purchase_receipt_lines. Nilai/kolom lama dipertahankan. PO historis berstatus DITERIMA_GUDANG dibuatkan catatan penerimaan bertanda legacy dengan pelaku tidak diketahui; stok tidak dinaikkan lagi. MR lama yang belum memiliki referensi order tidak dihubungkan berdasarkan dugaan nama: batalkan pengajuan tanpa PO dan buat ulang dari order yang benar. Rollback migrasi ledger akan menghapus histori; gunakan perbaikan maju setelah sistem dipakai.

Bukti pengujian dan review independen disimpan pada laporan Sprint 1 completion: suite backend, browser desktop/mobile, unit frontend, enam race MariaDB, uji migrasi atas salinan data lama, serta salinan sumber/database sebelum integrasi. Review dilakukan subagent sesuai persetujuan pengguna; bukan klaim review manusia. Integrasi lokal dan status Jira dicatat pada laporan akhir.

Data demo terisolasi menyediakan ORD-SP1-READY-DIRECTOR (Draft, Rp5.550.000, seluruh stok teralokasi) untuk approval Sprint 2 serta ORD-SP1-SHORTAGE untuk skenario pengadaan. Jangan mengganti database operasional dengan database demo. Approval/invoice, VA/QRIS sandbox, pembayaran terverifikasi, SPJ final dan pengeluaran fisik/pengiriman/pelacakan tetap harus diselesaikan serta diuji pada Sprint 2; kelulusan Sprint 1 tidak menandai backlog itu Done.
