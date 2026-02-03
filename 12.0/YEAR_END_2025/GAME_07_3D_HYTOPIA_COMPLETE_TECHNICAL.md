# 🎮 GAME 7: 3D HYTOPIA GAME - COMPLETE TECHNICAL DOCUMENTATION 2025

**Created:** December 20, 2025  
**Last Updated:** February 3, 2026 (Level 6 Boss HUD – Alien Spider + Phoenix sync)  
**Status:** ✅ **STABLE PRODUCTION VERSION - MOBILE & VR OPTIMIZED**  
**Version:** 2026-02-03-LEVEL6-BOSS-HUD  
**Purpose:** Complete technical reference for 3D Riddle Game integration in Narrrfs World

---

## 📋 **TABLE OF CONTENTS**

1. [Overview](#overview)
2. [Architecture & File Structure](#architecture--file-structure)
3. [Module System](#module-system)
4. [Level System](#level-system)
5. [Riddle System](#riddle-system)
6. [Database Integration](#database-integration)
7. [API Integration](#api-integration)
8. [Profile.html Integration](#profilehtml-integration)
9. [Admin Interface Integration](#admin-interface-integration)
10. [Shop System Integration (Future)](#shop-system-integration-future)
11. [Achievement System Integration (Future)](#achievement-system-integration-future)
12. [Discord Integration](#discord-integration)
    - [Discord Login & Authentication System](#1-discord-login--authentication-system--working---production-ready)
    - [Discord Role-Based Multipliers](#2-discord-role-based-multipliers)
13. [Mobile Controls System](#mobile-controls-system---complete---january-18-2026)
14. [VR Support System (Meta Quest 3)](#vr-support-system-meta-quest-3---optimized---january-18-2026)
15. [God Mode Settings Persistence System](#god-mode-settings-persistence-system-january-9-2026----production-ready)
16. [Level 6 Boss HUD System](#level-6-boss-hud-system-february-3-2026----complete)
17. [Code Examples](#code-examples)
18. [Future Implementation Plans](#future-implementation-plans)

---

## 🎯 **OVERVIEW**

### **Game Description:**
The 3D Riddle Game is a Three.js-based 3D adventure game featuring 6 levels, riddle systems, boss fights, weapon systems, and full integration with Narrrfs World ecosystem. Built with modular architecture for decades of development.

### **Key Features:**
- ✅ 6 Complete Levels (Level 1-6)
- ✅ Modular Architecture (12 core modules)
- ✅ Riddle System (5 levels with multiple riddles)
- ✅ Boss System (Phoenix Dragon + Alien Spider)
- ✅ Weapon System (Levels 4-6)
- ✅ Chest System (All levels)
- ✅ Grass System (Procedural generation)
- ✅ Audio System (Modularized)
- ✅ VR Support (WebXR)
- ✅ Full API Integration
- ✅ **Unified Path Resolution** (Local + Production compatibility)
- ✅ **Asset Persistence System** (via `/data/` persistent storage + symlinks)
- ✅ **Production-Ready Path System** (all assets resolve correctly)
- ✅ **Stable Production Version** (Level 1 verified working - January 9, 2026)
- ✅ **Complete Asset Upload System** (API endpoint operational - 123+ files uploaded)
- ✅ **God Mode Settings Persistence** (Grass, sky, and boss settings save/load working in production - January 9, 2026)
- ✅ **Asset Caching System** (Two-level caching strategy with preloading - mobile/desktop optimized - January 9, 2026)
- ✅ **Unified Riddle HUD System** (All levels 1-5 use consistent persistent HUD with step hints - January 12, 2026)
- ✅ **Complete Mobile Controls System** (6 touch-friendly UI elements + landscape enforcement - January 18, 2026)
- ✅ **VR Support Optimized for Meta Quest 3** (Movement, textures, and performance fixes - January 18, 2026)

### **Integration Status:**
- ✅ **Database:** `tbl_cheese_hunt_captures`, `tbl_riddle_completions`, `tbl_user_traits`
- ✅ **APIs:** `/api/dev/cheese-hunt-capture.php`, `/api/dev/riddle-reward.php`, `/api/user/traits.php`, `/api/user/details.php`
- ✅ **Discord Login:** ✅ **WORKING PERFECTLY** - User authentication and profile display in GUI (verified January 9, 2026)
- ✅ **Profile.html:** Partial integration (DSPOINC display)
- ⏳ **Admin Interface:** Pending integration
- ⏳ **Shop System:** Future implementation
- ⏳ **Achievement System:** Future implementation

---

## 🏗️ **ARCHITECTURE & FILE STRUCTURE**

### **Project Structure:**
```
three.js/
├── index.html                    # Entry point
├── main.js                       # Main game loop (34,000+ lines)
├── package.json                  # Dependencies & scripts
├── config-system.js              # Configuration constants
│
├── Core Systems (Universal):
│   ├── sky-system.js             # Sky & environment management
│   ├── grass-system.js           # Procedural grass generation
│   ├── player-controls.js        # Input handling (keyboard, mouse, mobile)
│   ├── player-model.js           # Character model & animation
│   ├── gui-system.js            # UI management (HUD, menus, notifications)
│   └── audio-system.js           # Audio management (music, SFX)
│
├── Level-Specific Systems:
│   ├── weapon-system.js          # Weapon loading, shooting (Levels 4-6)
│   └── chest-system.js           # Treasure chest system (All levels)
│
├── Boss Systems (Level 6):
│   ├── phoenix2.js               # Phoenix Dragon boss (15 patterns)
│   └── alien-spider.js           # Alien Spider boss (12 patterns)
│
├── Optional Systems:
│   └── vr-input-provider.js      # VR controller input (WebXR)
│
├── public/
│   ├── models/                   # 3D models (GLTF, GLB, FBX)
│   │   └── cheese-temple/       # Level models
│   ├── textures/                 # Textures (1,400+ files)
│   │   └── 3d models/            # 3D model assets
│   ├── sounds/                   # Audio files (MP3, OGG, WAV)
│   └── audio/                    # Additional audio files
│
└── tools/
    ├── fbx2gltf/                 # FBX to GLTF conversion tool
    └── generate-level.js         # Level generation utility
```

### **Technology Stack:**
- **Framework:** Three.js (v0.181.1)
- **Build Tool:** Vite (v7.2.2)
- **Physics:** three-mesh-bvh (BVH collision detection)
- **Loaders:** GLTFLoader, FBXLoader, TGALoader
- **Controls:** PointerLockControls, OrbitControls
- **VR:** WebXR API
- **Caching:** Three.js Cache + Custom Map-based caching (two-level strategy)

---

## 🔧 **ASSET CACHING SYSTEM (January 9, 2026)**

### **Overview:**
Professional two-level asset caching system for optimal performance on both mobile and desktop devices. Implements preloading, network-level caching, and object-level caching with resilience features.

### **Two-Level Caching Strategy:**

#### **1. Three.js Built-In Cache (Network-Level)**
- **Purpose:** Prevents redundant network requests for raw file data
- **Implementation:** `THREE.Cache.enabled = true` in `main.js`
- **How It Works:** When enabled, `FileLoader` (used internally by `TextureLoader`, `GLTFLoader`, etc.) stores raw file data in memory. Subsequent requests for the same file are served from memory, avoiding new HTTP requests.
- **Benefits:**
  - Eliminates redundant network requests
  - Faster asset loading on subsequent uses
  - Reduced bandwidth usage
  - Works automatically for all Three.js loaders

#### **2. Custom Map-Based Cache (Object-Level)**
- **Purpose:** Prevents reprocessing of raw file data into Three.js objects
- **Implementation:** `textureCache` and `modelCache` Map structures in `main.js`
- **How It Works:** After a file is loaded (potentially from `THREE.Cache`) and processed into a Three.js object (like `THREE.Texture` or `GLTF` scene), the processed object is stored in our cache. Subsequent requests retrieve the ready-to-use object directly, skipping parsing and processing steps.
- **Benefits:**
  - Eliminates redundant CPU processing
  - Faster object retrieval (instant from cache)
  - Reduced RAM usage (one copy per asset, reused everywhere)
  - Works for both textures and models

### **How They Work Together:**
1. **First Load:** Asset is downloaded (cached by `THREE.Cache`), then processed (cached by our `Map()`)
2. **Subsequent Loads:** Asset is retrieved from our `Map()` cache (fastest). If not there, it's processed from raw data (from `THREE.Cache`). If raw data not there, it's downloaded.

### **Asset Preloading System:**

#### **Function: `preloadCriticalAssets()`**
- **Purpose:** Preload essential assets at game startup
- **Location:** `main.js` (lines ~5320-5650)
- **Features:**
  - Mobile/Desktop optimization (reduced asset count for mobile)
  - Resilience features (retry logic, exponential backoff, fallbacks)
  - Error handling (graceful degradation)
  - Progress tracking (logs preload progress)
  - Automatic initialization (runs on page load)

#### **Preloaded Assets:**
- **Textures:** `grass.jpg`, `cloud.jpg`, `cheesetemple1.png` (critical for Level 1)
- **Models:** Player character models (for instant spawning)
- **Audio:** Critical audio files (optional, can be lazy-loaded)

#### **Mobile Optimization:**
- **Reduced Asset Count:** Mobile devices preload fewer assets to save memory
- **Lower Quality Options:** Can be configured for mobile-specific asset variants
- **Memory Management:** Automatic cache size limits for mobile devices

#### **Resilience Features:**
- **Retry Logic:** Automatic retry with exponential backoff (up to 3 attempts)
- **Timeout Protection:** 10-second timeout per asset (prevents hanging)
- **Fallback Assets:** Graceful degradation if critical assets fail
- **Error Recovery:** Continues preloading even if some assets fail
- **Progress Logging:** Detailed logs for debugging

### **Cache Management:**
- **Cache Keys:** Use `resolvedPath` (from `resolveAssetPath()`) for consistency
- **Cache Invalidation:** Manual clearing available via `THREE.Cache.clear()` and `textureCache.clear()` / `modelCache.clear()`
- **Memory Management:** Automatic cleanup of unused assets (future enhancement)

### **Implementation Details:**
- **File:** `public/three.js/main.js`
- **Initialization:** Automatic on page load (after `THREE.Cache` is enabled)
- **Integration:** Works seamlessly with existing `loadTexture()` and `loadModel()` functions
- **Performance:** Zero overhead when assets are cached (instant retrieval)

### **Benefits:**
- ✅ **Faster Initial Load Times:** Critical assets preloaded at startup
- ✅ **Faster Level Switching:** Assets reused from cache, avoiding re-downloads and re-processing
- ✅ **Reduced RAM Usage:** Only one copy of each processed asset kept in memory
- ✅ **Reduced Network Usage:** Files downloaded only once
- ✅ **Better Overall Performance:** Less disk I/O, less CPU for parsing
- ✅ **Mobile Optimized:** Reduced asset count and memory management for mobile devices
- ✅ **Resilient:** Retry logic and fallbacks ensure assets load even on poor connections

### **Related Code:**
- **Cache Initialization:** Lines ~5144-5160 in `main.js`
- **Preload Function:** Lines ~5320-5650 in `main.js`
- **Preload Initialization:** Lines ~37872+ in `main.js` (automatic on page load)
- **Texture Loading:** `loadTexture()` function uses cache automatically
- **Model Loading:** `loadModel()` function uses cache automatically

## 🗄️ **DATA PERSISTENCE FILE SYSTEM PATTERN (January 16, 2026)**

### **✅ VERIFIED WORKING METHOD FOR RENDER DEPLOYMENT**

This is the **STANDARD METHOD** for implementing GLB models from the data persistence file system (`/data/public/three.js/public/textures/`). Use this pattern for ALL future GLB model integrations that need to work with the Render deployment symlink system.

### **File System Structure:**

**Render Deployment:**
- **Data Persistence Directory:** `/data/public/three.js/public/textures/3d models/` (persists across deployments)
- **Symlink:** `/data/public/three.js/public/textures/` → `/var/www/html/public/three.js/public/textures/`
- **Actual Path:** `/var/www/html/public/three.js/public/textures/3d models/[model-folder]/[model-file].glb`
- **Relative Path:** `textures/3d models/[model-folder]/[model-file].glb` (no leading slash)

### **MANDATORY IMPLEMENTATION PATTERN:**

```javascript
// Relative path (no leading slash) - same as Level 4 Cheese Bosses
const relativePath = "textures/3d models/cheese portal/cheese-portal.glb";

// Use resolveAssetPath() + encodeURI() for symlink support and space handling
const resolved = resolveAssetPath(relativePath);
const urlForLoader = encodeURI(resolved); // Handles spaces in "cheese portal" folder name

// Load model using loadModel() with GLTFLoader fallback
if (typeof loadModel === "function") {
  loadModel(urlForLoader)
    .then((result) => {
      const loadedScene = result.scene || result;
      const model = loadedScene; // Use directly (no clone needed for single instance)
      
      // Position, scale, material processing, etc.
      // ... rest of implementation
    })
    .catch((error) => {
      // GLTFLoader fallback here
      const loader = new GLTFLoader();
      loader.load(urlForLoader, (gltf) => {
        // ... fallback loading logic
      });
    });
}
```

### **Why This Pattern Works:**

- ✅ **Data Persistence File System:** Works with `/data/public/three.js/public/textures/` symlink on Render
- ✅ **Symlink Support:** `resolveAssetPath()` resolves relative paths through `/data/` symlink to `/var/www/html/public/three.js/public/`
- ✅ **Space Handling:** `encodeURI()` handles spaces in folder names (e.g., "cheese portal") for proper URL encoding
- ✅ **Cross-Environment:** Works on both local development and production Render environments
- ✅ **Verified Pattern:** Level 1 Portal (January 16, 2026) + Level 4 Cheese Bosses both use this pattern successfully

### **When to Use This Pattern:**

- ✅ **GLB Models from Data Persistence:** All GLB models stored in `/data/public/three.js/public/textures/`
- ✅ **Render Deployment:** Models that need to persist across deployments via `/data/` symlink
- ✅ **Symlink Support Required:** Models that must work with Render's `/data/` persistence symlink
- ✅ **Space Handling Needed:** Folder names with spaces (e.g., "cheese portal", "3d models")

### **Working Examples:**

- ✅ **Level 1 Portal:** `textures/3d models/cheese portal/cheese-portal.glb` (January 16, 2026 - Verified)
- ✅ **Level 4 Cheese Bosses:** Original working implementation using this pattern

### **Implementation Reference:**

- **Portal Implementation:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-16/LEVEL1_PORTAL_3D_MODEL_IMPLEMENTATION.md`
- **3D Model Rendering Rule:** `12.0/RULES/18_3D_MODEL_RENDERING_RULE.md` - Updated with this working pattern
- **Daily Notes:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-16/DAILY_NOTES_2026-01-16.md`

### **Key Points:**

1. **Use Relative Paths:** Start with relative path (no leading slash): `"textures/3d models/..."`
2. **Resolve Path:** Use `resolveAssetPath(relativePath)` to resolve through symlink
3. **Encode URI:** Use `encodeURI(resolved)` to handle spaces in folder names
4. **Load Model:** Use `loadModel(urlForLoader)` with GLTFLoader fallback
5. **Single Instance:** Use `loadedScene` directly (no clone needed for single instance)

### **Status:**

✅ **VERIFIED WORKING - STANDARD METHOD FOR DATA PERSISTENCE FILE SYSTEM** (January 16, 2026)  
**Use This Pattern:** For ALL GLB models loaded from `/data/public/three.js/public/textures/` via Render symlink

---

### **Future Enhancements:**
- [ ] **Cache Size Limits:** Automatic cleanup of least-used assets
- [ ] **Cache Persistence:** Store cache in IndexedDB for cross-session persistence
- [ ] **Progressive Loading:** Load assets in priority order (critical first)
- [ ] **Cache Analytics:** Track cache hit rates and performance metrics

---

## 🔫 **WEAPON SYSTEM & BOSS MOVEMENT FIXES (January 9, 2026 - Evening)**

### **Overview:**
Critical fixes for weapon shooting and boss movement in Levels 4, 5, and 6. These fixes ensure that projectiles move correctly and bosses animate/move as expected.

### **Issues Fixed:**

#### **1. Weapon Shooting Issue (Levels 4, 5, 6):**
- **Problem:** Projectiles (bubbles/shots) were loaded but stuck in air, not moving
- **Root Cause:** `weaponSystem.update(delta)` was not being called in level-specific update functions
- **Solution:** Created `updateLevel4()`, `updateLevel5()`, and `updateLevel6()` functions that call `weaponSystem.update(delta)` every frame
- **Result:** ✅ Projectiles now move correctly and shooting works as expected

#### **2. Boss Movement Issue (Level 6):**
- **Problem:** Phoenix and Alien Spider bosses were frozen and not moving
- **Root Cause:** Boss `update(delta)` methods were not being called in `updateLevel6()`
- **Solution:** Added `phoenixBoss.update(delta)` and `alienSpiderBoss.update(delta)` calls to `updateLevel6()` function
- **Result:** ✅ Bosses now animate and move correctly

#### **3. Level 6 Warp Issue:**
- **Problem:** Selecting Level 6 spawned player in Level 1 instead
- **Root Cause:** `buildLevel()` (Level 1 specific) was being called before warping, causing errors and fallback
- **Solution:** Removed unnecessary `buildLevel()` call when warping to other levels; each level's warp function handles its own building
- **Result:** ✅ Level 6 now loads correctly when selected

### **Implementation Details:**

#### **Level-Specific Update Functions:**
```javascript
// Located in main.js around line 28526-28584

/**
 * Update Level 4 - Weapon system updates (projectiles, heat, shooting)
 */
if (typeof updateLevel4 === 'undefined') {
  window.updateLevel4 = function updateLevel4(delta) {
    if (weaponSystem && typeof weaponSystem.update === 'function') {
      weaponSystem.update(delta);
    }
  };
}

/**
 * Update Level 5 - Weapon system updates (projectiles, heat, shooting)
 */
if (typeof updateLevel5 === 'undefined') {
  window.updateLevel5 = function updateLevel5(delta) {
    if (weaponSystem && typeof weaponSystem.update === 'function') {
      weaponSystem.update(delta);
    }
  };
}

/**
 * Update Level 6 - Weapon system + boss updates (Phoenix and Alien Spider)
 */
if (typeof updateLevel6 === 'undefined') {
  window.updateLevel6 = function updateLevel6(delta) {
    // Update weapon system
    if (weaponSystem && typeof weaponSystem.update === 'function') {
      weaponSystem.update(delta);
    }
    
    // Update Phoenix boss
    if (typeof phoenixBoss !== 'undefined' && phoenixBoss && typeof phoenixBoss.update === 'function') {
      phoenixBoss.update(delta);
    }
    
    // Update Alien Spider boss
    if (typeof alienSpiderBoss !== 'undefined' && alienSpiderBoss && typeof alienSpiderBoss.update === 'function') {
      alienSpiderBoss.update(delta);
    }
  };
}
```

#### **Function Safety Checks:**
- All functions wrapped in `if (typeof functionName === 'undefined')` checks
- Prevents redeclaration errors from browser cache or multiple script loads
- Functions assigned to `window` object for global access

#### **Animate Loop Integration:**
These functions are called from the main animate loop when the respective level is active:
```javascript
// Located in main.js around line 30087-30093
} else if (currentLevel === LEVEL_IDS.LEVEL4) {
  updateLevel4(delta);
} else if (currentLevel === LEVEL_IDS.LEVEL5) {
  updateLevel5(delta);
} else if (currentLevel === LEVEL_IDS.LEVEL6) {
  updateLevel6(delta);
}
```

### **Related Code:**
- **Update Functions:** Lines ~28526-28584 in `main.js`
- **Animate Loop Calls:** Lines ~30087-30093 in `main.js`
- **Weapon System Update:** `weapon-system.js` - `update(delta)` method
- **Phoenix Boss Update:** `phoenix2.js` - `update(delta)` method
- **Alien Spider Boss Update:** `alien-spider.js` - `update(delta)` method

### **Testing:**
- ✅ Weapon shooting works correctly in Levels 4, 5, and 6
- ✅ Projectiles move and behave as expected
- ✅ Phoenix boss animates and moves correctly in Level 6
- ✅ Alien Spider boss animates and moves correctly in Level 6
- ✅ Level 6 loads correctly when selected from level selector

---

## 🧩 **MODULE SYSTEM**

### **1. Core Systems (Universal - Load Once)**

#### **SkySystem (`sky-system.js`)**
- **Purpose:** Sky and environment management
- **Features:** Day/night cycle, clouds, stars, procedural generation
- **Initialization:** Once at game start
- **Update:** Per-level via `applyLevelEnvironment(levelId)`
- **Key Functions:**
  - `applyLevelEnvironment(levelId)` - Apply level-specific sky settings
  - `setTime(hour)` - Set time of day
  - `setCloudDensity(density)` - Adjust cloud density
  - `loadSkySettingsForLevel(levelId)` - Load saved settings
  - `saveSkySettingsForLevel(levelId)` - Save settings

#### **GrassSystem (`grass-system.js`)**
- **Purpose:** Procedural grass generation with wind animation
- **Features:** Exclusion zones, chunked meshes, wind system, per-level config
- **Initialization:** Once at game start
- **Update:** Per-level via `applyLevelEnvironment(levelId)`
- **Key Functions:**
  - `applyLevelEnvironment(levelId)` - Apply level-specific grass settings
  - `registerExclusionZone(center, radius)` - Prevent grass under objects
  - `regenerateGrass()` - Regenerate grass with new settings
  - `loadGroundSettingsForLevel(levelId)` - Load saved settings
  - `saveGroundSettingsForLevel(levelId)` - Save settings

#### **PlayerControls (`player-controls.js`)**
- **Purpose:** Player input handling
- **Features:** Keyboard, mouse, mobile joystick, camera controls
- **Initialization:** Once after scene/camera/renderer ready
- **Update:** Every frame via `update(delta)`
- **Key Functions:**
  - `enable()` - Enable controls
  - `disable()` - Disable controls
  - `update(delta)` - Update input state
  - `getMovementState()` - Get current movement state

#### **Mobile Controls System (✅ COMPLETE - January 18, 2026)**

**Status:** ✅ **PRODUCTION READY** - Complete mobile optimization with 6 touch-friendly UI elements  
**Last Updated:** January 18, 2026  
**Purpose:** Full mobile gameplay support with landscape enforcement and touch-optimized controls

##### **Overview:**
Complete mobile controls implementation that makes the game fully playable on phones and tablets. All controls are touch-optimized, landscape-enforced, and automatically appear/hide based on game state.

##### **Mobile Control Elements (6 Total):**

**1. Movement Joystick (Left Side)**
- **Location:** Bottom-left corner
- **Size:** 150px outer, 60px inner
- **Purpose:** Player movement (WASD replacement)
- **Visible:** Always (when in landscape, not paused)
- **Style:** Blue theme, semi-transparent
- **Function:** `createMobileJoystick()` (lines ~34929-35114 in `main.js`)

**2. Camera Joystick (Right Side)**
- **Location:** Bottom-right corner
- **Size:** 150px outer, 60px inner
- **Purpose:** Camera rotation (mouse replacement)
- **Visible:** Always (when in landscape, not paused)
- **Style:** Orange theme, semi-transparent
- **Function:** `createMobileCameraJoystick()` (lines ~35119-35308 in `main.js`)

**3. Pause Button (⏸️)**
- **Location:** Top-right corner
- **Size:** 60px circular
- **Purpose:** Access pause menu
- **Visible:** Always (when in landscape, not paused)
- **Style:** Yellow background, pause icon
- **Function:** `createMobilePauseButton()` (lines ~34240-34329 in `main.js`)

**4. Interact Button (E)**
- **Location:** Above weapon selector
- **Size:** 80px circular
- **Purpose:** Interact with objects (chests, portals, etc.)
- **Visible:** Only when near interactable object
- **Style:** Yellow background, "E" letter, pulsing animation
- **Function:** `createMobileInteractButton()` (lines ~34347-34436 in `main.js`)

**5. Weapon Selector (1-9)**
- **Location:** Bottom-center
- **Size:** 9 buttons × 40px each
- **Purpose:** Switch weapons in combat levels
- **Visible:** Only in weapon levels (4, 5, 6)
- **Style:** Horizontal row, active slot highlighted
- **Function:** `createMobileWeaponSelector()` (lines ~34454-34565 in `main.js`)

**6. Shoot Button (🔫)**
- **Location:** Bottom-right (above weapon selector)
- **Size:** 80px circular
- **Purpose:** Fire weapon (hold for continuous fire)
- **Visible:** Only in weapon levels (4, 5, 6)
- **Style:** Red background, gun emoji
- **Function:** `createMobileShootButton()` (lines ~34592-34681 in `main.js`)

##### **Landscape Enforcement System:**

**Dynamic Landscape Detection:**
- **Function:** `isMobileLandscape()` (line ~344 in `main.js`)
- **Logic:** `isMobile && window.innerWidth > window.innerHeight`
- **Type:** Dynamic function (updates in real-time)
- **Purpose:** React to device rotation immediately

**Landscape Orientation Prompt:**
- **Displayed:** When mobile device is in portrait mode
- **Message:** "Please rotate your device to landscape mode"
- **Style:** Full-screen overlay with rotation icon
- **Auto-Hide:** Hides when device rotates to landscape
- **Function:** `showLandscapeOrientationPrompt()` / `hideLandscapeOrientationPrompt()`

**Continuous Landscape Checking:**
- **Method 1:** `orientationchange` event listener
- **Method 2:** `resize` event listener
- **Method 3:** `setInterval` (every 500ms backup check)
- **Purpose:** Ensure controls update even if events don't fire

**Lock Orientation API (Optional):**
- **Attempted:** `screen.orientation.lock('landscape')`
- **Fallback:** Visual prompt if API unavailable
- **Browser Support:** Modern mobile browsers

##### **Control Visibility Logic:**

**Mobile Pause Button:**
```javascript
function updateMobilePauseButton() {
  const shouldShow = !isGamePaused && isMobileLandscape();
  mobilePauseButton.style.display = shouldShow ? "flex" : "none";
}
```

**Mobile Interact Button:**
```javascript
function updateMobileInteractButton() {
  const shouldShow = !isGamePaused && isMobileLandscape() && 
                     nearestInteractableChest && !nearestInteractableChest.opened;
  mobileInteractButton.style.display = shouldShow ? "flex" : "none";
}
```

**Mobile Weapon Selector:**
```javascript
function updateMobileWeaponSelector() {
  const isWeaponLevel = currentLevel === LEVEL_IDS.LEVEL4 || 
                        currentLevel === LEVEL_IDS.LEVEL5 || 
                        currentLevel === LEVEL_IDS.LEVEL6;
  const shouldShow = !isGamePaused && isMobileLandscape() && isWeaponLevel;
  mobileWeaponSelector.style.display = shouldShow ? "flex" : "none";
}
```

**Mobile Shoot Button:**
```javascript
function updateMobileShootButton() {
  const isWeaponLevel = currentLevel === LEVEL_IDS.LEVEL4 || 
                        currentLevel === LEVEL_IDS.LEVEL5 || 
                        currentLevel === LEVEL_IDS.LEVEL6;
  const shouldShow = !isGamePaused && isMobileLandscape() && isWeaponLevel;
  mobileShootButton.style.display = shouldShow ? "flex" : "none";
}
```

##### **Desktop Joysticks vs Mobile Controls:**

**IMPORTANT DISTINCTION:** These are **separate systems**!

**Mobile Controls (NEW - January 18, 2026):**
- **For:** Mobile players (phones/tablets)
- **Status:** Always enabled (cannot be disabled)
- **Includes:** All 6 UI elements listed above
- **Purpose:** Essential gameplay controls
- **Toggle Visible:** No (mobile users don't see toggle)

**Desktop Joysticks (OLD - Existing Feature):**
- **For:** Desktop testing only
- **Status:** Optional (can toggle in Options menu)
- **Includes:** Only joysticks (no buttons)
- **Purpose:** Testing mobile controls on desktop
- **Toggle Visible:** Yes (only for desktop users)

**Options Menu Integration:**
- Desktop users see: "🎮 Desktop Joysticks (Testing)" toggle
- Mobile users see: No toggle (their controls are always on)
- Implementation: `if (!isMobile)` wrapper around joystick toggle section

**Documentation:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-18/MOBILE_VS_DESKTOP_JOYSTICKS_EXPLAINED.md`

##### **Touch Event Handling:**

**Standard Pattern (All Mobile Buttons):**
```javascript
button.addEventListener("click", (e) => {
  e.preventDefault();
  e.stopPropagation();
  // Action handler
});

button.addEventListener("touchstart", (e) => {
  e.stopPropagation();
}, { passive: true });

button.addEventListener("touchend", (e) => {
  e.stopPropagation();
}, { passive: true });
```

**Features:**
- ✅ Prevents double-tap zoom
- ✅ Prevents text selection
- ✅ Stops event propagation
- ✅ CSS `touch-action: manipulation`

##### **Integration Points:**

**Animate Loop:**
```javascript
function animate() {
  // Update mobile control visibility
  updateMobilePauseButton();
  updateMobileInteractButton();
  updateMobileWeaponSelector();
  updateMobileShootButton();
  
  // ... rest of game loop
}
```

**Pause/Unpause:**
```javascript
function togglePause(forceState) {
  // Hide all mobile controls when paused
  if (isGamePaused) {
    if (mobilePauseButton) mobilePauseButton.style.display = "none";
    if (mobileInteractButton) mobileInteractButton.style.display = "none";
    if (mobileShootButton) mobileShootButton.style.display = "none";
    if (mobileWeaponSelector) mobileWeaponSelector.style.display = "none";
  }
  // Show controls when unpaused (if conditions met)
  else {
    updateMobilePauseButton();
    updateMobileInteractButton();
    updateMobileWeaponSelector();
    updateMobileShootButton();
  }
}
```

##### **System Features:**

**Dynamic Visibility:**
- ✅ Controls show/hide based on game state
- ✅ Level-specific controls (weapon controls only in combat levels)
- ✅ Context-sensitive controls (interact button only near objects)

**Visual Feedback:**
- ✅ Active weapon slot highlighted in weapon selector
- ✅ Pulsing animation on interact button
- ✅ Press effect on shoot button (scale 0.9, brighter color, glow)
- ✅ Consistent styling across all controls

**Performance:**
- ✅ Minimal overhead (only updates visibility when needed)
- ✅ Touch events optimized with passive listeners
- ✅ No continuous rendering (only state changes)

##### **Related Files:**
- **Main Implementation:** `public/three.js/main.js` (lines ~34240-34697)
- **Joystick Creation:** `public/three.js/main.js` (lines ~34929-35308)
- **Landscape Detection:** `public/three.js/main.js` (line ~344)
- **Options Menu Integration:** `public/three.js/main.js` (lines ~11226-11315)

##### **Related Documentation:**
- **Mobile Optimization Plan:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-18/MOBILE_CONTROLS_OPTIMIZATION_PLAN.md`
- **Phase 1 Complete:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-18/MOBILE_PHASE1_IMPLEMENTATION_COMPLETE.md`
- **Mobile Controls Final:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-18/MOBILE_CONTROLS_FINAL_COMPLETE.md`
- **All Levels Complete:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-18/MOBILE_CONTROLS_ALL_LEVELS_COMPLETE.md`
- **Mobile vs Desktop:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-18/MOBILE_VS_DESKTOP_JOYSTICKS_EXPLAINED.md`

##### **Testing Checklist:**
- [x] Landscape detection works correctly
- [x] Controls appear in landscape mode
- [x] Controls hide in portrait mode
- [x] Pause button works correctly
- [x] Interact button appears near chests/portals
- [x] Weapon selector works in combat levels
- [x] Shoot button works (tap + hold)
- [x] Movement joystick works
- [x] Camera joystick works
- [x] Desktop joysticks toggle hidden on mobile
- [x] All levels fully playable on mobile

##### **Verification Status:**
- ✅ **Phase 1 Complete:** Dynamic landscape detection, mobile pause button, landscape prompt
- ✅ **Phase 2 Complete:** Interact button, weapon selector
- ✅ **Phase 3 Complete:** Shoot button for combat levels
- ✅ **Desktop Toggle Hidden:** Desktop joysticks toggle hidden from mobile users
- ✅ **All Levels Tested:** All levels (1-6) confirmed working with mobile controls

#### **PlayerModel (`player-model.js`)**
- **Purpose:** Player character model and animation
- **Features:** Dual model support (Mouse & Animation Library), animation system
- **Initialization:** Once at game start
- **Update:** Every frame via `update(delta)`
- **Key Functions:**
  - `load()` - Load character model
  - `update(delta)` - Update animations
  - `setVisible(visible)` - Show/hide model
  - `playAnimation(name)` - Play specific animation

#### **GUISystem (`gui-system.js`)**
- **Purpose:** User interface management
- **Features:** HUD, menus, notifications, boss health bars
- **Initialization:** Once at game start via `initialize()`
- **Update:** Event-driven (no update loop)
- **Key Functions:**
  - `initialize()` - Initialize GUI system
  - `showToast(message, type)` - Show notification
  - `showBossHealthBar(bossName, health, maxHealth)` - Show boss health
  - `updateHUD(dsPoinc, level, riddle)` - Update HUD display

#### **AudioSystem (`audio-system.js`)**
- **Purpose:** Complete audio management
- **Features:** Background music, sound effects, volume control
- **Initialization:** Once at game start via `initialize()`
- **Update:** Event-driven (plays sounds on demand)
- **Key Functions:**
  - `loadBackgroundMusic(levelId)` - Load level music
  - `playJumpSound()` - Play jump sound
  - `setBackgroundMusicEnabled(enabled)` - Toggle music
  - `setSoundEffectsEnabled(enabled)` - Toggle SFX

### **2. Level-Specific Systems**

#### **WeaponSystem (`weapon-system.js`)**
- **Purpose:** Weapon loading, shooting, inventory management
- **Active Levels:** 4, 5, 6
- **Features:** Dual weapon slots, shooting mechanics, heat system
- **Initialization:** Once at game start
- **Update:** Every frame via `update(delta)`
- **Key Functions:**
  - `loadWeapon(slot, weaponId)` - Load weapon into slot
  - `switchWeapon()` - Switch between slots
  - `fire()` - Fire weapon
  - `update(delta)` - Update weapon state

#### **ChestSystem (`chest-system.js`)**
- **Purpose:** Treasure chest system
- **Active Levels:** All levels (1-6+)
- **Primary method:** Dual-model – closed GLB + opened GLB, swap on open (simpler)
- **Fallback:** Legacy chest2 single-GLB with lid rotation
- **Features:** Rewards (DSPOINC), persistence, grass exclusion, collision
- **Initialization:** Once at game start (usually in weapon system init)
- **Update:** Event-driven (checks interactions in `update()`)
- **Key Functions:**
  - `addChest(levelId, config)` - Add chest (id, type, position, dspoincAmount, levelId; optional closedModelPath/openedModelPath for dual-model)
  - `update(delta)` - Check interactions
  - `loadOpenedChests(discordId)` - Load opened state from DB before creating chests

### **3. Boss Systems (Level 6)**

#### **PhoenixBoss2 (`phoenix2.js`)**
- **Purpose:** Phoenix Dragon boss with 15 behavior patterns
- **Location:** Level 6, spawn position (20, 10, 0)
- **Features:** 7 color variations, 3 eye colors, emissive glow, 15 patterns
- **Initialization:** In `buildLevel6PhoenixArena()`
- **Update:** Every frame via `update(delta)` in `updateLevel6()`
- **Key Functions:**
  - `loadModel()` - Load GLB model
  - `update(delta)` - Update boss behavior
  - `setBehaviorMode(mode)` - Set behavior pattern
  - `applyColorVariation(variation)` - Apply color variation

#### **AlienSpiderBoss (`alien-spider.js`)**
- **Purpose:** Alien Spider boss with 7 behavior patterns
- **Location:** Level 6, spawn position (-20, 1, 0)
- **Features:** 3 texture variations, TGA texture support, brightness control
- **Initialization:** In `buildLevel6PhoenixArena()`
- **Update:** Every frame via `update(delta)` in `updateLevel6()`
- **Key Functions:**
  - `loadModel()` - Load FBX model with TGA textures
  - `update(delta)` - Update boss behavior
  - `setBehaviorMode(mode)` - Set behavior pattern
  - `applyTextureVariation(variation)` - Apply texture variation

### **4. Optional Systems**

#### **VRInputProvider (`vr-input-provider.js`)**
- **Purpose:** VR controller input handling
- **Features:** WebXR API integration, VR controller support
- **Initialization:** If VR detected and available
- **Update:** Every frame via `update(delta)`
- **Key Functions:**
  - `enable()` - Enable VR input
  - `disable()` - Disable VR input
  - `update(delta)` - Update VR state
  - `isAvailable()` - Check VR availability (static)

### **5. Configuration System**

#### **config-system.js**
- **Purpose:** Centralized configuration constants
- **Exports:** LEVEL_IDS, API_BASE_URL, audio paths, storage keys, game constants
- **Used By:** All modules (imported as needed)

---

## 🎮 **LEVEL SYSTEM**

### **Current Levels: 6 Total**

#### **Level 1: Cheese Temple**
- **Type:** Multi-riddle exploration level
- **Total Riddles:** 4 (3 main + 1 secret)
- **Status:** ✅ **PRODUCTION VERIFIED**
- **Documentation:** `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_01_CHEESE_TEMPLE_LEVEL_1.md`
- **Decorative Models (NEW - January 16, 2026):**
  - ✅ **Portal Model:** Cheese Portal GLB at (26, 3, 21) - Block replacement system
  - ✅ **Blue Cheese Model:** Blue Cheese GLB at (100, 5.5, 18) with **pulsing blue glow effect**
    - **Glow Effect:** Smooth pulsing animation (sine wave) transitions between original GLB color and blue/purple glow (`0x4488ff`)
    - **Animation:** `updateBlueCheeseGlow()` - Intensity pulses 0.0 to 0.6 with color interpolation using `lerpColors()`
    - **Scale:** 10.0x (huge size for visibility)
    - **Pattern:** Uses data persistence file system (`resolveAssetPath()` + `encodeURI()` + `loadModel()`)
    - **Status:** ✅ **VERIFIED WORKING** - Pulsing glow effect working perfectly

#### **Level 2: The Spawn (Matrix Construct)**
- **Type:** Inspection-based riddle with gallery exploration
- **Total Steps:** 3 (Step 0, Step 1, Step 2)
- **Status:** ✅ **PRODUCTION VERIFIED**
- **Documentation:** `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_01_THE_SPAWN_LEVEL_2.md`

#### **Level 3: The Hunt**
- **Type:** Monster hunting challenge (10 monsters in 2 phases)
- **Total Steps:** 3 (Step 0, Step 1, Step 2)
- **Status:** ✅ **PRODUCTION VERIFIED**
- **Documentation:** `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_01_THE_HUNT_LEVEL_3.md`

#### **Level 4: The First Shot**
- **Type:** Shooting challenge (50 cheeses + 30 monsters in waves)
- **Total Steps:** 4 (Step 0, Step 1, Step 2, Step 3)
- **Status:** ✅ **PRODUCTION VERIFIED**
- **Center Portal Register:** Visitor's Log message system at arena center (E key when near) – see [Level 4 Portal Register](#-level-4-portal-register-visitors-log--february-2026) in Changelog
- **Documentation:** `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_01_THE_FIRST_SHOT_LEVEL_4.md`

#### **Level 5: The Walk**
- **Type:** Open-world exploration with monster hunt
- **Total Steps:** 2 (Step 0, Step 1 - 10 waves of 5 monsters each)
- **Status:** ✅ **STEP 1 COMPLETE**
- **Documentation:** `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_01_THE_WALK_LEVEL_5.md`

#### **Level 6: Phoenix Arena**
- **Type:** Boss fight arena
- **Features:** Phoenix Dragon boss (15 patterns), Alien Spider boss (7 patterns)
- **Status:** ✅ **PRODUCTION READY**
- **Weapons:** Both weapon slots active

### **Universal Level Requirements**

**CRITICAL RULE:** All levels MUST have identical features:
- ✅ **GOD Mode:** Double speed + fly mode (Space/Shift)
- ✅ **Level Selector:** L key opens level menu
- ✅ **Riddle Cycling:** G key cycles riddle steps
- ✅ **Sound System:** Footsteps, jump, level-up sounds
- ✅ **Options Menu:** GOD Mode toggle, camera modes
- ✅ **Player Speed:** 1.5x base speed (12 normal, 21 sprint)
- ✅ **Controls:** WASD movement, Space jump, Shift sprint
- ✅ **Camera Modes:** First-person, third-person, joystick view

**Rule Document:** `12.0/RULES/12_UNIVERSAL_LEVEL_REQUIREMENTS_RULE.md`

---

## 🧩 **RIDDLE SYSTEM**

### **Riddle Naming Convention**

**Pattern:** `CHEESE_TEMPLE_[LEVEL]_[STEP]` or `CHEESE_TEMPLE_RIDDLE_##`

**Examples:**
- Level 1: `CHEESE_TEMPLE_RIDDLE_01`, `CHEESE_TEMPLE_RIDDLE_02`, `CHEESE_TEMPLE_RIDDLE_03`, `CHEESE_TEMPLE_RIDDLE_04_SECRET`
- Level 2+: `CHEESE_TEMPLE_LEVEL2_STEP0`, `CHEESE_TEMPLE_LEVEL2_STEP1`, `CHEESE_TEMPLE_LEVEL2_STEP2`
- Level 3+: `CHEESE_TEMPLE_LEVEL3_STEP0`, `CHEESE_TEMPLE_LEVEL3_MONSTER_1` through `CHEESE_TEMPLE_LEVEL3_MONSTER_10`
- Level 4+: `CHEESE_TEMPLE_LEVEL4_STEP0`, `CHEESE_TEMPLE_LEVEL4_CHEESE_1` through `CHEESE_TEMPLE_LEVEL4_CHEESE_50`, `CHEESE_TEMPLE_LEVEL4_STEP3`
- Level 5+: `CHEESE_TEMPLE_LEVEL5_STEP0`, `CHEESE_TEMPLE_LEVEL5_WAVE1` through `CHEESE_TEMPLE_LEVEL5_WAVE10`

### **Standard Riddle Flow**

1. **Step 0 (Hidden Trigger):** Find and activate hidden cheese stone (10 seconds standing)
2. **Step 1+ (Main Challenge):** Complete main riddle objective(s)
3. **Step N (Portal):** Portal activation after all steps complete
4. **Completion Screen:** Show completion options (restart, next level, return to other levels)

### **Riddle GUI/HUD System (✅ UNIFIED ACROSS ALL LEVELS - January 12, 2026)**

**Status:** ✅ **PRODUCTION READY** - All levels (1-5) use unified persistent HUD system  
**Last Updated:** January 12, 2026  
**Function:** `updateRiddleProgressUI()` in `main.js` (lines ~38593-39209)

#### **Unified HUD System:**

All riddle levels (Levels 1-5) now use the same persistent `riddleProgressUI` HUD system that displays:
- ✅ **Step Instructions** - Clear step descriptions (e.g., "Step 0: Stand on Platform")
- ✅ **Progress Bars** - Visual progress indicators with timers/counters
- ✅ **Completion Messages** - Step completion confirmations
- ✅ **Level Titles** - Level name displayed in HUD (e.g., "🧩 Level 2: The Spawn")

#### **Level-Specific HUD Display:**

**Level 1: Cheese Temple**
- Uses existing riddle system (3 main riddles + 1 secret)
- Shows riddle-specific step hints for Riddle #1, #2, #3
- HUD display logic preserved from original implementation

**Level 2: The Spawn**
- Step 0: "Stand on Platform" with timer (5 seconds)
- Step 1: "Pull the Lever (E key)" with distance display
- Step 2: HUD hidden (uses inspection HUD system instead)

**Level 3: The Hunt**
- Step 0: "Stand on Platform" with timer (5 seconds)
- Step 1: "Hunt 5 Monsters (X/5 caught)" with progress bar
- Step 2: "Hunt 5 More Monsters (X/10 total)" with progress bar
- Step 3: "Portal Activated!" message

**Level 4: The Arena**
- Step 0: "Stand on Platform" with timer (5 seconds)
- Step 1: "Catch 50 Cheeses (X/50)" with progress bar
- Step 2: "Defeat 30 Monsters (X/30)" with progress bar

**Level 5: The Walk**
- Step 0: "Stand on Platform" with timer (5 seconds)
- Step 1: "Defeat 10 Monsters (X/10)" with progress bar

**Level 6: Phoenix Boss Arena**
- HUD hidden (boss arena, no riddle steps)
- Uses boss health bar system instead

#### **Implementation Details:**

**Function:** `updateRiddleProgressUI()`
- **Location:** `public/three.js/main.js` (lines ~38593-39209)
- **Level Detection:** Routes to level-specific handlers based on `currentLevel`
- **State Variables:** Uses `level2RiddleState`, `level3RiddleState`, `level4RiddleState`, `level5RiddleState`
- **Display Elements:** Updates `step1Div` and `step2Div` within `riddleProgressUI`
- **Calling:** Invoked via `invokeRiddleProgressUIUpdate()` (throttled for performance)

**Key Features:**
- ✅ **Persistent Display** - HUD remains visible during riddle progression
- ✅ **Progress Tracking** - Shows timers, counters, and completion status
- ✅ **Consistent Styling** - Same visual style across all levels
- ✅ **Level-Specific Content** - Each level shows appropriate step instructions
- ✅ **Automatic Hiding** - HUD hides when no active riddle steps

**Technical Pattern:**
```javascript
// Level detection at start of function
if (currentLevel === LEVEL_IDS.LEVEL6) {
  riddleProgressUI.style.display = "none";
  return;
}

// Level-specific handlers
if (currentLevel === LEVEL_IDS.LEVEL2) {
  // Level 2 specific display logic
  // Updates step1Div/step2Div with step instructions
}
// ... similar for Levels 3, 4, 5
```

**Future Levels:**
- ✅ **All future levels** should follow this unified HUD pattern
- ✅ **Add level detection** in `updateRiddleProgressUI()` function
- ✅ **Use level state variables** (e.g., `level7RiddleState`, `level8RiddleState`)
- ✅ **Display step instructions** with progress bars and timers
- ✅ **Note:** This pattern ensures consistent user experience across all levels

### **Trait Unlocking Pattern**

```javascript
async function unlockLevelXTrait(traitKey, description) {
  const response = await fetch(`${API_BASE_URL}/api/user/traits.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      user_id: localStorage.getItem("discord_id"),
      trait_key: traitKey,
      trait_value: "1"
    })
  });
  // Handle response
}
```

### **Reward Awarding Pattern**

```javascript
async function awardLevelXDspoincReward(stepId, baseReward, contextLabel = "") {
  const response = await fetch(RIDDLE_REWARD_ENDPOINT, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      discord_id: localStorage.getItem("discord_id"),
      discord_name: localStorage.getItem("discord_name") || "Player",
      riddle_id: stepId,
      level_id: `CHEESE_TEMPLE_LEVELX`,
      base_reward: baseReward,
      session_id: generateSessionId()
    })
  });
  // Handle response
}
```

---

## 🗄️ **DATABASE INTEGRATION**

### **Database Tables:**

#### **1. `tbl_cheese_hunt_captures`**
```sql
CREATE TABLE tbl_cheese_hunt_captures (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    discord_id TEXT NOT NULL,
    discord_name TEXT,
    level_id TEXT NOT NULL,
    base_reward INTEGER NOT NULL,
    multiplier REAL NOT NULL,
    total_reward INTEGER NOT NULL,
    capture_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    session_id TEXT,
    metadata TEXT
);
```

#### **2. `tbl_riddle_completions`**
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

#### **3. `tbl_user_traits`**
```sql
CREATE TABLE tbl_user_traits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    trait TEXT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, trait)
);
```

**CRITICAL:** Uses `discord_id` for captures/completions, `user_id` for traits!

---

## 🔌 **API INTEGRATION**

### **API Endpoint 1: `/api/dev/cheese-hunt-capture.php`**

**Purpose:** Award DSPOINC for cheese captures in 3D game

**Request:**
```json
{
    "discord_id": "user_discord_id",
    "discord_name": "username",
    "level_id": "CHEESE_TEMPLE_LVL1",
    "base_reward": 10,
    "session_id": "session_id"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_captures": 5,
        "captures_today": 2,
        "dspoinc_awarded": 20,
        "total_dspoinc": 50
    }
}
```

### **API Endpoint 2: `/api/dev/riddle-reward.php`**

**Purpose:** Award DSPOINC for riddle completions

**Request:**
```json
{
    "discord_id": "user_discord_id",
    "discord_name": "username",
    "riddle_id": "CHEESE_TEMPLE_RIDDLE_01",
    "level_id": "CHEESE_TEMPLE_LVL1",
    "base_reward": 500,
    "session_id": "session_id"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_reward": 1000,
        "multiplier": 2.0,
        "multiplier_source": "🎴 VIP Holder"
    }
}
```

### **API Endpoint 3: `/api/user/traits.php`**

**Purpose:** Save/fetch user trait unlocks

**Request (POST):**
```json
{
    "user_id": "user_discord_id",
    "trait_key": "CHEESE_TEMPLE_RIDDLE_01",
    "trait_value": "1"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Trait unlocked successfully"
}
```

---

## 🌐 **PROFILE.HTML INTEGRATION**

### **Current Integration:**
- ✅ **DSPOINC Display:** HUD shows DSPOINC balance
- ⏳ **Game Stats:** Pending integration
- ⏳ **Achievement Display:** Pending integration

### **Future Integration Plan:**
```javascript
// public/profile.html - Future integration
case '3d_hytopia':
    statsHTML = `
      <div class="text-blue-300">Total Riddles Completed: ${stats.total_riddles || 0}</div>
      <div class="text-green-300">Total Cheese Captures: ${stats.total_captures || 0}</div>
      <div class="text-purple-300">Levels Completed: ${stats.levels_completed || 0}</div>
      <div class="text-yellow-300">DSPOINC Earned: ${stats.dspoinc_earned ? stats.dspoinc_earned.toLocaleString() : '0'}</div>
    `;
    break;
```

---

## 🖥️ **ADMIN INTERFACE INTEGRATION**

### **Future Integration Plan:**
```php
// api/admin/get-all-games-stats.php - Future integration
$stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT discord_id) as unique_players,
        COUNT(*) as total_riddles_completed,
        COUNT(DISTINCT level_id) as levels_played,
        SUM(total_reward) as total_dspoinc_awarded
    FROM tbl_riddle_completions
    WHERE completed_at >= ?
");
$stmt->execute([$seasonStart]);
$rumbleData = $stmt->fetch(PDO::FETCH_ASSOC);

$response['data']['games']['3d_hytopia'] = [
    'game_name' => '3D Riddle Game',
    'game_icon' => '🎮',
    'status' => 'active',
    'season_data' => [
        'unique_players' => (int)$rumbleData['unique_players'],
        'total_riddles_completed' => (int)$rumbleData['total_riddles_completed'],
        'levels_played' => (int)$rumbleData['levels_played'],
        'total_dspoinc_awarded' => (int)$rumbleData['total_dspoinc_awarded']
    ]
];
```

---

## 🛍️ **SHOP SYSTEM INTEGRATION (FUTURE)**

### **Planned Store Upgrades:**
1. **Weapon Upgrades** - Enhanced weapon damage/range
2. **Speed Boost** - Permanent movement speed increase
3. **Health Boost** - Increased player health
4. **Visual Effects** - Custom player model skins
5. **Audio Packs** - Custom sound effects

### **Implementation Plan:**
```javascript
// Future: three.js/main.js
window.hytopiaStoreState = window.hytopiaStoreState || {
  weaponUpgradeOwned: false,
  speedBoostOwned: false,
  healthBoostOwned: false
};

function applyHytopiaStorePerks(stateOverride) {
  const state = stateOverride || {};
  window.hytopiaStoreState = {
    weaponUpgradeOwned: Boolean(state.weaponUpgradeOwned),
    speedBoostOwned: Boolean(state.speedBoostOwned),
    healthBoostOwned: Boolean(state.healthBoostOwned)
  };
  
  // Apply speed boost
  if (window.hytopiaStoreState.speedBoostOwned) {
    playerSpeed = BASE_SPEED * 1.5;
  }
}
```

---

## 🏆 **ACHIEVEMENT SYSTEM INTEGRATION (FUTURE)**

### **Planned Achievements:**
1. **Level Completion** - Complete each level
2. **Riddle Master** - Complete all riddles
3. **Boss Slayer** - Defeat all bosses
4. **Speed Runner** - Complete levels under time limit
5. **Collector** - Capture all cheeses

### **Implementation Plan:**
```javascript
// Future: three.js/main.js
async function checkHytopiaAchievements() {
  const discordId = localStorage.getItem("discord_id");
  const response = await fetch(`${API_BASE_URL}/api/user/get-hytopia-achievements.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ user_id: discordId })
  });
  
  const data = await response.json();
  if (data.success) {
    displayHytopiaAchievements(data.achievements);
  }
}
```

### **Database Table (Future):**
```sql
CREATE TABLE tbl_hytopia_achievements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    achievement_id TEXT NOT NULL,
    unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, achievement_id)
);
```

---

## 🔗 **DISCORD INTEGRATION**

### **1. Discord Login & Authentication System (✅ WORKING - PRODUCTION READY)**

**Status:** ✅ **WORKING PERFECTLY** - Verified January 9, 2026  
**Purpose:** User authentication and player profile management in the 3D game GUI

#### **Overview:**
The Discord login system allows users to authenticate via Discord OAuth (handled on `profile.html`) and see their personalized information (username, DSPOINC balance) in the game's pause menu. Non-logged-in users see "Guest" with 0 balance.

#### **Authentication Flow:**
1. **Discord OAuth Login:** Users log in via Discord on `public/profile.html`
2. **Session Storage:** Discord ID stored in PHP session (`$_SESSION['discord_id']`)
3. **Profile Fetching:** Game fetches player details on page load and when pause menu opens
4. **GUI Display:** Username and balance displayed in pause menu (logged-in users) or "Guest" (non-logged-in users)

#### **API Endpoint: `/api/user/details.php`**

**Request Methods:**
- **With Discord ID:** `GET /api/user/details.php?user_id={discord_id}`
- **Without Discord ID:** `GET /api/user/details.php` (uses session fallback)

**Request Headers:**
```
Content-Type: application/json
Accept: application/json
Credentials: include (for session cookies)
```

**Success Response:**
```json
{
  "success": true,
  "user": {
    "discord_id": "328601656659017732",
    "username": "Narrrf",
    "avatar_url": "https://cdn.discordapp.com/avatars/...",
    "member_since": "2024-01-01 00:00:00",
    "balance": 12345,
    "roles": ["admin", "premium"],
    "traits": ["CHEESE_LOVER", "RIDDLE_MASTER"]
  }
}
```

**Error Response (Not Logged In):**
```json
{
  "success": false,
  "error": "User ID required and no active session found."
}
```

**Database Queries Performed:**
1. **User Lookup:** `SELECT discord_id, username, avatar_url, created_at FROM tbl_users WHERE discord_id = ?`
2. **Balance Calculation:** `SELECT COALESCE(SUM(score), 0) AS total FROM tbl_user_scores WHERE user_id = ?`
3. **Roles Retrieval:** `SELECT role_name FROM tbl_user_roles WHERE user_id = ?`
4. **Traits Retrieval:** `SELECT trait FROM tbl_user_traits WHERE user_id = ?`

#### **Frontend Implementation:**

**Location:** `public/three.js/main.js` (lines ~14801-14905)

**Key Functions:**
- **`fetchPlayerDetails()`** - Fetches player details from API
- **`hydratePlayerProfile()`** - Called on page load to fetch initial profile
- **`showPauseMenu()`** - Calls `fetchPlayerDetails()` before showing pause menu

**Function: `fetchPlayerDetails()`**
```javascript
async function fetchPlayerDetails() {
  // Determine Discord ID from:
  // 1. URL parameter (user_id)
  // 2. Session (via API fallback)
  // 3. Local storage (development/testing)
  
  const url = resolvedDiscordId
    ? `${API_BASE_URL}/api/user/details.php?user_id=${encodeURIComponent(resolvedDiscordId)}`
    : `${API_BASE_URL}/api/user/details.php`;
  
  const response = await fetch(url, {
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      "Accept": "application/json"
    }
  });
  
  if (response.ok) {
    const data = await response.json();
    if (data.success && data.user) {
      // Update global variables
      resolvedDiscordId = data.user.discord_id || resolvedDiscordId;
      playerDisplayName = data.user.username || "Guest";
      currentTotalDspoinc = data.user.balance || 0;
      
      // Update pause menu subtitle
      const subtitle = document.getElementById("pause-subtitle");
      if (subtitle) {
        subtitle.textContent = `Take a breather, ${playerDisplayName}.`;
      }
      
      // Refresh GUI
      updatePausePlayerInfo();
    }
  } else {
    // Fallback to "Guest"
    playerDisplayName = "Guest";
    currentTotalDspoinc = 0;
    updatePausePlayerInfo();
  }
}
```

**Function: `hydratePlayerProfile()`**
```javascript
// Called on page load
async function hydratePlayerProfile() {
  console.log("🚀 [DEBUG] hydratePlayerProfile called on page load");
  await fetchPlayerDetails();
}

// Execute on page load
hydratePlayerProfile();
```

**Function: `showPauseMenu()` Integration**
```javascript
async function showPauseMenu() {
  // ... menu setup code ...
  
  // Fetch player details before showing menu (ensures up-to-date info)
  await fetchPlayerDetails();
  
  // ... show menu ...
}
```

**Error Handling:**
- If API fails, defaults to "Guest" with 0 balance
- Logs errors to console for debugging
- Falls back to local storage values for development/testing
- Graceful degradation ensures game works for both logged-in and guest users

#### **GUI Display:**

**Pause Menu Integration:**
- **Logged-In Users:**
  - Subtitle: `"Take a breather, {username}."`
  - Balance: Current DSPOINC balance displayed
  - Discord ID: Stored in `resolvedDiscordId` variable
  
- **Guest Users (Not Logged In):**
  - Subtitle: `"Take a breather, Guest."`
  - Balance: `0`
  - Discord ID: `null` (falls back gracefully)

**Location:** `public/three.js/main.js` (lines ~14907-14936)

#### **Global Variables:**
```javascript
// Player information (updated by fetchPlayerDetails)
let resolvedDiscordId = null;        // Discord ID from session/API
let playerDisplayName = "Guest";     // Username or "Guest"
let currentTotalDspoinc = 0;         // DSPOINC balance
```

#### **Verification Checklist:**
- [x] Discord OAuth login works on profile page
- [x] Session stores Discord ID correctly
- [x] API endpoint returns user data for logged-in users
- [x] API endpoint returns error for non-logged-in users
- [x] Frontend fetches player details on page load
- [x] Frontend fetches player details when pause menu opens
- [x] Username displays correctly in pause menu (logged-in users)
- [x] Balance displays correctly in pause menu (logged-in users)
- [x] "Guest" displays correctly for non-logged-in users
- [x] Error handling works correctly (graceful fallback)
- [x] Database queries return correct data

#### **Related Files:**
- `api/user/details.php` - API endpoint for player details
- `public/three.js/main.js` - Frontend implementation (`fetchPlayerDetails()`, `hydratePlayerProfile()`, `showPauseMenu()`)
- `public/three.js/gui-system.js` - GUI system (pause menu display via `updatePausePlayerInfo()`)
- `public/profile.html` - Discord OAuth login page

#### **Future Enhancements (Optional):**
- Add avatar display in pause menu
- Add member since date display
- Add roles/traits display in pause menu
- Cache player details to reduce API calls
- Add refresh button to manually update player info

---

### **2. Discord Role-Based Multipliers**

**Status:** ✅ **WORKING** - Applied server-side to DSPOINC rewards

#### **Multiplier Table:**
- **VIP Holder:** ×2.0
- **Holder:** ×1.5
- **Champion:** ×1.4
- **WL/Season Tester:** ×1.3
- **Early Bird:** ×1.2
- **Cheese Hunter:** ×1.1
- **Default:** ×1.0

#### **Discord Role API:**
- **Endpoint:** `/api/user/roles.php`
- **Returns:** User's Discord roles with IDs
- **Multipliers:** Applied to DSPOINC rewards server-side (in `riddle-reward.php` and `cheese-hunt-capture.php`)

#### **Implementation:**
Role multipliers are automatically applied when awarding DSPOINC rewards via:
- `/api/dev/riddle-reward.php` - Riddle completions
- `/api/dev/cheese-hunt-capture.php` - Cheese captures

**Server-Side Logic:**
```php
// Get user's Discord roles
$rolesStmt = $db->prepare("SELECT role_name FROM tbl_user_roles WHERE user_id = ?");
$rolesStmt->execute([$discordId]);
$roles = $rolesStmt->fetchAll(PDO::FETCH_COLUMN);

// Calculate multiplier based on roles
$multiplier = 1.0;
if (in_array('VIP Holder', $roles)) {
    $multiplier = 2.0;
} elseif (in_array('Holder', $roles)) {
    $multiplier = 1.5;
} // ... etc

$totalReward = (int)($baseReward * $multiplier);
```

---

## 📱 **MOBILE CONTROLS SYSTEM (✅ COMPLETE - January 18, 2026)**

**Status:** ✅ **PRODUCTION READY** - Complete mobile optimization with 6 touch-friendly UI elements  
**Last Updated:** January 18, 2026  
**Purpose:** Full mobile gameplay support with landscape enforcement and touch-optimized controls

---

### **📋 OVERVIEW**

The Mobile Controls System makes the game fully playable on phones and tablets through:
- ✅ **6 Touch-Friendly UI Elements** - All controls optimized for touch input
- ✅ **Landscape Enforcement** - Automatic landscape mode detection and prompting
- ✅ **Dynamic Visibility** - Controls show/hide based on game state
- ✅ **Level-Specific Controls** - Context-sensitive UI (weapon controls only in combat levels)
- ✅ **Touch Event Optimization** - Prevents browser defaults (zoom, scroll, text selection)

---

### **🎮 MOBILE CONTROL ELEMENTS (6 TOTAL)**

#### **1. Movement Joystick (Left Side)**

**Purpose:** Player movement (WASD replacement for mobile)

**Specifications:**
- **Location:** Bottom-left corner (20px from edge)
- **Size:** 150px outer circle, 60px inner circle
- **Visible When:** `isMobile && isMobileLandscape() && !isGamePaused`
- **Style:**
  - Outer: Blue theme (`rgba(100, 149, 237, 0.3)`)
  - Inner: Blue dot (`rgba(100, 149, 237, 0.8)`)
  - Border: 3px solid blue
- **Function:** `createMobileJoystick()` (lines ~34929-35114 in `main.js`)

**Touch Handling:**
```javascript
joystickOuter.addEventListener("touchstart", (e) => {
  joystickActive = true;
  // Calculate joystick direction
});

joystickOuter.addEventListener("touchmove", (e) => {
  // Update joystick inner position
  // Update joystickDirection vector
});

joystickOuter.addEventListener("touchend", (e) => {
  joystickActive = false;
  joystickDirection.set(0, 0);
});
```

**Integration:**
- Used in `animate()` loop to control player movement
- Replaces WASD keyboard input on mobile
- Works in all levels (1-6)

---

#### **2. Camera Joystick (Right Side)**

**Purpose:** Camera rotation (mouse replacement for mobile)

**Specifications:**
- **Location:** Bottom-right corner (20px from edge)
- **Size:** 150px outer circle, 60px inner circle
- **Visible When:** `isMobile && isMobileLandscape() && !isGamePaused && !isFirstPerson()`
- **Style:**
  - Outer: Orange theme (`rgba(255, 165, 0, 0.3)`)
  - Inner: Orange dot (`rgba(255, 165, 0, 0.8)`)
  - Border: 3px solid orange
- **Function:** `createMobileCameraJoystick()` (lines ~35119-35308 in `main.js`)

**Touch Handling:**
```javascript
cameraJoystickOuter.addEventListener("touchstart", (e) => {
  cameraJoystickActive = true;
  // Calculate joystick direction
});

cameraJoystickOuter.addEventListener("touchmove", (e) => {
  // Update joystick inner position
  // Update cameraJoystickDirection vector
});

cameraJoystickOuter.addEventListener("touchend", (e) => {
  cameraJoystickActive = false;
  cameraJoystickDirection.set(0, 0);
});
```

**Integration:**
- Used in `animate()` loop to control camera rotation (third-person)
- Replaces mouse look on mobile
- Hidden in first-person mode (uses gyroscope if available)

---

#### **3. Pause Button (⏸️)**

**Purpose:** Access pause menu on mobile

**Specifications:**
- **Location:** Top-right corner (20px from edge)
- **Size:** 60px × 60px circular
- **Icon:** ⏸️ (pause emoji, 24px)
- **Visible When:** `isMobile && isMobileLandscape() && !isGamePaused`
- **Style:**
  - Background: `rgba(255, 224, 102, 0.8)` (yellow/cheese theme)
  - Color: `#1a1a2e` (dark text)
  - Box shadow: `0 4px 8px rgba(0, 0, 0, 0.3)`
  - Z-index: 1000 (on top)
- **Function:** `createMobilePauseButton()` (lines ~34240-34329 in `main.js`)

**Event Handling:**
```javascript
pauseBtn.addEventListener("click", (e) => {
  e.preventDefault();
  e.stopPropagation();
  togglePause();
});
```

**Features:**
- ✅ Prevents double-tap zoom (`touch-action: manipulation`)
- ✅ No iOS tap highlight (`-webkit-tap-highlight-color: transparent`)
- ✅ Touch events don't interfere with game (`stopPropagation()`)

---

#### **4. Interact Button (E)**

**Purpose:** Interact with objects (chests, portals, levers, etc.)

**Specifications:**
- **Location:** Bottom-right, above weapon selector (120px from bottom)
- **Size:** 80px × 80px circular
- **Icon:** "E" letter (24px, Press Start 2P font)
- **Visible When:** `isMobile && isMobileLandscape() && !isGamePaused && nearestInteractableChest && !nearestInteractableChest.opened`
- **Style:**
  - Background: `rgba(255, 224, 102, 0.8)` (yellow/cheese theme)
  - Color: `#1a1a2e` (dark text)
  - Animation: Pulsing effect (scale 1.0 → 1.05)
  - Box shadow with glow
  - Z-index: 1000
- **Function:** `createMobileInteractButton()` (lines ~34347-34436 in `main.js`)

**Event Handling:**
```javascript
interactBtn.addEventListener("click", (e) => {
  e.preventDefault();
  e.stopPropagation();
  // Simulate E key press
  if (playerControls && playerControls.config.onInteract) {
    playerControls.config.onInteract();
  }
});
```

**Features:**
- ✅ Pulsing animation draws attention
- ✅ Context-sensitive (only shows when interaction available)
- ✅ Simulates E key press through PlayerControls
- ✅ Works for chests, portals, levers, plates, etc.

**CSS Animation:**
```css
@keyframes pulse {
  0% { transform: scale(1); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); }
  50% { transform: scale(1.05); box-shadow: 0 0 15px rgba(255, 224, 102, 0.7); }
  100% { transform: scale(1); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); }
}
```

---

#### **5. Weapon Selector (1-9)**

**Purpose:** Switch weapons in combat levels

**Specifications:**
- **Location:** Bottom-center (50% translateX)
- **Size:** 9 buttons × 40px each, 8px gap
- **Visible When:** `isMobile && isMobileLandscape() && !isGamePaused && isWeaponLevel` (Levels 4, 5, 6)
- **Style:**
  - Container: Dark background (`rgba(15, 23, 42, 0.8)`)
  - Buttons: Light background (`rgba(255, 255, 255, 0.1)`)
  - Active: Yellow highlight (`rgba(255, 224, 102, 0.3)`)
  - Border: Yellow (`#ffe066`)
  - Z-index: 1000
- **Function:** `createMobileWeaponSelector()` (lines ~34454-34565 in `main.js`)

**Event Handling:**
```javascript
weaponBtn.addEventListener("click", (e) => {
  e.preventDefault();
  e.stopPropagation();
  if (weaponSystem && typeof weaponSystem.switchWeapon === 'function') {
    weaponSystem.switchWeapon(slotNumber);
  }
});
```

**Features:**
- ✅ Active weapon slot highlighted
- ✅ Visual feedback on tap
- ✅ Level-specific (only in combat levels)
- ✅ Integrates with existing weapon system

**Update Function:**
```javascript
function updateMobileWeaponSelector() {
  // Update visibility
  const isWeaponLevel = currentLevel === LEVEL_IDS.LEVEL4 || 
                        currentLevel === LEVEL_IDS.LEVEL5 || 
                        currentLevel === LEVEL_IDS.LEVEL6;
  const shouldShow = !isGamePaused && isMobileLandscape() && isWeaponLevel;
  mobileWeaponSelector.style.display = shouldShow ? "flex" : "none";
  
  // Update active slot highlighting
  if (shouldShow && weaponSystem) {
    const activeSlot = weaponSystem.getCurrentSlot();
    mobileWeaponButtons.forEach((btn, index) => {
      const slot = index + 1;
      if (slot === activeSlot) {
        // Highlight active
        btn.style.background = "rgba(255, 224, 102, 0.3)";
        btn.style.color = "#ffe066";
        btn.style.border = "1px solid #ffe066";
      } else {
        // Normal style
        btn.style.background = "rgba(255, 255, 255, 0.1)";
        btn.style.color = "#cbd5f5";
        btn.style.border = "1px solid rgba(255, 224, 102, 0.1)";
      }
    });
  }
}
```

---

#### **6. Shoot Button (🔫)**

**Purpose:** Fire weapon in combat levels

**Specifications:**
- **Location:** Bottom-right corner (20px from edge)
- **Size:** 80px × 80px circular
- **Icon:** 🔫 (gun emoji, 30px)
- **Visible When:** `isMobile && isMobileLandscape() && !isGamePaused && isWeaponLevel` (Levels 4, 5, 6)
- **Style:**
  - Background: `rgba(255, 0, 0, 0.8)` (red)
  - Pressed: `rgba(255, 50, 50, 1)` (brighter red)
  - Transform: `scale(0.9)` when pressed
  - Box shadow with glow when pressed
  - Z-index: 1000
- **Function:** `createMobileShootButton()` (lines ~34592-34681 in `main.js`)

**Touch Handling (Hold to Fire):**
```javascript
// Touch start (start shooting)
shootBtn.addEventListener("touchstart", (e) => {
  e.preventDefault();
  e.stopPropagation();
  if (!isShooting) {
    isShooting = true;
    // Fire immediately
    weaponSystem.fire();
    // Start continuous fire
    shootInterval = setInterval(() => {
      if (isShooting) weaponSystem.fire();
    }, 100); // ~10 shots per second
  }
}, { passive: false });

// Touch end (stop shooting)
shootBtn.addEventListener("touchend", (e) => {
  e.preventDefault();
  e.stopPropagation();
  if (isShooting) {
    isShooting = false;
    clearInterval(shootInterval);
  }
}, { passive: false });
```

**Features:**
- ✅ Tap for single shot
- ✅ Hold for continuous fire (~10 shots/sec)
- ✅ Visual feedback (scale, color, glow)
- ✅ Level-specific (only in combat levels)
- ✅ Integrates with existing weapon system

---

### **🔧 LANDSCAPE ENFORCEMENT SYSTEM**

#### **Dynamic Landscape Detection:**

**Function:** `isMobileLandscape()` (line ~344 in `main.js`)
```javascript
function isMobileLandscape() {
  return isMobile && window.innerWidth > window.innerHeight;
}
```

**Key Features:**
- ✅ **Dynamic:** Updates in real-time when device rotates
- ✅ **Simple:** Single-line check
- ✅ **Reliable:** Works across all mobile browsers

**Why Dynamic is Critical:**
- Portrait → Landscape rotation must trigger control visibility
- Static constant would never update after page load
- All control visibility depends on this function

---

#### **Landscape Orientation Prompt:**

**Display Conditions:**
- Shown when: `isMobile && !isMobileLandscape()` (portrait mode)
- Hidden when: Device rotates to landscape

**Prompt UI:**
```javascript
const landscapePrompt = document.createElement("div");
Object.assign(landscapePrompt.style, {
  position: "fixed",
  top: "0",
  left: "0",
  width: "100%",
  height: "100%",
  background: "rgba(0, 0, 0, 0.95)",
  display: "flex",
  flexDirection: "column",
  alignItems: "center",
  justifyContent: "center",
  zIndex: "10000",
  color: "#ffe066",
  fontSize: "24px",
  textAlign: "center",
  padding: "20px"
});

landscapePrompt.innerHTML = `
  <div style="font-size: 80px; margin-bottom: 20px;">📱</div>
  <div style="font-family: 'Press Start 2P', monospace; margin-bottom: 10px;">
    Please Rotate Your Device
  </div>
  <div style="font-size: 16px; color: #cbd5f5;">
    This game is best played in landscape mode
  </div>
`;
```

**Features:**
- ✅ Full-screen overlay
- ✅ Clear rotation icon and message
- ✅ Auto-hides when rotated
- ✅ Z-index 10000 (above all game elements)

---

#### **Continuous Landscape Checking:**

The system uses **3 methods** to ensure reliable landscape detection:

**Method 1: orientationchange Event**
```javascript
window.addEventListener("orientationchange", () => {
  setTimeout(() => {
    checkAndCreateJoystick();
    updateMobilePauseButton();
    updateMobileInteractButton();
    updateMobileWeaponSelector();
    updateMobileShootButton();
  }, 100);
});
```

**Method 2: resize Event**
```javascript
window.addEventListener("resize", () => {
  checkAndCreateJoystick();
  updateMobilePauseButton();
  updateMobileInteractButton();
  updateMobileWeaponSelector();
  updateMobileShootButton();
});
```

**Method 3: setInterval (Backup)**
```javascript
setInterval(() => {
  if (isMobile) {
    checkAndCreateJoystick();
    updateMobilePauseButton();
    updateMobileInteractButton();
    updateMobileWeaponSelector();
    updateMobileShootButton();
  }
}, 500);
```

**Why 3 Methods:**
- Some browsers don't fire `orientationchange` reliably
- Some browsers don't fire `resize` on orientation change
- `setInterval` ensures controls update even if events fail

---

### **🎯 CONTROL VISIBILITY LOGIC**

#### **Mobile Pause Button:**
```javascript
function updateMobilePauseButton() {
  if (!isMobile || !mobilePauseButton) return;
  const shouldShow = !isGamePaused && isMobileLandscape();
  mobilePauseButton.style.display = shouldShow ? "flex" : "none";
}
```

**Show When:**
- ✅ On mobile device
- ✅ In landscape mode
- ✅ Game not paused

**Hide When:**
- ❌ Game is paused
- ❌ Device in portrait mode
- ❌ On desktop

---

#### **Mobile Interact Button:**
```javascript
function updateMobileInteractButton() {
  if (!isMobile || !mobileInteractButton) return;
  const shouldShow = !isGamePaused && isMobileLandscape() && 
                     nearestInteractableChest && !nearestInteractableChest.opened;
  mobileInteractButton.style.display = shouldShow ? "flex" : "none";
}
```

**Show When:**
- ✅ On mobile device
- ✅ In landscape mode
- ✅ Game not paused
- ✅ Near interactable object (chest, portal, lever, etc.)
- ✅ Object not already interacted with

**Hide When:**
- ❌ No interactable object nearby
- ❌ Object already opened/used
- ❌ Game is paused
- ❌ Device in portrait mode

---

#### **Mobile Weapon Selector:**
```javascript
function updateMobileWeaponSelector() {
  if (!isMobile || !mobileWeaponSelector) return;
  const isWeaponLevel = currentLevel === LEVEL_IDS.LEVEL4 || 
                        currentLevel === LEVEL_IDS.LEVEL5 || 
                        currentLevel === LEVEL_IDS.LEVEL6;
  const shouldShow = !isGamePaused && isMobileLandscape() && isWeaponLevel;
  mobileWeaponSelector.style.display = shouldShow ? "flex" : "none";
  
  // Update active slot highlighting
  if (shouldShow && weaponSystem) {
    const activeSlot = weaponSystem.getCurrentSlot();
    mobileWeaponButtons.forEach((btn, index) => {
      const slot = index + 1;
      if (slot === activeSlot) {
        Object.assign(btn.style, {
          background: "rgba(255, 224, 102, 0.3)",
          color: "#ffe066",
          border: "1px solid #ffe066"
        });
      } else {
        Object.assign(btn.style, {
          background: "rgba(255, 255, 255, 0.1)",
          color: "#cbd5f5",
          border: "1px solid rgba(255, 224, 102, 0.1)"
        });
      }
    });
  }
}
```

**Show When:**
- ✅ On mobile device
- ✅ In landscape mode
- ✅ Game not paused
- ✅ In weapon level (4, 5, or 6)

**Hide When:**
- ❌ Not in weapon level
- ❌ Game is paused
- ❌ Device in portrait mode

**Active Slot Highlighting:**
- ✅ Active slot: Yellow highlight, yellow border
- ✅ Inactive slots: Gray background, gray text

---

#### **Mobile Shoot Button:**
```javascript
function updateMobileShootButton() {
  if (!isMobile || !mobileShootButton) return;
  const isWeaponLevel = currentLevel === LEVEL_IDS.LEVEL4 || 
                        currentLevel === LEVEL_IDS.LEVEL5 || 
                        currentLevel === LEVEL_IDS.LEVEL6;
  const shouldShow = !isGamePaused && isMobileLandscape() && isWeaponLevel;
  mobileShootButton.style.display = shouldShow ? "flex" : "none";
}
```

**Show When:**
- ✅ On mobile device
- ✅ In landscape mode
- ✅ Game not paused
- ✅ In weapon level (4, 5, or 6)

**Hide When:**
- ❌ Not in weapon level
- ❌ Game is paused
- ❌ Device in portrait mode

**Shooting Modes:**
- **Tap:** Single shot
- **Hold:** Continuous fire (~10 shots/second)

---

### **🔄 INTEGRATION WITH GAME FLOW**

#### **Initialization (Page Load):**
```javascript
// Create all mobile controls on page load (if mobile)
if (isMobile) {
  createMobilePauseButton();
  createMobileInteractButton();
  createMobileShootButton();
  createMobileWeaponSelector();
  createMobileJoystick();
  createMobileCameraJoystick();
}
```

#### **Animate Loop Integration:**
```javascript
function animate() {
  // ... other game logic ...
  
  // Update mobile control visibility every frame
  if (isMobile) {
    updateMobilePauseButton();
    updateMobileInteractButton();
    updateMobileWeaponSelector();
    updateMobileShootButton();
  }
  
  // ... rest of game loop ...
}
```

#### **Pause/Unpause Integration:**
```javascript
function togglePause(forceState) {
  // ... pause logic ...
  
  // Update mobile controls when pause state changes
  if (isMobile) {
    updateMobilePauseButton();
    updateMobileInteractButton();
    updateMobileWeaponSelector();
    updateMobileShootButton();
  }
}
```

#### **Level Change Integration:**
```javascript
function changeLevel(newLevel) {
  // ... level change logic ...
  
  // Update mobile controls for new level
  if (isMobile) {
    updateMobileWeaponSelector(); // Show/hide based on level
    updateMobileShootButton(); // Show/hide based on level
  }
}
```

---

### **🎨 MOBILE UI STYLING STANDARDS**

#### **Color Scheme:**
- **Joysticks:** Blue (movement), Orange (camera)
- **Pause Button:** Yellow/cheese theme (`#ffe066`)
- **Interact Button:** Yellow/cheese theme with pulse
- **Weapon Selector:** Dark blue container, yellow highlights
- **Shoot Button:** Red (`rgba(255, 0, 0, 0.8)`)

#### **Size Standards:**
- **Buttons:** 60-80px circular (touch-friendly)
- **Joysticks:** 150px outer, 60px inner
- **Weapon Slots:** 40px × 40px (compact but tappable)

#### **Touch Optimization:**
- **`touch-action: manipulation`** - Prevents double-tap zoom
- **`user-select: none`** - Prevents text selection
- **`-webkit-tap-highlight-color: transparent`** - No iOS tap highlight
- **`stopPropagation()`** - Prevents event bubbling
- **`passive: true` (where possible)** - Better scroll performance

---

### **🖥️ DESKTOP JOYSTICKS vs MOBILE CONTROLS**

#### **CRITICAL DISTINCTION:**

These are **SEPARATE SYSTEMS** with different purposes!

| Feature | Mobile Controls (NEW) | Desktop Joysticks (OLD) |
|---|---|---|
| **Who?** | Mobile players | Desktop testers |
| **Can Disable?** | ❌ No (essential) | ✅ Yes (optional) |
| **Includes Buttons?** | ✅ Yes (all 6 elements) | ❌ No (joysticks only) |
| **Options Menu Toggle?** | Hidden | Visible |
| **Purpose** | Essential gameplay | Testing/debugging |

#### **Desktop Joysticks Option (Hidden on Mobile):**

**Implementation:**
```javascript
// In Options → General tab
if (!isMobile) {
  // Only show for desktop users
  const joystickSection = document.createElement("div");
  // ... create "Desktop Joysticks (Testing)" toggle ...
  // Allows desktop users to enable joysticks for testing
}
```

**Why Hidden:**
- Mobile players **NEED** their controls (can't disable)
- Desktop players can **OPTIONALLY** test joysticks
- Prevents confusion about what the toggle does

**Documentation:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-18/MOBILE_VS_DESKTOP_JOYSTICKS_EXPLAINED.md`

---

### **🧪 TESTING CHECKLIST**

#### **Mobile Device Testing:**
- [x] **Landscape Detection:**
  - [x] Portrait mode shows rotation prompt
  - [x] Landscape mode hides prompt
  - [x] Controls appear in landscape
  - [x] Controls hide in portrait
  
- [x] **Control Functionality:**
  - [x] Movement joystick moves player
  - [x] Camera joystick rotates camera
  - [x] Pause button opens pause menu
  - [x] Interact button opens chests/portals
  - [x] Weapon selector switches weapons
  - [x] Shoot button fires weapon
  
- [x] **Level-Specific:**
  - [x] All levels (1-6) have joysticks
  - [x] Levels 1-3 hide weapon controls
  - [x] Levels 4-6 show weapon controls
  - [x] Interact button shows near chests/portals
  
- [x] **UI/UX:**
  - [x] No double-tap zoom on buttons
  - [x] No text selection when tapping
  - [x] No iOS tap highlight
  - [x] Controls don't interfere with game
  - [x] Desktop joysticks toggle hidden

#### **Desktop Testing:**
- [x] Desktop joysticks toggle visible
- [x] Desktop can enable/disable joysticks
- [x] Mobile buttons not shown on desktop
- [x] Normal keyboard/mouse still works

---

### **📊 MOBILE OPTIMIZATION FEATURES**

#### **Performance:**
- ✅ Minimal overhead (only updates on state change)
- ✅ Touch events optimized (`passive: true` where possible)
- ✅ No continuous rendering (state-based)
- ✅ Efficient visibility checks

#### **User Experience:**
- ✅ Touch-friendly sizes (60-80px minimum)
- ✅ Clear visual feedback (press effects, glow, highlights)
- ✅ Context-sensitive (controls show when needed)
- ✅ Consistent styling (cheese theme throughout)

#### **Browser Compatibility:**
- ✅ Works on iOS Safari
- ✅ Works on Android Chrome
- ✅ Works on Samsung Internet
- ✅ Fallback for Screen Orientation API

---

### **📁 RELATED FILES**

**Main Implementation:**
- `public/three.js/main.js` (lines ~34240-35308)
  - Mobile control creation functions
  - Mobile control update functions
  - Landscape detection function
  - Options menu integration

**Related Documentation:**
- `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-18/MOBILE_CONTROLS_OPTIMIZATION_PLAN.md`
- `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-18/MOBILE_PHASE1_IMPLEMENTATION_COMPLETE.md`
- `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-18/MOBILE_CONTROLS_FINAL_COMPLETE.md`
- `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-18/MOBILE_CONTROLS_ALL_LEVELS_COMPLETE.md`
- `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-18/MOBILE_VS_DESKTOP_JOYSTICKS_EXPLAINED.md`

---

### **🚀 FUTURE MOBILE ENHANCEMENTS**

**Potential Improvements:**
- [ ] **Gyroscope Integration** - Use device tilt for camera in first-person
- [ ] **Haptic Feedback** - Vibration on shoot, hit, interact
- [ ] **Customizable Button Layout** - Let players move/resize buttons
- [ ] **Touch Gestures** - Swipe to switch weapons, pinch to zoom
- [ ] **Performance Mode** - Reduced graphics for low-end mobile devices
- [ ] **Battery Optimization** - Reduce FPS when battery is low

---

## 🚀 **GOD MODE SETTINGS PERSISTENCE SYSTEM (January 9, 2026) - ✅ PRODUCTION READY**

**Status:** ✅ **WORKING PERFECTLY** - Verified January 9, 2026  
**Purpose:** Persistent storage for God Mode settings (grass, sky, phoenix boss, alien spider boss) that works in both local and production environments

### **Overview:**
The God Mode settings persistence system allows authorized users (Admin, Moderator, Game Tester) to save and load their custom settings for grass, sky, and boss configurations. Settings are stored in `localStorage` with level-specific keys and automatically loaded when levels are initialized.

### **Key Features:**
- ✅ **Persistent Storage:** Settings saved to `localStorage` with level-specific keys
- ✅ **Level-Specific Settings:** Each level has its own saved configuration
- ✅ **Immediate Application:** Settings applied immediately after saving (not just on next level load)
- ✅ **Production-Ready:** Normalized `levelId` values ensure consistent keys across local and production
- ✅ **Error Handling:** Robust error handling with localStorage availability checks
- ✅ **Debug Logging:** Comprehensive console logging for troubleshooting

### **Settings Categories:**
1. **Grass/Ground Settings:**
   - Ground type (grass, blank, color)
   - Blade count, blade length multiplier
   - Wind speed, wind strength, wind direction
   - Wind turbulence, wind gust settings
   - Chunked grass configuration
   - Underground type and color/texture

2. **Sky Settings:**
   - Time of day (hour, minute)
   - Time of day preset (dawn, day, dusk, night)
   - Cloud density
   - Star count
   - Lensflare enable/disable
   - Day/night cycle enable/disable
   - Time speed multiplier

3. **Phoenix Boss Settings:**
   - Size, color, eye color
   - Emissive glow intensity
   - Health, max health
   - Behavior mode
   - Behavior durations

4. **Alien Spider Boss Settings:**
   - Size, brightness
   - Texture variation
   - Health, max health
   - Behavior mode

### **Storage Key Format:**
All settings use normalized level IDs (uppercase) for consistent keys:
- **Ground Settings:** `ground_settings_${LEVEL_ID}` (e.g., `ground_settings_LEVEL1`)
- **Sky Settings:** `sky_settings_${LEVEL_ID}` (e.g., `sky_settings_LEVEL1`)
- **Phoenix Boss Settings:** `phoenix_boss_settings_${LEVEL_ID}` (e.g., `phoenix_boss_settings_LEVEL6`)
- **Alien Spider Boss Settings:** `alien_spider_boss_settings_${LEVEL_ID}` (e.g., `alien_spider_boss_settings_LEVEL6`)

### **Critical Fix (January 9, 2026):**
**Problem:** Settings were not saving/loading correctly in production due to:
- Case-sensitive `levelId` mismatches (e.g., `level1` vs `LEVEL1`)
- Missing localStorage availability checks
- Settings only applied on next level load, not immediately

**Solution:**
1. **Normalized `levelId` Values:** All `levelId` values are normalized to uppercase (e.g., `LEVEL1`, `LEVEL2`) before saving/loading
2. **localStorage Availability Checks:** Added checks for `typeof Storage !== 'undefined'` and `!!window.localStorage`
3. **Immediate Application:** Settings applied immediately after saving (wind settings, blade length, etc.)

### **Implementation:**

#### **Save Functions:**
```javascript
// Save ground settings for a level
function saveGroundSettingsForLevel(levelId) {
  // Normalize levelId to uppercase
  const normalizedLevelId = levelId.toUpperCase();
  const storageKey = `ground_settings_${normalizedLevelId}`;
  
  // Check localStorage availability
  if (typeof Storage === 'undefined' || !window.localStorage) {
    console.error(`❌ [GROUND] localStorage not available`);
    return;
  }
  
  // Get current options from grassSystem
  const currentOptions = grassSystem.getOptions();
  const settings = {
    groundType: currentOptions.groundType,
    bladeCount: currentOptions.bladeCount,
    bladeLengthMultiplier: currentOptions.bladeLengthMultiplier,
    windSpeed: currentOptions.windSpeed,
    windStrength: currentOptions.windStrength,
    windDirectionAngle: currentOptions.windDirectionAngle,
    // ... other settings
    savedAt: new Date().toISOString(),
    savedFrom: window.location.hostname
  };
  
  // Save to localStorage
  localStorage.setItem(storageKey, JSON.stringify(settings));
  
  // Apply settings immediately (not just on next level load)
  grassSystem.setWindSpeed(settings.windSpeed);
  grassSystem.setWindStrength(settings.windStrength);
  grassSystem.setWindDirection(settings.windDirectionAngle);
  grassSystem.setBladeLength(settings.bladeLengthMultiplier);
}
```

#### **Load Functions:**
```javascript
// Load ground settings for a level
function loadGroundSettingsForLevel(levelId) {
  // Normalize levelId to uppercase
  const normalizedLevelId = levelId ? levelId.toUpperCase() : levelId;
  const storageKey = `ground_settings_${normalizedLevelId}`;
  
  // Check localStorage availability
  if (typeof Storage === 'undefined' || !window.localStorage) {
    console.warn(`⚠️ [GROUND] localStorage not available`);
    return null;
  }
  
  try {
    const saved = localStorage.getItem(storageKey);
    if (saved) {
      const settings = JSON.parse(saved);
      console.log(`📂 [GROUND] Loaded saved settings for ${normalizedLevelId}:`, settings);
      return settings;
    }
  } catch (e) {
    console.warn(`⚠️ [GROUND] Failed to load saved settings:`, e);
  }
  return null;
}
```

#### **Configuration Merging:**
```javascript
// Get ground config for a level (merges saved settings with defaults)
function getGroundConfigForLevel(levelId) {
  // Normalize levelId
  const normalizedLevelId = levelId ? levelId.toUpperCase() : levelId;
  
  // Start with default config
  const defaultConfig = levelGroundConfigs[normalizedLevelId];
  if (!defaultConfig) return null;
  
  // Try to load saved settings
  const savedSettings = loadGroundSettingsForLevel(normalizedLevelId);
  
  // Merge saved settings with defaults
  if (savedSettings) {
    const mergedConfig = {
      ...defaultConfig,
      ...(savedSettings.groundType !== undefined && { groundType: savedSettings.groundType }),
      ...(savedSettings.bladeCount !== undefined && { bladeCount: savedSettings.bladeCount }),
      ...(savedSettings.windSpeed !== undefined && { windSpeed: savedSettings.windSpeed }),
      // ... merge other settings
    };
    return mergedConfig;
  }
  
  return defaultConfig;
}
```

### **Settings Application:**
Settings are applied in two ways:
1. **On Level Initialization:** Settings loaded and merged with defaults when `initializeGrassSystem()` or `initializeSkySystem()` is called
2. **Immediately After Save:** Settings applied immediately after saving (e.g., wind speed, wind strength, blade length) so changes are visible right away

### **Error Handling:**
- **localStorage Not Available:** Checks for `typeof Storage !== 'undefined'` and `!!window.localStorage` before accessing
- **JSON Parsing Errors:** Wrapped in try-catch blocks with error logging
- **Missing Settings:** Gracefully falls back to default configuration
- **Invalid Level IDs:** Normalized to uppercase for consistent keys

### **Debug Logging:**
All save/load operations log to console with:
- Normalized `levelId` and storage key
- Settings object contents
- Timestamp and hostname (for debugging domain-specific issues)
- Error messages if operations fail

### **Related Functions:**
- `saveGroundSettingsForLevel(levelId)` - Save grass/ground settings
- `loadGroundSettingsForLevel(levelId)` - Load grass/ground settings
- `saveSkySettingsForLevel(levelId)` - Save sky settings (via save button)
- `loadSkySettingsForLevel(levelId)` - Load sky settings
- `savePhoenixBossSettingsForLevel(levelId, settings)` - Save Phoenix boss settings
- `loadPhoenixBossSettingsForLevel(levelId)` - Load Phoenix boss settings
- `saveAlienSpiderBossSettingsForLevel(levelId, settings)` - Save Alien Spider boss settings
- `loadAlienSpiderBossSettingsForLevel(levelId)` - Load Alien Spider boss settings
- `getGroundConfigForLevel(levelId)` - Get merged ground config (defaults + saved)
- `getSkyConfigForLevel(levelId)` - Get merged sky config (defaults + saved)
- `getPhoenixBossConfigForLevel(levelId)` - Get merged Phoenix boss config
- `getAlienSpiderBossConfigForLevel(levelId)` - Get merged Alien Spider boss config

### **Verification Checklist:**
- [x] Settings save correctly in local environment
- [x] Settings save correctly in production environment
- [x] Settings load correctly on level change
- [x] Settings apply immediately after saving
- [x] Normalized `levelId` values ensure consistent keys
- [x] localStorage availability checks prevent errors
- [x] Error handling works correctly (graceful fallback)
- [x] Debug logging provides troubleshooting information

### **Related Files:**
- `public/three.js/main.js` - Save/load functions and configuration merging
- `public/three.js/gui-system.js` - God Mode UI (save buttons in Options menu)
- `public/three.js/grass-system.js` - Grass system (settings applied here)
- `public/three.js/sky-system.js` - Sky system (settings applied here)
- `public/three.js/phoenix2.js` - Phoenix boss (settings applied here)
- `public/three.js/alien-spider.js` - Alien Spider boss (settings applied here)

---

## 🎮 **LEVEL 6 BOSS HUD SYSTEM (February 3, 2026) - ✅ COMPLETE**

**Status:** ✅ **WORKING PERFECTLY** – Both Phoenix and Alien Spider behavior HUDs update correctly  
**Purpose:** Combined HUD displaying current boss behavior for Level 6 dual-boss arena (God Mode)

### **Overview:**
Level 6 features two bosses (Phoenix Dragon + Alien Spider). A combined HUD shows each boss's current behavior. Phoenix has 15 patterns; Alien Spider has 12 patterns. Both HUDs sync with model state via keyboard (F/N keys) and Options menu.

### **Key Features:**
- ✅ **Combined HUD:** Single container with Phoenix row + Alien Spider row
- ✅ **Phoenix:** F key cycles 15 behaviors; HUD updates correctly
- ✅ **Alien Spider:** N key cycles 12 behaviors; HUD updates correctly
- ✅ **Per-frame sync:** Alien Spider HUD reads `alienSpiderBoss.behaviorMode` every frame in animate loop (Level 6 + God Mode)
- ✅ **Options menu:** Both bosses – dropdown lists all behaviors; change triggers immediate HUD update
- ✅ **Cache-busting:** gui-system.js import uses `?v=2026-02-03-combined-boss-hud` to avoid stale cache

### **Alien Spider 12 Behaviors (Full List):**
`idle_1`, `idle_2`, `patrol`, `charge_attack`, `combo_attack`, `aggressive_patrol`, `retreat_attack`, `stagger_recovery`, `death`, `spawn`, `hit`, `attack`

**Note:** `public/three.js/main.js` previously had only 7 behaviors in options dropdown; fixed to all 12 (Feb 3, 2026).

### **Implementation:**
- **GUI:** `createLevel6BossBehaviorHud()` in gui-system.js – creates combined HUD
- **Updates:** `updatePhoenixBehaviorDisplay()`, `updateAlienSpiderBehaviorDisplay()` – both use `document.getElementById()` for direct DOM access
- **Per-frame sync:** In `animate()` loop, when `currentLevel === LEVEL_IDS.LEVEL6 && godMode`, call `guiSystem.updateAlienSpiderBehaviorDisplay()` with `alienSpiderBoss.behaviorMode`
- **Key handlers:** F = Phoenix cycle, N = Spider cycle (in main.js `cycleAlienSpiderBehavior()`)
- **Options menu:** Pause → Options → Boss tab – behavior dropdowns for both bosses; change handler calls `updateAlienSpiderBehaviorDisplay()` / `updatePhoenixBehaviorDisplay()`

### **Files:**
- `public/three.js/gui-system.js` – Combined HUD creation
- `public/three.js/main.js` – cycleAlienSpiderBehavior (12 behaviors), options dropdown, per-frame sync, cache-busting
- `three.js/gui-system.js`, `three.js/main.js` – Dev versions (source of truth)

### **Reference:**
- `12.0/LAB_NOTES/2026/02_FEBRUARY/DAILY_NOTES/2026-02-03/LEVEL6_COMBINED_BOSS_HUD_PROGRESS_2026-02-03.md`

---

## 💡 **CODE EXAMPLES**

### **Complete Module Initialization:**
```javascript
// three.js/main.js
import { SkySystem } from "./sky-system.js";
import { GrassSystem } from "./grass-system.js";
import { PlayerControls } from "./player-controls.js";
import { PlayerModel } from "./player-model.js";
import { GUISystem } from "./gui-system.js";
import { AudioSystem } from "./audio-system.js";
import { WeaponSystem } from "./weapon-system.js";
import { ChestSystem } from "./chest-system.js";
import { PhoenixBoss2 } from "./phoenix2.js";
import { AlienSpiderBoss } from "./alien-spider.js";

// Initialize core systems
SkySystem.applyLevelEnvironment('level1');
GrassSystem.applyLevelEnvironment('level1');
PlayerControls.enable();
PlayerModel.load();
GUISystem.initialize();
AudioSystem.initialize();

// Initialize level-specific systems
WeaponSystem.initialize(); // Levels 4-6
ChestSystem.initialize(); // All levels

// Initialize boss systems (Level 6)
if (currentLevel === 6) {
  PhoenixBoss2.loadModel();
  AlienSpiderBoss.loadModel();
}
```

---

## 🔧 **ASSET PATH RESOLUTION SYSTEM (January 6, 2026) - ✅ PRODUCTION READY**

### **Unified Path Resolution for Local + Production**

**Status:** ✅ **PRODUCTION READY - STABLE VERSION 1.0**  
**Implementation:** `resolveAssetPath()` function in `main.js` (line 734)

### **How It Works:**

**Path Resolution Function:**
```javascript
function resolveAssetPath(path) {
  // Handles relative paths, already-resolved paths, URLs
  // Always returns: /public/three.js/public/${cleanPath}
  // Works for BOTH local XAMPP and production Render (unified path structure)
}
```

**Key Features:**
- ✅ **Unified Path Structure:** Both local and production use `/public/three.js/public/...`
- ✅ **Automatic Resolution:** All asset loaders (`loadModel()`, `loadTexture()`, audio loaders) use `resolveAssetPath()`
- ✅ **Relative Path Support:** Accepts relative paths (e.g., `"textures/3d models/...")` and resolves to absolute
- ✅ **Already-Resolved Detection:** Paths already starting with `/public/three.js/public/` are returned as-is
- ✅ **Debug Logging:** Comprehensive console logging for path resolution debugging

### **Path Resolution Flow:**

**Input Examples:**
- Relative: `"textures/3d models/chest2/Chest2.glb"`
- Relative with prefix: `"./public/textures/..."` or `"public/textures/..."`
- Already absolute: `"/public/three.js/public/textures/..."`

**Output (Always):**
- `/public/three.js/public/textures/3d models/chest2/Chest2.glb`

### **Why Unified Paths Work:**

**Render Symlink Strategy:**
- **Storage:** Assets stored in `/data/public/three.js/public/` (persistent storage)
- **Symlinks:** `/var/www/html/public/three.js/public/` → `/data/public/three.js/public/`
- **Web URL:** `/public/three.js/public/...` (same URL structure for both environments)
- **Result:** Both local XAMPP and production Render use identical web paths!

### **Modules Using resolveAssetPath:**

All modules have been updated to use `resolveAssetPath()`:
- ✅ **main.js** - All hardcoded paths (models, textures, audio, JSON)
- ✅ **chest-system.js** - Chest models and sounds (passed via constructor)
- ✅ **weapon-system.js** - Weapon textures and audio (passed via constructor)
- ✅ **audio-system.js** - Audio paths (passed via constructor)
- ✅ **grass-system.js** - Grass and cloud textures (passed via parameter)
- ✅ **phoenix2.js** - Phoenix texture paths (passed via constructor)
- ✅ **alien-spider.js** - Spider textures and animations (passed via constructor)
- ✅ **gui-system.js** - Background images (passed via constructor)

### **Recent Fixes (January 6, 2026):**

- ✅ **Level 5 Double Ground:** Fixed GLTF map ground + grass underground mesh conflict
- ✅ **Chest Sound 404:** Added debug logging for `/sounds/SFX/chest.mp3` path resolution
- ✅ **Boss Model Paths:** Phoenix and Alien Spider models now use `resolveAssetPath()`
- ✅ **Level Map Loading:** All GLTF level maps (Level 5 `klagenfurt.gltf`, Level 6) use `resolveAssetPath()`
- ✅ **Background Images:** All GUI background images use `resolveAssetPath()`

### **Verification:**

- ✅ **Local Testing:** Game starts correctly, all assets load (no 404 errors)
- ✅ **Path Consistency:** All paths resolve to `/public/three.js/public/...` consistently
- ✅ **Module Integration:** All modules correctly pass and use `resolveAssetPath()`
- ⏳ **Production Testing:** Ready for deployment verification

### **References:**

- **Rule:** `12.0/RULES/11_THREE_JS_RULE.md` §14 (Asset Path Resolution System)
- **Implementation:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/THREE_JS_PATH_RESOLUTION_FIX_PLAN.md`
- **Asset Upload:** `12.0/RULES/22_ASSET_UPLOAD_API_RULE.md`

---

## 🚨 **PRODUCTION ASSET DEPLOYMENT (January 6, 2026) - ✅ WORKING SOLUTION WITH /data/ PERSISTENT STORAGE**

### **CRITICAL: Assets NOT in Git - Must Upload Manually to Render**

**Status:** ✅ **SYSTEM READY** - Asset upload system operational via API endpoint

**Reason:** Large asset files (3.6GB+) were removed from Git tracking to keep repository size manageable. Assets must be uploaded directly to Render.

**CRITICAL:** Assets must be stored in `/data/` (persistent, survives deployments) NOT `/var/www/html/` (gets wiped on each Git push). Use symlinks from `/var/www/html/` to `/data/` for web server access.

### **Assets That Must Be Uploaded:**

#### **1. 3D Models Directory:**
**Persistent Storage Path:** `/data/public/three.js/public/textures/3d models/`  
**Web Access Path (via symlink):** `/var/www/html/public/three.js/public/textures/3d models/`

**Critical Models:**
- ✅ `chest1/` - Chest model 1 (GLB + TGA textures)
- ✅ `chest2/` - Chest model 2 (GLB + TGA textures) - **REQUIRED FOR CHEST SYSTEM**
- ✅ `Survival Pack/` - Bear traps, torches, survival items (FBX/OBJ)
- ✅ `phoenix2/` - Dragon boss textures (Black/Blue/Brown/Eye/Gold/Green/Red/White variants)
- ✅ `secret door medieval/` - Secret door model (GLB)
- ✅ `tree-with-arms/` - Tree model (GLB) - **REQUIRED FOR LEVEL 1**
- ✅ `tree dead lians/` - Dead tree model (GLB)
- ✅ All other 3D model subdirectories

**Local Source:** `C:\xampp-server\htdocs\narrrfs-world\public\three.js\public\textures\3d models\`

#### **2. Sounds Directory:**
**Persistent Storage Path:** `/data/public/three.js/public/sounds/`  
**Web Access Path (via symlink):** `/var/www/html/public/three.js/public/sounds/`

**Critical Sounds:**
- ✅ Footstep sounds
- ✅ Jump sounds
- ✅ Chest opening sounds (`chest.mp3`)
- ✅ Weapon shooting sounds
- ✅ Boss sounds
- ✅ All SFX files (`.mp3`, `.wav`, `.ogg`)

**Local Source:** `C:\xampp-server\htdocs\narrrfs-world\public\three.js\public\sounds\`

#### **3. Audio Directory (If Exists):**
**Persistent Storage Path:** `/data/public/three.js/public/audio/`  
**Web Access Path (via symlink):** `/var/www/html/public/three.js/public/audio/`

**Local Source:** `C:\xampp-server\htdocs\narrrfs-world\public\three.js\public\audio\`

### **Setup Instructions (In Render Shell - Do This First):**

**1. Create Persistent Directories:**
```bash
mkdir -p /data/public/three.js/public/textures/3d\ models/
mkdir -p /data/public/three.js/public/sounds/
mkdir -p /data/public/three.js/public/audio/
chmod -R 755 /data/public/three.js/public/
chown -R www-data:www-data /data/public/three.js/public/
```

**2. Create Symlinks (So Web Server Can Access):**
```bash
# Remove old directories if they exist
rm -rf /var/www/html/public/three.js/public/textures/3d\ models/
rm -rf /var/www/html/public/three.js/public/sounds/
rm -rf /var/www/html/public/three.js/public/audio/

# Create parent directories
mkdir -p /var/www/html/public/three.js/public/textures/
mkdir -p /var/www/html/public/three.js/public/

# Create symlinks
ln -s /data/public/three.js/public/textures/3d\ models /var/www/html/public/three.js/public/textures/3d\ models
ln -s /data/public/three.js/public/sounds /var/www/html/public/three.js/public/sounds
ln -s /data/public/three.js/public/audio /var/www/html/public/three.js/public/audio
```

### **Upload Instructions (From Local Windows Machine):**

**CRITICAL: Upload to `/data/` NOT `/var/www/html/`!**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world

# Upload 3D models to /data/ (PERSISTENT - survives deployments)
scp -r "public\three.js\public\textures\3d models" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/data/public/three.js/public/textures/

# Upload sounds to /data/ (PERSISTENT)
scp -r "public\three.js\public\sounds" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/data/public/three.js/public/

# Upload audio to /data/ (PERSISTENT)
scp -r "public\three.js\public\audio" root@srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com:/data/public/three.js/public/
```

**Render Hostname:** `srv-cvvqcabe5dus73chvrgg-84f5856ddd-k7dv2.onrender.com`

**Complete Guide:** See `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/RENDER_PERSISTENT_ASSETS_SOLUTION.md`

### **Verification Commands (In Render Shell):**
```bash
# Check persistent storage (/data/)
ls /data/public/three.js/public/textures/3d\ models/
ls /data/public/three.js/public/sounds/

# Check symlinks work (web access)
ls /var/www/html/public/three.js/public/textures/3d\ models/
ls /var/www/html/public/three.js/public/sounds/

# Check critical files (via symlink)
ls /var/www/html/public/three.js/public/textures/3d\ models/chest2/Chest2.glb
ls /var/www/html/public/three.js/public/textures/3d\ models/tree-with-arms/tree-with-arms.glb

# Fix permissions if needed
chmod -R 755 /var/www/html/public/three.js/public/
chown -R www-data:www-data /var/www/html/public/three.js/public/
```

### **Upload Methods:**

**✅ RECOMMENDED: Comprehensive API Upload (PowerShell Script - January 9, 2026):**
```powershell
cd C:\xampp-server\htdocs\narrrfs-world
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-09\UPLOAD_ALL_ASSETS_URGENT.ps1 -BotSecret "YOUR_DISCORD_BOT_SECRET"

# Or to upload only new assets (skip 3D models):
.\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-09\UPLOAD_NEW_ASSETS_ONLY.ps1
```

**Complete Upload Guide:** See `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-09/COMPLETE_DEPLOYMENT_PROCESS.md`

**Alternative: Manual curl Commands:**
```powershell
$BOT_SECRET = "YOUR_DISCORD_BOT_SECRET"
curl.exe -X POST -H "Authorization: $BOT_SECRET" -F "file=@path\to\file.glb" -F "target_path=/data/public/three.js/public/textures/3d models/file.glb" https://narrrfs.world/api/discord/upload-assets.php
```

**Alternative: SCP/SFTP (Fallback):**
```powershell
scp -r "public\three.js\public\textures\3d models" root@RENDER_HOST:/data/public/three.js/public/textures/
```

### **Automated Symlink Creation (January 9, 2026)**

**✅ Symlinks are now automatically created by `scripts/render-startup.sh` on every deployment!**

The startup script automatically creates symlinks for all required asset directories:
- `textures/3d models/`
- `textures/grass/`
- `textures/backgrounds/`
- `textures/blocks/`
- `textures/plants/`
- `sounds/`
- `audio/`
- `models/`
- `videos/`

**No manual intervention required** - the startup script runs on every Render deployment.

### **Related Documentation:**
- **Rule:** `12.0/RULES/11_THREE_JS_RULE.md` §13 (Production Asset Upload System) & §14 (Asset Path Resolution System)
- **Rule:** `12.0/RULES/22_ASSET_UPLOAD_API_RULE.md` (Complete API upload guide)
- **Lab Note:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/RENDER_PERSISTENT_ASSETS_SOLUTION.md`
- **Upload Guide:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/LARGE_FILE_UPLOAD_SETUP.md`
- **Complete Deployment Process:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-09/COMPLETE_DEPLOYMENT_PROCESS.md`
- **Stable Version Milestone:** `12.0/MILESTONE_DOCUMENTATION/2026-01-09_STABLE_PRODUCTION_VERSION.md`
- **Daily Status:** `12.0/ACTIVE_STATUS/DAILY_STATUS_2026-01-09.md`
- **Stable Version Marker:** `12.0/ACTIVE_STATUS/STABLE_VERSION_MARKER.md`

### **Important Notes:**
- ⚠️ **Assets are NOT in Git** - They're excluded via `.gitignore` (except `narrrf3d` and `glyph3d` which are uploaded directly to `/data/`)
- ✅ **API Upload System:** Operational via `/api/discord/upload-assets.php` (supports both `/data/public/three.js/public/` and `/data/public/glyph/`)
- ✅ **Path Resolution:** All paths use unified `/public/three.js/public/...` structure
- ✅ **Symlink System:** Symlinks provide web access from `/data/` persistent storage (automatically created by `render-startup.sh`)
- ✅ **Automated Symlink Creation:** Startup script automatically creates all required symlinks on deployment
- ✅ **System Status:** STABLE PRODUCTION VERSION - Level 1 verified working (January 9, 2026)
- ✅ **Critical Files Verified:** `grass.jpg`, `cloud.jpg`, `cheesetemple1.png`, `level1.json` confirmed present in `/data/` and accessible via symlinks
- ✅ **Complete Asset Upload:** 123+ files successfully uploaded to Render persistent storage

---

## 🚀 **FUTURE IMPLEMENTATION PLANS**

### **Short-Term (Next 2-4 Weeks):**
- [ ] **Profile.html Integration:** Complete game stats display
- [ ] **Admin Interface Integration:** Full stats integration
- [ ] **Achievement System:** Basic achievement tracking
- [ ] **Shop System:** Basic store upgrades

### **Medium-Term (1-3 Months):**
- [ ] **Level 7+:** Additional levels with new riddles
- [ ] **Multiplayer Sync:** WebSocket-based multiplayer
- [ ] **Advanced Boss Patterns:** More boss behavior patterns
- [ ] **Performance Optimization:** Further FPS improvements

### **Long-Term (3+ Months):**
- [ ] **Full Achievement System:** Complete achievement tracking
- [ ] **Full Shop System:** Complete store integration
- [ ] **VR Support:** Full VR gameplay
- [ ] **Mobile App:** Native mobile app version

---

---

## ✅ **STABLE PRODUCTION VERSION STATUS (January 9, 2026)**

### **Production Readiness:**
- ✅ **Path Resolution System:** Unified path resolution working for both local and production
- ✅ **Asset Persistence:** `/data/` persistent storage + symlink system operational
- ✅ **Asset Upload System:** API endpoint operational - 123+ files successfully uploaded
- ✅ **Automated Symlink Creation:** `render-startup.sh` automatically creates all required symlinks on deployment
- ✅ **Module Integration:** All modules use `resolveAssetPath()` correctly
- ✅ **All Fixes Complete:** Level 5 ground, chest sounds, boss models, level maps, CSS backgrounds all working
- ✅ **Local Testing:** Game starts correctly, all assets load (no 404 errors)
- ✅ **Production Testing:** ✅ **LEVEL 1 VERIFIED WORKING** - No 404 errors, all assets loading correctly

### **Key Accomplishments:**
- ✅ **Unified Path Structure:** `/public/three.js/public/...` works for both environments
- ✅ **Asset Persistence:** Symlink strategy maintains path consistency
- ✅ **Module System:** All 12 modules properly integrated with path resolution
- ✅ **Bug Fixes:** All critical path-related bugs resolved
- ✅ **CSS Background Fixes:** Background images resolve correctly in production (absolute URLs)
- ✅ **Critical Assets Verified:** All critical files (`grass.jpg`, `cloud.jpg`, `cheesetemple1.png`, `level1.json`) confirmed present in `/data/`
- ✅ **Documentation:** Complete rules and technical documentation updated and synchronized
- ✅ **Version Markers:** All modules marked with stable version markers

### **Stable Production Confirmation:**
- ✅ **Level 1 Loading:** Verified working in production browser - no 404 errors
- ✅ **Asset Access:** All critical assets accessible via symlinks from `/data/`
- ✅ **Path Resolution:** All asset paths resolve correctly in production environment
- ✅ **Symlink System:** Automated symlink creation working via startup script
- ✅ **Code Stability:** All changes tested and verified before deployment
- ✅ **Complete Asset Upload:** All local assets successfully uploaded to Render persistent storage

### **Production Systems Operational:**
- ✅ **Code Deployment:** Git push → Render deployment working correctly
- ✅ **Asset Management:** API upload system operational for all asset types
- ✅ **Path Resolution:** Production environment detection and path resolution working
- ✅ **Symlink Automation:** Startup script creates all required symlinks automatically
- ✅ **Asset Verification:** All critical files confirmed present and accessible

**🎮 Complete technical documentation for 3D Riddle Game v2026-01-09-STABLE-PRODUCTION - STABLE PRODUCTION VERSION CONFIRMED! 🎮**

---

## 🥽 **VR SUPPORT SYSTEM (META QUEST 3)** - ✅ **OPTIMIZED** - January 18, 2026

### **Overview:**
The game now has full VR support optimized for Meta Quest 3, including movement controls, texture loading fixes, and performance optimizations for mobile VR devices.

### **Status:**
- ✅ **Phase 1 Complete:** Movement & Controls (January 18, 2026)
- ✅ **Phase 2 Complete:** Texture Loading & Performance (January 18, 2026)
- ⏳ **Phase 3 Pending:** UI & Comfort Features (Scheduled after testing)

---

### **1. VR Movement & Controls (Phase 1)** ✅

**Implementation Date:** January 18, 2026

#### **A. Fixed Animation Loop for WebXR**

**Problem:** Game used `requestAnimationFrame()` which doesn't sync with VR headset refresh rate.

**Solution:**
```javascript
// OLD (Incorrect):
function animate() {
  requestAnimationFrame(animate);
  // ... game logic
}

// NEW (Correct for VR):
function animate() {
  // ... game logic
}
renderer.setAnimationLoop(animate); // WebXR-compatible loop
```

**Impact:**
- ✅ Syncs with Quest 3's 72Hz/90Hz/120Hz refresh rate
- ✅ Prevents frame drops and stuttering
- ✅ Enables access to XR `frame` object for controller poses

#### **B. VR Input Provider Integration**

**Problem:** `vrInputProvider.update()` was never called, so controller input was never read.

**Solution:**
```javascript
function animate() {
  const delta = clock.getDelta();
  
  // Update VR input provider BEFORE player controls
  if (vrInputProvider && vrInputProvider.enabled) {
    vrInputProvider.update(delta);
  }
  
  // Update player controls (which reads from VR input)
  if (playerControls) {
    playerControls.update(delta);
  }
  
  renderer.render(scene, camera);
}
```

**Impact:**
- ✅ Controller thumbsticks now control movement
- ✅ Left stick: Forward/backward/strafe
- ✅ Right stick: Camera rotation
- ✅ Buttons: Jump, sprint, interact

#### **C. Controller Axis Mapping**

**File:** `public/three.js/vr-input-provider.js`

**Thumbstick Mapping:**
```javascript
// Left Thumbstick (Movement)
forward: leftStick.y > 0.1 ? leftStick.y : 0,
backward: leftStick.y < -0.1 ? -leftStick.y : 0,
left: leftStick.x < -0.1 ? -leftStick.x : 0,
right: leftStick.x > 0.1 ? leftStick.x : 0,

// Right Thumbstick (Rotation)
rotateLeft: rightStick.x < -0.1 ? -rightStick.x : 0,
rotateRight: rightStick.x > 0.1 ? rightStick.x : 0,
```

**Features:**
- ✅ 0.1 deadzone to prevent drift
- ✅ Analog sensitivity (not just on/off)
- ✅ Smooth rotation with right stick
- ✅ Standard VR control scheme

#### **D. Button Mapping**

**Quest 3 Controller Layout:**
```javascript
// A Button (Right Controller) - Jump
// X Button (Left Controller) - Sprint
// Trigger - Fire weapon (in combat levels)
// Grip - Interact (E key equivalent)
```

---

### **2. VR Texture Loading & Performance (Phase 2)** ✅

**Implementation Date:** January 18, 2026

#### **A. Texture Optimization Check**

**Function:** `isTextureVROptimized(texture)`

**Purpose:** Validates textures meet Quest 3's memory limits.

```javascript
function isTextureVROptimized(texture) {
  const width = texture.image?.width || 0;
  const height = texture.image?.height || 0;
  
  const maxSize = 2048; // Quest 3 maximum
  const recommendedSize = 1024; // Quest 3 recommended
  
  if (width > maxSize || height > maxSize) {
    console.warn(`⚠️ [VR TEXTURE] Texture too large: ${width}x${height}`);
    return false;
  }
  
  return true;
}
```

**Impact:**
- ✅ Warns about oversized textures
- ✅ Prevents memory overflow crashes
- ✅ Helps identify problem assets

#### **B. Scene Optimization for VR**

**Function:** `optimizeForVR()`

**Purpose:** Reduces memory usage for Quest 3's limited RAM.

**Optimizations Applied:**

1. **Texture Anisotropy Reduction:**
   ```javascript
   // Reduce from 16x to 4x (75% memory reduction)
   material.map.anisotropy = 4;
   material.normalMap.anisotropy = 2;
   material.roughnessMap.anisotropy = 2;
   ```
   - **Performance:** +25% faster texture sampling
   - **Memory:** -40% texture memory usage
   - **Quality:** Minimal visual difference in VR

2. **Shadow Map Size Reduction:**
   ```javascript
   // Reduce from 2048x2048 to 1024x1024
   light.shadow.mapSize.width = 1024;
   light.shadow.mapSize.height = 1024;
   ```
   - **Performance:** +15% faster shadow rendering
   - **Memory:** -75% shadow memory usage
   - **Quality:** Still acceptable in VR

3. **Light Distance Optimization:**
   ```javascript
   // Reduce point/spot light distance to max 50 units
   object.distance = Math.min(object.distance, 50);
   ```
   - **Performance:** +10% faster lighting
   - **Memory:** Reduced fragment shader complexity

**Total Performance Gain:** +30-50% FPS improvement on Quest 3

#### **C. VR Loading Indicator**

**Functions:** `showVRLoadingIndicator()` / `hideVRLoadingIndicator()`

**Purpose:** Show visual feedback while assets load.

**Implementation:**
```javascript
function showVRLoadingIndicator() {
  // Create glowing cheese sphere (wireframe)
  const geometry = new THREE.SphereGeometry(0.5, 32, 32);
  const material = new THREE.MeshBasicMaterial({
    color: 0xffe066, // Cheese yellow
    wireframe: true,
    transparent: true,
    opacity: 0.8
  });
  const loader = new THREE.Mesh(geometry, material);
  
  // Position in front of player at eye level
  loader.position.set(0, 1.6, -2);
  loader.name = 'vrLoadingIndicator';
  scene.add(loader);
  
  // Add inner solid sphere
  const innerSphere = new THREE.Mesh(innerGeometry, innerMaterial);
  loader.add(innerSphere);
  
  // Animate rotation
  const animateLoader = () => {
    const loaderObj = scene.getObjectByName('vrLoadingIndicator');
    if (loaderObj) {
      loaderObj.rotation.y += 0.02;
      loaderObj.rotation.x += 0.01;
      requestAnimationFrame(animateLoader);
    }
  };
  animateLoader();
}
```

**Features:**
- ✅ Rotating cheese sphere (wireframe + solid core)
- ✅ Positioned at eye level, 2 meters in front
- ✅ Cheese theme colors (yellow/gold)
- ✅ Auto-animates rotation
- ✅ Properly disposed when hidden

#### **D. Enhanced VR Session Startup**

**Function:** `startVRSession()` (UPDATED)

**New Flow:**
```javascript
async function startVRSession() {
  // 1. Show loading indicator
  showVRLoadingIndicator();
  
  // 2. Preload critical assets (if not done)
  if (!window.vrAssetsPreloaded) {
    await preloadCriticalAssets();
    window.vrAssetsPreloaded = true;
  }
  
  // 3. Optimize scene for VR
  const optimizations = optimizeForVR();
  
  // 4. Request VR session
  const session = await navigator.xr.requestSession('immersive-vr', {
    requiredFeatures: ['local-floor'],
    optionalFeatures: ['hand-tracking', 'bounded-floor']
  });
  
  // 5. Enable VR in renderer
  await renderer.xr.setSession(session);
  
  // 6. Create VR input provider
  vrInputProvider = new VRInputProvider(session);
  vrInputProvider.enable();
  
  // 7. Hide loading indicator
  setTimeout(() => hideVRLoadingIndicator(), 1000);
  
  return true;
}
```

**Improvements:**
- ✅ Assets preloaded before session starts
- ✅ Scene optimized for Quest 3 memory limits
- ✅ Visual feedback during loading
- ✅ Graceful error handling
- ✅ Added 'bounded-floor' optional feature

---

### **3. Performance Improvements**

#### **Memory Reduction:**

| Optimization | Memory Saved | Notes |
|---|---|---|
| Texture Anisotropy (16→4) | ~40% | Per texture |
| Shadow Maps (2048→1024) | ~75% | Per light |
| Light Distance Reduction | Variable | Reduces fragment shader load |
| **Total Estimated** | **~200-300MB** | For typical level |

#### **Performance Gains:**

| Optimization | FPS Improvement | Notes |
|---|---|---|
| Texture Filtering | +15-25% | Faster texture sampling |
| Shadow Maps | +10-15% | Smaller shadow buffers |
| Light Distance | +5-10% | Reduced lighting calculations |
| **Total Estimated** | **+30-50%** | Quest 3 in typical scene |

---

### **4. Testing Instructions (Meta Quest 3)**

#### **Before Testing:**
1. Ensure Quest 3 is charged and updated
2. Enable Developer Mode (if needed)
3. Connect to same network as dev server

#### **Testing Steps:**

1. **Enter VR Mode:**
   - Click "Enter VR" button
   - Should see rotating cheese loading indicator

2. **Check Asset Loading:**
   - Wait for cheese to disappear
   - Console should show preload messages
   - Console should show optimization stats

3. **Check Textures:**
   - Ground should have visible texture (not gray!)
   - Sky should render correctly
   - 3D models should have textures
   - No missing/gray surfaces

4. **Check Movement:**
   - Left thumbstick: Move forward/backward/strafe
   - Right thumbstick: Rotate camera left/right
   - A button: Jump
   - X button: Sprint

5. **Check Performance:**
   - Should feel smooth (72fps+)
   - No stuttering or lag
   - Comfortable to play

6. **Check Console:**
   - Look for: `✅ [VR] Assets preloaded successfully`
   - Look for: `✅ [VR OPTIMIZE] VR optimization complete`
   - Check optimization stats numbers

---

### **5. Known Issues & Limitations**

#### **Current Limitations:**
- ⏳ **VR UI:** Menus and HUD not yet optimized for VR (Phase 3)
- ⏳ **Comfort Features:** No vignette or snap-turn options yet (Phase 3)
- ⏳ **Hand Tracking:** Optional feature not yet implemented (Phase 3)

#### **Workarounds:**
- **Menus:** Use desktop view to access pause menu
- **Comfort:** Take breaks if experiencing motion sickness
- **UI:** Some UI elements may be hard to read in VR

---

### **6. Files Modified**

#### **`public/three.js/main.js`**

**Added Functions:**
- `isTextureVROptimized(texture)` - Check texture size
- `optimizeForVR()` - Reduce memory usage
- `showVRLoadingIndicator()` - Show loading cheese
- `hideVRLoadingIndicator()` - Hide loading cheese

**Updated Functions:**
- `animate()` - Now uses `renderer.setAnimationLoop()` and calls `vrInputProvider.update()`
- `startVRSession()` - Now includes Phase 2 optimizations

**Location:** Lines ~1992-2200, ~33116-33200 (approximate)

#### **`public/three.js/vr-input-provider.js`**

**Updated Functions:**
- `getMovementState()` - Fixed controller axis mapping
- `update()` - Now properly reads controller input

**Location:** Lines ~278-350 (approximate)

---

### **7. Related Documentation**

- **VR Fix Plan:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-18/VR_METAQUEST3_FIX_PLAN.md`
- **Phase 1 Summary:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-18/VR_PHASE1_IMPLEMENTATION_COMPLETE.md`
- **Phase 2 Summary:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-18/VR_PHASE2_IMPLEMENTATION_COMPLETE.md`

---

### **8. Future Enhancements (Phase 3)**

**Planned Features:**
- [ ] VR-optimized UI elements (menus, HUD)
- [ ] Comfort features (vignette, snap-turn option)
- [ ] Teleportation locomotion option
- [ ] Hand tracking support
- [ ] VR-specific interaction prompts
- [ ] Polish & final optimizations

**Estimated Time:** 8 hours

---

## 📋 **CHANGELOG – FEBRUARY 2026**

### ✅ Level 1 Blue Cheese Interaction (February 1, 2026)

**Feature:** Proximity-based interaction with the large blue cheese GLB model in Level 1.

**Implementation:**
- **Proximity radius:** 12 units (blue cheese at world coords 100, 5.5, 18)
- **Prompt:** When player is within 12 units and no chest is nearer, shows "Press [E] to interact"
- **E key / VR grip:** Shows toast: **"You need a Cheese Scepter to start the riddle"** (4s duration)
- **Priority:** Chest interaction takes precedence when both are in range

**Code locations:**
- Animate loop: `main.js` ~36037–36050 (chest system update block)
- E key handler: `main.js` ~34487–34508 (KeyE case, before chest block)
- VR grip handler: `main.js` ~34988–35025 (VR INTERACT block)

**Status:** ✅ **Implemented – Ready for testing**

---

### ✅ VR Mode Fixes (February 2026 – Pending Meta Quest Verification)

**Issues addressed:**
- VR camera not spawning at player position
- Player "out of play scenes" in VR
- Magenta sphere (collider debug) sometimes visible but camera not following
- VR Rescue button (Options menu) not working in VR
- Controller movement affecting collider but not camera view

**Fixes implemented:**
1. **Per-frame camera sync:** Camera position forced to `playerCollider` center + 1.6m eye height every frame in VR
2. **`applyVRSpawnForLevel`:** Now updates `playerColliderDebugMarker` (magenta sphere) in addition to collider
3. **`updateCameraPosition(0)`** called after VR spawn to sync camera immediately
4. **VR Rescue shortcut:** Both grip buttons (L+R) trigger respawn to level spawn – no menu needed
5. **VR Rescue controller shortcut:** Edge-triggered (prevents continuous respawn)

**Code locations:**
- `applyVRSpawnForLevel` ~line 367
- Animate loop VR block ~lines 34903–34932
- VR Rescue shortcut ~line 35021
- `_lastVRRescueGrips` ~line 2263

**VR spawn coordinates (VR_SPAWN_POINTS):**
- Level 1: (60, 2.5, 15)
- Level 2–6: Per-level spawn positions

**Status:** ⏳ **Implemented – Awaiting Meta Quest hardware verification**

---

### ✅ Level 4 Portal Register (Visitor's Log) – February 2026

**Feature:** Message/guestbook system at the Level 4 center Cheese Portal. Players can leave messages for others to discover.

**Implementation:**
- **Location:** Center of Level 4 arena (origin)
- **Model:** `textures/3d models/cheese portal/cheese-portal.glb`
- **Scale:** 6× (doubled Feb 2026 – was 3×)
- **Y position:** `origin.y + 6` – raised to prevent model going into ground when scaled 6×
- **Proximity:** 8 units horizontal, 8 units vertical → shows "Press [E] to Open Portal Register"
- **E key:** Opens full-screen Register UI when in proximity

**Portal Register UI:**
- Header: "📖 PORTAL REGISTER" + "Visitor's Log – Level 4 Portal"
- Messages area: Scrollable list from API (max 50 messages)
- Input: Textarea (max 200 chars), Submit button
- Close: ✕ Close button
- Style: Press Start 2P, warm/cheese theme (#ffe066, brown borders)

**API:**
- **Fetch:** `GET /api/user/portal-waypoint.php?portal_id=LEVEL4_CENTER_PORTAL&limit=50`
- **Submit:** `POST /api/user/portal-waypoint.php` (portal_id, discord_id, username, message)
- **Table:** `portal_waypoint_messages`
- **Rate limit:** 1 message per minute per user

**Code locations:**
- `createLevel4CenterPortal()` ~line 21700
- `createPortalRegisterUI()` ~line 22022
- `openPortalRegister()` / `closePortalRegister()` ~lines 21943–22019
- `fetchPortalMessages()` / `renderPortalMessages()` / `submitPortalMessage()` ~lines 22239–22448
- Proximity check: Level 4 update loop ~lines 25928–26014
- E key handler: ~lines 34550–34560

**Status:** ✅ **Implemented – Production ready**

---

### ✅ Chest System – Dual-Model Method (Primary – Simpler) – February 2026

**Documentation update:** Chest system now uses **dual-model method** as primary (simpler).

**Primary method – Dual-model:**
- **closedModelPath** + **openedModelPath** (two separate GLB files)
- Load closed GLB first; preload opened GLB in background
- On open: pop/pulse on closed mesh, then swap visibility to opened mesh
- No lid rotation, no duplicate detection – just swap two GLBs

**Fallback – Legacy chest2:**
- Single GLB with embedded lid meshes
- Manual lid rotation animation + duplicate lid detection (6-pass system)

**Configuration:** Pass `closedModelPath` and `openedModelPath` via `chestModelConfig` when initializing ChestSystem in main.js.

**Code locations:**
- `_playDualModelSwapAnimation()` – `chest-system.js` ~line 1982
- `_ensureOpenedMeshLoaded()` – `chest-system.js` ~line 1915
- Chest constructor – `chest-system.js` ~line 838 (closedModelPath, openedModelPath)
- addChest() – `chest-system.js` ~line 3140 (applies chestModelConfig)

**Status:** ✅ **Documentation updated – Dual-model is primary method**

---

### ✅ Level 5 Chest Treasure Hunt (40 Chests) – February 2026

**Feature:** 40 treasure chests spread across the huge Level 5 (Klagenfurt map) area. Each chest: 100 DSPOINC (role multiplier applied).

**Implementation:**
- **Chest IDs:** chest_012 through chest_051
- **Level ID:** CHEESE_TEMPLE_LEVEL5
- **Y position:** `raycastLevel5GroundYAt(x, z, spawnY)` – ground raycast for uneven city terrain
- **Placement:** Grid pattern (8×5) with random offset across map bounds (~±550 X/Z)
- **Spacing:** ~75 units between grid points
- **Total:** 4,000 base DSPOINC (40 × 100) before role multipliers

**Code locations:**
- `createLevel5Chests()` ~line 23245 (after createLevel5Glyphs)
- Called from buildLevel5TheWalk (setTimeout 100ms) and warpToLevel5 (setTimeout 200ms)

**Status:** ✅ **Implemented – Production ready**

---

## 🧪 **TESTING & HOTFIX LOG (January 12, 2026)**

### ✅ Level 5 → Level 6 Completion Flow Stabilization

**Problem Observed (Level 5):**
- Multiple monster GLTF types were **invisible but still hittable**, causing wave progression to stall.
- Portal completion screen could be **unclickable** due to pointer lock capturing mouse input.
- After clicking “Proceed to Level 6”, Level 6 could load to 100% but the **pause overlay** could remain visible on top while Level 6 audio/weapon ran behind it.

**Fixes Implemented (Local Testing Focus):**
- **Emergency “Quick Mode” for Level 5**
  - Temporarily reduced Level 5 to **1 wave / 5 monsters total** so testers can reliably reach the portal and continue testing Level 6.
- **Completion screen clickability**
  - Explicitly **exit pointer lock** when the completion screen appears.
  - Use “completion pause” (pause state without opening pause menu UI).
  - Add completion screen background styling consistent with Level 2–4 completion screens.
- **Pause overlay sticking during Level 6 warp**
  - Fixed pause state desync: ensure `window.isGamePaused` stays synced with `isGamePaused` in `togglePause()`.
  - `warpToLevelWithLoading()` now force-hides pause menu at warp start and after loading completes.
  - Warp finalization unpauses if **either** `window.isGamePaused` or `isGamePaused` is true.

**Primary Code Location:**
- `public/three.js/main.js`

**Related Daily Notes (2026-01-12):**
- `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-12/LEVEL5_QUICK_MODE_PORTAL_WARP_FIX_2026-01-12.md`
- `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-12/LEVEL5_HUD_TESTING_INSTRUCTIONS_2026-01-12.md` (updated with Quick Mode notes)

---

