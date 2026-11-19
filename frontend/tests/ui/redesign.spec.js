import { signIn } from './helpers/session';
import { customerFixture } from './helpers/customer-fixtures';
import fs from 'node:fs';
import { test, expect } from '@playwright/test';
import * as XLSX from 'xlsx';

// Exercise every current screen without sending any business mutations to the API.
const source = fs.readFileSync(new URL('../../../backend/config/pages.php', import.meta.url), 'utf8');
const screens = [...source.matchAll(/\['path' => '([^']+)'([^\n]+)\]/g)]
  .filter(([, , body]) => body.includes("'component' =>"))
  .map(([, route, body]) => ({ route: route.replace('{id}', '1'), role: body.match(/'role' => '([^']+)'/)?.[1] }));

async function isolate(page, role) {
  await signIn(page, role);
  await page.route('**/api/**', route => route.fulfill({ status: 503, contentType: 'application/json', body: '{"message":"API isolated for UI review"}' }));
  await page.addInitScript(role => {
    localStorage.clear();
    if (role) {
      localStorage.setItem('token', 'ui-review-only');
      localStorage.setItem('user', JSON.stringify({ id: 1, name: 'Pengguna Review', role, email: 'review@example.test' }));
    }
  }, role);
}

for (const { route, role } of screens) {
  test(`screen ${route}`, async ({ page }, testInfo) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await isolate(page, role);
    await page.goto(route);
    await expect(page.locator('h1, h2').first(), `Render errors: ${errors.join('; ')}`).toBeVisible();
    await expect(page).toHaveURL(new RegExp(route.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '(?:\\?.*)?$'));
    expect(errors).toEqual([]);
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth);
    expect(overflow, `Viewport overflow on ${route}`).toBeLessThanOrEqual(1);
    await page.screenshot({ path: testInfo.outputPath('screen.png'), fullPage: true, animations: 'disabled' });
  });
}

test('public portal keeps all three existing service tabs', async ({ page }) => {
  await isolate(page);
  await page.goto('/portal');
  await page.getByRole('navigation', { name: 'Navigasi portal publik' }).getByRole('button', { name: 'Lacak Pesanan & Pembayaran', exact: true }).click();
  await expect(page.getByText('1. Data Instansi & Pemohon', { exact: true })).toHaveCount(0);
  await page.getByRole('navigation', { name: 'Navigasi portal publik' }).getByRole('button', { name: 'Lacak Pesanan & Pembayaran', exact: true }).click();
  await expect(page.getByRole('heading', { name: /Pembayaran|Tagihan|Rekening/i }).first()).toBeVisible();
  await page.getByRole('navigation', { name: 'Navigasi portal publik' }).getByRole('button', { name: 'Ajukan Kebutuhan', exact: true }).click();
  await expect(page.getByText('1. Data Instansi & Pemohon', { exact: true })).toBeVisible();
});

test('navigation and notification panels remain usable', async ({ page }, testInfo) => {
  await isolate(page, 'MARKETING');
  await page.goto('/marketing/dashboard');
  if (testInfo.project.name === 'mobile') {
    await page.getByRole('button', { name: 'Buka navigasi', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Buka navigasi', exact: true })).toHaveAttribute('aria-expanded', 'true');
    await expect.poll(async () => (await page.locator('#app-sidebar').boundingBox())?.x).toBe(0);
    await page.getByRole('button', { name: 'Tutup navigasi' }).click();
  } else {
    await page.getByRole('button', { name: 'Ciutkan sidebar', exact: true }).click();
    await expect(page.locator('#app-sidebar')).toHaveCSS('width', '80px');
    await page.getByRole('button', { name: 'Buka sidebar', exact: true }).click();
  }
  await page.getByRole('button', { name: 'Buka pusat notifikasi' }).click();
  await expect(page.locator('#notification-panel')).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(page.locator('#notification-panel')).toHaveCount(0);
});

test('existing role guard rejects another internal role', async ({ page }) => {
  await isolate(page, 'MARKETING');
  await page.goto('/finance/dashboard');
  await expect(page).toHaveURL(/\/marketing\/dashboard$/);
  await expect(page.locator('#app-sidebar a[href="/finance/purchases"]')).toHaveCount(0);
});

test('protected pages continue to require authentication', async ({ page }) => {
  await isolate(page);
  await page.goto('/warehouse/products');
  await expect(page).toHaveURL(/\/login\?redirect=/);
  await expect(page.getByRole('button', { name: 'Masuk ke sistem', exact: true })).toBeVisible();
});

test('warehouse Excel export stays reachable at responsive widths', async ({ page }, testInfo) => {
  await isolate(page, 'WAREHOUSE');
  // This case verifies layout/export, independently of the offline seed download.
  await page.route('**/api/products?*', route => route.fulfill({ json: { success: true, data: [
    { id: 1, sku: 'EXPORT-QA', name: 'Barang Ekspor', category: 'QA', unit: 'PCS', stock: 12, min_stock: 10, sell_price: 100, hpp: 80, last_buy_price: 80, status: 'Tersedia' },
  ] } }));
  await page.goto('/warehouse/products');
  const firstSku = page.locator('#page-content tbody tr td').first();
  await expect(firstSku).toBeVisible();
  const sku = (await firstSku.innerText()).trim();
  await page.getByPlaceholder('Cari ID / nama / satuan / kategori').fill(sku);
  await page.getByRole('button', { name: 'Cari', exact: true }).click();
  await expect(page.locator('#page-content tbody tr')).toHaveCount(1);

  const widths = testInfo.project.name === 'desktop' ? [1920, 1440, 1280, 1024, 768] : [390];
  for (const width of widths) {
    await page.setViewportSize({ width, height: 900 });
    const excel = page.getByRole('button', { name: 'Excel', exact: true });
    const bounds = await excel.evaluate(button => {
      const rect = button.getBoundingClientRect();
      const card = button.closest('.ui-panel').getBoundingClientRect();
      return { left: rect.left, right: rect.right, cardLeft: card.left, cardRight: card.right, viewport: innerWidth };
    });
    expect(bounds.left, `${width}px: Excel left edge`).toBeGreaterThanOrEqual(bounds.cardLeft);
    expect(bounds.right, `${width}px: Excel right edge`).toBeLessThanOrEqual(Math.min(bounds.cardRight, bounds.viewport));
    const downloadEvent = page.waitForEvent('download');
    await excel.click();
    const download = await downloadEvent;
    expect(download.suggestedFilename()).toMatch(/^Data_Barang_Export_\d{8}\.xlsx$/);
    const workbook = XLSX.read(fs.readFileSync(await download.path()), { type: 'buffer' });
    const rows = XLSX.utils.sheet_to_json(workbook.Sheets['Data Barang']);
    expect(rows).toHaveLength(1);
    expect(rows[0]['ID Barang']).toBe(sku);
    await page.screenshot({ path: testInfo.outputPath(`excel-${width}.png`), animations: 'disabled' });
  }
});

test('director monitoring and report categories keep their existing content', async ({ page }, testInfo) => {
  await isolate(page, 'DIREKTUR');
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto('/director/monitoring');
  for (const name of ['Logistik & Gudang', 'Dokumen & SPJ', 'Keuangan & Piutang', 'Pesanan & Kontrak']) {
    await page.getByRole('button', { name: new RegExp(name) }).click();
    await expect(page.locator('#page-content table').first()).toBeVisible();
  }
  await page.goto('/director/reports');
  for (const name of ['Client', 'Barang', 'Fulfillment', 'Invoice', 'Pembayaran', 'Piutang', 'Log Aktivitas', 'Order']) {
    await page.getByRole('button', { name, exact: true }).click();
    await expect(page.locator('#page-content table').first()).toBeVisible();
  }
  expect(errors).toEqual([]);
  await page.screenshot({ path: testInfo.outputPath('reports.png'), fullPage: true });
});

test('warehouse procurement tabs stay accessible', async ({ page }) => {
  await isolate(page, 'WAREHOUSE');
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto('/warehouse/fulfillment');
  for (const name of [/Penerimaan Fisik & Resi Supplier/, /Pemenuhan Order Proyek/, /Pengajuan Kebutuhan \(Material Request\)/]) {
    await page.getByRole('button', { name }).click();
    await expect(page.locator('#page-content')).toBeVisible();
    expect(await page.locator('#page-content').innerText()).toMatch(/(Daftar|Order|Pengajuan|Penerimaan)/i);
  }
  expect(errors).toEqual([]);
});

test('supplier service tabs stay accessible', async ({ page }) => {
  await isolate(page, 'SUPPLIER');
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto('/supplier/portal');
  for (const [name, content] of [
    [/Surat Jalan & Pengiriman/, /Arsip Pengiriman & Surat Jalan Supplier/],
    [/Tindak Lanjut Barang \/ Retur/, /Tindak Lanjut Ketidaksesuaian Barang/],
    [/Profil Rekanan & Rekening/, /Informasi Profil & Rekening Pembayaran Supplier/],
    [/Pesanan Pembelian Masuk/, /Pesanan Masuk/i],
  ]) {
    await page.getByRole('button', { name }).click();
    await expect(page.locator('main > section')).toContainText(content);
  }
  expect(errors).toEqual([]);
});

test('client search shows a readable empty state and restores rows', async ({ page }, testInfo) => {
  await isolate(page, 'MARKETING');
  await page.route('**/api/customers', route => route.fulfill({ json: { data: Array.from({ length: 10 }, (_, index) => ({ ...customerFixture, id: index + 1, code: 'CS' + String(index + 1).padStart(5, '0'), name: index ? 'Customer QA ' + index : 'SMA 9' })) } }));
  await page.goto('/marketing/clients');
  const search = page.getByPlaceholder('Cari ID, nama customer, alamat, kontak, email...');
  await search.fill('NO-MATCH-UI-REVIEW');
  await expect(page.locator('.ui-empty-state').first()).toBeVisible();
  await page.screenshot({ path: testInfo.outputPath('empty-state.png'), fullPage: true });
  await search.fill('SMA 9');
  await expect(page.locator('tbody')).toContainText('SMA 9');
  await search.clear();
  await expect(page.locator('tbody tr')).toHaveCount(10);
});

test('payment modal traps focus and closes without changing transactions', async ({ page }, testInfo) => {
  await isolate(page, 'FINANCE');
  await page.goto('/finance/payments');
  await expect(page.locator('tbody tr').first()).toBeVisible();
  const rowsBefore = await page.locator('tbody tr').count();
  const trigger = page.getByRole('button', { name: 'Informasi Pembayaran', exact: true });
  await trigger.click();
  const dialog = page.getByRole('dialog', { name: 'Input Pembayaran Masuk', exact: true });
  await expect(dialog).toBeVisible();
  await expect.poll(() => page.evaluate(() => !!document.activeElement?.closest('[role="dialog"]'))).toBe(true);
  await page.keyboard.press('Shift+Tab');
  await expect.poll(() => page.evaluate(() => !!document.activeElement?.closest('[role="dialog"]'))).toBe(true);
  await page.screenshot({ path: testInfo.outputPath('payment-modal.png'), fullPage: true });
  await page.keyboard.press('Escape');
  await expect(dialog).toHaveCount(0);
  await expect(trigger).toBeFocused();
  expect(await page.locator('tbody tr').count()).toBe(rowsBefore);
  expect(await page.evaluate(() => document.body.style.overflow)).not.toBe('hidden');
});

test('order detail drawer and quick customer form retain their fields', async ({ page }, testInfo) => {
  await isolate(page, 'MARKETING');
  await page.goto('/marketing/dashboard');
  await page.evaluate(async () => {
    const { store } = await import(document.querySelector('script[type=module][src*="/src/main.js"]').src.replace('/main.js', '/stores/mockData.js'));
    store.orders = [{ id: 'qa-drawer', orderNumber: 'ORD-QA-DRAWER', clientName: 'Customer QA', projectName: 'Proyek QA', date: '2026-11-19', grandTotal: 100000, status: 'Draft', items: [], timeline: [], documents: {} }];
  });
  await page.getByRole('button', { name: 'Detail', exact: true }).first().click();
  const drawer = page.getByRole('dialog');
  await expect(drawer).toContainText('Timeline Proses');
  await page.screenshot({ path: testInfo.outputPath('order-drawer.png'), fullPage: true });
  await page.keyboard.press('Escape');
  await expect(drawer).toHaveCount(0);
  await page.goto('/marketing/orders/create');
  const projectId = page.locator('input[readonly]').first();
  await expect(projectId).toBeDisabled();
  await page.getByRole('button', { name: 'Tambah Customer', exact: true }).click();
  const customer = page.getByRole('dialog', { name: 'Tambah Customer', exact: true });
  await expect(customer).toBeVisible();
  await expect(customer.locator('input[required]').first()).toBeVisible();
  await page.screenshot({ path: testInfo.outputPath('quick-customer.png'), fullPage: true });
  await page.keyboard.press('Escape');
  await expect(customer).toHaveCount(0);
});
