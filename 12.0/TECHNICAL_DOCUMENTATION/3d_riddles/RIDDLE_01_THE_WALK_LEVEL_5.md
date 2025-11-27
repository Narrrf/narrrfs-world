# 🚶 RIDDLE #1 — THE WALK (LEVEL 5)

**Document Created:** November 24, 2025  
**Last Updated:** November 23, 2025  
**Riddle ID:** `CHEESE_TEMPLE_LEVEL5_RIDDLE_01` (to be implemented)  
**Level:** Cheese Temple — Level 5 "The Walk"  
**Status:** ✅ **STEP 1 COMPLETE — MONSTER HUNT WORKING** — Exploration level with monster hunt riddle (10-minute timer, 50 monsters, flying monster shooting)  
**Traits / Rewards:** 
- ✅ **STEP 1 Trait:** `CHEESE_TEMPLE_LEVEL5_STEP1` (unlocked on completion of all 10 waves)
- ✅ **STEP 1 Rewards:** 250 DSPOINC base per wave × 10 waves = 2,500 base DSPOINC (multiplied by role multiplier server-side)
- ✅ **Database Tables:** See "DATABASE STRUCTURE & REWARD SYSTEM" section below

---

## 📋 OVERVIEW

### Objective
Enter a massive open-world city environment and freely explore the Klagenfurt city map. This is a large-scale exploration level where players can walk through a detailed 3D city environment scaled 5x larger than typical levels, creating an immersive urban exploration experience.

### Flow Summary
1. **Level Access:** Warp to Level 5 from Level 4 completion screen or level selector menu
2. **Map Loading:** Klagenfurt city map (GLTF) loads and scales to 5x size (~300 units)
3. **Exploration:** Free exploration of the city - walk, run, fly (god mode) through the urban environment
4. **Future Riddles:** Exploration objectives and interactive elements will be added

---

## 🏗️ ENVIRONMENT

### Map Specifications
- **Source:** `/textures/3d models/Maps/klagenfurt.gltf` (891KB)
- **Structure:** Multiple mesh objects (mesh_0_1 through mesh_0_7+) forming a complete city layout
- **Scale:** 5x larger than typical levels (~300 units vs ~60 units)
- **Dimensions:** Dynamically calculated from map bounding box, then multiplied by 5x scale factor
- **Position:** Centered at origin (0, 0, 0)
- **Ground Level:** Calculated from bounding box minimum Y value

### Environment Settings
- **Background:** Sky blue (`0x87ceeb`) - Outdoor city atmosphere
- **Fog:** Enabled for large-scale depth
  - **Color:** Sky blue (`0x87ceeb`)
  - **Near:** 100 units
  - **Far:** 500 units
- **Lighting:** Standard scene lighting (to be enhanced)
- **Spawn Position:** Center of map (X: 0, Z: 0), above ground level (Y: groundLevel + 2.0)

### Map Details
- **File Size:** 891KB
- **Vertices:** ~27,468 vertices
- **Triangles:** ~9,156 triangles
- **Objects:** 12+ objects
- **Mesh Structure:** Multiple child meshes forming buildings, roads, and city infrastructure

---

## 🎮 GAMEPLAY MECHANICS

### Current Implementation
1. **Level Warp:** `warpToLevel5()` function loads and displays the city map
2. **Map Loading:** 
   - Async GLTF loading with progress tracking
   - Automatic scaling calculation based on bounding box
   - 5x size multiplier applied
   - Map centered at origin
3. **Player Spawn:** 
   - Positioned at map center (X: 0, Z: 0)
   - Height calculated from ground level + 2 units
   - Spawn position logged to console for debugging
4. **Navigation:** 
   - Free movement through city
   - God mode supported (4x speed)
   - First-person and third-person camera modes
   - Collision detection with map geometry
5. **Super Jump:** 
   - 5x higher jump than other levels (75 units vs 15 units)
   - Activated with Space bar
   - Console log: "🚀 [LEVEL 5] Super Jump activated!"
6. **Character Animation:** 
   - 3rd person mouse character rendering perfect in all modes
   - Animation speed: 1.8x multiplier in normal mode (smooth walk)
   - Dynamic velocity-based scaling in god mode
   - Position lerp: 60 (faster than default 30)
   - Rotation speed: 0.5 (faster than default 0.3)

### Future Enhancements
- **Riddles/Objectives:** Add interactive elements and exploration goals
- **NPCs/Characters:** Populate the city with characters
- **Collectibles:** Add items to discover
- **Secrets:** Hidden areas and rewards
- **Landmarks:** Points of interest to explore
- **Quest System:** Structured exploration objectives

---

## 🔧 TECHNICAL IMPLEMENTATION

### Level 5 State Object
```javascript
const level5State = {
  built: false,
  group: new THREE.Group(),
  mapMesh: null, // Reference to loaded Klagenfurt map
  mapScale: 5.0, // Scale multiplier to make it 5x larger
  spawnPosition: new THREE.Vector3(0, 5, 0) // Player spawn position (adjusted after map loads)
};
```

### Map Loading Function
**Function:** `buildLevel5TheWalk()`  
**Location:** `three.js/main.js` (lines ~9077-9195)

**Process:**
1. Load GLTF file from `/textures/3d models/Maps/klagenfurt.gltf`
2. Calculate bounding box to determine original dimensions
3. Scale to 5x larger than base level size (300 units)
4. Center map at origin (0, 0, 0)
5. Calculate spawn position on top of ground level
6. Add map to level group and set visibility

**Key Features:**
- Async/await for proper loading sequence
- Progress tracking during file load
- Automatic scaling based on map dimensions
- Material updates for all child meshes
- Error handling with fallback placeholder

### Warp Function
**Function:** `warpToLevel5()`  
**Location:** `three.js/main.js` (lines ~11532-11596)

**Process:**
1. Hide all other levels
2. Build Level 5 if not already built (awaits async loading)
3. Show Level 5 group
4. Set current level to LEVEL5
5. Apply environment (background, fog)
6. Load background music
7. Position player at spawn position
8. Reset camera to first-person view

**Key Features:**
- Proper async/await handling
- Level cleanup (Level 4 HUD, weapons)
- Comprehensive logging for debugging
- Environment application
- Player positioning

### Restart Function
**Function:** `restartLevel5()`  
**Location:** `three.js/main.js` (lines ~11598-11620)

**Process:**
1. Build level if needed
2. Set current level
3. Show level group
4. Apply environment
5. Load music
6. Reset player position
7. Reset camera

---

## 📊 MAP STRUCTURE ANALYSIS

### Based on Three.js Editor Inspection
- **Primary Meshes:** mesh_0_1 through mesh_0_7 (at least 7 main mesh objects)
- **Geometry:** City layout with buildings, roads, and infrastructure
- **Scale:** Some meshes use Y scale of 0.300 (flattened city blocks)
- **Materials:** Dark blue/grey textures for buildings and roads
- **Position:** All meshes initially at origin (0, 0, 0)
- **Complexity:** ~27,468 vertices, ~9,156 triangles across all objects

### Scaling Strategy
1. **Base Reference:** Typical level size is ~60 units
2. **Target Size:** 300 units (5x multiplier)
3. **Calculation:** 
   - Get map's maximum dimension (X, Y, or Z)
   - Calculate scale factor: `targetSize / maxDimension`
   - Apply additional 5x multiplier from `level5State.mapScale`
   - Final scale = `scaleFactor * 5.0`
4. **Result:** Map is significantly larger than other levels for exploration

---

## 🎯 SPAWN POSITION CALCULATION

### Process
1. **Before Scaling:** Calculate original bounding box and center
2. **Apply Scale:** Scale the entire scene uniformly
3. **Recalculate:** Get new bounding box after scaling
4. **Center Map:** Position map so center is at origin (0, 0, 0)
5. **Final Bounding Box:** Recalculate one more time after positioning
6. **Ground Level:** Use `scaledBox.min.y` as ground level
7. **Spawn Height:** `groundLevel + 2.0` units (safe spawn above ground)

### Code Logic
```javascript
// Calculate bounding box before positioning
let scaledBox = new THREE.Box3().setFromObject(gltf.scene);
const scaledCenter = scaledBox.getCenter(new THREE.Vector3());

// Position map at origin
gltf.scene.position.sub(scaledCenter);

// Recalculate after positioning
scaledBox = new THREE.Box3().setFromObject(gltf.scene);
const groundLevel = scaledBox.min.y;
const spawnY = groundLevel + 2.0;

level5State.spawnPosition.set(0, spawnY, 0);
```

---

## 🔗 INTEGRATION POINTS

### Level 4 Completion
- **Button:** "🚀 Proceed to Level 5" in Level 4 completion screen
- **Action:** Calls `warpToLevel5()` to transition to Level 5
- **Location:** `showLevel4CompletionScreen()` function

### Level Selector Menu
- **Button:** "🚶 Level 5 - The Walk" in God Mode level selector
- **Action:** Calls `warpToLevel5()` to jump directly to Level 5
- **Access:** Press `L` key in God Mode to open level selector
- **Location:** `showLevelSelector()` function

### Environment System
- **Background Color:** Sky blue (`0x87ceeb`) applied via `applyLevelEnvironment()`
- **Fog:** Enabled with far distance for large-scale rendering
- **Music:** Background music path configured (to be added)

---

## 🐛 KNOWN ISSUES & FIXES

### Issue 1: Player Gets Stuck in Level 1 When Loading Level 5
**Status:** ✅ FIXED  
**Cause:** `buildLevel5TheWalk()` is async but wasn't awaited in `warpToLevel5()`  
**Solution:** Made `warpToLevel5()` async and await the build function  
**Fix Date:** November 24, 2025

### Issue 2: Incorrect Spawn Position
**Status:** ✅ FIXED  
**Cause:** Bounding box calculated before map was centered, causing wrong ground level  
**Solution:** Recalculate bounding box after positioning map at origin  
**Fix Date:** November 24, 2025

### Issue 3: Map Not Loading
**Status:** ✅ FIXED  
**Cause:** No progress tracking or error handling  
**Solution:** Added progress callbacks, detailed logging, and error handling with fallback  
**Fix Date:** November 24, 2025

### Issue 4: 3rd Person Animation Too Slow in Normal Mode
**Status:** ✅ FIXED  
**Cause:** Animation speed not scaled for Level 5 normal mode (looked like slow motion)  
**Solution:** Added 1.8x animation speed multiplier for Level 5 in normal mode  
**Fix Date:** November 24, 2025  
**Result:** Smooth, natural-looking walk animation matching movement speed

### Issue 5: Collision Mesh Creation Failure (UV Attribute Mismatch)
**Status:** ✅ FIXED  
**Cause:** `mergeGeometries()` failed because GLTF map meshes had inconsistent UV attributes (some had UVs, some didn't), while border walls (BoxGeometry) always had UVs. The merge function requires ALL geometries to have identical attribute sets.  
**Solution:** Created `normalizeGeometryAttributes()` helper function that ensures all geometries have compatible attributes (indices, normals, UVs) before merging. Adds dummy UVs `(0,0)` to geometries missing them, computes normals if missing, and creates indices if missing.  
**Fix Date:** November 23, 2025  
**Result:** Collision mesh now merges successfully with all 9 geometries (5 map meshes + 4 border walls). Ground collision and wall collision working perfectly. Player can walk on map surface and is blocked by cheese border walls. Super jump feature fully functional.  
**Technical Details:**
- **Function:** `normalizeGeometryAttributes()` in `three.js/main.js` (lines ~9500-9534)
- **Applied To:** All map meshes and border walls before merging
- **Merge Result:** Single indexed `BufferGeometry` with `MeshBVH` for efficient collision detection
- **Console Output:** `✅ [LEVEL 5] Collision mesh created successfully!`

---

## 📝 FUTURE DEVELOPMENT

### Planned Features
1. **Exploration Objectives:** Add specific goals for players to find
2. **Interactive Elements:** Buildings, doors, NPCs, items
3. **Collectibles:** Items scattered throughout the city
4. **Secrets:** Hidden areas and rewards
5. **Landmarks:** Notable locations to discover
6. **Quest System:** Structured exploration missions
7. **Riddles:** Puzzle elements within the city
8. **Performance Optimization:** LOD system if needed for large map

### Technical Improvements
1. **Collision System:** ✅ Complete - Ground and wall collision working perfectly (November 23, 2025)
2. **Lighting:** Dynamic lighting based on time of day
3. **Weather:** Optional weather effects
4. **Sound Design:** Ambient city sounds, footsteps
5. **Performance:** Culling optimization for distant objects
6. **Loading:** Chunked loading for very large maps

---

## 🎨 VISUAL DESIGN

### Color Scheme
- **Background:** Sky blue (`0x87ceeb`) - Bright, outdoor feel
- **Fog:** Sky blue with far distance (100-500 units) - Depth and performance
- **Map Colors:** Dark blue/grey buildings and roads (from GLTF materials)

### Atmosphere
- **Mood:** Open-world exploration, freedom to roam
- **Scale:** Massive environment emphasizing exploration
- **Style:** Realistic city layout with detailed geometry

---

## 🔍 DEBUGGING & TESTING

### Console Logs
The implementation includes comprehensive logging:
- `🚶 [LEVEL 5] Building 'The Walk' level...` - Build start
- `📦 [LEVEL 5] Loading map: X%` - Progress updates
- `✅ [LEVEL 5] GLTF file loaded successfully` - File loaded
- `📊 [LEVEL 5] Map structure:` - Mesh information
- `📏 [LEVEL 5] Map dimensions:` - Size calculations
- `✅ [LEVEL 5] Map scaled by Xx` - Scale factor
- `📏 [LEVEL 5] Final map dimensions:` - Final size and spawn position
- `✅ [LEVEL 5] Warped to 'The Walk'` - Warp complete

### Testing Checklist
- [x] Map loads successfully from level selector
- [x] Map loads successfully from Level 4 completion
- [x] Player spawns on top of ground (not floating or clipping)
- [x] Player can walk/run through city
- [x] Ground collision working (player walks on map surface)
- [x] Wall collision working (player blocked by cheese border walls)
- [x] Super jump working (75 units jump height)
- [x] Camera works correctly in first-person
- [x] Camera works correctly in third-person
- [x] God mode works (4x speed)
- [x] Map scales correctly (5x larger)
- [x] Fog renders correctly
- [x] Background color applies correctly
- [x] Collision mesh created successfully
- [x] No console errors during loading
- [x] Performance is acceptable

---

## 📚 RELATED FILES

### Implementation Files
- `three.js/main.js` - Main game logic and Level 5 implementation
  - `level5State` object (lines ~903-912)
  - `buildLevel5TheWalk()` function (lines ~9077-9195)
  - `warpToLevel5()` function (lines ~11532-11596)
  - `restartLevel5()` function (lines ~11598-11620)

### Documentation Files
- `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/LEVEL_5_THE_WALK_PLAN.md` - Initial implementation plan
- `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_01_THE_WALK_LEVEL_5.md` - This file

### Asset Files
- `three.js/public/textures/3d models/Maps/klagenfurt.gltf` - The city map model (891KB)

---

## 🚀 DEPLOYMENT STATUS

### Current Status
- ✅ Level 5 constant added to `LEVEL_IDS`
- ✅ Level 5 environment settings configured
- ✅ Level 5 state object created
- ✅ Map loading function implemented
- ✅ Warp function implemented
- ✅ Restart function implemented
- ✅ Level 4 completion screen updated
- ✅ Level selector menu updated
- ✅ Spawn position calculation fixed

### Next Steps
- [ ] Add exploration objectives/riddles
- [ ] Test map loading in production
- [ ] Add background music for Level 5
- [ ] Optimize performance if needed
- [ ] Add interactive elements

---

## 🎯 SUCCESS METRICS

### Technical Success
- ✅ Map loads without errors
- ✅ Map scales correctly (5x)
- ✅ Player spawns correctly
- ✅ Navigation works smoothly
- ✅ No performance issues

### User Experience Success
- ✅ Smooth level transition
- ✅ Intuitive exploration
- ✅ Large, impressive environment
- ✅ Freedom to explore
- ✅ Clear visual feedback

---

---

## 🧀 CHEESE BORDER WALLS (November 23, 2025)

### Implementation
Four massive cheese-stone walls border the entire Level 5 play zone, creating a visual and physical boundary around the Klagenfurt city map.

### Wall Specifications
- **Texture:** `/textures/blocks/cheese-stone.png` (preserves original PNG scale - no stretching)
- **Count:** 4 walls (North, South, East, West)
- **Height:** 200 units (tall enough to contain the play zone)
- **Thickness:** 50 units (thick walls for visual presence)
- **Positioning:** Walls directly border the map with 5-unit overlap to eliminate gaps
- **Ground Alignment:** Walls sit directly on map ground level (`scaledMin.y`)
- **Collision:** Walls are included in collision mesh for player collision detection

### Technical Details
**Function:** Created in `buildLevel5TheWalk()` (lines ~9270-9375)  
**Storage:** Walls stored in `level5State.borderWalls` object:
```javascript
level5State.borderWalls = {
  north: northWall,
  south: southWall,
  east: eastWall,
  west: westWall
}
```

**Collision Integration:** Walls are merged into the Level 5 collision mesh (lines ~9483-9485) using `BufferGeometryUtils.mergeGeometries()`, ensuring player cannot walk through them.

### Wall Positioning Logic
- **North Wall (Z+):** Positioned at `mapMaxZ - 5 + (wallThickness / 2)` (overlaps map by 5 units)
- **South Wall (Z-):** Positioned at `mapMinZ + 5 - (wallThickness / 2)` (overlaps map by 5 units)
- **East Wall (X+):** Positioned at `mapMaxX - 5 + (wallThickness / 2)` (overlaps map by 5 units)
- **West Wall (X-):** Positioned at `mapMinX + 5 - (wallThickness / 2)` (overlaps map by 5 units)

### Texture Settings
- **Wrap Mode:** `THREE.RepeatWrapping` (S and T)
- **Repeat:** `(1, 1)` - Original PNG scale preserved (no stretching or "scratching")
- **Material:** `MeshStandardMaterial` with cheese-stone texture

### Known Issues & Fixes
**Issue:** Player could walk through walls  
**Status:** ✅ FIXED  
**Solution:** Added wall geometries to collision mesh merge process  
**Fix Date:** November 23, 2025

**Issue:** Texture was stretched/scratched  
**Status:** ✅ FIXED  
**Solution:** Changed texture repeat from (20, 20) to (1, 1) to preserve original PNG scale  
**Fix Date:** November 23, 2025

**Issue:** Visible gap between map and walls  
**Status:** ✅ FIXED  
**Solution:** Added 5-unit overlap offset so walls overlap into map edges  
**Fix Date:** November 23, 2025

---

---

## 🧀 STEP 0: WEAPON TRIGGER PLATE (November 24, 2025)

### Implementation
Classic cheese-stone trigger plate spawns near player spawn point. When player stands on the plate, it activates the weapon system (slots 1 & 2) for Level 5 riddle gameplay.

### Trigger Plate Specifications
- **Texture:** `/textures/blocks/cheese-stone.png`
- **Size:** 2.6 x 0.35 x 2.6 units (BoxGeometry)
- **Position:** Near spawn point (X: spawn + 4, Z: spawn - 4, Y: ground level)
- **Ground Alignment:** Plate center positioned at `groundLevel + (plateHeight / 2)` so top sits flush with ground
- **Material:** `MeshStandardMaterial` with cheese-stone texture, emissive yellow glow (0.2 intensity)

### Step 0 State Machine
- **State Object:** `level5RiddleState` manages plate, timers, and weapon flags
- **Activation:** Player must stand on plate for `LEVEL5_TRIGGER_PLATE_ACTIVATION_TIME` (1.5 seconds)
- **Visual Feedback:** Plate animates down when pressed, smoothly lerps to pressed position
- **Weapon Unlock:** After activation, weapons in slots 1 & 2 become available (same as Level 4)

### Technical Details
**Function:** `createLevel5TriggerPlate()` creates and positions the plate  
**Update:** `updateLevel5Step0()` handles player detection and plate animation  
**Reset:** `resetLevel5RiddleState()` cleans up state on level warp/restart

### Known Issues & Fixes

**Issue:** Plate was floating too high above ground  
**Status:** ✅ FIXED (November 24, 2025)  
**Solution:** Calculate and use actual ground level from raycast instead of spawn-relative offset  
**Technical:** Plate positioned at `groundLevel + (plateHeight / 2)` using stored `level5State.groundLevelY`

**Issue:** Bullets not visible when shooting in Level 5  
**Status:** ✅ FIXED (November 24, 2025)  
**Solution:** Updated `updateLevel4Bullets()` to allow Level 5 bullets when weapons enabled  
**Technical:** Added check: `currentLevel === LEVEL_IDS.LEVEL5 && level5RiddleState.weaponsEnabled`

### Weapon System Integration
- **Slots:** 1 (Pistol Mk I) and 2 (Sci-Fi Pistol 1)
- **Bullets:** Yellow cheese bullets (slot 1), purple SF13 bullets (slot 2)
- **Shooting:** Same system as Level 4 (reuses `handleLevel4Shooting()`, `fireLevel4SingleShot()`)
- **Update Loop:** Bullets update via `updateLevel4Bullets()` called in `updateLevel5()` when weapons enabled

---

---

## 🐉 STEP 1: MONSTER HUNT (November 24, 2025)

### Implementation
After weapons are activated in Step 0, Step 1 begins automatically: a 10-minute monster hunt across the entire Level 5 map. Players must defeat all 50 monsters before time runs out to complete the step.

### Step 1 Specifications
- **Duration:** 10 minutes (600 seconds) countdown timer
- **Monster Count:** 50 monsters spawned across the entire map
- **Spawn System:** Two-pass grid system ensuring full map coverage
  - **Pass 1:** At least one monster per grid cell (60x60 unit cells)
  - **Pass 2:** Remaining monsters randomly distributed
- **Monster Types:** All available monsters from Level 4, including flying monsters
- **Flying Monsters:** Can shoot thunder bullets at the player (dangerous!)
- **Reward:** 2,500 DSPOINC on completion
- **Trait:** `CHEESE_TEMPLE_LEVEL5_STEP1_COMPLETE` trait unlocked

### Timer System
- **HUD Display:** Top-center timer showing remaining time (MM:SS format)
- **Auto-Reactivation:** Timer auto-reactivates if accidentally disabled (prevents stops)
- **Warning State:** Timer turns red and pulses when < 1 minute remains
- **Timeout Handling:** Game over screen if timer reaches 0:00
- **Status:** ✅ **FIXED** (November 24, 2025) — Timer now counts down continuously without stopping

### Countdown Popup
- **Duration:** 5-second countdown (5, 4, 3, 2, 1, BEGIN!)
- **Size:** Compact design (40% smaller than original)
  - Padding: 20px 30px
  - Font sizes: Title 20px, Countdown 36px
  - Dimensions: 250px-300px width
- **Animation:** Subtle scale animation (1.0 to 1.1) instead of aggressive bounce
- **Status:** ✅ **FIXED** (November 24, 2025) — Popup is appropriately sized and less distracting

### Monster Spawning
- **Grid System:** 25 columns × 16 rows = 400 cells covering entire map
- **Map Bounds:** X(-750 to 750), Z(-480 to 480)
- **Two-Pass Distribution System:**
  - **Pass 1:** At least one monster per grid cell (ensures full map coverage up to 400 cells)
  - **Pass 2:** Remaining monsters randomly distributed across all cells
  - **Cell Size:** ~60×60 units per cell (map width/depth divided by grid dimensions)
  - **Cell Padding:** 10% padding from cell edges (prevents boundary spawns)
- **Ground Detection:** Uses `getLevel5GroundLevelAt(x, z)` raycast for accurate ground positioning
- **Flying Monsters:** Spawn at ground level + height offset (20-30 units above ground)
- **Ground Monsters:** Spawn on ground + small offset (1-3 units above ground)
- **Size Variation:** Base scale 4x for testing visibility, +10% per wave (wave 1: 4x, wave 10: 4.9x)
- **Animation:** Monsters play 'Walk', 'Run', 'Fly', or first available animation based on type

### Flying Monster Thunder Bullets
- **Speed:** 9 units/second (50% faster than original 6)
- **Cooldown:** 4.5 seconds between shots
- **Lifetime:** 10 seconds
- **Visual:** Yellow sphere with purple emissive glow, pulsing animation
- **Danger:** Player hit = instant game over (crushed death scene)
- **Collision:** Ray-based collision detection with player collider

### Explosion Effects
- **Style:** Rainbow pixel cubes (matches Level 4)
- **Particle Count:** 15 cubes per explosion
- **Physics:** Outward explosion with gravity and fade-out
- **Visual:** Random HSL colors, smooth rotation and opacity fade

### Known Issues & Fixes

**Issue:** Timer stopped counting down at 9:59  
**Status:** ✅ FIXED (November 24, 2025)  
**Solution:** Added auto-reactivation logic - if `step1Active` is true but `step1TimerActive` is false, timer automatically reactivates  
**Technical:** Modified `updateLevel5Step1Timer()` to detect and recover from accidental timer stops

**Issue:** Countdown popup too large and bouncy  
**Status:** ✅ FIXED (November 24, 2025)  
**Solution:** Reduced popup size by 40% and changed animation to subtle scale (1.0 to 1.1)  
**Technical:** Reduced padding, font sizes, dimensions, and visual effects in `showLevel5Step1Countdown()`

**Issue:** Flying monsters couldn't be shot  
**Status:** ✅ FIXED (November 24, 2025)  
**Solution:** Enhanced hit detection with bounding box fallback for skinned meshes  
**Technical:** Modified `fireLevel4SingleShot()` to handle skinned meshes and use bounding box approximation if raycast fails

**Issue:** Monster explosion effects not matching Level 4  
**Status:** ✅ FIXED (November 24, 2025)  
**Solution:** Created `createLevel5MonsterExplosionEffect()` with rainbow pixel cubes matching Level 4  
**Technical:** Particles use random HSL colors, explode outward with gravity, fade out over 1 second

### Technical Details
**Functions:**
- `startLevel5Step1()` — Initializes Step 1 state and spawns monsters
- `updateLevel5Step1Timer(delta)` — Updates timer countdown and display
- `updateLevel5MonsterCounter()` — Updates monster count display
- `spawnLevel5Step1Monsters()` — Two-pass spawn system for map coverage
- `spawnLevel5Monster()` — Individual monster spawn with skeleton fixes
- `defeatLevel5Monster()` — Handles monster defeat, explosion, and rewards
- `updateLevel5FlyingMonsters()` — Updates flying monster shooting logic
- `updateLevel5ThunderBullets()` — Updates thunder bullet movement and collision
- `updateLevel5ExplosionParticles()` — Updates rainbow cube explosion particles
- `createLevel5Step1HUD()` — Creates timer and counter UI elements

**State Management:**
- `level5RiddleState.step1Active` — Step 1 is active
- `level5RiddleState.step1Timer` — Remaining time in seconds
- `level5RiddleState.step1TimerActive` — Timer is counting down
- `level5RiddleState.monstersDefeated` — Count of defeated monsters
- `level5RiddleState.totalMonsters` — Total monsters to defeat (50)
- `level5State.monsters[]` — Array of active monster objects
- `level5State.thunderBullets[]` — Array of active thunder bullets
- `level5State.explosionParticles[]` — Array of explosion particle objects

**Constants:**
- `LEVEL5_STEP1_TIMER_DURATION = 600` (10 minutes)
- `LEVEL5_STEP1_MONSTER_COUNT = 50`
- `LEVEL5_STEP1_GRID_CELL_SIZE = 50`
- `LEVEL5_THUNDER_BULLET_SPEED = 9` (50% faster)
- `LEVEL5_THUNDER_BULLET_COOLDOWN = 4500` (4.5 seconds)
- `LEVEL5_STEP1_DSPOINC_REWARD = 2500`
- `LEVEL5_STEP1_TRAIT = 'CHEESE_TEMPLE_LEVEL5_STEP1_COMPLETE'`

---

---

## 💾 DATABASE STRUCTURE & REWARD SYSTEM

### **Database Tables Used for Level 5 Rewards & Traits**

#### **1. Trait Storage: `tbl_user_traits`**
**Purpose:** Stores user trait unlocks for Level 5 completion  
**Schema:**
```sql
CREATE TABLE tbl_user_traits (
  user_id TEXT NOT NULL,
  trait TEXT NOT NULL,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, trait)
);
```

**Level 5 Traits:**
- **Trait Key:** `CHEESE_TEMPLE_LEVEL5_STEP1`
- **Description:** "Level 5 Step 1 - All Waves Complete"
- **Unlocked:** When player completes all 10 waves of Step 1
- **Storage:** Saved via `/api/user/traits.php` POST endpoint

**Query Example:**
```sql
-- Check if user has Level 5 Step 1 trait
SELECT * FROM tbl_user_traits 
WHERE user_id = ? AND trait = 'CHEESE_TEMPLE_LEVEL5_STEP1';
```

#### **2. Reward Completion Tracking: `tbl_riddle_completions`**
**Purpose:** Records each wave completion with base reward, multiplier, and total reward  
**Schema:**
```sql
CREATE TABLE tbl_riddle_completions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  discord_id TEXT NOT NULL,
  discord_name TEXT,
  riddle_id TEXT NOT NULL,
  level_id TEXT NOT NULL,
  base_reward INTEGER NOT NULL,
  multiplier REAL NOT NULL,
  total_reward INTEGER NOT NULL,
  completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  session_id TEXT,
  metadata TEXT,
  UNIQUE(discord_id, riddle_id)
);
```

**Level 5 Wave Riddle IDs:**
- `CHEESE_TEMPLE_LEVEL5_WAVE1` through `CHEESE_TEMPLE_LEVEL5_WAVE10`
- Each wave: `base_reward: 250`, `multiplier: 2.0` (if VIP Holder), `total_reward: 500`
- Total for all 10 waves: **5,000 DSPOINC** (with 2x multiplier)

**Query Example:**
```sql
-- Check all Level 5 wave completions for a user
SELECT riddle_id, base_reward, multiplier, total_reward, completed_at 
FROM tbl_riddle_completions 
WHERE discord_id = ? AND riddle_id LIKE 'CHEESE_TEMPLE_LEVEL5_WAVE%'
ORDER BY completed_at DESC;

-- Get total DSPOINC from Level 5 waves
SELECT COUNT(*) as wave_count, SUM(total_reward) as total_dspoinc 
FROM tbl_riddle_completions 
WHERE discord_id = ? AND riddle_id LIKE 'CHEESE_TEMPLE_LEVEL5_WAVE%';
```

#### **3. DSPOINC Balance: `tbl_user_scores`**
**Purpose:** Stores actual DSPOINC points added to user balance  
**Schema:**
```sql
CREATE TABLE tbl_user_scores (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id TEXT NOT NULL,
  game TEXT NOT NULL,
  score INTEGER NOT NULL,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  source TEXT DEFAULT 'legacy',
  game_type TEXT,
  season TEXT DEFAULT 'season_2',
  created_at DATETIME
);
```

**Level 5 Score Records:**
- **game:** `'cheese_temple_riddles'`
- **source:** `'riddle_completion'`
- **score:** Total reward amount (e.g., 500 per wave with multiplier)
- Each wave completion adds a separate record with the multiplied reward

**Query Example:**
```sql
-- Get total DSPOINC from Level 5 rewards
SELECT SUM(score) as total_dspoinc 
FROM tbl_user_scores 
WHERE user_id = ? 
  AND source = 'riddle_completion' 
  AND game = 'cheese_temple_riddles';
```

#### **4. Score Adjustment History: `tbl_score_adjustments`**
**Purpose:** Audit trail for all DSPOINC awards (admin tracking)  
**Schema:**
```sql
CREATE TABLE tbl_score_adjustments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id TEXT NOT NULL,
  admin_id TEXT NOT NULL,
  amount INTEGER NOT NULL,
  action TEXT NOT NULL,
  reason TEXT,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Level 5 Adjustment Records:**
- **admin_id:** `'system-riddle-reward'`
- **action:** `'add'`
- **amount:** Total reward (e.g., 500 per wave)
- **reason:** `'Riddle completion (CHEESE_TEMPLE_LEVEL5_WAVE1): base 250 × 2.00 = 500 DSPOINC'`

### **Reward Calculation Flow**

1. **Base Reward:** 250 DSPOINC per wave (defined in `LEVEL5_STEP1_WAVE_REWARD_DSPOINC`)
2. **Role Multiplier:** Applied server-side by `/api/dev/riddle-reward.php`
   - VIP Holder: 2.0x
   - Holder: 1.5x
   - Champion: 1.4x
   - Season Tester/WL: 1.3x
   - Early Bird: 1.2x
   - Cheese Hunter: 1.1x
   - Default: 1.0x
3. **Total Reward:** `base_reward × multiplier = total_reward`
   - Example: 250 × 2.0 = 500 DSPOINC per wave
   - Total for 10 waves: 5,000 DSPOINC (with 2x multiplier)

### **API Endpoints**

**Reward API:** `/api/dev/riddle-reward.php`
- **Method:** POST
- **Payload:**
  ```json
  {
    "discord_id": "user_discord_id",
    "discord_name": "username",
    "riddle_id": "CHEESE_TEMPLE_LEVEL5_WAVE1",
    "level_id": "CHEESE_TEMPLE_LEVEL5",
    "base_reward": 250,
    "session_id": "session_id"
  }
  ```
- **Response:** Returns `success`, `total_reward`, `multiplier`, `multiplier_source`

**Trait API:** `/api/user/traits.php`
- **Method:** POST
- **Payload:**
  ```json
  {
    "user_id": "user_discord_id",
    "trait_key": "CHEESE_TEMPLE_LEVEL5_STEP1",
    "trait_value": "1"
  }
  ```
- **Response:** Returns `success` and confirmation message

### **Important Notes**

⚠️ **Display vs Actual Reward:**
- The game UI displays base reward (e.g., "2500 DSPOINC" = 250 × 10 waves)
- The API applies role multipliers server-side
- Actual DSPOINC awarded = base reward × multiplier (e.g., 5000 DSPOINC with 2x multiplier)

✅ **Duplicate Prevention:**
- `tbl_riddle_completions` has `UNIQUE(discord_id, riddle_id)` constraint
- Prevents duplicate rewards if player completes same wave multiple times
- Returns 409 Conflict if already completed

✅ **Trait Unlocking:**
- Trait is unlocked once when all 10 waves are complete
- Stored in `tbl_user_traits` with timestamp
- Can be queried to check completion status

**Last Updated:** November 26, 2025 (Database Structure Documentation Added)  
**Status:** ✅ **STEP 1 COMPLETE - MONSTER HUNT WORKING** — Timer counting down, monsters shootable, explosions working, rewards and traits verified  
**Next Phase:** Test Step 1 completion and design Step 2 riddle objectives

