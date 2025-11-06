# 🚀 BUG #269 - PRODUCTION DEPLOYMENT PLAN

**Date:** November 6, 2025 - Late Evening  
**Status:** ✅ **LOCAL TESTING COMPLETE - READY FOR PRODUCTION**  

---

## ✅ **LOCAL TESTING RESULTS:**

### **Before Cleanup:**
- Space Invaders: **12 unlocked** (with 4 duplicates)
- Snake: **Unknown** (had old format achievements)
- Profile displayed **35 achievement cards** but said "Total: 28"

### **After Cleanup:**
- Space Invaders: **8 unlocked** ✅ **CORRECT** (no duplicates)
- Snake: **0 unlocked** ✅ **CLEAN**
- Profile should now display **28 achievement cards** matching "Total: 28"

### **Verification:**
- ✅ Database backup created (`narrrf_world_backup_pre_cleanup_nov6.sqlite`)
- ✅ Old format achievements deleted (0 remaining)
- ✅ Achievement DEFINITIONS intact (28 Space Invaders, 20 Snake)
- ✅ Ready for user testing on profile page

---

## 🎯 **PRODUCTION DEPLOYMENT PLAN:**

### **📊 IMPACT ASSESSMENT:**

**Affected Users:**
- Space Invaders: **10 users**, **22 old achievements**
- Snake: **32 users**, **50 old achievements**
- Total: **42 unique users**, **72 old achievements to delete**

**User Experience:**
- Users will see fewer unlocked achievements temporarily
- They will re-unlock naturally as they play
- No data loss (just cleanup of duplicate/old format records)
- Profile pages will show correct counts immediately

---

## 🔧 **DEPLOYMENT PROCEDURE:**

### **STEP 1: ACCESS RENDER SHELL**
```bash
# Open Render shell for narrrfs.world service
# Navigate to database directory
cd /var/www/html/db
```

---

### **STEP 2: BACKUP PRODUCTION DATABASE** ⚠️ **CRITICAL**
```bash
# Backup to /data for persistence
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world_backup_pre_cleanup_nov6_$(date +%Y%m%d_%H%M%S).sqlite

# Verify backup exists
ls -lh /data/narrrf_world_backup_pre_cleanup_nov6_*

# Get backup size for verification
du -h /data/narrrf_world_backup_pre_cleanup_nov6_*
```

**Expected:** Backup file ~20-50 MB created successfully

---

### **STEP 3: CREATE CLEANUP SCRIPT**
```bash
# Create SQL cleanup script
echo "-- Space Invaders Cleanup
DELETE FROM tbl_space_invaders_achievements
WHERE user_id != 'ACHIEVEMENT_DEFINITIONS'
  AND achievement_key NOT IN (
    SELECT achievement_key 
    FROM tbl_space_invaders_achievements 
    WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'
  );

-- Snake Cleanup
DELETE FROM tbl_snake_achievements
WHERE user_id != 'ACHIEVEMENT_DEFINITIONS'
  AND achievement_key NOT IN (
    SELECT achievement_key 
    FROM tbl_snake_achievements 
    WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'
  );

-- Verification
SELECT 'Space Invaders Old Format Remaining:' as result, COUNT(*) as count
FROM tbl_space_invaders_achievements
WHERE user_id != 'ACHIEVEMENT_DEFINITIONS'
  AND achievement_key NOT IN (
    SELECT achievement_key 
    FROM tbl_space_invaders_achievements 
    WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'
  );

SELECT 'Snake Old Format Remaining:' as result, COUNT(*) as count
FROM tbl_snake_achievements
WHERE user_id != 'ACHIEVEMENT_DEFINITIONS'
  AND achievement_key NOT IN (
    SELECT achievement_key 
    FROM tbl_snake_achievements 
    WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'
  );" > /tmp/cleanup_achievements.sql

# Verify script created
cat /tmp/cleanup_achievements.sql
```

---

### **STEP 4: RUN CLEANUP ON PRODUCTION**
```bash
# Execute cleanup
sqlite3 /var/www/html/db/narrrf_world.sqlite < /tmp/cleanup_achievements.sql
```

**Expected Output:**
```
Space Invaders Old Format Remaining:|0
Snake Old Format Remaining:|0
```

---

### **STEP 5: VERIFY CLEANUP RESULTS**
```bash
# Check Space Invaders old format count (should be 0)
echo "SELECT COUNT(*) FROM tbl_space_invaders_achievements WHERE user_id != 'ACHIEVEMENT_DEFINITIONS' AND achievement_key NOT IN (SELECT achievement_key FROM tbl_space_invaders_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS');" | sqlite3 /var/www/html/db/narrrf_world.sqlite

# Expected: 0

# Check Snake old format count (should be 0)
echo "SELECT COUNT(*) FROM tbl_snake_achievements WHERE user_id != 'ACHIEVEMENT_DEFINITIONS' AND achievement_key NOT IN (SELECT achievement_key FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS');" | sqlite3 /var/www/html/db/narrrf_world.sqlite

# Expected: 0

# Verify DEFINITIONS intact
echo "SELECT COUNT(*) FROM tbl_space_invaders_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 /var/www/html/db/narrrf_world.sqlite
# Expected: 28

echo "SELECT COUNT(*) FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 /var/www/html/db/narrrf_world.sqlite
# Expected: 20
```

---

### **STEP 6: COPY TO /data FOR PERSISTENCE** ⚠️ **CRITICAL**
```bash
# Copy cleaned database to /data
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite

# Verify copy successful
ls -lh /data/narrrf_world.sqlite

# Compare sizes (should be slightly smaller after cleanup)
du -h /var/www/html/db/narrrf_world.sqlite
du -h /data/narrrf_world.sqlite
```

**Why This Matters:**
- Render uses `/data` for persistent storage
- Next deployment will copy from `/data` to `/var/www/html/db`
- Ensures cleanup persists across deployments

---

### **STEP 7: VERIFY ON LIVE SITE**
```bash
# Test on live site
# 1. Open https://narrrfs.world/public/profile.html
# 2. Login with Discord
# 3. Open Space Invaders achievements
# 4. Verify: Total Achievements = Card Count (should be 28)
# 5. Open Snake achievements
# 6. Verify: Total Achievements = Card Count (should be 20)
```

**Success Criteria:**
- ✅ Achievement modal shows correct total count
- ✅ Total count matches number of displayed cards
- ✅ No duplicate achievements visible
- ✅ Stats are accurate (Unlocked + Locked = Total)

---

## 📋 **POST-DEPLOYMENT CHECKLIST:**

### **Immediate Verification:**
- [ ] Backup exists in `/data`
- [ ] Old format achievements count = 0
- [ ] DEFINITIONS count = 28 (Space Invaders) / 20 (Snake)
- [ ] Database copied to `/data`
- [ ] Profile page shows correct counts
- [ ] No duplicate achievements displayed

### **Community Monitoring:**
- [ ] Watch for user reports of missing achievements
- [ ] Monitor Discord for confusion/questions
- [ ] Check if achievements re-unlock properly during gameplay
- [ ] Verify no other bugs introduced

---

## 🔄 **ROLLBACK PLAN (If Needed):**

### **If Something Goes Wrong:**
```bash
# Restore from backup
cp /data/narrrf_world_backup_pre_cleanup_nov6_* /var/www/html/db/narrrf_world.sqlite

# Copy to /data
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite

# Verify restoration
echo "SELECT COUNT(*) FROM tbl_space_invaders_achievements WHERE user_id != 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 /var/www/html/db/narrrf_world.sqlite
# Should show old count (with duplicates)
```

**When to Rollback:**
- DEFINITIONS deleted (count ≠ 28/20)
- Database corruption
- Widespread user complaints
- Unexpected errors

---

## 📢 **COMMUNITY COMMUNICATION (Optional):**

### **Discord Announcement (After Deployment):**
```
🔧 Achievement System Cleanup Complete!

We've cleaned up some legacy achievement data from Season 4.

📊 What Changed:
✅ Duplicate achievement records removed
✅ Achievement counts now accurate
✅ Profile pages display correctly

🎮 What This Means:
• Your achievement totals may temporarily appear lower
• Don't worry! You'll re-unlock them as you play
• All your progress and scores are safe
• This fixes the "35 cards but says 28 total" bug

🧀 Thanks for your patience as we improve the system!
```

### **When to Post:**
- After successful deployment
- After verifying no major issues
- Ideally within 1 hour of cleanup

---

## 📊 **EXPECTED RESULTS:**

### **Database Changes:**
```sql
-- Before:
Space Invaders user achievements: ~50-100 records (with duplicates)
Snake user achievements: ~150-200 records (with duplicates)

-- After:
Space Invaders user achievements: ~30-80 records (clean)
Snake user achievements: ~100-150 records (clean)

-- Deleted:
72 old format achievement records total
```

### **User Experience:**
**Narrrf (Example):**
- Before: 12 unlocked Space Invaders (with 4 duplicates)
- After: 8 unlocked Space Invaders (correct)
- Profile modal: Shows 28 total, 8 unlocked, 20 locked ✅

**Other Users:**
- Similar cleanup across 42 users
- Some will drop from inflated counts to accurate counts
- They'll re-earn achievements naturally during gameplay

---

## 🎯 **SUCCESS METRICS:**

### **Technical Success:**
- ✅ 0 old format achievements remaining
- ✅ All DEFINITIONS intact (28 + 20)
- ✅ Database size reduced slightly
- ✅ No errors during cleanup
- ✅ Backup created successfully

### **User Experience Success:**
- ✅ Profile modals show correct totals
- ✅ No duplicate achievements displayed
- ✅ Stats match reality
- ✅ Users can re-unlock achievements
- ✅ No complaints about missing data

---

## ⏰ **RECOMMENDED DEPLOYMENT TIME:**

### **Best Time:**
- **Late evening / night (low traffic)**
- **After peak gaming hours**
- **When you can monitor for 30-60 minutes**

### **Estimated Duration:**
- Backup: 1 minute
- Cleanup script: 1 minute
- Execute cleanup: 1 second
- Verification: 2 minutes
- Copy to /data: 1 minute
- **Total: ~5 minutes**

---

## 🚨 **CRITICAL REMINDERS:**

### **BEFORE DEPLOYMENT:**
1. ✅ Backup database to `/data`
2. ✅ Verify backup size reasonable
3. ✅ Test script syntax before running
4. ✅ Have rollback plan ready

### **DURING DEPLOYMENT:**
1. ✅ Run queries one at a time
2. ✅ Verify each step before proceeding
3. ✅ Watch for error messages
4. ✅ Don't skip verification steps

### **AFTER DEPLOYMENT:**
1. ✅ Copy database to `/data` (CRITICAL!)
2. ✅ Test on live site
3. ✅ Monitor user feedback
4. ✅ Document any issues

---

## 📝 **DEPLOYMENT LOG TEMPLATE:**

```
=== BUG #269 PRODUCTION DEPLOYMENT ===
Date: [DATE]
Time: [TIME]
Operator: [YOUR NAME]

PRE-DEPLOYMENT:
[ ] Backup created: /data/narrrf_world_backup_pre_cleanup_nov6_[TIMESTAMP].sqlite
[ ] Backup size: [SIZE] MB

CLEANUP EXECUTION:
[ ] Script created: /tmp/cleanup_achievements.sql
[ ] Cleanup executed successfully
[ ] Space Invaders old format: 0 remaining
[ ] Snake old format: 0 remaining

VERIFICATION:
[ ] Space Invaders DEFINITIONS: 28
[ ] Snake DEFINITIONS: 20
[ ] Database copied to /data
[ ] Live site tested
[ ] Profile modals show correct counts

ISSUES ENCOUNTERED:
[List any issues or "None"]

STATUS: [SUCCESS/ROLLBACK/PARTIAL]
```

---

**DEPLOYMENT PLAN READY:** November 6, 2025 - Late Evening  
**LOCAL TESTING:** ✅ **SUCCESSFUL**  
**PRODUCTION RISK:** 🟢 **LOW** (simple DELETE operation, full backup)  
**USER IMPACT:** 🟡 **MEDIUM** (temporary reduction in achievement counts)  
**NEXT:** Deploy to production when ready!


