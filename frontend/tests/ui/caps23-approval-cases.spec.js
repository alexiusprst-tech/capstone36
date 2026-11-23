import { test, expect } from '@playwright/test';
import { signIn } from './helpers/session';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

async function api(page, method, path, data) {
  return page.evaluate(async ({ method, path, data }) => {
    const csrf = document.cookie.split('; ').find(cookie => cookie.startsWith('XSRF-TOKEN='))?.slice(11);
    const response = await fetch('/api' + path, { method, credentials: 'same-origin',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...(csrf ? { 'X-XSRF-TOKEN': decodeURIComponent(csrf) } : {}) },
      ...(data === undefined ? {} : { body: JSON.stringify(data) }) });
    const body = await response.json();
    return { status: response.status, data: body.data, body };
  }, { method, path, data });
}

async function scenario(browser, testInfo, suffix, email) {
  const contexts = [], errors = [];
  const role = async (name, path) => {
    const context = await browser.newContext({ viewport: testInfo.project.use.viewport,
      isMobile: testInfo.project.use.isMobile, hasTouch: testInfo.project.use.hasTouch });
    contexts.push(context);
    const page = await context.newPage();
    page.on('pageerror', error => errors.push(error.message));
    await signIn(page, name);
    await page.goto(path);
    return page;
  };
  const warehouse = await role('WAREHOUSE', '/warehouse/fulfillment');
  const sku = `C23-${testInfo.project.name}-${suffix}`;
  const product = await api(warehouse, 'POST', '/products', { sku, name: 'Laptop Pengadaan QA ' + suffix,
    category: 'Pengujian CAPS23', unit: 'PCS', stock: 5, hpp: 800000, sell_price: 1000000 });
  expect(product.status).toBe(201);
  const marketing = await role('MARKETING', '/marketing/orders');
  const customer = await api(marketing, 'POST', '/customers', { name: 'SMK Kasus CAPS23 ' + suffix,
    contactPerson: 'PIC Laboratorium QA', phone: '081234567890', address: 'Jalan Pengujian CAPS23', email });
  expect(customer.status).toBe(201);
  const save = async (code, price, qty = 1) => {
    const number = `ORD-C23-${testInfo.project.name}-${suffix}-${code}`;
    const response = await api(marketing, 'PUT', `/orders/${number}`, { version: 0,
      details: { clientId: customer.data.id, clientName: customer.data.name, projectName: 'Pengadaan Laboratorium ' + code, date: '2026-11-20' },
      items: [{ sku, name: product.data.name, unit: 'PCS', qty, price }] });
    expect(response.status).toBe(200);
    return response.data;
  };
  return { contexts, errors, role, marketing, warehouse, product: product.data, save };
}

test('CAPS23 routes the three nominal boundaries through the real UI and persists Director approval before invoice', async ({ browser }, testInfo) => {
  test.setTimeout(120000);
  const s = await scenario(browser, testInfo, 'BOUNDARY');
  try {
    const orders = [];
    for (const [code, price, total] of [['BELOW', 4504504, 4999999], ['EXACT', 4504504.50, 5000000], ['ABOVE', 4504505, 5000001]]) {
      const saved = await s.save(code, price);
      expect(saved.grandTotal).toBe(total);
      orders.push(saved);
    }
    await s.marketing.reload();
    for (const order of orders) {
      const row = s.marketing.getByRole('row').filter({ hasText: order.orderNumber });
      const above = order.grandTotal > 5000000;
      await row.getByRole('button', { name: above ? 'Ajukan ke Direktur' : 'Ajukan ke Finance', exact: true }).click();
      const confirm = s.marketing.getByRole('dialog', { name: above ? 'Ajukan Pesanan ke Direktur?' : 'Ajukan Pesanan ke Finance?' });
      await confirm.getByRole('button', { name: above ? 'Ajukan ke Direktur' : 'Ajukan ke Finance', exact: true }).click();
      await expect(row).toContainText(above ? 'Menunggu Verifikasi' : 'Siap Invoice');
      await expect(row.getByRole('link', { name: 'Edit Order', exact: true })).toHaveCount(0);
    }
    const high = orders[2];
    const finance = await s.role('FINANCE', '/finance/invoices');
    await expect(finance.getByRole('row').filter({ hasText: high.orderNumber })).toHaveCount(0);
    expect((await api(finance, 'POST', `/orders/${high.orderNumber}/invoice`, { invoiceDate: '2026-11-20', dueDate: '2026-11-27' })).status).toBe(409);
    const director = await s.role('DIREKTUR', '/director/verification');
    const pending = director.getByRole('row').filter({ hasText: high.orderNumber });
    await pending.getByRole('button', { name: 'Telaah & Putuskan', exact: true }).click();
    const dialog = director.getByRole('dialog', { name: `Telaah & Keputusan Verifikasi Order: ${high.orderNumber}` });
    await dialog.getByPlaceholder('Catatan persetujuan untuk Marketing dan Finance...').fill('Data final dan stok sesuai kasus CAPS23.');
    await dialog.getByRole('button', { name: 'Setujui Order (Approve)', exact: true }).click();
    await expect(pending).toHaveCount(0);
    await director.getByRole('button', { name: /Riwayat Keputusan/ }).click();
    await expect(director.getByRole('row').filter({ hasText: high.orderNumber })).toContainText('UI DIREKTUR');
    await finance.reload();
    await finance.getByRole('row').filter({ hasText: high.orderNumber }).getByRole('button', { name: 'Terbitkan Invoice', exact: true }).click();
    const invoice = finance.getByRole('dialog', { name: 'Terbitkan Invoice Tagihan Baru' });
    await invoice.locator('input[type=date]').nth(0).fill('2026-11-20');
    await invoice.locator('input[type=date]').nth(1).fill('2026-11-27');
    await invoice.getByRole('button', { name: 'Terbitkan Tagihan', exact: true }).click();
    await expect(invoice).toHaveCount(0);
    const final = (await api(finance, 'GET', '/orders')).data.find(order => order.orderNumber === high.orderNumber);
    expect(final.status).toBe('Menunggu Pembayaran');
    expect(final.invoice.totalAmount).toBe(5000001);
    expect(final.timeline.filter(event => event.stage === 'Disetujui Direktur')).toHaveLength(1);
    expect(final.approvedBy).toBe('UI DIREKTUR');
    expect(final.approvalNotes).toBe('Data final dan stok sesuai kasus CAPS23.');
    expect(s.errors).toEqual([]);
    await finance.screenshot({ path: testInfo.outputPath('caps23-invoice-after-approved.png'), fullPage: true });
  } finally { for (const context of s.contexts) await context.close(); }
});

test('CAPS25 Finance issues a full invoice and downloads the same school order after reloading', async ({ browser }, testInfo) => {
  test.setTimeout(120000);
  const s = await scenario(browser, testInfo, 'INVOICE');
  try {
    const order = await s.save('LAB', 2252252.25, 2);
    expect(order.grandTotal).toBe(5000000);
    const submitted = await api(s.marketing, 'POST', `/orders/${order.orderNumber}/submit`, { version: order.version });
    expect(submitted.data.status).toBe('Siap Invoice');
    const finance = await s.role('FINANCE', '/finance/invoices');
    const row = finance.getByRole('row').filter({ hasText: order.orderNumber });
    await row.getByRole('button', { name: 'Terbitkan Invoice', exact: true }).click();
    const form = finance.getByRole('dialog', { name: 'Terbitkan Invoice Tagihan Baru' });
    await expect(form).toContainText('5.000.000');
    await form.locator('input[type=date]').nth(0).fill('2026-11-20');
    await form.locator('input[type=date]').nth(1).fill('2026-11-27');
    const issued = finance.waitForResponse(response => response.request().method() === 'POST' && response.url().endsWith(`/orders/${order.orderNumber}/invoice`));
    await form.getByRole('button', { name: 'Terbitkan Tagihan', exact: true }).click();
    const response = await issued;
    expect(response.ok()).toBeTruthy();
    const { data } = await response.json();
    expect(data.invoice).toMatchObject({ totalAmount: 5000000, paidAmount: 0, remainingAmount: 5000000, paymentType: 'Penuh', status: 'Belum Dibayar' });
    expect(data.invoice.customerProfile.contactPerson).toBe('PIC Laboratorium QA');
    await finance.reload();
    const invoiceRow = finance.getByRole('row').filter({ hasText: data.invoice.invoiceNumber });
    await expect(invoiceRow).toHaveCount(1);
    await expect(invoiceRow).toContainText(order.orderNumber);
    await expect(invoiceRow).toContainText(order.clientName);
    await expect(invoiceRow).toContainText('5.000.000');
    await invoiceRow.getByRole('button', { name: 'Detail', exact: true }).click();
    const detail = finance.getByRole('dialog', { name: new RegExp(data.invoice.invoiceNumber) });
    await expect(detail).toContainText(/VA|Virtual Account/i);
    await expect(detail).toContainText('QRIS');
    const downloadingWord = finance.waitForEvent('download');
    await detail.getByRole('button', { name: 'Unduh Word' }).click();
    const word = await downloadingWord;
    expect(word.suggestedFilename()).toBe('Invoice_' + data.invoice.invoiceNumber + '.docx');
    await word.saveAs(testInfo.outputPath('invoice-sekolah.docx'));
    const previewResponse = finance.waitForResponse(response => response.url().includes('/invoice/download?preview=1'));
    await detail.getByRole('button', { name: 'Cetak / Pratinjau' }).click();
    const preview = finance.getByRole('dialog', { name: 'Pratinjau Invoice: ' + data.invoice.invoiceNumber, exact: true });
    await expect(preview).toBeVisible();
    expect((await previewResponse).headers()['content-type']).toContain('application/pdf');
    const frame = preview.getByTitle('Pratinjau PDF Invoice', { exact: true });
    await expect(frame).toHaveAttribute('src', /^blob:/);
    await expect(preview.getByRole('link', { name: 'Buka di Tab Baru' })).toHaveAttribute('href', await frame.getAttribute('src'));
    await preview.getByRole('button', { name: 'Tutup', exact: true }).click();
    await expect(preview).toBeHidden();
    const downloading = finance.waitForEvent('download');
    await detail.getByRole('button', { name: 'Unduh PDF', exact: true }).click();
    const file = await downloading;
    expect(file.suggestedFilename()).toContain(data.invoice.invoiceNumber);
    const pdf = testInfo.outputPath('invoice-sekolah.pdf');
    await file.saveAs(pdf);
    expect(fs.readFileSync(pdf).subarray(0, 5).toString()).toBe('%PDF-');
    await testInfo.attach('Invoice sekolah', { path: pdf, contentType: 'application/pdf' });
    expect(s.errors).toEqual([]);
  } finally { for (const context of s.contexts) await context.close(); }
});

test('CAPS26 Finance sends a school invoice over SMTP, recovers from rejection and revokes guest access', async ({ browser }, testInfo) => {
  test.setTimeout(120000);
  const recipient = 'caps26-' + testInfo.project.name + '@example.test';
  const s = await scenario(browser, testInfo, 'DELIVERY', recipient);
  const scratch = fileURLToPath(new URL('../../.ui-review/inertia-ui/', import.meta.url));
  const mailbox = path.join(scratch, fs.readFileSync(path.join(scratch, 'mailbox-directory'), 'utf8'));
  function receivedLink(attempt) {
    const message = fs.readFileSync(path.join(mailbox, recipient + '-' + attempt + '.eml'), 'utf8').replace(/=\r?\n/g, '');
    expect(message).toContain(recipient);
    const url = message.match(/http:\/\/localhost:5174\/invoice-access\/[a-f0-9]{64}/)?.[0];
    if (!url) throw new Error('Tautan invoice tidak ditemukan di email uji.');
    return url;
  }
  try {
    const order = await s.save('SEKOLAH', 2000000, 2);
    expect((await api(s.marketing, 'POST', '/orders/' + order.orderNumber + '/submit', { version: order.version })).status).toBe(200);
    const finance = await s.role('FINANCE', '/finance/invoices');
    const issued = await api(finance, 'POST', '/orders/' + order.orderNumber + '/invoice', { invoiceDate: '2026-11-21', dueDate: '2026-11-28' });
    expect(issued.status).toBe(200);
    await finance.reload();
    await finance.getByRole('row').filter({hasText: order.orderNumber}).getByRole('button', { name: 'Detail', exact: true }).click();
    const delivery = finance.getByRole('region', {name:'Pengiriman invoice ke customer'});
    await expect(delivery).toContainText(recipient);
    await delivery.getByRole('button', {name:'Kirim invoice', exact:true}).click();
    await expect(delivery).toContainText('Terkirim', {timeout:20000});
    const first = receivedLink(1);
    const guest = await browser.newContext({ viewport: testInfo.project.use.viewport, isMobile: testInfo.project.use.isMobile });
    s.contexts.push(guest);
    const customer = await guest.newPage();
    expect((await customer.goto(first)).status()).toBe(200);
    await expect(customer.getByRole('heading', {name:'Invoice', exact:true})).toBeVisible();
    await expect(customer.getByRole('main')).toContainText(issued.data.invoice.invoiceNumber);
    await expect(customer.getByRole('main')).toContainText('Rp 4.440.000');
    await expect(customer.getByRole('main')).toContainText('Instruksi pembayaran belum tersedia');
    expect(await customer.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    await customer.screenshot({path:testInfo.outputPath('invoice-customer.png'), fullPage:true});
    const resend = delivery.getByRole('button', {name:'Kirim ulang invoice'});
    await resend.click();
    await expect(delivery).toContainText('Gagal dikirim', {timeout:20000});
    expect((await customer.reload()).status()).toBe(200);
    await resend.click();
    await expect(delivery).toContainText('Terkirim', {timeout:20000});
    const next = receivedLink(3);
    expect(next !== first).toBe(true);
    expect((await customer.reload()).status()).toBe(404);
    expect((await customer.goto(next)).status()).toBe(200);
    await delivery.getByRole('button', {name:'Cabut tautan'}).click();
    await expect(delivery).toContainText('Tidak aktif');
    expect((await customer.reload()).status()).toBe(404);
    const persisted = (await api(finance, 'GET', '/orders')).data.find(item => item.orderNumber === order.orderNumber);
    expect(persisted.invoice).toEqual(issued.data.invoice);
    expect(persisted.status).toBe('Menunggu Pembayaran');
    expect(s.errors).toEqual([]);
  } finally { for (const context of s.contexts) await context.close(); }
});

test('CAPS23 review shows the recorded Marketing submitter and offers only the agreed approval action', async ({ browser }, testInfo) => {
  test.setTimeout(120000);
  const s = await scenario(browser, testInfo, 'REVIEW');
  try {
    const saved = await s.save('PENDING', 5000000);
    const submitted = await api(s.marketing, 'POST', `/orders/${saved.orderNumber}/submit`, { version: saved.version });
    expect(submitted.status).toBe(200);
    expect(submitted.data.timeline.at(-1).user).toBe('UI MARKETING');
    const director = await s.role('DIREKTUR', '/director/verification');
    const row = director.getByRole('row').filter({ hasText: saved.orderNumber });
    await expect(row).toBeVisible();
    await expect.soft(row.getByRole('cell').nth(4), 'The submitter must come from the saved Marketing submission').toContainText('UI MARKETING');
    await row.getByRole('button', { name: 'Telaah & Putuskan', exact: true }).click();
    const dialog = director.getByRole('dialog', { name: `Telaah & Keputusan Verifikasi Order: ${saved.orderNumber}` });
    await expect.soft(dialog.getByRole('button', { name: /Tahan.*Revisi/ }), 'CAPS23 does not define a Hold or revision transition').toHaveCount(0);
    await expect(dialog).toContainText('Rp 800.000');
    await expect(dialog).toContainText('84%');
    const layout = await director.evaluate(width => ({ width, browserWidth: innerWidth, pageWidth: document.documentElement.scrollWidth,
      overflowing: [...document.querySelectorAll('body *')].filter(element => {
        const box = element.getBoundingClientRect();
        return box.width > 0 && box.right > width + 2 && getComputedStyle(element).position !== 'fixed';
      }).slice(0, 16).map(element => ({ tag: element.tagName, class: String(element.className), text: element.textContent.trim().slice(0, 70) })) }), director.viewportSize().width);
    fs.writeFileSync(testInfo.outputPath('caps23-layout.json'), JSON.stringify(layout, null, 2));
    await testInfo.attach('caps23-layout', { path: testInfo.outputPath('caps23-layout.json'), contentType: 'application/json' });
    expect.soft(layout.pageWidth, 'The approval page must fit the viewport; tables scroll inside their panels').toBeLessThanOrEqual(layout.width + 1);
    await director.screenshot({ path: testInfo.outputPath('caps23-review-contract.png'), fullPage: true });

    for (const [hpp, expected] of [[0, 'Data HPP belum tersedia'], [6000000, 'Rp -1.000.000']]) {
      const updated = await api(s.warehouse, 'PUT', `/products/${s.product.id}`, { sku: s.product.sku, name: s.product.name,
        unit: s.product.unit, category: s.product.category, hpp });
      expect(updated.status).toBe(200);
      await director.reload();
      await director.getByRole('row').filter({ hasText: saved.orderNumber }).getByRole('button', { name: 'Telaah & Putuskan', exact: true }).click();
      await expect(dialog).toContainText(expected);
      await expect(dialog).not.toContainText('22%');
    }
    await director.goto('/director/monitoring?tab=orders');
    const monitored = director.getByRole('row').filter({ hasText: saved.orderNumber });
    await expect(monitored).toBeVisible();
    await expect.soft(monitored.getByRole('button', { name: /Tahan|Lepas Tahan/ }), 'Monitoring must use the same agreed transition boundary as approval').toHaveCount(0);
    expect(s.errors).toEqual([]);
  } finally { for (const context of s.contexts) await context.close(); }
});

test('CAPS23 archives cached server Hold without losing its notes or changing unrelated local orders', async ({ browser }, testInfo) => {
  test.setTimeout(120000);
  const s = await scenario(browser, testInfo, 'CACHE');
  try {
    const saved = await s.save('HELD', 5000000);
    const submitted = await api(s.marketing, 'POST', `/orders/${saved.orderNumber}/submit`, { version: saved.version });
    expect(submitted.status).toBe(200);
    const server = submitted.data;
    for (const role of ['DIREKTUR', 'WAREHOUSE']) {
      const page = await s.role(role, role === 'DIREKTUR' ? '/director/monitoring?tab=orders' : '/warehouse/fulfillment');
      await expect.poll(() => page.evaluate(number => JSON.parse(localStorage.getItem('alpharo_integrated_system_v3') || '{}').orders?.some(order => order.orderNumber === number), saved.orderNumber)).toBe(true);
      // Seed on cold start, after the old document's beforeunload save has finished.
      await page.addInitScript(server => {
        if (sessionStorage.getItem('caps23-cache-seeded')) return;
        sessionStorage.setItem('caps23-cache-seeded', '1');
        const key = 'alpharo_integrated_system_v3';
        const state = JSON.parse(localStorage.getItem(key));
        state.orders = state.orders.filter(order => order.orderNumber !== server.orderNumber);
        state.orders.push({ ...server, isHold: true, holdReason: 'Catatan Hold browser versi lama', holdBy: 'Direktur Versi Lama', holdDate: '20/11/2026 09.00',
          timeline: [...server.timeline, { stage: 'Tahan Pengiriman (Hold)', user: 'Direktur Versi Lama', timestamp: '20/11/2026 09.00', status: 'completed', notes: 'Catatan Hold browser versi lama' }] });
        state.orders.push({ id: 'local-caps23-cache', orderNumber: 'LOCAL-CAPS23-UNLINKED', serverManaged: false, status: 'Draft',
          clientName: 'Customer Lokal QA', projectName: 'Pesanan Lokal Tidak Terhubung', items: [], timeline: [], isHold: true, holdReason: 'Catatan lokal tetap dipertahankan' });
        localStorage.setItem(key, JSON.stringify(state));
      }, server);
      const cached = () => page.evaluate(number => {
        const state = JSON.parse(localStorage.getItem('alpharo_integrated_system_v3'));
        return { order: state.orders.find(order => order.orderNumber === number), local: state.orders.find(order => order.orderNumber === 'LOCAL-CAPS23-UNLINKED') };
      }, server.orderNumber);
      await page.reload();
      await expect.poll(async () => (await cached()).order.isHold, { message: 'A browser-only Hold must not block a server-managed order' }).toBe(false);
      const first = await cached();
      expect(first.order.legacyHold).toMatchObject({ isHold: true, holdReason: 'Catatan Hold browser versi lama', holdBy: 'Direktur Versi Lama', holdDate: '20/11/2026 09.00' });
      expect(first.local).toMatchObject({ isHold: true, holdReason: 'Catatan lokal tetap dipertahankan' });
      const synchronized = page.waitForResponse(response => new URL(response.url()).pathname === '/api/orders');
      await page.reload();
      await synchronized;
      await expect.poll(async () => (await cached()).order.legacyHold).toEqual(first.order.legacyHold);
      const second = await cached();
      expect(second.order.isHold).toBe(false);
      expect(second.order.timeline.filter(event => event.stage === 'Tahan Pengiriman (Hold)')).toHaveLength(1);
      const persisted = (await api(page, 'GET', '/orders')).data.find(order => order.orderNumber === server.orderNumber);
      expect(persisted.version).toBe(server.version);
      expect(persisted.status).toBe(server.status);
      expect(persisted.timeline).toEqual(server.timeline);
    }
    expect(s.errors).toEqual([]);
  } finally { for (const context of s.contexts) await context.close(); }
});
