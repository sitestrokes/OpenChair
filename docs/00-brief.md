# OpenChair — Founder Brief (verbatim source idea)

**Product name:** OpenChair
**Category:** "Live Capacity Prospecting" for local-business consultants / affiliates.
**One-line promise:** Walk in with their empty calendar, not a cold pitch.

**Why it's never been launched:** every JVZoo-style local lead tool qualifies prospects on how bad
their *website* looks. OpenChair qualifies on how much *unsold time* they have right now — a number
the owner already feels in their gut but has never seen on paper.

## Where the data lives (all public, read-only)
Online booking pages expose availability to anyone who clicks "Book now":
- Beauty & wellness: Booksy, Vagaro, Fresha, GlossGenius, Square Appointments, Acuity — slot grids for the next 7–14 days per staff member
- Studios: Mindbody, Momence — class capacity ("4 of 12 spots left")
- Clinics: Zocdoc-style "next available" dates for dental, chiro, physio, med spa
- Home services: Housecall Pro / Jobber / ServiceTitan online booking windows
- Restaurants: OpenTable / Resy availability for Friday and Saturday 7pm

The tool NEVER books or holds a slot. It observes the grid, records it, moves on. Rate-limited,
cached, one pass per business per day.

## The mechanism — S.E.A.T.
1. **Scan** — nightly crawl of every business in a niche + city that has public booking
2. **Estimate** — Vacancy Score (open slots ÷ bookable slots, next 7 days) × their own menu prices
   = **Empty-Chair Revenue**: "$2,340 of unsold time this week"
3. **Alert** — 8am list, two buckets
4. **Target** — one-tap pitch pack: their actual availability grid screenshot, the dollar figure,
   and a matched fix

## The two buckets (this is the part that doubles the market)
- 🔴 **Hungry** (60%+ open): sell them *customers* — midweek offers, Google offer posts, review
  velocity, a LossReel
- 🟢 **Slammed** (95%+ booked, next-available 2+ weeks out): sell them *capacity* — waitlist
  automation, price-increase justification, deposit gates, "open a second chair" or hire

Same scan, two completely different pitches. Nobody sells to the slammed business because nobody
knows it's slammed. Their problem is leaking money to walk-aways, and they can afford you.

## Named components
- **Radar** — the crawler + platform adapters (each booking platform is one adapter; ship 6 at
  launch, add monthly as OTO value)
- **Meter** — Vacancy Score, Empty-Chair Revenue, and a **Dead-Zone Map** (which days/hours are
  chronically empty — Tuesday 1–4pm is where the offer goes)
- **Ledger** — 8-week vacancy trend per business; the retention product. Once they're a client the
  monthly re-scan proves fill rate went 41% → 68%. The prospecting artifact and the retainer report
  are the same screen.
- **Pitch** — generates the walk-in one-pager and the email, and feeds LossReel for a 30-second video

## Demo (under 60 seconds)
Type "barbershop, Tampa" → 47 pins drop, coloured red-to-green by vacancy → click the reddest →
their real Booksy grid appears with 38 open slots highlighted and "$1,860 unsold this week" → tap
Pitch → one-pager with the grid screenshot renders. Then click a green pin: "next available: 19
days" → Pitch flips to the capacity offer. **Two pitches, one scan, live.**

## Money
- COGS ≈ $0.02 per business scan; a full niche-city scan of 50 businesses ≈ $1/night
- **FE $47** — 1 city, 3 niches, Hungry list only
- **OTO1 $97** — unlimited cities/niches + Slammed bucket + Dead-Zone Map
- **OTO2 $197** — white-label Ledger reports (retention play — consultant charges $97–197/mo for
  "capacity monitoring")
- **OTO3 $297** — Fill-the-Chair campaign kit: dead-zone offers, last-minute SMS templates, Google
  offer posts, LossReel credits
- **Backend $997** — agency license + restaurant/clinic adapters
- Consultant economics: land at $497 setup + $197/mo per client. One scan pays for the year.

## Risks (say these on the sales page before an affiliate does)
1. **Vacancy isn't always vacancy.** Blocked slots, staff days off and walk-ins don't show online.
   Label it "online-bookable open slots," never "empty shop." The number is still directionally
   brutal and the owner knows it.
2. **Anti-bot on the big platforms.** Radar needs headless browsers, residential rotation, and
   patience. Budget this in the spike; it's the whole build risk.
3. **Coverage.** Only businesses with online booking show up. That's a feature: they've already paid
   for software, they're your buyer.
4. **Never touch a slot.** Read-only, always. One "the tool booked a fake appointment" story kills
   the launch.

## Identity shift (the core marketing hook)
Before: "Hi, I help local businesses with marketing" → hang-up.
After: "I noticed you had 38 open slots this week — mostly Tuesday afternoons. I can fill those.
Here's how." → meeting.
The consultant stops being a marketer and becomes the person who knows their calendar.

## How it stacks with the others (MotionStack)
OpenChair finds the business → **LossReel** delivers the proof as a video → **Living Site /
ProjectPreview** is what you sell them. It is the top of the funnel for the whole MotionStack, and
the cheapest of the four to build.

---

## Host-repo context (this repo = InstantClose AI)
OpenChair is intended to be built by the same team, reusing the same platform primitives:
- CodeIgniter-3 HMVC engine at `engine/application/` (admin UI, Ion Auth, clients, settings,
  sequences, telephony, compliance/DNC). Dark themed module pattern: `engine/application/modules/instantclose/`.
- Node 22 ESM service at `engine/bridge-service/` (HTTP :4000 + WS lanes, defensive dynamic route
  loading, all config via `src/config.js`, in-process event bus `src/events.js`, state machine).
- One shared MariaDB. Migrations in `sql/migrations/ic_NNN_*.sql`, idempotent + numbered (currently
  through ic_045). Collation gotcha: `funnels.site_uid` utf8mb4_unicode_ci vs `subscribers.site_uid`
  utf8mb4_general_ci — never JOIN them.
- CI3 ↔ Node is server-to-server, gated by `X-Internal-Token`.
- Philosophy: extend, don't duplicate. Keep new modules self-contained (Separation Charter).
