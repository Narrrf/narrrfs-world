# 🐍 SNAKE ACHIEVEMENTS - PRODUCTION DEPLOYMENT COMMANDS

**Deployment Date:** October 26, 2025 - 23:00  
**Status:** 📋 READY FOR PRODUCTION DEPLOYMENT  
**Purpose:** Deploy Snake achievement fixes to live Render environment  

---

## 🚀 **DEPLOYMENT PROCEDURE**

### **Step 1: Git Commit and Push (Local)**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world

# Stage all changes
git add .

# Commit with descriptive message
git commit -m "🐍 Snake Achievements System Overhaul (Bug Fix)

- FIXED: Score thresholds reduced to realistic levels (200-3500 DSPOINC)
- FIXED: Emoji encoding issues with JavaScript icon mapping
- FIXED: Removed 8 unreachable/meta achievements (28 → 20 total)
- UPDATED: All thresholds based on grid reality (10×20 = 200 tiles, max 3920 DSPOINC)
- ADDED: getSnakeAchievementIcon() function for proper emoji display
- SYNCHRONIZED: Code and API definitions (cheese, not apple)

Score Thresholds (NEW):
- score_hunter: 200 DSPOINC (was 1000)
- point_master: 500 DSPOINC (was 2500)
- high_scorer: 1000 DSPOINC (was 5000)
- snake_king: 1500 DSPOINC (was 10000)
- score_legend: 2000 DSPOINC (was 20000)
- score_god: 3500 DSPOINC (was 50000 - now near theoretical max!)

Files Modified:
- public/scripts/snake-scroll.js (achievement thresholds)
- api/dev/unlock-snake-achievement.php (API definitions)
- public/profile.html (icon mapping, total count 28→20)

Documentation Created:
- 9 comprehensive lab notes (analysis, fixes, testing)
- Technical documentation (SNAKE_ACHIEVEMENTS_SYSTEM.md)

Following: Tetris achievement overhaul success model
Bugs Fixed: Snake achievement balance and reachability"

# Push to production
git push origin render-deploy
```

---

## 🗄️ **Step 2: Production Database Update (Render Shell)**

### **After Auto-Deploy Completes:**

```bash
# Navigate to database directory
cd /var/www/html/db

# Backup current database
cp narrrf_world.sqlite /data/narrrf_world.sqlite

# Delete old Snake achievement definitions (29 old definitions)
echo "DELETE FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite

# Verify deletion
echo "SELECT COUNT(*) FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite
# Expected: 0

# Check user achievements preserved
echo "SELECT COUNT(*) FROM tbl_snake_achievements WHERE user_id != 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite
# Expected: ~239 (or more)

# OPTION 1: Let achievements auto-create on first unlock (RECOMMENDED)
# - First player to unlock Snake achievement will trigger definition creation
# - API will auto-create new definitions with correct values
# - No manual insert needed

# OPTION 2: Manual insert (if you want definitions immediately)
# See SNAKE_RENDER_SQL_INSERT.md for full SQL script
```

---

## ✅ **VERIFICATION STEPS**

### **After Deployment:**

1. **Check Code Deployed:**
   ```bash
   # On Render shell
   cd /var/www/html/public/scripts
   grep "score >= 200" snake-scroll.js
   # Expected: { key: 'score_hunter', condition: score >= 200 }
   ```

2. **Check Database:**
   ```bash
   cd /var/www/html/db
   echo "SELECT COUNT(*) FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite
   # Expected: 0 (will auto-create on first unlock)
   ```

3. **Test on Live Site:**
   - Visit `https://narrrfs.world/public/profile.html`
   - Hard refresh (Ctrl+F5)
   - Click "🐍 View Snake Achievements"
   - Should show "Total: 20" (after first unlock creates definitions)

---

## 🚨 **CRITICAL: Why Auto-Create is Better**

### **Tetris Experience:**
- We manually inserted 25 definitions via SQL
- Had to split into 3 parts due to size
- Required SSH shell access
- Complex multi-step process

### **Snake Approach (Better):**
- API auto-creates definitions on first unlock
- No manual SQL needed
- No SSH complexity
- Definitions guaranteed to match code

### **How Auto-Create Works:**
```php
// In unlock-snake-achievement.php (lines 44-86)
// Check if definition exists
if ($defExists['count'] == 0) {
    // Create definition from $achievementData array
    $createDefStmt = $db->prepare("INSERT INTO tbl_snake_achievements ...");
    $createDefStmt->execute([...]);
}
```

**First unlock triggers:** Definition creation + user unlock = 2 database rows

---

## 🎯 **EXPECTED TIMELINE**

### **Immediate (After Push):**
- ✅ Code auto-deploys to Render
- ✅ New achievement thresholds live
- ✅ Icon mapping function active

### **Within 1 Hour:**
- ✅ First player unlocks Snake achievement
- ✅ API auto-creates all 20 definitions
- ✅ Profile page shows "Total: 20"

### **Verification (Tomorrow):**
- ✅ Check production database has 20 definitions
- ✅ Verify all descriptions show DSPOINC values
- ✅ Confirm emojis display correctly

---

## 📋 **DEPLOYMENT CHECKLIST**

### **Pre-Deployment:**
- [x] Code changes complete (snake-scroll.js, unlock-snake-achievement.php, profile.html)
- [x] Local database tested (20 definitions, emojis display correctly)
- [x] Documentation complete (9 lab notes + technical doc)
- [x] All achievement thresholds verified (200-3500 DSPOINC)

### **Deployment:**
- [ ] Git add all changes
- [ ] Git commit with detailed message
- [ ] Git push to render-deploy
- [ ] Wait for auto-deployment confirmation

### **Post-Deployment:**
- [ ] SSH to Render shell
- [ ] Navigate to /var/www/html/db
- [ ] Delete old definitions
- [ ] Backup database to /data
- [ ] Test on live site

---

## 🚨 **CRITICAL DIFFERENCE: Snake vs Tetris**

### **Tetris (Manual Insert Required):**
- ❌ Emoji encoding issues in SQLite
- ❌ Required manual SQL insert on production
- ❌ Had to split into 3 parts
- ❌ Complex deployment process

### **Snake (Auto-Create Recommended):**
- ✅ API handles emoji encoding correctly
- ✅ Auto-creates on first unlock
- ✅ No manual SQL needed
- ✅ Simple deployment process

**Recommendation:** Delete old definitions, let API auto-create new ones!

---

## 🎯 **DEPLOYMENT SUMMARY**

### **Files to Deploy:**
1. `public/scripts/snake-scroll.js` - 20 achievements, new thresholds
2. `api/dev/unlock-snake-achievement.php` - Synchronized definitions
3. `public/profile.html` - Icon mapping + total count fix

### **Database Changes:**
1. Delete old 29 definitions (manual on Render)
2. New 20 definitions auto-create (API handles it)

### **Testing:**
1. Hard refresh profile page
2. Click "🐍 View Snake Achievements"
3. Verify "Total: 20" and all emojis display

---

**🐍 READY FOR PRODUCTION DEPLOYMENT!**

**Status:** All code ready, deployment commands prepared  
**Next:** Git commit and push to render-deploy  
**Expected:** Clean deployment with auto-creating definitions  

---

**Document Created:** October 26, 2025 - 23:00  
**Maintained By:** Cursor LLM 12.0  
**Model:** Improved process based on Tetris lessons learned

