import { test, expect } from '@playwright/test';
import { signIn } from './helpers/session';
import { customerFixture } from './helpers/customer-fixtures';

test('Marketing can identify legacy catalog lines and explicitly update items, units and quotation prices', async ({ page }) => {
  await signIn(page, 'MARKETING');
  const products = [
    { id: 568, sku: 'A00000714', name: 'Granit 60 x 60', unit: 'M', stock: 100, available_stock: 100, sell_price: 0 },
    { id: 1308, sku: 'A00001678', name: 'Pasir Pasang', unit: 'M3', stock: 100, available_stock: 100, sell_price: 0 },
  ];
  let order = { id: 'server-77', serverManaged: true, version: 1, orderNumber: 'ORD-CATALOG-REVIEW',
    status: 'Draft', clientId: 1, clientName: 'SMA 9', projectName: 'Perbaikan katalog pesanan',
    items: [{ sku: 'MAT-007', name: 'Granit Tile Lama', unit: 'Dus', qty: 1, price: 215000 }],
    stockReadiness: { status: 'Menunggu Stok', items: [{ sku: 'MAT-007', name: 'Granit Tile Lama', unit: 'Dus', catalogFound: false, required: 1, allocated: 0, shortage: 1 }] },
    subtotal: 215000, tax: 23650, grandTotal: 238650, timeline: [],
  };
  let saved = false;
  await page.route('**/api/**', route => {
    const request = route.request();
    const path = new URL(request.url()).pathname;
    if (path === '/api/customers') return route.fulfill({ json: { data: [customerFixture] } });
    if (request.method() === 'PUT') {
      const payload = request.postDataJSON();
      expect(payload.details.customerProfile).toMatchObject({ code: 'CS00001', orgFullName: 'SEKOLAH MENENGAH ATAS NEGERI 9 JAKARTA', province: 'DKI JAKARTA' });
      expect(payload.items[0]).toMatchObject({ sku: 'A00000714', name: 'Granit 60 x 60', unit: 'M', qty: 1, price: 100000 });
      order = { ...order, ...payload.details, items: payload.items, version: 2,
        stockReadiness: { status: 'Siap Diajukan', items: [{ ...payload.items[0], catalogFound: true, required: 1, allocated: 1, shortage: 0 }] } };
      saved = true;
      return route.fulfill({ json: { data: order } });
    }
    return route.fulfill({ json: { data: path === '/api/products' ? products : path.startsWith('/api/orders') ? (path.endsWith('/stock-check') ? order : [order]) : [] } });
  });
  await page.goto('/marketing/orders');
  const row = page.getByRole('row').filter({ hasText: order.orderNumber });
  await expect(row).toContainText('Perbarui barang katalog');
  await row.getByRole('button', { name: /^Ajukan(?: ke| persetujuan|$)/i }).click();
  await expect(page.getByText('Barang belum terdaftar: Granit Tile Lama. Pilih ulang di Edit Order.', { exact: true })).toBeVisible();
  await row.getByRole('link', { name: 'Perbarui Barang', exact: true }).click();
  await expect(page.getByText('Barang ini belum terdaftar di katalog gudang. Pilih ulang barang yang sesuai dan periksa satuannya.', { exact: true })).toBeVisible();
  await page.getByRole('combobox', { name: 'Barang pesanan 1', exact: true }).click();
  await page.getByRole('searchbox', { name: 'Cari nama, kode, atau satuan barang...', exact: true }).fill('A00000714');
  await page.getByRole('option', { name: /Granit 60 x 60/ }).click();
  await expect(page.getByPlaceholder('Nama barang / spesifikasi...', { exact: true })).toHaveValue('Granit 60 x 60');
  await expect(page.getByText('Harga jual belum diisi. Isi harga penawaran.', { exact: true })).toBeVisible();
  await page.getByRole('spinbutton', { name: 'Harga barang 1', exact: true }).fill('100000');
  await page.getByRole('button', { name: 'Simpan', exact: true }).click();
  await expect(page).toHaveURL(/\/marketing\/orders$/);
  expect(saved).toBe(true);
  await expect(page.getByRole('row').filter({ hasText: order.orderNumber })).toContainText('Siap Diajukan');
});

test('product search covers a large catalog without changing the selection until a result is chosen', async ({ page }, testInfo) => {
  await signIn(page, 'MARKETING');
  const products = Array.from({ length: 2200 }, (_, index) => ({
    id: index + 1, sku: `RKAS-${String(index + 1).padStart(5, '0')}`, name: `Barang katalog ${index + 1}`,
    unit: 'UNIT', available_stock: 20, stock: 20, sell_price: 5000,
  }));
  products[1677] = { ...products[1677], name: 'Pasir Pasang untuk pekerjaan pembangunan dan pemeliharaan sarana laboratorium sekolah', unit: 'M3', available_stock: 100, stock: 100, sell_price: 125000 };
  const order = { id: 'server-search', serverManaged: true, version: 1, orderNumber: 'ORD-SEARCH', status: 'Draft',
    clientId: 1, clientName: 'SMA 9', projectName: 'Pengujian pencarian katalog', items: [{ ...products[0], qty: 1, price: 5000 }], timeline: [] };
  let writes = 0;
  await page.route('**/api/**', route => {
    if (route.request().method() !== 'GET') writes++;
    if (new URL(route.request().url()).pathname === '/api/customers') return route.fulfill({ json: { data: [customerFixture] } });
    return route.fulfill({ json: { data: new URL(route.request().url()).pathname === '/api/products' ? products : [order] } });
  });
  await page.goto('/marketing/orders/server-search/edit');
  const trigger = page.getByRole('combobox', { name: 'Barang pesanan 1', exact: true });
  const search = page.getByRole('searchbox', { name: 'Cari nama, kode, atau satuan barang...', exact: true });
  await expect(trigger).toContainText('RKAS-00001');
  await trigger.focus();
  await trigger.press('ArrowDown');
  await expect(search).toBeFocused();
  const list = page.getByRole('listbox', { name: 'Barang pesanan 1', exact: true });
  await expect(list.getByRole('option')).toHaveCount(100);
  await list.evaluate(node => { node.scrollTop = node.scrollHeight; });
  await expect.poll(() => list.getByRole('option').count()).toBeGreaterThan(100);

  await search.fill('pAsIr M3');
  await expect(list.getByRole('option')).toHaveCount(1);
  await expect(list.getByRole('option')).toContainText('Tersedia: 100 M3');
  await search.fill('RKAS-01678');
  await expect(list.getByRole('option')).toContainText('Pasir Pasang');
  await search.fill('TIDAK-ADA-XYZ');
  await expect(page.getByText('Tidak ada pilihan yang cocok.', { exact: true })).toBeVisible();
  await search.press('Enter');
  await expect(trigger).toContainText('RKAS-00001');
  await expect(page.getByRole('spinbutton', { name: 'Harga barang 1', exact: true })).toHaveValue('5000');
  await search.press('Escape');
  await expect(trigger).toBeFocused();
  await expect(list).toHaveCount(0);

  await trigger.click();
  await search.fill('pasir m3');
  const geometry = await list.locator('..').evaluate(node => {
    const bounds = node.getBoundingClientRect();
    return { left: bounds.left, right: bounds.right, top: bounds.top, bottom: bounds.bottom, width: innerWidth, height: innerHeight, overflow: node.scrollWidth - node.clientWidth, listHeight: node.querySelector('[role="listbox"]').clientHeight, listContent: node.querySelector('[role="listbox"]').scrollHeight };
  });
  expect(geometry.left).toBeGreaterThanOrEqual(0);
  expect(geometry.right).toBeLessThanOrEqual(geometry.width);
  expect(geometry.top).toBeGreaterThanOrEqual(0);
  expect(geometry.bottom).toBeLessThanOrEqual(geometry.height);
  expect(geometry.overflow).toBeLessThanOrEqual(1);
  expect(geometry.listContent).toBeLessThanOrEqual(geometry.listHeight + 1);
  await list.locator('..').screenshot({ path: testInfo.outputPath('searchable-products.png') });
  await search.press('Enter');
  await expect(trigger).toContainText('Pasir Pasang');
  await expect(page.getByPlaceholder('Nama barang / spesifikasi...', { exact: true })).toHaveValue(products[1677].name);
  await expect(page.getByRole('spinbutton', { name: 'Harga barang 1', exact: true })).toHaveValue('125000');

  await trigger.click();
  await search.fill('barang');
  await search.press('Tab');
  await expect(page.getByPlaceholder('Nama barang / spesifikasi...', { exact: true })).toBeFocused();
  await expect(trigger).toContainText('Pasir Pasang');
  await trigger.click();
  await page.getByRole('heading').first().click();
  await expect(list).toHaveCount(0);
  expect(writes).toBe(0);
});
