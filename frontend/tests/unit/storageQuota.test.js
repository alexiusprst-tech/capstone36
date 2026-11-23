import { test } from 'node:test';
import assert from 'node:assert';
import { prepareStorageSnapshot } from '../../src/services/storageManager.js';

test('prepareStorageSnapshot strips massive catalog and prevents localStorage bloat', () => {
  // Simulate 2,500 products (like the RKAS template catalog)
  const hugeProducts = Array.from({ length: 2500 }, (_, i) => ({
    id: i + 1,
    sku: `SKU-${i}`,
    name: `Nama Barang Nomor ${i} untuk Keperluan Pengadaan Sekolah`,
    category: 'Material Pengadaan',
    unit: 'Unit',
    stock: 100,
    min_stock: 10,
    sell_price: 250000,
    hpp: 200000,
    last_buy_price: 200000,
    description: 'Deskripsi panjang spesifikasi teknis barang pengadaan resmi tahun anggaran 2026',
    status: 'Tersedia',
  }));

  const state = {
    currentUser: { id: 1, role: 'WAREHOUSE' },
    wilayahDki: [{ kota: 'JAKARTA', kecamatan: 'CIRACAS', kelurahan: 'RAMBUTAN' }],
    products: hugeProducts,
    orders: [{ id: 1, orderNumber: 'ORD-001', grandTotal: 5000000 }],
    purchases: [{ id: 1, purchase_code: 'PO-001' }],
    warehouseRequests: [{ id: 1, requestCode: 'REQ-001' }],
    activityLogs: Array.from({ length: 500 }, (_, i) => ({ id: i, action: `Aksi ${i}` })),
    stockMovements: Array.from({ length: 500 }, (_, i) => ({ id: i, type: 'Masuk' })),
    notifications: Array.from({ length: 500 }, (_, i) => ({ id: i, title: `Notif ${i}` })),
  };

  // Raw state serialized size
  const rawJson = JSON.stringify(state);
  assert.ok(rawJson.length > 500 * 1024, `Raw JSON should be large (>500KB), was: ${rawJson.length}`);

  // Snapshot for storage
  const snapshot = prepareStorageSnapshot(state);
  const snapshotJson = JSON.stringify(snapshot);

  // Snapshot size should be well under 100KB, preventing QuotaExceededError
  assert.ok(snapshotJson.length < 100 * 1024, `Snapshot JSON should be under 100KB, was: ${snapshotJson.length}`);
  assert.strictEqual(snapshot.products.length, 0, 'Massive catalog should not be serialized into localStorage');
  assert.strictEqual(snapshot.stockMovements.length, 100, 'Stock movements should be capped at 100');
  assert.strictEqual(snapshot.activityLogs.length, 100, 'Activity logs should be capped at 100');
  assert.strictEqual(snapshot.notifications.length, 100, 'Notifications should be capped at 100');
});

test('prepareStorageSnapshot preserves small test products', () => {
  const smallState = {
    products: [{ id: 1, sku: 'TEST-SKU', name: 'Barang Uji', stock: 5 }],
  };
  const snapshot = prepareStorageSnapshot(smallState);
  assert.strictEqual(snapshot.products.length, 1);
  assert.strictEqual(snapshot.products[0].sku, 'TEST-SKU');
});
