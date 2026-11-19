import { test, expect } from '@playwright/test';
import { mkdirSync } from 'node:fs';

test('company profile remains readable and aligned across viewport widths', async ({ page }, testInfo) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto('/company-profile');
  await expect(page).toHaveURL(/\/portal\?tab=profile$/);
  await expect(page.getByRole('heading', { level: 1 })).toHaveCount(1);
  await expect(page.getByRole('heading', { name: /Mitra pengadaan/ })).toBeVisible();
  const navigation = page.getByRole('navigation', { name: 'Navigasi portal publik' });
  await expect(navigation.getByRole('button', { name: 'Profil Perusahaan', exact: true })).toHaveAttribute('aria-current', 'page');

  const widths = testInfo.project.name === 'desktop' ? [1920, 1440, 1280, 1024, 900, 768] : [390, 320];
  for (const width of widths) {
    await page.setViewportSize({ width, height: 900 });
    expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth), `Horizontal overflow at ${width}px`).toBeLessThanOrEqual(1);
    const buttons = await navigation.getByRole('button').evaluateAll(items => items.map(item => {
      const rect = item.getBoundingClientRect();
      return { left: rect.left, right: rect.right, height: rect.height, clipped: item.scrollWidth > item.clientWidth + 1 };
    }));
    expect(buttons).toHaveLength(3);
    buttons.forEach((button, index) => {
      expect(button.left).toBeGreaterThanOrEqual(0);
      expect(button.right).toBeLessThanOrEqual(width);
      expect(button.height).toBeGreaterThanOrEqual(44);
      expect(button.clipped).toBe(false);
      if (index) expect(button.left).toBeGreaterThanOrEqual(buttons[index - 1].right);
    });
  }

  await page.setViewportSize(testInfo.project.name === 'desktop' ? { width: 1440, height: 1000 } : { width: 390, height: 844 });
  for (const image of await page.locator('.company-profile img').all()) {
    await image.scrollIntoViewIfNeeded();
    await expect.poll(() => image.evaluate(img => img.complete && img.naturalWidth > 0)).toBe(true);
  }
  await expect(page.getByRole('heading', { name: 'General Supplier & Sarpras', exact: true })).toBeVisible();
  await expect(page.getByText('BCA · 52323628813', { exact: true })).toBeVisible();
  await expect(page.getByTitle('Lokasi kantor CV Alpharo Artha Jaya di Google Maps')).toBeVisible();
  expect(errors).toEqual([]);
  mkdirSync('.ui-review/company-profile', { recursive: true });
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.screenshot({ path: `.ui-review/company-profile/${testInfo.project.name}.png`, fullPage: true, animations: 'disabled' });
  await expect(page.getByTitle('Lokasi kantor CV Alpharo Artha Jaya di Google Maps')).toHaveAttribute('src', /^https:\/\/www\.google\.com\/maps\/embed\?/);
  await expect(page.locator('.company-map-footer').getByRole('link', { name: 'Buka Google Maps' })).toHaveAttribute('href', 'https://maps.app.goo.gl/3qdNy8ETp7YdoGuU9');
});

test('company profile actions preserve the public service flow and unfinished request', async ({ page }) => {
  await page.goto('/portal');
  await page.getByRole('button', { name: 'Ajukan Kebutuhan', exact: true }).last().click();
  await expect(page).toHaveURL(/tab=request/);
  const input = page.getByPlaceholder('Nama lengkap PIC pemesan...', { exact: true });
  await input.fill('PIC Sekolah Uji Desain');
  const navigation = page.getByRole('navigation', { name: 'Navigasi portal publik' });
  await navigation.getByRole('button', { name: 'Profil Perusahaan', exact: true }).click();
  await page.getByRole('button', { name: 'Lacak Pesanan', exact: true }).last().click();
  await expect(page).toHaveURL(/tab=tracking/);
  await expect(page.getByRole('heading', { name: 'Lacak Pesanan, Pengiriman & Pembayaran Tagihan', exact: true })).toBeVisible();
  await navigation.getByRole('button', { name: 'Lacak Pesanan & Pembayaran', exact: true }).click();
  await expect(page).toHaveURL(/tab=tracking/);
  await expect(page.getByRole('heading', { name: 'Lacak Pesanan, Pengiriman & Pembayaran Tagihan', exact: true })).toBeVisible();
  await navigation.getByRole('button', { name: 'Profil Perusahaan', exact: true }).click();
  await page.getByRole('button', { name: 'Buat Pesanan Sekarang', exact: true }).click();
  await expect(input).toHaveValue('PIC Sekolah Uji Desain');
  await expect(page.getByRole('link', { name: 'Login Staff Internal', exact: true })).toHaveAttribute('href', '/login');
});
