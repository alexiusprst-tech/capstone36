import { test, expect } from '@playwright/test';
import { signIn } from './helpers/session';

// All writes use the real cookie-authenticated API. Receipt/followup use the actual role UI.
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

test('live Sprint 1 shortage, rejected delivery, Finance followup and replacement reach persisted ready stock', async ({ browser }, testInfo) => {
  test.setTimeout(120000);
  const suffix = testInfo.project.name.toUpperCase();
  const sku = `LIVE-SP1-${suffix}`;
  const orderNumber = `ORD-LIVE-SP1-${suffix}`;
  const purchaseCode = `PO-LIVE-SP1-${suffix}`;
  const contexts = [];
  const errors = [];
  async function rolePage(role, path) {
    const context = await browser.newContext({ viewport: testInfo.project.use.viewport, isMobile: testInfo.project.use.isMobile, hasTouch: testInfo.project.use.hasTouch });
    contexts.push(context);
    const page = await context.newPage();
    page.on('pageerror', error => errors.push(`${role}: ${error.message}`));
    await signIn(page, role);
    await page.goto(path);
    return page;
  }
  try {
    const warehouse = await rolePage('WAREHOUSE', '/warehouse/fulfillment');
    const product = await api(warehouse, 'post', '/products', { sku, name: `Barang Integrasi ${suffix}`, category: 'Pengujian', unit: 'PCS', stock: 2, sell_price: 12500 });
    expect(product.status).toBe(201);
    const marketing = await rolePage('MARKETING', '/marketing/orders');
    const customer = await api(marketing, 'post', '/customers', { name: `Customer Live ${suffix}`, contactPerson: 'PIC QA', phone: '081234567890', address: 'Jalan Pengujian 19' });
    expect(customer.status).toBe(201);
    const order = await api(marketing, 'put', `/orders/${orderNumber}`, { version: 0,
      details: { clientId: customer.data.id, clientName: customer.data.name, projectName: `Proyek Live ${suffix}` },
      items: [{ sku, name: product.data.name, unit: 'PCS', qty: 10, price: 12500 }] });
    expect(order.status).toBe(200);
    expect(order.data.stockReadiness).toMatchObject({ status: 'Menunggu Stok', items: [{ allocated: 2, shortage: 8 }] });
    const requested = await api(warehouse, 'post', '/warehouse/requests', { order_number: orderNumber, purpose: 'Memenuhi delapan unit kekurangan', items: [{ sku, qty: 8, est_price: 10000 }] });
    expect(requested.status).toBe(201);
    const duplicate = await api(warehouse, 'post', '/warehouse/requests', { order_number: orderNumber, purpose: 'Duplikat kebutuhan', items: [{ sku, qty: 8, est_price: 10000 }] });
    expect(duplicate.status).toBe(422);

    const finance = await rolePage('FINANCE', '/finance/purchases');
    const supplier = `Supplier Live ${suffix}`;
    expect((await api(finance, 'post', '/suppliers', { name: supplier, phone: '02112345678' })).status).toBe(201);
    const purchased = await api(finance, 'post', '/purchases', { purchase_code: purchaseCode, transaction_date: '2026-11-19', project_name: `Proyek Live ${suffix}`,
      warehouse_request_code: requested.data.request_code, default_supplier: supplier, subtotal: 1, discount: 0, total_price: 1, payment_method: 'Transfer',
      items: [{ sku, barang: `${sku} - ${product.data.name}`, unit: 'PCS', supplier, qty: 8, harga_beli: 10000, diskon_percent: 0, diskon_nominal: 0, subtotal: 1 }] });
    expect(purchased.status).toBe(201);
    expect(Number(purchased.data.total_price)).toBe(80000);

    await warehouse.reload();
    await warehouse.getByRole('button', { name: 'Penerimaan Fisik & Resi Supplier', exact: true }).click();
    await warehouse.getByRole('button', { name: `Catat Penerimaan ${purchaseCode}`, exact: true }).click();
    await warehouse.getByLabel(`Diterima ${sku}`, { exact: true }).fill('3');
    await warehouse.getByLabel(`Ditolak ${sku}`, { exact: true }).fill('2');
    await warehouse.getByLabel(`Alasan penolakan ${sku}`, { exact: true }).fill('Dua unit rusak dalam pengiriman');
    const firstReceipt = warehouse.waitForResponse(response => response.url().endsWith(`/api/purchases/${purchaseCode}/receive`) && response.request().method() === 'POST');
    await warehouse.getByRole('button', { name: 'Simpan Penerimaan', exact: true }).click();
    expect((await firstReceipt).status()).toBe(200);
    let persisted = (await api(finance, 'get', '/purchases')).data.find(row => row.purchase_code === purchaseCode);
    expect(persisted.pending_followup).toBe(true);
    expect(persisted.receipt_summary[0]).toMatchObject({ accepted: 3, rejected: 2, remaining: 5 });
    expect(Number((await api(warehouse, 'get', `/products/${product.data.id}`)).data.stock)).toBe(5);

    await finance.reload();
    await finance.getByRole('button', { name: `Tindak Lanjut ${purchaseCode}`, exact: true }).click();
    await finance.getByLabel('Referensi Tindak Lanjut', { exact: true }).fill(`RETUR-${suffix}`);
    await finance.getByLabel('Catatan Tindak Lanjut', { exact: true }).fill('Supplier mengganti dua unit rusak dan melengkapi tiga unit tersisa.');
    const followup = finance.waitForResponse(response => response.url().endsWith(`/api/purchases/${purchaseCode}/supplier-progress`) && response.request().method() === 'POST');
    await finance.getByRole('button', { name: 'Simpan Tindak Lanjut', exact: true }).click();
    expect((await followup).status()).toBe(200);

    await warehouse.reload();
    await warehouse.getByRole('button', { name: 'Penerimaan Fisik & Resi Supplier', exact: true }).click();
    await warehouse.getByRole('button', { name: `Catat Penerimaan ${purchaseCode}`, exact: true }).click();
    await warehouse.getByLabel(`Diterima ${sku}`, { exact: true }).fill('5');
    const replacement = warehouse.waitForResponse(response => response.url().endsWith(`/api/purchases/${purchaseCode}/receive`) && response.request().method() === 'POST');
    await warehouse.getByRole('button', { name: 'Simpan Penerimaan', exact: true }).click();
    expect((await replacement).status()).toBe(200);

    await marketing.reload();
    const ready = (await api(marketing, 'get', '/orders')).data.find(row => row.orderNumber === orderNumber);
    expect(ready.stockReadiness).toMatchObject({ status: 'Siap Diajukan', items: [{ allocated: 10, shortage: 0 }] });
    expect(ready.grandTotal).toBe(138750);
    expect(ready.status).toBe('Draft');
    expect(Number((await api(warehouse, 'get', `/products/${product.data.id}`)).data.stock)).toBe(10);
    persisted = (await api(finance, 'get', '/purchases')).data.find(row => row.purchase_code === purchaseCode);
    expect(persisted.status).toBe('DITERIMA_GUDANG');
    expect(persisted.receipts).toHaveLength(2);
    expect(persisted.receipt_summary[0]).toMatchObject({ accepted: 8, rejected: 2, remaining: 0 });
    await warehouse.screenshot({ path: testInfo.outputPath('sprint-one-receipts-complete.png'), fullPage: true });
    expect(errors).toEqual([]);
  } finally {
    for (const context of contexts) await context.close();
  }
});
