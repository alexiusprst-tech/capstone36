# Catatan AI opsional dan batas otomasi

Baseline 20 November 2026. File ini adalah **catatan produk**, bukan `AGENTS.md` instruksi pengerjaan repository. Framework AI multi-agent belum menjadi kebutuhan wajib BPMN atau goal Sprint 2 dan tidak dinyatakan telah tersedia pada aplikasi.

Prioritas implementasi adalah [BPMN revisi](PROBIS_TO_BE.md), [PRD](PRD.md), [RULES](RULES.md) dan backlog Jira. Jangan membuat dependency AI untuk mengajukan pesanan, membayar, menyiapkan SPJ atau mengirim barang.

## Gagasan yang boleh dinilai kemudian

| Gagasan opsional | Batas |
| --- | --- |
| Saran supplier/estimasi biaya | Rekomendasi saja dari data terverifikasi; Finance tetap memilih dan menerbitkan PO |
| Pemeriksaan konsistensi dokumen | Menandai perbedaan sumber/nominal/tanggal/fase; bukan mengesahkan pembayaran, tanda tangan atau kepatuhan hukum |
| Peringatan stok minimum | Informasi kepada Warehouse; tidak otomatis membuat MR/PO atau mengubah stok |

Tidak ada ambang anomali 15% atau approval pembelian ≥Rp50 juta yang disahkan oleh BPMN/Jira yang diperiksa. Hapus ketergantungan pada aturan hipotetis tersebut. Gerbang yang jelas adalah **pesanan penjualan >Rp5 juta → approval Direktur**, sesudah Marketing submit dan stok siap.

Jika AI kelak ditambahkan, semua transaksi tetap lewat validasi/otorisasi server yang sama. AI tidak boleh mengarang identitas, nilai/tanggal, tanda tangan, bukti foto, pelunasan atau dokumen yang tidak tersedia; tidak boleh menyetujui order, meluluskan pembayaran atau dispatch secara mandiri. Data sensitif tidak dikirim ke layanan eksternal tanpa keputusan dan konfigurasi yang disetujui.

Review pengembangan oleh subagent yang diminta pengguna adalah cara kerja pengerjaan/review kode, bukan fitur AI bisnis baru yang harus dipasang ke aplikasi. Tidak ada tool-call framework atau layanan AI baru yang ditambahkan oleh revisi dokumentasi ini.
