# 🎮 GAME MANAGEMENT FUNCTIONS IMPLEMENTATION

## 🎯 **CORE SEASON MANAGEMENT**

### **1. Season Control API Endpoints**
```php
// api/admin/season-management.php

function getCurrentSeason() {
    $db = getSQLite3Connection();
    $query = "SELECT * FROM tbl_seasons WHERE is_active = 1";
    $result = $db->query($query);
    return $result->fetchArray(SQLITE3_ASSOC);
}

function createNewSeason($name, $startDate) {
    $db = getSQLite3Connection();
    $db->exec('BEGIN TRANSACTION');
    
    try {
        // 1. Archive current season
        $db->exec("UPDATE tbl_tetris_scores SET is_current_season = 0, season_end_date = datetime('now') WHERE is_current_season = 1");
        $db->exec("UPDATE tbl_cheese_clicks SET is_current_season = 0, season_end_date = datetime('now') WHERE is_current_season = 1");
        $db->exec("UPDATE tbl_race_participants SET is_current_season = 0, season_end_date = datetime('now') WHERE is_current_season = 1");
        
        // 2. Deactivate current season
        $db->exec("UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1");
        
        // 3. Create new season
        $stmt = $db->prepare("INSERT INTO tbl_seasons (season_name, start_date, is_active) VALUES (:name, :start_date, 1)");
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':start_date', $startDate, SQLITE3_TEXT);
        $stmt->execute();
        
        // 4. Update season settings
        $db->exec("UPDATE tbl_season_settings SET season_name = '$name'");
        
        $db->exec('COMMIT');
        return ['success' => true];
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function switchActiveSeason($seasonId) {
    $db = getSQLite3Connection();
    $db->exec('BEGIN TRANSACTION');
    
    try {
        // 1. Get season info
        $stmt = $db->prepare("SELECT season_name FROM tbl_seasons WHERE season_id = :id");
        $stmt->bindValue(':id', $seasonId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $season = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$season) {
            throw new Exception("Season not found");
        }
        
        // 2. Deactivate current season
        $db->exec("UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1");
        
        // 3. Activate new season
        $stmt = $db->prepare("UPDATE tbl_seasons SET is_active = 1 WHERE season_id = :id");
        $stmt->bindValue(':id', $seasonId, SQLITE3_INTEGER);
        $stmt->execute();
        
        // 4. Update season settings
        $db->exec("UPDATE tbl_season_settings SET season_name = '{$season['season_name']}'");
        
        $db->exec('COMMIT');
        return ['success' => true];
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getSeasonStats($seasonId) {
    $db = getSQLite3Connection();
    
    // Get season info
    $stmt = $db->prepare("SELECT * FROM tbl_seasons WHERE season_id = :id");
    $stmt->bindValue(':id', $seasonId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $season = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$season) {
        return ['success' => false, 'error' => 'Season not found'];
    }
    
    // Get game stats
    $stats = [
        'tetris' => getGameStats($db, 'tetris', $season['season_name']),
        'snake' => getGameStats($db, 'snake', $season['season_name']),
        'space_invaders' => getGameStats($db, 'space_invaders', $season['season_name']),
        'cheese_hunt' => getCheeseHuntStats($db, $season['season_name']),
        'discord_race' => getRaceStats($db, $season['season_name'])
    ];
    
    return ['success' => true, 'season' => $season, 'stats' => $stats];
}

function getGameStats($db, $game, $season) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_games,
            COUNT(DISTINCT user_id) as unique_players,
            MAX(score) as high_score,
            AVG(score) as avg_score,
            SUM(score) as total_score
        FROM tbl_tetris_scores 
        WHERE game = :game AND season = :season
    ");
    $stmt->bindValue(':game', $game, SQLITE3_TEXT);
    $stmt->bindValue(':season', $season, SQLITE3_TEXT);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

function getCheeseHuntStats($db, $season) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_clicks,
            COUNT(DISTINCT user_wallet) as unique_players,
            COUNT(DISTINCT egg_id) as unique_eggs,
            COUNT(CASE WHEN quest_id IS NOT NULL THEN 1 END) as quest_clicks
        FROM tbl_cheese_clicks 
        WHERE season = :season
    ");
    $stmt->bindValue(':season', $season, SQLITE3_TEXT);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

function getRaceStats($db, $season) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT r.race_id) as total_races,
            COUNT(DISTINCT p.user_id) as unique_players,
            AVG(p.cheese_count) as avg_cheese_per_race,
            SUM(p.dspoinc_earned) as total_dspoinc
        FROM tbl_cheese_races r
        JOIN tbl_race_participants p ON r.race_id = p.race_id
        WHERE p.season = :season
    ");
    $stmt->bindValue(':season', $season, SQLITE3_TEXT);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}
```

### **2. Admin Interface Functions**
```javascript
// Game Management Core Functions

// Load all game data
async function loadGameManagementData() {
    try {
        // Load current season
        const seasonResponse = await fetch('/api/admin/season-management.php?action=getCurrentSeason');
        const seasonData = await seasonResponse.json();
        
        if (seasonData.success) {
            updateSeasonDisplay(seasonData.season);
        }
        
        // Load game stats
        await loadAllGameStats();
        
        // Start auto-refresh
        startGameDataRefresh();
    } catch (error) {
        console.error('Error loading game management data:', error);
        addLog('❌ Error loading game management data');
    }
}

// Load stats for all games
async function loadAllGameStats() {
    try {
        const response = await fetch('/api/admin/season-management.php?action=getSeasonStats');
        const data = await response.json();
        
        if (data.success) {
            updateGameStats(data.stats);
        }
    } catch (error) {
        console.error('Error loading game stats:', error);
        addLog('❌ Error loading game statistics');
    }
}

// Create new season
async function createNewSeason() {
    const name = document.getElementById('newSeasonName').value;
    const startDate = document.getElementById('newSeasonStart').value;
    
    if (!name || !startDate) {
        addLog('❌ Please provide season name and start date');
        return;
    }
    
    try {
        const response = await fetch('/api/admin/season-management.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'createNewSeason',
                name: name,
                startDate: startDate
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            addLog('✅ New season created successfully');
            loadGameManagementData();
        } else {
            addLog(`❌ Error creating season: ${data.error}`);
        }
    } catch (error) {
        console.error('Error creating season:', error);
        addLog('❌ Error creating new season');
    }
}

// Switch active season
async function switchActiveSeason(seasonId) {
    if (!confirm('Switch active season? This will affect all game data.')) return;
    
    try {
        const response = await fetch('/api/admin/season-management.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'switchActiveSeason',
                seasonId: seasonId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            addLog('✅ Season switched successfully');
            loadGameManagementData();
        } else {
            addLog(`❌ Error switching season: ${data.error}`);
        }
    } catch (error) {
        console.error('Error switching season:', error);
        addLog('❌ Error switching season');
    }
}

// Update game stats display
function updateGameStats(stats) {
    // Tetris Stats
    updateGameSection('tetris', stats.tetris);
    
    // Snake Stats
    updateGameSection('snake', stats.snake);
    
    // Space Invaders Stats
    updateGameSection('spaceInvaders', stats.space_invaders);
    
    // Cheese Hunt Stats
    updateCheeseHuntSection(stats.cheese_hunt);
    
    // Discord Race Stats
    updateRaceSection(stats.discord_race);
}

// Update individual game section
function updateGameSection(game, stats) {
    const section = document.getElementById(`${game}Section`);
    if (!section) return;
    
    section.querySelector('.total-games').textContent = stats.total_games;
    section.querySelector('.unique-players').textContent = stats.unique_players;
    section.querySelector('.high-score').textContent = stats.high_score;
    section.querySelector('.avg-score').textContent = Math.round(stats.avg_score);
}

// Update Cheese Hunt section
function updateCheeseHuntSection(stats) {
    const section = document.getElementById('cheeseHuntSection');
    if (!section) return;
    
    section.querySelector('.total-clicks').textContent = stats.total_clicks;
    section.querySelector('.unique-players').textContent = stats.unique_players;
    section.querySelector('.quest-clicks').textContent = stats.quest_clicks;
}

// Update Race section
function updateRaceSection(stats) {
    const section = document.getElementById('discordRaceSection');
    if (!section) return;
    
    section.querySelector('.total-races').textContent = stats.total_races;
    section.querySelector('.unique-players').textContent = stats.unique_players;
    section.querySelector('.total-dspoinc').textContent = stats.total_dspoinc;
}

// Auto-refresh game data
function startGameDataRefresh() {
    // Clear existing interval if any
    if (window.gameDataRefreshInterval) {
        clearInterval(window.gameDataRefreshInterval);
    }
    
    // Refresh every 30 seconds
    window.gameDataRefreshInterval = setInterval(loadAllGameStats, 30000);
}
```

### **3. HTML Structure**
```html
<div id="gameManagement" class="admin-card mb-6">
    <!-- Season Management -->
    <div class="season-header bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 id="currentSeason" class="text-2xl font-bold">Season 2</h2>
                <p class="text-sm opacity-80">
                    Started: <span id="seasonStart">2024-04-01</span>
                    Ends: <span id="seasonEnd">2024-06-30</span>
                </p>
            </div>
            <div>
                <p>Days Remaining: <span id="daysRemaining">64</span></p>
                <div class="progress-bar bg-white bg-opacity-20 rounded-full h-2 mt-2 w-48">
                    <div id="seasonProgress" class="bg-green-400 rounded-full h-2 transition-all duration-500"></div>
                </div>
            </div>
        </div>
        
        <!-- Season Controls -->
        <div class="flex gap-4 mt-6">
            <button onclick="createNewSeason()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                Create New Season
            </button>
            <button onclick="switchActiveSeason()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                Switch Season
            </button>
        </div>
    </div>
    
    <!-- Game Tabs -->
    <div class="game-tabs flex gap-2 mb-6">
        <button class="game-tab active" data-game="tetris">🎮 Tetris</button>
        <button class="game-tab" data-game="snake">🐍 Snake</button>
        <button class="game-tab" data-game="spaceInvaders">👾 Space Invaders</button>
        <button class="game-tab" data-game="cheeseHunt">🧀 Cheese Hunt</button>
        <button class="game-tab" data-game="discordRace">🏁 Discord Race</button>
    </div>
    
    <!-- Game Sections -->
    <div id="tetrisSection" class="game-section">
        <div class="grid grid-cols-4 gap-4">
            <div class="stat-card">
                <h4>Total Games</h4>
                <p class="total-games">0</p>
            </div>
            <div class="stat-card">
                <h4>Unique Players</h4>
                <p class="unique-players">0</p>
            </div>
            <div class="stat-card">
                <h4>High Score</h4>
                <p class="high-score">0</p>
            </div>
            <div class="stat-card">
                <h4>Average Score</h4>
                <p class="avg-score">0</p>
            </div>
        </div>
    </div>
    
    <!-- Repeat similar structure for other game sections -->
</div>
```

## 🔧 **IMPLEMENTATION STEPS**

1. Create the PHP endpoints in `api/admin/season-management.php`
2. Add the JavaScript functions to `admin-interface.html`
3. Add the HTML structure to `admin-interface.html`
4. Test all functionality:
   - Season creation
   - Season switching
   - Stats display
   - Auto-refresh

Would you like me to start implementing these changes? I can:
1. Create the PHP endpoints first
2. Update the admin interface
3. Do both in parallel

Please let me know which approach you prefer!
