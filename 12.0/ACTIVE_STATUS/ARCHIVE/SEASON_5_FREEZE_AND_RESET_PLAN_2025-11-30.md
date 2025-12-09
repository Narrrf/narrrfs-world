# 🚀 SEASON 5 FREEZE & RESET PLAN - NOVEMBER 30, 2025

**Date:** November 30, 2025  
**Time:** [Current Time - Season 5 Ends in <3 Hours]  
**Status:** 🚨 **URGENT - SEASON 5 ENDING TODAY**  
**Based On:** `12.0/RULES/09_RESET_SEASON_PROTOCOL_RULE.md` (v3.2)

---

## ⏰ **CRITICAL TIMING INFORMATION**

**Season 5 Ending:**
- **End Time:** [Exact time when Season 5 ends - need to verify]
- **Time Remaining:** Less than 3 hours from now
- **Action Required:** Freeze leaderboards, archive stats, prepare Season 6

**Season 6 Preparation:**
- **Duration:** 30 days (standard)
- **Season Name:** "Season 6"
- **Status:** Settings being prepared

---

## 🎯 **COMPLETE ACTION PLAN**

### **PHASE 1: PRE-FREEZE PREPARATION (NOW - BEFORE FREEZE)**

**Status:** ⏳ **READY TO EXECUTE**

#### **✅ Step 1.1: Verify Current Season 5 Status**

**Execute in Render Shell:**
```bash
# Connect to Render shell first, then run:

# 1.1.1 - Check current season status
echo "=== CURRENT SEASON 5 STATUS ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT * FROM tbl_seasons WHERE is_active = 1;"

# 1.1.2 - Check Season 5 end date
echo "=== SEASON 5 END DATE ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season_name, end_date, datetime('now') as current_time FROM tbl_seasons WHERE is_active = 1;"

# 1.1.3 - Calculate time remaining
echo "=== TIME REMAINING ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT 
  season_name,
  end_date,
  datetime('now') as current_time,
  CASE 
    WHEN datetime(end_date) > datetime('now') 
    THEN printf('%d hours, %d minutes', 
         (julianday(end_date) - julianday('now')) * 24,
         ((julianday(end_date) - julianday('now')) * 24 * 60) % 60)
    ELSE 'EXPIRED'
  END as time_remaining
FROM tbl_seasons WHERE is_active = 1;"
```

**Expected Results:**
- Current season shows "Season 5"
- End date shows exact time Season 5 ends
- Time remaining calculated

---

#### **✅ Step 1.2: Document Pre-Reset Data Counts**

**Execute in Render Shell:**
```bash
# 1.2.1 - Count Season 5 data (for archival verification)
echo "=== SEASON 5 DATA COUNTS (TO BE ARCHIVED) ===" && \
echo "Tetris scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';" && \
echo "Snake scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';" && \
echo "Space Invaders scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';" && \
echo "Total Season 5 scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');"

# 1.2.2 - Count preserved data (should remain unchanged)
echo "=== PRESERVED DATA COUNTS (MUST NOT CHANGE) ===" && \
echo "Cheese Hunt clicks:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_cheese_clicks;" && \
echo "Discord Race participants:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_race_participants;" && \
echo "Tetris achievements:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_achievements;" && \
echo "Snake achievements:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_snake_achievements;" && \
echo "Space Invaders achievements:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_space_invaders_achievements;"

# 1.2.3 - Document current state
echo "Pre-freeze data counts documented: $(date)" >> /data/season_reset_log.txt
echo "=== PRE-FREEZE COUNTS ===" >> /data/season_reset_log.txt
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';" >> /data/season_reset_log.txt
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';" >> /data/season_reset_log.txt
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';" >> /data/season_reset_log.txt
```

**Save Results:** Document all counts for verification later

---

### **PHASE 2: DATABASE BACKUP (CRITICAL - DO FIRST!)**

**Status:** ⏳ **READY TO EXECUTE**

#### **✅ Step 2.1: Create Timestamped Backup**

**Execute in Render Shell:**
```bash
# 2.1.1 - Create backup BEFORE any operations
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite

# 2.1.2 - Verify backup was created
ls -lh /data/narrrf_world_backup_*.sqlite | tail -1

# 2.1.3 - Document backup creation
echo "Database backup created BEFORE Season 5 freeze: $(date)" >> /data/season_reset_log.txt
echo "Backup file: /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite" >> /data/season_reset_log.txt
```

**Expected Results:**
- New backup file created with timestamp
- File size matches current database (~6-7 MB)
- Backup documented in log

**🚨 CRITICAL:** Do this BEFORE any other operations!

---

### **PHASE 3: ARCHIVE SEASON 5 STATS (MOST CRITICAL!)**

**Status:** ⏳ **READY TO EXECUTE**

#### **✅ Step 3.1: Archive Season 5 Historical Data**

**Execute in Render Shell:**
```bash
# 🚨 MOST CRITICAL STEP - Archive Season 5 data BEFORE deletion!
# This preserves all player gaming history for all-time statistics display

# 3.1.1 - Archive Season 5 statistics
curl https://narrrfs.world/api/admin/archive-season-stats.php

# 3.1.2 - Verify archival worked (MANDATORY!)
echo "=== ARCHIVED SEASON 5 DATA VERIFICATION ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season, game, COUNT(*) as count FROM tbl_historical_stats WHERE season = 'Season 5' GROUP BY season, game;"

# Expected output:
# Season 5|tetris|[count]
# Season 5|snake|[count]
# Season 5|space_invaders|[count]

# 3.1.3 - Document archival completion
echo "Historical stats archived for Season 5: $(date)" >> /data/season_reset_log.txt
```

**Expected Results:**
- API returns success
- Historical stats table shows Season 5 data
- All 3 games have archived records

**🚨 IF ARCHIVAL FAILS - STOP IMMEDIATELY!**
- **DO NOT PROCEED** with reset until archival works
- Fix archival issues first
- All Season 5 player history will be LOST FOREVER without this!

---

### **PHASE 4: FREEZE LEADERBOARDS (AT SEASON END TIME)**

**Status:** ⏳ **EXECUTE AT SEASON 5 END TIME**

#### **✅ Step 4.1: Leaderboard Freeze - Database Operations**

**Note:** Leaderboards are dynamically generated from `tbl_tetris_scores` table. They will automatically "freeze" when we stop accepting new scores, but we should verify the freeze mechanism.

**Execute in Render Shell at Season 5 End Time:**
```bash
# 4.1.1 - Verify Season 5 is ending/ended
echo "=== SEASON 5 END TIME CHECK ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT 
  season_name,
  end_date,
  datetime('now') as current_time,
  CASE 
    WHEN datetime(end_date) <= datetime('now') THEN 'ENDED'
    ELSE 'ACTIVE'
  END as status
FROM tbl_seasons WHERE is_active = 1;"

# 4.1.2 - Document leaderboard freeze time
echo "Leaderboard freeze initiated: $(date)" >> /data/season_reset_log.txt

# 4.1.3 - Get final leaderboard snapshot (optional - for records)
echo "=== FINAL SEASON 5 LEADERBOARD SNAPSHOT ===" && \
echo "Tetris Top 10:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT discord_id, MAX(score) as best_score FROM tbl_tetris_scores WHERE game = 'tetris' GROUP BY discord_id ORDER BY best_score DESC LIMIT 10;" && \
echo "Snake Top 10:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT discord_id, MAX(score) as best_score FROM tbl_tetris_scores WHERE game = 'snake' GROUP BY discord_id ORDER BY best_score DESC LIMIT 10;" && \
echo "Space Invaders Top 10:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT discord_id, MAX(score) as best_score FROM tbl_tetris_scores WHERE game = 'space_invaders' GROUP BY discord_id ORDER BY best_score DESC LIMIT 10;"
```

**Leaderboard Freeze Mechanism:**
- Leaderboards are dynamically generated from current scores
- When Season 5 ends and Season 6 begins, old scores won't appear in Season 6 leaderboards
- Historical data is preserved in `tbl_historical_stats` for all-time statistics
- The freeze happens automatically when season changes

**Status:** Leaderboards will freeze when Season 5 becomes inactive

---

### **PHASE 5: SEASON 5 RESET & SEASON 6 CREATION**

**Status:** ⏳ **EXECUTE AFTER ARCHIVAL**

#### **✅ Step 5.1: Execute Season Reset**

**Execute in Render Shell:**
```bash
# 5.1.1 - Execute reset in single transaction (SAFER)
sqlite3 /var/www/html/db/narrrf_world.sqlite "
BEGIN TRANSACTION;

-- Reset 3 main games only (CRITICAL: Must delete ALL old data)
DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');
DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');

-- Deactivate Season 5
UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1;

-- Create Season 6
INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) 
VALUES ('Season 6', datetime('now'), datetime('now', '+30 days'), 1);

COMMIT;
"

# 5.1.2 - Document reset execution
echo "Season 6 reset executed: $(date)" >> /data/season_reset_log.txt
```

**Expected Results:**
- No error messages
- Transaction committed successfully
- Season 5 deactivated
- Season 6 created and active

---

#### **✅ Step 5.2: Verify Reset Success**

**Execute in Render Shell:**
```bash
# 5.2.1 - Verify Season 6 is active
echo "=== NEW SEASON 6 STATUS ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT * FROM tbl_seasons WHERE is_active = 1;"

# 5.2.2 - Verify 3 main games are reset (MUST BE 0!)
echo "=== RESET VERIFICATION (MUST ALL BE 0) ===" && \
echo "Tetris scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';" && \
echo "Snake scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';" && \
echo "Space Invaders scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"

# 5.2.3 - Verify preserved data (should match Step 1.2 counts)
echo "=== PRESERVED DATA VERIFICATION ===" && \
echo "Cheese Hunt clicks:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_cheese_clicks;" && \
echo "Discord Race participants:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_race_participants;" && \
echo "Tetris achievements:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_achievements;" && \
echo "Snake achievements:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_snake_achievements;" && \
echo "Space Invaders achievements:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_space_invaders_achievements;"

# 5.2.4 - Document verification
echo "Post-reset verification completed: $(date)" >> /data/season_reset_log.txt
```

**Verification Criteria:**
- ✅ Season 6 is active (is_active = 1)
- ✅ Tetris scores = 0
- ✅ Snake scores = 0
- ✅ Space Invaders scores = 0
- ✅ All preserved data matches pre-reset counts
- ✅ No data loss!

---

#### **✅ Step 5.3: Copy Database to /DATA (CRITICAL!)**

**Execute in Render Shell:**
```bash
# 5.3.1 - Copy updated database to persistent storage
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite

# 5.3.2 - Verify copy succeeded
ls -lh /data/narrrf_world.sqlite

# 5.3.3 - Document copy
echo "Database copied to /data: $(date)" >> /data/season_reset_log.txt
echo "✅ SEASON 5 FREEZE & SEASON 6 RESET COMPLETE!"
```

**Why This Is Critical:**
- Preserves reset state for next deployment
- Render starter copies `/data/narrrf_world.sqlite` to production after deploy
- Without this, next deployment would revert to old database

---

### **PHASE 6: API & FRONTEND UPDATES (If Needed)**

**Status:** ⚠️ **REVIEW IF NEEDED**

**Note:** According to the protocol rule, we may need to update hardcoded season references in APIs and frontend. Check if these were already made dynamic:

#### **✅ Step 6.1: Check for Hardcoded Season References**

**Files to Check:**
- `api/user/user-game-missions.php` - Should use dynamic season detection
- `api/dev/save-score.php` - Check fallback season
- `api/admin/get-season-stats.php` - Check fallback
- `api/admin/get-current-season-settings.php` - Check fallback
- `api/admin/get-all-games-stats.php` - Check fallback
- `public/admin-interface.html` - Check dropdown options

**If Updates Needed:**
- Follow instructions in `09_RESET_SEASON_PROTOCOL_RULE.md` (v3.2) lines 161-280
- Update hardcoded "Season 5" → "Season 6" where applicable
- Test locally before deploying

---

### **PHASE 7: CODE DEPLOYMENT (If Code Changes Made)**

**Status:** ⏳ **READY IF NEEDED**

#### **✅ Step 7.1: Git Commit & Push**

**Execute Locally (Windows):**
```powershell
# Navigate to project directory
cd C:\xampp-server\htdocs\narrrfs-world

# Stage all changes
git add .

# Commit with descriptive message
git commit -m "feat: Season 6 Launch - Season 5 Freeze & Reset

- Season 5 ended and archived
- Historical stats preserved for Season 5
- Leaderboards frozen at season end
- Season 6 created and activated (30-day duration)
- 3 main games reset (Tetris, Snake, Space Invaders)
- All preserved data intact (Cheese Hunt, Discord Race, Achievements)
- Database backed up and copied to /data
- Season 6 settings prepared"

# Push to production
git push origin render-deploy

# Document deployment
echo "Code deployed to production: $(date)" > deployment_log.txt
```

**Status:** Only push if code changes were made

---

## 📋 **EXECUTION CHECKLIST**

### **Before Season 5 Ends (<3 Hours):**
- [ ] **Step 1.1:** Verify Season 5 status and end time
- [ ] **Step 1.2:** Document pre-reset data counts
- [ ] **Step 2.1:** Create database backup
- [ ] **Step 3.1:** Archive Season 5 historical stats
- [ ] **Verify:** All archived data is present

### **At Season 5 End Time:**
- [ ] **Step 4.1:** Freeze leaderboards (document freeze time)
- [ ] **Step 5.1:** Execute season reset (delete scores, create Season 6)
- [ ] **Step 5.2:** Verify reset success (all games = 0)
- [ ] **Step 5.3:** Copy database to /data

### **After Reset:**
- [ ] **Step 6.1:** Check if API/frontend updates needed
- [ ] **Step 7.1:** Deploy code changes (if any)
- [ ] **Test:** Verify Season 6 is active
- [ ] **Document:** Create lab note with all details

---

## 🚨 **CRITICAL REMINDERS**

### **✅ ALWAYS DO:**
- ✅ **Backup FIRST** - Always create backup before any operations
- ✅ **Archive BEFORE Delete** - Archive historical stats BEFORE reset
- ✅ **Verify Each Step** - Check results before proceeding
- ✅ **Copy to /data** - Always copy database to /data after reset
- ✅ **Document Everything** - Complete audit trail in log file

### **❌ NEVER DO:**
- ❌ **Reset without backup** - Always backup first!
- ❌ **Reset preserved data** - Never touch Cheese Hunt, Discord Race, or achievements
- ❌ **Skip archival** - Always archive before deletion
- ❌ **Skip verification** - Always verify before and after reset
- ❌ **Forget /data copy** - Always copy database to /data

---

## 📊 **DATA PRESERVATION RULES**

### **✅ ALWAYS RESET (3 Main Games):**
- **`tbl_tetris_scores`** - Tetris, Snake, Space Invaders scores
- **`tbl_user_season_achievements`** - Season achievements for 3 main games

### **🚨 NEVER RESET (Critical Data):**
- **`tbl_cheese_clicks`** - Cheese Hunt data (PRESERVE ALWAYS)
- **`tbl_race_participants`** - Discord Race data (PRESERVE ALWAYS)
- **`tbl_tetris_achievements`** - Individual Tetris achievements (PRESERVE ALWAYS)
- **`tbl_snake_achievements`** - Individual Snake achievements (PRESERVE ALWAYS)
- **`tbl_space_invaders_achievements`** - Individual Space Invaders achievements (PRESERVE ALWAYS)

---

## 🎯 **SUCCESS CRITERIA**

### **✅ Technical Success:**
- ✅ Zero data loss (all critical data preserved)
- ✅ Clean reset (3 main games = 0 records)
- ✅ Season 6 activated (is_active = 1)
- ✅ Historical data archived (Season 5 preserved)
- ✅ System integrity (no broken functionality)

### **✅ Operational Success:**
- ✅ Professional process (all steps followed)
- ✅ Complete documentation (full audit trail)
- ✅ Risk mitigation (backup + verification)
- ✅ Future readiness (Season 6 ready to go!)
- ✅ Leaderboards frozen correctly

---

## ⏰ **TIMELINE ESTIMATE**

**Total Time:** ~30-45 minutes

- **Pre-Freeze (Steps 1-3):** 15 minutes
- **Freeze & Reset (Steps 4-5):** 10 minutes
- **Verification:** 5 minutes
- **Code Deployment (if needed):** 10 minutes
- **Documentation:** 5 minutes

---

## 🚨 **EMERGENCY ROLLBACK**

### **If Reset Fails:**

**Execute in Render Shell:**
```bash
# Use most recent backup
cp /data/narrrf_world_backup_[TIMESTAMP].sqlite /var/www/html/db/narrrf_world.sqlite

# Verify rollback
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT * FROM tbl_seasons WHERE is_active = 1;"
# Should show Season 5 active

# Document rollback
echo "Emergency rollback executed: $(date)" >> /data/season_reset_log.txt
```

---

## 📝 **LAB NOTE TEMPLATE**

### **After Reset, Create Lab Note:**

```markdown
# 🚀 SEASON 5 FREEZE & SEASON 6 RESET - NOVEMBER 30, 2025

## Pre-Reset State:
- Season 5 End Time: [Exact time]
- Tetris Scores: [count]
- Snake Scores: [count]
- Space Invaders Scores: [count]
- Total: [total count]

## Execution:
- Backup Created: [timestamp]
- Historical Stats Archived: [verified]
- Leaderboards Frozen: [timestamp]
- Reset Executed: [timestamp]
- Season 6 Created: [verified]

## Verification:
- Season 6 Active: ✅
- Tetris Reset: 0 ✅
- Snake Reset: 0 ✅
- Space Invaders Reset: 0 ✅
- Preserved Data: [all counts match] ✅

## Deployment:
- Code Changes: [if any]
- Commit: [hash if deployed]
- Status: Success ✅
```

---

## 🎯 **QUICK REFERENCE COMMANDS**

### **One-Command Verification (Copy/Paste):**
```bash
echo "Current Season:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season_name FROM tbl_seasons WHERE is_active = 1;" && \
echo "Time Remaining:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT printf('%d hours, %d minutes', (julianday(end_date) - julianday('now')) * 24, ((julianday(end_date) - julianday('now')) * 24 * 60) % 60) FROM tbl_seasons WHERE is_active = 1;" && \
echo "Tetris Scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';" && \
echo "Snake Scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';" && \
echo "Space Invaders Scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"
```

---

## 🚀 **READY TO EXECUTE!**

**Status:** ✅ **PLAN COMPLETE - READY FOR EXECUTION**

**Next Steps:**
1. Execute Phase 1 (Pre-Freeze Preparation) NOW
2. Execute Phase 2 (Database Backup) BEFORE anything else
3. Execute Phase 3 (Archive Stats) BEFORE reset
4. Execute Phase 4 (Freeze) at season end time
5. Execute Phase 5 (Reset) immediately after freeze
6. Execute Phase 6-7 (Updates & Deployment) if needed

**Remember:** Archive BEFORE delete, backup BEFORE everything!

---

**Plan Created:** November 30, 2025  
**Status:** ✅ **READY FOR SEASON 5 FREEZE & SEASON 6 RESET**  
**Based On:** `12.0/RULES/09_RESET_SEASON_PROTOCOL_RULE.md` (v3.2)  
**Timeline:** <3 hours until Season 5 ends

**🚀 THIS PLAN ENSURES ZERO DATA LOSS AND PROFESSIONAL SEASON TRANSITION! 🚀**

