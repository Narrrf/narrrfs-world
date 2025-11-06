// 🧀 Cheese Tetris Scroll v9.8 + PNG BLOCKS (all features from perfect backup, plus PNG support)
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

// 🏆 ROLE-BASED GAMEPLAY SYSTEM - Season 4 Feature
let userRoles = [];
let roleMultipliers = {
  'VIP Holder': 2.0,
  '🎴 VIP Holder': 2.0,
  'Holder': 1.5,
  '🏆 Holder': 1.5,
  'Season Tester': 1.3,
  'Early Bird': 1.2,
  'Champion': 1.4,
  'Cheese Hunter': 1.1,
  '🧀 Cheese Hunter': 1.1
};
let roleThemes = {
  'VIP Holder': 'golden',
  '🎴 VIP Holder': 'golden',
  'Holder': 'silver',
  '🏆 Holder': 'silver', 
  'Cheese Hunter': 'cheese',
  '🧀 Cheese Hunter': 'cheese',
  'Season Tester': 'rainbow',
  'Early Bird': 'blue',
  'Champion': 'red'
};

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

// 🎨 Role-based theme application for Tetris
function applyRoleTheme() {
  // Get user roles from localStorage or default test roles
  const isLocalDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
  if (isLocalDevelopment) {
    userRoles = [
      "VIP Holder", "Holder", "Champion", "Season Tester", "Early Bird", "Cheese Hunter"
    ];
  }
  
  // Determine primary role
  const priorityOrder = ['VIP Holder', '🎴 VIP Holder', 'Holder', '🏆 Holder', 'Champion', 'Season Tester', 'Early Bird', 'Cheese Hunter', '🧀 Cheese Hunter'];
  let primaryRole = null;
  for (const role of priorityOrder) {
    if (userRoles.includes(role)) {
      // Return clean role name for consistent theming and multipliers
      primaryRole = role.replace(/^[🎴🏆🧀]\s*/, '');
      break;
    }
  }
  
  const theme = roleThemes[primaryRole] || 'default';
  console.log(`🎨 Tetris theme: ${theme} for role: ${primaryRole}`);
  
  // Update score display with role multiplier
  updateTetrisScoreDisplay();
  
  // Apply theme to canvas
  const canvas = document.getElementById('tetris-canvas');
  if (canvas) {
    canvas.classList.remove('golden', 'silver', 'cheese', 'rainbow', 'blue', 'red');
    if (theme !== 'default') {
      canvas.classList.add(theme);
    }
  }
  
  // Apply theme to controls section
  const controlsSection = document.getElementById('tetris-controls-section');
  const controlsTitle = document.getElementById('tetris-controls-title');
  if (controlsSection && controlsTitle) {
    controlsSection.classList.remove('golden', 'silver', 'cheese', 'rainbow', 'blue', 'red');
    controlsTitle.classList.remove('golden', 'silver', 'cheese', 'rainbow', 'blue', 'red');
    if (theme !== 'default') {
      controlsSection.classList.add(theme);
      controlsTitle.classList.add(theme);
    }
    console.log(`🎨 Tetris controls theme applied: ${theme}`);
  }
}

// 🏆 Get user's primary role (highest priority role)
function getUserPrimaryRole() {
  const priorityOrder = ['VIP Holder', '🎴 VIP Holder', 'Holder', '🏆 Holder', 'Champion', 'Season Tester', 'Early Bird', 'Cheese Hunter', '🧀 Cheese Hunter'];
  
  for (const role of priorityOrder) {
    if (userRoles.includes(role)) {
      // Return clean role name for consistent theming and multipliers
      return role.replace(/^[🎴🏆🧀]\s*/, '');
    }
  }
  
  return null;
}

// ⚡ Calculate role-based score multiplier
function getRoleScoreMultiplier() {
  const primaryRole = getUserPrimaryRole();
  return roleMultipliers[primaryRole] || 1.0;
}

// 🏆 Update Tetris score display with role bonus
function updateTetrisScoreDisplay() {
  const scoreDisplay = document.getElementById('tetris-score');
  if (scoreDisplay) {
    const roleMultiplier = getRoleScoreMultiplier();
    const primaryRole = getUserPrimaryRole();
    
    if (roleMultiplier > 1.0) {
      scoreDisplay.textContent = `💰 Tetris Score: $${score} DSPOINC (${roleMultiplier}x Role Bonus!)`;
    } else {
      scoreDisplay.textContent = `💰 Tetris Score: $${score} DSPOINC`;
    }
    
    console.log(`🏆 Tetris score display update: Role=${primaryRole}, Multiplier=${roleMultiplier}x, Score=${score} DSPOINC`);
  }
}

// 🏆 GLOBAL TEST FUNCTION - Test Tetris role-based features
window.testTetrisRoleFeatures = function() {
  console.log('🏆 Testing Tetris role-based features...');
  console.log('Current roles:', userRoles);
  console.log('Primary role:', getUserPrimaryRole());
  console.log('Score multiplier:', getRoleScoreMultiplier());
  console.log('Expected theme:', roleThemes[getUserPrimaryRole()]);
  
  // Test emoji role matching
  console.log('🧪 Testing emoji role matching...');
  const testRoles = ['🎴 VIP Holder', '🏆 Holder', '🧀 Cheese Hunter'];
  testRoles.forEach(role => {
    const cleanRole = role.replace(/^[🎴🏆🧀]\s*/, '');
    console.log(`Role: ${role} -> Clean: ${cleanRole} -> Multiplier: ${roleMultipliers[cleanRole]}`);
  });
  
  // Test priority order matching
  console.log('🎯 Testing priority order...');
  const priorityOrder = ['VIP Holder', '🎴 VIP Holder', 'Holder', '🏆 Holder', 'Champion', 'Season Tester', 'Early Bird', 'Cheese Hunter', '🧀 Cheese Hunter'];
  priorityOrder.forEach(role => {
    const hasRole = userRoles.includes(role);
    const cleanRole = role.replace(/^[🎴🏆🧀]\s*/, '');
    console.log(`${hasRole ? '✅' : '❌'} ${role} -> Clean: ${cleanRole} -> Multiplier: ${roleMultipliers[cleanRole]}`);
  });
  
  const canvas = document.getElementById('tetris-canvas');
  console.log('Canvas element:', canvas);
  console.log('Canvas classes:', canvas?.className);
  console.log('Current score:', score);
  console.log('Current DSPOINC:', score);
};

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
const TETRIS_SWIPE_THRESHOLD = 30; // Reduced for more sensitive control
const TETRIS_SWIPE_TIME_THRESHOLD = 400; // Reduced for faster response
const TETRIS_DOUBLE_TAP_THRESHOLD = 300; // Maximum time between taps for double tap
const TETRIS_DOWN_SWIPE_THRESHOLD = 40; // Separate threshold for down swipes (more sensitive)
let tetrisLastTapTime = 0;
let tetrisLastMoveTime = 0; // Throttle rapid movements
const TETRIS_MOVE_THROTTLE = 100; // Reduced throttle for more responsive control

// 🎮 Hold-to-drop functionality
let tetrisIsHolding = false;
let tetrisHoldInterval = null;
let tetrisRotationTimer = null; // Timer for delayed rotation
const TETRIS_HOLD_DELAY = 50; // Reduced delay for more responsive hold-to-drop (ms) - was 100
const TETRIS_HOLD_INTERVAL = 30; // Faster interval for more responsive hold-to-drop (ms) - was 50
let tetrisHoldStartTime = 0;

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
    
    // 🚀 Check for auto-start flag (from Play Again button)
    if (localStorage.getItem('tetris_auto_start') === 'true') {
      console.log('🚀 Auto-start flag detected - starting Tetris automatically');
      localStorage.removeItem('tetris_auto_start'); // Clear flag
      setTimeout(() => {
        window.startTetrisGame();
      }, 500); // Small delay to ensure page is fully loaded
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

window.startTetrisGame = function () {
  console.log('🎮 startTetrisGame called');
  
  if (!allImagesLoaded) {
    console.warn("Assets still loading...");
    return;
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
  startTetris(); // ← main game logic
};

// 🎮 Restart game function (called by Play Again button) - Like Space Invaders
window.restartTetrisGame = function() {
  console.log('🔄 Play Again button clicked - restarting Tetris game');
  
  // Hide game over modal
  const modal = document.getElementById("game-over-modal");
  if (modal) {
    modal.classList.add("hidden");
  }
  
  // 🚀 Set flag to auto-start game after reload
  localStorage.setItem('tetris_auto_start', 'true');
  
  // Reload page to get fresh game state (simplest and most reliable)
  window.location.reload();
};

// 🏁 End game function (called by OK button) - Reload page without auto-start
window.endTetrisGame = function() {
  console.log('🏁 OK button clicked - reloading page (no auto-start)');
  
  // Hide game over modal
  const modal = document.getElementById("game-over-modal");
  if (modal) {
    modal.classList.add("hidden");
  }
  
  // 🚨 CRITICAL: DO NOT set auto-start flag (user wants clean reload)
  // This is different from Play Again which sets the flag
  
  // Reload page to get fresh game state (no auto-start)
  window.location.reload();
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

function startTetris() {
  const canvas = document.getElementById("tetris-canvas");
  const context = canvas.getContext("2d");
  const scoreDisplay = document.getElementById("spoink-score");
  if (!scoreDisplay) {
    console.log('⚠️ Score display element not found - creating fallback');
  }

  const gridWidth = 10;
  const gridHeight = 20;
  const blockSize = 20;

  let score = 0;
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

  let nextPiece = randomPiece();
  let current = {
    shape: nextPiece,
    row: 0,
    col: 3,
    timer: null
  };
  nextPiece = randomPiece();

  function randomPiece() {
    const isExplosive = Math.random() < 0.1;
    return isExplosive ? [[6]] : pieces[Math.floor(Math.random() * pieces.length)];
  }

  function explode(centerX, centerY) {
    for (let y = -1; y <= 1; y++) {
      for (let x = -1; x <= 1; x++) {
        const ny = centerY + y;
        const nx = centerX + x;
        if (ny >= 0 && ny < gridHeight && nx >= 0 && nx < gridWidth && grid[ny]?.[nx]) {
          grid[ny][nx] = 0;
        }
      }
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
        if (val) drawBlock(current.col + x, current.row + y, val);
      })
    );
    context.restore();
    
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

    const rotated = current.shape[0].map((_, i) =>
      current.shape.map(row => row[i]).reverse()
    );
    if (!collide(rotated, current.row, current.col)) {
      current.shape = rotated;
    }
  }
  
  window.tetrisRotatePiece = rotatePiece;

  // --- Next block preview: PNG if available, else color ---
  function renderNextBlock(shape) {
    if (!nextCtx || !shape) return;
    nextCtx.clearRect(0, 0, nextCanvas.width, nextCanvas.height);
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
        }
      });
    });
    // 💣 Bomb detection & warning toggle
    const bombWarning = document.getElementById("bomb-warning");
    const isBomb = shape.length === 1 && shape[0].length === 1 && shape[0][0] === 6;
    if (bombWarning) {
      bombWarning.classList.toggle("hidden", !isBomb);
    }
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
        let lines = 0;
        for (let y = gridHeight - 1; y >= 0; y--) {
          if (grid[y].every(v => v !== 0)) {
            // 🧠 Check for bomb BEFORE removing the row
            if (grid[y].includes(6)) {
              showBombDefusedPopup();
          score += 10; // Bonus for defusing bomb (reduced for balance)
          if (scoreDisplay) {
            scoreDisplay.textContent = `💰 $DSPOINC earned: ${score}`;
          }
            }
      
            grid.splice(y, 1);
            grid.unshift(Array(gridWidth).fill(0));
            lines++;
            y++; // Re-check same row index
          }
        }
      
        if (lines > 0) {
          // 🎵 Play line clear sound
          tetrisSounds.playSound('lineClear');
          
          // 🎵 Check for level up (every 20 lines)
          const oldLevel = Math.floor(linesClearedTotal / 20);
          linesClearedTotal += lines;
          const newLevel = Math.floor(linesClearedTotal / 20);
          
          if (newLevel > oldLevel) {
            tetrisSounds.playSound('levelUp');
            console.log(`🎵 Level up! Now at level ${newLevel}`);
          }
          
          // 🏆 Apply role-based scoring
          const baseScore = lines * 2; // Season 3: 2 DSPOINC per line (balanced for ~5k max)
          const roleMultiplier = getRoleScoreMultiplier();
          const roleBonus = Math.floor(baseScore * (roleMultiplier - 1)); // Calculate bonus points
          score += baseScore + roleBonus;
          
          console.log(`📊 Line clearing: ${lines} lines = ${baseScore} DSPOINC + ${roleBonus} role bonus = ${baseScore + roleBonus} total`);
          
          // 🏆 Log role-based scoring
          if (roleMultiplier > 1.0) {
            console.log(`🏆 Role multiplier applied: ${roleMultiplier}x (${roleBonus} bonus points)`);
          }
          
          // 🏆 Track Tetris clears (4 lines at once)
          if (lines === 4) {
            tetrisClears++;
            console.log('🏆 Tetris clear! Total tetris clears:', tetrisClears);
          }
          
          // 🏆 Check achievements immediately when lines are cleared
          // This gives players instant feedback when they unlock achievements
          console.log('🧩 Lines cleared! Checking achievements...', { linesClearedTotal, score, tetrisClears });
          console.log('🚨🚨🚨 ABOUT TO CALL CHECKTETRISACHIEVEMENTS! 🚨🚨🚨');
          console.log('🔍 Function exists?', typeof checkTetrisAchievements);
          try {
            checkTetrisAchievements(localStorage.getItem('discord_id') || '328601656659017732', score, linesClearedTotal, Math.floor(linesClearedTotal / 20), piecesDropped, tetrisClears, lines);
            console.log('✅ checkTetrisAchievements call completed successfully');
          } catch (error) {
            console.error('❌ ERROR in checkTetrisAchievements call:', error);
            console.error('❌ Error stack:', error.stack);
          }
          
      if (scoreDisplay) {
          scoreDisplay.textContent = `💰 $DSPOINC earned: ${score}`;
      }
      
          // ⏩ Speed up every 20 lines
          if (linesClearedTotal % 20 === 0) {
            dropInterval = Math.max(100, dropInterval - 50);
            clearInterval(gameInterval);
            gameInterval = setInterval(drop, dropInterval);
          }
        }
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
      clearInterval(gameInterval);
      // 🔓 Re-enable page links/buttons when paused (like Snake)
      unlockTetrisScroll();
      enableTetrisPageButtons();
    } else {
      clearInterval(gameInterval); // always reset interval
      gameInterval = setInterval(drop, dropInterval);
      drop(); // redraw immediately
      // 🔒 Disable page links/buttons again when resumed (like Snake)
      lockTetrisScroll();
      disableTetrisPageButtons();
    }
  };

  pauseBtn.addEventListener("click", pauseHandler);
  pauseBtn.addEventListener("touchend", pauseHandler, { passive: false });
}

// Add keyboard event listener for movement controls
document.addEventListener("keydown", e => {
  // Handle pause key first (before checking if paused)
  if (e.key === 'p' || e.key === 'P') {
    const pauseBtn = document.getElementById("pause-tetris-btn");
    if (pauseBtn) {
      pauseBtn.click(); // Trigger the existing pause button handler
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
    // 💣 Bomb piece logic
    if (
      current.shape.length === 1 &&
      current.shape[0].length === 1 &&
      current.shape[0][0] === 6
    ) {
      const cx = current.col;
      const cy = current.row;
      const countdown = Math.floor(Math.random() * 30) + 1;

      activeExplosive = { x: cx, y: cy, countdown, start: Date.now() };

      setTimeout(() => {
        if (grid[cy]?.[cx] === 6) {
          explode(cx, cy);
        }
        activeExplosive = null;
      }, countdown * 1000);
    }

    merge();
    // 🎵 Play piece placement sound
    tetrisSounds.playSound('piecePlace');
    clearLines();

    current = {
      shape: nextPiece,
      row: 0,
      col: 3
    };
    nextPiece = randomPiece();
    renderNextBlock(nextPiece);
    
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
  onTetrisGameOver(score);

  const modal = document.getElementById("game-over-modal");
  const finalScoreText = document.getElementById("final-score-text");
  const pauseBtn = document.getElementById("pause-tetris-btn");

  if (modal && finalScoreText) {
    const gameOverText = modal.querySelector('h2') || modal.querySelector('strong');
    if (gameOverText) {
      try {
        gameOverText.innerHTML = '🧠 GAME OVER';
      } catch (error) {
        console.log('⚠️ Could not update game over text:', error);
      }
    }
    try {
      // ✅ Show role multiplier in final score display
      const roleMultiplier = getRoleScoreMultiplier();
      if (roleMultiplier > 1.0) {
        finalScoreText.textContent = `You earned $${score} DSPOINC (${roleMultiplier}x Role Bonus!)`;
      } else {
        finalScoreText.textContent = `You earned $${score} DSPOINC`;
      }
      console.log("🧀 Displaying score:", score, "with role multiplier:", roleMultiplier);
    } catch (error) {
      console.log('⚠️ Could not update final score text:', error);
    }
    modal.classList.remove('hidden');
    // 🔓 Re-enable page links/buttons when game over (like Snake)
    unlockTetrisScroll();
    enableTetrisPageButtons();
    cleanupTouchControls();
  } else {
    console.log('⚠️ Game over modal elements not found - game over handled gracefully');
    // 🔓 Re-enable page links/buttons when game over (like Snake)
    unlockTetrisScroll();
    enableTetrisPageButtons();
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
let heldDown = false;
  let dropHoldTimeout;
  let touchDropInterval;
  const sensitivity = 50;

  // 🏆 Tetris Achievement Checking Function (Inside Game Scope) - Make globally accessible
  function checkTetrisAchievements(userId, gameScore, linesCleared, levelReached, piecesDropped, tetrisClears, linesClearedInTurn) {
    console.log('🚨🚨🚨 TETRIS ACHIEVEMENT CHECK CALLED! 🚨🚨🚨');
    console.log('🏆 Checking Tetris achievements for user:', userId);
    console.log('🏆 Game stats:', { gameScore, linesCleared, levelReached, piecesDropped, tetrisClears, linesClearedInTurn });
    
    // Define achievement checks
    const achievementChecks = [
      // Basic Achievements
      { key: 'first_line', condition: linesCleared >= 1 },
      { key: 'line_master', condition: linesCleared >= 10 },
      { key: 'tetris_pro', condition: linesCleared >= 50 },
      { key: 'line_legend', condition: linesCleared >= 100 },
      { key: 'speed_demon', condition: levelReached >= 5 },
      { key: 'level_master', condition: levelReached >= 10 },
      { key: 'high_roller', condition: gameScore >= 2000 },
      { key: 'score_hunter', condition: gameScore >= 1000 },
      { key: 'point_master', condition: gameScore >= 3000 },
      { key: 'tetris_king', condition: gameScore >= 5000 },
      
      // Advanced Achievements
      { key: 'piece_dropper', condition: piecesDropped >= 100 },
      { key: 'block_master', condition: piecesDropped >= 500 },
      { key: 'tetris_clear', condition: tetrisClears >= 1 },
      { key: 'tetris_master', condition: tetrisClears >= 5 },
      { key: 'tetris_god', condition: tetrisClears >= 10 },
      { key: 'combo_starter', condition: linesClearedInTurn >= 2 },
      { key: 'combo_master', condition: linesClearedInTurn >= 5 },
      { key: 'combo_legend', condition: linesCleared >= 10 },
      { key: 'back_to_back', condition: tetrisClears >= 2 },
      
      // Expert Achievements
      { key: 'level_warrior', condition: levelReached >= 15 },
      { key: 'level_champion', condition: levelReached >= 20 },
      { key: 'score_legend', condition: gameScore >= 4000 },
      { key: 'score_god', condition: gameScore >= 5000 },
      { key: 'line_destroyer', condition: linesCleared >= 200 },
      { key: 'piece_legend', condition: piecesDropped >= 1000 },
      { key: 'tetris_legend', condition: tetrisClears >= 25 }
    ];
    
    // Check each achievement
    achievementChecks.forEach(achievement => {
      console.log(`🔍 Checking achievement ${achievement.key}: condition=${achievement.condition}, linesCleared=${linesCleared}, linesClearedInTurn=${linesClearedInTurn}`);
      
      if (achievement.condition) {
        // 🏆 Check if we already checked this achievement this game
        if (achievementsCheckedThisGame.has(achievement.key)) {
          console.log(`ℹ️ Achievement ${achievement.key} already checked this game - skipping`);
          return;
        }
        
        console.log(`🏆 Achievement condition met: ${achievement.key}`);
        
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
  function saveAchievementsToDatabase(userId, gameScore, linesCleared, levelReached, piecesDropped, tetrisClears) {
    console.log('💾 Saving achievements to database for user:', userId);
    console.log('💾 Game stats:', { gameScore, linesCleared, levelReached, piecesDropped, tetrisClears });
    
    // Define achievement checks (same as checkTetrisAchievements but without popups)
    const achievementChecks = [
      // Basic Achievements
      { key: 'first_line', condition: linesCleared >= 1 },
      { key: 'line_master', condition: linesCleared >= 10 },
      { key: 'tetris_pro', condition: linesCleared >= 50 },
      { key: 'line_legend', condition: linesCleared >= 100 },
      { key: 'speed_demon', condition: levelReached >= 5 },
      { key: 'level_master', condition: levelReached >= 10 },
      { key: 'high_roller', condition: gameScore >= 2000 },
      { key: 'score_hunter', condition: gameScore >= 1000 },
      { key: 'point_master', condition: gameScore >= 3000 },
      { key: 'tetris_king', condition: gameScore >= 5000 },
      
      // Advanced Achievements
      { key: 'piece_dropper', condition: piecesDropped >= 100 },
      { key: 'block_master', condition: piecesDropped >= 500 },
      { key: 'tetris_clear', condition: tetrisClears >= 1 },
      { key: 'tetris_master', condition: tetrisClears >= 5 },
      { key: 'tetris_god', condition: tetrisClears >= 10 },
      { key: 'combo_starter', condition: linesCleared >= 2 },
      { key: 'combo_master', condition: linesCleared >= 5 },
      { key: 'combo_legend', condition: linesCleared >= 10 },
      { key: 'back_to_back', condition: tetrisClears >= 2 },
      
      // Expert Achievements
      { key: 'level_warrior', condition: levelReached >= 15 },
      { key: 'level_champion', condition: levelReached >= 20 },
      { key: 'score_legend', condition: gameScore >= 4000 },
      { key: 'score_god', condition: gameScore >= 5000 },
      { key: 'line_destroyer', condition: linesCleared >= 200 },
      { key: 'piece_legend', condition: piecesDropped >= 1000 },
      { key: 'tetris_legend', condition: tetrisClears >= 25 }
    ];
    
    // Check each achievement and save to database (no popups)
    achievementChecks.forEach(achievement => {
      if (achievement.condition) {
        console.log(`💾 Achievement condition met: ${achievement.key} - saving to database`);
        
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
    })
    .then(response => response.json())
    .then(data => {
      if (data.success && data.achievements) {
        // Check if this specific achievement is already unlocked
        const alreadyUnlocked = data.achievements.some(achievement => 
          achievement.key === achievementKey && achievement.unlocked_at
        );
        
        if (alreadyUnlocked) {
          console.log(`💾 Achievement ${achievementKey} already unlocked - skipping save`);
          return; // Don't save already unlocked achievements
        }
        
        // Achievement not unlocked yet - unlock it (no popup)
        console.log(`💾 Achievement ${achievementKey} not yet unlocked - saving to database`);
        
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
        })
        .then(response => response.json())
        .then(unlockData => {
          if (unlockData.success) {
            console.log(`💾 Achievement ${achievementKey} saved to database successfully!`);
          } else {
            console.error(`❌ Failed to save achievement ${achievementKey}:`, unlockData.error);
          }
        })
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
    console.log('🚨🚨🚨 CHECK AND UNLOCK ACHIEVEMENT CALLED! 🚨🚨🚨');
    console.log('🔍 Achievement Key:', achievementKey);
    console.log('👤 User ID:', userId);
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
    })
    .then(response => response.json())
    .then(data => {
      if (data.success && data.achievements) {
        // Check if this specific achievement is already unlocked
        const alreadyUnlocked = data.achievements.some(achievement => 
          achievement.key === achievementKey && achievement.unlocked_at
        );
        
        if (alreadyUnlocked) {
          console.log(`ℹ️ Achievement ${achievementKey} already unlocked - skipping popup`);
          return; // Don't show popup for already unlocked achievements
        }
        
        // Achievement not unlocked yet - unlock it and show popup
        console.log(`🏆 Achievement ${achievementKey} not yet unlocked - unlocking now`);
        
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
        })
        .then(response => response.json())
        .then(unlockData => {
          if (unlockData.success) {
            console.log(`🏆 Achievement ${achievementKey} unlocked successfully!`);
            // Show achievement popup
            const achievementTitles = {
              'first_line': 'First Line',
              'line_master': 'Line Master',
              'tetris_pro': 'Tetris Pro',
              'line_legend': 'Line Legend',
              'speed_demon': 'Speed Demon',
              'level_master': 'Level Master',
              'high_roller': 'High Roller',
              'score_hunter': 'Score Hunter',
              'point_master': 'Point Master',
              'tetris_king': 'Tetris King',
              'piece_dropper': 'Piece Dropper',
              'block_master': 'Block Master',
              'tetris_clear': 'Tetris Clear',
              'tetris_master': 'Tetris Master',
              'tetris_god': 'Tetris God',
              'combo_starter': 'Combo Starter',
              'combo_master': 'Combo Master',
              'combo_legend': 'Combo Legend',
              'back_to_back': 'Back to Back',
              'level_warrior': 'Level Warrior',
              'level_champion': 'Level Champion',
              'score_legend': 'Score Legend',
              'score_god': 'Score God',
              'line_destroyer': 'Line Destroyer',
              'piece_legend': 'Piece Legend',
              'tetris_legend': 'Tetris Legend'
            };
            const title = achievementTitles[achievementKey] || achievementKey;
            showAchievementNotification(achievementKey, title);
          } else {
            console.error(`❌ Failed to unlock achievement ${achievementKey}:`, unlockData.error);
          }
        })
        .catch(error => {
          console.error(`❌ Error unlocking achievement ${achievementKey}:`, error);
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
    const achievementTitles = {
      'first_line': 'First Line',
      'line_master': 'Line Master',
      'tetris_pro': 'Tetris Pro',
      'line_legend': 'Line Legend',
      'speed_demon': 'Speed Demon',
      'level_master': 'Level Master',
      'high_roller': 'High Roller',
      'score_hunter': 'Score Hunter',
      'point_master': 'Point Master',
      'tetris_king': 'Tetris King',
      'piece_dropper': 'Piece Dropper',
      'block_master': 'Block Master',
      'tetris_clear': 'Tetris Clear',
      'tetris_master': 'Tetris Master',
      'tetris_god': 'Tetris God',
      'combo_starter': 'Combo Starter',
      'combo_master': 'Combo Master',
      'combo_legend': 'Combo Legend',
      'back_to_back': 'Back-to-Back',
      'level_warrior': 'Level Warrior',
      'level_champion': 'Level Champion',
      'score_legend': 'Score Legend',
      'score_god': 'Score God',
      'line_destroyer': 'Line Destroyer',
      'piece_legend': 'Piece Legend',
      'tetris_legend': 'Tetris Legend'
    };
    
    const title = achievementTitles[achievementKey] || achievementKey;
    showAchievementNotification(achievementKey, title);
  }

  // 🧹 Clean up touch controls (Inside Game Scope)
  function cleanupTouchControls() {
    clearTimeout(dropHoldTimeout);
    clearTimeout(touchDropInterval);
    heldDown = false;
    unlockTetrisScroll();
  }

  // 🎉 Show Achievement Notification (Inside Game Scope)
  function showAchievementNotification(achievementKey, achievementTitle) {
    console.log('🚨🚨🚨 SHOW ACHIEVEMENT NOTIFICATION CALLED! 🚨🚨🚨');
    console.log('🏆 Achievement Unlocked:', achievementTitle);
    console.log('🔑 Achievement Key:', achievementKey);
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
    console.log(`🏆 Achievement Unlocked: ${achievementTitle}`);
    console.log(`📝 Popup added to array. Total popups: ${window.tetrisAchievementPopups.length}`);
    
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
    
    console.log(`🎨 Drawing ${window.tetrisAchievementPopups.length} achievement popups`);
    
    window.tetrisAchievementPopups.forEach((popup, index) => {
      popup.life--;
      
      // Remove expired popups
      if (popup.life <= 0) {
        window.tetrisAchievementPopups.splice(index, 1);
        return;
      }
      
      const alpha = popup.life / popup.maxLife;
      
      console.log(`🎨 Drawing popup ${index}: ${popup.title}, life=${popup.life}, alpha=${alpha}`);
      
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
  
  // 🔒 Disable page links/buttons (including guide button) when game starts
  lockTetrisScroll();
  disableTetrisPageButtons();
  
  // ✅ Final game loop initialization
  renderNextBlock(nextPiece);
  
  // 🔧 MOBILE FIX: Initialize touch controls properly
  console.log('📱 Tetris game started successfully');
  
  // Initialize touch controls for the canvas (always, not just mobile)
  initTouchControls(canvas);
  
  // 🔧 MOBILE FIX: Ensure touch controls stay active even after other games start
  setTimeout(() => {
    console.log('📱 Checking Tetris touch controls after delay...');
    const tetrisCanvas = document.getElementById('tetris-canvas');
    if (tetrisCanvas && tetrisCanvas.ontouchstart === null) {
      console.log('📱 Tetris touch controls lost - re-initializing...');
      initTouchControls(tetrisCanvas);
    }
  }, 2000);
  
  // 🔧 MOBILE FIX: Continuous touch control monitoring
  setInterval(() => {
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

function lockTetrisScroll() {
    document.body.style.overflow = "hidden";
}

function unlockTetrisScroll() {
    document.body.style.overflow = "";
}

// 🔒 Disable page links/buttons during Tetris gameplay (including guide button)
function disableTetrisPageButtons() {
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

// 🔓 Re-enable page links/buttons when Tetris game ends or pauses
function enableTetrisPageButtons() {
  document.querySelectorAll('a, button').forEach(el => {
    el.style.pointerEvents = '';
    el.style.opacity = '';
  });
  console.log('🔓 Page links/buttons re-enabled after Tetris gameplay');
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

// 🎨 Apply role theme on page load
setTimeout(() => {
  applyRoleTheme();
  console.log('🎨 Tetris role theme applied on load');
}, 500);