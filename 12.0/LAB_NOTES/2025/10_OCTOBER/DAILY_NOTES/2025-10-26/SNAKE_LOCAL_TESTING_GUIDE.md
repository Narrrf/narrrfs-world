# 🐍 SNAKE ACHIEVEMENTS - LOCAL TESTING GUIDE

**Testing Date:** October 26, 2025 - 22:35  
**Status:** 📋 TESTING INSTRUCTIONS  
**Purpose:** Verify all 20 Snake achievements work with new thresholds  

---

## 🎯 **TESTING OBJECTIVE**

Verify that all 20 Snake achievements unlock at the correct thresholds and that the 8 removed achievements no longer trigger.

---

## 🔧 **SETUP INSTRUCTIONS**

### **Step 1: Open Local Snake Game**
```
http://localhost/public/profile.html
```

### **Step 2: Scroll to Snake Game**
- Find the "🐍 Snake Scroll" section
- Click "Start Game" button

### **Step 3: Open Browser Console**
- Press `F12` to open Developer Tools
- Go to "Console" tab
- Watch for achievement logs

---

## 🏆 **TESTING CHECKLIST - ALL 20 ACHIEVEMENTS**

### **CATEGORY 1: CHEESE-BASED (5 achievements)**

| Test | Achievement | Threshold | How to Test | Status |
|------|-------------|-----------|-------------|--------|
| 1 | first_cheese | 1 cheese | Eat 1 cheese | [ ] |
| 2 | cheese_collector | 5 cheese | Eat 5 cheeses | [ ] |
| 3 | cheese_hunter | 10 cheese | Eat 10 cheeses | [ ] |
| 4 | cheese_master | 25 cheese | Eat 25 cheeses | [ ] |
| 5 | cheese_legend | 75 cheese | Eat 75 cheeses (HARD!) | [ ] |

**Expected:** Popups show at 1, 5, 10, 25, 75 cheese

---

### **CATEGORY 2: SCORE-BASED (6 achievements)**

| Test | Achievement | Threshold | Cheese (VIP 2.0x) | Status |
|------|-------------|-----------|-------------------|--------|
| 6 | score_hunter | 200 DSPOINC | 10 cheese | [ ] |
| 7 | point_master | 500 DSPOINC | 25 cheese | [ ] |
| 8 | high_scorer | 1000 DSPOINC | 50 cheese | [ ] |
| 9 | snake_king | 1500 DSPOINC | 75 cheese | [ ] |
| 10 | score_legend | 2000 DSPOINC | 100 cheese | [ ] |
| 11 | score_god | 3500 DSPOINC | 175 cheese | [ ] |

**Expected:** Popups show at 200, 500, 1000, 1500, 2000, 3500 DSPOINC

**Note:** Test with VIP role for easier testing (2.0x multiplier)

---

### **CATEGORY 3: LEVEL-BASED (4 achievements)**

| Test | Achievement | Threshold | Status |
|------|-------------|-----------|--------|
| 12 | speed_demon | Level 5 | [ ] |
| 13 | level_master | Level 10 | [ ] |
| 14 | level_warrior | Level 15 | [ ] |
| 15 | level_champion | Level 20 | [ ] |

**Expected:** Popups show at Level 5, 10, 15, 20

---

### **CATEGORY 4: LENGTH-BASED (3 achievements)**

| Test | Achievement | Threshold | Status |
|------|-------------|-----------|--------|
| 16 | long_snake | 10 segments | [ ] |
| 17 | giant_snake | 25 segments | [ ] |
| 18 | mega_snake | 50 segments | [ ] |

**Expected:** Popups show at 10, 25, 50 segments

**Note:** Snake length = cheese eaten + 3 (starts at 3 segments)

---

### **CATEGORY 5: TIME-BASED (2 achievements)**

| Test | Achievement | Threshold | Status |
|------|-------------|-----------|--------|
| 19 | survivor | 2 minutes | [ ] |
| 20 | endurance_master | 5 minutes | [ ] |

**Expected:** Popups show after 2 minutes and 5 minutes of gameplay

---

## 🚨 **REMOVED ACHIEVEMENTS VERIFICATION**

### **These Should NOT Trigger:**

| Achievement | OLD Threshold | Should NOT Show |
|-------------|---------------|-----------------|
| ❌ game_starter | 1 game | NO POPUP |
| ❌ game_player | 5 games | NO POPUP |
| ❌ game_master | 10 games | NO POPUP |
| ❌ game_legend | 25 games | NO POPUP |
| ❌ snake_champion | 20k score + 50 length | NO POPUP |
| ❌ snake_ninja | perfectGame + 5k score | NO POPUP |
| ❌ perfectionist | perfectGame + 25 length | NO POPUP |
| ❌ ultimate_player | 50k + 100 length + 100 cheese | NO POPUP |

**Expected:** No popups for these 8 removed achievements, even if old thresholds would be met.

---

## 🧪 **TESTING PROCEDURE**

### **Quick Test (Score Achievements):**

1. **Start Snake game**
2. **Eat 10 cheese** → Check for `score_hunter` popup (200 DSPOINC)
3. **Eat 25 cheese** → Check for `point_master` popup (500 DSPOINC)
4. **Eat 50 cheese** → Check for `high_scorer` popup (1000 DSPOINC)
5. **Eat 75 cheese** → Check for `snake_king` popup (1500 DSPOINC)
6. **Eat 100 cheese** → Check for `score_legend` popup (2000 DSPOINC)

**Note:** With VIP 2.0x role, each cheese = 20 DSPOINC

---

### **Full Test (All 20 Achievements):**

**Game 1: Score & Cheese Achievements**
- Eat 1 cheese → `first_cheese` ✅
- Eat 5 cheese → `cheese_collector` ✅
- Eat 10 cheese → `cheese_hunter` + `score_hunter` (200) ✅
- Eat 25 cheese → `cheese_master` + `point_master` (500) ✅
- Eat 50 cheese → `high_scorer` (1000) ✅
- Eat 75 cheese → `snake_king` (1500) + `cheese_legend` ✅
- Eat 100 cheese → `score_legend` (2000) ✅

**Game 2: Length Achievements**
- Grow to 10 segments → `long_snake` ✅
- Grow to 25 segments → `giant_snake` ✅
- Grow to 50 segments → `mega_snake` ✅

**Game 3: Level Achievements**
- Reach Level 5 → `speed_demon` ✅
- Reach Level 10 → `level_master` ✅
- Reach Level 15 → `level_warrior` ✅
- Reach Level 20 → `level_champion` ✅

**Game 4: Time Achievements**
- Survive 2 minutes → `survivor` ✅
- Survive 5 minutes → `endurance_master` ✅

**Game 5: Legendary Challenge**
- Eat 175 cheese → `score_god` (3500 DSPOINC) ✅ (LEGENDARY!)

---

## 🔍 **CONSOLE VERIFICATION**

### **Expected Console Logs:**

**When Achievement Unlocks:**
```
🏆 Checking Snake achievements... {cheeseEaten: 10, score: 200, ...}
🎯 Achievement condition met: score_hunter true
🔍 checkAndUnlockAchievement called for: score_hunter
📡 API Response: {success: true, achievements: [...]}
✅ Achievement not unlocked, showing notification
🏆 Snake achievement unlocked: score_hunter
🎉 showAchievementNotification called: {key: "score_hunter", ...}
📝 Popup added to array. Total popups: 1
```

**When Already Unlocked:**
```
⚠️ Achievement already unlocked: score_hunter
```

---

## 📊 **DATABASE VERIFICATION**

### **Check Local Database:**

```powershell
# Check Snake achievement definitions (should be 20 after first unlock)
sqlite3 db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';"

# Check user unlocks (should match unlocked count)
sqlite3 db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_snake_achievements WHERE user_id = 'YOUR_DISCORD_ID' AND unlocked_at IS NOT NULL;"

# Check specific achievement thresholds
sqlite3 db/narrrf_world.sqlite "SELECT achievement_key, achievement_description FROM tbl_snake_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS' ORDER BY achievement_key;"
```

**Expected:** All 20 achievement definitions with correct descriptions (200, 500, 1000, 1500, 2000, 3500 DSPOINC)

---

## ✅ **SUCCESS CRITERIA**

### **Testing Passes When:**
- ✅ All 20 achievements can be unlocked
- ✅ Achievement popups show at correct thresholds
- ✅ Database saves achievements correctly
- ✅ Profile page shows "20 total" achievements
- ✅ No removed achievements trigger
- ✅ All score thresholds match documentation (200-3500)

### **Testing Fails When:**
- ❌ Any achievement doesn't unlock at correct threshold
- ❌ Removed achievements still trigger
- ❌ Database saves incorrect definitions
- ❌ Profile page shows wrong total count
- ❌ Score thresholds don't match

---

## 🚀 **DEPLOYMENT READINESS**

### **After Local Testing Success:**
1. ✅ Create testing results document
2. ✅ Update SUNDAY_SESSION_STATUS.md
3. ✅ Update QUICK_STATUS.md
4. ✅ Commit changes with descriptive message
5. ✅ Push to render-deploy branch
6. ✅ Verify auto-deployment
7. ✅ Test on live site

---

**🐍 READY TO TEST SNAKE ACHIEVEMENTS LOCALLY!**

**Status:** Testing guide complete  
**Next:** Run local tests and verify all 20 achievements  
**Expected Time:** ~30 minutes for full testing  

---

**Guide Created:** October 26, 2025 - 22:35  
**Maintained By:** Cursor LLM 12.0  
**Following:** Tetris testing success model

