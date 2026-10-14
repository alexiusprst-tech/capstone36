import { reactive, watch } from 'vue';
import { WILAYAH_DKI } from '../data/wilayahDki';

const STORAGE_KEY = 'alpharo_integrated_system_v1';

// Initial realistic enterprise seed data for CV Alpharo Artha Jaya (Supplier & Contractor)
const defaultData = {
  wilayahDki: WILAYAH_DKI,
  currentUser: {
    id: 1,
    name: 'Budi Hartono, S.T.',
    email: 'marketing@alpharo.co.id',
    role: 'MARKETING',
    avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&auto=format&fit=crop&q=80',
    phone: '0812-3456-7890',
    division: 'Divisi Pemasaran & Kontrak',
  },
  roles: [
    { key: 'MARKETING', label: 'Marketing', email: 'marketing@alpharo.co.id', name: 'Budi Hartono, S.T.', division: 'Pemasaran & Kontrak' },
    { key: 'DIREKTUR', label: 'Direktur', email: 'direktur@alpharo.co.id', name: 'Ir. Alpharo Wijaya, M.M.', division: 'Direksi & Manajemen Eksekutif' },
    { key: 'ADMINISTRASI', label: 'Administrasi', email: 'admin@alpharo.co.id', name: 'Siti Rahmawati, S.Ak.', division: 'Administrasi & Legal Dokumen' },
    { key: 'WAREHOUSE', label: 'Warehouse', email: 'warehouse@alpharo.co.id', name: 'Agus Santoso', division: 'Logistik, Gudang & Pengiriman' },
    { key: 'FINANCE', label: 'Finance', email: 'finance@alpharo.co.id', name: 'Dewi Lestari, S.E.', division: 'Keuangan & Akuntansi' },
    { key: 'CLIENT', label: 'Client / Sekolah', email: 'client@alpharo.co.id', name: 'Kepala SMA Negeri 9 Jakarta', division: 'Rekanan Client / Instansi Pendidikan' },
  ],
  clients: [
    {
      id: 1,
      code: 'CS00001',
      name: 'SMA 9',
      orgFullName: 'SMA Negeri 9 Jakarta',
      orgShortName: 'SMAN 9',
      address: 'Jl. SMU 9, Halim Perdanakusuma, Kelurahan Kebon Pala, Kecamatan Makasar, Kota Jakarta Timur, DKI Jakarta',
      province: 'DKI JAKARTA',
      city: 'JAKARTA TIMUR',
      district: 'Makasar',
      subdistrict: 'Kebon Pala',
      postalCode: '13650',
      contactPerson: 'Bpk. supri',
      phone: '081316673264',
      email: 'adm@sma9jkt.sch.id',
      npwp: '01.234.567.8-001.000',
      letterheadAddress: 'Jl. SMU 9, Halim Perdanakusuma, Kebon Pala, Makasar, Jakarta Timur',
      letterheadCity: 'Jakarta Timur',
      orgPhone: '021-8091234',
      fax: '021-8091235',
      website: 'https://sma9jkt.sch.id',
      status: 'Aktif',
      type: 'Instansi Pendidikan',
      totalOrders: 3,
      totalTransaction: 54000000,
      createdAt: '2025-01-15',
    },
    {
      id: 2,
      code: 'CS00002',
      name: 'SMA 14 JAKARTA',
      orgFullName: 'SMA Negeri 14 Jakarta',
      orgShortName: 'SMAN 14',
      address: 'Jalan SMA XIV (atau Jl. SMA Barat / Jl. SMAN 14 Barat I No. 6), Kelurahan Cililitan / Cawang, Kecamatan Kramat Jati, Jakarta Timur',
      province: 'DKI JAKARTA',
      city: 'JAKARTA TIMUR',
      district: 'Kramat Jati',
      subdistrict: 'Cililitan',
      postalCode: '13640',
      contactPerson: 'Bu Nis Kepsek',
      phone: '087887744383',
      email: 'sman14dkijakarta@gmail.com',
      npwp: '01.234.567.8-002.000',
      letterheadAddress: 'Jalan SMA XIV, Kelurahan Cililitan, Kecamatan Kramat Jati, Jakarta Timur',
      letterheadCity: 'Jakarta Timur',
      orgPhone: '021-8092345',
      fax: '021-8092346',
      website: 'https://sman14jkt.sch.id',
      status: 'Aktif',
      type: 'Instansi Pendidikan',
      totalOrders: 2,
      totalTransaction: 32000000,
      createdAt: '2025-02-10',
    },
    {
      id: 3,
      code: 'CS00003',
      name: 'SMA PKP',
      orgFullName: 'SMA PKP Jakarta Islamic School',
      orgShortName: 'SMA PKP',
      address: 'I. Lkr. Pkp No.1, RT.1/RW.8, Klp. Dua Wetan, Kec. Ciracas, Kota Jakarta Timur, Daerah Khusus Ibukota Jakarta 13730',
      province: 'DKI JAKARTA',
      city: 'JAKARTA TIMUR',
      district: 'Ciracas',
      subdistrict: 'Kelapa Dua Wetan',
      postalCode: '13730',
      contactPerson: 'IBU SHIFA',
      phone: '083870292444',
      email: 'smapkp@yahoo.com',
      npwp: '01.234.567.8-003.000',
      letterheadAddress: 'Jl. Lkr. Pkp No.1, RT.1/RW.8, Klp. Dua Wetan, Ciracas, Jakarta Timur',
      letterheadCity: 'Jakarta Timur',
      orgPhone: '021-8710234',
      fax: '021-8710235',
      website: 'https://smapkp.sch.id',
      status: 'Aktif',
      type: 'Instansi Pendidikan',
      totalOrders: 1,
      totalTransaction: 15500000,
      createdAt: '2025-03-01',
    },
    {
      id: 4,
      code: 'CS00004',
      name: 'SMAN 30 JAKARTA',
      orgFullName: 'SMA Negeri 30 Jakarta',
      orgShortName: 'SMAN 30',
      address: 'Jl. Jend. A. Yani No.43A, Kelurahan Cempaka Putih Timur, Kecamatan Cempaka Putih, Jakarta Pusat, DKI Jakarta 10510',
      province: 'DKI JAKARTA',
      city: 'JAKARTA PUSAT',
      district: 'Cempaka Putih',
      subdistrict: 'Cempaka Putih Timur',
      postalCode: '10510',
      contactPerson: 'BU MURTI',
      phone: '081399224967',
      email: 'info@sman30-jkt.sch.id',
      npwp: '01.234.567.8-004.000',
      letterheadAddress: 'Jl. Jend. A. Yani No.43A, Cempaka Putih Timur, Jakarta Pusat',
      letterheadCity: 'Jakarta Pusat',
      orgPhone: '021-4241234',
      fax: '021-4241235',
      website: 'https://sman30-jkt.sch.id',
      status: 'Aktif',
      type: 'Instansi Pendidikan',
      totalOrders: 2,
      totalTransaction: 28000000,
      createdAt: '2025-03-15',
    },
    {
      id: 5,
      code: 'CS00005',
      name: 'SMAN 39 JAKARTA',
      orgFullName: 'SMA Negeri 39 Jakarta',
      orgShortName: 'SMAN 39',
      address: 'Jl. R.A. Fadillah, Cijantung, Kelurahan Baru, Kecamatan Pasar Rebo, Jakarta Timur, DKI Jakarta, kode pos 13780',
      province: 'DKI JAKARTA',
      city: 'JAKARTA TIMUR',
      district: 'Pasar Rebo',
      subdistrict: 'Baru',
      postalCode: '13780',
      contactPerson: 'BU EKA KEPSEK',
      phone: '081574326913',
      email: 'sman39jakarta@gmail.com',
      npwp: '01.234.567.8-005.000',
      letterheadAddress: 'Jl. R.A. Fadillah, Cijantung, Pasar Rebo, Jakarta Timur',
      letterheadCity: 'Jakarta Timur',
      orgPhone: '021-8770123',
      fax: '021-8770124',
      website: 'https://sman39jakarta.sch.id',
      status: 'Aktif',
      type: 'Instansi Pendidikan',
      totalOrders: 1,
      totalTransaction: 19000000,
      createdAt: '2025-04-02',
    },
    {
      id: 6,
      code: 'CS00006',
      name: 'SMAN 42 JAKARTA',
      orgFullName: 'SMA Negeri 42 Jakarta',
      orgShortName: 'SMAN 42',
      address: 'Jl. Rajawali Raya / Jl. Rajawali – Halim Perdanakusuma, Kelurahan Halim Perdanakusuma, Kecamatan Makasar, Jakarta Timur, 13610.',
      province: 'DKI JAKARTA',
      city: 'JAKARTA TIMUR',
      district: 'Makasar',
      subdistrict: 'Halim Perdanakusuma',
      postalCode: '13610',
      contactPerson: 'BU NURAY BENDAHARA',
      phone: '08561072635',
      email: 'sman42jaktim@gmail.com',
      npwp: '01.234.567.8-006.000',
      letterheadAddress: 'Jl. Rajawali Raya, Halim Perdanakusuma, Makasar, Jakarta Timur',
      letterheadCity: 'Jakarta Timur',
      orgPhone: '021-8001234',
      fax: '021-8001235',
      website: 'https://sman42jakarta.sch.id',
      status: 'Aktif',
      type: 'Instansi Pendidikan',
      totalOrders: 0,
      totalTransaction: 0,
      createdAt: '2025-04-18',
    },
    {
      id: 7,
      code: 'CS00007',
      name: 'SMAN 58 JAKARTA',
      orgFullName: 'SMA Negeri 58 Jakarta',
      orgShortName: 'SMAN 58',
      address: 'Jl. Raya Ciracas No. 2, RT.007/RW.003, Kelurahan Ciracas, Kecamatan Ciracas, Kota Jakarta Timur, DKI Jakarta 13740',
      province: 'DKI JAKARTA',
      city: 'JAKARTA TIMUR',
      district: 'Ciracas',
      subdistrict: 'Ciracas',
      postalCode: '13740',
      contactPerson: 'BU DIAH KEPSEK',
      phone: '081218012250',
      email: 'admin@sman58-jkt.sch.id',
      npwp: '01.234.567.8-007.000',
      letterheadAddress: 'Jl. Raya Ciracas No. 2, Ciracas, Jakarta Timur',
      letterheadCity: 'Jakarta Timur',
      orgPhone: '021-8711234',
      fax: '021-8711235',
      website: 'https://sman58-jkt.sch.id',
      status: 'Aktif',
      type: 'Instansi Pendidikan',
      totalOrders: 0,
      totalTransaction: 0,
      createdAt: '2025-05-01',
    },
    {
      id: 8,
      code: 'CS00008',
      name: 'SMAN 62 JAKARTA',
      orgFullName: 'SMA Negeri 62 Jakarta',
      orgShortName: 'SMAN 62',
      address: 'Jl. Raya Jakarta-Bogor, RT.13/RW.11, Kramat Jati, Kec. Kramat jati, Kota Jakarta Timur, Daerah Khusus Ibukota Jakarta 13540',
      province: 'DKI JAKARTA',
      city: 'JAKARTA TIMUR',
      district: 'Kramat Jati',
      subdistrict: 'Kramat Jati',
      postalCode: '13540',
      contactPerson: 'PAK WAHYU SMA 62',
      phone: '085213138444',
      email: 'sman62enamdua@gmail.com',
      npwp: '01.234.567.8-008.000',
      letterheadAddress: 'Jl. Raya Jakarta-Bogor, Kramat Jati, Jakarta Timur',
      letterheadCity: 'Jakarta Timur',
      orgPhone: '021-8093456',
      fax: '021-8093457',
      website: 'https://sman62jkt.sch.id',
      status: 'Aktif',
      type: 'Instansi Pendidikan',
      totalOrders: 0,
      totalTransaction: 0,
      createdAt: '2025-05-14',
    },
    {
      id: 9,
      code: 'CS00009',
      name: 'SMAN 64 JAKARTA',
      orgFullName: 'SMA Negeri 64 Jakarta',
      orgShortName: 'SMAN 64',
      address: 'Jl. Raya Cipayung, Cipayung, Kecamatan Cipayung, Kota Jakarta Timur, DKI Jakarta 13840',
      province: 'DKI JAKARTA',
      city: 'JAKARTA TIMUR',
      district: 'Cipayung',
      subdistrict: 'Cipayung',
      postalCode: '13840',
      contactPerson: 'BU INDAH SMA 64',
      phone: '081290565304',
      email: 'sman64jkt@gmail.com',
      npwp: '01.234.567.8-009.000',
      letterheadAddress: 'Jl. Raya Cipayung, Cipayung, Jakarta Timur',
      letterheadCity: 'Jakarta Timur',
      orgPhone: '021-8441234',
      fax: '021-8441235',
      website: 'https://sman64jkt.sch.id',
      status: 'Aktif',
      type: 'Instansi Pendidikan',
      totalOrders: 0,
      totalTransaction: 0,
      createdAt: '2025-05-20',
    },
    {
      id: 10,
      code: 'CS00010',
      name: 'SMAN 67 JAKARTA',
      orgFullName: 'SMA Negeri 67 Jakarta',
      orgShortName: 'SMAN 67',
      address: 'alan Squadron, Kelurahan Halim Perdanakusuma, Kecamatan Makasar, Jakarta Timur, DKI Jakarta 13610',
      province: 'DKI JAKARTA',
      city: 'JAKARTA TIMUR',
      district: 'Makasar',
      subdistrict: 'Halim Perdanakusuma',
      postalCode: '13610',
      contactPerson: 'PAK GATOT SMA 67',
      phone: '087757770321',
      email: 'info@sman67-jkt.sch.id',
      npwp: '01.234.567.8-010.000',
      letterheadAddress: 'Jl. Squadron, Halim Perdanakusuma, Makasar, Jakarta Timur',
      letterheadCity: 'Jakarta Timur',
      orgPhone: '021-8002345',
      fax: '021-8002346',
      website: 'https://sman67-jkt.sch.id',
      status: 'Aktif',
      type: 'Instansi Pendidikan',
      totalOrders: 0,
      totalTransaction: 0,
      createdAt: '2025-06-01',
    },
    {
      id: 11,
      code: 'CS00011',
      name: 'SMAN 68 JAKARTA',
      orgFullName: 'SMA Negeri 68 Jakarta',
      orgShortName: 'SMAN 68',
      address: 'Jalan Salemba Raya No. 18, Kelurahan Kenari, Kecamatan Senen, Kota Jakarta Pusat, DKI Jakarta 10430',
      province: 'DKI JAKARTA',
      city: 'JAKARTA PUSAT',
      district: 'Senen',
      subdistrict: 'Kenari',
      postalCode: '10430',
      contactPerson: 'BU YANI SMA 68',
      phone: '087896090000',
      email: 'sman68@gmail.com',
      npwp: '01.234.567.8-011.000',
      letterheadAddress: 'Jalan Salemba Raya No. 18, Kenari, Senen, Jakarta Pusat',
      letterheadCity: 'Jakarta Pusat',
      orgPhone: '021-3141234',
      fax: '021-3141235',
      website: 'https://sman68jkt.sch.id',
      status: 'Aktif',
      type: 'Instansi Pendidikan',
      totalOrders: 0,
      totalTransaction: 0,
      createdAt: '2025-06-15',
    },
    {
      id: 12,
      code: 'CS00012',
      name: 'SMAN 71 JAKARTA',
      orgFullName: 'SMA Negeri 71 Jakarta',
      orgShortName: 'SMAN 71',
      address: 'Jalan H. Dogol, Kavling TNI AL, Duren Sawit, Jakarta Timur, DKI Jakarta 13440',
      province: 'DKI JAKARTA',
      city: 'JAKARTA TIMUR',
      district: 'Duren Sawit',
      subdistrict: 'Duren Sawit',
      postalCode: '13440',
      contactPerson: 'BU ISOH',
      phone: '081311331171',
      email: 'smatujuhsatu@gmail.com',
      npwp: '01.234.567.8-012.000',
      letterheadAddress: 'Jalan H. Dogol, Kavling TNI AL, Duren Sawit, Jakarta Timur',
      letterheadCity: 'Jakarta Timur',
      orgPhone: '021-8611234',
      fax: '021-8611235',
      website: 'https://sman71jkt.sch.id',
      status: 'Aktif',
      type: 'Instansi Pendidikan',
      totalOrders: 0,
      totalTransaction: 0,
      createdAt: '2025-07-01',
    },
    {
      id: 13,
      code: 'CS00013',
      name: 'SMAN 76 JAKARTA',
      orgFullName: 'SMA Negeri 76 Jakarta',
      orgShortName: 'SMAN 76',
      address: 'Jl. Tipar Cakung (Jl. Inspeksi PAM RT.10/RW.07), Kelurahan Cakung Barat, Kecamatan Cakung, Jakarta Timur',
      province: 'DKI JAKARTA',
      city: 'JAKARTA TIMUR',
      district: 'Cakung',
      subdistrict: 'Cakung Barat',
      postalCode: '13910',
      contactPerson: 'BU YAYUK SMA 76',
      phone: '081314885995',
      email: 'tu.sman76jakarta@gmail.com',
      npwp: '01.234.567.8-013.000',
      letterheadAddress: 'Jl. Tipar Cakung, Cakung Barat, Cakung, Jakarta Timur',
      letterheadCity: 'Jakarta Timur',
      orgPhone: '021-4601234',
      fax: '021-4601235',
      website: 'https://sman76jkt.sch.id',
      status: 'Aktif',
      type: 'Instansi Pendidikan',
      totalOrders: 0,
      totalTransaction: 0,
      createdAt: '2025-07-10',
    },
    {
      id: 14,
      code: 'CS00014',
      name: 'SMPN 106 JAKARTA',
      orgFullName: 'SMP Negeri 106 Jakarta',
      orgShortName: 'SMPN 106',
      address: 'Jakarta',
      province: 'DKI JAKARTA',
      city: 'JAKARTA TIMUR',
      district: 'Ciracas',
      subdistrict: 'Kelapa Dua Wetan',
      postalCode: '13730',
      contactPerson: 'Pa Ujang',
      phone: '0812584184814',
      email: 'mail@smpn106jkt.sch.id',
      npwp: '01.234.567.8-014.000',
      letterheadAddress: 'Jl. SMPN 106, Ciracas, Jakarta Timur',
      letterheadCity: 'Jakarta Timur',
      orgPhone: '021-8701234',
      fax: '021-8701235',
      website: 'https://smpn106jkt.sch.id',
      status: 'Aktif',
      type: 'Instansi Pendidikan',
      totalOrders: 0,
      totalTransaction: 0,
      createdAt: '2025-07-22',
    },
    {
      id: 15,
      code: 'CS00015',
      name: 'SMPN 188 JAKARTA',
      orgFullName: 'SMP Negeri 188 Jakarta',
      orgShortName: 'SMPN 188',
      address: 'Jalan SMP 188 Kelurahan Rambutan, Kecamatan Ciracas - Jakarta Timur',
      province: 'DKI JAKARTA',
      city: 'JAKARTA TIMUR',
      district: 'Ciracas',
      subdistrict: 'Rambutan',
      postalCode: '13830',
      contactPerson: 'Parsono, M.Pd',
      phone: '021-8403136',
      email: 'smpn188kprambutan@gmail.com',
      npwp: '01.234.567.8-015.000',
      letterheadAddress: 'Jalan SMP 188 Kelurahan Rambutan, Kecamatan Ciracas - Jakarta Timur',
      letterheadCity: 'Jakarta Timur',
      orgPhone: '021-8403136',
      fax: '021-8403137',
      website: 'https://smpn188jkt.sch.id',
      status: 'Aktif',
      type: 'Instansi Pendidikan',
      totalOrders: 0,
      totalTransaction: 0,
      createdAt: '2025-08-01',
    },
    {
      id: 16,
      code: 'CS00016',
      name: 'SMA NEGRI 2 BOGOR',
      orgFullName: 'SMA Negeri 2 Bogor',
      orgShortName: 'SMAN 2 BOGOR',
      address: 'Jl. Kranji Ujung No.1, RT.03/RW.04, Budi Agung, Tanah Sareal, Kota Bogor, Jawa Barat 16165',
      province: 'JAWA BARAT',
      city: 'BOGOR',
      district: 'Tanah Sareal',
      subdistrict: 'Kedungbadak',
      postalCode: '16165',
      contactPerson: 'Endang Suhendar',
      phone: '(0251) 8318761',
      email: 'smandabogor@yahoo.co.id',
      npwp: '01.234.567.8-016.000',
      letterheadAddress: 'Jl. Kranji Ujung No.1, RT.03/RW.04, Budi Agung, Tanah Sareal, Kota Bogor',
      letterheadCity: 'Kota Bogor',
      orgPhone: '(0251) 8318761',
      fax: '(0251) 8318762',
      website: 'https://sman2bogor.sch.id',
      status: 'Aktif',
      type: 'Instansi Pendidikan',
      totalOrders: 0,
      totalTransaction: 0,
      createdAt: '2025-08-15',
    },
  ],
  products: [
    {
      id: 1,
      sku: 'MAT-001',
      name: 'Semen Portland Tipe 1 (50kg)',
      category: 'Material Konstruksi',
      unit: 'Sak',
      stock: 450,
      minStock: 100,
      price: 68000,
      status: 'Tersedia',
      location: 'Gudang Utama A-01',
    },
    {
      id: 2,
      sku: 'MAT-002',
      name: 'Besi Beton Ulir D16 (12m)',
      category: 'Besi & Baja',
      unit: 'Batang',
      stock: 320,
      minStock: 50,
      price: 145000,
      status: 'Tersedia',
      location: 'Gudang Logam B-03',
    },
    {
      id: 3,
      sku: 'MAT-003',
      name: 'Besi Beton Polos D10 (12m)',
      category: 'Besi & Baja',
      unit: 'Batang',
      stock: 18,
      minStock: 80,
      price: 78000,
      status: 'Menipis',
      location: 'Gudang Logam B-01',
    },
    {
      id: 4,
      sku: 'MAT-004',
      name: 'Pasir Pasang Ekstra Bangunan',
      category: 'Material Alam',
      unit: 'M3',
      stock: 85,
      minStock: 20,
      price: 320000,
      status: 'Tersedia',
      location: 'Area Terbuka Yard C',
    },
    {
      id: 5,
      sku: 'MAT-005',
      name: 'Cat Epoxy Floor Heavy Duty (20L)',
      category: 'Finishing & Cat',
      unit: 'Pail',
      stock: 0,
      minStock: 10,
      price: 1850000,
      status: 'Tidak Tersedia',
      location: 'Gudang Kimia D-02',
    },
    {
      id: 6,
      sku: 'MAT-006',
      name: 'Pipa PVC Conduit AW 3/4" (4m)',
      category: 'MEP & Plumbing',
      unit: 'Batang',
      stock: 620,
      minStock: 150,
      price: 42000,
      status: 'Tersedia',
      location: 'Rak Pipa E-05',
    },
    {
      id: 7,
      sku: 'MAT-007',
      name: 'Granit Tile 60x60 Polished Glazed',
      category: 'Finishing Lantai',
      unit: 'Dus',
      stock: 140,
      minStock: 40,
      price: 215000,
      status: 'Tersedia',
      location: 'Gudang Utama A-08',
    },
    {
      id: 8,
      sku: 'JSA-001',
      name: 'Jasa Pengecoran Ready Mix K-300',
      category: 'Jasa Konstruksi',
      unit: 'M3',
      stock: 9999,
      minStock: 0,
      price: 850000,
      status: 'Tersedia',
      location: 'Layanan Lapangan',
    },
  ],
  orders: [
    {
      id: 1,
      orderNumber: 'ORD-2026-001',
      clientId: 1,
      clientName: 'SMA 9',
      projectName: 'Pembangunan Fasilitas Workshop & Lab Vokasi',
      date: '2026-10-01',
      submittedBy: 'Budi Hartono, S.T.',
      status: 'Menunggu Pembayaran', // Draft, Menunggu Verifikasi, Disetujui, Ditolak, Diproses, Menunggu Pembayaran, Selesai
      isHold: false,
      holdReason: null,
      holdBy: null,
      holdDate: null,
      followUpLogs: [
        {
          id: 1,
          date: '2026-10-06 14:00',
          contactPerson: 'Ir. Hendra Saputra (Procurement WIKA)',
          method: 'Telepon & WhatsApp',
          outcome: 'Berkas BAST sudah diserahkan ke bagian Keuangan Proyek WIKA.',
          promisedPaymentDate: '2026-10-25',
          notes: 'Pembayaran termin 2 dijadwalkan cair sebelum 25 Oktober sesuai SPK.',
          recordedBy: 'Budi Hartono, S.T. (Marketing)',
        },
      ],
      approvedBy: 'Ir. Alpharo Wijaya, M.M.',
      approvalDate: '2026-10-02 09:30',
      approvalNotes: 'Spesifikasi telah divalidasi dengan konsultan perencana.',
      items: [
        { productId: 2, sku: 'MAT-002', name: 'Besi Beton Ulir D16 (12m)', qty: 200, unit: 'Batang', price: 145000, total: 29000000 },
        { productId: 1, sku: 'MAT-001', name: 'Semen Portland Tipe 1 (50kg)', qty: 300, unit: 'Sak', price: 68000, total: 20400000 },
      ],
      subtotal: 49400000,
      tax: 5434000, // PPN 11%
      grandTotal: 54834000,
      notes: 'Pengiriman langsung ke lokasi proyek Cipinang. Koordinasi dengan Site Manager 1 hari sebelumnya.',
      timeline: [
        { stage: 'Order Dibuat', user: 'Budi Hartono (Marketing)', timestamp: '2026-10-01 10:15', status: 'completed', notes: 'Draft order dibuat berdasarkan PO request client.' },
        { stage: 'Menunggu Verifikasi', user: 'System', timestamp: '2026-10-01 14:00', status: 'completed', notes: 'Diajukan ke Direktur untuk telaah margin & ketersediaan.' },
        { stage: 'Disetujui Direktur', user: 'Ir. Alpharo Wijaya (Direktur)', timestamp: '2026-10-02 09:30', status: 'completed', notes: 'Disetujui penuh untuk eksekusi administrasi dan gudang.' },
        { stage: 'Dokumen Diproses', user: 'Siti Rahmawati (Administrasi)', timestamp: '2026-10-02 11:20', status: 'completed', notes: 'PO resmi, Surat Jalan, dan BAST diterbitkan.' },
        { stage: 'Barang Dipenuhi', user: 'Agus Santoso (Warehouse)', timestamp: '2026-10-03 08:45', status: 'completed', notes: 'Stok dialokasikan dan dipacking di loading bay.' },
        { stage: 'Dikirim', user: 'Agus Santoso (Warehouse)', timestamp: '2026-10-03 13:00', status: 'completed', notes: 'Truk N-8841-AA berangkat ke Cipinang.' },
        { stage: 'Invoice Dibuat', user: 'Dewi Lestari (Finance)', timestamp: '2026-10-04 10:00', status: 'completed', notes: 'Invoice INV-2026-001 diterbitkan.' },
        { stage: 'Pembayaran', user: 'Dewi Lestari (Finance)', timestamp: '2026-10-05 14:15', status: 'in_progress', notes: 'Termin 1 (DP 50%) diterima via BCA. Status order: Menunggu Pembayaran.' },
        { stage: 'Follow-Up Pembayaran Client', user: 'Budi Hartono (Marketing)', timestamp: '2026-10-06 14:00', status: 'completed', notes: 'Hasil: Berkas BAST sudah diverifikasi. Janji Bayar: 2026-10-25' },
        { stage: 'Selesai', user: '-', timestamp: '-', status: 'pending', notes: 'Menunggu pelunasan termin 2.' },
      ],
      documents: {
        po: { exists: true, number: 'PO-2026-001', status: 'Lengkap', date: '2026-10-02', file: 'PO_WIKA_ORD001.pdf' },
        suratJalan: { exists: true, number: 'SJ-2026-001', status: 'Lengkap', date: '2026-10-03', file: 'SJ_ORD001_031026.pdf' },
        bast: { exists: true, number: 'BAST-2026-001', status: 'Lengkap', date: '2026-10-04', file: 'BAST_WIKA_001.pdf' },
        faktur: { exists: true, number: 'FK-2026-001', status: 'Lengkap', date: '2026-10-04', file: 'FAKTUR_PAJAK_001.pdf' },
      },
      fulfillmentStatus: 'Siap Dikirim', // Tersedia, Tidak Tersedia, Diproses, Siap Dikirim, Sedang Dikirim, Selesai
    },
    {
      id: 2,
      orderNumber: 'ORD-2026-002',
      clientId: 2,
      clientName: 'SMA 14 JAKARTA',
      projectName: 'Pekerjaan Pasangan & Struktur Jembatan Kanal',
      date: '2026-10-04',
      submittedBy: 'Budi Hartono, S.T.',
      status: 'Menunggu Verifikasi',
      approvedBy: null,
      approvalDate: null,
      approvalNotes: null,
      items: [
        { productId: 4, sku: 'MAT-004', name: 'Pasir Pasang Ekstra Bangunan', qty: 40, unit: 'M3', price: 320000, total: 12800000 },
        { productId: 2, sku: 'MAT-002', name: 'Besi Beton Ulir D16 (12m)', qty: 100, unit: 'Batang', price: 145000, total: 14500000 },
      ],
      subtotal: 27300000,
      tax: 3003000,
      grandTotal: 30303000,
      notes: 'Kebutuhan mendesak untuk pengecoran akhir pekan. Memohon approval prioritas.',
      timeline: [
        { stage: 'Order Dibuat', user: 'Budi Hartono (Marketing)', timestamp: '2026-10-04 09:00', status: 'completed', notes: 'Draft diajukan oleh Marketing.' },
        { stage: 'Menunggu Verifikasi', user: 'System', timestamp: '2026-10-04 09:10', status: 'in_progress', notes: 'Sedang menunggu review & persetujuan Direktur.' },
        { stage: 'Disetujui Direktur', user: '-', timestamp: '-', status: 'pending', notes: '-' },
        { stage: 'Dokumen Diproses', user: '-', timestamp: '-', status: 'pending', notes: '-' },
        { stage: 'Barang Dipenuhi', user: '-', timestamp: '-', status: 'pending', notes: '-' },
        { stage: 'Dikirim', user: '-', timestamp: '-', status: 'pending', notes: '-' },
        { stage: 'Invoice Dibuat', user: '-', timestamp: '-', status: 'pending', notes: '-' },
        { stage: 'Pembayaran', user: '-', timestamp: '-', status: 'pending', notes: '-' },
        { stage: 'Selesai', user: '-', timestamp: '-', status: 'pending', notes: '-' },
      ],
      documents: {
        po: { exists: true, number: 'PO-2026-002', status: 'Draft', date: '2026-10-04', file: 'Draft_PO_MKP_002.pdf' },
        suratJalan: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
        bast: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
        faktur: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
      },
      fulfillmentStatus: 'Diproses',
    },
    {
      id: 3,
      orderNumber: 'ORD-2026-003',
      clientId: 3,
      clientName: 'SMA PKP',
      projectName: 'Instalasi Jalur Kabel & Gardu Distribusi',
      date: '2026-10-05',
      submittedBy: 'Budi Hartono, S.T.',
      status: 'Diproses',
      approvedBy: 'Ir. Alpharo Wijaya, M.M.',
      approvalDate: '2026-10-05 11:00',
      approvalNotes: 'Disetujui. Pastikan sertifikat uji conduit disertakan dalam pengiriman.',
      items: [
        { productId: 6, sku: 'MAT-006', name: 'Pipa PVC Conduit AW 3/4" (4m)', qty: 200, unit: 'Batang', price: 42000, total: 8400000 },
        { productId: 1, sku: 'MAT-001', name: 'Semen Portland Tipe 1 (50kg)', qty: 150, unit: 'Sak', price: 68000, total: 10200000 },
      ],
      subtotal: 18600000,
      tax: 2046000,
      grandTotal: 20646000,
      notes: 'Alamat pengiriman Site Gardu Cikarang Barat.',
      timeline: [
        { stage: 'Order Dibuat', user: 'Budi Hartono (Marketing)', timestamp: '2026-10-05 08:30', status: 'completed', notes: 'Order berhasil di-submit.' },
        { stage: 'Menunggu Verifikasi', user: 'System', timestamp: '2026-10-05 08:40', status: 'completed', notes: 'Menunggu konfirmasi direktur.' },
        { stage: 'Disetujui Direktur', user: 'Ir. Alpharo Wijaya (Direktur)', timestamp: '2026-10-05 11:00', status: 'completed', notes: 'Disetujui dengan catatan sertifikasi pipa.' },
        { stage: 'Dokumen Diproses', user: 'Siti Rahmawati (Administrasi)', timestamp: '2026-10-05 13:20', status: 'in_progress', notes: 'PO diterbitkan, Surat Jalan dalam drafting.' },
        { stage: 'Barang Dipenuhi', user: 'Agus Santoso (Warehouse)', timestamp: '2026-10-05 15:00', status: 'in_progress', notes: 'Pengecekan fisik barang di rak E-05 & A-01.' },
        { stage: 'Dikirim', user: '-', timestamp: '-', status: 'pending', notes: '-' },
        { stage: 'Invoice Dibuat', user: '-', timestamp: '-', status: 'pending', notes: '-' },
        { stage: 'Pembayaran', user: '-', timestamp: '-', status: 'pending', notes: '-' },
        { stage: 'Selesai', user: '-', timestamp: '-', status: 'pending', notes: '-' },
      ],
      documents: {
        po: { exists: true, number: 'PO-2026-003', status: 'Lengkap', date: '2026-10-05', file: 'PO_NEA_003.pdf' },
        suratJalan: { exists: true, number: 'SJ-2026-003', status: 'Diproses', date: '2026-10-05', file: 'SJ_DRAFT_003.pdf' },
        bast: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
        faktur: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
      },
      fulfillmentStatus: 'Diproses',
    },
    {
      id: 4,
      orderNumber: 'ORD-2026-004',
      clientId: 4,
      clientName: 'SMAN 30 JAKARTA',
      projectName: 'Pemasangan Granit Kantor Operasional',
      date: '2026-10-06',
      submittedBy: 'Budi Hartono, S.T.',
      status: 'Draft',
      approvedBy: null,
      approvalDate: null,
      approvalNotes: null,
      items: [
        { productId: 7, sku: 'MAT-007', name: 'Granit Tile 60x60 Polished Glazed', qty: 40, unit: 'Dus', price: 215000, total: 8600000 },
        { productId: 1, sku: 'MAT-001', name: 'Semen Portland Tipe 1 (50kg)', qty: 20, unit: 'Sak', price: 68000, total: 13600000 },
      ],
      subtotal: 9960000,
      tax: 1095600,
      grandTotal: 11055600,
      notes: 'Masih menunggu revisi volume meter persegi dari mandor kontraktor.',
      timeline: [
        { stage: 'Order Dibuat', user: 'Budi Hartono (Marketing)', timestamp: '2026-10-06 11:00', status: 'in_progress', notes: 'Draft disimpan.' },
        { stage: 'Menunggu Verifikasi', user: '-', timestamp: '-', status: 'pending', notes: '-' },
        { stage: 'Disetujui Direktur', user: '-', timestamp: '-', status: 'pending', notes: '-' },
        { stage: 'Dokumen Diproses', user: '-', timestamp: '-', status: 'pending', notes: '-' },
        { stage: 'Barang Dipenuhi', user: '-', timestamp: '-', status: 'pending', notes: '-' },
        { stage: 'Dikirim', user: '-', timestamp: '-', status: 'pending', notes: '-' },
        { stage: 'Invoice Dibuat', user: '-', timestamp: '-', status: 'pending', notes: '-' },
        { stage: 'Pembayaran', user: '-', timestamp: '-', status: 'pending', notes: '-' },
        { stage: 'Selesai', user: '-', timestamp: '-', status: 'pending', notes: '-' },
      ],
      documents: {
        po: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
        suratJalan: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
        bast: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
        faktur: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
      },
      fulfillmentStatus: 'Tersedia',
    },
    {
      id: 5,
      orderNumber: 'ORD-2026-005',
      clientId: 5,
      clientName: 'Dinas PUPR Wilayah Jawa Timur',
      projectName: 'Peningkatan Jalan & Saluran Drainase Paket 3',
      date: '2026-09-18',
      submittedBy: 'Budi Hartono, S.T.',
      status: 'Selesai',
      approvedBy: 'Ir. Alpharo Wijaya, M.M.',
      approvalDate: '2026-09-19 14:00',
      approvalNotes: 'Disetujui sesuai SPK Dinas No. 602/PUPR/IX/2026.',
      items: [
        { productId: 8, sku: 'JSA-001', name: 'Jasa Pengecoran Ready Mix K-300', qty: 100, unit: 'M3', price: 850000, total: 85000000 },
        { productId: 2, sku: 'MAT-002', name: 'Besi Beton Ulir D16 (12m)', qty: 150, unit: 'Batang', price: 145000, total: 21750000 },
      ],
      subtotal: 106750000,
      tax: 11742500,
      grandTotal: 118492500,
      notes: 'Pekerjaan tuntas 100% dan telah lulus uji kuat tekan beton.',
      timeline: [
        { stage: 'Order Dibuat', user: 'Marketing', timestamp: '2026-09-18 09:00', status: 'completed', notes: 'Order dibuat.' },
        { stage: 'Menunggu Verifikasi', user: 'System', timestamp: '2026-09-18 09:30', status: 'completed', notes: 'Verifikasi sistem.' },
        { stage: 'Disetujui Direktur', user: 'Direktur', timestamp: '2026-09-19 14:00', status: 'completed', notes: 'Disetujui.' },
        { stage: 'Dokumen Diproses', user: 'Administrasi', timestamp: '2026-09-20 10:00', status: 'completed', notes: 'PO, SJ, BAST selesai.' },
        { stage: 'Barang Dipenuhi', user: 'Warehouse', timestamp: '2026-09-22 08:00', status: 'completed', notes: 'Material & Ready Mix terdistribusi.' },
        { stage: 'Dikirim', user: 'Warehouse', timestamp: '2026-09-23 11:00', status: 'completed', notes: 'Pengiriman selesai.' },
        { stage: 'Invoice Dibuat', user: 'Finance', timestamp: '2026-09-24 13:00', status: 'completed', notes: 'Invoice INV-2026-000 diterbitkan.' },
        { stage: 'Pembayaran', user: 'Finance', timestamp: '2026-09-29 16:00', status: 'completed', notes: 'Pelunasan seluruh termin diterima.' },
        { stage: 'Selesai', user: 'Direktur', timestamp: '2026-09-30 10:00', status: 'completed', notes: 'Project resmi ditutup tuntas.' },
      ],
      documents: {
        po: { exists: true, number: 'PO-2026-000', status: 'Lengkap', date: '2026-09-20', file: 'PO_PUPR_FINAL.pdf' },
        suratJalan: { exists: true, number: 'SJ-2026-000', status: 'Lengkap', date: '2026-09-23', file: 'SJ_PUPR_FINAL.pdf' },
        bast: { exists: true, number: 'BAST-2026-000', status: 'Lengkap', date: '2026-09-25', file: 'BAST_PUPR_FINAL.pdf' },
        faktur: { exists: true, number: 'FK-2026-000', status: 'Lengkap', date: '2026-09-26', file: 'FAKTUR_PUPR.pdf' },
      },
      fulfillmentStatus: 'Selesai',
    },
    {
      id: 6,
      orderNumber: 'ORD-2026-006',
      clientId: 2,
      clientName: 'CV Mitra Konstruksi Perkasa',
      projectName: 'Pekerjaan Pengecatan Lantai Pabrik',
      date: '2026-09-25',
      submittedBy: 'Budi Hartono, S.T.',
      status: 'Ditolak',
      approvedBy: 'Ir. Alpharo Wijaya, M.M.',
      approvalDate: '2026-09-26 10:15',
      approvalNotes: 'Ditolak: Stok Cat Epoxy MAT-005 saat ini kosong dan client meminta termin pembayaran tanpa DP melebihi batas kebijakan kredit.',
      items: [
        { productId: 5, sku: 'MAT-005', name: 'Cat Epoxy Floor Heavy Duty (20L)', qty: 15, unit: 'Pail', price: 1850000, total: 27750000 },
      ],
      subtotal: 27750000,
      tax: 3052500,
      grandTotal: 30802500,
      notes: 'Permintaan kredit 60 hari.',
      timeline: [
        { stage: 'Order Dibuat', user: 'Budi Hartono (Marketing)', timestamp: '2026-09-25 14:00', status: 'completed', notes: 'Draft order diajukan.' },
        { stage: 'Menunggu Verifikasi', user: 'System', timestamp: '2026-09-25 14:05', status: 'completed', notes: 'Menunggu telaah Direktur.' },
        { stage: 'Ditolak Direktur', user: 'Ir. Alpharo Wijaya (Direktur)', timestamp: '2026-09-26 10:15', status: 'completed', notes: 'Ditolak karena stok OOS dan termin risiko tinggi.' },
      ],
      documents: {
        po: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
        suratJalan: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
        bast: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
        faktur: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
      },
      fulfillmentStatus: 'Tidak Tersedia',
    },
  ],
  warehouseFulfillments: [
    {
      id: 1,
      orderNumber: 'ORD-2026-001',
      clientName: 'SMA 9',
      items: [
        { sku: 'MAT-002', name: 'Besi Beton Ulir D16 (12m)', reqQty: 200, availQty: 320, fulfilledQty: 200, unit: 'Batang' },
        { sku: 'MAT-001', name: 'Semen Portland Tipe 1 (50kg)', reqQty: 300, availQty: 450, fulfilledQty: 300, unit: 'Sak' },
      ],
      status: 'Siap Dikirim', // Tersedia, Tidak Tersedia, Diproses, Siap Dikirim, Sedang Dikirim, Selesai
      checkedBy: 'Agus Santoso',
      notes: 'Packaging pallet kayu dan strapping baja siap muat.',
    },
    {
      id: 2,
      orderNumber: 'ORD-2026-003',
      clientName: 'SMA PKP',
      items: [
        { sku: 'MAT-006', name: 'Pipa PVC Conduit AW 3/4" (4m)', reqQty: 200, availQty: 620, fulfilledQty: 200, unit: 'Batang' },
        { sku: 'MAT-001', name: 'Semen Portland Tipe 1 (50kg)', reqQty: 150, availQty: 450, fulfilledQty: 150, unit: 'Sak' },
      ],
      status: 'Diproses',
      checkedBy: 'Agus Santoso',
      notes: 'Sedang dikelompokkan di bay logistik 2.',
    },
  ],
  shipments: [
    {
      id: 1,
      shipmentNumber: 'SHP-2026-001',
      orderNumber: 'ORD-2026-001',
      clientName: 'SMA 9',
      suratJalanNumber: 'SJ-2026-001',
      destination: 'Jl. D.I. Panjaitan Kav. 9-10, Cipinang, Jakarta Timur',
      expedition: 'Armada Internal CV Alpharo (Truk Tronton N-8841-AA)',
      driverName: 'Joko Purwanto',
      driverPhone: '0812-7711-2233',
      departureDate: '2026-10-03 13:00',
      estimatedArrival: '2026-10-04 10:00',
      status: 'Sedang Dikirim', // Siap Dikirim, Sedang Dikirim, Selesai
      notes: 'Pengemudi membawa fisik Surat Jalan dan BAST asli 3 rangkap.',
    },
    {
      id: 2,
      shipmentNumber: 'SHP-2026-000',
      orderNumber: 'ORD-2026-005',
      clientName: 'Dinas PUPR Wilayah Jawa Timur',
      suratJalanNumber: 'SJ-2026-000',
      destination: 'Lokasi Proyek Gayungan, Surabaya',
      expedition: 'Mixer Truck & Flatbed Crane',
      driverName: 'Bambang Sudiro',
      driverPhone: '0813-9922-3344',
      departureDate: '2026-09-23 11:00',
      estimatedArrival: '2026-09-23 14:00',
      status: 'Selesai',
      notes: 'Diterima oleh PPK PUPR Bpk. Rahmat Hidayat. BAST telah ditandatangani basah.',
    },
  ],
  stockMovements: [
    { id: 1, date: '2026-10-03 12:45', sku: 'MAT-002', name: 'Besi Beton Ulir D16', type: 'Keluar', qty: 200, unit: 'Batang', reference: 'ORD-2026-001', pic: 'Agus Santoso' },
    { id: 2, date: '2026-10-03 12:50', sku: 'MAT-001', name: 'Semen Portland Tipe 1', type: 'Keluar', qty: 300, unit: 'Sak', reference: 'ORD-2026-001', pic: 'Agus Santoso' },
    { id: 3, date: '2026-10-02 08:30', sku: 'MAT-006', name: 'Pipa PVC Conduit AW 3/4"', type: 'Masuk', qty: 500, unit: 'Batang', reference: 'PO-SUPPLIER-098', pic: 'Agus Santoso' },
    { id: 4, date: '2026-10-01 16:00', sku: 'MAT-003', name: 'Besi Beton Polos D10', type: 'Penyesuaian', qty: -5, unit: 'Batang', reference: 'OPNAME-OKT-01', pic: 'Agus Santoso' },
  ],
  warehouseRequests: [
    {
      id: 1,
      requestCode: 'REQ-2026-001',
      date: '2026-10-02',
      requestedBy: 'Agus Santoso (Warehouse)',
      purpose: 'Restock Pengadaan Bahan Habis & Proyeksi Order Q4',
      status: 'Diproses', // Draft, Menunggu Verifikasi, Disetujui, Diproses, Selesai
      items: [
        { sku: 'MAT-005', name: 'Cat Epoxy Floor Heavy Duty (20L)', qty: 25, unit: 'Pail', estPrice: 1750000, total: 43750000 },
        { sku: 'MAT-003', name: 'Besi Beton Polos D10 (12m)', qty: 100, unit: 'Batang', estPrice: 75000, total: 7500000 },
      ],
      totalEstCost: 51250000,
    },
  ],
  invoices: [
    {
      id: 1,
      invoiceNumber: 'INV-2026-001',
      orderNumber: 'ORD-2026-001',
      clientId: 1,
      clientName: 'SMA 9',
      invoiceDate: '2026-10-04',
      dueDate: '2026-10-25',
      totalAmount: 54834000,
      paidAmount: 25000000,
      remainingAmount: 29834000,
      status: 'Sebagian / Termin', // Belum Dibayar, Sebagian / Termin, Lunas, Jatuh Tempo
      paymentType: 'Termin', // Penuh, Termin
      terminCount: 2,
      termins: [
        { termNumber: 1, amount: 25000000, dueDate: '2026-10-05', status: 'Lunas', paidDate: '2026-10-05', method: 'Transfer BCA' },
        { termNumber: 2, amount: 29834000, dueDate: '2026-10-25', status: 'Belum Dibayar', paidDate: null, method: '-' },
      ],
    },
    {
      id: 2,
      invoiceNumber: 'INV-2026-002',
      orderNumber: 'ORD-2026-003',
      clientId: 3,
      clientName: 'SMA PKP',
      invoiceDate: '2026-10-06',
      dueDate: '2026-10-20',
      totalAmount: 20646000,
      paidAmount: 0,
      remainingAmount: 20646000,
      status: 'Belum Dibayar',
      paymentType: 'Penuh',
      terminCount: 1,
      termins: [
        { termNumber: 1, amount: 20646000, dueDate: '2026-10-20', status: 'Belum Dibayar', paidDate: null, method: '-' },
      ],
    },
    {
      id: 3,
      invoiceNumber: 'INV-2026-000',
      orderNumber: 'ORD-2026-005',
      clientId: 5,
      clientName: 'Dinas PUPR Wilayah Jawa Timur',
      invoiceDate: '2026-09-24',
      dueDate: '2026-09-30',
      totalAmount: 118492500,
      paidAmount: 118492500,
      remainingAmount: 0,
      status: 'Lunas',
      paymentType: 'Termin',
      terminCount: 3,
      termins: [
        { termNumber: 1, amount: 35000000, dueDate: '2026-09-25', status: 'Lunas', paidDate: '2026-09-25', method: 'Bank Jatim BSB' },
        { termNumber: 2, amount: 45000000, dueDate: '2026-09-28', status: 'Lunas', paidDate: '2026-09-28', method: 'Bank Jatim BSB' },
        { termNumber: 3, amount: 38492500, dueDate: '2026-09-30', status: 'Lunas', paidDate: '2026-09-30', method: 'Bank Jatim BSB' },
      ],
    },
    {
      id: 4,
      invoiceNumber: 'INV-2025-089',
      orderNumber: 'ORD-2025-089',
      clientId: 2,
      clientName: 'SMA 14 JAKARTA',
      invoiceDate: '2026-08-15',
      dueDate: '2026-09-15',
      totalAmount: 38500000,
      paidAmount: 10000000,
      remainingAmount: 28500000,
      status: 'Jatuh Tempo',
      paymentType: 'Termin',
      terminCount: 2,
      termins: [
        { termNumber: 1, amount: 10000000, dueDate: '2026-08-20', status: 'Lunas', paidDate: '2026-08-22', method: 'Transfer Mandiri' },
        { termNumber: 2, amount: 28500000, dueDate: '2026-09-15', status: 'Jatuh Tempo', paidDate: null, method: '-' },
      ],
    },
  ],
  payments: [
    {
      id: 1,
      paymentNumber: 'PAY-2026-001',
      invoiceNumber: 'INV-2026-001',
      clientName: 'SMA 9',
      totalInvoice: 54834000,
      paymentType: 'Pembayaran Termin', // Pembayaran Penuh, Pembayaran Termin
      terminSequence: 'Termin Ke-1 (Uang Muka 50%)',
      amount: 25000000,
      remainingBalance: 29834000,
      paymentDate: '2026-10-05',
      paymentMethod: 'Transfer Bank BCA',
      referenceNumber: 'TRF-BCA-88192039',
      proofFile: 'BUKTI_TRANSFER_WIKA_T1.pdf',
      status: 'Lunas', // Lunas, Menunggu Verifikasi
      recordedBy: 'Dewi Lestari, S.E. (Finance)',
      notes: 'Diverifikasi masuk ke Rekening CV Alpharo Bank BCA No. 142-990-1288.',
    },
    {
      id: 2,
      paymentNumber: 'PAY-2026-000',
      invoiceNumber: 'INV-2026-000',
      clientName: 'Dinas PUPR Wilayah Jawa Timur',
      totalInvoice: 118492500,
      paymentType: 'Pembayaran Termin',
      terminSequence: 'Termin Ke-3 (Pelunasan Akhir)',
      amount: 38492500,
      remainingBalance: 0,
      paymentDate: '2026-09-30',
      paymentMethod: 'SP2D Kas Daerah Bank Jatim',
      referenceNumber: 'SP2D-PUPR-99201',
      proofFile: 'SP2D_BANKJATIM_PUPR.pdf',
      status: 'Lunas',
      recordedBy: 'Dewi Lestari, S.E. (Finance)',
      notes: 'SP2D telah cair dan terekonsiliasi.',
    },
  ],
  notifications: [
    {
      id: 1,
      role: 'DIREKTUR',
      title: 'Order Baru Menunggu Verifikasi',
      message: 'Order ORD-2026-002 dari CV Mitra Konstruksi Perkasa membutuhkan telaah dan persetujuan Anda.',
      time: '10 menit lalu',
      isRead: false,
      category: 'approval',
      link: '/director/verification',
    },
    {
      id: 2,
      role: 'ADMINISTRASI',
      title: 'Dokumen Belum Lengkap',
      message: 'Order ORD-2026-003 membutuhkan kelengkapan Surat Jalan & Faktur.',
      time: '45 menit lalu',
      isRead: false,
      category: 'document',
      link: '/administration/spj',
    },
    {
      id: 3,
      role: 'WAREHOUSE',
      title: 'Peringatan Stok Menipis',
      message: 'Besi Beton Polos D10 (MAT-003) tersisa 18 batang (di bawah batas minimum 80).',
      time: '2 jam lalu',
      isRead: false,
      category: 'warehouse',
      link: '/warehouse/inventory',
    },
    {
      id: 4,
      role: 'FINANCE',
      title: 'Invoice Telah Jatuh Tempo',
      message: 'Invoice INV-2025-089 (CV Mitra Konstruksi Perkasa) senilai Rp 28.500.000 telah jatuh tempo.',
      time: '5 jam lalu',
      isRead: false,
      category: 'finance',
      link: '/finance/invoices',
    },
    {
      id: 5,
      role: 'MARKETING',
      title: 'Order ORD-2026-001 Disetujui',
      message: 'Order ORD-2026-001 (PT WIKA Bangun Nusantara) telah disetujui penuh oleh Direktur.',
      time: '1 hari lalu',
      isRead: true,
      category: 'order',
      link: '/marketing/orders',
    },
    {
      id: 6,
      role: 'CLIENT',
      title: 'Invoice Tagihan Diterbitkan',
      message: 'Invoice INV-2026-001 untuk Order ORD-2026-001 telah diterbitkan. Total Rp 54.834.000 dengan skema pembayaran Termin 2 tahap.',
      time: '2 jam lalu',
      isRead: false,
      category: 'finance',
      link: '/client/payments',
    },
    {
      id: 7,
      role: 'CLIENT',
      title: 'Pesanan Dalam Pengiriman',
      message: 'Order ORD-2026-001 sedang dalam proses pengiriman menuju lokasi sekolah. Estimasi tiba sesuai jadwal surat jalan.',
      time: '6 jam lalu',
      isRead: false,
      category: 'order',
      link: '/client/orders',
    },
    {
      id: 8,
      role: 'CLIENT',
      title: 'Termin 1 Telah Diverifikasi',
      message: 'Pembayaran Anda sebesar Rp 25.000.000 (Termin Ke-1 / Uang Muka 50%) telah diterima dan diverifikasi oleh bagian Keuangan.',
      time: '3 hari lalu',
      isRead: true,
      category: 'finance',
      link: '/client/payments',
    },
  ],
  activityLogs: [
    { id: 1, timestamp: '2026-10-06 11:00', user: 'Budi Hartono (Marketing)', role: 'MARKETING', action: 'Membuat Draft Order', target: 'ORD-2026-004', details: 'Nilai total Rp 11.055.600 untuk PT Semen Persada Sejahtera.' },
    { id: 2, timestamp: '2026-10-05 14:15', user: 'Dewi Lestari (Finance)', role: 'FINANCE', action: 'Input Pembayaran Termin', target: 'PAY-2026-001', details: 'Penerimaan Rp 25.000.000 (Termin 1) untuk INV-2026-001.' },
    { id: 3, timestamp: '2026-10-05 11:00', user: 'Ir. Alpharo Wijaya (Direktur)', role: 'DIREKTUR', action: 'Menyetujui Order', target: 'ORD-2026-003', details: 'Persetujuan eksekusi pemenuhan pipa PVC dan semen.' },
    { id: 4, timestamp: '2026-10-04 10:00', user: 'Siti Rahmawati (Administrasi)', role: 'ADMINISTRASI', action: 'Menerbitkan Dokumen PO & Surat Jalan', target: 'ORD-2026-001', details: 'PO-2026-001 dan SJ-2026-001 siap cetak.' },
    { id: 5, timestamp: '2026-10-03 13:00', user: 'Agus Santoso (Warehouse)', role: 'WAREHOUSE', action: 'Konfirmasi Pengiriman Armada', target: 'SHP-2026-001', details: 'Truk N-8841-AA diberangkatkan ke Cipinang.' },
    { id: 6, timestamp: '2026-10-02 09:30', user: 'Ir. Alpharo Wijaya (Direktur)', role: 'DIREKTUR', action: 'Menyetujui Order', target: 'ORD-2026-001', details: 'Verifikasi volume material dan jadwal pemenuhan gudang.' },
  ],
};

function loadInitialState() {
  const saved = localStorage.getItem(STORAGE_KEY);
  if (saved) {
    try {
      const parsed = JSON.parse(saved);
      const merged = { ...defaultData, ...parsed };
      if (!merged.clients || !merged.clients.some((c) => c.code === 'CS00001')) {
        merged.clients = JSON.parse(JSON.stringify(defaultData.clients));
      }
      if (!Array.isArray(merged.notifications) || !merged.notifications.some((n) => n.role === 'CLIENT')) {
        const nonClientNotifs = (merged.notifications || []).filter((n) => n.role !== 'CLIENT');
        const clientNotifs = defaultData.notifications.filter((n) => n.role === 'CLIENT');
        merged.notifications = [...nonClientNotifs, ...clientNotifs];
      }
      if (!Array.isArray(merged.roles) || !merged.roles.some((r) => r.key === 'CLIENT')) {
        merged.roles = JSON.parse(JSON.stringify(defaultData.roles));
      }
      if (merged.currentUser && merged.currentUser.role === 'CLIENT' && !merged.currentUser.clientId) {
        merged.currentUser.clientId = 1;
      }
      if (Array.isArray(merged.orders)) {
        merged.orders = merged.orders.map((o) => ({
          ...o,
          isHold: o.isHold ?? false,
          holdReason: o.holdReason ?? null,
          holdBy: o.holdBy ?? null,
          holdDate: o.holdDate ?? null,
          followUpLogs: Array.isArray(o.followUpLogs) ? o.followUpLogs : [],
        }));
      }
      merged.wilayahDki = WILAYAH_DKI;
      return merged;
    } catch (e) {
      console.warn('Failed to parse saved state, resetting to defaults', e);
    }
  }
  return JSON.parse(JSON.stringify(defaultData));
}

export const store = reactive(loadInitialState());

// Auto-save on change
watch(
  () => store,
  (newVal) => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal));
    } catch (e) {
      console.error('Storage save error:', e);
    }
  },
  { deep: true }
);

// Store Actions & Business Logic
export const storeActions = {
  resetToDefaults() {
    localStorage.removeItem(STORAGE_KEY);
    const defaults = JSON.parse(JSON.stringify(defaultData));
    Object.assign(store, defaults);
  },

  switchRole(roleKey) {
    const roleObj = store.roles.find((r) => r.key === roleKey);
    if (roleObj) {
      store.currentUser = {
        id: roleObj.key === 'MARKETING' ? 1 : roleObj.key === 'DIREKTUR' ? 2 : roleObj.key === 'ADMINISTRASI' ? 3 : roleObj.key === 'WAREHOUSE' ? 4 : roleObj.key === 'FINANCE' ? 5 : 6,
        name: roleObj.name,
        email: roleObj.email,
        role: roleObj.key,
        division: roleObj.division,
        avatar: store.currentUser.avatar,
        phone: '0812-3456-7890',
        clientId: roleObj.key === 'CLIENT' ? 1 : null,
      };
      localStorage.setItem('user', JSON.stringify(store.currentUser));
      localStorage.setItem('token', 'mock-token-' + roleKey.toLowerCase());
    }
  },

  addActivityLog(action, target, details) {
    const now = new Date();
    const formatted = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    store.activityLogs.unshift({
      id: Date.now(),
      timestamp: formatted,
      user: store.currentUser.name,
      role: store.currentUser.role,
      action,
      target,
      details,
    });
  },

  addNotification(role, title, message, category, link) {
    store.notifications.unshift({
      id: Date.now(),
      role,
      title,
      message,
      time: 'Baru saja',
      isRead: false,
      category,
      link,
    });
  },

  // Marketing Client Actions
  addClient(clientData) {
    let nextNum = 1;
    if (store.clients && store.clients.length > 0) {
      const numbers = store.clients.map((c) => {
        const match = c.code ? c.code.match(/(\d+)/) : null;
        return match ? parseInt(match[1], 10) : c.id;
      });
      nextNum = Math.max(...numbers, 0) + 1;
    }
    const code = clientData.code || `CS${String(nextNum).padStart(5, '0')}`;
    const nextId = store.clients.length ? Math.max(...store.clients.map((c) => c.id)) + 1 : 1;

    const newClient = {
      id: nextId,
      code,
      name: clientData.name || '',
      orgFullName: clientData.orgFullName || clientData.name || '',
      orgShortName: clientData.orgShortName || clientData.name || '',
      address: clientData.address || '',
      npwp: clientData.npwp || '',
      province: clientData.province || 'DKI JAKARTA',
      city: clientData.city || 'JAKARTA TIMUR',
      district: clientData.district || '',
      subdistrict: clientData.subdistrict || '',
      postalCode: clientData.postalCode || '',
      contactPerson: clientData.contactPerson || '',
      phone: clientData.phone || '',
      letterheadAddress: clientData.letterheadAddress || clientData.address || '',
      letterheadCity: clientData.letterheadCity || clientData.city || '',
      orgPhone: clientData.orgPhone || '',
      fax: clientData.fax || '',
      website: clientData.website || '',
      email: clientData.email || '',
      status: clientData.status || 'Aktif',
      type: clientData.type || 'Instansi Pendidikan',
      totalOrders: 0,
      totalTransaction: 0,
      createdAt: new Date().toISOString().split('T')[0],
    };

    store.clients.unshift(newClient);
    this.addActivityLog('Menambah Client Baru', code, `Client ${newClient.name} berhasil didaftarkan.`);
    return newClient;
  },

  updateClient(clientId, clientData) {
    const idx = store.clients.findIndex((c) => c.id === Number(clientId) || c.code === String(clientId));
    if (idx !== -1) {
      const existing = store.clients[idx];
      const updated = {
        ...existing,
        ...clientData,
        id: existing.id,
        code: existing.code,
      };
      store.clients[idx] = updated;
      this.addActivityLog('Memperbarui Data Client', existing.code, `Data client ${updated.name} diperbarui.`);
      return updated;
    }
    return null;
  },

  deleteClient(clientId) {
    const idx = store.clients.findIndex((c) => c.id === Number(clientId) || c.code === String(clientId));
    if (idx !== -1) {
      const removed = store.clients.splice(idx, 1)[0];
      this.addActivityLog('Menghapus Data Client', removed.code, `Client ${removed.name} dihapus dari sistem.`);
      return removed;
    }
    return null;
  },

  createOrder(orderData) {
    const nextId = store.orders.length ? Math.max(...store.orders.map((o) => o.id)) + 1 : 1;
    const orderNumber = `ORD-2026-${String(nextId).padStart(3, '0')}`;
    const client = store.clients.find((c) => c.id === Number(orderData.clientId)) || { name: 'Client Umum' };
    
    const subtotal = orderData.items.reduce((sum, item) => sum + item.qty * item.price, 0);
    const tax = Math.round(subtotal * 0.11);
    const grandTotal = subtotal + tax;

    const newOrder = {
      id: nextId,
      orderNumber,
      clientId: Number(orderData.clientId),
      clientName: client.name,
      projectName: orderData.projectName || 'Proyek Pengadaan Rutin',
      date: new Date().toISOString().split('T')[0],
      submittedBy: store.currentUser.name,
      status: orderData.submitDirectly ? 'Menunggu Verifikasi' : 'Draft',
      isHold: false,
      holdReason: null,
      holdBy: null,
      holdDate: null,
      followUpLogs: [],
      approvedBy: null,
      approvalDate: null,
      approvalNotes: null,
      items: orderData.items.map((it) => ({
        productId: it.productId,
        sku: it.sku || 'SKU-GEN',
        name: it.name,
        qty: Number(it.qty),
        unit: it.unit || 'Unit',
        price: Number(it.price),
        total: Number(it.qty) * Number(it.price),
      })),
      subtotal,
      tax,
      grandTotal,
      notes: orderData.notes || '',
      timeline: [
        {
          stage: 'Order Dibuat',
          user: store.currentUser.name,
          timestamp: new Date().toLocaleString('id-ID'),
          status: 'completed',
          notes: orderData.submitDirectly ? 'Diajukan langsung untuk verifikasi Direktur' : 'Disimpan sebagai draft order',
        },
        ...(orderData.submitDirectly
          ? [
              {
                stage: 'Menunggu Verifikasi',
                user: 'System',
                timestamp: new Date().toLocaleString('id-ID'),
                status: 'in_progress',
                notes: 'Sedang menunggu review Direktur',
              },
            ]
          : []),
      ],
      documents: {
        po: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
        suratJalan: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
        bast: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
        faktur: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
      },
      fulfillmentStatus: 'Diproses',
    };

    store.orders.unshift(newOrder);

    if (orderData.submitDirectly) {
      this.addNotification('DIREKTUR', 'Verifikasi Order Diperlukan', `Order ${orderNumber} dari ${client.name} menunggu verifikasi Anda.`, 'approval', '/director/verification');
    }

    this.addActivityLog(orderData.submitDirectly ? 'Mengajukan Order Verifikasi' : 'Menyimpan Draft Order', orderNumber, `Nilai Rp ${grandTotal.toLocaleString('id-ID')}`);
    return newOrder;
  },

  deleteOrder(orderId) {
    const idx = store.orders.findIndex((o) => o.id === orderId);
    if (idx !== -1) {
      const order = store.orders[idx];
      store.orders.splice(idx, 1);
      this.addActivityLog('Menghapus Draft Order', order.orderNumber, 'Draft order telah dihapus permanen.');
    }
  },

  submitOrderForVerification(orderId) {
    const order = store.orders.find((o) => o.id === orderId);
    if (order && order.status === 'Draft') {
      order.status = 'Menunggu Verifikasi';
      order.timeline.push({
        stage: 'Menunggu Verifikasi',
        user: store.currentUser.name,
        timestamp: new Date().toLocaleString('id-ID'),
        status: 'in_progress',
        notes: 'Diajukan ke meja Direktur untuk proses verifikasi & approval.',
      });
      this.addNotification('DIREKTUR', 'Order Menunggu Verifikasi', `Order ${order.orderNumber} dari ${order.clientName} menunggu verifikasi Anda.`, 'approval', '/director/verification');
      this.addActivityLog('Mengajukan Verifikasi Order', order.orderNumber, 'Status berubah dari Draft ke Menunggu Verifikasi.');
    }
  },

  // Direktur Verification & Approval Actions
  approveOrder(orderId, notes = '') {
    const order = store.orders.find((o) => o.id === orderId);
    if (order) {
      order.status = 'Disetujui';
      order.approvedBy = store.currentUser.name;
      order.approvalDate = new Date().toLocaleString('id-ID');
      order.approvalNotes = notes || 'Disetujui oleh Direktur.';
      
      order.timeline.push({
        stage: 'Disetujui Direktur',
        user: store.currentUser.name,
        timestamp: new Date().toLocaleString('id-ID'),
        status: 'completed',
        notes: notes || 'Disetujui penuh untuk eksekusi dokumen dan persiapan barang.',
      });

      // Notify Administrasi & Warehouse & Marketing
      this.addNotification('MARKETING', 'Order Disetujui Direktur', `Order ${order.orderNumber} telah disetujui oleh Direktur.`, 'order', '/marketing/orders');
      this.addNotification('ADMINISTRASI', 'Order Baru Disetujui', `Order ${order.orderNumber} siap untuk penerbitan PO, Surat Jalan, dan BAST.`, 'document', '/administration/spj');
      this.addNotification('WAREHOUSE', 'Order Masuk Antrean Gudang', `Order ${order.orderNumber} membutuhkan persiapan & pengecekan stok barang.`, 'warehouse', '/warehouse/fulfillment');

      // Create warehouse fulfillment queue if not exist
      if (!store.warehouseFulfillments.some((wf) => wf.orderNumber === order.orderNumber)) {
        store.warehouseFulfillments.unshift({
          id: Date.now(),
          orderNumber: order.orderNumber,
          clientName: order.clientName,
          items: order.items.map((it) => ({
            sku: it.sku,
            name: it.name,
            reqQty: it.qty,
            availQty: 250,
            fulfilledQty: it.qty,
            unit: it.unit,
          })),
          status: 'Diproses',
          checkedBy: '-',
          notes: 'Menunggu alokasi dan packing tim gudang.',
        });
      }

      this.addActivityLog('Menyetujui Order', order.orderNumber, `Catatan: ${notes || 'Tanpa catatan khusus'}`);
    }
  },

  rejectOrder(orderId, reason) {
    const order = store.orders.find((o) => o.id === orderId);
    if (order) {
      order.status = 'Ditolak';
      order.approvedBy = store.currentUser.name;
      order.approvalDate = new Date().toLocaleString('id-ID');
      order.approvalNotes = `DITOLAK: ${reason}`;

      order.timeline.push({
        stage: 'Ditolak Direktur',
        user: store.currentUser.name,
        timestamp: new Date().toLocaleString('id-ID'),
        status: 'completed',
        notes: `Alasan penolakan: ${reason}`,
      });

      this.addNotification('MARKETING', 'Order Ditolak Direktur', `Order ${order.orderNumber} ditolak. Alasan: ${reason}`, 'order', '/marketing/orders');
      this.addActivityLog('Menolak Order', order.orderNumber, `Alasan: ${reason}`);
    }
  },

  // Direktur Executive Control: Hold & Release Order
  holdOrder(orderId, reason) {
    const order = store.orders.find((o) => o.id === orderId);
    if (order) {
      order.isHold = true;
      order.holdReason = reason || 'Ditahan oleh Direktur (Menunggu Kepastian Pembayaran)';
      order.holdBy = store.currentUser.name;
      order.holdDate = new Date().toLocaleString('id-ID');

      order.timeline.push({
        stage: 'Tahan Pengiriman (Hold)',
        user: store.currentUser.name,
        timestamp: new Date().toLocaleString('id-ID'),
        status: 'completed',
        notes: `Pengiriman ditahan Direktur. Alasan: ${order.holdReason}`,
      });

      this.addNotification(
        'WAREHOUSE',
        'PERINGATAN: Pengiriman Order Ditahan',
        `Order ${order.orderNumber} (${order.clientName}) ditahan oleh Direktur. Jangan lakukan pengiriman! Alasan: ${order.holdReason}`,
        'warehouse',
        '/warehouse/shipments'
      );

      this.addNotification(
        'MARKETING',
        'Order Ditahan Direktur',
        `Order ${order.orderNumber} (${order.clientName}) ditahan Direktur karena: ${order.holdReason}. Segera konfirmasi pembayaran dengan client.`,
        'order',
        '/marketing/orders/monitoring'
      );

      this.addActivityLog('Tahan Pengiriman Order', order.orderNumber, `Alasan: ${order.holdReason}`);
    }
  },

  releaseOrderHold(orderId, notes = '') {
    const order = store.orders.find((o) => o.id === orderId);
    if (order) {
      order.isHold = false;
      const prevReason = order.holdReason;
      order.holdReason = null;
      order.holdBy = null;
      order.holdDate = null;

      order.timeline.push({
        stage: 'Tahanan Pengiriman Dilepas',
        user: store.currentUser.name,
        timestamp: new Date().toLocaleString('id-ID'),
        status: 'completed',
        notes: `Tahanan dilepas Direktur: ${notes || 'Instruksi pengiriman dapat dilanjutkan.'}`,
      });

      this.addNotification(
        'WAREHOUSE',
        'Tahanan Order Telah Dilepas',
        `Order ${order.orderNumber} (${order.clientName}) telah dilepas tahanannya oleh Direktur. Pengiriman dapat diproses kembali.`,
        'warehouse',
        '/warehouse/shipments'
      );

      this.addNotification(
        'MARKETING',
        'Tahanan Order Dilepas',
        `Order ${order.orderNumber} telah dilepas tahanannya. Persiapan pengiriman dilanjutkan.`,
        'order',
        '/marketing/orders/monitoring'
      );

      this.addActivityLog('Lepas Tahanan Order', order.orderNumber, `Catatan: ${notes || 'Pengiriman diizinkan kembali'}`);
    }
  },

  // Marketing Follow-Up Action (Client Communication Log)
  recordClientFollowUp(orderId, followUpData) {
    const order = store.orders.find((o) => o.id === orderId);
    if (order) {
      if (!Array.isArray(order.followUpLogs)) {
        order.followUpLogs = [];
      }
      const newLog = {
        id: Date.now(),
        date: followUpData.date || new Date().toLocaleString('id-ID'),
        contactPerson: followUpData.contactPerson,
        method: followUpData.method || 'Telepon / WhatsApp',
        outcome: followUpData.outcome,
        promisedPaymentDate: followUpData.promisedPaymentDate || '-',
        notes: followUpData.notes || '',
        recordedBy: store.currentUser.name,
      };
      order.followUpLogs.unshift(newLog);

      order.timeline.push({
        stage: 'Follow-Up Pembayaran Client',
        user: store.currentUser.name,
        timestamp: new Date().toLocaleString('id-ID'),
        status: 'completed',
        notes: `Kontak: ${followUpData.contactPerson} (${newLog.method}). Hasil: ${followUpData.outcome}. Janji Bayar: ${followUpData.promisedPaymentDate || '-'}`,
      });

      this.addNotification(
        'FINANCE',
        'Update Follow-Up Penagihan Client',
        `Marketing (${store.currentUser.name}) mencatat hasil follow-up ${order.clientName} (${order.orderNumber}): ${followUpData.outcome}. Janji Bayar: ${followUpData.promisedPaymentDate || '-'}.`,
        'finance',
        '/finance/invoices'
      );

      this.addActivityLog('Catat Follow-Up Pembayaran', order.orderNumber, `PIC: ${followUpData.contactPerson}, Hasil: ${followUpData.outcome}`);
      return newLog;
    }
    return null;
  },

  // Finance Reminder Action to Marketing
  sendPaymentReminderToMarketing(invoiceNumber, note = '') {
    const inv = store.invoices.find((i) => i.invoiceNumber === invoiceNumber);
    if (inv) {
      this.addNotification(
        'MARKETING',
        'Pengingat Penagihan Client',
        `Invoice ${inv.invoiceNumber} (${inv.clientName}) belum lunas (Sisa: Rp ${inv.remainingAmount.toLocaleString('id-ID')}). Catatan Finance: ${note || 'Mohon dibantu konfirmasi tanggal pembayaran dengan PIC client.'}`,
        'finance',
        '/marketing/monitoring'
      );
      this.addActivityLog('Kirim Pengingat Tagihan ke Marketing', inv.invoiceNumber, `Client: ${inv.clientName}, Sisa: Rp ${inv.remainingAmount.toLocaleString('id-ID')}`);
    }
  },

  // Administrasi Document Actions
  generateDocument(orderNumber, docType, docNumber) {
    const order = store.orders.find((o) => o.orderNumber === orderNumber);
    if (order && order.documents) {
      const today = new Date().toISOString().split('T')[0];
      if (docType === 'po') {
        order.documents.po = { exists: true, number: docNumber || `PO-2026-${order.id}`, status: 'Lengkap', date: today, file: `PO_${order.orderNumber}.pdf` };
      } else if (docType === 'suratJalan') {
        order.documents.suratJalan = { exists: true, number: docNumber || `SJ-2026-${order.id}`, status: 'Lengkap', date: today, file: `SJ_${order.orderNumber}.pdf` };
      } else if (docType === 'bast') {
        order.documents.bast = { exists: true, number: docNumber || `BAST-2026-${order.id}`, status: 'Lengkap', date: today, file: `BAST_${order.orderNumber}.pdf` };
      } else if (docType === 'faktur') {
        order.documents.faktur = { exists: true, number: docNumber || `FK-2026-${order.id}`, status: 'Lengkap', date: today, file: `FK_${order.orderNumber}.pdf` };
      }

      this.addActivityLog(`Menerbitkan Dokumen ${docType.toUpperCase()}`, orderNumber, `Dokumen ${docNumber} telah dibuat & disimpan.`);
    }
  },

  // Warehouse Actions
  confirmFulfillment(fulfillmentId) {
    const item = store.warehouseFulfillments.find((f) => f.id === fulfillmentId);
    if (item) {
      item.status = 'Siap Dikirim';
      item.checkedBy = store.currentUser.name;

      const order = store.orders.find((o) => o.orderNumber === item.orderNumber);
      if (order) {
        order.fulfillmentStatus = 'Siap Dikirim';
      }

      this.addActivityLog('Konfirmasi Pemenuhan Barang', item.orderNumber, 'Semua item siap dikirim di Loading Dock.');
      this.addNotification('ADMINISTRASI', 'Barang Siap Dikirim', `Order ${item.orderNumber} telah siap dikirim oleh Gudang.`, 'warehouse', '/administration/delivery-notes');
    }
  },

  createShipment(shipmentData) {
    const nextId = store.shipments.length ? Math.max(...store.shipments.map((s) => s.id)) + 1 : 1;
    const shipmentNumber = `SHP-2026-${String(nextId).padStart(3, '0')}`;
    const newShipment = {
      id: nextId,
      shipmentNumber,
      orderNumber: shipmentData.orderNumber,
      clientName: shipmentData.clientName,
      suratJalanNumber: shipmentData.suratJalanNumber || `SJ-${shipmentData.orderNumber}`,
      destination: shipmentData.destination || 'Gudang Client',
      expedition: shipmentData.expedition || 'Armada Internal CV Alpharo',
      driverName: shipmentData.driverName || 'Sopir Ekspedisi',
      driverPhone: shipmentData.driverPhone || '0812-0000-1111',
      departureDate: new Date().toLocaleString('id-ID'),
      estimatedArrival: 'Hari berikutnya',
      status: 'Sedang Dikirim',
      notes: shipmentData.notes || '',
    };
    store.shipments.unshift(newShipment);

    const order = store.orders.find((o) => o.orderNumber === shipmentData.orderNumber);
    if (order) {
      order.fulfillmentStatus = 'Sedang Dikirim';
      order.status = 'Diproses';
    }

    this.addActivityLog('Memulai Pengiriman Barang', shipmentNumber, `Order ${shipmentData.orderNumber} via ${newShipment.expedition}`);
    return newShipment;
  },

  updateShipmentStatus(shipmentId, newStatus) {
    const s = store.shipments.find((item) => item.id === shipmentId);
    if (s) {
      s.status = newStatus;
      if (newStatus === 'Selesai') {
        const order = store.orders.find((o) => o.orderNumber === s.orderNumber);
        if (order) {
          order.fulfillmentStatus = 'Selesai';
          order.timeline.push({
            stage: 'Pengiriman Selesai',
            user: store.currentUser.name,
            timestamp: new Date().toLocaleString('id-ID'),
            status: 'completed',
            notes: 'Armada tiba di site proyek. BAST fisik ditandatangani oleh penerima lapangan.',
          });

          // Check if invoice is also paid
          const inv = store.invoices.find((i) => i.orderNumber === s.orderNumber);
          if (inv && inv.status === 'Lunas') {
            order.status = 'Selesai';
            order.timeline.push({
              stage: 'Pesanan Selesai',
              user: 'System',
              timestamp: new Date().toLocaleString('id-ID'),
              status: 'completed',
              notes: 'Pesanan resmi tuntas: Pengiriman barang tuntas dan seluruh tagihan telah lunas 100%.',
            });
            this.addNotification('DIREKTUR', 'Proyek Selesai Tuntas', `Order ${order.orderNumber} (${order.clientName}) telah selesai penuh (Pengiriman & Pelunasan 100%).`, 'order', '/director/monitoring');
          }
        }
      }
      this.addActivityLog('Update Status Pengiriman', s.shipmentNumber, `Status diubah menjadi ${newStatus}.`);
    }
  },

  addStockMovement(data) {
    const product = store.products.find((p) => p.sku === data.sku);
    if (product) {
      if (data.type === 'Masuk') {
        product.stock += Number(data.qty);
      } else if (data.type === 'Keluar') {
        product.stock = Math.max(0, product.stock - Number(data.qty));
      } else if (data.type === 'Penyesuaian') {
        product.stock = Number(data.qty);
      }
      product.status = product.stock <= 0 ? 'Tidak Tersedia' : product.stock <= product.minStock ? 'Menipis' : 'Tersedia';

      store.stockMovements.unshift({
        id: Date.now(),
        date: new Date().toLocaleString('id-ID'),
        sku: product.sku,
        name: product.name,
        type: data.type,
        qty: Number(data.qty),
        unit: product.unit,
        reference: data.reference || 'PENYESUAIAN-MANUAL',
        pic: store.currentUser.name,
      });

      this.addActivityLog('Mutasi Stok Barang', product.sku, `${data.type} sebanyak ${data.qty} ${product.unit}.`);
    }
  },

  // Finance Actions (Unified Single Module: Full & Termin Payments)
  createInvoice(invoiceData) {
    const nextId = store.invoices.length ? Math.max(...store.invoices.map((i) => i.id)) + 1 : 1;
    const invoiceNumber = `INV-2026-${String(nextId).padStart(3, '0')}`;
    const order = store.orders.find((o) => o.orderNumber === invoiceData.orderNumber);

    const newInvoice = {
      id: nextId,
      invoiceNumber,
      orderNumber: invoiceData.orderNumber,
      clientId: order ? order.clientId : 1,
      clientName: order ? order.clientName : 'Client Mitra',
      invoiceDate: invoiceData.invoiceDate || new Date().toISOString().split('T')[0],
      dueDate: invoiceData.dueDate,
      totalAmount: Number(invoiceData.totalAmount),
      paidAmount: 0,
      remainingAmount: Number(invoiceData.totalAmount),
      status: 'Belum Dibayar',
      paymentType: invoiceData.paymentType || 'Penuh', // Penuh, Termin
      terminCount: Number(invoiceData.terminCount) || 1,
      termins: invoiceData.termins || [
        { termNumber: 1, amount: Number(invoiceData.totalAmount), dueDate: invoiceData.dueDate, status: 'Belum Dibayar', paidDate: null, method: '-' },
      ],
    };

    store.invoices.unshift(newInvoice);

    if (order && order.status !== 'Selesai') {
      order.status = 'Menunggu Pembayaran';
      order.timeline.push({
        stage: 'Invoice Diterbitkan',
        user: store.currentUser.name,
        timestamp: new Date().toLocaleString('id-ID'),
        status: 'completed',
        notes: `Invoice ${invoiceNumber} senilai Rp ${newInvoice.totalAmount.toLocaleString('id-ID')} diterbitkan. Status pesanan: Menunggu Pembayaran.`,
      });
      this.addNotification(
        'MARKETING',
        'Invoice Diterbitkan (Menunggu Pembayaran)',
        `Invoice ${invoiceNumber} untuk order ${order.orderNumber} telah diterbitkan. Lakukan koordinasi dan pemantauan pembayaran client.`,
        'finance',
        '/marketing/monitoring'
      );
    }

    this.addActivityLog('Menerbitkan Invoice', invoiceNumber, `Nilai Rp ${newInvoice.totalAmount.toLocaleString('id-ID')} untuk ${newInvoice.clientName}`);
    return newInvoice;
  },

  recordPayment(paymentData) {
    const invoice = store.invoices.find((i) => i.invoiceNumber === paymentData.invoiceNumber);
    if (!invoice) return null;

    const nextId = store.payments.length ? Math.max(...store.payments.map((p) => p.id)) + 1 : 1;
    const paymentNumber = `PAY-2026-${String(nextId).padStart(3, '0')}`;
    const payAmount = Number(paymentData.amount);

    invoice.paidAmount += payAmount;
    invoice.remainingAmount = Math.max(0, invoice.totalAmount - invoice.paidAmount);

    if (invoice.remainingAmount <= 0) {
      invoice.status = 'Lunas';
    } else {
      invoice.status = 'Sebagian / Termin';
    }

    // Update specific termin if payment is termin
    if (paymentData.terminNumber && invoice.termins) {
      const term = invoice.termins.find((t) => t.termNumber === Number(paymentData.terminNumber));
      if (term) {
        term.status = 'Lunas';
        term.paidDate = paymentData.paymentDate || new Date().toISOString().split('T')[0];
        term.method = paymentData.paymentMethod || 'Transfer Bank';
      }
    }

    const newPayment = {
      id: nextId,
      paymentNumber,
      invoiceNumber: invoice.invoiceNumber,
      clientName: invoice.clientName,
      totalInvoice: invoice.totalAmount,
      paymentType: paymentData.paymentType === 'Termin' ? 'Pembayaran Termin' : 'Pembayaran Penuh',
      terminSequence: paymentData.paymentType === 'Termin' ? `Termin Ke-${paymentData.terminNumber || 1}` : 'Pelunasan Penuh',
      amount: payAmount,
      remainingBalance: invoice.remainingAmount,
      paymentDate: paymentData.paymentDate || new Date().toISOString().split('T')[0],
      paymentMethod: paymentData.paymentMethod || 'Transfer BCA',
      referenceNumber: paymentData.referenceNumber || `TRF-${Math.floor(100000 + Math.random() * 900000)}`,
      proofFile: paymentData.proofFile || 'BUKTI_PEMBAYARAN.pdf',
      status: 'Lunas',
      recordedBy: store.currentUser.name,
      notes: paymentData.notes || '',
    };

    store.payments.unshift(newPayment);

    // Update order timeline and status
    const order = store.orders.find((o) => o.orderNumber === invoice.orderNumber);
    if (order) {
      order.timeline.push({
        stage: invoice.remainingAmount <= 0 ? 'Pembayaran Lunas' : `Pembayaran Diterima (${newPayment.terminSequence})`,
        user: store.currentUser.name,
        timestamp: new Date().toLocaleString('id-ID'),
        status: 'completed',
        notes: `Penerimaan kas Rp ${payAmount.toLocaleString('id-ID')}. Sisa tagihan invoice: Rp ${invoice.remainingAmount.toLocaleString('id-ID')}`,
      });

      if (invoice.remainingAmount <= 0) {
        if (order.fulfillmentStatus === 'Selesai') {
          order.status = 'Selesai';
          order.timeline.push({
            stage: 'Pesanan Selesai',
            user: 'System',
            timestamp: new Date().toLocaleString('id-ID'),
            status: 'completed',
            notes: 'Pesanan resmi tuntas: Pengiriman barang selesai dan seluruh tagihan telah lunas 100%.',
          });
          this.addNotification('DIREKTUR', 'Order Selesai Tuntas', `Order ${order.orderNumber} (${order.clientName}) telah lunas dan pengiriman selesai.`, 'order', '/director/monitoring');
        } else {
          this.addNotification('WAREHOUSE', 'Pembayaran Client Lunas', `Order ${order.orderNumber} telah lunas. Persiapkan dan selesaikan pengiriman.`, 'finance', '/warehouse/shipments');
        }
      }
    }

    this.addActivityLog('Mencatat Pembayaran', paymentNumber, `Penerimaan Rp ${payAmount.toLocaleString('id-ID')} (${newPayment.terminSequence})`);
    return newPayment;
  },
};
