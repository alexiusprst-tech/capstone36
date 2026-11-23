# Aturan bisnis dan batas kewenangan

Baseline 20 November 2026. Dasar: [BPMN revisi](PROBIS_TO_BE.md), Jira dan klarifikasi pengguna. Status kemampuan aktif versus target ada di [audit](BPMN_JIRA_AUDIT.md). Seluruh gerbang transaksi harus ditegakkan server; menyembunyikan tombol saja tidak cukup.

## Peran dan sumber kebenaran

| Pelaku | Berwenang | Batas |
| --- | --- | --- |
| Marketing | Master customer, Draft penjualan, harga jual, pengajuan setelah stok siap | Tidak mengesahkan QC, PO supplier, approval Direktur atau pembayaran |
| Warehouse | Katalog/stok dengan ledger, pengajuan kebutuhan, kedatangan/QC, pengiriman setelah gerbang terpenuhi | Harga/supplier pada form pengajuan adalah usulan; bukan PO terbit atau komitmen kas |
| Finance | Master supplier, PO dari pengajuan Gudang, tindak lanjut ketidaksesuaian, pemeriksaan final/invoice, pengelolaan instruksi pembayaran | Tidak menambah stok hanya karena PO dibuat; tidak menyatakan lunas tanpa verifikasi provider |
| Direktur | Approval pesanan >Rp5 juta dan monitoring | Tidak dibutuhkan untuk nilai ≤Rp5 juta; tahan/tolak/revisi formal belum disepakati |
| Administrasi | Melengkapi metadata administratif, dokumen SPJ, lampiran dan arsip | Tidak mengubah sumber harga/order, approval, pelunasan atau fakta pengiriman |
| Customer/Supplier | Komunikasi/transaksi eksternal sesuai akses khusus | Bukan role internal bebas akses seluruh pesanan |

Data otoritatif: customer dari master dan snapshot order; barang dari SKU/satuan master; nilai penjualan dari order; pembelian dari PO; stok dari mutasi fisik dan alokasi; approval dari pelaku/waktu server; pembayaran target dari provider terverifikasi; SPJ dari sumber tersebut dan isian administratif yang terlacak. Cache localStorage, template, status bawaan dan unggahan bukti tidak dapat menggantikan sumber ini.

## Order, stok dan pengadaan

1. Draft boleh diperbarui Marketing memakai versi terbaru. Customer harus valid; SKU wajib terhubung katalog aktif sebelum submit/pengadaan. Kode aktif masih dapat menyimpan kebutuhan SKU belum dikenal pada Draft, tetapi tidak menganggapnya siap stok atau membuat master fiktif. Nama atau subtotal kiriman browser tidak menjadi sumber final untuk barang yang terhubung master. Snapshot historis tidak ditimpa perubahan master.
2. Stok tersedia = stok fisik − seluruh alokasi aktif. Alokasi pesanan sendiri tetap diperhitungkan sebagai hak pesanan itu. Submit membutuhkan semua baris terpenuhi, bukan hanya total kuantitas gabungan.
3. Alokasi, submit dan approval tidak mengurangi stok fisik. Pembelian tidak menambah stok. Penerimaan layak QC menambah stok dan ledger tepat sekali; penolakan tidak menambah stok.
4. Gudang membuat MR hanya untuk kekurangan pesanan Draft yang terhubung; jumlah tidak melebihi kebutuhan tersisa, tanpa pengajuan aktif ganda untuk kebutuhan yang sama. Jika rincian/kekurangan berubah, batalkan MR yang belum menjadi PO lalu ajukan ulang. MR historis tidak dihubungkan otomatis lewat kemiripan nama.
5. Form Gudang boleh memuat kode rencana `PB...`, tanggal, faktur opsional, satu supplier default, harga/diskon estimasi dan metode usulan. Kode server di `procurement_draft` berbeda dari `purchase_code` utama yang masih null. Finance memvalidasi ulang sebelum menerbitkan PO.
6. Satu PO aktif dari satu MR, satu supplier aktif, seluruh SKU/qty/satuan harus cocok. Status awal `DIPESAN`, bukan `LUNAS`. Metode pembayaran pembelian tidak menjadi metode pembayaran invoice customer.
7. QC per baris: kuantitas diperiksa tidak melebihi sisa, jumlah diterima/ditolak nonnegatif, alasan wajib jika ditolak, tanggal tidak di masa depan WIB. Ditolak tetap menjadi kebutuhan pengganti, bukan pengurangan pesanan.
8. Retur dalam cakupan ini hanya ketidaksesuaian saat QC. Finance wajib mencatat referensi/hasil tindak lanjut sebelum barang pengganti diterima. Ulangi pengiriman/penerimaan pada sisa kebutuhan PO; jangan membuat pembelian seluruh kuantitas lagi. Retur stok yang telah diterima sah, refund dan amendemen PO belum masuk cakupan ini.
9. Penulis stok/alokasi menggunakan transaksi dan urutan kunci inventory yang sama. Versi usang ditolak. Penerimaan dengan UUID dan payload sama mengembalikan hasil tersimpan tanpa mutasi ganda; UUID sama berisi payload berbeda ditolak. Jangan menyamakan retry ini dengan izin mengulang submit/approval.

## Nilai, approval dan invoice

- Harga penjualan aktif diolah sebagai sen, lalu total dibulatkan sekali ke rupiah: `grand_total = intdiv(subtotal_sen × 111 + 5000, 10000)`; subtotal rupiah dibulatkan dan pajak adalah selisih total terhadap subtotal. Ini kontrak aplikasi yang ada, bukan pernyataan tarif hukum atau syarat baru BPMN.
- Pembanding approval adalah `sales_orders.grand_total` server. >Rp5.000.000 wajib Direktur; tepat Rp5.000.000 dan lebih kecil langsung `Siap Invoice` setelah Marketing submit valid. Monitoring Direktur bukan gerbang tambahan untuk pesanan kecil.
- Setelah submit, rincian terkunci. Invoice hanya Finance, setelah submit, approval bila wajib, dan stok siap diperiksa kembali. Satu invoice aktif per order untuk versi awal; nomor dan nilai dari server. Penerbitan invoice bukan pelunasan.
- Pembelian: `diskon_per_unit = diskon_nominal` bila diberikan, selain itu hasil persentase harga per unit; `subtotal_baris = qty × (harga − diskon_per_unit)`; `total = Σsubtotal_baris − diskon_transaksi`. Nominal dan persen bukan dua potongan terpisah. Diskon tidak boleh melebihi dasar nilainya. Gunakan aritmetika sen/decimal; jangan menerapkan diskon transaksi dua kali.
- Harga beli, HPP estimasi dan harga jual adalah nilai berbeda. HPP belum tersedia ditampilkan sebagai belum tersedia; tidak diisi 78% harga jual. Kerugian boleh tampil negatif dan tidak mengubah gerbang approval.
- Tidak ada aturan approval pembelian ≥Rp50 juta pada BPMN/Jira yang diperiksa. Jangan menambahkannya dari gagasan AI lama.

## Pembayaran, SPJ dan pengiriman — kontrak target Sprint 2

1. Invoice → instruksi VA/QRIS satu provider sandbox → callback/query provider yang diverifikasi server → status lunas. Signature/identitas, referensi, nominal, mata uang dan status harus sesuai kontrak provider. Redirect sukses atau foto transfer tidak cukup.
2. Callback duplikat/terlambat aman: tidak mencatat uang, histori, notifikasi atau stok dua kali; tidak menurunkan transaksi sudah lunas menjadi gagal. Satu invoice boleh punya beberapa percobaan instruksi sesuai masa berlaku, tanpa kewajiban baru. Termin, refund dan cicilan di luar versi awal Sprint 2.
3. Antrean kerja SPJ aktual hanya pesanan lunas. Menyiapkan metadata/draft sejak order tidak berarti SPJ boleh difinalkan atau pesanan boleh dikirim.
4. **SPJ Siap pra-kirim** mensyaratkan lunas terverifikasi, data sumber valid dan seluruh berkas wajib pra-kirim terpenuhi menurut daftar yang ditetapkan. Daftar persisnya belum disepakati; tidak boleh menyatakan semua 15 jenis dokumen wajib.
5. Keputusan pengguna: BAST bertanda tangan dan foto penerimaan adalah lampiran susulan sesudah penyerahan. Draft BAST/foto persiapan dapat dibuat lebih awal bila relevan, tetapi bukan bukti penerimaan. Kekurangan lampiran susulan ditampilkan/ditagih tanpa memundurkan fakta pengiriman yang sah.
6. Warehouse hanya boleh mengirim jika pembayaran lunas **dan** SPJ pra-kirim Siap, diperiksa server. Simpan resi/kurir/waktu/pelaku/referensi versi bundel. Pengurangan stok dan pelepasan alokasi terjadi tepat sekali saat dispatch yang berhasil; aksi gagal tidak mengubah stok.
7. Customer memperoleh tautan akses terbatas tanpa akun sesuai CAPS-26/32. Nomor order/ID/resi berurutan bukan otorisasi data invoice/proyek. Resi awal dicatat manual; jangan menjanjikan integrasi API kurir.
8. Konfirmasi terima hanya setelah dikirim, disimpan idempoten dengan waktu dan identitas akses. Ini perluasan Jira setelah akhir BPMN, bukan syarat untuk mengirim SPJ pra-kirim.
9. Email gagal tidak membatalkan invoice atau pembayaran sah. Catat kegagalan dan retry tanpa mengubah fakta transaksi. Pekerjaan async dijalankan setelah commit; pengiriman pesan memerlukan konfigurasi dan lingkungan uji yang disetujui.

## Tanggal, dokumen dan audit

Tidak ada satu urutan tanggal linear untuk semua dokumen: pengadaan supplier dapat terjadi sebelum pengajuan approval penjualan. Invoice customer setelah approval yang diwajibkan; pelunasan terverifikasi sebelum SPJ Siap; SPJ pra-kirim sebelum dispatch; BAST bertanda tangan/foto penerimaan sesudah penyerahan. Surat Jalan supplier berbeda dari Surat Jalan customer. Jangan menerapkan aturan lama `surat jalan customer ≤ invoice ≤ SPJ`.

Tanggal kegiatan, tanggal surat, dan timestamp sistem disimpan terpisah; tampilkan zona Asia/Jakarta. Jangan mengarang tanda tangan, pelaku, nomor, tanggal, meterai atau bukti foto. Kebutuhan meterai mengikuti ketentuan dokumen/proyek yang ditetapkan, bukan flag “selalu wajib” tanpa dasar.

Hasil generate `FINAL`, keberadaan file, dan status template tidak sama dengan persetujuan substansi atau kesiapan bundel. Simpan versi sumber, hasil validasi, pelaku dan waktu. Revisi setelah final harus terlihat; file yang menyertai dispatch tetap dapat dirujuk. Persyaratan lintas modul yang belum ditegakkan pada kode tercatat di [audit](BPMN_JIRA_AUDIT.md).
