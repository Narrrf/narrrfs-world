# 🔄 SEASON 5 RESET - COMPLETE CHECKLIST

**Date:** November 1, 2025  
**Purpose:** Step-by-step guide for Season 5 reset  
**Based On:** `12.0/RULES/09_RESET_SEASON_PROTOCOL_RULE.md` (v2.0)  
**Status:** 🔄 **READY TO EXECUTE**  

---

## 🚨 CRITICAL: FOLLOW THIS ORDER EXACTLY

**NEVER skip steps. ALWAYS verify each step before proceeding.**

---

## ✅ STEP 1: PRE-RESET VERIFICATION (Render Shell)

### **Commands:**
```bash
# Connect to Render shell first, then run:

# 1.1 - Verify current season status
echo "=== CURRENT SEASON ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT * FROM tbl_seasons WHERE is_active = 1;"

# 1.2 - Count existing data (DOCUMENT THESE!)
echo "=== PRE-RESET DATA COUNTS ===" && \
echo "Tetris scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';" && \
echo "Snake scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';" && \
echo "Space Invaders scores:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';" && \
echo "Cheese clicks:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_cheese_clicks;" && \
echo "Race participants:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_race_participants;" && \
echo "Tetris achievements:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_achievements;" && \
echo "Snake achievements:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_snake_achievements;" && \
echo "Space Invaders achievements:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_space_invaders_achievements;"

# 1.3 - Document pre-reset state
echo "Pre-reset verification completed: $(date)" >> /data/season_reset_log.txt
```

**Expected Results:**
- Current season shows (should be "Season 4")
- Data counts documented for verification

---

## ✅ STEP 2: DATABASE BACKUP (CRITICAL!)

### **Commands:**
```bash
# 2.1 - Create timestamped backup
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite

# 2.2 - Verify backup was created
ls -lh /data/narrrf_world_backup_* | tail -1

# 2.3 - Document backup
echo "Database backup created: $(date)" >> /data/season_reset_log.txt
```

**Expected Results:**
- New backup file in `/data/` with timestamp
- File size ~6-7 MB (should match current database)

---

## ✅ STEP 3: ARCHIVE HISTORICAL STATS (CRITICAL v2.0!)

### **Commands:**
```bash
# 🚨 MOST CRITICAL STEP - Archive Season 4 data BEFORE deletion!

# 3.1 - Archive Season 4 statistics
curl https://narrrfs.world/api/admin/archive-season-stats.php

# 3.2 - Verify archival worked (MANDATORY)
echo "=== ARCHIVED SEASON 4 DATA ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season, game, COUNT(*) FROM tbl_historical_stats WHERE season = 'Season 4' GROUP BY season, game;"

# 3.3 - Document archival
echo "Historical stats archived for Season 4: $(date)" >> /data/season_reset_log.txt
```

**Expected Results:**
```
Season 4|tetris|[count]
Season 4|snake|[count]
Season 4|space_invaders|[count]
```

**🚨 IF THIS FAILS - STOP! DO NOT PROCEED!**
- Fix archival issues first
- All Season 4 player history will be lost forever without this!

---

## ✅ STEP 4: EXECUTE SEASON RESET

### **Commands:**
```bash
# 4.1 - Execute reset in single transaction
sqlite3 /var/www/html/db/narrrf_world.sqlite "
BEGIN TRANSACTION;

-- Reset 3 main games only
DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');
DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');

-- Deactivate Season 4
UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1;

-- Create Season 5
INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) 
VALUES ('Season 5', datetime('now'), datetime('now', '+30 days'), 1);

COMMIT;
"

# 4.2 - Document reset execution
echo "Season 5 reset executed: $(date)" >> /data/season_reset_log.txt
```

**Expected Results:**
- No error messages
- Transaction committed successfully

---

## ✅ STEP 5: VERIFY RESET SUCCESS

### **Commands:**
```bash
# 5.1 - Verify Season 5 is active
echo "=== NEW SEASON ===" && \
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT * FROM tbl_seasons WHERE is_active = 1;"

# 5.2 - Verify 3 main games are reset (MUST BE 0!)
echo "=== RESET VERIFICATION ===" && \
echo "Tetris (should be 0):" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';" && \
echo "Snake (should be 0):" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';" && \
echo "Space Invaders (should be 0):" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"

# 5.3 - Verify preserved data (should match Step 1 counts)
echo "=== PRESERVED DATA ===" && \
echo "Cheese clicks:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_cheese_clicks;" && \
echo "Race participants:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_race_participants;" && \
echo "Tetris achievements:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_achievements;" && \
echo "Snake achievements:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_snake_achievements;" && \
echo "Space Invaders achievements:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_space_invaders_achievements;"

# 5.4 - Document verification
echo "Post-reset verification completed: $(date)" >> /data/season_reset_log.txt
```

**Expected Results:**
- ✅ Season 5 is active
- ✅ Tetris scores = 0
- ✅ Snake scores = 0
- ✅ Space Invaders scores = 0
- ✅ All preserved data matches Step 1 counts

---

## ✅ STEP 6: COPY DATABASE TO /DATA (CRITICAL!)

### **Commands:**
```bash
# 6.1 - Copy updated database to persistent storage
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite

# 6.2 - Verify copy succeeded
ls -lh /data/narrrf_world.sqlite

# 6.3 - Document copy
echo "Database copied to /data: $(date)" >> /data/season_reset_log.txt
echo "✅ SEASON 5 RESET COMPLETE!"
```

**Expected Results:**
- `/data/narrrf_world.sqlite` shows recent timestamp
- File size ~6-7 MB

---

## ✅ STEP 7: FRONTEND UPDATES (Local Development)

### **Files to Update:**

**1. Remove "Config Mode" banners:**
- `public/index.html` - Remove yellow/orange "Season 5 Config" banner
- `public/profile.html` - Remove config banner above leaderboard
- Update to "Season 5 Live" messaging

**2. Update season references:**
- Search for "Season 4" and update to "Season 5" where appropriate
- Update any hardcoded season references in APIs

**3. Test locally:**
- Verify all games work
- Check leaderboards show empty (for 3 main games)
- Confirm achievements still display
- Test mobile compatibility

---

## ✅ STEP 8: CODE DEPLOYMENT

### **Commands (Local Machine):**
```powershell
# 8.1 - Stage all changes
cd C:\xampp-server\htdocs\narrrfs-world
git add .

# 8.2 - Commit with descriptive message
git commit -m "feat: Season 5 Launch

- Season 5 reset completed on Render
- Leaderboards cleared for Tetris, Snake, Space Invaders
- Season 4 historical stats archived
- Achievements preserved
- Cheese Hunt data preserved
- Discord Race data preserved
- Frontend updated with Season 5 Live messaging
- Config mode banners removed
- Database backed up to /data"

# 8.3 - Push to production
git push origin render-deploy

# 8.4 - Document deployment
# [Make lab note after push completes]
```

---

## ✅ STEP 9: POST-DEPLOYMENT VERIFICATION

### **After Render deploys:**

**Test These URLs:**
1. `https://narrrfs.world/profile.html` - Leaderboards should be empty for 3 main games
2. `https://narrrfs.world/partners.html` - All partner images present (automation!)
3. `https://narrrfs.world/index.html` - Season 5 Live messaging

**Browser Console:**
- No 404 errors
- No JavaScript errors
- Games load correctly

**Play Test:**
- Play one game from each of the 3 main games
- Verify scores save correctly
- Check leaderboard updates
- Confirm achievements still work

---

## 📊 VERIFICATION CHECKLIST

### **Database Verification:**
- [ ] Season 5 is active in `tbl_seasons`
- [ ] Tetris scores = 0
- [ ] Snake scores = 0
- [ ] Space Invaders scores = 0
- [ ] Cheese Hunt data preserved
- [ ] Discord Race data preserved
- [ ] All achievements preserved
- [ ] Database copied to `/data/`

### **Frontend Verification:**
- [ ] Config mode banners removed
- [ ] "Season 5 Live" messaging active
- [ ] Leaderboards show empty for 3 main games
- [ ] All games playable
- [ ] Scores save correctly
- [ ] Achievements display correctly

### **System Verification:**
- [ ] No 404 errors
- [ ] No JavaScript errors
- [ ] Partner images persisting (automation!)
- [ ] Mobile compatibility maintained
- [ ] All APIs operational

---

## 🎯 EXPECTED TIMELINE

**Total Time:** ~30-45 minutes

- **Steps 1-3 (Pre-Reset):** 10 minutes
- **Steps 4-6 (Reset + Backup):** 5 minutes
- **Steps 7-8 (Frontend + Deploy):** 15 minutes
- **Step 9 (Verification):** 10 minutes
- **Documentation:** 5 minutes

---

## 🚨 TROUBLESHOOTING

### **If Archival Fails:**
```bash
# Check if tbl_historical_stats exists
sqlite3 /var/www/html/db/narrrf_world.sqlite ".schema tbl_historical_stats"

# If missing, create it first, then retry archival
# DO NOT proceed with reset without archival!
```

### **If Reset Fails:**
```bash
# Rollback to backup
cp /data/narrrf_world_backup_[TIMESTAMP].sqlite /var/www/html/db/narrrf_world.sqlite

# Verify rollback
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT * FROM tbl_seasons WHERE is_active = 1;"

# Should show Season 4 as active
```

### **If Verification Fails:**
- Check if counts match expectations
- Verify SQL commands executed correctly
- Check for partial transaction commits
- Review `/data/season_reset_log.txt` for errors

---

## 📝 DOCUMENTATION TEMPLATE

### **After Reset, Document:**

```markdown
# Season 5 Reset - November 1, 2025

## Pre-Reset Counts:
- Tetris: [count]
- Snake: [count]
- Space Invaders: [count]
- Cheese Hunt: [count] (preserved)
- Discord Race: [count] (preserved)
- Achievements: [count] (preserved)

## Reset Execution:
- Backup created: [timestamp]
- Historical stats archived: [verified]
- Reset executed: [timestamp]
- Season 5 created: [verified]

## Post-Reset Verification:
- Tetris: 0 ✅
- Snake: 0 ✅
- Space Invaders: 0 ✅
- Preserved data: [all counts match] ✅

## Deployment:
- Commit: [hash]
- Deployed: [timestamp]
- Status: Success ✅
```

---

## 🎯 QUICK REFERENCE

### **One-Command Full Reset (For Copy/Paste):**
```bash
# ONLY use after completing Steps 1-3!
sqlite3 /var/www/html/db/narrrf_world.sqlite "
BEGIN TRANSACTION;
DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');
DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');
UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1;
INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) 
VALUES ('Season 5', datetime('now'), datetime('now', '+30 days'), 1);
COMMIT;
" && echo "✅ Season 5 reset complete!"
```

### **One-Command Verification:**
```bash
echo "Season:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season_name FROM tbl_seasons WHERE is_active = 1;" && \
echo "Tetris:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';" && \
echo "Snake:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';" && \
echo "Space Invaders:" && sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"
```

---

## 🎊 SUCCESS CRITERIA

### **Reset is Successful When:**
- ✅ Season 5 is active (verified in database)
- ✅ All 3 main game scores = 0 (Tetris, Snake, Space Invaders)
- ✅ All preserved data intact (Cheese Hunt, Race, Achievements)
- ✅ Database copied to `/data/` (for next deployment)
- ✅ Frontend updated and deployed
- ✅ Games playable and saving scores
- ✅ No data loss or corruption

---

## 🧀 FINAL REMINDERS

**NEVER RESET:**
- ❌ Cheese Hunt (`tbl_cheese_clicks`)
- ❌ Discord Race (`tbl_race_participants`)
- ❌ Individual Achievements (all 3 games)
- ❌ User accounts or roles
- ❌ Store inventory or purchases

**ALWAYS:**
- ✅ Backup before reset
- ✅ Archive historical stats BEFORE deletion
- ✅ Verify each step
- ✅ Copy database to `/data/`
- ✅ Document everything

---

**🚀 READY TO EXECUTE SEASON 5 RESET!**

**Status:** ✅ **CHECKLIST READY**  
**Next:** Execute steps 1-9 in exact order  
**Remember:** Archive historical stats BEFORE deletion! 🚨

