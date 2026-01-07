/**
 * ============================================================================
 * GUI SYSTEM - User Interface Management
 * ============================================================================
 * 
 * ✅ STATUS: STABLE - PRODUCTION READY
 * 📅 CREATED: December 2025
 * 📅 LAST UPDATED: January 4, 2026
 * 
 * ============================================================================
 * 🎯 PURPOSE
 * ============================================================================
 * 
 * Manages ALL DOM-based UI elements for the game:
 * - Main menu (first screen - Welcome screen with New Game/Options/Exit)
 * - Score HUD display
 * - Debug overlay
 * - Toast notifications
 * - Pause menu
 * - Options menu
 * - Game over screens
 * - Completion screens (Level 1-4)
 * - Character selection menu
 * - Level selector (God Mode)
 * - Level-specific HUD elements (Level 2, 4)
 * - Crosshair
 * - Boss health bars (Phoenix, Alien Spider)
 * - Sound/Music control buttons
 * - Loading screens
 * 
 * Built for decades of development with extensible architecture.
 * Device-agnostic (VR, Android, PC, etc.) - works on all devices.
 * 
 * ============================================================================
 * ✅ STABLE VERSION STATUS
 * ============================================================================
 * 
 * ✅ **STABLE VERSION - PRODUCTION READY**
 * 
 * This system has reached a stable, production-ready state with:
 * - ✅ All UI elements working
 * - ✅ Main menu (welcome screen) implemented (January 3, 2026)
 * - ✅ Loading screen GUI fix - HUD elements hidden during initial loading (January 4, 2026)
 * - ✅ Phoenix HUD fix - HUD only displays in Level 6, disappears when defeated/switching levels (January 4, 2026)
 * - ✅ Phoenix HUD fine-tuning - Critical health visual effects, pulsing animation, enhanced dead state (January 4, 2026)
 * - ✅ Debug Helpers System - Complete DEV menu with 3 debug visualization tools (Shadow Camera, World Axes, Player Axes) + level initialization system for seamless level transitions (January 4, 2026)
 * - ✅ Level 4 Progress HUD position fix - Moved from center to top-left to avoid blocking view (January 4, 2026)
 * - ✅ Cheese Temple background image - Added cheesetemple1.png to loading screen, main menu, character selection, and level selector (January 4, 2026)
 * - ✅ Menus responsive and functional
 * - ✅ Notifications displaying correctly
 * - ✅ Boss health bars working
 * - ✅ Loading screens functional (clean display, no HUD elements visible)
 * - ✅ God Mode controls working
 * - ✅ Settings persistence working
 * 
 * ============================================================================
 * 📦 WHAT THIS MODULE LOADS
 * ============================================================================
 * 
 * Resources:
 * - No external files (pure DOM manipulation)
 * - Creates all UI elements dynamically
 * 
 * Dependencies:
 * - THREE.js Scene (for 3D UI elements)
 * - THREE.js Camera (for 3D UI positioning)
 * - Callbacks from main.js (getPlayerName, getDSPOINC, etc.)
 * 
 * ============================================================================
 * 🔧 INTEGRATION IN MAIN.JS
 * ============================================================================
 * 
 * 1. Import:
 *    import { GUISystem } from "./gui-system.js";
 * 
 * 2. Initialize:
 *    guiSystem = new GUISystem({
 *      scene: scene,
 *      camera: camera,
 *      getPlayerName: () => playerDisplayName,
 *      getDSPOINC: () => dspoinC,
 *      getCurrentLevel: () => currentLevel,
 *      onStartGame: () => { startGame(); },
 *      onResume: () => { // resume logic },
 *      // ... other callbacks
 *    });
 *    guiSystem.initialize();
 * 
 * 3. Update in game loop (if needed):
 *    if (guiSystem) {
 *      guiSystem.update(delta);
 *    }
 * 
 * 4. Loading Screen HUD Management (January 4, 2026):
 *    - HUD elements (FPS counter, cheese HUD, debug overlay) are hidden initially
 *    - Hidden in main.js: stats.dom, cheeseHud, debugOverlay set to display: none
 *    - Shown when game starts: All HUD elements displayed in startGame() function
 *    - Ensures clean loading screen before main menu appears
 * 
 * 5. Cheese Temple Background Image System (January 4, 2026):
 *    - Immersive background image (cheesetemple1.png) displays on all initial game screens
 *    - Applied to: Loading Screen, Main Menu, Character Selection, Level Selector (GOD MODE)
 *    - Image path: /textures/backgrounds/cheesetemple1.png
 *    - Styling: background-size: cover, background-position: center, background-repeat: no-repeat
 *    - Semi-transparent overlay maintains text readability (rgba(5, 7, 16, 0.85) or rgba(0, 0, 0, 0.7))
 *    - Backdrop blur applied on menu screens for enhanced visual depth
 *    - Creates consistent, immersive Cheese Temple theme throughout initial game flow
 *    - Perfectly matches the 6 Cheese Temple levels visual aesthetic
 * 
 * 6. Debug Helpers System (January 4, 2026):
 *    - Complete debug visualization system with 3 helpers: Shadow Camera, World Axes, Player Axes
 *    - Menu location: Bottom-right corner (fixed position, z-index: 10001)
 *    - Shadow Camera Helper: Shows sun's shadow camera frustum, follows sun/moon movement dynamically
 *    - World Axes Helper: Shows coordinate axes at level center (50 units, different position per level)
 *    - Player Axes Helper: Shows coordinate axes at player position (5 units, snapshot position)
 *    - Level Initialization System: initializeShadowCameraHelperAtLevelStart() called in all 6 level warp functions
 *    - Shadow Camera Helper: Recreates entirely when warping to levels (dispose + create new) for correct initialization
 *    - Update Logic: Shadow camera helper updates every frame in animate loop to follow sun movement
 *    - Integration: Works seamlessly across all 6 levels without requiring manual toggle off/on
 *    - Implementation: All helper functions in main.js (createDebugHelpersMenu, toggle functions, initialization)
 * 
 * ============================================================================
 * 🎮 KEY FUNCTIONS
 * ============================================================================
 * 
 * HUD:
 * - updateHUD() - Update HUD elements
 * - showToast(message, duration) - Show notification
 * 
 * Menus:
 * - showMainMenu() - Show main menu (first screen - January 3, 2026)
 * - hideMainMenu() - Hide main menu
 * - showPauseMenu() - Show pause menu
 * - hidePauseMenu() - Hide pause menu
 * - showOptionsMenu() - Show options menu
 * - hideOptionsMenu() - Hide options menu
 * 
 * Boss Health:
 * - showBossHealthBar() - Show boss health bar
 * - hideBossHealthBar() - Hide boss health bar
 * - updateBossHealthBar(health, maxHealth, phase) - Update health display
 * 
 * Loading:
 * - showLoadingScreen(progress) - Show loading screen
 * - hideLoadingScreen() - Hide loading screen
 * 
 * ============================================================================
 * 
 * @module GUISystem
 */

import * as THREE from "three";

/**
 * GUI System
 * Manages all DOM-based UI elements for the game
 */
export class GUISystem {
  constructor(config = {}) {
    // Configuration with dependency injection callbacks
    this.config = {
      // Game state callbacks
      getPlayerName: config.getPlayerName || (() => 'Player'),
      getDSPOINC: config.getDSPOINC || (() => 0),
      getCameraMode: config.getCameraMode || (() => 0),
      getDebugState: config.getDebugState || (() => ({ pending: 0, lastResponse: null })),
      getIsGamePaused: config.getIsGamePaused || (() => false),
      getCurrentLevel: config.getCurrentLevel || (() => 1),
      getGodMode: config.getGodMode || (() => false),
      getSoundFxEnabled: config.getSoundFxEnabled || (() => true),
      getBackgroundMusicEnabled: config.getBackgroundMusicEnabled || (() => true),
      getBackgroundMusicVolume: config.getBackgroundMusicVolume || (() => 0.5),
      getCheeseSessionId: config.getCheeseSessionId || (() => 'session'),
      getAPIBaseURL: config.getAPIBaseURL || (() => ''),
      getIsProduction: config.getIsProduction || (() => false),
      getPlayerPosition: config.getPlayerPosition || (() => ({ x: 0, y: 0, z: 0 })),
      
      // Level state callbacks
      getLevel1State: config.getLevel1State || (() => ({})),
      getLevel2State: config.getLevel2State || (() => ({})),
      getLevel2RiddleState: config.getLevel2RiddleState || (() => ({})),
      getLevel3State: config.getLevel3State || (() => ({})),
      getLevel4State: config.getLevel4State || (() => ({})),
      getLevel4RiddleState: config.getLevel4RiddleState || (() => ({})),
      getLevel5State: config.getLevel5State || (() => ({})),
      
      // Action callbacks
      onResume: config.onResume || (() => {}),
      onRestart: config.onRestart || (() => {}),
      onBackToPortal: config.onBackToPortal || (() => {}),
      onShowOptions: config.onShowOptions || (() => {}),
      onTogglePause: config.onTogglePause || ((paused) => {}),
      onToggleSoundFx: config.onToggleSoundFx || ((enabled) => {}),
      onToggleMusic: config.onToggleMusic || ((enabled) => {}),
      onVolumeChange: config.onVolumeChange || ((volume) => {}),
      onRestartLevel1: config.onRestartLevel1 || (() => {}),
      onRestartLevel2: config.onRestartLevel2 || (() => {}),
      onRestartLevel3: config.onRestartLevel3 || (() => {}),
      onWarpToLevel1: config.onWarpToLevel1 || (() => {}),
      onWarpToLevel2: config.onWarpToLevel2 || (() => {}),
      onWarpToLevel3: config.onWarpToLevel3 || (() => {}),
      onWarpToLevel4: config.onWarpToLevel4 || (() => {}),
      onWarpToLevel5: config.onWarpToLevel5 || (() => {}),
      onWarpToLevel6: config.onWarpToLevel6 || (() => {}),
      onShowLevelSelector: config.onShowLevelSelector || (() => {}),
      onSelectCharacter: config.onSelectCharacter || ((path) => {}),
      onStartGame: config.onStartGame || (() => {}),
      onPlayLevelUpSound: config.onPlayLevelUpSound || (() => {}),
      
      // Level IDs constants (passed from main.js)
      LEVEL_IDS: config.LEVEL_IDS || { LEVEL1: 1, LEVEL2: 2, LEVEL3: 3, LEVEL4: 4, LEVEL5: 5 },
      
      // Level constants (passed from main.js)
      LEVEL4_CHEESES_TO_CATCH: config.LEVEL4_CHEESES_TO_CATCH || 15,
      LEVEL4_TOTAL_MONSTERS: config.LEVEL4_TOTAL_MONSTERS || 30,
      LEVEL4_WAVES_COUNT: config.LEVEL4_WAVES_COUNT || 10,
      LEVEL3_MONSTERS_PER_STEP: config.LEVEL3_MONSTERS_PER_STEP || 5,
      
      // Profile URL
      PROFILE_URL: config.PROFILE_URL || '/profile.html',
      
      ...config
    };
    
    // DOM container
    this.container = document.body;
    
    // Core UI elements
    this.scoreHud = null;
    this.debugOverlay = null;
    this.crosshair = null;
    this.interactionPrompt = null;
    
    // Menu elements
    this.mainMenu = null;
    this.pauseMenu = null;
    this.optionsMenu = null;
    this.characterSelectionMenu = null;
    this.levelSelectorScreen = null;
    
    // Screen overlays
    this.level3GameOverScreen = null;
    this.level1CompletionScreen = null;
    this.level2CompletionScreen = null;
    this.level3CompletionScreen = null;
    this.level4CompletionScreen = null;
    
    // Level-specific HUD elements
    this.level2InspectionHud = null;
    this.level4ProgressHUD = null;
    this.level4HitIndicator = null;
    this.level4HitIndicatorTimer = 0;
    
    // Phoenix Boss behavior display (dev testing)
    this.phoenixBehaviorDisplay = null;
    
    // Toast system
    this.activeRiddleToasts = new Map();
    
    // Mad Mode notification
    this.madModeNotification = null;
    
    // State
    this.isInitialized = false;
  }
  
  /**
   * Initialize the GUI system
   * Creates all core UI elements
   */
  initialize() {
    if (this.isInitialized) {
      console.warn('GUISystem: Already initialized');
      return;
    }
    
    console.log('🎨 [GUI] Initializing GUI System...');
    
    // Create core elements
    this.createScoreHud();
    this.createDebugOverlay();
    this.createCrosshair();
    this.createInteractionPrompt();
    
    this.isInitialized = true;
    console.log('✅ [GUI] GUI System initialized');
  }
  
  /**
   * Update GUI elements each frame
   * @param {number} delta - Time delta in seconds
   */
  update(delta) {
    if (!this.isInitialized) return;
    
    // Update debug overlay
    this.refreshDebugOverlay();
    
    // Get current level and camera mode
    const currentLevel = this.config.getCurrentLevel ? this.config.getCurrentLevel() : null;
    const LEVEL_IDS = this.config.LEVEL_IDS || {};
    const cameraMode = this.config.getCameraMode ? this.config.getCameraMode() : 0;
    const isFirstPerson = cameraMode === 0;
    const isThirdPerson = cameraMode === 1;
    
    // Crosshair: Show in first-person mode or in Level 4 (shooting game), hide in third-person
    if (this.crosshair) {
      const shouldShowCrosshair = isFirstPerson || (currentLevel === LEVEL_IDS.LEVEL4);
      this.setCrosshairVisible(shouldShowCrosshair && !isThirdPerson);
    }
    
    // Update level-specific HUD elements only in their respective levels
    const isLevel4 = currentLevel === LEVEL_IDS.LEVEL4;
    const isLevel2 = currentLevel === LEVEL_IDS.LEVEL2;
    
    // Level 4 Hit Indicator: Update only in Level 4
    if (isLevel4 && this.level4HitIndicatorTimer > 0) {
      this.updateLevel4HitIndicator(delta);
    }
    
    // Level 4 Progress HUD: Update only in Level 4
    if (isLevel4) {
      this.updateLevel4ProgressHUD();
    } else {
      // Hide Level 4 HUD when not in Level 4
      if (this.level4ProgressHUD) {
        this.level4ProgressHUD.style.display = "none";
      }
    }
    
    // Level 2 Inspection HUD: Update only in Level 2
    if (isLevel2) {
      this.updateLevel2InspectionHud();
    } else {
      // Hide Level 2 HUD when not in Level 2
      this.hideLevel2InspectionHud();
    }
  }
  
  // ========================================
  // SCORE HUD
  // ========================================
  
  /**
   * Create score HUD element
   */
  createScoreHud() {
    if (this.scoreHud) return this.scoreHud;
    
    this.scoreHud = document.getElementById("cheeseHud");
    if (this.scoreHud) return this.scoreHud;
    
    this.scoreHud = document.createElement("div");
    this.scoreHud.id = "cheeseHud";
    Object.assign(this.scoreHud.style, {
      position: "fixed",
      top: "20px",
      right: "24px",
      padding: "10px 14px",
      background: "rgba(14, 12, 20, 0.75)",
      color: "#ffe066",
      fontFamily: "Montserrat, Arial, sans-serif",
      fontSize: "16px",
      fontWeight: "600",
      border: "1px solid rgba(255, 224, 102, 0.35)",
      borderRadius: "8px",
      boxShadow: "0 0 12px rgba(255, 224, 102, 0.25)",
      pointerEvents: "none"
    });
    this.scoreHud.innerText = "Cheese Collected: 0";
    this.container.appendChild(this.scoreHud);
    
    return this.scoreHud;
  }
  
  /**
   * Update score display
   * @param {number} score - Current score
   */
  updateScore(score) {
    if (!this.scoreHud) this.createScoreHud();
    if (this.scoreHud) {
      this.scoreHud.innerText = `Cheese Collected: ${score}`;
    }
  }
  
  // ========================================
  // BOSS HEALTH BAR
  // ========================================
  
  /**
   * Create boss health bar element
   */
  createBossHealthBar() {
    if (this.bossHealthBar) return this.bossHealthBar;
    
    // Add CSS animation for critical health pulsing (January 4, 2026)
    if (!document.getElementById('phoenixCriticalPulseStyle')) {
      const styleElement = document.createElement("style");
      styleElement.id = "phoenixCriticalPulseStyle";
      styleElement.textContent = `
        @keyframes phoenixCriticalPulse {
          0%, 100% { 
            opacity: 1;
            box-shadow: inset 0 0 10px rgba(255, 255, 255, 0.4), 0 0 20px rgba(255, 68, 68, 0.8);
          }
          50% { 
            opacity: 0.85;
            box-shadow: inset 0 0 15px rgba(255, 255, 255, 0.6), 0 0 30px rgba(255, 0, 0, 1);
          }
        }
      `;
      document.head.appendChild(styleElement);
    }
    
    const container = document.createElement("div");
    container.id = "bossHealthBar";
    Object.assign(container.style, {
      position: "fixed",
      top: "20px", // Moved higher to avoid any conflicts
      left: "50%",
      transform: "translateX(-50%)",
      width: "600px",
      padding: "0",
      display: "none", // Hidden by default
      zIndex: "50", // Lower z-index - well below pause menu (1001) and crosshair (1000)
      pointerEvents: "none", // CRITICAL: Never block mouse clicks
      userSelect: "none" // CRITICAL: Never interfere with selection
    });
    
    // Boss name
    const bossName = document.createElement("div");
    bossName.id = "bossName";
    Object.assign(bossName.style, {
      fontFamily: "Montserrat, Arial, sans-serif",
      fontSize: "24px",
      fontWeight: "700",
      color: "#ff4444",
      textAlign: "center",
      marginBottom: "10px",
      textShadow: "0 0 10px rgba(255, 68, 68, 0.8), 0 0 20px rgba(255, 68, 68, 0.5)",
      pointerEvents: "none", // CRITICAL: Never block clicks
      userSelect: "none"
    });
    bossName.innerText = "🔥 PHOENIX DRAGON";
    container.appendChild(bossName);
    
    // Phase indicator
    const phaseIndicator = document.createElement("div");
    phaseIndicator.id = "bossPhase";
    Object.assign(phaseIndicator.style, {
      fontFamily: "Montserrat, Arial, sans-serif",
      fontSize: "14px",
      fontWeight: "600",
      color: "#ffaa00",
      textAlign: "center",
      marginBottom: "8px",
      textShadow: "0 0 5px rgba(255, 170, 0, 0.8)",
      pointerEvents: "none", // CRITICAL: Never block clicks
      userSelect: "none"
    });
    phaseIndicator.innerText = "Phase 1";
    container.appendChild(phaseIndicator);
    
    // Health bar container
    const healthBarContainer = document.createElement("div");
    Object.assign(healthBarContainer.style, {
      width: "100%",
      height: "30px",
      background: "rgba(14, 12, 20, 0.85)",
      border: "2px solid rgba(255, 68, 68, 0.5)",
      borderRadius: "15px",
      overflow: "hidden",
      boxShadow: "0 0 15px rgba(255, 68, 68, 0.5), inset 0 0 10px rgba(0, 0, 0, 0.8)",
      pointerEvents: "none", // CRITICAL: Never block clicks
      userSelect: "none"
    });
    
    // Health bar fill
    const healthBarFill = document.createElement("div");
    healthBarFill.id = "bossHealthFill";
    Object.assign(healthBarFill.style, {
      width: "100%",
      height: "100%",
      background: "linear-gradient(90deg, #ff4444 0%, #ff8844 50%, #ffaa44 100%)",
      transition: "width 0.3s ease",
      boxShadow: "inset 0 0 10px rgba(255, 255, 255, 0.3)",
      pointerEvents: "none", // CRITICAL: Never block clicks
      userSelect: "none"
    });
    healthBarContainer.appendChild(healthBarFill);
    
    // Health text
    const healthText = document.createElement("div");
    healthText.id = "bossHealthText";
    Object.assign(healthText.style, {
      position: "absolute",
      top: "50%",
      left: "50%",
      transform: "translate(-50%, -50%)",
      fontFamily: "Montserrat, Arial, sans-serif",
      fontSize: "14px",
      fontWeight: "700",
      color: "#ffffff",
      textShadow: "0 0 5px rgba(0, 0, 0, 1), 1px 1px 2px rgba(0, 0, 0, 0.8)",
      pointerEvents: "none",
      zIndex: "1",
      userSelect: "none"
    });
    healthText.innerText = "1000 / 1000";
    
    const healthBarWrapper = document.createElement("div");
    Object.assign(healthBarWrapper.style, {
      position: "relative",
      width: "100%",
      pointerEvents: "none", // CRITICAL: Never block clicks
      userSelect: "none"
    });
    healthBarWrapper.appendChild(healthBarContainer);
    healthBarWrapper.appendChild(healthText);
    
    container.appendChild(healthBarWrapper);
    this.container.appendChild(container);
    
    this.bossHealthBar = container;
    this.bossHealthFill = healthBarFill;
    this.bossHealthText = healthText;
    this.bossPhaseIndicator = phaseIndicator;
    this.bossHealthBarContainer = healthBarContainer; // Store reference for critical health effects (January 4, 2026)
    
    return container;
  }
  
  /**
   * Show boss health bar
   */
  showBossHealthBar() {
    if (!this.bossHealthBar) this.createBossHealthBar();
    this.bossHealthBar.style.display = "block";
  }
  
  /**
   * Hide boss health bar
   */
  hideBossHealthBar() {
    if (this.bossHealthBar) {
      this.bossHealthBar.style.display = "none";
    }
  }
  
  /**
   * Update boss health bar
   * @param {number} currentHealth - Current health
   * @param {number} maxHealth - Maximum health
   * @param {number} phase - Current phase (1-4)
   */
  updateBossHealthBar(currentHealth, maxHealth, phase = 1) {
    if (!this.bossHealthBar) this.createBossHealthBar();
    
    // Ensure health never goes negative
    const safeHealth = Math.max(0, currentHealth);
    const healthPercent = Math.max(0, Math.min(100, (safeHealth / maxHealth) * 100));
    
    if (this.bossHealthFill) {
      this.bossHealthFill.style.width = healthPercent + "%";
      
      // Change color based on health with enhanced critical state
      if (healthPercent > 75) {
        // High health: Bright red-orange gradient
        this.bossHealthFill.style.background = "linear-gradient(90deg, #ff4444 0%, #ff8844 50%, #ffaa44 100%)";
        this.bossHealthFill.style.boxShadow = "inset 0 0 10px rgba(255, 255, 255, 0.3)";
        this.bossHealthFill.style.animation = "none"; // Remove pulsing
      } else if (healthPercent > 50) {
        // Medium-high health: Orange-yellow gradient
        this.bossHealthFill.style.background = "linear-gradient(90deg, #ff6644 0%, #ffaa44 50%, #ffcc44 100%)";
        this.bossHealthFill.style.boxShadow = "inset 0 0 10px rgba(255, 255, 255, 0.3)";
        this.bossHealthFill.style.animation = "none"; // Remove pulsing
      } else if (healthPercent > 25) {
        // Medium-low health: Yellow-orange gradient
        this.bossHealthFill.style.background = "linear-gradient(90deg, #ff8844 0%, #ffcc44 50%, #ffee44 100%)";
        this.bossHealthFill.style.boxShadow = "inset 0 0 10px rgba(255, 255, 255, 0.3)";
        this.bossHealthFill.style.animation = "none"; // Remove pulsing
      } else if (healthPercent > 10) {
        // Low health: Dark red gradient with subtle pulse
        this.bossHealthFill.style.background = "linear-gradient(90deg, #cc2222 0%, #ff4444 50%, #ff6644 100%)";
        this.bossHealthFill.style.boxShadow = "inset 0 0 10px rgba(255, 255, 255, 0.3), 0 0 15px rgba(255, 68, 68, 0.6)";
        this.bossHealthFill.style.animation = "none"; // Remove pulsing for now
      } else if (healthPercent > 0) {
        // Critical health: Dark red with strong pulsing glow
        this.bossHealthFill.style.background = "linear-gradient(90deg, #990000 0%, #cc2222 50%, #ff4444 100%)";
        this.bossHealthFill.style.boxShadow = "inset 0 0 10px rgba(255, 255, 255, 0.4), 0 0 20px rgba(255, 68, 68, 0.8)";
        // Add pulsing animation for critical health
        if (!this.bossHealthFill.style.animation || this.bossHealthFill.style.animation === "none") {
          this.bossHealthFill.style.animation = "phoenixCriticalPulse 1s ease-in-out infinite";
        }
      } else {
        // Dead/Zero health: Very dark red, no animation
        this.bossHealthFill.style.background = "linear-gradient(90deg, #440000 0%, #660000 50%, #880000 100%)";
        this.bossHealthFill.style.boxShadow = "inset 0 0 10px rgba(0, 0, 0, 0.8)";
        this.bossHealthFill.style.animation = "none";
      }
    }
    
    if (this.bossHealthText) {
      // Ensure text never shows negative values
      const displayHealth = Math.max(0, Math.ceil(safeHealth));
      this.bossHealthText.innerText = `${displayHealth} / ${maxHealth}`;
      
      // Add critical health text styling
      if (healthPercent <= 10 && healthPercent > 0) {
        this.bossHealthText.style.color = "#ff0000";
        this.bossHealthText.style.textShadow = "0 0 10px rgba(255, 0, 0, 1), 0 0 20px rgba(255, 0, 0, 0.8), 1px 1px 2px rgba(0, 0, 0, 0.8)";
      } else {
        this.bossHealthText.style.color = "#ffffff";
        this.bossHealthText.style.textShadow = "0 0 5px rgba(0, 0, 0, 1), 1px 1px 2px rgba(0, 0, 0, 0.8)";
      }
    }
    
    if (this.bossPhaseIndicator) {
      this.bossPhaseIndicator.innerText = `Phase ${phase}`;
      
      // Add critical indicator when health is very low
      if (healthPercent <= 10 && healthPercent > 0) {
        this.bossPhaseIndicator.innerText = `Phase ${phase} - ⚠️ CRITICAL`;
        this.bossPhaseIndicator.style.color = "#ff0000";
        this.bossPhaseIndicator.style.textShadow = "0 0 10px rgba(255, 0, 0, 1), 0 0 20px rgba(255, 0, 0, 0.8)";
      } else {
        this.bossPhaseIndicator.style.color = "#ffaa00";
        this.bossPhaseIndicator.style.textShadow = "0 0 5px rgba(255, 170, 0, 0.8)";
      }
    }
    
    // Add critical health border glow effect (January 4, 2026)
    if (this.bossHealthBarContainer) {
      if (healthPercent <= 10 && healthPercent > 0) {
        // Critical health: Red border with pulsing glow
        this.bossHealthBarContainer.style.border = "2px solid rgba(255, 0, 0, 0.8)";
        this.bossHealthBarContainer.style.boxShadow = "0 0 15px rgba(255, 68, 68, 0.5), inset 0 0 10px rgba(0, 0, 0, 0.8), 0 0 30px rgba(255, 0, 0, 0.6)";
      } else {
        // Normal health: Standard border
        this.bossHealthBarContainer.style.border = "2px solid rgba(255, 68, 68, 0.5)";
        this.bossHealthBarContainer.style.boxShadow = "0 0 15px rgba(255, 68, 68, 0.5), inset 0 0 10px rgba(0, 0, 0, 0.8)";
      }
    }
  }
  
  // ========================================
  // PHOENIX BOSS BEHAVIOR DISPLAY (DEV TESTING)
  // ========================================
  
  /**
   * Create Phoenix behavior display element (for dev testing)
   */
  createPhoenixBehaviorDisplay() {
    if (this.phoenixBehaviorDisplay) return this.phoenixBehaviorDisplay;
    
    const container = document.createElement("div");
    container.id = "phoenixBehaviorDisplay";
    Object.assign(container.style, {
      position: "fixed",
      top: "20px", // Top of screen
      left: "20px", // Top-left corner (away from cheese collected HUD on right)
      padding: "12px 20px",
      backgroundColor: "rgba(0, 0, 0, 0.7)",
      border: "2px solid #ffaa44",
      borderRadius: "8px",
      display: "none", // Hidden by default
      zIndex: "51", // Just above boss health bar (50)
      pointerEvents: "none", // Never block mouse clicks
      userSelect: "none",
      fontFamily: "Montserrat, Arial, sans-serif",
      fontSize: "16px",
      fontWeight: "700",
      color: "#ffaa44",
      textShadow: "0 0 5px rgba(255, 170, 68, 0.8), 1px 1px 2px rgba(0, 0, 0, 0.8)",
      boxShadow: "0 4px 8px rgba(0, 0, 0, 0.5), inset 0 0 10px rgba(255, 170, 68, 0.2)"
    });
    
    // Label
    const label = document.createElement("div");
    label.id = "phoenixBehaviorLabel";
    Object.assign(label.style, {
      fontSize: "12px",
      color: "#ffaa44",
      opacity: "0.8",
      marginBottom: "4px",
      pointerEvents: "none",
      userSelect: "none"
    });
    label.innerText = "🐉 Behavior:";
    
    // Behavior name
    const behaviorName = document.createElement("div");
    behaviorName.id = "phoenixBehaviorName";
    Object.assign(behaviorName.style, {
      fontSize: "18px",
      color: "#ffaa44",
      fontWeight: "700",
      pointerEvents: "none",
      userSelect: "none"
    });
    behaviorName.innerText = "Flying Circle";
    
    container.appendChild(label);
    container.appendChild(behaviorName);
    this.container.appendChild(container);
    
    this.phoenixBehaviorDisplay = container;
    this.phoenixBehaviorName = behaviorName;
    
    return container;
  }
  
  /**
   * Show Phoenix behavior display
   */
  showPhoenixBehaviorDisplay() {
    if (!this.phoenixBehaviorDisplay) this.createPhoenixBehaviorDisplay();
    this.phoenixBehaviorDisplay.style.display = "block";
  }
  
  /**
   * Hide Phoenix behavior display
   */
  hidePhoenixBehaviorDisplay() {
    if (this.phoenixBehaviorDisplay) {
      this.phoenixBehaviorDisplay.style.display = "none";
    }
  }
  
  /**
   * Update Phoenix behavior display
   * @param {string} behaviorName - Current behavior name (e.g., "🔄 Flying Circle")
   * @param {number} behaviorIndex - Current behavior index (1-15)
   * 📝 UPDATED: December 18, 2025 - Changed from 9 to 15 total patterns
   */
  updatePhoenixBehaviorDisplay(behaviorName, behaviorIndex = null) {
    if (!this.phoenixBehaviorDisplay) this.createPhoenixBehaviorDisplay();
    
    if (this.phoenixBehaviorName) {
      const displayText = behaviorIndex !== null 
        ? `${behaviorName} (${behaviorIndex}/15)` // Updated from /14 to /15
        : behaviorName;
      this.phoenixBehaviorName.innerText = displayText;
    }
  }
  
  // ========================================
  // DEBUG OVERLAY
  // ========================================
  
  /**
   * Create debug overlay element
   */
  createDebugOverlay() {
    if (this.debugOverlay) return this.debugOverlay;
    
    this.debugOverlay = document.getElementById("cheeseDebug");
    if (this.debugOverlay) return this.debugOverlay;
    
    this.debugOverlay = document.createElement("div");
    this.debugOverlay.id = "cheeseDebug";
    Object.assign(this.debugOverlay.style, {
      position: "fixed",
      top: "20px",
      left: "50%",
      transform: "translateX(-50%)",
      padding: "8px 12px",
      background: "rgba(10, 12, 24, 0.8)",
      color: "#9ca3af",
      fontFamily: "Montserrat, Arial, sans-serif",
      fontSize: "12px",
      border: "1px solid rgba(148, 163, 184, 0.25)",
      borderRadius: "6px",
      pointerEvents: "none",
      boxShadow: "0 0 8px rgba(15, 23, 42, 0.4)",
      zIndex: "999"
    });
    this.container.appendChild(this.debugOverlay);
    
    this.refreshDebugOverlay();
    return this.debugOverlay;
  }
  
  /**
   * Refresh debug overlay with current state
   */
  refreshDebugOverlay() {
    if (!this.debugOverlay) return;
    
    const isProduction = this.config.getIsProduction();
    const envText = isProduction ? "Production" : "Local";
    const port = window.location.port || "(default)";
    const debugState = this.config.getDebugState();
    const isPaused = this.config.getIsGamePaused();
    const cameraMode = this.config.getCameraMode();
    
    const pendingText = `Pending: ${debugState.pending || 0}`;
    const pausedText = `Paused: ${isPaused ? "yes" : "no"}`;
    let lastStatus = "Last: none";
    
    if (debugState.lastResponse) {
      const status = debugState.lastResponse.success ? "ok" : "fail";
      const detail = debugState.lastResponse.success
        ? `total=${debugState.lastResponse.total ?? "?"}`
        : debugState.lastResponse.error || debugState.lastResponse.details?.error || "error";
      lastStatus = `Last: ${status} (${detail})`;
    }
    
    const cameraModeText = cameraMode === 0 ? "1st Person" : (cameraMode === 1 ? "3rd Person" : "Joystick View");
    const apiBaseURL = this.config.getAPIBaseURL();
    const sessionId = this.config.getCheeseSessionId();
    
    // Get player position for XYZ display
    const playerPos = this.config.getPlayerPosition();
    const posX = playerPos.x.toFixed(2);
    const posY = playerPos.y.toFixed(2);
    const posZ = playerPos.z.toFixed(2);
    const positionText = `Position: X: ${posX} Y: ${posY} Z: ${posZ}`;
    
    // Display debug info with XYZ position on new line below
    // Using innerHTML to support line breaks (user requested position display below camera info)
    this.debugOverlay.innerHTML = [
      `Env: ${envText} | Host: ${window.location.hostname}:${port}`,
      `API: ${apiBaseURL} | Session: ${sessionId.slice(0, 8)}`,
      `Camera: ${cameraModeText} | ${pausedText} | ${pendingText} | ${lastStatus}`,
      positionText
    ].join("<br>");
  }
  
  // ========================================
  // CROSSHAIR
  // ========================================
  
  /**
   * Create crosshair element
   */
  createCrosshair() {
    if (this.crosshair) return this.crosshair;
    
    this.crosshair = document.getElementById("crosshair");
    if (this.crosshair) return this.crosshair;
    
    this.crosshair = document.createElement("div");
    this.crosshair.id = "crosshair";
    Object.assign(this.crosshair.style, {
      position: 'fixed',
      top: '50%',
      left: '50%',
      transform: 'translate(-50%, -50%)',
      width: '16px',
      height: '16px',
      pointerEvents: 'none',
      zIndex: '1000'
    });
    
    const createLine = (width, height) => {
      const line = document.createElement('div');
      Object.assign(line.style, {
        position: 'absolute',
        background: 'rgba(255, 255, 255, 0.9)',
        width: width,
        height: height,
        left: '50%',
        top: '50%',
        transform: 'translate(-50%, -50%)',
        borderRadius: '1px',
        transition: 'background-color 0.1s, box-shadow 0.1s'
      });
      return line;
    };
    
    const horizontal = createLine('16px', '2px');
    const vertical = createLine('2px', '16px');
    
    this.crosshair.appendChild(horizontal);
    this.crosshair.appendChild(vertical);
    this.container.appendChild(this.crosshair);
    
    return this.crosshair;
  }
  
  /**
   * Update crosshair visibility
   * @param {boolean} visible - Whether crosshair should be visible
   */
  setCrosshairVisible(visible) {
    if (!this.crosshair) this.createCrosshair();
    if (this.crosshair) {
      this.crosshair.style.display = visible ? 'block' : 'none';
    }
  }
  
  /**
   * Update crosshair color (for hit feedback, etc.)
   * @param {string} color - CSS color string
   */
  setCrosshairColor(color) {
    if (!this.crosshair) return;
    const lines = this.crosshair.querySelectorAll('div');
    lines.forEach(line => {
      line.style.background = color;
    });
  }
  
  // ========================================
  // INTERACTION PROMPT
  // ========================================
  
  /**
   * Create interaction prompt element (for chest opening, etc.)
   */
  createInteractionPrompt() {
    if (this.interactionPrompt) return this.interactionPrompt;
    
    this.interactionPrompt = document.getElementById("interactionPrompt");
    if (this.interactionPrompt) return this.interactionPrompt;
    
    this.interactionPrompt = document.createElement("div");
    this.interactionPrompt.id = "interactionPrompt";
    Object.assign(this.interactionPrompt.style, {
      position: 'fixed',
      top: '50%',
      left: '50%',
      transform: 'translate(-50%, -50%)',
      marginTop: '80px', // Position below crosshair (increased from 60px)
      padding: '16px 28px', // Increased padding for better visibility
      background: 'rgba(14, 12, 20, 0.95)', // Slightly more opaque
      color: '#ffe066',
      fontFamily: 'Montserrat, Arial, sans-serif',
      fontSize: '24px', // Increased from 18px for better visibility
      fontWeight: '700', // Increased from 600 for bolder text
      border: '3px solid rgba(255, 224, 102, 0.7)', // Thicker border
      borderRadius: '10px',
      boxShadow: '0 0 30px rgba(255, 224, 102, 0.5), 0 4px 12px rgba(0, 0, 0, 0.4)', // Enhanced glow
      pointerEvents: 'none',
      zIndex: '999',
      display: 'none', // Hidden by default
      textAlign: 'center',
      whiteSpace: 'nowrap',
      transition: 'opacity 0.2s ease, transform 0.2s ease',
      textShadow: '0 2px 4px rgba(0, 0, 0, 0.5)', // Text shadow for better readability
      letterSpacing: '0.5px' // Slight letter spacing for better readability
    });
    this.interactionPrompt.innerText = "Press [E] to Open";
    this.container.appendChild(this.interactionPrompt);
    
    return this.interactionPrompt;
  }
  
  /**
   * Show interaction prompt with custom message
   * @param {string} message - Message to display (default: "Press [E] to Open")
   */
  showInteractionPrompt(message = "Press [E] to Open") {
    if (!this.interactionPrompt) this.createInteractionPrompt();
    if (this.interactionPrompt) {
      this.interactionPrompt.innerText = message;
      this.interactionPrompt.style.display = 'block';
      this.interactionPrompt.style.opacity = '0';
      // Fade in animation
      requestAnimationFrame(() => {
        if (this.interactionPrompt) {
          this.interactionPrompt.style.opacity = '1';
        }
      });
    }
  }
  
  /**
   * Hide interaction prompt
   */
  hideInteractionPrompt() {
    if (!this.interactionPrompt) return;
    // Fade out animation
    if (this.interactionPrompt.style.opacity !== '0') {
      this.interactionPrompt.style.opacity = '0';
      setTimeout(() => {
        if (this.interactionPrompt) {
          this.interactionPrompt.style.display = 'none';
        }
      }, 200); // Match transition duration
    } else {
      this.interactionPrompt.style.display = 'none';
    }
  }
  
  // ========================================
  // TOAST NOTIFICATIONS
  // ========================================
  
  /**
   * Show a toast notification
   * @param {string} message - Message to display
   * @param {Object} options - Toast options
   * @param {string|null} options.id - Unique ID for the toast (prevents duplicates)
   * @param {number} options.duration - Duration in milliseconds (default: 5500)
   * @returns {HTMLElement} The created toast element
   */
  showRiddleToast(message, options = {}) {
    const { id = null, duration = 5500 } = options;
    
    // Remove existing toast with same ID if present
    if (id && this.activeRiddleToasts.has(id)) {
      const existing = this.activeRiddleToasts.get(id);
      if (existing && document.body.contains(existing)) {
        document.body.removeChild(existing);
      }
      this.activeRiddleToasts.delete(id);
    }
    
    // Create toast element
    const toast = document.createElement("div");
    toast.textContent = message;
    Object.assign(toast.style, {
      position: "fixed",
      top: "32px",
      left: "50%",
      transform: "translateX(-50%)",
      padding: "18px 32px",
      borderRadius: "16px",
      background: "rgba(15, 23, 42, 0.92)",
      border: "1px solid rgba(255, 224, 102, 0.35)",
      color: "#ffe066",
      fontFamily: "Montserrat, Arial, sans-serif",
      fontSize: "15px",
      fontWeight: "600",
      zIndex: "100001",
      boxShadow: "0 12px 30px rgba(0,0,0,0.35)",
      letterSpacing: "0.4px",
      textTransform: "uppercase",
      textAlign: "center",
      pointerEvents: "none"
    });
    
    this.container.appendChild(toast);
    
    // Store toast if ID provided
    if (id) {
      this.activeRiddleToasts.set(id, toast);
    }
    
    // Auto-remove after duration
    setTimeout(() => {
      toast.style.opacity = "0";
      toast.style.transition = "opacity 0.6s ease-out";
      setTimeout(() => {
        if (document.body.contains(toast)) {
          document.body.removeChild(toast);
        }
        if (id && this.activeRiddleToasts.get(id) === toast) {
          this.activeRiddleToasts.delete(id);
        }
      }, 600);
    }, duration);
    
    return toast;
  }
  
  /**
   * Remove a toast by ID
   * @param {string} id - Toast ID to remove
   */
  removeToast(id) {
    if (this.activeRiddleToasts.has(id)) {
      const toast = this.activeRiddleToasts.get(id);
      if (toast && document.body.contains(toast)) {
        document.body.removeChild(toast);
      }
      this.activeRiddleToasts.delete(id);
    }
  }
  
  /**
   * Clear all active toasts
   */
  clearAllToasts() {
    this.activeRiddleToasts.forEach((toast, id) => {
      if (toast && document.body.contains(toast)) {
        document.body.removeChild(toast);
      }
    });
    this.activeRiddleToasts.clear();
  }
  
  // ========================================
  // MAD MODE NOTIFICATION
  // ========================================
  
  /**
   * Show Mad Mode notification (fun Level 1 feature)
   */
  showMadModeNotification() {
    // Remove any existing notification
    if (this.madModeNotification && document.body.contains(this.madModeNotification)) {
      document.body.removeChild(this.madModeNotification);
    }
    
    // Create notification element
    this.madModeNotification = document.createElement("div");
    this.madModeNotification.id = "madModeNotification";
    Object.assign(this.madModeNotification.style, {
      position: "fixed",
      top: "20px",
      right: "20px",
      zIndex: "10000",
      padding: "10px 16px",
      background: "linear-gradient(135deg, rgba(255, 68, 68, 0.95), rgba(255, 136, 0, 0.95))",
      border: "2px solid #ffaa00",
      borderRadius: "8px",
      color: "#fff",
      fontSize: "16px",
      fontWeight: "bold",
      textAlign: "center",
      boxShadow: "0 4px 12px rgba(255, 68, 68, 0.6), inset 0 0 8px rgba(255, 255, 255, 0.2)",
      animation: "madModePulse 0.3s ease-out",
      backdropFilter: "blur(4px)",
      userSelect: "none",
      pointerEvents: "none"
    });
    this.madModeNotification.textContent = "🧀💥 MAD MODE! 💥🧀";
    
    // Add pulse animation styles if not exists
    let styleElement = document.getElementById("madModeNotificationStyle");
    if (!styleElement) {
      styleElement = document.createElement("style");
      styleElement.id = "madModeNotificationStyle";
      styleElement.textContent = `
        @keyframes madModePulse {
          0% { transform: scale(0.8); opacity: 0; }
          50% { transform: scale(1.05); }
          100% { transform: scale(1); opacity: 1; }
        }
        @keyframes madModeFadeOut {
          0% { opacity: 1; transform: scale(1); }
          100% { opacity: 0; transform: scale(0.9); }
        }
      `;
      document.head.appendChild(styleElement);
    }
    
    this.container.appendChild(this.madModeNotification);
    
    // Auto-remove after 2.5 seconds with fade out
    setTimeout(() => {
      if (this.madModeNotification && document.body.contains(this.madModeNotification)) {
        this.madModeNotification.style.animation = "madModeFadeOut 0.4s ease-out";
        setTimeout(() => {
          if (this.madModeNotification && this.madModeNotification.parentNode) {
            this.madModeNotification.parentNode.removeChild(this.madModeNotification);
            this.madModeNotification = null;
          }
        }, 400);
      }
    }, 2500);
  }
  
  // ========================================
  // PAUSE MENU
  // ========================================
  
  /**
   * Create or get pause menu element
   */
  getPauseMenu() {
    if (!this.pauseMenu) {
      this.pauseMenu = document.createElement("div");
      Object.assign(this.pauseMenu.style, {
        position: "fixed",
        top: "0",
        left: "0",
        width: "100%",
        height: "100%",
        display: "none",
        alignItems: "center",
        justifyContent: "center",
        flexDirection: "column",
        gap: "18px",
        background: "rgba(5, 7, 16, 0.88)",
        backdropFilter: "blur(6px)",
        zIndex: "99999", // CRITICAL: Very high z-index to be above EVERYTHING
        color: "#fef3c7",
        fontFamily: "Montserrat, Arial, sans-serif",
        pointerEvents: "auto" // CRITICAL: Menu must receive clicks
      });

      const panel = document.createElement("div");
      Object.assign(panel.style, {
        background: "linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(17, 24, 39, 0.95))",
        border: "1px solid rgba(255, 224, 102, 0.35)",
        borderRadius: "14px",
        padding: "32px 36px",
        boxShadow: "0 20px 60px rgba(0, 0, 0, 0.45)",
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        minWidth: "280px",
        maxWidth: "90vw",
        textAlign: "center",
        pointerEvents: "auto", // CRITICAL: Panel must receive clicks
        position: "relative",
        zIndex: "99999" // CRITICAL: Very high z-index
      });

      const title = document.createElement("div");
      title.textContent = "Cheese Temple Paused";
      Object.assign(title.style, {
        fontSize: "clamp(20px, 4vw, 26px)",
        fontWeight: "700",
        color: "#ffe066",
        marginBottom: "12px",
        textShadow: "0 0 16px rgba(255, 224, 102, 0.45)"
      });
      panel.appendChild(title);

      const subtitle = document.createElement("div");
      subtitle.id = "pause-subtitle";
      subtitle.textContent = "Take a breather, gifted hunter.";
      Object.assign(subtitle.style, {
        fontSize: "clamp(13px, 3vw, 15px)",
        color: "#cbd5f5",
        marginBottom: "14px"
      });
      panel.appendChild(subtitle);

      const playerInfo = document.createElement("div");
      playerInfo.id = "pause-player-info";
      Object.assign(playerInfo.style, {
        fontSize: "clamp(12px, 3vw, 14px)",
        color: "#fcd34d",
        marginBottom: "22px",
        display: "flex",
        flexDirection: "column",
        gap: "4px",
        alignItems: "center"
      });
      panel.appendChild(playerInfo);

      const actions = document.createElement("div");
      Object.assign(actions.style, {
        display: "flex",
        flexDirection: "column",
        gap: "12px",
        width: "100%"
      });

      const resumeBtn = document.createElement("button");
      resumeBtn.textContent = "Resume Hunt";
      this.stylePauseButton(resumeBtn, true);
      resumeBtn.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        // Hide menu first, then toggle pause (which will resume music)
        this.hidePauseMenu();
      });

      const backBtn = document.createElement("button");
      backBtn.textContent = "Back to Portal";
      this.stylePauseButton(backBtn, false);
      backBtn.addEventListener("click", (event) => {
        event.preventDefault();
        this.config.onBackToPortal();
      });

      const restartBtn = document.createElement("button");
      restartBtn.textContent = "Restart Level";
      this.stylePauseButton(restartBtn, false);
      restartBtn.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        // Hide menu first, then restart
        this.pauseMenu.style.display = "none";
        // Ensure game is unpaused before restarting
        if (this.config.onTogglePause) {
          this.config.onTogglePause(false);
        }
        // Call restart callback
        if (this.config.onRestart) {
          this.config.onRestart();
        }
      });

      const optionsBtn = document.createElement("button");
      optionsBtn.textContent = "Options";
      this.stylePauseButton(optionsBtn, false);
      
      // CRITICAL: Add pointer events and z-index to ensure button is clickable
      Object.assign(optionsBtn.style, {
        pointerEvents: "auto",
        position: "relative",
        zIndex: "9999"
      });
      
      optionsBtn.addEventListener("click", (event) => {
        console.log("🎮 [GUI] Options button clicked!");
        event.preventDefault();
        event.stopPropagation();
        // Ensure cursor is visible and controls are unlocked
        if (this.config.onUnlockControls) {
          this.config.onUnlockControls();
        }
        document.body.style.cursor = "default";
        console.log("🎮 [GUI] Calling onShowOptions callback");
        this.config.onShowOptions();
      });
      
      // CRITICAL: Add mouseenter/mouseleave for debugging
      optionsBtn.addEventListener("mouseenter", () => {
        console.log("🖱️ [GUI] Mouse entered Options button");
      });
      optionsBtn.addEventListener("mouseleave", () => {
        console.log("🖱️ [GUI] Mouse left Options button");
      });

      actions.appendChild(resumeBtn);
      actions.appendChild(optionsBtn);
      
      // 📱 MOBILE LANDSCAPE MODE (Always visible)
      {
        const landscapeSection = document.createElement("div");
        Object.assign(landscapeSection.style, {
          width: "100%",
          marginTop: "8px",
          marginBottom: "8px",
          display: "flex",
          flexDirection: "column",
          gap: "8px",
          alignItems: "center",
          padding: "12px",
          background: "rgba(15, 23, 42, 0.4)",
          borderRadius: "8px",
          border: "1px solid rgba(255, 224, 102, 0.2)"
        });

        const landscapeLabel = document.createElement("div");
        landscapeLabel.textContent = "📱 Landscape Mode";
        Object.assign(landscapeLabel.style, {
          fontSize: "13px",
          color: "#cbd5f5",
          marginBottom: "4px",
          fontWeight: "600",
          textAlign: "center"
        });
        landscapeSection.appendChild(landscapeLabel);

        const landscapeToggle = document.createElement("div");
        Object.assign(landscapeToggle.style, {
          display: "flex",
          gap: "8px",
          alignItems: "center"
        });

        window.forceLandscapeMode = window.forceLandscapeMode || false;

        const landscapeOffBtn = document.createElement("button");
        landscapeOffBtn.textContent = "Portrait";
        Object.assign(landscapeOffBtn.style, {
          padding: "6px 14px",
          borderRadius: "6px",
          border: "none",
          fontSize: "12px",
          fontWeight: "600",
          cursor: "pointer",
          transition: "all 0.2s",
          background: !window.forceLandscapeMode ? "rgba(255, 224, 102, 0.3)" : "rgba(255, 255, 255, 0.1)",
          color: !window.forceLandscapeMode ? "#ffe066" : "#cbd5f5"
        });
        landscapeOffBtn.addEventListener("click", async () => {
          window.forceLandscapeMode = false;
          if (screen.orientation && screen.orientation.unlock) {
            try {
              await screen.orientation.unlock();
              console.log("📱 [PAUSE MENU] Landscape mode: DISABLED - Screen rotation unlocked");
            } catch (err) {
              console.warn("⚠️ Failed to unlock screen orientation:", err);
              console.log("💡 [PAUSE MENU] Screen orientation unlock not available on this device (normal on desktop)");
            }
          }
          if (this.config.onUpdateLandscapeButtons) {
            this.config.onUpdateLandscapeButtons();
          }
          if (this.config.onUpdateJoysticks) {
            setTimeout(() => this.config.onUpdateJoysticks(), 100);
          }
        });

        const landscapeOnBtn = document.createElement("button");
        landscapeOnBtn.textContent = "Landscape";
        Object.assign(landscapeOnBtn.style, {
          padding: "6px 14px",
          borderRadius: "6px",
          border: "none",
          fontSize: "12px",
          fontWeight: "600",
          cursor: "pointer",
          transition: "all 0.2s",
          background: window.forceLandscapeMode ? "rgba(255, 224, 102, 0.3)" : "rgba(255, 255, 255, 0.1)",
          color: window.forceLandscapeMode ? "#ffe066" : "#cbd5f5"
        });
        landscapeOnBtn.addEventListener("click", async () => {
          window.forceLandscapeMode = true;
          if (screen.orientation && screen.orientation.lock) {
            try {
              await screen.orientation.lock('landscape');
              console.log("📱 [PAUSE MENU] Landscape mode: ENABLED - Screen locked to landscape");
            } catch (err) {
              console.warn("⚠️ Failed to lock screen orientation to landscape:", err);
              if (window.showRiddleToast) {
                window.showRiddleToast("📱 Please rotate your device to landscape mode manually", { duration: 3000 });
              } else {
                console.log("💡 [PAUSE MENU] Screen orientation lock not available on this device (normal on desktop)");
              }
            }
          }
          if (this.config.onUpdateLandscapeButtons) {
            this.config.onUpdateLandscapeButtons();
          }
          if (this.config.onUpdateJoysticks) {
            setTimeout(() => this.config.onUpdateJoysticks(), 100);
          }
        });

        landscapeToggle.appendChild(landscapeOffBtn);
        landscapeToggle.appendChild(landscapeOnBtn);
        landscapeSection.appendChild(landscapeToggle);
        actions.appendChild(landscapeSection);
      }
      
      actions.appendChild(backBtn);
      actions.appendChild(restartBtn);
      panel.appendChild(actions);
      this.pauseMenu.appendChild(panel);
      this.container.appendChild(this.pauseMenu);
    }
    return this.pauseMenu;
  }
  
  /**
   * Style a pause menu button
   * @param {HTMLElement} button - Button element
   * @param {boolean} primary - Whether this is a primary button
   */
  stylePauseButton(button, primary) {
    Object.assign(button.style, {
      padding: "12px 18px",
      borderRadius: "10px",
      border: "none",
      fontSize: "15px",
      fontWeight: "600",
      cursor: "pointer",
      color: primary ? "#0f172a" : "#ffe066",
      background: primary
        ? "linear-gradient(135deg, #ffe066, #facc15)"
        : "linear-gradient(135deg, rgba(15, 23, 42, 0.92), rgba(15, 23, 42, 0.7))",
      boxShadow: primary
        ? "0 10px 30px rgba(250, 204, 21, 0.35)"
        : "0 10px 30px rgba(15, 23, 42, 0.4)",
      transition: "transform 0.15s ease, box-shadow 0.15s ease"
    });
    button.addEventListener("mouseenter", () => {
      button.style.transform = "translateY(-1px)";
      button.style.boxShadow = primary
        ? "0 12px 34px rgba(250, 204, 21, 0.45)"
        : "0 12px 34px rgba(15, 23, 42, 0.55)";
    });
    button.addEventListener("mouseleave", () => {
      button.style.transform = "translateY(0)";
      button.style.boxShadow = primary
        ? "0 10px 30px rgba(250, 204, 21, 0.35)"
        : "0 10px 30px rgba(15, 23, 42, 0.4)";
    });
  }
  
  /**
   * Update pause menu player info display
   */
  updatePausePlayerInfo() {
    const info = document.getElementById("pause-player-info");
    if (!info) return;
    
    const playerName = this.config.getPlayerName();
    const dspoinc = this.config.getDSPOINC();
    
    const nameLine = `Player: <span style="color: #fcd34d; font-weight: 700;">${playerName || "Guest"}</span>`;
    const dspoLine = `DSPOINC: <span style="color: #fcd34d; font-weight: 700;">${dspoinc !== null && Number.isFinite(dspoinc) ? dspoinc.toLocaleString() : "Loading..."}</span>`;
    info.innerHTML = `<div>${nameLine}</div><div>${dspoLine}</div>`;
    
    // Update subtitle
    const subtitle = document.getElementById("pause-subtitle");
    if (subtitle) {
      subtitle.textContent = playerName ? `Take a breather, ${playerName}.` : "Take a breather, gifted hunter.";
    }
  }
  
  /**
   * Show pause menu
   */
  async showPauseMenu() {
    const menu = this.getPauseMenu();
    
    // CRITICAL: Hide options menu if it's open (it has lower z-index and would cover pause menu)
    if (this.config.onHideOptionsMenu) {
      this.config.onHideOptionsMenu();
      console.log("🎮 [GUI] Options menu hidden when pause menu opens");
    }
    
    // CRITICAL: Hide boss health bar when pausing (if it exists)
    // This ensures it doesn't interfere with pause menu clicks
    if (this.bossHealthBar) {
      this.bossHealthBar.style.display = "none";
      console.log("🔥 [GUI] Boss health bar hidden during pause");
    }
    
    // Hide Phoenix behavior display when pausing
    if (this.phoenixBehaviorDisplay) {
      this.phoenixBehaviorDisplay.style.display = "none";
      console.log("🐉 [GUI] Phoenix behavior display hidden during pause");
    }
    
    // CRITICAL: Store pointer lock state BEFORE unlocking
    // This is needed to restore pointer lock after resume
    if (this.config.onStorePointerLockState) {
      this.config.onStorePointerLockState();
    }
    
    // CRITICAL: Set game paused state via togglePause callback
    // This ensures isGamePaused is set correctly in main.js
    if (this.config.onTogglePause) {
      this.config.onTogglePause(true);
    }
    
    // Unlock controls before showing menu
    if (this.config.onUnlockControls) {
      this.config.onUnlockControls();
    }
    
    // CRITICAL: Ensure pause menu is on top with highest z-index
    menu.style.display = "flex";
    menu.style.zIndex = "99999"; // Nuclear z-index to be above everything
    
    // Hide mobile joysticks when paused
    if (this.config.onHideJoysticks) {
      this.config.onHideJoysticks();
    }
    
    // Fetch player details before updating menu
    if (this.config.onFetchPlayerDetails) {
      await this.config.onFetchPlayerDetails();
    }
    
    // Update player info
    this.updatePausePlayerInfo();
    
    // Refresh debug overlay
    this.refreshDebugOverlay();
  }
  
  /**
   * Hide pause menu
   */
  hidePauseMenu() {
    if (!this.pauseMenu) return;
    
    this.pauseMenu.style.display = "none";
    
    // CRITICAL: Show boss health bar again when unpausing (if Level 6 with boss)
    // Check if we're in a boss fight level and restore health bar visibility
    const currentLevel = this.config.getCurrentLevel ? this.config.getCurrentLevel() : null;
    if (this.bossHealthBar && currentLevel === this.config.LEVEL_IDS?.LEVEL6) {
      this.bossHealthBar.style.display = "block";
      console.log("🔥 [GUI] Boss health bar restored after resume");
    }
    
    // Show Phoenix behavior display again when unpausing (if Level 6 with boss and God Mode)
    const isGodMode = this.config.getGodMode ? this.config.getGodMode() : false;
    if (this.phoenixBehaviorDisplay && currentLevel === this.config.LEVEL_IDS?.LEVEL6 && isGodMode) {
      this.phoenixBehaviorDisplay.style.display = "block";
      console.log("🐉 [GUI] Phoenix behavior display restored after resume");
    }
    
    // CRITICAL: Set game unpaused state via togglePause callback
    // This ensures isGamePaused is set correctly and music resumes in main.js
    if (this.config.onTogglePause) {
      this.config.onTogglePause(false);
    }
    
    // CRITICAL: Use the same restoration logic as warp functions
    // This ensures controls work immediately after resuming
    if (this.config.onRestoreGameStateAfterWarp) {
      this.config.onRestoreGameStateAfterWarp();
    } else {
      // Fallback: Use individual callbacks if restore function not available
      // Show mobile joysticks when unpaused (if needed)
      if (this.config.onShowJoysticks) {
        this.config.onShowJoysticks();
      }
      
      // CRITICAL: Request pointer lock restoration (done in main.js via callback)
      // This callback will set needsPointerLockAfterPause flag based on stored state
      if (this.config.onRequestPointerLockRestore) {
        this.config.onRequestPointerLockRestore();
      }
    }
    
    // Refresh debug overlay
    this.refreshDebugOverlay();
  }
  
  /**
   * Toggle pause menu
   * @param {boolean|null} forceState - Force pause (true) or unpause (false), or toggle (null/undefined)
   */
  async togglePause(forceState) {
    const shouldPause = forceState !== undefined ? forceState : !this.config.getIsGamePaused();
    
    if (shouldPause) {
      await this.showPauseMenu();
    } else {
      this.hidePauseMenu();
    }
  }
  
  // ========================================
  // OPTIONS MENU
  // ========================================
  
  /**
   * Create or get options menu element
   * Note: This is a very large component (~500 lines in original)
   * We create the basic structure and use callbacks for all interactions
   */
  getOptionsMenu() {
    // Options menu will be created on-demand when needed
    // For now, return placeholder structure
    // Full implementation will be added incrementally
    if (!this.optionsMenu) {
      console.log('📋 [GUI] Options Menu will be created when showOptionsMenu() is called');
    }
    return this.optionsMenu;
  }
  
  /**
   * Show options menu
   * Calls main.js function via callback - menu creation handled there initially
   */
  showOptionsMenu() {
    if (this.config.onShowOptionsMenu) {
      this.config.onShowOptionsMenu();
    }
  }
  
  /**
   * Hide options menu
   */
  hideOptionsMenu() {
    if (this.optionsMenu) {
      this.optionsMenu.style.display = "none";
    }
    // Clear flag to allow pointer lock again
    window.optionsMenuOpen = false;
  }
  
  // ========================================
  // GAME OVER SCREEN
  // ========================================
  
  /**
   * Show game over screen (used for Level 1, 2, 3 deaths)
   * @param {Object} options - Game over options
   * @param {string} options.deathType - Type of death ('bearTrapLevel1', 'bearTrapLevel2', 'crushLevel3', 'monsterLevel4')
   */
  showGameOverScreen(options = {}) {
    const { deathType = 'crushLevel3' } = options;
    
    // CRITICAL: Unlock pointer lock and show cursor when game over screen appears
    // This ensures users can click the restart button
    if (this.config.onUnlockControls) {
      this.config.onUnlockControls();
    }
    document.body.style.cursor = "default";
    
    // Hide any existing game over screen
    this.hideGameOverScreen(false);
    
    // Hide completion screen if showing
    if (this.level1CompletionScreen && document.body.contains(this.level1CompletionScreen)) {
      this.hideLevel1CompletionScreen(false);
    }
    if (this.level2CompletionScreen && document.body.contains(this.level2CompletionScreen)) {
      this.hideLevel2CompletionScreen(false);
    }
    if (this.level3CompletionScreen && document.body.contains(this.level3CompletionScreen)) {
      this.hideLevel3CompletionScreen(false);
    }
    if (this.level4CompletionScreen && document.body.contains(this.level4CompletionScreen)) {
      this.hideLevel4CompletionScreen(false);
    }
    
    // Hide level selector if open
    if (this.levelSelectorScreen && document.body.contains(this.levelSelectorScreen)) {
      this.hideLevelSelector();
    }
    
    // Pause the game
    if (this.config.onTogglePause) {
      this.config.onTogglePause(true);
    }
    
    // Create game over screen overlay
    this.level3GameOverScreen = document.createElement("div");
    Object.assign(this.level3GameOverScreen.style, {
      position: "fixed",
      top: 0,
      left: 0,
      width: "100%",
      height: "100%",
      display: "flex",
      alignItems: "center",
      justifyContent: "center",
      flexDirection: "column",
      gap: "18px",
      background: "rgba(0, 0, 0, 0.95)",
      backdropFilter: "blur(12px)",
      zIndex: "1005",
      color: "#fef3c7",
      fontFamily: "Montserrat, Arial, sans-serif",
      pointerEvents: "auto",
      cursor: "default",
      animation: "fadeIn 0.3s ease-out"
    });
    
    // Add fade-in animation if not exists
    if (!document.getElementById("gameOverFadeInStyle")) {
      const style = document.createElement("style");
      style.id = "gameOverFadeInStyle";
      style.textContent = `
        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }
        @keyframes shake {
          0%, 100% { transform: translateX(0); }
          25% { transform: translateX(-10px); }
          75% { transform: translateX(10px); }
        }
      `;
      document.head.appendChild(style);
    }
    
    const panel = document.createElement("div");
    Object.assign(panel.style, {
      background: "linear-gradient(135deg, rgba(127, 29, 29, 0.98), rgba(69, 10, 10, 0.98))",
      border: "3px solid rgba(239, 68, 68, 0.8)",
      borderRadius: "16px",
      padding: "40px 48px",
      maxWidth: "520px",
      textAlign: "center",
      boxShadow: "0 12px 48px rgba(239, 68, 68, 0.4), 0 0 60px rgba(239, 68, 68, 0.2)",
      animation: "shake 0.5s ease-out"
    });
    
    // Dramatic title
    const title = document.createElement("h2");
    title.textContent = "💥 CRUSHED!";
    Object.assign(title.style, {
      margin: "0 0 8px 0",
      fontSize: "42px",
      fontWeight: "900",
      color: "#fef3c7",
      textShadow: "0 4px 16px rgba(239, 68, 68, 0.8), 0 0 24px rgba(239, 68, 68, 0.6)",
      letterSpacing: "2px"
    });
    panel.appendChild(title);
    
    // Subtitle
    const subtitle = document.createElement("h3");
    subtitle.textContent = "GAME OVER";
    Object.assign(subtitle.style, {
      margin: "0 0 20px 0",
      fontSize: "24px",
      fontWeight: "700",
      color: "#fca5a5",
      textShadow: "0 2px 8px rgba(239, 68, 68, 0.6)",
      letterSpacing: "4px"
    });
    panel.appendChild(subtitle);
    
    // Message based on death type
    const message = document.createElement("p");
    let deathMessage = "The moving walls have crushed you! The labyrinth proved too dangerous this time.";
    if (deathType === 'bearTrapLevel1') {
      deathMessage = "The bear trap snapped shut! Level 1 proved too dangerous this time.";
    } else if (deathType === 'bearTrapLevel2') {
      deathMessage = "The bear trap caught you! Level 2 proved too dangerous this time.";
    } else if (deathType === 'monsterLevel4') {
      deathMessage = "You were hit by a monster projectile! The hunt ended in defeat.";
    }
    
    message.textContent = deathMessage;
    Object.assign(message.style, {
      margin: "0 0 32px 0",
      fontSize: "16px",
      lineHeight: "1.6",
      color: "#fecaca"
    });
    panel.appendChild(message);
    
    // Button container
    const buttonContainer = document.createElement("div");
    Object.assign(buttonContainer.style, {
      display: "flex",
      flexDirection: "column",
      gap: "14px",
      width: "100%"
    });
    
    const createButton = (text, onClick, isPrimary = false) => {
      const btn = document.createElement("button");
      btn.textContent = text;
      Object.assign(btn.style, {
        padding: "14px 28px",
        fontSize: "17px",
        fontWeight: "600",
        border: "none",
        borderRadius: "10px",
        cursor: "pointer",
        transition: "all 0.2s",
        background: isPrimary
          ? "linear-gradient(135deg, #ef4444, #dc2626)"
          : "rgba(239, 68, 68, 0.25)",
        color: "#fef3c7",
        border: `2px solid ${isPrimary ? "#f87171" : "rgba(239, 68, 68, 0.5)"}`,
        boxShadow: isPrimary ? "0 4px 12px rgba(239, 68, 68, 0.3)" : "none"
      });
      btn.onmouseenter = () => {
        btn.style.transform = "scale(1.05)";
        btn.style.boxShadow = isPrimary 
          ? "0 6px 20px rgba(239, 68, 68, 0.5)" 
          : "0 4px 12px rgba(239, 68, 68, 0.3)";
      };
      btn.onmouseleave = () => {
        btn.style.transform = "scale(1)";
        btn.style.boxShadow = isPrimary 
          ? "0 4px 12px rgba(239, 68, 68, 0.3)" 
          : "none";
      };
      btn.onclick = onClick;
      return btn;
    };
    
    // Determine buttons based on death type
    const level1State = this.config.getLevel1State();
    const level2State = this.config.getLevel2State();
    
    if (level1State?.bearTrapDeathActive) {
      // Level 1 bear trap: Show "Restart Level 1" button
      buttonContainer.appendChild(
        createButton("🔄 Restart Level 1", () => {
          this.hideGameOverScreen();
          // CRITICAL: Unpause game if paused (game over screen pauses the game)
          if (this.config.onTogglePause) {
            this.config.onTogglePause(false);
          }
          // CRITICAL: Request pointer lock restoration after restart
          // Use setTimeout to ensure level is loaded first
          setTimeout(() => {
            if (this.config.onRequestPointerLockRestore) {
              this.config.onRequestPointerLockRestore();
            }
          }, 500); // Delay to ensure level is fully loaded
          if (this.config.onRestartLevel1) {
            this.config.onRestartLevel1();
          }
        }, true)
      );
    } else if (level2State?.bearTrapDeathActive) {
      // Level 2 bear trap: Show "Restart Level 2" and "Go to Level 1" buttons
      buttonContainer.appendChild(
        createButton("🔄 Restart Level 2", () => {
          this.hideGameOverScreen();
          // CRITICAL: Unpause game if paused
          if (this.config.onTogglePause) {
            this.config.onTogglePause(false);
          }
          // Request pointer lock restoration after restart
          setTimeout(() => {
            if (this.config.onRequestPointerLockRestore) {
              this.config.onRequestPointerLockRestore();
            }
          }, 500);
          if (this.config.onRestartLevel2) {
            this.config.onRestartLevel2();
          }
        }, true)
      );
      
      buttonContainer.appendChild(
        createButton("🏠 Go to Level 1", () => {
          this.hideGameOverScreen();
          // CRITICAL: Unpause game if paused
          if (this.config.onTogglePause) {
            this.config.onTogglePause(false);
          }
          // Request pointer lock restoration after warp
          setTimeout(() => {
            if (this.config.onRequestPointerLockRestore) {
              this.config.onRequestPointerLockRestore();
            }
          }, 500);
          if (this.config.onWarpToLevel1) {
            this.config.onWarpToLevel1();
          }
        })
      );
    } else {
      // Level 3/4 crush/death: Show standard buttons
      buttonContainer.appendChild(
        createButton("🔄 Try Again", () => {
          this.hideGameOverScreen();
          // CRITICAL: Unpause game if paused
          if (this.config.onTogglePause) {
            this.config.onTogglePause(false);
          }
          // Request pointer lock restoration after restart
          setTimeout(() => {
            if (this.config.onRequestPointerLockRestore) {
              this.config.onRequestPointerLockRestore();
            }
          }, 500);
          if (this.config.onRestartLevel3) {
            this.config.onRestartLevel3();
          }
        }, true)
      );
      
      // Level Select button (only for Level 3)
      if (deathType === 'crushLevel3' && this.config.getGodMode()) {
        buttonContainer.appendChild(
          createButton("🎮 Level Select", () => {
            this.hideGameOverScreen();
            if (this.config.onShowLevelSelector) {
              this.config.onShowLevelSelector();
            }
          })
        );
      }
      
      // Return to Level 1 button
      buttonContainer.appendChild(
        createButton("🏠 Return to Level 1", () => {
          this.hideGameOverScreen();
          // CRITICAL: Unpause game if paused
          if (this.config.onTogglePause) {
            this.config.onTogglePause(false);
          }
          // Request pointer lock restoration after warp
          setTimeout(() => {
            if (this.config.onRequestPointerLockRestore) {
              this.config.onRequestPointerLockRestore();
            }
          }, 500);
          if (this.config.onWarpToLevel1) {
            this.config.onWarpToLevel1();
          }
        })
      );
    }
    
    panel.appendChild(buttonContainer);
    this.level3GameOverScreen.appendChild(panel);
    this.container.appendChild(this.level3GameOverScreen);
  }
  
  /**
   * Hide game over screen
   * @param {boolean} clearFlag - Whether to clear state flags
   */
  hideGameOverScreen(clearFlag = true) {
    if (this.level3GameOverScreen && document.body.contains(this.level3GameOverScreen)) {
      document.body.removeChild(this.level3GameOverScreen);
      this.level3GameOverScreen = null;
    }
    if (clearFlag) {
      const level3State = this.config.getLevel3State();
      if (level3State) {
        level3State.crushActive = false;
      }
    }
  }
  
  // ========================================
  // COMPLETION SCREENS (Level 1, 2, 3, 4)
  // ========================================
  
  /**
   * Show Level 1 completion screen
   */
  showLevel1CompletionScreen() {
    // Pause the game
    if (this.config.onTogglePause && !this.config.getIsGamePaused()) {
      this.config.onTogglePause(true);
    }
    
    // CRITICAL: Unlock pointer lock and show cursor when completion screen appears
    // This ensures users can click the buttons
    if (this.config.onUnlockControls) {
      this.config.onUnlockControls();
    }
    document.body.style.cursor = "default";
    
    // Remove existing if any
    this.hideLevel1CompletionScreen(false);
    
    // Create screen
    this.level1CompletionScreen = document.createElement("div");
    Object.assign(this.level1CompletionScreen.style, {
      position: "fixed", top: "0", left: "0", width: "100%", height: "100%",
      display: "flex", alignItems: "center", justifyContent: "center", flexDirection: "column", gap: "18px",
      backgroundImage: "url('/textures/backgrounds/cheesetemple1.png')",
      backgroundSize: "cover",
      backgroundPosition: "center",
      backgroundRepeat: "no-repeat",
      backgroundColor: "rgba(5, 7, 16, 0.85)",
      backdropFilter: "blur(8px)", zIndex: "1003",
      color: "#fef3c7", fontFamily: "Montserrat, Arial, sans-serif", pointerEvents: "auto", cursor: "default"
    });
    
    const panel = this._createCompletionPanel("🎉 LEVEL 1 COMPLETE! 🎉", "Cheese Temple - Level 1", 
      "Congratulations! You've solved all three riddles and activated the portal!");
    
    const buttonContainer = document.createElement("div");
    Object.assign(buttonContainer.style, {
      display: "flex", flexDirection: "column", gap: "16px", width: "100%", alignItems: "stretch"
    });
    
    const nextBtn = this._createCompletionButton("🚀 Next Level (Level 2)", () => {
      this.hideLevel1CompletionScreen();
      // Restore game state after navigation
      setTimeout(() => {
        if (this.config.onRestoreGameStateAfterWarp) {
          this.config.onRestoreGameStateAfterWarp();
        }
      }, 100);
      if (this.config.onWarpToLevel2) this.config.onWarpToLevel2();
    }, true);
    
    const replayBtn = this._createCompletionButton("🔄 Replay Level 1", () => {
      this.hideLevel1CompletionScreen();
      // Restore game state after restart
      setTimeout(() => {
        if (this.config.onRestoreGameStateAfterWarp) {
          this.config.onRestoreGameStateAfterWarp();
        }
      }, 100);
      if (this.config.onRestartLevel1) this.config.onRestartLevel1();
    }, false);
    
    buttonContainer.appendChild(nextBtn);
    buttonContainer.appendChild(replayBtn);
    panel.appendChild(buttonContainer);
    this.level1CompletionScreen.appendChild(panel);
    this.container.appendChild(this.level1CompletionScreen);
  }
  
  /**
   * Hide Level 1 completion screen
   */
  hideLevel1CompletionScreen(clearFlag = true) {
    if (this.level1CompletionScreen && document.body.contains(this.level1CompletionScreen)) {
      document.body.removeChild(this.level1CompletionScreen);
      this.level1CompletionScreen = null;
    }
  }
  
  /**
   * Show Level 2 completion screen
   */
  showLevel2CompletionScreen() {
    const level2State = this.config.getLevel2State();
    if (level2State?.completionScreenShown) return;
    
    if (level2State) level2State.completionScreenShown = true;
    if (this.config.onPlayLevelUpSound) this.config.onPlayLevelUpSound();
    if (this.config.onTogglePause && !this.config.getIsGamePaused()) {
      this.config.onTogglePause(true);
    }
    
    // CRITICAL: Unlock pointer lock and show cursor when completion screen appears
    if (this.config.onUnlockControls) {
      this.config.onUnlockControls();
    }
    document.body.style.cursor = "default";
    
    this.hideLevel2CompletionScreen(false);
    
    this.level2CompletionScreen = document.createElement("div");
    Object.assign(this.level2CompletionScreen.style, {
      position: "fixed", top: 0, left: 0, width: "100%", height: "100%",
      display: "flex", alignItems: "center", justifyContent: "center", flexDirection: "column", gap: "18px",
      backgroundImage: "url('/textures/backgrounds/cheesetemple1.png')",
      backgroundSize: "cover",
      backgroundPosition: "center",
      backgroundRepeat: "no-repeat",
      backgroundColor: "rgba(5, 7, 16, 0.85)",
      backdropFilter: "blur(8px)", zIndex: "1004",
      color: "#fef3c7", fontFamily: "Montserrat, Arial, sans-serif", pointerEvents: "auto", cursor: "default"
    });
    
    const panel = this._createCompletionPanel("🎉 LEVEL 2 COMPLETE! 🎉", "The Spawn - Level 2",
      "All weapons and monsters logged. The portal hums with energy.");
    
    const buttonContainer = document.createElement("div");
    Object.assign(buttonContainer.style, {
      display: "flex", flexDirection: "column", gap: "14px", width: "100%"
    });
    
    buttonContainer.appendChild(this._createCompletionButton("🚀 Proceed to Level 3", () => {
      this.hideLevel2CompletionScreen();
      setTimeout(() => {
        if (this.config.onRestoreGameStateAfterWarp) {
          this.config.onRestoreGameStateAfterWarp();
        }
      }, 100);
      if (this.config.onWarpToLevel3) this.config.onWarpToLevel3();
    }, true));
    
    buttonContainer.appendChild(this._createCompletionButton("↩ Back to Level 1", () => {
      this.hideLevel2CompletionScreen();
      setTimeout(() => {
        if (this.config.onRestoreGameStateAfterWarp) {
          this.config.onRestoreGameStateAfterWarp();
        }
      }, 100);
      if (this.config.onWarpToLevel1) this.config.onWarpToLevel1();
    }, false));
    
    buttonContainer.appendChild(this._createCompletionButton("🧀 Stay in Level 2", () => {
      this.hideLevel2CompletionScreen();
      setTimeout(() => {
        if (this.config.onRestoreGameStateAfterWarp) {
          this.config.onRestoreGameStateAfterWarp();
        }
      }, 100);
      if (this.config.onRestartLevel2) this.config.onRestartLevel2();
    }, false));
    
    panel.appendChild(buttonContainer);
    this.level2CompletionScreen.appendChild(panel);
    this.container.appendChild(this.level2CompletionScreen);
  }
  
  /**
   * Hide Level 2 completion screen
   */
  hideLevel2CompletionScreen(clearFlag = true) {
    if (this.level2CompletionScreen && document.body.contains(this.level2CompletionScreen)) {
      document.body.removeChild(this.level2CompletionScreen);
      this.level2CompletionScreen = null;
    }
    if (clearFlag) {
      const level2State = this.config.getLevel2State();
      if (level2State) level2State.completionScreenShown = false;
    }
  }
  
  /**
   * Show Level 3 completion screen
   */
  showLevel3CompletionScreen() {
    const level3State = this.config.getLevel3State();
    if (level3State?.completionScreenShown) return;
    
    if (level3State) level3State.completionScreenShown = true;
    if (this.config.onPlayLevelUpSound) this.config.onPlayLevelUpSound();
    if (this.config.onTogglePause && !this.config.getIsGamePaused()) {
      this.config.onTogglePause(true);
    }
    
    // CRITICAL: Unlock pointer lock and show cursor when completion screen appears
    if (this.config.onUnlockControls) {
      this.config.onUnlockControls();
    }
    document.body.style.cursor = "default";
    
    this.hideLevel3CompletionScreen(false);
    
    this.level3CompletionScreen = document.createElement("div");
    Object.assign(this.level3CompletionScreen.style, {
      position: "fixed", top: 0, left: 0, width: "100%", height: "100%",
      display: "flex", alignItems: "center", justifyContent: "center", flexDirection: "column", gap: "18px",
      backgroundImage: "url('/textures/backgrounds/cheesetemple1.png')",
      backgroundSize: "cover",
      backgroundPosition: "center",
      backgroundRepeat: "no-repeat",
      backgroundColor: "rgba(5, 7, 16, 0.85)",
      backdropFilter: "blur(8px)", zIndex: "1004",
      color: "#fef3c7", fontFamily: "Montserrat, Arial, sans-serif", pointerEvents: "auto", cursor: "default"
    });
    
    const panel = document.createElement("div");
    Object.assign(panel.style, {
      background: "linear-gradient(135deg, rgba(30, 41, 59, 0.98), rgba(17, 24, 39, 0.98))",
      border: "2px solid rgba(139, 92, 246, 0.5)", borderRadius: "16px", padding: "32px 40px",
      maxWidth: "500px", textAlign: "center", boxShadow: "0 8px 32px rgba(0, 0, 0, 0.5)"
    });
    
    const title = document.createElement("h2");
    title.textContent = "🎯 The Hunt Complete!";
    Object.assign(title.style, {
      margin: "0 0 16px 0", fontSize: "28px", fontWeight: "700", color: "#fef3c7",
      textShadow: "0 2px 8px rgba(139, 92, 246, 0.6)"
    });
    panel.appendChild(title);
    
    const totalMonsters = this.config.getLevel3MonstersPerStep ? this.config.getLevel3MonstersPerStep() * 2 : 10;
    const message = document.createElement("p");
    message.textContent = `You've successfully captured all ${totalMonsters} monsters! The portal is now open.`;
    Object.assign(message.style, {
      margin: "0 0 24px 0", fontSize: "16px", lineHeight: "1.6", color: "#cbd5e1"
    });
    panel.appendChild(message);
    
    const buttonContainer = document.createElement("div");
    Object.assign(buttonContainer.style, {
      display: "flex", flexDirection: "column", gap: "12px", width: "100%"
    });
    
    buttonContainer.appendChild(this._createCompletionButton("🚀 Proceed to Level 4", () => {
      this.hideLevel3CompletionScreen();
      setTimeout(() => {
        if (this.config.onRestoreGameStateAfterWarp) {
          this.config.onRestoreGameStateAfterWarp();
        }
      }, 100);
      if (this.config.onWarpToLevel4) this.config.onWarpToLevel4();
    }, true, { borderColor: "rgba(139, 92, 246, 0.4)", bgColor: "rgba(139, 92, 246, 0.2)", primaryGradient: "linear-gradient(135deg, #8b5cf6, #6366f1)" }));
    
    buttonContainer.appendChild(this._createCompletionButton("🧀 Stay in Level 3", () => {
      this.hideLevel3CompletionScreen();
      setTimeout(() => {
        if (this.config.onRestoreGameStateAfterWarp) {
          this.config.onRestoreGameStateAfterWarp();
        }
      }, 100);
      if (this.config.onRestartLevel3) this.config.onRestartLevel3();
    }, false, { borderColor: "rgba(139, 92, 246, 0.4)", bgColor: "rgba(139, 92, 246, 0.2)" }));
    
    buttonContainer.appendChild(this._createCompletionButton("🔄 Return to Level 2", () => {
      this.hideLevel3CompletionScreen();
      setTimeout(() => {
        if (this.config.onRestoreGameStateAfterWarp) {
          this.config.onRestoreGameStateAfterWarp();
        }
      }, 100);
      if (this.config.onWarpToLevel2) this.config.onWarpToLevel2();
    }, false, { borderColor: "rgba(139, 92, 246, 0.4)", bgColor: "rgba(139, 92, 246, 0.2)" }));
    
    buttonContainer.appendChild(this._createCompletionButton("🏠 Return to Level 1", () => {
      this.hideLevel3CompletionScreen();
      setTimeout(() => {
        if (this.config.onRestoreGameStateAfterWarp) {
          this.config.onRestoreGameStateAfterWarp();
        }
      }, 100);
      if (this.config.onWarpToLevel1) this.config.onWarpToLevel1();
    }, false, { borderColor: "rgba(139, 92, 246, 0.4)", bgColor: "rgba(139, 92, 246, 0.2)" }));
    
    panel.appendChild(buttonContainer);
    this.level3CompletionScreen.appendChild(panel);
    this.container.appendChild(this.level3CompletionScreen);
  }
  
  /**
   * Hide Level 3 completion screen
   */
  hideLevel3CompletionScreen(clearFlag = true) {
    if (this.level3CompletionScreen && document.body.contains(this.level3CompletionScreen)) {
      document.body.removeChild(this.level3CompletionScreen);
      this.level3CompletionScreen = null;
    }
    if (clearFlag) {
      const level3State = this.config.getLevel3State();
      if (level3State) level3State.completionScreenShown = false;
    }
  }
  
  /**
   * Show Level 4 completion screen
   */
  showLevel4CompletionScreen() {
    const level4State = this.config.getLevel4State();
    if (level4State?.completionScreenShown) return;
    
    if (level4State) level4State.completionScreenShown = true;
    if (this.config.onPlayLevelUpSound) this.config.onPlayLevelUpSound();
    if (this.config.onTogglePause && !this.config.getIsGamePaused()) {
      this.config.onTogglePause(true);
    }
    
    // CRITICAL: Unlock pointer lock and show cursor when completion screen appears
    if (this.config.onUnlockControls) {
      this.config.onUnlockControls();
    }
    document.body.style.cursor = "default";
    
    this.hideLevel4CompletionScreen(false);
    
    // Level 4 completion screen implementation
    // (Similar structure to other completion screens)
    this.level4CompletionScreen = document.createElement("div");
    Object.assign(this.level4CompletionScreen.style, {
      position: "fixed", top: 0, left: 0, width: "100%", height: "100%",
      display: "flex", alignItems: "center", justifyContent: "center", flexDirection: "column", gap: "18px",
      backgroundImage: "url('/textures/backgrounds/cheesetemple1.png')",
      backgroundSize: "cover",
      backgroundPosition: "center",
      backgroundRepeat: "no-repeat",
      backgroundColor: "rgba(5, 7, 16, 0.85)",
      backdropFilter: "blur(8px)", zIndex: "1004",
      color: "#fef3c7", fontFamily: "Montserrat, Arial, sans-serif", pointerEvents: "auto", cursor: "default"
    });
    
    const panel = this._createCompletionPanel("🎉 LEVEL 4 COMPLETE! 🎉", "The Arena - Level 4",
      "Congratulations! You've completed the arena challenge!");
    
    const buttonContainer = document.createElement("div");
    Object.assign(buttonContainer.style, {
      display: "flex", flexDirection: "column", gap: "14px", width: "100%"
    });
    
    buttonContainer.appendChild(this._createCompletionButton("🚀 Proceed to Level 5", () => {
      this.hideLevel4CompletionScreen();
      setTimeout(() => {
        if (this.config.onRestoreGameStateAfterWarp) {
          this.config.onRestoreGameStateAfterWarp();
        }
      }, 100);
      if (this.config.onWarpToLevel5) this.config.onWarpToLevel5();
    }, true));
    
    buttonContainer.appendChild(this._createCompletionButton("🧀 Stay in Level 4", () => {
      this.hideLevel4CompletionScreen();
      setTimeout(() => {
        if (this.config.onRestoreGameStateAfterWarp) {
          this.config.onRestoreGameStateAfterWarp();
        }
      }, 100);
      if (this.config.onRestartLevel4) this.config.onRestartLevel4();
    }, false));
    
    buttonContainer.appendChild(this._createCompletionButton("🔄 Return to Level 3", () => {
      this.hideLevel4CompletionScreen();
      setTimeout(() => {
        if (this.config.onRestoreGameStateAfterWarp) {
          this.config.onRestoreGameStateAfterWarp();
        }
      }, 100);
      if (this.config.onWarpToLevel3) this.config.onWarpToLevel3();
    }, false));
    
    panel.appendChild(buttonContainer);
    this.level4CompletionScreen.appendChild(panel);
    this.container.appendChild(this.level4CompletionScreen);
  }
  
  /**
   * Hide Level 4 completion screen
   */
  hideLevel4CompletionScreen(clearFlag = true) {
    if (this.level4CompletionScreen && document.body.contains(this.level4CompletionScreen)) {
      document.body.removeChild(this.level4CompletionScreen);
      this.level4CompletionScreen = null;
    }
    if (clearFlag) {
      const level4State = this.config.getLevel4State();
      if (level4State) level4State.completionScreenShown = false;
    }
  }
  
  // ========================================
  // LEVEL SELECTOR SCREEN
  // ========================================
  
  /**
   * Show level selector screen (GOD Mode menu)
   */
  showLevelSelector() {
    if (this.levelSelectorScreen && document.body.contains(this.levelSelectorScreen)) {
      this.hideLevelSelector();
      return;
    }
    
    // CRITICAL: Unlock pointer lock and show cursor when level selector appears
    // This ensures users can click the level buttons
    if (this.config.onUnlockControls) {
      this.config.onUnlockControls();
    }
    document.body.style.cursor = "default";
    
    if (this.config.onTogglePause && !this.config.getIsGamePaused()) {
      this.config.onTogglePause(true);
    }
    
    this.levelSelectorScreen = document.createElement("div");
    Object.assign(this.levelSelectorScreen.style, {
      position: "fixed", top: 0, left: 0, width: "100%", height: "100%",
      display: "flex", alignItems: "center", justifyContent: "center", flexDirection: "column", gap: "18px",
      backgroundImage: "url('/textures/backgrounds/cheesetemple1.png')",
      backgroundSize: "cover",
      backgroundPosition: "center",
      backgroundRepeat: "no-repeat",
      backgroundColor: "rgba(5, 7, 16, 0.85)",
      backdropFilter: "blur(8px)", zIndex: "10005",
      color: "#fef3c7", fontFamily: "Montserrat, Arial, sans-serif", pointerEvents: "auto", cursor: "default"
    });
    
    const panel = document.createElement("div");
    Object.assign(panel.style, {
      background: "linear-gradient(135deg, rgba(30, 41, 59, 0.98), rgba(17, 24, 39, 0.98))",
      border: "2px solid rgba(255, 224, 102, 0.5)", borderRadius: "16px", padding: "40px 48px",
      boxShadow: "0 20px 60px rgba(0, 0, 0, 0.6), 0 0 40px rgba(255, 224, 102, 0.3)",
      display: "flex", flexDirection: "column", alignItems: "center", minWidth: "400px", maxWidth: "90vw", textAlign: "center"
    });
    
    const title = document.createElement("div");
    title.textContent = "🎮 LEVEL SELECTOR (GOD MODE)";
    Object.assign(title.style, {
      fontSize: "clamp(24px, 5vw, 32px)", fontWeight: "700", color: "#ffe066", marginBottom: "12px",
      textShadow: "0 0 20px rgba(255, 224, 102, 0.6)"
    });
    panel.appendChild(title);
    
    const subtitle = document.createElement("div");
    // Check if game start is pending (showing selector after character selection)
    const isGameStartPending = this.config.isGameStartPending ? this.config.isGameStartPending() : false;
    subtitle.textContent = isGameStartPending 
      ? "Select a level to start the game" 
      : "Press L to toggle this menu";
    Object.assign(subtitle.style, {
      fontSize: "clamp(14px, 3vw, 18px)", color: "#cbd5f5", marginBottom: "32px"
    });
    panel.appendChild(subtitle);
    
    const buttonContainer = document.createElement("div");
    Object.assign(buttonContainer.style, {
      display: "flex", flexDirection: "column", gap: "14px", width: "100%"
    });
    
    const currentLevel = this.config.getCurrentLevel ? this.config.getCurrentLevel() : null;
    const LEVEL_IDS = this.config.getLevelIds ? this.config.getLevelIds() : {};
    
    // Create level buttons (Level 1-6)
    for (let i = 1; i <= 6; i++) {
      const levelNames = {
        1: "🧀 Level 1 - Cheese Temple",
        2: "🔫 Level 2 - The Spawn",
        3: "🎯 Level 3 - The Hunt",
        4: "⚔️ Level 4 - The Arena",
        5: "🚶 Level 5 - The Walk",
        6: "🔥 Level 6 - Phoenix Boss Arena"
      };
      
      const isCurrent = currentLevel === LEVEL_IDS[`LEVEL${i}`];
      const btn = this._createCompletionButton(levelNames[i], () => {
        // Hide selector first
        this.hideLevelSelector();
        
        // Check if game start is pending (January 4, 2026)
        const isGameStartPending = this.config.isGameStartPending ? this.config.isGameStartPending() : false;
        
        if (isGameStartPending) {
          // Game hasn't started yet - start it with selected level
          console.log(`🎮 [GOD MODE] Starting game with Level ${i}`);
          
          // Clear pending flag
          if (this.config.setGameStartPending) {
            this.config.setGameStartPending(false);
          }
          
          // Start game with selected level
          const levelId = LEVEL_IDS[`LEVEL${i}`];
          if (this.config.onStartGameWithLevel && typeof this.config.onStartGameWithLevel === 'function') {
            this.config.onStartGameWithLevel(levelId);
          } else {
            console.error("❌ [LEVEL SELECTOR] onStartGameWithLevel callback not found");
            // Fallback: start game normally (will load Level 1)
            if (this.config.onStartGame) {
              this.config.onStartGame();
            }
          }
        } else {
          // Game is already running - just warp to level (current behavior)
          const warpFnName = `onWarpToLevel${i}`;
          const warpFn = this.config[warpFnName];
          
          if (warpFn && typeof warpFn === 'function') {
            // Call warp function
            warpFn();
            
            // Request pointer lock restoration after warp
            setTimeout(() => {
              if (this.config.onRequestPointerLockRestore) {
                this.config.onRequestPointerLockRestore();
              }
            }, 500);
          } else {
            console.error(`❌ [LEVEL SELECTOR] Warp function ${warpFnName} not found or not a function`);
          }
        }
      }, false, {
        bgColor: isCurrent ? "rgba(255, 224, 102, 0.3)" : "rgba(255, 224, 102, 0.15)"
      });
      
      buttonContainer.appendChild(btn);
    }
    
    // Reset Chests button (admin/god mode only - for local testing)
    const isLocal = !this.config.getIsProduction || !this.config.getIsProduction();
    if (isLocal) {
      const resetChestsBtn = this._createCompletionButton("🔄 Reset All Chests", () => {
        // Confirm action
        if (confirm("Are you sure you want to reset all opened chests? This will allow you to open them again for testing.")) {
          // Get chest system from config or global
          const chestSystem = this.config.getChestSystem ? this.config.getChestSystem() : (window.chestSystem || null);
          
          if (chestSystem && typeof chestSystem.resetOpenedChests === 'function') {
            chestSystem.resetOpenedChests();
            console.log("✅ [GOD MODE] All chests reset - ready for testing");
            
            // Show confirmation message
            alert("✅ All chests have been reset! You can now open them again.");
          } else {
            console.error("❌ [GOD MODE] Chest system not available or reset method not found");
            alert("❌ Error: Could not reset chests. Chest system not available.");
          }
        }
      }, false, {
        bgColor: "rgba(255, 100, 100, 0.2)",
        hoverBgColor: "rgba(255, 100, 100, 0.3)"
      });
      buttonContainer.appendChild(resetChestsBtn);
    }
    
    // Close button
    const closeBtn = this._createCompletionButton("❌ Close", () => {
      this.hideLevelSelector();
    }, false);
    buttonContainer.appendChild(closeBtn);
    
    panel.appendChild(buttonContainer);
    this.levelSelectorScreen.appendChild(panel);
    this.container.appendChild(this.levelSelectorScreen);
  }
  
  /**
   * Hide level selector screen
   */
  hideLevelSelector() {
    if (this.levelSelectorScreen && document.body.contains(this.levelSelectorScreen)) {
      document.body.removeChild(this.levelSelectorScreen);
      this.levelSelectorScreen = null;
    }
    
    // CRITICAL: Unpause game and request pointer lock restoration
    if (this.config.onTogglePause) {
      this.config.onTogglePause(false);
    }
    
    // Request pointer lock restoration after closing level selector
    // Use setTimeout to ensure level is loaded first (if warping)
    setTimeout(() => {
      if (this.config.onRequestPointerLockRestore) {
        this.config.onRequestPointerLockRestore();
      }
    }, 100);
    
    // Restore previous God Mode state (handled via callback)
    if (this.config.onHideLevelSelector) {
      this.config.onHideLevelSelector();
    }
  }
  
  // ========================================
  // MAIN MENU
  // ========================================
  
  /**
   * Show main menu (first screen when game loads)
   */
  showMainMenu() {
    // If menu already exists and is in DOM, just show it
    if (this.mainMenu && this.container && this.container.contains(this.mainMenu)) {
      this.mainMenu.style.display = "flex";
      return;
    }
    
    // Clean up any existing menu first
    if (this.mainMenu) {
      this.hideMainMenu();
    }
    
    // Create new menu
    this.mainMenu = document.createElement("div");
    Object.assign(this.mainMenu.style, {
      position: "fixed", top: "0", left: "0", width: "100%", height: "100%",
      display: "flex", alignItems: "center", justifyContent: "center", flexDirection: "column", gap: "24px",
      backgroundImage: "url('/textures/backgrounds/cheesetemple1.png')",
      backgroundSize: "cover",
      backgroundPosition: "center",
      backgroundRepeat: "no-repeat",
      backgroundColor: "rgba(5, 7, 16, 0.85)",
      backdropFilter: "blur(6px)", zIndex: "1002",
      color: "#fef3c7", fontFamily: "Montserrat, Arial, sans-serif", pointerEvents: "auto", cursor: "default"
    });
    
    const panel = document.createElement("div");
    Object.assign(panel.style, {
      background: "linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(17, 24, 39, 0.95))",
      border: "1px solid rgba(255, 224, 102, 0.35)", borderRadius: "14px", padding: "48px 56px",
      boxShadow: "0 20px 60px rgba(0, 0, 0, 0.45)", display: "flex", flexDirection: "column",
      alignItems: "center", minWidth: "400px", maxWidth: "90vw", textAlign: "center"
    });
    
    // Title
    const title = document.createElement("div");
    title.textContent = "Welcome to Narrrf's World 3D Riddle Game";
    Object.assign(title.style, {
      fontSize: "clamp(24px, 4.5vw, 32px)", fontWeight: "700", color: "#ffe066", marginBottom: "32px",
      textShadow: "0 0 20px rgba(255, 224, 102, 0.5)", lineHeight: "1.3"
    });
    panel.appendChild(title);
    
    // Buttons container
    const buttonsContainer = document.createElement("div");
    Object.assign(buttonsContainer.style, {
      display: "flex", flexDirection: "column", gap: "16px", width: "100%", minWidth: "280px"
    });
    
    // New Game button
    const newGameBtn = this._createCompletionButton("New Game", () => {
      this.hideMainMenu();
      // Small delay to ensure main menu is fully hidden
      setTimeout(() => {
        this.showCharacterSelectionMenu();
      }, 50);
    }, true);
    Object.assign(newGameBtn.style, {
      width: "100%", padding: "16px 28px", fontSize: "clamp(16px, 3vw, 20px)"
    });
    buttonsContainer.appendChild(newGameBtn);
    
    // Options button
    const optionsBtn = this._createCompletionButton("Options", () => {
      // Hide main menu when opening options (options menu will overlay)
      // User can close options to return to main menu
      if (this.config.onShowOptions) {
        this.config.onShowOptions();
      }
    }, false);
    Object.assign(optionsBtn.style, {
      width: "100%", padding: "16px 28px", fontSize: "clamp(16px, 3vw, 20px)"
    });
    buttonsContainer.appendChild(optionsBtn);
    
    // Exit button
    const exitBtn = this._createCompletionButton("Exit", () => {
      if (this.config.onBackToPortal) {
        this.config.onBackToPortal();
      } else {
        // Fallback: Navigate to profile page
        window.location.href = this.config.PROFILE_URL || '/profile.html';
      }
    }, false);
    Object.assign(exitBtn.style, {
      width: "100%", padding: "16px 28px", fontSize: "clamp(16px, 3vw, 20px)"
    });
    buttonsContainer.appendChild(exitBtn);
    
    panel.appendChild(buttonsContainer);
    this.mainMenu.appendChild(panel);
    this.container.appendChild(this.mainMenu);
  }
  
  /**
   * Hide main menu
   */
  hideMainMenu() {
    if (this.mainMenu) {
      // Hide via display first (immediate visual removal)
      this.mainMenu.style.display = "none";
      this.mainMenu.style.pointerEvents = "none";
      this.mainMenu.style.zIndex = "-1";
      
      // Remove from DOM completely
      if (this.container && this.container.contains(this.mainMenu)) {
        this.container.removeChild(this.mainMenu);
      } else if (this.mainMenu.parentNode) {
        this.mainMenu.parentNode.removeChild(this.mainMenu);
      }
      
      // Clear reference
      this.mainMenu = null;
    }
  }
  
  // ========================================
  // CHARACTER SELECTION MENU
  // ========================================
  
  /**
   * Show character selection menu
   */
  showCharacterSelectionMenu() {
    // If menu already exists and is in DOM, just show it
    if (this.characterSelectionMenu && this.container && this.container.contains(this.characterSelectionMenu)) {
      this.characterSelectionMenu.style.display = "flex";
      return;
    }
    
    // Clean up any existing menu first
    if (this.characterSelectionMenu) {
      this.hideCharacterSelectionMenu();
    }
    
    // Create new menu
    this.characterSelectionMenu = document.createElement("div");
    Object.assign(this.characterSelectionMenu.style, {
      position: "fixed", top: "0", left: "0", width: "100%", height: "100%",
      display: "flex", alignItems: "center", justifyContent: "center", flexDirection: "column", gap: "18px",
      backgroundImage: "url('/textures/backgrounds/cheesetemple1.png')",
      backgroundSize: "cover",
      backgroundPosition: "center",
      backgroundRepeat: "no-repeat",
      backgroundColor: "rgba(5, 7, 16, 0.85)",
      backdropFilter: "blur(6px)", zIndex: "1003",
      color: "#fef3c7", fontFamily: "Montserrat, Arial, sans-serif", pointerEvents: "auto", cursor: "default"
    });
    
    const panel = document.createElement("div");
    Object.assign(panel.style, {
      background: "linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(17, 24, 39, 0.95))",
      border: "1px solid rgba(255, 224, 102, 0.35)", borderRadius: "14px", padding: "32px 36px",
      boxShadow: "0 20px 60px rgba(0, 0, 0, 0.45)", display: "flex", flexDirection: "column",
      alignItems: "center", minWidth: "320px", maxWidth: "90vw", textAlign: "center"
    });
    
    const title = document.createElement("div");
    title.textContent = "Select Character";
    Object.assign(title.style, {
      fontSize: "clamp(22px, 4vw, 28px)", fontWeight: "700", color: "#ffe066", marginBottom: "24px",
      textShadow: "0 0 16px rgba(255, 224, 102, 0.45)"
    });
    panel.appendChild(title);
    
    const subtitle = document.createElement("div");
    subtitle.textContent = "Choose your character";
    Object.assign(subtitle.style, {
      fontSize: "14px", color: "#cbd5f5", marginBottom: "24px"
    });
    panel.appendChild(subtitle);
    
    const characterButtons = document.createElement("div");
    Object.assign(characterButtons.style, {
      display: "flex", flexDirection: "column", gap: "12px", width: "100%", marginBottom: "20px"
    });
    
    // Mouse character button
    const mouseBtn = this._createCompletionButton("🐭 Mouse\nMouse Character", () => {
      if (this.config.onSelectCharacter) {
        this.config.onSelectCharacter('mouse');
      }
      // Hide menu immediately and ensure it stays hidden
      this.hideCharacterSelectionMenu();
      
      // Check if GOD MODE is enabled (January 4, 2026)
      const isGodMode = this.config.getGodMode ? this.config.getGodMode() : false;
      
      if (isGodMode) {
        // GOD MODE ON: Show level selector instead of starting game immediately
        console.log("🎮 [GOD MODE] Character selected - showing level selector instead of starting game");
        setTimeout(() => {
          this.hideCharacterSelectionMenu(); // Double-check it's hidden
          // Set flag that game start is pending
          if (this.config.setGameStartPending) {
            this.config.setGameStartPending(true);
          }
          // Show level selector
          this.showLevelSelector();
        }, 50);
      } else {
        // GOD MODE OFF: Start game normally (current behavior)
        setTimeout(() => {
          this.hideCharacterSelectionMenu(); // Double-check it's hidden
          if (this.config.onStartGame) this.config.onStartGame();
        }, 50);
      }
    }, false);
    
    // Animation Library character button
    const animLibBtn = this._createCompletionButton("🎭 Animation Library\nAnimation Library [Standard]", () => {
      if (this.config.onSelectCharacter) {
        this.config.onSelectCharacter('animation_library');
      }
      // Hide menu immediately and ensure it stays hidden
      this.hideCharacterSelectionMenu();
      
      // Check if GOD MODE is enabled (January 4, 2026)
      const isGodMode = this.config.getGodMode ? this.config.getGodMode() : false;
      
      if (isGodMode) {
        // GOD MODE ON: Show level selector instead of starting game immediately
        console.log("🎮 [GOD MODE] Character selected - showing level selector instead of starting game");
        setTimeout(() => {
          this.hideCharacterSelectionMenu(); // Double-check it's hidden
          // Set flag that game start is pending
          if (this.config.setGameStartPending) {
            this.config.setGameStartPending(true);
          }
          // Show level selector
          this.showLevelSelector();
        }, 50);
      } else {
        // GOD MODE OFF: Start game normally (current behavior)
        setTimeout(() => {
          this.hideCharacterSelectionMenu(); // Double-check it's hidden
          if (this.config.onStartGame) this.config.onStartGame();
        }, 50);
      }
    }, false);
    
    characterButtons.appendChild(mouseBtn);
    characterButtons.appendChild(animLibBtn);
    panel.appendChild(characterButtons);
    this.characterSelectionMenu.appendChild(panel);
    this.container.appendChild(this.characterSelectionMenu);
  }
  
  /**
   * Hide character selection menu
   */
  hideCharacterSelectionMenu() {
    if (this.characterSelectionMenu) {
      // Hide via display first (immediate visual removal)
      this.characterSelectionMenu.style.display = "none";
      this.characterSelectionMenu.style.pointerEvents = "none"; // Disable pointer events
      this.characterSelectionMenu.style.zIndex = "-1"; // Move behind everything
      
      // Remove from DOM completely
      if (this.container && this.container.contains(this.characterSelectionMenu)) {
        this.container.removeChild(this.characterSelectionMenu);
      } else if (this.characterSelectionMenu.parentNode) {
        // If not in container, remove from wherever it is
        this.characterSelectionMenu.parentNode.removeChild(this.characterSelectionMenu);
      }
      
      // Clear reference
      this.characterSelectionMenu = null;
    }
    
    // Also hide legacy menu if it exists (for compatibility)
    const legacyMenu = document.getElementById('characterSelectionMenu');
    if (legacyMenu) {
      legacyMenu.style.display = "none";
      legacyMenu.style.pointerEvents = "none";
      legacyMenu.style.zIndex = "-1";
      if (legacyMenu.parentNode) {
        legacyMenu.parentNode.removeChild(legacyMenu);
      }
    }
    
    // Force remove any character selection menus that might be lingering
    const allMenus = document.querySelectorAll('div[style*="Select Character"], div[style*="Choose your character"]');
    allMenus.forEach((menu) => {
      // Check if parent has character selection styling
      const parent = menu.parentElement;
      if (parent && (parent.style.background?.includes('rgba(5, 7, 16') || parent.style.zIndex === '1003')) {
        parent.style.display = "none";
        parent.style.pointerEvents = "none";
        parent.style.zIndex = "-1";
        if (parent.parentNode) {
          parent.parentNode.removeChild(parent);
        }
        console.log("🧹 [GUI] Removed lingering character selection menu");
      }
    });
  }
  
  // ========================================
  // HELPER METHODS FOR COMPLETION SCREENS
  // ========================================
  
  /**
   * Create a completion panel (shared structure)
   * @private
   */
  _createCompletionPanel(titleText, subtitleText, messageText) {
    const panel = document.createElement("div");
    Object.assign(panel.style, {
      background: "linear-gradient(135deg, rgba(30, 41, 59, 0.98), rgba(17, 24, 39, 0.98))",
      border: "2px solid rgba(255, 224, 102, 0.5)", borderRadius: "16px", padding: "40px 48px",
      boxShadow: "0 20px 60px rgba(0, 0, 0, 0.6), 0 0 40px rgba(255, 224, 102, 0.3)",
      display: "flex", flexDirection: "column", alignItems: "center", minWidth: "320px",
      maxWidth: "90vw", textAlign: "center"
    });
    
    const title = document.createElement("div");
    title.textContent = titleText;
    Object.assign(title.style, {
      fontSize: "clamp(24px, 5vw, 32px)", fontWeight: "700", color: "#ffe066", marginBottom: "12px",
      textShadow: "0 0 20px rgba(255, 224, 102, 0.6)"
    });
    panel.appendChild(title);
    
    if (subtitleText) {
      const subtitle = document.createElement("div");
      subtitle.textContent = subtitleText;
      Object.assign(subtitle.style, {
        fontSize: "clamp(14px, 3vw, 18px)", color: "#cbd5f5", marginBottom: subtitleText ? "32px" : "0"
      });
      panel.appendChild(subtitle);
    }
    
    if (messageText) {
      const message = document.createElement("div");
      message.textContent = messageText;
      Object.assign(message.style, {
        fontSize: "clamp(14px, 2.5vw, 16px)", color: "#e2e8f0", marginBottom: "32px",
        lineHeight: "1.6", maxWidth: "500px"
      });
      panel.appendChild(message);
    }
    
    return panel;
  }
  
  /**
   * Create a completion button (shared styling)
   * @private
   */
  _createCompletionButton(text, onClick, isPrimary = false, customStyles = {}) {
    const btn = document.createElement("button");
    btn.textContent = text;
    btn.style.whiteSpace = "pre-line"; // Allow line breaks in button text
    
    const defaultStyles = {
      borderColor: isPrimary ? "rgba(255, 224, 102, 0.5)" : "rgba(203, 213, 245, 0.4)",
      bgColor: isPrimary ? "rgba(255, 224, 102, 0.15)" : "rgba(203, 213, 245, 0.1)",
      primaryGradient: null
    };
    
    const styles = { ...defaultStyles, ...customStyles };
    
    Object.assign(btn.style, {
      padding: "14px 24px", borderRadius: "10px", border: `2px solid ${styles.borderColor}`,
      background: isPrimary && styles.primaryGradient ? styles.primaryGradient : styles.bgColor,
      color: isPrimary ? "#ffe066" : "#cbd5f5",
      fontFamily: "Montserrat, Arial, sans-serif", fontSize: "clamp(14px, 2.5vw, 16px)",
      fontWeight: "600", cursor: "pointer", transition: "all 0.3s",
      textShadow: isPrimary ? "0 0 10px rgba(255, 224, 102, 0.5)" : "none"
    });
    
    btn.addEventListener("mouseenter", () => {
      btn.style.background = isPrimary ? "rgba(255, 224, 102, 0.3)" : "rgba(203, 213, 245, 0.2)";
      btn.style.borderColor = isPrimary ? "rgba(255, 224, 102, 0.8)" : "rgba(203, 213, 245, 0.6)";
      btn.style.transform = "scale(1.05)";
    });
    
    btn.addEventListener("mouseleave", () => {
      btn.style.background = styles.bgColor;
      btn.style.borderColor = styles.borderColor;
      btn.style.transform = "scale(1)";
    });
    
    btn.addEventListener("click", onClick);
    
    return btn;
  }
  
  // ========================================
  // LEVEL 4 HUD (Progress HUD and Hit Indicator)
  // ========================================
  
  /**
   * Create Level 4 progress HUD
   */
  createLevel4ProgressHUD() {
    if (this.level4ProgressHUD && document.body.contains(this.level4ProgressHUD)) {
      return; // Already exists
    }
    
    this.level4ProgressHUD = document.createElement("div");
    this.level4ProgressHUD.id = "level4ProgressHUD";
    Object.assign(this.level4ProgressHUD.style, {
      position: "fixed", top: "70px", left: "20px", // Moved to top-left (below FPS counter) to avoid blocking center view (January 4, 2026)
      background: "rgba(5, 7, 16, 0.85)", border: "2px solid rgba(139, 92, 246, 0.5)", borderRadius: "12px",
      padding: "10px 16px", zIndex: "1000", color: "#fef3c7", fontFamily: "Montserrat, Arial, sans-serif",
      fontSize: "14px", fontWeight: "600", textAlign: "left", boxShadow: "0 4px 16px rgba(0, 0, 0, 0.5)",
      maxWidth: "320px", // Limit width to prevent it from being too wide
      display: "none" // Hidden until Step 1 starts
    });
    this.container.appendChild(this.level4ProgressHUD);
    this.updateLevel4ProgressHUD();
  }
  
  /**
   * Update Level 4 progress HUD
   */
  updateLevel4ProgressHUD() {
    // CRITICAL: Only show Level 4 HUD when actually in Level 4
    const currentLevel = this.config.getCurrentLevel ? this.config.getCurrentLevel() : null;
    const LEVEL_IDS = this.config.LEVEL_IDS || {};
    
    if (currentLevel !== LEVEL_IDS.LEVEL4) {
      // Hide HUD if not in Level 4
      if (this.level4ProgressHUD) {
        this.level4ProgressHUD.style.display = "none";
      }
      return;
    }
    
    if (!this.level4ProgressHUD) {
      this.createLevel4ProgressHUD();
    }
    
    const level4RiddleState = this.config.getLevel4RiddleState();
    const level4State = this.config.getLevel4State();
    const level4WeaponSlots = this.config.getLevel4WeaponSlots();
    
    if (!level4RiddleState || !level4State || !level4WeaponSlots) {
      if (this.level4ProgressHUD) {
        this.level4ProgressHUD.style.display = "none";
      }
      return;
    }
    
    // Calculate heat percentage and status
    const heatPercentage = Math.round((level4State.weaponHeat / level4State.maxHeat) * 100);
    const heatColor = level4State.isOverheated 
      ? "#ef4444" 
      : heatPercentage >= 80 ? "#f59e0b" 
      : heatPercentage >= 50 ? "#eab308" 
      : "#22c55e";
    const heatStatus = level4State.isOverheated ? "OVERHEATED!" 
      : heatPercentage >= 80 ? "CRITICAL" 
      : heatPercentage >= 50 ? "WARNING" 
      : "NORMAL";
    
    // Weapon slot information
    const currentSlot = level4State.currentWeaponSlot;
    const weaponInfo = level4WeaponSlots[currentSlot];
    const weaponName = weaponInfo ? weaponInfo.name : "Unknown";
    
    if (level4RiddleState.step1Active) {
      // Cheese waves progress
      const LEVEL4_CHEESES_TO_CATCH = this.config.getLevel4CheesesToCatch ? this.config.getLevel4CheesesToCatch() : 50;
      const LEVEL4_WAVES_COUNT = this.config.getLevel4WavesCount ? this.config.getLevel4WavesCount() : 9;
      const LEVEL4_CHEESES_PER_WAVE = this.config.getLevel4CheesesPerWave ? this.config.getLevel4CheesesPerWave() : 5;
      const LEVEL4_FINAL_WAVE_CHEESES = this.config.getLevel4FinalWaveCheeses ? this.config.getLevel4FinalWaveCheeses() : 5;
      
      const progress = level4RiddleState.cheesesCaught;
      const total = LEVEL4_CHEESES_TO_CATCH;
      const percentage = Math.floor((progress / total) * 100);
      
      const isFinalWave = level4RiddleState.currentWave > LEVEL4_WAVES_COUNT;
      const waveText = isFinalWave ? "FINAL WAVE" : `Wave ${level4RiddleState.currentWave}/${LEVEL4_WAVES_COUNT + 1}`;
      const cheesesInWave = level4RiddleState.cheesesInCurrentWave;
      const cheesesNeededForWave = isFinalWave ? LEVEL4_FINAL_WAVE_CHEESES : LEVEL4_CHEESES_PER_WAVE;
      
      // Get wave color (via callback)
      const calculateWaveDifficulty = this.config.calculateLevel4WaveDifficulty;
      let difficulty = { color: new THREE.Color(0xffffff), colorName: 'white' };
      if (calculateWaveDifficulty && typeof calculateWaveDifficulty === 'function') {
        try {
          difficulty = calculateWaveDifficulty(level4RiddleState.currentWave);
          // Ensure difficulty has color property
          if (!difficulty.color || !(difficulty.color instanceof THREE.Color)) {
            difficulty.color = new THREE.Color(0xffffff);
          }
        } catch (error) {
          console.warn('⚠️ [GUI] Error in calculateLevel4WaveDifficulty:', error);
          difficulty = { color: new THREE.Color(0xffffff), colorName: 'white' };
        }
      }
      const colorHex = `#${difficulty.color.getHexString()}`;
      
      this.level4ProgressHUD.innerHTML = `
        <div style="font-size: 13px; color: ${colorHex}; margin-bottom: 3px; font-weight: bold;">${waveText}</div>
        <div style="font-size: 11px; color: rgba(255,255,255,0.8); margin-bottom: 3px;">Wave Progress: ${cheesesInWave}/${cheesesNeededForWave}</div>
        <div style="font-size: 14px; margin-bottom: 5px; font-weight: 600;">🔫 Cheeses Shot: ${progress}/${total} (${percentage}%)</div>
        <div style="font-size: 12px; color: rgba(255,224,102,0.9); border-top: 1px solid rgba(255,224,102,0.3); padding-top: 6px; margin-top: 6px;">
          <div style="margin-bottom: 2px;">Weapon: <span style="color: #ffe066; font-weight: bold;">${weaponName}</span> (Slot ${currentSlot})</div>
          <div style="font-size: 10px; color: rgba(255,255,255,0.6); margin-bottom: 4px;">Press number keys (1-9) to switch weapons</div>
          <div style="margin-top: 4px; padding-top: 4px; border-top: 1px solid rgba(255,255,255,0.2);">
            <div style="font-size: 11px; color: ${heatColor}; font-weight: bold; margin-bottom: 2px;">
              🔥 Heat: ${Math.round(level4State.weaponHeat)}/${level4State.maxHeat} (${heatPercentage}%) - ${heatStatus}
            </div>
            <div style="width: 100%; height: 4px; background: rgba(0,0,0,0.3); border-radius: 2px; overflow: hidden; margin-top: 2px;">
              <div style="width: ${heatPercentage}%; height: 100%; background: ${heatColor}; transition: width 0.1s ease;"></div>
            </div>
          </div>
        </div>
      `;
      this.level4ProgressHUD.style.display = "block";
    } else if (level4RiddleState.step2Active) {
      // Monster waves progress
      const LEVEL4_TOTAL_MONSTERS = this.config.getLevel4TotalMonsters ? this.config.getLevel4TotalMonsters() : 15;
      const LEVEL4_MONSTER_WAVES_COUNT = this.config.getLevel4MonsterWavesCount ? this.config.getLevel4MonsterWavesCount() : 4;
      const LEVEL4_MONSTERS_PER_WAVE = this.config.getLevel4MonstersPerWave ? this.config.getLevel4MonstersPerWave() : 3;
      
      const progress = level4RiddleState.monstersDefeated;
      const total = LEVEL4_TOTAL_MONSTERS;
      const percentage = Math.floor((progress / total) * 100);
      
      const isFinalWave = level4RiddleState.currentMonsterWave > LEVEL4_MONSTER_WAVES_COUNT;
      const waveText = isFinalWave ? "FINAL BOSS WAVE" : `Monster Wave ${level4RiddleState.currentMonsterWave}/${LEVEL4_MONSTER_WAVES_COUNT + 1}`;
      const monstersInWave = level4RiddleState.monstersInCurrentWave;
      const monstersNeededForWave = LEVEL4_MONSTERS_PER_WAVE;
      
      const colorHex = isFinalWave ? "#ff0000" : "#ff3300";
      
      this.level4ProgressHUD.innerHTML = `
        <div style="font-size: 13px; color: ${colorHex}; margin-bottom: 3px; font-weight: bold;">${waveText}</div>
        <div style="font-size: 11px; color: rgba(255,255,255,0.8); margin-bottom: 3px;">Wave Progress: ${monstersInWave}/${monstersNeededForWave}</div>
        <div style="font-size: 14px; margin-bottom: 5px; font-weight: 600;">🐉 Monsters Defeated: ${progress}/${total} (${percentage}%)</div>
        <div style="font-size: 12px; color: rgba(255,224,102,0.9); border-top: 1px solid rgba(255,224,102,0.3); padding-top: 6px; margin-top: 6px;">
          <div style="margin-bottom: 2px;">Weapon: <span style="color: #ffe066; font-weight: bold;">${weaponName}</span> (Slot ${currentSlot})</div>
          <div style="font-size: 10px; color: rgba(255,255,255,0.6); margin-bottom: 4px;">Press number keys (1-9) to switch weapons</div>
          <div style="margin-top: 4px; padding-top: 4px; border-top: 1px solid rgba(255,255,255,0.2);">
            <div style="font-size: 11px; color: ${heatColor}; font-weight: bold; margin-bottom: 2px;">
              🔥 Heat: ${Math.round(level4State.weaponHeat)}/${level4State.maxHeat} (${heatPercentage}%) - ${heatStatus}
            </div>
            <div style="width: 100%; height: 4px; background: rgba(0,0,0,0.3); border-radius: 2px; overflow: hidden; margin-top: 2px;">
              <div style="width: ${heatPercentage}%; height: 100%; background: ${heatColor}; transition: width 0.1s ease;"></div>
            </div>
          </div>
        </div>
      `;
      this.level4ProgressHUD.style.display = "block";
    } else {
      this.level4ProgressHUD.style.display = "none";
    }
  }
  
  /**
   * Create Level 4 hit indicator
   */
  createLevel4HitIndicator() {
    if (this.level4HitIndicator) return this.level4HitIndicator;
    
    this.level4HitIndicator = document.createElement("div");
    this.level4HitIndicator.id = "level4HitIndicator";
    Object.assign(this.level4HitIndicator.style, {
      position: "fixed", top: "50%", left: "50%", transform: "translate(-50%, -50%)",
      width: "100vw", height: "100vh", pointerEvents: "none", zIndex: "9999", opacity: "0",
      transition: "opacity 0.1s ease-out",
      background: "radial-gradient(circle, rgba(255, 224, 102, 0.5) 0%, rgba(255, 165, 0, 0.3) 50%, transparent 100%)",
      display: "none"
    });
    this.container.appendChild(this.level4HitIndicator);
    return this.level4HitIndicator;
  }
  
  /**
   * Show Level 4 hit indicator
   */
  showLevel4HitIndicator() {
    // CRITICAL: Only show Level 4 hit indicator when actually in Level 4
    const currentLevel = this.config.getCurrentLevel ? this.config.getCurrentLevel() : null;
    const LEVEL_IDS = this.config.LEVEL_IDS || {};
    
    if (currentLevel !== LEVEL_IDS.LEVEL4) {
      return; // Don't show hit indicator if not in Level 4
    }
    
    const indicator = this.createLevel4HitIndicator();
    indicator.style.display = "block";
    indicator.style.opacity = "0.7";
    
    const LEVEL4_HIT_INDICATOR_DURATION = this.config.getLevel4HitIndicatorDuration ? this.config.getLevel4HitIndicatorDuration() : 0.3;
    this.level4HitIndicatorTimer = LEVEL4_HIT_INDICATOR_DURATION;
    
    // Auto-fade out after duration
    setTimeout(() => {
      if (indicator && document.body.contains(indicator)) {
        indicator.style.opacity = "0";
        setTimeout(() => {
          if (indicator && document.body.contains(indicator)) {
            indicator.style.display = "none";
          }
        }, 100);
      }
    }, LEVEL4_HIT_INDICATOR_DURATION * 1000);
  }
  
  /**
   * Update Level 4 hit indicator
   */
  updateLevel4HitIndicator(delta) {
    if (this.level4HitIndicatorTimer > 0) {
      this.level4HitIndicatorTimer -= delta;
      if (this.level4HitIndicatorTimer <= 0 && this.level4HitIndicator) {
        this.level4HitIndicator.style.opacity = "0";
        setTimeout(() => {
          if (this.level4HitIndicator && document.body.contains(this.level4HitIndicator)) {
            this.level4HitIndicator.style.display = "none";
          }
        }, 100);
      }
    }
  }
  
  // ========================================
  // LEVEL 2 HUD (Inspection HUD)
  // ========================================
  
  /**
   * Ensure Level 2 inspection HUD exists
   */
  ensureLevel2InspectionHud() {
    if (this.level2InspectionHud) return this.level2InspectionHud;
    
    this.level2InspectionHud = document.createElement("div");
    this.level2InspectionHud.id = "level2InspectionHud";
    Object.assign(this.level2InspectionHud.style, {
      position: "fixed", top: "28px", left: "50%", transform: "translateX(-50%)",
      minWidth: "320px", maxWidth: "80vw", padding: "14px 20px", borderRadius: "12px",
      background: "rgba(15, 23, 42, 0.92)", border: "1px solid rgba(255, 224, 102, 0.4)",
      color: "#ffe066", fontFamily: "Montserrat, Arial, sans-serif", fontSize: "14px",
      boxShadow: "0 12px 30px rgba(0, 0, 0, 0.35)", zIndex: "100002", display: "none",
      flexDirection: "column", gap: "6px", textAlign: "center"
    });
    
    const title = document.createElement("div");
    title.id = "level2InspectionHudTitle";
    title.style.fontWeight = "700";
    title.style.letterSpacing = "0.5px";
    this.level2InspectionHud.appendChild(title);
    
    const progressLine = document.createElement("div");
    progressLine.id = "level2InspectionHudProgress";
    progressLine.style.fontSize = "13px";
    progressLine.style.color = "#fef9c3";
    this.level2InspectionHud.appendChild(progressLine);
    
    const remainingLine = document.createElement("div");
    remainingLine.id = "level2InspectionHudRemaining";
    remainingLine.style.fontSize = "12px";
    remainingLine.style.color = "rgba(255, 224, 102, 0.85)";
    this.level2InspectionHud.appendChild(remainingLine);
    
    this.container.appendChild(this.level2InspectionHud);
    return this.level2InspectionHud;
  }
  
  /**
   * Update Level 2 inspection HUD
   */
  updateLevel2InspectionHud() {
    // CRITICAL: Only show Level 2 HUD when actually in Level 2
    const currentLevel = this.config.getCurrentLevel ? this.config.getCurrentLevel() : null;
    const LEVEL_IDS = this.config.LEVEL_IDS || {};
    
    if (currentLevel !== LEVEL_IDS.LEVEL2) {
      // Hide HUD if not in Level 2
      this.hideLevel2InspectionHud();
      return;
    }
    
    const level2RiddleState = this.config.getLevel2RiddleState();
    const level2State = this.config.getLevel2State();
    
    if (!level2RiddleState || !level2State) {
      this.hideLevel2InspectionHud();
      return;
    }
    
    if (!level2RiddleState.step0Complete || !level2RiddleState.leverPressed) {
      this.hideLevel2InspectionHud();
      return;
    }
    if (level2State.portalActive || level2State.completionScreenShown) {
      this.hideLevel2InspectionHud();
      return;
    }
    
    const totalZones = level2State.inspectionZones.length;
    if (!totalZones) {
      this.hideLevel2InspectionHud();
      return;
    }
    
    const visitedCount = level2State.inspectionZones.filter((zone) => zone.visited).length;
    const remainingLabels = level2State.inspectionZones
      .filter((zone) => !zone.visited)
      .map((zone) => zone.label);
    
    const hud = this.ensureLevel2InspectionHud();
    const title = hud.querySelector("#level2InspectionHudTitle");
    const progress = hud.querySelector("#level2InspectionHudProgress");
    const remaining = hud.querySelector("#level2InspectionHudRemaining");
    
    if (title) {
      title.textContent = "Step 2 — Inspect Every Display";
    }
    if (progress) {
      progress.textContent = `${visitedCount} / ${totalZones} galleries logged`;
    }
    if (remaining) {
      remaining.textContent = remainingLabels.length === 0
        ? "All aisles documented — portal manifesting..."
        : `Still missing: ${remainingLabels.join(", ")}`;
    }
    hud.style.display = "flex";
  }
  
  /**
   * Hide Level 2 inspection HUD
   */
  hideLevel2InspectionHud(removeFromDom = false) {
    if (!this.level2InspectionHud) return;
    if (removeFromDom && document.body.contains(this.level2InspectionHud)) {
      document.body.removeChild(this.level2InspectionHud);
      this.level2InspectionHud = null;
      return;
    }
    this.level2InspectionHud.style.display = "none";
  }
  
  // ========================================
  // CLEANUP
  // ========================================
  
  /**
   * Dispose of all GUI elements
   */
  /**
   * Create loading screen UI elements
   */
  createLoadingScreen() {
    // Remove existing loading screen if it exists
    const existing = document.getElementById('loadingScreen');
    if (existing) {
      existing.remove();
    }
    
    // Create loading screen container
    const loadingScreen = document.createElement('div');
    loadingScreen.id = 'loadingScreen';
    loadingScreen.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: url('/textures/backgrounds/cheesetemple1.png');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-color: rgba(0, 0, 0, 0.7);
      display: none;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      z-index: 999999;
      font-family: 'Arial', sans-serif;
      color: #fff;
    `;
    
    // Create spinner
    const spinner = document.createElement('div');
    spinner.style.cssText = `
      width: 60px;
      height: 60px;
      border: 4px solid rgba(255, 215, 0, 0.3);
      border-top: 4px solid #ffd700;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-bottom: 20px;
    `;
    
    // Add spinner animation
    if (!document.getElementById('loadingSpinnerStyle')) {
      const style = document.createElement('style');
      style.id = 'loadingSpinnerStyle';
      style.textContent = `
        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
      `;
      document.head.appendChild(style);
    }
    
    // Create loading message
    const message = document.createElement('div');
    message.id = 'loadingMessage';
    message.style.cssText = `
      font-size: 24px;
      font-weight: bold;
      color: #ffd700;
      margin-bottom: 20px;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
    `;
    message.textContent = 'Loading...';
    
    // Create progress bar container
    const progressContainer = document.createElement('div');
    progressContainer.style.cssText = `
      width: 400px;
      height: 30px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 15px;
      overflow: hidden;
      margin-bottom: 10px;
      border: 2px solid rgba(255, 215, 0, 0.3);
    `;
    
    // Create progress bar
    const progressBar = document.createElement('div');
    progressBar.id = 'loadingProgressBar';
    progressBar.style.cssText = `
      width: 0%;
      height: 100%;
      background: linear-gradient(90deg, #ffd700, #ffed4e);
      transition: width 0.3s ease;
      box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
    `;
    
    // Create progress percentage text
    const progressText = document.createElement('div');
    progressText.id = 'loadingProgressText';
    progressText.style.cssText = `
      font-size: 18px;
      color: #ffd700;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
    `;
    progressText.textContent = '0%';
    
    // Assemble loading screen
    progressContainer.appendChild(progressBar);
    loadingScreen.appendChild(spinner);
    loadingScreen.appendChild(message);
    loadingScreen.appendChild(progressContainer);
    loadingScreen.appendChild(progressText);
    
    document.body.appendChild(loadingScreen);
    
    this.loadingScreen = loadingScreen;
    this.loadingMessage = message;
    this.loadingProgressBar = progressBar;
    this.loadingProgressText = progressText;
  }
  
  /**
   * Show loading screen
   * @param {string} message - Loading message (e.g., "Loading Level 5...")
   */
  showLoadingScreen(message = 'Loading...') {
    if (!this.loadingScreen) {
      this.createLoadingScreen();
    }
    
    if (this.loadingMessage) {
      this.loadingMessage.textContent = message;
    }
    
    if (this.loadingProgressBar) {
      this.loadingProgressBar.style.width = '0%';
    }
    
    if (this.loadingProgressText) {
      this.loadingProgressText.textContent = '0%';
    }
    
    this.loadingScreen.style.display = 'flex';
  }
  
  /**
   * Update loading progress
   * @param {number} percent - Progress percentage (0-100)
   */
  updateLoadingProgress(percent) {
    if (!this.loadingScreen || !this.loadingProgressBar || !this.loadingProgressText) {
      return;
    }
    
    const clampedPercent = Math.max(0, Math.min(100, percent));
    this.loadingProgressBar.style.width = `${clampedPercent}%`;
    this.loadingProgressText.textContent = `${Math.round(clampedPercent)}%`;
  }
  
  /**
   * Hide loading screen
   */
  hideLoadingScreen() {
    if (this.loadingScreen) {
      this.loadingScreen.style.display = 'none';
    }
  }
  
  dispose() {
    // Remove all DOM elements
    const elements = [
      this.scoreHud,
      this.debugOverlay,
      this.crosshair,
      this.pauseMenu,
      this.optionsMenu,
      this.characterSelectionMenu,
      this.levelSelectorScreen,
      this.level3GameOverScreen,
      this.level1CompletionScreen,
      this.level2CompletionScreen,
      this.level3CompletionScreen,
      this.level4CompletionScreen,
      this.level2InspectionHud,
      this.level4ProgressHUD,
      this.level4HitIndicator,
      this.madModeNotification
    ];
    
    elements.forEach(element => {
      if (element && document.body.contains(element)) {
        document.body.removeChild(element);
      }
    });
    
    // Remove loading screen
    if (this.loadingScreen && document.body.contains(this.loadingScreen)) {
      document.body.removeChild(this.loadingScreen);
    }
    
    // Clear toast map
    this.activeRiddleToasts.clear();
    
    // Reset state
    this.scoreHud = null;
    this.debugOverlay = null;
    this.crosshair = null;
    this.pauseMenu = null;
    this.optionsMenu = null;
    this.characterSelectionMenu = null;
    this.levelSelectorScreen = null;
    this.level3GameOverScreen = null;
    this.level1CompletionScreen = null;
    this.level2CompletionScreen = null;
    this.level3CompletionScreen = null;
    this.level4CompletionScreen = null;
    this.level2InspectionHud = null;
    this.level4ProgressHUD = null;
    this.level4HitIndicator = null;
    this.level4HitIndicatorTimer = 0;
    this.madModeNotification = null;
    this.loadingScreen = null;
    this.loadingMessage = null;
    this.loadingProgressBar = null;
    this.loadingProgressText = null;
    
    this.isInitialized = false;
  }
}

