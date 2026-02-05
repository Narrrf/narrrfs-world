# 🎮 Level 6 – New Riddle Steps Plan

**Date:** February 4, 2026  
**Status:** 📋 **PLAN – Ready for Implementation**  
**Scope:** Level 6 Phoenix Arena – new riddle steps  
**Files:** `public/three.js/main.js`, `public/three.js/gui-system.js`, `api/dev/riddle-reward.php`

---

## 🎯 Current Level 6 State

### What Exists Today
- **Type:** Boss fight arena (Phoenix Dragon + Alien Spider)
- **Phoenix:** 15 behavior patterns, F key cycle, HUD sync
- **Alien Spider:** 12 behavior patterns, N key cycle, HUD sync
- **Chest:** `chest_011` spawns in front of player on entry
- **Weapons:** Both slots (1 & 2) active
- **Riddle HUD:** Currently hidden (boss arena, no riddle steps)
- **Documentation:** No formal Level 6 riddle doc in `3d_riddles/`

### Standard Riddle Pattern (Levels 2–5)
1. **Step 0:** Hidden trigger (stand on platform/stone 10 seconds)
2. **Step 1+:** Main challenge (lever, monster hunt, cheese shoot, etc.)
3. **Step N:** Portal activation after all steps complete
4. **Completion:** DSPOINC + trait unlock via `/api/dev/riddle-reward.php`

---

## 📋 Proposed New Steps for Level 6

### Option A: Boss Interaction Steps (Recommended)
| Step | Name | Description | Reward | Trait |
|------|------|-------------|--------|-------|
| **Step 0** | Platform | Stand on arena center platform 10 seconds | 100 DSPOINC | `CHEESE_TEMPLE_LEVEL6_STEP0` |
| **Step 1** | Phoenix Encounter | Interact with Phoenix (E key when near) or observe Phoenix cycle | 150 DSPOINC | `CHEESE_TEMPLE_LEVEL6_STEP1` |
| **Step 2** | Spider Encounter | Interact with Alien Spider (E key when near) or observe Spider cycle | 150 DSPOINC | `CHEESE_TEMPLE_LEVEL6_STEP2` |
| **Step 3** | Chest | Open chest_011 (already exists) | 200 DSPOINC | `CHEESE_TEMPLE_LEVEL6_STEP3` |
| **Step 4** | Portal | Enter portal (future) or complete arena | 200 DSPOINC | `CHEESE_TEMPLE_LEVEL6_STEP4` |

**Total base:** 800 DSPOINC (VIP 2x = 1,600 DSPOINC)

### Option B: Minimal Steps (Quick Win)
| Step | Name | Description | Reward | Trait |
|------|------|-------------|--------|-------|
| **Step 0** | Platform | Stand on arena center 10 seconds | 100 DSPOINC | `CHEESE_TEMPLE_LEVEL6_STEP0` |
| **Step 1** | Chest | Open chest_011 | 200 DSPOINC | `CHEESE_TEMPLE_LEVEL6_STEP1` |

**Total base:** 300 DSPOINC

### Option C: Boss Defeat Steps (Future – Requires Damage System)
| Step | Name | Description | Reward | Trait |
|------|------|-------------|--------|-------|
| **Step 0** | Platform | Stand on platform 10 seconds | 100 DSPOINC | `CHEESE_TEMPLE_LEVEL6_STEP0` |
| **Step 1** | Defeat Phoenix | Shoot Phoenix until "defeated" (needs health/damage) | 250 DSPOINC | `CHEESE_TEMPLE_LEVEL6_STEP1` |
| **Step 2** | Defeat Spider | Shoot Spider until "defeated" | 250 DSPOINC | `CHEESE_TEMPLE_LEVEL6_STEP2` |
| **Step 3** | Chest + Portal | Open chest, enter portal | 300 DSPOINC | `CHEESE_TEMPLE_LEVEL6_STEP3` |

**Note:** Option C requires boss health/damage system – not yet implemented.

---

## 🔧 Implementation Checklist

### Phase 1: Foundation (Step 0)
- [ ] Add `level6RiddleState` object (step0Complete, step1Complete, etc.)
- [ ] Create trigger zone/block at arena center (or use existing spawn area)
- [ ] 10-second standing timer (same pattern as Level 2–5)
- [ ] Call `riddle-reward.php` on Step 0 complete
- [ ] Unlock trait `CHEESE_TEMPLE_LEVEL6_STEP0`
- [ ] Enable riddle HUD for Level 6 in `updateRiddleProgressUI()`

### Phase 2: Step 1 (Boss Interaction or Chest)
- [ ] **If boss interaction:** Add proximity check for Phoenix (E key when within 4 units)
- [ ] **If chest first:** chest_011 already exists – wire completion to Step 1
- [ ] Trait: `CHEESE_TEMPLE_LEVEL6_STEP1`
- [ ] Reward: 150–200 DSPOINC base

### Phase 3: Step 2 (Second Boss or Chest)
- [ ] **If boss interaction:** Add proximity check for Alien Spider
- [ ] **If chest:** May need second chest or reuse chest as Step 1
- [ ] Trait: `CHEESE_TEMPLE_LEVEL6_STEP2`

### Phase 4: HUD Integration
- [ ] Add Level 6 branch in `updateRiddleProgressUI()`
- [ ] Display: "Step 0: Stand on Platform" with timer
- [ ] Display: "Step 1: Interact with Phoenix" (or chest)
- [ ] Display: "Step 2: Interact with Spider" (or portal)
- [ ] Show completion messages

### Phase 5: Documentation
- [ ] Create `RIDDLE_01_PHOENIX_ARENA_LEVEL_6.md` in `3d_riddles/`
- [ ] Update `3d_riddles/README.md` with Level 6 entry
- [ ] Update `GAME_07_3D_HYTOPIA_COMPLETE_TECHNICAL.md` Level 6 section

---

## 📁 Files to Modify

| File | Changes |
|------|---------|
| `public/three.js/main.js` | `level6RiddleState`, Step 0 trigger, Step 1–2 logic, `updateRiddleProgressUI()` Level 6 branch |
| `public/three.js/gui-system.js` | Level 6 HUD labels (if needed) |
| `api/dev/riddle-reward.php` | No changes – already supports any riddle_id |
| `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/` | New `RIDDLE_01_PHOENIX_ARENA_LEVEL_6.md` |

---

## 🚨 DSPOINC Sync Rules (from 13_3D_GAME_DSPOINC_SYNC_RULE)

- **ALWAYS** use `/api/dev/riddle-reward.php` for step completions
- **ALWAYS** unlock trait **before** awarding DSPOINC
- **ALWAYS** use `level_id: "CHEESE_TEMPLE_LEVEL6"`
- **ALWAYS** use `riddle_id` format: `CHEESE_TEMPLE_LEVEL6_STEP0`, `CHEESE_TEMPLE_LEVEL6_STEP1`, etc.
- Rewards appear in `tbl_riddle_completions`, `tbl_user_scores`, `tbl_score_adjustments`

---

## 🧪 Testing Checklist

- [ ] Step 0: Stand on platform 10 seconds → trait + DSPOINC
- [ ] Step 1: Complete (boss or chest) → trait + DSPOINC
- [ ] HUD shows correct step instructions
- [ ] G key (god mode) cycles steps if applicable
- [ ] Duplicate prevention (409 Conflict) works
- [ ] Role multipliers applied correctly

---

## 📝 Recommendation

**Start with Option B (Minimal)** for quick implementation:
1. Step 0: Platform trigger (reuse Level 2–5 pattern)
2. Step 1: Chest (chest_011 already exists – wire to riddle completion)

Then expand to **Option A** by adding boss interaction steps (Step 1 = Phoenix, Step 2 = Spider) before chest.

---

**Next Step:** Choose option (A, B, or C) and implement Phase 1 (Step 0).
