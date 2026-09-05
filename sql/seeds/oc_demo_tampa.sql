-- oc_demo_tampa.sql — the FR-1.4 demo market: "barbershop, Tampa".
--
-- Deterministic sample data so a new account sees a populated product before any
-- crawl has run. FR-1.4 requires time-to-first-value not be gated on the nightly
-- scan, and 40-ux-spec.md P2 is blunt about why: "your first results will be ready
-- tomorrow morning" is a refund.
--
-- Every row is identifiable by platform_business_key LIKE 'demo-%'. Nothing here
-- was observed from a real business; booking_url points at example.invalid; and
-- the shell flags the market as sample data so a demo number can never be mistaken
-- for an observed one.
--
-- The spread is shaped, not uniform: a Hungry tail, a fat middle, a Slammed tail,
-- and a few businesses deliberately under the confidence floor so the demo itself
-- exercises the "insufficient data — show the gap, not a number" path.
--
-- Idempotent: safe to re-run.

INSERT INTO `oc_business`
  (platform_id, platform_business_key, name, booking_url, address_line, city, region_code,
   country_code, city_key, niche_key, lat, lng, timezone, staff_count, slot_minutes,
   avg_price_cents, price_source, currency, status, first_seen_at, last_scan_at,
   last_good_scan_at, latest_observed_on, latest_vacancy_bp, latest_open_slots,
   latest_bookable_slots, latest_empty_chair_cents, latest_days_to_next, latest_bucket,
   latest_confidence)
VALUES
(3,'demo-001','Ybor Cuts & Fades','https://example.invalid/fresha/demo-001','1270 E 7th Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.990400,-82.397000,'America/New_York',6,30,2822,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',6376,168,264,71114,0,'hungry',79),
(2,'demo-002','Seminole Heights Barber Co','https://example.invalid/vagaro/demo-002','8034 N Florida Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.973100,-82.461000,'America/New_York',3,30,3394,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',3827,54,141,27491,1,'neutral',84),
(4,'demo-003','Bayshore Grooming','https://example.invalid/glossgenius/demo-003','3413 S Howard Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.941500,-82.453400,'America/New_York',4,30,4673,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',3775,69,184,48366,1,'neutral',79),
(2,'demo-004','Tampa Heights Cuts','https://example.invalid/vagaro/demo-004','4033 W Kennedy Blvd','Tampa','FL','US','us-fl-tampa','barbershop',28.008400,-82.520000,'America/New_York',3,30,3900,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',8643,135,156,78975,0,'hungry',28),
(2,'demo-005','Channelside Barbers','https://example.invalid/vagaro/demo-005','3066 N Nebraska Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.972300,-82.409900,'America/New_York',2,30,4678,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',5608,52,92,36488,1,'neutral',71),
(4,'demo-006','Hyde Park Barber Lounge','https://example.invalid/glossgenius/demo-006','492 E Fowler Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.895600,-82.395500,'America/New_York',3,30,3779,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',7347,99,135,56118,0,'hungry',65),
(2,'demo-007','Westshore Fade House','https://example.invalid/vagaro/demo-007','4401 S Dale Mabry Hwy','Tampa','FL','US','us-fl-tampa','barbershop',27.972900,-82.445400,'America/New_York',3,30,3728,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',5096,61,120,34111,0,'neutral',73),
(3,'demo-008','Sulphur Springs Clippers','https://example.invalid/fresha/demo-008','5708 N Armenia Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.984700,-82.492400,'America/New_York',4,30,3061,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',5229,84,160,38569,0,'neutral',91),
(5,'demo-009','Palma Ceia Barbershop','https://example.invalid/square_appointments/demo-009','7178 E Hillsborough Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.908600,-82.527700,'America/New_York',5,30,3840,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',7003,147,210,84672,0,'hungry',94),
(3,'demo-010','Riverside Razor','https://example.invalid/fresha/demo-010','8703 W Platt St','Tampa','FL','US','us-fl-tampa','barbershop',27.950200,-82.458600,'America/New_York',6,30,4100,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',5471,125,228,76875,3,'neutral',74),
(1,'demo-011','Davis Islands Grooming','https://example.invalid/booksy/demo-011','3451 E 7th Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.979200,-82.457700,'America/New_York',6,30,4174,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',3484,102,294,63862,0,'neutral',83),
(4,'demo-012','V.M. Ybor Cuts','https://example.invalid/glossgenius/demo-012','3483 N Florida Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.958200,-82.480300,'America/New_York',6,30,3305,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',9153,286,312,141785,0,'hungry',66),
(3,'demo-013','Armature Works Barber','https://example.invalid/fresha/demo-013','4170 S Howard Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.963200,-82.405700,'America/New_York',3,30,4795,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',3061,43,141,30928,1,'neutral',65),
(5,'demo-014','Nebraska Ave Cuts','https://example.invalid/square_appointments/demo-014','5886 W Kennedy Blvd','Tampa','FL','US','us-fl-tampa','barbershop',27.940700,-82.526900,'America/New_York',4,30,3820,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',5326,107,200,61311,0,'neutral',65),
(1,'demo-015','Old Seminole Shave Co','https://example.invalid/booksy/demo-015','4582 N Nebraska Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.891800,-82.428000,'America/New_York',2,30,4549,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',1483,12,84,8188,23,'slammed',82),
(3,'demo-016','Kennedy Blvd Barbers','https://example.invalid/fresha/demo-016','8869 E Fowler Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.956800,-82.496500,'America/New_York',3,30,2842,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',6114,72,117,30694,0,'hungry',77),
(3,'demo-017','Port Tampa Clippers','https://example.invalid/fresha/demo-017','8280 S Dale Mabry Hwy','Tampa','FL','US','us-fl-tampa','barbershop',27.891100,-82.485100,'America/New_York',2,30,4421,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',4430,45,102,29842,1,'neutral',82),
(1,'demo-018','Drew Park Fades','https://example.invalid/booksy/demo-018','9117 N Armenia Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.902400,-82.436500,'America/New_York',4,30,3719,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',6197,102,164,56901,0,'hungry',69),
(1,'demo-019','Beach Park Barber','https://example.invalid/booksy/demo-019','2993 E Hillsborough Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.938500,-82.460100,'America/New_York',3,30,4042,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',7674,99,129,60024,0,'hungry',92),
(5,'demo-020','Gandy Cuts','https://example.invalid/square_appointments/demo-020','929 W Platt St','Tampa','FL','US','us-fl-tampa','barbershop',27.950900,-82.474100,'America/New_York',4,30,4235,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',1033,17,160,10799,24,'slammed',79),
(3,'demo-021','Temple Terrace Barbershop','https://example.invalid/fresha/demo-021','2108 E 7th Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.966400,-82.481900,'America/New_York',4,30,3940,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',3483,53,152,31323,1,'neutral',37),
(5,'demo-022','Carrollwood Grooming Co','https://example.invalid/square_appointments/demo-022','2210 N Florida Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.920400,-82.414100,'America/New_York',6,30,4352,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',2015,60,300,39168,9,'slammed',79),
(5,'demo-023','North Hyde Park Cuts','https://example.invalid/square_appointments/demo-023','7409 S Howard Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.993200,-82.398900,'America/New_York',3,30,3904,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',2750,41,150,24010,2,'neutral',89),
(2,'demo-024','Ridgewood Razor','https://example.invalid/vagaro/demo-024','1479 W Kennedy Blvd','Tampa','FL','US','us-fl-tampa','barbershop',27.934400,-82.439300,'America/New_York',5,30,3798,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',7708,170,220,96849,0,'hungry',59),
(3,'demo-025','Sunset Park Barbers','https://example.invalid/fresha/demo-025','4099 N Nebraska Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.946900,-82.489600,'America/New_York',5,30,3811,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',7127,139,195,79459,0,'hungry',67),
(5,'demo-026','Egypt Lake Fades','https://example.invalid/square_appointments/demo-026','349 E Fowler Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.999100,-82.393600,'America/New_York',4,30,4425,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',1543,28,184,18585,17,'slammed',88),
(4,'demo-027','Forest Hills Barber','https://example.invalid/glossgenius/demo-027','1072 S Dale Mabry Hwy','Tampa','FL','US','us-fl-tampa','barbershop',27.938800,-82.398400,'America/New_York',2,30,3230,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',1584,14,90,6783,22,'slammed',83),
(5,'demo-028','Lowry Park Clippers','https://example.invalid/square_appointments/demo-028','6340 N Armenia Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.987100,-82.487400,'America/New_York',2,30,4627,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',2124,20,96,13881,20,'slammed',73),
(2,'demo-029','Sligh Ave Cuts','https://example.invalid/vagaro/demo-029','9213 E Hillsborough Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.959100,-82.429700,'America/New_York',6,30,4417,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',7913,218,276,144436,0,'hungry',61),
(1,'demo-030','MacFarlane Barber Co','https://example.invalid/booksy/demo-030','5604 W Platt St','Tampa','FL','US','us-fl-tampa','barbershop',27.983200,-82.419300,'America/New_York',4,30,3157,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',3041,46,152,21783,0,'neutral',61),
(3,'demo-031','Bon Air Grooming','https://example.invalid/fresha/demo-031','5670 E 7th Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.913300,-82.473600,'America/New_York',4,30,3355,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',7353,144,196,72468,0,'hungry',62),
(2,'demo-032','Wellswood Barbers','https://example.invalid/vagaro/demo-032','6378 N Florida Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.920000,-82.523500,'America/New_York',2,30,4736,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',5436,51,94,36230,3,'neutral',92),
(2,'demo-033','Virginia Park Cuts','https://example.invalid/vagaro/demo-033','8372 S Howard Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.973500,-82.422100,'America/New_York',3,30,4097,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',8169,115,141,70673,0,'hungry',93),
(5,'demo-034','Golfview Fade Lab','https://example.invalid/square_appointments/demo-034','3079 W Kennedy Blvd','Tampa','FL','US','us-fl-tampa','barbershop',27.974900,-82.428600,'America/New_York',3,30,4058,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',4424,60,135,36522,3,'neutral',79),
(2,'demo-035','Courier City Barber','https://example.invalid/vagaro/demo-035','4381 N Nebraska Ave','Tampa','FL','US','us-fl-tampa','barbershop',28.005000,-82.442300,'America/New_York',2,30,4452,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',5858,60,102,40068,1,'neutral',59),
(2,'demo-036','Gray Gables Grooming','https://example.invalid/vagaro/demo-036','5019 E Fowler Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.976400,-82.481900,'America/New_York',2,30,2897,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',5769,45,78,19555,1,'neutral',74),
(1,'demo-037','Parkland Barbershop','https://example.invalid/booksy/demo-037','5772 S Dale Mabry Hwy','Tampa','FL','US','us-fl-tampa','barbershop',27.903500,-82.487600,'America/New_York',6,30,3637,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',1988,62,312,33824,21,'slammed',60),
(3,'demo-038','Oakford Cuts','https://example.invalid/fresha/demo-038','3013 N Armenia Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.951500,-82.431500,'America/New_York',4,30,3129,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',5315,104,196,48812,3,'neutral',35),
(2,'demo-039','Belmont Heights Barbers','https://example.invalid/vagaro/demo-039','2124 E Hillsborough Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.936300,-82.410600,'America/New_York',6,30,3620,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',4847,131,270,71133,3,'neutral',78),
(2,'demo-040','Jackson Heights Fades','https://example.invalid/vagaro/demo-040','5469 W Platt St','Tampa','FL','US','us-fl-tampa','barbershop',27.953400,-82.520600,'America/New_York',4,30,3745,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',2863,50,176,28088,0,'neutral',91),
(4,'demo-041','East Tampa Clippers','https://example.invalid/glossgenius/demo-041','5029 E 7th Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.924100,-82.413900,'America/New_York',2,30,4479,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',1670,17,104,11421,15,'slammed',87),
(2,'demo-042','Grant Park Grooming','https://example.invalid/vagaro/demo-042','7890 N Florida Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.924100,-82.462800,'America/New_York',6,30,3878,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',2179,55,252,31994,25,'slammed',67),
(2,'demo-043','Highland Pines Cuts','https://example.invalid/vagaro/demo-043','8545 S Howard Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.996000,-82.409000,'America/New_York',6,30,4464,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',7647,216,282,144634,0,'hungry',64),
(3,'demo-044','College Hill Barber','https://example.invalid/fresha/demo-044','8454 W Kennedy Blvd','Tampa','FL','US','us-fl-tampa','barbershop',27.948900,-82.516700,'America/New_York',5,30,3768,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',5447,123,225,69520,1,'neutral',87),
(2,'demo-045','Live Oak Barbershop','https://example.invalid/vagaro/demo-045','6441 N Nebraska Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.938800,-82.457800,'America/New_York',2,30,3888,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',7174,57,80,33242,0,'hungry',94),
(2,'demo-046','Rembrandt Gardens Cuts','https://example.invalid/vagaro/demo-046','1430 E Fowler Ave','Tampa','FL','US','us-fl-tampa','barbershop',27.990000,-82.464600,'America/New_York',4,30,3551,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',5478,96,176,51134,0,'neutral',64),
(2,'demo-047','Southeast Seminole Fades','https://example.invalid/vagaro/demo-047','952 S Dale Mabry Hwy','Tampa','FL','US','us-fl-tampa','barbershop',27.927500,-82.387000,'America/New_York',4,30,3072,'menu','USD','active','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05 04:12:00','2026-09-05',8446,132,156,60826,0,'hungry',64)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  latest_vacancy_bp = VALUES(latest_vacancy_bp),
  latest_open_slots = VALUES(latest_open_slots),
  latest_bookable_slots = VALUES(latest_bookable_slots),
  latest_empty_chair_cents = VALUES(latest_empty_chair_cents),
  latest_days_to_next = VALUES(latest_days_to_next),
  latest_bucket = VALUES(latest_bucket),
  latest_confidence = VALUES(latest_confidence),
  latest_observed_on = VALUES(latest_observed_on);

-- The matching daily aggregate. The Meter reads THIS table, not the denormalised
-- projection on oc_business — that projection is a map-sort convenience, and a
-- model that reads it would be reading a cache it does not own.
INSERT INTO `oc_vacancy_daily`
  (business_id, observed_on, horizon_days, open_slots, bookable_slots, vacancy_bp,
   staff_count, avg_price_cents, empty_chair_cents, days_to_next, bucket, confidence)
SELECT b.id, '2026-09-05', 7, d.o, d.bk, d.vb, d.st, d.ap, d.ec, d.dn, d.bu, d.cf
  FROM `oc_business` b
  JOIN (
    SELECT 'demo-001' k,168 o,264 bk,6376 vb,6 st,2822 ap,71114 ec,0 dn,'hungry' bu,79 cf
    UNION ALL
    SELECT 'demo-002' k,54 o,141 bk,3827 vb,3 st,3394 ap,27491 ec,1 dn,'neutral' bu,84 cf
    UNION ALL
    SELECT 'demo-003' k,69 o,184 bk,3775 vb,4 st,4673 ap,48366 ec,1 dn,'neutral' bu,79 cf
    UNION ALL
    SELECT 'demo-004' k,135 o,156 bk,8643 vb,3 st,3900 ap,78975 ec,0 dn,'hungry' bu,28 cf
    UNION ALL
    SELECT 'demo-005' k,52 o,92 bk,5608 vb,2 st,4678 ap,36488 ec,1 dn,'neutral' bu,71 cf
    UNION ALL
    SELECT 'demo-006' k,99 o,135 bk,7347 vb,3 st,3779 ap,56118 ec,0 dn,'hungry' bu,65 cf
    UNION ALL
    SELECT 'demo-007' k,61 o,120 bk,5096 vb,3 st,3728 ap,34111 ec,0 dn,'neutral' bu,73 cf
    UNION ALL
    SELECT 'demo-008' k,84 o,160 bk,5229 vb,4 st,3061 ap,38569 ec,0 dn,'neutral' bu,91 cf
    UNION ALL
    SELECT 'demo-009' k,147 o,210 bk,7003 vb,5 st,3840 ap,84672 ec,0 dn,'hungry' bu,94 cf
    UNION ALL
    SELECT 'demo-010' k,125 o,228 bk,5471 vb,6 st,4100 ap,76875 ec,3 dn,'neutral' bu,74 cf
    UNION ALL
    SELECT 'demo-011' k,102 o,294 bk,3484 vb,6 st,4174 ap,63862 ec,0 dn,'neutral' bu,83 cf
    UNION ALL
    SELECT 'demo-012' k,286 o,312 bk,9153 vb,6 st,3305 ap,141785 ec,0 dn,'hungry' bu,66 cf
    UNION ALL
    SELECT 'demo-013' k,43 o,141 bk,3061 vb,3 st,4795 ap,30928 ec,1 dn,'neutral' bu,65 cf
    UNION ALL
    SELECT 'demo-014' k,107 o,200 bk,5326 vb,4 st,3820 ap,61311 ec,0 dn,'neutral' bu,65 cf
    UNION ALL
    SELECT 'demo-015' k,12 o,84 bk,1483 vb,2 st,4549 ap,8188 ec,23 dn,'slammed' bu,82 cf
    UNION ALL
    SELECT 'demo-016' k,72 o,117 bk,6114 vb,3 st,2842 ap,30694 ec,0 dn,'hungry' bu,77 cf
    UNION ALL
    SELECT 'demo-017' k,45 o,102 bk,4430 vb,2 st,4421 ap,29842 ec,1 dn,'neutral' bu,82 cf
    UNION ALL
    SELECT 'demo-018' k,102 o,164 bk,6197 vb,4 st,3719 ap,56901 ec,0 dn,'hungry' bu,69 cf
    UNION ALL
    SELECT 'demo-019' k,99 o,129 bk,7674 vb,3 st,4042 ap,60024 ec,0 dn,'hungry' bu,92 cf
    UNION ALL
    SELECT 'demo-020' k,17 o,160 bk,1033 vb,4 st,4235 ap,10799 ec,24 dn,'slammed' bu,79 cf
    UNION ALL
    SELECT 'demo-021' k,53 o,152 bk,3483 vb,4 st,3940 ap,31323 ec,1 dn,'neutral' bu,37 cf
    UNION ALL
    SELECT 'demo-022' k,60 o,300 bk,2015 vb,6 st,4352 ap,39168 ec,9 dn,'slammed' bu,79 cf
    UNION ALL
    SELECT 'demo-023' k,41 o,150 bk,2750 vb,3 st,3904 ap,24010 ec,2 dn,'neutral' bu,89 cf
    UNION ALL
    SELECT 'demo-024' k,170 o,220 bk,7708 vb,5 st,3798 ap,96849 ec,0 dn,'hungry' bu,59 cf
    UNION ALL
    SELECT 'demo-025' k,139 o,195 bk,7127 vb,5 st,3811 ap,79459 ec,0 dn,'hungry' bu,67 cf
    UNION ALL
    SELECT 'demo-026' k,28 o,184 bk,1543 vb,4 st,4425 ap,18585 ec,17 dn,'slammed' bu,88 cf
    UNION ALL
    SELECT 'demo-027' k,14 o,90 bk,1584 vb,2 st,3230 ap,6783 ec,22 dn,'slammed' bu,83 cf
    UNION ALL
    SELECT 'demo-028' k,20 o,96 bk,2124 vb,2 st,4627 ap,13881 ec,20 dn,'slammed' bu,73 cf
    UNION ALL
    SELECT 'demo-029' k,218 o,276 bk,7913 vb,6 st,4417 ap,144436 ec,0 dn,'hungry' bu,61 cf
    UNION ALL
    SELECT 'demo-030' k,46 o,152 bk,3041 vb,4 st,3157 ap,21783 ec,0 dn,'neutral' bu,61 cf
    UNION ALL
    SELECT 'demo-031' k,144 o,196 bk,7353 vb,4 st,3355 ap,72468 ec,0 dn,'hungry' bu,62 cf
    UNION ALL
    SELECT 'demo-032' k,51 o,94 bk,5436 vb,2 st,4736 ap,36230 ec,3 dn,'neutral' bu,92 cf
    UNION ALL
    SELECT 'demo-033' k,115 o,141 bk,8169 vb,3 st,4097 ap,70673 ec,0 dn,'hungry' bu,93 cf
    UNION ALL
    SELECT 'demo-034' k,60 o,135 bk,4424 vb,3 st,4058 ap,36522 ec,3 dn,'neutral' bu,79 cf
    UNION ALL
    SELECT 'demo-035' k,60 o,102 bk,5858 vb,2 st,4452 ap,40068 ec,1 dn,'neutral' bu,59 cf
    UNION ALL
    SELECT 'demo-036' k,45 o,78 bk,5769 vb,2 st,2897 ap,19555 ec,1 dn,'neutral' bu,74 cf
    UNION ALL
    SELECT 'demo-037' k,62 o,312 bk,1988 vb,6 st,3637 ap,33824 ec,21 dn,'slammed' bu,60 cf
    UNION ALL
    SELECT 'demo-038' k,104 o,196 bk,5315 vb,4 st,3129 ap,48812 ec,3 dn,'neutral' bu,35 cf
    UNION ALL
    SELECT 'demo-039' k,131 o,270 bk,4847 vb,6 st,3620 ap,71133 ec,3 dn,'neutral' bu,78 cf
    UNION ALL
    SELECT 'demo-040' k,50 o,176 bk,2863 vb,4 st,3745 ap,28088 ec,0 dn,'neutral' bu,91 cf
    UNION ALL
    SELECT 'demo-041' k,17 o,104 bk,1670 vb,2 st,4479 ap,11421 ec,15 dn,'slammed' bu,87 cf
    UNION ALL
    SELECT 'demo-042' k,55 o,252 bk,2179 vb,6 st,3878 ap,31994 ec,25 dn,'slammed' bu,67 cf
    UNION ALL
    SELECT 'demo-043' k,216 o,282 bk,7647 vb,6 st,4464 ap,144634 ec,0 dn,'hungry' bu,64 cf
    UNION ALL
    SELECT 'demo-044' k,123 o,225 bk,5447 vb,5 st,3768 ap,69520 ec,1 dn,'neutral' bu,87 cf
    UNION ALL
    SELECT 'demo-045' k,57 o,80 bk,7174 vb,2 st,3888 ap,33242 ec,0 dn,'hungry' bu,94 cf
    UNION ALL
    SELECT 'demo-046' k,96 o,176 bk,5478 vb,4 st,3551 ap,51134 ec,0 dn,'neutral' bu,64 cf
    UNION ALL
    SELECT 'demo-047' k,132 o,156 bk,8446 vb,4 st,3072 ap,60826 ec,0 dn,'hungry' bu,64 cf
  ) d ON d.k = b.platform_business_key
 WHERE b.platform_business_key LIKE 'demo-%'
ON DUPLICATE KEY UPDATE
  open_slots = VALUES(open_slots),
  bookable_slots = VALUES(bookable_slots),
  vacancy_bp = VALUES(vacancy_bp),
  empty_chair_cents = VALUES(empty_chair_cents),
  days_to_next = VALUES(days_to_next),
  bucket = VALUES(bucket),
  confidence = VALUES(confidence);
