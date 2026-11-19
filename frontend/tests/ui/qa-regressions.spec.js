import { test, expect } from '@playwright/test';
import { signIn } from './helpers/session';

const modulePath = "document.querySelector('script[type=module][src*=\"/src/main.js\"]').src.replace('/main.js', '/stores/mockData.js')";

test('valid October and formerly excluded order numbers remain visible', async ({ page }) => {
  await signIn(page, 'MARKETING');
  const orders = ['ORD-2026-007', 'ORD-2026-008'].map((orderNumber, index) => ({
    id: `server-${index + 1}`, serverManaged: true, version: 1, orderNumber,
    date: '2026-10-19', clientName: 'Customer QA', projectName: 'Proyek Oktober',
    status: 'Draft', grandTotal: 111000, subtotal: 100000, tax: 11000, items: [], timeline: [],
    stockReadiness: { status: 'Menunggu Stok', items: [] }, invoice: null,
  }));
  await page.route('**/api/orders', route => route.fulfill({ json: { data: orders } }));
  await page.goto('/marketing/orders');
  for (const order of orders) await expect(page.getByRole('row').filter({ hasText: order.orderNumber })).toBeVisible();
});

test('browser-local payment and dispatch cannot fabricate successful transactions', async ({ page }) => {
  await signIn(page, 'FINANCE');
  await page.goto('/finance/payments');
  const results = await page.evaluate(async expression => {
    const { store, storeActions } = await import(eval(expression));
    store.invoices = [{ invoiceNumber: 'INV-QA', totalAmount: 100000, paidAmount: 0, remainingAmount: 100000, status: 'Belum Dibayar' }];
    const errors = [];
    for (const action of [() => storeActions.recordPayment({ invoiceNumber: 'INV-QA', amount: 100000 }), () => storeActions.createShipment({ orderNumber: 'ORD-QA' })]) {
      try { action(); } catch (error) { errors.push(error.message); }
    }
    return { errors, paid: store.invoices[0].paidAmount, status: store.invoices[0].status, shipments: store.shipments.length };
  }, modulePath);
  expect(results.errors).toHaveLength(2);
  expect(results.paid).toBe(0);
  expect(results.status).toBe('Belum Dibayar');
  expect(results.shipments).toBe(0);
  await expect(page.getByRole('note')).toContainText('Pembayaran belum aktif');
});

test('login starts empty and contains no embedded demo account shortcuts', async ({ page }) => {
  await page.goto('/login');
  await expect(page.getByLabel('Email perusahaan', { exact: true })).toHaveValue('');
  await expect(page.getByLabel('Kata sandi', { exact: true })).toHaveValue('');
  await expect(page.getByText('Akses cepat untuk uji coba', { exact: true })).toHaveCount(0);
});

test('sync failure is not presented as zero orders or an online system', async ({ page }) => {
  await signIn(page, 'MARKETING');
  await page.route('**/api/orders', route => route.fulfill({ status: 503, json: { message: 'QA offline' } }));
  await page.goto('/marketing/dashboard');
  await expect(page.locator('.kpi-card').filter({ hasText: 'Siap Invoice' })).toContainText('—');
  await expect(page.locator('.app-footer')).toContainText('Sinkronisasi terganggu');
  await expect(page.locator('.app-footer')).not.toContainText('Sistem Online');
});

test('large currency values remain fully available in KPI cards', async ({ page }) => {
  await signIn(page, 'FINANCE');
  await page.goto('/finance/dashboard');
  await page.evaluate(async expression => {
    const { store } = await import(eval(expression));
    store.invoices = [{ id: 'qa-kpi', invoiceNumber: 'INV-QA', totalAmount: '1234567890123', remainingAmount: '1234567890123', status: 'Belum Dibayar', clientName: 'QA', dueDate: '2026-12-01' }];
    store.payments = [];
  }, modulePath);
  const value = page.locator('.kpi-card').filter({ hasText: 'Total Piutang' });
  await expect(value).toContainText('1.234.567.890.123');
  const clipped = await value.locator('span').evaluateAll(nodes => nodes.some(node => getComputedStyle(node).textOverflow === 'ellipsis' && node.scrollWidth > node.clientWidth));
  expect(clipped).toBe(false);
  expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
});

test('warehouse uses current server stock and keeps rejected edits out of the catalog', async ({ page }) => {
  await signIn(page, 'WAREHOUSE');
  const product = { id: 99, sku: 'QA-CATALOG', name: 'Barang Katalog QA', category: 'QA', unit: 'PCS', stock: 3, min_stock: 1, sell_price: 100000, last_buy_price: 50000, hpp: 50000, status: 'Tersedia' };
  await page.addInitScript(product => localStorage.setItem('alpharo_integrated_system_v3', JSON.stringify({ products: [{ ...product, stock: 999 }] })), product);
  await page.route('**/api/**', route => {
    const path = new URL(route.request().url()).pathname;
    if (route.request().method() === 'PUT') return route.fulfill({ status: 409, json: { message: 'Stok QA sedang dialokasikan.' } });
    return route.fulfill({ json: { success: true, data: path === '/api/products' ? [product] : [] } });
  });
  await page.goto('/warehouse/products');
  const row = page.getByRole('row').filter({ hasText: product.sku });
  await expect(row).toBeVisible();
  expect(await page.evaluate(async expression => (await import(eval(expression))).store.products.find(item => item.sku === 'QA-CATALOG').stock, modulePath)).toBe(3);
  await row.getByTitle('Edit Data Barang', { exact: true }).click();
  await page.getByRole('button', { name: 'Simpan Data Barang', exact: true }).click();
  await expect(page.getByText('Stok QA sedang dialokasikan.', { exact: true })).toBeVisible();
  await expect(page.getByRole('dialog', { name: 'Edit Data Barang', exact: true })).toBeVisible();
  await expect(row).toContainText(product.name);
});

test('an empty server catalog does not silently become demonstration stock', async ({ page }) => {
  await signIn(page, 'WAREHOUSE');
  await page.route('**/api/**', route => route.fulfill({ json: { success: true, data: [] } }));
  await page.goto('/warehouse/products');
  await expect(page.getByText('Memuat katalog data barang...', { exact: true })).toHaveCount(0);
  await expect.poll(() => page.evaluate(async expression => (await import(eval(expression))).store.products.length, modulePath)).toBe(0);
  await expect(page.getByRole('alert').filter({ hasText: 'Data contoh' })).toHaveCount(0);
});

test('rejected warehouse requests do not create local records or notifications and successful ones use server identifiers', async ({ page }) => {
  await signIn(page, 'WAREHOUSE');
  let rejected = true;
  await page.route('**/api/**', route => {
    if (route.request().method() === 'POST' && route.request().url().endsWith('/warehouse/requests')) {
      return rejected
        ? route.fulfill({ status: 422, json: { message: 'Pengajuan QA ditolak.' } })
        : route.fulfill({ status: 201, json: { data: { id: 912, request_code: 'REQ-SERVER-QA', request_date: '2026-11-19', requested_by: 'UI WAREHOUSE', project_name: 'Proyek QA', total_est_cost: '6000', items: [{ sku: 'QA', name: 'Barang QA', qty: 2, est_price: 3000 }] } } });
    }
    return route.fulfill({ json: { success: true, data: [] } });
  });
  await page.goto('/warehouse/fulfillment');
  const submit = () => page.evaluate(async expression => {
    const { store, storeActions } = await import(eval(expression));
    const before = { records: store.warehouseRequests.length, notifications: store.notifications.length, logs: store.activityLogs.length };
    let error;
    try { await storeActions.createWarehouseRequest({ purpose: 'QA', requested_by: 'QA', items: [{ sku: 'QA', name: 'Barang QA', qty: 2 }] }); } catch (e) { error = e.response?.data?.message || e.message; }
    return { before, error, records: store.warehouseRequests.length, notifications: store.notifications.length, logs: store.activityLogs.length, record: store.warehouseRequests[0] };
  }, modulePath);
  const failed = await submit();
  expect(failed.error).toBe('Pengajuan QA ditolak.');
  expect(failed.records).toBe(failed.before.records);
  expect(failed.notifications).toBe(failed.before.notifications);
  expect(failed.logs).toBe(failed.before.logs);
  rejected = false;
  const saved = await submit();
  expect(saved.error).toBeUndefined();
  expect(saved.record).toMatchObject({ id: 912, requestCode: 'REQ-SERVER-QA', totalEstCost: 6000 });
});

test('supplier form keeps a rejected create open and does not fabricate a supplier', async ({ page }) => {
  await signIn(page, 'FINANCE');
  await page.route('**/api/**', route => route.fulfill(route.request().method() === 'POST'
    ? { status: 422, json: { message: 'Supplier QA tidak valid.' } }
    : { json: { success: true, data: [] } }));
  await page.goto('/finance/suppliers');
  await page.getByRole('button', { name: 'Tambah', exact: true }).click();
  const dialog = page.getByRole('dialog', { name: 'Tambah Supplier Baru', exact: true });
  await dialog.getByLabel('Nama Supplier / Perusahaan').fill('Supplier QA ditolak');
  await dialog.getByRole('button', { name: 'Simpan Supplier', exact: true }).click();
  await expect(page.getByText('Supplier QA tidak valid.', { exact: true })).toBeVisible();
  await expect(dialog).toBeVisible();
  expect(await page.evaluate(async expression => (await import(eval(expression))).store.suppliers.length, modulePath)).toBe(0);
});

test('supplier load errors do not display demonstration suppliers as server data', async ({ page }) => {
  await signIn(page, 'FINANCE');
  await page.route('**/api/**', route => route.fulfill({ status: 503, json: { message: 'QA offline' } }));
  await page.goto('/finance/suppliers');
  await expect(page.getByRole('alert').filter({ hasText: 'Data supplier belum dapat dimuat dari server.' })).toBeVisible();
  await expect(page.getByRole('row').filter({ hasText: 'PT Synnex Metrodata Indonesia' })).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Muat ulang supplier', exact: true })).toBeVisible();
});
