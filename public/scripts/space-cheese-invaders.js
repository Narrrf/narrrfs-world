// 🧀 Space Cheese Invaders v3.0 - ULTRA SLOW WITH TETRIS BLOCKS
// Much slower invaders (1 second drop, 1 minute break) with Tetris block danger items
// NEW: Auto-shoot feature - automatically fires when ship moves (toggle with 'T' key)
// NEW: Laser shot type, Speed boost power-up, and Bomb weapon

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
  let autoShootEnabled = false; // NEW: Auto-shoot toggle (disabled by default)

// 🚀 NEW: Enhanced weapon system variables
let currentWeaponType = 'normal'; // 'normal', 'laser', 'bomb'
let weaponCooldowns = {
  normal: 0,
  laser: 0,
  bomb: 0
};
let weaponAmmo = {
  normal: Infinity,
  laser: 5, // Limited laser ammo
  bomb: 3   // Limited bomb ammo
};
let speedBoostActive = false;
let speedBoostTimer = 0;
let speedBoostMultiplier = 2.0; // 2x speed when active
// 🚀 NEW: Limited speed boost ammo
let speedBoostAmmo = 2; // Limited speed boost uses

// 🆘 NEW: Help system variables
let helpOverlayVisible = false;
let mobileControlsVisible = true; // Show mobile controls by default on mobile

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

  // 🚀 NEW: Weapon switching system
  function switchWeapon(weaponType) {
    if (weaponAmmo[weaponType] > 0 || weaponType === 'normal') {
      currentWeaponType = weaponType;
      console.log(`🔫 Switched to ${weaponType} weapon`);
      
      // Update weapon display
      updateWeaponDisplay();
      
      // 🚀 NEW: Update reload button immediately when weapon changes
      if (reloadButton) {
        updateReloadButton();
      }
    } else {
      console.log(`❌ No ammo for ${weaponType} weapon`);
    }
  }

  // 🚀 NEW: Speed boost power-up
  function activateSpeedBoost() {
    if (!speedBoostActive && speedBoostAmmo > 0) {
      speedBoostActive = true;
      speedBoostTimer = 500; // 5 seconds at 100ms intervals
      speedBoostAmmo--; // Use one speed boost
      console.log('⚡ Speed boost activated! Speed boosts remaining:', speedBoostAmmo);
    } else if (speedBoostActive && speedBoostAmmo > 0) {
      // Extend existing speed boost if you have ammo
      speedBoostTimer = Math.min(speedBoostTimer + 200, 800); // Max 8 seconds
      speedBoostAmmo--; // Use one speed boost
      console.log('⚡ Speed boost extended! Speed boosts remaining:', speedBoostAmmo);
    } else if (speedBoostAmmo <= 0) {
      console.log('❌ No speed boost ammo available!');
    }
  }

  // 🚀 NEW: Update speed boost
  function updateSpeedBoost() {
    if (speedBoostActive) {
      speedBoostTimer--;
      if (speedBoostTimer <= 0) {
        speedBoostActive = false;
        console.log('⚡ Speed boost expired');
      }
    }
  }

  // 🚀 NEW: Get current player speed
  function getPlayerSpeed() {
    return speedBoostActive ? playerShip.speed * speedBoostMultiplier : playerShip.speed;
  }

  // 🚀 NEW: Update weapon display
  function updateWeaponDisplay() {
    const weaponDisplay = document.getElementById("weapon-display");
    if (weaponDisplay) {
      let displayText = `🔫 Weapon: ${currentWeaponType.toUpperCase()}`;
      
      if (currentWeaponType === 'laser') {
        displayText += ` (${weaponAmmo.laser} ammo)`;
      } else if (currentWeaponType === 'bomb') {
        displayText += ` (${weaponAmmo.bomb} ammo)`;
      }
      
      weaponDisplay.textContent = displayText;
    }
  }

  // 🚀 NEW: Spawn power-ups randomly
  function spawnPowerUp() {
    // Check if we already have too many power-ups on screen
    if (window.powerUps && window.powerUps.length >= 4) {
      return; // Don't spawn if we already have 4 or more (increased from 3)
    }
    
    // 🚀 NEW: Much more aggressive scaling for higher waves
    let spawnChance = 0.001; // Base rate for early waves
    
    // Progressive scaling that ACTUALLY helps in higher waves
    if (waveNumber >= 5) spawnChance = 0.005;   // 0.5% for wave 5+
    if (waveNumber >= 10) spawnChance = 0.012;  // 1.2% for wave 10+
    if (waveNumber >= 15) spawnChance = 0.025;  // 2.5% for wave 15+
    if (waveNumber >= 18) spawnChance = 0.040;  // 4.0% for wave 18+ (where you are!)
    if (waveNumber >= 20) spawnChance = 0.060;  // 6.0% for wave 20+
    if (waveNumber >= 25) spawnChance = 0.085;  // 8.5% for wave 25+
    if (waveNumber >= 30) spawnChance = 0.120;  // 12.0% for wave 30+
    if (waveNumber >= 35) spawnChance = 0.160;  // 16.0% for wave 35+
    
    if (Math.random() < spawnChance) {
      const powerUpType = Math.random() < 0.5 ? 'speed' : 'ammo';
      
      if (powerUpType === 'speed') {
        // Speed boost power-up
        const powerUp = {
          x: Math.random() * (canvasWidth - 20),
          y: -20,
          width: 20,
          height: 20,
          type: 'speed',
          color: '#00ff00',
          speed: 2,
          collected: false
        };
        
        // Add to game objects (we'll need to create a powerUps array)
        if (!window.powerUps) window.powerUps = [];
        window.powerUps.push(powerUp);
      } else {
        // Ammo power-up
        const ammoType = Math.random() < 0.5 ? 'laser' : 'bomb';
        const powerUp = {
          x: Math.random() * (canvasWidth - 20),
          y: -20,
          width: 20,
          height: 20,
          type: 'ammo',
          ammoType: ammoType,
          color: ammoType === 'laser' ? '#00ffff' : '#ff00ff',
          speed: 2,
          collected: false
        };
        
        if (!window.powerUps) window.powerUps.push(powerUp);
      }
    }
  }

  // 🚀 NEW: Move and check power-up collisions
  function updatePowerUps() {
    if (!window.powerUps) return;
    
    window.powerUps.forEach((powerUp, index) => {
      if (powerUp.collected) return;
      
      // Move power-up down
      powerUp.y += powerUp.speed;
      
      // Check collision with player
      if (checkCollision(playerShip, powerUp)) {
        powerUp.collected = true;
        
        if (powerUp.type === 'speed') {
          // 🚀 NEW: Add speed boost ammo instead of immediate activation
          speedBoostAmmo += 2; // Add 2 speed boost uses
          console.log(`⚡ Added 2 speed boost ammo! Total: ${speedBoostAmmo}`);
        } else if (powerUp.type === 'ammo') {
          weaponAmmo[powerUp.ammoType] += 2; // Add 2 ammo
          console.log(`🔫 Added 2 ${powerUp.ammoType} ammo!`);
          updateWeaponDisplay();
          
          // 🚀 NEW: Update reload button when ammo is collected
          if (reloadButton) {
            updateReloadButton();
          }
        }
      }
      
      // Remove if off screen
      if (powerUp.y > canvasHeight + 20) {
        window.powerUps.splice(index, 1);
      }
    });
  }

  // 🚀 NEW: Draw power-ups
  function drawPowerUps() {
    if (!window.powerUps) return;
    
    window.powerUps.forEach(powerUp => {
      if (powerUp.collected) return;
      
      ctx.fillStyle = powerUp.color;
      ctx.fillRect(powerUp.x, powerUp.y, powerUp.width, powerUp.height);
      
      // Add glow effect
      ctx.fillStyle = powerUp.color + '40';
      ctx.fillRect(powerUp.x - 2, powerUp.y - 2, powerUp.width + 4, powerUp.height + 4);
      
      // Draw power-up symbol
      ctx.fillStyle = '#ffffff';
      ctx.font = '12px Arial';
      if (powerUp.type === 'speed') {
        ctx.fillText('⚡', powerUp.x + 4, powerUp.y + 15);
      } else if (powerUp.type === 'ammo') {
        ctx.fillText('🔫', powerUp.x + 4, powerUp.y + 15);
      }
    });
  }

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

    // 🚀 NEW: Initialize weapon system
    currentWeaponType = 'normal';
    weaponCooldowns = { normal: 0, laser: 0, bomb: 0 };
    weaponAmmo = { normal: Infinity, laser: 5, bomb: 3 };
    speedBoostActive = false;
    speedBoostTimer = 0;
    speedBoostAmmo = 2; // 🚀 NEW: Limited speed boost ammo
    window.powerUps = []; // Initialize power-ups array

    // 🆘 NEW: Initialize help system
    helpOverlayVisible = false;
    mobileControlsVisible = window.innerWidth <= 768; // Show on mobile by default
    
    // 🆘 IMPROVED: Show mobile controls by default on mobile devices
    if (window.innerWidth <= 768) {
      mobileControlsVisible = true;
    }

    console.log('✅ Game state initialized');

    // Initialize invaders
    initializeInvaders();
    
    // Load DSPOINC settings
    loadDspoinSettings();
    
    // 🚀 NEW: Initialize weapon display
    updateWeaponDisplay();
    
    // 🆘 NEW: Create enhanced mobile controls - ALWAYS CREATE FOR BETTER UX
    setTimeout(() => {
      createEnhancedMobileControls();
      // 🆘 IMPROVED: Show mobile controls by default on every game start
      const mobileControls = document.getElementById('mobile-controls');
      if (mobileControls) {
        mobileControls.style.display = 'flex';
        console.log('✅ Mobile controls made visible on game start');
      }
    }, 50); // Reduced delay for faster control creation
    
    // 🆘 NEW: Display help information outside game canvas
    setTimeout(() => {
      displayHelpInfoOutside();
    }, 200);
    
    // 🆘 NEW: Game panel is now created automatically with createEnhancedMobileControls
    // No need for separate toggle button - the game panel button is included
    
    // 🆘 NEW: Ensure mobile controls are visible after initialization
    setTimeout(() => {
      ensureMobileControlsVisible();
    }, 250);
    
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
        // 🚀 NEW: V-shaped formation - MUCH closer to player and more aggressive!
        const vPositions = [
          [3, 0], [4, 0], [5, 0],
          [2, 1], [3, 1], [4, 1], [5, 1], [6, 1],
          [1, 2], [2, 2], [3, 2], [4, 2], [5, 2], [6, 2], [7, 2],
          [0, 3], [1, 3], [2, 3], [3, 3], [4, 3], [5, 3], [6, 3], [7, 3], [8, 3]
        ];
        vPositions.forEach(([col, row]) => {
          // 🚀 NEW: Spawn invaders at reasonable distance (canvasHeight - 350 instead of 50)
          const spawnY = canvasHeight - 350 + (row * 35); // Reasonable distance spawn
          invaders.push(createInvader(col * 45 + 30, spawnY, row, 'v_formation'));
        });
        break;
        
      case 'pyramid':
        // 🚀 NEW: Pyramid formation - MUCH closer to player and more aggressive!
        for (let row = 0; row < 4; row++) {
          const colsInRow = row + 1;
          const startCol = 4 - row;
          for (let col = 0; col < colsInRow; col++) {
            // 🚀 NEW: Spawn invaders at reasonable distance
            const spawnY = canvasHeight - 350 + (row * 35); // Reasonable distance spawn
            invaders.push(createInvader((startCol + col) * 45 + 30, spawnY, row, 'pyramid'));
          }
        }
        break;
        
      case 'diamond':
        // 🚀 NEW: Diamond formation - MUCH closer to player and more aggressive!
        const diamondPositions = [
          [4, 0],
          [3, 1], [4, 1], [5, 1],
          [2, 2], [3, 2], [4, 2], [5, 2], [6, 2],
          [3, 3], [4, 3], [5, 3],
          [4, 4]
        ];
        diamondPositions.forEach(([col, row]) => {
          // 🚀 NEW: Spawn invaders at reasonable distance
          const spawnY = canvasHeight - 350 + (row * 35); // Reasonable distance spawn
          invaders.push(createInvader(col * 45 + 30, spawnY, row, 'diamond'));
        });
        break;
        
      case 'cross':
        // 🚀 NEW: Cross formation - MUCH closer to player and more aggressive!
        const crossPositions = [
          [4, 0], [4, 1], [4, 2], [4, 3], [4, 4],
          [2, 2], [3, 2], [5, 2], [6, 2]
        ];
        crossPositions.forEach(([col, row]) => {
          // 🚀 NEW: Spawn invaders at reasonable distance
          const spawnY = canvasHeight - 350 + (row * 35); // Reasonable distance spawn
          invaders.push(createInvader(col * 45 + 30, spawnY, row, 'cross'));
        });
        break;
        
      case 'spiral':
        // 🚀 NEW: Spiral formation - MUCH closer to player and more aggressive!
        const spiralPositions = [
          [4, 0], [5, 0], [6, 0],
          [3, 1], [7, 1],
          [2, 2], [8, 2],
          [1, 3], [9, 3],
          [0, 4], [10, 4]
        ];
        spiralPositions.forEach(([col, row]) => {
          // 🚀 NEW: Spawn invaders at reasonable distance
          const spawnY = canvasHeight - 350 + (row * 35); // Reasonable distance spawn
          invaders.push(createInvader(col * 40 + 20, spawnY, row, 'spiral'));
        });
        break;
        
      case 'random_cluster':
        // 🚀 NEW: Random cluster - MUCH closer to player and more aggressive!
        for (let i = 0; i < 12; i++) {
          const col = Math.floor(Math.random() * 8);
          const row = Math.floor(Math.random() * 4);
          // 🚀 NEW: Spawn invaders at reasonable distance
          const spawnY = canvasHeight - 350 + (row * 35); // Reasonable distance spawn
          invaders.push(createInvader(col * 45 + 30, spawnY, row, 'random_cluster'));
        }
        break;
        
      case 'ultra_swarm':
        // 🚀 NEW: Ultra dense swarm - MUCH closer to player and more aggressive!
        for (let row = 0; row < 6; row++) {
          for (let col = 0; col < 12; col++) {
            // 🚀 NEW: Spawn invaders at reasonable distance
            const spawnY = canvasHeight - 350 + (row * 30); // Reasonable distance spawn, tighter spacing
            invaders.push(createInvader(col * 35 + 20, spawnY, row, 'ultra_swarm'));
          }
        }
        // Add extra random invaders at reasonable distance
        for (let i = 0; i < 15; i++) {
          const x = Math.random() * (canvasWidth - 60);
          const y = canvasHeight - 400 + Math.random() * 150; // Reasonable distance random spawns
          invaders.push(createInvader(x, y, Math.floor(Math.random() * 3), 'ultra_swarm_extra'));
        }
        break;
        
      case 'double_formation':
        // 🚀 NEW: Double formation - MUCH closer to player and more aggressive!
        // First layer
        for (let row = 0; row < 4; row++) {
          for (let col = 0; col < 8; col++) {
            // 🚀 NEW: Spawn invaders at reasonable distance
            const spawnY = canvasHeight - 350 + (row * 35); // Reasonable distance spawn
            invaders.push(createInvader(col * 45 + 30, spawnY, row, 'double_formation_1'));
          }
        }
        // Second layer (offset) - also at reasonable distance
        for (let row = 0; row < 3; row++) {
          for (let col = 0; col < 6; col++) {
            // 🚀 NEW: Spawn second layer invaders at reasonable distance
            const spawnY = canvasHeight - 300 + (row * 30); // Reasonable distance for second layer
            invaders.push(createInvader(col * 45 + 60, spawnY, row + 4, 'double_formation_2'));
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
    
    // 🆘 NEW: Ensure mobile controls are always visible when game starts
    setTimeout(() => {
      ensureMobileControlsVisible();
    }, 100);
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
    
    // 🚀 NEW: Reset weapon system
    currentWeaponType = 'normal';
    weaponCooldowns = { normal: 0, laser: 0, bomb: 0 };
    weaponAmmo = { normal: Infinity, laser: 5, bomb: 3 };
    speedBoostActive = false;
    speedBoostTimer = 0;
    speedBoostAmmo = 2; // 🚀 NEW: Limited speed boost ammo
    window.powerUps = [];
    
    playerShip.x = canvasWidth / 2;
    playerShip.health = 3;
    
    initializeInvaders();
    updateScore();
    
    // 🚀 NEW: Update weapon display
    updateWeaponDisplay();
    
    // 🆘 NEW: Display help information outside game canvas
    setTimeout(() => {
      displayHelpInfoOutside();
    }, 100);
    
    // 🆘 NEW: Ensure mobile controls are visible when game is reset
    setTimeout(() => {
      ensureMobileControlsVisible();
    }, 150);
  }

  function gameLoop() {
    if (isSpaceInvadersPaused) return;
    
    updateGame();
    draw();
  }

  function updateGame() {
    // 🎯 NEW: Much faster and more engaging gameplay
    phaseTimer++;
    
    // 🚀 NEW: Update weapon cooldowns and speed boost
    updateWeaponCooldowns();
    updateSpeedBoost();
    
    if (gamePhase === 'formation') {
      // Formation phase - much shorter and you can shoot!
      moveInvadersFormation();
      moveBullets();
      moveInvaderBullets();
      moveTetrisDangerItems();
      checkBulletCollisions();
      checkPlayerHit();
      checkTetrisCollisions();
      
      // 🚀 NEW: Check invader-player collisions
      checkInvaderPlayerCollisions();
      
      // 🚀 NEW: Update power-ups
      updatePowerUps();
      
      if (phaseTimer > 50) { // 🚀 NEW: 5 seconds at 100ms intervals (was 10 seconds) - MUCH more aggressive!
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
      
      // 🚀 NEW: Check invader-player collisions
      checkInvaderPlayerCollisions();
      
      // 🚀 NEW: Update power-ups
      updatePowerUps();
      
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
    
    // 🚀 NEW: Spawn power-ups periodically
    spawnPowerUp();
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
        
        // 🚀 NEW: MUCH more aggressive wave-based speed multiplier!
        const waveSpeedMultiplier = 2 + (waveNumber - 1) * 2.5; // 250% faster each wave - EXTREMELY dangerous!
        
        // 🚀 NEW: Different movement patterns - ALL much more aggressive!
        if (invader.movePattern === 'zigzag') {
          // Zigzag pattern - MUCH more dangerous and faster
          invader.x += invaderDirection * gameSpeed * 1.5 * waveSpeedMultiplier;
          invader.y += 3.0 * waveSpeedMultiplier; // 3x faster downward movement
          
          // Zigzag movement - more frequent direction changes in later waves
          if (Math.random() < (0.25 + waveNumber * 0.05)) { // More direction changes
            invaderDirection *= -1;
          }
        } else if (invader.movePattern === 'dive') {
          // 🚀 NEW: Dive pattern - invaders dive STRAIGHT DOWN at player aggressively
          invader.y += 4.0 * waveSpeedMultiplier; // 4x faster downward movement
          invader.x += (playerShip.x - invader.x) * 0.08 * waveSpeedMultiplier; // Much more aggressive targeting
        } else if (invader.movePattern === 'spiral') {
          // 🚀 NEW: Spiral pattern - invaders move in aggressive spiral motion
          const spiralRadius = 20 + waveNumber * 3; // Smaller, tighter spirals
          const spiralSpeed = 0.3 + waveNumber * 0.05; // Much faster spiraling
          invader.spiralOffset += spiralSpeed;
          invader.x += Math.cos(invader.spiralOffset) * spiralRadius * 0.2;
          invader.y += 2.5 * waveSpeedMultiplier; // 2.5x faster downward movement
        } else if (invader.movePattern === 'hover') {
          // 🚀 NEW: Hover pattern - invaders hover and move side to side aggressively
          invader.y += 1.5 * waveSpeedMultiplier; // 1.5x faster downward movement
          invader.x += Math.sin(currentTime * 0.01 + invader.x * 0.02) * 4 * waveSpeedMultiplier; // More aggressive side movement
        } else {
          // 🚀 NEW: Standard movement - straight down with much more aggressive movement
          invader.x += invaderDirection * gameSpeed * 0.8 * waveSpeedMultiplier;
          invader.y += 2.2 * waveSpeedMultiplier; // 2.2x faster downward movement
        }
        
        // 🚀 NEW: Bounce off walls with MUCH more aggressive behavior
        if (invader.x <= 0 || invader.x >= canvasWidth - invader.width) {
          invaderDirection *= -1;
          // 🚀 NEW: ALL waves now have aggressive wall bouncing
          invader.y += 8 + waveNumber * 2; // Much more aggressive drop down
          
          // 🚀 NEW: Add extra speed boost when hitting walls
          invader.wallBounceBoost = true;
          invader.wallBounceTimer = 30; // 3 seconds of boosted speed
        }
        
        // 🚀 NEW: Apply wall bounce speed boost
        if (invader.wallBounceBoost && invader.wallBounceTimer > 0) {
          invader.y += 3; // Extra downward speed
          invader.wallBounceTimer--;
          if (invader.wallBounceTimer <= 0) {
            invader.wallBounceBoost = false;
          }
        }
        
        // 🚀 NEW: Ensure invaders can reach the player area
        if (invader.y < canvasHeight - 100) { // Allow invaders to go close to player area
          // No restrictions - invaders can move freely and reach the player!
          
          // 🚀 NEW: Extra aggressive behavior when close to player
          if (invader.y > canvasHeight - 200) {
            invader.y += 1; // Moderate speed boost when close to player
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
            rotation: Math.random() * 360,
            // 🚀 NEW: Enhanced destruction properties - MUCH HARDER!
            health: calculateBlockHealth(regularConfig.type, waveNumber),
            maxHealth: calculateBlockHealth(regularConfig.type, waveNumber),
            isDestroyed: false,
            destructionProgress: 0,
            lastHitTime: 0
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
      isBomb: type === 'BOMB_BLOCK', // Flag for special bomb effects
      // 🚀 NEW: Enhanced destruction properties - MUCH HARDER!
      health: calculateBlockHealth(tetrisConfig.type, waveNumber),
      maxHealth: calculateBlockHealth(tetrisConfig.type, waveNumber),
      isDestroyed: false,
      destructionProgress: 0,
      lastHitTime: 0
    });
  }

  // 🚀 NEW: Calculate block health based on type and wave
  function calculateBlockHealth(blockType, wave) {
    const baseHealth = 6 + Math.floor(wave / 2); // Base health doubled from 3 to 6
    
    // 🚀 NEW: Different health multipliers for different block types
    switch (blockType) {
      case 'SNAKE_DNA':
        return Math.floor(baseHealth * 2.5); // DNA strings are VERY tough!
      case 'SNAKE_HEAD':
        return Math.floor(baseHealth * 2.0); // Snake heads are tough!
      case 'BOMB_BLOCK':
        return Math.floor(baseHealth * 1.8); // Bombs are hard to destroy
      case 'I_BLOCK':
      case 'O_BLOCK':
      case 'T_BLOCK':
      case 'S_BLOCK':
      case 'Z_BLOCK':
      case 'J_BLOCK':
      case 'L_BLOCK':
        return Math.floor(baseHealth * 1.0); // Regular cheese blocks (doubled base)
      default:
        return baseHealth;
    }
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
        
        // 🐍 NEW: Special snake head explosion effect
        if (item.type === 'SNAKE_HEAD') {
          // 30% chance to trigger eye explosion
          if (Math.random() < 0.3) {
            createEyeExplosion(item.x + item.width / 2, item.y + item.height / 2);
            console.log('👁️ Snake head triggered eye explosion!');
          }
        }
        
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

  // 🐍 NEW: Create eye explosion effect
  function createEyeExplosion(x, y) {
    // Create multiple eye explosions around the hit point
    for (let i = 0; i < 5; i++) {
      const offsetX = (Math.random() - 0.5) * 60; // Spread horizontally
      const offsetY = (Math.random() - 0.5) * 40; // Spread vertically
      
      explosions.push({
        x: x + offsetX,
        y: y + offsetY,
        size: 15 + Math.random() * 20, // Varied sizes
        timer: 25 + Math.random() * 15, // Varied durations
        isEyeExplosion: true,
        eyeType: Math.random() < 0.5 ? 'red' : 'blue' // Different eye colors
      });
    }
    
    // Add screen flash effect for dramatic impact
    ctx.save();
    ctx.fillStyle = 'rgba(255, 0, 255, 0.2)'; // Magenta flash
    ctx.fillRect(0, 0, canvasWidth, canvasHeight);
    ctx.restore();
    
    // Create additional particle effects
    for (let i = 0; i < 8; i++) {
      const angle = (i / 8) * Math.PI * 2;
      const distance = 40 + Math.random() * 30;
      const particleX = x + Math.cos(angle) * distance;
      const particleY = y + Math.sin(angle) * distance;
      
      explosions.push({
        x: particleX,
        y: particleY,
        size: 8 + Math.random() * 12,
        timer: 20 + Math.random() * 20,
        isEyeParticle: true,
        angle: angle,
        speed: 2 + Math.random() * 3
      });
    }
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

  // 🚀 NEW: Draw spectacular laser beam
  function drawSpectacularLaserBeam(bullet) {
    const currentTime = Date.now();
    const pulseSpeed = 0.01;
    const pulseIntensity = 0.5 + Math.sin(currentTime * pulseSpeed) * 0.5;
    
    // 🚀 NEW: Main laser beam with pulsing effect
    const beamWidth = bullet.width * (1 + pulseIntensity * 0.3);
    const beamX = bullet.x - (beamWidth - bullet.width) / 2;
    
    // Create gradient for laser beam
    const gradient = ctx.createLinearGradient(beamX, bullet.y, beamX + beamWidth, bullet.y);
    gradient.addColorStop(0, 'rgba(0, 255, 255, 0.3)'); // Outer edge
    gradient.addColorStop(0.3, 'rgba(0, 255, 255, 0.8)'); // Inner glow
    gradient.addColorStop(0.5, 'rgba(255, 255, 255, 1.0)'); // Bright center
    gradient.addColorStop(0.7, 'rgba(0, 255, 255, 0.8)'); // Inner glow
    gradient.addColorStop(1, 'rgba(0, 255, 255, 0.3)'); // Outer edge
    
    // Draw main laser beam
    ctx.fillStyle = gradient;
    ctx.fillRect(beamX, bullet.y, beamWidth, bullet.height);
    
    // 🚀 NEW: Add energy core
    ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
    ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
    
    // 🚀 NEW: Add outer energy glow
    ctx.fillStyle = 'rgba(0, 255, 255, 0.2)';
    ctx.fillRect(beamX - 4, bullet.y - 4, beamWidth + 8, bullet.height + 8);
    
    // 🚀 NEW: Add energy particles along the beam
    for (let i = 0; i < 5; i++) {
      const particleY = bullet.y + (i * bullet.height / 4);
      const particleSize = 2 + Math.sin(currentTime * 0.02 + i) * 1;
      
      ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
      ctx.beginPath();
      ctx.arc(bullet.x + bullet.width / 2, particleY, particleSize, 0, Math.PI * 2);
      ctx.fill();
    }
    
    // 🚀 NEW: Add beam distortion effect
    ctx.strokeStyle = 'rgba(0, 255, 255, 0.4)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    for (let i = 0; i < bullet.height; i += 10) {
      const waveOffset = Math.sin(currentTime * 0.01 + i * 0.1) * 2;
      ctx.moveTo(beamX + waveOffset, bullet.y + i);
      ctx.lineTo(beamX + beamWidth + waveOffset, bullet.y + i);
    }
    ctx.stroke();
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
      let bulletHit = false;
      
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
          
          // 🚀 NEW: Handle laser piercing
          if (bullet.type === 'laser' && bullet.pierce) {
            // Laser can hit multiple invaders - don't remove bullet yet
            bulletHit = true;
            // Reduce laser damage for each hit (optional)
            bullet.damage = Math.max(1, bullet.damage - 1);
          } else {
            // Normal bullet - remove after first hit
            bulletHit = true;
          }
        }
      });
      
      // 🚀 NEW: Check bullet collisions with Tetris blocks
      tetrisDangerItems.forEach((tetrisItem, tetrisIndex) => {
        if (tetrisItem.isDestroyed) return;
        
        if (checkCollision(bullet, tetrisItem)) {
          // Calculate damage based on bullet type
          let damage = 1;
          if (bullet.type === 'laser') damage = 2;
          if (bullet.type === 'bomb') damage = 5;
          
          // Apply damage to Tetris block
          tetrisItem.health -= damage;
          tetrisItem.destructionProgress = 1 - (tetrisItem.health / tetrisItem.maxHealth);
          tetrisItem.lastHitTime = Date.now();
          
          // Create hit effect
          explosions.push({
            x: tetrisItem.x + tetrisItem.width / 2,
            y: tetrisItem.y + tetrisItem.height / 2,
            size: 15 + damage * 2,
            timer: 15,
            isTetrisHit: true,
            tetrisType: tetrisItem.type
          });
          
          // Check if Tetris block is destroyed
          if (tetrisItem.health <= 0) {
            tetrisItem.isDestroyed = true;
            
            // 🚀 NEW: Chance to spawn more invaders when destroyed!
            if (Math.random() < 0.4) { // 40% chance
              spawnInvadersFromTetris(tetrisItem);
            }
            
            // Create destruction explosion
            explosions.push({
              x: tetrisItem.x + tetrisItem.width / 2,
              y: tetrisItem.y + tetrisItem.height / 2,
              size: 30,
              timer: 25,
              isTetrisDestroyed: true,
              tetrisType: tetrisItem.type
            });
            
            // Remove destroyed Tetris block
            tetrisDangerItems.splice(tetrisIndex, 1);
          }
          
          // Mark bullet as hit
          bulletHit = true;
        }
      });
      
      // Remove bullet if it hit something or if it's a laser that's lost all damage
      if (bulletHit && (!bullet.pierce || bullet.damage <= 0)) {
        bullets.splice(bulletIndex, 1);
      }
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

  // 🚀 NEW: Check for collisions between invaders and player ship
  function checkInvaderPlayerCollisions() {
    invaders.forEach((invader, index) => {
      if (!invader.alive) return;
      
      // Check if invader collides with player ship
      if (checkCollision(invader, playerShip)) {
        console.log(`💥 Invader collision with player! Player health: ${playerShip.health} -> ${playerShip.health - 1}`);
        
        // Damage player (invader collision is deadly!)
        playerShip.health--;
        
        // Kill the invading invader
        invader.alive = false;
        spaceInvadersScore += invader.points;
        
        // Create explosion effect at collision point
        explosions.push({
          x: invader.x + invader.width / 2,
          y: invader.y + invader.height / 2,
          size: 30,
          timer: 15,
          isInvaderCollision: true
        });
        
        // Create additional explosion at player ship
        explosions.push({
          x: playerShip.x + playerShip.width / 2,
          y: playerShip.y + playerShip.height / 2,
          size: 25,
          timer: 12,
          isPlayerHit: true
        });
        
        // Check if player is dead
        if (playerShip.health <= 0) {
          console.log('💀 Player destroyed by invader collision!');
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
    drawPowerUps(); // 🚀 NEW: Draw power-ups
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
      if (bullet.type === 'laser') {
        // 🚀 NEW: Spectacular laser beam drawing
        drawSpectacularLaserBeam(bullet);
      } else {
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
      }
    });
  }

  // 🚀 NEW: Draw spectacular laser beam
  function drawSpectacularLaserBeam(bullet) {
    const currentTime = Date.now();
    const pulseSpeed = 0.01;
    const pulseIntensity = 0.5 + Math.sin(currentTime * pulseSpeed) * 0.5;
    
    // 🚀 NEW: Main laser beam with pulsing effect
    const beamWidth = bullet.width * (1 + pulseIntensity * 0.3);
    const beamX = bullet.x - (beamWidth - bullet.width) / 2;
    
    // Create gradient for laser beam
    const gradient = ctx.createLinearGradient(beamX, bullet.y, beamX + beamWidth, bullet.y);
    gradient.addColorStop(0, 'rgba(0, 255, 255, 0.3)'); // Outer edge
    gradient.addColorStop(0.3, 'rgba(0, 255, 255, 0.8)'); // Inner glow
    gradient.addColorStop(0.5, 'rgba(255, 255, 255, 1.0)'); // Bright center
    gradient.addColorStop(0.7, 'rgba(0, 255, 255, 0.8)'); // Inner glow
    gradient.addColorStop(1, 'rgba(0, 255, 255, 0.3)'); // Outer edge
    
    // Draw main laser beam
    ctx.fillStyle = gradient;
    ctx.fillRect(beamX, bullet.y, beamWidth, bullet.height);
    
    // 🚀 NEW: Add energy core
    ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
    ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
    
    // 🚀 NEW: Add outer energy glow
    ctx.fillStyle = 'rgba(0, 255, 255, 0.2)';
    ctx.fillRect(beamX - 4, bullet.y - 4, beamWidth + 8, bullet.height + 8);
    
    // 🚀 NEW: Add energy particles along the beam
    for (let i = 0; i < 5; i++) {
      const particleY = bullet.y + (i * bullet.height / 4);
      const particleSize = 2 + Math.sin(currentTime * 0.02 + i) * 1;
      
      ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
      ctx.beginPath();
      ctx.arc(bullet.x + bullet.width / 2, particleY, particleSize, 0, Math.PI * 2);
      ctx.fill();
    }
    
    // 🚀 NEW: Add beam distortion effect
    ctx.strokeStyle = 'rgba(0, 255, 255, 0.4)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    for (let i = 0; i < bullet.height; i += 10) {
      const waveOffset = Math.sin(currentTime * 0.01 + i * 0.1) * 2;
      ctx.moveTo(beamX + waveOffset, bullet.y + i);
      ctx.lineTo(beamX + beamWidth + waveOffset, bullet.y + i);
    }
    ctx.stroke();
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
      if (item.isDestroyed) return; // Skip destroyed items
      
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
      
      // 🚀 NEW: Draw health bar above Tetris block
      if (item.health < item.maxHealth) {
        const healthBarWidth = item.width;
        const healthBarHeight = 4;
        const healthBarX = item.x;
        const healthBarY = item.y - 8;
        
        // Background (damaged)
        ctx.fillStyle = 'rgba(255, 0, 0, 0.7)';
        ctx.fillRect(healthBarX, healthBarY, healthBarWidth, healthBarHeight);
        
        // Health (remaining)
        const healthPercentage = item.health / item.maxHealth;
        ctx.fillStyle = 'rgba(0, 255, 0, 0.9)';
        ctx.fillRect(healthBarX, healthBarY, healthBarWidth * healthPercentage, healthBarHeight);
        
        // Health border
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.8)';
        ctx.lineWidth = 1;
        ctx.strokeRect(healthBarX, healthBarY, healthBarWidth, healthBarHeight);
      }
      
      // 🚀 NEW: Draw destruction progress effect
      if (item.destructionProgress > 0) {
        const currentTime = Date.now();
        const flashIntensity = Math.sin(currentTime * 0.02) * 0.3 + 0.7;
        
        // Cracks and damage effects
        ctx.strokeStyle = `rgba(255, 0, 0, ${flashIntensity * item.destructionProgress})`;
        ctx.lineWidth = 2;
        ctx.beginPath();
        
        // Draw random cracks based on destruction progress
        const crackCount = Math.floor(item.destructionProgress * 5) + 1;
        for (let i = 0; i < crackCount; i++) {
          const startX = item.x + Math.random() * item.width;
          const startY = item.y + Math.random() * item.height;
          const endX = startX + (Math.random() - 0.5) * 20;
          const endY = startY + (Math.random() - 0.5) * 20;
          
          ctx.moveTo(startX, startY);
          ctx.lineTo(endX, endY);
        }
        ctx.stroke();
        
        // Damage glow effect
        ctx.fillStyle = `rgba(255, 0, 0, ${item.destructionProgress * 0.2})`;
        ctx.fillRect(item.x - 2, item.y - 2, item.width + 4, item.height + 4);
      }
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
      } else if (explosion.isBombKill) {
        // 💥 NEW: Bomb kill explosion effects for individual invaders
        ctx.fillStyle = `rgba(255, 0, 255, ${alpha})`; // Magenta bomb kill
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha, 0, Math.PI * 2);
        ctx.fill();
        
        // Inner bomb kill effect
        ctx.fillStyle = `rgba(255, 20, 147, ${alpha * 0.8})`; // Deep pink center
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha * 0.7, 0, Math.PI * 2);
        ctx.fill();
        
        // Bomb kill particles - intense and chaotic
        for (let i = 0; i < 10; i++) {
          const angle = (i / 10) * Math.PI * 2;
          const distance = explosion.size * alpha * 1.0;
          const particleX = explosion.x + Math.cos(angle) * distance;
          const particleY = explosion.y + Math.sin(angle) * distance;
          
          const colors = ['#ff00ff', '#ff1493', '#c71585', '#db7093'];
          ctx.fillStyle = colors[i % colors.length] + Math.floor(alpha * 255).toString(16).padStart(2, '0');
          ctx.beginPath();
          ctx.arc(particleX, particleY, 4 * alpha, 0, Math.PI * 2);
          ctx.fill();
        }
      } else if (explosion.isBombSubExplosion) {
        // 💥 NEW: Bomb sub-explosion effects across the screen
        ctx.fillStyle = `rgba(255, 165, 0, ${alpha})`; // Orange sub-explosion
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha, 0, Math.PI * 2);
        ctx.fill();
        
        // Inner sub-explosion effect
        ctx.fillStyle = `rgba(255, 69, 0, ${alpha * 0.8})`; // Red-orange center
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha * 0.6, 0, Math.PI * 2);
        ctx.fill();
        
        // Sub-explosion particles
        for (let i = 0; i < 8; i++) {
          const angle = (i / 8) * Math.PI * 2;
          const distance = explosion.size * alpha * 0.8;
          const particleX = explosion.x + Math.cos(angle) * distance;
          const particleY = explosion.y + Math.sin(angle) * distance;
          
          const colors = ['#ff4500', '#ff6347', '#ff8c00', '#ffa500'];
          ctx.fillStyle = colors[i % colors.length] + Math.floor(alpha * 255).toString(16).padStart(2, '0');
          ctx.beginPath();
          ctx.arc(particleX, particleY, 3 * alpha, 0, Math.PI * 2);
          ctx.fill();
        }
      } else if (explosion.isEyeExplosion) {
        // 🐍 NEW: Eye explosion effects from snake head hits
        const eyeColor = explosion.eyeType === 'red' ? '#ff0000' : '#0000ff';
        ctx.fillStyle = eyeColor + Math.floor(alpha * 255).toString(16).padStart(2, '0');
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha, 0, Math.PI * 2);
        ctx.fill();
        
        // Inner eye pupil
        ctx.fillStyle = '#000000';
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha * 0.6, 0, Math.PI * 2);
        ctx.fill();
        
        // Eye highlight
        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        ctx.arc(explosion.x - 2, explosion.y - 2, explosion.size * alpha * 0.3, 0, Math.PI * 2);
        ctx.fill();
        
        // Eye rays/spikes
        for (let i = 0; i < 6; i++) {
          const angle = (i / 6) * Math.PI * 2;
          const rayLength = explosion.size * alpha * 1.2;
          const rayX = explosion.x + Math.cos(angle) * rayLength;
          const rayY = explosion.y + Math.sin(angle) * rayLength;
          
          ctx.strokeStyle = eyeColor + Math.floor(alpha * 255).toString(16).padStart(2, '0');
          ctx.lineWidth = 3 * alpha;
          ctx.beginPath();
          ctx.moveTo(explosion.x, explosion.y);
          ctx.lineTo(rayX, rayY);
          ctx.stroke();
        }
      } else if (explosion.isEyeParticle) {
        // 🐍 NEW: Eye particle effects
        ctx.fillStyle = `rgba(255, 0, 255, ${alpha})`; // Magenta particles
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha, 0, Math.PI * 2);
        ctx.fill();
        
        // Moving particles
        explosion.x += Math.cos(explosion.angle) * explosion.speed * alpha;
        explosion.y += Math.sin(explosion.angle) * explosion.speed * alpha;
      } else if (explosion.isLaserParticle) {
        // 🚀 NEW: Laser particle effects
        ctx.fillStyle = explosion.color + Math.floor(alpha * 255).toString(16).padStart(2, '0');
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha, 0, Math.PI * 2);
        ctx.fill();
        
        // Add glow effect
        ctx.fillStyle = explosion.color + Math.floor(alpha * 100).toString(16).padStart(2, '0');
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha * 1.5, 0, Math.PI * 2);
        ctx.fill();
        
        // Move particles with velocity
        if (explosion.velocity) {
          explosion.x += explosion.velocity.x * alpha;
          explosion.y += explosion.velocity.y * alpha;
        }
      } else if (explosion.isLaserTrail) {
        // 🚀 NEW: Laser trail effects
        const trailAlpha = alpha * (1 - explosion.trailIndex * 0.1); // Fade trail
        ctx.fillStyle = explosion.color + Math.floor(trailAlpha * 255).toString(16).padStart(2, '0');
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * trailAlpha, 0, Math.PI * 2);
        ctx.fill();
        
        // Add energy glow
        ctx.fillStyle = explosion.color + Math.floor(trailAlpha * 50).toString(16).padStart(2, '0');
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * trailAlpha * 2, 0, Math.PI * 2);
        ctx.fill();
      } else if (explosion.isEnergyDrain) {
        // 🚀 NEW: Energy drain effects
        explosion.progress += explosion.speed;
        const currentX = explosion.startX + (explosion.endX - explosion.startX) * explosion.progress;
        const currentY = explosion.startY + (explosion.endY - explosion.startY) * explosion.progress;
        
        // Draw energy particle
        ctx.fillStyle = explosion.color + Math.floor(alpha * 255).toString(16).padStart(2, '0');
        ctx.beginPath();
        ctx.arc(currentX, currentY, explosion.size * alpha, 0, Math.PI * 2);
        ctx.fill();
        
        // Add energy trail
        const trailLength = 20;
        ctx.strokeStyle = explosion.color + Math.floor(alpha * 100).toString(16).padStart(2, '0');
        ctx.lineWidth = 2 * alpha;
        ctx.beginPath();
        ctx.moveTo(currentX, currentY);
        ctx.lineTo(currentX, currentY + trailLength);
        ctx.stroke();
        
        // Update position for next frame
        explosion.x = currentX;
        explosion.y = currentY;
      } else if (explosion.isTetrisHit) {
        // 🚀 NEW: Tetris hit explosion effects
        ctx.fillStyle = `rgba(255, 165, 0, ${alpha})`; // Orange hit effect
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha, 0, Math.PI * 2);
        ctx.fill();
        
        // Inner hit effect
        ctx.fillStyle = `rgba(255, 255, 255, ${alpha * 0.8})`; // White center
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha * 0.6, 0, Math.PI * 2);
        ctx.fill();
        
        // Hit particles
        for (let i = 0; i < 6; i++) {
          const angle = (i / 6) * Math.PI * 2;
          const distance = explosion.size * alpha * 0.8;
          const particleX = explosion.x + Math.cos(angle) * distance;
          const particleY = explosion.y + Math.sin(angle) * distance;
          
          ctx.fillStyle = `rgba(255, 69, 0, ${alpha * 0.7})`; // Red-orange particles
          ctx.beginPath();
          ctx.arc(particleX, particleY, 3 * alpha, 0, Math.PI * 2);
          ctx.fill();
        }
      } else if (explosion.isTetrisDestroyed) {
        // 🚀 NEW: Tetris destruction explosion effects
        ctx.fillStyle = `rgba(255, 0, 0, ${alpha})`; // Red destruction
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha, 0, Math.PI * 2);
        ctx.fill();
        
        // Inner destruction effect
        ctx.fillStyle = `rgba(255, 165, 0, ${alpha * 0.8})`; // Orange center
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha * 0.7, 0, Math.PI * 2);
        ctx.fill();
        
        // Destruction particles - more intense
        for (let i = 0; i < 12; i++) {
          const angle = (i / 12) * Math.PI * 2;
          const distance = explosion.size * alpha * 0.9;
          const particleX = explosion.x + Math.cos(angle) * distance;
          const particleY = explosion.y + Math.sin(angle) * distance;
          
          const colors = ['#ff0000', '#ff4500', '#ff6347', '#ff8c00'];
          ctx.fillStyle = colors[i % colors.length] + Math.floor(alpha * 255).toString(16).padStart(2, '0');
          ctx.beginPath();
          ctx.arc(particleX, particleY, 4 * alpha, 0, Math.PI * 2);
          ctx.fill();
        }
      } else if (explosion.isInvaderCollision) {
        // 💥 NEW: Invader collision explosion effects
        ctx.fillStyle = `rgba(255, 0, 255, ${alpha})`; // Magenta collision
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha, 0, Math.PI * 2);
        ctx.fill();
        
        // Inner collision effect
        ctx.fillStyle = `rgba(255, 20, 147, ${alpha * 0.8})`; // Deep pink center
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha * 0.7, 0, Math.PI * 2);
        ctx.fill();
        
        // Collision particles - intense and chaotic
        for (let i = 0; i < 15; i++) {
          const angle = (i / 15) * Math.PI * 2;
          const distance = explosion.size * alpha * 1.1;
          const particleX = explosion.x + Math.cos(angle) * distance;
          const particleY = explosion.y + Math.sin(angle) * distance;
          
          const colors = ['#ff00ff', '#ff1493', '#c71585', '#db7093', '#ff69b4'];
          ctx.fillStyle = colors[i % colors.length] + Math.floor(alpha * 255).toString(16).padStart(2, '0');
          ctx.beginPath();
          ctx.arc(particleX, particleY, 5 * alpha, 0, Math.PI * 2);
          ctx.fill();
        }
        
        // Collision shockwave
        ctx.strokeStyle = `rgba(255, 0, 255, ${alpha * 0.6})`;
        ctx.lineWidth = 3 * alpha;
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha * 1.5, 0, Math.PI * 2);
        ctx.stroke();
      } else if (explosion.isPlayerHit) {
        // 💥 NEW: Player hit explosion effects
        ctx.fillStyle = `rgba(255, 0, 0, ${alpha})`; // Red player hit
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha, 0, Math.PI * 2);
        ctx.fill();
        
        // Inner player hit effect
        ctx.fillStyle = `rgba(255, 69, 0, ${alpha * 0.8})`; // Red-orange center
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha * 0.6, 0, Math.PI * 2);
        ctx.fill();
        
        // Player hit particles - warning effect
        for (let i = 0; i < 10; i++) {
          const angle = (i / 10) * Math.PI * 2;
          const distance = explosion.size * alpha * 0.9;
          const particleX = explosion.x + Math.cos(angle) * distance;
          const particleY = explosion.y + Math.sin(angle) * distance;
          
          const colors = ['#ff0000', '#ff4500', '#ff6347', '#dc143c'];
          ctx.fillStyle = colors[i % colors.length] + Math.floor(alpha * 255).toString(16).padStart(2, '0');
          ctx.beginPath();
          ctx.arc(particleX, particleY, 3 * alpha, 0, Math.PI * 2);
          ctx.fill();
        }
        
        // Player hit warning ring
        ctx.strokeStyle = `rgba(255, 0, 0, ${alpha * 0.8})`;
        ctx.lineWidth = 2 * alpha;
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, explosion.size * alpha * 1.3, 0, Math.PI * 2);
        ctx.stroke();
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
    } else {
      console.warn('⚠️ Score display element not found');
    }
  }

  // ❤️ NEW: Draw health display (compact layout)
  function drawHealth() {
    ctx.fillStyle = '#ff0000';
    ctx.font = '16px Arial';
    ctx.fillText(`❤${playerShip.health}`, 10, 30);
    
    // 🚀 NEW: Compact ammo display in one line
    let ammoText = '';
    let ammoColor = '#ffffff';
    
    if (weaponAmmo.bomb > 0) {
      ammoText += `💣${weaponAmmo.bomb} `;
    }
    if (weaponAmmo.laser > 0) {
      ammoText += `🔫${weaponAmmo.laser} `;
    }
    if (speedBoostAmmo > 0) {
      ammoText += `⚡${speedBoostAmmo} `;
    }
    
    // Position ammo info to the right of health
    if (ammoText) {
      ctx.fillStyle = ammoColor;
      ctx.fillText(ammoText, 80, 30);
    }
    
    // 🚀 NEW: Speed boost timer (if active) - positioned on the right side
    if (speedBoostActive) {
      ctx.fillStyle = '#00ff00';
      const timeLeft = Math.ceil(speedBoostTimer / 10);
      const timeText = `⚡${timeLeft}s`;
      const timeWidth = ctx.measureText(timeText).width;
      // Position on the right side with some margin
      ctx.fillText(timeText, canvasWidth - timeWidth - 10, 30);
    }
    
    // 🚀 NEW: Weapon ready indicator for desktop players
    if (currentWeaponType === 'laser' && weaponAmmo.laser > 0) {
      ctx.fillStyle = '#00ffff';
      ctx.font = '12px Arial';
      ctx.fillText('🔫 READY', canvasWidth - 80, 50);
    } else if (currentWeaponType === 'bomb' && weaponAmmo.bomb > 0) {
      ctx.fillStyle = '#ff00ff';
      ctx.font = '12px Arial';
      ctx.fillText('💣 READY', canvasWidth - 80, 50);
    }
  }

  // 📊 NEW: Draw phase information with bomb status (compact layout)
  function drawPhaseInfo() {
    if (!ctx || typeof canvasHeight === 'undefined') {
      return; // Don't draw if context or canvas height is not available
    }
    
    ctx.font = '14px Arial';
    
    // 🚀 NEW: Compact phase display
    let phaseText = '';
    let phaseColor = '#ffffff';
    
    if (gamePhase === 'formation') {
      phaseColor = '#4ade80'; // Green for formation
      phaseText = `🎯W${waveNumber}`; // Ultra compact
    } else if (gamePhase === 'attack') {
      if (invaderDropPhase) {
        phaseColor = '#ff6b6b'; // Red for drop phase
        phaseText = `🚀W${waveNumber}`; // Ultra compact
      } else {
        phaseColor = '#4ecdc4'; // Cyan for break phase
        phaseText = `⏸️W${waveNumber}`; // Ultra compact
      }
    }
    
    // 🚀 NEW: Show phase info on the left
    ctx.fillStyle = phaseColor;
    ctx.fillText(phaseText, 10, 50);
    
    // 🚀 NEW: Show auto-shoot status in the center
    const autoText = `AUTO: ${autoShootEnabled ? 'ON' : 'OFF'}`;
    const autoWidth = ctx.measureText(autoText).width;
    ctx.fillStyle = autoShootEnabled ? '#4ade80' : '#ff6b6b';
    ctx.fillText(autoText, (canvasWidth - autoWidth) / 2, 50);
    
    // 🚀 NEW: Show weapon type on the right
    const weaponText = `🔫${currentWeaponType.toUpperCase()}`;
    const weaponWidth = ctx.measureText(weaponText).width;
    ctx.fillStyle = '#ffffff';
    ctx.fillText(weaponText, canvasWidth - weaponWidth - 10, 50);
    
    // 🚀 NEW: Show special weapon ammo below if available
    if (currentWeaponType === 'laser' && weaponAmmo.laser > 0) {
      ctx.fillStyle = '#00ffff';
      ctx.fillText(`⚡${weaponAmmo.laser}`, 10, 70);
    } else if (currentWeaponType === 'bomb' && weaponAmmo.bomb > 0) {
      ctx.fillStyle = '#ff00ff';
      ctx.fillText(`💣${weaponAmmo.bomb}`, 10, 70);
    }
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

    // 🆘 NEW: Ensure mobile controls are visible when game ends
    setTimeout(() => {
      ensureMobileControlsVisible();
    }, 100);

    // Dispatch game end event for UI reset
    window.dispatchEvent(new Event('spaceInvadersGameEnd'));
  }

  function playerShoot() {
    if (isSpaceInvadersPaused) return;
    
    console.log(`🔫 playerShoot called with weapon: ${currentWeaponType}, isQuickShotCall: ${window.isQuickShotCall}`);
    
    // 🚀 NEW: Enhanced shooting system with weapon types
    switch (currentWeaponType) {
      case 'normal':
        // Normal cheese bullet
        bullets.push({
          x: playerShip.x + playerShip.width / 2 - 4,
          y: playerShip.y,
          width: 8,
          height: 16,
          speed: 6,
          type: 'normal',
          damage: 1
        });
        break;
        
      case 'laser':
        // Laser beam - powerful piercing weapon (ONLY through Quick Shot button)
        if (weaponAmmo.laser > 0 && weaponCooldowns.laser <= 0) {
          // Check if this is a Quick Shot button call
          if (window.isQuickShotCall) {
            bullets.push({
              x: playerShip.x + playerShip.width / 2 - 2,
              y: playerShip.y,
              width: 4,
              height: canvasHeight, // Full screen height
              speed: 8,
              type: 'laser',
              damage: 3,
              pierce: true, // Can hit multiple invaders
              color: '#00ffff',
              // 🚀 NEW: Enhanced laser properties
              beamIntensity: 1.0,
              pulsePhase: 0,
              energyLevel: 100,
              isCharged: true
            });
            
            weaponAmmo.laser--;
            weaponCooldowns.laser = 20; // 2 second cooldown
            updateWeaponDisplay();
            
            // 🚀 NEW: Create spectacular laser effect
            createSpectacularLaserEffect();
            
            // Reset the flag
            window.isQuickShotCall = false;
          } else {
            console.log('🚫 Laser can only be fired through Quick Shot button!');
            return; // Don't shoot
          }
        } else {
          console.log('🚫 Laser ammo or cooldown not ready!');
          return; // Don't shoot
        }
        break;
        
      case 'bomb':
        // Bomb weapon - screen clearing explosion (ONLY through Quick Shot button)
        if (weaponAmmo.bomb > 0 && weaponCooldowns.bomb <= 0) {
          // Check if this is a Quick Shot button call
          if (window.isQuickShotCall) {
            console.log('💣 BOMB FIRED! Creating explosion and killing invaders...');
            
            // Create bomb explosion effect
            createBombExplosion();
            
            // Kill all invaders on screen
            let invadersKilled = 0;
            invaders.forEach(invader => {
              if (invader.alive) {
                invader.alive = false;
                invadersKilled++;
                spaceInvadersScore += invader.points;
                
                // Create explosion for each killed invader
                explosions.push({
                  x: invader.x + invader.width / 2,
                  y: invader.y + invader.height / 2,
                  size: 25,
                  timer: 15,
                  isBombKill: true
                });
              }
            });
            
            console.log(`💣 BOMB KILLED ${invadersKilled} invaders!`);
            
            // Clear all invader bullets
            const bulletsCleared = invaderBullets.length;
            invaderBullets = [];
            console.log(`💣 BOMB CLEARED ${bulletsCleared} invader bullets!`);
            
            weaponAmmo.bomb--;
            weaponCooldowns.bomb = 60; // 6 second cooldown
            updateWeaponDisplay();
            
            // Reset the flag
            window.isQuickShotCall = false;
          } else {
            console.log('🚫 Bomb can only be fired through Quick Shot button!');
            return; // Don't shoot
          }
        } else {
          console.log('🚫 Bomb ammo or cooldown not ready!');
          return; // Don't shoot
        }
        break;
    }
  }

  // 🚀 NEW: Create spectacular laser visual effect
  function createSpectacularLaserEffect() {
    // 🚀 NEW: Intense screen flash effect
    ctx.save();
    ctx.fillStyle = 'rgba(0, 255, 255, 0.4)'; // Brighter cyan flash
    ctx.fillRect(0, 0, canvasWidth, canvasHeight);
    ctx.restore();
    
    // 🚀 NEW: Create multiple laser beam particles
    for (let i = 0; i < 15; i++) {
      const angle = Math.random() * Math.PI * 2;
      const distance = 20 + Math.random() * 40;
      const particleX = playerShip.x + playerShip.width / 2 + Math.cos(angle) * distance;
      const particleY = playerShip.y + Math.sin(angle) * distance;
      
      explosions.push({
        x: particleX,
        y: particleY,
        size: 4 + Math.random() * 8,
        timer: 15 + Math.random() * 20,
        isLaserParticle: true,
        color: '#00ffff',
        velocity: {
          x: Math.cos(angle) * (3 + Math.random() * 5),
          y: Math.sin(angle) * (3 + Math.random() * 5)
        }
      });
    }
    
    // 🚀 NEW: Create energy beam trail
    for (let i = 0; i < 8; i++) {
      const trailY = playerShip.y - i * 30;
      explosions.push({
        x: playerShip.x + playerShip.width / 2,
        y: trailY,
        size: 6 + Math.random() * 6,
        timer: 25 + Math.random() * 15,
        isLaserTrail: true,
        color: '#00ffff',
        trailIndex: i
      });
    }
    
    // 🚀 NEW: Create screen shake effect
    if (window.screenShake) {
      window.screenShake(15, 200); // Laser shake effect
    }
    
    // 🚀 NEW: Add energy drain effect
    createEnergyDrainEffect();
  }

  // 🚀 NEW: Create energy drain visual effect
  function createEnergyDrainEffect() {
    // Create energy particles flowing from player to laser
    for (let i = 0; i < 12; i++) {
      const startX = playerShip.x + playerShip.width / 2;
      const startY = playerShip.y + playerShip.height;
      const endX = startX;
      const endY = 0;
      
      explosions.push({
        x: startX,
        y: startY,
        size: 3 + Math.random() * 4,
        timer: 30 + Math.random() * 20,
        isEnergyDrain: true,
        color: '#00ffff',
        startX: startX,
        startY: startY,
        endX: endX,
        endY: endY,
        progress: 0,
        speed: 0.05 + Math.random() * 0.05
      });
    }
  }

  // 🚀 NEW: Create bomb explosion effect
  function createBombExplosion() {
    // Create massive explosion at player position
    explosions.push({
      x: playerShip.x + playerShip.width / 2,
      y: playerShip.y + playerShip.height / 2,
      size: 100,
      timer: 40,
      isBombExplosion: true
    });
    
    // Create multiple smaller explosions across the screen
    for (let i = 0; i < 8; i++) {
      explosions.push({
        x: Math.random() * canvasWidth,
        y: Math.random() * (canvasHeight * 0.8),
        size: 30 + Math.random() * 40,
        timer: 20 + Math.random() * 20,
        isBombSubExplosion: true
      });
    }
    
    // Screen shake effect
    if (window.screenShake) {
      window.screenShake(20, 300); // Intensity 20, duration 300ms
    }
  }

  // 🚀 NEW: Update weapon cooldowns
  function updateWeaponCooldowns() {
    Object.keys(weaponCooldowns).forEach(weapon => {
      if (weaponCooldowns[weapon] > 0) {
        weaponCooldowns[weapon]--;
      }
    });
  }

  function movePlayer(direction) {
    if (isSpaceInvadersPaused) return;
    
    // 🚀 NEW: Use enhanced speed system
    const moveAmount = getPlayerSpeed();
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
    
    // Auto-shoot when ship moves (with cooldown) - only if enabled AND using normal weapon
    if (autoShootEnabled && currentWeaponType === 'normal' && (oldX !== playerShip.x || oldY !== playerShip.y)) {
      const currentTime = Date.now();
      if (currentTime - lastPlayerShootTime > autoShootCooldown) {
        playerShoot();
        lastPlayerShootTime = currentTime;
      }
    }
  }

  // 🚀 NEW: Weapon switching controls
  function switchWeaponByKey(key) {
    switch (key) {
      case '1':
        switchWeapon('normal');
        break;
      case '2':
        switchWeapon('laser');
        break;
      case '3':
        switchWeapon('bomb');
        break;
    }
  }

  // 🚀 NEW: Activate speed boost with key
  function activateSpeedBoostByKey() {
    activateSpeedBoost();
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
    
    // 🚀 NEW: Handle weapon switching
    if (['1', '2', '3'].includes(e.key)) {
      if (typeof window.switchWeaponByKey === 'function') {
        window.switchWeaponByKey(e.key);
      }
      return;
    }
    
    // 🚀 NEW: Handle direct special weapon firing
    if (e.key === 'l' || e.key === 'L') {
      // Direct laser fire
      if (currentWeaponType === 'laser' && weaponAmmo.laser > 0) {
        window.isQuickShotCall = true;
        console.log('🚀 L key: Direct laser fire!');
        playerShoot();
      } else {
        console.log('⚠️ L key: No laser ammo or wrong weapon selected');
      }
      return;
    }
    
    if (e.key === 'b' || e.key === 'B') {
      // Direct bomb fire
      if (currentWeaponType === 'bomb' && weaponAmmo.bomb > 0) {
        window.isQuickShotCall = true;
        console.log('🚀 B key: Direct bomb launch!');
        playerShoot();
      } else {
        console.log('⚠️ B key: No bomb ammo or wrong weapon selected');
      }
      return;
    }
    
    // 🚀 NEW: Handle speed boost activation
    if (e.key === 's' || e.key === 'S') {
      if (typeof window.activateSpeedBoostByKey === 'function') {
        window.activateSpeedBoostByKey();
      }
      return;
    }
    
    // 🆘 NEW: Handle help system
    if (e.key === 'h' || e.key === 'H') {
      if (typeof window.toggleHelpOverlay === 'function') {
        window.toggleHelpOverlay();
      }
      return;
    }
    
    // Handle escape key for help overlay
    if (e.key === 'Escape') {
      if (helpOverlayVisible && typeof window.toggleHelpOverlay === 'function') {
        window.toggleHelpOverlay();
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
        if (typeof window.movePlayer === 'function') {
          window.movePlayer('down');
        }
        break;
      case ' ':
        // 🚀 NEW: Enhanced spacebar support for all weapon types
        if (typeof window.playerShoot === 'function') {
          // For special weapons, set the flag to allow shooting
          if (currentWeaponType === 'laser' || currentWeaponType === 'bomb') {
            window.isQuickShotCall = true;
            console.log(`🚀 Spacebar: Setting isQuickShotCall = true for ${currentWeaponType}`);
          }
          window.playerShoot();
        }
        break;
    }
  });

  // 🆘 REMOVED: Duplicate touch handling - using existing system below

  // 🎮 Make game functions globally available
  window.startGameWithCountdown = startGameWithCountdown;
  window.startGame = startGame;
  window.movePlayer = movePlayer;
  window.playerShoot = playerShoot;
  window.togglePause = togglePause;
  window.toggleAutoShoot = toggleAutoShoot;

  // 🚀 NEW: Make weapon system functions globally available
  window.switchWeapon = switchWeapon;
  window.switchWeaponByKey = switchWeaponByKey;
  window.activateSpeedBoost = activateSpeedBoost;
  window.activateSpeedBoostByKey = activateSpeedBoostByKey;

  // 🆘 NEW: Make help system functions globally available
  window.toggleHelpOverlay = toggleHelpOverlay;
  window.toggleMobileControls = toggleMobileControls;
  window.createEnhancedMobileControls = createEnhancedMobileControls;
  window.displayHelpInfoOutside = displayHelpInfoOutside;

  // 🎮 NEW: Make game panel functions globally available
  window.toggleGamePanel = toggleGamePanel;
  window.updateWeaponAmmoDisplay = updateWeaponAmmoDisplay;

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

// 🚀 NEW: Test weapon system
window.testWeaponSystem = function() {
  console.log('🧪 Testing Weapon System...');
  console.log('✅ Current weapon:', currentWeaponType);
  console.log('✅ Laser ammo:', weaponAmmo.laser);
  console.log('✅ Bomb ammo:', weaponAmmo.bomb);
  console.log('✅ Speed boost active:', speedBoostActive);
  return true;
};

// 🚀 NEW: Add missing functions that were referenced
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
  let holdShootInterval = null;
  let holdShootDelay = 150; // 150ms between shots for rapid fire
  let reloadButton = null; // 🚀 NEW: Floating reload button
  let reloadButtonInterval = null; // 🚀 NEW: Interval to check ammo and show/hide button

  function handleTouchStart(e) {
    if (e.target.closest("#space-invaders-canvas")) {
      e.preventDefault();
      e.stopPropagation();
      const touch = e.touches[0];
      touchStartX = touch.clientX;
      touchStartY = touch.clientY;
      isTouching = true;
      
      // Store touch start time for tap detection
      touchStartTime = Date.now();
      
      // 🚀 NEW: Start continuous shooting while holding (smart weapon handling)
      if (holdShootInterval) {
        clearInterval(holdShootInterval);
      }
      
      // Start rapid fire shooting based on weapon type
      holdShootInterval = setInterval(() => {
        if (isTouching && !isSpaceInvadersPaused) {
          // Smart shooting based on weapon type
          if (currentWeaponType === 'normal') {
            // Normal weapon: shoot continuously (infinite ammo)
            playerShoot();
          }
          // Special weapons (laser/bomb) can ONLY be fired through Quick Shot button
          // No automatic shooting through hold-to-shoot for special weapons
        }
      }, holdShootDelay);
      
      // First immediate shot (smart weapon handling)
      setTimeout(() => {
        if (isTouching && !isSpaceInvadersPaused) {
          if (currentWeaponType === 'normal') {
            playerShoot();
          }
          // Special weapons require Quick Shot button
        }
      }, 50); // Small delay for first shot
    }
  }

  function handleTouchMove(e) {
    if (isTouching && e.target.closest("#space-invaders-canvas")) {
      e.preventDefault();
      e.stopPropagation();
      const touch = e.touches[0];
      const deltaX = touch.clientX - touchStartX;
      const deltaY = touch.clientY - touchStartY;
      
      // 🆘 IMPROVED: More responsive touch controls
      // Horizontal movement - reduced sensitivity for better control
      if (Math.abs(deltaX) > 8) {
        movePlayer(deltaX > 0 ? 'right' : 'left');
        touchStartX = touch.clientX;
        // Auto-shoot is handled in movePlayer function
      }
      
      // Vertical movement - reduced sensitivity for better control
      if (Math.abs(deltaY) > 8) {
        movePlayer(deltaY > 0 ? 'down' : 'up');
        touchStartY = touch.clientY;
        // Auto-shoot is handled in movePlayer function
      }
      
      // 🆘 IMPROVED: Better shooting detection (smart weapon handling)
      // Quick swipe up to shoot (like modern mobile games)
      if (deltaY < -15) {
        if (currentWeaponType === 'normal') {
          playerShoot();
        }
        // Special weapons (laser/bomb) can ONLY be fired through Quick Shot button
        // Reset touch to prevent multiple shots
        touchStartY = touch.clientY;
      }
      
      // 🆘 NEW: Quick swipe down for special action (bomb only)
      if (deltaY > 25) {
        // Swipe down only works with bomb weapon and available ammo
        if (currentWeaponType === 'bomb' && weaponAmmo.bomb > 0) {
          playerShoot(); // This will use bomb if selected
        }
        touchStartY = touch.clientY;
      }
    }
  }

  function handleTouchEnd(e) {
    // 🆘 IMPROVED: Better tap-to-shoot detection
    if (isTouching) {
      e.preventDefault();
      e.stopPropagation();
      const touchDuration = Date.now() - touchStartTime;
      const touch = e.changedTouches[0];
      const deltaX = Math.abs(touch.clientX - touchStartX);
      const deltaY = Math.abs(touch.clientY - touchStartY);
      
      // 🚀 NEW: Stop continuous shooting
      if (holdShootInterval) {
        clearInterval(holdShootInterval);
        holdShootInterval = null;
      }
      
      // 🆘 IMPROVED: More forgiving tap detection for mobile (smart weapon handling)
      // If it's a quick tap (less than 200ms) with minimal movement (less than 12px)
      if (touchDuration < 200 && deltaX < 12 && deltaY < 12) {
        if (currentWeaponType === 'normal') {
          playerShoot();
          console.log('🎯 Tap-to-shoot activated');
        }
        // Special weapons (laser/bomb) can ONLY be fired through Quick Shot button
      }
      
      // 🆘 NEW: Long press detection for special actions
      if (touchDuration > 500 && deltaX < 15 && deltaY < 15) {
        // Long press could activate speed boost or special weapon
        if (speedBoostAmmo > 0 && !speedBoostActive) {
          activateSpeedBoost();
          console.log('⚡ Long press activated speed boost');
        }
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
      // 🆘 NEW: Ensure mobile controls are visible when resuming game
      setTimeout(() => {
        ensureMobileControlsVisible();
      }, 50);
    }
  }

  function toggleAutoShoot() {
    autoShootEnabled = !autoShootEnabled;
    console.log(`🎯 Auto-shoot ${autoShootEnabled ? 'enabled' : 'disabled'}`);
  }

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
    mobileAutoShootBtn.textContent = autoShootEnabled ? "🎯 Auto: ON" : "�� Auto: OFF";
    });
  }

  // 🎮 Touch controls setup
  enableGlobalSpaceInvadersTouch();
  // Don't lock scroll immediately - only lock when game starts

  // 🚀 NEW: Create floating reload button for special weapons
  function createReloadButton() {
    if (reloadButton) {
      document.body.removeChild(reloadButton);
    }
    
    reloadButton = document.createElement('button');
    reloadButton.id = 'reload-button';
    reloadButton.innerHTML = `
      <div style="font-size: 1.2em; margin-bottom: 5px;">🚀</div>
      <div style="font-size: 0.9em; margin-bottom: 3px;">QUICK SHOT</div>
      <div style="font-size: 0.8em; color: #9ca3af;" id="reload-button-ammo">Loading...</div>
    `;
    
    reloadButton.style.cssText = `
      position: fixed;
      bottom: 120px;
      right: 25px;
      width: 85px;
      height: 85px;
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: white;
      border: 3px solid #dc2626;
      border-radius: 50%;
      font-size: 1.1em;
      font-weight: bold;
      cursor: pointer;
      z-index: 999;
      box-shadow: 0 8px 25px rgba(0,0,0,0.5), 0 0 20px rgba(239, 68, 68, 0.3);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: none;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      line-height: 1.2;
      -webkit-touch-callout: none;
      -webkit-user-select: none;
      -khtml-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
      -webkit-tap-highlight-color: transparent;
      touch-action: manipulation;
    `;
    
    // Add hover effects
    reloadButton.addEventListener('mouseenter', () => {
      reloadButton.style.transform = 'scale(1.15) rotate(8deg)';
      reloadButton.style.boxShadow = '0 12px 35px rgba(0,0,0,0.6), 0 0 30px rgba(239, 68, 68, 0.5)';
    });
    
    reloadButton.addEventListener('mouseleave', () => {
      reloadButton.style.transform = 'scale(1) rotate(0deg)';
      reloadButton.style.boxShadow = '0 8px 25px rgba(0,0,0,0.5), 0 0 20px rgba(239, 68, 68, 0.3)';
    });
    
    // Add click to handle both functions (shoot special weapon OR toggle auto-shoot)
    reloadButton.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      handleReloadButtonClick();
    });
    
    // Prevent touch events from causing screenshots
    reloadButton.addEventListener('touchstart', (e) => {
      e.preventDefault();
      e.stopPropagation();
    }, { passive: false });
    
    reloadButton.addEventListener('touchend', (e) => {
      e.preventDefault();
      e.stopPropagation();
      handleReloadButtonClick();
    }, { passive: false });
    
    document.body.appendChild(reloadButton);
    
    // Start monitoring ammo and showing/hiding button
    startReloadButtonMonitoring();
  }
  
  // 🚀 NEW: Handle reload button click based on current function
  function handleReloadButtonClick() {
    if (!reloadButton) return;
    
    const buttonFunction = reloadButton.dataset.function;
    
    if (buttonFunction === 'toggle') {
      // Normal weapon mode - toggle auto-shoot
      toggleAutoShoot();
      console.log('🎯 Auto-shoot toggled through Quick Shot button');
      // Update button immediately after toggle
      setTimeout(() => updateReloadButton(), 100);
    } else if (buttonFunction === 'shoot') {
      // Special weapon mode - shoot weapon
      shootSpecialWeapon();
    }
  }
  
  // 🚀 NEW: Shoot special weapon based on current selection (only when ammo available)
  function shootSpecialWeapon() {
    console.log(`🚀 Quick shot called for weapon: ${currentWeaponType}, ammo:`, weaponAmmo);
    
    if (currentWeaponType === 'laser' && weaponAmmo.laser > 0) {
      // Set flag to allow laser shooting
      window.isQuickShotCall = true;
      console.log('🚀 Setting isQuickShotCall = true for laser');
      playerShoot(); // This will use laser
      console.log('🚀 Quick shot: Laser fired!');
      // Update button immediately after shooting
      setTimeout(() => updateReloadButton(), 100);
    } else if (currentWeaponType === 'bomb' && weaponAmmo.bomb > 0) {
      // Set flag to allow bomb shooting
      window.isQuickShotCall = true;
      console.log('🚀 Setting isQuickShotCall = true for bomb');
      playerShoot(); // This will use bomb
      console.log('🚀 Quick shot: Bomb launched!');
      // Update button immediately after shooting
      setTimeout(() => updateReloadButton(), 100);
    } else {
      console.log('⚠️ No special weapon ammo available');
      // Show visual feedback that button is disabled
      if (reloadButton) {
        reloadButton.style.transform = 'scale(0.95)';
        setTimeout(() => {
          if (reloadButton) {
            reloadButton.style.transform = 'scale(1)';
          }
        }, 150);
      }
    }
  }
  
  // 🚀 NEW: Start monitoring ammo and showing/hiding reload button
  function startReloadButtonMonitoring() {
    if (reloadButtonInterval) {
      clearInterval(reloadButtonInterval);
    }
    
    reloadButtonInterval = setInterval(() => {
      if (reloadButton && !isSpaceInvadersPaused) {
        updateReloadButton();
      }
    }, 500); // Check every 500ms
  }
  
  // 🚀 NEW: Update reload button based on current weapon and ammo (always visible)
  function updateReloadButton() {
    if (!reloadButton) return;
    
    let ammoText = '';
    let buttonColor = '';
    let isActive = false;
    let buttonFunction = 'shoot'; // Default function
    
    if (currentWeaponType === 'laser' && weaponAmmo.laser > 0) {
      ammoText = `Laser: ${weaponAmmo.laser}`;
      buttonColor = 'linear-gradient(135deg, #3b82f6, #2563eb)';
      isActive = true;
      buttonFunction = 'shoot';
    } else if (currentWeaponType === 'bomb' && weaponAmmo.bomb > 0) {
      ammoText = `Bomb: ${weaponAmmo.bomb}`;
      buttonColor = 'linear-gradient(135deg, #f59e0b, #d97706)';
      isActive = true;
      buttonFunction = 'shoot';
    } else if (currentWeaponType === 'laser') {
      ammoText = 'Laser: 0';
      buttonColor = 'linear-gradient(135deg, #6b7280, #4b5563)';
      isActive = false;
      buttonFunction = 'shoot';
    } else if (currentWeaponType === 'bomb') {
      ammoText = 'Bomb: 0';
      buttonColor = 'linear-gradient(135deg, #6b7280, #4b5563)';
      isActive = false;
      buttonFunction = 'shoot';
    } else {
      // Normal weapon mode - button becomes Auto-Shoot toggle
      ammoText = `Auto: ${autoShootEnabled ? 'ON' : 'OFF'}`;
      buttonColor = autoShootEnabled ? 
        'linear-gradient(135deg, #10b981, #059669)' : 
        'linear-gradient(135deg, #6b7280, #4b5563)';
      isActive = true; // Always active for normal weapon
      buttonFunction = 'toggle';
    }
    
    // Always show the button
    reloadButton.style.display = 'flex';
    reloadButton.style.background = buttonColor;
    
    // Update button text and icon based on function
    const buttonIcon = reloadButton.querySelector('div:first-child');
    const buttonTitle = reloadButton.querySelector('div:nth-child(2)');
    
    if (buttonIcon && buttonTitle) {
      if (buttonFunction === 'toggle') {
        buttonIcon.innerHTML = '🎯';
        buttonTitle.textContent = 'AUTO-SHOOT';
      } else {
        buttonIcon.innerHTML = '🚀';
        buttonTitle.textContent = 'QUICK SHOT';
      }
    }
    
    // Update ammo text
    const ammoElement = reloadButton.querySelector('#reload-button-ammo');
    if (ammoElement) {
      ammoElement.textContent = ammoText;
    }
    
    // Update button interactivity
    if (isActive) {
      reloadButton.style.cursor = 'pointer';
      reloadButton.style.opacity = '1';
    } else {
      reloadButton.style.cursor = 'not-allowed';
      reloadButton.style.opacity = '0.6';
    }
    
    // Store current function for click handling
    reloadButton.dataset.function = buttonFunction;
  }
  
  // 🧹 Cleanup function
  function cleanupSpaceInvadersControls() {
    // Note: We can't easily remove the specific keydown listener since it's anonymous
    // The browser will clean it up when the page is unloaded
    disableGlobalSpaceInvadersTouch();
    unlockSpaceInvadersScroll();
    
    // 🚀 NEW: Clean up hold-to-shoot interval
    if (holdShootInterval) {
      clearInterval(holdShootInterval);
      holdShootInterval = null;
    }
    
    // 🚀 NEW: Clean up reload button
    if (reloadButtonInterval) {
      clearInterval(reloadButtonInterval);
      reloadButtonInterval = null;
    }
    
    if (reloadButton) {
      document.body.removeChild(reloadButton);
      reloadButton = null;
    }
  }

  // 🆘 NEW: Toggle help overlay
  function toggleHelpOverlay() {
    helpOverlayVisible = !helpOverlayVisible;
    
    // Create help overlay if it doesn't exist
    if (helpOverlayVisible && !document.getElementById('help-overlay')) {
      createHelpOverlay();
    }
    
    // Show/hide overlay
    const helpOverlay = document.getElementById('help-overlay');
    if (helpOverlay) {
      helpOverlay.style.display = helpOverlayVisible ? 'block' : 'none';
    }
    
    console.log(`📖 Help overlay ${helpOverlayVisible ? 'shown' : 'hidden'}`);
  }

  // 🆘 NEW: Create help overlay
  function createHelpOverlay() {
    const overlay = document.createElement('div');
    overlay.id = 'help-overlay';
    overlay.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.9);
      z-index: 1000;
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: Arial, sans-serif;
      color: white;
    `;
    
    const content = document.createElement('div');
    content.style.cssText = `
      background: #1a1a1a;
      border: 2px solid #fbbf24;
      border-radius: 15px;
      padding: 30px;
      max-width: 90%;
      max-height: 90%;
      overflow-y: auto;
      text-align: center;
    `;
    
    content.innerHTML = `
      <h1 style="color: #fbbf24; margin-bottom: 20px; font-size: 2em;">🧀 SPACE CHEESE INVADERS - HOW TO PLAY</h1>
      
      <div style="text-align: left; margin-bottom: 20px;">
        <h2 style="color: #fbbf24; border-bottom: 1px solid #fbbf24; padding-bottom: 5px;">🎮 MOVEMENT CONTROLS</h2>
        <p><strong>WASD Keys:</strong> W=Up, A=Left, S=Down, D=Right</p>
        <p><strong>Arrow Keys:</strong> ↑=Up, ←=Left, ↓=Down, →=Right</p>
        <p><strong>Mobile:</strong> Swipe or tap directional buttons</p>
      </div>
      
      <div style="text-align: left; margin-bottom: 20px;">
        <h2 style="color: #fbbf24; border-bottom: 1px solid #fbbf24; padding-bottom: 5px;">🔫 WEAPON SYSTEM</h2>
        <p><strong>1 Key:</strong> Normal Cheese Bullets (Unlimited)</p>
        <p><strong>2 Key:</strong> Laser Beam (5 ammo, pierces enemies)</p>
        <p><strong>3 Key:</strong> Bomb (3 ammo, clears screen)</p>
        <p><strong>Space Bar:</strong> Shoot current weapon</p>
        <p><strong>Mobile:</strong> Tap shoot button or swipe up</p>
      </div>
      
      <div style="text-align: left; margin-bottom: 20px;">
        <h2 style="color: #fbbf24; border-bottom: 1px solid #fbbf24; padding-bottom: 5px;">⚡ POWER-UPS & SPECIALS</h2>
        <p><strong>S Key:</strong> Activate Speed Boost (2x speed)</p>
        <p><strong>Green ⚡:</strong> Speed Boost power-up</p>
        <p><strong>Cyan 🔫:</strong> Laser ammo refill</p>
        <p><strong>Magenta 🔫:</strong> Bomb ammo refill</p>
      </div>
      
      <div style="text-align: left; margin-bottom: 20px;">
        <h2 style="color: #fbbf24; border-bottom: 1px solid #fbbf24; padding-bottom: 5px;">🎯 GAME FEATURES</h2>
        <p><strong>T Key:</strong> Toggle Auto-shoot</p>
        <p><strong>P Key:</strong> Pause/Resume game</p>
        <p><strong>Auto-shoot:</strong> Automatically fires when moving</p>
        <p><strong>Weak Points:</strong> Hit glowing eyes/DNA for bonus points</p>
      </div>
      
      <div style="text-align: left; margin-bottom: 20px;">
        <h2 style="color: #fbbf24; border-bottom: 1px solid #fbbf24; padding-bottom: 5px;">📱 MOBILE CONTROLS</h2>
        <p><strong>Touch Movement:</strong> Swipe in any direction to move</p>
        <p><strong>Shooting:</strong> Swipe up or tap shoot button</p>
        <p><strong>Weapon Switch:</strong> Use weapon buttons below game</p>
        <p><strong>Speed Boost:</strong> Tap speed boost button</p>
      </div>
      
      <div style="text-align: left; margin-bottom: 20px;">
        <h2 style="color: #fbbf24; border-bottom: 1px solid #fbbf24; padding-bottom: 5px;">🎮 GAMEPLAY TIPS</h2>
        <p><strong>Formation Phase:</strong> Take time to aim and destroy invaders</p>
        <p><strong>Attack Phase:</strong> Dodge falling invaders and their bullets</p>
        <p><strong>Weapon Strategy:</strong> Save bombs for emergency situations</p>
        <p><strong>Speed Boost:</strong> Use to escape dangerous situations</p>
        <p><strong>Weak Points:</strong> Prioritize invaders with glowing weak points</p>
      </div>
      
      <div style="text-align: center; margin-top: 30px;">
        <button id="close-help-btn" style="
          background: #fbbf24;
          color: #1a1a1a;
          border: none;
          padding: 15px 30px;
          border-radius: 25px;
          font-size: 1.2em;
          font-weight: bold;
          cursor: pointer;
          transition: all 0.3s ease;
        " onmouseover="this.style.background='#f59e0b'" onmouseout="this.style.background='#fbbf24'">
          🎮 GOT IT! LET'S PLAY!
        </button>
      </div>
    `;
    
    overlay.appendChild(content);
    document.body.appendChild(overlay);
    
    // Add close button functionality
    document.getElementById('close-help-btn').addEventListener('click', toggleHelpOverlay);
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && helpOverlayVisible) {
        toggleHelpOverlay();
      }
    });
  }

  // 🆘 NEW: Toggle mobile controls visibility
  function toggleMobileControls() {
    mobileControlsVisible = !mobileControlsVisible;
    
    const mobileControls = document.getElementById('mobile-controls');
    if (mobileControls) {
      mobileControls.style.display = mobileControlsVisible ? 'flex' : 'none';
    }
    
    // Update toggle button text
    const toggleBtn = document.getElementById('mobile-controls-toggle');
    if (toggleBtn) {
      toggleBtn.textContent = mobileControlsVisible ? '📱 Hide Controls' : '📱 Show Controls';
    }
    
    // 🆘 IMPROVED: Update floating toggle button appearance
    const toggleFloat = document.getElementById('mobile-controls-toggle-float');
    if (toggleFloat) {
      if (mobileControlsVisible) {
        toggleFloat.style.background = '#fbbf24';
        toggleFloat.textContent = '🎮';
      } else {
        toggleFloat.style.background = '#6b7280';
        toggleFloat.textContent = '🎮';
      }
    }
    
    console.log(`📱 Mobile controls ${mobileControlsVisible ? 'shown' : 'hidden'}`);
  }

  // 🆘 NEW: Create enhanced mobile controls - IMPROVED FOR BETTER MOBILE UX
  function createEnhancedMobileControls() {
    console.log('🎮 Creating enhanced mobile controls...');
    
    // 🆘 IMPROVED: Check if controls already exist to prevent duplicates
    if (document.getElementById('game-panel-btn')) {
      console.log('✅ Enhanced mobile controls already exist, skipping creation');
      return;
    }
    
    const mobileControls = document.getElementById('mobile-controls');
    if (!mobileControls) {
      console.warn('⚠️ Mobile controls container not found, creating fallback container');
      // Create fallback container if it doesn't exist
      const fallbackContainer = document.createElement('div');
      fallbackContainer.id = 'mobile-controls';
      fallbackContainer.className = 'hidden fixed bottom-4 left-1/2 transform -translate-x-1/2 z-50 bg-black/90 backdrop-blur-md border border-yellow-400/30 rounded-xl p-4 shadow-2xl max-w-sm w-full';
      document.body.appendChild(fallbackContainer);
      console.log('✅ Created fallback mobile controls container');
    }
    
    console.log('✅ Mobile controls container ready, creating game panel...');
    
    // Clear existing content
    mobileControls.innerHTML = '';
    
    // 🆘 NEW: Create floating game panel button (bottom right)
    const gamePanelBtn = document.createElement('button');
    gamePanelBtn.id = 'game-panel-btn';
    gamePanelBtn.innerHTML = '🎮<br><span style="font-size: 0.7em;">GAME PANEL</span>';
    gamePanelBtn.style.cssText = `
      position: fixed;
      bottom: 25px;
      right: 25px;
      width: 85px;
      height: 85px;
      background: linear-gradient(135deg, #fbbf24, #f59e0b);
      color: #1a1a1a;
      border: 3px solid #f59e0b;
      border-radius: 50%;
      font-size: 1.3em;
      font-weight: bold;
      cursor: pointer;
      z-index: 1000;
      box-shadow: 0 8px 25px rgba(0,0,0,0.5), 0 0 20px rgba(251, 191, 36, 0.3);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      line-height: 1.2;
      -webkit-touch-callout: none;
      -webkit-user-select: none;
      -khtml-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
      -webkit-tap-highlight-color: transparent;
      touch-action: manipulation;
    `;
    
    // Add hover effects
    gamePanelBtn.addEventListener('mouseenter', () => {
      gamePanelBtn.style.transform = 'scale(1.15) rotate(8deg)';
      gamePanelBtn.style.boxShadow = '0 12px 35px rgba(0,0,0,0.6), 0 0 30px rgba(251, 191, 36, 0.5)';
    });
    
    gamePanelBtn.addEventListener('mouseleave', () => {
      gamePanelBtn.style.transform = 'scale(1) rotate(0deg)';
      gamePanelBtn.style.boxShadow = '0 8px 25px rgba(0,0,0,0.5), 0 0 20px rgba(251, 191, 36, 0.3)';
    });
    
    // Add click to open game panel
    gamePanelBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      toggleGamePanel();
    });
    
    // Prevent touch events from causing screenshots
    gamePanelBtn.addEventListener('touchstart', (e) => {
      e.preventDefault();
      e.stopPropagation();
    }, { passive: false });
    
    gamePanelBtn.addEventListener('touchend', (e) => {
      e.preventDefault();
      e.stopPropagation();
      toggleGamePanel();
    }, { passive: false });
    
    document.body.appendChild(gamePanelBtn);
    
    console.log('✅ Game panel button created and added to body');
    console.log('🎮 Game panel button position:', gamePanelBtn.style.position, gamePanelBtn.style.bottom, gamePanelBtn.style.right);
    console.log('🎮 Game panel button z-index:', gamePanelBtn.style.zIndex);
    
    // 🆘 NEW: Create popup game panel overlay
    const gamePanel = document.createElement('div');
    gamePanel.id = 'game-panel-overlay';
    gamePanel.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.85);
      z-index: 9999;
      display: none;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      -webkit-touch-callout: none;
      -webkit-user-select: none;
      -khtml-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
      -webkit-tap-highlight-color: transparent;
      touch-action: manipulation;
    `;
    
    // Create panel content
    const panelContent = document.createElement('div');
    panelContent.style.cssText = `
      background: linear-gradient(135deg, #1a1a1a, #374151);
      border: 3px solid #fbbf24;
      border-radius: 25px;
      padding: 30px;
      max-width: 90vw;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
      box-shadow: 0 20px 60px rgba(0,0,0,0.8), 0 0 40px rgba(251, 191, 36, 0.2);
      -webkit-touch-callout: none;
      -webkit-user-select: none;
      -khtml-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
      -webkit-tap-highlight-color: transparent;
      touch-action: manipulation;
    `;
    
    // Add close button
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '✕';
    closeBtn.style.cssText = `
      position: absolute;
      top: 20px;
      right: 20px;
      width: 35px;
      height: 35px;
      background: #ef4444;
      color: white;
      border: none;
      border-radius: 50%;
      font-size: 1.3em;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    `;
    closeBtn.addEventListener('click', function() {
      toggleGamePanel();
    });
    
    // Add hover effects to close button
    closeBtn.addEventListener('mouseenter', function() {
      this.style.transform = 'scale(1.1)';
      this.style.boxShadow = '0 6px 16px rgba(239, 68, 68, 0.6)';
    });
    
    closeBtn.addEventListener('mouseleave', function() {
      this.style.transform = 'scale(1)';
      this.style.boxShadow = '0 4px 12px rgba(239, 68, 68, 0.4)';
    });
    panelContent.appendChild(closeBtn);
    
    // Add panel title
    const panelTitle = document.createElement('h2');
    panelTitle.textContent = '🎮 GAME CONTROL PANEL';
    panelTitle.style.cssText = `
      color: #fbbf24;
      text-align: center;
      margin: 0 0 20px 0;
      font-size: 1.5em;
      text-shadow: 0 2px 4px rgba(0,0,0,0.5);
    `;
    panelContent.appendChild(panelTitle);
    
    // 🚀 NEW: Simple weapon selection buttons
    const weaponSection = document.createElement('div');
    weaponSection.style.cssText = `
      margin-bottom: 25px;
      text-align: center;
    `;
    
    const weaponTitle = document.createElement('h3');
    weaponTitle.textContent = '🔫 WEAPONS';
    weaponTitle.style.cssText = `
      color: #ffffff;
      margin: 0 0 15px 0;
      font-size: 1.2em;
    `;
    weaponSection.appendChild(weaponTitle);
    
    const weaponGrid = document.createElement('div');
    weaponGrid.className = 'weapon-grid';
    weaponGrid.style.cssText = `
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px;
      margin-bottom: 15px;
    `;
    
    const weaponTypes = [
      { type: 'normal', label: '1️⃣ NORMAL', color: '#6b7280', ammo: '∞' },
      { type: 'laser', label: '2️⃣ LASER', color: '#00ffff', ammo: weaponAmmo.laser },
      { type: 'bomb', label: '3️⃣ BOMB', color: '#ff00ff', ammo: weaponAmmo.bomb }
    ];
    
    weaponTypes.forEach((weapon, index) => {
      const weaponBtn = document.createElement('button');
      weaponBtn.id = `weapon-btn-${weapon.type}`;
      weaponBtn.innerHTML = `
        <div style="font-size: 1.1em; margin-bottom: 5px;">${weapon.label.split(' ')[0]}</div>
        <div style="font-size: 0.9em; margin-bottom: 3px;">${weapon.label.split(' ')[1]}</div>
        <div style="font-size: 0.8em; color: #9ca3af;">Ammo: ${weapon.ammo}</div>
      `;
      

      weaponBtn.style.cssText = `
        background: ${currentWeaponType === weapon.type ? '#fbbf24' : weapon.color};
        color: ${currentWeaponType === weapon.type ? '#1a1a1a' : 'white'};
        border: 3px solid ${currentWeaponType === weapon.type ? '#f59e0b' : weapon.color};
        border-radius: 18px;
        padding: 18px 12px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: bold;
        min-height: 90px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        box-shadow: ${currentWeaponType === weapon.type ? '0 0 25px rgba(251, 191, 36, 0.7)' : '0 6px 16px rgba(0, 0, 0, 0.15)'};
        transform: ${currentWeaponType === weapon.type ? 'scale(1.08)' : 'scale(1)'};
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
      `;
      
      weaponBtn.addEventListener('click', function() {
        if (typeof switchWeapon === 'function') {
          switchWeapon(weapon.type);
          // Update all weapon button colors and effects
          weaponGrid.querySelectorAll('button').forEach((btn, i) => {
            const weaponData = weaponTypes[i];
            const isSelected = currentWeaponType === weaponData.type;
            btn.style.background = isSelected ? '#fbbf24' : weaponData.color;
            btn.style.color = isSelected ? '#1a1a1a' : 'white';
            btn.style.borderColor = isSelected ? '#f59e0b' : weaponData.color;
            btn.style.borderWidth = isSelected ? '3px' : '2px';
            btn.style.boxShadow = isSelected ? '0 0 20px rgba(251, 191, 36, 0.6)' : 'none';
            btn.style.transform = isSelected ? 'scale(1.05)' : 'scale(1)';
          });
          // Update ammo display
          updateWeaponAmmoDisplay();
        }
        
        // Close the game panel after weapon selection
        setTimeout(() => {
          toggleGamePanel();
        }, 300);
      });
      
      weaponGrid.appendChild(weaponBtn);
    });
    
    weaponSection.appendChild(weaponGrid);
    panelContent.appendChild(weaponSection);
    

    
    // 🚀 NEW: Power-ups section with auto-shoot and speed boost
    const powerUpsSection = document.createElement('div');
    powerUpsSection.style.cssText = `
      margin-bottom: 25px;
      text-align: center;
    `;
    
    const powerUpsTitle = document.createElement('h3');
    powerUpsTitle.textContent = '⚡ POWER-UPS';
    powerUpsTitle.style.cssText = `
      color: #ffffff;
      margin: 0 0 15px 0;
      font-size: 1.2em;
    `;
    powerUpsSection.appendChild(powerUpsTitle);
    
    const powerUpsGrid = document.createElement('div');
    powerUpsGrid.style.cssText = `
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 15px;
      margin-bottom: 15px;
    `;
    
    // Auto-shoot button
    const autoShootBtn = document.createElement('button');
    autoShootBtn.innerHTML = `
      <div style="font-size: 1.1em; margin-bottom: 5px;">🎯</div>
      <div style="font-size: 0.9em; margin-bottom: 3px;">AUTO-SHOOT</div>
      <div style="font-size: 0.8em; color: #9ca3af;">${autoShootEnabled ? 'ON' : 'OFF'}</div>
    `;
    autoShootBtn.style.cssText = `
      background: ${autoShootEnabled ? '#10b981' : '#6b7280'};
      color: white;
      border: 2px solid ${autoShootEnabled ? '#059669' : '#6b7280'};
      border-radius: 18px;
      padding: 18px 12px;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      font-weight: bold;
      min-height: 90px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    `;
    
    autoShootBtn.addEventListener('click', function() {
      if (typeof toggleAutoShoot === 'function') {
        toggleAutoShoot();
        // Update button appearance
        autoShootBtn.style.background = autoShootEnabled ? '#10b981' : '#6b7280';
        autoShootBtn.style.borderColor = autoShootEnabled ? '#059669' : '#6b7280';
        autoShootBtn.querySelector('div:last-child').textContent = autoShootEnabled ? 'ON' : 'OFF';
      }
      
      // Close panel after selection
      setTimeout(() => {
        toggleGamePanel();
      }, 300);
    });
    
    // Speed boost button
    const speedBoostBtn = document.createElement('button');
    speedBoostBtn.innerHTML = `
      <div style="font-size: 1.1em; margin-bottom: 5px;">⚡</div>
      <div style="font-size: 0.9em; margin-bottom: 3px;">SPEED BOOST</div>
      <div style="font-size: 0.8em; color: #9ca3af;">Available: ${speedBoostAmmo}</div>
    `;
    speedBoostBtn.style.cssText = `
      background: ${speedBoostAmmo > 0 ? '#f59e0b' : '#6b7280'};
      color: white;
      border: 2px solid ${speedBoostAmmo > 0 ? '#d97706' : '#6b7280'};
      border-radius: 18px;
      padding: 18px 12px;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      font-weight: bold;
      min-height: 90px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    `;
    
    speedBoostBtn.addEventListener('click', function() {
      if (speedBoostAmmo > 0) {
        if (typeof activateSpeedBoost === 'function') {
          activateSpeedBoost();
          // Update button appearance
          speedBoostBtn.style.background = '#6b7280';
          speedBoostBtn.style.borderColor = '#6b7280';
          speedBoostBtn.querySelector('div:last-child').textContent = 'Available: 0';
        }
      }
      
      // Close panel after activation
      setTimeout(() => {
        toggleGamePanel();
      }, 300);
    });
    
    powerUpsGrid.appendChild(autoShootBtn);
    powerUpsGrid.appendChild(speedBoostBtn);
    powerUpsSection.appendChild(powerUpsGrid);
    panelContent.appendChild(powerUpsSection);
    
    // 🚀 NEW: Quick actions section
    const quickActionsSection = document.createElement('div');
    quickActionsSection.style.cssText = `
      margin-bottom: 25px;
      text-align: center;
    `;
    
    const quickActionsTitle = document.createElement('h3');
    quickActionsTitle.textContent = '🎮 QUICK ACTIONS';
    quickActionsTitle.style.cssText = `
      color: #ffffff;
      margin: 0 0 15px 0;
      font-size: 1.2em;
    `;
    quickActionsSection.appendChild(quickActionsTitle);
    
    const quickActionsGrid = document.createElement('div');
    quickActionsGrid.style.cssText = `
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 15px;
      margin-bottom: 15px;
    `;
    
    // Manual shoot button
    const manualShootBtn = document.createElement('button');
    manualShootBtn.innerHTML = `
      <div style="font-size: 1.1em; margin-bottom: 5px;">🎯</div>
      <div style="font-size: 0.9em; margin-bottom: 3px;">MANUAL SHOOT</div>
      <div style="font-size: 0.8em; color: #9ca3af;">Tap to fire</div>
    `;
    manualShootBtn.style.cssText = `
      background: #3b82f6;
      color: white;
      border: 2px solid #2563eb;
      border-radius: 18px;
      padding: 18px 12px;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      font-weight: bold;
      min-height: 90px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    `;
    
    manualShootBtn.addEventListener('click', function() {
      if (typeof playerShoot === 'function') {
        playerShoot();
      }
      
      // Close panel after action
      setTimeout(() => {
        toggleGamePanel();
      }, 300);
    });
    
    // Help button
    const helpBtn = document.createElement('button');
    helpBtn.innerHTML = `
      <div style="font-size: 1.1em; margin-bottom: 5px;">❓</div>
      <div style="font-size: 0.9em; margin-bottom: 3px;">HELP</div>
      <div style="font-size: 0.8em; color: #9ca3af;">Game info</div>
    `;
    helpBtn.style.cssText = `
      background: #8b5cf6;
      color: white;
      border: 2px solid #7c3aed;
      border-radius: 18px;
      padding: 18px 12px;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      font-weight: bold;
      min-height: 90px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    `;
    
    helpBtn.addEventListener('click', function() {
      // Close panel after action
      setTimeout(() => {
        toggleGamePanel();
      }, 300);
    });
    
    quickActionsGrid.appendChild(manualShootBtn);
    quickActionsGrid.appendChild(helpBtn);
    quickActionsSection.appendChild(quickActionsGrid);
    panelContent.appendChild(quickActionsSection);
    
    console.log('✅ Game panel overlay created with weapon grid, power-ups, and quick actions');
    
    // Add panel content to overlay
    gamePanel.appendChild(panelContent);
    document.body.appendChild(gamePanel);
    
    // 🚀 REMOVED: Overly aggressive touch prevention that was blocking button clicks
    console.log('✅ Game panel ready for button interactions');
    
    // 🆘 IMPROVED: Add swipe instruction
    const swipeInstruction = document.createElement('div');
    swipeInstruction.style.cssText = `
      text-align: center;
      margin-bottom: 15px;
      padding: 10px;
      background: #374151;
      border-radius: 10px;
      border: 1px solid #fbbf24;
    `;
    swipeInstruction.innerHTML = `
      <div style="color: #fbbf24; font-weight: bold; margin-bottom: 5px;">🎮 MOBILE CONTROLS</div>
      <div style="color: #9ca3af; font-size: 0.8em;">
        • Swipe left/right to move ship<br>
        • Swipe up/down for vertical movement<br>
        • Tap to shoot (single shot)<br>
        • 🚀 <strong>HOLD to shoot continuously!</strong><br>
        • Use 🎮 GAME PANEL for all controls
      </div>
    `;
    mobileControls.appendChild(swipeInstruction);
    
    // Add quick help text
    const helpText = document.createElement('div');
    helpText.style.cssText = `
      text-align: center;
      margin-top: 15px;
      font-size: 0.8em;
      color: #9ca3af;
      max-width: 300px;
    `;
    helpText.innerHTML = `
      <p><strong>💡 Quick Access:</strong></p>
      <p>🎮 <strong>Tap the GAME PANEL button</strong> (bottom right) to access all controls</p>
      <p>📱 <strong>No need to pause</strong> - use controls while playing!</p>
      <p>⚡ <strong>Quick weapon switching</strong> and power-ups</p>
      <p>🎯 <strong>All features</strong> in one convenient panel</p>
      <p><strong>🚀 NEW: Hold-to-Shoot!</strong></p>
      <p>🎯 <strong>Hold your finger</strong> on the screen for continuous rapid fire!</p>
      <p>⚡ <strong>150ms delay</strong> between shots for smooth gameplay</p>
      <p><strong>🔫 Smart Weapon System:</strong></p>
      <p>• <strong>Normal:</strong> Infinite ammo, shoots on movement</p>
      <p>• <strong>Laser:</strong> Limited ammo, only shoots when you want</p>
      <p>• <strong>Bomb:</strong> Limited ammo, only shoots when you want</p>
      <p><strong>🚀 NEW: Smart Quick Shot Button!</strong></p>
      <p>🎯 <strong>Always visible</strong> - shows current weapon and ammo</p>
      <p>⚡ <strong>ONLY way to fire</strong> laser and bomb weapons!</p>
      <p>🎯 <strong>Auto-Shoot toggle</strong> when using Normal weapon!</p>
      <p>🔫 <strong>Normal weapon</strong> works with touch/swipe as usual</p>
      <p><strong>🖥️ NEW: Desktop Controls!</strong></p>
      <p>🎯 <strong>SPACEBAR:</strong> Shoot with current weapon (works with all weapons!)</p>
      <p>🔫 <strong>L key:</strong> Direct laser fire (if laser weapon selected)</p>
      <p>💣 <strong>B key:</strong> Direct bomb launch (if bomb weapon selected)</p>
      <p>⚡ <strong>1/2/3:</strong> Switch weapons instantly</p>
      <p>🎮 <strong>S key:</strong> Activate speed boost</p>
    `;
    mobileControls.appendChild(helpText);
    
    // 🚀 NEW: Create the floating reload button
    createReloadButton();
    
    console.log('🎮 Enhanced mobile controls with game panel and reload button created');
  }

  // 🆘 NEW: Function to ensure mobile controls are always visible when game starts
  function ensureMobileControlsVisible() {
    console.log('🎮 Ensuring mobile controls are visible...');
    
    // Check if the game panel button exists
    const gamePanelBtn = document.getElementById('game-panel-btn');
    if (!gamePanelBtn) {
      console.log('⚠️ Game panel button not found, creating enhanced mobile controls...');
      createEnhancedMobileControls();
      return;
    }
    
    // Ensure the game panel button is visible
    if (gamePanelBtn.style.display === 'none') {
      gamePanelBtn.style.display = 'flex';
      console.log('✅ Game panel button made visible');
    }
    
    // Ensure the mobile controls container is visible
    const mobileControls = document.getElementById('mobile-controls');
    if (mobileControls && mobileControls.style.display === 'none') {
      mobileControls.style.display = 'flex';
      console.log('✅ Mobile controls container made visible');
    }
    
    // Check if the reload button exists and is visible
    const reloadBtn = document.getElementById('reload-button');
    if (reloadBtn && reloadBtn.style.display === 'none') {
      reloadBtn.style.display = 'block';
      console.log('✅ Reload button made visible');
    }
    
    console.log('✅ Mobile controls visibility check complete');
  }

  // 🆘 NEW: Toggle game panel overlay
  function toggleGamePanel() {
    const gamePanel = document.getElementById('game-panel-overlay');
    if (gamePanel) {
      const isVisible = gamePanel.style.display === 'flex';
      gamePanel.style.display = isVisible ? 'none' : 'flex';
      
      // Update weapon ammo display when opening
      if (!isVisible) {
        updateWeaponAmmoDisplay();
      }
    }
  }

  // 🚀 NEW: Update weapon ammo display in game panel
  function updateWeaponAmmoDisplay() {
    const weaponGrid = document.querySelector('#game-panel-overlay .weapon-grid');
    if (weaponGrid) {
      const weaponButtons = weaponGrid.querySelectorAll('button');
      
      weaponButtons.forEach((btn, index) => {
        const weaponTypes = ['normal', 'laser', 'bomb'];
        const weaponType = weaponTypes[index];
        const ammoElement = btn.querySelector('div:last-child');
        if (ammoElement) {
          if (weaponType === 'normal') {
            ammoElement.textContent = 'Ammo: ∞';
          } else {
            ammoElement.textContent = `Ammo: ${weaponAmmo[weaponType]}`;
          }
        }
      });
    }
    
    // Update speed boost display
    const speedBtn = document.querySelector('#game-panel-overlay button[onclick*="activateSpeedBoost"]');
    if (speedBtn) {
      const ammoElement = speedBtn.querySelector('div:last-child');
      if (ammoElement) {
        ammoElement.textContent = `Available: ${speedBoostAmmo}`;
      }
    }
  }

  // 🆘 REMOVED: Old mobile controls toggle function - replaced with game panel system

  // 🚀 NEW: Test help system
  window.testHelpSystem = function() {
    console.log('🧪 Testing Help System...');
    console.log('✅ Help overlay visible:', helpOverlayVisible);
    console.log('✅ Mobile controls visible:', mobileControlsVisible);
    console.log('✅ Help functions available:', {
      toggleHelpOverlay: typeof window.toggleHelpOverlay === 'function',
      toggleMobileControls: typeof window.toggleMobileControls === 'function',
      createEnhancedMobileControls: typeof window.createEnhancedMobileControls === 'function'
    });
    return true;
  };

  // 🆘 NEW: Display help information outside game canvas
  function displayHelpInfoOutside() {
    // Find or create the help info container
    let helpContainer = document.getElementById('help-info-container');
    if (!helpContainer) {
      helpContainer = document.createElement('div');
      helpContainer.id = 'help-info-container';
      helpContainer.style.cssText = `
        background: #1a1a1a;
        border: 2px solid #fbbf24;
        border-radius: 10px;
        padding: 20px;
        margin: 20px auto;
        max-width: 800px;
        text-align: center;
        font-family: Arial, sans-serif;
        color: white;
      `;
      
      // Insert after the game canvas
      const gameContainer = document.getElementById('space-cheese-invaders');
      if (gameContainer) {
        gameContainer.parentNode.insertBefore(helpContainer, gameContainer.nextSibling);
      }
    }
    
    // Update help information
    helpContainer.innerHTML = `
      <h3 style="color: #fbbf24; margin-bottom: 15px; font-size: 1.3em;">🎮 GAME CONTROLS & HELP</h3>
      
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; text-align: left;">
        <div>
          <h4 style="color: #4ade80; border-bottom: 1px solid #4ade80; padding-bottom: 5px;">🎯 MOVEMENT & SHOOTING</h4>
          <p><strong>WASD Keys:</strong> W=Up, A=Left, S=Down, D=Right</p>
          <p><strong>Arrow Keys:</strong> ↑=Up, ←=Left, ↓=Down, →=Right</p>
          <p><strong>Space Bar:</strong> Shoot current weapon</p>
          <p><strong>Mobile:</strong> Swipe or tap directional buttons</p>
        </div>
        
        <div>
          <h4 style="color: #fbbf24; border-bottom: 1px solid #fbbf24; padding-bottom: 5px;">🔫 WEAPON SYSTEM</h4>
          <p><strong>1 Key:</strong> Normal Cheese Bullets (Unlimited)</p>
          <p><strong>2 Key:</strong> Laser Beam (5 ammo, pierces enemies)</p>
          <p><strong>3 Key:</strong> Bomb (3 ammo, clears screen)</p>
          <p><strong>Mobile:</strong> Use weapon buttons below game</p>
        </div>
        
        <div>
          <h4 style="color: #8b5cf6; border-bottom: 1px solid #8b5cf6; padding-bottom: 5px;">⚡ POWER-UPS & SPECIALS</h4>
          <p><strong>S Key:</strong> Activate Speed Boost (2x speed)</p>
          <p><strong>Green ⚡:</strong> Speed Boost power-up</p>
          <p><strong>Cyan 🔫:</strong> Laser ammo refill</p>
          <p><strong>Magenta 🔫:</strong> Bomb ammo refill</p>
        </div>
        
        <div>
          <h4 style="color: #ef4444; border-bottom: 1px solid #ef4444; padding-bottom: 5px;">🎮 GAME FEATURES</h4>
          <p><strong>T Key:</strong> Toggle Auto-shoot</p>
          <p><strong>P Key:</strong> Pause/Resume game</p>
          <p><strong>H Key:</strong> Show detailed help overlay</p>
          <p><strong>Escape:</strong> Close help overlay</p>
        </div>
      </div>
      
      <div style="margin-top: 20px; padding: 15px; background: rgba(251, 191, 36, 0.1); border-radius: 8px;">
        <h4 style="color: #fbbf24; margin-top: 0;">💡 GAMEPLAY TIPS</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; text-align: left;">
          <div>
            <p style="margin: 5px 0;"><strong>🎯 Formation Phase:</strong></p>
            <p style="margin: 5px 0;">• Take time to aim and destroy invaders</p>
            <p style="margin: 5px 0;">• Line up shots for maximum efficiency</p>
            <p style="margin: 5px 0;">• Use this time to plan your strategy</p>
          </div>
          <div>
            <p style="margin: 5px 0;"><strong>🚀 Attack Phase:</strong></p>
            <p style="margin: 5px 0;">• Dodge falling invaders and their bullets</p>
            <p style="margin: 5px 0;">• Use speed boost to escape danger</p>
            <p style="margin: 5px 0;">• Save bombs for emergency situations</p>
          </div>
          <div>
            <p style="margin: 5px 0;"><strong>⚡ Power Strategy:</strong></p>
            <p style="margin: 5px 0;">• Hit glowing weak points for bonus points</p>
            <p style="margin: 5px 0;">• Collect power-ups when safe</p>
            <p style="margin: 5px 0;">• Manage weapon ammo wisely</p>
          </div>
        </div>
      </div>
      
      <div style="margin-top: 20px; padding: 15px; background: rgba(139, 92, 246, 0.1); border-radius: 8px;">
        <h4 style="color: #8b5cf6; margin-top: 0;">🎮 GAME CONTROLS SUMMARY</h4>
        <p style="margin: 5px 0;"><strong>Movement:</strong> WASD/Arrows to move (up/down/left/right)</p>
        <p style="margin: 5px 0;"><strong>Shooting:</strong> Space to shoot, T to toggle auto-shoot</p>
        <p style="margin: 5px 0;"><strong>Weapons:</strong> 1=Normal, 2=Laser, 3=Bomb, S=Speed Boost</p>
        <p style="margin: 5px 0;"><strong>Game:</strong> P to pause, H for help, Escape to close help</p>
        <p style="margin: 5px 0;"><strong>Mobile:</strong> Use buttons below or swipe on canvas to move and shoot</p>
      </div>
      
      <div style="margin-top: 20px;">
        <button onclick="window.toggleHelpOverlay()" style="
          background: #8b5cf6;
          color: white;
          border: none;
          padding: 12px 25px;
          border-radius: 20px;
          font-size: 1.1em;
          font-weight: bold;
          cursor: pointer;
          margin: 0 10px;
          transition: all 0.3s ease;
        " onmouseover="this.style.background='#7c3aed'" onmouseout="this.style.background='#8b5cf6'">
          📖 DETAILED HELP OVERLAY
        </button>
        
        <button onclick="window.toggleMobileControls()" style="
          background: #fbbf24;
          color: #1a1a1a;
          border: none;
          padding: 12px 25px;
          border-radius: 20px;
          font-size: 1.1em;
          font-weight: bold;
          cursor: pointer;
          margin: 0 10px;
          transition: all 0.3s ease;
        " onmouseover="this.style.background='#f59e0b'" onmouseout="this.style.background='#fbbf24'">
          📱 TOGGLE MOBILE CONTROLS
        </button>
      </div>
    `;
  }

  // 🚀 NEW: Spawn invaders when Tetris blocks are destroyed
  function spawnInvadersFromTetris(tetrisItem) {
    const invaderCount = 2 + Math.floor(Math.random() * 3); // 2-4 invaders
    console.log(`🧀 Tetris block destroyed! Spawning ${invaderCount} new invaders!`);
    
    for (let i = 0; i < invaderCount; i++) {
      // Spawn invaders around the destroyed Tetris block
      const spawnX = tetrisItem.x + (Math.random() - 0.5) * 100;
      const spawnY = tetrisItem.y + (Math.random() - 0.5) * 60;
      
      // Create new invader with aggressive patterns
      const newInvader = createInvader(
        Math.max(0, Math.min(canvasWidth - 30, spawnX)),
        Math.max(50, Math.min(canvasHeight - 100, spawnY)),
        Math.floor(Math.random() * 3),
        'tetris_spawn'
      );
      
      // Make these invaders more dangerous
      newInvader.movePattern = Math.random() < 0.7 ? 'dive' : 'zigzag';
      newInvader.speed = 1.5 + Math.random() * 1.0; // Faster movement
      newInvader.points = 25 + Math.floor(Math.random() * 15); // More points
      
      invaders.push(newInvader);
    }
  }