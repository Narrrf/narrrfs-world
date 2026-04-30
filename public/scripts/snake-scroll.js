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
  // Respect the global Narrrfs sound toggle from cheese-auth-indicator.js.
  // When players turn sound off, Snake must not create Web Audio sounds that can pause music apps.
  if (window.NarrrfsSound && !window.NarrrfsSound.isEnabled()) {
    return;
  }

  if (!this.audioContext) {
    return;
  }

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
  'Season Tester': 'green',
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
  'Season Tester': { snake: '#00FF00', food: '#7FFF00', trail: '#00FF00' },
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
      // 🏠 Local environment detected - using all test roles
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
    canvas.classList.remove('golden', 'silver', 'cheese', 'rainbow', 'green', 'royal', 'blue', 'red');
    // Add new theme class
    if (theme !== 'default') {
      canvas.classList.add(theme);
    }
  }
  
  // 🎨 NEW: Apply theme to controls section
  const controlsSection = document.getElementById('snake-controls-section');
  const controlsTitle = document.getElementById('snake-controls-title');
  if (controlsSection && controlsTitle) {
    // Remove existing theme classes
    controlsSection.classList.remove('golden', 'silver', 'cheese', 'rainbow', 'green', 'blue', 'red');
    controlsTitle.classList.remove('golden', 'silver', 'cheese', 'rainbow', 'green', 'blue', 'red');
    // Add new theme class
    if (theme !== 'default') {
      controlsSection.classList.add(theme);
      controlsTitle.classList.add(theme);
    }
    console.log(`🎨 Snake controls section theme applied: ${theme}`);
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
  
  // 🐛 BUG #312 FIX: Invalid turn glow effect
  let invalidTurnGlow = 0; // Glow intensity (0-1)
  let invalidTurnGlowDirection = null; // Direction that was attempted
  
  // 🐍🧀 SEASON 5: GIANT CHEESE SNAKE BOSS SYSTEM
  let giantSnakeBossActive = false;
  let giantSnakeBoss = null;
  let goldenApples = [];
  let goldenApplesCollected = 0;
  let bossTimer = 0;
  let bossBattleActive = false;
  let bossCollisionGraceUntil = 0;
  const BOSS_COLLISION_GRACE_MS = 1000;
  let totalBossesDefeated = 0; // 🧪 Track total bosses defeated for testing
  
  // 🧀 GIANT CHEESE SNAKE BOSS CONFIGURATION - PRODUCTION MODE
  const giantSnakeBossConfig = {
    // 🎯 PRODUCTION MODE: Cheese-based progressive spawning (9 bosses total!)
    spawnMode: 'cheese_progressive',  // Production: cheese-based, Test: every 3 cheeses
    
    // 🐍 Boss spawn points (cheese counts) - Baby, 10, 30, 50, 80, 120, 170, 230, 300 (equivalent to full canvas)
    spawnPoints: [3, 10, 30, 50, 80, 120, 170, 230, 300],  // 9 bosses! Baby boss at 3!
    
    // 🧠 Progressive intelligence (0-100%, higher = smarter)
    intelligence: [15, 20, 30, 45, 60, 75, 85, 90, 95],  // Baby: 15%, Boss 9: 95%
    
    // ⚡ Speed progression (always slower than 400ms player!)
    speeds: [650, 600, 580, 560, 540, 520, 500, 480, 460],  // Baby: 650ms (very slow!)
    
    // 📏 Length progression
    lengths: [6, 10, 12, 14, 16, 18, 20, 22, 24],  // Baby: 6 segments (tiny!)
    
    // 🍎 Golden apples required per boss
    applesRequired: [5, 10, 10, 10, 10, 10, 10, 10, 10],  // Baby: 5 apples (easy!), others: 10
    goldenApplesRequired: 10,
    
    // 💰 DSPOINC rewards
    rewards: [30, 50, 80, 120, 170, 230, 300, 400, 550],  // Baby: 30, Boss 9: 550
    
    // 🎨 Boss colors (progressive danger)
    colors: [
      '#E6B3FF',  // Baby: Light Purple (cute!)
      '#9400D3',  // Boss 2: Dark Violet
      '#BA55D3',  // Boss 3: Medium Orchid
      '#FFD700',  // Boss 4: Gold
      '#FF8C00',  // Boss 5: Dark Orange
      '#FF6347',  // Boss 6: Tomato Red
      '#FF4500',  // Boss 7: Orange Red
      '#DC143C',  // Boss 8: Crimson
      '#8B0000'   // Boss 9: Dark Red (ultimate!)
    ],
    
    // ⏰ Time limits (60s = 150 frames at 400ms)
    timeLimit: 150,
    
    // 🧪 TEST MODE OVERRIDE (localhost only)
    testMode: {
      enabled: true,  // Auto-detect localhost
      spawnInterval: 3,  // Every 3 cheeses
      maxBosses: 5  // Test first 5 bosses only
    }
  };
  
  // 🐍🧀 GIANT CHEESE SNAKE BOSS CLASS
  class GiantCheeseSnakeBoss {
    constructor(cheeseCount, bossNumber) {
      // 🎯 PRODUCTION MODE: Use cheese count and boss number for stats
      this.cheeseCount = cheeseCount;
      this.bossNumber = bossNumber; // 1-9 (includes Baby Boss!)
      
      // Get stats from config arrays (bossNumber - 1 for 0-based indexing)
      const index = bossNumber - 1;
      this.length = giantSnakeBossConfig.lengths[index] || 6;
      this.speed = giantSnakeBossConfig.speeds[index] || 650;
      this.intelligence = giantSnakeBossConfig.intelligence[index] || 15;
      this.color = giantSnakeBossConfig.colors[index] || '#E6B3FF';
      const baseApplesRequired = giantSnakeBossConfig.applesRequired[index] || 5;
      const effectiveApples = Math.max(1, baseApplesRequired - snakeAppleBonus);
      if (effectiveApples !== baseApplesRequired) {
        console.log(`🍏 Golden Apple Booster active - boss apples reduced from ${baseApplesRequired} to ${effectiveApples}`);
      }
      this.health = effectiveApples;
      this.maxHealth = this.health;
      giantSnakeBossConfig.goldenApplesRequired = this.maxHealth;
      this.aiMode = 'hunt';
      this.lastMoveTime = Date.now();
      
      // 🎯 Initialize boss BELOW UI panels AND away from player (BUG FIX: prevent instant collision)
      this.segments = [];
      
      // Build quick lookup tables for player body and a 1-tile safety buffer
      const clampToBoard = (x, y) =>
        x >= 0 && x < tileCountX && y >= 0 && y < tileCountY;
      const occupiedCells = new Set();
      const bufferCells = new Set();
      snake.forEach(seg => {
        if (clampToBoard(seg.x, seg.y)) {
          occupiedCells.add(`${seg.x},${seg.y}`);
        }
        for (let dx = -1; dx <= 1; dx++) {
          for (let dy = -1; dy <= 1; dy++) {
            const bx = seg.x + dx;
            const by = seg.y + dy;
            if (clampToBoard(bx, by)) {
              bufferCells.add(`${bx},${by}`);
            }
          }
        }
      });
      
      const orientations = [
        { name: 'horizontal', dx: 1, dy: 0, maxLength: tileCountX },
        { name: 'vertical', dx: 0, dy: 1, maxLength: tileCountY }
      ];
      
      const uiSafeStartY = 5; // Keep boss below UI overlay (rows 0-4)
      let chosenPlacement = null;
      
      orientations.forEach(orientation => {
        if (chosenPlacement) return;
        
        const maxSegments = orientation.maxLength;
        if (maxSegments <= 0) return;
        
        const spawnLength = Math.min(this.length, maxSegments);
        if (spawnLength <= 0) return;
        
        let startYMax = orientation.dy === 0
          ? tileCountY - 1
          : tileCountY - spawnLength;
        let startYMin = orientation.dy === 0 ? uiSafeStartY : uiSafeStartY;
        if (startYMax < startYMin) {
          startYMin = Math.max(0, startYMax);
        }
        startYMin = Math.max(0, startYMin);
        startYMax = Math.max(startYMax, startYMin);
        
        for (let tryY = startYMin; tryY <= startYMax && !chosenPlacement; tryY++) {
          for (let tryX = 0; tryX < tileCountX && !chosenPlacement; tryX++) {
            const segments = [];
            let collision = false;
            let minDistanceToPlayer = Infinity;
            
            for (let i = 0; i < spawnLength; i++) {
              const x = tryX + orientation.dx * i;
              const y = tryY + orientation.dy * i;
              
              if (!clampToBoard(x, y)) {
                collision = true;
                break;
              }
              
              const key = `${x},${y}`;
              if (occupiedCells.has(key) || bufferCells.has(key)) {
                collision = true;
                break;
              }
              
              const distance = snake.reduce((min, seg) => {
                const dist = Math.abs(seg.x - x) + Math.abs(seg.y - y);
                return Math.min(min, dist);
              }, Infinity);
              minDistanceToPlayer = Math.min(minDistanceToPlayer, distance);
              
              segments.push({ x, y });
            }
            
            if (!collision && segments.length === spawnLength) {
              chosenPlacement = {
                segments,
                orientation: orientation.name,
                direction: orientation.name === 'vertical' ? { x: 0, y: 1 } : { x: 1, y: 0 },
                spawnLength,
                minDistanceToPlayer
              };
            }
          }
        }
      });
      
      if (chosenPlacement) {
        if (chosenPlacement.spawnLength !== this.length) {
          console.warn(`⚠️ Adjusted boss length from ${this.length} to ${chosenPlacement.spawnLength} to fit board safely.`);
          this.length = chosenPlacement.spawnLength;
        }
        this.segments = chosenPlacement.segments;
        this.direction = chosenPlacement.direction;
        console.log(`🎯 Safe boss spawn found (${chosenPlacement.orientation}) with minimum player distance ${chosenPlacement.minDistanceToPlayer}`);
      } else {
        // Fallback to legacy spawn (kept for absolute safety) - ensure coordinates stay on board
        console.warn('⚠️ Fallback boss spawn used. Unable to find fully safe placement.');
        const fallbackY = 6;
        for (let i = 0; i < Math.min(this.length, tileCountX); i++) {
          this.segments.push({ x: i, y: fallbackY });
        }
        this.length = this.segments.length;
        this.direction = { x: 1, y: 0 };
      }
      
      // 🍼 Baby Boss indicator
      const isBabyBoss = bossNumber === 1;
      const bossName = isBabyBoss ? '🍼 BABY BOSS' : `Boss ${bossNumber}`;
      
      console.log(`🐍 ${bossName} spawned! Cheese: ${cheeseCount}, Length: ${this.length}, Speed: ${this.speed}ms (Player: 400ms), Intelligence: ${this.intelligence}%, Apples: ${this.health}`);
    }
    
    getColorByBossNumber() {
      // Helper method for color (already set in constructor)
      return this.color;
    }
    
    get head() {
      return this.segments[this.segments.length - 1];
    }
    
    get tail() {
      return this.segments[0];
    }
    
    update() {
      // Time-based movement (boss moves at own speed)
      const now = Date.now();
      if (now - this.lastMoveTime < this.speed) {
        return; // Not time to move yet
      }
      this.lastMoveTime = now;
      
      // 🧪 DEBUG: Log boss update
      console.log(`🐍 Boss updating... Head: (${this.head.x}, ${this.head.y}), Segments: ${this.segments.length}, Mode: ${this.aiMode}`);
      
      // AI decision making
      if (this.aiMode === 'hunt') {
        this.huntPlayer();
      } else if (this.aiMode === 'patrol') {
        this.patrolArena();
      }
      
      // Move boss snake
      this.move();
      
      // 🧪 DEBUG: Log after movement
      console.log(`🐍 Boss moved! New head: (${this.head.x}, ${this.head.y}), Direction: (${this.direction.x}, ${this.direction.y})`);
    }
    
    huntPlayer() {
      // 🧠 INTELLIGENCE-BASED AI: Boss makes dumb/random moves based on intelligence level
      const playerHead = snake[0]; // 🔧 FIX: Snake uses unshift(), so head is at index 0
      const dx = playerHead.x - this.head.x;
      const dy = playerHead.y - this.head.y;
      
      // Roll intelligence dice: 0-100
      const intelligenceRoll = Math.random() * 100;
      const shouldHunt = intelligenceRoll < this.intelligence;
      
      // 🧪 DEBUG: Log AI decision
      console.log(`🧠 Boss ${this.bossNumber} intelligence check: ${intelligenceRoll.toFixed(1)}/${this.intelligence}% - ${shouldHunt ? 'HUNTING' : 'DUMB MOVE'}`);
      
      let newDirection = { ...this.direction };
      
      if (shouldHunt) {
        // 🎯 SMART MODE: Hunt player (percentage based on intelligence)
        console.log(`🎯 Boss hunting player... Player: (${playerHead.x}, ${playerHead.y}), Boss: (${this.head.x}, ${this.head.y}), Distance: (${dx}, ${dy})`);
        
        // Choose direction based on which axis is farther
        if (Math.abs(dx) > Math.abs(dy)) {
          // Horizontal priority
          newDirection = { x: dx > 0 ? 1 : -1, y: 0 };
        } else if (Math.abs(dy) > 0) {
          // Vertical priority
          newDirection = { x: 0, y: dy > 0 ? 1 : -1 };
        }
      } else {
        // 🐌 DUMB MODE: Random/dumb movement (not hunting player directly)
        const dumbChoices = [
          { x: 1, y: 0, name: 'Right' },   // Move right
          { x: -1, y: 0, name: 'Left' },  // Move left
          { x: 0, y: 1, name: 'Down' },   // Move down
          { x: 0, y: -1, name: 'Up' },    // Move up
          { x: dx > 0 ? -1 : 1, y: 0, name: 'Away-X' }, // Move away from player X
          { x: 0, y: dy > 0 ? -1 : 1, name: 'Away-Y' }  // Move away from player Y
        ];
        
        // Sometimes move away from player (gives player space!)
        const awayChance = 30; // 30% chance to move away
        if (Math.random() * 100 < awayChance) {
          // Move away from player (player-friendly!)
          if (Math.abs(dx) > Math.abs(dy)) {
            newDirection = { x: dx > 0 ? -1 : 1, y: 0 }; // Move opposite X
          } else {
            newDirection = { x: 0, y: dy > 0 ? -1 : 1 }; // Move opposite Y
          }
          console.log(`🐌 Boss making DUMB MOVE: Moving AWAY from player (gives space!)`);
        } else {
          // Random direction (completely dumb)
          const randomDir = dumbChoices[Math.floor(Math.random() * 4)]; // Only cardinal directions
          newDirection = { x: randomDir.x, y: randomDir.y };
          console.log(`🐌 Boss making DUMB MOVE: Random direction ${randomDir.name}`);
        }
      }
      
      // Check if new direction is safe (won't hit wall or self)
      const nextPos = {
        x: this.head.x + newDirection.x,
        y: this.head.y + newDirection.y
      };
      
      if (this.isPositionSafe(nextPos)) {
        this.direction = newDirection;
        console.log(`✅ Boss chose direction: (${newDirection.x}, ${newDirection.y})`);
      } else {
        // Try alternative directions
        console.log(`⚠️ Primary direction blocked! Finding alternative...`);
        this.chooseSafeDirection();
      }
    }
    
    patrolArena() {
      // Simple patrol: follow the walls
      // Implementation for higher levels
      this.chooseSafeDirection();
    }
    
    isPositionSafe(pos) {
      // Check walls
      if (pos.x < 0 || pos.x >= tileCountX || pos.y < 0 || pos.y >= tileCountY) {
        return false;
      }
      
      // Check self-collision
      for (let segment of this.segments) {
        if (segment.x === pos.x && segment.y === pos.y) {
          return false;
        }
      }
      
      return true;
    }
    
    chooseSafeDirection() {
      // Try all 4 directions and pick first safe one
      const directions = [
        { x: 1, y: 0, name: 'Right' },
        { x: -1, y: 0, name: 'Left' },
        { x: 0, y: 1, name: 'Down' },
        { x: 0, y: -1, name: 'Up' }
      ];
      
      console.log(`🔍 Boss choosing safe direction from (${this.head.x}, ${this.head.y})...`);
      
      for (let dir of directions) {
        const nextPos = {
          x: this.head.x + dir.x,
          y: this.head.y + dir.y
        };
        
        if (this.isPositionSafe(nextPos)) {
          this.direction = { x: dir.x, y: dir.y };
          console.log(`✅ Boss found safe direction: ${dir.name} (${dir.x}, ${dir.y})`);
          return;
        }
      }
      
      // If no safe direction, keep current direction (will hit wall and die)
      console.warn(`⚠️ Boss has NO safe direction! Stuck at (${this.head.x}, ${this.head.y})`);
    }
    
    move() {
      // Calculate new head position
      const newHead = {
        x: this.head.x + this.direction.x,
        y: this.head.y + this.direction.y
      };
      
      // 🔧 CRITICAL FIX: Clamp position to stay within bounds!
      newHead.x = Math.max(0, Math.min(tileCountX - 1, newHead.x));
      newHead.y = Math.max(0, Math.min(tileCountY - 1, newHead.y));
      
      // 🧪 DEBUG: Log if boss hit boundary
      if (newHead.x === 0 || newHead.x === tileCountX - 1 || newHead.y === 0 || newHead.y === tileCountY - 1) {
        console.log(`🚧 Boss hit boundary! Clamped to (${newHead.x}, ${newHead.y})`);
      }
      
      // Add new head
      this.segments.push(newHead);
      
      // Remove tail (maintains length)
      this.segments.shift();
    }
    
    takeDamage(amount) {
      this.health -= amount;
      console.log(`🐍 Boss took ${amount} damage! Health: ${this.health}/${this.maxHealth}`);
      
      if (this.health <= 0) {
        this.die();
      }
    }
    
    die() {
      const isBabyBoss = this.bossNumber === 1;
      const bossName = isBabyBoss ? '🍼 Baby Boss' : `Boss ${this.bossNumber}`;
      console.log(`🧀 ${bossName} defeated at ${this.cheeseCount} cheeses!`);
      
      // Get rewards from config (bossNumber - 1 for 0-based indexing)
      const index = this.bossNumber - 1;
      const bonusPoints = giantSnakeBossConfig.rewards[index] || 30;
      
      // Award bonus points
      score += bonusPoints;
      totalBossesDefeated++; // Increment boss counter after using bossNumber
      console.log(`🏆 ${bossName} defeated! Bonus: ${bonusPoints} DSPOINC`);
      
      // 🚨 PAUSE GAME for victory countdown
      isSnakePaused = true;
      
      // Show victory notification (no lives - Snake has no lives system!)
      showBossVictoryNotification(bonusPoints, this.bossNumber);
      
      // Boss battle complete
      giantSnakeBossActive = false;
      bossBattleActive = false;
      giantSnakeBoss = null;
      goldenApples = [];
      goldenApplesCollected = 0;
      
      // Return to normal game after countdown completes (~4.9s)
      setTimeout(() => {
        isSnakePaused = false; // 🔧 UNPAUSE after countdown
        console.log('🐍 Boss defeated! Returning to normal gameplay...');
        placeFood();
      }, 4900); // Match countdown timing
    }
    
    draw(ctx) {
      // 🧪 DEBUG: Log draw call
      console.log(`🎨 Drawing boss with ${this.segments.length} segments, Color: ${this.color}`);
      
      // Draw boss segments (1.5x size for better balance)
      this.segments.forEach((segment, index) => {
        const isHead = index === this.segments.length - 1;
        const size = gridSize * 1.5; // 🔧 BALANCED: 1.5x size (was 2x, now smaller and themed!)
        const x = segment.x * gridSize - gridSize / 4; // 🔧 CENTERED: Offset for 1.5x block
        const y = segment.y * gridSize - gridSize / 4;
        
        // 🧪 DEBUG: Log each segment position
        if (index % 5 === 0) { // Log every 5th segment to reduce spam
          console.log(`🎨 Drawing segment ${index} at (${x}, ${y}), Grid: (${segment.x}, ${segment.y})`);
        }
        
        // 🧀 CHEESE-THEMED BOSS GLOW
        ctx.shadowBlur = 15;
        ctx.shadowColor = this.color; // Boss color glow
        
        // Progressive brightness (head brightest)
        const brightness = 1 - (index / this.segments.length) * 0.3;
        const rgb = this.hexToRgb(this.color);
        ctx.fillStyle = `rgba(${rgb.r * brightness}, ${rgb.g * brightness}, ${rgb.b * brightness}, 0.95)`;
        
        // Draw segment with rounded corners (cheese style)
        const borderRadius = size * 0.2;
        ctx.beginPath();
        ctx.moveTo(x + borderRadius, y);
        ctx.arcTo(x + size, y, x + size, y + size, borderRadius);
        ctx.arcTo(x + size, y + size, x, y + size, borderRadius);
        ctx.arcTo(x, y + size, x, y, borderRadius);
        ctx.arcTo(x, y, x + size, y, borderRadius);
        ctx.closePath();
        ctx.fill();
        
        // 🧀 Cheese holes pattern (Swiss cheese style)
        if (!isHead && index % 2 === 0) {
          ctx.shadowBlur = 0;
          ctx.fillStyle = 'rgba(0, 0, 0, 0.3)';
          const holeSize = size * 0.2;
          ctx.beginPath();
          ctx.arc(x + size * 0.3, y + size * 0.3, holeSize, 0, Math.PI * 2);
          ctx.fill();
          ctx.beginPath();
          ctx.arc(x + size * 0.7, y + size * 0.7, holeSize, 0, Math.PI * 2);
          ctx.fill();
        }
        
        // Golden border for extra cheese effect
        ctx.shadowBlur = 0;
        ctx.strokeStyle = '#FFD700';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(x + borderRadius, y);
        ctx.arcTo(x + size, y, x + size, y + size, borderRadius);
        ctx.arcTo(x + size, y + size, x, y + size, borderRadius);
        ctx.arcTo(x, y + size, x, y, borderRadius);
        ctx.arcTo(x, y, x + size, y, borderRadius);
        ctx.closePath();
        ctx.stroke();
        
        // 🧀 Draw cheese-themed eyes on head
        if (isHead) {
          ctx.shadowBlur = 5;
          ctx.shadowColor = '#FFD700';
          
          // Left eye (golden cheese style)
          ctx.fillStyle = '#FFD700';
          ctx.beginPath();
          ctx.arc(x + size * 0.3, y + size * 0.4, 6, 0, Math.PI * 2);
          ctx.fill();
          
          // Left pupil
          ctx.fillStyle = '#000000';
          ctx.beginPath();
          ctx.arc(x + size * 0.3, y + size * 0.4, 3, 0, Math.PI * 2);
          ctx.fill();
          
          // Right eye (golden cheese style)
          ctx.fillStyle = '#FFD700';
          ctx.beginPath();
          ctx.arc(x + size * 0.7, y + size * 0.4, 6, 0, Math.PI * 2);
          ctx.fill();
          
          // Right pupil
          ctx.fillStyle = '#000000';
          ctx.beginPath();
          ctx.arc(x + size * 0.7, y + size * 0.4, 3, 0, Math.PI * 2);
          ctx.fill();
          
          // Eye shine (white dots for sparkle)
          ctx.shadowBlur = 0;
          ctx.fillStyle = '#FFFFFF';
          ctx.beginPath();
          ctx.arc(x + size * 0.3 + 1, y + size * 0.4 - 1, 1.5, 0, Math.PI * 2);
          ctx.fill();
          ctx.beginPath();
          ctx.arc(x + size * 0.7 + 1, y + size * 0.4 - 1, 1.5, 0, Math.PI * 2);
          ctx.fill();
        }
      });
      
      ctx.shadowBlur = 0; // Reset shadow
    }
    
    hexToRgb(hex) {
      const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
      return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
      } : { r: 255, g: 165, b: 0 }; // Default orange
    }
  }
  
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

  const snakeBaseSpeed = 400;
  let snakeActiveSpeed = snakeBaseSpeed;
  let madModeSpeed = Math.floor(snakeActiveSpeed / 2);
  let snakeAppleBonus = 0;

  window.snakeStoreState = window.snakeStoreState || {
    speedSurgeOwned: false,
    appleBoosterOwned: false
  };

  function updateSnakeSpeedFromPerks() {
    snakeActiveSpeed = snakeBaseSpeed;
    if (window.snakeStoreState.speedSurgeOwned) {
      snakeActiveSpeed = Math.max(220, Math.floor(snakeBaseSpeed * 0.8));
    }
    madModeSpeed = Math.max(120, Math.floor(snakeActiveSpeed / 2));
  }

  updateSnakeSpeedFromPerks();

  window.applySnakeStorePerks = function(storeStateOverride) {
    const override = storeStateOverride || {};
    window.snakeStoreState = {
      speedSurgeOwned: Boolean(override.speedSurgeOwned),
      appleBoosterOwned: Boolean(override.appleBoosterOwned)
    };
    snakeAppleBonus = window.snakeStoreState.appleBoosterOwned ? 2 : 0;
    updateSnakeSpeedFromPerks();

    if (gameInterval) {
      clearInterval(gameInterval);
      const intervalSpeed = madModeActive ? madModeSpeed : snakeActiveSpeed;
      gameInterval = setInterval(moveSnake, intervalSpeed);
    }

    console.log('🐍 Store perks applied:', window.snakeStoreState, 'speed:', snakeActiveSpeed, 'apple bonus:', snakeAppleBonus);
  };

  window.applySnakeStorePerks(window.snakeStoreState);

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
    
    updateSnakeSpeedFromPerks();
    console.log(`🎮 Setting up gameInterval - moveSnake will be called every ${snakeActiveSpeed}ms`);
    gameInterval = setInterval(moveSnake, snakeActiveSpeed);
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
    
    // 🐛 BUG #312 FIX: Reset invalid turn glow on game reset
    invalidTurnGlow = 0;
    invalidTurnGlowDirection = null;
    
    // 🧀 Reset cheese teleportation system
    cheeseTeleportTimer = 0;
    cheeseTeleportChance = 0.001; // Reset to base chance
    lastCheesePosition = null;
    teleportCooldown = 0;
    // 🚨 DON'T reset firstTeleportDone here - only reset when actually starting new game
    
    // 🐍 Reset boss battle state
    giantSnakeBossActive = false;
    bossBattleActive = false;
    giantSnakeBoss = null;
    goldenApples = [];
    goldenApplesCollected = 0;
    bossTimer = 0;
    
    // 🐛 BUG #322 FIX: Hide boss HUD when game resets
    updateBossHUD();
    
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
    
    // 🧪 VISUAL FEEDBACK: Yellow flash removed (user reported as bug - too distracting)
    
    console.log(`${mode} Cheese teleported from (${oldPosition.x}, ${oldPosition.y}) to (${food.x}, ${food.y}) at ${gameTime}s`);
  }
  
  // 🐍🧀 SEASON 5: GIANT CHEESE SNAKE BOSS HELPER FUNCTIONS
  
  function spawnGiantCheeseBoss(cheeseCount, bossNumber) {
    const isBabyBoss = bossNumber === 1;
    const bossName = isBabyBoss ? '🍼 Baby Boss' : `Boss ${bossNumber}`;
    console.log(`🐍 Spawning ${bossName} after ${cheeseCount} cheeses!`);
    
    // Set boss battle flags
    giantSnakeBossActive = true;
    bossBattleActive = true;
    bossCollisionGraceUntil = 0;
    
    // Create boss instance with cheese count and boss number
    giantSnakeBoss = new GiantCheeseSnakeBoss(cheeseCount, bossNumber);
    
    // Spawn golden apples (5 for Baby Boss, 10 for others)
    spawnGoldenApples();
    
    // Reset timer
    bossTimer = 0;
    goldenApplesCollected = 0;
    
    // Show boss notification
    showBossSpawnNotification(cheeseCount, bossNumber);
    
    // Pause normal game
    isSnakePaused = true;
    
    // After countdown completes (1.5s info + 3.4s countdown = ~4.9s), unpause for boss battle
    setTimeout(() => {
      isSnakePaused = false;
      bossCollisionGraceUntil = Date.now() + BOSS_COLLISION_GRACE_MS;
      console.log(`🛡️ Boss collision grace active for ${BOSS_COLLISION_GRACE_MS}ms`);
      console.log('🐍 Boss battle started! Collect all golden apples!');
    }, 4900); // Extended to match countdown timing (3, 2, 1, GO!)
  }
  
  function spawnGoldenApples() {
    goldenApples = [];
    
    // 🎯 UI SAFE ZONE: Don't spawn apples in top 4 rows (y = 0-3) where UI panels are!
    const uiSafeZoneRows = 4; // Top 4 rows reserved for UI panels
    const minY = uiSafeZoneRows; // Start spawning from row 4 onwards
    
    // Get apple count from boss (Baby Boss = 5, others = 10)
    const appleCount = giantSnakeBoss ? giantSnakeBoss.maxHealth : 10;
    
    for (let i = 0; i < appleCount; i++) {
      let apple = {
        x: Math.floor(Math.random() * tileCountX),
        y: minY + Math.floor(Math.random() * (tileCountY - minY)) // 🔧 Spawn below UI zone!
      };
      
      // Ensure apple doesn't spawn on snake, boss, or other apples
      while (
        snake.some(seg => seg.x === apple.x && seg.y === apple.y) ||
        (giantSnakeBoss && giantSnakeBoss.segments.some(seg => seg.x === apple.x && seg.y === apple.y)) ||
        goldenApples.some(a => a.x === apple.x && a.y === apple.y) ||
        apple.y < minY // 🔧 CRITICAL: Never spawn in UI zone!
      ) {
        apple = {
          x: Math.floor(Math.random() * tileCountX),
          y: minY + Math.floor(Math.random() * (tileCountY - minY)) // 🔧 Always below UI!
        };
      }
      
      goldenApples.push(apple);
    }
    
    console.log(`🍎 Spawned ${goldenApples.length} golden apples (UI-safe zone: y >= ${minY})!`);
  }
  
  function checkBossCollision() {
    if (!giantSnakeBoss || !bossBattleActive) return;

    // 🛡️ Fairness buffer: prevent instant-death feel right after boss battle starts
    if (Date.now() < bossCollisionGraceUntil) {
      return;
    }
    
    const playerHead = snake[0]; // 🔧 FIX: Snake uses unshift(), so head is at index 0
    
    // Check collision with boss body
    for (let segment of giantSnakeBoss.segments) {
      // Boss segments occupy 1.5x grid cells (smaller hitbox for better gameplay)
      const bossOccupiesCell = (x, y) => {
        return (
          x === segment.x && y === segment.y // 🔧 SIMPLER: Direct hit only (1x1 hitbox, boss is 1.5x visual)
        );
      };
      
      if (bossOccupiesCell(playerHead.x, playerHead.y)) {
        console.log('💀 Player collided with Giant Cheese Snake Boss!');
        onGameOver(); // 🔧 FIX: Snake uses onGameOver() not gameOver()
        return;
      }
    }
  }
  
  function checkGoldenAppleCollection() {
    if (!bossBattleActive || goldenApples.length === 0) return;
    
    const playerHead = snake[0]; // 🔧 FIX: Snake uses unshift(), so head is at index 0
    
    for (let i = goldenApples.length - 1; i >= 0; i--) {
      const apple = goldenApples[i];
      
      if (playerHead.x === apple.x && playerHead.y === apple.y) {
        // Collected golden apple!
        goldenApples.splice(i, 1);
        goldenApplesCollected++;
        
        // Boss takes damage
        if (giantSnakeBoss) {
          giantSnakeBoss.takeDamage(1);
        }
        
        // Small score bonus
        score += 5;
        
        // Play sound
        if (typeof snakeSounds !== 'undefined' && snakeSounds.playSound) {
          snakeSounds.playSound('eatCheese');
        }
        
        console.log(`🍎 Golden apple collected! ${goldenApplesCollected}/${giantSnakeBossConfig.goldenApplesRequired}`);
        
        // 🐛 BUG #322 FIX: Update HUD immediately when apple is collected
        updateBossHUD();
        
        // Check if all apples collected (boss defeated)
        if (goldenApplesCollected >= giantSnakeBossConfig.goldenApplesRequired && giantSnakeBoss) {
          giantSnakeBoss.die();
        }
      }
    }
  }
  
  function updateBossTimer() {
    if (!bossBattleActive) return;
    
    bossTimer++;
    
    // Check time limit
    if (bossTimer >= giantSnakeBossConfig.bossTimeLimit) {
      console.log('⏰ Boss battle time limit reached! Game Over!');
      onGameOver(); // 🔧 FIX: Snake uses onGameOver() not gameOver()
    }
  }
  
  function showBossSpawnNotification(cheeseCount, bossNumber) {
    // 🐛 BUG #350 FIX: Save scroll position before showing notification to prevent frame shift
    const savedScrollX = window.scrollX || window.pageXOffset || 0;
    const savedScrollY = window.scrollY || window.pageYOffset || 0;
    
    const notification = document.createElement('div');
    notification.id = 'boss-spawn-notification';
    
    const isBabyBoss = bossNumber === 1;
    const spawnTitle = isBabyBoss ? '🍼 BABY BOSS - TINY CHEESE SNAKE! 🧀' : `🐍 BOSS ${bossNumber} - GIANT CHEESE SNAKE! 🧀`;
    const baseApples = giantSnakeBossConfig.applesRequired[bossNumber - 1] || 5;
    const applesNeeded = Math.max(1, baseApples - snakeAppleBonus);
    const subtitle = isBabyBoss ? `Your first boss battle!` : `After ${cheeseCount} cheeses!`;
    
    notification.innerHTML = `
      <div id="boss-spawn-content" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                  background: linear-gradient(45deg, rgba(148, 0, 211, 0.4), rgba(255, 215, 0, 0.4), rgba(148, 0, 211, 0.4)); 
                  color: white; padding: 20px 30px; border-radius: 20px; font-weight: bold; 
                  z-index: 10000; text-align: center; box-shadow: 0 0 50px rgba(255, 215, 0, 0.4);
                  animation: bossSpawnPulse 0.5s ease-in-out infinite alternate;
                  border: 4px solid rgba(255, 215, 0, 0.6);
                  backdrop-filter: blur(5px);
                  transition: opacity 0.3s ease-in-out;
                  opacity: 0;
                  max-width: 90vw;
                  width: 400px;
                  pointer-events: auto;">
        <div style="font-size: clamp(20px, 5vw, 32px);">${spawnTitle}</div>
        <div style="font-size: clamp(14px, 3.5vw, 18px); margin-top: 8px;">${subtitle}</div>
        <div style="font-size: clamp(12px, 3vw, 14px); color: #FFD700; margin-top: 8px;">Collect ${applesNeeded} Golden Apples!</div>
        <div style="font-size: clamp(11px, 2.5vw, 12px); color: #FFA500; margin-top: 5px;">Time Limit: 60 seconds</div>
      </div>
      <style>
        @keyframes bossSpawnPulse {
          0% { transform: translate(-50%, -50%) scale(1); }
          100% { transform: translate(-50%, -50%) scale(1.1); }
        }
        @keyframes countdownPulse {
          0%, 100% { transform: translate(-50%, -50%) scale(1); }
          50% { transform: translate(-50%, -50%) scale(1.3); }
        }
      </style>
    `;
    
    // 🐛 BUG #350 FIX: Set notification container to not affect layout (position fixed, no dimensions)
    notification.style.position = 'fixed';
    notification.style.top = '0';
    notification.style.left = '0';
    notification.style.width = '0';
    notification.style.height = '0';
    notification.style.overflow = 'visible';
    notification.style.pointerEvents = 'none';
    notification.style.zIndex = '10000';
    
    document.body.appendChild(notification);
    
    // 🐛 BUG #350 FIX: Restore scroll position immediately after DOM update to prevent frame shift
    requestAnimationFrame(() => {
      window.scrollTo(savedScrollX, savedScrollY);
    });
    
    // Fade in
    setTimeout(() => {
      const div = notification.querySelector('#boss-spawn-content');
      if (div) div.style.opacity = '1';
    }, 10);
    
    // Show countdown: 3, 2, 1, GO!
    const countdownDiv = notification.querySelector('#boss-spawn-content');
    let countdown = 3;
    
    // Update to countdown after 1.5 seconds (boss info shown first)
    setTimeout(() => {
      if (!countdownDiv || !countdownDiv.parentNode) return;
      
      const countdownInterval = setInterval(() => {
        if (!countdownDiv || !countdownDiv.parentNode) {
          clearInterval(countdownInterval);
          return;
        }
        
        if (countdown > 0) {
          countdownDiv.innerHTML = `
            <div style="font-size: clamp(48px, 12vw, 72px); font-weight: bold; text-shadow: 0 0 20px rgba(255, 255, 255, 1);
                        animation: countdownPulse 0.5s ease-in-out;
                        color: #FFD700;">
              ${countdown}
            </div>
          `;
          countdownDiv.style.animation = 'countdownPulse 0.5s ease-in-out';
          countdown--;
        } else {
          countdownDiv.innerHTML = `
            <div style="font-size: clamp(40px, 10vw, 64px); font-weight: bold; text-shadow: 0 0 30px rgba(16, 185, 129, 1);
                        animation: countdownPulse 0.3s ease-in-out;
                        color: #10b981;">
              GO!
            </div>
          `;
          countdownDiv.style.animation = 'countdownPulse 0.3s ease-in-out';
          clearInterval(countdownInterval);
          
          // Remove after "GO!" shown
          setTimeout(() => {
            if (countdownDiv) countdownDiv.style.opacity = '0';
            setTimeout(() => {
              if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
                // 🐛 BUG #350 FIX: Restore scroll position after notification removed to prevent frame shift
                requestAnimationFrame(() => {
                  window.scrollTo(savedScrollX, savedScrollY);
                });
              }
            }, 300);
          }, 500);
        }
      }, 800); // Countdown updates every 800ms
    }, 1500); // Start countdown after 1.5 seconds
  }
  
  function showBossVictoryNotification(bonus, bossNumber) {
    // 🐛 BUG #350 FIX: Save scroll position before showing notification to prevent frame shift
    const savedScrollX = window.scrollX || window.pageXOffset || 0;
    const savedScrollY = window.scrollY || window.pageYOffset || 0;
    
    const notification = document.createElement('div');
    notification.id = 'boss-victory-notification';
    
    const isBabyBoss = bossNumber === 1;
    const victoryTitle = isBabyBoss ? '🎉 BABY BOSS DEFEATED! 🎉' : `🎉 BOSS ${bossNumber} DEFEATED! 🎉`;
    
    notification.innerHTML = `
      <div id="boss-victory-content" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                  background: linear-gradient(45deg, rgba(16, 185, 129, 0.4), rgba(5, 150, 105, 0.4), rgba(16, 185, 129, 0.4)); 
                  color: white; padding: 20px 30px; border-radius: 20px; font-weight: bold; 
                  z-index: 10000; text-align: center; box-shadow: 0 0 50px rgba(16, 185, 129, 0.4);
                  border: 4px solid rgba(255, 215, 0, 0.6);
                  backdrop-filter: blur(5px);
                  transition: opacity 0.3s ease-in-out;
                  opacity: 0;
                  max-width: 90vw;
                  width: 400px;
                  pointer-events: auto;">
        <div style="font-size: clamp(20px, 5vw, 28px);">${victoryTitle}</div>
        <div style="font-size: clamp(16px, 4vw, 22px); color: #FFD700; margin-top: 8px;">+${bonus} DSPOINC!</div>
      </div>
      <style>
        @keyframes countdownPulse {
          0%, 100% { transform: translate(-50%, -50%) scale(1); }
          50% { transform: translate(-50%, -50%) scale(1.3); }
        }
      </style>
    `;
    
    // 🐛 BUG #350 FIX: Set notification container to not affect layout (position fixed, no dimensions)
    notification.style.position = 'fixed';
    notification.style.top = '0';
    notification.style.left = '0';
    notification.style.width = '0';
    notification.style.height = '0';
    notification.style.overflow = 'visible';
    notification.style.pointerEvents = 'none';
    notification.style.zIndex = '10000';
    
    document.body.appendChild(notification);
    
    // 🐛 BUG #350 FIX: Restore scroll position immediately after DOM update to prevent frame shift
    requestAnimationFrame(() => {
      window.scrollTo(savedScrollX, savedScrollY);
    });
    
    // Fade in
    setTimeout(() => {
      const div = notification.querySelector('#boss-victory-content');
      if (div) div.style.opacity = '1';
    }, 10);
    
    // Show countdown: 3, 2, 1, GO!
    const countdownDiv = notification.querySelector('#boss-victory-content');
    let countdown = 3;
    
    // Update to countdown after 1.5 seconds (victory info shown first)
    setTimeout(() => {
      if (!countdownDiv || !countdownDiv.parentNode) return;
      
      const countdownInterval = setInterval(() => {
        if (!countdownDiv || !countdownDiv.parentNode) {
          clearInterval(countdownInterval);
          return;
        }
        
        if (countdown > 0) {
          countdownDiv.innerHTML = `
            <div style="font-size: clamp(48px, 12vw, 72px); font-weight: bold; text-shadow: 0 0 20px rgba(255, 255, 255, 1);
                        animation: countdownPulse 0.5s ease-in-out;
                        color: #FFD700;">
              ${countdown}
            </div>
          `;
          countdownDiv.style.animation = 'countdownPulse 0.5s ease-in-out';
          countdown--;
        } else {
          countdownDiv.innerHTML = `
            <div style="font-size: clamp(40px, 10vw, 64px); font-weight: bold; text-shadow: 0 0 30px rgba(16, 185, 129, 1);
                        animation: countdownPulse 0.3s ease-in-out;
                        color: #10b981;">
              GO!
            </div>
          `;
          countdownDiv.style.animation = 'countdownPulse 0.3s ease-in-out';
          clearInterval(countdownInterval);
          
          // Remove after "GO!" shown
          setTimeout(() => {
            if (countdownDiv) countdownDiv.style.opacity = '0';
            setTimeout(() => {
              if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
                // 🐛 BUG #350 FIX: Restore scroll position after notification removed to prevent frame shift
                requestAnimationFrame(() => {
                  window.scrollTo(savedScrollX, savedScrollY);
                });
              }
            }, 300);
          }, 500);
        }
      }, 800); // Countdown updates every 800ms
    }, 1500); // Start countdown after 1.5 seconds
  }
  
  // 🐛 BUG #322 FIX: Update boss HUD HTML elements (outside canvas)
  function updateBossHUD() {
    if (!bossBattleActive || !giantSnakeBoss) {
      // Hide HUD container when no boss battle
      const hudContainer = document.getElementById('snake-boss-hud-container');
      if (hudContainer) {
        hudContainer.classList.add('hidden');
      }
      return;
    }
    
    // Show HUD container
    const hudContainer = document.getElementById('snake-boss-hud-container');
    if (hudContainer) {
      hudContainer.classList.remove('hidden');
    }
    
    // 1. Update Boss HP Bar
    const healthPercent = giantSnakeBoss.health / giantSnakeBoss.maxHealth;
    const hpFill = document.getElementById('snake-boss-hp-fill');
    const hpText = document.getElementById('snake-boss-hp-text');
    
    if (hpFill) {
      hpFill.style.width = `${healthPercent * 100}%`;
      // Color based on health percentage
      if (healthPercent > 0.5) {
        hpFill.style.backgroundColor = '#10b981'; // Green
      } else if (healthPercent > 0.25) {
        hpFill.style.backgroundColor = '#f59e0b'; // Orange
      } else {
        hpFill.style.backgroundColor = '#ef4444'; // Red
      }
    }
    
    if (hpText) {
      hpText.textContent = `${giantSnakeBoss.health}/${giantSnakeBoss.maxHealth}`;
    }
    
    // 2. Update Golden Apples Counter
    const totalApples = giantSnakeBoss.maxHealth; // Baby: 5, Others: 10
    const applesIcons = document.getElementById('snake-boss-apples-icons');
    const applesText = document.getElementById('snake-boss-apples-text');
    
    if (applesIcons) {
      applesIcons.innerHTML = ''; // Clear existing icons
      
      for (let i = 0; i < totalApples; i++) {
        const icon = document.createElement('div');
        icon.className = 'apple-icon rounded-full';
        icon.style.width = '12px';
        icon.style.height = '12px';
        
        if (i < goldenApplesCollected) {
          // ✅ Collected apple (golden glow)
          icon.style.backgroundColor = '#FFD700';
          icon.style.boxShadow = '0 0 5px rgba(255, 215, 0, 0.8)';
        } else {
          // ⬜ Not collected (gray)
          icon.style.backgroundColor = '#444444';
          icon.style.boxShadow = 'none';
        }
        
        applesIcons.appendChild(icon);
      }
    }
    
    if (applesText) {
      applesText.textContent = `${goldenApplesCollected}/${totalApples} 🍎`;
    }
    
    // 3. Update Timer + Bonus
    const timeLeft = giantSnakeBossConfig.bossTimeLimit - bossTimer;
    const seconds = Math.ceil(timeLeft * 0.4);
    const timerText = document.getElementById('snake-boss-timer-text');
    const bonusText = document.getElementById('snake-boss-bonus-text');
    
    if (timerText) {
      timerText.textContent = `⏰ Time: ${seconds}s`;
      // Warning color if low
      if (seconds < 10) {
        timerText.style.color = '#ef4444'; // Red
      } else {
        timerText.style.color = '#fbbf24'; // Yellow
      }
    }
    
    if (bonusText) {
      const index = giantSnakeBoss.bossNumber - 1;
      const potentialBonus = giantSnakeBossConfig.rewards[index] || 30;
      bonusText.textContent = `+${potentialBonus} 💰`;
    }
  }
  
  function drawBossUI(ctx) {
    if (!bossBattleActive || !giantSnakeBoss) {
      // 🐛 BUG #322 FIX: Hide HUD when no boss battle
      updateBossHUD();
      return;
    }
    
    // 🐛 BUG #322 FIX: Update HTML HUD instead of drawing on canvas
    updateBossHUD();
    
    // 🐍 BOSS BATTLE INDICATOR (Bottom of screen - still on canvas)
    // 🐛 TRANSPARENCY FIX: Make boss battle text more transparent to not block player view
    ctx.save(); // Save current context state
    ctx.globalAlpha = 0.5; // Make text 50% transparent (was 100% opaque)
    ctx.shadowBlur = 10;
    ctx.shadowColor = giantSnakeBoss.color;
    ctx.fillStyle = giantSnakeBoss.color;
    ctx.font = 'bold 14px Arial';
    ctx.textAlign = 'center';
    const isBabyBoss = giantSnakeBoss.bossNumber === 1;
    const battleText = isBabyBoss ? '🍼 BABY BOSS BATTLE 🍼' : `🐍 BOSS ${giantSnakeBoss.bossNumber} BATTLE 🐍`;
    ctx.fillText(battleText, canvas.width / 2, canvas.height - 10);
    ctx.restore(); // Restore context state (resets globalAlpha)
    ctx.shadowBlur = 0;
    ctx.textAlign = 'left'; // Reset
  }
  
  function drawGoldenApples(ctx) {
    if (!bossBattleActive) return;
    
    goldenApples.forEach(apple => {
      const x = apple.x * gridSize;
      const y = apple.y * gridSize;
      
      // Glow effect (softened for readability)
      ctx.shadowBlur = 8;
      ctx.shadowColor = '#FFD700';
      
      // Draw golden apple
      ctx.fillStyle = 'rgba(255, 215, 0, 0.82)';
      ctx.beginPath();
      ctx.arc(x + gridSize / 2, y + gridSize / 2, gridSize / 2 - 2, 0, Math.PI * 2);
      ctx.fill();
      
      // Inner shine
      ctx.fillStyle = 'rgba(255, 247, 0, 0.75)';
      ctx.beginPath();
      ctx.arc(x + gridSize / 2 - 3, y + gridSize / 2 - 3, gridSize / 4, 0, Math.PI * 2);
      ctx.fill();
      
      ctx.shadowBlur = 0;
    });
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
    updateSnakeSpeedFromPerks();
    gameInterval = setInterval(moveSnake, snakeActiveSpeed);
    
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
        // Show DSPOINC score (already calculated with multiplier)
        scoreDisplay.textContent = `💰 Snake Score: $${score} DSPOINC (${roleMultiplier}x Role Bonus!)`;
      } else {
        scoreDisplay.textContent = `💰 Snake Score: $${score} DSPOINC`;
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
    if (score >= 1000 && !window.brainUnlocked) { // Updated from 100 to 1000 (score is now in DSPOINC)
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

    // Draw static/hostile elements first, then player snake on top for readability
    const roleColors = getSnakeRoleColors();

    // 🧀 Draw cheese - Role-based colors (only if not in boss battle)
    if (!bossBattleActive) {
      if (cheeseImg.complete) {
        ctx.drawImage(cheeseImg, food.x * gridSize, food.y * gridSize, gridSize, gridSize);
      } else {
        ctx.fillStyle = roleColors.food;
        ctx.fillRect(food.x * gridSize, food.y * gridSize, gridSize, gridSize);
      }
    }

    // 🐍🧀 SEASON 5: Draw Boss Battle Elements
    if (bossBattleActive) {
      console.log(`🎮 Boss battle active! Boss exists: ${!!giantSnakeBoss}, Apples: ${goldenApples.length}`);
      drawGoldenApples(ctx);

      if (giantSnakeBoss) {
        console.log(`🐍 About to draw boss... Segments: ${giantSnakeBoss.segments.length}, Head: (${giantSnakeBoss.head.x}, ${giantSnakeBoss.head.y})`);
        giantSnakeBoss.draw(ctx);
      } else {
        console.warn('⚠️ Boss battle active but giantSnakeBoss is null!');
      }

      drawBossUI(ctx);
    }

    // 🔁 Trail glow - Role-based colors
    for (let i = 0; i < snake.length; i++) {
      const segment = snake[i];
      
      // 🐛 BUG #229 FIX: Clamp trail positions to visible bounds
      const clampedX = Math.max(0, Math.min(tileCountX - 1, segment.x));
      const clampedY = Math.max(0, Math.min(tileCountY - 1, segment.y));
      
      const t = i / snake.length;
      const fade = 0.25 * (1 - t);
      ctx.save();
      ctx.globalAlpha = fade;
      ctx.fillStyle = roleColors.trail;
      ctx.beginPath();
      ctx.arc(
        clampedX * gridSize + gridSize / 2,
        clampedY * gridSize + gridSize / 2,
        gridSize * 0.3,
        0,
        2 * Math.PI
      );
      ctx.fill();
      ctx.restore();
    }

    // 🧬 Render Snake
    snake.forEach((segment, index) => {
      // 🐛 BUG #229 FIX: Clamp segment positions to visible bounds
      // Prevents snake from being rendered off-screen during boss battles or edge cases
      const clampedX = Math.max(0, Math.min(tileCountX - 1, segment.x));
      const clampedY = Math.max(0, Math.min(tileCountY - 1, segment.y));
      
      // 🧪 DEBUG: Log if segment was out of bounds
      if (segment.x !== clampedX || segment.y !== clampedY) {
        console.warn(`🐛 BUG #229: Snake segment out of bounds! Original: (${segment.x}, ${segment.y}), Clamped: (${clampedX}, ${clampedY})`);
      }
      
      const isHead = index === 0;
      const img = isHead ? snakeGameHeadImg : snakeGameDnaImg;
      const next = snake[index + 1] || snake[index - 1] || segment;
      const dir = getDirection(segment, next);

      const pulse = 0.5 + 0.5 * Math.sin(performance.now() / 120 + index);
      ctx.globalAlpha = 0.85 + pulse * 0.1;

      const posX = clampedX * gridSize + gridSize / 2;
      const posY = clampedY * gridSize + gridSize / 2;

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

      // 🎯 Boss readability: highlight player head during boss battles
      if (bossBattleActive && isHead) {
        ctx.save();
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.9)';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(posX, posY, gridSize * 0.52, 0, Math.PI * 2);
        ctx.stroke();
        ctx.restore();
      }

      ctx.globalAlpha = 1;
      ctx.filter = "none";
    });

    // 🐛 BUG #312 FIX: Draw invalid turn glow effect (enhanced - more frames, more love!)
    if (invalidTurnGlow > 0 && snake.length > 0) {
      const head = snake[0];
      const headX = head.x * gridSize + gridSize / 2;
      const headY = head.y * gridSize + gridSize / 2;
      
      // Enhanced pulsing red/orange glow effect with more intensity
      const glowIntensity = invalidTurnGlow;
      const pulsePhase = performance.now() / 100; // Pulsing animation
      const pulseAmount = 0.3 + 0.2 * Math.sin(pulsePhase); // Pulsing between 0.3-0.5
      const glowSize = gridSize * (1.8 + pulseAmount); // Larger, pulsing size
      
      // Outer glow (red/orange gradient) - more intense
      const gradient = ctx.createRadialGradient(headX, headY, 0, headX, headY, glowSize);
      gradient.addColorStop(0, `rgba(255, 0, 0, ${glowIntensity * 0.95})`); // Brighter red center
      gradient.addColorStop(0.4, `rgba(255, 100, 0, ${glowIntensity * 0.75})`); // Brighter orange middle
      gradient.addColorStop(0.7, `rgba(255, 150, 0, ${glowIntensity * 0.4})`); // Extended orange
      gradient.addColorStop(1, `rgba(255, 0, 0, 0)`); // Transparent edge
      
      ctx.save();
      ctx.globalAlpha = glowIntensity;
      ctx.fillStyle = gradient;
      ctx.beginPath();
      ctx.arc(headX, headY, glowSize, 0, Math.PI * 2);
      ctx.fill();
      
      // Middle glow layer (orange/yellow)
      ctx.globalAlpha = glowIntensity * 0.85;
      const middleGradient = ctx.createRadialGradient(headX, headY, 0, headX, headY, gridSize * 1.2);
      middleGradient.addColorStop(0, `rgba(255, 200, 0, ${glowIntensity * 0.9})`);
      middleGradient.addColorStop(1, `rgba(255, 100, 0, 0)`);
      ctx.fillStyle = middleGradient;
      ctx.beginPath();
      ctx.arc(headX, headY, gridSize * 1.2, 0, Math.PI * 2);
      ctx.fill();
      
      // Inner bright flash (white/yellow) - more intense
      ctx.globalAlpha = glowIntensity * 0.95;
      ctx.fillStyle = `rgba(255, 255, 200, ${glowIntensity})`; // Brighter yellow-white flash
      ctx.beginPath();
      ctx.arc(headX, headY, gridSize * 0.9, 0, Math.PI * 2);
      ctx.fill();
      
      // Direction indicator (arrow pointing in attempted direction)
      if (invalidTurnGlowDirection) {
        ctx.strokeStyle = `rgba(255, 255, 255, ${glowIntensity})`;
        ctx.lineWidth = 3;
        ctx.beginPath();
        
        const arrowSize = gridSize * 0.6;
        let arrowX = headX;
        let arrowY = headY;
        
        switch (invalidTurnGlowDirection) {
          case 'left':
            arrowX -= arrowSize;
            ctx.moveTo(headX, headY);
            ctx.lineTo(arrowX, arrowY);
            ctx.lineTo(arrowX + arrowSize * 0.3, arrowY - arrowSize * 0.3);
            ctx.moveTo(arrowX, arrowY);
            ctx.lineTo(arrowX + arrowSize * 0.3, arrowY + arrowSize * 0.3);
            break;
          case 'right':
            arrowX += arrowSize;
            ctx.moveTo(headX, headY);
            ctx.lineTo(arrowX, arrowY);
            ctx.lineTo(arrowX - arrowSize * 0.3, arrowY - arrowSize * 0.3);
            ctx.moveTo(arrowX, arrowY);
            ctx.lineTo(arrowX - arrowSize * 0.3, arrowY + arrowSize * 0.3);
            break;
          case 'up':
            arrowY -= arrowSize;
            ctx.moveTo(headX, headY);
            ctx.lineTo(arrowX, arrowY);
            ctx.lineTo(arrowX - arrowSize * 0.3, arrowY + arrowSize * 0.3);
            ctx.moveTo(arrowX, arrowY);
            ctx.lineTo(arrowX + arrowSize * 0.3, arrowY + arrowSize * 0.3);
            break;
          case 'down':
            arrowY += arrowSize;
            ctx.moveTo(headX, headY);
            ctx.lineTo(arrowX, arrowY);
            ctx.lineTo(arrowX - arrowSize * 0.3, arrowY - arrowSize * 0.3);
            ctx.moveTo(arrowX, arrowY);
            ctx.lineTo(arrowX + arrowSize * 0.3, arrowY - arrowSize * 0.3);
            break;
        }
        ctx.stroke();
      }
      
      ctx.restore();
    }

    // 🏆 Draw achievement popups
    drawAchievementPopups();
  } // ✅ End of draw()

  function moveSnake() {
    if (isSnakePaused) return;
    
    // 🐛 BUG #312 FIX: Decay invalid turn glow effect (enhanced - more frames, more love!)
    if (invalidTurnGlow > 0) {
      invalidTurnGlow = Math.max(0, invalidTurnGlow - 0.08); // Fade out over ~12-13 frames (longer, more visible)
      if (invalidTurnGlow <= 0) {
        invalidTurnGlowDirection = null; // Clear direction when glow fades
      }
    }

    // 🧪 DEBUG: Log every moveSnake call (disabled for production)
    // console.log('🐍 moveSnake() called - Timer:', cheeseTeleportTimer);
    
    // 🐍🧀 SEASON 5: Boss Battle Updates
    if (bossBattleActive && giantSnakeBoss) {
      // Update boss AI
      giantSnakeBoss.update();
      
      // Update boss timer
      updateBossTimer();
      
      // Check collisions
      checkBossCollision();
      checkGoldenAppleCollection();
    }

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

    // 🎯 Game over logic - Wall collision and self-collision
    // 🔧 BOSS BATTLE FIX: During boss battles, player should NOT die from walls!
    //    They only die from hitting the boss snake itself (checked in checkBossCollision)
    const shouldCheckWalls = !bossBattleActive; // Only check walls in normal gameplay
    
    if (shouldCheckWalls) {
      // Normal gameplay: Die from hitting walls
      if (
        head.x < 0 || head.x >= tileCountX ||
        head.y < 0 || head.y >= tileCountY ||
        snake.some(seg => seg.x === head.x && seg.y === head.y)
      ) {
        // Call proper game over function (like Tetris)
        onGameOver();
        return;
      }
    } else {
      // Boss battle: Only die from self-collision, NOT walls!
      // Allow player to move anywhere in canvas (including top area with UI)
      
      // Wrap around walls (player can go through walls during boss battle!)
      if (head.x < 0) head.x = tileCountX - 1;
      if (head.x >= tileCountX) head.x = 0;
      if (head.y < 0) head.y = tileCountY - 1;
      if (head.y >= tileCountY) head.y = 0;
      
      // 🐛 BUG #229 FIX: Final safety clamp to ensure head is within bounds
      head.x = Math.max(0, Math.min(tileCountX - 1, head.x));
      head.y = Math.max(0, Math.min(tileCountY - 1, head.y));
      
      // Check self-collision only
      if (snake.some(seg => seg.x === head.x && seg.y === head.y)) {
        onGameOver();
        return;
      }
    }

    snake.unshift(head);

    // 🍽️ Check if snake eats cheese
    const ate = head.x === food.x && head.y === food.y;
    if (ate) {
      // 🎵 Play cheese eating sound
      snakeSounds.playSound('eatCheese');
      
      // Role-based scoring with multipliers
      const roleMultiplier = getSnakeRoleScoreMultiplier();
      const baseScore = 10; // Changed from 1 to 10 for proper DSPOINC calculation
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
      
      // 🐍🧀 SEASON 5: Check for Giant Cheese Snake Boss spawn
      // 🎯 PRODUCTION MODE: Cheese-based boss spawning
      const isLocalDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
      
      let bossNumber = null;
      let isBossTrigger = false;
      
      // 🚀 BOTH MODES: Use same spawn points (3, 10, 30, 50, 80, 120, 170, 230, 300)
      const spawnIndex = giantSnakeBossConfig.spawnPoints.indexOf(cheeseEaten);
      if (spawnIndex !== -1) {
        bossNumber = spawnIndex + 1; // 1-9 (includes Baby Boss!)
        isBossTrigger = true;
        const bossName = bossNumber === 1 ? '🍼 Baby Boss' : `Boss ${bossNumber}`;
        const mode = isLocalDevelopment ? '🧪 TEST MODE' : '🚀 PRODUCTION MODE';
        console.log(`${mode}: ${bossName} triggered at ${cheeseEaten} cheeses!`);
      }
      
      if (isBossTrigger && !giantSnakeBossActive && bossNumber) {
        spawnGiantCheeseBoss(cheeseEaten, bossNumber);
      }
      
      // 🎵 Check for score milestones (every 100 DSPOINC)
      if (score % 100 === 0) {
        snakeSounds.playSound('scoreMilestone');
        console.log(`🎵 Score milestone reached: ${score} DSPOINC!`);
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

    // 🎯 Disable touch gating so buttons work post-game (matches Tetris)
    disableGlobalSnakeTouch();

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
      // Score is already in DSPOINC (baseScore = 10 with role multiplier applied)
      finalScoreText.textContent = `You earned $${finalScore} DSPOINC`;
      console.log("🐍 Displaying final score:", finalScore, "DSPOINC");

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
    // 🏆 REVISED 2025-10-26: Score thresholds based on realistic max (Grid: 10×20 = 200 tiles)
    // Theoretical max: 3,920 DSPOINC (196 cheese with VIP 2.0x, 98% grid coverage)
    // Expert realistic: 2,000 DSPOINC (100 cheese, 50% grid coverage)
    // Legendary challenge: 3,500 DSPOINC (175 cheese, 88% grid coverage)
    const achievements = [
      // === CHEESE-BASED (5 achievements) ===
      { key: 'first_cheese', condition: cheeseEaten >= 1 },
      { key: 'cheese_collector', condition: cheeseEaten >= 5 },
      { key: 'cheese_hunter', condition: cheeseEaten >= 10 },
      { key: 'cheese_master', condition: cheeseEaten >= 25 },
      { key: 'cheese_legend', condition: cheeseEaten >= 75 },  // Reduced: 100 → 75 (expert)
      
      // === SCORE-BASED (6 achievements - 5% to 89% of theoretical max 3920) ===
      { key: 'score_hunter', condition: score >= 200 },    // Reduced: 1000 → 200 (5% of max)
      { key: 'point_master', condition: score >= 500 },    // Reduced: 2500 → 500 (13% of max)
      { key: 'high_scorer', condition: score >= 1000 },    // Reduced: 5000 → 1000 (26% of max)
      { key: 'snake_king', condition: score >= 1500 },     // Reduced: 10000 → 1500 (38% of max)
      { key: 'score_legend', condition: score >= 2000 },   // Reduced: 20000 → 2000 (51% of max)
      { key: 'score_god', condition: score >= 3500 },      // Reduced: 50000 → 3500 (89% of max - LEGENDARY!)
      
      // === LEVEL-BASED (4 achievements) ===
      { key: 'speed_demon', condition: currentLevel >= 5 },
      { key: 'level_master', condition: currentLevel >= 10 },
      { key: 'level_warrior', condition: currentLevel >= 15 },
      { key: 'level_champion', condition: currentLevel >= 20 },
      
      // === LENGTH-BASED (3 achievements) ===
      { key: 'long_snake', condition: longestSnake >= 10 },
      { key: 'giant_snake', condition: longestSnake >= 25 },
      { key: 'mega_snake', condition: longestSnake >= 50 },
      // REMOVED: snake_legend (100 segments) - Too difficult
      
      // === TIME-BASED (2 achievements) ===
      { key: 'survivor', condition: (Date.now() - gameStartTime) >= 120000 },      // 2 minutes
      { key: 'endurance_master', condition: (Date.now() - gameStartTime) >= 300000 } // 5 minutes
      
      // REMOVED: All meta/perfect game achievements (8 total)
      // - game_starter, game_player, game_master, game_legend
      // - snake_champion, snake_ninja, perfectionist, ultimate_player
      // - snake_legend (100 segments impossible)
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
    
    // FIXED: Send game stats with achievement unlock (Nov 1, 2025)
    fetch(`${apiBaseUrl}/api/dev/unlock-snake-achievement.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        user_id: userId, 
        achievement_key: achievementKey,
        game_score: score,
        apples_eaten: cheeseEaten,
        level_reached: currentLevel,
        games_played: gamesPlayed,
        longest_snake: longestSnake
      }),
      keepalive: true,
      cache: 'no-store'
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
    // 🎮 P KEY PAUSE/UNPAUSE (BUG #263)
    if (e.key === 'p' || e.key === 'P') {
      e.preventDefault();
      const pauseBtn = document.getElementById('pause-snake-btn');
      if (pauseBtn) {
        pauseBtn.click(); // Trigger existing pause/unpause logic
        console.log('🎮 P key pressed - toggling Snake pause state');
      }
      return;
    }
    
    // 🐛 BUG #312 FIX: Prevent opposite direction turns (prevents self-collision)
    switch (e.key) {
      case "ArrowLeft": case "a": 
        // Only allow left if not moving right (opposite direction)
        if (velocity.x === 0) {
          velocity = { x: -1, y: 0 };
        } else if (velocity.x === 1) {
          // Attempted opposite turn - show glow instead of changing direction
          invalidTurnGlow = 1.0;
          invalidTurnGlowDirection = 'left';
          console.log('🚫 Invalid turn: Cannot turn left while moving right');
        }
        break;
      case "ArrowRight": case "d": 
        // Only allow right if not moving left (opposite direction)
        if (velocity.x === 0) {
          velocity = { x: 1, y: 0 };
        } else if (velocity.x === -1) {
          // Attempted opposite turn - show glow instead of changing direction
          invalidTurnGlow = 1.0;
          invalidTurnGlowDirection = 'right';
          console.log('🚫 Invalid turn: Cannot turn right while moving left');
        }
        break;
      case "ArrowUp": case "w": 
        // Only allow up if not moving down (opposite direction)
        if (velocity.y === 0) {
          velocity = { x: 0, y: -1 };
        } else if (velocity.y === 1) {
          // Attempted opposite turn - show glow instead of changing direction
          invalidTurnGlow = 1.0;
          invalidTurnGlowDirection = 'up';
          console.log('🚫 Invalid turn: Cannot turn up while moving down');
        }
        break;
      case "ArrowDown": case "s": 
        // Only allow down if not moving up (opposite direction)
        if (velocity.y === 0) {
          velocity = { x: 0, y: 1 };
        } else if (velocity.y === -1) {
          // Attempted opposite turn - show glow instead of changing direction
          invalidTurnGlow = 1.0;
          invalidTurnGlowDirection = 'down';
          console.log('🚫 Invalid turn: Cannot turn down while moving up');
        }
        break;
    }
  });

// Touch controls with scroll prevention - Enhanced for mobile
let touchStartX = 0, touchStartY = 0;
let isSnakeGameActive = false;
let snakeScrollLocked = false;
const SNAKE_SWIPE_THRESHOLD = 45; // Safer threshold to avoid accidental turns
const SNAKE_AXIS_LOCK_RATIO = 1.2; // Require clearer dominant axis

function enableGlobalSnakeTouch() { 
  isSnakeGameActive = true; 
  document.body.style.overflow = "hidden";
  snakeScrollLocked = true;
  
  // 🚨 BUG #263 FIX: Disable all page links/buttons during active gameplay (except game controls and modal buttons)
  document.querySelectorAll('a, button').forEach(el => {
    // Skip game control buttons (but NOT guide button!)
    if (el.id && el.id.includes('snake') && !el.id.includes('guide')) {
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
  console.log('🔒 Page links/buttons disabled during Snake gameplay (guide button blocked)');
}

function disableGlobalSnakeTouch() { 
  isSnakeGameActive = false;
  document.body.style.overflow = "";
  snakeScrollLocked = false;
  unlockSnakeScroll(); // 🎯 Ensure scrolling is unlocked when disabling touch
  
  // 🚨 BUG #263 FIX: Re-enable all page links/buttons when game ends
  document.querySelectorAll('a, button').forEach(el => {
    el.style.pointerEvents = '';
    el.style.opacity = '';
  });
  console.log('🔓 Page links/buttons re-enabled after Snake gameplay');
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
  const isSnakeCanvasTarget = Boolean(e.target && (e.target.id === 'snake-canvas' || e.target.closest?.('#snake-canvas')));
  if (isSnakeGameActive && !isSnakePaused && isSnakeCanvasTarget) {
    e.preventDefault();
    e.stopPropagation();
  }
}, { passive: false });

canvas.addEventListener("touchstart", function(e) {
  if (!isSnakeGameActive || isSnakePaused) return;
  e.preventDefault();
  e.stopPropagation();
  if (!e.touches || !e.touches.length) return;
  const touch = e.touches[0];
  touchStartX = touch.clientX;
  touchStartY = touch.clientY;
}, { passive: false });

canvas.addEventListener("touchend", function(e) {
  if (!isSnakeGameActive || isSnakePaused) return;
  e.preventDefault();
  e.stopPropagation();
  if (!e.changedTouches || !e.changedTouches.length) return;
  const touch = e.changedTouches[0];
  const deltaX = touch.clientX - touchStartX;
  const deltaY = touch.clientY - touchStartY;
  const absDeltaX = Math.abs(deltaX);
  const absDeltaY = Math.abs(deltaY);
  
  // Ignore micro-swipes/taps
  const minSwipeDistance = SNAKE_SWIPE_THRESHOLD;
  if (Math.max(absDeltaX, absDeltaY) < minSwipeDistance) {
    return;
  }

  const isHorizontalSwipe = absDeltaX > absDeltaY * SNAKE_AXIS_LOCK_RATIO;
  const isVerticalSwipe = absDeltaY > absDeltaX * SNAKE_AXIS_LOCK_RATIO;
  if (!isHorizontalSwipe && !isVerticalSwipe) {
    return;
  }
  
  // Only process swipes if game is active and not paused
  // 🐛 BUG #312 FIX: Prevent opposite direction turns for touch controls too
  if (isHorizontalSwipe) {
    // Horizontal swipe detection - more responsive
    if (deltaX > minSwipeDistance) {
      // Only allow right if not moving left (opposite direction)
      if (velocity.x === 0) {
        velocity = { x: 1, y: 0 }; // Right
      } else if (velocity.x === -1) {
        // Attempted opposite turn - show glow instead
        invalidTurnGlow = 1.0;
        invalidTurnGlowDirection = 'right';
        console.log('🚫 Invalid turn (touch): Cannot turn right while moving left');
      }
    } else if (deltaX < -minSwipeDistance) {
      // Only allow left if not moving right (opposite direction)
      if (velocity.x === 0) {
        velocity = { x: -1, y: 0 }; // Left
      } else if (velocity.x === 1) {
        // Attempted opposite turn - show glow instead
        invalidTurnGlow = 1.0;
        invalidTurnGlowDirection = 'left';
        console.log('🚫 Invalid turn (touch): Cannot turn left while moving right');
      }
    }
  } else if (isVerticalSwipe) {
    // Vertical swipe detection - more responsive
    if (deltaY > minSwipeDistance) {
      // Only allow down if not moving up (opposite direction)
      if (velocity.y === 0) {
        velocity = { x: 0, y: 1 }; // Down
      } else if (velocity.y === -1) {
        // Attempted opposite turn - show glow instead
        invalidTurnGlow = 1.0;
        invalidTurnGlowDirection = 'down';
        console.log('🚫 Invalid turn (touch): Cannot turn down while moving up');
      }
    } else if (deltaY < -minSwipeDistance) {
      // Only allow up if not moving down (opposite direction)
      if (velocity.y === 0) {
        velocity = { x: 0, y: -1 }; // Up
      } else if (velocity.y === 1) {
        // Attempted opposite turn - show glow instead
        invalidTurnGlow = 1.0;
        invalidTurnGlowDirection = 'up';
        console.log('🚫 Invalid turn (touch): Cannot turn up while moving down');
      }
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
        
        // 🚨 BUG #263 FIX: Re-enable all page links/buttons when paused
        document.querySelectorAll('a, button').forEach(el => {
          el.style.pointerEvents = '';
          el.style.opacity = '';
        });
        console.log('🔓 Page links/buttons re-enabled during Snake pause');
      } else {
        // Only restart if game is not over
        if (gameInterval) {
          clearInterval(gameInterval);
          gameInterval = setInterval(moveSnake, 400);
          // 🎯 Lock scrolling when resumed (like Tetris)
          lockSnakeScroll();
          
          // 🚨 BUG #263 FIX: Disable page links/buttons again when resumed
          document.querySelectorAll('a, button').forEach(el => {
            // Skip game control buttons
            if (el.id && el.id.includes('snake') && !el.id.includes('guide')) {
              return; // Keep game controls enabled
            }
            // Skip buttons inside modals
            const parentModal = el.closest('[id*="modal"]') || el.closest('[class*="modal"]');
            if (parentModal) {
              return; // Keep modal buttons enabled
            }
            // Disable everything else
            el.style.pointerEvents = 'none';
            el.style.opacity = '0.5';
          });
          console.log('🔒 Page links/buttons disabled during Snake gameplay');
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
    
    // 🚀 Check for auto-start flag (from Play Again button)
    if (localStorage.getItem('snake_auto_start') === 'true') {
      console.log('🚀 Auto-start flag detected - starting game automatically');
      localStorage.removeItem('snake_auto_start'); // Clear flag
      setTimeout(() => {
        startGameWithCountdown();
      }, 500); // Small delay to ensure page is fully loaded
    }
  } else {
    startGameWithCountdown();
  }

  // 🎮 Restart game function (called by Play Again button) - Like Space Invaders
  function restartSnakeGame() {
    console.log('🔄 Play Again button clicked - restarting Snake game');
    
    // Hide game over modal
    const modal = document.getElementById("snake-over-modal");
    if (modal) {
      modal.classList.add("hidden");
      modal.style.display = "none";
    }
    
    // Release touch locks before reload to avoid stuck UI if reload is blocked
    disableGlobalSnakeTouch();

    // 🚀 Set flag to auto-start game after reload
    localStorage.setItem('snake_auto_start', 'true');
    
    // Reload page to get fresh game state (simplest and most reliable)
    window.location.reload();
  }
  
  // 🏁 End game function (called by OK button) - Like Space Invaders
  function endSnakeGame() {
    console.log('🏁 OK button clicked - ending Snake game');
    
    // 🚨 CRITICAL: Clear any existing game interval
    if (gameInterval) {
      clearInterval(gameInterval);
      gameInterval = null;
      console.log('🧹 Cleared game interval on end');
    }

    // ✅ Fully release touch/scroll locks so UI becomes interactive again
    disableGlobalSnakeTouch();
    
    // Hide game over modal
    const modal = document.getElementById("snake-over-modal");
    if (modal) {
      modal.classList.add("hidden");
      modal.style.display = "none";
    }
    
    // Clear the canvas
    const canvas = document.getElementById("snake-canvas");
    if (canvas) {
      const ctx = canvas.getContext('2d');
      if (ctx) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#000000';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        console.log('🎨 Snake canvas cleared');
      }
    }
    
    // 🚨 CRITICAL: Reset game state flags
    isSnakePaused = false;
    
    // Reset start button text and state
    const startBtn = document.getElementById("start-snake-btn");
    if (startBtn) {
      startBtn.textContent = "▶️ Start";
      startBtn.disabled = false;
    }
    
    // Reset pause button text
    const pauseBtn = document.getElementById("pause-snake-btn");
    if (pauseBtn) {
      pauseBtn.textContent = "⏸️ Pause";
    }
  }

  // Expose functions for external use
  window.startSnakeGame = startGameWithCountdown;
  window.restartSnakeGame = restartSnakeGame;
  window.endSnakeGame = endSnakeGame;

  const playAgainBtn = document.getElementById('snake-play-again-btn');
  if (playAgainBtn) {
    const restartHandler = (e) => {
      e.preventDefault();
      e.stopPropagation();
      restartSnakeGame();
    };
    playAgainBtn.addEventListener('click', restartHandler);
    playAgainBtn.addEventListener('touchend', restartHandler, { passive: false });
    playAgainBtn.style.touchAction = 'manipulation';
    playAgainBtn.style.webkitTapHighlightColor = 'transparent';
  }

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