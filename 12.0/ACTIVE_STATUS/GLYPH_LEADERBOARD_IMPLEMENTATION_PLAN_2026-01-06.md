# 🧩 GLYPH MEMORY LEADERBOARD - IMPLEMENTATION PLAN

**Date:** January 6, 2026  
**Status:** 📋 **PLANNING COMPLETE - READY FOR IMPLEMENTATION**  
**Goal:** Add leaderboard system to Glyph Memory game (same concept as Tetris/Snake)

---

## 🎯 **OVERVIEW**

Add a best leaderboard to Glyph Memory that displays:
- Top players by difficulty (Easy, Medium, Hard)
- Player names (discord_name)
- Completion times (formatted as MM:SS)
- Timestamps (when score was achieved)
- Season support (current season or frozen previous season)

**Pattern:** Follow exact same pattern as Tetris/Snake/Space Invaders leaderboards

---

## 📋 **IMPLEMENTATION CHECKLIST**

### **Phase 1: API Updates** ✅ **REQUIRED**

#### **1.1 Update `api/dev/get-leaderboard.php`**
- [ ] Add `glyph_memory` to the leaderboard API
- [ ] Query `tbl_glyph_memory_scores` table
- [ ] Support difficulty filtering (easy, medium, hard)
- [ ] Sort by `time_ms` ASC (lower is better)
- [ ] Include season support (current season or frozen)
- [ ] Format time_ms as MM:SS for display
- [ ] Return top 10 players per difficulty

**SQL Query Pattern:**
```sql
SELECT 
    discord_id,
    discord_name,
    difficulty,
    MIN(time_ms) as best_time_ms,  -- Best time per player per difficulty
    timestamp
FROM tbl_glyph_memory_scores 
WHERE difficulty = ? AND season = ?
GROUP BY discord_id, discord_name, difficulty
ORDER BY best_time_ms ASC, timestamp ASC
LIMIT 10
```

**API Response Structure:**
```json
{
  "success": true,
  "current_season": "Season 7",
  "display_season": "Season 7",
  "is_frozen": false,
  "glyph_memory": {
    "easy": [
      {
        "discord_id": "123456789",
        "discord_name": "Player1",
        "difficulty": "easy",
        "best_time_ms": 45000,
        "best_time_formatted": "00:45",
        "timestamp": "2026-01-06 12:30:00"
      }
    ],
    "medium": [...],
    "hard": [...]
  }
}
```

---

### **Phase 2: Frontend UI** ✅ **REQUIRED**

#### **2.1 Add Leaderboard Section to HTML (`public/glyph/glyph.html`)**
- [ ] Add leaderboard container in menu view
- [ ] Add difficulty tabs (Easy, Medium, Hard)
- [ ] Add leaderboard list container
- [ ] Style to match game's design (gold accents, dark theme)

**HTML Structure:**
```html
<!-- Leaderboard Section (in menuView) -->
<div id="leaderboardSection" class="leaderboard-section">
  <h3 class="leaderboard-title">🏆 Leaderboard</h3>
  <div class="leaderboard-tabs">
    <button class="tab-btn active" data-difficulty="easy">Easy</button>
    <button class="tab-btn" data-difficulty="medium">Medium</button>
    <button class="tab-btn" data-difficulty="hard">Hard</button>
  </div>
  <div id="leaderboardList" class="leaderboard-list">
    <!-- Leaderboard entries will be inserted here -->
  </div>
  <div id="leaderboardLoading" class="leaderboard-loading" hidden>Loading...</div>
  <div id="leaderboardError" class="leaderboard-error" hidden>Failed to load leaderboard</div>
</div>
```

#### **2.2 Add CSS Styling (`public/glyph/styles.css`)**
- [ ] Style leaderboard section (matches game theme)
- [ ] Style tabs (active/inactive states)
- [ ] Style leaderboard entries (rank, name, time, date)
- [ ] Responsive design (mobile-friendly)
- [ ] Loading and error states

**CSS Classes Needed:**
- `.leaderboard-section` - Container
- `.leaderboard-title` - Title styling
- `.leaderboard-tabs` - Tab container
- `.tab-btn` / `.tab-btn.active` - Tab buttons
- `.leaderboard-list` - List container
- `.leaderboard-entry` - Individual entry
- `.leaderboard-rank` - Rank number (1, 2, 3...)
- `.leaderboard-name` - Player name
- `.leaderboard-time` - Formatted time
- `.leaderboard-date` - Timestamp

#### **2.3 Add JavaScript Logic (`public/glyph/game.js`)**
- [ ] Add `fetchLeaderboard(difficulty)` function
- [ ] Add `displayLeaderboard(difficulty, data)` function
- [ ] Add tab switching logic
- [ ] Add loading state management
- [ ] Add error handling
- [ ] Call `fetchLeaderboard()` on page load
- [ ] Update leaderboard when difficulty changes

**JavaScript Functions:**
```javascript
// Fetch leaderboard from API
async function fetchLeaderboard(difficulty) {
  try {
    const response = await fetch('/api/dev/get-leaderboard.php');
    const data = await response.json();
    
    if (data.success && data.glyph_memory && data.glyph_memory[difficulty]) {
      displayLeaderboard(difficulty, data.glyph_memory[difficulty]);
    } else {
      showLeaderboardError();
    }
  } catch (error) {
    console.error('Error fetching leaderboard:', error);
    showLeaderboardError();
  }
}

// Display leaderboard entries
function displayLeaderboard(difficulty, entries) {
  const listEl = document.getElementById('leaderboardList');
  listEl.innerHTML = '';
  
  if (entries.length === 0) {
    listEl.innerHTML = '<div class="leaderboard-empty">No scores yet. Be the first!</div>';
    return;
  }
  
  entries.forEach((entry, index) => {
    const entryEl = document.createElement('div');
    entryEl.className = 'leaderboard-entry';
    entryEl.innerHTML = `
      <span class="leaderboard-rank">${index + 1}</span>
      <span class="leaderboard-name">${entry.discord_name || 'Guest'}</span>
      <span class="leaderboard-time">${entry.best_time_formatted || formatTime(entry.best_time_ms)}</span>
      <span class="leaderboard-date">${formatDate(entry.timestamp)}</span>
    `;
    listEl.appendChild(entryEl);
  });
}
```

---

### **Phase 3: Integration & Testing** ✅ **REQUIRED**

#### **3.1 Database Verification**
- [ ] Verify `tbl_glyph_memory_scores` table exists
- [ ] Verify indexes are created
- [ ] Test with sample data (multiple players, different difficulties)
- [ ] Verify season column is populated

#### **3.2 API Testing**
- [ ] Test API endpoint returns correct data
- [ ] Test difficulty filtering (easy, medium, hard)
- [ ] Test season support (current vs frozen)
- [ ] Test empty leaderboard (no scores)
- [ ] Test sorting (lowest time_ms first)

#### **3.3 Frontend Testing**
- [ ] Test leaderboard loads on page load
- [ ] Test tab switching (Easy → Medium → Hard)
- [ ] Test leaderboard updates when new score saved
- [ ] Test loading states
- [ ] Test error handling
- [ ] Test responsive design (mobile/desktop)
- [ ] Test with no scores (empty state)

#### **3.4 Integration Testing**
- [ ] Test complete flow: Play game → Save score → Leaderboard updates
- [ ] Test with Discord logged in
- [ ] Test with Discord not logged in (should still show leaderboard)
- [ ] Test multiple players on same difficulty
- [ ] Test best time per player (only show best, not all attempts)

---

## 🔧 **TECHNICAL DETAILS**

### **Database Table: `tbl_glyph_memory_scores`**
- ✅ Already exists (created January 6, 2026)
- ✅ Fields: `id`, `discord_id`, `discord_name`, `difficulty`, `time_ms`, `pairs_matched`, `timestamp`, `season`
- ✅ Indexes: `idx_glyph_discord_id`, `idx_glyph_difficulty`, `idx_glyph_time`, `idx_glyph_season`

### **API Endpoint: `/api/dev/get-leaderboard.php`**
- ✅ Already exists (handles tetris, snake, space_invaders)
- ⏳ **NEEDS UPDATE:** Add `glyph_memory` support

### **Score Saving: `/api/dev/save-score.php`**
- ✅ Already supports `glyph_memory` game type
- ✅ Saves to `tbl_glyph_memory_scores` table
- ✅ Includes season support

---

## 📝 **FILES TO MODIFY**

### **Backend (1 file):**
1. `api/dev/get-leaderboard.php` - Add glyph_memory support

### **Frontend (3 files):**
1. `public/glyph/glyph.html` - Add leaderboard HTML structure
2. `public/glyph/styles.css` - Add leaderboard CSS styling
3. `public/glyph/game.js` - Add leaderboard JavaScript logic

---

## 🎨 **UI/UX DESIGN**

### **Leaderboard Placement:**
- **Location:** Main menu (below difficulty selector, above "Start Game" button)
- **Visibility:** Always visible (not hidden behind a button)
- **Design:** Matches game's dark theme with gold accents

### **Leaderboard Entry Format:**
```
Rank | Player Name | Time | Date
-----|-------------|------|-----
  1  | Player1     | 00:45| Jan 6
  2  | Player2     | 01:12| Jan 6
  3  | Player3     | 01:30| Jan 5
```

### **Responsive Design:**
- Desktop: Full leaderboard with all columns
- Mobile: Compact view (rank, name, time only - date hidden or abbreviated)

---

## ✅ **SUCCESS CRITERIA**

1. ✅ Leaderboard displays top 10 players per difficulty
2. ✅ Shows player names (discord_name)
3. ✅ Shows formatted times (MM:SS)
4. ✅ Shows timestamps (formatted date)
5. ✅ Tabs switch between Easy/Medium/Hard
6. ✅ Updates when new scores are saved
7. ✅ Handles empty leaderboard gracefully
8. ✅ Handles API errors gracefully
9. ✅ Matches design of other games (Tetris/Snake)
10. ✅ Works on mobile and desktop

---

## 🚀 **IMPLEMENTATION ORDER**

1. **Step 1:** Update API (`get-leaderboard.php`) - Add glyph_memory support
2. **Step 2:** Add HTML structure (`glyph.html`) - Leaderboard container
3. **Step 3:** Add CSS styling (`styles.css`) - Leaderboard styles
4. **Step 4:** Add JavaScript logic (`game.js`) - Fetch and display
5. **Step 5:** Test complete flow - Play → Save → Display
6. **Step 6:** Verify responsive design - Mobile/Desktop
7. **Step 7:** Final polish - Error handling, loading states

---

## 📚 **REFERENCE DOCUMENTATION**

- **Game Scoring Rules:** `12.0/RULES/04_GAME_SCORING_SYSTEM_RULES.md`
- **Score Retrieval System:** `12.0/RULES/07_GAME_SCORE_RETRIEVAL_SYSTEM.md`
- **Glyph Memory Technical Doc:** `12.0/YEAR_END_2025/GAME_08_GLYPH_MEMORY_COMPLETE_TECHNICAL.md`
- **Leaderboard API:** `api/dev/get-leaderboard.php` (existing implementation)

---

## 🎯 **ESTIMATED TIME**

- **API Update:** 15-20 minutes
- **HTML Structure:** 10-15 minutes
- **CSS Styling:** 20-30 minutes
- **JavaScript Logic:** 30-40 minutes
- **Testing & Polish:** 20-30 minutes

**Total:** ~2 hours for complete implementation

---

**Status:** 📋 **PLAN COMPLETE - READY FOR IMPLEMENTATION**

