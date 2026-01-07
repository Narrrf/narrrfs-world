/**
 * ============================================================================
 * CONFIGURATION SYSTEM - Game Configuration Constants
 * ============================================================================
 * 
 * ✅ STATUS: STABLE - PRODUCTION READY
 * 📅 CREATED: December 18, 2025
 * 📅 LAST UPDATED: December 20, 2025
 * 
 * ============================================================================
 * 🎯 PURPOSE
 * ============================================================================
 * 
 * This module contains ALL game configuration constants extracted from main.js
 * to reduce main.js size and improve maintainability.
 * 
 * Centralized configuration makes it easy to:
 * - Update game settings in one place
 * - Share constants across modules
 * - Maintain consistency across the codebase
 * 
 * ============================================================================
 * ✅ STABLE VERSION STATUS
 * ============================================================================
 * 
 * ✅ **STABLE VERSION - PRODUCTION READY**
 * 
 * This system has reached a stable, production-ready state with:
 * - ✅ All constants properly exported
 * - ✅ Used by AudioSystem and other modules
 * - ✅ Environment detection working
 * - ✅ API configuration correct
 * - ✅ Level IDs and map configs defined
 * - ✅ Audio paths configured
 * - ✅ Storage keys defined
 * 
 * ============================================================================
 * 📦 WHAT THIS MODULE PROVIDES
 * ============================================================================
 * 
 * Environment Detection:
 * - isProduction - Production vs development
 * - isMobile - Mobile device detection
 * - isMobileLandscape - Mobile landscape mode
 * 
 * API Configuration:
 * - API_BASE_URL - Base URL for API calls
 * - PROFILE_URL - Profile page URL
 * - CHEESE_CAPTURE_ENDPOINT - Cheese hunt API endpoint
 * - RIDDLE_REWARD_ENDPOINT - Riddle reward API endpoint
 * 
 * Level Configuration:
 * - LEVEL_IDS - Level identifier constants
 * - LEVEL_MAP_CONFIG - GLTF map files per level
 * 
 * Audio Configuration:
 * - CHARACTER_FOOTSTEP_AUDIO - Footstep sound path
 * - CHARACTER_JUMP_AUDIO - Jump sound path
 * - BACKGROUND_MUSIC_PATHS - Music paths per level
 * - All other audio file paths
 * 
 * Storage Keys:
 * - SOUND_FX_STORAGE_KEY - Sound FX preference key
 * - BACKGROUND_MUSIC_STORAGE_KEY - Music preference key
 * - BACKGROUND_MUSIC_VOLUME_STORAGE_KEY - Volume preference key
 * 
 * Game Constants:
 * - LEVEL2_LEVER_DISTANCE - Lever interaction distance
 * - LEVEL4_SHOOT_RANGE - Weapon shooting range
 * - RIDDLE3_LEVER_CLICK_DISTANCE - Riddle lever distance
 * - RIDDLE_UI_UPDATE_INTERVAL - UI update frequency
 * 
 * Debug Flags:
 * - DEBUG_FORCE_LEVEL2_START - Force start at Level 2
 * - DEBUG_FORCE_LEVEL3_START - Force start at Level 3
 * - DEBUG_FORCE_LEVEL4_START - Force start at Level 4
 * - initializeDebugFlags() - Initialize debug flags
 * 
 * ============================================================================
 * 🔧 INTEGRATION
 * ============================================================================
 * 
 * Usage in other modules:
 * ```javascript
 * import {
 *   LEVEL_IDS,
 *   BACKGROUND_MUSIC_PATHS,
 *   API_BASE_URL
 * } from "./config-system.js";
 * ```
 * 
 * No initialization needed - pure constants module.
 * 
 * ============================================================================
 */

/**
 * Environment Detection
 */
export const isProduction = window.location.hostname === "narrrfs.world";
export const isMobile = /Mobi|Android/i.test(navigator.userAgent);
export const isMobileLandscape = isMobile && window.innerWidth > window.innerHeight;

/**
 * API Configuration
 */
export const API_BASE_URL = isProduction ? "https://narrrfs.world" : "http://localhost";
export const PROFILE_URL = isProduction
  ? "https://narrrfs.world/profile.html"
  : "http://localhost/public/profile.html";

/**
 * API Endpoints
 */
export const CHEESE_CAPTURE_ENDPOINT = `${API_BASE_URL}/api/dev/cheese-hunt-capture.php`;
export const RIDDLE_REWARD_ENDPOINT = `${API_BASE_URL}/api/dev/riddle-reward.php`;

/**
 * Level IDs
 */
export const LEVEL_IDS = {
  LEVEL1: "LEVEL1",
  LEVEL2: "LEVEL2",
  LEVEL3: "LEVEL3",
  LEVEL4: "LEVEL4",
  LEVEL5: "LEVEL5",
  LEVEL6: "LEVEL6"
};

/**
 * Level Map Configuration
 * Specify GLTF map file for each level (optional - if not specified, uses default ground plane)
 * Maps are loaded from: /textures/3d models/Maps/
 */
export const LEVEL_MAP_CONFIG = {
  [LEVEL_IDS.LEVEL1]: null, // No map - uses default ground
  [LEVEL_IDS.LEVEL2]: null, // No map - uses default ground
  [LEVEL_IDS.LEVEL3]: null, // No map - uses default ground
  [LEVEL_IDS.LEVEL4]: null, // No map - uses default ground
  [LEVEL_IDS.LEVEL5]: "klagenfurt.gltf", // Level 5 uses Klagenfurt map
  [LEVEL_IDS.LEVEL6]: null  // Level 6 uses normal ground system (switched back from GLTF)
};

/**
 * Audio File Paths
 * Note: These paths are relative (no leading /) so resolveAssetPath() can process them
 * They will be resolved to /public/three.js/public/audio/... or ./public/audio/... depending on environment
 */
export const CHARACTER_FOOTSTEP_AUDIO = "audio/character/footstep_cheese.ogg";
export const CHARACTER_JUMP_AUDIO = "audio/character/jump_cheese.ogg";
export const CHEESE_PLATFORM_AUDIO = "audio/gameplay/cheese_platform_active.ogg";
export const CHEESE_AIM_CLEAR_AUDIO = "audio/gameplay/cheese_aim_clear.wav";
export const LEVER_AUDIO = "audio/gameplay/slever.ogg";
export const BLOCK_MOVED_AUDIO = "audio/gameplay/block_moved_correct.ogg";
export const LEVEL_UP_AUDIO = "audio/gameplay/LEVEL%20UP!.wav";
export const LEVEL4_SHOOT_AUDIO = "sounds/invaders/weapons/normal_shoot.wav";
export const LEVEL4_SF13_SHOOT_AUDIO = "sounds/invaders/weapons/normal_shoot.wav"; // SF13 uses same sound (triple burst)

/**
 * Background Music Paths
 * Initialize after LEVEL_IDS is defined
 * Note: These paths are relative (no leading /) so resolveAssetPath() can process them
 */
export const BACKGROUND_MUSIC_PATHS = {
  [LEVEL_IDS.LEVEL1]: "sounds/music/level1.mp3",
  [LEVEL_IDS.LEVEL2]: "sounds/music/level2.mp3",
  [LEVEL_IDS.LEVEL3]: "sounds/music/level3.mp3",
  [LEVEL_IDS.LEVEL4]: "sounds/music/level4.mp3",
  [LEVEL_IDS.LEVEL5]: "sounds/music/level5.mp3", // Use level5.mp3 if available, otherwise will fallback
  [LEVEL_IDS.LEVEL6]: "sounds/music/level6.mp3" // Level 6 boss fight music
};

/**
 * LocalStorage Keys
 */
export const SOUND_FX_STORAGE_KEY = "cheese_temple_sound_fx_enabled";
export const BACKGROUND_MUSIC_STORAGE_KEY = "cheese_temple_background_music_enabled";
export const BACKGROUND_MUSIC_VOLUME_STORAGE_KEY = "cheese_temple_background_music_volume";

/**
 * Game Constants
 */
export const LEVEL2_LEVER_DISTANCE = 2.6;
export const LEVEL4_SHOOT_RANGE = 200; // Maximum shooting range
export const RIDDLE3_LEVER_CLICK_DISTANCE = 2.0; // Distance threshold for clicking lever (2.0 units)
export const RIDDLE4_LEVER_CLICK_DISTANCE = 2.0; // Distance threshold for clicking hidden riddle levers (2.0 units)
export const RIDDLE_UI_UPDATE_INTERVAL = 100; // Update UI max once per 100ms (10 times per second)

/**
 * Debug Flags (can be overridden via localStorage)
 */
export let DEBUG_FORCE_LEVEL2_START = false; // Devs auto-warp into Level 2 start (disabled - start at Level 1)
export let DEBUG_LEVEL2_GALLERY_START = false;
export let DEBUG_FORCE_LEVEL3_START = false; // Devs auto-warp into Level 3 start (disabled)
export let DEBUG_FORCE_LEVEL4_START = false; // Devs auto-warp into Level 4 start (disabled - start at Level 1)

/**
 * Initialize debug flags from localStorage
 */
export function initializeDebugFlags() {
  try {
    // CLEAR all debug force level flags from localStorage to ensure game starts at Level 1
    localStorage.removeItem("debug_force_level2_start");
    localStorage.removeItem("debug_force_level3_start");
    localStorage.removeItem("debug_force_level4_start");
    localStorage.removeItem("debug_level2_gallery_start");
    
    // Force all debug flags to false (game always starts at Level 1)
    DEBUG_FORCE_LEVEL2_START = false;
    DEBUG_FORCE_LEVEL3_START = false;
    DEBUG_FORCE_LEVEL4_START = false;
    DEBUG_LEVEL2_GALLERY_START = false;
    
    console.log("✅ [DEBUG] All debug force level flags cleared - game will start at Level 1");
  } catch (error) {
    console.warn("⚠️ [DEBUG] Failed to clear debug flags:", error);
    // Force all flags to false even if localStorage fails
    DEBUG_FORCE_LEVEL2_START = false;
    DEBUG_FORCE_LEVEL3_START = false;
    DEBUG_FORCE_LEVEL4_START = false;
    DEBUG_LEVEL2_GALLERY_START = false;
  }
}

/**
 * Local Test User Configuration
 */
export const LOCAL_TEST_DISCORD_ID = "328601656659017732"; // Narrrf's Discord ID
export const LOCAL_TEST_DISPLAY_NAME = "Narrrf";
export const OLD_LOCAL_TEST_DISCORD_STRING = "LOCAL_TEST_DISCORD"; // Legacy test user string

/**
 * Display Configuration
 */
export const SHOW_LEVEL2_ANCHOR_LABELS = true;
