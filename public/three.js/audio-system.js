/**
 * ============================================================================
 * AUDIO SYSTEM - Complete Audio Management
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
 * This module contains ALL audio functionality extracted from main.js
 * to reduce main.js size (~200+ lines removed) and improve maintainability.
 * 
 * All audio logic is centralized here - main.js only has wrapper functions
 * that delegate to AudioSystem for backward compatibility.
 * 
 * ============================================================================
 * ✅ STABLE VERSION STATUS
 * ============================================================================
 * 
 * ✅ **STABLE VERSION - PRODUCTION READY**
 * 
 * This system has reached a stable, production-ready state with:
 * - ✅ All audio logic modularized (no audio code in main.js)
 * - ✅ Background music management (per-level)
 * - ✅ Sound effects (footsteps, jumps, gameplay sounds)
 * - ✅ Audio state management (enabled/disabled, volume)
 * - ✅ UI control updates (buttons properly enabled)
 * - ✅ Settings persistence (localStorage)
 * - ✅ Wrapper functions maintain backward compatibility
 * - ✅ Legacy variables synced after initialization
 * 
 * ============================================================================
 * 📦 WHAT THIS MODULE LOADS
 * ============================================================================
 * 
 * Audio Files:
 * - Background music (per-level): /sounds/music/level1.mp3 through level6.mp3
 * - Character footsteps: /audio/character/footstep_cheese.ogg
 * - Character jump: /audio/character/jump_cheese.ogg
 * - Gameplay sounds: Various .ogg and .wav files
 * 
 * Dependencies:
 * - THREE.js AudioListener (from main.js)
 * - THREE.js AudioLoader (from main.js)
 * - config-system.js (for audio constants and paths)
 * 
 * ============================================================================
 * 🔧 INTEGRATION IN MAIN.JS
 * ============================================================================
 * 
 * 1. Import:
 *    import { AudioSystem } from "./audio-system.js";
 * 
 * 2. Initialize (in startGame() or after scene/camera ready):
 *    audioSystem = new AudioSystem({
 *      audioListener: audioListener,
 *      audioLoader: audioLoader,
 *      getCurrentLevel: () => currentLevel,
 *      getIsGamePaused: () => isGamePaused,
 *      getOptionsMenu: () => optionsMenu,
 *      getPlayerVelocity: () => playerVelocity,
 *      getOnGround: () => onGround
 *    });
 *    audioSystem.initialize();
 * 
 * 3. Wrapper functions in main.js delegate to AudioSystem:
 *    - loadCharacterAudio() → audioSystem.loadCharacterAudio()
 *    - playJumpSound() → audioSystem.playJumpSound()
 *    - loadBackgroundMusic() → audioSystem.loadBackgroundMusic()
 *    - etc.
 * 
 * ============================================================================
 * 🎮 KEY FUNCTIONS
 * ============================================================================
 * 
 * Background Music:
 * - loadBackgroundMusic(levelId) - Load music for specific level
 * - playBackgroundMusic(levelId) - Play music for level
 * - stopBackgroundMusic() - Stop current music
 * - pauseBackgroundMusic() - Pause current music
 * - resumeBackgroundMusic() - Resume paused music
 * - setBackgroundMusicEnabled(enabled) - Enable/disable music
 * - setBackgroundMusicVolume(volume) - Set volume (0.0-1.0)
 * 
 * Sound Effects:
 * - loadCharacterAudio() - Load character sound effects
 * - playJumpSound() - Play jump sound
 * - playFootstepSound() - Play footstep sound (auto-triggered)
 * - playCheesePlatformSound() - Play platform activation sound
 * - playLeverSound() - Play lever interaction sound
 * - playLevelUpSound() - Play level up sound
 * 
 * UI Updates:
 * - updateSoundFxButtons() - Update sound FX button states
 * - updateBackgroundMusicButtons() - Update music button states
 * - updateBackgroundMusicVolumeSlider() - Update volume slider
 * 
 * ============================================================================
 */

import * as THREE from "three";
import {
  SOUND_FX_STORAGE_KEY,
  BACKGROUND_MUSIC_STORAGE_KEY,
  BACKGROUND_MUSIC_VOLUME_STORAGE_KEY,
  BACKGROUND_MUSIC_PATHS,
  CHARACTER_FOOTSTEP_AUDIO,
  CHARACTER_JUMP_AUDIO,
  CHEESE_PLATFORM_AUDIO,
  CHEESE_AIM_CLEAR_AUDIO,
  LEVER_AUDIO,
  BLOCK_MOVED_AUDIO,
  LEVEL_UP_AUDIO,
  LEVEL4_SHOOT_AUDIO
} from "./config-system.js";

/**
 * AudioSystem - Complete audio management system
 */
export class AudioSystem {
  /**
   * @param {Object} options - Configuration options
   * @param {THREE.AudioListener} options.audioListener - THREE.js audio listener
   * @param {THREE.AudioLoader} options.audioLoader - THREE.js audio loader
   * @param {Function} options.getCurrentLevel - Getter for current level ID
   * @param {Function} options.getIsGamePaused - Getter for game pause state
   * @param {Function} options.getOptionsMenu - Getter for options menu object
   * @param {Function} options.getPlayerVelocity - Getter for player velocity (for footsteps)
   * @param {Function} options.getOnGround - Getter for on-ground state (for footsteps)
   */
  constructor(options) {
    const {
      audioListener,
      audioLoader,
      getCurrentLevel,
      getIsGamePaused,
      getOptionsMenu,
      getPlayerVelocity,
      getOnGround
    } = options;

    if (!audioListener || !audioLoader) {
      throw new Error("AudioSystem requires audioListener and audioLoader");
    }

    this.audioListener = audioListener;
    this.audioLoader = audioLoader;
    this.getCurrentLevel = getCurrentLevel || (() => null);
    this.getIsGamePaused = getIsGamePaused || (() => false);
    this.getOptionsMenu = getOptionsMenu || (() => null);
    this.getPlayerVelocity = getPlayerVelocity || (() => ({ x: 0, y: 0, z: 0 }));
    this.getOnGround = getOnGround || (() => false);

    // Sound FX State
    this.soundFxEnabled = true;
    this.loadSoundFxPreference();

    // Background Music State
    this.backgroundMusicEnabled = true;
    this.backgroundMusicVolume = 0.5; // Default 50% volume
    this.currentBackgroundMusic = null;
    this.backgroundMusicObjects = {}; // Store music objects for each level
    this.loadBackgroundMusicPreference();

    // Sound Effect Objects
    this.footstepSound = null;
    this.jumpSound = null;
    this.cheesePlatformSound = null;
    this.cheeseAimClearSound = null;
    this.leverSound = null;
    this.blockMovedSound = null;
    this.levelUpSound = null;
    this.level4ShootSound = null;
    this.level4SF13ShootSound = null;
    this.bearTrapSound = null;
    this.hiddenSleverSound = null;

    // Sound Effect Ready Flags
    this.footstepAudioReady = false;
    this.jumpAudioReady = false;
    this.cheesePlatformAudioReady = false;
    this.cheeseAimClearAudioReady = false;
    this.leverAudioReady = false;
    this.blockMovedAudioReady = false;
    this.levelUpAudioReady = false;
    this.level4ShootAudioReady = false;
    this.level4SF13ShootAudioReady = false;
    this.bearTrapAudioReady = false;
    this.hiddenSleverAudioReady = false;

    // Setup audio context resume listeners
    this.setupAudioContextResume();
  }

  /**
   * Load sound FX preference from localStorage
   */
  loadSoundFxPreference() {
    try {
      const savedSoundFx = localStorage.getItem(SOUND_FX_STORAGE_KEY);
      if (savedSoundFx !== null) {
        this.soundFxEnabled = savedSoundFx === "true";
      }
    } catch (error) {
      console.warn("⚠️ [AUDIO] Failed to load sound FX preference:", error);
    }
  }

  /**
   * Load background music preference from localStorage
   */
  loadBackgroundMusicPreference() {
    try {
      const savedMusicEnabled = localStorage.getItem(BACKGROUND_MUSIC_STORAGE_KEY);
      if (savedMusicEnabled !== null) {
        this.backgroundMusicEnabled = savedMusicEnabled === "true";
      }
      const savedMusicVolume = localStorage.getItem(BACKGROUND_MUSIC_VOLUME_STORAGE_KEY);
      if (savedMusicVolume !== null) {
        this.backgroundMusicVolume = parseFloat(savedMusicVolume);
        if (isNaN(this.backgroundMusicVolume) || this.backgroundMusicVolume < 0 || this.backgroundMusicVolume > 1) {
          this.backgroundMusicVolume = 0.5; // Reset to default if invalid
        }
      }
    } catch (error) {
      console.warn("⚠️ [AUDIO] Failed to load background music preference:", error);
    }
  }

  /**
   * Setup audio context resume listeners
   */
  setupAudioContextResume() {
    ["pointerdown", "touchstart", "keydown"].forEach((eventName) => {
      document.addEventListener(eventName, () => this.resumeAudioContextIfNeeded(), { passive: true });
    });
  }

  /**
   * Resume audio context if needed (required for autoplay policies)
   */
  resumeAudioContextIfNeeded() {
    if (!this.audioListener || !this.audioListener.context) {
      console.warn("⚠️ [AUDIO] Cannot resume audio context - audioListener or context not available");
      return;
    }
    const context = this.audioListener.context;
    if (context.state === "suspended") {
      console.log("🔊 [AUDIO] Resuming suspended audio context...");
      context.resume().then(() => {
        console.log("✅ [AUDIO] Audio context resumed successfully, state:", context.state);
      }).catch((error) => {
        console.warn("⚠️ [AUDIO] Failed to resume audio context:", error);
      });
    } else {
      // Log state for debugging (only first few times to avoid spam)
      if (!this._audioContextStateLogged) {
        console.log(`🔊 [AUDIO] Audio context state: ${context.state}`);
        this._audioContextStateLogged = true;
      }
    }
  }

  /**
   * Load all character and gameplay audio files
   */
  loadCharacterAudio() {
    console.log("🎵 [AUDIO] Starting to load character audio files...");
    
    // FIXED (January 6, 2026): Resolve all audio paths for production
    const resolvePath = window.resolveAssetPath || ((p) => p);
    
    // Footstep Sound
    this.audioLoader.load(
      resolvePath(CHARACTER_FOOTSTEP_AUDIO),
      (buffer) => {
        this.footstepSound = new THREE.Audio(this.audioListener);
        this.footstepSound.setBuffer(buffer);
        this.footstepSound.setLoop(true);
        this.footstepSound.setVolume(0.4);
        this.footstepSound.setPlaybackRate(1);
        this.footstepAudioReady = true;
        console.log("✅ [AUDIO] Footstep audio loaded:", CHARACTER_FOOTSTEP_AUDIO);
      },
      undefined,
      (error) => {
        console.error("❌ [AUDIO] Failed to load footstep audio:", CHARACTER_FOOTSTEP_AUDIO, error);
      }
    );

    // Jump Sound
    this.audioLoader.load(
      resolvePath(CHARACTER_JUMP_AUDIO),
      (buffer) => {
        this.jumpSound = new THREE.Audio(this.audioListener);
        this.jumpSound.setBuffer(buffer);
        this.jumpSound.setLoop(false);
        this.jumpSound.setVolume(0.55);
        this.jumpAudioReady = true;
        console.log("✅ [AUDIO] Jump audio loaded:", CHARACTER_JUMP_AUDIO);
      },
      undefined,
      (error) => {
        console.error("❌ [AUDIO] Failed to load jump audio:", CHARACTER_JUMP_AUDIO, error);
      }
    );

    // Cheese Platform Sound
    this.audioLoader.load(
      resolvePath(CHEESE_PLATFORM_AUDIO),
      (buffer) => {
        this.cheesePlatformSound = new THREE.Audio(this.audioListener);
        this.cheesePlatformSound.setBuffer(buffer);
        this.cheesePlatformSound.setLoop(false);
        this.cheesePlatformSound.setVolume(0.7);
        this.cheesePlatformAudioReady = true;
      },
      undefined,
      (error) => {
        console.error("❌ [AUDIO] Failed to load cheese platform audio:", error);
      }
    );

    // Cheese Aim Clear Sound
    this.audioLoader.load(
      resolvePath(CHEESE_AIM_CLEAR_AUDIO),
      (buffer) => {
        this.cheeseAimClearSound = new THREE.Audio(this.audioListener);
        this.cheeseAimClearSound.setBuffer(buffer);
        this.cheeseAimClearSound.setLoop(false);
        this.cheeseAimClearSound.setVolume(0.75);
        this.cheeseAimClearAudioReady = true;
      },
      undefined,
      (error) => {
        console.error("❌ [AUDIO] Failed to load cheese aim clear audio:", error);
      }
    );

    // Lever Sound
    this.audioLoader.load(
      resolvePath(LEVER_AUDIO),
      (buffer) => {
        this.leverSound = new THREE.Audio(this.audioListener);
        this.leverSound.setBuffer(buffer);
        this.leverSound.setLoop(false);
        this.leverSound.setVolume(0.6);
        this.leverAudioReady = true;
      },
      undefined,
      (error) => {
        console.error("❌ [AUDIO] Failed to load lever audio:", error);
      }
    );

    // Block Moved Sound
    this.audioLoader.load(
      resolvePath(BLOCK_MOVED_AUDIO),
      (buffer) => {
        this.blockMovedSound = new THREE.Audio(this.audioListener);
        this.blockMovedSound.setBuffer(buffer);
        this.blockMovedSound.setLoop(false);
        this.blockMovedSound.setVolume(0.7);
        this.blockMovedAudioReady = true;
      },
      undefined,
      (error) => {
        console.error("❌ [AUDIO] Failed to load block moved audio:", error);
      }
    );

    // Level Up Sound
    this.audioLoader.load(
      resolvePath(LEVEL_UP_AUDIO),
      (buffer) => {
        this.levelUpSound = new THREE.Audio(this.audioListener);
        this.levelUpSound.setBuffer(buffer);
        this.levelUpSound.setLoop(false);
        this.levelUpSound.setVolume(0.75);
        this.levelUpAudioReady = true;
      },
      undefined,
      (error) => {
        console.error("❌ [AUDIO] Failed to load level up audio:", error);
      }
    );

    // Level 4 Shooting Sound (Space Invaders normal_shoot.wav)
    this.audioLoader.load(
      resolvePath(LEVEL4_SHOOT_AUDIO),
      (buffer) => {
        this.level4ShootSound = new THREE.Audio(this.audioListener);
        this.level4ShootSound.setBuffer(buffer);
        this.level4ShootSound.setLoop(false);
        this.level4ShootSound.setVolume(0.6); // Slightly lower volume for shooting
        this.level4ShootAudioReady = true;
        console.log("✅ [AUDIO] Level 4 shooting sound loaded");
      },
      undefined,
      (error) => {
        console.error("❌ [AUDIO] Failed to load Level 4 shooting sound:", error);
      }
    );

    // SF13 Triple-Shot Sound (SF13-Gun-future.mp3)
    this.audioLoader.load(
      resolvePath("/public/sounds/SFX/SF13-Gun-future.mp3"),
      (buffer) => {
        this.level4SF13ShootSound = new THREE.Audio(this.audioListener);
        this.level4SF13ShootSound.setBuffer(buffer);
        this.level4SF13ShootSound.setLoop(false);
        this.level4SF13ShootSound.setVolume(0.6); // Same volume as normal shot
        this.level4SF13ShootAudioReady = true;
        console.log("✅ [AUDIO] SF13 triple-shot sound loaded");
      },
      undefined,
      (error) => {
        console.error("❌ [AUDIO] Failed to load SF13 shooting sound:", error);
      }
    );

    // Bear Trap Sound (bear-trap-103800.mp3)
    this.audioLoader.load(
      "/sounds/SFX/bear-trap-103800.mp3",
      (buffer) => {
        this.bearTrapSound = new THREE.Audio(this.audioListener);
        this.bearTrapSound.setBuffer(buffer);
        this.bearTrapSound.setLoop(false);
        this.bearTrapSound.setVolume(0.7); // Volume for trap closing sound
        this.bearTrapAudioReady = true;
        console.log("✅ [AUDIO] Bear trap sound loaded");
      },
      undefined,
      (error) => {
        console.error("❌ [AUDIO] Failed to load bear trap sound:", error);
      }
    );

    // Hidden Slever Riddle Sound (hidden-slever.mp3)
    this.audioLoader.load(
      "/sounds/SFX/hidden-slever.mp3",
      (buffer) => {
        this.hiddenSleverSound = new THREE.Audio(this.audioListener);
        this.hiddenSleverSound.setBuffer(buffer);
        this.hiddenSleverSound.setLoop(false);
        this.hiddenSleverSound.setVolume(0.8); // Volume for riddle solved sound
        this.hiddenSleverAudioReady = true;
        console.log("✅ [AUDIO] Hidden slever riddle sound loaded successfully");
      },
      undefined,
      (error) => {
        console.error("❌ [AUDIO] Failed to load hidden slever riddle sound:", error);
        console.error("❌ [AUDIO] Sound path attempted: /sounds/SFX/hidden-slever.mp3");
        this.hiddenSleverAudioReady = false;
      }
    );
  }

  // ============================================================================
  // FOOTSTEP SOUND MANAGEMENT
  // ============================================================================

  /**
   * Stop footstep sound
   */
  stopFootstepSound() {
    if (this.footstepSound && this.footstepSound.isPlaying) {
      this.footstepSound.stop();
    }
  }

  /**
   * Update footstep sound state based on player movement
   * Should be called in game update loop
   * 
   * BUG FIX (December 18, 2025): Stop footsteps when game is paused or options menu is open
   */
  updateFootstepSoundState() {
    if (!this.footstepAudioReady || !this.footstepSound) return;

    // Check if game is paused or options menu is open
    const isPaused = this.getIsGamePaused();
    const optionsMenu = this.getOptionsMenu();
    const isOptionsMenuOpen = optionsMenu && optionsMenu.style && optionsMenu.style.display === "flex";
    const isMenuOpen = isPaused || isOptionsMenuOpen || (typeof window !== "undefined" && window.optionsMenuOpen);

    // If menu is open, stop footsteps immediately
    if (isMenuOpen) {
      if (this.footstepSound.isPlaying) {
        this.stopFootstepSound();
      }
      return;
    }

    const playerVelocity = this.getPlayerVelocity();
    const horizontalSpeed = Math.sqrt(
      (playerVelocity?.x || 0) * (playerVelocity?.x || 0) +
      (playerVelocity?.z || 0) * (playerVelocity?.z || 0)
    );
    const shouldPlay = this.soundFxEnabled && !isPaused && this.getOnGround() && horizontalSpeed > 0.5;

    if (shouldPlay) {
      this.resumeAudioContextIfNeeded();
      if (!this.footstepSound.isPlaying) {
        this.footstepSound.play();
      }
      const playbackRate = THREE.MathUtils.clamp(0.5 + horizontalSpeed / 22, 0.5, 1.0);
      this.footstepSound.setPlaybackRate(playbackRate);
    } else if (this.footstepSound.isPlaying) {
      this.stopFootstepSound();
    }
  }

  // ============================================================================
  // SOUND EFFECT PLAYBACK
  // ============================================================================

  /**
   * Play jump sound
   */
  playJumpSound() {
    if (!this.soundFxEnabled || !this.jumpAudioReady || !this.jumpSound) {
      console.warn("⚠️ [AUDIO] Jump sound cannot play:", {
        soundFxEnabled: this.soundFxEnabled,
        jumpAudioReady: this.jumpAudioReady,
        hasJumpSound: !!this.jumpSound
      });
      return;
    }
    this.resumeAudioContextIfNeeded();
    if (this.jumpSound.isPlaying) {
      this.jumpSound.stop();
    }
    this.jumpSound.play();
    console.log("🔊 [AUDIO] Jump sound played");
  }

  /**
   * Play cheese platform sound
   */
  playCheesePlatformSound() {
    if (!this.soundFxEnabled || !this.cheesePlatformAudioReady || !this.cheesePlatformSound) return;
    this.resumeAudioContextIfNeeded();
    if (this.cheesePlatformSound.isPlaying) {
      this.cheesePlatformSound.stop();
    }
    this.cheesePlatformSound.play();
  }

  /**
   * Play cheese aim clear sound
   */
  playCheeseAimClearSound() {
    if (!this.soundFxEnabled || !this.cheeseAimClearAudioReady || !this.cheeseAimClearSound) return;
    this.resumeAudioContextIfNeeded();
    if (this.cheeseAimClearSound.isPlaying) {
      this.cheeseAimClearSound.stop();
    }
    this.cheeseAimClearSound.play();
  }

  /**
   * Play lever sound
   */
  playLeverSound() {
    if (!this.soundFxEnabled || !this.leverAudioReady || !this.leverSound) return;
    this.resumeAudioContextIfNeeded();
    if (this.leverSound.isPlaying) {
      this.leverSound.stop();
    }
    this.leverSound.play();
  }

  /**
   * Play hidden slever sound
   */
  playHiddenSleverSound() {
    if (!this.soundFxEnabled || !this.hiddenSleverAudioReady || !this.hiddenSleverSound) {
      console.warn("⚠️ [AUDIO] Hidden slever sound cannot play:", {
        soundFxEnabled: this.soundFxEnabled,
        hiddenSleverAudioReady: this.hiddenSleverAudioReady,
        hasSound: !!this.hiddenSleverSound
      });
      return;
    }
    this.resumeAudioContextIfNeeded();
    if (this.hiddenSleverSound.isPlaying) {
      this.hiddenSleverSound.stop();
    }
    this.hiddenSleverSound.play();
    console.log("🔊 [AUDIO] Hidden slever riddle sound played");
  }

  /**
   * Play level up sound
   */
  playLevelUpSound() {
    if (!this.soundFxEnabled || !this.levelUpAudioReady || !this.levelUpSound) return;
    this.resumeAudioContextIfNeeded();
    if (this.levelUpSound.isPlaying) {
      this.levelUpSound.stop();
    }
    this.levelUpSound.play();
  }

  /**
   * Play block moved sound
   */
  playBlockMovedSound() {
    if (!this.soundFxEnabled || !this.blockMovedAudioReady || !this.blockMovedSound) return;
    this.resumeAudioContextIfNeeded();
    if (this.blockMovedSound.isPlaying) {
      this.blockMovedSound.stop();
    }
    this.blockMovedSound.play();
  }

  /**
   * Play Level 4 shooting sound (Space Invaders normal_shoot.wav)
   */
  playLevel4ShootSound() {
    if (!this.soundFxEnabled || !this.level4ShootAudioReady || !this.level4ShootSound) {
      console.warn("⚠️ [AUDIO] Level 4 shoot sound cannot play:", {
        soundFxEnabled: this.soundFxEnabled,
        level4ShootAudioReady: this.level4ShootAudioReady,
        hasSound: !!this.level4ShootSound
      });
      return;
    }
    this.resumeAudioContextIfNeeded();
    // Stop any currently playing sound and restart it (for rapid firing)
    if (this.level4ShootSound.isPlaying) {
      this.level4ShootSound.stop();
    }
    this.level4ShootSound.play();
    console.log("🔫 [AUDIO] Level 4 weapon 1 shot sound played");
  }

  /**
   * Play bear trap closing sound
   */
  playBearTrapSound() {
    if (!this.soundFxEnabled || !this.bearTrapAudioReady || !this.bearTrapSound) {
      console.warn("⚠️ [AUDIO] Bear trap sound cannot play:", {
        soundFxEnabled: this.soundFxEnabled,
        bearTrapAudioReady: this.bearTrapAudioReady,
        hasSound: !!this.bearTrapSound
      });
      return;
    }
    this.resumeAudioContextIfNeeded();
    if (this.bearTrapSound.isPlaying) {
      this.bearTrapSound.stop();
    }
    this.bearTrapSound.play();
    console.log("🔊 [AUDIO] Bear trap sound played");
  }

  /**
   * Play SF13 triple-shot sound (Level 4 weapon slot 2)
   */
  playSF13ShootSound() {
    if (!this.soundFxEnabled || !this.level4SF13ShootAudioReady || !this.level4SF13ShootSound) {
      console.warn("⚠️ [AUDIO] SF13 shoot sound cannot play:", {
        soundFxEnabled: this.soundFxEnabled,
        level4SF13ShootAudioReady: this.level4SF13ShootAudioReady,
        hasSound: !!this.level4SF13ShootSound
      });
      return;
    }
    this.resumeAudioContextIfNeeded();
    // Stop any currently playing sound and restart it (for rapid firing)
    if (this.level4SF13ShootSound.isPlaying) {
      this.level4SF13ShootSound.stop();
    }
    this.level4SF13ShootSound.play();
    console.log("🔫 [AUDIO] SF13 triple-shot sound played");
  }

  // ============================================================================
  // SOUND FX STATE MANAGEMENT
  // ============================================================================

  /**
   * Set sound FX enabled state
   * @param {boolean} enabled - Whether sound FX should be enabled
   */
  setSoundFxEnabled(enabled) {
    this.soundFxEnabled = enabled;
    if (!enabled) {
      this.stopFootstepSound();
    }
    try {
      localStorage.setItem(SOUND_FX_STORAGE_KEY, enabled ? "true" : "false");
    } catch (error) {
      console.warn("⚠️ [AUDIO] Failed to save sound FX preference:", error);
    }
    this.updateSoundFxButtons();
  }

  /**
   * Get sound FX enabled state
   * @returns {boolean} Whether sound FX is enabled
   */
  getSoundFxEnabled() {
    return this.soundFxEnabled;
  }

  /**
   * Update sound FX buttons in options menu
   */
  updateSoundFxButtons() {
    const optionsMenu = this.getOptionsMenu();
    if (!optionsMenu) return;
    const offBtn = optionsMenu._soundFxOffBtn;
    const onBtn = optionsMenu._soundFxOnBtn;
    if (offBtn && onBtn) {
      // Enable buttons and make them visible
      offBtn.disabled = false;
      offBtn.style.opacity = "1";
      offBtn.style.cursor = "pointer";
      onBtn.disabled = false;
      onBtn.style.opacity = "1";
      onBtn.style.cursor = "pointer";
      
      // Update styling
      offBtn.style.background = !this.soundFxEnabled ? "rgba(255, 224, 102, 0.3)" : "rgba(255, 255, 255, 0.1)";
      offBtn.style.color = !this.soundFxEnabled ? "#ffe066" : "#cbd5f5";
      onBtn.style.background = this.soundFxEnabled ? "rgba(255, 224, 102, 0.3)" : "rgba(255, 255, 255, 0.1)";
      onBtn.style.color = this.soundFxEnabled ? "#ffe066" : "#cbd5f5";
    }
  }

  // ============================================================================
  // BACKGROUND MUSIC MANAGEMENT
  // ============================================================================

  /**
   * Stop background music
   */
  stopBackgroundMusic() {
    if (this.currentBackgroundMusic && this.currentBackgroundMusic.isPlaying) {
      this.currentBackgroundMusic.stop();
    }
    this.currentBackgroundMusic = null;
  }

  /**
   * Pause background music
   */
  pauseBackgroundMusic() {
    if (this.currentBackgroundMusic && this.currentBackgroundMusic.isPlaying) {
      this.currentBackgroundMusic.pause();
    }
  }

  /**
   * Resume background music
   */
  resumeBackgroundMusic() {
    if (this.currentBackgroundMusic && this.backgroundMusicEnabled && !this.getIsGamePaused()) {
      if (!this.currentBackgroundMusic.isPlaying) {
        this.currentBackgroundMusic.play();
      }
    }
  }

  /**
   * Load background music for a level
   * @param {string} levelId - Level ID to load music for
   * @returns {THREE.Audio|null} Music object or null if failed
   */
  loadBackgroundMusic(levelId) {
    // Check if audioListener is initialized
    try {
      if (!this.audioListener) {
        console.warn(`⚠️ [AUDIO] Audio listener not initialized yet, cannot load music for ${levelId}`);
        return null;
      }
      if (!this.audioListener.context) {
        console.warn(`⚠️ [AUDIO] Audio listener context not available, cannot load music for ${levelId}`);
        return null;
      }
    } catch (e) {
      // audioListener not initialized yet
      console.warn(`⚠️ [AUDIO] Audio listener error, cannot load music for ${levelId}:`, e);
      return null;
    }

    const musicPath = BACKGROUND_MUSIC_PATHS[levelId];
    if (!musicPath) {
      console.warn(`⚠️ [AUDIO] No background music path for level: ${levelId}`);
      return null;
    }

    // Return existing music object if already loaded
    if (this.backgroundMusicObjects[levelId]) {
      return this.backgroundMusicObjects[levelId];
    }

    // FIXED (January 6, 2026): Resolve music path for production
    const resolvePath = window.resolveAssetPath || ((p) => p);
    const resolvedMusicPath = resolvePath(musicPath);

    // Create new music object
    const music = new THREE.Audio(this.audioListener);
    music.userData = music.userData || {};
    music.userData.levelId = levelId;
    this.backgroundMusicObjects[levelId] = music;

    console.log(`📂 [AUDIO] Loading background music for ${levelId} from: ${resolvedMusicPath}`);
    this.audioLoader.load(
      resolvedMusicPath,
      (buffer) => {
        music.setBuffer(buffer);
        music.setLoop(true); // Loop the music
        music.setVolume(this.backgroundMusicVolume);
        console.log(`✅ [AUDIO] Background music loaded for ${levelId}: ${musicPath}`);

        // If this is the current level and music is enabled, play it
        const currentLevel = this.getCurrentLevel();
        if (currentLevel === levelId && this.backgroundMusicEnabled) {
          console.log(`🎵 [AUDIO] Auto-playing music for current level ${levelId}`);
          this.playBackgroundMusic(levelId);
        }
      },
      undefined,
      (error) => {
        console.error(`❌ [AUDIO] Failed to load background music for ${levelId} from ${musicPath}:`, error);
        // Remove failed music object
        delete this.backgroundMusicObjects[levelId];
      }
    );

    return music;
  }

  /**
   * Play background music for a level
   * @param {string} levelId - Level ID to play music for
   */
  playBackgroundMusic(levelId) {
    console.log(`🎵 [AUDIO] playBackgroundMusic called for ${levelId}, enabled: ${this.backgroundMusicEnabled}`);
    if (!this.backgroundMusicEnabled) {
      console.log("⏸️ [AUDIO] Background music is disabled, not playing");
      return; // Music is disabled
    }

    // Check if audioListener is initialized (may not be ready during initial setup)
    try {
      if (!this.audioListener) {
        return;
      }
    } catch (e) {
      // audioListener not initialized yet
      return;
    }

    // Resume audio context if needed (required for autoplay policies)
    this.resumeAudioContextIfNeeded();

    // Stop current music
    this.stopBackgroundMusic();

    // Load and play music for this level
    let music = this.backgroundMusicObjects[levelId];
    if (!music) {
      music = this.loadBackgroundMusic(levelId);
      if (!music) {
        return; // Failed to load
      }
    }

    // Wait for buffer to be loaded before playing
    if (music.buffer) {
      music.setVolume(this.backgroundMusicVolume);
      if (!this.getIsGamePaused()) {
        music.play();
      }
      music.userData = music.userData || {};
      music.userData.levelId = levelId;
      music._levelId = levelId;
      this.currentBackgroundMusic = music;
      console.log(`🎵 [AUDIO] Playing background music for ${levelId}`);
    } else {
      // Buffer not loaded yet, wait for it
      const checkBuffer = setInterval(() => {
        if (music.buffer) {
          clearInterval(checkBuffer);
          music.setVolume(this.backgroundMusicVolume);
          if (!this.getIsGamePaused()) {
            music.play();
          }
          music.userData = music.userData || {};
          music.userData.levelId = levelId;
          music._levelId = levelId;
          this.currentBackgroundMusic = music;
          console.log(`🎵 [AUDIO] Playing background music for ${levelId}`);
        }
      }, 100);

      // Timeout after 5 seconds
      setTimeout(() => {
        clearInterval(checkBuffer);
      }, 5000);
    }
  }

  /**
   * Set background music enabled state
   * @param {boolean} enabled - Whether background music should be enabled
   */
  setBackgroundMusicEnabled(enabled) {
    this.backgroundMusicEnabled = enabled;
    try {
      localStorage.setItem(BACKGROUND_MUSIC_STORAGE_KEY, enabled ? "true" : "false");
    } catch (error) {
      console.warn("⚠️ [AUDIO] Failed to save background music preference:", error);
    }

    if (enabled) {
      // Play music for current level
      const currentLevel = this.getCurrentLevel();
      if (currentLevel) {
        this.playBackgroundMusic(currentLevel);
      }
    } else {
      // Stop current music
      this.stopBackgroundMusic();
    }

    this.updateBackgroundMusicButtons();
  }

  /**
   * Get background music enabled state
   * @returns {boolean} Whether background music is enabled
   */
  getBackgroundMusicEnabled() {
    return this.backgroundMusicEnabled;
  }

  /**
   * Set background music volume
   * @param {number} volume - Volume level (0.0 to 1.0)
   */
  setBackgroundMusicVolume(volume) {
    this.backgroundMusicVolume = Math.max(0, Math.min(1, volume)); // Clamp between 0 and 1
    try {
      localStorage.setItem(BACKGROUND_MUSIC_VOLUME_STORAGE_KEY, this.backgroundMusicVolume.toString());
    } catch (error) {
      console.warn("⚠️ [AUDIO] Failed to save background music volume:", error);
    }

    // Update volume of current music if playing
    if (this.currentBackgroundMusic) {
      this.currentBackgroundMusic.setVolume(this.backgroundMusicVolume);
    }

    // Update volume of all loaded music objects
    Object.values(this.backgroundMusicObjects).forEach(music => {
      if (music && music.buffer) {
        music.setVolume(this.backgroundMusicVolume);
      }
    });

    this.updateBackgroundMusicVolumeSlider();
  }

  /**
   * Get background music volume
   * @returns {number} Volume level (0.0 to 1.0)
   */
  getBackgroundMusicVolume() {
    return this.backgroundMusicVolume;
  }

  /**
   * Update background music buttons in options menu
   */
  updateBackgroundMusicButtons() {
    const optionsMenu = this.getOptionsMenu();
    if (!optionsMenu) return;
    const offBtn = optionsMenu._backgroundMusicOffBtn;
    const onBtn = optionsMenu._backgroundMusicOnBtn;
    if (offBtn && onBtn) {
      // Enable buttons and make them visible
      offBtn.disabled = false;
      offBtn.style.opacity = "1";
      offBtn.style.cursor = "pointer";
      onBtn.disabled = false;
      onBtn.style.opacity = "1";
      onBtn.style.cursor = "pointer";
      
      // Update styling
      offBtn.style.background = !this.backgroundMusicEnabled ? "rgba(255, 224, 102, 0.3)" : "rgba(255, 255, 255, 0.1)";
      offBtn.style.color = !this.backgroundMusicEnabled ? "#ffe066" : "#cbd5f5";
      onBtn.style.background = this.backgroundMusicEnabled ? "rgba(255, 224, 102, 0.3)" : "rgba(255, 255, 255, 0.1)";
      onBtn.style.color = this.backgroundMusicEnabled ? "#ffe066" : "#cbd5f5";
    }
  }

  /**
   * Update background music volume slider in options menu
   */
  updateBackgroundMusicVolumeSlider() {
    const optionsMenu = this.getOptionsMenu();
    if (!optionsMenu) return;
    const volumeSlider = optionsMenu._backgroundMusicVolumeSlider;
    const volumeValue = optionsMenu._backgroundMusicVolumeValue;
    if (volumeSlider) {
      // Enable slider and make it visible
      volumeSlider.disabled = false;
      volumeSlider.style.opacity = "1";
      volumeSlider.style.cursor = "pointer";
      volumeSlider.value = this.backgroundMusicVolume;
    }
    if (volumeValue) {
      volumeValue.textContent = Math.round(this.backgroundMusicVolume * 100) + "%";
    }
  }

  /**
   * Ensure background music is playing for current level
   * @param {boolean} force - Force play even if already playing
   */
  ensureBackgroundMusicForCurrentLevel(force = false) {
    if (!this.backgroundMusicEnabled) {
      return;
    }

    try {
      if (!this.audioListener) {
        return;
      }
    } catch (error) {
      return;
    }

    if (force) {
      const currentLevel = this.getCurrentLevel();
      if (currentLevel) {
        this.playBackgroundMusic(currentLevel);
      }
      return;
    }

    const currentLevelId = this.getCurrentLevel();
    const playingLevelId =
      (this.currentBackgroundMusic && (this.currentBackgroundMusic.userData?.levelId || this.currentBackgroundMusic._levelId)) || null;
    const isPlaying = this.currentBackgroundMusic && this.currentBackgroundMusic.isPlaying;

    if (!isPlaying || playingLevelId !== currentLevelId) {
      if (currentLevelId) {
        this.playBackgroundMusic(currentLevelId);
      }
    } else {
      this.currentBackgroundMusic.setVolume(this.backgroundMusicVolume);
    }
  }
}
