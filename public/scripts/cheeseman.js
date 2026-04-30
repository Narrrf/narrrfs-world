// 🧀 Cheese Runner / Cheeseman v1.0.1
// Pac-Man-style Narrrfs World game.
// API/table write is intentionally placeholder until backend is created.

(function () {
  'use strict';

  const GAME_KEY = 'cheeseman';
  const LOCAL_TEST_DISCORD_ID = '328601656659017732';
  const LOCAL_TEST_DISCORD_NAME = 'Narrrf';

  const TILE_SIZE = 28;
  const GRID_ROWS = 21;
  const GRID_COLS = 19;

  const PLAYER_START = { row: 17, col: 9 };
const ENEMY_NEST_CENTER = { row: 10, col: 9 };
const ENEMY_RESPAWN_LOCK_TICKS = 10;

  const SCORE_CRUMB = 10;
  const SCORE_POWER = 50;
  const SCORE_ENEMY = 200;
  const SCORE_LEVEL_CLEAR = 500;
  const POWER_MODE_MS = 8000;

const BASE_TICK_MS = 175;
const MIN_TICK_MS = 105;
  
  const DSPOINC_CONVERSION_RATE = 25;

  const COMBO_WINDOW_MS = 2200;
const COMBO_MAX_STACK = 10;
const COMBO_PICKUPS_PER_STACK = 10;
const COMBO_BONUS_PER_STACK = 0.15;

const ENEMY_MOVE_DELAY_BY_LEVEL = [5, 5, 4, 4, 3, 3, 2, 2, 1, 1];
const ENEMY_SMART_CHANCE_BY_LEVEL = [0.08, 0.12, 0.18, 0.25, 0.34, 0.44, 0.55, 0.66, 0.76, 0.86];

const GLYPH_BOOST_IMAGE_SRC = 'img/cheeseman/F.png';
const GLYPH_BOOST_DURATION_MS = 6000;
const GLYPH_BOOST_DROP_CHANCE_ON_LEVEL_START = 1; //0.18; normal 
const GLYPH_BOOST_SCORE_BONUS = 100;
const GLYPH_BOOST_TICK_SPEED_MULTIPLIER = 0.65;

  const ROLE_MULTIPLIERS = {
    'VIP Holder': 2.0,
    '🎴 VIP Holder': 2.0,
    'Holder': 1.5,
    '🏆 Holder': 1.5,
    'Champion': 1.4,
    'WL': 1.3,
    'Season Tester': 1.3,
    'Early Bird': 1.2,
    'Cheese Hunter': 1.1,
    '🧀 Cheese Hunter': 1.1
  };

  const ROLE_PRIORITY = [
    '🎴 VIP Holder',
    'VIP Holder',
    '🏆 Holder',
    'Holder',
    'Champion',
    'WL',
    'Season Tester',
    'Early Bird',
    '🧀 Cheese Hunter',
    'Cheese Hunter'
  ];

  const ROLE_THEMES = {
    'VIP Holder': 'golden',
    '🎴 VIP Holder': 'golden',
    'Holder': 'silver',
    '🏆 Holder': 'silver',
    'Champion': 'red',
    'WL': 'blue',
    'Season Tester': 'green',
    'Early Bird': 'blue',
    'Cheese Hunter': 'cheese',
    '🧀 Cheese Hunter': 'cheese'
  };

  const MAX_LEVEL_TEMPLATE_COUNT = 10;
  const TETRIS_WALL_BLOCK_TYPES = ['I', 'O', 'T', 'S', 'Z', 'J', 'L'];



  const tetrisWallImages = TETRIS_WALL_BLOCK_TYPES.reduce((images, blockType) => {
    const image = new Image();
    image.src = `img/tetris/block_${blockType}.png`;
    images[blockType] = image;
    return images;
  }, {});

const LEVEL_TEMPLATES = [
  [
    '###################',
    '#o......#........o#',
    '#.####..#..####...#',
    '#......##......#..#',
    '###.##....##.###..#',
    '#...#..##..#......#',
    '#.#.#.####.#.####.#',
    '#.#..........#....#',
    '#.#####.###.###.#.#',
    '#.....#.NNN.#...#.#',
    '###.#...NNN...#.#.#',
    '#...#.#.NNN.#.#...#',
    '#.#.###...###.###.#',
    '#.#.............#.#',
    '#.###.#####.###.#.#',
    '#o....#..P..#....o#',
    '###.#.#.###.#.#.###',
    '#...#...#...#.....#',
    '#.#####.#.#####.#.#',
    '#.................#',
    '###################'
  ],
  [
    '###################',
    '#o..#........#...o#',
    '#.#.#.######.#.#..#',
    '#.#........#...#..#',
    '#.#####.##.#####.##',
    '#.....#....#......#',
    '###.#.####.#.####.#',
    '#...#......#....#.#',
    '#.###.########.#..#',
    '#.#....NNNNN...#..#',
    '#.#.##.NNNNN.###.##',
    '#...#..NNNNN....#.#',
    '###.######.####.#.#',
    '#.....#.......#...#',
    '#.###.#.#####.###.#',
    '#o..#....P....#..o#',
    '###.###.###.###.###',
    '#.....#.....#.....#',
    '#.###.###.#.###.#.#',
    '#........#........#',
    '###################'
  ],
  [
    '###################',
    '#o.....#...#.....o#',
    '#.###..#.#.#..###.#',
    '#...#....#....#...#',
    '###.#.#######.#.###',
    '#...#...#.#...#...#',
    '#.#####.#.#.#####.#',
    '#.......#.#.......#',
    '#.###.###.###.###.#',
    '#...#...NNN...#...#',
    '###.###.NNN.###.###',
    '#...#...NNN...#...#',
    '#.###.###.###.###.#',
    '#.......#.#.......#',
    '#.#####.#.#.#####.#',
    '#o....#..P..#....o#',
    '###.#.#.###.#.#.###',
    '#...#.........#...#',
    '#.#.###########.#.#',
    '#.................#',
    '###################'
  ],
  [
    '###################',
    '#o...............o#',
    '###.###.###.###.###',
    '#...#.....#.....#.#',
    '#.#.#.###.#.###.#.#',
    '#.#.....#...#.....#',
    '#.#####.###.#####.#',
    '#.....#.....#.....#',
    '###.#.#.###.#.#.###',
    '#...#...NNN...#...#',
    '#.#####.NNN.#####.#',
    '#...#...NNN...#...#',
    '###.#.###.###.#.###',
    '#.....#.....#.....#',
    '#.#####.###.#####.#',
    '#o......#P#......o#',
    '###.###.#.#.###.###',
    '#.....#.....#.....#',
    '#.###.#######.###.#',
    '#.................#',
    '###################'
  ],
  [
    '###################',
    '#o..#...#.#...#..o#',
    '#.#.#.#.#.#.#.#.#.#',
    '#.#...#...#...#...#',
    '#.###.#######.###.#',
    '#.....#.....#.....#',
    '###.#.#.###.#.#.###',
    '#...#.........#...#',
    '#.#####.###.#####.#',
    '#.....#.NNN.#.....#',
    '###.#...NNN...#.###',
    '#.....#.NNN.#.....#',
    '#.#####.###.#####.#',
    '#...#.........#...#',
    '###.#.###.###.#.###',
    '#o....#..P..#....o#',
    '#.###.#.###.#.###.#',
    '#...#...#.#...#...#',
    '#.#.###.#.#.###.#.#',
    '#.................#',
    '###################'
  ],
  [
    '###################',
    '#o....#.....#....o#',
    '#.###.#.###.#.###.#',
    '#.#...#.#.#.#...#.#',
    '#.#.###.#.#.###.#.#',
    '#.....#.....#.....#',
    '#####.###.###.#####',
    '#.................#',
    '#.#.#.###.###.#.#.#',
    '#.#...NNNNN...#.#.#',
    '#.###.NNNNN.###.#.#',
    '#.#...NNNNN...#...#',
    '#.#.#.###.###.#.#.#',
    '#.................#',
    '#####.###.###.#####',
    '#o......P......#.o#',
    '#.###.#####.###.#.#',
    '#...#.......#.....#',
    '#.#.###.#.###.###.#',
    '#.................#',
    '###################'
  ],
  [
    '###################',
    '#o....#..#..#....o#',
    '#.##..#..#..#..##.#',
    '#...#.........#...#',
    '###.#.#######.#.###',
    '#.....#.....#.....#',
    '#.###.#.###.#.###.#',
    '#.#...#.....#...#.#',
    '#.#.###.###.###.#.#',
    '#...#..NNNNN..#...#',
    '###.#.#NNNNN#.#.###',
    '#...#..NNNNN..#...#',
    '#.#.###.###.###.#.#',
    '#.#...#.....#...#.#',
    '#.###.###.###.###.#',
    '#o....#..P..#....o#',
    '###.#.#.###.#.#.###',
    '#...#.........#...#',
    '#.#.###########.#.#',
    '#.................#',
    '###################'
  ],
  [
    '###################',
    '#o#.....#.....#..o#',
    '#.#.###.#.###.#.#.#',
    '#...#...#...#...#.#',
    '###.#.#####.#.###.#',
    '#...#.......#.....#',
    '#.#####.###.#####.#',
    '#.....#.....#.....#',
    '###.#.###.###.#.###',
    '#...#...NNN...#...#',
    '#.#####.NNN.#####.#',
    '#...#...NNN...#...#',
    '###.#.###.###.#.###',
    '#.....#.....#.....#',
    '#.#####.###.#####.#',
    '#o....#..P..#....o#',
    '#.###.#.###.#.###.#',
    '#...#.........#...#',
    '#.#.###########.#.#',
    '#.................#',
    '###################'
  ],
  [
    '###################',
    '#o...............o#',
    '#.##.#.#####.#.##.#',
    '#....#.......#....#',
    '####.###...###.####',
    '#.......#.#.......#',
    '#.#####.#.#.#####.#',
    '#.#.....#.#.....#.#',
    '#.#.###.....###.#.#',
    '#...#..NNNNN..#...#',
    '###.#.#NNNNN#.#.###',
    '#...#..NNNNN..#...#',
    '#.#.###.....###.#.#',
    '#.#.....#.#.....#.#',
    '#.#####.#.#.#####.#',
    '#o......P......#.o#',
    '####.###.#.###.####',
    '#....#.......#....#',
    '#.##.#.#####.#.##.#',
    '#.................#',
    '###################'
  ],
  [
    '###################',
    '#o..#...#.#...#..o#',
    '#.#.#.#.#.#.#.#.#.#',
    '#.#...#.....#...#.#',
    '#.###.#######.###.#',
    '#.....#.....#.....#',
    '###.#.#.###.#.#.###',
    '#...#...#.#...#...#',
    '#.###.###.###.###.#',
    '#.#....NNNNN....#.#',
    '#.#.##.NNNNN.##.#.#',
    '#.#....NNNNN....#.#',
    '#.###.###.###.###.#',
    '#...#...#.#...#...#',
    '###.#.#.#.#.#.#.###',
    '#o....#..P..#....o#',
    '#.#####.###.#####.#',
    '#.....#.....#.....#',
    '#.###.###.###.###.#',
    '#.................#',
    '###################'
  ]
];

  const DIRECTIONS = {
    up: { row: -1, col: 0 },
    down: { row: 1, col: 0 },
    left: { row: 0, col: -1 },
    right: { row: 0, col: 1 }
  };

  const ENEMY_STARTS = [
    { row: 9, col: 9, color: '#ef4444', name: 'Cheese Destroyer' },
    { row: 9, col: 8, color: '#a855f7', name: 'Cheese Emperor' },
    { row: 9, col: 10, color: '#22c55e', name: 'Cheese Invader' }
  ];

  const canvas = document.getElementById('cheeseman-canvas');
  if (!canvas) {
    console.error('❌ Cheese Runner canvas not found.');
    return;
  }

  const ctx = canvas.getContext('2d');
  canvas.width = GRID_COLS * TILE_SIZE;
  canvas.height = GRID_ROWS * TILE_SIZE;

  const scoreEl = document.getElementById('cheeseman-score');
  const dspoincEl = document.getElementById('cheeseman-dspoinc');
  const levelEl = document.getElementById('cheeseman-level');
  const livesEl = document.getElementById('cheeseman-lives');
  const statusEl = document.getElementById('cheeseman-status');
  const roleEl = document.getElementById('cheeseman-role-multiplier');
  const startBtn = document.getElementById('start-cheeseman-btn');
  const pauseBtn = document.getElementById('pause-cheeseman-btn');
  const restartBtn = document.getElementById('restart-cheeseman-btn');
  const modal = document.getElementById('cheeseman-over-modal');
  const finalScoreEl = document.getElementById('cheeseman-final-score');
  const finalDspoincEl = document.getElementById('cheeseman-final-dspoinc');
  const saveStatusEl = document.getElementById('cheeseman-save-status');
  const playAgainBtn = document.getElementById('cheeseman-play-again-btn');
  const comboLabelEl = document.getElementById('cheeseman-combo-label');
  const comboBarEl = document.getElementById('cheeseman-combo-bar');
  const comboTextEl = document.getElementById('cheeseman-combo-text');

const glyphBoostImage = new Image();
glyphBoostImage.src = GLYPH_BOOST_IMAGE_SRC;

const cheeseImg = new Image();
cheeseImg.src = 'img/cheeseman/cheeseman1.png';

  const invaderImg = new Image();
  invaderImg.src = 'img/space/cheese-invader.png';

  const emperorImg = new Image();
  emperorImg.src = 'img/space/cheese_emporer.png';

  const destroyerImg = new Image();
  destroyerImg.src = 'img/space/cheese_destroyer.png';

    const cheeseExplosionImg = new Image();
  cheeseExplosionImg.src = 'img/space/cheese-explosion.png';

  let maze = [];
  let player = createPlayer();
  let enemies = [];
  let currentDirection = DIRECTIONS.left;
  let nextDirection = DIRECTIONS.left;
  let score = 0;
  let level = 1;
  let lives = 3;
  let crumbsRemaining = 0;
  let gameTimer = null;
  let isRunning = false;
  let isPaused = false;
  let hasScoreBeenSaved = false;
  let powerModeUntil = 0;
let enemyMoveCounter = 0;
let cheesemanUserRoleNames = [];
  let touchStartX = 0;
  let touchStartY = 0;
let comboStack = 0;
let comboPickupCount = 0;
let lastComboCollectAt = 0;
let floatingTexts = [];
  let hitExplosions = [];
  let cheesemanAudioContext = null;

let glyphBoostItem = null; // glyphBoostItem = current item on board or null
let glyphBoostUntil = 0; // glyphBoostUntil = time when boost ends

/**
 * Creates the player at the starting tile and stores the previous tile.
 * Previous tile tracking is required to detect cross-tile enemy collisions.
 */
function createPlayer() {
  return {
    row: PLAYER_START.row,
    col: PLAYER_START.col,
    previousRow: PLAYER_START.row,
    previousCol: PLAYER_START.col
  };
}

  function isLocalDevelopment() {
    return window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
  }

  function resolveDiscordIdentity() {
    let discordId = String(
      window.cheesemanDiscordId ||
      localStorage.getItem('discord_id') ||
      localStorage.getItem('narrrfs_last_discord_id') ||
      ''
    ).trim();

    let discordName = String(
      window.cheesemanDiscordName ||
      localStorage.getItem('discord_name') ||
      localStorage.getItem('narrrfs_last_discord_name') ||
      localStorage.getItem('DISCORD_NAME') ||
      ''
    ).trim();

    if (isLocalDevelopment() && !discordId) {
      discordId = LOCAL_TEST_DISCORD_ID;
      discordName = LOCAL_TEST_DISCORD_NAME;
      localStorage.setItem('discord_id', discordId);
      localStorage.setItem('discord_name', discordName);
      localStorage.setItem('narrrfs_last_discord_id', discordId);
      localStorage.setItem('narrrfs_last_discord_name', discordName);
    }

    return { discordId, discordName };
  }

  async function fetchCheesemanUserRoleNames() {
    try {
      if (isLocalDevelopment()) {
        cheesemanUserRoleNames = [
          '🎴 VIP Holder',
          '🏆 Holder',
          'Champion',
          'Season Tester',
          'Early Bird',
          '🧀 Cheese Hunter'
        ];
        applyCheesemanRoleTheme();
        updateScoreDisplay();
        return;
      }

      const apiBaseUrl = window.location.hostname === 'narrrfs.world'
        ? 'https://narrrfs.world'
        : '';

      const response = await fetch(`${apiBaseUrl}/api/auth/sync-role.php`, {
        method: 'GET',
        credentials: 'include',
        cache: 'no-store'
      });

      if (!response.ok) {
        cheesemanUserRoleNames = [];
        applyCheesemanRoleTheme();
        updateScoreDisplay();
        return;
      }

      const data = await response.json();
      cheesemanUserRoleNames = Array.isArray(data.roles) ? data.roles : [];
      applyCheesemanRoleTheme();
      updateScoreDisplay();
    } catch (error) {
      console.warn('🏆 Cheese Runner role fetch failed:', error);
      cheesemanUserRoleNames = [];
      applyCheesemanRoleTheme();
      updateScoreDisplay();
    }
  }

  function getCheesemanPrimaryRoleName() {
    for (const roleName of ROLE_PRIORITY) {
      if (cheesemanUserRoleNames.includes(roleName)) {
        return roleName;
      }
    }

    return null;
  }

  function getCheesemanRoleScoreMultiplier() {
    const primaryRoleName = getCheesemanPrimaryRoleName();
    return ROLE_MULTIPLIERS[primaryRoleName] || 1.0;
  }

  function applyCheesemanRoleTheme() {
    const primaryRoleName = getCheesemanPrimaryRoleName();
    const theme = ROLE_THEMES[primaryRoleName] || 'default';
    const controlsSection = document.getElementById('cheeseman-controls-section');
    const controlsTitle = document.getElementById('cheeseman-controls-title');
    const themeClasses = ['golden', 'silver', 'cheese', 'green', 'blue', 'red'];

    canvas.classList.remove(...themeClasses);
    controlsSection?.classList.remove(...themeClasses);
    controlsTitle?.classList.remove(...themeClasses);

    if (theme === 'default') {
      return;
    }

    canvas.classList.add(theme);
    controlsSection?.classList.add(theme);
    controlsTitle?.classList.add(theme);
  }

  function calculateDspoincReward() {
    const roleMultiplier = getCheesemanRoleScoreMultiplier();
    const beforeConversion = Math.floor(score * roleMultiplier);
    return Math.floor(beforeConversion / DSPOINC_CONVERSION_RATE);
  }

    /**
   * Returns the active combo multiplier.
   * Combo is frontend gameplay only; backend still receives the final score.
   */
  function getComboMultiplier() {
    if (comboStack <= 1) {
      return 1;
    }

    return 1 + ((comboStack - 1) * COMBO_BONUS_PER_STACK);
  }

  /**
   * Returns how much of the current combo timer is still alive.
   */
  function getComboWindowProgress() {
    if (!comboStack || !lastComboCollectAt) {
      return 0;
    }

    const elapsed = performance.now() - lastComboCollectAt;
    return Math.max(0, 1 - (elapsed / COMBO_WINDOW_MS));
  }

  /**
   * Resets combo when the player waits too long between cheese pickups.
   */
  function expireComboIfNeeded() {
    if (!comboStack || !lastComboCollectAt) {
      return;
    }

    if (performance.now() - lastComboCollectAt <= COMBO_WINDOW_MS) {
      return;
    }

    comboStack = 0;
comboPickupCount = 0;
lastComboCollectAt = 0;
  }

  /**
   * Adds one combo stack and returns the multiplier used for this pickup.
   */
  /**
   * Adds one cheese pickup toward the next combo stack.
   * Ten fast cheese pickups increase the combo level by one.
   */
  function registerComboPickup() {
    const now = performance.now();

    if (!lastComboCollectAt || now - lastComboCollectAt > COMBO_WINDOW_MS) {
      comboStack = 1;
      comboPickupCount = 0;
    }

    comboPickupCount += 1;

    if (comboPickupCount >= COMBO_PICKUPS_PER_STACK) {
      comboPickupCount = 0;
      comboStack = Math.min(COMBO_MAX_STACK, comboStack + 1);
      addFloatingText(`COMBO LEVEL ${comboStack}!`, player.row, player.col, '#fb923c');
    }

    lastComboCollectAt = now;
    return getComboMultiplier();
  }

  /**
   * Adds animated reward text above the player.
   * Keeps the list capped so long sessions do not overload the browser.
   */
  function addFloatingText(text, row, col, color = '#facc15') {
    floatingTexts.push({
      text,
      x: col * TILE_SIZE + TILE_SIZE / 2,
      y: row * TILE_SIZE + 4,
      life: 34,
      color
    });

    if (floatingTexts.length > 24) {
      floatingTexts = floatingTexts.slice(-24);
    }
  }

  /**
   * Draws temporary score/combo text on the canvas.
   */
  function drawFloatingTexts() {
    floatingTexts = floatingTexts.filter(item => item.life > 0);

    floatingTexts.forEach(item => {
      const alpha = Math.max(0, item.life / 34);
      ctx.save();
      ctx.globalAlpha = alpha;
      ctx.fillStyle = item.color;
      ctx.font = 'bold 14px Arial';
      ctx.textAlign = 'center';
      ctx.shadowColor = item.color;
      ctx.shadowBlur = 10;
      ctx.fillText(item.text, item.x, item.y);
      ctx.restore();

      item.y -= 0.55;
      item.life -= 1;
    });
  }

  // Glyph Power Booster Functions
  function isGlyphBoostActive() {
  return performance.now() < glyphBoostUntil;
}
function clearGlyphBoost() {
  glyphBoostUntil = 0;
}
/**
 * Activates the rare Glyph Boost item.
 * The mouse becomes faster and protected for a short time.
 */
function activateGlyphBoost() {
  glyphBoostUntil = performance.now() + GLYPH_BOOST_DURATION_MS;
  score += GLYPH_BOOST_SCORE_BONUS;
  glyphBoostItem = null;

  setStatus('🖤 Glyph Boost active! Faster and undefeatable!');
  playSound('power');

  if (!isRunning || isPaused) {
    return;
  }

  startGameTimer();
}

/**
 * Ends Glyph Boost when its timer expires and restores normal game speed.
 */
function updateGlyphBoost() {
  if (!glyphBoostUntil) {
    return;
  }

  if (isGlyphBoostActive()) {
    return;
  }

  glyphBoostUntil = 0;
  setStatus('Glyph Boost faded.');

  if (!isRunning || isPaused) {
    return;
  }

  startGameTimer();
}

/**
 * Spawns the rare Glyph Boost item on a safe visible board tile.
 * It never spawns inside walls, enemy nests, the player tile, or enemy tiles.
 */
function spawnGlyphBoostItem() {
  glyphBoostItem = null;

  if (Math.random() > GLYPH_BOOST_DROP_CHANCE_ON_LEVEL_START) {
    return;
  }

  const validTiles = [];

  for (let row = 1; row < GRID_ROWS - 1; row += 1) {
    for (let col = 1; col < GRID_COLS - 1; col += 1) {
      const tile = maze[row]?.[col];

      if (!tile || tile === 'wall' || tile === 'nest') {
        continue;
      }

      if (!canMove(row, col)) {
        continue;
      }

      if (row === player.row && col === player.col) {
        continue;
      }

      const hasEnemyOnTile = enemies.some(enemy => enemy.row === row && enemy.col === col);
      if (hasEnemyOnTile) {
        continue;
      }

      validTiles.push({ row, col });
    }
  }

  if (!validTiles.length) {
    console.warn('🖤 Glyph Boost: no valid spawn tile found.');
    return;
  }

  glyphBoostItem = validTiles[Math.floor(Math.random() * validTiles.length)];
  console.log('🖤 Glyph Boost spawned at:', glyphBoostItem);
}

function getCurrentTickMs() {
  const baseTickMs = Math.max(MIN_TICK_MS, BASE_TICK_MS - ((level - 1) * 8));

  if (isGlyphBoostActive()) {
    return Math.max(MIN_TICK_MS, Math.floor(baseTickMs * GLYPH_BOOST_TICK_SPEED_MULTIPLIER));
  }

  return baseTickMs;
}




  /**
   * Updates the combo HUD scale below the score cards.
   */
  function updateComboDisplay() {
    expireComboIfNeeded();

    const multiplier = getComboMultiplier();
        const timeProgress = getComboWindowProgress();

    const totalComboProgress = comboStack > 0
      ? ((comboStack - 1) + (comboPickupCount / COMBO_PICKUPS_PER_STACK)) / COMBO_MAX_STACK
      : 0;

    const comboPercent = comboStack > 0
      ? Math.max(6, Math.min(100, Math.round(totalComboProgress * 100)))
      : 0;

    if (comboLabelEl) {
      comboLabelEl.textContent = `x${multiplier.toFixed(1)}`;
      comboLabelEl.className = comboStack >= 5
        ? 'text-orange-300 font-black animate-pulse'
        : 'text-gray-300 font-bold';
    }

    if (comboBarEl) {
      comboBarEl.style.width = `${comboPercent}%`;
    }

    if (comboTextEl) {
            comboTextEl.textContent = comboStack > 0
        ? `Combo Level ${comboStack}/${COMBO_MAX_STACK} · ${comboPickupCount}/${COMBO_PICKUPS_PER_STACK} cheese to next level · ${Math.ceil(timeProgress * COMBO_WINDOW_MS / 1000)}s before melt`
        : 'Eat 10 cheese fast to raise combo level.';
    }
  }

  function updateScoreDisplay() {
    const dspoincReward = calculateDspoincReward();
    const roleMultiplier = getCheesemanRoleScoreMultiplier();

    if (scoreEl) scoreEl.textContent = score.toLocaleString();
    if (dspoincEl) dspoincEl.textContent = `$${dspoincReward.toLocaleString()}`;
    if (levelEl) levelEl.textContent = String(level);
    if (livesEl) livesEl.textContent = '🧀'.repeat(Math.max(0, lives)) || '0';
        updateComboDisplay();

    if (roleEl) {
      roleEl.textContent = `Role Bonus: ${roleMultiplier.toFixed(1)}x`;
      roleEl.className = roleMultiplier > 1
        ? 'font-bold text-yellow-300 animate-pulse'
        : 'font-bold text-cyan-300';
    }
  }

    function getCurrentLevelTemplate() {
    return LEVEL_TEMPLATES[(level - 1) % LEVEL_TEMPLATES.length];
  }

  function validateLevelTemplates() {
    LEVEL_TEMPLATES.forEach((template, index) => {
      if (template.length !== GRID_ROWS) {
        console.warn(`⚠️ Cheese Runner level ${index + 1} has ${template.length} rows, expected ${GRID_ROWS}`);
      }

      template.forEach((rowText, rowIndex) => {
        if (rowText.length !== GRID_COLS) {
          console.warn(`⚠️ Cheese Runner level ${index + 1}, row ${rowIndex + 1} has ${rowText.length} columns, expected ${GRID_COLS}`);
        }
      });
    });
  }

  function getWallImageForTile(row, col) {
    const blockType = TETRIS_WALL_BLOCK_TYPES[(row + col + level) % TETRIS_WALL_BLOCK_TYPES.length];
    return tetrisWallImages[blockType];
  }

  function buildMaze() {
    crumbsRemaining = 0;

    maze = getCurrentLevelTemplate().map(rowText => rowText.split('').map(cell => {
      if (cell === '#') return 'wall';

      if (cell === 'N') return 'nest';

      if (cell === 'o') {
        crumbsRemaining += 1;
        return 'power';
      }

      if (cell === '.') {
        crumbsRemaining += 1;
        return 'crumb';
      }

      return 'empty';
    }));
  }

  /**
   * Creates enemies inside the central nest.
   * Each enemy keeps its own respawn home so weak enemies can return after being eaten.
   */
  function createEnemies() {
    const nestSpawns = [
      { row: ENEMY_NEST_CENTER.row, col: ENEMY_NEST_CENTER.col - 1 },
      { row: ENEMY_NEST_CENTER.row, col: ENEMY_NEST_CENTER.col },
      { row: ENEMY_NEST_CENTER.row, col: ENEMY_NEST_CENTER.col + 1 }
    ];

    return ENEMY_STARTS.map((start, index) => {
      const nestSpawn = nestSpawns[index] || ENEMY_NEST_CENTER;

      return {
  row: nestSpawn.row,
  col: nestSpawn.col,
  previousRow: nestSpawn.row,
  previousCol: nestSpawn.col,
  startRow: nestSpawn.row,
  startCol: nestSpawn.col,
  color: start.color,
  name: start.name,
  direction: Object.values(DIRECTIONS)[index % 4],
  isStunned: false,
  respawnLockTicks: ENEMY_RESPAWN_LOCK_TICKS
};
    });
  }

function resetGame() {
  clearGameTimer();

  score = 0;
  level = 1;
  lives = 3;
  isRunning = false;
  isPaused = false;
  hasScoreBeenSaved = false;
  powerModeUntil = 0;
  glyphBoostUntil = 0;
  glyphBoostItem = null;
  comboStack = 0;
  comboPickupCount = 0;
  lastComboCollectAt = 0;
  floatingTexts = [];
  hitExplosions = [];
  enemyMoveCounter = 0;

  buildMaze();

  player = createPlayer();
  enemies = createEnemies();
  spawnGlyphBoostItem();

  currentDirection = DIRECTIONS.left;
  nextDirection = DIRECTIONS.left;

if (modal) {
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  modal.style.display = 'none';
}

  if (saveStatusEl) {
    saveStatusEl.textContent = '';
  }

  setStatus('Ready to run.');
  updateScoreDisplay();
  render();
}

  function startGame() {
    if (isRunning && !isPaused) {
      return;
    }

    if (isPaused) {
      togglePause();
      return;
    }

    isRunning = true;
    isPaused = false;
    setStatus('Running through the cheese maze!');
    startGameTimer();
  }

  function togglePause() {
    if (!isRunning) {
      return;
    }

    isPaused = !isPaused;

    if (isPaused) {
      clearGameTimer();
      setStatus('Paused.');
      render();
      return;
    }

    setStatus('Running through the cheese maze!');
    startGameTimer();
  }

/**
 * Starts or restarts the Cheese Runner game loop.
 * Uses the current tick speed so temporary boosts can safely speed up and fade out.
 */
function startGameTimer() {
  clearGameTimer();
  gameTimer = window.setInterval(gameTick, getCurrentTickMs());
}

  function clearGameTimer() {
    if (!gameTimer) {
      return;
    }

    window.clearInterval(gameTimer);
    gameTimer = null;
  }

function collectGlyphBoostItem() {
  if (!glyphBoostItem) {
    return;
  }

  if (player.row !== glyphBoostItem.row || player.col !== glyphBoostItem.col) {
    return;
  }

  activateGlyphBoost();
}

/**
 * Runs one game tick.
 * Movement, collection, enemy movement, collision checks, UI updates, and level clear checks happen here.
 */
function gameTick() {
  if (!isRunning || isPaused) {
    return;
  }

  if (lives <= 0) {
    clearGameTimer();
    endGame();
    return;
  }

  updatePowerMode();
  updateGlyphBoost();

  movePlayer();

  if (checkEnemyCollisions()) {
    updateScoreDisplay();
    render();
    return;
  }

  collectTile();
  collectGlyphBoostItem();
  moveEnemies();

  if (checkEnemyCollisions()) {
    updateScoreDisplay();
    render();
    return;
  }

  updateScoreDisplay();
  render();

  if (crumbsRemaining <= 0) {
    advanceLevel();
  }
}

  function setDirection(directionName) {
    const direction = DIRECTIONS[directionName];
    if (!direction) {
      return;
    }

    nextDirection = direction;
  }

/**
 * Moves the player one tile and records the tile they came from.
 * Previous-position tracking lets collision logic catch cross-tile swaps.
 */
function movePlayer() {
  player.previousRow = player.row;
  player.previousCol = player.col;

  if (canMove(player.row + nextDirection.row, player.col + nextDirection.col)) {
    currentDirection = nextDirection;
  }

  const nextRow = player.row + currentDirection.row;
  const nextCol = player.col + currentDirection.col;

  if (canMove(nextRow, nextCol)) {
    player.row = nextRow;
    player.col = nextCol;
  }
}

  function collectTile() {
    const tile = maze[player.row]?.[player.col];

    if (tile === 'crumb') {
      const comboMultiplier = registerComboPickup();
      const gainedScore = Math.round(SCORE_CRUMB * comboMultiplier);

      score += gainedScore;
      crumbsRemaining -= 1;
      maze[player.row][player.col] = 'empty';

      addFloatingText(`+${gainedScore} x${comboMultiplier.toFixed(1)}`, player.row, player.col);
      playSound('crumb');
      return;
    }

    if (tile === 'power') {
      const comboMultiplier = registerComboPickup();
      const gainedScore = Math.round(SCORE_POWER * comboMultiplier);

      score += gainedScore;
      crumbsRemaining -= 1;
      maze[player.row][player.col] = 'empty';
      powerModeUntil = performance.now() + POWER_MODE_MS;

      enemies.forEach(enemy => {
        enemy.isStunned = true;
      });

      addFloatingText(`POWER +${gainedScore} x${comboMultiplier.toFixed(1)}`, player.row, player.col, '#fb923c');
      setStatus('Power Cheese active! Eat the enemies!');
      playSound('power');
    }
  }

    /**
   * Returns which handcrafted template difficulty is active.
   * Level 11 loops to template 1 visually, but difficulty keeps rising through the cap.
   */
  function getDifficultyLevelIndex() {
    return Math.min(level, MAX_LEVEL_TEMPLATE_COUNT) - 1;
  }

  /**
   * Controls how often enemies move.
   * Higher number = slower enemies. Level 1 is intentionally beginner-friendly.
   */
  function getEnemyMoveDelayTicks() {
    return ENEMY_MOVE_DELAY_BY_LEVEL[getDifficultyLevelIndex()] || 1;
  }

  /**
   * Controls how often enemies choose the shortest path toward the player.
   * Lower levels wander more. Higher levels hunt harder.
   */
  function getEnemySmartMoveChance() {
    return ENEMY_SMART_CHANCE_BY_LEVEL[getDifficultyLevelIndex()] || 0.82;
  }

  function moveEnemies() {
    enemyMoveCounter += 1;

    if (enemyMoveCounter < getEnemyMoveDelayTicks()) {
      return;
    }

    enemyMoveCounter = 0;

    enemies.forEach(enemy => {
  enemy.previousRow = enemy.row;
  enemy.previousCol = enemy.col;
  enemy.isStunned = isPowerModeActive();

            if (enemy.respawnLockTicks > 0) {
        enemy.respawnLockTicks -= 1;
        return;
      }

      const possibleDirections = Object.values(DIRECTIONS).filter(direction => {
        const nextRow = enemy.row + direction.row;
        const nextCol = normalizeColumn(enemy.col + direction.col);
        return canMove(nextRow, nextCol);
      });

      if (possibleDirections.length === 0) {
        return;
      }

      enemy.direction = chooseEnemyDirection(enemy, possibleDirections);
      enemy.row += enemy.direction.row;
      enemy.col = normalizeColumn(enemy.col + enemy.direction.col);
    });
  }

  function chooseEnemyDirection(enemy, possibleDirections) {
    const targetDirection = possibleDirections
      .map(direction => {
        const nextRow = enemy.row + direction.row;
        const nextCol = normalizeColumn(enemy.col + direction.col);
        const distance = getDistance(nextRow, nextCol, player.row, player.col);

        return {
          direction,
          distance
        };
      })
      .sort((left, right) => {
        return isPowerModeActive()
          ? right.distance - left.distance
          : left.distance - right.distance;
      })[0];

    const shouldUseSmartMove = Math.random() < getEnemySmartMoveChance();

    if (shouldUseSmartMove && targetDirection) {
      return targetDirection.direction;
    }

    return possibleDirections[Math.floor(Math.random() * possibleDirections.length)];
  }

    /**
   * Adds a short explosion animation at the player tile when the mouse is caught.
   */
  function addHitExplosion(row, col) {
    hitExplosions.push({
      x: col * TILE_SIZE + TILE_SIZE / 2,
      y: row * TILE_SIZE + TILE_SIZE / 2,
      life: 24,
      maxLife: 24,
      size: TILE_SIZE * 1.8
    });
  }

  /**
   * Draws active hit explosions over the maze.
   */
  function drawHitExplosions() {
    hitExplosions = hitExplosions.filter(explosion => explosion.life > 0);

    hitExplosions.forEach(explosion => {
      const progress = 1 - (explosion.life / explosion.maxLife);
      const size = explosion.size * (1 + progress * 0.8);
      const alpha = Math.max(0, explosion.life / explosion.maxLife);

      ctx.save();
      ctx.globalAlpha = alpha;
      ctx.shadowColor = '#f97316';
      ctx.shadowBlur = 18;

      if (cheeseExplosionImg.complete && cheeseExplosionImg.naturalWidth > 0) {
        ctx.drawImage(
          cheeseExplosionImg,
          explosion.x - size / 2,
          explosion.y - size / 2,
          size,
          size
        );
      } else {
        ctx.fillStyle = '#f97316';
        ctx.beginPath();
        ctx.arc(explosion.x, explosion.y, size / 2, 0, Math.PI * 2);
        ctx.fill();
      }

      ctx.restore();
      explosion.life -= 1;
    });
  }

  /**
   * Shows a 3, 2, 1, GO countdown after losing a life before gameplay resumes.
   */
  function showLifeRestartCountdown(onComplete) {
    showLevelTransitionCountdown('again', () => {
  onComplete();
}, 'life');

    const content = document.getElementById('cheeseman-level-transition-content');

    if (content) {
      content.innerHTML = `
        <div style="font-size: clamp(22px, 5vw, 32px); color: #fb923c; text-shadow: 0 0 18px rgba(249, 115, 22, 0.8);">
          💥 MOUSE CAUGHT!
        </div>
        <div style="font-size: clamp(16px, 4vw, 22px); color: #fde68a; margin-top: 8px;">
          Life lost · Get ready
        </div>
        <div style="font-size: 14px; color: #e5e7eb; margin-top: 8px;">
          Remaining lives: ${lives}
        </div>
      `;
    }
  }

  /**
   * Pauses the game, shows explosion feedback, then restarts the current life safely.
   */
  function restartLifeAfterHit() {
    clearGameTimer();
    isRunning = false;
    isPaused = false;

    addHitExplosion(player.row, player.col);
    setStatus('Mouse caught! Restarting life...');
    render();

    comboStack = 0;
    comboPickupCount = 0;
    lastComboCollectAt = 0;
    floatingTexts = [];

    showLifeRestartCountdown(() => {
      player = createPlayer();
      enemies = createEnemies();
      currentDirection = DIRECTIONS.left;
      nextDirection = DIRECTIONS.left;
      powerModeUntil = 0;
      enemyMoveCounter = 0;

      isRunning = true;
      isPaused = false;

      setStatus(`Life restarted. Level ${level} continues.`);
      updateScoreDisplay();
      render();
      startGameTimer();
    });
  }

/**
 * Returns true when the player and enemy occupy the same tile.
 */
function isSameTileCollision(enemy) {
  return enemy.row === player.row && enemy.col === player.col;
}

/**
 * Returns true when the player and enemy cross through each other between ticks.
 * This fixes the classic Pac-Man style bug where both actors swap tiles and never share one.
 */
function isCrossTileCollision(enemy) {
  return (
    enemy.row === player.previousRow &&
    enemy.col === player.previousCol &&
    enemy.previousRow === player.row &&
    enemy.previousCol === player.col
  );
}

/**
 * Handles one confirmed enemy collision and returns whether gameplay should stop this tick.
 * Vulnerable enemies are eaten; normal enemies cost one life.
 */
function resolveEnemyCollision(enemy) {
  if (enemy.respawnLockTicks > 0) {
    return false;
  }

  if (isPowerModeActive()) {
    score += SCORE_ENEMY;

    addHitExplosion(enemy.row, enemy.col);
    addFloatingText(`EATEN +${SCORE_ENEMY}`, enemy.row, enemy.col, '#22c55e');

    enemy.row = enemy.startRow;
    enemy.col = enemy.startCol;
    enemy.previousRow = enemy.startRow;
    enemy.previousCol = enemy.startCol;
    enemy.direction = DIRECTIONS.up;
    enemy.isStunned = false;
    enemy.respawnLockTicks = ENEMY_RESPAWN_LOCK_TICKS;

    setStatus(`${enemy.name} eaten! It respawns in the nest.`);
    playSound('enemy');
    return false;
  }

if (isGlyphBoostActive() && lives > 0) {
  setStatus('🖤 Glyph Boost protected you!');
  playSound('power');
  return false;
}

if (lives <= 0) {
  clearGameTimer();
  endGame();
  return true;
}

lives -= 1;
  playSound('hit');
  addHitExplosion(player.row, player.col);

  if (lives <= 0) {
    endGame();
    return true;
  }

  restartLifeAfterHit();
  return true;
}

/**
 * Checks enemy collisions for exact tile overlap and cross-tile swaps.
 * Returns true when the active tick should stop because the player lost a life or the game ended.
 */
function checkEnemyCollisions() {
  for (const enemy of enemies) {
    if (!isSameTileCollision(enemy) && !isCrossTileCollision(enemy)) {
      continue;
    }

    if (resolveEnemyCollision(enemy)) {
      return true;
    }
  }

  return false;
}

    /**
   * Shows a Snake-style level transition popup without changing layout or scroll position.
   * The game timer stays stopped until the countdown finishes.
   */
  function showLevelTransitionCountdown(nextLevel, onComplete, transitionType = 'level') {
    const savedScrollX = window.scrollX || window.pageXOffset || 0;
    const savedScrollY = window.scrollY || window.pageYOffset || 0;

    const notification = document.createElement('div');
    notification.id = 'cheeseman-level-transition-notification';

    notification.innerHTML = `
      <div id="cheeseman-level-transition-content" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
                  background: linear-gradient(45deg, rgba(250, 204, 21, 0.35), rgba(245, 158, 11, 0.35), rgba(16, 185, 129, 0.25));
                  color: white; padding: 22px 32px; border-radius: 22px; font-weight: bold;
                  z-index: 10000; text-align: center; box-shadow: 0 0 50px rgba(250, 204, 21, 0.45);
                  border: 4px solid rgba(255, 215, 0, 0.75);
                  backdrop-filter: blur(6px);
                  transition: opacity 0.3s ease-in-out;
                  opacity: 0;
                  max-width: 90vw;
                  width: 420px;
                  pointer-events: auto;">
        <div style="font-size: clamp(22px, 5vw, 32px); color: ${transitionType === 'life' ? '#fb923c' : '#facc15'}; text-shadow: 0 0 18px ${transitionType === 'life' ? 'rgba(249, 115, 22, 0.8)' : 'rgba(250, 204, 21, 0.8)'};">
  ${transitionType === 'life' ? '💥 MOUSE CAUGHT!' : '🧀 LEVEL PASSED!'}
</div>
<div style="font-size: clamp(16px, 4vw, 22px); color: ${transitionType === 'life' ? '#fde68a' : '#bbf7d0'}; margin-top: 8px;">
  ${transitionType === 'life' ? 'Life lost · Get ready' : `Level ${nextLevel} starts soon`}
</div>
<div style="font-size: 14px; color: #e5e7eb; margin-top: 8px;">
  ${transitionType === 'life' ? `Remaining lives: ${lives}` : `+${SCORE_LEVEL_CLEAR} clear bonus`}
</div>
      </div>
      <style>
        @keyframes cheesemanCountdownPulse {
          0%, 100% { transform: translate(-50%, -50%) scale(1); }
          50% { transform: translate(-50%, -50%) scale(1.3); }
        }
      </style>
    `;

    notification.style.position = 'fixed';
    notification.style.top = '0';
    notification.style.left = '0';
    notification.style.width = '0';
    notification.style.height = '0';
    notification.style.overflow = 'visible';
    notification.style.pointerEvents = 'none';
    notification.style.zIndex = '10000';

    document.body.appendChild(notification);

    requestAnimationFrame(() => {
      window.scrollTo(savedScrollX, savedScrollY);
    });

    const countdownDiv = notification.querySelector('#cheeseman-level-transition-content');

    setTimeout(() => {
      if (countdownDiv) countdownDiv.style.opacity = '1';
    }, 10);

    let countdown = 3;

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
                        animation: cheesemanCountdownPulse 0.5s ease-in-out;
                        color: #FFD700;">
              ${countdown}
            </div>
          `;
          countdownDiv.style.animation = 'cheesemanCountdownPulse 0.5s ease-in-out';
          countdown -= 1;
          return;
        }

        countdownDiv.innerHTML = `
          <div style="font-size: clamp(40px, 10vw, 64px); font-weight: bold; text-shadow: 0 0 30px rgba(16, 185, 129, 1);
                      animation: cheesemanCountdownPulse 0.3s ease-in-out;
                      color: #10b981;">
            GO!
          </div>
        `;
        countdownDiv.style.animation = 'cheesemanCountdownPulse 0.3s ease-in-out';
        clearInterval(countdownInterval);

        setTimeout(() => {
          countdownDiv.style.opacity = '0';

          setTimeout(() => {
            if (notification.parentNode) {
              notification.parentNode.removeChild(notification);
            }

            requestAnimationFrame(() => {
              window.scrollTo(savedScrollX, savedScrollY);
            });

            onComplete();
          }, 300);
        }, 500);
      }, 800);
    }, 1500);
  }

  function advanceLevel() {
    score += SCORE_LEVEL_CLEAR;
    level += 1;

    clearGameTimer();
    isRunning = false;
    isPaused = false;

    buildMaze();
    player = createPlayer();
    enemies = createEnemies();
    currentDirection = DIRECTIONS.left;
    nextDirection = DIRECTIONS.left;
    powerModeUntil = 0;
    enemyMoveCounter = 0;
    comboStack = 0;
comboPickupCount = 0;
lastComboCollectAt = 0;
    floatingTexts = [];

    playSound('level');
    setStatus(`Level ${level}! Get ready...`);
    updateScoreDisplay();
    render();

    showLevelTransitionCountdown(level, () => {
      isRunning = true;
      isPaused = false;
      setStatus(`Level ${level}! Run through the cheese maze.`);
      startGameTimer();
    });
  }

/**
 * Keeps backward compatibility for older HTML modals.
 * Current cheeseman.html already contains real Game Over navigation buttons.
 */
function ensureGameOverNavigationLinks() {
  if (!modal) {
    return;
  }

  if (modal.querySelector('[data-cheeseman-gameover-nav]')) {
    return;
  }

  const playAgainButton = modal.querySelector('#cheeseman-play-again-btn');
  if (!playAgainButton) {
    return;
  }

  const existingNavigationLink = modal.querySelector('a[href="profile.html"], a[href="index.html"], a[href="leaderboard.html"]');
  if (existingNavigationLink) {
    return;
  }

  const navigationWrap = document.createElement('div');
  navigationWrap.dataset.cheesemanGameoverNav = 'true';
  navigationWrap.className = 'grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4';
  navigationWrap.innerHTML = `
    <a href="profile.html" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 font-black text-white hover:bg-blue-500">👤 Profile</a>
    <a href="index.html" class="inline-flex items-center justify-center rounded-xl bg-gray-700 px-4 py-3 font-black text-white hover:bg-gray-600">🏠 Home</a>
    <a href="leaderboard.html" class="inline-flex items-center justify-center rounded-xl bg-purple-600 px-4 py-3 font-black text-white hover:bg-purple-500">🏆 Leaderboard</a>
  `;

  playAgainButton.parentElement.insertBefore(navigationWrap, playAgainButton);
}

/**
 * Ends the current Cheese Runner run safely.
 * This stops the timer, clears temporary boost state, updates the final modal, and saves the score once.
 */
async function endGame() {
  clearGameTimer();
  isRunning = false;
  isPaused = false;
  glyphBoostUntil = 0;
  glyphBoostItem = null;
  powerModeUntil = 0;

  updateScoreDisplay();
  render();

  if (finalScoreEl) {
    finalScoreEl.textContent = `Score: ${score.toLocaleString()}`;
  }

  if (finalDspoincEl) {
    finalDspoincEl.textContent = `DSPOINC: ${calculateDspoincReward().toLocaleString()}`;
  }

  if (saveStatusEl) {
    saveStatusEl.textContent = 'Saving score...';
  }

  if (modal) {
    ensureGameOverNavigationLinks();
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.style.display = 'flex';
  }

  await submitCheesemanScore();
}

  /**
   * Save Cheese Runner score through the backend-authoritative API.
   * The API writes the seasonal game score and the DSPOINC ledger reward.
   */
  async function submitCheesemanScore() {
    if (hasScoreBeenSaved) {
      return;
    }

    hasScoreBeenSaved = true;

    const { discordId, discordName } = resolveDiscordIdentity();
    const dspoincReward = calculateDspoincReward();

    if (!discordId) {
      if (saveStatusEl) {
        saveStatusEl.textContent = 'Login required before Cheese Runner scores can be saved.';
      }
      return;
    }

    const payload = {
      user_id: discordId,
      discord_id: discordId,
      discord_name: discordName || 'Discord Mouse',
      game: GAME_KEY,
      source: 'game_reward',
      raw_score: score,
      dspoinc_score: dspoincReward,
      level,
      lives_remaining: lives,
      role_multiplier: getCheesemanRoleScoreMultiplier(),
      client_version: 'cheeseman-1.0.3'
    };

    const apiBaseUrl = window.location.hostname === 'narrrfs.world'
      ? 'https://narrrfs.world'
      : '';

    try {
      if (saveStatusEl) {
        saveStatusEl.textContent = 'Saving Cheese Runner score...';
      }

      const response = await fetch(`${apiBaseUrl}/api/dev/save-cheeseman-score.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        credentials: 'include',
        cache: 'no-store',
        body: JSON.stringify(payload)
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.error || data.details || `HTTP ${response.status}`);
      }

      if (saveStatusEl) {
        saveStatusEl.textContent = `✅ Score saved for ${data.season}: ${Number(data.dspoinc_score || 0).toLocaleString()} DSPOINC`;
      }

      console.log('✅ Cheese Runner score saved:', data);
    } catch (error) {
      console.error('❌ Cheese Runner score save failed:', error);

      hasScoreBeenSaved = false;

      if (saveStatusEl) {
        saveStatusEl.textContent = `❌ Score save failed: ${error.message}`;
      }
    }
  }

  /**
 * Saves Cheese Runner score to backend.
 * Backend is authoritative and writes:
 * - tbl_user_scores (DSPOINC ledger)
 * - tbl_tetris_scores (leaderboard)
 */
async function saveCheeseRunnerScore(score, dspoinc) {
  try {
    const payload = {
      score: score,
      dspoinc: dspoinc,
      game: 'cheeseman'
    };

    const res = await fetch('/api/games/save-cheeseman-score.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const data = await res.json();

    if (!data?.success) {
      console.warn('❌ Cheese Runner save failed:', data);
      return;
    }

    console.log('✅ Cheese Runner score saved:', data);

  } catch (err) {
    console.error('❌ Cheese Runner save error:', err);
  }
}

  function canMove(row, col) {
    if (row < 0 || row >= GRID_ROWS) {
      return false;
    }

    const normalizedCol = normalizeColumn(col);
    return maze[row]?.[normalizedCol] !== 'wall';
  }

  function normalizeColumn(col) {
    if (col < 0) return GRID_COLS - 1;
    if (col >= GRID_COLS) return 0;
    return col;
  }

  function getDistance(rowA, colA, rowB, colB) {
    return Math.abs(rowA - rowB) + Math.abs(colA - colB);
  }

  function isPowerModeActive() {
    return performance.now() < powerModeUntil;
  }

  /**
 * Keeps power mode state in sync every tick.
 * When the timer ends, enemies return to normal movement/visual state.
 */
function updatePowerMode() {
  if (isPowerModeActive()) {
    enemies.forEach(enemy => {
      enemy.isStunned = true;
    });
    return;
  }

  if (powerModeUntil > 0) {
    powerModeUntil = 0;
    enemies.forEach(enemy => {
      enemy.isStunned = false;
    });
  }
}

function render() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  drawMaze();
  drawGlyphBoostItem();
  drawEnemies();
  drawPlayer();
  drawHitExplosions();
  drawFloatingTexts();

    if (!isRunning && score === 0) {
      drawCenterText('PRESS START');
    }

    if (isPaused) {
      drawCenterText('PAUSED');
    }
  }

  function drawMaze() {
    for (let row = 0; row < GRID_ROWS; row += 1) {
      for (let col = 0; col < GRID_COLS; col += 1) {
        const x = col * TILE_SIZE;
        const y = row * TILE_SIZE;
        const tile = maze[row][col];

        ctx.fillStyle = '#020617';
        ctx.fillRect(x, y, TILE_SIZE, TILE_SIZE);

 if (tile === 'wall') {
  const wallImg = getWallImageForTile(row, col);

  if (wallImg && wallImg.complete && wallImg.naturalWidth > 0) {
    ctx.drawImage(wallImg, x + 1, y + 1, TILE_SIZE - 2, TILE_SIZE - 2);
  } else {
    // TODO: This is only a temporary first-frame placeholder while wall PNGs finish loading.
    // It intentionally uses cheese colors so players never see unrelated blue debug blocks.
    ctx.fillStyle = '#facc15';
    ctx.fillRect(x + 2, y + 2, TILE_SIZE - 4, TILE_SIZE - 4);

    ctx.fillStyle = 'rgba(120, 53, 15, 0.28)';
    ctx.beginPath();
    ctx.arc(x + TILE_SIZE * 0.35, y + TILE_SIZE * 0.35, 3, 0, Math.PI * 2);
    ctx.arc(x + TILE_SIZE * 0.68, y + TILE_SIZE * 0.58, 2.5, 0, Math.PI * 2);
    ctx.arc(x + TILE_SIZE * 0.48, y + TILE_SIZE * 0.78, 2, 0, Math.PI * 2);
    ctx.fill();

    ctx.strokeStyle = '#f59e0b';
    ctx.strokeRect(x + 2, y + 2, TILE_SIZE - 4, TILE_SIZE - 4);
  }

  continue;
}

        if (tile === 'nest') {
          ctx.fillStyle = 'rgba(124, 58, 237, 0.22)';
          ctx.fillRect(x + 2, y + 2, TILE_SIZE - 4, TILE_SIZE - 4);

          ctx.strokeStyle = 'rgba(250, 204, 21, 0.55)';
          ctx.lineWidth = 2;
          ctx.strokeRect(x + 5, y + 5, TILE_SIZE - 10, TILE_SIZE - 10);

          continue;
        }

        if (tile === 'crumb') {
          ctx.fillStyle = '#facc15';
          ctx.beginPath();
          ctx.arc(x + TILE_SIZE / 2, y + TILE_SIZE / 2, 3, 0, Math.PI * 2);
          ctx.fill();
        }

        if (tile === 'power') {
          ctx.fillStyle = '#fde68a';
          ctx.beginPath();
          ctx.arc(x + TILE_SIZE / 2, y + TILE_SIZE / 2, 8, 0, Math.PI * 2);
          ctx.fill();
          ctx.strokeStyle = '#f97316';
          ctx.stroke();
        }
      }
    }
  }

/**
 * Draws the rare Glyph Boost item on the board.
 * The bright backing ring makes the dark F.png readable on the dark maze.
 */
function drawGlyphBoostItem() {
  if (!glyphBoostItem) {
    return;
  }

  const x = glyphBoostItem.col * TILE_SIZE;
  const y = glyphBoostItem.row * TILE_SIZE;
  const centerX = x + TILE_SIZE / 2;
  const centerY = y + TILE_SIZE / 2;
  const pulse = 0.9 + Math.sin(performance.now() / 140) * 0.08;

  ctx.save();

  // Bright readable backing so the dark F.png does not disappear into the maze.
  ctx.shadowColor = '#facc15';
  ctx.shadowBlur = 20;
  ctx.fillStyle = 'rgba(250, 204, 21, 0.9)';
  ctx.beginPath();
  ctx.arc(centerX, centerY, TILE_SIZE * 0.46 * pulse, 0, Math.PI * 2);
  ctx.fill();

  // Purple inner ring to make it feel like a rare glyph/power item.
  ctx.shadowColor = '#a855f7';
  ctx.shadowBlur = 12;
  ctx.strokeStyle = '#a855f7';
  ctx.lineWidth = 3;
  ctx.beginPath();
  ctx.arc(centerX, centerY, TILE_SIZE * 0.36 * pulse, 0, Math.PI * 2);
  ctx.stroke();

  if (glyphBoostImage && glyphBoostImage.complete && glyphBoostImage.naturalWidth > 0) {
    ctx.shadowBlur = 0;
    ctx.drawImage(
      glyphBoostImage,
      x + 6,
      y + 6,
      TILE_SIZE - 12,
      TILE_SIZE - 12
    );
    ctx.restore();
    return;
  }

  // Fallback when the image is not loaded yet.
  ctx.shadowBlur = 0;
  ctx.fillStyle = '#111827';
  ctx.font = 'bold 14px Arial';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText('F', centerX, centerY);

  ctx.restore();
}

/**
 * Draws a clear active sign around the mouse while Glyph Boost is active.
 * This tells players the mouse is faster and protected.
 */
function drawGlyphBoostAura() {
  if (!isGlyphBoostActive()) {
    return;
  }

  const centerX = player.col * TILE_SIZE + TILE_SIZE / 2;
  const centerY = player.row * TILE_SIZE + TILE_SIZE / 2;
  const pulse = 0.5 + Math.sin(performance.now() / 120) * 0.12;

  ctx.save();
  ctx.strokeStyle = 'rgba(168, 85, 247, 0.95)';
  ctx.lineWidth = 3;
  ctx.shadowColor = '#a855f7';
  ctx.shadowBlur = 18;
  ctx.beginPath();
  ctx.arc(centerX, centerY, TILE_SIZE * pulse, 0, Math.PI * 2);
  ctx.stroke();

  ctx.fillStyle = '#f5d0fe';
  ctx.font = 'bold 10px Arial';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText('F BOOST', centerX, centerY - TILE_SIZE * 0.72);
  ctx.restore();
}

function drawPlayer() {
  const x = player.col * TILE_SIZE;
  const y = player.row * TILE_SIZE;

  if (isGlyphBoostActive()) {
    drawGlyphBoostAura();
  }

  if (cheeseImg.complete && cheeseImg.naturalWidth > 0) {
    ctx.drawImage(cheeseImg, x - 2, y - 4, TILE_SIZE + 4, TILE_SIZE + 6);
    return;
  }

    ctx.fillStyle = '#facc15';
    ctx.beginPath();
    ctx.arc(x + TILE_SIZE / 2, y + TILE_SIZE / 2, TILE_SIZE / 2 - 3, 0.2 * Math.PI, 1.8 * Math.PI);
    ctx.lineTo(x + TILE_SIZE / 2, y + TILE_SIZE / 2);
    ctx.fill();
  }

    /**
   * Returns the visual sprite for each Cheese Runner enemy.
   * Enemy logic stays unchanged; this only maps existing enemy names to existing img/space assets.
   */
  function getEnemyImage(enemy) {
    if (enemy.name.includes('Destroyer')) {
      return destroyerImg;
    }

    if (enemy.name.includes('Emperor')) {
      return emperorImg;
    }

    return invaderImg;
  }

  /**
   * Draws all Cheese Evils.
   * Normal state uses enemy sprites.
   * Vulnerable state uses a clear cyan/white "EAT" marker so players know enemies are safe to hunt.
   */
  function drawEnemies() {
    enemies.forEach(enemy => {
      const x = enemy.col * TILE_SIZE;
      const y = enemy.row * TILE_SIZE;
      const isVulnerable = isPowerModeActive();

      ctx.save();

      if (isVulnerable) {
        ctx.globalAlpha = 1;
        ctx.shadowColor = '#22d3ee';
        ctx.shadowBlur = 18;

        ctx.fillStyle = '#22d3ee';
        ctx.fillRect(x + 3, y + 3, TILE_SIZE - 6, TILE_SIZE - 6);

        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 3;
        ctx.strokeRect(x + 4, y + 4, TILE_SIZE - 8, TILE_SIZE - 8);

        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 11px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('EAT', x + TILE_SIZE / 2, y + TILE_SIZE / 2);

        ctx.restore();
        return;
      }

      const activeImg = getEnemyImage(enemy);

      if (activeImg && activeImg.complete && activeImg.naturalWidth > 0) {
        ctx.drawImage(activeImg, x - 3, y - 4, TILE_SIZE + 6, TILE_SIZE + 6);
      } else {
        ctx.fillStyle = enemy.color;
        ctx.beginPath();
        ctx.arc(x + TILE_SIZE / 2, y + TILE_SIZE / 2, TILE_SIZE / 2 - 3, Math.PI, 0);
        ctx.lineTo(x + TILE_SIZE - 3, y + TILE_SIZE - 3);
        ctx.lineTo(x + 3, y + TILE_SIZE - 3);
        ctx.closePath();
        ctx.fill();
      }

      ctx.restore();
    });
  }

  function drawCenterText(text) {
    ctx.save();
    ctx.fillStyle = 'rgba(0, 0, 0, 0.68)';
    ctx.fillRect(0, canvas.height / 2 - 42, canvas.width, 84);
    ctx.fillStyle = '#facc15';
    ctx.font = 'bold 28px system-ui, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(text, canvas.width / 2, canvas.height / 2 + 10);
    ctx.restore();
  }

  function setStatus(message) {
    if (statusEl) {
      statusEl.textContent = message;
    }
  }

function getCheesemanAudioContext() {
  if (cheesemanAudioContext) {
    return cheesemanAudioContext;
  }

  const AudioContextClass = window.AudioContext || window.webkitAudioContext;
  if (!AudioContextClass) {
    return null;
  }

  cheesemanAudioContext = new AudioContextClass();
  return cheesemanAudioContext;
}

function playSound(type) {
  if (window.NarrrfsSound && !window.NarrrfsSound.isEnabled()) {
    return;
  }

  try {
    const audioContext = getCheesemanAudioContext();
    if (!audioContext) {
      return;
    }

    if (audioContext.state === 'suspended') {
      audioContext.resume();
    }

    const soundMap = {
      crumb: { frequency: 520, endFrequency: 720, type: 'sine', volume: 0.16, duration: 0.08 },
      power: { frequency: 780, endFrequency: 1040, type: 'triangle', volume: 0.2, duration: 0.16 },
      enemy: { frequency: 220, endFrequency: 880, type: 'square', volume: 0.22, duration: 0.22 },
      hit: { frequency: 160, endFrequency: 80, type: 'sawtooth', volume: 0.22, duration: 0.22 },
      level: { frequency: 660, endFrequency: 990, type: 'triangle', volume: 0.2, duration: 0.24 }
    };

    const config = soundMap[type] || soundMap.crumb;
    const now = audioContext.currentTime;

    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();

    oscillator.type = config.type;
    oscillator.frequency.setValueAtTime(config.frequency, now);
    oscillator.frequency.exponentialRampToValueAtTime(config.endFrequency, now + config.duration);

    gainNode.gain.setValueAtTime(config.volume, now);
    gainNode.gain.exponentialRampToValueAtTime(0.001, now + config.duration);

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);

    oscillator.start(now);
    oscillator.stop(now + config.duration);
  } catch (error) {
    console.warn('⚠️ Cheeseman sound failed:', error);
  }
}

/**
 * Restarts Cheese Runner from the Game Over modal.
 * This clears the forced modal display style before starting a fresh run.
 */
function restartCheesemanGame() {
  if (modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.style.display = 'none';
  }

  resetGame();
  startGame();
}

window.restartCheesemanGame = restartCheesemanGame;


  function bindControls() {
    window.addEventListener('keydown', event => {
      const keys = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', ' ', 'a', 's', 'd', 'w', 'p', 'P', 'Enter'];

      if (keys.includes(event.key)) {
        event.preventDefault();
      }

      if (event.key === 'ArrowUp' || event.key.toLowerCase() === 'w') setDirection('up');
      if (event.key === 'ArrowDown' || event.key.toLowerCase() === 's') setDirection('down');
      if (event.key === 'ArrowLeft' || event.key.toLowerCase() === 'a') setDirection('left');
      if (event.key === 'ArrowRight' || event.key.toLowerCase() === 'd') setDirection('right');
      if (event.key === ' ' || event.key.toLowerCase() === 'p') togglePause();
      if (event.key === 'Enter') startGame();
    }, { passive: false });

    canvas.addEventListener('touchstart', event => {
      const touch = event.changedTouches[0];
      touchStartX = touch.clientX;
      touchStartY = touch.clientY;
      event.preventDefault();
    }, { passive: false });

    canvas.addEventListener('touchend', event => {
      const touch = event.changedTouches[0];
      const deltaX = touch.clientX - touchStartX;
      const deltaY = touch.clientY - touchStartY;

      if (Math.abs(deltaX) > Math.abs(deltaY)) {
        setDirection(deltaX > 0 ? 'right' : 'left');
      } else {
        setDirection(deltaY > 0 ? 'down' : 'up');
      }

      event.preventDefault();
    }, { passive: false });

    document.querySelectorAll('[data-cheeseman-dir]').forEach(button => {
      button.addEventListener('click', () => setDirection(button.dataset.cheesemanDir));
      button.addEventListener('touchstart', event => {
        setDirection(button.dataset.cheesemanDir);
        event.preventDefault();
      }, { passive: false });
    });

    startBtn?.addEventListener('click', startGame);
    pauseBtn?.addEventListener('click', togglePause);
    restartBtn?.addEventListener('click', () => {
      resetGame();
      startGame();
    });
playAgainBtn?.addEventListener('click', event => {
  event.preventDefault();
  event.stopPropagation();
  restartCheesemanGame();
});

playAgainBtn?.addEventListener('touchend', event => {
  event.preventDefault();
  event.stopPropagation();
  restartCheesemanGame();
}, { passive: false });
  }

  async function initCheeseman() {
    validateLevelTemplates();
    bindControls();
    await fetchCheesemanUserRoleNames();
    resetGame();
  }

  window.testCheesemanRoleFeatures = function testCheesemanRoleFeatures() {
    console.log('🏆 Cheese Runner role test');
    console.log('Roles:', cheesemanUserRoleNames);
    console.log('Primary role:', getCheesemanPrimaryRoleName());
    console.log('Multiplier:', getCheesemanRoleScoreMultiplier());
    console.log('Canvas classes:', canvas.className);
  };

  window.cheesemanDebugState = function cheesemanDebugState() {
    return {
      game: GAME_KEY,
      score,
      dspoinc: calculateDspoincReward(),
      level,
      lives,
      crumbsRemaining,
      roleMultiplier: getCheesemanRoleScoreMultiplier(),
      primaryRole: getCheesemanPrimaryRoleName(),
      identity: resolveDiscordIdentity()
    };
  };

  initCheeseman();
})();