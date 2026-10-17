-- oc_011_radar_adapters.sql — schedule the first two adapters, in SHADOW.
--
-- docs/70-radar-build.md. Booksy and Square Appointments now have adapters in
-- openchair-worker, tested against fixture pages. oc_007 parks every platform
-- in `research`, which the scheduler never claims (scan/queue.js claim()). This
-- moves exactly those two to `shadow`:
--
--   shadow — scans run and are stored, the canary watches them, but the
--            adapter is not trusted for LIVE reads. The worker's own switch
--            (OC_READ_MODE=fixture by default, OC_ALLOW_LIVE=1 required for the
--            broker) keeps every read on recorded pages until the spike runs.
--
-- It does NOT move anything to `live`. That is the founder's call after the
-- spike replaces the synthetic fixtures with real recordings (docs/70 §4), and
-- it is made by hand, with adapter_version stamped, not by a migration.
--
-- Only rows still in `research` are touched, so a platform the canary has
-- `frozen` stays frozen, and re-running this file is a no-op.
--
-- Idempotent: safe to re-run.

UPDATE `oc_platform`
   SET `rollout`            = 'shadow',
       `rollout_changed_at` = NOW(),
       `rollout_note`       = 'oc_011: adapter built and fixture-tested; synthetic fixtures — record real pages in the spike before live'
 WHERE `platform_key` IN ('booksy', 'square_appointments')
   AND `rollout` = 'research';
