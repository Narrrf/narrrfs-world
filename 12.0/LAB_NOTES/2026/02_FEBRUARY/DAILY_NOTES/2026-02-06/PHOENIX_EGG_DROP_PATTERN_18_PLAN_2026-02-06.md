# 🥚 Phoenix Egg Drop – Pattern 18 Plan

**Date:** February 6, 2026  
**Status:** ✅ **IMPLEMENTED**  
**Files:** `public/three.js/phoenix2.js`, `main.js`, `gui-system.js`

---

## 🎯 OVERVIEW

**Pattern 18** = Ultra-extended version of **Pattern 17** (fire_sphere_hunt_extended) **plus egg laying when the Phoenix is in the air in circle/patrol mode**.

### Core Loop
1. Phoenix uses Pattern 17 as base (all existing phases: sleep, wake, takeoff, fire, ground fire bursts, patrol, dive, etc.)
2. **NEW:** While in **circle/patrol mode** (airborne), Phoenix periodically **lays Volcanic Eggs**
3. Egg **falls with gravity** to the ground
4. When egg **lands**, **minion spiders spawn** and attack the player
5. Loop repeats until player defeats **Phoenix + all spider minions**

---

## 📋 PATTERN 18 DESIGN

### Relationship to Pattern 17
- **Pattern 18** extends **Pattern 17** (fire_sphere_hunt_extended)
- Same phase structure (phases 1–26)
- Same circle/patrol behavior
- **Add:** Egg drop logic during **circle mode** (phases where Phoenix is flying in patrol circle)

### Circle Mode Phases (Pattern 17)
- **Phase 21** – Patrol (circle) start
- **Phase 22** – Smooth transition to circle path
- **Phase 23** – Flying in circle (patrol)
- Egg drop should trigger **during phase 21–23** when Phoenix is in the air

### Egg Drop Mechanics
| Step | Description |
|------|-------------|
| 1 | Phoenix in circle/patrol mode (airborne) |
| 2 | Trigger egg drop at Phoenix position (or slightly below) |
| 3 | Spawn Volcanic Egg model (`EggVolcanic.glb`) at drop position |
| 4 | Egg falls with gravity (animate Y downward to ground) |
| 5 | On landing: play egg crack effect, spawn N spider minions at egg position |
| 6 | Repeat (e.g., every X seconds or at health thresholds) |

### Win Condition
- Player must defeat **both** Phoenix **and** all spawned spider minions

---

## 🔧 TECHNICAL NOTES

### Phoenix Pattern Architecture
- **phoenix2.js:** `updateFireSphereHuntExtended()` handles Pattern 17
- Pattern 18: Either extend that function or create `updateFireSphereHuntEggDrop()` that calls Pattern 17 logic + adds egg drop in circle phase
- **Egg model:** `textures/3d models/Egg Volcanic Core/EggVolcanic.glb` (already in Level 6)

### Existing Systems to Reuse
- **Volcanic Egg:** Decorative model already placed in Level 6 (`createLevel6VolcanicEgg()`)
- **Spider minions:** `AlienSpiderMinion.createFromCache()`, `spawnLevel6SpiderWave()`
- **Ground detection:** `LEVEL6_SPIDER_WAVE_CONFIG.groundY`, raycast from egg position

### New Systems Needed
- Egg drop spawn (dynamic egg instance at Phoenix position)
- Egg fall animation (Y position lerp or simple velocity)
- Egg landing detection (collision with ground or Y ≤ ground + threshold)
- Egg → spider spawn trigger
- Pattern 18 config (egg drop interval, spiders per egg, etc.)

---

## 📁 FILES TO MODIFY (When Implementing)

- `public/three.js/phoenix2.js` – Add Pattern 18, egg drop logic in circle phase
- `public/three.js/main.js` – Pattern 18 config, egg/spider coordination, win condition
- Possibly: new `egg-drop-system.js` or integrate into main.js

---

## 📊 STATUS SYNC (Feb 6, 2026)

- **QUICK_STATUS:** Updated with Phoenix Egg Drop Pattern 18
- **DAILY_STATUS:** Updated with implementation
- **Lab note:** This file

---

## ✅ IMPLEMENTATION COMPLETE (Feb 6, 2026)

### Phases 1–5 Delivered
1. **Phase 1:** Pattern 18 config + behavior entry in phoenix2.js (`fire_sphere_hunt_egg_drop`)
2. **Phase 2:** Egg drop spawn at Phoenix position during circle/patrol mode (phases 21–23)
3. **Phase 3:** Egg fall animation + landing detection
4. **Phase 4:** `spawnLevel6SpiderMinionsAtPosition(landPos, count)` + `onEggLanded` callback in phoenixConfig
5. **Phase 5:** Pattern 18 wired in main.js (cyclePhoenixBehavior B key, behavior dropdown, gui-system X/18)

### Files Modified
- `phoenix2.js` – Pattern 18 config, egg drop logic
- `main.js` – `spawnLevel6SpiderMinionsAtPosition()`, `onEggLanded`, cyclePhoenixBehavior (18 patterns), behavior dropdown
- `gui-system.js` – updatePhoenixBehaviorDisplay (X/18 format)

---

**Status:** ✅ **IMPLEMENTATION COMPLETE**
