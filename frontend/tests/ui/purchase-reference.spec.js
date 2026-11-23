import { test, expect } from '@playwright/test';
import { signIn } from './helpers/session';

async function api(page, method, path, data) {
  return page.evaluate(async ({ method, path, data }) => {
    const token = document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='))?.slice(11);
    const response = await fetch('/api' + path, { method, credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...(token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {}) }, ...(data === undefined ? {} : { body: JSON.stringify(data) }) });
    return { status: response.status, body: await response.json() };
  }, { method, path, data });
}

test('Warehouse purchase reference form persists every field and Finance receives its exact values', async ({ browser }, info) => {
  test.setTimeout(120000);
  const contexts = [], errors = [];
  async function pageFor(role, url) {
    const context = await browser.newContext({ viewport: info.project.use.viewport, isMobile: info.project.use.isMobile, hasTouch: info.project.use.hasTouch });
    contexts.push(context); const page = await context.newPage();
    page.on('pageerror', error => errors.push(error.message)); await signIn(page, role); await page.goto(url); return page;
  }
  const suffix = info.project.name.toUpperCase(), sku = `REF-FORM-${suffix}`, orderNumber = `ORD-REF-FORM-${suffix}`;
  try {
    const warehouse = await pageFor('WAREHOUSE', '/warehouse/requests');
    const finance = await pageFor('FINANCE', '/finance/purchases');
    const marketing = await pageFor('MARKETING', '/marketing/orders');
    const supplier = await api(finance, 'POST', '/suppliers', { name: `Supplier Referensi ${suffix}`, phone: '02112345678' }); expect(supplier.status).toBe(201);
    const product = await api(warehouse, 'POST', '/products', { sku, name: `Barang Referensi ${suffix}`, category: 'QA', unit: 'PCS', stock: 2 }); expect(product.status).toBe(201);
    const customer = await api(marketing, 'POST', '/customers', { name: `Client Referensi ${suffix}`, contactPerson: 'PIC', phone: '081234567890', address: 'Jalan Referensi' }); expect(customer.status).toBe(201);
    expect((await api(marketing, 'PUT', `/orders/${orderNumber}`, { version: 0, details: { clientId: customer.body.data.id, clientName: customer.body.data.name, projectName: `Project Referensi ${suffix}` }, items: [{ sku, name: `Barang Referensi ${suffix}`, unit: 'PCS', qty: 5, price: 150 }] })).status).toBe(200);
    await warehouse.goto('/warehouse/requests'); await warehouse.bringToFront();
    await warehouse.getByRole('button', { name: 'Buat Pengajuan Kebutuhan', exact: true }).click();
    const form = warehouse.getByRole('form', { name: 'Form Pengajuan Kebutuhan' });
    for (const heading of ['Header Pembelian', 'Detail Pembelian', 'Total dan Pembayaran']) await expect(form.getByRole('heading', { name: heading, exact: true })).toBeVisible();
    await expect(form.getByLabel('Kode Pembelian', { exact: true })).toHaveAttribute('readonly', '');
    await form.getByLabel('Pesanan Sumber', { exact: true }).selectOption(orderNumber);
    await form.getByRole('button', { name: 'Cari Supplier Default', exact: true }).click();
    const picker = warehouse.getByRole('dialog', { name: 'Pilih Supplier', exact: true });
    await picker.getByLabel('Cari Supplier', { exact: true }).fill(`Referensi ${suffix}`);
    await picker.getByRole('button', { name: `Gunakan Supplier ${supplier.body.data.code}`, exact: true }).click();
    await expect(form.getByLabel(`Supplier ${sku}`, { exact: true })).toHaveValue(`Supplier Referensi ${suffix}`);
    await form.getByLabel('No Faktur', { exact: true }).fill(`FAKTUR-${suffix}`);
    await form.getByLabel(/^Tanggal/).fill('2026-11-20');
    await form.getByLabel(/^Keperluan Pengadaan/).fill('Pembelian sesuai referensi');
    await form.getByLabel('Catatan Pengajuan (Opsional)', { exact: true }).fill('Semua nilai diteruskan ke Finance');
    await form.getByLabel(`Estimasi Harga ${sku}`, { exact: true }).fill('100.10');
    await form.getByLabel(`Persen Diskon ${sku}`, { exact: true }).fill('10');
    await expect(form.getByLabel(`Diskon Satuan ${sku}`, { exact: true })).toHaveValue('10.01');
    await form.getByLabel(`Diskon Satuan ${sku}`, { exact: true }).fill('0.05');
    await expect(form.getByLabel(`Persen Diskon ${sku}`, { exact: true })).not.toHaveValue('10');
    await form.getByLabel('Diskon Transaksi Pengajuan (Rp)', { exact: true }).fill('0.10');
    await form.getByLabel('Jenis Pembayaran', { exact: true }).selectOption('Termin');
    await expect(form.getByTestId('mr-total-estimate')).toContainText('300,05');
    const refresh = warehouse.waitForResponse(r => new URL(r.url()).pathname === '/api/warehouse/requests' && r.request().method() === 'GET');
    await refresh;
    await expect(form.getByLabel('No Faktur', { exact: true })).toHaveValue(`FAKTUR-${suffix}`);
    await expect(form.getByLabel('Diskon Transaksi Pengajuan (Rp)', { exact: true })).toHaveValue(/^0\.10?$/);
    expect(await warehouse.evaluate(() => innerWidth)).toBeLessThanOrEqual(info.project.use.viewport.width + 1);
    expect(await warehouse.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(info.project.use.viewport.width + 1);
    await warehouse.screenshot({ path: info.outputPath('purchase-reference-form.png'), fullPage: true });
    const savedResponse = warehouse.waitForResponse(r => r.url().endsWith('/api/warehouse/requests') && r.request().method() === 'POST');
    await form.getByRole('button', { name: 'Simpan Pengajuan', exact: true }).click();
    const saved = await savedResponse; expect(saved.status()).toBe(201); const request = (await saved.json()).data;
    expect(request.procurement_draft).toMatchObject({ invoice_number: `FAKTUR-${suffix}`, supplier_id: supplier.body.data.id, default_supplier: `Supplier Referensi ${suffix}`, discount: 0.1, total_price: 300.05, payment_method: 'Termin' });
    expect(request.procurement_draft.purchase_code).toMatch(/^PB20261120/);
    expect(request.purchase_code).toBeNull();
    expect(Number((await api(warehouse, 'GET', `/products/${product.body.data.id}`)).body.data.stock)).toBe(2);
    await warehouse.reload();
    await warehouse.getByRole('button', { name: `Detail Pengajuan ${request.request_code}`, exact: true }).click();
    const detail = warehouse.getByRole('dialog');
    await expect(detail.getByRole('region', { name: 'Rencana Pembelian', exact: true })).toContainText(`FAKTUR-${suffix}`);
    await expect(detail).toContainText(request.procurement_draft.purchase_code);
    await finance.reload();
    await finance.getByRole('button', { name: `Pilih Supplier & Beli ${request.request_code}`, exact: true }).click();
    await expect(finance.getByLabel('Kode Pembelian', { exact: true })).toHaveValue(request.procurement_draft.purchase_code);
    await expect(finance.getByRole('combobox', { name: 'Supplier', exact: true })).toHaveValue(String(supplier.body.data.id));
    await expect(finance.getByLabel('Nomor Faktur Supplier (Opsional)', { exact: true })).toHaveValue(`FAKTUR-${suffix}`);
    await expect(finance.getByLabel(`Harga Beli ${sku}`, { exact: true })).toHaveValue(/^100\.10?$/);
    await expect(finance.getByLabel(`Diskon ${sku}`, { exact: true })).toHaveValue('0.05');
    await expect(finance.getByLabel('Diskon Transaksi (Rp)', { exact: true })).toHaveValue(/^0\.10?$/);
    await expect(finance.getByRole('combobox', { name: 'Metode Pembayaran Supplier', exact: true })).toHaveValue('Termin');
    const purchaseResponse = finance.waitForResponse(r => r.url().endsWith('/api/purchases') && r.request().method() === 'POST');
    await finance.getByRole('button', { name: 'Simpan Pembelian', exact: true }).click();
    const purchase = await purchaseResponse; expect(purchase.status()).toBe(201);
    expect((await purchase.json()).data).toMatchObject({ purchase_code: request.procurement_draft.purchase_code, invoice_number: `FAKTUR-${suffix}`, total_price: '300.05' });
    expect(errors).toEqual([]);
  } finally { for (const context of contexts) await context.close(); }
});
