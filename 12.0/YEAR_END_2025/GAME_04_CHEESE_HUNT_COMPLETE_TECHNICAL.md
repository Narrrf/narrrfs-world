# 🧀 GAME 4: CHEESE HUNT - COMPLETE TECHNICAL DOCUMENTATION 2025

**Created:** December 20, 2025  
**Status:** ✅ **PRODUCTION READY - COMPLETE INTEGRATION**  
**Version:** 3.0 - Personality-Based System  
**Purpose:** Complete technical reference for Cheese Hunt game integration in Narrrfs World

---

## 📋 **TABLE OF CONTENTS**

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Frontend Implementation](#frontend-implementation)
4. [Backend API Integration](#backend-api-integration)
5. [Database Schema](#database-schema)
6. [Click Tracking System](#click-tracking-system)
7. [Personality System](#personality-system)
8. [Quest Integration](#quest-integration)
9. [3D Game Integration](#3d-game-integration)
10. [Admin Interface Integration](#admin-interface-integration)
11. [Code Examples](#code-examples)
12. [Testing & Verification](#testing--verification)

---

## 🎯 **OVERVIEW**

### **Game Description:**
Cheese Hunt is an interactive mini-game on the index page where 3 cheese eggs move around with personality-based intelligent movement. Players must click them to earn DSPOINC rewards and complete quests. The game features three distinct cheese personalities, quest integration, and full integration with Narrrfs World ecosystem.

### **Key Features:**
- ✅ 3 Cheese Eggs with unique personalities
- ✅ Personality-based intelligent movement
- ✅ Quest system integration
- ✅ Click tracking with database logging
- ✅ 3D game integration (Cheese Temple)
- ✅ Mobile-optimized touch controls
- ✅ Visual effects and sound system

### **Integration Status:**
- ✅ **Frontend:** `public/index.html` (lines 364-950)
- ✅ **Backend:** `/api/track-egg-click.php`, `/api/dev/cheese-hunt-capture.php`
- ✅ **Database:** `tbl_cheese_clicks`, `tbl_cheese_hunt_captures`
- ✅ **Admin Interface:** Full integration via `/api/admin/get-all-games-stats.php`

---

## 🏗️ **ARCHITECTURE**

### **System Flow:**
```
User Opens index.html
        ↓
Cheese Eggs Spawn (3 eggs with personalities)
        ↓
Personality-Based Movement (different patterns)
        ↓
User Clicks Cheese Egg
        ↓
Click Tracking API Call
        ↓
Quest Progress Check
        ↓
Database Logging (tbl_cheese_clicks)
        ↓
DSPOINC Reward (if applicable)
        ↓
Update Admin Interface Statistics
```

### **Technology Stack:**
- **Frontend:** HTML5, Vanilla JavaScript, CSS3, Tailwind CSS
- **Backend:** PHP 8.x, SQLite3
- **Authentication:** Discord OAuth 2.0
- **APIs:** RESTful endpoints
- **Database:** SQLite (`narrrf_world.sqlite`)

---

## 💻 **FRONTEND IMPLEMENTATION**

### **File Structure:**
```
public/
└── index.html                    # Main page with Cheese Hunt (lines 364-950)
```

### **Key Functions:**

#### **1. Cheese Egg Initialization:**
```javascript
// index.html
function initCheeseHunt() {
    // Create 3 cheese eggs
    const cheeses = [
        { id: 'cheese-egg', type: 'yellow', personality: 0 },      // Wild Jumper
        { id: 'cheese-egg-finance', type: 'orange', personality: 1 }, // Teleporter
        { id: 'cheese-egg-blue', type: 'blue', personality: 2 }      // Page Jumper
    ];
    
    cheeses.forEach(cheese => {
        createCheeseEgg(cheese);
        startCheeseMovement(cheese);
    });
}
```

#### **2. Personality-Based Movement:**
```javascript
// index.html
function moveEgg(eggElement, personality) {
    const baseInterval = 1000 + Math.random() * 6500; // 1-7.5 seconds
    
    let interval;
    switch(personality) {
        case 0: // Wild Jumper (Yellow)
            interval = baseInterval * 0.6; // 0.6-4.5 seconds
            moveToViewport(eggElement);
            break;
        case 1: // Teleporter (Orange)
            interval = baseInterval * 1.0; // 1-7.5 seconds
            moveToPageOrViewport(eggElement, 0.6); // 60% entire page
            break;
        case 2: // Page Jumper (Blue)
            interval = baseInterval * 1.2; // 1.2-9 seconds
            moveToPageSections(eggElement, 0.5); // 50% page sections
            break;
    }
    
    setTimeout(() => moveEgg(eggElement, personality), interval);
}
```

#### **3. Click Tracking:**
```javascript
// index.html
async function trackCheeseClick(eggId) {
    const userWallet = localStorage.getItem('discord_id') || localStorage.getItem('user_wallet');
    const questId = getActiveQuestId(); // From quest system
    
    const response = await fetch('/api/track-egg-click.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            user_wallet: userWallet,
            egg_id: eggId,
            timestamp: Date.now(),
            quest_id: questId,
            hunt_context: 'enhanced_tracking_v2'
        })
    });
    
    const result = await response.json();
    
    if (result.success) {
        showClickFeedback(result);
        updateQuestProgress(result);
    }
}
```

---

## 🔌 **BACKEND API INTEGRATION**

### **API Endpoint 1: `/api/track-egg-click.php`**

#### **Request Format:**
```json
{
    "user_wallet": "1107633105185013790",
    "egg_id": "cheese-egg",
    "timestamp": 1729974000000,
    "quest_id": 123,
    "hunt_context": "enhanced_tracking_v2"
}
```

#### **Response Format:**
```json
{
    "success": true,
    "message": "🧀 Cheese click logged: cheese-egg by 1107633105185013790",
    "timestamp": 1729974000,
    "insert_id": 12345,
    "quest_completed": false,
    "progress": "2/5 eggs found"
}
```

#### **PHP Implementation:**
```php
// api/track-egg-click.php
<?php
require_once __DIR__ . '/config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$userWallet = trim($input['user_wallet']);
$eggId = trim($input['egg_id']);
$timestamp = isset($input['timestamp']) ? ($input['timestamp'] / 1000) : time();
$questId = isset($input['quest_id']) ? intval($input['quest_id']) : null;

$pdo = getDatabaseConnection();

// Insert into tbl_cheese_clicks
$stmt = $pdo->prepare("
    INSERT INTO tbl_cheese_clicks (user_wallet, egg_id, timestamp, quest_id)
    VALUES (?, ?, datetime(?, 'unixepoch'), ?)
");
$stmt->execute([$userWallet, $eggId, $timestamp, $questId]);

// Check quest progress
if ($questId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT egg_id) as unique_eggs 
        FROM tbl_cheese_clicks 
        WHERE quest_id = ? AND user_wallet = ?
    ");
    $stmt->execute([$questId, $userWallet]);
    $eggCount = $stmt->fetch(PDO::FETCH_ASSOC)['unique_eggs'];
    
    // Check if quest completed
    // ... quest completion logic
}

echo json_encode(['success' => true, 'message' => 'Click tracked']);
?>
```

**CRITICAL:** Uses `user_wallet` field (NOT `discord_id` or `user_id`)!

### **API Endpoint 2: `/api/dev/cheese-hunt-capture.php` (3D Game)**

#### **Request Format:**
```json
{
    "discord_id": "1107633105185013790",
    "discord_name": "PlayerName",
    "level_id": "CHEESE_TEMPLE_LVL1",
    "base_reward": 50,
    "session_id": "session_123",
    "capture_index": 1
}
```

#### **Response Format:**
```json
{
    "success": true,
    "base_reward": 50,
    "multiplier": 2.0,
    "total_reward": 100,
    "dspoinc_balance": 5000
}
```

#### **PHP Implementation:**
```php
// api/dev/cheese-hunt-capture.php
<?php
require_once __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$discordId = $data['discord_id'];
$baseReward = $data['base_reward'] ?? 50;
$levelId = $data['level_id'] ?? 'CHEESE_TEMPLE_LVL1';

// Get role multiplier
[$multiplier, $source] = getRoleMultiplier($pdo, $discordId, ...);
$totalReward = round($baseReward * $multiplier);

// Insert into tbl_cheese_hunt_captures
$stmt = $pdo->prepare("
    INSERT INTO tbl_cheese_hunt_captures 
    (discord_id, discord_name, level_id, base_reward, multiplier, total_reward, session_id)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([$discordId, $data['discord_name'], $levelId, $baseReward, $multiplier, $totalReward, $data['session_id']]);

// Update DSPOINC balance
updateDspoincBalance($discordId, $totalReward, 'cheese_hunt_3d');

echo json_encode(['success' => true, 'total_reward' => $totalReward]);
?>
```

---

## 🗄️ **DATABASE SCHEMA**

### **1. Click Table: `tbl_cheese_clicks`**

```sql
CREATE TABLE tbl_cheese_clicks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_wallet TEXT NOT NULL,              -- Discord ID or wallet (NOT discord_id!)
    egg_id TEXT NOT NULL,                   -- Cheese egg identifier
    clicks INTEGER DEFAULT 1,               -- Click count
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    quest_id INTEGER,                       -- Quest identifier (if applicable)
    screenshot TEXT,                        -- Screenshot path (if applicable)
    season TEXT,                           -- Season identifier
    FOREIGN KEY (user_wallet) REFERENCES tbl_users(discord_id)
);

CREATE INDEX idx_cheese_clicks_wallet ON tbl_cheese_clicks(user_wallet);
CREATE INDEX idx_cheese_clicks_quest ON tbl_cheese_clicks(quest_id);
CREATE INDEX idx_cheese_clicks_season ON tbl_cheese_clicks(season);
```

**CRITICAL:** Uses `user_wallet` field (NOT `discord_id` or `user_id`)!

### **2. 3D Capture Table: `tbl_cheese_hunt_captures`**

```sql
CREATE TABLE tbl_cheese_hunt_captures (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    discord_id TEXT NOT NULL,               -- Discord ID
    discord_name TEXT,                      -- Player name
    level_id TEXT NOT NULL,                 -- Level identifier
    base_reward INTEGER NOT NULL,           -- Base DSPOINC reward
    multiplier REAL NOT NULL,               -- Role multiplier applied
    total_reward INTEGER NOT NULL,          -- Final DSPOINC (base × multiplier)
    capture_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    session_id TEXT,                        -- Game session identifier
    metadata TEXT                           -- Additional metadata (JSON)
);

CREATE INDEX idx_captures_discord ON tbl_cheese_hunt_captures(discord_id);
CREATE INDEX idx_captures_level ON tbl_cheese_hunt_captures(level_id);
CREATE INDEX idx_captures_time ON tbl_cheese_hunt_captures(capture_time);
```

### **3. Query Examples:**

#### **Get User Clicks:**
```sql
SELECT 
    COUNT(*) as total_clicks,
    COUNT(DISTINCT egg_id) as unique_eggs,
    MAX(timestamp) as last_click
FROM tbl_cheese_clicks
WHERE user_wallet = ?;
```

#### **Get Quest Progress:**
```sql
SELECT 
    COUNT(DISTINCT egg_id) as unique_eggs,
    COUNT(*) as total_clicks
FROM tbl_cheese_clicks
WHERE quest_id = ? AND user_wallet = ?;
```

#### **Get 3D Game Captures:**
```sql
SELECT 
    COUNT(*) as total_captures,
    SUM(total_reward) as total_dspoinc,
    AVG(multiplier) as avg_multiplier
FROM tbl_cheese_hunt_captures
WHERE discord_id = ?;
```

---

## 🧠 **PERSONALITY SYSTEM**

### **Cheese #1: Yellow (Wild Jumper)**
- **Type:** `cheese-egg`
- **Personality:** 0 (Wild Jumper)
- **Movement:** Random positions in current viewport
- **Speed:** Fast (0.6-4.5 seconds stand time)
- **Coverage:** Current viewport only
- **Difficulty:** ⭐⭐ Medium
- **Strategy:** Stay on current page section

### **Cheese #2: Orange (Teleporter)**
- **Type:** `cheese-egg-finance`
- **Personality:** 1 (Teleporter)
- **Movement:** 60% entire page, 40% current viewport
- **Speed:** Medium (1-7.5 seconds stand time)
- **Coverage:** Entire page (top to bottom)
- **Difficulty:** ⭐⭐⭐ Hard
- **Strategy:** Scroll entire page to find

### **Cheese #3: Blue (Page Jumper)**
- **Type:** `cheese-egg-blue`
- **Personality:** 2 (Page Jumper)
- **Movement:** 50% page sections, 50% current viewport
- **Speed:** Slower (1.2-9 seconds stand time)
- **Coverage:** 5 page sections (0%, 25%, 50%, 75%, 100%)
- **Difficulty:** ⭐⭐ Medium-Hard
- **Strategy:** Check different page sections

### **Movement Algorithm:**
```javascript
// Personality-based movement
function moveEgg(eggElement, personality) {
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight;
    const currentScroll = window.scrollY || window.pageYOffset;
    
    let newLeft, newTop;
    
    switch(personality) {
        case 0: // Wild Jumper - Viewport only
            newLeft = 50 + Math.random() * (viewportWidth - 90);
            newTop = currentScroll + 50 + Math.random() * (viewportHeight - 90);
            break;
        case 1: // Teleporter - 60% entire page
            if (Math.random() < 0.6) {
                newLeft = 50 + Math.random() * (viewportWidth - 90);
                newTop = 50 + Math.random() * (documentHeight - 90);
            } else {
                newLeft = 50 + Math.random() * (viewportWidth - 90);
                newTop = currentScroll + 50 + Math.random() * (viewportHeight - 90);
            }
            break;
        case 2: // Page Jumper - 50% page sections
            if (Math.random() < 0.5) {
                const section = Math.floor(Math.random() * 5); // 0-4
                const sectionTop = (documentHeight / 5) * section;
                newLeft = 50 + Math.random() * (viewportWidth - 90);
                newTop = sectionTop + 50 + Math.random() * (documentHeight / 5 - 90);
            } else {
                newLeft = 50 + Math.random() * (viewportWidth - 90);
                newTop = currentScroll + 50 + Math.random() * (viewportHeight - 90);
            }
            break;
    }
    
    eggElement.style.left = newLeft + 'px';
    eggElement.style.top = newTop + 'px';
}
```

---

## 🎯 **QUEST INTEGRATION**

### **Active Quest API: `/api/get-active-cheese-hunt.php`**

#### **Response Format:**
```json
{
    "success": true,
    "quest": {
        "quest_id": 123,
        "description": "Click 5 cheese to claim reward",
        "reward": 12345,
        "is_active": true,
        "cheese_config": {
            "cheese_count": 5,
            "screenshot_required": false,
            "discord_ticket": false
        }
    }
}
```

### **Quest Completion Flow:**
```javascript
// Check quest progress after each click
async function checkQuestProgress(questId, userWallet) {
    const response = await fetch('/api/track-egg-click.php', {
        method: 'POST',
        body: JSON.stringify({
            user_wallet: userWallet,
            egg_id: clickedEggId,
            quest_id: questId
        })
    });
    
    const result = await response.json();
    
    if (result.quest_completed) {
        showQuestComplete(result);
        claimQuestReward(questId);
    } else {
        updateQuestProgress(result.progress);
    }
}
```

---

## 🎮 **3D GAME INTEGRATION**

### **Cheese Temple Capture System:**
- **Endpoint:** `/api/dev/cheese-hunt-capture.php`
- **Purpose:** Awards DSPOINC for cheese captures in 3D game
- **Role Multipliers:** Applied (VIP 2.0x, Holder 1.5x, etc.)
- **Logging:** `tbl_cheese_hunt_captures` + `tbl_user_scores`

### **Integration Flow:**
```javascript
// three.js/main.js
async function captureCheese(cheeseObject) {
    const discordId = localStorage.getItem('discord_id');
    const baseReward = 50; // Base DSPOINC per capture
    
    const response = await fetch('/api/dev/cheese-hunt-capture.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            discord_id: discordId,
            discord_name: playerDisplayName,
            level_id: currentLevelId,
            base_reward: baseReward,
            session_id: gameSessionId,
            capture_index: captureCount
        })
    });
    
    const result = await response.json();
    
    if (result.success) {
        updateHUD(result.total_reward);
        showCaptureFeedback(result);
    }
}
```

---

## 🖥️ **ADMIN INTERFACE INTEGRATION**

### **API Endpoint: `/api/admin/get-all-games-stats.php`**

#### **Cheese Hunt Statistics:**
```php
// Get Cheese Hunt stats
$cheeseStats = [
    'total_clicks' => $pdo->query("SELECT COUNT(*) FROM tbl_cheese_clicks")->fetchColumn(),
    'unique_players' => $pdo->query("SELECT COUNT(DISTINCT user_wallet) FROM tbl_cheese_clicks")->fetchColumn(),
    'clicks_last_24h' => $pdo->query("SELECT COUNT(*) FROM tbl_cheese_clicks WHERE timestamp >= datetime('now', '-1 day')")->fetchColumn(),
    'most_active_egg' => $pdo->query("SELECT egg_id, COUNT(*) as count FROM tbl_cheese_clicks GROUP BY egg_id ORDER BY count DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC)
];
```

#### **Admin Interface Display:**
```javascript
// admin-interface.html
function displayCheeseHuntStats(data) {
    const cheeseTab = document.getElementById('cheeseHuntTab');
    
    cheeseTab.innerHTML = `
        <div class="stats-card">
            <h3>Cheese Hunt Statistics</h3>
            <p>Total Clicks: ${data.total_clicks}</p>
            <p>Unique Players: ${data.unique_players}</p>
            <p>Clicks Last 24h: ${data.clicks_last_24h}</p>
            <p>Most Active Egg: ${data.most_active_egg.egg_id} (${data.most_active_egg.count} clicks)</p>
        </div>
    `;
}
```

---

## 💡 **CODE EXAMPLES**

### **Complete Click Flow:**
```javascript
// index.html
function handleCheeseClick(event) {
    const eggId = event.target.id;
    const userWallet = localStorage.getItem('discord_id');
    
    // Play sound
    playCheeseClickSound();
    
    // Visual feedback
    showClickAnimation(event.target);
    
    // Track click
    trackCheeseClick(eggId).then(result => {
        if (result.success) {
            updateClickCounter();
            checkQuestProgress(result);
        }
    });
    
    // Move egg to new position
    moveEgg(event.target, getPersonality(eggId));
}
```

### **Personality Detection:**
```javascript
// index.html
function getPersonality(eggId) {
    const personalities = {
        'cheese-egg': 0,           // Wild Jumper
        'cheese-egg-finance': 1,   // Teleporter
        'cheese-egg-blue': 2       // Page Jumper
    };
    return personalities[eggId] || 0;
}
```

---

## ✅ **TESTING & VERIFICATION**

### **Test Checklist:**
- [ ] All 3 cheeses spawn correctly
- [ ] Movement patterns work as designed
- [ ] Click tracking records to database
- [ ] Quest integration functional
- [ ] 3D game captures work
- [ ] Admin interface displays stats
- [ ] Database queries use correct fields (`user_wallet`)
- [ ] Mobile touch controls work

### **Verification Commands:**
```sql
-- Verify click saved
SELECT * FROM tbl_cheese_clicks WHERE user_wallet = 'YOUR_DISCORD_ID' ORDER BY timestamp DESC LIMIT 1;

-- Verify 3D capture
SELECT * FROM tbl_cheese_hunt_captures WHERE discord_id = 'YOUR_DISCORD_ID' ORDER BY capture_time DESC LIMIT 1;

-- Verify admin stats
SELECT COUNT(*) as total_clicks, COUNT(DISTINCT user_wallet) as unique_players FROM tbl_cheese_clicks;
```

---

## 🚨 **CRITICAL RULES**

1. **ALWAYS use `user_wallet`** for Cheese Hunt clicks (NOT `discord_id` or `user_id`)
2. **ALWAYS use `tbl_cheese_clicks`** table for web game clicks
3. **ALWAYS use `tbl_cheese_hunt_captures`** table for 3D game captures
4. **ALWAYS use `discord_id`** for 3D game captures (NOT `user_wallet`)
5. **CRITICAL:** Field names differ between web game and 3D game!

---

**🧀 Complete technical documentation for Cheese Hunt - Ready for decades of development! 🧀**

