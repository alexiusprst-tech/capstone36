import test from 'node:test';
import assert from 'node:assert/strict';
import { customerProfile } from '../../src/services/customerProfile.js';

test('an explicit official institution name is preserved as entered', () => {
  assert.equal(customerProfile({ name: 'SMK 1', orgFullName: 'SMK Negeri 1 Bogor' }).orgFullName, 'SMK Negeri 1 Bogor');
});
test('a missing official name can be inferred without discarding contact data', () => {
  const profile = customerProfile({ name: 'SMKN 1 Bogor', email: 'admin@example.test' });
  assert.equal(profile.orgFullName, 'SEKOLAH MENENGAH KEJURUAN NEGERI 1 Bogor');
  assert.equal(profile.email, 'admin@example.test');
});
test('the profile adapter does not mutate the saved source object', () => {
  const source = { name: ' Sekolah A ', orgFullName: ' Nama Resmi A ' };
  customerProfile(source);
  assert.deepEqual(source, { name: ' Sekolah A ', orgFullName: ' Nama Resmi A ' });
});
