import { test } from 'node:test';
import assert from 'node:assert/strict';
import { customerIdentitySync } from '../../src/services/customerIdentity.js';

test('inferred identity and letterhead follow each character until typing is complete', () => {
  const form = { value: { name: '', address: '' } };
  const sync = customerIdentitySync(form);
  for (const letter of 'Sekolah Pengujian') { form.value.name += letter; sync.nameChanged(); }
  for (const letter of 'Jalan Pengujian 19') { form.value.address += letter; sync.addressChanged(); }
  assert.equal(form.value.orgFullName, 'Sekolah Pengujian');
  assert.equal(form.value.orgShortName, '');
  assert.equal(form.value.letterheadAddress, 'Jalan Pengujian 19');
});

test('explicitly edited official identity remains intact when display name and address change', () => {
  const form = { value: { name: 'Alias', address: 'Alamat' } };
  const sync = customerIdentitySync(form);
  sync.nameChanged(); sync.addressChanged();
  form.value.orgFullName = 'NAMA RESMI TETAP'; sync.edit('orgFullName');
  form.value.orgShortName = 'NRT'; sync.edit('orgShortName');
  form.value.letterheadAddress = 'Alamat resmi tetap'; sync.edit('letterheadAddress');
  form.value.name = 'Alias baru'; form.value.address = 'Alamat baru';
  sync.nameChanged(); sync.addressChanged();
  assert.equal(form.value.orgFullName, 'NAMA RESMI TETAP');
  assert.equal(form.value.orgShortName, 'NRT');
  assert.equal(form.value.letterheadAddress, 'Alamat resmi tetap');
});

test('loading an existing customer preserves saved official fields', () => {
  const form = { value: { name: 'Alias', orgFullName: 'Nama Resmi', orgShortName: 'NR', letterheadAddress: 'Alamat Resmi' } };
  const sync = customerIdentitySync(form, value => value.toUpperCase());
  sync.reset(form.value);
  form.value.name = 'Alias lain'; form.value.address = 'Alamat lain';
  sync.nameChanged(); sync.addressChanged();
  assert.equal(form.value.orgFullName, 'Nama Resmi');
  assert.equal(form.value.orgShortName, 'NR');
  assert.equal(form.value.letterheadAddress, 'Alamat Resmi');
});

test('reopening a blank form resets automatic inference after a manual edit', () => {
  const form = { value: { name: 'Lama', orgFullName: 'Manual' } };
  const sync = customerIdentitySync(form, value => value.toUpperCase());
  sync.edit('orgFullName');
  form.value = { name: 'Baru' }; sync.reset(); sync.nameChanged();
  assert.equal(form.value.orgFullName, 'BARU');
  assert.equal(form.value.orgShortName, 'Baru');
});
