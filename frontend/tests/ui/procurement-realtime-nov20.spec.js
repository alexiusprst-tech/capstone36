import { test, expect } from '@playwright/test';
import { signIn } from './helpers/session';

async function api(page, method, url, data) {
  return page.evaluate(async ({ method, url, data }) => {
    const csrf = document.cookie.split('; ').find(value => value.startsWith('XSRF-TOKEN='))?.slice(11);
    const response = await fetch('/api' + url, { method: method.toUpperCase(), credentials: 'same-origin',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...(csrf ? { 'X-XSRF-TOKEN': decodeURIComponent(csrf) } : {}) },
      ...(data === undefined ? {} : { body: JSON.stringify(data) }) });
    const body = await response.json();
    return { status: response.status, data: body.data, body };
  }, { method, url, data });
}

async function actors(browser, testInfo) {
  const contexts = [], errors = [];
  async function open(role, url) {
    const context = await browser.newContext({ viewport: testInfo.project.use.viewport,
      isMobile: testInfo.project.use.isMobile, hasTouch: testInfo.project.use.hasTouch });
    contexts.push(context);
    const page = await context.newPage();
    page.on('pageerror', error => errors.push(`${role}: ${error.message}`));
    await signIn(page, role); await page.goto(url);
    return page;
  }
  const warehouse = await open('WAREHOUSE', '/warehouse/requests');
  const marketing = await open('MARKETING', '/marketing/orders');
  const finance = await open('FINANCE', '/finance/purchases');
  return { warehouse, marketing, finance, errors, close: async () => { for (const context of contexts) await context.close(); } };
}

async function fixture(people, suffix, lines) {
  const { warehouse, marketing } = people;
  const products = [];
  for (const line of lines) {
    const product = await api(warehouse, 'post', '/products', { sku: line.sku, name: `Barang ${line.sku}`, category: 'Pengujian', unit: 'PCS', stock: line.stock || 0, sell_price: 1000 });
    expect(product.status).toBe(201); products.push(product.data);
  }
  const customer = await api(marketing, 'post', '/customers', { name: `Customer Nov20 ${suffix}`, contactPerson: 'PIC QA', phone: '081234567890', address: 'Jalan Pengujian 20' });
  expect(customer.status).toBe(201);
  const number = `ORD-N20-${suffix}`;
  const details = { clientId: customer.data.id, clientName: customer.data.name, projectName: `Proyek November ${suffix}` };
  const items = lines.map(line => ({ sku: line.sku, name: `Barang ${line.sku}`, unit: 'PCS', qty: line.qty, price: 1000 }));
  const order = await api(marketing, 'put', `/orders/${number}`, { version: 0, details, items });
  expect(order.status).toBe(200);
  return { number, order: order.data, details, items, products };
}

const readResponse = (page, endpoint) => page.waitForResponse(response => new URL(response.url()).pathname === `/api${endpoint}` && response.request().method() === 'GET', { timeout: 30000 });
const writeResponse = (page, endpoint) => page.waitForResponse(response => new URL(response.url()).pathname === `/api${endpoint}` && response.request().method() === 'POST');
async function noPageOverflow(page) {
  const viewportWidth = page.viewportSize().width;
  expect(await page.evaluate(width => document.documentElement.scrollWidth <= width + 1, viewportWidth)).toBe(true);
  expect(await page.evaluate(() => window.innerWidth)).toBeLessThanOrEqual(viewportWidth + 1);
}

test('structured MR picker and exact cents survive automatic refresh and remain cancellable', async ({ browser }, testInfo) => {
  test.setTimeout(150000);
  const people = await actors(browser, testInfo), { warehouse, marketing, finance } = people;
  const suffix = `MR-${testInfo.project.name.toUpperCase()}`;
  const skuA = `N20-${suffix}-A`, skuB = `N20-${suffix}-B`;
  try {
    const record = await fixture(people, suffix, [{ sku: skuA, qty: 3 }, { sku: skuB, qty: 2 }]);
    const supplier = await api(finance, 'post', '/suppliers', { name: `Supplier Form ${suffix}` });
    expect(supplier.status).toBe(201);
    await warehouse.goto('/warehouse/requests'); await warehouse.bringToFront();
    await warehouse.getByRole('button', { name: 'Buat Pengajuan Kebutuhan', exact: true }).click();
    await warehouse.getByLabel(/^Pesanan Sumber/).selectOption(record.number);
    await warehouse.getByLabel(/^Supplier Default/).selectOption(String(supplier.data.id));
    const form = warehouse.locator('form').filter({ has: warehouse.getByLabel(/^Pesanan Sumber/) });
    await expect(form.getByRole('heading', { name: 'Header Pembelian', exact: true })).toBeVisible();
    await expect(form.getByRole('heading', { name: 'Detail Pembelian', exact: true })).toBeVisible();
    await form.getByLabel(/^Keperluan Pengadaan/).fill('Pengadaan ruang belajar - nilai harus tetap selama refresh');
    await form.getByLabel('Catatan Pengajuan (Opsional)', { exact: true }).fill('Catatan draf tetap tersimpan di form');
    await form.getByLabel(`Estimasi Harga ${skuA}`, { exact: true }).fill('100.10');
    await form.getByRole('button', { name: `Hapus Barang ${skuB}`, exact: true }).click();
    await expect(form.getByLabel(`Jumlah Pengadaan ${skuB}`, { exact: true })).toHaveCount(0);
    await form.getByRole('button', { name: 'Tambah Barang', exact: true }).click();
    const picker = warehouse.getByRole('dialog', { name: 'Pilih Barang Kebutuhan', exact: true });
    await picker.getByLabel('Cari Barang Kebutuhan', { exact: true }).fill(skuB);
    await picker.getByRole('button', { name: `Tambahkan ${skuB}`, exact: true }).click();
    await expect(picker).toHaveCount(0);
    await form.getByLabel(`Estimasi Harga ${skuB}`, { exact: true }).fill('50.05');
    await expect(form).toContainText('400,40');

    // Another role updates the persisted order. The visible page refreshes itself, retaining its local draft.
    const refreshed = readResponse(warehouse, '/orders');
    const revised = await api(marketing, 'put', `/orders/${record.number}`, { version: record.order.version,
      details: { ...record.details, description: 'Metadata diperbarui Marketing dari sesi lain' }, items: record.items });
    expect(revised.status).toBe(200);
    await warehouse.bringToFront(); await refreshed;
    await expect(form.getByLabel(/^Keperluan Pengadaan/)).toHaveValue('Pengadaan ruang belajar - nilai harus tetap selama refresh');
    await expect(form.getByLabel('Catatan Pengajuan (Opsional)', { exact: true })).toHaveValue('Catatan draf tetap tersimpan di form');
    await expect(form.getByLabel(`Jumlah Pengadaan ${skuA}`, { exact: true })).toHaveValue('3');
    await expect(form.getByLabel(`Estimasi Harga ${skuA}`, { exact: true })).toHaveValue(/^100\.10?$/);
    await expect(form.getByLabel(`Estimasi Harga ${skuB}`, { exact: true })).toHaveValue('50.05');
    await expect(form).toContainText('400,40');
    await noPageOverflow(warehouse);
    await warehouse.screenshot({ path: testInfo.outputPath('nov20-material-request-draft-after-live-refresh.png'), fullPage: true });
    const savedResponse = writeResponse(warehouse, '/warehouse/requests');
    await form.getByRole('button', { name: 'Simpan Pengajuan', exact: true }).click();
    const saved = await savedResponse; expect(saved.status()).toBe(201);
    const request = (await saved.json()).data;
    expect(Number(request.total_est_cost)).toBe(400.4);
    expect(request.items).toHaveLength(2);
    expect(request.notes).toBe('Catatan draf tetap tersimpan di form');
    const cancelled = writeResponse(warehouse, `/warehouse/requests/${request.request_code}/cancel`);
    await warehouse.getByRole('button', { name: `Batalkan Pengajuan ${request.request_code}`, exact: true }).click();
    expect((await cancelled).status()).toBe(200);
    await expect(warehouse.getByRole('button', { name: `Batalkan Pengajuan ${request.request_code}`, exact: true })).toHaveCount(0);
    await warehouse.getByRole('button', { name: 'Buat Pengajuan Kebutuhan', exact: true }).click();
    await warehouse.getByLabel(/^Pesanan Sumber/).selectOption(record.number);
    await expect(warehouse.getByLabel(`Jumlah Pengadaan ${skuA}`, { exact: true })).toHaveValue('3');
    await noPageOverflow(warehouse);
    expect(people.errors).toEqual([]);
  } finally { await people.close(); }
});

test('live cross-role tracking preserves QC drafts and rejected-only returns until replacement is accepted', async ({ browser }, testInfo) => {
  test.setTimeout(180000);
  const people = await actors(browser, testInfo), { warehouse, finance, marketing } = people;
  const suffix = `QC-${testInfo.project.name.toUpperCase()}`, sku = `N20-${suffix}`, code = `PO-N20-${suffix}`;
  try {
    const record = await fixture(people, suffix, [{ sku, qty: 10, stock: 2 }]);
    const requested = await api(warehouse, 'post', '/warehouse/requests', { order_number: record.number, purpose: 'Pengadaan pemeriksaan November', items: [{ sku, qty: 8, est_price: 100 }] });
    expect(requested.status).toBe(201);
    const supplier = `Supplier November ${suffix}`;
    expect((await api(finance, 'post', '/suppliers', { name: supplier, phone: '02112345678' })).status).toBe(201);
    const purchased = await api(finance, 'post', '/purchases', { purchase_code: code, transaction_date: '2026-11-20',
      warehouse_request_code: requested.data.request_code, default_supplier: supplier, payment_method: 'Transfer',
      items: [{ sku, unit: 'PCS', qty: 8, harga_beli: 100 }] });
    expect(purchased.status).toBe(201);
    await warehouse.goto('/warehouse/fulfillment'); await warehouse.bringToFront();
    await warehouse.getByRole('button', { name: 'Penerimaan Fisik & Resi Supplier', exact: true }).click();
    await expect(warehouse.getByRole('heading', { name: code, exact: true })).toBeVisible();
    // A document token survives history.replaceState, but disappears on a real page reload.
    const documentId = await warehouse.evaluate(() => { window.__nov20DocumentId = crypto.randomUUID(); return window.__nov20DocumentId; });
    let progress = await api(finance, 'post', `/purchases/${code}/supplier-progress`, { action: 'confirm', version: purchased.data.progress_version, progress: { supplier_notes: 'Barang tersedia' } });
    expect(progress.status).toBe(200);
    progress = await api(finance, 'post', `/purchases/${code}/supplier-progress`, { action: 'dispatch', version: progress.data.progress_version,
      progress: { delivery_info: { surat_jalan: `SJ-${suffix}`, courier: 'Kurir November', tracking_number: `RESI-${suffix}`, dispatch_date: '2026-11-20', shipping_status: 'Dalam Perjalanan' } } });
    expect(progress.status).toBe(200);
    await warehouse.bringToFront();
    await expect(warehouse.locator('#page-content')).toContainText(`RESI-${suffix}`, { timeout: 30000 });
    await warehouse.getByRole('button', { name: `Cek Resi ${code}`, exact: true }).click();
    const tracking = warehouse.getByRole('dialog', { name: `Tracking Pengiriman ${code}`, exact: true });
    await expect(tracking).toContainText(`SJ-${suffix}`);
    progress = await api(finance, 'post', `/purchases/${code}/supplier-progress`, { action: 'tracking', version: progress.data.progress_version,
      progress: { delivery_info: { shipping_status: 'Tiba di Gudang', current_location: 'Gerbang Gudang November' } } });
    expect(progress.status).toBe(200); await warehouse.bringToFront();
    await expect(tracking).toContainText('Gerbang Gudang November', { timeout: 30000 });
    await expect(tracking).toContainText(`RESI-${suffix}`);
    await expect(tracking.locator('dl > div').filter({ hasText: 'Pembaruan Terakhir' }).locator('dd')).not.toHaveText('Belum dicatat');
    await noPageOverflow(warehouse);
    await warehouse.screenshot({ path: testInfo.outputPath('nov20-live-tracking-without-reload.png'), fullPage: true });
    await tracking.getByRole('button', { name: 'Tutup dialog', exact: true }).click();

    await warehouse.getByRole('button', { name: `Catat Penerimaan ${code}`, exact: true }).click();
    const receiptDialog = warehouse.getByRole('dialog', { name: `Catat Penerimaan ${code}`, exact: true });
    await receiptDialog.getByLabel(`Diterima ${sku}`, { exact: true }).fill('3');
    await receiptDialog.getByLabel(`Ditolak ${sku}`, { exact: true }).fill('2');
    await receiptDialog.getByLabel(`Alasan penolakan ${sku}`, { exact: true }).fill('Dua unit pecah saat pemeriksaan awal');
    progress = await api(finance, 'post', `/purchases/${code}/supplier-progress`, { action: 'tracking', version: progress.data.progress_version,
      progress: { delivery_info: { current_location: 'Area bongkar November' } } });
    expect(progress.status).toBe(200); await warehouse.bringToFront();
    await expect(receiptDialog).toContainText('PO berubah', { timeout: 30000 });
    await expect(receiptDialog.getByLabel(`Diterima ${sku}`, { exact: true })).toHaveValue('3');
    await expect(receiptDialog.getByLabel(`Ditolak ${sku}`, { exact: true })).toHaveValue('2');
    await expect(receiptDialog.getByLabel(`Alasan penolakan ${sku}`, { exact: true })).toHaveValue('Dua unit pecah saat pemeriksaan awal');
    await expect(receiptDialog.getByRole('button', { name: 'Simpan Penerimaan', exact: true })).toBeDisabled();
    await receiptDialog.getByRole('button', { name: 'Tutup dialog', exact: true }).click();
    await warehouse.getByRole('button', { name: `Catat Penerimaan ${code}`, exact: true }).click();
    await receiptDialog.getByLabel(`Diterima ${sku}`, { exact: true }).fill('3');
    await receiptDialog.getByLabel(`Ditolak ${sku}`, { exact: true }).fill('2');
    await receiptDialog.getByLabel(`Alasan penolakan ${sku}`, { exact: true }).fill('Dua unit pecah saat pemeriksaan awal');
    const firstResponse = writeResponse(warehouse, `/purchases/${code}/receive`);
    await receiptDialog.getByRole('button', { name: 'Simpan Penerimaan', exact: true }).click();
    const first = await firstResponse; expect(first.status()).toBe(200);
    const partial = (await first.json()).data.purchase;
    expect(partial.inspection).toMatchObject({ accepted_qty: 3, rejected_qty: 2, remaining_qty: 5, return_status: 'PERLU_TINDAK_LANJUT' });
    expect(partial.return_items).toHaveLength(1); expect(partial.return_items[0].rejected_qty).toBe(2);
    expect(Number((await api(warehouse, 'get', `/products/${record.products[0].id}`)).data.stock)).toBe(5);
    const card = warehouse.locator('article').filter({ has: warehouse.getByRole('heading', { name: code, exact: true }) });
    await card.getByRole('button', { name: `Ditolak / Retur ${code} (2)`, exact: true }).click();
    await expect(card).toContainText('tidak pernah menambah stok');
    await expect(card).toContainText('Dua unit pecah saat pemeriksaan awal');
    await card.getByText('Riwayat Penerimaan (1)', { exact: true }).click();
    await expect(card).toContainText('UI WAREHOUSE');
    await warehouse.screenshot({ path: testInfo.outputPath('nov20-rejected-qc-only-return-and-receiver.png'), fullPage: true });

    await finance.bringToFront();
    await finance.getByRole('button', { name: 'Muat Ulang PO', exact: true }).click();
    await finance.getByRole('button', { name: `Tindak Lanjut ${code}`, exact: true }).click();
    await finance.getByLabel('Referensi Tindak Lanjut', { exact: true }).fill(`RETUR-${suffix}`);
    await finance.getByLabel('Catatan Tindak Lanjut', { exact: true }).fill('Penggantian dua unit ditolak dan tiga unit yang belum dikirim.');
    const followed = writeResponse(finance, `/purchases/${code}/supplier-progress`);
    await finance.getByRole('button', { name: 'Simpan Tindak Lanjut', exact: true }).click();
    expect((await followed).status()).toBe(200);
    await warehouse.bringToFront();
    await expect(warehouse.getByRole('button', { name: `Catat Penerimaan ${code}`, exact: true })).toBeEnabled({ timeout: 30000 });
    await warehouse.getByRole('button', { name: `Catat Penerimaan ${code}`, exact: true }).click();
    await receiptDialog.getByLabel(`Diterima ${sku}`, { exact: true }).fill('5');
    const replaced = writeResponse(warehouse, `/purchases/${code}/receive`);
    await receiptDialog.getByRole('button', { name: 'Simpan Penerimaan', exact: true }).click();
    const complete = await replaced; expect(complete.status()).toBe(200);
    const completeData = (await complete.json()).data.purchase;
    expect(completeData.inspection.return_status).toBe('PENGGANTI_DITERIMA');
    expect(completeData.return_items).toHaveLength(1);
    expect(completeData.receipts).toHaveLength(2);
    expect(Number((await api(warehouse, 'get', `/products/${record.products[0].id}`)).data.stock)).toBe(10);
    const ready = (await api(marketing, 'get', '/orders')).data.find(order => order.orderNumber === record.number);
    expect(ready.stockReadiness.status).toBe('Siap Diajukan');
    expect(await warehouse.evaluate(() => window.__nov20DocumentId)).toBe(documentId);
    await noPageOverflow(warehouse);
    expect(people.errors).toEqual([]);
  } finally { await people.close(); }
});
