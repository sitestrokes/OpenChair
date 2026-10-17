/**
 * @file test/validator.test.js
 * The §3.1 unit extension (docs/70-radar-build.md): the hour-level grid an
 * adapter may now report, and the new ways the validator refuses it.
 *
 * Each refusal is a way a Chair Week card could otherwise draw something we did
 * not see — an open chair before the shop opens, one chair counted twice, a
 * stylist's name riding along in a field nobody looked at.
 */

import { test, describe } from 'node:test';
import assert from 'node:assert/strict';
import { validateResult, CAPABILITY, NAME_KEYS } from '../src/registry/contract.js';

const unit = (over = {}) => ({
  staffHash: 'a1b2c3', staffKey: 'st_1', day: '2026-10-20',
  openMinutes: 60, envelopeMinutes: 600, envelopeStart: 540, envelopeEnd: 1140,
  openStarts: [540, 570], ...over,
});
const res = (units, over = {}) => ({
  status: 'ok', capability: CAPABILITY.SLOT_GRID, adapterVersion: '1.0.0', slotMinutes: 30, units, ...over,
});

describe('validator — §3.1 hour-level grid', () => {
  test('accepts a unit with an envelope and open starts', () => {
    assert.equal(validateResult(res([unit()])).ok, true);
  });

  test('still accepts a unit WITHOUT the optional grid (backwards compatible)', () => {
    assert.equal(validateResult(res([{ staffHash: 'x', day: '2026-10-20', openMinutes: 30, envelopeMinutes: 480 }])).ok, true);
  });

  test('REFUSES an open slot before the shop opens', () => {
    assert.equal(validateResult(res([unit({ openStarts: [500] })])).code, 'OPEN_OUTSIDE_ENVELOPE');
  });

  test('REFUSES an open slot at the closing minute (the envelope end is exclusive)', () => {
    assert.equal(validateResult(res([unit({ openStarts: [1140] })])).code, 'OPEN_OUTSIDE_ENVELOPE');
  });

  test('REFUSES an envelope that ends before it starts', () => {
    assert.equal(validateResult(res([unit({ envelopeStart: 900, envelopeEnd: 900, openStarts: [] })])).code, 'BAD_ENVELOPE_SPAN');
  });

  test('REFUSES fractional minutes', () => {
    assert.equal(validateResult(res([unit({ openMinutes: 30.5 })])).code, 'BAD_MINUTES');
    assert.equal(validateResult(res([unit({ openStarts: [540.5] })])).code, 'BAD_MINUTES');
    assert.equal(validateResult(res([unit({ envelopeStart: 540.25 })])).code, 'BAD_MINUTES');
  });

  test('REFUSES open starts with no envelope to place them in', () => {
    const u = unit();
    delete u.envelopeStart; delete u.envelopeEnd;
    assert.equal(validateResult(res([u])).code, 'BAD_OPEN_STARTS');
  });

  for (const k of NAME_KEYS) {
    test(`REFUSES a unit carrying '${k}'`, () => {
      assert.equal(validateResult(res([unit({ [k]: 'Marcus Bell' })])).code, 'STAFF_NAME_LEAK');
    });
  }

  test('REFUSES the same chair twice on one day', () => {
    assert.equal(validateResult(res([unit(), unit()])).code, 'DUPLICATE_UNIT');
  });

  test('allows the same chair on different days', () => {
    assert.equal(validateResult(res([unit(), unit({ day: '2026-10-21' })])).ok, true);
  });
});

describe('validator — service names', () => {
  const svc = (over = {}) => ({ key: 'cut', name: 'Skin Fade', durationMin: 45, priceCents: 4000, ...over });

  test('accepts a named service', () => {
    assert.equal(validateResult(res([unit()], { services: [svc()] })).ok, true);
  });
  test('REFUSES a service with no name', () => {
    assert.equal(validateResult(res([unit()], { services: [svc({ name: '' })] })).code, 'BAD_SERVICE_NAME');
    assert.equal(validateResult(res([unit()], { services: [svc({ name: undefined })] })).code, 'BAD_SERVICE_NAME');
  });
  test('REFUSES a description block grabbed as a name', () => {
    assert.equal(validateResult(res([unit()], { services: [svc({ name: 'x'.repeat(121) })] })).code, 'BAD_SERVICE_NAME');
  });
});
