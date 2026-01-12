# 🔍 LEVEL 2 PLATE MECHANICS DEBUG INSTRUCTIONS

**Date:** January 12, 2026  
**Status:** ✅ **DEBUG LOGGING ADDED - READY FOR TESTING**  
**Purpose:** Diagnose Level 2 Step 0 plate/trigger block mechanics

---

## 🎯 **CHANGES MADE**

### **1. HUD Update Calls Added**
Added `invokeRiddleProgressUIUpdate()` to all level update functions:
- ✅ `updateLevel2()` - Now calls HUD update every frame
- ✅ `updateLevel3()` - Now calls HUD update every frame
- ✅ `updateLevel4()` - Now calls HUD update every frame
- ✅ `updateLevel5()` - Now calls HUD update every frame

**Result:** HUD should now display riddle hints dynamically for all levels 2-5

---

### **2. Debug Logging Added to Level 2 Step 0**
Added comprehensive debug logging to `updateLevel2Step0()` function:

**What It Logs (Every 2 Seconds):**
- Player feet position (X, Y, Z)
- Trigger block position (X, Y, Z)
- Block top Y position (for standing detection)
- Block scale (X, Y, Z)
- Standing detection status (true/false)
- Timer progress (0.00 → 10.00)

---

## 🧪 **TESTING INSTRUCTIONS**

### **Step 1: Load Level 2**
1. Start the game
2. Warp to Level 2 (L key → Level 2)
3. Look for the yellow cheese plate on the floor

### **Step 2: Open Browser Console**
1. Press F12 to open Developer Tools
2. Go to Console tab
3. Clear console (trash icon)

### **Step 3: Walk to the Plate**
1. Walk towards the yellow cheese plate
2. Stand on top of it
3. Watch the console logs

---

## 🔍 **WHAT TO CHECK IN CONSOLE**

### **Expected Console Output (When Standing on Plate):**

```javascript
🔍 [LEVEL 2 STEP 0 DEBUG] {
  playerFeet: { x: "48.00", y: "0.10", z: "-7.00" },
  blockPos: { x: "48.00", y: "0.23", z: "-7.00" },
  blockTopY: "0.40",
  blockScale: { x: "1.40", y: "0.35", z: "1.40" },
  isStandingOnTrigger: true,  // 👈 Should be TRUE when on plate
  timer: "5.50"                // 👈 Should increment (0.00 → 10.00)
}
```

### **Key Values to Check:**

#### **1. `isStandingOnTrigger`:**
- ✅ **TRUE** - Player is on plate, timer should increment
- ❌ **FALSE** - Player is NOT detected on plate (problem!)

#### **2. `timer`:**
- ✅ **Incrementing (0.00 → 10.00)** - Plate detection working, reward will trigger at 10.00
- ❌ **Stuck at 0.00** - Timer not incrementing (problem!)

#### **3. Player Position vs Block Position:**
- **X and Z should be close** - Player feet should be within block bounds
- **Y differences:**
  - `playerFeet.y` should be around `0.10` (player feet height)
  - `blockTopY` should be around `0.40` (top of plate)
  - Standing detection checks if feet are within ±0.4 of block top

---

## 🚨 **POTENTIAL ISSUES & SOLUTIONS**

### **Issue 1: `isStandingOnTrigger` is Always FALSE**

**Possible Causes:**
1. **Trigger block doesn't exist** - Check if `level2RiddleState.triggerBlock` is defined
2. **Player position is wrong** - Check if playerFeet X/Z match block X/Z
3. **Y position is wrong** - Check if playerFeet.y is close to blockTopY (within ±0.4)
4. **Block scale is wrong** - Check if block scale matches expected (1.4, 0.35, 1.4)

**What to Look For in Console:**
- If no logs appear at all → Trigger block not created (serious issue)
- If logs show very different X/Z → Player is far from plate
- If logs show very different Y → Player is above/below detection range

---

### **Issue 2: Timer Stuck at 0.00**

**Possible Causes:**
1. **`isStandingOnTrigger` is false** - Player not being detected
2. **`step0Complete` is already true** - Step already completed (check state)
3. **Timer reset logic interfering** - Check if timer is being reset elsewhere

**What to Look For in Console:**
- `isStandingOnTrigger: false` → Fix standing detection first
- `timer: 0.00` continuously → Timer increment logic not running

---

### **Issue 3: HUD Not Appearing**

**Possible Causes:**
1. **`updateRiddleProgressUI()` not being called** - Now fixed with HUD update calls
2. **HUD display logic wrong** - Check if Level 2 block in `updateRiddleProgressUI()` works
3. **CSS/styling issue** - HUD might be hidden by CSS

**What to Look For:**
- Check if HUD element appears in DOM (F12 → Elements)
- Check if `riddleProgressUI.style.display` is "flex" or "none"
- Look for HUD update logs in console

---

## 🎯 **EXPECTED BEHAVIOR**

### **When Standing on Yellow Cheese Plate:**

1. **Sound:** Cheese platform sound plays (once)
2. **HUD:** Riddle progress HUD appears showing:
   - Title: "🧩 Level 2: The Spawn"
   - Step 1: "⏱️ Stand on the golden pressure plate"
   - Timer: "⏱️ 5.5 / 10.0s" (increments)
3. **Visual:** Plate slowly depresses (moves down slightly)
4. **After 10 seconds:**
   - Sound: Level-up sound plays
   - Toast: "Step 0 Complete!" message
   - HUD: Updates to show "✅ Step 0 Complete"
   - Lever: Lever appears on far wall
   - Trait: `CHEESE_TEMPLE_LEVEL2_STEP0` unlocked
   - Reward: 100 DSPOINC awarded (with role multiplier)

---

## 📊 **TRIGGER BLOCK SPECIFICATIONS**

### **Position:**
- X: `level2Config.origin.x - 12` (12 blocks left of center)
- Y: `0.225` (block center), `0.40` (block top)
- Z: `level2Config.origin.z - 22` (22 blocks back from center)

### **Scale:**
- X: `1.4` (wider than normal block)
- Y: `0.35` (thin, like a pressure plate)
- Z: `1.4` (wider than normal block)

### **Detection:**
- Uses `isPlayerStandingOnBlock()` function
- Checks if player feet (X, Z) are within block bounds
- Checks if player feet Y is within ±0.4 of block top
- Requires continuous standing for 10 seconds

---

## 🔧 **TECHNICAL DETAILS**

### **Files Modified:**
1. **`public/three.js/main.js`**
   - Added HUD update calls to 4 functions (lines 27891, 19302, 22391, 22477)
   - Added debug logging to `updateLevel2Step0()` (line 28409+)

### **Functions Involved:**
- `updateLevel2()` - Main Level 2 update loop
- `updateLevel2Step0()` - Step 0 trigger block detection
- `checkLevel2TriggerBlockStanding()` - Standing detection
- `isPlayerStandingOnBlock()` - Generic block standing detection
- `invokeRiddleProgressUIUpdate()` - HUD update caller
- `updateRiddleProgressUI()` - HUD display logic

### **State Variables:**
- `level2RiddleState.triggerBlock` - Trigger block mesh (invisible collider)
- `level2RiddleState.triggerBlockVisual` - Visual yellow plate (visible)
- `level2RiddleState.triggerBlockTimer` - Timer (0.00 → 10.00)
- `level2RiddleState.step0Complete` - Completion flag
- `level2RiddleState.step0StandingSoundPlayed` - Sound flag

---

## 📋 **TESTING CHECKLIST**

### **Verify in Console:**
- [ ] Debug logs appear every 2 seconds when near plate
- [ ] `isStandingOnTrigger` changes to `true` when on plate
- [ ] `timer` increments when standing on plate (0.00 → 10.00)
- [ ] Timer resets when stepping off plate
- [ ] Completion triggers at 10.00 seconds

### **Verify in Game:**
- [ ] Yellow cheese plate is visible on floor
- [ ] HUD appears when standing on plate
- [ ] HUD shows timer progress
- [ ] Plate depresses when standing on it
- [ ] Sound plays when first stepping on plate
- [ ] Step 0 completes after 10 seconds
- [ ] Lever appears after completion
- [ ] 100 DSPOINC awarded (check console/balance)

---

## 🚀 **NEXT STEPS AFTER TESTING**

### **If Plate Works:**
1. Test Level 2 Step 1 (lever)
2. Test Level 2 Step 2 (inspection zones)
3. Test Levels 3, 4, 5 with new HUD
4. Remove debug logging if not needed

### **If Plate Doesn't Work:**
1. Share console logs with debug output
2. Check player position vs block position values
3. Verify `isStandingOnTrigger` status
4. Adjust detection parameters if needed

---

**Created:** January 12, 2026  
**Status:** ✅ Ready for testing with debug diagnostics

**🔍 Check the console logs to see what's happening with the plate detection! 🔍**
