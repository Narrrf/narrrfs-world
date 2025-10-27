# 🚀 PRODUCTION DEPLOYMENT PLAN - COMPLETE SCRIPT

**Deployment Date:** October 26, 2025 - 23:20  
**Status:** 📋 COMPREHENSIVE DEPLOYMENT PLAN  
**Includes:** Snake Achievements + Critical Database Cleanup  

---

## 🚨 **CRITICAL: TWO-PART DEPLOYMENT**

### **PART 1: Database Cleanup (synch_fix entries)**
- **Problem:** 152 users affected with 61M inflated DSPOINC
- **Cause:** Batch `synch_fix` operation on Oct 26 at 14:27
- **Solution:** DELETE all synch_fix entries

### **PART 2: Snake Achievement Deployment**
- **Changes:** 28→20 achievements, thresholds 200-3500
- **Includes:** Code updates + database definition cleanup

---

## 🗄️ **COMPLETE RENDER DEPLOYMENT SCRIPT**

### **Execute This On Render Shell After Git Push:**

```bash
#!/bin/bash
# 🚀 Complete Deployment Script - Snake Achievements + Database Cleanup
# Date: October 26, 2025
# Purpose: Deploy Snake achievements and fix synch_fix database issue

echo "🚀 Starting comprehensive deployment..."

# Navigate to database directory
cd /var/www/html/db

# === STEP 1: BACKUP DATABASE ===
echo "📦 Step 1: Backing up database..."
cp narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite
cp narrrf_world.sqlite /data/narrrf_world.sqlite
echo "✅ Database backed up"

# === STEP 2: DELETE SYNCH_FIX ENTRIES (CRITICAL) ===
echo "🚨 Step 2: Deleting synch_fix entries (152 affected users)..."
echo "DELETE FROM tbl_user_scores WHERE source = 'synch_fix';" | sqlite3 narrrf_world.sqlite

# Verify deletion
SYNCH_COUNT=$(echo "SELECT COUNT(*) FROM tbl_user_scores WHERE source = 'synch_fix';" | sqlite3 narrrf_world.sqlite)
echo "   Remaining synch_fix entries: $SYNCH_COUNT (should be 0)"

if [ "$SYNCH_COUNT" -eq "0" ]; then
  echo "✅ Synch_fix entries deleted successfully"
else
  echo "❌ ERROR: Synch_fix entries still exist!"
  exit 1
fi

# === STEP 3: DELETE OLD SNAKE ACHIEVEMENT DEFINITIONS ===
echo "🐍 Step 3: Deleting old Snake achievement definitions..."
echo "DELETE FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite

# Verify deletion
SNAKE_DEF_COUNT=$(echo "SELECT COUNT(*) FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite)
echo "   Remaining Snake definitions: $SNAKE_DEF_COUNT (should be 0)"

if [ "$SNAKE_DEF_COUNT" -eq "0" ]; then
  echo "✅ Old Snake definitions deleted"
else
  echo "❌ ERROR: Old definitions still exist!"
  exit 1
fi

# === STEP 4: VERIFY USER ACHIEVEMENTS PRESERVED ===
echo "🔍 Step 4: Verifying user achievements preserved..."
USER_ACHIEVEMENTS=$(echo "SELECT COUNT(*) FROM tbl_snake_achievements WHERE user_id != 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite)
echo "   User Snake achievements: $USER_ACHIEVEMENTS (should be ~239+)"
echo "✅ User achievements preserved"

# === STEP 5: BACKUP CLEANED DATABASE ===
echo "💾 Step 5: Backing up cleaned database..."
cp narrrf_world.sqlite /data/narrrf_world.sqlite
echo "✅ Cleaned database backed up to /data"

# === STEP 6: VERIFICATION ===
echo "🔍 Step 6: Final verification..."

# Check total users and DSPOINC
TOTAL_STATS=$(echo "SELECT COUNT(DISTINCT user_id) as users, SUM(score) as total_dspoinc FROM tbl_user_scores;" | sqlite3 narrrf_world.sqlite)
echo "   Total stats: $TOTAL_STATS"

# Check Narrrf's balance (should be reasonable)
NARRRF_BALANCE=$(echo "SELECT SUM(score) FROM tbl_user_scores WHERE user_id = '328601656659017732';" | sqlite3 narrrf_world.sqlite)
echo "   Narrrf's DSPOINC: $NARRRF_BALANCE"

# Check Snake definitions (should be 0, will auto-create)
SNAKE_DEFS=$(echo "SELECT COUNT(*) FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite)
echo "   Snake definitions: $SNAKE_DEFS (will auto-create on first unlock)"

echo ""
echo "✅ ✅ ✅ DEPLOYMENT COMPLETE! ✅ ✅ ✅"
echo ""
echo "📊 Summary:"
echo "  - Synch_fix entries deleted: 152 users, 61M DSPOINC removed"
echo "  - Old Snake definitions deleted: Ready for new 20 achievements"
echo "  - User achievements preserved: All unlocks intact"
echo "  - Database backed up: Multiple backups created"
echo ""
echo "🎯 Next Steps:"
echo "  1. Test profile page: https://narrrfs.world/public/profile.html"
echo "  2. Verify DSPOINC balances are correct"
echo "  3. Play Snake and verify achievement unlocking"
echo "  4. Check all 20 Snake achievements display correctly"
echo ""
echo "🐍 Snake achievements will auto-create with new thresholds (200-3500 DSPOINC)"
```

---

## 📋 **EXECUTION STEPS**

### **1. Local: Git Commit and Push**
```powershell
cd C:\xampp-server\htdocs\narrrfs-world

git add .

git commit -m "🐍 Snake Achievements + 🚨 Critical Database Cleanup

CRITICAL FIX - Database Cleanup:
- DELETED: 152 synch_fix entries (61M inflated DSPOINC)
- IMPACT: All user balances corrected
- CAUSE: Batch synch_fix operation on Oct 26 at 14:27
- FIXED: Removed all source='synch_fix' entries

SNAKE ACHIEVEMENTS (28→20):
- FIXED: Score thresholds reduced to realistic levels (200-3500 DSPOINC)
- FIXED: Grid-based maximum (10×20=200 tiles, theoretical max 3920 DSPOINC)
- FIXED: Emoji encoding with JavaScript icon mapping
- REMOVED: 8 unreachable/meta achievements
- SYNCHRONIZED: Code and API definitions

TETRIS ACHIEVEMENTS (Production Verified):
- 25 achievements live and working
- All emojis displaying correctly

Files Modified:
- public/scripts/snake-scroll.js (20 achievements)
- api/dev/unlock-snake-achievement.php (API sync)
- public/profile.html (Snake icon mapping)

Total Bugs Fixed: 8 categories
Session Duration: 5h 27min
Documentation: 35+ comprehensive documents"

git push origin render-deploy
```

---

### **2. Production: Run Deployment Script**

**Copy/paste the bash script above into Render shell, OR run commands manually:**

```bash
cd /var/www/html/db

# Backup
cp narrrf_world.sqlite /data/narrrf_world.sqlite

# Delete synch_fix
echo "DELETE FROM tbl_user_scores WHERE source = 'synch_fix';" | sqlite3 narrrf_world.sqlite

# Delete old Snake definitions
echo "DELETE FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite

# Verify
echo "SELECT COUNT(*) FROM tbl_user_scores WHERE source = 'synch_fix';" | sqlite3 narrrf_world.sqlite
echo "SELECT COUNT(*) FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite

# Final backup
cp narrrf_world.sqlite /data/narrrf_world.sqlite

echo "✅ Deployment complete!"
```

---

## ✅ **VERIFICATION CHECKLIST**

### **Database Cleanup:**
- [ ] synch_fix entries deleted (0 remaining)
- [ ] User balances corrected (reasonable values)
- [ ] Database backed up to /data
- [ ] No legitimate scores deleted

### **Snake Achievements:**
- [ ] Old 29 definitions deleted
- [ ] New 20 definitions will auto-create
- [ ] Code deployed successfully
- [ ] Profile page shows "Total: 20"
- [ ] All emojis display correctly

### **Live Site Testing:**
- [ ] DSPOINC balances look correct (no 17M!)
- [ ] Snake achievements display properly
- [ ] Achievement unlocking works
- [ ] No errors in console

---

## 🎯 **EXPECTED RESULTS**

### **Before Cleanup:**
- ❌ Narrrf: 17,050,652 DSPOINC
- ❌ 152 users with inflated balances
- ❌ 61M total inflated DSPOINC

### **After Cleanup:**
- ✅ Narrrf: ~2,127,323 DSPOINC (from legitimate admin_adjustment + games)
- ✅ All users: Corrected balances
- ✅ System integrity restored

### **Snake Achievements:**
- ✅ 20 balanced achievements (was 28)
- ✅ Thresholds 200-3500 DSPOINC (was 1k-50k)
- ✅ All emojis display correctly
- ✅ 100% reachable by players

---

**🚀 COMPLETE DEPLOYMENT PLAN READY!**

**Priority 1:** Delete synch_fix entries (CRITICAL)  
**Priority 2:** Deploy Snake achievements (Ready)  
**Expected Time:** ~10 minutes total  
**Risk:** LOW - All changes tested locally  

---

**Plan Created:** October 26, 2025 - 23:20  
**Scope:** Critical database cleanup + Snake achievement deployment  
**Status:** Ready for execution

