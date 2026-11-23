# Dokumentasi sistem CV Alpharo Artha Jaya

Baseline revisi: **20 November 2026**. Referensi proses adalah [gambar BPMN revisi](references/PROBIS-TO-BE-Revisi.png), pembacaan terurut di [PROBIS_TO_BE.md](PROBIS_TO_BE.md), Jira CAPS yang dibaca pada tanggal tersebut, dan kode dalam checkout ini. Dokumen ini tidak menyatakan seluruh TO-BE sudah berjalan.

Urutan wajib: **stok/QC terpenuhi → Marketing mengajukan → Direktur bila nilai >Rp5 juta → Finance menerbitkan invoice → pembayaran terverifikasi lunas → Administrasi menyiapkan SPJ pra-kirim → Warehouse mengirim barang bersama SPJ**. BAST bertanda tangan dan foto penerimaan menjadi lampiran susulan, sesuai keputusan pengguna 20 November 2026.

## Indeks dan cara membaca

| Dokumen | Isi |
| --- | --- |
| [PROBIS_TO_BE.md](PROBIS_TO_BE.md) | Semua jalur, pesan, gerbang, dan keterbatasan gambar |
| [BPMN_JIRA_AUDIT.md](BPMN_JIRA_AUDIT.md) | Kesesuaian Jira, temuan kode/dokumen, keputusan terbuka, urutan implementasi dan kasus uji |
| [PRD.md](PRD.md) | 46 kebutuhan dengan ID lama dipertahankan; tanggung jawab dan batas target |
| [RULES.md](RULES.md) | Aturan lintas modul dan gerbang yang wajib diperiksa server |
| [USER_FLOW.md](USER_FLOW.md) | Interaksi tiap peran dari pesanan sampai lampiran susulan |
| [API.md](API.md) | Endpoint aktif, payload pokok dan kontrak target yang belum tersedia |
| [DATABASE.md](DATABASE.md) | Struktur penyimpanan aktif, relasi dan kebutuhan migrasi mendatang |
| [SCHEMA.md](SCHEMA.md) | Bentuk data, status dan kepemilikan sumber data |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Laravel/Inertia, batas modul dan integrasi target |
| [DESIGN.md](DESIGN.md) | Pola UI yang dipertahankan dan representasi status yang benar |
| [SPJ_GENERATION.md](SPJ_GENERATION.md) | Generator aktif, sumber data, pra-kirim versus susulan dan kekurangan validasinya |
| [CAPS23_APPROVAL.md](CAPS23_APPROVAL.md) | Kontrak rinci pengajuan/approval yang sudah diimplementasikan |
| [INERTIA.md](INERTIA.md) | Menjalankan halaman dan pengujian melalui Laravel |
| [AGENT.md](AGENT.md) | Batas gagasan AI opsional; bukan kewenangan transaksi atau fitur wajib |
| [SPRINT1_PROGRESS.md](SPRINT1_PROGRESS.md), [SPRINT1_COMPLETION.md](SPRINT1_COMPLETION.md) | Bukti historis pekerjaan Sprint 1; bukan status Jira langsung |

Istilah **aktif** berarti ada implementasi pada kode yang diperiksa; **target** berarti kontrak implementasi berikutnya; **terbuka** berarti belum ada keputusan yang cukup. Jika dokumen historis berbeda, gunakan baseline revisi ini untuk target proses, kode untuk mengetahui kemampuan saat ini, dan catat selisihnya. Jangan memperlakukan spesifikasi sebagai bukti fitur sudah selesai.

## Teknologi dan menjalankan lokal

Backend memakai Laravel `^13.17`, PHP `^8.3`, Sanctum, Inertia 2, Dompdf dan Reverb. Frontend memakai Vue 3.5, Inertia 2, Vite 6, Tailwind CSS 4 dan Lucide. Route halaman dimiliki Laravel, bukan Vue Router. State browser lama masih memakai Vue reactive/localStorage; bukan bukti semua transaksi tersimpan di server. Database operasional pada lingkungan yang diperiksa menggunakan MariaDB 10.4/InnoDB; pengujian terisolasi memakai SQLite.

Untuk instalasi baru saja: pasang dependensi melalui `composer install` di `backend` dan `npm ci` di `frontend`; siapkan `.env` dan APP_KEY tanpa menimpa konfigurasi yang sudah dipakai. Tinjau/backup database sebelum menjalankan migrasi pada lingkungan tujuan. `npm run build` menghasilkan aset di `backend/public/build`. Jalankan `php artisan serve` dari backend dan buka URL Laravel, biasanya `http://localhost:8000`. Mode pengembangan aset dijelaskan di [INERTIA.md](INERTIA.md).

Pengujian: `php artisan test`, `npm run test:unit`, dan `npm run test:ui` pada lingkungan uji yang disediakan. Playwright menggunakan server/database SQLite terpisah. Jangan menjalankan `migrate:fresh`, seeder contoh, atau fixture terhadap database operasional. Akun uji tidak menjadi kredensial pasti akun operasional.

## Batas kondisi saat audit

Customer/order, stok/alokasi, pengadaan, penerimaan/QC, tindak lanjut dan pengajuan/approval tersedia di server. Finance dapat menerbitkan invoice dan mengirim tautan customer melalui SMTP. Integrasi pembayaran, antrean SPJ khusus lunas, kesiapan bundel pra-kirim, pengiriman barang dan tautan pelacakan masih menjadi pekerjaan Sprint 2.

Untuk pengujian invoice CAPS-26 dengan Mailtrap Sandbox, isi `INVOICE_MAILER=mailtrap_sandbox`, `MAILTRAP_SANDBOX_TOKEN`, dan `MAILTRAP_SANDBOX_INBOX_ID` pada `backend/.env`. Gunakan `MAIL_FROM_ADDRESS` dan `MAIL_FROM_NAME` untuk identitas pengirim serta `APP_URL` untuk alamat aplikasi yang dapat dibuka penguji. Finance membuka detail invoice lalu memilih **Kirim invoice**. Pesan dapat diperiksa di inbox sandbox terpilih. Masa berlaku tautan default tujuh hari. Untuk tes browser terisolasi, `npm run test:ui -- --grep CAPS26` menggunakan SMTP loopback dan SQLite.

Untuk mengirim semua invoice baru secara otomatis ke satu inbox uji, gunakan `INVOICE_MAILER=mailtrap_demo`, `INVOICE_USE_DEFAULT_RECIPIENT=true`, `MAILTRAP_DEMO_TOKEN` dan `MAILTRAP_DEMO_RECIPIENT` pada backend. Mailtrap harus mengizinkan alamat penguji tersebut untuk domain demo. Penerbitan oleh Finance memicu email dan detail invoice menampilkan tujuan, status dan tombol kirim ulang. Invoice lama dapat dikirim satu per satu. Dengan opsi default nonaktif, pengiriman manual menggunakan alamat customer dan beralih ke Sandbox bila alamat itu berbeda dari penerima demo. Tautan memakai `APP_URL`. Alamat localhost hanya dapat dibuka di komputer yang menjalankan aplikasi.

Jira saat dibaca: CAPS-23 Done; CAPS-24–38 To Do; Sprint 1 Closed, Sprint 2 Epic 4–6 Active. Audit ini tidak mengubah Jira, kode aplikasi, database atau status sprint. Lihat [audit](BPMN_JIRA_AUDIT.md) untuk batas kesiapan sebelum implementasi berikutnya.
