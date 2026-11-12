import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import { signIn } from './helpers/session';

const live = process.env.PO_LIVE_CONTEXT ? JSON.parse(fs.readFileSync(process.env.PO_LIVE_CONTEXT, 'utf8')) : null;
test.skip(!live, 'Set PO_LIVE_CONTEXT to run against the real local API.');
test.use({ trace: 'off' });

test('Supplier shipment stays saved after Finance creates a second PO and pages reload', async ({ browser, request }, testInfo) => {
  test.setTimeout(120000);
  const apiURL = 'http://127.0.0.1:8000';
  const contexts = [];
  const errors = [];
  const project = live.prefix + '-' + testInfo.project.name;
  const open = async (role, path) => {
    const context = await browser.newContext({ viewport: testInfo.project.use.viewport });
    contexts.push(context);
    await context.addInitScript(({ user, token }) => {
      localStorage.setItem('user', JSON.stringify(user));
      localStorage.setItem('token', token);
    }, live.users[role]);
    const page = await context.newPage();
    await signIn(page, role, live.users[role].user.email);
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/api/**', async route => {
      const url = new URL(route.request().url());
      await route.fulfill({ response: await route.fetch({ url: apiURL + url.pathname + url.search }) });
    });
    await page.goto(path);
    return page;
  };
  try {
    const makeRequest = async suffix => {
      const response = await request.post(apiURL + '/api/warehouse/requests', { data: {
        project_name: project + suffix, purpose: 'Uji regresi PO', requested_by: 'Gudang Uji',
        items: [{ sku: project, name: 'Barang regresi PO', qty: 1, unit: 'UNIT', est_price: 100 }],
      } });
      expect(response.ok()).toBeTruthy();
      return (await response.json()).data;
    };
    const issuePO = async warehouseRequest => {
      const page = await open('FINANCE', '/finance/purchases/create?requestCode=' + warehouseRequest.request_code + '&supplier=' + encodeURIComponent(live.supplier.name));
      await expect(page.getByRole('combobox').filter({ has: page.getByRole('option', { name: warehouseRequest.project_name, exact: true }) })).toHaveValue(warehouseRequest.project_name);
      const saved = page.waitForResponse(response => new URL(response.url()).pathname === '/api/purchases' && response.request().method() === 'POST');
      await page.getByRole('button', { name: /Simpan/ }).first().click();
      const response = await saved;
      expect(response.ok()).toBeTruthy();
      const po = (await response.json()).data;
      await expect(page).toHaveURL(/\/finance\/purchases$/);
      return { page, po };
    };
    const first = await issuePO(await makeRequest('-FIRST'));
    const supplier = await open('SUPPLIER', '/supplier/portal');
    const row = supplier.getByRole('article', { name: 'PO ' + first.po.purchase_code, exact: true });
    await expect(row).toContainText('Menunggu konfirmasi');
    await row.getByRole('button', { name: /Konfirmasi|Terima PO/ }).first().click();
    await supplier.getByRole('dialog').getByRole('button', { name: /Konfirmasi|Terima/ }).last().click();
    await expect(row.getByRole('button', { name: /Kirim Barang/ })).toBeVisible();
    await row.getByRole('button', { name: /Kirim Barang/ }).click();
    const sent = supplier.waitForResponse(response => response.url().includes('/supplier-progress') && response.request().postDataJSON()?.action === 'dispatch');
    await supplier.getByRole('dialog').getByRole('button', { name: /Kirim|Berangkat/ }).last().click();
    const shipped = (await (await sent).json()).data;
    expect(shipped.supplier_status).toBe('Dikirim ke Gudang CV');
    const tracking = shipped.delivery_info.tracking_number;
    await issuePO(await makeRequest('-SECOND'));
    await first.page.reload();
    await supplier.reload();
    await expect(row.locator('[data-order-status]')).toContainText('Dijemput kurir');
    await expect(row.getByRole('button', { name: /Konfirmasi PO|Kirim Barang/ })).toHaveCount(0);
    const list = await request.get(apiURL + '/api/purchases');
    const original = (await list.json()).data.find(item => item.purchase_code === first.po.purchase_code);
    expect(original.delivery_info.tracking_number).toBe(tracking);
    expect(original.progress_history.map(event => event.action)).toEqual(['confirm', 'dispatch']);
    const activities = await supplier.evaluate(async code => {
      const { store } = await import(document.querySelector('script[type=module][src*="/src/main.js"]').src.replace('/main.js', '/stores/mockData.js'));
      return store.activityLogs.filter(log => log.target === code && ['Konfirmasi Pesanan PO', 'Kirim Barang Supplier'].includes(log.action)).map(log => log.action);
    }, first.po.purchase_code);
    expect(activities.sort()).toEqual(['Kirim Barang Supplier', 'Konfirmasi Pesanan PO']);
    expect(errors).toEqual([]);
    await supplier.screenshot({ path: testInfo.outputPath('supplier-po-still-shipped.png'), fullPage: true });
  } finally { await Promise.all(contexts.map(context => context.close())); }
});
