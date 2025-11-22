# 🐛 RIDDLE #4 SEQUENCE BUG FIX — November 22, 2025

**Date:** November 22, 2025  
**Issue:** Hidden Secret Riddle (Riddle #4) sequence not working correctly  
**Status:** ✅ **FIXED**

---

## 🐛 **THE PROBLEM**

### **User Report:**
User tried the hidden riddle combination but it wasn't working. The sequence should be:
1. All 3 levers ON
2. All 3 levers OFF  
3. Only middle lever ON

### **Root Cause:**
The reset logic in `checkRiddle4Combination()` was too aggressive. When the player was in Step 1 (all levers ON) and started turning levers OFF one by one, the code would reset the sequence immediately because it detected that some levers were still ON.

**Example of the bug:**
1. Step 1 complete (all ON) → `sequenceStep = 1`
2. Player turns lever1 OFF → `lever1State = false`, `lever2State = true`, `lever3State = true`
3. Code checks: `if (r4.lever1State || r4.lever2State || r4.lever3State)` → TRUE (lever2 and lever3 still ON)
4. **BUG:** Sequence resets to 0, even though player is correctly turning levers OFF!

---

## ✅ **THE FIX**

### **Updated Reset Logic:**

**Step 1 (All ON → All OFF):**
- **Before:** Reset if ANY lever is still ON (too aggressive)
- **After:** Only reset if ALL levers are back ON (player went backwards)
- **Result:** Player can now turn levers OFF one by one without resetting

**Step 2 (All OFF → Middle ON):**
- **Before:** Reset if wrong combination detected (could reset too early)
- **After:** Only reset if lever1 or lever3 is turned ON (wrong levers)
- **Result:** Player can turn lever2 ON without resetting

### **Code Changes:**
**File:** `three.js/main.js`  
**Function:** `checkRiddle4Combination()`  
**Lines:** ~14725-14754

**Key Changes:**
1. **Step 1 Reset Logic:**
   ```javascript
   // OLD (BUGGY):
   if (r4.lever1State || r4.lever2State || r4.lever3State) {
     r4.sequenceStep = 0; // Resets too early!
   }
   
   // NEW (FIXED):
   if (r4.lever1State && r4.lever2State && r4.lever3State) {
     r4.sequenceStep = 0; // Only reset if all back ON (went backwards)
   }
   ```

2. **Step 2 Reset Logic:**
   ```javascript
   // OLD (BUGGY):
   if ((r4.lever1State || r4.lever3State) || !r4.lever2State) {
     r4.sequenceStep = 0; // Could reset too early
   }
   
   // NEW (FIXED):
   if (r4.lever1State || r4.lever3State) {
     r4.sequenceStep = 0; // Only reset if wrong lever turned ON
   }
   // If all OFF, player might be about to turn lever2 ON - don't reset
   ```

---

## 🧪 **TESTING**

### **Correct Sequence (Should Work Now):**
1. **Step 0 → Step 1:**
   - Turn lever1 ON
   - Turn lever2 ON
   - Turn lever3 ON
   - ✅ Step 1 complete (all ON)

2. **Step 1 → Step 2:**
   - Turn lever1 OFF (lever2 and lever3 still ON - OK, don't reset)
   - Turn lever2 OFF (lever3 still ON - OK, don't reset)
   - Turn lever3 OFF
   - ✅ Step 2 complete (all OFF)

3. **Step 2 → Step 3 (SOLVED):**
   - Turn lever2 ON (lever1 and lever3 still OFF - OK, don't reset)
   - ✅ Riddle solved! +1,000 DSPOINC

### **Wrong Sequences (Should Reset):**
1. **In Step 1, turn lever back ON:**
   - All ON → Turn lever1 OFF → Turn lever1 back ON → ❌ Reset (went backwards)

2. **In Step 2, turn wrong lever ON:**
   - All OFF → Turn lever1 ON → ❌ Reset (should only turn lever2 ON)

---

## 📝 **FILES MODIFIED**

- ✅ `three.js/main.js` 
  - Fixed `checkRiddle4Combination()` reset logic
  - Fixed `unlockRiddle4Reward()` function (was calling non-existent function)

---

## 🐛 **ADDITIONAL BUG FIXED**

### **Issue #2: Reward Not Awarding**
**User Report:** Sequence completed (middle lever lighting up), but no DSPOINC popup appeared.

**Root Cause:** The function `awardLevel1DspoincReward()` didn't exist - it was being called but never defined, so the reward was never actually awarded.

**Fix:** Replaced the non-existent function call with a proper async function that:
- Calls the `RIDDLE_REWARD_ENDPOINT` API directly (same pattern as other riddles)
- Handles success/error responses properly
- Updates HUD balance
- Shows reward notification popup
- Handles duplicate completion (409 Conflict)

**Code Changes:**
- `unlockRiddle4Reward()` - Now properly implemented as async function
- Uses same API pattern as `completeRiddle()`, `completeRiddle2()`, `completeRiddle3()`

---

## ✅ **STATUS**

**Bug #1 (Sequence Reset):** ✅ **FIXED**  
**Bug #2 (Reward Not Awarding):** ✅ **FIXED**  
**Testing:** ⏳ **REQUIRED** - User should test the sequence and verify reward appears  
**Documentation:** ✅ **UPDATED** - This lab note created

---

## 🎯 **NEXT STEPS**

1. Test the sequence in-game
2. Verify all 3 steps work correctly
3. Verify wrong sequences reset properly
4. **Verify reward popup appears** when sequence is completed
5. **Verify +1,000 DSPOINC is awarded** and appears in HUD
6. **Check browser console** for any error messages

---

**Created:** November 22, 2025  
**Status:** ✅ **FIXED - READY FOR TESTING**

