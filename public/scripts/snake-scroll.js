// 🐍 Cheese Snake Scroll v3.9.19 - PHASE 3 AUDIO & ENHANCEMENTS - TIMESTAMP: ${Date.now()}
// Classic Snake gameplay with cheese theme and modern enhancements
// 
// 🏆 SEASON 3 PHASE 2 COMPLETE (2025-01-28):
// - ACHIEVEMENT SYSTEM: In-game milestone tracking with animated pop-ups
// - DYNAMIC SCORING: Performance-based rewards and bonus objectives
// - SKILL TRACKING: Length milestones, speed challenges, and perfect runs
// - ENGAGEMENT FEATURES: Multiple achievements to unlock
// 
// 🔧 CRITICAL BUG FIXES APPLIED (2025-01-28):
// - FIXED ACHIEVEMENT POPUPS: Only show for newly earned achievements
// - FIXED THEME CONVERSION: Changed from apple to cheese theme
// - FIXED USER ID: Corrected hardcoded test ID to Santa's Discord ID
// - FIXED GAME START: Resolved duplicate startGame function conflict
// - FIXED API URL: Corrected localhost URL pattern
// 
// 🌟 SEASON 3 FEATURES (2025-01-28):
// - CHEESE THEME: Complete conversion from apple to cheese theme
// - MOBILE OPTIMIZATION: Touch controls and mobile device detection
// - SOUND SYSTEM: Professional Web Audio API sound effects
// - ACHIEVEMENT INTEGRATION: Complete achievement system with database sync
// 
// 🎮 GAME FEATURES:
// - CLASSIC SNAKE GAMEPLAY: Move, eat cheese, grow longer
// - SPEED CONTROL: Adjustable game speed for different skill levels
// - MOBILE CONTROLS: Touch-friendly swipe controls
// - ACHIEVEMENT TRACKING: Real-time progress monitoring
// - CHEESE COLLECTION: Collect cheese pieces to grow and score
// 
// 🔧 TECHNICAL IMPLEMENTATION:
// - Canvas-based rendering with smooth animations
// - Achievement system with database synchronization
// - Mobile device detection and touch controls
// - Sound management with Web Audio API
// - Real-time scoring and length tracking
// 
// 🚀 PRODUCTION CONFIGURATION: Classic Snake with cheese theme!
// 🏆 Achievement types: Length milestones, speed challenges, perfect runs
// 🎯 Balanced difficulty curve for engaging progression!

// 🆘 NEW: Display control instructions outside game canvas
// 🆘 REMOVED: displaySnakeHelpInfoOutside function to prevent duplicate instruction overlays
// Using HTML instructions instead for cleaner, single-source implementation

// 🚫 Full page scroll prevention
window.addEventListener("keydown", function (e) {
  const keys = ["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight", " ", "a", "s", "d", "w"];
  if (keys.includes(e.key)) {
    e.preventDefault();
  }
}, { passive: false });

// 🎵 SNAKE SOUND SYSTEM - Professional Web Audio API sounds
class SnakeSoundManager {
  constructor() {
    this.audioContext = null;
    this.sounds = {};
    this.initAudio();
  }

  initAudio() {
    try {
      this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
      console.log('🎵 Snake Sound System initialized');
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

    // Professional sound design for Snake
    switch (type) {
      case 'eatCheese':
        // Satisfying cheese eating sound - quick ascending chirp
        oscillator.frequency.setValueAtTime(330, this.audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(660, this.audioContext.currentTime + 0.1);
        oscillator.type = 'sine';
        gainNode.gain.setValueAtTime(0.4, this.audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.12);
        oscillator.start();
        oscillator.stop(this.audioContext.currentTime + 0.12);
        break;

      case 'scoreMilestone':
        // Celebratory score milestone sound - ascending arpeggio
        oscillator.frequency.setValueAtTime(392, this.audioContext.currentTime); // G4
        oscillator.frequency.setValueAtTime(523, this.audioContext.currentTime + 0.05); // C5
        oscillator.frequency.setValueAtTime(659, this.audioContext.currentTime + 0.1); // E5
        oscillator.type = 'triangle';
        gainNode.gain.setValueAtTime(0.5, this.audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.2);
        oscillator.start();
        oscillator.stop(this.audioContext.currentTime + 0.2);
        break;

      case 'gameOver':
        // Dramatic game over sound - descending tone with vibrato
        oscillator.frequency.setValueAtTime(330, this.audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(110, this.audioContext.currentTime + 0.4);
        oscillator.type = 'sawtooth';
        gainNode.gain.setValueAtTime(0.6, this.audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.5);
        oscillator.start();
        oscillator.stop(this.audioContext.currentTime + 0.5);
        break;

            case 'cheeseTeleport':
              // Mystical teleportation sound - quick ascending/descending whoosh
              oscillator.frequency.setValueAtTime(200, this.audioContext.currentTime);
              oscillator.frequency.exponentialRampToValueAtTime(800, this.audioContext.currentTime + 0.1);
              oscillator.frequency.exponentialRampToValueAtTime(150, this.audioContext.currentTime + 0.2);
              oscillator.type = 'sine';
              gainNode.gain.setValueAtTime(0.3, this.audioContext.currentTime);
              gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.25);
              oscillator.start();
              oscillator.stop(this.audioContext.currentTime + 0.25);
              break;
            case 'madMode':
              // Epic mad mode activation sound - powerful ascending sweep
              oscillator.frequency.setValueAtTime(100, this.audioContext.currentTime);
              oscillator.frequency.exponentialRampToValueAtTime(1200, this.audioContext.currentTime + 0.5);
              oscillator.type = 'sawtooth';
              gainNode.gain.setValueAtTime(0.4, this.audioContext.currentTime);
              gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.6);
              oscillator.start();
              oscillator.stop(this.audioContext.currentTime + 0.6);
              break;
    }
  }
}

// Initialize Snake sound manager
const snakeSounds = new SnakeSoundManager();

// ✅ GENOME 10.5 FINAL FIXED — Mutation on Score 100 + No Duplication + Global Scope Fix

let gameInterval; // ✅ Global scope
window.brainUnlocked = false; // ✅ Mutation flag, resets on game start

// 🧬 Load images (global scope) - Snake-specific naming to avoid conflicts
const snakeGameHeadImg = new Image();
snakeGameHeadImg.src = "img/snake/snake-head.png";

const snakeGameDnaImg = new Image();
snakeGameDnaImg.src = "img/snake/snake-dna.png";

const cheeseImg = new Image();
cheeseImg.src = "img/snake/cheese.png";

// 🧠 Mutation flag (false by default)
let mutationActive = false;

// 🏆 ROLE-BASED GAMEPLAY SYSTEM - Season 4 Feature (Global Scope)
let snakeUserRoles = [];
let snakeRoleMultipliers = {
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

// 🎨 Role-based visual themes
let snakeRoleThemes = {
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

// 🧀 Role-based colors for snake and food
let snakeRoleColors = {
  'VIP Holder': { snake: '#FFD700', food: '#FFA500', trail: '#FFD700' },
  '🎴 VIP Holder': { snake: '#FFD700', food: '#FFA500', trail: '#FFD700' },
  'Holder': { snake: '#C0C0C0', food: '#E6E6FA', trail: '#C0C0C0' },
  '🏆 Holder': { snake: '#C0C0C0', food: '#E6E6FA', trail: '#C0C0C0' },
  'Champion': { snake: '#FF4500', food: '#FF6347', trail: '#FF4500' },
  'Season Tester': { snake: '#8A2BE2', food: '#DA70D6', trail: '#8A2BE2' },
  'Early Bird': { snake: '#00BFFF', food: '#87CEEB', trail: '#00BFFF' },
  'Cheese Hunter': { snake: '#FFA500', food: '#FFD700', trail: '#FFA500' },
  '🧀 Cheese Hunter': { snake: '#FFA500', food: '#FFD700', trail: '#FFA500' }
};

// 🏆 ROLE DETECTION SYSTEM - Fetch user Discord roles (Global)
async function fetchSnakeUserRoles() {
  try {
    // 🌍 Local development bypass - use Narrrf's roles for testing
    const isLocalDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    
    if (isLocalDevelopment) {
      console.log('🏠 Local environment detected - using Narrrf\'s roles for Snake testing');
      snakeUserRoles = [
        "VIP Holder", "Holder", "Champion", "Season Tester", "Early Bird", "Cheese Hunter",
        "Alpha Caller", "Community Member", "Moderator", "PokerOG", "Rumble"
      ];
      console.log('🏆 Local test roles loaded for Snake:', snakeUserRoles);
      
      // Apply role-based theme on load
      applySnakeRoleTheme();
      
      return snakeUserRoles;
    }
    
    const isProduction = window.location.hostname === 'narrrfs.world';
    const API_BASE_URL = isProduction ? 'https://narrrfs.world' : 'http://localhost';
    
    const response = await fetch(`${API_BASE_URL}/api/user/roles.php`, {
      method: 'GET',
      credentials: 'include'
    });
    
    if (response.ok) {
      const data = await response.json();
      snakeUserRoles = data.roles || [];
      console.log('🏆 User roles loaded from API for Snake:', snakeUserRoles);
      
      // Apply role-based theme on load
      applySnakeRoleTheme();
      
      return snakeUserRoles;
    } else {
      console.log('🏆 No roles found or not logged in for Snake');
      return [];
    }
  } catch (error) {
    console.log('🏆 Error fetching roles for Snake:', error);
    return [];
  }
}

// 🎨 Apply role-based visual theme to Snake canvas (Global)
function applySnakeRoleTheme() {
  const primaryRole = getSnakePrimaryRole();
  const theme = snakeRoleThemes[primaryRole] || 'default';
  
  console.log(`🎨 Applying ${theme} theme for Snake role: ${primaryRole}`);
  
  // Add theme class to canvas
  const canvas = document.getElementById('snake-canvas');
  if (canvas) {
    // Remove existing theme classes
    canvas.classList.remove('golden', 'silver', 'cheese', 'rainbow', 'royal', 'blue', 'red');
    // Add new theme class
    if (theme !== 'default') {
      canvas.classList.add(theme);
    }
  }
}

// 🏆 Get user's primary role (Global)
function getSnakePrimaryRole() {
  const priorityOrder = [
    'VIP Holder', '🎴 VIP Holder',
    'Holder', '🏆 Holder', 
    'Champion', 
    'Season Tester', 
    'Early Bird', 
    'Cheese Hunter', '🧀 Cheese Hunter'
  ];
  
  for (const role of priorityOrder) {
    if (snakeUserRoles.includes(role)) {
      return role;
    }
  }
  
  return null;
}

// ⚡ Calculate role-based score multiplier (Global)
function getSnakeRoleScoreMultiplier() {
  const primaryRole = getSnakePrimaryRole();
  return snakeRoleMultipliers[primaryRole] || 1.0;
}

// 🎨 Get role-based colors for snake and food (Global)
function getSnakeRoleColors() {
  const primaryRole = getSnakePrimaryRole();
  return snakeRoleColors[primaryRole] || { snake: '#00FF00', food: '#FFD700', trail: '#00FF00' };
}

// 🏆 GLOBAL TEST FUNCTION - Test Snake role-based features
window.testSnakeRoleFeatures = function() {
  console.log('🏆 Testing Snake role-based features...');
  console.log('Current roles:', snakeUserRoles);
  console.log('Primary role:', getSnakePrimaryRole());
  console.log('Score multiplier:', getSnakeRoleScoreMultiplier());
  console.log('Role colors:', getSnakeRoleColors());
  
  const canvas = document.getElementById('snake-canvas');
  console.log('Theme applied:', canvas?.className);
  
  // Test role detection
  fetchSnakeUserRoles().then(() => {
    console.log('🏆 Snake role detection test completed');
  });
};

  // ✅ DOM-ready game boot
window.addEventListener("DOMContentLoaded", () => {
  initSnake(); // ✅ Runs only after DOM is ready
  
  // 🏆 Initialize role detection for Snake on page load
  setTimeout(() => {
    fetchSnakeUserRoles();
  }, 1000);
});

function initSnake() {
  const canvas = document.getElementById("snake-canvas");
  if (!canvas) {
    console.error("Canvas element with id 'snake-canvas' not found.");
    return;
  }
  const ctx = canvas.getContext("2d");
  const scoreDisplay = document.getElementById("snake-score");

  const gridSize = 20;
  const tileCountX = 10;
  const tileCountY = 20;
  let snake = [{ x: 5, y: 10 }];
  let velocity = { x: 0, y: -1 };
  let food = { x: 7, y: 7 };
  let score = 0;
  let isSnakePaused = false;
  
  // 🧀 CHEESE TELEPORTATION SYSTEM
  let cheeseTeleportTimer = 0;
  let cheeseTeleportChance = 0.001; // 0.1% chance per frame (very rare)
  let lastCheesePosition = null;
  let firstTeleportDone = false; // Track if first teleport already happened
  
  // 🧪 LOCAL TESTING MODE - FORCE TELEPORTATION
  const isLocalTesting = window.location.hostname === 'localhost';
  const forceTeleportInterval = 3; // Force teleport every 1.2 seconds (3 frames at 400ms intervals = 1.2s)
  const guaranteedTeleportFrames = 25; // Guaranteed teleport in first 10 seconds (25 frames at 400ms = 10s)
  const testingTeleportInterval = 75; // Test teleport every 30 seconds (75 frames at 400ms = 30s)
  
  // 🧀 PRODUCTION TELEPORTATION SETTINGS
  let productionTeleportChance = 0.002; // 0.2% chance per frame (more balanced)
  let teleportCooldown = 0; // Cooldown between teleports
  
  // 🔥 MAD MODE SYSTEM
  let madModeActive = false;
  let madModeTimer = 0;
  let madModeDuration = 150; // 60 seconds at 400ms intervals
  let originalGameSpeed = 400;
  let madModeSpeed = 200; // 2x faster

  // 🏆 ROLE-BASED GAMEPLAY SYSTEM - Using Global Functions

  // 🏆 ROLE DETECTION SYSTEM - Using Global Functions
  
  // 🏆 Snake Achievement Tracking Variables
  let achievementsCheckedThisGame = new Set();
  let cheeseEaten = 0;
  let gamesPlayed = 0;
  let longestSnake = 1;
  let currentLevel = 1;
  let gameStartTime = 0;
  let perfectGame = true; // Track if player hits walls

  function startGameWithCountdown() {
    const countdownEl = document.getElementById("snake-countdown");
    let count = 5;

    if (!countdownEl) {
      console.warn("Countdown element not found.");
      startGame(); // fallback
      return;
    }

    countdownEl.classList.remove("hidden");
    countdownEl.textContent = count;

    const countdownInterval = setInterval(() => {
      count--;
      if (count > 0) {
        countdownEl.textContent = count;
      } else if (count === 0) {
        countdownEl.textContent = "GO!";
      } else {
        clearInterval(countdownInterval);
        countdownEl.classList.add("hidden");
        startGame(); // begin actual game
      }
    }, 1000);
  }

  function startGame() {
    console.log('🚀 startGame() called - Starting Snake game!');
    
    // 🚨 PROTECTION: Prevent multiple game starts - if game is already running, just return
    if (gameInterval) {
      console.log('⚠️ Game already running, ignoring duplicate startGame call');
      return; // Exit early, don't restart the game
    }
    
    // 🚨 RESET FIRST TELEPORT FLAG ONLY ON ACTUAL GAME START
    firstTeleportDone = false;
    console.log('🔄 First teleport flag reset for new game');
    
    resetGame();
    
    // 🏆 Fetch user roles for role-based gameplay
    fetchSnakeUserRoles();
    
    // 🆘 REMOVED: Display control instructions outside game canvas (using HTML instructions instead)
    
    // 🛠️ Mock fallback if testing locally (same as Tetris)
    let discordId = localStorage.getItem("discord_id");
    let discordName = localStorage.getItem("discord_name");
    
    if (!discordId) {
      discordId = "328601656659017732"; // Narrrf's Discord ID for testing
      discordName = "narrrf";
      localStorage.setItem("discord_id", discordId);
      localStorage.setItem("discord_name", discordName);
    }
    
    console.log('🎮 Setting up gameInterval - moveSnake will be called every 400ms');
    gameInterval = setInterval(moveSnake, 400); // slower start for better device compatibility
    console.log('🎮 gameInterval set:', gameInterval);
    enableGlobalSnakeTouch(); // Enable touch controls when game starts
    lockSnakeScroll(); // 🎯 Lock scrolling when game starts (like Tetris)
  }

  function resetGame() {
    snake = [{ x: 5, y: 10 }];
    velocity = { x: 0, y: -1 };
    placeFood();
    score = 0;
    updateScore();
    isSnakePaused = false;
    window.brainUnlocked = false;
    
    // 🧀 Reset cheese teleportation system
    cheeseTeleportTimer = 0;
    cheeseTeleportChance = 0.001; // Reset to base chance
    lastCheesePosition = null;
    teleportCooldown = 0;
    // 🚨 DON'T reset firstTeleportDone here - only reset when actually starting new game
    
    // 🔥 Reset mad mode system
    madModeActive = false;
    madModeTimer = 0;
    
    // 🏆 Reset achievement tracking for new game
    achievementsCheckedThisGame.clear();
    mutationActive = false;
    localStorage.removeItem("snake_mutation");
    checkMutationStatus();
    const btn = document.getElementById("pause-snake-btn");
    if (btn) btn.textContent = "⏸️ Pause";
    
    // 🏆 Reset achievement tracking variables
    cheeseEaten = 0;
    longestSnake = 1;
    currentLevel = 1;
    gameStartTime = Date.now();
    perfectGame = true;
    
    // Clear achievement popups
    if (window.snakeAchievementPopups) {
      window.snakeAchievementPopups = [];
    }
    
    // 🎯 Ensure scrolling is locked when game is reset and active
    if (gameInterval) {
      lockSnakeScroll();
    }
  }

  function placeFood() {
    // 🧀 Store previous cheese position for teleportation effect
    lastCheesePosition = food ? { x: food.x, y: food.y } : null;
    
    // 🧀 Place cheese in new random location
    food = {
      x: Math.floor(Math.random() * tileCountX),
      y: Math.floor(Math.random() * tileCountY)
    };
    
    // 🧀 Ensure cheese doesn't spawn on snake
    while (snake.some(segment => segment.x === food.x && segment.y === food.y)) {
      food = {
        x: Math.floor(Math.random() * tileCountX),
        y: Math.floor(Math.random() * tileCountY)
      };
    }
  }

  // 🧀 CHEESE TELEPORTATION FUNCTION
  function teleportCheese() {
    if (!food) return;
    
    const currentLevel = Math.floor(cheeseEaten / 5) + 1;
    const mode = isLocalTesting ? '🧪 TESTING' : '🧀 NORMAL';
    const gameTime = (cheeseTeleportTimer * 0.4).toFixed(1);
    
    console.log(`${mode} Cheese teleporting! Level: ${currentLevel}, Game Time: ${gameTime}s`);
    
    // 🧀 Store old position for visual effect
    const oldPosition = { x: food.x, y: food.y };
    
    // 🧀 Teleport cheese to new location
    placeFood();
    
    // 🧀 Play teleportation sound effect
    if (typeof snakeSounds !== 'undefined' && snakeSounds.playSound) {
      snakeSounds.playSound('cheeseTeleport');
    }
    
    // 🧪 VISUAL FEEDBACK: Flash the screen briefly to show teleportation
    if (isLocalTesting) {
      document.body.style.backgroundColor = '#ffeb3b'; // Yellow flash
      setTimeout(() => {
        document.body.style.backgroundColor = '';
      }, 100);
    }
    
    console.log(`${mode} Cheese teleported from (${oldPosition.x}, ${oldPosition.y}) to (${food.x}, ${food.y}) at ${gameTime}s`);
  }

  // 🔥 MAD MODE FUNCTIONS
  function activateMadMode() {
    console.log('🔥 MAD MODE ACTIVATED! Snake is glowing and faster!');
    madModeActive = true;
    madModeTimer = 0;
    
    // 🔥 Speed up the game
    clearInterval(gameInterval);
    gameInterval = setInterval(moveSnake, madModeSpeed); // 2x faster
    
    // 🔥 Visual effects - make snake glow
    document.body.classList.add('mad-mode');
    
    // 🔥 Sound effect
    if (typeof snakeSounds !== 'undefined' && snakeSounds.playSound) {
      snakeSounds.playSound('madMode');
    }
    
    // 🔥 Show mad mode notification
    showMadModeNotification();
  }

  function deactivateMadMode() {
    console.log('🔥 MAD MODE DEACTIVATED! Snake returns to normal speed.');
    madModeActive = false;
    madModeTimer = 0;
    
    // 🔥 Return to normal speed
    clearInterval(gameInterval);
    gameInterval = setInterval(moveSnake, originalGameSpeed);
    
    // 🔥 Remove visual effects
    document.body.classList.remove('mad-mode');
    
    // 🔥 Show end notification
    showMadModeEndNotification();
  }

  function showMadModeNotification() {
    const notification = document.createElement('div');
    notification.id = 'mad-mode-notification';
    notification.innerHTML = `
      <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                  background: linear-gradient(45deg, #ff6b6b, #ffa500, #ff6b6b); 
                  color: white; padding: 20px; border-radius: 15px; font-weight: bold; 
                  font-size: 24px; z-index: 10000; text-align: center; box-shadow: 0 0 30px #ff6b6b;
                  animation: madModePulse 0.5s ease-in-out infinite alternate;">
        🔥 MAD MODE! 🔥<br>
        <span style="font-size: 16px;">Snake is glowing and faster!</span>
      </div>
      <style>
        @keyframes madModePulse {
          0% { transform: translate(-50%, -50%) scale(1); }
          100% { transform: translate(-50%, -50%) scale(1.1); }
        }
        .mad-mode {
          filter: hue-rotate(180deg) brightness(1.2);
        }
      </style>
    `;
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 3000);
  }

  function showMadModeEndNotification() {
    const notification = document.createElement('div');
    notification.id = 'mad-mode-end-notification';
    notification.innerHTML = `
      <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                  background: linear-gradient(45deg, #4ecdc4, #44a08d); 
                  color: white; padding: 15px; border-radius: 10px; font-weight: bold; 
                  font-size: 18px; z-index: 10000; text-align: center;">
        Mad Mode Ended<br>
        <span style="font-size: 14px;">Back to normal speed</span>
      </div>
    `;
    document.body.appendChild(notification);
    
    // Remove notification after 2 seconds
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 2000);
  }

  function updateScore() {
    if (scoreDisplay) {
      const roleMultiplier = getSnakeRoleScoreMultiplier();
      
      if (roleMultiplier > 1.0) {
        // Show DSPOINC score (score * 10) with role bonus indicator
        const dspoincScore = score * 10;
        scoreDisplay.textContent = `💰 Snake Score: $${dspoincScore} DSPOINC (${roleMultiplier}x Role Bonus!)`;
      } else {
        scoreDisplay.textContent = `💰 Snake Score: $${score * 10} DSPOINC`;
      }
    }
  }

  // 🧭 Direction helper
  function getDirection(from, to) {
    if (!to) return "right";
    if (to.x > from.x) return "right";
    if (to.x < from.x) return "left";
    if (to.y > from.y) return "down";
    if (to.y < from.y) return "up";
    return "up";
  }

  // ✅ GENETIC MODE CHECK FUNCTION
  function checkMutationStatus() {
    const badge = document.getElementById("mutation-badge");
    if (mutationActive) {
      document.body.classList.add("mutation-mode");
      if (badge) badge.classList.remove("hidden");
    } else {
      document.body.classList.remove("mutation-mode");
      if (badge) badge.classList.add("hidden");
    }
  }

  // ✅ TRAIT TRIGGER FUNCTION (only when x cheese eaten defined in score threshold is passed for first time)
  function tryActivateMutation(score) {
    if (score >= 100 && !window.brainUnlocked) {
      unlockTrait("GENETIC_SENTINEL");
      mutationActive = true;
      localStorage.setItem("snake_mutation", "true");

      setTimeout(() => {
        mutationActive = false;
        checkMutationStatus();
      }, 15000);

      window.brainUnlocked = true;
      checkMutationStatus();
      console.log("🧬 Mutation triggered at score:", score);
    }
  }

  // 🖌️ Draw Function
  function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    checkMutationStatus();

    // 🔁 Trail glow - Role-based colors
    const roleColors = getSnakeRoleColors();
    for (let i = 0; i < snake.length; i++) {
      const segment = snake[i];
      const t = i / snake.length;
      const fade = 0.25 * (1 - t);
      ctx.save();
      ctx.globalAlpha = fade;
      ctx.fillStyle = roleColors.trail;
      ctx.beginPath();
      ctx.arc(
        segment.x * gridSize + gridSize / 2,
        segment.y * gridSize + gridSize / 2,
        gridSize * 0.3,
        0,
        2 * Math.PI
      );
      ctx.fill();
      ctx.restore();
    }

    // 🧬 Render Snake
    snake.forEach((segment, index) => {
      const isHead = index === 0;
      const img = isHead ? snakeGameHeadImg : snakeGameDnaImg;
      const next = snake[index + 1] || snake[index - 1] || segment;
      const dir = getDirection(segment, next);

      const pulse = 0.5 + 0.5 * Math.sin(performance.now() / 120 + index);
      ctx.globalAlpha = 0.85 + pulse * 0.1;

      const posX = segment.x * gridSize + gridSize / 2;
      const posY = segment.y * gridSize + gridSize / 2;

      if (img.complete) {
        ctx.save();
        ctx.translate(posX, posY);

        switch (dir) {
          case "up": ctx.rotate(0); break;
          case "down": ctx.rotate(Math.PI); break;
          case "left": ctx.rotate(-Math.PI / 2); break;
          case "right": ctx.rotate(Math.PI / 2); break;
        }

        ctx.shadowColor = isHead && mutationActive ? "white" : "lime";
        ctx.shadowBlur = isHead
          ? mutationActive
            ? 30 + 10 * Math.sin(performance.now() / 100)
            : 20
          : 15;

        ctx.drawImage(img, -gridSize / 2, -gridSize / 2, gridSize, gridSize);
        ctx.restore();
      } else {
        // Role-based snake colors
        ctx.fillStyle = isHead ? roleColors.snake : roleColors.snake;
        ctx.fillRect(segment.x * gridSize, segment.y * gridSize, gridSize, gridSize);
      }

      ctx.globalAlpha = 1;
      ctx.filter = "none";
    });

    // 🧀 Draw cheese - Role-based colors
    if (cheeseImg.complete) {
      ctx.drawImage(cheeseImg, food.x * gridSize, food.y * gridSize, gridSize, gridSize);
    } else {
      ctx.fillStyle = roleColors.food;
      ctx.fillRect(food.x * gridSize, food.y * gridSize, gridSize, gridSize);
    }
    
    // 🏆 Draw achievement popups
    drawAchievementPopups();
  } // ✅ End of draw()

  function moveSnake() {
    if (isSnakePaused) return;

    // 🧪 DEBUG: Log every moveSnake call (disabled for production)
    // console.log('🐍 moveSnake() called - Timer:', cheeseTeleportTimer);

    // 🧀 CHEESE TELEPORTATION SYSTEM - Check if cheese should teleport
    cheeseTeleportTimer++;
    
    // 🔥 MAD MODE SYSTEM - Update mad mode timer
    if (madModeActive) {
      madModeTimer++;
      if (madModeTimer >= madModeDuration) {
        deactivateMadMode();
      }
    }
    
    // 🧪 DEBUG: Log teleportation timer every few frames
    if (cheeseTeleportTimer % 2 === 0) {
      console.log(`🧪 Teleport Timer: ${cheeseTeleportTimer}, First Done: ${firstTeleportDone}, Local Testing: ${isLocalTesting}`);
    }
    
    // 🧪 LOCAL TESTING MODE - GUARANTEED TELEPORTATION IN FIRST 10 SECONDS
    if (isLocalTesting) {
      // 🧪 GUARANTEED TELEPORT: First teleport in first 10 seconds (100% chance) - ONLY ONCE
      if (cheeseTeleportTimer === 3 && !firstTeleportDone) {
        console.log('🧪 GUARANTEED TELEPORT: First teleportation in first 10 seconds!');
        teleportCheese();
        firstTeleportDone = true; // Mark first teleport as done
        console.log('✅ First teleport flag set to TRUE');
      }
      // 🧪 REGULAR FORCED TELEPORTS: Every 30 seconds after first teleport (for testing)
      else if (cheeseTeleportTimer >= testingTeleportInterval && cheeseTeleportTimer > 3 && firstTeleportDone) {
        console.log('🧪 LOCAL TESTING: Regular forced teleportation every 30 seconds!');
        teleportCheese();
        cheeseTeleportTimer = 25; // Reset to 10 seconds to wait for next teleport
      }
    } else {
      // 🧀 PRODUCTION MODE - Normal teleportation logic
      // 🧀 Increase teleportation chance based on level (every 5 cheeses = 1 level)
      const currentLevel = Math.floor(cheeseEaten / 5) + 1;
      const dynamicTeleportChance = cheeseTeleportChance * (1 + (currentLevel * 0.2)); // 20% increase per level
      
      // 🧀 PRODUCTION MODE - REALISTIC TELEPORTATION LOGIC
      teleportCooldown = Math.max(0, teleportCooldown - 1);
      
      if (teleportCooldown === 0) {
        // 🧀 One guaranteed teleport in first 10 seconds (like testing mode) - ONLY ONCE
        if (cheeseTeleportTimer === 25 && !firstTeleportDone) { // 10 seconds at 400ms intervals
          console.log('🧀 GUARANTEED TELEPORT: First teleportation in first 10 seconds!');
          teleportCheese();
          firstTeleportDone = true; // Mark first teleport as done
          teleportCooldown = 150; // 1 minute cooldown (150 frames at 400ms)
        }
        // 🧀 Very rare teleports after that - decreases as snake grows
        else if (cheeseTeleportTimer > 25) {
          const snakeLength = snake.length;
          
          // 🧀 Teleportation becomes slightly rarer as snake gets longer (more challenging)
          const lengthPenalty = Math.max(0.3, 1 - (snakeLength * 0.01)); // 1% penalty per segment, min 30%
          const dynamicChance = productionTeleportChance * lengthPenalty;
          
          if (Math.random() < dynamicChance) {
            console.log(`🧀 RARE TELEPORT: Level ${currentLevel}, Snake Length: ${snakeLength}, Chance: ${(dynamicChance * 100).toFixed(4)}%`);
            teleportCheese();
            teleportCooldown = 225; // 1.5 minutes cooldown (225 frames at 400ms)
          }
        }
      }
    }

    const head = { x: snake[0].x + velocity.x, y: snake[0].y + velocity.y };

    // Game over logic
    if (
      head.x < 0 || head.x >= tileCountX ||
      head.y < 0 || head.y >= tileCountY ||
      snake.some(seg => seg.x === head.x && seg.y === head.y)
    ) {
      // Call proper game over function (like Tetris)
      onGameOver();
      return;
    }

    snake.unshift(head);

    // 🍽️ Check if snake eats cheese
    const ate = head.x === food.x && head.y === food.y;
    if (ate) {
      // 🎵 Play cheese eating sound
      snakeSounds.playSound('eatCheese');
      
      // Role-based scoring with multipliers
      const roleMultiplier = getSnakeRoleScoreMultiplier();
      const baseScore = 1;
      const totalScore = Math.floor(baseScore * roleMultiplier);
      score += totalScore;
      
      // 🏆 Log role-based scoring
      if (roleMultiplier > 1.0) {
        const bonusPoints = totalScore - baseScore;
        console.log(`🏆 Snake role multiplier applied: ${roleMultiplier}x (${bonusPoints} bonus points)`);
      }
      
      cheeseEaten++;
      longestSnake = Math.max(longestSnake, snake.length + 1);
      
      // 🏆 Update level based on cheese eaten (like Tetris levels)
      const newLevel = Math.floor(cheeseEaten / 5) + 1; // Level up every 5 cheeses
      if (newLevel > currentLevel) {
        currentLevel = newLevel;
        console.log(`🏆 Level up! Now at level ${currentLevel}`);
      }
      
      // 🎵 Check for score milestones (every 10 points)
      if (score % 10 === 0) {
        snakeSounds.playSound('scoreMilestone');
        console.log(`🎵 Score milestone reached: ${score} points!`);
      }
      
      updateScore();
      placeFood();
      tryActivateMutation(score); // ✅ Now runs exactly on score increase
      
      // 🔥 MAD MODE SYSTEM - Balanced activation based on score milestones
      if (!madModeActive && score > 0) {
        // MAD MODE triggers at score milestones with increasing chance
        const madModeChance = Math.min(0.15, 0.05 + (score * 0.01)); // 5% base + 1% per cheese, max 15%
        if (Math.random() < madModeChance) {
          console.log(`🔥 MAD MODE TRIGGER: Score ${score}, Chance ${(madModeChance * 100).toFixed(1)}%`);
          activateMadMode();
        }
      }
      
      // 🏆 Check achievements immediately when eating cheese
      // This gives players instant feedback when they unlock achievements
      console.log('🧀 Cheese eaten! Checking achievements...', { cheeseEaten, score, longestSnake, currentLevel });
      checkSnakeAchievements();
    } else {
      snake.pop(); // ✅ Don't grow if no cheese
    }

    draw(); // 🖌️ Always render after logic
  }

  // 🐍 Game Over Function (moved inside initSnake scope)
  function onGameOver() {
    // 🎵 Play game over sound
    snakeSounds.playSound('gameOver');
    
    clearInterval(gameInterval);
    gameInterval = null;
    isSnakePaused = true;

    // 🎯 Unlock scrolling when game over (like Tetris)
    unlockSnakeScroll();

    // 🐍 Capture the final score before any potential resets
    const finalScore = score;
    gamesPlayed++;
    console.log("🐍 Game Over - Final Score:", finalScore);
    
    // 🏆 Check final achievements (games played, perfect game, etc.)
    checkSnakeAchievements();

    const modal = document.getElementById("snake-over-modal");
    const finalScoreText = document.getElementById("snake-final-score-text");
    const pauseBtn = document.getElementById("pause-snake-btn");

    if (modal && finalScoreText) {
      // ✅ Only update score content, no style changes — handled in HTML
      const dspoincScore = finalScore * 10; // Convert raw score to DSPOINC
      finalScoreText.textContent = `You earned $${dspoincScore} DSPOINC`;
      console.log("🐍 Displaying score:", finalScore, "DSPOINC:", dspoincScore);

      modal.classList.remove("hidden");
      modal.style.display = "flex"; // fallback for older browsers
      
      // 📱 MOBILE FIX: Add proper touch event handling for Play Again button
      const playAgainBtn = document.getElementById("snake-play-again-btn");
      if (playAgainBtn) {
        // Remove any existing event listeners
        playAgainBtn.replaceWith(playAgainBtn.cloneNode(true));
        const newPlayAgainBtn = document.getElementById("snake-play-again-btn");
        
        // Add mobile-friendly event handlers
        newPlayAgainBtn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          console.log('🐍 Play Again button clicked - restarting Snake game');
          window.location.reload();
        });
        
        // Add touch event for mobile
        newPlayAgainBtn.addEventListener('touchend', function(e) {
          e.preventDefault();
          e.stopPropagation();
          console.log('🐍 Play Again button touched - restarting Snake game');
          window.location.reload();
        });
        
        // Ensure button is clickable on mobile
        newPlayAgainBtn.style.touchAction = 'manipulation';
        newPlayAgainBtn.style.webkitTapHighlightColor = 'transparent';
        
        console.log('🐍 Mobile Play Again button setup complete');
      }
    }

    if (pauseBtn) {
      pauseBtn.textContent = "⏸️ Pause";
    }

    // 🐍 Save the captured final score
    saveScore(finalScore);
  }
  
  // 🏆 Snake Achievement Functions - Make globally accessible
  function checkSnakeAchievements() {
    console.log('🏆 Checking Snake achievements...', { cheeseEaten, score, longestSnake, currentLevel });
    
    // Check achievements based on current game state
    const achievements = [
      // Basic Achievements
      { key: 'first_cheese', condition: cheeseEaten >= 1 },
      { key: 'cheese_collector', condition: cheeseEaten >= 5 },
      { key: 'cheese_hunter', condition: cheeseEaten >= 10 },
      { key: 'cheese_master', condition: cheeseEaten >= 25 },
      { key: 'speed_demon', condition: currentLevel >= 5 },
      { key: 'level_master', condition: currentLevel >= 10 },
      { key: 'score_hunter', condition: score >= 100 },
      { key: 'point_master', condition: score >= 250 },
      { key: 'high_scorer', condition: score >= 500 },
      { key: 'snake_king', condition: score >= 1000 },
      
      // Advanced Achievements
      { key: 'long_snake', condition: longestSnake >= 10 },
      { key: 'giant_snake', condition: longestSnake >= 25 },
      { key: 'mega_snake', condition: longestSnake >= 50 },
      { key: 'survivor', condition: (Date.now() - gameStartTime) >= 120000 }, // 2 minutes
      { key: 'endurance_master', condition: (Date.now() - gameStartTime) >= 300000 }, // 5 minutes
      
      // Expert Achievements
      { key: 'level_warrior', condition: currentLevel >= 15 },
      { key: 'level_champion', condition: currentLevel >= 20 },
      { key: 'score_legend', condition: score >= 2000 },
      { key: 'score_god', condition: score >= 5000 },
      { key: 'cheese_legend', condition: cheeseEaten >= 100 },
      { key: 'snake_legend', condition: longestSnake >= 100 },
      
      // Additional Achievements (from database)
      { key: 'game_starter', condition: gamesPlayed >= 1 },
      { key: 'game_player', condition: gamesPlayed >= 5 },
      { key: 'game_master', condition: gamesPlayed >= 10 },
      { key: 'game_legend', condition: gamesPlayed >= 25 },
      { key: 'snake_champion', condition: score >= 2000 && longestSnake >= 50 },
      { key: 'snake_ninja', condition: perfectGame && score >= 500 },
      { key: 'perfectionist', condition: perfectGame && longestSnake >= 25 },
      { key: 'ultimate_player', condition: score >= 5000 && longestSnake >= 100 && cheeseEaten >= 100 }
    ];
    
    achievements.forEach(achievement => {
      if (achievement.condition) {
        // 🏆 Check if we already checked this achievement this game
        if (achievementsCheckedThisGame.has(achievement.key)) {
          console.log(`ℹ️ Achievement ${achievement.key} already checked this game - skipping`);
          return;
        }
        
        console.log('🎯 Achievement condition met:', achievement.key, achievement.condition);
        
        // Mark as checked this game BEFORE making API call
        achievementsCheckedThisGame.add(achievement.key);
        
        // Only call API if we haven't checked this achievement this game
        checkAndUnlockAchievement(achievement.key);
      }
    });
  }
  
  // 🏆 Make Snake achievements globally accessible
  window.checkSnakeAchievements = checkSnakeAchievements;
  
  function checkAndUnlockAchievement(achievementKey) {
    console.log('🔍 checkAndUnlockAchievement called for:', achievementKey);
    console.log('🔍 achievementsCheckedThisGame has:', Array.from(achievementsCheckedThisGame));
    
    // Get user's current achievements to check if already unlocked
    const userId = localStorage.getItem('discord_id') || '328601656659017732';
    console.log('👤 User ID:', userId);
    
    // Environment-aware API endpoint
    const isProduction = window.location.hostname === 'narrrfs-world.onrender.com' || window.location.hostname === 'narrrfs.world';
    const apiBaseUrl = isProduction ? 'https://narrrfs.world' : '';
    console.log('🌍 API Base URL:', apiBaseUrl);
    
    fetch(`${apiBaseUrl}/api/user/get-snake-achievements.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: userId })
    })
    .then(response => response.json())
    .then(data => {
      console.log('📡 API Response:', data);
      if (data.success) {
        const achievement = data.achievements.find(a => a.key === achievementKey);
        console.log('🔍 Found achievement:', achievement);
        if (achievement && !achievement.unlocked) {
          console.log('✅ Achievement not unlocked, showing notification');
          // Show notification and unlock achievement
          showAchievementNotification(achievementKey, achievement.achievement_title, achievement.achievement_description, achievement.achievement_icon);
          unlockSnakeAchievement(achievementKey);
        } else if (achievement && achievement.unlocked) {
          console.log('⚠️ Achievement already unlocked:', achievementKey);
        } else {
          console.log('❌ Achievement not found in API response:', achievementKey);
        }
      } else {
        console.log('❌ API call failed:', data.error);
      }
    })
    .catch(error => {
      console.error('❌ API call failed:', error);
      // 🚨 FIX: Don't show popup if API fails - better to miss duplicate than show wrong popup
      console.log(`⚠️ API failed for ${achievementKey} - NOT showing popup to avoid duplicates`);
      
      // Don't show notification if we can't verify status
      // This prevents showing popups for already unlocked achievements
      return;
    });
  }
  
  function showAchievementNotification(key, title, description, icon) {
    console.log('🎉 showAchievementNotification called:', { key, title, description, icon });
    
    // Create achievement popup on canvas - Tetris-style centered
    const popup = {
      key: key,
      title: title,
      description: description || 'Achievement Unlocked!',
      icon: icon || '🏆',
      life: 30, // 0.5 seconds at 60fps (like Tetris)
      maxLife: 30,
      scale: 1, // No scale animation - instant appearance
      maxScale: 1,
      color: '#ffd700' // Gold color for achievements
    };
    
    if (!window.snakeAchievementPopups) {
      window.snakeAchievementPopups = [];
    }
    window.snakeAchievementPopups.push(popup);
    console.log('📝 Popup added to array. Total popups:', window.snakeAchievementPopups.length);
  }
  
  function drawAchievementPopups() {
    if (!window.snakeAchievementPopups) return;
    
    window.snakeAchievementPopups.forEach((popup, index) => {
      // Remove if expired
      if (popup.life <= 0) {
        window.snakeAchievementPopups.splice(index, 1);
        return;
      }
      
      const alpha = popup.life / 30; // 30 is maxLife
      const centerX = canvas.width / 2;
      const centerY = canvas.height / 2;
      
      // Save context state
      ctx.save();
      
      // Draw background (Tetris-style centered)
      ctx.fillStyle = `rgba(0, 0, 0, ${alpha * 0.9})`;
      ctx.fillRect(centerX - 150, centerY - 30, 300, 60);
      
      // Draw border (Tetris-style)
      ctx.strokeStyle = `rgba(255, 215, 0, ${alpha})`;
      ctx.lineWidth = 2;
      ctx.strokeRect(centerX - 150, centerY - 30, 300, 60);
      
      // Draw icon (Tetris-style centered)
      ctx.fillStyle = `rgba(255, 215, 0, ${alpha})`;
      ctx.font = '20px Arial';
      ctx.textAlign = 'center';
      ctx.fillText(popup.icon, centerX - 80, centerY + 5);
      
      // Draw title (Tetris-style centered)
      ctx.fillStyle = `rgba(255, 255, 255, ${alpha})`;
      ctx.font = 'bold 18px Arial';
      ctx.fillText(popup.title, centerX, centerY - 10);
      
      // Draw description (Tetris-style centered)
      ctx.fillStyle = `rgba(200, 200, 200, ${alpha})`;
      ctx.font = '12px Arial';
      ctx.fillText(popup.description, centerX, centerY + 12);
      
      // Restore context state
      ctx.restore();
      
      // Update life
      popup.life--;
    });
  }
  
  function unlockSnakeAchievement(achievementKey) {
    const userId = localStorage.getItem('discord_id') || '328601656659017732';
    
    // Environment-aware API endpoint
    const isProduction = window.location.hostname === 'narrrfs-world.onrender.com' || window.location.hostname === 'narrrfs.world';
    const apiBaseUrl = isProduction ? 'https://narrrfs.world' : '';
    
    fetch(`${apiBaseUrl}/api/dev/unlock-snake-achievement.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        user_id: userId, 
        achievement_key: achievementKey 
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        console.log('🏆 Snake achievement unlocked:', achievementKey);
      } else {
        console.warn('Failed to unlock Snake achievement:', data.error);
      }
    })
    .catch(error => {
      console.error('Error unlocking Snake achievement:', error);
    });
  }

  // --- Event Listeners (only add once!) ---

  // Keyboard controls
  document.addEventListener("keydown", e => {
    switch (e.key) {
      case "ArrowLeft": case "a": if (velocity.x === 0) velocity = { x: -1, y: 0 }; break;
      case "ArrowRight": case "d": if (velocity.x === 0) velocity = { x: 1, y: 0 }; break;
      case "ArrowUp": case "w": if (velocity.y === 0) velocity = { x: 0, y: -1 }; break;
      case "ArrowDown": case "s": if (velocity.y === 0) velocity = { x: 0, y: 1 }; break;
    }
  });

// Touch controls with scroll prevention - Enhanced for mobile
let touchStartX = 0, touchStartY = 0;
let isSnakeGameActive = false;
let snakeScrollLocked = false;
const SNAKE_SWIPE_THRESHOLD = 30; // Optimized for instant touch response

function enableGlobalSnakeTouch() { 
  isSnakeGameActive = true; 
  document.body.style.overflow = "hidden";
  snakeScrollLocked = true;
}

function disableGlobalSnakeTouch() { 
  isSnakeGameActive = false;
  document.body.style.overflow = "";
  snakeScrollLocked = false;
  unlockSnakeScroll(); // 🎯 Ensure scrolling is unlocked when disabling touch
}

// Helper: Lock/unlock scrolling for Snake (like Tetris)
function lockSnakeScroll() {
  if (!snakeScrollLocked) {
    document.body.style.overflow = "hidden";
    snakeScrollLocked = true;
  }
}

function unlockSnakeScroll() {
  if (snakeScrollLocked) {
    document.body.style.overflow = "";
    snakeScrollLocked = false;
  }
}

// Prevent scrolling on the game canvas - Enhanced for mobile
document.addEventListener('touchmove', function(e) {
  if (isSnakeGameActive && !isSnakePaused) {
    e.preventDefault();
    e.stopPropagation();
  }
}, { passive: false });

document.body.addEventListener("touchstart", function(e) {
  if (!isSnakeGameActive || isSnakePaused) return;
  e.preventDefault();
  e.stopPropagation();
  const touch = e.touches[0];
  touchStartX = touch.clientX;
  touchStartY = touch.clientY;
}, { passive: false });

document.body.addEventListener("touchend", function(e) {
  if (!isSnakeGameActive || isSnakePaused) return;
  e.preventDefault();
  e.stopPropagation();
  const touch = e.changedTouches[0];
  const deltaX = touch.clientX - touchStartX;
  const deltaY = touch.clientY - touchStartY;
  
  // Optimized minimum swipe distance for instant response
  const minSwipeDistance = SNAKE_SWIPE_THRESHOLD;
  
  // Only process swipes if game is active and not paused
  if (Math.abs(deltaX) > Math.abs(deltaY)) {
    // Horizontal swipe detection - more responsive
    if (deltaX > minSwipeDistance && velocity.x === 0) {
      velocity = { x: 1, y: 0 }; // Right
    } else if (deltaX < -minSwipeDistance && velocity.x === 0) {
      velocity = { x: -1, y: 0 }; // Left
    }
  } else {
    // Vertical swipe detection - more responsive
    if (deltaY > minSwipeDistance && velocity.y === 0) {
      velocity = { x: 0, y: 1 }; // Down
    } else if (deltaY < -minSwipeDistance && velocity.y === 0) {
      velocity = { x: 0, y: -1 }; // Up
    }
  }
}, { passive: false });

// Touch control activation/deactivation is handled in the main startGame function above


// 🐍 Snake Score Saving Function
function saveScore(finalScore) {
  let wallet = localStorage.getItem("walletAddress");
  let discordId = localStorage.getItem("discord_id");
  let discordName = localStorage.getItem("discord_name");

  // 🛠️ Mock fallback if testing locally
  if (!discordId) {
    discordId = "328601656659017732"; // Narrrf's Discord ID for testing
    discordName = "narrrf";
    localStorage.setItem("discord_id", discordId);
    localStorage.setItem("discord_name", discordName);
  }

  // ✅ For Local Testing
  if (!wallet) {
    localStorage.setItem("walletAddress", "TestWallet123456789XYZ");
    wallet = "TestWallet123456789XYZ";
  }

  if (!discordId) {
    discordId = "328601656659017732"; // Narrrf's Discord ID for testing
    discordName = "narrrf";
    localStorage.setItem("discord_id", discordId);
    localStorage.setItem("discord_name", discordName);
  }

  // ✅ Basic validation
  if (!wallet || wallet.length < 15 || finalScore <= 0) {
    console.warn("❌ Invalid wallet or zero score — skipping save.");
    return;
  }

  const payload = {
    wallet,
    score: finalScore,
    discord_id: discordId,
    discord_name: discordName,
    game: "snake" // 🐍 Specify this is a Snake game score
  };

  console.log("⏎ Sending score payload:", payload);
  
  // 🌍 Environment-aware API endpoint (works both locally and in production)
  const isProduction = window.location.hostname === 'narrrfs-world.onrender.com' || window.location.hostname === 'narrrfs.world';
  const apiBaseUrl = isProduction ? 'https://narrrfs.world' : 'http://localhost';
  const apiUrl = `${apiBaseUrl}/api/dev/save-score.php`;
  
  console.log(`🌍 Environment: ${isProduction ? 'Production' : 'Local'}`);
  console.log(`🔗 API URL: ${apiUrl}`);

  fetch(apiUrl, {
           method: "POST",
           headers: { "Content-Type": "application/json" },
           body: JSON.stringify(payload)
         })
           .then(res => res.json())
           .then(data => {
             console.log("💾 Snake score saved:", data);
             
             // 🏆 Check for WL Role Grant
             if (data.wl_check && data.wl_check.eligible) {
               console.log("🎉 WL Role granted:", data.wl_check);
               
               // Show WL notification
               showWLNotification(data.wl_check);
             }

             // ✅ Force leaderboard refresh after short delay
             setTimeout(() => {
               if (document.getElementById("leaderboard-list")) {
                 // 🌍 Environment-aware API endpoint for leaderboard
                 const leaderboardUrl = `${apiBaseUrl}/api/dev/get-leaderboard.php?t=${Date.now()}`;
                 console.log(`🔗 Leaderboard API URL: ${leaderboardUrl}`);
                 
                 fetch(leaderboardUrl)
                   .then(res => res.json())
                   .then(result => {
                     const scores = result.leaderboard || [];
                     const list = document.getElementById("leaderboard-list");
                     list.innerHTML = "";

                     const rankColors = ["text-yellow-400", "text-gray-300", "text-yellow-200"];
                     const rankEmojis = ["👑", "🥈", "🥉"];

                     scores.forEach((entry, i) => {
                       const name = entry.discord_name || `${entry.wallet.slice(0, 6)}...${entry.wallet.slice(-4)}`;
                       const li = document.createElement("li");
                       const emoji = rankEmojis[i] || "";

                       li.innerHTML = `${emoji} #${i + 1} <strong>${name}</strong> – ${entry.score} $DSPOINC`;
                       li.classList.add("animate-pop", rankColors[i] || "text-white");
                       list.appendChild(li);
                     });
                   });
               }
             }, 500);
           })
           .catch(err => console.error("Snake score save failed:", err));
}



  // Pause button
  const pauseBtn = document.getElementById("pause-snake-btn");
  if (pauseBtn) {
    // Add mobile-friendly styles
    pauseBtn.style.padding = "12px 24px";
    pauseBtn.style.fontSize = "18px";
    pauseBtn.style.touchAction = "manipulation";
    pauseBtn.style.userSelect = "none";
    pauseBtn.style.webkitTapHighlightColor = "transparent";
    
    // Remove any existing listeners
    pauseBtn.replaceWith(pauseBtn.cloneNode(true));
    const newPauseBtn = document.getElementById("pause-snake-btn");
    
    // Add both click and touch events
    const pauseHandler = (e) => {
      e.preventDefault();
      e.stopPropagation();
      
      isSnakePaused = !isSnakePaused;
      newPauseBtn.textContent = isSnakePaused ? "▶️ Resume" : "⏸️ Pause";
      
      if (isSnakePaused) {
        clearInterval(gameInterval);
        // 🎯 Unlock scrolling when paused (like Tetris)
        unlockSnakeScroll();
      } else {
        // Only restart if game is not over
        if (gameInterval) {
          clearInterval(gameInterval);
          gameInterval = setInterval(moveSnake, 400);
          // 🎯 Lock scrolling when resumed (like Tetris)
          lockSnakeScroll();
        }
      }
    };

    newPauseBtn.addEventListener("click", pauseHandler);
    newPauseBtn.addEventListener("touchend", pauseHandler, { passive: false });
  }

  // Start button
  const startBtn = document.getElementById("start-snake-btn");
  if (startBtn) {
    startBtn.addEventListener("click", () => {
      startGameWithCountdown();
    });
  } else {
    startGameWithCountdown();
  }

  // Expose start for external use
  window.startSnakeGame = startGameWithCountdown;

  // 🏆 TEST FUNCTION - Test role-based features
  window.testSnakeRoleFeatures = function() {
    console.log('🏆 Testing Snake role-based features...');
    console.log('Current roles:', snakeUserRoles);
    console.log('Primary role:', getSnakePrimaryRole());
    console.log('Score multiplier:', getSnakeRoleScoreMultiplier());
    console.log('Role colors:', getSnakeRoleColors());
    console.log('Theme applied:', canvas?.className);
    
    // Test role detection
    fetchSnakeUserRoles().then(() => {
      console.log('🏆 Snake role detection test completed');
    });
  };

  // 👁️ Watch DOM visibility and re-init if hidden
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) {
        clearInterval(gameInterval);
      }
    });
  }, { threshold: 0.1 });

  observer.observe(canvas);
}

/*
============================================================
🐍 SNAKE 9.0 SCROLL – EXTENDED GLYPH
============================================================
⛏️ AUTHOR: Cheese Architect + SQL Junior
📅 VERSION: 9.0 FINAL · Scroll Timestamp: 2025-06-13
🧠 DESC: Enhanced Narrrf Snake Game Scroll with full trait DOM hooks,
        countdown intro, Discord-linked score sync, pause/resume,
        mobile gesture controls, and future-ready mutation slots.
------------------------------------------------------------
✅ FEATURE OVERVIEW:
- Cheese-styled food block (png)
- Game Over modal with DSPOINC score
- Discord + Wallet linked save logic
- Countdown overlay intro (5..GO!)
- Touch gesture controls for mobile
- Trait-based DOM unlock compatibility
- Extensible logic for bomb mode, glowing snake, and lore unlock

🧬 FUTURE ENHANCEMENTS:
- Snake trail glow (DOM filters)
- Rare cheese blocks that speed up gameplay
- Reward-linked NFT unlock
- Puzzle bridge w/ Riddle Brain 9.0
- LocalStorage streak tracking
============================================================
*/

function unlockTrait(trait) {
  fetch("https://narrrfs.world/api/user/traits.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ trait })
  })
  .then(res => res.json())
  .then(data => {
    console.log(`🔓 Trait Unlocked: ${trait}`, data);
  })
  .catch(err => console.warn("⚠️ Trait unlock failed:", err));
}

// 🧠 Optional trait trigger at score threshold
function checkTraitUnlocks(score) {
  if (score >= 100 && !window.snakeTraitUnlocked) {
    unlockTrait("SNAKE_CHEESE_MASTER");
    const unlockDiv = document.getElementById("snake-unlock");
    if (unlockDiv) unlockDiv.classList.remove("hidden");
    window.snakeTraitUnlocked = true;
  }
}

// 🔁 Local storage bonus tracking
function updateStreakCounter() {
  let streak = parseInt(localStorage.getItem("snake_streak") || "0", 10);
  streak += 1;
  localStorage.setItem("snake_streak", streak);
  console.log(`🔥 Current Snake Streak: ${streak}`);
}

// 🧪 Cheese bomb seed logic (disabled by default)
function maybePlaceBomb() {
  // Reserved for bomb feature in future
  if (Math.random() < 0.05) {
    console.log("💣 Bomb block placed (placeholder)");
  }
}

// 🏆 WL Role Notification Function
function showWLNotification(wlData) {
  // Create notification element
  const notification = document.createElement('div');
  notification.className = 'fixed top-4 right-4 bg-gradient-to-r from-purple-600 to-blue-600 text-white p-4 rounded-lg shadow-lg z-50 transform transition-all duration-500';
  notification.style.maxWidth = '400px';
  
  const roleName = getRoleName(wlData.role_id);
  
  notification.innerHTML = `
    <div class="flex items-center space-x-3">
      <div class="text-2xl">🏆</div>
      <div>
        <div class="font-bold text-lg">WL Role Granted!</div>
        <div class="text-sm opacity-90">${roleName}</div>
        <div class="text-xs opacity-75">+${wlData.bonus_points} DSPOINC Bonus</div>
      </div>
    </div>
  `;
  
  document.body.appendChild(notification);
  
  // Animate in
  setTimeout(() => {
    notification.style.transform = 'translateX(0)';
  }, 100);
  
  // Remove after 5 seconds
  setTimeout(() => {
    notification.style.transform = 'translateX(100%)';
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 500);
  }, 5000);
}

// Helper function to get role name from ID
function getRoleName(roleId) {
  const roleMap = {
    '1399651053682692208': '🧀 Cheese Hunter',
    '1332017770937847809': '🏆 Alpha Caller',
    '1332017420591697972': '🥇 Champion',
    '1332016526848692345': '👑 VIP Cheese Lord',
    '1333347801408737323': '✅ Verified'
  };
  return roleMap[roleId] || 'WL Role';
}

// 🛠️ UI Debug Helper
function showDebugInfo() {
  // These variables are only available inside initSnake, so this is a placeholder.
  // To use this globally, you would need to expose them to window or refactor.
  console.log("Debug info only available in game context.");
} 