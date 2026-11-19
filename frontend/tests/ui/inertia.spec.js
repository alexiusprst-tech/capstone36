import { test, expect } from '@playwright/test';
import { signIn } from './helpers/session';

test('internal links use Inertia and preserve browser back navigation', async ({ page }, testInfo) => {
  await signIn(page, 'MARKETING');
  await page.route('**/api/**', route => route.fulfill({ json: { data: [] } }));
  await page.goto('/marketing/dashboard');
  await page.evaluate(() => { window.inertiaReviewMarker = 'same-document'; });
  if (testInfo.project.name === 'mobile') await page.getByRole('button', { name: 'Buka navigasi', exact: true }).click();
  const visit = page.waitForResponse(response => new URL(response.url()).pathname === '/marketing/orders' && response.headers()['x-inertia'] === 'true');
  await page.locator('#app-sidebar').getByRole('link', { name: /Order|Pesanan|Proyek/ }).first().click();
  await visit;
  await expect(page).toHaveURL(/\/marketing\/orders$/);
  expect(await page.evaluate(() => window.inertiaReviewMarker)).toBe('same-document');
  await page.goBack();
  await expect(page).toHaveURL(/\/marketing\/dashboard$/);
  await expect(page.locator('.dashboard-workspace')).toBeVisible();
  expect(await page.evaluate(() => window.inertiaReviewMarker)).toBe('same-document');
});

test('query-only Inertia tab changes retain an unfinished portal form', async ({ page }) => {
  await page.goto('/portal?tab=request');
  const input = page.getByPlaceholder('Nama lengkap PIC pemesan...', { exact: true });
  await input.fill('Sekolah untuk uji Inertia');
  await page.getByRole('navigation', { name: 'Navigasi portal publik' }).getByRole('button', { name: 'Lacak Pesanan & Pembayaran', exact: true }).click();
  await expect(page).toHaveURL(/tab=tracking/);
  await page.getByRole('navigation', { name: 'Navigasi portal publik' }).getByRole('button', { name: 'Ajukan Kebutuhan', exact: true }).click();
  await expect(input).toHaveValue('Sekolah untuk uji Inertia');
});

test('web login, refresh and logout use the server session', async ({ page }, testInfo) => {
  await page.goto('/finance/purchases');
  await expect(page).toHaveURL(/\/login\?redirect=/);
  await page.getByLabel('Email perusahaan', { exact: true }).fill('finance@example.test');
  await page.getByLabel('Kata sandi', { exact: true }).fill('incorrect-password');
  await page.getByRole('button', { name: 'Masuk ke sistem', exact: true }).click();
  await expect(page.getByRole('alert')).toBeVisible();
  await expect(page).toHaveURL(/\/login\?redirect=/);
  await page.getByLabel('Kata sandi', { exact: true }).fill('ui-test-password');
  await page.getByRole('button', { name: 'Masuk ke sistem', exact: true }).click();
  await expect(page).toHaveURL(/\/finance\/purchases$/);
  await page.reload();
  await expect(page.getByRole('heading', { name: 'Transaksi Pembelian', exact: true })).toBeVisible();
  // Check the actual backend guard, independent of browser-local role data.
  await page.goto('/director/dashboard');
  await expect(page).toHaveURL(/\/finance\/dashboard$/);
  if (testInfo.project.name === 'mobile') await page.getByRole('button', { name: 'Buka navigasi', exact: true }).click();
  await page.getByRole('button', { name: 'Keluar dari sistem', exact: true }).first().click();
  await expect(page).toHaveURL(/\/login$/);
  await page.goto('/finance/dashboard');
  await expect(page).toHaveURL(/\/login\?redirect=/);
});

test('supplier legacy shipment URL selects the correct tab after refresh', async ({ page }) => {
  await signIn(page, 'SUPPLIER');
  await page.route('**/api/**', route => route.fulfill({ json: { data: [] } }));
  await page.goto('/supplier/shipments');
  await expect(page).toHaveURL(/\/supplier\/portal\?tab=shipments$/);
  await expect(page.getByRole('heading', { name: /Surat Jalan|Pengiriman/ }).first()).toBeVisible();
  await page.reload();
  await expect(page.getByRole('heading', { name: /Surat Jalan|Pengiriman/ }).first()).toBeVisible();
});
