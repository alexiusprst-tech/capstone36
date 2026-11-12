import { test, expect } from '@playwright/test';
import { signIn } from './helpers/session';

const dashboards = [['WAREHOUSE', 'warehouse'], ['MARKETING', 'marketing'], ['ADMINISTRASI', 'administration'], ['FINANCE', 'finance'], ['DIREKTUR', 'director']];

for (const [role, prefix] of dashboards) {
  test(`${role} dashboard panels pass wheel scrolling to the page at both edges`, async ({ page }) => {
    await signIn(page, role);
    await page.route('**/api/**', route => route.fulfill({ status: 503, json: {} }));
    await page.goto(`/${prefix}/dashboard`);
    const workspace = page.locator('.dashboard-workspace');
    await expect(workspace).toBeVisible();
    // Leave room on both sides of every panel, including the final activity list.
    // These spacers isolate scroll chaining from the document's own boundaries.
    await page.evaluate(() => {
      for (const position of ['afterbegin', 'beforeend']) {
        const spacer = document.createElement('div');
        spacer.style.height = '100vh';
        spacer.setAttribute('aria-hidden', 'true');
        document.body.insertAdjacentElement(position, spacer);
      }
    });
    const panels = [];
    for (const panel of await workspace.locator('[class*="overflow-"]').elementHandles()) {
      if (await panel.evaluate(node => node.clientHeight > 0 && ['auto', 'scroll'].includes(getComputedStyle(node).overflowY))) panels.push(panel);
    }
    // Some mobile dashboards have no inner scroller; wheel events must still
    // move the page while the cursor is over their normally flowing content.
    if (!panels.length) panels.push(await workspace.elementHandle());
    let checked = 0;
    for (const panel of panels) {
      for (const direction of [1, -1]) {
        await panel.evaluate((node, direction) => {
          node.scrollTop = direction > 0 ? node.scrollHeight : 0;
          const bounds = node.getBoundingClientRect();
          window.scrollBy(0, bounds.top + Math.min(bounds.height / 2, 160) - innerHeight / 2);
        }, direction);
        const bounds = await panel.boundingBox();
        await page.mouse.move(bounds.x + Math.min(bounds.width / 2, 100), bounds.y + Math.min(bounds.height / 2, 160));
        const before = await page.evaluate(() => window.scrollY);
        await page.mouse.wheel(0, direction * 320);
        await expect.poll(() => page.evaluate(() => window.scrollY), { message: `${role}: wheel must leave panel at its ${direction > 0 ? 'bottom' : 'top'} edge` })
          [direction > 0 ? 'toBeGreaterThan' : 'toBeLessThan'](before);
      }
      checked++;
    }
    expect(checked).toBeGreaterThan(0);
  });
}
