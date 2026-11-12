import { test } from 'node:test';
import assert from 'node:assert/strict';
import { getVisibleActivityLogs } from '../../src/services/activityLogs.js';

const logs = [
  { id: 1, role: 'FINANCE', timestamp: '2026-11-06 10:00', details: 'Penerimaan kas' },
  { id: 2, role: 'MARKETING', timestamp: '2026-11-06 09:00' },
  { id: 3, role: 'WAREHOUSE', timestamp: '2026-11-06 11:00' },
  { id: 4, role: 'ADMINISTRASI', timestamp: '2026-11-06 08:00' },
  { id: 5, role: 'CLIENT', timestamp: '2026-11-06 07:00' },
  { id: 6, role: 'SUPPLIER', timestamp: '2026-11-06 06:00' },
  { id: 7, role: 'DIREKTUR', timestamp: '2026-11-06 12:00' },
];

test('staff can only see activity authored by their own division', () => {
  for (const role of ['MARKETING', 'ADMINISTRASI', 'WAREHOUSE', 'FINANCE']) {
    const visible = getVisibleActivityLogs(logs, role);
    assert.equal(visible.length, 1);
    assert.equal(visible[0].role, role);
  }
});

test('director retains access to the complete system audit including partner activities', () => {
  assert.deepEqual(getVisibleActivityLogs(logs, 'DIREKTUR').map(log => log.id), [7, 3, 1, 2, 4, 5, 6]);
});

test('external, missing and unknown roles have no internal dashboard activity access', () => {
  for (const role of ['CLIENT', 'SUPPLIER', 'ALL', 'ADMIN', '', undefined, null]) {
    assert.deepEqual(getVisibleActivityLogs(logs, role), []);
  }
});

test('scoping and chronological sorting do not reorder or modify the source logs', () => {
  const before = structuredClone(logs);
  getVisibleActivityLogs(logs, 'DIREKTUR');
  getVisibleActivityLogs(logs, 'FINANCE');
  assert.deepEqual(logs, before);
});

test('incomplete logs do not crash the dashboard or expose unscoped entries to staff', () => {
  assert.deepEqual(getVisibleActivityLogs(null, 'FINANCE'), []);
  const entries = [null, undefined, 'invalid', { id: 1 }, { id: 2, role: 'FINANCE', timestamp: 'invalid' }, { id: 3, role: 'FINANCE', timestamp: '2026-11-06 12:00' }];
  assert.deepEqual(getVisibleActivityLogs(entries, 'FINANCE').map(log => log.id), [3, 2]);
});

test('equal timestamps preserve newest-first insertion order', () => {
  const entries = [{ id: 3, role: 'FINANCE', timestamp: '2026-11-06 12:00' }, { id: 2, role: 'FINANCE', timestamp: '2026-11-06 12:00' }];
  assert.deepEqual(getVisibleActivityLogs(entries, 'FINANCE').map(log => log.id), [3, 2]);
});
