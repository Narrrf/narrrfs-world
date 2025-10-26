# 🚀 RESET SEASON PROTOCOL RULE - PROFESSIONAL OPERATIONS

**STATUS:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**  
**CREATED:** October 6, 2025  
**PURPOSE:** Standardized season reset protocol for all future seasons  
**PRIORITY:** 🚨 **CRITICAL - PRODUCTION OPERATIONS**  

---

## 🎯 **RULE OVERVIEW**

### **CORE PRINCIPLE:**
**Every season reset must follow this exact protocol to ensure zero data loss, complete system integrity, and professional operations.**

### **RULE SCOPE:**
- **Database Operations** - Season activation and data management
- **Code Deployment** - Git workflow and production deployment
- **Data Preservation** - Critical data protection protocols
- **Verification Procedures** - Comprehensive validation steps
- **Documentation Requirements** - Complete audit trail
- **Emergency Procedures** - Rollback and recovery protocols

---

## 📋 **MANDATORY PRE-RESET CHECKLIST**

### **✅ PRE-RESET VERIFICATION (MANDATORY):**
```bash
# 1. Verify current season status
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT * FROM tbl_seasons WHERE is_active = 1;"

# 2. Count existing data for verification
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores;"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_cheese_clicks;"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_race_participants;"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_achievements;"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_snake_achievements;"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_space_invaders_achievements;"

# 3. Document pre-reset state
echo "Pre-reset data counts documented: $(date)" >> /data/season_reset_log.txt
```

### **✅ DATABASE BACKUP (CRITICAL):**
```bash
# ALWAYS backup before any reset operation
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite
echo "Database backup created: $(date)" >> /data/season_reset_log.txt
```

### **🏆 ARCHIVE HISTORICAL STATS (CRITICAL - NEW IN v2.0):**
```bash
# 🚨 MOST CRITICAL STEP - Archive current season data BEFORE deletion
# This preserves all player gaming history for all-time statistics display

# Archive Season 4 data to historical tables
curl https://narrrfs.world/api/admin/archive-season-stats.php

# Verify archival worked (MANDATORY)
echo "SELECT season, game, COUNT(*) FROM tbl_historical_stats WHERE season = 'Season 4' GROUP BY season, game;" | sqlite3 /var/www/html/db/narrrf_world.sqlite

# Should show Season 4 data counts for tetris, snake, space_invaders
# Example output:
# Season 4|tetris|45
# Season 4|snake|38
# Season 4|space_invaders|52

# Document archival completion
echo "Historical stats archived for Season 4: $(date)" >> /data/season_reset_log.txt

# 🚨 IF ARCHIVAL FAILS - DO NOT PROCEED WITH RESET!
# Fix archival issues first, or all Season 4 player history will be lost forever!
```

**WHY THIS IS CRITICAL:**
- Without archival, ALL player gaming history from Season 4 is **LOST FOREVER**
- Players will only see Season 5 data on their profiles
- Complete gaming legacy disappears
- Community trust damaged
- **This step enables the "All-Time Statistics" feature on profile pages!**

---

## 🚨 **RESET EXECUTION PROTOCOL**

### **✅ STEP 1: DATABASE RESET COMMANDS**
```bash
# Execute reset commands in exact order
sqlite3 /var/www/html/db/narrrf_world.sqlite "
-- Reset 3 main games only (CRITICAL: Must delete ALL old data)
DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');
DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');

-- Deactivate current season
UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1;

-- Create new season (adjust name and duration as needed)
INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) 
VALUES ('Season X', datetime('now'), datetime('now', '+30 days'), 1);
"

# CRITICAL VERIFICATION: Ensure old data is completely deleted
echo "Verifying reset completion..." >> /data/season_reset_log.txt
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) as tetris_count FROM tbl_tetris_scores WHERE game = 'tetris';" >> /data/season_reset_log.txt
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) as snake_count FROM tbl_tetris_scores WHERE game = 'snake';" >> /data/season_reset_log.txt
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) as space_invaders_count FROM tbl_tetris_scores WHERE game = 'space_invaders';" >> /data/season_reset_log.txt

echo "Database reset completed: $(date)" >> /data/season_reset_log.txt
```

### **✅ STEP 2: COPY DATABASE TO /DATA**
```bash
# Critical for next deployment
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite
echo "Database copied to /data: $(date)" >> /data/season_reset_log.txt
```

---

## 🔍 **POST-RESET VERIFICATION PROTOCOL**

### **✅ MANDATORY VERIFICATION STEPS:**
```bash
# 1. Verify new season is active
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT * FROM tbl_seasons WHERE is_active = 1;"

# 2. Verify 3 main games are reset (should show 0)
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores;"

# 3. Verify preserved data (should match pre-reset counts)
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_cheese_clicks;"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_race_participants;"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_achievements;"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_snake_achievements;"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_space_invaders_achievements;"

# 4. Document verification results
echo "Post-reset verification completed: $(date)" >> /data/season_reset_log.txt
```

### **✅ VERIFICATION CRITERIA:**
- **New Season Active:** Must show new season with `is_active = 1`
- **3 Main Games Reset:** `tbl_tetris_scores` count must be 0 for ALL three games
- **Data Preserved:** All other counts must match pre-reset values
- **No Data Loss:** Zero tolerance for data loss

### **🚨 CRITICAL VERIFICATION STEPS:**
```bash
# MUST verify ALL three games are completely reset
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';" # Must be 0
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';" # Must be 0
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';" # Must be 0
```

### **⚠️ COMMON RESET FAILURES:**
- **Incomplete DELETE:** Old season data still exists after reset
- **Season Name Mismatch:** API expects different season name format
- **Partial Reset:** Only some games reset, others retain old data
- **API Filtering Issues:** Admin interface shows mixed season data

### **🔧 POST-RESET API FIXES REQUIRED:**
```bash
# 1. Update API hardcoded fallbacks to new season
# Files: api/admin/get-all-games-stats.php, api/admin/get-season-stats.php
# Change: 'Season X-1' → 'Season X' in fallback values

# 2. Verify admin interface season dropdown
# File: public/admin-interface.html
# Ensure: New season appears in dropdown options

# 3. Test admin interface data display
# Verify: 3 main games show 0 scores, preserved games show all-time data
```

---

## 🚀 **CODE DEPLOYMENT PROTOCOL**

### **✅ GIT WORKFLOW (MANDATORY):**
```bash
# 1. Stage all changes
git add .

# 2. Commit with descriptive message
git commit -m "Season X Launch: [List all changes and fixes]

- [Feature 1 description]
- [Feature 2 description]
- [Bug fix 1 description]
- [Bug fix 2 description]
- Database reset completed for Season X
- Ready for live Season X launch"

# 3. Push to render-deploy branch
git push origin render-deploy

# 4. Document deployment
echo "Code deployed to production: $(date)" >> /data/season_reset_log.txt
```

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

### **⚠️ SPECIAL CASES:**
- **`tbl_season_leaderboards`** - No `game` column, regenerates automatically
- **User Profiles** - Never reset user account data
- **Store Items** - Never reset store inventory or purchases
- **Wallet Data** - Never reset NFT ownership or wallet balances

---

## 🚨 **EMERGENCY PROCEDURES**

### **✅ ROLLBACK PROTOCOL:**
```bash
# If reset fails or causes issues
cp /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite /var/www/html/db/narrrf_world.sqlite
echo "Emergency rollback executed: $(date)" >> /data/season_reset_log.txt
```

### **✅ RECOVERY VERIFICATION:**
```bash
# Verify rollback success
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT * FROM tbl_seasons WHERE is_active = 1;"
# Should show previous season as active
```

---

## 📝 **DOCUMENTATION REQUIREMENTS**

### **✅ MANDATORY DOCUMENTATION:**
1. **Pre-Reset State** - Document all data counts
2. **Reset Commands** - Record exact commands executed
3. **Verification Results** - Document all verification steps
4. **Deployment Log** - Record git commit and push details
5. **Issue Log** - Document any problems or deviations
6. **Lab Note** - Create comprehensive lab note in `12.0/LAB_NOTES/`

### **✅ LAB NOTE TEMPLATE:**
```markdown
# 🚀 SEASON X RESET - [DATE]

## Pre-Reset State:
- Current Season: [Season Name]
- Data Counts: [Document all counts]

## Reset Execution:
- Commands: [List exact commands]
- Results: [Document outcomes]

## Verification:
- New Season: [Confirm active]
- Data Reset: [Confirm 3 main games reset]
- Data Preserved: [Confirm preserved data]

## Deployment:
- Commit: [Git commit hash]
- Status: [Success/Failure]

## Issues:
- Problems: [List any issues]
- Solutions: [Document resolutions]
```

---

## 🎯 **SUCCESS METRICS**

### **✅ TECHNICAL SUCCESS CRITERIA:**
- **Zero Data Loss** - All critical data preserved
- **Clean Reset** - 3 main games completely cleared
- **Season Activation** - New season successfully activated
- **Code Deployment** - All changes deployed successfully
- **System Integrity** - No broken functionality

### **✅ OPERATIONAL SUCCESS CRITERIA:**
- **Professional Process** - All steps followed exactly
- **Complete Documentation** - Full audit trail created
- **Team Communication** - All stakeholders informed
- **Risk Mitigation** - Backup and verification completed
- **Future Readiness** - System ready for new season

---

## 🚨 **CRITICAL RULES TO NEVER VIOLATE**

### **❌ NEVER DO:**
- **Reset without backup** - Always backup database first
- **Reset preserved data** - Never touch Cheese Hunt, Discord Race, or achievements
- **Skip verification** - Always verify before and after reset
- **Deploy without testing** - Always test locally first
- **Skip documentation** - Always create complete audit trail

### **✅ ALWAYS DO:**
- **Follow exact protocol** - No deviations from this rule
- **Document everything** - Complete record of all operations
- **Verify at each step** - Check results before proceeding
- **Communicate status** - Keep team informed of progress
- **Plan for rollback** - Always have recovery plan ready

---

## 🔄 **RULE EVOLUTION**

### **📋 RULE UPDATES:**
- **Version 1.0** - Initial rule based on Season 4 reset (October 6, 2025)
- **Version 2.0** - Added Historical Stats Archival Protocol (October 25, 2025)
  - **Critical Addition:** Archive historical stats BEFORE season reset
  - **New Requirement:** Run `archive-season-stats.php` before deletion
  - **Impact:** Preserves all player gaming history across unlimited seasons
  - **Feature Enabled:** All-Time Statistics on profile pages
- **Future Updates** - Rule will be updated based on new learnings and requirements
- **Version Control** - All updates must be documented with rationale

### **📚 LESSONS LEARNED INTEGRATION:**
- **Database Structure Changes** - Update commands if table structure changes
- **New Game Integration** - Extend reset commands for new games
- **Process Improvements** - Incorporate efficiency improvements
- **Error Prevention** - Add safeguards for common mistakes
- **Historical Data Preservation** - Archive before delete (October 25, 2025)

---

## 🧀 **FINAL MANDATE**

### **THIS RULE IS NON-NEGOTIABLE:**
- **Every season reset** MUST follow this exact protocol
- **No exceptions** without documented rationale and approval
- **Complete compliance** required for all team members
- **Professional standards** maintained at all times

### **THE ULTIMATE GOAL:**
**Ensure every season reset is executed with surgical precision, zero data loss, complete system integrity, and professional documentation for decades of reliable operations.**

---

**RULE CREATED:** October 6, 2025  
**LAST UPDATED:** October 25, 2025 (v2.0 - Historical Stats Archival)  
**STATUS:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**  
**PURPOSE:** Professional Season Reset Operations with Historical Data Preservation  
**SCOPE:** All future season resets, all team members, all environments  

**🚀 THIS RULE ENSURES DECADES OF RELIABLE SEASON OPERATIONS! 🚀**

---

## 🏆 **VERSION 2.0 CRITICAL UPDATE (OCTOBER 25, 2025)**

### **NEW MANDATORY STEP:**
**ALWAYS run `archive-season-stats.php` BEFORE resetting any season!**

This preserves complete player gaming history across unlimited seasons and enables the "All-Time Statistics" feature on profile pages.

**Failure to archive = Permanent loss of that season's player data!**
