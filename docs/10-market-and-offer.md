# OpenChair — Market & Offer

> Companion to [00-brief.md](00-brief.md). This is the document the sales page, the JV page, the
> demo video and the OTO ladder get built from. Everything numeric is tagged **[VERIFIED]** (with a
> source) or **[ASSUMPTION]** (my model — challenge it, don't cite it as fact).

---

## 0. The one thing to read if you read nothing else

The product idea is right. The qualifier — *unsold time* — is genuinely new in this market and it is
the first local-prospecting hook in years that is **emotionally pre-loaded**: the owner already
knows the number, they've just never seen it written down. That is a far better place to start a
conversation than "your website scores 34/100," which the owner does not believe and does not care
about.

Four things in the brief are wrong or under-cooked, and all four are fixable before a line of code:

1. **The name is not usable.** "OpenChair" is occupied by at least five live products in this exact
   vertical, one of which does approximately the thing you're describing, plus a registered US
   trademark. Rename. (§10)
2. **The FE is crippled in the wrong place.** Withholding the Slammed bucket kills the demo's punch
   line and hides the single most differentiated thing you have. Cripple on *volume*, not on *the
   idea*. (§6)
3. **COGS is modelled 10–25x too low** and the FE has no quota. At realistic anti-bot pricing an
   "unlimited-ish" FE goes underwater at roughly 90–150 scans/day per user. Ship credits from day
   one. (§7)
4. **There is no recurring revenue in the ladder.** OTO2 gives the *customer* recurring revenue and
   gives you a one-time $197. That's backwards. (§9)

---

## 1. Buyer avatar — who actually swipes a card for a $47 JVZoo local tool

Two segments buy this. They are not the same person, they don't respond to the same copy, and the
funnel has to serve both without confusing either.

### Segment A — "Dave," the aspiring / part-time local consultant (≈70% of FE units [ASSUMPTION])

**Who he is.** 38–58. Has a job, or had one. Bought his first IM product somewhere between 2015 and
2022 and never fully stopped. Lives on a Gmail address he's had since 2009 that receives forty
launch emails a day. Has a Facebook-group habit. His "agency" is a Wix site with a stock photo of a
handshake and a name like *Apex Local Media*.

**What he already owns.** This matters more than anything else in this document — he is not an
un-marketed buyer, he is an *over*-marketed one:

- Two or three website-audit / report generators. Some flavour of "scan any local site, generate a
  branded PDF, email it to the owner." One of them still works.
- At least one Google Maps / GBP scraper that spits out CSVs of businesses with no website.
- A cold-email or "DFY agency in a box" kit — swipe files, a contract, a proposal template.
- A "local video" product (spokesperson video, review video, slideshow-from-photos).
- Canva Pro. Maybe a $17/mo scheduler. Almost certainly not a real CRM.
- **Lifetime spend: $800–$3,000. Lifetime earned: $0–$1,500.** [ASSUMPTION]

**Why he stopped using all of it.** Not because the software broke. Because of the *last mile*.
Every one of those tools ends in the same place: a list, or a PDF. Then he has to go send a cold
message that begins with some version of *"Hi, I noticed your website could use some improvements."*
He has sent maybe forty of those. He got two replies, both hostile. The tool didn't fail — the
**opening line** failed, and he correctly concluded he had nothing worth saying. The CSV is still on
his desktop.

**What makes him buy again.** He is not buying software. He is buying **a sentence he isn't
embarrassed to say.** Every time he opens a launch email he is scanning for one thing: *is there a
reason for this business owner to talk to me that isn't "I want to sell you something"?* That is why
the identity-shift hook in the brief is the strongest asset in the whole product — it *is* the
product. The software is the excuse to own the sentence.

And, honestly: **he buys because it's $47 and it's Tuesday.** At this price the purchase is closer
to a lottery ticket than a capex decision. He needs (a) a mechanism he has never seen before, (b) a
demo where a real dollar number appears on a real screen, (c) a believable path to *one* client.

**What kills the sale for him.** "You'll need to do outreach at scale." Anything that smells like
work he has already failed at. Anything requiring an API key, a proxy, a Zapier account, or a
monthly bill stacked on top of the $47.

**What he refunds over.** He runs one scan on his own town, gets 6 results instead of 47, and feels
lied to. **Coverage in thin markets is refund driver #1.** (§7, §8)

### Segment B — "Marisa," the working agency owner (≈30% of FE units, ≈60% of ladder revenue) [ASSUMPTION]

**Who she is.** 32–50. Actually has 4–11 local clients paying $800–$3,500/mo — med spas, a dental
practice, two HVAC companies, a gym. Runs Google Ads and GBP management. Has a VA, maybe a
part-time media buyer. Revenue $12k–$40k/mo. She is not on Muncheye for entertainment; she buys at
$47 to test-drive a tool before deciding whether it earns a place in her stack.

**What she already owns.** BrightLocal or Whitespark. Apollo or Instantly plus a domain-warming
setup. A GoHighLevel sub-account per client. Loom. A real CRM. Possibly a $299/mo call-tracking
bill.

**Why she stopped using the last one.** It didn't survive contact with her *process*. It produced
output she couldn't white-label, couldn't put in front of a client, and couldn't hand to a VA with
a one-page SOP.

**What makes her buy.** Two things, and only two:

1. **A new opening for outbound her competitors don't have.** Her churn problem is upstream: she
   must replace one or two clients a quarter and her outbound sounds exactly like everyone else's.
2. **A retention artifact.** She is quietly desperate for a monthly deliverable that proves she is
   working. Her clients ask "what am I actually paying for?" every ninety days. The **Ledger** — a
   monthly report saying *your online fill rate went 41% → 68% since we started* — is worth more to
   her than the prospecting. She will pay $197 for that without blinking, and $97/mo forever.

**The strategic consequence:** Dave pays for the launch. Marisa pays for the business. The sales
page is written to Dave; the OTOs are engineered for Marisa. Do not write the OTO pages in Dave's
voice.

### The third buyer nobody plans for — and should

**The affiliate who wants a lead magnet.** People on your JV list will buy this to run scans in
their own city and use the resulting reports as free-value bait for their own list building. Give
them a "share this report, branded as me" mode and you get organic distribution for free. (Feeds
§9.)

---

## 2. Positioning + category

### The category

**Live Capacity Prospecting** — finding local businesses by what their calendar is doing *right
now*, not by what their website looks like.

Is that a real category or a positioning trick? Real, for one specific and defensible reason: **it
inverts the qualifier from a supply-side judgment to a demand-side fact.**

Every existing local-prospecting tool qualifies on **what the business built** — their site, their
listing, their reviews, their schema markup. Those are all *artifacts the owner made*, which means
criticising them is criticising the owner. That's why the reply is defensive, when there is a reply.

This qualifies on **what the market did to them** — how many hours nobody bought. The owner isn't
being told his work is bad. He's being shown a number he already suspects. The emotional register is
completely different: **audit tools produce shame; capacity data produces recognition.** Shame makes
people close the email. Recognition makes them ask "how did you know that?"

That is the actual innovation. Not the crawling.

### Positioning statement (use verbatim)

> **For local consultants who are sick of opening with "I noticed your website" — [PRODUCT] reads
> the public booking calendars of every salon, clinic, gym and restaurant in a city and tells you,
> in dollars, how much of this week they failed to sell.**

Shorter, for the top of the sales page:

> **Stop scoring their website. Start reading their calendar.**

### The enemy

**Website graders and audit-report generators.** Name the enemy explicitly and early. The attack is
*not* "they're bad tools" — Dave owns three and doesn't want to be told he was stupid. The attack
is: **they all score the same thing, so they all say the same thing, so every consultant in America
opens with the same sentence.**

The argument, in the order it should appear on the page:

1. There are only so many things a website grader *can* measure — speed, mobile, SSL, meta tags,
   schema, an NAP citation check. Every tool measures the same six. So every report says the same
   six things.
2. Which means the business owner has already received your email. Four times. This year.
3. And the score is about something he doesn't believe matters. He knows his site is ugly. He's
   known for six years. It didn't stop him being slammed in March.
4. Meanwhile the thing he *actually* lies awake about — Tuesday afternoon, nobody in the chair — is
   invisible to every tool on the market, because it isn't on his website. **It's on his booking
   page, and nobody thought to look.**

### Proof of difference — the three tests

A positioning claim is only real if it survives these:

| Test | Website grader | [PRODUCT] |
|---|---|---|
| **Can the owner dispute the number?** | Yes, instantly — "my site's fine, I'm busy." | Not really. It's *his own booking page*, screenshotted. He can dispute the interpretation — and disputing it starts a conversation about his calendar, which **is** the sale. |
| **Does it change week to week?** | No. A site scores 34 in January and 34 in June. One-shot pitch, no reason to follow up. | Yes. Vacancy moves constantly. That's a legitimate **reason to make contact again in three weeks** — and it's the entire retention product (Ledger). |
| **Can a competitor copy the qualifier in a weekend?** | Yes. PageSpeed Insights API is free and public. | No. Six platform adapters against live anti-bot defences is a genuine moat that *deepens* over time. It's also why it's expensive (§7). |

Row two is the underrated one. **Audit scores are static; capacity is a time series.** A static
number is a pitch. A time series is a subscription. That difference is the whole business, and it's
the reason this can be a company rather than a launch.

### The one-liner that does the most work

> **"Two pitches, one scan."** Same data, opposite problems. The empty ones need customers. The full
> ones need capacity — and nobody is selling to the full ones, because nobody else knows they're
> full.

### Supporting market facts worth putting on the page

- Median US salon chair utilisation is **47–49%**; top performers run **76–79%**. Barbershop median
  utilisation is about **56%**. [VERIFIED — [Zenoti salon revenue-management guide](https://www.zenoti.com/thecheckin/salon-revenue-management-guide), [Zenoti salon/barbershop metrics](https://www.zenoti.com/thecheckin/salon-and-barbershop-metrics-guide)]
  **This is the single best stat on the sales page.** It means the median target business is selling
  roughly half its available hours — and the Hungry bucket is not a niche, it's the majority of the
  market.
- Roughly **78% of salons worldwide had adopted online booking as of 2024**, and ~61% of beauty
  appointments were booked online in 2025. [VERIFIED — secondary aggregators (Market Growth Reports
  / DataIntelo via [Guideflow](https://www.guideflow.com/blog/salon-booking-software)); treat as
  directionally right, not precise] This defuses the "coverage" objection: online booking is now the
  norm in beauty, not the exception.
- Booksy reports **140,000+ businesses** and 260M appointments in 2024; Vagaro reports **100,000+
  businesses**; Mindbody claims **40,000+** (third-party estimates range 18k–58k) and roughly
  **25–28% share** of the studio-software market. [VERIFIED — [Booksy/Vagaro comparisons](https://trafft.com/booksy-vs-vagaro/),
  [Mindbody](https://www.mindbodyonline.com/), [market-share estimate](https://www.marketreportanalytics.com/reports/fitness-center-studio-software-56357)]
  Note these are **global** counts and vendor-reported — do not quote them as US TAM.
- OpenTable holds roughly **46% US share** of restaurant reservations (down from 51%); Resy is
  strong in dense metros; DoorDash bought SevenRooms in 2025 and Toast Tables has ~5%. [VERIFIED —
  [analytics.restaurant](https://www.analytics.restaurant/our-data-in-the-work/the-us-restaurant-reservation-market-heats-up),
  [CNBC](https://www.cnbc.com/2026/02/25/doordash-resy-opentable-restaurant-reservation-wars.html)]
  **Implication: restaurants are the most fragmented and fastest-changing vertical. Do not ship it
  at launch.** (It's already correctly placed on the $997 backend.)
- IBISWorld counts **1.06M hair salons** and **154,925 barbershops** in the US (2025). [VERIFIED —
  [IBISWorld](https://www.ibisworld.com/united-states/number-of-businesses/hair-salons/4410/)]
  ⚠️ **Do not use the 1M+ figure on the sales page.** That count is dominated by non-employer
  sole-proprietor booth renters. The addressable universe — multi-staff shops with a public booking
  page — is far smaller, and quoting an inflated TAM is exactly the kind of claim an affiliate will
  fact-check on the JV call.

---

%%SECTION3PLACEHOLDER%%

## 4. The two-bucket doubling — Hungry vs Slammed

This is the highest-leverage section in the product and the one most likely to be under-built. The
scripts below are the actual deliverable customers are buying. They ship inside **Pitch** as
editable templates with merge fields, and they get printed in the sales page as proof.

### Why two buckets doubles the market (the real argument)

Every prospecting tool in this space has an implicit filter: *find the ones who are failing.* That
throws away the better half of the list. The businesses that are **slammed** are:

- **Solvent.** They can pay you this month. The empty ones often genuinely cannot.
- **In pain.** A two-week wait means they are turning away money every single day, and they *feel*
  it — turn-aways are the most visceral loss in a service business.
- **Completely un-pitched.** Nobody cold-calls the busy salon. There is no competition in that inbox
  because every other consultant's tool told them to skip it.

The Slammed pitch also carries an enormous psychological advantage: **it opens with a compliment
that is verifiably true and specific.** "You're booked out nineteen days" is flattery that can't be
read as flattery, because it's a measurement.

### Merge fields used in every script

`{first_name}` · `{business}` · `{open_slots}` · `{bookable_slots}` · `{vacancy_pct}` ·
`{dollar_figure}` · `{dead_zone}` (e.g. "Tuesday and Wednesday 1–4pm") · `{next_available_days}` ·
`{platform}` (Booksy/Vagaro/…) · `{avg_ticket}` · `{city}` · `{my_name}` · `{staff_count}`

---

### 4A. HUNGRY — phone openers

The rule for all four: **the number comes out of your mouth inside the first eight seconds, and you
never say the word "marketing."**

**H-Phone-1 — "The straight read" (default; highest reply rate in cold local calling)**

> "Hi, is this {first_name}? — Great. Look, this is a cold call, you can hang up on me. Ten seconds?
> … I was looking at your booking page this morning and you've got {open_slots} open appointments
> between now and next Friday. Most of them Tuesday and Wednesday afternoons. Is that normal for
> this time of year, or is that a slow patch?"
>
> *[Let them answer. They will answer — nobody has ever asked them this.]*
>
> "Right. So at your prices that's about {dollar_figure} of chair time that just doesn't get sold
> this week. I fill those specific hours for {city} shops. Can I show you the two things I'd do for
> Tuesdays? Takes six minutes."

*Why it works:* the permission-to-hang-up opener disarms; the number proves you did work; the
question is genuinely open and the owner has an opinion about it. You've had a conversation before
you've pitched.

**H-Phone-2 — "The wrong-guess" (for the ones who are defensive)**

> "Hi {first_name} — quick one. I run the numbers on booking calendars for {city}. Yours came up
> with {vacancy_pct}% of next week still open, which usually means one of two things: either you're
> deliberately holding those slots back, or Tuesday afternoons have gone quiet. Which is it?"
>
> *[If "we're fine, we take walk-ins":]* "Fair enough — so those {open_slots} are getting filled off
> the street, they're just not getting filled *online*. That's the bit I'd fix. Online-bookable
> hours are the only ones you can pre-sell."

*Why it works:* deliberately offers them a face-saving out. Both answers lead to the same pitch, and
the walk-in objection — which is the #1 true objection (§8, Risk 1) — is pre-handled inside the
script.

**H-Phone-3 — "The dead zone" (most consultative; best for higher-ticket verticals: med spa, dental, chiro)**

> "{first_name}, I'm going to tell you something about your own business and you tell me if I'm
> wrong. Over the last {n} days, your Tuesday and Wednesday afternoons have been open basically
> every week — but your Fridays and Saturdays are gone within a day or two of opening up. Right?…
> That pattern costs you about {dollar_figure} a week. It's not a demand problem, it's a *shape*
> problem, and it's fixable without discounting. Fifteen minutes on Thursday?"

*Why it works:* pattern-over-time beats snapshot. Requires Ledger/8-week history, which is exactly
why history should not be buried in a $197 OTO (§6).

**H-Phone-4 — "The referral-shaped open" (for the ones who screen calls; leave as voicemail)**

> "{first_name}, it's {my_name}. I don't know you — I work with {city} shops on filling midweek. The
> reason I'm calling: I pulled your online availability and you've got {open_slots} unsold hours in
> the next seven days, {dollar_figure} worth. I've got a one-page breakdown showing exactly which
> hours. I'll text it to you rather than chase you — if it's useful, call me back on this number.
> Either way you can keep the sheet."

*Why it works:* it's the only voicemail with a specific number in it, and it ends with a gift, not
an ask. Send the one-pager by SMS within sixty seconds of hanging up.

---

### 4B. HUNGRY — walk-in openers

Walk-ins are how Dave actually gets his first client, because he can't sell on the phone yet. The
tool must produce a **printable one-pager** for this — a phone screen doesn't work standing at a
counter.

**H-Walk-1 — "The paper drop" (default)**

Hand the one-pager over the counter, printed, their logo on it, the availability grid visible.

> "Hi — are you the owner? … Is {first_name} in? No worries. I'll leave this for them. It's a
> one-page look at their online booking for next week — I do this for shops in {city}. There's a
> number circled on it, that's roughly what next week's unsold hours are worth at their prices. My
> number's at the bottom. That's it — I'm not going to stand here and pitch you."
>
> **Then leave.** Do not linger. Do not ask for anything.

*Why it works:* the receptionist will read it, because it has their own booking grid on it, and will
absolutely show the owner. The scarcity of *not pitching* is the entire move.

**H-Walk-2 — "The Tuesday question" (when the owner is actually there and it's quiet)**

> "Are you {first_name}? — I'll be ninety seconds. I looked at your Booksy this morning; you've got
> {open_slots} slots open through next week and almost all of them are Tuesday–Wednesday afternoon.
> I fill afternoons for shops like this. Here's the sheet. Two questions and I'll go: what's a
> {service} worth to you, and would you *want* Tuesday afternoons full, or do you like them quiet?"

*Why it works:* the second question is real. Some owners genuinely want the afternoon off — and if
they say so, they've just disqualified themselves in ten seconds and you've saved a week. The ones
who say "of course I want them full" have opened the door themselves.

**H-Walk-3 — "The competitor sheet" (aggressive; use in dense markets)**

Bring a second page showing the anonymised vacancy spread of five nearby shops in the same niche.

> "Two things. First page is yours — {vacancy_pct}% of next week unsold. Second page is five other
> {niche} in a mile radius, names off. You're the third-emptiest. The top one on that list is at
> 12%. I know what they're doing on Tuesdays. Ten minutes this week?"

*Why it works:* competitive envy is the strongest motivator in local business. ⚠️ **Compliance
note:** anonymise the competitors — never print another business's name on a sheet you hand to a
rival. Ship this template with the anonymisation enforced by the product, not left to the user.

**H-Walk-4 — "The soft-entry" (for the timid buyer — put this one in the training, it's Dave's on-ramp)**

> "Hi — can I ask you something odd? Do you guys ever actually get walk-ins on a Tuesday afternoon?
> … Reason I ask, I look at booking calendars around {city} and yours has {open_slots} open midweek.
> I'm not selling you anything today, I genuinely just want to know if that matches what it feels
> like in here. Does it?"

*Why it works:* zero pressure and it's a real question. Dave can say this without his voice shaking,
which is the actual constraint. Half the time the owner talks for five minutes and asks what he
does.

---

### 4C. HUNGRY — email

Short. Plain text. No images, no logo, no signature block with sixteen social icons. The whole email
should look like it was typed by a person on a phone.

**H-Email-1 — "38 slots" (default; subject line is the whole game)**

> **Subject:** 38 open slots next week
>
> {first_name} —
>
> I pulled your online booking for the next 7 days. {open_slots} of your {bookable_slots} bookable
> appointments are still open. At your listed prices that's about **{dollar_figure}**.
>
> Almost all of it is {dead_zone}.
>
> I fill exactly those hours for {niche} in {city}. If that number is wrong, tell me and I'll shut
> up. If it's roughly right, I'll show you the two offers that work on midweek afternoons.
>
> — {my_name}
> {phone}

**H-Email-2 — "The screenshot" (highest reply rate; one image only)**

> **Subject:** your Tuesdays
>
> {first_name} — this is your own booking page, this morning:
>
> *[screenshot of the availability grid, open slots highlighted]*
>
> {open_slots} open. {dollar_figure} at your prices. Same shape every week for the last {n} weeks.
>
> Want me to fill them?
>
> — {my_name}

*Why it works:* the screenshot of their own grid is unfakeable and takes zero cognitive effort to
verify. This is the single strongest asset the product generates — **make the screenshot beautiful,
it's the whole email.**

**H-Email-3 — "The no-pitch value email" (for lists; use as a sequence step 1)**

> **Subject:** something I noticed about {business}
>
> Not a sales email — genuinely just noticed this and thought you'd want it.
>
> Your online calendar for the next week: {vacancy_pct}% open, concentrated {dead_zone}. Your
> weekends are gone almost immediately. That gap between weekend demand and midweek demand is the
> single most common shape in {niche}, and it's usually worth about {dollar_figure}/week.
>
> Three things fix it, in order of effort: a standing midweek offer, a waitlist that texts, and
> Google offer posts timed to Sunday night. Happy to explain any of them, no charge, no catch —
> reply "1", "2" or "3".
>
> — {my_name}

*Why it works:* the reply mechanism is one keystroke. This is the one to use for volume.

**H-Email-4 — "The follow-up that only this product can send" (day 21)**

> **Subject:** re: your Tuesdays — it got worse
>
> {first_name}, three weeks ago I sent you a note saying you had {open_slots_then} open slots.
>
> I re-ran it this morning: **{open_slots_now}**. Vacancy went {vacancy_then}% → {vacancy_now}%.
>
> That's {dollar_delta} more unsold time per week than when we last spoke. I'm not trying to be
> clever — it's just that this is the number I watch, and yours is going the wrong way.
>
> Fifteen minutes?

*Why it works:* **no competing tool can send this email.** A website grader has nothing new to say
in week three. This is the time-series advantage cashed out as revenue, and it's the strongest
single argument for putting history in the FE rather than an OTO (§6).

---

### 4D. HUNGRY — DM (Instagram / Facebook — where salons actually live)

Under 300 characters. No links in the first message (kills reach and looks like spam).

**H-DM-1**
> hey — do you take bookings for Tuesday afternoons? asking for a slightly weird reason 😅

*[When they reply "yes, we've got loads":]*
> that's actually why I messaged. I look at booking calendars for {city} shops — you've got
> {open_slots} open midweek, roughly {dollar_figure} of chair time. I fill those. want me to send
> the breakdown?

*Why it works:* the first message is a **genuine customer-shaped question**, so it lands in the
primary inbox and gets answered by whoever runs the account. The reveal is honest and immediate.

**H-DM-2**
> {first_name} — quick one, not spam: {open_slots} of your bookable slots next week are still open
> (mostly {dead_zone}). that's about {dollar_figure}. I fill midweek for {niche} in {city}. want the
> one-pager or shall I leave you alone?

*Why it works:* "or shall I leave you alone" gets an answer roughly every time. Give them a
comfortable no and they'll often give you a yes.

**H-DM-3 — comment-then-DM**
> *[Comment on a recent post, genuine and specific — reference the actual work in the photo.]*
> *[24h later, DM:]* commented on your fade post yesterday — that's proper work. Which is why the
> midweek thing bugged me: you've got {open_slots} open Tue/Wed. Somebody doing that quality
> shouldn't have empty afternoons. I fix that specific problem. Interested?

*Why it works:* the compliment is pre-paid and verifiable. "Somebody doing that quality shouldn't
have empty afternoons" is the best single sentence in the Hungry arsenal.

**H-DM-4 — the LossReel handoff (MotionStack cross-sell)**
> made you a 30-second thing — it's your booking calendar for next week with the open slots counted
> up. {dollar_figure} of unsold time. no pitch in it, just the number. want it?

---

### 4E. SLAMMED — phone openers

**Completely different energy.** You are not solving a crisis, you are pointing at a leak in a
success. Never sound worried. Never discount. The frame is: *you are winning, and winning badly is
expensive.*

**S-Phone-1 — "The turn-away math" (default)**

> "{first_name}? — I'll be quick. Your next available appointment online is **{next_available_days}
> days out**. That's a good problem and it's also an expensive one. Every person who checks your
> booking page today and sees 'nineteen days', most of them don't wait — they go to the shop down
> the road. You never see them, so you never count them. I turn that leak into a waitlist. Have you
> got ten minutes this week?"

*Why it works:* it names an invisible loss. The owner has never been able to measure walk-aways, and
the moment you say it out loud they know it's true.

**S-Phone-2 — "The price conversation"**

> "You're booked nineteen days out. When did you last raise your prices? … Right. Here's the thing —
> a nineteen-day wait *is* the market telling you you're underpriced, and you're the only one who
> hasn't been told. I can show you exactly what a {x}% increase does to your book, and how to do it
> without losing the regulars. That conversation's free."

*Why it works:* it's a business conversation, not a marketing one, which immediately puts you in a
different category from every other caller. Enormously flattering. Works exceptionally well on
owner-operators.

**S-Phone-3 — "The second chair"**

> "{first_name}, your online book is at {booked_pct}% and next available is {next_available_days}
> days. That means you've hit the ceiling of what {staff_count} chairs can do. There are only three
> ways past that: raise prices, add a chair, or stop wasting the ones you have on no-shows. I do all
> three, and I'd start with the third because it's free. Are no-shows costing you much?"

*Why it works:* offers three doors and starts with the one that costs nothing. "Are no-shows costing
you much" is a guaranteed yes and a guaranteed rant.

**S-Phone-4 — "The deposit gate" (best for high-ticket: med spa, tattoo, dental)**

> "You're running at {booked_pct}% online with a {next_available_days}-day wait. So a no-show
> doesn't cost you one appointment — it costs you the one you turned away to hold it. Do you take
> deposits? … That's what I'd fix first. It takes an afternoon and it pays for me in a month."

---

### 4F. SLAMMED — walk-in openers

**S-Walk-1 — "The compliment with a number"**

> "Are you {first_name}? I'm not going to take your time, you're clearly busy — that's actually why
> I'm here. Your next online booking is {next_available_days} days out. You're the busiest {niche}
> in this postcode by that measure. *[hand the sheet]* This is what that's costing you. Not what
> you're making — what you're **turning away**. Have a look when it's quiet. Number's at the
> bottom."

*Why it works:* "you're the busiest in this postcode" is the highest-status thing anyone will say to
that owner all month, and you can prove it. Then you flip to loss. Then you leave.

**S-Walk-2 — "The waiting-room read" (do this while there are people waiting)**

> "How long's the wait to get in here normally? … Right, and how many people ring up, hear that, and
> just say 'no worries' and never call back? … Nobody knows that number. It's the biggest number in
> your business and it's the only one you can't see. I make it visible and then I capture it."

**S-Walk-3 — "The referral trade" (soft; builds a relationship instead of a sale)**

> "You're booked out {next_available_days} days — so you don't need customers, and I'm not going to
> pretend you do. Two things instead. One: I've got a sheet here showing what your wait time is
> probably costing in walk-aways. Two: when you turn people away, where do you send them? Because I
> work with a couple of shops nearby and I'd rather send you *their* overflow than sell you
> anything."

*Why it works:* Marisa's move, not Dave's. Turns the Slammed list into a referral network. Ship this
in the training — it's how one scan turns into five relationships.

---

### 4G. SLAMMED — email

**S-Email-1 — "19 days" (default)**

> **Subject:** you're booked out 19 days
>
> {first_name} — your next available online appointment is {next_available_days} days away, and your
> book is {booked_pct}% full for the next two weeks.
>
> Congratulations, genuinely. Now the annoying part: at that wait time, most people who look you up
> don't book. They just go somewhere else, and you never find out.
>
> Conservatively that's {walkaway_estimate} people a week.
>
> Three fixes, none of them "get more customers": a waitlist that auto-texts on cancellations, a
> deposit gate to kill no-shows, and a price review — because a 19-day wait is the market telling
> you something.
>
> Worth fifteen minutes?
>
> — {my_name}

**S-Email-2 — "The wrong problem"**

> **Subject:** not a lead-gen email
>
> {first_name} — I'm not writing to get you customers. You've got a {next_available_days}-day
> waiting list; more customers is the last thing you need.
>
> I'm writing because being this booked is a *capacity* problem, and capacity problems get solved
> with pricing, waitlists and deposits — not ads. Nobody sells that to you because everyone selling
> to local businesses only knows how to sell traffic.
>
> I've attached what your wait time looks like over the last {n} weeks. It's getting longer.
>
> — {my_name}

*Why it works:* "I'm not writing to get you customers" is pattern-interrupt in the truest sense —
it's the opposite of every email they've ever deleted.

**S-Email-3 — "The no-show line"**

> **Subject:** what a no-show actually costs you now
>
> When you're 60% booked, a no-show costs you one appointment.
>
> When you're {booked_pct}% booked with a {next_available_days}-day wait — like you are — a no-show
> costs you the appointment *and* the person you turned away to hold that slot. It's double.
>
> Do you take deposits? If not, that's the highest-ROI afternoon of work available to you right now,
> and I'll set it up.
>
> — {my_name}

**S-Email-4 — "The trend" (day 21 / Ledger-powered)**

> **Subject:** your wait time went from 12 days to 19
>
> {first_name} — three weeks ago your next available was {days_then}. Today it's {days_now}.
>
> That's not growth you can bank; that's a queue getting longer. Queues don't wait — they leave.
>
> Fifteen minutes and I'll show you what to do about it before it's 25.

---

### 4H. SLAMMED — DM

**S-DM-1**
> are you taking new clients? 👀

*[They reply with the wait:]*
> ha — I knew, I checked. {next_available_days} days. that's actually why I messaged: I work with
> {city} shops on what to do when you're *too* booked (waitlists, deposits, pricing). nobody sells
> that. want the breakdown of what your wait time is costing?

**S-DM-2**
> {first_name} — you're the most booked-out {niche} within 3 miles ({next_available_days} days).
> genuinely impressive. also genuinely leaking money — every person who sees that and doesn't book.
> I fix that specific thing. worth a chat?

**S-DM-3**
> not looking to get you more customers, you clearly don't need them. do you have a waitlist that
> texts people when someone cancels? if no, that's free money and it takes an afternoon. happy to
> show you either way

*Why it works:* leads with a specific, small, free-feeling fix rather than an engagement. High
reply rate, low commitment, and it opens the retainer conversation naturally.

---

### 4I. Script deployment notes for the build

- Every script ships as an **editable template with the merge fields pre-wired**, not as a PDF of
  swipe copy. The difference between "swipe file" and "one-tap generated pitch" is the difference
  between a $47 product and a $197 one.
- **Bucket detection must auto-select the script set.** The user should never have to decide whether
  a business is Hungry or Slammed; the tool decides and shows the right four scripts.
- Ship a **"soften" toggle** — Dave needs a lower-confrontation variant of every script (H-Walk-4 is
  the model). Marisa needs the direct ones.
- The **screenshot is the product.** Budget real design time on the availability-grid render: their
  logo, their platform's look, open slots highlighted, the dollar figure large. If that one image
  isn't beautiful, none of the emails work.

---

## 5. Messaging hierarchy

### Headline

**Primary (use this):**
> # Walk in with their empty calendar. Not a cold pitch.

The brief's own line is the best line. It's concrete, it contains the mechanism, it implies the
identity shift, and it's the only headline in the market that isn't about a website.

**Sub-head:**
> [PRODUCT] reads the public booking calendars of every salon, clinic, gym and studio in your city —
> and hands you a list of who has unsold hours this week, how many, and exactly what they're worth
> in dollars.

**Alternates to split-test:**

- *"Their website is fine. Their Tuesdays are empty. Guess which one they'll talk to you about."* —
  best pure-differentiation angle; leads with the enemy.
- *"The median salon sells less than half its chair time. Here's the list for your city."* — best for
  the analytical buyer (Marisa); anchors on the [VERIFIED] 47–49% utilisation stat.
- *"I know your Tuesdays are empty." Say that, and the conversation starts.* — best identity-shift
  angle; strongest for Dave.
- *"Every local tool scores their website. This one counts their empty chairs."* — cleanest one-line
  category statement; good for ads and the JV page.

### Five bullet benefits (in priority order — the order is the argument)

1. **Open with a number they can't argue with.** Not a score you made up — their own booking page,
   screenshotted, with the open slots counted and priced from their own menu. *"You've got 38 open
   slots this week, about $1,860."*
2. **Two lists from one scan, so nobody in the city is a waste of time.** 🔴 Empty shops need
   customers. 🟢 Booked-solid shops need capacity — waitlists, deposits, a price rise — and nobody on
   earth is pitching them, because nobody else can tell they're full.
3. **A pitch pack in one tap.** The grid screenshot, the dollar figure, the dead-zone breakdown, and
   a matched offer — as a printable one-pager, an email, and a DM. Walk in, hand it over, leave.
4. **A reason to follow up in three weeks.** Vacancy moves. A website score doesn't. *"Three weeks
   ago you had 38 open. Today it's 44."* is an email no other tool on the market can send.
5. **The prospecting report and the client report are the same screen.** The moment they sign, the
   scan becomes the monthly proof — *fill rate 41% → 68%* — which is how you keep a retainer for two
   years instead of four months.

### The identity-shift section (put this immediately under the fold — it's the emotional core)

Write it as a two-column before/after, then the paragraph. Don't over-design it. Headline it:

> ## The reason you stopped doing outreach

**Before**
> "Hi, I help local businesses with marketing."
>
> Click. Or worse — *"we're not interested"* before you finish the sentence. And you don't blame
> them, because you've been on the other end of that call. You know you sound like the four people
> who rang before you. So you sent forty emails, got two replies, and quietly stopped.
>
> It was never a confidence problem. **You just didn't have anything to say.**

**After**
> "I noticed you had 38 open slots this week — mostly Tuesday afternoons. I can fill those. Here's
> how."
>
> Now watch what happens. They don't say "not interested." They say **"how do you know that?"**
>
> And the conversation you're now having is not a sales conversation. It's a conversation about
> their business, with someone who has clearly already looked. You've skipped the entire part you
> were bad at.

> **You stop being a marketer. You become the person who knows their calendar.**
>
> That's a different job. It's paid better, it's argued with less, and nobody else in your city is
> doing it — because until now, nobody could see the calendar.

### The demo narration script — under 60 seconds, beat by beat

This is the most important asset in the launch. It goes at the top of the sales page, on the JV
page, and in email 2. **Record it in one take with real data on a real city.** No slides. No stock
music under the voice. Screen + voice only.

| Time | On screen | Voiceover |
|---|---|---|
| **0:00–0:04** | Empty search box. Cursor blinks. Type `barbershop` then `Tampa`. Hit enter. | "Barbershops. Tampa." |
| **0:04–0:09** | Map. 47 pins drop in a fast cascade, colouring red → amber → green as they land. Counter ticks to 47. | "Forty-seven shops with public online booking. Coloured by how much of next week they haven't sold." |
| **0:09–0:14** | Cursor moves to the reddest pin. Click. Panel slides in. | "Red is empty. Let's take the emptiest one." |
| **0:14–0:22** | **Their real Booksy grid renders.** 7-day columns. Open slots light up one by one. Counter climbs to 38. Then the dollar figure lands with weight: **$1,860**. | "That's their actual booking page. Thirty-eight open appointments between now and next Friday. At their own listed prices — eighteen hundred and sixty dollars of chair time that nobody is going to buy." |
| **0:22–0:27** | Dead-Zone Map: a small heat grid, Tue/Wed 1–4pm glowing dark red. | "And it's not random. It's Tuesday and Wednesday afternoons. Every single week." |
| **0:27–0:34** | Click **Pitch**. One-pager renders in ~1s: their logo, the grid screenshot, $1,860 circled, the matched offer. | "One tap. There's the one-pager — their grid, their number, and the offer that fixes Tuesdays. Print it, walk in, hand it over." |
| **0:34–0:38** | **Pause. Beat.** Cursor slides back to the map. Deliberate. | "Now. Here's the part nobody else does." |
| **0:38–0:45** | Click a **green** pin. Panel slides. Grid is almost entirely solid. Big readout: **NEXT AVAILABLE: 19 DAYS**. | "This one's the opposite. Ninety-six percent booked. Next free appointment — nineteen days out." |
| **0:45–0:52** | Pitch flips automatically. Header changes colour to green. New one-pager: waitlist, deposits, price review. | "Same button. Completely different pitch. This one doesn't need customers — they need a waitlist, deposits, and probably a price rise. Nobody is selling them that, because nobody knows they're full." |
| **0:52–0:58** | Split screen: both one-pagers side by side. Zoom out to the full 47-pin map. | "Two pitches. One scan. Forty-seven businesses in the time it took to watch this." |
| **0:58–1:00** | Logo + URL. | "[PRODUCT]. Stop scoring their website." |

**Direction notes that make or break it:**
- **The 0:34 pause is the most valuable second in the video.** Do not fill it. That's where the
  viewer realises there's a second half to the product.
- The counter animating 0 → 38 and the dollar figure landing hard at 0:22 is what people screenshot
  and post in Facebook groups. Over-invest in that animation.
- **Use a real city with real businesses, blurred names.** If it looks like mock data the whole
  launch dies, and affiliates will notice first.
- Do not narrate the interface ("now I click here"). Narrate the *conclusion*.
- Under 60 seconds, hard. If it's 74 seconds, cut the Dead-Zone beat, not the green-pin beat.

### Words to use / words to ban

**Use:** unsold hours · chair time · booked out · next available · walk-aways · turned away · the
shape of their week · midweek · dead zone · fill rate.

**Ban:** leads · prospects · audit · score · grade · optimise · "in today's digital landscape" ·
*empty shop* (see Risk 1 — it is factually wrong and it is the sentence that gets the product
attacked).

---

## 6. Offer ladder validation

### The proposed ladder

| | Price | Contents |
|---|---|---|
| FE | $47 | 1 city, 3 niches, **Hungry list only** |
| OTO1 | $97 | Unlimited cities/niches + **Slammed bucket** + Dead-Zone Map |
| OTO2 | $197 | White-label Ledger reports |
| OTO3 | $297 | Fill-the-Chair campaign kit + LossReel credits |
| Backend | $997 | Agency license + **restaurant/clinic adapters** |

The *shape* is right — five rungs, standard IM spacing, each rung aimed at a slightly more
committed buyer. Four specific things are wrong.

### Critique 1 — The FE is crippled in the wrong place (this is the big one)

**Withholding the Slammed bucket behind OTO1 is a mistake, and it's the most expensive mistake in
the plan.**

Three reasons:

1. **It breaks the demo promise.** The 60-second demo's entire punch line — the beat at 0:34, the
   green pin, "two pitches, one scan" — is the Slammed bucket. That beat is what makes the product
   feel new. If a buyer watches that, pays $47, and discovers the green pins are locked, you have
   converted your best marketing asset into your best refund trigger. **Never demo the thing you're
   about to paywall.** The rule in launch funnels: the FE delivers the *mechanism* in full and the
   OTOs deliver *scale, automation and white-label*. Never sell half a mechanism.
2. **It halves the FE's chance of producing a win.** The Slammed pitch is objectively easier for a
   beginner to land — the prospect is solvent, flattered, and un-pitched. **Dave's first client is
   far more likely to come from a green pin than a red one.** Locking green pins means most FE
   buyers never get a result, which means no testimonials, no repeat buyers, and a support queue
   full of "this doesn't work."
3. **It's the most differentiated thing you own.** Anyone can eventually build a vacancy scanner.
   The "sell to the busy ones" insight is the part competitors won't copy because they won't
   understand it. Putting it behind a paywall means 70% of your customers never see the idea and
   never talk about it.

**Cripple on volume and automation, not on the idea.** Both are non-resentful (the buyer
understands why more usage costs more), both scale with your real COGS (§7), and neither breaks the
demo.

### Critique 2 — "3 niches" is the wrong lever, and "1 city" is the right one

"3 niches" is invisible friction. Dave has no idea whether he needs three niches or nine when he's
on the OTO page — he hasn't used the product yet. Limits that a buyer can't evaluate don't sell
upgrades; they just create a bad taste later.

**"1 metro area" is a good limit** — it's instantly legible, it maps to his actual life (he works
one town), and the upgrade trigger is obvious and self-generated ("I want to try Orlando too").
Keep the city cap, drop the niche cap, add a **credit cap** (§7).

### Critique 3 — The backend gates the wrong thing entirely

**Putting restaurant and clinic adapters behind $997 is a serious error.** Adapter coverage is not a
premium feature; it's whether the product works at all in a given city. A buyer in a town that's
light on salons but heavy on dental and physio will run one scan, get eleven results, and refund —
and he'll be right to.

**Coverage is refund driver #1 (§1). Never sell coverage.** Every adapter you have ships in the FE,
metered by credits. The $997 backend sells **seats, sub-accounts, reseller rights, and volume** —
things that only matter once you're already succeeding.

### Critique 4 — There's no order bump, and there's no "and then what"

Two gaps:

- **No order bump.** A $27 bump at ~35–40% take rate is the cheapest revenue in the entire funnel
  and costs nothing to fulfil. Its absence is leaving roughly $0.46/click on the table (see model
  below). The natural bump is the **Pitch Pack** — the forty scripts in §4 as editable templates
  plus the printable one-pager designs.
- **The FE dead-ends.** Dave lands a client with the Tuesday pitch… and then has to actually fill
  the Tuesdays. That capability lives entirely in OTO3 at $297. So the majority of FE buyers who get
  a "yes" have nothing to deliver. That is a customer-success failure that becomes a refund and a
  reputation problem. **Move a starter version of the campaign kit into the FE or the bump** — three
  midweek offers, one SMS template, one Google offer post — and keep the full kit at OTO3.

### Recommended ladder

| | Price | Contents | The upgrade trigger |
|---|---|---|---|
| **Bump** | **$27** | **Pitch Pack** — all 40 scripts (§4) as editable merge-field templates, the printable one-pager designs, the objection sheet | Impulse; sits right under the buy button |
| **FE** | **$47** | 1 metro · **all niches** · **all available adapters** · **BOTH buckets** · 250 scan credits/mo · manual on-demand scan · 4-week history · starter campaign kit (3 midweek offers + 1 SMS + 1 GBP post) | — |
| **OTO1** | **$97** *(downsell $67)* | **Autopilot** — unlimited metros · 2,500 credits/mo · **nightly auto-rescan + the 8am alert list** · Dead-Zone Map · "Movers" alerts (vacancy jumped >15pts) · CSV export | "I don't want to remember to run this" |
| **OTO2** | **$197** *(downsell: $27/mo)* | **Ledger Pro** — white-label client reports · agency branding · client-facing report portal · 25 monitored client businesses · **12 months included, then $27/mo** | "I need something to send my clients monthly" |
| **OTO3** | **$197** *(was $297)* | **Fill-the-Chair kit** — full dead-zone offer library, last-minute SMS sequences, GBP offer posts, waitlist/deposit SOPs, LossReel credits | "I got a yes, now what do I do?" |
| **Backend** | **$997** *(or $297 + $97/mo)* | Agency license — 10 sub-accounts, reseller rights, API access, priority adapters, unlimited credits | "I want my VAs and my clients in here" |

**The core structural change: FE = you pull, OTO1 = it pushes.** Manual on-demand scanning vs.
automated nightly monitoring with an alert list. This is elegant because it is simultaneously (a)
the most valuable upgrade in the product, (b) completely non-resentful, and (c) **exactly where your
COGS actually lives** — nightly monitoring of a whole city is the expensive thing, and now the
people who pay you more are the people who cost you more. Price and cost move together, which is the
only way the credit model in §7 stays honest.

**Also change OTO3's price to $197.** At $297 it competes head-on with OTO2 for the same wallet in
the same five minutes, and it's a template/content product this deep in the funnel — the perceived
value doesn't support $297 without a services component. Dropping to $197 should lift take rate
enough to more than cover the price cut (modelled below).

### The EPC model

**Assumptions — all [ASSUMPTION] unless marked. Change these and the whole model moves; they are
deliberately stated so an affiliate can argue with them on a JV call.**

| Input | Pessimistic | Base | Optimistic | Basis |
|---|---|---|---|---|
| FE conversion (visitor → buyer) | 2.5% | 4.0% | 6.0% | Tripwire FE benchmark is 1.5–3% for cold traffic [VERIFIED — [VWO/industry](https://vwo.com/blog/high-converting-sales-funnel/)]; IM launch traffic is pre-sold affiliate list traffic, so 3–6% is normal for a $47 offer [ASSUMPTION] |
| Bump take rate | 30% | 38% | 45% | Order-bump benchmark ~40% [VERIFIED — same source] |
| OTO1 take rate | 22% | 32% | 38% | Upsell benchmark ~20–25% average [VERIFIED]; a well-matched OTO1 in IM funnels commonly runs 25–35% [ASSUMPTION] |
| OTO1 downsell take (of non-takers) | 8% | 10% | 12% | [ASSUMPTION] |
| OTO2 take rate | 10% | 15% | 20% | [ASSUMPTION] |
| OTO3 take rate | 7% | 10% | 13% | [ASSUMPTION] — uplifted vs. $297 pricing |
| Backend take (of FE buyers) | 0.8% | 1.5% | 2.5% | [ASSUMPTION] — backend usually sells post-launch, not in-funnel |
| Refund rate | 18% | 12% | 9% | JVZoo typical range 3–8%, "healthy" under 7% [VERIFIED — [JVZoo/W+ benchmarks](https://saaspenguin.com/jvzoo-vs-warriorplus)]. **I model higher deliberately: this product has real delivery risk (coverage, anti-bot, "vacancy isn't vacancy"), and crawler-dependent tools refund above category average** [ASSUMPTION] |
| Affiliate commission | 100% FE + bump, 50% OTOs | same | same | Standard aggressive new-vendor structure |
| Payment processing | 3.5% | 3.5% | 3.5% | [ASSUMPTION] |

#### Founder's proposed ladder — base case, per 1,000 clicks

```
FE      1,000 × 4.0%              = 40.0 sales × $47  = $1,880.00
OTO1    40 × 30%                  = 12.0 sales × $97  = $1,164.00
OTO2    40 × 15%                  =  6.0 sales × $197 = $1,182.00
OTO3    40 ×  8%                  =  3.2 sales × $297 =   $950.40
Backend 40 ×  1.5%                =  0.6 sales × $997 =   $598.20
                                   GROSS FUNNEL REVENUE = $5,774.60

Affiliate share  = $1,880.00 (100% FE)
                 + $3,894.60 × 50%  = $1,947.30
                 = $3,827.30        → GROSS EPC = $3.83
Less 12% refunds                    → NET EPC   = $3.37

Vendor gross     = $5,774.60 − $3,827.30 = $1,947.30
Less 12% refunds → $1,713.62
Less 3.5% fees on gross ($202.11)   → ~$1,511 per 1,000 clicks, before COGS
```

#### Recommended ladder — base case, per 1,000 clicks

```
FE      1,000 × 4.5%              = 45.0 sales × $47  = $2,115.00
Bump    45 × 38%                  = 17.1 sales × $27  =   $461.70
OTO1    45 × 32%                  = 14.4 sales × $97  = $1,396.80
  +downsell (45−14.4)=30.6 × 10%  =  3.06 sales × $67 =   $205.02
OTO2    45 × 15%                  =  6.75 sales × $197= $1,329.75
OTO3    45 × 10%                  =  4.5 sales × $197 =   $886.50
Backend 45 × 1.5%                 =  0.675 sales × $997= $672.98
                                   GROSS FUNNEL REVENUE = $7,067.75

Affiliate share  = $2,576.70 (100% of FE + bump)
                 + $4,491.05 × 50% = $2,245.53
                 = $4,822.23       → GROSS EPC = $4.82
Less 12% refunds                   → NET EPC   = $4.24

Vendor gross     = $7,067.75 − $4,822.23 = $2,245.52
Less 12% refunds → $1,976.06
Less 3.5% fees on gross ($247.37)  → ~$1,729 per 1,000 clicks, before COGS
```

**FE conversion is modelled 0.5pt higher (4.0% → 4.5%) on the recommended ladder** because the FE
now delivers what the demo showed. That's a judgement call, not a measured effect [ASSUMPTION] — but
note the recommended ladder wins on gross EPC ($4.82 vs $3.83, **+26%**) even if you hold FE
conversion flat at 4.0%: at 4.0% it still produces a gross EPC of **$4.31**, +12.5%, entirely from
the bump and the OTO1 downsell. **The bump alone is worth ~$0.46/click and costs nothing to build.**

#### Full three-scenario range (recommended ladder)

| | Pessimistic | Base | Optimistic |
|---|---|---|---|
| FE sales / 1,000 clicks | 25 | 45 | 60 |
| Gross funnel revenue | $3,556 | $7,068 | $11,606 |
| **Affiliate gross EPC** | **$2.41** | **$4.82** | **$7.85** |
| **Affiliate net EPC (after refunds)** | **$1.98** | **$4.24** | **$7.14** |
| Vendor net (per 1,000 clicks, pre-COGS) | ~$820 | ~$1,729 | ~$3,197 |

### What this means for the JV page

- **Publish the base case, not the optimistic case.** Affiliates in this space have been burned by
  inflated EPC promises and they compare notes. A stated **"$4–5 EPC target"** with the assumptions
  printed underneath is more credible — and more persuasive to the good affiliates — than "$7 EPC!"
  with nothing behind it. An EPC above $1.50 is already considered good on JVZoo [VERIFIED —
  [industry benchmark](https://jvzooextractor.com/blog/best-jvzoo-products-to-promote-2025)], so
  $4+ is a genuinely strong pitch that doesn't need inflating.
- **Even the pessimistic case clears $1.98 net EPC**, which beats the platform's "good" threshold.
  Say that on the JV page — *"our downside case still pays you a $1.98 EPC"* is a far stronger
  argument than any upside number, and it's the sentence that gets a mid-tier affiliate to commit.
- **Watch the refund rate obsessively in the first 72 hours.** At 18% refunds the vendor's net per
  1,000 clicks drops by more than half. Refunds are the single biggest swing factor in this model —
  bigger than conversion rate — because they hit the vendor's slice disproportionately (affiliates
  are paid on the front, refunds come out of the back).

---

%%SECTION7PLACEHOLDER%%

## 8. Launch plan

### 8.1 Pre-launch: affiliate recruitment (start T−45 days)

The single hardest thing about this launch is that **the mechanism needs to be seen to be
understood.** "It reads their booking calendar" doesn't land in a text email to a JV list — it lands
in eleven seconds of video. Structure the entire recruitment around getting the demo watched.

| When | Action |
|---|---|
| **T−45** | Cut the 60-second demo (§5) on a real city. This exists before the JV page, before the sales page, before anything. Nothing else in recruitment works without it. |
| **T−40** | JV page live. Above the fold: the demo, the EPC table (base case), the commission structure, the contest, the calendar. |
| **T−38** | **Personal outreach to the top 25 only.** Not a broadcast. A 40-second Loom *addressed to them by name*, running a scan on **their** city and **their** niche. "Here's Boise. Here's the eleven emptiest med spas in Boise this week." This is the highest-ROI hour in the entire launch — the tool personalises its own affiliate recruitment, which almost no IM product can do. Build a tiny internal script that generates these in bulk. |
| **T−30** | Broadcast to the wider JV list. Lead with the identity-shift angle, not the features. |
| **T−21** | Reviewer seeding — full funnel access to 15–20 review-site owners (§8.5). |
| **T−14** | JV webinar / live scan session. Take city names from chat and scan them live. **This is the proof event.** If the crawler can't survive a live audience typing in random cities, you are not ready to launch. |
| **T−7** | Swipe emails, banners, bonus pages, the demo in three lengths (15s / 60s / 3min) delivered. |
| **T−3** | Leaderboard opens; early-bird affiliate contest tiers announced. |

**Commission structure:** 100% on FE + bump, 50% on OTOs, 50% on the backend. 100% FE is the price
of attention for an unproven product concept — and this concept genuinely needs the demo watched
before anyone believes it. **Recommend 60% on OTOs for the top 10 affiliates** as a private tier,
rather than raising it for everyone.

**Contest:** $5k–$10k prize pool, but structure it to reward *mid-tier* affiliates, not just the top
three. Tiered thresholds (25 / 75 / 200 / 500 sales) get far more small lists to actually mail than
a winner-take-all leaderboard, and mid-tier affiliates are where launch volume actually comes from.

### 8.2 JV page angles (in the order they should appear)

1. **"This is the first new *qualifier* in local since Google Maps scraping."** Frame it as a
   category event, not a product. Affiliates promote things their list hasn't seen before; the
   entire pitch to an affiliate is novelty, and this genuinely is novel.
2. **"Your list has already bought four website-audit tools. They will not buy a fifth."** Speaks
   directly to the affiliate's actual problem — list fatigue. Positions your product as the reason
   their unsubscribes stop.
3. **"The demo sells it. You just have to get them to press play."** Low perceived promotional
   effort. Affiliates choose launches partly on how much work the promotion is.
4. **"Two markets in one product."** The Slammed bucket is the thing that makes an experienced
   affiliate lean in — it's the part they haven't heard before and it's what they'll talk about on
   their review video.
5. **"$4.82 base-case EPC — here are the assumptions."** Publish the model from §6. Transparency is
   a differentiator on JV pages, where everyone else is claiming $7 with no arithmetic.
6. **"Deep funnel, real backend."** Bump + 3 OTOs + a $997 backend + recurring. Affiliates care
   about funnel depth more than FE price.

### 8.3 The three emails that sell this

These are the **vendor's** list emails; affiliates get their own swipes derived from these.

---

**EMAIL 1 — "Tuesday afternoon" (the mechanism reveal). Send: launch morning.**

> **Subject:** their website is fine. their Tuesdays are empty.
> **Preview:** the number no local tool has ever shown you

> Every tool you've ever bought for local scores their website.
>
> Speed. Mobile. SSL. Meta tags. A number out of a hundred.
>
> And every one of those tools ends the same way: you email a business owner and tell him his
> website is bad. He knows. He's known for six years. It hasn't stopped him being fully booked in
> March.
>
> So he doesn't reply. And after forty of those, neither do you.
>
> Here's what nobody has been looking at.
>
> Right now, that same guy's booking page — the public one, the one his customers use — is showing
> **38 open appointments** between today and next Friday. Mostly Tuesday and Wednesday afternoons.
> At his own listed prices that's **$1,860** of chair time that nobody is going to buy.
>
> He can feel that number. He's never seen it written down.
>
> Now imagine the email you send him starts with *that* instead of "I noticed your website could use
> some improvements."
>
> [PRODUCT] reads the public booking calendars of every salon, barbershop, med spa, gym, clinic and
> studio in your city — and hands you the list. Who's empty. How empty. What it's worth.
>
> 60-second demo, no opt-in: [link]
>
> — {name}
>
> P.S. There's a second half to this that's honestly better than the first half. It's in the demo at
> the 34-second mark. Watch to the end.

---

**EMAIL 2 — "The busy ones" (the differentiation / second market). Send: launch day +1.**

> **Subject:** nobody pitches the busy salon
> **Preview:** which is exactly why you should

> Yesterday I showed you the empty ones.
>
> Today, the part that made me actually want to build this.
>
> Every prospecting tool ever made has the same hidden instruction: **find the businesses that are
> failing.** Find the bad website, the missing listing, the two-star review. Find the ones who are
> losing.
>
> Which means every consultant in your city is fighting over the same broke half of the market.
>
> Meanwhile there's a barbershop four streets away that's booked out **nineteen days**.
>
> Nobody has ever cold-called him. Not once. Because no tool can tell that he's full — being full
> doesn't show up on your website.
>
> And here's the thing: **he has a worse problem than the empty guy.** Every person who checks his
> page, sees "nineteen days," and quietly goes somewhere else — he never sees them. He never counts
> them. It's the biggest number in his business and it's completely invisible.
>
> He doesn't need customers. He needs a waitlist, a deposit policy, and a price rise. That's a
> $500-a-month engagement, and he can actually pay it — unlike the empty guy who's three months
> behind on rent.
>
> Same scan. Same tool. Opposite pitch.
>
> [PRODUCT] sorts the whole city into two lists and writes the matching pitch for both.
>
> [link]
>
> — {name}

---

**EMAIL 3 — "How do you know that?" (identity shift + close). Send: launch day +3, or the day before cart close.**

> **Subject:** the four words that end the cold call
> **Preview:** and the four that start the conversation

> Here are the four words that end it:
>
> **"We're not interested."**
>
> You've heard them before you finish your sentence. You don't even blame him — you sound exactly
> like the three people who called before you today.
>
> Here are the four words that start it:
>
> **"How do you know that?"**
>
> That's what he says when you open with *"I noticed you had 38 open slots this week — mostly
> Tuesday afternoons."*
>
> And notice what's happened. You are no longer having a sales conversation. You're having a
> conversation about his business, with someone who has obviously already done the work. You've
> skipped the entire part you were bad at.
>
> That's the whole product. Everything else — the map, the scoring, the one-pagers, the dollar
> figures — exists to get you to that one sentence.
>
> **You stop being a marketer. You become the person who knows their calendar.**
>
> Nobody else in your city has that job, because until now nobody could see the calendar.
>
> Doors close [time]. $47.
>
> [link]
>
> — {name}
>
> P.S. If you've bought a website-audit tool and never used it — I know why, and it wasn't laziness.
> You didn't have anything to say. That's the specific problem this fixes.

---

### 8.4 Bonus stack ideas

The rule: **every bonus should shorten the distance to the first client.** Nothing generic, nothing
PLR, nothing that is obviously a folder of PDFs from 2019.

**Tier 1 — bonuses that remove a blocker (best)**
- **"Your First 10 Calls" pre-loaded pack** — on signup, the tool auto-runs one free scan of the
  buyer's own city and delivers ten finished pitch packs before he's touched the interface. He is
  three clicks from a phone call within ninety seconds of buying. This is the highest-converting
  bonus you can build and it costs one scan batch.
- **The objection sheet** — the eight things a local owner actually says back ("we take walk-ins",
  "we block those slots", "we're not looking for marketing"), with the tested response for each.
  Directly kills the biggest cause of buyer abandonment.
- **Dead-Zone Offer Library** — thirty tested midweek offers by vertical, with the exact GBP post
  and SMS copy. (Starter version in the FE, full version is OTO3.)

**Tier 2 — bonuses that build confidence**
- **"The Slammed Playbook"** — the waitlist / deposit / price-increase SOPs, since almost nobody in
  this buyer pool has ever sold a capacity engagement.
- **Live "scan my city" session** — a recorded group call in week one where buyers post their city
  and you run it live. Doubles as your best refund-prevention tool and your best testimonial mine.
- **A pricing + proposal template** — $497 setup / $197/mo, filled in, ready to send.

**Tier 3 — affiliate bonus ammo (give affiliates something to build their own stack on)**
- Rights to give away 50 bonus credits per buyer.
- A white-label version of the objection sheet.

**Do not** offer "10,000 PLR articles", a "$4,997 value" stack of unrelated old products, or
anything with a fake value number. Segment B (Marisa) is actively repelled by it and the good
affiliates read bonus pages as a quality signal.

### 8.5 Review-copy angle

Send reviewers the **funnel access plus a pre-run scan of their own city**. Ask them explicitly to:

1. Type their own city into the box on camera. Unscripted.
2. Click a red pin **and** a green pin.
3. Say out loud the sentence they'd use to open a call.

The review video basically writes itself, and reviewers love a product that survives an unscripted
input. **Conversely: if the crawler can't handle a reviewer's random city, you find out three weeks
before launch instead of during it.** Treat reviewer seeding as your load test.

Give reviewers the honest limits sheet (§8.6) up front. In this market, reviewers who find a
limitation you didn't disclose will lead with it; reviewers you hand it to will frame it as
credibility. Reviewers who say "and the vendor tells you this on the sales page" sell more copies
than reviewers who gush.

### 8.6 "Say the risks first" — sales-page trust blocks

Put these in a boxed section titled **"Four things I'd rather tell you now than have you find
out."** Placed after the demo and before the price. Each one is written to be lifted onto the page
as-is.

---

#### Block 1 — Vacancy isn't always vacancy

> **We count online-bookable open slots. That is not the same as an empty shop.**
>
> Some of those open slots are blocked out on purpose. Some are a stylist's day off. Some will get
> filled by walk-ins nobody records. [PRODUCT] cannot see any of that, and it will never claim to.
> Every number this tool produces says **"online-bookable open slots"** — because that's exactly
> what it is.
>
> Here's why it still works, and works better than a website score:
>
> Online-bookable hours are the **only hours a business can pre-sell.** If they're open, they're
> unsold *and* unsellable-in-advance. So when you walk in and say *"you've got 38 slots that nobody
> can book online this week"* and the owner says *"well, we get walk-ins"* — congratulations, you're
> now in a conversation about his calendar. That is the entire objective.
>
> And be honest: he knows. Nobody who owns a shop is surprised to hear his Tuesday afternoons are
> quiet.

---

#### Block 2 — The platforms don't want to be read

> **Booking platforms use bot protection, and sometimes we lose.**
>
> Booksy, Vagaro, Fresha and the rest are not obliged to make this easy, and they don't. We run
> real browsers, we rotate, we go slow, we cache. We are polite about it — **one pass per business
> per day, ever.**
>
> Two honest consequences:
>
> 1. **Some scans will come back partial**, and the tool tells you when they do instead of quietly
>    guessing. A business we couldn't read is marked "no data," never "0% booked."
> 2. **Adapters break.** A platform ships a redesign and that adapter goes down for a few days while
>    we fix it. That's the job. It's also the moat — it's the reason there isn't a free version of
>    this.
>
> You can see the live status of every adapter, in the app, before you spend a credit.

---

#### Block 3 — Coverage: you only see businesses that book online

> **If a business has no online booking, it does not appear in [PRODUCT]. At all.**
>
> That's a real limit and you should factor it in. In a town of 30,000 you might get 40 targets, not
> 400.
>
> But look at who that filter removes and who it keeps.
>
> It removes the businesses with no software, no systems, no card on file and no budget — the exact
> people who waste six weeks of your time and then ghost you.
>
> It keeps the ones who **already pay monthly for software.** They've already decided that
> technology is worth money. They have a card on file *right now*. They're pre-qualified in the only
> way that actually matters.
>
> Fewer names. Much better names. And roughly 78% of salons worldwide have already adopted online
> booking — this filter is getting less restrictive every year, not more.

---

#### Block 4 — We never touch a slot

> **[PRODUCT] is read-only. Permanently. Architecturally.**
>
> It cannot book an appointment. It cannot hold one. It cannot cancel one. There is no code in this
> product that submits a booking form, because we never wrote any.
>
> It looks at the same public availability page your customers look at, writes down what's open,
> and leaves. Once per business, per day.
>
> We're saying this loudly because we know exactly what one bad story would do — to us, and more
> importantly to you, standing in front of a business owner. **The number you show them was never
> obtained by doing anything to their calendar.** You can say that out loud, and it will always be
> true.

---

**Why this section works:** it pre-empts the exact four objections a competing affiliate will raise
in a "should you buy X?" review video, and it converts each into evidence that you're the honest
vendor. In a market where every sales page over-claims, a page that under-claims in four specific
places reads as confidence. It also does real work on refunds — Block 3 in particular directly
addresses refund driver #1 (§1) *before* the purchase.

---

## 9. Retention / MRR path

### The problem with OTO2 as designed

The brief describes OTO2 as: *"white-label Ledger reports (retention play — consultant charges
$97–197/mo for capacity monitoring)."*

Read that back carefully. **You are building the recurring-revenue engine, and then handing 100% of
the recurring revenue to the customer for a one-time $197.**

The customer gets $97–197/mo × N clients, forever. You get $197, once. And the ongoing COGS —
because a white-label monthly Ledger report means you are re-scanning that client's business every
single month, forever, at your expense, for a customer who already paid you and is done paying.

**OTO2 as designed is a permanent negative-margin liability disguised as an upsell.** At scale it is
the thing that kills the business quietly: two years in, you have thousands of lifetime users each
generating monthly crawls you're never paid for again. That is the same failure mode that has
killed a long line of "unlimited lifetime" IM SaaS products.

### The fix: OTO2 is where your MRR is born, not where it dies

The Ledger is the highest-value object in the product and the only one with genuinely recurring
cost. It must have genuinely recurring price.

**Recommended OTO2 structure:**

> **Ledger Pro — $197 today, includes 12 months. Then $27/month.**
> *Or: $27/month from day one, cancel anytime — [downsell button]*

Why this specific shape:

- **It does not hurt launch conversion.** IM buyers reflexively refuse forced continuity on an OTO
  page; a big "$197" with the recurring stated plainly beneath it converts close to a flat $197,
  because the buyer is paying the same amount today.
- **It is honest and it survives the review videos.** Disclose the month-13 charge in the same font
  size as the price. Hidden continuity is how a launch gets a reputation problem, and this market
  talks.
- **It defers the churn decision to a point where the product has proven itself.** By month 13 the
  consultant has been sending white-label Ledger reports to real clients for a year. Cancelling
  means telling clients the monthly report is stopping. Churn at that point is very low
  [ASSUMPTION].
- **It matches cost to revenue.** Monitored client businesses are exactly the recurring COGS. Cap
  the $27 tier at 25 monitored businesses, then $27 per additional 25.

### The subscription tier I'd actually add

Beyond fixing OTO2, add **one** standalone subscription. Not three tiers — one, with a name.

> ## Watchtower — $47/month
> **Your whole city, watched every night.**
> - Unlimited metros, unlimited niches
> - 5,000 scan credits/month (top-ups $15 / 1,000)
> - Nightly re-scan of every business you've ever flagged
> - The 8am list: new Hungry, new Slammed, and **Movers** — anyone whose vacancy jumped 15+ points
> - Full 12-month Ledger history on every tracked business
> - Adapter early access

**Why $47/mo and why this framing:**

- **It's the FE price, monthly.** Trivial to explain, trivially anchored: *"the same $47 you paid
  once, except now the city watches itself."*
- **The value proposition is time, not features** — the thing an IM buyer will actually pay monthly
  for. Nobody renews for features; people renew for "it does it while I sleep."
- **The Movers alert is the killer.** *"Bella Nails went from 22% open to 61% open in three weeks"*
  is the single most actionable prospecting signal the product can generate — that business just
  lost a stylist or lost a contract, and they are in pain **this week**. No competitor can produce
  that signal at all, and it only exists if you're scanning continuously, which is only economic if
  someone is paying monthly. Feature, price and cost are aligned.
- **It's the natural landing spot for OTO1 buyers.** OTO1 ($97 "Autopilot") gives them nightly
  scanning with a fixed credit pool. Watchtower is the same idea unbounded. Offer it at the OTO1
  page as a checkbox ("add Watchtower, first month $1") and on the dashboard as a credit-exhaustion
  upsell.

### Where the subscription is sold (three doors, in order of yield)

1. **Credit exhaustion — the highest-converting moment in the product.** When a user burns their
   monthly credits, that's a user who is *actively working*. The upgrade prompt at that moment
   converts far better than any OTO page. Build this before launch, not after.
2. **Month 13 on OTO2** — the built-in continuity above.
3. **The OTO1 order bump** — "add Watchtower, first month $1."

### The MRR model

**Assumptions [ASSUMPTION throughout]:** 3,000 FE buyers in the launch window; 15% take OTO2 (450);
90% of those still active at month 13 → 405 × $27 = **$10,935/mo** starting month 13. Watchtower
converts 8% of all FE buyers within 90 days (240) at $47 = **$11,280/mo**, with 8% monthly churn
settling to a steady state around 180–200 subscribers ≈ **$8,500–9,400/mo**.

**Steady state ≈ $18,000–20,000 MRR from a single 3,000-unit launch** — versus **$0** under the
ladder as proposed, plus an uncapped, unpaid, permanent crawling liability.

That gap is the difference between a launch and a company. It is, in my view, the most important
recommendation in this document after the naming fix.

### Retention mechanics that actually keep people

- **The Ledger is the retention product, so put a taste of it in the FE.** Four weeks of history in
  the FE (§6) exists specifically so that by week four the user has a chart, and a chart is a
  reason to log in again. A prospecting tool with no history has no second visit.
- **Email the 8am list even to free-tier-ish users.** The daily email is the habit. If they stop
  opening it, they've churned regardless of what the billing says.
- **Make the first client's Ledger free forever.** Whoever their first client is, monitor that
  business at no charge, permanently. It costs you a few scans a month and it makes the product
  emotionally un-cancellable — it's the record of their first win.

---

## 10. Naming check

I searched for existing products and marks. **One serious problem, one moderate one, two clean.**

### 🔴 "OpenChair" — do not use. Rename before anything else is built.

This is the strongest recommendation in the document. The name is occupied, repeatedly, in exactly
this vertical:

| Name | What it is | Collision severity |
|---|---|---|
| [**openchairpro.com** / tryopenchair.com](https://openchairpro.com/) | Live salon & barbershop management SaaS, owned by **Open Chair Pty Ltd** (ABN 11 699 884 074), AU/NZ/UK, A$34.95/mo. Its own marketing line: *"Salon software that fills cancellations… AI detects gaps in the calendar."* | **Severe.** Trading company of the same name, and its headline feature is calendar-gap detection. |
| [**openchair.ai** / findanopenchair.com](https://openchair.ai/) | AI booking platform whose stated function is *"deep links with service providers' appointment book systems, identifies openings"* | **Severe.** This is functionally the closest thing to your product that exists — it finds open appointment slots. Different customer (consumers, not consultants), same data, same name. |
| [**theopenchairapp.com**](https://theopenchairapp.com/) | Marketplace for renting salon chairs to stylists | Moderate — and it reveals the **semantic problem** below. |
| **OpenChair — barber booking app**, by **Open Chair LLC** (iOS) | Consumer barber booking | Moderate; note the LLC name. |
| **OPEN CHAIR**, USPTO Reg. **5182258**, Serial 86661895 | Registered 11 Apr 2017. Covers software for managing dialysis patients and *"patient relationship management… to schedule medical appointments."* | Moderate. Different field of use, but the services description touches **appointment scheduling**, which is uncomfortably adjacent given your brief lists dental/chiro/physio/med-spa as target verticals. |

[VERIFIED — all of the above, via the linked sites and [Justia trademark record](https://trademarks.justia.com/866/61/open-86661895.html)]

**Three independent reasons to rename, any one of which is sufficient:**

1. **Legal.** A registered US mark whose services include appointment scheduling, plus at least two
   operating companies named "Open Chair" in the beauty-tech space. You cannot register the mark and
   you are exposed on any vertical that touches clinics.
2. **Commercial.** Search results for your product name will be dominated by four competitors, one
   of which sells the adjacent product to the adjacent buyer. Every affiliate review video, every
   Facebook-group mention, every "is [PRODUCT] legit?" search leaks traffic to someone else. On a
   JVZoo launch, where the primary discovery channel *is* a name search, this is a direct revenue
   loss.
3. **Semantic — and this is the one people miss.** In the salon industry, **"an open chair" already
   means a chair available to rent to a stylist.** That's exactly what theopenchairapp.com sells.
   Your name tells your target vertical the wrong thing in their own vocabulary. You'd be spending
   the first ten seconds of every conversation correcting the name.

**Proposed alternates.** The name should own *unsold time*, be a real word or a coined compound, and
be clean in salon/booking search:

| Name | Why | Notes |
|---|---|---|
| **DeadHours** ⭐ | Says the entire product in two syllables. "Dead hours" is language owners already use, with none of the salon-jargon collision "open chair" has. Aggressive in the right way. | Recommended. Verify `.com`/`.io` and USPTO before committing. |
| **Vacancy** / **VacancyIQ** | "Vacancy Score" is already your core metric — the name and the metric reinforce each other. Slight hotel connotation, which actually helps: hotels are the one industry that has always priced unsold time. | Strong second. Bare "Vacancy" is likely unregistrable; a compound is safer. |
| **Unsold** | Blunt, memorable, ownable. The whole thesis in one word. | Strong, but check for collisions in retail/inventory software. |
| **SlotHound** | Descriptive of the mechanic, obviously a prospecting tool, easy to say. Slightly more "IM launch" flavoured — which for a $47 JVZoo product is a feature, not a bug. | Good fallback. |
| **Deadstock** | Retail term for unsold inventory, applied to time. Great concept, but "deadstock" is heavily owned in sneaker/fashion resale. | ❌ Avoid. |
| ~~**Unbooked**~~ | Semantically perfect — but **taken.** [UnBooked](https://www.prnewswire.com/news-releases/unbooked-teams-up-with-bizlink-salon-software-to-help-fill-appointment-times-300266640.html) is an existing yield-management platform that sells *"control over unsold inventory during slow and off-peak periods"* to salons. [VERIFIED] That is your exact concept, your exact vertical, and your exact wording. | ❌ Avoid. |

**Do a proper clearance search on the shortlist before spending on design.** A $500 knock-out search
now is cheap against re-branding a live launch.

### 🟡 "Radar" — usable as an internal component name, but don't lean on it

"Radar" is extremely well-trodden in this exact market: **Radar.com** (location infrastructure),
**Citation Radar** (Local Dominator), **GMB Radar** (local rank tracking). [VERIFIED — search
results] Trademark risk for a *component* inside a product is low; discoverability value is
approximately zero.

**Verdict:** fine internally, but don't put "Radar" in a headline or spend on it. If you want the
component names to work harder, **Sweep** or **Nightwatch** are more distinctive for a nightly
crawler (check Nightwatch — there is an SEO tool by that name).

### 🟢 "Meter" and "Ledger" — clean, keep them

Both are generic English nouns used as sub-feature names inside a branded product. That's the
lowest-risk possible naming pattern (nobody owns "Ledger" in the abstract; plenty of products have a
feature called one).

- **Meter** does its job — it measures, and "Vacancy Meter" is instantly readable.
- **Ledger** is the better of the two and deserves promotion. It's the retention product (§9), it
  sounds like something a business owner takes seriously, and "the Ledger" as a proper noun is
  genuinely good branding. Consider making it the *client-facing* name — the consultant hands over
  "your monthly Ledger," which sounds like accounting, not marketing. That framing is worth money.

⚠️ One flag: **"Ledger" collides with the crypto hardware-wallet company (Ledger SAS)** in general
brand search. Irrelevant for a feature name inside another product; **do not** use it as a standalone
product or spin it out under that name.

### 🟢 "Pitch" — clean, but slightly weak

No collision risk as a component name. It's just the least distinctive of the four — every sales
tool has something called Pitch, and there is a well-known presentation startup at **pitch.com**.
Consider **Walk-In** as the component name instead: *"tap Walk-In"* is more evocative, more specific
to how the product is actually used, and directly echoes the headline promise.

### 🟢 "S.E.A.T." — clean, and better than it looks

Scan → Estimate → Alert → Target. Checks out:

- **No meaningful collision.** The only "S.E.A.T. method" in search is an unrelated job-interview
  framework (Skills/Education/Achievements/Type). [VERIFIED] SEAT is also a Spanish car marque
  (Volkswagen Group) — different class entirely, no realistic conflict for a marketing methodology.
- **The pun is genuinely good** and it's *load-bearing*: it ties the acronym to the chair/seat
  metaphor that runs through the whole product. Rare for a marketing acronym to earn its keep.
- **Keep it even if you rename the product.** In fact it becomes *more* valuable after a rename —
  S.E.A.T. is where the chair imagery lives once "OpenChair" is gone, and it lets you keep the
  visual language (empty chairs, the seat icon) without the name collision.

**One refinement:** "Estimate" is the weakest letter — it's vague and it undersells. Consider
**S.E.A.T. = Scan · Evaluate · Alert · Target**, or keep Estimate but always render it as
*"Estimate the loss"* so the letter carries the dollar figure.

### Naming summary

| Name | Verdict |
|---|---|
| **OpenChair** | 🔴 **Rename.** Five live collisions in-vertical, a registered US mark touching appointment scheduling, and the phrase already means something else to your target customer. |
| **Radar** | 🟡 Keep internally; don't build brand on it. |
| **Meter** | 🟢 Keep. |
| **Ledger** | 🟢 Keep — and promote it; it's the retention product. Don't spin it out standalone. |
| **Pitch** | 🟢 Keep, or upgrade to **Walk-In**. |
| **S.E.A.T.** | 🟢 Keep. Becomes more valuable after the rename. |

---
