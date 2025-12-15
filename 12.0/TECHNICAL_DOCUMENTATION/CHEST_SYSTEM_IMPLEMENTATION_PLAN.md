# 🎁 CHEST SYSTEM IMPLEMENTATION PLAN

**Date:** December 14, 2025  
**Status:** 📋 **PLANNING PHASE**  
**Purpose:** Game-wide treasure chest system for easy integration across all levels  

---

## 🎯 **SYSTEM OVERVIEW**

### **Goal:**
Create a modular, reusable chest system that allows easy addition of treasure chests to any level with minimal code. Players can open chests to receive DSPOINC rewards.

### **Key Requirements:**
- ✅ **Easy Integration:** Just specify position, amount, and chest type
- ✅ **Multiple Chest Types:** Support chest1 and chest2 models
- ✅ **DSPOINC Rewards:** Integrate with existing reward system
- ✅ **Opening Animation:** Visual feedback when chest opens
- ✅ **One-Time Opening:** Prevent duplicate rewards
- ✅ **Per-Level Management:** Track chests per level
- ✅ **Standard Pattern:** Follow existing 3D model rendering rules

---

## 📋 **ARCHITECTURE PLAN**

### **1. Module Structure: `chest-system.js`**

**Location:** `three.js/chest-system.js`

**Purpose:** Centralized chest management system

**Key Features:**
- Chest class for individual chest management
- Chest registry per level
- Interaction detection (distance-based)
- Opening state management
- DSPOINC reward integration
- Animation handling

---

## 🏗️ **SYSTEM DESIGN**

### **Chest Class Structure:**

```javascript
class Chest {
  constructor(config) {
    this.id = config.id;                    // Unique chest ID
    this.type = config.type;                // 'chest1' or 'chest2'
    this.position = config.position;        // THREE.Vector3 position
    this.dspoincAmount = config.dspoincAmount; // Base DSPOINC reward
    this.levelId = config.levelId;          // Level identifier
    this.opened = false;                     // Opening state
    this.mesh = null;                        // Three.js mesh
    this.openMesh = null;                    // Opened chest mesh (optional)
    this.interactionRadius = 2.0;            // Distance to interact
    this.openingAnimation = null;            // Animation mixer (if animated)
  }
  
  // Methods:
  - load() - Load chest model
  - checkPlayerInteraction() - Detect if player is near
  - open() - Open chest and award DSPOINC
  - update() - Update animation/interaction
  - dispose() - Cleanup resources
}
```

### **ChestSystem Class:**

```javascript
class ChestSystem {
  constructor() {
    this.chests = new Map();  // Map<levelId, Map<chestId, Chest>>
    this.activeLevel = null;
  }
  
  // Methods:
  - addChest(levelId, config) - Add chest to level
  - removeChest(levelId, chestId) - Remove chest
  - getChestsForLevel(levelId) - Get all chests for level
  - update(levelId, playerPosition, delta) - Update all chests
  - clearLevel(levelId) - Remove all chests for level
}
```

---

## 🎮 **INTERACTION SYSTEM**

### **Interaction Detection:**

**Method 1: Distance-Based (Recommended)**
- Check horizontal distance from player to chest
- Show "Press [E] to Open" UI when within range
- Open chest when player presses E key

**Method 2: Raycaster-Based (Alternative)**
- Use raycaster to detect chest in crosshair
- Show interaction prompt when looking at chest
- Open on click/interaction

**Recommended:** Distance-based (simpler, more reliable)

### **Interaction Flow:**
1. Player approaches chest (within `interactionRadius`)
2. UI shows "Press [E] to Open Chest"
3. Player presses E key
4. Chest opens (animation/state change)
5. DSPOINC reward awarded via API
6. Chest remains open (cannot be opened again)

---

## 💰 **DSPOINC REWARD INTEGRATION**

### **API Endpoint:**
- **Endpoint:** `/api/dev/riddle-reward.php`
- **Same as:** Riddle completion rewards

### **Payload Structure:**
```javascript
{
  discord_id: string,        // User's Discord ID
  discord_name: string|null, // User's Discord username
  riddle_id: string,          // Format: "CHEST_[LEVEL_ID]_[CHEST_ID]"
  level_id: string,           // Level identifier (e.g., "CHEESE_TEMPLE_LEVEL1")
  base_reward: number,        // DSPOINC amount
  session_id: string|null     // Session identifier
}
```

### **Example Riddle IDs:**
- `"CHEST_LEVEL1_CHEST_001"`
- `"CHEST_LEVEL2_CHEST_001"`
- `"CHEST_LEVEL1_CHEST_002"`

### **Integration Pattern:**
```javascript
async function openChest(chest) {
  if (chest.opened) return; // Already opened
  
  // Award DSPOINC via API
  const response = await fetch(RIDDLE_REWARD_ENDPOINT, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      discord_id: resolvedDiscordId,
      discord_name: resolvedDiscordName,
      riddle_id: `CHEST_${chest.levelId}_${chest.id}`,
      level_id: chest.levelId,
      base_reward: chest.dspoincAmount,
      session_id: cheeseSessionId
    })
  });
  
  // Handle response and mark as opened
  chest.opened = true;
  chest.playOpenAnimation();
}
```

---

## 📦 **CHEST MODEL PATHS**

### **Chest 1:**
- **Path:** `/textures/3d models/chest1/chest1.glb`
- **Type:** `'chest1'`

### **Chest 2:**
- **Path:** `/textures/3d models/chest2/Chest2.glb`
- **Type:** `'chest2'`

### **Model Loading:**
- Use existing `loadModel()` function
- Process materials with `processWeaponMaterial()`
- Set proper scale, position, rotation
- Enable shadows (castShadow, receiveShadow)

---

## 🎨 **OPENING ANIMATION**

### **Option 1: Model Swap (Simple)**
- Load closed chest model initially
- When opened, swap to open chest model (if available)
- Or rotate lid mesh if model has separate lid

### **Option 2: Animation (If Model Has Animation)**
- Check if GLB has opening animation
- Play animation when opened
- Use AnimationMixer if available

### **Option 3: Manual Rotation (Fallback)**
- If model has separate lid mesh, rotate it
- Rotate lid around hinge axis (usually X or Y)
- Smooth rotation animation

**Recommended:** Start with Option 1 (model swap), add animation support later if needed

---

## 🔧 **EASY INTEGRATION API**

### **Simple API for Adding Chests:**

```javascript
// In buildLevel() or level-specific function
function createLevel1Chests(spawnData, blockSize) {
  // Chest 1: Near spawn area
  chestSystem.addChest(LEVEL_IDS.LEVEL1, {
    id: 'chest_001',
    type: 'chest1',
    position: new THREE.Vector3(55, 1.0, 20), // x, y, z
    dspoincAmount: 100,  // Base DSPOINC reward
    levelId: 'CHEESE_TEMPLE_LEVEL1'
  });
  
  // Chest 2: Center platform
  chestSystem.addChest(LEVEL_IDS.LEVEL1, {
    id: 'chest_002',
    type: 'chest2',
    position: new THREE.Vector3(60, 1.0, 60), // x, y, z
    dspoincAmount: 250,  // Base DSPOINC reward
    levelId: 'CHEESE_TEMPLE_LEVEL1'
  });
}
```

### **Even Simpler Helper Function:**

```javascript
// Helper function for spawn-relative positioning
function addChestToLevel(levelId, chestId, type, spawnOffset, dspoincAmount) {
  const spawnX = spawnData ? spawnData.x * blockSize + blockSize / 2 : 60;
  const spawnZ = spawnData ? spawnData.z * blockSize + blockSize / 2 : 15;
  const floorTopY = 0 * blockSize + blockSize; // 1.0
  
  chestSystem.addChest(levelId, {
    id: chestId,
    type: type, // 'chest1' or 'chest2'
    position: new THREE.Vector3(
      spawnX + spawnOffset.x,
      floorTopY + spawnOffset.y,
      spawnZ + spawnOffset.z
    ),
    dspoincAmount: dspoincAmount,
    levelId: levelId
  });
}

// Usage:
addChestToLevel(LEVEL_IDS.LEVEL1, 'chest_001', 'chest1', { x: -5, y: 0, z: 5 }, 100);
addChestToLevel(LEVEL_IDS.LEVEL1, 'chest_002', 'chest2', { x: 0, y: 0, z: 45 }, 250);
```

---

## 📊 **STATE MANAGEMENT**

### **Level State Integration:**

```javascript
// In level state (e.g., level1State)
const level1State = {
  built: true,
  group: null,
  // ... other properties ...
  chests: null  // Managed by ChestSystem, but can track here if needed
};
```

### **Chest State Tracking:**
- **Per-Level:** ChestSystem manages chests per level
- **Per-Chest:** Each chest tracks its own state (opened/closed)
- **Persistence:** Chest states could be saved to localStorage (optional)

---

## 🎯 **IMPLEMENTATION PHASES**

### **Phase 1: Core System (Foundation)**
1. Create `chest-system.js` module
2. Implement `Chest` class
3. Implement `ChestSystem` class
4. Basic model loading (chest1 and chest2)
5. Position and rendering

### **Phase 2: Interaction System**
1. Distance-based interaction detection
2. UI prompt ("Press [E] to Open")
3. E key detection
4. Opening state management

### **Phase 3: DSPOINC Integration**
1. API integration with `/api/dev/riddle-reward.php`
2. Reward awarding
3. Duplicate prevention
4. Error handling

### **Phase 4: Animation & Polish**
1. Opening animation (model swap or rotation)
2. Visual feedback (particles, sound effects)
3. UI improvements (reward notification)
4. Performance optimization

### **Phase 5: Documentation & Rules**
1. Add to 3D Model Rendering Rule
2. Create usage examples
3. Document API patterns
4. Add to technical documentation

---

## 🔑 **KEY CONFIGURATION VALUES**

### **Chest Defaults:**
- **Interaction Radius:** 2.0 units (player must be within 2 units)
- **Base Scale:** 1.0 (adjust per chest type if needed)
- **Ground Level:** Same as trees (1.0)
- **Collision:** Optional (can reuse tree collision pattern)

### **Chest Types:**
- **chest1:** `/textures/3d models/chest1/chest1.glb`
- **chest2:** `/textures/3d models/chest2/Chest2.glb`

### **DSPOINC Amounts:**
- **Small Chest:** 50-150 DSPOINC
- **Medium Chest:** 150-300 DSPOINC
- **Large Chest:** 300-500 DSPOINC
- **Rare Chest:** 500-1000 DSPOINC

---

## 🎮 **USAGE EXAMPLES**

### **Example 1: Simple Chest Addition**

```javascript
// In buildLevel() for Level 1
if (mapData.spawn) {
  // Add chest near spawn
  chestSystem.addChest(LEVEL_IDS.LEVEL1, {
    id: 'spawn_chest',
    type: 'chest1',
    position: new THREE.Vector3(55, 1.0, 20),
    dspoincAmount: 100,
    levelId: 'CHEESE_TEMPLE_LEVEL1'
  });
}
```

### **Example 2: Multiple Chests**

```javascript
// In buildLevel() for Level 1
if (mapData.spawn) {
  const spawnX = spawnData.x * blockSize + blockSize / 2;
  const spawnZ = spawnData.z * blockSize + blockSize / 2;
  const floorTopY = 0 * blockSize + blockSize;
  
  // Chest 1: Near spawn
  chestSystem.addChest(LEVEL_IDS.LEVEL1, {
    id: 'chest_001',
    type: 'chest1',
    position: new THREE.Vector3(spawnX - 5, floorTopY, spawnZ + 10),
    dspoincAmount: 100,
    levelId: 'CHEESE_TEMPLE_LEVEL1'
  });
  
  // Chest 2: Center platform
  chestSystem.addChest(LEVEL_IDS.LEVEL1, {
    id: 'chest_002',
    type: 'chest2',
    position: new THREE.Vector3(60, floorTopY, 60),
    dspoincAmount: 250,
    levelId: 'CHEESE_TEMPLE_LEVEL1'
  });
  
  // Chest 3: Hidden area
  chestSystem.addChest(LEVEL_IDS.LEVEL1, {
    id: 'chest_003',
    type: 'chest1',
    position: new THREE.Vector3(80, floorTopY, 40),
    dspoincAmount: 500,
    levelId: 'CHEESE_TEMPLE_LEVEL1'
  });
}
```

### **Example 3: Helper Function**

```javascript
// Helper function for easy chest placement
function addLevel1Chest(id, type, offsetX, offsetZ, dspoincAmount) {
  const spawnX = mapData.spawn ? mapData.spawn.x * blockSize + blockSize / 2 : 60;
  const spawnZ = mapData.spawn ? mapData.spawn.z * blockSize + blockSize / 2 : 15;
  const floorTopY = 0 * blockSize + blockSize;
  
  chestSystem.addChest(LEVEL_IDS.LEVEL1, {
    id: id,
    type: type,
    position: new THREE.Vector3(spawnX + offsetX, floorTopY, spawnZ + offsetZ),
    dspoincAmount: dspoincAmount,
    levelId: 'CHEESE_TEMPLE_LEVEL1'
  });
}

// Usage:
addLevel1Chest('chest_001', 'chest1', -5, 10, 100);
addLevel1Chest('chest_002', 'chest2', 0, 45, 250);
```

---

## 🔄 **INTEGRATION WITH MAIN GAME LOOP**

### **In `main.js`:**

```javascript
// Import chest system
import { ChestSystem } from "./chest-system.js";

// Initialize chest system
const chestSystem = new ChestSystem();

// In animate() loop:
function animate() {
  // ... existing code ...
  
  // Update chests for current level
  if (currentLevel && !isGamePaused) {
    const playerPos = new THREE.Vector3().lerpVectors(
      playerCollider.start, 
      playerCollider.end, 
      0.5
    );
    chestSystem.update(currentLevel, playerPos, delta);
  }
  
  // ... rest of code ...
}

// In buildLevel():
function buildLevel(mapData) {
  // ... existing code ...
  
  // Add chests to level
  if (mapData.spawn) {
    createLevel1Chests(mapData.spawn, blockSize);
  }
  
  // ... rest of code ...
}

// In cleanup functions:
function cleanupAllLevels() {
  // ... existing cleanup ...
  
  // Clear chests for all levels
  chestSystem.clearLevel(LEVEL_IDS.LEVEL1);
  chestSystem.clearLevel(LEVEL_IDS.LEVEL2);
  // ... etc ...
}
```

---

## 🎨 **UI INTEGRATION**

### **Interaction Prompt:**
- Show "Press [E] to Open Chest" when player is near
- Position: Center of screen or above chest
- Style: Similar to other game UI elements
- Hide when not near chest or chest already opened

### **Reward Notification:**
- Show DSPOINC amount when chest opens
- Format: "+100 DSPOINC" or "+250 DSPOINC (VIP 2.0x = 500)"
- Animation: Fade in/out or slide up
- Duration: 2-3 seconds

---

## 🔒 **DUPLICATE PREVENTION**

### **API-Level:**
- Riddle reward API already prevents duplicates via `UNIQUE(discord_id, riddle_id)`
- Each chest has unique `riddle_id`: `"CHEST_[LEVEL_ID]_[CHEST_ID]"`

### **Client-Level:**
- Track `chest.opened` state
- Don't allow interaction if already opened
- Visual indication (chest remains open)

---

## 📝 **FILE STRUCTURE**

### **New Files:**
- `three.js/chest-system.js` - Main chest system module

### **Modified Files:**
- `three.js/main.js` - Import and integrate chest system
- `12.0/RULES/18_3D_MODEL_RENDERING_RULE.md` - Add chest section
- `12.0/TECHNICAL_DOCUMENTATION/HYTOPIA_THREE_TECH_DOCUMENTATION.md` - Add chest system docs

---

## 🧪 **TESTING CHECKLIST**

### **Phase 1 Testing:**
- [x] Chest models load correctly ✅
- [x] Chests render at correct positions ✅
- [x] Materials process correctly ✅
- [x] Shadows work properly ✅
- [x] Y position calculation fixed (using `min.y`) ✅
- [x] Verification system working ✅
- [x] Both chests visible after warp/respawn ✅

**Status:** ✅ **PHASE 1 COMPLETE - December 15, 2025**

### **Phase 2 Testing:**
- [ ] Interaction detection works
- [ ] UI prompt shows/hides correctly
- [ ] E key opens chest
- [ ] Opening state persists

### **Phase 3 Testing:**
- [ ] DSPOINC rewards awarded correctly
- [ ] Role multipliers applied
- [ ] Duplicate prevention works
- [ ] API errors handled gracefully

### **Phase 4 Testing:**
- [ ] Opening animation plays
- [ ] Visual feedback works
- [ ] Performance is acceptable
- [ ] Multiple chests work simultaneously

---

## 🚀 **IMPLEMENTATION PRIORITY**

### **High Priority:**
1. Core chest system (loading, rendering)
2. Interaction detection
3. DSPOINC reward integration

### **Medium Priority:**
4. Opening animation
5. UI improvements
6. Helper functions

### **Low Priority:**
7. Sound effects
8. Particle effects
9. Advanced animations

---

## 📚 **DOCUMENTATION REQUIREMENTS**

### **Code Documentation:**
- Comprehensive JSDoc comments
- Usage examples in comments
- Configuration options documented

### **Rule Documentation:**
- Add chest section to 3D Model Rendering Rule
- Document chest API patterns
- Add quick reference guide

### **Technical Documentation:**
- System architecture overview
- Integration guide
- Troubleshooting section

---

## 🎯 **SUCCESS CRITERIA**

### **Functional:**
- ✅ Chests can be added to any level with 1-3 lines of code
- ✅ Players can open chests and receive DSPOINC
- ✅ Chests prevent duplicate rewards
- ✅ System works across all levels

### **Technical:**
- ✅ Modular, reusable code
- ✅ Follows existing patterns
- ✅ Well documented
- ✅ Performance optimized

### **User Experience:**
- ✅ Clear interaction prompts
- ✅ Smooth opening animations
- ✅ Reward notifications
- ✅ Intuitive gameplay

---

## 🔮 **FUTURE ENHANCEMENTS (Optional)**

### **Advanced Features:**
- Chest rarity system (common, rare, epic, legendary)
- Random DSPOINC amounts within range
- Chest respawn system (optional)
- Chest keys/locks system
- Animated treasure particles
- Sound effects (opening, reward)
- Chest glow effects (unopened chests)
- Mini-map chest indicators

### **Integration Ideas:**
- Quest system integration
- Achievement system integration
- Leaderboard for chest discoveries
- Chest collection tracking

---

## 📋 **IMPLEMENTATION CHECKLIST**

### **Before Starting:**
- [ ] Review existing 3D model patterns
- [ ] Review DSPOINC reward system
- [ ] Check chest model formats
- [ ] Plan module structure

### **During Implementation:**
- [ ] Create chest-system.js module
- [ ] Implement Chest class
- [ ] Implement ChestSystem class
- [ ] Integrate with main.js
- [ ] Test model loading
- [ ] Test interaction detection
- [ ] Test DSPOINC rewards
- [ ] Test opening animations

### **After Implementation:**
- [ ] Add to rules documentation
- [ ] Create usage examples
- [ ] Test across all levels
- [ ] Performance testing
- [ ] User testing

---

## 🧀 **FINAL NOTES**

### **Design Principles:**
- **Simplicity:** Easy to add chests with minimal code
- **Consistency:** Follow existing patterns (trees, bear traps)
- **Modularity:** Reusable across all levels
- **Extensibility:** Easy to add new chest types or features

### **Key Benefits:**
- **Quick Integration:** Add chests in seconds
- **Consistent Rewards:** Uses existing DSPOINC system
- **Scalable:** Works for unlimited chests per level
- **Maintainable:** Centralized chest management

---

**PLAN CREATED:** December 14, 2025  
**STATUS:** 📋 **READY FOR IMPLEMENTATION**  
**NEXT STEP:** Begin Phase 1 implementation  

**🎁 THIS PLAN ENSURES EASY CHEST INTEGRATION ACROSS ALL LEVELS! 🎁**

