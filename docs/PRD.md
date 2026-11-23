# PRD — Pengadaan, pesanan dan SPJ terintegrasi

CV Alpharo Artha Jaya. Baseline revisi 20 November 2026. [BPMN](PROBIS_TO_BE.md) adalah acuan urutan; [RULES.md](RULES.md) memerinci kontrak dan [audit](BPMN_JIRA_AUDIT.md) memisahkan kemampuan aktif dari backlog. ID 46 FR dipertahankan untuk keterlacakan; uraian di bawah adalah kebutuhan, bukan pernyataan semua sudah diimplementasikan.

## Tujuan dan batas produk

Hubungkan pesanan customer, ketersediaan stok, pengadaan bila perlu, approval, invoice, pembayaran, SPJ dan pengiriman tanpa entri transaksi ganda atau pelompatan wewenang. Generator mengambil data yang dapat ditelusuri; nilai contoh tidak boleh menjadi bukti transaksi. Jumlah SKU master dapat berubah; angka 2.196 adalah kondisi dataset awal, bukan batas bisnis yang harus ditanam dalam kode.

Marketing mengelola customer dan pesanan; Warehouse memastikan stok, mengajukan kebutuhan kepada Finance, menerima/QC dan mengirim; Finance memesan supplier, menindaklanjuti ketidaksesuaian dan mengelola invoice/pembayaran; Direktur menyetujui penjualan >Rp5 juta; Administrasi mengurus SPJ. Customer/Supplier adalah peserta eksternal. Kanal akses tanpa akun untuk customer merupakan target Jira, bukan endpoint anonim yang saat ini tersedia.

Pengajuan Gudang memuat field pembelian sesuai permintaan pengguna sebagai **usulan**. Ini tidak menyerahkan penerbitan PO atau verifikasi uang kepada Gudang. Pisahkan pembelian supplier dan penjualan customer, termasuk kode, nilai, faktur serta resinya.

## Kebutuhan fungsional

### Marketing — 8 FR

| ID | Kebutuhan | Keterlacakan |
| --- | --- | --- |
| FR-MKT-01 | Kelola customer server dengan identitas/kontak dan kode unik; perubahan master tidak menulis ulang snapshot order lama. | CAPS-11 |
| FR-MKT-02 | Buat Draft pesanan dari customer valid dan simpan di server dengan nomor unik. | CAPS-12 |
| FR-MKT-03 | Pilih SKU/satuan master, kuantitas, harga jual dan rincian kebutuhan; server menghitung total final. | CAPS-12 |
| FR-MKT-04 | Lihat alokasi dan kekurangan setiap baris, bukan sekadar stok fisik katalog. | CAPS-10/12 |
| FR-MKT-05 | Teruskan kebutuhan yang kurang ke antrean Warehouse; MR resmi tetap dibuat Warehouse. | CAPS-13/16 |
| FR-MKT-06 | Perbarui harga jual/rincian selama Draft, memakai versi terbaru; kunci setelah pengajuan. | CAPS-15/23 |
| FR-MKT-07 | Ajukan approval hanya setelah seluruh kebutuhan terpenuhi; >Rp5 juta menunggu Direktur, sisanya Siap Invoice. | CAPS-23 |
| FR-MKT-08 | Pantau status, pengajuan, approval dan kejadian dengan pelaku/waktu server. | CAPS-16/23 |

### Finance — 11 FR

| ID | Kebutuhan | Keterlacakan |
| --- | --- | --- |
| FR-FIN-01 | Tinjau rincian pengajuan pembelian dan penjualan pada antrean yang terpisah. | CAPS-14/25 |
| FR-FIN-02 | Tampilkan kebutuhan approval berdasarkan nilai final penjualan server >Rp5 juta. | CAPS-23/25 |
| FR-FIN-03 | Terima penjualan yang telah diajukan dan memenuhi approval; tidak menggantikan submit Marketing. | CAPS-23/25 |
| FR-FIN-04 | Tampilkan status approval dan pelaku/waktunya; tidak membuat persetujuan lokal. | CAPS-23/27 |
| FR-FIN-05 | Terbitkan satu invoice aktif dari penjualan valid; nomor/nilai unik dan rincian terkunci. | CAPS-25 |
| FR-FIN-06 | Sediakan VA/QRIS melalui satu provider sandbox versi awal; bank/provider belum diputuskan. | CAPS-28 |
| FR-FIN-07 | Kirim invoice/instruksi lewat email uji dan tautan akses customer terbatas; resend tidak membuat invoice baru. | CAPS-26 |
| FR-FIN-08 | Sistem memverifikasi hasil provider beserta identitas transaksi, nominal dan status; bukti unggah tidak meluluskan pembayaran. | CAPS-31 |
| FR-FIN-09 | Tandai lunas hanya dari hasil terverifikasi dan tangani callback duplikat/terlambat dengan aman. | CAPS-31/33 |
| FR-FIN-10 | Kirim konfirmasi lunas; kegagalan email tidak membatalkan pembayaran sah. | CAPS-29 |
| FR-FIN-11 | Lihat riwayat pembelian, tindak lanjut supplier, invoice, percobaan dan status pembayaran sesuai kewenangan. | CAPS-14/18/27/33 |

### Warehouse — 12 FR

| ID | Kebutuhan | Keterlacakan |
| --- | --- | --- |
| FR-WH-01 | Kelola katalog SKU/satuan/kategori, impor/ekspor dan arsip barang dengan perlindungan referensi. | CAPS-10 |
| FR-WH-02 | Perbarui stok fisik melalui operasi sah dengan ledger; PO/alokasi tidak menambah/mengurangi stok fisik. | CAPS-10/17 |
| FR-WH-03 | Lihat kebutuhan order, alokasi, kekurangan dan sisa pengadaan per SKU. | CAPS-13/16 |
| FR-WH-04 | Lihat Draft penjualan yang membutuhkan pengadaan tanpa menduplikasi order Marketing. | CAPS-13 |
| FR-WH-05 | Buat MR kepada Finance untuk kekurangan; form supplier/harga/diskon adalah estimasi. Finance yang memesan supplier. | CAPS-13/14 |
| FR-WH-06 | Lihat resi supplier dan catat kedatangan/QC parsial/penolakan; Finance menangani tindak lanjut penggantian. | CAPS-17/18 |
| FR-WH-07 | Tampilkan kesiapan secara jujur: stok siap diajukan berbeda dari lunas/SPJ siap dikirim. | CAPS-16/30 |
| FR-WH-08 | Tampilkan antrean penyiapan pengiriman customer setelah lunas dan SPJ pra-kirim Siap. | CAPS-30 |
| FR-WH-09 | Simpan proses penyiapan tanpa menganggap kedatangan supplier atau stok teralokasi sebagai sudah dikirim customer. | CAPS-30 |
| FR-WH-10 | Catat kurir/resi/waktu dispatch dan referensi versi SPJ yang ikut dikirim. | CAPS-30 |
| FR-WH-11 | Perbarui pengiriman dengan audit dan mutasi stok sekali; resi manual versi awal. | CAPS-30/32 |
| FR-WH-12 | Lihat konfirmasi penerimaan customer dan lampiran susulan setelah dispatch. | CAPS-32/36 |

### Administrasi — 6 FR

| ID | Kebutuhan | Keterlacakan |
| --- | --- | --- |
| FR-ADM-01 | Antrean kerja SPJ aktual hanya order lunas; draft metadata awal diberi label persiapan. | CAPS-24 |
| FR-ADM-02 | Generate dokumen dari customer/order/invoice dan isian administratif valid; satu template prioritas Sprint 2, 15 jenis yang ada bukan daftar wajib otomatis. | CAPS-24 |
| FR-ADM-03 | Unggah/arsip dokumen eksternal asli terpisah dari hasil generate; BAST bertanda tangan/foto penerimaan adalah susulan. | CAPS-24 + klarifikasi pengguna |
| FR-ADM-04 | Bedakan status file dengan kesiapan bundel pra-kirim dan kelengkapan susulan; simpan versi/validasi. | CAPS-24/30 |
| FR-ADM-05 | Jelaskan kekurangan pra-kirim dan cegah dispatch lewat validasi server, bukan peringatan UI saja. | CAPS-24/30 |
| FR-ADM-06 | Catat checklist wajib yang ditetapkan untuk proyek/template termasuk meterai bila memiliki dasar; daftar final masih terbuka. | CAPS-24/36 |

### Direktur — 4 FR

| ID | Kebutuhan | Keterlacakan |
| --- | --- | --- |
| FR-DIR-01 | Lihat antrean pesanan >Rp5 juta setelah Marketing submit dan stok siap. | CAPS-23 |
| FR-DIR-02 | Setujui pesanan valid melalui server; penolakan/tahan/revisi formal tidak ditambahkan tanpa keputusan proses. | CAPS-23 |
| FR-DIR-03 | Simpan pelaku/waktu/catatan approval dan kunci perubahan rincian setelah pengajuan. | CAPS-23 |
| FR-DIR-04 | Monitor pesanan ≤Rp5 juta serta invoice/pembayaran/pengiriman sesuai data tersedia; tidak memerlukan approval tambahan. | CAPS-23/27 |

### Customer tanpa akun — 5 FR (target akses Jira)

| ID | Kebutuhan | Keterlacakan |
| --- | --- | --- |
| FR-CL-01 | Terima invoice dan instruksi VA/QRIS melalui kanal uji yang ditetapkan. | CAPS-26/28 |
| FR-CL-02 | Terima konfirmasi setelah pembayaran benar-benar terverifikasi lunas. | CAPS-29 |
| FR-CL-03 | Akses pelacakan melalui tautan terbatas tanpa akun, tanpa membuka data pesanan orang lain. | CAPS-32 |
| FR-CL-04 | Lihat status/resi pesanan sendiri; awalnya resi manual/tautan kurir. | CAPS-32 |
| FR-CL-05 | Konfirmasi penerimaan hanya setelah dispatch; aksi berulang tidak menggandakan efek. | CAPS-32 |

## Cakupan rilis dan penerimaan

Fondasi aktif: customer/order, stok/alokasi, pengadaan, penerimaan/QC dan followup, serta CAPS-23. Invoice/generator SPJ memiliki implementasi awal yang perlu diselaraskan. Target Sprint 2: penyempurnaan invoice, akses/email customer, satu provider VA/QRIS sandbox, verifikasi dan retry, antrean SPJ lunas, bundel pra-kirim, dispatch dan tracking manual. Checklist susulan terpisah dari syarat dispatch.

Tidak termasuk tanpa persetujuan baru: integrasi semua bank/kurir, pembayaran cicilan/refund, retur stok yang sudah diterima sah, approval pembelian Rp50 juta, AI otonom, reject/hold server, dan pengubahan desain seluruh aplikasi. Pengguna meminta tampilan/alur yang ada dipertahankan; perubahan UI mengikuti [DESIGN.md](DESIGN.md).

Penerimaan membutuhkan pembuktian di server, review independen dan integrasi sesuai DoD Jira; fixture saja tidak menyelesaikan integrasi provider. Uji nilai Rp4.999.999/Rp5.000.000/Rp5.000.001, stok parsial/bersaing, QC/pengganti/retry, akses lintas role, invoice ganda, callback palsu/duplikat/terlambat, unpaid/SPJ belum siap menolak dispatch, lalu dispatch/konfirmasi/susulan yang benar. Rincian skenario di [audit](BPMN_JIRA_AUDIT.md).

Kebutuhan nonfungsional: transaksi atomik, referensi unik, versi konflik, histori pelaku/waktu, akses objek per customer/supplier, file aman dan privat, perhitungan uang deterministik, pesan kesalahan jelas, serta layar kecil tanpa overflow halaman. Jangan mengklaim SLA/availability numerik yang belum disetujui. Stack aktual dijelaskan di [README.md](README.md).
