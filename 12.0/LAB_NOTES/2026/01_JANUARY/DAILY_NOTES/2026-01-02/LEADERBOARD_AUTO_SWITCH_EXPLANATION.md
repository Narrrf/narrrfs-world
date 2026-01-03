# 🏆 LEADERBOARD AUTO-SWITCH SYSTEM EXPLANATION

**Date:** January 2, 2026  
**Status:** ✅ **FULLY AUTOMATIC - NO ADDITIONAL WORK NEEDED**

---

## 🎯 **HOW THE LEADERBOARD SYSTEM WORKS**

### **1. Sorting System**

**Leaderboard is sorted PER GAME (not per user):**
- **Tetris:** Top 10 players sorted by highest score
- **Snake:** Top 10 players sorted by highest score  
- **Space Invaders:** Top 10 players sorted by highest score

**Sorting Logic:**
- Primary: Score (DESC) - Highest score first
- Secondary: Timestamp (ASC) - Earliest timestamp first (tiebreaker)

**Example:**
```
Tetris Leaderboard:
1. Player A - 2,500 DSPOINC
2. Player B - 2,000 DSPOINC
3. Player C - 1,500 DSPOINC
...
```

---

### **2. Auto-Switch Logic (Frozen → Active)**

**The system automatically switches from frozen Season 6 to active Season 7:**

#### **API Logic (`api/dev/get-leaderboard.php`):**

```php
// Check total scores across ALL 3 games combined (not per game)
$totalScoresStmt = $db->prepare("
    SELECT COUNT(*) as total_scores
    FROM tbl_tetris_scores 
    WHERE season = ? AND game IN ('tetris', 'snake', 'space_invaders')
");
$totalScoresStmt->execute([$currentSeason]);
$totalScoresAcrossAllGames = $totalScoresStmt->fetchColumn() ?: 0;

// If we have 3+ scores total across all games, use current season; otherwise use frozen
$useFrozenLeaderboard = ($totalScoresAcrossAllGames < 3);
```

**Decision Logic:**
- **< 3 total scores:** Shows frozen Season 6 leaderboard (from `tbl_historical_stats`)
- **>= 3 total scores:** Shows active Season 7 leaderboard (from `tbl_tetris_scores`)

**Examples:**
- **1 player plays 3 games (1 Tetris, 1 Snake, 1 Space Invaders) = 3 total scores** → Switches to Season 7 ✅
- **3 players each play 1 game (3 Tetris) = 3 total scores** → Switches to Season 7 ✅
- **2 players play 1 game each (2 Tetris) = 2 total scores** → Still shows frozen Season 6 ⏸️

---

### **3. What Happens When 3 Games Are Played**

**Scenario: Player plays 3 games (1 of each)**

1. **Game 1 (Tetris):** Score saved → Total scores = 1 → Still frozen Season 6
2. **Game 2 (Snake):** Score saved → Total scores = 2 → Still frozen Season 6
3. **Game 3 (Space Invaders):** Score saved → Total scores = 3 → **AUTOMATICALLY SWITCHES TO SEASON 7!** ✅

**What Changes Automatically:**
- ✅ Leaderboard data switches from `tbl_historical_stats` (Season 6) to `tbl_tetris_scores` (Season 7)
- ✅ `is_frozen` flag changes from `true` to `false`
- ✅ Frontend detects change and updates all UI elements:
  - Banner theme: Blue (frozen) → Green (active)
  - Title: "⏸️ Season 6 FROZEN" → "🎮 Season 7 ACTIVE"
  - Subtitle: "Frozen" → "Live Rankings"
  - All season status messages update dynamically

---

### **4. Frontend Auto-Update System**

**The `loadLeaderboard()` function automatically updates everything:**

```javascript
// Called on page load and can be called manually
async function loadLeaderboard() {
  const response = await fetch(`${apiBaseUrl}/api/dev/get-leaderboard.php`);
  const data = await response.json();
  
  if (data.is_frozen) {
    // Show frozen Season 6 (blue theme)
    // Update all banners, messages, leaderboard title
  } else {
    // Show active Season 7 (green theme)
    // Update all banners, messages, leaderboard title
  }
  
  // Populate leaderboard lists (automatically sorted by score)
  // Tetris, Snake, Space Invaders - each sorted independently
}
```

**UI Elements Updated Automatically:**
- ✅ Leaderboard title and subtitle
- ✅ Season status banner (top of page)
- ✅ Main season banner
- ✅ Wallet season banner
- ✅ Mint season status
- ✅ All season status messages throughout page
- ✅ Leaderboard data (Tetris, Snake, Space Invaders)

---

## ✅ **NO ADDITIONAL WORK NEEDED**

### **After Pushing Code:**

1. **Initial State (0 scores):**
   - Shows frozen Season 6 leaderboard (from historical stats)
   - Blue theme, "FROZEN" messages
   - Leaderboard shows Season 6 final rankings

2. **After 1-2 scores:**
   - Still shows frozen Season 6 leaderboard
   - No change in UI

3. **After 3+ scores (automatic switch):**
   - **AUTOMATICALLY** switches to active Season 7 leaderboard
   - Green theme, "ACTIVE" messages
   - Leaderboard shows Season 7 live rankings
   - All UI elements update automatically

**No manual intervention needed!** The system handles everything automatically.

---

## 🔍 **VERIFICATION CHECKLIST**

### **To Verify Auto-Switch Works:**

1. **Check API Response:**
   ```bash
   curl https://narrrfs.world/api/dev/get-leaderboard.php
   ```
   - Look for `"is_frozen": true` (frozen) or `"is_frozen": false` (active)
   - Check `"display_season"` - should be "Season 6" (frozen) or "Season 7" (active)

2. **Check Database:**
   ```sql
   SELECT COUNT(*) FROM tbl_tetris_scores 
   WHERE season = 'Season 7' AND game IN ('tetris', 'snake', 'space_invaders');
   ```
   - < 3 = frozen Season 6
   - >= 3 = active Season 7

3. **Check Frontend:**
   - Open `profile.html`
   - Check leaderboard title (should say "FROZEN" or "ACTIVE")
   - Check banner theme (blue = frozen, green = active)
   - Check leaderboard data (should match current season)

---

## 📊 **DATA SOURCES**

### **Frozen Leaderboard (Season 6):**
- **Source:** `tbl_historical_stats` table
- **Query:** `WHERE game = ? AND season = 'Season 6'`
- **Data:** Final rankings from Season 6 (archived)

### **Active Leaderboard (Season 7):**
- **Source:** `tbl_tetris_scores` table
- **Query:** `WHERE game = ? AND season = 'Season 7'`
- **Data:** Live rankings from Season 7 (current)

---

## 🎯 **SUMMARY**

### **✅ Fully Automatic System:**
- ✅ Leaderboard sorted per game (by score)
- ✅ Auto-switches from frozen to active when 3+ scores exist
- ✅ Frontend updates all UI elements automatically
- ✅ No manual intervention needed
- ✅ Works immediately after code push

### **✅ What Happens:**
1. **0-2 scores:** Shows frozen Season 6 (blue theme)
2. **3+ scores:** Automatically switches to active Season 7 (green theme)
3. **All UI updates:** Banners, messages, leaderboard data - all automatic

### **✅ No Additional Work:**
- Just push the code
- System handles everything automatically
- Leaderboard will switch when 3+ games are played
- All UI elements update dynamically

---

**The system is production-ready and fully automatic! 🚀**

