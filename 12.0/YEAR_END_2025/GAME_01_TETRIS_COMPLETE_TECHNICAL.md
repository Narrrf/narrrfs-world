# 🧩 GAME 1: TETRIS - COMPLETE TECHNICAL DOCUMENTATION 2025

**Created:** December 20, 2025  
**Status:** ✅ **PRODUCTION READY - COMPLETE INTEGRATION**  
**Version:** 10.2.0 - Season 5 Stable  
**Purpose:** Complete technical reference for Tetris game integration in Narrrfs World

---

## 📋 **TABLE OF CONTENTS**

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Frontend Implementation](#frontend-implementation)
4. [Backend API Integration](#backend-api-integration)
5. [Database Schema](#database-schema)
6. [Scoring System](#scoring-system)
7. [Achievement System](#achievement-system)
8. [Role-Based System](#role-based-system)
9. [Admin Interface Integration](#admin-interface-integration)
10. [Code Examples](#code-examples)
11. [Testing & Verification](#testing--verification)

---

## 🎯 **OVERVIEW**

### **Game Description:**
Cheese Tetris Scroll is a classic block-stacking puzzle game where players rotate and position falling pieces (tetrominoes) to create complete horizontal lines. The game features role-based multipliers, 25 achievements, cheese particle effects, and full integration with Narrrfs World ecosystem.

### **Key Features:**
- ✅ Role-based DSPOINC multipliers (1.1x → 2.0x)
- ✅ 25 achievements with dynamic loading
- ✅ Cheese particle system
- ✅ Store upgrades (Matrix Glow, Cheese Drop Reactor)
- ✅ Mobile-optimized touch controls
- ✅ Professional sound system
- ✅ Boss system (9 progressive bosses)

### **Integration Status:**
- ✅ **Frontend:** `public/tetris.html`, `public/scripts/tetris-scroll.js`
- ✅ **Backend:** `/api/dev/save-score.php`
- ✅ **Database:** `tbl_tetris_scores`, `tbl_tetris_achievements`
- ✅ **Admin Interface:** Full integration via `/api/admin/get-all-games-stats.php`

---

## 🏗️ **ARCHITECTURE**

### **System Flow:**
```
User Opens tetris.html
        ↓
Discord Authentication Check
        ↓
Load User Roles (for multipliers)
        ↓
Initialize Game Canvas
        ↓
Game Loop (Piece falling, line clearing)
        ↓
Score Calculation (with role multiplier)
        ↓
Achievement Checking (real-time)
        ↓
Game End → Save Score to Database
        ↓
Update Admin Interface Statistics
```

### **Technology Stack:**
- **Frontend:** HTML5 Canvas, Vanilla JavaScript, CSS3
- **Backend:** PHP 8.x, SQLite3
- **Authentication:** Discord OAuth 2.0
- **APIs:** RESTful endpoints
- **Database:** SQLite (`narrrf_world.sqlite`)

---

## 💻 **FRONTEND IMPLEMENTATION**

### **File Structure:**
```
public/
├── tetris.html                    # Main game page
└── scripts/
    └── tetris-scroll.js          # Game logic (2,000+ lines)
```

### **Key Functions:**

#### **1. Game Initialization:**
```javascript
// tetris-scroll.js
function startTetrisGame() {
    // Initialize canvas
    const canvas = document.getElementById('tetris-canvas');
    const ctx = canvas.getContext('2d');
    
    // Load user roles for multipliers
    loadUserRoles().then(roles => {
        applyRoleTheme(roles);
        calculateRoleMultiplier(roles);
    });
    
    // Start game loop
    gameLoop();
}
```

#### **2. Score Calculation with Role Multiplier:**
```javascript
// tetris-scroll.js
function calculateScore(lines) {
    const baseScore = lines * 2; // 2 DSPOINC per line
    const roleMultiplier = getRoleMultiplier(); // 1.0x - 2.0x
    const finalScore = Math.round(baseScore * roleMultiplier);
    
    return finalScore;
}
```

#### **3. Achievement Checking:**
```javascript
// tetris-scroll.js
function checkTetrisAchievements() {
    const achievements = [
        { key: 'score_hunter', check: () => gameScore >= 200 },
        { key: 'first_line', check: () => linesCleared >= 1 },
        { key: 'combo_starter', check: () => linesClearedInTurn >= 2 },
        // ... 22 more achievements
    ];
    
    achievements.forEach(achievement => {
        if (achievement.check() && !unlockedAchievements[achievement.key]) {
            unlockAchievement(achievement.key);
        }
    });
}
```

#### **4. Score Saving:**
```javascript
// tetris-scroll.js
async function saveTetrisScore() {
    const discordId = localStorage.getItem('discord_id');
    const score = gameScore; // Final DSPOINC including multiplier
    
    const response = await fetch('/api/dev/save-score.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            game: 'tetris',
            discord_id: discordId,
            score: score,
            lines_cleared: linesCleared,
            level_reached: levelReached
        })
    });
    
    const result = await response.json();
    console.log('Score saved:', result);
}
```

---

## 🔌 **BACKEND API INTEGRATION**

### **API Endpoint: `/api/dev/save-score.php`**

#### **Request Format:**
```json
{
    "game": "tetris",
    "discord_id": "1107633105185013790",
    "score": 48,
    "lines_cleared": 12,
    "level_reached": 3
}
```

#### **Response Format:**
```json
{
    "success": true,
    "message": "Score saved successfully",
    "data": {
        "game": "tetris",
        "score": 48,
        "dspoinc_earned": 48,
        "season": "Season 5"
    }
}
```

#### **PHP Implementation:**
```php
// api/dev/save-score.php
<?php
require_once __DIR__ . '/../../db/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$game = $input['game'] ?? '';
$discordId = $input['discord_id'] ?? '';
$score = intval($input['score'] ?? 0);

if ($game === 'tetris') {
    // Insert into tbl_tetris_scores
    $stmt = $pdo->prepare("
        INSERT INTO tbl_tetris_scores 
        (discord_id, game, score, lines_cleared, level_reached, timestamp)
        VALUES (?, 'tetris', ?, ?, ?, datetime('now'))
    ");
    
    $stmt->execute([
        $discordId,
        $score,
        $input['lines_cleared'] ?? 0,
        $input['level_reached'] ?? 0
    ]);
    
    // Update DSPOINC balance in tbl_user_scores
    updateDspoincBalance($discordId, $score, 'tetris');
    
    echo json_encode(['success' => true, 'message' => 'Score saved']);
}
?>
```

### **Achievement API: `/api/user/get-tetris-achievements.php`**

#### **Request Format:**
```json
{
    "user_id": "1107633105185013790"
}
```

#### **Response Format:**
```json
{
    "success": true,
    "achievements": [
        {
            "achievement_key": "score_hunter",
            "achievement_title": "Score Hunter",
            "achievement_description": "Earn 200 DSPOINC in one game",
            "achievement_icon": "🎯",
            "unlocked_at": "2025-12-20 10:30:00",
            "game_score": 200
        }
        // ... more achievements
    ],
    "definitions": [
        // All 25 achievement definitions
    ]
}
```

---

## 🗄️ **DATABASE SCHEMA**

### **1. Score Table: `tbl_tetris_scores`**

```sql
CREATE TABLE tbl_tetris_scores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    discord_id TEXT NOT NULL,              -- Discord ID (NOT user_id!)
    game TEXT NOT NULL DEFAULT 'tetris',   -- Game identifier
    score INTEGER NOT NULL,                -- Final DSPOINC (with multiplier)
    lines_cleared INTEGER DEFAULT 0,      -- Lines cleared in game
    level_reached INTEGER DEFAULT 0,      -- Level reached
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    season TEXT,                          -- Season identifier
    FOREIGN KEY (discord_id) REFERENCES tbl_users(discord_id)
);

CREATE INDEX idx_tetris_discord_id ON tbl_tetris_scores(discord_id);
CREATE INDEX idx_tetris_game ON tbl_tetris_scores(game);
CREATE INDEX idx_tetris_season ON tbl_tetris_scores(season);
```

**CRITICAL:** Uses `discord_id` field (NOT `user_id`)!

### **2. Achievement Table: `tbl_tetris_achievements`**

```sql
CREATE TABLE tbl_tetris_achievements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,                    -- Discord ID or 'ACHIEVEMENT_DEFINITIONS'
    achievement_key TEXT NOT NULL,            -- Unique identifier
    achievement_title TEXT NOT NULL,          -- Display name
    achievement_description TEXT NOT NULL,   -- Description
    achievement_icon TEXT NOT NULL,          -- Emoji icon
    unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    game_score INTEGER DEFAULT 0,             -- Score threshold
    lines_cleared INTEGER DEFAULT 0,          -- Lines threshold
    level_reached INTEGER DEFAULT 0,         -- Level threshold
    pieces_dropped INTEGER DEFAULT 0,        -- Pieces threshold
    tetris_clears INTEGER DEFAULT 0,         -- Tetris clears threshold
    FOREIGN KEY (user_id) REFERENCES tbl_users(discord_id)
);

CREATE INDEX idx_tetris_achievements_user ON tbl_tetris_achievements(user_id);
CREATE INDEX idx_tetris_achievements_key ON tbl_tetris_achievements(achievement_key);
```

**Special Record:** `user_id = 'ACHIEVEMENT_DEFINITIONS'` contains all 25 achievement definitions.

### **3. Query Examples:**

#### **Get User Scores:**
```sql
SELECT 
    COUNT(*) as total_games,
    MAX(score) as best_score,
    SUM(score) as total_dspoinc,
    SUM(lines_cleared) as total_lines
FROM tbl_tetris_scores
WHERE discord_id = ? AND game = 'tetris';
```

#### **Get User Achievements:**
```sql
SELECT 
    achievement_key,
    achievement_title,
    achievement_description,
    achievement_icon,
    unlocked_at
FROM tbl_tetris_achievements
WHERE user_id = ? AND unlocked_at IS NOT NULL;
```

#### **Get Achievement Definitions:**
```sql
SELECT 
    achievement_key,
    achievement_title,
    achievement_description,
    achievement_icon,
    game_score,
    lines_cleared
FROM tbl_tetris_achievements
WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';
```

---

## 📊 **SCORING SYSTEM**

### **Base Scoring:**
- **Per Line:** 2 DSPOINC
- **Multi-Line Bonus:** +1 DSPOINC per additional line (2 lines = 5 DSPOINC, 3 lines = 8 DSPOINC, 4 lines = 12 DSPOINC)

### **Role Multipliers:**
| Role | Multiplier | Example (2 lines) |
|------|-----------|-------------------|
| VIP Holder | 2.0x | 4 DSPOINC → 8 DSPOINC |
| Holder | 1.5x | 4 DSPOINC → 6 DSPOINC |
| Champion | 1.4x | 4 DSPOINC → 6 DSPOINC (rounded) |
| Season Tester | 1.3x | 4 DSPOINC → 5 DSPOINC (rounded) |
| Early Bird | 1.2x | 4 DSPOINC → 5 DSPOINC (rounded) |
| Cheese Hunter | 1.1x | 4 DSPOINC → 4 DSPOINC (rounded) |
| No Role | 1.0x | 4 DSPOINC → 4 DSPOINC |

### **Score Calculation Flow:**
```javascript
// 1. Calculate base score
const baseScore = lines * 2; // 2 DSPOINC per line

// 2. Add multi-line bonus
if (lines >= 2) {
    baseScore += (lines - 1); // +1 per additional line
}

// 3. Apply role multiplier
const roleMultiplier = getRoleMultiplier(); // 1.0x - 2.0x
const finalScore = Math.round(baseScore * roleMultiplier);

// 4. Save to database
saveTetrisScore(finalScore);
```

---

## 🏆 **ACHIEVEMENT SYSTEM**

### **25 Total Achievements:**

#### **Score-Based (5):**
- Score Hunter: 200 DSPOINC
- High Roller: 800 DSPOINC
- Point Master: 1,500 DSPOINC
- Score Legend: 2,000 DSPOINC
- Tetris King: 2,500 DSPOINC (maximum)

#### **Line-Based (5):**
- First Line: 1 line
- Line Master: 10 lines
- Tetris Pro: 30 lines
- Line Legend: 50 lines
- Line Destroyer: 100 lines

#### **Level-Based (4):**
- Speed Demon: Level 5
- Level Master: Level 8
- Level Warrior: Level 12
- Level Champion: Level 15

#### **Tetris Clears (5):**
- Tetris Clear: 1 Tetris
- Back to Back: 2 Tetris
- Tetris Master: 5 Tetris
- Tetris God: 8 Tetris
- Tetris Legend: 15 Tetris

#### **Combo-Based (3):**
- Combo Starter: 2 lines at once
- Combo Master: 3 lines at once
- Combo Legend: 4 lines at once (Tetris!)

#### **Piece-Based (3):**
- Piece Dropper: 100 pieces
- Block Master: 400 pieces
- Piece Legend: 600 pieces

### **Achievement Loading:**
```javascript
// Load from database (dynamic, no hardcoding)
async function loadTetrisAchievements() {
    const response = await fetch('/api/user/get-tetris-achievements.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: discordId })
    });
    
    const data = await response.json();
    
    // Build achievement cards dynamically
    data.definitions.forEach(def => {
        const unlocked = data.achievements.find(a => a.achievement_key === def.achievement_key);
        createAchievementCard(def, unlocked);
    });
}
```

---

## 🎨 **ROLE-BASED SYSTEM**

### **Role Multiplier Detection:**
```javascript
// Load roles from API
async function loadUserRoles() {
    const response = await fetch('/api/user/roles.php');
    const roles = await response.json();
    
    // Normalize role names (remove emojis)
    return roles.map(role => normalizeRole(role));
}

// Apply highest multiplier
function calculateRoleMultiplier(roles) {
    const multipliers = {
        'VIP Holder': 2.0,
        'Holder': 1.5,
        'Champion': 1.4,
        'Season Tester': 1.3,
        'Early Bird': 1.2,
        'Cheese Hunter': 1.1,
        'WL': 1.3
    };
    
    let maxMultiplier = 1.0;
    roles.forEach(role => {
        if (multipliers[role] > maxMultiplier) {
            maxMultiplier = multipliers[role];
        }
    });
    
    return maxMultiplier;
}
```

### **Visual Themes:**
- **Golden:** VIP Holder (2.0x)
- **Silver:** Holder (1.5x)
- **Red:** Champion (1.4x)
- **Green:** Season Tester (1.3x)
- **Blue:** Early Bird (1.2x)
- **Cheese:** Cheese Hunter (1.1x)

---

## 🌐 **PROFILE.HTML INTEGRATION**

### **Game Stats Display:**
```javascript
// public/profile.html - getGameStats() function
case 'tetris':
    statsHTML = `
      <div class="text-blue-300">Best Score: ${stats.best_score ? stats.best_score.toLocaleString() : 'N/A'}</div>
      <div class="text-green-300">Total Games: ${stats.total_games || 0}</div>
      <div class="text-yellow-300">DSPOINC Earned: ${stats.dspoinc_earned ? stats.dspoinc_earned.toLocaleString() : '0'}</div>
    `;
    break;
```

### **All-Time Stats Card:**
```javascript
// public/profile.html - displayAllTimeStats() function
const tetris = stats.games.tetris;
if (tetris) {
  cardsHTML += `
    <div class="bg-gradient-to-br from-blue-900/20 to-purple-900/20 rounded-lg p-3 border border-blue-500/30">
      <div class="flex items-center gap-2 mb-2">
        <span class="text-2xl">🧩</span>
        <span class="text-white font-semibold">${tetris.name || 'Tetris'}</span>
      </div>
      <div class="text-xs space-y-1">
        <div class="flex justify-between">
          <span class="text-gray-400">Total Games:</span>
          <span class="text-white font-semibold">${tetris.total_games ? tetris.total_games.toLocaleString() : 0}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-400">Best Score:</span>
          <span class="text-blue-300">${tetris.best_score ? tetris.best_score.toLocaleString() : 'N/A'}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-400">DSPOINC Earned:</span>
          <span class="text-yellow-300">${tetris.dspoinc_earned ? tetris.dspoinc_earned.toLocaleString() : 0}</span>
        </div>
      </div>
    </div>
  `;
}
```

### **API Endpoint: `/api/user/user-game-missions.php`**
- **Returns:** Tetris stats (total_games, best_score, dspoinc_earned)
- **Used By:** profile.html for displaying game statistics
- **Field:** Uses `discord_id` in query (NOT `user_id`)

---

## 🛍️ **SHOP SYSTEM INTEGRATION**

### **Store Upgrades Available:**
1. **Glow Effect** - Visual enhancement for blocks
2. **Reactor Upgrade** - Faster piece movement (hold delay reduction)

### **Store State Management:**
```javascript
// public/scripts/tetris-scroll.js
window.tetrisStoreState = window.tetrisStoreState || {
  glowOwned: false,
  glowColor: '#FFD700',
  reactorOwned: false,
  reactorEnabled: false
};

function applyTetrisStorePerks(stateOverride) {
  const state = stateOverride || {};
  window.tetrisStoreState = {
    glowOwned: Boolean(state.glowOwned),
    glowColor: state.glowColor || '#FFD700',
    reactorOwned: Boolean(state.reactorOwned),
    reactorEnabled: Boolean(state.reactorEnabled)
  };
  
  // Apply reactor speed boost
  tetrisHoldDelay = reactorOwned && reactorEnabled
    ? Math.max(10, TETRIS_HOLD_DELAY_BASE - 10)
    : TETRIS_HOLD_DELAY_BASE;
}
```

### **Store API Integration:**
- **Purchase API:** `/api/store/purchase.php`
- **Inventory API:** `/api/store/inventory.php`
- **Database Tables:** `tbl_store_items`, `tbl_user_inventory`
- **Profile Store:** Integrated in `profile.html` with instant sync

---

## 🏆 **ACHIEVEMENT SYSTEM INTEGRATION**

### **Achievement Loading:**
```javascript
// public/profile.html
async function loadTetrisAchievements() {
    const apiBaseUrl = getApiBaseUrl();
    const discordId = localStorage.getItem('discord_id');
    
    const response = await fetch(`${apiBaseUrl}/api/user/get-tetris-achievements.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: discordId })
    });
    
    const data = await response.json();
    
    if (data.success) {
        displayTetrisAchievements(data.achievements, data.definitions);
    }
}
```

### **Achievement Display:**
- **Profile.html Section:** Dedicated Tetris achievements section
- **Total Achievements:** 25 achievements
- **Dynamic Loading:** All descriptions loaded from database
- **Unlock Status:** Real-time display of unlocked vs locked achievements

### **Achievement API:**
- **Endpoint:** `/api/user/get-tetris-achievements.php`
- **Database:** `tbl_tetris_achievements`
- **Field:** Uses `user_id` (NOT `discord_id`)

---

## 🔗 **DISCORD INTEGRATION**

### **Role-Based Multipliers:**
```javascript
// public/scripts/tetris-scroll.js
async function loadUserRoles() {
    const apiBaseUrl = getApiBaseUrl();
    const discordId = localStorage.getItem('discord_id');
    
    const response = await fetch(`${apiBaseUrl}/api/user/roles.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: discordId })
    });
    
    const data = await response.json();
    
    if (data.success) {
        applyRoleTheme(data.roles);
        calculateRoleMultiplier(data.roles);
    }
}
```

### **Discord Role API:**
- **Endpoint:** `/api/user/roles.php`
- **Returns:** User's Discord roles with IDs
- **Multipliers:** Applied to DSPOINC rewards (1.1x → 2.0x)
- **Theme System:** Visual themes based on highest role

### **Discord Sync:**
- **Score Sync:** Scores sync to Discord bot `/balance` command
- **Achievement Sync:** Achievements visible in Discord profile
- **Role Sync:** Real-time role detection for multipliers

---

## 🖥️ **ADMIN INTERFACE INTEGRATION**

### **API Endpoint: `/api/admin/get-all-games-stats.php`**

#### **Tetris Statistics:**
```php
// Get Tetris stats
$tetrisStats = [
    'total_games' => $pdo->query("SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris'")->fetchColumn(),
    'unique_players' => $pdo->query("SELECT COUNT(DISTINCT discord_id) FROM tbl_tetris_scores WHERE game = 'tetris'")->fetchColumn(),
    'max_score' => $pdo->query("SELECT MAX(score) FROM tbl_tetris_scores WHERE game = 'tetris'")->fetchColumn(),
    'avg_score' => $pdo->query("SELECT AVG(score) FROM tbl_tetris_scores WHERE game = 'tetris'")->fetchColumn(),
    'total_dspoinc' => $pdo->query("SELECT SUM(score) FROM tbl_tetris_scores WHERE game = 'tetris'")->fetchColumn()
];
```

#### **Admin Interface Display:**
```javascript
// admin-interface.html
function displayTetrisStats(data) {
    const tetrisTab = document.getElementById('tetrisTab');
    
    tetrisTab.innerHTML = `
        <div class="stats-card">
            <h3>Tetris Statistics</h3>
            <p>Total Games: ${data.total_games}</p>
            <p>Unique Players: ${data.unique_players}</p>
            <p>Max Score: ${data.max_score} DSPOINC</p>
            <p>Average Score: ${Math.round(data.avg_score)} DSPOINC</p>
            <p>Total DSPOINC Awarded: ${data.total_dspoinc} DSPOINC</p>
        </div>
    `;
}
```

### **Season Management:**
```php
// Get season-specific stats
$seasonStats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_games,
        MAX(score) as max_score,
        AVG(score) as avg_score
    FROM tbl_tetris_scores
    WHERE game = 'tetris' AND season = ?
");
$seasonStats->execute([$currentSeason]);
```

---

## 💡 **CODE EXAMPLES**

### **Complete Game Save Flow:**
```javascript
// tetris-scroll.js
async function endGame() {
    // 1. Calculate final score
    const finalScore = calculateScore(linesCleared);
    
    // 2. Save score to database
    await saveTetrisScore(finalScore);
    
    // 3. Check and save achievements
    await saveTetrisAchievements();
    
    // 4. Update UI
    displayGameOver(finalScore);
    
    // 5. Admin interface auto-updates via API polling
}
```

### **Role Multiplier Application:**
```javascript
// tetris-scroll.js
function clearLines(lines) {
    // Calculate base score
    let baseScore = lines * 2;
    
    // Add multi-line bonus
    if (lines >= 2) {
        baseScore += (lines - 1);
    }
    
    // Apply role multiplier
    const roleMultiplier = getRoleMultiplier();
    const dspoincEarned = Math.round(baseScore * roleMultiplier);
    
    // Update game score
    gameScore += dspoincEarned;
    
    // Display with role bonus message
    showScoreMessage(`💰 +${dspoincEarned} DSPOINC (${roleMultiplier}x Role Bonus!)`);
}
```

---

## ✅ **TESTING & VERIFICATION**

### **Test Checklist:**
- [ ] Game saves scores correctly
- [ ] Role multipliers apply correctly
- [ ] Achievements unlock properly
- [ ] Admin interface displays stats
- [ ] Database queries use correct fields (`discord_id`)
- [ ] API endpoints return correct data
- [ ] Mobile controls work
- [ ] Sound system functions

### **Verification Commands:**
```sql
-- Verify score saved
SELECT * FROM tbl_tetris_scores WHERE discord_id = 'YOUR_DISCORD_ID' ORDER BY timestamp DESC LIMIT 1;

-- Verify achievement unlocked
SELECT * FROM tbl_tetris_achievements WHERE user_id = 'YOUR_DISCORD_ID' AND unlocked_at IS NOT NULL;

-- Verify admin stats
SELECT COUNT(*) as total_games, MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'tetris';
```

---

## 🚨 **CRITICAL RULES**

1. **ALWAYS use `discord_id`** for Tetris scores (NOT `user_id`)
2. **ALWAYS use `game = 'tetris'`** in `tbl_tetris_scores`
3. **ALWAYS use `user_id`** for achievements (NOT `discord_id`)
4. **ALWAYS load achievements dynamically** from database
5. **NEVER hardcode achievement descriptions** in frontend

---

**🧀 Complete technical documentation for Tetris - Ready for decades of development! 🧀**

