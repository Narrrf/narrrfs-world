# 🎯 LEVEL 5 HUD TESTING INSTRUCTIONS

**Date:** January 12, 2026  
**Status:** ⏳ **READY TO TEST**  
**Purpose:** Test riddle HUD system with Level 5's 10-wave monster hunt

---

## 🎮 **LEVEL 5 OVERVIEW**

### **Level Structure:**
- **Step 0:** Trigger plate (stand for 10 seconds)
- **Step 1:** 10-wave monster hunt
  - Each wave: Defeat 10 monsters
  - Total monsters: 100
  - Progressive difficulty (monsters get faster/bigger)

---

## 🚨 **TEMPORARY TEST MODE (2026-01-12) — QUICK MODE ENABLED**

Due to a persistent Level 5 issue where **some monster models are invisible but still hittable**, Level 5 currently supports an emergency “Quick Mode” so testers can reach Level 6 reliably.

### **Quick Mode Behavior**
- **Waves:** 1 (instead of 10)
- **Monsters per wave:** 5 (instead of 10)
- **Total monsters:** 5 (instead of 100)
- After those 5 are defeated → Step 1 completes → portal activates → completion screen shows

### **Related Daily Note**
- `LEVEL5_QUICK_MODE_PORTAL_WARP_FIX_2026-01-12.md`

### **Expected HUD Display:**
- **Wave Progress:** "Wave X/10: Defeat 10 monsters"
- **Wave Counter:** X/10 for current wave
- **Total Progress:** XX/100 overall progress
- **Hint Text:** Clear instructions for current step

---

## 🧪 **TESTING CHECKLIST**

### **✅ Step 0 (Trigger Plate):**
- [ ] HUD shows: "Stand on the trigger plate for 10 seconds"
- [ ] Timer displays (0s → 10s)
- [ ] Progress bar fills up
- [ ] Step completes after 10 seconds
- [ ] Step 1 activates automatically

### **✅ Step 1 (Monster Waves 1-10):**

**Each Wave Should Show:**
- [ ] HUD updates: "Wave X/10: Defeat 10 monsters"
- [ ] Counter displays: X/10 for current wave
- [ ] Total progress: XX/100 overall
- [ ] Progress bar updates as monsters are defeated
- [ ] Wave completion message appears
- [ ] Next wave countdown (if applicable)
- [ ] New wave spawns automatically

**Specific Checks:**
- [ ] **Wave 1:** 10 monsters spawn, HUD shows 0/10 → 10/10
- [ ] **Wave 2-9:** Each wave progresses correctly
- [ ] **Wave 10:** Final wave completes, portal appears (if applicable)

### **✅ Weapon System:**
- [ ] Weapon loads correctly
- [ ] Shooting mechanics work
- [ ] Bullets hit monsters
- [ ] Monster defeat animations play
- [ ] DSPOINC rewards awarded

### **✅ Monster Mechanics:**
- [ ] 10 monsters spawn per wave
- [ ] Monsters appear at correct Y position (ground level)
- [ ] Monsters move toward player
- [ ] Monsters can be shot and defeated
- [ ] Wave completes after all 10 defeated

---

## 🔍 **WHAT TO WATCH FOR**

### **✅ Good Signs:**
- HUD updates smoothly as wave progresses
- Counter increments: 0/10 → 1/10 → 2/10 → ... → 10/10
- Total progress updates: 0/100 → 10/100 → 20/100 → ... → 100/100
- Progress bar fills up correctly
- Wave completion messages appear
- Next wave spawns automatically

### **❌ Problems to Report:**
- HUD not updating (stuck at 0/10)
- Counter not incrementing when monster defeated
- Progress bar not filling
- Wave not completing (stuck at 9/10 or similar)
- Monsters not spawning
- Monsters spawning too high/low
- Weapon not working
- Bullets not hitting monsters

---

## 📊 **EXPECTED CONSOLE LOGS**

### **Wave Start:**
```
🌊 [LEVEL 5] Starting wave X/10
🐉 [LEVEL 5] Spawning 10 monsters for wave X
```

### **Monster Defeat:**
```
💥 [LEVEL 5] Monster defeated! Wave progress: X/10
🎯 [LEVEL 5] Total progress: XX/100
```

### **Wave Complete:**
```
✅ [LEVEL 5] Wave X complete! 10/10 monsters defeated
🌊 [LEVEL 5] Starting next wave countdown...
```

### **All Waves Complete:**
```
🎉 [LEVEL 5] All 10 waves complete! 100/100 monsters defeated!
🚪 [LEVEL 5] Portal activated!
```

---

## 🎯 **SUCCESS CRITERIA**

### **Level 5 is Working if:**
- ✅ HUD displays correctly for all 10 waves
- ✅ Wave counter updates: 0/10 → 10/10 for each wave
- ✅ Total progress updates: 0/100 → 100/100 overall
- ✅ All 10 waves complete successfully
- ✅ Monsters spawn at correct Y position (ground level)
- ✅ Weapon system works throughout all waves
- ✅ No stuck states (wave doesn't complete, HUD doesn't update)

---

## 🐛 **TROUBLESHOOTING**

### **If HUD Doesn't Update:**
- Check console for `invokeRiddleProgressUIUpdate()` calls
- Verify `level5RiddleState.currentWave` is incrementing
- Check if `updateRiddleProgressUI()` is handling Level 5 correctly

### **If Monsters Don't Spawn:**
- Check console for spawn error messages
- Verify monster paths exist in `LEVEL5_MONSTER_QUEUE`
- Check if `spawnLevel5MonsterWave()` is being called

### **If Monster Y Position Wrong:**
- Report the Y position from console logs
- Similar fix as Level 3 and Level 4 (adjust spawn Y calculation)

### **If Weapon Doesn't Work:**
- Verify weapon loads on entering Step 1
- Check raycasting is enabled for Level 5 monsters
- Verify hit detection callback is registered

---

## 🔮 **AFTER TESTING**

### **If Level 5 Works:**
1. Mark Level 5 as ✅ WORKING
2. Update daily notes with results
3. Update quick status
4. **HUD Standardization COMPLETE!** 🎉

### **If Level 5 Has Issues:**
1. Report specific issues found
2. Check console logs for errors
3. Apply fixes similar to Levels 3-4
4. Retest and verify

---

## 📝 **PREVIOUS LEVEL RESULTS (FOR REFERENCE)**

### **✅ Levels 1-4: 100% Success Rate**
- **Level 1:** All 3 riddles working
- **Level 2:** All 3 steps working
- **Level 3:** All 4 steps working (plate, 2 monster hunts, portal)
- **Level 4:** All 3 steps working (plate, cheese, monster waves)

**Pattern:** HUD system is proven working across all tested levels!

---

**Created:** January 12, 2026  
**Status:** ⏳ Ready for Level 5 testing

**🎯 Test Level 5 - Last level to verify! 🎯**

---

## ✅ FINAL RESULT (2026-01-12)

### **Level 5 (Quick Mode) → Portal → Level 6**
- ✅ **Level 5 Quick Mode confirmed**: 1 wave / 5 monsters → Step 1 completes → portal activates.
- ✅ **Completion screen UX confirmed**: clickable and consistent with other end screens.
- ✅ **Warp confirmed stable**: Level 6 scene visible and playable after portal (no stuck pause overlay).

### **Level 6 Chest (Boss Arena)**
- ✅ **Chest spawns in front of player** for immediate visibility and testing.
- ✅ **Chest Y corrected** to sit on Phoenix boss arena floor (not floating).
- ✅ **Chest collision working**: player cannot walk through the chest.
- ✅ **DSPOINC behavior**: rewards still use duplicate prevention (409 expected if already claimed).

### **Next Step Before Push**
- ✅ If you want a clean “first-open” DSPOINC test, reset opened chests (local dev only) before final verification.
