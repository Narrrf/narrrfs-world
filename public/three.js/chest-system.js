/**
 * ============================================================================
 * CHEST SYSTEM - Treasure Chest Management
 * ============================================================================
 * 
 * ✅ STATUS: STABLE - PRODUCTION READY - ALL LEVELS COMPLETE
 * 📅 CREATED: December 2025
 * 📅 LAST UPDATED: January 9, 2026
 * 🎯 MILESTONE: Stable Production Version - Level 1 verified working
 * ✅ Version: 2026-01-09-STABLE-PRODUCTION
 * Previous: January 4, 2026 - Enhanced chest clearing system with level isolation guarantee
 * 
 * ✅ ALL LEVELS HAVE WORKING CHESTS (December 30, 2025):
 * ======================================================
 * - Level 1: ✅ 3 chests (chest_001, chest_002, chest_003)
 * - Level 2: ✅ 2 chests (chest_004, chest_005)
 * - Level 3: ✅ 2 chests (chest_006, chest_007) - Fixed: Uses spawn position Y (same approach as Level 5)
 * - Level 4: ✅ 2 chests (chest_008, chest_009)
 * - Level 5: ✅ 1 chest (chest_010) - Uses dynamic ground detection via raycast
 * - Level 6: ✅ 1 chest (chest_011)
 * Total: 11 chests across all 6 levels, all working correctly with proper ground positioning
 * 
 * ============================================================================
 * 🎯 PURPOSE
 * ============================================================================
 * 
 * Game-wide treasure chest system for easy integration across all levels:
 * - Chest spawning and management
 * - Player interaction (E key)
 * - Animation system (lid opening)
 * - Reward system (DSPOINC via API)
 * - Visual effects (particles, glow)
 * - Sound effects
 * - Collision detection
 * - Persistence (database)
 * - Grass exclusion zones (automatic registration)
 * 
 * ============================================================================
 * ✅ STABLE VERSION STATUS
 * ============================================================================
 * 
 * ✅ **STABLE VERSION - PRODUCTION READY - CHEST DUPLICATE DETECTION WORKING**
 * 
 * 🚨 CRITICAL: This is the WORKING version with functional duplicate lid detection!
 * - Restored from working backup on December 19, 2025
 * - DO NOT modify duplicate detection logic without reading full documentation below
 * - The rotation-based check (lines ~1842-1900) is THE KEY WORKING MECHANISM
 * 
 * This system has reached a stable, production-ready state with:
 * - ✅ Chest2 animation system working perfectly (lid rotation, duplicate detection)
 * - ✅ Duplicate lid detection working (6-pass system with rotation-based check)
 * - ✅ Grass exclusion zone system working (no grass under chests)
 * - ✅ Reward system integrated with API (DSPOINC rewards)
 * - ✅ Visual effects (sparkling particles, emissive glow)
 * - ✅ Sound effects (opening sound with fallback)
 * - ✅ Interaction system (E key detection, UI prompts)
 * - ✅ Collision detection (players cannot walk through chests)
 * - ✅ Persistence system (opened state saved/restored from database)
 * - ✅ Level warping persistence (chests restored with opened state when warping between levels) (January 4, 2026)
 * - ✅ Uneven terrain positioning (Level 5 chests use raycast at chest position, not spawn position) (January 4, 2026)
 * - ✅ Scale factor positioning fix (Y position calculation accounts for 2.0x scale factor) (January 4, 2026)
 * - ✅ Final Y position fix (added 1.0 offset to compensate for underground issue) (January 4, 2026) - All chests now correctly positioned on ground
 * - ✅ Enhanced chest clearing system (improved logging, level isolation guarantee, triple-clearing safety) (January 4, 2026)
 * - ✅ Warp duplicate prevention (async load cancellation + disposed-chest guards) (January 15, 2026)
 *   - Fixes: “double chests stacked” and “chests from other levels appear” during fast GOD-mode warps
 *   - Implementation: `Chest.isDisposed`, `_loadTimeoutId`, and guards in `ChestSystem.addChest()` delayed loader
 * 
 * **CHEST PERSISTENCE ON LEVEL WARPING (January 4, 2026):**
 * ==========================================================
 * When warping between levels, chests are correctly restored with their opened state:
 * 
 * 1. **cleanupAllLevels()** clears all chests from all levels when switching levels
 * 2. **loadOpenedChests()** is called BEFORE creating chests for ALL levels (Levels 1-6)
 *    - This loads opened chest data from the database into memory
 * 3. **restoreChestOpenedState()** is called AFTER each chest model loads in addChest()
 *    - This checks if the chest was previously opened and restores its opened state
 * 4. **Warp functions** recreate chests when warping back to already-built levels:
 *    - warpToLevel2/3/4/5/6() check if level is already built
 *    - If already built, chests are recreated after cleanupAllLevels() clears them
 *    - Opened chests are loaded from database and restored correctly
 * 
 * This ensures:
 * - ✅ Chests load correctly when warping to any level
 * - ✅ Chests that were opened display as opened (lid rotated)
 * - ✅ Chests that were not opened display as closed and ready to open
 * - ✅ Each chest's opened state is tracked on the player's profile
 * - ✅ Works correctly when warping between levels multiple times
 * 
 * **CRITICAL IMPLEMENTATION NOTES (January 4, 2026):**
 * - loadOpenedChests() must be called BEFORE creating chests for ALL levels
 * - restoreChestOpenedState() is automatically called after chest model loads in addChest()
 * - Warp functions must recreate chests even if level is already built
 * 
 * **CHEST SCALE FACTOR POSITIONING (January 4, 2026):**
 * =====================================================
 * CRITICAL: All chests are scaled to 2.0x for visibility and interaction.
 * The Y position calculation MUST account for this scale factor, otherwise chests will appear
 * underground or floating.
 * 
 * How it works:
 * 1. Calculate bounding box bottom in model space (unscaled)
 * 2. Apply scale factor: scaledBoundingBoxBottom = boundingBoxBottom * 2.0
 * 3. Calculate position: calculatedY = targetBottomY - scaledBoundingBoxBottom
 * 4. Set position first, then apply 2.0x scale
 * 
 * This ensures that after scaling, the chest's bottom sits exactly at the target Y position.
 * 
 * **FINAL Y POSITION FIX (January 4, 2026):**
 * ============================================
 * After implementing scale factor positioning, chests were still appearing 1 unit underground.
 * Final fix: Added +1.0 offset to the calculated Y position to compensate.
 * 
 * Formula: calculatedY = targetBottomY - scaledBoundingBoxBottom + 1.0
 * 
 * This ensures all chests (Levels 1-6) sit correctly on the ground visually.
 * 
 * Example:
 * - Target Y: 0.0 (ground level)
 * - Model bottom (unscaled): -0.5
 * - Scaled bottom: -0.5 * 2.0 = -1.0
 * - Calculated Y: 0.0 - (-1.0) = 1.0
 * - Result: Chest center at Y: 1.0, bottom at Y: 0.0 after 2.0x scale ✅
 * 
 * If scale is NOT accounted for:
 * - Calculated Y: 0.0 - (-0.5) = 0.5
 * - After 2.0x scale: Bottom at Y: 0.5 + (-1.0) = -0.5 ❌ (underground!)
 * 
 * NEVER modify the scale calculation without accounting for it in position calculation!
 * Implementation: chest-system.js lines ~1080-1133 (accounting for CHEST_SCALE = 2.0)
 * 
 * **REFERENCE IMPLEMENTATION:**
 * - Level 1 chests (chest_001, chest_002, chest_003) are the standard for all future chests
 * - Level 2 chests (chest_004, chest_005, ...) follow the same pattern
 * - All future chests MUST follow the Level 1 pattern
 * - No grass under chests (automatic exclusion zone registration)
 * - Proper animation (lid opens, duplicates hidden)
 * 
 * **CHEST ID NUMBERING (December 30, 2025):**
 * ===========================================
 * - Level 1: chest_001, chest_002, chest_003
 * - Level 2: chest_004, chest_005, chest_006, ...
 * - Level 3: chest_007, chest_008, ...
 * - And so on for all levels
 * 
 * **LEVEL 2 CHESTS (January 4, 2026):**
 * =======================================
 * - chest_004: X: 22.8, Y: 0.0 (ground level), Z: 589 - 150 DSPOINC
 * - chest_005: X: 9.72, Y: 0.0 (ground level), Z: 648 - 200 DSPOINC
 *   ✅ FIXED: Level 2 uses Y: 0.0 for ground level (level2Config.origin.y = 0, spawnPosition.y = 0)
 *   Previous code used Y: 1.0 which caused chests to appear 1 unit too high
 *   Now correctly uses level2Config.origin.y (0.0) for ground level
 * 
 * **LEVEL 3 CHESTS (December 30, 2025):**
 * =======================================
 * - chest_006: X: 77, Y: spawnY (spawn position Y), Z: 724 - 180 DSPOINC (Level 3 uses spawn position Y, same approach as Level 5)
 * - chest_007: X: 55, Y: spawnY (spawn position Y), Z: 805 - 200 DSPOINC (Level 3 uses spawn position Y, same approach as Level 5)
 * 
 * **LEVEL 4 CHESTS (December 30, 2025):**
 * =======================================
 * - chest_008: X: 75, Y: 0, Z: 923 - 220 DSPOINC (Level 4 ground is at Y: 0, not Y: 1)
 * - chest_009: X: 54, Y: 0, Z: 1050 - 250 DSPOINC (Level 4 ground is at Y: 0, not Y: 1)
 * 
 * **LEVEL 5 CHESTS (December 30, 2025):**
 * =======================================
 * - chest_010: X: 33, Y: detected ground level at chest position, Z: -41 - 280 DSPOINC
 *   ✅ WORKING: Level 5 uses dynamic ground detection via raycast at chest's specific X/Z position (January 4, 2026)
 *   CRITICAL: Level 5 has uneven terrain (city map), so ground level varies by position
 *   - Chest Y is detected via raycast at chest position (33, -41), NOT at spawn position (0, 0)
 *   - This ensures chest sits correctly on ground even if terrain differs from spawn position
 *   - Raycast finds highest intersection near chest position within 1 unit radius
 *   - Falls back to spawn Y if raycast fails or map mesh unavailable
 *   - Implementation: createLevel5Chests() does raycast at chest position before creating chest
 * 
 * **LEVEL 6 CHESTS (December 30, 2025):**
 * =======================================
 * - chest_011: X: 79, Y: 0.0 (ground level), Z: -98 - 300 DSPOINC (Level 6 ground is at Y: 0.0, same as Level 3/4)
 * 
 * ============================================================================
 * 📦 WHAT THIS MODULE LOADS
 * ============================================================================
 * 
 * Chest Models:
 * - GLTF format (chest2.gltf - has animation)
 * - Path: /textures/3d models/chest2/chest2.gltf
 * 
 * Audio:
 * - Opening sound: /audio/gameplay/chest_open.ogg
 * 
 * Dependencies:
 * - THREE.js Scene
 * - THREE.js GLTFLoader
 * - Player object (for interaction detection)
 * - GrassSystem (for exclusion zone registration)
 * - API endpoints (for rewards)
 * 
 * ============================================================================
 * 🔧 INTEGRATION IN MAIN.JS
 * ============================================================================
 * 
 * 1. Import:
 *    import { ChestSystem } from "./chest-system.js";
 * 
 * 2. Initialize (usually in weapon system initialization):
 *    chestSystem = new ChestSystem({
 *      scene: scene,
 *      player: player,
 *      getGrassSystem: () => grassSystem,
 *      getAPIBaseURL: () => API_BASE_URL
 *    });
 * 
 * 3. Create chests for level:
 *    chestSystem.createChestsForLevel(levelId, chestData);
 * 
 * 4. Update in game loop (for interactions):
 *    if (chestSystem) {
 *      chestSystem.update(delta);
 *    }
 * 
 * ============================================================================
 * 🎮 KEY FUNCTIONS
 * ============================================================================
 * 
 * - createChestsForLevel(levelId, chestData) - Create chests for level
 * - update(delta) - Update chest system (check interactions)
 * - registerExclusionZones() - Register grass exclusion zones
 * - resetOpenedChests(levelId) - Reset opened chests (admin)
 * 
 * ============================================================================
 * 🏗️ SYSTEM ARCHITECTURE - MULTI-LEVEL SCALING (December 18, 2025)
 * ============================================================================
 * 
 * This chest system is designed to scale across UNLIMITED levels for decades.
 * 
 * ARCHITECTURE OVERVIEW:
 * ======================
 * 
 * Data Structure: Map<levelId, Map<chestId, Chest>>
 * - Outer Map: Keys are level IDs (e.g., 'LEVEL1', 'LEVEL2', 'LEVEL3')
 * - Inner Map: Keys are chest IDs (e.g., 'chest_001', 'chest_002', 'chest_003')
 * - Values: Chest instances with full state (mesh, position, opened status)
 * 
 * MULTI-LEVEL ISOLATION:
 * ======================
 * 
 * Each level has its own chest collection:
 * - Level 1 chests: chestSystem.chests.get('LEVEL1')
 * - Level 2 chests: chestSystem.chests.get('LEVEL2')
 * - Level 3 chests: chestSystem.chests.get('LEVEL3')
 * - And so on for 100+ levels...
 * 
 * Benefits:
 * - ✅ No interference between levels (Level 1 chest_001 ≠ Level 2 chest_001)
 * - ✅ Fast lookup by level (O(1) access to level-specific chests)
 * - ✅ Easy cleanup (remove entire level's chests at once)
 * - ✅ Memory efficient (only current level's chests are loaded)
 * 
 * SCALING ACROSS 100+ LEVELS:
 * ===========================
 * 
 * The system handles unlimited levels without performance degradation:
 * 
 * Example for 100 levels:
 * - Level 1: 5 chests → chestSystem.chests.get('LEVEL1') = Map(5)
 * - Level 2: 8 chests → chestSystem.chests.get('LEVEL2') = Map(8)
 * - Level 3: 3 chests → chestSystem.chests.get('LEVEL3') = Map(3)
 * - ...
 * - Level 100: 10 chests → chestSystem.chests.get('LEVEL100') = Map(10)
 * 
 * Performance characteristics:
 * - Adding chest: O(1) - direct Map insertion
 * - Finding chest: O(1) - Map.get(levelId).get(chestId)
 * - Clearing level: O(n) where n = chests in that level only
 * - Memory: Only stores data for currently loaded levels
 * 
 * LEVEL LIFECYCLE:
 * ================
 * 
 * When player enters a level:
 * 1. createLevelXChests() adds chests to that level's Map
 * 2. Chests load asynchronously (50ms delay per chest)
 * 3. Grass exclusion zones register automatically
 * 4. Opened chests restore from database (persistence)
 * 
 * When player leaves a level:
 * 1. cleanupAllLevels() or clearLevel() called
 * 2. All chest meshes removed from scene
 * 3. All chest resources disposed (geometry, materials)
 * 4. Level's chest Map cleared or entire Map removed
 * 5. Memory freed for next level
 * 
 * BEST PRACTICES FOR ORGANIZING CHESTS:
 * ======================================
 * 
 * 1. CONSISTENT NAMING:
 *    - Level 1: chest_001, chest_002, chest_003, ...
 *    - Level 2: chest_001, chest_002, chest_003, ... (same IDs, different level)
 *    - Level 3: chest_001, chest_002, chest_003, ...
 *    - No conflict between levels! (isolated by level ID)
 * 
 * 2. FUNCTION ORGANIZATION:
 *    - createLevel1Chests(spawnData, blockSize) - Level 1 chests
 *    - createLevel2Chests(spawnData, blockSize) - Level 2 chests
 *    - createLevel3Chests(spawnData, blockSize) - Level 3 chests
 *    - Each level has its own chest creation function
 * 
 * 3. POSITION PATTERNS:
 *    - Ground level: Y = 1.0 (standard)
 *    - Elevated: Y > 5.0 (towers, platforms, floating islands)
 *    - Underground: Y < -4.0 (caves, dungeons, sewers)
 * 
 * 4. REWARD SCALING:
 *    - Early levels (1-10): 50-250 DSPOINC
 *    - Mid levels (11-50): 250-500 DSPOINC
 *    - Late levels (51-100): 500-1000+ DSPOINC
 *    - Boss levels: 1000-5000 DSPOINC
 * 
 * MEMORY MANAGEMENT:
 * ==================
 * 
 * Automatic cleanup prevents memory leaks:
 * - Chest meshes removed from scene on level change
 * - Geometries and materials disposed properly
 * - Event listeners cleaned up
 * - References cleared from Maps
 * 
 * Methods:
 * - clearLevel(levelId) - Remove specific level's chests
 * - clearAllChests() - Remove ALL chests (used on game exit)
 * 
 * PERFORMANCE OPTIMIZATION:
 * =========================
 * 
 * The system is optimized for large-scale games:
 * - Only current level's chests are loaded (lazy loading)
 * - Collision detection only checks current level
 * - Interaction checks only current level
 * - Database queries filtered by level
 * - No global chest array (level-isolated Maps)
 * 
 * EXAMPLE: Creating chests for 10 levels:
 * ========================================
 * 
 * function createLevel1Chests(spawnData, blockSize) {
 *   chestSystem.addChest(LEVEL_IDS.LEVEL1, { ... });
 * }
 * 
 * function createLevel2Chests(spawnData, blockSize) {
 *   chestSystem.addChest(LEVEL_IDS.LEVEL2, { ... });
 * }
 * 
 * // ... and so on for all levels
 * 
 * Called from buildLevel() based on currentLevel:
 * if (currentLevel === LEVEL_IDS.LEVEL1) createLevel1Chests(...);
 * if (currentLevel === LEVEL_IDS.LEVEL2) createLevel2Chests(...);
 * // etc.
 * 
 * ============================================================================
 * 📖 HOW TO CREATE CHESTS - COMPLETE GUIDE
 * ============================================================================
 * 
 * STANDARDIZED: All chests use chest2 (has animation support)
 * - chest1 type is deprecated and automatically converts to chest2
 * - All chests use 2.0x scale (standardized size for visibility and interaction)
 * - All chests have opening animation (lid rotation)
 * - All chests hide closed state and show opened state after opening
 * 
 * 🚨 CRITICAL REQUIREMENTS FOR ALL FUTURE CHESTS (December 16, 2025):
 * ===================================================================
 * 
 * ALL chests MUST follow the Level 1 chest pattern to ensure:
 * 1. ✅ NO GRASS UNDER CHESTS - Grass exclusion zone system automatically registers
 *    - Chests automatically register with grass system when loaded
 *    - Exclusion zones prevent grass from rendering inside/under chest models
 *    - No manual setup required - works automatically via auto-registration
 * 
 * 2. ✅ PROPER ANIMATION - Duplicate lid detection system
 *    - Opening animation rotates lid -90 degrees (smooth animation)
 *    - All duplicate closed lid meshes are automatically hidden
 *    - Rotation-based detection ensures only opened lid remains visible
 *    - Works perfectly for all chest2 models
 * 
 * 3. ✅ GRASS SYSTEM INTEGRATION - Automatic exclusion zone registration
 *    - Chests auto-register exclusion zones after loading (200ms delay)
 *    - Grass regenerates automatically after chest registration
 *    - Exclusion zones use bounding box with 0.5 unit padding
 *    - No grass renders inside or under chest models
 * 
 * VERIFICATION: Level 1 chests (chest_001, chest_002) are the reference implementation
 * - ✅ No grass under chests (verified December 16, 2025)
 * - ✅ Animation works perfectly (lid opens, duplicates hidden)
 * - ✅ All meshes visible correctly (body, handles, rotated lid only)
 * 
 * WHEN CREATING NEW CHESTS:
 * - Always use type: 'chest2' (standardized)
 * - Always use Y position: 1.0 (matches bear trap)
 * - System handles grass exclusion and animation automatically
 * - Follow the Level 1 chest pattern exactly
 * 
 * ----------------------------------------------------------------------------
 * STEP 1: Add Chest to Level
 * ----------------------------------------------------------------------------
 * 
 * In your level creation function (e.g., createLevel1Chests), add:
 * 
 *   chestSystem.addChest(LEVEL_IDS.LEVEL1, {
 *     id: 'chest_001',                    // REQUIRED: Unique chest ID (e.g., 'chest_001', 'chest_002')
 *     type: 'chest2',                      // REQUIRED: Always use 'chest2' (standardized, has animation)
 *     position: new THREE.Vector3(55, 1.0, 20),  // REQUIRED: THREE.Vector3 position
 *                                            // Y position should be 1.0 (matches bear trap)
 *     dspoincAmount: 100,                   // REQUIRED: Base DSPOINC reward amount
 *     levelId: 'CHEESE_TEMPLE_LEVEL1'      // REQUIRED: Level identifier for API
 *   });
 * 
 * ----------------------------------------------------------------------------
 * STEP 2: Position Guidelines (GROUND AND ELEVATED CHESTS)
 * ----------------------------------------------------------------------------
 * 
 * GROUND-LEVEL CHESTS (standard):
 * - X, Z: Set based on level layout (use blockSize for grid alignment)
 * - Y: 1.0 (standard ground level, matches bear trap)
 * - Example: new THREE.Vector3(spawnX - 5, 1.0, spawnZ + 10)
 * - Grass exclusion: Automatic (no grass under chest)
 * 
 * ELEVATED CHESTS (towers, platforms, floating islands):
 * - X, Z: Set based on level layout
 * - Y: > 5.0 (triggers custom Y positioning mode)
 * - Example: new THREE.Vector3(82, 29, 39) // Tower top at Y=29
 * - Grass exclusion: Automatic (works at any Y height)
 * - Protection: Add chest ID to verifyAndFixLevel1ChestPositions() skip list
 * 
 * UNDERGROUND CHESTS (caves, dungeons):
 * - X, Z: Set based on level layout
 * - Y: < -4.0 (triggers custom Y positioning mode)
 * - Example: new THREE.Vector3(50, -10, 50) // Underground cave at Y=-10
 * - Grass exclusion: Automatic (prevents grass at entrance)
 * 
 * CRITICAL: useCustomY Flag (automatic):
 * - If |Y - 1.0| > 5.0 → useCustomY = true (chest-system.js line 615)
 * - System preserves custom Y position (won't force to Y=1.0)
 * - Grass exclusion works at ANY Y position
 * - No special code needed - automatic detection!
 * 
 * ----------------------------------------------------------------------------
 * STEP 3: Chest ID Naming Convention
 * ----------------------------------------------------------------------------
 * 
 * Format: 'chest_XXX' where XXX is zero-padded number
 * Examples:
 *   - 'chest_001', 'chest_002', 'chest_003'
 *   - 'chest_010', 'chest_025', 'chest_100'
 * 
 * ----------------------------------------------------------------------------
 * STEP 4: Reward Amount Guidelines
 * ----------------------------------------------------------------------------
 * 
 * - Small chests: 50-100 DSPOINC
 * - Medium chests: 100-250 DSPOINC
 * - Large chests: 250-500 DSPOINC
 * - Special chests: 500+ DSPOINC
 * 
 * Note: Rewards are multiplied by player's role multiplier (e.g., VIP = 2.0x)
 * 
 * ----------------------------------------------------------------------------
 * STEP 5: Level ID Format
 * ----------------------------------------------------------------------------
 * 
 * Format: 'CHEESE_TEMPLE_LEVELX' where X is level number
 * Examples:
 *   - 'CHEESE_TEMPLE_LEVEL1'
 *   - 'CHEESE_TEMPLE_LEVEL2'
 *   - 'CHEESE_TEMPLE_LEVEL3'
 * 
 * ----------------------------------------------------------------------------
 * COMPLETE EXAMPLE - Creating Multiple Chests
 * ----------------------------------------------------------------------------
 * 
 * function createLevel1Chests(spawnData, blockSize) {
 *   const spawnX = spawnData.x * blockSize + blockSize / 2;
 *   const spawnZ = spawnData.z * blockSize + blockSize / 2;
 *   const chestY = 1.0; // Standard Y: 1.0 (matches bear trap for ground-level chests)
 *                       // For elevated chests (tower tops, platforms): Use Y > 5.0
 *                       // For underground chests: Use Y < -4.0
 * 
 *   // Chest 1: Near spawn
 *   chestSystem.addChest(LEVEL_IDS.LEVEL1, {
 *     id: 'chest_001',
 *     type: 'chest2',
 *     position: new THREE.Vector3(spawnX - 5, chestY, spawnZ + 10),
 *     dspoincAmount: 100,
 *     levelId: 'CHEESE_TEMPLE_LEVEL1'
 *   });
 * 
 *   // Chest 2: Left side
 *   chestSystem.addChest(LEVEL_IDS.LEVEL1, {
 *     id: 'chest_002',
 *     type: 'chest2',
 *     position: new THREE.Vector3(35, chestY, 50),
 *     dspoincAmount: 250,
 *     levelId: 'CHEESE_TEMPLE_LEVEL1'
 *   });
 * 
 *   // Chest 3: Tower top (custom Y positioning - elevated chest)
 *   chestSystem.addChest(LEVEL_IDS.LEVEL1, {
 *     id: 'chest_003',
 *     type: 'chest2',
 *     position: new THREE.Vector3(82, 29, 39), // Y: 29 (elevated, chest bottom sits flush on tower top surface)
 *     dspoincAmount: 500,
 *     levelId: 'CHEESE_TEMPLE_LEVEL1'
 *   });
 * }
 * 
 * ----------------------------------------------------------------------------
 * WHAT HAPPENS WHEN PLAYER OPENS CHEST
 * ----------------------------------------------------------------------------
 * 
 * 1. Player approaches chest (within 2.0 units)
 * 2. UI shows "Press [E] to Open" prompt
 * 3. Player presses E key
 * 4. Chest opens:
 *    - Lid rotates -90 degrees (opening animation)
 *    - Sparkling particles appear (50 golden particles)
 *    - Chest glows (emissive effect, fades after 1s)
 *    - Sound plays (/sounds/SFX/chest.mp3)
 *    - Closed chest top is hidden (3-pass duplicate detection system)
 *    - Opened chest is shown (with body and handles)
 * 5. Reward is awarded:
 *    - API call to /api/dev/riddle-reward.php
 *    - DSPOINC saved to database (tbl_riddle_completions)
 *    - Player balance updated
 *    - Notification shown with reward amount
 * 6. Chest counter incremented (for statistics)
 * 7. Chest marked as opened (can't be opened again)
 * 
 * ----------------------------------------------------------------------------
 * DUPLICATE LID DETECTION SYSTEM (CRITICAL) - WORKING MECHANISM DOCUMENTED
 * ----------------------------------------------------------------------------
 * 
 * 🚨 CRITICAL: THIS SYSTEM WORKS - DO NOT MODIFY WITHOUT UNDERSTANDING IT FIRST!
 * 
 * Date Documented: December 19, 2025
 * Status: ✅ WORKING - Verified with chest_001 and chest_002 (restored from working backup)
 * 
 * PROBLEM:
 * ========
 * The chest2.glb model contains BOTH closed and opened lid meshes simultaneously.
 * When the chest opens, both lids are visible, creating a "double lid" visual bug.
 * 
 * SOLUTION - 6-PASS DETECTION SYSTEM:
 * ====================================
 * 
 * The system uses 6 PASSES to ensure all duplicate lids are caught:
 * 
 * PASS 1: Hide duplicateLidMeshes (lines ~1569-1574)
 * - During load, all lid meshes except main lid are stored in duplicateLidMeshes
 * - These are hidden first when chest opens
 * 
 * PASS 2: Search for all lids by name (lines ~1599-1610)
 * - Traverse entire model looking for meshes with "lid", "top", or "cover" in name
 * - Hide any lid that is NOT the main lid (this.lidMesh)
 * 
 * PASS 3: Hide duplicateLidMeshes again (lines ~1669-1672)
 * - Redundant safety check
 * 
 * PASS 4: Aggressive cleanup if > 4 visible meshes (lines ~1750-1761)
 * - If still have too many visible meshes, find ALL lids
 * - Hide all lids except the main lid
 * 
 * PASS 5: FINAL PASS - Count all meshes (lines ~1820-1832)
 * - Count ALL meshes in entire model
 * - If > 4 visible, find all lids and hide all except main lid
 * 
 * PASS 6: 🎯 ROTATION-BASED CHECK - THE KEY WORKING MECHANISM (lines ~1842-1900)
 * ============================================================================
 * 
 * THIS IS THE CRITICAL FIX THAT ACTUALLY WORKS:
 * 
 * Why it works:
 * - The main lid (this.lidMesh) gets ROTATED to -90 degrees (rotation.x ≈ -1.57)
 * - Any lid that stays at rotation.x ≈ 0 is a duplicate closed lid
 * - By checking ROTATION instead of names, we catch duplicates regardless of naming
 * 
 * How it works:
 * 1. Find ALL lid meshes (by name: "lid", "top", "cover")
 * 2. Find the ROTATED lid (rotation.x ≈ -1.57, which is -90 degrees)
 * 3. Hide ANY lid that:
 *    - Is in closed position (rotation.x ≈ 0)
 *    - Is NOT the rotated lid
 * 
 * Code Logic (simplified):
 * ```
 * const rotatedLid = allLidMeshes.find(l => Math.abs(l.rotationX + Math.PI / 2) < 0.15);
 * 
 * allLidMeshes.forEach(({ mesh, rotationX }) => {
 *   const isClosedPosition = Math.abs(rotationX) < 0.15;
 *   if (rotatedLid && isClosedPosition && mesh !== rotatedLid.mesh) {
 *     mesh.visible = false; // Hide closed duplicate
 *   }
 * });
 * ```
 * 
 * Why this approach works:
 * - ✅ Doesn't rely on mesh names (catches "Wooden_Bar" or any duplicate)
 * - ✅ Checks actual state (rotation) instead of assumptions
 * - ✅ Works even if duplicate lids have same name as main lid
 * - ✅ Handles edge cases where earlier passes miss duplicates
 * 
 * Expected Visible Meshes After Opening: 4 total
 * - Chest_Body (1)
 * - Chest_Handle_01 (1)
 * - Chest_Handle_02 (1)
 * - Chest_Lid (1) - the rotated/opened lid (rotation.x ≈ -1.57)
 * 
 * CRITICAL RULES FOR FUTURE DEVELOPERS:
 * =====================================
 * 
 * 1. ⚠️ NEVER remove the rotation-based check (lines ~1842-1900)
 *    - This is the only pass that actually works reliably
 *    - All other passes are backups/first attempts
 * 
 * 2. ⚠️ NEVER check rotation BEFORE animation completes
 *    - The main lid starts at rotation.x = 0 (closed position)
 *    - Only after animation does it become rotation.x = -1.57 (opened)
 *    - switchToOpenedState() is called AFTER animation completes (line ~1533)
 * 
 * 3. ⚠️ ALWAYS keep the main lid (this.lidMesh) visible
 *    - Even if it appears to be in closed position initially
 *    - The animation will rotate it, making it the "rotated lid"
 *    - Never hide the main lid!
 * 
 * 4. ⚠️ The rotation check threshold is 0.15 radians
 *    - Closed position: Math.abs(rotationX) < 0.15 (≈ 0 degrees)
 *    - Opened position: Math.abs(rotationX + Math.PI/2) < 0.15 (≈ -90 degrees)
 *    - Don't change these thresholds without testing
 * 
 * 5. ⚠️ If adding new duplicate detection logic:
 *    - Keep the rotation-based check as the final pass
 *    - Don't rely solely on mesh names
 *    - Test with the actual GLB model (chest2.glb)
 * 
 * TESTING CHECKLIST:
 * ==================
 * When testing changes to duplicate detection:
 * [ ] Open a chest and verify only 4 meshes visible (body, 2 handles, 1 rotated lid)
 * [ ] Check console logs - should show "FINAL PASS: 4 visible meshes"
 * [ ] Verify no closed lid is visible when chest is opened
 * [ ] Test with chest_001 and chest_002 (reference implementation)
 * [ ] Check rotation values in logs - rotated lid should show rotation.x ≈ -1.57
 * 
 * DEBUGGING:
 * ==========
 * If duplicate lids still appear:
 * 1. Check console logs for "FINAL PASS" messages
 * 2. Verify rotation-based check is running (lines ~1842-1900)
 * 3. Check if rotatedLid is found correctly
 * 4. Verify all lids are being checked (allLidMeshes array)
 * 5. Ensure switchToOpenedState() is called AFTER animation completes
 * 
 * ----------------------------------------------------------------------------
 * CHEST COUNTER SYSTEM
 * ----------------------------------------------------------------------------
 * 
 * The system automatically tracks how many chests the player has opened:
 * - Stored in localStorage: 'chests_opened_count'
 * - Incremented each time a chest is successfully opened
 * - Can be displayed in UI (e.g., profile page, statistics)
 * 
 * Access counter:
 *   const chestsOpened = chestSystem.getChestsOpenedCount();
 * 
 * ----------------------------------------------------------------------------
 * CHEST PERSISTENCE SYSTEM
 * ----------------------------------------------------------------------------
 * 
 * The system automatically saves and restores opened chest states:
 * - Opened chests saved to database (tbl_riddle_completions)
 * - Chest state loaded from database on level load
 * - Opened chests appear as opened immediately (no animation)
 * - Player cannot open same chest twice (even across sessions)
 * 
 * Methods:
 *   await chestSystem.loadOpenedChests(discordId, apiBaseUrl);
 *   const isOpened = chestSystem.isChestOpened('chest_001');
 *   chestSystem.markChestAsOpened('chest_001');
 *   chestSystem.restoreChestOpenedState(chest);
 * 
 * API Endpoint: /api/user/get-opened-chests.php
 * 
 * ----------------------------------------------------------------------------
 * COLLISION DETECTION SYSTEM
 * ----------------------------------------------------------------------------
 * 
 * Players cannot walk through chests - collision detection prevents it:
 * - System checks collision between player capsule and all chests
 * - Uses bounding box to calculate chest collision radius
 * - Only checks closed chests (opened chests don't block movement)
 * - Player is pushed away from chest if collision detected
 * - Velocity toward chest is canceled to prevent sliding
 * 
 * Method: checkChestCollision(levelId, playerStart, playerEnd, playerRadius)
 * Called: Every frame in player movement update
 * Works: For all levels automatically
 * 
 * ----------------------------------------------------------------------------
 * DUPLICATE LID DETECTION - CRITICAL FIX (December 15, 2025)
 * ----------------------------------------------------------------------------
 * 
 * ISSUE: The chest2 model contains both closed and opened lid meshes.
 * After opening, both lids were visible, creating a "double chest" effect.
 * 
 * SOLUTION: 3-Pass Detection System
 * 1. First Pass: Hide duplicate lids found during load
 * 2. Second Pass: Search for all lid meshes and hide duplicates
 * 3. Final Pass: Count ALL meshes, if > 4 visible, find and hide duplicate lids
 * 
 * EXPECTED: 4 visible meshes after opening
 * - Chest_Body (1)
 * - Chest_Handle_01 (1)
 * - Chest_Handle_02 (1)
 * - Chest_Lid (1) - the rotated/opened lid
 * 
 * STATUS: ✅ FIXED - Tested with chest_001 and chest_002
 * - Both chests now correctly hide closed lid after opening
 * - Only opened (rotated) lid remains visible
 * - 3-pass detection ensures all duplicates are caught
 * 
 * ----------------------------------------------------------------------------
 * IMPORTANT NOTES
 * ----------------------------------------------------------------------------
 * 
 * ✅ ALWAYS use type: 'chest2' (standardized, has animation)
 * ✅ Standard Y position: 1.0 (matches bear trap for ground-level chests)
 * ✅ Custom Y position: Use Y > 5.0 or Y < -4.0 for elevated/underground chests (e.g., tower tops)
 * ✅ ALWAYS use unique chest IDs (chest_001, chest_002, etc.)
 * ✅ ALWAYS use correct levelId format (CHEESE_TEMPLE_LEVELX)
 * ✅ Chests are automatically positioned correctly (bounding box calculation)
 * ✅ Standard Y positioning: Ground-level chests use Y = 1.0 (matches bear trap)
 * ✅ Custom Y positioning: Elevated chests (Y > 5.0) and underground chests (Y < -4.0) supported
 * ✅ Custom Y example: Tower top chest at (82, 29, 39) - chest bottom placed at Y:29 on tower surface
 * ✅ Chests have automatic duplicate detection (hides closed state)
 * ✅ Chests prevent duplicate rewards (409 Conflict handling)
 * ✅ Chests have collision detection (players cannot walk through)
 * ✅ Chests have persistence (opened state saved/restored from database)
 * 
 * ❌ DON'T use type: 'chest1' (deprecated, auto-converts to chest2)
 * ❌ DON'T use Y positions between 1.5 and 5.0 (will be forced to 1.0 - use > 5.0 for elevated)
 * ❌ DON'T use duplicate chest IDs (will cause conflicts)
 * ❌ DON'T forget to set levelId (required for API)
 * 
 * ----------------------------------------------------------------------------
 * GRASS EXCLUSION ZONE INTEGRATION (December 16, 2025)
 * ----------------------------------------------------------------------------
 * 
 * Chests automatically register with the grass exclusion zone system to prevent
 * grass from rendering inside chest models.
 * 
 * 🚨 CRITICAL REQUIREMENT: ALL FUTURE CHESTS MUST HAVE NO GRASS UNDER THEM
 * =========================================================================
 * 
 * This system ensures NO grass renders inside or under chest models:
 * - Automatic exclusion zone registration when chest loads
 * - Grass regenerates with exclusion zones after chest registration
 * - Clean visuals with no grass artifacts inside chests
 * - Reference implementation: Level 1 chests (verified working December 16, 2025)
 * 
 * HOW IT WORKS:
 * 1. ChestSystem stores a reference to GrassSystem via setGrassSystem()
 * 2. When a chest loads, it registers its bounding box as an exclusion zone
 * 3. After registration (200ms delay), grass is regenerated with exclusion zones applied
 * 4. Result: No grass renders inside chest models - verified working!
 * 
 * EXCLUSION ZONE DETAILS:
 * - Bounding box calculated using THREE.Box3().setFromObject(mesh)
 * - Padding: 0.5 units around chest (prevents grass from touching edges)
 * - Auto-registration happens 200ms after chest loads (ensures mesh is ready)
 * - Grass regeneration delayed 1500ms to batch multiple chest registrations
 * 
 * CRITICAL INTEGRATION POINT:
 * - applyLevelEnvironment() must call chestSystem.setGrassSystem(grassSystem)
 * - This updates the reference when grassSystem is recreated during level load
 * - setGrassSystem() auto-registers all existing loaded chests
 * - NEVER call initializeGrassSystem() directly outside applyLevelEnvironment()
 * 
 * CRITICAL BUG FIX (December 16, 2025):
 * - BUG: Grass was rendering inside chests despite exclusion zone system
 * - ROOT CAUSE: warpToLevel1() called initializeGrassSystem() TWICE, destroying exclusion zones
 * - FIX: Removed duplicate call - applyLevelEnvironment() already handles it
 * - LESSON: NEVER call initializeGrassSystem() directly outside applyLevelEnvironment()
 * 
 * VERIFICATION STATUS: ✅ WORKING - VERIFIED DECEMBER 16, 2025
 * - ✅ No grass under chest_001 (Level 1, near spawn)
 * - ✅ No grass under chest_002 (Level 1, left side)
 * - ✅ Exclusion zones registering correctly
 * - ✅ Grass regenerating with exclusion zones applied
 * - ✅ Clean visuals - no grass artifacts inside chest models
 * 
 * ALL FUTURE CHESTS MUST FOLLOW THIS PATTERN:
 * - Use chestSystem.addChest() method (automatic exclusion zone registration)
 * - Use type: 'chest2' (standardized, has animation support)
 * - Standard Y position: 1.0 (matches bear trap for ground-level chests)
 * 
 * GRASS EXCLUSION AT ANY Y POSITION (December 18, 2025):
 * ======================================================
 * 
 * The grass exclusion system works at ANY Y position:
 * - ✅ Ground level (Y=1.0) - Prevents grass under chest
 * - ✅ Elevated (Y>5.0) - Prevents grass at tower base
 * - ✅ Underground (Y<-4.0) - Prevents grass at cave entrance
 * - ✅ Floating islands - Prevents grass on island surface
 * - ✅ Multi-level structures - Each chest at its own Y
 * 
 * How it works:
 * 1. Chest loads at ANY Y position (ground, elevated, underground)
 * 2. Bounding box calculated from chest mesh (3D box)
 * 3. Exclusion zone uses XYZ coordinates (not just XZ)
 * 4. Grass system checks 3D distance (respects Y height)
 * 5. Result: No grass renders near chest at ANY elevation
 * 
 * Example scenarios:
 * - Tower chest at Y=29: Grass excluded from tower top
 * - Cave chest at Y=-10: Grass excluded from cave floor
 * - Floating chest at Y=50: Grass excluded from floating platform
 * - Ground chest at Y=1: Grass excluded from ground area
 * 
 * No special code needed - works automatically for ALL Y positions!
 * - Custom Y position: Use Y > 5.0 for elevated chests (tower tops, platforms) or Y < -4.0 for underground
 * - System handles grass exclusion automatically - no manual setup needed
 * - Example: Tower top chest at (82, 29, 39) - chest bottom placed at Y:29 to sit flush on tower surface
 * 
 * ============================================================================
 */

import * as THREE from "three";
import { GLTFLoader } from "three/examples/jsm/loaders/GLTFLoader.js";

/**
 * Individual Chest Class
 * Manages a single treasure chest with opening state and rewards
 */
class Chest {
  constructor(config) {
    this.id = config.id;                    // Unique chest ID
    this.type = config.type;                // 'chest1' or 'chest2'
    this.position = config.position;        // THREE.Vector3 position
    this.dspoincAmount = config.dspoincAmount; // Base DSPOINC reward
    this.levelId = config.levelId;          // Level identifier
    this.opened = false;                     // Opening state
    this.mesh = null;                        // Three.js mesh/group
    this.openMesh = null;                    // Opened chest mesh (optional, for future)
    this.interactionRadius = 2.0;            // Distance to interact (units)
    this.openingAnimation = null;            // Animation mixer (if model has animation)
    this.lidMesh = null;                     // Lid mesh for manual rotation animation
    this.lidRotation = { current: 0, target: 0 }; // Lid rotation state
    this.closedMeshes = [];                  // Meshes to hide after opening (closed state)
    this.openedMeshes = [];                  // Meshes to show after opening (opened state)
    this.allChestMeshes = [];                // All meshes in the chest (for duplicate detection)
    this.chestBodyMesh = null;               // Main chest body/base mesh (to hide after opening)
    this.duplicateLidMeshes = [];            // Duplicate lid/top meshes (closed state to hide)
    this.scene = config.scene;               // Three.js scene reference
    this.loadModel = config.loadModel;      // Model loading function
    this.processWeaponMaterial = config.processWeaponMaterial; // Material processing function
    this.resolveAssetPath = config.resolveAssetPath || ((path) => path);
    
    // Dual-model chest support (Jan 2026):
    // If provided, this chest uses two GLBs (no embedded animation):
    // - closedModelPath: visible initially
    // - openedModelPath: swapped in after opening / for restored-opened state
    this.closedModelPath = config.closedModelPath || null;
    this.openedModelPath = config.openedModelPath || null;
    this.closedMesh = null;
    this.openedMesh = null;
    this.openedMeshLoaded = false;
    this.openedMeshLoading = false;
    
    // State tracking
    this.isLoading = false;
    this.isLoaded = false;
    this.loadError = null;
    
    // CRITICAL (Jan 2026): Async load cancellation / disposal safety.
    // Chests are created with delayed async loads, but levels can be cleared while those loads are pending.
    // Without guarding, "ghost" chests from other levels can appear and duplicates can stack.
    this.isDisposed = false;
    this._loadTimeoutId = null;
    
    // Interaction state
    this.playerNearby = false;
    this.canInteract = false;
  }
  
  /**
   * Load chest model from file
   */
  async load() {
    if (this.isDisposed) {
      return;
    }
    if (this.isLoading || this.isLoaded) {
      return; // Already loading or loaded
    }
    
    this.isLoading = true;
    this.loadError = null;
    
    // Determine model path based on type or dual-model override.
    // If dual-model paths are provided, we load the CLOSED model here.
    let modelPath;
    if (this.type === 'chest1') {
      // Legacy support: chest1 now uses chest2 model
      console.log(`🔄 [CHEST] ${this.id} chest1 type detected, using chest2 model (standardized)`);
      this.type = 'chest2'; // Update type to chest2
    }
    
    if (this.closedModelPath && this.openedModelPath) {
      modelPath = this.closedModelPath;
    } else if (this.type === 'chest2') {
      // Try both possible paths for chest2 (case sensitivity)
      // First try with capital C (Chest2.glb)
      modelPath = "/textures/3d models/chest2/Chest2.glb";
      // If that fails, will try lowercase in catch block
    } else {
      this.loadError = new Error(`Unknown chest type: ${this.type}`);
      this.isLoading = false;
      console.error(`❌ [CHEST] Unknown chest type: ${this.type}`);
      return;
    }
    
    console.log(`🎁 [CHEST] Loading ${this.type} at ${this.id}:`, {
      path: modelPath,
      position: this.position,
      dspoincAmount: this.dspoincAmount
    });
    
    try {
      // Use provided loadModel function (from main.js)
      // NOTE: We always run through resolveAssetPath + encodeURI because our asset paths
      // contain spaces and must work on Render where textures are symlinked from /data.
      const resolvedModelPath = encodeURI(this.resolveAssetPath(modelPath));
      const gltf = await this.loadModel(resolvedModelPath);
      
      // If this chest was disposed while the model was loading, do not attach it to the scene.
      if (this.isDisposed) {
        this.isLoading = false;
        return;
      }
      const chest = gltf.scene;
      
      // DEBUG: Inspect GLB structure for animations
      console.log(`🔍 [CHEST] ${this.id} GLB structure inspection:`, {
        hasAnimations: !!gltf.animations,
        animationCount: gltf.animations ? gltf.animations.length : 0,
        animations: gltf.animations,
        scene: gltf.scene,
        scenes: gltf.scenes,
        asset: gltf.asset,
        parser: gltf.parser ? 'exists' : 'none'
      });
      
      // Also check if animations might be in the scene or nodes
      // Also inspect mesh structure to find lid or openable parts
      const meshInfo = [];
      if (gltf.scene) {
        gltf.scene.traverse((node) => {
          if (node.animations && node.animations.length > 0) {
            console.log(`🔍 [CHEST] ${this.id} Found animations in node "${node.name}":`, node.animations.length);
          }
          
          // Collect mesh information for potential manual animation
          if (node.isMesh || node.isGroup) {
            const nameLower = (node.name || '').toLowerCase();
            meshInfo.push({
              name: node.name,
              type: node.isMesh ? 'Mesh' : 'Group',
              children: node.children ? node.children.length : 0,
              isLid: nameLower.includes('lid') || nameLower.includes('top') || nameLower.includes('cover'),
              isBase: nameLower.includes('base') || nameLower.includes('bottom') || nameLower.includes('body')
            });
          }
        });
        
        console.log(`🔍 [CHEST] ${this.id} Mesh structure:`, meshInfo);
        
        // Store all meshes for later reference
        gltf.scene.traverse((node) => {
          if (node.isMesh || node.isGroup) {
            this.allChestMeshes.push(node);
          }
        });
        
        // Try to find lid mesh for manual animation
        // Also identify closed/opened state meshes
        if (meshInfo.length > 0) {
          const lidMesh = meshInfo.find(m => m.isLid);
          if (lidMesh) {
            console.log(`🔍 [CHEST] ${this.id} Found potential lid mesh: "${lidMesh.name}"`);
            // Find the actual mesh object
            gltf.scene.traverse((node) => {
              if (node.name === lidMesh.name && (node.isMesh || node.isGroup)) {
                this.lidMesh = node;
                console.log(`✅ [CHEST] ${this.id} Lid mesh stored for animation:`, {
                  name: node.name,
                  type: node.isMesh ? 'Mesh' : 'Group',
                  position: node.position,
                  rotation: node.rotation
                });
              }
            });
          }
          
          // Find chest body/base mesh (the main chest structure, not the lid)
          const baseMesh = meshInfo.find(m => m.isBase);
          if (baseMesh) {
            gltf.scene.traverse((node) => {
              if (node.name === baseMesh.name && (node.isMesh || node.isGroup) && node !== this.lidMesh) {
                this.chestBodyMesh = node;
                console.log(`✅ [CHEST] ${this.id} Chest body mesh stored: "${node.name}"`);
              }
            });
          }
          
          // ========================================================================
          // DUPLICATE LID IDENTIFICATION DURING LOAD (Phase 1 of duplicate detection)
          // ========================================================================
          // 
          // DOCUMENTED: December 19, 2025 - WORKING MECHANISM
          // 
          // Purpose: Identify duplicate lid meshes during load and store them for later hiding
          // This is the FIRST pass of the 6-pass duplicate detection system
          // 
          // How it works:
          // 1. Find all meshes with "lid", "top", or "cover" in their name
          // 2. Identify which one is the main lid (this.lidMesh - the one we'll rotate)
          // 3. Store all OTHER lids in duplicateLidMeshes array
          // 4. These will be hidden in switchToOpenedState() (Pass 1)
          // 
          // Note: This pass may miss duplicates if they don't have "lid"/"top"/"cover" in name
          // The rotation-based check (Pass 6) will catch those anyway
          // ========================================================================
          const lidMeshes = [];
          gltf.scene.traverse((node) => {
            if (node.isMesh || node.isGroup) {
              const nameLower = (node.name || '').toLowerCase();
              const isLidLike = nameLower.includes('lid') || nameLower.includes('top') || nameLower.includes('cover');
              
              if (isLidLike) {
                lidMeshes.push({
                  node: node,
                  name: node.name,
                  position: node.position.clone(),
                  isMainLid: node === this.lidMesh
                });
              }
            }
          });
          
          // If we have multiple lid meshes, the ones that aren't the main lid are duplicates (closed state)
          // Store them in duplicateLidMeshes array for hiding later in switchToOpenedState()
          if (lidMeshes.length > 1) {
            console.log(`🔍 [CHEST] ${this.id} Found ${lidMeshes.length} lid/top meshes, identifying duplicates...`);
            lidMeshes.forEach(({ node, name, isMainLid }) => {
              if (!isMainLid) {
                this.duplicateLidMeshes.push(node);
                console.log(`🔍 [CHEST] ${this.id} Found duplicate lid/top mesh (will hide after opening): "${name}"`);
              }
            });
          }
          // Note: This is Pass 1 preparation - actual hiding happens in switchToOpenedState()
          
          // Identify closed and opened state meshes
          const allMeshes = [];
          gltf.scene.traverse((node) => {
            if (node.isMesh || node.isGroup) {
              allMeshes.push(node);
              const nameLower = (node.name || '').toLowerCase();
              
              // Check for closed state indicators
              if (nameLower.includes('closed') || 
                  nameLower.includes('close') || 
                  (nameLower.includes('base') && !nameLower.includes('open'))) {
                this.closedMeshes.push(node);
                console.log(`🔍 [CHEST] ${this.id} Found closed state mesh: "${node.name}"`);
              }
              
              // Check for opened state indicators
              if (nameLower.includes('open') || 
                  nameLower.includes('opened')) {
                this.openedMeshes.push(node);
                // Initially hide opened state
                node.visible = false;
                console.log(`🔍 [CHEST] ${this.id} Found opened state mesh (initially hidden): "${node.name}"`);
              }
            }
          });
          
          // If we found meshes but no explicit closed/opened states,
          // check for duplicate chest structures (common in some GLB exports)
          if (allMeshes.length > 0 && this.closedMeshes.length === 0 && this.openedMeshes.length === 0) {
            console.log(`🔍 [CHEST] ${this.id} No explicit closed/opened meshes found, checking for duplicates...`);
            // Look for meshes that might be duplicates (same structure, different state)
            // This is a fallback - we'll handle it in switchToOpenedState if needed
          }
        }
      }
      
      // CRITICAL: Calculate bounding box BEFORE positioning to get model space dimensions
      // The bounding box is in model space (relative to model origin at 0,0,0)
      const box = new THREE.Box3().setFromObject(chest);
      const size = box.getSize(new THREE.Vector3());
      const center = box.getCenter(new THREE.Vector3()); // Center point for logging
      const min = box.min; // Minimum point (bottom-left-back corner) in model space
      const max = box.max; // Maximum point (top-right-front corner) in model space
      
      console.log(`🎁 [CHEST] ${this.id} model loaded:`, {
        size: size,
        min: min,
        max: max,
        children: chest.children.length
      });
      
      // CRITICAL: Position chest at requested X and Z first
      chest.position.x = this.position.x;
      chest.position.z = this.position.z;
            
      // =====================================================================
      // GLOBAL CHEST GROUND PLACEMENT (Raycast) - January 15, 2026
      // =====================================================================
      // We now raycast DOWN at the chest X/Z to find the actual surface (works in ALL levels),
      // then place the CHEST BOTTOM flush on that surface.
      //
      // ✅ VERIFIED WORKING (Jan 15, 2026):
      // - Levels 1-6: chests spawn flush on the ground (no more "1 unit under" / "top only visible")
      // - GOD-mode warps: if floor/map isn’t ready yet, chest will retry-align shortly after load
      // - Works with BOTH chest systems:
      //   - Legacy `chest2` single-GLB (with internal lid meshes)
      //   - New `chest3` dual-GLB (closed + opened) with instant swap animation
      //
      // Implementation details:
      // - Raycast filters:
      //   - ignores chest meshes (`userData.isChest`) including hidden opened meshes
      //   - ignores decorative Level 4 bosses (`userData.isCheeseBoss`)
      //   - ignores invisible objects (walks up parents; any `visible === false` => skip)
      // - Placement math:
      //   - uses a post-scale world-space Box3 (`Box3().setFromObject(chest).min.y`)
      //   - shifts mesh by `desiredBottomY - box.min.y` (epsilon ~ 0.03) for perfect alignment
      // - Retries:
      //   - if raycast hits nothing during fast warp, `_scheduleGroundAlignRetry()` re-aligns
      //     once the floor exists in the scene.
      //
      // This replaces the old per-level Y heuristics that could drift during warps and cause
      // "chests under the ground" reports.
      const requestedY = this.position.y;
      let raycastGroundY = null;
      // If a chest is intentionally elevated/underground (large offsets), keep its requested Y.
      // This preserves special placements like tower-top chests.
      const shouldRaycastGround = Number.isFinite(requestedY) ? (requestedY <= 5.0 && requestedY >= -4.0) : true;
      try {
        const raycaster = new THREE.Raycaster();
        const rayOriginY = (Number.isFinite(requestedY) ? requestedY : 1.0) + 300;
        const origin = new THREE.Vector3(this.position.x, rayOriginY, this.position.z);
        const direction = new THREE.Vector3(0, -1, 0);
        raycaster.set(origin, direction);
        raycaster.far = 2000;
        
        // Intersect the whole scene; InstancedMesh raycast works in Three.js.
        // We filter out obvious non-ground items like other chests and boss statues.
        const hits = shouldRaycastGround ? raycaster.intersectObjects(this.scene.children, true) : [];
        const isActuallyVisible = (obj) => {
          // Raycaster can hit objects even if they are invisible via parent visibility.
          // For ground placement we ONLY want real visible world geometry (floor/map).
          let cur = obj;
          while (cur) {
            if (cur.visible === false) return false;
            cur = cur.parent;
          }
          return true;
        };

        const filtered = hits.filter((hit) => {
          const obj = hit.object;
          if (!obj) return false;
          if (!isActuallyVisible(obj)) return false;
          if (obj.userData?.isChest) return false;
          if (obj.userData?.isCheeseBoss) return false;
          return true;
        });
        if (filtered.length > 0 && filtered[0].point && Number.isFinite(filtered[0].point.y)) {
          raycastGroundY = filtered[0].point.y;
        }
      } catch (e) {
        // If raycast fails for any reason, we fall back to legacy math below.
      }
      
      // If raycast succeeded, this is the target surface Y where chest bottom should sit.
      const targetBottomY = (raycastGroundY !== null) ? raycastGroundY : requestedY;
      const useCustomY = raycastGroundY === null; // legacy info for logs
      console.log(`🔍 [CHEST] ${this.id} ground raycast:`, {
        x: this.position.x,
        z: this.position.z,
        requestedY,
        shouldRaycastGround,
        raycastGroundY,
        targetBottomY
      });
      
      // Place chest using a post-scale world-space bounding box so all GLBs (old chest2 + new chest3)
      // align correctly, even if their origin/min.y differs by ~1 unit.
      if (size.y > 0) {
        const CHEST_SCALE = 2.0; // standardized size (matches prior system)
        const epsilon = 0.03; // tiny lift to avoid z-fighting / clipping

        // Apply scale first, then measure world-space bottom.
        chest.scale.setScalar(CHEST_SCALE);
        chest.position.y = 0;
        chest.updateMatrixWorld(true);

        const scaledBox = new THREE.Box3().setFromObject(chest);
        const worldMinY = scaledBox.min.y;
        const desiredBottomY = targetBottomY + (shouldRaycastGround ? epsilon : 0);
        const deltaY = desiredBottomY - worldMinY;
        chest.position.y += deltaY;
        chest.updateMatrixWorld(true);

        // Recompute to store a reliable bottom offset for later retries/verification.
        const finalBox = new THREE.Box3().setFromObject(chest);
        const finalWorldMinY = finalBox.min.y;
        const scaledBoundingBoxBottom = finalWorldMinY - chest.position.y; // offset relative to origin

        chest.userData.requestedY = requestedY;
        chest.userData.targetBottomY = desiredBottomY;
        chest.userData.useCustomY = useCustomY;
        chest.userData.calculatedY = chest.position.y;
        chest.userData.chestScale = CHEST_SCALE;
        chest.userData.shouldRaycastGround = shouldRaycastGround;
        chest.userData.raycastGroundY = raycastGroundY;
        chest.userData.scaledBoundingBoxBottom = scaledBoundingBoxBottom;

        console.log(`🎁 [CHEST] ${this.id} Y aligned via scaled world Box3:`, {
          desiredBottomY,
          finalWorldMinY,
          deltaY,
          finalY: chest.position.y,
          chestScale: CHEST_SCALE
        });
      } else {
        // If no size, use requested position directly
        const requestedY = this.position.y;
        chest.position.y = requestedY;
        chest.userData.requestedY = requestedY;
        chest.userData.targetBottomY = requestedY;
        chest.userData.useCustomY = Math.abs(requestedY - 1.0) > 5.0;
        chest.userData.calculatedY = requestedY;
        chest.userData.boundingBoxBottom = 0;
        console.log(`🎁 [CHEST] ${this.id} Y position set directly to ${requestedY} (no bounding box):`, {
          requestedY: requestedY,
          useCustomY: chest.userData.useCustomY
        });
        // Still need to apply scale (standardized: 2.0x for all chests)
        const CHEST_SCALE = 2.0;
        chest.scale.setScalar(CHEST_SCALE);
        chest.userData.chestScale = CHEST_SCALE;
      }
      
      // Rotation (can be adjusted if needed)
      chest.rotation.y = 0;
      
      // Process materials to ensure proper rendering
      chest.traverse((child) => {
        if (child.isMesh) {
          child.castShadow = true;
          child.receiveShadow = true;
          child.visible = true;
          child.frustumCulled = false; // CRITICAL: Ensure all meshes are visible
          if (child.material) {
            // Process material using provided function
            if (Array.isArray(child.material)) {
              child.material = child.material.map(mat => {
                const processed = this.processWeaponMaterial(mat);
                if (processed) {
                  processed.needsUpdate = true; // CRITICAL: Force material update
                }
                return processed;
              });
            } else {
              child.material = this.processWeaponMaterial(child.material);
              if (child.material) {
                child.material.needsUpdate = true; // CRITICAL: Force material update
              }
            }
          }
        }
      });
      
      // Store collision data in userData (for future collision detection)
      chest.userData.collisionRadius = Math.max(size.x, size.z) * 0.5;
      chest.userData.collisionPosition = this.position.clone();
      chest.userData.chestId = this.id;
      chest.userData.chestType = this.type;
      chest.userData.isChest = true;
      
      // CRITICAL: Ensure chest is visible and not culled
      chest.visible = true;
      chest.frustumCulled = false; // Ensure it's always rendered
      chest.updateMatrixWorld(true);
      
      // Traverse and ensure all children are visible
      chest.traverse((child) => {
        if (child.isMesh) {
          child.visible = true;
          child.frustumCulled = false;
        }
      });
      
      // Add to scene
      this.scene.add(chest);
      this.mesh = chest;
      this.closedMesh = chest;

      // If the ground raycast did not hit anything (common during fast level warp while
      // the environment/floor is still being created), retry alignment a few times.
      // This makes Level 2-6 reliable and prevents "chest under ground" even if load() ran early.
      if (chest.userData && chest.userData.shouldRaycastGround === true && chest.userData.raycastGroundY === null) {
        this._scheduleGroundAlignRetry(6);
      }
      // Dual-model: start preloading the opened mesh in the background (so swaps feel instant).
      if (this.closedModelPath && this.openedModelPath) {
        this._ensureOpenedMeshLoaded().catch(() => {});
      }

      
      // Check for animations in GLB
      if (gltf.animations && gltf.animations.length > 0) {
        console.log(`🎁 [CHEST] ${this.id} has ${gltf.animations.length} animation(s):`, 
          gltf.animations.map(anim => ({
            name: anim.name,
            duration: anim.duration,
            tracks: anim.tracks.length
          }))
        );
        
        // Create animation mixer for this chest
        this.openingAnimation = new THREE.AnimationMixer(chest);
        
        // Store all animations for later use
        chest.userData.animations = gltf.animations;
        chest.userData.animationMixer = this.openingAnimation;
        
        // Log available animation names
        const animationNames = gltf.animations.map(anim => anim.name);
        console.log(`🎁 [CHEST] ${this.id} available animations:`, animationNames);
      } else {
        console.log(`🎁 [CHEST] ${this.id} has no animations in GLB file`);
      }
      
      this.isLoaded = true;
      this.isLoading = false;
      
      // Count total meshes in the chest model for debugging
      let totalMeshes = 0;
      chest.traverse((child) => {
        if (child.isMesh) totalMeshes++;
      });
      
      console.log(`✅ [CHEST] ${this.id} created successfully:`, {
        id: this.id,
        type: this.type,
        position: chest.position,
        originalPosition: this.position,
        scale: chest.scale,
        visible: chest.visible,
        inScene: this.scene.children.includes(chest),
        boundingBox: { size: size, center: center },
        collisionRadius: chest.userData.collisionRadius,
        children: chest.children.length,
        totalMeshes: totalMeshes,
        meshExists: !!this.mesh,
        sceneChildrenCount: this.scene.children.length,
        frustumCulled: chest.frustumCulled
      });
      
      // Double-check visibility after a short delay
      setTimeout(() => {
        if (this.mesh) {
          this.mesh.visible = true;
          this.mesh.frustumCulled = false;
          this.mesh.updateMatrixWorld(true);
          
          // CRITICAL: Ensure all children are visible and materials are processed
          this.mesh.traverse((child) => {
            if (child.isMesh) {
              child.visible = true;
              child.frustumCulled = false;
              // Ensure material is properly set and bright enough
              if (child.material) {
                if (Array.isArray(child.material)) {
                  child.material.forEach(mat => {
                    if (mat) {
                      mat.needsUpdate = true;
                      // Brighten material if too dark
                      if (mat.color) {
                        const brightness = (mat.color.r + mat.color.g + mat.color.b) / 3;
                        if (brightness < 0.2) {
                          mat.color.setRGB(
                            Math.min(1.0, mat.color.r * 2.0),
                            Math.min(1.0, mat.color.g * 2.0),
                            Math.min(1.0, mat.color.b * 2.0)
                          );
                        }
                      }
                    }
                  });
                } else {
                  child.material.needsUpdate = true;
                  // Brighten material if too dark
                  if (child.material.color) {
                    const brightness = (child.material.color.r + child.material.color.g + child.material.color.b) / 3;
                    if (brightness < 0.2) {
                      child.material.color.setRGB(
                        Math.min(1.0, child.material.color.r * 2.0),
                        Math.min(1.0, child.material.color.g * 2.0),
                        Math.min(1.0, child.material.color.b * 2.0)
                      );
                    }
                  }
                }
              }
            }
          });
          
          // CRITICAL: Re-verify Y position matches calculated Y
          // Sometimes the position can be lost during scene operations or warp
          // CRITICAL FIX (Jan 12, 2026): Use SCALED bounding box bottom for any bottom-Y checks.
          // The initial placement uses scaledBoundingBoxBottom (accounts for 2.0x chest scale).
          // Using the unscaled boundingBoxBottom here will "correct" the chest into the wrong Y,
          // which is exactly what caused LEVEL6 chest_011 to end up at y ≈ -7.01 (invisible / wrong height).
          const boundingBoxBottom = (this.mesh.userData.scaledBoundingBoxBottom !== undefined)
            ? this.mesh.userData.scaledBoundingBoxBottom
            : (
              this.mesh.userData.boundingBoxBottom !== undefined
                ? (this.mesh.userData.boundingBoxBottom * (this.mesh.userData.chestScale || 1))
                : undefined
            );
          const expectedY = this.mesh.userData.calculatedY;
          
          if (boundingBoxBottom !== undefined && expectedY !== undefined) {
            // Check if Y position was lost
            if (Math.abs(this.mesh.position.y - expectedY) > 0.01) {
              console.warn(`🔧 [CHEST] ${this.id} Y position lost, restoring:`, {
                currentY: this.mesh.position.y,
                expectedY: expectedY,
                requestedY: this.position.y,
                difference: Math.abs(this.mesh.position.y - expectedY)
              });
              this.mesh.position.y = expectedY;
              this.mesh.updateMatrixWorld(true);
            }
            
            // CRITICAL: Verify chest bottom is at target Y (1.0 for ground level, or requested Y for elevated)
            // Skip this check if chest uses custom Y positioning (e.g., tower top)
            const useCustomY = this.mesh.userData.useCustomY === true;
            const targetBottomY = this.mesh.userData.targetBottomY !== undefined ? this.mesh.userData.targetBottomY : 1.0;
            
            // Always verify against stored targetBottomY (ground-level chests store 1.0, custom store requested Y).
            // NOTE: useCustomY flag is still logged below for debugging, but the math is identical now.
            const finalBottomY = this.mesh.position.y + boundingBoxBottom;
            if (Math.abs(finalBottomY - targetBottomY) > 0.01) {
              console.warn(`🔧 [CHEST] ${this.id} bottom Y not at target ${targetBottomY}, adjusting:`, {
                currentBottomY: finalBottomY,
                targetBottomY: targetBottomY,
                adjustment: targetBottomY - finalBottomY,
                useCustomY: useCustomY
              });
              const adjustment = targetBottomY - finalBottomY;
              this.mesh.position.y += adjustment;
              this.mesh.updateMatrixWorld(true);
            }
          }
          
          const finalBottomY = boundingBoxBottom !== undefined ? this.mesh.position.y + boundingBoxBottom : 'N/A';
          const targetBottomY = this.mesh.userData.targetBottomY !== undefined ? this.mesh.userData.targetBottomY : 1.0;
          const useCustomY = this.mesh.userData.useCustomY === true;
          
          console.log(`🎁 [CHEST] ${this.id} visibility and Y position verified:`, {
            visible: this.mesh.visible,
            inScene: this.scene.children.includes(this.mesh),
            position: this.mesh.position,
            y: this.mesh.position.y,
            calculatedY: expectedY,
            requestedY: this.position.y,
            finalBottomY: finalBottomY,
            targetBottomY: targetBottomY,
            useCustomY: useCustomY,
            worldPosition: this.mesh.getWorldPosition(new THREE.Vector3()),
            frustumCulled: this.mesh.frustumCulled,
            childrenCount: this.mesh.children.length,
            meshCount: this.mesh.children.filter(c => c.isMesh).length
          });
        } else {
          console.warn(`⚠️ [CHEST] ${this.id} mesh is null after load!`);
        }
      }, 300); // Increased delay for reliability
      
    } catch (error) {
      // If chest2 failed with capital C, try lowercase (dual-model does not use this path)
      if (!this.closedModelPath && this.type === 'chest2' && modelPath && modelPath.includes('Chest2.glb')) {
        console.log(`🔄 [CHEST] ${this.id} failed with capital C, trying lowercase...`);
        try {
          const lowercasePath = "/textures/3d models/chest2/chest2.glb";
          const resolvedLowercasePath = encodeURI(this.resolveAssetPath(lowercasePath));
          const gltf = await this.loadModel(resolvedLowercasePath);
          const chest = gltf.scene;
          
          // Use same positioning logic as above
          const box = new THREE.Box3().setFromObject(chest);
          const size = box.getSize(new THREE.Vector3());
          const center = box.getCenter(new THREE.Vector3());
          
          chest.position.copy(this.position);
          if (size.y > 0) {
            const modelBaseY = center.y - (size.y / 2);
            if (Math.abs(modelBaseY) > 0.1) {
              chest.position.y = this.position.y - modelBaseY;
            }
          }
          
          chest.scale.setScalar(2.0); // Standardized: 2.0x scale for all chests
          chest.rotation.y = 0;
          
          chest.traverse((child) => {
            if (child.isMesh) {
              child.castShadow = true;
              child.receiveShadow = true;
              if (child.material) {
                if (Array.isArray(child.material)) {
                  child.material = child.material.map(mat => this.processWeaponMaterial(mat));
                } else {
                  child.material = this.processWeaponMaterial(child.material);
                }
              }
            }
          });
          
          chest.userData.collisionRadius = Math.max(size.x, size.z) * 0.5;
          chest.userData.collisionPosition = this.position.clone();
          chest.userData.chestId = this.id;
          chest.userData.chestType = this.type;
          chest.userData.isChest = true;
          
          chest.visible = true;
          chest.frustumCulled = false;
          chest.updateMatrixWorld(true);
          
          this.scene.add(chest);
          this.mesh = chest;
          this.closedMesh = chest;
          
          // Check for animations in GLB (lowercase path fallback)
          if (gltf.animations && gltf.animations.length > 0) {
            console.log(`🎁 [CHEST] ${this.id} has ${gltf.animations.length} animation(s) (lowercase path):`, 
              gltf.animations.map(anim => ({
                name: anim.name,
                duration: anim.duration,
                tracks: anim.tracks.length
              }))
            );
            
            // Create animation mixer for this chest
            this.openingAnimation = new THREE.AnimationMixer(chest);
            
            // Store all animations for later use
            chest.userData.animations = gltf.animations;
            chest.userData.animationMixer = this.openingAnimation;
            
            // Log available animation names
            const animationNames = gltf.animations.map(anim => anim.name);
            console.log(`🎁 [CHEST] ${this.id} available animations:`, animationNames);
          } else {
            console.log(`🎁 [CHEST] ${this.id} has no animations in GLB file (lowercase path)`);
          }
          
          this.isLoaded = true;
          this.isLoading = false;
          
          console.log(`✅ [CHEST] ${this.id} loaded successfully with lowercase path:`, {
            id: this.id,
            type: this.type,
            position: chest.position,
            scale: chest.scale,
            visible: chest.visible,
            inScene: this.scene.children.includes(chest)
          });
          
          // Double-check visibility after a short delay
          setTimeout(() => {
            if (this.mesh) {
              this.mesh.visible = true;
              this.mesh.updateMatrixWorld(true);
              console.log(`🎁 [CHEST] ${this.id} visibility verified (lowercase):`, {
                visible: this.mesh.visible,
                inScene: this.scene.children.includes(this.mesh),
                position: this.mesh.position
              });
            }
          }, 100);
          
          return; // Success with lowercase path
        } catch (lowercaseError) {
          console.error(`❌ [CHEST] Both paths failed for ${this.id}:`, {
            capitalPath: modelPath,
            lowercasePath: "/textures/3d models/chest2/chest2.glb",
            error: lowercaseError
          });
        }
      }
      
      this.loadError = error;
      this.isLoading = false;
      console.error(`❌ [CHEST] Failed to load ${this.id}:`, error);
      console.error(`❌ [CHEST] Chest path attempted:`, modelPath);
      console.error(`❌ [CHEST] Full error:`, error.message, error.stack);
    }
  }
  
  /**
   * Check if player is near enough to interact
   * @param {THREE.Vector3} playerPosition - Player position (center of capsule)
   * @returns {boolean} - True if player is within interaction radius
   */
  checkPlayerInteraction(playerPosition) {
    if (!this.isLoaded || !this.mesh || this.opened) {
      return false; // Can't interact if not loaded, no mesh, or already opened
    }
    
    // Calculate horizontal distance from player to chest.
    // IMPORTANT: Use the actual mesh world position, because chest Y/position can be corrected
    // after load() based on bounding box + scale, and relying on the original config.position
    // can break interaction distance checks.
    const chestPos = new THREE.Vector3();
    this.mesh.getWorldPosition(chestPos);
    
    const dx = playerPosition.x - chestPos.x;
    const dz = playerPosition.z - chestPos.z;
    const horizontalDistance = Math.sqrt(dx * dx + dz * dz);
    
    // Check if player is within interaction radius
    this.playerNearby = horizontalDistance < this.interactionRadius;
    this.canInteract = this.playerNearby && !this.opened;
    
    return this.canInteract;
  }
  
  /**
   * Open chest and award DSPOINC reward
   * @param {Function} awardRewardCallback - Callback function to award DSPOINC
   */
  async open(awardRewardCallback) {
    if (this.opened || !this.isLoaded) {
      return; // Already opened or not loaded
    }
    
    console.log(`🎁 [CHEST] Opening ${this.id}...`, {
      type: this.type,
      dspoincAmount: this.dspoincAmount,
      levelId: this.levelId
    });
    
    // Mark as opened
    this.opened = true;
    this.canInteract = false;
    
    // Play opening animation (if available)
    this.playOpenAnimation();
    
    // Award DSPOINC reward via callback
    if (awardRewardCallback && typeof awardRewardCallback === 'function') {
      await awardRewardCallback(this);
    }
    
    console.log(`✅ [CHEST] ${this.id} opened successfully`);
  }
  
  /**
   * Restore opened state without animation (for chests already opened)
   * Called when loading chests that were previously opened
   */
  restoreOpenedState() {
    if (this.opened || !this.isLoaded || !this.mesh) {
      return; // Already opened, not loaded, or no mesh
    }
    
    console.log(`🔄 [CHEST] Restoring opened state for ${this.id}...`);
    
    // Mark as opened
    this.opened = true;
    this.canInteract = false;
    
    // Restore visual state (hide closed, show opened) without animation
    this.switchToOpenedState();
    
    console.log(`✅ [CHEST] ${this.id} opened state restored`);
  }
  
  /**
   * Play opening animation with visual effects
   */
  playOpenAnimation() {
    if (!this.mesh) return;
    
    console.log(`🎁 [CHEST] ${this.id} opening animation with visual effects`);
    
    // Dual-model animation: no embedded GLB animation, so we do a small pop/pulse + sparkle,
    // then swap to the opened model.
    if (this.closedModelPath && this.openedModelPath) {
      this._playDualModelSwapAnimation();
      return;
    }
    
    // Try to play GLB animation if available
    if (this.openingAnimation && this.mesh.userData.animations && this.mesh.userData.animations.length > 0) {
      const animations = this.mesh.userData.animations;
      
      // Look for opening-related animation names (case-insensitive)
      const openingAnimationNames = ['open', 'opening', 'lid', 'unlock', 'activate'];
      let foundAnimation = null;
      
      // First, try to find an animation with "open" in the name
      for (const anim of animations) {
        const animNameLower = anim.name.toLowerCase();
        if (openingAnimationNames.some(keyword => animNameLower.includes(keyword))) {
          foundAnimation = anim;
          console.log(`🎁 [CHEST] ${this.id} found opening animation: "${anim.name}"`);
          break;
        }
      }
      
      // If no specific opening animation found, use the first animation
      if (!foundAnimation && animations.length > 0) {
        foundAnimation = animations[0];
        console.log(`🎁 [CHEST] ${this.id} using first available animation: "${foundAnimation.name}"`);
      }
      
      // Play the animation
      if (foundAnimation) {
        const action = this.openingAnimation.clipAction(foundAnimation);
        action.reset(); // Reset to start
        action.setLoop(THREE.LoopOnce); // Play once
        action.clampWhenFinished = true; // Keep final frame
        action.play();
        
        console.log(`🎁 [CHEST] ${this.id} playing animation "${foundAnimation.name}" (duration: ${foundAnimation.duration.toFixed(2)}s)`);
      }
    } else {
      console.log(`🎁 [CHEST] ${this.id} no animations available, using visual effects only`);
      
      // Try manual lid rotation animation if lid mesh was found
      if (this.lidMesh) {
        console.log(`🎁 [CHEST] ${this.id} attempting manual lid rotation animation`);
        this.playManualLidAnimation();
      } else {
        console.log(`🎁 [CHEST] ${this.id} no lid mesh found for manual animation`);
      }
    }
    
    // Create sparkling glow effect
    this.createSparklingGlow();
    
    // Play opening sound effect
    this.playOpeningSound();
  }
  
  /**
   * Create sparkling glow effect around chest
   */
  createSparklingGlow() {
    if (!this.mesh || !this.scene) return;
    
    // Create particle system for sparkling effect
    const particleCount = 50;
    const particles = new THREE.BufferGeometry();
    const positions = new Float32Array(particleCount * 3);
    const colors = new Float32Array(particleCount * 3);
    const sizes = new Float32Array(particleCount);
    
    // Get chest world position
    const chestPosition = new THREE.Vector3();
    this.mesh.getWorldPosition(chestPosition);
    
    // Create golden/yellow particles
    const color1 = new THREE.Color(0xffe066); // Cheese yellow
    const color2 = new THREE.Color(0xffd700); // Gold
    
    for (let i = 0; i < particleCount; i++) {
      const i3 = i * 3;
      
      // Random position around chest (spherical distribution)
      const radius = 1.5 + Math.random() * 1.0;
      const theta = Math.random() * Math.PI * 2;
      const phi = Math.random() * Math.PI;
      
      positions[i3] = chestPosition.x + radius * Math.sin(phi) * Math.cos(theta);
      positions[i3 + 1] = chestPosition.y + radius * Math.sin(phi) * Math.sin(theta) + 0.5;
      positions[i3 + 2] = chestPosition.z + radius * Math.cos(phi);
      
      // Random color between yellow and gold
      const colorMix = Math.random();
      const color = color1.clone().lerp(color2, colorMix);
      colors[i3] = color.r;
      colors[i3 + 1] = color.g;
      colors[i3 + 2] = color.b;
      
      // Random size
      sizes[i] = 0.1 + Math.random() * 0.2;
    }
    
    particles.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    particles.setAttribute('color', new THREE.BufferAttribute(colors, 3));
    particles.setAttribute('size', new THREE.BufferAttribute(sizes, 1));
    
    // Create material
    const particleMaterial = new THREE.PointsMaterial({
      size: 0.3,
      vertexColors: true,
      transparent: true,
      opacity: 1.0,
      blending: THREE.AdditiveBlending,
      depthWrite: false
    });
    
    // Create points system
    const particleSystem = new THREE.Points(particles, particleMaterial);
    this.scene.add(particleSystem);
    
    // Animate particles (expand and fade out)
    const startTime = Date.now();
    const duration = 2000; // 2 seconds
    const initialPositions = positions.slice();
    
    const animate = () => {
      const elapsed = Date.now() - startTime;
      const progress = elapsed / duration;
      
      if (progress >= 1.0) {
        // Remove particle system
        this.scene.remove(particleSystem);
        particles.dispose();
        particleMaterial.dispose();
        return;
      }
      
      // Update positions (expand outward)
      const expansionSpeed = 2.0;
      for (let i = 0; i < particleCount; i++) {
        const i3 = i * 3;
        const dx = positions[i3] - chestPosition.x;
        const dy = positions[i3 + 1] - chestPosition.y;
        const dz = positions[i3 + 2] - chestPosition.z;
        
        positions[i3] += dx * expansionSpeed * 0.016; // ~60fps
        positions[i3 + 1] += dy * expansionSpeed * 0.016;
        positions[i3 + 2] += dz * expansionSpeed * 0.016;
      }
      
      particles.attributes.position.needsUpdate = true;
      
      // Fade out
      particleMaterial.opacity = 1.0 - progress;
      
      requestAnimationFrame(animate);
    };
    
    animate();
    
    // Also add a bright glow to the chest itself
    this.mesh.traverse((child) => {
      if (child.isMesh && child.material) {
        const originalEmissive = child.material.emissive ? child.material.emissive.clone() : new THREE.Color(0, 0, 0);
        const glowColor = new THREE.Color(0xffe066);
        
        // Set emissive glow
        if (Array.isArray(child.material)) {
          child.material.forEach(mat => {
            if (mat) {
              mat.emissive = glowColor.clone();
              mat.emissiveIntensity = 1.0;
              mat.needsUpdate = true;
            }
          });
        } else {
          child.material.emissive = glowColor.clone();
          child.material.emissiveIntensity = 1.0;
          child.material.needsUpdate = true;
        }
        
        // Fade out glow after 1 second
        setTimeout(() => {
          if (child.material) {
            if (Array.isArray(child.material)) {
              child.material.forEach((mat, idx) => {
                if (mat) {
                  mat.emissive = originalEmissive.clone();
                  mat.emissiveIntensity = 0.0;
                  mat.needsUpdate = true;
                }
              });
            } else {
              child.material.emissive = originalEmissive.clone();
              child.material.emissiveIntensity = 0.0;
              child.material.needsUpdate = true;
            }
          }
        }, 1000);
      }
    });
  }

  /**
   * Dual-model helper: ensure opened mesh is loaded (hidden by default).
   * This is required because our new chest GLBs have no internal animations/states.
   */
  async _ensureOpenedMeshLoaded() {
    if (!this.closedModelPath || !this.openedModelPath) return;
    if (this.openedMeshLoaded || this.openedMeshLoading) return;
    if (!this.scene) return;
    
    this.openedMeshLoading = true;
    try {
      const resolvedOpenedPath = encodeURI(this.resolveAssetPath(this.openedModelPath));
      const gltf = await this.loadModel(resolvedOpenedPath);
      const opened = gltf.scene;

      // Mark as chest so ground raycasts never consider this geometry.
      // IMPORTANT: We keep openedMesh in the scene (hidden) to make swaps instant.
      // Without this tag, the global ground raycast can hit hidden opened meshes
      // and place other chests on top of them (stacked chest bug).
      opened.userData = opened.userData || {};
      opened.userData.isChest = true;
      opened.traverse((node) => {
        node.userData = node.userData || {};
        node.userData.isChest = true;
      });
      
      // Copy transforms from closed mesh so both models align perfectly.
      // This avoids subtle bottom-Y mismatches and prevents "sinking" during swaps/warps.
      if (this.closedMesh) {
        opened.position.copy(this.closedMesh.position);
        opened.rotation.copy(this.closedMesh.rotation);
        opened.scale.copy(this.closedMesh.scale);
      } else {
        opened.position.copy(this.position);
      }
      
      // Process materials and ensure visibility flags
      opened.traverse((child) => {
        if (child.isMesh) {
          child.castShadow = true;
          child.receiveShadow = true;
          child.visible = true;
          child.frustumCulled = false;
          if (child.material) {
            if (Array.isArray(child.material)) {
              child.material = child.material.map((mat) => this.processWeaponMaterial(mat));
            } else {
              child.material = this.processWeaponMaterial(child.material);
            }
          }
        }
      });
      
      opened.visible = false; // Hidden until we swap state
      opened.frustumCulled = false;
      opened.updateMatrixWorld(true);
      
      this.scene.add(opened);
      this.openedMesh = opened;
      this.openedMeshLoaded = true;
    } finally {
      this.openedMeshLoading = false;
    }
  }

  /**
   * Dual-model open animation:
   * - Create sparkle + sound immediately
   * - Animate the CLOSED mesh with a small Y pop + scale pulse
   * - Swap to OPENED mesh at the end (loaded lazily if needed)
   */
  _playDualModelSwapAnimation() {
    const closed = this.closedMesh || this.mesh;
    if (!closed) return;
    
    // Effects
    this.createSparklingGlow();
    this.playOpeningSound();
    
    // Start loading opened mesh in parallel, then animate and swap
    this._ensureOpenedMeshLoaded()
      .then(() => {
        const opened = this.openedMesh;
        if (!opened) return;
        
        const startY = closed.position.y;
        const startScale = closed.scale.clone();
        const popHeight = 0.25; // subtle
        const durationMs = 650;
        const startTime = performance.now();
        
        const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);
        
        const tick = (now) => {
          const tRaw = Math.min(1, (now - startTime) / durationMs);
          const t = easeOutCubic(tRaw);
          
          // Pop up then settle
          const pop = Math.sin(t * Math.PI) * popHeight;
          closed.position.y = startY + pop;
          
          // Small pulse (scale up then back)
          const pulse = 1 + Math.sin(t * Math.PI) * 0.06;
          closed.scale.set(startScale.x * pulse, startScale.y * pulse, startScale.z * pulse);
          closed.updateMatrixWorld(true);
          
          if (tRaw < 1) {
            requestAnimationFrame(tick);
            return;
          }
          
          // Finalize: reset closed transform, swap visibility
          closed.position.y = startY;
          closed.scale.copy(startScale);
          closed.updateMatrixWorld(true);
          
          closed.visible = false;
          opened.visible = true;
          opened.updateMatrixWorld(true);
          
          // Collision + interaction should now point at the opened mesh.
          this.mesh = opened;
        };
        
        requestAnimationFrame(tick);
      })
      .catch((err) => {
        console.warn(`⚠️ [CHEST] ${this.id} dual-model opened mesh load failed, staying on closed mesh:`, err);
      });
  }

  /**
   * Retry aligning this chest to ground a few times.
   * Used when load() ran before the level's floor/map geometry was ready.
   */
  _scheduleGroundAlignRetry(attemptsLeft = 4) {
    if (!this.mesh || !this.scene) return;
    if (attemptsLeft <= 0) return;
    const delayMs = 120;
    setTimeout(() => {
      try {
        // Only retry if still not aligned (we keep the last known raycast in userData).
        const requestedY = this.position?.y;
        const shouldRaycastGround = Number.isFinite(requestedY) ? (requestedY <= 5.0 && requestedY >= -4.0) : true;
        if (!shouldRaycastGround) return;
        
        const raycaster = new THREE.Raycaster();
        const rayOriginY = (Number.isFinite(requestedY) ? requestedY : 1.0) + 300;
        const origin = new THREE.Vector3(this.position.x, rayOriginY, this.position.z);
        raycaster.set(origin, new THREE.Vector3(0, -1, 0));
        raycaster.far = 2000;
        
        const hits = raycaster.intersectObjects(this.scene.children, true);
        const isActuallyVisible = (obj) => {
          let cur = obj;
          while (cur) {
            if (cur.visible === false) return false;
            cur = cur.parent;
          }
          return true;
        };
        const filtered = hits.filter((hit) => {
          const obj = hit.object;
          if (!obj) return false;
          if (!isActuallyVisible(obj)) return false;
          if (obj.userData?.isChest) return false;
          if (obj.userData?.isCheeseBoss) return false;
          return true;
        });
        
        if (filtered.length === 0 || !filtered[0].point || !Number.isFinite(filtered[0].point.y)) {
          this._scheduleGroundAlignRetry(attemptsLeft - 1);
          return;
        }
        
        const groundY = filtered[0].point.y;
        // Align using a fresh world-space Box3 measurement (robust for any GLB origin).
        const epsilon = 0.03;
        const targetBottomY = groundY + epsilon;
        const currentBox = new THREE.Box3().setFromObject(this.mesh);
        const currentMinY = currentBox.min.y;
        const deltaY = targetBottomY - currentMinY;
        
        this.mesh.position.y += deltaY;
        this.mesh.updateMatrixWorld(true);
        if (this.openedMesh) {
          this.openedMesh.position.y += deltaY;
          this.openedMesh.updateMatrixWorld(true);
        }
        
        this.mesh.userData = this.mesh.userData || {};
        this.mesh.userData.raycastGroundY = groundY;
        this.mesh.userData.targetBottomY = targetBottomY;
      } catch {
        this._scheduleGroundAlignRetry(attemptsLeft - 1);
      }
    }, delayMs);
  }
  
  /**
   * Play manual lid rotation animation (if no GLB animation available)
   */
  playManualLidAnimation() {
    if (!this.lidMesh) return;
    
    console.log(`🎁 [CHEST] ${this.id} playing manual lid rotation animation`);
    
    // Rotate lid around X axis (typical chest opening)
    // Target rotation: -90 degrees (or -Math.PI / 2 radians) to open upward
    const targetRotation = -Math.PI / 2; // -90 degrees
    const duration = 1000; // 1 second
    const startRotation = this.lidMesh.rotation.x;
    const startTime = Date.now();
    
    const animate = () => {
      const elapsed = Date.now() - startTime;
      const progress = Math.min(elapsed / duration, 1.0);
      
      // Ease out cubic for smooth animation
      const eased = 1 - Math.pow(1 - progress, 3);
      
      // Interpolate rotation
      this.lidMesh.rotation.x = startRotation + (targetRotation - startRotation) * eased;
      
      if (progress < 1.0) {
        requestAnimationFrame(animate);
      } else {
        console.log(`✅ [CHEST] ${this.id} lid rotation animation complete`);
        // Hide closed meshes and show opened meshes after animation
        this.switchToOpenedState();
      }
    };
    
    animate();
  }
  
  /**
   * Switch chest to opened state (hide closed meshes, show opened meshes)
   * 
   * CRITICAL: Duplicate Lid Detection System - WORKING MECHANISM
   * ============================================================
   * 
   * 🚨 THIS FUNCTION WORKS - DO NOT MODIFY WITHOUT READING THE FULL DOCUMENTATION ABOVE!
   * 
   * The chest2 model contains both closed and opened lid meshes simultaneously.
   * After opening, we must hide the closed lid mesh and keep only the opened (rotated) lid visible.
   * 
   * 6-PASS DETECTION SYSTEM (in order of execution):
   * ================================================
   * 
   * PASS 1 (lines 1569-1574): Hide duplicateLidMeshes from load
   * - Meshes stored during load phase (all lids except main lid)
   * 
   * PASS 2 (lines 1599-1610): Search for all lids by name
   * - Find meshes with "lid", "top", "cover" in name
   * - Hide any that aren't the main lid
   * 
   * PASS 3 (lines 1669-1672): Hide duplicateLidMeshes again (safety)
   * 
   * PASS 4 (lines 1750-1761): Aggressive cleanup if > 4 visible meshes
   * - Uses allChestMeshes array
   * - Hides all lids except main lid
   * 
   * PASS 5 (lines 1820-1832): FINAL PASS - Count all meshes
   * - Traverses entire model to count visible meshes
   * - If > 4, finds all lids and hides all except main lid
   * 
   * PASS 6 (lines 1842-1900): 🎯 ROTATION-BASED CHECK - THE KEY WORKING MECHANISM
   * ==============================================================================
   * 
   * THIS IS THE CRITICAL FIX THAT ACTUALLY WORKS:
   * 
   * Logic:
   * 1. Find ALL lid meshes (by name: "lid", "top", "cover")
   * 2. Find the ROTATED lid: rotation.x ≈ -1.57 radians (-90 degrees)
   * 3. Hide ANY lid that:
   *    - Is in closed position: rotation.x ≈ 0 (within 0.15 rad)
   *    - Is NOT the rotated lid
   * 
   * Why this works:
   * - ✅ Doesn't rely on mesh names (catches any duplicate regardless of name)
   * - ✅ Checks actual state (rotation) instead of assumptions
   * - ✅ The main lid (this.lidMesh) gets rotated during animation
   * - ✅ Any lid still at rotation.x ≈ 0 must be a duplicate
   * 
   * Expected Visible Meshes After Opening: 4 total
   * - Chest_Body (1)
   * - Chest_Handle_01 (1)
   * - Chest_Handle_02 (1)
   * - Chest_Lid (1) - the rotated/opened lid (rotation.x ≈ -1.57)
   * 
   * CRITICAL RULES:
   * ==============
   * 
   * 1. ⚠️ NEVER remove the rotation-based check (lines 1842-1900)
   *    - This is the only pass that works reliably
   *    - All other passes are backups/first attempts
   * 
   * 2. ⚠️ switchToOpenedState() MUST be called AFTER animation completes
   *    - Called at line 1533 after playManualLidAnimation() finishes
   *    - The main lid starts at rotation.x = 0, becomes -1.57 after animation
   * 
   * 3. ⚠️ Rotation thresholds:
   *    - Closed: Math.abs(rotationX) < 0.15 radians
   *    - Opened: Math.abs(rotationX + Math.PI/2) < 0.15 radians
   *    - Don't change without testing
   * 
   * 4. ⚠️ Never hide the main lid (this.lidMesh)
   *    - Even if it appears closed initially, it will be rotated
   *    - The rotated lid IS the main lid
   * 
   * Status: ✅ WORKING - Tested December 19, 2025 (restored from working backup)
   * Verified: chest_001 and chest_002 working correctly
   */
  switchToOpenedState() {
    console.log(`🎁 [CHEST] ${this.id} switching to opened state`);
    
    // Dual-model chests: swap visibility between two independent GLBs.
    // This path intentionally bypasses the chest2 duplicate-lid logic below.
    if (this.closedModelPath && this.openedModelPath) {
      this._ensureOpenedMeshLoaded()
        .then(() => {
          if (this.closedMesh) this.closedMesh.visible = false;
          if (this.openedMesh) {
            this.openedMesh.visible = true;
            this.openedMesh.updateMatrixWorld(true);
            this.mesh = this.openedMesh;
          }
        })
        .catch((err) => {
          console.warn(`⚠️ [CHEST] ${this.id} failed to switch to opened model:`, err);
        });
      return;
    }
    
    // CRITICAL: Hide duplicate lid/top meshes first (closed state versions)
    // These are the duplicate closed tops that need to be hidden
    if (this.duplicateLidMeshes.length > 0) {
      this.duplicateLidMeshes.forEach((mesh) => {
        mesh.visible = false;
        console.log(`🎁 [CHEST] ${this.id} hiding duplicate closed lid/top mesh: "${mesh.name}"`);
      });
    }
    
    // ALWAYS search for duplicate lids, even if we found some during load
    // This ensures we catch all duplicates (some might be in groups or nested)
    console.log(`🔍 [CHEST] ${this.id} Searching for all lid/top meshes to hide duplicates...`);
    if (this.mesh) {
      const foundLids = [];
      this.mesh.traverse((node) => {
        if (node.isMesh) {
          const nameLower = (node.name || '').toLowerCase();
          const isLidLike = nameLower.includes('lid') || nameLower.includes('top') || nameLower.includes('cover');
          
          if (isLidLike) {
            foundLids.push({
              node: node,
              name: node.name,
              isMainLid: node === this.lidMesh,
              position: node.position ? node.position.clone() : null
            });
          }
        }
      });
      
      // If we found multiple lids, hide the ones that aren't the main lid
      // The main lid is the one we're rotating, so it should stay visible
      if (foundLids.length > 1) {
        console.log(`🔍 [CHEST] ${this.id} Found ${foundLids.length} lid meshes, hiding duplicates...`);
        foundLids.forEach(({ node, name, isMainLid }) => {
          if (!isMainLid) {
            node.visible = false;
            console.log(`🎁 [CHEST] ${this.id} hiding duplicate lid mesh: "${name}"`);
          } else {
            // Ensure main lid stays visible
            node.visible = true;
            console.log(`🎁 [CHEST] ${this.id} keeping main lid visible: "${name}"`);
          }
        });
      } else if (foundLids.length === 1 && foundLids[0].isMainLid) {
        // Only one lid found and it's the main one - good
        console.log(`✅ [CHEST] ${this.id} Only one lid found (main lid), no duplicates to hide`);
      }
    }
    
    // Hide all closed state meshes
    this.closedMeshes.forEach((mesh) => {
      mesh.visible = false;
      console.log(`🎁 [CHEST] ${this.id} hiding closed mesh: "${mesh.name}"`);
    });
    
    // Show all opened state meshes
    this.openedMeshes.forEach((mesh) => {
      mesh.visible = true;
      console.log(`🎁 [CHEST] ${this.id} showing opened mesh: "${mesh.name}"`);
    });
    
    // If no opened meshes found, try alternative approach:
    // Hide the lid mesh itself (since it's now "opened" and rotated)
    if (this.openedMeshes.length === 0 && this.closedMeshes.length === 0) {
      // Alternative: If the model has both states as separate meshes,
      // we might need to hide the entire closed chest and show opened chest
      // For now, just hide the lid if it exists
      if (this.lidMesh) {
        // Don't hide lid - keep it visible but rotated
        // Instead, check if there are duplicate chest meshes
        console.log(`🎁 [CHEST] ${this.id} no explicit opened/closed meshes found, keeping lid visible`);
      }
    }
    
    // Also check if the main mesh has children that represent closed state
    // This handles cases where the GLB has both states but without explicit naming
    if (this.mesh) {
      const chestMeshes = [];
      this.mesh.traverse((child) => {
        if (child.isMesh || child.isGroup) {
          const nameLower = (child.name || '').toLowerCase();
          
          // Collect all chest-related meshes
          if (nameLower.includes('chest') || 
              nameLower.includes('lid') || 
              nameLower.includes('base') ||
              nameLower.includes('body')) {
            chestMeshes.push({ node: child, name: child.name, nameLower });
          }
          
          // Hide explicitly named closed meshes
          if (nameLower.includes('closed') || 
              (nameLower.includes('chest') && nameLower.includes('close'))) {
            child.visible = false;
            console.log(`🎁 [CHEST] ${this.id} hiding closed chest mesh: "${child.name}"`);
          }
        }
      });
      
      // CRITICAL: Hide duplicate lid/top meshes (closed state versions)
      // These are the duplicate closed tops that appear alongside the opened lid
      this.duplicateLidMeshes.forEach((mesh) => {
        mesh.visible = false;
        console.log(`🎁 [CHEST] ${this.id} hiding duplicate closed lid/top mesh: "${mesh.name}"`);
      });
      
      // If we have multiple chest meshes that look like duplicates,
      // hide the ones that aren't the lid (lid should stay visible and rotated)
      if (chestMeshes.length > 1) {
        console.log(`🔍 [CHEST] ${this.id} Found ${chestMeshes.length} chest-related meshes, checking for duplicates...`);
        
        // Strategy: Keep the main chest body/base visible (it's the container)
        // Only hide duplicate closed chest meshes if they exist
        // The lid is already rotated, so we keep it visible
        chestMeshes.forEach(({ node, name, nameLower }) => {
          const isLid = node === this.lidMesh || nameLower.includes('lid');
          const isOpened = nameLower.includes('open') || nameLower.includes('opened');
          const isBody = nameLower.includes('body') && !nameLower.includes('lid');
          const isHandle = nameLower.includes('handle');
          const isDuplicateLid = this.duplicateLidMeshes.includes(node);
          
          // CRITICAL: Only hide meshes that are EXPLICITLY marked as closed
          // "Chest_Body", "Chest_Handle_01", etc. should NEVER be hidden - they're the main structure!
          const isExplicitlyClosed = (nameLower.includes('closed') && !nameLower.includes('body') && !nameLower.includes('handle')) || 
                                     (nameLower.includes('close') && nameLower.includes('duplicate')) ||
                                     (nameLower.includes('close') && nameLower.includes('old'));
          
          // ALWAYS keep: lid (main one), body (main container), handles, opened meshes
          // ALWAYS hide: duplicate lid meshes (closed state versions)
          // NEVER hide: body, handles, main lid, opened meshes - these are essential parts!
          if (isDuplicateLid) {
            // Hide duplicate lid meshes (closed state)
            node.visible = false;
            console.log(`🎁 [CHEST] ${this.id} hiding duplicate lid mesh (closed state): "${name}"`);
          } else if (isBody || isHandle || isLid || isOpened) {
            // Force these to be visible - they're essential parts of the chest
            node.visible = true;
            console.log(`🎁 [CHEST] ${this.id} keeping visible (essential part): "${name}" (${isBody ? 'body' : isHandle ? 'handle' : isLid ? 'lid' : 'opened'})`);
          } else if (isExplicitlyClosed) {
            // Only hide if it's explicitly marked as closed AND not an essential part
            node.visible = false;
            console.log(`🎁 [CHEST] ${this.id} hiding explicitly closed mesh: "${name}"`);
          }
          // If it's neither essential nor explicitly closed, leave it as is
        });
        
        // DO NOT hide the main chest body mesh - it's the container that should stay visible
        if (this.chestBodyMesh && this.chestBodyMesh !== this.lidMesh) {
          this.chestBodyMesh.visible = true; // Keep it visible!
          console.log(`🎁 [CHEST] ${this.id} keeping chest body mesh visible: "${this.chestBodyMesh.name}"`);
        }
      }
      
      // Alternative approach: If we still see duplicates, be more selective
      // Keep: lid, body, handles, opened meshes
      // Hide: only explicitly closed or duplicate meshes
      if (this.allChestMeshes.length > 0) {
        let visibleCount = 0;
        this.allChestMeshes.forEach((mesh) => {
          if (mesh.visible) visibleCount++;
        });
        
        // If we still have too many visible meshes (likely duplicates), be selective
        // Expected: Body (1) + Handles (2) + Lid (1) = 4 meshes
        // If we have more than 4, we likely have duplicate lids
        if (visibleCount > 4) {
          console.log(`🔍 [CHEST] ${this.id} Too many visible meshes (${visibleCount}), applying aggressive duplicate hiding...`);
          
          // Find ALL lid meshes and hide all except the main one
          const allLids = [];
          this.allChestMeshes.forEach((mesh) => {
            const nameLower = (mesh.name || '').toLowerCase();
            if (nameLower.includes('lid') || nameLower.includes('top') || nameLower.includes('cover')) {
              allLids.push({
                mesh: mesh,
                name: mesh.name,
                isMainLid: mesh === this.lidMesh
              });
            }
          });
          
          // Hide all lids except the main one (the one we rotated)
          if (allLids.length > 1) {
            console.log(`🔍 [CHEST] ${this.id} Found ${allLids.length} lid meshes, hiding duplicates...`);
            allLids.forEach(({ mesh, name, isMainLid }) => {
              if (!isMainLid) {
                mesh.visible = false;
                console.log(`🎁 [CHEST] ${this.id} aggressively hiding duplicate lid: "${name}"`);
              } else {
                mesh.visible = true; // Ensure main lid stays visible
                console.log(`🎁 [CHEST] ${this.id} keeping main lid visible: "${name}"`);
              }
            });
          }
          
          // Ensure body and handles stay visible
          this.allChestMeshes.forEach((mesh) => {
            const nameLower = (mesh.name || '').toLowerCase();
            const isBody = nameLower.includes('body') && !nameLower.includes('lid');
            const isHandle = nameLower.includes('handle');
            const isMainLid = mesh === this.lidMesh;
            
            if (isBody || isHandle || isMainLid) {
              mesh.visible = true;
            }
          });
        }
      }
      
      // ========================================================================
      // FINAL SAFETY PASS: Count ALL meshes in the entire chest model
      // ========================================================================
      // This catches any meshes that weren't in allChestMeshes.
      // This is the last line of defense against duplicate lids.
      // If we still have more than 4 visible meshes, we aggressively find and hide duplicate lids.
      // 
      // Expected: 4 visible meshes (Body + 2 Handles + 1 Lid)
      // If > 4: Duplicate lids are present and must be hidden
      // 
      // Status: ✅ WORKING - Tested December 15, 2025
      // ========================================================================
      if (this.mesh) {
        const allMeshesInModel = [];
        this.mesh.traverse((node) => {
          if (node.isMesh) {
            allMeshesInModel.push(node);
          }
        });
        
        let finalVisibleCount = 0;
        allMeshesInModel.forEach((mesh) => {
          if (mesh.visible) finalVisibleCount++;
        });
        
        // Expected: Body (1) + Handles (2) + Lid (1) = 4 meshes
        if (finalVisibleCount > 4) {
          console.log(`🔍 [CHEST] ${this.id} FINAL PASS: ${allMeshesInModel.length} total meshes, ${finalVisibleCount} visible (should be 4)`);
          
          // Find ALL lid meshes in the entire model
          const allLidsInModel = [];
          allMeshesInModel.forEach((mesh) => {
            const nameLower = (mesh.name || '').toLowerCase();
            if (nameLower.includes('lid') || nameLower.includes('top') || nameLower.includes('cover')) {
              allLidsInModel.push({
                mesh: mesh,
                name: mesh.name,
                isMainLid: mesh === this.lidMesh,
                isVisible: mesh.visible
              });
            }
          });
          
          // Hide ALL lids except the main one
          if (allLidsInModel.length > 1) {
            console.log(`🔍 [CHEST] ${this.id} FINAL PASS: Found ${allLidsInModel.length} lid meshes, hiding all except main lid...`);
            allLidsInModel.forEach(({ mesh, name, isMainLid }) => {
              if (!isMainLid) {
                mesh.visible = false;
                console.log(`🎁 [CHEST] ${this.id} FINAL PASS: Hiding duplicate lid: "${name}"`);
              } else {
                mesh.visible = true;
                console.log(`🎁 [CHEST] ${this.id} FINAL PASS: Keeping main lid visible: "${name}"`);
              }
            });
          }
          
          // Final count after hiding
          let finalCount = 0;
          allMeshesInModel.forEach((mesh) => {
            if (mesh.visible) finalCount++;
          });
          console.log(`✅ [CHEST] ${this.id} FINAL PASS: ${finalCount} visible meshes after hiding duplicates (should be 4)`);
        }
        
        // ========================================================================
        // 🎯 CRITICAL FIX: ROTATION-BASED DUPLICATE LID DETECTION - THE WORKING MECHANISM
        // ========================================================================
        // 
        // 🚨 THIS IS THE KEY WORKING FIX - DO NOT MODIFY WITHOUT UNDERSTANDING IT!
        // 
        // HOW IT WORKS:
        // =============
        // After opening, the main lid (this.lidMesh) gets ROTATED to -90 degrees (rotation.x ≈ -1.57)
        // Any lid that still has rotation.x ≈ 0 (closed position) is a duplicate and must be hidden.
        // 
        // WHY THIS WORKS:
        // ===============
        // - Doesn't rely on mesh names (catches "Wooden_Bar" or any duplicate regardless of name)
        // - Checks actual state (rotation) instead of assumptions
        // - Works even if duplicate lids have same name as main lid
        // - The main lid IS the rotated lid - any lid at rotation.x ≈ 0 must be duplicate
        // 
        // LOGIC:
        // ======
        // 1. Find ALL lid meshes (by name: "lid", "top", "cover")
        // 2. Find the ROTATED lid: rotation.x ≈ -1.57 radians (-90 degrees)
        // 3. Hide ANY lid that is in closed position (rotation.x ≈ 0) AND is NOT the rotated lid
        // 
        // CRITICAL RULES:
        // ==============
        // - ⚠️ This check runs AFTER animation completes (switchToOpenedState called at line 1533)
        // - ⚠️ The main lid starts at rotation.x = 0, becomes -1.57 after animation
        // - ⚠️ Rotation threshold: 0.15 radians (≈ 8.6 degrees) - don't change without testing
        // - ⚠️ Never hide the rotated lid (it IS the main lid)
        // - ⚠️ This is the final/last check - all other passes are backups
        // 
        // STATUS: ✅ WORKING - Verified December 19, 2025
        // ========================================================================
        const allLidMeshes = [];
        this.mesh.traverse((node) => {
          if (node.isMesh) {
            const nameLower = (node.name || '').toLowerCase();
            if (nameLower.includes('lid') || nameLower.includes('top') || nameLower.includes('cover')) {
              allLidMeshes.push({
                mesh: node,
                name: node.name,
                rotationX: node.rotation.x,
                isMainLid: node === this.lidMesh,
                isVisible: node.visible
              });
            }
          }
        });
        
        if (allLidMeshes.length > 0) {
          console.log(`🔍 [CHEST] ${this.id} Checking lid rotations to find closed duplicate lids...`, allLidMeshes.map(l => ({
            name: l.name,
            rotationX: l.rotationX,
            isMainLid: l.isMainLid,
            isVisible: l.isVisible
          })));
          
          // Find the lid that was rotated (should have rotation.x around -1.57 radians = -90 degrees)
          const rotatedLid = allLidMeshes.find(l => Math.abs(l.rotationX + Math.PI / 2) < 0.15); // Within 0.15 rad of -90 degrees
          
          // ========================================================================
          // 🎯 CRITICAL WORKING LOGIC - ROTATION-BASED DUPLICATE HIDING
          // ========================================================================
          // 
          // THIS IS THE KEY THAT MAKES THE SYSTEM WORK:
          // 
          // The main lid (this.lidMesh) gets rotated from 0 to -90 degrees during animation.
          // After animation completes, we can identify lids by their rotation state:
          // - Rotated lid (rotation.x ≈ -1.57): This IS the main lid - keep it visible ✅
          // - Closed lid (rotation.x ≈ 0): This is a duplicate - hide it ❌
          // 
          // Why this works better than name-based checks:
          // - Doesn't depend on mesh names (catches "Wooden_Bar" or any duplicate)
          // - Checks actual state instead of assumptions
          // - Works even if duplicates have same name as main lid
          // 
          // Execution flow:
          // 1. switchToOpenedState() is called AFTER animation completes (line ~1533)
          // 2. At that point, main lid has rotation.x ≈ -1.57 (rotated)
          // 3. Any lid with rotation.x ≈ 0 must be a duplicate (still closed)
          // 4. Hide the duplicate, keep the rotated one
          // ========================================================================
          allLidMeshes.forEach(({ mesh, name, rotationX, isMainLid }) => {
            // Check if this lid is still in closed position (rotation.x close to 0)
            const isClosedPosition = Math.abs(rotationX) < 0.15; // Within 0.15 rad of 0
            
            // If this lid is in closed position AND there's a rotated lid (chest is opened)
            // AND this is not the rotated lid, hide it
            if (rotatedLid && isClosedPosition && mesh !== rotatedLid.mesh) {
              mesh.visible = false;
              console.log(`🎁 [CHEST] ${this.id} HIDING closed lid (not rotated): "${name}" (rotation.x: ${rotationX.toFixed(3)}, rotated lid found: "${rotatedLid.name}")`);
            } else if (rotatedLid && mesh === rotatedLid.mesh) {
              // Ensure the rotated lid stays visible (this IS the main lid)
              mesh.visible = true;
              console.log(`🎁 [CHEST] ${this.id} KEEPING rotated lid visible: "${name}" (rotation.x: ${rotationX.toFixed(3)})`);
            } else if (!rotatedLid && allLidMeshes.length > 1 && isClosedPosition && !isMainLid) {
              // If no lid was rotated yet but we have multiple lids, hide non-main closed lids
              // This handles cases where animation hasn't started but duplicate lids are present
              mesh.visible = false;
              console.log(`🎁 [CHEST] ${this.id} HIDING duplicate closed lid (no rotation yet): "${name}"`);
            }
          });
        }
      }
    }
  }
  
  /**
   * Play opening sound effect
   */
  playOpeningSound() {
    try {
      // Create audio element for chest opening sound
      // Use resolveAssetPath if available (passed from main.js), otherwise fallback to old logic
      let audioPath;
      if (this.resolveAssetPath) {
        audioPath = this.resolveAssetPath("sounds/SFX/chest.mp3");
        console.log(`🎁 [CHEST SOUND] Resolved path: "${audioPath}" (from "sounds/SFX/chest.mp3")`);
      } else {
        // Fallback: Old environment detection logic (for backward compatibility)
        const isDevServer = window.location.hostname === 'localhost' && window.location.port !== '';
        audioPath = isDevServer 
          ? './public/sounds/SFX/chest.mp3'  // Dev server (Vite) - relative to HTML
          : '/sounds/SFX/chest.mp3';  // Production - absolute from web root
        console.warn(`🎁 [CHEST SOUND] resolveAssetPath not available, using fallback: "${audioPath}"`);
      }
      
      const audio = new Audio(audioPath);
      audio.volume = 0.6;
      audio.play().catch(err => {
        // Fallback: Try alternative sound or use a simple beep
        console.log(`🎁 [CHEST] Could not play chest opening sound, using fallback:`, err);
        // Create a simple beep sound using Web Audio API as fallback
        this.playBeepSound();
      });
    } catch (error) {
      console.warn(`🎁 [CHEST] Failed to play opening sound:`, error);
      // Fallback beep
      this.playBeepSound();
    }
  }
  
  /**
   * Play a simple beep sound as fallback
   */
  playBeepSound() {
    try {
      const audioContext = new (window.AudioContext || window.webkitAudioContext)();
      const oscillator = audioContext.createOscillator();
      const gainNode = audioContext.createGain();
      
      oscillator.connect(gainNode);
      gainNode.connect(audioContext.destination);
      
      oscillator.frequency.value = 800; // Higher pitch for "success" sound
      oscillator.type = 'sine';
      
      gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
      gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
      
      oscillator.start(audioContext.currentTime);
      oscillator.stop(audioContext.currentTime + 0.3);
    } catch (error) {
      console.warn(`🎁 [CHEST] Could not play beep sound:`, error);
    }
  }
  
  /**
   * Update chest state (called every frame)
   * @param {number} delta - Time delta since last frame
   */
  update(delta) {
    // Update animation mixer if available
    if (this.openingAnimation) {
      this.openingAnimation.update(delta);
    }
  }
  
  /**
   * Dispose of chest resources
   */
  dispose() {
    // Mark disposed first so any pending async work bails out safely
    this.isDisposed = true;
    if (this._loadTimeoutId) {
      clearTimeout(this._loadTimeoutId);
      this._loadTimeoutId = null;
    }
    // Dual-model cleanup: if we swapped mesh to openedMesh, ensure both are removed.
    if (this.openedMesh && this.openedMesh !== this.mesh) {
      if (this.openedMesh.parent) {
        this.openedMesh.parent.remove(this.openedMesh);
      }
      this.openedMesh.traverse((child) => {
        if (child.isMesh) {
          if (child.geometry) child.geometry.dispose();
          if (child.material) {
            if (Array.isArray(child.material)) child.material.forEach((mat) => mat.dispose());
            else child.material.dispose();
          }
        }
      });
      this.openedMesh = null;
    }

    if (this.mesh) {
      // Remove from scene
      if (this.mesh.parent) {
        this.mesh.parent.remove(this.mesh);
      }
      
      // Dispose of geometry and materials
      this.mesh.traverse((child) => {
        if (child.isMesh) {
          if (child.geometry) {
            child.geometry.dispose();
          }
          if (child.material) {
            if (Array.isArray(child.material)) {
              child.material.forEach(mat => mat.dispose());
            } else {
              child.material.dispose();
            }
          }
        }
      });
      
      this.mesh = null;
    }
    
    this.closedMesh = null;
    
    if (this.openMesh) {
      if (this.openMesh.parent) {
        this.openMesh.parent.remove(this.openMesh);
      }
      this.openMesh = null;
    }
    
    if (this.openingAnimation) {
      this.openingAnimation = null;
    }
    
    this.isLoaded = false;
    this.isLoading = false;
  }
}

/**
 * Chest System Class
 * Manages all chests across all levels
 */
/**
 * ============================================================================
 * CHEST SYSTEM CLASS
 * ============================================================================
 * 
 * Manages all chests across all levels.
 * 
 * Features:
 * - Chest loading and positioning
 * - Interaction detection (distance-based)
 * - Opening animation (lid rotation)
 * - State management (closed/opened)
 * - Reward system (DSPOINC via API)
 * - Chest counter (tracks opened chests)
 * 
 * ============================================================================
 */
/**
 * ChestSystem - Multi-Level Treasure Chest Management System
 * 
 * 📝 SYSTEM ARCHITECTURE (December 18, 2025):
 * ==========================================
 * 
 * This class manages treasure chests across UNLIMITED levels in a scalable,
 * performant way designed for decades of game development.
 * 
 * DATA STRUCTURE:
 * ===============
 * this.chests: Map<levelId, Map<chestId, Chest>>
 * 
 * - Outer Map: Organizes chests by level (Level 1, Level 2, Level 3, ...)
 * - Inner Map: Organizes chests within each level (chest_001, chest_002, ...)
 * - Values: Chest instances with full state (mesh, position, opened)
 * 
 * LEVEL ISOLATION:
 * ================
 * Each level's chests are completely isolated:
 * - Level 1 can have chest_001, Level 2 can also have chest_001
 * - No naming conflicts between levels
 * - Fast lookup: O(1) access to any chest in any level
 * - Easy cleanup: Remove entire level's chests at once
 * 
 * MEMORY MANAGEMENT:
 * ==================
 * - Only current level's chests are loaded in scene
 * - Unused chests are disposed (geometry, materials, meshes)
 * - Maps cleared when levels change
 * - Scales efficiently to 100+ levels
 * 
 * GRASS SYSTEM INTEGRATION:
 * =========================
 * - this.grassSystem: Reference to grass exclusion system
 * - Chests automatically register exclusion zones when loaded
 * - Works at ANY Y position (ground, elevated, underground)
 * - Prevents grass from rendering inside/under chests
 * 
 * PERSISTENCE:
 * ============
 * - this.openedChestsCache: Set of opened chest IDs (from database)
 * - Opened state persists across sessions
 * - Player cannot open same chest twice
 * - Counter tracks total chests opened (localStorage)
 * 
 * SCALING EXAMPLE (100 levels):
 * =============================
 * Level 1: 5 chests → this.chests.get('LEVEL1').size = 5
 * Level 2: 8 chests → this.chests.get('LEVEL2').size = 8
 * Level 3: 3 chests → this.chests.get('LEVEL3').size = 3
 * ...
 * Level 100: 10 chests → this.chests.get('LEVEL100').size = 10
 * 
 * Total: 600+ chests across 100 levels
 * Memory: Only current level's chests loaded (5-10 chests typical)
 * Performance: O(1) lookup, no degradation
 */
export class ChestSystem {
  constructor(
    scene,
    loadModel,
    processWeaponMaterial,
    grassSystem = null,
    resolveAssetPath = null,
    chestModelConfig = null
  ) {
    this.scene = scene;
    this.loadModel = loadModel;
    this.processWeaponMaterial = processWeaponMaterial;
    this.grassSystem = grassSystem;
    this.resolveAssetPath = resolveAssetPath || ((path) => path);

    // Global chest model config (dual-model etc.)
    this.chestModelConfig = chestModelConfig;

    // 🔁 HARD RESET callback (FIXED)
    this.onRecreateLevelChests = chestModelConfig?.onRecreateLevelChests || null;

    // Chest storage
    this.chests = new Map();
    this.activeLevel = null;

    // Persistence
    this.chestsOpenedCount = this.loadChestsOpenedCount();
    this.openedChestsCache = new Set();
    this.openedChestsLoaded = false;
    this.openedChestsLoadedForDiscordId = null;
  }
  
  /**
   * Load chests opened count from localStorage
   * @returns {number} - Number of chests opened
   */
  loadChestsOpenedCount() {
    try {
      const count = localStorage.getItem('chests_opened_count');
      return count ? parseInt(count, 10) : 0;
    } catch (error) {
      console.warn('⚠️ [CHEST SYSTEM] Failed to load chests opened count:', error);
      return 0;
    }
  }
  
  /**
   * Save chests opened count to localStorage
   * @param {number} count - Number of chests opened
   */
  saveChestsOpenedCount(count) {
    try {
      localStorage.setItem('chests_opened_count', count.toString());
      console.log(`📊 [CHEST SYSTEM] Chests opened count saved: ${count}`);
    } catch (error) {
      console.warn('⚠️ [CHEST SYSTEM] Failed to save chests opened count:', error);
    }
  }
  
  /**
   * Increment chests opened counter
   * Called when a chest is successfully opened
   */
  incrementChestsOpenedCount() {
    this.chestsOpenedCount++;
    this.saveChestsOpenedCount(this.chestsOpenedCount);
    console.log(`📊 [CHEST SYSTEM] Chests opened: ${this.chestsOpenedCount}`);
  }
  
  /**
   * Get current chests opened count
   * @returns {number} - Number of chests opened
   */
  getChestsOpenedCount() {
    return this.chestsOpenedCount;
  }
  
  /**
   * Load opened chests from database for a player
   * @param {string} discordId - Player Discord ID
   * @param {string} apiBaseUrl - API base URL (for environment detection)
   * @returns {Promise<Set<string>>} - Set of opened chest IDs
   */
  async loadOpenedChests(discordId) {
    if (!discordId) {
      console.warn("⚠️ [CHEST SYSTEM] Cannot load opened chests without Discord ID");
      return new Set();
    }

    // 🧪 DEV OVERRIDE LOCAL CHEST: on localhost we don't restore opened chests
    // This makes all chests start CLOSED every refresh, which is perfect for testing.
    if (window.location.hostname === "localhost") {
      console.log("🧪 [CHEST SYSTEM] Localhost detected – skipping opened-chest persistence. All chests start CLOSED.");
      this.openedChestsCache = new Set();
      this.openedChestsLoaded = true;
      this.openedChestsLoadedForDiscordId = null;
      return this.openedChestsCache;
    }

    // ... existing production logic continues here ...

    
    // If we already loaded for a DIFFERENT user, reset and reload.
    if (this.openedChestsLoaded && this.openedChestsLoadedForDiscordId && this.openedChestsLoadedForDiscordId !== discordId) {
      console.warn("⚠️ [CHEST SYSTEM] Discord ID changed since last load; resetting opened chest cache.", {
        previousDiscordId: this.openedChestsLoadedForDiscordId,
        newDiscordId: discordId
      });
      this.openedChestsCache.clear();
      this.openedChestsLoaded = false;
      this.openedChestsLoadedForDiscordId = null;
    }
    
    if (this.openedChestsLoaded && this.openedChestsLoadedForDiscordId === discordId) {
      // Already loaded for this user, return cached data
      return this.openedChestsCache;
    }
    
    try {
      const endpoint = apiBaseUrl ? `${apiBaseUrl}/api/user/get-opened-chests.php` : '/api/user/get-opened-chests.php';
      
      console.log(`📥 [CHEST SYSTEM] Loading opened chests for player ${discordId}...`);
      
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ discord_id: discordId })
      });
      
      const result = await response.json().catch(() => ({ success: false, error: "Invalid JSON" }));
      
      if (response.ok && result.success && result.data) {
        const openedChests = result.data.opened_chests || [];
        const chestIds = openedChests.map(chest => chest.chest_id);
        
        this.openedChestsCache = new Set(chestIds);
        this.openedChestsLoaded = true;
        this.openedChestsLoadedForDiscordId = discordId;
        
        console.log(`✅ [CHEST SYSTEM] Loaded ${chestIds.length} opened chests:`, chestIds);
        return this.openedChestsCache;
      } else {
        console.warn(`⚠️ [CHEST SYSTEM] Failed to load opened chests:`, result.error || 'Unknown error');
        return new Set();
      }
    } catch (error) {
      console.error(`❌ [CHEST SYSTEM] Error loading opened chests:`, error);
      return new Set();
    }
  }
  
  /**
   * Check if a chest is already opened
   * @param {string} chestId - Chest ID to check
   * @returns {boolean} - True if chest is already opened
   */
  isChestOpened(chestId) {
    return this.openedChestsCache.has(chestId);
  }
  
  /**
   * Mark a chest as opened (adds to cache)
   * @param {string} chestId - Chest ID to mark as opened
   */
  markChestAsOpened(chestId) {
    this.openedChestsCache.add(chestId);
    console.log(`✅ [CHEST SYSTEM] Marked chest ${chestId} as opened in cache`);
  }
  
  /**
   * Restore opened state for a chest (called after chest model loads)
   * @param {Chest} chest - Chest object to restore
   */
  restoreChestOpenedState(chest) {
    if (!chest || !chest.id) {
      return;
    }
    
    // Check if this chest was already opened
    if (this.isChestOpened(chest.id)) {
      console.log(`🔄 [CHEST SYSTEM] Restoring opened state for chest ${chest.id}...`);
      
      // Mark as opened
      chest.opened = true;
      chest.canInteract = false;
      
      // Restore visual state (hide closed, show opened)
      if (chest.mesh && chest.isLoaded) {
        // Call switchToOpenedState to restore visual appearance
        if (typeof chest.switchToOpenedState === 'function') {
          chest.switchToOpenedState();
          console.log(`✅ [CHEST SYSTEM] Restored opened state for chest ${chest.id}`);
        } else {
          console.warn(`⚠️ [CHEST SYSTEM] Chest ${chest.id} does not have switchToOpenedState method`);
        }
      } else {
        // Chest not loaded yet, wait for it to load
        console.log(`⏳ [CHEST SYSTEM] Chest ${chest.id} not loaded yet, will restore state after load`);
        // Store a flag to restore after load
        chest.userData.needsOpenedStateRestore = true;
      }
    }
  }
  
  /**
   * Add a chest to a level
   * 
   * STANDARDIZED: All chests use chest2 (has animation support)
   * - chest1 type is deprecated and automatically converts to chest2
   * 
   * @param {string} levelId - Level identifier (e.g., LEVEL_IDS.LEVEL1)
   * @param {Object} config - Chest configuration
   * @param {string} config.id - REQUIRED: Unique chest ID (e.g., 'chest_001', 'chest_002')
   * @param {string} config.type - REQUIRED: Always use 'chest2' (standardized, has animation)
   *                                Legacy 'chest1' automatically converts to 'chest2'
   * @param {THREE.Vector3} config.position - REQUIRED: Chest position (Y should be 1.0 to match bear trap)
   * @param {number} config.dspoincAmount - REQUIRED: Base DSPOINC reward amount
   * @param {string} config.levelId - REQUIRED: Level identifier for API (e.g., 'CHEESE_TEMPLE_LEVEL1')
   * 
   * @example
   * // Create a chest in Level 1
   * chestSystem.addChest(LEVEL_IDS.LEVEL1, {
   *   id: 'chest_001',
   *   type: 'chest2',  // Always use chest2
   *   position: new THREE.Vector3(55, 1.0, 20),  // Y must be 1.0
   *   dspoincAmount: 100,
   *   levelId: 'CHEESE_TEMPLE_LEVEL1'
   * });
   */
  addChest(levelId, config) {
    console.log(`🔍 [CHEST SYSTEM] addChest called:`, {
      levelId: levelId,
      chestId: config.id,
      position: config.position ? { x: config.position.x, y: config.position.y, z: config.position.z } : 'missing',
      type: config.type,
      dspoincAmount: config.dspoincAmount,
      levelIdInConfig: config.levelId
    });
    
    // Validate required fields
    if (!config.id || !config.type || !config.position || !config.dspoincAmount || !config.levelId) {
      console.error(`❌ [CHEST SYSTEM] Invalid chest config:`, config);
      return;
    }
    
    // Validate chest type.
    // NOTE (Jan 2026): We still accept config.type = 'chest2' everywhere, but if a global
    // dual-model config is provided, we will render with the dual models instead of chest2.
    if (config.type === 'chest1') {
      // Legacy support: automatically convert chest1 to chest2
      console.log(`🔄 [CHEST SYSTEM] Converting chest1 to chest2 (standardized)`);
      config.type = 'chest2';
    } else if (config.type !== 'chest2') {
      console.error(`❌ [CHEST SYSTEM] Invalid chest type: ${config.type}. Must be 'chest2' (chest1 is deprecated)`);
      return;
    }
    
    // Get or create level chest map
    if (!this.chests.has(levelId)) {
      this.chests.set(levelId, new Map());
    }
    
    const levelChests = this.chests.get(levelId);
    
    // Check if chest already exists
    // CRITICAL: Always remove existing chest to ensure clean recreation after warp/respawn
    // This prevents issues where chests exist but have wrong Y positions or aren't visible
    if (levelChests.has(config.id)) {
      const existingChest = levelChests.get(config.id);
      console.log(`🔄 [CHEST SYSTEM] Chest ${config.id} already exists, removing for clean recreation...`, {
        isLoaded: existingChest.isLoaded,
        hasMesh: !!existingChest.mesh,
        position: existingChest.position,
        inScene: existingChest.mesh ? this.scene.children.includes(existingChest.mesh) : false
      });
      existingChest.dispose();
      levelChests.delete(config.id);
      // Continue to create new chest below
    }
    
    // Create new chest
    const chest = new Chest({
      ...config,
      scene: this.scene,
      loadModel: this.loadModel,
      processWeaponMaterial: this.processWeaponMaterial,
      resolveAssetPath: this.resolveAssetPath,
      
      // Apply global model override if provided (dual-model chest system).
      closedModelPath: this.chestModelConfig?.closedModelPath || config.closedModelPath || null,
      openedModelPath: this.chestModelConfig?.openedModelPath || config.openedModelPath || null
    });
    
    // Add to level chest map
    levelChests.set(config.id, chest);
    
    console.log(`✅ [CHEST SYSTEM] Added chest ${config.id} to level ${levelId}:`, {
      type: config.type,
      position: config.position,
      dspoincAmount: config.dspoincAmount,
      levelId: config.levelId,
      grassSystemAvailable: !!this.grassSystem
    });
    
    // Load chest model (async, will complete in background)
    // Use setTimeout to ensure it loads after scene is ready
    // CRITICAL: Reduced delay to 50ms for faster loading, but still allows scene to be ready
    chest._loadTimeoutId = setTimeout(async () => {
      try {
        // If this chest was cleared/disposed before its delayed load runs, skip.
        if (chest.isDisposed) return;
        const stillCurrent = this.chests.get(levelId)?.get(config.id) === chest;
        if (!stillCurrent) return;
        await chest.load();
        console.log(`✅ [CHEST SYSTEM] Chest ${config.id} loaded successfully:`, {
          id: config.id,
          type: config.type,
          position: chest.position,
          isLoaded: chest.isLoaded,
          hasMesh: !!chest.mesh,
          inScene: chest.mesh ? this.scene.children.includes(chest.mesh) : false
        });
        
        // Guard again after await in case a level clear happened mid-load.
        if (chest.isDisposed) return;
        if (this.chests.get(levelId)?.get(config.id) !== chest) return;
        
        // CHEST PERSISTENCE: Restore opened state if chest was previously opened (January 4, 2026)
        // CRITICAL: This ensures chests appear as opened when warping back to a level
        // Must be called after chest model loads so mesh is available for switchToOpenedState
        this.restoreChestOpenedState(chest);
        
        // GRASS EXCLUSION ZONE AUTO-REGISTRATION (Phase 2)
        // Register this chest with grass system after it's loaded
        // CRITICAL: Add a small delay to ensure mesh is fully ready in scene
        setTimeout(() => {
          if (!this.grassSystem) {
            console.warn(`⚠️ [CHEST SYSTEM] Grass system not available for chest ${config.id}`);
            return;
          }
          
          if (!chest.mesh) {
            console.warn(`⚠️ [CHEST SYSTEM] Chest ${config.id} mesh not available for exclusion zone registration`);
            return;
          }
          
          if (!chest.isLoaded) {
            console.warn(`⚠️ [CHEST SYSTEM] Chest ${config.id} not fully loaded yet`);
            return;
          }
          
          // CRITICAL: Update world matrix before calculating bounding box
          // This ensures the bounding box is in world coordinates
          chest.mesh.updateMatrixWorld(true);
          
          const worldBox = new THREE.Box3().setFromObject(chest.mesh);
          const registered = this.grassSystem.registerExclusionZone(chest.id, worldBox, 0.5);
          if (registered) {
            console.log(`🌱 [CHEST SYSTEM] Chest ${config.id} registered with grass exclusion system`, {
              bounds: {
                minX: worldBox.min.x.toFixed(2),
                maxX: worldBox.max.x.toFixed(2),
                minZ: worldBox.min.z.toFixed(2),
                maxZ: worldBox.max.z.toFixed(2)
              },
              padding: 0.5,
              worldPosition: chest.mesh.getWorldPosition(new THREE.Vector3())
            });
            
            // Trigger grass regeneration after chest registration
            // This ensures grass is regenerated with exclusion zones applied
            if (this.grassSystem && typeof this.grassSystem.regenerateGrass === 'function') {
              // Use a longer delay to ensure all chests have loaded and registered
              // This batches multiple chest registrations and ensures grass is only regenerated once
              clearTimeout(this._grassRegenerationTimer);
              this._grassRegenerationTimer = setTimeout(() => {
                console.log("🌱 [CHEST SYSTEM] Triggering grass regeneration after chest registration...");
                // 🚨 CRITICAL PERFORMANCE FIX: Defer grass regeneration to next frame to prevent blocking
                // Grass regeneration creates hundreds of thousands of blades - this prevents 2-second freeze
                requestAnimationFrame(() => {
                  this.grassSystem.regenerateGrass().then(() => {
                    console.log("✅ [CHEST SYSTEM] Grass regenerated successfully with exclusion zones");
                  }).catch(err => {
                    console.warn("⚠️ [CHEST SYSTEM] Grass regeneration failed:", err);
                  });
                });
              }, 1500); // Increased to 1500ms to ensure all chests have loaded
            }
          } else {
            console.warn(`⚠️ [CHEST SYSTEM] Failed to register chest ${config.id} with grass exclusion system`);
          }
        }, 200); // Small delay to ensure mesh is fully ready
      } catch (error) {
        console.error(`❌ [CHEST SYSTEM] Failed to load chest ${config.id}:`, error);
        console.error(`❌ [CHEST SYSTEM] Error details:`, {
          chestId: config.id,
          chestType: config.type,
          position: config.position,
          error: error.message,
          stack: error.stack
        });
        
        // CRITICAL: Remove failed chest from map to allow retry
        levelChests.delete(config.id);
      }
    }, 50); // Reduced from 100ms to 50ms for faster loading
  }
  
  /**
   * Set grass system reference (for exclusion zone registration)
   * 
   * GRASS EXCLUSION ZONE SYSTEM (Phase 2)
   * 
   * Allows setting or updating the grass system reference after construction.
   * Useful when grass system is initialized after chest system.
   * 
   * @param {GrassSystem} grassSystem - Reference to grass system
   */
  setGrassSystem(grassSystem) {
    this.grassSystem = grassSystem;
    
    // Auto-register all existing chests with the grass system
    if (grassSystem && typeof grassSystem.registerExclusionZone === 'function') {
      let registeredCount = 0;
      this.chests.forEach((levelChests) => {
        levelChests.forEach((chest) => {
          if (chest && chest.mesh && chest.isLoaded) {
            const worldBox = new THREE.Box3().setFromObject(chest.mesh);
            if (grassSystem.registerExclusionZone(chest.id, worldBox, 0.5)) {
              registeredCount++;
            }
          }
        });
      });
      if (registeredCount > 0) {
        console.log(`🌱 [CHEST SYSTEM] Auto-registered ${registeredCount} existing chest(s) with grass exclusion system`);
      }
    }
  }
  
  /**
   * Remove a chest from a level
   * @param {string} levelId - Level identifier
   * @param {string} chestId - Chest ID to remove
   */
  removeChest(levelId, chestId) {
    const levelChests = this.chests.get(levelId);
    if (!levelChests) {
      return;
    }
    
    const chest = levelChests.get(chestId);
    if (chest) {
      // GRASS EXCLUSION ZONE AUTO-UNREGISTRATION (Phase 2)
      // Unregister from grass system when chest is removed
      if (this.grassSystem && typeof this.grassSystem.unregisterExclusionZone === 'function') {
        this.grassSystem.unregisterExclusionZone(chestId);
      }
      
      chest.dispose();
      levelChests.delete(chestId);
      console.log(`🗑️ [CHEST SYSTEM] Removed chest ${chestId} from level ${levelId}`);
    }
  }
  
  /**
   * Get all chests for a level
   * @param {string} levelId - Level identifier
   * @returns {Map<string, Chest>} - Map of chests for the level
   */
  getChestsForLevel(levelId) {
    return this.chests.get(levelId) || new Map();
  }
  
  /**
   * Check collision between player capsule and all chests in a level
   * Returns collision data for the nearest chest if collision occurs
   * @param {string} levelId - Level identifier
   * @param {THREE.Vector3} playerStart - Player capsule start position
   * @param {THREE.Vector3} playerEnd - Player capsule end position
   * @param {number} playerRadius - Player capsule radius
   * @returns {Object|null} - Collision data or null if no collision
   */
  checkChestCollision(levelId, playerStart, playerEnd, playerRadius) {
    const chests = this.getChestsForLevel(levelId);
    if (chests.size === 0) {
      return null;
    }
    
    // Get player center position (middle of capsule)
    const playerPos = new THREE.Vector3().lerpVectors(playerStart, playerEnd, 0.5);
    
    let nearestCollision = null;
    let nearestOverlap = 0;
    
    // Check collision with all chests
    chests.forEach((chest, chestId) => {
      // Skip if chest doesn't exist or isn't loaded
      // NOTE (Jan 12, 2026): We keep chest collision enabled even if the chest is already opened,
      // so players can't walk through chest meshes in Level 6 (and future levels).
      if (!chest || !chest.mesh || !chest.isLoaded) {
        return;
      }
      
      // Get chest position (from mesh world position)
      const chestPos = new THREE.Vector3();
      chest.mesh.getWorldPosition(chestPos);
      
      // Calculate chest collision radius based on bounding box
      const box = new THREE.Box3().setFromObject(chest.mesh);
      const size = box.getSize(new THREE.Vector3());
      // Use the larger of X or Z dimension as collision radius (chests are roughly rectangular)
      const chestRadius = Math.max(size.x, size.z) * 0.5; // Half of the larger dimension
      
      // Calculate horizontal distance from player to chest center
      const dx = playerPos.x - chestPos.x;
      const dz = playerPos.z - chestPos.z;
      const horizontalDistance = Math.sqrt(dx * dx + dz * dz);
      
      // Collision occurs when player is within chest radius + player radius
      const collisionDistance = chestRadius + playerRadius;
      
      if (horizontalDistance < collisionDistance) {
        // Player is colliding with chest
        const overlap = collisionDistance - horizontalDistance;
        
        // Track nearest collision (largest overlap)
        if (overlap > nearestOverlap) {
          nearestCollision = {
            chest: chest,
            chestId: chestId,
            chestPos: chestPos.clone(),
            chestRadius: chestRadius,
            playerPos: playerPos.clone(),
            horizontalDistance: horizontalDistance,
            overlap: overlap,
            pushDirection: new THREE.Vector3(dx, 0, dz).normalize()
          };
          nearestOverlap = overlap;
        }
      }
    });
    
    return nearestCollision;
  }
  
  /**
   * Update all chests for a level (called every frame)
   * @param {string} levelId - Level identifier
   * @param {THREE.Vector3} playerPosition - Player position (center of capsule)
   * @param {number} delta - Time delta since last frame
   * @returns {Chest|null} - Nearest interactable chest, or null
   */
  update(levelId, playerPosition, delta) {
    const levelChests = this.chests.get(levelId);
    if (!levelChests) {
      return null;
    }
    
    let nearestChest = null;
    let nearestDistance = Infinity;
    
    // Update all chests and find nearest interactable chest
    levelChests.forEach((chest) => {
      // Update chest animation
      chest.update(delta);
      
      // Check player interaction
      const canInteract = chest.checkPlayerInteraction(playerPosition);
      
      if (canInteract) {
        // Calculate distance to find nearest
        const dx = playerPosition.x - chest.position.x;
        const dz = playerPosition.z - chest.position.z;
        const distance = Math.sqrt(dx * dx + dz * dz);
        
        if (distance < nearestDistance) {
          nearestDistance = distance;
          nearestChest = chest;
        }
      }
    });
    
    return nearestChest;
  }
  
  /**
   * Clear all chests for a specific level
   * CRITICAL: This ensures level isolation - each level ONLY loads its own chests
   * Enhanced with detailed logging to track clearing process (January 4, 2026)
   * 
   * Clearing Process:
   * 1. Gets level's chest Map from this.chests
   * 2. Disposes each chest (removes from scene, disposes geometry/materials)
   * 3. Deletes level's chest Map entry
   * 4. Logs detailed information (chest count, disposal status, errors)
   * 
   * Called by:
   * - cleanupAllLevels() - Clears all levels when warping
   * - createLevelXChests() functions - Clears level's chests before creating new ones
   * - warp functions - Explicit clearing for extra safety layer
   * 
   * Triple-Clearing Safety:
   * - cleanupAllLevels() clears all levels (first pass)
   * - Warp functions explicitly clear target level (second pass)
   * - createLevelXChests() clears level internally (third pass)
   * This ensures NO chests from other levels persist when loading a level
   * 
   * @param {string} levelId - Level identifier (e.g., LEVEL_IDS.LEVEL1)
   */
  clearLevel(levelId) {
    const levelChests = this.chests.get(levelId);
    if (!levelChests) {
      console.log(`🗑️ [CHEST SYSTEM] No chests to clear for level ${levelId} (level not in map)`);
      return;
    }
    
    const chestCount = levelChests.size;
    console.log(`🗑️ [CHEST SYSTEM] Clearing ${chestCount} chest(s) for level ${levelId}...`);
    
    // Dispose of all chests
    let disposedCount = 0;
    levelChests.forEach((chest, chestId) => {
      if (chest) {
        try {
          chest.dispose();
          disposedCount++;
          console.log(`🗑️ [CHEST SYSTEM] Disposed chest ${chestId} from level ${levelId}`);
        } catch (error) {
          console.error(`❌ [CHEST SYSTEM] Error disposing chest ${chestId} from level ${levelId}:`, error);
        }
      }
    });
    
    // Clear level chest map
    this.chests.delete(levelId);
    
    console.log(`✅ [CHEST SYSTEM] Cleared all chests for level ${levelId} (${disposedCount}/${chestCount} disposed)`);
  }
  
  /**
   * Reset all opened chests (admin/god mode only)
   * Uses the per-level reset so dual-model + legacy chests are handled correctly.
   */
  resetOpenedChests() {
    console.log(`🔄 [CHEST SYSTEM] Global chest reset starting...`);

    // Clear global cache flags
    if (this.openedChestsCache) {
      this.openedChestsCache.clear();
    }
    this.openedChestsLoaded = false;
    this.openedChestsLoadedForDiscordId = null;

    let totalReset = 0;

    // Call the per-level reset for every level we know about
    this.chests.forEach((_, levelId) => {
      if (typeof this.resetOpenedChestsForLevel === "function") {
        const count = this.resetOpenedChestsForLevel(levelId);
        totalReset += count;
      }
    });

    console.log(`✅ [CHEST SYSTEM] Global reset completed – ${totalReset} chest(s) reset across all levels`);
    return totalReset;
  }

  
/**
 * Reset opened chests for a specific level (admin/god mode only)
 * - HARD RESET: clear level chests and recreate them in closed state
 * - Does NOT touch other levels
 * - Intended for local/dev testing & god mode
 */
resetOpenedChestsForLevel(levelId) {
  if (!levelId) {
    console.warn("⚠️ [CHEST SYSTEM] resetOpenedChestsForLevel called without levelId");
    return 0;
  }

  const levelKey = String(levelId);
  const levelChests = this.chests.get(levelKey);

  if (!levelChests) {
    console.warn(`⚠️ [CHEST SYSTEM] No chests found for level ${levelKey} – nothing to reset`);
    return 0;
  }

  console.log(`🔄 [CHEST SYSTEM] HARD reset for level ${levelKey}`);

  // 1) Clear persistence / cache entries for this level's chests
  if (this.openedChestsCache) {
    levelChests.forEach((_, chestId) => {
      if (this.openedChestsCache.has(chestId)) {
        this.openedChestsCache.delete(chestId);
      }
    });
  }

  // Reset global flags so we don't re-apply opened state
  this.openedChestsLoaded = false;
  this.openedChestsLoadedForDiscordId = null;

  // 2) FULL NUKE: remove all chest meshes & objects for this level
  this.clearLevel(levelKey);

  // 3) Recreate chests for this level in CLOSED state via callback
  if (typeof this.onRecreateLevelChests === "function") {
    try {
      this.onRecreateLevelChests(levelKey);
      console.log(`✅ [CHEST SYSTEM] Level ${levelKey} chests fully rebuilt (hard reset)`);
      return 1;
    } catch (err) {
      console.error(`❌ [CHEST SYSTEM] Error recreating chests for level ${levelKey}:`, err);
      return 0;
    }
  } else {
    console.warn(
      "⚠️ [CHEST SYSTEM] onRecreateLevelChests callback not set – level chests were cleared but not recreated"
    );
    return 0;
  }
}


// ─────────────────────────────────────────────
// 1) Dual-model chests (new mouse chest)
// ─────────────────────────────────────────────
if (chest.closedModelPath && chest.openedModelPath) {
  console.log("🔧 [CHEST SYSTEM] Dual-model reset – before:", {
    id: chestId,
    hasClosedMesh: !!chest.closedMesh,
    hasOpenedMesh: !!chest.openedMesh,
    currentMeshIsClosed: chest.mesh === chest.closedMesh,
    openedParent: chest.openedMesh?.parent?.name || chest.openedMesh?.parent?.type
  });

  // 🔥 HARD REMOVE opened mesh from scene graph
  if (chest.openedMesh && chest.openedMesh.parent) {
    chest.openedMesh.parent.remove(chest.openedMesh);
  }

  // Also handle alternate property name
  if (chest.openMesh && chest.openMesh.parent) {
    chest.openMesh.parent.remove(chest.openMesh);
  }

  // Ensure closed mesh is attached to scene
  if (chest.closedMesh) {
    if (!chest.closedMesh.parent) {
      this.scene.add(chest.closedMesh);
    }
    chest.closedMesh.visible = true;
    chest.closedMesh.updateMatrixWorld(true);
    chest.mesh = chest.closedMesh;
  }

  // Safety: ensure NO opened visuals remain
  if (chest.openedMesh) chest.openedMesh.visible = false;
  if (chest.openMesh) chest.openMesh.visible = false;

  console.log(`✅ [CHEST SYSTEM] Dual-model chest ${chestId} FORCE reset to CLOSED mesh`);
  resetCount++;
  return;
}


    // ─────────────────────────────────────────────
    // 2) Legacy single-model chest2 logic
    // ─────────────────────────────────────────────
    if (chest.mesh && chest.isLoaded) {
      // Show closed meshes
      if (chest.closedMeshes && chest.closedMeshes.length > 0) {
        chest.closedMeshes.forEach((mesh) => {
          if (mesh) mesh.visible = true;
        });
      }

      // Hide opened meshes
      if (chest.openedMeshes && chest.openedMeshes.length > 0) {
        chest.openedMeshes.forEach((mesh) => {
          if (mesh) mesh.visible = false;
        });
      }

      // Show duplicate lids again
      if (chest.duplicateLidMeshes && chest.duplicateLidMeshes.length > 0) {
        chest.duplicateLidMeshes.forEach((mesh) => {
          if (mesh) mesh.visible = true;
        });
      }

      // Reset lid rotation to closed
      if (chest.lidMesh) {
        chest.lidMesh.rotation.x = 0;
        chest.lidMesh.visible = true;
      }

      // Stop opening animation if present
      if (chest.openingAnimation) {
        chest.openingAnimation.stopAllAction();
      }

      // Make sure body/handles are visible
      if (chest.chestBodyMesh) {
        chest.chestBodyMesh.visible = true;
      }

      if (chest.mesh) {
        chest.mesh.traverse((node) => {
          if (node.isMesh) {
            const nameLower = (node.name || "").toLowerCase();
            const isBody = nameLower.includes("body") && !nameLower.includes("lid");
            const isHandle = nameLower.includes("handle");
            if (isBody || isHandle) {
              node.visible = true;
            }
          }
        });
      }

      resetCount++;
      console.log(`✅ [CHEST SYSTEM] Chest ${chestId} in level ${levelKey} reset to closed state`);
    } else {
      // Mesh not yet loaded – just reset flags, visuals will spawn closed
      resetCount++;
      console.log(`✅ [CHEST SYSTEM] Chest ${chestId} (level ${levelKey}) flags reset (mesh not loaded yet)`);
    }
  });

  console.log(`✅ [CHEST SYSTEM] Reset ${resetCount} chest(s) in level ${levelKey} – ready for testing`);
  return resetCount;
}

  /**
   * Get chest by ID
   * @param {string} levelId - Level identifier
   * @param {string} chestId - Chest ID
   * @returns {Chest|null} - Chest object or null
   */
  getChest(levelId, chestId) {
    const levelChests = this.chests.get(levelId);
    if (!levelChests) {
      return null;
    }
    
    return levelChests.get(chestId) || null;
  }
}

