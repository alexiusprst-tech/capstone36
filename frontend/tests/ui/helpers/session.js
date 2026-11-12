import { expect } from '@playwright/test';

export async function signIn(page, role, email = `${role?.toLowerCase()}@example.test`) {
  if (!role) return;
  const response = await page.request.get('/login');
  expect(response.ok()).toBeTruthy();
  const cookie = (await page.context().cookies()).find(cookie => cookie.name === 'XSRF-TOKEN');
  const login = await page.request.post('/login', {
    headers: { 'X-XSRF-TOKEN': decodeURIComponent(cookie.value), Accept: 'application/json' },
    data: { email, password: 'ui-test-password' },
  });
  expect(login.ok()).toBeTruthy();
}
