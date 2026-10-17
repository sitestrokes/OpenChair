# OpenChair — project structure

**Status:** Phases 0 and 2–8 are built and green, plus the **signature visuals**
(Chair Score, five shareable cards, the redesigned business screen and share links —
[docs/60-signature-visuals.md](docs/60-signature-visuals.md)), and the **Radar pipeline** end to end on
fixtures — New scan → discovery → queue → worker (Booksy + Square adapters) → Meter → observed Chair Week —
with agency tenancy, tier gating and a security pass ([docs/70-radar-build.md](docs/70-radar-build.md)).
Live crawling stays off until the spike's go/no-go. **Phase 1 (the Radar spike) has not been run**,
and it gates everything that turns seeded data into observed data — see §8.

    197 automated checks, all passing
      72  openchair-service   (meter, ledger, canary, removal, queue, isolation)
      57  openchair-egress    (the read-only guard — this suite IS spike criterion S7)
      28  openchair-worker    (adapter contract + chair-minute normalisation)
      41  CI3 integrity       (the product-integrity rules from 05-prd.md §8)

    10 migrations, all idempotent · 24 routes serving · 0 warnings on a clean pass

Run them:

    cd engine/openchair-service && DB_NAME=db_openchair node --test
    php engine/application/modules/openchair/tests/integrity_test.php

Local: **http://localhost/OpenChair/engine/** — `admin@openchair.local` / `OpenChair!2026`
(the seed password is committed on purpose so a fresh clone can get in; change it before this
faces anything).

OpenChair is its own product on the **same platform** as Agentic Affiliates / InstantClose: the same
CodeIgniter-3 HMVC engine, the same common modules (`auth`, `users`, `setting`, `whitelabel`,
`sites`, `config`), the same dark-shell render library pattern, the same numbered-idempotent
migrations, and a sibling Node service built to `bridge-service`'s house patterns. **The product
concept is different** — it reads third-party public booking availability instead of capturing the
user's own leads — so the product module and the `oc_` schema island are new.

---

## 1. Top-level layout — mirrors `instacloseai-nakul-launch/`

```
OpenChair/
├── README.md              product overview
├── STRUCTURE.md           this file
├── docs/                  the nine-document design package (existing)
├── sql/migrations/        oc_001 … oc_006 + oc_999_rollback (existing)
├── ui/prototype.html      the clickable prototype — the UI target (existing)
└── engine/
    ├── index.php  .htaccess          front controller  (planned — ported)
    ├── application/                  the CI3 app
    ├── assets/                       css · js · img
    ├── config/                       app-level config
    ├── system/                       CI 3.1.11 framework  (planned — ported)
    ├── uploads/                      runtime, gitignored
    ├── openchair-service/            Node :4100 — sibling of bridge-service
    ├── openchair-worker/             Node — Radar crawl fleet
    └── openchair-egress/             Node — the read-only egress broker
```

Host parity: the Node services sit **inside `engine/`** exactly as `bridge-service` does, and
migrations live at **`sql/migrations/`** exactly as `ic_NNN_*.sql` do.

## 2. `engine/application/` — the CI3 app

```
application/
├── config/        config.php · routes.php · autoload.php · app_config.php   (planned — ported)
├── controllers/   Api.php — SPL/JVZoo provisioning                          (planned — ported)
├── core/          MY_Controller · MY_Loader · MY_Router · App_Controller    (planned — ported)
├── helpers/  hooks/  language/english/  logs/  models/
├── libraries/     the shared library layer                                  (planned)
├── third_party/   MX — Modular Extensions (HMVC)                            (planned — ported)
├── modules/       see §3
└── views/
    ├── oc_shell/  layout.php · _oc_rail.php · _oc_topbar.php · _icons.php · _pager.php
    ├── errors/  pages/  partials/  shared/
```

**The shell is engine-wide, not module-owned.** `views/oc_shell/` sits beside `modules/`, exactly as
the host's `views/ic_shell/` does — that is why the host moved it out of the `instantclose` module in
the first place: `whitelabel`, `setting` and `users` must render in the same chrome as the product
pages. `Oc_render_lib` (in `libraries/`) mirrors `Ic_render_lib`'s contract:

```php
$this->load->library('oc_render_lib');
$this->oc_render_lib->render('pages/radar', $this->data, [
    'page'       => 'radar',        // active nav key
    'page_title' => 'Radar',
]);
```

Ported from the agentic affiliate engine, adapted: `App_Controller` (auth gate, `$this->user_id`,
`$this->data['ic_brand']`, `require_access()`), `Access_control_lib` (JVZoo access codes CSV in
`users.access`), `App_lib`, `Ion_auth`, `Datamodel`.

## 3. `engine/application/modules/` — HMVC

Modules are auto-discovered by folder presence (`$config['modules_locations']`); there is no registry
to edit. Every controller `extends App_Controller`. Cross-module loading is prefixed:
`$this->load->model('openchair/oc_business_model')`.

### 3.1 Common platform modules (ported)

| Module | Role | Phase |
|---|---|---|
| `auth/` | Ion Auth — login, logout, password reset | 2 |
| `users/` | Sub-users, `parent_user_id`, `/myprofile` | 2 |
| `setting/` | Per-user rows in `settings` keyed by `meta_type`, encrypted BYOK | 2 |
| `whitelabel/` | `settings` row `meta_type='whitelabel'`, serialized brand → resolved by `App_Controller::_build_ic_brand()` into `$ic_brand` for every request | 2 |
| `sites/` | `Datamodel` (autoloaded) + `Custom_domain_model` + `Cname` | 2 / 7 |
| `config/` | Superadmin API config | 2 |
| `telegram/` | Optional alert channel — to the **consultant**, never the business (FR-6.4) | 6 |

### 3.2 Deliberately NOT ported

`sequence/`, `twilio_call/`, `twilio_sms/`, `bridgebot/`, `leads/`, `moderation/`.

This is a product-integrity decision, not an omission. [05-prd §8 rule 7](docs/05-prd.md) requires
that there be **no code path from Pitch into `sequence_queue` or telephony** and no Send button. Not
porting those modules makes that structural rather than a policy anyone can regress.

### 3.3 `modules/openchair/` — the product module

| Path | Planned contents | Spec | Phase |
|---|---|---|---|
| `controllers/Openchair.php` | Every authenticated screen; sets `$page` per the nav contract | [40-ux §2.1](docs/40-ux-spec.md) | 2 |
| `controllers/Oc_internal.php` | Node → CI callbacks; `X-Internal-Token` gated, CSRF-excluded | [20-arch §2.3](docs/20-architecture.md) | 3 |
| `controllers/Oc_report.php` | Public token-gated Ledger report, no login | FR-8.4 | 7 |
| `models/Oc_business_model.php` | **Global scan layer — no `user_id`, ever** | [30-data §3](docs/30-data-model.md) | 2 |
| `models/Oc_market_model.php` | Tenant layer: saved market (city × niche) | [30-data §3](docs/30-data-model.md) | 2 |
| `models/Oc_prospect_model.php` | Watchlist, stage, notes | FR-5.3 | 5 |
| `models/Oc_meter_model.php` | Reads `oc_vacancy_daily` / `_weekly` / `oc_deadzone_cell` | [35-scoring](docs/35-scoring-model.md) | 4 |
| `models/Oc_alert_model.php` | `oc_alert_run` / `_item` — the numbers **as sent** | FR-6.1 | 6 |
| `models/Oc_pitch_model.php` | Pitch packs + frozen evidence grid | FR-7.1 | 6 |
| `models/Oc_ledger_model.php` | 8-week rollup + attribution caveat | [35-scoring §6](docs/35-scoring-model.md) | 7 |
| `views/NAV-CONTRACT.md` | Nav keys in writing — the house convention | — | 2 |
| `views/pages/*.php` | 13 screens — see below | [40-ux §2.2](docs/40-ux-spec.md) | 2–7 |
| `helpers/oc_format_helper.php` | Money **ranges**, confidence bands, data age, wording lock | [05-prd §8](docs/05-prd.md) | 2 |
| `config/oc_config.php` | Service base URLs, tier gates, demo-dataset switch | — | 2 |
| `language/english/oc_lang.php` | All user-facing strings — makes the wording lock testable in one place | FR-4.6 | 2 |
| `tests/` | Wording-lock assertion; the no-path-to-telephony assertion | [05-prd §8](docs/05-prd.md) | 6 |

**Screens:** `firstrun` · `today` · `scan` · `scan_progress` · `radar` ★hero · `business` · `pitch` ·
`onepager` · `prospects` · `ledger` · `coverage` · `adapters` · `billing` · `how_it_works`

**Signature visuals** (docs/60): `libraries/Oc_visuals_lib.php` builds one card payload;
`views/visuals/_chair_week · _chair_map · _receipt · _same_day · _progress` render it, through the
shared `_grid` and `_card_foot`; `card_page.php` is the standalone page (`/openchair/card/{id}/{kind}`,
`?size=phone`, `?print=1`, `?format=json`). `models/Oc_share_model.php` + public
`controllers/Oc_share.php` serve frozen cards at `/c/{token}` (migration `oc_010_share_cards.sql`).
CSS: `assets/css/oc-visuals.css` + `assets/css/visuals/*.css`; export: `assets/js/oc-card-export.js`.

**Rail grouping** follows [ui/prototype.html](ui/prototype.html): **Prospect** (Today · Radar · Meter ·
Pitch · Prospects) · **Retain** (Ledger) · **Operate** (Coverage). `Prospects` is added to the
prototype's rail because it is a v1 FE screen in the UX spec that the prototype omits.

## 4. `engine/openchair-service/` — app box, :4100

House patterns inherited from `bridge-service`: ESM, Node ≥22, no build/lint/transpile step, JSDoc
not TypeScript, `node --test`, all config through `src/config.js` (never `process.env` elsewhere),
defensive dynamic route loading so a broken module 503s one surface instead of killing boot.

`src/` → `routes` · `models` · `discovery` · `scan` · `meter` · `ledger` · `pitch` · `security` ·
`jobs` · `lib` · `observability`

**Declared dependency: `mysql2` only.** No `playwright`, no HTTP client — that absence *is* the
isolation guarantee ([20-arch §1.6](docs/20-architecture.md): "if `playwright` is ever imported there,
the isolation is gone — make it a CI grep").

## 5. `engine/openchair-worker/` — the crawl fleet

`src/` → `adapters` (one file per platform) · `registry` (validator + shadow rollout) · `normalise`
(→ canonical chair-minutes grid) · `browser` (capped headless pool) · `lib`; plus `fixtures/` —
recorded pages for contract tests, which is what keeps adapter maintenance at 3 dev-days/month
instead of 10.

Launch adapters: Booksy · Vagaro · Fresha · GlossGenius · Square · Acuity · Momence.
**Refused by decision:** OpenTable and Resy (v1); Mindbody deferred to its paid partner API; tier-3
stealth / residential rotation is a hard ceiling, not a config flag
([50-risk A2.1](docs/50-risk-compliance-qa.md)).

> Deployment note: worker and egress run on a **separate cgroup-capped VM**, not the app box
> ([20-arch §1.6](docs/20-architecture.md)). They live under `engine/` for repo parity with
> `bridge-service`; the deploy target is different.

## 6. `engine/openchair-egress/` — the door

The whole read-only guarantee narrows to this one process.

`src/guard/` refuses **every non-GET/HEAD request** — spike criterion S7 is *zero* non-GET requests,
pass/fail rather than a threshold. `src/proxy/` holds all proxy credentials; nothing else on the box
has them. `src/audit/` writes rotated JSONL, one line per fetch (FR-3.4) — a table would be ~800k
rows/night (PRD Q8).

**Zero declared dependencies.** Node built-ins only.

---

## 7. What is on disk right now

Directories, CI3 `index.html` 403 guards, `.gitignore`, three `package.json` skeletons, three
`.env.example` files. Nothing else.

## 8. Next action needs your go

**Port the platform layer** from `instacloseai-nakul-launch/engine` into `engine/`: the CI3
framework (`system/`, `application/third_party/MX`), `core/` (`App_Controller`, `MY_*`), the shared
libraries, and the six common modules in §3.1. That is a bulk copy of your working code with real
choices inside it — which modules cross over, whether `config/` and its credentials come with them,
and whether `ic_shell` is copied then renamed to `oc_shell` or rebuilt from the prototype.

I have not touched `instacloseai-nakul-launch/` at all.

## 9. Still open

| # | Decision | Blocks |
|---|---|---|
| 1 | **Product name** — "OpenChair" collides with a live salon SaaS and a registered US mark ([10-market §10](docs/10-market-and-offer.md)). Internal slug `openchair` is cheap to keep; display name resolves through `$ic_brand`. | branding, not code |
| 2 | **Phase order** — spike first (~2 weeks before anything is visible), or module skeleton first so there is something to show? | what I build next |
| 3 | **Entitlement codes** — new JVZoo codes on OpenChair's own `users` table, or reuse the InstantClose code map? | §3.1 `Access_control_lib` |
| 4 | **FE gating** — ship the Slammed bucket in the FE, or hold it behind OTO1 as the brief has it? | nav gates, demo |
| 5 | **Shared DB or its own?** OpenChair now has its own `engine/`, so it can run its own schema. The `oc_` design assumes it links out to `users`/`clients` by loose INT — that works either way, but the answer decides whether an InstantClose customer is the same account. | migrations |

Phase definitions and gates: the plan in the conversation, derived from
[20-architecture §10.3](docs/20-architecture.md).
