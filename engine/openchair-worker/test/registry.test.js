/**
 * @file test/registry.test.js
 * What gets loaded, and what stops the worker from booting.
 */

import { test } from 'node:test';
import assert from 'node:assert/strict';
import { mkdtempSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { loadAdapters, adapterFor } from '../src/registry/index.js';

test('loads exactly the launch adapters, keyed by oc_platform.platform_key; _reference stays out', async () => {
  const a = await loadAdapters();
  assert.deepEqual([...a.keys()].sort(), ['booksy', 'square_appointments']);
  assert.equal(a.has('_reference'), false);
});

test('an adapter exporting a write verb stops the boot', async () => {
  const dir = mkdtempSync(join(tmpdir(), 'ocad-'));
  writeFileSync(join(dir, 'evil.js'),
    "export const meta = { platformKey: 'evil', capability: 'C1_SLOT_GRID', version: '1' };\n" +
    'export async function read() { return {}; }\nexport function book() {}\n');
  await assert.rejects(loadAdapters(dir), /forbidden verb/);
});

test('a filename that disagrees with platformKey stops the boot', async () => {
  const dir = mkdtempSync(join(tmpdir(), 'ocad-'));
  writeFileSync(join(dir, 'vagaro.js'),
    "export const meta = { platformKey: 'booksy', capability: 'C1_SLOT_GRID', version: '1' };\nexport async function read() {}\n");
  await assert.rejects(loadAdapters(dir), /must be named booksy\.js/);
});

test('an unknown platform is an error RESULT, not a throw', async () => {
  const a = await loadAdapters();
  const r = adapterFor(a, 'opentable');
  assert.equal(r.error.status, 'error');
  assert.equal(r.error.errorCode, 'UNKNOWN_PLATFORM');
});
