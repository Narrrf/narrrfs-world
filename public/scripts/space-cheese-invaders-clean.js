// 🧀 SPACE CHEESE INVADERS - CLEAN VERSION WITH ROLE-BASED SYSTEM
// Professional Gaming Platform - Narrrfs World

// 🎯 GAME VARIABLES
let spaceInvadersScore = 0;
let spaceInvadersCount = 0;
let spaceInvadersGameInterval = null;
let gameStarted = false;
let isSpaceInvadersPaused = false;

// 🏆 ROLE-BASED SYSTEM
let primaryRole = 'None';
let roleMultiplier = 1.0;
let roleTheme = 'default';

// Role multipliers for Space Invaders
let spaceInvadersRoleMultipliers = {
  'VIP Holder': 2.0,
  'Holder': 1.5,
  'Season Tester': 1.3,
  'Early Bird': 1.2,
  'Champion': 1.4,
  'Cheese Hunter': 1.1
};

// Role themes for Space Invaders
let spaceInvadersRoleThemes = {
  'VIP Holder': 'golden',
  'Holder': 'silver',
  'Cheese Hunter': 'cheese',
  'Season Tester': 'rainbow',
  'Early Bird': 'blue',
  'Champion': 'red'
};

// 🎮 GAME CANVAS SETUP
const canvas = document.getElementById('space-invaders-canvas');
const ctx = canvas.getContext('2d');

// 🚀 INITIALIZE GAME
async function initializeSpaceInvaders() {
  console.log('🚀 Initializing Space Invaders...');
  
  // Fetch user roles
  await fetchSpaceInvadersUserRoles();
  
  // Update score display
  updateSpaceInvadersScoreDisplay();
  
  // Setup event listeners
  setupEventListeners();
  
  console.log('✅ Space Invaders initialized successfully');
}

// 🏆 FETCH USER ROLES
async function fetchSpaceInvadersUserRoles() {
  try {
    const discordId = localStorage.getItem("discord_id");
    
    if (!discordId) {
      // Local development fallback
      primaryRole = 'VIP Holder';
      roleMultiplier = spaceInvadersRoleMultipliers[primaryRole];
      roleTheme = spaceInvadersRoleThemes[primaryRole];
      console.log('🏆 Local development: Using VIP Holder role');
      return;
    }
    
    // Production API call
    const response = await fetch(`${API_BASE_URL}/api/user/get-user-roles.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ discord_id: discordId })
    });
    
    const data = await response.json();
    
    if (data.success && data.roles && data.roles.length > 0) {
      primaryRole = data.roles[0];
      roleMultiplier = spaceInvadersRoleMultipliers[primaryRole] || 1.0;
      roleTheme = spaceInvadersRoleThemes[primaryRole] || 'default';
      console.log(`🏆 User role: ${primaryRole}, Multiplier: ${roleMultiplier}x, Theme: ${roleTheme}`);
    } else {
      primaryRole = 'None';
      roleMultiplier = 1.0;
      roleTheme = 'default';
      console.log('🏆 No roles found, using default settings');
    }
  } catch (error) {
    console.error('❌ Error fetching user roles:', error);
    primaryRole = 'None';
    roleMultiplier = 1.0;
    roleTheme = 'default';
  }
}

// 💰 UPDATE SCORE DISPLAY
function updateSpaceInvadersScoreDisplay() {
  const scoreElement = document.getElementById('space-invaders-top-score');
  const multiplierElement = document.getElementById('space-invaders-role-multiplier');
  
  if (scoreElement) {
    const totalDSPOINC = spaceInvadersScore * roleMultiplier;
    scoreElement.textContent = `💰 Score: $${totalDSPOINC.toFixed(4)} DSPOINC`;
  }
  
  if (multiplierElement) {
    if (roleMultiplier > 1.0) {
      multiplierElement.textContent = `Role Bonus: ${roleMultiplier}x (${primaryRole})`;
      multiplierElement.style.color = '#fbbf24';
    } else {
      multiplierElement.textContent = 'Role Bonus: 1.0x';
      multiplierElement.style.color = '#9ca3af';
    }
  }
  
  // Apply role theme to canvas
  if (canvas && roleTheme !== 'default') {
    canvas.className = `game-canvas role-${roleTheme}`;
  }
}

// 🎮 SETUP EVENT LISTENERS
function setupEventListeners() {
  const startBtn = document.getElementById("start-space-invaders-btn");
  const pauseBtn = document.getElementById("pause-space-invaders-btn");
  
  if (startBtn) {
    startBtn.addEventListener("click", startGame);
  }
  
  if (pauseBtn) {
    pauseBtn.addEventListener("click", togglePause);
  }
}

// 🚀 START GAME
async function startGame() {
  console.log('🚀 Starting Space Invaders game...');
  
  gameStarted = true;
  isSpaceInvadersPaused = false;
  
  // Update button text
  const startBtn = document.getElementById("start-space-invaders-btn");
  if (startBtn) {
    startBtn.textContent = "🔄 Restart";
  }
  
  // Start game loop
  spaceInvadersGameInterval = setInterval(gameLoop, 100);
  
  console.log('✅ Space Invaders game started');
}

// ⏸️ TOGGLE PAUSE
function togglePause() {
  isSpaceInvadersPaused = !isSpaceInvadersPaused;
  
  const pauseBtn = document.getElementById("pause-space-invaders-btn");
  if (pauseBtn) {
    pauseBtn.textContent = isSpaceInvadersPaused ? "▶️ Resume" : "⏸️ Pause";
  }
  
  console.log(`⏸️ Game ${isSpaceInvadersPaused ? 'paused' : 'resumed'}`);
}

// 🎮 GAME LOOP
function gameLoop() {
  if (isSpaceInvadersPaused) return;
  
  // Clear canvas
  ctx.fillStyle = '#000000';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  
  // Draw stars
  drawStars();
  
  // Draw score
  ctx.fillStyle = '#ffffff';
  ctx.font = '20px Arial';
  ctx.fillText(`Score: ${spaceInvadersScore}`, 10, 30);
  
  // Draw role info
  ctx.fillStyle = '#fbbf24';
  ctx.font = '16px Arial';
  ctx.fillText(`Role: ${primaryRole} (${roleMultiplier}x)`, 10, 60);
  
  // Simulate game progression
  spaceInvadersScore += 1;
  spaceInvadersCount += 1;
  
  // Update display every 100 frames
  if (spaceInvadersCount % 100 === 0) {
    updateSpaceInvadersScoreDisplay();
  }
}

// ⭐ DRAW STARS
function drawStars() {
  ctx.fillStyle = '#ffffff';
  for (let i = 0; i < 50; i++) {
    const x = (i * 37) % canvas.width;
    const y = (i * 23) % canvas.height;
    ctx.fillRect(x, y, 1, 1);
  }
}

// 🌍 ENVIRONMENT DETECTION
const isProduction = window.location.hostname === 'narrrfs.world';
const API_BASE_URL = isProduction ? 'https://narrrfs.world' : 'http://localhost';

// 🚀 INITIALIZE WHEN PAGE LOADS
document.addEventListener('DOMContentLoaded', () => {
  console.log('🌍 Environment detected:', isProduction ? 'Production' : 'Local');
  console.log('🔗 API Base URL:', API_BASE_URL);
  
  initializeSpaceInvaders();
});

// 🎮 MAKE FUNCTIONS GLOBALLY AVAILABLE
window.startGame = startGame;
window.togglePause = togglePause;
