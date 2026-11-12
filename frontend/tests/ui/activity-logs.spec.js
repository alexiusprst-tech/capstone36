import { signIn } from './helpers/session';
import { test, expect } from '@playwright/test';

const roles = [
  ['MARKETING', '/marketing/dashboard'],
  ['DIREKTUR', '/director/dashboard'],
  ['ADMINISTRASI', '/administration/dashboard'],
  ['WAREHOUSE', '/warehouse/dashboard'],
  ['FINANCE', '/finance/dashboard'],
];
const logs = roles.flatMap(([role], roleIndex) => Array.from({ length: 6 }, (_, index) => ({
  id: roleIndex * 10 + index,
  role,
  timestamp: `2026-11-06 ${String(8 + index).padStart(2, '0')}:00`,
  user: `Pengguna ${role}`,
  action: `ACT-${role}-${index}`,
  target: `REF-${role}-${index}`,
  details: `DETAIL-${role}-${index}`,
})));
logs.push(
  { id: 100, role: 'CLIENT', action: 'ACT-CLIENT', timestamp: '2026-11-06 07:00', details: 'DETAIL-CLIENT' },
  { id: 101, role: 'SUPPLIER', action: 'ACT-SUPPLIER', timestamp: '2026-11-06 06:00', details: 'DETAIL-SUPPLIER' },
);

for (const [role, route] of roles) {
  test(`${role} dashboard activity respects division visibility, pagination and live updates without search`, async ({ page }, testInfo) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.clock.setFixedTime(new Date('2026-11-07T03:00:00Z'));
    await signIn(page, role);
    await page.route('**/api/**', request => request.fulfill({ status: 503, contentType: 'application/json', body: '{"message":"UI review API isolation"}' }));
    await page.addInitScript(role => {
      localStorage.clear();
      localStorage.setItem('token', 'ui-review-only');
      localStorage.setItem('user', JSON.stringify({ id: 1, name: 'Pengguna Review', role, email: 'review@example.test' }));
    }, role);
    await page.goto(route);
    await page.evaluate(async entries => {
      const { store } = await import(document.querySelector('script[type=module][src*="/src/main.js"]').src.replace('/main.js', '/stores/mockData.js'));
      store.activityLogs = entries;
    }, logs);
    const card = page.getByRole('region', { name: 'Log Aktivitas', exact: true });
    await expect(card).toHaveCount(1);
    await expect(card.getByRole('searchbox')).toHaveCount(0);
    await expect(card.getByRole('listitem')).toHaveCount(5);
    await expect(card.getByRole('status')).toHaveText(`${role === 'DIREKTUR' ? logs.length : 6} aktivitas tercatat`);
    if (role !== 'DIREKTUR') {
      for (const [other] of roles.filter(([other]) => other !== role)) await expect(card).not.toContainText(`DETAIL-${other}`);
      await expect(card.getByRole('listitem').first()).toContainText(`ACT-${role}-5`);
      await expect(card.getByRole('link', { name: 'Lihat audit lengkap' })).toHaveCount(0);
    } else {
      await expect(card.getByRole('link', { name: 'Lihat audit lengkap' })).toHaveAttribute('href', '/director/reports?tab=activities');
    }

    await card.getByRole('button', { name: 'Aktivitas berikutnya' }).click();
    if (role !== 'DIREKTUR') {
      await expect(card.getByRole('listitem')).toHaveCount(1);
      await expect(card).toContainText(`ACT-${role}-0`);
    }
    if (role === 'DIREKTUR') {
      const next = card.getByRole('button', { name: 'Aktivitas berikutnya' });
      while (await next.isEnabled()) await next.click();
      await expect(card).toContainText('DETAIL-SUPPLIER');
      await expect(card).toContainText('DETAIL-CLIENT');
    } else {
      await expect(card).not.toContainText('DETAIL-SUPPLIER');
      await expect(card).not.toContainText('DETAIL-CLIENT');
    }

    const previous = card.getByRole('button', { name: 'Aktivitas sebelumnya' });
    while (await previous.isEnabled()) await previous.click();
    await page.evaluate(async () => {
      const { storeActions } = await import(document.querySelector('script[type=module][src*="/src/main.js"]').src.replace('/main.js', '/stores/mockData.js'));
      storeActions.addActivityLog('Aktivitas baru langsung', 'REF-BARU', 'Pembaruan log divisi saat dashboard terbuka.');
    });
    await expect(card.getByRole('listitem').first()).toContainText('Aktivitas baru langsung');
    await expect(card).toContainText(`UI ${role}`);
    await card.scrollIntoViewIfNeeded();
    const box = await card.boundingBox();
    expect(box.x + box.width).toBeLessThanOrEqual(page.viewportSize().width);
    await page.screenshot({ path: testInfo.outputPath('activity-log.png'), animations: 'disabled' });

    await page.evaluate(async () => {
      const { store } = await import(document.querySelector('script[type=module][src*="/src/main.js"]').src.replace('/main.js', '/stores/mockData.js'));
      store.activityLogs = [];
    });
    await expect(card).toContainText('Belum ada aktivitas yang tercatat.');
    await expect(card.getByRole('button', { name: 'Aktivitas berikutnya' })).toHaveCount(0);
    expect(errors).toEqual([]);
  });
}
