# 🐍 GAME 2: SNAKE - COMPLETE TECHNICAL DOCUMENTATION 2025

**Created:** December 20, 2025  
**Status:** ✅ **PRODUCTION READY - COMPLETE INTEGRATION**  
**Version:** 5.6.0 - Season 5 Stable  
**Purpose:** Complete technical reference for Snake game integration in Narrrfs World

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
9. [Boss System](#boss-system)
10. [Admin Interface Integration](#admin-interface-integration)
11. [Code Examples](#code-examples)
12. [Testing & Verification](#testing--verification)

---

## 🎯 **OVERVIEW**

### **Game Description:**
Cheese Snake Scroll is a classic arcade game where players control a growing snake, collecting cheese pieces while avoiding walls and their own tail. The game features role-based multipliers, 20 achievements, Giant Cheese Snake Boss battles, and full integration with Narrrfs World ecosystem.

### **Key Features:**
- ✅ Role-based DSPOINC multipliers (1.1x → 2.0x)
- ✅ 20 achievements with dynamic loading
- ✅ Giant Cheese Snake Boss system (9 progressive bosses)
- ✅ Golden Apple system for boss battles
- ✅ Store upgrades (Serpent Velocity Core, Golden Apple Booster)
- ✅ Mobile-optimized touch controls
- ✅ Professional sound system

### **Integration Status:**
- ✅ **Frontend:** `public/snake.html`, `public/scripts/snake-scroll.js`
- ✅ **Backend:** `/api/dev/save-score.php`
- ✅ **Database:** `tbl_tetris_scores` (game: 'snake'), `tbl_snake_achievements`
- ✅ **Admin Interface:** Full integration via `/api/admin/get-all-games-stats.php`

---

## 🏗️ **ARCHITECTURE**

### **System Flow:**
```
User Opens snake.html
        ↓
Discord Authentication Check
        ↓
Load User Roles (for multipliers)
        ↓
Initialize Game Canvas
        ↓
Game Loop (Snake movement, cheese collection)
        ↓
Boss Spawn Check (every 10 levels)
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
├── snake.html                    # Main game page
└── scripts/
    └── snake-scroll.js          # Game logic (2,500+ lines)
```

### **Key Functions:**

#### **1. Game Initialization:**
```javascript
// snake-scroll.js
function startSnakeGame() {
    // Initialize canvas
    const canvas = document.getElementById('snake-canvas');
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
// snake-scroll.js
function calculateScore(cheeseEaten) {
    const baseScore = cheeseEaten * 10; // 10 DSPOINC per cheese
    const roleMultiplier = getRoleMultiplier(); // 1.0x - 2.0x
    const finalScore = Math.round(baseScore * roleMultiplier);
    
    return finalScore;
}
```

#### **3. Achievement Checking:**
```javascript
// snake-scroll.js
function checkSnakeAchievements() {
    const achievements = [
        { key: 'first_cheese', check: () => cheeseEaten >= 1 },
        { key: 'cheese_collector', check: () => cheeseEaten >= 5 },
        { key: 'score_hunter', check: () => score >= 200 },
        { key: 'long_snake', check: () => longestSnake >= 10 },
        // ... 16 more achievements
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
// snake-scroll.js
async function saveSnakeScore() {
    const discordId = localStorage.getItem('discord_id');
    const score = gameScore; // Final DSPOINC including multiplier
    
    const response = await fetch('/api/dev/save-score.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            game: 'snake',
            discord_id: discordId,
            score: score,
            cheese_eaten: cheeseEaten,
            level_reached: currentLevel
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
    "game": "snake",
    "discord_id": "1107633105185013790",
    "score": 150,
    "cheese_eaten": 15,
    "level_reached": 3
}
```

#### **Response Format:**
```json
{
    "success": true,
    "message": "Score saved successfully",
    "data": {
        "game": "snake",
        "score": 150,
        "dspoinc_earned": 150,
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

if ($game === 'snake') {
    // Insert into tbl_tetris_scores (SAME table as Tetris!)
    $stmt = $pdo->prepare("
        INSERT INTO tbl_tetris_scores 
        (discord_id, game, score, cheese_eaten, level_reached, timestamp)
        VALUES (?, 'snake', ?, ?, ?, datetime('now'))
    ");
    
    $stmt->execute([
        $discordId,
        $score,
        $input['cheese_eaten'] ?? 0,
        $input['level_reached'] ?? 0
    ]);
    
    // Update DSPOINC balance in tbl_user_scores
    updateDspoincBalance($discordId, $score, 'snake');
    
    echo json_encode(['success' => true, 'message' => 'Score saved']);
}
?>
```

**CRITICAL:** Uses `tbl_tetris_scores` table with `game = 'snake'` identifier!

### **Achievement API: `/api/user/get-snake-achievements.php`**

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
            "achievement_key": "first_cheese",
            "achievement_title": "First Cheese",
            "achievement_description": "Eat your first cheese",
            "achievement_icon": "🧀",
            "unlocked_at": "2025-12-20 10:30:00"
        }
        // ... more achievements
    ],
    "definitions": [
        // All 20 achievement definitions
    ]
}
```

---

## 🗄️ **DATABASE SCHEMA**

### **1. Score Table: `tbl_tetris_scores`**

**CRITICAL:** Snake uses the SAME table as Tetris, differentiated by `game` field!

```sql
CREATE TABLE tbl_tetris_scores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    discord_id TEXT NOT NULL,              -- Discord ID (NOT user_id!)
    game TEXT NOT NULL DEFAULT 'tetris',   -- Game identifier: 'snake'
    score INTEGER NOT NULL,                -- Final DSPOINC (with multiplier)
    cheese_eaten INTEGER DEFAULT 0,       -- Cheese collected in game
    level_reached INTEGER DEFAULT 0,      -- Level reached
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    season TEXT,                          -- Season identifier
    FOREIGN KEY (discord_id) REFERENCES tbl_users(discord_id)
);

CREATE INDEX idx_tetris_discord_id ON tbl_tetris_scores(discord_id);
CREATE INDEX idx_tetris_game ON tbl_tetris_scores(game);
CREATE INDEX idx_tetris_season ON tbl_tetris_scores(season);
```

**CRITICAL:** Uses `discord_id` field (NOT `user_id`) and `game = 'snake'`!

### **2. Achievement Table: `tbl_snake_achievements`**

```sql
CREATE TABLE tbl_snake_achievements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,                    -- Discord ID or 'ACHIEVEMENT_DEFINITIONS'
    achievement_key TEXT NOT NULL,            -- Unique identifier
    achievement_title TEXT NOT NULL,          -- Display name
    achievement_description TEXT NOT NULL,     -- Description
    achievement_icon TEXT NOT NULL,           -- Emoji icon
    unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES tbl_users(discord_id)
);

CREATE INDEX idx_snake_achievements_user ON tbl_snake_achievements(user_id);
CREATE INDEX idx_snake_achievements_key ON tbl_snake_achievements(achievement_key);
```

**Special Record:** `user_id = 'ACHIEVEMENT_DEFINITIONS'` contains all 20 achievement definitions.

### **3. Query Examples:**

#### **Get User Scores:**
```sql
SELECT 
    COUNT(*) as total_games,
    MAX(score) as best_score,
    SUM(score) as total_dspoinc,
    SUM(cheese_eaten) as total_cheese
FROM tbl_tetris_scores
WHERE discord_id = ? AND game = 'snake';
```

#### **Get User Achievements:**
```sql
SELECT 
    achievement_key,
    achievement_title,
    achievement_description,
    achievement_icon,
    unlocked_at
FROM tbl_snake_achievements
WHERE user_id = ? AND unlocked_at IS NOT NULL;
```

---

## 📊 **SCORING SYSTEM**

### **Base Scoring:**
- **Per Cheese:** 10 DSPOINC
- **Role Multiplier:** Applied to total score

### **Role Multipliers:**
| Role | Multiplier | Example (15 cheese) |
|------|-----------|---------------------|
| VIP Holder | 2.0x | 150 DSPOINC → 300 DSPOINC |
| Holder | 1.5x | 150 DSPOINC → 225 DSPOINC |
| Champion | 1.4x | 150 DSPOINC → 210 DSPOINC |
| Season Tester | 1.3x | 150 DSPOINC → 195 DSPOINC |
| Early Bird | 1.2x | 150 DSPOINC → 180 DSPOINC |
| Cheese Hunter | 1.1x | 150 DSPOINC → 165 DSPOINC |
| No Role | 1.0x | 150 DSPOINC → 150 DSPOINC |

### **Score Calculation Flow:**
```javascript
// 1. Calculate base score
const baseScore = cheeseEaten * 10; // 10 DSPOINC per cheese

// 2. Apply role multiplier
const roleMultiplier = getRoleMultiplier(); // 1.0x - 2.0x
const finalScore = Math.round(baseScore * roleMultiplier);

// 3. Save to database
saveSnakeScore(finalScore);
```

---

## 🏆 **ACHIEVEMENT SYSTEM**

### **20 Total Achievements:**

#### **Cheese-Based (5):**
- First Cheese: 1 cheese
- Cheese Collector: 5 cheeses
- Cheese Hunter: 10 cheeses
- Cheese Master: 25 cheeses
- Cheese Legend: 75 cheeses

#### **Score-Based (6):**
- Score Hunter: 200 DSPOINC
- Point Master: 500 DSPOINC
- High Scorer: 1,000 DSPOINC
- Snake King: 1,500 DSPOINC
- Score Legend: 2,000 DSPOINC
- Score God: 3,500 DSPOINC

#### **Level-Based (4):**
- Speed Demon: Level 5
- Level Master: Level 10
- Level Warrior: Level 15
- Level Champion: Level 20

#### **Length-Based (3):**
- Long Snake: 10 segments
- Giant Snake: 25 segments
- Mega Snake: 50 segments

#### **Time-Based (2):**
- Survivor: 2 minutes
- Endurance Master: 5 minutes

### **Achievement Loading:**
```javascript
// Load from database (dynamic, no hardcoding)
async function loadSnakeAchievements() {
    const response = await fetch('/api/user/get-snake-achievements.php', {
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

## 🐍 **BOSS SYSTEM**

### **Giant Cheese Snake Boss - 9 Total Bosses:**

| Boss | Cheeses | DSPOINC | Lives | Apples | Intelligence |
|------|---------|---------|-------|--------|-------------|
| 🍼 Baby | 5 | +30 | +1 | 5 | 15% |
| Boss 2 | 25 | +50 | +1 | 10 | 20% |
| Boss 3 | 60 | +80 | +1 | 10 | 30% |
| Boss 4 | 110 | +120 | +2 | 10 | 45% |
| Boss 5 | 175 | +170 | +2 | 10 | 60% |
| Boss 6 | 260 | +230 | +3 | 10 | 75% |
| Boss 7 | 370 | +300 | +3 | 10 | 85% |
| Boss 8 | 500 | +400 | +4 | 10 | 90% |
| Boss 9 | 650 | +550 | +5 | 10 | 95% |

**Total if all 9 defeated:** 1,930 DSPOINC! 🏆

### **Boss Battle Flow:**
```javascript
// snake-scroll.js
function checkBossSpawn() {
    const bossSpawnPoints = [5, 25, 60, 110, 175, 260, 370, 500, 650];
    
    if (bossSpawnPoints.includes(cheeseEaten)) {
        spawnGiantSnakeBoss();
        pauseGameForCountdown();
        startBossBattle();
    }
}

function spawnGiantSnakeBoss() {
    const bossNumber = getBossNumber(cheeseEaten);
    giantSnakeBoss = new GiantCheeseSnakeBoss({
        level: currentLevel,
        bossNumber: bossNumber,
        goldenApplesRequired: bossNumber === 1 ? 5 : 10
    });
}
```

---

## 🖥️ **ADMIN INTERFACE INTEGRATION**

### **API Endpoint: `/api/admin/get-all-games-stats.php`**

#### **Snake Statistics:**
```php
// Get Snake stats
$snakeStats = [
    'total_games' => $pdo->query("SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake'")->fetchColumn(),
    'unique_players' => $pdo->query("SELECT COUNT(DISTINCT discord_id) FROM tbl_tetris_scores WHERE game = 'snake'")->fetchColumn(),
    'max_score' => $pdo->query("SELECT MAX(score) FROM tbl_tetris_scores WHERE game = 'snake'")->fetchColumn(),
    'avg_score' => $pdo->query("SELECT AVG(score) FROM tbl_tetris_scores WHERE game = 'snake'")->fetchColumn(),
    'total_dspoinc' => $pdo->query("SELECT SUM(score) FROM tbl_tetris_scores WHERE game = 'snake'")->fetchColumn()
];
```

#### **Admin Interface Display:**
```javascript
// admin-interface.html
function displaySnakeStats(data) {
    const snakeTab = document.getElementById('snakeTab');
    
    snakeTab.innerHTML = `
        <div class="stats-card">
            <h3>Snake Statistics</h3>
            <p>Total Games: ${data.total_games}</p>
            <p>Unique Players: ${data.unique_players}</p>
            <p>Max Score: ${data.max_score} DSPOINC</p>
            <p>Average Score: ${Math.round(data.avg_score)} DSPOINC</p>
            <p>Total DSPOINC Awarded: ${data.total_dspoinc} DSPOINC</p>
        </div>
    `;
}
```

---

## 💡 **CODE EXAMPLES**

### **Complete Game Save Flow:**
```javascript
// snake-scroll.js
async function endGame() {
    // 1. Calculate final score
    const finalScore = calculateScore(cheeseEaten);
    
    // 2. Save score to database
    await saveSnakeScore(finalScore);
    
    // 3. Check and save achievements
    await saveSnakeAchievements();
    
    // 4. Update UI
    displayGameOver(finalScore);
    
    // 5. Admin interface auto-updates via API polling
}
```

### **Role Multiplier Application:**
```javascript
// snake-scroll.js
function eatCheese() {
    cheeseEaten++;
    
    // Calculate base score
    const baseScore = cheeseEaten * 10;
    
    // Apply role multiplier
    const roleMultiplier = getRoleMultiplier();
    const dspoincEarned = Math.round(baseScore * roleMultiplier);
    
    // Update game score
    gameScore = dspoincEarned;
    
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
- [ ] Boss system spawns correctly
- [ ] Admin interface displays stats
- [ ] Database queries use correct fields (`discord_id`, `game = 'snake'`)
- [ ] API endpoints return correct data
- [ ] Mobile controls work

### **Verification Commands:**
```sql
-- Verify score saved
SELECT * FROM tbl_tetris_scores WHERE discord_id = 'YOUR_DISCORD_ID' AND game = 'snake' ORDER BY timestamp DESC LIMIT 1;

-- Verify achievement unlocked
SELECT * FROM tbl_snake_achievements WHERE user_id = 'YOUR_DISCORD_ID' AND unlocked_at IS NOT NULL;

-- Verify admin stats
SELECT COUNT(*) as total_games, MAX(score) as max_score FROM tbl_tetris_scores WHERE game = 'snake';
```

---

## 🚨 **CRITICAL RULES**

1. **ALWAYS use `discord_id`** for Snake scores (NOT `user_id`)
2. **ALWAYS use `game = 'snake'`** in `tbl_tetris_scores` (SAME table as Tetris!)
3. **ALWAYS use `user_id`** for achievements (NOT `discord_id`)
4. **ALWAYS load achievements dynamically** from database
5. **NEVER hardcode achievement descriptions** in frontend
6. **CRITICAL:** Snake uses `tbl_tetris_scores` table, NOT a separate table!

---

**🧀 Complete technical documentation for Snake - Ready for decades of development! 🧀**

