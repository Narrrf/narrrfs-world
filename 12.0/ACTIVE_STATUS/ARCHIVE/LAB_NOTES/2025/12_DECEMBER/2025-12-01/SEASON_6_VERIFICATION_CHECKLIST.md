# ✅ SEASON 6 VERIFICATION CHECKLIST — DECEMBER 1, 2025

**Date:** December 1, 2025  
**Purpose:** Verify Season 5 → Season 6 reset executed correctly  
**Status:** 🔄 **IN PROGRESS**

---

## 📋 **VERIFICATION CHECKLIST**

### **1. Database Verification**

#### **Season Status:**
- [ ] Season 6 is active in `tbl_seasons` table
  - Query: `SELECT * FROM tbl_seasons WHERE is_active = 1;`
  - Expected: Season 6, start: 2025-11-30 23:01:45, end: 2025-12-30 23:01:45
- [ ] Season 5 is inactive
  - Query: `SELECT * FROM tbl_seasons WHERE season_name = 'Season 5';`
  - Expected: `is_active = 0`

#### **Game Reset Verification:**
- [ ] Tetris scores reset to 0
  - Query: `SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';`
  - Expected: `0`
- [ ] Snake scores reset to 0
  - Query: `SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';`
  - Expected: `0`
- [ ] Space Invaders scores reset to 0
  - Query: `SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';`
  - Expected: `0`
- [ ] User season achievements reset
  - Query: `SELECT COUNT(*) FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');`
  - Expected: `0`

#### **Historical Stats Verification:**
- [ ] Season 5 historical stats archived
  - Query: `SELECT season, game, COUNT(*) FROM tbl_historical_stats WHERE season = 'Season 5' GROUP BY season, game;`
  - Expected: Season 5|tetris|16, Season 5|snake|17, Season 5|space_invaders|15
- [ ] Cheese users archived
  - Query: `SELECT COUNT(*) FROM tbl_historical_stats WHERE season = 'Season 5' AND game IS NULL;`
  - Expected: 37 or more

#### **Preserved Data Verification:**
- [ ] Cheese Hunt data intact
  - Query: `SELECT COUNT(*) FROM tbl_cheese_clicks;`
  - Expected: 1597 (or close to this value)
- [ ] Discord Race data intact
  - Query: `SELECT COUNT(*) FROM tbl_race_participants;`
  - Expected: 821 (or close to this value)
- [ ] Achievements preserved
  - Query: `SELECT COUNT(*) FROM tbl_tetris_achievements;` (should still exist)
  - Query: `SELECT COUNT(*) FROM tbl_snake_achievements;` (should still exist)
  - Query: `SELECT COUNT(*) FROM tbl_space_invaders_achievements;` (should still exist)

---

### **2. Admin Menu Verification**

- [ ] Admin interface loads without errors
- [ ] Season dropdown shows Season 6
- [ ] Current season settings show Season 6
- [ ] Stats display correctly for Season 6 (empty initially)
- [ ] No old Season 5 data in current season views
- [ ] Historical stats view shows Season 5 archived data

---

### **3. Frontend Verification**

#### **Profile Page:**
- [ ] Frozen leaderboard displays Season 5 scores
- [ ] Header shows "⏸️ Season 5 Leaderboard - FREEZED"
- [ ] Subtitle shows "Season 6 Starting Soon"
- [ ] All three games show Season 5 frozen scores
- [ ] No empty leaderboards

#### **Index Page:**
- [ ] Season 6 messaging displays correctly
- [ ] Christmas theme active
- [ ] Snowflake effects working

#### **All-Time Stats:**
- [ ] Historical data still displays on profile
- [ ] Season 5 data appears in historical view
- [ ] No errors loading all-time statistics

---

### **4. API Verification**

#### **Leaderboard API:**
- [ ] API returns `is_frozen: true`
- [ ] API returns `display_season: "Season 5"`
- [ ] API returns `current_season: "Season 6"`
- [ ] Leaderboard data shows Season 5 scores from historical stats

#### **Score Saving API:**
- [ ] Scores save correctly with Season 6
- [ ] Season field set correctly in database

#### **Mission Status API:**
- [ ] Shows Season 6 correctly
- [ ] No old Season 5 data in current season queries

---

### **5. Automatic Switch Verification**

- [ ] When Season 6 has 3+ scores, leaderboard automatically switches
- [ ] Header updates from "FREEZED" to "Live Rankings"
- [ ] Subtitle updates to show Season 6 as active
- [ ] No manual intervention required

---

## 📝 **VERIFICATION NOTES**

**Date/Time:** _________________

**Database Path:** `db/narrrf_world.sqlite` (downloaded from live server)

**Issues Found:**
- 
- 
- 

**Notes:**
- 
- 
- 

---

## ✅ **VERIFICATION RESULTS**

- [ ] **All Database Checks Passed**
- [ ] **All Admin Menu Checks Passed**
- [ ] **All Frontend Checks Passed**
- [ ] **All API Checks Passed**
- [ ] **Automatic Switch Tested**

**Overall Status:** ⬜ Pass ⬜ Fail ⬜ Needs Fixes

**Verified By:** _________________  
**Date:** December 1, 2025

---

**Last Updated:** December 1, 2025

