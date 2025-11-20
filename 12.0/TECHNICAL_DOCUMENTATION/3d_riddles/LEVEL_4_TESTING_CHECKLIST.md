# 🎯 LEVEL 4 TESTING CHECKLIST - "THE FIRST SHOT"

**Test Date:** November 19, 2025  
**Test Player:** Narrrf (VIP Holder - 2.0x multiplier)  
**Level:** Level 4 "The First Shot"  
**Status:** Ready for Testing

---

## 📊 EXPECTED REWARDS FOR NARRRF (VIP 2.0x MULTIPLIER)

### **Step 0: Hidden Cheese Stone Platform**
- **Action:** Stand on hidden cheese stone platform for 10 seconds
- **Base Reward:** 100 DSPOINC
- **VIP Multiplier:** 2.0x
- **Expected Reward:** **200 DSPOINC** (100 × 2.0)
- **Trait:** `CHEESE_TEMPLE_LEVEL4_STEP0`
- **Description:** "Level 4 Step 0"

### **Step 1: Shoot 50 Floating Cheeses**
- **Action:** Shoot 50 floating cheese entities with first-person weapon
- **Base Reward:** 50 DSPOINC per cheese
- **VIP Multiplier:** 2.0x
- **Per Cheese Reward:** **100 DSPOINC** (50 × 2.0)
- **Total Cheeses:** 50
- **Expected Total:** **5,000 DSPOINC** (50 × 100)
- **Individual Rewards:** 
  - `CHEESE_TEMPLE_LEVEL4_CHEESE_1` → +100 DSPOINC
  - `CHEESE_TEMPLE_LEVEL4_CHEESE_2` → +100 DSPOINC
  - ... (continues for all 50 cheeses)
  - `CHEESE_TEMPLE_LEVEL4_CHEESE_50` → +100 DSPOINC
- **Trait:** `CHEESE_TEMPLE_LEVEL4_STEP1` (unlocked after all 50 cheeses shot)
- **Description:** "Level 4 Step 1"

### **Step 2: Portal Completion**
- **Action:** Enter the portal after completing Step 1
- **Base Reward:** 200 DSPOINC
- **VIP Multiplier:** 2.0x
- **Expected Reward:** **400 DSPOINC** (200 × 2.0)
- **Trait:** `CHEESE_TEMPLE_LEVEL4_STEP2`
- **Description:** "Level 4 Step 2 - Portal Entry"

---

## 🎯 TOTAL EXPECTED REWARDS

| Step | Base Reward | VIP Multiplier | Expected Reward | Trait |
|------|-------------|----------------|-----------------|-------|
| Step 0 | 100 DSPOINC | 2.0x | **200 DSPOINC** | `CHEESE_TEMPLE_LEVEL4_STEP0` |
| Step 1 (50 cheeses) | 50 × 50 = 2,500 DSPOINC | 2.0x | **5,000 DSPOINC** | `CHEESE_TEMPLE_LEVEL4_STEP1` |
| Step 2 | 200 DSPOINC | 2.0x | **400 DSPOINC** | `CHEESE_TEMPLE_LEVEL4_STEP2` |
| **GRAND TOTAL** | **2,800 DSPOINC** | **2.0x** | **5,600 DSPOINC** | **3 Traits** |

---

## ✅ TESTING CHECKLIST

### **Step 0: Hidden Cheese Stone Platform**
- [ ] Player spawns in center of 160x160 arena
- [ ] Hidden cheese stone platform is visible (normal cheese-stone.png texture)
- [ ] Platform sinks when player stands on it
- [ ] Platform rises when player steps off
- [ ] Timer counts up when standing (10 seconds required)
- [ ] Timer decays when not standing
- [ ] Step 0 completes after 10 seconds
- [ ] Trait `CHEESE_TEMPLE_LEVEL4_STEP0` unlocks
- [ ] **+200 DSPOINC reward awarded** (100 base × 2.0 VIP)
- [ ] Toast message appears: "Step 1 begins! The First Shot awaits..."
- [ ] Trigger block disappears after completion
- [ ] Weapon viewmodel loads (Pistol_1.fbx)
- [ ] Initial batch of 1-5 cheeses spawns

### **Step 1: Shoot 50 Floating Cheeses**
- [ ] First-person weapon viewmodel is visible
- [ ] Pointer lock activates (click to lock)
- [ ] Left mouse button shoots (mousedown event)
- [ ] Raycasting detects cheese hits
- [ ] Hit indicator flashes on successful hit (cheese-themed radial gradient)
- [ ] Shooting sound plays on each shot (Space Invaders normal_shoot.wav)
- [ ] Weapon bobbing animation works when moving
- [ ] Recoil animation works on each shot
- [ ] Cheeses jump and fly around the arena
- [ ] Cheeses have AI dodging behavior
- [ ] Mad mode triggers randomly (red-orange glow)
- [ ] Explosion effect appears when cheese is hit (12 particles)
- [ ] New batch spawns 0.5 seconds after each hit
- [ ] Difficulty increases progressively (smaller, faster, smarter)
- [ ] Progress HUD shows: "🔫 Cheeses Shot: X/50 (X%)"
- [ ] Each cheese shot rewards **+100 DSPOINC** (50 base × 2.0 VIP)
- [ ] Toast shows progress: "🧀 Cheese X/50 caught!"
- [ ] All 50 individual rewards appear in "Recent Score Changes"
- [ ] After 50 shot, trait `CHEESE_TEMPLE_LEVEL4_STEP1` unlocks
- [ ] Portal appears at back of arena
- [ ] Portal suction pulls player closer

### **Step 2: Portal Completion**
- [ ] Portal is visible at back of arena
- [ ] Portal suction effect works (pulls player within 6 units)
- [ ] Entering portal (within 2.5 units horizontally, 3 units vertically) completes Step 2
- [ ] **"LEVEL UP!" sound plays** (same as Level 1)
- [ ] **+400 DSPOINC reward awarded** (200 base × 2.0 VIP)
- [ ] Trait `CHEESE_TEMPLE_LEVEL4_STEP2` unlocks
- [ ] Completion screen appears with navigation options:
  - "🚀 Proceed to Level 5" (restarts Level 4 for now)
  - "🧀 Stay in Level 4" (restarts Level 4)
  - "🔄 Return to Level 3" (warps to Level 3)
  - "🏠 Return to Level 1" (restarts Level 1)

---

## 🗄️ DATABASE VERIFICATION

### **After Completing Level 4, Verify:**

1. **tbl_riddle_completions:**
   - [ ] `CHEESE_TEMPLE_LEVEL4_STEP0` - base_reward: 100, total_reward: 200
   - [ ] `CHEESE_TEMPLE_LEVEL4_CHEESE_1` through `CHEESE_TEMPLE_LEVEL4_CHEESE_50` - base_reward: 50, total_reward: 100 each
   - [ ] `CHEESE_TEMPLE_LEVEL4_STEP2` - base_reward: 200, total_reward: 400

2. **tbl_score_adjustments:**
   - [ ] 52 total entries (1 Step 0 + 50 cheeses + 1 Step 2)
   - [ ] All entries show correct reason format: "Riddle completion (...): base X × 2.00 = Y DSPOINC"
   - [ ] All entries show correct amounts (200, 100×50, 400)

3. **tbl_user_traits:**
   - [ ] `CHEESE_TEMPLE_LEVEL4_STEP0` unlocked
   - [ ] `CHEESE_TEMPLE_LEVEL4_STEP1` unlocked
   - [ ] `CHEESE_TEMPLE_LEVEL4_STEP2` unlocked

4. **Profile Page:**
   - [ ] All 3 achievements appear in "3D Puzzles Achievements" section
   - [ ] All 52 rewards appear in "Recent Score Changes" section
   - [ ] Total DSPOINC balance increased by 5,600

---

## 🎯 EXPECTED RESULTS SUMMARY

### **For Narrrf (VIP 2.0x Multiplier):**
- **Step 0:** 200 DSPOINC ✅
- **Step 1:** 5,000 DSPOINC (50 × 100) ✅
- **Step 2:** 400 DSPOINC ✅
- **Total:** **5,600 DSPOINC** ✅
- **Traits:** 3 achievements unlocked ✅

### **Profile Page Display:**
- **3D Puzzles Achievements:** Level 4 "The First Shot" shows 3/3 achievements solved
- **Recent Score Changes:** 52 entries (1 Step 0 + 50 cheeses + 1 Step 2)
- **All entries show VIP 2.0x multiplier in reason field**

---

## 🚨 KNOWN ISSUES / NOTES

- **Progressive Difficulty:** Cheeses get smaller (100% → 60%), faster (1x → 2.5x), and smarter (40% → 90%) as you progress
- **Continuous Spawning:** New batch (1-5 cheeses) spawns 0.5 seconds after each hit
- **Mad Mode:** 18% chance for cheeses to enter "mad mode" (red-orange glow, increased speed)
- **Weapon System:** First-person viewmodel (Pistol_1.fbx) with bobbing and recoil animations
- **Hit Detection:** Raycasting from camera center (crosshair position) with 200 unit range

---

**Last Updated:** November 19, 2025  
**Status:** Ready for Production Testing  
**Test Player:** Narrrf (VIP 2.0x Multiplier)

