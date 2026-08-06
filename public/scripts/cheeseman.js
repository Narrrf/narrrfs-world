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

// Power Cheese reward cap.
// Plain language for DEVS:
// Skilled players may eat several enemies during one Power Cheese,
// but the reward is capped so DSPOINC cannot be farmed endlessly.
const POWER_CHEESE_ENEMY_REWARD_CAP = 3;

const BASE_TICK_MS = 175;
const MIN_TICK_MS = 105;
  
  const DSPOINC_CONVERSION_RATE = 25;

  const COMBO_WINDOW_MS = 2200;
const COMBO_MAX_STACK = 10;
const COMBO_PICKUPS_PER_STACK = 10;
const COMBO_BONUS_PER_STACK = 0.15;

const ENEMY_MOVE_DELAY_BY_LEVEL = [5, 5, 4, 4, 3, 3, 2, 2, 1, 1];
const ENEMY_SMART_CHANCE_BY_LEVEL = [0.08, 0.12, 0.18, 0.25, 0.34, 0.44, 0.55, 0.66, 0.76, 0.86];

// 🧠 CheeseMind AI memory settings.
// Plain language for DEVS:
// CheeseMind learns only during the current run. It does not persist player behavior,
// does not touch the DB, and does not change scoring or DSPOINC.
const CHEESEMIND_PLAYER_TRAIL_LIMIT = 32;
const CHEESEMIND_RECENT_TURN_LIMIT = 16;
const CHEESEMIND_DEBUG_MAP_LIMIT = 12;

// 🧠 CheeseMind tactical AI settings.
// Plain language for DEVS:
// These values let enemies become smarter by level without becoming unfair at level 1.
const CHEESEMIND_PREDICTION_MIN_LEVEL = 3;
const CHEESEMIND_CONTROL_MIN_LEVEL = 5;
const CHEESEMIND_TUNNEL_COUNTER_MIN_LEVEL = 7;
const CHEESEMIND_MAX_PREDICTION_TILES = 6;
const CHEESEMIND_RANDOM_FALLBACK_CHANCE = 0.18;

// 🧠 CheeseMind team coordination settings.
// Plain language for DEVS:
// Patch 3 makes enemies act like a squad instead of three isolated chasers.
// This changes target selection only. It does not change DB, API, score, or DSPOINC.
const CHEESEMIND_COORDINATION_MIN_LEVEL = 6;
const CHEESEMIND_CUTOFF_MIN_LEVEL = 6;
const CHEESEMIND_ASSIGNMENT_OVERLAP_PENALTY = 1.15;

// 🧠 CheeseMind Observatory settings.
// Plain language for DEVS:
// These messages make enemy thinking visible to testers without changing movement, DB, API, scoring, or DSPOINC.
const CHEESEMIND_OBSERVATORY_STATUS_COOLDOWN_MS = 4500;

// 🧠 CheeseMind visual counterplay settings.
// Plain language for DEVS:
// These canvas hints let players understand enemy intent without opening console.
// They are visual-only and must never affect score, DSPOINC, DB, API, or movement.
const CHEESEMIND_VISUAL_SIGNAL_MIN_LEVEL = 3;
const CHEESEMIND_TARGET_MARKER_RADIUS = 8;
const CHEESEMIND_ENEMY_BADGE_RADIUS = 7;

// 🧠 CheeseMind production concealment.
// Plain language for DEVS:
// The AI stays active, but exact target lines/markers must not expose the algorithm
// to normal players. Local debug can turn full visuals back on for testing.
const CHEESEMIND_PRODUCTION_FULL_VISUALS_ENABLED = false;
const CHEESEMIND_LOCAL_VISUAL_DEBUG_STORAGE_KEY = 'narrrfs_cheesemind_visual_debug';

const GLYPH_BOOST_IMAGE_SRC = 'img/cheeseman/F.png';
const GLYPH_BOOST_DURATION_MS = 6000;
const GLYPH_BOOST_DROP_CHANCE_ON_LEVEL_START = 0.18;
const GLYPH_BOOST_SCORE_BONUS = 100;
const GLYPH_BOOST_TICK_SPEED_MULTIPLIER = 0.65;

const CONFUSION_MUSHROOM_IMAGE_SRC = 'img/cheeseman/mushroom.png';
const CONFUSION_MUSHROOM_DURATION_MS = 5000;
const CONFUSION_MUSHROOM_DROP_CHANCE_ON_LEVEL_START = 0.44;
const CONFUSION_MUSHROOM_SCORE_PENALTY = 0;

const TILE_TUNNEL = 'tunnel';
const WRAP_TUNNELS_ENABLED = true;

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

  const MAX_LEVEL_TEMPLATE_COUNT = 20;
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
    'To......#........oT',
    '#.###...#...###...#',
    '#.................#',
    '###.##....##.###..#',
    '#...#..##..#......#',
    'T.#.#......#.#....T',
    '#.#...####...#....#',
    '#.###...#...###.#.#',
    '#.....#.NNN.#...#.#',
    '###.#...NNN...#.#.#',
    '#...#.#.NNN.#.#...#',
    '#.#.###...###.###.#',
    'T.................T',
    '#.###.#####.###.#.#',
    '#o....#..P..#....o#',
    '###.#.#.###.#.#.###',
    '#...#...#...#.....#',
    '#.###...#...###.#.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To..#........#...oT',
    '#.#.#..####..#.#..#',
    '#.#............#..#',
    '#.###..##.##..###.#',
    'T.....#....#......T',
    '###.#.####.#.####.#',
    '#...#......#....#.#',
    '#.###...####...#..#',
    '#.#....NNNNN...#..#',
    '#...##.NNNNN.##...#',
    '#...#..NNNNN....#.#',
    '###.###....####.#.#',
    'T.....#.......#...T',
    '#.###.#..#..#.###.#',
    '#o..#....P....#..o#',
    '###.###.###.###.###',
    '#.....#.....#.....#',
    '#.###.....#.....#.#',
    'T........#........T',
    '###################'
  ],
  [
    '###################',
    'To.....#...#.....oT',
    '#.###..#.#.#..###.#',
    '#...#.........#...#',
    '###.#.###.###.#.###',
    '#...#...#.#...#...#',
    '#.###...#.#...###.#',
    'T.......#.#.......T',
    '#.###.###.###.###.#',
    '#...#...NNN...#...#',
    '###.###.NNN.###.###',
    '#...#...NNN...#...#',
    '#.###.###.###.###.#',
    'T.......#.#.......T',
    '#.###...#.#...###.#',
    '#o....#..P..#....o#',
    '###.#.#.###.#.#.###',
    '#...#.........#...#',
    '#.#.###...###.#.#.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To...............oT',
    '###.###.....###.###',
    '#...#.....#.....#.#',
    '#.#.#.###.#.###.#.#',
    'T.#.....#...#.....T',
    '#.###...###...###.#',
    '#.....#.....#.....#',
    '###.#.#.###.#.#.###',
    '#...#...NNN...#...#',
    '#.###...NNN...###.#',
    '#...#...NNN...#...#',
    '###.#.###.###.#.###',
    'T.....#.....#.....T',
    '#.###...###...###.#',
    '#o......#P#......o#',
    '###.###.#.#.###.###',
    '#.....#.....#.....#',
    '#.###...###...###.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To..#...#.#...#..oT',
    '#.#.#.#.#.#.#.#.#.#',
    '#.#...#.....#...#.#',
    '#.###...###...###.#',
    'T.....#.....#.....T',
    '###.#.#.###.#.#.###',
    '#...#.........#...#',
    '#.###...###...###.#',
    '#.....#.NNN.#.....#',
    '###.#...NNN...#.###',
    '#.....#.NNN.#.....#',
    '#.###...###...###.#',
    '#...#.........#...#',
    '###.#...###...#.###',
    '#o....#..P..#....o#',
    '#.###.#.###.#.###.#',
    'T...#...#.#...#...T',
    '#.#.###.#.#.###.#.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To....#.....#....oT',
    '#.###.#.###.#.###.#',
    '#.#...#.....#...#.#',
    '#.#.###.#.#.###.#.#',
    'T.....#.....#.....T',
    '#####.###.###.#####',
    '#.................#',
    'T.#.#.###.###.#.#.T',
    '#.#...NNNNN...#.#.#',
    '#.###.NNNNN.###.#.#',
    '#.#...NNNNN...#...#',
    'T.#.#.###.###.#.#.T',
    '#.................#',
    '#####.###.###.#####',
    '#o......P......#.o#',
    '#.###.#####.###.#.#',
    '#...#.......#.....#',
    '#.#.###...###.###.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To....#..#..#....oT',
    '#.##..#..#..#..##.#',
    '#...#.........#...#',
    '###.#.###.###.#.###',
    'T.....#.....#.....T',
    '#.###.#.###.#.###.#',
    '#.#...#.....#...#.#',
    '#.#.###.###.###.#.#',
    '#...#..NNNNN..#...#',
    '###.#.#NNNNN#.#.###',
    '#...#..NNNNN..#...#',
    '#.#.###.###.###.#.#',
    'T.#...#.....#...#.T',
    '#.###.###.###.###.#',
    '#o....#..P..#....o#',
    '###.#.#.###.#.#.###',
    '#...#.........#...#',
    '#.#.###...###.#.#.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To#.....#.....#..oT',
    '#.#.###.#.###.#.#.#',
    '#...#...#...#...#.#',
    '###.#.###.#.###.#.#',
    'T...#.......#.....T',
    '#.###...###...###.#',
    '#.....#.....#.....#',
    '###.#.###.###.#.###',
    '#...#...NNN...#...#',
    '#.###...NNN...###.#',
    '#...#...NNN...#...#',
    '###.#.###.###.#.###',
    'T.....#.....#.....T',
    '#.###...###...###.#',
    '#o....#..P..#....o#',
    '#.###.#.###.#.###.#',
    '#...#.........#...#',
    '#.#.###...###.#.#.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To...............oT',
    '#.##.#.#####.#.##.#',
    '#....#.......#....#',
    '####.###...###.####',
    'T.......#.#.......T',
    '#.###...#.#...###.#',
    '#.#.....#.#.....#.#',
    '#.#.###.....###.#.#',
    '#...#..NNNNN..#...#',
    '###.#.#NNNNN#.#.###',
    '#...#..NNNNN..#...#',
    '#.#.###.....###.#.#',
    'T.#.....#.#.....#.T',
    '#.###...#.#...###.#',
    '#o......P......#.o#',
    '####.###.#.###.####',
    '#....#.......#....#',
    '#.##.#...#...#.##.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To..#...#.#...#..oT',
    '#.#.#.#.#.#.#.#.#.#',
    '#.#...#.....#...#.#',
    '#.###...###...###.#',
    'T.....#.....#.....T',
    '###.#.#.###.#.#.###',
    '#...#...#.#...#...#',
    '#.###.###.###.###.#',
    '#.#....NNNNN....#.#',
    '#.#.##.NNNNN.##.#.#',
    '#.#....NNNNN....#.#',
    '#.###.###.###.###.#',
    'T...#...#.#...#...T',
    '###.#.#.#.#.#.#.###',
    '#o....#..P..#....o#',
    '#.###...###...###.#',
    '#.....#.....#.....#',
    '#.###...###...###.#',
    'T.................T',
    '###################'
  ],
    [
    '###################',
    'To.....#...#.....oT',
    '#.###..#...#..###.#',
    '#...#.........#...#',
    '#.#.###.#.#.###.#.#',
    'T.#.....#.#.....#.T',
    '#.###...#.#...###.#',
    '#.....#.....#.....#',
    '#.###.#.###.#.###.#',
    '#...#...NNN...#...#',
    '###...#.NNN.#...###',
    '#...#...NNN...#...#',
    '#.###.#.###.#.###.#',
    '#.....#.....#.....#',
    'T.###...###...###.T',
    '#o....#..P..#....o#',
    '#.###.#.###.#.###.#',
    '#...#.........#...#',
    '#.###...###...###.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To...............oT',
    '#.###.###.###.###.#',
    '#...#.....#.....#.#',
    '###.#.###...###.#.#',
    'T...#...#...#...#.T',
    '#.###.#.###.#.###.#',
    '#.....#.....#.....#',
    '#.#.###.###.###.#.#',
    '#.#....NNNNN....#.#',
    '#...##.NNNNN.##...#',
    '#.#....NNNNN....#.#',
    '#.#.###.###.###.#.#',
    '#.....#.....#.....#',
    'T.###...#.#...###.T',
    '#o......P......#.o#',
    '###.###.#.#.###.###',
    '#.....#.....#.....#',
    '#.###...###...###.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To..#.........#..oT',
    '#.#.#.###.###.#.#.#',
    '#.#.....#.#.....#.#',
    '#.###.#.#.#.#.###.#',
    'T.....#.....#.....T',
    '###.#.###.###.#.###',
    '#...#.........#...#',
    '#.###.###.###.###.#',
    '#.....#.NNN.#.....#',
    '###.#...NNN...#.###',
    '#.....#.NNN.#.....#',
    '#.###.###.###.###.#',
    '#...#.........#...#',
    'T.#.###.....###.#.T',
    '#o....#..P..#....o#',
    '#.###.#.###.#.###.#',
    '#...#...#.#...#...#',
    '#.#.###.#.#.###.#.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To....#.....#....oT',
    '#.##..#.....#..##.#',
    '#...#...###...#...#',
    '###.#.#.....#.#.###',
    'T.....#.###.#.....T',
    '#.###.#.....#.###.#',
    '#.#.............#.#',
    '#.#.###.###.###.#.#',
    '#...#..NNNNN..#...#',
    '###...#NNNNN#...###',
    '#...#..NNNNN..#...#',
    '#.#.###.###.###.#.#',
    '#.#.............#.#',
    'T.###.#.....#.###.T',
    '#o......P......#.o#',
    '#.###.###.###.###.#',
    '#.....#.....#.....#',
    '#.###...###...###.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To#.....#.#.....#oT',
    '#.#.###.#.#.###.#.#',
    '#...#...#.#...#...#',
    '###.#.#.....#.#.###',
    'T...#.#.###.#.#...T',
    '#.###.#.....#.###.#',
    '#.....###.###.....#',
    '#.###...#.#...###.#',
    '#...#...NNN...#...#',
    '#.###.#.NNN.#.###.#',
    '#...#...NNN...#...#',
    '#.###...#.#...###.#',
    '#.....###.###.....#',
    'T.###.#.....#.###.T',
    '#o....#..P..#....o#',
    '###.#.#.###.#.#.###',
    '#...#.........#...#',
    '#.#.###...###.#.#.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To...............oT',
    '#.###.#.###.#.###.#',
    '#.....#.....#.....#',
    '#.#.#####.#####.#.#',
    'T.#.............#.T',
    '#.###.###.###.###.#',
    '#...#...#.#...#...#',
    '###.#.#.....#.#.###',
    '#...#..NNNNN..#...#',
    '#.###.#NNNNN#.###.#',
    '#...#..NNNNN..#...#',
    '###.#.#.....#.#.###',
    '#...#...#.#...#...#',
    'T.###.###.###.###.T',
    '#o......P......#.o#',
    '#.###.#####.###.#.#',
    '#.....#.....#.....#',
    '#.###...###...###.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To..#...#.#...#..oT',
    '#.#.#.#.#.#.#.#.#.#',
    '#.#...#.....#...#.#',
    '#.###.###.###.###.#',
    'T.....#.....#.....T',
    '###.#...###...#.###',
    '#...#.........#...#',
    '#.#####.#.#.#####.#',
    '#.....#.NNN.#.....#',
    '#.###...NNN...###.#',
    '#.....#.NNN.#.....#',
    '#.#####.#.#.#####.#',
    '#...#.........#...#',
    'T.###...###...###.T',
    '#o....#..P..#....o#',
    '###.#.#.###.#.#.###',
    '#...#...#.#...#...#',
    '#.#.###.#.#.###.#.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To.....#...#.....oT',
    '#.###..#.#.#..###.#',
    '#...#.........#...#',
    '###.#.#######.#.###',
    'T...#...#.#...#...T',
    '#.###...#.#...###.#',
    '#.....#.....#.....#',
    '#.#.###.###.###.#.#',
    '#.#....NNNNN....#.#',
    'T...##.NNNNN.##...T',
    '#.#....NNNNN....#.#',
    '#.#.###.###.###.#.#',
    '#.....#.....#.....#',
    'T.###...#.#...###.T',
    '#o....#..P..#....o#',
    '#.###.#.###.#.###.#',
    '#...#.........#...#',
    '#.#.###...###.#.#.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To...............oT',
    '#.##.#.#####.#.##.#',
    '#....#.......#....#',
    '####...###.###...##',
    'T.......#.#.......T',
    '#.###...#.#...###.#',
    '#.#.....#.#.....#.#',
    '#.#.###.....###.#.#',
    '#...#..NNNNN..#...#',
    '###.#.#NNNNN#.#.###',
    '#...#..NNNNN..#...#',
    '#.#.###.....###.#.#',
    '#.#.....#.#.....#.#',
    'T.###...#.#...###.T',
    '#o......P......#.o#',
    '####.###.#.###.####',
    '#....#.......#....#',
    '#.##.#...#...#.##.#',
    'T.................T',
    '###################'
  ],
  [
    '###################',
    'To..#...#.#...#..oT',
    '#.#.#.#...#.#.#.#.#',
    '#.#...###.###...#.#',
    '#.###...#.#...###.#',
    'T.....#.....#.....T',
    '###.#.#.###.#.#.###',
    '#...#.........#...#',
    '#.###.###.###.###.#',
    '#.#....NNNNN....#.#',
    '#...##.NNNNN.##...#',
    '#.#....NNNNN....#.#',
    '#.###.###.###.###.#',
    'T...#...#.#...#...T',
    '###.#.#.#.#.#.#.###',
    '#o....#..P..#....o#',
    '#.###...###...###.#',
    '#.....#.....#.....#',
    '#.###...###...###.#',
    'T.................T',
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
  {
    row: 9,
    col: 9,
    color: '#ef4444',
    name: 'Cheese Destroyer',
    strategyRole: 'hunter',
    strategyLabel: 'Pressure Hunter'
  },
  {
    row: 9,
    col: 8,
    color: '#a855f7',
    name: 'Cheese Emperor',
    strategyRole: 'predictor',
    strategyLabel: 'Route Predictor'
  },
  {
    row: 9,
    col: 10,
    color: '#22c55e',
    name: 'Cheese Invader',
    strategyRole: 'controller',
    strategyLabel: 'Lane Controller'
  }
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

  /**
   * Partner gameplay iframe mode.
   *
   * Plain language for DEVS FOR DECADES:
   * When Cheese Runner is opened with a Narrrfs partner session token, the
   * game submits only to the isolated Partner Bridge close-session API.
   * It must not call the normal Cheese Runner DSPOINC / leaderboard save path.
   */
  const cheesemanPartnerSessionToken =
    new URLSearchParams(window.location.search).get('session') || '';

  const isCheesemanPartnerMode =
    /^pgst_[a-f0-9]{48,80}$/i.test(String(cheesemanPartnerSessionToken || ''));

  const cheesemanPartnerClientRunId =
    isCheesemanPartnerMode
      ? `run-${Date.now()}-${Math.random().toString(16).slice(2, 10)}`
      : '';

const glyphBoostImage = new Image();
glyphBoostImage.src = GLYPH_BOOST_IMAGE_SRC;

const confusionMushroomImage = new Image();
confusionMushroomImage.src = CONFUSION_MUSHROOM_IMAGE_SRC;

const cheeseImg = new Image();
cheeseImg.src = 'img/cheeseman/cheeseman1.png';

if (isCheesemanPartnerMode) {
  document.body.classList.add('cheeseman-partner-mode');

  if (statusEl) {
    statusEl.textContent = 'Partner iframe mode active. Play normally; final score closes the partner session only.';
  }

  if (roleEl) {
    roleEl.textContent = 'Partner Mode: no Narrrfs DSPOINC / leaderboard write';
  }

  const playerNameEl = document.getElementById('cheeseman-player-name');
  if (playerNameEl) {
    playerNameEl.textContent = 'Samuzi Partner Session';
  }

  const loginPromptEl = document.getElementById('cheeseman-login-prompt');
  if (loginPromptEl) {
    loginPromptEl.classList.add('hidden');
  }

  const seasonBannerEl = document.getElementById('cheeseman-season-banner');
  if (seasonBannerEl) {
    const partnerNotice = document.createElement('div');
    partnerNotice.className = 'mt-4 rounded-2xl border border-cyan-300/40 bg-cyan-950/50 p-3 text-sm text-cyan-100';
    partnerNotice.textContent = 'Partner iframe mode: this run closes an isolated Samuzi partner session. It does not credit Narrrfs DSPOINC or write the public Narrrfs leaderboard.';
    seasonBannerEl.appendChild(partnerNotice);
  }

  const modalSubtitleEl = document.getElementById('cheeseman-modal-subtitle');
  if (modalSubtitleEl) {
    modalSubtitleEl.textContent = 'Your Cheese Runner result closed the isolated Samuzi partner session only.';
  }
}

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
let cheeseMind = createCheeseMindState();
let cheeseMindLastObservatoryStatusAt = 0;
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
let powerCheeseEnemyRewardsUsed = 0;
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

let confusionMushroomItem = null;
let confusionMushroomUntil = 0;

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

  function getWrappedPosition(row, col) {
  if (!WRAP_TUNNELS_ENABLED) {
    return { row, col };
  }

  if (col < 0) {
    return { row, col: GRID_COLS - 1 };
  }

  if (col >= GRID_COLS) {
    return { row, col: 0 };
  }

  return { row, col };
}

function isTunnelRow(row) {
  return maze[row]?.[0] === TILE_TUNNEL && maze[row]?.[GRID_COLS - 1] === TILE_TUNNEL;
}

function getWrappedPosition(row, col) {
  if (!WRAP_TUNNELS_ENABLED || !isTunnelRow(row)) {
    return { row, col };
  }

  if (col < 0) {
    return { row, col: GRID_COLS - 1 };
  }

  if (col >= GRID_COLS) {
    return { row, col: 0 };
  }

  return { row, col };
}

/**
 * Creates a safe looping background music controller for Narrrfs games.
 *
 * Plain language for DEVS:
 * Browser audio cannot autoplay before a user action. This controller starts
 * only after game start / player interaction, follows the global Narrrfs sound
 * toggle, pauses on game pause, and stops when the run ends.
 */
window.NarrrfsGameMusicFactory = window.NarrrfsGameMusicFactory || function createNarrrfsGameMusicController(config) {
  let audio = null;
  let wantsPlayback = false;

  function isSoundAllowed() {
  if (window.NarrrfsAudio && typeof window.NarrrfsAudio.isMusicEnabled === 'function') {
    return window.NarrrfsAudio.isMusicEnabled();
  }

  if (window.NarrrfsSound && typeof window.NarrrfsSound.isEnabled === 'function') {
    return window.NarrrfsSound.isEnabled();
  }

  return localStorage.getItem('narrrfs_music_enabled') !== 'false';
}

  function getAudio() {
    if (audio) {
      return audio;
    }

    audio = new Audio(config.src);
    audio.loop = true;
    audio.preload = 'auto';
    audio.volume = typeof config.volume === 'number' ? config.volume : 0.22;

    return audio;
  }

  async function start() {
    wantsPlayback = true;

    if (!isSoundAllowed() || document.hidden) {
      pause();
      return;
    }

    try {
      const music = getAudio();
      await music.play();
    } catch (error) {
      console.warn(`🎵 ${config.label} music could not start yet:`, error);
    }
  }

  /**
 * Pauses background music.
 *
 * Plain language for DEVS:
 * keepWanted=true is used when sound is temporarily blocked by tab visibility
 * or global sound toggle. keepWanted=false is used by actual game pause so the
 * sync listener cannot accidentally restart music while the game is paused.
 */
function pause(keepWanted = true) {
  if (!keepWanted) {
    wantsPlayback = false;
  }

  if (!audio) {
    return;
  }

  audio.pause();
}

/**
 * Suspends music because the game itself is paused.
 */
function suspend() {
  pause(false);
}

  function stop() {
    wantsPlayback = false;

    if (!audio) {
      return;
    }

    audio.pause();
    audio.currentTime = 0;
  }

  function sync() {
    if (!wantsPlayback) {
      return;
    }

    if (!isSoundAllowed() || document.hidden) {
      pause();
      return;
    }

    start();
  }

  window.addEventListener('storage', sync);
window.addEventListener('narrrfs:music-toggle', sync);
window.addEventListener('narrrfs:sound-toggle', sync);
document.addEventListener('visibilitychange', sync);

  return {
  start,
  pause,
  suspend,
  stop,
  sync
};
};

const cheeseRunnerMusicController = window.NarrrfsGameMusicFactory({
  label: 'Cheese Runner',
  src: 'sounds/music/cheese-runner.mp3',
  volume: 0.2
});


  function collectConfusionMushroomItem() {
  if (!confusionMushroomItem) {
    return;
  }

  if (player.row !== confusionMushroomItem.row || player.col !== confusionMushroomItem.col) {
    return;
  }

  activateConfusionMushroom();
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

  function applyConfusionToDirection(direction) {
  if (!isConfusionMushroomActive()) {
    return direction;
  }

  return {
    row: direction.row * -1,
    col: direction.col * -1
  };
}

function drawConfusionAura() {
  if (!isConfusionMushroomActive()) {
    return;
  }

  const centerX = player.col * TILE_SIZE + TILE_SIZE / 2;
  const centerY = player.row * TILE_SIZE + TILE_SIZE / 2;

  ctx.save();
  ctx.strokeStyle = 'rgba(239, 68, 68, 0.95)';
  ctx.lineWidth = 3;
  ctx.shadowColor = '#ef4444';
  ctx.shadowBlur = 16;
  ctx.beginPath();
  ctx.arc(centerX, centerY, TILE_SIZE * 0.55, 0, Math.PI * 2);
  ctx.stroke();

  ctx.fillStyle = '#fecaca';
  ctx.font = 'bold 10px Arial';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText('CONFUSED', centerX, centerY - TILE_SIZE * 0.78);
  ctx.restore();
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

/**
 * Returns true while the Confusion Mushroom poison effect is active.
 * During this time, player controls are reversed.
 */
function isConfusionMushroomActive() {
  return performance.now() < confusionMushroomUntil;
}

/**
 * Clears the Confusion Mushroom item and active poison timer.
 */
function clearConfusionMushroom() {
  confusionMushroomUntil = 0;
  confusionMushroomItem = null;
}

/**
 * Activates the Confusion Mushroom poison effect.
 * This does not change score directly; the danger is reversed movement.
 */
function activateConfusionMushroom() {
  confusionMushroomUntil = performance.now() + CONFUSION_MUSHROOM_DURATION_MS;
  confusionMushroomItem = null;

  setStatus('🍄 Confusion Mushroom! Controls reversed!');
  playSound('hit');
}

/**
 * Ends Confusion Mushroom when its timer expires.
 */
function updateConfusionMushroom() {
  if (!confusionMushroomUntil) {
    return;
  }

  if (isConfusionMushroomActive()) {
    return;
  }

  confusionMushroomUntil = 0;
  setStatus('Confusion faded. Controls normal again.');
}

/**
 * Spawns the Confusion Mushroom on a safe visible board tile.
 * It never spawns inside walls, enemy nests, the player tile, enemy tiles, or the active Glyph Boost tile.
 */
function spawnConfusionMushroomItem() {
  confusionMushroomItem = null;

  if (Math.random() > CONFUSION_MUSHROOM_DROP_CHANCE_ON_LEVEL_START) {
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

      if (glyphBoostItem && row === glyphBoostItem.row && col === glyphBoostItem.col) {
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
    console.warn('🍄 Confusion Mushroom: no valid spawn tile found.');
    return;
  }

  confusionMushroomItem = validTiles[Math.floor(Math.random() * validTiles.length)];
  console.log('🍄 Confusion Mushroom spawned at:', confusionMushroomItem);
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
    if (dspoincEl) {
      dspoincEl.textContent = isCheesemanPartnerMode
        ? 'Partner'
        : `$${dspoincReward.toLocaleString()}`;
    }
    if (levelEl) levelEl.textContent = String(level);
    if (livesEl) livesEl.textContent = '🧀'.repeat(Math.max(0, lives)) || '0';
        updateComboDisplay();

    if (roleEl) {
      if (isCheesemanPartnerMode) {
        roleEl.textContent = 'Partner Mode: no Narrrfs DSPOINC / leaderboard write';
        roleEl.className = 'font-bold text-cyan-300';
        return;
      }

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

/**
 * Creates a fresh CheeseMind memory object for the current run.
 * Plain language for DEVS:
 * This is short-term AI memory only. It is reset every game and never saved to DB.
 */
function createCheeseMindState() {
  return {
  playerTrail: [],
  visitedHeatmap: {},
  tunnelUsage: {},
  recentTurns: [],
  dangerEscapes: [],
  powerCheeseRushes: 0,
  lastKnownPlayerTile: null,
  lastRecordedDirection: null,
  ticksObserved: 0,
  coordinationTick: 0,
  enemyAssignments: {}
};
}

/**
 * Resets CheeseMind memory for a fresh run.
 */
function resetCheeseMind() {
  cheeseMind = createCheeseMindState();
}

/**
 * Builds a stable row/column key for CheeseMind maps.
 */
function getCheeseMindTileKey(row, col) {
  return `${row},${col}`;
}

/**
 * Adds one visit to a CheeseMind count map.
 */
function incrementCheeseMindCounter(counterMap, key) {
  counterMap[key] = Number(counterMap[key] || 0) + 1;
}

/**
 * Records the mouse position and habits for CheeseMind.
 * Plain language for DEVS:
 * This does not move enemies yet. It only builds the memory layer that later AI uses.
 */
function recordCheeseMindPlayerStep() {
  if (!player || !cheeseMind) {
    return;
  }

  const tileKey = getCheeseMindTileKey(player.row, player.col);
  const previousTrailEntry = cheeseMind.playerTrail[cheeseMind.playerTrail.length - 1];

  if (previousTrailEntry && previousTrailEntry.key === tileKey) {
    return;
  }

  const tile = maze[player.row]?.[player.col] || 'unknown';
  const directionName = getDirectionName(currentDirection);

  cheeseMind.ticksObserved += 1;
  cheeseMind.lastKnownPlayerTile = {
    row: player.row,
    col: player.col,
    key: tileKey,
    tile
  };

  cheeseMind.playerTrail.push({
    row: player.row,
    col: player.col,
    key: tileKey,
    tile,
    direction: directionName,
    tick: cheeseMind.ticksObserved
  });

  if (cheeseMind.playerTrail.length > CHEESEMIND_PLAYER_TRAIL_LIMIT) {
    cheeseMind.playerTrail.shift();
  }

  incrementCheeseMindCounter(cheeseMind.visitedHeatmap, tileKey);

  if (tile === TILE_TUNNEL) {
    incrementCheeseMindCounter(cheeseMind.tunnelUsage, tileKey);
  }

  if (directionName && directionName !== cheeseMind.lastRecordedDirection) {
    cheeseMind.recentTurns.push({
      direction: directionName,
      row: player.row,
      col: player.col,
      tick: cheeseMind.ticksObserved
    });

    if (cheeseMind.recentTurns.length > CHEESEMIND_RECENT_TURN_LIMIT) {
      cheeseMind.recentTurns.shift();
    }

    cheeseMind.lastRecordedDirection = directionName;
  }
}

/**
 * Converts a direction object back into its readable name.
 */
function getDirectionName(direction) {
  if (!direction) {
    return '';
  }

  const entry = Object.entries(DIRECTIONS).find(([, value]) => {
    return value.row === direction.row && value.col === direction.col;
  });

  return entry ? entry[0] : '';
}

/**
 * Returns the most-used CheeseMind map entries for debugging.
 */
function getTopCheeseMindCounterEntries(counterMap, limit = CHEESEMIND_DEBUG_MAP_LIMIT) {
  return Object.entries(counterMap)
    .map(([key, count]) => ({ key, count }))
    .sort((left, right) => right.count - left.count)
    .slice(0, limit);
}

/**
 * Builds a safe debug snapshot of CheeseMind memory.
 */
function getCheeseMindDebugState() {
  return {
    enabled: true,
    version: 'foundation-1',
    ticksObserved: cheeseMind.ticksObserved,
    lastKnownPlayerTile: cheeseMind.lastKnownPlayerTile,
    playerTrailLength: cheeseMind.playerTrail.length,
    playerTrail: cheeseMind.playerTrail.slice(-8),
    recentTurns: cheeseMind.recentTurns.slice(-8),
    topVisitedTiles: getTopCheeseMindCounterEntries(cheeseMind.visitedHeatmap),
    tunnelUsage: getTopCheeseMindCounterEntries(cheeseMind.tunnelUsage),
coordinationTick: cheeseMind.coordinationTick,
enemyAssignments: cheeseMind.enemyAssignments,
enemyRoles: enemies.map(enemy => ({
  name: enemy.name,
  role: enemy.strategyRole,
  label: enemy.strategyLabel,
  lastTarget: enemy.lastCheeseMindTarget || null,
  lastDecision: enemy.lastCheeseMindDecision || null
}))
  };
}

function buildMaze() {
  crumbsRemaining = 0;

  maze = getCurrentLevelTemplate().map(rowText => rowText.split('').map(cell => {
    if (cell === '#') return 'wall';

    if (cell === 'N') return 'nest';

    if (cell === 'T') {
  // Portals are movement tiles only.
  // They must never count as hidden cheese or required level-clear collectibles.
  return TILE_TUNNEL;
}

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
  strategyRole: start.strategyRole,
  strategyLabel: start.strategyLabel,
  direction: Object.values(DIRECTIONS)[index % 4],
  isStunned: false,
  respawnLockTicks: ENEMY_RESPAWN_LOCK_TICKS
};
    });
  }

function resetGame() {
  clearGameTimer();
  cheeseRunnerMusicController.stop();

  score = 0;
  level = 1;
  lives = 3;
  isRunning = false;
  isPaused = false;
  hasScoreBeenSaved = false;
  powerModeUntil = 0;
powerCheeseEnemyRewardsUsed = 0;
glyphBoostUntil = 0;
  glyphBoostItem = null;
  confusionMushroomUntil = 0;
confusionMushroomItem = null;
  comboStack = 0;
  comboPickupCount = 0;
  lastComboCollectAt = 0;
  floatingTexts = [];
  hitExplosions = [];
  enemyMoveCounter = 0;

  buildMaze();

player = createPlayer();
resetCheeseMind();
enemies = createEnemies();
spawnGlyphBoostItem();
spawnConfusionMushroomItem();

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

cheeseRunnerMusicController.start();

setStatus('Eat the visible cheese. Blue portals are travel lanes only.');
startGameTimer();
  }

  function togglePause() {
    if (!isRunning) {
      return;
    }

    isPaused = !isPaused;

    if (isPaused) {
  clearGameTimer();
  cheeseRunnerMusicController.suspend();
  setStatus('Paused.');
  render();
  return;
}

cheeseRunnerMusicController.start();

setStatus('Eat the visible cheese. Blue portals are travel lanes only.');
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

  /**
 * Collects the Confusion Mushroom when the player steps onto it.
 */
function collectConfusionMushroomItem() {
  if (!confusionMushroomItem) {
    return;
  }

  if (player.row !== confusionMushroomItem.row || player.col !== confusionMushroomItem.col) {
    return;
  }

  activateConfusionMushroom();
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
  updateConfusionMushroom();

  movePlayer();
recordCheeseMindPlayerStep();

if (checkEnemyCollisions()) {
    updateScoreDisplay();
    render();
    return;
  }

  collectTile();
  collectGlyphBoostItem();
  collectConfusionMushroomItem();
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

/**
 * Sets the next player direction from keyboard, swipe, or touch controls.
 * Confusion Mushroom reverses the requested direction while its timer is active.
 */
function setDirection(directionName) {
  const direction = DIRECTIONS[directionName];

  if (!direction) {
    return;
  }

  nextDirection = applyConfusionToDirection(direction);
}

/**
 * Moves the player one tile and records the tile they came from.
 * Previous-position tracking lets collision logic catch cross-tile swaps.
 */
/**
 * Moves the player one tile and records the tile they came from.
 * Horizontal movement wraps through open side tunnels by normalizing the target column.
 */
function movePlayer() {
  player.previousRow = player.row;
  player.previousCol = player.col;

  const requestedRow = player.row + nextDirection.row;
  const requestedCol = player.col + nextDirection.col;

  if (canMove(requestedRow, requestedCol)) {
    currentDirection = nextDirection;
  }

  const nextRow = player.row + currentDirection.row;
  const nextCol = player.col + currentDirection.col;
  const normalizedNextCol = normalizeColumn(nextCol);

  if (canMove(nextRow, nextCol)) {
    player.row = nextRow;
    player.col = normalizedNextCol;
  }
}

/**
 * Counts remaining visible collectibles by tile type.
 * Plain language for DEVS:
 * This helps debug level-clear reports without treating portal lanes as cheese.
 * Crumbs and power cheese are required to clear the level; portals are not.
 */
function countRemainingVisibleCollectibles() {
  const counts = {
    crumbs: 0,
    powerCheese: 0,
    total: 0
  };

  maze.forEach(row => {
    row.forEach(tile => {
      if (tile === 'crumb') {
        counts.crumbs += 1;
        counts.total += 1;
        return;
      }

      if (tile === 'power') {
        counts.powerCheese += 1;
        counts.total += 1;
      }
    });
  });

  return counts;
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

if (tile === TILE_TUNNEL) {
  // Portals are travel lanes only.
  // They do not give score and they do not count as hidden cheese.
  return;
}


  if (tile === 'power') {
    const comboMultiplier = registerComboPickup();
    const gainedScore = Math.round(SCORE_POWER * comboMultiplier);

    score += gainedScore;
    crumbsRemaining -= 1;
    maze[player.row][player.col] = 'empty';
    powerModeUntil = performance.now() + POWER_MODE_MS;
powerCheeseEnemyRewardsUsed = 0;

enemies.forEach(enemy => {
  enemy.isStunned = true;
});

addFloatingText(`POWER +${gainedScore} x${comboMultiplier.toFixed(1)}`, player.row, player.col, '#fb923c');
setStatus(`Power Cheese active! Eat up to ${POWER_CHEESE_ENEMY_REWARD_CAP} enemies outside the nest.`);
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

    beginCheeseMindCoordinationTick();

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
pulseCheeseMindObservatory(enemy);
enemy.row += enemy.direction.row;
enemy.col = normalizeColumn(enemy.col + enemy.direction.col);
    });
  }

  /**
 * Starts a fresh CheeseMind team assignment cycle for this enemy movement tick.
 * Plain language for DEVS:
 * Enemies move one after another. This clears old decisions so each tick can
 * coordinate fresh pressure, cutoff, and guard roles.
 */
function beginCheeseMindCoordinationTick() {
  if (!cheeseMind) {
    return;
  }

  cheeseMind.coordinationTick += 1;
  cheeseMind.enemyAssignments = {};
}

/**
 * Returns true when team coordination is unlocked for the current level.
 */
function isCheeseMindCoordinationUnlocked() {
  return level >= CHEESEMIND_COORDINATION_MIN_LEVEL;
}

/**
 * Returns true when route cutoff behavior is unlocked for the current level.
 */
function isCheeseMindCutoffUnlocked() {
  return level >= CHEESEMIND_CUTOFF_MIN_LEVEL;
}

/**
 * Saves the current enemy tactical assignment for debug and team coordination.
 */
function recordCheeseMindEnemyAssignment(enemy, target) {
  if (!enemy || !target || !cheeseMind) {
    return;
  }

  cheeseMind.enemyAssignments[enemy.name] = {
    name: enemy.name,
    role: enemy.strategyRole,
    label: enemy.strategyLabel,
    row: target.row,
    col: target.col,
    reason: target.reason,
    tick: cheeseMind.coordinationTick
  };
}

/**
 * Returns true when another enemy already has the same target reason this tick.
 */
function hasCheeseMindReasonAlreadyAssigned(reason, currentEnemyName) {
  if (!cheeseMind?.enemyAssignments || !reason) {
    return false;
  }

  return Object.values(cheeseMind.enemyAssignments).some(assignment => {
    return assignment.name !== currentEnemyName && assignment.reason === reason;
  });
}

/**
 * Builds a cutoff target for the Emperor.
 * Plain language for DEVS:
 * Instead of only chasing the mouse, the Emperor aims at the predicted route
 * and marks it as a cutoff assignment so testers can see team behavior.
 */
function findCheeseMindCutoffTarget(enemy) {
  if (!isCheeseMindCutoffUnlocked()) {
    return null;
  }

  const predictedTile = predictPlayerTile();

  if (!predictedTile) {
    return null;
  }

  return {
    row: predictedTile.row,
    col: predictedTile.col,
    reason: 'cutoff-route',
    sourceReason: predictedTile.reason,
    distance: getDistance(enemy.row, enemy.col, predictedTile.row, predictedTile.col)
  };
}

/**
 * Builds a coordinated controller target for the Invader.
 * Plain language for DEVS:
 * If the team already has pressure and cutoff roles active,
 * the Invader should prefer controlling resources or escape lanes.
 */
function findCoordinatedControllerTarget(enemy) {
  if (!isCheeseMindControlUnlocked()) {
    return null;
  }

  const controllerTarget = findControllerCheeseTarget(enemy);

  if (controllerTarget) {
    return controllerTarget;
  }

  return {
    row: player.row,
    col: player.col,
    reason: 'controller-fallback-player'
  };
}

  /**
 * Returns whether CheeseMind prediction behavior is unlocked for the current level.
 */
function isCheeseMindPredictionUnlocked() {
  return level >= CHEESEMIND_PREDICTION_MIN_LEVEL;
}

/**
 * Returns whether CheeseMind controller behavior is unlocked for the current level.
 */
function isCheeseMindControlUnlocked() {
  return level >= CHEESEMIND_CONTROL_MIN_LEVEL;
}

/**
 * Returns whether CheeseMind tunnel counter behavior is unlocked for the current level.
 */
function isCheeseMindTunnelCounterUnlocked() {
  return level >= CHEESEMIND_TUNNEL_COUNTER_MIN_LEVEL;
}

/**
 * Clamps a row into the maze bounds.
 */
function clampMazeRow(row) {
  return Math.max(0, Math.min(GRID_ROWS - 1, row));
}

/**
 * Predicts where the player may be soon based on current movement direction.
 * Plain language for DEVS:
 * The Emperor does not only chase the mouse. It aims ahead of the route.
 */
function predictPlayerTile() {
  const predictionTiles = Math.min(
    CHEESEMIND_MAX_PREDICTION_TILES,
    Math.max(2, Math.floor(level / 2) + 1)
  );

  let predictedRow = player.row;
  let predictedCol = player.col;

  for (let step = 0; step < predictionTiles; step += 1) {
    const nextRow = clampMazeRow(predictedRow + currentDirection.row);
    const nextCol = normalizeColumn(predictedCol + currentDirection.col);

    if (!canMove(nextRow, nextCol)) {
      break;
    }

    predictedRow = nextRow;
    predictedCol = nextCol;
  }

  return {
    row: predictedRow,
    col: predictedCol,
    reason: 'prediction'
  };
}

/**
 * Finds the nearest board tile matching a target type.
 */
function findNearestTileByType(fromRow, fromCol, targetTileType) {
  let bestTile = null;

  maze.forEach((rowTiles, rowIndex) => {
    rowTiles.forEach((tile, colIndex) => {
      if (tile !== targetTileType) {
        return;
      }

      const distance = getDistance(fromRow, fromCol, rowIndex, colIndex);

      if (!bestTile || distance < bestTile.distance) {
        bestTile = {
          row: rowIndex,
          col: colIndex,
          distance,
          reason: `nearest-${targetTileType}`
        };
      }
    });
  });

  return bestTile;
}

/**
 * Finds a high-value tunnel tile to counter repeated portal usage.
 */
function findMostUsedTunnelTile() {
  if (!isCheeseMindTunnelCounterUnlocked()) {
    return null;
  }

  const tunnelEntries = getTopCheeseMindCounterEntries(cheeseMind.tunnelUsage, 1);

  if (!tunnelEntries.length) {
    return null;
  }

  const [rowText, colText] = tunnelEntries[0].key.split(',');
  const row = Number(rowText);
  const col = Number(colText);

  if (!Number.isFinite(row) || !Number.isFinite(col)) {
    return null;
  }

  if (maze[row]?.[col] !== TILE_TUNNEL) {
    return null;
  }

  return {
    row,
    col,
    reason: 'counter-used-tunnel'
  };
}

/**
 * Finds a remaining crumb that is useful for controller enemies to guard.
 */
function findControllerCheeseTarget(enemy) {
  const powerTarget = findNearestTileByType(enemy.row, enemy.col, 'power');

  if (powerTarget) {
    return {
      ...powerTarget,
      reason: 'guard-power-cheese'
    };
  }

  const tunnelTarget = findMostUsedTunnelTile();

  if (tunnelTarget) {
    return tunnelTarget;
  }

  const crumbTarget = findNearestTileByType(enemy.row, enemy.col, 'crumb');

  if (crumbTarget) {
    return {
      ...crumbTarget,
      reason: 'guard-remaining-cheese'
    };
  }

  return null;
}

/**
 * Converts a CheeseMind target reason into readable player/testing text.
 * Plain language for DEVS:
 * This is observability only. It does not change AI decisions or rewards.
 */
function describeCheeseMindTargetReason(reason) {
  const descriptions = {
  'hunt-player': 'hunting the mouse',
  'pressure-player': 'pressuring the mouse',
  'prediction': 'predicting the mouse route',
  'cutoff-route': 'cutting off the predicted route',
  'guard-power-cheese': 'guarding Power Cheese',
  'counter-used-tunnel': 'countering repeated portal use',
  'guard-remaining-cheese': 'guarding remaining cheese',
  'controller-fallback-player': 'falling back to a chase',
  'flee-from-player': 'fleeing during Power Cheese'
};

  return descriptions[reason] || reason || 'thinking';
}

/**
 * Shows a limited CheeseMind status pulse for special AI decisions.
 * Plain language for DEVS:
 * This helps testers prove the AI is active without spamming the UI every tick.
 */
function pulseCheeseMindObservatory(enemy) {
  if (!enemy?.lastCheeseMindTarget) {
    return;
  }

  const reason = enemy.lastCheeseMindTarget.reason;

  if (!reason || reason === 'hunt-player') {
    return;
  }

  const now = performance.now();

  if (now - cheeseMindLastObservatoryStatusAt < CHEESEMIND_OBSERVATORY_STATUS_COOLDOWN_MS) {
    return;
  }

  cheeseMindLastObservatoryStatusAt = now;
  setStatus(`🧠 CheeseMind: ${enemy.name} is ${describeCheeseMindTargetReason(reason)}.`);
}

/**
 * Resolves the current tactical target for one enemy role.
 * Plain language for DEVS:
 * Patch 3 adds team coordination:
 * Destroyer pressures, Emperor cuts off, Invader controls resources/escape lanes.
 */
function resolveCheeseMindEnemyTarget(enemy) {
  if (isPowerModeActive()) {
    return {
      row: player.row,
      col: player.col,
      reason: 'flee-from-player'
    };
  }

  if (enemy.strategyRole === 'hunter') {
    return {
      row: player.row,
      col: player.col,
      reason: 'pressure-player'
    };
  }

  if (enemy.strategyRole === 'predictor' && isCheeseMindPredictionUnlocked()) {
    const cutoffTarget = isCheeseMindCoordinationUnlocked()
      ? findCheeseMindCutoffTarget(enemy)
      : null;

    return cutoffTarget || predictPlayerTile();
  }

  if (enemy.strategyRole === 'controller' && isCheeseMindControlUnlocked()) {
    return findCoordinatedControllerTarget(enemy);
  }

  return {
    row: player.row,
    col: player.col,
    reason: 'hunt-player'
  };
}

/**
 * Scores a possible direction against the tactical target.
 * Lower score means the move is better unless enemies are fleeing in Power Mode.
 */
function scoreCheeseMindDirection(enemy, direction, target) {
  const nextRow = enemy.row + direction.row;
  const nextCol = normalizeColumn(enemy.col + direction.col);
  const distance = getDistance(nextRow, nextCol, target.row, target.col);

  let scoreValue = distance;

  if (enemy.direction && direction.row === -enemy.direction.row && direction.col === -enemy.direction.col) {
    scoreValue += 0.35;
  }

  if (
  isCheeseMindCoordinationUnlocked() &&
  hasCheeseMindReasonAlreadyAssigned(target.reason, enemy.name)
) {
  scoreValue += CHEESEMIND_ASSIGNMENT_OVERLAP_PENALTY;
}

  return {
    direction,
    distance,
    scoreValue,
    targetReason: target.reason
  };
}

  /**
 * Chooses an enemy direction using CheeseMind tactical targeting.
 * Plain language for DEVS:
 * The old logic chased only the current mouse tile.
 * CheeseMind lets each enemy role target different tactical goals.
 */
function chooseEnemyDirection(enemy, possibleDirections) {
  const target = resolveCheeseMindEnemyTarget(enemy);
  const scoredDirections = possibleDirections
    .map(direction => scoreCheeseMindDirection(enemy, direction, target))
    .sort((left, right) => {
      return isPowerModeActive()
        ? right.scoreValue - left.scoreValue
        : left.scoreValue - right.scoreValue;
    });

  const bestDirection = scoredDirections[0];
  const shouldUseSmartMove = Math.random() < getEnemySmartMoveChance();
  const shouldUseRandomFallback = Math.random() < CHEESEMIND_RANDOM_FALLBACK_CHANCE;

  enemy.lastCheeseMindTarget = target;
recordCheeseMindEnemyAssignment(enemy, target);

enemy.lastCheeseMindDecision = {
  targetReason: bestDirection?.targetReason || 'none',
  usedSmartMove: Boolean(shouldUseSmartMove && !shouldUseRandomFallback),
  usedRandomFallback: shouldUseRandomFallback,
  coordinationTick: cheeseMind.coordinationTick
};

  if (shouldUseSmartMove && !shouldUseRandomFallback && bestDirection) {
    return bestDirection.direction;
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
powerCheeseEnemyRewardsUsed = 0;
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
 * Returns true when an enemy is still inside the protected nest/spawn area.
 * Plain language for DEVS:
 * Power Cheese rewards should come from risky chase gameplay,
 * not from standing inside the enemy spawn and farming respawns.
 */
function isEnemyInsideNestArea(enemy) {
  return maze[enemy.row]?.[enemy.col] === 'nest';
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
    if (isPowerModeActive()) {
      addFloatingText('RESPAWNING', enemy.row, enemy.col, '#94a3b8');
      setStatus(`${enemy.name} is respawning and cannot be eaten yet.`);
    }

    return false;
  }

  if (isPowerModeActive()) {
    if (isEnemyInsideNestArea(enemy)) {
      addFloatingText('NEST SAFE', enemy.row, enemy.col, '#d8b4fe');
      setStatus('Protected spawn zone: lure enemies out of the nest before eating them.');
      return false;
    }

    if (powerCheeseEnemyRewardsUsed >= POWER_CHEESE_ENEMY_REWARD_CAP) {
      addFloatingText('CAP 3/3', enemy.row, enemy.col, '#facc15');
      setStatus(`Power Cheese reward cap reached (${POWER_CHEESE_ENEMY_REWARD_CAP}/${POWER_CHEESE_ENEMY_REWARD_CAP}). Clear cheese or grab another Power Cheese.`);
      return false;
    }

    powerCheeseEnemyRewardsUsed += 1;
    score += SCORE_ENEMY;

    addHitExplosion(enemy.row, enemy.col);
    addFloatingText(
      `EATEN +${SCORE_ENEMY} ${powerCheeseEnemyRewardsUsed}/${POWER_CHEESE_ENEMY_REWARD_CAP}`,
      enemy.row,
      enemy.col,
      '#22c55e'
    );

    enemy.row = enemy.startRow;
    enemy.col = enemy.startCol;
    enemy.previousRow = enemy.startRow;
    enemy.previousCol = enemy.startCol;
    enemy.direction = DIRECTIONS.up;
    enemy.isStunned = false;
    enemy.respawnLockTicks = ENEMY_RESPAWN_LOCK_TICKS;

    setStatus(`${enemy.name} eaten! Power reward ${powerCheeseEnemyRewardsUsed}/${POWER_CHEESE_ENEMY_REWARD_CAP}.`);
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
      setStatus(`Level ${level}! Eat the visible cheese. Portals are travel lanes only.`);
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
  cheeseRunnerMusicController.stop();
  isRunning = false;
  isPaused = false;
  glyphBoostUntil = 0;
  glyphBoostItem = null;
  confusionMushroomUntil = 0;
confusionMushroomItem = null;
  powerModeUntil = 0;

  updateScoreDisplay();
  render();

  if (finalScoreEl) {
    finalScoreEl.textContent = `Score: ${score.toLocaleString()}`;
  }

  if (finalDspoincEl) {
    finalDspoincEl.textContent = isCheesemanPartnerMode
      ? 'Partner Points: pending'
      : `DSPOINC: ${calculateDspoincReward().toLocaleString()}`;
  }

  if (saveStatusEl) {
    saveStatusEl.textContent = isCheesemanPartnerMode
      ? 'Closing partner session...'
      : 'Saving score...';
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

    if (isCheesemanPartnerMode) {
      await submitCheesemanPartnerSessionScore();
      return;
    }

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
   * Close one Partner Bridge gameplay session with the real Cheese Runner score.
   *
   * Plain language for DEVS FOR DECADES:
   * This is the partner iframe save path. It writes only to the isolated
   * partner bridge tables through close-session.php. It does not credit
   * Narrrfs DSPOINC, does not write tbl_tetris_scores, and does not touch the
   * public leaderboard or normal Cheese Runner reward API.
   */
  async function submitCheesemanPartnerSessionScore() {
    const apiBaseUrl = window.location.hostname === 'narrrfs.world'
      ? 'https://narrrfs.world'
      : '';

    const payload = {
      session_token: cheesemanPartnerSessionToken,
      game: GAME_KEY,
      score: Math.max(0, Math.round(Number(score || 0))),
      client_run_id: cheesemanPartnerClientRunId
    };

    try {
      if (saveStatusEl) {
        saveStatusEl.textContent = 'Closing partner Cheese Runner session...';
      }

      const response = await fetch(`${apiBaseUrl}/api/partner/games/close-session.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        cache: 'no-store',
        body: JSON.stringify(payload)
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.error || data.details || `HTTP ${response.status}`);
      }

      if (finalDspoincEl) {
        finalDspoincEl.textContent = `Partner Points: ${Number(data.partner_points || 0).toLocaleString()}`;
      }

      if (saveStatusEl) {
        saveStatusEl.textContent = `✅ Partner session closed. Partner points: ${Number(data.partner_points || 0).toLocaleString()}`;
      }

      console.log('✅ Partner Cheese Runner session closed:', data);
    } catch (error) {
      console.error('❌ Partner Cheese Runner close failed:', error);

      hasScoreBeenSaved = false;

      if (saveStatusEl) {
        saveStatusEl.textContent = `❌ Partner session close failed: ${error.message}`;
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
drawConfusionMushroomItem();
drawCheeseMindTargetSignals();
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
          // Protected enemy spawn zone.
          // Plain language for DEVS:
          // This is the anti-farm area. Power Cheese rewards should happen in the maze,
          // not by camping enemies inside their respawn nest.
          const centerX = x + TILE_SIZE / 2;
          const centerY = y + TILE_SIZE / 2;
          const pulse = 0.75 + Math.sin(performance.now() / 180) * 0.12;

          ctx.fillStyle = 'rgba(88, 28, 135, 0.42)';
          ctx.fillRect(x + 1, y + 1, TILE_SIZE - 2, TILE_SIZE - 2);

          ctx.strokeStyle = 'rgba(216, 180, 254, 0.75)';
          ctx.lineWidth = 2;
          ctx.strokeRect(x + 4, y + 4, TILE_SIZE - 8, TILE_SIZE - 8);

          ctx.fillStyle = `rgba(250, 204, 21, ${pulse})`;
          ctx.font = 'bold 9px Arial';
          ctx.textAlign = 'center';
          ctx.textBaseline = 'middle';
          ctx.fillText('SAFE', centerX, centerY - 3);

          ctx.fillStyle = 'rgba(248, 250, 252, 0.78)';
          ctx.font = 'bold 8px Arial';
          ctx.fillText('NEST', centerX, centerY + 7);

          continue;
        }

        if (tile === TILE_TUNNEL) {
  // Portal tiles are visible movement lanes, not hidden cheese.
  ctx.fillStyle = 'rgba(14, 165, 233, 0.18)';
  ctx.fillRect(x + 2, y + 2, TILE_SIZE - 4, TILE_SIZE - 4);

  ctx.strokeStyle = 'rgba(103, 232, 249, 0.9)';
  ctx.lineWidth = 2;
  ctx.beginPath();
  ctx.arc(x + TILE_SIZE / 2, y + TILE_SIZE / 2, 9, 0, Math.PI * 2);
  ctx.stroke();

  ctx.fillStyle = '#67e8f9';
  ctx.font = 'bold 13px sans-serif';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText('↔', x + TILE_SIZE / 2, y + TILE_SIZE / 2);

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
 * Draws the Confusion Mushroom item on the board.
 * The bright red/purple backing keeps mushroom.png readable on the dark maze.
 */
function drawConfusionMushroomItem() {
  if (!confusionMushroomItem) {
    return;
  }

  const x = confusionMushroomItem.col * TILE_SIZE;
  const y = confusionMushroomItem.row * TILE_SIZE;
  const centerX = x + TILE_SIZE / 2;
  const centerY = y + TILE_SIZE / 2;
  const pulse = 0.9 + Math.sin(performance.now() / 130) * 0.08;

  ctx.save();

  ctx.shadowColor = '#ef4444';
  ctx.shadowBlur = 20;
  ctx.fillStyle = 'rgba(239, 68, 68, 0.88)';
  ctx.beginPath();
  ctx.arc(centerX, centerY, TILE_SIZE * 0.46 * pulse, 0, Math.PI * 2);
  ctx.fill();

  ctx.shadowColor = '#a855f7';
  ctx.shadowBlur = 14;
  ctx.strokeStyle = '#a855f7';
  ctx.lineWidth = 3;
  ctx.beginPath();
  ctx.arc(centerX, centerY, TILE_SIZE * 0.36 * pulse, 0, Math.PI * 2);
  ctx.stroke();

  if (confusionMushroomImage && confusionMushroomImage.complete && confusionMushroomImage.naturalWidth > 0) {
    ctx.shadowBlur = 0;
    ctx.drawImage(
      confusionMushroomImage,
      x + 4,
      y + 4,
      TILE_SIZE - 8,
      TILE_SIZE - 8
    );
    ctx.restore();
    return;
  }

  // TODO: Keep this fallback until mushroom.png loading is verified on production.
  ctx.shadowBlur = 0;
  ctx.fillStyle = '#fecaca';
  ctx.font = 'bold 16px Arial';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText('🍄', centerX, centerY);

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

/**
 * Returns true when the current host may use local CheeseMind visual debugging.
 * Plain language for DEVS:
 * Full intent lines are a test tool. Do not expose exact AI targets on production.
 */
function isCheeseMindLocalVisualDebugHost() {
  const host = String(window.location.hostname || '');
  return host === 'localhost' || host === '127.0.0.1';
}

/**
 * Returns true when full CheeseMind visuals are explicitly enabled.
 * Plain language for DEVS:
 * Normal players should feel hunted, not see the exact algorithm.
 */
function isCheeseMindFullVisualDebugEnabled() {
  if (CHEESEMIND_PRODUCTION_FULL_VISUALS_ENABLED) {
    return true;
  }

  if (!isCheeseMindLocalVisualDebugHost()) {
    return false;
  }

  return localStorage.getItem(CHEESEMIND_LOCAL_VISUAL_DEBUG_STORAGE_KEY) === 'true';
}

/**
 * Returns true when CheeseMind visual hints should be drawn.
 * Plain language for DEVS:
 * This now requires local debug opt-in, so production does not reveal exact AI targets.
 */
function shouldDrawCheeseMindVisualSignals() {
  return (
    level >= CHEESEMIND_VISUAL_SIGNAL_MIN_LEVEL &&
    Array.isArray(enemies) &&
    isCheeseMindFullVisualDebugEnabled()
  );
}

/**
 * Returns the visual style for each CheeseMind tactical reason.
 * Plain language for DEVS:
 * Keep these icons readable on small canvas tiles. Do not use long text here.
 */
function getCheeseMindSignalStyle(reason) {
  const styles = {
    'pressure-player': {
      color: '#ef4444',
      glow: 'rgba(239, 68, 68, 0.55)',
      icon: '!'
    },
    'cutoff-route': {
      color: '#a855f7',
      glow: 'rgba(168, 85, 247, 0.58)',
      icon: '✕'
    },
    'prediction': {
      color: '#c084fc',
      glow: 'rgba(192, 132, 252, 0.55)',
      icon: '?'
    },
    'guard-power-cheese': {
      color: '#22c55e',
      glow: 'rgba(34, 197, 94, 0.55)',
      icon: '🛡'
    },
    'guard-remaining-cheese': {
      color: '#facc15',
      glow: 'rgba(250, 204, 21, 0.52)',
      icon: '•'
    },
    'counter-used-tunnel': {
      color: '#22d3ee',
      glow: 'rgba(34, 211, 238, 0.58)',
      icon: '↔'
    },
    'controller-fallback-player': {
      color: '#94a3b8',
      glow: 'rgba(148, 163, 184, 0.45)',
      icon: '→'
    },
    'flee-from-player': {
      color: '#38bdf8',
      glow: 'rgba(56, 189, 248, 0.50)',
      icon: '↯'
    }
  };

  return styles[reason] || null;
}

/**
 * Draws a soft line from an enemy to its CheeseMind target tile.
 * Plain language for DEVS:
 * This lets players see where the AI is focusing without changing movement.
 */
function drawCheeseMindIntentLine(enemy, target, style) {
  const enemyX = enemy.col * TILE_SIZE + TILE_SIZE / 2;
  const enemyY = enemy.row * TILE_SIZE + TILE_SIZE / 2;
  const targetX = target.col * TILE_SIZE + TILE_SIZE / 2;
  const targetY = target.row * TILE_SIZE + TILE_SIZE / 2;

  ctx.save();
  ctx.globalAlpha = 0.32;
  ctx.strokeStyle = style.color;
  ctx.lineWidth = 2;
  ctx.setLineDash([5, 5]);
  ctx.shadowColor = style.color;
  ctx.shadowBlur = 8;
  ctx.beginPath();
  ctx.moveTo(enemyX, enemyY);
  ctx.lineTo(targetX, targetY);
  ctx.stroke();
  ctx.restore();
}

/**
 * Draws the target marker for one CheeseMind enemy decision.
 * Plain language for DEVS:
 * This marks the tile the enemy brain is thinking about.
 */
function drawCheeseMindTargetMarker(enemy) {
  if (!isCheeseMindFullVisualDebugEnabled()) {
  return;
}

if (!enemy?.lastCheeseMindTarget) {
  return;
}

  const target = enemy.lastCheeseMindTarget;
  const style = getCheeseMindSignalStyle(target.reason);

  if (!style || target.reason === 'hunt-player') {
    return;
  }

  if (target.row < 0 || target.row >= GRID_ROWS || target.col < 0 || target.col >= GRID_COLS) {
    return;
  }

  const centerX = target.col * TILE_SIZE + TILE_SIZE / 2;
  const centerY = target.row * TILE_SIZE + TILE_SIZE / 2;
  const pulse = 1 + Math.sin(performance.now() / 130) * 0.15;

  drawCheeseMindIntentLine(enemy, target, style);

  ctx.save();
  ctx.globalAlpha = 0.9;
  ctx.strokeStyle = style.color;
  ctx.fillStyle = 'rgba(2, 6, 23, 0.72)';
  ctx.lineWidth = 2;
  ctx.shadowColor = style.color;
  ctx.shadowBlur = 14;

  ctx.beginPath();
  ctx.arc(centerX, centerY, CHEESEMIND_TARGET_MARKER_RADIUS * pulse, 0, Math.PI * 2);
  ctx.fill();
  ctx.stroke();

  ctx.fillStyle = style.color;
  ctx.font = 'bold 10px Arial';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText(style.icon, centerX, centerY + 0.5);

  ctx.restore();
}

/**
 * Draws all CheeseMind target markers under enemies.
 * Plain language for DEVS:
 * This is called before drawEnemies() so enemy sprites stay readable on top.
 */
function drawCheeseMindTargetSignals() {
  if (!shouldDrawCheeseMindVisualSignals()) {
    return;
  }

  enemies.forEach(enemy => {
    drawCheeseMindTargetMarker(enemy);
  });
}

/**
 * Draws a small role/intention badge above one enemy.
 * Plain language for DEVS:
 * This gives players a quick read of the enemy's current job.
 */
function drawCheeseMindEnemySignalBadge(enemy, x, y) {
  if (!shouldDrawCheeseMindVisualSignals() || !enemy?.lastCheeseMindTarget) {
    return;
  }

  const reason = enemy.lastCheeseMindTarget.reason;

  if (!reason || reason === 'hunt-player') {
    return;
  }

  const style = getCheeseMindSignalStyle(reason);

  if (!style) {
    return;
  }

  const badgeX = x + TILE_SIZE - 4;
  const badgeY = y + 4;

  ctx.save();
  ctx.globalAlpha = 0.96;
  ctx.fillStyle = 'rgba(2, 6, 23, 0.88)';
  ctx.strokeStyle = style.color;
  ctx.lineWidth = 2;
  ctx.shadowColor = style.color;
  ctx.shadowBlur = 10;

  ctx.beginPath();
  ctx.arc(badgeX, badgeY, CHEESEMIND_ENEMY_BADGE_RADIUS, 0, Math.PI * 2);
  ctx.fill();
  ctx.stroke();

  ctx.shadowBlur = 0;
  ctx.fillStyle = style.color;
  ctx.font = 'bold 9px Arial';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText(style.icon, badgeX, badgeY + 0.5);

  ctx.restore();
}

function drawPlayer() {
  const x = player.col * TILE_SIZE;
  const y = player.row * TILE_SIZE;

  if (isGlyphBoostActive()) {
    drawGlyphBoostAura();
  }

  if (isConfusionMushroomActive()) {
  drawConfusionAura();
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

drawCheeseMindEnemySignalBadge(enemy, x, y);

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

  /**
 * Local-only CheeseMind test helper.
 * Plain language for DEVS:
 * This helps test higher AI levels without playing through every level.
 * It is blocked outside localhost-style development hosts.
 */
window.cheesemanSetDebugLevel = function cheesemanSetDebugLevel(nextLevel) {
  const host = String(window.location.hostname || '');

  if (host !== 'localhost' && host !== '127.0.0.1') {
    console.warn('CheeseMind debug level helper is localhost-only.');
    return false;
  }

  const safeLevel = Math.max(1, Math.min(MAX_LEVEL_TEMPLATE_COUNT, Number(nextLevel || 1)));

  if (!Number.isFinite(safeLevel)) {
    console.warn('Invalid CheeseMind debug level.');
    return false;
  }

  level = safeLevel;
  buildMaze();
  player = createPlayer();
  resetCheeseMind();
  enemies = createEnemies();
  enemyMoveCounter = 0;
  powerModeUntil = 0;
  powerCheeseEnemyRewardsUsed = 0;
  glyphBoostUntil = 0;
  confusionMushroomUntil = 0;
  spawnGlyphBoostItem();
  spawnConfusionMushroomItem();
  updateScoreDisplay();
  render();
  setStatus(`🧠 CheeseMind debug test level ${level}. Watch enemy decisions.`);
  return true;
};

/**
 * Local-only CheeseMind visual debug toggle.
 * Plain language for DEVS:
 * This lets testers enable/disable full CheeseMind target lines and badges locally.
 * Production players should not see exact AI target logic.
 */
window.cheesemanSetCheeseMindVisualDebug = function cheesemanSetCheeseMindVisualDebug(isEnabled) {
  if (!isCheeseMindLocalVisualDebugHost()) {
    console.warn('CheeseMind visual debug is localhost-only.');
    return false;
  }

  const enabled = Boolean(isEnabled);
  localStorage.setItem(CHEESEMIND_LOCAL_VISUAL_DEBUG_STORAGE_KEY, enabled ? 'true' : 'false');
  render();

  setStatus(
    enabled
      ? '🧠 CheeseMind visual debug enabled locally.'
      : '🧠 CheeseMind full visuals hidden. AI still active.'
  );

  return true;
};

 window.cheesemanDebugState = function cheesemanDebugState() {
  const visibleCollectibles = countRemainingVisibleCollectibles();

  return {
    game: GAME_KEY,
    score,
    dspoinc: calculateDspoincReward(),
    level,
    lives,
    crumbsRemaining,
    powerCheeseEnemyRewardsUsed,
    powerCheeseEnemyRewardCap: POWER_CHEESE_ENEMY_REWARD_CAP,
    visibleCollectibles,
    portalTilesAreCollectibles: false,
    cheeseMind: getCheeseMindDebugState(),
cheeseMindFullVisualDebugEnabled: isCheeseMindFullVisualDebugEnabled(),
    roleMultiplier: getCheesemanRoleScoreMultiplier(),
    primaryRole: getCheesemanPrimaryRoleName(),
    identity: resolveDiscordIdentity()
  };
};
  initCheeseman();
})();