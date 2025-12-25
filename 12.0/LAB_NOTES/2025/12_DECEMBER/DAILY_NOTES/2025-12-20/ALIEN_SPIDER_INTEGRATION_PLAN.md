# 🕷️ ALIEN SPIDER INTEGRATION PLAN - Level 6

**Date:** December 20, 2025  
**Status:** 📋 **PLANNING PHASE**  
**Target:** Level 6 (alongside Phoenix Dragon)

---

## 🎯 **OBJECTIVE**

Create a new monster system (`alien-spider.js`) similar to `phoenix2.js` that:
- Spawns in Level 6 alongside the Phoenix Dragon
- Has multiple behavior patterns (idle, walk, run, attack, damage, etc.)
- Uses the Alien Spider FBX model with embedded animations
- Follows the same architecture pattern as Phoenix Boss 2.0

---

## 📁 **MODEL FILES AVAILABLE**

### **Location:**
```
C:\xampp-server\htdocs\narrrfs-world\three.js\public\textures\3d models\Alien Spider 1\AFC_03\
```

### **Model Files:**
- ✅ **Base Model:** `AFC_03.fbx` - Main spider model
- ✅ **Textures:** 
  - `AFC_03_color.tga` - Base color texture
  - `AFC_03_normal.tga` - Normal map
  - `AFC_03_ao.tga` - Ambient occlusion
  - `AFC_03_metalness.tga` - Metalness map
  - `AFC_03_rough.tga` - Roughness map
  - `Eye_*.tga` - Eye textures (color, normal, ao, metalness, rough)
  - `Fur_1.tga`, `Fur_2.tga` - Fur textures

### **Animation Files (FBX):**
- ✅ `AFC_03@Idle_1.fbx` - Idle animation 1
- ✅ `AFC_03@Idle_2.fbx` - Idle animation 2
- ✅ `AFC_03@Walk.fbx` - Walking animation
- ✅ `AFC_03@Run.fbx` - Running animation
- ✅ `AFC_03@Attack_1.fbx` - Attack animation 1
- ✅ `AFC_03@Attack_2.fbx` - Attack animation 2
- ✅ `AFC_03@Damage_taken.fbx` - Damage reaction animation

---

## 🏗️ **ARCHITECTURE PLAN**

### **1. Create `alien-spider.js` Module**

**File:** `three.js/alien-spider.js`

**Structure (Following phoenix2.js pattern):**
```javascript
/**
 * 🕷️ ALIEN SPIDER BOSS SYSTEM - BEHAVIOR PATTERN ARCHITECTURE
 * 
 * Similar to Phoenix Boss 2.0, but with spider-specific behaviors:
 * - Ground-based movement (no flying)
 * - Multiple attack patterns
 * - Web-based attacks (future enhancement)
 * - Ground patrol behaviors
 */

class AlienSpiderBoss {
  constructor(config) {
    // Similar structure to PhoenixBoss2
    this.scene = config.scene;
    this.camera = config.camera;
    this.levelGroup = config.levelGroup;
    this.onBossDefeated = config.onBossDefeated || (() => {});
    this.onBossHit = config.onBossHit || (() => {});
    
    // Spider-specific properties
    this.model = null;
    this.mixer = null;
    this.actions = {};
    this.currentAction = null;
    
    // Behavior system
    this.behaviorMode = 'idle_1';
    this.behaviorTimer = 0;
    this.isAlive = true;
    this.isGrounded = true; // Always on ground (no flying)
    
    // Position and movement
    this.spawnPosition = new THREE.Vector3();
    this.patrolRadius = 10.0; // Ground patrol radius
    this.movementSpeed = 2.0;
    
    // Health system
    this.health = 100;
    this.maxHealth = 100;
    
    // Animation map
    this.animationMap = {
      idle: ['Idle_1', 'Idle_2'],
      movement: ['Walk', 'Run'],
      attack: ['Attack_1', 'Attack_2'],
      damage: ['Damage_taken']
    };
    
    // Behavior durations
    this.behaviorDurations = {
      idle_1: { duration: 3.0 },
      idle_2: { duration: 3.0 },
      walk_patrol: { duration: 5.0, movementSpeed: 2.0 },
      run_patrol: { duration: 3.0, movementSpeed: 4.0 },
      attack_1: { duration: 2.0 },
      attack_2: { duration: 2.5 },
      damage_reaction: { duration: 1.5 },
      // Future patterns can be added here
    };
  }
  
  // Model loading
  async loadModel(modelPath) {
    // Load FBX model using FBXLoader
    // Similar to Phoenix but for spider
  }
  
  // Behavior system
  setBehaviorMode(mode) {
    // Switch between behavior patterns
  }
  
  update(delta) {
    // Main update loop
  }
  
  // Individual behavior update functions
  updateIdle1(delta) { }
  updateIdle2(delta) { }
  updateWalkPatrol(delta) { }
  updateRunPatrol(delta) { }
  updateAttack1(delta) { }
  updateAttack2(delta) { }
  updateDamageReaction(delta) { }
}
```

---

## 🎮 **BEHAVIOR PATTERNS PLAN**

### **Phase 1: Basic Behaviors (7 patterns)**

1. **`idle_1`** - Idle animation 1
   - Duration: 3.0s
   - Animation: `Idle_1`
   - Behavior: Stand still, look around

2. **`idle_2`** - Idle animation 2
   - Duration: 3.0s
   - Animation: `Idle_2`
   - Behavior: Alternative idle pose

3. **`walk_patrol`** - Walking patrol
   - Duration: 5.0s
   - Animation: `Walk`
   - Behavior: Walk in circle around spawn point
   - Movement: 2.0 units/s

4. **`run_patrol`** - Running patrol
   - Duration: 3.0s
   - Animation: `Run`
   - Behavior: Fast circular patrol
   - Movement: 4.0 units/s

5. **`attack_1`** - Attack pattern 1
   - Duration: 2.0s
   - Animation: `Attack_1`
   - Behavior: Melee attack forward
   - Position: Move toward player briefly

6. **`attack_2`** - Attack pattern 2
   - Duration: 2.5s
   - Animation: `Attack_2`
   - Behavior: Alternative attack (maybe jump attack)
   - Position: Jump forward toward player

7. **`damage_reaction`** - Damage taken reaction
   - Duration: 1.5s
   - Animation: `Damage_taken`
   - Behavior: Play when hit by player
   - Trigger: When health decreases

### **Phase 2: Advanced Behaviors (Future - 5+ patterns)**

8. **`aggressive_charge`** - Charge at player
   - Run animation + direct movement toward player
   - High speed, short duration

9. **`web_attack`** - Web shooting (if web model available)
   - Special attack animation
   - Projectile system

10. **`retreat`** - Back away from player
    - Walk backward animation
    - Move away when health low

11. **`enraged_mode`** - Low health behavior
    - Faster attacks
    - More aggressive patterns
    - Red glow effect

12. **`death_sequence`** - Death animation
    - Play death animation
    - Set isAlive = false
    - Trigger onBossDefeated callback

---

## 🔧 **IMPLEMENTATION STEPS**

### **STEP 1: Create alien-spider.js Module**

**File:** `three.js/alien-spider.js`

**Tasks:**
1. Create `AlienSpiderBoss` class structure
2. Implement model loading (FBXLoader)
3. Implement animation system (AnimationMixer)
4. Create behavior pattern system
5. Add health system
6. Add position/movement system

**Reference:** Use `phoenix2.js` as template (lines 1-500)

---

### **STEP 2: Model Path Configuration**

**Model Path:**
```javascript
const SPIDER_MODEL_PATH = "/textures/3d models/Alien Spider 1/AFC_03/AFC_03.fbx";
```

**Animation Paths:**
```javascript
const SPIDER_ANIMATIONS = {
  idle_1: "/textures/3d models/Alien Spider 1/AFC_03/AFC_03@Idle_1.fbx",
  idle_2: "/textures/3d models/Alien Spider 1/AFC_03/AFC_03@Idle_2.fbx",
  walk: "/textures/3d models/Alien Spider 1/AFC_03/AFC_03@Walk.fbx",
  run: "/textures/3d models/Alien Spider 1/AFC_03/AFC_03@Run.fbx",
  attack_1: "/textures/3d models/Alien Spider 1/AFC_03/AFC_03@Attack_1.fbx",
  attack_2: "/textures/3d models/Alien Spider 1/AFC_03/AFC_03@Attack_2.fbx",
  damage: "/textures/3d models/Alien Spider 1/AFC_03/AFC_03@Damage_taken.fbx"
};
```

**Note:** FBX files need to be loaded separately (unlike GLB which has embedded animations)

---

### **STEP 3: Integrate into main.js**

**File:** `three.js/main.js`

**Tasks:**

1. **Import AlienSpiderBoss:**
```javascript
import { AlienSpiderBoss } from "./alien-spider.js";
```

2. **Add spider boss variable:**
```javascript
let alienSpiderBoss = null;
```

3. **Initialize in buildLevel6():**
```javascript
async function buildLevel6() {
  // ... existing Level 6 code ...
  
  // Initialize Alien Spider Boss
  const spiderConfig = {
    scene: scene,
    camera: camera,
    levelGroup: level6State.group,
    onBossDefeated: () => {
      console.log("🕷️ Alien Spider defeated!");
      // Handle defeat logic
    },
    onBossHit: (health, maxHealth) => {
      console.log(`🕷️ Alien Spider hit! Health: ${health}/${maxHealth}`);
      // Handle hit logic
    }
  };
  
  alienSpiderBoss = new AlienSpiderBoss(spiderConfig);
  
  // Set spawn position (different from Phoenix)
  const spiderSpawnPos = new THREE.Vector3(-20, 1, 0); // Ground level, opposite side
  alienSpiderBoss.spawnPosition.copy(spiderSpawnPos);
  
  // Load spider model
  await alienSpiderBoss.loadModel(SPIDER_MODEL_PATH);
  
  // Start with idle behavior
  alienSpiderBoss.setBehaviorMode('idle_1');
}
```

4. **Update in updateLevel6():**
```javascript
function updateLevel6(delta) {
  // ... existing Phoenix update ...
  
  // Update Alien Spider
  if (alienSpiderBoss && alienSpiderBoss.isAlive && typeof alienSpiderBoss.update === 'function') {
    alienSpiderBoss.update(delta);
  }
}
```

---

### **STEP 4: Add Behavior Cycling (Similar to Phoenix)**

**File:** `three.js/main.js`

**Add to cyclePhoenixBehavior function (or create separate function):**

```javascript
function cycleAlienSpiderBehavior() {
  if (!alienSpiderBoss) return;
  
  const behaviors = [
    'idle_1',
    'idle_2',
    'walk_patrol',
    'run_patrol',
    'attack_1',
    'attack_2',
    'damage_reaction'
  ];
  
  const behaviorNames = [
    '🕷️ Idle 1',
    '🕷️ Idle 2',
    '🕷️ Walk Patrol',
    '🕷️ Run Patrol',
    '🕷️ Attack 1',
    '🕷️ Attack 2',
    '🕷️ Damage Reaction'
  ];
  
  const currentIndex = behaviors.indexOf(alienSpiderBoss.behaviorMode);
  const nextIndex = (currentIndex + 1) % behaviors.length;
  const nextBehavior = behaviors[nextIndex];
  
  alienSpiderBoss.setBehaviorMode(nextBehavior);
  console.log(`🕷️ [ALIEN SPIDER] Switched to: ${behaviorNames[nextIndex]}`);
}
```

**Add key binding:**
```javascript
// Add to keydown handler
if (event.code === 'KeyN') { // N key for Next Spider behavior
  cycleAlienSpiderBehavior();
}
```

---

### **STEP 5: Add GUI Integration (Optional)**

**File:** `three.js/main.js` or `three.js/gui-system.js`

**Add spider boss section to options menu:**
- Behavior selector dropdown
- Health display
- Position display
- Size/scale controls (similar to Phoenix)

---

### **STEP 6: Handle FBX Animation Loading**

**Challenge:** FBX animations are separate files (unlike GLB embedded animations)

**Solution Options:**

**Option A: Load All Animations Separately**
```javascript
async loadAnimations() {
  const loader = new FBXLoader();
  
  for (const [key, path] of Object.entries(SPIDER_ANIMATIONS)) {
    const animFBX = await loader.loadAsync(path);
    // Extract animation from FBX
    const animation = animFBX.animations[0];
    this.actions[key] = this.mixer.clipAction(animation);
  }
}
```

**Option B: Use GLB Conversion (Recommended)**
- Convert FBX model + animations to GLB format
- Single file with embedded animations
- Better performance
- Easier to manage

**Conversion Tool:** Use existing FBX2glTF tool
```bash
npm run convert-spider
```

---

## 📊 **TECHNICAL CONSIDERATIONS**

### **1. Model Scaling**

**Target Size:** Similar to Phoenix (4.0 units)

**Implementation:**
```javascript
// Calculate bounding box
const box = new THREE.Box3().setFromObject(this.model);
const size = box.getSize(new THREE.Vector3());
const maxDim = Math.max(size.x, size.y, size.z);
const targetSize = 4.0;
const scale = targetSize / maxDim;

this.model.scale.set(scale, scale, scale);
```

---

### **2. Ground Positioning**

**Spawn Position:**
- Y = 1.0 (ground level)
- X, Z: Opposite side from Phoenix
- Example: Phoenix at (20, 12, 0), Spider at (-20, 1, 0)

**Ground Collision:**
- Ensure spider model bottom aligns with Y = 1.0
- Use bounding box min.y for ground alignment

---

### **3. Animation System**

**FBX Animation Loading:**
- Each animation is a separate FBX file
- Extract animation clip from each FBX
- Create AnimationAction for each clip
- Use AnimationMixer to play actions

**Animation Blending:**
- Smooth transitions between animations
- Use `fadeIn()` and `fadeOut()` methods
- Default fade duration: 0.3s

---

### **4. Behavior Pattern System**

**Following Phoenix Pattern:**
- `setBehaviorMode(mode)` - Initialize pattern
- `updateBehavior(delta)` - Switch statement routing
- Individual `updateXxxPattern(delta)` functions
- Timer-based phase system
- Animation fallback system

---

### **5. Collision Detection (Future)**

**Hit Detection:**
- Raycast from player weapon to spider
- Check distance for melee attacks
- Trigger `onBossHit` callback
- Play damage reaction animation

**Spider Attacks:**
- Check distance to player
- Trigger attack patterns when player nearby
- Deal damage to player (if health system exists)

---

## 🎯 **DEVELOPMENT PHASES**

### **Phase 1: Basic Integration (MVP)**
- ✅ Create `alien-spider.js` module
- ✅ Load model and basic animations
- ✅ Implement 3 basic behaviors (idle_1, walk_patrol, attack_1)
- ✅ Spawn in Level 6
- ✅ Basic movement and animation

**Estimated Time:** 2-3 hours

---

### **Phase 2: Complete Behavior System**
- ✅ Implement all 7 basic behaviors
- ✅ Add behavior cycling (N key)
- ✅ Add health system
- ✅ Add damage reaction

**Estimated Time:** 2-3 hours

---

### **Phase 3: Advanced Features**
- ✅ Add advanced behaviors (charge, retreat, enraged)
- ✅ Add GUI integration
- ✅ Add hit detection
- ✅ Add death sequence

**Estimated Time:** 3-4 hours

---

### **Phase 4: Polish & Optimization**
- ✅ Animation blending improvements
- ✅ Performance optimization
- ✅ Visual effects (glow, particles)
- ✅ Sound effects integration

**Estimated Time:** 2-3 hours

---

## 🚨 **CRITICAL CONSIDERATIONS**

### **1. FBX vs GLB Format**

**Current:** FBX files (separate model + animations)

**Recommendation:** Convert to GLB for:
- Better performance (single file)
- Embedded animations
- Smaller file size
- Easier management

**Action:** Use FBX2glTF conversion tool before implementation

---

### **2. Model Scale**

**Target:** 4.0 units (same as Phoenix)

**Check:** Verify model size after loading
- If too large: Scale down
- If too small: Scale up
- Use bounding box calculation

---

### **3. Ground Alignment**

**Critical:** Spider must be on ground (Y = 1.0)

**Implementation:**
```javascript
// After loading model
const box = new THREE.Box3().setFromObject(this.model);
const minY = box.min.y;
const yOffset = 1.0 - minY; // Adjust to ground level
this.model.position.y += yOffset;
```

---

### **4. Animation Naming**

**FBX Animation Names:**
- May differ from file names
- Check actual animation clip names in FBX
- Use fallback system if names don't match

**Example:**
```javascript
// Check available animations
console.log('Available animations:', this.model.animations.map(a => a.name));

// Use fallback
const animName = this.animationMap.idle[0] || 'Idle_1' || 'idle';
```

---

### **5. Performance**

**Two Bosses in Level 6:**
- Phoenix Dragon (flying, complex animations)
- Alien Spider (ground, complex animations)

**Optimization:**
- Use frustum culling (if needed)
- Limit update frequency for distant bosses
- Use LOD system (if needed)
- Monitor FPS during testing

---

## 📝 **FILE STRUCTURE**

### **New Files:**
- `three.js/alien-spider.js` - Main spider boss class

### **Modified Files:**
- `three.js/main.js` - Integration, initialization, update loop
- `three.js/gui-system.js` - GUI display (optional)

### **Model Files (No Changes):**
- `public/textures/3d models/Alien Spider 1/AFC_03/*.fbx` - Model and animations

---

## 🧪 **TESTING CHECKLIST**

### **Model Loading:**
- [ ] Model loads successfully
- [ ] Model scales correctly (4.0 units)
- [ ] Model positioned correctly (ground level)
- [ ] Model visible in Level 6

### **Animations:**
- [ ] All 7 animations load correctly
- [ ] Animations play smoothly
- [ ] Animation transitions work
- [ ] No animation glitches

### **Behaviors:**
- [ ] All 7 behaviors work correctly
- [ ] Behavior switching works (N key)
- [ ] Movement patterns work (patrol)
- [ ] Attack animations play correctly

### **Integration:**
- [ ] Spider spawns in Level 6
- [ ] Spider doesn't interfere with Phoenix
- [ ] Both bosses update correctly
- [ ] Performance is acceptable (60 FPS)

### **Future Features:**
- [ ] Hit detection works
- [ ] Health system works
- [ ] Death sequence works
- [ ] GUI integration works

---

## 🎯 **SUCCESS CRITERIA**

### **Phase 1 Complete When:**
- ✅ Spider model loads and displays in Level 6
- ✅ At least 3 behaviors work (idle, walk, attack)
- ✅ Animations play correctly
- ✅ No crashes or errors

### **Phase 2 Complete When:**
- ✅ All 7 basic behaviors implemented
- ✅ Behavior cycling works (N key)
- ✅ Health system functional
- ✅ Damage reaction works

### **Phase 3 Complete When:**
- ✅ Advanced behaviors added
- ✅ Hit detection works
- ✅ Death sequence works
- ✅ GUI integration complete

---

## 📚 **REFERENCES**

### **Similar Implementations:**
- `phoenix2.js` - Pattern architecture reference
- `main.js` - Integration examples (Level 6)
- `gui-system.js` - GUI display patterns

### **Documentation:**
- Phoenix Boss 2.0 documentation
- FBX loading examples
- Animation system documentation

---

## 🚀 **NEXT STEPS**

1. **Review this plan** with team
2. **Decide on FBX vs GLB** format
3. **Create alien-spider.js** module structure
4. **Implement Phase 1** (basic integration)
5. **Test and iterate**

---

**Status:** 📋 **PLANNING COMPLETE - READY FOR IMPLEMENTATION**  
**Created:** December 20, 2025  
**Next:** Begin Phase 1 implementation

