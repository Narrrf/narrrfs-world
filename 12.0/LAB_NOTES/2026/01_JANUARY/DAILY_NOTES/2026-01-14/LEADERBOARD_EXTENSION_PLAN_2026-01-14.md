# 🏆 Leaderboard Page Extension Plan

**Date:** January 14, 2026  
**Status:** 📋 **PLAN - READY FOR IMPLEMENTATION**  
**Purpose:** Extend leaderboard page to show all 6 games with profile pictures and highest roles

---

## 🎯 **OBJECTIVE**

Extend the leaderboard page (`leaderboard.html`) to:
1. Show all 6 games (currently only shows 3: Tetris, Snake, Space Invaders)
2. Add the missing 3 games: Cheese Hunt, Discord Cheese Race, Cheese Rumble
3. Display Discord profile pictures for each user
4. Display highest role instead of just numbers under user names
5. Use the same data structure as the profile page's "Current Season Statistics"

---

## 📊 **CURRENT STATE ANALYSIS**

### **Current Leaderboard Page:**
- ✅ Shows 3 games: Tetris, Snake, Space Invaders
- ✅ Uses `/api/dev/get-leaderboard.php` API
- ❌ Missing: Cheese Hunt, Discord Cheese Race, Cheese Rumble
- ❌ Missing: Profile pictures (Discord avatars)
- ❌ Missing: Highest role display (currently shows Discord ID)

### **Profile Page "Current Season Statistics":**
- ✅ Shows all 6 games via `/api/user/user-game-missions.php`
- ✅ Games shown:
  1. Tetris (tbl_tetris_scores, game='tetris')
  2. Snake (tbl_tetris_scores, game='snake')
  3. Space Invaders (tbl_tetris_scores, game='space_invaders')
  4. Cheese Hunt (tbl_cheese_clicks)
  5. Discord Cheese Race (tbl_race_participants)
  6. Cheese Rumble (tbl_rumble_participants)

### **Available APIs:**
- ✅ `/api/dev/get-leaderboard.php` - Returns top 10 for 3 games (needs extension)
- ✅ `/api/user/user-game-missions.php` - Returns user's season stats for all 6 games
- ✅ `/api/user/details.php` - Returns user info (avatar_url, roles)
- ✅ `/api/user/profile.php` - Returns user profile (avatar_url, roles)

---

## 🔧 **IMPLEMENTATION PLAN**

### **Phase 1: Extend Leaderboard API (`/api/dev/get-leaderboard.php`)**

#### **1.1 Add Cheese Hunt Leaderboard**
- **Table:** `tbl_cheese_clicks`
- **Field:** `user_wallet` (contains Discord ID)
- **Metric:** `MAX(clicks)` - Highest total clicks
- **Season Filter:** Filter by current season (same as other games)
- **Order By:** `clicks DESC`
- **Limit:** Top 10 players

#### **1.2 Add Discord Cheese Race Leaderboard**
- **Table:** `tbl_race_participants`
- **Field:** `user_id` (contains Discord ID)
- **Metric:** `COUNT(*) as total_races` - Total races participated
- **Alternative Metrics:** `wins`, `podiums`, `best_position`
- **Season Filter:** Filter by current season
- **Order By:** `total_races DESC` or `wins DESC`
- **Limit:** Top 10 players

#### **1.3 Add Cheese Rumble Leaderboard**
- **Table:** `tbl_rumble_participants`
- **Field:** `user_id` (contains Discord ID)
- **Metric:** `COUNT(*) as total_rumbles` - Total rumbles participated
- **Alternative Metrics:** `wins`, `podiums`, `best_position`
- **Season Filter:** Filter by timestamp (created_at >= season_start)
- **Order By:** `total_rumbles DESC` or `wins DESC`
- **Limit:** Top 10 players

#### **1.4 API Response Structure**
```json
{
  "success": true,
  "current_season": "Season 7",
  "display_season": "Season 7",
  "is_frozen": false,
  "tetris": [
    {
      "discord_id": "...",
      "discord_name": "...",
      "score": 1234,
      "timestamp": "..."
    }
  ],
  "snake": [...],
  "space_invaders": [...],
  "cheese_hunt": [
    {
      "discord_id": "...",
      "discord_name": "...",
      "score": 567,  // total clicks
      "timestamp": "..."
    }
  ],
  "discord_race": [
    {
      "discord_id": "...",
      "discord_name": "...",
      "score": 15,  // total races or wins
      "timestamp": "..."
    }
  ],
  "cheese_rumble": [
    {
      "discord_id": "...",
      "discord_name": "...",
      "score": 8,  // total rumbles or wins
      "timestamp": "..."
    }
  ]
}
```

---

### **Phase 2: Add User Info Fetching (Avatar + Roles)**

#### **2.1 Fetch User Details for Leaderboard Entries**
- **API:** `/api/user/details.php` or `/api/user/profile.php`
- **Method:** Batch fetch or individual fetch per user
- **Data Needed:**
  - `avatar_url` - Discord profile picture
  - `roles` - Array of user roles
  - `discord_name` - Username (may already have from leaderboard)

#### **2.2 Determine Highest Role**
- **Role Priority Hierarchy** (based on multiplier system from rules):
  1. 🎴 VIP Holder (2.0x multiplier) - **HIGHEST PRIORITY**
  2. 🏆 Holder (1.5x multiplier)
  3. 🔴 Champion (1.4x multiplier)
  4. 🟢 Season Tester (1.3x multiplier)
  5. 🔵 Early Bird (1.2x multiplier)
  6. 🧀 Cheese Hunter (1.1x multiplier)
  7. Default: No role / Basic member

- **Implementation:**
  - Create `getHighestRole(roles)` function
  - Check roles array in priority order
  - Return first matching role (highest priority)
  - Return "Member" or empty if no roles match

#### **2.3 Optimize User Info Fetching**
- **Option 1: Batch Fetch** (Recommended)
  - Collect all unique Discord IDs from all 6 leaderboards
  - Fetch user details in one or a few API calls
  - Cache results in JavaScript object

- **Option 2: Extend Leaderboard API**
  - Modify `/api/dev/get-leaderboard.php` to join with `tbl_users` and `tbl_user_roles`
  - Include `avatar_url` and `roles` in leaderboard response
  - Single API call returns all needed data

**Recommendation:** Option 2 (Extend API) - More efficient, single API call

---

### **Phase 3: Update Frontend (`leaderboard.html`)**

#### **3.1 Add 3 New Game Sections**
- Add HTML sections for:
  - Cheese Hunt Leaderboard
  - Discord Cheese Race Leaderboard
  - Cheese Rumble Leaderboard

#### **3.2 Update Leaderboard Display Function**
- Modify `displayLeaderboard()` function to:
  - Display profile picture (Discord avatar)
  - Display highest role under username
  - Handle different score types (clicks, races, rumbles vs game scores)

#### **3.3 Profile Picture Display**
- **Format:** `<img src="{avatar_url}" alt="{username}" class="rounded-full w-12 h-12" />`
- **Fallback:** Default avatar if no `avatar_url`
- **Position:** Left of username, before rank medal

#### **3.4 Highest Role Display**
- **Format:** Show role text under username
- **Styling:** Color-coded based on role (gold for VIP, silver for Holder, etc.)
- **Position:** Under username, replace Discord ID

---

## 📋 **DETAILED IMPLEMENTATION STEPS**

### **Step 1: Extend Leaderboard API**

**File:** `api/dev/get-leaderboard.php`

**Changes:**
1. Add `getCheeseHuntLeaderboard()` function
   - Query: `SELECT user_wallet as discord_id, MAX(clicks) as score, MIN(timestamp) as timestamp FROM tbl_cheese_clicks WHERE season = ? GROUP BY user_wallet ORDER BY score DESC LIMIT 10`
   - Join with `tbl_users` to get `discord_name`

2. Add `getDiscordRaceLeaderboard()` function
   - Query: `SELECT user_id as discord_id, COUNT(*) as total_races, COUNT(CASE WHEN position = 1 THEN 1 END) as wins, MIN(finished_at) as timestamp FROM tbl_race_participants WHERE season = ? GROUP BY user_id ORDER BY wins DESC, total_races DESC LIMIT 10`
   - Use `total_races` or `wins` as score metric

3. Add `getCheeseRumbleLeaderboard()` function
   - Query: `SELECT rp.user_id as discord_id, COUNT(*) as total_rumbles, COUNT(CASE WHEN rp.final_position = 1 THEN 1 END) as wins, MIN(cr.created_at) as timestamp FROM tbl_rumble_participants rp JOIN tbl_cheese_rumbles cr ON rp.rumble_id = cr.rumble_id WHERE datetime(replace(replace(cr.created_at, 'T', ' '), 'Z', '')) >= datetime(?) AND datetime(replace(replace(cr.created_at, 'T', ' '), 'Z', '')) < datetime(?) GROUP BY rp.user_id ORDER BY wins DESC, total_rumbles DESC LIMIT 10`
   - Use timestamp filtering for season (similar to user-game-missions.php)

4. Join with `tbl_users` for all leaderboards to get `discord_name` and `avatar_url`
5. Join with `tbl_user_roles` to get user roles

**Enhanced API Response:**
```json
{
  "success": true,
  "current_season": "Season 7",
  "display_season": "Season 7",
  "is_frozen": false,
  "tetris": [
    {
      "discord_id": "...",
      "discord_name": "...",
      "avatar_url": "https://cdn.discordapp.com/avatars/...",
      "roles": ["VIP Holder", "Holder"],
      "highest_role": "VIP Holder",
      "score": 1234,
      "timestamp": "..."
    }
  ],
  "snake": [...],
  "space_invaders": [...],
  "cheese_hunt": [...],
  "discord_race": [...],
  "cheese_rumble": [...]
}
```

---

### **Step 2: Add Highest Role Calculation**

**File:** `api/dev/get-leaderboard.php`

**Add Helper Function:**
```php
function getHighestRole($roles) {
    if (empty($roles) || !is_array($roles)) {
        return null;
    }
    
    $rolePriority = [
        'VIP Holder' => 1,
        'Holder' => 2,
        'Champion' => 3,
        'Season Tester' => 4,
        'WL' => 4,  // Same as Season Tester
        'Early Bird' => 5,
        'Cheese Hunter' => 6
    ];
    
    $highestRole = null;
    $highestPriority = 999;
    
    foreach ($roles as $role) {
        // Clean role name (remove emojis)
        $cleanRole = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $role);
        $cleanRole = trim($cleanRole);
        
        // Check for exact match
        if (isset($rolePriority[$cleanRole])) {
            $priority = $rolePriority[$cleanRole];
            if ($priority < $highestPriority) {
                $highestPriority = $priority;
                $highestRole = $role; // Keep original with emoji
            }
        }
    }
    
    return $highestRole;
}
```

**Apply to each leaderboard entry:**
```php
$highestRole = getHighestRole($userRoles);
$entry['highest_role'] = $highestRole;
```

---

### **Step 3: Update Frontend HTML**

**File:** `public/leaderboard.html`

**Add 3 New Game Sections:**
```html
<!-- Cheese Hunt Leaderboard -->
<div class="leaderboard-card rounded-xl p-6 mb-6">
  <h2 class="text-2xl font-bold text-yellow-300 mb-4 flex items-center gap-2">
    <span>🧀</span>
    <span>Cheese Hunt Leaderboard</span>
  </h2>
  <div id="cheese-hunt-leaderboard" class="space-y-2">
    <!-- Populated by JavaScript -->
  </div>
</div>

<!-- Discord Cheese Race Leaderboard -->
<div class="leaderboard-card rounded-xl p-6 mb-6">
  <h2 class="text-2xl font-bold text-blue-300 mb-4 flex items-center gap-2">
    <span>🏁</span>
    <span>Discord Cheese Race Leaderboard</span>
  </h2>
  <div id="discord-race-leaderboard" class="space-y-2">
    <!-- Populated by JavaScript -->
  </div>
</div>

<!-- Cheese Rumble Leaderboard -->
<div class="leaderboard-card rounded-xl p-6 mb-6">
  <h2 class="text-2xl font-bold text-red-300 mb-4 flex items-center gap-2">
    <span>💥</span>
    <span>Cheese Rumble Leaderboard</span>
  </h2>
  <div id="cheese-rumble-leaderboard" class="space-y-2">
    <!-- Populated by JavaScript -->
  </div>
</div>
```

---

### **Step 4: Update JavaScript Display Function**

**File:** `public/leaderboard.html`

**Enhanced `displayLeaderboard()` Function:**
```javascript
function displayLeaderboard(game, leaderboard, isFrozen) {
  const containerId = `${game}-leaderboard`;
  const container = document.getElementById(containerId);
  
  if (!container) {
    console.error(`Container not found: ${containerId}`);
    return;
  }

  if (!leaderboard || leaderboard.length === 0) {
    container.innerHTML = `
      <div class="text-center py-8 text-gray-400">
        ${isFrozen ? '⏸️ No scores available for frozen season' : '🎮 No scores yet! Be the first to play!'}
      </div>
    `;
    return;
  }

  container.innerHTML = leaderboard.map((entry, index) => {
    const rank = index + 1;
    const rankClass = rank === 1 ? 'rank-1' : rank === 2 ? 'rank-2' : rank === 3 ? 'rank-3' : '';
    const medal = rank === 1 ? '🥇' : rank === 2 ? '🥈' : rank === 3 ? '🥉' : `${rank}.`;
    
    // Format score with commas
    const formattedScore = entry.score ? Number(entry.score).toLocaleString() : '0';
    
    // Get avatar URL (with fallback)
    const avatarUrl = entry.avatar_url || `https://cdn.discordapp.com/embed/avatars/${parseInt(entry.discord_id) % 5}.png`;
    
    // Get highest role (with fallback)
    const highestRole = entry.highest_role || 'Member';
    
    // Determine score label based on game
    let scoreLabel = 'score';
    if (game === 'cheese-hunt') scoreLabel = 'clicks';
    else if (game === 'discord-race') scoreLabel = 'races';
    else if (game === 'cheese-rumble') scoreLabel = 'rumbles';
    else if (game === 'space-invaders') scoreLabel = 'kills';
    
    return `
      <div class="flex items-center justify-between p-4 rounded-lg ${rankClass || 'bg-gray-800/50'} border border-gray-600 hover:border-yellow-400 transition-all">
        <div class="flex items-center gap-4">
          <div class="text-2xl font-bold w-12 text-center">${medal}</div>
          <img src="${avatarUrl}" alt="${entry.discord_name || 'Unknown'}" class="w-12 h-12 rounded-full border-2 border-gray-500" onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'" />
          <div>
            <div class="font-semibold text-lg">${entry.discord_name || entry.discord_id || 'Unknown'}</div>
            <div class="text-xs font-bold ${getRoleColorClass(highestRole)}">${highestRole}</div>
          </div>
        </div>
        <div class="text-right">
          <div class="text-xl font-bold text-yellow-300">${formattedScore}</div>
          <div class="text-xs text-gray-400">${scoreLabel}</div>
        </div>
      </div>
    `;
  }).join('');
}

// Helper function to get role color class
function getRoleColorClass(role) {
  if (!role) return 'text-gray-400';
  const roleLower = role.toLowerCase();
  if (roleLower.includes('vip')) return 'text-yellow-400';
  if (roleLower.includes('holder')) return 'text-gray-300';
  if (roleLower.includes('champion')) return 'text-red-400';
  if (roleLower.includes('season tester') || roleLower.includes('wl')) return 'text-green-400';
  if (roleLower.includes('early bird')) return 'text-blue-400';
  if (roleLower.includes('cheese hunter')) return 'text-yellow-500';
  return 'text-gray-400';
}
```

**Update `loadLeaderboard()` Function:**
```javascript
// Display leaderboards (all 6 games)
displayLeaderboard('tetris', data.tetris || [], data.is_frozen);
displayLeaderboard('snake', data.snake || [], data.is_frozen);
displayLeaderboard('space-invaders', data.space_invaders || [], data.is_frozen);
displayLeaderboard('cheese-hunt', data.cheese_hunt || [], data.is_frozen);
displayLeaderboard('discord-race', data.discord_race || [], data.is_frozen);
displayLeaderboard('cheese-rumble', data.cheese_rumble || [], data.is_frozen);
```

---

## 🎨 **UI/UX ENHANCEMENTS**

### **Profile Picture Styling:**
- Size: 48px × 48px (w-12 h-12)
- Border: 2px gray border, changes to yellow on hover
- Shape: Fully rounded (rounded-full)
- Fallback: Default Discord avatar (avatar #0-4) if image fails to load

### **Role Display Styling:**
- Font: Small, bold text
- Colors:
  - VIP Holder: Yellow (#fbbf24)
  - Holder: Light gray (#d1d5db)
  - Champion: Red (#f87171)
  - Season Tester/WL: Green (#34d399)
  - Early Bird: Blue (#60a5fa)
  - Cheese Hunter: Orange/Yellow (#eab308)
  - Default/Member: Gray (#9ca3af)

### **Layout Improvements:**
- **Before:** Medal → Username → Discord ID → Score
- **After:** Medal → Avatar → Username + Role → Score

---

## 📊 **GAME-SPECIFIC METRICS**

### **1. Tetris**
- **Metric:** Best score (MAX(score))
- **Label:** "score"

### **2. Snake**
- **Metric:** Best score (MAX(score))
- **Label:** "score"

### **3. Space Invaders**
- **Metric:** Best score (MAX(score), rounded)
- **Label:** "kills"

### **4. Cheese Hunt**
- **Metric:** Highest total clicks (MAX(clicks))
- **Label:** "clicks"
- **Alternative:** Could show quest_clicks or unique_eggs

### **5. Discord Cheese Race**
- **Metric:** Total wins (COUNT(CASE WHEN position = 1))
- **Label:** "races" or "wins"
- **Alternative:** Total races participated

### **6. Cheese Rumble**
- **Metric:** Total wins (COUNT(CASE WHEN final_position = 1))
- **Label:** "rumbles" or "wins"
- **Alternative:** Total rumbles participated

---

## 🔍 **TESTING CHECKLIST**

### **Before Implementation:**
- [ ] Review current leaderboard API structure
- [ ] Verify database table structures for all 6 games
- [ ] Check season filtering logic matches user-game-missions.php
- [ ] Verify avatar_url exists in tbl_users
- [ ] Verify roles exist in tbl_user_roles

### **After Implementation:**
- [ ] Test all 6 leaderboards load correctly
- [ ] Test profile pictures display correctly
- [ ] Test highest role displays correctly
- [ ] Test fallback for missing avatars
- [ ] Test fallback for missing roles
- [ ] Test empty leaderboards (no data)
- [ ] Test frozen season leaderboards
- [ ] Test on mobile devices (responsive design)
- [ ] Verify season filtering works correctly
- [ ] Verify data matches profile page "Current Season Statistics"

---

## 🚨 **CRITICAL CONSIDERATIONS**

### **Performance:**
- **Issue:** Fetching user details for 60 entries (10 per game × 6 games)
- **Solution:** Extend API to join with tbl_users and tbl_user_roles in SQL
- **Result:** Single API call returns all data (avatar_url, roles, highest_role)

### **Database Queries:**
- **Issue:** Different field names (discord_id, user_id, user_wallet)
- **Solution:** Standardize in API queries using aliases
- **Result:** All games return consistent structure

### **Season Filtering:**
- **Issue:** Cheese Rumble uses timestamp filtering (no season column)
- **Solution:** Match logic from user-game-missions.php (timestamp >= season_start AND < season_end)
- **Result:** Consistent season filtering across all games

### **Role Priority:**
- **Issue:** Multiple roles per user - need to determine "highest"
- **Solution:** Use multiplier-based priority system
- **Result:** Consistent role display matching game mechanics

---

## 📝 **FILE MODIFICATIONS SUMMARY**

### **Files to Modify:**

1. **`api/dev/get-leaderboard.php`**
   - Add 3 new leaderboard functions
   - Add user info joins (avatar_url, roles)
   - Add highest role calculation
   - Update response structure

2. **`public/leaderboard.html`**
   - Add 3 new game sections (HTML)
   - Update `displayLeaderboard()` function (JavaScript)
   - Add `getRoleColorClass()` helper function
   - Update `loadLeaderboard()` to display all 6 games

### **Estimated Changes:**
- **API:** ~200 lines added/modified
- **Frontend:** ~100 lines added/modified

---

## ✅ **SUCCESS CRITERIA**

### **Functional Requirements:**
- ✅ All 6 games show leaderboards
- ✅ Profile pictures display for all users
- ✅ Highest role displays under username
- ✅ Season filtering works correctly
- ✅ Frozen leaderboard support maintained
- ✅ Empty state handling works

### **Visual Requirements:**
- ✅ Profile pictures are visible and properly sized
- ✅ Roles are color-coded correctly
- ✅ Layout is clean and organized
- ✅ Mobile-responsive design maintained

### **Technical Requirements:**
- ✅ Single API call returns all needed data
- ✅ Performance is acceptable (fast loading)
- ✅ Error handling works correctly
- ✅ Fallbacks work for missing data

---

## 🎯 **NEXT STEPS**

1. **Review this plan** with user
2. **Implement Phase 1** (Extend API)
3. **Test API** with all 6 games
4. **Implement Phase 2** (Frontend updates)
5. **Test complete system** (all 6 games, avatars, roles)
6. **Deploy and verify** on production

---

**STATUS:** 📋 **PLAN READY - AWAITING APPROVAL TO IMPLEMENT**
