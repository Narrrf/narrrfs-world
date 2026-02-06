# 🐉 Phoenix Fire Sphere Attack – Implementation Plan

**Date:** February 5, 2026  
**Status:** ✅ **RESOLVED – Fireball visibility fix applied (Feb 6, 2026)**  
**Scope:** Level 6 Phoenix Boss – New Pattern 16 with player-targeting fire spheres  
**Files:** `phoenix2.js`, `main.js`, `gui-system.js`

---

## 📋 **CURRENT STATUS (February 6, 2026 – Visibility Fix)**

### ✅ **Working – New Pattern**
- **Takeoff:** Sleep → Wake → Takeoff sequence works perfectly
- **Animations:** Fire attack animations play correctly (FlyIdleFireAttack1, GroundFireballAttack, etc.)
- **Phases 5, 7, 13, 15:** `shootFireBreath(playerPos, 1)` and `shootFireBreath(playerPos, 2)` are called when entering those phases
- **Game over flow:** Crushed-style game over screen implemented (phoenixFireLevel6 death type, Restart Level 6, Level Select, Return to Level 1)
- **Collision:** `checkPhoenixFireSpherePlayerCollision()` runs; sphere radius updated to 1.0 to match geometry
- **Restart:** `restartLevel6()` clears projectiles, resets playerDead, repositions player

### ✅ **Fireball Visibility Fix (Feb 6, 2026)**
- **Parent:** Use `levelGroup || scene` so fireballs are added to Level 6 group (same hierarchy as Phoenix) and hide when leaving level
- **Material:** Switched from `MeshBasicMaterial` to `MeshStandardMaterial` with `emissive: 0xff4400`, `emissiveIntensity: 1.0` for visible glow in all lighting
- **Coordinate space:** When parent is `levelGroup`, convert world spawn position to local with `parent.worldToLocal(fireball.position)`
- **Direction:** Compute from `worldPos` (world space) before converting position to local
- **Layers:** `fireball.layers.set(0)` so camera renders fireballs
- **Spawn position:** Store `fireball.position.clone()` (same coordinate space as position) for distance-based cleanup
- **Debug logging:** Console logs parent type, spawn position, total projectiles when firing

### ✅ **God Mode F Key – Fire Sphere Test (Feb 6, 2026)**
- **Key:** Press **F** in God Mode while in Level 6 to fire 2 fire spheres at player instantly
- **Purpose:** Test fire sphere visibility without waiting for Pattern 16 phases (5, 7, 13, 15)
- **Usage:** Warp to Level 6 → Enable God Mode → Press F → Spheres should spawn from Phoenix mouth and travel toward player
- **Feedback:** Toast "🔥 Fire spheres test fired! (F key)" + console log

---

## ✅ **WORKING PATTERN (New Pattern – Feb 5, 2026)**

The new fire sphere hunt pattern is **fully functional**:

| Component | Status | Notes |
|-----------|--------|-------|
| **Takeoff sequence** | ✅ Working | Sleep → Wake → Takeoff (Pattern 15 style) |
| **Phase state machine** | ✅ Working | 18 phases, correct transitions |
| **Fire phases (5, 7, 13, 15)** | ✅ Called | `shootFireBreath(playerPos, 1)` or `(playerPos, 2)` invoked |
| **Game over on hit** | ✅ Working | `phoenixFireLevel6` death type, crushed-style screen |
| **Restart Level 6** | ✅ Working | Clears projectiles, resets playerDead, repositions player |
| **Collision check** | ✅ Running | `checkPhoenixFireSpherePlayerCollision()` with radius 1.0 |
| **Fire sphere visibility** | ✅ **FIXED** | levelGroup parent, MeshStandardMaterial+emissive, worldToLocal, layers.set(0) |

**Key files:**
- `phoenix2.js` – `updateFireSphereHunt()`, `shootFireBreath()`, `clearFireBreathProjectiles()`
- `main.js` – `checkPhoenixFireSpherePlayerCollision()`, `onPlayerHitByPhoenixFire()`, `restartLevel6()`
- `gui-system.js` – `phoenixFireLevel6` death type, Restart Level 6 / Level Select / Return to Level 1

---

## 🎯 **GOAL**

Add a new Phoenix attack pattern (Pattern 16) where the dragon **spawns fire spheres** that **travel toward the player** and **deal damage on hit**. Similar to Pattern 15 (player_hunt_combo) but focused on **ranged fire attacks** instead of dive/melee.

---

## 📋 **WHAT ALREADY EXISTS**

### ✅ Fire Projectile System (phoenix2.js)

| Component | Status | Location |
|-----------|--------|----------|
| `shootFireBreath(targetPosition)` | ✅ Exists | phoenix2.js ~2692 |
| `fireBreathProjectiles[]` | ✅ Exists | Stores active fire spheres |
| `updateFireBreathProjectiles(delta)` | ✅ Exists | Moves projectiles forward |
| `getFireBreathProjectiles()` | ✅ Exists | Returns array for hit detection |

**Current `shootFireBreath` behavior:**
- Creates SphereGeometry (0.3 radius) with orange emissive material
- Positions at dragon mouth (`model.position + mouthOffset`)
- Calculates direction: `targetPosition - fireball.position`
- Velocity: 20 units/second toward target
- Adds to `levelGroup` or `scene`
- Plays random fire attack animation

### ⚠️ What's Missing

| Component | Status | Notes |
|-----------|--------|-------|
| **Player collision** | ❌ Not implemented | Projectiles move but never check player hit |
| **Player damage** | ❌ Not implemented | No `onPlayerHit` or player health system |
| **Pattern that spawns fire** | ❌ Not called | `shootFireBreath` exists but is never invoked |
| **getPlayerPosition callback** | ⚠️ Partial | Pattern 15 uses `this.player.position` – may need `getPlayerPosition` like Alien Spider (playerCollider center) |

---

## 🏗️ **ARCHITECTURE OVERVIEW**

**Includes Pattern 15 takeoff** (sleep → wake → takeoff) + **long attack** (1 sphere, then 2 spheres) **repeated 2 times**. **Between cycles:** Pattern 15 style **landing → sleep → wake** (no patrol/observe).

```
┌─────────────────────────────────────────────────────────────────┐
│  NEW PATTERN: fire_sphere_hunt (Pattern 16)                      │
├─────────────────────────────────────────────────────────────────┤
│  ═══ TAKEOFF (from Pattern 15) ═══                               │
│  Phase 1: Sleep (2s) – GroundSleep on ground                     │
│  Phase 2: Wake Up (2s) – GroundWakeUp / GroundAwake              │
│  Phase 3: Takeoff (3s) – StartFly, rise to patrol height         │
│  ═══ ATTACK CYCLE 1 ═══                                          │
│  Phase 4: Aim (2s) – FlyIdle, face player                        │
│  Phase 5: Fire 1 sphere → shootFireBreath(playerPos, count: 1)   │
│  Phase 6: Cooldown (1.5s) – let sphere travel                     │
│  Phase 7: Fire 2 spheres → shootFireBreath(playerPos, count: 2)  │
│  Phase 8: LANDING (like Pattern 15) – descend to ground           │
│  Phase 9: Sleep (2s) – GroundSleep on ground                      │
│  Phase 10: Wake (2s) – GroundWakeUp                              │
│  Phase 11: Takeoff (3s) – StartFly, rise again                   │
│  ═══ ATTACK CYCLE 2 ═══                                          │
│  Phase 12: Aim (2s) – FlyIdle, face player                       │
│  Phase 13: Fire 1 sphere → shootFireBreath(playerPos, count: 1)   │
│  Phase 14: Cooldown (1.5s) – let sphere travel                    │
│  Phase 15: Fire 2 spheres → shootFireBreath(playerPos, count: 2) │
│  Phase 16: LANDING – descend to ground                            │
│  Phase 17: Sleep (2s) – GroundSleep                               │
│  Phase 18: Wake (2s) – GroundWakeUp                              │
│  → LOOP back to Phase 1 (Sleep)                                   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  main.js – Level 6 Update Loop                                    │
├─────────────────────────────────────────────────────────────────┤
│  Each frame:                                                      │
│  1. phoenixBoss.update(delta)  → moves fire spheres               │
│  2. checkFireSpherePlayerCollision()  → NEW: sphere vs player      │
│  3. If hit: remove sphere, call onPlayerHitByFire(damage)         │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📐 **IMPLEMENTATION PHASES**

### **Phase 1: Add getPlayerPosition to Phoenix (like Alien Spider)**

**Problem:** Pattern 15 uses `this.player.position` – but in Level 6, player position comes from `playerCollider` (Capsule: start/end). Center = `lerpVectors(start, end, 0.5)`.

**Solution:** Pass `getPlayerPosition` callback in Phoenix config (same pattern as Alien Spider).

**File:** `main.js` – where PhoenixBoss2 is created for Level 6

```javascript
phoenixBoss = new PhoenixBoss2({
  // ... existing config ...
  getPlayerPosition: () => {
    if (!playerCollider) return phoenixBoss?.spawnPosition?.clone() || new THREE.Vector3(0, 1, 0);
    return new THREE.Vector3().lerpVectors(playerCollider.start, playerCollider.end, 0.5);
  }
});
```

**File:** `phoenix2.js` – constructor

```javascript
this.getPlayerPosition = config.getPlayerPosition || null;
```

**Update:** All patterns that need player position (15, 16) use `this.getPlayerPosition()` instead of `this.player.position`.

---

### **Phase 2: Create Pattern 16 – fire_sphere_hunt**

**File:** `phoenix2.js`

#### 2a. Add behaviorDurations

```javascript
fire_sphere_hunt: {
  // Takeoff (from Pattern 15)
  sleepDuration: 2.0,
  wakeupDuration: 2.0,
  takeoffDuration: 3.0,
  // Attack cycle (repeated 2 times)
  aimDuration: 2.0,
  cooldownAfterFirst: 1.5,
  landingDuration: 3.0,        // Like Pattern 15 – descend to ground
  // Between cycles: sleep, wake, takeoff (same durations)
}
```

#### 2b. Add updateFireSphereHunt(delta)

**State machine (18 phases):**

| Phase | Name | Duration | Animation | Action |
|-------|------|----------|-----------|--------|
| 1 | sleep | 2s | GroundSleep | On ground |
| 2 | wakeup | 2s | GroundWakeUp | On ground |
| 3 | takeoff | 3s | StartFly | Rise to patrol height |
| 4 | aim | 2s | FlyIdle1 | Face player |
| 5 | fire_1 | instant | GroundFireballAttack | **shootFireBreath(pos, 1)** |
| 6 | cooldown_1 | 1.5s | FlyIdle1 | Let sphere travel |
| 7 | fire_2 | instant | GroundFireballAttack | **shootFireBreath(pos, 2)** |
| 8 | **landing** | 3s | EndFly / Landing | **Descend to ground (Pattern 15 style)** |
| 9 | sleep | 2s | GroundSleep | Fall asleep on ground |
| 10 | wakeup | 2s | GroundWakeUp | Wake up |
| 11 | takeoff | 3s | StartFly | Rise again |
| 12 | aim | 2s | FlyIdle1 | Face player |
| 13 | fire_1 | instant | GroundFireballAttack | **shootFireBreath(pos, 1)** |
| 14 | cooldown_1 | 1.5s | FlyIdle1 | Let sphere travel |
| 15 | fire_2 | instant | GroundFireballAttack | **shootFireBreath(pos, 2)** |
| 16 | **landing** | 3s | EndFly / Landing | Descend to ground |
| 17 | sleep | 2s | GroundSleep | Fall asleep |
| 18 | wakeup | 2s | GroundWakeUp | Wake up → **loop to Phase 1** |

**Between attack cycles:** Landing → Sleep → Wake → Takeoff (Pattern 15 style). No patrol/observe.

**Total per round:** **6 fire spheres** (1+2 + 1+2)

#### 2c. shootFireBreath modification

**Current:** `shootFireBreath(targetPosition)` – uses `fireballCount` from `currentPhase`.

**Required:** Add optional 2nd param for explicit count:

```javascript
shootFireBreath(targetPosition, fireballCount = 1)
```

- Phase 5 (fire_1): `shootFireBreath(getPlayerPosition(), 1)`
- Phase 7 (fire_2): `shootFireBreath(getPlayerPosition(), 2)`

---

### **Phase 3: Player Collision Detection**

**File:** `main.js` – Level 6 update section

**New function:** `checkPhoenixFireSpherePlayerCollision()`

```javascript
function checkPhoenixFireSpherePlayerCollision() {
  if (currentLevel !== LEVEL_IDS.LEVEL6 || !phoenixBoss) return;
  
  const projectiles = phoenixBoss.getFireBreathProjectiles();
  if (!projectiles || projectiles.length === 0) return;
  
  // Player center (capsule midpoint)
  const playerCenter = new THREE.Vector3().lerpVectors(
    playerCollider.start, playerCollider.end, 0.5
  );
  const playerRadius = PLAYER_RADIUS || 0.5; // Match player capsule radius
  
  for (let i = projectiles.length - 1; i >= 0; i--) {
    const proj = projectiles[i];
    const dist = proj.position.distanceTo(playerCenter);
    const sphereRadius = 0.3; // Fire sphere radius (from SphereGeometry)
    
    if (dist < playerRadius + sphereRadius) {
      // HIT! Remove projectile and damage player
      if (proj.parent) proj.parent.remove(proj);
      projectiles.splice(i, 1);
      
      // Apply damage to player
      onPlayerHitByPhoenixFire(PHOENIX_FIRE_DAMAGE);
    }
  }
}
```

**Call from:** Level 6 update block, after `phoenixBoss.update(delta)`.

---

### **Phase 4: Player Damage System**

**Question:** Does the game have a player health system?

**Options:**

| Option | Approach | Complexity |
|--------|----------|------------|
| **A** | Add `playerHealth`, `playerMaxHealth` – reduce on hit, show in HUD | Medium |
| **B** | Callback only – `onPlayerHitByPhoenixFire(damage)` – main.js handles (e.g. flash screen, sound, future health) | Low |
| **C** | Instant death / respawn – fire sphere = one-hit kill | Simple |

**Recommendation:** Start with **Option B** – define callback, log damage, add placeholder. Health system can be added later.

**File:** `main.js`

```javascript
const PHOENIX_FIRE_DAMAGE = 10; // Configurable

function onPlayerHitByPhoenixFire(damage) {
  console.log(`🔥 [LEVEL 6] Player hit by Phoenix fire! Damage: ${damage}`);
  // TODO: Reduce player health, update HUD, play hurt sound
  // For now: visual feedback (screen flash red?), play hurt sound
}
```

---

### **Phase 5: Fire Sphere Visual Polish**

**Current:** Orange sphere (0.3 radius), MeshBasicMaterial

**Enhancements (optional):**
- Slightly larger (0.4–0.5) for visibility
- Add subtle scale pulse (grow/shrink) for "living fire" feel
- Trail effect (optional – particles behind sphere)
- Hit effect – brief flash/explosion when hitting player

---

### **Phase 6: GUI & Cycling**

**Files:** `main.js`, `gui-system.js`

- Add `fire_sphere_hunt` to Phoenix behavior cycle (B key)
- Add to God Mode dropdown: "🔥 Fire Sphere Hunt"
- Update display: "X/16" (was 15)

---

## 📁 **FILES TO MODIFY**

| File | Changes |
|------|---------|
| `phoenix2.js` | Add `getPlayerPosition` in config, `fire_sphere_hunt` pattern, `updateFireSphereHunt()`, use `getPlayerPosition` in pattern 15 |
| `main.js` | Pass `getPlayerPosition` to Phoenix, add `checkPhoenixFireSpherePlayerCollision()`, add `onPlayerHitByPhoenixFire()`, call collision check in Level 6 update, add pattern to cycle & dropdown |
| `gui-system.js` | Update Phoenix behavior count 15 → 16 |

---

## 🔧 **TECHNICAL NOTES**

### Pattern 15 Landing Reference

**Phases 8 & 16 (landing):** Use the same landing logic as Pattern 15 (`player_hunt_combo`):
- Descend from patrol height to ground (e.g. `patrolHeight` → `groundY`)
- Animation: EndFly or equivalent landing animation
- Duration: ~3s (configurable via `landingDuration`)
- After landing: dragon is on ground, ready for Sleep phase

### Fire Sphere Spawn Position

Current: `model.position + mouthOffset(0, 0, 2)`

- Mouth offset may need tuning per dragon model
- For flying dragon: use model position + forward direction
- Consider: `model.position.clone().add(new THREE.Vector3(0, 0, 2).applyQuaternion(model.quaternion))`

### Projectile Lifetime

- Current: removed when `distance > 50` from spawn
- Add: max lifetime (e.g. 5 seconds) to avoid stale projectiles
- Add: `projectile.spawnTime = performance.now()` in shootFireBreath

### Multiple Spheres

- Modify `shootFireBreath(targetPosition, fireballCount)` – add explicit 2nd param (override phase-based count)
- **Attack 1:** `shootFireBreath(getPlayerPosition(), 1)` – single sphere
- **Attack 2:** `shootFireBreath(getPlayerPosition(), 2)` – two spheres (slight spread)
- Per full round: 6 spheres total (1+2 + 1+2, repeated twice)

---

## ⚠️ **EDGE CASES**

1. **Player not in Level 6** – Don't run collision check
2. **Player respawns** – Fire spheres may still be in flight; let them expire or hit
3. **Phoenix defeated** – `defeat()` already clears `fireBreathProjectiles`
4. **Level unload** – Ensure projectiles removed when leaving Level 6

---

## 🧪 **TESTING CHECKLIST**

- [ ] Phoenix spawns fire sphere when in `fire_sphere_hunt` mode
- [ ] Fire sphere moves toward player position
- [ ] Fire sphere hits player (collision detected)
- [ ] Player takes damage (callback fires)
- [ ] Fire sphere removed on hit
- [ ] B key cycles to new pattern
- [ ] GUI shows "16" patterns
- [ ] No errors when `getPlayerPosition` returns fallback

---

## 📝 **SUMMARY**

| Item | Value |
|------|-------|
| **New pattern** | `fire_sphere_hunt` |
| **Pattern number** | 16 |
| **Takeoff** | Pattern 15 style: Sleep → Wake → Takeoff |
| **Attack sequence** | 1 sphere → (cooldown) → 2 spheres |
| **Between cycles** | **Landing → Sleep → Wake** (Pattern 15 style) – no patrol/observe |
| **Repeats** | 2 times per round (6 spheres total: 1+2 + 1+2) |
| **Key mechanic** | Dragon takes off, attacks, lands, sleeps, wakes, takes off again, attacks, lands, sleeps, wakes, loop |
| **Reuses** | `shootFireBreath()`, `updateFireBreathProjectiles()`, `getFireBreathProjectiles()` |
| **Modify** | `shootFireBreath(targetPos, fireballCount)` – add explicit count param |
| **New code** | `updateFireSphereHunt()`, `checkPhoenixFireSpherePlayerCollision()`, `onPlayerHitByPhoenixFire()` |
| **Config** | `getPlayerPosition` callback (like Alien Spider) |

---

**Next step:** Implement Phase 1 (getPlayerPosition) and Phase 2 (fire_sphere_hunt with takeoff + 1+2×2 attack), then add collision (Phase 3) and damage callback (Phase 4).
