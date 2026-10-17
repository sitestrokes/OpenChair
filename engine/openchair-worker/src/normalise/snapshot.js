/**
 * @file src/normalise/snapshot.js
 * Adapter output → the stored snapshot (docs/70-radar-build.md §3.2/§3.3).
 *
 * normalise() collapses a read into totals for the Meter; this keeps the SHAPE —
 * which chair was open at which hour — because that is what every Chair Week
 * card draws. Without it the cards fall back to a derived grid and say so.
 *
 * What goes in: staff HASHES, envelopes and open-slot start minutes. What never
 * goes in: a name, a resource id, a service, a price. The service rewrites each
 * staffHash to the schema hash before storing, and the staffKey travels
 * separately in `staff` so the blob itself holds nothing re-identifiable.
 */

/**
 * @typedef {Object} Snapshot
 * @property {1} v
 * @property {number} slotMinutes
 * @property {Array<{date: string, units: Array<{staffHash: string, envelope: [number, number], open: number[]}>}>} days
 */

/**
 * Build the §3.2 `snapshot` and `staff` blocks from a validated result.
 *
 * Units without an hour-level envelope (an adapter that only reports totals)
 * cannot be drawn and are left out; if none can be drawn the snapshot is null
 * and the service stores no blob — the card then says its grid is derived,
 * rather than drawing one we never saw.
 *
 * @param {import('../registry/contract.js').AdapterResult} result
 * @returns {{snapshot: Snapshot|null, staff: Array<{staffHash: string, staffKey: string|null}>}}
 */
export function buildSnapshot(result) {
  /** @type {Map<string, Array<{staffHash: string, envelope: [number, number], open: number[]}>>} */
  const byDate = new Map();
  /** @type {Map<string, string|null>} */
  const staff = new Map();

  for (const u of result.units ?? []) {
    if (!staff.has(u.staffHash)) {
      staff.set(u.staffHash, typeof u.staffKey === 'string' && u.staffKey ? u.staffKey : null);
    }
    if (!Number.isInteger(u.envelopeStart) || !Number.isInteger(u.envelopeEnd)) continue;
    if (!byDate.has(u.day)) byDate.set(u.day, []);
    byDate.get(u.day).push({
      staffHash: u.staffHash,
      envelope: [u.envelopeStart, u.envelopeEnd],
      open: [...new Set(Array.isArray(u.openStarts) ? u.openStarts : [])].sort((a, b) => a - b),
    });
  }

  const days = [...byDate.keys()].sort().map((date) => ({
    date,
    units: byDate.get(date).sort((a, b) => a.staffHash.localeCompare(b.staffHash)),
  }));

  return {
    snapshot: days.length ? { v: 1, slotMinutes: Number(result.slotMinutes) || 30, days } : null,
    staff: [...staff.entries()].map(([staffHash, staffKey]) => ({ staffHash, staffKey })),
  };
}

export default { buildSnapshot };
