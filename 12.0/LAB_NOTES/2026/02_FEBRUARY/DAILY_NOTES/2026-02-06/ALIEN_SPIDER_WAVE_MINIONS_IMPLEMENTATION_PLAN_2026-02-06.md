# 🕷️ Alien Spider Wave Minions – Level 6 Boss Fight Implementation Plan

**Date:** February 6, 2026  
**Status:** ✅ **IMPLEMENTATION COMPLETE**  
**Scope:** Level 6 – Add dangerous Alien Spider minions that spawn in waves while Phoenix Dragon fights  
**Files:** `alien-spider.js`, `main.js`, `weapon-system.js`

---

## 🔧 **CRITICAL FIXES (Feb 6, 2026 – Handoff)**

### **Issue 1: Plate disappears, nothing happens**
- **Cause:** Trigger block used `floorY = 0` while Level 6 map (thefield.gltf) has ground at a different Y. Plate was underground; player never stood on it.
- **Fix:** Use actual ground: `groundY = Math.max(0, spawnPosition.y - 2)` so plate sits on walkable surface.
- **File:** `main.js` – `createLevel6TriggerBlock()`

### **Issue 2: Spider minions too large (fill screen)**
- **Cause:** `createFromCache()` destructured only `model`, `clips`, `originalSize` from `_minionCache`. `basePath` and `tgaLoader` were undefined, so `_applyMinionMaterials()` was never called (`if (tgaLoader && basePath)` failed). Materials/textures not applied; scaling/bbox logic may have been affected.
- **Fix:** Add `basePath`, `tgaLoader` to destructuring: `const { model, clips, originalSize, basePath, tgaLoader } = _minionCache;`
- **File:** `alien-spider.js` – `AlienSpiderMinion.createFromCache()`

### **Issue 3: Minions still huge after material fix (Feb 6, 2026 – evening)**
- **Cause:** SkinnedMesh bbox can be wrong before skeleton pose; fallback scale had no absolute cap; world-size safeguard used 1.5 units (still large).
- **Fix (alien-spider.js):**
  - Hard cap: `scale = Math.min(scale, 0.05)` – AFC_03 is ~100+ units, scale > 0.05 makes minion huge
  - Skeleton pose before bbox: `clone.traverse(c => { if (c.isSkinnedMesh && c.skeleton) c.skeleton.pose(); })`
  - Reduce max minion size: 1.5 → 1.0 units (dog-sized)
- **Fix (main.js):** God-mode debug log for `bossSize`, `bossOriginalSize`, `bossScale`, `minionTargetSize`

### **Rigging note (minion vs boss)**
- Boss and minion both use **AFC_03.fbx** and **SkeletonUtils.clone** (Rule 14).
- If minion looks stiff/elongated vs boss: FBX default pose or animation binding may differ; both use same walk animation.

---

## ✅ **IMPLEMENTATION SUMMARY (Feb 6, 2026)**

| Phase | Status | Location |
|-------|--------|----------|
| Phase 1: AlienSpiderMinion class | ✅ | `alien-spider.js` lines 1409–1599 |
| Phase 2: Wave spawn system | ✅ | `main.js` lines 3552–3694 |
| Phase 3: Melee death | ✅ | `main.js` – `onPlayerHitBySpiderMinion`, `checkSpiderMinionPlayerCollision` |
| Phase 4: Weapon raycast | ✅ | `weapon-system.js` lines 1340–1368, 1548–1554 |
| Phase 5: Death & cleanup | ✅ | `main.js` – `onMinionDied`, `cleanupAllLevels` spider section |
| Wiring: getLevel6State | ✅ | `main.js` line 11720 (weaponConfig) |
| Wiring: updateLevel6SpiderWaves | ✅ | `main.js` line 27065 (updateLevel6) |
| Wiring: spawn on build | ✅ | `main.js` line 25533 (buildLevel6PhoenixArena) |
| Wiring: spawn on return | ✅ | `main.js` line 29971 (warpToLevel6 already-built branch) |

---

## 🎯 **GOAL**

Integrate the Alien Spider into the Level 6 boss fight as **dangerous ground minions** that:
- Spawn in **waves** (like Level 4 monster waves)
- **Chase the player** (mouse) and **melee attack** when in range
- Fight **simultaneously** with the Phoenix Dragon (dual threat)
- Can be **shot by the player** (weapon system already active in Level 6)
- Create a **chaotic, dangerous** boss arena experience

---

## 📋 **CURRENT STATE**

### ✅ **What Exists**

| Component | Status | Location |
|-----------|--------|----------|
| **Alien Spider Boss** | ✅ Single instance | `alien-spider.js` – `AlienSpiderBoss` class |
| **Model** | ✅ FBX (AFC_03.fbx) | `/textures/3d models/Alien Spider 1/AFC_03/` |
| **Animations** | ✅ 7 animations | Idle_1, Idle_2, Walk, Run, Attack_1, Attack_2, Damage_taken |
| **Behaviors** | ✅ 12 patterns | `follow_attack` chases player, `attack_1`/`attack_2` melee |
| **Level 6 Arena** | ✅ Phoenix + Spider | `buildLevel6PhoenixArena()` – Spider at (-20, 1, 0) |
| **Weapon System** | ✅ Active in Level 6 | Player can shoot |
| **Phoenix Fire** | ✅ Pattern 16/17 | Dragon fires at player |

### ❌ **What's Missing**

| Component | Status | Notes |
|-----------|--------|-------|
| **Multiple spider instances** | ❌ | Only 1 boss – need wave spawning |
| **Spider melee damage** | ❌ | Attack animations play but no player damage |
| **Spider health / death** | ❌ | Boss has health; minions need HP + death |
| **Wave spawn logic** | ❌ | No wave system for spiders in Level 6 |
| **Raycast vs spiders** | ❌ | Player shots don't hit spiders (no hit detection) |
| **Spider–Phoenix sync** | ❌ | Spawn timing not tied to Phoenix phases |

---

## 🏗️ **ARCHITECTURE OVERVIEW**

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  LEVEL 6 BOSS FIGHT – DUAL THREAT                                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  🐉 PHOENIX DRAGON (existing)          🕷️ ALIEN SPIDER MINIONS (new)         │
│  - Patterns 16/17 fire spheres        - Spawn in waves (2–4 per wave)        │
│  - Flies, dives, fires at player       - Chase player (follow_attack logic)   │
│  - Player must dodge fire              - Melee attack when in range           │
│                                        - Player can shoot them               │
│                                        - Deal damage on touch (melee)        │
│                                                                              │
│  SPAWN SYNC: Spiders spawn when Phoenix is in combat (Pattern 16/17 active)  │
│  WAVE TRIGGER: Time-based or Phoenix phase-based (e.g. every 30s, or on      │
│                specific Phoenix phases)                                      │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 📐 **IMPLEMENTATION PHASES**

### **Phase 1: Alien Spider Minion Class (Spawnable Instance)**

**Purpose:** Create a lightweight, spawnable spider instance (not full boss) for wave minions.

**Option A – Reuse AlienSpiderBoss with factory:**
- Add `AlienSpiderBoss.spawnMinion(config)` – returns a minion instance
- Minions share same model/animations but have: reduced health, smaller size, `follow_attack` only

**Option B – New AlienSpiderMinion class (recommended):**
- New class in `alien-spider.js` or `alien-spider-minions.js`
- Loads model once, clones for each minion (FBX: use `clone(true)` per Rule 18)
- Simpler: only `follow_attack` behavior, no boss GUI/config
- Each minion: `{ model, mixer, health, position, behavior: 'follow_attack' }`

**FBX Cloning (Rule 18 – 3D Model Rendering):**
- Alien Spider is FBX – **MUST clone** for multiple instances
- `const spiderMesh = result.isFBX ? loadedScene.clone(true) : loadedScene`
- Material processing required (dark FBX materials)

**SkeletonUtils for FBX?**
- Rule 14: SkeletonUtils for **GLTF** skinned meshes
- Alien Spider is **FBX** – Rule 18 says standard `clone(true)` is used for FBX
- Test: If cloned FBX spiders render correctly, use `clone(true)`. If invisible, try SkeletonUtils.

**Files:** `alien-spider.js` – add `AlienSpiderMinion` class or `spawnMinion()` factory

---

### **Phase 2: Wave Spawn System**

**Purpose:** Spawn spider minions in waves during the Level 6 boss fight.

**Wave Config (suggested):**
```javascript
const LEVEL6_SPIDER_WAVE_CONFIG = {
  enabled: true,                    // Toggle (God Mode or boss config)
  spidersPerWave: 3,                // 2–4 spiders per wave
  waveIntervalSeconds: 25,          // Spawn new wave every 25s
  maxSpidersAlive: 8,               // Cap total spiders (performance)
  spawnRadius: 25,                  // Spawn around arena edge (not center)
  spawnHeight: 1.0,                 // Ground level
  minionHealth: 3,                  // 3 shots to kill (or 1 with strong weapon)
  minionSize: 2.0,                  // Smaller than boss (4.0)
  minionSpeed: 3.5,                 // Slightly faster than boss follow_attack
  textureVariation: 'Default'       // Or random: Default, Fur_1, Fur_2
};
```

**Spawn Positions:**
- Random points on circle: `spawnRadius * (cos(angle), sin(angle))` around arena center
- Avoid spawning on top of player
- Arena center ~ (20, 10, 0) for Phoenix; use similar center for spider spawn circle

**Wave Trigger Options:**
1. **Time-based:** Every N seconds while in Level 6 and Phoenix is active
2. **Phoenix phase-based:** Spawn on specific Pattern 16/17 phases (e.g. when dragon lands)
3. **Hybrid:** Time-based, but only when Phoenix is in combat (not idle/sleep)

**Files:** `main.js` – `level6SpiderWaveState`, `spawnLevel6SpiderWave()`, `updateLevel6SpiderWaves(delta)`

---

### **Phase 3: Spider Minion Behavior – Chase & Melee**

**Purpose:** Spiders chase player and deal damage on contact (melee – no projectiles).

**Behavior (reuse `follow_attack` logic):**
1. **Walk** toward player (`getPlayerPosition()`)
2. When in range (e.g. 2.5 units): play **Attack_1** or **Attack_2**
3. During attack animation: **check overlap with player** → deal damage
4. After attack: brief idle, then resume chase

**Melee Damage Detection:**
- **Option A – Bounding box overlap:** Spider bounding box vs player capsule
- **Option B – Distance check during attack:** When `Attack_1` or `Attack_2` is playing and `distance(spider, player) < MELEE_RANGE` (e.g. 2.5) for at least 0.3s → deal damage once per attack
- **Option B** is simpler and avoids continuous damage while standing in overlap

**Damage Values:**
```javascript
const SPIDER_MINION_MELEE_DAMAGE = 5;   // Per hit (tune for balance)
const SPIDER_MELEE_RANGE = 2.5;          // Units
const SPIDER_ATTACK_COOLDOWN = 1.5;      // Seconds between damage applications
```

**Player Health (if not exists):**
- Level 6 may use instant death (Phoenix fire = crush). For spiders, options:
  - **A:** Same – spider touch = instant death (very dangerous)
  - **B:** Add simple `playerHealth` – spiders reduce it, 0 = death
  - **C:** Spider touch = "stagger" (brief stun, no death) – less lethal
- **Recommendation:** Start with **A** (instant death) for maximum danger; add health later if needed

**Files:** `alien-spider.js` – minion `update()` with chase + melee check; `main.js` – `checkSpiderMinionPlayerCollision()`

---

### **Phase 4: Player Shoots Spiders (Raycast Hit Detection)**

**Purpose:** Player weapon shots can hit and kill spider minions.

**Current:** Level 6 has weapon system; shots use raycast. Need to add spider minions to raycast targets.

**Implementation:**
1. **Raycast targets:** Include all spider minion models in raycast check (or use a dedicated `spiderMinions[]` array with bounding boxes)
2. **On hit:** Reduce spider health; if health ≤ 0, remove from scene, play death animation (or simple vanish)
3. **Weapon system:** `weapon-system.js` or `main.js` – extend raycast to check spider meshes

**Reference:** Level 4 monster raycast – `checkLevel4MonsterRaycast()` or similar. Reuse pattern for Level 6 spiders.

**Files:** `main.js` – `checkLevel6SpiderMinionRaycast()` or extend existing weapon raycast

---

### **Phase 5: Spider Death & Cleanup**

**Purpose:** Spiders die when health reaches 0; clean up properly.

**Death Behavior:**
- Play `Damage_taken` animation briefly (0.5s) then remove
- Or: instant vanish (simpler)
- Remove from `level6SpiderMinions[]`
- Remove model from scene/levelGroup
- Dispose mixer, actions (memory cleanup)

**Files:** `alien-spider.js` – `minion.takeDamage(amount)`, `minion.die()`; `main.js` – call from raycast hit

---

### **Phase 6: Phoenix–Spider Sync (Optional Enhancement)**

**Purpose:** Spawn spiders at dramatic moments (e.g. when dragon lands, or every 2nd fire cycle).

**Option A – Phoenix phase callback:**
- Phoenix emits `onPhaseChange(phaseId)` or similar
- main.js listens, spawns wave when phase in [9, 10, 17, 18, 24, 26] (ground phases)

**Option B – Simple timer:**
- `level6SpiderWaveState.lastSpawnTime` – spawn every 25s
- No Phoenix coupling – easier to implement

**Recommendation:** Start with **Option B** (timer). Add phase sync later for polish.

---

### **Phase 7: GUI & Config**

**Purpose:** Toggle spider waves, adjust difficulty.

**God Mode – Level 6 Boss Config:**
- Checkbox: "🕷️ Enable Spider Minion Waves"
- Slider: Spiders per wave (1–5)
- Slider: Wave interval (15–45s)
- Slider: Minion health (1–5)

**Persistence:** Save to `localStorage` per level (like Phoenix/Spider boss settings).

**Files:** `main.js` – Options menu; `gui-system.js` – if GUI handles boss config

---

## 📁 **FILES TO MODIFY**

| File | Changes |
|------|---------|
| `alien-spider.js` | Add `AlienSpiderMinion` class (or `spawnMinion()`), clone logic, `takeDamage()`, `die()`, melee damage check in update |
| `main.js` | `level6SpiderWaveState`, `spawnLevel6SpiderWave()`, `updateLevel6SpiderWaves()`, `checkSpiderMinionPlayerCollision()`, raycast for spider hits, `updateLevel6()` calls |
| `phoenix2.js` | (Optional) Phase callback for spawn sync |
| `gui-system.js` | Spider wave toggle, config sliders |

---

## 🔧 **TECHNICAL NOTES**

### **Model Loading – Shared Cache**
- Load Alien Spider model **once** (first minion spawn)
- Clone for each minion – avoid loading 3+ times per wave
- Use `modelCache` or similar: `getCachedModel('alien_spider')` → clone

### **Performance**
- Max 8 spiders alive – reasonable for mobile
- Reuse mixers carefully – each minion needs own mixer for independent animation
- FBX clone: each clone gets own mixer from same animation clips

### **Collision – Player vs Spider**
- Player capsule: `playerCollider.start`, `playerCollider.end`, `PLAYER_RADIUS`
- Spider: bounding box or sphere at `spider.model.position` with radius ~1.5 (scaled)
- Overlap: `distance(playerCenter, spiderPos) < PLAYER_RADIUS + SPIDER_MELEE_RANGE`

### **Rule Compliance**
- **Rule 14 (GLTF):** Alien Spider is FBX – SkeletonUtils not required per rule
- **Rule 18 (FBX):** Must clone FBX for multiple instances; process materials
- **Rule 19 (Chest):** N/A
- **Rule 12 (Universal Level):** Level 6 already has GOD Mode, L key, etc.

---

## ⚠️ **EDGE CASES**

1. **Player leaves Level 6** – Clear all spider minions, reset wave state
2. **Phoenix defeated** – Option: stop spawning, or let remaining spiders fight
3. **Restart Level 6** – `restartLevel6()` must clear `level6SpiderMinions`
4. **God Mode warp** – Spiders should persist (or clear – decide per design)

---

## 🧪 **TESTING CHECKLIST**

- [ ] Spider minions spawn in waves
- [ ] Spiders chase player (follow_attack behavior)
- [ ] Spiders deal melee damage when in range
- [ ] Player can shoot spiders (raycast hit)
- [ ] Spiders die when health reaches 0
- [ ] No more than maxSpidersAlive at once
- [ ] Wave interval respected
- [ ] Phoenix and spiders fight simultaneously
- [ ] Restart Level 6 clears spiders
- [ ] GUI toggle enables/disables waves

---

## 📝 **SUMMARY**

| Item | Value |
|------|-------|
| **New system** | Spider minion waves |
| **Spawn** | Wave-based, 2–4 per wave, every ~25s |
| **Behavior** | Chase player, melee attack when in range |
| **Damage** | Spiders deal 5 dmg (or instant death); player shoots to kill |
| **Model** | FBX clone (Alien Spider) – reuse existing asset |
| **Sync** | Timer-based (optional: Phoenix phase sync) |
| **Config** | God Mode toggle, wave interval, spiders per wave |

---

**Next step:** Implement Phase 1 (AlienSpiderMinion class) and Phase 2 (wave spawn), then Phase 3 (melee damage) and Phase 4 (player shoots spiders).
