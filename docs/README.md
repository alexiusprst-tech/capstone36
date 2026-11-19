# Sistem Terintegrasi Pengadaan Barang & SPJ Dokumen
## CV. Alpharo Artha Jaya — Dokumentasi Teknis Sistem

Dokumentasi resmi arsitektur sistem, proses bisnis, model data, dan antarmuka operasional CV. Alpharo Artha Jaya. Disusun berdasarkan analisis mendalam terhadap **Diagram BPMN To-Be (Proses Pesanan Hingga Dokumen SPJ)** serta implementasi kode aktif (Laravel 13 / PHP 8.4 dan Vue 3 / Vite 6 / Tailwind CSS).

---

## 📌 Indeks Berkas Dokumentasi (Folder: docs/)

Seluruh berkas dokumentasi teknis sistem tersentralisasi pada direktori [docs/](file:///c:/Users/Acer/capstone/docs/):

| No | Berkas | Deskripsi & Cakupan Teknis |
|:---|:---|:---|
| 1 | [PRD.md](PRD.md) | **Product Requirements Document**: Latar belakang, peran pengguna, katalog lengkap **46 Functional Requirements (FR)**, dan alur cross-functional BPMN. |
| 2 | [ARCHITECTURE.md](ARCHITECTURE.md) | **System Architecture**: Arsitektur Decoupled SPA + Headless REST API, pembagian layer, diagram modular, dan keamanan Sanctum. |
| 3 | [DATABASE.md](DATABASE.md) | **Database Design**: Diagram ERD, kamus data tabel (products, warehouse_requests, purchase_transactions, spj_projects), indexing, dan seeder. |
| 4 | [SCHEMA.md](SCHEMA.md) | **Data Schema & Models**: Definisi Eloquent models, casts, fillables, relationships, dan skema payload JSON. |
| 5 | [API.md](API.md) | **API Contracts**: Rute endpoint RESTful aktif di `backend/routes/api.php`, header, parameter, body request, response payload, dan HTTP status. |
| 6 | [RULES.md](RULES.md) | **Business Rules & Governance**: Aturan Segregation of Duties (SoD), formula kalkulasi diskon & PPN, penguncian satuan, dan kronologi SPJ. |
| 7 | [USER_FLOW.md](USER_FLOW.md) | **User Flow**: Alur langkah demi langkah interaksi setiap peran selaras dengan swimlane BPMN To-Be (Marketing, Gudang, Finance, Admin). |
| 8 | [DESIGN.md](DESIGN.md) | **UI/UX Design System**: Token warna aktif, sidebar gradient `#0B4DA2`, topbar, tabel data, modal catalog, dan tombol aksi bergradasi dengan icon putih. |
| 9 | [AGENT.md](AGENT.md) | **AI Agent Framework**: Spesifikasi agen otonom (Procurement Advisor, SPJ Compliance Auditor, Inventory Optimizer) dan skema tool call. |

---

## ⚙️ Stack Teknologi Aktual

* **Backend**: Laravel 13.x (composer: ^13.17), PHP >=8.3, Eloquent ORM.
* **Frontend**: Vue 3.5 (Composition API `<script setup>`), Inertia 2 dengan Vue 3, Vite 6, Tailwind CSS v4, dan Lucide Icons.
* **Database**: SQLite 3 untuk pengujian; koneksi mysql pada lingkungan ini memakai MariaDB 10.4/InnoDB.
* **Dokumen & Spreadsheet**: PhpSpreadsheet & SheetJS (Import/Export 2.196 SKU).
* **Pengujian**: PHPUnit 12.5, unit test Node.js, dan Playwright. Hasil terbaru dicatat dalam laporan pengujian Sprint 1.

---

## Progres dan kontrak Sprint 1

Lihat [SPRINT1_PROGRESS.md](SPRINT1_PROGRESS.md) untuk kontrak customer server, relasi pesanan, pengujian, penanganan data customer lama, pengadaan, penerimaan parsial, ledger, audit perubahan, serta batas penyerahan Sprint 2. Kontrak ini mengoreksi spesifikasi awal yang belum mencerminkan kode terkini.

## 🚀 Panduan Memulai Cepat (Quickstart)

```bash
# 1. Setup Backend
cd backend
composer install
# Buat .env dari .env.example hanya pada instalasi baru; pertahankan .env yang sudah ada.
# Jalankan php artisan key:generate hanya bila APP_KEY belum tersedia.
php artisan migrate
php artisan serve

# 2. Setup & Build Frontend
cd ../frontend
npm ci
npm run build   # Kompilasi otomatis ke backend/public
```

Pengujian aman: backend menggunakan php artisan test (SQLite :memory: sesuai phpunit.xml); frontend menggunakan npm run test:unit dan npm run test:ui. Server UI membuat SQLite terpisah pada .ui-review/inertia-ui dan akun sintetis. Jangan menjalankan migrate:fresh pada database operasional.

Daftar berikut hanya berlaku bila seeder development terkait memang dijalankan pada database uji baru; bukan kredensial pasti dari database yang sudah dipakai. Jangan reset database operasional untuk membuat akun contoh.

Akun contoh dari dokumentasi awal:
* Admin: `admin@example.com` / `password`
* Warehouse: `warehouse@example.com` / `password`
* Finance: `finance@example.com` / `password`
* Marketing: `marketing@example.com` / `password`
