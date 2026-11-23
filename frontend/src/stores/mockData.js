import { computed, reactive, watch } from 'vue';
import api from '../services/api.js';
import { WILAYAH_DKI } from '../data/wilayahDki.js';
import { orderWorkflow } from '../services/orderWorkflow.js';
import { purchaseWorkflow, mergePurchaseRecords } from '../services/purchaseWorkflow.js';
import { SHIPPING_STATUS } from '../services/shippingStatus.js';
import { customerProfile } from '../services/customerProfile.js';

function withOrderCustomerProfile(order) {
  const client = store.clients.find(client => client.serverManaged && client.id === Number(order.clientId));
  return client ? { ...order, customerProfile: { ...customerProfile(client), ...(order.customerProfile || {}), code: client.code, name: client.name } } : order;
}

export { STORAGE_KEY, LEGACY_CUSTOMERS_KEY, prepareStorageSnapshot, saveStateToStorage } from '../services/storageManager.js';
import { STORAGE_KEY, LEGACY_CUSTOMERS_KEY, prepareStorageSnapshot, saveStateToStorage } from '../services/storageManager.js';
export function legacyCustomersBackup() {
  try {
    const saved = localStorage.getItem(LEGACY_CUSTOMERS_KEY);
    if (saved) return saved;
    const parsed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
    const clients = (Array.isArray(parsed.clients) ? parsed.clients : []).filter(client => !client.serverManaged);
    return clients.length ? JSON.stringify({ clients }) : null;
  } catch { return null; }
}

// Initial realistic enterprise seed data for CV Alpharo Artha Jaya (Supplier & Contractor)
const defaultData = {
  wilayahDki: WILAYAH_DKI,
  companyBankAccount: {
    bankName: 'Bank BCA',
    accountNumber: '52323628813',
    accountHolder: 'CV ALPHARO ARTHA JAYA',
  },
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
    { key: 'SUPPLIER', label: 'Supplier / Vendor', email: 'sales@synnexmetrodata.com', name: 'Hendra Gunawan (PT Synnex)', division: 'Mitra Pengadaan & Vendor' },
  ],
  marketingStaff: [
    { id: 1, name: 'Budi Hartono, S.T.', email: 'marketing@alpharo.co.id', phone: '0812-3456-7890', title: 'Senior Account Executive' },
    { id: 2, name: 'Ahmad Fauzi', email: 'ahmad.fauzi@alpharo.co.id', phone: '0813-8821-4455', title: 'Marketing Officer' },
    { id: 3, name: 'Rina Wijaya, S.E.', email: 'rina.wijaya@alpharo.co.id', phone: '0815-9922-3344', title: 'Marketing Representative' },
    { id: 4, name: 'Siti Rahma', email: 'siti.rahma@alpharo.co.id', phone: '0817-6655-4433', title: 'Education Sector Specialist' },
  ],
  clients: [
    {
      id: 1,
      code: 'CS00001',
      name: 'SMA 9',
      orgFullName: 'SEKOLAH MENENGAH ATAS NEGERI 9 JAKARTA',
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
      version: 1,
      serverManaged: true,
    },
    {
      id: 2,
      code: 'CS00002',
      name: 'SMA 14 JAKARTA',
      orgFullName: 'SEKOLAH MENENGAH ATAS NEGERI 14 JAKARTA',
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
      version: 1,
      serverManaged: true,
    },
    {
      id: 3,
      code: 'CS00003',
      name: 'SMA PKP',
      orgFullName: 'SEKOLAH MENENGAH ATAS PKP JAKARTA ISLAMIC SCHOOL',
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
      version: 1,
      serverManaged: true,
    },
    {
      id: 4,
      code: 'CS00004',
      name: 'SMAN 30 JAKARTA',
      orgFullName: 'SEKOLAH MENENGAH ATAS NEGERI 30 JAKARTA',
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
      version: 1,
      serverManaged: true,
    },
    {
      id: 5,
      code: 'CS00005',
      name: 'SMAN 39 JAKARTA',
      orgFullName: 'SEKOLAH MENENGAH ATAS NEGERI 39 JAKARTA',
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
      version: 1,
      serverManaged: true,
    },
    {
      id: 6,
      code: 'CS00006',
      name: 'SMAN 42 JAKARTA',
      orgFullName: 'SEKOLAH MENENGAH ATAS NEGERI 42 JAKARTA',
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
      version: 1,
      serverManaged: true,
    },
    {
      id: 7,
      code: 'CS00007',
      name: 'SMAN 58 JAKARTA',
      orgFullName: 'SEKOLAH MENENGAH ATAS NEGERI 58 JAKARTA',
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
      version: 1,
      serverManaged: true,
    },
    {
      id: 8,
      code: 'CS00008',
      name: 'SMAN 62 JAKARTA',
      orgFullName: 'SEKOLAH MENENGAH ATAS NEGERI 62 JAKARTA',
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
      version: 1,
      serverManaged: true,
    },
    {
      id: 9,
      code: 'CS00009',
      name: 'SMAN 64 JAKARTA',
      orgFullName: 'SEKOLAH MENENGAH ATAS NEGERI 64 JAKARTA',
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
      version: 1,
      serverManaged: true,
    },
    {
      id: 10,
      code: 'CS00010',
      name: 'SMAN 67 JAKARTA',
      orgFullName: 'SEKOLAH MENENGAH ATAS NEGERI 67 JAKARTA',
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
      version: 1,
      serverManaged: true,
    },
    {
      id: 11,
      code: 'CS00011',
      name: 'SMAN 68 JAKARTA',
      orgFullName: 'SEKOLAH MENENGAH ATAS NEGERI 68 JAKARTA',
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
      version: 1,
      serverManaged: true,
    },
    {
      id: 12,
      code: 'CS00012',
      name: 'SMAN 71 JAKARTA',
      orgFullName: 'SEKOLAH MENENGAH ATAS NEGERI 71 JAKARTA',
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
      version: 1,
      serverManaged: true,
    },
    {
      id: 13,
      code: 'CS00013',
      name: 'SMAN 76 JAKARTA',
      orgFullName: 'SEKOLAH MENENGAH ATAS NEGERI 76 JAKARTA',
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
      version: 1,
      serverManaged: true,
    },
    {
      id: 14,
      code: 'CS00014',
      name: 'SMPN 106 JAKARTA',
      orgFullName: 'SEKOLAH MENENGAH PERTAMA NEGERI 106 JAKARTA',
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
      version: 1,
      serverManaged: true,
    },
    {
      id: 15,
      code: 'CS00015',
      name: 'SMPN 188 JAKARTA',
      orgFullName: 'SEKOLAH MENENGAH PERTAMA NEGERI 188 JAKARTA',
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
      version: 1,
      serverManaged: true,
    },
    {
      id: 16,
      code: 'CS00016',
      name: 'SMA NEGRI 2 BOGOR',
      orgFullName: 'SEKOLAH MENENGAH ATAS NEGERI 2 BOGOR',
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
      version: 1,
      serverManaged: true,
    },
  ],

  suppliers: [
    {
      id: 1,
      code: 'SUP00001',
      name: 'PT Synnex Metrodata Indonesia',
      address: 'Gedung APL Tower Lt. 42, Jl. Letjen S. Parman Kav. 28, Tanjung Duren Selatan, Jakarta Barat',
      contactPerson: 'Hendra Gunawan (Sales Enterprise)',
      phone: '021-29345800',
      email: 'sales@synnexmetrodata.com',
      npwp: '01.345.678.9-031.000',
      bankName: 'BCA',
      bankAccountNumber: '5001239841',
      bankAccountName: 'PT Synnex Metrodata Indonesia',
      status: 'Aktif',
      notes: 'Supplier utama hardware komputer, server, dan laptop bergaransi resmi.',
      createdAt: '2025-01-10',
    },
    {
      id: 2,
      code: 'SUP00002',
      name: 'CV Sumber Makmur Komputer',
      address: 'Jl. Pajajaran No. 45, Baranangsiang, Kota Bogor, Jawa Barat',
      contactPerson: 'Budi Setiawan',
      phone: '0251-8321900',
      email: 'marketing@sumbermakmur.co.id',
      npwp: '02.456.789.0-042.000',
      bankName: 'Bank Mandiri',
      bankAccountNumber: '1330018892019',
      bankAccountName: 'CV Sumber Makmur Komputer',
      status: 'Aktif',
      notes: 'Vendor peripheral IT, printer, proyektor, dan aksesoris multimedia.',
      createdAt: '2025-01-15',
    },
    {
      id: 3,
      code: 'SUP00003',
      name: 'PT Indo Komputer Perkasa',
      address: 'Komplek Harco Mangga Dua Blok E No. 12, Sawah Besar, Jakarta Pusat',
      contactPerson: 'Siti Nurhaliza',
      phone: '021-62301122',
      email: 'info@indokomputer.com',
      npwp: '01.889.012.3-018.000',
      bankName: 'BNI',
      bankAccountNumber: '0829103982',
      bankAccountName: 'PT Indo Komputer Perkasa',
      status: 'Aktif',
      notes: 'Distributor jaringan komputer, kabel UTP, switch mikrotik, dan router cisco.',
      createdAt: '2025-02-01',
    },
    {
      id: 4,
      code: 'SUP00004',
      name: 'CV Mandiri Perkasa Prima',
      address: 'Kawasan Industri Sentul, Jl. Babakan Madang No. 8, Bogor, Jawa Barat',
      contactPerson: 'Ahmad Fauzi',
      phone: '0251-8456789',
      email: 'order@mandiriperkasa.id',
      npwp: '03.112.233.4-055.000',
      bankName: 'BCA',
      bankAccountNumber: '5230918231',
      bankAccountName: 'CV Mandiri Perkasa Prima',
      status: 'Aktif',
      notes: 'Penyedia material konstruksi, baja ringan, semen, dan pipa pvc.',
      createdAt: '2025-02-12',
    },
    {
      id: 5,
      code: 'SUP00005',
      name: 'PT Cipta Karya Niaga',
      address: 'Jl. Raya Daan Mogot Km. 11, Cengkareng, Jakarta Barat',
      contactPerson: 'Dewi Sartika',
      phone: '021-58902233',
      email: 'sales@ciptakaryaniaga.com',
      npwp: '01.992.384.1-085.000',
      bankName: 'BRI',
      bankAccountNumber: '038901000293301',
      bankAccountName: 'PT Cipta Karya Niaga',
      status: 'Aktif',
      notes: 'Grosir perlengkapan elektrikal kabel NYM, panel MCB, dan lampu LED gedung.',
      createdAt: '2025-03-05',
    },
    {
      id: 6,
      code: 'SUP00006',
      name: 'PT Global Sarana Solusindo',
      address: 'Jl. TB Simatupang No. 18, Cilandak, Jakarta Selatan',
      contactPerson: 'Rian Hidayat',
      phone: '021-75912345',
      email: 'procurement@globalsarana.co.id',
      npwp: '02.102.938.4-012.000',
      bankName: 'Bank Mandiri',
      bankAccountNumber: '1270098234112',
      bankAccountName: 'PT Global Sarana Solusindo',
      status: 'Aktif',
      notes: 'Penyedia software lisensi, Microsoft Windows, Office Pro, dan Antivirus.',
      createdAt: '2025-03-20',
    },
    {
      id: 7,
      code: 'CV Graha Pratama Furniture',
      address: 'Jl. Dr. Saharjo No. 112, Tebet, Jakarta Selatan',
      contactPerson: 'Eko Prasetyo',
      phone: '021-83709876',
      email: 'sales@grahafuniture.com',
      npwp: '03.445.667.8-061.000',
      bankName: 'BCA',
      bankAccountNumber: '6820192834',
      bankAccountName: 'CV Graha Pratama Furniture',
      status: 'Aktif',
      notes: 'Meja dan kursi laboratorium komputer sekolah, partisi ruang kantor.',
      createdAt: '2025-04-10',
    },
    {
      id: 8,
      code: 'SUP00008',
      name: 'PT Mitra Sejahtera Teknik',
      address: 'Kawasan Industri Pulogadung Blok III A, Cakung, Jakarta Timur',
      contactPerson: 'Ir. Bambang Tri',
      phone: '021-4601199',
      email: 'contact@mitrasejahtera.co.id',
      npwp: '01.556.778.9-009.000',
      bankName: 'BSI',
      bankAccountNumber: '7112093847',
      bankAccountName: 'PT Mitra Sejahtera Teknik',
      status: 'Aktif',
      notes: 'Alat mekanikal dan elektrikal heavy-duty, genset, dan stabilizer daya.',
      createdAt: '2025-05-02',
    },
  ],
  purchases: [
    {
      id: 1,
      purchase_code: 'PB20261014114148',
      transaction_date: '2026-10-14',
      invoice_number: 'FAK-SNX-99812',
      project_id: 'PR00001',
      project_name: 'pengadaan komputer sekolah (SMA NEGRI 2 BOGOR)',
      default_supplier: 'PT Synnex Metrodata Indonesia',
      supplier_code: 'SUP00001',
      supplier_email: 'sales@synnexmetrodata.com',
      subtotal: 10000000,
      discount: 0,
      total_price: 10000000,
      payment_method: 'Tunai',
      supplier_status: 'Diterima Gudang',
      delivery_info: {
        surat_jalan: 'SJ/SNX/X/2026/088',
        courier: 'Armada Internal PT Synnex',
        tracking_number: 'TRK-SNX-8821',
        dispatch_date: '2026-10-15',
        est_arrival: '2026-10-16',
        notes: 'Barang telah diantar langsung dan diverifikasi lengkap oleh staf gudang CV Alpharo.',
      },
      items: [
        {
          barang: 'A00001770 - Pemeliharaan Komputer Note Book/ Laptop',
          sku: 'A00001770',
          supplier: 'PT Synnex Metrodata Indonesia',
          qty: 100,
          harga_beli: 100000,
          diskon_percent: 0,
          diskon_nominal: 0,
          subtotal: 10000000,
        },
      ],
    },
    {
      id: 2,
      purchase_code: 'PB20261028091522',
      transaction_date: '2026-10-28',
      invoice_number: 'FAK-SNX-100234',
      project_id: 'PR00002',
      project_name: 'Pembangunan Fasilitas Workshop & Lab Vokasi',
      default_supplier: 'PT Synnex Metrodata Indonesia',
      supplier_code: 'SUP00001',
      supplier_email: 'sales@synnexmetrodata.com',
      subtotal: 25000000,
      discount: 500000,
      total_price: 24500000,
      payment_method: 'Transfer Bank BCA',
      supplier_status: 'Dikirim ke Gudang CV',
      delivery_info: {
        surat_jalan: 'SJ/SNX/X/2026/102',
        courier: 'JNE Trucking',
        tracking_number: 'JNET-298371928',
        dispatch_date: '2026-10-29',
        est_arrival: '2026-10-31',
        shipping_status: 'Kurir Mengambil Barang (Dispatched)',
        current_location: 'Hub Transit Logistik Cakung, Jakarta Timur',
        last_updated: '14:20 WIB',
        notes: 'Dikirim dalam 5 pallet kayu pelindung garansi resmi.',
        milestones: [
          {
            id: 1,
            timestamp: '29 Okt 2026 09:30 WIB',
            status: 'Pesanan Diberangkatkan dari Gudang Supplier',
            location: 'Gudang Utama PT Synnex Metrodata',
            notes: 'Surat Jalan SJ/SNX/X/2026/102 diterbitkan & segel utuh.',
            updated_by: 'PT Synnex Metrodata Indonesia'
          },
          {
            id: 2,
            timestamp: '29 Okt 2026 14:20 WIB',
            status: 'Kurir Mengambil Barang dari Gudang Supplier',
            location: 'Hub Transit Logistik Cakung, Jakarta Timur',
            notes: 'Paket sedang disortir dan dimuat ke armada ekspedisi JNE Trucking tujuan gudang proyek CV Alpharo.',
            updated_by: 'JNE Trucking'
          }
        ]
      },
      items: [
        {
          barang: 'PRD-SRV-01 - Server Tower Intel Xeon E-2324G',
          sku: 'PRD-SRV-01',
          supplier: 'PT Synnex Metrodata Indonesia',
          qty: 2,
          harga_beli: 12250000,
          diskon_percent: 0,
          diskon_nominal: 0,
          subtotal: 24500000,
        },
      ],
    },
    {
      id: 3,
      purchase_code: 'PB20261030132015',
      transaction_date: '2026-10-30',
      invoice_number: 'FAK-SNX-101550',
      project_id: 'PR00001',
      project_name: 'pengadaan komputer sekolah (SMA NEGRI 2 BOGOR)',
      default_supplier: 'PT Synnex Metrodata Indonesia',
      supplier_code: 'SUP00001',
      supplier_email: 'sales@synnexmetrodata.com',
      subtotal: 18000000,
      discount: 0,
      total_price: 18000000,
      payment_method: 'Transfer Bank BCA',
      supplier_status: 'Menunggu Konfirmasi',
      delivery_info: null,
      items: [
        {
          barang: 'PRD-LPT-02 - Laptop Asus ExpertBook B1 Core i5 16GB',
          sku: 'PRD-LPT-02',
          supplier: 'PT Synnex Metrodata Indonesia',
          qty: 2,
          harga_beli: 9000000,
          diskon_percent: 0,
          diskon_nominal: 0,
          subtotal: 18000000,
        },
      ],
    },
    {
      id: 4,
      purchase_code: 'PB20261030141018',
      transaction_date: '2026-10-30',
      invoice_number: 'FAK-SBM-55421',
      project_id: 'PR00004',
      project_name: 'Renovasi Gedung Laboratorium Komputer',
      default_supplier: 'CV Sumber Makmur Komputer',
      supplier_code: 'SUP00002',
      supplier_email: 'marketing@sumbermakmur.co.id',
      subtotal: 8500000,
      discount: 0,
      total_price: 8500000,
      payment_method: 'Transfer Bank Mandiri',
      supplier_status: 'Menunggu Konfirmasi',
      delivery_info: null,
      items: [
        {
          barang: 'PRD-SW-24 - Switch Gigabyte 24 Port Managed',
          sku: 'PRD-SW-24',
          supplier: 'CV Sumber Makmur Komputer',
          qty: 5,
          harga_beli: 1700000,
          diskon_percent: 0,
          diskon_nominal: 0,
          subtotal: 8500000,
        },
      ],
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
  orders: [],
  warehouseFulfillments: [],
  deliveryNotes: [],
  shipments: [],
  stockMovements: [],
  warehouseRequests: [],
  invoices: [],
  payments: [],
  notifications: [],
  activityLogs: [],
};

function applyAuthenticatedUser(data) {
  const savedUser = localStorage.getItem('user');
  if (!savedUser) return;

  let user;
  try {
    user = JSON.parse(savedUser);
  } catch (error) {
    console.warn('Failed to parse authenticated user from storage', error);
    return;
  }

  const role = data.roles.find((item) => item.key === user.role);
  if (role) {
    data.currentUser = {
      ...data.currentUser,
      id: user.id,
      name: user.name,
      email: user.email,
      role: role.key,
      division: role.division,
      clientId: role.key === 'CLIENT' ? 1 : null,
    };
  }
}

let preserveLegacyStorage = false;
let storageSaveTimer = null;

function loadInitialState() {
  try {
    localStorage.removeItem('alpharo_integrated_system_v1');
    localStorage.removeItem('alpharo_integrated_system_v2');
  } catch {}
  const saved = localStorage.getItem(STORAGE_KEY);
  if (saved) {
    try {
      const parsed = JSON.parse(saved);
      // Clean up legacy bloated storage (e.g. products or huge payloads) immediately to free quota
      if (
        (Array.isArray(parsed.products) && parsed.products.length > 50) ||
        parsed.wilayahDki ||
        (saved.length > 500 * 1024)
      ) {
        try {
          const sanitized = prepareStorageSnapshot(parsed);
          localStorage.setItem(STORAGE_KEY, JSON.stringify(sanitized));
        } catch {}
      }
      const merged = { ...defaultData, ...parsed };
      applyAuthenticatedUser(merged);
      const legacyClients = (Array.isArray(parsed.clients) ? parsed.clients : []).filter(client => !client.serverManaged);
      if (legacyClients.length && !localStorage.getItem(LEGACY_CUSTOMERS_KEY)) {
        try { localStorage.setItem(LEGACY_CUSTOMERS_KEY, JSON.stringify({ savedAt: new Date().toISOString(), clients: legacyClients })); }
        catch { preserveLegacyStorage = true; }
      }
      // Customer identity is loaded from the authenticated server, fallback to default clients
      merged.clients = (Array.isArray(parsed.clients) && parsed.clients.length > 0)
        ? parsed.clients
        : JSON.parse(JSON.stringify(defaultData.clients || []));
      if (!Array.isArray(merged.notifications) || !merged.notifications.some((n) => n.role === 'CLIENT')) {
        const nonClientNotifs = (merged.notifications || []).filter((n) => n.role !== 'CLIENT');
        const clientNotifs = defaultData.notifications.filter((n) => n.role === 'CLIENT');
        merged.notifications = [...nonClientNotifs, ...clientNotifs];
      }
      if (!Array.isArray(merged.roles) || !merged.roles.some((r) => r.key === 'SUPPLIER')) {
        merged.roles = JSON.parse(JSON.stringify(defaultData.roles));
      }

      if (!Array.isArray(merged.suppliers) || merged.suppliers.length === 0) {
        merged.suppliers = JSON.parse(JSON.stringify(defaultData.suppliers));
      }
      if (!Array.isArray(merged.purchases)) {
        merged.purchases = JSON.parse(JSON.stringify(defaultData.purchases));
      }
      if (!Array.isArray(merged.warehouseRequests)) {
        merged.warehouseRequests = JSON.parse(JSON.stringify(defaultData.warehouseRequests));
      }

      // Pastikan seluruh master produk dasar (MAT-001 s/d MAT-012, termasuk MAT-005 Cat Epoxy Floor) selalu ada di store.products
      if (Array.isArray(merged.products)) {
        defaultData.products.forEach((dp) => {
          const existing = merged.products.find(
            (p) => (p.sku && dp.sku && p.sku.toLowerCase() === dp.sku.toLowerCase()) ||
                   (p.name && dp.name && p.name.toLowerCase() === dp.name.toLowerCase())
          );
          if (!existing) {
            merged.products.unshift(JSON.parse(JSON.stringify(dp)));
          }
        });
      } else {
        merged.products = JSON.parse(JSON.stringify(defaultData.products));
      }

      if (merged.currentUser && merged.currentUser.role === 'CLIENT' && !merged.currentUser.clientId) {
        merged.currentUser.clientId = 1;
      }
      if (Array.isArray(merged.orders)) {
        const seenOrderNumbers = new Set();
        merged.orders = merged.orders
          .filter((o) => {
            if (!o || !o.orderNumber) return false;
            const key = o.orderNumber.toUpperCase().trim();
            if (seenOrderNumbers.has(key)) return false;
            seenOrderNumbers.add(key);
            return true;
          })
          .map((o) => ({
            ...o,
            isHold: o.isHold ?? false,
            holdReason: o.holdReason ?? null,
            holdBy: o.holdBy ?? null,
            holdDate: o.holdDate ?? null,
            followUpLogs: Array.isArray(o.followUpLogs) ? o.followUpLogs : [],
          }))
          .sort((a, b) => {
            const dateA = a.date || '';
            const dateB = b.date || '';
            if (dateA !== dateB) return dateB.localeCompare(dateA);
            const numA = parseInt(String(a.orderNumber || '').replace(/\D/g, ''), 10) || Number(a.id) || 0;
            const numB = parseInt(String(b.orderNumber || '').replace(/\D/g, ''), 10) || Number(b.id) || 0;
            return numB - numA;
          });
      } else {
        merged.orders = [];
      }
      if (!Array.isArray(merged.deliveryNotes)) {
        merged.deliveryNotes = JSON.parse(JSON.stringify(defaultData.deliveryNotes || []));
      }
      if (!Array.isArray(merged.shipments)) {
        merged.shipments = JSON.parse(JSON.stringify(defaultData.shipments || []));
      }

      if (Array.isArray(merged.notifications)) {
        const seenNotifs = new Set();
        merged.notifications = merged.notifications.filter((n) => {
          if (!n || n.id === 99) return false;
          const key = `${n.role}_${n.title}_${n.message}`;
          if (seenNotifs.has(key)) return false;
          seenNotifs.add(key);
          return true;
        });
      } else {
        merged.notifications = [];
      }
      merged.wilayahDki = WILAYAH_DKI;
      merged.companyBankAccount = defaultData.companyBankAccount;
      merged.marketingStaff = JSON.parse(JSON.stringify(defaultData.marketingStaff));
      return merged;
    } catch (e) {
      console.warn('Failed to parse saved state, resetting to defaults', e);
    }
  }
  const defaults = JSON.parse(JSON.stringify(defaultData));
  applyAuthenticatedUser(defaults);
  return defaults;
}

export const store = reactive(loadInitialState());
export const clientSync = reactive({ loading: false, error: '' });
let clientLoadPromise = null;
const clientBusy = new Set();
export const orderSync = reactive({ loading: false, error: '', busy: false });
let orderLoadPromise = null;
const syncedOrders = reactive(new Map());

function canIssueInvoice(order) {
  return order?.serverManaged && order.status === 'Siap Invoice' && !order.invoice &&
    order.stockReadiness?.ready && order.submittedAt &&
    (order.grandTotal <= 5000000 || Boolean(order.approvalDate));
}

export const readyInvoiceOrders = computed(() => Array.from(syncedOrders.values()).filter(canIssueInvoice));
export const issuedInvoices = computed(() => Array.from(syncedOrders.values(), order => order.invoice)
  .filter(Boolean).sort((a, b) => String(b.invoiceDate ?? '').localeCompare(String(a.invoiceDate ?? '')) || String(b.invoiceNumber).localeCompare(String(a.invoiceNumber))));

function requireOrderRole(role) {
  if (store.currentUser?.role !== role) throw new Error('Peran Anda tidak berhak melakukan tindakan ini.');
}

function mergeServerOrder(data, target) {
  const existing = target || store.orders.find(order => order.orderNumber === data.orderNumber);
  // A delayed list response may predate a write in this session. A browser cache
  // from a previous session is never evidence of a newer server version.
  if (existing && syncedOrders.get(data.orderNumber)?.version > data.version) return existing;
  syncedOrders.set(data.orderNumber, data);
  // Keep the existing UI identity and document references when upgrading a local draft.
  const documents = Object.fromEntries(['po', 'suratJalan', 'bast', 'faktur'].map(key => [key, {
    exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null,
  }]));
  const value = { documents, date: new Date().toISOString().slice(0, 10), ...data, id: existing?.id ?? data.id,
    isHold: false, holdReason: null, holdBy: null, holdDate: null };
  if (existing) {
    value.documents = { ...documents, ...existing.documents };
    // Old browser-only Hold flags cannot override a successfully read server order.
    // Keep their original metadata and local history instead of silently losing notes.
    if (existing.isHold || existing.holdReason || existing.holdBy || existing.holdDate) {
      value.legacyHold = existing.legacyHold || { isHold: Boolean(existing.isHold), holdReason: existing.holdReason ?? null,
        holdBy: existing.holdBy ?? null, holdDate: existing.holdDate ?? null };
    }
    // Existing downstream modules still keep follow-ups and shipping/payment events locally.
    // Refresh only the server-owned history; do not discard those existing records.
    if (existing.serverManaged || value.legacyHold) {
      const localEvents = (existing.timeline || []).filter(event => event.id == null);
      value.timeline = [...(data.timeline || []), ...localEvents];
    }
    Object.assign(existing, value);
  } else store.orders.unshift(value);
  if (data.invoice) {
    const invoice = store.invoices.find(item => item.invoiceNumber === data.invoice.invoiceNumber);
    if (invoice) Object.assign(invoice, data.invoice);
    else store.invoices.unshift(data.invoice);
  }
  for (const event of data.timeline || []) {
    const id = `order-${data.orderNumber}-${event.id}`;
    if (!store.activityLogs.some(log => log.id === id)) store.activityLogs.unshift({
      id, action: event.stage, target: data.orderNumber, details: event.notes,
      user: event.user, role: event.role, timestamp: event.timestamp,
    });
  }
  store.orders.sort((a, b) => {
    const dateA = a.date || '';
    const dateB = b.date || '';
    if (dateA !== dateB) return dateB.localeCompare(dateA);
    const numA = parseInt(String(a.orderNumber || '').replace(/\D/g, ''), 10) || Number(a.id) || 0;
    const numB = parseInt(String(b.orderNumber || '').replace(/\D/g, ''), 10) || Number(b.id) || 0;
    return numB - numA;
  });
  return existing || value;
}

async function runOrderAction(action) {
  if (orderSync.busy) throw new Error('Tunggu hingga proses pesanan selesai.');
  orderSync.busy = true;
  try { return await action(); } finally { orderSync.busy = false; }
}

const purchaseBusy = new Set();
let purchaseLoadPromise = null;

async function persistPurchaseProgress(purchase, action) {
  const existing = store.purchases.find(item => item.purchase_code === purchase.purchase_code);
  try {
    if (store.currentUser?.role === 'SUPPLIER' && !existing?.progress_persisted
      && existing?.supplier_status && existing.supplier_status !== 'Menunggu Konfirmasi') {
      try {
        const recovered = await purchaseWorkflow.progress(existing, 'recover');
        mergePurchaseRecords(store.purchases, [recovered]);
        purchase.progress_version = recovered.progress_version;
      } catch (e) {
        if (e?.response?.status !== 404) console.warn('[PO Recovery]', e);
      }
    }
    const saved = await purchaseWorkflow.progress(purchase, action);
    mergePurchaseRecords(store.purchases, [saved]);
    window.__alpharoRealtimePurchaseSync?.(saved);
  } catch (err) {
    if (err?.response?.status === 404) {
      throw new Error('PO belum tersimpan di server. Muat ulang dan pastikan PO valid sebelum melanjutkan.');
    }
    throw err;
  }
}

// Auto-save on change (debounced 200ms with storage sanitization)
watch(
  () => store,
  (newVal) => {
    if (preserveLegacyStorage) return;
    if (storageSaveTimer) clearTimeout(storageSaveTimer);
    storageSaveTimer = setTimeout(() => {
      saveStateToStorage(newVal);
    }, 200);
  },
  { deep: true }
);

if (typeof window !== 'undefined') {
  window.addEventListener('beforeunload', () => {
    if (storageSaveTimer) {
      clearTimeout(storageSaveTimer);
      saveStateToStorage(store);
    }
  });
}

// Store Actions & Business Logic
export const storeActions = {
  async createWarehouseRequest(payload) {
    const response = await api.post('/warehouse/requests', payload);
    const saved = response.data?.data;
    if (!saved?.id || !saved.request_code) throw new Error('Server tidak mengembalikan nomor pengajuan yang tersimpan.');
    const record = {
      ...saved, requestCode: saved.request_code, date: saved.request_date,
      requestedBy: saved.requested_by, projectName: saved.project_name,
      totalEstCost: Number(saved.total_est_cost),
      items: (saved.items || []).map(item => ({ ...item, estPrice: Number(item.est_price || 0) })),
    };
    if (!store.warehouseRequests) store.warehouseRequests = [];
    store.warehouseRequests = [record, ...store.warehouseRequests.filter(item => item.id !== saved.id)];
    this.addNotification('FINANCE', 'Pengajuan Kebutuhan Barang (Pengadaan)',
      'Pengajuan ' + saved.request_code + ' untuk proyek "' + saved.project_name + '" telah tersimpan.',
      'procurement', '/finance/purchases/create?request_id=' + saved.id);
    this.addNotification('MARKETING', 'Pengadaan Barang Dalam Proses',
      'Pengajuan ' + saved.request_code + ' telah dikirim Gudang ke Finance.', 'warehouse', '/marketing/orders');
    this.addActivityLog('Pengajuan Kebutuhan Barang', saved.request_code, 'Diajukan ke Finance untuk proyek ' + saved.project_name);
    return record;
  },
  mergePurchases(purchases) { mergePurchaseRecords(store.purchases, purchases); },

  loadPurchases() {
    if (purchaseLoadPromise) return purchaseLoadPromise;
    purchaseLoadPromise = purchaseWorkflow.list().then(purchases => {
      mergePurchaseRecords(store.purchases, purchases);
    }).finally(() => { purchaseLoadPromise = null; });
    return purchaseLoadPromise;
  },

  async createPurchase(payload) {
    requireOrderRole('FINANCE');
    if (purchaseBusy.has('create')) throw new Error('Penerbitan PO sedang diproses.');
    purchaseBusy.add('create');
    try {
      const purchase = await purchaseWorkflow.create({ ...payload, created_by: store.currentUser.name });
      mergePurchaseRecords(store.purchases, [purchase]);
      const request = store.warehouseRequests.find(item =>
        (item.requestCode || item.request_code) === purchase.warehouse_request_code);
      if (request) Object.assign(request, { status: 'DIPROSES_PEMBELIAN', purchaseCode: purchase.purchase_code, purchase_code: purchase.purchase_code });
      window.__alpharoRealtimePurchaseSync?.(purchase);
      return purchase;
    } finally { purchaseBusy.delete('create'); }
  },

  loadOrders() {
    if (orderLoadPromise) return orderLoadPromise;
    if (!['MARKETING', 'DIREKTUR', 'FINANCE', 'WAREHOUSE', 'ADMINISTRASI'].includes(store.currentUser?.role)) return Promise.resolve();
    orderSync.loading = true;
    const knownAtRequest = new Map(syncedOrders);
    orderLoadPromise = (async () => {
      try {
        const orders = await orderWorkflow.list();
        const numbers = new Set(orders.map(order => order.orderNumber));
        for (const [number, previous] of knownAtRequest) {
          if (!numbers.has(number) && syncedOrders.get(number) === previous) syncedOrders.delete(number);
        }
        store.orders = store.orders.filter(order => {
          return !order.serverManaged || numbers.has(order.orderNumber) || syncedOrders.has(order.orderNumber);
        });
        for (const order of orders) {
          mergeServerOrder(order);
        }
        store.orders.sort((a, b) => {
          const dateA = a.date || '';
          const dateB = b.date || '';
          if (dateA !== dateB) return dateB.localeCompare(dateA);
          const numA = parseInt(String(a.orderNumber || '').replace(/\D/g, ''), 10) || Number(a.id) || 0;
          const numB = parseInt(String(b.orderNumber || '').replace(/\D/g, ''), 10) || Number(b.id) || 0;
          return numB - numA;
        });
        orderSync.error = '';
      } catch {
        orderSync.error = 'Data pesanan belum tersinkron. Periksa koneksi lalu muat ulang sebelum melanjutkan.';
      } finally { orderSync.loading = false; orderLoadPromise = null; }
    })();
    return orderLoadPromise;
  },

  async saveOrderDraft(order) {
    requireOrderRole('MARKETING');
    if (!['Draft', 'Diajukan Client'].includes(order.status)) throw new Error('Rincian pesanan terkunci setelah diajukan.');
    return runOrderAction(async () => mergeServerOrder(await orderWorkflow.save(withOrderCustomerProfile(order)), store.orders.find(item => item.orderNumber === order.orderNumber)));
  },
  async checkOrderStock(order) {
    requireOrderRole('MARKETING');
    return runOrderAction(async () => mergeServerOrder(await orderWorkflow.stockCheck(order), order));
  },
  resetToDefaults() {
    syncedOrders.clear();
    localStorage.removeItem(STORAGE_KEY);
    const defaults = JSON.parse(JSON.stringify(defaultData));
    Object.assign(store, defaults);
  },

  setAuthenticatedUser(user) {
    const roleObj = store.roles.find((r) => r.key === user.role);
    if (roleObj) {
      const activeSupplier = (user.role === 'SUPPLIER' && store.suppliers)
        ? (store.suppliers.find((s) => s.email?.toLowerCase() === user.email?.toLowerCase()) || store.suppliers[0])
        : null;

      store.currentUser = {
        id: user.id,
        name: activeSupplier ? `${activeSupplier.contactPerson} (${activeSupplier.name})` : user.name,
        email: user.email,
        role: roleObj.key,
        division: roleObj.division,
        avatar: store.currentUser.avatar,
        phone: user.phone || '',
        clientId: roleObj.key === 'CLIENT' ? 1 : null,
        supplierId: activeSupplier ? activeSupplier.id : null,
        supplierCode: activeSupplier ? activeSupplier.code : null,
        supplierName: activeSupplier ? activeSupplier.name : null,
      };
      localStorage.setItem('user', JSON.stringify(store.currentUser));
    }
  },

  switchRole(roleKey, customSupplier = null) {
    const roleObj = store.roles.find((r) => r.key === roleKey);
    if (roleObj) {
      const activeSupplier = customSupplier || (store.suppliers ? store.suppliers[0] : null);
      store.currentUser = {
        id: roleObj.key === 'MARKETING' ? 1 : roleObj.key === 'DIREKTUR' ? 2 : roleObj.key === 'ADMINISTRASI' ? 3 : roleObj.key === 'WAREHOUSE' ? 4 : roleObj.key === 'FINANCE' ? 5 : roleObj.key === 'CLIENT' ? 6 : 7,
        name: roleObj.key === 'SUPPLIER' && activeSupplier ? `${activeSupplier.contactPerson} (${activeSupplier.name})` : roleObj.name,
        email: roleObj.key === 'SUPPLIER' && activeSupplier ? activeSupplier.email : roleObj.email,
        role: roleObj.key,
        division: roleObj.division,
        avatar: store.currentUser.avatar,
        phone: '',
        clientId: roleObj.key === 'CLIENT' ? 1 : null,
        supplierId: roleObj.key === 'SUPPLIER' && activeSupplier ? activeSupplier.id : null,
        supplierCode: roleObj.key === 'SUPPLIER' && activeSupplier ? activeSupplier.code : null,
        supplierName: roleObj.key === 'SUPPLIER' && activeSupplier ? activeSupplier.name : null,
      };
      localStorage.setItem('user', JSON.stringify(store.currentUser));
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
    const isDuplicate = store.notifications.some(
      (n) => n.role === role && n.title === title && n.message === message && !n.isRead
    );
    if (isDuplicate) return;

    const notifItem = {
      id: Date.now() + Math.floor(Math.random() * 1000),
      role,
      title,
      message,
      time: 'Baru saja',
      isRead: false,
      category,
      link,
      timestamp: new Date().toISOString(),
    };

    store.notifications.unshift(notifItem);

    if (typeof window !== 'undefined' && window.__alpharoRealtimeDispatch) {
      window.__alpharoRealtimeDispatch(notifItem);
    }
  },

  // CAPS-11: customer mutations must succeed on the server before updating the UI.
  loadClients() {
    if (clientLoadPromise) return clientLoadPromise;
    if (!['MARKETING', 'WAREHOUSE', 'FINANCE', 'ADMINISTRASI', 'DIREKTUR'].includes(store.currentUser?.role)) {
      if (!store.clients.length) {
        store.clients = JSON.parse(JSON.stringify(defaultData.clients || []));
      }
      return Promise.resolve(store.clients);
    }
    clientSync.loading = true;
    clientLoadPromise = (async () => {
      try {
        const response = await api.get('/customers');
        if (!Array.isArray(response.data?.data) || response.data.data.some(client => !client.id || !client.name || !client.code || !client.serverManaged)) throw new Error('Respons customer tidak valid.');
        if (response.data.data.length > 0) {
          store.clients = response.data.data;
        } else if (!store.clients.length) {
          store.clients = JSON.parse(JSON.stringify(defaultData.clients || []));
        }
        clientSync.error = '';
        return store.clients;
      } catch (error) {
        if (!store.clients.length) {
          store.clients = JSON.parse(JSON.stringify(defaultData.clients || []));
        }
        clientSync.error = 'Data customer belum dapat dimuat dari server. Menampilkan data cadangan.';
        return store.clients;
      } finally { clientSync.loading = false; clientLoadPromise = null; }
    })();
    return clientLoadPromise;
  },

  async addClient(data) {
    requireOrderRole('MARKETING');
    if (clientLoadPromise) await clientLoadPromise;
    if (clientBusy.has('create')) throw new Error('Penyimpanan customer sedang diproses.');
    clientBusy.add('create');
    try {
      const saved = (await api.post('/customers', data)).data.data;
      if (!saved?.id || !saved.serverManaged) throw new Error('Customer belum dikonfirmasi tersimpan di server.');
      store.clients.unshift(saved);
      this.addActivityLog('Menambah Client Baru', saved.code, 'Customer tersimpan di server.');
      return saved;
    } finally { clientBusy.delete('create'); }
  },

  async updateClient(identifier, data) {
    requireOrderRole('MARKETING');
    if (clientLoadPromise) await clientLoadPromise;
    const key = String(identifier);
    if (clientBusy.has(key)) throw new Error('Perubahan customer sedang diproses.');
    clientBusy.add(key);
    try {
      const saved = (await api.put('/customers/' + encodeURIComponent(identifier), data)).data.data;
      if (!saved?.id || !saved.serverManaged) throw new Error('Perubahan customer belum dikonfirmasi server.');
      const index = store.clients.findIndex(client => client.id === saved.id);
      if (index >= 0) store.clients[index] = saved;
      else store.clients.unshift(saved);
      this.addActivityLog('Memperbarui Data Client', saved.code, 'Data customer diperbarui di server.');
      return saved;
    } finally { clientBusy.delete(key); }
  },

  async deleteClient(identifier) {
    requireOrderRole('MARKETING');
    await api.delete('/customers/' + encodeURIComponent(identifier));
    store.clients = store.clients.filter(client => client.id !== Number(identifier) && client.code !== String(identifier));
    this.addActivityLog('Menghapus Data Client', String(identifier), 'Customer yang tidak dipakai pesanan dihapus dari server.');
  },

  async createOrder(orderData) {
    requireOrderRole('MARKETING');
    const client = store.clients.find(item => item.id === Number(orderData.clientId));
    const draft = {
      ...orderData,
      orderNumber: `ORD-${new Date().getFullYear()}-${crypto.randomUUID().slice(0, 8).toUpperCase()}`,
      clientName: client?.name || orderData.clientName || 'Client Umum',
      customerProfile: client ? customerProfile(client) : orderData.customerProfile,
      date: new Date().toISOString().slice(0, 10), status: 'Draft',
    };
    const saved = await runOrderAction(async () => mergeServerOrder(await orderWorkflow.save(draft)));
    if (orderData.submitDirectly) return this.submitOrderForVerification(saved.id);
    return saved;
  },

  async deleteOrder(orderId) {
    requireOrderRole('MARKETING');
    const order = store.orders.find(item => item.id === orderId);
    if (!order || order.status !== 'Draft') throw new Error('Hanya draft yang dapat dihapus.');
    await runOrderAction(async () => {
      if (order.serverManaged) {
        await orderWorkflow.remove(order);
        syncedOrders.delete(order.orderNumber);
      }
      store.orders.splice(store.orders.indexOf(order), 1);
    });
  },

  async submitOrderForVerification(orderId) {
    requireOrderRole('MARKETING');
    const order = store.orders.find(item => item.id === orderId);
    if (!order || order.status !== 'Draft') throw new Error('Hanya draft yang dapat diajukan.');
    return runOrderAction(async () => {
      if (!order.serverManaged) mergeServerOrder(await orderWorkflow.save(withOrderCustomerProfile(order)), order);
      const result = mergeServerOrder(await orderWorkflow.submit(order), order);
      const director = result.status === 'Menunggu Verifikasi';
      this.addNotification(director ? 'DIREKTUR' : 'FINANCE', 'Pengajuan Pesanan',
        `Pesanan ${result.orderNumber}: ${result.status}.`, 'approval', director ? '/director/verification' : '/finance/invoices');
      return result;
    });
  },

  async approveOrder(orderId, notes = '') {
    requireOrderRole('DIREKTUR');
    const order = store.orders.find(item => item.id === orderId);
    if (!order?.serverManaged) throw new Error('Pesanan lama belum memiliki pengajuan server. Marketing perlu mengajukan pesanan yang valid.');
    return runOrderAction(async () => {
      const result = mergeServerOrder(await orderWorkflow.approve(order, notes), order);
      this.addNotification(['MARKETING', 'FINANCE'], 'Pesanan Siap Invoice',
        `Pesanan ${order.orderNumber} disetujui Direktur dan siap diperiksa Finance.`, 'order', '/finance/invoices');
      return result;
    });
  },

  rejectOrder() {
    throw new Error('Penolakan formal belum ditetapkan dalam alur TO-BE. Pesanan tetap menunggu persetujuan.');
  },

  // Direktur Executive Control: Hold & Release Order
  holdOrder(orderId, reason) {
    requireOrderRole('DIREKTUR');
    const order = store.orders.find((o) => o.id === orderId);
    if (order?.serverManaged) throw new Error('Tahan atau revisi pesanan server belum ditetapkan dalam alur TO-BE. Pesanan tetap mengikuti status server.');
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
    requireOrderRole('DIREKTUR');
    const order = store.orders.find((o) => o.id === orderId);
    if (order?.serverManaged) throw new Error('Lepas tahanan pesanan server belum ditetapkan dalam alur TO-BE. Pesanan tetap mengikuti status server.');
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
        `Invoice ${inv.invoiceNumber} (${inv.clientName}) belum lunas (Sisa: Rp ${Number(inv.remainingAmount || 0).toLocaleString('id-ID')}). Catatan Finance: ${note || 'Mohon dibantu konfirmasi tanggal pembayaran dengan PIC client.'}`,
        'finance',
        '/marketing/monitoring'
      );
      this.addActivityLog('Kirim Pengingat Tagihan ke Marketing', inv.invoiceNumber, `Client: ${inv.clientName}, Sisa: Rp ${Number(inv.remainingAmount || 0).toLocaleString('id-ID')}`);
    }
  },

  // Administrasi Document Actions
  async generateDocument(orderNumber, docType, docNumber) {
    if (docType === 'faktur') await this.loadOrders();
    if (docType === 'faktur' && orderSync.error) throw new Error(orderSync.error);
    const order = store.orders.find((o) => o.orderNumber === orderNumber);
    if (docType === 'faktur' && (!order?.serverManaged || !order.submittedAt || (order.grandTotal > 5000000 && !order.approvalDate) || !['Siap Invoice', 'Menunggu Pembayaran', 'Selesai'].includes(order.status))) {
      throw new Error('Pesanan belum mendapat persetujuan valid untuk invoice.');
    }
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

  createShipment() {
    throw new Error('Pengiriman belum aktif. Pembayaran lunas dan SPJ siap harus diverifikasi di server terlebih dahulu.');
  },

  updateShipmentStatus() {
    throw new Error('Status pengiriman belum terhubung ke server dan tidak dapat dikonfirmasi dari browser.');
  },

  // Finance Actions (Unified Single Module: Invoice & Payments)
  async createInvoice(invoiceData) {
    requireOrderRole('FINANCE');
    const order = syncedOrders.get(invoiceData.orderNumber);
    if (orderSync.error) throw new Error(orderSync.error);
    if (!canIssueInvoice(order)) throw new Error('Periksa kembali pengajuan, stok, dan persetujuan pesanan sebelum menerbitkan invoice.');
    return runOrderAction(async () => {
      const { data, delivery } = await orderWorkflow.invoice(order, invoiceData);
      const result = mergeServerOrder(data);
      this.addNotification('MARKETING', 'Invoice Diterbitkan',
        `Invoice ${result.invoice.invoiceNumber} diterbitkan untuk ${order.orderNumber}.`, 'finance', '/marketing/orders');
      return { ...result.invoice, delivery };
    });
  },

  recordPayment() {
    throw new Error('Pembayaran belum terhubung ke penyedia pembayaran. Bukti transfer tidak dapat menandai invoice lunas.');
  },

  // Client Order Request (Public Portal & Client Role)
  submitClientOrderRequest(data) {
    const nextId = store.orders.length ? Math.max(0, ...store.orders.map((o) => Number(o.id)).filter(Number.isFinite)) + 1 : 1;
    const orderNumber = `ORD-2026-${String(nextId).padStart(3, '0')}`;
    const subtotal = (data.items || []).reduce((sum, item) => sum + (Number(item.qty || 0) * Number(item.price || 0)), 0);
    const tax = Math.round(subtotal * 0.11);
    const grandTotal = subtotal + tax;
    const assignedMarketing = data.marketingName || 'Budi Hartono, S.T.';

    const newOrder = {
      id: nextId,
      orderNumber,
      clientId: Number(data.clientId) || 1,
      clientName: data.clientName || 'Instansi / Sekolah Rekanan',
      contactPerson: data.contactPerson || '-',
      phone: data.phone || '-',
      email: data.email || '-',
      address: data.address || '',
      projectName: data.projectName || 'Pengadaan Kebutuhan Sarana Prasarana',
      date: new Date().toISOString().split('T')[0],
      submittedBy: data.contactPerson ? `${data.contactPerson} (Client Portal)` : 'Client Portal',
      marketingName: assignedMarketing,
      status: 'Diajukan Client',
      isHold: false,
      holdReason: null,
      holdBy: null,
      holdDate: null,
      urgency: data.urgency || 'Normal',
      targetDeliveryDate: data.targetDeliveryDate || '',
      followUpLogs: [],
      approvedBy: null,
      approvalDate: null,
      approvalNotes: null,
      items: (data.items || []).map((it) => ({
        productId: it.productId || null,
        sku: it.sku || 'SKU-CUSTOM',
        name: it.name,
        qty: Number(it.qty),
        unit: it.unit || 'Unit',
        price: Number(it.price || 0),
        specs: it.specs || '',
        total: Number(it.qty) * Number(it.price || 0),
      })),
      subtotal,
      tax,
      grandTotal,
      notes: data.notes || '',
      supportingDocument: data.supportingDocument || null,
      timeline: [
        {
          stage: 'Pengajuan Client',
          user: data.contactPerson || 'Client / Sekolah',
          timestamp: new Date().toLocaleString('id-ID'),
          status: 'completed',
          notes: `Pengajuan kebutuhan barang mandiri via Portal Publik (${(data.items || []).length} item). Ditangani oleh Marketing: ${assignedMarketing}. Total Est: Rp ${grandTotal.toLocaleString('id-ID')}`,
        },
        {
          stage: 'Verifikasi & Review Marketing',
          user: assignedMarketing,
          timestamp: '-',
          status: 'in_progress',
          notes: `Menunggu review ketersediaan stok & penerbitan penawaran resmi oleh ${assignedMarketing}.`,
        },
      ],
      documents: {
        po: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
        suratJalan: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
        bast: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
        faktur: { exists: false, number: '-', status: 'Belum Dibuat', date: '-', file: null },
      },
      fulfillmentStatus: 'Menunggu Persiapan',
    };

    store.orders.unshift(newOrder);
    store.orders.sort((a, b) => {
      const dateA = a.date || '';
      const dateB = b.date || '';
      if (dateA !== dateB) return dateB.localeCompare(dateA);
      const numA = parseInt(String(a.orderNumber || '').replace(/\D/g, ''), 10) || Number(a.id) || 0;
      const numB = parseInt(String(b.orderNumber || '').replace(/\D/g, ''), 10) || Number(b.id) || 0;
      return numB - numA;
    });

    this.addNotification(
      'MARKETING',
      'Pengajuan Pesanan Baru dari Client',
      `Client ${newOrder.clientName} mengajukan pesanan baru (${newOrder.orderNumber}) untuk ditangani oleh ${assignedMarketing}: "${newOrder.projectName}". Total est. Rp ${grandTotal.toLocaleString('id-ID')}.`,
      'order',
      '/marketing/orders'
    );

    this.addActivityLog('Pengajuan Pesanan Baru Client', orderNumber, `Client: ${newOrder.clientName}, PIC Marketing: ${assignedMarketing}, Nilai: Rp ${grandTotal.toLocaleString('id-ID')}`);
    return newOrder;
  },

  // Marketing Accepts & Reviews Client Order Submission
  async acceptClientOrder(orderId, reviewData = {}) {
    requireOrderRole('MARKETING');
    const order = store.orders.find(item => item.id === orderId || item.orderNumber === orderId);
    if (!order || !['Diajukan Client', 'Draft'].includes(order.status)) throw new Error('Pesanan sudah diajukan dan rincian terkunci.');
    const saved = await this.saveOrderDraft({
      ...order, notes: reviewData.notes || order.notes,
      items: reviewData.items || order.items,
    });
    if (reviewData.actionType !== 'draft') return this.submitOrderForVerification(saved.id);
    return saved;
  },

  // Marketing Clarifies or Rejects Client Order
  rejectClientOrder(orderId, reason = '') {
    requireOrderRole('MARKETING');
    const order = store.orders.find((o) => o.id === Number(orderId) || o.orderNumber === orderId);
    if (!order) return false;

    if (!['Diajukan Client', 'Draft'].includes(order.status)) throw new Error('Rincian pesanan terkunci setelah diajukan.');
    const marketingUser = store.currentUser?.name || 'Marketing';
    order.status = 'Ditolak';
    order.timeline.push({
      stage: 'Pengajuan Ditolak Marketing',
      user: marketingUser,
      timestamp: new Date().toLocaleString('id-ID'),
      status: 'completed',
      notes: `Alasan penolakan / klarifikasi: ${reason || 'Spesifikasi teknis di luar lingkup layanan atau stok tidak dapat dipenuhi.'}`,
    });

    this.addNotification(
      'CLIENT',
      'Pengajuan Pesanan Belum Dapat Diproses',
      `Mohon maaf, pengajuan #${order.orderNumber} belum dapat disetujui oleh Marketing Alpharo. Alasan: ${reason}`,
      'order',
      '/portal?tab=tracking'
    );

    this.addActivityLog('Penolakan Pengajuan Client', order.orderNumber, `Alasan: ${reason}`);
    return true;
  },

  // Client Confirm Receipt of Goods (FR-CL-05)
  confirmClientReceipt(orderNumber, receiptData = {}) {
    const order = store.orders.find((o) => o.orderNumber === orderNumber);
    if (!order) return false;

    order.fulfillmentStatus = 'Selesai';
    const recipientName = receiptData.recipientName || 'Petugas Penerima Sekolah / PIC';
    const note = receiptData.notes || 'Barang telah diperiksa fisik lengkap dan sesuai spesifikasi.';

    order.timeline.push({
      stage: 'Barang Diterima & BAST',
      user: recipientName,
      timestamp: new Date().toLocaleString('id-ID'),
      status: 'completed',
      notes: `Penerimaan barang telah dikonfirmasi oleh client (${recipientName}). Catatan: ${note}`,
    });

    const inv = store.invoices.find((i) => i.orderNumber === orderNumber);
    if (inv && inv.status === 'Lunas') {
      order.status = 'Selesai';
    }

    const shipment = store.shipments.find((s) => s.orderNumber === orderNumber);
    if (shipment) {
      shipment.status = 'Selesai';
    }

    this.addNotification(
      'WAREHOUSE',
      'Client Konfirmasi Penerimaan Barang',
      `Order ${order.orderNumber} (${order.clientName}) telah dikonfirmasi diterima di lokasi oleh ${recipientName}.`,
      'warehouse',
      '/warehouse/shipments'
    );

    this.addNotification(
      'ADMINISTRASI',
      'BAST Siap Diarsipkan',
      `Penerimaan barang order ${order.orderNumber} telah tuntas. Segera verifikasi arsip BAST bertanda tangan.`,
      'document',
      '/administration/spj'
    );

    this.addActivityLog('Konfirmasi Penerimaan Barang', orderNumber, `Diterima oleh ${recipientName}. BAST tuntas.`);
  },

  // ==========================================
  // SUPPLIER ACTIONS (PROBIS TO-BE TAHAP 4 & 5)
  // ==========================================
  addSupplier(newSup) {
    if (!store.suppliers) store.suppliers = [];
    store.suppliers.push(newSup);
  },

  async confirmPurchaseOrderBySupplier(purchaseCode, supplierNotes = '') {
    const original = (store.purchases || []).find((x) => x.purchase_code === purchaseCode || x.id === purchaseCode);
    if (!original) throw new Error('PO tidak ditemukan.');
    const p = JSON.parse(JSON.stringify(original));
    p.supplier_status = 'Dikonfirmasi Supplier';
    p.supplier_confirmation_date = new Date().toISOString().split('T')[0];
    p.supplier_notes = supplierNotes || 'Pesanan telah diterima dan diverifikasi oleh Supplier. Barang sedang dipersiapkan untuk pengiriman.';

    // PROBIS TO-BE Tahap 4: Notifikasi ke Finance & Warehouse bahwa pesanan dikonfirmasi
    await persistPurchaseProgress(p, 'confirm');
    this.addNotification(
      ['FINANCE', 'WAREHOUSE'],
      'Pesanan Dikonfirmasi Supplier',
      `Supplier ${p.default_supplier} telah mengonfirmasi kesiapan barang untuk PO ${p.purchase_code}. Barang sedang disiapkan.`,
      'purchase',
      '/finance/purchases'
    );
    this.addActivityLog('Konfirmasi Pesanan PO', p.purchase_code, `Dikonfirmasi oleh Supplier ${p.default_supplier}.`);
    return true;
  },

  async dispatchSupplierDelivery(purchaseCode, deliveryData = {}) {
    const original = (store.purchases || []).find((x) => x.purchase_code === purchaseCode || x.id === purchaseCode);
    if (!original) throw new Error('PO tidak ditemukan.');
    const p = JSON.parse(JSON.stringify(original));
    p.supplier_status = 'Dikirim ke Gudang CV';
    const currentTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
    const currentDate = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    const courier = deliveryData.courier || 'Armada Internal Supplier';
    const trackingNum = deliveryData.tracking_number || `RESI-${Math.floor(100000 + Math.random() * 900000)}`;
    const sj = deliveryData.surat_jalan || `SJ/SUP/${Date.now().toString().slice(-6)}`;

    p.delivery_info = {
      surat_jalan: sj,
      courier: courier,
      tracking_number: trackingNum,
      dispatch_date: deliveryData.dispatch_date || new Date().toISOString().split('T')[0],
      est_arrival: deliveryData.est_arrival || new Date(Date.now() + 86400000).toISOString().split('T')[0],
      shipping_status: SHIPPING_STATUS.DISPATCHED,
      current_location: `Gudang Supplier (${p.default_supplier})`,
      last_updated: currentTime,
      notes: deliveryData.notes || 'Paket telah diserahterimakan ke kurir pengantar.',
      milestones: [
        {
          id: Date.now(),
          timestamp: `${currentDate} ${currentTime}`,
          status: SHIPPING_STATUS.DISPATCHED,
          location: `Gudang Supplier (${p.default_supplier})`,
          notes: deliveryData.notes || 'Surat Jalan resmi diterbitkan dan paket diberangkatkan.',
          updated_by: p.default_supplier || 'Supplier'
        }
      ]
    };

    // PROBIS TO-BE Tahap 4 & 5: Supplier mengirim barang -> Notifikasi ke Warehouse & Finance
    await persistPurchaseProgress(p, 'dispatch');
    this.addNotification(
      'WAREHOUSE',
      'Barang Supplier Sedang Dikirim ke Gudang',
      `Supplier ${p.default_supplier} telah mengirimkan barang untuk PO ${p.purchase_code} dengan No. Surat Jalan ${p.delivery_info.surat_jalan} (${p.delivery_info.courier} - ${p.delivery_info.tracking_number}). Mohon siapkan penerimaan fisik di gudang.`,
      'delivery',
      '/warehouse/fulfillment'
    );
    this.addNotification(
      'FINANCE',
      'Pengiriman Barang Supplier Berangkat',
      `Pesanan ${p.purchase_code} telah dikirim oleh Supplier ${p.default_supplier} (Surat Jalan: ${p.delivery_info.surat_jalan}).`,
      'purchase',
      '/finance/purchases'
    );
    this.addActivityLog('Kirim Barang Supplier', p.purchase_code, `No. SJ: ${p.delivery_info.surat_jalan}, Kurir: ${p.delivery_info.courier}`);
    return true;
  },

  async updateSupplierShipmentStatus(purchaseCode, updateData = {}) {
    const original = (store.purchases || []).find((x) => x.purchase_code === purchaseCode || x.id === purchaseCode);
    if (!original) throw new Error('PO tidak ditemukan.');
    const p = JSON.parse(JSON.stringify(original));

    if (!p.delivery_info) {
      p.delivery_info = {};
    }

    const currentTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
    const currentDate = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });

    if (updateData.shipping_status) p.delivery_info.shipping_status = updateData.shipping_status;
    if (updateData.courier) p.delivery_info.courier = updateData.courier;
    if (updateData.tracking_number) p.delivery_info.tracking_number = updateData.tracking_number;
    if (updateData.est_arrival) p.delivery_info.est_arrival = updateData.est_arrival;
    if (updateData.location) p.delivery_info.current_location = updateData.location;
    if (updateData.notes) p.delivery_info.notes = updateData.notes;
    p.delivery_info.last_updated = currentTime;

    if (!Array.isArray(p.delivery_info.milestones)) {
      p.delivery_info.milestones = [];
    }

    const newMilestone = {
      id: Date.now(),
      timestamp: `${currentDate} ${currentTime}`,
      status: updateData.shipping_status || 'Pembaruan Posisi Kurir',
      location: updateData.location || 'Dalam Perjalanan Menuju Gudang',
      notes: updateData.notes || '',
      updated_by: p.default_supplier || 'Supplier'
    };
    p.delivery_info.milestones.unshift(newMilestone);

    // PROBIS TO-BE: Notifikasi Real-time ke Warehouse
    await persistPurchaseProgress(p, 'tracking');
    this.addNotification(
      'WAREHOUSE',
      'Update Status Pengiriman Kurir Vendor',
      `Supplier ${p.default_supplier} memperbarui pengiriman PO ${p.purchase_code} (Resi: ${p.delivery_info.tracking_number}): "${updateData.shipping_status}" - Posisi: ${updateData.location || '-'}.`,
      'delivery',
      '/warehouse/fulfillment'
    );

    this.addActivityLog('Update Status Kurir', p.purchase_code, `Status: ${updateData.shipping_status}, Lokasi: ${updateData.location || '-'}`);

    return true;
  },

  async confirmWarehouseArrival(purchaseCode) {
    const original = (store.purchases || []).find((x) => x.purchase_code === purchaseCode || x.id === purchaseCode);
    if (!original) throw new Error('PO tidak ditemukan.');
    const p = JSON.parse(JSON.stringify(original));

    p.warehouse_arrived = true;
    if (!p.delivery_info) {
      p.delivery_info = {};
    }

    const currentTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
    const currentDate = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });

    if (!Array.isArray(p.delivery_info.milestones)) {
      p.delivery_info.milestones = [];
    }

    const newMilestone = {
      id: Date.now(),
      timestamp: `${currentDate} ${currentTime}`,
      status: 'Pesanan Tiba di Gudang & Dikonfirmasi Tim Gudang',
      location: 'Area Bongkar Muat Gudang CV Alpharo',
      notes: 'Staf gudang mengonfirmasi armada kurir telah tiba di lokasi. Siap pemeriksaan fisik.',
      updated_by: 'Staff Gudang (CV Alpharo)'
    };
    p.delivery_info.milestones.unshift(newMilestone);

    await persistPurchaseProgress(p, 'arrival');
    this.addActivityLog('Kedatangan Barang', p.purchase_code, 'Kurir telah tiba di gudang. Staf gudang siap melakukan pemeriksaan fisik.');

    return true;
  },

  async resolveSupplierDiscrepancy(purchaseCode, resolutionData = {}) {
    const original = (store.purchases || []).find((x) => x.purchase_code === purchaseCode || x.id === purchaseCode);
    if (!original) throw new Error('PO tidak ditemukan.');
    const p = JSON.parse(JSON.stringify(original));
    p.supplier_status = 'Barang Pengganti Dikirim';
    const repSJ = resolutionData.replacement_surat_jalan || `SJ-REP-${Date.now().toString().slice(-6)}`;
    const currentTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
    const currentDate = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });

    p.discrepancy_resolution = {
      action: resolutionData.action || 'Penggantian Barang Baru 100% Sesuai Spesifikasi',
      replacement_surat_jalan: repSJ,
      courier: resolutionData.courier || p.delivery_info?.courier || 'Armada Supplier',
      tracking_number: resolutionData.tracking_number || p.delivery_info?.tracking_number || '-',
      dispatch_date: resolutionData.dispatch_date || new Date().toISOString().split('T')[0],
      est_arrival: resolutionData.est_arrival || new Date(Date.now() + 86400000).toISOString().split('T')[0],
      notes: resolutionData.notes || 'Barang pengganti telah disiapkan dan dikirimkan kembali ke gudang CV Alpharo.',
      resolved_date: new Date().toISOString().split('T')[0],
    };

    // Sinkronkan info pengiriman dengan Surat Jalan Pengganti
    p.delivery_info = {
      ...(p.delivery_info || {}),
      surat_jalan: repSJ,
      courier: p.discrepancy_resolution.courier,
      tracking_number: p.discrepancy_resolution.tracking_number,
      dispatch_date: p.discrepancy_resolution.dispatch_date,
      est_arrival: p.discrepancy_resolution.est_arrival,
      shipping_status: SHIPPING_STATUS.REPLACEMENT,
      current_location: `Gudang Supplier (${p.default_supplier})`,
      last_updated: currentTime,
      notes: p.discrepancy_resolution.notes,
      is_replacement: true,
      status: 'Dalam Pengiriman',
      milestones: [
        {
          id: Date.now(),
          timestamp: `${currentDate} ${currentTime}`,
          status: 'Barang Pengganti Diberangkatkan (Retur)',
          location: `Gudang Supplier (${p.default_supplier})`,
          notes: `SJ Pengganti: ${repSJ} - ${p.discrepancy_resolution.notes}`,
          updated_by: p.default_supplier || 'Supplier'
        },
        ...(p.delivery_info?.milestones || [])
      ]
    };

    // PROBIS TO-BE Tahap 5b: Notifikasi ke Warehouse dan Finance
    await persistPurchaseProgress(p, 'replacement');
    this.addNotification(
      'WAREHOUSE',
      'Barang Pengganti Supplier Menuju Gudang',
      `Supplier ${p.default_supplier} telah mengirimkan barang pengganti untuk PO ${p.purchase_code} (SJ Pengganti: ${repSJ}). Silakan periksa fisik saat tiba.`,
      'delivery',
      '/warehouse/fulfillment'
    );
    this.addNotification(
      'FINANCE',
      'Supplier Mengirimkan Barang Pengganti',
      `Supplier ${p.default_supplier} telah memproses retur PO ${p.purchase_code} dengan mengirimkan barang pengganti (SJ: ${repSJ}).`,
      'delivery',
      '/finance/purchases'
    );
    this.addActivityLog('Kirim Barang Pengganti Supplier', p.purchase_code, `Barang pengganti dikirim (SJ: ${repSJ})`);
    return true;
  },

  async confirmSupplierReceipt(purchaseCode, receiptData = {}) {
    const purchase = (store.purchases || []).find(record => record.purchase_code === purchaseCode || record.id === purchaseCode);
    if (!purchase) throw new Error('PO tidak ditemukan.');
    const result = await purchaseWorkflow.receive(purchase, receiptData);
    mergePurchaseRecords(store.purchases, [result.purchase || result]);
    await this.loadOrders();
    return result;
  },

  async reportSupplierShipmentDiscrepancy(purchaseCode, reason = '', media = [], damagedItems = [], undamagedItems = []) {
    const original = (store.purchases || []).find((x) => x.purchase_code === purchaseCode || x.id === purchaseCode);
    if (!original) throw new Error('PO tidak ditemukan.');
    const p = JSON.parse(JSON.stringify(original));

    p.supplier_status = 'Ketidaksesuaian Fisik (Menunggu Retur)';
    p.discrepancy_reason = reason || 'Spesifikasi barang tidak sesuai atau terdapat kerusakan fisik kemasan saat tiba di gudang.';
    p.discrepancy_reported_at = new Date().toISOString().split('T')[0];
    p.discrepancy_media = Array.isArray(media) ? media : [];
    p.discrepancy_items = Array.isArray(damagedItems) ? damagedItems : [];
    p.undamaged_items = Array.isArray(undamagedItems) ? undamagedItems : [];
    p.discrepancy_info = {
      notes: p.discrepancy_reason,
      date: p.discrepancy_reported_at,
      reported_by: store.currentUser.name || 'Staff Gudang',
      items: p.discrepancy_items,
      undamaged_items: p.undamaged_items,
      media: p.discrepancy_media,
    };

    // PROBIS TO-BE Tahap 5b: Lapor ke Supplier & Finance agar diproses penggantian barang
    await persistPurchaseProgress(p, 'discrepancy');

    const damagedSummary = p.discrepancy_items.length > 0
      ? ` Barang diretur: ${p.discrepancy_items.map(d => `${d.barang} (${d.damaged_qty} ${d.unit})`).join(', ')}.`
      : '';

    this.addNotification(
      'SUPPLIER',
      'Laporan Retur / Kerusakan Barang',
      `Gudang CV Alpharo melaporkan kerusakan barang PO ${p.purchase_code}: "${p.discrepancy_reason}".${damagedSummary} Mohon segera siapkan dan kirimkan barang pengganti.`,
      'alert',
      '/supplier/portal'
    );
    this.addNotification(
      'FINANCE',
      'Laporan Ketidaksesuaian Barang Supplier (Retur)',
      `Gudang melaporkan ketidaksesuaian barang untuk PO ${p.purchase_code} dari Supplier ${p.default_supplier}: "${p.discrepancy_reason}".${damagedSummary}`,
      'alert',
      '/finance/purchases'
    );
    this.addActivityLog('Lapor Ketidaksesuaian Supplier', p.purchase_code, `Ketidaksesuaian: ${p.discrepancy_reason} (${p.discrepancy_items.length} jenis barang diretur, ${p.discrepancy_media.length} bukti media)`);
    return true;
  },

  async rejectPurchaseOrderBySupplier(purchaseCode, reason = '') {
    const original = (store.purchases || []).find((x) => x.purchase_code === purchaseCode || x.id === purchaseCode);
    if (!original) throw new Error('PO tidak ditemukan.');
    const p = JSON.parse(JSON.stringify(original));
    p.supplier_status = 'Ditolak Supplier';
    p.rejection_reason = reason || 'Stok tidak mencukupi atau kendala logistik supplier.';
    
    await persistPurchaseProgress(p, 'reject');
    this.addNotification(
      'FINANCE',
      'Pesanan Ditolak oleh Supplier',
      `Supplier ${p.default_supplier} menolak pesanan PO ${p.purchase_code}. Alasan: ${p.rejection_reason}`,
      'alert',
      '/finance/purchases'
    );
    this.addActivityLog('Penolakan PO oleh Supplier', p.purchase_code, `Alasan: ${p.rejection_reason}`);
    return true;
    return true;
  },
};


// A pending click cannot repeat a transition, notification or activity entry in the same tab.
for (const method of ['confirmPurchaseOrderBySupplier', 'dispatchSupplierDelivery', 'updateSupplierShipmentStatus',
  'confirmWarehouseArrival', 'resolveSupplierDiscrepancy', 'reportSupplierShipmentDiscrepancy',
  'rejectPurchaseOrderBySupplier', 'confirmSupplierReceipt']) {
  const action = storeActions[method];
  storeActions[method] = async function (code, ...args) {
    if (purchaseBusy.has(code)) throw new Error('Proses PO sedang berjalan.');
    purchaseBusy.add(code);
    try { return await action.call(this, code, ...args); }
    finally { purchaseBusy.delete(code); }
  };
}

