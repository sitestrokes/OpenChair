# OpenChair — Risk, Compliance & QA

**Component:** cross-cutting (Radar · Meter · Ledger · Pitch · Ops console)
**Status:** DRAFT v0.1 — pre-build, for founder + counsel review
**Owner doc for:** PRD §8 (product-integrity rules), PRD §11 risk register R4/R5/R6/R8, PRD Q6
**Related:** [00-brief](00-brief.md) · [05-prd](05-prd.md) · [30-data-model](30-data-model.md) ·
[35-scoring-model](35-scoring-model.md) · [40-ux-spec](40-ux-spec.md) · [45-flows](45-flows.md)

---

> # ⚠️ THIS IS NOT LEGAL ADVICE
>
> **I am a risk/compliance analyst and QA lead, not a lawyer, and this document is not legal advice.**
> Nothing here creates an attorney–client relationship or substitutes for one. It is an engineering
> and product risk assessment written so that a qualified attorney — ideally one who has handled
> web-scraping, CFAA and FTC advertising matters, plus a privacy specialist for GDPR/UK GDPR/CCPA —
> can review a concrete proposal rather than an open question.
>
> **Case law and platform terms below are stated from working knowledge with a training cutoff of
> May 2026.** Litigation moves; terms of service change without notice; a robots.txt can change
> between this sentence and the first crawl. **Every legal proposition in §A1 and every cell in the
> platform table in §A2 must be re-verified by counsel before the first paid scan.**
>
> **Verification status of this pass:** platform `robots.txt` files and Terms of Service were **not
> re-verified in this pass** (research budget exhausted). Where a cell says *not re-verified in this
> pass — confirm before launch*, treat it as a hypothesis to check, **not a finding**. I have not
> invented robots.txt directives or ToS clause text anywhere in this document.
>
> Claims are tagged **[VERIFIED]** (high-confidence recall of settled, widely-reported law or a stable
> technical fact), **[ASSUMPTION]** (my inference, needs checking), **[CONFIRM]** (a specific action
> item for counsel or ops before launch).

---

# Part A — Legal & platform risk

## A1. The scraping-legality landscape

The most useful reframing for the build team: **"is scraping legal?" is the wrong question.** There is
no general law of scraping. There are six separate legal theories, and over the last decade they have
moved in *opposite* directions. The quasi-criminal theory (CFAA) has narrowed sharply against
plaintiffs. The **contract** theory has strengthened. Engineering effort should therefore be spent
defending the contract vector, not the CFAA vector.

### A1.1 CFAA — narrowed, but not neutralised

**Van Buren v. United States**, 593 U.S. 374 (2021) [VERIFIED]. A police officer ran a licence-plate
lookup he was authorised to run, for a bribe. The Supreme Court held that "exceeds authorized access"
under the CFAA has a **gates-up-or-down** meaning: liability attaches to accessing areas of a system
you are not entitled to access **at all**, not to accessing permitted areas for an improper purpose.
This killed the "you broke the ToS, therefore you committed a federal crime" theory.

**hiQ Labs v. LinkedIn** — the case everyone cites, usually for the wrong proposition [VERIFIED as to
the outcome sequence; **[CONFIRM]** any successor litigation or later distinguishing authority]:

| Stage | What happened | What it actually stands for |
|---|---|---|
| N.D. Cal. 2017 | Preliminary injunction for hiQ; LinkedIn ordered to stop blocking | Interim relief only |
| 9th Cir. 2019 | Affirmed — scraping public data likely not "without authorization" | Widely over-read as "scraping is legal" |
| SCOTUS 2021 | **Vacated and remanded** in light of *Van Buren* | The 2019 opinion had no force at that moment |
| 9th Cir. 2022 (on remand) | Reaffirmed: where data is **public and requires no authentication**, there is no gate to breach, so no CFAA liability | The real holding, and it genuinely helps us |
| N.D. Cal. **Nov 2022** | **LinkedIn won summary judgment on breach of contract** — hiQ had accepted the User Agreement, and used fake accounts and turkers | **This is the part that matters** |
| Dec 2022 | Settled; hiQ permanently enjoined from scraping LinkedIn | The "winner" stopped scraping |

**The lesson OpenChair must internalise: hiQ won the CFAA question and lost the case.** The
enforceable weapon was **contract**, and what dragged hiQ into contract was (a) **having accepted the
terms** via accounts and (b) **using fabricated identities**. Both are things we can architecturally
refuse to do. See §A3.

### A1.2 Contract / ToS — the real vector

Two adjacent decisions define the live boundary [substance recalled with reasonable confidence;
**[CONFIRM]** current procedural posture, appeals, and any 2025–26 developments]:

- **Meta Platforms v. Bright Data** (N.D. Cal., 2024) — Meta's breach-of-contract claim over
  *logged-out* scraping failed. The reasoning that matters: the terms bound users *while using the
  service*, and a logged-out scraper was not a "user". Terms did not reach logged-out collection of
  public pages. [ASSUMPTION as to precise holding scope]
- **Craigslist v. 3Taps** (N.D. Cal. 2013) [VERIFIED] — the counterweight. After Craigslist sent a
  cease-and-desist **and** IP-blocked 3Taps, continued access *was* held potentially "without
  authorization" under the CFAA even though the pages were public. **A C&D plus a technical block
  revokes authorisation.** This is why our incident playbook (§A6.7) treats a C&D as an immediate,
  permanent, non-negotiable stop for that platform — not an opening negotiating position.
- **Ryanair v. Booking.com** (D. Del., 2024, jury verdict for Ryanair on a CFAA theory) [ASSUMPTION as
  to detail] — a reminder that the CFAA is not dead where **logins, account creation, or CAPTCHA
  circumvention** are in the fact pattern. Again: **the login line is the line.**

**Browsewrap vs clickwrap** [VERIFIED as a general principle]: US courts enforce **clickwrap**
(affirmative "I agree") far more readily than **browsewrap** (a "Terms" link in a footer). A crawler
that never creates an account, never clicks anything and never authenticates has a materially better
argument that it never formed the contract. It is *not* a guaranteed argument — a defendant with
actual knowledge of terms can still be bound in some circumstances — but it is the difference between
hiQ's position and Bright Data's.

**Trespass to chattels** (*eBay v. Bidder's Edge*, N.D. Cal. 2000) [VERIFIED] survives where scraping
**impairs the server**. It is a load-based claim. Our rate limits (§A6.3) are therefore not courtesy —
they are the defence to this specific tort.

### A1.3 DMCA §1201 — the anti-circumvention trap, and our sharpest self-inflicted risk

17 U.S.C. §1201 prohibits circumventing "a technological measure that effectively controls access to a
work" [VERIFIED as to the statute]. Whether commercial bot mitigation (Cloudflare / DataDome /
PerimeterX-style challenges) is such a measure protecting a *copyrighted work* is contested and
fact-specific [ASSUMPTION]. But note the shape of the risk:

> The founder brief's own mitigation for R2 — "headless browsers, residential rotation, and patience"
> — restated in legal terms is **a programme of defeating access controls and disguising the origin of
> requests.** That is the single most legally expensive sentence in the brief.

This is where the engineering plan and the legal plan collide head-on. §A8.2 resolves it:
residential-proxy rotation and challenge-solving are re-scoped from "the whole build risk" to
**prohibited above tier 2**.

### A1.4 EU — database rights and the TDM opt-out

- **Sui generis database right** (Directive 96/9/EC) [VERIFIED as to existence]. Independent of
  copyright. Protects a maker who made a **substantial investment** in *obtaining, verifying or
  presenting* contents, against extraction/re-utilisation of a **substantial part**, and against
  repeated extraction of insubstantial parts conflicting with normal exploitation. A booking
  platform's aggregated availability across its merchant base is a plausible protected database
  [ASSUMPTION — the *British Horseracing Board v. William Hill* line holds that investment in
  *creating* data does not count, only in *obtaining* it, which cuts our way for platform-generated
  availability and against us for platform-*collected* directory content].
- **Ryanair v. PR Aviation** (CJEU, C-30/14, 2015) [VERIFIED]. A database **not** protected by
  copyright or the sui generis right may still be **contractually** restricted — the Directive's
  lawful-user exceptions do not apply to it. In the EU the ToS argument is *stronger*, not weaker.
- **DSM Directive (EU) 2019/790, Arts. 3 & 4** — text-and-data-mining exceptions; the Art. 4
  commercial exception applies **unless rights are expressly reserved in a machine-readable way**
  [VERIFIED]. **robots.txt is the canonical machine-readable reservation.** This is the strongest
  practical reason to treat robots.txt as binding rather than advisory.

> **Recommendation R-GEO-1.** **Geo-scope v1 to the US and Canada.** Do not crawl EU/UK-established
> booking platforms or EU/UK business listings at launch. This removes the sui generis right, the
> *PR Aviation* contract amplification, the GDPR Art. 14 notice problem (§A4.3) and the EU
> representative requirement in one decision. Revisit for v2 with counsel and a DPIA.

### A1.5 Copyright in the compiled data — and the screenshot problem

- **Feist v. Rural Telephone**, 499 U.S. 340 (1991) [VERIFIED]: facts are not copyrightable; a
  compilation is protected only in its original **selection and arrangement**, and "sweat of the brow"
  earns nothing. *"Tuesday 2pm is open"* is a fact. Our Vacancy Score, capacity envelope, buckets and
  Dead-Zone Map are **our own** original work product derived from facts. This is a strong position.
- **But the demo's most persuasive artifact is a screenshot of their booking page** (brief §Demo,
  FR-7.1). A pixel capture reproduces the platform's layout, typography, iconography, staff
  **photographs** and logos — expressive elements that *are* copyrightable and are **not owned by the
  business we are pitching**. Fair use is arguable (transformative, non-substitutive, negligible
  market effect) but it is a defence you fund, not a right you have.

> **Recommendation R-COPY-1 (build-affecting).** The one-pager and the in-app grid must render **our
> own `SlotGrid` component from our own canonical data** — never a raster capture of the platform's
> page. 40-ux-spec already defines `SlotGrid` with a `capture` state at 2×; make that the **only**
> path. No `page.screenshot()` of a third-party booking page, ever, and nothing of the sort stored in
> `oc_pitch.evidence_gz`. This removes a copyright claim, removes staff photos (a PII vector, §A4),
> removes trade-dress arguments, and makes the artifact themeable, white-labelable and printable at a
> real page spec. **It costs nothing:** our grid is more legible than theirs.

### A1.6 Summary posture

| Theory | Direction of travel | Our exposure **with the rules in this doc** |
|---|---|---|
| CFAA | Narrowed (*Van Buren*, *hiQ* remand) | **Low** — no auth gate is ever crossed |
| CFAA after C&D / IP block | Still live (*3Taps*) | **Low if** the kill switch is honoured instantly (§A6.7) |
| Breach of contract / ToS | **Strengthening — the main vector** | **Medium** — mitigated by never accepting terms (§A3) |
| DMCA §1201 anti-circumvention | Untested for bot mitigation | **Low if** tier-3 evasion is banned; **High if** it is built |
| Trespass to chattels | Load-based, rarely successful | **Low** — rate limits are the defence |
| EU sui generis DB right | Live; ToS enforceable post-*PR Aviation* | **Low** if R-GEO-1 (US/CA only) is adopted |
| Copyright in output | Weak against facts (*Feist*) | **Low if** R-COPY-1 (no screenshots) is adopted |
| Defamation / trade libel | Fact-specific | **Low if** the wording lock holds (§A5) |
| FTC advertising / endorsements | Actively enforced | **Medium** — vendor-controlled, see §A7 |

---

## A2. Per-platform risk table

**Read these caveats before the table.**

1. **robots.txt and ToS columns were NOT re-verified in this pass.** Every such cell is marked and
   **must be fetched, archived with a date and a hash, and reviewed** before that adapter is enabled.
   I have deliberately **not** written specific directives or clause text I cannot cite.
2. Booking platforms typically expose availability through a **customer-facing booking page** that is
   deliberately public and indexable — that is the platform's value proposition to the merchant. That
   public posture is the core of our legal position and should be **evidenced per platform**, not
   assumed.
3. "Official API?" almost always means **a merchant-authorised API for a merchant's own data**. That
   is not usable for prospecting third parties — but it *is* usable for the **Ledger** once the
   consultant signs the client (§A2.3), and that door is strategically important.

| # | Platform | robots.txt stance | ToS stance | Official API? | Risk | Recommendation |
|---|---|---|---|---|---|---|
| 1 | **Booksy** | not re-verified in this pass — confirm before launch | not re-verified in this pass — confirm before launch. Expect a standard anti-automated-access clause binding "users"; browsewrap for logged-out visitors [ASSUMPTION] | Partner/merchant API only; no public availability API [ASSUMPTION] | **Amber** | **Ship at launch.** Anonymous-only, tier ≤2, strict per-domain rate limit. Verify + archive robots/ToS first. |
| 2 | **Vagaro** | not re-verified in this pass — confirm before launch | not re-verified in this pass — confirm before launch | Merchant API (OAuth, merchant's own data) [ASSUMPTION] | **Amber** | **Ship at launch**, same conditions. |
| 3 | **Fresha** | not re-verified in this pass — confirm before launch | not re-verified in this pass — confirm before launch | Partner API, not open [ASSUMPTION] | **Amber** | **Ship at launch**, same conditions. If reliable reads require tier-3 evasion, **drop the adapter** rather than escalate. |
| 4 | **GlossGenius** | not re-verified in this pass — confirm before launch | not re-verified in this pass — confirm before launch | Limited/emerging API [ASSUMPTION] | **Amber-green** | **Ship at launch.** Frequently hosts booking pages per-business, which is a better posture (§A2.2). |
| 5 | **Square Appointments** | not re-verified in this pass — confirm before launch | Square's *developer/seller* terms are explicit and enforced; the consumer booking site is a separate surface [ASSUMPTION] | **Yes** — Bookings API including availability search, scoped to a **seller who grants OAuth** [VERIFIED as a capability; **[CONFIRM]** endpoints/scopes] | **Amber-green** | **Ship at launch** for public pages **and build the seller-OAuth path** for signed clients — the compliant Ledger route (§A2.3). |
| 6 | **Acuity / Squarespace Scheduling** | not re-verified in this pass — confirm before launch | not re-verified in this pass — confirm before launch | **Yes** — API keyed to the account owner [ASSUMPTION] | **Green-amber** | **Ship at launch.** A publicly embeddable scheduler is the strongest "intended to be read by anyone" argument in the set. |
| 7 | **Mindbody** | not re-verified in this pass — confirm before launch | Historically restrictive with an active partner programme and enforcement posture [ASSUMPTION] | **Yes** — Public API, gated by partner approval, per-site activation by the studio owner, and fees [ASSUMPTION] | **Red-amber** | **Defer past launch.** Where a sanctioned paid API is *the* commercial route, scraping around it is the classic contract fact pattern. Pursue the partner programme instead. |
| 8 | **Momence** | not re-verified in this pass — confirm before launch | not re-verified in this pass — confirm before launch | No public API [ASSUMPTION] | **Amber** | **Ship at launch or first value-drop.** Delivers the C2 class-capacity class without Mindbody. |
| 9 | **OpenTable** | not re-verified in this pass — confirm before launch | not re-verified in this pass — confirm before launch. Historically active against reservation scrapers/bots [ASSUMPTION] | Partner/affiliate integrations only [ASSUMPTION] | **RED** | **Refuse for v1** — see §A2.1. |
| 10 | **Resy** (Amex) | not re-verified in this pass — confirm before launch | not re-verified in this pass — confirm before launch | No public API [ASSUMPTION] | **RED** | **Refuse for v1** — see §A2.1. |
| 11 | **Housecall Pro** | not re-verified in this pass — confirm before launch. Booking widget is usually embedded on **the business's own website**, whose robots.txt then governs | Widget terms bind the merchant, not a visitor to the merchant's site [ASSUMPTION] | Merchant API [ASSUMPTION] | **Green-amber** | **Ship in the first value-drop.** Best legal posture in the set (§A2.2). |
| 12 | **Jobber** | as above — not re-verified in this pass | as above [ASSUMPTION] | Merchant API / developer platform [ASSUMPTION] | **Green-amber** | As above. |
| 13 | **ServiceTitan** | as above — not re-verified in this pass; more enterprise, more likely to carry explicit terms | not re-verified in this pass — confirm before launch | Merchant API [ASSUMPTION] | **Amber** | Backend ($997) tier, as the brief plans. |
| 14 | **Zocdoc-style clinic "next available"** | not re-verified in this pass — confirm before launch. Health directories often carry explicit anti-scraping terms [ASSUMPTION] | not re-verified in this pass — confirm before launch | No [ASSUMPTION] | **Red-amber** | **Defer.** Health-adjacent context (§A4.7) plus the C5 0.40 confidence ceiling = low product value at high legal cost. |

### A2.1 The answer to PRD Q6 — platforms we refuse even though they are technically easy

**Refuse for v1: OpenTable and Resy.** Three independent reasons, any one sufficient:

1. **The scoring model already says they are our weakest product.** 35-scoring-model puts reservation
   platforms in class **C4 with a 0.65 confidence ceiling**, and prints the caveat that we cannot see
   table count and are measuring *seating availability for one party size*, not occupancy. We would be
   taking our highest legal risk to ship our least defensible number.
2. **Reservation-bot regulation is a live, adjacent hazard.** New York enacted a restaurant-reservation
   anti-piracy statute aimed at unauthorised third-party reservation listing/selling, and other states
   have looked at similar bills [ASSUMPTION as to citation and effective date — **[CONFIRM]**]. We do
   not book or resell, so we should sit outside it — but "automated tool reads restaurant reservation
   availability" is not a headline you want during launch week, and the regulatory temperature in that
   vertical is the highest on the list.
3. **Anti-bot posture.** Reservation platforms fight scrapers professionally because scalpers attack
   them. Winning there requires exactly the tier-3 techniques §A8.2 bans.

**Defer: Mindbody** (a sanctioned partner API is the commercial route) and **clinic / Zocdoc-style**
(health adjacency, plus C5 means we can only ever print a lead time — which we can usually read from
the practice's own site anyway).

This costs the brief's "Restaurants" bullet and part of "Clinics". **Reframe, don't hide it:** the
backend $997 tier's "restaurant/clinic adapters" becomes "**enterprise + home-services adapters**",
a larger and more solvent market.

### A2.2 The first-party-widget insight — bias the roadmap toward it

Housecall Pro, Jobber, ServiceTitan, GlossGenius, Square and Acuity commonly render the booking UI
**on the business's own domain** (embed, subdomain, or white-labelled site). That is materially better
on every axis:

- The page we read is published by **the business we are pitching** — the party whose interests the
  data serves. No third-party intermediary has an obvious grievance about *that* business.
- No marketplace ToS is plausibly in play for a logged-out visitor to a small business's own website.
- The **business's own robots.txt** governs, and small-business sites almost never disallow crawling.
- It is the same page the consultant will point at during the walk-in.

> **Recommendation R-PLAT-1.** Weight the adapter roadmap by *hosting posture*, not only market share:
> **first-party-hosted widgets before marketplace-hosted availability.** Store
> `oc_platform.hosting_posture ∈ {first_party, marketplace, hybrid}` and surface it in the Ops console
> next to the risk rating and the kill switch.

### A2.3 The consented path — the upgrade every platform risk eventually wants

Once a consultant **signs** the business as a client, the legal question changes completely: the client
can authorise API access to their own data. That yields a far stronger Ledger (real bookings, not an
online-bookable residual; no confidence ceiling; no anti-bot cost) and it is exactly the OTO2 /
retention product.

> **Recommendation R-PLAT-2.** Spec a `connected` state on `oc_business` (merchant OAuth granted via
> the consultant) that switches the Ledger from scraped to authorised data, lifts the confidence
> ceiling toward 1.00 (35-scoring §1.3 already reserves 1.00 for "owner-verified"), and **removes that
> business from the crawl entirely**. This converts our highest-risk activity into our stickiest
> revenue, and shrinks the crawl fleet as the product succeeds.

### A2.4 Verification protocol before any adapter is enabled (per platform, re-run quarterly)

Produce a dated evidence file in the Ops console, per platform:

1. `robots.txt` fetched; full text archived with SHA-256 + timestamp; the `User-agent` / `Disallow`
   lines governing our paths quoted verbatim.
2. ToS / Terms of Use fetched and archived, with anti-automation, anti-scraping, account, and
   governing-law clauses quoted verbatim and pinpoint-cited.
3. Whether an account or click-through is required to see availability. **If yes → refuse (§A3).**
4. Whether an official/partner API covers the same data, and its cost/terms. **If the API is the
   sanctioned commercial route → escalate to counsel before scraping.**
5. Bot-mitigation vendor observed, and the highest access tier required for a reliable read.
   **If > tier 2 → refuse (§A8.2).**
6. Counsel sign-off checkbox with reviewer name and date.

**Automate the drift check:** re-fetch `robots.txt` and the ToS URL nightly, diff the hash, raise a
**P2 ops alert on any change**, and pause new-market expansion on that adapter until reviewed. A ToS
change is the cheapest early warning we can buy.

---

## A3. The account/login line — the sharpest distinction in the whole product

Everything in §A1 converges on one boundary:

> **Anonymous retrieval of a page a platform publishes to the open internet is a defensible act.
> Anything that requires an account, a credential, a session cookie, a click-through acceptance, or a
> defeated challenge is a different legal universe.**

Crossing that line simultaneously (a) forms the contract that hiQ lost on, (b) restores the CFAA gate
that *Van Buren* removed, (c) puts DMCA §1201 in play, and (d) in most cases requires accepting terms
in our own name or a fabricated one — which is the fact that turned hiQ from plaintiff into defendant.

### A3.1 The hard rule

> **RULE OC-NOLOGIN — non-negotiable, architectural, not policy.**
> OpenChair never creates an account on a booking platform, never stores or transmits a credential to
> one, never persists a session cookie across requests, never accepts terms on any screen, never
> solves or outsources a CAPTCHA or bot challenge, and never uses a residential/mobile proxy or
> browser-fingerprint spoofing to appear to be a consumer.
> **If availability cannot be read anonymously in ≤2 tiers, that platform has no adapter.**

### A3.2 How to enforce it technically (six layers, each independently sufficient to catch a mistake)

1. **No credential store exists.** 30-data-model already rules out an `oc_credentials` table. Keep it
   ruled out. There must be **no schema location** in which a booking-platform username, password,
   OAuth token or session cookie can be persisted. (The `connected` merchant-OAuth state of §A2.3 is a
   deliberate, separate, consent-backed exception and must live in the engine's existing encrypted
   `settings` store with its own audit trail and its own review — never in the crawl path.)
2. **A single egress client for all booking-host traffic.** Every adapter calls one HTTP module; direct
   `fetch`/`axios`/`http` use in adapter code is blocked by lint rule + CI grep. That module:
   - refuses any method other than `GET` and `HEAD` (this is also the read-only guard, §A8.4);
   - **strips `Cookie`, `Authorization`, `Proxy-Authorization` and any `X-*-Token` header on the way
     out**, unconditionally;
   - **discards `Set-Cookie` on the way in** — the cookie jar is per-request and destroyed with the
     request, so no session can survive to a second call;
   - refuses any URL whose path matches a deny-list of auth routes (`/login`, `/signin`, `/signup`,
     `/register`, `/account`, `/oauth`, `/auth`, `/checkout`, `/cart`, `/book`, `/confirm`, `/hold`,
     `/reserve`, `/pay`) — fail closed on the *first* match, log the adapter and URL as a **P1 defect**;
   - sets the identifying User-Agent (§A6.1) and cannot be overridden by adapter code.
3. **Headless browser profile is hard-configured hostile to evasion.** No stealth plugins, no
   fingerprint randomisation, no `navigator.webdriver` patching; a fixed viewport and a fixed, honest
   UA. If a page only renders under evasion, it does not render.
4. **Challenge = terminate, never solve.** Detecting an interstitial/CAPTCHA/JS challenge sets
   `oc_scan_run.status='captcha'`, raises `captcha_degraded`, and — per 35-scoring §2.3(5) — the Meter
   **hard-refuses to publish**. There is no branch that attempts a solve, and no CAPTCHA-solving
   service is ever a dependency in `package.json` (CI check on the dependency tree).
5. **Egress allow-list at the network layer.** The crawl fleet may only reach hosts registered in
   `oc_platform` plus resolved business domains; a per-adapter kill switch (§A6.8) drops the host from
   the allow-list. Combined with the SSRF-guard pattern already used in `bridge-service`, this stops
   both "adapter reaches somewhere unexpected" and "adapter reaches internal infra".
6. **Tests that assert the negative.** The adapter contract suite (§B9.3) includes, for every adapter:
   a POST attempt is rejected; an injected `Set-Cookie` does not survive to the next request; an
   injected auth-path URL is refused; a fixture containing a challenge page yields `captcha` +
   refusal, never a score. **These tests are release-blocking.**

### A3.3 The one grey area to decide explicitly

Some platforms require a **party size or service selection** before showing times (notably C4
reservations and some C1 grids). Clicking a `<select>` on a public page is not authentication, and I
regard it as inside the line [ASSUMPTION — **[CONFIRM]** with counsel]. But make it explicit rather
than emergent:

> **Allowed:** selecting a service, staff member, party size, or date on a public page, and reading
> the resulting availability.
> **Not allowed:** entering any personal data (name, email, phone), progressing past the point where a
> booking would be created or held, or any step the platform labels as sign-in.
> Adapters declare `interaction_depth ∈ {static, select_only}`; anything beyond `select_only` requires
> written counsel approval recorded against the adapter.

---

## A4. Data protection

### A4.1 Is availability data personal data? Mostly no.

**Business availability is not personal data** [VERIFIED as a reasoning line; **[CONFIRM]** with a
privacy specialist]. Opening hours, open slots, published menu prices and a shop's listed phone number
are ordinary published *business* information. The Vacancy Score, Empty-Chair Revenue and Dead-Zone Map
are computed entirely from it. 30-data-model §9 already states this position; this doc endorses it.

Two carve-outs where that stops being true:

- **Sole traders and micro-businesses.** For a one-chair barber, "the business" and "a natural person"
  are the same entity; their mobile is their business number and their trading name may be their own
  name. Under GDPR that is personal data; under **CCPA/CPRA** the business-to-business exemption
  **sunset at the end of 2022** [VERIFIED as to the sunset; **[CONFIRM]** current status], so business
  contact information about a Californian sole proprietor is in scope.
- **Staff names and photographs.** See §A4.2 — the real issue.

### A4.2 Staff in the grid — the actual PII problem, and the recommendation

*"Marco — Tuesday 2pm free"* identifies a natural person at an identifiable employer and describes his
working pattern. That is personal data under GDPR/UK GDPR and plausibly under CCPA. Worse, think about
what the artifact **says**: a document, handed to a salesperson, listing a named employee's idle hours.
It is hard to describe a legitimate-interest balancing test that survives that sentence, and the data
subject (Marco) has no relationship with us and no expectation of it.

30-data-model §9 already lands close to the right answer (`staff_hash` in blobs, positional `label`
for rendering, `display_name` nullable/opt-in with a 30-day purge). **This doc recommends going one
step further and closing the opt-in door for v1:**

> **Recommendation R-PII-1 (build-affecting).** **Store staff as an opaque index only. Never a name.**
> - `oc_staff.staff_key` = the platform's own resource id; `staff_hash = sha1(business_id|staff_key)`
>   is the only identifier used in `oc_scan_snapshot.grid_gz` and in every computation. Keep this.
> - **Drop `display_name` and `name_captured_at` from the v1 build** (leave the columns in the
>   migration if you like, but no writer, no reader, no config flag, no UI). A nullable opt-in field
>   is a field somebody enables in a hurry during a support escalation.
> - Render **`Chair 1`, `Chair 2`, `Stylist A`** — derived from stable position. PII-free in the app,
>   in the one-pager, in the email, in the CSV export and in a white-label PDF.
> - **Never fetch or store staff photographs.** R-COPY-1's no-screenshot rule already removes the main
>   ingestion path; make it explicit in the adapter contract (`assertNoImageFetch`).
> - Where a platform's markup interleaves names with slot data, the adapter **strips names at parse
>   time, before the canonical grid is constructed** — not at render time. The name must never enter
>   our storage, so a bug in the renderer cannot leak it.
>
> **What this costs:** nothing the product needs. Nothing in 35-scoring-model consumes a name — the
> envelope, eligibility, dispersion, ghost-staff detection and pooling all run on `staff_hash`. A
> consultant does not need to know Marco's name to say "one of your four chairs is idle on Tuesdays";
> saying "Marco is idle on Tuesdays" is *worse* sales anyway — it makes the owner defensive about a
> person instead of curious about a number.

### A4.3 GDPR / UK GDPR — the analysis, and why R-GEO-1 is the cheap answer

If any EU/UK data were processed, we would need: a **lawful basis** (Art. 6(1)(f) legitimate interests,
plus a documented LIA that R-PII-1 is what lets it pass); **Art. 14 notice to data subjects we did not
collect from** — practically impossible at scale, and the Art. 14(5)(b) "disproportionate effort"
derogation is contestable for a commercial prospecting product; **DPIA** under Art. 35 (systematic
monitoring / large-scale profiling is squarely on the list); **data subject rights** (access, erasure,
objection under Art. 21 — and for direct-marketing-adjacent processing, an objection is absolute);
**Art. 27 EU/UK representatives**; and a **records of processing** entry.

> This is a real programme of work, not a paragraph. **R-GEO-1 (US/CA only for v1)** removes all of it
> for launch. If EU/UK is ever in scope, budget a DPIA and a representative *before* the first crawl,
> not after the first complaint.

### A4.4 CCPA / CPRA — what actually applies in v1

Assume in scope [ASSUMPTION — thresholds depend on revenue/volume; **[CONFIRM]**]:

- **Notice at collection** covering indirect collection, published on the OpenChair site.
- **We do not "sell" personal information** — and we must keep it that way. Selling or licensing the
  business dataset to third parties would flip this analysis and add a "Do Not Sell or Share" plumbing
  requirement. **Recommendation: contractually and architecturally forbid dataset resale in v1.**
- **Deletion / correction / opt-out** for any covered person → this is the same intake as the business
  removal path (§A6.6). Build one path, serve both purposes.
- **Data minimisation and purpose limitation** — R-PII-1 and the retention rules in 30-data-model §9
  are the substantive answer.
- **Service-provider contracts** with any proxy/hosting/LLM vendor in the crawl path.

### A4.5 Then the outbound side — the consultant contacts the business

The Pitch component generates an **email**, an **SMS** and (via the host engine) potentially a **call
script**. OpenChair does not send them; the consultant does. But the vendor still carries meaningful
exposure, because we drafted the message, and because the host repo contains a working telephony and
sequence engine that somebody will inevitably try to wire up.

**CAN-SPAM (US, email)** [VERIFIED in outline]:
- No opt-in required for B2B email — the US is an opt-**out** regime. This is the permissive one.
- Required: accurate From/Reply-To and routing information; a non-deceptive subject line; identify the
  message as an advertisement (or make its commercial nature clear); a **valid physical postal
  address**; a working **opt-out mechanism honoured within 10 business days**.
- **Address harvesting and dictionary attacks are aggravating factors** carrying enhanced penalties.
  **This bites us directly:** scraping email addresses off booking pages to prefill the pitch email
  would be exactly that. **Do not harvest email addresses from booking pages.** Get the business email
  from the consultant's own research or a licensed directory, or leave the field blank.
- Penalties are **per email**.

**CASL (Canada)** [VERIFIED in outline] — much stricter, and the brief's market includes Canada:
- **Consent required** (express, or implied). The relevant implied-consent route is a **conspicuously
  published business email address** where the publication carries no statement refusing unsolicited
  messages **and** the message is **relevant to the recipient's business role**. A booking page's
  contact address arguably qualifies, but this is exactly the kind of judgement a $47 buyer will get
  wrong.
- Every CEM must identify the sender, give contact information valid for 60 days, and carry a working
  unsubscribe honoured within 10 business days.
- Penalties are severe (administrative monetary penalties up to CAD $1M individual / $10M
  organisation) [ASSUMPTION as to current figures], and there is **officer/director liability**.
- **Recommendation:** ship a distinct **CASL-safe** template variant, and if the consultant's target
  is a Canadian business, show a CASL notice in the Pitch composer before the email variant renders.

**TCPA (US, SMS and calls)** [VERIFIED in outline — this is the expensive one]:
- Marketing texts/calls sent with an **ATDS or a prerecorded/artificial voice** require **prior express
  written consent**. A cold marketing SMS to a shop's number — which for a barber is very often a
  **mobile** — is the highest-risk artifact in the entire product.
- Statutory damages **$500–$1,500 per message**, with an active plaintiffs' bar and no cap.
- B2B calling has partial relief (the national DNC registry is aimed at residential/wireless personal
  numbers), **but**: several **state mini-TCPAs are broader than the federal statute** (Florida,
  Oklahoma, Washington and others) and some reach business lines and manually dialled calls
  [ASSUMPTION as to current state list — **[CONFIRM]**].
- The regulatory picture is in flux — e.g. the FCC's "one-to-one consent" rule was vacated on appeal in
  2025 [ASSUMPTION as to citation]. **Do not build product logic that depends on a specific current
  rule**; build it to the conservative floor.
- **Manual, individual, one-to-one texting from the consultant's own handset is a materially different
  risk profile from anything automated**, and that is the only mode we should encourage.

**Recommendation R-OUT-1 — what the product must NOT do (each of these is a hard architectural line):**

| Must NOT | Why |
|---|---|
| Auto-send any email, SMS or call to a prospect business | PRD §8 rule 7; TCPA/CASL exposure moves from the user to the vendor the moment we press send |
| Provide a "Send" button that transmits from vendor infrastructure | Same. The composer's terminal action is **Copy** or **Download**, never **Send** |
| Wire `oc_pitch` into `sequence_queue`, the voice lanes, or the SMS scheduler | 30-data-model §7's "boundary that must not be softened" — keep it hard, and add a CI grep asserting no OpenChair module imports the sequence/telephony modules |
| Bulk-generate messages for a whole list in one action | Bulk generation is what turns a consultant into a spammer; cap pitch generation at one business per action, with a per-day quota |
| Scrape or store business email addresses from booking pages | CAN-SPAM harvesting aggravation |
| Prefill an SMS body containing an offer, with a `sms:` deep link, for more than one recipient at a time | One-to-one manual send only |
| Generate a message that omits sender identity, a physical address, or an opt-out line | CAN-SPAM/CASL formal requirements; make these template-locked and non-deletable |
| Auto-dial or auto-voicemail via the host engine's telephony | TCPA prerecorded-voice liability |

**What the product SHOULD do:**
- Template-lock the compliance furniture (sender identity, physical address, opt-out sentence) as
  **non-editable regions** of the generated email; the consultant edits the pitch, not the footer.
- Require the user's own business name + postal address at onboarding, and refuse to render an email
  variant until they are supplied. (Also a quality win: a real address makes the pitch credible.)
- Route any dial or SMS the consultant *does* choose to make through the engine's existing
  `suppression_list` / `phone_blocklist` / `dnc_check_cache` — 30-data-model §7 already commits to
  this. Surface the check result in the UI before the number is revealed.
- A one-screen **"Before you contact anyone"** interstitial on first pitch generation, with a recorded
  acknowledgement (`user_id`, timestamp, version) — this is both user protection and vendor evidence.

### A4.6 Copy of record

Keep an immutable, versioned copy of every generated template plus the acknowledgement text. If a buyer
is ever accused of a CAN-SPAM/CASL/TCPA violation using our template, the vendor's defence is "here is
exactly what we gave them, here is the compliance guidance attached, here is their acknowledgement,
and here is the fact that our software cannot send".

### A4.7 Sensitive-category adjacency

Med spas, chiropractors, physio and dental are on the brief's list. Availability at a *health provider*
is provider data, not patient data, and there is no HIPAA relationship — but "we monitor when this
clinic has open appointments" reads badly and invites a story. Combined with the C5 confidence ceiling
of 0.40, that is the second reason to defer clinics (§A2.1).

---

## A5. Defamation and accuracy exposure

### A5.1 The exposure, stated precisely

We tell **a third party** (the consultant — publication is satisfied) a **statement of fact** about an
identifiable business ("of and concerning" is satisfied) that is capable of **injuring it in its
trade**. If that statement is false and causes loss, the theories are **defamation** and, more aptly,
**trade libel / commercial disparagement** (which typically requires falsity about the business's goods
or services **and** proof of **special damages** — a higher bar, and our friend) [VERIFIED in outline;
**[CONFIRM]** state-by-state elements].

The realistic harm scenario is not a lawsuit — it is worse for the launch: a walk-in-heavy barber tells
the local business Facebook group that "some AI tool is telling salesmen my shop is empty." 35-scoring
§7.1 already names this as the #1 product risk. Reputationally, **the true-but-clumsy statement and the
false statement do identical damage.**

### A5.2 The three defences we can engineer

1. **Truth is a complete defence — but only for the statement we actually make.**
   *"This shop is empty"* is false about a walk-in barber. *"38 of the slots this shop publishes for
   online booking in the next 7 days are still open"* is **verifiably true** and the owner can check it
   in ten seconds. This is why FR-4.6's wording lock is a **legal control**, not a style preference.
2. **Opinion based on disclosed facts is not actionable** (*Milkovich v. Lorain Journal*, 1990, and the
   line of cases treating a conclusion drawn from stated facts as non-actionable opinion) [VERIFIED in
   outline]. This is precisely the shape of our artifact: **we show the grid, we show the arithmetic,
   we show the price source, and we show what we could not see.** The disclosure block (FR-4.5) is
   therefore not a courtesy — it converts an assertion into a supported inference.
3. **Refusal beats a hedge.** 35-scoring §2.3's hard gates mean that when we are most likely to be
   wrong (blackout, captcha, one prior scan, truncated horizon) we publish **nothing**. You cannot
   defame anyone with a number you did not print.

### A5.3 Labelling constraints (enforceable, testable)

| Constraint | Rule |
|---|---|
| **Unit lock** | Every rendering of the metric uses "**online-bookable open slots**". The strings "empty shop", "empty", "dead", "no customers", "nobody books", "failing", "struggling", "going under" are **banned tokens** in generated copy — enforced by a lint over the template corpus **and a runtime guard on any LLM-generated sentence** (§B9.2 test `copy-guard`) |
| **Residual honesty** | Never say "booked". 35-scoring §1.2 is explicit that `envelope − open` is *booked-or-blocked*, never observed as booked. Generated copy says "not available online", not "booked" |
| **Dollars are a range with a named source** | PRD §8.4. "Unsold Value" is arithmetic over observable facts; **"Recoverable Revenue" must never be the headline** and must never appear without its 15–35% assumption stated |
| **No counterfactual** | Never "you lost $X" or "you're losing $X" — that asserts demand existed, which we cannot see. Say "unsold time", "time you published and nobody booked" |
| **Attribution caveat on every client-facing Ledger** (FR-8.3) | A fill-rate improvement is not proof our consultant caused it; `ATTRIBUTION_CAVEAT` already exists in `meter-reference.mjs` — render it, don't just export it |
| **Data age is always adjacent to the number** | A stale number presented as current is the easiest way to be accidentally false |
| **No public/indexed rankings** | See below — the most important one |

> **Recommendation R-DEF-1 (product-shaping).** **Never publish a business's vacancy data outside the
> tenant's own account.** No public URL, no SEO page, no "the 10 deadest barbershops in Tampa" lead
> magnet, no social-share card naming a business, no indexable one-pager link. Publication to one
> consultant who is about to walk into the shop is a defensible, contextual, private disclosure.
> Publication to the world is a different act with a different risk profile, and it would also hand
> every platform a much stronger grievance. If the Pitch one-pager is ever shareable by link, the link
> must be **unguessable, expiring, `noindex`, and access-logged**.
> *(The OTO2 white-label Ledger delivered by a consultant to their own signed client, about that
> client's own business, is fine — the subject is the recipient.)*

### A5.4 Right of publicity / staff

A named, photographed employee appearing in a sales document about their idleness raises right-of-
publicity and dignity concerns alongside data protection. **R-PII-1 and R-COPY-1 remove this entirely.**

---

## A6. Concrete mitigations checklist

Each item names where it is enforced and how it is tested. This is the list I would hand an auditor.

### A6.1 Identify ourselves honestly
- **Fixed, descriptive User-Agent** on every request, containing the product name, a version, and a
  URL explaining what we do and how to opt out — e.g.
  `OpenChairBot/1.0 (+https://openchair.example/bot)`.
- The bot page states: what we read, that we are read-only, that we never book, our rate limits, and a
  **one-click opt-out form**.
- The UA is set in the single egress client (§A3.2.2) and **cannot be overridden by adapter code**.
  A contract test asserts it on every adapter.
- **Never** rotate, randomise, or impersonate a consumer browser UA. If honest identification gets us
  blocked, that is the platform exercising its choice, and the correct response is the kill switch —
  not a disguise. (This single rule is what keeps us out of *3Taps* and §1201 territory.)

### A6.2 robots.txt policy
- **Honour `robots.txt` for our own UA and for `*`.** Fetch, cache 24h, and evaluate before every
  request; a `Disallow` covering the target path is a hard skip recorded as `status='robots_denied'`.
- Honour `Crawl-delay` where present, and never go below our own floor if it is more permissive.
- Honour `<meta name="robots" content="noindex,nofollow">` and `X-Robots-Tag` as a signal to not
  *store* the page, even where crawling is technically allowed.
- Store the governing directive on `oc_scan_run` so any single fetch can be justified after the fact.
- **This is also the EU Art. 4 TDM machine-readable reservation compliance point (§A1.4).**

### A6.3 Rate limits and politeness (also the trespass-to-chattels defence)
- **One observation per business per day**, shared across all tenants (FR-3.1, already in 45-flows §3).
- Per-domain concurrency cap (start at **1–2**) and a per-domain request floor (start at **≥2s**
  between requests, jittered). Tune *down*, never up, without a written decision.
- Crawl in the target's **local overnight window** where determinable.
- Exponential backoff on 429/5xx; **circuit breaker** opens the adapter after N consecutive failures.
- **Conditional requests** (`If-None-Match` / `If-Modified-Since`) and `grid_sha1` unchanged-detection
  (already in 30-data-model) to avoid re-fetching what has not changed.
- Publish the rate-limit policy on the bot page. A platform that can see our limits is far less likely
  to escalate.

### A6.4 The no-login rule — §A3, six enforcement layers, release-blocking tests.

### A6.5 Read-only enforcement — §A8.4, and it is the launch-ending risk (R4).

### A6.6 Public business opt-out / removal path, with a published SLA
- A **public, no-account-required** form at the bot URL: business name + booking URL + requester email;
  no proof-of-ownership required for a *suppression* request (asking a small business owner for
  documentation to stop being scanned is both hostile and slow — and the request costs us almost
  nothing to honour).
- **Published SLA: acknowledged within 1 business day, fully effected within 5 business days.** Target
  actual effect within 24 hours; the gap between promise and practice is our safety margin.
- **Effect** = exactly the 30-data-model §9 flow: cascade-delete every child row; keep the redacted
  `oc_business` **tombstone** with `status='suppressed'` so the next market sweep collides with it
  rather than re-creating the business. Discovery must treat `suppressed` as *skip, never revive*.
  Removal is honoured **globally across all tenants at once**.
- Log every request in the engine's existing `data_rights_requests` (no new table).
- **A monthly automated test re-runs discovery against a suppressed fixture business and asserts it
  does not come back.** A removal that silently reverses is worse than never honouring it.
- Also accept removal requests by email, and treat a platform-forwarded owner complaint as one.

### A6.7 Takedown / abuse / C&D policy
- Published abuse contact; monitored inbox with a named owner and a backup.
- **On a C&D or a platform legal contact:** *stop that adapter within 1 hour* via the kill switch
  (§A6.8), preserve all logs, acknowledge receipt in writing the same business day, **do not resume
  without counsel**, and treat a resumption decision as a launch-level decision. (*3Taps*: continued
  access after notice is the fact that converts a civil argument into a CFAA one.)
- **On an IP block or a hard 403 pattern:** treat it as constructive notice. Auto-open the circuit,
  page the operator, and **never** respond by changing IP, UA, or fingerprint. Rotating around a block
  is the single most incriminating thing an engineer could do here, so make it impossible: proxy
  configuration is not an adapter-level setting.
- Pre-drafted response templates for (a) a platform inquiry, (b) an owner complaint, (c) a press
  inquiry — written **before** launch week, when nobody has time.

### A6.8 Per-adapter kill switch
- One row-level flag (`oc_platform.enabled`, plus `kill_reason`, `killed_by`, `killed_at`) that:
  drains the queue for that platform, drops its hosts from the network egress allow-list, freezes
  publication of its scores, marks affected businesses as `insufficient_data` in the UI (**not** as
  "0 vacancy" — see the canary, §B9.5), and shows the operator a per-platform banner.
- **Reachable in under 60 seconds by one person, from a phone**, without a deploy. Practise it: a
  scheduled quarterly drill where an operator kills and restores an adapter, timed.

### A6.9 Retention limits
- As specified in 30-data-model §9 — raw grids **14 days**, scan audit **60 days**, daily aggregates
  **400 days**, weekly rollups retained for the Ledger window. Add: **`display_name` not collected at
  all** (R-PII-1), superseding the 30-day purge.
- Purge jobs are **monitored**, not merely scheduled: a nightly assertion that no row exists past its
  retention horizon, alerting if it does. An unrun purge job is invisible until it is a disclosure.

### A6.10 Audit logging of every fetch
- `oc_scan_run` already carries business, date, status, adapter version and cost. Add, per fetch:
  **target URL, HTTP method, response status, bytes, the governing robots directive, the access tier
  used, the egress IP/region, the UA sent, and the operator/scheduler that initiated it.**
- **Retain 60 days minimum** (aligned to the scan-audit retention) — long enough to answer "what
  exactly did you do to our site on the 14th?" with a table rather than an apology.
- The log is **append-only** and covered by the same access controls as billing data.
- **Every fetch is attributable to a scheduled scan of a specific business.** An unattributed fetch is
  a P1 defect, and a nightly reconciliation job asserts `count(fetches) == count(expected fetches)`.

### A6.11 Governance
- **Named accountable owner** for crawl policy (not a rotating on-call).
- Adapter changes require a second reviewer; **adding a platform requires the §A2.4 evidence file** and
  a counsel checkbox.
- Quarterly review: re-verify robots/ToS, review complaints and blocks, review the risk table.
- **Insurance:** media/tech E&O with an explicit look at whether scraping-related claims are excluded
  [CONFIRM with broker before launch].

---

## A7. FTC rules for a JVZoo launch — the vendor's do/don't list

Affiliate launches are a specifically watched environment. The vendor is **not** insulated by "an
affiliate said it, not me": the FTC has repeatedly pursued advertisers for their affiliates' claims,
and the 2023 revision of the **Endorsement Guides (16 CFR Part 255)** [VERIFIED as to existence and
year] tightened expectations on advertiser **monitoring** of affiliate/endorser conduct.

### A7.1 The four regimes that touch this launch

| Regime | Why it applies |
|---|---|
| **FTC Act §5** — deceptive acts | Any claim in the sales copy, VSL, or an affiliate's email |
| **Endorsement Guides, 16 CFR 255** [VERIFIED] | Affiliate reviews, testimonials, "results" screenshots, review-copy bonuses; requires clear disclosure of **material connections** |
| **Rule on the Use of Consumer Reviews and Testimonials, 16 CFR Part 465** (effective 2024) [VERIFIED as to existence; **[CONFIRM]** current scope after any appeals] | Bans fake/AI-generated reviews, buying positive reviews, undisclosed insider reviews, and suppressing negative reviews. Directly targets the JVZoo review-video culture |
| **ROSCA / negative-option** | If OTO2/Ledger is a **recurring** tier: clear disclosure of terms before billing, express informed consent, and a simple cancellation mechanism. The FTC's 2024 Negative Option ("click-to-cancel") Rule was vacated on appeal in 2025 [ASSUMPTION as to citation], **but ROSCA itself still applies** — build to the strict standard regardless |
| **Business Opportunity Rule, 16 CFR 437** [ASSUMPTION — needs counsel] | Triggered where a seller takes a required payment, provides goods/services, **and** makes an earnings claim or provides "outlets, accounts, or customers". We provide *prospect lists*, not customers — probably outside it — but **any earnings claim moves you toward it**, and if it applies you owe a disclosure document and an Earnings Claim Statement. **Get a written opinion before writing the sales page.** |

### A7.2 DO

- **Do** require every affiliate to disclose the material connection **clearly and conspicuously**,
  **before** the affiliate link, in the same medium — "I get a commission if you buy through my link" —
  and **in the video/audio itself**, not only in a description box.
- **Do** publish written **affiliate terms** with a prohibited-claims list, require acceptance before
  an affiliate link is issued, and reserve the right to terminate and claw back commissions.
- **Do** actively **monitor**: sample affiliate pages, review videos, emails and paid ads weekly during
  launch week; keep a dated log of what you reviewed and what you enforced. *The log is the defence.*
- **Do** keep **substantiation on file, dated, before publication**, for every objective claim ("scans
  50 businesses in under 3 minutes", "$0.02 per scan", "reads 6 platforms").
- **Do** put the honest limitations **on the sales page**, as the brief already insists: online-bookable
  slots only, coverage limited to businesses with online booking, read-only, results vary. Founder
  instinct here is correct and it is also the best legal hygiene available.
- **Do** ensure testimonials are from real users of the actual product, are typical or accompanied by a
  clear statement of generally expected results, and that any incentive given for a review is disclosed.
- **Do** disclose plainly what the FE does **not** include (1 city / 3 niches / Hungry only) **above**
  the buy button, not in the OTO flow. Bait-and-switch funnels are the #1 refund driver and the #1
  chargeback/complaint driver.
- **Do** make the recurring tier's terms (price, interval, cancellation) unmissable at the point of
  purchase, and make cancellation one step in-app.

### A7.3 DON'T — the prohibited-claims list to paste into affiliate terms

- **No income or earnings claims of any kind** — no "$5k/month with 10 clients", no "one client pays
  for it for a year", no screenshots of dashboards/bank balances/commission ledgers, **no "results
  typical" hedging in place of substantiation**, and no disclaimer that contradicts the main claim.
  *(Note: the brief's own "Consultant economics: land at $497 setup + $197/mo per client" is exactly
  the sentence that must not survive into launch copy without substantiation and counsel review.)*
- **No** "guaranteed clients", "guaranteed meetings", "guaranteed results".
- **No** claims the product does something it does not: it does **not** book appointments, does **not**
  contact businesses for you, does **not** show real occupancy, does **not** cover every business, does
  **not** work on platforms without public online booking.
- **No** fake/AI-generated reviews, no reviews from people who never used it, no undisclosed
  insider/family reviews, **no suppressing or hiding negative reviews** (16 CFR 465).
- **No** fake scarcity or fake countdowns that reset, no fabricated "spots left", no fabricated
  price-increase timers.
- **No** implying platform endorsement, partnership, or affiliation with Booksy/Vagaro/Fresha/etc.,
  and **no use of their logos** in sales material.
- **No** claim that the tool is "undetectable", "bypasses blocks", or "gets around" anything — that
  copy is simultaneously a marketing claim and a confession about §1201.
- **No** health/medical claims where clinics are mentioned.
- **No** spam: affiliates must send only to lists with permission; no purchased lists; no cold SMS
  blasts promoting the launch (that is the affiliate's TCPA problem and the vendor's reputational one).

### A7.4 Enforcement mechanics

Prohibited-claims list in the affiliate terms → acceptance recorded → weekly sampling during launch →
one written warning → **link termination and commission clawback** for a repeat. Publish the
consequence in advance; it is what makes the first warning work.

---

## A8. The four founder-stated risks — engineering, product and copy mitigation

### A8.1 Risk 1 — "Vacancy isn't always vacancy" (PRD R1)

*Blocked slots, staff days off and walk-ins do not show online.*

**Engineering.** Already largely designed in 35-scoring-model — implement it exactly:
- **Published capacity envelope** (p90 of offered-span minutes over 6 weeks) as the denominator, so a
  4-hour online window is not scored as 50% vacant.
- **Unit eligibility** rules kill phantom capacity (must have offered a slot on that DOW; ≥2 distinct
  days in 14; not 100% vacant for 14+ days → `ghost_staff`).
- **`suspect_walk_in`** signature — high vacancy **+** near-zero lead time **+** unusually *low*
  week-over-week variance → ×0.60 confidence **and a display cap: bucket + slot count, no dollars**.
- **`suspect_total_blackout`** (V ≥ 0.97 all days) is a **hard refusal**, not a penalty.
- The **multiplicative** confidence formula, so one fatal factor cannot be averaged away.
- **Days with `env = 0` drop out of both sums.**

**Product.**
- The **"What we could not see"** block (FR-4.5) is a first-class element on Business Detail, not fine
  print — it lists blocked slots, staff days off, walk-ins, phone bookings, and the party-size caveat
  for C4.
- Confidence bands gate what may be rendered; below 0.35 the business is **excluded from lists, Map
  and Pitch** entirely.
- The **opener script asks the question**: *"How much of your book is walk-in?"* — engineering the
  owner into correcting us before he catches us is the single best mitigation in the product, and it
  is free.
- The walk-in case has its **own pitch** ("your online booking isn't carrying any weight — that's
  leaked revenue too"), so the detector does not cost us the sale.

**Copy.** The unit lock (§A5.3). Banned tokens enforced by test. Ranges, never point estimates. The
price source named on the artifact. "Not available online", never "booked".

### A8.2 Risk 2 — "Anti-bot on the big platforms" (PRD R2, the whole-project risk)

**This is where I disagree with the brief, and the disagreement is the recommendation.** The brief's
mitigation ("headless browsers, residential rotation, and patience") *is itself the legal risk*
(§A1.3). Residential-proxy rotation exists to make automated traffic look like a consumer, which is
the fact pattern that converts a defensible read into circumvention and misrepresentation — and it is
also the largest COGS line (PRD R7).

> **Recommendation R-BOT-1 — the tiered access ladder, with a hard ceiling.**
>
> | Tier | Technique | Status |
> |---|---|---|
> | **1** | Plain HTTP GET, honest UA, honest datacentre IP, robots-honouring, rate-limited | **Allowed** |
> | **2** | Headless browser (unmodified, honest UA, no stealth plugins) for JS-rendered pages | **Allowed** |
> | **3** | Residential/mobile proxy rotation, fingerprint spoofing, consumer-UA impersonation, CAPTCHA solving, challenge bypass | **PROHIBITED. No exceptions. Not a config flag. Not a "temporary" fix.** |
>
> **If a platform cannot be read at tier ≤2, it does not get an adapter** (or its adapter is retired).
> That is a product decision made once, deliberately, rather than an engineering escalation made at
> 2am by whoever is on call.

**Engineering.**
- The ladder is enforced at the egress client, not per adapter; there is **no code path** for tier 3
  and no proxy vendor in the dependency tree (CI check).
- Circuit breakers, backoff, and the per-adapter kill switch (§A6.8).
- **Graceful degradation is designed-for, not exceptional:** on block → serve last-good, explicitly
  labelled stale with its age (FR-3.5); confidence decays with freshness (24h→1.00, 48h→0.90,
  72h→0.75, 168h→0.40, 336h→0.00) and refusal arrives on its own.
- **Bias the roadmap toward first-party-hosted widgets (R-PLAT-1)** — small-business sites do not run
  enterprise bot mitigation. This is the durable answer to the anti-bot risk, and it happens to be the
  low-legal-risk answer too.
- **Prove it in the spike** (45-flows §10 already gates the project on this): the spike must report
  success rate **at tier ≤2 only**. A spike that succeeds by using tier 3 has proven nothing we are
  allowed to ship.

**Product.** Coverage is displayed honestly ("38 of 91 have online booking"; FR-2.3). A dead adapter
degrades one platform's businesses to `insufficient_data`, never to a wrong number. Adapters are a
value-drop cadence, so losing one is a roadmap event, not a refund event.

**Copy.** Never claim undetectability or bypass. Never promise a specific platform by name in the
sales copy unless its adapter is live **and** passing canary — say "6 platforms at launch, more added
monthly", and list them in a page that can change.

### A8.3 Risk 3 — "Coverage" (PRD R3)

*Only businesses with online booking show up.*

**Engineering.**
- Record `not_bookable` with a re-check horizon (FR-2.3) so the denominator is real and improves.
- **Pre-compute coverage for the top metro × niche combinations before launch** and store it, so the
  city picker can warn *before* the user spends a scan credit.
- Manual add by pasted booking URL (FR-2.5) as the escape hatch for a thin market.

**Product.**
- **Show the coverage ratio in the scan result header, always.** The brief's framing is correct and
  should be in the UI verbatim: businesses with online booking **already pay for software**, so they
  are the qualified buyer — coverage is a *qualifier*, not a limitation.
- Instant demo dataset on first login (FR-1.4) so time-to-first-value is never gated on a crawl —
  this is also the primary refund mitigation.
- **A coverage floor in the go/no-go gate (§B12)**: if the median top-25 metro × top-3 niche
  combination yields fewer than a defined number of scored businesses, the FE is a refund magnet and
  either the niche list or the price must change.

**Copy.** Say the coverage limitation on the sales page, in the FAQ, and in the onboarding — three
times, in the founder's own framing. A buyer who learns it from the product does not refund; a buyer
who discovers it after paying does.

### A8.4 Risk 4 — "Never touch a slot" (PRD R4 — launch-ending)

*One "the tool booked a fake appointment" story kills the launch.* Agreed, and this is the risk I would
spend the most engineering on relative to its probability, because its cost is unbounded.

**Engineering — structural incapability, not policy (FR-3.2), in seven layers:**
1. **The egress client refuses any method but `GET`/`HEAD`.** Not a check inside a branch — the
   function that builds a request has no parameter for a body or another method.
2. **Auth/booking path deny-list** (`/book`, `/confirm`, `/hold`, `/reserve`, `/checkout`, `/cart`,
   `/pay`, …) — fail closed, log **P1**.
3. **`interaction_depth ≤ select_only`** (§A3.3). The headless driver exposes no `click()` to adapter
   code except on registered `select`/date-picker elements identified by an allow-list of roles; a
   click on any element whose text or role matches `book|confirm|reserve|submit|pay` **throws and
   aborts the scan**.
4. **No form-field typing at all.** The driver's `type()`/`fill()` is not exported to adapters. Since a
   booking requires a name/email/phone somewhere, removing text entry removes the capability.
5. **Adapter interface exposes no write verb** (45-flows §3 already states this): the contract is
   `fetch(bookingRef, horizonDays) → CanonicalGrid`, and nothing else.
6. **Network-level assertion in staging:** a proxy in front of the crawl fleet that **alarms on any
   non-GET/HEAD request to any booking host**, independent of application code. Two independent
   mechanisms, because the whole point is that a single bug must not be sufficient.
7. **Never store anything that could complete a booking** — no consumer names, no emails, no phones for
   the *booking flow*, no payment instrument, ever.

**Testing (release-blocking, §B9.3):** every adapter is run against a fixture containing a booking form
and a "Confirm booking" button, and must produce a grid without touching either; a mutation test that
deliberately adds a POST to an adapter must **fail CI**; a monthly game-day where an engineer *tries*
to make the fleet issue a write and documents that they could not.

**Product.** A visible **"Read-only — OpenChair never books, holds, or cancels anything"** badge on
Business Detail and on the one-pager. It is a trust asset with owners, a differentiator, and a standing
public commitment that makes the internal rule harder to erode.

**Copy.** Say it on the sales page (the brief already does), in onboarding, in the bot page, and in the
support macros. And do **not** ship any feature that reserves, holds, "checks a slot for you", or
"tests the booking flow" — not in an OTO, not in the $997 tier, not ever. **The moment the product can
write, every legal argument in this document weakens at once.**

---

# Part B — QA & acceptance

## B9. Test strategy

Five layers, chosen so that each catches a class the others structurally cannot. The layer that
matters most is the last one, and §B9.5 is the most important section in this document.

| Layer | What it proves | Where it runs | Blocking? |
|---|---|---|---|
| **Unit — scoring math** | The Meter computes what the spec says | CI, every commit | Yes |
| **Adapter contract vs recorded fixtures** | The parser understands the markup it was written for | CI, every commit | Yes |
| **Integration** | Queue → adapter → snapshot → Meter → rollup → alert works end to end | CI nightly + pre-release | Yes |
| **E2E / journey** | A buyer can get from login to a printed one-pager | Pre-release | Yes |
| **Canary (production)** | The adapter is returning **right** data, not merely **parseable** data | Continuously, in production | **Yes — publication gate** |

### B9.1 Unit — the scoring math

**`meter-reference.mjs` already exists in `openchair/docs/` with 14 passing assertions under
`node --test`** [VERIFIED — the doc pastes real output: 14 tests, 14 pass, ~338ms]. That file is the
spec: per 35-scoring §9, *if the production implementation and the reference disagree, the reference is
right until doc and output are updated together.*

**Therefore:**
1. **The production Meter is tested against the reference, not re-tested from scratch.** A
   `meter-parity.test.js` generates ~10k randomised scan inputs (property-based over staff count,
   horizon, envelope history, anomaly flags, price tiers) and asserts the production implementation
   and `meter-reference.mjs` agree **bit-for-bit** on `V`, `C`, band, bucket, refusal reasons, and the
   revenue band. Any divergence fails the build. This makes drift structurally detectable rather than
   a discovery made in front of a customer.
2. **The 14 existing assertions are ported unchanged** into the production suite as a regression floor.
3. **Add coverage for what the reference tests do not yet assert, in priority order:**
   - **Every one of the 7 hard gates fires independently** (35-scoring §2.3), including the case that
     matters most: `C = 0.712` (healthy) **still refuses** on `need_two_successful_scans`.
   - **Every anomaly penalty** applies its exact multiplier and **stacks multiplicatively**.
   - **Display caps**: `suspect_walk_in` and `multi_location` suppress dollars **at any confidence**.
   - **DST both transitions** (spring forward / fall back), and a business scanned from a different
     timezone — assert bins land on local wall clock and `dst_unresolved` fires.
   - **Envelope p90** behaviour: one freak late booking does not inflate it; a blocked lunch hour is
     absorbed over weeks; zero history → hard refusal.
   - **C5 monotonicity**: the lead-time proxy is monotone decreasing and log-linear in lead days, and
     **never emits dollars**.
   - **Freshness piecewise-linear knots** at exactly 24/48/72/168/336h.
   - **Dead-zone gates**: the documented Wednesday case *passes* consistency (6/8) and *fails*
     effect-size — assert it is rejected. This is the test that stops us shipping noise as insight.
   - **Ledger lift is only claimable net of the cohort**, and `ATTRIBUTION_CAVEAT` is present in the
     rendered output, not merely exported.
4. **Golden-output test on generated copy** (`copy-guard`): render every template variant across
   Hungry/Slammed × high/medium/low confidence × walk-in-suspected, and assert **zero banned tokens**
   (§A5.3), presence of the unit phrase "online-bookable", presence of the range, presence of the
   price source, presence of the data-age string, and presence of the compliance footer. **This is a
   legal control implemented as a unit test** — treat a failure as a P1, not a copy nit.

### B9.2 Adapter contract tests against recorded fixtures

**Fixture format** (one directory per case; everything checked into the repo, no network in CI):

```
fixtures/<platform>/<case-slug>/
├── meta.json          # provenance + expectations header
├── request.json       # method, path template, headers we sent (UA asserted here)
├── response/
│   ├── index.html     # or index.json for JSON endpoints
│   ├── xhr-01.json    # subsequent XHRs the page makes, in order
│   └── ...
├── expected.json      # the CanonicalGrid the adapter must produce
└── notes.md           # why this case exists; what broke when it was captured
```

`meta.json`:

```json
{
  "platform": "booksy",
  "case": "multi-staff-partial-week",
  "captured_at": "2026-09-04T02:14:00Z",
  "captured_by": "fixture-recorder@1.4.0",
  "adapter_version_at_capture": "booksy@1.2.0",
  "capability_class": "C1",
  "page_schema_hash": "sha256:…",
  "redaction": {
    "staff_names": "stripped-at-capture",
    "images": "removed",
    "consumer_pii": "none-present",
    "business_identity": "anonymised: bk-fixture-0031"
  },
  "expectations": {
    "units": 4, "days": 7, "open_minutes": 4380, "envelope_minutes": 9600,
    "anomaly_flags": [], "capability": "C1"
  },
  "source": "public booking page, anonymous GET, robots-permitted at capture time"
}
```

**Fixture rules (non-negotiable):**
- **Fixtures are redacted at capture time.** The recorder strips staff names (R-PII-1), removes all
  images (R-COPY-1), and anonymises business identity before the fixture is written to disk. A fixture
  containing a real staff name fails a CI scan.
- **Fixtures are inputs, not truth about the world.** They prove the parser handles a shape. They
  **cannot** detect that the live shape has changed — that is the canary's job, and conflating the two
  is the mistake this whole strategy exists to prevent.
- Every fixture is byte-frozen; a fixture is **never edited** to make a test pass. A changed page is a
  **new fixture** with a new `captured_at`, and the old one is kept until deliberately retired, so the
  suite accumulates a regression history of every markup change we have survived.
- **Required case set per adapter (minimum 12):** happy path single-staff · happy path multi-staff ·
  fully booked (zero open) · fully open · closed day inside the week · truncated booking window
  (`release_limited`) · staff added mid-week · staff removed / ghost profile · non-uniform slot pitch ·
  DST-transition week · **challenge/CAPTCHA page** (must yield `captcha` + refusal) · **page containing
  a booking form and a Confirm button** (must yield a grid and touch neither) · empty/no-booking page ·
  malformed/truncated HTML (must fail cleanly, never partially).
- **Refresh cadence:**
  - **Weekly** automated live-shape check per adapter (out-of-band from the crawl, 1 request per
    adapter): fetch a canary business page, compute the `page_schema_hash`, and diff against the
    fixture header. A change opens a **P2 ticket automatically** with the diff attached.
  - **Monthly** mandatory refresh of the happy-path fixtures per active adapter.
  - **Immediately** on any canary alarm, block, or parse-rate drop — the fixture diff is step A3 of the
    45-flows §9 canary flow.
  - **Any adapter whose fixtures are >90 days old is flagged in the Ops console and is a go/no-go
    blocker** (§B12).
- **Contract assertions applied to every adapter, generically** (the suite is written once, run per
  adapter): output validates against the CanonicalGrid schema · `dayOffset ∈ 1..7` and day 0 excluded ·
  `openMin ≤ envelopeMin` for every unit-day · staff identified by `staff_hash` only · **no name-shaped
  string anywhere in the output** (regex + entropy check) · declared capability class matches the
  shape · UA header asserted · **non-GET rejected** · **`Set-Cookie` not persisted** · auth-path URL
  refused · challenge page → `captcha` + refusal.

### B9.3 Integration tests

Run against a seeded MariaDB, in-process, no network:
- **Shared-scan dedupe:** 5 tenants watching one business ⇒ exactly **1** `oc_scan_run` for the day
  and **1** outbound fetch. (This is a COGS test as much as a correctness test.)
- **Full pipeline:** queue → adapter (fixture) → `oc_scan_snapshot` → `oc_vacancy_daily` → Meter →
  `oc_deadzone_cell` → alert fan-out to every watcher.
- **Idempotency:** re-running the same scan for the same `(business_id, observed_on)` does not
  duplicate rows or double-count the cost (UNIQUE constraint is exercised, not assumed).
- **Removal cascade:** a suppression request deletes every child row, leaves the redacted tombstone,
  and a subsequent **discovery sweep does not revive the business**.
- **Tenant deletion:** removes only the tenant layer; other tenants' views are byte-identical before
  and after; `watcher_count` recalculates and the unwatched business falls out of the crawl.
- **Retention:** rows past their horizon are purged; the assertion job alarms when they are not.
- **Circuit breaker + kill switch:** N consecutive failures opens the circuit; the kill switch drains
  the queue, freezes publication, and flips affected businesses to `insufficient_data` (**not** to a
  score of 0).
- **NFR-7 isolation:** with the crawl fleet at full load, the existing bridge-service capture path
  stays under its **<300 ms** SLO. This is a **hard requirement** and therefore a **hard test** —
  assert p99, not mean.

### B9.4 E2E / journey tests

Playwright against a staging stack with a fixed, seeded dataset (never live crawl data):
1. **The 60-second demo** (45-flows §4): search "barbershop, Tampa" → pins by 4s (NFR-1) → full set by
   25s (NFR-2) → click reddest → detail <400ms (NFR-3) → Pitch → one-pager <3s (NFR-4). Assert the
   timings, not just the screens.
2. **The Slammed flip:** click a green pin → "next available: 19 days" → Pitch renders the **capacity**
   variant. (The demo's second half is the OTO1 sale; if it breaks, revenue breaks.)
3. **Refusal path:** a below-floor business shows "Not enough data — rescanning tonight", **no number
   anywhere on screen**, and is **absent** from list, Map and Pitch.
4. **Stale path:** a 9-day-old scan shows the stale banner, the `≈` prefix on dollars, and the age.
5. **Degraded path:** map tiles fail → **list-only mode** is fully usable (40-ux-spec calls this a hard
   requirement).
6. **Mobile walk-in:** the one-screen card is usable at 375px, outdoors-contrast, one-handed.
7. **Accessibility (NFR-8):** axe pass on the four core screens; keyboard-only path through the whole
   demo flow; **no meaning conveyed by colour alone** (assert the redundant encoding on pins).
8. **Print:** the one-pager PDF at the real page spec — margins, no clipped grid, legible at 100%,
   compliance furniture present.
9. **Quota:** FE limits (1 city / 3 niches / Hungry only) enforced; the OTO1 upsell state renders
   correctly rather than 500-ing.

### B9.5 THE CANARY — detecting wrong-but-plausible adapter output

> **This is the highest-value control in this document.** Every other test proves the code does what we
> wrote. The canary is the only thing that proves the *world* still matches what we wrote. The failure
> it exists to catch is silent, systemic, and lands in front of a paying customer inside one nightly
> cycle: **a selector breaks, the parser returns 0 open slots, and every shop on that platform looks
> slammed.** The Meter cannot help — 0 open slots out of a healthy envelope is a perfectly *valid*
> input that produces a perfectly confident, completely wrong score. Then the 8am digest tells 400
> consultants that every barbershop in Tampa is booked solid, and the Slammed pitch — *"you're turning
> people away"* — gets read aloud in a shop that is empty. **That is the single most humiliating
> outcome the product can produce, and it is one CSS class away at all times.**
>
> The symmetric failure is just as bad and easier to miss: a selector matches *too much*, every slot
> reads open, and the Hungry list fills with thriving businesses.

#### B9.5.1 Four independent detectors

No single signal is both sensitive and specific enough. Each detector below fails in a different
direction, so requiring agreement between them gives sensitivity **and** an acceptable false-alarm rate.

**Detector 1 — Structural invariants (per response, instant, zero false positives).**
Cheap assertions that a *correct* parse can never violate. Any violation fails that single scan
immediately (`status='partial'`, no publish):
- `page_schema_hash` matches the fixture header, or differs → **degraded mode**, publish blocked for
  that business pending review.
- The parse consumed a **non-trivial fraction of the DOM** — if the selector set now matches 0 nodes,
  or 100× the historical node count, the parse is wrong regardless of what it produced.
- `openMin ≤ envelopeMin`; unit count within ±1 of the trailing median; slot-pitch distribution has the
  same modal value as the trailing 14 days (`slot_pitch_changed` already exists as a flag).
- The page contains at least one recognisable availability affordance (a slot-like token) — a page that
  parses to zero slots **and** contains no slot-like tokens is a *layout change*, not an empty shop.

**Detector 2 — Sentinel businesses (per adapter, per run — the ground-truth anchor).**
**6–10 sentinel businesses per adapter**, chosen and maintained deliberately:

| Sentinel type | Count | Expected signature | Catches |
|---|---|---|---|
| **Partner shops** (2 per adapter) — real owners who let us verify against their actual book, in exchange for free Ledger access | 2 | Known true occupancy, refreshed weekly by the owner | **Everything.** This is real ground truth and the only detector that can validate the *magnitude* of the score, not just its stability |
| **Known-busy** — a high-demand shop that is reliably near-full | 2 | `V` low, stable | Selector matching too much (false Hungry) |
| **Known-open** — a new or quiet shop with reliably wide availability | 2 | `V` high, stable | **Selector matching nothing (false Slammed) — the headline failure** |
| **Known-closed-one-day** — closed Sundays, say | 1 | Exactly one `env = 0` day, same DOW every week | Day-boundary, timezone and DST bugs |
| **Structural edge** — many staff, or unusual slot pitch | 1–2 | Stable unit count | Pooling and envelope regressions |

Sentinel checks run **first in every adapter's nightly batch, before any customer business is
scanned.** If sentinels fail, the batch never starts — we do not spend the money or the requests, and
no wrong number ever reaches a row. Alarm when a sentinel's `V` moves more than **20 points** from its
own trailing 14-day median, or when its unit count or closed-day pattern changes.

**Recruiting the two partner shops per adapter is the single highest-value QA investment in the
project** and should start during the spike, not at launch. It converts the whole accuracy question
from inference to measurement, and it doubles as case-study material for the sales page.

**Detector 3 — Cohort distribution monitor (per adapter, per run — catches the subtle systemic case).**
The population-level check 35-scoring §7.2 and 45-flows §9 already call for. Computed per adapter per
run against a **trailing 28-day baseline**, using robust statistics (median and MAD, never mean/σ —
the failure we are hunting is exactly the kind that poisons a mean):

| Signal | Alarm threshold | What it catches |
|---|---|---|
| Median `V` shift | **> 15 points** overnight (35-scoring's own number) | Wholesale parse change, either direction |
| Share of businesses at **exactly `V = 0`** | > baseline + 3·MAD, or > **20% absolute** | **The headline failure.** True zeros are rare and never sudden |
| Share at exactly `V = 1.0` | same | Inverse failure |
| Parse-success rate | **< 90% of trailing median** (35-scoring's number) | Partial breakage |
| Mean open-slot count per business | drop or rise **> 40%** | Selector scope change |
| Distinct slot-duration values collapsing to 1 | any collapse | Modal-pitch regression |
| Mean unit (staff) count per business | shift **> 25%** | Roster-selector break |
| **Cross-business `grid_sha1` collision rate** | **> 2% of the batch sharing a hash with a *different* business** | **The sharpest signal available.** Two genuinely different shops producing byte-identical grids is essentially impossible; a broken parser emitting a constant makes it universal. Near-zero false-positive rate — a strong candidate for the auto-freeze threshold |
| Day-over-day identical-grid rate **within** a business | > 60% of the batch unchanged | A stuck cache or a frozen parse |

**Detector 4 — The adapter-vs-geography discriminator (the false-positive killer).**
Every population signal above has one benign explanation: **the world actually changed.** A public
holiday, a snowstorm, a citywide event, a DST weekend, or a platform outage all move cohort statistics
legitimately, and an alarm system that cannot tell those from a broken selector will be muted within a
week — at which point the control is worthless.

> **The discriminator: partition the variance two ways and compare.**
>
> - **If the shift appears across *all* adapters within *one* geography → it is the world.** Holiday,
>   weather, local event. Log it, annotate the cohort baseline, **do not freeze**.
> - **If the shift appears across *all* geographies within *one* adapter → it is us.** Markup change,
>   selector break, platform-side change. **Freeze immediately.**
> - **If it appears in one adapter × one geography → it is probably a regional platform change** (a
>   rollout, an A/B test, a regional CDN variant). Freeze *that* partition only, page the operator.
> - **If it appears everywhere at once → suspect our own shared code** (the canonical-grid builder, the
>   envelope job, a timezone library upgrade), not any single adapter. Freeze all publishing and check
>   the deploy log first.
>
> Concretely: hold each adapter's businesses out as a control group for every other adapter operating
> in the same metro. Because most metros carry 3+ adapters, this control group is free — we already
> have it, we just have to compute against it. **Adding this one comparison is what makes the cohort
> monitor sensitive enough to be worth having**, because it lets us set the thresholds aggressively
> without generating the false alarms that would get them ignored.
>
> Additionally: reconcile against a **regional holiday calendar** (35-scoring §7.3 already requires one
> for `suspect_total_blackout`) and against a **platform-status check** — a platform-wide outage should
> present as a spike in fetch failures, not as a spike in vacancy, and if we see vacancy move while
> fetches stay healthy, that is *us*.

#### B9.5.2 The state machine, and what the user sees

```
   OK ──► WATCH ──► FREEZE ──► QUARANTINE
    ▲        │         │            │
    └────────┴─────────┴────────────┘   (only via human release, §B9.5.4)
```

| State | Trigger | System behaviour | User-visible |
|---|---|---|---|
| **OK** | all detectors green | publish normally | normal |
| **WATCH** | 1 cohort signal breached, or a single sentinel drifting | publish, but tag the run; raise a **P3**; snapshot the raw pages for diffing | nothing (deliberately — a single signal is not evidence) |
| **FREEZE** | **any** sentinel hard-fail, **or** ≥2 cohort signals, **or** the `grid_sha1` cross-business collision signal alone, **or** any structural invariant violated batch-wide | **publication halted for that adapter**; today's rows are written with `published=0`; last-good served with the **STALE** banner and its age; circuit opened; operator paged; automatic fixture diff (45-flows §9 step A3) attached to the ticket | **Last-good data, explicitly labelled stale with its age.** Scores keep decaying by the freshness factor, so a long freeze degrades to refusal on its own |
| **QUARANTINE** | frozen > 48h, or the fix requires an adapter rewrite | adapter disabled via the kill switch; its businesses become **`insufficient_data`** | "We can't read this platform right now" — **never a zero, never a stale-but-unlabelled number** |

**Fail closed, always.** If the canary itself errors, cannot compute, or has no baseline (a brand-new
adapter, or an adapter with <14 days of history), the default is **FREEZE**, not publish. A new adapter
therefore runs in **shadow mode** — scanning, scoring, storing with `published=0`, and accumulating a
baseline — for its first **14 days**. It ships to customers only after 14 consecutive green canary
runs. This is also the honest answer to "how do we know a new adapter is right on day one": we don't,
so we don't publish it.

#### B9.5.3 What the frozen batch is worth: nothing

**Do not publish a frozen batch retroactively after the fix.** Discard it and re-scan. Data collected
by a parser we later learned was broken has no evidentiary value, and back-filling it would silently
overwrite the Ledger's history — the retention product's entire credibility rests on that series being
trustworthy. `oc_vacancy_daily` rows from a frozen window are marked `void` with a reason, kept for
forensics, and **excluded from every rollup, trend, dead-zone accumulator and Ledger report.**

#### B9.5.4 Release from FREEZE requires all five

1. A **named human** has reviewed the fixture diff and identified the root cause.
2. A **new fixture** is recorded from the live shape and checked in (never an edited old one).
3. The adapter contract suite passes against **both** the new fixture and every historical one that is
   still meaningful.
4. **Three consecutive green canary runs** in shadow mode.
5. The release is **logged** with who, when, why, and the diff — and the canary configuration change,
   if any, is logged separately (see below).

#### B9.5.5 The canary of the canary

A control that can be silently disabled is not a control.
- **Canary thresholds live in versioned config, not a database row an operator can edit at 3am.**
  Changing one is a code review.
- **Every threshold change and every manual FREEZE release is audit-logged** and appears in the weekly
  ops review.
- **Monthly game day (chaos test):** deliberately deploy a broken adapter to staging — one that returns
  0 slots for every business — and **assert the canary freezes it before any score is published**.
  Measure **MTTD**; target **< 1 scan cycle** (i.e. it never publishes). Alternate the injected fault:
  all-zero, all-open, unit-count collapse, constant-grid, one-geography-only.
- **Track canary false negatives as a launch metric** (PRD §9 already lists it). A false negative found
  by a *customer* is a **P0 post-mortem**, not a bug ticket.
- **Sentinel freshness is monitored:** a partner shop that stops responding to weekly verification is a
  degraded sentinel and must be replaced within 14 days; an adapter running on fewer than 4 live
  sentinels drops to WATCH automatically.

---

## B10. Acceptance criteria (MVP) — Given / When / Then

### B10.1 Onboarding & scan setup

- **AC-1.1** *Given* a new buyer logging in for the first time, *when* the dashboard loads, *then* a
  demo dataset is present and explorable **within 3 seconds**, labelled "Sample data", with no crawl
  required. *(FR-1.4 — the primary refund mitigation.)*
- **AC-1.2** *Given* an FE-tier user who has used 3 niches, *when* they try to start a 4th, *then* the
  quota is enforced with a clear upgrade path and **no scan credit is consumed**.
- **AC-1.3** *Given* a scan of ~50 businesses, *when* it runs, *then* results stream progressively —
  first pins **<4s**, full set **<25s** — and a blocking spinner is **never** shown. *(FR-1.3, NFR-1/2.)*
- **AC-1.4** *Given* a city+niche whose known coverage is below the useful floor, *when* the user
  selects it, *then* they are warned **before** the scan credit is spent.

### B10.2 Discovery

- **AC-2.1** *Given* a niche and city, *when* discovery completes, *then* each candidate has name,
  address, website (where public) and phone, and each is classified `bookable` / `not_bookable` /
  `unknown` with a platform where bookable. *(FR-2.1/2.2.)*
- **AC-2.2** *Given* a completed discovery, *when* results render, *then* the coverage ratio is shown
  verbatim in the header — "38 of 91 have online booking". *(FR-2.3.)*
- **AC-2.3** *Given* any discovery or scan path, *when* it executes, *then* **no account creation is
  attempted on any platform**, and the audit log shows zero requests to auth-path URLs. *(FR-2.4, §A3.)*
- **AC-2.4** *Given* a business previously suppressed by a removal request, *when* a new market sweep
  covers its area, *then* it is **not** re-created and does not appear for any tenant. *(§A6.6.)*

### B10.3 Scanning (Radar)

- **AC-3.1** *Given* 5 tenants watching the same business, *when* the nightly scan runs, *then*
  **exactly one** fetch and one `oc_scan_run` row occur for that day. *(FR-3.1.)*
- **AC-3.2** *Given* any adapter, *when* it executes against a fixture containing a booking form and a
  Confirm button, *then* it produces a grid, issues **only GET/HEAD**, and touches neither control.
  *(FR-3.2, §A8.4 — release-blocking.)*
- **AC-3.3** *Given* a platform returning 429s, *when* the threshold is hit, *then* the circuit opens,
  backoff applies, no further requests are sent that cycle, and the operator is alerted. *(FR-3.3.)*
- **AC-3.4** *Given* any fetch, *when* it completes, *then* an audit row exists with target URL, method,
  status, bytes, robots directive, tier, egress region, UA and initiator. *(FR-3.4, §A6.10.)*
- **AC-3.5** *Given* a business whose last successful scan was 4 days ago, *when* the user opens its
  detail, *then* last-good data is shown with an explicit stale banner and its age, and the dollar
  figures carry the `≈` prefix. *(FR-3.5.)*
- **AC-3.6** *Given* a `robots.txt` disallowing our path, *when* the scheduler reaches that business,
  *then* no request is made and the run records `robots_denied`. *(§A6.2.)*
- **AC-3.7** *Given* a page serving a bot challenge, *when* the adapter encounters it, *then* the run
  records `captcha`, **no solve is attempted**, and the Meter refuses to publish. *(§A3.2.4.)*
- **AC-3.8** *Given* the crawl fleet at full load, *when* the bridge-service capture path is exercised,
  *then* p99 stays under **300 ms**. *(NFR-7.)*

### B10.4 Meter (scoring & display)

- **AC-4.1** *Given* a business with `C ≥ 0.75`, *when* detail renders, *then* score, range and dollars
  appear with the confidence band labelled and its **reason** stated (not a bare decimal).
- **AC-4.2** *Given* `C < 0.35` **or** any hard gate, *when* the business is processed, *then* **no
  number is rendered anywhere**, the UI says "Not enough data — rescanning tonight", and the business
  is **excluded from list, Map, alerts and Pitch**. *(FR-4.2, 35-scoring §2.3.)*
- **AC-4.3** *Given* a business flagged `suspect_walk_in`, *when* anything renders, *then* bucket and
  slot count appear and **no dollar figure appears** — at any confidence level.
- **AC-4.4** *Given* `V ≥ 0.97` on every day, *when* scoring runs, *then* the business is **hard-refused**
  and **absent from the Hungry digest**. *(The highest-embarrassment path.)*
- **AC-4.5** *Given* any Empty-Chair Revenue rendering, *when* displayed, *then* it is a **range**, its
  **price source is named**, and Recoverable Revenue never appears as the headline. *(FR-4.3, PRD §8.4.)*
- **AC-4.6** *Given* any business detail, *when* it renders, *then* the **"What we could not see"**
  block is visible without scrolling past the fold on desktop and is not collapsed by default.
  *(FR-4.5.)*
- **AC-4.7** *Given* any generated artifact, *when* rendered, *then* it contains "online-bookable" and
  **zero banned tokens**. *(FR-4.6, §A5.3 — automated.)*
- **AC-4.8** *Given* a business with <8 weeks of observations, *when* the Dead-Zone Map renders, *then*
  cells failing the minimum-observation or effect-size gates are **not** presented as chronic.
  *(FR-4.7.)*
- **AC-4.9** *Given* the production Meter and `meter-reference.mjs`, *when* the parity suite runs over
  10k randomised inputs, *then* they agree exactly on `V`, `C`, band, bucket, refusal reasons and the
  revenue band.

### B10.5 Prospect management

- **AC-5.1** *Given* a scan result, *when* the Map renders, *then* pins encode bucket by colour **and**
  by a second non-colour channel, and the list is fully usable if map tiles fail. *(FR-5.1, NFR-8.)*
- **AC-5.2** *Given* an active filter set, *when* the user changes any filter, *then* list and map stay
  in sync and refusal-band businesses never appear regardless of filter.
- **AC-5.3** *Given* a mobile viewport at 375px outdoors, *when* the user opens a watched business,
  *then* the one-screen walk-in card renders the business, distance and the pitch sentence, one-handed.
  *(FR-5.4.)*
- **AC-5.4** *Given* a CSV export, *when* it downloads, *then* it contains **no staff names** and
  carries the unit-lock header and the data-age column. *(§A4.2.)*

### B10.6 Alerts

- **AC-6.1** *Given* a tenant in `America/New_York`, *when* the digest sends, *then* it arrives at 8am
  **local**, split by bucket, ranked by change. *(FR-6.1.)*
- **AC-6.2** *Given* any digest, *when* it is generated, *then* every recipient is a **consultant**, and
  a test asserts **no prospect business address is ever a recipient**. *(FR-6.3 — assert the negative.)*
- **AC-6.3** *Given* an adapter in FREEZE, *when* the digest generates, *then* businesses on that
  adapter are **omitted**, not included with stale or zero scores.

### B10.7 Pitch

- **AC-7.1** *Given* a business with sufficient confidence, *when* the user taps Pitch, *then* a
  one-pager renders **<3s** containing our own rendered grid (**never a screenshot of the platform**),
  the dollar range, the price source, the data age, the read-only badge and the disclosure block.
  *(FR-7.1, NFR-4, R-COPY-1.)*
- **AC-7.2** *Given* the same business, *when* the user switches variant, *then* Hungry and Slammed
  render distinct offers from the **same** scan. *(FR-7.2.)*
- **AC-7.3** *Given* the email variant, *when* it renders, *then* sender identity, physical address and
  the opt-out line are present and **cannot be deleted** by the editor. *(§A4.5.)*
- **AC-7.4** *Given* any pitch artifact, *when* the user reaches the end of the composer, *then* the
  terminal action is **Copy** or **Download** — **there is no Send button, and no code path transmits
  to a prospect**. *(PRD §8.7, R-OUT-1 — assert the negative.)*
- **AC-7.5** *Given* a first-time pitch generation, *when* the composer opens, *then* the
  "Before you contact anyone" compliance interstitial is shown and the acknowledgement is recorded with
  user, timestamp and version.
- **AC-7.6** *Given* a Canadian target business, *when* the email variant renders, *then* the
  CASL-specific notice and template variant are used. *(§A4.5.)*
- **AC-7.7** *Given* the PDF, *when* printed at the specified page size, *then* nothing is clipped and
  the grid is legible at 100%. *(FR-7.3.)*

### B10.8 Ledger

- **AC-8.1** *Given* ≥2 weekly observations, *when* the Ledger renders, *then* the 8-week trend shows
  with gaps drawn as gaps (never interpolated) and weeks from a **void** (frozen) window excluded.
- **AC-8.2** *Given* any client-facing Ledger export, *when* rendered, *then* the attribution caveat is
  **printed on the artifact**. *(FR-8.3.)*
- **AC-8.3** *Given* scores computed under two model versions, *when* a historical Ledger renders,
  *then* it is not silently re-rendered under the new version without relabelling. *(35-scoring §9.)*

### B10.9 Operations & compliance

- **AC-9.1** *Given* the Ops console, *when* an operator views adapter health, *then* success rate,
  block rate, circuit state, canary state, **fixture age** and the risk rating are all visible.
  *(FR-9.1.)*
- **AC-9.2** *Given* an injected broken adapter returning 0 slots for every business, *when* the
  nightly run executes, *then* the canary **freezes publication before any score reaches a user**.
  *(FR-9.2 — the game-day test.)*
- **AC-9.3** *Given* a removal request, *when* it is processed, *then* it is acknowledged within
  **1 business day**, effected within **5**, cascades globally across all tenants, leaves a tombstone,
  and is logged in `data_rights_requests`. *(FR-9.3.)*
- **AC-9.4** *Given* a kill-switch activation, *when* an operator triggers it from a phone, *then* the
  adapter stops within **60 seconds** without a deploy. *(§A6.8.)*
- **AC-9.5** *Given* the nightly retention job, *when* it completes, *then* an assertion confirms no row
  exists past its horizon, and alarms if any does. *(§A6.9.)*

---

## B11. Failure-mode table

Severity: **S1** launch-ending / customer-visible wrong data · **S2** major degradation · **S3** partial
· **S4** cosmetic.

| # | Failure | Detection | User-visible behaviour | Recovery | Sev |
|---|---|---|---|---|---|
| F1 | **Adapter selector breaks → 0 open slots, everything looks Slammed** | Canary D1+D2+D3 (`V=0` share, `grid_sha1` collisions, sentinel `V` drift); D4 confirms adapter-not-geography | Last-good with STALE banner + age; after 14d → "Not enough data" | FREEZE → fixture diff → new fixture → 3 green shadow runs → release. Frozen rows **voided, not published** | **S1** |
| F2 | **Selector over-matches → everything looks Hungry** | Same detectors, inverse thresholds; known-busy sentinels | As F1 | As F1 | **S1** |
| F3 | **A booking or hold is created** | Egress client method guard; independent network proxy alarm; audit reconciliation | None (must never happen) | Halt all crawling; notify platform and business immediately; full post-mortem; do not resume without a root cause | **S1 — launch-ending** |
| F4 | **Walk-in shop scored as empty and pitched as dead** | `suspect_walk_in` signature; owner complaint; the opener's own question | Bucket + slot count, **no dollars**; walk-in-specific pitch variant | Flag business, suppress dollars permanently for it, add to the sentinel review | **S1 (reputational)** |
| F5 | **Holiday/closure week → shop tops the Hungry list** | `suspect_total_blackout` hard gate; regional holiday calendar; D4 (all adapters in one geography move) | Business absent from the digest; detail shows "Not enough data" | Automatic — the gate suppresses it; annotate the cohort baseline for that date | **S1 if it escapes** |
| F6 | **Platform blocks our IP / serves challenges** | 429/403 rate, `captcha_degraded`, circuit breaker | Stale banner, decaying confidence, eventually refusal | Open circuit; **never** rotate IP/UA; assess as constructive notice (§A6.7); consider retiring the adapter | S2 |
| F7 | **Cease-and-desist or platform legal contact** | Abuse inbox, monitored | That platform's businesses → `insufficient_data` | Kill switch **<1h**; preserve logs; acknowledge same day; counsel before any resumption | **S1 (legal)** |
| F8 | **Business owner objects publicly / removal request** | Removal form, abuse inbox, social monitoring during launch | Business disappears for **all** tenants immediately | Suppress + tombstone within SLA; reply personally; log; check whether the score was defensible and feed it back to the canary | S2 |
| F9 | **Meter drifts from `meter-reference.mjs`** | Parity suite over 10k randomised inputs, every commit | None (caught pre-release) | Fix production, or update reference **and** doc together (35-scoring §9) | S2 |
| F10 | **Timezone/DST error shifts every block by an hour** | DST unit tests; `dst_unresolved` flag; sentinel closed-day pattern breaks | Dead-Zone Map names the wrong hours | Fix binning; **recompute affected cells**; suppress dead-zone claims for the affected window | S2 |
| F11 | **Phantom capacity (departed staff still listed) inflates vacancy** | Unit-eligibility rules; `ghost_staff_present`; roster-churn factor | Slightly high `V`, confidence reduced | Automatic via eligibility; if systemic, tighten the window | S3 |
| F12 | **Wrong service priced (bridal package prices a beard trim)** | Slot-pitch matching; `ppm > 3× median` drop; owner correction | Dollar figure 3× reality; **assumed ticket is printed**, so the owner corrects it | Fix price matching; the printed assumption converts the failure into a conversation | S2 |
| F13 | **Thin coverage in the chosen market** | Pre-computed coverage table; scan-result ratio | "38 of 91 have online booking" shown honestly; pre-scan warning | Suggest an adjacent niche/metro; **refund risk — this is the #1 refund driver (§B12)** | S2 |
| F14 | **Crawl fleet degrades the bridge-service voice SLO** | p99 capture latency monitor (NFR-7 is a hard requirement) | Voice capture slows — **damages the existing product** | Throttle/pause the fleet automatically on SLO breach; isolate resources; capacity review | **S1 (host product)** |
| F15 | **COGS blowout** | Per-scan `cost_micros`, per-tenant attribution, daily budget alarm | Quotas bite sooner | Reduce horizon/frequency; drop the most expensive adapter; re-price. **Never** solve it by relaxing tier limits | S2 |
| F16 | **Staff name leaks into an artifact** | CI name-shaped-string scan on adapter output and fixtures; export tests | A named person appears in a sales document | Purge; hotfix the adapter's parse-time strip; audit all artifacts generated since the regression | **S1 (privacy)** |
| F17 | **Generated copy contains a banned token / an earnings claim** | `copy-guard` golden test; LLM-output runtime guard | An indefensible sentence is handed to an owner | Block render; fix template; regenerate. Treat as **P1** | S2 |
| F18 | **Affiliate publishes an earnings claim during launch** | Weekly affiliate sampling; buyer reports | FTC exposure; refund/chargeback pressure | Written warning → link termination + clawback; document the enforcement | S2 |
| F19 | **Canary itself broken or muted** | Monthly game day; threshold-change audit log; sentinel-count monitor | Nothing — until F1 happens undetected | Restore config; **P0 post-mortem** if a customer found the failure first | **S1 (control failure)** |
| F20 | **`robots.txt` or ToS changes to disallow us** | Nightly hash diff → P2 alert | None immediately | Pause expansion on that adapter; review; **honour the new directive**, kill the adapter if required | S2 |

---

## B12. Pre-launch go / no-go gate

**One meeting, one page, five sections. Every line is pass/fail. Any FAIL is a no-go — the remedy is to
move the date, not to soften the criterion.** The go/no-go owner is the founder; the accuracy and
canary sections are signed by QA; the legal section by counsel.

### B12.1 Data-accuracy sampling protocol — the core of the gate

This is the only evidence that the product is *right*, as opposed to merely *working*.

**Design**
- **Stratified sample: n = 30 businesses per live adapter.** With 6 adapters that is **N = 180**. The
  arithmetic: with 30 observations and **0** material errors, the rule of three gives roughly 95%
  confidence that the true per-adapter error rate is **below ~10%**. Fewer than 30 per adapter cannot
  support a per-adapter claim, and a whole-product average hides exactly the failure mode (one broken
  adapter) that this document exists to catch. **Every adapter is gated individually.**
- Within each adapter's 30, stratify: **10 Hungry · 10 Steady/Slammed · 5 low-confidence · 5 refused**.
  The refused stratum matters — **a wrong refusal is a product failure too** (we lost a real prospect),
  and refusals are where a lazy implementation hides its errors.
- **Blind protocol.** The verifier opens the live booking page **first** and records ground truth into
  a form (open slot count per day, staff/unit count, next-available date, closed days, published price
  for the modal service). **Only then** is OpenChair's output revealed. A verifier who sees our number
  first will unconsciously confirm it; this is the whole reason for the ordering.
- **Timing.** Verify within **90 minutes** of the scan capture. Immediately after verification, re-fetch
  and compare `grid_sha1` — **if the grid changed between scan and verification, discard the sample and
  redraw.** Otherwise we would count real-world churn as our error, which both flatters and confuses
  the result.
- **Inter-rater reliability.** Two independent verifiers on a **20% overlap**; require **Cohen's
  κ ≥ 0.80** on the bucket judgement. If verifiers cannot agree with each other, the sample cannot
  measure us.
- **Record every sample** with business, adapter, timestamps, verifier, ground truth, our output, and
  the error classification. Keep the sheet — it is the baseline for every future adapter and the
  substantiation file for accuracy claims in the sales copy (§A7.2).

**Error classification**

| Class | Definition |
|---|---|
| **Material — bucket** | We said Hungry, ground truth is Slammed, or vice versa (Steady↔adjacent is **minor**) |
| **Material — magnitude** | Vacancy Score off by **> 10 points** |
| **Material — dollars** | Empty-Chair Revenue range **does not contain** the hand-computed value |
| **Material — false publish** | We published where a hard gate should have refused *(the worst class)* |
| **Material — false refusal** | We refused where a confident, correct score was available |
| **Minor** | Unit count off by 1; next-available off by 1 day; adjacent-bucket boundary case |
| **Not an error** | The grid genuinely changed between scan and verification (sample discarded and redrawn) |

**Pass thresholds — all must hold**

| # | Criterion | Threshold |
|---|---|---|
| 1 | Overall material error rate | **≤ 5%** (≤ 9 of 180) |
| 2 | Per-adapter material error rate | **≤ 10%** (≤ 3 of 30) — **any adapter above this ships disabled** |
| 3 | **Material errors in the High-confidence band (`C ≥ 0.75`)** | **ZERO. Hard pass/fail, no tolerance.** This is PRD G5 — "never embarrass the user in front of a business owner" — expressed as a number. A confident wrong answer is the failure the whole confidence system exists to prevent; one occurrence means the gating is not working, and no amount of good average accuracy compensates |
| 4 | Bucket classification accuracy (non-refused) | **≥ 95%** |
| 5 | False-publish errors (published where a gate should have refused) | **ZERO** |
| 6 | Dollar range contains hand-computed value | **≥ 95%** of published-dollar samples |
| 7 | Read-only violations observed in the audit log across the whole sampling window | **ZERO** |
| 8 | Staff names found in any output, export or artifact | **ZERO** |

### B12.2 Canary and adapter readiness

| # | Criterion | Threshold |
|---|---|---|
| 9 | Every live adapter has **≥ 4 sentinels**, including **≥ 1 partner shop** with owner-verified ground truth | Pass/fail per adapter |
| 10 | Game day executed: an injected broken adapter is frozen **before publication** | Pass/fail, ≥ 2 fault types |
| 11 | Every live adapter has ≥ **14 days** of shadow-mode baseline | Pass/fail |
| 12 | Fixture age per adapter | **≤ 90 days**, ≥ 12 required cases each |
| 13 | Canary thresholds in versioned config; change-audit demonstrated | Pass/fail |
| 14 | Kill-switch drill: adapter stopped from a phone in **< 60s** | Pass/fail, timed |

### B12.3 Performance and stability

| # | Criterion | Threshold |
|---|---|---|
| 15 | NFR-1 search → first pins | **< 4s** at expected launch-day concurrency |
| 16 | NFR-2 full result set (~50 businesses) | **< 25s** |
| 17 | NFR-3 pin → detail | **< 400ms** p95 |
| 18 | NFR-4 detail → one-pager | **< 3s** p95 |
| 19 | **NFR-7 bridge-service capture p99 under full crawl load** | **< 300ms — hard requirement, protects the existing product** |
| 20 | Load test at **3× projected launch-day peak** | No error-rate increase, no queue starvation |
| 21 | Nightly crawl completes inside its window with ≥ 40% headroom | Pass/fail |
| 22 | COGS per watched business per month | Within the budget in 10-market-and-offer |
| 23 | Accessibility: axe clean on the 4 core screens; keyboard path through the demo | Pass/fail (NFR-8) |

### B12.4 Legal sign-off

| # | Criterion | Owner |
|---|---|---|
| 24 | §A2.4 evidence file complete for **every enabled adapter** (robots + ToS archived, dated, hashed, quoted) | Ops + counsel |
| 25 | Counsel has reviewed: the no-login rule, the tier ceiling (no tier 3), the read-only guarantee, R-COPY-1, R-PII-1, and the removal SLA | Counsel |
| 26 | **OpenTable, Resy, Mindbody and clinic adapters confirmed disabled** in the launch build (PRD Q6) | Founder |
| 27 | Privacy notice, bot page (with opt-out form), terms, and refund policy published and linked | Founder |
| 28 | Sales-page claims substantiated; **all earnings claims removed**; affiliate prohibited-claims terms published and acceptance gated | Founder + counsel |
| 29 | Generated email/SMS templates reviewed for CAN-SPAM / CASL / TCPA; compliance furniture template-locked; **no send capability exists in the codebase** (CI-asserted) | Counsel + eng |
| 30 | Business Opportunity Rule applicability opinion obtained in writing | Counsel |
| 31 | Media/tech E&O insurance in force; scraping-claim exclusions reviewed | Founder + broker |
| 32 | Data retention job verified in production; no rows past horizon | Eng |

### B12.5 Support readiness and refund-risk review

| # | Criterion | Threshold |
|---|---|---|
| 33 | **Coverage floor:** for the **top 25 metro × top 3 niche** combinations, the median combination yields **≥ 25 scored (non-refused) businesses** | Pass/fail — **if this fails, the FE is a refund magnet regardless of how good the software is.** Remedy: change the niche list, widen the radius, or change the price — not the threshold |
| 34 | Removal-request intake staffed and tested end-to-end, with the SLA published | Pass/fail |
| 35 | Abuse/legal inbox monitored with a named owner **and** a backup, for launch week specifically | Pass/fail |
| 36 | Support macros written for the **7 predictable tickets**: "my city has no results" · "this number looks wrong" · "the shop says it's not empty" · "a platform is showing stale" · "how do I cancel" · "does this book appointments?" · "a business owner contacted me" | Pass/fail |
| 37 | Refund policy published, honoured **without argument**, and the refund path is one click | Pass/fail |
| 38 | Rollback plan rehearsed: any adapter, or the whole product, can be disabled without a deploy | Pass/fail |
| 39 | Launch-week war room: named on-call, escalation path, and a **daily canary + complaint review** for the first 14 days | Pass/fail |
| 40 | Post-launch review scheduled at **day 7** and **day 30** against PRD §9 metrics — including the pass/fail one: **zero "the tool booked a fake appointment" incidents** | Pass/fail |

---

## Appendix — decisions this document asks the founder to make

| # | Decision | My recommendation | Section |
|---|---|---|---|
| D1 | Which platforms do we refuse? (PRD **Q6**) | **Refuse OpenTable + Resy for v1. Defer Mindbody + clinics.** Rebrand the $997 tier as enterprise + home-services | §A2.1 |
| D2 | Is residential-proxy / anti-bot evasion allowed? | **No. Hard tier-2 ceiling.** If it needs tier 3, there is no adapter. This is the largest single change to the brief | §A8.2 |
| D3 | Screenshot of their booking page, or our own rendered grid? | **Our own grid, always.** No third-party page captures anywhere | §A1.5 |
| D4 | Staff names? | **Never stored. Opaque index + positional label only.** Drop `display_name` from the v1 build | §A4.2 |
| D5 | Geographic scope for v1 | **US + Canada only.** Removes the entire EU/UK compliance programme from launch | §A1.4 |
| D6 | Public/shareable vacancy pages? | **No.** Tenant-private only; any share link is unguessable, expiring, `noindex`, access-logged | §A5.3 |
| D7 | Recruit partner shops for ground truth? | **Yes — 2 per adapter, starting during the spike.** The highest-value QA investment available, and it doubles as case-study material | §B9.5.1 |
| D8 | New-adapter shadow-mode period | **14 days, 14 consecutive green canary runs, before any customer sees it** | §B9.5.2 |

---

*This document is a risk and QA assessment by a non-lawyer. §A1 and §A2 require review by qualified
counsel before the first paid scan; §A4 requires a privacy specialist. Platform robots.txt and ToS
positions were not re-verified in this pass and are marked accordingly throughout — confirm before
launch.*
