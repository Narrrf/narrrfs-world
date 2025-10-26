# 🎮 GAME SCORING SYSTEM RULES - CRITICAL REFERENCE

## 🚨 **CRITICAL RULE: NEVER FORGET THIS SYSTEM!**

**File Updated:** September 14, 2025  
**Purpose:** Document the critical game scoring system that powers mission status  
**Status:** ✅ **ACTIVE - VERIFIED AND WORKING**  

---

## 🎯 **THE 5 GAMES AND THEIR TABLE DEPENDENCIES**

### **1. Tetris** ✅
- **Saves to:** `tbl_tetris_scores` (game: 'tetris')
- **Field:** `discord_id` (contains Discord ID)
- **Mission Status:** ✅ Working
- **Admin Interface:** ✅ Working

### **2. Snake** ✅
- **Saves to:** `tbl_tetris_scores` (game: 'snake') 
- **Field:** `discord_id` (contains Discord ID)
- **Mission Status:** ✅ Working
- **Admin Interface:** ✅ Working

### **3. Space Invaders** ✅
- **Saves to:** `tbl_tetris_scores` (game: 'space_invaders')
- **Field:** `discord_id` (contains Discord ID)
- **Mission Status:** ✅ Working
- **Admin Interface:** ✅ Working

### **4. Cheese Hunt** ✅
- **Saves to:** `tbl_cheese_clicks` (different table)
- **Field:** `user_wallet` (contains Discord ID)
- **Mission Status:** ✅ Working
- **Admin Interface:** ✅ Working

### **5. Discord Race** ✅
- **Saves to:** `tbl_race_participants` (different table)
- **Field:** `user_id` (contains Discord ID)
- **Mission Status:** ✅ Working
- **Admin Interface:** ✅ Working

---

## 🚨 **CRITICAL RULES:**

1. **ALWAYS use `discord_id` for Tetris, Snake, and Space Invaders SCORES**
2. **ALWAYS use `user_wallet` for Cheese Hunt SCORES**
3. **ALWAYS use `user_id` for Discord Race SCORES**
4. **ALWAYS use the correct table for each game**
5. **NEVER assume all games use the same field name**
6. **🚨 CRITICAL:** Frontend calculates final DSPOINC - Backend MUST NOT multiply again
7. **🚨 CRITICAL:** Role multipliers applied in frontend - Backend uses score as-is

---

## 📊 **SCORING SYSTEM ARCHITECTURE**

### **Dual Table Strategy:**
- **`tbl_tetris_scores`** - For mission status display and game tracking (Tetris, Snake, Space Invaders)
- **`tbl_user_scores`** - For DSPOINC balance and rewards
- **`tbl_score_adjustments`** - For admin interface and audit trail
- **`tbl_cheese_clicks`** - For Cheese Hunt game tracking
- **`tbl_race_participants`** - For Discord Race participation tracking

### **API Response Structure:**
```json
{
  "success": true,
  "data": {
    "games": {
      "tetris": { "season_data": {...} },
      "snake": { "season_data": {...} },
      "space_invaders": { "season_data": {...} },
      "cheese_hunt": { "current_data": {...} },
      "discord_race": { "race_data": {...} }
    }
  }
}
```

---

## 🎯 **SUCCESS METRICS**

### **Mission Status Should Show:**
- **Tetris:** ✅ Games played, best score, DSPOINC earned
- **Snake:** ✅ Games played, best score, DSPOINC earned  
- **Space Invaders:** ✅ Games played, best score, DSPOINC earned
- **Cheese Hunt:** ✅ Total clicks, quest clicks, DSPOINC earned
- **Discord Race:** ✅ Total races, wins, DSPOINC earned

### **Total Games Played:**
- **Should show:** 5/5 Games Played
- **Should NOT show:** 2/5 or 3/5 Games Played

---

## 🚀 **CURRENT STATUS: FULLY OPERATIONAL**

### **✅ Working Systems:**
- **Mission Status API:** ✅ Returning correct data for all 5 games
- **User Profile Pages:** ✅ Displaying correct mission status
- **Database Tables:** ✅ Properly synchronized
- **Score System:** ✅ DSPOINC rewards working correctly
- **Backend APIs:** ✅ All APIs returning correct data
- **Admin Interface:** ✅ Showing all data correctly

---

## 🚨 **CRITICAL BACKEND FIX - OCTOBER 26, 2025**

### **THE DOUBLE MULTIPLICATION BUG:**

**PROBLEM DISCOVERED:**
Backend `save-score.php` was multiplying Snake scores by 10 (`points_per_cheese`), even though frontend already calculated final DSPOINC with role multipliers.

**Example:**
- Frontend: 1 cheese × 10 base × 1.5 Holder multiplier = 15 DSPOINC ✅
- Backend: 15 × 10 (`points_per_cheese`) = 150 DSPOINC ❌ **WRONG!**

**THE FIX:**
```php
// ❌ OLD (WRONG):
} elseif ($game === 'snake') {
    $pointsPerUnit = $seasonSettings['points_per_cheese'] ?? 1;
    $unit = 'cheese';
    $dspoinc_score = $raw_score * $pointsPerUnit; // DOUBLE MULTIPLICATION!
}

// ✅ NEW (CORRECT):
} elseif ($game === 'snake') {
    // Frontend now calculates DSPOINC (like Tetris and Space Invaders)
    // Don't multiply again - use score as-is (already includes role bonus)
    $pointsPerUnit = 1; // No multiplication needed
    $unit = 'dspoinc';
    $dspoinc_score = $raw_score; // Use score directly
}
```

**CRITICAL RULE:**
- ✅ **Tetris:** Frontend calculates DSPOINC → Backend uses `$pointsPerUnit = 1`
- ✅ **Snake:** Frontend calculates DSPOINC → Backend uses `$pointsPerUnit = 1`
- ✅ **Space Invaders:** Frontend calculates DSPOINC → Backend uses `$pointsPerUnit = 1`

**WHY THIS MATTERS:**
- Frontend applies role multipliers (VIP 2.0x, Holder 1.5x, etc.)
- Score sent to backend is FINAL DSPOINC amount
- Backend MUST NOT multiply again or it doubles the score

**VERIFICATION CHECKLIST:**
Before adding new games, verify:
- [ ] Frontend calculates final DSPOINC with role multipliers
- [ ] Backend `save-score.php` uses `$pointsPerUnit = 1` for that game
- [ ] Backend uses `$dspoinc_score = $raw_score` (no multiplication)
- [ ] Test with ALL role multipliers
- [ ] Verify database shows correct amounts

---

**Last Updated:** October 26, 2025  
**Status:** ✅ **VERIFIED AND WORKING**  
**Source:** Master Ruleset - Single Source of Truth  
**Critical Fix:** Backend no longer double-multiplies Snake scores