# Penyelesaian Sprint 1 — CAPS / CV Alpharo Artha Jaya

Acuan: Jira Sprint 4 “Sprint 1 - Epic 1-3” dan BPMN TO-BE. Target: pesanan tercatat, pengadaan terpenuhi, dan stok teralokasi sampai **Siap Diajukan**.

## Hasil implementasi

- Customer dan pesanan memakai penyimpanan server, nomor unik, validasi, pembatasan peran dan total yang dihitung server.
- MR merujuk pesanan serta kekurangan nyata, mencatat petugas/waktu dan mencegah kebutuhan ganda. MR usang dapat dibatalkan sebelum PO.
- PO wajib berdasarkan MR, memakai supplier terdaftar dan rincian yang sama. Harga/diskon dihitung dalam sen, tanpa mempercayai total browser.
- Penerimaan mencatat jumlah sesuai/tidak sesuai per baris. Hanya barang diterima baik menambah stok. UUID dan versi melindungi retry, konflik, serta penerimaan serentak.
- Finance mencatat tindak lanjut pada PO asal; Supplier tidak memerlukan akun dan tidak dapat melewati kewajiban tindak lanjut Finance.
- Ledger server menyimpan saldo sebelum/sesudah, perubahan, petugas dan referensi. Alokasi tidak mengurangi stok fisik. Semua penulis inventori memakai urutan mutex yang sama.
- Perubahan pesanan menyimpan nilai sebelum/sesudah. Jumlah/SKU dikunci selama PO aktif; harga/metadata tetap dapat diedit. Detail Marketing menampilkan MR, PO, kekurangan dan audit perubahan.
- Form duplikat dan data proyek contoh pada pengadaan diganti panel bersama. Status dan satuan diperjelas. Form Finance mobile dibatasi lebarnya sehingga tabel bergulir di dalam form dan tombol Simpan dapat diklik normal.
- Tanggal bisnis WIB dipisahkan dari timestamp UTC, termasuk pergantian hari/tahun dan tanggal penerimaan bawaan.

Kontrak rinci, payload, status, formula, penanganan data lama dan batas Sprint 2: [SPRINT1_PROGRESS.md](SPRINT1_PROGRESS.md).

## Verifikasi

| Pemeriksaan | Hasil |
|---|---|
| Seluruh backend PHPUnit | 176 lulus, 2.137 assertions |
| Unit frontend | 13 lulus |
| Browser desktop/mobile | 200 kasus unik lulus setelah perbaikan dan pengujian ulang terkait; 4 konteks live opsional tidak diaktifkan |
| Build produksi Vite | Berhasil |
| MariaDB 10.4, proses bersamaan | 6 skenario lulus: order berebut stok, MR ganda, PO ganda, receipt ganda, benturan kode PO dan benturan SKU |
| Migrasi atas salinan data lama | Semua kolom/baris lama tetap; 2.196 opening ledger dan 3 receipt historis tanpa menambah stok |
| Review independen | Subagent implementasi/QA sesuai persetujuan pengguna; temuan diperbaiki dan diuji ulang |

Uji browser mencakup alur lintas sesi/peran, pengadaan melalui form, pembatalan dan penerbitan ulang MR, nilai pecahan Rp300,05, penerimaan parsial/penolakan/pengganti, audit sebelum/sesudah, serta retry setelah server commit tetapi respons terputus. Pengujian tidak memakai klik paksa untuk tombol yang tertutup.

Pemeriksaan awal mengalami gangguan akses file Word sementara; 12 tes SPJ kemudian lulus terisolasi dan seluruh suite akhir lulus. Tes UI diperbaiki untuk selector aksesibilitas, format rupiah locale, fixture ekspor deterministik dan startup Vite sebelum URL Laravel dianggap siap. Kesalahan layout mobile diperbaiki pada kode aplikasi.

## Integrasi dan penyerahan

Sumber diterapkan dengan pemeriksaan hash terhadap baseline dan backup sebelum perubahan. Dua migrasi bersifat penambahan; tidak ada reset database operasional. Data historis tidak dihubungkan dengan menebak ID customer atau nama proyek. Backup sumber/database dan matriks 72 issue tersimpan di direktori audit lokal `docs/audit/sprint1-completion` pada workspace pengerjaan; backup database tidak dimasukkan ke Git.

Snapshot demo SQLite terisolasi menyediakan ORD-SP1-READY-DIRECTOR (Draft, Rp5.550.000, Siap Diajukan) dan ORD-SP1-SHORTAGE. Database demo tidak menggantikan data operasional.

Proses merge lokal, smoke test proyek utama dan pembacaan balik Jira dicatat pada manifest integrasi akhir di direktori audit. Bukti pengujian ditambahkan pada deskripsi issue terkait saat rekonsiliasi status, dengan mempertahankan acceptance criteria.

Approval Direktur, invoice, VA/QRIS sandbox, verifikasi pembayaran, SPJ final, pengeluaran fisik/pengiriman dan pelacakan tetap mengikuti backlog Sprint 2. Retur/refund formal dan amendemen PO belum menjadi cakupan Sprint 1. Tidak ada klaim bahwa pengujian menjamin seluruh kemungkinan bug telah dieliminasi.
