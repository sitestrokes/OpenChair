-- oc_demo_tampa_weekly.sql — 8 weeks of rollups, plus a few shops without online
-- booking, for the demo market. Gives the signature visuals
-- (docs/60-signature-visuals.md) a trend to draw and "?" pins to place before
-- any crawl has run.
--
-- DEMO DATA. Every row hangs off a 'demo-%' business or carries a 'demo-' ref;
-- nothing here was observed. The newest week is pinned to each business's
-- latest_vacancy_bp, so the 8-week report's last point and the Chair Score on
-- every other screen agree.
--
-- Shape: the emptiest shops were emptier eight weeks ago and have been filling
-- (the "after the Tue/Wed offer" story the 8-week report tells); everyone else
-- wanders within a couple of points. The newest week has 5 scan days, the rest 7.
--
-- Idempotent: safe to re-run (uq_vw_biz_week, uq_cand_source).

INSERT INTO `oc_vacancy_weekly`
  (business_id, week_start, scan_days, avg_vacancy_bp, fill_rate_bp,
   avg_open_slots, avg_bookable_slots, avg_empty_chair_cents, avg_days_to_next, dominant_bucket)
SELECT x.business_id, x.week_start, x.scan_days, x.v, 10000 - x.v,
       x.open_slots, x.bookable, x.ecr, x.dtn,
       CASE WHEN 100 - ROUND(x.v / 100) >= 85 THEN 'slammed'
            WHEN 100 - ROUND(x.v / 100) <  50 THEN 'hungry'
            ELSE 'neutral' END
  FROM (
    SELECT b.id AS business_id,
           DATE_SUB(DATE_SUB(b.latest_observed_on, INTERVAL WEEKDAY(b.latest_observed_on) DAY), INTERVAL w.n WEEK) AS week_start,
           IF(w.n = 0, 5, 7) AS scan_days,
           LEAST(9800, GREATEST(200,
             b.latest_vacancy_bp
             + w.n * IF(b.latest_vacancy_bp >= 3700, LEAST(300, FLOOR((9300 - b.latest_vacancy_bp) / 8)), 30)
             + IF(w.n = 0, 0, ((b.id * 37 + w.n * 53) % 360) - 180)
           )) AS v,
           b.latest_open_slots AS open_slots, b.latest_bookable_slots AS bookable,
           b.latest_empty_chair_cents AS ecr, b.latest_days_to_next AS dtn
      FROM `oc_business` b
      JOIN (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3
            UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7) w
     WHERE b.platform_business_key LIKE 'demo-%' AND b.latest_observed_on IS NOT NULL
  ) x
ON DUPLICATE KEY UPDATE
  scan_days = VALUES(scan_days), avg_vacancy_bp = VALUES(avg_vacancy_bp),
  fill_rate_bp = VALUES(fill_rate_bp), dominant_bucket = VALUES(dominant_bucket);

-- Shops discovery found that take no online booking: the dashed "?" pins on the
-- Chair Map ("7 of 9 shops visible"). Placed near demo businesses on purpose.
INSERT INTO `oc_discovery_candidate`
  (source, source_ref, city_key, niche_key, name, address_line, lat, lng, status, detect_confidence)
VALUES
('manual','demo-nb-001','us-fl-tampa','barbershop','(demo) walk-in shop 1','N Florida Ave',27.9612,-82.4781,'not_bookable',90),
('manual','demo-nb-002','us-fl-tampa','barbershop','(demo) walk-in shop 2','N Florida Ave',27.9534,-82.4840,'not_bookable',90),
('manual','demo-nb-003','us-fl-tampa','barbershop','(demo) walk-in shop 3','E 7th Ave',27.9876,-82.4012,'not_bookable',90),
('manual','demo-nb-004','us-fl-tampa','barbershop','(demo) walk-in shop 4','W Kennedy Blvd',27.9452,-82.5230,'not_bookable',90),
('manual','demo-nb-005','us-fl-tampa','barbershop','(demo) walk-in shop 5','S Howard Ave',27.9440,-82.4570,'not_bookable',90),
('manual','demo-nb-006','us-fl-tampa','barbershop','(demo) walk-in shop 6','N Nebraska Ave',27.9700,-82.4150,'not_bookable',90)
ON DUPLICATE KEY UPDATE status = VALUES(status), lat = VALUES(lat), lng = VALUES(lng);
