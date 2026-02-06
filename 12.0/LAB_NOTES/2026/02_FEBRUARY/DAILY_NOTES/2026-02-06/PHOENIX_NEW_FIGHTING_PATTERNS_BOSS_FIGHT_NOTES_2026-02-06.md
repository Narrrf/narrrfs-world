# 🐉 Phoenix New Fighting Patterns – Boss Fight Notes

**Date:** February 6, 2026  
**Status:** ✅ **PRODUCTION READY – Patterns 16 & 17 Working**  
**Scope:** Level 6 Phoenix Boss – Real boss fight with fire sphere attacks  
**Related:** `PHOENIX_FIRE_SPHERE_ATTACK_PLAN_2026-02-05.md`

---

## 📋 **OVERVIEW**

Level 6 Phoenix Dragon now has **17 behavior patterns**, including two new ranged fire-attack patterns that create a **real boss fight** experience:

- **Pattern 16 (fire_sphere_hunt):** Airborne fire sphere barrage – 2 attack cycles, 6 spheres total
- **Pattern 17 (fire_sphere_hunt_extended):** Full boss sequence – Pattern 16 + ground fight + huge patrol + dive attack + ground attack + sleep loop

Both patterns use **player-targeting fire spheres** that travel toward the player and deal damage on hit. Game over flow, restart, and collision are fully implemented.

---

## 🔥 **PATTERN 16: Fire Sphere Hunt**

### **Flow (18 phases, loops)**

| Phase | Action | Duration | Fire Spheres |
|-------|--------|----------|--------------|
| 1–3 | Sleep → Wake → Takeoff | 2s + 2s + 3s | — |
| 4 | Aim (face player) | 2s | — |
| 5 | **Fire 1 sphere** | instant | 1 |
| 6 | Cooldown | 1.5s | — |
| 7 | **Fire 2 spheres** | instant | 2 |
| 8 | Landing | 3s | — |
| 9–11 | Sleep → Wake → Takeoff | 2s + 2s + 3s | — |
| 12 | Aim | 2s | — |
| 13 | **Fire 1 sphere** | instant | 1 |
| 14 | Cooldown | 1.5s | — |
| 15 | **Fire 2 spheres** | instant | 2 |
| 16–18 | Landing → Sleep → Wake | 3s + 2s + 2s | — |

**Total per round:** 6 fire spheres (1+2 + 1+2)

### **Boss Fight Notes**

- Phoenix sleeps on ground, wakes, takes off, then aims and fires from the air
- Fire spheres use `MeshStandardMaterial` with emissive glow for visibility
- Player collision: `checkPhoenixFireSpherePlayerCollision()` with radius 1.0
- On hit: `phoenixFireLevel6` death type → crushed-style game over → Restart Level 6 / Level Select / Return to Level 1

---

## 🔥 **PATTERN 17: Fire Sphere Hunt Extended**

### **Flow (26 phases, loops)**

Pattern 17 runs **Pattern 16 (phases 1–18)** first, then adds extended phases:

| Phase | Action | Notes |
|-------|--------|-------|
| 1–18 | **Pattern 16** – Cycle 2 (9–18) more aggressive: shorter aim/cooldown, 2 spheres on first fire | 7 fire spheres (3+4 in cycle 2) |
| 19 | Ground wake | Wake up on ground |
| 20 | **Ground attack** | Face player, GroundFireAttack1 / GroundMeleeAttack1 |
| 21 | Takeoff | Rise to patrol height |
| 22 | **Huge circle patrol + fire** | 25-unit radius, 6s patrol, fires 1 sphere mid-patrol |
| 23 | **Dive attack** | Dive toward player, fires 2 spheres on dive |
| 24 | Sleep | GroundSleep |
| 25 | **Ground attack** | Face player, fire/melee attack |
| 26 | Sleep | Final sleep → loop to Phase 1 |

### **Extended Phase Details**

- **Phase 22 (patrol):** Phoenix flies a circle (radius 18) around spawn for 4.5s, fires 1 sphere at 1.5s (one-shot to prevent frame drops)
- **Phase 23 (dive):** Phoenix dives from patrol height toward player ground position, fires 2 spheres on dive start
- **Phase 20 & 25:** Ground melee/fire attacks – Phoenix faces player and uses GroundFireAttack1 or GroundMeleeAttack1

### **Boss Fight Notes**

- Full boss sequence: air barrage → ground fight → patrol + fire → dive attack → ground attack → sleep
- More varied and intense than Pattern 16
- Total fire spheres per round: 6 (Pattern 16) + 1 (patrol) + 2 (dive) = **9 spheres** in extended section

---

## 🎮 **HOW TO USE (GOD Mode)**

| Key | Action |
|-----|--------|
| **B** | Cycle Phoenix behavior (1→2→…→17→1) |
| **F** | Fire 2 test spheres at player (no pattern wait) |

**Requirements:** Level 6, GOD Mode enabled

**Options menu:** Phoenix Boss section has dropdown with all 17 patterns (X/17 format).

---

## 📁 **FILES**

| File | Purpose |
|------|---------|
| `phoenix2.js` | `updateFireSphereHunt()`, `updateFireSphereHuntExtended()`, `shootFireBreath()`, `behaviorDurations` |
| `main.js` | `cyclePhoenixBehavior()`, `checkPhoenixFireSpherePlayerCollision()`, `onPlayerHitByPhoenixFire()`, `restartLevel6()` |
| `gui-system.js` | `phoenixFireLevel6` death type, Restart Level 6 / Level Select / Return to Level 1 |

---

## ✅ **VERIFICATION (February 6, 2026)**

- [x] Pattern 16 runs full 18-phase cycle
- [x] Pattern 17 runs full 26-phase cycle (16 + extended)
- [x] B key cycles 1→17→1 (Pattern 17 reachable)
- [x] Fire spheres visible (levelGroup parent, emissive material)
- [x] Player collision and game over working
- [x] Restart Level 6 clears projectiles and resets state

---

## 🔧 **FINE-TUNING (February 6, 2026 – Harder Boss, Frame Fix)**

### **Frame Drop Fix – Patrol Phase**
- **Issue:** Patrol phase (22) fired 1 sphere **every frame** for 0.5s → 15–30 spheres → massive lag
- **Fix:** One-shot fire with `_patrolFireFired` flag – fires **once** at 1.5s into patrol
- **Result:** Smooth frames during large circle patrol

### **2nd Attack Cycle More Aggressive**
- **Cycle 2 (phases 9–18):** Shorter aim (1.0s vs 1.5s), shorter cooldown (0.6s vs 1.0s)
- **Cycle 2 first fire:** 2 spheres instead of 1 (phase 13)
- **Result:** Second repeat feels noticeably harder

### **Overall Harder Fight**
- **Cycle 1:** aimDuration 1.5s (was 2s), cooldownAfterFirst 1.0s (was 1.5s)
- **Patrol:** Smaller radius (18 vs 25), shorter duration (4.5s vs 6s) – less travel, still dangerous
- **Result:** Faster attacks, less time to react

### **Config Changes (fire_sphere_hunt_extended)**
```javascript
aimDuration: 1.5, cooldownAfterFirst: 1.0,  // Cycle 1 – faster
aimDurationCycle2: 1.0, cooldownAfterFirstCycle2: 0.6,  // Cycle 2 – aggressive
patrolCircleDuration: 4.5, patrolCircleRadius: 18,  // Smaller circle, less lag risk
```

---

**🧀 These patterns form the core of the Level 6 Phoenix boss fight – a real boss encounter with ranged fire attacks, ground combat, and dive attacks.**
