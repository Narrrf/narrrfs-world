# 🧩 LEVEL 2 TESTING CHECKLIST - REWARDS & ACHIEVEMENTS

**Created:** November 19, 2025  
**Purpose:** Complete testing checklist for Level 2 "The Spawn" rewards and achievements  
**Test Player:** Narrrf (Discord ID: 328601656659017732)

---

## 📊 LEVEL 2 REWARDS SUMMARY

### **Total DSPOINC Rewards: 320 DSPOINC** (Base, before role multipliers)

| Step | Trait | Base Reward | Description |
|------|-------|-------------|-------------|
| **Step 0** | `CHEESE_TEMPLE_LEVEL2_STEP0` | **+100 DSPOINC** | Stand on hidden cheese stone for 10 seconds |
| **Step 1** | `CHEESE_TEMPLE_LEVEL2_STEP1` | **+100 DSPOINC** | Pull the lever to unlock weapon gallery |
| **Step 2** | `CHEESE_TEMPLE_LEVEL2_STEP2` | **+120 DSPOINC** | Inspect all display rows (weapons, accessories, monsters, etc.) |

---

## 🏆 EXPECTED ACHIEVEMENTS (3D Puzzles Section)

After completing Level 2, the profile page should show:

### **Level 2: The Spawn**
- ✅ **Step 0 Complete** - `CHEESE_TEMPLE_LEVEL2_STEP0` trait unlocked
- ✅ **Step 1 Complete** - `CHEESE_TEMPLE_LEVEL2_STEP1` trait unlocked  
- ✅ **Step 2 Complete** - `CHEESE_TEMPLE_LEVEL2_STEP2` trait unlocked

**Total:** 3/3 achievements unlocked for Level 2

---

## 🎯 TESTING WORKFLOW

### **Step 0: Hidden Cheese Stone**
1. **Action:** Stand on the golden cheese stone platform for 10 seconds
2. **Expected Result:**
   - Platform moves down visually when standing on it
   - Countdown timer appears (10 seconds)
   - Sound plays when stepping on platform
   - After 10 seconds: Trait `CHEESE_TEMPLE_LEVEL2_STEP0` unlocked
   - **+100 DSPOINC** awarded (base, before role multiplier)
   - Lever appears at far wall
   - HUD shows hint to pull lever

3. **Verify:**
   - ✅ Trait appears in database (`tbl_user_traits`)
   - ✅ DSPOINC reward logged in `tbl_score_adjustments` with reason "Level 2 Step 0"
   - ✅ Balance increases by 100 (or 200 if VIP 2x multiplier)
   - ✅ Achievement appears on profile page under "3D Puzzles" → "Level 2: The Spawn"

### **Step 1: Pull the Lever**
1. **Action:** Walk to lever at far wall, press `E` key when close
2. **Expected Result:**
   - Lever texture changes (visual feedback)
   - Lever sound plays
   - Weapon gallery unlocks (all weapons/accessories become visible)
   - Player teleports to weapon gallery area
   - Trait `CHEESE_TEMPLE_LEVEL2_STEP1` unlocked
   - **+100 DSPOINC** awarded (base, before role multiplier)
   - HUD shows "Inspect Every Display" message
   - Inspection zones become active

3. **Verify:**
   - ✅ Trait appears in database (`tbl_user_traits`)
   - ✅ DSPOINC reward logged in `tbl_score_adjustments` with reason "Level 2 Step 1"
   - ✅ Balance increases by another 100 (or 200 if VIP 2x multiplier)
   - ✅ Achievement appears on profile page
   - ✅ All weapon models visible (W1, W2, W3, primary weapons, accessories, survival pack, old school weapons)

### **Step 2: Inspect All Display Rows**
1. **Action:** Walk through all inspection zones:
   - **Primary Weapon Rows** (left and right lanes)
   - **Accessory Corridor** (inner walkway)
   - **Survival Pack Rows** (4 lanes)
   - **Old School Armory** (2 rows)
   - **Monster Runway** (creature lineup)

2. **Expected Result:**
   - HUD shows progress: "X / Y zones inspected"
   - Each zone logs when visited (console: `🧱 [LEVEL 2] <zone> inspected`)
   - When all zones visited: Trait `CHEESE_TEMPLE_LEVEL2_STEP2` unlocked
   - **+120 DSPOINC** awarded (base, before role multiplier)
   - Portal appears at far wall
   - HUD updates to "Portal unlocked"

3. **Verify:**
   - ✅ Trait appears in database (`tbl_user_traits`)
   - ✅ DSPOINC reward logged in `tbl_score_adjustments` with reason "Level 2 Step 2"
   - ✅ Balance increases by 120 (or 240 if VIP 2x multiplier)
   - ✅ Achievement appears on profile page
   - ✅ Portal is visible and functional

### **Portal Completion**
1. **Action:** Walk into the portal
2. **Expected Result:**
   - Completion screen appears with options:
     - "Stay in Level 2" (restart Level 2)
     - "Back to Level 1"
     - "Go to Level 3" (if available)
   - "LEVEL UP!" sound plays
   - Portal effect animation

3. **Verify:**
   - ✅ Completion screen displays correctly
   - ✅ All options work as expected

---

## 💰 ROLE-BASED MULTIPLIER VERIFICATION

### **For VIP Holder (2x multiplier):**
- **Step 0:** 100 × 2 = **200 DSPOINC**
- **Step 1:** 100 × 2 = **200 DSPOINC**
- **Step 2:** 120 × 2 = **240 DSPOINC**
- **Total:** **640 DSPOINC** (instead of 320 base)

### **For Holder (1.5x multiplier):**
- **Step 0:** 100 × 1.5 = **150 DSPOINC**
- **Step 1:** 100 × 1.5 = **150 DSPOINC**
- **Step 2:** 120 × 1.5 = **180 DSPOINC**
- **Total:** **480 DSPOINC** (instead of 320 base)

### **For Normal User (1x multiplier):**
- **Step 0:** 100 × 1 = **100 DSPOINC**
- **Step 1:** 100 × 1 = **100 DSPOINC**
- **Step 2:** 120 × 1 = **120 DSPOINC**
- **Total:** **320 DSPOINC**

---

## 📋 DATABASE VERIFICATION QUERIES

### **Check Traits:**
```sql
SELECT trait_name, unlocked_at 
FROM tbl_user_traits 
WHERE user_id = '328601656659017732' 
  AND trait_name LIKE 'CHEESE_TEMPLE_LEVEL2%'
ORDER BY unlocked_at;
```

**Expected Results:**
- `CHEESE_TEMPLE_LEVEL2_STEP0` - unlocked after Step 0
- `CHEESE_TEMPLE_LEVEL2_STEP1` - unlocked after Step 1
- `CHEESE_TEMPLE_LEVEL2_STEP2` - unlocked after Step 2

### **Check DSPOINC Rewards:**
```sql
SELECT reason, amount, created_at 
FROM tbl_score_adjustments 
WHERE user_id = '328601656659017732' 
  AND reason LIKE '%Level 2%'
ORDER BY created_at;
```

**Expected Results:**
- "Level 2 Step 0" - 100 (or 200 if VIP)
- "Level 2 Step 1" - 100 (or 200 if VIP)
- "Level 2 Step 2" - 120 (or 240 if VIP)

### **Check Total Balance:**
```sql
SELECT SUM(score) as total_dspoinc 
FROM tbl_user_scores 
WHERE user_id = '328601656659017732';
```

**Expected:** Should increase by 320 (or 640 if VIP) after completing all 3 steps

---

## 🎮 PROFILE PAGE VERIFICATION

### **3D Puzzles Achievements Section:**
1. Navigate to profile page: `http://localhost/public/profile.html`
2. Click "3D Puzzles Achievements" button
3. Verify "Level 2: The Spawn" section shows:
   - ✅ **3 achievements** listed (Step 0, Step 1, Step 2)
   - ✅ All 3 show as **unlocked** (not grayed out)
   - ✅ Icons display correctly
   - ✅ Achievement names match trait names

### **Recent Score Changes Section:**
1. Scroll to "Recent Score Changes" section
2. Verify 3 entries appear (most recent first):
   - ✅ "Level 2 Step 2" - +120 (or +240 if VIP)
   - ✅ "Level 2 Step 1" - +100 (or +200 if VIP)
   - ✅ "Level 2 Step 0" - +100 (or +200 if VIP)
3. Verify timestamps are correct (recent)
4. Verify amounts match role multiplier

---

## 🚨 COMMON ISSUES TO WATCH FOR

### **Issue 1: Traits Not Unlocking**
- **Symptom:** Steps complete but traits don't appear in database
- **Check:** Console logs for `unlockLevel2Trait` errors
- **Fix:** Verify API endpoint `/api/user/unlock-trait.php` is accessible

### **Issue 2: DSPOINC Not Awarding**
- **Symptom:** Steps complete but balance doesn't increase
- **Check:** Console logs for `awardLevel2DspoincReward` errors
- **Fix:** Verify API endpoint `/api/dev/riddle-reward.php` is accessible and role multiplier is working

### **Issue 3: Role Multiplier Not Applied**
- **Symptom:** VIP user only gets 1x multiplier instead of 2x
- **Check:** Verify `tbl_user_roles` has correct role_name for user
- **Fix:** Check `api/dev/riddle-reward.php` `getRoleMultiplier()` function

### **Issue 4: Achievements Not Showing on Profile**
- **Symptom:** Traits in database but not on profile page
- **Check:** Verify `/api/user/get-3d-puzzles-achievements.php` returns Level 2 traits
- **Fix:** Check API response includes `CHEESE_TEMPLE_LEVEL2_*` traits

### **Issue 5: Inspection Zones Not Registering**
- **Symptom:** Can't complete Step 2 even after walking all rows
- **Check:** Console logs for `🧱 [LEVEL 2] <zone> inspected`
- **Fix:** Verify all inspection zones are properly registered in `level2State.inspectionZones`

---

## ✅ SUCCESS CRITERIA

**Level 2 testing is successful when:**

1. ✅ All 3 steps complete without errors
2. ✅ All 3 traits appear in database
3. ✅ All 3 DSPOINC rewards logged in `tbl_score_adjustments`
4. ✅ Total balance increases by correct amount (with role multiplier)
5. ✅ All 3 achievements appear on profile page under "3D Puzzles"
6. ✅ All 3 rewards appear in "Recent Score Changes" section
7. ✅ Portal appears and completion screen works
8. ✅ Role multiplier correctly applied (VIP gets 2x, etc.)

---

## 📝 TESTING NOTES

- **Test Environment:** Local development (localhost)
- **Test User:** Narrrf (Discord ID: 328601656659017732)
- **Expected Role:** VIP Holder (2x multiplier)
- **Expected Total Rewards:** 640 DSPOINC (320 base × 2)

---

**Last Updated:** November 19, 2025  
**Status:** Ready for Testing  
**Next:** Test Level 2 with Narrrf's account and verify all rewards/achievements

