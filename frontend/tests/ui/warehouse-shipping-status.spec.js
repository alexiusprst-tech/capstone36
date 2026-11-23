import { test, expect } from '@playwright/test';
import { signIn } from './helpers/session';

test('Warehouse shipping status uses Supplier wording and icons in the card, tracking and history', async ({ page }, info) => {
  const expected = [
    ['Sedang Dipersiapkan & Dikemas (Packing)', 'Sedang Dipersiapkan & Dikemas', '📦'],
    ['Kurir Mengambil Barang (Dispatched)', 'Kurir Mengambil Barang (Dispatched)', '🚚'],
    ['Menuju Alamat Gudang CV Alpharo (Out for Delivery)', 'Menuju Alamat Gudang (Out for Delivery)', '📍'],
    ['Penundaan Pengiriman / Kendala Rute (Delayed)', 'Penundaan Pengiriman / Kendala Rute', '⚠️'],
    ['Barang Pengganti Sedang Dikirim (Retur)', 'Barang Pengganti Sedang Dikirim (Retur)', '🚚'],
  ];
  const purchase = { id: 1, purchase_code: 'PO-STATUS-ICONS', status: 'DIPESAN', progress_persisted: true, progress_version: 6,
    default_supplier: 'Supplier Tampilan', project_name: 'Project Status', total_price: 100, receipts: [], receipt_summary: [],
    delivery_info: { shipping_status: expected[2][0], courier: 'Kurir Pengujian', tracking_number: 'RESI-ICONS',
      milestones: [{ status: 'Kurir Menjemput Paket (Dispatched)' }, { status: 'Menunggu jadwal bongkar' }, { status: '' }] },
    progress_history: expected.map(([status], index) => ({ version: index + 1, action: index ? 'tracking' : 'dispatch', user: 'Petugas Supplier', role: 'SUPPLIER', timestamp: `2026-11-20T0${index}:00:00Z`, after: { delivery_info: { shipping_status: status } } })),
  };
  await signIn(page, 'WAREHOUSE');
  const errors = [], writes = [];
  page.on('pageerror', error => errors.push(error.message));
  page.on('request', request => { if (new URL(request.url()).pathname.startsWith('/api/') && request.method() !== 'GET') writes.push(request.url()); });
  await page.route('**/api/purchases', route => route.fulfill({ json: { success: true, data: [purchase] } }));
  await page.goto('/warehouse/fulfillment?tab=inbound');
  const card = page.locator('article').filter({ has: page.getByRole('heading', { name: 'PO-STATUS-ICONS', exact: true }) });
  await expect(card.locator('[data-shipping-status]').getByTestId('shipping-status-icon')).toHaveText('📍');
  await page.getByRole('button', { name: 'Cek Resi PO-STATUS-ICONS', exact: true }).click();
  const tracking = page.getByRole('dialog', { name: 'Tracking Pengiriman PO-STATUS-ICONS', exact: true });
  const current = tracking.locator('dl > div').filter({ hasText: 'Status Pengiriman' });
  await expect(current).toContainText('Menuju Alamat Gudang (Out for Delivery)');
  await expect(current.getByTestId('shipping-status-icon')).toHaveText('📍');
  for (const [, label, icon] of expected) {
    const event = tracking.locator('ol').first().locator('li').filter({ hasText: label });
    await expect(event.locator('[data-shipping-status]')).toContainText(label);
    await expect(event.getByTestId('shipping-status-icon')).toHaveText(icon);
    await expect(event.getByTestId('shipping-status-icon')).toHaveAttribute('aria-hidden', 'true');
  }
  const supplierNotes = tracking.locator('ol').last();
  await expect(supplierNotes).toContainText('Kurir Mengambil Barang (Dispatched)');
  await expect(supplierNotes).toContainText('Menunggu jadwal bongkar');
  const blank = supplierNotes.locator('li').last();
  await expect(blank).toContainText('Pembaruan pengiriman');
  await expect(blank.getByTestId('shipping-status-icon')).toHaveText('ℹ️');
  expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(info.project.use.viewport.width + 1);
  await page.screenshot({ path: info.outputPath('warehouse-shipping-status-icons.png'), fullPage: false });
  expect(writes).toEqual([]); expect(errors).toEqual([]);
});
