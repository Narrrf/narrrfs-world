# 🎯 Player Profile Season Statistics Tracking

**Author:** Cursor LLM • **Date:** 2025-11-07 • **Scope:** Season 5 profile portal (Tetris, Snake, Space Invaders, Cheese Hunt, Discord Cheese Race)

---

## 1. Purpose
The profile portal shows “Current Season Statistics” for all five games. This document explains how the backend assembles those numbers, how season scoping works, and what fallbacks exist when legacy (Season <5) or untagged data is encountered.

Primary reference implementation: `api/user/user-game-missions.php` (lines ≈200-420).

---

## 2. Data Sources & Conversions
| Game | Table | Identifier | Score Stored As | DSPOINC Conversion |
|------|-------|------------|-----------------|--------------------|
| Tetris | `tbl_tetris_scores` | `discord_id` | DSPOINC already | `total_score` (no multiplier) |
| Snake | `tbl_tetris_scores` | `discord_id` | Raw score (games × base 10) | `total_score * 10` |
| Space Invaders | `tbl_tetris_scores` | `discord_id` | Raw invader points | `total_score * 0.1` |
| Cheese Hunt | `tbl_cheese_clicks` | `user_wallet` (Discord ID) | Click count | `total_clicks * 10` |
| Discord Cheese Race | `tbl_race_participants` | `user_id` | DSPOINC earned per race | `SUM(dspoinc_earned)` |

> **Note:** Cheese Hunt & Discord Race cards show *season-only* data. If a player has zero Season 5 entries, the profile shows 0 instead of lifetime totals.

*Totals feed both the individual card metrics and the “Overall DSPOINC” summary.*

---

## 3. Season Filtering Strategy
To avoid mixing historic runs with Season 5, every query now follows a **three-pass** strategy:

1. **Exact match:** `season = 'Season 5'`
2. **Prefix match:** `season LIKE 'Season 5%'` (covers variations such as `Season 5 – Weekly Finals`)
3. **All-time fallback:** no season filter (captures legacy records with `NULL` / empty season columns)

### 3.1 Applied Games
| Game | Query Fields | Notes |
|------|--------------|-------|
| Tetris | `COUNT`, `MAX`, `SUM`, `MAX(timestamp)` | Uses DSPOINC totals directly with season-first logic + career fallback |
| Snake | Same structure | Adds `*10` DSPOINC conversion (season-first with fallback) |
| Space Invaders | Same structure | Adds `*0.1` DSPOINC conversion (season-first with fallback) |
| Cheese Hunt | Same structure against `user_wallet` | Season-only (no fallback) |
| Discord Race | Same structure + wins/podium aggregation | Season-only (no fallback) |

*Season matching stops at the first pass that returns rows; for Cheese Hunt/Race, failure leaves the card at 0 to mirror the admin view. All queries also accept `timestamp >= season_start` (with optional end) as a safety net while legacy season labels are phased out.*

---

## 4. Response Payload (per game)
```json
{
  "total_games": 57,
  "best_score": 915,
  "total_score": 2020,
  "last_played": "2025-11-07 01:33:22",
  "dspoinc_earned": 202,
  "achievements": {
    "total_available": 28,
    "unlocked": 12,
    "completion_percentage": 42.9
  }
}
```

*Cheese Hunt and Discord Race expose analogous fields (`total_clicks`, `total_races`, etc.).*

---

## 5. Fallback & Debug Logging
- Each query emits a ✅ log when the season-scoped pass succeeds.
- If no Season 5 rows are found, a ⚠️ log marks the all-time fallback.
- Cheese Hunt adds additional logs when wallet lookup is required.
- Discord Race prints a breakdown of race statuses (`joined`, `completed`, etc.) after aggregation.

> Check the PHP error log (`storage/logs/php-error.log` on Render) when validating new seasons.

---

## 6. Maintenance Checklist
1. **Season rollover:** Update `$currentSeason` constant in `user-game-missions.php` (`Season 6`, etc.).
2. **Archive rules:** Ensure the new season name follows the same prefix style if suffixes ("Week 1") are used.
3. **Database prep:** Confirm `tbl_tetris_scores`, `tbl_cheese_clicks`, and `tbl_race_participants` include the new season label in live data collectors.
4. **QA checklist:**
   - Play at least one run in each game after reset.
   - Verify profile cards update without fallback logs.
   - Confirm DSPOINC totals align with admin “Season Overview.”

---

## 7. Testing Commands
```powershell
# Inspect race participation by season
db\narrrf_world.sqlite "SELECT season, COUNT(*) FROM tbl_race_participants GROUP BY season;"

# Inspect cheese clicks
sqlite3 db\narrrf_world.sqlite "SELECT season, COUNT(*) FROM tbl_cheese_clicks GROUP BY season;"
```

---

## 8. Known Edge Cases
- **Legacy Season Entries:** Old rows without season labels automatically roll into fallback totals. Consider backfilling season columns if we ever want “current only” stats with no fallback.
- **Wallet mismatch:** If a player changes their Discord wallet, legacy clicks tied to the old wallet stay in fallback totals until we migrate the data.
- **Admin imports:** Manual score adjustments must populate the `season` column to appear in Season cards.

---

## 9. Future Enhancements
- Surface both “Season” and “Career” tabs on the profile portal instead of hiding fallback totals.
- Move repeated aggregation logic into helper functions or SQL views to reduce duplication across games.
- Add automated checks that warn when a season reset occurs but no Season 6 data is detected after X hours.

---

## 10. Current Season Detection
- Pulls active record from `tbl_seasons` (`is_active = 1`).
- Uses `start_date` / `end_date` to determine the timestamp window (`>= start`, `< end` when provided).
- Fallback when no record is active: assumes 30-day window ending now (prevents empty dashboards during testing).

---

*End of document.*

- Deployment reminder: after Season 5 DB cleanup, remove the timestamp fallback to enforce strict `season` labels.
