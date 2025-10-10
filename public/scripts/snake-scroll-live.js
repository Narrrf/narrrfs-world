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

// 🏆 ROLE-BASED GAMEPLAY SYSTEM - Season 4 Feature
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

// 🎨 Role-based theme application for Snake
function applySnakeRoleTheme() {
  // Get user roles from localStorage or default test roles
  const isLocalDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
  if (isLocalDevelopment) {
    snakeUserRoles = [
      "VIP Holder", "Holder", "Champion", "Season Tester", "Early Bird", "Cheese Hunter"
    ];
  }
  
  // Determine primary role
  const priorityOrder = ['VIP Holder', '🎴 VIP Holder', 'Holder', '🏆 Holder', 'Champion', 'Season Tester', 'Early Bird', 'Cheese Hunter', '🧀 Cheese Hunter'];
  let primaryRole = null;
  for (const role of priorityOrder) {
    if (snakeUserRoles.includes(role)) {
      // Return clean role name for consistent theming and multipliers
      primaryRole = role.replace(/^[🎴🏆🧀]\s*/, '');
      break;
    }
  }
  
  const theme = snakeRoleThemes[primaryRole] || 'default';
  console.log(`🎨 Snake theme: ${theme} for role: ${primaryRole}`);
  
  // Update score display with role multiplier
  updateSnakeScoreDisplay();
  
  // Apply theme to canvas
  const canvas = document.getElementById('snake-canvas');
  if (canvas) {
    canvas.classList.remove('golden', 'silver', 'cheese', 'rainbow', 'blue', 'red');
    if (theme !== 'default') {
      canvas.classList.add(theme);
    }
  }
  
  // Apply theme to controls section
  const controlsSection = document.getElementById('snake-controls-section');
  const controlsTitle = document.getElementById('snake-controls-title');
  if (controlsSection && controlsTitle) {
    controlsSection.classList.remove('golden', 'silver', 'cheese', 'rainbow', 'blue', 'red');
    controlsTitle.classList.remove('golden', 'silver', 'cheese', 'rainbow', 'blue', 'red');
    if (theme !== 'default') {
      controlsSection.classList.add(theme);
      controlsTitle.classList.add(theme);
    }
    console.log(`🎨 Snake controls theme applied: ${theme}`);
  }
}

// 🏆 Get user's primary role (highest priority role)
function getSnakePrimaryRole() {
  const priorityOrder = ['VIP Holder', '🎴 VIP Holder', 'Holder', '🏆 Holder', 'Champion', 'Season Tester', 'Early Bird', 'Cheese Hunter', '🧀 Cheese Hunter'];
  
  for (const role of priorityOrder) {
    if (snakeUserRoles.includes(role)) {
      // Return clean role name for consistent theming and multipliers
      return role.replace(/^[🎴🏆🧀]\s*/, '');
    }
  }
  
  return null;
}

// ⚡ Calculate role-based score multiplier
function getSnakeRoleScoreMultiplier() {
  const primaryRole = getSnakePrimaryRole();
  return snakeRoleMultipliers[primaryRole] || 1.0;
}

// 🏆 Update Snake score display with role bonus
function updateSnakeScoreDisplay() {
  const scoreDisplay = document.getElementById('snake-score');
  if (scoreDisplay) {
    const roleMultiplier = getSnakeRoleScoreMultiplier();
    const primaryRole = getSnakePrimaryRole();
    
    if (roleMultiplier > 1.0) {
      scoreDisplay.textContent = `💰 Snake Score: $${score * 10} DSPOINC (${roleMultiplier}x Role Bonus!)`;
    } else {
      scoreDisplay.textContent = `💰 Snake Score: $${score * 10} DSPOINC`;
    }
    
    console.log(`🏆 Snake score display update: Role=${primaryRole}, Multiplier=${roleMultiplier}x, Score=${score * 10} DSPOINC`);
  }
}

// 🏆 GLOBAL TEST FUNCTION - Test Snake role-based features
window.testSnakeRoleFeatures = function() {
  console.log('🏆 Testing Snake role-based features...');
  console.log('Current roles:', snakeUserRoles);
  console.log('Primary role:', getSnakePrimaryRole());
  console.log('Score multiplier:', getSnakeRoleScoreMultiplier());
  console.log('Expected theme:', snakeRoleThemes[getSnakePrimaryRole()]);
  
  // Test emoji role matching
  console.log('🧪 Testing emoji role matching...');
  const testRoles = ['🎴 VIP Holder', '🏆 Holder', '🧀 Cheese Hunter'];
  testRoles.forEach(role => {
    const cleanRole = role.replace(/^[🎴🏆🧀]\s*/, '');
    console.log(`Role: ${role} -> Clean: ${cleanRole} -> Multiplier: ${snakeRoleMultipliers[cleanRole]}`);
  });
  
  // Test priority order matching
  console.log('🎯 Testing priority order...');
  const priorityOrder = ['VIP Holder', '🎴 VIP Holder', 'Holder', '🏆 Holder', 'Champion', 'Season Tester', 'Early Bird', 'Cheese Hunter', '🧀 Cheese Hunter'];
  priorityOrder.forEach(role => {
    const hasRole = snakeUserRoles.includes(role);
    const cleanRole = role.replace(/^[🎴🏆🧀]\s*/, '');
    console.log(`${hasRole ? '✅' : '❌'} ${role} -> Clean: ${cleanRole} -> Multiplier: ${snakeRoleMultipliers[cleanRole]}`);
  });
  
  const canvas = document.getElementById('snake-canvas');
  console.log('Canvas element:', canvas);
  console.log('Canvas classes:', canvas?.className);
  console.log('Current score:', score);
  console.log('Current DSPOINC:', score * 10);
};

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

// ✅ DOM-ready game boot
window.addEventListener("DOMContentLoaded", () => {
  initSnake(); // ✅ Runs only after DOM is ready
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
    clearInterval(gameInterval);
    resetGame();
    
    // 🛠️ Mock fallback if testing locally (same as Tetris)
    let discordId = localStorage.getItem("discord_id");
    let discordName = localStorage.getItem("discord_name");
    
    if (!discordId) {
      discordId = "328601656659017732"; // Narrrf's Discord ID for testing
      discordName = "narrrf";
      localStorage.setItem("discord_id", discordId);
      localStorage.setItem("discord_name", discordName);
    }
    
    gameInterval = setInterval(moveSnake, 250); // slow start
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
    food = {
      x: Math.floor(Math.random() * tileCountX),
      y: Math.floor(Math.random() * tileCountY)
    };
  }

  function updateScore() {
    if (scoreDisplay) {
      const roleMultiplier = getSnakeRoleScoreMultiplier();
      
      if (roleMultiplier > 1.0) {
        scoreDisplay.textContent = `💰 Snake Score: $${score * 10} DSPOINC (${roleMultiplier}x Role Bonus!)`;
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

    // 🔁 Trail glow
    for (let i = 0; i < snake.length; i++) {
      const segment = snake[i];
      const t = i / snake.length;
      const fade = 0.25 * (1 - t);
      ctx.save();
      ctx.globalAlpha = fade;
      ctx.fillStyle = "#39FF14";
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
        ctx.fillStyle = isHead ? "#FFD700" : "#39FF14";
        ctx.fillRect(segment.x * gridSize, segment.y * gridSize, gridSize, gridSize);
      }

      ctx.globalAlpha = 1;
      ctx.filter = "none";
    });

    // 🧀 Draw cheese
    if (cheeseImg.complete) {
      ctx.drawImage(cheeseImg, food.x * gridSize, food.y * gridSize, gridSize, gridSize);
    } else {
      ctx.fillStyle = "#FFA500";
      ctx.fillRect(food.x * gridSize, food.y * gridSize, gridSize, gridSize);
    }
    
    // 🏆 Draw achievement popups
    drawAchievementPopups();
  } // ✅ End of draw()

  function moveSnake() {
    if (isSnakePaused) return;

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
      
      // 🏆 Apply role-based scoring
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
      // ✅ Show role multiplier in final score display
      const roleMultiplier = getSnakeRoleScoreMultiplier();
      if (roleMultiplier > 1.0) {
        finalScoreText.textContent = `You earned $${finalScore * 10} DSPOINC (${roleMultiplier}x Role Bonus!)`;
      } else {
        finalScoreText.textContent = `You earned $${finalScore * 10} DSPOINC`;
      }
      console.log("🐍 Displaying score:", finalScore, "with role multiplier:", roleMultiplier);

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
const SNAKE_SWIPE_THRESHOLD = 50; // Increased from 30 to 50 for better mobile control

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
  
  // Increased minimum swipe distance for better mobile control
  const minSwipeDistance = SNAKE_SWIPE_THRESHOLD;
  
  // Only process swipes if game is active and not paused
  if (Math.abs(deltaX) > Math.abs(deltaY)) {
    if (deltaX > minSwipeDistance && velocity.x === 0) {
      velocity = { x: 1, y: 0 };
    } else if (deltaX < -minSwipeDistance && velocity.x === 0) {
      velocity = { x: -1, y: 0 };
    }
  } else {
    if (deltaY > minSwipeDistance && velocity.y === 0) {
      velocity = { x: 0, y: 1 };
    } else if (deltaY < -minSwipeDistance && velocity.y === 0) {
      velocity = { x: 0, y: -1 };
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
          gameInterval = setInterval(moveSnake, 250);
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

// 🎨 Apply role theme on page load
setTimeout(() => {
  applySnakeRoleTheme();
  console.log('🎨 Snake role theme applied on load');
}, 500);

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