# Desain antarmuka dan makna status

Baseline 20 November 2026. Pertahankan layout, navigasi, komponen dan pilihan visual pengguna yang sudah ada. Revisi BPMN mengoreksi urutan/kewenangan/data; tidak memberi izin mendesain ulang aplikasi. Sumber token aktif: [style.css](../frontend/src/style.css); kontrak peran: [RULES.md](RULES.md).

## Gaya yang dipertahankan

| Elemen | Acuan aktif/ketentuan |
| --- | --- |
| Font | Inter, Plus Jakarta Sans/system fallback; monospaced untuk kode bila perlu |
| Aksi/tab aktif | Biru `alpharo-600 #2563eb`, teks putih; hover mengikuti token yang ada |
| Tab tidak dipilih | Putih, teks netral, garis tepi abu-abu terlihat seperti tombol Muat Ulang; bukan semua tab biru |
| Panel/card | Permukaan putih/terang, batas abu-abu dan spasi konsisten; jangan mewarnai semua card biru karena permintaan terdahulu merujuk tombol/tab |
| Fokus | Ring/outline keyboard terlihat; tombol ikon diberi label aksesibel/title yang jelas |
| Status | Teks + ikon + warna, tidak mengandalkan warna saja; warna status bukan indikator tab aktif |

Permintaan pengguna terakhir memakai garis tepi untuk tab nonaktif agar tidak menyatu dengan background, menggantikan permintaan lama tanpa border. Jangan mengembalikan sidebar gradient atau warna biru tua seluruh tab dari spesifikasi awal yang usang.

## Dashboard, navigasi dan modal yang dipertahankan

Sidebar/topbar terang, breadcrumbs dan navigasi Inertia tetap mengikuti halaman yang ada. Panel dashboard internal mengalir satu kolom lebar penuh dengan jarak konsisten; susunan KPI dan grafik berpasangan tetap. Isi panel memakai baris/separator, bukan menambahkan card bertingkat untuk setiap nilai. ActivityLogCard ditempatkan terakhir dan memakai penyaringan role yang ada; log browser ini belum merupakan audit server lintas perangkat.

Pertahankan fokus modal, Escape, pengembalian fokus pemicu, penguncian scroll dan dukungan reduced-motion. Badge dashboard plain memakai teks/penanda warna; layar lain boleh mempertahankan varian badge yang sudah ada. Jangan menambah batas/bayangan di seluruh elemen karena permintaan border tab nonaktif. Jalur menu aktual berasal konfigurasi halaman Laravel, bukan daftar URL rekaan.

## Pengajuan Kebutuhan Gudang dan Pembelian Finance

Pertahankan tiga bagian sesuai gambar pengguna: **Header Pembelian**, **Detail Pembelian**, **Total dan Pembayaran**. Pada Gudang tampilkan konteks bahwa data merupakan usulan pengadaan; judul/teks bantuan harus membedakannya dari PO Finance, tanpa mengubah susunan form yang diminta.

- Header: kode pembelian/rencana dari server (read-only), tanggal, nomor faktur opsional, proyek terkait order, supplier default. Kontrol cari/tambah/bersihkan supplier mengikuti kewenangan; Gudang tidak mendapat aksi membuat master supplier Finance tanpa otorisasi.
- Detail: pilih barang dari katalog, supplier mengikuti default satu supplier per pengajuan/PO, jumlah, harga, % diskon, diskon per satuan, subtotal dan hapus baris. SKU/satuan berasal master; diskon persen dan nominal adalah dua representasi potongan yang sama. Kuantitas pengajuan dibatasi kekurangan.
- Ringkasan: jumlah harga setelah diskon baris, diskon transaksi, total akhir dan metode usulan pembayaran pembelian. Tampilkan Rp dan pemisah angka konsisten. Jangan tampilkan usulan ini sebagai invoice customer atau pembayaran terverifikasi.
- Cari menampilkan ikon search di dalam area input dengan padding cukup; label tetap ada. Tombol tambah/hapus/cari memiliki fokus dan area klik yang memadai, bukan ikon tanpa nama.
- Tombol Simpan mencegah klik ganda selama request; validasi baris ditampilkan dekat field. Konflik versi/kebutuhan meminta muat ulang/tinjau ulang, tidak menghilangkan form secara diam-diam.

## Penerimaan fisik dan cek resi supplier

Pertahankan ikon status yang konsisten pada pilihan status, ringkasan dan riwayat. Contoh label yang sudah diminta: paket disiapkan, kurir mengambil, menuju gudang, penundaan/kendala, pengganti dikirim. Ikon tidak mengubah state API. Tampilkan pelaku/role, timestamp, lokasi dan catatan; bedakan waktu kejadian dari waktu pembaruan.

Ringkasan harus membedakan dipesan, layak diterima kumulatif, ditolak QC dan sisa pengganti. Kedatangan bukan penerimaan lengkap. Aksi terima/QC hanya Gudang, tindak lanjut Finance, tracking sesuai kewenangan. Pembaruan yang tersedia saat ini menggunakan polling ketika halaman aktif/online dan data server; jangan menulis “terhubung langsung kurir” atau “real time tanpa jeda” tanpa integrasi yang terbukti.

## Order, approval, invoice dan pembayaran

Tampilkan indikator “Siap Diajukan” terpisah dari status Draft/approval. Nama dan waktu pengaju harus dari kejadian server. HPP belum tersedia diberi teks, bukan 0/78% perkiraan tersembunyi; margin negatif tetap terlihat. Tab membungkus pada mobile, tabel bergulir di panel tanpa memperlebar seluruh halaman.

Siap Invoice, Menunggu Pembayaran dan Lunas adalah keadaan berbeda. Instruksi VA/QRIS yang tampil tidak membuktikan lunas. Jangan menawarkan aksi yang menyatakan pembayaran terverifikasi/dispatch bila backend belum aktif; ketika diimplementasikan, tombol hanya membantu UX dan server tetap memeriksa gerbang.

## SPJ proyek — penyelarasan target

Pertahankan pola detail proyek seperti referensi pengguna: identitas, nilai/jadwal, perkembangan/foto, dan daftar dokumen dengan aksi preview/generate/upload/download. Sistem referensi JAYA GROUP menjadi acuan organisasi tampilan, bukan sumber aturan yang mengganti BPMN proyek ini.

Detail proyek wajib menunjukkan sumber order/invoice dan status pembayaran yang benar. Bagi checklist menjadi **SPJ pra-kirim** dan **Lampiran setelah penyerahan**; ini pemisahan makna/proses, bukan keharusan merombak layout. Tampilkan alasan dokumen belum siap beserta sumber data yang kurang. Persentase memakai jumlah wajib pada fase terkait, bukan seluruh file_path dari 15 jenis.

“File tersedia”, “Hasil generate”, “Siap pra-kirim”, “Sudah dikirim” dan “Lampiran susulan lengkap” harus dapat dibedakan. BAST bertanda tangan/foto penerimaan belum ada sebelum penyerahan bukan error pada kesiapan pra-kirim. Jangan menampilkan nama sopir, pelat, stok, tanggal atau tanda tangan contoh sebagai nilai isian transaksi. Placeholder boleh memberi petunjuk tetapi tidak disimpan otomatis.

## Kriteria pemeriksaan UI

Periksa desktop dan viewport tetap 390px: label/input terbaca, fokus keyboard, kontras tab terpilih/tidak, border terlihat, tabel bergulir lokal, total tidak terpotong, loading/error/empty state jelas dan data belum tersedia tidak disamakan nol. Uji tombol dengan API nyata untuk memastikan status/nilai/otorisasi sesuai [API.md](API.md). Dokumen ini adalah kontrak desain; audit revisi Markdown tidak mengklaim semua layar sudah lulus uji browser ulang.
