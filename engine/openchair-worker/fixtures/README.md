# Worker fixtures — SYNTHETIC

**Nothing in this directory was captured from Booksy or Square.**

The founder decided (docs/70-radar-build.md) that no request goes to a real booking platform until
the spike's go/no-go. These pages are therefore *modelled*: shaped after each platform's public
booking flow so that every branch of the Booksy and Square Appointments adapters runs, including
every refusal. They prove the pipeline, the validator and the privacy rules. They do **not** prove
that the adapters read the real platforms correctly — only real recordings can.

## Before an adapter leaves `shadow`

1. Record the platform's real public pages during the spike (through the egress broker, read-only).
2. Replace that platform's files here and its entries in `index.json`; keep the negative cases.
3. Re-verify every field path in `src/adapters/<platform>.js` against the recordings.
4. Bump the adapter's `meta.version` (it is stamped on every scan run and snapshot).

## Layout

| File | What it is |
|---|---|
| `generate.mjs` | Writes everything below, deterministically: `npm run fixtures` |
| `index.json` | URL → `{file, status, contentType}`. The fixture reader serves only these; anything else is a 404 `FIXTURE_MISSING` |
| `catalog.json` | Booking URL per case per platform, the timezone, and the staff names that must never reach adapter output |
| `booksy/`, `square_appointments/` | The pages |

Dates are `{{D0}}`…`{{D13}}` placeholders — today + n in the business's timezone, filled at read
time, in both the URL keys and the bodies. Availability URLs therefore assume a 14-day horizon.
The shut and short days repeat by **offset** (every 7th day from day 3 is shut, from day 2 is short),
not by weekday, since a fixture cannot know which weekday "today" is.

## Cases (both platforms)

| Case | Expect | What it guards |
|---|---|---|
| `hungry` | `ok`, vacancy > 50% | Booksy's version also puts `staffer_name` beside each slot list — the adapter must ignore it |
| `steady` | `ok`, 15–50% | |
| `slammed` | `ok`, < 10%, first opening ≥ 9 days out | the Slammed lead-time path |
| `partial` (Booksy) | `partial` | the platform served 7 of the 14 days asked for |
| `blocked` | `blocked` | HTTP 403 |
| `notFound` | `not_found` | URL absent from `index.json` |
| `captcha` | `captcha` | an anti-bot page served with HTTP 200 |
| `stale` | `error NO_GRID_PARSED` | fetch fine, nothing parses — must never become a "fully booked" shop |
| `leak` | `error UNSAFE_STAFF_ID` | a stylist's name where the resource id belongs |
