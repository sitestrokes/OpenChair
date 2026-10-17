/**
 * @file test/snapshot.test.js
 * The stored snapshot (docs/70 §3.2/§3.3): shape in, nothing personal out.
 */

import { test } from 'node:test';
import assert from 'node:assert/strict';
import { buildSnapshot } from '../src/normalise/snapshot.js';

const result = {
  status: 'ok', capability: 'C1_SLOT_GRID', adapterVersion: '1', slotMinutes: 30,
  units: [
    { staffHash: 'bb', staffKey: 'st_2', day: '2026-10-21', openMinutes: 30, envelopeMinutes: 600, envelopeStart: 540, envelopeEnd: 1140, openStarts: [600] },
    { staffHash: 'aa', staffKey: 'st_1', day: '2026-10-20', openMinutes: 60, envelopeMinutes: 600, envelopeStart: 540, envelopeEnd: 1140, openStarts: [570, 540, 540] },
    { staffHash: 'bb', staffKey: 'st_2', day: '2026-10-20', openMinutes: 0, envelopeMinutes: 600, envelopeStart: 540, envelopeEnd: 1140, openStarts: [] },
  ],
};

test('groups by date, sorted; units sorted by hash; open starts deduped and sorted', () => {
  const { snapshot } = buildSnapshot(result);
  assert.deepEqual(snapshot, {
    v: 1, slotMinutes: 30,
    days: [
      { date: '2026-10-20', units: [
        { staffHash: 'aa', envelope: [540, 1140], open: [540, 570] },
        { staffHash: 'bb', envelope: [540, 1140], open: [] },
      ] },
      { date: '2026-10-21', units: [{ staffHash: 'bb', envelope: [540, 1140], open: [600] }] },
    ],
  });
});

test('staff travels separately, once per chair; the blob holds no resource id', () => {
  const { snapshot, staff } = buildSnapshot(result);
  assert.deepEqual(staff, [{ staffHash: 'bb', staffKey: 'st_2' }, { staffHash: 'aa', staffKey: 'st_1' }]);
  assert.doesNotMatch(JSON.stringify(snapshot), /st_1|st_2/);
});

test('units without an envelope cannot be drawn: no snapshot rather than a guessed one', () => {
  const { snapshot, staff } = buildSnapshot({ ...result, units: [{ staffHash: 'aa', day: '2026-10-20', openMinutes: 30, envelopeMinutes: 480 }] });
  assert.equal(snapshot, null);
  assert.deepEqual(staff, [{ staffHash: 'aa', staffKey: null }]);
});
