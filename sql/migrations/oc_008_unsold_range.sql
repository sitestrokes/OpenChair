-- oc_008_unsold_range.sql — store BOTH ends of the Unsold Value range.
--
-- 05-prd.md §8 rule 4: always a range, never a point estimate. The schema only
-- had `empty_chair_cents`, one column, so the display layer was DERIVING the
-- high end by scaling the low one. That approximation is wrong, and it is wrong
-- on the single number this product is judged by.
--
-- The model's range width is not a fixed multiple. From 35-scoring-model.md §3.4
-- the band is openMinutes × (1 ± rho) crossed with the price band's p25..p75,
-- where rho = 0.05 + 0.45 × (1 − confidence). So the range WIDENS as confidence
-- falls — which is the whole point of it — and no constant ratio can reproduce
-- that from the low end alone. A confident business and a shaky one would have
-- been shown ranges of identical relative width.
--
-- Both ends are rounded DOWN to the nearest $50 by the model: we never overstate.
--
-- Naming: the stored pair is UNSOLD VALUE (openMinutes × price band) — the
-- headline. Recoverable Revenue (unsold_low × 15–35% sell-through) sizes the
-- offer and is deliberately NOT stored: it is derived at render time from the
-- tenant's own sell-through assumption, and freezing one operator's assumption
-- into the shared global scan layer would be wrong.
--
-- Idempotent: safe to re-run.

SET @ddl := (
  SELECT IF(COUNT(*) > 0,
    'SELECT ''oc_vacancy_daily.empty_chair_hi_cents already present''',
    'ALTER TABLE `oc_vacancy_daily`
       ADD COLUMN `empty_chair_hi_cents` int(11) NOT NULL DEFAULT 0
         COMMENT ''high end of the Unsold Value range; low end is empty_chair_cents''
         AFTER `empty_chair_cents`')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_vacancy_daily'
    AND COLUMN_NAME = 'empty_chair_hi_cents'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl := (
  SELECT IF(COUNT(*) > 0,
    'SELECT ''oc_business.latest_empty_chair_hi_cents already present''',
    'ALTER TABLE `oc_business`
       ADD COLUMN `latest_empty_chair_hi_cents` int(11) DEFAULT NULL
         COMMENT ''high end of the latest Unsold Value range''
         AFTER `latest_empty_chair_cents`')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_business'
    AND COLUMN_NAME = 'latest_empty_chair_hi_cents'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Back-fill existing rows with the ratio the display layer had been assuming
-- (high/low = 0.35/0.15), so nothing renders as a zero-width range in the gap
-- between this migration and the next scan. These are approximations by
-- definition and are overwritten the first time the Meter scores the business.
UPDATE `oc_vacancy_daily`
   SET `empty_chair_hi_cents` = ROUND(`empty_chair_cents` * 2.3333)
 WHERE `empty_chair_hi_cents` = 0 AND `empty_chair_cents` > 0;

UPDATE `oc_business`
   SET `latest_empty_chair_hi_cents` = ROUND(`latest_empty_chair_cents` * 2.3333)
 WHERE `latest_empty_chair_hi_cents` IS NULL AND `latest_empty_chair_cents` > 0;
