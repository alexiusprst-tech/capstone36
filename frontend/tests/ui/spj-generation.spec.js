import { test, expect } from '@playwright/test';
import { signIn } from './helpers/session';

function pdfFixture() {
  const stream = 'BT /F1 12 Tf 40 780 Td (Dokumen SPJ terisi) Tj ET';
  const objects = ['<</Type/Catalog/Pages 2 0 R>>', '<</Type/Pages/Kids [3 0 R]/Count 1>>', '<</Type/Page/Parent 2 0 R/MediaBox [0 0 595 842]/Resources<</Font<</F1 4 0 R>>>>/Contents 5 0 R>>', '<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>', `<</Length ${stream.length}>>\nstream\n${stream}\nendstream`];
  let pdf = '%PDF-1.4\n';
  const offsets = [0];
  objects.forEach((object, index) => { offsets.push(Buffer.byteLength(pdf)); pdf += `${index + 1} 0 obj\n${object}\nendobj\n`; });
  const xref = Buffer.byteLength(pdf);
  pdf += `xref\n0 6\n0000000000 65535 f \n${offsets.slice(1).map(offset => `${String(offset).padStart(10, '0')} 00000 n \n`).join('')}trailer\n<</Size 6/Root 1 0 R>>\nstartxref\n${xref}\n%%EOF`;
  return Buffer.from(pdf);
}

async function openSpj(page, { failSave = false, profile = null } = {}) {
  await signIn(page, 'ADMINISTRASI');
  const project = { project_id: 'SPJ-TEST', project_name: 'Pengadaan Workshop', customer_name: 'SMK Contoh',
    activity_name: 'Pengadaan Peralatan', funding_source: 'BOS', fiscal_year: '2026', project_value: 90000,
    items: [{ item_name: 'Gelas', quantity: 2, unit: 'Buah', price: 15000 }, { item_name: 'Piring', quantity: 3, unit: 'Buah', price: 20000 }] };
  if (profile) project.customer_profile = profile;
  const docs = ['perencanaan', 'bast_sekolah', 'bast_company', 'bukti_foto'].map((key, i) => ({
    doc_key: key, doc_order: i + 1, doc_name: key === 'perencanaan' ? 'Perencanaan' : key,
    doc_number: `${key}/2026`, doc_date: '07/11/2026', status: 'DRAFT', is_data_complete: true,
    form_data: { content: `Isi khusus ${key}` },
  }));
  const calls = [];
  await page.route('**/api/**', async route => {
    const request = route.request();
    const path = new URL(request.url()).pathname;
    const payload = request.method() !== 'GET' ? request.postDataJSON() : null;
    calls.push({ path, method: request.method(), payload });
    if (path.endsWith('/download')) {
      const format = new URL(request.url()).searchParams.get('format');
      const body = format === 'pdf' ? pdfFixture() : Buffer.from('PK-fixture-filled-word');
      return route.fulfill({ body, contentType: format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', headers: { 'Content-Disposition': `attachment; filename="hasil.${format}"` } });
    }
    if (request.method() === 'PUT' && path.includes('/documents/')) {
      if (failSave) return route.fulfill({ status: 422, json: { message: 'Tanggal dokumen tidak valid.', errors: { doc_date: ['Tanggal dokumen tidak valid.'] } } });
      const doc = docs.find(item => path.endsWith('/' + item.doc_key));
      Object.assign(doc, payload, { form_data: { ...doc.form_data, ...payload.form_data } });
      return route.fulfill({ json: { success: true, data: doc } });
    }
    if (request.method() === 'PUT') return route.fulfill({ json: { success: true, data: Object.assign(project, payload) } });
    if (path.endsWith('/generate')) return route.fulfill({ json: { success: true, data: {} } });
    if (path.endsWith('/documents')) return route.fulfill({ json: { success: true, project, data: docs } });
    if (path === '/api/spj/projects') return route.fulfill({ json: { success: true, data: [project] } });
    return route.fulfill({ json: { data: [] } });
  });
  await page.goto('/administration/spj');
  await expect(page.locator('[data-doc-key]')).toHaveCount(4);
  return calls;
}

test('SPJ saves current inputs before downloading Word and PDF', async ({ page }) => {
  const calls = await openSpj(page);
  await page.locator('[data-doc-key="perencanaan"]').getByRole('button', { name: 'Buka Form' }).click();
  await page.getByPlaceholder('Nama spesifikasi barang').first().fill('Gelas Kaca Terbaru');
  for (const format of ['Word', 'PDF']) {
    const downloadPromise = page.waitForEvent('download');
    await page.getByRole('button', { name: `Cetak Perencanaan ${format}`, exact: true }).click();
    const download = await downloadPromise;
    expect(download.suggestedFilename()).toBe(`hasil.${format === 'Word' ? 'docx' : 'pdf'}`);
    const generate = calls.filter(call => call.path.endsWith('/generate')).at(-1);
    expect(generate.payload.form_data.items[0].item_name).toBe('Gelas Kaca Terbaru');
    expect(generate.payload.format).toBe(format === 'Word' ? 'docx' : 'pdf');
    expect(calls.findIndex(call => call.method === 'PUT' && call.path.endsWith('/perencanaan'))).toBeLessThan(calls.indexOf(generate));
    await expect(page.getByRole('button', { name: `Cetak Perencanaan ${format}`, exact: true })).toBeEnabled();
  }
});

test('SPJ blocks generation when saving fails and displays the actual validation error', async ({ page }) => {
  const calls = await openSpj(page, { failSave: true });
  await page.locator('[data-doc-key="perencanaan"]').getByRole('button', { name: 'Buka Form' }).click();
  await page.getByRole('button', { name: 'Cetak Perencanaan Word', exact: true }).click();
  await expect(page.getByText('Tanggal dokumen tidak valid.', { exact: true })).toBeVisible();
  expect(calls.some(call => call.path.endsWith('/generate') || call.path.endsWith('/download'))).toBe(false);
});

test('BAST Company uses its own form and document key', async ({ page }) => {
  const calls = await openSpj(page);
  await page.locator('[data-doc-key="bast_company"]').getByRole('button', { name: 'Buka Form' }).click();
  await expect(page.locator('textarea')).toHaveValue('Isi khusus bast_company');
  await page.locator('textarea').fill('Serah terima oleh perusahaan');
  const downloadPromise = page.waitForEvent('download');
  await page.getByRole('button', { name: 'Cetak BAST Word', exact: true }).click();
  await downloadPromise;
  expect(calls.find(call => call.path.endsWith('/bast_company/generate')).payload.form_data.content).toBe('Serah terima oleh perusahaan');
  expect(calls.some(call => call.method === 'PUT' && call.path.endsWith('/bast_sekolah'))).toBe(false);
});

test('documentation photos are previewed and included in saved generation data', async ({ page }) => {
  const calls = await openSpj(page);
  await page.locator('[data-doc-key="bukti_foto"]').getByRole('button', { name: 'Buka Form' }).click();
  const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=', 'base64');
  await page.locator('input[type="file"]').first().setInputFiles({ name: 'foto.png', mimeType: 'image/png', buffer: png });
  await expect(page.locator('img[alt="Foto 1: Pemeriksaan Fisik Barang"]')).toBeVisible();
  const downloadPromise = page.waitForEvent('download');
  await page.getByRole('button', { name: 'Cetak Bukti Foto Word', exact: true }).click();
  await downloadPromise;
  expect(calls.find(call => call.path.endsWith('/bukti_foto/generate')).payload.form_data.photos[0].data_url).toMatch(/^data:image\/png;base64,/);
});

test('PDF preview displays the server file and its download uses the PDF endpoint', async ({ page }) => {
  const calls = await openSpj(page);
  await page.locator('[data-doc-key="perencanaan"]').getByRole('button', { name: 'PDF / Cetak', exact: true }).click();
  await expect(page.locator('iframe[title="PDF Perencanaan"]')).toHaveAttribute('src', /^blob:/);
  const downloadPromise = page.waitForEvent('download');
  await page.getByRole('button', { name: 'Unduh PDF', exact: true }).click();
  expect((await downloadPromise).suggestedFilename()).toBe('hasil.pdf');
  expect(calls.filter(call => call.path.endsWith('/download'))).toHaveLength(2);
  await page.getByRole('button', { name: 'Tutup', exact: true }).click();
  await expect(page.locator('iframe[title="PDF Perencanaan"]')).toHaveCount(0);
});

test('Administration edits the project school letterhead once and persists its contacts and logo', async ({ page }, testInfo) => {
  const calls = await openSpj(page, { profile: { orgFullName: 'SMK Negeri 1 Bogor', province: 'JAWA BARAT', letterheadAddress: 'Jalan Sekolah A', orgPhone: '0251-123456', email: 'admin@sekolah-a.test', postalCode: '16111' } });
  await page.getByRole('button', { name: 'Data Project', exact: true }).click();
  const header = page.getByRole('region', { name: 'Kop Surat Instansi', exact: true });
  await expect(header.getByLabel('Nama Resmi Sekolah / Instansi', { exact: true })).toHaveValue('SMK Negeri 1 Bogor');
  await expect(header.getByLabel('Provinsi', { exact: true })).toHaveValue('JAWA BARAT');
  await expect(header.getByLabel('Alamat Kop Surat', { exact: true })).toHaveValue('Jalan Sekolah A');
  await header.getByLabel('Telepon Instansi', { exact: true }).fill('0251-999999');
  await header.getByLabel('Nama Pemerintah / Yayasan', { exact: true }).fill('PEMERINTAH PROVINSI JAWA BARAT');
  await header.getByLabel('Dinas / Unit Organisasi', { exact: true }).fill('DINAS PENDIDIKAN');
  await header.getByLabel('Logo Instansi / Daerah', { exact: true }).setInputFiles({ name: 'logo.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=', 'base64') });
  await expect(header.getByAltText('Logo instansi untuk kop surat')).toBeVisible();
  await header.screenshot({ path: testInfo.outputPath('kop-surat.png') });
  await page.getByRole('button', { name: 'Simpan Data Project', exact: true }).click();
  await expect(page.getByRole('button', { name: 'Data Project', exact: true })).toBeVisible();
  const saved = calls.find(call => call.method === 'PUT' && call.path === '/api/spj/projects/SPJ-TEST').payload.metadata.customer_profile;
  expect(saved).toMatchObject({ orgFullName: 'SMK Negeri 1 Bogor', governmentName: 'PEMERINTAH PROVINSI JAWA BARAT', orgPhone: '0251-999999', email: 'admin@sekolah-a.test', postalCode: '16111' });
  expect(saved.logoDataUrl).toMatch(/^data:image\/png;base64,/);
  await page.getByRole('button', { name: 'Data Project', exact: true }).click();
  await expect(page.getByLabel('Telepon Instansi', { exact: true })).toHaveValue('0251-999999');
  await page.getByLabel('Customer / Klien Sekolah *', { exact: true }).fill('SMP Sekolah B');
  await page.getByLabel('Customer / Klien Sekolah *', { exact: true }).press('Tab');
  await expect(page.getByLabel('Nama Resmi Sekolah / Instansi', { exact: true })).toHaveValue('SMP Sekolah B');
  await expect(page.getByLabel('Telepon Instansi', { exact: true })).toHaveValue('');
  await expect(page.getByLabel('Surel / Email', { exact: true })).toHaveValue('');
  await expect(page.getByAltText('Logo instansi untuk kop surat')).toHaveCount(0);
});
