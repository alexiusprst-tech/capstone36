import { test, expect } from '@playwright/test';
import { signIn } from './helpers/session';

async function api(page, method, url, data) {
  return page.evaluate(async ({ method, url, data }) => {
    const csrf = document.cookie.split('; ').find(cookie => cookie.startsWith('XSRF-TOKEN='))?.slice('XSRF-TOKEN='.length);
    const response = await fetch('/api' + url, { method: method.toUpperCase(), credentials: 'same-origin',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...(csrf ? { 'X-XSRF-TOKEN': decodeURIComponent(csrf) } : {}) },
      ...(data === undefined ? {} : { body: JSON.stringify(data) }) });
    const body = await response.json();
    return { status: response.status, data: body.data, body };
  }, { method, url, data });
}

test('Warehouse creates and cancels a real order shortage, Finance buys only its reissued request with exact cents', async ({ browser }, testInfo) => {
  test.setTimeout(90000);
  const suffix = testInfo.project.name.toUpperCase(), sku = `UI-MR-${suffix}`, orderNumber = `ORD-UI-MR-${suffix}`;
  const contexts = [], errors = [];
  async function rolePage(role, path) {
    const context = await browser.newContext({ viewport: testInfo.project.use.viewport, isMobile: testInfo.project.use.isMobile, hasTouch: testInfo.project.use.hasTouch });
    contexts.push(context);
    const page = await context.newPage(); page.on('pageerror', error => errors.push(error.message));
    await signIn(page, role); await page.goto(path); return page;
  }
  try {
    const warehouse = await rolePage('WAREHOUSE', '/warehouse/fulfillment');
    const product = await api(warehouse, 'post', '/products', { sku, name: `Barang Pengadaan UI ${suffix}`, category: 'Pengujian', unit: 'PCS', stock: 2, sell_price: 150 });
    expect(product.status).toBe(201);
    const marketing = await rolePage('MARKETING', '/marketing/orders');
    const customer = await api(marketing, 'post', '/customers', { name: `Customer Pengadaan UI ${suffix}`, contactPerson: 'PIC QA', phone: '081234567890', address: 'Jalan Pengadaan 1' });
    const order = await api(marketing, 'put', `/orders/${orderNumber}`, { version: 0, details: { clientId: customer.data.id, clientName: customer.data.name, projectName: `Proyek Pengadaan UI ${suffix}` }, items: [{ sku, name: product.data.name, unit: 'PCS', qty: 5, price: 150 }] });
    expect(order.data.stockReadiness.items[0]).toMatchObject({ shortage: 3, unrequestedShortage: 3 });
    const revised = await api(marketing, 'put', `/orders/${orderNumber}`, { version: order.data.version, details: { clientId: customer.data.id, clientName: customer.data.name, projectName: `Proyek Pengadaan UI ${suffix} Revisi`, notes: 'Catatan revisi untuk jejak audit' }, items: [{ sku, name: product.data.name, unit: 'PCS', qty: 5, price: 150.25 }] });
    expect(revised.data.version).toBe(order.data.version + 1);
    await warehouse.reload();

    async function createRequest() {
      await warehouse.getByRole('button', { name: 'Buat Pengajuan Kebutuhan', exact: true }).click();
      await warehouse.getByLabel(/^Pesanan Sumber/).selectOption(orderNumber);
      await expect(warehouse.getByLabel(`Jumlah Pengadaan ${sku}`, { exact: true })).toHaveValue('3');
      await warehouse.getByLabel(/^Keperluan Pengadaan/).fill('Memenuhi tiga unit kekurangan stok pesanan.');
      await warehouse.getByLabel(`Estimasi Harga ${sku}`, { exact: true }).fill('100.10');
      const response = warehouse.waitForResponse(response => response.url().endsWith('/api/warehouse/requests') && response.request().method() === 'POST');
      await warehouse.getByRole('button', { name: 'Kirim Pengajuan', exact: true }).click();
      const saved = await response; expect(saved.status()).toBe(201);
      const record = (await saved.json()).data;
      await expect(warehouse.getByRole('heading', { name: record.request_code, exact: true })).toBeVisible();
      return record;
    }
    const first = await createRequest();
    await expect(warehouse.getByText(`Pesanan ${orderNumber}`, { exact: false }).first()).toBeVisible();
    const cancellation = warehouse.waitForResponse(response => response.url().endsWith(`/api/warehouse/requests/${first.request_code}/cancel`) && response.request().method() === 'POST');
    await warehouse.getByRole('button', { name: `Batalkan Pengajuan ${first.request_code}`, exact: true }).click();
    expect((await cancellation).status()).toBe(200);
    await expect(warehouse.getByRole('button', { name: `Batalkan Pengajuan ${first.request_code}`, exact: true })).toHaveCount(0);
    const request = await createRequest(); expect(request.request_code).not.toBe(first.request_code);
    await warehouse.getByRole('button', { name: 'Buat Pengajuan Kebutuhan', exact: true }).click();
    await expect(warehouse.getByLabel(/^Pesanan Sumber/).locator(`option[value="${orderNumber}"]`)).toHaveCount(0);

    const finance = await rolePage('FINANCE', '/finance/purchases');
    const supplier = await api(finance, 'post', '/suppliers', { name: `Supplier Pengadaan UI ${suffix}`, phone: '02112345678' });
    await finance.reload();
    await finance.getByRole('button', { name: `Pilih Supplier & Beli ${request.request_code}`, exact: true }).click();
    await expect(finance.getByRole('combobox', { name: 'Pengajuan Gudang', exact: true })).toHaveValue(request.request_code);
    await expect(finance.getByRole('combobox', { name: 'Pengajuan Gudang', exact: true }).locator(`option[value="${first.request_code}"]`)).toHaveCount(0);
    await finance.getByRole('combobox', { name: 'Supplier', exact: true }).selectOption(String(supplier.data.id));
    await finance.getByLabel(`Harga Beli ${sku}`, { exact: true }).fill('100.10');
    await finance.getByLabel(`Diskon ${sku}`, { exact: true }).fill('0.05');
    await finance.getByLabel('Diskon Transaksi (Rp)', { exact: true }).fill('0.10');
    await expect(finance.locator('dl')).toContainText('300,05');
    expect(await finance.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true);
    await finance.screenshot({ path: testInfo.outputPath('purchase-form-exact-cents.png'), fullPage: true });
    const purchaseResponse = finance.waitForResponse(response => response.url().endsWith('/api/purchases') && response.request().method() === 'POST');
    await finance.getByRole('button', { name: 'Simpan Pembelian', exact: true }).click();
    const purchaseResult = await purchaseResponse; expect(purchaseResult.status()).toBe(201);
    const purchased = (await purchaseResult.json()).data;
    expect(Number(purchased.total_price)).toBe(300.05);
    expect(purchased.items).toHaveLength(1); expect(purchased.items[0]).toMatchObject({ sku, qty: 3 });
    await expect(finance.getByRole('heading', { name: purchased.purchase_code, exact: true })).toBeVisible();
    await expect(finance.locator('#page-content')).toContainText(orderNumber);
    expect(Number((await api(warehouse, 'get', `/products/${product.data.id}`)).data.stock)).toBe(2);
    await finance.reload();
    expect((await api(finance, 'get', '/purchases')).data.filter(record => record.warehouse_request_code === request.request_code)).toHaveLength(1);
    for (const page of [warehouse, finance]) expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true);
    await finance.screenshot({ path: testInfo.outputPath('purchase-created-from-material-request.png'), fullPage: true });

    await marketing.reload();
    await marketing.getByRole('row').filter({ hasText: orderNumber }).getByTitle('Lihat Rincian Barang & Monitoring Order', { exact: true }).click();
    const detail = marketing.getByRole('dialog');
    await expect(detail.getByRole('region', { name: 'Pengadaan Terkait Pesanan', exact: true })).toContainText(purchased.purchase_code);
    await expect(detail).toContainText('Dalam pengadaan 3, belum diajukan 0 PCS.');
    const audit = detail.getByRole('region', { name: 'Riwayat Perubahan Pesanan', exact: true });
    await audit.getByText(`Draft Disimpan · Versi ${revised.data.version}`, { exact: true }).click();
    const priceHistory = audit.getByRole('row').filter({ hasText: `Barang ${sku} · Harga Satuan` }).first();
    await expect(priceHistory.getByRole('cell').nth(0)).toHaveText(/^Rp\s*150$/);
    await expect(priceHistory.getByRole('cell').nth(1)).toHaveText(/^Rp\s*150,25$/);
    await expect(audit).toContainText('Catatan revisi untuk jejak audit'); await expect(audit).toContainText('MARKETING');
    await marketing.screenshot({ path: testInfo.outputPath('persisted-order-procurement-and-audit-trail.png'), fullPage: true });

    // The backend commits, then its response is lost. Retrying the actual form must reuse the persisted key.
    await warehouse.reload();
    await warehouse.getByRole('button', { name: 'Penerimaan Fisik & Resi Supplier', exact: true }).click();
    await warehouse.getByRole('button', { name: `Catat Penerimaan ${purchased.purchase_code}`, exact: true }).click();
    await warehouse.getByLabel(`Diterima ${sku}`, { exact: true }).fill('3');
    await warehouse.route(`**/api/purchases/${purchased.purchase_code}/receive`, async route => {
      expect((await route.fetch()).status()).toBe(200);
      await route.abort('failed');
    }, { times: 1 });
    await warehouse.getByRole('button', { name: 'Simpan Penerimaan', exact: true }).click();
    await expect(warehouse.getByRole('alert')).toBeVisible();
    await expect(warehouse.getByLabel(`Diterima ${sku}`, { exact: true })).toBeDisabled();
    const retry = warehouse.waitForResponse(response => response.url().endsWith(`/api/purchases/${purchased.purchase_code}/receive`) && response.request().method() === 'POST');
    await warehouse.getByRole('button', { name: 'Simpan Penerimaan', exact: true }).click();
    expect((await retry).status()).toBe(200);
    await expect(warehouse.getByRole('dialog')).toHaveCount(0);
    const received = (await api(warehouse, 'get', '/purchases')).data.find(record => record.purchase_code === purchased.purchase_code);
    expect(received.receipts).toHaveLength(1);
    expect(Number((await api(warehouse, 'get', `/products/${product.data.id}`)).data.stock)).toBe(5);
    expect(errors).toEqual([]);
  } finally { for (const context of contexts) await context.close(); }
});
