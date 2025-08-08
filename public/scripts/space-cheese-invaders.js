// 🧀 Space Cheese Invaders v3.0 - ULTRA SLOW WITH TETRIS BLOCKS
// Much slower invaders (1 second drop, 1 minute break) with Tetris block danger items
// NEW: Auto-shoot feature - automatically fires when ship moves (toggle with 'T' key)

// 🚫 Full page scroll prevention (same as other games)
window.addEventListener("touchmove", function(e) {
  if (e.target.closest("#space-invaders-canvas")) {
    e.preventDefault();
  }
}, { passive: false });

window.addEventListener("keydown", function (e) {
  const keys = ["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight", " ", "a", "s", "d", "w"];
  if (keys.includes(e.key)) {
    e.preventDefault();
    // Don't stop propagation - let the second listener handle the movement
  }
}, { passive: false });

// 🎮 Game Variables (global scope like other games)
let spaceInvadersGameInterval;
let isSpaceInvadersPaused = false;
let spaceInvadersScore = 0;

// 🧀 Load cheese-themed images
const cheeseShipImg = new Image();
cheeseShipImg.src = 'img/space/cheese-ship.png';
cheeseShipImg.onload = () => {
  console.log('✅ Cheese ship image loaded successfully');
};
cheeseShipImg.onerror = (e) => {
  console.error('❌ Failed to load cheese ship image:', e);
  console.error('❌ Attempted path:', cheeseShipImg.src);
};

const cheeseInvaderImg = new Image();
cheeseInvaderImg.src = 'img/space/cheese-invader.png';
cheeseInvaderImg.onload = () => {
  console.log('✅ Cheese invader image loaded successfully');
};
cheeseInvaderImg.onerror = (e) => {
  console.error('❌ Failed to load cheese invader image:', e);
  console.error('❌ Attempted path:', cheeseInvaderImg.src);
};

const cheeseBulletImg = new Image();
cheeseBulletImg.src = 'img/space/cheese-bullet.png';
cheeseBulletImg.onload = () => {
  console.log('✅ Cheese bullet image loaded successfully');
};
cheeseBulletImg.onerror = (e) => {
  console.error('❌ Failed to load cheese bullet image:', e);
  console.error('❌ Attempted path:', cheeseBulletImg.src);
};

const cheeseExplosionImg = new Image();
cheeseExplosionImg.src = 'img/space/cheese-explosion.png';
cheeseExplosionImg.onload = () => {
  console.log('✅ Cheese explosion image loaded successfully');
};
cheeseExplosionImg.onerror = (e) => {
  console.error('❌ Failed to load cheese explosion image:', e);
  console.error('❌ Attempted path:', cheeseExplosionImg.src);
};

  // 🧩 Load Tetris block images for danger items
  const tetrisBlockImages = {
    I: new Image(),
    O: new Image(),
    T: new Image(),
    S: new Image(),
    Z: new Image(),
    J: new Image(),
    L: new Image(),
    BOMB: new Image()
  };

  // Load all Tetris block images
  Object.keys(tetrisBlockImages).forEach(blockType => {
    const img = tetrisBlockImages[blockType];
    img.onload = () => {}; // Removed debug log
    img.onerror = () => {}; // Removed debug log
    img.src = `img/tetris/block_${blockType}.png`;
  });

  // 🐍 Load snake-themed images for dangerous invaders
  const snakeDNAImg = new Image();
  snakeDNAImg.src = 'img/snake/snake-dna.png';
  snakeDNAImg.onload = () => {
    console.log('✅ Snake DNA image loaded');
  };
  snakeDNAImg.onerror = () => {
    console.warn('⚠️ Failed to load snake DNA image');
  };

  const snakeHeadImg = new Image();
  snakeHeadImg.src = 'img/snake/snake-head.png';
  snakeHeadImg.onload = () => {
    console.log('✅ Snake head image loaded');
  };
  snakeHeadImg.onerror = () => {
    console.warn('⚠️ Failed to load snake head image');
  };

// 🎯 Game State - ULTRA SLOW REDESIGN
let playerShip = { x: 0, y: 0 };
let invaders = [];
let bullets = [];
let invaderBullets = [];
let tetrisDangerItems = []; // NEW: Tetris block danger items
let explosions = [];
let gameSpeed = 0.1; // ULTRA SLOW BASE SPEED
let invaderDirection = 1;
let invaderDropTimer = 0;
let waveNumber = 1;
let lastSpawnTime = 0;
let lastTetrisSpawnTime = 0;
let gamePhase = 'formation';
let phaseTimer = 0;
let invaderDropPhase = false; // NEW: Track if invaders are dropping
let dropStartTime = 0; // NEW: Track when drop started
let dropDuration = 1000; // NEW: 1 second drop duration
let breakDuration = 60000; // NEW: 1 minute break duration
let lastPlayerShootTime = 0; // NEW: Track last player shoot time for auto-shoot cooldown
let autoShootCooldown = 300; // NEW: 300ms cooldown between auto-shots
let autoShootEnabled = true; // NEW: Auto-shoot toggle (enabled by default)

// 🎮 Canvas context (global scope)
let ctx;
let canvasWidth;
let canvasHeight;

  // 🧩 NEW: Tetris block danger types with snake invaders and bombs
  const TETRIS_DANGER_TYPES = {
    I_BLOCK: { type: 'I', speed: 1.0, points: -30, size: 20, color: '#00f0f0' },
    O_BLOCK: { type: 'O', speed: 0.8, points: -25, size: 18, color: '#f0f000' },
    T_BLOCK: { type: 'T', speed: 1.2, points: -35, size: 22, color: '#a000f0' },
    S_BLOCK: { type: 'S', speed: 1.1, points: -32, size: 21, color: '#00f000' },
    Z_BLOCK: { type: 'Z', speed: 1.1, points: -32, size: 21, color: '#f00000' },
    J_BLOCK: { type: 'J', speed: 0.9, points: -28, size: 19, color: '#0000f0' },
    L_BLOCK: { type: 'L', speed: 0.9, points: -28, size: 19, color: '#f0a000' },
    BOMB_BLOCK: { type: 'BOMB', speed: 1.5, points: -100, size: 25, color: '#ff0000', bombLevel: 4 },
    SNAKE_DNA: { type: 'SNAKE_DNA', speed: 1.8, points: -150, size: 30, color: '#ffff00', image: 'snakeDNA' },
    SNAKE_HEAD: { type: 'SNAKE_HEAD', speed: 2.0, points: -200, size: 35, color: '#00ff00', image: 'snakeHead' }
  };

  function initSpaceInvaders() {
    console.log('🚀 Initializing Space Invaders...');
    
    // Get canvas and context
    const canvas = document.getElementById('space-invaders-canvas');
    if (!canvas) {
      console.error('❌ Canvas not found - make sure element with id "space-invaders-canvas" exists');
      return;
    }

    ctx = canvas.getContext('2d');
    if (!ctx) {
      console.error('❌ Canvas context not found');
      return;
    }

    console.log('✅ Canvas and context initialized');

    // Set canvas dimensions based on device
    let maxWidth = 400;
    let maxHeight = 600;
    
    if (window.innerWidth < 768) {
      // Mobile device - use smaller canvas
      maxWidth = Math.min(350, window.innerWidth - 40);
      maxHeight = Math.min(500, window.innerHeight - 200);
    }
    
    canvas.width = maxWidth;
    canvas.height = maxHeight;
    canvasWidth = maxWidth;
    canvasHeight = maxHeight;

    console.log(`📏 Canvas dimensions set to: ${canvasWidth}x${canvasHeight}`);

    // Initialize player ship
    playerShip = {
      x: canvasWidth / 2,
      y: canvasHeight - 60,
      width: 40,
      height: 30,
      speed: 5,
      health: 3
    };

    // Initialize game state
    spaceInvadersScore = 0;
    gameSpeed = 0.1;
    waveNumber = 1;
    gamePhase = 'formation';
    phaseTimer = 0;
    invaderDropPhase = false;
    dropStartTime = Date.now();
    invaders = [];
    bullets = [];
    invaderBullets = [];
    tetrisDangerItems = [];
    explosions = [];
    invaderDirection = 1;
    invaderDropTimer = 0;
    lastSpawnTime = Date.now();
    lastTetrisSpawnTime = Date.now();

    console.log('✅ Game state initialized');

    // Initialize invaders
    initializeInvaders();
    
    // Load DSPOINC settings
    loadDspoinSettings();
    
    // Initial draw
    draw();
    
    console.log('✅ Space Invaders initialization complete');
  }

  function initializeInvaders() {
    invaders = [];
    // Start with a simple formation that gradually becomes complex
    const formationPatterns = [
      'v_formation',    // V-shaped formation
      'pyramid',        // Pyramid formation  
      'diamond',        // Diamond formation
      'cross',          // Cross formation
      'spiral',         // Spiral formation
      'random_cluster'  // Random cluster
    ];
    
    const pattern = formationPatterns[Math.floor(Math.random() * formationPatterns.length)];
    createFormation(pattern);
  }

  // 🎯 NEW: Create different formation patterns
  function createFormation(pattern) {
    // Safety check for canvas dimensions
    if (typeof canvasWidth === 'undefined' || typeof canvasHeight === 'undefined') {
      console.warn('⚠️ Canvas dimensions not available, using default values');
      canvasWidth = canvasWidth || 400;
      canvasHeight = canvasHeight || 600;
    }
    
    switch (pattern) {
      case 'v_formation':
        // V-shaped formation - much smaller and slower
        const vPositions = [
          [3, 0], [4, 0], [5, 0],
          [2, 1], [3, 1], [4, 1], [5, 1], [6, 1],
          [1, 2], [2, 2], [3, 2], [4, 2], [5, 2], [6, 2], [7, 2],
          [0, 3], [1, 3], [2, 3], [3, 3], [4, 3], [5, 3], [6, 3], [7, 3], [8, 3]
        ];
        vPositions.forEach(([col, row]) => {
          invaders.push(createInvader(col * 45 + 30, row * 35 + 50, row, 'v_formation'));
        });
        break;
        
      case 'pyramid':
        // Pyramid formation - gradual build-up
        for (let row = 0; row < 4; row++) {
          const colsInRow = row + 1;
          const startCol = 4 - row;
          for (let col = 0; col < colsInRow; col++) {
            invaders.push(createInvader((startCol + col) * 45 + 30, row * 35 + 50, row, 'pyramid'));
          }
        }
        break;
        
      case 'diamond':
        // Diamond formation - more strategic
        const diamondPositions = [
          [4, 0],
          [3, 1], [4, 1], [5, 1],
          [2, 2], [3, 2], [4, 2], [5, 2], [6, 2],
          [3, 3], [4, 3], [5, 3],
          [4, 4]
        ];
        diamondPositions.forEach(([col, row]) => {
          invaders.push(createInvader(col * 45 + 30, row * 35 + 50, row, 'diamond'));
        });
        break;
        
      case 'cross':
        // Cross formation - challenging pattern
        const crossPositions = [
          [4, 0], [4, 1], [4, 2], [4, 3], [4, 4],
          [2, 2], [3, 2], [5, 2], [6, 2]
        ];
        crossPositions.forEach(([col, row]) => {
          invaders.push(createInvader(col * 45 + 30, row * 35 + 50, row, 'cross'));
        });
        break;
        
      case 'spiral':
        // Spiral formation - complex pattern
        const spiralPositions = [
          [4, 0], [5, 0], [6, 0],
          [3, 1], [7, 1],
          [2, 2], [8, 2],
          [1, 3], [9, 3],
          [0, 4], [10, 4]
        ];
        spiralPositions.forEach(([col, row]) => {
          invaders.push(createInvader(col * 40 + 20, row * 35 + 50, row, 'spiral'));
        });
        break;
        
      case 'random_cluster':
        // Random cluster - unpredictable
        for (let i = 0; i < 12; i++) {
          const col = Math.floor(Math.random() * 8);
          const row = Math.floor(Math.random() * 4);
          invaders.push(createInvader(col * 45 + 30, row * 35 + 50, row, 'random_cluster'));
        }
        break;
        
      case 'ultra_swarm':
        // NEW: Ultra dense swarm - much more invaders!
        for (let row = 0; row < 6; row++) {
          for (let col = 0; col < 12; col++) {
            invaders.push(createInvader(col * 35 + 20, row * 30 + 30, row, 'ultra_swarm'));
          }
        }
        // Add extra random invaders
        for (let i = 0; i < 15; i++) {
          const x = Math.random() * (canvasWidth - 60);
          const y = Math.random() * 200;
          invaders.push(createInvader(x, y, Math.floor(Math.random() * 3), 'ultra_swarm_extra'));
        }
        break;
        
      case 'double_formation':
        // NEW: Double formation - two layers of invaders
        // First layer
        for (let row = 0; row < 4; row++) {
          for (let col = 0; col < 8; col++) {
            invaders.push(createInvader(col * 45 + 30, row * 35 + 50, row, 'double_formation_1'));
          }
        }
        // Second layer (offset)
        for (let row = 0; row < 3; row++) {
          for (let col = 0; col < 6; col++) {
            invaders.push(createInvader(col * 45 + 60, row * 35 + 200, row + 4, 'double_formation_2'));
          }
        }
        break;
    }
  }

    // 💰 Load DSPOINC settings from admin panel
  function loadDspoinSettings() {
    fetch('/api/admin/space-invaders-settings.php')
      .then(response => response.json())
      .then(data => {
        if (data.success && data.settings) {
          data.settings.forEach(setting => {
            if (setting.setting_key === 'dspoin_rewards_enabled') {
              localStorage.setItem('space_invaders_dspoin_enabled', setting.setting_value);
            } else if (setting.setting_key === 'dspoin_conversion_rate') {
              localStorage.setItem('space_invaders_conversion_rate', setting.setting_value);
            }
          });
        }
      })
      .catch(error => {
        console.warn('Failed to load DSPOINC settings, using defaults:', error);
        // Set defaults if API fails
        localStorage.setItem('space_invaders_dspoin_enabled', '0'); // OFF by default
        localStorage.setItem('space_invaders_conversion_rate', '10000');
      });
  }

  // 🎮 Start game with countdown (same as Snake)
  function startGameWithCountdown() {
    const countdownEl = document.getElementById("space-invaders-countdown");
    let count = 5;

    if (!countdownEl) {
      console.warn("Countdown element not found.");
      startGame();
      return;
    }

    countdownEl.classList.remove("hidden");

    const countdownInterval = setInterval(() => {
        countdownEl.textContent = count;
      count--;
      
      if (count < 0) {
        clearInterval(countdownInterval);
        countdownEl.classList.add("hidden");
        startGame();
      }
    }, 1000);
  }

  function startGame() {
    resetGame();
    spaceInvadersGameInterval = setInterval(gameLoop, 100); // ULTRA SLOW GAME LOOP (100ms instead of 50ms)
    document.getElementById("start-space-invaders-btn").textContent = "🔄 Restart";
    
    // Lock scroll only when game is actually running
    lockSpaceInvadersScroll();
  }

  function resetGame() {
    // Clear any existing game interval
    if (spaceInvadersGameInterval) {
      clearInterval(spaceInvadersGameInterval);
      spaceInvadersGameInterval = null;
    }
    
    // Unlock scroll when game is reset
    unlockSpaceInvadersScroll();
    
    spaceInvadersScore = 0;
    gameSpeed = 0.1; // ULTRA SLOW STARTING SPEED
    waveNumber = 1;
    gamePhase = 'formation';
    phaseTimer = 0;
    invaderDropPhase = false;
    dropStartTime = Date.now();
    invaders = [];
    bullets = [];
    invaderBullets = [];
    tetrisDangerItems = [];
    explosions = [];
    invaderDirection = 1;
    invaderDropTimer = 0;
    lastSpawnTime = Date.now();
    lastTetrisSpawnTime = Date.now();
    
    playerShip.x = canvasWidth / 2;
    playerShip.health = 3;
    
    initializeInvaders();
    updateScore();
  }

  function gameLoop() {
    if (isSpaceInvadersPaused) return;
    
    updateGame();
    draw();
  }

  function updateGame() {
    // 🎯 NEW: Much faster and more engaging gameplay
    phaseTimer++;
    
    if (gamePhase === 'formation') {
      // Formation phase - much shorter and you can shoot!
      moveInvadersFormation();
      moveBullets();
      moveInvaderBullets();
      moveTetrisDangerItems();
      checkBulletCollisions();
      checkPlayerHit();
      checkTetrisCollisions();
      
      if (phaseTimer > 100) { // 10 seconds at 100ms intervals (was 60 seconds)
        gamePhase = 'attack';
        phaseTimer = 0;
        invaderDropPhase = false;
        dropStartTime = Date.now(); // Reset drop start time for new wave
        console.log(`🎯 Starting attack phase for wave ${waveNumber}`);
      }
    } else if (gamePhase === 'attack') {
      // Attack phase - invaders move and shoot
      moveInvadersUltraSlow();
      moveBullets();
      moveInvaderBullets();
      moveTetrisDangerItems();
      checkBulletCollisions();
      checkPlayerHit();
      checkTetrisCollisions();
      
      // NEW: Check for stuck or hidden invaders
      checkForStuckInvaders();
      
      // Phase transitions - check if only 2 or fewer invaders are left
      const aliveInvaders = invaders.filter(invader => invader.alive);
      
      // NEW: Time-based wave progression to prevent getting stuck
      const currentTime = Date.now();
      const waveTimeLimit = 30000; // 30 seconds per wave
      const timeSinceWaveStart = currentTime - dropStartTime;
      
      // Debug: Log wave status every 10 seconds
      if (phaseTimer % 100 === 0) { // Every 10 seconds
        console.log(`🎯 Wave ${waveNumber} status: ${aliveInvaders.length} invaders alive, ${Math.round(timeSinceWaveStart/1000)}s elapsed`);
      }
      
      if (aliveInvaders.length <= 2 || timeSinceWaveStart > waveTimeLimit) {
        if (aliveInvaders.length <= 2) {
          console.log(`🎯 Wave ${waveNumber} completed! Only ${aliveInvaders.length} invaders left. Spawning wave ${waveNumber + 1}...`);
        } else {
          console.log(`⏰ Wave ${waveNumber} time limit reached (${Math.round(timeSinceWaveStart/1000)}s). Spawning wave ${waveNumber + 1}...`);
        }
        
        gamePhase = 'formation';
        phaseTimer = 0;
        waveNumber++;
        invaderDropPhase = false;
        dropStartTime = Date.now(); // Reset drop start time for new wave
        spawnNewWave();
      }
    }
    
    // Spawn Tetris danger items periodically - much more frequent with snake invaders!
    const currentTime = Date.now();
    const tetrisSpawnInterval = Math.max(1500, 8000 - (waveNumber - 1) * 800); // Much faster spawning
    if (currentTime - lastTetrisSpawnTime > tetrisSpawnInterval) {
      spawnTetrisDangerItem();
      lastTetrisSpawnTime = currentTime;
    }
  }

  // NEW: Function to check for stuck or hidden invaders
  function checkForStuckInvaders() {
    const aliveInvaders = invaders.filter(invader => invader.alive);
    let stuckInvaders = 0;
    
    aliveInvaders.forEach(invader => {
      // Check if invader is completely off-screen or stuck
      const isOffScreen = invader.x < -100 || invader.x > canvasWidth + 100 || 
                         invader.y < -100 || invader.y > canvasHeight + 100;
      
      // Check if invader has been in the same position for too long (stuck)
      const currentTime = Date.now();
      if (!invader.lastMoveTime) {
        invader.lastMoveTime = currentTime;
        invader.lastX = invader.x;
        invader.lastY = invader.y;
      }
      
      const timeSinceLastMove = currentTime - invader.lastMoveTime;
      const hasMoved = Math.abs(invader.x - invader.lastX) > 5 || Math.abs(invader.y - invader.lastY) > 5;
      
      if (isOffScreen || (timeSinceLastMove > 10000 && !hasMoved)) { // 10 seconds without movement
        console.log(`⚠️ Removing stuck/hidden invader at (${Math.round(invader.x)}, ${Math.round(invader.y)})`);
        invader.alive = false;
        stuckInvaders++;
      } else if (hasMoved) {
        invader.lastMoveTime = currentTime;
        invader.lastX = invader.x;
        invader.lastY = invader.y;
      }
    });
    
    if (stuckInvaders > 0) {
      console.log(`🧹 Removed ${stuckInvaders} stuck/hidden invaders`);
    }
  }

  // 🎯 NEW: Formation phase movement - very slow and deliberate
  function moveInvadersFormation() {
    invaders.forEach(invader => {
      if (!invader.alive) return;
      
      // Move very slowly to target position
      const targetX = invader.targetX || invader.x;
      const targetY = invader.targetY || invader.y;
      
      if (Math.abs(invader.x - targetX) > 1) {
        invader.x += (targetX - invader.x) * 0.01; // Ultra slow movement
      }
      if (Math.abs(invader.y - targetY) > 1) {
        invader.y += (targetY - invader.y) * 0.01; // Ultra slow movement
      }
    });
  }

  // 🎯 ULTRA SLOW: Invaders drop for 1 second, then break for 1 minute
  function moveInvadersUltraSlow() {
    const currentTime = Date.now();
    
    // Check if we should start a drop phase
    if (!invaderDropPhase && currentTime - dropStartTime > breakDuration) {
      invaderDropPhase = true;
      dropStartTime = currentTime;
    }
    
    // Check if drop phase should end
    if (invaderDropPhase && currentTime - dropStartTime > dropDuration) {
      invaderDropPhase = false;
      dropStartTime = currentTime;
    }
    
    if (invaderDropPhase) {
      // During drop phase - invaders move down in various dangerous patterns
      invaders.forEach(invader => {
        if (!invader.alive) return;
        
        // Wave-based speed multiplier - gets MUCH faster each wave!
        const waveSpeedMultiplier = 1 + (waveNumber - 1) * 1.2; // 120% faster each wave - EXTREMELY dangerous!
        
        // Different movement patterns based on invader type - more aggressive in later waves
        if (invader.movePattern === 'zigzag') {
          // Zigzag pattern - more dangerous and faster each wave
          invader.x += invaderDirection * gameSpeed * 0.3 * waveSpeedMultiplier;
          invader.y += 1.2 * waveSpeedMultiplier;
          
          // Zigzag movement - more frequent direction changes in later waves
          if (Math.random() < (0.15 + waveNumber * 0.03)) { // More direction changes
            invaderDirection *= -1;
          }
        } else if (invader.movePattern === 'dive') {
          // NEW: Dive pattern - invaders dive straight down at player
          invader.y += 2.0 * waveSpeedMultiplier;
          invader.x += (playerShip.x - invader.x) * 0.02 * waveSpeedMultiplier; // Move towards player
        } else if (invader.movePattern === 'spiral') {
          // NEW: Spiral pattern - invaders move in spiral motion
          const spiralRadius = 30 + waveNumber * 5;
          const spiralSpeed = 0.1 + waveNumber * 0.02;
          invader.spiralOffset += spiralSpeed;
          invader.x += Math.cos(invader.spiralOffset) * spiralRadius * 0.1;
          invader.y += 1.0 * waveSpeedMultiplier;
        } else if (invader.movePattern === 'hover') {
          // NEW: Hover pattern - invaders hover and move side to side
          invader.y += 0.3 * waveSpeedMultiplier;
          invader.x += Math.sin(currentTime * 0.005 + invader.x * 0.01) * 2 * waveSpeedMultiplier;
        } else {
          // Standard movement - straight down with slight horizontal movement
          invader.x += invaderDirection * gameSpeed * 0.15 * waveSpeedMultiplier;
          invader.y += 0.8 * waveSpeedMultiplier;
        }
        
        // Bounce off walls with more aggressive behavior in later waves
        if (invader.x <= 0 || invader.x >= canvasWidth - invader.width) {
          invaderDirection *= -1;
          // In later waves, invaders can bounce back more aggressively
          if (waveNumber >= 3) {
            invader.y += 5; // Drop down when hitting walls
          }
        }
      });
    }
  }
  
  // 👾 Spawn new wave of invaders with variety - ULTRA SLOW
  function spawnNewWave() {
    // waveNumber is already incremented in updateGame, so don't increment here
    gameSpeed += 0.001; // TINY difficulty increase
    
    // Choose a random formation pattern
    const patterns = [
      'v_formation',    // V-shaped formation
      'pyramid',        // Pyramid formation  
      'diamond',        // Diamond formation
      'cross',          // Cross formation
      'spiral',         // Spiral formation
      'random_cluster', // Random cluster
      'ultra_swarm',    // NEW: Ultra dense swarm
      'double_formation' // NEW: Double formation
    ];
    
    const pattern = patterns[Math.floor(Math.random() * patterns.length)];
    createFormation(pattern);
  }

  // 🧩 NEW: Spawn Tetris block danger items with bomb level restrictions
  function spawnTetrisDangerItem() {
    // Get available tetris types based on current wave level
    const availableTypes = Object.keys(TETRIS_DANGER_TYPES).filter(type => {
      const config = TETRIS_DANGER_TYPES[type];
      // Only include bombs if we're at or past the required level
      if (config.bombLevel && waveNumber < config.bombLevel) {
        return false;
      }
      return true;
    });
    
    if (availableTypes.length === 0) return;
    
    const type = availableTypes[Math.floor(Math.random() * availableTypes.length)];
    const tetrisConfig = TETRIS_DANGER_TYPES[type];
    
    // Special bomb spawning logic - bombs are rarer but more dangerous
    if (type === 'BOMB_BLOCK') {
      // Only 20% chance to spawn bombs when eligible
      if (Math.random() > 0.2) {
        // Spawn a regular block instead
        const regularTypes = availableTypes.filter(t => t !== 'BOMB_BLOCK');
        if (regularTypes.length > 0) {
          const regularType = regularTypes[Math.floor(Math.random() * regularTypes.length)];
          const regularConfig = TETRIS_DANGER_TYPES[regularType];
          
          tetrisDangerItems.push({
            x: Math.random() * (canvasWidth - 30),
            y: -30,
            width: regularConfig.size,
            height: regularConfig.size,
            type: regularConfig.type,
            color: regularConfig.color,
            speed: regularConfig.speed,
            points: regularConfig.points,
            rotation: Math.random() * 360
          });
          return;
        }
      }
    }
    
    tetrisDangerItems.push({
      x: Math.random() * (canvasWidth - 30),
      y: -30,
      width: tetrisConfig.size,
      height: tetrisConfig.size,
      type: tetrisConfig.type,
      color: tetrisConfig.color,
      speed: tetrisConfig.speed,
      points: tetrisConfig.points,
      rotation: Math.random() * 360, // Random rotation for visual effect
      isBomb: type === 'BOMB_BLOCK' // Flag for special bomb effects
    });
  }

  // 🧩 NEW: Move Tetris danger items
  function moveTetrisDangerItems() {
    tetrisDangerItems.forEach(item => {
      item.y += item.speed;
      item.rotation += 2; // Rotate as they fall
    });
    
    // Remove items that go off screen
    tetrisDangerItems = tetrisDangerItems.filter(item => item.y < canvasHeight + 30);
  }

  // 🧩 NEW: Check Tetris item collisions with special bomb effects
  function checkTetrisCollisions() {
    tetrisDangerItems.forEach((item, index) => {
      if (checkCollision(playerShip, item)) {
        // Player hit by Tetris block
        spaceInvadersScore += item.points;
        
        // Special bomb effects - bombs do more damage!
        if (item.isBomb) {
          playerShip.health -= 2; // Bombs take 2 health instead of 1
          console.log('💥 BOMB HIT! Double damage!');
          
          // Create bigger explosion for bombs
          explosions.push({
            x: item.x,
            y: item.y,
            size: 50, // Bigger explosion
            timer: 30, // Longer explosion
            isBombExplosion: true // Flag for special bomb explosion
          });
        } else {
          playerShip.health--;
          
          // Create normal explosion effect
          explosions.push({
            x: item.x,
            y: item.y,
            size: 30,
            timer: 20
          });
        }
        
        // Remove Tetris item
        tetrisDangerItems.splice(index, 1);
        
        if (playerShip.health <= 0) {
          onGameOver();
        }
      }
    });
  }

  // 🎯 NEW: Create invader with more variety and dangerous patterns
  function createInvader(x, y, row, formation) {
    const movePatterns = ['normal', 'zigzag', 'dive', 'spiral', 'hover'];
    // More dangerous patterns become more common in later waves
    let patternWeights = [0.3, 0.2, 0.2, 0.15, 0.15]; // Default weights
    
    if (waveNumber >= 3) {
      patternWeights = [0.2, 0.25, 0.25, 0.15, 0.15]; // More zigzag and dive
    }
    if (waveNumber >= 5) {
      patternWeights = [0.1, 0.3, 0.3, 0.15, 0.15]; // Even more aggressive
    }
    if (waveNumber >= 7) {
      patternWeights = [0.05, 0.35, 0.35, 0.15, 0.1]; // Mostly dangerous patterns
    }
    
    // Weighted random selection
    const random = Math.random();
    let cumulativeWeight = 0;
    let selectedPattern = 'normal';
    
    for (let i = 0; i < movePatterns.length; i++) {
      cumulativeWeight += patternWeights[i];
      if (random <= cumulativeWeight) {
        selectedPattern = movePatterns[i];
        break;
      }
    }
    
    return {
      x: x,
      y: y,
      targetX: x, // For formation phase
      targetY: y, // For formation phase
      width: 30,
      height: 25,
      alive: true,
      points: (5 - row) * 15 + (waveNumber - 1) * 10, // More points in later waves
      formation: formation,
      shootTimer: Math.random() * (200 - waveNumber * 20), // Much faster shooting timing
      movePattern: selectedPattern, // More dangerous movement patterns
      spiralOffset: Math.random() * Math.PI * 2, // For spiral movement
      hasWeakPoint: Math.random() < (0.3 + waveNumber * 0.05), // More weak points in later waves
      weakPointType: Math.random() < 0.5 ? 'eye' : 'dna', // Eye or DNA weak point
      weakPointHealth: 2 + Math.floor(waveNumber / 3), // More health in later waves
      weakPointX: x + 15, // Center of invader
      weakPointY: y + 12, // Upper part of invader
      weakPointSize: 6 + Math.floor(waveNumber / 4) // Bigger weak points in later waves
    };
  }

  function moveBullets() {
    bullets.forEach(bullet => {
      bullet.y -= bullet.speed;
    });
    // Allow bullets to travel much further to hit invaders outside screen bounds
    bullets = bullets.filter(bullet => bullet.y > -200); // Allow bullets to go 200px above screen
  }

  function moveInvaderBullets() {
    invaderBullets.forEach(bullet => {
      if (bullet.type === 'targeting' && bullet.targetX) {
        // Targeting bullets move towards player
        const dx = bullet.targetX - bullet.x;
        const dy = canvasHeight - bullet.y;
        const distance = Math.sqrt(dx * dx + dy * dy);
        
        if (distance > 0) {
          bullet.x += (dx / distance) * bullet.speed * 0.3;
          bullet.y += bullet.speed;
        } else {
          bullet.y += bullet.speed;
        }
      } else {
        // Normal bullets go straight down
        bullet.y += bullet.speed;
      }
    });
    invaderBullets = invaderBullets.filter(bullet => bullet.y < canvasHeight + 200); // Allow bullets to go 200px below screen
  }

  function checkBulletCollisions() {
    bullets.forEach((bullet, bulletIndex) => {
      invaders.forEach(invader => {
        // Check if invader is alive and within extended bounds (including far outside screen)
        if (invader.alive && 
            invader.x > -300 && invader.x < canvasWidth + 300 && 
            invader.y > -300 && invader.y < canvasHeight + 300 &&
            checkCollision(bullet, invader)) {
          
          // Check if bullet hit weak point
          const hitWeakPoint = invader.hasWeakPoint && 
            bullet.x >= invader.weakPointX - invader.weakPointSize &&
            bullet.x <= invader.weakPointX + invader.weakPointSize &&
            bullet.y >= invader.weakPointY - invader.weakPointSize &&
            bullet.y <= invader.weakPointY + invader.weakPointSize;
          
          if (hitWeakPoint) {
            // Hit weak point - extra damage
            invader.weakPointHealth--;
            spaceInvadersScore += invader.points * 2; // Double points for weak point hit
            
            // Create special weak point explosion
            explosions.push({
              x: invader.weakPointX,
              y: invader.weakPointY,
              size: 15,
              timer: 20,
              isWeakPointHit: true,
              weakPointType: invader.weakPointType
            });
            
            if (invader.weakPointHealth <= 0) {
              // Weak point destroyed - kill invader
              invader.alive = false;
              spaceInvadersScore += invader.points * 3; // Triple points for destroying weak point
              
              // Create big explosion
              explosions.push({
                x: invader.x + invader.width / 2,
                y: invader.y + invader.height / 2,
                size: 35,
                timer: 25,
                isWeakPointDestroyed: true
              });
            }
          } else {
            // Normal hit - kill invader
            invader.alive = false;
            spaceInvadersScore += invader.points;
            
            // Create normal explosion
            explosions.push({
              x: invader.x + invader.width / 2,
              y: invader.y + invader.height / 2,
              size: 25,
              timer: 15
            });
          }
          
          bullets.splice(bulletIndex, 1);
        }
      });
    });
  }

  // 🎯 ULTRA DANGEROUS: Multiple invaders shoot simultaneously with targetable weak points
  function shootFromRandomInvader() {
    if (invaders.length === 0) return;
    
    const aliveInvaders = invaders.filter(invader => invader.alive);
    if (aliveInvaders.length === 0) return;
    
    // EXTREMELY aggressive shooting - multiple invaders shoot at once!
    const baseShootInterval = Math.max(10, 120 - (waveNumber - 1) * 20); // Much faster shooting
    const shootIntervalVariation = Math.max(5, 80 - (waveNumber - 1) * 8); // Less variation, more consistent
    const simultaneousShooters = Math.min(5, Math.floor(waveNumber / 2) + 2); // More invaders shoot at once
    
    aliveInvaders.forEach(invader => {
      invader.shootTimer--;
      if (invader.shootTimer <= 0) {
        // Multiple bullets per invader for higher waves
        let bulletCount = 1;
        if (waveNumber >= 3) bulletCount = 2; // Double bullets for wave 3+
        if (waveNumber >= 6) bulletCount = 3; // Triple bullets for wave 6+
        if (waveNumber >= 8) bulletCount = 4; // Quadruple bullets for wave 8+
        
        for (let i = 0; i < bulletCount; i++) {
          invaderBullets.push({
            x: invader.x + invader.width / 2 - 4 + (i * 4), // Spread bullets
            y: invader.y + invader.height,
            width: 8,
            height: 16,
            speed: Math.min(12, 4 + (waveNumber - 1) * 1.2), // Much faster bullets
            type: 'normal'
          });
        }
        
        // Add special targeting bullets for higher waves
        if (waveNumber >= 2) {
          invaderBullets.push({
            x: invader.x + invader.width / 2 - 4,
            y: invader.y + invader.height,
            width: 10,
            height: 20,
            speed: Math.min(15, 5 + (waveNumber - 1) * 1.5), // Super fast targeting bullets
            type: 'targeting',
            targetX: playerShip.x + playerShip.width / 2 // Target player position
          });
        }
        
        // NEW: Rapid-fire bursts for very high waves
        if (waveNumber >= 5) {
          setTimeout(() => {
            if (invader.alive) {
              invaderBullets.push({
                x: invader.x + invader.width / 2 - 4,
                y: invader.y + invader.height,
                width: 8,
                height: 16,
                speed: Math.min(10, 3 + (waveNumber - 1) * 0.8),
                type: 'normal'
              });
            }
          }, 200);
        }
        
        invader.shootTimer = Math.random() * shootIntervalVariation + baseShootInterval;
      }
    });
  }

  function checkPlayerHit() {
    invaderBullets.forEach((bullet, index) => {
      if (checkCollision(playerShip, bullet)) {
        playerShip.health--;
        invaderBullets.splice(index, 1);
        
        // Create explosion effect
        explosions.push({
          x: playerShip.x + playerShip.width / 2,
          y: playerShip.y + playerShip.height / 2,
          size: 20,
          timer: 10
        });
        
        if (playerShip.health <= 0) {
          onGameOver();
        }
      }
    });
  }

  function checkCollision(rect1, rect2) {
    return rect1.x < rect2.x + rect2.width &&
           rect1.x + rect1.width > rect2.x &&
           rect1.y < rect2.y + rect2.height &&
           rect1.y + rect1.height > rect2.y;
  }

  function draw() {
    if (!ctx || typeof canvasWidth === 'undefined' || typeof canvasHeight === 'undefined') {
      return; // Don't draw if context or canvas dimensions are not available
    }
    
    ctx.clearRect(0, 0, canvasWidth, canvasHeight);
    
    drawStars();
    drawPlayerShip();
    drawInvaders();
    drawBullets();
    drawInvaderBullets();
    drawTetrisDangerItems(); // NEW: Draw Tetris danger items
    drawExplosions();
    drawScore();
    drawHealth();
    drawPhaseInfo(); // NEW: Show current phase info
  }

  function drawStars() {
    ctx.fillStyle = '#ffffff';
    for (let i = 0; i < 50; i++) {
      const x = (i * 37) % canvasWidth;
      const y = (i * 73) % canvasHeight;
      ctx.fillRect(x, y, 1, 1);
    }
  }

  function drawPlayerShip() {
    // Try to draw cheese ship image first
    if (cheeseShipImg.complete && cheeseShipImg.naturalWidth > 0) {
      ctx.drawImage(cheeseShipImg, playerShip.x, playerShip.y, playerShip.width, playerShip.height);
    } else {
      // Fallback to cheese-themed rectangle
      ctx.fillStyle = '#fbbf24'; // Cheese yellow
      ctx.fillRect(playerShip.x, playerShip.y, playerShip.width, playerShip.height);
      
      // Draw cheese details
      ctx.fillStyle = '#f59e0b'; // Darker yellow
      ctx.fillRect(playerShip.x + 5, playerShip.y + 5, 30, 20);
      ctx.fillStyle = '#d97706'; // Even darker for cheese holes
      ctx.fillRect(playerShip.x + 10, playerShip.y + 10, 20, 10);
      
      // Draw cheese holes
      ctx.fillStyle = '#92400e';
      ctx.beginPath();
      ctx.arc(playerShip.x + 15, playerShip.y + 15, 2, 0, Math.PI * 2);
      ctx.arc(playerShip.x + 25, playerShip.y + 18, 1.5, 0, Math.PI * 2);
      ctx.arc(playerShip.x + 20, playerShip.y + 25, 1, 0, Math.PI * 2);
      ctx.fill();
    }
  }

  function drawInvaders() {
    invaders.forEach(invader => {
      if (!invader.alive) return;
      
      // Try to draw cheese invader image first
      if (cheeseInvaderImg.complete && cheeseInvaderImg.naturalWidth > 0) {
        ctx.drawImage(cheeseInvaderImg, invader.x, invader.y, invader.width, invader.height);
      } else {
        // Debug: Log when falling back to colored rectangles
        if (invader.x === 0 && invader.y === 0) { // Only log once per frame
          console.log('⚠️ Using fallback colored rectangles - cheese invader image not loaded');
          console.log('⚠️ Image complete:', cheeseInvaderImg.complete);
          console.log('⚠️ Image naturalWidth:', cheeseInvaderImg.naturalWidth);
          console.log('⚠️ Image src:', cheeseInvaderImg.src);
        }
        
        // Fallback to cheese-themed invaders with different colors based on formation
        let cheeseColor, detailColor, holeColor;
        
        switch (invader.formation) {
          case 'v_formation':
            cheeseColor = '#fbbf24'; // Bright cheese yellow
            detailColor = '#f59e0b';
            holeColor = '#d97706';
            break;
          case 'pyramid':
            cheeseColor = '#f97316'; // Orange cheese
            detailColor = '#ea580c';
            holeColor = '#c2410c';
            break;
          case 'diamond':
            cheeseColor = '#eab308'; // Golden cheese
            detailColor = '#ca8a04';
            holeColor = '#a16207';
            break;
          case 'cross':
            cheeseColor = '#f59e0b'; // Darker cheese
            detailColor = '#d97706';
            holeColor = '#92400e';
            break;
          case 'spiral':
            cheeseColor = '#fbbf24'; // Classic cheese
            detailColor = '#f59e0b';
            holeColor = '#d97706';
            break;
          case 'ultra_swarm':
            cheeseColor = '#ef4444'; // Red cheese for ultra swarm
            detailColor = '#dc2626';
            holeColor = '#b91c1c';
            break;
          case 'double_formation_1':
            cheeseColor = '#8b5cf6'; // Purple cheese for double formation
            detailColor = '#7c3aed';
            holeColor = '#6d28d9';
            break;
          case 'double_formation_2':
            cheeseColor = '#06b6d4'; // Cyan cheese for second layer
            detailColor = '#0891b2';
            holeColor = '#0e7490';
            break;
          default:
            cheeseColor = '#fbbf24';
            detailColor = '#f59e0b';
            holeColor = '#d97706';
        }
        
        // Draw cheese invader body
        ctx.fillStyle = cheeseColor;
        ctx.fillRect(invader.x, invader.y, invader.width, invader.height);
        
        // Draw cheese details
        ctx.fillStyle = detailColor;
        ctx.fillRect(invader.x + 3, invader.y + 3, invader.width - 6, invader.height - 6);
        
        // Draw cheese holes
        ctx.fillStyle = holeColor;
        ctx.beginPath();
        ctx.arc(invader.x + 8, invader.y + 8, 2, 0, Math.PI * 2);
        ctx.arc(invader.x + 22, invader.y + 8, 1.5, 0, Math.PI * 2);
        ctx.arc(invader.x + 15, invader.y + 18, 1, 0, Math.PI * 2);
        ctx.fill();
      }
      
      // NEW: Draw targetable weak points (eyes/DNA)
      if (invader.hasWeakPoint && invader.weakPointHealth > 0) {
        if (invader.weakPointType === 'eye') {
          // Draw glowing red eye
          ctx.fillStyle = `rgba(255, 0, 0, ${0.8 + Math.sin(Date.now() * 0.01) * 0.2})`; // Pulsing red
          ctx.beginPath();
          ctx.arc(invader.weakPointX, invader.weakPointY, invader.weakPointSize, 0, Math.PI * 2);
          ctx.fill();
          
          // Eye pupil
          ctx.fillStyle = '#000000';
          ctx.beginPath();
          ctx.arc(invader.weakPointX, invader.weakPointY, invader.weakPointSize * 0.6, 0, Math.PI * 2);
          ctx.fill();
          
          // Eye highlight
          ctx.fillStyle = '#ffffff';
          ctx.beginPath();
          ctx.arc(invader.weakPointX - 1, invader.weakPointY - 1, invader.weakPointSize * 0.3, 0, Math.PI * 2);
          ctx.fill();
        } else if (invader.weakPointType === 'dna') {
          // Draw glowing green DNA helix
          ctx.fillStyle = `rgba(0, 255, 0, ${0.8 + Math.sin(Date.now() * 0.01) * 0.2})`; // Pulsing green
          ctx.beginPath();
          ctx.arc(invader.weakPointX, invader.weakPointY, invader.weakPointSize, 0, Math.PI * 2);
          ctx.fill();
          
          // DNA strands
          ctx.strokeStyle = '#ffffff';
          ctx.lineWidth = 2;
          ctx.beginPath();
          ctx.moveTo(invader.weakPointX - 3, invader.weakPointY - 3);
          ctx.lineTo(invader.weakPointX + 3, invader.weakPointY + 3);
          ctx.moveTo(invader.weakPointX - 2, invader.weakPointY + 2);
          ctx.lineTo(invader.weakPointX + 2, invader.weakPointY - 2);
          ctx.stroke();
        }
      }
    });
  }

  function drawBullets() {
    bullets.forEach(bullet => {
      // Try to draw cheese bullet image first
      if (cheeseBulletImg.complete && cheeseBulletImg.naturalWidth > 0) {
        ctx.drawImage(cheeseBulletImg, bullet.x, bullet.y, bullet.width, bullet.height);
      } else {
        // Fallback to cheese-themed bullet
        ctx.fillStyle = '#fbbf24'; // Cheese yellow
      ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
        
        // Add cheese glow effect
        ctx.fillStyle = 'rgba(251, 191, 36, 0.3)';
        ctx.fillRect(bullet.x - 1, bullet.y - 1, bullet.width + 2, bullet.height + 2);
      }
    });
  }

  function drawInvaderBullets() {
    invaderBullets.forEach(bullet => {
      if (bullet.type === 'targeting') {
        // Targeting bullets - purple with trail effect
        ctx.fillStyle = '#8b5cf6'; // Purple
      ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
        
        // Purple glow effect
        ctx.fillStyle = 'rgba(139, 92, 246, 0.4)';
        ctx.fillRect(bullet.x - 2, bullet.y - 2, bullet.width + 4, bullet.height + 4);
        
        // Trail effect
        ctx.fillStyle = 'rgba(139, 92, 246, 0.2)';
        ctx.fillRect(bullet.x, bullet.y - 10, bullet.width, 10);
      } else {
        // Normal red enemy bullets
        ctx.fillStyle = '#ef4444';
        ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
        
        // Add red glow effect
        ctx.fillStyle = 'rgba(239, 68, 68, 0.3)';
        ctx.fillRect(bullet.x - 1, bullet.y - 1, bullet.width + 2, bullet.height + 2);
      }
    });
  }

  // 🧩 NEW: Draw Tetris danger items with snake invaders
  function drawTetrisDangerItems() {
    tetrisDangerItems.forEach(item => {
      // Save context for rotation
      ctx.save();
      ctx.translate(item.x + item.width / 2, item.y + item.height / 2);
      ctx.rotate(item.rotation * Math.PI / 180);
      
      // Check for snake-themed items first
      if (item.type === 'SNAKE_DNA' && snakeDNAImg.complete && snakeDNAImg.naturalWidth > 0) {
        // Draw snake DNA image
        ctx.drawImage(snakeDNAImg, -item.width / 2, -item.height / 2, item.width, item.height);
      } else if (item.type === 'SNAKE_HEAD' && snakeHeadImg.complete && snakeHeadImg.naturalWidth > 0) {
        // Draw snake head image
        ctx.drawImage(snakeHeadImg, -item.width / 2, -item.height / 2, item.width, item.height);
      } else {
        // Try to draw Tetris block image
        const tetrisImg = tetrisBlockImages[item.type];
        if (tetrisImg && tetrisImg.complete && tetrisImg.naturalWidth > 0) {
          ctx.drawImage(tetrisImg, -item.width / 2, -item.height / 2, item.width, item.height);
        } else {
          // Fallback to colored rectangle
          ctx.fillStyle = item.color;
          ctx.fillRect(-item.width / 2, -item.height / 2, item.width, item.height);
          
          // Add details based on type
          if (item.type === 'SNAKE_DNA') {
            // DNA-themed fallback
            ctx.fillStyle = 'rgba(255, 255, 0, 0.8)';
            ctx.fillRect(-item.width / 2 + 3, -item.height / 2 + 3, item.width - 6, 2);
            ctx.fillRect(-item.width / 2 + 3, -item.height / 2 + 8, item.width - 6, 2);
            ctx.fillRect(-item.width / 2 + 3, -item.height / 2 + 13, item.width - 6, 2);
          } else if (item.type === 'SNAKE_HEAD') {
            // Snake head-themed fallback
            ctx.fillStyle = 'rgba(0, 255, 0, 0.8)';
            ctx.beginPath();
            ctx.arc(0, 0, item.width / 2 - 2, 0, Math.PI * 2);
            ctx.fill();
            ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
            ctx.beginPath();
            ctx.arc(-3, -3, 2, 0, Math.PI * 2);
            ctx.arc(3, -3, 2, 0, Math.PI * 2);
            ctx.fill();
          } else {
            // Regular Tetris block details
            ctx.fillStyle = 'rgba(255, 255, 255, 0.3)';
            ctx.fillRect(-item.width / 2 + 2, -item.height / 2 + 2, item.width - 4, 2);
            ctx.fillRect(-item.width / 2 + 2, -item.height / 2 + 2, 2, item.height - 4);
          }
        }
      }
      
      ctx.restore();
    });
  }

  // 💥 NEW: Draw explosions with cheese theme and bomb explosions
  function drawExplosions() {
    explosions.forEach((explosion, index) => {
      const alpha = explosion.timer / (explosion.isBombExplosion ? 30 : 20);
      
      // Special weak point hit effects
      if (explosion.isWeakPointHit) {
        if (explosion.weakPointType === 'eye') {
          // Eye hit explosion - bright red flash
          ctx.fillStyle = `rgba(255, 0, 0, ${alpha})`; // Bright red
          ctx.beginPath();
          ctx.arc(explosion.x, explosion.y, explosion.size * alpha, 0, Math.PI * 2);
          ctx.fill();
          
          // Eye particles
          for (let i = 0; i < 6; i++) {
            const angle = (i / 6) * Math.PI * 2;
            const distance = explosion.size * alpha * 0.7;
            const particleX = explosion.x + Math.cos(angle) * distance;
            const particleY = explosion.y + Math.sin(angle) * distance;
            
            ctx.fillStyle = `rgba(255, 255, 255, ${alpha * 0.8})`; // White particles
            ctx.beginPath();
            ctx.arc(particleX, particleY, 2 * alpha, 0, Math.PI * 2);
            ctx.fill();
          }
        } else if (explosion.weakPointType === 'dna') {
          // DNA hit explosion - green helix effect
          ctx.fillStyle = `rgba(0, 255, 0, ${alpha})`; // Bright green
          ctx.beginPath();
          ctx.arc(explosion.x, explosion.y, explosion.size * alpha, 0, Math.PI * 2);
          ctx.fill();
          
          // DNA helix particles
          for (let i = 0; i < 8; i++) {
            const angle = (i / 8) * Math.PI * 2;
            const distance = explosion.size * alpha * 0.8;
            const particleX = explosion.x + Math.cos(angle) * distance;
            const particleY = explosion.y + Math.sin(angle) * distance;
            
            ctx.fillStyle = `rgba(0, 255, 255, ${alpha * 0.6})`; // Cyan particles
            ctx.beginPath();
            ctx.arc(particleX, particleY, 3 * alpha, 0, Math.PI * 2);
            ctx.fill();
          }
        }
      } else if (explosion.isWeakPointDestroyed) {
        // Weak point destroyed - massive explosion
        ctx.fillStyle = `rgba(255, 255, 0, ${alpha})`; // Bright yellow
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha, 0, Math.PI * 2);
        ctx.fill();
        
        // Multiple colored particles
        for (let i = 0; i < 15; i++) {
          const angle = (i / 15) * Math.PI * 2;
          const distance = explosion.size * alpha * 0.9;
          const particleX = explosion.x + Math.cos(angle) * distance;
          const particleY = explosion.y + Math.sin(angle) * distance;
          
          const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff'];
          ctx.fillStyle = colors[i % colors.length] + Math.floor(alpha * 255).toString(16).padStart(2, '0');
          ctx.beginPath();
          ctx.arc(particleX, particleY, 4 * alpha, 0, Math.PI * 2);
          ctx.fill();
        }
      } else if (explosion.isBombExplosion) {
        // Special bomb explosion effects
        ctx.fillStyle = `rgba(255, 0, 0, ${alpha})`; // Red
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha, 0, Math.PI * 2);
        ctx.fill();
        
        // Inner bomb explosion
        ctx.fillStyle = `rgba(255, 165, 0, ${alpha * 0.8})`; // Orange
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha * 0.7, 0, Math.PI * 2);
        ctx.fill();
        
        // Bomb particles - more intense
        for (let i = 0; i < 12; i++) {
          const angle = (i / 12) * Math.PI * 2;
          const distance = explosion.size * alpha * 0.9;
          const particleX = explosion.x + Math.cos(angle) * distance;
          const particleY = explosion.y + Math.sin(angle) * distance;
          
          ctx.fillStyle = `rgba(255, 69, 0, ${alpha * 0.7})`; // Red-orange
          ctx.beginPath();
          ctx.arc(particleX, particleY, 3 * alpha, 0, Math.PI * 2);
          ctx.fill();
        }
      } else {
        // Try to draw cheese explosion image first
        if (cheeseExplosionImg.complete && cheeseExplosionImg.naturalWidth > 0) {
          ctx.globalAlpha = alpha;
          ctx.drawImage(cheeseExplosionImg, 
            explosion.x - explosion.size, 
            explosion.y - explosion.size, 
            explosion.size * 2, 
            explosion.size * 2
          );
          ctx.globalAlpha = 1;
        } else {
          // Fallback to cheese-themed explosion
          ctx.fillStyle = `rgba(251, 191, 36, ${alpha})`; // Cheese yellow
          ctx.beginPath();
          ctx.arc(explosion.x, explosion.y, explosion.size * alpha, 0, Math.PI * 2);
          ctx.fill();
          
          // Inner explosion
          ctx.fillStyle = `rgba(245, 158, 11, ${alpha * 0.7})`; // Darker cheese
          ctx.beginPath();
          ctx.arc(explosion.x, explosion.y, explosion.size * alpha * 0.6, 0, Math.PI * 2);
          ctx.fill();
          
          // Cheese particles
          for (let i = 0; i < 8; i++) {
            const angle = (i / 8) * Math.PI * 2;
            const distance = explosion.size * alpha * 0.8;
            const particleX = explosion.x + Math.cos(angle) * distance;
            const particleY = explosion.y + Math.sin(angle) * distance;
            
            ctx.fillStyle = `rgba(217, 119, 6, ${alpha * 0.5})`; // Cheese hole color
            ctx.beginPath();
            ctx.arc(particleX, particleY, 2 * alpha, 0, Math.PI * 2);
            ctx.fill();
          }
        }
      }
      
      explosion.timer--;
      if (explosion.timer <= 0) {
        explosions.splice(index, 1);
      }
    });
  }

  function drawScore() {
    const scoreDisplay = document.getElementById("space-invaders-score");
    if (scoreDisplay) {
      // Space Invaders scoring: 1,000 invaders = 10 DSPOINC
      const conversionRate = 1000; // 1,000 invaders = 10 DSPOINC
      const dspoinEarned = Math.floor(spaceInvadersScore / conversionRate);
      scoreDisplay.textContent = `💰 Space Invaders Score: ${spaceInvadersScore.toLocaleString()} invaders (${dspoinEarned} DSPOINC)`;
    }
  }

  // ❤️ NEW: Draw health display
  function drawHealth() {
    ctx.fillStyle = '#ff0000';
    ctx.font = '16px Arial';
    ctx.fillText(`Health: ${'❤'.repeat(playerShip.health)}`, 10, 30);
  }

  // 📊 NEW: Draw phase information with bomb status
  function drawPhaseInfo() {
    if (!ctx || typeof canvasHeight === 'undefined') {
      return; // Don't draw if context or canvas height is not available
    }
    
    ctx.fillStyle = '#ffffff';
    ctx.font = '14px Arial';
    
    if (gamePhase === 'formation') {
      ctx.fillStyle = '#4ade80'; // Green for formation
      ctx.fillText(`🎯 FORMATION PHASE (10s) - SHOOT! - WAVE ${waveNumber}`, 10, 50);
    } else if (gamePhase === 'attack') {
      if (invaderDropPhase) {
        ctx.fillStyle = '#ff6b6b';
        ctx.fillText(`🚀 DROP PHASE (1 second) - WAVE ${waveNumber}`, 10, 50);
      } else {
        ctx.fillStyle = '#4ecdc4';
        ctx.fillText(`⏸️ BREAK PHASE (1 minute) - WAVE ${waveNumber}`, 10, 50);
      }
    }
    
    // Show auto-shoot status
    ctx.fillStyle = autoShootEnabled ? '#4ade80' : '#ff6b6b';
    ctx.fillText(`🎯 AUTO-SHOOT: ${autoShootEnabled ? 'ON' : 'OFF'}`, 10, 90);
    
    // Show difficulty level and danger indicators
    if (waveNumber >= 3) {
      ctx.fillStyle = '#ffaa00'; // Orange for medium difficulty
      ctx.fillText(`⚠️ DIFFICULTY: MEDIUM - Faster invaders!`, 10, 110);
    }
    if (waveNumber >= 5) {
      ctx.fillStyle = '#ff6600'; // Dark orange for high difficulty
      ctx.fillText(`🔥 DIFFICULTY: HIGH - Multiple bullets!`, 10, 110);
    }
    if (waveNumber >= 7) {
      ctx.fillStyle = '#ff0000'; // Red for extreme difficulty
      ctx.fillText(`💀 DIFFICULTY: EXTREME - Rapid fire!`, 10, 110);
    }
    if (waveNumber >= 10) {
      ctx.fillStyle = '#ff00ff'; // Magenta for insane difficulty
      ctx.fillText(`👹 DIFFICULTY: INSANE - Maximum chaos!`, 10, 110);
    }
    
    // Show bomb status if we're at level 4 or higher
    if (waveNumber >= 4) {
      ctx.fillStyle = '#ff4444'; // Red for bombs
      ctx.fillText(`💣 BOMBS ACTIVE! - Level ${waveNumber}`, 10, 130);
    }
    
    // Show controls help
    ctx.fillStyle = '#ffffff';
    ctx.font = '12px Arial';
    ctx.fillText(`Controls: WASD/Arrows to move (up/down/left/right), Space to shoot, T to toggle auto-shoot`, 10, canvasHeight - 20);
  }

  function updateScore() {
    const scoreDisplay = document.getElementById("space-invaders-score");
    if (scoreDisplay) {
      // Space Invaders scoring: 1,000 invaders = 10 DSPOINC
      const conversionRate = 1000; // 1,000 invaders = 10 DSPOINC
      const dspoinEarned = Math.floor(spaceInvadersScore / conversionRate);
      scoreDisplay.textContent = `💰 Space Invaders Score: ${spaceInvadersScore.toLocaleString()} invaders (${dspoinEarned} DSPOINC)`;
    } else {
      console.warn('⚠️ Score display element not found');
    }
  }

  function onGameWin() {
    clearInterval(spaceInvadersGameInterval);
    
    const winModal = document.getElementById("space-invaders-win-modal");
    const winScoreText = document.getElementById("space-invaders-win-score-text");
    
    // Space Invaders scoring: 1,000 invaders = 10 DSPOINC
    const conversionRate = 1000;
    const dspoinEarned = Math.floor(spaceInvadersScore / conversionRate);
    
    if (winModal && winScoreText) {
      winScoreText.textContent = `You earned ${dspoinEarned} DSPOINC! (${spaceInvadersScore.toLocaleString()} invaders destroyed)`;
      winModal.classList.remove("hidden");
    }
    
    // Save score to database
    saveScore(spaceInvadersScore);
    cleanupSpaceInvadersControls();

    // Dispatch game end event for UI reset
    window.dispatchEvent(new Event('spaceInvadersGameEnd'));
  }

  function onGameOver() {
    clearInterval(spaceInvadersGameInterval);
    
    const gameOverModal = document.getElementById("space-invaders-over-modal");
    const finalScoreText = document.getElementById("space-invaders-final-score-text");
    
    // Space Invaders scoring: 1,000 invaders = 10 DSPOINC
    const conversionRate = 1000;
    const dspoinEarned = Math.floor(spaceInvadersScore / conversionRate);
    
    if (gameOverModal && finalScoreText) {
      finalScoreText.textContent = `You earned ${dspoinEarned} DSPOINC! (${spaceInvadersScore.toLocaleString()} invaders destroyed)`;
      gameOverModal.classList.remove("hidden");
    }
    
    // Save score to database
    saveScore(spaceInvadersScore);
    cleanupSpaceInvadersControls();

    // Dispatch game end event for UI reset
    window.dispatchEvent(new Event('spaceInvadersGameEnd'));
  }

  function saveScore(finalScore) {
    const discordId = localStorage.getItem('discord_id');
    const discordName = localStorage.getItem('discord_name') || 'Unknown Player';
    const wallet = localStorage.getItem('user_wallet') || discordId;
    
    if (!discordId) {
      console.error('No Discord ID found for score saving');
      return;
    }

    // Space Invaders scoring: 1,000 invaders = 10 DSPOINC
    const conversionRate = 1000;
    const dspoincScore = Math.floor(finalScore / conversionRate) * 10; // Convert to DSPOINC (1,000 invaders = 10 DSPOINC)

    console.log(`💾 Saving Space Invaders score: ${finalScore} invaders = ${dspoincScore} DSPOINC`);

    // Save to the same API endpoint as Tetris and Snake
    fetch('/api/dev/save-score.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        wallet: wallet,
        score: finalScore, // Raw score (number of invaders destroyed)
        discord_id: discordId,
        discord_name: discordName,
        game: 'space_invaders'
      })
    })
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      // Check if response is JSON
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        throw new Error('Response is not JSON - server may be returning HTML error page');
      }
      return response.json();
    })
    .then(data => {
      if (data.success) {
        console.log(`✅ Space Invaders score saved successfully: ${finalScore} invaders = ${dspoincScore} DSPOINC`);
      } else {
        if (data.local_test) {
          console.log(`🔄 Local testing detected - score would be saved in production: ${finalScore} invaders = ${dspoincScore} DSPOINC`);
        } else {
          console.error('❌ Failed to save Space Invaders score:', data.error || 'Unknown error');
        }
      }
    })
    .catch(error => {
      console.error('❌ Error saving Space Invaders score:', error.message);
      // Check if this is a local testing issue
      if (error.message.includes('HTML') || error.message.includes('fetch')) {
        console.log('🔄 Local testing detected - score saving disabled for local development');
        console.log(`📊 Score would be saved in production: ${finalScore} invaders = ${dspoincScore} DSPOINC`);
      }
    });
  }

  function movePlayer(direction) {
    if (isSpaceInvadersPaused) return;
    
    const moveAmount = playerShip.speed;
    const oldX = playerShip.x;
    const oldY = playerShip.y;
    
    switch (direction) {
      case 'left':
        playerShip.x = Math.max(0, playerShip.x - moveAmount);
        break;
      case 'right':
        playerShip.x = Math.min(canvasWidth - playerShip.width, playerShip.x + moveAmount);
        break;
      case 'up':
        playerShip.y = Math.max(0, playerShip.y - moveAmount);
        break;
      case 'down':
        playerShip.y = Math.min(canvasHeight - playerShip.height, playerShip.y + moveAmount);
        break;
    }
    
    // Auto-shoot when ship moves (with cooldown) - only if enabled
    if (autoShootEnabled && (oldX !== playerShip.x || oldY !== playerShip.y)) {
      const currentTime = Date.now();
      if (currentTime - lastPlayerShootTime > autoShootCooldown) {
        playerShoot();
        lastPlayerShootTime = currentTime;
      }
    }
  }

  function playerShoot() {
    if (isSpaceInvadersPaused) return;
    
    bullets.push({
      x: playerShip.x + playerShip.width / 2 - 4, // Adjusted for larger bullet
      y: playerShip.y,
      width: 8, // Doubled from 4
      height: 16, // Doubled from 8
      speed: 6 // Slower bullets
    });
  }

  // 🎮 Touch controls (same as other games)
  function enableGlobalSpaceInvadersTouch() {
    document.addEventListener('touchstart', handleTouchStart, { passive: false });
    document.addEventListener('touchmove', handleTouchMove, { passive: false });
    document.addEventListener('touchend', handleTouchEnd, { passive: false });
  }

  function disableGlobalSpaceInvadersTouch() {
    document.removeEventListener('touchstart', handleTouchStart);
    document.removeEventListener('touchmove', handleTouchMove);
    document.removeEventListener('touchend', handleTouchEnd);
  }

  let touchStartX = 0;
  let touchStartY = 0;
  let touchStartTime = 0;
  let isTouching = false;

  function handleTouchStart(e) {
    if (e.target.closest("#space-invaders-canvas")) {
    e.preventDefault();
    const touch = e.touches[0];
    touchStartX = touch.clientX;
    touchStartY = touch.clientY;
      isTouching = true;
      
      // Store touch start time for tap detection
      touchStartTime = Date.now();
    }
  }

  function handleTouchMove(e) {
    if (isTouching && e.target.closest("#space-invaders-canvas")) {
      e.preventDefault();
      const touch = e.touches[0];
      const deltaX = touch.clientX - touchStartX;
      const deltaY = touch.clientY - touchStartY;
      
      // Horizontal movement - more sensitive for mobile (like Tetris)
      if (Math.abs(deltaX) > 10) {
        movePlayer(deltaX > 0 ? 'right' : 'left');
        touchStartX = touch.clientX;
        // Auto-shoot is handled in movePlayer function
      }
      
      // Vertical movement - more sensitive for mobile
      if (Math.abs(deltaY) > 10) {
        movePlayer(deltaY > 0 ? 'down' : 'up');
        touchStartY = touch.clientY;
        // Auto-shoot is handled in movePlayer function
      }
      
      // Shooting - more reliable swipe up detection (like Snake)
      if (deltaY < -20) {
        playerShoot();
        // Reset touch to prevent multiple shots
        touchStartY = touch.clientY;
      }
    }
  }

  function handleTouchEnd(e) {
    // Check for tap-to-shoot (quick tap without movement) - like Tetris
    if (isTouching) {
      const touchDuration = Date.now() - touchStartTime;
      const touch = e.changedTouches[0];
      const deltaX = Math.abs(touch.clientX - touchStartX);
      const deltaY = Math.abs(touch.clientY - touchStartY);
      
      // If it's a quick tap (less than 150ms) with minimal movement (less than 8px)
      if (touchDuration < 150 && deltaX < 8 && deltaY < 8) {
        playerShoot();
      }
    }
    
    isTouching = false;
  }

  function lockSpaceInvadersScroll() {
    // Mobile-friendly scroll lock (like Tetris and Snake)
    // Modified to not cut off bottom content
    
    // Prevent scroll on body and html
    document.body.style.overflow = 'hidden';
    document.body.style.touchAction = 'none';
    
    // Also lock scroll on html element for better mobile support
    document.documentElement.style.overflow = 'hidden';
    document.documentElement.style.touchAction = 'none';
    
    // Store current scroll position to prevent jumping
    if (!window.spaceInvadersScrollPosition) {
      window.spaceInvadersScrollPosition = window.pageYOffset;
    }
    
    // Scroll to the game container to ensure it's visible
    const gameContainer = document.getElementById('space-cheese-invaders');
    if (gameContainer) {
      const rect = gameContainer.getBoundingClientRect();
      const offset = rect.top + window.pageYOffset - 20; // 20px offset from top
      window.scrollTo(0, offset);
    } else {
      // Fallback to stored position
      window.scrollTo(0, window.spaceInvadersScrollPosition);
    }
  }

  function unlockSpaceInvadersScroll() {
    // Restore scroll for mobile devices (like Tetris and Snake)
    
    // Restore body scroll
    document.body.style.overflow = '';
    document.body.style.touchAction = '';
    
    // Also restore scroll on html element
    document.documentElement.style.overflow = '';
    document.documentElement.style.touchAction = '';
    
    // Restore scroll position if it was stored
    if (window.spaceInvadersScrollPosition !== undefined) {
      window.scrollTo(0, window.spaceInvadersScrollPosition);
      delete window.spaceInvadersScrollPosition;
    }
  }



  function togglePause() {
    isSpaceInvadersPaused = !isSpaceInvadersPaused;
    const pauseBtn = document.getElementById("pause-space-invaders-btn");
    if (pauseBtn) {
      pauseBtn.textContent = isSpaceInvadersPaused ? "▶️ Resume" : "⏸️ Pause";
    }
    
    // Unlock scroll when paused, lock when resumed
    if (isSpaceInvadersPaused) {
      unlockSpaceInvadersScroll();
    } else {
      lockSpaceInvadersScroll();
    }
  }

  function toggleAutoShoot() {
    autoShootEnabled = !autoShootEnabled;
    console.log(`🎯 Auto-shoot ${autoShootEnabled ? 'enabled' : 'disabled'}`);
  }

  // 🎮 Combined keyboard event listener for Space Invaders movement
  document.addEventListener('keydown', (e) => {
    // Handle pause first
    if (e.key === 'p' || e.key === 'P') {
      if (typeof window.togglePause === 'function') {
        window.togglePause();
      }
      return;
    }
    
    // Handle auto-shoot toggle
    if (e.key === 't' || e.key === 'T') {
      if (typeof window.toggleAutoShoot === 'function') {
        window.toggleAutoShoot();
      }
      return;
    }
    
    // If paused, don't handle other keys
    if (typeof isSpaceInvadersPaused !== 'undefined' && isSpaceInvadersPaused) return;
    
    // Handle movement and shooting
    switch (e.key) {
      case 'ArrowLeft':
      case 'a':
      case 'A':
        if (typeof window.movePlayer === 'function') {
          window.movePlayer('left');
        }
        break;
      case 'ArrowRight':
      case 'd':
      case 'D':
        if (typeof window.movePlayer === 'function') {
          window.movePlayer('right');
        }
        break;
      case 'ArrowUp':
      case 'w':
      case 'W':
        if (typeof window.movePlayer === 'function') {
          window.movePlayer('up');
        }
        break;
      case 'ArrowDown':
      case 's':
      case 'S':
        if (typeof window.movePlayer === 'function') {
          window.movePlayer('down');
        }
        break;
      case ' ':
        if (typeof window.playerShoot === 'function') {
          window.playerShoot();
        }
        break;
    }
  });

  // 🎮 Button event listeners
  const startBtn = document.getElementById("start-space-invaders-btn");
  const pauseBtn = document.getElementById("pause-space-invaders-btn");

  if (startBtn) {
    startBtn.addEventListener("click", startGameWithCountdown);
  }

  if (pauseBtn) {
    pauseBtn.addEventListener("click", togglePause);
  }

  // 🎮 Mobile controls setup
  const mobileControls = document.getElementById("mobile-controls");
  const mobileLeftBtn = document.getElementById("mobile-left-btn");
  const mobileRightBtn = document.getElementById("mobile-right-btn");
  const mobileUpBtn = document.getElementById("mobile-up-btn");
  const mobileDownBtn = document.getElementById("mobile-down-btn");
  const mobileShootBtn = document.getElementById("mobile-shoot-btn");
  const mobileAutoShootBtn = document.getElementById("mobile-auto-shoot-btn");

  // Show mobile controls on mobile devices
  if (mobileControls && window.innerWidth <= 768) {
    mobileControls.classList.remove("hidden");
  }

  // Mobile button event listeners
  if (mobileLeftBtn) {
    mobileLeftBtn.addEventListener("click", () => {
      if (!isSpaceInvadersPaused) movePlayer('left');
    });
  }

  if (mobileRightBtn) {
    mobileRightBtn.addEventListener("click", () => {
      if (!isSpaceInvadersPaused) movePlayer('right');
    });
  }

  if (mobileUpBtn) {
    mobileUpBtn.addEventListener("click", () => {
      if (!isSpaceInvadersPaused) movePlayer('up');
    });
  }

  if (mobileDownBtn) {
    mobileDownBtn.addEventListener("click", () => {
      if (!isSpaceInvadersPaused) movePlayer('down');
    });
  }

  if (mobileShootBtn) {
    mobileShootBtn.addEventListener("click", () => {
      if (!isSpaceInvadersPaused) playerShoot();
    });
  }

  if (mobileAutoShootBtn) {
    mobileAutoShootBtn.addEventListener("click", () => {
      toggleAutoShoot();
      // Update button text
      mobileAutoShootBtn.textContent = autoShootEnabled ? "🎯 Auto: ON" : "🎯 Auto: OFF";
    });
  }

  // 🎮 Touch controls setup
  enableGlobalSpaceInvadersTouch();
  // Don't lock scroll immediately - only lock when game starts

  // 🧹 Cleanup function
  function cleanupSpaceInvadersControls() {
    // Note: We can't easily remove the specific keydown listener since it's anonymous
    // The browser will clean it up when the page is unloaded
    disableGlobalSpaceInvadersTouch();
    unlockSpaceInvadersScroll();
  }

  // 🎮 Make game functions globally available
  window.startGameWithCountdown = startGameWithCountdown;
  window.startGame = startGame;
  window.movePlayer = movePlayer;
  window.playerShoot = playerShoot;
  window.togglePause = togglePause;
  window.toggleAutoShoot = toggleAutoShoot;

// 🎮 Make initSpaceInvaders globally available
window.initSpaceInvaders = initSpaceInvaders;

// 🎮 Auto-initialize when DOM is loaded
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    // Wait a bit for all elements to be available
    setTimeout(() => {
      if (document.getElementById('space-invaders-canvas')) {
        console.log('🎮 Auto-initializing Space Invaders from DOMContentLoaded');
        initSpaceInvaders();
      } else {
        console.warn('⚠️ Canvas not found during auto-initialization');
      }
    }, 100);
  });
} else {
  // DOM is already loaded
  setTimeout(() => {
    if (document.getElementById('space-invaders-canvas')) {
      console.log('🎮 Auto-initializing Space Invaders (DOM already loaded)');
      initSpaceInvaders();
    } else {
      console.warn('⚠️ Canvas not found during auto-initialization');
    }
  }, 100);
}

// 🎮 Also expose the function immediately for manual calls
console.log('🎮 Space Invaders script loaded - initSpaceInvaders available as window.initSpaceInvaders');

// 🧪 Test function to check if everything is working
window.testSpaceInvaders = function() {
  console.log('🧪 Testing Space Invaders...');
  const canvas = document.getElementById('space-invaders-canvas');
  if (canvas) {
    console.log('✅ Canvas found:', canvas);
    console.log('✅ Canvas dimensions:', canvas.width, 'x', canvas.height);
    if (window.initSpaceInvaders) {
      console.log('✅ initSpaceInvaders function available');
      return true;
    } else {
      console.error('❌ initSpaceInvaders function not available');
      return false;
    }
  } else {
    console.error('❌ Canvas not found');
    return false;
  }
};
