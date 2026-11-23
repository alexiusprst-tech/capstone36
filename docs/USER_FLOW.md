# Alur pengguna menurut BPMN revisi

Baseline 20 November 2026; lihat [pembacaan lengkap](PROBIS_TO_BE.md). Bagian pembayaran sampai customer menerima adalah **target Sprint 2**, bukan klaim fitur saat ini sudah berfungsi penuh. Tidak ada pengiriman paralel dengan invoice.

## 1. Customer dan Marketing membuat pesanan

Customer menyampaikan kebutuhan ke perusahaan. Marketing memilih/melengkapi customer server, membuat Draft dan memilih barang dari master SKU beserta kuantitas, harga jual serta rincian kebutuhan. Simpan memakai versi terbaru. Sistem menyimpan snapshot identitas, menghitung nilai dan menampilkan alokasi/kekurangan per barang.

Jika stok seluruh baris terpenuhi, menuju pengajuan (langkah 4). Jika kurang, Draft tetap tersimpan dan Warehouse melihat kebutuhan untuk pengadaan. Marketing tidak menerbitkan MR Gudang, PO supplier atau Surat Jalan pengiriman customer sebagai pengganti role terkait.

## 2. Warehouse mengajukan kebutuhan, Finance membeli

Warehouse membuka Pengajuan Kebutuhan pada pesanan yang kurang. Form mengikuti pola pembelian yang telah diminta pengguna: header kode rencana otomatis, tanggal, faktur opsional, proyek dari pesanan dan supplier default; tabel barang/supplier/jumlah/harga estimasi/diskon/subtotal; ringkasan total/diskon/metode usulan. Barang dan satuan dari master, kuantitas dibatasi kekurangan. Simpan membentuk MR, **belum PO**.

Finance memilih MR yang masih valid, memeriksa supplier aktif, harga beli/diskon serta seluruh rincian. Perubahan kebutuhan memerlukan pembatalan MR yang belum diproses dan pengajuan ulang. Finance menerbitkan satu PO supplier; status `DIPESAN`. PO ini tidak menambah stok atau menyatakan pembayaran customer lunas.

Supplier/Finance memperbarui konfirmasi dan resi perjalanan barang menuju Gudang. Gudang melihat status beserta ikon dan riwayat pembaruannya. Ini terpisah dari resi pengiriman ke customer pada langkah 8.

## 3. Warehouse menerima dan memeriksa barang

Catat kedatangan, lalu penerimaan/QC per baris dengan jumlah layak dan ditolak. Isi alasan barang ditolak. Ringkasan harus menampilkan dipesan, diterima layak kumulatif, ditolak dan sisa yang masih harus dipenuhi; kedatangan tidak otomatis berarti seluruh barang sesuai.

Jika parsial layak, hanya bagian layak masuk stok/ledger. Jika ada penolakan, Finance melihat antrean tindak lanjut, mencatat referensi dan hasil komunikasi supplier, lalu pengganti dikirim dan QC diulang pada kebutuhan tersisa. Barang ditolak tidak lebih dulu dimasukkan stok lalu diretur dari stok sah. Retry penerimaan yang sama tidak menambah stok lagi.

Setelah seluruh kebutuhan pesanan teralokasi, tampilkan “Siap Diajukan”. Ini indikator kesiapan; status penjualan masih Draft hingga Marketing submit.

## 4. Marketing mengajukan; Direktur bila perlu

Marketing memeriksa rincian final dan mengajukan versi terbaru. Server memeriksa ulang seluruh alokasi. Detail kemudian terkunci. Nilai final server >Rp5 juta menjadi `Menunggu Verifikasi`; Direktur membaca rincian, pengaju, waktu aktual, stok, harga dan estimasi HPP bila tersedia, lalu menyetujui menjadi `Siap Invoice`.

Nilai ≤Rp5 juta langsung `Siap Invoice` setelah submit; Direktur dapat memantau tanpa menyetujui. Tidak ada tombol tahan/tolak/revisi untuk order server sampai proses tersebut disepakati. Pesanan yang belum disetujui tetap menunggu; Finance tidak dapat melompatinya.

## 5. Finance menerbitkan dan mengirim invoice

Finance membuka antrean Siap Invoice, memeriksa customer, barang, nilai final, persetujuan bila wajib dan tanggal jatuh tempo. Server menerbitkan satu invoice bernomor unik dari order. Status menjadi `Menunggu Pembayaran`. Finance dapat mempratinjau atau mengunduh kuitansi dan membuka bagian **Pengiriman invoice ke customer** pada detail invoice.

Di lingkungan uji dengan penerima default aktif, **Terbitkan Tagihan** sekaligus mengirim pemberitahuan ke inbox uji yang ditentukan. Finance melihat alamat dan hasil pengiriman pada detail invoice. Saat pengiriman gagal, Finance memilih **Kirim ulang invoice** dari detail tersebut. Di luar mode ini, Finance memeriksa email customer lalu memilih **Kirim invoice**. Penerima membuka tautan untuk membaca nomor, rincian dan saldo tagihan. Jika instruksi VA/QRIS dari provider belum tersedia, halaman menampilkan status tersebut. Kirim ulang mengganti tautan setelah penyedia menerima pesan. **Cabut tautan** mengakhiri akses ke invoice terkait.

## 6. Customer membayar; sistem memverifikasi

Target CAPS-28/31/33: pilih kanal tersedia, server mengambil nominal dari invoice dan membuat instruksi provider dengan masa berlaku. Sistem memverifikasi hasil dari provider. Sambil menunggu, tampilkan status tertunda/gagal/kedaluwarsa sesuai hasil yang valid. Instruksi yang kedaluwarsa dapat diganti percobaannya tanpa invoice kedua.

Belum lunas kembali ke informasi invoice/pembayaran; tidak maju ke SPJ operasional atau pengiriman. Sudah lunas mencatat fakta server dan memicu konfirmasi email uji. Duplikat callback/refresh tidak mengubah jumlah uang atau menggandakan efek.

## 7. Administrasi menyelesaikan SPJ pra-kirim

Target CAPS-24: antrean kerja berisi pesanan lunas. Metadata/draft yang disiapkan sebelum lunas tetap bertanda persiapan. Detail proyek menunjukkan sumber customer/order/invoice/pembayaran dan kekurangan administratif, bukan nilai contoh.

Checklist terbagi **pra-kirim** dan **lampiran susulan**. Daftar wajib pra-kirim harus ditetapkan sebelum menyelesaikan implementasi gerbang Siap. Admin melengkapi data, preview, generate atau mengunggah dokumen yang relevan; server memvalidasi kesiapan bundel, tidak sekadar menghitung file terunduh. Dokumen yang belum mungkin ditandatangani penerima tidak memblokir fase ini.

## 8. Warehouse mengirim bersama SPJ

Target CAPS-30: antrean pengiriman hanya dapat ditindaklanjuti bila lunas dan SPJ pra-kirim Siap. Gudang memeriksa alamat/barang/bundel, mengisi kurir, nomor resi dan informasi kirim, lalu mengonfirmasi dispatch. Server memeriksa ulang semua prasyarat, menyimpan bukti versi SPJ dan mutasi stok sekali. Barang dan SPJ dikirim bersama; customer memperoleh resi/tautan akses terbatas.

Jika pembayaran/SPJ belum sah, server menolak tanpa perubahan stok atau status. Pesan menyebut penghambat yang konkret dan bagian yang bertanggung jawab; jangan menyiasati dengan tombol lokal.

## 9. Pelacakan dan lampiran setelah penyerahan

Target CAPS-32: customer melihat pesanan yang berhak diakses melalui tautan terbatas; resi manual dapat mengarah ke layanan kurir. Konfirmasi terima tersedia setelah dispatch dan tidak menghasilkan efek berulang. Nomor resi saja tidak membuka data invoice pribadi.

Sesuai klarifikasi pengguna, BAST bertanda tangan dan foto penerimaan dilengkapi setelah penyerahan. Admin mencatat kelengkapan susulan terpisah; pengiriman yang sah tidak berubah kembali menjadi belum dikirim. Keterkaitan tanggal, penerima dan file harus dapat diaudit. Langkah ini memperluas titik akhir BPMN yang hanya menunjukkan barang+SPJ sudah dikirim.

## Pemulihan kesalahan

Versi usang: muat ulang dan tinjau perubahan, jangan menimpa otomatis. Koneksi gagal: tampilkan bahwa hasil belum diketahui dan gunakan mekanisme retry sesuai endpoint. Hak akses gagal: hentikan aksi dan arahkan ke peran yang tepat. Email gagal: retry pengiriman pesan, bukan transaksi bisnis. Data sumber kurang: tampilkan field/sumber yang harus dilengkapi, jangan mengganti dengan nol, tanggal hari ini, nama contoh atau status selesai.
