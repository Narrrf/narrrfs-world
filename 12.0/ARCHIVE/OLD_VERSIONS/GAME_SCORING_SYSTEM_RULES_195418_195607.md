# 🎮 GAME SCORING SYSTEM RULES - CRITICAL REFERENCE

## 🚨 **CRITICAL RULE: NEVER FORGET THIS SYSTEM!**

**File Created:** 2025-01-28  
**Purpose:** Document the critical game scoring system that powers mission status  
**Status:** ACTIVE - MUST FOLLOW FOR ALL FUTURE DEVELOPMENT  

---

## 🎯 **THE 5 GAMES AND THEIR TABLE DEPENDENCIES**

### **1. Tetris** ✅
- **Saves to:** `tbl_tetris_scores` (game: 'tetris')
- **Field:** `discord_id`
- **Mission Status:** ✅ Working
- **Admin Interface:** ✅ Working

### **2. Snake** ✅
- **Saves to:** `tbl_tetris_scores` (game: 'snake') 
- **Field:** `discord_id`
- **Mission Status:** ✅ **FIXED** - Now saves to correct table
- **Admin Interface:** ✅ Working

### **3. Space Invaders** ✅
- **Saves to:** `tbl_tetris_scores` (game: 'space_invaders')
- **Field:** `discord_id`
- **Mission Status:** ✅ **FIXED** - Now saves to correct table
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

## 🔧 **CRITICAL FIX IMPLEMENTED (2025-01-28)**

### **Problem Discovered:**
Snake and Space Invaders were only saving to `tbl_user_scores` and `tbl_score_adjustments`, but NOT to `tbl_tetris_scores` where the mission status API looks for them.

### **Root Cause:**
The `save-score.php` API was designed to only save Tetris to `tbl_tetris_scores`, while Snake and Space Invaders only went to `tbl_user_scores`.

### **Solution Applied:**
Modified `save-score.php` to ensure Snake and Space Invaders ALSO save to `tbl_tetris_scores` for mission status visibility.

### **Code Location:**
`narrrfs-world/api/dev/save-score.php` lines 204-220

---

## 📊 **SCORING SYSTEM ARCHITECTURE**

### **Dual Table Strategy:**
- **`tbl_tetris_scores`** - For mission status display and game tracking
- **`tbl_user_scores`** - For DSPOINC balance and rewards
- **`tbl_score_adjustments`** - For admin interface and audit trail

### **Why This Matters:**
- Mission status API queries `tbl_tetris_scores` for Tetris, Snake, Space Invaders
- If games don't save there, mission status shows 0 games played
- Users see scores in admin but not in their profile mission status
- This creates the "phantom score" problem - scores exist but are invisible

---

## ⚠️ **FUTURE DEVELOPMENT RULES**

### **NEVER change the save-score.php logic without ensuring:**
1. **All 5 games save to their required tables**
2. **Mission status API can find the data**
3. **DSPOINC calculations remain consistent**
4. **Admin interface continues to show all data**

### **If adding new games:**
1. **Check where mission status API looks for data**
2. **Ensure save-score.php saves to correct tables**
3. **Test mission status updates immediately**
4. **Document the table dependencies here**

### **Testing Checklist:**
- [ ] Play the game
- [ ] Check if score appears in admin interface
- [ ] Check if score appears in mission status
- [ ] Verify data is in the correct table
- [ ] Test with multiple users

---

## 🚨 **COMMON MISTAKES TO AVOID**

### **❌ DON'T:**
- Change save-score.php without testing mission status
- Assume all games use the same table
- Forget to check table dependencies
- Modify scoring logic without understanding the full system

### **✅ DO:**
- Always test mission status after changes
- Document table dependencies for new games
- Use the dual table strategy consistently
- Test with real users and real data

---

## 🔍 **DEBUGGING THE SYSTEM**

### **If Mission Status Shows 0 Games:**
1. **Check `tbl_tetris_scores`** - Are scores being saved there?
2. **Check `save-score.php`** - Is it saving to the right tables?
3. **Check API response** - Is the mission status API returning data?
4. **Check frontend** - Is the data being displayed correctly?

### **If Scores Exist in Admin But Not Mission Status:**
1. **Check table mapping** - Are games saving to the right tables?
2. **Check field names** - Are the correct fields being queried?
3. **Check game names** - Are the game identifiers consistent?

---

## 📝 **CHANGE LOG**

### **2025-01-28: Critical Fix Applied**
- **Issue:** Snake and Space Invaders not showing in mission status
- **Root Cause:** Not saving to `tbl_tetris_scores`
- **Solution:** Modified `save-score.php` to save to both tables
- **Result:** Mission status now shows all 5 games correctly

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

**Remember: This system powers the entire mission status display. Breaking it breaks user experience! 🚨**
