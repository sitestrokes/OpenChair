/**
 * @file src/scan/payload.js
 * Server-side validation of the §3.2 completion payload (docs/70-radar-build.md).
 *
 * The worker validates its adapter's output before it reports (registry/
 * contract.js). This file does it AGAIN, on the other side of the wire, and does
 * not trust that the first check happened: the worker runs on a separate,
 * cheaper, more exposed VM, and a completion is the one call that turns into a
 * number a consultant reads aloud in someone's shop. A worker that has been
 * compromised, or simply has a bug, must not be able to write a name into a
 * snapshot or a nonsense grid into the Meter.
 *
 * Fails closed: anything not recognisably the contract is refused with a code.
 */

/** Statuses a scan may end in. Mirrors the oc_scan_run.status enum. */
export const STATUSES = new Set(['ok', 'partial', 'blocked', 'captcha', 'not_found', 'timeout', 'error']);

/** Capability classes (worker registry/contract.js CAPABILITY). */
export const CAPABILITIES = new Set([
  'C1_SLOT_GRID', 'C2_CLASS_CAPACITY', 'C3_ARRIVAL_WINDOW', 'C4_RESERVATION', 'C5_NEXT_AVAILABLE',
]);

/**
 * Keys that name a person. None may appear anywhere in a snapshot or the staff
 * list (50-risk §A4.2): oc_staff.display_name is the only column allowed to hold
 * a name, it is opt-in, and it is never fed from a scan.
 */
const NAME_KEYS = new Set([
  'name', 'staffname', 'staff_name', 'displayname', 'display_name', 'fullname', 'full_name',
  'firstname', 'first_name', 'lastname', 'last_name', 'employee', 'employeename', 'stylist', 'barber',
]);

/** Hard caps — larger than any real 14-day grid, small enough to bound work. */
export const CAPS = {
  days: 31,
  unitsPerDay: 64,
  openPerUnit: 24 * 12,     // 5-minute slots, all day
  staff: 64,
  services: 200,
  byDay: 31,
};

/**
 * A payload problem, carrying a stable code for the response.
 */
export class PayloadError extends Error {
  /** @param {string} code @param {string} message */
  constructor(code, message) {
    super(message);
    this.code = code;
  }
}

const isInt = (v) => Number.isInteger(v);
const isNonNegInt = (v) => Number.isInteger(v) && v >= 0;
const isIsoDate = (v) => typeof v === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(v) && !Number.isNaN(Date.parse(`${v}T00:00:00Z`));
const isSha1ish = (v) => typeof v === 'string' && /^[A-Za-z0-9_:.-]{1,120}$/.test(v);

/**
 * Walk a value and report the first key that names a person.
 *
 * @param {unknown} v
 * @param {string} path
 * @returns {string|null}
 */
export function findNameKey(v, path = '') {
  if (Array.isArray(v)) {
    for (let i = 0; i < v.length; i++) {
      const hit = findNameKey(v[i], `${path}[${i}]`);
      if (hit) return hit;
    }
    return null;
  }
  if (v && typeof v === 'object') {
    for (const [k, child] of Object.entries(v)) {
      if (NAME_KEYS.has(k.toLowerCase())) return `${path}.${k}`;
      const hit = findNameKey(child, `${path}.${k}`);
      if (hit) return hit;
    }
  }
  return null;
}

/**
 * Validate the envelope fields every completion carries.
 *
 * @param {any} p
 * @returns {void}
 * @throws {PayloadError}
 */
export function validateEnvelope(p) {
  if (!p || typeof p !== 'object') throw new PayloadError('NOT_AN_OBJECT', 'Body must be a JSON object.');
  for (const f of ['queueId', 'businessId', 'platformId']) {
    if (!isInt(p[f]) || p[f] <= 0) throw new PayloadError('BAD_FIELD', `${f} must be a positive integer.`);
  }
  if (!isIsoDate(p.observedOn)) throw new PayloadError('BAD_FIELD', 'observedOn must be YYYY-MM-DD.');
  if (!STATUSES.has(p.status)) throw new PayloadError('BAD_STATUS', `Unknown status '${String(p.status).slice(0, 20)}'.`);
  if (p.tier !== undefined && !isInt(p.tier)) throw new PayloadError('BAD_FIELD', 'tier must be an integer.');
  for (const f of ['bytes', 'costMicros', 'durationMs']) {
    if (p[f] !== undefined && p[f] !== null && !isNonNegInt(p[f])) {
      throw new PayloadError('BAD_FIELD', `${f} must be a non-negative integer.`);
    }
  }
  if (p.adapterVersion !== undefined && p.adapterVersion !== null
      && (typeof p.adapterVersion !== 'string' || p.adapterVersion.length > 20)) {
    throw new PayloadError('BAD_FIELD', 'adapterVersion must be a string of at most 20 characters.');
  }
}

/**
 * Validate and normalise the data half of an ok/partial completion.
 *
 * Returns a cleaned copy: only the fields the pipeline reads survive, so an
 * extra key a worker invents can never be stored by accident.
 *
 * @param {any} p
 * @returns {{grid: object, snapshot: object|null, staff: Array<{staffHash:string, staffKey:string}>, services: Array<{key:string,name:string,durationMin:number|null,priceCents:number|null}>, capability: string|null, slotMinutes: number, horizonDays: number}}
 * @throws {PayloadError}
 */
export function validateData(p) {
  if (p.capability !== undefined && p.capability !== null && !CAPABILITIES.has(p.capability)) {
    throw new PayloadError('BAD_CAPABILITY', 'Unknown capability.');
  }

  // ── Names never cross the wire (§3.1). Services are exempt: a service is
  //    "Skin fade", not a person — but its entries are checked below.
  const nameHit = findNameKey(p.snapshot, 'snapshot') ?? findNameKey(p.staff, 'staff') ?? findNameKey(p.grid, 'grid');
  if (nameHit) {
    throw new PayloadError('STAFF_NAME_LEAK', `Payload carries a personal-name key at ${nameHit}. Snapshots hold hashes only.`);
  }

  // ── grid (normalised) — the Meter's input.
  const g = p.grid;
  if (!g || typeof g !== 'object') throw new PayloadError('NO_GRID', 'ok/partial completion must carry grid.');
  for (const f of ['openMinutes', 'envelopeMinutes']) {
    if (!(Number.isFinite(g[f]) && g[f] >= 0)) throw new PayloadError('BAD_GRID', `grid.${f} must be a non-negative number.`);
  }
  if (g.openMinutes > g.envelopeMinutes) {
    throw new PayloadError('BAD_GRID', 'grid.openMinutes exceeds grid.envelopeMinutes — the parse is wrong.');
  }
  if (g.daysToNext !== null && g.daysToNext !== undefined && !(isNonNegInt(g.daysToNext) && g.daysToNext <= 400)) {
    throw new PayloadError('BAD_GRID', 'grid.daysToNext is not a plausible lead time.');
  }
  const byDay = {};
  const days = Object.entries(g.byDay ?? {});
  if (days.length > CAPS.byDay) throw new PayloadError('TOO_LARGE', 'grid.byDay has too many days.');
  for (const [day, cell] of days) {
    if (!isIsoDate(day)) throw new PayloadError('BAD_GRID', `grid.byDay key '${day.slice(0, 12)}' is not a date.`);
    const open = Number(cell?.open), env = Number(cell?.envelope);
    if (!(Number.isFinite(open) && open >= 0 && Number.isFinite(env) && env >= 0 && open <= env)) {
      throw new PayloadError('BAD_GRID', `grid.byDay['${day}'] is invalid.`);
    }
    byDay[day] = { open, envelope: env };
  }
  const grid = {
    capability: p.capability ?? g.capability ?? null,
    openMinutes: Number(g.openMinutes),
    envelopeMinutes: Number(g.envelopeMinutes),
    vacancyBp: isNonNegInt(g.vacancyBp) ? Math.min(10000, g.vacancyBp) : 0,
    staffCount: isNonNegInt(g.staffCount) ? g.staffCount : 0,
    dayCount: Object.keys(byDay).length,
    daysToNext: isNonNegInt(g.daysToNext) ? g.daysToNext : null,
    byDay,
  };

  const slotMinutes = isInt(p.slotMinutes) && p.slotMinutes >= 5 && p.slotMinutes <= 240 ? p.slotMinutes : 30;
  const horizonDays = isInt(p.horizonDays) && p.horizonDays >= 1 && p.horizonDays <= 31 ? p.horizonDays : 7;

  // ── staff — hash + the platform's pseudonymous resource id.
  const staffIn = Array.isArray(p.staff) ? p.staff : [];
  if (staffIn.length > CAPS.staff) throw new PayloadError('TOO_LARGE', 'Too many staff entries.');
  const staff = [];
  const seen = new Set();
  for (const [i, s] of staffIn.entries()) {
    if (!isSha1ish(s?.staffHash)) throw new PayloadError('BAD_STAFF', `staff[${i}].staffHash is invalid.`);
    const key = s.staffKey === undefined || s.staffKey === null || s.staffKey === '' ? s.staffHash : String(s.staffKey);
    if (key.length > 120 || /\s/.test(key)) {
      // A resource id never has spaces; a person's name usually does.
      throw new PayloadError('BAD_STAFF', `staff[${i}].staffKey does not look like a resource id.`);
    }
    if (seen.has(s.staffHash)) continue;
    seen.add(s.staffHash);
    staff.push({ staffHash: s.staffHash, staffKey: key });
  }

  // ── snapshot — the per-chair hourly grid (§3.3).
  let snapshot = null;
  if (p.snapshot !== undefined && p.snapshot !== null) {
    const sn = p.snapshot;
    if (typeof sn !== 'object' || !Array.isArray(sn.days)) throw new PayloadError('BAD_SNAPSHOT', 'snapshot.days must be an array.');
    if (sn.days.length > CAPS.days) throw new PayloadError('TOO_LARGE', 'snapshot has too many days.');
    const outDays = [];
    for (const [di, d] of sn.days.entries()) {
      if (!isIsoDate(d?.date)) throw new PayloadError('BAD_SNAPSHOT', `snapshot.days[${di}].date is invalid.`);
      const units = Array.isArray(d.units) ? d.units : [];
      if (units.length > CAPS.unitsPerDay) throw new PayloadError('TOO_LARGE', `snapshot.days[${di}] has too many units.`);
      const outUnits = [];
      for (const [ui, u] of units.entries()) {
        const where = `snapshot.days[${di}].units[${ui}]`;
        if (!isSha1ish(u?.staffHash)) throw new PayloadError('BAD_SNAPSHOT', `${where}.staffHash is invalid.`);
        const env = u.envelope;
        if (!Array.isArray(env) || env.length !== 2 || !isNonNegInt(env[0]) || !isNonNegInt(env[1])
            || env[1] <= env[0] || env[1] > 24 * 60) {
          throw new PayloadError('BAD_SNAPSHOT', `${where}.envelope must be [start, end] minutes with end > start.`);
        }
        const open = Array.isArray(u.open) ? u.open : [];
        if (open.length > CAPS.openPerUnit) throw new PayloadError('TOO_LARGE', `${where}.open is too long.`);
        for (const m of open) {
          if (!isNonNegInt(m) || m < env[0] || m >= env[1]) {
            throw new PayloadError('BAD_SNAPSHOT', `${where}.open has a start outside the envelope.`);
          }
        }
        outUnits.push({ staffHash: u.staffHash, envelope: [env[0], env[1]], open: [...new Set(open)].sort((a, b) => a - b) });
        if (!seen.has(u.staffHash)) {
          // A unit whose staff entry the worker forgot: keep it, keyed by its own hash.
          seen.add(u.staffHash);
          staff.push({ staffHash: u.staffHash, staffKey: u.staffHash });
        }
      }
      outDays.push({ date: d.date, units: outUnits });
    }
    if (staff.length > CAPS.staff) throw new PayloadError('TOO_LARGE', 'Too many distinct staff in snapshot.');
    outDays.sort((a, b) => a.date.localeCompare(b.date));
    snapshot = {
      v: 1,
      slotMinutes: isInt(sn.slotMinutes) && sn.slotMinutes >= 5 && sn.slotMinutes <= 240 ? sn.slotMinutes : slotMinutes,
      days: outDays,
    };
  }

  // ── services — the menu. A service name is fine; a price must be plausible.
  const svcIn = Array.isArray(p.services) ? p.services : [];
  if (svcIn.length > CAPS.services) throw new PayloadError('TOO_LARGE', 'Too many services.');
  const services = [];
  for (const [i, s] of svcIn.entries()) {
    const name = typeof s?.name === 'string' ? s.name.trim().slice(0, 191) : '';
    if (!name) throw new PayloadError('BAD_SERVICE', `services[${i}].name is required.`);
    const price = s.priceCents === undefined || s.priceCents === null ? null : s.priceCents;
    if (price !== null && !(isNonNegInt(price) && price <= 1_000_000)) {
      throw new PayloadError('BAD_SERVICE', `services[${i}].priceCents is implausible.`);
    }
    const dur = s.durationMin === undefined || s.durationMin === null ? null : s.durationMin;
    if (dur !== null && !(isInt(dur) && dur >= 5 && dur <= 24 * 60)) {
      throw new PayloadError('BAD_SERVICE', `services[${i}].durationMin is implausible.`);
    }
    services.push({ key: typeof s.key === 'string' ? s.key.slice(0, 120) : '', name, durationMin: dur, priceCents: price });
  }

  // The shop's published name (a business, not a person); cleaned again where it is written.
  const businessName = typeof p.businessName === 'string' ? p.businessName.slice(0, 200) : null;
  // The shop's published location (a business address, not a person's): two
  // finite in-range numbers or nothing.
  let businessLocation = null;
  const bl = p.businessLocation;
  if (bl && typeof bl === 'object') {
    const lat = Number(bl.lat), lng = Number(bl.lng);
    if (Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180 && !(lat === 0 && lng === 0)) {
      businessLocation = {
        lat, lng,
        address: typeof bl.address === 'string' ? bl.address.replace(/[\u0000-\u001f\u007f]/g, '').trim().slice(0, 255) || null : null,
      };
    }
  }
  return { grid, snapshot, staff, services, businessName, businessLocation, capability: grid.capability, slotMinutes, horizonDays };
}

export default { STATUSES, CAPABILITIES, CAPS, PayloadError, findNameKey, validateEnvelope, validateData };
