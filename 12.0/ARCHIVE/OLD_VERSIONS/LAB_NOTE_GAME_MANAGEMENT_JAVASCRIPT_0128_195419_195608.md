# 🎮 GAME MANAGEMENT JAVASCRIPT FUNCTIONS

## 🎯 **CORE FUNCTIONS TO ADD**

### **1. Season Management Functions**
```javascript
// Load game management data
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
```

### **2. Game Data Loading Functions**
```javascript
// Load all game stats
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
    section.querySelector('.unique-eggs').textContent = stats.unique_eggs;
}

// Update Race section
function updateRaceSection(stats) {
    const section = document.getElementById('discordRaceSection');
    if (!section) return;
    
    section.querySelector('.total-races').textContent = stats.total_races;
    section.querySelector('.unique-players').textContent = stats.unique_players;
    section.querySelector('.avg-cheese').textContent = Math.round(stats.avg_cheese_per_race);
    section.querySelector('.total-dspoinc').textContent = stats.total_dspoinc;
}
```

### **3. UI Management Functions**
```javascript
// Update season display
function updateSeasonDisplay(season) {
    document.getElementById('currentSeason').textContent = season.season_name;
    document.getElementById('seasonStart').textContent = formatDate(season.start_date);
    document.getElementById('seasonEnd').textContent = season.end_date ? formatDate(season.end_date) : 'Ongoing';
    
    // Calculate days remaining
    const endDate = new Date(season.end_date || '2024-12-31');
    const today = new Date();
    const daysRemaining = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24));
    document.getElementById('daysRemaining').textContent = daysRemaining;
    
    // Update progress bar
    const startDate = new Date(season.start_date);
    const totalDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
    const daysPassed = Math.ceil((today - startDate) / (1000 * 60 * 60 * 24));
    const progress = Math.min(100, Math.max(0, (daysPassed / totalDays) * 100));
    
    const progressBar = document.getElementById('seasonProgress');
    if (progressBar) {
        progressBar.style.width = `${progress}%`;
    }
}

// Handle game tab switching
function switchGameTab(tabName) {
    // Hide all game sections
    document.querySelectorAll('.game-section').forEach(section => {
        section.style.display = 'none';
    });
    
    // Show selected section
    const selectedSection = document.getElementById(`${tabName}Section`);
    if (selectedSection) {
        selectedSection.style.display = 'block';
    }
    
    // Update tab buttons
    document.querySelectorAll('.game-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    const selectedTab = document.querySelector(`.game-tab[data-game="${tabName}"]`);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
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

// Format date helper
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}
```

### **4. Event Listeners**
```javascript
// Add event listeners when tab loads
function initializeGameManagement() {
    // Game tab switching
    document.querySelectorAll('.game-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const game = tab.getAttribute('data-game');
            if (game) {
                switchGameTab(game);
            }
        });
    });
    
    // Load initial data
    loadGameManagementData();
}

// Add to showTab function
if (tabName === 'games') {
    initializeGameManagement();
}
```

## 🔧 **IMPLEMENTATION STEPS**

1. Add these functions to `admin-interface.html`
2. Add event listeners
3. Test functionality:
   - Season display
   - Game stats
   - Tab switching
   - Auto-refresh

Would you like me to start implementing these functions in the admin interface?
