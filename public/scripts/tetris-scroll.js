// 🧀 Cheese Tetris Scroll v10.0 - Legacy Boss Build (Season 3 revamp + Season 5 fixes)
//
// 🏆 SEASON 5 UPDATE (2025-11-06):
// - ROLE SYSTEM: Fetches live roles via /api/user/roles.php (emoji-safe normalization)
// - UX PARITY: OK/Play Again buttons, auto-start flag, guide locking, P-key pause
// - SCORE SYNC: Global score HUD, Snake-style achievement popups, DSPOINC parity
// - TEST HELPERS: forceLoadTestRoles(), testRoleSystem(), testRoleFeatures()
//
// 🏆 SEASON 3 PHASE 2 COMPLETE (2025-01-28):
// - ACHIEVEMENT SYSTEM: In-game milestone tracking with animated pop-ups
// - DYNAMIC SCORING: Performance-based rewards and bonus objectives
// - SKILL TRACKING: Line clears, combos, perfect clears, and speed challenges
// - ENGAGEMENT FEATURES: Multiple achievements to unlock
// 
// 🔧 CRITICAL BUG FIXES APPLIED (2025-01-28):
// - FIXED ACHIEVEMENT POPUPS: Only show for newly earned achievements
// - FIXED COMBO LOGIC: Corrected combo_starter and combo_master conditions
// - FIXED USER ID: Corrected hardcoded test ID to Santa's Discord ID
// - FIXED DATABASE SAVE: Achievements now save properly after game ends
// - FIXED VARIABLE SCOPE: Resolved linesCleared reference error
// 
// 🌟 SEASON 3 FEATURES (2025-01-28):
// - PNG BLOCK SUPPORT: Enhanced visual blocks with PNG images
// - MOBILE OPTIMIZATION: Touch controls and mobile device detection
// - SOUND SYSTEM: Professional Web Audio API sound effects
// - ACHIEVEMENT INTEGRATION: Complete achievement system with database sync
// 
// 🎮 GAME FEATURES:
// - CLASSIC TETRIS GAMEPLAY: Drop, rotate, and clear lines
// - COMBO SYSTEM: Chain line clears for bonus points
// - POWER-UPS: Special blocks and explosive mechanics
// - MOBILE CONTROLS: Touch-friendly interface
// - ACHIEVEMENT TRACKING: Real-time progress monitoring
// 
// 🔧 TECHNICAL IMPLEMENTATION:
// - Canvas-based rendering with PNG block support
// - Achievement system with database synchronization
// - Mobile device detection and touch controls
// - Sound management with Web Audio API
// - Real-time scoring and combo tracking
// 
// 🚀 PRODUCTION CONFIGURATION: Classic Tetris with modern enhancements!
// 🏆 Achievement types: Line clears, combos, perfect clears, speed challenges
// 🎯 Balanced difficulty curve for engaging progression!

// 🔧 MOBILE INITIALIZATION - Tetris-specific naming to avoid conflicts
let isTetrisMobileDevice = false;

// 🛑 Pause Logic — Global variable for touch controls
let isTetrisPaused = false;

// 🏆 ROLE-BASED GAMEPLAY SYSTEM
let userRoles = [];
let roleMultipliers = {
  'VIP Holder': 2.0,
  '🎴 VIP Holder': 2.0,
  'Holder': 1.5,
  '🏆 Holder': 1.5,
  'Champion': 1.4,
  'Season Tester': 1.3,
  'Early Bird': 1.2,
  'Cheese Hunter': 1.1,
  '🧀 Cheese Hunter': 1.1
};

const rolePriority = [
  'VIP Holder',
  'Holder',
  'Champion',
  'Season Tester',
  'Early Bird',
  'Cheese Hunter'
];

// 🎨 Role-based visual themes
let roleThemes = {
  'VIP Holder': 'golden',
  '🎴 VIP Holder': 'golden',
  'Holder': 'silver',
  '🏆 Holder': 'silver', 
  'Cheese Hunter': 'cheese',
  '🧀 Cheese Hunter': 'cheese',
  'Season Tester': 'green',
  'Early Bird': 'blue',
  'Champion': 'red'
};

function normalizeRole(role) {
  if (!role) return '';
  try {
    return role.replace(/^[\p{Emoji_Presentation}\p{Extended_Pictographic}\s]+/gu, '').trim();
  } catch (error) {
    return role.replace(/^[🎴🏆🧀\s]+/, '').trim();
  }
}

function getNormalizedRoles() {
  return userRoles.map(normalizeRole).filter(Boolean);
}

async function fetchTetrisUserRoles() {
  try {
    const isLocalDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' || window.location.hostname === '';
    
    if (isLocalDevelopment) {
      console.log('🏠 Local environment detected - using test roles for Tetris');
      userRoles = [
        '🎴 VIP Holder',
        '🏆 Holder',
        'Champion',
        'Season Tester',
        'Early Bird',
        '🧀 Cheese Hunter'
      ];
      applyRoleTheme();
      return userRoles;
    }
    
    const isProduction = window.location.hostname === 'narrrfs.world';
    const API_BASE_URL = isProduction ? 'https://narrrfs.world' : 'http://localhost';
    
    const response = await fetch(`${API_BASE_URL}/api/user/roles.php`, {
      method: 'GET',
      credentials: 'include'
    });
    
    if (response.ok) {
      const data = await response.json();
      userRoles = data.roles || [];
      console.log('🏆 User roles loaded from API for Tetris:', userRoles);
      applyRoleTheme();
      return userRoles;
    } else {
      console.log('🏆 No roles found or not logged in for Tetris');
      return [];
    }
  } catch (error) {
    console.log('🏆 Error fetching roles for Tetris:', error);
    return [];
  }
}

function applyRoleTheme() {
  const normalizedRoles = getNormalizedRoles();
  let primaryRole = null;
  
  for (const role of rolePriority) {
    if (normalizedRoles.includes(normalizeRole(role))) {
      primaryRole = normalizeRole(role);
      break;
    }
  }
  
  const theme =
    roleThemes[primaryRole] ||
    roleThemes[userRoles.find(role => normalizeRole(role) === primaryRole)] ||
    'default';
  
  console.log(`🎨 Tetris theme: ${theme} for role: ${primaryRole} (normalized from ${JSON.stringify(userRoles)})`);
  
  updateTetrisScoreDisplay();
  
  const canvas = document.getElementById('tetris-canvas');
  if (canvas) {
    canvas.classList.remove('golden', 'silver', 'cheese', 'green', 'blue', 'red');
    if (theme !== 'default') {
      canvas.classList.add(theme);
    }
  }
  
  const controlsSection = document.getElementById('tetris-controls-section');
  const controlsTitle = document.getElementById('tetris-controls-title');
  if (controlsSection && controlsTitle) {
    controlsSection.classList.remove('golden', 'silver', 'cheese', 'green', 'blue', 'red');
    controlsTitle.classList.remove('golden', 'silver', 'cheese', 'green', 'blue', 'red');
    if (theme !== 'default') {
      controlsSection.classList.add(theme);
      controlsTitle.classList.add(theme);
    }
  }
}

function getUserPrimaryRole() {
  const normalizedRoles = getNormalizedRoles();
  for (const role of rolePriority) {
    const normalized = normalizeRole(role);
    if (normalizedRoles.includes(normalized)) {
      return normalized;
    }
  }
  return null;
}

function getRoleScoreMultiplier() {
  const primaryRole = getUserPrimaryRole();
  if (!primaryRole) return 1.0;
  
  const matchingRole = userRoles.find(role => normalizeRole(role) === primaryRole);
  if (matchingRole && roleMultipliers[matchingRole]) {
    return roleMultipliers[matchingRole];
  }
  return roleMultipliers[primaryRole] || 1.0;
}

// Detect mobile device
if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
  isTetrisMobileDevice = true;
  console.log('📱 Mobile device detected');
  
  // Add mobile-specific meta viewport if not present
  if (!document.querySelector('meta[name="viewport"]')) {
    const viewport = document.createElement('meta');
    viewport.name = 'viewport';
    viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
    document.head.appendChild(viewport);
    console.log('📱 Added mobile viewport meta tag');
  }
} else {
  console.log('🖥️ Desktop device detected - touch controls still enabled');
}

// 🏆 Global score display element and score variable
let tetrisScoreDisplay = null;
let score = 0;

// 🏆 Update Tetris score display with role bonus
function updateTetrisScoreDisplay() {
  if (tetrisScoreDisplay) {
    const roleMultiplier = getRoleScoreMultiplier();
    
    if (roleMultiplier > 1.0) {
      tetrisScoreDisplay.textContent = `💰 Tetris Score: $${score} DSPOINC (${roleMultiplier}x Role Bonus!)`;
    } else {
      tetrisScoreDisplay.textContent = `💰 Tetris Score: $${score} DSPOINC`;
    }
  }
}

// 🚫 Full page scroll prevention
window.addEventListener("touchmove", function(e) {
  if (e.target.closest("#tetris-canvas")) {
    e.preventDefault();
  }
}, { passive: false });

window.addEventListener("keydown", function (e) {
  const keys = ["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight", " ", "a", "s", "d", "w"];
  if (keys.includes(e.key)) {
    e.preventDefault();
  }
}, { passive: false });

// 🎵 TETRIS SOUND SYSTEM - Professional Web Audio API sounds
class TetrisSoundManager {
  constructor() {
    this.audioContext = null;
    this.sounds = {};
    this.initAudio();
  }

  initAudio() {
    try {
      this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
      console.log('🎵 Tetris Sound System initialized');
    } catch (error) {
      console.warn('🎵 Audio not supported:', error);
    }
  }

  // Generate professional sound effects using Web Audio API
  playSound(type) {
    if (!this.audioContext) return;

    const oscillator = this.audioContext.createOscillator();
    const gainNode = this.audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(this.audioContext.destination);

    // Professional sound design
    switch (type) {
      case 'piecePlace':
        // Soft landing sound - low frequency with quick decay
        oscillator.frequency.setValueAtTime(220, this.audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(110, this.audioContext.currentTime + 0.1);
        oscillator.type = 'sine';
        gainNode.gain.setValueAtTime(0.3, this.audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.15);
        oscillator.start();
        oscillator.stop(this.audioContext.currentTime + 0.15);
        break;

      case 'lineClear':
        // Satisfying clear sound - ascending chord
        oscillator.frequency.setValueAtTime(440, this.audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(880, this.audioContext.currentTime + 0.2);
        oscillator.type = 'triangle';
        gainNode.gain.setValueAtTime(0.4, this.audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.3);
        oscillator.start();
        oscillator.stop(this.audioContext.currentTime + 0.3);
        break;

      case 'levelUp':
        // Triumphant level up sound - ascending scale
        oscillator.frequency.setValueAtTime(523, this.audioContext.currentTime); // C5
        oscillator.frequency.setValueAtTime(659, this.audioContext.currentTime + 0.1); // E5
        oscillator.frequency.setValueAtTime(784, this.audioContext.currentTime + 0.2); // G5
        oscillator.type = 'square';
        gainNode.gain.setValueAtTime(0.5, this.audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.4);
        oscillator.start();
        oscillator.stop(this.audioContext.currentTime + 0.4);
        break;

      case 'gameOver':
        // Dramatic game over sound - descending tone
        oscillator.frequency.setValueAtTime(440, this.audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(110, this.audioContext.currentTime + 0.5);
        oscillator.type = 'sawtooth';
        gainNode.gain.setValueAtTime(0.6, this.audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.6);
        oscillator.start();
        oscillator.stop(this.audioContext.currentTime + 0.6);
        break;
    }
  }
}

// Initialize Tetris sound manager
const tetrisSounds = new TetrisSoundManager();

// --- PNG Block Support simple only one template for all can be defined with new img/tetris ---
let allImagesLoaded = false;
let loadedCount = 0;
let pendingTetrisStart = false;

const blockImages = {};
const pieceImageMap = {
  1: "block_T.png",
  2: "block_O.png",
  3: "block_S.png",
  4: "block_Z.png",
  5: "block_I.png",
  6: "block_BOMB.png",
  7: "block_L.png",
  8: "block_J.png"
};

// 🎮 Touch control variables (Tetris-specific) - OPTIMIZED FOR MOBILE
let tetrisTouchStartX = 0;
let tetrisTouchStartY = 0;
let tetrisTouchStartTime = 0;
const TETRIS_SWIPE_THRESHOLD = 20; // Reduced for more sensitive control
const TETRIS_SWIPE_TIME_THRESHOLD = 300; // Reduced for faster response
const TETRIS_DOUBLE_TAP_THRESHOLD = 250; // Maximum time between taps for double tap
const TETRIS_DOWN_SWIPE_THRESHOLD = 25; // Separate threshold for down swipes (more sensitive)
let tetrisLastTapTime = 0;
let tetrisLastMoveTime = 0; // Throttle rapid movements
const TETRIS_MOVE_THROTTLE = 50; // Reduced throttle for more responsive control

// 🎮 Hold-to-drop functionality
let tetrisIsHolding = false;
let tetrisHoldInterval = null;
let tetrisRotationTimer = null; // Timer for delayed rotation
const TETRIS_HOLD_DELAY = 25; // Reduced delay for more responsive hold-to-drop (ms) - was 100
const TETRIS_HOLD_INTERVAL = 20; // Faster interval for more responsive hold-to-drop (ms) - was 50
let tetrisHoldStartTime = 0;
let tetrisTouchRecoveryTimeout = null;
let tetrisTouchMonitorInterval = null;
let dropHoldTimeout = null;
let touchDropInterval = null;
let heldDown = false;

function resetTouchControlTimers() {
  if (tetrisTouchRecoveryTimeout) {
    clearTimeout(tetrisTouchRecoveryTimeout);
    tetrisTouchRecoveryTimeout = null;
  }
  if (tetrisTouchMonitorInterval) {
    clearInterval(tetrisTouchMonitorInterval);
    tetrisTouchMonitorInterval = null;
  }
}

function cleanupTouchControls() {
  if (dropHoldTimeout) {
    clearTimeout(dropHoldTimeout);
    dropHoldTimeout = null;
  }
  if (touchDropInterval) {
    clearTimeout(touchDropInterval);
    touchDropInterval = null;
  }
  heldDown = false;
  resetTouchControlTimers();
  stopTetrisHold();
  if (tetrisRotationTimer) {
    clearTimeout(tetrisRotationTimer);
    tetrisRotationTimer = null;
  }
  tetrisLastMoveTime = 0;
  unlockTetrisScroll();
}

// 🎮 Hold-to-drop functions
function startTetrisHold() {
  if (tetrisIsHolding) return; // Already holding
  
  console.log('📱 Starting hold-to-drop - delay:', TETRIS_HOLD_DELAY, 'ms, interval:', TETRIS_HOLD_INTERVAL, 'ms');
  tetrisIsHolding = true;
  tetrisHoldStartTime = Date.now();
  
  // Start the hold interval after initial delay
  setTimeout(() => {
    if (tetrisIsHolding) {
      tetrisHoldInterval = setInterval(() => {
        if (tetrisIsHolding && typeof window.tetrisCollide === 'function' && typeof window.tetrisCurrent !== 'undefined' && window.tetrisCurrent.shape) {
          // Move piece down one step
          if (!window.tetrisCollide(window.tetrisCurrent.shape, window.tetrisCurrent.row + 1, window.tetrisCurrent.col)) {
            window.tetrisCurrent.row++;
            console.log('📱 Hold drop - piece moved to row:', window.tetrisCurrent.row);
            window.tetrisDraw();
          } else {
            // Piece can't move down anymore, stop holding
            console.log('📱 Hold drop - piece reached bottom');
            stopTetrisHold();
          }
        }
      }, TETRIS_HOLD_INTERVAL);
    }
  }, TETRIS_HOLD_DELAY);
}

function stopTetrisHold() {
  if (!tetrisIsHolding) return; // Not holding
  
  console.log('📱 Stopping hold-to-drop');
  tetrisIsHolding = false;
  
  if (tetrisHoldInterval) {
    clearInterval(tetrisHoldInterval);
    tetrisHoldInterval = null;
  }
}

function checkAndStartTetris() {
  const btn = document.getElementById("start-tetris-btn");
  if (!btn) {
    // auto-start fallback if no button present
    window.startTetrisGame();
  } else {
    // 🔧 MOBILE FIX: Ensure button is clickable on mobile
    btn.style.touchAction = "manipulation";
    btn.style.webkitTapHighlightColor = "transparent";
    
    // Add mobile-specific event listeners
    if ('ontouchstart' in window) {
      console.log('📱 Mobile device detected, adding touch event listeners');
      btn.addEventListener('touchstart', (e) => {
        e.preventDefault();
        console.log('📱 Mobile Tetris start button touched');
        window.startTetrisGame();
        btn.disabled = true;
        btn.textContent = "🕹️ Playing...";
      }, { passive: false });
    }

    // 🚀 Auto-start support (matches live build)
    if (localStorage.getItem('tetris_auto_start') === 'true') {
      console.log('🚀 Auto-start flag detected - starting Legacy Tetris automatically');
      localStorage.removeItem('tetris_auto_start');
      setTimeout(() => {
        window.startTetrisGame();
        btn.disabled = true;
        btn.textContent = "🕹️ Playing...";
      }, 500);
    }
  }
}

Object.entries(pieceImageMap).forEach(([key, filename]) => {
  const img = new Image();
  img.src = "img/tetris/" + filename;
  img.onload = () => {
    loadedCount++;
    if (loadedCount === Object.keys(pieceImageMap).length) {
      allImagesLoaded = true;
      checkAndStartTetris(); // ✅ Ensure the game can start after loading

      if (pendingTetrisStart) {
        console.log('🚀 Pending Tetris start detected - launching game now that assets are ready');
        const btn = document.getElementById("start-tetris-btn");
        if (btn) {
          btn.disabled = true;
          btn.textContent = "🕹️ Playing...";
        }
        pendingTetrisStart = false;
        window.startTetrisGame();
      }
      
      // 🔧 MOBILE INITIALIZATION
      if (isTetrisMobileDevice) {
        console.log('📱 All Tetris images loaded, mobile initialization complete');
        
        // Ensure mobile-specific setup is complete
        setTimeout(() => {
          const canvas = document.getElementById('tetris-canvas');
          const startBtn = document.getElementById('start-tetris-btn');
          
          if (canvas && startBtn) {
            console.log('📱 Mobile Tetris elements ready');
            
            // Test mobile functionality
            if (typeof window.testMobileTetris === 'function') {
              window.testMobileTetris();
            }
          }
        }, 500);
      }
    }
  };
  img.onerror = (error) => {
    loadedCount++;
    console.error(`⚠️ Failed to load Tetris block image: ${filename}`, error);
    if (loadedCount === Object.keys(pieceImageMap).length) {
      allImagesLoaded = true;
      console.warn('⚠️ Proceeding without one or more block textures (fallback colors will be used)');
      checkAndStartTetris();
    }
  };
  blockImages[key] = img;
});

// 🎮 MOBILE TOUCH CONTROLS - Fixed Implementation
function initTouchControls(canvas) {
  console.log('📱 Initializing Tetris touch controls for canvas:', canvas.id);
  
  // Remove any existing touch listeners to prevent duplicates
  canvas.removeEventListener("touchstart", handleTouchStart);
  canvas.removeEventListener("touchmove", handleTouchMove);
  canvas.removeEventListener("touchend", handleTouchEnd);
  
  // Store references on the canvas for proper cleanup
  canvas.tetrisTouchStart = handleTouchStart;
  canvas.tetrisTouchMove = handleTouchMove;
  canvas.tetrisTouchEnd = handleTouchEnd;
  
  // Add new touch listeners with stored references
  canvas.addEventListener("touchstart", canvas.tetrisTouchStart, { passive: false });
  canvas.addEventListener("touchmove", canvas.tetrisTouchMove, { passive: false });
  canvas.addEventListener("touchend", canvas.tetrisTouchEnd, { passive: false });
  
  console.log('📱 Touch controls initialized successfully');
  console.log('📱 Canvas touch events after init:', canvas.ontouchstart, canvas.ontouchmove, canvas.ontouchend);
}

function handleTouchStart(e) {
  console.log('📱 Touch start detected on Tetris canvas');
  
  // Check if game is running and functions are available
  if (isTetrisPaused || typeof window.tetrisDraw !== 'function') {
    console.log('📱 Touch ignored - game not running or functions not available');
    return;
  }
  
  e.preventDefault();
  e.stopPropagation();
  
  const touch = e.touches[0];
  
  // ✅ FIX: Always reset touch position for new gesture
  tetrisTouchStartX = touch.clientX;
  tetrisTouchStartY = touch.clientY;
  tetrisTouchStartTime = Date.now();
  tetrisLastMoveTime = 0; // Reset throttle timer for new gesture
  
  console.log('📱 Touch start position:', tetrisTouchStartX, tetrisTouchStartY);
  console.log('📱 Touch state reset for new gesture');

  // 🎮 Start hold-to-drop timer (will be cancelled if user moves finger significantly)
  console.log('📱 Touch start - starting hold timer with delay:', TETRIS_HOLD_DELAY, 'ms');
  tetrisRotationTimer = setTimeout(() => {
    // Check if touch is still at the same position (no movement)
    const timeSinceStart = Date.now() - tetrisTouchStartTime;
    if (timeSinceStart >= TETRIS_HOLD_DELAY && !tetrisIsHolding) {
      console.log('📱 Long press detected - starting hold-to-drop');
      startTetrisHold();
    }
  }, TETRIS_HOLD_DELAY);
}

function handleTouchMove(e) {
  // Check if game is running and functions are available
  if (isTetrisPaused || typeof window.tetrisDraw !== 'function') {
    console.log('📱 Touch move ignored - game not running or functions not available');
    return;
  }
  
  e.preventDefault();
  e.stopPropagation();
  
  const touch = e.touches[0];
  const deltaX = touch.clientX - tetrisTouchStartX;
  const deltaY = touch.clientY - tetrisTouchStartY;
  const touchTime = Date.now() - tetrisTouchStartTime;
  const currentTime = Date.now();
  
  // 🎮 Only cancel hold-to-drop if user moves finger significantly (not just small movements)
  if (tetrisIsHolding && (Math.abs(deltaX) > 20 || Math.abs(deltaY) > 20)) {
    console.log('📱 Significant finger movement detected - cancelling hold-to-drop');
    stopTetrisHold();
  }
  
  if (tetrisRotationTimer) {
    console.log('📱 Finger moved - cancelling rotation timer');
    clearTimeout(tetrisRotationTimer);
    tetrisRotationTimer = null;
  }
  
  // ✅ FIX: Throttle rapid movements to prevent too-fast control
  if (currentTime - tetrisLastMoveTime < TETRIS_MOVE_THROTTLE) {
    return; // Skip this move to prevent rapid-fire movements
  }
  
  console.log('📱 Touch move delta:', deltaX, deltaY, 'time:', touchTime);

  // Horizontal movement (left/right) - OPTIMIZED for mobile responsiveness
  if (Math.abs(deltaX) > TETRIS_SWIPE_THRESHOLD && Math.abs(deltaY) < TETRIS_SWIPE_THRESHOLD && touchTime < TETRIS_SWIPE_TIME_THRESHOLD) {
    if (deltaX > 0) {
      console.log('📱 Swipe right detected');
      if (typeof window.tetrisCollide === 'function' && typeof window.tetrisCurrent !== 'undefined' && window.tetrisCurrent.shape) {
        console.log('📱 DEBUG: Current piece position before move:', window.tetrisCurrent.row, window.tetrisCurrent.col);
        console.log('📱 DEBUG: Checking collision at:', window.tetrisCurrent.row, window.tetrisCurrent.col + 1);
        const collisionResult = window.tetrisCollide(window.tetrisCurrent.shape, window.tetrisCurrent.row, window.tetrisCurrent.col + 1);
        console.log('📱 DEBUG: Collision result:', collisionResult);
        if (!collisionResult) {
          window.tetrisCurrent.col++;
          console.log('📱 Piece moved right to column:', window.tetrisCurrent.col);
          window.tetrisDraw();
          tetrisLastMoveTime = currentTime; // Update throttle timer
          // Reset touch position for next movement
          tetrisTouchStartX = touch.clientX;
        } else {
          console.log('📱 DEBUG: Movement blocked by collision');
        }
      } else {
        console.log('📱 DEBUG: Game functions not available:', typeof window.tetrisCollide, typeof window.tetrisCurrent);
      }
    } else {
      console.log('📱 Swipe left detected');
      if (typeof window.tetrisCollide === 'function' && typeof window.tetrisCurrent !== 'undefined' && window.tetrisCurrent.shape) {
        console.log('📱 DEBUG: Current piece position before move:', window.tetrisCurrent.row, window.tetrisCurrent.col);
        console.log('📱 DEBUG: Checking collision at:', window.tetrisCurrent.row, window.tetrisCurrent.col - 1);
        const collisionResult = window.tetrisCollide(window.tetrisCurrent.shape, window.tetrisCurrent.row, window.tetrisCurrent.col - 1);
        console.log('📱 DEBUG: Collision result:', collisionResult);
        if (!collisionResult) {
          window.tetrisCurrent.col--;
          console.log('📱 Piece moved left to column:', window.tetrisCurrent.col);
          window.tetrisDraw();
          tetrisLastMoveTime = currentTime; // Update throttle timer
          // Reset touch position for next movement
          tetrisTouchStartX = touch.clientX;
        } else {
          console.log('📱 DEBUG: Movement blocked by collision');
        }
      } else {
        console.log('📱 DEBUG: Game functions not available:', typeof window.tetrisCollide, typeof window.tetrisCurrent);
      }
    }
  }

  // Vertical movement (quick drop) - OPTIMIZED for better mobile control
  if (deltaY > TETRIS_DOWN_SWIPE_THRESHOLD && Math.abs(deltaX) < TETRIS_SWIPE_THRESHOLD && touchTime < TETRIS_SWIPE_TIME_THRESHOLD) {
    console.log('📱 Swipe down detected - quick drop');
    if (typeof window.tetrisCollide === 'function' && typeof window.tetrisCurrent !== 'undefined' && window.tetrisCurrent.shape) {
      // Quick drop: move piece down as far as possible
      let dropDistance = 0;
      while (!window.tetrisCollide(window.tetrisCurrent.shape, window.tetrisCurrent.row + 1, window.tetrisCurrent.col)) {
        window.tetrisCurrent.row++;
        dropDistance++;
      }
      console.log('📱 Piece dropped', dropDistance, 'rows to position:', window.tetrisCurrent.row);
      window.tetrisDraw();
      tetrisLastMoveTime = currentTime; // Update throttle timer
      // Reset touch position for next gesture
      tetrisTouchStartY = touch.clientY;
    }
  }
  
  // Gentle downward movement (single step down) - for more precise control
  else if (deltaY > 15 && deltaY < TETRIS_DOWN_SWIPE_THRESHOLD && Math.abs(deltaX) < 20 && touchTime < 200) {
    console.log('📱 Gentle down movement detected');
    if (typeof window.tetrisCollide === 'function' && typeof window.tetrisCurrent !== 'undefined' && window.tetrisCurrent.shape) {
      if (!window.tetrisCollide(window.tetrisCurrent.shape, window.tetrisCurrent.row + 1, window.tetrisCurrent.col)) {
        window.tetrisCurrent.row++;
        console.log('📱 Piece moved down one step to row:', window.tetrisCurrent.row);
        window.tetrisDraw();
        tetrisLastMoveTime = currentTime;
        // Reset touch position for next movement
        tetrisTouchStartY = touch.clientY;
      }
    }
  }
  
  // 🎮 Swipe up for rotation - more intuitive for mobile
  else if (deltaY < -TETRIS_SWIPE_THRESHOLD && Math.abs(deltaX) < TETRIS_SWIPE_THRESHOLD && touchTime < TETRIS_SWIPE_TIME_THRESHOLD) {
    console.log('📱 Swipe up detected - rotating piece');
    if (typeof window.tetrisRotatePiece === 'function') {
      console.log('📱 DEBUG: Current piece position before rotation:', window.tetrisCurrent.row, window.tetrisCurrent.col);
      window.tetrisRotatePiece();
      console.log('📱 DEBUG: Piece rotated successfully');
      window.tetrisDraw();
      tetrisLastMoveTime = currentTime;
    } else {
      console.log('📱 DEBUG: Rotation function not available');
    }
  }
}

function handleTouchEnd(e) {
  console.log('📱 Touch end detected on Tetris canvas');
  e.preventDefault();
  e.stopPropagation();
  
  // 🎮 Stop hold-to-drop when user lifts finger
  if (tetrisIsHolding) {
    console.log('📱 Finger lifted - stopping hold-to-drop');
    stopTetrisHold();
  }
  
  // 🎮 Clear rotation timer when user lifts finger
  if (tetrisRotationTimer) {
    console.log('📱 Finger lifted - clearing rotation timer');
    clearTimeout(tetrisRotationTimer);
    tetrisRotationTimer = null;
  }
  
  // ✅ FIX: Only reset throttle timer, keep touch position for next gesture
  tetrisLastMoveTime = 0; // Reset throttle timer only
  console.log('📱 Touch gesture completed, ready for next gesture');
}

window.startTetrisGame = async function () {
  console.log('🎮 startTetrisGame called');
  const startBtn = document.getElementById("start-tetris-btn");
  
  if (!allImagesLoaded) {
    console.warn("Assets still loading... deferring Tetris start until ready");
    pendingTetrisStart = true;
    if (startBtn) {
      startBtn.disabled = false;
      startBtn.textContent = "▶️ Start";
    }
    return;
  }
  
  pendingTetrisStart = false;
  
  if (startBtn) {
    startBtn.disabled = true;
    startBtn.textContent = "🕹️ Playing...";
  }
  
  // 🔧 MOBILE FIX: Ensure canvas is properly sized for mobile
  const canvas = document.getElementById("tetris-canvas");
  if (canvas) {
    // Force canvas size for mobile compatibility
    canvas.width = 200;
    canvas.height = 400;
    canvas.style.width = '200px';
    canvas.style.height = '400px';
    
    // Mobile-specific canvas settings
    if ('ontouchstart' in window) {
      console.log('📱 Mobile canvas setup');
      canvas.style.touchAction = 'none';
      canvas.style.webkitUserSelect = 'none';
      canvas.style.userSelect = 'none';
    }
  }
  
  console.log('🚀 Starting Tetris game...');
  await startTetris(); // ← main game logic (now async)
};

// 🎨 Cheese-Themed Block Colors do not work now code does so kind of backup 
const colors = [
  "#000000",    // 0 - empty
  "#FFB347",    // 1 - T (cheddar orange)
  "#FFFACD",    // 2 - O (lemon cream)
  "#EEDC82",    // 3 - S (aged parmesan)
  "#FFDEAD",    // 4 - Z (mild gouda)
  "#FFFF99",    // 5 - I (soft mozzarella)
  "#FFD700",    // 6 - bomb (keep yellow explosion)
  "#FFA500",    // 7 - L (sharp cheddar)
  "#F4C430"     // 8 - J (gruyère gold)
];

let activeExplosive = null; // track position and countdown

// 🔮 Preview canvas setup
const nextCanvas = document.getElementById("next-canvas");
const nextCtx = nextCanvas?.getContext("2d");

// 🧀 CHEESE PARTICLE SYSTEM - Season 4 Enhancement
class CheeseParticleSystem {
  constructor() {
    this.particles = [];
    this.maxParticles = 20; // Reduced from 50 to 20 for better performance
  }

  // Create cheese particles when lines are cleared with role-based enhancement
  createCheeseParticles(clearedLines, canvasWidth, canvasHeight) {
    let baseParticleCount = clearedLines * 4; // Base particles per line
    
    // Role-based particle enhancement using role IDs
    const primaryRole = getUserPrimaryRole();
    if (primaryRole === 'VIP Holder') {
      baseParticleCount *= 2; // Double particles for VIP
    } else if (primaryRole === 'Holder' || primaryRole === 'Champion') {
      baseParticleCount = Math.floor(baseParticleCount * 1.5); // 1.5x particles for Holder/Champion
    } else if (primaryRole === 'Cheese Hunter') {
      baseParticleCount = Math.floor(baseParticleCount * 1.3); // Extra particles for Cheese Hunter
    }
    
    const particleCount = Math.min(baseParticleCount, this.maxParticles);
    
    for (let i = 0; i < particleCount; i++) {
      const particle = {
        x: Math.random() * canvasWidth,
        y: canvasHeight - (clearedLines * 20) + Math.random() * (clearedLines * 20),
        vx: (Math.random() - 0.5) * 6, // Faster horizontal velocity
        vy: -Math.random() * 8 - 4, // Faster upward velocity
        life: 30, // Shorter life (30 frames instead of 60)
        maxLife: 30,
        size: Math.random() * 3 + 2, // Slightly smaller particles
        color: this.getRandomCheeseColor(),
        rotation: Math.random() * Math.PI * 2,
        rotationSpeed: (Math.random() - 0.5) * 0.3 // Faster rotation
      };
      
      this.particles.push(particle);
    }
    
    // Particles created successfully
  }

  // Get random cheese-themed colors with role-based enhancement
  getRandomCheeseColor() {
    const primaryRole = getUserPrimaryRole();
    
    // Role-based color themes using role IDs
    if (primaryRole === 'VIP Holder') {
      const vipColors = [
        '#FFD700', // Golden yellow
        '#FFA500', // Cheddar orange
        '#FFE55C', // Bright gold
        '#FFB347', // Golden peach
        '#DAA520'  // Goldenrod
      ];
      return vipColors[Math.floor(Math.random() * vipColors.length)];
    } else if (primaryRole === 'Holder') {
      const holderColors = [
        '#C0C0C0', // Silver
        '#D3D3D3', // Light gray
        '#A8A8A8', // Dark gray
        '#E6E6FA', // Lavender
        '#F5F5F5'  // White smoke
      ];
      return holderColors[Math.floor(Math.random() * holderColors.length)];
    } else if (primaryRole === 'Cheese Hunter') {
      const cheeseHunterColors = [
        '#FFA500', // Cheddar orange
        '#FF8C00', // Dark orange
        '#FF7F50', // Coral
        '#FF6347', // Tomato
        '#FF4500'  // Orange red
      ];
      return cheeseHunterColors[Math.floor(Math.random() * cheeseHunterColors.length)];
    } else if (primaryRole === 'Season Tester') {
      const seasonTesterColors = [
        '#8A2BE2', // Blue violet
        '#9932CC', // Dark orchid
        '#8B008B', // Dark magenta
        '#4B0082', // Indigo
        '#9400D3'  // Violet
      ];
      return seasonTesterColors[Math.floor(Math.random() * seasonTesterColors.length)];
    } else if (primaryRole === 'Champion') {
      const championColors = [
        '#FF4500', // Orange red
        '#FF6347', // Tomato
        '#FF0000', // Red
        '#DC143C', // Crimson
        '#B22222'  // Fire brick
      ];
      return championColors[Math.floor(Math.random() * championColors.length)];
    } else if (primaryRole === 'Early Bird') {
      const earlyBirdColors = [
        '#00BFFF', // Deep sky blue
        '#1E90FF', // Dodger blue
        '#87CEEB', // Sky blue
        '#87CEFA', // Light sky blue
        '#ADD8E6'  // Light blue
      ];
      return earlyBirdColors[Math.floor(Math.random() * earlyBirdColors.length)];
    }
    
    // Default cheese colors for users without special roles
    const cheeseColors = [
      '#FFD700', // Golden yellow
      '#FFA500', // Cheddar orange
      '#F5DEB3', // Mozzarella white
      '#87CEEB', // Blue cheese blue
      '#FFE4B5', // Cream cheese
      '#DAA520'  // Goldenrod
    ];
    return cheeseColors[Math.floor(Math.random() * cheeseColors.length)];
  }

  // Update all particles
  update() {
    // Early exit if no particles
    if (this.particles.length === 0) return;
    
    for (let i = this.particles.length - 1; i >= 0; i--) {
      const particle = this.particles[i];
      
      // Update position
      particle.x += particle.vx;
      particle.y += particle.vy;
      
      // Update rotation
      particle.rotation += particle.rotationSpeed;
      
      // Apply gravity
      particle.vy += 0.15; // Slightly stronger gravity for faster movement
      
      // Reduce life
      particle.life--;
      
      // Remove dead particles
      if (particle.life <= 0) {
        this.particles.splice(i, 1);
      }
    }
  }

  // Draw all particles
  draw(ctx) {
    this.particles.forEach(particle => {
      const alpha = particle.life / particle.maxLife;
      
      ctx.save();
      ctx.globalAlpha = alpha;
      ctx.fillStyle = particle.color;
      ctx.translate(particle.x, particle.y);
      ctx.rotate(particle.rotation);
      
      // Draw cheese particle as a small square with rounded corners
      ctx.beginPath();
      const x = -particle.size/2;
      const y = -particle.size/2;
      const width = particle.size;
      const height = particle.size;
      const radius = 2;
      
      // Draw rounded rectangle manually for browser compatibility
      ctx.moveTo(x + radius, y);
      ctx.lineTo(x + width - radius, y);
      ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
      ctx.lineTo(x + width, y + height - radius);
      ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
      ctx.lineTo(x + radius, y + height);
      ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
      ctx.lineTo(x, y + radius);
      ctx.quadraticCurveTo(x, y, x + radius, y);
      ctx.closePath();
      ctx.fill();
      
      // Add cheese sparkle effect (reduced frequency for performance)
      if (Math.random() > 0.85) {
        ctx.fillStyle = '#FFFFFF';
        ctx.beginPath();
        ctx.arc(particle.size/3, -particle.size/3, particle.size/4, 0, Math.PI * 2);
        ctx.fill();
      }
      
      ctx.restore();
    });
  }

  // Clear all particles (for game reset)
  clear() {
    this.particles = [];
  }
}

// Initialize cheese particle system
const cheeseParticles = new CheeseParticleSystem();

// 🎆 BOMB DEFUSAL SPARKLES EFFECT - Season 4 Enhancement
function createBombDefusalSparkles() {
  // Create golden sparkles for bomb defusal
  const canvas = document.getElementById("tetris-canvas");
  if (!canvas) return;
  
  const sparkleCount = 8;
  const centerX = canvas.width / 2;
  const centerY = canvas.height / 2;
  
  for (let i = 0; i < sparkleCount; i++) {
    const sparkle = {
      x: centerX + (Math.random() - 0.5) * 100,
      y: centerY + (Math.random() - 0.5) * 100,
      vx: (Math.random() - 0.5) * 8,
      vy: (Math.random() - 0.5) * 8,
      life: 60,
      maxLife: 60,
      size: Math.random() * 4 + 2,
      color: '#FFD700', // Golden color for bomb defusal
      rotation: Math.random() * Math.PI * 2,
      rotationSpeed: (Math.random() - 0.5) * 0.2
    };
    
    // Add sparkle to cheese particle system temporarily
    cheeseParticles.particles.push(sparkle);
  }
  
  console.log(`🎆 Bomb defusal sparkles created: ${sparkleCount} golden sparkles at center (${centerX}, ${centerY})`);
}

// 🧀 TEST FUNCTION - Can be called from console to test particles
window.testCheeseParticles = function() {
  const canvas = document.getElementById("tetris-canvas");
  if (canvas) {
    cheeseParticles.createCheeseParticles(1, canvas.width, canvas.height);
    console.log('🧀 Test particles created:', cheeseParticles.particles.length);
  }
};

// 🏆 TEST FUNCTION - Test role-based features
window.testRoleFeatures = function() {
  console.log('🏆 Testing role-based features...');
  console.log('Current roles:', userRoles);
  console.log('Primary role:', getUserPrimaryRole());
  console.log('Score multiplier:', getRoleScoreMultiplier());
  console.log('Theme applied:', document.getElementById('tetris-canvas')?.className);
  
  const canvas = document.getElementById("tetris-canvas");
  if (canvas) {
    cheeseParticles.createCheeseParticles(2, canvas.width, canvas.height);
    console.log('🏆 Role-based test particles created:', cheeseParticles.particles.length);
  }
};

async function startTetris() {
  const canvas = document.getElementById("tetris-canvas");
  const context = canvas.getContext("2d");
  tetrisScoreDisplay = document.getElementById("tetris-score");
  
  // 🏆 Fetch user roles for role-based gameplay (CRITICAL: await this!)
  await fetchTetrisUserRoles();

  // 🔁 Reset control state before starting a fresh session
  resetTouchControlTimers();
  stopTetrisHold();
  tetrisIsHolding = false;
  if (tetrisRotationTimer) {
    clearTimeout(tetrisRotationTimer);
    tetrisRotationTimer = null;
  }
  if (dropHoldTimeout) {
    clearTimeout(dropHoldTimeout);
    dropHoldTimeout = null;
  }
  if (touchDropInterval) {
    clearTimeout(touchDropInterval);
    touchDropInterval = null;
  }
  tetrisLastMoveTime = 0;
  
  // 🧀 Clear cheese particles when starting new game
  cheeseParticles.clear();
  
  // 📱 PREVENT SCREEN SWIPE (like Snake!)
  document.body.style.overflow = "hidden";
  console.log('📱 Screen swipe prevented - Tetris active');
  
  // 🚨 BUG #263 FIX: Disable all page links/buttons when game starts (except game controls and modal buttons)
  document.querySelectorAll('a, button').forEach(el => {
    // Skip game control buttons (but NOT guide button!)
    if (el.id && el.id.includes('tetris') && !el.id.includes('guide')) {
      return; // Keep game controls enabled (pause, start, etc.)
    }
    // Skip buttons inside modals
    const parentModal = el.closest('[id*="modal"]') || el.closest('[class*="modal"]');
    if (parentModal) {
      return; // Keep modal buttons enabled
    }
    // Skip if button has onclick with game functions
    if (el.onclick && (el.textContent.includes('Play Again') || el.textContent.includes('Restart'))) {
      return; // Keep modal action buttons enabled
    }
    // Disable everything else (guide button, page links, etc.)
    el.style.pointerEvents = 'none';
    el.style.opacity = '0.5';
  });
  console.log('🔒 Page links/buttons disabled - Tetris game started (guide button blocked)');
  
  if (!tetrisScoreDisplay) {
    console.log('⚠️ Score display element not found - creating fallback');
  }

  const gridWidth = 10;
  const gridHeight = 20;
  const blockSize = 20;

  // Reset global score for new game
  score = 0;
  let linesClearedTotal = 0;
  let piecesDropped = 0;
  let tetrisClears = 0;
  let dropInterval = 500;
  const grid = Array.from({ length: gridHeight }, () => Array(gridWidth).fill(0));
  
  // 🏆 Achievement tracking to prevent duplicate checks
  let achievementsCheckedThisGame = new Set();
  
  // 🎉 Achievement popups array (Inside Game Scope)
  let achievementPopups = [];

  const pieces = [
    [[1, 1, 1], [0, 1, 0]],     // T
    [[2, 2], [2, 2]],           // O
    [[0, 3, 3], [3, 3, 0]],     // S
    [[4, 4, 0], [0, 4, 4]],     // Z
    [[5, 5, 5, 5]],             // I
    [[7, 0], [7, 0], [7, 7]],   // L
    [[0, 8], [0, 8], [8, 8]],   // J ← mirrored L block
    [[6]]                       // 💣
  ];

  // 👑 SEASON 5: TETRIS BOSS MODE SYSTEM (Like Snake Bosses!)
  const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
  
  const tetrisBossConfig = {
    // Test mode vs Production spawn intervals (in total lines cleared) - 9 BOSSES LIKE SNAKE!
    spawnIntervals: isLocalhost 
      ? [3, 10, 20, 35, 55, 80, 110, 145, 185]  // Test: Starting at 3, progressive spacing
      : [10, 30, 60, 100, 150, 210, 280, 360, 450], // Production: More spaced out
    
    // Boss names (colorful and fun!) - 9 PROGRESSIVE BOSSES
    names: [
      '🧀 Cheese Block King',      // Boss 1 - Intro
      '👑 Tetris Emperor',          // Boss 2 - Easy
      '⚡ Lightning Lord',           // Boss 3 - Medium
      '🌟 Galaxy Master',            // Boss 4 - Challenging
      '💎 Diamond Deity',            // Boss 5 - Hard
      '🔥 Inferno Architect',        // Boss 6 - Very Hard
      '🌊 Tsunami Titan',            // Boss 7 - Extreme
      '💀 Shadow Overlord',          // Boss 8 - Brutal
      '🏆 ULTIMATE CHEESE GOD'       // Boss 9 - ULTIMATE!
    ],
    
    // Boss colors (vibrant themed colors!) - 9 UNIQUE COLORS
    colors: ['#FFD700', '#9370DB', '#00CED1', '#FF1493', '#00FF00', '#FF4500', '#1E90FF', '#8B008B', '#FF0000'],
    
    // Boss mechanics (progressive difficulty) - 9 BOSSES
    frozenPercent: [15, 20, 25, 30, 35, 40, 45, 50, 60],  // Progressive freeze rate
    giantChance: [20, 25, 30, 35, 40, 45, 50, 60, 70],     // Progressive giant rate
    requiredLines: [5, 7, 9, 11, 13, 15, 18, 21, 25],      // Progressive line requirements
    rewards: [50, 100, 150, 200, 300, 400, 550, 750, 1000] // Progressive rewards (BIG for final boss!)
  };
  
  // Boss state tracking
  let currentBoss = null;
  let bossLinesCleared = 0;
  let totalBossesDefeated = 0;

  let nextPiece = randomPiece();
  let current = {
    shape: nextPiece.shape, // Extract shape from piece object
    isFrozen: nextPiece.isFrozen, // Track frozen status
    isGiant: nextPiece.isGiant, // Track giant status
    isBomb: nextPiece.isBomb, // Track bomb status
    row: 0,
    col: 3,
    timer: null
  };
  nextPiece = randomPiece();

  // 🚀 SEASON 5: FROZEN BLOCK SYSTEM (like Snake Mad Mode!)
  function randomPiece() {
    // 💣 BOSS MODE: More bombs during boss battles!
    const bombChance = currentBoss ? 0.25 : 0.1; // Boss: 25%, Normal: 10%
    const isExplosive = Math.random() < bombChance;
    let piece = isExplosive ? [[6]] : pieces[Math.floor(Math.random() * pieces.length)];
    let isBomb = isExplosive;
    
    // 👑 BOSS MODE: Adjust frozen chance and check for giant blocks
    let frozenChance = isLocalhost ? 0.15 : 0.05; // Normal: Test 15%, Prod 5% (REDUCED - was too tricky!)
    let isGiant = false;
    
    if (currentBoss) {
      // During boss battle: Higher frozen chance based on boss level
      const bossIndex = totalBossesDefeated % tetrisBossConfig.names.length;
      frozenChance = tetrisBossConfig.frozenPercent[bossIndex] / 100;
      
      // Check for giant block during boss
      const giantChance = tetrisBossConfig.giantChance[bossIndex] / 100;
      isGiant = Math.random() < giantChance;
      
      if (isGiant) {
        console.log(`🧀 GIANT BLOCK spawned during boss! (${tetrisBossConfig.giantChance[bossIndex]}% chance)`);
        piece = makeGiantPiece(piece);
        
        // 💣 GIANT BOMB: If this is a bomb, make it a GIANT BOMB!
        if (isBomb) {
          console.log('💣 GIANT BOMB created! (2x2 = 4x explosion radius!)');
        }
      }
    }
    
    // ❄️ FROZEN BLOCK CHANCE
    const isFrozen = Math.random() < frozenChance;
    
    if (isFrozen) {
      console.log(`❄️ FROZEN BLOCK spawned! (${currentBoss ? 'BOSS' : 'NORMAL'} mode, ${Math.round(frozenChance * 100)}% chance)`);
    }
    
    return { shape: piece, isFrozen: isFrozen, isGiant: isGiant, isBomb: isBomb };
  }
  
  // 🧀 Make a piece GIANT (1.5x size - better balance!)
  function makeGiantPiece(normalPiece) {
    // For 1.5x size, we add extra cells strategically
    // This gives a bigger piece without being overwhelming
    const giant = [];
    
    normalPiece.forEach((row, rowIndex) => {
      const newRow = [];
      row.forEach((cell, colIndex) => {
        newRow.push(cell);
        // Add extra column every other cell for 1.5x width
        if (colIndex % 2 === 0 && colIndex < row.length - 1) {
          newRow.push(cell);
        }
      });
      giant.push(newRow);
      
      // Add extra row every other row for 1.5x height
      if (rowIndex % 2 === 0 && rowIndex < normalPiece.length - 1) {
        giant.push([...newRow]);
      }
    });
    
    return giant;
  }

  function explode(centerX, centerY, isGiantBomb = false) {
    // 💣 GIANT BOMB: 2x explosion radius! (4x4 area instead of 3x3)
    const radius = isGiantBomb ? 2 : 1;
    
    console.log(`💥 EXPLODING ${isGiantBomb ? 'GIANT BOMB' : 'NORMAL BOMB'} at (${centerX}, ${centerY}) with radius ${radius}`);
    
    for (let y = -radius; y <= radius; y++) {
      for (let x = -radius; x <= radius; x++) {
        const ny = centerY + y;
        const nx = centerX + x;
        if (ny >= 0 && ny < gridHeight && nx >= 0 && nx < gridWidth && grid[ny]?.[nx]) {
          grid[ny][nx] = 0;
        }
      }
    }
    
    // 🎆 Extra particles for giant bomb!
    const canvas = document.getElementById("tetris-canvas");
    if (canvas && isGiantBomb) {
      cheeseParticles.createCheeseParticles(10, canvas.width, canvas.height); // Giant explosion particles!
    }
    
    window.tetrisDraw();
  }

  // --- Drawing blocks: PNG if available, else color ---
function drawBlock(x, y, val) {
  context.save();

  // 💣 Bomb glow (active countdown)
  if (activeExplosive && activeExplosive.x === x && activeExplosive.y === y) {
    const timeElapsed = (Date.now() - activeExplosive.start) / 1000;
    const remaining = activeExplosive.countdown - timeElapsed;
    const intensity = Math.max(0, Math.min(1, 1 - remaining / activeExplosive.countdown));
    context.shadowColor = '#facc15';
    context.shadowBlur = 10 + 30 * intensity;
  }

  const img = blockImages[val];
  if (img && img.complete) {
    context.drawImage(img, x * blockSize, y * blockSize, blockSize, blockSize);
  } else {
    context.fillStyle = colors[val] || "#FFFFFF";
    context.fillRect(x * blockSize, y * blockSize, blockSize, blockSize);

    // 👁️ Optional stroke for clarity
    context.strokeStyle = "#1f2937";
    context.strokeRect(x * blockSize + 0.5, y * blockSize + 0.5, blockSize - 1, blockSize - 1);
  }

  context.restore();
}

  // --- Main draw loop ---
  window.tetrisDraw = function draw() {
    context.clearRect(0, 0, canvas.width, canvas.height);
    context.save();
    context.translate(0.5, 0.5);
    grid.forEach((row, y) =>
      row.forEach((val, x) => {
        if (val) drawBlock(x, y, val);
      })
    );
    current.shape.forEach((row, y) =>
      row.forEach((val, x) => {
        if (val) {
          drawBlock(current.col + x, current.row + y, val);
          
          // ❄️ FROZEN OVERLAY on current piece
          if (current.isFrozen) {
            context.fillStyle = 'rgba(59, 130, 246, 0.4)';
            context.fillRect((current.col + x) * blockSize, (current.row + y) * blockSize, blockSize, blockSize);
            context.strokeStyle = "#3b82f6";
            context.lineWidth = 2;
            context.strokeRect((current.col + x) * blockSize, (current.row + y) * blockSize, blockSize, blockSize);
          }
        }
      })
    );
    context.restore();
    
    // ❄️ FROZEN INDICATOR on top of canvas
    if (current.isFrozen) {
      context.save();
      context.fillStyle = '#3b82f6';
      context.font = 'bold 14px Arial';
      context.textAlign = 'center';
      context.shadowBlur = 10;
      context.shadowColor = '#3b82f6';
      context.fillText('❄️ FROZEN ❄️', canvas.width / 2, 15);
      context.restore();
    }
    
    // 👑 BOSS MODE INDICATOR on canvas
    if (currentBoss) {
      context.save();
      context.fillStyle = currentBoss.color;
      context.font = 'bold 12px Arial';
      context.textAlign = 'center';
      context.shadowBlur = 10;
      context.shadowColor = currentBoss.color;
      context.fillText(currentBoss.name, canvas.width / 2, 30);
      
      // Progress bar
      const barWidth = canvas.width - 20;
      const barHeight = 8;
      const barX = 10;
      const barY = 35;
      const progress = bossLinesCleared / currentBoss.requiredLines;
      
      // Background
      context.fillStyle = 'rgba(0,0,0,0.5)';
      context.fillRect(barX, barY, barWidth, barHeight);
      
      // Progress fill
      context.fillStyle = currentBoss.color;
      context.fillRect(barX, barY, barWidth * progress, barHeight);
      
      // Text
      context.fillStyle = '#FFD700';
      context.font = 'bold 10px Arial';
      context.fillText(`${bossLinesCleared}/${currentBoss.requiredLines} Lines`, canvas.width / 2, 52);
      
      context.restore();
    }
    
    // 🧀 Update and draw cheese particles
    cheeseParticles.update();
    cheeseParticles.draw(context);
    
    // 🏆 Draw achievement popups on canvas (like Space Invaders)
    drawAchievementPopups();
  }
  
  // ✅ Initial draw after function is defined
  window.tetrisDraw();

  // Make game functions globally accessible for touch controls
  window.tetrisCurrent = current;
  window.tetrisCollide = collide;
  
  function rotatePiece() {
    if (isTetrisPaused) return; // Prevent rotation while paused
    
    // ❄️ FROZEN BLOCK: Can't rotate!
    if (current.isFrozen) {
      console.log('❄️ FROZEN BLOCK - Rotation blocked!');
      showFrozenWarning();
      tetrisSounds.playSound('error'); // Optional: error sound
      return;
    }

    const rotated = current.shape[0].map((_, i) =>
      current.shape.map(row => row[i]).reverse()
    );
    if (!collide(rotated, current.row, current.col)) {
      current.shape = rotated;
    }
  }
  
  window.tetrisRotatePiece = rotatePiece;

  // ❄️ FROZEN BLOCK WARNING POPUP
  function showFrozenWarning() {
    const existing = document.getElementById('frozen-warning');
    if (existing) return; // Don't spam warnings
    
    const warning = document.createElement('div');
    warning.id = 'frozen-warning';
    warning.style.cssText = `
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: linear-gradient(45deg, rgba(59, 130, 246, 0.95), rgba(147, 197, 253, 0.95));
      color: white;
      padding: 15px 25px;
      border-radius: 10px;
      font-weight: bold;
      font-size: 18px;
      z-index: 10000;
      box-shadow: 0 0 30px rgba(59, 130, 246, 0.8);
      border: 3px solid rgba(255, 255, 255, 0.9);
    `;
    warning.innerHTML = '❄️ FROZEN! No Rotation! ❄️';
    document.body.appendChild(warning);
    
    setTimeout(() => {
      if (warning.parentNode) warning.parentNode.removeChild(warning);
    }, 800);
  }

  // --- Next block preview: PNG if available, else color ---
  function renderNextBlock(pieceObj) {
    if (!nextCtx || !pieceObj) return;
    const shape = pieceObj.shape || pieceObj; // Support both object and array
    const isFrozen = pieceObj.isFrozen || false;
    
    nextCtx.clearRect(0, 0, nextCanvas.width, nextCanvas.height);
    
    // ❄️ FROZEN INDICATOR on next piece preview
    if (isFrozen) {
      nextCtx.fillStyle = 'rgba(59, 130, 246, 0.3)';
      nextCtx.fillRect(0, 0, nextCanvas.width, nextCanvas.height);
      nextCtx.fillStyle = '#3b82f6';
      nextCtx.font = 'bold 12px Arial';
      nextCtx.textAlign = 'center';
      nextCtx.fillText('❄️ FROZEN', nextCanvas.width / 2, 12);
      nextCtx.textAlign = 'left';
    }
    
    const offsetX = Math.floor((4 - shape[0].length) / 2);
    const offsetY = Math.floor((4 - shape.length) / 2);
    shape.forEach((row, y) => {
      row.forEach((val, x) => {
        if (val) {
          const img = blockImages[val];
          if (img && img.complete) {
            nextCtx.drawImage(img, (x + offsetX) * 20, (y + offsetY) * 20, 20, 20);
          } else {
            nextCtx.fillStyle = colors[val];
            nextCtx.fillRect((x + offsetX) * 20, (y + offsetY) * 20, 20, 20);
            nextCtx.strokeStyle = "#1f2937";
            nextCtx.strokeRect((x + offsetX) * 20 + 0.5, (y + offsetY) * 20 + 0.5, 19, 19);
          }
          
          // ❄️ FROZEN OVERLAY on blocks
          if (isFrozen) {
            nextCtx.fillStyle = 'rgba(59, 130, 246, 0.5)';
            nextCtx.fillRect((x + offsetX) * 20, (y + offsetY) * 20, 20, 20);
            nextCtx.strokeStyle = "#3b82f6";
            nextCtx.lineWidth = 2;
            nextCtx.strokeRect((x + offsetX) * 20, (y + offsetY) * 20, 20, 20);
          }
        }
      });
    });
  }

  // --- Drawing blocks: PNG if available, else color ---
  function drawBlock(x, y, val) {
    context.save();

    // 💣 Bomb glow (active countdown)
    if (activeExplosive && activeExplosive.x === x && activeExplosive.y === y) {
      const timeElapsed = (Date.now() - activeExplosive.start) / 1000;
      const remaining = activeExplosive.countdown - timeElapsed;
      const intensity = Math.max(0, Math.min(1, 1 - remaining / activeExplosive.countdown));
      context.shadowColor = '#facc15';
      context.shadowBlur = 10 + 30 * intensity;
    }

    const img = blockImages[val];
    if (img && img.complete) {
      context.drawImage(img, x * blockSize, y * blockSize, blockSize, blockSize);
    } else {
      context.fillStyle = colors[val] || "#FFFFFF";
      context.fillRect(x * blockSize, y * blockSize, blockSize, blockSize);

      // 👁️ Optional stroke for clarity
      context.strokeStyle = "#1f2937";
      context.strokeRect(x * blockSize + 0.5, y * blockSize + 0.5, blockSize - 1, blockSize - 1);
    }

    context.restore();
  }

function collide(shape, row, col) {
          return shape.some((r, y) =>
            r.some((v, x) => {
              const ny = row + y;
              const nx = col + x;
              return v && (ny >= gridHeight || nx < 0 || nx >= gridWidth || (ny >= 0 && grid[ny][nx]));
            })
          );
        }
      
        function merge() {
          current.shape.forEach((row, y) =>
            row.forEach((val, x) => {
              if (val) grid[current.row + y][current.col + x] = val;
            })
          );
          piecesDropped++; // Track pieces dropped
        }
      
      function clearLines() {
        console.log('🔍 clearLines() called');
        console.log(`🔍 Current total lines cleared: ${linesClearedTotal}`);
        console.log(`🔍 Boss active: ${currentBoss ? 'YES' : 'NO'}, Boss lines: ${bossLinesCleared}`);
        
        let lines = 0;
        let bombDefusedLines = 0; // Track bomb-defused lines separately
        for (let y = gridHeight - 1; y >= 0; y--) {
          const isFullLine = grid[y].every(v => v !== 0);
          
          if (isFullLine) {
            console.log(`✅ FULL LINE DETECTED at row ${y}!`);
            console.log(`📊 Row ${y} contents:`, grid[y]);
            // 🧠 Check for bomb BEFORE removing the row
            if (grid[y].includes(6)) {
              console.log(`💣 BOMB LINE - will score separately`);
              // 🚨 BOMB DEFUSED: Clear the entire line (bomb is disarmed, not exploded)
              grid.splice(y, 1);
              grid.unshift(Array(gridWidth).fill(0));
              
              // 🎆 Add sparkles effect for bomb defusal
              createBombDefusalSparkles();
              
              showBombDefusedPopup();
              
              // 🚨 FIX: Bomb defusal gets special scoring (NOT regular line clearing bonus)
              // Bomb defusal = 10 DSPOINC + role bonus (no double counting)
              const baseBombScore = 10; // Bonus for defusing bomb (reduced for balance)
              const roleMultiplier = getRoleScoreMultiplier();
              const roleBombBonus = Math.round(baseBombScore * (roleMultiplier - 1)); // Changed Math.floor to Math.round for fairer bonuses
              score += baseBombScore + roleBombBonus;
              
              // 🏆 Update score display with role bonus
              updateTetrisScoreDisplay();
              
              bombDefusedLines++; // Track bomb-defused lines (ONLY bomb counter, not lines)
              y++; // Re-check same row index
              continue;
            }
      
            // Regular line clear
            console.log(`📊 REGULAR LINE - will score in scoring block`);
            grid.splice(y, 1);
            grid.unshift(Array(gridWidth).fill(0));
            lines++;
            y++; // Re-check same row index
          }
        }
        
        console.log(`🔍 clearLines() finished - lines: ${lines}, bombDefusedLines: ${bombDefusedLines}`);
      
        // 🏆 SCORING: Apply scores and effects after all lines are detected
        if (lines > 0 || bombDefusedLines > 0) {
          console.log(`🏆 SCORING BLOCK ENTERED! lines: ${lines}, bombDefusedLines: ${bombDefusedLines}`);
          // 🎵 Play line clear sound
          tetrisSounds.playSound('lineClear');
          
          // 🧀 Create cheese particles for line clear effect
          const canvas = document.getElementById("tetris-canvas");
          if (canvas) {
            cheeseParticles.createCheeseParticles(lines + bombDefusedLines, canvas.width, canvas.height);
          }
          
          // 🎵 Check for level up (every 20 lines)
          const oldLevel = Math.floor(linesClearedTotal / 20);
          const oldTotal = linesClearedTotal;
          linesClearedTotal += lines + bombDefusedLines; // Count both types of lines
          const newLevel = Math.floor(linesClearedTotal / 20);
          
          console.log(`📊 LINES UPDATE: ${oldTotal} → ${linesClearedTotal} (added ${lines + bombDefusedLines})`);
          
          if (newLevel > oldLevel) {
            tetrisSounds.playSound('levelUp');
          }
          
          // 🏆 Apply regular line clearing scoring to non-bomb lines
          console.log(`🔍 About to check if lines > 0: lines=${lines}, type=${typeof lines}`);
          if (lines > 0) {
            console.log(`🏆 SCORING: Regular line scoring triggered for ${lines} lines`);
            
            // 🚀 SEASON 5: MULTI-LINE BONUS SYSTEM (rewards clearing more lines!)
            // Base: 2 DSPOINC per line
            // Bonus: Extra points for 2, 3, or 4 lines cleared at once
            let baseScore = lines * 2; // Base 2 per line
            let multiLineBonus = 0;
            
            if (lines === 2) {
              multiLineBonus = 1; // Double = +1 bonus (5 total)
            } else if (lines === 3) {
              multiLineBonus = 3; // Triple = +3 bonus (9 total)
            } else if (lines === 4) {
              multiLineBonus = 8; // TETRIS! = +8 bonus (16 total!)
            }
            
            const totalBase = baseScore + multiLineBonus;
            const roleMultiplier = getRoleScoreMultiplier();
            const roleBonus = Math.round(totalBase * (roleMultiplier - 1)); // Fair rounding
            
            console.log(`🏆 SEASON 5 Multi-Line Scoring: ${lines} lines | Base: ${baseScore} | Bonus: ${multiLineBonus} | Total: ${totalBase} | Role: ${roleMultiplier}x | RoleBonus: ${roleBonus}`);
            console.log(`🏆 Score before: ${score}`);
            score += totalBase + roleBonus;
            console.log(`🏆 Score after: ${score}`);
            
            // 🏆 Update score display immediately
            updateTetrisScoreDisplay();
          }
          
          // 🏆 Track Tetris clears (4 lines at once)
          if (lines === 4) {
            tetrisClears++;
          }
          
          // 🏆 Check achievements immediately when lines are cleared
          checkTetrisAchievements(localStorage.getItem('discord_id') || '328601656659017732', score, linesClearedTotal, Math.floor(linesClearedTotal / 20), piecesDropped, tetrisClears, lines);
          
          // ⏩ Speed up every 20 lines
          if (linesClearedTotal % 20 === 0) {
            dropInterval = Math.max(100, dropInterval - 50);
            clearInterval(gameInterval);
            gameInterval = setInterval(drop, dropInterval);
          }
          
          // 👑 BOSS MODE: Check if boss should spawn or if boss is defeated
          if (currentBoss) {
            // Boss active: Check if boss is defeated
            bossLinesCleared += lines + bombDefusedLines; // Count ALL lines (regular + bomb)
            console.log(`👑 Boss lines cleared: ${bossLinesCleared}/${currentBoss.requiredLines} (regular: ${lines}, bombs: ${bombDefusedLines})`);
            
            if (bossLinesCleared >= currentBoss.requiredLines) {
              // Boss defeated!
              defeatBoss();
            }
          } else {
            // No boss: Check if boss should spawn
            const nextBossIndex = totalBossesDefeated % tetrisBossConfig.spawnIntervals.length;
            const nextBossSpawn = tetrisBossConfig.spawnIntervals[nextBossIndex];
            
            console.log(`🔍 Boss Spawn Check: Lines ${linesClearedTotal}/${nextBossSpawn}, Boss Index: ${nextBossIndex}, Defeated: ${totalBossesDefeated}`);
            
            if (linesClearedTotal >= nextBossSpawn && totalBossesDefeated === nextBossIndex) {
              // Spawn boss!
              console.log(`🚀 BOSS ${nextBossIndex + 1} SPAWNING NOW!`);
              spawnBoss(nextBossIndex);
            }
          }
        }
      }
      
  // 👑 BOSS SPAWN FUNCTION (like Snake bosses!)
  function spawnBoss(bossIndex) {
    const bossName = tetrisBossConfig.names[bossIndex];
    const bossColor = tetrisBossConfig.colors[bossIndex];
    const requiredLines = tetrisBossConfig.requiredLines[bossIndex];
    const reward = tetrisBossConfig.rewards[bossIndex];
    
    currentBoss = {
      name: bossName,
      color: bossColor,
      requiredLines: requiredLines,
      reward: reward,
      bossIndex: bossIndex
    };
    
    bossLinesCleared = 0;
    
    console.log(`👑 BOSS SPAWNED: ${bossName} | Lines: ${requiredLines} | Reward: ${reward} DSPOINC`);
    
    // ⏸️ PAUSE GAME during boss spawn countdown (like Snake!)
    isTetrisPaused = true;
    clearInterval(gameInterval);
    console.log('⏸️ Game PAUSED for boss spawn countdown!');
    
    // Show boss spawn notification with countdown (like Snake!)
    showBossSpawnNotification(bossName, bossColor, requiredLines, reward);
    
    // ⏱️ Resume game after countdown (4.9 seconds total: 1s wait + 3s countdown + 0.8s GO!)
    setTimeout(() => {
      // ▶️ Resume game
      isTetrisPaused = false;
      clearInterval(gameInterval); // always reset interval
      gameInterval = setInterval(drop, dropInterval);
      console.log('▶️ Game RESUMED after boss spawn countdown!');
    }, 4900); // Match countdown duration (1s wait + 3s countdown + 0.8s GO! + buffer)
  }
  
  // 👑 BOSS DEFEAT FUNCTION
  function defeatBoss() {
    const bossName = currentBoss.name;
    const reward = currentBoss.reward;
    const bossColor = currentBoss.color;
    
    console.log(`🎉 BOSS DEFEATED: ${bossName} | Reward: ${reward} DSPOINC`);
    
    // ⏸️ PAUSE GAME during victory countdown (like Snake!)
    isTetrisPaused = true;
    clearInterval(gameInterval);
    console.log('⏸️ Game PAUSED for boss victory celebration!');
    
    // Add boss reward to score (with role multiplier!)
    const roleMultiplier = getRoleScoreMultiplier();
    const totalReward = Math.round(reward * roleMultiplier);
    const oldScore = score;
    score += totalReward;
    
    console.log(`💰 BOSS REWARD APPLIED: ${oldScore} + ${totalReward} = ${score} DSPOINC`);
    console.log(`💰 Base reward: ${reward} | Role multiplier: ${roleMultiplier}x | Total: ${totalReward}`);
    
    updateTetrisScoreDisplay();
    
    totalBossesDefeated++;
    console.log(`🏆 Total bosses defeated: ${totalBossesDefeated}`);
    
    // 💥 EPIC BOSS DEFEAT: Clear entire field! (All explode!)
    console.log('💥 BOSS DEFEATED - CLEARING ENTIRE FIELD!');
    for (let y = 0; y < gridHeight; y++) {
      for (let x = 0; x < gridWidth; x++) {
        grid[y][x] = 0;
      }
    }
    
    // 🎆 Create massive cheese particle explosion!
    const canvas = document.getElementById("tetris-canvas");
    if (canvas) {
      cheeseParticles.createCheeseParticles(20, canvas.width, canvas.height); // Epic explosion!
    }
    
    // 🎵 Play victory sound
    tetrisSounds.playSound('levelUp');
    
    // Show boss victory notification with countdown
    showBossVictoryNotification(bossName, bossColor, totalReward);
    
    // ⏱️ Resume game after countdown (4.9 seconds total)
    setTimeout(() => {
      // ⚡ Speed up game after boss (faster gameplay!)
      dropInterval = Math.max(100, dropInterval - 100); // Bigger speed boost!
      clearInterval(gameInterval);
      gameInterval = setInterval(drop, dropInterval);
      console.log(`⚡ Game speed increased! New interval: ${dropInterval}ms`);
      
      // ▶️ Resume game
      isTetrisPaused = false;
      console.log('▶️ Game RESUMED after boss victory!');
    }, 4900); // Match countdown duration (3s countdown + 0.8s GO! + buffer)
    
    // Clear boss state
    currentBoss = null;
    bossLinesCleared = 0;
  }
  
  // 📢 BOSS SPAWN NOTIFICATION (with countdown like Snake!)
  function showBossSpawnNotification(bossName, bossColor, requiredLines, reward) {
    const notification = document.createElement('div');
    notification.id = 'boss-spawn-notification';
    notification.style.cssText = `
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: linear-gradient(135deg, rgba(0,0,0,0.95), rgba(30,30,30,0.95));
      color: white;
      padding: 20px 30px;
      border-radius: 15px;
      font-weight: bold;
      z-index: 10000;
      box-shadow: 0 0 40px ${bossColor};
      border: 4px solid ${bossColor};
      text-align: center;
      max-width: 90vw;
      width: 400px;
    `;
    
    notification.innerHTML = `
      <div style="font-size: clamp(20px, 5vw, 32px); margin-bottom: 10px; color: ${bossColor};">${bossName}</div>
      <div style="font-size: clamp(14px, 3.5vw, 18px); margin-bottom: 8px;">Clear ${requiredLines} lines to win!</div>
      <div style="font-size: clamp(12px, 3vw, 16px); color: #FFD700;">Reward: +${reward} DSPOINC</div>
      <div id="boss-countdown" style="font-size: clamp(32px, 8vw, 48px); margin-top: 15px; color: ${bossColor};">3</div>
    `;
    
    document.body.appendChild(notification);
    
    // Wait 1 second before starting countdown (let player see boss info!)
    setTimeout(() => {
      const countdownEl = document.getElementById('boss-countdown');
      if (!countdownEl) return;
      
      // Countdown: 3, 2, 1, GO!
      let count = 3;
      
      const countdownInterval = setInterval(() => {
        count--;
        if (count > 0) {
          countdownEl.textContent = count;
          countdownEl.style.color = bossColor;
        } else {
          countdownEl.textContent = 'GO!';
          countdownEl.style.color = '#FFD700'; // Gold for GO!
          
          setTimeout(() => {
            if (notification.parentElement) notification.remove();
          }, 800);
          
          clearInterval(countdownInterval);
        }
      }, 1000);
    }, 1000); // Start countdown after 1 second
  }
  
  // 🎉 BOSS VICTORY NOTIFICATION (with countdown!)
  function showBossVictoryNotification(bossName, bossColor, totalReward) {
    const notification = document.createElement('div');
    notification.id = 'boss-victory-notification';
    notification.style.cssText = `
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: linear-gradient(135deg, rgba(0,128,0,0.95), rgba(0,200,0,0.95));
      color: white;
      padding: 20px 30px;
      border-radius: 15px;
      font-weight: bold;
      z-index: 10000;
      box-shadow: 0 0 40px ${bossColor};
      border: 4px solid #FFD700;
      text-align: center;
      max-width: 90vw;
      width: 400px;
    `;
    
    notification.innerHTML = `
      <div style="font-size: clamp(24px, 6vw, 36px); margin-bottom: 10px;">🎉 BOSS DEFEATED! 🎉</div>
      <div style="font-size: clamp(16px, 4vw, 24px); margin-bottom: 8px; color: ${bossColor};">${bossName}</div>
      <div style="font-size: clamp(14px, 3.5vw, 20px); color: #FFD700;">+${totalReward} DSPOINC!</div>
      <div id="victory-countdown" style="font-size: clamp(32px, 8vw, 48px); margin-top: 15px; color: #FFD700;">3</div>
    `;
    
    document.body.appendChild(notification);
    
    // Wait 1 second before starting countdown (let player see victory message!)
    setTimeout(() => {
      const countdownEl = document.getElementById('victory-countdown');
      if (!countdownEl) return;
      
      // Countdown: 3, 2, 1, GO!
      let count = 3;
      
      const countdownInterval = setInterval(() => {
        count--;
        if (count > 0) {
          countdownEl.textContent = count;
          countdownEl.style.color = '#FFD700';
        } else {
          countdownEl.textContent = 'GO!';
          countdownEl.style.color = '#10b981'; // Green for GO!
          
          setTimeout(() => {
            if (notification.parentElement) notification.remove();
          }, 800);
          
          clearInterval(countdownInterval);
        }
      }, 1000);
    }, 1000); // Start countdown after 1 second
  }
      
// 🔔 Defused popup UI logic
function showBombDefusedPopup() {
  // Create bomb defused notification dynamically
  const notification = document.createElement('div');
  notification.className = 'fixed top-4 right-4 bg-yellow-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in max-w-sm';
  
  // Safe innerHTML assignment
  try {
    notification.innerHTML = `
      <div class="flex items-center gap-2">
        <span>💣</span>
        <span>Bomb Defused! +10 DSPOINC</span>
        <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-white hover:text-gray-200 text-lg">×</button>
      </div>
    `;
  } catch (error) {
    console.log('⚠️ Could not create bomb defused popup:', error);
    return; // Exit gracefully if popup creation fails
  }
  
  document.body.appendChild(notification);
  
  // Auto-remove after 1 second (much faster to not block gameplay)
  setTimeout(() => {
    if (notification.parentElement) {
      notification.remove();
    }
  }, 1000);
}

// 🛑 Pause Logic — now mobile compatible
let gameInterval;

const pauseBtn = document.getElementById("pause-tetris-btn");
if (pauseBtn) {
  pauseBtn.style.padding = "12px 24px";
  pauseBtn.style.fontSize = "18px";
  pauseBtn.style.touchAction = "manipulation";

  const pauseHandler = (e) => {
    e.preventDefault();

    isTetrisPaused = !isTetrisPaused;
    pauseBtn.textContent = isTetrisPaused ? "▶️ Resume" : "⏸️ Pause";

    if (isTetrisPaused) {
      // 📱 PAUSED: Allow screen swipe (like Snake!)
      document.body.style.overflow = "";
      console.log('📱 Game PAUSED - Screen swipe ENABLED (user can scroll)');
      clearInterval(gameInterval);
      
      // 🚨 BUG #263 FIX: Re-enable all page links/buttons when paused
      document.querySelectorAll('a, button').forEach(el => {
        el.style.pointerEvents = '';
        el.style.opacity = '';
      });
      console.log('🔓 Page links/buttons re-enabled during Tetris pause');
    } else {
      // 📱 RESUMED: Lock screen swipe again (like Snake!)
      document.body.style.overflow = "hidden";
      console.log('📱 Game RESUMED - Screen swipe LOCKED (no scrolling)');
      clearInterval(gameInterval); // always reset interval
      gameInterval = setInterval(drop, dropInterval);
      drop(); // redraw immediately
      
      // 🚨 BUG #263 FIX: Disable all page links/buttons during active gameplay (except modal buttons)
      document.querySelectorAll('a, button').forEach(el => {
        // Skip game control buttons (but NOT guide button!)
        if (el.id && el.id.includes('tetris') && !el.id.includes('guide')) {
          return; // Keep game controls enabled (pause, start, etc.)
        }
        // Skip buttons inside modals
        const parentModal = el.closest('[id*="modal"]') || el.closest('[class*="modal"]');
        if (parentModal) {
          return; // Keep modal buttons enabled
        }
        // Skip if button has onclick with game functions
        if (el.onclick && (el.textContent.includes('Play Again') || el.textContent.includes('Restart'))) {
          return; // Keep modal action buttons enabled
        }
        // Disable everything else (guide button, page links, etc.)
        el.style.pointerEvents = 'none';
        el.style.opacity = '0.5';
      });
      console.log('🔒 Page links/buttons disabled during Tetris gameplay (guide button blocked)');
    }
  };

  pauseBtn.addEventListener("click", pauseHandler);
  pauseBtn.addEventListener("touchend", pauseHandler, { passive: false });
}

// Add keyboard event listener for movement controls
document.addEventListener("keydown", e => {
  // 🎮 P KEY PAUSE/UNPAUSE (BUG #263)
  if (e.key === 'p' || e.key === 'P') {
    e.preventDefault();
    const pauseBtn = document.getElementById('pause-tetris-btn');
    if (pauseBtn) {
      pauseBtn.click(); // Trigger existing pause/unpause logic
      console.log('🎮 P key pressed - toggling pause state');
    }
    return;
  }
  
  if (isTetrisPaused) return; // Prevent movement while paused

  switch (e.key) {
    case "ArrowLeft":
    case "a":
      if (typeof window.tetrisCollide === 'function' && typeof window.tetrisCurrent !== 'undefined' && window.tetrisCurrent.shape) {
        if (!window.tetrisCollide(window.tetrisCurrent.shape, window.tetrisCurrent.row, window.tetrisCurrent.col - 1)) {
          window.tetrisCurrent.col--;
          window.tetrisDraw();
        }
      }
      break;
    case "ArrowRight":
    case "d":
      if (typeof window.tetrisCollide === 'function' && typeof window.tetrisCurrent !== 'undefined' && window.tetrisCurrent.shape) {
        if (!window.tetrisCollide(window.tetrisCurrent.shape, window.tetrisCurrent.row, window.tetrisCurrent.col + 1)) {
          window.tetrisCurrent.col++;
          window.tetrisDraw();
        }
      }
      break;
    case "ArrowDown":
    case "s":
      if (typeof window.tetrisCollide === 'function' && typeof window.tetrisCurrent !== 'undefined' && window.tetrisCurrent.shape) {
        if (!window.tetrisCollide(window.tetrisCurrent.shape, window.tetrisCurrent.row + 1, window.tetrisCurrent.col)) {
          window.tetrisCurrent.row++;
          window.tetrisDraw();
        }
      }
      break;
    case "ArrowUp":
    case "w":
    case " ":
      if (typeof window.tetrisRotatePiece === 'function') {
        window.tetrisRotatePiece();
        window.tetrisDraw();
      }
      break;
  }
});

// 🧱 Drop Function
function drop() {
  if (isTetrisPaused) return; // ⛔ Early return if paused

  if (!collide(current.shape, current.row + 1, current.col)) {
    current.row++;
  } else {
    // 💣 Bomb piece logic (normal or GIANT!)
    if (current.isBomb || (
      current.shape.length === 1 &&
      current.shape[0].length === 1 &&
      current.shape[0][0] === 6
    )) {
      const cx = current.col;
      const cy = current.row;
      const countdown = Math.floor(Math.random() * 30) + 1;
      const isGiantBomb = current.isGiant; // Track if giant bomb!

      activeExplosive = { x: cx, y: cy, countdown, start: Date.now(), isGiant: isGiantBomb };
      
      console.log(`💣 ${isGiantBomb ? 'GIANT BOMB' : 'NORMAL BOMB'} placed at (${cx}, ${cy}) - Explodes in ${countdown}s`);

      setTimeout(() => {
        if (grid[cy]?.[cx] === 6) {
          explode(cx, cy, isGiantBomb); // Pass giant flag to explode function!
        }
        activeExplosive = null;
      }, countdown * 1000);
    }

    merge();
    // 🎵 Play piece placement sound
    tetrisSounds.playSound('piecePlace');
    clearLines();

    current = {
      shape: nextPiece.shape, // Extract shape from piece object
      isFrozen: nextPiece.isFrozen, // Track frozen status
      isGiant: nextPiece.isGiant, // Track giant status
      isBomb: nextPiece.isBomb, // Track bomb status
      row: 0,
      col: 3
    };
    nextPiece = randomPiece();
    renderNextBlock(nextPiece); // Render with frozen indicator
    
    // 💬 Log special piece types
    if (nextPiece.isFrozen) {
      console.log('❄️ Next piece is FROZEN! Player will see frozen indicator!');
    }
    if (nextPiece.isGiant) {
      console.log('🧀 Next piece is GIANT! Player will see 2x size!');
    }
    if (nextPiece.isBomb && nextPiece.isGiant) {
      console.log('💣 Next piece is GIANT BOMB! Massive explosion incoming!');
    }
    
    // ✅ CRITICAL FIX: Update window.tetrisCurrent to point to the new piece
    window.tetrisCurrent = current;
    console.log('📱 CRITICAL: Updated window.tetrisCurrent for new piece');
    
    // ✅ FIX: Ensure touch controls remain active for new piece
    console.log('📱 New piece spawned - touch controls should remain active');
    console.log('📱 Current piece position:', current.row, current.col);
    console.log('📱 Touch functions available:', typeof window.tetrisDraw, typeof window.tetrisCollide, typeof window.tetrisCurrent);
    console.log('📱 window.tetrisCurrent points to:', window.tetrisCurrent === current ? 'NEW PIECE' : 'OLD PIECE');

      // 🚨 CRITICAL: Check for game over (blocks reached top)
if (collide(current.shape, current.row, current.col)) {
  clearInterval(gameInterval);
  gameInterval = null;
  isTetrisPaused = true;
  
  // 🚨 BUG #263 FIX: Re-enable all page links/buttons when game ends
  document.querySelectorAll('a, button').forEach(el => {
    el.style.pointerEvents = '';
    el.style.opacity = '';
  });
  console.log('🔓 Page links/buttons re-enabled - Tetris game over');
  
  onTetrisGameOver(score);

  // ✅ FIX: Get ALL modals with this ID and find the LOCAL one (in Tetris canvas area, like Snake!)
  const allModals = document.querySelectorAll("#game-over-modal");
  console.log(`🔍 Found ${allModals.length} game-over-modal elements`);
  
  let modal = null;
  let finalScoreText = null;
  
  // Find the LOCAL modal (inside Tetris canvas container, NOT the global fixed one)
  // The correct modal is the one that's ABSOLUTE positioned over the canvas (like Snake!)
  allModals.forEach((m, index) => {
    console.log(`🔍 Modal ${index}:`, m.parentElement?.className);
    // Check if this modal is the local one (has absolute positioning, NOT fixed)
    if (m.classList.contains('absolute') || (!m.classList.contains('fixed'))) {
      modal = m;
      finalScoreText = m.querySelector("#final-score-text");
      console.log(`✅ Found LOCAL modal at index ${index} (like Snake!)`);
    }
  });
  
  // Fallback: use first modal if none found
  if (!modal && allModals.length > 0) {
    modal = allModals[0]; // Use first modal (the local one)
    finalScoreText = modal.querySelector("#final-score-text");
    console.log('⚠️ Using fallback modal (first one)');
  }
  
  const pauseBtn = document.getElementById("pause-tetris-btn");

  if (modal && finalScoreText) {
    console.log('✅ Modal and score text found');
    const gameOverText = modal.querySelector('h2') || modal.querySelector('strong');
    if (gameOverText) {
      try {
        gameOverText.innerHTML = '🧠 GAME OVER';
      } catch (error) {
        console.log('⚠️ Could not update game over text:', error);
      }
    }
    try {
      finalScoreText.textContent = `You earned $${score} DSPOINC`;
      console.log(`✅ Final score set: ${score} DSPOINC`);
    } catch (error) {
      console.log('⚠️ Could not update final score text:', error);
    }
    
    modal.classList.remove('hidden');
    console.log('✅ Removed hidden class from modal');
    
    // ✅ CRITICAL FIX: Force display but keep original positioning (absolute over canvas, like Snake!)
    console.log('🎯 Forcing modal display with explicit styling');
    modal.style.display = 'flex';
    modal.style.zIndex = '999'; // High but not 9999 (stays in canvas area)
    
    // ✅ Verify modal is visible
    setTimeout(() => {
      const isVisible = modal.offsetParent !== null && window.getComputedStyle(modal).display !== 'none';
      console.log(`🔍 Modal visibility check: ${isVisible ? 'VISIBLE ✅' : 'NOT VISIBLE ❌'}`);
      
      if (!isVisible) {
        console.log('🚨 Modal not visible - showing fallback alert');
        alert(`🧠 GAME OVER\n\nYou earned $${score} DSPOINC!\n\nRefresh the page to play again.`);
      }
    }, 100);
    
    // 📱 RESTORE SCREEN SWIPE (allow scrolling again)
    document.body.style.overflow = "";
    console.log('📱 Screen swipe restored - Tetris ended');
    
    cleanupTouchControls();
  } else {
    console.log('❌ Game over modal or score text not found!');
    console.log('Modal:', modal);
    console.log('Score text:', finalScoreText);
    // ✅ CRITICAL FALLBACK: Always show alert if modal not found
    alert(`🧠 GAME OVER\n\nYou earned $${score} DSPOINC!\n\nRefresh the page to play again.`);
    
    // 📱 RESTORE SCREEN SWIPE
    document.body.style.overflow = "";
    
    cleanupTouchControls();
  }

  if (pauseBtn) {
    pauseBtn.textContent = "⏸️ Pause";
  }

  if (typeof loadCombinedLeaderboards === "function") {
    loadCombinedLeaderboards();
  }

  return;
}
  }

      window.tetrisDraw(); // ✅ Always redraw
}

      function onTetrisGameOver(finalScore) {
        // 🎮 Stop any active hold-to-drop
        if (tetrisIsHolding) {
          console.log('📱 Game over - stopping hold-to-drop');
          stopTetrisHold();
        }
        
        // 🎵 Play game over sound
        tetrisSounds.playSound('gameOver');
        
        // 🧀 Clear cheese particles on game over
        cheeseParticles.clear();
        
        let wallet = localStorage.getItem("walletAddress");
        let discordId = localStorage.getItem("discord_id");
        let discordName = localStorage.getItem("discord_name");
      
        // 🏆 Reset achievement tracking for new game
        achievementsCheckedThisGame.clear();
        
        // 🚨 CRITICAL FIX: Clear global popups like Snake
        if (window.tetrisAchievementPopups) {
          window.tetrisAchievementPopups = [];
        }
      
        // 🛠️ Mock fallback if testing locally
        if (!discordId) {
          discordId = "328601656659017732"; // Narrrf's Discord ID for testing
          discordName = "narrrf";
          localStorage.setItem("discord_id", discordId);
          localStorage.setItem("discord_name", discordName);
        }
      
        if (!wallet) {
      wallet = discordId; // fallback
    }

    // 🏆 Check and unlock Tetris achievements
    console.log('🏆 Game Over Stats:', {
      discordId,
      finalScore,
      linesClearedTotal,
      levelReached: Math.floor(linesClearedTotal / 20),
      piecesDropped,
      tetrisClears
    });
    // 🚨 FIX: Save achievements to database after game ends (without popups)
    // This ensures achievements are saved even if not triggered during gameplay
    saveAchievementsToDatabase(discordId, finalScore, linesClearedTotal, Math.floor(linesClearedTotal / 20), piecesDropped, tetrisClears);

    // 💾 Save score to database
    // 🔧 FIX: Send DSPOINC score directly (frontend already calculated it)
    // The API should NOT multiply again - frontend already did lines * 2
    const dspoincScore = score; // Send DSPOINC score (already calculated as lines * 2)
    const payload = {
      wallet: wallet,
      score: dspoincScore, // Send DSPOINC score directly
      discord_id: discordId,
      discord_name: discordName,
      game: "tetris"
    };
      
        console.log("⏎ Sending score payload:", payload);
      
    // 🌍 Environment-aware API endpoint (use relative path like Space Invaders)
    const apiUrl = '/api/dev/save-score.php';

    console.log(`🔗 API URL: ${apiUrl}`);

    fetch(apiUrl, {
          method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("✅ Score saved successfully:", data);
            // ✅ Force leaderboard refresh after short delay
            setTimeout(() => {
              if (document.getElementById("leaderboard-list")) {
            // 🌍 Environment-aware API endpoint for leaderboard (use relative path)
            const leaderboardUrl = `/api/dev/get-leaderboard.php?t=${Date.now()}`;
            console.log(`🔗 Leaderboard API URL: ${leaderboardUrl}`);

            fetch(leaderboardUrl)
              .then((response) => response.json())
              .then((data) => {
                console.log("✅ Leaderboard refreshed:", data);
                if (typeof loadCombinedLeaderboards === "function") {
                  loadCombinedLeaderboards();
                }
              })
              .catch((error) => {
                console.error("❌ Leaderboard refresh failed:", error);
              });
          }
        }, 1000);
      })
      .catch((error) => {
        console.error("❌ Score save failed:", error);
      });
  }

  // Touch controls setup
  heldDown = false;
  dropHoldTimeout = null;
  touchDropInterval = null;
  const sensitivity = 50;

  // 🏆 Tetris Achievement Checking Function (Inside Game Scope) - Make globally accessible
  function checkTetrisAchievements(userId, gameScore, linesCleared, levelReached, piecesDropped, tetrisClears, linesClearedInTurn) {
    
    // 🏆 Define achievement checks (REVISED 2025-10-26 - Bug #131, #136, #127, #134)
    // Based on max score ~2500 DSPOINC, balanced for realistic gameplay
    const achievementChecks = [
      // === SCORE-BASED (5 achievements - 8% to 100% of max) ===
      { key: 'score_hunter', condition: gameScore >= 200 },   // 8% of max
      { key: 'high_roller', condition: gameScore >= 800 },    // 32% of max
      { key: 'point_master', condition: gameScore >= 1500 },  // 60% of max
      { key: 'score_legend', condition: gameScore >= 2000 },  // 80% of max
      { key: 'tetris_king', condition: gameScore >= 2500 },   // 100% of max
      
      // === LINE-BASED (5 achievements - beginner to expert) ===
      { key: 'first_line', condition: linesCleared >= 1 },
      { key: 'line_master', condition: linesCleared >= 10 },
      { key: 'tetris_pro', condition: linesCleared >= 30 },
      { key: 'line_legend', condition: linesCleared >= 50 },
      { key: 'line_destroyer', condition: linesCleared >= 100 },
      
      // === LEVEL-BASED (4 achievements - speed progression) ===
      { key: 'speed_demon', condition: levelReached >= 5 },
      { key: 'level_master', condition: levelReached >= 8 },
      { key: 'level_warrior', condition: levelReached >= 12 },
      { key: 'level_champion', condition: levelReached >= 15 },
      
      // === TETRIS CLEARS (5 achievements - 4-line mastery) ===
      { key: 'tetris_clear', condition: tetrisClears >= 1 },
      { key: 'back_to_back', condition: tetrisClears >= 2 },
      { key: 'tetris_master', condition: tetrisClears >= 5 },
      { key: 'tetris_god', condition: tetrisClears >= 8 },
      { key: 'tetris_legend', condition: tetrisClears >= 15 },
      
      // === COMBO-BASED (3 achievements - FIXED: uses linesClearedInTurn) ===
      { key: 'combo_starter', condition: linesClearedInTurn >= 2 },  // Double
      { key: 'combo_master', condition: linesClearedInTurn >= 3 },   // Triple
      { key: 'combo_legend', condition: linesClearedInTurn >= 4 },   // Tetris (max)
      
      // === PIECE-BASED (3 achievements - endurance) ===
      { key: 'piece_dropper', condition: piecesDropped >= 100 },
      { key: 'block_master', condition: piecesDropped >= 400 },
      { key: 'piece_legend', condition: piecesDropped >= 600 }
    ];
    
    // Check each achievement
    achievementChecks.forEach(achievement => {
      if (achievement.condition) {
        // 🏆 Check if we already checked this achievement this game
        if (achievementsCheckedThisGame.has(achievement.key)) {
          return;
        }
        
        // Mark as checked this game
        achievementsCheckedThisGame.add(achievement.key);
        
        // Check if achievement is already unlocked (Season 3 Final Version)
        checkAndUnlockAchievement(userId, achievement.key, gameScore, linesCleared, levelReached, piecesDropped, tetrisClears, linesClearedInTurn);
      }
    });
  }
  
  // 🏆 Make Tetris achievements globally accessible
  window.checkTetrisAchievements = checkTetrisAchievements;
  
  // 🏆 Save Achievements to Database (No Popups) - For Game End
  function saveAchievementsToDatabase(userId, gameScore, linesCleared, levelReached, piecesDropped, tetrisClears, linesClearedInTurn = 0) {
    console.log('💾 saveAchievementsToDatabase called with:', { userId, gameScore, linesCleared, levelReached, piecesDropped, tetrisClears, linesClearedInTurn });
    
    // 🏆 Define achievement checks (REVISED 2025-10-26 - Bug #131, #136, #127, #134)
    // Based on max score ~2500 DSPOINC, balanced for realistic gameplay
    const achievementChecks = [
      // === SCORE-BASED (5 achievements - 8% to 100% of max) ===
      { key: 'score_hunter', condition: gameScore >= 200 },   // 8% of max
      { key: 'high_roller', condition: gameScore >= 800 },    // 32% of max
      { key: 'point_master', condition: gameScore >= 1500 },  // 60% of max
      { key: 'score_legend', condition: gameScore >= 2000 },  // 80% of max
      { key: 'tetris_king', condition: gameScore >= 2500 },   // 100% of max
      
      // === LINE-BASED (5 achievements - beginner to expert) ===
      { key: 'first_line', condition: linesCleared >= 1 },
      { key: 'line_master', condition: linesCleared >= 10 },
      { key: 'tetris_pro', condition: linesCleared >= 30 },
      { key: 'line_legend', condition: linesCleared >= 50 },
      { key: 'line_destroyer', condition: linesCleared >= 100 },
      
      // === LEVEL-BASED (4 achievements - speed progression) ===
      { key: 'speed_demon', condition: levelReached >= 5 },
      { key: 'level_master', condition: levelReached >= 8 },
      { key: 'level_warrior', condition: levelReached >= 12 },
      { key: 'level_champion', condition: levelReached >= 15 },
      
      // === TETRIS CLEARS (5 achievements - 4-line mastery) ===
      { key: 'tetris_clear', condition: tetrisClears >= 1 },
      { key: 'back_to_back', condition: tetrisClears >= 2 },
      { key: 'tetris_master', condition: tetrisClears >= 5 },
      { key: 'tetris_god', condition: tetrisClears >= 8 },
      { key: 'tetris_legend', condition: tetrisClears >= 15 },
      
      // === COMBO-BASED (3 achievements - FIXED: uses linesClearedInTurn not linesCleared) ===
      { key: 'combo_starter', condition: linesClearedInTurn >= 2 },  // Double (NOTE: needs linesClearedInTurn param!)
      { key: 'combo_master', condition: linesClearedInTurn >= 3 },   // Triple (NOTE: needs linesClearedInTurn param!)
      { key: 'combo_legend', condition: linesClearedInTurn >= 4 },   // Tetris (max) (NOTE: needs linesClearedInTurn param!)
      
      // === PIECE-BASED (3 achievements - endurance) ===
      { key: 'piece_dropper', condition: piecesDropped >= 100 },
      { key: 'block_master', condition: piecesDropped >= 400 },
      { key: 'piece_legend', condition: piecesDropped >= 600 }
    ];
    
    // Check each achievement and save to database (no popups)
    achievementChecks.forEach(achievement => {
      if (achievement.condition) {
        // Save achievement to database without popup
        saveAchievementToDatabase(userId, achievement.key, gameScore, linesCleared, levelReached, piecesDropped, tetrisClears);
      }
    });
  }
  
  // 🏆 Save Single Achievement to Database (No Popup)
  function saveAchievementToDatabase(userId, achievementKey, gameScore, linesCleared, levelReached, piecesDropped, tetrisClears) {
    // Environment-aware API endpoint
    const isProduction = window.location.hostname === 'narrrfs-world.onrender.com' || window.location.hostname === 'narrrfs.world';
    const apiBaseUrl = isProduction ? 'https://narrrfs.world' : '';
    
    // First check if achievement is already unlocked
    fetch(`${apiBaseUrl}/api/user/get-tetris-achievements.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ user_id: userId }),
      keepalive: true,
      cache: 'no-store'
    })
    .then(response => response.json())
    .then(data => {
      if (data.success && data.achievements) {
        // Check if this specific achievement is already unlocked
        const alreadyUnlocked = data.achievements.some(achievement => 
          achievement.key === achievementKey && achievement.unlocked_at
        );
        
        if (alreadyUnlocked) {
          return; // Don't save already unlocked achievements
        }
        
        // Unlock the achievement
        fetch(`${apiBaseUrl}/api/dev/unlock-tetris-achievement.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            user_id: userId,
            achievement_key: achievementKey,
            game_score: gameScore,
            lines_cleared: linesCleared,
            level_reached: levelReached,
            pieces_dropped: piecesDropped,
            tetris_clears: tetrisClears
          }),
          keepalive: true,
          cache: 'no-store'
        })
        .then(response => response.json())
        .catch(error => {
          console.error(`❌ Error saving achievement ${achievementKey}:`, error);
        });
      } else {
        console.error('❌ Failed to fetch achievements:', data.error);
      }
    })
    .catch(error => {
      console.error('❌ Error fetching achievements:', error);
    });
  }
  
  // 🏆 Check if Achievement Already Unlocked (Inside Game Scope)
  function checkAndUnlockAchievement(userId, achievementKey, gameScore, linesCleared, levelReached, piecesDropped, tetrisClears, linesClearedInTurn) {
    // Environment-aware API endpoint
    const isProduction = window.location.hostname === 'narrrfs-world.onrender.com' || window.location.hostname === 'narrrfs.world';
    const apiBaseUrl = isProduction ? 'https://narrrfs.world' : '';
    
    // First check if achievement is already unlocked
    fetch(`${apiBaseUrl}/api/user/get-tetris-achievements.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ user_id: userId }),
      keepalive: true,
      cache: 'no-store'
    })
    .then(response => response.json())
    .then(data => {
      if (data.success && data.achievements) {
        // Check if this specific achievement is already unlocked
        const alreadyUnlocked = data.achievements.some(achievement => 
          achievement.key === achievementKey && achievement.unlocked_at
        );
        
        if (alreadyUnlocked) {
          return; // Don't show popup for already unlocked achievements
        }
        
        // Unlock the achievement
        fetch(`${apiBaseUrl}/api/dev/unlock-tetris-achievement.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            user_id: userId,
            achievement_key: achievementKey,
            game_score: gameScore,
            lines_cleared: linesCleared,
            level_reached: levelReached,
            pieces_dropped: piecesDropped,
            tetris_clears: tetrisClears
          }),
          keepalive: true,
          cache: 'no-store'
        })
        .then(response => response.json())
        .then(unlockData => {
          if (unlockData.success) {
            // Show achievement popup
            // 🏆 Achievement titles and descriptions (REVISED 2025-10-26)
            const achievementData = {
              // SCORE-BASED
              'score_hunter': { title: 'Score Hunter', desc: 'Earn 200 DSPOINC in one game' },
              'high_roller': { title: 'High Roller', desc: 'Earn 800 DSPOINC in one game' },
              'point_master': { title: 'Point Master', desc: 'Earn 1,500 DSPOINC in one game' },
              'score_legend': { title: 'Score Legend', desc: 'Earn 2,000 DSPOINC in one game' },
              'tetris_king': { title: 'Tetris King', desc: 'Earn 2,500 DSPOINC (maximum score!)' },
              
              // LINE-BASED
              'first_line': { title: 'First Line', desc: 'Clear your first line' },
              'line_master': { title: 'Line Master', desc: 'Clear 10 lines in one game' },
              'tetris_pro': { title: 'Tetris Pro', desc: 'Clear 30 lines in one game' },
              'line_legend': { title: 'Line Legend', desc: 'Clear 50 lines in one game' },
              'line_destroyer': { title: 'Line Destroyer', desc: 'Clear 100 lines in one game' },
              
              // LEVEL-BASED
              'speed_demon': { title: 'Speed Demon', desc: 'Reach Level 5' },
              'level_master': { title: 'Level Master', desc: 'Reach Level 8' },
              'level_warrior': { title: 'Level Warrior', desc: 'Reach Level 12' },
              'level_champion': { title: 'Level Champion', desc: 'Reach Level 15' },
              
              // TETRIS CLEARS (4-line clears)
              'tetris_clear': { title: 'Tetris Clear', desc: 'Clear 4 lines at once (Tetris!)' },
              'back_to_back': { title: 'Back to Back', desc: 'Clear 2 Tetris in one game' },
              'tetris_master': { title: 'Tetris Master', desc: 'Clear 5 Tetris in one game' },
              'tetris_god': { title: 'Tetris God', desc: 'Clear 8 Tetris in one game' },
              'tetris_legend': { title: 'Tetris Legend', desc: 'Clear 15 Tetris in one game' },
              
              // COMBO-BASED
              'combo_starter': { title: 'Combo Starter', desc: 'Clear 2 lines at once' },
              'combo_master': { title: 'Combo Master', desc: 'Clear 3 lines at once' },
              'combo_legend': { title: 'Combo Legend', desc: 'Clear 4 lines at once (Tetris!)' },
              
              // PIECE-BASED
              'piece_dropper': { title: 'Piece Dropper', desc: 'Drop 100 pieces in one game' },
              'block_master': { title: 'Block Master', desc: 'Drop 400 pieces in one game' },
              'piece_legend': { title: 'Piece Legend', desc: 'Drop 600 pieces in one game' }
            };
            const achievementInfo = achievementData[achievementKey] || { title: achievementKey, desc: 'Achievement unlocked!' };
            const title = achievementInfo.title;
            showAchievementNotification(achievementKey, title);
          }
        })
        .catch(error => {
          console.error(`❌ Error unlocking achievement:`, error);
        });
      } else {
        console.error('❌ Failed to fetch achievements:', data.error);
      }
    })
    .catch(error => {
      console.error('❌ Error fetching achievements:', error);
    });
  }

  // 🏆 Show Achievement Popup Function (Inside Game Scope)
  function showAchievementPopup(achievementKey, gameScore, linesCleared, levelReached, piecesDropped, tetrisClears) {
    // 🏆 Achievement data (REVISED 2025-10-26 - removed score_god duplicate)
    const achievementData = {
      // SCORE-BASED
      'score_hunter': { title: 'Score Hunter', desc: 'Earn 200 DSPOINC in one game' },
      'high_roller': { title: 'High Roller', desc: 'Earn 800 DSPOINC in one game' },
      'point_master': { title: 'Point Master', desc: 'Earn 1,500 DSPOINC in one game' },
      'score_legend': { title: 'Score Legend', desc: 'Earn 2,000 DSPOINC in one game' },
      'tetris_king': { title: 'Tetris King', desc: 'Earn 2,500 DSPOINC (maximum score!)' },
      
      // LINE-BASED
      'first_line': { title: 'First Line', desc: 'Clear your first line' },
      'line_master': { title: 'Line Master', desc: 'Clear 10 lines in one game' },
      'tetris_pro': { title: 'Tetris Pro', desc: 'Clear 30 lines in one game' },
      'line_legend': { title: 'Line Legend', desc: 'Clear 50 lines in one game' },
      'line_destroyer': { title: 'Line Destroyer', desc: 'Clear 100 lines in one game' },
      
      // LEVEL-BASED
      'speed_demon': { title: 'Speed Demon', desc: 'Reach Level 5' },
      'level_master': { title: 'Level Master', desc: 'Reach Level 8' },
      'level_warrior': { title: 'Level Warrior', desc: 'Reach Level 12' },
      'level_champion': { title: 'Level Champion', desc: 'Reach Level 15' },
      
      // TETRIS CLEARS (4-line clears)
      'tetris_clear': { title: 'Tetris Clear', desc: 'Clear 4 lines at once (Tetris!)' },
      'back_to_back': { title: 'Back to Back', desc: 'Clear 2 Tetris in one game' },
      'tetris_master': { title: 'Tetris Master', desc: 'Clear 5 Tetris in one game' },
      'tetris_god': { title: 'Tetris God', desc: 'Clear 8 Tetris in one game' },
      'tetris_legend': { title: 'Tetris Legend', desc: 'Clear 15 Tetris in one game' },
      
      // COMBO-BASED
      'combo_starter': { title: 'Combo Starter', desc: 'Clear 2 lines at once' },
      'combo_master': { title: 'Combo Master', desc: 'Clear 3 lines at once' },
      'combo_legend': { title: 'Combo Legend', desc: 'Clear 4 lines at once (Tetris!)' },
      
      // PIECE-BASED
      'piece_dropper': { title: 'Piece Dropper', desc: 'Drop 100 pieces in one game' },
      'block_master': { title: 'Block Master', desc: 'Drop 400 pieces in one game' },
      'piece_legend': { title: 'Piece Legend', desc: 'Drop 600 pieces in one game' }
    };
    
    const achievementInfo = achievementData[achievementKey] || { title: achievementKey, desc: 'Achievement unlocked!' };
    const title = achievementInfo.title;
    showAchievementNotification(achievementKey, title);
  }

  // 🎉 Show Achievement Notification (Inside Game Scope)
  function showAchievementNotification(achievementKey, achievementTitle) {
    const canvas = document.getElementById("tetris-canvas");
    if (!canvas) return;
    
    const popup = {
      title: achievementTitle,
      description: `Achievement Unlocked!`,
      icon: '🏆',
      life: 30, // 0.5 seconds at 60fps (much faster)
      maxLife: 30,
      scale: 1, // No scale animation - instant appearance
      maxScale: 1,
      y: canvas.height / 2,
      color: '#ffd700' // Gold color for achievements
    };
    
    // 🚨 CRITICAL FIX: Use global window object like Snake
    if (!window.tetrisAchievementPopups) {
      window.tetrisAchievementPopups = [];
    }
    window.tetrisAchievementPopups.push(popup);
    
    // 🚨 CRITICAL FIX: Force immediate popup display even if game is over
    // Draw the popup immediately to ensure it's visible
    drawAchievementPopups();
  }

  // 🎨 Draw Achievement Popups on Canvas (Inside Game Scope)
  function drawAchievementPopups() {
    const canvas = document.getElementById("tetris-canvas");
    if (!canvas) return;
    
    const ctx = canvas.getContext("2d");
    const centerX = canvas.width / 2;
    
    // 🚨 CRITICAL FIX: Use global window object like Snake
    if (!window.tetrisAchievementPopups) return;
    
    window.tetrisAchievementPopups.forEach((popup, index) => {
      popup.life--;
      
      // Remove expired popups
      if (popup.life <= 0) {
        window.tetrisAchievementPopups.splice(index, 1);
        return;
      }
      
      const alpha = popup.life / popup.maxLife;
      
      // Draw background (Tetris-optimized size)
      ctx.fillStyle = `rgba(0, 0, 0, ${alpha * 0.9})`;
      ctx.fillRect(centerX - 150, popup.y - 30, 300, 60);
      
      // Draw border (thinner for Tetris)
      ctx.strokeStyle = `rgba(255, 215, 0, ${alpha})`;
      ctx.lineWidth = 2;
      ctx.strokeRect(centerX - 150, popup.y - 30, 300, 60);
      
      // Draw icon (Tetris-optimized size with proper positioning)
      ctx.fillStyle = `rgba(255, 215, 0, ${alpha})`;
      ctx.font = `20px Arial`; // Slightly smaller to ensure it fits
      ctx.textAlign = 'center';
      ctx.fillText(popup.icon, centerX - 80, popup.y + 5); // Better positioning
      
      // Draw title (Tetris-optimized size)
      ctx.fillStyle = `rgba(255, 255, 255, ${alpha})`;
      ctx.font = `bold 18px Arial`;
      ctx.fillText(popup.title, centerX, popup.y - 10);
      
      // Draw description (Tetris-optimized size)
      ctx.fillStyle = `rgba(200, 200, 200, ${alpha})`;
      ctx.font = `12px Arial`;
      ctx.fillText(popup.description, centerX, popup.y + 12);
    });
  }

  // 🚀 Start the game loop immediately when game starts
  gameInterval = setInterval(drop, dropInterval);
  drop();
  
  // ✅ Final game loop initialization
  renderNextBlock(nextPiece);
  
  // 🔧 MOBILE FIX: Initialize touch controls properly
  console.log('📱 Tetris game started successfully');
  
  // Initialize touch controls for the canvas (always, not just mobile)
  initTouchControls(canvas);
  
  // 🔧 MOBILE FIX: Ensure touch controls stay active even after other games start
  tetrisTouchRecoveryTimeout = setTimeout(() => {
    console.log('📱 Checking Tetris touch controls after delay...');
    const tetrisCanvas = document.getElementById('tetris-canvas');
    if (tetrisCanvas && tetrisCanvas.ontouchstart === null) {
      console.log('📱 Tetris touch controls lost - re-initializing...');
      initTouchControls(tetrisCanvas);
    }
  }, 2000);
  
  // 🔧 MOBILE FIX: Continuous touch control monitoring
  tetrisTouchMonitorInterval = setInterval(() => {
    const tetrisCanvas = document.getElementById('tetris-canvas');
    if (tetrisCanvas && tetrisCanvas.ontouchstart === null) {
      console.log('📱 Tetris touch controls lost - continuous recovery...');
      initTouchControls(tetrisCanvas);
    }
  }, 5000); // Check every 5 seconds
  
  // Force a redraw to ensure everything is visible
  setTimeout(() => {
    if (typeof window.tetrisDraw === 'function') {
      window.tetrisDraw();
      console.log('📱 Tetris redraw completed');
    } else {
      console.log('❌ window.tetrisDraw not available yet');
    }
  }, 100);
}

// 🏁 Legacy OK button handler (matches live behavior)
window.endTetrisGame = function() {
  console.log('🏁 OK button clicked - ending Legacy Tetris game');
  // Reload the page to ensure a clean reset (no auto-start)
  cleanupTouchControls();
  localStorage.removeItem('tetris_auto_start');
  window.location.reload();
};

// 🔁 Legacy Play Again (reload + auto-start)
window.restartTetrisGame = function() {
  console.log('🔄 Play Again button clicked - restarting Legacy Tetris game');
  
  const modal = document.getElementById("game-over-modal");
  if (modal) {
    modal.classList.add("hidden");
    modal.style.display = "none";
  }
  cleanupTouchControls();
  localStorage.setItem('tetris_auto_start', 'true');
  window.location.reload();
};

function lockTetrisScroll() {
    document.body.style.overflow = "hidden";
}

function unlockTetrisScroll() {
    document.body.style.overflow = "";
}

// 🔧 MOBILE FIX: Removed conflicting global touch listeners
// Touch controls are now handled only by the canvas-specific listeners in initTouchControls()
// This prevents double event handling and touch control conflicts

// 🔧 MOBILE ERROR HANDLER
window.addEventListener('error', function(e) {
  if (isTetrisMobileDevice) {
    console.error('📱 Mobile Tetris error:', e.error);
    
    // Try to recover the game
    if (e.error && e.error.message && e.error.message.includes('tetris')) {
      console.log('📱 Attempting to recover Tetris game...');
      setTimeout(() => {
        try {
          if (typeof startTetris === 'function') {
            startTetris();
          }
        } catch (recoveryError) {
          console.error('📱 Recovery failed:', recoveryError);
        }
      }, 1000);
    }
  }
});

// 🔧 MOBILE TEST FUNCTION
window.testMobileTetris = function() {
  if (isTetrisMobileDevice) {
    console.log('📱 Testing mobile Tetris functionality...');
    console.log('📱 Canvas element:', document.getElementById('tetris-canvas'));
    console.log('📱 Start button:', document.getElementById('start-tetris-btn'));
    console.log('📱 startTetrisGame function:', typeof window.startTetrisGame);
    console.log('📱 startTetris function:', typeof startTetris);
    console.log('📱 All images loaded:', allImagesLoaded);
    
    // Test touch events
    const canvas = document.getElementById('tetris-canvas');
    if (canvas) {
      console.log('📱 Canvas touch events:', canvas.ontouchstart, canvas.ontouchmove, canvas.ontouchend);
    }
  } else {
    console.log('🖥️ Not a mobile device');
  }
};

// 🔧 MOBILE TOUCH RECOVERY FUNCTION
window.reinitializeTetrisTouch = function() {
  console.log('📱 Re-initializing Tetris touch controls...');
  const canvas = document.getElementById('tetris-canvas');
  if (canvas) {
    // Force re-initialization of touch controls
    initTouchControls(canvas);
    console.log('📱 Tetris touch controls re-initialized');
    
    // Test if touch events are working
    setTimeout(() => {
      console.log('📱 Touch event test:', {
        canvas: !!canvas,
        touchstart: canvas.ontouchstart !== null,
        touchmove: canvas.ontouchmove !== null,
        touchend: canvas.ontouchend !== null,
        storedListeners: {
          touchstart: !!canvas.tetrisTouchStart,
          touchmove: !!canvas.tetrisTouchMove,
          touchend: !!canvas.tetrisTouchEnd
        }
      });
    }, 100);
  } else {
    console.log('❌ Tetris canvas not found for touch re-initialization');
  }
};

// 🧪 GLOBAL TEST FUNCTION - Test role system (names)
window.testRoleSystem = function() {
  console.log('🧪 Testing Role System...');
  console.log('Hostname:', window.location.hostname);
  console.log('Is Local Development:', ['localhost', '127.0.0.1', ''].includes(window.location.hostname));
  console.log('User Roles:', userRoles);
  console.log('Role Multipliers:', roleMultipliers);
  console.log('Role Priority:', rolePriority);
  console.log('Normalized Roles:', getNormalizedRoles());
  console.log('Primary role:', getUserPrimaryRole());
  console.log('Current multiplier:', getRoleScoreMultiplier());

  const testScore = 10;
  const multiplier = getRoleScoreMultiplier();
  const finalScore = testScore * multiplier;
  console.log(`Test score calculation: ${testScore} * ${multiplier} = ${finalScore}`);
};

// 🧪 GLOBAL TEST FUNCTION - Test Tetris game elements
window.testTetrisGame = function() {
  console.log('🧪 Testing Tetris Game Elements...');
  console.log('Canvas element:', document.getElementById('tetris-canvas'));
  console.log('Score display element:', document.getElementById('tetris-score'));
  console.log('Current score variable:', score);
  console.log('Score display object:', tetrisScoreDisplay);
  console.log('Game interval:', gameInterval);
  console.log('Is game running:', !isTetrisPaused);
  
  // Test manual score update
  if (tetrisScoreDisplay) {
    console.log('Testing manual score update...');
    score = 100;
    updateTetrisScoreDisplay();
    console.log('Manual score update completed');
  } else {
    console.log('❌ Score display element not found!');
  }
};

// 🧪 GLOBAL TEST FUNCTION - Force load test roles
window.forceLoadTestRoles = function() {
  console.log('🧪 Force loading test roles...');
  userRoles = [
    '🎴 VIP Holder',
    '🏆 Holder',
    'Champion',
    'Season Tester',
    'Early Bird',
    '🧀 Cheese Hunter'
  ];
  console.log('🏆 Test roles forced:', userRoles);
  applyRoleTheme();
  updateTetrisScoreDisplay();
  console.log('🏆 Role theme and score display updated');
};

// 🧪 GLOBAL TEST FUNCTION - Test line clearing and scoring
window.testLineClearing = function() {
  console.log('🧪 Testing line clearing and scoring...');
  console.log('Current score before:', score);
  console.log('Current role multiplier:', getRoleScoreMultiplier());
  
  // Fill a test line to make it clearable
  if (grid && grid.length > 0) {
    console.log('🔍 Filling row 19 with test blocks...');
    grid[19] = new Array(COLS).fill(1); // Fill bottom row
    console.log('🔍 Row 19 after filling:', grid[19]);
    
    // Call clearLines to test scoring
    console.log('🔍 Calling clearLines()...');
    clearLines();
    
    console.log('🔍 Score after line clear:', score);
    console.log('🔍 Score display should show:', score);
    updateTetrisScoreDisplay();
  } else {
    console.log('❌ Grid not available - start a game first');
  }
};

// 🔧 EMERGENCY TOUCH RECOVERY - Force touch controls to work
window.forceTetrisTouch = function() {
  console.log('🚨 EMERGENCY: Forcing Tetris touch controls...');
  const canvas = document.getElementById('tetris-canvas');
  if (canvas) {
    // Remove ALL existing listeners
    canvas.removeEventListener("touchstart", handleTouchStart);
    canvas.removeEventListener("touchmove", handleTouchMove);
    canvas.removeEventListener("touchend", handleTouchEnd);
    
    // Clear any stored references
    delete canvas.tetrisTouchStart;
    delete canvas.tetrisTouchMove;
    delete canvas.tetrisTouchEnd;
    
    // Force re-initialization
    initTouchControls(canvas);
    
    console.log('🚨 Emergency touch controls applied');
  }
};