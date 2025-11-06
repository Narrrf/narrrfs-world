# 🐛 BUG #269 - ACHIEVEMENT DUPLICATES ACROSS ALL 3 GAMES

**Date:** November 6, 2025 - Late Evening  
**Reporter:** User (Screenshot evidence)  
**Issue:** Achievement modal shows 35 cards but header says "Total Achievements: 28"  
**Severity:** HIGH (data integrity issue)  
**Status:** 🔍 **INVESTIGATION IN PROGRESS**  

---

## 🎯 **ISSUE DESCRIPTION:**

### **The Problem:**
- Space Invaders achievement modal displays **35 achievement cards**
- But the header stats show "Total Achievements: **28**"
- This is a **7-card discrepancy** (35 - 28 = 7)
- **Cause:** Old achievement records with deprecated keys still in database

### **Visual Evidence:**
- Screenshot shows 35 individual achievement cards in the grid
- Top summary shows "Total: 28, Unlocked: 23, Locked: 5"
- Some achievements appear to be duplicated

---

## 🔍 **ROOT CAUSE ANALYSIS:**

### **Database Investigation Results:**

**ACHIEVEMENT DEFINITIONS (Correct):**
```
✅ Tetris: 25 definitions
✅ Snake: 20 definitions
✅ Space Invaders: 28 definitions
```

**NARRRF'S UNLOCKED ACHIEVEMENTS (Has Duplicates):**
```sql
-- OLD FORMAT (Deprecated keys from Season 4 or earlier):
Combo Master|Achieved 4x score multiplier!|2025-10-30 20:05:14
Boss Novice|Defeated Cheese King - First Victory!|2025-10-30 20:05:28
Phoenix Hunter|Destroyed 10 Phoenix birds!|2025-11-02 18:54:53
Ultimate Survivor|Survived for 20 minutes!|2025-11-05 15:24:02

-- NEW FORMAT (Season 5 proper keys):
killStreak8|Killing Spree|2025-11-04 07:07:17
firstKill|First Blood|2025-11-04 07:07:17
killStreak15|Rampage|2025-11-04 07:07:17
score2500|Getting Started|2025-11-04 07:07:17
killStreak25|Unstoppable|2025-11-04 07:07:17
comboMaster8|Combo Master|2025-11-04 07:07:17       ← DUPLICATE OF OLD "Combo Master"
bossKiller1|Boss Novice|2025-11-04 07:07:17         ← DUPLICATE OF OLD "Boss Novice"
phoenixHunter|Phoenix Hunter|2025-11-04 07:07:17    ← DUPLICATE OF OLD "Phoenix Hunter"
```

**DUPLICATES IDENTIFIED:**
1. "Combo Master" (old) vs "comboMaster8" (new) - SAME ACHIEVEMENT
2. "Boss Novice" (old) vs "bossKiller1" (new) - SAME ACHIEVEMENT
3. "Phoenix Hunter" (old) vs "phoenixHunter" (new) - SAME ACHIEVEMENT
4. "Ultimate Survivor" (old) - NO NEW KEY FOUND YET

---

## 📊 **DATA DISCREPANCY:**

### **Why 35 Cards Instead of 28:**

**Database has:**
- **28 DEFINITIONS** (correct, from `ACHIEVEMENT_DEFINITIONS`)
- **12 USER UNLOCKS** for Narrrf:
  - **4 old format** (deprecated keys)
  - **8 new format** (proper Season 5 keys)

**Profile page shows:**
- **28 DEFINITIONS** (loaded from API)
- **+7 EXTRA USER UNLOCKS** (old format duplicates not in DEFINITIONS)
- **= 35 TOTAL CARDS DISPLAYED**

### **Why This Happens:**

The API logic (lines 114-129 of `get-space-invaders-achievements.php`):
```php
// Add locked achievements
foreach ($allAchievements as $key => $achievement) {
    if (!in_array($key, $unlockedKeys)) {
        // Add as locked achievement
    }
}
```

**PROBLEM:** This logic assumes:
- ALL user achievements have keys that exist in DEFINITIONS
- BUT: Old format achievements DON'T exist in DEFINITIONS
- SO: They get added as EXTRA unlocked achievements
- THEN: All 28 definitions ALSO get added (some as locked, some as unlocked)
- RESULT: Duplicates displayed (old format + new format)

---

## 🎮 **IMPACT ON ALL 3 GAMES:**

### **Need to Check:**

**1. Space Invaders:**
- ✅ **Confirmed:** Has old format duplicates
- ❌ **Affected Users:** Narrrf, possibly others who played before Season 5
- 🔧 **Fix Required:** Clean up old format achievements

**2. Tetris:**
- ⏳ **Status:** NEED TO CHECK for old format achievements
- ❓ **Potential Duplicates:** Unknown
- 🧪 **Test Required:** Query Narrrf's Tetris achievements

**3. Snake:**
- ⏳ **Status:** NEED TO CHECK for old format achievements
- ❓ **Potential Duplicates:** Unknown
- 🧪 **Test Required:** Query Narrrf's Snake achievements

---

## 🔍 **OLD FORMAT ACHIEVEMENT KEYS (Space Invaders):**

Based on database evidence, these are the **deprecated achievement keys**:

```
"Combo Master"        → Should be "comboMaster8"
"Boss Novice"         → Should be "bossKiller1"
"Phoenix Hunter"      → Should be "phoenixHunter"
"Ultimate Survivor"   → Should be "survivor10min" (?)
```

**These keys DO NOT exist in `ACHIEVEMENT_DEFINITIONS` table!**

---

## 🧪 **VERIFICATION QUERIES:**

### **Check All Users for Old Format Achievements:**

```sql
-- Space Invaders - Find old format achievements
SELECT user_id, achievement_key, COUNT(*) as count
FROM tbl_space_invaders_achievements
WHERE user_id != 'ACHIEVEMENT_DEFINITIONS'
  AND achievement_key NOT IN (
    SELECT achievement_key 
    FROM tbl_space_invaders_achievements 
    WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'
  )
GROUP BY user_id, achievement_key
ORDER BY user_id, achievement_key;

-- Tetris - Same check
SELECT user_id, achievement_key, COUNT(*) as count
FROM tbl_tetris_achievements
WHERE user_id != 'ACHIEVEMENT_DEFINITIONS'
  AND achievement_key NOT IN (
    SELECT achievement_key 
    FROM tbl_tetris_achievements 
    WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'
  )
GROUP BY user_id, achievement_key
ORDER BY user_id, achievement_key;

-- Snake - Same check
SELECT user_id, achievement_key, COUNT(*) as count
FROM tbl_snake_achievements
WHERE user_id != 'ACHIEVEMENT_DEFINITIONS'
  AND achievement_key NOT IN (
    SELECT achievement_key 
    FROM tbl_snake_achievements 
    WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'
  )
GROUP BY user_id, achievement_key
ORDER BY user_id, achievement_key;
```

---

## 🔧 **PROPOSED SOLUTIONS:**

### **Option 1: DELETE Old Format Achievements (Recommended)**
**Pros:**
- Clean database
- No duplicates displayed
- Correct counts
- Simple fix

**Cons:**
- Users lose old unlock dates (cosmetic only)
- They will re-unlock with new keys

**SQL:**
```sql
-- Delete old format Space Invaders achievements
DELETE FROM tbl_space_invaders_achievements
WHERE user_id != 'ACHIEVEMENT_DEFINITIONS'
  AND achievement_key NOT IN (
    SELECT achievement_key 
    FROM tbl_space_invaders_achievements 
    WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'
  );
```

### **Option 2: MIGRATE Old Keys to New Keys**
**Pros:**
- Preserve unlock dates
- No re-unlocking needed
- Better UX

**Cons:**
- Complex mapping required
- Risk of data errors
- Time-consuming

**SQL:**
```sql
-- Map old keys to new keys
UPDATE tbl_space_invaders_achievements
SET achievement_key = CASE
  WHEN achievement_key = 'Combo Master' THEN 'comboMaster8'
  WHEN achievement_key = 'Boss Novice' THEN 'bossKiller1'
  WHEN achievement_key = 'Phoenix Hunter' THEN 'phoenixHunter'
  WHEN achievement_key = 'Ultimate Survivor' THEN 'survivor10min'
  ELSE achievement_key
END
WHERE user_id != 'ACHIEVEMENT_DEFINITIONS';
```

### **Option 3: API FILTER (Frontend Only)**
**Pros:**
- No database changes
- Quick fix

**Cons:**
- Doesn't fix root cause
- Still shows wrong data in database
- Confusion for admins

**Code:**
```php
// In get-space-invaders-achievements.php
$processedAchievements = [];
foreach ($achievements as $achievement) {
    // FILTER: Only include achievements with valid keys
    if (isset($allAchievements[$achievement['achievement_key']])) {
        $processedAchievements[] = /* ... */;
    }
}
```

---

## 🎯 **RECOMMENDED ACTION PLAN:**

### **Phase 1: INVESTIGATION (NOW)**
1. ✅ Identify old format achievements in Space Invaders
2. ⏳ Check Tetris for same issue
3. ⏳ Check Snake for same issue
4. ⏳ Count affected users across all 3 games
5. ⏳ Document all old → new key mappings

### **Phase 2: DECISION (NEXT)**
- Decide between DELETE vs MIGRATE approach
- Consider user impact
- Choose simplest reliable fix

### **Phase 3: IMPLEMENTATION**
- Write SQL migration/cleanup script
- Test on local database first
- Backup production database
- Run cleanup on production
- Verify results

### **Phase 4: VERIFICATION**
- Check all users' achievement counts
- Verify no duplicates displayed
- Confirm stats match reality
- Test on all 3 games

---

## 📝 **FILES TO INVESTIGATE:**

### **Database Tables:**
- `tbl_space_invaders_achievements` - Has old format duplicates
- `tbl_tetris_achievements` - Need to check
- `tbl_snake_achievements` - Need to check

### **API Files:**
- `api/user/get-space-invaders-achievements.php` - May need filter logic
- `api/user/get-tetris-achievements.php` - May need filter logic
- `api/user/user-game-missions.php` - Snake achievements (integrated)

### **Frontend:**
- `public/profile.html` - Achievement display modals
- `public/scripts/space-cheese-invaders.js` - Achievement unlock logic
- `public/scripts/tetris-scroll.js` - Achievement unlock logic
- `public/scripts/snake-scroll.js` - Achievement unlock logic

---

## 🧪 **NEXT STEPS:**

### **IMMEDIATE (Tonight):**
1. Run verification queries for all 3 games
2. Count total affected users
3. Document all old → new key mappings
4. Create cleanup SQL scripts
5. Test cleanup on local database

### **SHORT-TERM (This Week):**
1. Decide on DELETE vs MIGRATE approach
2. Backup production database
3. Run cleanup script on production
4. Verify all games show correct counts
5. Update documentation

---

## 🏆 **SUCCESS CRITERIA:**

### **Bug Fixed When:**
- ✅ Achievement modals show correct total count
- ✅ No duplicate achievements displayed
- ✅ Stats match actual unlocked achievements
- ✅ All 3 games verified and clean
- ✅ No user complaints about missing achievements

---

**BUG INVESTIGATION STARTED:** November 6, 2025 - Late Evening  
**ROOT CAUSE:** Old achievement keys from Season 4 still in database  
**SEVERITY:** HIGH (affects data integrity and user experience)  
**PRIORITY:** MEDIUM (not game-breaking, but needs fixing)  
**STATUS:** 🔍 **INVESTIGATION IN PROGRESS - AWAITING FULL ANALYSIS**  

**NEXT:** Run comprehensive verification queries across all 3 games!


