import { test, expect } from '@playwright/test';
import { signIn } from './helpers/session';
import fs from 'node:fs';

async function fillCustomer(scope, name) {
  await scope.getByLabel('Nama Customer', { exact: false }).fill(name);
  await scope.getByRole('textbox', { name: 'Alamat *', exact: true }).fill('Jalan Pengujian 19');
  await scope.getByLabel('Contact Person', { exact: false }).fill('PIC Pengujian');
  await scope.getByLabel('No HP', { exact: false }).fill('081234567890');
}

test('customer persists across sessions, can be selected on an order and cannot be deleted while referenced', async ({ page, browser }, testInfo) => {
  test.setTimeout(90000);
  const name = 'Customer Sprint 1 ' + testInfo.project.name;
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await signIn(page, 'MARKETING');
  await page.goto('/marketing/clients/create');
  await fillCustomer(page, name);
  const createdResponse = page.waitForResponse(response => response.url().endsWith('/api/customers') && response.request().method() === 'POST');
  await page.getByRole('button', { name: 'Simpan', exact: true }).click();
  const response = await createdResponse;
  expect(response.status()).toBe(201);
  const customer = (await response.json()).data;
  await expect(page).toHaveURL(/\/marketing\/clients$/);
  await expect(page.getByRole('row').filter({ hasText: name })).toBeVisible();
  const second = await browser.newContext({ viewport: testInfo.project.use.viewport });
  try {
    const other = await second.newPage();
    await signIn(other, 'MARKETING');
    await other.goto('/marketing/clients/' + customer.id + '/edit');
    await expect(other.getByLabel('Nama Customer', { exact: false })).toHaveValue(name);
    await other.getByLabel('No HP', { exact: false }).fill('081111111111');
    await other.getByRole('button', { name: 'Simpan', exact: true }).click();
    await expect(other).toHaveURL(/\/marketing\/clients$/);
    await page.reload();
    await expect(page.getByRole('row').filter({ hasText: name })).toContainText('081111111111');
    await page.goto('/marketing/orders/create');
    await page.getByLabel('Customer', { exact: false }).selectOption(String(customer.id));
    await expect(page.getByLabel('Customer', { exact: false })).toHaveValue(String(customer.id));
    const saved = await page.evaluate(async ({ customer, number }) => {
      const module = document.querySelector('script[type=module][src*="/src/main.js"]').src.replace('/main.js', '/services/api.js');
      const api = (await import(module)).default;
      return (await api.put('/orders/' + number, { version: 0, details: { clientId: customer.id, clientName: customer.name, projectName: 'Integrasi Customer Sprint 1' },
        items: [{ sku: 'SP1-CUSTOMER-ITEM', name: 'Barang Uji Sprint 1', unit: 'PCS', qty: 1, price: 1000 }] })).data.data;
    }, { customer, number: 'ORD-CUSTOMER-' + testInfo.project.name.toUpperCase() });
    expect(saved.stockReadiness.status).toBe('Siap Diajukan');
    expect(saved.grandTotal).toBe(1110);
    await page.goto('/marketing/orders');
    await expect(page.getByRole('row').filter({ hasText: saved.orderNumber })).toContainText(name);
    await page.goto('/marketing/clients');
    const row = page.getByRole('row').filter({ hasText: name });
    await row.getByTitle('Hapus Customer', { exact: true }).click();
    await page.getByRole('button', { name: 'Ya, Hapus Customer', exact: true }).click();
    await expect(page.getByText('Customer masih dipakai pesanan dan tidak dapat dihapus. Referensi transaksi harus dipertahankan.', { exact: true })).toBeVisible();
    await expect(row).toBeVisible();
    expect(errors).toEqual([]);
  } finally { await second.close(); }
});

test('quick customer form keeps rejected server writes open without selecting an invented customer', async ({ page }) => {
  await signIn(page, 'MARKETING');
  await page.route('**/api/customers', route => route.request().method() === 'POST'
    ? route.fulfill({ status: 422, json: { message: 'Customer QA ditolak server.' } })
    : route.continue());
  await page.goto('/marketing/orders/create');
  await page.getByTitle('Tambah Customer', { exact: true }).click();
  const dialog = page.getByRole('dialog', { name: 'Tambah Customer', exact: true });
  await fillCustomer(dialog, 'Customer yang ditolak');
  await dialog.getByRole('button', { name: 'Simpan', exact: true }).click();
  await expect(page.getByText('Customer QA ditolak server.', { exact: true })).toBeVisible();
  await expect(dialog).toBeVisible();
  await expect(page.locator('#order-draft-4')).toHaveValue('');
});

test('legacy browser customers are preserved separately and are never substituted for server customers', async ({ page }) => {
  await signIn(page, 'MARKETING');
  await page.addInitScript(() => {
    localStorage.removeItem('alpharo_customers_legacy_v1');
    localStorage.setItem('alpharo_integrated_system_v3', JSON.stringify({ clients: [{ id: 22, code: 'LOCAL-22', name: 'Customer Perangkat Lama', address: 'Alamat lama' }] }));
  });
  await page.route('**/api/customers', route => route.fulfill({ json: { data: [] } }));
  await page.goto('/marketing/clients');
  await expect(page.getByRole('note')).toContainText('Data customer lama');
  await expect(page.getByRole('row').filter({ hasText: 'Customer Perangkat Lama' })).toHaveCount(0);
  const backup = await page.evaluate(() => JSON.parse(localStorage.getItem('alpharo_customers_legacy_v1')));
  expect(backup.clients[0]).toMatchObject({ id: 22, code: 'LOCAL-22', name: 'Customer Perangkat Lama' });
  const downloaded = page.waitForEvent('download');
  await page.getByRole('button', { name: 'Unduh salinan customer lama', exact: true }).click();
  const download = await downloaded;
  expect(download.suggestedFilename()).toBe('customer-perangkat-lama.json');
  expect(JSON.parse(fs.readFileSync(await download.path(), 'utf8')).clients[0].code).toBe('LOCAL-22');
});

test('a full storage quota does not overwrite the only legacy customer copy', async ({ page }) => {
  await signIn(page, 'MARKETING');
  await page.addInitScript(() => {
    localStorage.removeItem('alpharo_customers_legacy_v1');
    localStorage.setItem('alpharo_integrated_system_v3', JSON.stringify({ clients: [{ id: 33, code: 'LOCAL-33', name: 'Salinan yang harus dipertahankan' }] }));
    const original = Storage.prototype.setItem;
    Storage.prototype.setItem = function (key, value) {
      if (key === 'alpharo_customers_legacy_v1') throw new DOMException('Storage penuh', 'QuotaExceededError');
      return original.call(this, key, value);
    };
  });
  await page.route('**/api/customers', route => route.fulfill({ json: { data: [] } }));
  await page.goto('/marketing/clients');
  await expect(page.getByRole('note')).toContainText('Data customer lama');
  expect(await page.evaluate(() => JSON.parse(localStorage.getItem('alpharo_integrated_system_v3')).clients[0].code)).toBe('LOCAL-33');
  const pending = page.waitForEvent('download');
  await page.getByRole('button', { name: 'Unduh salinan customer lama', exact: true }).click();
  const downloaded = await pending;
  expect(JSON.parse(fs.readFileSync(await downloaded.path(), 'utf8')).clients[0].code).toBe('LOCAL-33');
});

for (const mode of ['full form', 'quick modal']) {
  test('identity inference follows incremental typing and preserves manual edits in ' + mode, async ({ page }, testInfo) => {
    await signIn(page, 'MARKETING');
    let scope = page;
    if (mode === 'full form') await page.goto('/marketing/clients/create');
    else {
      await page.goto('/marketing/orders/create');
      await page.getByTitle('Tambah Customer', { exact: true }).click();
      scope = page.getByRole('dialog', { name: 'Tambah Customer', exact: true });
    }
    const name = scope.getByRole('textbox', { name: 'Nama Customer *', exact: true });
    const official = scope.getByRole('textbox', { name: 'Nama Organisasi Lengkap', exact: true });
    const address = scope.getByRole('textbox', { name: 'Alamat *', exact: true });
    const letterhead = scope.getByRole('textbox', { name: 'Alamat Kop Surat', exact: true });
    await name.pressSequentially('SMA 18 QA');
    await address.pressSequentially('Jalan Pengujian 19');
    await expect(official).toHaveValue(mode === 'full form' ? 'SMA 18 QA' : 'SEKOLAH MENENGAH ATAS 18 QA');
    await expect(letterhead).toHaveValue('Jalan Pengujian 19');
    await official.fill('NAMA RESMI YANG DIPERTAHANKAN');
    await letterhead.fill('Alamat kop resmi yang dipertahankan');
    await name.fill('Alias Baru');
    await address.fill('Alamat kontak baru');
    await expect(official).toHaveValue('NAMA RESMI YANG DIPERTAHANKAN');
    await expect(letterhead).toHaveValue('Alamat kop resmi yang dipertahankan');
    await page.screenshot({ path: testInfo.outputPath('identity-typing.png'), fullPage: true });
  });
}
