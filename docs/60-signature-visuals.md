# 60 — Signature Visuals: goal and integration plan

**Source:** `OpenChair_Signature_Visuals.pdf` (8 pages, supplied by the founder, Oct 2026).
**Status:** plan approved for build in this change. Every page of the PDF maps to a shipped surface below.

---

## 1. Goal

> **Give a consultant one picture that sells the meeting.**
> A business owner should understand, in five seconds and from their own calendar, either
> *"my chairs sit empty"* (Hungry) or *"I'm turning people away"* (Slammed) — and the consultant
> should be able to send that picture from the app in two taps, under their own agency's name.

The PDF replaces the abstract "Vacancy Score" with **one number an owner is proud or ashamed of —
the Chair Score** — and wraps it in five shareable cards plus a redesigned business screen that
produces them. Conversion comes from three things the cards do that the old screens did not:

1. **Higher = busier.** A full shop is proud of 94; a quiet one hears a plain fact at 41.
2. **Chair by chair, hour by hour.** The owner recognises their own Tuesday afternoon.
3. **Their street, not an average.** "You scored 41. The shop 3 blocks away scored 97."

### Success criteria (definition of done)

| # | Criterion | How it is checked |
|---|---|---|
| G1 | Every one of the 8 PDF pages exists as a working surface in the app | §3 mapping table, each row links a route |
| G2 | The Chair Score, its band and its money agree on **every** screen for the same business | one helper (`oc_chair_*`) feeds every view; integrity test |
| G3 | Cards obey the product-integrity rules (05-prd §8) | integrity suite extended: ranges, data age, "online-bookable only", no other shop named on a card, gap below the floor |
| G4 | A consultant can produce a share link, a phone-size image and a one-page PDF from the business screen | manual walk + screenshots in §8 |
| G5 | A share link is public, read-only, expires in 30 days, counts opens, and is **frozen** (it never changes after it is sent) | `oc_share_link` migration + public controller |
| G6 | Nothing contacts the business | no Send control; existing rule-7 checks stay green |
| G7 | All existing checks stay green; new checks added for the new rules | `php …/integrity_test.php` |

---

## 2. What the PDF specifies (page by page)

| Page | Visual | Key content | Notes for build |
|---|---|---|---|
| 1 | **Chair Week — Hungry** | Name, area, chairs, week; HUNGRY pill; big score `41/100`; "165 of your 280 online-bookable chair-hours sat empty"; "≈ $2,900–$4,100 unsold this week"; Mon–Sun × 9a–6p grid, one tile per chair; per-day `EMPTY 45/50`; legend incl. hatched walk-in chair; footer method + data age + platform; "Prepared by [YOUR AGENCY]" | Mon shut; Sun short hours |
| 2 | **Chair Week — Slammed** | SLAMMED pill (black); `94/100`; "A new client's first opening online is **9 days** away. Similar shops nearby: 2 days."; mostly-booked grid; `FULL` day badge; "Only 12 of 200 online chair-hours went unbooked all week." | Wait days replace money |
| 3 | **Chair Map** | "You scored 41. The shop 3 blocks away scored 97."; map with 1-mile ring, pins carrying each shop's score, colour by band (red empty, green busy, black-ringed full, dashed `?` no online booking); rank / area average / unsold tiles | Other shops are **anonymised** ("Shop C · 8 chairs") |
| 4 | **Receipt** | Till-receipt styling: per-day EMPTY HRS and UNSOLD range sorted worst-first; worst-window note (`↳ 1–5PM: ALL 5 CHAIRS EMPTY`); totals; Chair Score line; "NOT ON THIS RECEIPT"; barcode = the week hour by hour | Mono type throughout |
| 5 | **Same Tuesday** | One day, every chair, three shops side by side (you + 2 nearby busier shops); `45 of 50 chair-hours empty` each; dark callout "Tuesday isn't a dead day on your street…" | Day = the business's worst day |
| 6 | **How the Chair Score works** | Definition; "how it sounds in the shop" quote; 0–100 scale with bands (Hungry <50, Steady 50–84, Slammed 85+) and markers; per-band "second number"; method rules (last check before slot start; walk-in chairs left out; "Not enough data yet" until 5+ days and 70% seen; method version on every card) | In-app explainer page, dark card |
| 7 | **8-week report** | `Chair Score 41 → 63`; band change pill; "61 more chair-hours booked a week"; unsold before (struck) / after; line chart with area-average dashed line and an annotated campaign start; before/after mini Chair Weeks | Same method version for every week |
| 8 | **Business screen (in-app)** | New rail (Work / Agency); breadcrumb; header with band pill, data age, Watching; Assigned to; Make pitch / Share card; pipeline stepper with "Next:" action; 4 stat tiles (Chair Score ±margin, Unsold this week, On the street rank, Trend); Chair Week panel; "What we couldn't see" list; **Share card** panel (pick card, show agency logo, copy link, download image, download PDF, 30-day expiry note); Activity | The production surface that makes pages 1–7 |

---

## 3. Mapping to the app

| PDF page | Surface | Route (also under `/app/…`) | Files |
|---|---|---|---|
| 1, 2 | Chair Week card | `openchair/card/{id}/week` | `views/visuals/_chair_week.php` |
| 3 | Chair Map card | `openchair/card/{id}/map` | `views/visuals/_chair_map.php` |
| 4 | Receipt card | `openchair/card/{id}/receipt` | `views/visuals/_receipt.php` |
| 5 | Same Tuesday card | `openchair/card/{id}/tuesday` | `views/visuals/_same_day.php` |
| 6 | How it works | `openchair/how-it-works` | `views/pages/how_it_works.php` |
| 7 | 8-week report card + Ledger header | `openchair/card/{id}/progress` | `views/visuals/_progress.php` |
| 8 | Business screen | `openchair/business/{id}` | `views/pages/business.php` (rewritten) |
| 8 | Share card actions | `openchair/share` (POST) → `/c/{token}` (public) | `controllers/Oc_share.php`, `models/Oc_share_model.php` |
| 8 | Rail | every screen | `views/oc_shell/_oc_rail.php` |

Card pages render in their **own chrome** (like the one-pager): no rail, print-ready at A4/Letter,
and a `?size=phone` variant at 390 px wide for the phone-size image.

---

## 4. Architecture

```
oc_business (global, unchanged) ─┐
oc_vacancy_weekly ───────────────┤
oc_discovery_candidate (? pins) ─┤──► Oc_visuals_lib ──► $card data (one array per card)
oc_prospect (tenant) ────────────┘        │                    │
                                          │                    ├─► views/visuals/_*.php (one partial per card)
                         oc_format_helper: oc_chair_*()        ├─► business.php (inline Chair Week + tiles)
                                                               └─► Oc_share_model::create() freezes it as JSON
                                                                         └─► /c/{token} renders the frozen copy
```

### 4.1 The Chair Score (one definition, one helper)

* `chair_score = 100 − round(vacancy_bp / 100)` — the share of online-bookable chair-hours that got
  booked. It is the existing Meter number, inverted so that **higher = busier**. No new scoring math.
* Bands (Chair Score **v1.0**, PDF p.6): **Hungry < 50**, **Steady 50–84**, **Slammed ≥ 85**.
  The scoring model's lead-time override is kept: **next opening ≥ 10 days ⇒ Slammed** regardless of
  score (35-scoring §4.2) — a shop a new client cannot get into for ten days is turning people away.
* Below the confidence floor, or under 70% of hours seen: **"Not enough data yet"** — no number,
  no band, no money (rule 3).
* `±margin` = `max(2, round((100 − confidence) / 2.5))` — wider as confidence falls.
* Helpers: `oc_chair_score()`, `oc_chair_score_or_gap()`, `oc_chair_band()`, `oc_chair_band_label()`,
  `oc_chair_band_need()`, `oc_chair_margin()`, `oc_method_version()`.
* The bucket filter and counts on Radar/Today use the **same** band rule in SQL
  (`Oc_business_model::band_sql()`), so a chip, a pin, a pill and a card never disagree (G2).

### 4.2 The Chair Week grid

`Oc_visuals_lib::chair_week($b)` returns days × hours × chairs, each tile one of
`empty | booked | unseen | shut`, plus per-day and weekly totals and unsold ranges.

* **Source.** Until Radar runs (the spike, STRUCTURE §8), the grid is **derived**: deterministic per
  business (seeded PRNG), with *exactly* `round(vacancy × bookable)` empty tiles so the grid, the
  score and the "165 of 280" sentence always agree. Empty tiles are weighted toward each shop's
  chronic dead zones (Tue/Wed afternoons for Hungry shops). Every derived card says
  `source: derived` in its payload and prints "Sample data" when the market is a demo market.
  When `oc_scan_snapshot` is populated, `chair_week()` reads the observed grid instead — the views
  do not change, only the source (same contract as the existing illustrative grid).
* **Hours.** 9a–6p rows (10 hours). Monday is shut for barbershops; Sunday is short (10a–3p) or shut.
* **Walk-in chairs.** A shop with 6+ chairs has its last chair marked walk-in only: hatched, excluded
  from the score, listed under "what we couldn't see".
* **Unseen hours.** Below 95% confidence a timed-out check window is hatched as unseen and stated
  ("Thu 2–4pm: one check timed out (6% of hours unseen this week)").

### 4.3 Money (rule 4)

The headline range is **the Meter's own band** (`latest_empty_chair_cents` … `_hi_cents`) — the
format helper is explicit that a range must not be re-derived in PHP. The card splits that range
across days in proportion to each day's empty hours (largest-remainder rounding so the days sum to
the total). The footnote names the price source as "your own menu prices ($X–Y/hr)", where the
hourly band is read from `oc_business_service` when present, otherwise from `avg_price_cents`.

> **Decision D-1.** The PDF's footnote says "Money counts half your open hours". The shipped footnote
> describes the Meter's actual method instead ("counts only the share of open hours that could
> realistically sell"), because a card must never describe a calculation it did not do. If the
> founder wants the literal 50% rule, it is one config value (`oc_card_sell_share`) and a Meter change,
> not a view change.

### 4.4 Neighbours (map and Same Tuesday)

`Oc_business_model::nearby($b, $miles)` — same city and niche, haversine distance, nearest first.
Radius starts at 1 mile and widens (1.5 → 2 → 3) until at least 4 visible shops are found; the card
prints the radius actually used. `?` pins come from `oc_discovery_candidate` rows that were found
but have no online booking. On a card every other shop is **"Shop A…Shop H"** by distance, never by
name (PDF p.8: "Other shops are never named on a card").

### 4.5 Share links

New table `oc_share_link` (migration `oc_010_share_cards.sql`): token (32 hex), user, business,
card kind, show-logo flag, **frozen JSON payload** (the exact card data and brand at the moment the
link was made), `expires_at` (+30 days), `open_count`, `last_opened_at`. Public controller
`Oc_share` serves `/c/{token}` with no login, `noindex`, Open Graph tags for iMessage/WhatsApp
previews, and a 410 page once expired. Opens are counted, not tracked (no IP stored).

Also in `oc_010`: `oc_prospect.assigned_user_id` and `oc_prospect.next_due_on` for the
"Assigned to" and "Next: … due Mon, Oct 20" lines.

### 4.6 Exports

* **Download one-pager (PDF)** — the card page with `?print=1` calls the browser's print dialog
  ("Save as PDF"); print CSS sizes it to one page. No server-side PDF dependency.
* **Download image (phone size)** — `assets/js/oc-card-export.js` renders the card DOM to a PNG at
  390×(auto) @2x, in the browser, using an SVG `foreignObject` with the fonts inlined. It renders
  **our own markup**; it is not screen-capture machinery and never touches a booking platform's page.

### 4.7 Rail (PDF p.8)

```
+ New scan                  (button → /openchair/scan)
WORK     Home [n due] · Find businesses · My pipeline [n] · Client reports
AGENCY   How it works · What we can see · Team & roles* · Brand & white-label* · API & webhooks**
         (Adapters* stays under Agency)
ACCOUNT  Settings · Log out · Theme
foot     Agency plan · used/cap · Sample data flag
```
`*` admin only, `**` superadmin only. Keys are unchanged (`today`, `radar`, `prospects`, `ledger`,
`coverage`, …) so the NAV-CONTRACT and every controller keep working; only labels, grouping and the
new `how` key change. Business and Pitch light up **My pipeline** when the business is watched,
otherwise **Find businesses**. The old search topbar is retired: "+ New scan" replaces its Scan
button and the sample-data flag moves to the rail foot.

---

## 5. Integrity rules applied to the cards

| Rule (05-prd §8) | How a card obeys it |
|---|---|
| 2 never "empty shop" | Unit is "online-bookable chair-hours"; footers say "Online-bookable slots only" |
| 3 confidence floor | Below floor: "Not enough data yet", no number/band/money; the share action is disabled |
| 4 range, named source | Every $ is a range from `oc_money()`; footnote names the price source |
| 5 what we couldn't see | Legend hatch + footnote on every card; full list on the business screen |
| 6 data age | "Checked Oct 15, 2026 (2 days ago)" on every card |
| 7 never contact | Share = a link/image the consultant sends. No Send button; `Oc_share` is read-only |
| 10 never publish rankings | Share pages are `noindex`, token-gated, expire; other shops are anonymised |

---

## 6. Work breakdown (build order)

| # | Work package | Owner | Depends on |
|---|---|---|---|
| W0 | Plan + goal (this doc) | lead | — |
| W1 | Foundation: `oc_chair_*` helpers, `Oc_visuals_lib` (chair week, neighbours, receipt, same-day, progress data), `nearby()`/`band_sql()`, migration `oc_010`, weekly demo seed, `oc-visuals.css` tokens + card frame, card controller route | lead | W0 |
| W2 | Cards: Chair Week (Hungry + Slammed), Receipt, Same Tuesday | agent A | W1 |
| W3 | Cards: Chair Map, 8-week report; How-it-works page | agent B | W1 |
| W4 | Business screen rewrite + Share panel + `Oc_share` public controller + export JS | agent C | W1 |
| W5 | Rail restructure; Vacancy → Chair Score wording sweep (Radar, Today, Prospects, Ledger, Pitch); NAV-CONTRACT | lead | W1 |
| W6 | Integrity tests for the new rules; full-app screenshot pass at desktop and phone width; STRUCTURE/README updates | lead | W2–W5 |

---

## 7. Open decisions for the founder

| # | Decision | Default shipped |
|---|---|---|
| D-1 | Money method on cards: Meter band vs the PDF's literal "half your open hours" | Meter band (§4.3) |
| D-2 | Meter's percentile buckets vs Chair Score v1.0 fixed bands for the 8am digest | UI uses v1.0 bands everywhere; the Node meter still writes its own `latest_bucket` for alerts — align it in the meter in a follow-up |
| D-3 | Real map tiles (street names as in p.3) | Stylised street grid, no third-party tiles (no tile licence, no request leaking the market to a map vendor) |
| D-4 | Server-rendered OG preview image for share links | Title/description previews now; image preview needs a rasteriser on the server — follow-up |
| D-5 | Product name (still open from STRUCTURE §9) | "OpenChair" via `$ic_brand` |

---

## 8. Acceptance walk

1. Log in → rail shows Work / Agency groups, "+ New scan", plan usage.
2. Find businesses → chips read Hungry / Steady / Slammed with Chair Scores; counts match the pins.
3. Open a Hungry shop → header pill, stepper, 4 tiles, Chair Week, "What we couldn't see", Share card.
4. Pick each of the 4 cards → preview opens; Copy share link → `/c/{token}` opens logged-out and
   shows the frozen card with the agency name; open count increments in Activity.
5. Download image → a phone-size PNG; Download one-pager → print dialog with a single page.
6. Open a Slammed shop → Chair Week shows wait days instead of money.
7. How it works → the Chair Score explainer.
8. Client reports → 8-week report card with 41 → 63-style headline from the weekly rollups.
9. A shop below the confidence floor → "Not enough data yet" everywhere; share disabled.
10. `php engine/application/modules/openchair/tests/integrity_test.php` → ALL GREEN.
