# 👾 GAME 3: SPACE INVADERS - COMPLETE TECHNICAL DOCUMENTATION 2025

**Created:** December 20, 2025  
**Status:** ✅ **PRODUCTION READY - COMPLETE INTEGRATION**  
**Version:** 5.2.0 - Season 5 Stable  
**Purpose:** Complete technical reference for Space Invaders game integration in Narrrfs World

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
9. [Boss Systems](#boss-systems)
10. [Enemy Systems](#enemy-systems)
11. [Admin Interface Integration](#admin-interface-integration)
12. [Code Examples](#code-examples)
13. [Testing & Verification](#testing--verification)

---

## 🎯 **OVERVIEW**

### **Game Description:**
Space Cheese Invaders is a modern space shooter with extensive features including 5 enemy types, 4 boss battles, Giant Cheese Boss system, Phoenix shooting mechanics, 28 achievements, and full integration with Narrrfs World ecosystem.

### **Key Features:**
- ✅ Role-based DSPOINC multipliers (1.1x → 2.0x)
- ✅ 28 achievements with dynamic loading
- ✅ 4 Main Bosses (Cheese King, Emperor, God, Destroyer)
- ✅ Giant Cheese Boss system (every 8th wave)
- ✅ Phoenix shooting system (every 4th wave)
- ✅ 10:1 score conversion (perfect game balance)
- ✅ Multiple weapon types (Normal, Laser, Bomb)
- ✅ Power-up system
- ✅ Mobile-optimized controls

### **Integration Status:**
- ✅ **Frontend:** `public/space-cheese-invaders.html`, `public/scripts/space-cheese-invaders.js`
- ✅ **Backend:** `/api/dev/save-score.php`
- ✅ **Database:** `tbl_tetris_scores` (game: 'space_invaders'), `tbl_space_invaders_achievements`
- ✅ **Admin Interface:** Full integration via `/api/admin/get-all-games-stats.php`

---

## 🏗️ **ARCHITECTURE**

### **System Flow:**
```
User Opens space-cheese-invaders.html
        ↓
Discord Authentication Check
        ↓
Load User Roles (for multipliers)
        ↓
Initialize Game Canvas
        ↓
Game Loop (Enemy movement, shooting, collisions)
        ↓
Wave Progression (Formation → Attack → Boss)
        ↓
Boss Spawn Check (waves 10, 25, 75, 100)
        ↓
Giant Cheese Boss Check (every 8th wave)
        ↓
Phoenix Wave Check (every 4th wave)
        ↓
Score Calculation (with role multiplier, 10:1 conversion)
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
├── space-cheese-invaders.html    # Main game page
└── scripts/
    └── space-cheese-invaders.js  # Game logic (3,500+ lines)
```

### **Key Functions:**

#### **1. Game Initialization:**
```javascript
// space-cheese-invaders.js
function startGame() {
    // Initialize canvas
    const canvas = document.getElementById('game-canvas');
    const ctx = canvas.getContext('2d');
    
    // Load user roles for multipliers
    loadUserRoles().then(roles => {
        applyRoleTheme(roles);
        calculateRoleMultiplier(roles);
    });
    
    // Load existing achievements (prevent duplicate popups)
    loadExistingAchievements();
    
    // Start game loop
    gameLoop();
}
```

#### **2. Score Calculation with Role Multiplier:**
```javascript
// space-cheese-invaders.js
function calculateScore(kills, comboMultiplier) {
    // Base score from kills (raw points)
    const rawScore = kills * 10; // Example: 100 kills = 1000 raw points
    
    // Apply combo multiplier
    const comboScore = rawScore * comboMultiplier;
    
    // Convert to DSPOINC (10:1 ratio)
    const baseDspoinc = Math.floor(comboScore / 10);
    
    // Apply role multiplier
    const roleMultiplier = getRoleMultiplier(); // 1.0x - 2.0x
    const finalScore = Math.round(baseDspoinc * roleMultiplier);
    
    return finalScore;
}
```

#### **3. Achievement Checking:**
```javascript
// space-cheese-invaders.js
function checkAchievements() {
    const achievements = [
        { key: 'firstKill', check: () => totalKills >= 100 },
        { key: 'score2500', check: () => spaceInvadersScore >= 1000 },
        { key: 'bossKiller1', check: () => bossesKilled >= 1 },
        { key: 'phoenixHunter', check: () => phoenixesDestroyed >= 10 },
        // ... 24 more achievements
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
// space-cheese-invaders.js
async function saveSpaceInvadersScore() {
    const discordId = localStorage.getItem('discord_id');
    const score = spaceInvadersScore; // Final DSPOINC including multiplier
    
    const response = await fetch('/api/dev/save-score.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            game: 'space_invaders',
            discord_id: discordId,
            score: score,
            total_kills: totalKills,
            bosses_killed: bossesKilled,
            wave_reached: currentWave
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
    "game": "space_invaders",
    "discord_id": "1107633105185013790",
    "score": 200,
    "total_kills": 200,
    "bosses_killed": 2,
    "wave_reached": 30
}
```

#### **Response Format:**
```json
{
    "success": true,
    "message": "Score saved successfully",
    "data": {
        "game": "space_invaders",
        "score": 200,
        "dspoinc_earned": 200,
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

if ($game === 'space_invaders') {
    // Insert into tbl_tetris_scores (SAME table as Tetris & Snake!)
    $stmt = $pdo->prepare("
        INSERT INTO tbl_tetris_scores 
        (discord_id, game, score, total_kills, bosses_killed, wave_reached, timestamp)
        VALUES (?, 'space_invaders', ?, ?, ?, ?, datetime('now'))
    ");
    
    $stmt->execute([
        $discordId,
        $score,
        $input['total_kills'] ?? 0,
        $input['bosses_killed'] ?? 0,
        $input['wave_reached'] ?? 0
    ]);
    
    // Update DSPOINC balance in tbl_user_scores
    updateDspoincBalance($discordId, $score, 'space_invaders');
    
    echo json_encode(['success' => true, 'message' => 'Score saved']);
}
?>
```

**CRITICAL:** Uses `tbl_tetris_scores` table with `game = 'space_invaders'` identifier!

### **Achievement API: `/api/user/get-space-invaders-achievements.php`**

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
            "achievement_key": "firstKill",
            "achievement_title": "First Blood",
            "achievement_description": "Destroyed your first 100 invaders!",
            "achievement_icon": "🎯",
            "unlocked_at": "2025-12-20 10:30:00",
            "total_kills": 100
        }
        // ... more achievements
    ],
    "definitions": [
        // All 28 achievement definitions
    ]
}
```

---

## 🗄️ **DATABASE SCHEMA**

### **1. Score Table: `tbl_tetris_scores`**

**CRITICAL:** Space Invaders uses the SAME table as Tetris & Snake, differentiated by `game` field!

```sql
CREATE TABLE tbl_tetris_scores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    discord_id TEXT NOT NULL,              -- Discord ID (NOT user_id!)
    game TEXT NOT NULL DEFAULT 'tetris',  -- Game identifier: 'space_invaders'
    score INTEGER NOT NULL,                -- Final DSPOINC (with multiplier)
    total_kills INTEGER DEFAULT 0,         -- Total enemies killed
    bosses_killed INTEGER DEFAULT 0,     -- Bosses defeated
    wave_reached INTEGER DEFAULT 0,       -- Wave reached
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    season TEXT,                          -- Season identifier
    FOREIGN KEY (discord_id) REFERENCES tbl_users(discord_id)
);

CREATE INDEX idx_tetris_discord_id ON tbl_tetris_scores(discord_id);
CREATE INDEX idx_tetris_game ON tbl_tetris_scores(game);
CREATE INDEX idx_tetris_season ON tbl_tetris_scores(season);
```

**CRITICAL:** Uses `discord_id` field (NOT `user_id`) and `game = 'space_invaders'`!

### **2. Achievement Table: `tbl_space_invaders_achievements`**

```sql
CREATE TABLE tbl_space_invaders_achievements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,                    -- Discord ID or 'ACHIEVEMENT_DEFINITIONS'
    achievement_key TEXT NOT NULL,            -- Unique identifier
    achievement_title TEXT NOT NULL,          -- Display name
    achievement_description TEXT NOT NULL,   -- Description
    achievement_icon TEXT NOT NULL,           -- Emoji icon
    unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    game_score INTEGER DEFAULT 0,             -- Score threshold
    total_kills INTEGER DEFAULT 0,           -- Kills threshold
    bosses_killed INTEGER DEFAULT 0,          -- Bosses threshold
    phoenixes_destroyed INTEGER DEFAULT 0,   -- Phoenix threshold
    FOREIGN KEY (user_id) REFERENCES tbl_users(discord_id)
);

CREATE INDEX idx_space_achievements_user ON tbl_space_invaders_achievements(user_id);
CREATE INDEX idx_space_achievements_key ON tbl_space_invaders_achievements(achievement_key);
```

**Special Record:** `user_id = 'ACHIEVEMENT_DEFINITIONS'` contains all 28 achievement definitions.

### **3. Query Examples:**

#### **Get User Scores:**
```sql
SELECT 
    COUNT(*) as total_games,
    MAX(score) as best_score,
    SUM(score) as total_dspoinc,
    SUM(total_kills) as total_kills,
    MAX(wave_reached) as best_wave
FROM tbl_tetris_scores
WHERE discord_id = ? AND game = 'space_invaders';
```

#### **Get User Achievements:**
```sql
SELECT 
    achievement_key,
    achievement_title,
    achievement_description,
    achievement_icon,
    unlocked_at
FROM tbl_space_invaders_achievements
WHERE user_id = ? AND unlocked_at IS NOT NULL;
```

---

## 📊 **SCORING SYSTEM**

### **Base Scoring:**
- **Per Kill:** 10 raw points
- **Combo Multiplier:** Up to 4x (consecutive kills)
- **10:1 Conversion:** Raw points ÷ 10 = DSPOINC
- **Role Multiplier:** Applied to final DSPOINC

### **Score Calculation Example:**
```javascript
// 100 kills with 2x combo = 2000 raw points
// 2000 ÷ 10 = 200 DSPOINC base
// 200 × 2.0x (VIP Holder) = 400 DSPOINC final
```

### **Role Multipliers:**
| Role | Multiplier | Example (200 DSPOINC base) |
|------|-----------|---------------------------|
| VIP Holder | 2.0x | 200 → 400 DSPOINC |
| Holder | 1.5x | 200 → 300 DSPOINC |
| Champion | 1.4x | 200 → 280 DSPOINC |
| Season Tester | 1.3x | 200 → 260 DSPOINC |
| Early Bird | 1.2x | 200 → 240 DSPOINC |
| Cheese Hunter | 1.1x | 200 → 220 DSPOINC |
| No Role | 1.0x | 200 → 200 DSPOINC |

### **Score Calculation Flow:**
```javascript
// 1. Calculate raw score from kills
const rawScore = totalKills * 10;

// 2. Apply combo multiplier
const comboScore = rawScore * comboMultiplier;

// 3. Convert to DSPOINC (10:1 ratio)
const baseDspoinc = Math.floor(comboScore / 10);

// 4. Apply role multiplier
const roleMultiplier = getRoleMultiplier();
const finalScore = Math.round(baseDspoinc * roleMultiplier);

// 5. Save to database
saveSpaceInvadersScore(finalScore);
```

---

## 🏆 **ACHIEVEMENT SYSTEM**

### **28 Total Achievements:**

#### **Kill-Based (4):**
- First Blood: 100 total kills
- Killing Spree: 25 kill combo
- Rampage: 50 kill combo
- Unstoppable: 100 kill combo

#### **Score-Based (4):**
- Getting Started: 1,000 DSPOINC
- Rising Star: 5,000 DSPOINC
- Space Ace: 10,000 DSPOINC
- Legend: 20,000 DSPOINC (maximum)

#### **Skill-Based (5):**
- Perfect Wave: 5 waves without damage
- Untouchable: 5 minutes without damage
- Combo Master: 4x multiplier
- Speed Demon: 5,000 DSPOINC in 3 minutes
- Ultimate Survivor: 20 minutes survival

#### **Boss-Based (4):**
- Boss Novice: 1 boss (Wave 10)
- Boss Veteran: 2 bosses (Wave 25)
- Boss Slayer: 3 bosses (Wave 75)
- Boss Destroyer: 4 bosses (Wave 100)

#### **Phoenix-Based (4):**
- Phoenix Hunter: 10 phoenixes
- Phoenix Slayer: 25 phoenixes
- Phoenix Destroyer: 50 phoenixes
- Phoenix Master: 100 phoenixes

#### **Egg-Based (4):**
- Egg Hunter: 50 eggs
- Egg Slayer: 100 eggs
- Egg Destroyer: 150 eggs
- Egg Master: 250 eggs

#### **Mini-Phoenix-Based (3):**
- Mini-Phoenix Hunter: 25 mini
- Mini-Phoenix Slayer: 50 mini
- Mini-Phoenix Master: 75 mini

### **Achievement Loading:**
```javascript
// Load from database (dynamic, no hardcoding)
async function loadExistingAchievements() {
    const discordId = localStorage.getItem('discord_id');
    
    const response = await fetch('/api/user/get-space-invaders-achievements.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: discordId })
    });
    
    const data = await response.json();
    
    // Mark achievements as unlocked locally (prevent duplicate popups)
    if (data.success && data.achievements) {
        data.achievements.forEach(achievement => {
            if (achievement.unlocked_at) {
                achievements[achievement.achievement_key] = true;
            }
        });
    }
}
```

---

## 👑 **BOSS SYSTEMS**

### **Main Bosses (4 Total):**
| Boss | Wave | Name | HP | Reward |
|------|------|------|-----|--------|
| Boss 1 | 10 | Cheese King | 100 | +50 DSPOINC |
| Boss 2 | 25 | Cheese Emperor | 200 | +100 DSPOINC |
| Boss 3 | 75 | Cheese God | 400 | +200 DSPOINC |
| Boss 4 | 100 | Cheese Destroyer | 800 | +500 DSPOINC |

### **Giant Cheese Boss (Every 8th Wave):**
- **Spawn:** Waves 8, 16, 24, 32, 40, 48...
- **HP:** Progressive scaling (50 → 150+)
- **Designs:** 6 unique Tetris-block patterns
- **Reward:** Progressive DSPOINC bonuses

---

## 🖥️ **ADMIN INTERFACE INTEGRATION**

### **API Endpoint: `/api/admin/get-all-games-stats.php`**

#### **Space Invaders Statistics:**
```php
// Get Space Invaders stats
$spaceStats = [
    'total_games' => $pdo->query("SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders'")->fetchColumn(),
    'unique_players' => $pdo->query("SELECT COUNT(DISTINCT discord_id) FROM tbl_tetris_scores WHERE game = 'space_invaders'")->fetchColumn(),
    'max_score' => $pdo->query("SELECT MAX(score) FROM tbl_tetris_scores WHERE game = 'space_invaders'")->fetchColumn(),
    'avg_score' => $pdo->query("SELECT AVG(score) FROM tbl_tetris_scores WHERE game = 'space_invaders'")->fetchColumn(),
    'total_dspoinc' => $pdo->query("SELECT SUM(score) FROM tbl_tetris_scores WHERE game = 'space_invaders'")->fetchColumn(),
    'max_wave' => $pdo->query("SELECT MAX(wave_reached) FROM tbl_tetris_scores WHERE game = 'space_invaders'")->fetchColumn()
];
```

---

## 💡 **CODE EXAMPLES**

### **Complete Game Save Flow:**
```javascript
// space-cheese-invaders.js
async function endGame() {
    // 1. Calculate final score
    const finalScore = calculateScore(totalKills, comboMultiplier);
    
    // 2. Save score to database
    await saveSpaceInvadersScore(finalScore);
    
    // 3. Check and save achievements
    await saveAchievementsToDatabase();
    
    // 4. Update UI
    displayGameOver(finalScore);
    
    // 5. Admin interface auto-updates via API polling
}
```

---

## ✅ **TESTING & VERIFICATION**

### **Test Checklist:**
- [ ] Game saves scores correctly
- [ ] Role multipliers apply correctly
- [ ] 10:1 score conversion works
- [ ] Achievements unlock properly
- [ ] Boss system spawns correctly
- [ ] Admin interface displays stats
- [ ] Database queries use correct fields (`discord_id`, `game = 'space_invaders'`)

### **Verification Commands:**
```sql
-- Verify score saved
SELECT * FROM tbl_tetris_scores WHERE discord_id = 'YOUR_DISCORD_ID' AND game = 'space_invaders' ORDER BY timestamp DESC LIMIT 1;

-- Verify achievement unlocked
SELECT * FROM tbl_space_invaders_achievements WHERE user_id = 'YOUR_DISCORD_ID' AND unlocked_at IS NOT NULL;
```

---

## 🚨 **CRITICAL RULES**

1. **ALWAYS use `discord_id`** for Space Invaders scores (NOT `user_id`)
2. **ALWAYS use `game = 'space_invaders'`** in `tbl_tetris_scores` (SAME table as Tetris & Snake!)
3. **ALWAYS use `user_id`** for achievements (NOT `discord_id`)
4. **ALWAYS apply 10:1 score conversion** (raw points ÷ 10 = DSPOINC)
5. **ALWAYS load achievements dynamically** from database
6. **NEVER hardcode achievement descriptions** in frontend

---

**🧀 Complete technical documentation for Space Invaders - Ready for decades of development! 🧀**

