# 🎯 LEVEL 5 STEP 1: MONSTER HUNT — IMPLEMENTATION PLAN

**Document Created:** November 24, 2025  
**Riddle Step:** Step 1 (Step 0 = trigger plate activation)  
**Objective:** Hunt all monsters scattered across the huge Level 5 map within 10 minutes  
**Reward:** 2,500 DSPOINC (full reward for completing Step 1)

---

## 📋 OVERVIEW

### Step 1 Gameplay Flow
1. **Step 0 Complete:** Player stands on cheese-stone plate, weapons activate (slots 1 & 2)
2. **Step 1 Starts:** All monsters spawn across the entire Level 5 map
3. **Timer Starts:** 10-minute countdown begins
4. **Monster Counter:** Shows remaining monsters (e.g., "45/50 monsters remaining")
5. **Hunt Phase:** Player shoots monsters using weapons (same system as Level 4)
6. **⚡ Flying Monster Threat:** Flying monsters shoot slow cheese thunder bullets - if player gets hit → instant Game Over (crushed death screen)
7. **Completion:** When all monsters defeated → Award 2,500 DSPOINC
8. **Failure Conditions:**
   - Timer reaches 0 → Game Over (restart level or go to another level)
   - Hit by thunder bullet → Game Over (crushed death screen, restart level)

---

## 🗺️ MONSTER SPAWNING STRATEGY

### Map Dimensions
- **Level 5 Map Size:** ~1,500 x 960 units (scaled 5x from original)
- **Map Center:** (0, 0, 0)
- **Map Bounds:**
  - X: -750 to +750 (1,500 units wide)
  - Z: -480 to +480 (960 units deep)
  - Y: Ground level varies (use raycast to find)

### Spawn Distribution Method
**Grid-Based Spawning System:**
- Divide map into grid cells (e.g., 50x50 unit cells)
- Total cells: ~30 cells wide x ~20 cells deep = ~600 cells
- Spawn 1-2 monsters per cell randomly
- Ensures even distribution across entire map
- No overlapping spawns (minimum distance check)

### Spawn Algorithm
```javascript
// 1. Calculate grid cell size
const GRID_CELL_SIZE = 50; // units
const MAP_WIDTH = 1500;
const MAP_DEPTH = 960;
const GRID_COLS = Math.floor(MAP_WIDTH / GRID_CELL_SIZE); // ~30 columns
const GRID_ROWS = Math.floor(MAP_DEPTH / GRID_CELL_SIZE); // ~20 rows

// 2. For each cell, spawn 0-2 monsters randomly
for (let row = 0; row < GRID_ROWS; row++) {
  for (let col = 0; col < GRID_COLS; col++) {
    const cellX = -750 + (col * GRID_CELL_SIZE) + (GRID_CELL_SIZE / 2);
    const cellZ = -480 + (row * GRID_CELL_SIZE) + (GRID_CELL_SIZE / 2);
    
    // Random chance to spawn 1-2 monsters in this cell
    const monstersInCell = Math.random() < 0.7 ? (Math.random() < 0.5 ? 1 : 2) : 0;
    
    for (let i = 0; i < monstersInCell; i++) {
      // Random position within cell
      const offsetX = (Math.random() - 0.5) * GRID_CELL_SIZE * 0.8;
      const offsetZ = (Math.random() - 0.5) * GRID_CELL_SIZE * 0.8;
      const spawnX = cellX + offsetX;
      const spawnZ = cellZ + offsetZ;
      
      // Find ground level via raycast
      const spawnY = getGroundLevelAt(spawnX, spawnZ);
      
      // Spawn random monster type
      spawnLevel5Monster(randomMonsterPath, spawnX, spawnY, spawnZ);
    }
  }
}
```

### Monster Models Available (from Level 4)
All monsters from Level 4 will be used:

**Ground Monsters:**
- `/textures/3d models/Monster 1/Big/glTF/Bunny.gltf`
- `/textures/3d models/Monster 1/Big/glTF/Monkroose.gltf`
- `/textures/3d models/Monster 1/Big/glTF/Cactoro.gltf`
- `/textures/3d models/Monster 1/Big/glTF/[others...]`

**Flying Monsters:**
- `/textures/3d models/Monster 1/Flying/[flying monsters...]`

### Spawn Specifications
- **Total Monsters:** ~50-60 monsters (adjustable based on map coverage)
- **Spawn Height:**
  - Ground monsters: `groundLevel + 1.2` units
  - Flying monsters: `groundLevel + 3-12` units (random height)
- **Minimum Distance:** 10 units between spawn points (prevents overlapping)
- **Random Rotation:** Monsters face random directions (0-360°)
- **Random Size:** 100%-150% scale variation (visual variety)

---

## ⏱️ TIMER SYSTEM

### 10-Minute Countdown Timer
- **Duration:** 600 seconds (10 minutes)
- **Start:** When Step 1 activates (all monsters spawned)
- **Display:** Large on-screen timer (MM:SS format)
- **Warning:** Change color to red when < 1 minute remaining
- **End Behavior:** When timer reaches 0 → Game Over

### Timer Implementation
```javascript
const LEVEL5_STEP1_TIMER_DURATION = 600; // 10 minutes in seconds

level5RiddleState.step1Timer = LEVEL5_STEP1_TIMER_DURATION;
level5RiddleState.step1StartTime = Date.now();
level5RiddleState.step1TimerActive = true;
```

### Timer Display UI
- **Position:** Top-center of screen
- **Format:** "10:00" → "0:00"
- **Style:** 
  - Normal: White/Yellow
  - Warning (< 1 min): Red, pulsing effect
  - Font: Large, bold, readable

---

## 🔢 MONSTER COUNTER SYSTEM

### Counter Display
- **Format:** "Monsters: 45/50" or "45 monsters remaining"
- **Position:** Below timer (top-center)
- **Update:** Real-time as monsters are defeated
- **Style:** Medium size, clear visibility

### Counter Logic
```javascript
const totalMonsters = level5State.monsters.length;
const defeatedMonsters = level5State.monsters.filter(m => m.defeated).length;
const remainingMonsters = totalMonsters - defeatedMonsters;

// Display: "Monsters: 45/50" or "45 remaining"
updateMonsterCounter(remainingMonsters, totalMonsters);
```

---

## 🎯 SHOOTING & HIT DETECTION

### Reuse Level 4 System
- **Function:** `fireLevel4SingleShot()` already supports Level 5
- **Raycast:** From camera center (crosshair) against all monsters
- **Instant Hit:** Monsters defeated immediately on hit (raycast-based)
- **Visual Bullets:** Yellow (slot 1) or purple (slot 2) bullets fly toward hit point

### Monster Defeat Process
```javascript
function defeatLevel5Monster(monsterIndex) {
  const monster = level5State.monsters[monsterIndex];
  
  // Mark as defeated
  monster.defeated = true;
  level5RiddleState.monstersDefeated++;
  
  // Create explosion effect
  createMonsterExplosionEffect(monster.mesh.position, monster.sizeMultiplier);
  
  // Remove from scene
  level5State.group.remove(monster.mesh);
  
  // Remove from array
  level5State.monsters.splice(monsterIndex, 1);
  
  // Update counter
  updateMonsterCounter();
  
  // Check for completion
  if (level5State.monsters.length === 0) {
    completeLevel5Step1();
  }
}
```

---

## ✅ COMPLETION & REWARDS

### Step 1 Completion
- **Trigger:** All monsters defeated (`level5State.monsters.length === 0`)
- **Reward:** 2,500 DSPOINC
- **Achievement:** `CHEESE_TEMPLE_LEVEL5_STEP1_COMPLETE`
- **Next:** Prepare for Step 2 (or show completion screen)

### Reward System
```javascript
async function completeLevel5Step1() {
  // Stop timer
  level5RiddleState.step1TimerActive = false;
  
  // Award DSPOINC
  await awardLevel5Step1Reward(2500);
  
  // Show completion message
  showLevel5Step1CompleteToast();
  
  // Mark step as complete
  level5RiddleState.step1Complete = true;
  
  // Save trait
  await saveLevel5Trait('CHEESE_TEMPLE_LEVEL5_STEP1_COMPLETE');
}
```

---

## ❌ GAME OVER SYSTEM

### Timer Expiration
- **Trigger:** Timer reaches 0 seconds
- **Action:** Game Over screen
- **Options:**
  1. **Restart Level 5:** Start over from Step 0 (reset everything)
  2. **Go to Another Level:** Return to level selector menu

### Game Over Implementation
```javascript
function handleLevel5Step1TimeOut() {
  // Stop timer
  level5RiddleState.step1TimerActive = false;
  
  // Show game over screen
  showLevel5GameOverScreen({
    reason: 'timeout',
    monstersRemaining: level5State.monsters.filter(m => !m.defeated).length,
    timeElapsed: LEVEL5_STEP1_TIMER_DURATION
  });
  
  // Disable weapons
  deactivateLevel5Weapons();
  
  // Freeze gameplay (or allow navigation to menu)
}
```

### Game Over Screen
- **Message:** "Time's Up! You ran out of time."
- **Details:** "X monsters remaining"
- **Buttons:**
  - "Restart Level 5" → `restartLevel5()`
  - "Level Selector" → `warpToLevelSelector()`

---

## 🎮 UI ELEMENTS

### HUD Display (Top-Center)
```
┌─────────────────────┐
│   ⏱️ 09:45          │  ← Timer (white/yellow)
│   🐉 45/50 Monsters │  ← Counter
└─────────────────────┘
```

### Timer Styling
- **Normal:** `#fcd34d` (yellow)
- **Warning (< 1 min):** `#ef4444` (red) + pulsing animation
- **Font:** Large, bold, monospace

### Counter Styling
- **Color:** `#ffffff` (white)
- **Font:** Medium, clear

---

## 📊 STATE MANAGEMENT

### Level 5 Riddle State (Step 1)
```javascript
const level5RiddleState = {
  // Step 0 (existing)
  step0Complete: false,
  weaponsEnabled: false,
  triggerPlate: null,
  
  // Step 1 (new)
  step1Active: false,
  step1Complete: false,
  step1Timer: 600, // 10 minutes in seconds
  step1StartTime: null,
  step1TimerActive: false,
  monstersDefeated: 0,
  totalMonsters: 0
};
```

### Level 5 State (Monsters)
```javascript
const level5State = {
  // Existing
  built: false,
  group: THREE.Group,
  mapMesh: null,
  spawnPosition: Vector3,
  groundLevelY: number,
  borderWalls: { north, south, east, west },
  
  // Step 1 (new)
  monsters: [], // Array of monster objects (same structure as Level 4)
};
```

---

## 🔧 IMPLEMENTATION STEPS

### 1. Monster Spawning Function
- **Function:** `spawnLevel5Step1Monsters()`
- **Process:**
  1. Calculate grid cells
  2. Loop through cells
  3. Raycast for ground level at each spawn point
  4. Spawn random monster types
  5. Add to `level5State.monsters[]`
  6. Add to scene

### 2. Shooting Integration
- **Modify:** `fireLevel4SingleShot()` to check `level5State.monsters[]`
- **Add:** Level 5 monster hit detection
- **Create:** `defeatLevel5Monster()` function

### 3. Timer System
- **Function:** `updateLevel5Step1Timer(delta)`
- **Called:** Every frame in `updateLevel5()` when Step 1 active
- **UI:** Update timer display every second

### 4. Counter System
- **Function:** `updateLevel5MonsterCounter()`
- **Called:** After each monster defeat
- **UI:** Update counter display

### 5. Completion System
- **Function:** `completeLevel5Step1()`
- **Trigger:** All monsters defeated
- **Actions:** Award reward, save trait, mark complete

### 6. Game Over System
- **Function:** `handleLevel5Step1TimeOut()`
- **Trigger:** Timer reaches 0
- **Actions:** Show game over screen, disable weapons

---

## 📝 CODE STRUCTURE

### Main Update Loop
```javascript
function updateLevel5(delta) {
  if (!level5State.built || currentLevel !== LEVEL_IDS.LEVEL5) return;
  
  // Step 0
  updateLevel5Step0(delta);
  
  // Weapons (if enabled)
  if (level5RiddleState.weaponsEnabled) {
    updateLevel4Bullets(delta);
    updateLevel4TripleShot(delta);
    updateLevel4Heat(delta);
    updateLevel4HitIndicator(delta);
    
    if (isFirstPerson()) {
      updateLevel4WeaponAnimation(delta, isMoving);
    }
  }
  
  // Step 1 (new)
  if (level5RiddleState.step1Active) {
    updateLevel5Step1Timer(delta);
    updateLevel5MonsterCounter();
    
    // Check for completion
    if (level5State.monsters.length === 0 && !level5RiddleState.step1Complete) {
      completeLevel5Step1();
    }
    
    // Check for timeout
    if (level5RiddleState.step1Timer <= 0 && level5RiddleState.step1TimerActive) {
      handleLevel5Step1TimeOut();
    }
  }
}
```

### Shooting Function Modification
```javascript
function fireLevel4SingleShot(isPurple = false) {
  // ... existing code ...
  
  // Add Level 5 monster check
  const level5WeaponsActive = currentLevel === LEVEL_IDS.LEVEL5 && level5RiddleState.weaponsEnabled;
  const level5Step1Active = level5WeaponsActive && level5RiddleState.step1Active;
  
  if (level5Step1Active) {
    level5State.monsters.forEach((monster, index) => {
      if (!monster || !monster.mesh || !monster.mesh.visible || monster.defeated) return;
      
      const intersects = raycaster.intersectObject(monster.mesh, true);
      if (intersects.length > 0) {
        const distance = intersects[0].distance;
        if (distance < hitDistance) {
          hitDistance = distance;
          hitMonster = monster;
          hitLevel5Monster = true;
          hitMonsterIndex = index;
          targetPos.copy(intersects[0].point);
        }
      }
    });
  }
  
  // ... create bullet ...
  
  // Handle Level 5 hit
  if (hitLevel5Monster && hitMonsterIndex >= 0) {
    defeatLevel5Monster(hitMonsterIndex);
    showLevel4HitIndicator();
  }
}
```

---

## 🎯 SUMMARY

### Step 1 Flow:
1. **Step 0 Complete** → Weapons enabled
2. **Step 1 Starts** → Spawn ~50-60 monsters across entire map
3. **Timer Starts** → 10-minute countdown begins
4. **Hunt Phase** → Player shoots monsters (Level 4 system)
5. **⚡ Flying Monster Threat** → Flying monsters shoot slow cheese thunder bullets at player
6. **Completion** → All monsters defeated → 2,500 DSPOINC reward
7. **Failure Conditions:**
   - Timer expires → Game Over (restart or level select)
   - Hit by thunder bullet → Game Over (crushed death screen, restart)

### Key Features:
- ✅ Grid-based spawn distribution (even coverage)
- ✅ 10-minute timer with visual countdown
- ✅ Monster counter (real-time updates)
- ✅ Reuses Level 4 shooting system (instant hit detection)
- ✅ ⚡ **Flying monsters shoot cheese thunder bullets** (slow but deadly)
- ✅ **Thunder bullet hit = instant Game Over** (crushed death screen)
- ✅ 2,500 DSPOINC reward on completion
- ✅ Game Over on timeout OR thunder bullet hit with restart option

---

---

## ⚡ FLYING MONSTER SHOOTING SYSTEM

### Overview
Flying monsters in Level 5 Step 1 can shoot **cheese thunder bullets** at the player. These bullets are slow but dangerous - if the player gets hit, it's instant Game Over with the crushed death screen.

### Cheese Thunder Bullets
- **Visual:** Yellow/purple glowing spheres with lightning/energy effect
- **Speed:** Slow (slower than Level 4 projectiles) - gives player time to dodge
- **Size:** Medium (0.4-0.5 units radius) - visible but dangerous
- **Color:** Yellow cheese color (`#fcd34d`) with purple lightning accents
- **Effect:** Glowing, pulsing, with emissive intensity

### Flying Monster Behavior
- **Shooting Range:** All flying monsters can shoot (not just final wave)
- **Cooldown:** 4-5 seconds between shots (slower than Level 4)
- **Targeting:** Always aims at player's current position
- **Bullet Lifetime:** 8-10 seconds (longer range due to slow speed)

### Player Collision
- **Hit Detection:** Distance check between bullet and player center
- **Hit Radius:** Player collision radius (~0.5 units)
- **On Hit:** Instant Game Over (crushed death screen)
- **Game Over Options:** Restart Level 5 or go to Level Selector

### Implementation Details

#### Constants
```javascript
const LEVEL5_THUNDER_BULLET_SPEED = 6; // Slow speed (half of Level 4)
const LEVEL5_THUNDER_BULLET_COOLDOWN = 4500; // 4.5 seconds between shots
const LEVEL5_THUNDER_BULLET_LIFETIME = 10.0; // 10 seconds lifetime
const LEVEL5_THUNDER_BULLET_SIZE = 0.4; // Medium size
```

#### Thunder Bullet Creation
```javascript
function createLevel5ThunderBullet(monster, playerPosition) {
  const monsterPos = monster.mesh.position.clone();
  const direction = new THREE.Vector3().subVectors(playerPosition, monsterPos);
  const distance = direction.length();
  if (distance < 0.1) return; // Too close
  direction.normalize();
  
  // Create cheese thunder bullet (yellow with purple lightning)
  const bulletGeometry = new THREE.SphereGeometry(LEVEL5_THUNDER_BULLET_SIZE, 12, 12);
  const bulletMaterial = new THREE.MeshStandardMaterial({
    color: 0xfcd34d, // Yellow cheese color
    emissive: 0x7c3aed, // Purple lightning glow
    emissiveIntensity: 1.5, // Strong glow
    metalness: 0.3,
    roughness: 0.2
  });
  
  const bullet = new THREE.Mesh(bulletGeometry, bulletMaterial);
  bullet.position.copy(monsterPos);
  
  // Add pulsing animation (scale effect)
  bullet.userData.pulsePhase = 0;
  bullet.userData.pulseSpeed = 5.0; // Fast pulse
  
  // Calculate velocity (slow speed)
  const velocity = direction.clone().multiplyScalar(LEVEL5_THUNDER_BULLET_SPEED);
  
  level5State.group.add(bullet);
  level5State.thunderBullets.push({
    mesh: bullet,
    velocity: velocity,
    lifetime: LEVEL5_THUNDER_BULLET_LIFETIME,
    age: 0
  });
}
```

#### Thunder Bullet Update
```javascript
function updateLevel5ThunderBullets(delta) {
  level5State.thunderBullets = level5State.thunderBullets.filter(bullet => {
    if (!bullet.mesh || !bullet.mesh.parent) return false;
    
    bullet.age += delta;
    if (bullet.age >= bullet.lifetime) {
      level5State.group.remove(bullet.mesh);
      bullet.mesh.geometry.dispose();
      bullet.mesh.material.dispose();
      return false;
    }
    
    // Update position (slow movement)
    bullet.mesh.position.addScaledVector(bullet.velocity, delta);
    
    // Pulsing animation (glow effect)
    bullet.mesh.userData.pulsePhase += delta * bullet.mesh.userData.pulseSpeed;
    const pulseScale = 1.0 + Math.sin(bullet.mesh.userData.pulsePhase) * 0.2;
    bullet.mesh.scale.set(pulseScale, pulseScale, pulseScale);
    
    // Rotate for visual effect
    bullet.mesh.rotation.x += delta * 3;
    bullet.mesh.rotation.y += delta * 2;
    
    // Check collision with player
    const playerPos = new THREE.Vector3().lerpVectors(playerCollider.start, playerCollider.end, 0.5);
    const distance = bullet.mesh.position.distanceTo(playerPos);
    const PLAYER_RADIUS = 0.5;
    
    if (distance < PLAYER_RADIUS + LEVEL5_THUNDER_BULLET_SIZE) {
      // Player hit! Game over!
      console.error("💥 [LEVEL 5] Player hit by thunder bullet! Game over!");
      triggerLevel5ThunderGameOver();
      level5State.group.remove(bullet.mesh);
      bullet.mesh.geometry.dispose();
      bullet.mesh.material.dispose();
      return false;
    }
    
    return true;
  });
}
```

#### Flying Monster Shooting Logic
```javascript
function updateLevel5FlyingMonsters(delta) {
  if (!level5RiddleState.step1Active) return;
  
  const playerPosition = new THREE.Vector3().lerpVectors(playerCollider.start, playerCollider.end, 0.5);
  const currentTime = performance.now();
  
  level5State.monsters.forEach((monster) => {
    if (!monster || !monster.mesh || monster.defeated) return;
    
    // Check if this is a flying monster
    const isFlying = monster.path && monster.path.includes('/Flying/');
    if (!isFlying) return; // Only flying monsters shoot
    
    // Check cooldown
    if (currentTime - monster.lastThunderShotTime > LEVEL5_THUNDER_BULLET_COOLDOWN) {
      // Shoot thunder bullet at player
      createLevel5ThunderBullet(monster, playerPosition);
      monster.lastThunderShotTime = currentTime;
    }
  });
}
```

#### Game Over on Hit
```javascript
function triggerLevel5ThunderGameOver() {
  // Stop timer
  level5RiddleState.step1TimerActive = false;
  
  // Disable weapons
  deactivateLevel5Weapons();
  
  // Show crushed game over screen (same as Level 3)
  showLevel3GameOverScreen();
  
  // Custom message for thunder bullet death
  // (Game over screen already shows "CRUSHED!" which is perfect)
  
  // Freeze gameplay
  isGamePaused = true;
}
```

### State Management
```javascript
// Add to level5State
const level5State = {
  // ... existing properties ...
  thunderBullets: [], // Array of active thunder bullets
};

// Add to monster object
const monster = {
  // ... existing properties ...
  lastThunderShotTime: 0, // Last time this monster shot
};
```

### Update Loop Integration
```javascript
function updateLevel5(delta) {
  // ... existing code ...
  
  if (level5RiddleState.step1Active) {
    updateLevel5Step1Timer(delta);
    updateLevel5MonsterCounter();
    
    // Update flying monster shooting
    updateLevel5FlyingMonsters(delta);
    
    // Update thunder bullets
    updateLevel5ThunderBullets(delta);
    
    // ... completion/timeout checks ...
  }
}
```

### Visual Design
- **Bullet:** Yellow sphere with purple emissive glow
- **Pulsing:** Scale animation (1.0 to 1.2, back and forth)
- **Rotation:** Slow rotation for visual interest
- **Trail Effect:** Optional - could add particle trail later
- **Size:** 0.4 units (visible but not too large)

### Gameplay Balance
- **Slow Speed:** Gives player time to see and dodge
- **Visible:** Large enough to spot from distance
- **Dangerous:** One hit = game over (high stakes)
- **Cooldown:** 4.5 seconds prevents spam
- **Flying Only:** Only flying monsters shoot (ground monsters don't)

---

**Ready to implement! Flying monsters add danger and challenge to the huge map hunt!**

---

**Last Updated:** November 24, 2025  
**Status:** ✅ **PLAN COMPLETE** — Includes flying monster shooting system  
**Next Phase:** Implementation of Step 1 with all features

