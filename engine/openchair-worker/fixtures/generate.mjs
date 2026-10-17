#!/usr/bin/env node
/**
 * @file fixtures/generate.mjs
 * Writes the SYNTHETIC fixture pages for the Booksy and Square adapters.
 *
 *   node fixtures/generate.mjs
 *
 * Deterministic (seeded PRNG), so a regenerate is a no-op diff unless this file
 * changed. Dates are written as {{D0}}…{{D13}} placeholders; the fixture reader
 * fills them with "today + n" in the business's timezone at read time.
 *
 * These pages were NOT captured from Booksy or Square — see README.md. They are
 * shaped after the platforms' public booking flows so every parser branch runs,
 * and the spike replaces them with real recordings.
 */

import { writeFileSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const H = 14;                                     // horizon the fixtures cover
const D = (n) => `{{D${n}}}`;

let seed = 20261017;
const rnd = () => ((seed = (seed * 48271) % 2147483647) / 2147483647);

const hhmm = (m) => `${String(Math.floor(m / 60)).padStart(2, '0')}:${String(m % 60).padStart(2, '0')}`;

/**
 * Open slot starts for one chair-day.
 * @param {number} s envelope start (minute of day)
 * @param {number} e envelope end
 * @param {number} slot slot length
 * @param {(m: number) => number} pOpen probability a slot is open
 */
function openSlots(s, e, slot, pOpen) {
  const out = [];
  for (let m = s; m < e; m += slot) if (rnd() < pOpen(m)) out.push(m);
  return out;
}

/**
 * The week's shape: one shut day and one short day per seven, by offset.
 * (Offsets, not weekdays — a fixture cannot know which weekday "today" is.)
 */
function dayHours(n) {
  if (n % 7 === 3) return null;                   // shut
  if (n % 7 === 2) return [600, 960];             // short day, 10:00–16:00
  return [540, 1140];                             // 09:00–19:00
}

// Staff names live ONLY on the page, where the real platforms put them. The
// adapter tests assert none of these strings reaches adapter output.
const NAMES = [
  'Marcus Bell', 'Andre Lopez', 'Tasha Greene', 'Devon Price', 'Luis Ortega', 'Kayla Moore',
  'Jasmine Ortiz', 'Ray Chen', 'Nina Patel', 'Omar Haddad', 'Chris Varga', 'Imani Brooks',
  'Sam Whitaker', 'Leo Marin',
];
let nameAt = 0;
const nextName = () => NAMES[nameAt++ % NAMES.length];

const files = {};
const index = {
  _comment: 'SYNTHETIC fixtures — see README.md. URL keys and bodies use {{Dn}} = today+n in the business timezone; the time-slot ranges assume a 14-day horizon.',
};

// ── Booksy ───────────────────────────────────────────────────────────────────
const BOOKSY_API = 'https://us.booksy.com/api/us/2/customer_api/businesses';

function booksyBusiness(id, staffIds, opts = {}) {
  const staffers = staffIds.map((sid) => ({
    id: sid, name: nextName(), position: 'Barber', photo: `https://example.invalid/p/${sid}.jpg`,
  }));
  return {
    business: {
      id: Number(id), name: opts.title, timezone: 'America/New_York',
      staffers,
      service_categories: [{
        name: 'Haircuts',
        services: [
          { id: 9001, name: 'Skin Fade', variants: [{ duration: 45, price: 40 }] },
          { id: 9002, name: 'Classic Cut', variants: [{ duration: 30, price: 32 }] },
          { id: 9003, name: 'Beard Trim', variants: [{ duration: 20, price: 18 }] },
          { id: 9004, name: 'Cut + Beard', variants: [{ duration: 60, price: 55 }] },
        ],
      }],
    },
    _staffers_by_name: undefined,
  };
}

function booksySlots(staffIds, pOpen, { days = H, firstOpenDay = 0, leakName = false } = {}) {
  const working_hours = [];
  const time_slots = [];
  for (let n = 0; n < days; n++) {
    const hrs = dayHours(n);
    if (!hrs) continue;
    for (const sid of staffIds) {
      working_hours.push({ date: D(n), staffer_id: sid, start: hhmm(hrs[0]), end: hhmm(hrs[1]) });
      const open = n < firstOpenDay ? [] : openSlots(hrs[0], hrs[1], 30, (m) => pOpen(n, m));
      const row = { date: D(n), staffer_id: sid, slots: open.map(hhmm) };
      // Some platforms put the stylist's name next to the slot list. The
      // adapter must read the id and ignore this field.
      if (leakName) row.staffer_name = NAMES[staffIds.indexOf(sid) % NAMES.length];
      time_slots.push(row);
    }
  }
  return { slot_length: 30, start_date: D(0), end_date: D(days - 1), working_hours, time_slots };
}

function addBooksy(id, slug, staffIds, pOpen, opts = {}) {
  const booking = `https://booksy.com/en-us/${id}_${slug}_barber-shop_134763_tampa`;
  const bizFile = `booksy/${id}-business.json`;
  const slotsFile = `booksy/${id}-time-slots.json`;
  files[bizFile] = opts.businessBody ?? booksyBusiness(id, staffIds, opts);
  index[`${BOOKSY_API}/${id}`] = { file: bizFile, status: opts.businessStatus ?? 200, contentType: 'application/json' };
  if (opts.slotsBody !== null) {
    files[slotsFile] = opts.slotsBody ?? booksySlots(staffIds, pOpen, opts);
    index[`${BOOKSY_API}/${id}/appointments/time_slots?start_date=${D(0)}&end_date=${D(H - 1)}`] =
      { file: slotsFile, status: 200, contentType: 'application/json' };
  }
  return booking;
}

const urls = { booksy: {}, square_appointments: {} };
const afternoon = (base, pm) => (_n, m) => (m >= 780 && m < 1020 ? pm : base);

urls.booksy.hungry = addBooksy('100001', 'fade-lab', ['st_51001', 'st_51002', 'st_51003', 'st_51004', 'st_51005'],
  (n, m) => (n % 7 === 0 || n % 7 === 1 ? 0.78 : afternoon(0.5, 0.8)(n, m)),
  { title: 'Fade Lab (synthetic)', leakName: true });
urls.booksy.slammed = addBooksy('100002', 'bayshore-cuts', ['st_52001', 'st_52002', 'st_52003', 'st_52004'],
  () => 0.12, { title: 'Bayshore Cuts (synthetic)', firstOpenDay: 9 });
urls.booksy.steady = addBooksy('100003', 'tampa-heights-trim', ['st_53001', 'st_53002', 'st_53003'],
  afternoon(0.22, 0.42), { title: 'Tampa Heights Trim (synthetic)' });

// Negative cases.
urls.booksy.blocked = addBooksy('100090', 'blocked-shop', ['st_59001'], () => 0.5,
  { title: 'Blocked', businessBody: { error: 'forbidden' }, businessStatus: 403, slotsBody: null });
urls.booksy.notFound = 'https://booksy.com/en-us/100091_gone-shop_barber-shop_134763_tampa';   // deliberately absent
urls.booksy.captcha = (() => {
  const booking = `https://booksy.com/en-us/100092_captcha-shop_barber-shop_134763_tampa`;
  files['booksy/100092-captcha.html'] = '<!doctype html><html><body><div class="g-recaptcha" data-sitekey="x"></div>Please verify you are human.</body></html>';
  index[`${BOOKSY_API}/100092`] = { file: 'booksy/100092-captcha.html', status: 200, contentType: 'text/html' };
  return booking;
})();
urls.booksy.stale = addBooksy('100093', 'stale-shop', ['st_59301', 'st_59302'], () => 0.4, {
  title: 'Stale selectors',
  // The slots document renamed its arrays: the fetch succeeds, nothing parses.
  slotsBody: { slot_length: 30, start_date: D(0), end_date: D(H - 1), hours_v2: [], slots_v2: [] },
});
urls.booksy.leak = addBooksy('100094', 'leaky-shop', ['Marcus Bell', 'st_59402'], () => 0.4, {
  title: 'Leaky ids',
  // A page that put the stylist's NAME where the id belongs.
  businessBody: {
    business: {
      id: 100094, timezone: 'America/New_York',
      staffers: [{ id: 'Marcus Bell', name: 'Marcus Bell' }, { id: 'st_59402', name: 'Andre Lopez' }],
      service_categories: [],
    },
  },
});
urls.booksy.partial = addBooksy('100095', 'short-horizon', ['st_59501', 'st_59502'], () => 0.35, {
  title: 'Short horizon',
  slotsBody: (() => { const s = booksySlots(['st_59501', 'st_59502'], () => 0.35, { days: 7 }); return s; })(),
});

// ── Square Appointments ──────────────────────────────────────────────────────
const SQ = 'https://book.squareup.com/appointments';

function squarePage(team, title) {
  const state = {
    merchant: { token: 'synthetic', timezone: 'America/New_York', name: title },
    location: { id: 'synthetic' },
    team_members: team.map((id) => ({ id, display_name: nextName(), avatar: null })),
    services: [
      { id: 'SV1', name: 'Signature Cut', duration_minutes: 45, price: { amount: 4500, currency: 'USD' } },
      { id: 'SV2', name: 'Buzz Cut', duration_minutes: 30, price: { amount: 2800, currency: 'USD' } },
      { id: 'SV3', name: 'Hot Towel Shave', duration_minutes: 30, price: { amount: 3500, currency: 'USD' } },
    ],
  };
  return `<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>${title} — Book online</title></head>
<body><div id="app">Loading…</div>
<script>window.__BOOKING_STATE__ = ${JSON.stringify(state)};</script>
</body></html>
`;
}

function squareAvailability(team, pOpen, { firstOpenDay = 0, days = H } = {}) {
  const business_hours = [];
  const availabilities = [];
  for (let n = 0; n < days; n++) {
    const hrs = dayHours(n);
    if (!hrs) continue;
    for (const id of team) {
      business_hours.push({ date: D(n), team_member_id: id, opens: hhmm(hrs[0]), closes: hhmm(hrs[1]) });
      if (n < firstOpenDay) continue;
      for (const m of openSlots(hrs[0], hrs[1], 15, (mm) => pOpen(n, mm))) {
        availabilities.push({ start_at: `${D(n)}T${hhmm(m)}:00-04:00`, team_member_id: id, duration_minutes: 15 });
      }
    }
  }
  return { slot_minutes: 15, range: { start_date: D(0), end_date: D(days - 1) }, business_hours, availabilities };
}

function addSquare(merchant, location, team, pOpen, opts = {}) {
  const root = `${SQ}/${merchant}/location/${location}`;
  const pageFile = `square_appointments/${merchant}-services.html`;
  const avFile = `square_appointments/${merchant}-availability.json`;
  files[pageFile] = opts.pageBody ?? squarePage(team, opts.title);
  index[`${root}/services`] = { file: pageFile, status: opts.pageStatus ?? 200, contentType: 'text/html' };
  if (opts.avBody !== null) {
    files[avFile] = opts.avBody ?? squareAvailability(team, pOpen, opts);
    index[`${root}/availability?start_date=${D(0)}&end_date=${D(H - 1)}`] = { file: avFile, status: 200, contentType: 'application/json' };
  }
  return `${root}/services`;
}

urls.square_appointments.hungry = addSquare('sqhungry01', 'LT0001', ['TMa1', 'TMa2', 'TMa3', 'TMa4'],
  afternoon(0.55, 0.85), { title: 'Ybor Clippers (synthetic)' });
urls.square_appointments.slammed = addSquare('sqslammed02', 'LT0002', ['TMb1', 'TMb2', 'TMb3'],
  () => 0.1, { title: 'Hyde Park Barber (synthetic)', firstOpenDay: 12 });
urls.square_appointments.steady = addSquare('sqsteady03', 'LT0003', ['TMc1', 'TMc2', 'TMc3', 'TMc4', 'TMc5'],
  afternoon(0.2, 0.4), { title: 'Riverside Fades (synthetic)' });

urls.square_appointments.blocked = addSquare('sqblocked09', 'LT0009', ['TMz1'], () => 0.5,
  { title: 'Blocked', pageBody: 'Access denied', pageStatus: 403, avBody: null });
urls.square_appointments.captcha = addSquare('sqcaptcha08', 'LT0008', ['TMy1'], () => 0.5,
  { title: 'Captcha', pageBody: '<html><body><div id="px-captcha"></div></body></html>', avBody: null });
urls.square_appointments.stale = addSquare('sqstale07', 'LT0007', ['TMx1'], () => 0.5,
  { title: 'Stale', pageBody: '<!doctype html><html><body><div id="app"></div><script>window.__NEXT_DATA__ = {};</script></body></html>', avBody: null });
urls.square_appointments.leak = addSquare('sqleak06', 'LT0006', ['Jasmine Ortiz', 'TMw2'], () => 0.5, { title: 'Leaky ids' });
urls.square_appointments.notFound = `${SQ}/sqgone05/location/LT0005/services`;       // deliberately absent

// ── Write ────────────────────────────────────────────────────────────────────
for (const [rel, body] of Object.entries(files)) {
  const path = join(HERE, rel);
  mkdirSync(dirname(path), { recursive: true });
  writeFileSync(path, typeof body === 'string' ? body : `${JSON.stringify(body, (k, v) => (k === '_staffers_by_name' ? undefined : v), 2)}\n`);
}
writeFileSync(join(HERE, 'index.json'), `${JSON.stringify(index, null, 2)}\n`);
writeFileSync(join(HERE, 'catalog.json'), `${JSON.stringify({
  _comment: 'Booking URLs per case, for tests and for seeding oc_business in the end-to-end run. SYNTHETIC.',
  timezone: 'America/New_York',
  horizonDays: H,
  staffNames: NAMES,
  urls,
}, null, 2)}\n`);
process.stdout.write(`wrote ${Object.keys(files).length} fixture files and index.json\n`);
