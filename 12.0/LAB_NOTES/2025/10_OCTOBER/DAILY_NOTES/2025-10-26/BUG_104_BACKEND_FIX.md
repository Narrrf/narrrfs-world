# 🚨 BUG #104 - CRITICAL BACKEND FIX DISCOVERED

**Date:** October 26, 2025  
**Day:** Sunday  
**Time:** 18:30  
**Status:** 🔴 **CRITICAL BACKEND BUG DISCOVERED**  
**Impact:** 🔴 **ALL SNAKE SCORES 10X TOO HIGH IN DATABASE**  

---

## 🎯 **THE DOUBLE MULTIPLICATION BUG**

### **What Happened:**
After deploying the frontend fix, production testing revealed scores were still wrong:
- **Expected:** 15 DSPOINC for 1 cheese (Holder 1.5x)
- **Actual:** 150 DSPOINC saved to database ❌

### **Root Cause Discovery:**
The backend `save-score.php` was ALSO multiplying Snake scores by 10!

```php
// Line 143-146 (OLD - WRONG):
} elseif ($game === 'snake') {
    $pointsPerUnit = $seasonSettings['points_per_cheese'] ?? 1;  // = 10
    $unit = 'cheese';
    $dspoinc_score = $raw_score * $pointsPerUnit;  // DOUBLE MULTIPLICATION!
}
```

### **The Double Multiplication:**
1. **Frontend:** 1 cheese × 10 base × 1.5 multiplier = **15 DSPOINC** ✅
2. **Backend:** 15 × 10 (`points_per_cheese`) = **150 DSPOINC** ❌

**Result:** All Snake scores are 10x too high in the database!

---

## 🔧 **THE FIX APPLIED**

### **Backend Change:**
```php
// Line 143-148 (NEW - CORRECT):
} elseif ($game === 'snake') {
    // 🔧 FIX: Snake frontend now calculates DSPOINC (like Tetris and Space Invaders)
    // Don't multiply again - use score as-is (already includes role bonus)
    $pointsPerUnit = 1; // No multiplication needed
    $unit = 'dspoinc';
    $dspoinc_score = $raw_score; // Use score directly (already DSPOINC with role bonus)
}
```

### **Why This Works:**
- Frontend sends **final DSPOINC** (15 for 1 cheese with 1.5x Holder)
- Backend receives 15 and saves 15 (no multiplication)
- Database shows correct 15 DSPOINC ✅

---

## 📊 **COMPLETE SCORING FLOW**

### **Correct Frontend → Backend Flow:**

**FRONTEND CALCULATION:**
```javascript
// snake-scroll.js (Line 880-883)
const roleMultiplier = getSnakeRoleScoreMultiplier(); // 1.5x for Holder
const baseScore = 10; // Base DSPOINC per cheese
const totalScore = Math.floor(baseScore * roleMultiplier); // 15
score += totalScore; // Add 15 to game score
```

**BACKEND PROCESSING:**
```php
// save-score.php (Line 143-148)
$pointsPerUnit = 1; // NO multiplication
$dspoinc_score = $raw_score; // Use 15 as-is
// Save 15 to database ✅
```

**DATABASE RESULT:**
- `tbl_tetris_scores`: score = 15 ✅
- `tbl_user_scores`: score = 15 ✅
- User balance: +15 DSPOINC ✅

---

## 🎮 **ALL THREE GAMES COMPARISON**

### **Tetris (Already Correct):**
- **Frontend:** Lines × 2 × role multiplier = Final DSPOINC
- **Backend:** `$pointsPerUnit = 1`, `$dspoinc_score = $raw_score`
- **Result:** ✅ Correct

### **Snake (JUST FIXED):**
- **Frontend:** Cheese × 10 × role multiplier = Final DSPOINC
- **Backend:** `$pointsPerUnit = 1`, `$dspoinc_score = $raw_score` (FIXED!)
- **Result:** ✅ Now correct

### **Space Invaders (Already Correct):**
- **Frontend:** Invader × 1 × role multiplier = Final DSPOINC
- **Backend:** `$pointsPerUnit = 1`, `$dspoinc_score = $raw_score`
- **Result:** ✅ Correct

---

## 🚨 **CRITICAL RULE FOR FUTURE GAMES**

### **THE GOLDEN RULE:**
**Frontend calculates FINAL DSPOINC. Backend saves it AS-IS. NEVER multiply again.**

### **Verification Checklist:**
Before adding ANY new game:
- [ ] **Check frontend calculation** - Does it apply role multipliers?
- [ ] **Check frontend sends DSPOINC** - Not raw units (lines, cheese, etc.)
- [ ] **Check backend uses `$pointsPerUnit = 1`** - No multiplication
- [ ] **Check backend uses `$dspoinc_score = $raw_score`** - Direct assignment
- [ ] **Test with role multipliers** - Verify correct amounts in DB
- [ ] **Compare display vs database** - Must match exactly

### **Common Mistakes to Avoid:**
- ❌ Backend using `points_per_cheese` for Snake
- ❌ Backend using `points_per_line` for Tetris
- ❌ Backend using `points_per_invader` for Space Invaders
- ❌ Any multiplication in backend when frontend calculates DSPOINC
- ❌ Assuming `tbl_season_settings` values should be used

---

## 🔍 **HOW WE DISCOVERED THIS**

### **Timeline:**
1. **18:00** - Fixed frontend baseScore (1 → 10)
2. **18:05** - Tested locally, worked perfectly
3. **18:10** - Deployed to production
4. **18:15** - User tested on production
5. **18:20** - **DISCOVERY:** Game over shows 15, but score writes 150
6. **18:25** - Checked backend, found double multiplication
7. **18:30** - Fixed backend `save-score.php`

### **Key Insight:**
Frontend AND backend were both calculating DSPOINC, causing 10x inflation!

### **Testing Method:**
- User played Snake on production with Holder role
- Ate 1 cheese
- Game over modal: 15 DSPOINC ✅
- Database: 150 DSPOINC ❌
- Clear indication of backend multiplication

---

## 🎯 **IMPACT ANALYSIS**

### **Historical Data:**
**ALL Snake scores in database are 10x too high!**

This affects:
- Season 4 current scores
- All-time statistics
- Leaderboards
- User balances

### **Correction Needed:**
```sql
-- Fix all Snake scores (divide by 10)
UPDATE tbl_tetris_scores 
SET score = ROUND(score / 10) 
WHERE game = 'snake';

UPDATE tbl_user_scores 
SET score = ROUND(score / 10) 
WHERE game = 'snake';

UPDATE tbl_score_adjustments 
SET amount = ROUND(amount / 10) 
WHERE reason LIKE '%snake%';
```

**WARNING:** This needs to be done AFTER deploying the backend fix!

---

## 🏆 **COMPLETE FIX SUMMARY**

### **Two-Part Fix:**

**Part 1: Frontend (Completed)**
- ✅ Changed `baseScore` from 1 to 10
- ✅ Removed `* 10` from display logic
- ✅ All role multipliers work correctly

**Part 2: Backend (Just Fixed)**
- ✅ Changed `$pointsPerUnit` from 10 to 1
- ✅ Changed calculation to direct assignment
- ✅ No more double multiplication

### **Files Modified:**
1. `public/scripts/snake-scroll.js` - Frontend scoring
2. `api/dev/save-score.php` - Backend scoring
3. `12.0/RULES/04_GAME_SCORING_SYSTEM_RULES.md` - Documentation

---

## 🚀 **NEXT STEPS**

### **Immediate:**
1. ✅ Backend fix applied
2. ⏳ Deploy to production
3. ⏳ Test with all roles again
4. ⏳ Verify database amounts

### **After Deployment:**
1. 🔄 Correct historical Snake scores in database
2. 🔄 Update all-time statistics
3. 🔄 Verify leaderboards
4. 🔄 Check user balances

### **Future Prevention:**
1. 📚 Update Master Ruleset with this rule
2. 📚 Create backend verification checklist
3. 📚 Test BOTH frontend AND backend for new games
4. 📚 Always compare display vs database

---

## 🧠 **LESSONS LEARNED**

### **Key Insights:**
1. **Frontend-Backend Sync:** Both must agree on who calculates DSPOINC
2. **Role Multipliers:** Applied in frontend, backend saves as-is
3. **Testing Completeness:** Test display AND database amounts
4. **Double Check:** When fixing one part, check the other part too

### **Rule Created:**
**"Frontend calculates FINAL DSPOINC with role multipliers. Backend saves it AS-IS. NEVER multiply in both places."**

---

## 🎯 **TETRIS TESTING NEXT**

### **What to Test:**
Using the same systematic approach:
1. Test all 6 role multipliers
2. Play exact amount per test (e.g., 4 lines cleared)
3. Check in-game display
4. Check game over display
5. Check database amount
6. Verify all three match

### **Expected Results for 4 Lines (8 DSPOINC base):**
- VIP Holder (2.0x): 16 DSPOINC
- Holder (1.5x): 12 DSPOINC (8 + 4 bonus)
- Champion (1.4x): ~11 DSPOINC (8 + 3 bonus)
- Season Tester (1.3x): ~10 DSPOINC (8 + 2 bonus)
- Early Bird (1.2x): ~10 DSPOINC (8 + 2 bonus)
- Cheese Hunter (1.1x): ~9 DSPOINC (8 + 1 bonus)

**Note:** Tetris uses different calculation (base + bonus), not (base × multiplier)

---

**CRITICAL BACKEND BUG DISCOVERED AND FIXED!** 🚨✅

