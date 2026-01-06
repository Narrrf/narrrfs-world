# 🎮 GAME 7: 3D HYTOPIA GAME - COMPLETE TECHNICAL DOCUMENTATION 2025

**Created:** December 20, 2025  
**Status:** ✅ **PRODUCTION READY - STABLE VERSION**  
**Version:** 1.0.0 - Modular Architecture  
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
13. [Code Examples](#code-examples)
14. [Future Implementation Plans](#future-implementation-plans)

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

### **Integration Status:**
- ✅ **Database:** `tbl_cheese_hunt_captures`, `tbl_riddle_completions`, `tbl_user_traits`
- ✅ **APIs:** `/api/dev/cheese-hunt-capture.php`, `/api/dev/riddle-reward.php`, `/api/user/traits.php`
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
│   └── alien-spider.js           # Alien Spider boss (7 patterns)
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
- **Features:** Animation, rewards (DSPOINC), persistence, grass exclusion
- **Initialization:** Once at game start (usually in weapon system init)
- **Update:** Event-driven (checks interactions in `update()`)
- **Key Functions:**
  - `createChestsForLevel(levelId, chests)` - Create chests for level
  - `update(delta)` - Check interactions
  - `registerExclusionZones()` - Prevent grass under chests

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

### **Role-Based Multipliers:**
- **VIP Holder:** ×2.0
- **Holder:** ×1.5
- **Champion:** ×1.4
- **WL/Season Tester:** ×1.3
- **Early Bird:** ×1.2
- **Cheese Hunter:** ×1.1
- **Default:** ×1.0

### **Discord Role API:**
- **Endpoint:** `/api/user/roles.php`
- **Returns:** User's Discord roles with IDs
- **Multipliers:** Applied to DSPOINC rewards server-side

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

## 🚨 **PRODUCTION ASSET DEPLOYMENT (January 4, 2026) - UPDATED WITH /data/ PERSISTENT STORAGE**

### **CRITICAL: Assets NOT in Git - Must Upload Manually to Render**

**Status:** ⚠️ **ASSETS MISSING ON PRODUCTION** - Must be uploaded via SCP/SFTP to `/data/` (persistent storage)

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

### **Related Documentation:**
- **Rule:** `12.0/RULES/11_THREE_JS_RULE.md` §13 (Production Asset Upload Checklist)
- **Lab Note:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/THREE_JS_ASSET_UPLOAD_GAP.md`
- **Upload Guide:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/RENDER_ASSET_UPLOAD_GUIDE.md`
- **Quick Commands:** `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-04/QUICK_UPLOAD_COMMANDS.md`

### **Important Notes:**
- ⚠️ **Assets are NOT in Git** - They're excluded via `.gitignore`
- ⚠️ **Must upload manually** - Use SCP/SFTP from local machine
- ⚠️ **Upload required for each deployment** - Assets don't deploy via Git
- ✅ **Directories created** - Ready for upload (January 4, 2026)
- ✅ **Permissions set** - Directories have correct permissions

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

**🎮 Complete technical documentation for 3D Riddle Game - Ready for decades of development! 🎮**

