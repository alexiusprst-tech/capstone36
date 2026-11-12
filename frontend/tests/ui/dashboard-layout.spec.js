import { signIn } from './helpers/session';
import { test, expect } from '@playwright/test';

const dashboards = [['MARKETING', 'marketing'], ['ADMINISTRASI', 'administration'], ['WAREHOUSE', 'warehouse'], ['FINANCE', 'finance'], ['DIREKTUR', 'director']];

for (const [role, prefix] of dashboards) {
  test(`${role} dashboard cards flow without holes or overlaps`, async ({ page }, testInfo) => {
    await signIn(page, role);
    await page.route('**/api/**', request => request.fulfill({ status: 503, contentType: 'application/json', body: '{}' }));
    await page.addInitScript(role => {
      localStorage.clear();
      localStorage.setItem('token', 'layout-review-only');
      localStorage.setItem('user', JSON.stringify({ id: 1, name: 'Review', role }));
    }, role);
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(`/${prefix}/dashboard`);
    const workspace = page.locator('.dashboard-workspace');
    await expect(workspace).toBeVisible();

    for (const state of ['default', 'busy']) {
      if (state === 'busy') {
        await page.evaluate(async role => {
          const { store } = await import(document.querySelector('script[type=module][src*="/src/main.js"]').src.replace('/main.js', '/stores/mockData.js'));
          store.activityLogs = Array.from({ length: 14 }, (_, index) => ({
            id: index,
            role,
            action: 'Memperbarui aktivitas operasional',
            timestamp: `2026-11-06 ${String(8 + Math.floor(index / 6)).padStart(2, '0')}:${String(index % 6).padStart(2, '0')}`,
            user: 'Pengguna Divisi',
            target: `REF-${index}`,
            details: 'Rincian aktivitas operasional tetap tersedia sepenuhnya dalam panel log yang dapat digulir.',
          }));
        }, role);
        await expect(workspace.getByRole('status')).toHaveText('14 aktivitas tercatat');
      }
      const geometry = await workspace.evaluate(element => {
        const box = node => { const rect = node.getBoundingClientRect(); return { x: rect.x, y: rect.y, right: rect.right, bottom: rect.bottom, width: rect.width, height: rect.height }; };
        const cards = [...element.children].map(box);
        return { frame: box(element), cards, footer: box(document.querySelector('.app-footer')), overflow: document.documentElement.scrollWidth - innerWidth };
      });
      expect(geometry.overflow).toBeLessThanOrEqual(1);
      expect(geometry.cards.length).toBeGreaterThanOrEqual(3);
      for (const card of geometry.cards) {
        expect(Math.abs(card.x - geometry.frame.x), `${role} ${state}: card left edge`).toBeLessThanOrEqual(1);
        expect(Math.abs(card.width - geometry.frame.width), `${role} ${state}: empty space beside card`).toBeLessThanOrEqual(1);
      }
      for (let index = 1; index < geometry.cards.length; index++) {
        const gap = geometry.cards[index].y - geometry.cards[index - 1].bottom;
        expect(gap, `${role} ${state}: spacing between cards`).toBeGreaterThanOrEqual(15);
        expect(gap, `${role} ${state}: empty space between cards`).toBeLessThanOrEqual(21);
      }
      const lastCard = geometry.cards.at(-1);
      expect(Math.abs(lastCard.bottom - geometry.frame.bottom), `${role} ${state}: unused workspace below final card`).toBeLessThanOrEqual(1);
      expect(geometry.footer.y - lastCard.bottom, `${role} ${state}: empty dashboard area before footer`).toBeLessThanOrEqual(33);
      const nestedCards = await page.locator('#page-content .ui-panel div, #page-content .ui-panel article, #page-content .ui-panel section').evaluateAll(elements => elements.filter(element => {
        const style = getComputedStyle(element);
        const hasBorder = ['borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth'].some(property => parseFloat(style[property]) > 0);
        const hasFill = style.backgroundColor !== 'rgba(0, 0, 0, 0)' && style.backgroundColor !== 'transparent';
        return element.getClientRects().length && hasBorder && hasFill && parseFloat(style.borderRadius) > 0;
      }).map(element => element.textContent.trim().slice(0, 80)));
      expect(nestedCards, `${role} ${state}: decorative cards nested inside panels`).toEqual([]);
      const statusBoxes = await workspace.locator('[data-status]').evaluateAll(elements => elements.filter(element => {
        const style = getComputedStyle(element);
        return style.backgroundColor !== 'rgba(0, 0, 0, 0)' || parseFloat(style.borderTopWidth) > 0 || parseFloat(style.borderRadius) > 0;
      }).map(element => element.textContent.trim()));
      expect(statusBoxes, `${role} ${state}: statuses should be unboxed labels`).toEqual([]);
      for (let index = 0; index < geometry.cards.length; index++) {
        for (const other of geometry.cards.slice(index + 1)) {
          const card = geometry.cards[index];
          const overlapWidth = Math.min(card.right, other.right) - Math.max(card.x, other.x);
          const overlapHeight = Math.min(card.bottom, other.bottom) - Math.max(card.y, other.y);
          expect(overlapWidth > 1 && overlapHeight > 1, `${role} ${state}: cards overlap`).toBe(false);
        }
      }
      if (testInfo.project.name === 'desktop') {
        const logList = workspace.getByRole('list', { name: 'Riwayat aktivitas' });
        if (state === 'busy') {
          expect(await logList.evaluate(element => element.scrollHeight > element.clientHeight)).toBe(true);
          expect((await logList.boundingBox()).height).toBeLessThanOrEqual(241);
        }
      }
      await page.screenshot({ path: testInfo.outputPath(`${state}-dashboard.png`), fullPage: true, animations: 'disabled' });
    }

    const activity = workspace.getByRole('region', { name: 'Log Aktivitas' });
    const list = activity.getByRole('list', { name: 'Riwayat aktivitas' });
    await list.evaluate(element => { element.scrollTop = element.scrollHeight; });
    await activity.getByRole('button', { name: 'Aktivitas berikutnya' }).click();
    await expect.poll(() => list.evaluate(element => element.scrollTop)).toBe(0);
    if (testInfo.project.name === 'desktop') {
      for (const width of [1024, 1280]) {
        await page.setViewportSize({ width, height: 1000 });
        const frame = await workspace.boundingBox();
        const cards = await workspace.locator(':scope > .ui-panel').evaluateAll(elements => elements.map(element => {
          const rect = element.getBoundingClientRect();
          return { x: rect.x, width: rect.width };
        }));
        for (const card of cards) {
          expect(Math.abs(card.x - frame.x)).toBeLessThanOrEqual(1);
          expect(Math.abs(card.width - frame.width)).toBeLessThanOrEqual(1);
        }
        expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
        await page.screenshot({ path: testInfo.outputPath(`dashboard-${width}.png`), fullPage: true, animations: 'disabled' });
      }
    }
    await page.evaluate(async () => {
      const { store } = await import(document.querySelector('script[type=module][src*="/src/main.js"]').src.replace('/main.js', '/stores/mockData.js'));
      store.activityLogs = [];
    });
    await expect(activity).toContainText('Belum ada aktivitas yang tercatat.');
    const finalCard = await activity.boundingBox();
    const footer = await page.locator('.app-footer').boundingBox();
    expect(footer.y - finalCard.y - finalCard.height).toBeLessThanOrEqual(33);
    expect(errors).toEqual([]);
  });
}
