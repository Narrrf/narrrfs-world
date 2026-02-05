# Alien Spider – Follow & Jump Attack Pattern Plan

**Date:** February 4, 2026  
**Status:** 📋 **PLAN – Phase 1 COMPLETE**  
**Scope:** Level 6 Alien Spider – new behavior pattern  
**Files:** `public/three.js/alien-spider.js`, `public/three.js/main.js`, `public/three.js/gui-system.js`

**Phase 1 (✅ COMPLETE – Feb 4, 2026):** `getPlayerPosition` callback added to AlienSpiderBoss config. main.js passes callback that returns `lerpVectors(playerCollider.start, playerCollider.end, 0.5)`; fallback to `spiderSpawnPos.clone()` when playerCollider unavailable.

---

## 🎯 Pattern Overview

**Name:** `follow_attack` (or `follow_player_jump_attack`)

**Behavior flow:**
1. **Follow mode** – Spider continuously tracks and moves toward the player
2. **Proximity check** – When within attack range, trigger jump attack
3. **Jump attack** – Spider jumps toward player position, plays Attack_2 animation
4. **Land & idle** – After attack completes, transition to idle/sleep mode
5. **Cooldown** – Optional: brief idle before re-entering follow mode (or stay idle until manually cycled)

---

## 📋 Current Alien Spider Architecture (Reference)

### Existing patterns (7 total)
| Mode | Animation | Behavior |
|------|-----------|----------|
| `idle_1` | Idle_1 | Stand still |
| `idle_2` | Idle_2 | Stand still |
| `walk_patrol` | Walk | Circle around spawn |
| `run_patrol` | Run | Fast circle |
| `attack_1` | Attack_1 | Melee forward, then idle |
| `attack_2` | Attack_2 | Jump attack, then idle |
| `damage_reaction` | Damage_taken | Hit reaction, then idle |

### Key integration points
- **Constructor:** `config.player` – currently passed but **not used** for tracking
- **Player position:** main.js has `playerCollider` (Capsule) – center = `lerpVectors(start, end, 0.5)`
- **Animations:** Attack_2 is the jump attack – ideal for “jump on player”
- **Update loop:** `alienSpiderBoss.update(delta)` called from main.js Level 6 update

---

## 🔧 Implementation Plan

### Phase 1: Player position access

**Problem:** `alien-spider.js` receives `config.player` but it may be the player model object, not the collider. The actual player position comes from `playerCollider` in main.js.

**Options:**

| Option | Approach | Pros | Cons |
|--------|----------|------|------|
| **A** | Pass `getPlayerPosition` callback in config | Clean, no coupling to main.js internals | main.js must provide callback |
| **B** | Pass `playerCollider` (Capsule) in config | Direct access | alien-spider needs to know Capsule API |
| **C** | Pass `{ start, end }` or `Vector3` updated each frame | Simple | main.js must update ref every frame |

**Recommendation:** **Option A** – `getPlayerPosition: () => Vector3`

```javascript
// In main.js – when creating alienSpiderBoss for Level 6
alienSpiderBoss = new AlienSpiderBoss({
  // ... existing config ...
  getPlayerPosition: () => {
    if (!playerCollider) return alienSpiderBoss?.spawnPosition?.clone() || new THREE.Vector3(0, 1, 0);
    const center = new THREE.Vector3().lerpVectors(playerCollider.start, playerCollider.end, 0.5);
    return center;
  }
});
```

---

### Phase 2: New behavior mode in alien-spider.js

**Add to `behaviorDurations`:**
```javascript
follow_attack: {
  followSpeed: 3.0,           // Movement speed when following
  attackRange: 4.0,          // Distance to trigger jump attack (units)
  attackDuration: 2.5,       // Attack_2 animation length
  idleAfterAttack: 3.0,      // Idle/sleep duration before re-follow (or infinite)
  reFollowAfterIdle: true     // If true, loop back to follow; if false, stay idle
}
```

**Add to `updateBehavior()` switch:**
```javascript
case 'follow_attack':
  this.updateFollowAttack(delta);
  break;
```

**Add to `setBehaviorMode()`:**
```javascript
case 'follow_attack':
  this.playAnimation('Walk', true);  // Start with walk toward player
  this.followAttackState = 'following';
  this.followAttackTimer = 0;
  console.log("🕷️ [ALIEN_SPIDER] Started follow_attack pattern");
  break;
```

---

### Phase 3: State machine for follow_attack

**States:**
1. `following` – Move toward player (Walk or Run animation)
2. `attacking` – Play Attack_2 (jump), move toward last-known player position
3. `idle_recovery` – Play Idle_1 or Idle_2 (sleep)
4. (Optional) back to `following` if `reFollowAfterIdle`

**Logic outline:**
```
updateFollowAttack(delta):
  if !getPlayerPosition: fallback to idle_1
  
  playerPos = getPlayerPosition()
  distToPlayer = distance(spider.position, playerPos)
  
  switch followAttackState:
    case 'following':
      if distToPlayer <= attackRange:
        followAttackState = 'attacking'
        lastPlayerPos = playerPos.clone()
        playAnimation('Attack_2', false)
        followAttackTimer = 0
      else:
        moveToward(playerPos, followSpeed)
        faceDirection(playerPos - spider.position)
    
    case 'attacking':
      followAttackTimer += delta
      // Optional: lerp spider position toward lastPlayerPos during jump
      if followAttackTimer >= attackDuration:
        followAttackState = 'idle_recovery'
        playAnimation('Idle_1', true)
        followAttackTimer = 0
    
    case 'idle_recovery':
      followAttackTimer += delta
      if reFollowAfterIdle && followAttackTimer >= idleAfterAttack:
        followAttackState = 'following'
        playAnimation('Walk', true)
        followAttackTimer = 0
      // else: stay idle (user can cycle to another pattern via N key)
```

---

### Phase 4: Movement toward player

**New helper (in alien-spider.js):**
```javascript
moveToward(targetPos, speed) {
  const dx = targetPos.x - this.model.position.x;
  const dz = targetPos.z - this.model.position.z;
  const dist = Math.sqrt(dx*dx + dz*dz);
  if (dist < 0.01) return;
  const nx = dx / dist;
  const nz = dz / dist;
  this.model.position.x += nx * speed * delta;
  this.model.position.z += nz * speed * delta;
  this.model.position.y = this.groundY;  // Keep on ground
  // Face movement direction
  this.model.rotation.y = Math.atan2(-nx, nz);
}
```

---

### Phase 5: Jump attack – position interpolation (optional)

**Option A (simple):** Spider stays in place, plays Attack_2. Visually: attack in place.

**Option B (enhanced):** During Attack_2, lerp spider from current position toward `lastPlayerPos` over the animation duration. Gives “jump toward player” feel.

```javascript
// In 'attacking' state, each frame:
const t = followAttackTimer / attackDuration;
this.model.position.lerpVectors(attackStartPos, lastPlayerPos, t);
this.model.position.y = this.groundY + jumpArc(t);  // Optional: parabolic arc
```

`jumpArc(t)` could be `4 * Math.sin(Math.PI * t)` for a simple jump curve.

---

### Phase 6: GUI & HUD integration

**main.js:**
- Add `follow_attack` to `ALIEN_SPIDER_BEHAVIORS` (or equivalent) array for N-key cycling
- Add to Options menu dropdown for Alien Spider behavior

**gui-system.js:**
- Add display label for new mode: e.g. `"Follow & Jump Attack"` in `updateAlienSpiderBehaviorDisplay()`

**Behavior list becomes 8:**
1. idle_1  
2. idle_2  
3. walk_patrol  
4. run_patrol  
5. attack_1  
6. attack_2  
7. damage_reaction  
8. **follow_attack** ← NEW

---

## 📁 Files to Modify

| File | Changes |
|------|---------|
| `alien-spider.js` | Add `follow_attack` mode, `updateFollowAttack()`, `moveToward()`, `getPlayerPosition` in config, `followAttackState`, `followAttackTimer`, `lastPlayerPos`, `attackStartPos` |
| `main.js` | Pass `getPlayerPosition` when creating AlienSpiderBoss; add `follow_attack` to cycle list and options dropdown |
| `gui-system.js` | Add `follow_attack` label to Alien Spider HUD display |

---

## ⚠️ Edge Cases

1. **Player not in Level 6** – `getPlayerPosition` should return safe fallback (e.g. spawn position)
2. **Player respawns / warps** – Spider may snap; consider max speed or smoothing
3. **Spider stuck on geometry** – Follow uses XZ only; ensure `groundY` keeps spider on floor
4. **Animation not loaded** – Fallback to Idle_1 if Attack_2 missing

---

## 🧪 Testing Checklist

- [ ] Spider follows player when in `follow_attack` mode
- [ ] Spider triggers attack when within `attackRange` (e.g. 4 units)
- [ ] Attack_2 plays; spider transitions to idle after duration
- [ ] Spider stays idle (or re-follows if `reFollowAfterIdle`)
- [ ] N key cycles to `follow_attack`; HUD shows correct label
- [ ] Options menu includes `follow_attack`; switching updates HUD
- [ ] No errors when `getPlayerPosition` returns fallback

---

## 📝 Summary

| Item | Value |
|------|-------|
| **New mode** | `follow_attack` |
| **States** | following → attacking → idle_recovery → (optional) following |
| **Animations** | Walk (follow), Attack_2 (jump), Idle_1 (sleep) |
| **Config** | `getPlayerPosition` callback from main.js |
| **Params** | followSpeed, attackRange, attackDuration, idleAfterAttack, reFollowAfterIdle |

---

**Next step:** Implement Phase 1 (getPlayerPosition) and Phase 2 (behavior mode skeleton), then iterate on movement and attack feel.
