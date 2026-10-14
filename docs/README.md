# Sistem Terintegrasi Pengadaan Barang & SPJ Dokumen
## CV. Alphate Artha Jaya (CV. Alpharo Artha Jaya) — Dokumentasi Teknis Sistem

Dokumentasi resmi arsitektur sistem, proses bisnis, model data, dan antarmuka operasional CV. Alphate Artha Jaya. Disusun berdasarkan analisis mendalam terhadap **Diagram BPMN To-Be (Proses Pesanan Hingga Dokumen SPJ)** serta implementasi kode aktif (Laravel 12 / PHP 8.4 dan Vue 3 / Vite 6 / Tailwind CSS).

---

## 📌 Indeks Berkas Dokumentasi (Folder: docs/)

Seluruh berkas dokumentasi teknis sistem tersentralisasi pada direktori [docs/](file:///c:/Users/Acer/capstone/docs/):

| No | Berkas | Deskripsi & Cakupan Teknis |
|:---|:---|:---|
| 1 | [PRD.md](file:///c:/Users/Acer/capstone/docs/PRD.md) | **Product Requirements Document**: Latar belakang, peran pengguna, katalog lengkap **46 Functional Requirements (FR)**, dan alur cross-functional BPMN. |
| 2 | [ARCHITECTURE.md](file:///c:/Users/Acer/capstone/docs/ARCHITECTURE.md) | **System Architecture**: Arsitektur Decoupled SPA + Headless REST API, pembagian layer, diagram modular, dan keamanan Sanctum. |
| 3 | [DATABASE.md](file:///c:/Users/Acer/capstone/docs/DATABASE.md) | **Database Design**: Diagram ERD, kamus data tabel (products, warehouse_requests, purchase_transactions, spj_projects), indexing, dan seeder. |
| 4 | [SCHEMA.md](file:///c:/Users/Acer/capstone/docs/SCHEMA.md) | **Data Schema & Models**: Definisi Eloquent models, casts, fillables, relationships, dan skema payload JSON. |
| 5 | [API.md](file:///c:/Users/Acer/capstone/docs/API.md) | **API Contracts**: Rute endpoint RESTful aktif di `backend/routes/api.php`, header, parameter, body request, response payload, dan HTTP status. |
| 6 | [RULES.md](file:///c:/Users/Acer/capstone/docs/RULES.md) | **Business Rules & Governance**: Aturan Segregation of Duties (SoD), formula kalkulasi diskon & PPN, penguncian satuan, dan kronologi SPJ. |
| 7 | [USER_FLOW.md](file:///c:/Users/Acer/capstone/docs/USER_FLOW.md) | **User Flow**: Alur langkah demi langkah interaksi setiap peran selaras dengan swimlane BPMN To-Be (Marketing, Gudang, Finance, Admin). |
| 8 | [DESIGN.md](file:///c:/Users/Acer/capstone/docs/DESIGN.md) | **UI/UX Design System**: Token warna aktif, sidebar gradient `#0B4DA2`, topbar, tabel data, modal catalog, dan tombol aksi bergradasi dengan icon putih. |
| 9 | [AGENT.md](file:///c:/Users/Acer/capstone/docs/AGENT.md) | **AI Agent Framework**: Spesifikasi agen otonom (Procurement Advisor, SPJ Compliance Auditor, Inventory Optimizer) dan skema tool call. |

---

## ⚙️ Stack Teknologi Aktual

* **Backend**: Laravel 12.x on PHP 8.4 (Strict typing, constructor promotion, Eloquent ORM).
* **Frontend**: Vue 3.5 (Composition API `<script setup>`), Vite 6, Tailwind CSS v4, Lucide Icons, Pinia, Vue Router 4.
* **Database**: SQLite 3 (Dev/Test) & MySQL 8.0+ (Production InnoDB).
* **Dokumen & Spreadsheet**: PhpSpreadsheet & SheetJS (Import/Export 2.196 SKU).
* **Pengujian**: PHPUnit / Pest Framework (22 passing tests, 206 assertions).

---

## 🚀 Panduan Memulai Cepat (Quickstart)

```bash
# 1. Setup Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve

# 2. Setup & Build Frontend
cd ../frontend
npm install
npm run build   # Kompilasi otomatis ke backend/public
```

Akun default seeded:
* Admin: `admin@example.com` / `password`
* Warehouse: `warehouse@example.com` / `password`
* Finance: `finance@example.com` / `password`
* Marketing: `marketing@example.com` / `password`
