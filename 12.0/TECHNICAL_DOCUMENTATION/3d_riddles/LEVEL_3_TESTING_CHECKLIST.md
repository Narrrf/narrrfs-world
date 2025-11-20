# 🏹 LEVEL 3 TESTING CHECKLIST - REWARDS & ACHIEVEMENTS

**Created:** November 19, 2025  
**Purpose:** Complete testing checklist for Level 3 "The Hunt" rewards and achievements  
**Test Player:** Narrrf (Discord ID: 328601656659017732)  
**Role:** VIP Holder (2x multiplier)

---

## 📊 LEVEL 3 REWARDS SUMMARY

### **Total DSPOINC Rewards: 600 DSPOINC** (Base, before role multipliers)

| Step | Action | Base Reward | Description |
|------|--------|-------------|-------------|
| **Step 0** | Stand on cheese stone (10 seconds) | **+100 DSPOINC** | Unlock the hunt |
| **Step 1** | Catch 5 monsters (Demon, Frog, Orc, Dino, Ninja) | **+250 DSPOINC** | 5 × 50 DSPOINC per monster |
| **Step 2** | Catch 5 more monsters (Blue Demon, Mushroom King, Tribal, Alien, Yeti) | **+250 DSPOINC** | 5 × 50 DSPOINC per monster |

**Total Base:** 600 DSPOINC (100 + 250 + 250)

---

## 🏆 EXPECTED ACHIEVEMENTS (3D Puzzles Section)

After completing Level 3, the profile page should show:

### **Level 3: The Hunt**
- ✅ **Step 0 Complete** - `CHEESE_TEMPLE_LEVEL3_STEP0` trait unlocked
- ✅ **Step 1 Complete** - `CHEESE_TEMPLE_LEVEL3_STEP1` trait unlocked  
- ✅ **Step 2 Complete** - `CHEESE_TEMPLE_LEVEL3_STEP2` trait unlocked

**Total:** 3/3 achievements unlocked for Level 3

---

## 💰 ROLE-BASED MULTIPLIER (VIP 2x)

### **For Narrrf (VIP Holder - 2x multiplier):**

| Step | Base Reward | With 2x Multiplier | Total |
|------|-------------|-------------------|-------|
| **Step 0** | 100 DSPOINC | × 2.0 | **200 DSPOINC** |
| **Step 1** | 250 DSPOINC (5 × 50) | × 2.0 | **500 DSPOINC** |
| **Step 2** | 250 DSPOINC (5 × 50) | × 2.0 | **500 DSPOINC** |
| **GRAND TOTAL** | **600 DSPOINC** | × 2.0 | **1,200 DSPOINC** |

### **Per Monster Reward (VIP 2x):**
- Each monster: 50 × 2.0 = **100 DSPOINC per monster**
- 10 monsters total = **1,000 DSPOINC** (instead of 500 base)

---

## 🎯 TESTING WORKFLOW

### **Step 0: Hidden Cheese Stone**
1. **Action:** Find and stand on the hidden cheese stone platform for 10 seconds
2. **Expected Result:**
   - Platform moves down visually when standing on it
   - Countdown timer appears (10 seconds)
   - Sound plays when stepping on platform
   - After 10 seconds: Trait `CHEESE_TEMPLE_LEVEL3_STEP0` unlocked
   - **+200 DSPOINC** awarded (VIP 2x: 100 × 2)
   - First monster (Demon) spawns at random location
   - Toast: "Step 1 begins! Hunt 5 monsters!"

3. **Verify:**
   - ✅ Trait appears in database (`tbl_user_traits`)
   - ✅ DSPOINC reward logged in `tbl_score_adjustments` with reason "Level 3 Step 0"
   - ✅ Balance increases by 200 (VIP 2x multiplier)
   - ✅ Achievement appears on profile page under "3D Puzzles" → "Level 3: The Hunt"
   - ✅ Demon monster spawns and starts moving

### **Step 1: Hunt First 5 Monsters**
1. **Monsters to Catch:**
   - Demon (Monster 1)
   - Frog (Monster 2)
   - Orc (Monster 3)
   - Dino (Monster 4)
   - Ninja (Monster 5)

2. **Action:** Stand within 2.5 units of each monster to catch it
3. **Expected Result for Each Monster:**
   - Monster disappears with scale animation
   - Capture sound plays
   - **+100 DSPOINC** awarded per monster (VIP 2x: 50 × 2)
   - Toast shows: "🎯 Step 1 - Monster X/5 caught! +100 DSPOINC"
   - Next monster spawns after 1.5 second delay
   - Progressive scaling: Each monster gets bigger (0.4x → 0.6x → 0.8x → 1.0x → 1.2x)

4. **After All 5 Monsters Caught:**
   - Trait `CHEESE_TEMPLE_LEVEL3_STEP1` unlocked
   - Toast: "🎯 Step 1 Complete! All 5 monsters caught! Starting Step 2..."
   - Step 2 begins automatically after 2 seconds
   - **Total Step 1 Rewards:** 500 DSPOINC (5 × 100 with VIP 2x)

5. **Verify:**
   - ✅ All 5 monster rewards logged in `tbl_score_adjustments`
   - ✅ Trait `CHEESE_TEMPLE_LEVEL3_STEP1` appears in database
   - ✅ Achievement appears on profile page
   - ✅ Step 2 monsters start spawning

### **Step 2: Hunt Second 5 Monsters**
1. **Monsters to Catch:**
   - Blue Demon (Monster 6)
   - Mushroom King (Monster 7)
   - Tribal (Monster 8)
   - Alien (Monster 9)
   - Yeti (Monster 10)

2. **Action:** Stand within 2.5 units of each monster to catch it
3. **Expected Result for Each Monster:**
   - Monster disappears with scale animation
   - Capture sound plays
   - **+100 DSPOINC** awarded per monster (VIP 2x: 50 × 2)
   - Toast shows: "🎯 Step 2 - Monster X/5 caught! +100 DSPOINC"
   - Next monster spawns after 1.5 second delay
   - Progressive scaling: Each monster gets bigger (0.4x → 0.6x → 0.8x → 1.0x → 1.2x)

4. **After All 5 Monsters Caught:**
   - Trait `CHEESE_TEMPLE_LEVEL3_STEP2` unlocked
   - Toast: "🎯 Step 2 Complete! All monsters hunted! The portal opens!"
   - Portal activates after 2 seconds
   - **Total Step 2 Rewards:** 500 DSPOINC (5 × 100 with VIP 2x)

5. **Verify:**
   - ✅ All 5 monster rewards logged in `tbl_score_adjustments`
   - ✅ Trait `CHEESE_TEMPLE_LEVEL3_STEP2` appears in database
   - ✅ Achievement appears on profile page
   - ✅ Portal becomes visible

### **Step 3: Portal Completion**
1. **Action:** Walk into the portal
2. **Expected Result:**
   - Completion screen appears with options:
     - "Go to Level 4" (if available)
     - "Back to Level 2"
     - "Back to Level 1"
     - "🧀 Stay in Level 3" (restart Level 3)
   - "LEVEL UP!" sound plays
   - Portal effect animation

3. **Verify:**
   - ✅ Completion screen displays correctly
   - ✅ All options work as expected
   - ✅ Sound plays correctly

---

## 💰 ROLE-BASED MULTIPLIER VERIFICATION

### **For Narrrf (VIP Holder - 2x multiplier):**
- **Step 0:** 100 × 2 = **200 DSPOINC**
- **Step 1 (5 monsters):** 250 × 2 = **500 DSPOINC** (5 × 100 each)
- **Step 2 (5 monsters):** 250 × 2 = **500 DSPOINC** (5 × 100 each)
- **Total:** **1,200 DSPOINC** (instead of 600 base)

### **Per Monster Breakdown (VIP 2x):**
- Monster 1 (Demon): 50 × 2 = **100 DSPOINC**
- Monster 2 (Frog): 50 × 2 = **100 DSPOINC**
- Monster 3 (Orc): 50 × 2 = **100 DSPOINC**
- Monster 4 (Dino): 50 × 2 = **100 DSPOINC**
- Monster 5 (Ninja): 50 × 2 = **100 DSPOINC**
- Monster 6 (Blue Demon): 50 × 2 = **100 DSPOINC**
- Monster 7 (Mushroom King): 50 × 2 = **100 DSPOINC**
- Monster 8 (Tribal): 50 × 2 = **100 DSPOINC**
- Monster 9 (Alien): 50 × 2 = **100 DSPOINC**
- Monster 10 (Yeti): 50 × 2 = **100 DSPOINC**

---

## 📋 DATABASE VERIFICATION QUERIES

### **Check Traits:**
```sql
SELECT trait_name, unlocked_at 
FROM tbl_user_traits 
WHERE user_id = '328601656659017732' 
  AND trait_name LIKE 'CHEESE_TEMPLE_LEVEL3%'
ORDER BY unlocked_at;
```

**Expected Results:**
- `CHEESE_TEMPLE_LEVEL3_STEP0` - unlocked after Step 0
- `CHEESE_TEMPLE_LEVEL3_STEP1` - unlocked after Step 1 (all 5 monsters)
- `CHEESE_TEMPLE_LEVEL3_STEP2` - unlocked after Step 2 (all 10 monsters)

### **Check DSPOINC Rewards:**
```sql
SELECT reason, amount 
FROM tbl_score_adjustments 
WHERE user_id = '328601656659017732' 
  AND (reason LIKE '%Level 3%' OR reason LIKE '%CHEESE_TEMPLE_LEVEL3%' OR reason LIKE '%Monster%')
ORDER BY timestamp DESC
LIMIT 15;
```

**Expected Results:**
- "Level 3 Step 0" - 200 (VIP 2x)
- "Monster 1 Captured" - 100 (VIP 2x)
- "Monster 2 Captured" - 100 (VIP 2x)
- "Monster 3 Captured" - 100 (VIP 2x)
- "Monster 4 Captured" - 100 (VIP 2x)
- "Monster 5 Captured" - 100 (VIP 2x)
- "Monster 6 Captured" - 100 (VIP 2x)
- "Monster 7 Captured" - 100 (VIP 2x)
- "Monster 8 Captured" - 100 (VIP 2x)
- "Monster 9 Captured" - 100 (VIP 2x)
- "Monster 10 Captured" - 100 (VIP 2x)

**Total:** 11 rewards (1 Step 0 + 10 monsters) = 1,200 DSPOINC

### **Check Total Balance:**
```sql
SELECT SUM(score) as total_dspoinc 
FROM tbl_user_scores 
WHERE user_id = '328601656659017732';
```

**Expected:** Should increase by 1,200 (or 600 if no multiplier) after completing all steps

---

## 🎮 PROFILE PAGE VERIFICATION

### **3D Puzzles Achievements Section:**
1. Navigate to profile page: `http://localhost/public/profile.html`
2. Click "3D Puzzles Achievements" button
3. Verify "Level 3: The Hunt" section shows:
   - ✅ **3 achievements** listed (Step 0, Step 1, Step 2)
   - ✅ All 3 show as **unlocked** (not grayed out)
   - ✅ Icons display correctly
   - ✅ Achievement names match trait names

### **Recent Score Changes Section:**
1. Scroll to "Recent Score Changes" section
2. Verify 11 entries appear (most recent first):
   - ✅ "Monster 10 Captured" - +100 (VIP 2x)
   - ✅ "Monster 9 Captured" - +100 (VIP 2x)
   - ✅ "Monster 8 Captured" - +100 (VIP 2x)
   - ✅ "Monster 7 Captured" - +100 (VIP 2x)
   - ✅ "Monster 6 Captured" - +100 (VIP 2x)
   - ✅ "Monster 5 Captured" - +100 (VIP 2x)
   - ✅ "Monster 4 Captured" - +100 (VIP 2x)
   - ✅ "Monster 3 Captured" - +100 (VIP 2x)
   - ✅ "Monster 2 Captured" - +100 (VIP 2x)
   - ✅ "Monster 1 Captured" - +100 (VIP 2x)
   - ✅ "Level 3 Step 0" - +200 (VIP 2x)
3. Verify timestamps are correct (recent)
4. Verify amounts match role multiplier (all 100 for monsters, 200 for Step 0)

---

## 🚨 COMMON ISSUES TO WATCH FOR

### **Issue 1: Traits Not Unlocking**
- **Symptom:** Monsters caught but traits don't appear in database
- **Check:** Console logs for `unlockLevel3Step1Trait` / `unlockLevel3Step2Trait` errors
- **Fix:** Verify API endpoint `/api/user/unlock-trait.php` is accessible

### **Issue 2: DSPOINC Not Awarding**
- **Symptom:** Monsters caught but balance doesn't increase
- **Check:** Console logs for `awardLevel3MonsterDspoincReward` errors
- **Fix:** Verify API endpoint `/api/dev/riddle-reward.php` is accessible and role multiplier is working

### **Issue 3: Role Multiplier Not Applied**
- **Symptom:** VIP user only gets 1x multiplier instead of 2x
- **Check:** Verify `tbl_user_roles` has correct role_name for user
- **Fix:** Check `api/dev/riddle-reward.php` `getRoleMultiplier()` function

### **Issue 4: Monsters Not Spawning**
- **Symptom:** Step 0 completes but no monster appears
- **Check:** Console logs for `spawnLevel3Monster` errors
- **Fix:** Verify monster model paths are correct

### **Issue 5: Monster Capture Not Working**
- **Symptom:** Standing near monster but not catching it
- **Check:** Console logs for `checkLevel3MonsterCapture` proximity detection
- **Fix:** Verify `LEVEL3_CAPTURE_DISTANCE` (2.5 units) is correct

---

## ✅ SUCCESS CRITERIA

**Level 3 testing is successful when:**

1. ✅ Step 0 completes and rewards 200 DSPOINC (VIP 2x)
2. ✅ All 10 monsters caught and each rewards 100 DSPOINC (VIP 2x)
3. ✅ All 3 traits appear in database
4. ✅ All 11 DSPOINC rewards logged in `tbl_score_adjustments`
5. ✅ Total balance increases by 1,200 DSPOINC (with VIP 2x multiplier)
6. ✅ All 3 achievements appear on profile page under "3D Puzzles"
7. ✅ All 11 rewards appear in "Recent Score Changes" section
8. ✅ Role multiplier correctly applied (VIP gets 2x, etc.)
9. ✅ Portal appears and completion screen works
10. ✅ "LEVEL UP!" sound plays when entering portal

---

## 📝 TESTING NOTES

- **Test Environment:** Local development (localhost)
- **Test User:** Narrrf (Discord ID: 328601656659017732)
- **Expected Role:** VIP Holder (2x multiplier)
- **Expected Total Rewards:** 1,200 DSPOINC (600 base × 2)
- **Monster Count:** 10 total (5 in Step 1, 5 in Step 2)
- **Arena Size:** 160x160 units (massive hunting ground)

---

**Last Updated:** November 19, 2025  
**Status:** Ready for Testing  
**Next:** Test Level 3 with Narrrf's account and verify all rewards/achievements

