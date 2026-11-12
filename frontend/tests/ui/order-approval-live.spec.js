import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import { signIn } from './helpers/session';

// Explicit opt-in: this suite uses a running Laravel API and a context prepared in an isolated QA run.
const contextPath = process.env.T22_LIVE_CONTEXT;
const live = contextPath ? JSON.parse(fs.readFileSync(contextPath, 'utf8')) : null;
test.skip(!live, 'Set T22_LIVE_CONTEXT to run the real API integration check.');
test.use({ trace: 'off' });

test('Marketing submission, Director approval and Finance invoice stay consistent across browsers', async ({ browser, request }, testInfo) => {
  test.setTimeout(90000);
  const apiURL = process.env.T22_LIVE_API || 'http://127.0.0.1:8000';
  const errors = [];
  const contexts = [];
  const openRole = async (role, path) => {
    const context = await browser.newContext({ viewport: testInfo.project.use.viewport });
    contexts.push(context);
    await context.addInitScript(({ user, token }) => {
      localStorage.setItem('user', JSON.stringify(user));
      localStorage.setItem('token', token);
    }, live.users[role]);
    const page = await context.newPage();
    await signIn(page, role, live.users[role].user.email);
    page.on('pageerror', error => errors.push(error.message));
    // Forward to the real API; all authorization, validation and persistence run in Laravel/MySQL.
    await page.route('**/api/**', async route => {
      const url = new URL(route.request().url());
      const response = await route.fetch({ url: apiURL + url.pathname + url.search });
      await route.fulfill({ response });
    });
    const loaded = page.waitForResponse(response => new URL(response.url()).pathname === '/api/orders');
    await page.goto(path);
    await loaded;
    return page;
  };
  const auth = role => ({ Authorization: `Bearer ${live.users[role].token}`, Accept: 'application/json' });
  const number = live.prefix + testInfo.project.name.toUpperCase();
  try {
    const save = await request.put(`${apiURL}/api/orders/${number}`, {
      headers: auth('MARKETING'),
      data: { version: 0, details: { clientName: 'Uji Integrasi T22', projectName: 'Pesanan Persetujuan', clientId: 1 },
        items: [{ sku: live.product.sku, name: live.product.name, unit: live.product.unit, qty: 1, price: 5000000 }] },
    });
    expect(save.ok()).toBeTruthy();
    const marketing = await openRole('MARKETING', '/marketing/orders');
    const row = marketing.getByRole('row').filter({ hasText: number });
    await expect(row).toContainText('Draft');
    await row.getByRole('link', { name: 'Edit Order' }).click();
    const editURL = marketing.url();
    const projectName = marketing.locator('form').first().locator('input[required]').first();
    await expect(projectName).toHaveValue('Pesanan Persetujuan');
    await projectName.fill('Pesanan Persetujuan Final');
    await marketing.getByRole('button', { name: 'Simpan', exact: true }).first().click();
    await expect(marketing).toHaveURL(/\/marketing\/orders$/);
    await expect(row).toContainText('Pesanan Persetujuan Final');
    await row.getByRole('button', { name: 'Ajukan', exact: true }).click();
    const confirm = marketing.getByRole('dialog', { name: 'Ajukan persetujuan pesanan?' });
    await expect(confirm).toContainText('5.550.000');
    await confirm.getByRole('button', { name: 'Ajukan Pesanan', exact: true }).click();
    await expect(row).toContainText('Menunggu Verifikasi');
    await expect(row.getByRole('link', { name: 'Edit Order' })).toHaveCount(0);
    await expect(row.getByRole('button', { name: 'Ajukan persetujuan' })).toHaveCount(0);
    await marketing.reload();
    await expect(marketing.getByRole('row').filter({ hasText: number })).toContainText('Menunggu Verifikasi');
    await marketing.goto(editURL);
    await expect(marketing).toHaveURL(/\/marketing\/orders$/);

    const finance = await openRole('FINANCE', '/finance/invoices');
    await finance.getByRole('button', { name: /Terbitkan Invoice Baru|Buat Invoice|Terbitkan Invoice/ }).first().click();
    await expect(finance.locator('option').filter({ hasText: number })).toHaveCount(0);
    const blocked = await request.post(`${apiURL}/api/orders/${number}/invoice`, {
      headers: auth('FINANCE'), data: { invoiceDate: '2026-11-06', dueDate: '2026-11-20', paymentType: 'Penuh', terminCount: 1 },
    });
    expect(blocked.status()).toBe(409);

    const director = await openRole('DIREKTUR', '/director/verification');
    const pending = director.getByRole('row').filter({ hasText: number });
    await pending.getByRole('button', { name: /Telaah|Putuskan/ }).click();
    const dialog = director.getByRole('dialog');
    await dialog.getByRole('button', { name: /Setujui/ }).click();
    await expect(pending).toHaveCount(0);
    await director.screenshot({ path: testInfo.outputPath('director-after-approval.png'), fullPage: true });

    await finance.reload();
    await finance.getByRole('button', { name: /Terbitkan Invoice Baru|Buat Invoice|Terbitkan Invoice/ }).first().click();
    await finance.locator('select').first().selectOption(number);
    const invoiceDialog = finance.getByRole('dialog');
    await expect(invoiceDialog).toContainText('5.550.000');
    await invoiceDialog.getByRole('button', { name: 'Terbitkan Tagihan' }).click();
    await expect(finance.getByRole('row').filter({ hasText: number })).toContainText('5.550.000');
    await finance.reload();
    await expect(finance.getByRole('row').filter({ hasText: number })).toContainText('5.550.000');
    const data = await request.get(`${apiURL}/api/orders`, { headers: auth('MARKETING') });
    const final = (await data.json()).data.find(order => order.orderNumber === number);
    expect(final.status).toBe('Menunggu Pembayaran');
    expect(final.approvedById).toBe(live.users.DIREKTUR.user.id);
    expect(final.timeline.some(event => event.role === 'DIREKTUR' && event.userId === live.users.DIREKTUR.user.id)).toBeTruthy();
    expect(final.timeline.at(-1).role).toBe('FINANCE');
    expect(errors).toEqual([]);
    await finance.screenshot({ path: testInfo.outputPath('invoice-persisted.png'), fullPage: true });
  } finally { await Promise.all(contexts.map(context => context.close())); }
});
