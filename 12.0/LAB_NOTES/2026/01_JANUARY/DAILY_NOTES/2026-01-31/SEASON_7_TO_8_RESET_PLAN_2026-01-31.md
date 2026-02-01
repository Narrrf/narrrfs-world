# 🚀 Season 7 → Season 8 Reset Plan – January 31, 2026

**Created:** January 31, 2026  
**Time Available:** ~1h 30 min until snapshot (updated)  
**Rule Reference:** `12.0/RULES/09_RESET_SEASON_PROTOCOL_RULE.md`  
**Status:** ✅ **EXECUTION COMPLETE – Ready to push**

---

## 🚀 **WHAT TO DO NEXT (1h 30 min – PRIORITIZED)**

| Step | Task | Est. Time | Status |
|------|------|-----------|--------|
| **1** | **profile.html** – Season 7 → Season 8 (12+ locations) | 15 min | ✅ Done |
| **2** | **project-updates.html** – Season 7 → Season 8 | 5 min | ✅ Done |
| **3** | **admin-interface.html** – Add Season 8, update displays | 10 min | ✅ Done |
| **4** | **leaderboard.html** – Fallback Season 7 → Season 8 | 5 min | ✅ Done |
| **5** | **4 API fallbacks** – Season 7 → Season 8 | 5 min | ⬜ |
| **6** | **Local smoke test** – index, profile, leaderboard, mint | 5 min | ⬜ |
| **7** | **Git add, commit, push** to `render-deploy` | 5 min | ⬜ |
| **8** | **Snapshot window** – wait for freeze | — | — |
| **9** | **On Render:** Archive + DB reset + copy to /data | 15 min | ⬜ |

**Total prep:** ~55 min. **Buffer:** ~35 min for issues.

---

## ⏱️ **TIMELINE OVERVIEW**

| Phase | Time | Action |
|-------|------|--------|
| **A** | Now – Snapshot | Pre-reset prep (local DB verified, frontend ready) |
| **B** | Snapshot | Freeze leaderboards (Season 7 final state) |
| **C** | After Snapshot | Database reset + deploy |

---

## 📋 **PHASE A: PRE-SNAPSHOT PREP (~30–45 min)**

### Step A1: Verify Local DB (5 min)

- [ ] Confirm local DB exists: `db/narrrf_world.sqlite`
- [ ] Verify current season: `SELECT * FROM tbl_seasons WHERE is_active = 1;`
- [ ] Document pre-reset counts (optional):

```powershell
# PowerShell (local)
cd C:\xampp-server\htdocs\narrrfs-world
sqlite3 db/narrrf_world.sqlite "SELECT * FROM tbl_seasons WHERE is_active = 1;"
sqlite3 db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';"
sqlite3 db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';"
sqlite3 db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"
```

### Step A2: Update Frontend for Season 8 (~25–30 min)

#### A2.1 – `public/index.html` (8+ locations) ✅ **DONE 2026-01-31**

- [x] **Meta description** (line ~11): `Season 7` → `Season 8 STARTED`
- [x] **Meta keywords** (line ~14): `Season 7` → `Season 8`
- [x] **Title** (line ~41): `Season 7 RUNNING` → `Season 8 STARTED`
- [x] **og:title** (line ~42): `Season 7` → `Season 8 STARTED`
- [x] **og:description** (line ~43): `Season 7` → `Season 8 STARTED`
- [x] **twitter:title** (line ~46): `Season 7` → `Season 8 STARTED`
- [x] **twitter:description** (line ~47): `Season 7` → `Season 8 STARTED`
- [x] Banner, CTA, hero section: `Season 7` → `Season 8 STARTED`
- [x] **TODO added:** Mint price updates (0.45 SOL) – confirm for Season 8

#### A2.2 – `public/profile.html` (12+ locations) ✅ **DONE 2026-01-31**

- [x] **Title** (line ~14): `Season 7 RUNNING` → `Season 8 STARTED`
- [ ] Season status banner text
- [ ] Wallet & Traits banner
- [ ] Mint section status
- [ ] Store banner
- [ ] Leaderboard header
- [ ] JS fallbacks (search for `Season 7`)

#### A2.3 – `public/project-updates.html`

- [ ] Search for `Season 7` and update to `Season 8`
- [ ] Events calendar if season-specific

#### A2.4 – `public/admin-interface.html` ✅ **DONE 2026-01-31**

- [x] Add `Season 8` to season dropdowns: `seasonSwitchSelector`, `viewSeasonSelector`
- [ ] `currentSeasonDisplay`: `Season 7` → `Season 8`
- [ ] `overviewSeasonName`: `Season 7` → `Season 8`
- [ ] JS fallbacks in `updateSeasonDisplay()` and `overviewSeasonTimeLeft`: `Season 7` → `Season 8`

#### A2.5 – `public/leaderboard.html`

- [ ] Check if any hardcoded `Season 7`; rule says it’s dynamic, verify and fix if needed

### Step A3: Update API Fallbacks (~5 min)

- [ ] **`api/admin/get-current-season-settings.php`** (line ~19): `'Season 7'` → `'Season 8'`
- [ ] **`api/dev/save-score.php`** (line ~107): `'Season 7'` → `'Season 8'`
- [ ] **`api/admin/get-all-games-stats.php`** (line ~35): `'Season 7'` → `'Season 8'`
- [ ] **`api/admin/get-season-stats.php`** (line ~44): `'Season 7'` → `'Season 8'`

---

## 📋 **PHASE B: SNAPSHOT (Leaderboard Freeze)**

### Step B1: Backup Database (CRITICAL)

**On Render (production):**

```bash
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite
```

**Locally (already done):**

- [ ] Live DB downloaded to local (already completed)

### Step B2: Document Final Season 7 State

- [ ] Run archival API to freeze leaderboard data (if needed before reset):

```bash
curl https://narrrfs.world/api/admin/archive-season-stats.php
```

**Note:** Archival is normally run **before** the database reset (Phase C). Do not delete data before archiving.

---

## 📋 **PHASE C: POST-SNAPSHOT – DATABASE RESET (On Render)**

### Step C1: Archive Historical Stats (CRITICAL – Before Delete)

```bash
curl https://narrrfs.world/api/admin/archive-season-stats.php
```

Verify archival:

```bash
echo "SELECT season, game, COUNT(*) FROM tbl_historical_stats WHERE season = 'Season 7' GROUP BY season, game;" | sqlite3 /var/www/html/db/narrrf_world.sqlite
```

### Step C2: Database Reset

```sql
BEGIN TRANSACTION;
DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');
DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');
UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1;
INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) 
VALUES ('Season 8', datetime('now'), datetime('now', '+30 days'), 1);
COMMIT;
```

### Step C3: Copy DB to /data (Render)

```bash
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite
```

### Step C4: Verification

- [ ] New season active: `SELECT * FROM tbl_seasons WHERE is_active = 1;`
- [ ] Tetris/Snake/Space Invaders counts = 0
- [ ] Cheese Hunt, Discord Race, achievements preserved

---

## 📁 **FILES TO MODIFY (CHECKLIST)**

| # | File | Change | Status |
|---|------|--------|--------|
| 1 | `public/index.html` | Season 7 → Season 8 STARTED (8+ locations) + TODO mint price | ✅ Done |
| 2 | `public/profile.html` | Season 7 → Season 8 (12+ locations) | ✅ Done |
| 3 | `public/project-updates.html` | Season 7 → Season 8 | ✅ Done |
| 4 | `public/admin-interface.html` | Add Season 8 to dropdowns, update displays | ✅ Done |
| 5 | `public/leaderboard.html` | Fallback Season 7 → Season 8 | ✅ Done |
| 5b | `public/mint.html` | Season 6 → Season 8 STARTED + TODO mint prices | ✅ Done |
| 6 | `api/admin/get-current-season-settings.php` | Fallback: Season 7 → Season 8 |
| 7 | `api/dev/save-score.php` | Fallback: Season 7 → Season 8 |
| 8 | `api/admin/get-all-games-stats.php` | Fallback: Season 7 → Season 8 |
| 9 | `api/admin/get-season-stats.php` | Fallback: Season 7 → Season 8 |

---

## 🚨 **HARD CONSTRAINTS (From Rule)**

### ❌ DO NOT

- Reset without backup
- Reset `tbl_cheese_clicks`, `tbl_race_participants`, `tbl_rumble_participants`, achievements
- Skip archival before delete
- Deploy without local testing
- Forget `cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite` on Render

### ✅ DO

- Archive before delete
- Backup before reset
- Test locally before deploy
- Clear browser cache after updates (CTRL+SHIFT+R)
- Push to `render-deploy` branch

---

## 📝 **MINT PRICES (Season 8) – UPDATED 2026-01-31**

**Implemented pricing:**
- **Holders:** 0.15 SOL (Follows per Sheet)
- **Early Bird:** 0.175 SOL (Follows per Sheet)
- **Partners:** 0.20 SOL
- **Redemption Mint:** 0.3999 SOL (endless running)

**index.html:** New Year banner + hero CTA area + Mint Now (0.15–0.3999 SOL)
**mint.html:** Tier cards in Pricing & Supply section

---

## 📝 **QUICK REFERENCE – SEARCH & REPLACE**

```text
Search:  Season 7
Replace: Season 8

Search:  season 7
Replace: season 8

Search:  season7
Replace: season8
```

**Caution:** Check context (e.g. historical references) before replacing.

---

## 🎯 **1h 30 MIN EXECUTION ORDER (UPDATED)**

1. **0:00–0:15** – Update frontend: profile, project-updates, admin-interface, leaderboard (index + mint already done)
2. **0:15–0:20** – Update API fallbacks (4 PHP files)
3. **0:20–0:25** – Local smoke test (index, profile, leaderboard, mint)
4. **0:25–0:30** – Git add, commit, push to `render-deploy`
5. **0:30–1:30** – Snapshot window; after snapshot: Archive + DB reset on Render + copy to /data

---

**Status:** Plan ready. Proceed step by step and confirm each phase before continuing.
