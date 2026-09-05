# OpenChair — UX / UI Specification

**Doc:** `openchair/docs/40-ux-spec.md`
**Status:** v1.0 — implementation-ready
**Source of truth for product:** [`00-brief.md`](00-brief.md)
**Audience:** whoever builds the HTML prototype and the CI3 module. This doc is the spec; build to it exactly.
**Non-goals of this doc:** data model, adapter architecture, API contracts, pricing page copy.

---

## 0. House-style audit (what OpenChair must feel like a sibling of)

I read the existing admin shell before designing anything. Findings that constrain us:

| Thing | Where | What it actually is |
|---|---|---|
| Shell layout | `engine/application/views/ic_shell/layout.php` | Top **pill nav** (Nixtio style), centred `.content` at `max-width:1280px`, `padding:18px clamp(16px,3vw,34px) 60px` |
| Palette | same file, `:root` | **Not dark any more.** It's warm cream — `--bg:#F3EAE4`, `--surface:#FFFFFF`, `--line:#ECDDD4`, accent coral `--coral:#F0755E` / `--coral2:#E75C43` |
| The dark part | same file | An **espresso ink block** kept as a second token family: `--sb:#2A1E18 --sb2:#3A2A23 --sb-line:#43332B --sb-txt:#FAEEE7 --sb-txt2:#C7ADA0 --sb-mute:#9A7E71`. It's used for the active nav pill and the old sidebar. |
| Semantic colours | same | `--green:#1FA274 --amber:#C07A12 --blue:#2E74B5 --violet:#7A5AF0` |
| Radii | same | `--r1:12px --r2:16px --r3:24px --r4:30px`, controls are `999px` pills |
| Type | same | `--disp:'Outfit'` (600), `--body:'Manrope'`, `--mono:'Space Mono'` (uppercase micro-labels, `letter-spacing:1.3px`, 10px), `--serif:'Fraunces'` |
| Components | same + `pages/*.php` | `.card` / `.card.pad`, `.kpi` (mono label + 38px Outfit number + mono caption), `.chip.green/.amber/.blue/.mute/.coral`, `.btn.pri/.gho/.sub/.sm`, `.tchip` filter chips, `.tabs`, `.empty`, `.pill`, `.live .blip`, `.bp-card` picker grid, `_pager.php`, `icTableSort()`, one global `[data-tip]` tooltip, `.ic-skip-link` |
| Gating idiom | `pages/offers.php:192` | A **padlock note inside the real panel** (`.of-nokey`), not a modal, not a redirect. Nav pill is hidden entirely when the flag is off (`$ic_can_offer_finder`). |
| Elevation | same | Borderless cards + one soft warm shadow `--card-shadow:0 24px 55px -42px rgba(90,50,35,.35)` |

**The sibling decision.** OpenChair is a *dark* product (the brief and the demo want a map that glows; a cream map is a mall directory). The way to be a sibling and still be dark is:

> **Promote the espresso ink block to the full surface system.** OpenChair's `--bg/--surface/--line/--txt` are the `--sb*` family expanded into a proper 6-step ramp. Coral stays the accent, unchanged. Radii, type stack, mono micro-labels, chip/kpi/btn/tchip idiom, pill nav, `_pager`, `icTableSort`, the tooltip and the skip link are **reused verbatim**.

Result: put the two products side by side and they are obviously from the same house — same coral, same Outfit numerals, same pill nav, same 24px card radius — one is the daylight side, one is the night side. Section 10 gives the exact tokens.

---

## 1. Design principles

The buyer: a JVZoo/local-consultant type. Bought at 11pm for $47 on a webinar. Not technical. Will refund inside 10 minutes if nothing lands. Will show this on a Zoom to a barbershop owner tomorrow. Judges the product by *one artifact*: the one-pager they can hand over.

### P1 — The number is the product
The Empty-Chair Revenue figure is the entire value proposition. Every screen answers "how much money is on the floor here?" before it answers anything else.
- **So we do:** put `$1,860 unsold this week` in 48px Outfit at the top of Business Detail, on the map card, in the list row, and as the subject line of the pitch email. One number, same formatting, everywhere.
- **We never do:** lead with a composite "OpenChair Score™" out of 100. Nobody walks into a shop and says "your OpenChair Score is 74."

### P2 — Ten-minute wow, or refund
Time-to-first-value is measured from checkout, not from "after the nightly scan runs."
- **So we do:** first-run runs a live scan of *their* city in front of them with results streaming in one business at a time, and the "Open your first pitch" CTA unlocks the moment the first business resolves — not when the scan completes.
- **We never do:** show a full-screen spinner, an empty dashboard, or the sentence "your first results will be ready tomorrow morning." That sentence is a refund.

### P3 — Honest about what we can't see
The brief's Risk #1 is a product risk, not a legal footnote. If a consultant repeats a number that turns out to be a staff holiday, they lose the room and blame us.
- **So we do:** ship a **ConfidenceBadge** on every score and a first-class, always-visible **"What we could not see"** disclosure block on Business Detail, and we say **"online-bookable open slots"** in every generated artifact.
- **We never do:** print "empty shop", hide the caveat behind a `?` tooltip, or set it in 10px grey at the bottom of the PDF.

### P4 — Every screen ends in a handable artifact
The consultant's job is to walk in with paper. Analysis they can't hand over is entertainment.
- **So we do:** every detail screen has exactly one primary coral button and it is always "Make the pitch" → a printable one-pager in under 3 seconds. Bulk actions on the list produce a stack of them.
- **We never do:** dead-end a drill-down in a chart. No screen may exist without an export/act path.

### P5 — Dense, but never a spreadsheet
This buyer is not an analyst. They can read a bar; they cannot read a pivot table. But they *do* need 47 businesses on one screen.
- **So we do:** encode with position, length and size first (VacancyBar, ScoreDial, pin radius), keep every dense grid **progressively disclosed** (week → day → slot), and always print the literal number next to the visual.
- **We never do:** render a raw 7×N×M table of slots, use a legend the user has to memorise, or require a hover to learn what a colour means.

---

## 2. Information architecture

### 2.1 Nav map

Top pill bar, identical mechanics to `_ic_topnav.php` (`.tn-pills` / `.tn-pill.on`, overflow scroll on mobile, `More ▾` dropdown, avatar menu right).

```
┌ OpenChair ─────────────────────────────────────────────────────────────────┐
│  [◱ logo]   ( Today · Radar · Prospects · Ledger · Coverage )   (?) (⚙) (av)│
└────────────────────────────────────────────────────────────────────────────┘
                                                      More ▾ ┐
                                                             ├ Adapters
                                                             ├ Campaign Kit ▲OTO3
                                                             ├ Billing & Quota
                                                             ├ White-label   ▲OTO2
                                                             ├ Team / Agency ▲BE
                                                             └ Help & Tutorials
```

Nav keys (mirroring the NAV-CONTRACT pattern — controller sets `$page` to the key):

| key | label | route | tier |
|---|---|---|---|
| `today` | Today | `/openchair` | FE |
| `radar` | Radar | `/openchair/radar` | FE |
| `prospects` | Prospects | `/openchair/prospects` | FE |
| `ledger` | Ledger | `/openchair/ledger` | FE (read-only, 1 client) / OTO2 (white-label, unlimited) |
| `coverage` | Coverage | `/openchair/coverage` | FE |
| `scan` | New Scan | `/openchair/scan` | FE — **not a pill**, reached via the coral `+ New scan` button |
| `business` | Business Detail | `/openchair/b/{id}` | FE — not a pill |
| `pitch` | Pitch Composer | `/openchair/pitch/{id}` | FE — not a pill |
| `adapters` | Adapters | `/openchair/adapters` | FE (More ▾) |
| `kit` | Campaign Kit | `/openchair/kit` | OTO3 |
| `billing` | Billing & Quota | `/openchair/billing` | FE (More ▾) |
| `whitelabel` | White-label | `/openchair/whitelabel` | OTO2 |
| `settings` | Settings | `/openchair/settings` | FE (avatar menu) |

Following the house precedent (`$ic_can_offer_finder`), **a pill for a tier the user does not own is not rendered in the primary bar** — it lives in `More ▾` with a small `▲` upgrade caret, so the daily bar never fills with locked bait.

### 2.2 Screen inventory

| # | Screen | Purpose | Tier | Entry points |
|---|---|---|---|---|
| S0 | **First-run wizard** | 3 steps → first live scan | FE | Auto on first login |
| S1 | **Today** (Alerts, the 8am list) | The habit screen. Two bucket columns, what changed overnight | FE (Hungry col) / OTO1 (Slammed col) | Logo, `Today` pill, daily email |
| S2 | **Scan Setup** | niche + city + depth → launch | FE (1 city / 3 niches) / OTO1 (unlimited) | `+ New scan`, first-run |
| S3 | **Scan Progress** | Progressive reveal; morphs into S4 | FE | From S2 |
| S4 | **Radar — Map + List** ★ hero | Browse, filter, triage, bulk-act | FE | `Radar` pill |
| S5 | **Business Detail** | Slot grid, score, revenue, dead-zone, integrity block | FE / dead-zone OTO1 | Pin or row click |
| S6 | **Pitch Composer** | Hungry vs Slammed variants + one-pager preview | FE | `Make the pitch` |
| S6b | **One-pager preview / print** | The handable artifact | FE (branded OpenChair) / OTO2 (white-label) | From S6 |
| S7 | **Prospects / Watchlist** | Pipeline: Watching → Pitched → Client → Passed | FE | `Prospects` pill |
| S8 | **Ledger** | 8-week trend. Prospecting artifact *and* retainer report | FE / OTO2 white-label | `Ledger` pill, Business Detail tab |
| S9 | **Coverage** | Which platforms/niches/cities we can actually see | FE | `Coverage` pill, every "we couldn't see" link |
| S10 | **Adapters** | Per-platform health, last success, queue depth | FE | More ▾ |
| S11 | **Campaign Kit** | Dead-zone offers, SMS templates, Google posts, LossReel credits | OTO3 | More ▾, Pitch Composer |
| S12 | **Settings** | Profile, notification time, timezone, data retention | FE | Avatar |
| S13 | **Billing & Quota** | Plan, scan quota, city/niche slots, upgrade | FE | More ▾, QuotaMeter |
| S14 | **White-label** | Logo, colours, custom domain for Ledger + one-pager | OTO2 | More ▾ |

### 2.3 How gating is teased (the anti-mugging rule)

Three rules, applied consistently. This is the difference between "I'll upgrade later" and "I want a refund."

1. **Never gate the count, only the payload.** The Slammed column on Today shows the *real* number — "**12 slammed businesses found**" — with the twelve rows rendered as skeleton bars, name blurred, one row shown in full as a sample. The user sees we did the work; they see exactly what they'd get.
2. **The lock is inline and calm, in the panel where the feature would be.** Reuse the `.of-nokey` idiom verbatim: a small padlock glyph, one sentence naming the tier and what it does, and a text link. No modal, no interstitial, no countdown timer, no "you're missing out!" No feature ever redirects a click to a sales page.
3. **One free taste per gated feature, permanently.** The Dead-Zone Map is unlocked for **one** business of the user's choosing, forever (`dead_zone_free_unlock_business_id`). The white-label Ledger renders one report with their logo, watermarked `PREVIEW`. The user gets to *do* the thing once and feel it work. This converts far better than a blur and it removes the "I paid $47 and it's a demo" refund trigger.

Copy template for every gate:
> 🔒 **Dead-Zone Map** is part of **OpenChair Pro (OTO1)**. It shows which days and hours are chronically empty across 8 weeks — the exact slot your offer should target. [See what it looks like →] · [Unlock Pro]

Left link opens a **static annotated screenshot** in a drawer. Right link goes to billing. The left link is listed first, deliberately.

---

## 3. The 60-second demo, storyboarded

This is the webinar flow. It must run live, from a cold browser, in under 60 seconds, on a hotel wifi. **Design rule: every beat below must be reachable by keyboard and must not depend on a network call that can take longer than 1.5s** — the demo dataset for the top-3 seeded cities is pre-warmed nightly and served from cache; a live scan is *shown* running in the background but the reveal is instant.

| # | t | On screen | Presenter does | What changes |
|---|---|---|---|---|
| 1 | 0:00 | **S1 Today**, one big search field centred, placeholder `barbershop, Tampa`. Two empty bucket columns behind it, dimmed. | Types `barbershop, Tampa`. Typeahead resolves the city chip and the niche chip inline as they type. | Field grows a `Tampa, FL` city chip + `Barbershop` niche chip. Coral `Scan →` button becomes enabled. |
| 2 | 0:06 | Same. | Hits `Enter`. | **Route to S3 Scan Progress**, which is the map already — dark map of Tampa, `ScanProgress` rail on the right reading `Resolving 47 businesses…`. No spinner, no blank frame. |
| 3 | 0:08 | Map with the city framed. | (nothing) | Pins **drop one at a time**, ~15/sec, each with a 180ms drop+settle, coloured as it resolves. The rail counts up: `12 / 47 · 3 hungry · 1 slammed`. Audible-looking: the counter is the drama. |
| 4 | 0:16 | 47 pins, red-to-teal. Rail flips to the FilterRail. Legend visible top-left of map. | (nothing) | Header KPI row animates in: `47 scanned · 28 hungry · 6 slammed · **$54,120 total unsold this week**`. That aggregate number is the webinar screenshot. |
| 5 | 0:20 | Hero S4. | Clicks the **reddest pin** (largest radius, top-left of the cluster; the demo dataset pins the reddest at a stable location). | Map recentres 200ms; a **card peek** rises from the bottom of the map: name, `88% open`, `$1,860 unsold this week`, `Booksy`, `Make the pitch`. |
| 6 | 0:25 | Peek card. | Clicks the card (or `Enter`). | **S5 Business Detail** slides in as a right drawer over the map (desktop) — map stays visible behind at 55% width. Above the fold: name, ScoreDial `88`, `$1,860 unsold this week` at 48px, ConfidenceBadge `High · 4 of 4 staff read`. |
| 7 | 0:31 | Drawer scrolled to the SlotGrid. | Scrolls once (or the drawer auto-scrolls 240px on open). | **The real Booksy grid** renders: 7 day-columns × hour-rows, 38 open cells lit coral, a coral count ribbon `38 open of 56 bookable`. Dead-zone overlay shows `Tue 1–4pm` ringed. This is the "oh" moment — it looks like their actual booking page. |
| 8 | 0:38 | Same. | Clicks coral **`Make the pitch`**. | **S6 Pitch Composer**, opening on the **Hungry** variant, already written. Left = controls, right = the one-pager rendering live at page proportions with the grid screenshot embedded. |
| 9 | 0:44 | One-pager visible: headline `You have 38 open chairs this week`, the grid image, `$1,860`, the Tuesday-afternoon fix, consultant's name + phone. | Says the identity-shift line from the brief. | (nothing — let it sit for 3 seconds; this is the artifact) |
| 10 | 0:48 | | Presses `Esc` twice (drawer → map) or clicks the breadcrumb. | Back to S4, map state preserved, previously-viewed pin now has a `seen` ring. |
| 11 | 0:50 | Hero S4. | Clicks a **teal pin**. | Peek card: `4% open · next available in 19 days · $0 unsold — they're turning people away`. Note the number flips meaning: revenue line becomes **`≈ $2,900/wk turned away`** (walk-away estimate). |
| 12 | 0:55 | | Clicks `Make the pitch`. | S6 opens on the **Slammed** variant — different headline (`You're booked 19 days out. That's a price problem, not a marketing problem.`), different fix list (waitlist / deposit gate / price increase / second chair). Same layout, same scan. |
| 13 | 0:59 | Two one-pagers side by side (a `Compare` chip in the composer header shows both). | "Two pitches. One scan. Live." | — |

**Demo-mode requirements for the build:**
- `?demo=1` seeds the map from `openchair/ui/fixtures/tampa-barbershop.json` and disables all network calls except the fake progressive-reveal timer. Every beat above must work offline.
- Pin drop animation respects `prefers-reduced-motion` — with it on, pins fade in in one 200ms batch and the counter still counts.
- Beats 5 and 11 must have keyboard equivalents: `Tab` into the map moves a roving focus ring through pins in vacancy order (descending), `Enter` opens the peek. That ordering means "hit Tab, Enter" lands on the reddest pin — the presenter's safety net if the click misses.

---

## 4. First-run experience — "time to first wow"

**The situation:** user just paid. Zero rows in every table. The nightly cron has never run for them. If we render our real dashboard they see nine empty cards and hit refund.

### 4.1 The first-run path (target: first pitch PDF in under 4 minutes)

```
Checkout ─► S0.1 Welcome ─► S0.2 Pick your patch ─► S0.3 Scanning (S3) ─► S4 Radar
                                                          │
                                                          └─(at first resolve, ~8s)─► "Open your first pitch" unlocks
```

**S0.1 Welcome** — one screen, no form. Big Outfit line: *"Let's find someone in your town with an empty calendar."* Three 1-line bullets of what happens next. One coral button `Start`. A `Skip, I'll explore` ghost link (goes to S4 in demo-data mode with a persistent banner `You're looking at sample data for Tampa — [scan your city]`). **Never trap the user in a wizard.**

**S0.2 Pick your patch** — the only data entry in the whole onboarding:
- `City` — typeahead, geocoded, defaults to the IP-guessed city pre-filled and pre-confirmed (user can change; most won't).
- `Niche` — a **grid of 12 illustrated niche cards** (Barbershop, Nail salon, Hair salon, Med spa, Dental, Chiro, Physio, Tattoo, Massage, Personal training, Pet grooming, Auto detail), each showing the platform logos we can see for it and a `~38 businesses` estimate. Multi-select up to 3 on FE. This grid reuses `.bp-grid` / `.bp-card`.
- Estimate line under the grid updates live: `We'll scan ~114 businesses tonight. First results in about 90 seconds.`
- Coral `Scan now →`.

**S0.3 Scanning** = S3, described below. This is where the wow happens.

### 4.2 Progressive-reveal scan (S3) — spec

**The core idea: the scan screen IS the map screen, populating.** There is no separate loading page and no transition. The moment the job is accepted we render S4's chrome with an empty map framed on the city, and results stream into it.

**Streaming transport:** SSE from `GET /v1/openchair/scan/{jobId}/stream` (fall back to 2s polling). Event types: `business.discovered` (name + latlng, pin drops **grey**), `business.resolved` (score + revenue, pin **recolours** and grows), `business.failed` (pin turns hollow/dashed), `platform.blocked`, `job.progress`, `job.done`.

**Two-phase pin lifecycle** — this is what makes it feel alive rather than laggy:
1. **Discovered** (fast, ~50/sec from the directory pass): small grey hollow pin, 6px. The map fills with grey confetti in the first 3 seconds. The user immediately sees *scale*.
2. **Resolved** (slow, ~1-3/sec, this is the adapter fetch): pin animates grey → its vacancy colour, radius grows to its score-scaled size, and its list row slides into the top of the list with a 900ms coral left-edge flash.

**ScanProgress rail** (right side, 300px, replaces the FilterRail during scan):

```
┌ SCANNING ─────────────────────────┐
│ Barbershop · Tampa, FL            │
│                                   │
│ ████████████░░░░░░░░  23 / 47     │   ← determinate once discovery completes
│                                   │
│  8  hungry     ▲                  │   ← counts tick up, mono numerals
│  2  slammed    ●                  │
│  3  couldn't read  ⚠ [why?]       │
│                                   │
│ $22,400 unsold found so far       │   ← the drama line, 26px Outfit
│                                   │
│ ─ live ───────────────────────────│
│ ● Fade Lab           88% open  ✓  │   ← last 5 resolves, newest on top
│ ● Kingsman Cuts      12% open  ✓  │
│ ● Tampa Barber Co.        …       │   ← in-flight, shimmer
│ ○ Clip Joint         blocked   ⚠  │
│                                   │
│ [ Open first pitch → ]            │   ← enabled after first resolve
│ You can leave — we'll email you.  │
└───────────────────────────────────┘
```

Rules:
- **The `Open first pitch` button enables at the first `business.resolved` event** (~8s), not at `job.done`. Wow is not gated on completion.
- **Leaving is safe and said out loud.** "You can leave — we'll email you when it's done." Removes the anxiety of a long job.
- Progress bar is **indeterminate for the discovery phase** (unknown denominator) then flips to determinate. Never fake a percentage.
- If a job is still running when the user navigates elsewhere, a **slim 3px coral progress strip pins under the top nav** on every screen, clickable back to S3.
- Estimated time is shown only after 10 resolves (so the estimate is real): `~2 min left`. Never before.

### 4.3 State matrix — every major screen

Legend: **E** empty · **L** loading · **P** partial · **X** error · **S** stale

| Screen | Empty | Loading | Partial | Error | Stale |
|---|---|---|---|---|---|
| **S1 Today** | Illustration + *"No scans yet."* + `Scan your first city` coral CTA. Behind it, the two bucket columns rendered as 4 ghost rows each so the shape is learnable. | Column headers real, 6 skeleton rows per column (shimmering `--surface2` bars at row height 64px). | Hungry column populated, Slammed still loading → Slammed shows its own skeleton, not a page spinner. | Inline red-tinted card in place of the column: *"Couldn't load today's list."* + `Retry` + `See yesterday's` (cached). Never a blank page. | `StaleDataBanner` above columns: `Last scan 3 days ago · [Re-scan Tampa]`. |
| **S2 Scan Setup** | — (always "empty" by nature) | Estimate line shows `Estimating…` shimmer | Niche cards load progressively (coverage counts fill in) | Estimate falls back to `We'll scan every business we can find` | — |
| **S3 Scan Progress** | Job accepted, 0 discovered: map framed on city, rail says `Finding businesses…` with indeterminate bar | (is the loading state) | The normal case; see 4.2 | Job died: rail turns amber, `Scan stopped at 23 of 47.` + `Keep what we found` (primary) + `Retry the rest` | — |
| **S4 Radar** | No results for filter: keep the map + rail, empty list shows *"No businesses match these filters"* + `Clear filters` chip-clear. If truly no scan: hand off to S1's empty. | Map skeleton = dark plate with a subtle grid; list = 8 skeleton rows. **Never blank the map container** (layout shift). | Some rows unresolved → they render **hollow/dashed** in list and map, sorted last, with a `Reading…` chip. | Map tiles fail → fall back to **list-only mode** with a small notice `Map unavailable — showing the list.` The list is fully usable alone. This is a hard requirement. | Rows >7 days old get a `StaleDataBanner` in the toolbar + a `7d old` mono chip per row. |
| **S5 Business Detail** | Never truly empty (there is always a business). If zero slots readable → the whole page becomes the "What we could not see" block, honestly, with `Try again` + `Report this`. | Header + name real instantly (we have it), grid area = skeleton at final height (no jump). | Some staff read, some not → grid renders the read staff; unread staff appear as **explicit named rows with a hatched "not readable" fill** — never silently dropped. | Adapter error → amber card at top of grid with the platform name, the failure class, and last-good timestamp + `Show last good read (4 days ago)`. | `Read 4 days ago` chip next to the ConfidenceBadge; the $ figure gets a `≈` prefix and the tooltip explains. |
| **S6 Pitch** | No generated copy yet → the composer shows the skeleton one-pager with real numbers and placeholder prose + `Write it for me` coral button. Numbers before words. | Copy streams token-by-token into the preview (visible generation = perceived speed). | Email done, SMS pending → tabs show a small spinner on the pending tab only. | Generation fails → **fall back to the deterministic template** filled with the real numbers (always ships something) + `Try AI again`. Never leave the consultant with nothing to hand over. | — |
| **S7 Prospects** | *"Nothing on your watchlist yet."* + `Go to Radar`. Shows the 4 pipeline columns empty with counts `0`. | Skeleton cards per column. | — | Inline retry per column. | Per-card `Last scan 12d` chip. |
| **S8 Ledger** | <2 data points: *"We need at least 2 scans to draw a trend."* + shows the single point and `Next scan: tonight 2am`. **This is the most likely empty state on day 1 — design it as a promise, not a failure.** | Chart area skeleton at final height. | Weeks with gaps → line drawn dashed across the gap, gap week marked with a hollow dot + tooltip `No scan this week`. Never interpolate silently. | — | Banner + `Re-scan now`. |
| **S9/S10 Coverage/Adapters** | — | Skeleton rows | Per-adapter status is inherently partial; that's the content | Per-row error chip | `Last checked` per row |

**Skeleton spec:** `background: linear-gradient(90deg, var(--surface2) 25%, var(--raise) 50%, var(--surface2) 75%)`, `background-size:200% 100%`, `animation: ocShimmer 1.4s linear infinite`; disabled under `prefers-reduced-motion` (static `--surface2`). Skeletons must match the final element's **exact height** — zero layout shift is a hard requirement on S4 and S5.

---

## 5. S4 — Radar (the hero screen), in detail

### 5.1 Layout

Desktop ≥1280px, full-bleed (this screen **overrides** the shell's `max-width:1280px` and uses `100vw` minus gutters — it's the one exception; declare `.content.is-radar{max-width:none;padding-inline:0}`).

```
┌──────────────────────────── top pill nav ─────────────────────────────────────┐
├── toolbar (sticky, 56px) ─────────────────────────────────────────────────────┤
│ Barbershop · Tampa FL ▾   [47 scanned][28 hungry][6 slammed][$54,120 unsold]  │
│                                        Sort: Most unsold ▾   ⌗ Map|List|Split │
├─────────────┬──────────────────────────────────────┬──────────────────────────┤
│ FILTER RAIL │              MAP                     │        LIST              │
│   280px     │            flexible                  │        420px             │
│  (collapse) │                                      │      (virtualised)       │
└─────────────┴──────────────────────────────────────┴──────────────────────────┘
```

Three view modes via a segmented control, persisted per user: **Split** (default, above), **Map** (rail + full map, list becomes a bottom sheet), **List** (rail + full-width table, denser row with more columns). The `List` mode is also the automatic fallback when map tiles fail.

### 5.2 Pin encoding

Pins carry **three** channels so colour is never load-bearing:

| Channel | Encodes | Detail |
|---|---|---|
| **Fill colour** | Vacancy % (0→100) | The 7-stop scale in §10.4 |
| **Radius** | Empty-Chair Revenue $ | `r = 9 + 13 * sqrt(rev / revMax)` px, clamped 9–24. Money = size. This is the redundant channel for "which one do I click." |
| **Glyph** | Bucket | `▲` Hungry (≥60% open) · `■` Balanced · `●` Slammed (≤5% open / next-avail ≥14d) · `?` Unread (hollow, dashed stroke, grey) |

States: `default` · `hover` (2px `--txt` ring, peek preview after 350ms) · `focus` (3px coral ring, always visible, keyboard) · `selected` (coral ring + raised z) · `seen` (a 1px `--mute` ring, persisted — "I already looked at this") · `watchlisted` (small bookmark notch on the pin's top-right).

**Clustering:** supercluster, `radius:56, maxZoom:15`. A cluster bubble is a **donut** whose arc segments are the bucket mix (coral arc = hungry share, teal arc = slammed share, grey = unread), with the count in the centre in Outfit 600 and the summed `$` beneath in 10px mono. Click → `fitBounds` to children with a 260ms ease. At ≤4 children, expand as a spiderfy instead of zooming.

**Legend:** always visible, top-left of the map, not a hover: a 7-stop horizontal gradient bar labelled `0% open` … `100% open` with the three bucket glyphs beneath. It is a `<figure>` with a real `<figcaption>` for screen readers.

**Map base style:** dark, low-saturation, label-light (Carto `dark_matter` or an equivalent MapLibre style). Roads at 18% opacity, water `#0F1A1C`, labels `--mute`. The pins are the only saturated thing on screen.

### 5.3 Filter rail (280px, collapsible to 48px icon rail)

Order matters — most-used at top:

1. **Bucket** — three big toggle tiles (not a dropdown): `▲ Hungry 28` / `■ Balanced 13` / `● Slammed 6`. Counts always live. On FE the Slammed tile is present, shows its true count, and is `is-locked` (padlock, `.of-nokey` line beneath the rail group). Toggling it on FE shows the pins **as grey unread pins** — position without payload.
2. **Vacancy score range** — dual-handle slider 0–100 with a histogram of the current result set behind it (32 bins, `--surface2` bars). Handles snap to 5.
3. **Empty-Chair Revenue** — dual-handle `$0 – $max`, log-ish scale, same histogram treatment.
4. **Niche** — `.tchip` multi-select, one chip per scanned niche + count.
5. **Platform** — `.tchip` per adapter (Booksy, Vagaro, Fresha, Square, Acuity, Mindbody…) with the adapter's health dot.
6. **Confidence** — three `.tchip`: `High` / `Medium` / `Low`. Default excludes nothing but **sorts Low last**.
7. **Last scanned** — radio: `Today` / `≤3 days` / `≤7 days` / `Any`.
8. **Status** — `Unseen` / `Watching` / `Pitched` / `Client` / `Passed`.

Rail footer: `Clear all (5)` ghost button + `Save this view` (named views appear as `.tchip` under the toolbar). Every applied filter also renders as a removable chip in the toolbar so state is never hidden inside a collapsed rail.

### 5.4 Sort

Dropdown, default **`Most unsold $`**. Options: Most unsold $ · Highest vacancy % · Lowest vacancy % (for Slammed hunting) · Closest to me (needs geolocation permission, asked in context with a reason) · Recently scanned · Confidence · Name A–Z. The active sort key gets a mono caption in the list header (`sorted by unsold $, high → low`) — reuse `icTableSort`'s indicator idiom.

### 5.5 List rows

Row height 72px, virtualised beyond 200 rows.

```
┌──────────────────────────────────────────────────────────────────┐
│ ☐  ▲  Fade Lab                                    $1,860  ›      │
│       Ybor City · Booksy · read 6h ago                            │
│       ████████████████████░░░░  88% open · 38 of 56   ◉ High     │
└──────────────────────────────────────────────────────────────────┘
   ^checkbox            ^VacancyBar (12px)         ^ConfidenceBadge
```

Hover: row lifts (`--raise`), the matching pin pulses once. Click row → detail drawer. Click name → same. `Space` → toggle select.

### 5.6 Bulk actions

Selection via checkbox, `Shift+click` range, header `select all (filtered)` which explicitly says **"Select all 28 matching"** not "all". Selection bar rises from the bottom of the list (`--raise`, 56px, coral count badge):

`28 selected` → **`Make 28 pitches`** (primary; opens a batch job producing a single merged PDF + a zip of one-pagers) · `Add to watchlist` · `Export CSV` · `Assign to…` (agency/BE) · `Re-scan selected` (costs quota — shows the cost inline: `Re-scan 28 · uses 28 of your 1,200 remaining`) · `Mark passed` · `Clear`.

Batch pitch generation shows a `ScanProgress`-style inline progress in the bar itself, and each finished one-pager is downloadable as it completes — same progressive-reveal principle.

### 5.7 Mobile / tablet — "standing outside the shop"

This is a real moment: the consultant is on the sidewalk, about to walk in, phone in one hand. Design for **one thumb, bright sun, 4G, 30 seconds.**

**Phone (<768px) — Radar becomes a bottom-sheet map:**

```
┌───────────────────────────┐
│ ⌂ Barbershop · Tampa   ⚲ │  ← 48px toolbar, ⚲ = filters (sheet)
├───────────────────────────┤
│                           │
│          MAP              │  ← full bleed, ~58vh
│      ● you are here       │  ← blue dot + accuracy halo
│                           │
├═══════════════════════════┤  ← drag handle
│ ▲ Fade Lab      0.1 mi    │  ← sheet, 3 snap points:
│ 88% open · $1,860         │     peek 96px / half 50vh / full
│ ████████████████░░  ◉High │
├───────────────────────────┤
│ ■ Kingsman Cuts  0.3 mi   │
└───────────────────────────┘
```

- **`Near me` is the default sort on phone.** The whole reason they're on a phone is that they're standing somewhere.
- **The walk-in card.** Tapping a row on phone does **not** open the full desktop detail. It opens a purpose-built `WalkInCard` — one screen, no scroll:
  - Name, distance, `Open now / Closed` from hours
  - `88% open this week` + `$1,860` at 34px
  - **The one sentence they will say**, in a copyable box, 15px, high contrast: *"I noticed you had 38 open slots this week — mostly Tuesday afternoons."* with a `Copy` button and a `🔊` read-aloud (so they can rehearse it in the car).
  - Three 56px-tall thumb buttons in a row: **`Call`** (tel:) · **`Text`** (sms: prefilled) · **`Directions`**
  - Secondary row: `Show grid` (opens the SlotGrid in day-swipe mode) · `Email pitch` · `Save to watchlist`
  - Bottom: the ConfidenceBadge and a one-line integrity note (never omitted on mobile).
- **Offline / bad signal:** the last-viewed 50 rows and their walk-in cards are cached in `localStorage` (name, score, $, the sentence, the grid as a pre-rendered PNG). A `⚡ Offline — showing saved data` bar appears. **The grid image is cached specifically so they can show the owner the screenshot with no signal.** This is the single highest-value offline feature.
- **Sunlight:** minimum body contrast on mobile is raised — `--txt2` is not used for any primary content on the WalkInCard; everything is `--txt` or the accent. Tap targets ≥48px. No hover-only affordance anywhere on the touch path.
- **Tablet (768–1279px):** rail collapses to icons, Split view keeps map + list but list drops to 320px and loses the address line. Landscape tablet = desktop layout.

---

## 6. S5 — Business Detail

### 6.1 Page structure (drawer on desktop ≥1100px at 620px wide; full page below)

```
┌ ← Radar / Barbershop / Fade Lab ──────────────────── ☆ Watch  ⋯ ┐
│                                                                  │
│  Fade Lab                                    ┌────────┐          │
│  1802 E 7th Ave, Ybor City · Booksy ↗        │  88    │  ScoreDial│
│  ▲ HUNGRY   ◉ High confidence   read 6h ago  │ % open │          │
│                                              └────────┘          │
│                                                                  │
│  $1,860  unsold this week                                        │  ← 48px
│  38 online-bookable open slots of 56 · avg ticket $49 (their menu)│  ← 13px --txt2
│                                                                  │
│  [ Make the pitch ]  [ Add to watchlist ]  [ Re-scan ]            │
├──────────────────────────────────────────────────────────────────┤
│  Availability  |  Dead zones  |  Ledger  |  What we saw          │  ← .tabs
├──────────────────────────────────────────────────────────────────┤
│  ... tab body ...                                                │
└──────────────────────────────────────────────────────────────────┘
```

Above the fold, always: name, bucket, confidence, the `%`, the `$`, the primary button. Everything else is a tab.

### 6.2 Rendering the availability grid legibly

**The problem:** 7 days × up to 8 staff × up to 28 half-hour slots = up to 1,568 cells. Unrenderable and unreadable.

**The solution: three levels of disclosure, and the default level is deliberately lossy.**

#### Level 1 — Week Density (default, always visible)
Aggregate across staff. **7 day-columns × N hour-rows** where N is the union of business hours (typically 9am–8pm = 11 rows at 1-hour granularity). Each cell is a **filled-proportion block**: the cell is `--surface2`, and a coral bar fills it from the bottom by `openSlots / bookableSlots` for that hour across all staff.

```
        MON   TUE   WED   THU   FRI   SAT   SUN
 9am   ▓▓░░  ████  ▓▓▓░  ▓▓░░  ░░░░  ░░░░   —
10am   ▓░░░  ████  ▓▓░░  ▓░░░  ░░░░  ░░░░   —
11am   ░░░░  ████  ▓░░░  ░░░░  ░░░░  ░░░░   —
12pm   ▓▓░░  ████  ▓▓▓░  ▓▓░░  ▓░░░  ░░░░   —
 1pm   ▓▓▓░ ┏████┓ ████  ▓▓▓░  ▓▓░░  ░░░░   —   ┐
 2pm   ▓▓▓░ ┃████┃ ████  ▓▓▓░  ▓▓░░  ░░░░   —   ├ DEAD ZONE
 3pm   ▓▓░░ ┗████┛ ▓▓▓░  ▓▓░░  ▓░░░  ░░░░   —   ┘
 4pm   ▓░░░  ▓▓▓░  ▓▓░░  ▓░░░  ░░░░  ░░░░   —
 5pm   ░░░░  ▓▓░░  ▓░░░  ░░░░  ░░░░  ▒▒▒▒   —   ← ▒ = not readable
 6pm   ░░░░  ▓░░░  ░░░░  ░░░░  ░░░░  ▒▒▒▒   —
 7pm    —     —     —     —     —     —     —   ← — = closed
      ─────────────────────────────────────────
 open  32%   91%   58%   36%   14%    4%    —   ← per-day footer, always numeric
```

Encoding rules:
- **Fill height** = open proportion (primary channel). **Fill colour** = the vacancy stop for that value (secondary, redundant). **Both** are used, so it survives greyscale and CVD.
- Every column has a **numeric footer** (`open %`) and every row a numeric right-gutter on hover/focus. Colour never carries information alone.
- `—` closed (no fill, `--faint` dash) · `▒` diagonal hatch = **not readable** (distinct from "booked", which is empty fill). These three must be visually unmistakable at a glance; hatch is the redundant channel for "unknown".
- Cell size 44×32px desktop (fits 7×11 in ~380px wide), 30×26px on tablet. On phone: **one day per screen with horizontal swipe + a day strip**, never a squeezed 7-column grid.
- Hover/focus → tooltip: `Tue 1:00–2:00pm · 6 of 8 slots open · 3 of 4 staff readable`.

#### Level 2 — Day × Staff (click a day header, or a cell)
The clicked day expands **in place**, pushing the rest of the grid down (accordion, 240ms):

```
 ▼ TUESDAY — 91% open · 21 of 23 bookable slots open
   ┌─────────┬────────────────────────────────────────────────┐
   │ Marcus  │ ░░▓▓████████████████████████░░  9a────────8p  │  ← 30-min ticks
   │ Dee     │ ░░░░████████████████████████░░                 │
   │ Trey    │ ▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒  not readable    │
   │ (chair 4)│ ────────── no online booking ──────────       │
   └─────────┴────────────────────────────────────────────────┘
```

One horizontal **lane per staff member**, time on x. Open = coral filled, booked = `--surface2`, unreadable = hatch, off = flat line. Staff who exist but have no readable calendar are **still listed by name** with an explicit label — never dropped (P3).

#### Level 3 — Slot list (click a lane segment)
A small popover listing literal times: `1:00pm · 1:30pm · 2:00pm · 2:30pm — open` with a `Copy times` button. This is what the consultant reads aloud on the phone.

#### Collapsed / expanded state summary
| State | Trigger | Shows |
|---|---|---|
| Collapsed (default) | page load | Level 1 only, ~380×420px |
| Day expanded | click day header / cell / `Enter` on cell | Level 1 + one Level 2 block. Only one day at a time (accordion). |
| All days expanded | `Expand all` ghost button | Level 2 for all 7. Height ~1400px; scroll. Used for the printed one-pager appendix. |
| Slot popover | click a lane segment | Level 3 |
| Screenshot mode | `Camera` icon | Renders Level 1 + the expanded dead-zone day at 2× into a PNG with a watermark bar (`OpenChair · Fade Lab · read Mar 4`) — this is the image embedded in the one-pager. |

### 6.3 Dead-zone heat overlay (OTO1)

The Dead-Zone Map is **not a separate chart** — it is an **overlay on the same Level 1 grid**, toggled by a switch above it (`Dead zones ⦿`). When on:
- Cells whose **8-week mean open-rate ≥ 70%** get a 1.5px coral outline and a subtle coral glow (`box-shadow: inset 0 0 0 1.5px var(--coral), 0 0 12px -2px var(--coraldim)`).
- Contiguous dead cells merge into a **bracketed region** with a label on the right gutter: `DEAD ZONE · Tue 1–4pm · empty 8 of last 8 weeks`.
- Beneath the grid, a one-line plain-English readout, which is the sentence that goes into the pitch:
  > **Tuesday afternoons are chronically empty.** 1–4pm has been ≥75% open for 8 weeks running. That's where the offer goes.
- Sorting/severity: regions ranked by `meanOpenRate × slotCount × avgTicket` = `$ recoverable`, and the top region shows `≈ $640/wk recoverable`.
- Cells with <3 weeks of history are excluded from dead-zone marking and shown with a small `new` dot — **we never call something chronic from one reading.**
- **Gated (FE):** the toggle is present and enabled once (the free unlock, §2.3). Otherwise flipping it renders the overlay **blurred at 6px** over the real grid for 1.2s, then settles to the `.of-nokey` line. Blur-then-explain, not blur-forever.

### 6.4 ConfidenceBadge

Three levels, computed and **explained on click** (a popover, not a tooltip — it must be readable on touch):

| Level | Rule (indicative) | Badge |
|---|---|---|
| **High** ◉ | ≥90% of expected staff calendars read, page fetched <24h ago, no adapter warnings | teal dot + `High` |
| **Medium** ◑ | 60–89% read, or 24–72h old, or 1 warning | amber half-dot + `Medium` |
| **Low** ◔ | <60% read, or >72h old, or blocked mid-read | grey quarter-dot + `Low` |

Popover content: `4 of 4 staff calendars read · fetched 6h ago · Booksy adapter healthy · 14-day window`. Shape (dot fill) is the redundant channel; colour alone never distinguishes them.

### 6.5 "What we could not see" — the integrity block (P3, first-class)

**This is a required, always-rendered, above-the-tab-fold-when-relevant component.** It is not fine print, not a tooltip, not collapsed by default when it has content.

Placement: it is the 4th tab (`What we saw`) **and** a condensed always-visible strip directly beneath the SlotGrid. If there is anything we could not read, the strip is amber-tinted and expanded.

```
┌ WHAT WE COULD AND COULDN'T SEE ─────────────────────────────────┐
│                                                                  │
│ ✓ We read   4 staff calendars · 14 days · 56 bookable slots      │
│             Booksy public booking page, fetched 6h ago            │
│                                                                  │
│ ⚠ We could NOT see                                               │
│   · Trey's calendar — the page didn't load his chair (retrying)   │
│   · Saturday after 5pm — not offered for online booking           │
│   · Walk-ins, phone bookings, blocked-out time, staff days off    │
│                                                                  │
│ ℹ What this number means                                         │
│   "$1,860 unsold" = 38 online-bookable open slots × their listed  │
│   average ticket ($49). It is a measure of *bookable* time that   │
│   is unsold online — not a claim that the shop is empty.          │
│                                                                  │
│                        [ Copy this disclosure ]  [ Report a gap ] │
└──────────────────────────────────────────────────────────────────┘
```

Design requirements:
1. Uses the word **"online-bookable"** every time the figure is restated. Enforced in copy, not left to the writer.
2. `Copy this disclosure` puts the block's text on the clipboard — because the honest consultant will want to paste it into the email, and making that easy is how we get the honest behaviour.
3. The **same block, abbreviated to 2 lines, is compulsory on the one-pager** (§7.3). It cannot be toggled off, even by white-label. White-label can restyle it; it cannot remove it.
4. Rendered even when everything was read: then it's teal-tinted, `✓ We read everything this platform exposes` + the ℹ paragraph. Presence is constant; tone varies.

---

## 7. S6 — Pitch Composer

### 7.1 Layout

```
┌ Pitch · Fade Lab ───────────────────────────────────── [ Compare ▣ ] ┐
├─────────────────────────────┬────────────────────────────────────────┤
│ CONTROLS (420px)            │  PREVIEW (flex, dark canvas)           │
│                             │                                        │
│ Variant                     │   ┌──────────────────────────┐        │
│ [▲ Hungry] [● Slammed]      │   │                          │        │
│   ↑ auto-selected by bucket │   │      the one-pager       │        │
│                             │   │   rendered at 816×1056   │        │
│ Angle                       │   │   scaled to fit, white   │        │
│ ○ Fill the dead zone        │   │                          │        │
│ ○ Midweek offer             │   │                          │        │
│ ○ Review velocity           │   └──────────────────────────┘        │
│ ○ LossReel video            │                                        │
│                             │   [One-pager] [Email] [SMS] [Voicemail]│
│ Tone    ▁▂▃▄▅               │                                        │
│  Blunt ─────●──── Warm      │   [ Print / PDF ] [ Send ] [ ⧉ Copy ]  │
│ Length  ▁▂▃                 │                                        │
│  Tight ──●────── Full       │                                        │
│                             │                                        │
│ Include                     │                                        │
│ ☑ Availability screenshot   │                                        │
│ ☑ Dead-zone callout   ▲OTO1 │                                        │
│ ☑ Dollar figure             │                                        │
│ ☑ Disclosure block  (locked)│  ← checkbox present but disabled+ticked│
│ ☐ 8-week trend        ▲OTO2 │                                        │
│                             │                                        │
│ Your details                │                                        │
│ [name][phone][email][logo]  │                                        │
│                             │                                        │
│ [ ✨ Rewrite ]              │                                        │
└─────────────────────────────┴────────────────────────────────────────┘
```

- **Variant auto-selects from the bucket** and is the demo's beat 12 flip. Switching variant regenerates copy and swaps the fix list; the numbers stay identical (same scan — say so in a mono line under the toggle: `same scan · different offer`).
- **Tone / Length are 5-stop and 3-stop sliders, not free text.** This buyer will not write a prompt. Stops are labelled words, and each has a one-line example preview under the slider so the choice is concrete.
- **Regeneration streams** into the preview (P2 perceived speed) and is debounced 400ms on slider release.
- **`Compare ▣`** shows Hungry and Slammed one-pagers side by side at 50% — this is the webinar's closing frame; make it a real feature, not a demo hack.

### 7.2 The two variants

| | **▲ Hungry** (≥60% open) | **● Slammed** (≤5% open / next-avail ≥14d) |
|---|---|---|
| Headline | `You have 38 open chairs this week.` | `You're booked 19 days out.` |
| Sub | `That's about $1,860 of online-bookable time going unsold.` | `That's roughly $2,900 a week walking away.` |
| Proof | The grid, open slots highlighted | The grid, wall-to-wall booked + the next-available date circled |
| Diagnosis | `Your quiet block is Tuesday 1–4pm — 8 weeks running.` | `You have no waitlist and no deposit. Every no-show is a dead chair.` |
| The fix (3 bullets) | Midweek dead-zone offer · Google offer post · Review velocity push | Waitlist automation · Deposit gate · Price increase justified by the wait (or: open a chair / hire) |
| CTA | `Give me 15 minutes and I'll show you how I'd fill Tuesdays.` | `Give me 15 minutes and I'll show you how to stop turning money away.` |
| Money framing | `$ unsold` | `$ turned away` |

The Slammed variant's revenue line uses a **different, clearly-labelled estimate** (`walk-away estimate`) and its own confidence caveat — we must not imply we measured lost customers. `≈` prefix mandatory.

### 7.3 The one-pager — print spec

This is a physical artifact. Spec it at print dimensions, not "responsive."

- **Canvas:** US Letter portrait, `816 × 1056 px` @96dpi (`8.5in × 11in`). A4 variant `794 × 1123 px` selectable in Settings; layout is identical with the extra 67px absorbed by the footer margin.
- **Margins:** 48px (0.5in) all sides. Live area `720 × 960`.
- **Print CSS:** `@page{size:letter;margin:0}`, `-webkit-print-color-adjust:exact; print-color-adjust:exact` (the grid MUST print in colour), `break-inside:avoid` on every zone. Single page is a hard requirement — if content overflows, the **fix list truncates to 2 bullets**, never the disclosure.
- **Colour on paper:** the one-pager is **light** (white paper), using the light token set — this is the one OpenChair surface that is not dark. It inherits the instantclose cream palette exactly (`--bg:#F3EAE4` header band, coral accents), which is why the two products look related in print too.

```
816 × 1056 (0.5in margins)
┌──────────────────────────────────────────────────────────┐  y=48
│ [consultant logo 140×40]              prepared for       │  Zone A · 72px
│                                       FADE LAB           │
│                                       Mar 4, 2026        │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  You have 38 open chairs                                 │  Zone B · 150px
│  this week.                                              │  Fraunces/Outfit 44px, 2 lines max
│                                                          │
│  About $1,860 of online-bookable time going unsold.      │  18px --txt2
├──────────────────────────────────────────────────────────┤
│  ┌────────────────────────────────┐  ┌────────────────┐  │
│  │                                │  │  88%           │  │  Zone C · 340px
│  │   AVAILABILITY GRID IMAGE      │  │  open          │  │  grid 460px wide,
│  │   (2× PNG from §6.2)           │  │                │  │  stat rail 200px
│  │   7 days · your Booksy page    │  │  38 / 56       │  │
│  │   read Mar 4                   │  │  slots open    │  │
│  │                                │  │                │  │
│  │        ┏━━━━┓ ← Tue 1–4pm      │  │  $1,860        │  │
│  └────────────────────────────────┘  │  unsold/week   │  │
│                                      └────────────────┘  │
├──────────────────────────────────────────────────────────┤
│  Your quiet block is Tuesday 1–4pm — empty 8 weeks       │  Zone D · 90px
│  running. That's where an offer goes.                    │  coral left rule 3px
├──────────────────────────────────────────────────────────┤
│  WHAT I'D DO                                             │  Zone E · 190px
│  1. Tuesday-only midweek offer, promoted Mon 6pm         │  3 numbered items
│  2. Google offer post pinned to your profile             │  with 1-line each
│  3. Review-velocity push to lift Saturday walk-ins       │
├──────────────────────────────────────────────────────────┤
│  Numbers here count online-bookable open slots on your   │  Zone F · 64px
│  public Booksy page (read Mar 4). They don't include     │  10px, --mute,
│  walk-ins, phone bookings or blocked time.               │  MANDATORY
├──────────────────────────────────────────────────────────┤
│  Nakul Sharma · 813-555-0134 · nakul@example.com         │  Zone G · 40px
└──────────────────────────────────────────────────────────┘  y=1008
```

Zone F is **non-removable**. In the composer its checkbox renders ticked and `disabled` with the tooltip `Required — this is what keeps the number credible.` That is a deliberate design statement, and it is also the thing that stops a refund-triggering "your tool lied to me" phone call.

### 7.4 Email + SMS variants

- **Email tab:** editable subject (default `38 open chairs at Fade Lab this week`), 120–160 word body, the one-pager auto-attached as PDF **and** the grid inlined as a PNG (many owners read on phone and won't open a PDF). A `Plain / Rich` toggle — plain text sends better and is the default. Merge-token chips (`{{business}}`, `{{open_slots}}`, `{{dollars}}`, `{{dead_zone}}`) are insertable via a chip row, never typed.
- **SMS tab:** hard 320-char counter with a live segment meter (`1 of 2 segments`), no links longer than a short domain, and an explicit compliance strip: `Cold SMS to a business number — check your local rules. [DNC list ↗]` (the engine already has a compliance/DNC lane; link into it).
- **Voicemail tab (OTO3):** a 20-second script sized to a ringless-voicemail drop, with a word-count-to-seconds readout.
- All three share the same numbers object; changing tone regenerates all three at once so they can't drift.

### 7.5 LossReel handoff

A card at the bottom of the composer:

```
┌ Turn this into a 30-second video ────────────────── LossReel ─┐
│ We'll hand LossReel: the grid screenshot, the $1,860 figure,  │
│ the dead-zone line, and your branding.                        │
│                                       [ Send to LossReel → ]  │
│  OTO3 · 4 of 10 credits left                     QuotaMeter   │
└───────────────────────────────────────────────────────────────┘
```

Behaviour: click → a confirm sheet listing **exactly what leaves OpenChair** (a payload preview, plain-language) → posts to LossReel, returns a job id, and the card becomes a status card (`Rendering… ~90s`) that persists on the Prospect record. When done, the video URL appears on the Prospect card and as a link in the email variant. If the user doesn't own LossReel, the card renders as a tease with a 12-second sample video, not a padlock — video sells itself.

---

## 8. S8 — Ledger (one component, two jobs)

**The insight from the brief:** the prospecting artifact and the retainer report are the same screen. So build **one** `LedgerReport` component with a `mode` prop, and let the *data* do the talking.

| | `mode="prospect"` (before) | `mode="client"` (after) |
|---|---|---|
| Who sees it | The consultant, pitching | The business owner, monthly |
| Data | 8 weeks of history, all pre-engagement | Same series + an **engagement marker** at the week work started |
| Headline | `Fade Lab has been ≥60% open for 8 straight weeks.` | `Fill rate: 41% → 68% since we started.` |
| Chart | Same TrendSparkline, big | Same chart + a vertical coral rule labelled `We started here` and the post-marker area tinted teal |
| Framing | Problem | Progress |
| Branding | OpenChair (FE) | **White-label** (OTO2): consultant's logo/colours/domain |
| Delivery | In-app + PDF | PDF + emailed monthly + a shareable link on the consultant's domain |

**One component, mode-switched.** Concretely: `LedgerReport` takes `{ businessId, weeks[], engagedAtWeek|null, brand, mode }`. If `engagedAtWeek` is non-null, `mode` defaults to `client` and the delta framing turns on. That's it — no second screen, no duplicated chart code. The mode toggle is visible to the consultant as a two-tab control (`Prospecting view` / `Client view`) so they can preview what the owner will receive.

**Screen body (both modes):**
1. Header: business, period, brand block, `Prospecting view | Client view` tabs.
2. Big stat row: `Current fill rate` · `8-week average` · `Best week` · `Δ since start` (client mode only, big and teal).
3. **TrendSparkline, large** — 8–52 week line of *fill rate* (inverse of vacancy, because "fill going up" is the story), with the vacancy band shaded behind. Hollow dots for weeks with no scan; dashed segment across gaps.
4. **Dead-zone drift matrix** — the DeadZoneMatrix rendered as a small-multiple: one mini week-grid per month, so you can literally see Tuesday afternoons filling in. This is the killer retention visual.
5. Money row: `Unsold this week` vs `Unsold 8 weeks ago` with the delta in dollars and an annualised line.
6. `What we could not see` block (§6.5), abbreviated but present.
7. Footer: `Next scan: tonight 2:00am` + `Report generated automatically on the 1st`.

**White-label controls (OTO2, S14):**
| Control | Field | Applied to | Validation |
|---|---|---|---|
| Logo | PNG/SVG upload, ≤512KB | Report header, one-pager Zone A, emailed PDF | min 280×80px, transparent bg recommended; live preview |
| Wordmark fallback | Text | Used if no logo | ≤28 chars |
| Accent colour | Hex picker + 6 presets | Buttons, chart line, rules | **Contrast-checked live** against white and against `--bg`; if it fails 4.5:1 we auto-derive a darker `--brand-ink` for text and say so: `We'll use a darker shade for text so it stays readable.` (Mirrors the existing `$brand_accent` override in `layout.php`.) |
| Report footer | Text, ≤160 chars | Report + PDF footer | — |
| Custom domain | `reports.theiragency.com` | Shareable report links | CNAME instructions + status `pending → live → broken`, reusing the engine's existing custom-domain status idiom |
| From-name / reply-to | Text + email | Monthly report email | verified-sender note |
| "Powered by OpenChair" | Toggle | Report footer | **On for FE/OTO1, off-able only at OTO2** |
| Disclosure block | — | — | **Not configurable.** Restylable, not removable. |

A `Preview as client` button renders the full report in the client's shoes (white-label applied, consultant-only controls hidden) — one click, one Esc back.

---

## 9. Component library

All components live under `openchair/ui/components/`. States listed are the ones that **must** be implemented; anything not listed falls back to `default`.

| Component | Purpose | Props | States |
|---|---|---|---|
| **ScoreDial** | The vacancy % as a single glanceable ring | `value:0-100`, `size:'sm'|'md'|'lg'` (44/72/120px), `bucket`, `label?`, `showGlyph=true` | `default` · `loading` (ring at 0, shimmer track) · `empty` (dashed track, `—` centre) · `error` (grey track, `!`) · `stale` (ring at 60% opacity + `Nd` badge) · `gated` (blurred value, padlock centre) |
| **VacancyBar** | Horizontal open/booked bar for list rows and cells | `open:int`, `bookable:int`, `height=12`, `showNumbers=true`, `hatchUnknown:int` | `default` · `loading` (shimmer full width) · `empty` (`--surface2` track, `no data`) · `partial` (hatched segment for unread) · `error` (grey + `!`) · `stale` (50% opacity) |
| **BucketChip** | Hungry / Balanced / Slammed / Unread | `bucket`, `count?`, `size`, `interactive?` | `default` · `active` · `hover` · `disabled` · `gated` (padlock, real count still shown) · `loading` (count shimmer) |
| **ConfidenceBadge** | How much we actually read | `level:'high'|'med'|'low'`, `detail:{staffRead,staffTotal,fetchedAt,adapter,warnings[]}` | `default` · `open` (popover) · `loading` · `unknown` (`?`, grey, `Not yet read`) |
| **SlotGrid** | The 3-level availability grid (§6.2) | `days[]`, `staff[]`, `granularity:'30m'|'1h'`, `level:1|2|3`, `expandedDay?`, `deadZones[]?`, `readonly?` | `default` · `loading` (skeleton at exact final height) · `empty` (`This business exposes no online booking`) · `partial` (hatched unread cells + named unread staff) · `error` (amber card + last-good CTA) · `stale` (banner + `≈` prefixes) · `capture` (2× screenshot render, no interactivity) |
| **DeadZoneMatrix** | Chronic-empty overlay + the small-multiple month view | `cells[][]` (mean open-rate), `weeks:int`, `threshold=0.7`, `variant:'overlay'|'multiples'` | `default` · `loading` · `insufficient` (`Needs 3+ weeks — we have 1`) · `none` (`No chronic dead zone found — their gaps move around`) · `gated` (blur-then-explain, §6.3) · `error` |
| **TrendSparkline** | 8–52 week fill-rate trend | `points[]`, `size:'inline'|'card'|'report'`, `marker?:{week,label}`, `band?` | `default` · `loading` · `insufficient` (`2 points needed` + shows the 1 point) · `gaps` (dashed across missing weeks, hollow dots) · `error` · `stale` |
| **PitchCard** | The one-pager preview tile / the Compare pane | `variant:'hungry'|'slammed'`, `data`, `brand`, `scale`, `printable` | `default` · `generating` (copy streams in, numbers already final) · `fallback` (deterministic template, amber `Written from your data, not AI` chip) · `error` · `print` (816×1056, no chrome) |
| **ScanProgress** | The streaming scan rail + the global strip | `job`, `variant:'rail'|'strip'|'inline'`, `counts`, `recent[]` | `discovering` (indeterminate) · `resolving` (determinate) · `paused` · `partial-fail` (amber, `Keep what we found`) · `done` (collapses to a 1-line summary + `View 47 results`) · `error` |
| **StaleDataBanner** | "This is old" | `lastScanAt`, `scope:'row'|'screen'`, `onRescan`, `quotaCost?` | `fresh` (renders nothing) · `aging` (3–7d, `--mute` text line) · `stale` (>7d, amber banner + `Re-scan`) · `very-stale` (>30d, amber + the `$` figures gain `≈` and a struck-through tooltip) |
| **QuotaMeter** | Scans / cities / niches / credits remaining | `used`, `limit`, `unit`, `resetAt`, `compact?` | `ok` (<70%) · `warn` (70–94%, amber) · `critical` (≥95%, coral + `Top up`) · `exhausted` (blocks the action inline, explains when it resets — **never** a modal) · `unlimited` (`∞`, teal) · `loading` |
| *(supporting)* **PinMarker** | Map pin | `vacancy`, `revenue`, `bucket`, `state` | `default` · `hover` · `focus` · `selected` · `seen` · `watchlisted` · `unread` (hollow dashed) · `clustered` |
| *(supporting)* **IntegrityBlock** | §6.5 "what we couldn't see" | `read{}`, `missed[]`, `meaning`, `tone:'ok'|'warn'` | `ok` (teal) · `warn` (amber, expanded) · `severe` (nothing read — becomes the whole page) |
| *(supporting)* **WalkInCard** | The phone one-screen card (§5.7) | `business`, `distance`, `sentence` | `default` · `offline` (cached, `⚡` bar) · `stale` · `loading` |

Every component must accept a `data-testid` and render its numeric value in the accessible tree even when the visual encoding is graphical.

---

## 10. Design tokens

### 10.1 Colour — dark surface ramp (derived from the instantclose espresso block)

```css
:root{
  /* ── Surfaces: the --sb* espresso family expanded into a 6-step ramp ── */
  --bg:        #15100D;   /* page                          */
  --surface:   #1F1813;   /* card                          */
  --surface2:  #2A1E18;   /* == instantclose --sb          */
  --raise:     #33251E;   /* hover / popover / sheet       */
  --sunken:    #100C0A;   /* map plate, inputs, code       */
  --line:      #33251E;   /* hairline                      */
  --line2:     #43332B;   /* == instantclose --sb-line     */

  /* ── Ink ── */
  --txt:       #FAEEE7;   /* == --sb-txt   · 15.9:1 on --bg */
  --txt2:      #C7ADA0;   /* == --sb-txt2  ·  8.4:1 on --bg */
  --mute:      #9A7E71;   /* == --sb-mute  ·  5.0:1 on --bg */
  --faint:     #6E5A50;   /*  2.4:1 — DECORATIVE ONLY, never text */

  /* ── Accent: unchanged from instantclose ── */
  --coral:     #F0755E;
  --coral2:    #E75C43;
  --coraldim:  rgba(240,117,94,.16);
  --coralring: rgba(240,117,94,.34);

  /* ── Semantics: instantclose hues lifted for dark-surface contrast ── */
  --teal:      #2FBF8C;   /* was --green #1FA274 → 7.1:1 on --bg */
  --amber:     #E8A33A;   /* was #C07A12         → 8.7:1 */
  --blue:      #5AA9F0;   /* was #2E74B5         → 7.4:1 */
  --violet:    #9B82FF;   /* was #7A5AF0         → 6.6:1 */
  --danger:    #FF6A52;
  --tealdim:   rgba(47,191,140,.14);
  --amberdim:  rgba(232,163,58,.14);

  /* ── Radii / type / motion: identical to instantclose ── */
  --r1:12px; --r2:16px; --r3:24px; --r4:30px; --rpill:999px;
  --disp:'Outfit',system-ui,sans-serif;
  --body:'Manrope',system-ui,sans-serif;
  --mono:'Space Mono',ui-monospace,monospace;
  --serif:'Fraunces',Georgia,serif;

  /* ── Elevation (dark: shadow + a top hairline, since shadows read weakly) ── */
  --e1: 0 1px 0 rgba(255,238,231,.04), 0 8px 20px -14px rgba(0,0,0,.7);
  --e2: 0 1px 0 rgba(255,238,231,.05), 0 18px 40px -24px rgba(0,0,0,.8);
  --e3: 0 1px 0 rgba(255,238,231,.06), 0 32px 64px -30px rgba(0,0,0,.9);
  --focus: 0 0 0 3px var(--coralring);
}
```

**Light counterpart (one-pager + printed Ledger only):** import the instantclose `:root` verbatim (`--bg:#F3EAE4; --surface:#FFF; --line:#ECDDD4; --txt:#1F1712; --coral:#F0755E; --green:#1FA274`). Same accent, same radii, same fonts — that's why the print artifact and the app look related.

### 10.2 Type scale

| Token | px / line | Family / weight | Use |
|---|---|---|---|
| `--t-mono` | 10 / 1.4, `ls 1.3px`, uppercase | Space Mono 700 | The `.lbl` micro-label above every stat. House signature — use it. |
| `--t-mono-sm` | 9 / 1.3 | Space Mono 700 | Nav group headers, pin captions |
| `--t-xs` | 11 / 1.45 | Manrope 500 | Captions, chip text, timestamps |
| `--t-sm` | 12.5 / 1.5 | Manrope 600 | Table cells, secondary rows |
| `--t-base` | 14 / 1.55 | Manrope 400 | Body |
| `--t-md` | 15 / 1.65 | Manrope 500 | Lede paragraphs, the walk-in sentence |
| `--t-lg` | 18 / 1.35 | Outfit 600 | Card titles |
| `--t-xl` | 21 / 1.3 | Outfit 600 | Section heads |
| `--t-2xl` | 26 / 1.2 | Outfit 600, `ls -.6px` | Sub-hero numbers ("$22,400 so far") |
| `--t-3xl` | 34 / 1.1 | Outfit 600, `ls -1px` | Page `h1` (matches `.pagehead h1`) |
| `--t-4xl` | 48 / 1.0 | Outfit 600, `ls -1.6px` | **The Empty-Chair Revenue figure.** Reserved. |

All numerals: `font-variant-numeric: tabular-nums` on every stat, table cell and grid footer — mandatory, non-negotiable for scanability.

### 10.3 Spacing / sizing

Base 2, scale `2 4 6 8 10 12 14 18 22 26 34 44 60` (`--s0…--s12`). Matches the existing shell's ad-hoc values, now named.

| Thing | Value |
|---|---|
| Card padding | `20px 22px` (`.card.pad`) |
| Grid gutter | 14px (cards), 18px (columns) |
| Page padding | `18px clamp(16px,3vw,34px) 60px` |
| Content max-width | 1280px — **except S4 Radar (full-bleed)** and S6 preview |
| Row height | list 72px · dense table 52px · mobile 84px |
| Touch target | ≥44px desktop, **≥48px** on the mobile walk-in path |
| Filter rail | 280px open / 48px collapsed |
| Detail drawer | 620px |
| Slot cell | 44×32 desktop · 30×26 tablet · full-width day on phone |

### 10.4 The vacancy scale — 7 stops (and the colour-blind decision)

**The problem.** The brief says "red to green." A literal red→green ramp is the single most common accessibility failure in data products, and ~8% of men (the majority of this buyer base) can't read it. It would also fail on a projector at a webinar.

**What I did.** Three things:

1. **Replaced green with teal.** The ramp runs **coral-red → sand → teal**, not red → green. Under deuteranopia and protanopia, red and green collapse onto each other; **red and teal do not**, because teal retains a blue component that survives both. Under tritanopia the coral end stays distinct. To a normal-vision user teal still reads as "green / good", so the marketing line "red to green" remains true.
2. **Made it a diverging lightness curve** — darkest at both ends, lightest at the neutral midpoint. So in pure greyscale (a photocopied one-pager, a bad projector) you can still tell "extreme" from "middle", even if you can't tell which extreme.
3. **Because greyscale still can't tell the two extremes apart, colour is never the only channel.** Redundant encodings, everywhere, no exceptions:
   - **Bucket glyph** `▲` hungry / `■` balanced / `●` slammed / `?` unread — on every pin, chip, row and legend entry.
   - **The literal number** is printed adjacent to every colour-encoded element (pin peek, row, grid column footer, cell tooltip).
   - **Fill height/length** in VacancyBar and SlotGrid cells duplicates the value positionally.
   - **Hatch pattern** for "not readable", which is categorically different from any colour on the ramp.

```
VACANCY (% of online-bookable slots open) — dark-surface stops

  0–9%    V0  #0E8570   ● SLAMMED    L*≈49   deep teal
 10–24%   V1  #22A98A   ●            L*≈62
 25–39%   V2  #5FC9A8   ■            L*≈74
 40–54%   V3  #E8C766   ■ BALANCED   L*≈81   sand (neutral midpoint, lightest)
 55–69%   V4  #FFA94D   ■            L*≈76
 70–84%   V5  #FF8352   ▲            L*≈68
 85–100%  V6  #FF5C43   ▲ HUNGRY     L*≈60   hot coral (accent-adjacent)

  unread      #6E5A50   ?            hollow + dashed stroke + 45° hatch
  closed      #2A1E18   —            flat, no fill
```

- The hungry end (`#FF5C43`) sits deliberately one step from the house `--coral #F0755E` — hungry prospects glow in the brand colour. That is the emotional point of the product.
- Contrast on `--bg #15100D`: every stop ≥4.6:1 (V0 lowest at ~4.7:1). Text placed **on** a stop uses `#15100D` ink for V2–V6 and `#FAEEE7` for V0–V1; the rule is `L* > 68 ? dark ink : light ink`.
- Stops are exposed as `--v0…--v6` and as a JS array so the map, bars and grid cannot drift.
- **Do not interpolate between stops.** Seven discrete buckets read faster than a continuous ramp and make the legend learnable in one glance.

### 10.5 Motion

| Token | Value | Use |
|---|---|---|
| `--dur-1` | 140ms | chips, buttons, hovers |
| `--dur-2` | 240ms | drawers, accordions, map recentre |
| `--dur-3` | 380ms | route transitions |
| `--ease` | `cubic-bezier(.2,.7,.3,1)` | everything |
| pin drop | 180ms, staggered 60ms | scan reveal |
| new-row flash | 900ms coral left edge, fades | scan reveal |

`@media (prefers-reduced-motion: reduce)` — all durations → 0.01ms, pin drop becomes a single 200ms opacity batch, shimmer becomes a static tint, the coral flash becomes a persistent 3px left border that decays after 4s. **The counters still count** (motion of numbers is information, not decoration).

---

## 11. Accessibility & heuristics review

### 11.1 Contrast targets

| Content | Target | Notes |
|---|---|---|
| Body text on any surface | **≥ 4.5:1** | `--txt`/`--txt2` pass everywhere; `--mute` passes on `--bg`/`--surface` only — **never** put `--mute` on `--raise` |
| Large text (≥18.66px bold / ≥24px) | ≥ 3:1 | The 48px `$` figure uses `--txt` anyway (15.9:1) |
| UI component boundaries, focus ring, chart strokes | **≥ 3:1** | vacancy stops all ≥4.6:1 on `--bg` |
| Text on a vacancy stop | ≥ 4.5:1 | via the L*>68 ink rule (§10.4) |
| `--faint` | — | **Decorative only.** Lint rule: `--faint` may not be used as `color` on any element containing text. |
| White-label accent | auto-checked | If the user's hex fails, derive `--brand-ink` and tell them (§8) |

Target level: **WCAG 2.2 AA**, with AAA on the walk-in card body text (it's read in sunlight).

### 11.2 Keyboard paths

Global: `/` focus search · `g` then `t/r/p/l` go to Today/Radar/Prospects/Ledger · `?` shortcuts sheet · `Esc` closes the topmost layer, one layer per press (drawer → sheet → popover), never more.

**Focus order for the demo flow (beats 1–13) — this is a spec, test it:**
1. Skip link (`.ic-skip-link`, reused verbatim) → 2. Search field (auto-focused on S1 empty) → 3. `Scan →` → *(route)* → 4. ScanProgress rail's `Open first pitch` (auto-focused when it enables; announced) → 5. `Map region` (single tab stop, `role="application"`, `aria-label` describing the roving-pin interaction) → 6. Roving focus through pins, **ordered by vacancy descending** so `Tab, Enter` lands on the reddest pin → 7. Peek card → 8. `Make the pitch` → 9. Detail drawer (focus trapped, `Esc` returns to the originating pin, restoring the exact scroll and map position) → 10. SlotGrid → 11. Composer variant toggle → 12. `Print / PDF`.

Grid keyboard model: arrow keys move cell-to-cell (`Home`/`End` = row start/end, `PgUp`/`PgDn` = week), `Enter` expands the day, `Esc` collapses. The grid is **one tab stop** with a roving `tabindex`, never 98 tab stops.

Focus ring: `box-shadow: var(--focus)` + a 1px `--txt` inner line so it's visible on both `--surface` and on a saturated pin. `:focus-visible` only for mouse, always-on for keyboard.

### 11.3 Screen-reader model

**SlotGrid** — a real `role="grid"` with `role="columnheader"` days and `role="rowheader"` hours, not a div soup:
- Grid label: `aria-label="Availability for Fade Lab, 7 days from March 4. 38 of 56 online-bookable slots open."`
- Cell: `aria-label="Tuesday 1 PM. 6 of 8 slots open. 3 of 4 staff readable. Dead zone: empty 8 of the last 8 weeks."` — value first, caveats after.
- Unreadable cell: `aria-label="Saturday 5 PM. Not readable — this platform doesn't publish it."` (never "0 open").
- Column footers are in the accessible tree as a summary row.
- A visually-hidden `<table>` alternative behind a `View as table` control — a real, focusable, exportable table of the same data. This also serves users who just prefer numbers.

**Map** — `role="application"` with a described interaction model, and an always-present, non-visually-hidden **`List` view toggle** as the equivalent path. The map is never the only way to reach any business.

**Live regions:**
- Scan progress: `aria-live="polite"` on a throttled summary that announces at most every 5 seconds — `"23 of 47 scanned. 8 hungry, 2 slammed."` Never announce per-pin (it would be a firehose).
- The `Open first pitch` unlock: `aria-live="assertive"` once — `"First result ready. Open first pitch."`
- Filter changes: polite — `"28 businesses match."`
- Errors: assertive, and the message must name the recovery action.

**Charts:** every TrendSparkline / DeadZoneMatrix carries `role="img"` + a full-sentence `aria-label` containing the actual trend (`"Fill rate rose from 41 percent to 68 percent over 8 weeks"`), plus a `<figcaption>`-style visible caption. The legend is a `<figure>`/`<figcaption>`, not a hover.

### 11.4 Heuristics review (Nielsen, applied)

| Heuristic | How we satisfy it | Where it would have failed |
|---|---|---|
| Visibility of system status | ScanProgress with real counts + the global 3px strip + `Next scan: tonight 2am` on every stale surface | A spinner during a 3-minute crawl |
| Match to the real world | "open chairs", "booked out 19 days", "$ unsold" — the owner's own words | "vacancy coefficient", "OpenChair Score" |
| User control | Scans are cancellable, keep-partial is offered, `Esc` unwinds one layer, filters are chips you can see and remove | A wizard you can't leave; filters hidden in a collapsed rail |
| Consistency | Reuses the instantclose shell, chips, buttons, pager, tooltip, skip link; `$` is formatted identically in 6 places | A second design language for a second product |
| Error prevention | Re-scan shows its quota cost *before* the click; bulk "select all" says "all 28 matching"; the disclosure checkbox can't be unticked | Silently burning quota; a one-pager with no caveat |
| Recognition over recall | Persistent legend, glyphs on chips *and* pins, niche cards with logos instead of a dropdown | A colour legend behind a hover |
| Flexibility | Saved views, keyboard shortcuts, three view modes, `Near me` default on phone — all optional, none required | Power features that block novices |
| Aesthetic & minimal | One 48px number per screen; the map is the only saturated surface | Nine KPI cards on the home screen |
| Error recovery | Every error names the cause, the last-good data, and one recovery button; pitch generation always falls back to a deterministic template | "Something went wrong" |
| Help | Contextual: the ConfidenceBadge popover, the integrity block, the `[why?]` link on every failure, `?` shortcuts sheet | A 40-page manual tab |

---

## 12. Low-fidelity wireframes

Annotations: `▓` filled/emphasis · `░` empty/track · `▒` hatch (unreadable) · `┈` fold line. Everything above the `┈` fold is visible at 1440×900 without scrolling.

### W1 — S2 Scan Setup

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  ◱ OpenChair    ( Today · Radar · Prospects · Ledger · Coverage )   ? ⚙ (NS) │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  NEW SCAN                                              ← t-mono label        │
│  Where are you working today?                          ← 34px Outfit         │
│  Pick a city and up to 3 niches. We scan every business with public booking. │
│                                                                              │
│  ┌───────────────────────────────┐  ┌────────────────────────────────────┐  │
│  │ CITY                          │  │ SCAN DEPTH                         │  │
│  │ [ Tampa, FL            × ]    │  │ ○ City centre (~5mi)               │  │
│  │  ↑ typeahead, geocoded        │  │ ● Whole metro (~15mi)   ← default  │  │
│  │  1 of 1 city slots used  ▲FE  │  │ ○ Custom radius        ▲OTO1       │  │
│  └───────────────────────────────┘  └────────────────────────────────────┘  │
│                                                                              │
│  NICHE — pick up to 3                                    2 of 3 selected     │
│  ┌────────┬────────┬────────┬────────┬────────┬────────┐                    │
│  │ ✂ Barb │ 💅Nail │ 💇Hair │ 💆Spa  │ 🦷Dent │ 🧘Chiro│  ← .bp-card grid   │
│  │  ▓ON   │        │  ▓ON   │        │        │        │     4 cols × 2 rows│
│  │ Booksy │ Vagaro │ Fresha │ Boulev │ Zocdoc │ Janeapp│  ← platforms we see│
│  │ ~38 biz│ ~52 biz│ ~44 biz│ ~19 biz│ ~31 biz│ ~12 biz│  ← live estimate   │
│  ├────────┼────────┼────────┼────────┼────────┼────────┤                    │
│  │ 🖋Tattoo│ 💪PT   │ 🐕Groom│ 🚗Deto │ 🍽Rest │ 🏥Med  │                    │
│  │        │        │        │        │  ▲BE   │  ▲BE   │  ← backend adapters│
│  └────────┴────────┴────────┴────────┴────────┴────────┘                    │
│                                                                              │
│  We'll scan ~82 businesses. First results in about 40 seconds.               │
│  Uses 82 of your 1,200 monthly scans.        [QuotaMeter ▓▓▓▓░░░░ 1,200]     │
│                                                                              │
│  [ Scan now → ]   [ Schedule nightly ]                                       │
│ ┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈│
│  Recent scans · Barbershop Tampa (6h) · Nail salon Tampa (2d)                │
└──────────────────────────────────────────────────────────────────────────────┘
```
Above the fold: everything needed to launch a scan. The niche grid is the hero — it *teaches coverage* (platform names under each card) while collecting input. The quota cost is shown before the button, not after the click.

### W2 — S3 Scan Progress (the progressive reveal)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  ◱ OpenChair    ( Today · Radar · … )                              ? ⚙ (NS) │
├──────────────────────────────────────────────────────────────────────────────┤
│ ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓░░░░░░░░░░░░░░░░░░░░░░░  ← 3px global strip, coral      │
├──────────────────────────────────────┬───────────────────────────────────────┤
│                                      │  SCANNING                             │
│         ·  ·      ▲                  │  Barbershop · Tampa, FL               │
│      ·      ●   ·    ·               │                                       │
│   ·    ▲  ·   ·    ▲   ·             │  ▓▓▓▓▓▓▓▓▓▓░░░░░░░░  23 / 47          │
│      ·    ·  ▲  ·      ·   ·         │                                       │
│  ·   ●  ·      ·   ▲   ·             │   8  hungry     ▲                     │
│     ·  ·   ▲     ·   ·               │   2  slammed    ●                     │
│        ·      ·    ·                 │   3  couldn't read  ⚠ [why?]          │
│                                      │                                       │
│   ↑ grey dots = discovered           │  $22,400  unsold found so far         │
│     glyphs = resolved, coloured      │  ← 26px Outfit, ticks up              │
│                                      │                                       │
│   [legend: 0% ▓▓▓▓▓▓▓ 100% open]     │  ─ live ──────────────────────────────│
│                                      │  ▲ Fade Lab          88% open   ✓     │
│                                      │  ● Kingsman Cuts     12% open   ✓     │
│                                      │  ◌ Tampa Barber Co.  reading…  ░░     │
│                                      │  ? Clip Joint        blocked    ⚠     │
│                                      │                                       │
│                                      │  [ Open first pitch → ]  ← enables at │
│                                      │  You can leave — we'll email you.     │
└──────────────────────────────────────┴───────────────────────────────────────┘
```
Above the fold: the whole thing. This screen never scrolls. The `$` counter and the live feed carry the drama; the progress bar is secondary. **This IS the map screen already** — when the job finishes, the rail cross-fades to the FilterRail and nothing else moves.

### W3 — S4 Radar, desktop (the hero)

```
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  ◱ OpenChair    ( Today · ▓Radar · Prospects · Ledger · Coverage )          ? ⚙ (NS) │
├──────────────────────────────────────────────────────────────────────────────────────┤
│ Barbershop · Tampa FL ▾   47 scanned  28 ▲  6 ●  $54,120 unsold   Sort: Most $ ▾  ⌗ │
│ [niche: Barbershop ×] [conf: High,Med ×] [≤3 days ×]              Map|List|▓Split    │
├─────────────┬──────────────────────────────────────────┬─────────────────────────────┤
│ BUCKET      │  ┌ legend ─────────┐                     │ 28 results                  │
│ ┌─────────┐ │  │0%▓▓▓▓▓▓▓100%open│                     │ ┌─────────────────────────┐ │
│ │▲ Hungry │ │  │ ▲ hungry ■ bal  │        ▲            │ │☐ ▲ Fade Lab    $1,860 ›│ │
│ │   28  ▓ │ │  │ ● slammed ? n/a │   ▲        ●        │ │  Ybor·Booksy·6h ago    │ │
│ ├─────────┤ │  └─────────────────┘      ▲              │ │  ▓▓▓▓▓▓▓▓▓▓░░ 88% ◉High│ │
│ │■ Balanced│ │                    ▲            ▲        │ ├─────────────────────────┤ │
│ │   13    │ │        ▲     (12)  ← cluster donut       │ │☐ ▲ Clipper Co  $1,540 ›│ │
│ ├─────────┤ │              ●        ▲     ■            │ │  Seminole·Vagaro·1d    │ │
│ │● Slammed│ │     ▲                                     │ │  ▓▓▓▓▓▓▓▓░░░░ 79% ◑Med │ │
│ │   6   🔒│ │           ■      ▲        ?              │ ├─────────────────────────┤ │
│ └─────────┘ │                                           │ │☐ ■ Kingsman     $610  ›│ │
│ 🔒 Slammed  │              ▲          ●                 │ │  Hyde Pk·Fresha·6h     │ │
│ is Pro. See │                                           │ │  ▓▓▓▓░░░░░░░░ 41% ◉High│ │
│ what it     │                          ▲                │ ├─────────────────────────┤ │
│ looks like →│                                           │ │☐ ? Clip Joint  couldn't│ │
│             │                                           │ │  read · Booksy [why?]  │ │
│ SCORE       │                                           │ ├─────────────────────────┤ │
│ 0 ●───●100  │                                           │ │ … virtualised …        │ │
│ ▁▂▅█▅▂▁     │                                           │ └─────────────────────────┘ │
│             │                                           │                             │
│ UNSOLD $    │                                           │                             │
│ $0 ●──● $3k │                                           │                             │
│ ┈┈┈┈┈┈┈┈┈┈┈ │ ┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈ │ ┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈ │
│ PLATFORM    │                                           │                             │
│ CONFIDENCE  │                                           │                             │
│ LAST SCAN   │                                           │                             │
│ [Clear all] │                                           │                             │
└─────────────┴──────────────────────────────────────────┴─────────────────────────────┘
     280px                      flexible                            420px
```
Above the fold: the toolbar aggregate (`$54,120` — the number that sells the product), the three bucket tiles with real counts, ~15 pins, the legend, and the top 4 list rows. Fold falls below `UNSOLD $` in the rail. The Slammed lock is a calm inline line in the rail, with a "see what it looks like" link listed before any upgrade link.

### W3b — S4 Radar, phone (the sidewalk)

```
┌─────────────────────────┐   ┌─────────────────────────┐
│ ⌂ Barbershop·Tampa   ⚲ │   │ ← Fade Lab           ☆ │  ← WalkInCard
├─────────────────────────┤   ├─────────────────────────┤
│      ▲                  │   │ ▲ HUNGRY    ◉ High      │
│  ▲       ●     ▲        │   │ 0.1 mi · Open now       │
│      ▲      ▲           │   │                         │
│   ●     ◉you            │   │  88% open this week     │
│      ▲        ■         │   │  $1,860                 │  ← 34px
│  ▲       ▲              │   │  38 of 56 online-       │
│                         │   │  bookable slots         │
├═════════════════════════┤   │                         │
│ ▲ Fade Lab      0.1 mi  │   │ ┌─────────────────────┐ │
│ 88% open · $1,860       │   │ │ "I noticed you had  │ │  ← the sentence
│ ▓▓▓▓▓▓▓▓▓▓░░  ◉ High    │   │ │ 38 open slots this  │ │     copyable +
├─────────────────────────┤   │ │ week — mostly Tues  │ │     read-aloud
│ ■ Kingsman      0.3 mi  │   │ │ afternoons."    ⧉ 🔊│ │
│ 41% open · $610         │   │ └─────────────────────┘ │
│ ▓▓▓▓░░░░░░░░  ◉ High    │   │                         │
├─────────────────────────┤   │ [ Call ][ Text ][ Map ] │  ← 56px thumb row
│ ● Sharp Fade    0.4 mi  │   │                         │
│ 4% open · booked 19d    │   │ [Show grid][Email pitch]│
│ ▓░░░░░░░░░░░  ◑ Med     │   │                         │
└─────────────────────────┘   │ ⓘ Online-bookable slots │  ← never omitted
   sort: Near me (default)    │   only. Not walk-ins.   │
                              └─────────────────────────┘
```

### W4 — S5 Business Detail

```
┌──────────────────────────────────────────────────────────────────────────┐
│ ← Radar / Barbershop Tampa / Fade Lab                    ☆ Watch    ⋯    │
├──────────────────────────────────────────────────────────────────────────┤
│                                                            ╭────────╮    │
│  Fade Lab                                                  │  ╭──╮  │    │
│  1802 E 7th Ave, Ybor City · Booksy ↗                      │ │ 88 │ │    │
│  ▲ HUNGRY    ◉ High confidence    read 6h ago              │ │ %  │ │    │
│                                                            │  ╰──╯  │    │
│  $1,860  unsold this week                       ← 48px     │ open   │    │
│  38 online-bookable open slots of 56 · avg ticket $49      ╰────────╯    │
│                                                                          │
│  [ Make the pitch ]  [ Add to watchlist ]  [ Re-scan · 1 scan ]           │
├──────────────────────────────────────────────────────────────────────────┤
│  ▓Availability │ Dead zones │ Ledger │ What we saw                       │
├──────────────────────────────────────────────────────────────────────────┤
│  Mar 4 – Mar 10        [Dead zones ⦿]  [Expand all]  [📷 Screenshot]     │
│                                                                          │
│        MON   TUE   WED   THU   FRI   SAT   SUN                           │
│  9am   ▓▓░░  ████  ▓▓▓░  ▓▓░░  ░░░░  ░░░░   —                            │
│ 10am   ▓░░░  ████  ▓▓░░  ▓░░░  ░░░░  ░░░░   —                            │
│ 11am   ░░░░  ████  ▓░░░  ░░░░  ░░░░  ░░░░   —                            │
│ 12pm   ▓▓░░  ████  ▓▓▓░  ▓▓░░  ▓░░░  ░░░░   —                            │
│  1pm   ▓▓▓░ ┏████┓ ████  ▓▓▓░  ▓▓░░  ░░░░   —  ┐                         │
│  2pm   ▓▓▓░ ┃████┃ ████  ▓▓▓░  ▓▓░░  ░░░░   —  ├ DEAD ZONE               │
│  3pm   ▓▓░░ ┗████┛ ▓▓▓░  ▓▓░░  ▓░░░  ░░░░   —  ┘ Tue 1–4pm · 8/8 wks     │
│  4pm   ▓░░░  ▓▓▓░  ▓▓░░  ▓░░░  ░░░░  ░░░░   —    ≈$640/wk recoverable    │
│  5pm   ░░░░  ▓▓░░  ▓░░░  ░░░░  ░░░░  ▒▒▒▒   —                            │
│  6pm   ░░░░  ▓░░░  ░░░░  ░░░░  ░░░░  ▒▒▒▒   —                            │
│       ─────────────────────────────────────────                          │
│ open   32%   91%   58%   36%   14%    4%    —   ← always numeric         │
│ ┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈│
│  Tuesday afternoons are chronically empty. That's where the offer goes.  │
│                                                                          │
│ ┌ WHAT WE COULD AND COULDN'T SEE ────────────────────── ⚠ amber ───────┐ │
│ │ ✓ Read: 4 staff · 14 days · 56 bookable slots · Booksy · 6h ago      │ │
│ │ ⚠ Not seen: Trey's chair (didn't load) · Sat after 5pm (not offered) │ │
│ │   · walk-ins, phone bookings, blocked time, staff days off           │ │
│ │ ℹ "$1,860 unsold" = 38 online-bookable open slots × $49 avg ticket.  │ │
│ │   Not a claim the shop is empty.       [Copy disclosure] [Report gap]│ │
│ └──────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────┘
```
Above the fold: name, bucket, confidence, `88%`, `$1,860`, the primary button, and the top ~5 rows of the grid. The disclosure block is one scroll away and is impossible to miss — it is a bordered, tinted card, not a footnote.

### W5 — S6 Pitch Composer + one-pager

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ ← Fade Lab / Pitch                                     [ ▣ Compare variants ] │
├──────────────────────────────┬───────────────────────────────────────────────┤
│ VARIANT   same scan · diff   │                                               │
│  [ ▲ Hungry ▓ ][ ● Slammed ] │   ┌───────────────────────────────────────┐   │
│                              │   │ [logo]              prepared for      │   │
│ ANGLE                        │   │                     FADE LAB          │   │
│  ● Fill the dead zone        │   │                     Mar 4, 2026       │   │
│  ○ Midweek offer             │   ├───────────────────────────────────────┤   │
│  ○ Review velocity           │   │                                       │   │
│  ○ LossReel video    ▲OTO3   │   │  You have 38 open chairs              │   │
│                              │   │  this week.                           │   │
│ TONE                         │   │  About $1,860 of online-bookable      │   │
│  Blunt ───●─── Warm          │   │  time going unsold.                   │   │
│  "Your Tuesdays are dead."   │   ├───────────────────────────────────────┤   │
│                              │   │ ┌───────────────────┐ ┌───────────┐  │   │
│ LENGTH                       │   │ │  ▓▓░░████▓▓▓░░░   │ │ 88% open  │  │   │
│  Tight ──●──── Full          │   │ │  GRID SCREENSHOT  │ │ 38 / 56   │  │   │
│                              │   │ │   ┏━━━┓ Tue 1-4   │ │ $1,860    │  │   │
│ INCLUDE                      │   │ └───────────────────┘ └───────────┘  │   │
│  ☑ Availability screenshot   │   ├───────────────────────────────────────┤   │
│  ☑ Dead-zone callout  ▲OTO1  │   │ ▌Your quiet block is Tue 1–4pm —      │   │
│  ☑ Dollar figure             │   │ ▌empty 8 weeks running.               │   │
│  ☑ Disclosure  🔒 required   │   ├───────────────────────────────────────┤   │
│  ☐ 8-week trend       ▲OTO2  │   │ WHAT I'D DO                           │   │
│                              │   │ 1. Tuesday-only midweek offer         │   │
│ YOUR DETAILS                 │   │ 2. Google offer post, pinned          │   │
│  [Nakul Sharma           ]   │   │ 3. Review-velocity push               │   │
│  [813-555-0134           ]   │   ├───────────────────────────────────────┤   │
│  [nakul@example.com      ]   │   │ Counts online-bookable open slots on  │   │
│  [ ⬆ logo ]                  │   │ your public Booksy page (read Mar 4). │   │
│                              │   ├───────────────────────────────────────┤   │
│  [ ✨ Rewrite ]              │   │ Nakul Sharma · 813-555-0134           │   │
│ ┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈ │   └───────────────────────────────────────┘   │
│ ┌ LossReel ─────────────────┐│    [One-pager ▓] [Email] [SMS] [Voicemail]   │
│ │ 30-sec video from this    ││                                               │
│ │ pitch.  4/10 credits      ││    [ Print / PDF ]  [ Send ]  [ ⧉ Copy ]     │
│ │        [ Send to LossReel ]│                                               │
│ └───────────────────────────┘│                                               │
└──────────────────────────────┴───────────────────────────────────────────────┘
        420px controls                     flexible preview (816×1056 scaled)
```
Above the fold: variant toggle, tone/length, and the **entire one-pager** at whatever scale fits. The artifact is never below the fold — it is the product.

### W6 — S8 Ledger (both modes, one component)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ ← Fade Lab / Ledger              [ Prospecting view ▓ | Client view ]   ⬆ PDF│
├──────────────────────────────────────────────────────────────────────────────┤
│  [consultant logo]                              CAPACITY REPORT              │
│  Fade Lab · Barbershop · Ybor City         Jan 8 – Mar 4, 2026 (8 weeks)     │
├──────────────────────────────────────────────────────────────────────────────┤
│  ┌────────────┬────────────┬────────────┬────────────┐                       │
│  │ FILL RATE  │ 8-WK AVG   │ BEST WEEK  │ Δ SINCE WE │                       │
│  │   68%      │   52%      │   71%      │  STARTED   │                       │
│  │            │            │  (Feb 26)  │  ▲ +27 pts │ ← client mode only   │
│  └────────────┴────────────┴────────────┴────────────┘   teal, 34px          │
│                                                                              │
│  FILL RATE, LAST 8 WEEKS                                                     │
│ 100%│                                                          ╭──●          │
│     │                                              ╭───●───●───╯             │
│  50%│  ●───●───●┈┈┈┈┈●───●╮   ┊we started here╭────╯                        │
│     │            ↑gap      ╰──●───────────────╯                              │
│   0%└──────────────────────┊───────────────────────────────────────          │
│      W1  W2  W3  W4  W5  W6┊ W7  W8  W9  W10 W11 W12                        │
│                    ↑hollow ┊ ↑ coral rule + label; area right of it tinted   │
│                     = no scan                                                │
│ ┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈│
│  DEAD ZONES, MONTH BY MONTH        ← DeadZoneMatrix, variant="multiples"     │
│   ┌─ JAN ──┐  ┌─ FEB ──┐  ┌─ MAR ──┐                                        │
│   │▓▓█▓░░░ │  │▓▓█▓░░░ │  │▓▓▓▓░░░ │   Tuesday afternoons filled in.        │
│   │▓▓█▓░░░ │  │▓░█▓░░░ │  │▓░▓░░░░ │   Friday mornings are the new gap.     │
│   │▓░█░░░░ │  │▓░▓░░░░ │  │░░▓░░░░ │                                        │
│   └────────┘  └────────┘  └────────┘                                        │
│                                                                              │
│  UNSOLD TIME     this week $980   ·   8 weeks ago $1,860   ·   ▼ −$880/wk    │
│                  ≈ $45,760 of capacity recovered per year                    │
│                                                                              │
│  ┌ WHAT WE COULD AND COULDN'T SEE ────────────────────────────────────────┐  │
│  │ ✓ Read 4 staff calendars weekly since Jan 8. ⚠ 2 weeks missed (W4, W6).│  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│  Next scan: tonight 2:00am · Report emailed on the 1st · Powered by OpenChair│
└──────────────────────────────────────────────────────────────────────────────┘
```
Prospecting mode is the identical layout with `engagedAtWeek = null`: no coral rule, no `Δ` card, no teal tint, and the headline stat becomes `8 weeks ≥60% open` instead of `+27 pts`. One component, one template, two stories.

---

## 13. Implementation notes for the prototype workstream

1. **Reuse, don't reimplement:** `.ic-skip-link`, `[data-tip]` tooltip, `_pager.php`, `icTableSort()`, `.btn/.chip/.tchip/.card/.kpi/.tabs/.empty/.pill` class names and behaviour. Only the token values change.
2. Ship the vacancy stops as **one** exported array consumed by map, bars and grid. Any hardcoded hex outside `tokens.css` is a bug.
3. `prefers-reduced-motion` and the greyscale/CVD check are part of "done", not polish.
4. `?demo=1` must satisfy every beat of §3 with zero network. Build that fixture first — it's the webinar, and the webinar is the launch.
5. **Do not ship any screen without its empty, loading, partial and error states from §4.3.** On this product the error states *are* the product integrity story.
