# 🎁 Hytopia Chest System Implementation Rules

**Last Updated:** December 30, 2025  
**Status:** ✅ **PRODUCTION READY - STANDARDIZED - COLLISION & PERSISTENCE WORKING - MULTI-LEVEL SUPPORT**

---

## ✅ **FINAL STATUS (December 15, 2025)**

- ✅ **All chests standardized to chest2** (has animation support)
- ✅ **Animation system working** (lid rotation, particles, sound, glow)
- ✅ **Duplicate lid detection working** (3-pass system ensures closed lid is hidden)
- ✅ **Chest counter system implemented** (tracks opened chests in localStorage)
- ✅ **Collision detection implemented** (players cannot walk through chests)
- ✅ **Persistence system implemented** (opened chests saved to database, restored on load)
- ✅ **Tested with chest_001 and chest_002** (both working perfectly)
- ✅ **Documentation complete** (comprehensive notes in chest-system.js and rule file)
- ✅ **Ready for hundreds of chests** (all will work consistently using this pattern)

---

## 📖 **OVERVIEW**

Complete guide for implementing treasure chests in the game. All chests use the standardized chest2 model with opening animation. This rule ensures all chests work consistently across all levels.

---

## ✅ **STANDARDIZATION**

- **Chest Type:** ALL chests use `type: 'chest2'` (has animation support)
- **Chest1 Deprecated:** `chest1` type automatically converts to `chest2`
- **Model:** All chests use `/textures/3d models/chest2/Chest2.glb`
- **Scale:** All chests use **2.0x scale** (standardized size for visibility and interaction)
- **Animation:** All chests have opening animation (lid rotation)
- **State Management:** All chests hide closed state and show opened state

---

## 🎯 **HOW TO CREATE CHESTS**

### **Step 1: Add Chest to Level**

In your level creation function, add:

```javascript
chestSystem.addChest(LEVEL_IDS.LEVEL1, {
  id: 'chest_001',                    // REQUIRED: Unique chest ID
  type: 'chest2',                      // REQUIRED: Always 'chest2' (standardized)
  position: new THREE.Vector3(55, 1.0, 20),  // REQUIRED: THREE.Vector3
  dspoincAmount: 100,                   // REQUIRED: Base DSPOINC reward
  levelId: 'CHEESE_TEMPLE_LEVEL1'      // REQUIRED: Level identifier for API
});
```

### **Step 2: Position Guidelines**

**X, Z Coordinates:**
- Use level layout to determine X and Z positions
- Use `blockSize` for grid alignment if needed
- Example: `spawnX - 5, spawnZ + 10`

**Y Coordinate:**
- **Level 1, 2:** Use `1.0` (standard ground level, matches bear trap Y position)
- **Level 3:** Use `spawnY` (spawn position Y from level3Config.spawnPosition.y - same approach as Level 5)
- **Level 4, 6:** Use `0.0` (Level 4/6 ground is at Y: 0, floor at Y: 0)
- **Level 5:** Use `spawnY` (dynamic ground detection via raycast - chest Y uses spawn position Y (level5State.spawnPosition.y))
- This ensures chests sit correctly on the ground for each level
- Example Level 1/2: `new THREE.Vector3(x, 1.0, z)`
- Example Level 3: `new THREE.Vector3(x, spawnY, z)` where `spawnY = level3Config.spawnPosition.y`
- Example Level 4/6: `new THREE.Vector3(x, 0.0, z)`
- Example Level 5: `new THREE.Vector3(x, spawnY, z)` where `spawnY = level5State.spawnPosition.y`
- **Note:** Level 3 now uses spawn position Y (same approach as Level 5) - uses level3Config.spawnPosition.y

### **Step 3: Chest ID Naming Convention**

**Format:** `'chest_XXX'` where XXX is zero-padded number

**Examples:**
- `'chest_001'`, `'chest_002'`, `'chest_003'`
- `'chest_010'`, `'chest_025'`, `'chest_100'`

**Rules:**
- Must be unique per level
- Use sequential numbering
- Zero-pad to 3 digits (001, 002, etc.)

### **Step 4: Reward Amount Guidelines**

**Small Chests:** 50-100 DSPOINC
- Common chests, easy to find
- Example: `dspoincAmount: 100`

**Medium Chests:** 100-250 DSPOINC
- Standard chests, moderate difficulty
- Example: `dspoincAmount: 250`

**Large Chests:** 250-500 DSPOINC
- Rare chests, hard to find
- Example: `dspoincAmount: 500`

**Special Chests:** 500+ DSPOINC
- Boss chests, secret chests
- Example: `dspoincAmount: 1000`

**Note:** Rewards are multiplied by player's role multiplier (e.g., VIP = 2.0x)

### **Step 5: Level ID Format**

**Format:** `'CHEESE_TEMPLE_LEVELX'` where X is level number

**Examples:**
- `'CHEESE_TEMPLE_LEVEL1'`
- `'CHEESE_TEMPLE_LEVEL2'`
- `'CHEESE_TEMPLE_LEVEL3'`

### **Step 6: Current Chest Inventory (December 30, 2025)**

**Level 1 Chests:**
- chest_001: X: 10, Y: 1, Z: 10 - 100 DSPOINC
- chest_002: X: 20, Y: 1, Z: 20 - 120 DSPOINC
- chest_003: X: 30, Y: 1, Z: 30 - 150 DSPOINC (elevated)

**Level 2 Chests:**
- chest_004: X: 22.8, Y: 1, Z: 589 - 150 DSPOINC
- chest_005: X: 9.72, Y: 1, Z: 648 - 200 DSPOINC

**Level 3 Chests:**
- chest_006: X: 77, Y: spawnY (spawn position Y), Z: 724 - 180 DSPOINC (Note: Level 3 uses spawn position Y, same approach as Level 5)
- chest_007: X: 55, Y: spawnY (spawn position Y), Z: 805 - 200 DSPOINC (Note: Level 3 uses spawn position Y, same approach as Level 5)

**Level 4 Chests:**
- chest_008: X: 75, Y: 0, Z: 923 - 220 DSPOINC (Note: Level 4 uses Y: 0.0, not Y: 1.0)
- chest_009: X: 54, Y: 0, Z: 1050 - 250 DSPOINC (Note: Level 4 uses Y: 0.0, not Y: 1.0)

**Level 5 Chests:**
- chest_010: X: 33, Y: 1, Z: -41 - 280 DSPOINC (Note: Level 5 uses dynamic ground detection via raycast, chest Y uses spawn position Y)

**Level 6 Chests:**
- chest_011: X: 79, Y: 0, Z: -98 - 300 DSPOINC (Note: Level 6 uses Y: 0.0, same as Level 3/4)

---

## 📝 **COMPLETE EXAMPLE**

### **Creating Multiple Chests in Level 1**

```javascript
function createLevel1Chests(spawnData, blockSize) {
  if (!chestSystem) {
    console.warn("⚠️ [LEVEL 1] Chest system not initialized");
    return;
  }
  
  const spawnX = spawnData ? spawnData.x * blockSize + blockSize / 2 : 60;
  const spawnZ = spawnData ? spawnData.z * blockSize + blockSize / 2 : 15;
  const chestY = 1.0; // ALWAYS 1.0 (matches bear trap)
  
  // Chest 1: Near spawn area
  chestSystem.addChest(LEVEL_IDS.LEVEL1, {
    id: 'chest_001',
    type: 'chest2',
    position: new THREE.Vector3(spawnX - 5, chestY, spawnZ + 10),
    dspoincAmount: 100,
    levelId: 'CHEESE_TEMPLE_LEVEL1'
  });
  
  // Chest 2: Left side
  chestSystem.addChest(LEVEL_IDS.LEVEL1, {
    id: 'chest_002',
    type: 'chest2',
    position: new THREE.Vector3(35, chestY, 50),
    dspoincAmount: 250,
    levelId: 'CHEESE_TEMPLE_LEVEL1'
  });
  
  // Chest 3: Right side (example)
  chestSystem.addChest(LEVEL_IDS.LEVEL1, {
    id: 'chest_003',
    type: 'chest2',
    position: new THREE.Vector3(85, chestY, 65),
    dspoincAmount: 150,
    levelId: 'CHEESE_TEMPLE_LEVEL1'
  });
}
```

---

## 🎮 **WHAT HAPPENS WHEN PLAYER OPENS CHEST**

### **1. Interaction Detection**
- Player approaches chest (within 2.0 units)
- UI shows "Press [E] to Open" prompt (large, visible)

### **2. Opening Animation**
- Player presses E key
- Lid rotates -90 degrees (smooth 1-second animation)
- Sparkling particles appear (50 golden particles)
- Chest glows (emissive effect, fades after 1s)
- Sound plays (`/sounds/SFX/chest.mp3`)

### **3. State Switching**
- Closed chest top is hidden
- Opened chest is shown (with body and handles)
- Only opened state remains visible

### **4. Reward System**
- API call to `/api/dev/riddle-reward.php`
- DSPOINC saved to database (`tbl_riddle_completions`)
- Player balance updated in real-time
- Notification shown with reward amount
- Role multiplier applied (e.g., VIP = 2.0x)

### **5. Counter System**
- Chest counter incremented (stored in localStorage)
- Can be displayed in UI (e.g., profile page, statistics)
- Access: `chestSystem.getChestsOpenedCount()`

### **6. State Management**
- Chest marked as opened (can't be opened again)
- 409 Conflict prevents duplicate rewards
- "Already completed" notification shown if tried again
- Chest state saved to database (`tbl_riddle_completions`)
- Opened chests restored on level load (persistent across sessions)

### **7. Persistence System**
- Opened chests saved to database automatically
- Chest state loaded from database on level load
- Opened chests appear as opened immediately (no animation)
- Player cannot open same chest twice (even across sessions)

---

## 📊 **CHEST COUNTER SYSTEM**

### **How It Works**

The system automatically tracks how many chests the player has opened:

```javascript
// Get current count
const chestsOpened = chestSystem.getChestsOpenedCount();

// Counter is stored in localStorage: 'chests_opened_count'
// Incremented automatically when chest is successfully opened
// Only counts successful rewards (not 409 Conflict duplicates)
```

### **Display in UI**

You can display the counter in profile page, statistics, or HUD:

```javascript
// Example: Display in profile page
const chestsOpened = chestSystem.getChestsOpenedCount();
document.getElementById('chests-opened-count').textContent = chestsOpened;
```

---

## 💾 **CHEST PERSISTENCE SYSTEM**

### **How It Works**

The system automatically saves and restores opened chest states:

1. **On Chest Open:**
   - Reward saved to database (`tbl_riddle_completions`)
   - Chest ID marked as opened in cache
   - State persists across game sessions

2. **On Level Load:**
   - System loads opened chests from database via API
   - Caches opened chest IDs in memory
   - Restores opened visual state for previously opened chests
   - Player cannot interact with opened chests

### **API Endpoint**

- **Endpoint:** `/api/user/get-opened-chests.php`
- **Method:** POST
- **Request:** `{ discord_id: "player_discord_id" }`
- **Response:** List of opened chest IDs with completion data

### **Methods Available**

```javascript
// Load opened chests from database (called automatically on level load)
await chestSystem.loadOpenedChests(discordId, apiBaseUrl);

// Check if a chest is already opened
const isOpened = chestSystem.isChestOpened('chest_001');

// Mark a chest as opened (called automatically after successful reward)
chestSystem.markChestAsOpened('chest_001');

// Restore opened state for a chest (called automatically on load)
chestSystem.restoreChestOpenedState(chest);
```

### **Database Storage**

- **Table:** `tbl_riddle_completions`
- **Riddle ID Format:** `CHEST_${chest.id}` (e.g., 'CHEST_chest_001')
- **Unique Constraint:** Prevents duplicate rewards
- **Persistence:** Survives game restarts, level changes, and sessions

---

## 🚧 **COLLISION DETECTION SYSTEM**

### **How It Works**

Players cannot walk through chests - collision detection prevents it:

1. **Collision Check:**
   - System checks collision between player capsule and all chests
   - Uses bounding box to calculate chest collision radius
   - Only checks closed chests (opened chests don't block movement)

2. **Collision Response:**
   - Player is pushed away from chest if collision detected
   - Velocity toward chest is canceled to prevent sliding
   - Smooth collision response (no jitter)

### **Implementation**

- **Method:** `checkChestCollision(levelId, playerStart, playerEnd, playerRadius)`
- **Called:** Every frame in player movement update
- **Works:** For all levels automatically
- **Collision Radius:** Based on chest bounding box (larger of X or Z dimension)

### **Features**

- ✅ Players cannot walk through closed chests
- ✅ Opened chests don't block movement (collision disabled)
- ✅ Smooth collision response (pushes player away)
- ✅ Prevents getting stuck (small buffer added)
- ✅ Works for all levels automatically

---

## ✅ **REQUIRED FIELDS**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | string | ✅ Yes | Unique chest ID (e.g., 'chest_001') |
| `type` | string | ✅ Yes | Always 'chest2' (standardized) |
| `position` | THREE.Vector3 | ✅ Yes | Chest position (Y must be 1.0) |
| `dspoincAmount` | number | ✅ Yes | Base DSPOINC reward amount |
| `levelId` | string | ✅ Yes | Level identifier (e.g., 'CHEESE_TEMPLE_LEVEL1') |

---

## ⚠️ **IMPORTANT RULES**

### **✅ DO:**
- ✅ Always use `type: 'chest2'` (standardized, has animation)
- ✅ Always use correct Y position for level:
  - Level 1, 2: `Y position: 1.0` (standard ground level)
  - Level 3: `Y position: spawnY` (use level3Config.spawnPosition.y - same approach as Level 5)
  - Level 4, 6: `Y position: 0.0` (Level 4/6 ground level, floor at Y: 0)
  - Level 5: `Y position: spawnY` (use level5State.spawnPosition.y - dynamic ground detection)
- ✅ Always use unique chest IDs (chest_001, chest_002, etc.)
- ✅ Always use correct levelId format (CHEESE_TEMPLE_LEVELX)
- ✅ Use sequential numbering for chest IDs
- ✅ Test chest opening animation works
- ✅ Verify closed state is hidden after opening
- ✅ Check level's ground height before positioning chests

### **❌ DON'T:**
- ❌ Don't use `type: 'chest1'` (deprecated, auto-converts to chest2)
- ❌ Don't use wrong Y position for level (Level 1/2 use 1.0, Level 3 uses spawnY, Level 4/6 use 0.0, Level 5 uses spawnY)
- ❌ Don't assume all levels use Y: 1.0 (Level 3 uses spawnY, Level 4/6 use Y: 0.0, Level 5 uses dynamic detection)
- ❌ Don't use duplicate chest IDs (will cause conflicts)
- ❌ Don't forget to set levelId (required for API)
- ❌ Don't skip testing (verify animation and state switching)

---

## 🔧 **TECHNICAL DETAILS**

### **Model Path**
- Standard: `/textures/3d models/chest2/Chest2.glb`
- Fallback: `/textures/3d models/chest2/chest2.glb` (lowercase)

### **Scale**
- **Standardized:** All chests use **2.0x scale** (applied automatically)
- **Purpose:** Makes chests more visible and easier to interact with
- **Applied:** Automatically when chest model loads
- **Note:** Scale is uniform (2.0x on all axes) for consistent appearance

### **Animation System**
- Manual lid rotation: -90 degrees around X-axis
- Duration: 1000ms (1 second)
- Easing: Cubic ease-out
- State switching: Hides closed, shows opened

### **Duplicate Lid Detection (CRITICAL)**
The chest2 model contains both closed and opened lid meshes. After opening, the closed lid must be hidden.

**3-Pass Detection System:**
1. **First Pass:** Hide duplicate lids found during load (stored in `this.duplicateLidMeshes`)
2. **Second Pass:** Search for all lid meshes and hide duplicates (catches nested/grouped meshes)
3. **Final Pass:** Count ALL meshes in model, if > 4 visible, find and hide duplicate lids

**Expected Visible Meshes After Opening: 4 total**
- Chest_Body (1)
- Chest_Handle_01 (1)
- Chest_Handle_02 (1)
- Chest_Lid (1) - the rotated/opened lid

**If more than 4 meshes are visible, duplicate lids are present and must be hidden.**

**Implementation:**
- The `switchToOpenedState()` function uses a 3-pass system to ensure all duplicate lids are hidden
- Final pass traverses entire chest model and counts all meshes
- All lids except the main rotated lid are hidden
- Body and handles are always kept visible

### **Interaction Radius**
- Default: 2.0 units
- Player must be within 2.0 units to interact
- UI prompt appears automatically

### **API Integration**

**Reward API:**
- Endpoint: `/api/dev/riddle-reward.php`
- Method: POST
- Riddle ID format: `CHEST_${chest.id}` (e.g., 'CHEST_chest_001')
- Prevents duplicates: 409 Conflict handling

**Persistence API:**
- Endpoint: `/api/user/get-opened-chests.php`
- Method: POST
- Request: `{ discord_id: "player_discord_id" }`
- Response: List of opened chest IDs with completion data
- Called automatically on level load

---

## 📚 **FILES REFERENCE**

### **Main Files:**
- `three.js/chest-system.js` - Chest system implementation
- `three.js/main.js` - Chest creation and reward callback
- `three.js/gui-system.js` - Interaction prompt UI
- `three.js/player-controls.js` - E key detection

### **API Files:**
- `api/dev/riddle-reward.php` - Reward API endpoint
- `api/user/get-opened-chests.php` - Persistence API endpoint (loads opened chests)

### **Database:**
- `tbl_riddle_completions` - Stores chest completion records (persistence)
- `tbl_user_scores` - Stores player DSPOINC balance

---

## 🎯 **QUICK REFERENCE**

### **Minimal Chest Creation:**
```javascript
chestSystem.addChest(LEVEL_IDS.LEVEL1, {
  id: 'chest_001',
  type: 'chest2',
  position: new THREE.Vector3(55, 1.0, 20),
  dspoincAmount: 100,
  levelId: 'CHEESE_TEMPLE_LEVEL1'
});
```

### **Get Chests Opened Count:**
```javascript
const count = chestSystem.getChestsOpenedCount();
```

### **Check if Chest Exists:**
```javascript
const chest = chestSystem.getChest(LEVEL_IDS.LEVEL1, 'chest_001');
if (chest && chest.opened) {
  // Chest already opened
}
```

### **Check if Chest is Opened (Persistence):**
```javascript
// Check if chest was previously opened (from database)
const isOpened = chestSystem.isChestOpened('chest_001');
if (isOpened) {
  // Chest was already opened in a previous session
}
```

### **Load Opened Chests:**
```javascript
// Load opened chests from database (usually called automatically)
await chestSystem.loadOpenedChests(discordId, API_BASE_URL);
```

---

## 📝 **NOTES FOR FUTURE CHEST CREATION**

When creating hundreds of chests:

1. **Use consistent naming:** chest_001, chest_002, chest_003, etc.
2. **Use consistent Y position:** 
   - Level 1 & 2: Always 1.0
   - Level 3: Always 0.0 (ground level differs)
   - Check level's ground height before positioning
3. **Use consistent type:** Always 'chest2'
4. **Use consistent levelId format:** CHEESE_TEMPLE_LEVELX
5. **Test each chest:** Verify animation and state switching work
6. **Document positions:** Keep track of where chests are placed
7. **Balance rewards:** Vary reward amounts based on difficulty

### **Level-Specific Ground Heights (December 30, 2025):**
- **Level 1:** Y: 1.0 (standard ground level)
- **Level 2:** Y: 1.0 (standard ground level)
- **Level 3:** spawnY (spawn position Y from level3Config.spawnPosition.y - same approach as Level 5)
- **Level 4:** Y: 0.0 (floor at Y: 0 - use 0.0 for chests)
- **Level 5:** Dynamic ground detection via raycast (chest Y uses spawn position Y from level5State.spawnPosition.y)
- **Level 6:** Y: 0.0 (ground plane at Y: 0 - use 0.0 for chests)
- **Level 7+:** Check level configuration for ground height

---

---

## 🎯 **SYSTEM FEATURES SUMMARY**

### **Core Features:**
- ✅ **Standardized chest2 model** (all chests use same model)
- ✅ **Opening animation** (lid rotation, particles, sound, glow)
- ✅ **State management** (closed → opened visual transition)
- ✅ **Reward system** (DSPOINC with role multipliers)
- ✅ **Counter system** (tracks opened chests)
- ✅ **Collision detection** (players cannot walk through chests)
- ✅ **Persistence system** (opened chests saved/restored)
- ✅ **Duplicate prevention** (409 Conflict handling)

### **Technical Features:**
- ✅ **3-pass duplicate detection** (ensures closed lid is hidden)
- ✅ **Bounding box positioning** (accurate Y positioning)
- ✅ **Automatic state restoration** (opened chests appear opened on load)
- ✅ **Database persistence** (survives game restarts)
- ✅ **Universal collision** (works for all levels)

---

**STATUS:** ✅ **PRODUCTION READY - ALL CHESTS USE CHEST2 - ANIMATION, COLLISION & PERSISTENCE WORKING PERFECTLY**
