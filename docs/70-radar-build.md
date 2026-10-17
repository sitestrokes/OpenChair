# 70 — Radar build: goal, contract and plan

**Status: built.** R1–R9 done; 422 automated checks green (integrity 99 · service 155 · worker 111 ·
egress 57). End-to-end run verified on fixtures (§7). **Decided with the founder (Oct 2026):** build *everything except the live crawl*. The whole
Radar pipeline is built and tested end to end against **fixture pages**; first adapters are
**Booksy and Square Appointments**. No request is made to a real booking platform. Plus the
product layers around it (team/agency tenancy, access-code gating) and a security pass.

---

## 1. Goal

> **A business URL goes in; a scored, observed Chair Week comes out — on every screen and card —
> without a single hand-seeded number, and without any way for the system to write to, or
> (until the founder flips one switch) even read from, a real booking platform.**

### Definition of done

| # | Criterion | Proof |
|---|---|---|
| R1 | Paste booking URLs on **New scan** → candidates → platform detected → `oc_business` rows → queued | CI3 + service discovery job; integration test |
| R2 | Worker claims work, runs the **Booksy / Square** adapter on fixture pages, validates, normalises, reports | worker tests + end-to-end run |
| R3 | Service completion stores the run, a **snapshot** (per-chair hourly grid, hashes only), staff (labels only), services/prices; **scores with the Meter**; updates daily + weekly rollups and dead zones | service tests + DB assertions |
| R4 | Business screen and every card read the **observed** grid when one exists (`source: observed`), derived only as fallback | CI3; screenshot |
| R5 | Scheduled jobs: enqueue due, reap locks, canary, weekly rollup, snapshot purge, staff-name purge, 8am digest | `node src/jobs/run.js <job>` + `php index.php cron <job>`; tests |
| R6 | **Agency tenancy**: a team (owner + sub-users) shares one pipeline, share links and digests; assignment within the team only | CI3; integrity test |
| R7 | **Access-code gating** enforced server-side (not just hidden in the rail), config-driven, off until codes are set | CI3; integrity test |
| R8 | **Security**: fail-closed service tokens in production, live reads impossible without an explicit switch, security headers, rate-limited public share route, IDOR review, no PII in snapshots | tests + checklist §6 |
| R9 | All suites green; new tests for every new path | `npm test` ×3, `php …/integrity_test.php` |

---

## 2. The pipeline

```
 CI3 New scan ──► oc_discovery_candidate (manual)        [CI3 writes, tenant-agnostic]
                        │  service job: discover
                        ▼  URL signature → platform; verify; create/attach oc_business
                  oc_business ──► oc_scan_queue (watchers → priority)
                        │  worker: POST /v1/scan/claim (x-worker-token)
                        ▼
   worker: registry → adapter.read(ctx) ──ctx.read──► fixtureReader (default)
                                                     └► egressClient → broker (only if OC_READ_MODE=broker AND OC_ALLOW_LIVE=1)
           validateResult → normalise → buildSnapshot
                        │  POST /v1/scan/complete (x-worker-token)
                        ▼
   service: oc_scan_run → oc_scan_snapshot (gzip) → oc_staff / oc_business_service
            → Meter score() → persist() (oc_vacancy_daily + oc_business latest_*)
            → rollupBusiness() (weekly) → accumulateDeadZones()
                        │
                        ▼
   CI3: Oc_visuals_lib::chair_week() reads the latest snapshot → cards, business screen
```

## 3. Contracts (both sides build to these; tests pin them)

### 3.1 Adapter unit (extends `registry/contract.js`, backwards compatible)

```js
{
  staffHash: 'sha1 hex',          // adapter: sha1(`${platformKey}:${resourceId}`) — never a name
  staffKey:  'resource id',       // the platform's own pseudonymous id (optional)
  day:       'YYYY-MM-DD',        // business-local
  openMinutes, envelopeMinutes,   // as today
  envelopeStart: 540,             // minute-of-day the chair's bookable day starts (optional)
  envelopeEnd:   1140,            //   … and ends
  openStarts: [540, 570, …],      // minute-of-day of each OPEN slot start (optional)
}
services: [{ key, name, durationMin, priceCents }]   // menu; name is a SERVICE name, not a person
```
The validator additionally refuses: `openStarts` outside the envelope, non-integer minutes,
`envelopeEnd <= envelopeStart`, and any unit key named `name`, `staffName`, `displayName`.

### 3.2 Completion payload — `POST /v1/scan/complete`

```js
{
  queueId, businessId, platformId, observedOn: 'YYYY-MM-DD',
  status, adapterVersion, tier, bytes, costMicros, durationMs, errorCode, error,
  capability, horizonDays, slotMinutes,
  grid:     { openMinutes, envelopeMinutes, vacancyBp, staffCount, dayCount, daysToNext, byDay },
  snapshot: { v: 1, slotMinutes, days: [ { date, units: [ { staffHash, envelope: [start,end], open: [starts…] } ] } ] },
  staff:    [ { staffHash, staffKey } ],
  services: [ { key, name, durationMin, priceCents } ],
  businessName: 'Fade Lab',   // the SHOP's public name (never a person's); replaces a discovery placeholder only (oc_014)
  businessLocation: { lat, lng, address },  // the shop's published map point; fills lat/lng only when empty
}
```
`grid`, `snapshot`, `staff`, `services`, `businessName`, `businessLocation` are present only for `ok`/`partial`. Payload cap stays 256 KB.

### 3.3 Snapshot blob (`oc_scan_snapshot.grid_gz`, codec `gzip_json_v1`)

Exactly `payload.snapshot`, with each `staffHash` rewritten by the service to the **schema** hash
`sha1(business_id|staff_key)` (`oc_staff.staff_hash`). Never a name. `purge_after` = captured_on + 14d.

### 3.4 CI3 read of a snapshot → Chair Week

The week is the Meter's own window — **days 1..7 after the read** (falls back to starting on the read
day when the snapshot holds fewer than 8 days). Hours are the 9a–6p rows. For each chair and hour:
bookable minutes = the hour ∩ the chair's envelope; open minutes = each open slot's length, clipped
to the hour and the envelope. A tile is **shut** with no bookable minutes, **empty** when open ≥ half
of bookable, otherwise **booked**. The day's and week's **numbers** ("199 of 280 chair-hours") come
from the minutes, not from counting tiles, so they match the Meter's score to rounding. Chairs are
ordered by `oc_staff.label` ("Chair 1…"). `source: observed`.

## 4. Safety switches

| Switch | Default | Effect |
|---|---|---|
| worker `OC_READ_MODE` | `fixture` | adapters read recorded/synthetic pages from `fixtures/`; no network |
| worker `OC_ALLOW_LIVE` | `0` | `broker` mode refuses to start unless this is `1` **and** the platform's `rollout` is `live` |
| service/worker `NODE_ENV=production` | — | boot refuses without `OC_INTERNAL_TOKEN` and `OC_WORKER_CALLBACK_TOKEN` |
| CI3 `oc_access_enforce` | `FALSE` | when TRUE, tier gates read `oc_access_codes`; until the codes are decided, all gates open |

> **Fixtures are synthetic.** No real Booksy or Square page was fetched to build them (founder
> decision: no live crawl). They are shaped after the platforms' public booking flows and exercise
> every parser branch, but **the spike must replace them with real recordings** before any adapter
> is promoted from `shadow` to `live` — the adapter version is bumped when that happens.

## 5. Work packages

| # | Package | Owner | Files |
|---|---|---|---|
| P1 | Goal + contract (this doc), task list | lead | docs/70 |
| P2 | Worker: registry loader, Booksy + Square adapters, fixtures + fixture reader, snapshot builder, worker loop, safety switches, tests | agent W | `engine/openchair-worker/**` |
| P3 | Service: completion pipeline (snapshot, staff, services, Meter, persist, rollups, dead zones), discovery job, jobs runner + scheduler, token fail-closed, tests, migration `oc_011` if needed | agent S | `engine/openchair-service/**`, `sql/migrations/oc_011_*` |
| P4 | CI3: observed Chair Week, New scan import + progress, agency tenancy, access gating, cron CLI, security headers + share rate limit, integrity tests | lead | `engine/application/**` |
| P5 | End-to-end run (DB → discovery → worker → service → CI3 screens), screenshots, docs, commit + push | lead | — |

## 6. Security checklist

- [x] Service internal + worker tokens required in production; constant-time compare (exists)
- [x] Worker cannot reach the network unless `broker` + `OC_ALLOW_LIVE=1`; adapters import no network module (CI grep)
- [x] Snapshot/staff never carry a person's name (validator + service re-check)
- [x] Discovery accepts only `https` URLs on known platform hosts; length-capped; no fetch
- [x] CI3: CSRF on every POST; tenant scoping on prospect/share/pitch/alert/market; assignment limited to the team
- [x] Security headers on every response (CSP-lite, X-Frame-Options, nosniff, Referrer-Policy)
- [x] Public `/c/{token}`: token format check, per-IP rate limit (no IP stored beyond a short window), noindex
- [x] Access gates enforced in controllers, not only the rail
- [x] Cron CLI refuses web requests (`is_cli()`)

## 7. Running it

```bash
# schema (idempotent; oc_011 moves Booksy + Square from research to SHADOW, never live)
for f in sql/migrations/oc_0*.sql; do mysql db_openchair < "$f"; done

# service (app box) — jobs on a timer with OC_JOBS=1, or via cron:
cd engine/openchair-service && node src/server.js
node src/jobs/run.js discover|enqueue-due|reap|canary|rollup|cohort|purge|all

# worker (crawl VM) — fixture mode by default; --once = one claim cycle
cd engine/openchair-worker && OC_SERVICE_URL=http://127.0.0.1:4100 node src/worker.js [--once]

# CI3 tenant jobs (CLI only)
php engine/index.php cron digest|housekeep|all
```

Production needs `OC_INTERNAL_TOKEN` and `OC_WORKER_CALLBACK_TOKEN` (24+ chars) on the service, the
worker token on the worker, and the same internal token in CI3 `oc_internal_token`.

### Verified end to end (fixtures, local DB)

New scan with 10 links → discovery placed 9 (Vagaro recognised, no adapter) → worker read 6 `ok`, and
mapped the negatives correctly (403 → `blocked`, stale page → `NO_GRID_PARSED`, staff-name page →
refused `UNSAFE_STAFF_ID`) → completion stored snapshots, "Chair N" labels (no names), menus, and
replaced Square's "Unnamed business" with the published shop name → the Meter **refused to publish a
first read** (needs 2 prior reads and evidence over 4 weeks — by design) → with 4 weeks of history
simulated in the local DB only, the next real read published: confidence 95, Hungry/Steady/Slammed,
lead-time override on both 9- and 12-day shops → CI3 Chair Week renders `source: observed`, and its
"199 of 280 chair-hours" matches the Meter's score.

### Bugs this run caught and fixed
- Money priced the whole 14-day read horizon as "this week" — now the Meter's 7-day window (`openMinutesInWindow`).
- The observed grid over-counted empty hours (any open slot ⇒ empty hour) and started on the read day — now
  minute-exact numbers, ≥50% tiles, days 1..7.
- Square listings stayed "Unnamed business" — adapters now report the shop's public name (oc_014).
- Pasted listings had no location, so the Chair Map and Same-day card had no neighbours — adapters now report the
  shop's published map point and address, filled in only when empty.
- Pitch slot counts covered the 14-day read while saying "this week" — now the Meter's 7-day window.
- Slammed rows showed "$0–0 unsold" — lists now show each band's second number (the wait).
- Open redirect through posted `back` targets — closed (`_safe_back`).

### Still the founder's call
- **The spike.** Fixtures are synthetic; adapters are `0.1.0-modelled` and platforms sit in `shadow`.
  Going live = real recordings → adapter re-verification → version bump → `rollout='live'` → `OC_ALLOW_LIVE=1`.
- **Access codes** (`oc_access_codes`, `oc_access_enforce`) — enforcement is built and tested, off until set.
- **CSP** is report-only until the ported modules' third-party embeds are cleaned up.
