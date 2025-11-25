# 🎯 Level 4 Monster Shooting Mechanics - Technical Breakdown

**Document Created:** November 24, 2025  
**Purpose:** Understand Level 4 shooting system for adapting to Level 5 monster spawns across the entire map

---

## 📋 OVERVIEW

Level 4 uses an **instant hit detection system** with **visual bullet projectiles**. When you shoot, the game:
1. **Instantly detects hits** via raycasting (before bullet animation)
2. **Creates visible bullets** that fly toward the hit point (for visual feedback)
3. **Defeats monsters immediately** on hit (no waiting for bullet to travel)

This system works perfectly for Level 5 because it's **distance-agnostic** - it works regardless of how far monsters are spread across the map.

---

## 🔫 SHOOTING SYSTEM ARCHITECTURE

### **1. Shooting Trigger**
- **Function:** `handleLevel4Shooting()` (line ~2599)
- **Called From:** Mouse click handler when `canUseLevel4ShooterSystem()` returns true
- **Conditions:** 
  - Level 4 Step 2 active, OR
  - Level 5 weapons enabled (already supported!)

### **2. Main Shooting Function**
- **Function:** `fireLevel4SingleShot(isPurple = false)` (line ~2684)
- **Process:**
  1. Creates raycaster from camera center (crosshair position)
  2. Calculates bullet start position (gun barrel position relative to camera)
  3. **Instantly raycasts against all monsters** to find closest hit
  4. Creates visible bullet projectile (yellow or purple)
  5. Instantly defeats monster if hit

---

## 🎯 INSTANT HIT DETECTION (Key Mechanism)

### **Raycast-Based Detection**
```javascript
// 1. Create raycaster from camera center
const raycaster = new THREE.Raycaster();
raycaster.setFromCamera(new THREE.Vector2(0, 0), camera); // Center = crosshair
raycaster.far = LEVEL4_SHOOT_RANGE; // Max range

// 2. Loop through all monsters and raycast against each
level4State.monsters.forEach((monster, index) => {
  if (!monster || !monster.mesh || !monster.mesh.visible || monster.defeated) return;
  
  // Raycast against monster mesh (recursive=true checks all nested meshes)
  const intersects = raycaster.intersectObject(monster.mesh, true);
  
  if (intersects.length > 0) {
    const distance = intersects[0].distance;
    if (distance < hitDistance) {
      // This is the closest hit - store it
      hitMonster = monster;
      targetPos.copy(intersects[0].point); // Actual hit point on mesh
    }
  }
});
```

### **Why This Works for Level 5:**
- ✅ **Distance-agnostic:** Raycast checks all monsters regardless of distance
- ✅ **Accurate:** Uses actual mesh geometry (not bounding boxes)
- ✅ **Fast:** Instant detection (no waiting for bullet travel)
- ✅ **Visual feedback:** Bullets still fly toward hit point for player feedback

---

## 💥 MONSTER DEFEAT PROCESS

### **Function:** `defeatLevel4Monster(monsterIndex)` (line ~11189)

**Process:**
1. **Validates monster exists** and isn't already defeated
2. **Checks health** (final wave monsters have 2 health, others have 1)
3. **If not defeated:** Reduces health, flashes red, returns
4. **If defeated:**
   - Marks monster as defeated
   - Increments defeat counter
   - Creates explosion effect
   - Removes monster from scene
   - Removes from `level4State.monsters` array
   - Awards DSPOINC reward

### **Monster Object Structure**
```javascript
const monster = {
  mesh: THREE.Group,           // Monster 3D model
  mixer: AnimationMixer,       // Animation controller
  path: string,                // Model file path
  position: Vector3,           // Spawn position
  health: number,              // 1 or 2 (final wave)
  defeated: boolean,           // Has been defeated
  sizeMultiplier: number,      // Scale factor (120%, 150%, etc.)
  baseY: number,               // Ground level for positioning
  isFinalWave: boolean,        // Final wave flag
  lastProjectileTime: number   // For monster shooting back
};
```

---

## 🔍 VISUAL BULLET SYSTEM

### **Bullet Creation**
- **Yellow Bullets (Slot 1):** `createLevel4CheeseBullet()` (line ~2347)
- **Purple Bullets (Slot 2):** `createLevel4SF13Bullet()` (line ~2300)

### **Bullet Properties**
- **Size:** `LEVEL4_BULLET_SIZE` (0.15 units)
- **Speed:** `LEVEL4_BULLET_SPEED` (units per second)
- **Lifetime:** `LEVEL4_BULLET_LIFETIME` (auto-remove after X seconds)
- **Direction:** Flies toward `targetPos` (the hit point on monster)

### **Important Note:**
Bullets are **purely visual** - they don't do the damage. The raycast does the damage instantly. Bullets are created after the hit is detected to show where you hit.

---

## 📊 MONSTER ARRAY MANAGEMENT

### **Storage**
- **Array:** `level4State.monsters[]` - stores all active monsters
- **Structure:** Each monster is an object with mesh, health, position, etc.

### **Monster Spawning**
- **Function:** `spawnLevel4Monster()` (line ~10300)
- **Process:**
  1. Loads GLTF model
  2. Clones mesh using `SkeletonUtils.clone()` (for skinned meshes)
  3. Positions monster at spawn location
  4. Scales based on `sizeMultiplier`
  5. Adds to `level4State.group`
  6. Pushes to `level4State.monsters[]` array

### **Monster Removal**
- **On Defeat:** Removed from array via `defeatLevel4Monster()`
- **On Wave Complete:** All monsters cleared from array
- **On Level Reset:** All monsters removed and array cleared

---

## 🎮 ADAPTATION FOR LEVEL 5

### **What Already Works:**
1. ✅ **Shooting function** already supports Level 5 (`level5WeaponsActive` check)
2. ✅ **Bullet system** already works in Level 5 (fixed in previous session)
3. ✅ **Raycast system** is distance-agnostic (works for huge map)

### **What You Need to Add:**

#### **1. Monster Array for Level 5**
```javascript
// In level5State or level5RiddleState
monsters: []  // Array to store Level 5 monsters
```

#### **2. Modify Shooting Function**
Add Level 5 monster detection to `fireLevel4SingleShot()`:
```javascript
// After Level 4 Step 2 check, add:
const level5MonstersActive = currentLevel === LEVEL_IDS.LEVEL5 && level5RiddleState.weaponsEnabled;

if (level5MonstersActive) {
  // Same raycast loop but check level5State.monsters[]
  level5State.monsters.forEach((monster, index) => {
    if (!monster || !monster.mesh || !monster.mesh.visible || monster.defeated) return;
    const intersects = raycaster.intersectObject(monster.mesh, true);
    if (intersects.length > 0) {
      const distance = intersects[0].distance;
      if (distance < hitDistance) {
        hitMonster = monster;
        hitLevel5Monster = true; // Flag for Level 5
        targetPos.copy(intersects[0].point);
      }
    }
  });
}
```

#### **3. Monster Defeat Function for Level 5**
Create `defeatLevel5Monster(monsterIndex)` similar to Level 4:
- Remove from `level5State.monsters[]`
- Remove from scene
- Create explosion effect
- Award rewards (if applicable)

#### **4. Monster Spawning for Level 5**
Create `spawnLevel5Monster()` function:
- Load monster GLTF models
- Position randomly across the huge map
- Add to `level5State.group` (or create separate group)
- Push to `level5State.monsters[]` array

---

## 🗺️ MAP COVERAGE STRATEGY

### **For Level 5's Huge Map:**

1. **Grid-Based Spawning:**
   - Divide map into grid cells (e.g., 50x50 unit cells)
   - Spawn monsters randomly within each cell
   - Ensures even distribution

2. **Distance Checks:**
   - Ensure monsters don't spawn too close together
   - Minimum distance between spawns (e.g., 10 units)

3. **Ground Level Detection:**
   - Use raycast to find ground level at each spawn point
   - Position monsters at `groundLevel + baseY` (same as Level 4)

4. **Monster Variety:**
   - Spawn different monster types randomly
   - Different sizes for visual variety
   - Different positions (some on buildings, some on ground)

---

## 🎯 KEY TAKEAWAYS

1. **Instant Hit Detection:** Raycast happens instantly - no bullet travel time matters
2. **Distance-Agnostic:** Works perfectly for huge maps like Level 5
3. **Visual Bullets:** Bullets are just for show - they don't do the damage
4. **Monster Array:** All active monsters stored in array for easy iteration
5. **Mesh-Based:** Raycast uses actual mesh geometry (accurate hit detection)

---

## 📝 CODE PATTERNS

### **Monster Spawn Pattern:**
```javascript
// 1. Load model
const gltf = await loadGLTF(monsterPath);

// 2. Clone for skinned meshes
const monsterMesh = SkeletonUtils.clone(gltf.scene);

// 3. Position and scale
monsterMesh.position.set(x, y, z);
monsterMesh.scale.set(size, size, size);

// 4. Add to scene
level5State.group.add(monsterMesh);

// 5. Store in array
level5State.monsters.push({
  mesh: monsterMesh,
  mixer: new AnimationMixer(monsterMesh),
  path: monsterPath,
  health: 1,
  defeated: false,
  // ... other properties
});
```

### **Shooting Pattern:**
```javascript
// 1. Create raycaster
const raycaster = new THREE.Raycaster();
raycaster.setFromCamera(new THREE.Vector2(0, 0), camera);

// 2. Check all monsters
level5State.monsters.forEach((monster) => {
  const intersects = raycaster.intersectObject(monster.mesh, true);
  if (intersects.length > 0) {
    // Hit detected - defeat monster
    defeatLevel5Monster(monsterIndex);
  }
});

// 3. Create visual bullet
createLevel4CheeseBullet(startPos, direction, targetPos);
```

---

**This system is perfect for Level 5's huge map because raycasting works at any distance!**

