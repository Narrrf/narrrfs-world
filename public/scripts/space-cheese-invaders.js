// 🧀 Space Cheese Invaders v3.6 - PHOENIX CONFIGURATION LOADING FIX - TIMESTAMP: ${Date.now()}
// Much slower invaders (1 second drop, 1 minute break) with Tetris block danger items
// NEW: Auto-shoot feature - automatically fires when ship moves (toggle with 'T' key)
// NEW: Laser shot type, Speed boost power-up, and Bomb weapon
// 
// 🔧 CRITICAL FIXES APPLIED (2025-01-28):
// - Fixed 300px ship jump when mouse re-enters canvas
// - Improved mouse target positioning system
// - Added global mouse coordinate tracking
// - Enhanced mouse re-entry positioning logic
// - Added safety checks for invalid mouse targets
// - Improved bounds checking and movement smoothing
// 
// 🚀 SHIP POSITIONING FIXES (2025-01-28):
// - Fixed ship starting position: moved from canvasHeight-60 to canvasHeight-120
// - Added boundary constraints to mouse movement for proper ship positioning
// - Ship can now reach bottom border and has better movement range
// - Increased space between ship and invaders from 60px to 230px
// 
// 🚀 GLOBAL MOUSE TRACKING FIX (2025-01-28):
// - Ship now follows mouse even when cursor leaves game container
// - Full movement range available including bottom border
// - Smooth control regardless of mouse position
// - Visual feedback for global tracking mode (orange border)
// - Perfect desktop gaming experience with unlimited mouse range
// 
// 🚀 EXTENDED BOTTOM BOUNDARY FIX (2025-01-28):
// - Ship can now move 20px beyond canvas bottom boundary
// - Extended movement range for better gameplay positioning
// - Allows ship to reach green line marker for optimal positioning
// - Enhanced vertical movement freedom for Phoenix-style gameplay
// 
// 🔥 PHOENIX INVADERS INTEGRATION (2025-01-28):
// - NEW: Phoenix wave system every 3rd wave
// - Phoenix birds with formation flying patterns (V, diamond, spiral, cluster, dive)
// - Egg-laying mechanics that hatch into mini-Phoenix enemies
// - Strategic gameplay: destroy eggs before they hatch
// - Admin interface configuration through Boss Management tab
// - Full collision detection and scoring system
// 
// 🔥 PHOENIX CONFIGURATION LOADING FIX (2025-01-28):
// - FIXED: Game now loads Phoenix configuration from admin interface
// - Phoenix waves spawn every 3rd wave (configurable in admin)
// - Visual indicators show when Phoenix waves are active
// - Debug functions available in browser console for testing

// 🚀 PRODUCTION CONFIGURATION: Epic boss progression!
// 🏆 Boss types: Wave 10=Cheese King, Wave 25=Cheese Emperor, Wave 75=Cheese God, Wave 100=Cheese Destroyer
// 🎯 Balanced difficulty curve for challenging but achievable progression!

// 🌍 Environment Detection for API calls
const isProduction = window.location.hostname === 'narrrfs.world';
const API_BASE_URL = isProduction ? 'https://narrrfs.world' : 'http://localhost/narrrfs-world';
console.log('🌍 Space Invaders Environment detected:', isProduction ? 'Production' : 'Local');
console.log('🔗 Space Invaders API Base URL:', API_BASE_URL);

// 📱 Mobile Detection for Control Priority
const isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
console.log('📱 Device type detected:', isMobileDevice ? 'Mobile' : 'Desktop');

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
let spaceInvadersCount = 0; // NEW: Track actual invader count for DSPOINC calculation

// 🚀 NEW: Progressive Multi-Shot Upgrades
let hasDoubleShotUpgrade = false; // Unlocked after defeating first boss (Cheese King)
let hasTripleShotUpgrade = false; // Unlocked after defeating second boss (Cheese Emperor)
let hasQuadShotUpgrade = false; // Unlocked after defeating third boss (Cheese God)

// 🔥 PHOENIX INVADERS SYSTEM - NEW FEATURE!
// Phoenix wave system that alternates with regular invader waves
let phoenixWaves = [];
let phoenixEggs = [];
let miniPhoenixes = [];
let isPhoenixWave = false;
let phoenixWaveConfig = {
  // 🎯 PROFESSIONAL GAME BALANCE: Strategic Phoenix wave placement
  waveFrequency: 5,        // Every 5th wave (3, 8, 13, 18, 23...)
  basePhoenixCount: 3,     // 🔥 BALANCED: 3 Phoenix for early waves
  difficultyScaling: 1.05, // 🔥 BALANCED: Gentle scaling for smooth progression
  eggLayingRate: 0.12,     // 🔥 BALANCED: 12% chance (reduced for balance)
  formationPatterns: ['v', 'diamond', 'spiral'], // 🔥 BALANCED: Progressive pattern unlocking
  maxPhoenixPerWave: 12,   // 🔥 BALANCED: Increased for late-game waves
  eggHatchTime: 500,       // 🔥 BALANCED: 5 seconds before hatching
  miniPhoenixHealth: 15,   // 🔥 BALANCED: 15 HP for mini-Phoenixes
  phoenixHealth: 30        // 🔥 BALANCED: 30 HP base (scales with waves)
};

// 🔥 PHOENIX CONFIGURATION LOADING - NEW!
// Load Phoenix configuration from admin interface when game starts
async function loadPhoenixConfiguration() {
  try {
    console.log('🔥 Loading Phoenix configuration from admin interface...');
    const response = await fetch(`${API_BASE_URL}/api/admin/phoenix-configuration.php`);
    if (response.ok) {
      const data = await response.json();
      if (data.success && data.data) {
        console.log('✅ Phoenix configuration loaded:', data.data);
        // Update the game's Phoenix configuration with admin settings
        phoenixWaveConfig = {
          ...phoenixWaveConfig,  // Keep defaults as fallback
          ...data.data           // Override with admin settings
        };
        console.log('🔥 Phoenix configuration updated:', phoenixWaveConfig);
      } else {
        console.log('⚠️ No Phoenix configuration found, using defaults');
      }
    } else {
      console.log('⚠️ Could not load Phoenix configuration, using defaults');
    }
  } catch (error) {
    console.log('⚠️ Error loading Phoenix configuration:', error);
    console.log('🔥 Using default Phoenix configuration');
  }
}

// 🔥 PHOENIX DEBUG FUNCTIONS - NEW!
// Add these to browser console to debug Phoenix system
window.debugPhoenixSystem = function() {
  console.log('🔥 PHOENIX SYSTEM DEBUG INFO:');
  console.log('Phoenix waves array:', phoenixWaves);
  console.log('Phoenix eggs array:', phoenixEggs);
  console.log('Mini-Phoenix array:', miniPhoenixes);
  console.log('Is Phoenix wave:', isPhoenixWave);
  console.log('Phoenix config:', phoenixWaveConfig);
  console.log('Current wave number:', waveNumber);
  console.log('Wave frequency:', phoenixWaveConfig.waveFrequency);
  console.log('Should be Phoenix wave:', waveNumber % phoenixWaveConfig.waveFrequency === 0);
};

window.forcePhoenixWave = function() {
  console.log('🔥 FORCING PHOENIX WAVE...');
  isPhoenixWave = true;
  
  // 🔥 CRITICAL FIX: Clear existing entities first
  phoenixWaves = [];
  phoenixEggs = [];
  miniPhoenixes = [];
  
  spawnPhoenixWave();
  console.log('🔥 Phoenix wave spawned!');
  console.log('🔥 Phoenix entities created:', {
    phoenixWaves: phoenixWaves.length,
    phoenixEggs: phoenixEggs.length,
    miniPhoenixes: miniPhoenixes.length
  });
  
  // 🔍 DEBUG: Log Phoenix entity details
  if (phoenixWaves.length > 0) {
    console.log('🔥 First Phoenix bird position:', phoenixWaves[0].x, phoenixWaves[0].y);
  }
  if (phoenixEggs.length > 0) {
    console.log('🥚 First Phoenix egg position:', phoenixEggs[0].x, phoenixEggs[0].y);
  }
  if (miniPhoenixes.length > 0) {
    console.log('🐤 First mini-Phoenix position:', miniPhoenixes[0].x, miniPhoenixes[0].y);
  }
};

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

    // 🔥 PHOENIX IMAGES - NEW!
const phoenixBirdImg = new Image();
phoenixBirdImg.src = 'img/space/phoenix-bird.png';
console.log('🔥 Loading Phoenix bird image from:', phoenixBirdImg.src);
phoenixBirdImg.onload = () => {
  console.log('✅ Phoenix bird image loaded successfully');
  console.log('🔥 Image dimensions:', phoenixBirdImg.naturalWidth, 'x', phoenixBirdImg.naturalHeight);
};
phoenixBirdImg.onerror = (e) => {
  console.error('❌ Failed to load Phoenix bird image:', e);
  console.error('❌ Attempted path:', phoenixBirdImg.src);
  console.log('🔥 Using fallback rectangle drawing for Phoenix birds');
};

const phoenixEggImg = new Image();
phoenixEggImg.src = 'img/space/phoenix-egg.png';
console.log('🥚 Loading Phoenix egg image from:', phoenixEggImg.src);
phoenixEggImg.onload = () => {
  console.log('✅ Phoenix egg image loaded successfully');
  console.log('🥚 Image dimensions:', phoenixEggImg.naturalWidth, 'x', phoenixEggImg.naturalHeight);
};
phoenixEggImg.onerror = (e) => {
  console.error('❌ Failed to load Phoenix egg image:', e);
  console.error('❌ Attempted path:', phoenixEggImg.src);
  console.log('🔥 Using fallback rectangle drawing for Phoenix eggs');
};

const miniPhoenixImg = new Image();
miniPhoenixImg.src = 'img/space/mini-phoenix.png';
console.log('🐤 Loading Mini Phoenix image from:', miniPhoenixImg.src);
miniPhoenixImg.onload = () => {
  console.log('✅ Mini Phoenix image loaded successfully');
  console.log('🐤 Image dimensions:', miniPhoenixImg.naturalWidth, 'x', miniPhoenixImg.naturalHeight);
};
miniPhoenixImg.onerror = (e) => {
  console.error('❌ Failed to load Mini Phoenix image:', e);
  console.error('❌ Attempted path:', miniPhoenixImg.src);
  console.log('🔥 Using fallback rectangle drawing for Mini Phoenix');
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

// 🚀 NEW: Load second cheese invader type for variety
const cheeseInvader2Img = new Image();
cheeseInvader2Img.src = 'img/space/cheese_invader2.png';
cheeseInvader2Img.onload = () => {
  console.log('✅ Cheese invader 2 image loaded successfully');
};
cheeseInvader2Img.onerror = (e) => {
  console.error('❌ Failed to load cheese invader 2 image:', e);
  console.error('❌ Attempted path:', cheeseInvader2Img.src);
};

// 🔥 PHOENIX INVADERS CLASSES
// Phoenix bird entity with formation flying and egg-laying mechanics
class PhoenixBird {
  constructor(x, y, formation, difficulty) {
    this.x = x;
    this.y = y;
    this.formation = formation;
    this.difficulty = difficulty;
    this.width = 60;  // 🔥 CRITICAL FIX: Add dimensions
    this.height = 60; // 🔥 CRITICAL FIX: Add dimensions
    this.health = (phoenixWaveConfig.phoenixHealth || 80) * difficulty;
    this.maxHealth = this.health;
    this.speed = phoenixWaveConfig.phoenixSpeed || 2.0;
    this.eggLayingCooldown = 0;
    this.flightPattern = this.generateFlightPattern();
    this.animationFrame = 0;
    this.animationSpeed = 0.1;
    this.isDead = false;
    this.explosionTimer = 0;
    
    console.log(`🔥 PhoenixBird created at x=${x}, y=${y}, health=${this.health}`);
  }
  
  generateFlightPattern() {
    const patterns = {
      'v': this.createVFormation.bind(this),
      'diamond': this.createDiamondFormation.bind(this),
      'spiral': this.createSpiralFormation.bind(this),
      'cluster': this.createClusterFormation.bind(this),
      'dive': this.createDiveFormation.bind(this)
    };
    
    return patterns[this.formation] || patterns['v'];
  }
  
  createVFormation() {
    // V-formation flying pattern
    const canvas = document.getElementById('space-invaders-canvas');
    const centerX = canvas ? canvas.width / 2 : 200;
    const centerY = 100;
    const vAngle = Math.PI / 4; // 45 degrees
    const vRadius = 150;
    
    this.targetX = centerX + Math.cos(vAngle) * vRadius;
    this.targetY = centerY + Math.sin(vAngle) * vRadius;
  }
  
  createDiamondFormation() {
    // Diamond formation pattern
    const centerX = canvasWidth / 2;
    const centerY = 120;
    const diamondSize = 120;
    
    this.targetX = centerX + (Math.random() - 0.5) * diamondSize;
    this.targetY = centerY + (Math.random() - 0.5) * diamondSize;
  }
  
  createSpiralFormation() {
    // Spiral formation pattern
    const canvas = document.getElementById('space-invaders-canvas');
    const centerX = canvas ? canvas.width / 2 : 200;
    const centerY = 100;
    const time = Date.now() * 0.001;
    const spiralRadius = 100 + Math.sin(time * 0.5) * 30;
    const spiralAngle = time * 0.3;
    
    this.targetX = centerX + Math.cos(spiralAngle) * spiralRadius;
    this.targetY = centerY + Math.sin(spiralAngle) * spiralRadius;
  }
  
  createClusterFormation() {
    // Random cluster formation
    const canvas = document.getElementById('space-invaders-canvas');
    const centerX = canvas ? canvas.width / 2 : 200;
    const centerY = 100;
    const clusterRadius = 80;
    
    this.targetX = centerX + (Math.random() - 0.5) * clusterRadius;
    this.targetY = centerY + (Math.random() - 0.5) * clusterRadius;
  }
  
  createDiveFormation() {
    // Dive bombing pattern
    this.targetX = playerShip.x + (Math.random() - 0.5) * 100;
    this.targetY = playerShip.y - 50;
  }
  
  update() {
    if (this.isDead) {
      this.explosionTimer++;
      if (this.explosionTimer > 30) {
        return false; // Remove from array
      }
      return true;
    }
    
    // Update flight pattern
    this.flightPattern();
    
    // Move towards target
    const dx = this.targetX - this.x;
    const dy = this.targetY - this.y;
    const distance = Math.sqrt(dx * dx + dy * dy);
    
    if (distance > 5) {
      this.x += (dx / distance) * this.speed;
      this.y += (dy / distance) * this.speed;
    }
    
    // Update animation
    this.animationFrame += this.animationSpeed;
    if (this.animationFrame >= 4) this.animationFrame = 0;
    
    // Egg laying mechanics
    this.eggLayingCooldown--;
    if (this.eggLayingCooldown <= 0 && Math.random() < phoenixWaveConfig.eggLayingRate) {
      this.layEgg();
      this.eggLayingCooldown = 120; // 🔥 BALANCED: Increased from 60 to 120 (2 second cooldown)
    }
    
    return true;
  }
  
  layEgg() {
    const egg = new PhoenixEgg(this.x, this.y, this);
    phoenixEggs.push(egg);
    console.log('🥚 Phoenix laid egg at:', this.x, this.y);
  }
  
  takeDamage(damage) {
    this.health -= damage;
    if (this.health <= 0) {
      this.die();
    }
  }
  
  die() {
    this.isDead = true;
    this.explosionTimer = 0;
    console.log('🔥 Phoenix destroyed!');
    
    // Add explosion effect
    createExplosion(this.x, this.y, 40, 25);
    
    // Award points
    spaceInvadersScore += 100 * this.difficulty;
    spaceInvadersCount++;
  }
  
  draw(ctx) {
    if (this.isDead) {
      // Draw explosion
      const explosionSize = 20 + this.explosionTimer;
      ctx.fillStyle = `rgba(255, ${255 - this.explosionTimer * 8}, 0, ${1 - this.explosionTimer / 30})`;
      ctx.beginPath();
      ctx.arc(this.x + this.width / 2, this.y + this.height / 2, explosionSize, 0, Math.PI * 2);
      ctx.fill();
      return;
    }
    
    // 🔥 PHOENIX DRAWING: Use image if available, fallback to rectangle
    if (typeof phoenixBirdImg !== 'undefined' && phoenixBirdImg.complete && phoenixBirdImg.naturalWidth > 0) {
      // Draw Phoenix bird image
      ctx.drawImage(phoenixBirdImg, this.x, this.y, this.width, this.height);
      console.log('🔥 Drawing Phoenix bird with image');
    } else {
      // 🔥 FALLBACK: Draw Phoenix bird as colored rectangle
      ctx.fillStyle = '#ff6b35'; // Phoenix orange
      ctx.fillRect(this.x, this.y, this.width, this.height);
      
      // Add Phoenix details
      ctx.fillStyle = '#ff4500'; // Darker orange
      ctx.fillRect(this.x + 5, this.y + 5, this.width - 10, this.height - 10);
      
      // Phoenix eyes
      ctx.fillStyle = '#ffff00'; // Yellow eyes
      ctx.fillRect(this.x + 15, this.y + 20, 8, 8);
      ctx.fillRect(this.x + 37, this.y + 20, 8, 8);
      
      // Phoenix wings
      ctx.fillStyle = '#ff8c42'; // Wing color
      ctx.fillRect(this.x - 10, this.y + 15, 15, 20);
      ctx.fillRect(this.x + this.width - 5, this.y + 15, 15, 20);
      console.log('🔥 Drawing Phoenix bird with fallback rectangle');
    }
    
    // Draw health bar
    if (this.health < this.maxHealth) {
      const healthBarWidth = this.width;
      const healthBarHeight = 4;
      const healthPercentage = this.health / this.maxHealth;
      
      ctx.fillStyle = '#ff0000'; // Red background
      ctx.fillRect(this.x, this.y - 10, healthBarWidth, healthBarHeight);
      
      ctx.fillStyle = '#00ff00'; // Green health
      ctx.fillRect(this.x, this.y - 10, healthBarWidth * healthPercentage, healthBarHeight);
    }
    
    // Debug: Show Phoenix position
    ctx.fillStyle = '#ffffff';
    ctx.font = '10px Arial';
    ctx.fillText(`P:${Math.round(this.x)},${Math.round(this.y)}`, this.x, this.y - 15);
  }
}

// Phoenix egg entity that hatches into mini-Phoenix enemies
class PhoenixEgg {
  constructor(x, y, parentPhoenix) {
    this.x = x;
    this.y = y;
    this.width = 16;  // 🔥 CRITICAL FIX: Add width for collision detection
    this.height = 16; // 🔥 CRITICAL FIX: Add height for collision detection
    this.parentPhoenix = parentPhoenix;
    this.hatchTimer = phoenixWaveConfig.eggHatchTime;
    this.isDestroyed = false;
    this.animationFrame = 0;
    this.animationSpeed = 0.2;
  }
  
  update() {
    if (this.isDestroyed) return false;
    
    this.hatchTimer--;
    this.animationFrame += this.animationSpeed;
    if (this.animationFrame >= 4) this.animationFrame = 0;
    
    if (this.hatchTimer <= 0) {
      this.hatch();
      return false; // Remove from array
    }
    
    return true;
  }
  
  hatch() {
    // Spawn mini-Phoenix enemy
    const miniPhoenix = new MiniPhoenix(this.x, this.y);
    miniPhoenixes.push(miniPhoenix);
    console.log('🐣 Egg hatched into mini-Phoenix!');
    
    // Add hatching effect
    createExplosion(this.x, this.y, 20, 15);
  }
  
  takeDamage(damage) {
    this.isDestroyed = true;
    console.log('💥 Egg destroyed!');
    
    // Award bonus points for destroying egg
    spaceInvadersScore += 50;
    
    // Add destruction effect
    createExplosion(this.x, this.y, 15, 10);
  }
  
  draw(ctx) {
    if (this.isDestroyed) return;
    
    const size = 16;
    ctx.save();
    ctx.translate(this.x, this.y);
    
    // 🔥 PHOENIX EGG DRAWING: Use image if available, fallback to ellipse
    if (typeof phoenixEggImg !== 'undefined' && phoenixEggImg.complete && phoenixEggImg.naturalWidth > 0) {
      // Draw Phoenix egg image
      ctx.drawImage(phoenixEggImg, -size/2, -size/2, size, size);
      console.log('🥚 Drawing Phoenix egg with image');
    } else {
      // 🔥 FALLBACK: Draw Phoenix egg as colored ellipse
      ctx.fillStyle = '#ff8c42';
      ctx.beginPath();
      ctx.ellipse(0, 0, size/2, size/3, 0, 0, Math.PI * 2);
      ctx.fill();
      
      // Egg pattern
      ctx.fillStyle = '#ff4500';
      ctx.fillRect(-size/4, -size/6, size/2, size/3);
      
      // Hatching animation (cracks)
      if (this.hatchTimer < 60) { // Last second before hatching
        ctx.strokeStyle = '#ff0000';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(-size/3, -size/4);
        ctx.lineTo(size/3, size/4);
        ctx.stroke();
      }
      
      // Hatching timer indicator
      const timePercentage = this.hatchTimer / phoenixWaveConfig.eggHatchTime;
      ctx.fillStyle = `rgba(255, 0, 0, ${1 - timePercentage})`;
      ctx.fillRect(-size/2, -size/2 - 6, size, 3);
      console.log('🥚 Drawing Phoenix egg with fallback ellipse');
    }
    
    ctx.restore();
  }
}

// Mini-Phoenix enemy spawned from eggs
class MiniPhoenix {
  constructor(x, y) {
    this.x = x;
    this.y = y;
    this.width = 20;  // 🔥 CRITICAL FIX: Add width for collision detection
    this.height = 20; // 🔥 CRITICAL FIX: Add height for collision detection
    this.health = phoenixWaveConfig.miniPhoenixHealth;
    this.maxHealth = this.health;
    this.speed = 1.5;
    this.targetX = playerShip.x;
    this.targetY = playerShip.y;
    this.animationFrame = 0;
    this.animationSpeed = 0.3;
    this.isDead = false;
    this.explosionTimer = 0;
  }
  
  update() {
    if (this.isDead) {
      this.explosionTimer++;
      if (this.explosionTimer > 20) {
        return false; // Remove from array
      }
      return true;
    }
    
    // Move towards player
    const dx = this.targetX - this.x;
    const dy = this.targetY - this.y;
    const distance = Math.sqrt(dx * dx + dy * dy);
    
    if (distance > 10) {
      this.x += (dx / distance) * this.speed;
      this.y += (dy / distance) * this.speed;
    }
    
    // Update target (player position)
    this.targetX = playerShip.x;
    this.targetY = playerShip.y;
    
    // Update animation
    this.animationFrame += this.animationSpeed;
    if (this.animationFrame >= 4) this.animationFrame = 0;
    
    return true;
  }
  
  takeDamage(damage) {
    this.health -= damage;
    if (this.health <= 0) {
      this.die();
    }
  }
  
  die() {
    this.isDead = true;
    this.explosionTimer = 0;
    console.log('🔥 Mini-Phoenix destroyed!');
    
    // Add explosion effect
    createExplosion(this.x, this.y, 25, 20);
    
    // Award points
    spaceInvadersScore += 25;
    spaceInvadersCount++;
  }
  
  draw(ctx) {
    if (this.isDead) {
      // Draw explosion
      const explosionSize = 15 + this.explosionTimer;
      ctx.fillStyle = `rgba(255, ${255 - this.explosionTimer * 12}, 0, ${1 - this.explosionTimer / 20})`;
      ctx.beginPath();
      ctx.arc(this.x, this.y, explosionSize, 0, Math.PI * 2);
      ctx.fill();
      return;
    }
    
    // 🔥 MINI-PHOENIX DRAWING: Use image if available, fallback to rectangle
    const size = 20;
    ctx.save();
    ctx.translate(this.x, this.y);
    
    if (typeof miniPhoenixImg !== 'undefined' && miniPhoenixImg.complete && miniPhoenixImg.naturalWidth > 0) {
      // Draw mini-Phoenix image
      ctx.drawImage(miniPhoenixImg, -size/2, -size/2, size, size);
      console.log('🐤 Drawing mini-Phoenix with image');
    } else {
      // 🔥 FALLBACK: Draw mini-Phoenix as colored rectangle
      ctx.fillStyle = '#ff6347';
      ctx.fillRect(-size/2, -size/2, size, size);
      
      // Mini-Phoenix details
      ctx.fillStyle = '#ff4500';
      ctx.fillRect(-size/2, -size/2, size, size/3);
      
      // Wings animation
      ctx.fillStyle = '#ff8c42';
      const wingOffset = Math.sin(this.animationFrame) * 3;
      ctx.fillRect(-size/2 - wingOffset, -size/2, size/6, size);
      ctx.fillRect(size/2 + wingOffset, -size/2, size/6, size);
      console.log('🐤 Drawing mini-Phoenix with fallback rectangle');
    }
    
    // Health bar
    if (this.health < this.maxHealth) {
      const healthBarWidth = size;
      const healthBarHeight = 3;
      const healthPercentage = this.health / this.maxHealth;
      
      ctx.fillStyle = '#ff0000';
      ctx.fillRect(-size/2, -size/2 - 6, healthBarWidth, healthBarHeight);
      ctx.fillStyle = '#00ff00';
      ctx.fillRect(-size/2, -size/2 - 6, healthBarWidth * healthPercentage, healthBarHeight);
    }
    
    ctx.restore();
  }
}

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

// 🚀 NEW: Load all Power-Up Images
const powerUpImages = {
  speed: new Image(),
  laser: new Image(),
  bomb: new Image(),
  collect: new Image()
};

// Load all power-up images
powerUpImages.speed.src = 'img/space/powerup_speed.png';
powerUpImages.laser.src = 'img/space/powerup_laser.png';
powerUpImages.bomb.src = 'img/space/powerup_bomb.png';
powerUpImages.collect.src = 'img/space/powerup_collect.png';

// Power-up image loading callbacks
powerUpImages.speed.onload = () => console.log('✅ Speed power-up image loaded');
powerUpImages.laser.onload = () => console.log('✅ Laser power-up image loaded');
powerUpImages.bomb.onload = () => console.log('✅ Bomb power-up image loaded');
powerUpImages.collect.onload = () => console.log('✅ Collect power-up image loaded');

powerUpImages.speed.onerror = () => console.warn('⚠️ Failed to load speed power-up image');
powerUpImages.laser.onerror = () => console.warn('⚠️ Failed to load laser power-up image');
powerUpImages.bomb.onerror = () => console.warn('⚠️ Failed to load bomb power-up image');
powerUpImages.collect.onerror = () => console.warn('⚠️ Failed to load collect power-up image');

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

  // 🚀 NEW: Load Boss Images
  const bossImages = {
    cheeseKing: new Image(),
    cheeseEmperor: new Image(),
    cheeseGod: new Image(),
    cheeseDestroyer: new Image()
  };

  // Load all boss images
  bossImages.cheeseKing.src = 'img/space/Cheese_King.png';
  bossImages.cheeseEmperor.src = 'img/space/cheese_emporer.png';
  bossImages.cheeseGod.src = 'img/space/cheese_god.png';
  bossImages.cheeseDestroyer.src = 'img/space/cheese_destroyer.png';

  // Boss image loading callbacks
  bossImages.cheeseKing.onload = () => console.log('✅ Cheese King boss image loaded');
  bossImages.cheeseEmperor.onload = () => console.log('✅ Cheese Emperor boss image loaded');
  bossImages.cheeseGod.onload = () => console.log('✅ Cheese God boss image loaded');
  bossImages.cheeseDestroyer.onload = () => console.log('✅ Cheese Destroyer boss image loaded');

  bossImages.cheeseKing.onerror = () => console.warn('⚠️ Failed to load Cheese King boss image');
  bossImages.cheeseEmperor.onerror = () => console.warn('⚠️ Failed to load Cheese Emperor boss image');
  bossImages.cheeseGod.onerror = () => console.warn('⚠️ Failed to load Cheese God boss image');
  bossImages.cheeseDestroyer.onerror = () => console.warn('⚠️ Failed to load Cheese Destroyer boss image');

  // 🚀 NEW: BOSS CONFIGURATION SYSTEM - Admin editable boss settings
  // This system allows admins to customize each boss individually through the admin interface
  
  // Default boss configurations (can be overridden by admin settings)
  const DEFAULT_BOSS_CONFIGS = {
    cheeseKing: {
      name: 'Cheese King',
      description: 'The first boss - beginner-friendly tutorial boss',
      baseHealth: 80, // Much less health (was 120, originally 150)
      healthMultiplier: 1.0,
      baseSpeed: 1.2, // Even slower movement (was 1.8)
      speedMultiplier: 1.0,
      baseAttackCooldown: 1800, // Much slower attacks (was 1200)
      attackCooldownMultiplier: 1.0,
      baseBulletSpeed: 2.0, // Very slow bullets (was 2.5)
      bulletSpeedMultiplier: 1.0,
      baseBulletDamage: 1, // Keep low damage
      bulletDamageMultiplier: 1.0,
      size: 0.8,
      movementPatterns: ['sideways'], // Only simple movement
      attackPatterns: [0, 1], // Very simple attacks only
      abilities: {
        canTeleport: false, // Disabled teleport for easier fights
        canShield: false, // Disabled shield for easier fights
        canSummonMinions: false,
        canUseLaser: false,
        canCreateExplosions: false
      },
      specialAttackChance: 0.1, // Rarely uses special attacks (was 0.2)
      rageModeThreshold: 0.2, // Earlier rage mode but very mild
      rageModeMultipliers: {
        speed: 1.3, // Barely faster in rage (was 1.5)
        attackCooldown: 0.8, // Slightly faster attacks (was 0.6)
        bulletSpeed: 1.2, // Barely faster bullets (was 1.4)
        bulletDamage: 1.0 // No damage increase in rage (was 1.2)
      },
      colors: {
        primary: '#ff6b35',
        secondary: '#ff8c42',
        particles: '#ffdd00'
      }
    },
    
    cheeseEmperor: {
      name: 'Cheese Emperor',
      description: 'The second boss - balanced for double shot players',
      baseHealth: 200, // Reduced from 300 - beatable with double shot
      healthMultiplier: 1.0,
      baseSpeed: 2.0, // Reduced from 2.8 - more manageable
      speedMultiplier: 1.0,
      baseAttackCooldown: 800, // Increased from 600 - gives players time to react
      attackCooldownMultiplier: 1.0,
      baseBulletSpeed: 3.0, // Reduced from 3.8 - easier to dodge
      bulletSpeedMultiplier: 1.0,
      baseBulletDamage: 2, // Reduced from 3 - less punishing
      bulletDamageMultiplier: 1.0,
      size: 1.0,
      movementPatterns: ['sideways', 'hover'], // Removed circle for simplicity
      attackPatterns: [0, 1, 2], // Reduced from 5 to 3 patterns
      abilities: {
        canTeleport: false,
        canShield: false,
        canSummonMinions: true,
        canUseLaser: true,
        canCreateExplosions: false
      },
      specialAttackChance: 0.25, // Reduced from 0.4
      rageModeThreshold: 0.4, // Increased from 0.35 - later rage mode
      rageModeMultipliers: {
        speed: 1.8, // Reduced from 2.2
        attackCooldown: 0.5, // Increased from 0.35
        bulletSpeed: 1.5, // Reduced from 2.0
        bulletDamage: 1.5 // Reduced from 1.8
      },
      colors: {
        primary: '#8b5cf6',
        secondary: '#a78bfa',
        particles: '#c084fc'
      }
    },
    
    cheeseGod: {
      name: 'Cheese God',
      description: 'The third boss - balanced for triple shot players',
      baseHealth: 350, // Reduced from 500 - beatable with triple shot
      healthMultiplier: 1.0,
      baseSpeed: 2.5, // Reduced from 3.2 - manageable with triple shot
      speedMultiplier: 1.0,
      baseAttackCooldown: 600, // Increased from 500 - gives triple shot players time
      attackCooldownMultiplier: 1.0,
      baseBulletSpeed: 3.5, // Reduced from 4.5 - easier to dodge
      bulletSpeedMultiplier: 1.0,
      baseBulletDamage: 3, // Reduced from 4 - less punishing
      bulletDamageMultiplier: 1.0,
      size: 1.2,
      movementPatterns: ['sideways', 'zigzag', 'hover'], // Removed dash for balance
      attackPatterns: [0, 1, 2, 3], // Reduced from 5 to 4 patterns
      abilities: {
        canTeleport: false,
        canShield: true,
        canSummonMinions: false,
        canUseLaser: true,
        canCreateExplosions: true
      },
      specialAttackChance: 0.35, // Reduced from 0.5
      rageModeThreshold: 0.4, // Increased from 0.3 - later rage mode
      rageModeMultipliers: {
        speed: 2.0, // Reduced from 2.5
        attackCooldown: 0.4, // Increased from 0.3
        bulletSpeed: 1.8, // Reduced from 2.2
        bulletDamage: 1.6 // Reduced from 2.0
      },
      colors: {
        primary: '#f59e0b',
        secondary: '#fbbf24',
        particles: '#fde047'
      }
    },
    
    cheeseDestroyer: {
      name: 'Cheese Destroyer',
      description: 'The final boss - balanced for quad shot players',
      baseHealth: 600, // Reduced from 800 - beatable with quad shot
      healthMultiplier: 1.0,
      baseSpeed: 3.0, // Reduced from 3.5 - manageable with quad shot
      speedMultiplier: 1.0,
      baseAttackCooldown: 500, // Increased from 400 - gives quad shot players time
      attackCooldownMultiplier: 1.0,
      baseBulletSpeed: 4.0, // Reduced from 5.0 - easier to dodge
      bulletSpeedMultiplier: 1.0,
      baseBulletDamage: 4, // Reduced from 5 - less punishing
      bulletDamageMultiplier: 1.0,
      size: 1.5,
      movementPatterns: ['sideways', 'zigzag', 'hover', 'circle'], // Removed dash for balance
      attackPatterns: [0, 1, 2, 3, 4], // Keep all 5 patterns for final challenge
      abilities: {
        canTeleport: true,
        canShield: true,
        canSummonMinions: true,
        canUseLaser: true,
        canCreateExplosions: true
      },
      specialAttackChance: 0.45, // Reduced from 0.6
      rageModeThreshold: 0.35, // Increased from 0.25 - later rage mode
      rageModeMultipliers: {
        speed: 2.5, // Reduced from 3.0
        attackCooldown: 0.35, // Increased from 0.25
        bulletSpeed: 2.0, // Reduced from 2.5
        bulletDamage: 2.0 // Reduced from 2.5
      },
      colors: {
        primary: '#dc2626',
        secondary: '#ef4444',
        particles: '#fca5a5'
      }
    }
  };

  // 🚀 NEW: Function to get boss configuration (with admin override support)
  function getBossConfiguration(bossType) {
    // Get base configuration
    const baseConfig = DEFAULT_BOSS_CONFIGS[bossType];
    if (!baseConfig) {
      console.error(`❌ Unknown boss type: ${bossType}`);
      return DEFAULT_BOSS_CONFIGS.cheeseKing; // Fallback to Cheese King
    }
    
    // 🚀 NEW: Check for admin override settings (stored in localStorage)
    const adminOverrideKey = `boss_config_${bossType}`;
    const adminOverride = localStorage.getItem(adminOverrideKey);
    
    if (adminOverride) {
      try {
        const adminConfig = JSON.parse(adminOverride);
        console.log(`⚙️ Admin override found for ${bossType}:`, adminConfig);
        
        // Merge admin config with base config
        return {
          ...baseConfig,
          ...adminConfig,
          // Ensure abilities object is properly merged
          abilities: {
            ...baseConfig.abilities,
            ...(adminConfig.abilities || {})
          },
          // Ensure rage mode multipliers are properly merged
          rageModeMultipliers: {
            ...baseConfig.rageModeMultipliers,
            ...(adminConfig.rageModeMultipliers || {})
          },
          // Ensure colors are properly merged
          colors: {
            ...baseConfig.colors,
            ...(adminConfig.colors || {})
          }
        };
      } catch (error) {
        console.error(`❌ Error parsing admin boss config for ${bossType}:`, error);
        return baseConfig; // Fallback to base config
      }
    }
    
    return baseConfig;
  }

  // 🚀 NEW: Function to save boss configuration (for admin interface)
  function saveBossConfiguration(bossType, config) {
    try {
      const adminOverrideKey = `boss_config_${bossType}`;
      localStorage.setItem(adminOverrideKey, JSON.stringify(config));
      console.log(`💾 Boss configuration saved for ${bossType}:`, config);
      return true;
    } catch (error) {
      console.error(`❌ Error saving boss configuration for ${bossType}:`, error);
      return false;
    }
  }

  // 🚀 NEW: Function to reset boss configuration to defaults
  function resetBossConfiguration(bossType) {
    try {
      const adminOverrideKey = `boss_config_${bossType}`;
      localStorage.removeItem(adminOverrideKey);
      console.log(`🔄 Boss configuration reset to defaults for ${bossType}`);
      return true;
    } catch (error) {
      console.error(`❌ Error resetting boss configuration for ${bossType}:`, error);
      return false;
    }
  }

  // 🚀 NEW: Function to get all boss configurations (for admin interface)
  function getAllBossConfigurations() {
    const configs = {};
    Object.keys(DEFAULT_BOSS_CONFIGS).forEach(bossType => {
      configs[bossType] = getBossConfiguration(bossType);
    });
    return configs;
  }

  // 🚀 NEW: Load Boss Health Bar Image
  const bossHealthBarImg = new Image();
  bossHealthBarImg.src = 'img/space/boss_healthbar.png';
  bossHealthBarImg.onload = () => console.log('✅ Boss health bar image loaded');
  bossHealthBarImg.onerror = () => console.warn('⚠️ Failed to load boss health bar image');

  // 🚀 NEW: Load Cheese Bullet Images
  const cheeseBulletImages = [];
  for (let i = 0; i < 8; i++) {
    const bulletImg = new Image();
    bulletImg.src = `img/space/cheese-bullet_${i}.png`;
    bulletImg.onload = () => console.log(`✅ Cheese bullet ${i} image loaded`);
    bulletImg.onerror = () => console.warn(`⚠️ Failed to load cheese bullet ${i} image`);
    cheeseBulletImages.push(bulletImg);
  }

// 🎵 NEW: Cheese Sound Manager for epic audio effects
class CheeseSoundManager {
  constructor() {
    this.audioContext = null;
    this.soundEnabled = false; // 🚫 Start with sound DISABLED by default
    this.masterVolume = 0.7;
    this.initAudioContext();
  }

  // Initialize Web Audio API context
  initAudioContext() {
    try {
      // Create audio context on first user interaction
      if (typeof AudioContext !== 'undefined' || typeof webkitAudioContext !== 'undefined') {
        this.audioContext = new (AudioContext || webkitAudioContext)();
        console.log('✅ Audio context initialized successfully');
      } else {
        console.warn('⚠️ Web Audio API not supported');
      }
    } catch (error) {
      console.warn('⚠️ Audio context initialization failed:', error);
    }
  }

  // Create the iconic Star Wars laser sound with cheese twist
  playStarWarsLaser() {
    if (!this.soundEnabled || !this.audioContext) return;

    try {
      // Resume audio context if suspended (browser requirement)
      if (this.audioContext.state === 'suspended') {
        this.audioContext.resume();
      }

      const now = this.audioContext.currentTime;

      // Create oscillator for the laser sound
      const oscillator = this.audioContext.createOscillator();
      const gainNode = this.audioContext.createGain();
      
      // Connect nodes
      oscillator.connect(gainNode);
      gainNode.connect(this.audioContext.destination);

      // 🚀 AUTHENTIC STAR WARS LASER SOUND
      // The real Star Wars laser has a very specific character:
      // 1. High-pitched "pew" with a quick attack
      // 2. Slight pitch bend down
      // 3. Very short duration (~80ms)
      // 4. Clean, crisp sound (not filtered)
      
      // 🎯 AUTHENTIC STAR WARS FREQUENCY CHARACTERISTICS
      // Start at high frequency (like the real sound)
      oscillator.type = 'sine'; // Clean, pure tone like Star Wars
      oscillator.frequency.setValueAtTime(2200, now); // High "pew" frequency
      
      // 🎵 AUTHENTIC PITCH BEND (this is the key!)
      // The real Star Wars laser bends DOWN in pitch
      oscillator.frequency.exponentialRampToValueAtTime(
        1800, // Bend down to lower frequency
        now + 0.08 // Over 80ms duration
      );

      // 🎚️ AUTHENTIC ENVELOPE SHAPE
      // The real sound has a very quick attack and natural decay
      gainNode.gain.setValueAtTime(0, now);
      gainNode.gain.linearRampToValueAtTime(this.masterVolume, now + 0.005); // Super quick attack
      gainNode.gain.exponentialRampToValueAtTime(0.001, now + 0.08); // Natural decay

      // 🚀 PLAY THE AUTHENTIC SOUND
      oscillator.start(now);
      oscillator.stop(now + 0.08); // 80ms - exactly like Star Wars

      console.log('🔊 AUTHENTIC Star Wars laser sound played!');
      
    } catch (error) {
      console.warn('⚠️ Laser sound failed:', error);
    }
  }

  // 🚀 NEW: Even more authentic Star Wars laser sound variant
  playStarWarsLaserVariant() {
    if (!this.soundEnabled || !this.audioContext) return;

    try {
      // Resume audio context if suspended
      if (this.audioContext.state === 'suspended') {
        this.audioContext.resume();
      }

      const now = this.audioContext.currentTime;
      
      // 🎯 ULTRA-AUTHENTIC STAR WARS LASER
      // This variant uses multiple oscillators for that rich, full sound
      
      // Main oscillator (the "pew" sound)
      const mainOsc = this.audioContext.createOscillator();
      const mainGain = this.audioContext.createGain();
      
      // Harmonic oscillator (adds richness)
      const harmonicOsc = this.audioContext.createOscillator();
      const harmonicGain = this.audioContext.createGain();
      
      // Connect main oscillator
      mainOsc.connect(mainGain);
      mainGain.connect(this.audioContext.destination);
      
      // Connect harmonic oscillator
      harmonicOsc.connect(harmonicGain);
      harmonicGain.connect(this.audioContext.destination);

      // 🎵 MAIN OSCILLATOR - The iconic "pew"
      mainOsc.type = 'sine';
      mainOsc.frequency.setValueAtTime(2400, now); // Higher starting frequency
      mainOsc.frequency.exponentialRampToValueAtTime(1600, now + 0.06); // Bend down faster
      
      // 🎵 HARMONIC OSCILLATOR - Adds richness
      harmonicOsc.type = 'sine';
      harmonicOsc.frequency.setValueAtTime(4800, now); // 2x frequency for harmonic
      harmonicOsc.frequency.exponentialRampToValueAtTime(3200, now + 0.06); // Bend down proportionally
      
      // 🎚️ ENVELOPE SHAPES
      // Main oscillator envelope
      mainGain.gain.setValueAtTime(0, now);
      mainGain.gain.linearRampToValueAtTime(this.masterVolume, now + 0.003); // Ultra quick attack
      mainGain.gain.exponentialRampToValueAtTime(0.001, now + 0.06); // Quick decay
      
      // Harmonic oscillator envelope (slightly different timing)
      harmonicGain.gain.setValueAtTime(0, now);
      harmonicGain.gain.linearRampToValueAtTime(this.masterVolume * 0.3, now + 0.004); // Slightly delayed, quieter
      harmonicGain.gain.exponentialRampToValueAtTime(0.001, now + 0.07); // Harmonic trails off slightly
      
      // 🚀 PLAY BOTH OSCILLATORS
      mainOsc.start(now);
      mainOsc.stop(now + 0.06); // 60ms - ultra quick like real Star Wars
      
      harmonicOsc.start(now);
      harmonicOsc.stop(now + 0.07); // Harmonic trails off slightly

      console.log('🔊 ULTRA-AUTHENTIC Star Wars laser variant played!');
      
    } catch (error) {
      console.warn('⚠️ Laser variant failed:', error);
    }
  }

  // Toggle sound on/off
  toggleSound() {
    this.soundEnabled = !this.soundEnabled;
    console.log(`🔊 Sound ${this.soundEnabled ? 'enabled' : 'disabled'}`);
    
    // 🎨 Update UI styling based on sound state
    this.updateSoundToggleButton();
    
    return this.soundEnabled;
  }
  
  // 🎨 Update sound toggle button styling
  updateSoundToggleButton() {
    // Find all sound toggle buttons in the game UI
    const soundButtons = document.querySelectorAll('[data-sound-toggle]');
    soundButtons.forEach(btn => {
      btn.style.background = this.soundEnabled ? 
        'linear-gradient(135deg, #10b981, #059669)' : // Green when ON
        'linear-gradient(135deg, #6b7280, #4b5563)'; // Gray when OFF
    });
  }

  // Set master volume
  setVolume(volume) {
    this.masterVolume = Math.max(0, Math.min(1, volume));
    console.log(`🔊 Volume set to: ${this.masterVolume}`);
  }
}

// 🎵 Create global sound manager instance
const cheeseSoundManager = new CheeseSoundManager();

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
let autoShootCooldown = 150; // 🚀 FIX: Reduced from 300ms to 150ms for more responsive shooting
let autoShootEnabled = false; // NEW: Auto-shoot toggle (disabled by default)

// 🔥 CRITICAL FIX: Unified firing rate system for consistent heat buildup
let unifiedFiringRate = 60; // 60ms between ANY shots (16.7 shots/sec) - faster for better heat buildup!
let lastUnifiedShotTime = 0; // Track last shot time for unified system
let isUnifiedShotReady = true; // Whether a shot can be fired

// 🔥 CRITICAL FIX: Auto-shoot system with separate cooldown and no heat buildup
let autoShootFiringRate = 150; // 🚀 FIX: Reduced from 200ms to 150ms for more responsive shooting
let lastAutoShootTime = 0; // Track last auto-shoot time
let isAutoShootReady = true; // Whether auto-shoot can fire
let lastMovementPosition = { x: 0, y: 0 }; // Track last position for movement detection
// 🚀 FIX: Using existing autoShootCooldown variable for consistent cooldown

// 🚀 NEW: Boss System Variables
let boss = null;
let bossHealth = 0;
let bossMaxHealth = 0;
let bossPhase = 'idle';
let bossAttackTimer = 0;
let bossAttackPattern = 0;
let bossBullets = [];
let bossExplosions = [];
let bossDefeated = false;
let bossReward = 0;
let bossDirection = 1; // Boss movement direction

// 🚀 NEW: Cool Boss Effects Variables
let screenShake = 0;
let bossParticles = [];
let bossGlowEffect = 0;
let bossEntranceEffect = 0;
let bossDefeatEffect = 0;

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

// 🚀 NEW: Floating reload button (global scope)
let reloadButton = null;
let reloadButtonInterval = null;

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
    // 🚀 FIX: Better weapon switching with immediate feedback
    if (weaponAmmo[weaponType] > 0 || weaponType === 'normal') {
      const previousWeapon = currentWeaponType;
      currentWeaponType = weaponType;
      
      // 🚀 FIX: Immediate visual feedback
      console.log(`🔫 Weapon switched from ${previousWeapon} to ${weaponType} weapon`);
      
      // 🚀 FIX: Update weapon display immediately
      updateWeaponDisplay();
      
      // 🚀 FIX: Update reload button immediately when weapon changes
      if (reloadButton) {
        updateReloadButton();
      }
      
      // 🚀 FIX: Update weapon panel if open
      if (typeof updateWeaponAmmoDisplay === 'function') {
        updateWeaponAmmoDisplay();
      }
      
      // 🚀 FIX: Show weapon switch notification
      showWeaponSwitchNotification(weaponType);
    } else {
      console.log(`❌ No ammo for ${weaponType} weapon`);
      // 🚀 FIX: Show ammo warning
      showAmmoWarning(weaponType);
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
      console.error('❌ No speed boost ammo available!');
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
  
  // 🚀 NEW: Update player invincibility
  function updatePlayerInvincibility() {
    if (playerShip.invincible && playerShip.invincibleTimer > 0) {
      playerShip.invincibleTimer--;
      if (playerShip.invincibleTimer <= 0) {
        playerShip.invincible = false;
        console.log('🛡️ Invincibility expired');
      }
    }
  }

  // 🚀 NEW: Get current player speed
  function getPlayerSpeed() {
    return speedBoostActive ? playerShip.speed * speedBoostMultiplier : playerShip.speed;
  }
  
  // 🚀 NEW: Show weapon switch notification
  function showWeaponSwitchNotification(weaponType) {
    const notification = document.createElement('div');
    notification.style.cssText = `
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: linear-gradient(135deg, #1e3a8a, #3730a3);
      color: white;
      padding: 20px 30px;
      border-radius: 15px;
      font-size: 1.2em;
      font-weight: bold;
      z-index: 10000;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
      animation: weaponSwitchFade 1s ease-in-out;
    `;
    
    const weaponLabels = {
      'normal': '🔫 NORMAL WEAPON',
      'laser': '⚡ LASER WEAPON',
      'bomb': '💣 BOMB WEAPON'
    };
    
    notification.textContent = weaponLabels[weaponType] || '🔫 WEAPON SWITCHED';
    
    // Add CSS animation
    const style = document.createElement('style');
    style.textContent = `
      @keyframes weaponSwitchFade {
        0% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
        50% { opacity: 1; transform: translate(-50%, -50%) scale(1.1); }
        100% { opacity: 0; transform: translate(-50%, -50%) scale(1); }
      }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(notification);
    
    // Remove notification after animation
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 1000);
  }
  
  // 🚀 NEW: Show ammo warning
  function showAmmoWarning(weaponType) {
    const warning = document.createElement('div');
    warning.style.cssText = `
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: linear-gradient(135deg, #dc2626, #b91c1c);
      color: white;
      padding: 15px 25px;
      border-radius: 10px;
      font-size: 1em;
      font-weight: bold;
      z-index: 10000;
      box-shadow: 0 8px 25px rgba(220,38,38,0.4);
      animation: ammoWarningShake 0.6s ease-in-out;
    `;
    
    warning.textContent = `❌ NO AMMO FOR ${weaponType.toUpperCase()}`;
    
    // Add CSS animation
    const style = document.createElement('style');
    style.textContent = `
      @keyframes ammoWarningShake {
        0%, 100% { transform: translate(-50%, -50%); }
        25% { transform: translate(-52%, -50%); }
        75% { transform: translate(-48%, -50%); }
      }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(warning);
    
    // Remove warning after animation
    setTimeout(() => {
      if (warning.parentNode) {
        warning.parentNode.removeChild(warning);
      }
    }, 800);
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
      
      // 🚀 NEW: Show progressive multi-shot upgrade status
      if (hasQuadShotUpgrade && currentWeaponType === 'normal') {
        displayText += ` 🎯 QUAD SHOT!`;
      } else if (hasTripleShotUpgrade && currentWeaponType === 'normal') {
        displayText += ` 🎯 TRIPLE SHOT!`;
      } else if (hasDoubleShotUpgrade && currentWeaponType === 'normal') {
        displayText += ` 🎯 DOUBLE SHOT!`;
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
    
    // 🚀 ULTRA AGGRESSIVE: Much higher spawn rates for more action!
    let spawnChance = 0.200; // Base rate for early waves (20% - MUCH higher!)
    
    // Progressive scaling that ACTUALLY helps in higher waves
    if (waveNumber >= 2) spawnChance = 0.250;   // 25% for wave 2+
    if (waveNumber >= 3) spawnChance = 0.300;   // 30% for wave 3+
    if (waveNumber >= 5) spawnChance = 0.400;   // 40% for wave 5+
    if (waveNumber >= 8) spawnChance = 0.500;   // 50% for wave 8+
    if (waveNumber >= 10) spawnChance = 0.600;  // 60% for wave 10+
    if (waveNumber >= 15) spawnChance = 0.700;  // 70% for wave 15+
    if (waveNumber >= 20) spawnChance = 0.800;  // 80% for wave 20+
    if (waveNumber >= 25) spawnChance = 0.850;  // 85% for wave 25+
    if (waveNumber >= 30) spawnChance = 0.900;  // 90% for wave 30+
    if (waveNumber >= 35) spawnChance = 0.950;  // 95% for wave 35+
    if (waveNumber >= 50) spawnChance = 0.980;  // 98% for wave 50+ (boss waves)
    if (waveNumber >= 75) spawnChance = 0.990;  // 99% for wave 75+ (ultra waves)
    if (waveNumber >= 100) spawnChance = 0.995; // 99.5% for wave 100+ (legendary waves)
    if (waveNumber >= 150) spawnChance = 0.999; // 99.9% for wave 150+ (mythical waves)
    if (waveNumber >= 200) spawnChance = 0.999; // 99.9% for wave 200+ (god-tier waves)
    
    if (Math.random() < spawnChance) {
      console.log(`🎁 SPAWNING POWER-UP: Wave ${waveNumber}, Chance: ${spawnChance.toFixed(2)}, Current power-ups: ${window.powerUps?.length || 0}`);
      
      // 🚀 NEW: Better power-up distribution - ensure all types appear
      const powerUpRoll = Math.random();
      let powerUpType, ammoType;
      
      if (powerUpRoll < 0.25) {
        // 25% chance: Speed boost power-up (green ⚡)
        powerUpType = 'speed';
      } else if (powerUpRoll < 0.50) {
        // 25% chance: Laser ammo (cyan 🔫)
        powerUpType = 'ammo';
        ammoType = 'laser';
      } else if (powerUpRoll < 0.75) {
        // 25% chance: Bomb ammo (magenta 💣)
        powerUpType = 'ammo';
        ammoType = 'bomb';
      } else {
        // 25% chance: Collect power-up (yellow ⭐)
        powerUpType = 'collect';
      }
      
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
      } else if (powerUpType === 'collect') {
        // Collect power-up (bonus points/effects)
        const powerUp = {
          x: Math.random() * (canvasWidth - 20),
          y: -20,
          width: 20,
          height: 20,
          type: 'collect',
          color: '#ffff00',
          speed: 2,
          collected: false
        };
        
        if (!window.powerUps) window.powerUps = [];
        window.powerUps.push(powerUp);
      } else {
        // Ammo power-up
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
        
        if (!window.powerUps) window.powerUps = [];
        window.powerUps.push(powerUp);
      }
      
      // 🚀 NEW: Debug logging for power-up spawning
      console.log(`🎁 Power-up spawned: ${powerUpType}${ammoType ? ' (' + ammoType + ')' : ''} at wave ${waveNumber} (${Math.round(spawnChance * 100)}% chance)`);
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
        } else if (powerUp.type === 'collect') {
          // 🚀 NEW: Collect power-up gives bonus points and temporary effects
                  spaceInvadersScore += 500; // Bonus points
        spaceInvadersCount += 5; // Bonus invader count for DSPOINC (kept for legacy compatibility)
          
          // Temporary invincibility (1 second)
          playerShip.invincible = true;
          playerShip.invincibleTimer = 100; // 1 second at 100ms intervals
          
          console.log(`⭐ Collect power-up collected! +500 points, +5 invaders, temporary invincibility!`);
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

  // 🚀 NEW: Draw power-ups with custom images
  function drawPowerUps() {
    if (!window.powerUps) return;
    
    window.powerUps.forEach(powerUp => {
      if (powerUp.collected) return;
      
      // Try to draw custom power-up image first
      let powerUpImg = null;
      if (powerUp.type === 'speed') {
        powerUpImg = powerUpImages.speed;
      } else if (powerUp.type === 'ammo') {
        powerUpImg = powerUpImages[powerUp.ammoType];
      } else if (powerUp.type === 'collect') {
        powerUpImg = powerUpImages.collect;
      }
      
      if (powerUpImg && powerUpImg.complete && powerUpImg.naturalWidth > 0) {
        // Draw custom power-up image
        ctx.drawImage(powerUpImg, powerUp.x, powerUp.y, powerUp.width, powerUp.height);
        
        // Add glow effect around the image
        ctx.fillStyle = powerUp.color + '40';
        ctx.fillRect(powerUp.x - 2, powerUp.y - 2, powerUp.width + 4, powerUp.height + 4);
      } else {
        // Fallback to colored rectangles with symbols
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
        } else if (powerUp.type === 'collect') {
          ctx.fillText('⭐', powerUp.x + 4, powerUp.y + 15);
        }
      }
    });
  }

  // 🚀 NEW: Boss Level Notification Function
  function sendBossLevelNotification(bossLevel, bossName, bossType) {
    try {
      // 🌍 Ensure API_BASE_URL is available (with fallback)
      const apiBaseUrl = (typeof API_BASE_URL !== 'undefined') ? API_BASE_URL : 
                        (window.location.hostname === 'narrrfs.world' ? 'https://narrrfs.world' : '');
      
      console.log('🌍 Boss Notification API Base URL:', apiBaseUrl);
      
      // Get current player info (you may need to adjust this based on your game's player system)
      const playerUsername = getCurrentPlayerUsername() || 'Anonymous Player';
      const playerId = getCurrentPlayerId() || null;
      const currentScore = spaceInvadersScore || 0;
      const currentWave = waveNumber || 0;
      
      // Calculate DSPOINC earned (10x multiplier like other games)
      const dspoincEarned = currentScore * 10;
      
      // Get Discord info for better identification
      const discordId = (typeof localStorage !== 'undefined') ? localStorage.getItem('discord_id') : null;
      const discordName = (typeof localStorage !== 'undefined') ? localStorage.getItem('discord_name') : null;
      
      // Prepare notification data
      const notificationData = {
        player_username: playerUsername,
        player_id: playerId, // This will be Discord ID if available
        discord_id: discordId, // Explicit Discord ID field
        discord_name: discordName, // Explicit Discord name field
        game_type: 'space_invaders',
        wave_number: currentWave,
        boss_level: bossLevel,
        boss_type: bossType,
        boss_name: bossName,
        score: currentScore,
        dspoinc_earned: dspoincEarned,
        notification_type: 'boss_level_reached'
      };
      
      console.log('📤 Sending boss notification data:', notificationData);
      
      // Send notification to admin API
      fetch(API_BASE_URL + '/api/admin/boss-level-notification.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(notificationData)
      })
      .then(response => {
        console.log('📡 Boss notification response status:', response.status);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        console.log('📨 Boss notification response data:', data);
        if (data.success) {
          console.log('✅ Boss level notification sent successfully:', data.message);
          console.log('🆔 Notification ID:', data.notification_id);
        } else {
          console.error('❌ Failed to send boss notification:', data.error);
        }
      })
      .catch(error => {
        console.error('🚨 Error sending boss notification:', error);
        console.error('🔍 Error details:', error.message);
      });
      
    } catch (error) {
      console.warn('⚠️ Error in sendBossLevelNotification:', error);
    }
  }
  
  // Helper functions to get player information
  function getCurrentPlayerUsername() {
    // 🔥 PRIORITY 1: Try to get Discord name (best option)
    if (typeof localStorage !== 'undefined') {
      const discordName = localStorage.getItem('discord_name');
      if (discordName && discordName !== 'null' && discordName !== '') {
        console.log('👤 Using Discord name:', discordName);
        return discordName;
      }
    }
    
    // Try to get username from various sources
    if (typeof window !== 'undefined' && window.currentPlayer) {
      return window.currentPlayer.username;
    }
    if (typeof window !== 'undefined' && window.playerUsername) {
      return window.playerUsername;
    }
    
    // Check localStorage for saved username (fallback)
    if (typeof localStorage !== 'undefined') {
      const savedUsername = localStorage.getItem('spaceInvadersUsername');
      if (savedUsername && savedUsername !== 'null' && savedUsername !== '') {
        return savedUsername;
      }
    }
    
    // If no username found, prompt for one and save it (last resort)
    if (typeof window !== 'undefined' && typeof localStorage !== 'undefined') {
      const username = prompt('🎮 Enter your player name for the leaderboard and boss achievements:');
      if (username && username.trim() !== '') {
        const cleanUsername = username.trim().substring(0, 20); // Limit to 20 chars
        localStorage.setItem('spaceInvadersUsername', cleanUsername);
        console.log('👤 Player username saved:', cleanUsername);
        return cleanUsername;
      }
    }
    
    // Fallback to anonymous
    return 'Anonymous Player';
  }
  
  function getCurrentPlayerId() {
    // 🔥 PRIORITY 1: Try to get Discord ID (best option)
    if (typeof localStorage !== 'undefined') {
      const discordId = localStorage.getItem('discord_id');
      if (discordId && discordId !== 'null' && discordId !== '') {
        console.log('🆔 Using Discord ID:', discordId);
        return discordId;
      }
    }
    
    // Try to get player ID from various sources (fallback)
    if (typeof window !== 'undefined' && window.currentPlayer) {
      return window.currentPlayer.id;
    }
    if (typeof window !== 'undefined' && window.playerId) {
      return window.playerId;
    }
    
    // Return null if no ID found
    return null;
  }

  // 🚀 NEW: Boss System Functions
  function spawnBoss() {
    try {
      console.log(`👑 BOSS WAVE ${waveNumber} - PREPARE FOR BATTLE!`);
      console.log(`🚀 SPAWN BOSS FUNCTION CALLED - Starting boss spawn sequence...`);
    
    // Clear all existing invaders for boss fight
    console.log(`🧹 Clearing invaders for boss fight...`);
    invaders = [];
    invaderBullets = [];
    console.log(`✅ Invaders cleared, arrays reset`);
    
    // 🚀 ENHANCED: Create boss with balanced difficulty
    console.log(`📊 Calculating boss stats...`);
    const bossLevel = Math.floor(waveNumber / 5); // Boss level (1, 2, 3, 4 for testing)
    
    // 🚀 NEW: Select boss type based on wave number
    // 🚀 PRODUCTION: Boss progression - Wave 10, 25, 75, 100
    console.log(`🎭 Selecting boss type for wave ${waveNumber}...`);
    let bossType, bossName;
    
    try {
      if (waveNumber === 100) {
        bossType = 'cheeseDestroyer';
        bossName = 'Cheese Destroyer';
        console.log(`👑 Wave 100: ULTIMATE FINAL BOSS - Cheese Destroyer!`);
      } else if (waveNumber === 75) {
        bossType = 'cheeseGod';
        bossName = 'Cheese God';
        console.log(`👑 Wave 75: THIRD BOSS - Cheese God!`);
      } else if (waveNumber === 25) {
        bossType = 'cheeseEmperor';
        bossName = 'Cheese Emperor';
        console.log(`👑 Wave 25: SECOND BOSS - Cheese Emperor!`);
      } else if (waveNumber === 10) {
        bossType = 'cheeseKing';
        bossName = 'Cheese King';
        console.log(`👑 Wave 10: FIRST BOSS - Cheese King!`);
      } else {
        // Fallback for any unexpected wave numbers
        bossType = 'cheeseKing';
        bossName = 'Cheese King';
        console.log(`👑 Fallback boss - Cheese King for wave ${waveNumber}`);
      }
      console.log(`🎭 Selected boss: ${bossName} (${bossType})`);
      
      // 🚀 CRITICAL DEBUG: Verify boss type selection
      if (!bossType || !bossName) {
        throw new Error(`Boss type selection failed: type=${bossType}, name=${bossName}`);
      }
      console.log(`✅ Boss type selection verified`);
    } catch (error) {
      console.error(`❌ ERROR in boss type selection:`, error);
      throw error;
    }
    
    // 🚀 NEW: Get boss configuration from admin settings (MOVED OUTSIDE TRY BLOCK)
    const bossConfig = getBossConfiguration(bossType);
    if (!bossConfig) {
      console.error(`❌ Failed to load boss configuration for ${bossType}`);
      throw new Error(`Boss configuration not found for type: ${bossType}`);
    }
    console.log(`⚙️ Boss config loaded:`, bossConfig);
    
    // 🚀 FIXED: Calculate boss health from configuration
    bossMaxHealth = bossConfig.baseHealth * bossConfig.healthMultiplier;
    bossHealth = bossMaxHealth;
    console.log(`📊 Boss level: ${bossLevel}, Max health: ${bossMaxHealth}, Current health: ${bossHealth}`);
    
    // 🚀 DEBUG: Log boss spawn with health info (AFTER variables are defined)
    console.log(`👑 BOSS SPAWNED: ${bossName} (Level ${bossLevel}) with ${bossMaxHealth} HP`);
    console.log(`🔍 Boss will take approximately ${Math.ceil(bossMaxHealth/10)} hits to defeat (assuming 10 damage per hit)`);
    console.log(`🎯 Boss type: ${bossType}, Wave: ${waveNumber}, Boss level: ${bossLevel}`);
    
    // 🚀 ENHANCED: Create boss with configuration-based properties
    console.log(`🏗️ Creating boss object using configuration...`);
    
    try {
      // 🚀 NEW: Apply boss configuration
      const config = bossConfig;
      const bossWidth = 120 * config.size;
      const bossHeight = 80 * config.size;
      
      boss = {
        x: canvasWidth / 2 - bossWidth / 2,
        y: 100, // Lower position so boss is fully visible
        width: bossWidth,
        height: bossHeight,
        speed: config.baseSpeed * config.speedMultiplier + (bossLevel * 0.5),
        health: bossHealth,
        maxHealth: bossMaxHealth,
        phase: 'entrance',
        // 🚀 CRITICAL FIX: Initialize boss phase properly
        bossPhase: 'entrance',
        attackPattern: 0,
        lastAttack: 0,
        attackCooldown: (config.baseAttackCooldown * config.attackCooldownMultiplier) + (bossLevel * 200), // FIXED: Slower attacks, not faster
        bulletSpeed: config.baseBulletSpeed * config.bulletSpeedMultiplier + (bossLevel * 1),
        bulletDamage: config.baseBulletDamage * config.bulletDamageMultiplier + Math.floor(bossLevel),
        type: bossType,
        name: bossName,
        
        // 🚀 NEW: Configuration-based boss properties
        size: config.size,
        wobble: 0,
        wobbleSpeed: 0.1 + (Math.random() * 0.2),
        specialAttackCooldown: 0,
        lastSpecialAttack: 0,
        movementPattern: config.movementPatterns[0], // Start with first pattern
        availableMovementPatterns: config.movementPatterns,
        attackMode: 'normal',
        invincibilityFrames: 0,
        rageMode: false,
        lastDirectionChange: 0,
        
        // 🚀 NEW: Configuration-based abilities
        canTeleport: config.abilities.canTeleport,
        canShield: config.abilities.canShield,
        canSummonMinions: config.abilities.canSummonMinions,
        canUseLaser: config.abilities.canUseLaser,
        canCreateExplosions: config.abilities.canCreateExplosions,
        
        // 🚀 NEW: Configuration-based settings
        specialAttackChance: config.specialAttackChance,
        rageModeThreshold: config.rageModeThreshold,
        rageModeMultipliers: config.rageModeMultipliers,
        colors: config.colors,
        
        // 🚀 NEW: Configuration-based attack patterns
        availableAttackPatterns: config.attackPatterns,
        };
      
      console.log(`✅ Boss object created successfully`);
      
      // 🚀 CRITICAL DEBUG: Verify boss object properties
      if (!boss.x || !boss.y || !boss.width || !boss.height) {
        throw new Error(`Boss object properties invalid: x=${boss.x}, y=${boss.y}, width=${boss.width}, height=${boss.height}`);
      }
      console.log(`✅ Boss object properties verified`);
      
    } catch (error) {
      console.error(`❌ ERROR in boss object creation:`, error);
      throw error;
    }
    
    // 🚀 NEW: Boss abilities already configured from config system
    console.log(`⚡ Boss abilities configured from configuration system`);
    console.log(`✅ Boss configuration complete: ${boss.name} (${boss.type})`);
    console.log(`📊 Final stats: Health=${boss.health}, Speed=${boss.speed}, AttackCooldown=${boss.attackCooldown}`);
    console.log(`🎭 Movement patterns: ${boss.availableMovementPatterns.join(', ')}`);
    console.log(`⚔️ Abilities: Teleport=${boss.canTeleport}, Shield=${boss.canShield}, Minions=${boss.canSummonMinions}, Laser=${boss.canUseLaser}, Explosions=${boss.canCreateExplosions}`);
    
    console.log(`🎬 Setting up boss phase and variables...`);
    
    try {
      bossPhase = 'entrance';
      bossAttackTimer = 0;
      bossAttackPattern = 0;
      bossBullets = [];
      bossExplosions = [];
      bossDefeated = false;
              bossReward = waveNumber * 2; // 100 DSPOINC for wave 50, 200 for wave 100, etc. (REDUCED for balance)
      console.log(`✅ Boss phase variables set: phase=${bossPhase}, reward=${bossReward}`);
      
      // 🚀 CRITICAL DEBUG: Verify phase variables
      if (bossPhase !== 'entrance') {
        throw new Error(`Boss phase not set correctly: ${bossPhase}`);
      }
      console.log(`✅ Boss phase verification passed`);
    } catch (error) {
      console.error(`❌ ERROR in boss phase setup:`, error);
      throw error;
    }
    
    // Boss entrance animation
    console.log(`🎬 Setting up boss entrance animation...`);
    boss.y = -50; // Start from lower position
    console.log(`👑 Boss spawned: Level ${bossLevel}, Health: ${bossHealth}, Reward: ${bossReward} DSPOINC`);
    console.log(`📍 Boss starting position: x=${boss.x}, y=${boss.y}`);
    
    // Send boss level notification to admin interface
    console.log(`📢 Sending boss notification...`);
    sendBossLevelNotification(bossLevel, bossName, bossType);
    console.log(`✅ Boss notification sent`);
    
    console.log(`✅ BOSS SPAWN COMPLETE: ${bossName} ready for battle!`);
    console.log(`🎯 Boss object created:`, boss);
    } catch (error) {
      console.error(`❌ ERROR IN SPAWN BOSS:`, error);
      console.error(`❌ Stack trace:`, error.stack);
      // Reset boss state to prevent game from getting stuck
      boss = null;
      bossPhase = 'idle';
      gamePhase = 'formation';
      console.log(`🔄 Boss spawn failed, returning to normal waves...`);
    }
  }

  function updateBoss() {
    // 🚀 CRITICAL: Always update boss effects for cleanup even when boss is defeated
    updateBossEffects();
    
    if (!boss || (bossDefeated && bossDefeatEffect <= 0)) return;
    
    const currentTime = Date.now();
    
    // 🚀 DEBUG: Log boss update status
    if (phaseTimer % 100 === 0) { // Every 10 seconds
      if (boss) {
        console.log(`👑 BOSS UPDATE: ${boss.name} at ${boss.health}/${boss.maxHealth} HP, Phase: ${bossPhase}, Position: x=${boss.x}, y=${boss.y}`);
        console.log(`🔍 BOSS PHASE DEBUG: bossPhase=${bossPhase}, typeof=${typeof bossPhase}, boss.phase=${boss.phase}`);
      }
    }
    
    // 🚀 NEW: Update boss bullets movement
    updateBossBullets();
    
    // Boss entrance animation
    if (bossPhase === 'entrance') {
      boss.y += 1;
      bossEntranceEffect += 0.1;
      
      // 🚀 NEW: Create entrance particles
      if (Math.random() < 0.3) {
        createBossParticle(boss.x + Math.random() * boss.width, boss.y + boss.height, 'entrance');
      }
      
      if (boss.y >= 100) {
        bossPhase = 'fighting';
        boss.y = 100;
        bossEntranceEffect = 0;
        // 🚀 NEW: Screen shake on boss arrival
        screenShake = 20;
        console.log('👑 Boss entrance complete - FIGHT BEGINS!');
      }
      
      // 🚀 DEBUG: Log entrance progress
      if (phaseTimer % 50 === 0) { // Every 5 seconds
        console.log(`👑 BOSS ENTRANCE: Y position ${boss.y}, target: 100, phase: ${bossPhase}`);
      }
      
      return;
    }
    
    // 🚀 ENHANCED: Advanced boss movement patterns and behaviors
    if (bossPhase === 'fighting') {
      // Update boss wobble animation
      boss.wobble += boss.wobbleSpeed;
      
      // 🚀 NEW: Rage mode activation (when health is below threshold - triggers earlier!)
      const rageThreshold = boss.rageModeThreshold || 0.4;
      if (boss.health < boss.maxHealth * rageThreshold && !boss.rageMode) {
        boss.rageMode = true;
        const rageMultipliers = boss.rageModeMultipliers || {
          speed: 2.0,
          attackCooldown: 0.4,
          bulletSpeed: 1.8,
          bulletDamage: 1.5
        };
        boss.speed *= rageMultipliers.speed; // Much faster
        boss.attackCooldown *= rageMultipliers.attackCooldown; // Much faster attacks
        boss.bulletSpeed *= rageMultipliers.bulletSpeed; // Much faster bullets
        boss.bulletDamage *= rageMultipliers.bulletDamage; // More damage
        console.log(`👑 ${boss.name} enters DEVASTATING RAGE MODE!`);
        screenShake = 40;
        
        // 🚀 NEW: Rage mode special effects
        for (let i = 0; i < 20; i++) {
          setTimeout(() => {
            if (boss && boss.rageMode) {
              createBossParticle(boss.x + Math.random() * boss.width, boss.y + Math.random() * boss.height, 'rage');
            }
          }, i * 100);
        }
      }
      
      // 🚀 NEW: Dynamic movement patterns based on boss type
      if (boss.movementPattern === 'sideways') {
        // Enhanced side-to-side with acceleration
        boss.x += boss.speed * (bossDirection || 1);
        
        // 🚨 CRITICAL FIX: Enforce strict boundaries
        if (boss.x <= 0) {
          boss.x = 0;
          bossDirection = 1;
        } else         if (boss.x + boss.width >= canvasWidth) {
          boss.x = canvasWidth - boss.width;
          bossDirection = -1;
        }
        
        // 🚨 CRITICAL FIX: Prevent boss from going too low
        boss.y = Math.max(50, boss.y);
        
        if (boss.x + boss.width >= canvasWidth) {
          boss.lastDirectionChange = currentTime;
          
          // 🚀 ENHANCED: More varied movement pattern changes with longer durations
          if (Math.random() < 0.8) { // 80% chance for more variety
            const availablePatterns = boss.availableMovementPatterns || ['sideways', 'zigzag', 'hover', 'dash'];
            const newPattern = availablePatterns[Math.floor(Math.random() * availablePatterns.length)];
            boss.movementPattern = newPattern;
            console.log(`🎭 ${boss.name} switches to ${newPattern} movement pattern!`);
            setTimeout(() => {
              if (boss) {
                // Switch to another random pattern instead of always returning to first
                const nextPattern = availablePatterns[Math.floor(Math.random() * availablePatterns.length)];
                boss.movementPattern = nextPattern;
              }
            }, 3000 + Math.random() * 2000); // Longer duration (3-5 seconds)
          }
        }
      } else if (boss.movementPattern === 'zigzag') {
        // Zigzag movement
        boss.x += boss.speed * (bossDirection || 1) * 0.7;
        boss.y = 100 + Math.sin(boss.wobble * 0.5) * 20;
        
        // 🚨 CRITICAL FIX: Enforce strict boundaries
        if (boss.x <= 0) {
          boss.x = 0;
          bossDirection = 1;
        } else if (boss.x + boss.width >= canvasWidth) {
          boss.x = canvasWidth - boss.width;
          bossDirection = -1;
        }
        
        // 🚨 CRITICAL FIX: Prevent boss from going too low
        boss.y = Math.max(50, boss.y);
      } else if (boss.movementPattern === 'hover') {
        // Hovering movement
        boss.x += boss.speed * (bossDirection || 1) * 0.5;
        boss.y = 100 + Math.sin(boss.wobble * 0.3) * 15;
        // 🚨 CRITICAL FIX: Enforce strict boundaries
        if (boss.x <= 0) {
          boss.x = 0;
          bossDirection = 1;
        } else if (boss.x + boss.width >= canvasWidth) {
          boss.x = canvasWidth - boss.width;
          bossDirection = -1;
        }
        
        // 🚨 CRITICAL FIX: Prevent boss from going too low
        boss.y = Math.max(50, boss.y);
      } else if (boss.movementPattern === 'circle') {
        // 🚀 NEW: Circular movement pattern
        const radius = 30;
        const centerX = canvasWidth / 2;
        const centerY = 100;
        boss.x = centerX + Math.cos(boss.wobble * 0.2) * radius;
        boss.y = centerY + Math.sin(boss.wobble * 0.2) * radius;
        
        // 🚨 CRITICAL FIX: Prevent boss from going too low
        boss.y = Math.max(50, boss.y);
      } else if (boss.movementPattern === 'dash') {
        // 🚀 NEW: Dash movement pattern - quick side-to-side dashes
        if (!boss.dashTimer) boss.dashTimer = 0;
        boss.dashTimer++;
        
        if (boss.dashTimer < 30) {
          boss.x += boss.speed * 3 * (bossDirection || 1); // Fast dash
        } else if (boss.dashTimer < 60) {
          boss.x -= boss.speed * 2 * (bossDirection || 1); // Return dash
        } else {
          boss.dashTimer = 0;
          bossDirection = bossDirection ? -bossDirection : -1;
        }
        
        // 🚨 CRITICAL FIX: Prevent boss from going too low
        boss.y = Math.max(50, boss.y);
      }
      
      // 🚀 BALANCED: Special attacks based on boss abilities  
      if (currentTime - boss.lastSpecialAttack > 5000) { // Every 5 seconds (balanced)
        // 🚀 BALANCED: Reduced special attack chance
        if (Math.random() < (boss.specialAttackChance || 0.15)) { // Reduced from 0.3 to 0.15
          bossSpecialAttack();
          boss.lastSpecialAttack = currentTime;
        }
      }
      
      // 🚀 NEW: Enhanced regular attacks with more variety
      if (currentTime - boss.lastAttack > boss.attackCooldown) {
        // 🚀 NEW: Random attack pattern selection for variety
        if (Math.random() < 0.3) { // 30% chance to change attack pattern
          const availablePatterns = boss.availableAttackPatterns || [0, 1, 2, 3, 4];
          boss.attackPattern = availablePatterns[Math.floor(Math.random() * availablePatterns.length)];
          console.log(`🎯 ${boss.name} switches to attack pattern ${boss.attackPattern}!`);
        }
        
        bossAttack();
        boss.lastAttack = currentTime;
        
        // 🚀 BALANCED: Sometimes fire multiple attacks in quick succession
        if (Math.random() < 0.1) { // Reduced to 10% chance for rapid fire
          setTimeout(() => {
            if (boss && boss.health > 0) {
              bossAttack();
              console.log(`⚡ ${boss.name} uses RAPID FIRE attack!`);
            }
          }, 800); // Increased delay from 300ms to 800ms
        }
      }
      
      // 🚀 BALANCED: Teleport ability for certain bosses
      if (boss.canTeleport && Math.random() < 0.008) { // Reduced to 0.8% chance per frame
        bossTeleport();
      }
      
      // 🚀 BALANCED: Shield ability for certain bosses
      if (boss.canShield && boss.health < boss.maxHealth * 0.5 && Math.random() < 0.005) { // Reduced to 0.5% chance, activate at 50% health
        bossActivateShield();
      }
      
      // 🚀 NEW: Update invincibility frames
      if (boss.invincibilityFrames > 0) {
        boss.invincibilityFrames--;
      }
      
      // 🚨 FINAL SAFETY CHECK: Ensure boss never gets too close to player
      if (boss && !bossDefeated) {
        const bossPlayerDistance = Math.abs(boss.y - playerShip.y);
        if (bossPlayerDistance < 60) { // If boss is within 60 pixels of player
          console.log(`🚨 FINAL SAFETY: Boss too close to player! Distance: ${bossPlayerDistance}px - Moving boss up!`);
          boss.y = Math.max(60, boss.y - 15); // Move boss up away from player
        }
      }
    }
    
    // 🚀 NEW: Handle boss defeat phase
    if (bossDefeated && boss) {
      // Boss is defeated but still visible for defeat effects
      boss.y += 2; // Slowly fall down
      boss.rotation = (boss.rotation || 0) + 0.1; // Slowly rotate
      
      // Create defeat particles
      if (Math.random() < 0.5) {
        createBossParticle(boss.x + Math.random() * boss.width, boss.y + Math.random() * boss.height, 'defeat');
      }
      
      // 🧪 TESTING: Add safety timer to prevent premature boss deletion
      if (!boss.defeatStartTime) {
        boss.defeatStartTime = Date.now();
        console.log('👑 Boss defeat sequence started - timer activated');
      }
      
      // Only remove boss after minimum time AND falling off screen
      const minDefeatTime = 2000; // 2 seconds minimum
      const timeInDefeat = Date.now() - boss.defeatStartTime;
      
      if (timeInDefeat >= minDefeatTime && boss.y > canvasHeight + 100) {
        boss = null;
        console.log(`👑 Boss defeat sequence complete after ${timeInDefeat}ms!`);
      } else if (timeInDefeat < minDefeatTime) {
        console.log(`👑 Boss defeat in progress... ${Math.round((minDefeatTime - timeInDefeat)/1000)}s remaining`);
      }
    }
    
    // 🧀 ENHANCED: Update boss bullets with crazy cheese physics
    if (bossBullets.length > 0 && Date.now() % 2000 < 16) { // Log every 2 seconds
      console.log(`🧪 UPDATING ${bossBullets.length} boss bullets`);
    }
    
    bossBullets.forEach((bullet, index) => {
      // 🧀 Handle special cheese bullet types
      if (bullet.type === 'cheese_wheel' && bullet.rotation !== undefined) {
        // Spinning cheese wheels
        bullet.rotation += bullet.rotationSpeed;
        bullet.y += bullet.speed;
      } else if (bullet.type === 'melted_cheese' && bullet.trail) {
        // Melted cheese with gravity effect
        bullet.x += bullet.vx;
        bullet.y += bullet.vy;
        bullet.vy += 0.1; // Gravity effect for melted cheese
      } else if (bullet.type === 'gouda_grenade' && bullet.explosive) {
        // Gouda grenades with timer
        bullet.x += bullet.vx;
        bullet.y += bullet.vy;
        bullet.timer--;
        if (bullet.timer <= 0) {
          // Create explosion
          createExplosion(bullet.x, bullet.y);
          // Create multiple smaller bullets from explosion
          for (let i = 0; i < 6; i++) {
            const angle = (i / 6) * Math.PI * 2;
            bossBullets.push({
              x: bullet.x,
              y: bullet.y,
              width: 6,
              height: 6,
              speed: 3,
              damage: Math.floor(bullet.damage * 0.5),
              vx: Math.cos(angle) * 3,
              vy: Math.sin(angle) * 3,
              color: '#ff6600',
              type: 'explosion_fragment',
              life: 60
            });
          }
          bossBullets.splice(index, 1);
          return;
        }
      } else if (bullet.type === 'brie_blast' && bullet.wobble) {
        // Brie blasts wobble as they move
        bullet.wobbleTime = (bullet.wobbleTime || 0) + bullet.wobbleSpeed;
        bullet.x += bullet.vx + Math.sin(bullet.wobbleTime) * 2;
        bullet.y += bullet.vy;
      } else if (bullet.type === 'swiss_sniper' && bullet.piercing) {
        // Swiss sniper bullets maintain trajectory
        bullet.x += bullet.vx;
        bullet.y += bullet.vy;
      } else if (bullet.type === 'explosion_fragment' && bullet.life) {
        // Explosion fragments fade out
        bullet.x += bullet.vx;
        bullet.y += bullet.vy;
        bullet.life--;
        if (bullet.life <= 0) {
          bossBullets.splice(index, 1);
          return;
        }
      } else if (bullet.vx && bullet.vy) {
        // Standard velocity-based bullets
        bullet.x += bullet.vx;
        bullet.y += bullet.vy;
      } else if (bullet.duration) {
        // Laser bullets - check duration
        if (Date.now() - bullet.startTime > bullet.duration) {
          bossBullets.splice(index, 1);
          return;
        }
      } else {
        // Regular bullets with angle adjustment
        if (bullet.angle) {
          bullet.x += Math.sin(bullet.angle) * bullet.speed;
          bullet.y += Math.cos(bullet.angle) * bullet.speed;
        } else {
          // 🧪 DEBUG: Log bullet movement for default bullets
          const oldY = bullet.y;
          bullet.y += bullet.speed;
          if (Date.now() % 3000 < 16 && bullet.type === 'test_bullet') { // Log test bullets
            console.log(`🧪 Moving bullet: ${oldY} -> ${bullet.y} (speed: ${bullet.speed})`);
          }
        }
      }
      
      // 🧀 Enhanced boundary checking with buffer for larger cheese bullets
      const buffer = Math.max(bullet.width, bullet.height) + 10;
      if (bullet.y > canvasHeight + buffer || 
          bullet.x < -buffer || 
          bullet.x > canvasWidth + buffer ||
          bullet.y < -buffer) {
        bossBullets.splice(index, 1);
      }
    });
  }

  function bossAttack() {
    if (!boss || bossDefeated) return;
    
    const attackPatterns = [
      // 🧀 Pattern 1: CHEESE CANNON - Single devastating cheesy shot
      () => {
        console.log(`🧀 ${boss.name} fires CHEESE CANNON!`);
        const bulletX = boss.x + boss.width / 2 - 8;
        const bulletY = boss.y + boss.height;
        
        // 🧪 DEBUG: Check for NaN positions
        if (isNaN(bulletX) || isNaN(bulletY)) {
          console.log(`🚨 NaN BULLET DETECTED! Boss pos: (${boss.x}, ${boss.y}), size: ${boss.width}x${boss.height}`);
          console.log(`🚨 Calculated bullet pos: (${bulletX}, ${bulletY})`);
        }
        
        bossBullets.push({
          x: bulletX,
          y: bulletY,
          width: 16,
          height: 24,
          speed: boss.bulletSpeed * 1.3,
          damage: Math.max(1, Math.floor(boss.bulletDamage * 1.2)), // Reduced from 2x to 1.2x
          color: '#ffdd00',
          type: 'cheese_cannon',
          glow: true
        });
        screenShake = 8;
      },
      
      // 🧀 Pattern 2: MELTED CHEESE SPREAD - Triple molten cheese spread
      () => {
        console.log(`🧀 ${boss.name} unleashes MELTED CHEESE SPREAD!`);
        for (let i = -1; i <= 1; i++) {
          bossBullets.push({
            x: boss.x + boss.width / 2 - 6,
            y: boss.y + boss.height,
            width: 12,
            height: 18,
            speed: boss.bulletSpeed * 0.8,
            damage: boss.bulletDamage * 1.5,
            color: '#ff8c00',
            type: 'melted_cheese',
            angle: i * 0.4,
            vx: Math.sin(i * 0.4) * boss.bulletSpeed * 0.8,
            vy: boss.bulletSpeed * 0.8,
            trail: true
          });
        }
        screenShake = 6;
      },
      
      // 🧀 Pattern 3: CHEESE WHEEL BARRAGE - Rapid spinning cheese wheels
      () => {
        console.log(`🧀 ${boss.name} launches CHEESE WHEEL BARRAGE!`);
        for (let i = 0; i < 5; i++) {
          setTimeout(() => {
            if (boss && !bossDefeated) {
              bossBullets.push({
                x: boss.x + boss.width / 2 - 8,
                y: boss.y + boss.height,
                width: 16,
                height: 16,
                speed: boss.bulletSpeed * 1.2,
                damage: boss.bulletDamage * 1.8,
                color: '#ffd700',
                type: 'cheese_wheel',
                rotation: 0,
                rotationSpeed: 0.3
              });
            }
          }, i * 150);
        }
        screenShake = 10;
      },
      
      // 🧀 Pattern 4: GOUDA GRENADE STORM - Explosive cheese balls in all directions
      () => {
        console.log(`🧀 ${boss.name} creates GOUDA GRENADE STORM!`);
        for (let i = 0; i < 12; i++) {
          const angle = (i / 12) * Math.PI * 2;
          bossBullets.push({
            x: boss.x + boss.width / 2 - 6,
            y: boss.y + boss.height / 2,
            width: 12,
            height: 12,
            speed: boss.bulletSpeed * 1.4,
            damage: Math.max(1, Math.floor(boss.bulletDamage * 1.4)), // Reduced from 2.2x to 1.4x
            vx: Math.cos(angle) * boss.bulletSpeed * 1.4,
            vy: Math.sin(angle) * boss.bulletSpeed * 1.4,
            color: '#ffaa00',
            type: 'gouda_grenade',
            explosive: true,
            timer: 90 // Explodes after 1.5 seconds
          });
        }
        screenShake = 15;
      },
      
      // 🧀 Pattern 5: SWISS CHEESE SNIPER - Precision holes that pierce through
      () => {
        console.log(`🧀 ${boss.name} uses SWISS CHEESE SNIPER!`);
        const playerX = playerShip.x + playerShip.width / 2;
        const dx = playerX - (boss.x + boss.width / 2);
        const dy = 400; // Distance to bottom
        const distance = Math.sqrt(dx * dx + dy * dy);
        
        bossBullets.push({
          x: boss.x + boss.width / 2 - 4,
          y: boss.y + boss.height,
          width: 8,
          height: 20,
          speed: boss.bulletSpeed * 2.0, // Reduced from 2.5x to 2.0x for better balance
          damage: Math.max(1, Math.floor(boss.bulletDamage * 1.8)), // Reduced from 3x to 1.8x
          vx: (dx / distance) * boss.bulletSpeed * 2.0,
          vy: (dy / distance) * boss.bulletSpeed * 2.0,
          color: '#ffffff',
          type: 'swiss_sniper',
          piercing: true,
          glow: true
        });
        screenShake = 12;
      },
      
      // 🧀 Pattern 6: CHEDDAR CHAOS CROSS - Four-way molten cheddar attack
      () => {
        console.log(`🧀 ${boss.name} unleashes CHEDDAR CHAOS CROSS!`);
        const directions = [[0, 1], [1, 0], [0, -1], [-1, 0], [1, 1], [-1, 1], [1, -1], [-1, -1]];
        directions.forEach(([dx, dy]) => {
          bossBullets.push({
            x: boss.x + boss.width / 2 - 6,
            y: boss.y + boss.height / 2,
            width: 12,
            height: 12,
            speed: boss.bulletSpeed * 1.6,
            damage: Math.max(1, Math.floor(boss.bulletDamage * 1.6)), // Reduced from 2.5x to 1.6x
            vx: dx * boss.bulletSpeed * 1.6,
            vy: dy * boss.bulletSpeed * 1.6,
            color: '#ff6600',
            type: 'cheddar_chaos',
            trail: true,
            heat: true
          });
        });
        screenShake = 18;
      },
      
      // 🧀 Pattern 7: PARMESAN PULSE WAVE - Expanding cheese wave
      () => {
        console.log(`🧀 ${boss.name} creates PARMESAN PULSE WAVE!`);
        for (let ring = 0; ring < 3; ring++) {
          setTimeout(() => {
            if (boss && !bossDefeated) {
              for (let i = 0; i < 16; i++) {
                const angle = (i / 16) * Math.PI * 2;
                const radius = 30 + (ring * 20);
                bossBullets.push({
                  x: boss.x + boss.width / 2 + Math.cos(angle) * radius,
                  y: boss.y + boss.height / 2 + Math.sin(angle) * radius,
                  width: 8,
                  height: 8,
                  speed: boss.bulletSpeed * (0.8 + ring * 0.3),
                  damage: boss.bulletDamage * 1.3,
                  vx: Math.cos(angle) * boss.bulletSpeed * (0.8 + ring * 0.3),
                  vy: Math.sin(angle) * boss.bulletSpeed * (0.8 + ring * 0.3),
                  color: '#ffffaa',
                  type: 'parmesan_pulse',
                  wave: ring
                });
              }
            }
          }, ring * 300);
        }
        screenShake = 20;
      },
      
      // 🧀 Pattern 8: FOCUSED CHEESE SHOT - Single precise shot (replaces overwhelming barrage)
      () => {
        console.log(`🧀 ${boss.name} fires FOCUSED CHEESE SHOT!`);
        bossBullets.push({
          x: boss.x + boss.width / 2 - 8,
          y: boss.y + boss.height,
          width: 16,
          height: 20,
          speed: boss.bulletSpeed * 1.2,
          damage: boss.bulletDamage * 1.5,
          color: '#fff8dc',
          type: 'focused_cheese',
          glow: true
        });
        screenShake = 8;
      }
    ];
    
    // Cycle through attack patterns
    const oldBulletCount = bossBullets.length;
    bossAttackPattern = (bossAttackPattern + 1) % attackPatterns.length;
    attackPatterns[bossAttackPattern]();
    const newBulletCount = bossBullets.length;
    
    console.log(`🚀 BOSS ATTACK EXECUTED: Pattern ${bossAttackPattern}, Bullets: ${oldBulletCount} -> ${newBulletCount}`);
    
    // 🚀 NEW: Create attack particles
    for (let i = 0; i < 5; i++) {
      createBossParticle(boss.x + boss.width / 2, boss.y + boss.height, 'attack');
    }
    
    // 🚀 NEW: Screen shake on boss attack
    screenShake = 5;
    
    console.log(`👑 Boss attack pattern ${bossAttackPattern + 1} executed!`);
  }

  // 🚀 NEW: Boss special attack function
  function bossSpecialAttack() {
    if (!boss || bossDefeated) return;
    
    console.log(`👑 ${boss.name} uses SPECIAL ATTACK!`);
    
    // 🧀 NEW: Random special attack selection for variety
    const attacks = [];
    if (boss.canUseLaser) attacks.push('laser');
    if (boss.canSummonMinions) attacks.push('minions');
    if (boss.canCreateExplosions) attacks.push('explosion');
    if (boss.canTeleport) attacks.push('teleport');
    attacks.push('cheese_rumble'); // Always available!
    
    // 🚀 NEW: Add devastating cheese storm attack for variety
    if (Math.random() < 0.3) { // 30% chance for cheese storm
      attacks.push('cheese_storm');
    }
    
    const attackType = attacks[Math.floor(Math.random() * attacks.length)];
    
    switch (attackType) {
      case 'laser':
        bossLaserAttack();
        break;
      case 'minions':
        bossSummonMinions();
        break;
      case 'explosion':
        bossExplosionAttack();
        break;
      case 'teleport':
        bossTeleportAttack();
        break;
      case 'cheese_rumble':
        bossCheeseRumbleAttack();
        break;
      case 'cheese_storm':
        bossCheeseStormAttack();
        break;
    }
    
    // Screen shake for special attacks
    screenShake = 15;
  }

  // 🚀 NEW: Boss laser attack - MUCH more dangerous!
  function bossLaserAttack() {
    if (!boss || bossDefeated) return;
    
    // Create massive laser beam
    const laser = {
      x: boss.x + boss.width / 2 - 20, // Wider laser
      y: boss.y + boss.height,
      width: 40, // Bigger laser
      height: canvasHeight - boss.y,
      damage: boss.bulletDamage * 5, // Much higher damage
      duration: 1500, // 1.5 seconds (longer)
      startTime: Date.now()
    };
    
    bossBullets.push(laser);
    
    // Create massive laser particles
    for (let i = 0; i < 30; i++) { // More particles
      createBossParticle(laser.x + Math.random() * laser.width, laser.y + Math.random() * laser.height, 'laser');
    }
    
    // Screen shake for laser
    screenShake = 20;
    
    console.log(`👑 ${boss.name} fires DEVASTATING LASER!`);
  }

  // 🚀 NEW: Boss minion summoning - MUCH more aggressive!
  function bossSummonMinions() {
    if (!boss || bossDefeated) return;
    
    // Spawn 4-8 minion invaders (more aggressive)
    const minionCount = 4 + Math.floor(Math.random() * 5);
    
    for (let i = 0; i < minionCount; i++) {
      const minion = {
        x: boss.x + (i * 30) - (minionCount * 15),
        y: boss.y + boss.height + 20,
        width: 25,
        height: 18,
        speed: 1.5, // Faster minions
        alive: true,
        health: 5, // Tougher minions
        isMinion: true,
        canShoot: true, // Minions can shoot
        lastShot: 0,
        shotCooldown: 2000
      };
      
      invaders.push(minion);
    }
    
    // Screen shake for minion summoning
    screenShake = 15;
    
    console.log(`👑 ${boss.name} summons ${minionCount} AGGRESSIVE minions!`);
  }

  // 🚀 NEW: Boss explosion attack - MUCH more dangerous!
  function bossExplosionAttack() {
    if (!boss || bossDefeated) return;
    
    // Create explosion bullets in ALL directions with more bullets
    for (let i = 0; i < 16; i++) { // 16 bullets instead of 8
      const angle = (i / 16) * Math.PI * 2;
      const bullet = {
        x: boss.x + boss.width / 2 - 4,
        y: boss.y + boss.height / 2 - 4,
        width: 10, // Bigger bullets
        height: 10,
        speed: boss.bulletSpeed * 1.6, // Reduced from 2x to 1.6x for better balance
        damage: boss.bulletDamage * 3, // Much higher damage
        vx: Math.cos(angle) * boss.bulletSpeed * 1.6,
        vy: Math.sin(angle) * boss.bulletSpeed * 1.6,
        color: '#ff6600'
      };
      
      bossBullets.push(bullet);
    }
    
    // Create massive explosion particles
    for (let i = 0; i < 25; i++) { // More particles
      createBossParticle(boss.x + boss.width / 2, boss.y + boss.height / 2, 'explosion');
    }
    
    // Screen shake for explosion
    screenShake = 25;
    
    console.log(`👑 ${boss.name} creates MASSIVE EXPLOSION ATTACK!`);
  }
  
  // 🧀 NEW: CHEESE RUMBLE ATTACK - Epic cheese-themed special!
  function bossCheeseRumbleAttack() {
    if (!boss || bossDefeated) return;
    
    console.log(`🧀 ${boss.name} unleashes the CHEESE RUMBLE ATTACK!`);
    
    // Create cheese rumble bullets (cheese wheel pattern)
    for (let i = 0; i < 12; i++) {
      const angle = (i / 12) * Math.PI * 2;
      const bullet = {
        x: boss.x + boss.width / 2 - 6,
        y: boss.y + boss.height / 2 - 6,
        width: 12, // Big cheese wheels
        height: 12,
        speed: boss.bulletSpeed * 1.4, // Reduced from 1.8x to 1.4x for better balance
        damage: boss.bulletDamage * 2,
        vx: Math.cos(angle) * boss.bulletSpeed * 1.4,
        vy: Math.sin(angle) * boss.bulletSpeed * 1.4,
        color: '#ffdd00', // Cheese yellow
        type: 'cheese_wheel'
      };
      
      bossBullets.push(bullet);
    }
    
    // Create cheese rumble particles
    for (let i = 0; i < 30; i++) {
      createBossParticle(boss.x + Math.random() * boss.width, boss.y + Math.random() * boss.height, 'cheese_rumble');
    }
    
    // Massive screen shake for cheese rumble
    screenShake = 35;
    
    // Play cheese rumble sound effect
    if (window.cheeseSoundManager && window.cheeseSoundManager.soundEnabled) {
      // Create a rumble effect by playing multiple sounds
      for (let i = 0; i < 3; i++) {
        setTimeout(() => {
          window.cheeseSoundManager.playStarWarsLaser();
        }, i * 100);
      }
    }
  }
  
  // 🚀 NEW: Boss cheese storm attack - devastating multi-directional barrage!
  function bossCheeseStormAttack() {
    if (!boss || bossDefeated) return;
    
    console.log(`👑 ${boss.name} unleashes the DEVASTATING CHEESE STORM!`);
    
    // Create 20 bullets in all directions with varying speeds
    for (let i = 0; i < 20; i++) {
      const angle = (i / 20) * Math.PI * 2;
      const speed = boss.bulletSpeed * (1.5 + Math.random() * 1.5); // Variable speed
      
      bossBullets.push({
        x: boss.x + boss.width / 2 - 6,
        y: boss.y + boss.height / 2 - 6,
        width: 12,
        height: 12,
        speed: speed,
        damage: boss.bulletDamage * 2, // High damage
        vx: Math.cos(angle) * speed,
        vy: Math.sin(angle) * speed,
        color: '#ff6600', // Orange cheese color
        isCheeseStorm: true
      });
    }
    
    // Extreme screen shake for cheese storm
    screenShake = 35;
    
    // Create massive storm particles
    for (let i = 0; i < 60; i++) {
      createBossParticle(boss.x + Math.random() * boss.width, boss.y + Math.random() * boss.height, 'cheese_storm');
    }
    
    // Play storm sound effects
    if (window.cheeseSoundManager && window.cheeseSoundManager.soundEnabled) {
      for (let i = 0; i < 5; i++) {
        setTimeout(() => {
          window.cheeseSoundManager.playStarWarsLaserVariant();
        }, i * 150);
      }
    }
    
    console.log(`🌪️ ${boss.name} creates ${20} bullets in devastating cheese storm!`);
  }

  // 🚀 NEW: Boss teleport attack - MUCH more aggressive!
  function bossTeleportAttack() {
    if (!boss || bossDefeated) return;
    
    // Teleport to random position with better positioning
    const newX = Math.random() * (canvasWidth - boss.width);
    const newY = 80 + Math.random() * 120; // Better Y range
    
    // Create massive teleport particles at old position
    for (let i = 0; i < 20; i++) { // More particles
      createBossParticle(boss.x + boss.width / 2, boss.y + boss.height / 2, 'teleport');
    }
    
    // Teleport boss
    boss.x = newX;
    boss.y = newY;
    
    // Create massive teleport particles at new position
    for (let i = 0; i < 20; i++) { // More particles
      createBossParticle(boss.x + boss.width / 2, boss.y + boss.height / 2, 'teleport');
    }
    
    // Screen shake for teleport
    screenShake = 18;
    
    console.log(`👑 ${boss.name} TELEPORTS AGGRESSIVELY to new position!`);
  }

  // 🚀 NEW: Boss teleport ability - MUCH more aggressive!
  function bossTeleport() {
    if (!boss || bossDefeated) return;
    
    // Quick teleport to dodge with larger range
    const newX = Math.max(0, Math.min(canvasWidth - boss.width, boss.x + (Math.random() - 0.5) * 150));
    boss.x = newX;
    
    // Create massive teleport particles
    for (let i = 0; i < 12; i++) { // More particles
      createBossParticle(boss.x + boss.width / 2, boss.y + boss.height / 2, 'teleport');
    }
    
    // Small screen shake for teleport
    screenShake = 8;
  }

  // 🚀 NEW: Boss shield activation - MUCH more aggressive!
  function bossActivateShield() {
    if (!boss || bossDefeated) return;
    
    boss.shieldActive = true;
    boss.shieldTimer = Date.now();
    
    // Create massive shield particles
    for (let i = 0; i < 15; i++) { // More particles
      createBossParticle(boss.x + boss.width / 2, boss.y + boss.height / 2, 'shield');
    }
    
    // Shield lasts for 4 seconds (longer)
    setTimeout(() => {
      if (boss) {
        boss.shieldActive = false;
        console.log(`👑 ${boss.name} shield deactivated!`);
      }
    }, 4000);
    
    // Screen shake for shield activation
    screenShake = 12;
    
    console.log(`👑 ${boss.name} activates POWERFUL SHIELD!`);
  }

  function checkBossCollisions() {
    if (!boss || bossDefeated) return;
    
    // 🚨 CRITICAL FIX: Check if boss is too close to player to prevent instant death
    const bossPlayerDistance = Math.abs(boss.y - playerShip.y);
    if (bossPlayerDistance < 50) { // If boss is within 50 pixels of player
      console.log(`🚨 BOSS TOO CLOSE TO PLAYER! Distance: ${bossPlayerDistance}px - Moving boss up!`);
      boss.y = Math.max(50, boss.y - 10); // Move boss up away from player
    }
    
    // 🧪 DEBUG: Log collision check
    if (bullets.length > 0 && Date.now() % 1000 < 16) { // Log every second
      console.log(`🧪 Checking ${bullets.length} bullets vs boss at (${boss.x}, ${boss.y})`);
    }
    
    // Check player bullets hitting boss
    bullets.forEach((bullet, bulletIndex) => {
      if (checkCollision(bullet, boss)) {
        console.log(`💥 COLLISION DETECTED: Bullet hit boss!`);
        // 🚀 NEW: Check if boss has active shield
        if (boss.shieldActive) {
          console.log(`🛡️ ${boss.name} shield blocks the attack!`);
          bullets.splice(bulletIndex, 1);
          createExplosion(bullet.x, bullet.y);
          
          // Create shield block particles
          for (let i = 0; i < 5; i++) {
            createBossParticle(bullet.x, bullet.y, 'shield_block');
          }
          return; // Don't take damage
        }
        
        // 🚀 NEW: Check if boss has invincibility frames
        if (boss.invincibilityFrames > 0) {
          console.log(`✨ ${boss.name} is temporarily invincible!`);
          bullets.splice(bulletIndex, 1);
          return; // Don't take damage
        }
        
        // Remove bullet
        bullets.splice(bulletIndex, 1);
        
        // 🚀 ENHANCED: Damage calculation based on weapon type
        let damage = 1;
        if (bullet.type === 'laser') damage = 3;
        else if (bullet.type === 'bomb') damage = 5;
        
        // 🚀 NEW: Extra damage against first boss to make it beatable
        if (boss.type === 'cheeseKing') {
          damage *= 2; // Double damage against first boss for easier fights
        }
        
        // 🚀 NEW: Boss takes damage with invincibility frames
        boss.health -= damage;
        
        // 🚀 NEW: No invincibility frames for first boss to make it very easy
        if (boss.type === 'cheeseKing') {
          boss.invincibilityFrames = 0; // No invincibility for tutorial boss
        } else {
          boss.invincibilityFrames = 1; // Normal invincibility for other bosses
        }
        
        // 🚀 NEW: Create enhanced hit effects
        createExplosion(bullet.x, bullet.y);
        
        // Create boss hit particles
        for (let i = 0; i < 8; i++) {
          createBossParticle(bullet.x, bullet.y, 'hit');
        }
        
        // 🚀 NEW: Screen shake on boss hit
        screenShake = 8;
        
        // 🚀 NEW: Boss hit sound effect
        if (window.cheeseSoundManager && window.cheeseSoundManager.soundEnabled) {
          window.cheeseSoundManager.playStarWarsLaserVariant();
        }
        
        console.log(`💥 ${boss.name} takes ${damage} damage! Health: ${boss.health}/${boss.maxHealth}`);
        
        // 🚀 DEBUG: Log boss health every hit to track progress
        if (boss.health % 500 === 0 || boss.health <= 100) {
          console.log(`🔍 BOSS HEALTH UPDATE: ${boss.name} at ${boss.health}/${boss.maxHealth} HP (${Math.round((boss.health/boss.maxHealth)*100)}%)`);
        }
        
        // Check if boss is defeated
        if (boss.health <= 0 && !bossDefeated) {
          bossDefeated = true;
          boss.health = 0; // Ensure it stays at 0
          bossReward = Math.floor(bossReward * (1 + (waveNumber / 100))); // Bonus for higher waves
          console.log(`👑 BOSS DEFEATED! ${boss.name} has been vanquished! Reward: ${bossReward} DSPOINC`);
          console.log(`🎉 Final boss stats: Wave ${waveNumber}, Type: ${boss.type}, Max Health: ${boss.maxHealth}`);
          
          // 🚀 CRITICAL FIX: Immediately clear boss bullets to prevent game over
          bossBullets = [];
          console.log(`🧹 Boss bullets cleared immediately to prevent player death`);
          
                  // Add reward to score
        spaceInvadersCount += bossReward; // Convert DSPOINC to invader count for scoring
        
        // 🚀 CRITICAL FIX: Also add to traditional score for consistency
        spaceInvadersScore += bossReward * 5; // Convert invader count to traditional points (1 DSPOINC = 5 points) (REDUCED for balance)
          
          // 🚀 NEW: Epic boss defeat effects (reduced intensity)
          bossDefeatEffect = 60; // Reduced from 100 to 60 frames
          screenShake = 15; // Reduced from 30 to 15 for less disorienting effect
          
          // Create defeat particles (reduced amount)
          for (let i = 0; i < 20; i++) {
            setTimeout(() => {
              createBossParticle(
                boss.x + Math.random() * boss.width,
                boss.y + Math.random() * boss.height,
                'defeat'
              );
            }, i * 40);
          }
          
          // 🚀 NEW: Create boss defeat celebration (reduced amount)
          for (let i = 0; i < 10; i++) {
            setTimeout(() => {
              createExplosion(
                boss.x + Math.random() * boss.width,
                boss.y + Math.random() * boss.height
              );
            }, i * 80);
          }
          
          // 🚀 NEW: Progressive Boss Upgrade Rewards
          if (boss.type === 'cheeseKing' && !hasDoubleShotUpgrade) {
            hasDoubleShotUpgrade = true;
            console.log(`🎯 DOUBLE SHOT UPGRADE UNLOCKED! You can now fire two bullets at once!`);
            console.log(`🔥 Your shooting power has been permanently upgraded!`);
            
            // Show upgrade notification on screen
            setTimeout(() => {
              console.log(`✨ UPGRADE NOTIFICATION: Double Shot mode activated!`);
            }, 2000);
          }
          
          if (boss.type === 'cheeseEmperor' && !hasTripleShotUpgrade) {
            hasTripleShotUpgrade = true;
            console.log(`🎯 TRIPLE SHOT UPGRADE UNLOCKED! You can now fire three bullets at once!`);
            console.log(`🔥 Your shooting power has been massively upgraded!`);
            
            // Show upgrade notification on screen
            setTimeout(() => {
              console.log(`✨ UPGRADE NOTIFICATION: Triple Shot mode activated!`);
            }, 2000);
          }
          
          if (boss.type === 'cheeseGod' && !hasQuadShotUpgrade) {
            hasQuadShotUpgrade = true;
            console.log(`🎯 QUAD SHOT UPGRADE UNLOCKED! You can now fire four bullets at once!`);
            console.log(`🔥 Your shooting power has been ULTIMATELY upgraded!`);
            
            // Show upgrade notification on screen
            setTimeout(() => {
              console.log(`✨ UPGRADE NOTIFICATION: Quad Shot mode activated!`);
            }, 2000);
          }
          
          // 🚀 NEW: Special boss defeat message
          setTimeout(() => {
            console.log(`🎉 CONGRATULATIONS! You defeated the ${boss.name}!`);
            console.log(`🏆 You earned ${bossReward} DSPOINC for this victory!`);
            console.log(`🚀 The next waves will be even more challenging...`);
          }, 1000);
        }
      }
    });
    
    // Check boss bullets hitting player
    bossBullets.forEach((bullet, bulletIndex) => {
      // 🚀 CRITICAL FIX: Don't process boss bullets if boss is defeated
      if (bossDefeated) {
        bossBullets.splice(bulletIndex, 1);
        return;
      }
      
      if (checkCollision(bullet, playerShip)) {
        // 🚀 NEW: Check if player is invincible
        if (playerShip.invincible && playerShip.invincibleTimer > 0) {
          console.log('🛡️ Player invincible - boss bullet blocked!');
          bossBullets.splice(bulletIndex, 1);
          return; // Don't take damage
        }
        
        // Remove bullet
        bossBullets.splice(bulletIndex, 1);
        
        // Damage player
        playerShip.health--;
        createExplosion(playerShip.x + playerShip.width / 2, playerShip.y + playerShip.height / 2);
        
        if (playerShip.health <= 0) {
          onGameOver();
        }
      }
    });
  }

  function drawBoss() {
    if (!boss || bossDefeated) {
      if (!boss) {
        console.log(`❌ DRAW BOSS: No boss object to draw`);
      } else {
        console.log(`✅ DRAW BOSS: Boss defeated, not drawing`);
      }
      return;
    }
    
    // 🚀 DEBUG: Log boss drawing
    if (phaseTimer % 200 === 0) { // Every 20 seconds
      console.log(`🎨 DRAWING BOSS: ${boss.name} at x=${boss.x}, y=${boss.y}, phase=${bossPhase}, defeated=${bossDefeated}`);
    }
    
          // 🚀 NEW: Handle defeated boss drawing
      if (bossDefeated) {
        // Draw defeated boss with special effects
        ctx.save();
        
        // Apply rotation for defeated boss
        const centerX = boss.x + boss.width / 2;
        const centerY = boss.y + boss.height / 2;
        ctx.translate(centerX, centerY);
        ctx.rotate(boss.rotation || 0);
        ctx.translate(-centerX, -centerY);
        
        // Draw defeated boss with red tint
        ctx.globalAlpha = 0.7;
        ctx.filter = 'brightness(0.5) saturate(2)';
        
        // Draw the boss using fallback logic
        ctx.fillStyle = '#ff0000';
        ctx.fillRect(boss.x, boss.y, boss.width, boss.height);
        
        ctx.restore();
        return;
      }
    
    // 🚀 ENHANCED: Save context for transformations
    ctx.save();
    
    // 🚀 NEW: Apply boss size scaling
    const centerX = boss.x + boss.width / 2;
    const centerY = boss.y + boss.height / 2;
    ctx.translate(centerX, centerY);
    ctx.scale(boss.size, boss.size);
    ctx.translate(-centerX, -centerY);
    
    // 🚀 NEW: Apply wobble animation
    if (boss.wobble > 0) {
      const wobbleAmount = Math.sin(boss.wobble) * 2;
      ctx.translate(wobbleAmount, 0);
    }
    
    // 🚀 NEW: Draw custom boss image based on type
    let bossImg = null;
    switch (boss.type) {
      case 'cheeseKing':
        bossImg = bossImages.cheeseKing;
        break;
      case 'cheeseEmperor':
        bossImg = bossImages.cheeseEmperor;
        break;
      case 'cheeseGod':
        bossImg = bossImages.cheeseGod;
        break;
      case 'cheeseDestroyer':
        bossImg = bossImages.cheeseDestroyer;
        break;
    }
    
    // 🚀 NEW: Draw boss with enhanced effects
    if (bossImg && bossImg.complete && bossImg.naturalWidth > 0) {
      ctx.drawImage(bossImg, boss.x, boss.y, boss.width, boss.height);
    } else {
      // 🚀 ENHANCED: Fallback to colored rectangle with boss-specific colors and effects
      let fallbackColor = '#ff0000';
      let glowColor = '#ffffff';
      
      switch (boss.type) {
        case 'cheeseKing': 
          fallbackColor = '#ff6b35'; 
          glowColor = '#ffa500';
          break;
        case 'cheeseEmperor': 
          fallbackColor = '#8b5cf6'; 
          glowColor = '#c084fc';
          break;
        case 'cheeseGod': 
          fallbackColor = '#f59e0b'; 
          glowColor = '#fbbf24';
          break;
        case 'cheeseDestroyer': 
          fallbackColor = '#dc2626'; 
          glowColor = '#fca5a5';
          break;
      }
      
      // 🚀 NEW: Draw boss glow effect
      if (boss.rageMode) {
        ctx.shadowColor = glowColor;
        ctx.shadowBlur = 20;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 0;
      }
      
      // 🚀 NEW: Draw boss with invincibility flash
      if (boss.invincibilityFrames > 0) {
        ctx.globalAlpha = 0.5 + (Math.sin(Date.now() * 0.1) * 0.3);
      }
      
      ctx.fillStyle = fallbackColor;
      ctx.fillRect(boss.x, boss.y, boss.width, boss.height);
      
      // 🚀 NEW: Draw boss shield effect
      if (boss.shieldActive) {
        ctx.strokeStyle = '#00ffff';
        ctx.lineWidth = 3;
        ctx.strokeRect(boss.x - 5, boss.y - 5, boss.width + 10, boss.height + 10);
        
        // Shield glow
        ctx.shadowColor = '#00ffff';
        ctx.shadowBlur = 15;
        ctx.strokeRect(boss.x - 5, boss.y - 5, boss.width + 10, boss.height + 10);
      }
      
      // Reset effects
      ctx.globalAlpha = 1.0;
      ctx.shadowBlur = 0;
    }
    
    // 🚀 NEW: Draw custom health bar image if loaded
    if (bossHealthBarImg && bossHealthBarImg.complete && bossHealthBarImg.naturalWidth > 0) {
      // Draw custom health bar background
      ctx.drawImage(bossHealthBarImg, boss.x, boss.y - 20, 120, 10);
      
      // Draw health bar fill overlay
      const healthPercentage = boss.health / boss.maxHealth;
      const healthBarWidth = 120;
      const healthBarHeight = 10;
      const healthBarX = boss.x;
      const healthBarY = boss.y - 20;
      
      // Health bar fill with boss-specific colors
      let healthColor = '#00ff00';
      if (healthPercentage <= 0.25) {
        healthColor = '#ff0000'; // Red when critical
      } else if (healthPercentage <= 0.5) {
        healthColor = '#ffff00'; // Yellow when medium
      }
      
      ctx.fillStyle = healthColor;
      ctx.fillRect(healthBarX, healthBarY, healthBarWidth * healthPercentage, healthBarHeight);
    } else {
      // Fallback to basic health bar
      const healthBarWidth = 120;
      const healthBarHeight = 10;
      const healthBarX = boss.x;
      const healthBarY = boss.y - 20;
      
      // Health bar background
      ctx.fillStyle = '#333333';
      ctx.fillRect(healthBarX, healthBarY, healthBarWidth, healthBarHeight);
      
      // Health bar border
      ctx.strokeStyle = '#ffffff';
      ctx.lineWidth = 2;
      ctx.strokeRect(healthBarX, healthBarY, healthBarWidth, healthBarHeight);
    }
    
    // Boss name and level indicator
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 16px Arial';
    ctx.textAlign = 'center';
    ctx.fillText(`${boss.name} - Wave ${waveNumber}`, boss.x + boss.width / 2, boss.y - 30);
    ctx.textAlign = 'left';
    
    // 🚀 NEW: Restore context after all transformations
    ctx.restore();
  }

  function drawBossBullets() {
    bossBullets.forEach((bullet, index) => {
      ctx.save();
      
      // 🧀 Handle different cheese bullet types with special effects
      if (bullet.type === 'cheese_cannon') {
        // 🧀 CHEESE CANNON - Large glowing yellow shot
        ctx.fillStyle = bullet.color;
        ctx.shadowColor = '#ffdd00';
        ctx.shadowBlur = 15;
        ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
        
        // Extra glow effect
        if (bullet.glow) {
          ctx.globalAlpha = 0.5;
          ctx.fillStyle = '#ffffaa';
          ctx.fillRect(bullet.x - 2, bullet.y - 2, bullet.width + 4, bullet.height + 4);
        }
        
      } else if (bullet.type === 'melted_cheese') {
        // 🧀 MELTED CHEESE - Drippy orange cheese with trail
        ctx.fillStyle = bullet.color;
        ctx.shadowColor = '#ff8c00';
        ctx.shadowBlur = 10;
        
        // Draw melted cheese with irregular shape
        ctx.beginPath();
        ctx.ellipse(bullet.x + bullet.width/2, bullet.y + bullet.height/2, 
                   bullet.width/2, bullet.height/2 + 2, 0, 0, Math.PI * 2);
        ctx.fill();
        
        // Trail effect
        if (bullet.trail) {
          ctx.globalAlpha = 0.3;
          ctx.fillStyle = '#ffaa44';
          for (let i = 1; i <= 3; i++) {
            ctx.fillRect(bullet.x - i * 2, bullet.y - i * 4, bullet.width, bullet.height * 0.8);
          }
        }
        
      } else if (bullet.type === 'cheese_wheel') {
        // 🧀 CHEESE WHEEL - Spinning golden wheel
        const centerX = bullet.x + bullet.width / 2;
        const centerY = bullet.y + bullet.height / 2;
        
        ctx.translate(centerX, centerY);
        ctx.rotate(bullet.rotation || 0);
        ctx.translate(-centerX, -centerY);
        
        ctx.fillStyle = bullet.color;
        ctx.shadowColor = '#ffd700';
        ctx.shadowBlur = 8;
        
        // Draw cheese wheel
        ctx.beginPath();
        ctx.arc(centerX, centerY, bullet.width/2, 0, Math.PI * 2);
        ctx.fill();
        
        // Add cheese holes
        ctx.fillStyle = '#cc9900';
        for (let i = 0; i < 3; i++) {
          const angle = (i / 3) * Math.PI * 2 + (bullet.rotation || 0);
          const holeX = centerX + Math.cos(angle) * 4;
          const holeY = centerY + Math.sin(angle) * 4;
          ctx.beginPath();
          ctx.arc(holeX, holeY, 2, 0, Math.PI * 2);
          ctx.fill();
        }
        
      } else if (bullet.type === 'gouda_grenade') {
        // 🧀 GOUDA GRENADE - Pulsating explosive cheese
        const pulse = Math.sin(Date.now() * 0.1) * 0.2 + 1;
        ctx.fillStyle = bullet.color;
        ctx.shadowColor = '#ffaa00';
        ctx.shadowBlur = 12 * pulse;
        
        // Draw pulsating grenade
        ctx.beginPath();
        ctx.arc(bullet.x + bullet.width/2, bullet.y + bullet.height/2, 
               (bullet.width/2) * pulse, 0, Math.PI * 2);
        ctx.fill();
        
        // Warning glow when about to explode
        if (bullet.timer < 30) {
          ctx.globalAlpha = 0.7;
          ctx.fillStyle = '#ff0000';
          ctx.beginPath();
          ctx.arc(bullet.x + bullet.width/2, bullet.y + bullet.height/2, 
                 bullet.width/2 + 4, 0, Math.PI * 2);
          ctx.fill();
        }
        
      } else if (bullet.type === 'swiss_sniper') {
        // 🧀 SWISS SNIPER - White piercing shot with holes
        ctx.fillStyle = bullet.color;
        ctx.shadowColor = '#ffffff';
        ctx.shadowBlur = 20;
        
        // Draw main bullet
        ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
        
        // Add swiss holes
        ctx.fillStyle = '#cccccc';
        ctx.fillRect(bullet.x + 2, bullet.y + 4, 2, 2);
        ctx.fillRect(bullet.x + 4, bullet.y + 8, 2, 2);
        
        if (bullet.glow) {
          ctx.globalAlpha = 0.6;
          ctx.fillStyle = '#ffffff';
          ctx.fillRect(bullet.x - 3, bullet.y - 3, bullet.width + 6, bullet.height + 6);
        }
        
      } else if (bullet.type === 'cheddar_chaos') {
        // 🧀 CHEDDAR CHAOS - Hot orange chaos with heat waves
        ctx.fillStyle = bullet.color;
        ctx.shadowColor = '#ff6600';
        ctx.shadowBlur = 15;
        
        // Draw main bullet
        ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
        
        // Heat effect
        if (bullet.heat) {
          for (let i = 0; i < 3; i++) {
            ctx.globalAlpha = 0.3 - i * 0.1;
            ctx.fillStyle = `hsl(${30 - i * 10}, 100%, 60%)`;
            ctx.fillRect(bullet.x - i, bullet.y - i, bullet.width + i * 2, bullet.height + i * 2);
          }
        }
        
      } else if (bullet.type === 'parmesan_pulse') {
        // 🧀 PARMESAN PULSE - Expanding wave bullets
        const waveAlpha = 0.8 - (bullet.wave * 0.2);
        ctx.globalAlpha = waveAlpha;
        ctx.fillStyle = bullet.color;
        ctx.shadowColor = '#ffffaa';
        ctx.shadowBlur = 6;
        
        ctx.beginPath();
        ctx.arc(bullet.x + bullet.width/2, bullet.y + bullet.height/2, 
               bullet.width/2, 0, Math.PI * 2);
        ctx.fill();
        
      } else if (bullet.type === 'brie_blast') {
        // 🧀 BRIE BLAST - Soft wobbly cheese
        ctx.fillStyle = bullet.color;
        ctx.shadowColor = '#fff8dc';
        ctx.shadowBlur = 8;
        
        // Draw wobbly brie
        const wobbleOffset = bullet.wobbleTime ? Math.sin(bullet.wobbleTime) * 2 : 0;
        ctx.beginPath();
        ctx.ellipse(bullet.x + bullet.width/2 + wobbleOffset, bullet.y + bullet.height/2, 
                   bullet.width/2, bullet.height/2, 0, 0, Math.PI * 2);
        ctx.fill();
        
      } else if (bullet.type === 'explosion_fragment') {
        // 🧀 EXPLOSION FRAGMENTS - Small fading pieces
        const alpha = bullet.life / 60;
        ctx.globalAlpha = alpha;
        ctx.fillStyle = bullet.color;
        ctx.shadowColor = bullet.color;
        ctx.shadowBlur = 5;
        
        ctx.beginPath();
        ctx.arc(bullet.x + bullet.width/2, bullet.y + bullet.height/2, 
               bullet.width/2, 0, Math.PI * 2);
        ctx.fill();
        
      } else if (bullet.duration) {
        // 🚀 LASER BULLETS
        ctx.fillStyle = bullet.color || '#ff0000';
        ctx.shadowColor = bullet.color || '#ff0000';
        ctx.shadowBlur = 10;
        ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
        
        // Laser glow effect
        ctx.globalAlpha = 0.6;
        ctx.fillRect(bullet.x - 2, bullet.y, bullet.width + 4, bullet.height);
        
      } else {
        // DEFAULT BULLETS - Enhanced effects for regular bullets
        ctx.fillStyle = bullet.color || '#ff0000';
        ctx.shadowColor = bullet.color || '#ff0000';
        ctx.shadowBlur = 8;
        
        // Draw with boss-specific colors
        let bulletColor = bullet.color || '#ff0000';
        if (boss) {
          switch (boss.type) {
            case 'cheeseKing': bulletColor = '#ff6b35'; break;      // Orange-red
            case 'cheeseEmperor': bulletColor = '#8b5cf6'; break;   // Purple
            case 'cheeseGod': bulletColor = '#f59e0b'; break;       // Gold
            case 'cheeseDestroyer': bulletColor = '#dc2626'; break; // Dark red
          }
        }
        ctx.fillStyle = bulletColor;
        ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
        
        // Add glow effect
        ctx.globalAlpha = 0.4;
        ctx.fillRect(bullet.x - 1, bullet.y - 1, bullet.width + 2, bullet.height + 2);
      }
      
      ctx.restore();
    });
  }

  // 🚀 NEW: Boss Effects Functions
  function updateBossEffects() {
    // Update screen shake
    if (screenShake > 0) {
      screenShake--;
    }
    
    // 🚀 NEW: Update boss defeat effect
    if (bossDefeatEffect > 0) {
      bossDefeatEffect--;
      // During defeat effect, don't add extra screen shake
      if (bossDefeatEffect <= 0) {
        console.log('✅ Boss defeat effect complete - cleaning up boss and transitioning to next wave');
        // Clean up boss after defeat effect
        if (bossDefeated) {
          // Clear all boss-related objects
          boss = null;
          bossDefeated = false;
          bossPhase = 'none';
          bossBullets = [];
          bossParticles = [];
          screenShake = 0; // Force stop any remaining screen shake
          
          // Transition back to normal waves
          gamePhase = 'formation';
          phaseTimer = 0;
          waveNumber++;
          invaderDropPhase = false;
          dropStartTime = Date.now();
          
          // Spawn the next wave
          spawnNewWave();
          
          console.log('🧹 Boss cleanup complete - transitioned to wave ' + waveNumber);
          console.log('🚀 Returning to normal waves after epic boss battle!');
        }
      }
    }
    
    // Update boss glow effect
    bossGlowEffect += 0.1;
    
    // Update boss particles
    bossParticles.forEach((particle, index) => {
      particle.x += particle.vx;
      particle.y += particle.vy;
      particle.life--;
      
      if (particle.life <= 0) {
        bossParticles.splice(index, 1);
      }
    });
  }
  
  // 🚀 NEW: Update boss bullets movement
  function updateBossBullets() {
    if (!bossBullets || bossDefeated) return;
    
    bossBullets.forEach((bullet, index) => {
      // Update bullet position
      bullet.x += bullet.vx;
      bullet.y += bullet.vy;
      
      // Remove bullets that are off screen
      if (bullet.x < -50 || bullet.x > canvasWidth + 50 || 
          bullet.y < -50 || bullet.y > canvasHeight + 50) {
        bossBullets.splice(index, 1);
      }
    });
  }

  function createBossParticle(x, y, type) {
    const particle = {
      x: x,
      y: y,
      vx: (Math.random() - 0.5) * 4,
      vy: (Math.random() - 0.5) * 4,
      life: 30 + Math.random() * 30,
      type: type,
      size: 2 + Math.random() * 3
    };
    
    bossParticles.push(particle);
  }

  function drawBossEffects() {
    if (!boss || bossDefeated) return;
    
    // 🚀 CRITICAL FIX: Don't draw effects for defeated boss
    if (bossDefeated) {
      return;
    }
    
    // 🚀 NEW: Draw boss glow effect
    if (bossGlowEffect > 0) {
      const glowIntensity = Math.sin(bossGlowEffect) * 0.3 + 0.7;
      ctx.shadowColor = getBossGlowColor();
      ctx.shadowBlur = 20 * glowIntensity;
      
      // Draw glow behind boss
      ctx.globalAlpha = 0.3 * glowIntensity;
      ctx.fillStyle = getBossGlowColor();
      ctx.fillRect(boss.x - 10, boss.y - 10, boss.width + 20, boss.height + 20);
      ctx.globalAlpha = 1.0;
      ctx.shadowBlur = 0;
    }
    
    // 🚀 NEW: Draw boss particles
    bossParticles.forEach(particle => {
      ctx.globalAlpha = particle.life / 60;
      ctx.fillStyle = getBossParticleColor(particle.type);
      ctx.fillRect(particle.x, particle.y, particle.size, particle.size);
    });
    ctx.globalAlpha = 1.0;
  }

  function getBossGlowColor() {
    if (boss && boss.colors && boss.colors.primary) {
      return boss.colors.primary;
    }
    
    // Fallback to old system
    switch (boss.type) {
      case 'cheeseKing': return '#ff6b35';      // Orange-red
      case 'cheeseEmperor': return '#8b5cf6';   // Purple
      case 'cheeseGod': return '#f59e0b';       // Gold
      case 'cheeseDestroyer': return '#dc2626'; // Dark red
      default: return '#ff0000';
    }
  }

  function getBossParticleColor(type) {
    switch (type) {
      case 'entrance': return '#ffffff';
      case 'attack': return '#ff0000';
      case 'defeat': return '#ffff00';
      case 'rage': return '#ff6600'; // Orange for rage mode
      case 'shield_block': return '#00ffff'; // Cyan for shield blocks
      case 'hit': return '#ff00ff'; // Magenta for hits
      case 'laser': return '#ff0000'; // Red for laser
      case 'explosion': return '#ff6600'; // Orange for explosions
      case 'teleport': return '#00ffff'; // Cyan for teleport
      case 'shield': return '#00ffff'; // Cyan for shield
      case 'cheese_rumble': return '#ffdd00'; // Cheese yellow for rumble
      case 'cheese_storm': return '#ff6600'; // Orange for cheese storm
      default: return '#ffffff';
    }
  }

  // 🚀 NEW: Draw boss wave announcement
  function drawBossAnnouncement() {
    if (!boss || bossDefeated) return;
    
    // Only show full announcement during entrance phase
    if (boss.phase === 'entrance') {
      // Background overlay for entrance announcement
      ctx.fillStyle = 'rgba(0, 0, 0, 0.8)';
      ctx.fillRect(0, 0, canvasWidth, 80);
      
      // Boss entrance announcement text
      ctx.fillStyle = '#ff0000';
      ctx.font = 'bold 28px Arial';
      ctx.textAlign = 'center';
      
      const bossText = `👑 BOSS WAVE ${waveNumber}`;
      ctx.fillText(bossText, canvasWidth / 2, 30);
      
      ctx.fillStyle = '#ffff00';
      ctx.font = 'bold 24px Arial';
      ctx.fillText(boss.name.toUpperCase(), canvasWidth / 2, 60);
    } else {
      // During fight - show only boss name and health in smaller, less intrusive way
      ctx.fillStyle = 'rgba(0, 0, 0, 0.4)';
      ctx.fillRect(0, 0, canvasWidth, 50);
      
      // Boss name
      ctx.fillStyle = '#ffff00';
      ctx.font = 'bold 18px Arial';
      ctx.textAlign = 'center';
      ctx.fillText(`👑 ${boss.name}`, canvasWidth / 2, 20);
      
      // Health bar
      ctx.fillStyle = '#ffffff';
      ctx.font = '14px Arial';
      const healthText = `Health: ${boss.health}/${boss.maxHealth}`;
      ctx.fillText(healthText, canvasWidth / 2, 40);
    }
    
    // Reset text alignment
    ctx.textAlign = 'left';
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

    // 🆘 NEW: Setup Discord ID and name for score saving (same as Tetris/Snake)
    let discordId = localStorage.getItem("discord_id");
    let discordName = localStorage.getItem("discord_name");
    let wallet = localStorage.getItem("user_wallet");
    
    // 🚀 NEW: Setup global mouse controls for shooting anywhere on screen
    setupGlobalMouseControls();
    setupHeatSystemDebug();
    
    // 🚀 CRITICAL FIX: Automatically disable mouse controls on mobile devices
    if (isMobileDevice) {
      console.log('📱 Mobile device detected - disabling mouse controls for touch priority');
      isMouseControlEnabled = false;
    }
    
    // 🛠️ Mock fallback if testing locally
    if (!discordId) {
      discordId = "1337";
      discordName = "Anonymous Mouse";
      localStorage.setItem("discord_id", discordId);
      localStorage.setItem("discord_name", discordName);
      console.log("🔄 Set fallback Discord ID for local testing:", discordId);
    }
    
    if (!wallet) {
      wallet = "TestWallet123456789XYZ";
      localStorage.setItem("user_wallet", wallet);
      console.log("🔄 Set fallback wallet for local testing:", wallet);
    }
    
    console.log("✅ Discord setup complete - ID:", discordId, "Name:", discordName, "Wallet:", wallet);

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
      y: canvasHeight - 120, // 🚀 FIXED: Moved ship further down for better movement range
      width: 40,
      height: 30,
      speed: 5,
      health: 3,
      invincible: false, // 🚀 NEW: Invincibility state
      invincibleTimer: 0 // 🚀 NEW: Invincibility timer
    };

    // Initialize game state
    spaceInvadersScore = 0;
    spaceInvadersCount = 0; // NEW: Reset invader count
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
    
    // 🚀 NEW: Reset boss effects
    boss = null;
    bossDefeated = false;
    screenShake = 0;
    bossParticles = [];
    bossGlowEffect = 0;
    bossEntranceEffect = 0;
    bossDefeatEffect = 0;
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
    
    // 🖱️ NEW: Setup mouse controls for desktop
    setTimeout(() => {
      // 🔧 CRITICAL FIX: Initialize mouse target to ship position
      mouseTargetX = playerShip.x;
      mouseTargetY = playerShip.y;
      console.log(`🖱️ Initial mouse target set to ship position: X=${mouseTargetX}, Y=${mouseTargetY}`);
      
      // 🔧 CRITICAL FIX: Initialize global mouse tracking variables
      window.mouseX = 0;
      window.mouseY = 0;
      window.lastCanvasMouseX = undefined;
      window.lastCanvasMouseY = undefined;
      window.hasMouseMovedInCanvas = false;
      
      setupMouseControls();
      console.log('✅ Mouse controls setup completed');
      
      // 🧀 NEW: Add global mouse tracking for custom cursor
      globalMouseListener = updateCustomCursorPosition;
      document.addEventListener('mousemove', globalMouseListener);
      
      // 🔧 CRITICAL FIX: Add global mouse coordinate tracking for re-entry positioning
      document.addEventListener('mousemove', (e) => {
        window.mouseX = e.clientX;
        window.mouseY = e.clientY;
      });
      
      // Test mouse control variables
      console.log('🖱️ Mouse control test:', {
        isMouseControlEnabled,
        isMouseOverCanvas,
        isSpaceInvadersPaused,
        mouseTargetX: mouseTargetX,
        mouseTargetY: mouseTargetY
      });
    }, 300);
    
    // Initial draw
    draw();
    
    // 🔥 NEW: Create always-visible heat display
    createAlwaysVisibleHeatDisplay();
    
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
        // 🚀 ULTRA DENSE: V-shaped formation - MUCH more invaders for action!
        const vPositions = [
          [2, 0], [3, 0], [4, 0], [5, 0], [6, 0],
          [1, 1], [2, 1], [3, 1], [4, 1], [5, 1], [6, 1], [7, 1],
          [0, 2], [1, 2], [2, 2], [3, 2], [4, 2], [5, 2], [6, 2], [7, 2], [8, 2],
          [0, 3], [1, 3], [2, 3], [3, 3], [4, 3], [5, 3], [6, 3], [7, 3], [8, 3], [9, 3],
          [0, 4], [1, 4], [2, 4], [3, 4], [4, 4], [5, 4], [6, 4], [7, 4], [8, 4], [9, 4]
        ];
        vPositions.forEach(([col, row]) => {
          // 🚀 NEW: Spawn invaders at reasonable distance (canvasHeight - 350 instead of 50)
          const spawnY = canvasHeight - 350 + (row * 35); // Reasonable distance spawn
          invaders.push(createInvader(col * 45 + 30, spawnY, row, 'v_formation'));
        });
        break;
        
      case 'pyramid':
        // 🚀 ULTRA DENSE: Pyramid formation - MUCH more invaders for action!
        for (let row = 0; row < 6; row++) { // Increased from 4 to 6 rows
          const colsInRow = row + 1;
          const startCol = 5 - row; // Adjusted for wider base
          for (let col = 0; col < colsInRow; col++) {
            // 🚀 NEW: Spawn invaders at reasonable distance
            const spawnY = canvasHeight - 350 + (row * 35); // Reasonable distance spawn
            invaders.push(createInvader((startCol + col) * 45 + 30, spawnY, row, 'pyramid'));
          }
        }
        break;
        
      case 'diamond':
        // 🚀 ULTRA DENSE: Diamond formation - MUCH more invaders for action!
        const diamondPositions = [
          [4, 0], [5, 0],
          [3, 1], [4, 1], [5, 1], [6, 1],
          [2, 2], [3, 2], [4, 2], [5, 2], [6, 2], [7, 2],
          [1, 3], [2, 3], [3, 3], [4, 3], [5, 3], [6, 3], [7, 3], [8, 3],
          [2, 4], [3, 4], [4, 4], [5, 4], [6, 4], [7, 4], [8, 4], [9, 4],
          [3, 5], [4, 5], [5, 5], [6, 5]
        ];
        diamondPositions.forEach(([col, row]) => {
          // 🚀 NEW: Spawn invaders at reasonable distance
          const spawnY = canvasHeight - 350 + (row * 35); // Reasonable distance spawn
          invaders.push(createInvader(col * 45 + 30, spawnY, row, 'diamond'));
        });
        break;
        
      case 'cross':
        // 🚀 ULTRA DENSE: Cross formation - MUCH more invaders for action!
        const crossPositions = [
          [4, 0], [4, 1], [4, 2], [4, 3], [4, 4], [4, 5], [4, 6],
          [2, 2], [3, 2], [5, 2], [6, 2],
          [1, 3], [7, 3],
          [0, 4], [8, 4]
        ];
        crossPositions.forEach(([col, row]) => {
          // 🚀 NEW: Spawn invaders at reasonable distance
          const spawnY = canvasHeight - 350 + (row * 35); // Reasonable distance spawn
          invaders.push(createInvader(col * 45 + 30, spawnY, row, 'cross'));
        });
        break;
        
      case 'spiral':
        // 🚀 ULTRA DENSE: Spiral formation - MUCH more invaders for action!
        const spiralPositions = [
          [4, 0], [5, 0], [6, 0], [7, 0],
          [3, 1], [4, 1], [5, 1], [6, 1], [7, 1], [8, 1],
          [2, 2], [3, 2], [4, 2], [5, 2], [6, 2], [7, 2], [8, 2], [9, 2],
          [1, 3], [2, 3], [3, 3], [4, 3], [5, 3], [6, 3], [7, 3], [8, 3], [9, 3], [10, 3],
          [0, 4], [1, 4], [2, 4], [3, 4], [4, 4], [5, 4], [6, 4], [7, 4], [8, 4], [9, 4], [10, 4], [11, 4]
        ];
        spiralPositions.forEach(([col, row]) => {
          // 🚀 NEW: Spawn invaders at reasonable distance
          const spawnY = canvasHeight - 350 + (row * 30); // Reasonable distance spawn, tighter spacing
          invaders.push(createInvader(col * 35 + 20, spawnY, row, 'spiral'));
        });
        break;
        
      case 'random_cluster':
        // 🚀 ULTRA DENSE: Random cluster - MUCH more invaders for action!
        for (let i = 0; i < 25; i++) { // Increased from 12 to 25 invaders
          const col = Math.floor(Math.random() * 10); // Increased from 8 to 10 columns
          const row = Math.floor(Math.random() * 6); // Increased from 4 to 6 rows
          // 🚀 NEW: Spawn invaders at reasonable distance
          const spawnY = canvasHeight - 350 + (row * 35); // Reasonable distance spawn
          invaders.push(createInvader(col * 45 + 30, spawnY, row, 'random_cluster'));
        }
        break;
        
      case 'ultra_swarm':
        // 🚀 ULTRA DENSE: Ultra dense swarm - MUCH more invaders for action!
        for (let row = 0; row < 8; row++) { // Increased from 6 to 8 rows
          for (let col = 0; col < 15; col++) { // Increased from 12 to 15 columns
            // 🚀 NEW: Spawn invaders at reasonable distance
            const spawnY = canvasHeight - 350 + (row * 30); // Reasonable distance spawn, tighter spacing
            invaders.push(createInvader(col * 35 + 20, spawnY, row, 'ultra_swarm'));
          }
        }
        // Add extra random invaders at reasonable distance
        for (let i = 0; i < 25; i++) { // Increased from 15 to 25 extra invaders
          const x = Math.random() * (canvasWidth - 60);
          const y = canvasHeight - 400 + Math.random() * 150; // Reasonable distance random spawns
          invaders.push(createInvader(x, y, Math.floor(Math.random() * 3), 'ultra_swarm_extra'));
        }
        break;
        
      case 'double_formation':
        // 🚀 ULTRA DENSE: Double formation - MUCH more invaders for action!
        // First layer
        for (let row = 0; row < 6; row++) { // Increased from 4 to 6 rows
          for (let col = 0; col < 10; col++) { // Increased from 8 to 10 columns
            // 🚀 NEW: Spawn invaders at reasonable distance
            const spawnY = canvasHeight - 350 + (row * 35); // Reasonable distance spawn
            invaders.push(createInvader(col * 45 + 30, spawnY, row, 'double_formation_1'));
          }
        }
        // Second layer (offset) - also at reasonable distance
        for (let row = 0; row < 5; row++) { // Increased from 3 to 5 rows
          for (let col = 0; col < 8; col++) { // Increased from 6 to 8 columns
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
    fetch(API_BASE_URL + '/api/admin/space-invaders-settings.php')
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
    
    // 🔥 PHOENIX CONFIGURATION LOADING - NEW!
    // Load Phoenix settings from admin interface before starting game
    loadPhoenixConfiguration().then(() => {
      console.log('🔥 Phoenix configuration loaded, starting game...');
      
      spaceInvadersGameInterval = setInterval(gameLoop, 50); // FAST GAME LOOP (50ms instead of 100ms) - MUCH more responsive!
      document.getElementById("start-space-invaders-btn").textContent = "🔄 Restart";
      
      // Lock scroll only when game is actually running
      lockSpaceInvadersScroll();
      
      // 🆘 NEW: Ensure mobile controls are always visible when game starts
      setTimeout(() => {
        ensureMobileControlsVisible();
      }, 100);
    });
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
    spaceInvadersCount = 0; // NEW: Reset invader count
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
    
    // 🔥 CRITICAL FIX: Reset heat system on game start/reset
    weaponHeat = 0;
    isOverheated = false;
    lastOverheatTime = 0;
    lastUnifiedShotTime = 0; // Reset unified firing rate timer
    lastAutoShootTime = 0; // Reset auto-shoot timer
    lastMovementPosition = { x: 0, y: 0 }; // Reset movement position tracking
    console.log('🔥 Heat system reset - weapon ready to fire!');
    
    // 🎯 NOTE: hasDoubleShotUpgrade is NOT reset - permanent upgrade after defeating first boss
    
    playerShip.x = canvasWidth / 2;
    playerShip.health = 3;
    playerShip.invincible = false; // 🚀 NEW: Reset invincibility
    playerShip.invincibleTimer = 0; // 🚀 NEW: Reset invincibility timer
    
      // 🔧 CRITICAL FIX: Initialize mouse targets to ship position to prevent 300px jumps
      mouseTargetX = playerShip.x;
      mouseTargetY = playerShip.y;
      console.log(`🖱️ Mouse targets initialized to ship position: X=${mouseTargetX}, Y=${mouseTargetY}`);
      
      // 🔧 CRITICAL FIX: Initialize global mouse tracking variables
      window.mouseX = 0;
      window.mouseY = 0;
      window.lastCanvasMouseX = undefined;
      window.lastCanvasMouseY = undefined;
      window.hasMouseMovedInCanvas = false;
    
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
    
    // 🚀 NEW: Reset ship cursor when game is reset
    cleanupCustomCursor();
    setTimeout(() => {
      showCustomCursor();
    }, 200);
    
    // 🔥 NEW: Recreate always-visible heat display on game reset
    createAlwaysVisibleHeatDisplay();
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
    
    // 🚀 NEW: Update player invincibility
    updatePlayerInvincibility();
    
    // 🖱️ NEW: Update mouse movement for ship positioning
    updateMouseMovement();
    
    // 🔥 NEW: Update weapon heat system
    updateHeat();
    
    if (gamePhase === 'formation') {
      // Formation phase - much shorter and you can shoot!
      moveInvadersFormation();
      moveBullets();
      moveInvaderBullets();
      moveTetrisDangerItems();
      checkBulletCollisions();
      checkPlayerHit();
      checkTetrisCollisions();
      
        // 🔥 PHOENIX INVADERS: Update Phoenix entities during formation
  if (isPhoenixWave) {
    updatePhoenixEntities();
    
    // 🔥 NEW: Check Phoenix-Player collisions during Phoenix waves
    checkPhoenixPlayerCollisions();
    
    // 🔥 EXCLUSIVE MODE: No regular invader updates during Phoenix waves
    return; // Skip all regular invader logic
  }
      
      // 🚀 NEW: Check invader-player collisions
      checkInvaderPlayerCollisions();
      
      // 🚀 NEW: Update power-ups
      updatePowerUps();
      
      // 🚀 NEW: Spawn power-ups during formation phase too!
      spawnPowerUp();
      
      if (phaseTimer > 20) { // 🚀 ULTRA FAST: 2 seconds at 100ms intervals - MUCH more aggressive!
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
      
      // 🔥 PHOENIX INVADERS: Update Phoenix entities during attack
      if (isPhoenixWave) {
        updatePhoenixEntities();
        
        // 🔥 NEW: Check Phoenix-Player collisions during Phoenix waves
        checkPhoenixPlayerCollisions();
        
        // 🔥 EXCLUSIVE MODE: No regular invader updates during Phoenix waves
        return; // Skip all regular invader logic
      }
      
      // 🚀 NEW: Check invader-player collisions
      checkInvaderPlayerCollisions();
      
      // 🚀 NEW: Update power-ups
      updatePowerUps();
      
      // NEW: Check for stuck or hidden invaders
      checkForStuckInvaders();
      
      // 🚀 ULTRA FAST: Check for wave completion in attack phase
      const aliveInvaders = invaders.filter(invader => invader.alive);
      const currentTime = Date.now();
      const waveTimeLimit = 15000; // 15 seconds per wave
      const timeSinceWaveStart = currentTime - dropStartTime;
      
      // Debug: Log wave status every 5 seconds
      if (phaseTimer % 50 === 0) { // Every 5 seconds
        console.log(`🎯 Wave ${waveNumber} status: ${aliveInvaders.length} invaders alive, ${Math.round(timeSinceWaveStart/1000)}s elapsed`);
      }
      
      if (aliveInvaders.length <= 2 || timeSinceWaveStart > waveTimeLimit) {
        if (aliveInvaders.length <= 2) {
          console.log(`🎯 Wave ${waveNumber} completed! Only ${aliveInvaders.length} invaders left. Spawning wave ${waveNumber + 1}...`);
        } else {
          console.log(`⏰ Wave ${waveNumber} time limit reached (${Math.round(timeSinceWaveStart/1000)}s). Spawning wave ${waveNumber + 1}...`);
        }
        
        // 🚀 NEW: Check if NEXT wave will be a boss wave
        // 🚀 PRODUCTION: Boss waves at proper progression levels
        const nextWave = waveNumber + 1;
        if (nextWave === 10 || nextWave === 25 || nextWave === 75 || nextWave === 100) {
          console.log(`🏆 BOSS WAVE! Wave ${nextWave} will be an epic boss fight!`);
          gamePhase = 'boss';
          phaseTimer = 0;
          waveNumber++;
          spawnBoss();
        } else {
          gamePhase = 'formation';
          phaseTimer = 0;
          waveNumber++;
          invaderDropPhase = false;
          dropStartTime = Date.now(); // Reset drop start time for new wave
          spawnNewWave();
        }
      }
    } else if (gamePhase === 'boss') {
      // 🚀 NEW: Boss phase - boss battle!
      if (Date.now() % 1000 < 16) { // Log every second during boss phase
        console.log(`🧪 BOSS PHASE ACTIVE: Updating boss and checking collisions... Time: ${Date.now()}`);
      }
      updateBoss();
      moveBullets();
      checkBossCollisions();
      checkPlayerHit();
      
      // 🚀 NEW: Update power-ups during boss fight
      updatePowerUps();
      
      // 🚀 NEW: Spawn power-ups during boss phase too!
      spawnPowerUp();
      
      // 🚀 CRITICAL FIX: Boss phase timer - boss fights last UNTIL DEFEATED (no time limit!)
      phaseTimer++;
      
      // 🚀 CLEANED UP: Boss transition now handled in updateBossEffects() after defeat animation
      // The boss cleanup and transition is now managed by the bossDefeatEffect countdown in updateBossEffects()
      
      // Phase transitions - check if only 2 or fewer invaders are left
      const aliveInvaders = invaders.filter(invader => invader.alive);
      
      // 🚀 NEW: Boss phase wave progression handled in attack phase
    }
    
    // Spawn Tetris danger items periodically - much more frequent with snake invaders!
    const currentTime = Date.now();
    const tetrisSpawnInterval = Math.max(1500, 8000 - (waveNumber - 1) * 800); // Much faster spawning
    if (currentTime - lastTetrisSpawnTime > tetrisSpawnInterval) {
      spawnTetrisDangerItem();
      lastTetrisSpawnTime = currentTime;
    }
    
    // 🚀 ULTRA AGGRESSIVE: Spawn power-ups much more frequently!
    spawnPowerUp();
    
    // 🚀 BONUS: Extra power-up spawn chance for more action!
    if (Math.random() < 0.05) { // 5% bonus chance every game loop
      spawnPowerUp();
    }
    
    // 🚀 WAVE BOOST: Higher waves get even more power-ups!
    if (waveNumber >= 10) {
      if (Math.random() < 0.1) { // 10% extra chance for wave 10+
        spawnPowerUp();
      }
    }
    if (waveNumber >= 20) {
      if (Math.random() < 0.15) { // 15% extra chance for wave 20+
        spawnPowerUp();
      }
    }
    if (waveNumber >= 30) {
      if (Math.random() < 0.2) { // 20% extra chance for wave 30+
        spawnPowerUp();
      }
    }
    
    // 🚀 FORCE SPAWN: If no power-ups on screen for too long, force spawn one
    if (!window.powerUps || window.powerUps.length === 0) {
      if (Math.random() < 0.5) { // 50% chance to force spawn when screen is empty (was 30%)
        console.log('🚨 FORCE SPAWNING power-up - screen was empty!');
        spawnPowerUp();
      }
    }
    
    // 🚀 DEBUG: Log power-up status every 2 seconds
    if (phaseTimer % 20 === 0) { // Every 2 seconds at 50ms intervals
      console.log(`🎁 Power-up status: ${window.powerUps ? window.powerUps.length : 0} power-ups on screen, Wave: ${waveNumber}, Phase: ${gamePhase}`);
    }
    
    // 🚀 FORCE WAVE PROGRESSION: If stuck in first wave for too long, force progression
    if (waveNumber === 1 && gamePhase === 'attack') {
      const timeSinceGameStart = Date.now() - dropStartTime;
      if (timeSinceGameStart > 30000) { // 30 seconds in first wave
        console.log('🚨 FORCE WAVE PROGRESSION - stuck in first wave too long!');
        gamePhase = 'formation';
        phaseTimer = 0;
        waveNumber++;
        invaderDropPhase = false;
        dropStartTime = Date.now();
        spawnNewWave();
      }
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
        invader.x += (targetX - invader.x) * 0.05; // Much faster movement (was 0.01)
      }
      if (Math.abs(invader.y - targetY) > 1) {
        invader.y += (targetY - invader.y) * 0.05; // Much faster movement (was 0.01)
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
        let waveSpeedMultiplier = 2 + (waveNumber - 1) * 2.5; // Base scaling
        
        // 🚀 NEW: EXTREME difficulty scaling beyond wave 50!
        if (waveNumber >= 50) {
          waveSpeedMultiplier *= 1.5; // 50% faster for boss waves
        }
        if (waveNumber >= 75) {
          waveSpeedMultiplier *= 1.8; // 80% faster for ultra waves
        }
        if (waveNumber >= 100) {
          waveSpeedMultiplier *= 2.2; // 120% faster for legendary waves
        }
        if (waveNumber >= 150) {
          waveSpeedMultiplier *= 3.0; // 200% faster for mythical waves
        }
        if (waveNumber >= 200) {
          waveSpeedMultiplier *= 4.0; // 300% faster for god-tier waves
        }
        
        // 🚀 NEW: EXTREME difficulty scaling beyond wave 50!
        
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
          invader.x += Math.sin(currentTime * 0.1 + invader.x * 0.02) * 4 * waveSpeedMultiplier; // More aggressive side movement
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
    
      // 🔥 PHOENIX INVADERS: Check if this should be a Phoenix wave
  if (waveNumber % phoenixWaveConfig.waveFrequency === 0) {
    console.log(`🔥 Wave ${waveNumber}: PHOENIX INVADERS WAVE!`);
    
    // 🔥 CLEAR SCREEN: Remove all other invaders during Phoenix waves
    invaders = [];
    invaderBullets = [];
    tetrisDangerItems = [];
    console.log('🔥 Screen cleared of regular invaders for Phoenix wave!');
    
    spawnPhoenixWave();
    return;
  }
    
    // 🚀 NEW: Choose formation pattern based on wave difficulty
    let patterns = [
      'v_formation',    // V-shaped formation
      'pyramid',        // Pyramid formation  
      'diamond',        // Diamond formation
      'cross',          // Cross formation
      'spiral',         // Spiral formation
      'random_cluster', // Random cluster
      'ultra_swarm',    // Ultra dense swarm
      'double_formation' // Double formation
    ];
    
    // 🚀 NEW: Add ultra-difficult patterns for higher waves
    if (waveNumber >= 50) {
      patterns.push('chaos_storm');      // Chaos storm - random movement
      patterns.push('death_spiral');     // Death spiral - aggressive spiral
      patterns.push('wall_crusher');     // Wall crusher - destroys everything
    }
    if (waveNumber >= 75) {
      patterns.push('void_walker');      // Void walker - teleports around
      patterns.push('time_bomb');        // Time bomb - explodes after time
      patterns.push('shadow_clone');     // Shadow clone - duplicates invaders
    }
    if (waveNumber >= 100) {
      patterns.push('reality_breaker');  // Reality breaker - glitch effects
      patterns.push('dimension_shift');  // Dimension shift - phase through walls
      patterns.push('eternal_swarm');    // Eternal swarm - infinite invaders
    }
    
    const pattern = patterns[Math.floor(Math.random() * patterns.length)];
    console.log(`🎯 Wave ${waveNumber}: Using formation pattern: ${pattern}`);
    createFormation(pattern);
  }

  // 🔥 PHOENIX INVADERS: Spawn Phoenix wave with formation flying
  function spawnPhoenixWave() {
    isPhoenixWave = true;
    
    // 🔥 PHOENIX WAVE ANNOUNCEMENT - NEW!
    showNotification('🔥 PHOENIX INVADERS WAVE! 🔥', 'phoenix');
    
    // 🎯 PROFESSIONAL DIFFICULTY SCALING: Smooth progression to wave 1000+
    const baseCount = phoenixWaveConfig.basePhoenixCount;
    
    // 🔥 BALANCED: Progressive difficulty scaling for professional game balance
    let difficultyMultiplier = 1.0;
    let phoenixHealth = phoenixWaveConfig.phoenixHealth;
    let miniHealth = phoenixWaveConfig.miniPhoenixHealth;
    
    // 🎯 EARLY GAME (Waves 1-25): Gentle learning curve
    if (waveNumber >= 8) difficultyMultiplier = 1.1;   // Wave 8+
    if (waveNumber >= 13) difficultyMultiplier = 1.2;  // Wave 13+
    if (waveNumber >= 18) difficultyMultiplier = 1.3;  // Wave 18+
    if (waveNumber >= 23) difficultyMultiplier = 1.4;  // Wave 23+
    
    // 🎯 MID GAME (Waves 25-100): Steady challenge increase
    if (waveNumber >= 28) difficultyMultiplier = 1.5;  // Wave 28+
    if (waveNumber >= 33) difficultyMultiplier = 1.6;  // Wave 33+
    if (waveNumber >= 38) difficultyMultiplier = 1.7;  // Wave 38+
    if (waveNumber >= 43) difficultyMultiplier = 1.8;  // Wave 43+
    if (waveNumber >= 48) difficultyMultiplier = 1.9;  // Wave 48+
    if (waveNumber >= 53) difficultyMultiplier = 2.0;  // Wave 53+
    if (waveNumber >= 58) difficultyMultiplier = 2.1;  // Wave 58+
    if (waveNumber >= 63) difficultyMultiplier = 2.2;  // Wave 63+
    if (waveNumber >= 68) difficultyMultiplier = 2.3;  // Wave 68+
    if (waveNumber >= 73) difficultyMultiplier = 2.4;  // Wave 73+
    if (waveNumber >= 78) difficultyMultiplier = 2.5;  // Wave 78+
    if (waveNumber >= 83) difficultyMultiplier = 2.6;  // Wave 83+
    if (waveNumber >= 88) difficultyMultiplier = 2.7;  // Wave 88+
    if (waveNumber >= 93) difficultyMultiplier = 2.8;  // Wave 93+
    if (waveNumber >= 98) difficultyMultiplier = 2.9;  // Wave 98+
    
    // 🎯 LATE GAME (Waves 100-500): Expert challenge
    if (waveNumber >= 103) difficultyMultiplier = 3.0;  // Wave 103+
    if (waveNumber >= 108) difficultyMultiplier = 3.2;  // Wave 108+
    if (waveNumber >= 113) difficultyMultiplier = 3.4;  // Wave 113+
    if (waveNumber >= 118) difficultyMultiplier = 3.6;  // Wave 118+
    if (waveNumber >= 123) difficultyMultiplier = 3.8;  // Wave 123+
    if (waveNumber >= 128) difficultyMultiplier = 4.0;  // Wave 128+
    if (waveNumber >= 133) difficultyMultiplier = 4.2;  // Wave 133+
    if (waveNumber >= 138) difficultyMultiplier = 4.4;  // Wave 138+
    if (waveNumber >= 143) difficultyMultiplier = 4.6;  // Wave 143+
    if (waveNumber >= 148) difficultyMultiplier = 4.8;  // Wave 148+
    if (waveNumber >= 153) difficultyMultiplier = 5.0;  // Wave 153+
    if (waveNumber >= 158) difficultyMultiplier = 5.2;  // Wave 158+
    if (waveNumber >= 163) difficultyMultiplier = 5.4;  // Wave 163+
    if (waveNumber >= 168) difficultyMultiplier = 5.6;  // Wave 168+
    if (waveNumber >= 173) difficultyMultiplier = 5.8;  // Wave 173+
    if (waveNumber >= 178) difficultyMultiplier = 6.0;  // Wave 178+
    if (waveNumber >= 183) difficultyMultiplier = 6.2;  // Wave 183+
    if (waveNumber >= 188) difficultyMultiplier = 6.4;  // Wave 188+
    if (waveNumber >= 193) difficultyMultiplier = 6.6;  // Wave 193+
    if (waveNumber >= 198) difficultyMultiplier = 6.8;  // Wave 198+
    
    // 🎯 ENDGAME (Waves 200-1000+): Legendary challenge
    if (waveNumber >= 203) difficultyMultiplier = 7.0;  // Wave 203+
    if (waveNumber >= 250) difficultyMultiplier = 8.0;  // Wave 250+
    if (waveNumber >= 300) difficultyMultiplier = 9.0;  // Wave 300+
    if (waveNumber >= 400) difficultyMultiplier = 10.0; // Wave 400+
    if (waveNumber >= 500) difficultyMultiplier = 12.0; // Wave 500+
    if (waveNumber >= 600) difficultyMultiplier = 14.0; // Wave 600+
    if (waveNumber >= 700) difficultyMultiplier = 16.0; // Wave 700+
    if (waveNumber >= 800) difficultyMultiplier = 18.0; // Wave 800+
    if (waveNumber >= 900) difficultyMultiplier = 20.0; // Wave 900+
    if (waveNumber >= 1000) difficultyMultiplier = 25.0; // Wave 1000+ (Legendary)
    
    // 🔥 HEALTH SCALING: Phoenix health increases with difficulty
    phoenixHealth = Math.floor(phoenixWaveConfig.phoenixHealth * (1 + (difficultyMultiplier - 1) * 0.3));
    miniHealth = Math.floor(phoenixWaveConfig.miniPhoenixHealth * (1 + (difficultyMultiplier - 1) * 0.2));
    
    const phoenixCount = Math.min(
      Math.floor(baseCount * difficultyMultiplier),
      phoenixWaveConfig.maxPhoenixPerWave
    );
    
    console.log(`🔥 Spawning Phoenix wave: ${phoenixCount} Phoenix birds`);
    
    // 🔥 WAVE 3 BALANCING: Special adjustments for first Phoenix wave
    if (waveNumber === 3) {
      console.log('🔥 WAVE 3 BALANCING: Applying beginner-friendly Phoenix settings');
      // Force simpler formation for first wave
      formationPattern = 'v';
      // Reduce egg laying rate even more for first wave
      phoenixWaveConfig.eggLayingRate = 0.1; // 10% chance for Wave 3
    }
    
    // Clear existing Phoenix entities
    phoenixWaves = [];
    phoenixEggs = [];
    miniPhoenixes = [];
    
    // Choose formation pattern for this wave
    const availablePatterns = phoenixWaveConfig.formationPatterns;
    let formationPattern = availablePatterns[Math.floor(Math.random() * availablePatterns.length)];
    
    // 🔥 WAVE 3 BALANCING: Force V-formation for first Phoenix wave
    if (waveNumber === 3) {
      formationPattern = 'v';
      console.log('🔥 WAVE 3: Forcing V-formation for beginner-friendly experience');
    }
    
          // Spawn Phoenix birds in formation
      for (let i = 0; i < phoenixCount; i++) {
        const individualDifficulty = 1 + (i * 0.05); // 🔥 BALANCED: Gentle individual scaling
        
        // Position Phoenix birds in formation
        let x, y;
      
      // 🔥 CRITICAL FIX: Get canvas dimensions safely
      const canvas = document.getElementById('space-invaders-canvas');
      const currentCanvasWidth = canvas ? canvas.width : 400; // Fallback to 400
      const currentCanvasHeight = canvas ? canvas.height : 600; // Fallback to 600
      
      console.log(`🔥 Canvas dimensions: ${currentCanvasWidth}x${currentCanvasHeight}`);
      
      switch (formationPattern) {
        case 'v':
          // V-formation
          const vAngle = Math.PI / 4; // 45 degrees
          const vRadius = 150;
          const vOffset = (i - phoenixCount / 2) * 40;
          x = currentCanvasWidth / 2 + Math.cos(vAngle) * vRadius + vOffset;
          y = 100 + Math.sin(vAngle) * vRadius;
          break;
          
        case 'diamond':
          // Diamond formation
          const diamondSize = 120;
          const diamondAngle = (i / phoenixCount) * Math.PI * 2;
          x = currentCanvasWidth / 2 + Math.cos(diamondAngle) * diamondSize;
          y = 120 + Math.sin(diamondAngle) * diamondSize;
          break;
          
        case 'spiral':
          // Spiral formation
          const spiralRadius = 80 + (i * 10);
          const spiralAngle = (i / phoenixCount) * Math.PI * 4;
          x = currentCanvasWidth / 2 + Math.cos(spiralAngle) * spiralRadius;
          y = 100 + Math.sin(spiralAngle) * spiralRadius;
          break;
          
        case 'cluster':
          // Random cluster formation
          x = currentCanvasWidth / 2 + (Math.random() - 0.5) * 200;
          y = 100 + (Math.random() - 0.5) * 100;
          break;
          
        case 'dive':
          // Dive formation - spread across top
          x = (currentCanvasWidth / phoenixCount) * i + 50;
          y = 80;
          break;
          
        default:
          // Default V-formation
          const defaultAngle = Math.PI / 4;
          const defaultRadius = 150;
          const defaultOffset = (i - phoenixCount / 2) * 40;
          x = currentCanvasWidth / 2 + Math.cos(defaultAngle) * defaultRadius + defaultOffset;
          y = 100 + Math.sin(defaultAngle) * defaultRadius;
      }
      
      // 🔥 CRITICAL FIX: Validate coordinates
      if (isNaN(x) || isNaN(y)) {
        console.error(`🔥 INVALID COORDINATES for Phoenix ${i}: x=${x}, y=${y}`);
        x = 200; // Fallback position
        y = 100;
      }
      
      console.log(`🔥 Phoenix ${i} positioned at: x=${Math.round(x)}, y=${Math.round(y)}`);
      
      // Create Phoenix bird with calculated difficulty and scaled health
      const phoenix = new PhoenixBird(x, y, formationPattern, individualDifficulty);
      phoenix.health = Math.floor(phoenixHealth * individualDifficulty);
      phoenix.maxHealth = phoenix.health;
      phoenix.damage = Math.max(1, Math.floor(difficultyMultiplier * 0.5)); // Damage scales with difficulty
      
      phoenixWaves.push(phoenix);
    }
    
    console.log(`🔥 Phoenix wave spawned with ${formationPattern} formation`);
  }

  // 🔥 PHOENIX INVADERS: Update all Phoenix entities
  function updatePhoenixEntities() {
    // Update Phoenix birds
    phoenixWaves = phoenixWaves.filter(phoenix => phoenix.update());
    
    // Update Phoenix eggs
    phoenixEggs = phoenixEggs.filter(egg => egg.update());
    
    // Update mini-Phoenix enemies
    miniPhoenixes = miniPhoenixes.filter(mini => mini.update());
    
    // Check if Phoenix wave is complete
    if (phoenixWaves.length === 0 && phoenixEggs.length === 0 && miniPhoenixes.length === 0) {
      console.log('🔥 Phoenix wave completed! Returning to regular invaders...');
      isPhoenixWave = false;
      
      // Check if next wave should be a boss wave
      const nextWave = waveNumber + 1;
      if (nextWave === 10 || nextWave === 25 || nextWave === 75 || nextWave === 100) {
        console.log(`🏆 BOSS WAVE! Wave ${nextWave} will be an epic boss fight!`);
        gamePhase = 'boss';
        phaseTimer = 0;
        waveNumber++;
        spawnBoss();
      } else {
        gamePhase = 'formation';
        phaseTimer = 0;
        waveNumber++;
        invaderDropPhase = false;
        dropStartTime = Date.now();
        spawnNewWave();
      }
    }
  }

  // 🔥 PHOENIX INVADERS: Draw all Phoenix entities
  function drawPhoenixEntities() {
    // Draw Phoenix birds
    phoenixWaves.forEach(phoenix => phoenix.draw(ctx));
    
    // Draw Phoenix eggs
    phoenixEggs.forEach(egg => egg.draw(ctx));
    
    // Draw mini-Phoenix enemies
    miniPhoenixes.forEach(mini => mini.draw(ctx));
    
    // Draw Phoenix wave indicator
    ctx.fillStyle = '#ff6b35';
    ctx.font = 'bold 24px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('🔥 PHOENIX INVADERS WAVE', canvasWidth / 2, 30);
    
    // Draw Phoenix count
    ctx.fillStyle = '#ff8c42';
    ctx.font = '16px Arial';
    ctx.fillText(`Phoenix: ${phoenixWaves.length} | Eggs: ${phoenixEggs.length} | Mini: ${miniPhoenixes.length}`, canvasWidth / 2, 55);
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
        // 🚀 NEW: Check if player is invincible
        if (playerShip.invincible && playerShip.invincibleTimer > 0) {
          console.log('🛡️ Player invincible - Tetris item blocked!');
          // Remove Tetris item without damaging player
          tetrisDangerItems.splice(index, 1);
          return; // Don't take damage
        }
        
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
  function createExplosion(x, y, size = 25, timer = 15) {
    explosions.push({
      x: x,
      y: y,
      size: size,
      timer: timer
    });
  }

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
    
    // 🚀 NEW: Random invader type selection (original vs new variant)
    const invaderType = Math.random() < 0.7 ? 'original' : 'variant'; // 70% original, 30% variant
    
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
      weakPointSize: 6 + Math.floor(waveNumber / 4), // Bigger weak points in later waves
      invaderType: invaderType // 🚀 NEW: Track which invader type this is
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
      const waveOffset = Math.sin(currentTime * 0.1 + i * 0.1) * 2;
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
      
            // 🚀 FIXED: Check bullet collisions with regular invaders
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
            spaceInvadersScore += invader.points; // Keep game points for display
            spaceInvadersCount += 1; // NEW: Track invader count for DSPOINC
            
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

      // 🚀 FIXED: Check bullet collisions with Phoenix entities (moved outside Tetris check)
      if (isPhoenixWave) {
        // Check Phoenix bird collisions
        phoenixWaves.forEach(phoenix => {
          if (!phoenix.isDead && checkCollision(bullet, phoenix)) {
            let phoenixDamage = 1;
            if (bullet.type === 'laser') phoenixDamage = 2;
            if (bullet.type === 'bomb') phoenixDamage = 5;
            
            phoenix.takeDamage(phoenixDamage);
            bulletHit = true;
            
            // Create Phoenix hit effect
            explosions.push({
              x: phoenix.x,
              y: phoenix.y,
              size: 20,
              timer: 15,
              isPhoenixHit: true
            });
          }
        });
        
        // Check Phoenix egg collisions
        phoenixEggs.forEach(egg => {
          if (!egg.isDestroyed && checkCollision(bullet, egg)) {
            console.log('🥚 Phoenix egg hit! Position:', egg.x, egg.y);
            egg.takeDamage(1);
            bulletHit = true;
            
            // Create egg destruction effect
            explosions.push({
              x: egg.x,
              y: egg.y,
              size: 15,
              timer: 10,
              isEggDestroyed: true
            });
          }
        });
        
        // Check mini-Phoenix collisions
        miniPhoenixes.forEach(mini => {
          if (!mini.isDead && checkCollision(bullet, mini)) {
            console.log('🎯 Mini-Phoenix hit! Position:', mini.x, mini.y, 'Health:', mini.health);
            let miniDamage = 1;
            if (bullet.type === 'laser') miniDamage = 2;
            if (bullet.type === 'bomb') miniDamage = 5;
            
            mini.takeDamage(miniDamage);
            bulletHit = true;
            
            // Create mini-Phoenix hit effect
            explosions.push({
              x: mini.x,
              y: mini.y,
              size: 15,
              timer: 12,
              isMiniPhoenixHit: true
            });
          }
        });
      }
      
      // 🚀 FIXED: Check bullet collisions with Tetris blocks (separate from Phoenix check)
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
    // 🧪 REDUCED DEBUG: Log less frequently during boss phase
    if (gamePhase === 'boss' && Date.now() % 3000 < 16) { // Every 3 seconds
      console.log(`🧪 checkPlayerHit() CALLED IN BOSS PHASE - Boss exists: ${!!boss}, Boss bullets: ${bossBullets?.length || 0}`);
    }
    
    // 🧪 DEBUG: Confirm function is being called
    if (Date.now() % 5000 < 16) { // Log every 5 seconds
      console.log(`🧪 checkPlayerHit() called - Boss exists: ${!!boss}, Boss bullets: ${bossBullets?.length || 0}, Invader bullets: ${invaderBullets?.length || 0}`);
    }
    
    // Check invader bullets
    invaderBullets.forEach((bullet, index) => {
      if (checkCollision(playerShip, bullet)) {
        // 🚀 NEW: Check if player is invincible
        if (playerShip.invincible && playerShip.invincibleTimer > 0) {
          console.log('🛡️ Player invincible - bullet blocked!');
          invaderBullets.splice(index, 1);
          return; // Don't take damage
        }
        
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
    
    // 🚀 NEW: Check boss bullets for player damage
    // 🧪 REDUCED DEBUG: Check conditions less frequently
    if (gamePhase === 'boss' && Date.now() % 2000 < 16) { // Every 2 seconds
      console.log(`🧪 BOSS BULLET CONDITIONS: boss=${!!boss}, bossBullets=${!!bossBullets}, length=${bossBullets?.length || 0}, typeof bossBullets=${typeof bossBullets}`);
    }
    
    if (boss && bossBullets && bossBullets.length > 0) {
      // 🧪 ENHANCED DEBUG: Log detailed boss bullet check
      if (Date.now() % 1000 < 16) { // Log every second
        console.log(`🧪 BOSS BULLET CHECK: ${bossBullets.length} bullets, Player at (${Math.round(playerShip.x)}, ${Math.round(playerShip.y)})`);
        console.log(`🧪 Boss position: (${Math.round(boss.x)}, ${Math.round(boss.y)}) size: ${boss.width}x${boss.height}`);
        if (bossBullets.length > 0) {
          const firstBullet = bossBullets[0];
          console.log(`🧪 First bullet at (${Math.round(firstBullet.x)}, ${Math.round(firstBullet.y)}) type: ${firstBullet.type || 'normal'}`);
          console.log(`🧪 First bullet properties: x=${firstBullet.x}, y=${firstBullet.y}, speed=${firstBullet.speed}, angle=${firstBullet.angle}`);
        }
      }
      
      bossBullets.forEach((bullet, index) => {
        // 🧪 DEBUG: Log every collision check attempt
        const collisionResult = checkCollision(playerShip, bullet);
        if (collisionResult) {
          console.log(`💥 BOSS BULLET COLLISION DETECTED: ${bullet.type || 'normal'} hit player!`);
          console.log(`🎯 Collision details: Bullet(${bullet.x}, ${bullet.y}, ${bullet.width}x${bullet.height}) vs Player(${playerShip.x}, ${playerShip.y}, ${playerShip.width}x${playerShip.height})`);
          // 🚀 NEW: Check if player is invincible
          if (playerShip.invincible && playerShip.invincibleTimer > 0) {
            console.log('🛡️ Player invincible - boss bullet blocked!');
            bossBullets.splice(index, 1);
            return; // Don't take damage
          }
          
          // 🧀 Enhanced damage system for different cheese bullet types
          let damage = bullet.damage || 1;
          const bulletType = bullet.type || 'normal';
          
          console.log(`💥 ${bulletType.toUpperCase()} hits player! Damage: ${damage}, Health: ${playerShip.health} -> ${playerShip.health - damage}`);
          
          // 🧀 Special effects based on bullet type
          if (bulletType === 'swiss_sniper' && bullet.piercing) {
            // Swiss sniper bullets don't get removed and create special effect
            console.log(`🧀 Swiss sniper pierces through player!`);
            screenShake = 20; // Extra screen shake for piercing
          } else {
            // Regular bullets get removed
            bossBullets.splice(index, 1);
          }
          
          playerShip.health -= damage;
          
          // 🧀 Create enhanced explosion effect based on cheese bullet type
          let explosionSize = 25;
          let explosionTimer = 15;
          
          // Different explosion effects for different cheese types
          switch (bulletType) {
            case 'cheese_cannon':
              explosionSize = 35;
              explosionTimer = 20;
              console.log(`🧀 CHEESE CANNON BLAST! The power of aged cheddar overwhelms you!`);
              break;
            case 'gouda_grenade':
              explosionSize = 40;
              explosionTimer = 25;
              console.log(`🧀 GOUDA GRENADE EXPLOSION! You're covered in molten cheese!`);
              break;
            case 'swiss_sniper':
              explosionSize = 30;
              explosionTimer = 18;
              console.log(`🧀 SWISS PRECISION STRIKE! Those holes aren't just for show!`);
              break;
            case 'cheddar_chaos':
              explosionSize = 32;
              explosionTimer = 20;
              console.log(`🧀 CHEDDAR CHAOS BURNS! The heat of aged cheddar sears you!`);
              break;
            case 'melted_cheese':
              explosionSize = 28;
              explosionTimer = 22;
              console.log(`🧀 MELTED CHEESE SPLASH! You're dripping with dairy destruction!`);
              break;
            default:
              console.log(`🧀 Cheese attack hits! The dairy devastation continues!`);
          }
          
          explosions.push({
            x: playerShip.x + playerShip.width / 2,
            y: playerShip.y + playerShip.height / 2,
            size: explosionSize,
            timer: explosionTimer,
            isBossHit: true,
            cheeseType: bulletType
          });
          
          // 🚀 NEW: Screen shake on boss bullet hit
          screenShake = 12;
          
          // 🚀 NEW: Boss bullet hit sound effect
          if (window.cheeseSoundManager && window.cheeseSoundManager.soundEnabled) {
            window.cheeseSoundManager.playStarWarsLaser();
          }
          
          if (playerShip.health <= 0) {
            onGameOver();
          }
        }
      });
    }
  }

  // 🚀 NEW: Check for collisions between invaders and player ship
  function checkInvaderPlayerCollisions() {
    invaders.forEach((invader, index) => {
      if (!invader.alive) return;
      
      // Check if invader collides with player ship
      if (checkCollision(invader, playerShip)) {
        // 🚀 NEW: Check if player is invincible
        if (playerShip.invincible && playerShip.invincibleTimer > 0) {
          console.log('🛡️ Player invincible - invader collision blocked!');
          // Kill the invading invader without damaging player
          invader.alive = false;
          spaceInvadersScore += invader.points; // Keep game points for display
          spaceInvadersCount += 1; // NEW: Track invader count for DSPOINC
          
          // Create explosion effect at collision point
          explosions.push({
            x: invader.x + invader.width / 2,
            y: invader.y + invader.height / 2,
            size: 30,
            timer: 15,
            isInvaderCollision: true
          });
          
          return; // Don't take damage
        }
        
        console.log(`💥 Invader collision with player! Player health: ${playerShip.health} -> ${playerShip.health - 1}`);
        
        // Damage player (invader collision is deadly!)
        playerShip.health--;
        
        // Kill the invading invader
        invader.alive = false;
        spaceInvadersScore += invader.points; // Keep game points for display
        spaceInvadersCount += 1; // NEW: Track invader count for DSPOINC
        
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
    
    // 🚀 NEW: Apply screen shake effect
    if (screenShake > 0) {
      const shakeX = (Math.random() - 0.5) * screenShake;
      const shakeY = (Math.random() - 0.5) * screenShake;
      ctx.save();
      ctx.translate(shakeX, shakeY);
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
    
    // 🔥 PHOENIX INVADERS: Draw Phoenix entities if in Phoenix wave
    if (isPhoenixWave) {
      drawPhoenixEntities();
    }
    
    // 🚀 NEW: Draw boss if in boss phase
    if (gamePhase === 'boss' && boss && !bossDefeated) {
      drawBossEffects(); // Draw glow and particles first
      drawBoss();
      drawBossBullets();
    }
    
    drawScore();
    drawHealth();
    drawPhaseInfo(); // NEW: Show current phase info
    
    // 🚀 NEW: Draw boss wave announcement
    if (gamePhase === 'boss' && boss && !bossDefeated) {
      drawBossAnnouncement();
    }
    
    // 🚀 NEW: Restore screen shake
    if (screenShake > 0) {
      ctx.restore();
    }
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
    // 🚀 NEW: Add invincibility glow effect
    if (playerShip.invincible && playerShip.invincibleTimer > 0) {
      // Create pulsing invincibility glow
      const glowIntensity = 0.3 + Math.sin(Date.now() * 0.1) * 0.2; // Pulsing effect
      ctx.fillStyle = `rgba(255, 255, 0, ${glowIntensity})`; // Yellow glow
      ctx.fillRect(playerShip.x - 4, playerShip.y - 4, playerShip.width + 8, playerShip.height + 8);
    }
    
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
    
    // 🚀 NEW: Add invincibility indicator text
    if (playerShip.invincible && playerShip.invincibleTimer > 0) {
      ctx.fillStyle = '#ffff00';
      ctx.font = '12px Arial';
      ctx.fillText('🛡️ INVINCIBLE', playerShip.x, playerShip.y - 10);
    }
    
    // 🔥 REMOVED: Duplicate heat warnings (now using unified heat display only)
    // This eliminates the duplicate "HIGH HEAT" and "OVERHEATED!" text that was causing confusion
    // The unified heat display in the top-right corner now handles all heat warnings consistently
  }

  function drawInvaders() {
    invaders.forEach(invader => {
      if (!invader.alive) return;
      
      // 🚀 NEW: Try to draw appropriate invader image based on type
      let invaderImg = null;
      if (invader.invaderType === 'variant' && cheeseInvader2Img.complete && cheeseInvader2Img.naturalWidth > 0) {
        // Use the new variant invader image
        invaderImg = cheeseInvader2Img;
      } else if (cheeseInvaderImg.complete && cheeseInvaderImg.naturalWidth > 0) {
        // Use the original invader image
        invaderImg = cheeseInvaderImg;
      }
      
      if (invaderImg) {
        ctx.drawImage(invaderImg, invader.x, invader.y, invader.width, invader.height);
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
          ctx.fillStyle = `rgba(255, 0, 0, ${0.8 + Math.sin(Date.now() * 0.1) * 0.2})`; // Pulsing red
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
          ctx.fillStyle = `rgba(0, 255, 0, ${0.8 + Math.sin(Date.now() * 0.1) * 0.2})`; // Pulsing green
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
      const waveOffset = Math.sin(currentTime * 0.1 + i * 0.1) * 2;
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
      // 🚀 CRITICAL FIX: Space Invaders scoring: Use SAME calculation as saveScore for consistency
      // Based on screenshot: 2185 invaders = 21.85 DSPOINC, so 100 invaders = 1 DSPOINC
      const dspoinEarned = Math.round((spaceInvadersScore * 0.01) * 100) / 100; // Round to 2 decimal places (100 invaders = 1 DSPOINC)
      
      // Add mouse control indicator
      const mouseIndicator = isMouseControlEnabled && isMouseOverCanvas ? '🖱️' : '⌨️';
      scoreDisplay.textContent = `💰 Space Invaders Score: ${spaceInvadersScore.toLocaleString()} invaders destroyed (${dspoinEarned} DSPOINC) ${mouseIndicator}`;
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
    
    // 🔥 PHOENIX INVADERS: Show Phoenix wave indicator
    if (isPhoenixWave) {
      phaseColor = '#ff6b35'; // Orange for Phoenix waves
      phaseText = `🔥W${waveNumber}`; // Phoenix wave indicator
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
    
    // 🎯 NEW: Show multi-shot upgrade status
    if (currentWeaponType === 'normal') {
      let upgradeText = '';
      let upgradeColor = '#ffffff';
      
      if (hasQuadShotUpgrade) {
        upgradeText = '🎯 QUAD SHOT';
        upgradeColor = '#ff00ff'; // Magenta for quad shot
      } else if (hasTripleShotUpgrade) {
        upgradeText = '🎯 TRIPLE SHOT';
        upgradeColor = '#ff8800'; // Orange for triple shot
      } else if (hasDoubleShotUpgrade) {
        upgradeText = '🎯 DOUBLE SHOT';
        upgradeColor = '#00ff00'; // Green for double shot
      }
      
      if (upgradeText) {
        ctx.fillStyle = upgradeColor;
        ctx.font = '12px Arial';
        const upgradeWidth = ctx.measureText(upgradeText).width;
        ctx.fillText(upgradeText, canvasWidth - upgradeWidth - 10, 70);
      }
    }
    
    // 🚀 NEW: Show special weapon ammo below if available
    if (currentWeaponType === 'laser' && weaponAmmo.laser > 0) {
      ctx.fillStyle = '#00ffff';
      ctx.fillText(`⚡${weaponAmmo.laser}`, 10, 70);
    } else if (currentWeaponType === 'bomb' && weaponAmmo.bomb > 0) {
      ctx.fillStyle = '#ff00ff';
      ctx.fillText(`💣${weaponAmmo.bomb}`, 10, 70);
    }
    
    // 🖱️ NEW: Show mouse control status
    if (isMouseOverCanvas && isMouseControlEnabled) {
      ctx.fillStyle = '#10b981';
      ctx.fillText('🖱️ MOUSE', canvasWidth - 80, 70);
      
      // 🚀 NEW: Show rapid fire status
      if (typeof window.isMouseButtonDown !== 'undefined' && window.isMouseButtonDown) {
        ctx.fillStyle = '#ff6b6b';
        ctx.fillText('🔥 RAPID FIRE', canvasWidth - 100, 85);
      }
      
      // 🔥 REMOVED: Canvas-based heat bar (now using unified HTML heat display only)
      // This eliminates the duplicate heat bar that was causing confusion
      // The unified heat display in the top-right corner now handles all heat information consistently
      
      // Debug: Show mouse position and target
      if (typeof window.mouseTargetX !== 'undefined' && typeof window.mouseTargetY !== 'undefined') {
        ctx.fillStyle = '#ffffff';
        ctx.font = '10px Arial';
        ctx.fillText(`Mouse: ${Math.round(mouseX)},${Math.round(mouseY)}`, 10, 90);
        ctx.fillText(`Target: ${Math.round(window.mouseTargetX)},${Math.round(window.mouseTargetY)}`, 10, 105);
        ctx.fillText(`Ship: ${Math.round(playerShip.x)},${Math.round(playerShip.y)}`, 10, 120);
      }
      
      // 🎯 NEW: Show all unlocked upgrades permanently
      let upgradeY = 140;
      ctx.font = '12px Arial';
      
      if (hasDoubleShotUpgrade) {
        ctx.fillStyle = '#00ff00';
        ctx.fillText('✅ DOUBLE SHOT UNLOCKED', 10, upgradeY);
        upgradeY += 15;
      }
      
      if (hasTripleShotUpgrade) {
        ctx.fillStyle = '#ff8800';
        ctx.fillText('✅ TRIPLE SHOT UNLOCKED', 10, upgradeY);
        upgradeY += 15;
      }
      
      if (hasQuadShotUpgrade) {
        ctx.fillStyle = '#ff00ff';
        ctx.fillText('✅ QUAD SHOT UNLOCKED', 10, upgradeY);
        upgradeY += 15;
      }
    }
  }



  function onGameOver() {
    clearInterval(spaceInvadersGameInterval);
    
    const gameOverModal = document.getElementById("space-invaders-over-modal");
    const finalScoreText = document.getElementById("space-invaders-final-score-text");
    
    // 🚀 CRITICAL FIX: Space Invaders scoring: Use SAME calculation as saveScore for consistency
    // Based on screenshot: 2185 invaders = 21.85 DSPOINC, so 100 invaders = 1 DSPOINC
    const dspoinEarned = Math.round((spaceInvadersScore * 0.01) * 100) / 100; // Round to 2 decimal places (100 invaders = 1 DSPOINC)
    
    if (gameOverModal && finalScoreText) {
      finalScoreText.textContent = `You earned ${dspoinEarned} DSPOINC! (${spaceInvadersScore.toLocaleString()} invaders destroyed)`;
      gameOverModal.classList.remove("hidden");
    }
    
    // 🔧 CRITICAL FIX: Score MUST be saved here for all players (not just winners)
    saveScore(spaceInvadersScore); // RESTORED: This is the main score saving point
    cleanupSpaceInvadersControls();
    
    // 🚀 NEW: Clean up ship cursor when game ends
    cleanupCustomCursor();

    // 🆘 NEW: Ensure mobile controls are visible when game ends
    setTimeout(() => {
      ensureMobileControlsVisible();
    }, 100);

    // Dispatch game end event for UI reset
    window.dispatchEvent(new Event('spaceInvadersGameEnd'));
  }

  function playerShoot() {
    if (isSpaceInvadersPaused) return;
    
    // 🔥 CRITICAL FIX: Check for overheating FIRST - before any shooting logic
    if (isOverheated) {
      console.log('🚫 Cannot shoot - weapon is overheated!');
      return; // Don't allow shooting when overheated
    }
    
    // 🔥 CRITICAL FIX: Unified firing rate check for ALL control methods
    const currentTime = Date.now();
    if (currentTime - lastUnifiedShotTime < unifiedFiringRate) {
      // Don't block shots completely - just add a small delay for heat balance
      // This prevents the "wishy" mouse movement while maintaining heat balance
      const remainingDelay = unifiedFiringRate - (currentTime - lastUnifiedShotTime);
      if (remainingDelay > 20) { // Only block if delay is significant (>20ms)
        console.log(`🚫 Shot delayed by unified firing rate - ${remainingDelay}ms remaining`);
        return; // Shot blocked by unified firing rate
      }
    }
    
    console.log(`🔫 playerShoot called with weapon: ${currentWeaponType}, isQuickShotCall: ${window.isQuickShotCall}`);
    
    // 🎵 NEW: Play Star Wars laser sound for all weapon types!
    cheeseSoundManager.playStarWarsLaser();
    
    // 🚀 NEW: Enhanced shooting system with weapon types
    switch (currentWeaponType) {
      case 'normal':
        // 🚀 NEW: Progressive Multi-Shot System
        if (hasQuadShotUpgrade) {
          // 🎯 QUAD SHOT: Fire four bullets in a spread pattern
          console.log('🎯 QUAD SHOT MODE: Firing 4 bullets!');
          bullets.push({
            x: playerShip.x + playerShip.width / 2 - 16, // Far left bullet
            y: playerShip.y,
            width: 8,
            height: 16,
            speed: 6,
            type: 'normal',
            damage: 1
          });
          bullets.push({
            x: playerShip.x + playerShip.width / 2 - 8, // Left bullet
            y: playerShip.y,
            width: 8,
            height: 16,
            speed: 6,
            type: 'normal',
            damage: 1
          });
          bullets.push({
            x: playerShip.x + playerShip.width / 2, // Center bullet
            y: playerShip.y,
            width: 8,
            height: 16,
            speed: 6,
            type: 'normal',
            damage: 1
          });
          bullets.push({
            x: playerShip.x + playerShip.width / 2 + 8, // Right bullet
            y: playerShip.y,
            width: 8,
            height: 16,
            speed: 6,
            type: 'normal',
            damage: 1
          });
        } else if (hasTripleShotUpgrade) {
          // 🎯 TRIPLE SHOT: Fire three bullets in a spread pattern
          console.log('🎯 TRIPLE SHOT MODE: Firing 3 bullets!');
          bullets.push({
            x: playerShip.x + playerShip.width / 2 - 12, // Left bullet
            y: playerShip.y,
            width: 8,
            height: 16,
            speed: 6,
            type: 'normal',
            damage: 1
          });
          bullets.push({
            x: playerShip.x + playerShip.width / 2, // Center bullet
            y: playerShip.y,
            width: 8,
            height: 16,
            speed: 6,
            type: 'normal',
            damage: 1
          });
          bullets.push({
            x: playerShip.x + playerShip.width / 2 + 12, // Right bullet
            y: playerShip.y,
            width: 8,
            height: 16,
            speed: 6,
            type: 'normal',
            damage: 1
          });
        } else if (hasDoubleShotUpgrade) {
          // 🎯 DOUBLE SHOT: Fire two bullets side by side
          console.log('🎯 DOUBLE SHOT MODE: Firing 2 bullets!');
          bullets.push({
            x: playerShip.x + playerShip.width / 2 - 12, // Left bullet
            y: playerShip.y,
            width: 8,
            height: 16,
            speed: 6,
            type: 'normal',
            damage: 1
          });
          bullets.push({
            x: playerShip.x + playerShip.width / 2 + 4, // Right bullet
            y: playerShip.y,
            width: 8,
            height: 16,
            speed: 6,
            type: 'normal',
            damage: 1
          });
        } else {
          // Normal single bullet
          bullets.push({
            x: playerShip.x + playerShip.width / 2 - 4,
            y: playerShip.y,
            width: 8,
            height: 16,
            speed: 6,
            type: 'normal',
            damage: 1
          });
        }
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
            // 🔧 FIXED: Allow laser to fire with mouse/touch when ammo is available
            console.log('🔫 LASER FIRED! (Mouse/Touch trigger)');
            
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
              spaceInvadersScore += invader.points; // Keep game points for display
              spaceInvadersCount += invadersKilled; // NEW: Track invader count for DSPOINC
              
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
            // 🔧 FIXED: Allow bomb to fire with mouse/touch when ammo is available
            console.log('💣 BOMB FIRED! (Mouse/Touch trigger)');
            
            // Create bomb explosion effect
            createBombExplosion();
            
            // Kill all invaders on screen
            let invadersKilled = 0;
            invaders.forEach(invader => {
              if (invader.alive) {
                invader.alive = false;
                invadersKilled++;
                spaceInvadersScore += invader.points; // Keep game points for display
                spaceInvadersCount += invadersKilled; // NEW: Track invader count for DSPOINC
                
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
          }
        } else {
          console.log('🚫 Bomb ammo or cooldown not ready!');
          return; // Don't shoot
        }
        break;
    }
    
    // 🔥 CRITICAL FIX: Add heat for ALL shots regardless of control method
    // This ensures mobile, keyboard, and mouse all use the same heat system
    const heatToAdd = getHeatPerShot();
    addHeat(heatToAdd);
    console.log(`🔥 Heat added: +${heatToAdd} (Total: ${weaponHeat}/${maxHeat})`);
    
    // Check if this shot caused overheating
    if (isOverheated) {
      console.log('🔥 WEAPON OVERHEATED from playerShoot!');
      // Stop any ongoing rapid fire
      if (mouseRapidFireInterval) {
        clearInterval(mouseRapidFireInterval);
        mouseRapidFireInterval = null;
      }
      isMouseButtonDown = false;
      window.isMouseButtonDown = false;
    }
    
    // Update last shoot time for cooldown management
    lastPlayerShootTime = Date.now();
    
    // 🔥 CRITICAL FIX: Update unified firing rate timer
    lastUnifiedShotTime = currentTime;
    console.log(`✅ Shot fired successfully - next shot available in ${unifiedFiringRate}ms`);
  }

  // 🔥 CRITICAL FIX: Auto-shoot function that doesn't add heat
  function autoShoot() {
    if (isSpaceInvadersPaused) return;
    
    // 🚀 FIX: Check cooldown to prevent rapid firing
    const currentTime = Date.now();
    if (currentTime - lastAutoShootTime < autoShootCooldown) {
      return; // Still in cooldown
    }
    
    console.log('🚀 Auto-shoot fired! (no heat added)');
    
    // 🎵 Play sound for auto-shoot
    cheeseSoundManager.playStarWarsLaser();
    
    // Create bullet based on weapon type
    switch (currentWeaponType) {
      case 'normal':
        // Create normal bullet
        bullets.push({
          x: playerShip.x + playerShip.width / 2 - 2,
          y: playerShip.y,
          width: 4,
          height: 10,
          speed: 8,
          type: 'normal'
        });
        break;
        
      case 'laser':
        // Create laser beam
        if (weaponAmmo.laser > 0) {
          bullets.push({
            x: playerShip.x + playerShip.width / 2 - 3,
            y: playerShip.y,
            width: 6,
            height: 20,
            speed: 12,
            type: 'laser'
          });
          weaponAmmo.laser--;
          updateWeaponDisplay();
        }
        break;
        
      case 'bomb':
        // Create bomb
        if (weaponAmmo.bomb > 0) {
          bullets.push({
            x: playerShip.x + playerShip.width / 2 - 4,
            y: playerShip.y,
            width: 8,
            height: 8,
            speed: 6,
            type: 'bomb'
          });
          weaponAmmo.bomb--;
          updateWeaponDisplay();
        }
        break;
    }
    
    // Update auto-shoot timer
    lastAutoShootTime = Date.now();
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
    // Note: screenShake function not implemented yet
    
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
    // Note: screenShake function not implemented yet
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
        // Allow ship to go down further - extended bottom boundary for better gameplay
        const extendedBottomBoundary = canvasHeight + 20; // Allow 20px beyond canvas bottom
        playerShip.y = Math.min(extendedBottomBoundary, playerShip.y + moveAmount);
        break;
    }
    
    // 🔥 CRITICAL FIX: Auto-shoot when ship moves (if enabled) - NO HEAT BUILDUP
    if (autoShootEnabled && (oldX !== playerShip.x || oldY !== playerShip.y)) {
      const currentTime = Date.now();
      
      // Check if enough time has passed since last auto-shoot
      if (currentTime - lastAutoShootTime >= autoShootFiringRate) {
        // 🔥 FIX: Better movement detection to prevent blinking
        const movementThreshold = 2; // Minimum movement to trigger auto-shoot
        const deltaX = Math.abs(playerShip.x - oldX);
        const deltaY = Math.abs(playerShip.y - oldY);
        
        if (deltaX > movementThreshold || deltaY > movementThreshold) {
          // 🔥 FIX: Use more precise position tracking
          const currentPos = { 
            x: Math.round(playerShip.x / 5), // Smaller grid for more responsive shooting
            y: Math.round(playerShip.y / 5) 
          };
          
          if (currentPos.x !== lastMovementPosition.x || currentPos.y !== lastMovementPosition.y) {
            autoShoot(); // Use special auto-shoot function that doesn't add heat
            lastMovementPosition = currentPos; // Update last position
            console.log('🚀 Auto-shoot triggered by movement:', deltaX.toFixed(1), deltaY.toFixed(1));
          }
        }
      }
    }
  }

  // 🖱️ GLOBAL MOUSE TRACKING: Handle mouse movement for ship positioning (works everywhere!)
  function updateMouseMovement() {
    // 🚀 CRITICAL FIX: Disable mouse controls when mobile touch is active OR on mobile devices
    if (!isMouseControlEnabled || isSpaceInvadersPaused || isTouching || isMobileDevice) {
      return;
    }
    
    // 🚀 NEW: Global mouse tracking - ship follows mouse even outside container!
    let targetX, targetY;
    
    if (isMouseOverCanvas && typeof mouseTargetX !== 'undefined' && typeof mouseTargetY !== 'undefined') {
      // Mouse is over canvas - use canvas-relative coordinates
      targetX = mouseTargetX;
      targetY = mouseTargetY;
    } else if (typeof window.mouseX !== 'undefined' && typeof window.mouseY !== 'undefined') {
      // Mouse is outside canvas - convert global coordinates to canvas-relative
      const canvas = document.getElementById('space-invaders-canvas');
      if (canvas) {
        const rect = canvas.getBoundingClientRect();
        const globalX = window.mouseX - rect.left;
        const globalY = window.mouseY - rect.top;
        
        // Convert to ship-relative coordinates
        targetX = globalX - playerShip.width / 2;
        targetY = globalY - playerShip.height / 2;
      } else {
        return; // Canvas not available
      }
    } else {
      return; // No mouse position available
    }
    
    if (isNaN(targetX) || isNaN(targetY)) {
      return; // Invalid coordinates
    }
    
    const oldX = playerShip.x;
    const oldY = playerShip.y;
    
    // 🚀 ENHANCED: Direct movement with smooth easing
    const easing = 0.4; // Responsive but smooth movement
    
    // Move ship directly toward target
    playerShip.x += (targetX - playerShip.x) * easing;
    playerShip.y += (targetY - playerShip.y) * easing;
    
    // 🚀 ENHANCED: Apply boundary constraints to ship movement with extended bottom range
    playerShip.x = Math.max(0, Math.min(canvasWidth - playerShip.width, playerShip.x));
    // Allow ship to go down further - extended bottom boundary for better gameplay
    const extendedBottomBoundary = canvasHeight + 20; // Allow 20px beyond canvas bottom
    playerShip.y = Math.max(0, Math.min(extendedBottomBoundary, playerShip.y));
    
    // 🔥 CRITICAL FIX: Auto-shoot when ship moves (if enabled) - NO HEAT BUILDUP
    if (autoShootEnabled && (oldX !== playerShip.x || oldY !== playerShip.y)) {
      const currentTime = Date.now();
      
      // Check if enough time has passed since last auto-shoot
      if (currentTime - lastAutoShootTime >= autoShootFiringRate) {
        // 🔥 FIX: Better movement detection to prevent blinking
        const movementThreshold = 2; // Minimum movement to trigger auto-shoot
        const deltaX = Math.abs(playerShip.x - oldX);
        const deltaY = Math.abs(playerShip.y - oldY);
        
        if (deltaX > movementThreshold || deltaY > movementThreshold) {
          // 🔥 FIX: Use more precise position tracking
          const currentPos = { 
            x: Math.round(playerShip.x / 5), // Smaller grid for more responsive shooting
            y: Math.round(playerShip.y / 5) 
          };
          
          if (currentPos.x !== lastMovementPosition.x || currentPos.y !== lastMovementPosition.y) {
            autoShoot(); // Use special auto-shoot function that doesn't add heat
            lastMovementPosition = currentPos; // Update last position
            console.log('🚀 Auto-shoot triggered by movement:', deltaX.toFixed(1), deltaY.toFixed(1));
          }
        }
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
    
    // 🎵 NEW: Handle sound controls
    if (e.key === 'm' || e.key === 'M') {
      // Toggle sound on/off
      cheeseSoundManager.toggleSound();
      console.log(`🔊 Sound ${cheeseSoundManager.soundEnabled ? 'enabled' : 'disabled'}`);
      return;
    }
    
    if (e.key === 'v' || e.key === 'V') {
      // Cycle through volume levels
      const volumes = [0.3, 0.5, 0.7, 1.0];
      const currentIndex = volumes.indexOf(cheeseSoundManager.masterVolume);
      const nextIndex = (currentIndex + 1) % volumes.length;
      const newVolume = volumes[nextIndex];
      
      cheeseSoundManager.setVolume(newVolume);
      console.log(`🔊 Volume set to: ${Math.round(newVolume * 100)}%`);
      
      // Play test sound at new volume
      cheeseSoundManager.playStarWarsLaser();
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
        // 🔥 CRITICAL FIX: Enhanced spacebar shooting support
        if (!isOverheated) {
          // For special weapons, set the flag to allow shooting
          if (currentWeaponType === 'laser' || currentWeaponType === 'bomb') {
            window.isQuickShotCall = true;
            console.log(`🚀 Spacebar: Setting isQuickShotCall = true for ${currentWeaponType}`);
          }
          console.log('🚀 Spacebar: Manual shot fired!');
          playerShoot();
        } else {
          console.log('🔥 Spacebar: Cannot shoot - weapon overheated!');
        }
        break;
    }
  });

  // 🖱️ NEW: Mouse control variables (GLOBAL SCOPE for proper access)
  let isMouseControlEnabled = true;
  let mouseX = 0;
  let mouseY = 0;
  let isMouseOverCanvas = false;
  let mouseTargetX = 0;
  let mouseTargetY = 0;
  
  // 🔧 CRITICAL FIX: Initialize mouse targets to prevent 300px jumps
  // These will be properly set when the game starts and playerShip is available
  
  // 🔥 OVERHEAT SYSTEM VARIABLES (GLOBAL SCOPE)
  let weaponHeat = 0; // Current heat level (0-100)
  let maxHeat = 150; // Maximum heat before overheating (increased for longer firing time)
  let heatPerShot = 6; // Heat generated per shot (reduced for slower overheating)
  let heatDecayRate = 2; // Heat decay per frame when not shooting (slower cooling for balance)
  let isOverheated = false; // Overheated state
  let overheatCooldown = 3000; // 3 seconds to cool down from overheated state
  let lastOverheatTime = 0; // When overheating occurred
  
  // Function to get heat per shot based on weapon type
  function getHeatPerShot() {
    switch (currentWeaponType) {
      case 'normal':
        return 6; // Normal shots - moderate heat (balanced for longer firing time)
      case 'laser':
        return 12; // Laser shots - high heat (balanced for longer firing time)
      case 'bomb':
        return 20; // Bomb shots - very high heat (balanced for longer firing time)
      default:
        return 6;
    }
  }
  
  // Function to get rapid fire rate based on weapon type
  function getRapidFireRate() {
    switch (currentWeaponType) {
      case 'normal':
        return 50; // 20 shots per second - rapid fire!
      case 'laser':
        return 200; // 5 shots per second - slower but powerful
      case 'bomb':
        return 300; // 3 shots per second - slow but devastating
      default:
        return 50;
    }
  }
  
  // 🔥 NEW: Overheat management functions (GLOBAL SCOPE)
  function addHeat(amount) {
    weaponHeat = Math.min(weaponHeat + amount, maxHeat);
    
    // Check for overheating
    if (weaponHeat >= maxHeat && !isOverheated) {
      overheat();
    }
    
    // 🔥 CRITICAL HEAT WARNING (at 80% heat)
    if (weaponHeat >= 120 && weaponHeat < maxHeat) {
      console.log('⚠️ WARNING: Weapon heat critical! Consider cooling down...');
      // Visual feedback - could add screen flash or sound here
    }
    
    // 🔥 CRITICAL FIX: Update unified heat display (mobile display removed)
    updateAlwaysVisibleHeatDisplay();
  }
  
  function overheat() {
    isOverheated = true;
    lastOverheatTime = Date.now();
    console.log('🔥 WEAPON OVERHEATED! Need to cool down...');
    
    // Stop rapid fire immediately
    if (mouseRapidFireInterval) {
      clearInterval(mouseRapidFireInterval);
      mouseRapidFireInterval = null;
    }
    isMouseButtonDown = false;
    window.isMouseButtonDown = false;
    
    // 🔥 NEW: Enhanced visual feedback for overheat
    // Create screen flash effect
    const overheatFlash = document.createElement('div');
    overheatFlash.id = 'overheat-flash';
    overheatFlash.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 0, 0, 0.3);
      z-index: 9998;
      pointer-events: none;
      animation: overheatFlash 0.5s ease-out;
    `;
    
    // Add CSS animation for overheat flash
    const style = document.createElement('style');
    style.textContent = `
      @keyframes overheatFlash {
        0% { opacity: 0.8; }
        50% { opacity: 0.4; }
        100% { opacity: 0; }
      }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(overheatFlash);
    
    // Remove flash after animation
    setTimeout(() => {
      if (overheatFlash.parentNode) {
        overheatFlash.parentNode.removeChild(overheatFlash);
      }
    }, 500);
    
    // 🔥 NEW: Create persistent overheat warning on screen
    createPersistentOverheatWarning();
    
    // Visual and audio feedback
    if (typeof cheeseSoundManager !== 'undefined') {
      cheeseSoundManager.playStarWarsLaserVariant(); // Different sound for overheat
    }
  }
  
  function updateHeat() {
    if (isOverheated) {
      // Check if cooldown period is over
      if (Date.now() - lastOverheatTime > overheatCooldown) {
        isOverheated = false;
        weaponHeat = 0;
        console.log('❄️ Weapon cooled down! Ready to fire again!');
        
        // 🔥 NEW: Remove overheat warning when cooldown is complete
        const warning = document.getElementById('persistent-overheat-warning');
        if (warning) {
          warning.remove();
        }
      }
    } else if (weaponHeat > 0) {
      // Natural heat decay when not shooting
      weaponHeat = Math.max(0, weaponHeat - heatDecayRate);
    }
    
    // Update global variables for UI display
    window.weaponHeat = weaponHeat;
    window.isOverheated = isOverheated;
    
    // 🔥 CRITICAL FIX: Update unified heat display only
    updateAlwaysVisibleHeatDisplay();
  }
  
  // 🔥 NEW: Create persistent overheat warning on screen
  function createPersistentOverheatWarning() {
    // Remove any existing warning first
    const existingWarning = document.getElementById('persistent-overheat-warning');
    if (existingWarning) {
      existingWarning.remove();
    }
    
    // Create persistent overheat warning
    const warning = document.createElement('div');
    warning.id = 'persistent-overheat-warning';
    warning.style.cssText = `
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: rgba(255, 0, 0, 0.9);
      color: white;
      padding: 30px;
      border-radius: 15px;
      font-size: 24px;
      font-weight: bold;
      text-align: center;
      z-index: 10000;
      border: 3px solid #ff4444;
      box-shadow: 0 0 20px rgba(255, 0, 0, 0.8);
      animation: overheatWarningPulse 1s infinite;
    `;
    
    warning.innerHTML = `
      <div style="font-size: 48px; margin-bottom: 15px;">🔥</div>
      <div>WEAPON OVERHEATED!</div>
      <div style="font-size: 16px; margin-top: 10px; opacity: 0.8;">
        Wait for cooldown to finish
      </div>
    `;
    
    // Add pulsing animation
    const style = document.createElement('style');
    style.textContent = `
      @keyframes overheatWarningPulse {
        0% { transform: translate(-50%, -50%) scale(1); }
        50% { transform: translate(-50%, -50%) scale(1.05); }
        100% { transform: translate(-50%, -50%) scale(1); }
      }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(warning);
    
    // Remove warning when cooldown is complete
    setTimeout(() => {
      if (warning.parentNode) {
        warning.parentNode.removeChild(warning);
      }
    }, overheatCooldown);
  }
  
  // 🔥 REMOVED: Mobile overheat notification (consolidated into always-visible display)
  function showMobileOverheatNotification() {
    // Function kept for compatibility but no longer creates separate notifications
    // The always-visible heat display now handles all overheat notifications
  }
  
  // 🔥 REMOVED: Mobile heat display function (consolidated into always-visible display)
  // This eliminates the duplicate heat display that was causing confusion
  function updateMobileHeatDisplay() {
    // Function kept for compatibility but now just calls the unified display
    updateAlwaysVisibleHeatDisplay();
  }
  
  // 🔥 NEW: Always-visible heat display for all input methods
  function createAlwaysVisibleHeatDisplay() {
    // Remove existing display if it exists
    const existingDisplay = document.getElementById('always-visible-heat-display');
    if (existingDisplay) {
      existingDisplay.remove();
    }
    
    // Create the heat display container - COMPACT & INTEGRATED DESIGN
    const heatDisplay = document.createElement('div');
    heatDisplay.id = 'always-visible-heat-display';
    heatDisplay.style.cssText = `
      position: fixed;
      top: 10px;
      right: 10px;
      background: rgba(0, 0, 0, 0.7);
      border: 1px solid #4b5563;
      border-radius: 8px;
      padding: 8px;
      z-index: 9999;
      width: 120px;
      backdrop-filter: blur(5px);
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
      transition: all 0.3s ease;
      font-family: 'Courier New', monospace;
    `;
    
    // Create compact heat status (no title to save space)
    const heatStatus = document.createElement('div');
    heatStatus.id = 'always-visible-heat-status';
    heatStatus.style.cssText = `
      color: #00ff00;
      font-size: 0.75em;
      font-weight: bold;
      margin-bottom: 6px;
      text-align: center;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
      line-height: 1;
    `;
    heatDisplay.appendChild(heatStatus);
    
    // Create compact heat bar container
    const heatBarContainer = document.createElement('div');
    heatBarContainer.style.cssText = `
      margin-bottom: 6px;
    `;
    
    // Create compact heat bar
    const heatBar = document.createElement('div');
    heatBar.id = 'always-visible-heat-bar';
    heatBar.style.cssText = `
      width: 100%;
      height: 12px;
      background: #374151;
      border-radius: 6px;
      overflow: hidden;
      position: relative;
      border: 1px solid #6b7280;
    `;
    
    // Create heat bar fill
    const heatBarFill = document.createElement('div');
    heatBarFill.id = 'always-visible-heat-bar-fill';
    heatBarFill.style.cssText = `
      height: 100%;
      background: linear-gradient(90deg, #00ff00, #ffcc00, #ff8800, #ff0000);
      width: 0%;
      transition: width 0.3s ease;
      border-radius: 5px;
    `;
    
    heatBar.appendChild(heatBarFill);
    heatBarContainer.appendChild(heatBar);
    heatDisplay.appendChild(heatBarContainer);
    
    // Create compact heat percentage
    const heatPercentage = document.createElement('div');
    heatPercentage.id = 'always-visible-heat-percentage';
    heatPercentage.style.cssText = `
      color: #ffffff;
      font-size: 0.7em;
      text-align: center;
      font-weight: bold;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
      line-height: 1;
    `;
    heatDisplay.appendChild(heatPercentage);
    
    // Create compact overheat warning
    const overheatWarning = document.createElement('div');
    overheatWarning.id = 'always-visible-overheat-warning';
    overheatWarning.style.cssText = `
      color: #ff6b6b;
      font-size: 0.65em;
      text-align: center;
      margin-top: 4px;
      font-weight: bold;
      display: none;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
      line-height: 1;
    `;
    heatDisplay.appendChild(overheatWarning);
    
    // Add to body
    document.body.appendChild(heatDisplay);
    
    // 🔥 NEW: Add hover effects for better integration
    heatDisplay.addEventListener('mouseenter', () => {
      heatDisplay.style.transform = 'scale(1.05)';
      heatDisplay.style.background = 'rgba(0, 0, 0, 0.85)';
      heatDisplay.style.boxShadow = '0 6px 20px rgba(0, 0, 0, 0.6)';
    });
    
    heatDisplay.addEventListener('mouseleave', () => {
      heatDisplay.style.transform = 'scale(1)';
      heatDisplay.style.background = 'rgba(0, 0, 0, 0.7)';
      heatDisplay.style.boxShadow = '0 4px 16px rgba(0, 0, 0, 0.4)';
    });
    
    // 🔥 NEW: Auto-hide when heat is low (less intrusive)
    heatDisplay.style.opacity = '0.8';
    heatDisplay.style.transition = 'all 0.3s ease, opacity 0.5s ease';
    
    // 🔥 NEW: Auto-hide when game is paused or inactive
    let heatDisplayTimeout = null;
    const autoHideHeatDisplay = () => {
      if (heatDisplayTimeout) clearTimeout(heatDisplayTimeout);
      heatDisplayTimeout = setTimeout(() => {
        if (weaponHeat < 20 && !isOverheated) {
          heatDisplay.style.opacity = '0.4'; // Very subtle when not needed
        }
      }, 3000); // Hide after 3 seconds of low heat
    };
    
    // Call auto-hide function
    autoHideHeatDisplay();
    
    // Initial update
    updateAlwaysVisibleHeatDisplay();
    
    console.log('🔥 Compact heat display created!');
    
    // 🔥 NEW: Add toggle functionality for heat display
    window.toggleHeatDisplay = () => {
      const heatDisplay = document.getElementById('always-visible-heat-display');
      if (heatDisplay) {
        if (heatDisplay.style.display === 'none') {
          heatDisplay.style.display = 'block';
          console.log('🔥 Heat display shown');
        } else {
          heatDisplay.style.display = 'none';
          console.log('🔥 Heat display hidden');
        }
      }
    };
    
    // 🔥 NEW: Add mini mode toggle for ultra-compact display
    window.toggleHeatMiniMode = () => {
      const heatDisplay = document.getElementById('always-visible-heat-display');
      if (heatDisplay) {
        if (heatDisplay.classList.contains('mini-mode')) {
          heatDisplay.classList.remove('mini-mode');
          heatDisplay.style.width = '120px';
          heatDisplay.style.padding = '8px';
          console.log('🔥 Heat display: Normal mode');
        } else {
          heatDisplay.classList.add('mini-mode');
          heatDisplay.style.width = '80px';
          heatDisplay.style.padding = '4px';
          console.log('🔥 Heat display: Mini mode');
        }
      }
    };
    
    // 🔥 NEW: Add keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      if (e.key === 'h' || e.key === 'H') {
        if (e.ctrlKey || e.metaKey) { // Ctrl+H or Cmd+H
          e.preventDefault();
          window.toggleHeatDisplay();
        }
      }
      if (e.key === 'm' || e.key === 'M') {
        if (e.ctrlKey || e.metaKey) { // Ctrl+M or Cmd+M
          e.preventDefault();
          window.toggleHeatMiniMode();
        }
      }
    });
    
    console.log('🔥 Heat display controls: Ctrl+H (toggle), Ctrl+M (mini mode)');
  }
  
  // 🔥 NEW: Update always-visible heat display
  function updateAlwaysVisibleHeatDisplay() {
    const heatStatus = document.getElementById('always-visible-heat-status');
    const heatBarFill = document.getElementById('always-visible-heat-bar-fill');
    const heatPercentage = document.getElementById('always-visible-heat-percentage');
    const overheatWarning = document.getElementById('always-visible-overheat-warning');
    
    if (!heatStatus || !heatBarFill || !heatPercentage || !overheatWarning) {
      return; // Display not created yet
    }
    
    // Update heat status text with compact design
    if (isOverheated) {
      heatStatus.textContent = '🔥 OVERHEATED!';
      heatStatus.style.color = '#ff6b6b';
      overheatWarning.textContent = '❄️ COOLING...';
      overheatWarning.style.display = 'block';
    } else if (weaponHeat >= 80) {
      heatStatus.textContent = '⚠️ CRITICAL!';
      heatStatus.style.color = '#ff8800';
      overheatWarning.textContent = '🔥 COOL SOON!';
      overheatWarning.style.display = 'block';
    } else if (weaponHeat >= 50) {
      heatStatus.textContent = '🔥 HIGH';
      heatStatus.style.color = '#ffcc00';
      overheatWarning.style.display = 'none';
    } else if (weaponHeat >= 20) {
      heatStatus.textContent = '🌡️ MED';
      heatStatus.style.color = '#ffffff';
      overheatWarning.style.display = 'none';
    } else {
      heatStatus.textContent = '❄️ COOL';
      heatStatus.style.color = '#00ff00';
      overheatWarning.style.display = 'none';
    }
    
    // Update heat bar fill
    const heatPercent = (weaponHeat / maxHeat) * 100;
    heatBarFill.style.width = `${heatPercent}%`;
    
    // Update heat percentage text
    heatPercentage.textContent = `${Math.round(weaponHeat)}% / ${maxHeat}%`;
    
    // Update heat bar color based on heat level
    if (isOverheated) {
      heatBarFill.style.background = '#ff0000';
    } else if (heatPercent >= 80) {
      heatBarFill.style.background = '#ff8800';
    } else if (heatPercent >= 50) {
      heatBarFill.style.background = '#ffcc00';
    } else {
      heatBarFill.style.background = 'linear-gradient(90deg, #00ff00, #ffcc00, #ff8800, #ff0000)';
    }
    
    // 🔥 NEW: Dynamic opacity based on heat level for better integration
    const heatDisplay = document.getElementById('always-visible-heat-display');
    if (heatDisplay) {
      if (isOverheated || heatPercent >= 80) {
        heatDisplay.style.opacity = '1.0'; // Full visibility when critical
        heatDisplay.style.background = 'rgba(0, 0, 0, 0.9)';
      } else if (heatPercent >= 50) {
        heatDisplay.style.opacity = '0.9'; // High visibility when hot
        heatDisplay.style.background = 'rgba(0, 0, 0, 0.8)';
      } else if (heatPercent >= 20) {
        heatDisplay.style.opacity = '0.8'; // Medium visibility
        heatDisplay.style.background = 'rgba(0, 0, 0, 0.7)';
      } else {
        heatDisplay.style.opacity = '0.6'; // Low visibility when cool (less intrusive)
        heatDisplay.style.background = 'rgba(0, 0, 0, 0.6)';
      }
      
      // 🔥 NEW: Trigger auto-hide for low heat situations
      if (weaponHeat < 20 && !isOverheated) {
        setTimeout(() => {
          if (weaponHeat < 20 && !isOverheated) {
            heatDisplay.style.opacity = '0.4'; // Very subtle when not needed
          }
        }, 2000);
      }
    }
  }

  // 🧀 NEW: Custom cheese cursor system
  let customCursor = null;
  let globalMouseListener = null;
  
  function showCustomCursor() {
    if (customCursor) return; // Already exists
    
    // 🚀 NEW: Create ship cursor instead of cheese cursor for better control feel
    customCursor = document.createElement('div');
    customCursor.id = 'ship-cursor';
    
    // 🎯 CRITICAL: Make cursor the actual ship sprite with golden glow
    customCursor.innerHTML = `
      <div style="
        position: fixed;
        pointer-events: none;
        z-index: 10000;
        width: 32px;
        height: 32px;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="8" y="0" width="16" height="8" fill="%23ffd700" rx="2"/><rect x="6" y="8" width="20" height="16" fill="%23ffd700" rx="2"/><rect x="12" y="24" width="8" height="8" fill="%23ff8c00" rx="1"/><circle cx="16" cy="16" r="4" fill="%23ff6b35"/><circle cx="14" cy="14" r="1" fill="%23ff4500"/><circle cx="18" cy="14" r="1" fill="%23ff4500"/></svg>') center/contain no-repeat;
        transform: translate(-50%, -50%);
        filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.8)) drop-shadow(0 0 20px rgba(255, 215, 0, 0.6));
        animation: shipCursorGlow 2s ease-in-out infinite alternate;
      ">
      </div>
    `;
    
    // Add CSS animation for ship cursor glow
    const style = document.createElement('style');
    style.textContent = `
      @keyframes shipCursorGlow {
        0% {
          filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.8)) drop-shadow(0 0 20px rgba(255, 215, 0, 0.6));
          transform: translate(-50%, -50%) scale(1);
        }
        100% {
          filter: drop-shadow(0 0 15px rgba(255, 215, 0, 1)) drop-shadow(0 0 25px rgba(255, 215, 0, 0.8)) drop-shadow(0 0 35px rgba(255, 215, 0, 0.6));
          transform: translate(-50%, -50%) scale(1.1);
        }
      }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(customCursor);
    
    // 🚀 CRITICAL: Set up global mouse listener for cursor tracking
    globalMouseListener = (e) => {
      updateCustomCursorPosition(e);
    };
    document.addEventListener('mousemove', globalMouseListener);
    
    console.log('🚀 Ship cursor created and visible');
  }
  
  function hideCustomCursor() {
    if (customCursor && customCursor.parentNode) {
      customCursor.parentNode.removeChild(customCursor);
      customCursor = null;
      console.log('🚀 Ship cursor hidden');
    }
  }
  
  function cleanupCustomCursor() {
    hideCustomCursor();
    if (globalMouseListener) {
      document.removeEventListener('mousemove', globalMouseListener);
      globalMouseListener = null;
      console.log('🚀 Ship cursor cleanup completed');
    }
  }
  
  function updateCustomCursorPosition(e) {
    if (customCursor) {
      customCursor.style.left = e.clientX + 'px';
      customCursor.style.top = e.clientY + 'px';
    }
  }
  
  // 🖱️ NEW: Mouse control functions
  function setupMouseControls() {
    const canvas = document.getElementById('space-invaders-canvas');
    if (!canvas) {
      console.warn('⚠️ Canvas not available for mouse controls');
      return;
    }

    console.log('🖱️ Setting up mouse controls for canvas:', canvas);

    // 🚀 REMOVED: Global mouse click handling moved to setupGlobalMouseControls() function
    // This prevents duplicate event listeners and keeps the code organized

    // Enable mouse control when mouse enters canvas
    canvas.addEventListener('mouseenter', () => {
      isMouseOverCanvas = true;
      console.log('🖱️ Mouse entered canvas - mouse control enabled');
      console.log('🖱️ Mouse control state:', { isMouseControlEnabled, isMouseOverCanvas, isSpaceInvadersPaused });
      
      // Visual feedback: Add a border to show mouse control is active
      canvas.style.border = '3px solid #10b981';
      canvas.style.boxShadow = '0 0 20px rgba(16, 185, 129, 0.5)';
      
      // 🚀 NEW: Hide default cursor and show ship cursor
      canvas.style.cursor = 'none';
      showCustomCursor();
      
      // 🔧 CRITICAL FIX: DO NOT change mouse target on entry - wait for actual mouse movement
      // This prevents the "magnetic pull" toward invaders by keeping ship in place
      console.log('🔍 === MOUSE ENTERED CANVAS ===');
      console.log(`🖱️ Mouse entered canvas - keeping ship at current position: X=${playerShip.x.toFixed(1)}, Y=${playerShip.y.toFixed(1)}`);
      console.log(`🎯 Mouse targets remain at: X=${mouseTargetX.toFixed(1)}, Y=${mouseTargetY.toFixed(1)}`);
      console.log(`📍 Ship center would be at: X=${(mouseTargetX + playerShip.width / 2).toFixed(1)}, Y=${(mouseTargetY + playerShip.height / 2).toFixed(1)}`);
      console.log(`📏 Canvas dimensions: ${canvasWidth}x${canvasHeight}`);
      console.log(`🔄 Mouse movement flag reset: false`);
      
      // 🔧 CRITICAL FIX: Reset mouse movement flag to prevent upward movement on entry
      window.hasMouseMovedInCanvas = false;
      
      // 🚀 IMPROVED: Only update targets when mouse actually moves within canvas
      // This ensures smooth, controlled movement without sudden jumps
      console.log('🔍 === END MOUSE ENTER DEBUG ===');
    });

    // 🚀 ENHANCED: Mouse left canvas but global tracking continues
    canvas.addEventListener('mouseleave', () => {
      // 🔧 FIXED: Add delay before updating state to prevent flickering
      setTimeout(() => {
        if (!canvas.matches(':hover')) { // Double-check mouse is really gone
          isMouseOverCanvas = false;
          console.log('🖱️ Mouse left canvas - switching to global tracking mode');
          console.log('🖱️ Mouse control state:', { isMouseControlEnabled, isMouseOverCanvas, isSpaceInvadersPaused });
          
          // Visual feedback: Change border to indicate global tracking mode
          canvas.style.border = '3px solid #f59e0b';
          canvas.style.boxShadow = '0 0 20px rgba(245, 158, 11, 0.5)';
          
          // 🚀 NEW: Show default cursor when leaving canvas
          canvas.style.cursor = 'default';
          hideCustomCursor();
          
          // 🚀 IMPROVED: Keep mouse target at current ship position for smoother re-entry
          // Don't reset - this prevents the 300px jump when re-entering
          console.log('🔍 === MOUSE LEFT CANVAS - GLOBAL TRACKING ACTIVE ===');
          console.log(`🖱️ Mouse left canvas - global tracking continues`);
          console.log(`🚀 Ship current position: X=${playerShip.x.toFixed(1)}, Y=${playerShip.y.toFixed(1)}`);
          console.log(`📍 Ship can still move with global mouse tracking`);
          console.log(`🔄 Mouse movement flag: ${window.hasMouseMovedInCanvas}`);
          
          // 🔧 CRITICAL FIX: Keep global mouse tracking active
          // This allows ship movement even when mouse is outside container
          console.log('🔍 === END MOUSE LEAVE - GLOBAL TRACKING ENABLED ===');
        }
      }, 100); // 100ms delay to prevent accidental state change
    });

    // 🔥 SIMPLIFIED: Track mouse position for ship movement
    canvas.addEventListener('mousemove', (e) => {
      if (!isMouseControlEnabled || !isMouseOverCanvas || isSpaceInvadersPaused) return;
      
      // Update ship cursor position
      updateCustomCursorPosition(e);
      
      const rect = canvas.getBoundingClientRect();
      mouseX = e.clientX - rect.left;
      mouseY = e.clientY - rect.top;
      
      // Track global mouse coordinates
      window.mouseX = e.clientX;
      window.mouseY = e.clientY;
      
      // 🔥 CRITICAL FIX: Simple, direct mouse targeting - ship follows cursor
      mouseTargetX = mouseX - playerShip.width / 2;
      mouseTargetY = mouseY - playerShip.height / 2;
      
      // Mark that mouse has moved in canvas
      window.hasMouseMovedInCanvas = true;
      
      // 🔥 CRITICAL FIX: Auto-shoot when mouse moves (if enabled) - NO HEAT BUILDUP
      if (autoShootEnabled) {
        const currentTime = Date.now();
        
        // Check if enough time has passed since last auto-shoot
        if (currentTime - lastAutoShootTime >= autoShootFiringRate) {
          // Check if we've moved to a new position (prevent multiple shots during continuous movement)
          const currentPos = { x: Math.round(playerShip.x / 10), y: Math.round(playerShip.y / 10) };
          if (currentPos.x !== lastMovementPosition.x || currentPos.y !== lastMovementPosition.y) {
            autoShoot(); // Use special auto-shoot function that doesn't add heat
            lastMovementPosition = currentPos; // Update last position
          }
        }
      }
    });

    // 🚀 NEW: Rapid-fire mouse shooting system with OVERHEAT MECHANICS!
    let isMouseButtonDown = false;
    let mouseRapidFireInterval = null;
    
    // Make variables globally accessible for UI display
    window.isMouseButtonDown = isMouseButtonDown;
    
    // 🚀 REMOVED: Canvas-specific mouse click handling (now handled globally above)
    // The global event listeners above now handle all mouse clicking for shooting
    
    // Mouse leave canvas - stop rapid fire
    canvas.addEventListener('mouseleave', () => {
      if (isMouseButtonDown) {
        isMouseButtonDown = false;
        window.isMouseButtonDown = false; // Update global state
        console.log('🖱️ Mouse left canvas - stopping rapid fire');
        
        // Clear rapid fire interval
        if (mouseRapidFireInterval) {
          clearInterval(mouseRapidFireInterval);
          mouseRapidFireInterval = null;
        }
      }
    });

    // Prevent context menu on right-click
    canvas.addEventListener('contextmenu', (e) => {
      e.preventDefault(); // Prevent context menu (Save image, etc.)
    });

    console.log('✅ Mouse controls setup complete with GLOBAL shooting support');
    
    // 🔍 DEBUG: Add global debug function for troubleshooting
    window.debugMouseControl = () => {
      console.log('🔍 === MOUSE CONTROL STATE DEBUG ===');
      console.log(`🖱️ Mouse Control Enabled: ${isMouseControlEnabled}`);
      console.log(`🖱️ Mouse Over Canvas: ${isMouseOverCanvas}`);
      console.log(`⏸️ Game Paused: ${isSpaceInvadersPaused}`);
      console.log(`🎯 Mouse Target X: ${mouseTargetX}`);
      console.log(`🎯 Mouse Target Y: ${mouseTargetY}`);
      console.log(`🚀 Ship X: ${playerShip.x}`);
      console.log(`🚀 Ship Y: ${playerShip.y}`);
      console.log(`📏 Canvas: ${canvasWidth}x${canvasHeight}`);
      console.log(`🔄 Has Mouse Moved: ${window.hasMouseMovedInCanvas}`);
      console.log(`🌍 Global Mouse X: ${window.mouseX}`);
      console.log(`🌍 Global Mouse Y: ${window.mouseY}`);
      console.log(`💾 Last Canvas X: ${window.lastCanvasMouseX}`);
      console.log(`💾 Last Canvas Y: ${window.lastCanvasMouseY}`);
      console.log('🔍 === END STATE DEBUG ===');
    };
    
    // 📱 DEBUG: Add mobile touch control debug function
    window.debugTouchControl = () => {
      console.log('📱 === TOUCH CONTROL STATE DEBUG ===');
      console.log(`📱 Is Touching: ${isTouching}`);
      console.log(`📱 Touch Start X: ${touchStartX}`);
      console.log(`📱 Touch Start Y: ${touchStartY}`);
      console.log(`🚀 Ship X: ${playerShip.x}`);
      console.log(`🚀 Ship Y: ${playerShip.y}`);
      console.log(`📏 Canvas: ${canvasWidth}x${canvasHeight}`);
      console.log(`⏸️ Game Paused: ${isSpaceInvadersPaused}`);
      console.log(`🔥 Is Overheated: ${isOverheated}`);
      console.log(`🎯 Current Weapon: ${currentWeaponType}`);
      console.log('📱 === END TOUCH DEBUG ===');
    };
    
    console.log('🔍 Debug function available: window.debugMouseControl()');
    console.log('📱 Debug function available: window.debugTouchControl()');
    
    // 📱 NEW: Mobile control instructions overlay
    function showMobileControlInstructions() {
      const instructions = document.createElement('div');
      instructions.id = 'mobile-control-instructions';
      instructions.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0, 0, 0, 0.9);
        color: white;
        padding: 20px;
        border-radius: 15px;
        text-align: center;
        z-index: 10000;
        max-width: 300px;
        font-family: Arial, sans-serif;
      `;
      
      instructions.innerHTML = `
        <h3 style="margin: 0 0 15px 0; color: #f0c92c;">📱 Mobile Controls</h3>
        <div style="margin-bottom: 15px;">
          <strong>🚀 Ship Movement:</strong><br>
          Touch and drag anywhere on the screen to move the ship
        </div>
        <div style="margin-bottom: 15px;">
          <strong>🎯 Shooting:</strong><br>
          Ship automatically shoots while moving
        </div>
        <div style="margin-bottom: 15px;">
          <strong>⚡ Quick Actions:</strong><br>
          Swipe up: Extra shot<br>
          Swipe down: Bomb (if available)
        </div>
        <button onclick="this.parentElement.remove()" style="
          background: #f0c92c;
          color: black;
          border: none;
          padding: 10px 20px;
          border-radius: 8px;
          cursor: pointer;
          font-weight: bold;
        ">Got it!</button>
      `;
      
      document.body.appendChild(instructions);
      
      // Auto-hide after 10 seconds
      setTimeout(() => {
        if (instructions.parentElement) {
          instructions.remove();
        }
      }, 10000);
    }
    
    // 📱 NEW: Show mobile instructions on first touch
    let mobileInstructionsShown = false;
    function checkAndShowMobileInstructions() {
      if (!mobileInstructionsShown && window.innerWidth <= 768) {
        // Check if it's a mobile device
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        if (isMobile) {
          setTimeout(() => {
            showMobileControlInstructions();
            mobileInstructionsShown = true;
          }, 2000); // Show after 2 seconds
        }
      }
    }
    
    // 📱 NEW: Call mobile instructions check when game starts
    document.addEventListener('DOMContentLoaded', () => {
      checkAndShowMobileInstructions();
    });
    
    // 📱 NEW: Mobile control status indicator
    function updateMobileControlStatus() {
      let statusIndicator = document.getElementById('mobile-control-status');
      
      if (!statusIndicator) {
        statusIndicator = document.createElement('div');
        statusIndicator.id = 'mobile-control-status';
        statusIndicator.style.cssText = `
          position: fixed;
          top: 10px;
          right: 10px;
          background: rgba(0, 0, 0, 0.8);
          color: white;
          padding: 8px 12px;
          border-radius: 8px;
          font-size: 12px;
          z-index: 9999;
          font-family: Arial, sans-serif;
        `;
        document.body.appendChild(statusIndicator);
      }
      
      if (isTouching) {
        statusIndicator.innerHTML = '📱 Touch Active';
        statusIndicator.style.background = 'rgba(0, 255, 0, 0.8)';
      } else {
        statusIndicator.innerHTML = '📱 Touch Ready';
        statusIndicator.style.background = 'rgba(0, 0, 0, 0.8)';
      }
    }
    
    // 📱 NEW: Update mobile control status in game loop
    setInterval(updateMobileControlStatus, 100);
    
    // 🔥 NEW: Add heat system debug functions
    window.debugHeatSystem = () => {
      console.log('🔥 === HEAT SYSTEM DEBUG ===');
      console.log(`🌡️ Current Heat: ${weaponHeat}/${maxHeat}`);
      console.log(`🔥 Overheated: ${isOverheated}`);
      console.log(`⏰ Last Overheat: ${lastOverheatTime}`);
      console.log(`❄️ Cooldown Remaining: ${isOverheated ? Math.max(0, overheatCooldown - (Date.now() - lastOverheatTime)) : 0}ms`);
      console.log(`🔫 Heat Per Shot: ${getHeatPerShot()}`);
      console.log(`📉 Heat Decay Rate: ${heatDecayRate}`);
      console.log(`⚡ Unified Firing Rate: ${unifiedFiringRate}ms (${(1000/unifiedFiringRate).toFixed(1)} shots/sec)`);
      console.log(`⏱️ Time Since Last Shot: ${Date.now() - lastUnifiedShotTime}ms`);
      console.log(`🎯 Next Shot Available: ${Math.max(0, unifiedFiringRate - (Date.now() - lastUnifiedShotTime))}ms`);
      console.log('🔥 === END HEAT DEBUG ===');
    };
    
    window.testHeatSystem = () => {
      console.log('🧪 Testing heat system...');
      addHeat(50);
      console.log(`🔥 Added 50 heat - Current: ${weaponHeat}/${maxHeat}`);
    };
    
    window.resetHeatSystem = () => {
      console.log('🔄 Resetting heat system...');
      weaponHeat = 0;
      isOverheated = false;
      lastOverheatTime = 0;
      console.log('✅ Heat system reset complete');
    };
    
    console.log('🔥 Heat system debug functions available:');
    console.log('  window.debugHeatSystem() - Show heat system state');
    console.log('  window.testHeatSystem() - Test heat addition');
    console.log('  window.resetHeatSystem() - Reset heat manually');
  } // End of setupMouseControls function

  // 🚀 NEW: Global mouse click handling for shooting anywhere on screen
  function setupGlobalMouseControls() {
    console.log('🌍 Setting up global mouse controls for shooting anywhere on screen');
    
    // Global mouse button down - start rapid fire (works everywhere)
    document.addEventListener('mousedown', (e) => {
      if (e.button === 0 && !isMouseButtonDown) { // Left mouse button
        if (!isMouseControlEnabled || isSpaceInvadersPaused) return;
        
        // 🔥 CHECK FOR OVERHEATING
        if (isOverheated) {
          console.log('🔥 Weapon is overheated! Wait for cooldown...');
          return; // Can't shoot when overheated
        }
        
        isMouseButtonDown = true;
        window.isMouseButtonDown = true; // Update global state
        console.log('🖱️ Left mouse button pressed - starting rapid fire! (Global)');
        
        // Start rapid fire immediately
        playerShoot();
        
        // 🔥 CRITICAL FIX: Use unified firing rate instead of separate interval
        // This ensures consistent heat buildup across all control methods
        mouseRapidFireInterval = setInterval(() => {
          if (isMouseButtonDown && !isSpaceInvadersPaused) {
            // 🔥 CRITICAL FIX: Check for overheating in mouse rapid fire
            if (isOverheated) {
              console.log('🔥 Mouse rapid fire stopped - weapon overheated!');
              // Stop rapid fire when overheated
              clearInterval(mouseRapidFireInterval);
              mouseRapidFireInterval = null;
              isMouseButtonDown = false;
              window.isMouseButtonDown = false;
              return;
            }
            
            // For special weapons, set the flag to allow shooting
            if (currentWeaponType === 'laser' || currentWeaponType === 'bomb') {
              window.isQuickShotCall = true;
            }
            
            // 🔥 CRITICAL FIX: Use unified firing rate check with mouse priority
            const currentTime = Date.now();
            if (currentTime - lastUnifiedShotTime >= unifiedFiringRate) {
              playerShoot(); // This will handle heat addition and firing rate
            } else {
              // 🔥 CRITICAL FIX: Allow mouse to override firing rate slightly for responsiveness
              const remainingDelay = unifiedFiringRate - (currentTime - lastUnifiedShotTime);
              if (remainingDelay <= 15) { // Allow mouse to fire if delay is very small
                playerShoot(); // Override for mouse responsiveness
              }
            }
          }
        }, unifiedFiringRate); // Use unified firing rate instead of weapon-specific rate
      }
    });

    // Global mouse button up - stop rapid fire (works everywhere)
    document.addEventListener('mouseup', (e) => {
      if (e.button === 0) { // Left mouse button
        if (isMouseButtonDown) {
          isMouseButtonDown = false;
          window.isMouseButtonDown = false; // Update global state
          console.log('🖱️ Left mouse button released - stopping rapid fire (Global)');
          
          // Clear rapid fire interval
          if (mouseRapidFireInterval) {
            clearInterval(mouseRapidFireInterval);
            mouseRapidFireInterval = null;
          }
        }
      } else if (e.button === 2) { // Right mouse button
        e.preventDefault(); // Prevent context menu
        if (isSpaceInvadersPaused) return;
        
        // Cycle through weapons on right-click (works everywhere)
        const weapons = ['normal', 'laser', 'bomb'];
        const currentIndex = weapons.indexOf(currentWeaponType);
        const nextIndex = (currentIndex + 1) % weapons.length;
        const nextWeapon = weapons[nextIndex];
        
        switchWeapon(nextWeapon);
        console.log(`🖱️ Right-click: Switched to ${nextWeapon} weapon (Global)`);
      }
    });

    // 🚀 NEW: Global context menu prevention for the entire game
    document.addEventListener('contextmenu', (e) => {
      if (!isSpaceInvadersPaused) {
        e.preventDefault(); // Prevent Windows context menu (Save Picture As, etc.)
        console.log('🚫 Context menu prevented - game is active');
      }
    });

      console.log('✅ Global mouse controls setup complete - shooting works everywhere!');
  
  // 🔍 DEBUG: Add Phoenix image status check
  window.debugPhoenixImages = () => {
    console.log('🔍 === PHOENIX IMAGE STATUS DEBUG ===');
    console.log('🔥 Phoenix Bird Image:', {
      exists: typeof phoenixBirdImg !== 'undefined',
      src: phoenixBirdImg?.src,
      complete: phoenixBirdImg?.complete,
      naturalWidth: phoenixBirdImg?.naturalWidth,
      naturalHeight: phoenixBirdImg?.naturalHeight,
      readyState: phoenixBirdImg?.readyState
    });
    console.log('🥚 Phoenix Egg Image:', {
      exists: typeof phoenixEggImg !== 'undefined',
      src: phoenixEggImg?.src,
      complete: phoenixEggImg?.complete,
      naturalWidth: phoenixEggImg?.naturalWidth,
      naturalHeight: phoenixEggImg?.naturalHeight,
      readyState: phoenixEggImg?.readyState
    });
    console.log('🐤 Mini Phoenix Image:', {
      exists: typeof miniPhoenixImg !== 'undefined',
      src: miniPhoenixImg?.src,
      complete: miniPhoenixImg?.complete,
      naturalWidth: miniPhoenixImg?.naturalWidth,
      naturalHeight: miniPhoenixImg?.naturalHeight,
      readyState: miniPhoenixImg?.readyState
    });
    console.log('🔍 === END PHOENIX IMAGE DEBUG ===');
  };
  
  console.log('🔍 Phoenix image debug function available: window.debugPhoenixImages()');
  
  // 🔍 DEBUG: Add Phoenix entity status check
  window.debugPhoenixEntities = () => {
    console.log('🔍 === PHOENIX ENTITY STATUS DEBUG ===');
    console.log('🔥 Phoenix Birds:', phoenixWaves.length, 'entities');
    phoenixWaves.forEach((phoenix, index) => {
      console.log(`  ${index}: Position (${phoenix.x}, ${phoenix.y}), Health: ${phoenix.health}/${phoenix.maxHealth}, Dead: ${phoenix.isDead}`);
    });
    console.log('🥚 Phoenix Eggs:', phoenixEggs.length, 'entities');
    phoenixEggs.forEach((egg, index) => {
      console.log(`  ${index}: Position (${egg.x}, ${egg.y}), Destroyed: ${egg.isDestroyed}, Hatch Timer: ${egg.hatchTimer}`);
    });
    console.log('🐤 Mini Phoenix:', miniPhoenixes.length, 'entities');
    miniPhoenixes.forEach((mini, index) => {
      console.log(`  ${index}: Position (${mini.x}, ${mini.y}), Health: ${mini.health}/${mini.maxHealth}, Dead: ${mini.isDead}`);
    });
    console.log('🔍 === END PHOENIX ENTITY DEBUG ===');
  };
  
  console.log('🔍 Phoenix entity debug function available: window.debugPhoenixEntities()');
  
  // 🔍 DEBUG: Add collision detection test function
  window.testPhoenixCollision = () => {
    console.log('🔍 === PHOENIX COLLISION TEST ===');
    
    // Test with a sample bullet
    const testBullet = { x: 100, y: 100, width: 4, height: 10 };
    
    if (miniPhoenixes.length > 0) {
      const mini = miniPhoenixes[0];
      console.log('🐤 Testing collision with mini-Phoenix:', {
        bullet: { x: testBullet.x, y: testBullet.y, width: testBullet.width, height: testBullet.height },
        mini: { x: mini.x, y: mini.y, width: mini.width, height: mini.height },
        collision: checkCollision(testBullet, mini)
      });
    }
    
    if (phoenixEggs.length > 0) {
      const egg = phoenixEggs[0];
      console.log('🥚 Testing collision with Phoenix egg:', {
        bullet: { x: testBullet.x, y: testBullet.y, width: testBullet.width, height: testBullet.height },
        egg: { x: egg.x, y: egg.y, width: egg.width, height: egg.height },
        collision: checkCollision(testBullet, egg)
      });
    }
    
    console.log('🔍 === END COLLISION TEST ===');
  };
  
  console.log('🔍 Phoenix collision test function available: window.testPhoenixCollision()');
}

  // 🚀 NEW: Enhanced heat system debug functions
  function setupHeatSystemDebug() {
    window.setUnifiedFiringRate = (rateMs) => {
      unifiedFiringRate = Math.max(50, Math.min(500, rateMs)); // Limit between 50ms and 500ms
      console.log(`⚡ Unified firing rate set to ${unifiedFiringRate}ms (${(1000/unifiedFiringRate).toFixed(1)} shots/sec)`);
    };
    
    window.testFiringRate = () => {
      console.log('🧪 === FIRING RATE TEST ===');
      console.log(`⚡ Current Rate: ${unifiedFiringRate}ms (${(1000/unifiedFiringRate).toFixed(1)} shots/sec)`);
      console.log(`⏱️ Time Since Last Shot: ${Date.now() - lastUnifiedShotTime}ms`);
      console.log(`🎯 Can Fire Now: ${(Date.now() - lastUnifiedShotTime) >= unifiedFiringRate ? 'YES' : 'NO'}`);
      console.log(`🔥 Current Heat: ${weaponHeat}/${maxHeat}`);
      console.log(`🔥 Overheated: ${isOverheated}`);
      console.log('🧪 === END TEST ===');
    };
    
    window.testOverheatSystem = () => {
      console.log('🔥 === OVERHEAT SYSTEM TEST ===');
      console.log(`🌡️ Current Heat: ${weaponHeat}/${maxHeat}`);
      console.log(`🔥 Overheated: ${isOverheated}`);
      console.log(`⏰ Last Overheat: ${lastOverheatTime}`);
      console.log(`❄️ Cooldown Remaining: ${isOverheated ? Math.max(0, overheatCooldown - (Date.now() - lastOverheatTime)) : 0}ms`);
      console.log(`🔫 Heat Per Shot: ${getHeatPerShot()}`);
      console.log(`📉 Heat Decay Rate: ${heatDecayRate}`);
      console.log(`⚡ Firing Rate: ${unifiedFiringRate}ms`);
      console.log('🔥 === END TEST ===');
    };
    
    console.log('🔥 Enhanced heat debug functions available:');
    console.log('  window.debugHeatSystem() - Show heat status');
    console.log('  window.testHeatSystem() - Test heat addition');
    console.log('  window.resetHeatSystem() - Reset heat manually');
    console.log('  window.setUnifiedFiringRate(rateMs) - Set firing rate (50-500ms)');
    console.log('  window.testFiringRate() - Test firing rate system');
    console.log('  window.testOverheatSystem() - Test overheat system');
  }

  // 🖱️ NEW: Notification function for mouse control feedback
  function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : type === 'phoenix' ? '#ff6b35' : '#3b82f6'};
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      font-family: Arial, sans-serif;
      font-size: 14px;
      font-weight: bold;
      z-index: 10000;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      transform: translateX(400px);
      transition: transform 0.3s ease;
    `;
    notification.textContent = message;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
      notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
      notification.style.transform = 'translateX(400px)';
      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification);
        }
      }, 300);
    }, 3000);
  }

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
  
  // 🔥 NEW: Make heat system functions globally available
  // Mobile heat display removed - now using unified always-visible display
  window.showMobileOverheatNotification = showMobileOverheatNotification;

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

// 🧪 TESTING: Manual boss spawn function for testing
window.testBossSpawn = function(waveNum = 5) {
  console.log(`🧪 Manual boss spawn test for wave ${waveNum}...`);
  if (boss && !bossDefeated) {
    console.log('❌ Boss already active! Defeat current boss first.');
    return false;
  }
  
  waveNumber = waveNum;
  gamePhase = 'boss';
  phaseTimer = 0;
  invaders = []; // Clear invaders
  invaderBullets = [];
  
  try {
    spawnBoss();
    console.log(`✅ Boss spawned for wave ${waveNum}!`);
    return true;
  } catch (error) {
    console.error('❌ Boss spawn failed:', error);
    return false;
  }
};

// 🧪 TESTING: Check boss status
window.checkBossStatus = function() {
  console.log('🔍 Boss Status Check:');
  console.log('Boss exists:', !!boss);
  if (boss) {
    console.log('Boss type:', boss.type);
    console.log('Boss name:', boss.name);
    console.log('Boss health:', boss.health, '/', boss.maxHealth);
    console.log('Boss defeated:', bossDefeated);
  }
};

// 📱 CRITICAL TEST: Mobile control debugging
window.testMobileControls = function() {
  console.log('📱 MOBILE CONTROLS TEST...');
  
  // Check if mobile controls are enabled
  console.log('📱 Mobile controls status:', {
    isTouching: isTouching,
    touchStartX: touchStartX,
    touchStartY: touchStartY,
    touchStartTime: touchStartTime
  });
  
  // Check if canvas exists
  const canvas = document.getElementById('space-invaders-canvas');
  if (canvas) {
    console.log('✅ Canvas found:', canvas.id);
    console.log('📱 Canvas dimensions:', canvas.width, 'x', canvas.height);
    console.log('📱 Canvas position:', canvas.getBoundingClientRect());
  } else {
    console.error('❌ Canvas not found!');
  }
  
  // Check player ship position
  if (playerShip) {
    console.log('📱 Player ship position:', {
      x: playerShip.x,
      y: playerShip.y,
      width: playerShip.width,
      height: playerShip.height
    });
  } else {
    console.error('❌ Player ship not found!');
  }
  
  // Check if touch event listeners are active
  console.log('📱 Touch event listeners status:', {
    touchstart: document.addEventListener.toString().includes('touchstart'),
    touchmove: document.addEventListener.toString().includes('touchmove'),
    touchend: document.addEventListener.toString().includes('touchend')
  });
  
  // Check control conflicts
  console.log('🚨 CONTROL CONFLICT CHECK:');
  console.log('   - isMobileDevice:', isMobileDevice);
  console.log('   - isMouseControlEnabled:', isMouseControlEnabled);
  console.log('   - isTouching:', isTouching);
  console.log('   - Mouse controls disabled:', !isMouseControlEnabled || isSpaceInvadersPaused || isTouching || isMobileDevice);
  
  // Force enable mobile controls
  console.log('📱 Forcing mobile controls to enable...');
  enableGlobalSpaceInvadersTouch();
  
  return 'Mobile controls test complete - check console for details';
};

// 🚀 CRITICAL FIX: Disable mouse controls completely on mobile
window.disableMouseControlsOnMobile = function() {
  if (isMobileDevice) {
    console.log('🚨 DISABLING MOUSE CONTROLS ON MOBILE DEVICE');
    isMouseControlEnabled = false;
    console.log('✅ Mouse controls disabled for mobile');
    return 'Mouse controls disabled on mobile';
  } else {
    console.log('🖱️ Desktop device - mouse controls remain enabled');
    return 'Mouse controls remain enabled on desktop';
  }
};

// 🔥 CRITICAL TEST: Phoenix collision system
window.testPhoenixCollisionSystem = function() {
  console.log('🔥 TESTING PHOENIX COLLISION SYSTEM...');
  
  if (!playerShip) {
    console.error('❌ Player ship not found!');
    return false;
  }
  
  console.log('✅ Player ship found:', {
    x: playerShip.x,
    y: playerShip.y,
    health: playerShip.health,
    invincible: playerShip.invincible
  });
  
  // Check if Phoenix entities exist
  console.log('🔥 Phoenix entities status:', {
    phoenixWaves: phoenixWaves ? phoenixWaves.length : 'undefined',
    phoenixEggs: phoenixEggs ? phoenixEggs.length : 'undefined',
    miniPhoenixes: miniPhoenixes ? miniPhoenixes.length : 'undefined'
  });
  
  // Test collision detection function
  if (typeof checkPhoenixPlayerCollisions === 'function') {
    console.log('✅ checkPhoenixPlayerCollisions function exists');
    
    // Test collision with a dummy Phoenix entity
    const testPhoenix = {
      x: playerShip.x + 10,
      y: playerShip.y + 10,
      width: 30,
      height: 30,
      isDead: false,
      damage: 1
    };
    
    const collision = checkCollision(testPhoenix, playerShip);
    console.log('🔥 Test collision result:', collision);
    
    if (collision) {
      console.log('✅ Collision detection working!');
    } else {
      console.log('❌ Collision detection not working!');
    }
  } else {
    console.error('❌ checkPhoenixPlayerCollisions function not found!');
  }
  
  return 'Phoenix collision test complete';
};

// 🎯 PROFESSIONAL GAME BALANCE: Complete progression analysis
window.analyzeGameBalance = function() {
  console.log('🎯 PROFESSIONAL GAME BALANCE ANALYSIS');
  console.log('=====================================');
  
  // Current wave analysis
  console.log(`📊 Current Wave: ${waveNumber}`);
  console.log(`🎮 Game Phase: ${gamePhase}`);
  console.log(`🔥 Phoenix Wave: ${isPhoenixWave ? 'YES' : 'NO'}`);
  
  // Phoenix wave analysis
  if (waveNumber % phoenixWaveConfig.waveFrequency === 0) {
    console.log(`🔥 Next Phoenix Wave: Wave ${waveNumber + phoenixWaveConfig.waveFrequency}`);
  } else {
    const nextPhoenix = Math.ceil(waveNumber / phoenixWaveConfig.waveFrequency) * phoenixWaveConfig.waveFrequency;
    console.log(`🔥 Next Phoenix Wave: Wave ${nextPhoenix} (in ${nextPhoenix - waveNumber} waves)`);
  }
  
  // Boss wave analysis
  const bossWaves = [10, 25, 75, 100];
  const nextBoss = bossWaves.find(wave => wave > waveNumber);
  if (nextBoss) {
    console.log(`🏆 Next Boss Wave: Wave ${nextBoss} (in ${nextBoss - waveNumber} waves)`);
  } else {
    console.log(`🏆 All Boss Waves Completed! (Waves 10, 25, 75, 100)`);
  }
  
  // Difficulty scaling analysis
  let difficultyMultiplier = 1.0;
  if (waveNumber >= 8) difficultyMultiplier = 1.1;
  if (waveNumber >= 13) difficultyMultiplier = 1.2;
  if (waveNumber >= 18) difficultyMultiplier = 1.3;
  if (waveNumber >= 23) difficultyMultiplier = 1.4;
  if (waveNumber >= 28) difficultyMultiplier = 1.5;
  if (waveNumber >= 33) difficultyMultiplier = 1.6;
  if (waveNumber >= 38) difficultyMultiplier = 1.7;
  if (waveNumber >= 43) difficultyMultiplier = 1.8;
  if (waveNumber >= 48) difficultyMultiplier = 1.9;
  if (waveNumber >= 53) difficultyMultiplier = 2.0;
  if (waveNumber >= 100) difficultyMultiplier = 3.0;
  if (waveNumber >= 200) difficultyMultiplier = 7.0;
  if (waveNumber >= 500) difficultyMultiplier = 12.0;
  if (waveNumber >= 1000) difficultyMultiplier = 25.0;
  
  console.log(`⚖️ Current Difficulty Multiplier: ${difficultyMultiplier.toFixed(1)}x`);
  
  // Phoenix health scaling
  const basePhoenixHealth = phoenixWaveConfig.phoenixHealth;
  const scaledPhoenixHealth = Math.floor(basePhoenixHealth * (1 + (difficultyMultiplier - 1) * 0.3));
  console.log(`🔥 Phoenix Health: ${basePhoenixHealth} → ${scaledPhoenixHealth} HP`);
  
  // Game progression stage
  let gameStage = 'Early Game';
  if (waveNumber >= 25) gameStage = 'Mid Game';
  if (waveNumber >= 100) gameStage = 'Late Game';
  if (waveNumber >= 500) gameStage = 'End Game';
  if (waveNumber >= 1000) gameStage = 'Legendary';
  
  console.log(`🎯 Game Stage: ${gameStage} (Waves 1-25: Early, 25-100: Mid, 100-500: Late, 500-1000: End, 1000+: Legendary)`);
  
  // Balance recommendations
  console.log(`💡 Balance Recommendations:`);
  if (waveNumber < 10) {
    console.log(`   - Focus on learning basic mechanics`);
    console.log(`   - Phoenix waves every 5 waves (gentle introduction)`);
    console.log(`   - Boss wave at Wave 10 (Cheese King)`);
  } else if (waveNumber < 25) {
    console.log(`   - Steady difficulty increase`);
    console.log(`   - Phoenix waves becoming more challenging`);
    console.log(`   - Prepare for Wave 25 boss (Cheese Emperor)`);
  } else if (waveNumber < 75) {
    console.log(`   - Expert challenge level`);
    console.log(`   - Phoenix waves with complex formations`);
    console.log(`   - Prepare for Wave 75 boss (Cheese God)`);
  } else if (waveNumber < 100) {
    console.log(`   - Master level challenge`);
    console.log(`   - Phoenix waves with maximum difficulty`);
    console.log(`   - Prepare for Wave 100 boss (Cheese Destroyer)`);
  } else {
    console.log(`   - Legendary challenge level`);
    console.log(`   - Phoenix waves beyond normal difficulty`);
    console.log(`   - Endless progression to Wave 1000+`);
  }
  
  return 'Game balance analysis complete';
};

// 🧪 TESTING: Check boss bullets
window.checkBossBullets = function() {
  console.log('🧀 Boss Bullets Status:');
  console.log('Total boss bullets:', bossBullets.length);
  
  if (bossBullets.length > 0) {
    const bulletTypes = {};
    bossBullets.forEach(bullet => {
      const type = bullet.type || 'normal';
      bulletTypes[type] = (bulletTypes[type] || 0) + 1;
    });
    
    console.log('Bullet breakdown by type:', bulletTypes);
    console.log('Sample bullet:', bossBullets[0]);
    
    // Show most dangerous bullets
    const dangerousBullets = bossBullets.filter(b => b.damage > 3);
    if (dangerousBullets.length > 0) {
      console.log(`⚠️ ${dangerousBullets.length} high-damage bullets (>3 damage) detected!`);
    }
  }
  
  return bossBullets;
};

// 🧪 TESTING: Force boss attack for testing
window.forceBossAttack = function(patternIndex = -1) {
  if (!boss || bossDefeated) {
    console.log('❌ No active boss to attack with!');
    return false;
  }
  
  if (patternIndex >= 0) {
    bossAttackPattern = patternIndex;
    console.log(`🧀 Forcing boss attack pattern ${patternIndex}`);
  }
  
  bossAttack();
  console.log(`✅ Boss attack executed! New bullet count: ${bossBullets.length}`);
  return true;
};

// 🧪 TESTING: Validate boss configuration system
window.testBossConfigs = function() {
  console.log('🧪 Testing boss configuration system...');
  
  const bossTypes = ['cheeseKing', 'cheeseEmperor', 'cheeseGod', 'cheeseDestroyer'];
  let allConfigsValid = true;
  
  bossTypes.forEach(bossType => {
    try {
      const config = getBossConfiguration(bossType);
      if (config) {
        console.log(`✅ ${bossType} config loaded:`, {
          name: config.name,
          health: config.baseHealth,
          damage: config.baseBulletDamage,
          abilities: config.abilities
        });
      } else {
        console.error(`❌ ${bossType} config failed to load`);
        allConfigsValid = false;
      }
    } catch (error) {
      console.error(`❌ Error loading ${bossType} config:`, error);
      allConfigsValid = false;
    }
  });
  
  if (allConfigsValid) {
    console.log('✅ All boss configurations are valid!');
  } else {
    console.error('❌ Some boss configurations have issues!');
  }
  
  return allConfigsValid;
};

// 🧪 TESTING: Test collision detection system
window.testCollisions = function() {
  console.log('🧪 Testing collision detection system...');
  
  console.log('Player ship:', {
    x: playerShip.x,
    y: playerShip.y,
    width: playerShip.width,
    height: playerShip.height,
    health: playerShip.health,
    invincible: playerShip.invincible,
    invincibleTimer: playerShip.invincibleTimer
  });
  
  if (boss) {
    console.log('Boss:', {
      x: boss.x,
      y: boss.y,
      width: boss.width,
      height: boss.height,
      health: boss.health,
      defeated: bossDefeated
    });
  } else {
    console.log('No boss active');
  }
  
  console.log('Player bullets:', bullets.length);
  console.log('Boss bullets:', bossBullets.length);
  
  // Test a collision manually
  if (boss && bullets.length > 0) {
    const testBullet = bullets[0];
    const collision = checkCollision(testBullet, boss);
    console.log('Test collision (first bullet vs boss):', collision);
    console.log('Bullet position:', {x: testBullet.x, y: testBullet.y, width: testBullet.width, height: testBullet.height});
  }
  
  if (bossBullets.length > 0) {
    const testBossBullet = bossBullets[0];
    const collision = checkCollision(playerShip, testBossBullet);
    console.log('Test collision (player vs first boss bullet):', collision);
    console.log('Boss bullet position:', {x: testBossBullet.x, y: testBossBullet.y, width: testBossBullet.width, height: testBossBullet.height});
  }
};

// 🧪 TESTING: Remove player invincibility for testing
window.removePlayerInvincibility = function() {
  playerShip.invincible = false;
  playerShip.invincibleTimer = 0;
  console.log('✅ Player invincibility removed - ready for damage testing!');
};

// 🧪 TESTING: Set player health for testing
window.setPlayerHealth = function(health) {
  playerShip.health = health;
  console.log(`✅ Player health set to ${health}`);
};

// 🧪 TESTING: Create a test bullet directly at player position
window.createTestBullet = function() {
  if (!boss) {
    console.log('❌ No boss active! Spawn a boss first.');
    return;
  }
  
  // Create a simple bullet right at the player position for testing
  const testBullet = {
    x: playerShip.x,
    y: playerShip.y - 50, // Start above player
    width: 10,
    height: 10,
    speed: 2,
    damage: 1,
    color: '#ff0000',
    type: 'test_bullet'
  };
  
  bossBullets.push(testBullet);
  console.log(`✅ Test bullet created at (${testBullet.x}, ${testBullet.y}) - should hit player in ~25 frames`);
  console.log(`🎯 Player is at (${playerShip.x}, ${playerShip.y})`);
  
  return testBullet;
};

// 🧪 TESTING: Clear all boss bullets
window.clearBossBullets = function() {
  const count = bossBullets.length;
  bossBullets.length = 0;
  console.log(`✅ Cleared ${count} boss bullets`);
};

// 🚨 EMERGENCY: Force boss damage test
window.emergencyDamageTest = function() {
  console.log(`🚨 EMERGENCY DAMAGE TEST - Current player health: ${playerShip.health}`);
  
  // Force remove invincibility
  playerShip.invincible = false;
  playerShip.invincibleTimer = 0;
  
  // Manually damage player
  const oldHealth = playerShip.health;
  playerShip.health -= 5;
  
  console.log(`💥 MANUAL DAMAGE: ${oldHealth} -> ${playerShip.health}`);
  
  // Create explosion effect
  explosions.push({
    x: playerShip.x + playerShip.width / 2,
    y: playerShip.y + playerShip.height / 2,
    size: 30,
    timer: 15
  });
  
  return `Health changed from ${oldHealth} to ${playerShip.health}`;
};

// 🚨 EMERGENCY: Force collision check
window.emergencyCollisionCheck = function() {
  console.log(`🚨 EMERGENCY COLLISION CHECK`);
  console.log(`Game phase: ${gamePhase}`);
  console.log(`Boss exists: ${!!boss}`);
  console.log(`Boss bullets: ${bossBullets?.length || 0}`);
  console.log(`Player position: (${playerShip.x}, ${playerShip.y})`);
  console.log(`Player invincible: ${playerShip.invincible}, timer: ${playerShip.invincibleTimer}`);
  
  if (bossBullets && bossBullets.length > 0) {
    console.log(`🔍 First 3 bullets:`);
    for (let i = 0; i < Math.min(3, bossBullets.length); i++) {
      const bullet = bossBullets[i];
      console.log(`  Bullet ${i}: (${Math.round(bullet.x)}, ${Math.round(bullet.y)}) type: ${bullet.type || 'normal'}`);
      
      // Check collision manually
      const collision = checkCollision(playerShip, bullet);
      console.log(`  Collision with player: ${collision}`);
    }
  }
  
  // Try calling checkPlayerHit manually
  console.log(`🧪 Manually calling checkPlayerHit()...`);
  checkPlayerHit();
};

// 🚀 NEW: Add missing functions that were referenced
  function updateScore() {
    const scoreDisplay = document.getElementById("space-invaders-score");
    if (scoreDisplay) {
      // 🚀 CRITICAL FIX: Space Invaders scoring: Use SAME calculation as saveScore for consistency
      // Based on screenshot: 2185 invaders = 21.85 DSPOINC, so 100 invaders = 1 DSPOINC
      const dspoinEarned = Math.round((spaceInvadersScore * 0.01) * 100) / 100; // Round to 2 decimal places (100 invaders = 1 DSPOINC)
      
      // Add mouse control indicator
      const mouseIndicator = isMouseControlEnabled && isMouseOverCanvas ? '🖱️' : '⌨️';
      scoreDisplay.textContent = `💰 Space Invaders Score: ${spaceInvadersScore.toLocaleString()} invaders destroyed (${dspoinEarned} DSPOINC) ${mouseIndicator}`;
    } else {
      console.warn('⚠️ Score display element not found');
    }
  }

  function onGameWin() {
    clearInterval(spaceInvadersGameInterval);
    
    const winModal = document.getElementById("space-invaders-win-modal");
    const winScoreText = document.getElementById("space-invaders-win-score-text");
    
    // 🚀 CRITICAL FIX: Space Invaders scoring: Use SAME calculation as saveScore for consistency
    // Based on screenshot: 2185 invaders = 21.85 DSPOINC, so 100 invaders = 1 DSPOINC
    const dspoinEarned = Math.round((spaceInvadersScore * 0.01) * 100) / 100; // Round to 2 decimal places (100 invaders = 1 DSPOINC)
    
    if (winModal && winScoreText) {
      winScoreText.textContent = `You earned ${dspoinEarned} DSPOINC! (${spaceInvadersScore.toLocaleString()} invaders destroyed)`;
      winModal.classList.remove("hidden");
    }
    
    // Save score to database
    saveScore(spaceInvadersScore); // 🚀 CRITICAL FIX: Save traditional score instead of invader count
    cleanupSpaceInvadersControls();

    // Dispatch game end event for UI reset
    window.dispatchEvent(new Event('spaceInvadersGameEnd'));
  }

  function saveScore(traditionalScore) {
    const discordId = localStorage.getItem('discord_id');
    const discordName = localStorage.getItem('discord_name') || 'Unknown Player';
    const wallet = localStorage.getItem('user_wallet') || discordId;
    
    if (!discordId) {
      console.error('No Discord ID found for score saving');
      return;
    }

    // 🚀 CRITICAL FIX: Space Invaders now saves traditional score (like classic Space Invaders)
    // Based on screenshot: 2185 invaders = 21.85 DSPOINC, so 100 invaders = 1 DSPOINC
    const dspoincScore = Math.round((traditionalScore * 0.01) * 100) / 100; // Convert to DSPOINC (100 invaders = 1 DSPOINC)

    console.log(`💾 Saving Space Invaders score: ${traditionalScore} invaders destroyed = ${dspoincScore} DSPOINC`);

    // 🌍 Environment-aware API endpoint (works both locally and in production)
    const apiUrl = `${API_BASE_URL}/api/dev/save-score.php`;
    
    console.log(`🌍 Environment: ${window.location.hostname === 'narrrfs.world' ? 'Production' : 'Local'}`);
    console.log(`🔗 API URL: ${apiUrl}`);
    console.log(`🔑 Discord ID: ${discordId}`);
    console.log(`👤 Discord Name: ${discordName}`);
    console.log(`💰 Wallet: ${wallet}`);

    // Save to the same API endpoint as Tetris and Snake
    fetch(apiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        wallet: wallet,
        score: traditionalScore, // 🚀 CRITICAL FIX: Raw traditional score (like classic Space Invaders)
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
      console.log(`📨 Server response:`, data);
      if (data.success) {
        console.log(`✅ Space Invaders score saved successfully: ${traditionalScore} invaders destroyed = ${dspoincScore} DSPOINC`);
        console.log(`🎯 Score ID: ${data.score_id || 'N/A'}`);
        console.log(`📊 Database confirmation: ${data.message || 'Score recorded'}`);
      } else {
        if (data.local_test) {
          console.log(`🔄 Local testing detected - score would be saved in production: ${traditionalScore} invaders destroyed = ${dspoincScore} DSPOINC`);
        } else {
          console.error('❌ Failed to save Space Invaders score:', data.error || 'Unknown error');
          console.error('❌ Error details:', data);
        }
      }
    })
    .catch(error => {
      console.error('❌ Error saving Space Invaders score:', error.message);
      // Check if this is a local testing issue
      if (error.message.includes('HTML') || error.message.includes('fetch')) {
        console.log('🔄 Local testing detected - score saving disabled for local development');
        console.log(`📊 Score would be saved in production: ${traditionalScore} invaders destroyed = ${dspoincScore} DSPOINC`);
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

  function handleTouchStart(e) {
    if (e.target.closest("#space-invaders-canvas")) {
      e.preventDefault();
      e.stopPropagation();
      const touch = e.touches[0];
      touchStartX = touch.clientX;
      touchStartY = touch.clientY;
      isTouching = true;
      
      // 🚀 NEW: Immediate ship positioning on touch start
      const canvas = document.getElementById('space-invaders-canvas');
      if (canvas) {
        const rect = canvas.getBoundingClientRect();
        const touchX = touch.clientX - rect.left;
        const touchY = touch.clientY - rect.top;
        
        // Store old position for comparison
        const oldX = playerShip.x;
        const oldY = playerShip.y;
        
        // 🚀 CRITICAL FIX: Position ship immediately at touch location
        const targetX = touchX - playerShip.width / 2;
        const targetY = touchY - playerShip.height / 2;
        
        // Apply boundary constraints
        const constrainedX = Math.max(0, Math.min(canvasWidth - playerShip.width, targetX));
        const extendedBottomBoundary = canvasHeight + 20;
        const constrainedY = Math.max(0, Math.min(extendedBottomBoundary, targetY));
        
        // 🚀 CRITICAL FIX: Instant ship positioning for immediate response
        playerShip.x = constrainedX;
        playerShip.y = constrainedY;
        
        // 🚀 NEW: Force immediate visual update
        if (playerShip.x !== oldX || playerShip.y !== oldY) {
          console.log('📱 Ship moved instantly to:', playerShip.x, playerShip.y);
        }
        
        console.log('📱 Touch start - Ship positioned at:', playerShip.x, playerShip.y);
      }
      
      // Store touch start time for tap detection
      touchStartTime = Date.now();
      
      // 🔥 CHECK FOR OVERHEATING
      if (isOverheated) {
        console.log('🔥 Weapon is overheated! Wait for cooldown...');
        return; // Can't shoot when overheated
      }
      
      // 🚀 NEW: Start continuous shooting while holding (smart weapon handling)
      if (holdShootInterval) {
        clearInterval(holdShootInterval);
      }
      
      // Start rapid fire shooting based on weapon type
      holdShootInterval = setInterval(() => {
        if (isTouching && !isSpaceInvadersPaused && !isOverheated) {
          // Smart shooting based on weapon type
          if (currentWeaponType === 'normal') {
            // Normal weapon: shoot continuously (infinite ammo)
            playerShoot();
            addHeat(getHeatPerShot()); // Add heat for each shot
          }
          // Special weapons (laser/bomb) can ONLY be fired through Quick Shot button
          // No automatic shooting through hold-to-shoot for special weapons
        }
      }, holdShootDelay);
      
      // First immediate shot (smart weapon handling)
      setTimeout(() => {
        if (isTouching && !isSpaceInvadersPaused && !isOverheated) {
          if (currentWeaponType === 'normal') {
            playerShoot();
            addHeat(getHeatPerShot()); // Add heat for first shot
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
      
      // 🚀 CRITICAL FIX: Direct ship positioning for mobile
      const canvas = document.getElementById('space-invaders-canvas');
      if (canvas) {
        const rect = canvas.getBoundingClientRect();
        const touchX = touch.clientX - rect.left;
        const touchY = touch.clientY - rect.top;
        
        // 🚀 NEW: Direct ship positioning (like mouse controls)
        // Convert touch position to ship center position
        const targetX = touchX - playerShip.width / 2;
        const targetY = touchY - playerShip.height / 2;
        
        // 🚀 ENHANCED: Smooth ship movement with boundary constraints
        const oldX = playerShip.x;
        const oldY = playerShip.y;
        
        // Apply boundary constraints
        const constrainedX = Math.max(0, Math.min(canvasWidth - playerShip.width, targetX));
        const extendedBottomBoundary = canvasHeight + 20; // Allow 20px beyond canvas bottom
        const constrainedY = Math.max(0, Math.min(extendedBottomBoundary, targetY));
        
        // 🚀 CRITICAL FIX: Direct positioning for mobile responsiveness
        // Mobile players need instant, direct control - no easing!
        playerShip.x = constrainedX;
        playerShip.y = constrainedY;
        
        // 🔥 CRITICAL FIX: Auto-shoot when ship moves (if enabled) - NO HEAT BUILDUP
        if (autoShootEnabled && (oldX !== playerShip.x || oldY !== playerShip.y)) {
          const currentTime = Date.now();
          
          // Check if enough time has passed since last auto-shoot
          if (currentTime - lastAutoShootTime >= autoShootFiringRate) {
            // 🔥 FIX: Better movement detection to prevent blinking
            const movementThreshold = 2; // Minimum movement to trigger auto-shoot
            const deltaX = Math.abs(playerShip.x - oldX);
            const deltaY = Math.abs(playerShip.y - oldY);
            
            if (deltaX > movementThreshold || deltaY > movementThreshold) {
              // 🔥 FIX: Use more precise position tracking
              const currentPos = { 
                x: Math.round(playerShip.x / 5), // Smaller grid for more responsive shooting
                y: Math.round(playerShip.y / 5) 
              };
              
              if (currentPos.x !== lastMovementPosition.x || currentPos.y !== lastMovementPosition.y) {
                autoShoot(); // Use special auto-shoot function that doesn't add heat
                lastMovementPosition = currentPos; // Update last position
                console.log('🚀 Auto-shoot triggered by movement:', deltaX.toFixed(1), deltaY.toFixed(1));
              }
            }
          }
        }
        
        console.log('📱 Touch move - Ship position updated:', playerShip.x, playerShip.y);
      }
      
      // 🚀 ENHANCED: Swipe gestures for additional actions
      const deltaX = touch.clientX - touchStartX;
      const deltaY = touch.clientY - touchStartY;
      
      // Quick swipe up to shoot (like modern mobile games)
      if (deltaY < -15) {
        if (currentWeaponType === 'normal' && !isOverheated) {
          playerShoot();
          addHeat(getHeatPerShot()); // Add heat for swipe shot
        }
        // Special weapons (laser/bomb) can ONLY be fired through Quick Shot button
        // Reset touch to prevent multiple shots
        touchStartY = touch.clientY;
      }
      
      // Quick swipe down for special action (bomb only)
      if (deltaY > 25) {
        // Swipe down only works with bomb weapon and available ammo
        if (currentWeaponType === 'bomb' && weaponAmmo.bomb > 0 && !isOverheated) {
          playerShoot(); // This will use bomb if selected
          addHeat(getHeatPerShot()); // Add heat for bomb shot
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
      
      // 🚀 CRITICAL FIX: Prevent mouse controls from interfering immediately after touch
      // Add a small delay before re-enabling mouse controls
      setTimeout(() => {
        isTouching = false;
        console.log('📱 Touch ended - mouse controls re-enabled');
      }, 100); // 100ms delay to prevent conflicts
    }
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
        <p><strong>🖱️ Mouse Control:</strong> Move mouse to control ship (smooth movement)</p>
        <p><strong>Mobile:</strong> Swipe or tap directional buttons</p>
      </div>
      
      <div style="text-align: left; margin-bottom: 20px;">
        <h2 style="color: #fbbf24; border-bottom: 1px solid #fbbf24; padding-bottom: 5px;">🔫 WEAPON SYSTEM</h2>
        <p><strong>1 Key:</strong> Normal Cheese Bullets (Unlimited)</p>
        <p><strong>2 Key:</strong> Laser Beam (5 ammo, pierces enemies)</p>
        <p><strong>3 Key:</strong> Bomb (3 ammo, clears screen)</p>
        <p><strong>Space Bar:</strong> Shoot current weapon</p>
        <p><strong>🖱️ Mouse Click:</strong> Shoot current weapon</p>
        <p><strong>🖱️ Right-Click:</strong> Switch weapons (cycle through)</p>
        <p><strong>Mobile:</strong> Tap shoot button or swipe up</p>
      </div>
      
      <div style="text-align: left; margin-bottom: 20px;">
        <h2 style="color: #fbbf24; border-bottom: 1px solid #fbbf24; padding-bottom: 5px;">🔥 HEAT SYSTEM</h2>
        <p><strong>Heat Display:</strong> Always visible in top-right corner during gameplay</p>
        <p><strong>Heat Build-up:</strong> Each shot adds heat to your weapon</p>
        <p><strong>Overheat:</strong> Weapon stops working when heat reaches 100%</p>
        <p><strong>Cooling:</strong> Heat naturally decreases when not shooting</p>
        <p><strong>Strategy:</strong> Switch weapons or pause shooting to manage heat</p>
        <p><strong>Toggle Display:</strong> Ctrl+H to show/hide heat display</p>
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
        <p><strong>M Key:</strong> Toggle Mouse/Keyboard control</p>
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
      
      <div style="text-align: left; margin-bottom: 20px;">
        <h2 style="color: #fbbf24; border-bottom: 1px solid #fbbf24; padding-bottom: 5px;">🔊 SOUND CONTROLS</h2>
        <p><strong>M Key:</strong> Toggle sound on/off</p>
        <p><strong>V Key:</strong> Cycle through volume levels (30%, 50%, 70%, 100%)</p>
        <p><strong>Game Panel:</strong> Access sound controls via 🎮 button</p>
        <p><strong>Test Sound:</strong> Click volume button to hear Star Wars laser!</p>
      </div>
      
      <div style="text-align: center; margin-top: 30px;">
        <button id="test-sound-btn" style="
          background: #8b5cf6;
          color: white;
          border: none;
          padding: 15px 30px;
          border-radius: 25px;
          font-size: 1.2em;
          font-weight: bold;
          cursor: pointer;
          transition: all 0.3s ease;
          margin-right: 15px;
        " onmouseover="this.style.background='#7c3aed'" onmouseout="this.style.background='#8b5cf6'">
          🔊 TEST STAR WARS LASER!
        </button>
        
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
    
    // Add test sound button functionality
    document.getElementById('test-sound-btn').addEventListener('click', () => {
      cheeseSoundManager.playStarWarsLaser();
    });
    
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
          // 🚀 FIX: Immediate weapon switching with visual feedback
          const previousWeapon = currentWeaponType;
          switchWeapon(weapon.type);
          
          // 🚀 FIX: Instant visual update for better responsiveness
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
          
          // 🚀 FIX: Update ammo display immediately
          updateWeaponAmmoDisplay();
          
          // 🚀 FIX: Show weapon switch confirmation
          console.log(`🔫 Weapon switched from ${previousWeapon} to ${weapon.type}`);
          
          // 🚀 FIX: Longer delay for better user experience
          setTimeout(() => {
            toggleGamePanel();
          }, 800); // Increased from 300ms to 800ms
        }
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
    
    // 🔥 NEW: Weapon Heat System section for mobile
    const heatSection = document.createElement('div');
    heatSection.style.cssText = `
      margin-bottom: 25px;
      text-align: center;
    `;
    
    const heatTitle = document.createElement('h3');
    heatTitle.textContent = '🔥 WEAPON HEAT';
    heatTitle.style.cssText = `
      color: #ffffff;
      margin: 0 0 15px 0;
      font-size: 1.2em;
    `;
    heatSection.appendChild(heatTitle);
    
    // Heat bar container
    const heatBarContainer = document.createElement('div');
    heatBarContainer.style.cssText = `
      background: #1f2937;
      border: 2px solid #374151;
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 15px;
    `;
    
    // Heat status text
    const heatStatusText = document.createElement('div');
    heatStatusText.id = 'mobile-heat-status';
    heatStatusText.style.cssText = `
      color: #ffffff;
      font-size: 1.1em;
      font-weight: bold;
      margin-bottom: 15px;
      text-align: center;
    `;
    heatBarContainer.appendChild(heatStatusText);
    
    // Heat bar
    const heatBar = document.createElement('div');
    heatBar.id = 'mobile-heat-bar';
    heatBar.style.cssText = `
      width: 100%;
      height: 20px;
      background: #374151;
      border-radius: 10px;
      overflow: hidden;
      position: relative;
      margin-bottom: 10px;
    `;
    
    // Heat bar fill
    const heatBarFill = document.createElement('div');
    heatBarFill.id = 'mobile-heat-bar-fill';
    heatBarFill.style.cssText = `
      height: 100%;
      background: linear-gradient(90deg, #00ff00, #ffcc00, #ff8800, #ff0000);
      width: 0%;
      transition: width 0.3s ease;
      border-radius: 10px;
    `;
    
    heatBar.appendChild(heatBarFill);
    heatBarContainer.appendChild(heatBar);
    
    // Heat percentage
    const heatPercentage = document.createElement('div');
    heatPercentage.id = 'mobile-heat-percentage';
    heatPercentage.style.cssText = `
      color: #ffffff;
      font-size: 1em;
      text-align: center;
      font-weight: bold;
    `;
    heatBarContainer.appendChild(heatPercentage);
    
    // Overheat warning
    const overheatWarning = document.createElement('div');
    overheatWarning.id = 'mobile-overheat-warning';
    overheatWarning.style.cssText = `
      color: #ff6b6b;
      font-size: 0.9em;
      text-align: center;
      margin-top: 10px;
      font-weight: bold;
      display: none;
    `;
    heatBarContainer.appendChild(overheatWarning);
    
    heatSection.appendChild(heatBarContainer);
    panelContent.appendChild(heatSection);
    
    // 🎵 NEW: Sound controls section
    const soundSection = document.createElement('div');
    soundSection.style.cssText = `
      margin-bottom: 25px;
      text-align: center;
    `;
    
    const soundTitle = document.createElement('h3');
    soundTitle.textContent = '🔊 SOUND CONTROLS';
    soundTitle.style.cssText = `
      color: #ffffff;
      margin: 0 0 15px 0;
      font-size: 1.2em;
    `;
    soundSection.appendChild(soundTitle);
    
    const soundGrid = document.createElement('div');
    soundGrid.style.cssText = `
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 15px;
      margin-bottom: 15px;
    `;
    
    // Sound toggle button
    const soundToggleBtn = document.createElement('button');
    soundToggleBtn.innerHTML = `
      <div style="font-size: 1.1em; margin-bottom: 5px;">🔊</div>
      <div style="font-size: 0.9em; margin-bottom: 3px;">SOUND</div>
      <div style="font-size: 0.8em; color: #9ca3af;">${cheeseSoundManager.soundEnabled ? 'ON' : 'OFF'}</div>
    `;
    soundToggleBtn.style.cssText = `
      background: ${cheeseSoundManager.soundEnabled ? '#10b981' : '#6b7280'};
      color: white;
      border: 2px solid ${cheeseSoundManager.soundEnabled ? '#059669' : '#6b7280'};
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
    
    soundToggleBtn.addEventListener('click', function() {
      cheeseSoundManager.toggleSound();
      // Update button appearance
      soundToggleBtn.style.background = cheeseSoundManager.soundEnabled ? '#10b981' : '#6b7280';
      soundToggleBtn.style.borderColor = cheeseSoundManager.soundEnabled ? '#059669' : '#6b7280';
      soundToggleBtn.querySelector('div:last-child').textContent = cheeseSoundManager.soundEnabled ? 'ON' : 'OFF';
      
      // Play test sound if enabled
      if (cheeseSoundManager.soundEnabled) {
        cheeseSoundManager.playStarWarsLaser();
      }
    });
    
    // Volume control button
    const volumeBtn = document.createElement('button');
    volumeBtn.innerHTML = `
      <div style="font-size: 1.1em; margin-bottom: 5px;">🎚️</div>
      <div style="font-size: 0.9em; margin-bottom: 3px;">VOLUME</div>
      <div style="font-size: 0.8em; color: #9ca3af;">${Math.round(cheeseSoundManager.masterVolume * 100)}%</div>
    `;
    volumeBtn.style.cssText = `
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
    
    volumeBtn.addEventListener('click', function() {
      // Cycle through volume levels: 30% -> 50% -> 70% -> 100% -> 30%
      const volumes = [0.3, 0.5, 0.7, 1.0];
      const currentIndex = volumes.indexOf(cheeseSoundManager.masterVolume);
      const nextIndex = (currentIndex + 1) % volumes.length;
      const newVolume = volumes[nextIndex];
      
      cheeseSoundManager.setVolume(newVolume);
      volumeBtn.querySelector('div:last-child').textContent = `${Math.round(newVolume * 100)}%`;
      
      // Play test sound at new volume
      cheeseSoundManager.playStarWarsLaser();
    });
    
    soundGrid.appendChild(soundToggleBtn);
    soundGrid.appendChild(volumeBtn);
    soundSection.appendChild(soundGrid);
    panelContent.appendChild(soundSection);
    
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
      <p><strong>🚀 NEW: Smart Quick Shoot Button!</strong></p>
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
    
    // 🔥 NEW: Initialize mobile heat display
    setTimeout(() => {
      updateAlwaysVisibleHeatDisplay();
    }, 100);
    
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

  // 🔥 CRITICAL FIX: Phoenix-Player collision detection
  function checkPhoenixPlayerCollisions() {
    if (!playerShip || playerShip.invincible) {
      return; // Player is invincible or doesn't exist
    }
    
    try {
      // Check Phoenix bird collisions
      if (phoenixWaves && Array.isArray(phoenixWaves)) {
        phoenixWaves.forEach((phoenix, index) => {
          if (phoenix && typeof phoenix === 'object' && !phoenix.isDead && checkCollision(phoenix, playerShip)) {
            // Player hit by Phoenix bird!
            const damage = (phoenix && typeof phoenix.damage === 'number') ? phoenix.damage : 1;
            playerShip.health -= damage;
            console.log(`🔥 Phoenix bird ${index} inflicted ${damage} damage! Player health: ${playerShip.health + damage} -> ${playerShip.health}`);
            
            // Create explosion effect
            createExplosion(playerShip.x + playerShip.width / 2, playerShip.y + playerShip.height / 2, 30);
            
            // Screen shake effect
            if (window.screenShake) {
              window.screenShake(10, 200);
            }
            
            // Sound effect
            if (window.playSound) {
              window.playSound('explosion');
            }
            
            // Check if player is defeated
            if (playerShip.health <= 0) {
              onGameOver();
            }
          }
        });
      }
      
      // Check Phoenix egg collisions
      if (phoenixEggs && Array.isArray(phoenixEggs)) {
        phoenixEggs.forEach((egg, index) => {
          if (egg && typeof egg === 'object' && !egg.isDead && checkCollision(egg, playerShip)) {
            // Player hit by Phoenix egg!
            const damage = 1; // Eggs do minimal damage
            playerShip.health -= damage;
            console.log(`🥚 Phoenix egg ${index} inflicted ${damage} damage! Player health: ${playerShip.health + damage} -> ${playerShip.health}`);
            
            // Create explosion effect
            createExplosion(playerShip.x + playerShip.width / 2, playerShip.y + playerShip.height / 2, 25);
            
            // Screen shake effect
            if (window.screenShake) {
              window.screenShake(8, 150);
            }
            
            // Check if player is defeated
            if (playerShip.health <= 0) {
              onGameOver();
            }
          }
        });
      }
      
      // Check mini-Phoenix collisions
      if (miniPhoenixes && Array.isArray(miniPhoenixes)) {
        miniPhoenixes.forEach((mini, index) => {
          if (mini && typeof mini === 'object' && !mini.isDead && checkCollision(mini, playerShip)) {
            // Player hit by mini-Phoenix!
            const damage = 1; // Mini-Phoenixes do minimal damage
            playerShip.health -= damage;
            console.log(`🐤 Mini-Phoenix ${index} inflicted ${damage} damage! Player health: ${playerShip.health + damage} -> ${playerShip.health}`);
            
            // Create explosion effect
            createExplosion(playerShip.x + playerShip.width / 2, playerShip.y + playerShip.height / 2, 20);
            
            // Screen shake effect
            if (window.screenShake) {
              window.screenShake(5, 100);
            }
            
            // Check if player is defeated
            if (playerShip.health <= 0) {
              onGameOver();
            }
          }
        });
      }
    } catch (error) {
      console.error('🔥 Error in Phoenix collision detection:', error);
    }
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